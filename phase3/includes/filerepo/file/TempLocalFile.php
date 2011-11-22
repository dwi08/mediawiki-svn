<?php
/**
 * @file
 * @ingroup FileRepo
 */

/**
 * This class is used to hold the location and do limited manipulation
 * of files stored temporarily (usually this will be $wgTmpDirectory)
 */
class TempLocalFile {
	protected $path; // path to local temp file directory
	protected $canDelete; // whether this object should garbage collect the temp file

	/**
	 * Sets up the temporary file object
	 *
	 * @param String $path Path to temporary file on local disk
	 * @param Boolean $canDelete Whether this object should garbage collect the temp file
	 */
	public function __construct( $path, $canDelete = true ) {
		$this->path = $path;
		$this->canDelete = $canDelete;
	}

	/**
	 * Returns the local path to the temp file
	 *
	 * @return String
	 */
	public function getPath() {
		return $this->path;
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
