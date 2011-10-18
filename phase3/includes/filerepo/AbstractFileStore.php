<?php

/**
 * A particular data store may not support every operation. If it does not support
 * an operation that should return -1. It should not throw an exception as
 * the operation in question may be naively called from a loop that does not
 * check for support
 */
abstract class AbstractFileStore {

    abstract store( $params ) {
        return -1;
    }

    abstract relocate( $params ) {
        return -1;
    }

    abstract copy( $params ) {
        return -1;
    }

    abstract delete( $params ) {
        return -1;
    }

    abstract move( $params ) {
        return -1;
    }

    abstract concatinate( $params ) {
        return -1;
    }

    abstract fileExists( $params ) {
        return -1;
    }

    abstract getLocalCopy( $params ) {
        return -1;
    }
} // end class