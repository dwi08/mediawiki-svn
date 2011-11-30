<?php
/**
 * @file
 * @ingroup FileRepo
 * @ingroup FileBackend
 */

/**
 * This class is used to hold the location and do limited manipulation
 * of files stored temporarily (usually this will be $wgTmpDirectory)
 *
 * @ingroup FileRepo
 * @ingroup FileBackend
 */
class TempFSFile extends FSFile {
	protected $canDelete = true; // garbage collect the temp file

	/**
	 * Make a new temporary file on the file system
	 * 
	 * @param $prefix string
	 * @param $extension string
	 * @return TempFSFile|null 
	 */
	public static function factory( $prefix, $extension = '' ) {
		$tmpDest = tempnam( wfTempDir(), $prefix );
		if ( $tmpDest === false ) {
			return null;
		}
		if ( $extension != '' ) {
			$path = "{$tmpDest}.{$extension}";
			if ( !rename( $tmpDest, $path ) ) {
				return null;
			}
		} else {
			$path = $tmpDest;
		}
		return new self( $path );
	}

	/**
	 * Flag to not clean up after the temporary file
	 *
	 * @return void
	 */
	public function preserve() {
		$this->canDelete = false;
	}

	/**
	 * Cleans up after the temporary file.
	 * Currently this means removing it from the local disk.
	 */
	function __destruct() {
		if ( $this->canDelete ) {
			wfSuppressWarnings();
			unlink( $this->path );
			wfRestoreWarnings();
		}
	}
}
