<?php
/**
 * @file
 * @ingroup FileBackend
 */

/**
 * Helper class for representing operations with transaction support.
 * FileBackend::doOperations() will require these classes for supported operations.
 * 
 * Use of large fields should be avoided as we want to be able to support
 * potentially many FileOp classes in large arrays in memory.
 * 
 * @ingroup FileBackend
 */
abstract class FileOp {
	/** $var Array */
	protected $params;
	/** $var FileBackendBase */
	protected $backend;
	/** @var TempLocalFile|null */
	protected $tmpSourceFile, $tmpDestFile;

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

	/*
	 * Get the value of the parameter with the given name.
	 * Returns null if the parameter is not set.
	 * 
	 * @param $name string
	 * @return mixed
	 */
	final public function getParam( $name ) {
		if ( isset( $this->params[$name] ) ) {
			return $this->params[$name];
		}
		return null;
	}

	/**
	 * Get a new empty predicates array for precheck()
	 *
	 * @return Array 
	 */
	final public static function newPredicates() {
		return array( 'exists' => array() );
	}

	/**
	 * Check preconditions of the operation and possibly stash temp files
	 *
	 * @param $predicates Array
	 * @return Status
	 */
	final public function precheck( array &$predicates ) {
		if ( $this->state !== self::STATE_NEW ) {
			return Status::newFatal( 'fileop-fail-state', self::STATE_NEW, $this->state );
		}
		$this->state = self::STATE_CHECKED;
		$status = $this->doPrecheck( $predicates );
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
		// Kill any backup files (useful for background scripts)
		if ( isset( $this->tmpDestFile ) ) {
			$this->tmpDestFile->purge();
		}
		if ( isset( $this->tmpSourceFile ) ) {
			$this->tmpSourceFile->purge();
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
	abstract protected function doPrecheck( array &$predicates );

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
	protected function doFinish() {
		return Status::newGood();
	}

	/**
	 * Backup any file at the source to a temporary file
	 *
	 * @return Status
	 */
	protected function backupSource() {
		$status = Status::newGood();
		// Check if a file already exists at the source...
		$params = array( 'src' => $this->params['src'] );
		if ( $this->backend->fileExists( $params ) ) {
			// Create a temporary backup copy...
			$this->tmpSourcePath = $this->backend->getLocalCopy( $params );
			if ( $this->tmpSourcePath === null ) {
				$status->fatal( 'backend-fail-backup', $this->params['src'] );
				return $status;
			}
		}
		return $status;
	}

	/**
	 * Backup the file at the destination to a temporary file.
	 * Don't bother backing it up unless we might overwrite the file.
	 * This assumes that the destination is in the backend and that
	 * the source is either in the backend or on the file system.
	 * This also handles the 'overwriteSame' check logic.
	 *
	 * @return Status
	 */
	protected function checkAndBackupDest() {
		$status = Status::newGood();

		if ( $this->getParam( 'overwriteDest' ) ) {
			// Create a temporary backup copy...
			$params = array( 'src' => $this->params['dst'] );
			$this->tmpDestFile = $this->backend->getLocalCopy( $params );
			if ( !$this->tmpDestFile ) {
				$status->fatal( 'backend-fail-backup', $this->params['dst'] );
				return $status;
			}
		} elseif ( $this->getParam( 'overwriteSame' ) ) {
			// Get the source content hash (if there is a single source)
			$shash = $this->getSourceMD5();
			// If there is a single source, then we can do some checks already.
			// For things like concatenate(), we need to build a temp file first.
			if ( $shash !== null ) {
				$dhash = $this->getFileMD5( $this->params['dst'] );
				if ( !strlen( $shash ) || !strlen( $dhash ) ) {
					$status->fatal( 'backend-fail-hashes' );
					return $status;
				}
				// Give an error if the files are not identical
				if ( $shash !== $dhash ) {
					$status->fatal( 'backend-fail-notsame', $this->params['dst'] );
				}
				return $status; // do nothing; either OK or bad status
			}
		} else {
			$status->fatal( 'backend-fail-alreadyexists', $this->params['dst'] );
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
				$tmp = $this->backend->getLocalCopy( array( 'src' => $path ) );
				if ( !$tmp ) {
					return false; // error
				}
				$hash = md5_file( $tmp->getPath() );
			}
		// Source file is on file system
		} else {
			$hash = md5_file( $path );
		}
		return $hash;
	}

	/**
	 * Restore any temporary source backup file
	 *
	 * @return Status
	 */
	protected function restoreSource() {
		$status = Status::newGood();
		// Restore any file that was at the destination
		if ( $this->tmpSourcePath !== null ) {
			$params = array(
				'src' => $this->tmpSourcePath,
				'dst' => $this->params['src']
			);
			$status = $this->backend->store( $params );
			if ( !$status->isOK() ) {
				return $status;
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
				'src' => $this->tmpDestFile->getPath(),
				'dst' => $this->params['dst']
			);
			$status = $this->backend->store( $params );
			if ( !$status->isOK() ) {
				return $status;
			}
		}
		return $status;
	}

	/**
	 * Check if a file will exist when this operation is attempted
	 * 
	 * @param $source string
	 * @param $predicates Array
	 * @return bool 
	 */
	final protected function fileExists( $source, $predicates ) {
		if ( isset( $predicates['exists'][$source] ) ) {
			return $predicates['exists'][$source]; // previous op assures this
		} else {
			return $this->backend->fileExists( array( 'src' => $source ) );
		}
	}
}

/**
 * Store a file into the backend from a file on the file system.
 * Parameters similar to FileBackend::store(), which include:
 *     src           : source path on file system
 *     dst           : destination storage path
 *     overwriteDest : do nothing and pass if an identical file exists at destination
 *     overwriteSame : override any existing file at destination
 */
class StoreFileOp extends FileOp {
	protected $checkDest = true;

	protected function doPrecheck( array &$predicates ) {
		$status = Status::newGood();
		// Check if destination file exists
		if ( $this->fileExists( $this->params['dst'], $predicates ) ) {
			if ( !$this->getParam( 'overwriteDest' ) ) {
				$status->fatal( 'backend-fail-alreadyexists', $this->params['dst'] );
				return $status;
			}
		} else {
			$this->checkDest = false;
		}
		// Check if the source file exists on the file system
		if ( !file_exists( $this->params['src'] ) ) {
			$status->fatal( 'backend-fail-notexists', $this->params['src'] );
			return $status;
		}
		// Update file existence predicates
		$predicates['exists'][$this->params['dst']] = true;
		return $status;
	}

	protected function doAttempt() {
		$status = Status::newGood();
		// Create a destination backup copy as needed
		if ( $this->checkDest ) {
			$status->merge( $this->checkAndBackupDest() );
			if ( !$status->isOK() ) {
				return $status;
			}
		}
		// Store the file at the destination
		$status->merge( $this->backend->store( $this->params ) );
		return $status;
	}

	protected function doRevert() {
		// Remove the file saved to the destination
		$params = array( 'src' => $this->params['dst'] );
		$status = $this->backend->delete( $params );
		if ( !$status->isOK() ) {
			return $status; // also can't restore any dest file
		}
		// Restore any file that was at the destination
		$status->merge( $this->restoreDest() );
		return $status;
	}

	protected function getSourceMD5() {
		return md5_file( $this->params['src'] );
	}

	function storagePathsUsed() {
		return array( $this->params['dst'] );
	}
}

/**
 * Create a file in the backend with the given content.
 * Parameters similar to FileBackend::create(), which include:
 *     content       : a string of raw file contents
 *     dst           : destination storage path
 *     overwriteDest : do nothing and pass if an identical file exists at destination
 *     overwriteSame : override any existing file at destination
 */
class CreateFileOp extends FileOp {
	protected $checkDest = true;

	protected function doPrecheck( array &$predicates ) {
		$status = Status::newGood();
		// Check if destination file exists
		if ( $this->fileExists( $this->params['dst'], $predicates ) ) {
			if ( !$this->getParam( 'overwriteDest' ) ) {
				$status->fatal( 'backend-fail-alreadyexists', $this->params['dst'] );
				return $status;
			}
		} else {
			$this->checkDest = false;
		}
		// Update file existence predicates
		$predicates['exists'][$this->params['dst']] = true;
		return $status;
	}

	protected function doAttempt() {
		$status = Status::newGood();
		// Create a destination backup copy as needed
		if ( $this->checkDest ) {
			$status->merge( $this->checkAndBackupDest() );
			if ( !$status->isOK() ) {
				return $status;
			}
		}
		// Create the file at the destination
		$status->merge( $this->backend->create( $this->params ) );
		return $status;
	}

	protected function doRevert() {
		// Remove the file saved to the destination
		$params = array( 'src' => $this->params['dst'] );
		$status = $this->backend->delete( $params );
		if ( !$status->isOK() ) {
			return $status; // also can't restore any dest file
		}
		// Restore any file that was at the destination
		$status->merge( $this->restoreDest() );
		return $status;
	}

	protected function getSourceMD5() {
		return md5( $this->params['content'] );
	}

	function storagePathsUsed() {
		return array( $this->params['dst'] );
	}
}

/**
 * Copy a file from one storage path to another in the backend.
 * Parameters similar to FileBackend::copy(), which include:
 *     src           : source storage path
 *     dst           : destination storage path
 *     overwriteDest : do nothing and pass if an identical file exists at destination
 *     overwriteSame : override any existing file at destination
 */
class CopyFileOp extends FileOp {
	protected $checkDest = true;

	protected function doPrecheck( array &$predicates ) {
		$status = Status::newGood();
		// Check if destination file exists
		if ( $this->fileExists( $this->params['dst'], $predicates ) ) {
			if ( !$this->getParam( 'overwriteDest' ) ) {
				$status->fatal( 'backend-fail-alreadyexists', $this->params['dst'] );
				return $status;
			}
		} else {
			$this->checkDest = false;
		}
		// Check if the source file exists
		if ( !$this->fileExists( $this->params['src'], $predicates ) ) {
			$status->fatal( 'backend-fail-notexists', $this->params['src'] );
			return $status;
		}
		// Update file existence predicates
		$predicates['exists'][$this->params['dst']] = true;
		return $status;
	}

	protected function doAttempt() {
		$status = Status::newGood();
		// Create a destination backup copy as needed
		if ( $this->checkDest ) {
			$status->merge( $this->checkAndBackupDest() );
			if ( !$status->isOK() ) {
				return $status;
			}
		}
		// Copy the file into the destination
		$status->merge( $this->backend->copy( $this->params ) );
		return $status;
	}

	protected function doRevert() {
		$status = Status::newGood();
		// Remove the file saved to the destination
		$params = array( 'src' => $this->params['dst'] );
		$status->merge( $this->backend->delete( $params ) );
		if ( !$status->isOK() ) {
			return $status; // also can't restore any dest file
		}
		// Restore any file that was at the destination
		$status->merge( $this->restoreDest() );
		return $status;
	}

	protected function getSourceMD5() {
		return $this->getFileMD5( $this->params['src'] );
	}

	function storagePathsUsed() {
		return array( $this->params['src'], $this->params['dst'] );
	}
}

/**
 * Move a file from one storage path to another in the backend.
 * Parameters similar to FileBackend::move(), which include:
 *     src           : source storage path
 *     dst           : destination storage path
 *     overwriteDest : do nothing and pass if an identical file exists at destination
 *     overwriteSame : override any existing file at destination
 */
class MoveFileOp extends FileOp {
	protected $usingMove = false; // using backend move() function?
	protected $checkDest = true;

	function initialize() {
		// Use faster, native, move() if applicable
		$this->usingMove = $this->backend->canMove( $this->params );
	}

	protected function doPrecheck( array &$predicates ) {
		$status = Status::newGood();
		// Check if destination file exists
		if ( $this->fileExists( $this->params['dst'], $predicates ) ) {
			if ( !$this->getParam( 'overwriteDest' ) ) {
				$status->fatal( 'backend-fail-alreadyexists', $this->params['dst'] );
				return $status;
			}
		} else {
			$this->checkDest = false;
		}
		// Check if the source file exists
		if ( !$this->fileExists( $this->params['src'], $predicates ) ) {
			$status->fatal( 'backend-fail-notexists', $this->params['src'] );
			return $status;
		}
		// Update file existence predicates
		$predicates['exists'][$this->params['src']] = false;
		$predicates['exists'][$this->params['dst']] = true;
		return $status;
	}

	protected function doAttempt() {
		$status = Status::newGood();
		// Create a destination backup copy as needed
		if ( $this->checkDest ) {
			$status->merge( $this->checkAndBackupDest() );
			if ( !$status->isOK() ) {
				return $status;
			}
		}
		// Native moves: move the file into the destination
		if ( $this->usingMove ) {
			$status->merge( $this->backend->move( $this->params ) );
		// Non-native moves: copy the file into the destination & delete source
		} else {
			// Copy source to dest
			$status->merge( $this->backend->copy( $this->params ) );
			if ( !$status->isOK() ) {
				return $status;
			}
			// Delete source
			$params = array( 'src' => $this->params['src'] );
			$status->merge( $this->backend->delete( $params ) );
			if ( !$status->isOK() ) {
				return $status;
			}
		}
		return $status;
	}

	protected function doRevert() {
		$status = Status::newGood();
		// Native moves: move the file back to the source
		if ( $this->usingMove ) {
			$params = array(
				'src' => $this->params['dst'],
				'dst' => $this->params['src']
			);
			$status->merge( $this->backend->move( $params ) );
			if ( !$status->isOK() ) {
				return $status; // also can't restore any dest file
			}
		// Non-native moves: remove the file saved to the destination
		} else {
			// Copy destination back to source
			$params = array( 'src' => $this->params['dst'], 'dst' => $this->params['src'] );
			$status = $this->backend->copy( $params );
			if ( !$status->isOK() ) {
				return $status; // also can't restore any dest file
			}
			// Delete destination
			$params = array( 'src' => $this->params['dst'] );
			$status = $this->backend->delete( $params );
			if ( !$status->isOK() ) {
				return $status; // also can't restore any dest file
			}
		}
		// Restore any file that was at the destination
		$status->merge( $this->restoreDest() );
		return $status;
	}

	protected function getSourceMD5() {
		return $this->getFileMD5( $this->params['src'] );
	}

	function storagePathsUsed() {
		return array( $this->params['src'], $this->params['dst'] );
	}
}

/**
 * Combines files from severals storage paths into a new file in the backend.
 * Parameters similar to FileBackend::concatenate(), which include:
 *     srcs          : ordered source storage paths (e.g. chunk1, chunk2, ...)
 *     dst           : destination storage path
 *     overwriteDest : do nothing and pass if an identical file exists at destination
 */
class ConcatenateFileOp extends FileOp {
	protected $checkDest = true;

	protected function doPrecheck( array &$predicates ) {
		$status = Status::newGood();
		// Check if destination file exists
		if ( $this->fileExists( $this->params['dst'], $predicates ) ) {
			if ( !$this->getParam( 'overwriteDest' ) ) {
				$status->fatal( 'backend-fail-alreadyexists', $this->params['dst'] );
				return $status;
			}
		} else {
			$this->checkDest = false;
		}
		// Check that source files exists
		foreach ( $this->params['srcs'] as $source ) {
			if ( !$this->fileExists( $source, $predicates ) ) {
				$status->fatal( 'backend-fail-notexists', $source );
				return $status;
			}
		}
		// Update file existence predicates
		$predicates['exists'][$this->params['dst']] = true;
		return $status;
	}

	protected function doAttempt() {
		$status = Status::newGood();
		// Create a destination backup copy as needed
		if ( $this->checkDest ) {
			$status->merge( $this->checkAndBackupDest() );
			if ( !$status->isOK() ) {
				return $status;
			}
		}
		// Concatenate the file at the destination
		$status->merge( $this->backend->concatenate( $this->params ) );
		return $status;
	}

	protected function doRevert() {
		// Remove the file saved to the destination
		$params = array( 'src' => $this->params['dst'] );
		$status = $this->backend->delete( $params );
		if ( !$status->isOK() ) {
			return $status; // also can't restore any dest file
		}
		// Restore any file that was at the destination
		$status->merge( $this->restoreDest() );
		return $status;
	}

	protected function getSourceMD5() {
		return null; // defer this until we finish building the new file
	}

	function storagePathsUsed() {
		return array_merge( $this->params['srcs'], $this->params['dst'] );
	}
}

/**
 * Delete a file at the storage path.
 * Parameters similar to FileBackend::delete(), which include:
 *     src                 : source storage path
 *     ignoreMissingSource : don't return an error if the file does not exist
 */
class DeleteFileOp extends FileOp {
	protected $needsDelete = true;

	protected function doPrecheck( array &$predicates ) {
		$status = Status::newGood();
		// Check if the source file exists
		if ( !$this->fileExists( $this->params['src'], $predicates ) ) {
			if ( !$this->getParam( 'ignoreMissingSource' ) ) {
				$status->fatal( 'backend-fail-notexists', $this->params['src'] );
				return $status;
			}
			$this->needsDelete = false;
		}
		// Update file existence predicates
		$predicates['exists'][$this->params['src']] = false;
		return $status;
	}

	protected function doAttempt() {
		$status = Status::newGood();
		if ( $this->needsDelete ) {
			// Create a source backup copy as needed
			$status->merge( $this->backupSource() );
			if ( !$status->isOK() ) {
				return $status;
			}
			// Delete the source file
			$status->merge( $this->backend->delete( $this->params ) );
			if ( !$status->isOK() ) {
				return $status;
			}
		}
		return $status;
	}

	protected function doRevert() {
		// Restore any source file
		return $this->restoreSource();
	}

	function storagePathsUsed() {
		return array( $this->params['src'] );
	}
}

/**
 * Placeholder operation that has no params and does nothing
 */
class NullFileOp extends FileOp {
	protected function doPrecheck( array &$predicates ) {
		return Status::newGood();
	}

	protected function doAttempt() {
		return Status::newGood();
	}

	protected function doRevert() {
		return Status::newGood();
	}
}
