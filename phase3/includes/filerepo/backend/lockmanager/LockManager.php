<?php
/**
 * Class for handling resource locking.
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
 * LockManager helper class to handle scoped locks, which
 * release when an object is destroyed or goes out of scope.
 */
class ScopedLock {
	/** @var LockManager */
	protected $manager;
	/** @var Status */
	protected $status;
	/** @var Array List of resource paths*/
	protected $paths;

	protected $type; // integer lock type

	/**
	 * @param $manager LockManager
	 * @param $paths Array List of storage paths
	 * @param $type integer LockManager::LOCK_* constant
	 * @param $status Status
	 */
	protected function __construct(
		LockManager $manager, array $paths, $type, Status $status
	) {
	   $this->manager = $manager;
	   $this->paths = $paths;
	   $this->status = $status;
	   $this->type = $type;
	}

	protected function __clone() {}

	/**
	 * Get a ScopedLock object representing a lock on resource paths.
	 * Any locks are released once this object goes out of scope.
	 * The status object is updated with any errors or warnings.
	 * 
	 * @param $manager LockManager
	 * @param $paths Array List of storage paths
	 * @param $type integer LockManager::LOCK_* constant
	 * @param $status Status
	 * @return ScopedLock|null Returns null on failure
	 */
	public static function factory(
		LockManager $manager, array $paths, $type, Status $status
	) {
		$lockStatus = $manager->lock( $paths, $type );
		$status->merge( $lockStatus );
		if ( $lockStatus->isOK() ) {
			return new self( $manager, $paths, $type, $status );
		}
		return null;
	}

	function __destruct() {
		$wasOk = $this->status->isOK();
		$this->status->merge( $this->manager->unlock( $this->paths, $this->type ) );
		if ( $wasOk ) {
			// Make sure status is OK, despite any unlockFiles() fatals
			$this->status->setResult( true, $this->status->value );
		}
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
