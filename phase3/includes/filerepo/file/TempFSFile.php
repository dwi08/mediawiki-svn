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

	/** @var Array of active temp files to purge on shutdown */
	protected static $instances = array();

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
		$tmpFile = new self( $path );
		self::$instances[] = $tmpFile;

		return $tmpFile;
	}

	/**
	 * Purge this file off the file system
	 * 
	 * @return bool Success
	 */
	public function purge() {
		$this->canDelete = false; // done
		wfSuppressWarnings();
		$ok = unlink( $this->path );
		wfRestoreWarnings();
		return $ok;
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
	 * Cleans up after the temporary file by deleting it.
	 * This is done on shutdown after PHP kills self::$instances.
	 */
	function __destruct() {
		if ( $this->canDelete ) {
			wfSuppressWarnings();
			unlink( $this->path );
			wfRestoreWarnings();
		}
	}
}
