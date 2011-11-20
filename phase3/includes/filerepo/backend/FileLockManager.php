<?php
/**
 * FileBackend helper class for handling file locking.
 * Implemenations can rack of what locked in the process cache.
 * This can reduce hits to external resources for lock()/unlock() calls.
 * 
 * Subclasses should avoid throwing exceptions at all costs.
 */
abstract class FileLockManager {
	/**
	 * Construct a new instance from configuration
	 * @param $config Array
	 */
	abstract public function __construct( array $config );

	/**
	 * Lock the files at a storage paths for a backend
	 * 
	 * @param $backendKey string
	 * @param $paths Array List of storage paths
	 * @return Status 
	 */
	final public function lock( $backendKey, array $paths ) {
		$keys = array();
		foreach ( $paths as $path ) {
			$keys[] = $this->getLockName( $backendKey, $path );
		}
		return $this->doLock( $keys );
	}

	/**
	 * Unlock the files at a storage paths for a backend
	 * 
	 * @param $backendKey string
	 * @param $paths Array List of storage paths
	 * @return Status 
	 */
	final public function unlock( $backendKey, array $paths ) {
		$keys = array();
		foreach ( $paths as $path ) {
			$keys[] = $this->getLockName( $backendKey, $path );
		}
		return $this->doUnlock( $keys );
	}

	/**
	 * Get the lock name given backend key (type/name) and a storage path
	 * 
	 * @param $backendKey string
	 * @param $name string
	 * @return string
	 */
	private function getLockName( $backendKey, $name ) {
		return urlencode( $backendKey ) . ':' . md5( $name );
	}

	/**
	 * Lock a resource with the given key
	 * 
	 * @param $key Array List of keys
	 * @return string
	 */
	abstract protected function doLock( array $keys );

	/**
	 * Unlock a resource with the given key
	 * 
	 * @param $key Array List of keys
	 * @return string
	 */
	abstract protected function doUnlock( array $keys );
}

/**
 * Simple version of FileLockManager based on using FS lock files
 *
 * This should work fine for small sites running off one server.
 * Do not use this with 'lockDir' set to an NFS mount.
 */
class FSFileLockManager extends FileLockManager {
	protected $lockDir; // global dir for all servers
	/** @var Array Map of lock key names to lock file handlers */
	protected $handles = array();

	function __construct( array $config ) {
		$this->lockDir = $config['lockDir'];
	}

	function doLock( array $keys ) {
		$status = Status::newGood();

		$lockedKeys = array(); // files locked in this attempt
		foreach ( $keys as $key ) {
			$subStatus = $this->doSingleLock( $key );
			$status->merge( $subStatus );
			if ( $subStatus->isOk() ) {
				$lockedKeys[] = $key;
			} else {
				// Abort and unlock everything
				$status->merge( $this->doUnlock( $lockedKeys ) );
				return $status;
			}
		}

		return $status;
	}

	function doUnlock( array $keys ) {
		$status = Status::newGood();

		foreach ( $keys as $key ) {
			$status->merge( $this->doSingleUnlock( $key ) );
		}

		return $status;
	}

	protected function doSingleLock( $key ) {
		$status = Status::newGood();

		if ( isset( $this->handles[$key] ) ) {
			$status->warning( 'File already locked.' );
		} else {
			wfSuppressWarnings();
			$handle = fopen( "{$this->lockDir}/{$key}", 'w' );
			if ( $handle ) {
				if ( flock( $handle, LOCK_SH ) ) {
					$this->handles[$key] = $handle;
				} else {
					fclose( $handle );
					$status->fatal( "Could not file acquire lock." );
				}
			} else {
				$status->fatal( "Could not open lock file." );
			}
			wfRestoreWarnings();
		}

		return $status;
	}

	protected function doSingleUnlock( $key ) {
		$status = Status::newGood();

		if ( isset( $this->handles[$key] ) ) {
			wfSuppressWarnings();
			if ( !flock( $this->handles[$key], LOCK_UN ) ) {
				$status->fatal( "Could not unlock file." );
			}
			if ( !fclose( $this->handles[$key] ) ) {
				$status->warning( "Could not close lock file." );
			}
			if ( !unlink( "{$this->lockDir}/{$key}" ) ) {
				$status->warning( "Could not delete lock file." );
			}
			wfRestoreWarnings();
			unset( $this->handles[$key] );
		} else {
			$status->warning( "There is no file lock to unlock." );
		}

		return $status;
	}

	protected function __destruct() {
		// Make sure remaining files get cleared for sanity
		foreach ( $this->handles as $key => $handler ) {
			flock( $handler, LOCK_UN ); // PHP 5.3 will not do this automatically
			fclose( $handler );
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
	/** @var Array List of active lock key names */
	protected $locksHeld = array(); // (key => 1)
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

	function doLock( array $keys ) {
		$status = Status::newGood();

		$keysToLock = array();
		// Get locks that need to be acquired (buckets => locks)...
		foreach ( $keys as $key ) {
			if ( isset( $this->locksHeld[$key] ) ) {
				$status->warning( 'File already locked.' );
			} else {
				$bucket = $this->getBucketFromKey( $key );
				if ( $bucket === null ) { // config error?
					$status->fatal( "Lock servers for key $key is not set." );
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
				$this->lockingSelect( $server, $keys ); // SELECT FOR UPDATE
			} catch ( DBError $e ) {
				// Can we manage to lock on any of the fallback servers?
				if ( !$this->lockingSelectFallbacks( $bucket, $keys ) ) {
					// Abort and unlock everything we just locked
					$status->fatal( "Could not contact the lock server." );
					$status->merge( $this->doUnlock( $lockedKeys ) );
					return $status;
				} else { // recovered using fallbacks
					$propagateToFallbacks = false; // done already
				}
			}
			// Propagate any locks to the fallback servers (best effort)
			if ( $propagateToFallbacks ) {
				$this->lockingSelectFallbacks( $bucket, $keys );
			}
			// Record locks as active
			foreach ( $keys as $key ) {
				$this->locksHeld[$key] = 1; // locked
			}
			// Keep track of what locks were made in this attempt
			$lockedKeys = array_merge( $lockedKeys, $keys );
		}

		return $status;
	}

	function doUnlock( array $keys ) {
		$status = Status::newGood();

		foreach ( $keys as $key ) {
			if ( $this->locksHeld[$key] ) {
				unset( $this->locksHeld[$key] );
				// Reference count the locks held and COMMIT when zero
				if ( !count( $this->locksHeld ) ) {
					$this->commitLockTransactions();
				}
			} else {
				$status->warning( "There is no file lock to unlock." );
			}
		}

		return $status;
	}

	/**
	 * Get a DB connection to a lock server and acquire locks on $keys.
	 *
	 * @param $server string
	 * @param $keys Array
	 * @return void
	 */
	protected function lockingSelect( $server, array $keys ) {
		if ( !isset( $this->activeConns[$server] ) ) {
			$this->activeConns[$server] = wfGetDB( DB_MASTER, array(), $server );
			$this->activeConns[$server]->begin(); // start transaction
		}
		$this->activeConns[$server]->select(
			'file_locking',
			'1',
			array( 'fl_key' => $keys ),
			__METHOD__,
			array( 'FOR UPDATE' )
		);
	}

	/**
	 * Propagate any locks to the fallback servers for a bucket.
	 * This should avoid throwing any exceptions.
	 *
	 * @param $bucket integer
	 * @param $keys Array
	 * @return bool Locks made on at least one fallback server
	 */
	protected function lockingSelectFallbacks( $bucket, array $keys ) {
		$locksMade = false;
		$count = count( $this->serverMap[$bucket] );
		for ( $i=1; $i < $count; $i++ ) { // start at 1 to only include fallbacks
			$server = $this->serverMap[$bucket][$i];
			try {
				$this->doLockingSelect( $server, $keys ); // SELECT FOR UPDATE
				$locksMade = true; // success for this fallback
			} catch ( DBError $e ) {
				// oh well; best effort
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

	function doLock( array $keys ) {
		return Status::newGood();
	}

	function doUnlock( array $keys ) {
		return Status::newGood();
	}
}
