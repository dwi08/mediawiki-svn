<?php

/**
 * This class defines the methods as abstract that should be implemented in 
 * child classes if the target store supports the operation.
 */
abstract class AbstractFileStore {

    /**
     * Array of operations that are supported by this data store
     * 
     * This could potentially be populated via reflection at some point
     * 
     * <code>
     * $supportedOperations = array('store', 'copy', 'delete')
     * </code>
     * 
     * @var Array
     */
    protected $supportedOperations;
    
    /**
     * Setup the FileStore. The operations in this constructor are required for
     * the object to operate properly. If any subclasses use a constructor they
     * must call this parent constructor as well
     */
    public function __construct() {
        // Make sure the getFileProps is in the supportedOperations array
        // The support for this only exists in this parent class
        $this->supportedOperations[] = 'getFileProps';
    }
    
    /**
     * Returns a list of supported operations
     * 
     * @return Array
     */
    public function getSupportedOperations() {
        return $this->supportedOperations;
    }
    
    /**
     * Use this function in subclasses to define the specific FileStore 
     * store functionality
     * 
     * @param Array $params 
     */
    abstract function store( $params ) {
        return -1;
    }

    /**
     * Use this function in subclasses to define the specific FileStore 
     * relocate functionality
     * 
     * @param Array $params 
     */
    abstract function relocate( $params ) {
        return -1;
    }

    /**
     * Use this function in subclasses to define the specific FileStore 
     * copy functionality
     * 
     * @param Array $params 
     */
    abstract function copy( $params ) {
        return -1;
    }

    /**
     * Use this function in subclasses to define the specific FileStore 
     * delete functionality
     * 
     * @param Array $params 
     */
    abstract function delete( $params ) {
        return -1;
    }

    /**
     * Use this function in subclasses to define the specific FileStore 
     * move functionality
     * 
     * @param Array $params 
     */
    abstract function move( $params ) {
        return -1;
    }

    /**
     * Use this function in subclasses to define the specific FileStore 
     * concatinate functionality
     * 
     * @param Array $params 
     */
    abstract function concatinate( $params ) {
        return -1;
    }

    /**
     * Use this function in subclasses to define the specific FileStore 
     * file exists functionality
     * 
     * @param Array $params 
     */
    abstract function fileExists( $params ) {
        return -1;
    }

    /**
     * Use this function in subclasses to define the specific FileStore 
     * retreive functionality
     * 
     * @param Array $params 
     */
    abstract function getLocalCopy( $params ) {
        return -1;
    }
    
    /**
     * Get the properties of a file
     * 
     * @param Array $params 
     * @return Array
     */
    public function getFileProps( $params ) {
        throw new MWException(__METHOD__ . " has not yet been implemented in AbstractFileStore");
    }
} // end class