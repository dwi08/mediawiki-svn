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
	 * @param $key Array List of keys to lock
	 * @param $type integer FileLockManager::LOCK_EX, FileLockManager::LOCK_SH
	 * @return string
	 */
	abstract protected function doLock( array $keys, $type );

	/**
	 * Unlock a resource with the given key.
	 * If $type is given, then only locks of that type should be cleared.
	 * 
	 * @param $key Array List of keys to unlock
	 * @param $type integer FileLockManager::LOCK_EX, FileLockManager::LOCK_SH, or 0
	 * @return string
	 */
	abstract protected function doUnlock( array $keys, $type );
}

/**
 * Simple version of FileLockManager based on using FS lock files
 *
 * This should work fine for small sites running off one server.
 * Do not use this with 'lockDir' set to an NFS mount.
 */
class FSFileLockManager extends FileLockManager {
	protected $lockDir; // global dir for all servers
	/** @var Array Map of (locked key => lock type => lock file handle) */
	protected $handles = array();

	function __construct( array $config ) {
		$this->lockDir = $config['lockDir'];
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
			$handle = fopen( "{$this->lockDir}/{$key}", 'c' );
			if ( $handle ) {
				// Either a shared or exclusive lock
				$lock = ( $type == self::LOCK_SH ) ? LOCK_SH : LOCK_EX;
				if ( flock( $handle, $lock ) ) {
					$this->handles[$key][$type] = $handle;
				} else {
					fclose( $handle );
					$status->fatal( 'lockmanager-fail-acquirelock', $key );
				}
			} else {
				$status->fatal( 'lockmanager-fail-openlock', $key );
			}
			wfRestoreWarnings();
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
				if ( !unlink( "{$this->lockDir}/{$key}" ) ) {
					$status->warning( 'lockmanager-fail-deletelock', $key );
				}
				wfRestoreWarnings();
			}
		}

		return $status;
	}

	protected function __destruct() {
		// Make sure remaining files get cleared for sanity
		foreach ( $this->handles as $key => $locks ) {
			foreach ( $locks as $type => $handle ) {
				flock( $handle, LOCK_UN ); // PHP 5.3 will not do this automatically
				fclose( $handle );
			}
			unlink( "{$this->lockDir}/{$key}" );
		}
	}
}

/**
 * Version of FileLockManager based on using DB table locks.
 *
 * This is meant for multi-wiki systems that may share share some files.
 * One or several lock database servers are set up having a `file_locking`
 * table with one field, fl_key, the PRIMARY KEY. The table engine should
 * have row-level locking. For performance, deadlock detection should be
 * disabled and a low lock-wait timeout should be set via server config.
 *
 * All lock requests for an item (identified by an abstract key string) will
 * map to one bucket. Each bucket maps to a single server, though each server
 * can have several fallback servers.
 *
 * Fallback servers recieve the same lock statements as the servers they standby for.
 * This propagation is only best-effort; lock requests will not be blocked just
 * because a fallback server cannot recieve a copy of the lock request.
 */
class DBFileLockManager extends FileLockManager {
	/** @var Array Map of bucket indexes to server names */
	protected $serverMap = array(); // (index => (server1,server2,...))
	/** @var Array Map of (locked key => lock type => 1) */
	protected $locksHeld = array();
	/** $var Array Map Lock-active database connections (name => Database) */
	protected $activeConns = array();

	/**
	 * Construct a new instance from configuration.
	 * $config paramaters include:
	 * 'serverMap' : Array of no more than 16 consecutive integer keys,
	 *               starting from 0, with a list of servers as values.
	 *               The first server in each list is the main server and
	 *               the others are fallback servers.
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
			$server = $this->serverMap[$bucket][0]; // primary lock server
			$propagateToFallbacks = true; // give lock statements to fallback servers
			// Acquire the locks for this server. Three main cases can happen:
			// (a) Server is up; common case
			// (b) Server is down but a fallback is up
			// (c) Server is down and no fallbacks are up (or none defined)
			try {
				$this->lockingSelect( $server, $keys, $type );
			} catch ( DBError $e ) {
				// Can we manage to lock on any of the fallback servers?
				if ( $this->lockingSelectFallbacks( $bucket, $keys, $type ) ) {
					// Recovered; a fallback server is up
					$propagateToFallbacks = false; // done already
				} else {
					// Abort and unlock everything we just locked
					$status->fatal( 'lockmanager-fail-db', $bucket );
					$status->merge( $this->doUnlock( $lockedKeys, $type ) );
					return $status;
				}
			}
			// Propagate any locks to the fallback servers (best effort)
			if ( $propagateToFallbacks ) {
				$this->lockingSelectFallbacks( $bucket, $keys, $type );
			}
			// Record locks as active
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
	protected function lockingSelect( $server, array $keys, $type ) {
		if ( !isset( $this->activeConns[$server] ) ) {
			$this->activeConns[$server] = wfGetDB( DB_MASTER, array(), $server );
			$this->activeConns[$server]->begin(); // start transaction
		}
		$lockingClause = ( $type == self::LOCK_SH )
			? 'LOCK IN SHARE MODE' // reader lock
			: 'FOR UPDATE'; // writer lock
		$this->activeConns[$server]->select(
			'file_locking',
			'1',
			array( 'fl_key' => $keys ),
			__METHOD__,
			array( $lockingClause )
		);
	}

	/**
	 * Propagate any locks to the fallback servers for a bucket.
	 * This should avoid throwing any exceptions.
	 *
	 * @param $bucket integer
	 * @param $keys Array
	 * @param $type integer FileLockManager::LOCK_EX or FileLockManager::LOCK_SH
	 * @return bool Locks made on at least one fallback server
	 */
	protected function lockingSelectFallbacks( $bucket, array $keys, $type ) {
		$locksMade = false;
		// Start at $i=1 to only include fallback servers
		for ( $i=1; $i < count( $this->serverMap[$bucket] ); $i++ ) {
			$server = $this->serverMap[$bucket][$i];
			try {
				$this->doLockingSelect( $server, $keys, $type );
				$locksMade = true; // success for this fallback
			} catch ( DBError $e ) {
				// oh well; best effort (@TODO: logging?)
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
				$db->commit(); // finish transaction
			} catch ( DBError $e ) {
				// oh well
			}
		}
		$this->activeConns = array();
	}

	/**
	 * Get the bucket for lock key.
	 * This should avoid throwing any exceptions.
	 *
	 * @param $key string
	 * @return integer
	 */
	protected function getBucketFromKey( $key ) {
		$hash = str_pad( md5( $key ), 32, '0', STR_PAD_LEFT ); // 32 char hash
		$prefix = substr( $hash, 0, 2 ); // first 2 hex chars (8 bits)
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
