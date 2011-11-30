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
	/** $var FileBackendBase */
	protected $backend;

	protected $state;
	protected $failed;

	/* Object life-cycle */
	const STATE_NEW = 1;
	const STATE_CHECKED = 2;
	const STATE_ATTEMPTED = 3;
	const STATE_DONE = 4;

	/**
	 * Build a new file operation transaction
	 *
	 * @params $backend FileBackend
	 * @params $params Array
	 */
	final public function __construct( FileBackendBase $backend, array $params ) {
		$this->backend = $backend;
		$this->params = $params;
		$this->state = self::STATE_NEW;
		$this->failed = false;
		$this->initialize();
	}

	/**
	 * Check preconditions of the operation and possibly stash temp files
	 *
	 * @return Status
	 */
	final public function precheck() {
		if ( $this->state !== self::STATE_NEW ) {
			return Status::newFatal( 'fileop-fail-state', self::STATE_NEW, $this->state );
		}
		$this->state = self::STATE_CHECKED;
		$status = $this->doPrecheck();
		if ( !$status->isOK() ) {
			$this->failed = true;
		}
		return $status;
	}

	/**
	 * Attempt the operation; this must be reversible
	 *
	 * @return Status
	 */
	final public function attempt() {
		if ( $this->state !== self::STATE_CHECKED ) {
			return Status::newFatal( 'fileop-fail-state', self::STATE_CHECKED, $this->state );
		} elseif ( $this->failed ) { // failed precheck
			return Status::newFatal( 'fileop-fail-attempt-precheck' );
		}
		$this->state = self::STATE_ATTEMPTED;
		$status = $this->doAttempt();
		if ( !$status->isOK() ) {
			$this->failed = true;
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
			return Status::newFatal( 'fileop-fail-state', self::STATE_ATTEMPTED, $this->state );
		}
		$this->state = self::STATE_DONE;
		if ( $this->failed ) {
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
			return Status::newFatal( 'fileop-fail-state', self::STATE_ATTEMPTED, $this->state );
		}
		$this->state = self::STATE_DONE;
		if ( $this->failed ) {
			$status = Status::newGood(); // nothing to finish
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
	public function storagePathsUsed() {
		return array();
	}

	/**
	 * @return void
	 */
	protected function initialize() {}

	/**
	 * @return Status
	 */
	abstract protected function doPrecheck();

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

	/**
	 * Backup any file at the destination to a temporary file.
	 * Don't bother backing it up unless we might overwrite the file.
	 * This assumes that the destination is in the backend and that
	 * the source is either in the backend or on the file system.
	 *
	 * @return Status
	 */
	protected function checkAndBackupDest() {
		$status = Status::newGood();
		// Check if a file already exists at the destination
		$params = array( 'source' => $this->params['dest'] );
		if ( !$this->backend->fileExists( $params ) ) {
			return $status; // nothing to do
		}

		if ( !empty( $this->params['overwriteDest'] ) ) {
			// Create a temporary backup copy...
			$this->tmpDestFile = $this->getLocalCopy( $this->params['dest'] );
			if ( !$this->tmpDestFile ) {
				$status->fatal( 'backend-fail-backup', $this->params['dest'] );
				return $status;
			}
		} elseif ( !empty( $this->params['overwriteSame'] ) ) {
			// Get the source content hash (if there is a single source)
			$shash = $this->getSourceMD5();
			// If there is a single source, then we can do some checks already.
			// For things like concatenate(), we need to build a temp file first.
			if ( $shash !== null ) {
				$dhash = $this->getFileMD5( $this->params['dest'] );
				if ( !strlen( $shash ) || !strlen( $dhash ) ) {
					$status->fatal( 'backend-fail-hashes' );
					return $status;
				}
				// Give an error if the files are not identical
				if ( $shash !== $dhash ) {
					$status->fatal( 'backend-fail-notsame', $this->params['dest'] );
				}
				return $status; // do nothing; either OK or bad status
			}
		} else {
			$status->fatal( 'backend-fail-alreadyexists', $this->params['dest'] );
			return $status;
		}

		return $status;
	}

	/**
	 * checkAndBackupDest() helper function to get the source file MD5.
	 * Returns false on failure and null if there is no single source.
	 *
	 * @return string|false|null
	 */
	protected function getSourceMD5() {
		return null; // N/A
	}

	/**
	 * checkAndBackupDest() helper function to get the MD5 of a file.
	 *
	 * @return string|false False on failure
	 */
	final protected function getFileMD5( $path ) {
		// Source file is in backend
		if ( FileBackend::isStoragePath( $path ) ) {
			// For some backends (e.g. Swift, Azure) we can get
			// standard hashes to use for this types of comparisons.
			if ( $this->backend->getHashType() === 'md5' ) {
				$hash = $this->backend->getFileHash( $path );
			} else {
				$tmp = $this->getLocalCopy( $path );
				if ( !$tmp ) {
					return false; // error
				}
				$hash = md5_file( $tmp->getPath() );
			}
		// Source file is on disk (FS)
		} else {
			$hash = md5_file( $path );
		}
		return $hash;
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
 * Store a file into the backend from a file on disk.
 * Parameters similar to FileBackend::store(), which include:
 *     source        : source path on disk (FS)
 *     dest          : destination storage path
 *     overwriteDest : do nothing and pass if an identical file exists at destination
 *     overwriteSame : override any existing file at destination
 */
class StoreFileOp extends FileOp {
	/** @var TempLocalFile|null */
	protected $tmpDestFile; // temp copy of existing destination file

	protected function doPrecheck() {
		$status = Status::newGood();
		// Check if the source files exists on disk (FS)
		if ( !file_exists( $this->params['source'] ) ) {
			$status->fatal( 'backend-fail-notexists', $this->params['source'] );
			return $status;
		}
		// Create a destination backup copy as needed
		$status->merge( $this->checkAndBackupDest() );
		return $status;
	}

	protected function doAttempt() {
		// Store the file at the destination
		$status = $this->backend->store( $this->params );
		return $status;
	}

	protected function doRevert() {
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

	protected function doFinish() {
		return Status::newGood();
	}

	protected function getSourceMD5() {
		return md5_file( $this->params['source'] );
	}

	function storagePathsUsed() {
		return array( $this->params['dest'] );
	}
}

/**
 * Create a file in the backend with the given content.
 * Parameters similar to FileBackend::create(), which include:
 *     content       : a string of raw file contents
 *     dest          : destination storage path
 *     overwriteDest : do nothing and pass if an identical file exists at destination
 *     overwriteSame : override any existing file at destination
 */
class CreateFileOp extends FileOp {
	protected function doPrecheck() {
		// Create a destination backup copy as needed
		$status = $this->checkAndBackupDest();
		return $status;
	}

	protected function doAttempt() {
		// Create the file at the destination
		$status = $this->backend->create( $this->params );
		return $status;
	}

	protected function doRevert() {
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

	protected function doFinish() {
		return Status::newGood();
	}

	protected function getSourceMD5() {
		return md5( $this->params['content'] );
	}

	function storagePathsUsed() {
		return array( $this->params['dest'] );
	}
}

/**
 * Copy a file from one storage path to another in the backend.
 * Parameters similar to FileBackend::copy(), which include:
 *     source        : source storage path
 *     dest          : destination storage path
 *     overwriteDest : do nothing and pass if an identical file exists at destination
 *     overwriteSame : override any existing file at destination
 */
class CopyFileOp extends FileOp {
	protected function doPrecheck() {
		$status = Status::newGood();
		// Check if the source files exists on disk
		$params = array( 'source' => $this->params['source'] );
		if ( !$this->backend->fileExists( $params ) ) {
			$status->fatal( 'backend-fail-notexists', $this->params['source'] );
			return $status;
		}
		// Create a destination backup copy as needed
		$status->merge( $this->checkAndBackupDest() );
		return $status;
	}

	protected function doAttempt() {
		// Copy the file into the destination
		$status = $this->backend->copy( $this->params );
		return $status;
	}

	protected function doRevert() {
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

	protected function doFinish() {
		return Status::newGood();
	}

	protected function getSourceMD5() {
		return $this->getFileMD5( $this->params['source'] );
	}

	function storagePathsUsed() {
		return array( $this->params['source'], $this->params['dest'] );
	}
}

/**
 * Move a file from one storage path to another in the backend.
 * Parameters similar to FileBackend::move(), which include:
 *     source        : source storage path
 *     dest          : destination storage path
 *     overwriteDest : do nothing and pass if an identical file exists at destination
 *     overwriteSame : override any existing file at destination
 */
class MoveFileOp extends FileOp {
	protected $usingMove = false; // using backend move() function?

	function initialize() {
		// Use faster, native, move() if applicable
		$this->usingMove = $this->backend->canMove( $this->params );
	}

	protected function doPrecheck() {
		$status = Status::newGood();
		// Check if the source files exists on disk
		$params = array( 'source' => $this->params['source'] );
		if ( !$this->backend->fileExists( $params ) ) {
			$status->fatal( 'backend-fail-notexists', $this->params['source'] );
			return $status;
		}
		// Create a destination backup copy as needed
		$status->merge( $this->checkAndBackupDest() );
		return $status;
	}

	protected function doAttempt() {
		// Native moves: move the file into the destination
		if ( $this->usingMove ) {
			$status = $this->backend->move( $this->params );
		// Non-native moves: copy the file into the destination
		} else {
			$status = $this->backend->copy( $this->params );
		}
		return $status;
	}

	protected function doRevert() {
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

	protected function doFinish() {
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

	protected function getSourceMD5() {
		return $this->getFileMD5( $this->params['source'] );
	}

	function storagePathsUsed() {
		return array( $this->params['source'], $this->params['dest'] );
	}
}

/**
 * Combines files from severals storage paths into a new file in the backend.
 * Parameters similar to FileBackend::concatenate(), which include:
 *     sources       : ordered source storage paths (e.g. chunk1,chunk2,...)
 *     dest          : destination storage path
 *     overwriteDest : do nothing and pass if an identical file exists at destination
 *     overwriteSame : override any existing file at destination
 */
class ConcatenateFileOp extends FileOp {
	protected function doPrecheck() {
		// Create a destination backup copy as needed
		$status = $this->checkAndBackupDest();
		return $status;
	}

	protected function doAttempt() {
		// Concatenate the file at the destination
		$status = $this->backend->concatenate( $this->params );
		return $status;
	}

	protected function doRevert() {
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

	protected function doFinish() {
		return Status::newGood();
	}

	protected function getSourceMD5() {
		return null; // defer this until we finish building the new file
	}

	function storagePathsUsed() {
		return array_merge( $this->params['sources'], $this->params['dest'] );
	}
}

/**
 * Delete a file at the storage path.
 * Parameters similar to FileBackend::delete(), which include:
 *     source              : source storage path
 *     ignoreMissingSource : don't return an error if the file does not exist
 */
class DeleteFileOp extends FileOp {
	protected function doPrecheck() {
		$status = Status::newGood();
		if ( empty( $this->params['ignoreMissingSource'] ) ) {
			$params = array( 'source' => $this->params['source'] );
			if ( !$this->backend->fileExists( $params ) ) {
				$status->fatal( 'backend-fail-notexists', $this->params['source'] );
				return $status;
			}
		}
		return $status;
	}

	protected function doAttempt() {
		return Status::newGood();
	}

	protected function doRevert() {
		return Status::newGood();
	}

	protected function doFinish() {
		// Delete the source file
		$status = $this->backend->delete( $this->params );
		return $status;
	}

	function storagePathsUsed() {
		return array( $this->params['source'] );
	}
}

/**
 * Placeholder operation that has no params and does nothing
 */
class NullFileOp extends FileOp {
	protected function doPrecheck() {
		return Status::newGood();
	}

	protected function doAttempt() {
		return Status::newGood();
	}

	protected function doRevert() {
		return Status::newGood();
	}

	protected function doFinish() {
		return Status::newGood();
	}
}
