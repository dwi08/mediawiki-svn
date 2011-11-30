<?php
/**
 * @file
 * @ingroup FileRepo
 * @ingroup FileBackend
 */

/**
 * Class for a file-system based file backend.
 * Status messages should avoid mentioning the internal FS paths.
 * Likewise, error suppression should be used to path disclosure.
 *
 * @ingroup FileRepo
 * @ingroup FileBackend
 */
class FSFileBackend extends FileBackend {
	/** @var Array Map of container names to paths */
	protected $containerPaths = array();
	protected $fileMode; // file permission mode

	function __construct( array $config ) {
		parent::__construct( $config );
		$this->containerPaths = (array)$config['containerPaths'];
		$this->fileMode = isset( $config['fileMode'] )
			? $config['fileMode']
			: 0644;
	}

	protected function resolveContainerPath( $container, $relStoragePath ) {
		// Get absolute path given the container base dir
		if ( isset( $this->containerPaths[$container] ) ) {
			return $this->containerPaths[$container] . "/{$relStoragePath}";
		}
		return null;
	}

	function store( array $params ) {
		$status = Status::newGood();

		list( $c, $dest ) = $this->resolveStoragePath( $params['dest'] );
		if ( $dest === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dest'] );
			return $status;
		}
		if ( is_file( $dest ) ) {
			if ( isset( $params['overwriteDest'] ) ) {
				wfSuppressWarnings();
				$ok = unlink( $dest );
				wfRestoreWarnings();
				if ( !$ok ) {
					$status->fatal( 'backend-fail-delete', $params['dest'] );
					return $status;
				}
			} else {
				$status->fatal( 'backend-fail-alreadyexists', $params['dest'] );
				return $status;
			}
		} else {
			if ( !wfMkdirParents( dirname( $dest ) ) ) {
				$status->fatal( 'directorycreateerror', $param['dest'] );
				return $status;
			}
		}

		wfSuppressWarnings();
		$ok = copy( $params['source'], $dest );
		wfRestoreWarnings();
		if ( !$ok ) {
			$status->fatal( 'backend-fail-copy', $params['source'], $params['dest'] );
			return $status;
		}

		$this->chmod( $dest );

		return $status;
	}

	function copy( array $params ) {
		return $this->store( $params ); // both source and dest are on FS
	}

	function canMove( array $params ) {
		return true;
	}

	function move( array $params ) {
		$status = Status::newGood();

		list( $c, $source ) = $this->resolveStoragePath( $params['source'] );
		if ( $source === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['source'] );
			return $status;
		}
		list( $c, $dest ) = $this->resolveStoragePath( $params['dest'] );
		if ( $dest === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dest'] );
			return $status;
		}

		if ( file_exists( $dest ) ) {
			if ( isset( $params['overwriteDest'] ) ) {
				// Windows does not support moving over existing files
				if ( wfIsWindows() ) {
					wfSuppressWarnings();
					$ok = unlink( $dest );
					wfRestoreWarnings();
					if ( !$ok ) {
						$status->fatal( 'backend-fail-delete', $params['dest'] );
						return $status;
					}
				}
			} else {
				$status->fatal( 'backend-fail-alreadyexists', $params['dest'] );
				return $status;
			}
		} else {
			if ( !wfMkdirParents( dirname( $dest ) ) ) {
				$status->fatal( 'directorycreateerror', $param['dest'] );
				return $status;
			}
		}

		wfSuppressWarnings();
		$ok = rename( $source, $dest );
		wfRestoreWarnings();
		if ( !$ok ) {
			$status->fatal( 'backend-fail-move', $params['source'], $params['dest'] );
			return $status;
		}

		return $status;
	}

	function delete( array $params ) {
		$status = Status::newGood();

		list( $c, $source ) = $this->resolveStoragePath( $params['source'] );
		if ( $source === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['source'] );
			return $status;
		}

		if ( !file_exists( $source ) ) {
			if ( empty( $params['ignoreMissingSource'] ) ) {
				$status->fatal( 'backend-fail-delete', $params['source'] );
			}
			return $status; // do nothing; either OK or bad status
		}

		wfSuppressWarnings();
		$ok = unlink( $source );
		wfRestoreWarnings();
		if ( !$ok ) {
			$status->fatal( 'backend-fail-delete', $params['source'] );
			return $status;
		}

		return $status;
	}

	function concatenate( array $params ) {
		$status = Status::newGood();

		list( $c, $dest ) = $this->resolveStoragePath( $params['dest'] );
		if ( $dest === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dest'] );
			return $status;
		}

		// Check if the destination file exists and we can't handle that
		$destExists = file_exists( $dest );
		if ( $destExists && empty( $params['overwriteDest'] ) ) {
			$status->fatal( 'backend-fail-alreadyexists', $params['dest'] );
			return $status;
		}

		// Create a new temporary file...
		wfSuppressWarnings();
		$tmpPath = tempnam( wfTempDir(), 'concatenate' );
		wfRestoreWarnings();
		if ( $tmpPath === false ) {
			$status->fatal( 'backend-fail-createtemp' );
			return $status;
		}

		// Build up that file using the source chunks (in order)...
		wfSuppressWarnings();
		$tmpHandle = fopen( $tmpPath, 'a' );
		wfRestoreWarnings();
		if ( $tmpHandle === false ) {
			$status->fatal( 'backend-fail-opentemp', $tmpPath );
			return $status;
		}
		foreach ( $params['sources'] as $virtualSource ) {
			list( $c, $source ) = $this->resolveStoragePath( $virtualSource );
			if ( $source === null ) {
				$status->fatal( 'backend-fail-invalidpath', $virtualSource );
				return $status;
			}
			// Load chunk into memory (it should be a small file)
			$chunk = file_get_contents( $source );
			if ( $chunk === false ) {
				$status->fatal( 'backend-fail-read', $virtualSource );
				return $status;
			}
			// Append chunk to file (pass chunk size to avoid magic quotes)
			if ( !fwrite( $tmpHandle, $chunk, count( $chunk ) ) ) {
				$status->fatal( 'backend-fail-writetemp', $tmpPath );
				return $status;
			}
		}
		wfSuppressWarnings();
		if ( !fclose( $tmpHandle ) ) {
			$status->fatal( 'backend-fail-closetemp', $tmpPath );
			return $status;
		}
		wfRestoreWarnings();

		// Handle overwrite behavior of file destination if applicable.
		// Note that we already checked if no overwrite params were set above.
		if ( $destExists ) {
			// Windows does not support moving over existing files
			if ( wfIsWindows() ) {
				wfSuppressWarnings();
				$ok = unlink( $dest );
				wfRestoreWarnings();
				if ( !$ok ) {
					$status->fatal( 'backend-fail-delete', $params['dest'] );
					return $status;
				}
			}
		} else {
			// Make sure destination directory exists
			if ( !wfMkdirParents( dirname( $dest ) ) ) {
				$status->fatal( 'directorycreateerror', $param['dest'] );
				return $status;
			}
		}

		// Rename the temporary file to the destination path
		wfSuppressWarnings();
		$ok = rename( $tmpPath, $dest );
		wfRestoreWarnings();
		if ( !$ok ) {
			$status->fatal( 'backend-fail-move', $tmpPath, $params['dest'] );
			return $status;
		}

		$this->chmod( $dest );

		return $status;
	}

	function create( array $params ) {
		$status = Status::newGood();

		list( $c, $dest ) = $this->resolveStoragePath( $params['dest'] );
		if ( $dest === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dest'] );
			return $status;
		}

		if ( file_exists( $dest ) ) {
			if ( isset( $params['overwriteDest'] ) ) {
				wfSuppressWarnings();
				$ok = unlink( $dest );
				wfRestoreWarnings();
				if ( !$ok ) {
					$status->fatal( 'backend-fail-delete', $param['dest'] );
					return $status;
				}
			} else {
				$status->fatal( 'backend-fail-alreadyexists', $params['dest'] );
				return $status;
			}
		} else {
			if ( !wfMkdirParents( dirname( $dest ) ) ) {
				$status->fatal( 'directorycreateerror', $param['dest'] );
				return $status;
			}
		}

		wfSuppressWarnings();
		$ok = file_put_contents( $dest, $params['content'] );
		wfRestoreWarnings();
		if ( !$ok ) {
			$status->fatal( 'backend-fail-create', $params['dest'] );
			return $status;
		}

		$this->chmod( $dest );

		return $status;
	}

	function prepare( array $params ) {
		$status = Status::newGood();
		list( $c, $dir ) = $this->resolveStoragePath( $params['directory'] );
		if ( $dir === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['directory'] );
			return $status; // invalid storage path
		}
		if ( !wfMkdirParents( $dir ) ) {
			$status->fatal( 'directorycreateerror', $param['directory'] );
			return $status;
		} elseif ( !is_writable( $dir ) ) {
			$status->fatal( 'directoryreadonlyerror', $param['directory'] );
			return $status;
		} elseif ( !is_readable( $dir ) ) {
			$status->fatal( 'directorynotreadableerror', $param['directory'] );
			return $status;
		}
		return $status;
	}

	function secure( array $params ) {
		$status = Status::newGood();
		list( $c, $dir ) = $this->resolveStoragePath( $params['directory'] );
		if ( $dir === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['directory'] );
			return $status; // invalid storage path
		}
		if ( !wfMkdirParents( $dir ) ) {
			$status->fatal( 'directorycreateerror', $param['directory'] );
			return $status;
		}
		// Add a .htaccess file to the root of the deleted zone
		if ( !empty( $params['noAccess'] ) && !file_exists( "{$dir}/.htaccess" ) ) {
			wfSuppressWarnings();
			$ok = file_put_contents( "{$dir}/.htaccess", "Deny from all\n" );
			wfRestoreWarnings();
			if ( !$ok ) {
				$status->fatal( 'backend-fail-create', $params['directory'] . '/.htaccess' );
				return $status;
			}
		}
		// Seed new directories with a blank index.html, to prevent crawling
		if ( !empty( $params['noListing'] ) && !file_exists( "{$dir}/index.html" ) ) {
			wfSuppressWarnings();
			$ok = file_put_contents( "{$dir}/index.html", '' );
			wfRestoreWarnings();
			if ( !$ok ) {
				$status->fatal( 'backend-fail-create', $params['dest'] . '/index.html' );
				return $status;
			}
		}
		return $status;
	}

	function clean( array $params ) {
		$status = Status::newGood();
		list( $c, $dir ) = $this->resolveStoragePath( $params['directory'] );
		if ( $dir === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['directory'] );
			return $status; // invalid storage path
		}
		wfSuppressWarnings();
		if ( is_dir( $dir ) ) {
			rmdir( $dir ); // remove directory if empty
		}
		wfRestoreWarnings();
		return $status;
	}

	function fileExists( array $params ) {
		list( $c, $source ) = $this->resolveStoragePath( $params['source'] );
		if ( $source === null ) {
			return false; // invalid storage path
		}
		return is_file( $source );
	}

	function getHashType() {
		return 'md5';
	}

	function getFileHash( array $params ) {
		list( $c, $source ) = $this->resolveStoragePath( $params['source'] );
		if ( $source === null ) {
			return false; // invalid storage path
		}
		return md5_file( $source );
	}

	function getFileTimestamp( array $params ) {
		list( $c, $source ) = $this->resolveStoragePath( $params['source'] );
		if ( $source === null ) {
			return false; // invalid storage path
		}
		$fsFile = new FSFile( $source );
		return $fsFile->getTimestamp();
	}

	function getFileProps( array $params ) {
		list( $c, $source ) = $this->resolveStoragePath( $params['source'] );
		if ( $source === null ) {
			return FSFile::placeholderProps(); // invalid storage path
		}
		$fsFile = new FSFile( $source );
		return $fsFile->getProps();
	}

	function getFileList( array $params ) {
		list( $c, $dir ) = $this->resolveStoragePath( $params['directory'] );
		if ( $dir === null ) { // invalid storage path
			return array(); // empty result
		}
		return new FSFileIterator( $dir );
	}

	function streamFile( array $params ) {
		$status = Status::newGood();

		list( $c, $source ) = $this->resolveStoragePath( $params['source'] );
		if ( $source === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['source'] );
			return $status;
		}

		$ok = StreamFile::stream( $source, array(), false );
		if ( !$ok ) {
			$status->fatal( 'backend-fail-stream', $params['source'] );
			return $status;
		}

		return $status;
	}

	function getLocalCopy( array $params ) {
		list( $c, $source ) = $this->resolveStoragePath( $params['source'] );
		if ( $source === null ) {
			return null;
		}

		// Get source file extension
		$i = strrpos( $source, '.' );
		$ext = strtolower( $i ? substr( $source, $i + 1 ) : '' );
		// Create a new temporary file...
		wfSuppressWarnings();
		$initialTmpPath = tempnam( wfTempDir(), 'localcopy' );
		wfRestoreWarnings();
		if ( $initialTmpPath === false ) {
			return null;
		}
		// Apply the original extension
		$tmpPath = "{$initialTmpPath}.{$ext}";
		if ( !rename( $initialTmpPath, $tmpPath ) ) {
			return null;
		}

		// Copy the source file over the temp file
		wfSuppressWarnings();
		$ok = copy( $source, $tmpPath );
		wfRestoreWarnings();
		if ( !$ok ) {
			return null;
		}

		$this->chmod( $tmpPath );

		return new TempFSFile( $tmpPath );
	}

	/**
	 * Chmod a file, suppressing the warnings
	 *
	 * @param $path string Absolute file system path
	 * @return bool Success
	 */
	protected function chmod( $path ) {
		wfSuppressWarnings();
		$ok = chmod( $path, $this->fileMode );
		wfRestoreWarnings();

		return $ok;
	}
}

/**
 * Semi-DFS based file browsing iterator. The highest number of file handles
 * open at any given time is proportional to the height of the directory tree.
 */
class FSFileIterator implements Iterator {
	private $directory; // starting directory
	private $itemStart = 0;

	private $position = 0;
	private $currentFile = false;
	private $dirStack = array(); // array of (dir name, dir handle) tuples

	private $loaded = false;

	/**
	 * Get an iterator to list the files under $directory and its subdirectories
	 * @param $directory string
	 */
	public function __construct( $directory ) {
		// Removing any trailing slash
		if ( substr( $this->directory, -1 ) === '/' ) {
			$this->directory = substr( $this->directory, 0, -1 );
		}
		$this->directory = realpath( $directory );
		// Remove internal base directory and trailing slash from results
		$this->itemStart = strlen( $this->directory ) + 1;
	}

	private function load() {
		if ( !$this->loaded ) {
			$this->loaded = true;
			// If we get an invalid directory then the result is an empty list
			if ( is_dir( $this->directory ) ) {
				$handle = opendir( $this->directory );
				if ( $handle ) {
					$this->pushDirectory( $this->directory, $handle );
					$this->currentFile = $this->nextFile();
				}
			}
		}
	}

	function rewind() {
		$this->closeDirectories();
		$this->position = 0;
		$this->currentFile = false;
		$this->loaded = false;
	}

	function current() {
		$this->load();
		// Remove internal base directory and trailing slash from results
		return substr( $this->currentFile, $this->itemStart );
	}

	function key() {
		$this->load();
		return $this->position;
	}

	function next() {
		$this->load();
		++$this->position;
		$this->currentFile = $this->nextFile();
	}

	function valid() {
		$this->load();
		return ( $this->currentFile !== false );
	}

	function nextFile() {
		if ( !$this->currentDirectory() ) {
			return false; // nothing else to scan
		}
		# Next file under the current directory (and subdirectories).
		# This may advance the current directory down to a descendent.
		# The current directory is set to the parent if nothing is found.
		$nextFile = $this->nextFileBelowCurrent();
		if ( $nextFile !== false ) {
			return $nextFile;
		} else {
			# Scan the higher directories
			return $this->nextFile();
		}
	}

	function nextFileBelowCurrent() {
		list( $dir, $handle ) = $this->currentDirectory();
		while ( false !== ( $file = readdir( $handle ) ) ) {
			// Exclude '.' and '..' as well .svn or .lock type files
			if ( $file[0] !== '.' ) {
				$path = "{$dir}/{$file}";
				// If the first thing we find is a file, then return it.
				// If the first thing we find is a directory, then return
				// the first file that it contains (via recursion).
				// We exclude symlink dirs in order to avoid cycles.
				if ( is_dir( $path ) && !is_link( $path ) ) {
					$subHandle = opendir( $path );
					if ( $subHandle ) {
						$this->pushDirectory( $path, $subHandle );
						$nextFile = $this->nextFileBelowCurrent();
						if ( $nextFile !== false ) {
							return $nextFile; // found the next one!
						}
					}
				} elseif ( is_file( $path ) ) {
					return $path; // found the next one!
				}
			}
		}
		# If we didn't find anything else in this directory,
		# then back out so we scan the other higher directories
		$this->popDirectory();
		return false;
	}

	private function currentDirectory() {
		if ( !count( $this->dirStack ) ) {
			return false;
		}
		return $this->dirStack[count( $this->dirStack ) - 1];
	}

	private function popDirectory() {
		if ( count( $this->dirStack ) ) {
			list( $dir, $handle ) = array_pop( $this->dirStack );
			closedir( $handle );
		}
	}

	private function pushDirectory( $dir, $handle ) {
		$this->dirStack[] = array( $dir, $handle );
	}

	private function closeDirectories() {
		foreach ( $this->dirStack as $set ) {
			list( $dir, $handle ) = $set;
			closedir( $handle );
		}
		$this->dirStack = array();
	}

	function __destruct() {
		$this->closeDirectories();
	}
}
