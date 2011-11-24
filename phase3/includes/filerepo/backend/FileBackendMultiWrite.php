<?php
/**
 * @file
 * @ingroup FileRepo
 */

/**
 * This class defines a multi-write backend. Multiple backends can
 * be registered to this proxy backend it will act as a single backend.
 * Use this only if all access to the backends is through an instance of this class.
 * 
 * The order that the backends are defined sets the priority of which
 * backend is read from or written to first. Functions like fileExists()
 * and getFileProps() will return information based on the first backend
 * that has the file (normally both should have it anyway). Special cases:
 *     a) getFileList() will return results from the first backend that is
 *        not declared as non-persistent cache. This is for correctness.
 *     b) getFileHash() will always check only the master backend to keep the
 *        result format consistent.
 * 
 * All write operations are performed on all backends.
 * If an operation fails on one backend it will be rolled back from the others.
 */
class FileBackendMultiWrite extends FileBackendBase {
	/** @var Array Prioritized list of FileBackend objects */
	protected $fileBackends = array(); // array of (backend index => backends)
	/** @var Array List of FileBackend object informations */
	protected $fileBackendsInfo = array(); // array (backend index => array of settings)

	/**
	 * Construct a proxy backend that consist of several internal backends.
	 * $config contains:
	 *     'name'       : The name of the proxy backend
	 *     'lockManger' : FileLockManager instance
	 *     'backends'   : Array of (backend object, settings) pairs.
	 *                    The settings per backend include:
	 *                        'isCache' : The backend is non-persistent
	 *                        'isMaster': This must be set for one non-persistent backend.
	 * @param $config Array
	 */
	public function __construct( array $config ) {
		$this->name = $config['name'];
		$this->lockManager = $config['lockManger'];

		$hasMaster = false;
		foreach ( $config['backends'] as $index => $info ) {
			list( $backend, $settings ) = $info;
			$this->fileBackends[$index] = $backend;
			// Default backend settings
			$defaults = array( 'isCache' => false, 'isMaster' => false );
			// Apply custom backend settings to defaults
			$this->fileBackendsInfo[$index] = $info + $defaults;
			if ( $info['isMaster'] ) {
				if ( $hasMaster ) {
					throw new MWException( 'More than one master backend defined.' );
				}
				$hasMaster = true;
			}
		}
		if ( !$hasMaster ) {
			throw new MWException( 'No master backend defined.' );
		}
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

		// Do pre-checks for each operation; abort on failure...
		foreach ( $performOps as $index => $fileOp ) {
			$status->merge( $fileOp->precheck() );
			if ( !$status->isOK() ) { // operation failed?
				$status->merge( $this->unlockFiles( $filesToLock ) );
				return $status;
			}
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
				$status->merge( $this->unlockFiles( $filesToLock ) );
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

	function canMove( array $params ) {
		return true; // this is irrelevant
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

	function create( array $params ) {
		$op = array( 'operation' => 'create' ) + $params;
		return $this->doOperation( array( $op ) );
	}

	function prepare( array $params ) {
		$status = Status::newGood();
		foreach ( $this->backends as $backend ) {
			$status->merge( $backend->prepare( $params ) );
		}
		return $status;
	}

	function fileExists( array $params ) {
		foreach ( $this->backends as $backend ) {
			if ( $backend->fileExists( $params ) ) {
				return true;
			}
		}
		return false;
	}

	function getFileHash( array $params ) {
		foreach ( $this->backends as $backend ) {
			// Skip non-master for consistent hash formats
			if ( $this->fileBackendsInfo[$index]['isMaster'] ) {
				return $backend->getFileHash( $params );
			}
		}
		return false;
	}

	function getHashType() {
		foreach ( $this->backends as $backend ) {
			// Skip non-master for consistent hash formats
			if ( $this->fileBackendsInfo[$index]['isMaster'] ) {
				return $backend->getHashType();
			}
		}
		return null; // shouldn't happen
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

	function getFileList( array $params ) {
		foreach ( $this->backends as $index => $backend ) {
			// Skip cache backends (like one using memcached)
			if ( !$this->fileBackendsInfo[$index]['isCache'] ) {
				return $backend->getFileList( $params );
			}
		}
		return array();
	}
}
