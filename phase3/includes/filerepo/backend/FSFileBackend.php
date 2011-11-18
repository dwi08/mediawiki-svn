<?php
/**
 * Base class for all file backend classes
 *
 * @file
 * @ingroup FileRepo
 */

/**
 * This class defines the methods as abstract that should be implemented in 
 * child classes if the target store supports the operation.
 */
class FSFileBackend extends FileBackend {
	public function store( array $params ) {
		$status = Status::newGood();

		wfMakeDirParents( $params['dest'] );

		if ( file_exists( $params['dest'] ) ) {
			if ( $params['overwriteDest'] ) {
				unlink( $params['dest'] );
			} elseif( $params['overwriteSame'] ) {
				if ( // check size first since it's faster
					filesize( $params['dest'] ) != filesize( $params['source'] ) ||
					sha1_file( $params['dest'] ) != sha1_file( $params['source'] )
				) {
					// error out
				}
			}
		}
		wfSuppressWarnings();
		copy( $params['source'], $params['dest'] );
		wfRestoreWarnings();

		return $status;
	}

    public function copy( array $params ) {
		$status = Status::newGood();

		wfMakeDirParents( $params['dest'] );

		wfSuppressWarnings();
		copy( $params['source'], $params['dest'] );
		wfRestoreWarnings();

		return $status;
	}

    public function delete( array $params ) {
		$status = Status::newGood();

		wfSuppressWarnings();
		unlink( $params['dest'] );
		wfRestoreWarnings();

		return $status;
	}

    public function concatenate( array $params ) {
		$status = Status::newGood();

		wfMakeDirParents( $params['dest'] );

		wfSuppressWarnings();
		$destHandle = fopen( $params['dest'], 'a' );
		
		wfRestoreWarnings();
		
		return $status;
	}

    public function fileExists( array $params ) {
		
	}

    public function getFileProps( array $params ) {
		return File::getPropsFromPath( $params['source'] );
	}

    public function getLocalCopy( array $params ) {
		
	}

	public function lockFile( $source ) {
	}

	public function unlockFile( $source ) {
		
	}
}
