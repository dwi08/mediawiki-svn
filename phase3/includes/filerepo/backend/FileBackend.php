<?php
/**
 * @file
 * @ingroup FileRepo
 */

/**
 * Base class for all file backend classes (including multi-write backends).
 * This class defines the methods as abstract that must be implemented subclasses.
 * 
 * All "storage paths" are of the format "mwstore://backend/container/path".
 * The paths use typical file system notation, though any particular backend may
 * not actually be using a local filesystem. Therefore, the paths are only virtual.
 *
 * All functions should avoid throwing exceptions at all costs.
 * As a corollary, external dependencies should be kept to a minimal.
 */
abstract class FileBackendBase {
	protected $name; // unique backend name
	/** @var FileLockManager */
	protected $lockManager;

	/**
	 * Build a new object from configuration.
	 * This should only be called from within FileRepo classes.
	 * $config includes:
	 *     'name'        : The name of this backend
	 *     'lockManager' : The file lock manager to use
	 * 
	 * @param $config Array
	 */
	public function __construct( array $config ) {
		$this->name = $config['name'];
		$this->lockManager = $config['lockManger'];
	}

	/**
	 * We may have multiple different backends of the same type.
	 * For example, we can have two Swift backends using different proxies.
	 * All backend instances must have unique names.
	 * 
	 * @return string
	 */
	final public function getName() {
		return $this->name;
	}

	/**
	 * This is the main entry point into the file system back end. Callers will
	 * supply a list of operations to be performed (almost like a script) as an
	 * array. This class will then handle handing the operations off to the
	 * correct file store module.
	 *
	 * Using $ops:
	 * $ops is an array of arrays. The first array holds a list of operations.
	 * The inner array contains the parameters, E.G:
	 * <code>
	 * $ops = array(
	 *     array(
	 *         'operation' => 'store',
	 *         'src'       => '/tmp/uploads/picture.png',
	 *         'dest'      => 'mwstore://container/uploadedFilename.png'
	 *     )
	 * );
	 * </code>
	 * 
	 * @param Array $ops Array of arrays containing N operations to execute IN ORDER
	 * @return Status
	 */
	abstract public function doOperations( array $ops );

	/**
	 * Store a file into the backend from a file on disk.
	 * Do not call this function from places outside FileBackend and FileOp.
	 * $params include:
	 *     source        : source path on disk
	 *     dest          : destination storage path
	 *     overwriteDest : do nothing and pass if an identical file exists at destination
	 *     overwriteSame : override any existing file at destination
	 * 
	 * @param Array $params
	 * @return Status
	 */
	abstract public function store( array $params );

	/**
	 * Copy a file from one storage path to another in the backend.
	 * Do not call this function from places outside FileBackend and FileOp.
	 * $params include:
	 *     source        : source storage path
	 *     dest          : destination storage path
	 *     overwriteDest : do nothing and pass if an identical file exists at destination
	 *     overwriteSame : override any existing file at destination
	 * 
	 * @param Array $params
	 * @return Status
	 */
	abstract public function copy( array $params );

	/**
	 * Copy a file from one storage path to another in the backend.
	 * This can be left as a dummy function as long as hasMove() returns false.
	 * Do not call this function from places outside FileBackend and FileOp.
	 * $params include:
	 *     source        : source storage path
	 *     dest          : destination storage path
	 *     overwriteDest : do nothing and pass if an identical file exists at destination
	 *     overwriteSame : override any existing file at destination
	 * 
	 * @param Array $params
	 * @return Status
	 */
	abstract public function move( array $params );

	/**
	 * Delete a file at the storage path.
	 * Do not call this function from places outside FileBackend and FileOp.
	 * $params include:
	 *     source              : source storage path
	 *     ignoreMissingSource : don't return an error if the file does not exist
	 * 
	 * @param Array $params
	 * @return Status
	 */
	abstract public function delete( array $params );

	/**
	 * Combines files from severals storage paths into a new file in the backend.
	 * Do not call this function from places outside FileBackend and FileOp.
	 * $params include:
	 *     source        : source storage path
	 *     dest          : destination storage path
	 *     overwriteDest : do nothing and pass if an identical file exists at destination
	 *     overwriteSame : override any existing file at destination
	 * 
	 * @param Array $params
	 * @return Status
	 */
	abstract public function concatenate( array $params );

	/**
	 * Whether this backend implements move() and is applies to a potential
	 * move from one storage path to another. No backends hits are required.
	 * For example, moving objects accross containers may not be supported.
	 * Do not call this function from places outside FileBackend and FileOp.
	 * $params include:
	 *     source : source storage path
	 *     dest   : destination storage path
	 *
	 * @param Array $params
	 * @return bool
	 */
	abstract public function canMove( array $params );

	/**
	 * Check if a file exits at a storage path in the backend.
	 * Do not call this function from places outside FileBackend and FileOp.
	 * $params include:
	 *     source : source storage path
	 * 
	 * @param Array $params
	 * @return bool
	 */
	abstract public function fileExists( array $params );

	/**
	 * Get the properties of the file that exists at a storage path in the backend
	 * $params include:
	 *     source : source storage path
	 * 
	 * @param Array $params
	 * @return Array|null Gives null if the file does not exist
	 */
	abstract public function getFileProps( array $params );

	/**
	 * Stream the file that exists at a storage path in the backend.
	 * Appropriate HTTP headers (Status, Content-Type, Content-Length)
	 * must be sent if streaming began, while none should be sent otherwise.
	 * Implementations should flush the output buffer before sending data.
	 * $params include:
	 *     source : source storage path
	 * 
	 * @param Array $params
	 * @return Status
	 */
	abstract public function streamFile( array $params );

	/**
	 * Get an iterator to list out all object files under a storage directory.
	 * Results should be storage paths relative to the given directory.
	 * If the directory is of the form "mwstore://container", then all items
	 * in the container should be listed. If of the form "mwstore://container/dir",
	 * then all items under that container directory should be listed.
	 * $params include:
	 *     directory : storage path directory.
	 *
	 * @return Iterator|Array
	 */
	abstract public function getFileList( array $params );

	/**
	 * Get a local copy on disk of the file at a storage path in the backend
	 * $params include:
	 *     source : source storage path
	 * 
	 * @param Array $params
	 * @return TempLocalFile|null Temporary file or null on failure
	 */
	abstract public function getLocalCopy( array $params );

	/**
	 * Lock the files at the given storage paths in the backend.
	 * This should either lock all the files or none (on failure).
	 * Do not call this function from places outside FileBackend and FileOp.
	 * 
	 * @param $sources Array Source storage paths
	 * @return Status
	 */
	final public function lockFiles( array $paths ) {
		return $this->lockManager->lock( $paths );
	}

	/**
	 * Unlock the files at the given storage paths in the backend.
	 * Do not call this function from places outside FileBackend and FileOp.
	 * 
	 * @param $sources Array Source storage paths
	 * @return Status
	 */
	final public function unlockFiles( array $paths ) {
		return $this->lockManager->unlock( $paths );
	}
}

/**
 * Base class for all single-write backends
 */
abstract class FileBackend extends FileBackendBase {
	function canMove( array $params ) {
		return false; // not implemented
	}

	function move( array $params ) {
		throw new MWException( "This function is not implemented." );
	}

	/**
	 * Get the list of supported operations and their corresponding FileOp classes.
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

	/**
	 * Return a list of FileOp objects from a list of operations.
	 * An exception is thrown if an unsupported operation is requested.
	 * 
	 * @param Array $ops Same format as doOperations()
	 * @return Array
	 * @throws MWException
	 */
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
				// Append the FileOp class
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

		// Build up a list of files to lock...
		$filesToLock = array();
		foreach ( $performOps as $index => $fileOp ) {
			$filesToLock = array_merge( $filesToLock, $fileOp->storagePathsToLock() );
		}
		$filesToLock = array_unique( $filesToLock ); // avoid warnings

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

	/**
	 * Split a storage path (e.g. "mwstore://backend/container/path/to/object")
	 * into a container name and a full object name within that container.
	 *
	 * @param $storagePath string
	 * @return Array (container, object name) or (null, null) if path is invalid
	 */
	final protected function resolveVirtualPath( $storagePath ) {
		if ( strpos( $storagePath, 'mwstore://' ) === 0 ) {
			$m = explode( '/', substr( $storagePath, 10 ), 3 );
			if ( count( $m ) == 3 ) {
				list( $backend, $container, $relPath ) = $m;
				if ( $backend === $this->name ) { // sanity
					$relPath = $this->resolveContainerPath( $container, $relPath );
					if ( $relPath !== null ) {
						return array( $container, $relPath ); // (container, path)
					}
				}
			}
		}
		return array( null, null );
	}

	/**
	 * Resolve a storage path relative to a particular container.
	 * This is for internal use for backends, such as encoding or
	 * perhaps getting absolute paths (e.g. file system based backends).
	 *
	 * @param $container string
	 * @param $relStoragePath string
	 * @return string|null Null if path is not valid
	 */
	protected function resolveContainerPath( $container, $relStoragePath ) {
		return $relStoragePath;
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
