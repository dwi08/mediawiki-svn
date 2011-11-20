<?php
/**
 * @file
 * @ingroup FileRepo
 */

/**
 * Base class for all file backend classes.
 * This class defines the methods as abstract that
 * must be implemented in all file backend classes.
 * 
 * All "storage paths" and "storage directories" may be real file system
 * paths or just virtual paths such as object names in Swift.
 */
interface IFileBackend {
	/**
	 * We may have multiple different backends of the same type.
	 * For example, we can have two Swift backends using different proxies.
	 * 
	 * @return string
	 */
	public function getName();

	/**
	 * This is the main entry point into the file system back end. Callers will
	 * supply a list of operations to be performed (almost like a script) as an
	 * array. This class will then handle handing the operations off to the
	 * correct file store module.
	 *
	 * Using $ops
	 * $ops is an array of arrays. The first array holds a list of operations.
	 * The inner array contains the parameters, E.G:
	 * <code>
	 * $ops = array(
	 *      array(
	 *          'operation' => 'store',
	 *          'src'       => '/tmp/uploads/picture.png',
	 *          'dest'      => 'zone/uploadedFilename.png'
	 *      )
	 * );
	 * </code>
	 * 
	 * @param Array $ops Array of arrays containing N operations to execute IN ORDER
	 * @return Status
	 */
	public function doOperations( array $ops );

	/**
	 * Return a list of FileOp objects from a list of operations.
	 * An exception is thrown if an unsupported operation is requested.
	 * 
	 * @param Array $ops Same format as doOperations()
	 * @return Array
	 * @throws MWException
	 */
	public function getOperations( array $ops );

	/**
	 * Store a file into the backend from a file on disk.
	 * Do not call this function from places other than FileOp.
	 * $params include:
	 *      source        : source path on disk
	 *      dest          : destination storage path
	 *      overwriteDest : do nothing and pass if an identical file exists at destination
	 *      overwriteSame : override any existing file at destination
	 * 
	 * @param Array $params
	 * @return Status
	 */
	public function store( array $params );

	/**
	 * Copy a file from one storage path to another in the backend.
	 * Do not call this function from places other than FileOp.
	 * $params include:
	 *      source        : source storage path
	 *      dest          : destination storage path
	 *      overwriteDest : do nothing and pass if an identical file exists at destination
	 *      overwriteSame : override any existing file at destination
	 * 
	 * @param Array $params 
	 * @return Status
	 */
	public function copy( array $params );

	/**
	 * Copy a file from one storage path to another in the backend.
	 * This can be left as a dummy function as long as hasMove() returns false.
	 * Do not call this function from places other than FileOp.
	 * $params include:
	 *      source        : source storage path
	 *      dest          : destination storage path
	 *      overwriteDest : do nothing and pass if an identical file exists at destination
	 *      overwriteSame : override any existing file at destination
	 * 
	 * @param Array $params 
	 * @return Status
	 */
	public function move( array $params );

	/**
	 * Delete a file at the storage path.
	 * Do not call this function from places other than FileOp.
	 * $params include:
	 *      source              : source storage path
	 *      ignoreMissingSource : don't return an error if the file does not exist
	 * 
	 * @param Array $params 
	 * @return Status
	 */
	public function delete( array $params );

	/**
	 * Combines files from severals storage paths into a new file in the backend.
	 * Do not call this function from places other than FileOp.
	 * $params include:
	 *      source        : source storage path
	 *      dest          : destination storage path
	 *      overwriteDest : do nothing and pass if an identical file exists at destination
	 *      overwriteSame : override any existing file at destination
	 * 
	 * @param Array $params 
	 * @return Status
	 */
	public function concatenate( array $params );

	/**
	 * Whether this backend has a move() implementation.
	 * Do not call this function from places other than FileOp.
	 *
	 * @return bool
	 */
	public function hasMove();

	/**
	 * Check if a file exits at a storage path in the backend.
	 * Do not call this function from places other than FileOp.
	 * $params include:
	 *      source : source storage path
	 * 
	 * @param Array $params 
	 * @return bool
	 */
	public function fileExists( array $params );

	/**
	 * Get the properties of the file that exists at a storage path in the backend
	 * $params include:
	 *      source : source storage path
	 * 
	 * @param Array $params 
	 * @return Array|null Gives null if the file does not exist
	 */
	public function getFileProps( array $params );

	/**
	 * Get a local copy on dist of the file at a storage path in the backend
	 * $params include:
	 *      source : source storage path
	 * 
	 * @param Array $params 
	 * @return string|null Path to temporary file or null on failure
	 */
	public function getLocalCopy( array $params );

	/**
	 * Lock the files at the given storage paths in the backend.
	 * Do not call this function from places other than FileOp.
	 * 
	 * @param $sources Array Source storage paths
	 * @return Status
	 */
	public function lockFiles( array $sources );

	/**
	 * Unlock the files at the given storage paths in the backend.
	 * Do not call this function from places other than FileOp.
	 * 
	 * @param $sources Array Source storage paths
	 * @return Status
	 */
	public function unlockFiles( array $sources );
}

/**
 * This class defines the methods as abstract that should be
 * implemented in child classes that represent a single-write backend.
 */
abstract class FileBackend implements IFileBackend {
	protected $name;
	/** @var FileLockManager */
	protected $lockManager;

	final public function getName() {
		return $this->name;
	}

	/**
	 * Build a new object from configuration.
	 * This should only be called from within FileRepo classes.
	 * $config includes:
	 *     'name'        : The name of this backend
	 *     'lockManager' : The lock manager to use
	 * 
	 * @param $config Array
	 */
	public function __construct( array $config ) {
		$this->name = $config['name'];
		$this->lockManager = $config['lockManger'];
	}

	function hasMove() {
		return false; // not implemented
	}

	function move( array $params ) {
		throw new MWException( "This function is not implemented." );
	}

	/**
	 * Get the list of supported operations and their corresponding FileOp classes.
	 * Subclasses should implement these using FileOp subclasses
	 * 
	 * @return Array
	 */
	protected function supportedOperations() {
		return array(
			'store'       => 'FileStoreOp',
			'copy'        => 'FileCopyOp',
			'move'        => 'FileMoveOp',
			'delete'      => 'FileDeleteOp',
			'concatenate' => 'FileConcatenateOp'
		);
	}

	final public function getOperations( array $ops ) {
		$supportedOps = $this->supportedOperations();

		$performOps = array(); // array of FileOp objects
		// Build up ordered array of FileOps...
		foreach ( $ops as $operation ) {
			$opName = $operation['operation'];
			if ( isset( $supportedOps[$opName] ) ) {
				$class = $supportedOps[$opName];
				// Get params for this operation
				$params = $operation;
				unset( $params['operation'] ); // don't need this
				// Add operation on to the list of things to do
				$performOps[] = new $class( $params );
			} else {
				throw new MWException( "Operation `$opName` is not supported." );
			}
		}

		return $performOps;
	}

	final public function doOperations( array $ops ) {
		$status = Status::newGood();
		// Build up a list of FileOps...
		$performOps = $this->getOperations( $ops );
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

	final public function lockFiles( array $paths ) {
		// Locks should be specific to this backend location
		$backendKey = get_class( $this ) . '-' . $this->getName();
		return $this->lockManager->lock( $backendKey, $paths ); // not supported
	}

	final public function unlockFiles( array $paths ) {
		// Locks should be specific to this backend location
		$backendKey = get_class( $this ) . '-' . $this->getName();
		return $this->lockManager->unlock( $backendKey, $paths ); // not supported
	}
}

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
		$status = $this->setLocks();
		if ( $status->isOK() ) {
			$status = $this->doAttempt();
		} else {
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
		if ( !$this->failedAttempt ) {
			$status = $this->doRevert();
		} else {
			$status = Status::newGood(); // nothing to revert
		}
		$this->unsetLocks();
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
		if ( !$this->failedAttempt ) {
			$status = $this->doFinish();
		} else {
			$status = Status::newGood(); // nothing to revert
		}
		$this->unsetLocks();
		return $status;
	}

	/**
	 * Try to lock any files before changing them
	 *
	 * @return Status
	 */
	private function setLocks() {
		return $this->backend->lockFiles( $this->storagePathsToLock() );
	}

	/**
	 * Try to unlock any files that this locked
	 *
	 * @return Status
	 */
	private function unsetLocks() {
		return $this->backend->unlockFiles( $this->storagePathsToLock() );
	}

	/**
	 * Get a list of storage paths to lock for this operation
	 *
	 * @return Array
	 */
	protected function storagePathsToLock() {
		return array();
	}

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
 *      source        : source path on disk
 *      dest          : destination storage path
 *      overwriteDest : do nothing and pass if an identical file exists at destination
 *      overwriteSame : override any existing file at destination
 */
class FileStoreOp extends FileOp {
	protected $tmpDestPath; // temp copy of existing destination file

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
				$this->tmpDestPath = $this->getLocalCopy( $this->params['dest'] );
				if ( $this->tmpDestPath === null ) {
					$status->fatal( "Could not backup destination file." );
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
		if ( $this->tmpDestPath !== null ) {
			$params = array(
				'source' => $this->tmpDestPath,
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
 * Copy a file from one storage path to another in the backend.
 * Parameters must match FileBackend::copy(), which include:
 *      source        : source storage path
 *      dest          : destination storage path
 *      overwriteDest : do nothing and pass if an identical file exists at destination
 *      overwriteSame : override any existing file at destination
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
 *      source        : source storage path
 *      dest          : destination storage path
 *      overwriteDest : do nothing and pass if an identical file exists at destination
 *      overwriteSame : override any existing file at destination
 */
class FileMoveOp extends FileStoreOp {
	function doAttempt() {
		// Create a backup copy of any file that exists at destination
		$status = $this->backupDest();
		if ( !$status->isOK() ) {
			return $status;
		}
		// Native moves: move the file into the destination
		if ( $this->backend->hasMove() ) {
			$status = $this->backend->move( $this->params );
		// Non-native moves: copy the file into the destination
		} else {
			$status = $this->backend->copy( $this->params );
		}
		return $status;
	}

	function doRevert() {
		// Native moves: move the file back to the source
		if ( $this->backend->hasMove() ) {
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
		if ( $this->backend->hasMove() ) {
			$status = Status::newGood();
		// Non-native moves: delete the source file
		} else {
			$params = array( 'source' => $this->params['source'] );
			$status = $this->backend->delete( $params );
		}
		return $status;
	}
}

/**
 * Combines files from severals storage paths into a new file in the backend.
 * Parameters must match FileBackend::concatenate(), which include:
 *      sources       : ordered source storage paths (e.g. chunk1,chunk2,...)
 *      dest          : destination storage path
 *      overwriteDest : do nothing and pass if an identical file exists at destination
 *      overwriteSame : override any existing file at destination
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
 *      source              : source storage path
 *      ignoreMissingSource : don't return an error if the file does not exist
 */
class FileDeleteOp extends FileOp {
	function doAttempt() {
		$status = Status::newGood();
		if ( !$this->params['ignoreMissingSource'] ) {
			if ( !$this->backend->fileExists( $this->params['source'] ) ) {
				$status->fatal( "Cannot delete file because it does not exist." );
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
