<?php

/**
 * This class is used to hold the location to and do limited manipulation of
 * files stored temporarily (usually this will be $wgTmpDirectory)
 * 
 */
class TempLocalFile {

    /**
     *  Path to local temp file directory
     * @var String
     */
    private $path;

    /**
     * Does this object have authority to remove the temp file
     * @var Boolean
     */
    private $canDelete;

    /**
     * Sets up the temporary file object
     * @param String $path - Path to temporary file on local disk
     * @param Boolean $canDelete  - Does this object have authority to remove the file
     */
    public function __construct($path, $canDelete = true) {
        $this->path = $path;
        $this->canDelete = $canDelete;
    }

    /**
     *  Returns the local path to the temp file
     * @return String
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * If this object has the authority this method will remove the temp file
     * from the local disk. This method is called on object destruct, but
     * may also be called any time a cleanup is needed
     */
    public function cleanup() {
        if($this->canDelete) {
            unlink($this->path);
        }
    }

    /**
     * Object destructor.
     *
     * Cleans up after the temporary file. Currently this means removing it
     * from the local disk
     */
    public function __destruct() {
        $this->cleanup();
    }
} // end class