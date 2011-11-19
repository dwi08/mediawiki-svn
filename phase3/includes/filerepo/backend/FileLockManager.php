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
			$lockStatus = $this->doSingleLock( $key );
			if ( $lockStatus->isOk() ) {
				$lockedKeys[] = $key;
			} else {
				// Abort and unlock everything
				$this->doUnlock( $lockedKeys );
				return $lockStatus;
			}
		}

		return $status;
	}

	function doUnlock( array $keys ) {
		$status = Status::newGood();

		foreach ( $keys as $key ) {
			$lockStatus = $this->doSingleUnlock( $key );
			if ( !$lockStatus->isOk() ) {
				// append $lockStatus to $status
			}
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

	function __destruct() {
		// Make sure remaining files get cleared for sanity
		foreach ( $this->handles as $key => $handler ) {
			flock( $handler, LOCK_UN ); // PHP 5.3 will not do this automatically
			fclose( $handler );
			unlink( "{$this->lockDir}/{$key}" );
		}
	}
}

/**
 * Version of FileLockManager based on using per-row DB locks
 */
class DBFileLockManager extends FileLockManager {
	/** @var Array Map of bucket indexes to server names */
	protected $serverMap = array(); // (index => server name)
	protected $shards; // number of severs to shard to

	/** @var Array List of active lock key names */
	protected $locksHeld = array(); // (key => 1)
	/** $var Array Map active database connections (name => Database) */
	protected $activeConns = array();

	/**
	 * Construct a new instance from configuration.
	 * The 'serverMap' param of $config has is an array of consecutive
	 * integer keys, starting from 0, with server name strings as values.
	 * It should have no more than 16 items in the array.
	 * 
	 * The `file_locking` table could be a MEMORY or innoDB table.
	 * 
	 * @param array $config 
	 */
	function __construct( array $config ) {
		$this->serverMap = $config['serverMap'];
		$this->shards = count( $this->serverMap );
	}

	function doLock( array $keys ) {
		$status = Status::newGood();

		$keysToLock = array();
		// Get locks that need to be acquired...
		foreach ( $keys as $key ) {
			if ( isset( $this->locksHeld[$key] ) ) {
				$status->warning( 'File already locked.' );
			} else {
				$server = $this->getDBServerFromKey( $key );
				if ( $server === null ) {
					$status->fatal( "Lock server for $key is not set." );
				}
				$keysToLock[$server][] = $key;
			}
		}

		$lockedKeys = array(); // files locked in this attempt
		// Attempt to acquire these locks...
		try {
			foreach ( $keysToLock as $server => $keys ) {
				$db = $this->getDB( $server );
				$db->select( 'file_locking',
					'1',
					array( 'fl_key' => $keys ),
					__METHOD__,
					array( 'FOR UPDATE' )
				);
				// Record locks as active
				foreach ( $keys as $key ) {
					$this->locksHeld[$key] = 1; // locked
				}
				// Keep track of what locks where made in this attempt
				$lockedKeys = array_merge( $lockedKeys, $keys );
			}
		} catch ( DBConnectionError $e ) {
			// Abort and unlock everything
			$status->fatal( "Could not contact the lock database." );
			$this->doUnlock( $lockedKeys );
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
					$this->commitOpenTransactions();
				}
			} else {
				// append warning to $status
			}
		}

		return $status;
	}

	/**
	 * Get a database connection for $server
	 * @param $server string
	 * @return Database
	 */
	protected function getDB( $server ) {
		if ( !isset( $this->activeConns[$server] ) ) {
			$this->activeConns[$server] = wfGetDB( DB_MASTER, array(), $server );
			$this->activeConns[$server]->begin(); // start transaction
		}
		return $this->activeConns[$server];
	}

	/**
	 * Commit all changes to active databases
	 * @return void
	 */
	protected function commitOpenTransactions() {
		try {
			foreach ( $this->activeConns as $server => $db ) {
				$db->commit(); // finish transaction
				unset( $this->activeConns[$server] );
			}
		} catch ( DBConnectionError $e ) {
			// oh well
		}
	}

	/**
	 * Get the server name for lock key
	 * @param $key string
	 * @return string|null
	 */
	protected function getDBServerFromKey( $key ) {
		$hash = str_pad( md5( $key ), 32, '0', STR_PAD_LEFT ); // 32 char hash
		$prefix = substr( $hash, 0, 2 ); // first 2 hex chars (8 bits)
		$bucket = intval( base_convert( $prefix, 16, 10 ) ) % $this->shards;

		if ( isset( $this->serverMap[$bucket] ) ) {
			return $this->serverMap[$bucket];
		} else {
			wfWarn( "No key for bucket $bucket in serverMap." );
			return null; // disabled? bad config?
		}
	}
}

/**
 * Simple version of FileLockManager that does nothing
 */
class NullFileLockManager extends FileLockManager {
	public function doLock( array $keys ) {
		return Status::newGood();
	}

	public function doUnlock( array $keys ) {
		return Status::newGood();
	}
}
