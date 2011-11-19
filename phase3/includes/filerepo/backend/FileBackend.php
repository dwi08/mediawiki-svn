<?php
/**
 * Base class for all file backend classes
 *
 * @file
 * @ingroup FileRepo
 */

/**
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
     *		array(
	 *					'operation' => 'store',
     *					'src'       => '/tmp/uploads/picture.png',
     *					'dest'      => 'zone/uploadedFilename.png'
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
	 * Do not call these from places other than FileOp.
	 * $params include:
	 *		source        : source path on disk
	 *		dest          : destination storage path
	 *		overwriteDest : do nothing and pass if an identical file exists at destination
	 *		overwriteSame : override any existing file at destination
	 * 
     * @param Array $params
	 * @return Status
     */
    public function store( array $params );

	/**
     * Copy a file from one storage path to another in the backend.
	 * Do not call these from places other than FileOp.
	 * $params include:
	 *		source        : source storage path
	 *		dest          : destination storage path
	 *		overwriteDest : do nothing and pass if an identical file exists at destination
	 *		overwriteSame : override any existing file at destination
	 * 
	 * @param Array $params 
	 * @return Status
	 */
    public function copy( array $params );

	/**
     * Delete a file at the storage path.
	 * Do not call these from places other than FileOp.
	 * $params include:
	 *		source              : source storage path
	 *		ignoreMissingSource : don't return an error if the file does not exist
	 * 
	 * @param Array $params 
	 * @return Status
	 */
    public function delete( array $params );

	/**
     * Combines files from severals storage paths into a new file in the backend.
	 * Do not call these from places other than FileOp.
	 * $params include:
	 *		source        : source storage path
	 *		dest          : destination storage path
	 *		overwriteDest : do nothing and pass if an identical file exists at destination
	 *		overwriteSame : override any existing file at destination
	 * 
	 * @param Array $params 
	 * @return Status
	 */
    public function concatenate( array $params );

	/**
     * Check if a file exits at a storage path in the backend.
	 * Do not call these from places other than FileOp.
	 * $params include:
	 *		source : source storage path
	 * 
	 * @param Array $params 
	 * @return bool
	 */
    public function fileExists( array $params );

	/**
     * Get the properties of the file that exists at a storage path in the backend
	 * $params include:
	 *		source : source storage path
	 * 
	 * @param Array $params 
	 * @return Array|null Gives null if the file does not exist
	 */
    public function getFileProps( array $params );

	/**
     * Get a local copy on dist of the file at a storage path in the backend
	 * $params include:
	 *		source : source storage path
	 * 
	 * @param Array $params 
	 * @return string|null Path to temporary file or null on failure
	 */
    public function getLocalCopy( array $params );

	/**
     * Lock the files at the given storage paths in the backend.
	 * Do not call these from places other than FileOp.
	 * 
	 * @param $sources Array Source storage paths
	 * @return Status
	 */
	public function lockFiles( array $sources );

	/**
     * Unlock the files at the given storage paths in the backend.
	 * Do not call these from places other than FileOp.
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
	 * 
	 * @param $config Array
	 */
	public function __construct( array $config ) {
		$this->name = $config['name'];
		$this->lockManager = $config['lockManger'];
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
			'delete'      => 'FileDeleteOp',
			'move'        => 'FileMoveOp',
			'concatenate' => 'FileConcatenateOp'
		);
	}

	final public function getOperations( array $ops ) {
		$supportedOps = $this->supportedOperations();

		$performOps = array(); // array of FileOp objects
		//// Build up ordered array of FileOps...
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
			$tStatus = $transaction->attempt();
			if ( !$tStatus->isOk() ) {
				// merge $tStatus with $status
				// Revert everything done so far and error out
				$tStatus = $this->revertOperations( $performOps, $index );
				// merge $tStatus with $status
				return $status;
			}
		}
		// Finish each operation...
		foreach ( $performOps as $index => $transaction ) {
			$tStatus = $transaction->finish();
			// merge $tStatus with $status
		}
		return $status;
	}

	/**
	 * Revert a series of operations in reverse order.
	 * If $index is passed, then we revert all items in
	 * $ops from 0 to $index (inclusive).
	 * 
	 * @param $ops Array List of FileOp objects
	 * @param $index integer
	 * @return Status
	 */
	private function revertOperations( array $ops, $index = false ) {
		$status = Status::newGood();
		$pos = ( $index !== false )
			? $index // use provided index
			: $pos = count( $ops ) - 1; // last element (or -1)
		while ( $pos >= 0 ) {
			$tStatus = $ops[$pos]->revert();
			// merge $tStatus with $status
			$pos--;
		}
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
class FileOp {
	protected $state;
	/** $var Array */
	protected $params;
	/** $var FileBackend */
	protected $backend;

	const STATE_NEW = 1;
	const STATE_ATTEMPTED = 2;
	const STATE_DONE = 3;

	/**
	 * Build a new file operation transaction
	 * @params $backend FileBackend
	 * @params $params Array
	 */
	final public function __construct( FileBackend $backend, array $params ) {
		$this->backend = $backend;
		$this->params = $params;
		$this->state = self::STATE_NEW;
		$this->initialize();
	}

	/**
	 * Set any custom fields on construction
	 * @return void
	 */
	protected function initialize() {}

	/**
	 * Get a list of storage paths to lock for this operation
	 * @return Array
	 */
	protected function storagePathsToLock() {
		return array();
	}

	/**
	 * Attempt the operation, maintaining the source file
	 * @return Status
	 */
	final public function attempt() {
		if ( $this->state !== self::STATE_NEW ) {
			throw new MWException( "Cannot attempt operation called twice." );
		}
		$this->state = self::STATE_ATTEMPTED;
		$status = $this->setLocks();
		if ( $status->isOk() ) {
			$status = $this->doAttempt();
		}
		return $status;
	}

	/**
	 * Revert the operation
	 * @return Status
	 */
	final public function revert() {
		if ( $this->state !== self::STATE_ATTEMPTED ) {
			throw new MWException( "Cannot rollback an unstarted or finished operation." );
		}
		$this->state = self::STATE_DONE;
		$status = $this->doRevert();
		$this->unsetLocks();
		return $status;
	}

	/**
	 * Finish the operation, altering original files
	 * @return Status
	 */
	final public function finish() {
		if ( $this->state !== self::STATE_ATTEMPTED ) {
			throw new MWException( "Cannot cleanup an unstarted or finished operation." );
		}
		$this->state = self::STATE_DONE;
		$status = $this->doFinish();
		$this->unsetLocks();
		return $status;
	}

	/**
	 * Try to lock any files before changing them
	 * @return Status
	 */
	private function setLocks() {
		$status = Status::newGood();
		return $this->backend->lockFiles( $this->storagePathsToLock() );
		
		
		$lockedFiles = array(); // files actually locked
		foreach ( $this->storagePathsToLock() as $file ) {
			$lockStatus = $this->backend->lockFile( $file );
			if ( $lockStatus->isOk() ) {
				$lockedFiles[] = $file;
			} else {
				foreach ( $lockedFiles as $file ) {
					$this->backend->unlockFile( $file );
				}
				return $lockStatus; // abort
			}
		}
		return $status;
	}

	/**
	 * Try to unlock any files that this locked
	 * @return Status
	 */
	private function unsetLocks() {
		$status = Status::newGood();
		return $this->backend->unlockFiles( $this->storagePathsToLock() );
		
		foreach ( $this->storagePathsToLock() as $file ) {
			$lockStatus = $this->backend->unlockFile( $file );
			if ( !$lockStatus->isOk() ) {
				// append $lockStatus to $status
			}
		}
		return $status;
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
 * $params include:
 *		source        : source path on disk
 *		dest          : destination storage path
 *		overwriteDest : do nothing and pass if an identical file exists at destination
 *		overwriteSame : override any existing file at destination
 */
class FileStoreOp extends FileOp {
	protected $tmpDestPath; // temp copy of existing destination file

	function doAttempt() {
		// Check if a file already exists at the destination
		if ( $this->backend->fileExists( $this->params['dest'] ) ) {
			if ( $this->params['overwriteDest'] ) {
				$this->tmpDestPath = $this->getLocalCopy( $this->params['dest'] );
				if ( $this->tmpDestPath === null ) {
					// error out	
				}
			}
		}
		$status = $this->backend->store( $this->params );
		return $status;
	}

	function doRevert() {
		$status = Status::newGood();
		// Remove the file saved to the destination
		$params = array( 'source' => $this->params['dest'] );
		$subStatus = $this->backend->delete( $params );
		// merge $subStatus with $status
		// Restore any file that was at the destination
		if ( $this->tmpDestPath !== null ) {
			$params = array(
				'source' => $this->tmpDestPath,
				'dest'   => $this->params['dest']
			);
			$subStatus = $this->backend->store( $params );
			// merge $subStatus with $status
		}
		return $status;
	}

	function storagePathsToLock() {
		return array( $this->params['dest'] );
	}
}

/**
 * Copy a file from one storage path to another in the backend.
 * $params include:
 *		source        : source storage path
 *		dest          : destination storage path
 *		overwriteDest : do nothing and pass if an identical file exists at destination
 *		overwriteSame : override any existing file at destination
 */
class FileCopyOp extends FileOp {
	protected $tmpDestPath; // temp copy of existing destination file

	function doAttempt() {
		// Check if a file already exists at the destination
		if ( $this->backend->fileExists( $this->params['dest'] ) ) {
			if ( $this->params['overwriteDest'] ) {
				$this->tmpDestPath = $this->getLocalCopy( $this->params['dest'] );
				if ( $this->tmpDestPath === null ) {
					// error out	
				}
			}
		}
		$status = $this->backend->copy( $this->params );
		return $status;
	}

	function doRevert() {
		$status = Status::newGood();
		// Remove the file saved to the destination
		$params = array( 'source' => $this->params['dest'] );
		$subStatus = $this->backend->delete( $params );
		// merge $subStatus with $status
		// Restore any file that was at the destination
		if ( $this->tmpDestPath !== null ) {
			$params = array(
				'source' => $this->tmpDestPath,
				'dest'   => $this->params['dest']
			);
			$subStatus = $this->backend->store( $params );
			// merge $subStatus with $status
		}
		return $status;
	}

	function storagePathsToLock() {
		return array( $this->params['source'], $this->params['dest'] );
	}
}

/**
 * Move a file from one storage path to another in the backend.
 * $params include:
 *		source        : source storage path
 *		dest          : destination storage path
 *		overwriteDest : do nothing and pass if an identical file exists at destination
 *		overwriteSame : override any existing file at destination
 */
class FileMoveOp extends FileCopyOp {
	function doFinish() {
		$params = array( 'source' => $this->params['source'] );
		$status = $this->backend->delete( $params );
		return $status;
	}
}

/**
 * Delete a file at the storage path.
 * $params include:
 *		source              : source storage path
 *		ignoreMissingSource : don't return an error if the file does not exist
 */
class FileDeleteOp extends FileOp {
	function doFinish() {
		$status = $this->fileBackend->delete( $this->params );
		return $status;
	}

	function storagePathsToLock() {
		return array( $this->params['source'] );
	}
}

/**
 * Combines files from severals storage paths into a new file in the backend.
 * $params include:
 *		sources       : ordered source storage paths (e.g. chunk1,chunk2,...)
 *		dest          : destination storage path
 *		overwriteDest : do nothing and pass if an identical file exists at destination
 *		overwriteSame : override any existing file at destination
 */
class FileConcatenateOp extends FileOp {
	protected $tmpDestPath; // temp copy of existing destination file

	function doAttempt() {
		// Check if a file already exists at the destination
		if ( $this->backend->fileExists( $this->params['dest'] ) ) {
			if ( $this->params['overwriteDest'] ) {
				$this->tmpDestPath = $this->getLocalCopy( $this->params['dest'] );
				if ( $this->tmpDestPath === null ) {
					// error out	
				}
			}
		}
		$status = $this->backend->concatenate( $this->params );
		return $status;
	}

	function doRevert() {
		$status = Status::newGood();
		// Remove the file saved to the destination
		$params = array( 'source' => $this->params['dest'] );
		$subStatus = $this->backend->delete( $params );
		// merge $subStatus with $status
		// Restore any file that was at the destination
		if ( $this->tmpDestPath !== null ) {
			$params = array(
				'source' => $this->tmpDestPath,
				'dest'   => $this->params['dest']
			);
			$subStatus = $this->backend->store( $params );
			// merge $subStatus with $status
		}
		return $status;
	}

	function storagePathsToLock() {
		return array_merge( $this->params['sources'], $this->params['dest'] );
	}
}
