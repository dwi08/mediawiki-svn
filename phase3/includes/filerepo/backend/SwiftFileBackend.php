<?php
/**
 * @file
 * @ingroup FileBackend
 */

/**
 * Class for a Swift based file backend.
 * Status messages should avoid mentioning the Swift account name
 * Likewise, error suppression should be used to avoid path disclosure.
 *
 * @FIXME: resuse connections with auto-connect and don't let connection
 * exceptions bubble up from read/write operations.
 *
 * @ingroup FileBackend
 */
class SwiftFileBackend extends FileBackend {
	protected $swiftUser; // string
	protected $swiftKey; // string
	protected $swiftAuthUrl; // string
	protected $swiftProxyUser; // string

	/**
	 * @see FileBackend::__construct()
	 * Additional $config params include:
	 *    swiftAuthUrl   : Swift authentication server URL
	 *    swiftUser      : Swift user used by MediaWiki
	 *    swiftKey       : Authentication key for the above user (used to get sessions)
	 *    swiftProxyUser : Swift user used for end-user hits to proxy server
	 */
	function __construct( array $config ) {
		parent::__construct( $config );
		// Required settings
		$this->swiftUser = $config['swiftUser'];
		$this->swiftKey = $config['swiftKey'];
		$this->swiftAuthUrl = $config['swiftAuthUrl'];
		// Optional settings
		$this->swiftProxyUser = isset( $config['swiftProxyUser'] )
			? $config['swiftProxyUser']
			: '';
	}

	/**
	 * Get a connection to the swift proxy.
	 *
	 * @return CF_Connection
	 */
	protected function connect() {
		$auth = new CF_Authentication(
			$this->swiftUser, $this->swiftKey, NULL, $this->swiftAuthUrl );
		try {
			$auth->authenticate();
		} catch ( AuthenticationException $e ) {
			throw new MWException( "We can't authenticate ourselves." );
		# } catch (InvalidResponseException $e) {
		#   throw new MWException( __METHOD__ . "unexpected response '$e'" );
		}
		return new CF_Connection( $auth );
	}

	/**
	 * Given a connection and container name, return the container.
	 * We KNOW the container should exist, so puke if it doesn't.
	 *
	 * @param $conn CF_Connection
	 *
	 * @return CF_Container
	 */
	protected function get_container( $conn, $cont ) {
		try {
			return $conn->get_container( $cont );
		} catch ( NoSuchContainerException $e ) {
			throw new MWException( "A container we thought existed, doesn't." );
		# } catch (InvalidResponseException $e) {
		#   throw new MWException( __METHOD__ . "unexpected response '$e'" );
		}
	}

	/**
	 * Copy a file from one place to another place
	 *
	 * @param $srcContainer CF_Container
	 * @param $srcRel String: relative path to the source file.
	 * @param $dstContainer CF_Container
	 * @param $dstRel String: relative path to the destination.
	 */
	protected function swiftcopy( $srcContainer, $srcRel, $dstContainer, $dstRel ) {
		// The destination must exist already.
		$obj = $dstContainer->create_object( $dstRel );
		$obj->content_type = 'text/plain'; // overwritten by source object.

		try {
			$obj->write( '.' );
		} catch ( SyntaxException $e ) {
			throw new MWException( "Write failed: $e" );
		} catch ( BadContentTypeException $e ) {
			throw new MWException( "Missing Content-Type: $e" );
		} catch ( MisMatchedChecksumException $e ) {
			throw new MWException( __METHOD__ . "should not happen: '$e'" );
		}

		try {
			$obj = $dstContainer->get_object( $dstRel );
		} catch ( NoSuchObjectException $e ) {
			throw new MWException( 'The object we just created does not exist: ' .
				$dstContainer->name . "/$dstRel: $e" );
		}

		try {
			$srcObj = $srcContainer->get_object( $srcRel );
		} catch ( NoSuchObjectException $e ) {
			throw new MWException( 'Source file does not exist: ' .
				$srcContainer->name . "/$srcRel: $e" );
		} 

		try {
			$dstContainer->copy_object_from($srcObj,$srcContainer,$dstRel);
		} catch ( SyntaxException $e ) {
			throw new MWException( 'Source file does not exist: ' .
				$srcContainer->name . "/$srcRel: $e" );
		} catch ( MisMatchedChecksumException $e ) {
			throw new MWException( "Checksums do not match: $e" );
		}
	}

	/**
	 * @see FileBackend::doStore()
	 */
	function doStore( array $params ) {
		$status = Status::newGood();

		list( $destc, $dest ) = $this->resolveStoragePath( $params['dst'] );
		if ( $dest === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dst'] );
			return $status;
		}
		$conn = $this->connect();
		$dstc = $this->get_container( $conn, $destc );
		try {
			$objd = $dstc->get_object( $dest );
			// if we are still here, it exists.
			if ( empty( $params['overwriteDest'] ) ) {
				$status->fatal( 'backend-fail-alreadyexists', $params['dst'] );
				return $status;
			}
			$exists = true;
		} catch ( NoSuchObjectException $e ) {
			$exists = false;
		}

		try {
			$obj = $dstc->create_object( $dest);
			$obj->load_from_filename( $params['src'], True );
		} catch ( SyntaxException $e ) {
			throw new MWException( 'missing required parameters' );
		} catch ( BadContentTypeException $e ) {
			throw new MWException( 'No Content-Type was/could be set' );
		} catch (InvalidResponseException $e) {
			$status->fatal( 'backend-fail-copy', $params['src'], $params['dst'] );
		} catch ( IOException $e ) {
			throw new MWException( "error opening file '$e'" );
		}
		return $status;
	}

	/**
	 * @see FileBackend::doCopy()
	 */
	function doCopy( array $params ) {
		$status = Status::newGood();

		list( $sourcec, $source ) = $this->resolveStoragePath( $params['src'] );
		if ( $source === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['src'] );
			return $status;
		}

		list( $destc, $dest ) = $this->resolveStoragePath( $params['dst'] );
		if ( $dest === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dst'] );
			return $status;
		}

		$conn = $this->connect();
		$srcc = $this->get_container( $conn, $sourcec );
		$dstc = $this->get_container( $conn, $destc );
		try {
			$objd = $dstc->get_object( $dest );
			// if we are still here, it exists.
			if ( empty( $params['overwriteDest'] ) ) {
				$status->fatal( 'backend-fail-alreadyexists', $params['dst'] );
				return $status;
			}
			$exists = true;
		} catch ( NoSuchObjectException $e ) {
			$exists = false;
		}
		try {
			$this->swiftcopy( $srcc, $source, $dstc, $dest );
		} catch ( InvalidResponseException $e ) {
			$status->fatal( 'backend-fail-copy', $params['src'], $params['dst'] );
		}   
		return $status;
	}

	/**
	 * @see FileBackend::doDelete()
	 */
	function doDelete( array $params ) {
		$status = Status::newGood();

		list( $sourcec, $source ) = $this->resolveStoragePath( $params['src'] );
		if ( $source === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['src'] );
			return $status;
		}

		$conn = $this->connect();
		$container = $this->get_container( $conn, $sourcec );

		try {
			$obj = $container->get_object( $source );
			$exists = true;
		} catch ( NoSuchObjectException $e ) {
			if ( empty( $params['ignoreMissingSource'] ) ) {
				$status->fatal( 'backend-fail-delete', $params['src'] );
			}
			$exists = false;
		}

		if ( $exists ) {
			try {
				$container->delete_object( $source );
			} catch ( SyntaxException $e ) {
				throw new MWException( "Swift object name not well-formed: '$e'" );
			} catch ( NoSuchObjectException $e ) {
				throw new MWException( "Swift object we are trying to delete does not exist: '$e'" );
			} catch ( InvalidResponseException $e ) {
				$status->fatal( 'backend-fail-delete', $params['src'] );
			}
		}
		return $status; // do nothing; either OK or bad status
	}

	/**
	 * @see FileBackend::doConcatenate()
	 */
	function doConcatenate( array $params ) {
		$status = Status::newGood();

		list( $destc, $dest ) = $this->resolveStoragePath( $params['dst'] );
		if ( $dest === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dst'] );
			return $status;
		}

		$conn = $this->connect();
		$dstc = $this->get_container( $conn, $destc );
		try {
			$objd = $dstc->get_object( $dest );
			// if we are still here, it exists.
			if ( empty( $params['overwriteDest'] ) ) {
				$status->fatal( 'backend-fail-alreadyexists', $params['dst'] );
				return $status;
			}
			$exists = true;
		} catch ( NoSuchObjectException $e ) {
			$exists = false;
		}

		try {
			$biggie = $dstc->create_object( $dest );
		} catch ( InvalidResponseException $e ) {
			$status->fatal( 'backend-fail-opentemp', $tmpPath );
			return $status;
		}

		foreach ( $params['srcs'] as $virtualSource ) {
			list( $sourcec, $source ) = $this->resolveStoragePath( $virtualSource );
			if ( $source === null ) {
				$status->fatal( 'backend-fail-invalidpath', $virtualSource );
				return $status;
			}
			$srcc = $this->get_container( $conn, $sourcec );
			$obj = $srcc->get_object( $source );
			$biggie->write( $obj->read() );
		}
		return $status;
	}

	/**
	 * @see FileBackend::doCopy()
	 */
	function doCreate( array $params ) {
		$status = Status::newGood();

		list( $destc, $dest ) = $this->resolveStoragePath( $params['dst'] );
		if ( $dest === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dst'] );
			return $status;
		}

		$conn = $this->connect();
		$dstc = $this->get_container( $conn, $destc );
		try {
			$objd = $dstc->get_object( $dest );
			// if we are still here, it exists.
			if ( empty( $params['overwriteDest'] ) ) {
				$status->fatal( 'backend-fail-alreadyexists', $params['dst'] );
				return $status;
			}
			$exists = true;
		} catch ( NoSuchObjectException $e ) {
			$exists = false;
		}

		$obj = $dstc->create_object( $dest );
		//FIXME how do we know what the content type is?
		// This *should* work...cloudfiles can figure content type from strings too.
		$obj->content_type = 'text/plain';

		try {
			$obj->write( $params['content'] );
		} catch ( InvalidResponseException $e ) {
			$status->fatal( 'backend-fail-create', $params['dst'] );
			return $status;
		}

		return $status;
	}

	/**
	 * @see FileBackend::secure()
	 */
	function secure( array $params ) {
		$status = Status::newGood();
		// @TODO: restrict container from $this->swiftProxyUser
		return $status; // badgers? We don't need no steenking badgers!
	}

	/**
	 * @see FileBackend::fileExists()
	 */
	function fileExists( array $params ) {
		list( $sourcec, $source ) = $this->resolveStoragePath( $params['src'] );
		if ( $source === null ) {
			return false; // invalid storage path
		}
		$conn = $this->connect();
		$container = $this->get_container( $conn, $sourcec );
		try {
			$obj = $container->get_object( $source );
			$exists = true;
		} catch ( NoSuchObjectException $e ) {
			$exists = false;
		}
		return $exists;
	}

	/**
	 * @see FileBackend::getFileTimestamp()
	 */
	function getFileTimestamp( array $params ) {
		list( $sourcec, $source ) = $this->resolveStoragePath( $params['src'] );
		if ( $source === null ) {
			return false; // invalid storage path
		}

		$conn = $this->connect();
		$container = $this->get_container( $conn, $sourcec);
		try {
			$obj = $container->get_object( $source );
		} catch ( NoSuchObjectException $e ) {
			$obj = NULL;
		}
		if ( $obj ) {
			$thumbTime = $obj->last_modified;
			// @FIXME: strptime() UNIX-only (http://php.net/manual/en/function.strptime.php)
			$tm = strptime( $thumbTime, '%a, %d %b %Y %H:%M:%S GMT' );
			$thumbGMT = gmmktime( $tm['tm_hour'], $tm['tm_min'], $tm['tm_sec'],
				$tm['tm_mon'] + 1, $tm['tm_mday'], $tm['tm_year'] + 1900 );
			return ( gmdate( 'YmdHis', $thumbGMT ) );
		} else {
			return false; // file not found.
		}
	}

	/**
	 * @see FileBackend::getFileList()
	 */
	function getFileList( array $params ) {
		list( $dirc, $dir ) = $this->resolveStoragePath( $params['dir'] );
		if ( $dir === null ) { // invalid storage path
			return array(); // empty result
		}

		$conn = $this->connect();
		// @TODO: return an Iterator class that pages via list_objects()
		$container = $this->get_container( $conn, $dirc );
		$files = $container->list_objects( 0, NULL, $dir );
		// if there are no files matching the prefix, return empty array
		return $files;
	}

	/**
	 * @see FileBackend::getLocalCopy()
	 */
	function getLocalCopy( array $params ) {
		list( $sourcec, $source ) = $this->resolveStoragePath( $params['src'] );
		if ( $source === null ) {
			return null;
		}

		// Get source file extension
		$i = strrpos( $source, '.' );
		$ext = strtolower( $i ? substr( $source, $i + 1 ) : '' );
		// Create a new temporary file...
		$tmpFile = TempFSFile::factory( wfBaseName( $source ) . '_', $ext );
		if ( !$tmpFile ) {
			return null;
		}
		$tmpPath = $tmpFile->getPath();

		$conn = $this->connect();
		$cont = $this->get_container( $conn, $sourcec );

		try {
			$obj = $cont->get_object( $source );
		} catch ( NoSuchObjectException $e ) {
			throw new MWException( "Unable to open original file at", $params['src'] );
		}

		try {
			$obj->save_to_filename( $tmpPath );
		} catch ( IOException $e ) {
			// throw new MWException( __METHOD__ . ": error opening '$e'" );
			return null;
		} catch ( InvalidResponseException $e ) {
			// throw new MWException( __METHOD__ . "unexpected response '$e'" );
			return null;
		}
		return $tmpFile;
	}
}
