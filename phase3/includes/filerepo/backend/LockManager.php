<?php
/**
 * FileBackend helper class for handling file locking.
 * Locks on resource keys can either be shared or exclusive.
 * 
 * Implementations must keep track of what is locked by this proccess
 * in-memory and support nested locking calls (using reference counting).
 * At least LOCK_UW and LOCK_EX must be implemented. LOCK_SH can be a no-op.
 * Locks should either be non-blocking or have low wait timeouts.
 * 
 * Subclasses should avoid throwing exceptions at all costs.
 * 
 * @ingroup FileBackend
 */
abstract class LockManager {
	/* Lock types; stronger locks have higher values */
	const LOCK_SH = 1; // shared lock (for reads)
	const LOCK_UW = 2; // shared lock (for reads used to write elsewhere)
	const LOCK_EX = 3; // exclusive lock (for writes)

	/** @var Array Mapping of lock types to the type actually used */
	protected $lockTypeMap = array(
		self::LOCK_SH => self::LOCK_SH,
		self::LOCK_UW => self::LOCK_EX, // subclasses may use self::LOCK_SH
		self::LOCK_EX => self::LOCK_EX
	);

	/**
	 * Construct a new instance from configuration
	 *
	 * @param $config Array
	 */
	public function __construct( array $config ) {}

	/**
	 * Lock the resources at the given abstract paths
	 * 
	 * @param $paths Array List of resource names
	 * @param $type integer LockManager::LOCK_* constant
	 * @return Status 
	 */
	final public function lock( array $paths, $type = self::LOCK_EX ) {
		$keys = array_unique( array_map( 'sha1', $paths ) );
		return $this->doLock( $keys, $this->lockTypeMap[$type] );
	}

	/**
	 * Unlock the resources at the given abstract paths
	 * 
	 * @param $paths Array List of storage paths
	 * @param $type integer LockManager::LOCK_* constant
	 * @return Status 
	 */
	final public function unlock( array $paths, $type = self::LOCK_EX ) {
		$keys = array_unique( array_map( 'sha1', $paths ) );
		return $this->doUnlock( $keys, $this->lockTypeMap[$type] );
	}

	/**
	 * Lock resources with the given keys and lock type
	 * 
	 * @param $key Array List of keys to lock (40 char hex hashes)
	 * @param $type integer LockManager::LOCK_* constant
	 * @return string
	 */
	abstract protected function doLock( array $keys, $type );

	/**
	 * Unlock resources with the given keys and lock type
	 * 
	 * @param $key Array List of keys to unlock (40 char hex hashes)
	 * @param $type integer LockManager::LOCK_* constant
	 * @return string
	 */
	abstract protected function doUnlock( array $keys, $type );
}

/**
 * Simple version of LockManager based on using FS lock files
 *
 * This should work fine for small sites running off one server.
 * Do not use this with 'lockDir' set to an NFS mount unless the
 * NFS client is at least version 2.6.12. Otherwise, the BSD flock()
 * locks will be ignored; see http://nfs.sourceforge.net/#section_d.
 */
class FSLockManager extends LockManager {
	/** @var Array Mapping of lock types to the type actually used */
	protected $lockTypeMap = array(
		self::LOCK_SH => self::LOCK_SH,
		self::LOCK_UW => self::LOCK_SH,
		self::LOCK_EX => self::LOCK_EX
	);

	protected $lockDir; // global dir for all servers

	/** @var Array Map of (locked key => lock type => count) */
	protected $locksHeld = array();
	/** @var Array Map of (locked key => lock type => lock file handle) */
	protected $handles = array();

	function __construct( array $config ) {
		$this->lockDir = $config['lockDirectory'];
	}

	protected function doLock( array $keys, $type ) {
		$status = Status::newGood();

		$lockedKeys = array(); // files locked in this attempt
		foreach ( $keys as $key ) {
			$subStatus = $this->doSingleLock( $key, $type );
			$status->merge( $subStatus );
			if ( $status->isOK() ) {
				// Don't append to $lockedKeys if $key is already locked.
				// We do NOT want to unlock the key if we have to rollback.
				if ( $subStatus->isGood() ) { // no warnings/fatals?
					$lockedKeys[] = $key;
				}
			} else {
				// Abort and unlock everything
				$status->merge( $this->doUnlock( $lockedKeys, $type ) );
				return $status;
			}
		}

		return $status;
	}

	protected function doUnlock( array $keys, $type ) {
		$status = Status::newGood();

		foreach ( $keys as $key ) {
			$status->merge( $this->doSingleUnlock( $key, $type ) );
		}

		return $status;
	}

	/**
	 * Lock a single resource key
	 *
	 * @param $key string
	 * @param $type integer
	 * @return Status 
	 */
	protected function doSingleLock( $key, $type ) {
		$status = Status::newGood();

		if ( isset( $this->locksHeld[$key][$type] ) ) {
			++$this->locksHeld[$key][$type];
		} elseif ( isset( $this->locksHeld[$key][self::LOCK_EX] ) ) {
			$this->locksHeld[$key][$type] = 1;
		} else {
			wfSuppressWarnings();
			$handle = fopen( $this->getLockPath( $key ), 'a+' );
			wfRestoreWarnings();
			if ( !$handle ) { // lock dir missing?
				wfMkdirParents( $this->lockDir );
				wfSuppressWarnings();
				$handle = fopen( $this->getLockPath( $key ), 'a+' ); // try again
				wfRestoreWarnings();
			}
			if ( $handle ) {
				// Either a shared or exclusive lock
				$lock = ( $type == self::LOCK_SH ) ? LOCK_SH : LOCK_EX;
				if ( flock( $handle, $lock | LOCK_NB ) ) {
					// Record this lock as active
					$this->locksHeld[$key][$type] = 1;
					$this->handles[$key][$type] = $handle;
				} else {
					fclose( $handle );
					$status->fatal( 'lockmanager-fail-acquirelock', $key );
				}
			} else {
				$status->fatal( 'lockmanager-fail-openlock', $key );
			}
		}

		return $status;
	}

	/**
	 * Unlock a single resource key
	 * 
	 * @param $key string
	 * @param $type integer
	 * @return Status 
	 */
	protected function doSingleUnlock( $key, $type ) {
		$status = Status::newGood();

		if ( !isset( $this->locksHeld[$key] ) ) {
			$status->warning( 'lockmanager-notlocked', $key );
		} elseif ( !isset( $this->locksHeld[$key][$type] ) ) {
			$status->warning( 'lockmanager-notlocked', $key );
		} else {
			$handlesToClose = array();
			--$this->locksHeld[$key][$type];
			if ( $this->locksHeld[$key][$type] <= 0 ) {
				unset( $this->locksHeld[$key][$type] );
				// If a LOCK_SH comes in while we have a LOCK_EX, we don't
				// actually add a handler, so check for handler existence.
				if ( isset( $this->handles[$key][$type] ) ) {
					// Mark this handle to be unlocked and closed
					$handlesToClose[] = $this->handles[$key][$type];
					unset( $this->handles[$key][$type] );
				}
			}
			// Unlock handles to release locks and delete
			// any lock files that end up with no locks on them...
			if ( wfIsWindows() ) {
				// Windows: for any process, including this one,
				// calling unlink() on a locked file will fail
				$status->merge( $this->closeLockHandles( $key, $handlesToClose ) );
				$status->merge( $this->pruneKeyLockFiles( $key ) );
			} else {
				// Unix: unlink() can be used on files currently open by this 
				// process and we must do so in order to avoid race conditions
				$status->merge( $this->pruneKeyLockFiles( $key ) );
				$status->merge( $this->closeLockHandles( $key, $handlesToClose ) );
			}
		}

		return $status;
	}

	private function closeLockHandles( $key, array $handlesToClose ) {
		$status = Status::newGood();
		foreach ( $handlesToClose as $handle ) {
			wfSuppressWarnings();
			if ( !flock( $handle, LOCK_UN ) ) {
				$status->fatal( 'lockmanager-fail-releaselock', $key );
			}
			if ( !fclose( $handle ) ) {
				$status->warning( 'lockmanager-fail-closelock', $key );
			}
			wfRestoreWarnings();
		}
		return $status;
	}

	private function pruneKeyLockFiles( $key ) {
		$status = Status::newGood();
		if ( !count( $this->locksHeld[$key] ) ) {
			wfSuppressWarnings();
			# No locks are held for the lock file anymore
			if ( !unlink( $this->getLockPath( $key ) ) ) {
				$status->warning( 'lockmanager-fail-deletelock', $key );
			}
			wfRestoreWarnings();
			unset( $this->locksHeld[$key] );
			unset( $this->handles[$key] );
		}
		return $status;
	}

	/**
	 * Get the path to the lock file for a key
	 * @param $key string
	 * @return string
	 */
	protected function getLockPath( $key ) {
		return "{$this->lockDir}/{$key}.lock";
	}

	function __destruct() {
		// Make sure remaining locks get cleared for sanity
		foreach ( $this->locksHeld as $key => $locks ) {
			$this->doSingleUnlock( $key, 0 );
		}
	}
}

/**
 * Version of LockManager based on using DB table locks.
 * This is meant for multi-wiki systems that may share share files.
 *
 * All lock requests for a resource, identified by a hash string, will map
 * to one bucket. Each bucket maps to one or several peer DBs, on different
 * servers, each having the file_locks.sql tables (with row-level locking).
 * This does not use GET_LOCK() per http://bugs.mysql.com/bug.php?id=1118.
 *
 * A majority of peer DBs must agree for a lock to be acquired.
 * As long as one peer DB is up, lock requests will not be blocked
 * just because another peer DB cannot be contacted. A global status
 * cache can be setup to track DBs that recently missed queries; such
 * DBs will not be trusted for obtaining locks.
 * 
 * For performance, a small lock-wait timeout should be set via server config.
 * In innoDB, this can done via the innodb_lock_wait_timeout setting.
 */
class DBLockManager extends LockManager {
	/** @var Array Map of bucket indexes to peer sets */
	protected $dbsByBucket; // (bucket index => (ldb1, ldb2, ...))
	/** @var BagOStuff */
	protected $statusCache;

	protected $webTimeout; // integer number of seconds
	protected $cliTimeout; // integer number of seconds
	protected $safeDelay; // integer number of seconds

	/** @var Array Map of (locked key => lock type => count) */
	protected $locksHeld = array();
	/** @var Array Map Lock-active database connections (DB name => Database) */
	protected $activeConns = array();

	/**
	 * Construct a new instance from configuration.
	 * $config paramaters include:
	 *     'dbsByBucket' : Array of 1-16 consecutive integer keys, starting from 0, with
	 *                     a list of DB names (peers) as values. Each list should have
	 *                     an odd number of items and each DB should have its own server.
	 *     'webTimeout'  : Lock timeout (seconds) for non-CLI scripts. [optional]
	 *                     This tells the DB server how long to wait before assuming
	 *                     connection failure and releasing all the locks for a session.
	 *     'cliTimeout'  : Lock timeout (seconds) for CLI scripts. [optional]
	 *                     This tells the DB server how long to wait before assuming
	 *                     connection failure and releasing all the locks for a session.
	 *     'safeDelay'   : Seconds to mistrust a DB after restart/query loss. [optional]
	 *                     This should reflect the highest max_execution_time that PHP
	 *                     scripts might use on a wiki. Locks are lost on DB server restart.
	 *
	 * @param Array $config 
	 */
	public function __construct( array $config ) {
		// Sanitize dbsByBucket config to prevent PHP errors
		$this->dbsByBucket = array_filter( $config['dbsByBucket'], 'is_array' );
		$this->dbsByBucket = array_values( $this->dbsByBucket ); // consecutive

		if ( isset( $config['webTimeout'] ) ) {
			$this->webTimeout = $config['webTimeout'];
		} else {
			$met = ini_get( 'max_execution_time' );
			$this->webTimeout = $met ? $met : 60; // use some same amount if 0
		}
		$this->cliTimeout = isset( $config['cliTimeout'] )
			? $config['cliTimeout']
			: 60; // some sane amount
		$this->safeDelay = isset( $config['safeDelay'] )
			? $config['safeDelay']
			: max( $this->cliTimeout, $this->webTimeout ); // cover worst case

		$this->statusCache = wfGetMainCache(); // tracks peers that couldn't be queried recently
	}

	protected function doLock( array $keys, $type ) {
		$status = Status::newGood();

		$keysToLock = array();
		// Get locks that need to be acquired (buckets => locks)...
		foreach ( $keys as $key ) {
			if ( isset( $this->locksHeld[$key][$type] ) ) {
				++$this->locksHeld[$key][$type];
			} elseif ( isset( $this->locksHeld[$key][self::LOCK_EX] ) ) {
				$this->locksHeld[$key][$type] = 1;
			} else {
				$bucket = $this->getBucketFromKey( $key );
				$keysToLock[$bucket][] = $key;
			}
		}

		$lockedKeys = array(); // files locked in this attempt
		// Attempt to acquire these locks...
		foreach ( $keysToLock as $bucket => $keys ) {
			// Try to acquire the locks for this bucket
			$res = $this->doLockingQueryAll( $bucket, $keys, $type );
			if ( $res === 'cantacquire' ) {
				// Resources already locked by another process.
				// Abort and unlock everything we just locked.
				$status->fatal( 'lockmanager-fail-acquirelocks', implode( ', ', $keys ) );
				$status->merge( $this->doUnlock( $lockedKeys, $type ) );
				return $status;
			} elseif ( $res !== true ) {
				// Couldn't contact any DBs for this bucket.
				// Abort and unlock everything we just locked.
				$status->fatal( 'lockmanager-fail-db-bucket', $bucket );
				$status->merge( $this->doUnlock( $lockedKeys, $type ) );
				return $status;
			}
			// Record these locks as active
			foreach ( $keys as $key ) {
				$this->locksHeld[$key][$type] = 1; // locked
			}
			// Keep track of what locks were made in this attempt
			$lockedKeys = array_merge( $lockedKeys, $keys );
		}

		return $status;
	}

	protected function doUnlock( array $keys, $type ) {
		$status = Status::newGood();

		foreach ( $keys as $key ) {
			if ( !isset( $this->locksHeld[$key] ) ) {
				$status->warning( 'lockmanager-notlocked', $key );
			} elseif ( !isset( $this->locksHeld[$key][$type] ) ) {
				$status->warning( 'lockmanager-notlocked', $key );
			} else {
				--$this->locksHeld[$key][$type];
				if ( $this->locksHeld[$key][$type] <= 0 ) {
					unset( $this->locksHeld[$key][$type] );
				}
				if ( !count( $this->locksHeld[$key] ) ) {
					unset( $this->locksHeld[$key] ); // no SH or EX locks left for key
				}
			}
		}

		// Reference count the locks held and COMMIT when zero
		if ( !count( $this->locksHeld ) ) {
			$status->merge( $this->finishLockTransactions() );
		}

		return $status;
	}

	/**
	 * Get a connection to a lock DB and acquire locks on $keys
	 *
	 * @param $lockDb string
	 * @param $keys Array
	 * @param $type integer LockManager::LOCK_EX or LockManager::LOCK_SH
	 * @return bool Resources able to be locked
	 * @throws DBError
	 */
	protected function doLockingQuery( $lockDb, array $keys, $type ) {
		if ( $type == self::LOCK_EX ) { // writer locks
			$db = $this->getConnection( $lockDb );
			# Actually do the locking queries...
			$data = array();
			foreach ( $keys as $key ) {
				$data[] = array( 'fle_key' => $key );
			}
			# Wait on any existing writers and block new ones if we get in
			$db->insert( 'file_locks_exclusive', $data, __METHOD__ );
		}
		return true;
	}

	/**
	 * Attempt to acquire locks with the peers for a bucket.
	 * This should avoid throwing any exceptions.
	 *
	 * @param $bucket integer
	 * @param $keys Array List of resource keys to lock
	 * @param $type integer LockManager::LOCK_EX or LockManager::LOCK_SH
	 * @return bool|string One of (true, 'cantacquire', 'dberrors')
	 */
	protected function doLockingQueryAll( $bucket, array $keys, $type ) {
		$yesVotes = 0; // locks made on trustable DBs
		$votesLeft = count( $this->dbsByBucket[$bucket] ); // remaining DBs
		$quorum = floor( $votesLeft/2 + 1 ); // simple majority
		// Get votes for each DB, in order, until we have enough...
		foreach ( $this->dbsByBucket[$bucket] as $index => $lockDb ) {
			// Check that DB is not *known* to be down
			if ( $this->cacheCheckFailures( $lockDb ) ) {
				try {
					// Attempt to acquire the lock on this DB
					if ( !$this->doLockingQuery( $lockDb, $keys, $type ) ) {
						return 'cantacquire'; // vetoed; resource locked
					}
					// Check that DB has no signs of lock loss
					if ( $this->checkUptime( $lockDb ) ) {
						++$yesVotes; // success for this peer
						if ( $yesVotes >= $quorum ) {
							return true; // lock obtained
						}
					}
				} catch ( DBConnectionError $e ) {
					$this->cacheRecordFailure( $lockDb );
				} catch ( DBError $e ) {
					if ( $this->lastErrorIndicatesLocked( $lockDb ) ) {
						return 'cantacquire'; // vetoed; resource locked
					}
				}
			}
			$votesLeft--;
			$votesNeeded = $quorum - $yesVotes;
			if ( $votesNeeded > $votesLeft ) {
				// In "trust cache" mode we don't have to meet the quorum
				break; // short-circuit
			}
		}
		// At this point, we must not have meet the quorum
		return 'dberrors'; // not enough votes to ensure correctness
	}

	/**
	 * Get a new connection to a lock DB
	 *
	 * @param $lockDb string
	 * @return Database
	 * @throws DBError
	 */
	protected function getConnection( $lockDb ) {
		if ( !isset( $this->activeConns[$lockDb] ) ) {
			$this->activeConns[$lockDb] = wfGetDB( DB_MASTER, array(), $lockDb );
			$this->activeConns[$lockDb]->begin(); // start transaction
			# If the connection drops, try to avoid letting the DB rollback
			# and release the locks before the file operations are finished.
			# This won't handle the case of DB server reboots however.
			$options = array();
			if ( php_sapi_name() == 'cli' ) { // maintenance scripts
				if ( $this->cliTimeout > 0 ) {
					$options['connTimeout'] = $this->cliTimeout;
				}
			} else { // web requests
				if ( $this->webTimeout > 0 ) {
					$options['connTimeout'] = $this->webTimeout;
				}
			}
			$this->activeConns[$lockDb]->setSessionOptions( $options );
			$this->initConnection( $lockDb, $this->activeConns[$lockDb] );
		}
		return $this->activeConns[$lockDb];
	}

	/**
	 * Do additional initialization for new lock DB connection
	 *
	 * @param $lockDb string
	 * @param $db Database
	 * @return void
	 * @throws DBError
	 */
	protected function initConnection( $lockDb, DatabaseBase $db ) {}

	/**
	 * Commit all changes to lock-active databases.
	 * This should avoid throwing any exceptions.
	 *
	 * @return Status
	 */
	protected function finishLockTransactions() {
		$status = Status::newGood();
		foreach ( $this->activeConns as $lockDb => $db ) {
			try {
				$db->rollback(); // finish transaction and kill any rows
			} catch ( DBError $e ) {
				$status->fatal( 'lockmanager-fail-db-release', $lockDb );
				// oh well; best effort
			}
		}
		$this->activeConns = array();
		return $status;
	}

	/**
	 * Check if the last DB error for $lockDb indicates
	 * that a requested resource was locked by another process.
	 * This should avoid throwing any exceptions.
	 * 
	 * @param $lockDb string
	 * @return bool
	 */
	protected function lastErrorIndicatesLocked( $lockDb ) {
		if ( isset( $this->activeConns[$lockDb] ) ) { // sanity
			$db = $this->activeConns[$lockDb];
			return ( $db->wasDeadlock() || $db->wasLockTimeout() );
		}
		return false;
	}

	/**
	 * Checks if the DB server did not recently restart.
	 * This curtails the problem of locks falling off when DB servers restart.
	 * 
	 * @param $lockDb string
	 * @return bool
	 * @throws DBError
	 */
	protected function checkUptime( $lockDb ) {
		if ( isset( $this->activeConns[$lockDb] ) ) { // sanity
			if ( $this->safeDelay > 0 ) {
				$db = $this->activeConns[$lockDb];
				if ( $db->getServerUptime() < $this->safeDelay ) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Checks if the DB has not recently had connection/query errors.
	 * When in "trust cache" mode, this curtails the problem of peers occasionally
	 * missing locks. Otherwise, it just avoids wasting time on connection attempts.
	 * 
	 * @param $lockDb string
	 * @return bool
	 */
	protected function cacheCheckFailures( $lockDb ) {
		if ( $this->statusCache && $this->safeDelay > 0 ) {
			$key = $this->getMissKey( $lockDb );
			$misses = $this->statusCache->get( $key );
			return !$misses;
		}
		return true;
	}

	/**
	 * Log a lock request failure to the cache.
	 *
	 * Worst case scenario is that a resource lock was only
	 * on one peer and then that peer is restarted or goes down.
	 * Clients trying to get locks need to know if a DB server is down.
	 *
	 * @param $lockDb string
	 * @return bool Success
	 */
	protected function cacheRecordFailure( $lockDb ) {
		if ( $this->statusCache && $this->safeDelay > 0 ) {
			$key = $this->getMissKey( $lockDb );
			$misses = $this->statusCache->get( $key );
			if ( $misses ) {
				return $this->statusCache->incr( $key );
			} else {
				return $this->statusCache->add( $key, 1, $this->safeDelay );
			}
		}
		return true;
	}

	/**
	 * Get a cache key for recent query misses for a DB
	 *
	 * @param $lockDb string
	 * @return string
	 */
	protected function getMissKey( $lockDb ) {
		return "lockmanager:querymisses:$lockDb";
	}

	/**
	 * Get the bucket for lock key.
	 * This should avoid throwing any exceptions.
	 *
	 * @param $key string (40 char hex key)
	 * @return integer
	 */
	protected function getBucketFromKey( $key ) {
		$prefix = substr( $key, 0, 2 ); // first 2 hex chars (8 bits)
		return intval( base_convert( $prefix, 16, 10 ) ) % count( $this->dbsByBucket );
	}

	/**
	 * Make sure remaining locks get cleared for sanity
	 */
	function __destruct() {
		$this->finishLockTransactions();
	}
}

class MySqlLockManager extends DBLockManager {
	/** @var Array Mapping of lock types to the type actually used */
	protected $lockTypeMap = array(
		self::LOCK_SH => self::LOCK_SH,
		self::LOCK_UW => self::LOCK_SH,
		self::LOCK_EX => self::LOCK_EX
	);

	/** @var Array Map of (DB name => original transaction isolation) */
	protected $trxIso = array();

	protected function initConnection( $lockDb, DatabaseBase $db ) {
		# Get the original transaction level for the DB server
		$row = $db->query( "SELECT @@tx_isolation AS tx_iso;" )->fetchObject();
		# Convert "REPEATABLE-READ" => "REPEATABLE READ" for SET query
		$this->trxIso[$lockDb] = str_replace( '-', ' ', $row->tx_iso );
	}

	protected function doLockingQuery( $lockDb, array $keys, $type ) {
		$ok = true;
		# Actually do the locking queries...
		if ( $type == self::LOCK_SH ) { // reader locks
			$db = $this->getConnection( $lockDb );
			$data = array();
			foreach ( $keys as $key ) {
				$data[] = array( 'fls_key' => $key );
			}
			# Block new writers...
			$db->insert( 'file_locks_shared', $data, __METHOD__ );
			# Bail if there are any existing writers...
			$db->query( "SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;" );
			$ok = !$db->selectField( 'file_locks_exclusive', '1',
				array( 'fle_key' => $keys ),
				__METHOD__
			);
			$db->query( "SET SESSION TRANSACTION ISOLATION LEVEL {$this->trxIso[$lockDb]};" );
		} elseif ( $type == self::LOCK_EX ) { // writer locks
			$db = $this->getConnection( $lockDb );
			$data = array();
			foreach ( $keys as $key ) {
				$data[] = array( 'fle_key' => $key );
			}
			# Block new readers/writers and wait on any existing writers
			$db->insert( 'file_locks_exclusive', $data, __METHOD__ );
			# Bail if there are any existing readers...
			$db->query( "SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;" );
			$ok = !$db->selectField( 'file_locks_shared', '1',
				array( 'fls_key' => $keys ),
				__METHOD__
			);
			$db->query( "SET SESSION TRANSACTION ISOLATION LEVEL {$this->trxIso[$lockDb]};" );
		}
		return $ok;
	}
}

/**
 * Simple version of LockManager that does nothing
 */
class NullLockManager extends LockManager {
	protected function doLock( array $keys, $type ) {
		return Status::newGood();
	}

	protected function doUnlock( array $keys, $type ) {
		return Status::newGood();
	}
}
