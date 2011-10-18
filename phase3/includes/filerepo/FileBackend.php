<?php

/**
 * Handles all operations on real files. The FileBacked is designed to be an
 * abstraction layer that allows developers to create a new class that extends
 * this class to use any arbitrary data store for media uploads. To manipulate
 * files contained within the current MediaWiki installation the dev/extension
 * writer need only to interact with the doOps() method in this class
 *
 * PLEASE PLEASE leave feedback on improving this file abstraction layer
 *
 * @todo Implement streamFile
 * @todo Implement enumFiles
 * @todo Design and implement smart file chunking uploads
 * @todo Fill in $this->validOperations via reflection into AbstractDataStore
 * @todo implement a method to ensure there is a primary store that registerDataStore can use
 * @todo Alter registerDataStore with the above to set incoming data store as primary if one does not exist
 * @todo Alter registerDataStore to alter the current primary to false if isPrimaryStore is set to true on incoming
 * @todo Register data store in global and existing in includes/filerepo/dataStores inside of constructor
 */
abstract class FileBackend {

    /*
     * This could potentially be populated via reflection. Look at the methods
     * available in the AbstratDataStore object
     */
    protected $validOperations = array('store'/* add a new file to data store */,
                                       'relocate' /* used to relocate all files in store */,
                                       'copy',
                                       'delete',
                                       'move',
                                       'concatinate',
                                       'fileExists',
                                       'getFileProps',
                                       'getLocalCopy'
                                        /* 'streamFile' not yet implemented , */
                                       /* 'enumFiles'  not yet implemented*/
                                       );

    private $registeredDataStores = array();

    /**
     * Constructor, currently does nothing
     */
    public function __construct();



    /**
     * This is the main entry point into the file system back end. Callers will
     * supply a list of operations to be performed (almost like a script) as an
     * array. This class will then handle handing the operations off to the
     * correct file store module
     *
     * For more information about a specific operation see the abstract method
     * definitions comment block
     *
     * Using $ops
     * $ops is an array of arrays. The first array holds a list of operations.
     * The inner array contains the parameters, E.G:
     * <code>
     * $ops = array(
     *                   array('operation' => 'store',
     *                         'src' => '/tmp/uploads/picture.png',
     *                         'dest' => 'zone/uploadedFilename.png'
     *                         )
     *             );
     * </code>
     * @param Array $ops - Array of arrays containing N operations to execute IN ORDER
     * @return Status
     */
    public function doOps($ops) {
        if(!is_array($ops)) {
            throw new MWException(__METHOD__ . " not provided with an operations array");
        }

        $statusObject = ''; // some sort of status object that can hold other status objects

        foreach($ops AS $op) {
            switch ($op['operation']) {
                case 'move':
                    $statusObject->append($this->commonCaller('move', $op));
                    break;
                case 'delete':
                    $statusObject->append($this->commonCaller('delete', $op));
                    break;
                case 'copy':
                    $statusObject->append($this->copy());
                    break;
                case 'getFileProps':
                    $tmpFile = $this->getLocalCopy();
                    $statusObject->append($this->getFileProps($tmpFile));
                default:
                    $statusObject->append('Unknown data store operation ' . $op['operation']);

            }
        }

        return 'STATUS OBJECT';
    }

    /**
     *
     * @param <type> $dataStoreObject
     * @param <type> $isPrimaryStore
     */
    public function registerDataStoreModule($dataStoreObject, $isPrimaryStore = false) {
        if(is_subclassof($dataStoreObject, 'AbstractDataStore')) {
            $this->registeredDataStores[] = array($dataSourceObject, $isPrimaryStore);
        }
    }

    /**
     * Ensure that there are data stores registered. If not then register the
     * local file system as the default data store
     *
     * @todo Create local data store object and set as default if no store exists
     */
    private function ensureRegisteredDataStore() {
        if(empty($this->registeredDataStores)) {
            // $this->registerDataStoreModule(new LocalFileSystemDataStore())
        }
    }

    /**
     * The commonCaller() method is used for operations that contain the same
     * code as many other options. It should be used as often as possible to
     * reduce code duplication
     * @param String $operation
     * @param Array $params
     * @return Status object of some sort
     */
    private function commonCaller($operation, $params) {
        $statusObject = ''; // new Status object of some sort
        foreach($this->registeredDataStores AS $store) {
            $statusObject->append($store->$operation($params));
        }
        return $statusObject;
    }


//    /**
//     * Store a file to a given destination.
//     *
//     * @param $srcPath String: source path or virtual URL
//     * @param $dstZone String: destination zone
//     * @param $dstRel String: destination relative path
//     * @param $flags Integer: bitwise combination of the following flags:
//     *     self::DELETE_SOURCE     Delete the source file after upload
//     *     self::OVERWRITE         Overwrite an existing destination file instead of failing
//     *     self::OVERWRITE_SAME    Overwrite the file if the destination exists and has the
//     *                             same contents as the source
//     * @return FileRepoStatus
//     */
//    abstract function store( $srcPath, $dstZone, $dstRel, $flags = 0 );
//
//        /**
//     * Verify that a file exists in the file store
//     *
//     * @param String $file
//     * @param Integer $flags
//     * @return Boolean
//     */
//    abstract function fileExists( $file, $flags = 0 );
//
//    /**
//     * Move a file to the deletion archive.
//     * If no valid deletion archive exists, this may either delete the file
//     * or throw an exception, depending on the preference of the repository
//     * @param $srcRel Mixed: relative path for the file to be deleted
//     * @param $archiveRel Mixed: relative path for the archive location.
//     *        Relative to a private archive directory.
//     * @return FileRepoStatus object
//     */
//    abstract function delete( $srcRel, $archiveRel );
//
//    /**
//     * Moves a file from one place to another in the file store
//     *
//     * @param String $srcRel - Path to source file
//     * @param String $destRel - Path to new destination
//     * @return Boolean
//     */
//    abstract function move( $srcRel, $destRel );
//
//    abstract function copy ( $src, $dest );
//
//
//
//
//
//
//    /**
//     * Retreive a copy of a file from the file store and place it in the local
//     * $wgTmpDirectory. A new TempLocalFile object should be created and returned
//     * that contains the local path to the file
//     *
//     * Please overload this class inside your storage module
//     *
//     * @return TempLocalFile
//     */
//    abstract function getLocalCopy( $file );
//
//    /**
//     * Return an associative array containing the file properties
//     *
//     * @param String $file
//     * @return Array
//     */
//    public function getFileProps( $file ) {
//        $file = $this->getLocalCopy( $file );
//        return File::getPropsFromPath( $file->getPath() );
//    }
//
//    /**
//     * Returns an associative array containing a listing of the names of the
//     * thumbnail files associated with the provided image file
//     *
//     * Please overload this class inside your storage module
//     *
//     * @param String $file - Name of file to retreive thumbnail listing for
//     * @retrun Array
//     */
//    abstract function getThumbnailList( $file );
//
//
//
//    public function addChunk() { throw new MWException( __METHOD__ . ' not yet implemented.' ); }
//    public function concatenateChunks() { throw new MWException( __METHOD__ . ' not yet implemented.' ); }

    
} // end class