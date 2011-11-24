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
	 * Prepare a storage path for usage. This will create containers
	 * that don't yet exists or, on FS backends, create parent directories.
	 * Do not call this function from places outside FileBackend and FileOp.
	 * $params include:
	 *     directory : destination storage path
	 * 
	 * @param Array $params
	 * @return Status
	 */
	abstract public function prepare( array $params );

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
	 * Create a file in the backend with the given contents.
	 * Do not call this function from places outside FileBackend and FileOp.
	 * $params include:
	 *     contents      : the raw file contents
	 *     dest          : destination storage path
	 *     overwriteDest : do nothing and pass if an identical file exists at destination
	 *     overwriteSame : override any existing file at destination
	 * 
	 * @param Array $params
	 * @return Status
	 */
	abstract public function create( array $params );

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
			'concatenate' => 'FileConcatenateOp',
			'create'      => 'FileCreateOp'
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
