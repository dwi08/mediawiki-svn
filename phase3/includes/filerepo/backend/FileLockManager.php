<?php
/**
 * FileBackend helper class for handling file locking.
 * Implemenations can rack of what locked in the process cache.
 * This can reduce hits to external resources for lock()/unlock() calls.
 */
interface IFileLockManager {
	/**
	 * Lock the file at a storage path for a backend
	 * 
	 * @param $backendKey string
	 * @param $name string
	 * @return Status 
	 */
	public function lock( $backendKey, $name );

	/**
	 * Unlock the file at a storage path for a backend
	 * 
	 * @param $backendKey string
	 * @param $name string
	 * @return Status 
	 */
	public function unlock( $backendKey, $name );
}

class NullFileLockManager implements IFileLockManager {
	public function lock( $backendKey, $name ) {
		return Status::newGood();
	}

	public function unlock( $backendKey, $name ) {
		return Status::newGood();
	}
}
