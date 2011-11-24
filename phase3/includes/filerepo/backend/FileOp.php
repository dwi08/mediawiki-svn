<?php
/**
 * @file
 * @ingroup FileRepo
 */

/**
 * Helper class for representing operations with transaction support.
 * FileBackend::doOperations() will require these classes for supported operations.
 * 
 * Access use of large fields should be avoided as we want to be able to support
 * potentially many FileOp classes in large arrays in memory.
 */
abstract class FileOp {
	/** $var Array */
	protected $params;
	/** $var FileBackend */
	protected $backend;

	protected $state;
	protected $failedAttempt;

	const STATE_NEW = 1;
	const STATE_ATTEMPTED = 2;
	const STATE_DONE = 3;

	/**
	 * Build a new file operation transaction
	 *
	 * @params $backend FileBackend
	 * @params $params Array
	 */
	final public function __construct( FileBackend $backend, array $params ) {
		$this->backend = $backend;
		$this->params = $params;
		$this->state = self::STATE_NEW;
		$this->failedAttempt = false;
		$this->initialize();
	}

	/**
	 * Attempt the operation; this must be reversible
	 *
	 * @return Status
	 */
	final public function attempt() {
		if ( $this->state !== self::STATE_NEW ) {
			throw new MWException( "Cannot attempt operation called twice." );
		}
		$this->state = self::STATE_ATTEMPTED;
		$status = $this->doAttempt();
		if ( !$status->isOK() ) {
			$this->failedAttempt = true;
		}
		return $status;
	}

	/**
	 * Revert the operation; affected files are restored
	 *
	 * @return Status
	 */
	final public function revert() {
		if ( $this->state !== self::STATE_ATTEMPTED ) {
			throw new MWException( "Cannot rollback an unstarted or finished operation." );
		}
		$this->state = self::STATE_DONE;
		if ( $this->failedAttempt ) {
			$status = Status::newGood(); // nothing to revert
		} else {
			$status = $this->doRevert();
		}
		return $status;
	}

	/**
	 * Finish the operation; this may be irreversible
	 *
	 * @return Status
	 */
	final public function finish() {
		if ( $this->state !== self::STATE_ATTEMPTED ) {
			throw new MWException( "Cannot cleanup an unstarted or finished operation." );
		}
		$this->state = self::STATE_DONE;
		if ( $this->failedAttempt ) {
			$status = Status::newGood(); // nothing to revert
		} else {
			$status = $this->doFinish();
		}
		return $status;
	}

	/**
	 * Get a list of storage paths to lock for this operation
	 *
	 * @return Array
	 */
	public function storagePathsToLock() {
		return array();
	}

	/**
	 * @return void
	 */
	protected function initialize() {}

	/**
	 * @return Status
	 */
	abstract protected function doAttempt();

	/**
	 * @return Status
	 */
	abstract protected function doRevert();

	/**
	 * @return Status
	 */
	abstract protected function doFinish();
}

/**
 * Store a file into the backend from a file on disk.
 * Parameters must match FileBackend::store(), which include:
 *     source        : source path on disk
 *     dest          : destination storage path
 *     overwriteDest : do nothing and pass if an identical file exists at destination
 *     overwriteSame : override any existing file at destination
 */
class FileStoreOp extends FileOp {
	/** @var TempLocalFile|null */
	protected $tmpDestFile; // temp copy of existing destination file

	function doAttempt() {
		// Create a backup copy of any file that exists at destination
		$status = $this->backupDest();
		if ( !$status->isOK() ) {
			return $status;
		}
		// Store the file at the destination
		$status = $this->backend->store( $this->params );
		return $status;
	}

	function doRevert() {
		// Remove the file saved to the destination
		$params = array( 'source' => $this->params['dest'] );
		$status = $this->backend->delete( $params );
		if ( !$status->isOK() ) {
			return $status; // also can't restore any dest file
		}
		// Restore any file that was at the destination
		$status = $this->restoreDest();
		return $status;
	}

	function doFinish() {
		return Status::newGood();
	}

	function storagePathsToLock() {
		return array( $this->params['dest'] );
	}

	/**
	 * Backup any file at destination to a temporary file.
	 * Don't bother backing it up unless we might overwrite the file.
	 *
	 * @return Status
	 */
	protected function backupDest() {
		$status = Status::newGood();
		// Check if a file already exists at the destination...
		if ( $this->backend->fileExists( $this->params['dest'] ) ) {
			if ( $this->params['overwriteDest'] ) {
				// Create a temporary backup copy...
				$this->tmpDestFile = $this->getLocalCopy( $this->params['dest'] );
				if ( !$this->tmpDestFile ) {
					$status->fatal( 'backend-fail-restore', $this->params['dest'] );
					return $status;
				}
			}
		}
		return $status;
	}

	/**
	 * Restore any temporary destination backup file
	 *
	 * @return Status
	 */
	protected function restoreDest() {
		$status = Status::newGood();
		// Restore any file that was at the destination
		if ( $this->tmpDestFile ) {
			$params = array(
				'source' => $this->tmpDestFile->getPath(),
				'dest'   => $this->params['dest']
			);
			$status = $this->backend->store( $params );
			if ( !$status->isOK() ) {
				return $status;
			}
		}
		return $status;
	}
}

/**
 * Create a file in the backend with the given content.
 * Parameters must match FileBackend::create(), which include:
 *     content       : a string of raw file contents
 *     dest          : destination storage path
 *     overwriteDest : do nothing and pass if an identical file exists at destination
 *     overwriteSame : override any existing file at destination
 */
class FileCreateOp extends FileStoreOp {
	function doAttempt() {
		// Create a backup copy of any file that exists at destination
		$status = $this->backupDest();
		if ( !$status->isOK() ) {
			return $status;
		}
		// Create the file at the destination
		$status = $this->backend->create( $this->params );
		return $status;
	}

	function doRevert() {
		// Remove the file saved to the destination
		$params = array( 'source' => $this->params['dest'] );
		$status = $this->backend->delete( $params );
		if ( !$status->isOK() ) {
			return $status; // also can't restore any dest file
		}
		// Restore any file that was at the destination
		$status = $this->restoreDest();
		return $status;
	}

	function doFinish() {
		return Status::newGood();
	}

	function storagePathsToLock() {
		return array( $this->params['dest'] );
	}
}

/**
 * Copy a file from one storage path to another in the backend.
 * Parameters must match FileBackend::copy(), which include:
 *     source        : source storage path
 *     dest          : destination storage path
 *     overwriteDest : do nothing and pass if an identical file exists at destination
 *     overwriteSame : override any existing file at destination
 */
class FileCopyOp extends FileStoreOp {
	function doAttempt() {
		// Create a backup copy of any file that exists at destination
		$status = $this->backupDest();
		if ( !$status->isOK() ) {
			return $status;
		}
		// Copy the file into the destination
		$status = $this->backend->copy( $this->params );
		return $status;
	}

	function doRevert() {
		// Remove the file saved to the destination
		$params = array( 'source' => $this->params['dest'] );
		$status = $this->backend->delete( $params );
		if ( !$status->isOK() ) {
			return $status; // also can't restore any dest file
		}
		// Restore any file that was at the destination
		$status = $this->restoreDest();
		return $status;
	}

	function doFinish() {
		return Status::newGood();
	}

	function storagePathsToLock() {
		return array( $this->params['source'], $this->params['dest'] );
	}
}

/**
 * Move a file from one storage path to another in the backend.
 * Parameters must match FileBackend::move(), which include:
 *     source        : source storage path
 *     dest          : destination storage path
 *     overwriteDest : do nothing and pass if an identical file exists at destination
 *     overwriteSame : override any existing file at destination
 */
class FileMoveOp extends FileStoreOp {
	protected $usingMove = false; // using backend move() function?

	function initialize() {
		// Use faster, native, move() if applicable
		$this->usingMove = $this->backend->canMove( $this->params );
	}

	function doAttempt() {
		// Create a backup copy of any file that exists at destination
		$status = $this->backupDest();
		if ( !$status->isOK() ) {
			return $status;
		}
		// Native moves: move the file into the destination
		if ( $this->usingMove ) {
			$status = $this->backend->move( $this->params );
		// Non-native moves: copy the file into the destination
		} else {
			$status = $this->backend->copy( $this->params );
		}
		return $status;
	}

	function doRevert() {
		// Native moves: move the file back to the source
		if ( $this->usingMove ) {
			$params = array(
				'source' => $this->params['dest'],
				'dest'   => $this->params['source']
			);
			$status = $this->backend->move( $params );
			if ( !$status->isOK() ) {
				return $status; // also can't restore any dest file
			}
		// Non-native moves: remove the file saved to the destination
		} else {
			$params = array( 'source' => $this->params['dest'] );
			$status = $this->backend->delete( $params );
			if ( !$status->isOK() ) {
				return $status; // also can't restore any dest file
			}
		}
		// Restore any file that was at the destination
		$status = $this->restoreDest();
		return $status;
	}

	function doFinish() {
		// Native moves: nothing is at the source anymore
		if ( $this->usingMove ) {
			$status = Status::newGood();
		// Non-native moves: delete the source file
		} else {
			$params = array( 'source' => $this->params['source'] );
			$status = $this->backend->delete( $params );
		}
		return $status;
	}

	function storagePathsToLock() {
		return array( $this->params['source'], $this->params['dest'] );
	}
}

/**
 * Combines files from severals storage paths into a new file in the backend.
 * Parameters must match FileBackend::concatenate(), which include:
 *     sources       : ordered source storage paths (e.g. chunk1,chunk2,...)
 *     dest          : destination storage path
 *     overwriteDest : do nothing and pass if an identical file exists at destination
 *     overwriteSame : override any existing file at destination
 */
class FileConcatenateOp extends FileStoreOp {
	function doAttempt() {
		// Create a backup copy of any file that exists at destination
		$status = $this->backupDest();
		if ( !$status->isOK() ) {
			return $status;
		}
		// Concatenate the file at the destination
		$status = $this->backend->concatenate( $this->params );
		return $status;
	}

	function doRevert() {
		// Remove the file saved to the destination
		$params = array( 'source' => $this->params['dest'] );
		$status = $this->backend->delete( $params );
		if ( !$status->isOK() ) {
			return $status; // also can't restore any dest file
		}
		// Restore any file that was at the destination
		$status = $this->restoreDest();
		return $status;
	}

	function doFinish() {
		return Status::newGood();
	}

	function storagePathsToLock() {
		return array_merge( $this->params['sources'], $this->params['dest'] );
	}
}

/**
 * Delete a file at the storage path.
 * Parameters must match FileBackend::delete(), which include:
 *     source              : source storage path
 *     ignoreMissingSource : don't return an error if the file does not exist
 */
class FileDeleteOp extends FileOp {
	function doAttempt() {
		$status = Status::newGood();
		if ( !$this->params['ignoreMissingSource'] ) {
			if ( !$this->backend->fileExists( $this->params['source'] ) ) {
				$status->fatal( 'backend-fail-notexists', $this->params['source'] );
				return $status;
			}
		}
		return $status;
	}

	function doRevert() {
		return Status::newGood();
	}

	function doFinish() {
		// Delete the source file
		$status = $this->fileBackend->delete( $this->params );
		return $status;
	}

	function storagePathsToLock() {
		return array( $this->params['source'] );
	}
}
