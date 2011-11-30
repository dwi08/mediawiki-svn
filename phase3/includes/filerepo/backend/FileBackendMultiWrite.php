<?php
/**
 * @file
 * @ingroup FileRepo
 * @ingroup FileBackend
 */

/**
 * This class defines a multi-write backend. Multiple backends can
 * be registered to this proxy backend it will act as a single backend.
 * Use this when all access to the backends is through this proxy backend.
 * At least one of the backends must be declared the "master" backend.
 * 
 * The order that the backends are defined sets the priority of which
 * backend is read from or written to first. Functions like fileExists()
 * and getFileProps() will return information based on the first backend
 * that has the file (normally both should have it anyway). Special cases:
 *     a) getFileList() will return results from the first backend that is
 *        not declared as non-persistent cache. This is for correctness.
 *     b) getFileTimestamp() will always check only the master backend to
 *        avoid confusing and inconsistent results.
 *     c) getFileHash() will always check only the master backend to keep
 *        the result format consistent.
 * 
 * All write operations are performed on all backends.
 * If an operation fails on one backend it will be rolled back from the others.
 *
 * @ingroup FileRepo
 * @ingroup FileBackend
 */
class FileBackendMultiWrite extends FileBackendBase {
	/** @var Array Prioritized list of FileBackend objects */
	protected $fileBackends = array(); // array of (backend index => backends)
	/** @var Array List of FileBackend object informations */
	protected $fileBackendsInfo = array(); // array (backend index => array of settings)

	protected $masterIndex; // index of master backend

	/**
	 * Construct a proxy backend that consist of several internal backends.
	 * $config contains:
	 *     'name'       : The name of the proxy backend
	 *     'lockManger' : LockManager instance
	 *     'backends'   : Array of (backend object, settings) pairs.
	 *                    The settings per backend include:
	 *                        'isCache' : The backend is non-persistent
	 *                        'isMaster': This must be set for one non-persistent backend.
	 * @param $config Array
	 */
	public function __construct( array $config ) {
		parent::__construct( $config );

		$this->masterIndex = -1;
		foreach ( $config['backends'] as $index => $info ) {
			list( $backend, $settings ) = $info;
			$this->fileBackends[$index] = $backend;
			// Default backend settings
			$defaults = array( 'isCache' => false, 'isMaster' => false );
			// Apply custom backend settings to defaults
			$this->fileBackendsInfo[$index] = $info + $defaults;
			if ( $info['isMaster'] ) {
				if ( $this->masterIndex >= 0 ) {
					throw new MWException( 'More than one master backend defined.' );
				}
				$this->masterIndex = $index;
			}
		}
		if ( $this->masterIndex < 0 ) { // need backends and must have a master
			throw new MWException( 'No master backend defined.' );
		}
	}

	final public function doOperations( array $ops ) {
		$status = Status::newGood( array() );

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
					$filesToLock = array_merge( $filesToLock, $fileOp->storagePathsUsed() );
				}
			}
		}

		// Try to lock those files...
		$status->merge( $this->lockFiles( $filesToLock ) );
		if ( !$status->isOK() ) {
			return $status; // abort
		}

		$failedOps = array(); // failed ops with ignoreErrors
		// Do pre-checks for each operation; abort on failure...
		foreach ( $performOps as $index => $fileOp ) {
			$status->merge( $fileOp->precheck() );
			if ( !$status->isOK() ) { // operation failed?
				if ( !empty( $ops[$index]['ignoreErrors'] ) ) {
					$failedOps[$index] = 1; // remember not to call attempt()/finish()
					++$status->failCount;
					$status->value[$index] = false;
				} else {
					$status->merge( $this->unlockFiles( $filesToLock ) );
					return $status;
				}
			}
		}

		// Attempt each operation; abort on failure...
		foreach ( $performOps as $index => $fileOp ) {
			if ( isset( $failedOps[$index] ) ) {
				continue; // nothing to do
			}
			$status->merge( $fileOp->attempt() );
			if ( !$status->isOK() ) { // operation failed?
				if ( !empty( $ops[$index]['ignoreErrors'] ) ) {
					$failedOps[$index] = 1; // remember not to call finish()
					++$status->failCount;
					$status->value[$index] = false;
				} else {
					// Revert everything done so far and abort.
					// Do this by reverting each previous operation in reverse order.
					$pos = $index - 1; // last one failed; no need to revert()
					while ( $pos >= 0 ) {
						if ( !isset( $failedOps[$pos] ) ) {
							$status->merge( $performOps[$pos]->revert() );
						}
						$pos--;
					}
					$status->merge( $this->unlockFiles( $filesToLock ) );
					return $status;
				}
			}
		}

		// Finish each operation...
		foreach ( $performOps as $index => $fileOp ) {
			if ( isset( $failedOps[$index] ) ) {
				continue; // nothing to do
			}
			$subStatus = $fileOp->finish();
			if ( $subStatus->isOK() ) {
				++$status->successCount;
				$status->value[$index] = true;
			} else {
				++$status->failCount;
				$status->value[$index] = false;
			}
			$status->merge( $subStatus );
		}

		// Unlock all the files
		$status->merge( $this->unlockFiles( $filesToLock ) );

		// Make sure status is OK, despite any finish() or unlockFiles() fatals
		$status->setResult( true );

		return $status;
	}

	function prepare( array $params ) {
		$status = Status::newGood();
		foreach ( $this->backends as $backend ) {
			$status->merge( $backend->prepare( $params ) );
		}
		return $status;
	}

	function secure( array $params ) {
		$status = Status::newGood();
		foreach ( $this->backends as $backend ) {
			$status->merge( $backend->secure( $params ) );
		}
		return $status;
	}

	function clean( array $params ) {
		$status = Status::newGood();
		foreach ( $this->backends as $backend ) {
			$status->merge( $backend->clean( $params ) );
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

	function getFileTimestamp( array $params ) {
		// Skip non-master for consistent timestamps
		return $this->backends[$this->masterIndex]->getFileTimestamp( $params );
	}

	function getFileHash( array $params ) {
		// Skip non-master for consistent hash formats
		return $this->backends[$this->masterIndex]->getFileHash( $params );
	}

	function getHashType() {
		// Skip non-master for consistent hash formats
		return $this->backends[$this->masterIndex]->getHashType();
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
		foreach ( $this->backends as $backend ) {
			$status = $backend->streamFile( $params );
			if ( $status->isOK() ) {
				return $status;
			} elseif ( headers_sent() ) {
				return $status; // died mid-stream...so this is already fubar
			}
		}
		return Status::newFatal( 'backend-fail-stream', $params['source'] );
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
