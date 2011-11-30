<?php
/**
 * @file
 * @ingroup FileRepo
 * @ingroup FileBackend
 */

/**
 * Base class for all file backend classes (including multi-write backends).
 * This class defines the methods as abstract that subclasses must implement.
 *
 * Outside callers can assume that all backends will have these functions.
 * 
 * All "storage paths" are of the format "mwstore://backend/container/path".
 * The paths use typical file system notation, though any particular backend may
 * not actually be using a local filesystem. Therefore, the paths are only virtual.
 *
 * Methods should avoid throwing exceptions at all costs.
 * As a corollary, external dependencies should be kept to a minimum.
 *
 * @ingroup FileRepo
 * @ingroup FileBackend
 */
abstract class FileBackendBase {
	protected $name; // unique backend name
	protected $wikiId; // unique wiki name
	/** @var LockManager */
	protected $lockManager;

	/**
	 * Build a new object from configuration.
	 * This should only be called from within FileRepo classes.
	 * $config includes:
	 *     'name'        : The name of this backend
	 *     'wikiId'      : Prefix to container names that is unique to this wiki
	 *     'lockManager' : The file lock manager to use
	 * 
	 * @param $config Array
	 */
	public function __construct( array $config ) {
		$this->name = $config['name'];
		$this->wikiId = isset( $config['wikiId'] )
			? $config['wikiId']
			: wfWikiID();
		$this->lockManager = LockManagerGroup::singleton()->get( $config['lockManager'] );
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
	 *         'op'        => 'store',
	 *         'source'    => '/tmp/uploads/picture.png',
	 *         'dest'      => 'mwstore://container/uploadedFilename.png'
	 *     )
	 * );
	 * </code>
	 * If any serious errors occur, the operations will be rolled back.
	 * However, the 'ignoreErrors' parameter can be used on any operation to ignore errors.
	 * 
	 * @param Array $ops Array of arrays containing N operations to execute IN ORDER
	 * @return Status
	 */
	abstract public function doOperations( array $ops );

	/**
	 * Same as doOperations() except it takes a single operation array
	 *
	 * @param $op Array
	 * @return Status
	 */
	final public function doOperation( $op ) {
		return $this->doOperations( array( $op ) );
	}

	/**
	 * Prepare a storage path for usage. This will create containers
	 * that don't yet exists or, on FS backends, create parent directories.
	 * Do not call this function from places outside FileBackend and FileOp.
	 * $params include:
	 *     directory : storage directory
	 * 
	 * @param Array $params
	 * @return Status
	 */
	abstract public function prepare( array $params );

	/**
	 * Take measures to block web access to a directory.
	 * This is not guaranteed to actually do anything.
	 * Do not call this function from places outside FileBackend and FileOp.
	 * $params include:
	 *     directory : storage directory
	 *     noAccess  : try to deny file access
	 *     noListing : try to deny file listing
	 * 
	 * @param Array $params
	 * @return Status
	 */
	abstract public function secure( array $params );

	/**
	 * Clean up an empty storage directory.
	 * On FS backends, the directory will be deleted. Others may do nothing.
	 * Do not call this function from places outside FileBackend and FileOp.
	 * $params include:
	 *     directory : storage directory
	 * 
	 * @param Array $params
	 * @return Status
	 */
	abstract public function clean( array $params );

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
	 * Get a hash of the file at a storage path in the backend.
	 * Typically this will be a SHA-1 hash, MD5 hash, or something similar.
	 * $params include:
	 *     source : source storage path
	 * 
	 * @param Array $params
	 * @return string|false Hash string or false on failure
	 */
	abstract public function getFileHash( array $params );

	/**
	 * Get the format of the hash that getFileHash() uses
	 *
	 * @return string (md5, sha1, unknown, ...)
	 */
	abstract public function getHashType();

	/**
	 * Get the last-modified timestamp of the file at a storage path.
	 * $params include:
	 *     source : source storage path
	 * 
	 * @param Array $params
	 * @return string|false TS_MW timestamp or false on failure
	 */
	abstract public function getFileTimestamp( array $params );

	/**
	 * Get the properties of the file at a storage path in the backend.
	 * Returns FSFile::placeholderProps() on failure.
	 * $params include:
	 *     source : source storage path
	 * 
	 * @param Array $params
	 * @return Array
	 */
	abstract public function getFileProps( array $params );

	/**
	 * Stream the file at a storage path in the backend.
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
	 * If the directory is of the form "mwstore://container", then all items in
	 * the container should be listed. If of the form "mwstore://container/dir",
	 * then all items under that container directory should be listed.
	 * Results should be storage paths relative to the given directory.
	 * $params include:
	 *     directory : storage path directory.
	 *
	 * @return Iterator|Array
	 */
	abstract public function getFileList( array $params );

	/**
	 * Get a local copy on disk of the file at a storage path in the backend.
	 * The temporary copy should have the same file extension as the source.
	 * $params include:
	 *     source : source storage path
	 * 
	 * @param Array $params
	 * @return TempFSFile|null Temporary file or null on failure
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
 * Base class for all single-write backends.
 * This class defines the methods as abstract that subclasses must implement.
 *
 * @ingroup FileRepo
 * @ingroup FileBackend
 */
abstract class FileBackend extends FileBackendBase {
	/**
	 * Store a file into the backend from a file on disk.
	 * Do not call this function from places outside FileBackend and FileOp.
	 * $params include:
	 *     source        : source path on disk
	 *     dest          : destination storage path
	 *     overwriteDest : do nothing and pass if an identical file exists at destination
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
	 * 
	 * @param Array $params
	 * @return Status
	 */
	public function move( array $params ) {
		throw new MWException( "This function is not implemented." );
	}

	/**
	 * Delete a file at the storage path.
	 * Do not call this function from places outside FileBackend and FileOp.
	 * $params include:
	 *     source              : source storage path
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
	 * 
	 * @param Array $params
	 * @return Status
	 */
	abstract public function create( array $params );

	public function prepare( array $params ) {
		return Status::newGood();
	}

	public function secure( array $params ) {
		return Status::newGood();
	}

	public function clean( array $params ) {
		return Status::newGood();
	}

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
	public function canMove( array $params ) {
		return false; // not implemented
	}

	public function getFileProps( array $params ) {
		$tmpFile = $this->getLocalCopy( $params );
		if ( !$tmpFile ) {
			return FSFile::placeholderProps();
		} else {
			return $tmpFile->getProps();
		}
	}

	/**
	 * Get the list of supported operations and their corresponding FileOp classes.
	 * 
	 * @return Array
	 */
	protected function supportedOperations() {
		return array(
			'store'       => 'StoreFileOp',
			'copy'        => 'CopyFileOp',
			'move'        => 'MoveFileOp',
			'delete'      => 'DeleteFileOp',
			'concatenate' => 'ConcatenateFileOp',
			'create'      => 'CreateFileOp',
			'null'        => 'NullFileOp'
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
			$opName = $operation['op'];
			if ( isset( $supportedOps[$opName] ) ) {
				$class = $supportedOps[$opName];
				// Get params for this operation
				$params = $operation;
				unset( $params['op'] ); // don't need this
				unset( $params['ignoreErrors'] ); // don't need this
				// Append the FileOp class
				$performOps[] = new $class( $this, $params );
			} else {
				throw new MWException( "Operation `$opName` is not supported." );
			}
		}

		return $performOps;
	}

	final public function doOperations( array $ops ) {
		$status = Status::newGood( array() );

		// Build up a list of FileOps...
		$performOps = $this->getOperations( $ops );

		// Build up a list of files to lock...
		$filesToLock = array();
		foreach ( $performOps as $index => $fileOp ) {
			$filesToLock = array_merge( $filesToLock, $fileOp->storagePathsUsed() );
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

	/**
	 * Check if a given path is a mwstore:// path
	 * 
	 * @param $path string
	 * @return bool
	 */
	final public static function isStoragePath( $path ) {
		return ( strpos( $path, 'mwstore://' ) === 0 );
	}

	/**
	 * Split a storage path (e.g. "mwstore://backend/container/path/to/object")
	 * into a backend name, a container name, and a relative object path.
	 *
	 * @param $storagePath string
	 * @return Array (backend, container, rel object) or (null, null, null)
	 */
	final public static function splitStoragePath( $storagePath ) {
		if ( self::isStoragePath( $storagePath ) ) {
			// Note: strlen( 'mwstore://' ) = 10
			$parts = explode( '/', substr( $storagePath, 10 ), 3 );
			if ( count( $parts ) == 3 ) {
				return $parts; // e.g. "backend/container/path"
			} elseif ( count( $parts ) == 2 ) {
				return array( $parts[0], $parts[1], '' ); // e.g. "backend/container" 
			}
		}
		return array( null, null, null );
	}

	/**
	 * Split a storage path (e.g. "mwstore://backend/container/path/to/object")
	 * into a backend name, a container name and an internal relative object name.
	 *
	 * @param $storagePath string
	 * @return Array (container, object name) or (null, null) if path is invalid
	 */
	final protected function resolveStoragePath( $storagePath ) {
		$parts = self::splitStoragePath( $storagePath );
		if ( $parts[0] !== null ) { // either all null or all not null
			list( $backend, $container, $relPath ) = $parts;
			if ( $backend === $this->name ) { // sanity
				$relPath = $this->resolveContainerPath( $container, $relPath );
				if ( $relPath !== null ) {
					$container = $this->fullContainerName( $container );
					return array( $container, $relPath ); // (container, path)
				}
			}
		}
		return array( null, null );
	}

	/**
	 * Get the full container name, including the wiki ID prefix
	 * 
	 * @param $container string
	 * @return string 
	 */
	final protected function fullContainerName( $container ) {
		if ( $this->wikiId != '' ) {
			return "{$this->wikiId}-$container";
		} else {
			return $container;
		}
	}

	/**
	 * Resolve a storage path relative to a particular container.
	 * This is for internal use for backends, such as encoding or
	 * perhaps getting absolute paths (e.g. file system based backends).
	 *
	 * @param $container string
	 * @param $relStoragePath string
	 * @return string|null Path or null if not valid
	 */
	protected function resolveContainerPath( $container, $relStoragePath ) {
		return $relStoragePath;
	}
}
