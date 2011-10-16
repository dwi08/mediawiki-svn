<?php

abstract class FileBackend {


    public function __construct();


    public function doOps($ops) {
        if(!is_array($ops)) {
            throw new MWException(__METHOD__ . " not provided with an operations array");
        }

        foreach($ops AS $op) {

            switch ($op['operations']) {
                case 'move':
                    $this->move($params);
                    break;
                case 'delete':
                    $this->delete($params);
                    break;
                case 'copy':
                    $result = $this->copy();
                default:
                    throw new MWException("$op unknown in " . __METHOD__);

            }
        }
    }


    /**
     * Store a file to a given destination.
     *
     * @param $srcPath String: source path or virtual URL
     * @param $dstZone String: destination zone
     * @param $dstRel String: destination relative path
     * @param $flags Integer: bitwise combination of the following flags:
     *     self::DELETE_SOURCE     Delete the source file after upload
     *     self::OVERWRITE         Overwrite an existing destination file instead of failing
     *     self::OVERWRITE_SAME    Overwrite the file if the destination exists and has the
     *                             same contents as the source
     * @return FileRepoStatus
     */
    abstract function store( $srcPath, $dstZone, $dstRel, $flags = 0 );

        /**
     * Verify that a file exists in the file store
     *
     * @param String $file
     * @param Integer $flags
     * @return Boolean
     */
    abstract function fileExists( $file, $flags = 0 );

    /**
     * Move a file to the deletion archive.
     * If no valid deletion archive exists, this may either delete the file
     * or throw an exception, depending on the preference of the repository
     * @param $srcRel Mixed: relative path for the file to be deleted
     * @param $archiveRel Mixed: relative path for the archive location.
     *        Relative to a private archive directory.
     * @return FileRepoStatus object
     */
    abstract function delete( $srcRel, $archiveRel );

    /**
     * Moves a file from one place to another in the file store
     *
     * @param String $srcRel - Path to source file
     * @param String $destRel - Path to new destination
     * @return Boolean
     */
    abstract function move( $srcRel, $destRel );

    abstract function copy ( $src, $dest );






    /**
     * Retreive a copy of a file from the file store and place it in the local
     * $wgTmpDirectory. A new TempLocalFile object should be created and returned
     * that contains the local path to the file
     *
     * Please overload this class inside your storage module
     * 
     * @return TempLocalFile
     */
    abstract function getLocalCopy( $file );

    /**
     * Return an associative array containing the file properties
     *
     * @param String $file
     * @return Array
     */
    public function getFileProps( $file ) {
        $file = $this->getLocalCopy( $file );
        return File::getPropsFromPath( $file->getPath() );
    }

    /**
     * Returns an associative array containing a listing of the names of the
     * thumbnail files associated with the provided image file
     *
     * Please overload this class inside your storage module
     *
     * @param String $file - Name of file to retreive thumbnail listing for
     * @retrun Array
     */
    abstract function getThumbnailList( $file );



    public function addChunk() { throw new MWException( __METHOD__ . ' not yet implemented.' ); }
    public function concatenateChunks() { throw new MWException( __METHOD__ . ' not yet implemented.' ); }

    
} // end class