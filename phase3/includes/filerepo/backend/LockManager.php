<?php
/**
 * FileBackend helper class for handling file locking.
 * Locks on resource keys can either be shared or exclusive.
 * 
 * Implemenations can keep track of what is locked in the process cache.
 * This can reduce hits to external resources for lock()/unlock() calls.
 * 
 * Subclasses should avoid throwing exceptions at all costs.
 * 
 * @ingroup FileBackend
 */
abstract class LockManager {
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
	 * @param $type integer LockManager::LOCK_EX, LockManager::LOCK_SH
	 * @return Status 
	 */
	final public function lock( array $paths, $type = self::LOCK_EX ) {
		$keys = array_unique( array_map( 'sha1', $paths ) );
		return $this->doLock( $keys, $type );
	}

	/**
	 * Unlock the resources at the given abstract paths
	 * 
	 * @param $paths Array List of storage paths
	 * @return Status 
	 */
	final public function unlock( array $paths ) {
		$keys = array_unique( array_map( 'sha1', $paths ) );
		return $this->doUnlock( $keys, 0 );
	}

	/**
	 * Lock a resource with the given key
	 * 
	 * @param $key Array List of keys to lock (40 char hex hashes)
	 * @param $type integer LockManager::LOCK_EX, LockManager::LOCK_SH
	 * @return string
	 */
	abstract protected function doLock( array $keys, $type );

	/**
	 * Unlock a resource with the given key.
	 * If $type is given, then only locks of that type should be cleared.
	 * 
	 * @param $key Array List of keys to unlock (40 char hex hashes)
	 * @param $type integer LockManager::LOCK_EX, LockManager::LOCK_SH, or 0
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
		} elseif ( $type && !isset( $this->locksHeld[$key][$type] ) ) {
			$status->warning( 'lockmanager-notlocked', $key );
		} else {
			$handlesToClose = array();
			foreach ( $this->locksHeld[$key] as $lockType => $count ) {
				if ( $type && $lockType != $type ) {
					continue; // only unlock locks of type $type
				}
				--$this->locksHeld[$key][$lockType];
				if ( $this->locksHeld[$key][$lockType] <= 0 ) {
					unset( $this->locksHeld[$key][$lockType] );
					// If a LOCK_SH comes in while we have a LOCK_EX, we don't
					// actually add a handler, so check for handler existence.
					if ( isset( $this->handles[$key][$lockType] ) ) {
						// Mark this handle to be unlocked and closed
						$handlesToClose[] = $this->handles[$key][$lockType];
						unset( $this->handles[$key][$lockType] );
					}
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
 * Version of LockManager based on using DB table locks.
 * This is meant for multi-wiki systems that may share share files.
 *
 * All lock requests for a resource, identified by a hash string, will
 * map to one bucket. Each bucket maps to one or several peer DB servers,
 * each having a `file_locks` table with row-level locking.
 *
 * A majority of peer servers must agree for a lock to be acquired.
 * As long as one peer server is up, lock requests will not be blocked
 * just because another peer server cannot be contacted. A global status
 * cache can be setup to track servers that recently missed queries; such
 * servers will not be trusted for obtaining locks.
 * 
 * For performance, deadlock detection should be disabled and a small
 * lock-wait timeout should be set via server config. In innoDB, this can
 * done via the innodb_deadlock_detect and innodb_lock_wait_timeout settings.
 */
class DBLockManager extends LockManager {
	/** @var Array Map of bucket indexes to peer sets */
	protected $dbsByBucket; // (bucket index => (ldb1, ldb2, ...))
	/** @var BagOStuff */
	protected $statusCache;

	protected $trustCache; // boolean
	protected $webTimeout; // integer number of seconds
	protected $cliTimeout; // integer number of seconds
	protected $safeDelay; // integer number of seconds

	/** @var Array Map of (locked key => lock type => count) */
	protected $locksHeld = array();
	/** $var Array Map Lock-active database connections (server name => Database) */
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
	 *                     scripts might use on a wiki. Locks are lost on server restart.
	 *     'cache'       : $wgMemc (if set to a global memcached instance). [optional]
	 *                     This tracks peer servers that couldn't be queried recently.
	 *     'trustCache'  : Assume cache knows all servers missing queries recently. [optional]
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

		if ( isset( $config['cache'] ) && $config['cache'] instanceof BagOStuff ) {
			$this->statusCache = $config['cache'];
			$this->trustCache = ( !empty( $config['trustCache'] ) && $this->safeDelay > 0 );
		} else {
			$this->statusCache = null;
			$this->trustCache = false;
		}
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
				// Couldn't contact any servers for this bucket.
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
			} elseif ( $type && !isset( $this->locksHeld[$key][$type] ) ) {
				$status->warning( 'lockmanager-notlocked', $key );
			} else {
				foreach ( $this->locksHeld[$key] as $lockType => $count ) {
					if ( $type && $lockType != $type ) {
						continue; // only unlock locks of type $type
					}
					--$this->locksHeld[$key][$lockType];
					if ( $this->locksHeld[$key][$lockType] <= 0 ) {
						unset( $this->locksHeld[$key][$lockType] );
					}
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
	 * Get a DB connection to a lock server and acquire locks on $keys.
	 *
	 * @param $server string
	 * @param $keys Array
	 * @param $type integer LockManager::LOCK_EX or LockManager::LOCK_SH
	 * @return void
	 */
	protected function doLockingQuery( $server, array $keys, $type ) {
		if ( !isset( $this->activeConns[$server] ) ) {
			$this->activeConns[$server] = wfGetDB( DB_MASTER, array(), $server );
			$this->activeConns[$server]->begin(); // start transaction
			# If the connection drops, try to avoid letting the DB rollback
			# and release the locks before the file operations are finished.
			# This won't handle the case of server reboots however.
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
			$this->activeConns[$server]->setSessionOptions( $options );
		}
		$db = $this->activeConns[$server];
		# Try to get the locks...this should be the last query of this function
		if ( $type == self::LOCK_SH ) { // reader locks
			$db->select( 'file_locks', '1',
				array( 'fl_key' => $keys ),
				__METHOD__,
				array( 'LOCK IN SHARE MODE' ) // single-row gap locks
			);
		} else { // writer locks
			$data = array();
			foreach ( $keys as $key ) {
				$data[] = array( 'fl_key' => $key );
			}
			$db->insert( 'file_locks', $data, __METHOD__ );
		}
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
		$yesVotes = 0; // locks made on trustable servers
		$votesLeft = count( $this->dbsByBucket[$bucket] ); // remaining servers
		$quorum = floor( $votesLeft/2 + 1 ); // simple majority
		// Get votes for each server, in order, until we have enough...
		foreach ( $this->dbsByBucket[$bucket] as $index => $server ) {
			if ( $this->trustCache && $votesLeft < $quorum ) {
				// We are now hitting servers that are not normally
				// hit, meaning that some of the first ones are down.
				// Delay using these later servers until it's safe.
				if ( !$this->cacheCheckSkipped( $bucket, $index ) ) {
					return 'dberrors';
				}
			}
			// Check that server is not *known* to be down
			if ( $this->cacheCheckFailures( $server ) ) {
				try {
					// Attempt to acquire the lock on this server
					$this->doLockingQuery( $server, $keys, $type );
					// Check that server has no signs of lock loss
					if ( $this->checkUptime( $server ) ) {
						++$yesVotes; // success for this peer
						if ( $yesVotes >= $quorum ) {
							if ( $this->trustCache ) {
								// We didn't bother with the servers after this one
								$this->cacheRecordSkipped( $bucket, $index + 1 );
							}
							return true; // lock obtained
						}
					}
				} catch ( DBError $e ) {
					if ( $this->lastErrorIndicatesLocked( $server ) ) {
						return 'cantacquire'; // vetoed; resource locked
					} else { // can't connect?
						$this->cacheRecordFailure( $server );
					}
				}
			}
			$votesLeft--;
			$votesNeeded = $quorum - $yesVotes;
			if ( $votesNeeded > $votesLeft && !$this->trustCache ) {
				// In "trust cache" mode we don't have to meet the quorum
				break; // short-circuit
			}
		}
		// At this point, we must not have meet the quorum
		if ( $yesVotes > 0 && $this->trustCache ) {
			return true; // we are trusting the cache; may comprimise correctness
		}
		return 'dberrors'; // not enough votes to ensure correctness
	}

	/**
	 * Commit all changes to lock-active databases.
	 * This should avoid throwing any exceptions.
	 *
	 * @return Status
	 */
	protected function finishLockTransactions() {
		$status = Status::newGood();
		foreach ( $this->activeConns as $server => $db ) {
			try {
				$db->rollback(); // finish transaction and kill any rows
			} catch ( DBError $e ) {
				$status->fatal( 'lockmanager-fail-db-release', $server );
				// oh well; best effort
			}
		}
		$this->activeConns = array();
		return $status;
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
	 * Checks if the DB server did not recently restart.
	 * This curtails the problem of locks falling off when servers restart.
	 * 
	 * @param $server string
	 * @return bool
	 */
	protected function checkUptime( $server ) {
		if ( isset( $this->activeConns[$server] ) ) { // sanity
			if ( $this->safeDelay > 0 ) {
				$db = $this->activeConns[$server];
				if ( $db->getServerUptime() < $this->safeDelay ) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Check that DB servers starting at $index for a
	 * bucket were not recently skipped in obtaining a lock.
	 *
	 * @param $bucket
	 * @param $index
	 * @return bool
	 */
	protected function cacheCheckSkipped( $bucket, $index ) {
		if ( $this->statusCache && $this->safeDelay > 0 ) {
			$key = $this->getSkipsKey( $bucket, $index );
			$skips = $this->statusCache->get( $key );
			return !$skips;
		}
		return true;
	}

	/**
	 * Record that DB servers starting at $index for a
	 * bucket were just skipped in obtaining a lock (quorum met).
	 *
	 * @param $bucket
	 * @param $index
	 * @return bool Success
	 */
	protected function cacheRecordSkipped( $bucket, $index ) {
		if ( $this->statusCache && $this->safeDelay > 0 ) {
			$key = $this->getSkipsKey( $bucket, $index );
			$skips = $this->statusCache->get( $key );
			if ( $skips ) {
				return $this->statusCache->incr( $key );
			} else {
				return $this->statusCache->add( $key, 1, $this->safeDelay );
			}
		}
		return true;
	}

	/**
	 * Get a cache key for recent query skips for a bucket
	 *
	 * @param $bucket
	 * @param $index
	 * @return string
	 */
	protected function getSkipsKey( $bucket, $index ) {
		return "lockmanager:queryskips:$bucket:$index";
	}

	/**
	 * Checks if the DB server has not recently had connection/query errors.
	 * When in "trust cache" mode, this curtails the problem of peers occasionally
	 * missing locks. Otherwise, it just avoids wasting time on connection attempts.
	 * 
	 * @param $server string
	 * @return bool
	 */
	protected function cacheCheckFailures( $server ) {
		if ( $this->statusCache && $this->safeDelay > 0 ) {
			$key = $this->getMissKey( $server );
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
	 * Clients trying to get locks need to know if a server is down.
	 *
	 * @param $server string
	 * @return bool Success
	 */
	protected function cacheRecordFailure( $server ) {
		if ( $this->statusCache && $this->safeDelay > 0 ) {
			$key = $this->getMissKey( $server );
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
	 * Get a cache key for recent query misses for a DB server
	 *
	 * @param $server string
	 * @return string
	 */
	protected function getMissKey( $server ) {
		return "lockmanager:querymisses:$server";
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
