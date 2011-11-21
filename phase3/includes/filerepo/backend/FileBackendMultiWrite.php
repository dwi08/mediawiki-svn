<?php
/**
 * @file
 * @ingroup FileRepo
 */

/**
 * This class defines a multi-write backend. Multiple backends can
 * be registered to this proxy backend it will act as a single backend.
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
 * a generic FileLockManager and the others to use NullLockManager. This can
 * only be done if all access to the backends is through an instance of this class.
 */
class FileBackendMultiWrite extends FileBackendBase {
	/** @var Array Prioritized list of FileBackend classes */
	protected $fileBackends = array();

	public function __construct( array $config ) {
		$this->name = $config['name'];
		$this->fileBackends = $config['backends'];
	}

	function hasNativeMove() {
		return true; // this is irrelevant
	}

	final public function doOperations( array $ops ) {
		$status = Status::newGood();

		// Build up a list of FileOps. The list will have all the ops
		// for one backend, then all the ops for the next, and so on.
		// Also build up a list of files to lock...
		$performOps = array();
		$filesToLock = array();
		foreach ( $this->fileBackends as $index => $backend ) {
			$performOps = array_merge( $performOps, $backend->getOperations( $ops ) );
			// Set $filesToLock from the first backend so we don't try to set all
			// locks two or three times (depending on the number of backends).
			if ( $index == 0 ) {
				foreach ( $performOps as $index => $fileOp ) {
					$filesToLock = array_merge( $filesToLock, $fileOp->storagePathsToLock() );
				}
				$filesToLock = array_unique( $filesToLock ); // avoid warnings
			}
		}

		// Try to lock those files...
		$status->merge( $this->lockFiles( $filesToLock ) );
		if ( !$status->isOK() ) {
			return $status; // abort
		}

		// Attempt each operation; abort on failure...
		foreach ( $performOps as $index => $fileOp ) {
			$status->merge( $fileOp->attempt() );
			if ( !$status->isOK() ) { // operation failed?
				// Revert everything done so far and abort.
				// Do this by reverting each previous operation in reverse order.
				$pos = $index - 1; // last one failed; no need to revert()
				while ( $pos >= 0 ) {
					$status->merge( $performOps[$pos]->revert() );
					$pos--;
				}
				return $status;
			}
		}

		// Finish each operation...
		foreach ( $performOps as $index => $fileOp ) {
			$status->merge( $fileOp->finish() );
		}

		// Unlock all the files
		$status->merge( $this->unlockFiles( $filesToLock ) );

		// Make sure status is OK, despite any finish() or unlockFiles() fatals
		$status->setResult( true );

		return $status;
	}

	function store( array $params ) {
		$op = array( 'operation' => 'store' ) + $params;
		return $this->doOperation( array( $op ) );
	}

	function copy( array $params ) {
		$op = array( 'operation' => 'copy' ) + $params;
		return $this->doOperation( array( $op ) );
	}

	function move( array $params ) {
		$op = array( 'operation' => 'move' ) + $params;
		return $this->doOperation( array( $op ) );
	}

	function delete( array $params ){
		$op = array( 'operation' => 'delete' ) + $params;
		return $this->doOperation( array( $op ) );
	}

	function concatenate( array $params ){
		$op = array( 'operation' => 'concatenate' ) + $params;
		return $this->doOperation( array( $op ) );
	}

	function fileExists( array $params ) {
		foreach ( $this->backends as $backend ) {
			if ( $backend->fileExists( $params ) ) {
				return true;
			}
		}
		return false;
	}
 
	function getFileProps( array $params ) {
		foreach ( $this->backends as $backend ) {
			$props = $backend->getFileProps( $params );
			if ( $props !== null ) {
				return $props;
			}
		}
		return null;
	}

	function streamFile( array $params ) {
		if ( !count( $this->backends ) ) {
			return Status::newFatal( "No file backends are defined." );
		}
		foreach ( $this->backends as $backend ) {
			$status = $backend->streamFile( $params );
			if ( $status->isOK() ) {
				return $status;
			} elseif ( headers_sent() ) {
				return $status; // died mid-stream...so this is already fubar
			}
		}
		return Status::newFatal( "Could not stream file {$params['source']}." );
	}

	function getLocalCopy( array $params ) {
		foreach ( $this->backends as $backend ) {
			$tmpFile = $backend->getLocalCopy( $params );
			if ( $tmpFile ) {
				return $tmpFile;
			}
		}
		return null;
	}

	function lockFiles( array $paths ) {
		$status = Status::newGood();
		foreach ( $this->backends as $index => $backend ) {
			$status->merge( $backend->lockFiles( $paths ) );
			if ( !$status->isOk() ) {
				// Lock failed: release the locks done so far each backend
				for ( $i=0; $i < $index; $i++ ) { // don't do backend $index since it failed
					$status->merge( $backend->unlockFiles( $paths ) );
				}
				return $status;
			}
		}
		return $status;
	}

	function unlockFiles( array $paths ) {
		$status = Status::newGood();
		foreach ( $this->backends as $backend ) {
			$status->merge( $backend->unlockFile( $paths ) );
		}
		return $status;
	}
}
