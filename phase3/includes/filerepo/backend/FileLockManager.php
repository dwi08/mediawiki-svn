<?php
/**
 * FileBackend helper class for handling file locking.
 * Locks on resource keys can either be shared or exclusive.
 * 
 * Implemenations can keep track of what is locked in the process cache.
 * This can reduce hits to external resources for lock()/unlock() calls.
 * 
 * Subclasses should avoid throwing exceptions at all costs.
 */
abstract class FileLockManager {
	/* Lock types; stronger locks have high values */
	const LOCK_SH = 1; // shared lock (for reads)
	const LOCK_EX = 2; // exclusive lock (for writes)

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
	 * @param $type integer FileLockManager::LOCK_EX, FileLockManager::LOCK_SH
	 * @return Status 
	 */
	final public function lock( array $paths, $type = self::LOCK_EX ) {
		$keys = array_map( 'sha1', $paths );
		return $this->doLock( $keys, $type );
	}

	/**
	 * Unlock the resources at the given abstract paths
	 * 
	 * @param $paths Array List of storage paths
	 * @return Status 
	 */
	final public function unlock( array $paths ) {
		$keys = array_map( 'sha1', $paths );
		return $this->doUnlock( $keys, 0 );
	}

	/**
	 * Lock a resource with the given key
	 * 
	 * @param $key Array List of keys to lock (40 char hex hashes)
	 * @param $type integer FileLockManager::LOCK_EX, FileLockManager::LOCK_SH
	 * @return string
	 */
	abstract protected function doLock( array $keys, $type );

	/**
	 * Unlock a resource with the given key.
	 * If $type is given, then only locks of that type should be cleared.
	 * 
	 * @param $key Array List of keys to unlock (40 char hex hashes)
	 * @param $type integer FileLockManager::LOCK_EX, FileLockManager::LOCK_SH, or 0
	 * @return string
	 */
	abstract protected function doUnlock( array $keys, $type );
}

/**
 * Simple version of FileLockManager based on using FS lock files
 *
 * This should work fine for small sites running off one server.
 * Do not use this with 'lockDir' set to an NFS mount unless the
 * NFS client is at least version 2.6.12. Otherwise, the BSD flock()
 * locks will be ignored; see http://nfs.sourceforge.net/#section_d.
 */
class FSFileLockManager extends FileLockManager {
	protected $lockDir; // global dir for all servers
	/** @var Array Map of (locked key => lock type => lock file handle) */
	protected $handles = array();

	function __construct( array $config ) {
		$this->lockDir = $config['lockDirectory'];
	}

	function doLock( array $keys, $type ) {
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

	function doUnlock( array $keys, $type ) {
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

		if ( isset( $this->handles[$key][$type] ) ) {
			$status->warning( 'lockmanager-alreadylocked', $key );
		} elseif ( isset( $this->handles[$key][self::LOCK_EX] ) ) {
			$status->warning( 'lockmanager-alreadylocked', $key );
		} else {
			wfSuppressWarnings();
			$handle = fopen( $this->getLockPath( $key ), 'c' );
			wfRestoreWarnings();
			if ( !$handle ) { // lock dir missing?
				wfMkdirParents( $this->lockDir );
				wfSuppressWarnings();
				$handle = fopen( $this->getLockPath( $key ), 'c' ); // try again
				wfRestoreWarnings();
			}
			if ( $handle ) {
				// Either a shared or exclusive lock
				$lock = ( $type == self::LOCK_SH ) ? LOCK_SH : LOCK_EX;
				if ( flock( $handle, $lock | LOCK_NB ) ) {
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

		if ( !isset( $this->handles[$key] ) ) {
			$status->warning( 'lockmanager-notlocked', $key );
		} elseif ( $type && !isset( $this->handles[$key][$type] ) ) {
			$status->warning( 'lockmanager-notlocked', $key );
		} else {
			foreach ( $this->handles[$key] as $lockType => $handle ) {
				if ( $type && $lockType != $type ) {
					continue; // only unlock locks of type $type
				}
				wfSuppressWarnings();
				if ( !flock( $this->handles[$key][$lockType], LOCK_UN ) ) {
					$status->fatal( 'lockmanager-fail-releaselock', $key );
				}
				if ( !fclose( $this->handles[$key][$lockType] ) ) {
					$status->warning( 'lockmanager-fail-closelock', $key );
				}
				wfRestoreWarnings();
				unset( $this->handles[$key][$lockType] );
			}
			if ( !count( $this->handles[$key] ) ) {
				wfSuppressWarnings();
				# No locks are held for the lock file anymore
				if ( !unlink( $this->getLockPath( $key ) ) ) {
					$status->warning( 'lockmanager-fail-deletelock', $key );
				}
				wfRestoreWarnings();
				unset( $this->handles[$key] );
			}
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
		// Make sure remaining files get cleared for sanity
		foreach ( $this->handles as $key => $locks ) {
			foreach ( $locks as $type => $handle ) {
				flock( $handle, LOCK_UN ); // PHP 5.3 will not do this automatically
				fclose( $handle );
			}
			unlink( $this->getLockPath( $key ) );
		}
	}
}

/**
 * Version of FileLockManager based on using DB table locks.
 *
 * This is meant for multi-wiki systems that may share share files.
 * One or several database servers are set up having a `file_locking`
 * table with one field, fl_key, the PRIMARY KEY. The table engine should
 * have row-level locking. All lock requests for a resource, identified
 * by a hash string, will map to one bucket.
 * 
 * Each bucket maps to one or several pier servers.
 * All pier servers must agree to a lock in order for it to be acquired.
 * As long as one pier server is up, lock requests will not be blocked
 * just because another pier server cannot be contacted.
 * 
 * For performance, deadlock detection should be disabled and a small
 * lock-wait timeout should be set via server config. In innoDB, this can
 * done via the innodb_deadlock_detect and innodb_lock_wait_timeout settings.
 */
class DBFileLockManager extends FileLockManager {
	/** @var Array Map of bucket indexes to server names */
	protected $serverMap; // (index => (server1, server2, ...))
	protected $webTimeout; // integer number of seconds
	protected $cliTimeout; // integer number of seconds
	protected $resetDelay; // integer number of seconds
	/** @var Array Map of (locked key => lock type => 1) */
	protected $locksHeld = array();
	/** $var Array Map Lock-active database connections (server name => Database) */
	protected $activeConns = array();

	/**
	 * Construct a new instance from configuration.
	 * $config paramaters include:
	 *     'serverMap'  : Array of no more than 16 consecutive integer keys,
	 *                    starting from 0, with a list of servers as values.
	 *                    The first server in each list is the main server and
	 *                    the others are pier servers.
	 *     'webTimeout' : Connection timeout (seconds) for non-CLI scripts.
	 *                    This tells the DB server how long to wait before giving up
	 *                    and releasing all the locks made in a session transaction.
	 *     'cliTimeout' : Connection timeout (seconds) for CLI scripts.
	 *                    This tells the DB server how long to wait before giving up
	 *                    and releasing all the locks made in a session transaction.
	 *     'resetDelay' : How long (seconds) to avoid using a DB server after it restarted.
	 *                    This should reflect the highest max_execution_time that a PHP
	 *                    script might use on this wiki. Locks are lost on server restart.
	 *
	 * @param Array $config 
	 */
	function __construct( array $config ) {
		$this->serverMap = (array)$config['serverMap'];
		// Sanitize serverMap against bad config to prevent PHP errors
		for ( $i=0; $i < count( $this->serverMap ); $i++ ) {
			if (
				!isset( $this->serverMap[$i] ) || // not consecutive
				!is_array( $this->serverMap[$i] ) || // bad type
				!count( $this->serverMap[$i] ) // empty list
			) {
				$this->serverMap[$i] = null; // see getBucketFromKey()
				wfWarn( "No key for bucket $i in serverMap or server list is empty." );
			}
		}
		if ( !empty( $config['webTimeout'] ) ) { // disallow 0
			$this->webTimeout = $config['webTimeout'];
		} elseif ( ini_get( 'max_execution_time' ) > 0 ) {
			$this->webTimeout = ini_get( 'max_execution_time' );
		} else { // cli?
			$this->webTimeout = 60; // some sane number
		}
		$this->cliTimeout = !empty( $config['cliTimeout'] ) // disallow 0
			? $config['cliTimeout']
			: 60; // some sane number
		$this->resetDelay = isset( $config['resetDelay'] )
			? $config['resetDelay']
			: max( $this->cliTimeout, $this->webTimeout );
	}

	function doLock( array $keys, $type ) {
		$status = Status::newGood();

		$keysToLock = array();
		// Get locks that need to be acquired (buckets => locks)...
		foreach ( $keys as $key ) {
			if ( isset( $this->locksHeld[$key][$type] ) ) {
				$status->warning( 'lockmanager-alreadylocked', $key );
			} elseif ( isset( $this->locksHeld[$key][self::LOCK_EX] ) ) {
				$status->warning( 'lockmanager-alreadylocked', $key );
			} else {
				$bucket = $this->getBucketFromKey( $key );
				if ( $bucket === null ) { // config error?
					$status->fatal( 'lockmanager-fail-config', $key );
					return $status;
				}
				$keysToLock[$bucket][] = $key;
			}
		}

		$lockedKeys = array(); // files locked in this attempt
		// Attempt to acquire these locks...
		foreach ( $keysToLock as $bucket => $keys ) {
			// Acquire the locks for this server. Three main cases can happen:
			// (a) First server is up; common case
			// (b) First server is down but a pier is up
			// (c) First server is down and no pier are up (or none defined)
			$count = $this->doLockingSelectAll( $bucket, $keys, $type );
			if ( $count == -1 ) {
				// Resources already locked by another process.
				// Abort and unlock everything we just locked.
				$status->fatal( 'lockmanager-fail-acquirelocks' );
				$status->merge( $this->doUnlock( $lockedKeys, $type ) );
				return $status;
			} elseif ( $count <= 0 ) {
				// Couldn't contact any servers for this bucket.
				// Abort and unlock everything we just locked.
				$status->fatal( 'lockmanager-fail-db', $bucket );
				$status->merge( $this->doUnlock( $lockedKeys, $type ) );
				return $status; // error
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

	function doUnlock( array $keys, $type ) {
		$status = Status::newGood();

		foreach ( $keys as $key ) {
			if ( !isset( $this->locksHeld[$key] ) ) {
				$status->warning( 'lockmanager-notlocked', $key );
			} elseif ( $type && !isset( $this->locksHeld[$key][$type] ) ) {
				$status->warning( 'lockmanager-notlocked', $key );
			} else {
				foreach ( $this->locksHeld[$key] as $lockType => $x ) {
					if ( $type && $lockType != $type ) {
						continue; // only unlock locks of type $type
					}
					unset( $this->locksHeld[$key][$lockType] );
				}
				if ( !count( $this->locksHeld[$key] ) ) {
					unset( $this->locksHeld[$key] ); // no SH or EX locks left for key
				}
			}
		}

		// Reference count the locks held and COMMIT when zero
		if ( !count( $this->locksHeld ) ) {
			$this->commitLockTransactions();
		}

		return $status;
	}

	/**
	 * Get a DB connection to a lock server and acquire locks on $keys.
	 *
	 * @param $server string
	 * @param $keys Array
	 * @param $type integer FileLockManager::LOCK_EX or FileLockManager::LOCK_SH
	 * @return void
	 */
	protected function doLockingSelect( $server, array $keys, $type ) {
		if ( !isset( $this->activeConns[$server] ) ) {
			$this->activeConns[$server] = wfGetDB( DB_MASTER, array(), $server );
			$this->activeConns[$server]->begin(); // start transaction
			# If the connection drops, try to avoid letting the DB rollback
			# and release the locks before the file operations are finished.
			# This won't handle the case of server reboots however.
			$options = array();
			if ( php_sapi_name() == 'cli' ) { // maintenance scripts
				$options['connTimeout'] = $this->cliTimeout;
			} else { // web requests
				$options['connTimeout'] = $this->webTimeout;
			}
			$this->activeConns[$server]->setSessionOptions( $options );
		}
		$db = $this->activeConns[$server];
		# Try to get the locks...this should be the last query of this function
		if ( $type == self::LOCK_SH ) { // reader locks
			$db->select( 'file_locking', '1',
				array( 'fl_key' => $keys ),
				__METHOD__,
				array( 'LOCK IN SHARE MODE' ) // single-row gap locks
			);
		} else { // writer locks
			$data = array();
			foreach ( $keys as $key ) {
				$data[] = array( 'fl_key' => $key );
			}
			$db->insert( 'file_locking', $data, __METHOD__ ); // error on duplicate
		}
	}

	/**
	 * Attept to acquire a lock on the primary server as well
	 * as all pier servers for a bucket. Returns the number of
	 * servers with locks made or -1 if any of them claimed that
	 * any of the keys were already locked by another process.
	 * This should avoid throwing any exceptions.
	 *
	 * @param $bucket integer
	 * @param $keys Array
	 * @param $type integer FileLockManager::LOCK_EX or FileLockManager::LOCK_SH
	 * @return integer
	 */
	protected function doLockingSelectAll( $bucket, array $keys, $type ) {
		$locksMade = 0;
		for ( $i=0; $i < count( $this->serverMap[$bucket] ); $i++ ) {
			$server = $this->serverMap[$bucket][$i];
			try {
				$this->doLockingSelect( $server, $keys, $type );
				if ( $this->checkServerUptime( $server ) ) {
					++$locksMade; // success for this pier
				}
			} catch ( DBError $e ) {
				if ( $this->lastErrorIndicatesLocked( $server ) ) {
					return -1; // resource locked
				}
				// oh well; logged via wfLogDBError()
			}
		}
		return $locksMade;
	}

	/**
	 * Commit all changes to lock-active databases.
	 * This should avoid throwing any exceptions.
	 *
	 * @return void
	 */
	protected function commitLockTransactions() {
		foreach ( $this->activeConns as $server => $db ) {
			try {
				$db->rollback(); // finish transaction and kill any rows
			} catch ( DBError $e ) {
				// oh well; best effort
			}
		}
		$this->activeConns = array();
	}

	/**
	 * Check if the last DB error for $server indicates
	 * that a requested resource was locked by another process.
	 * This should avoid throwing any exceptions.
	 * 
	 * @param $server string
	 * @return bool
	 */
	protected function lastErrorIndicatesLocked( $server ) {
		if ( isset( $this->activeConns[$server] ) ) { // sanity
			$db = $this->activeConns[$server];
			return ( $db->wasDeadlock() || $db->wasLockTimeout() );
		}
		return false;
	}

	/**
	 * Check if the DB server has been up long enough to be safe
	 * to use. This is to get around the problems of locks falling
	 * off when servers restart.
	 * 
	 * @param $server string
	 * @return bool
	 */
	protected function checkServerUptime( $server ) {
		if ( isset( $this->activeConns[$server] ) ) { // sanity
			$db = $this->activeConns[$server];
			return ( $db->getServerUptime() >= $this->resetDelay );
		}
		return false;
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
		$bucket = intval( base_convert( $prefix, 16, 10 ) ) % count( $this->serverMap );
		// Sanity check that at least one server is handling this bucket
		if ( !isset( $this->serverMap[$bucket] ) ) {
			return null; // bad config?
		}
		return $bucket;
	}
}

/**
 * Simple version of FileLockManager that does nothing
 */
class NullFileLockManager extends FileLockManager {
	function __construct( array $config ) {}

	function doLock( array $keys, $type ) {
		return Status::newGood();
	}

	function doUnlock( array $keys, $type ) {
		return Status::newGood();
	}
}
