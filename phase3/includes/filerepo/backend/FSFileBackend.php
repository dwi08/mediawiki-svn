<?php
/**
 * @file
 * @ingroup FileRepo
 */

/**
 * Class for a file-system based file backend
 */
class FSFileBackend extends FileBackend {
	/** @var Array Map of container names to paths */
	protected $containerPaths = array();
	protected $fileMode; // file permission mode

	function __construct( array $config ) {
		$this->name = $config['name'];
		$this->containerPaths = (array)$config['containers'];
		$this->lockManager = $config['lockManger'];
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

		list( $c, $dest ) = $this->resolveVirtualPath( $params['dest'] );
		if ( $dest === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dest'] );
			return $status;
		}

		if ( file_exists( $dest ) ) {
			if ( isset( $params['overwriteDest'] ) ) {
				$ok = unlink( $dest );
				if ( !$ok ) {
					$status->fatal( 'backend-fail-delete', $param['dest'] );
					return $status;
				}
			} elseif ( isset( $params['overwriteSame'] ) ) {
				if ( !$this->filesAreSame( $params['source'], $dest ) ) {
					$status->fatal( 'backend-fail-notsame', $params['source'], $params['dest'] );
				}
				return $status; // do nothing; either OK or bad status
			} else {
				$status->fatal( 'backend-fail-alreadyexists', $params['dest'] );
				return $status;
			}
		} else {
			if ( !wfMkdirParents( $dest ) ) {
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

		list( $c, $source ) = $this->resolveVirtualPath( $params['source'] );
		if ( $source === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['source'] );
			return $status;
		}
		list( $c, $dest ) = $this->resolveVirtualPath( $params['dest'] );
		if ( $dest === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dest'] );
			return $status;
		}

		if ( file_exists( $dest ) ) {
			if ( isset( $params['overwriteDest'] ) ) {
				// Windows does not support moving over existing files
				if ( wfIsWindows() ) {
					$ok = unlink( $dest );
					if ( !$ok ) {
						$status->fatal( 'backend-fail-delete', $params['dest'] );
						return $status;
					}
				}
			} elseif ( isset( $params['overwriteSame'] ) ) {
				if ( !$this->filesAreSame( $source, $dest ) ) {
					$status->fatal( 'backend-fail-notsame', $params['source'], $params['dest'] );
				}
				return $status; // do nothing; either OK or bad status
			} else {
				$status->fatal( 'backend-fail-alreadyexists', $params['dest'] );
				return $status;
			}
		} else {
			if ( !wfMkdirParents( $dest ) ) {
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

		list( $c, $source ) = $this->resolveVirtualPath( $params['source'] );
		if ( $source === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['source'] );
			return $status;
		}

		if ( !file_exists( $source ) ) {
			if ( !$params['ignoreMissingSource'] ) {
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

		list( $c, $dest ) = $this->resolveVirtualPath( $params['dest'] );
		if ( $dest === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dest'] );
			return $status;
		}

		// Check if the destination file exists and we can't handle that
		$destExists = file_exists( $dest );
		if ( $destExists && !$params['overwriteDest'] && !$params['overwriteSame'] ) {
			$status->fatal( 'backend-fail-alreadyexists', $params['dest'] );
			return $status;
		}

		// Create a new temporary file...
		wfSuppressWarnings();
		$tmpPath = tempnam( wfTempDir(), 'file_concatenate' );
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
			list( $c, $source ) = $this->resolveVirtualPath( $virtualSource );
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
			} elseif ( isset( $params['overwriteSame'] ) ) {
				if ( !$this->filesAreSame( $tmpPath, $dest ) ) {
					$status->fatal( 'backend-fail-notsame', $tmpPath, $params['dest'] );
				}
				return $status; // do nothing; either OK or bad status
			}
		} else {
			// Make sure destination directory exists
			if ( !wfMkdirParents( $dest ) ) {
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

		list( $c, $dest ) = $this->resolveVirtualPath( $params['dest'] );
		if ( $dest === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dest'] );
			return $status;
		}

		if ( file_exists( $dest ) ) {
			if ( isset( $params['overwriteDest'] ) ) {
				$ok = unlink( $dest );
				if ( !$ok ) {
					$status->fatal( 'backend-fail-delete', $param['dest'] );
					return $status;
				}
			} elseif ( isset( $params['overwriteSame'] ) ) {
				if ( !$this->fileAndDataAreSame( $dest, $params['content'] ) ) {
					$status->fatal( 'backend-fail-notsame-raw', $params['dest'] );
				}
				return $status; // do nothing; either OK or bad status
			} else {
				$status->fatal( 'backend-fail-alreadyexists', $params['dest'] );
				return $status;
			}
		} else {
			if ( !wfMkdirParents( $dest ) ) {
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
		list( $c, $dir ) = $this->resolveVirtualPath( $params['directory'] );
		if ( $dir === null ) {
			return false; // invalid storage path
		}
		if ( !wfMkdirParents( $dir ) ) {
			$status->fatal( 'directorycreateerror', $param['directory'] );
			return $status;
		}
		if ( !is_writable( $dir ) ) {
			$status->fatal( 'directoryreadonlyerror', $param['directory'] );
			return $status;
		}
		return $status;
	}

	function fileExists( array $params ) {
		list( $c, $source ) = $this->resolveVirtualPath( $params['source'] );
		if ( $source === null ) {
			return false; // invalid storage path
		}
		return file_exists( $source );
	}

	function getHashType() {
		return 'md5';
	}

	function getFileHash( array $params ) {
		list( $c, $source ) = $this->resolveVirtualPath( $params['source'] );
		if ( $source === null ) {
			return false; // invalid storage path
		}
		return md5_file( $source );
	}

	function getFileProps( array $params ) {
		list( $c, $source ) = $this->resolveVirtualPath( $params['source'] );
		if ( $source === null ) {
			return null; // invalid storage path
		}
		return File::getPropsFromPath( $source );
	}

	function getFileList( array $params ) {
		list( $c, $dir ) = $this->resolveVirtualPath( $params['directory'] );
		if ( $dir === null ) { // invalid storage path
			return array(); // empty result
		}
		return new FileIterator( $dir );
	}

	function streamFile( array $params ) {
		$status = Status::newGood();

		list( $c, $source ) = $this->resolveVirtualPath( $params['source'] );
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
		list( $c, $source ) = $this->resolveVirtualPath( $params['source'] );
		if ( $source === null ) {
			return null;
		}

		// Create a new temporary file...
		wfSuppressWarnings();
		$tmpPath = tempnam( wfTempDir(), 'file_localcopy' );
		wfRestoreWarnings();
		if ( $tmpPath === false ) {
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

		return new TempLocalFile( $tmpPath );
	}

	/**
	 * Check if two files are identical
	 *
	 * @param $path1 string Absolute filesystem path
	 * @param $path2 string Absolute filesystem path
	 * @return bool
	 */
	protected function filesAreSame( $path1, $path2 ) {
		return ( // check size first since it's faster
			filesize( $path1 ) === filesize( $path2 ) &&
			sha1_file( $path1 ) === sha1_file( $path2 )
		);
	}

	/**
	 * Check if a file has identical contents as a string
	 *
	 * @param $path string Absolute filesystem path
	 * @param $content string Raw file data
	 * @return bool
	 */
	protected function fileAndDataAreSame( $path, $content ) {
		return ( // check size first since it's faster
			filesize( $path ) === strlen( $content ) &&
			sha1_file( $path ) === sha1( $content )
		);
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
class FileIterator implements Iterator {
	private $directory; // starting directory

	private $position = 0;
	private $currentFile = false;
	private $dirStack = array(); // array of (dir name, dir handle) tuples

	private $loaded = false;

	/**
	 * Get an iterator to list the files under $directory and its subdirectories
	 * @param $directory string
	 */
	public function __construct( $directory ) {
		$this->directory = (string)$directory;
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
		return $this->currentFile;
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
		$set = $this->currentDirectory();
		if ( !$set ) {
			return false; // nothing else to scan
		}
		list( $dir, $handle ) = $set;
		while ( false !== ( $file = readdir( $handle ) ) ) {
			// Exclude '.' and '..' as well .svn or .lock type files
			if ( $file[0] !== '.' ) {
				// If the first thing we find is a file, then return it.
				// If the first thing we find is a directory, then return
				// the first file that it contains (via recursion).
				// We exclude symlink dirs in order to avoid cycles.
				if ( is_dir( "$dir/$file" ) && !is_link( "$dir/$file" ) ) {
					$subHandle = opendir( "$dir/$file" );
					if ( $subHandle ) {
						$this->pushDirectory( "{$dir}/{$file}", $subHandle );
						$nextFile = $this->nextFile();
						if ( $nextFile !== false ) {
							return $nextFile; // found the next one!
						}
					}
				} elseif ( is_file( "$dir/$file" ) ) {
					return "$dir/$file"; // found the next one!
				}
			}
		}
		# If we didn't find anything in this directory,
		# then back out and scan the other higher directories
		$this->popDirectory();
		return $this->nextFile();
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
