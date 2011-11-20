<?php
/**
 * @file
 * @ingroup FileRepo
 */

/**
 * Class for a file-system based file backend
 */
class FSFileBackend extends FileBackend {
	protected $fileMode; // file permission mode

	function __construct( array $config ) {
		$this->name = $config['name'];
		$this->lockManager = $config['lockManger'];
		$this->fileMode = isset( $config['fileMode'] )
			? $config['fileMode']
			: 0644;
	}

	public function store( array $params ) {
		$status = Status::newGood();

		if ( file_exists( $params['dest'] ) ) {
			if ( isset( $params['overwriteDest'] ) ) {
				$ok = unlink( $params['dest'] );
				if ( !$ok ) {
					$status->fatal( "Could not delete destination file." );
					return $status;
				}
			} elseif ( isset( $params['overwriteSame'] ) ) {
				if ( !$this->filesAreSame( $params['source'], $params['dest'] ) ) {
					$status->fatal( "Non-identical destination file already exists." );
				}
				return $status; // do nothing; either OK or bad status
			} else {
				$status->fatal( "Destination file already exists." );
				return $status;
			}
		} else {
			wfMakeDirParents( $params['dest'] );
		}

		wfSuppressWarnings();
		$ok = copy( $params['source'], $params['dest'] );
		wfRestoreWarnings();
		if ( !$ok ) {
			$status->fatal( "Could not copy file to destination." );
			return $status;
		}

		$this->chmod( $params['dest'] );

		return $status;
	}

	public function copy( array $params ) {
		return $this->store( $params ); // both source and dest are on FS
	}

	public function move( array $params ) {
		$status = Status::newGood();

		if ( file_exists( $params['dest'] ) ) {
			if ( isset( $params['overwriteDest'] ) ) {
				$ok = unlink( $params['dest'] );
				if ( !$ok ) {
					$status->fatal( "Could not delete destination file." );
					return $status;
				}
			} elseif ( isset( $params['overwriteSame'] ) ) {
				if ( !$this->filesAreSame( $params['source'], $params['dest'] ) ) {
					$status->fatal( "Non-identical destination file already exists." );
				}
				return $status; // do nothing; either OK or bad status
			} else {
				$status->fatal( "Destination file already exists." );
				return $status;
			}
		} else {
			wfMakeDirParents( $params['dest'] );
		}

		wfSuppressWarnings();
		$ok = rename( $params['source'], $params['dest'] );
		wfRestoreWarnings();
		if ( !$ok ) {
			$status->fatal( "Could not move file to destination." );
			return $status;
		}

		return $status;
	}

	public function delete( array $params ) {
		$status = Status::newGood();

		if ( !file_exists( $params['source'] ) ) {
			if ( !$params['ignoreMissingSource'] ) {
				$status->fatal( "Could not delete source because it does not exist." );
			}
			return $status; // do nothing; either OK or bad status
		}

		wfSuppressWarnings();
		$ok = unlink( $params['dest'] );
		wfRestoreWarnings();
		if ( !$ok ) {
			$status->fatal( "Could not delete source file." );
			return $status;
		}

		return $status;
	}

	public function concatenate( array $params ) {
		$status = Status::newGood();

		// Check if the destination file exists and we can't handle that
		$destExists = file_exists( $params['dest'] );
		if ( $destExists && !$params['overwriteDest'] && !$params['overwriteSame'] ) {
			$status->fatal( "Destination file already exists." ); // short-circuit
			return $status;
		}

		// Create a new temporary file...
		wfSuppressWarnings();
		$tmpPath = tempnam( wfTempDir(), 'file_concatenate' );
		wfRestoreWarnings();
		if ( $tmpPath === false ) {
			$status->fatal( "Could not create temporary file $tmpPath." );
			return $status;
		}

		// Build up that file using the source chunks (in order)...
		wfSuppressWarnings();
		$tmpHandle = fopen( $tmpPath, 'a' );
		wfRestoreWarnings();
		if ( $tmpHandle === false ) {
			$status->fatal( "Could not open temporary file $tmpPath." );
			return $status;
		}
		foreach ( $params['sources'] as $source ) {
			// Load chunk into memory (it should be a small file)
			$chunk = file_get_contents( $source );
			if ( $chunk === false ) {
				$status->fatal( "Could not read source file $source." );
				return $status;
			}
			// Append chunk to file (pass chunk size to avoid magic quotes)
			if ( !fwrite( $tmpHandle, $chunk, count( $chunk ) ) ) {
				$status->fatal( "Could not write to temporary file $tmpPath." );
				return $status;
			}
		}
		wfSuppressWarnings();
		if ( !fclose( $tmpHandle ) ) {
			$status->fatal( "Could not close temporary file $tmpPath." );
			return $status;
		}
		wfRestoreWarnings();

		// Handle overwrite behavior of file destination if applicable.
		// Note that we already checked if no overwrite params were set above.
		if ( $destExists ) {
			if ( isset( $params['overwriteDest'] ) ) {
				wfSuppressWarnings();
				$ok = unlink( $params['dest'] );
				wfRestoreWarnings();
				if ( !$ok ) {
					$status->fatal( "Could not delete destination file." );
					return $status;
				}
			} elseif ( isset( $params['overwriteSame'] ) ) {
				if ( !$this->filesAreSame( $params['source'], $params['dest'] ) ) {
					$status->fatal( "Non-identical destination file already exists." );
				}
				return $status; // do nothing; either OK or bad status
			}
		} else {
			// Make sure destination directory exists
			wfMakeDirParents( $params['dest'] );
		}

		// Rename the temporary file to the destination path
		wfSuppressWarnings();
		$ok = rename( $tmpPath, $params['dest'] );
		wfRestoreWarnings();
		if ( !$ok ) {
			$status->fatal( "Could not rename temporary file to destination." );
			return $status;
		}

		$this->chmod( $params['dest'] );

		return $status;
	}

	public function fileExists( array $params ) {
		return file_exists( $params['source'] );
	}

	public function getFileProps( array $params ) {
		return File::getPropsFromPath( $params['source'] );
	}

	public function getLocalCopy( array $params ) {
		// Create a new temporary file...
		wfSuppressWarnings();
		$tmpPath = tempnam( wfTempDir(), 'file_localcopy' );
		wfRestoreWarnings();
		if ( $tmpPath === false ) {
			return null;
		}

		// Copy the source file over the temp file
		wfSuppressWarnings();
		$ok = copy( $params['source'], $tmpPath );
		wfRestoreWarnings();
		if ( !$ok ) {
			return null;
		}

		$this->chmod( $tmpPath );

		return $tmpPath;
	}

	/**
	 * Check if two files are identical
	 * @param $path1 string
	 * @param $path2 string
	 * @return bool
	 */
	protected function filesAreSame( $path1, $path2 ) {
		return ( // check size first since it's faster
			filesize( $path1 ) === filesize( $path2 ) &&
			sha1_file( $path1 ) === sha1_file( $path2 )
		);
	}

	/**
	 * Chmod a file, suppressing the warnings
	 * @param $path string The path to change
	 * @return bool Success
	 */
	protected function chmod( $path ) {
		wfSuppressWarnings();
		$ok = chmod( $path, $this->fileMode );
		wfRestoreWarnings();

		return $ok;
	}
}
