<?php
/**
 * Class for a "backend" consisting of a orioritized list of backend
 *
 * @file
 * @ingroup FileRepo
 */

/**
 * This class defines the methods as abstract that should be
 * implemented in child classes that represent a mutli-write backend.
 * 
 * The order that the backends are defined sets the priority of which
 * backend is read from or written to first. Functions like fileExists()
 * and getFileProps() will return information based on the first backend
 * that has the file (normally both should have it anyway).
 * 
 * All write operations are performed on all backends.
 * If an operation fails on one backend it will be rolled back from the others.
 * 
 * To avoid excess overhead, set the the highest priority backend to use
 * a generic FileLockManager and the others to use NullLockManager.
 */
class FileBackendMultiWrite implements IFileBackend {
	protected $name;
	/** @var Array Prioritized list of FileBackend classes */
	protected $fileBackends = array();

	public function __construct( array $config ) {
		$this->name = $config['name'];
	}

	public function getName() {
		return $this->name;
	}

	function hasNativeMove() {
		return true; // this is irrelevant
	}

	final public function doOperations( array $ops ) {
		$status = Status::newGood();
		// Build up a list of FileOps. The list will have all the ops
		// for one backend, then all the ops for the next, and so on.
		$performOps = array();
		foreach ( $this->fileBackends as $backend ) {
			$performOps = array_merge( $performOps, $backend->getOperations( $ops ) );
		}
		// Attempt each operation; abort on failure...
		foreach ( $performOps as $index => $transaction ) {
			$subStatus = $transaction->attempt();
			$status->merge( $subStatus );
			if ( !$subStatus->isOK() ) { // operation failed?
				// Revert everything done so far and abort.
				// Do this by reverting each previous operation in reverse order.
				$pos = $index - 1; // last one failed; no need to revert()
				while ( $pos >= 0 ) {
					$subStatus = $performOps[$pos]->revert();
					$status->merge( $subStatus );
					$pos--;
				}
				return $status;
			}
		}
		// Finish each operation...
		foreach ( $performOps as $index => $transaction ) {
			$subStatus = $transaction->finish();
			$status->merge( $subStatus );
		}
		// Make sure status is OK, despite any finish() fatals
		$status->setResult( true );
		return $status;
	}

	public function store( array $params ) {
		$op = array( 'operation' => 'store' ) + $params;
		return $this->doOperation( array( $op ) );
	}

	public function copy( array $params ) {
		$op = array( 'operation' => 'copy' ) + $params;
		return $this->doOperation( array( $op ) );
	}

	public function move( array $params ) {
		$op = array( 'operation' => 'move' ) + $params;
		return $this->doOperation( array( $op ) );
	}

	public function delete( array $params ){
		$op = array( 'operation' => 'delete' ) + $params;
		return $this->doOperation( array( $op ) );
	}

	public function concatenate( array $params ){
		$op = array( 'operation' => 'concatenate' ) + $params;
		return $this->doOperation( array( $op ) );
	}

	public function fileExists( array $params ) {
		foreach ( $this->backends as $backend ) {
			if ( $backend->fileExists( $params ) ) {
				return true;
			}
		}
		return false;
	}

	public function getLocalCopy( array $params ) {
		foreach ( $this->backends as $backend ) {
			$tmpPath = $backend->getLocalCopy( $params );
			if ( $tmpPath !== null ) {
				return $tmpPath;
			}
		}
		return null;
	}
 
	public function getFileProps( array $params ) {
		foreach ( $this->backends as $backend ) {
			$props = $backend->getFileProps( $params );
			if ( $props !== null ) {
				return $props;
			}
		}
		return null;
	}

	public function lockFiles( array $paths ) {
		$status = Status::newGood();
		foreach ( $this->backends as $index => $backend ) {
			$subStatus = $backend->lockFiles( $paths );
			$status->merge( $subStatus );
			if ( !$subStatus->isOk() ) {
				// Lock failed: release the locks done so far each backend
				for ( $i=0; $i < $index; $i++ ) { // don't do backend $index since it failed
					$subStatus = $backend->unlockFiles( $paths );
					$status->merge( $subStatus );
				}
				return $status;
			}
		}
		return $status;
	}

	public function unlockFiles( array $paths ) {
		$status = Status::newGood();
		foreach ( $this->backends as $backend ) {
			$subStatus = $backend->unlockFile( $paths );
			$status->merge( $subStatus );
		}
		return $status;
	}
}
