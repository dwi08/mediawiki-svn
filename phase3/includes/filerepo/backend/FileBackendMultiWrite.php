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
 * backend is read from or written to first.
 * 
 * All write operations are performed on all backends.
 * If an operation fails on one backend it will be rolled back from the others.
 * 
 * Functions like fileExists() and getFileProps() will return information
 * based on the first backend that has the file (normally both should have it anyway).
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
			$tStatus = $transaction->attempt();
			if ( !$tStatus->isOk() ) {
				// merge $tStatus with $status
				// Revert everything done so far and error out
				$tStatus = $this->revertOperations( $performOps, $index );
				// merge $tStatus with $status
				return $status;
			}
		}
		// Finish each operation...
		foreach ( $performOps as $index => $transaction ) {
			$tStatus = $transaction->finish();
			// merge $tStatus with $status
		}
		return $status;
	}

	/**
	 * Revert a series of operations in reverse order.
	 * If $index is passed, then we revert all items in
	 * $ops from 0 to $index (inclusive).
	 * 
	 * @param $ops Array List of FileOp objects
	 * @param $index integer
	 * @return Status
	 */
	private function revertOperations( array $ops, $index = false ) {
		$status = Status::newGood();
		$pos = ( $index !== false )
			? $index // use provided index
			: $pos = count( $ops ) - 1; // last element (or -1)
		while ( $pos >= 0 ) {
			$tStatus = $ops[$pos]->revert();
			// merge $tStatus with $status
			$pos--;
		}
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

	public function lockFile( array $path ) {
		$status = Status::newGood();
		foreach ( $this->backends as $index => $backend ) {
			$lockStatus = $backend->lockFile( $path );
			// merge $lockStatus with $status
			if ( !$lockStatus->isOk() ) {
				for ( $i=0; $i < $index; $i++ ) {
					$lockStatus = $this->unlockFile( $path );
					// merge $lockStatus with $status
				}
			}
		}
		return $status;
	}

	public function unlockFile( array $path ) {
		$status = Status::newGood();
		foreach ( $this->backends as $backend ) {
			$lockStatus = $backend->unlockFile( $path );
			// merge $lockStatus with $status
		}
		return $status;
	}
}
