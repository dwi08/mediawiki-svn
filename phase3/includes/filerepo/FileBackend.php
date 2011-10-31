<?php

/**
 * Handles all operations on real files. The FileBackend should be the single
 * point of interface to the filesystem in all core and extension code. To manipulate
 * files contained within the current MediaWiki installation the dev/extension
 * writer need only to interact with the doOps() method in this class
 *
 * PLEASE PLEASE leave feedback on improving this file abstraction layer
 *
 * @todo Implement streamFile
 * @todo Implement enumFiles
 * @todo Design and implement smart file chunking uploads
 */
abstract class FileBackend {

    /**
     *  This should be a subclass of the AbstractFileStore and specific to a
     * single data store
     * @var AbstractFileStore
     */
    private $registeredDataStore = array();

    /**
     * Constructor
     * Loads the required data store object for interacting with any data store.
     * The store is loaded via a config object that is loosely structured as
     * 
     * <code>
     *  $conf = array( 'store' => 'LocalFileSystemDataStore' ) 
     * </code>
     * 
     * The only required array item is 'store' which specifies which data store
     * object to load. Additional options may be in that array that are needed
     * for various configuration details of the specific store
     * 
     * @param Array $conf
     */
    public function __construct($conf) {
        if( !is_array( $conf ) ) {
            // Ensure there is configuration data present
            throw new MWException( __METHOD__ . ': no data store configuration available' );
        } elseif( !isset( $conf['store'] ) ) {
            // If a store is not specified use the LocalFileSystemDataStore
            $conf['store'] = 'LocalFileSystemDataStore';
        }
        if( !file_exists( __DIR__ . "/dataStores/{$conf['store']}.php" ) ) {
            // Ensure store object exists
            throw new MWException( __METHOD__ . ": {$conf['store']} data store object does not exist" );
        }
        
        // Load the dataStore object
        require_once "dataStore/{$conf['store']}.php";
        $this->registerDataStore( new $conf['store']($conf) );
  }



    /**
     * This is the main entry point into the file system back end. Callers will
     * supply a list of operations to be performed (almost like a script) as an
     * array. This class will then handle handing the operations off to the
     * correct file store module
     *
     * For more information about a specific operation see the abstract method
     * definitions in AbstractFileStore or the extended class
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
    public function doOps( $ops, $reversed = 0 ) {
        if(!is_array($ops)) {
            throw new MWException(__METHOD__ . " not provided with an operations array");
        }

        $st = ''; // Status message string
        $statusObject = ''; // some sort of status object that can hold other status objects

        foreach( $ops AS $i => $op ) {
            if( in_array( $op['operation'], $this->registeredDataStore->getSupportedOperations() ) ) {
                // Ensure that the operation is supported before attempting to do it
                $st = $this->registeredDataStore->$op['operation']();
            } else {
                $st = "{$op['operation']} is not supported by the current data store";
            }
            
            
            $statusObject->append( $st );
            if ( $st && $reversed) {
                // oh noes! Something went wrong AGAIN.
                // pray to gods.
                return 'STATUS OBJECT';
            } elseif ( $st ) {
                // oh crap, something went wrong. Try to unwind.
                return $this->doOps( $this->unwind( $ops, $i ), 1);
            }
        }

            return 'STATUS OBJECT';
	}

    /**
     * Unwinds an array of operations, attempting to reverse their action.
     * @param Array $ops - Array of arrays containing N operations to execute IN ORDER
     * @param Integer $i - index of first operation that failed.
     * @return Array
     */
	protected function unwind( $ops, $i ) {
		$outops = array();

        foreach( $ops AS $k => $op ) {
			$newop = null;
            switch ( $op['operation'] ) {
                case 'move':
					$newop = $op;
                    $newop['source'] = $op['source'];
                    $newop['dest'] = $op['dest'];
                    break;
                case 'delete':
                    // sigh.
                    break;
                case 'copy':
					$newop = $op;
                    $newop['operation'] = 'delete';
                    $newop['source'] = $op['dest'];
                    break;
			} 
			if ($newop) {
				array_unshift($outops, $newop);
			}
		}
		return $outops;
    }

    /**
     * Validates that the data store is infact of the proper type and then adds
     * it to the registered data store property
     * 
     * @param AbstractDataStore $dataStoreObject
     */
    public function registerDataStore($dataStoreObject) {
        if(is_subclassof($dataStoreObject, 'AbstractDataStore')) {
            $this->registeredDataStore[] = $dataSourceObject;
        }
    }
} // end class

