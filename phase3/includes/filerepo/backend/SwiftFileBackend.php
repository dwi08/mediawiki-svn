<?php
/**
 * @file
 * @ingroup FileBackend
 * @author Russ Nelson
 * @author Aaron Schulz
 */

/**
 * Class for a Swift based file backend.
 * Status messages should avoid mentioning the Swift account name
 * Likewise, error suppression should be used to avoid path disclosure.
 *
 * This requires the php-cloudfiles library is present,
 * which is available at https://github.com/rackspace/php-cloudfiles.
 * All of the library classes must be registed in $wgAutoloadClasses.
 *
 * @TODO: update MessagesEn for status errors.
 *
 * @ingroup FileBackend
 */
class SwiftFileBackend extends FileBackend {
	/** @var CF_Authentication */
	protected $auth; // swift authentication handler
	/** @var CF_Connection */
	protected $conn; // swift connection handle
	protected $connStarted = 0; // integer UNIX timestamp

	protected $swiftProxyUser; // string
	protected $connTTL = 60; // integer seconds

	/**
	 * @see FileBackend::__construct()
	 * Additional $config params include:
	 *    swiftAuthUrl   : Swift authentication server URL
	 *    swiftUser      : Swift user used by MediaWiki
	 *    swiftKey       : Authentication key for the above user (used to get sessions)
	 *    swiftProxyUser : Swift user used for end-user hits to proxy server
	 */
	public function __construct( array $config ) {
		parent::__construct( $config );
		// Required settings
		$this->auth = new CF_Authentication(
			$config['swiftUser'], $config['swiftKey'], $config['swiftAuthUrl'] );
		// Optional settings
		$this->connTTL = isset( $config['connTTL'] )
			? $config['connTTL']
			: 60; // some sane number
		$this->swiftProxyUser = isset( $config['swiftProxyUser'] )
			? $config['swiftProxyUser']
			: '';
	}

	/**
	 * @see FileBackend::resolveContainerPath()
	 */
	protected function resolveContainerPath( $container, $relStoragePath ) {
		if ( strlen( urlencode( $relStoragePath ) ) > 1024 ) {
			return null; // too long for swift
		}
		return $relStoragePath;
	}

	/**
	 * @see FileBackend::doStoreInternal()
	 */
	function doStoreInternal( array $params ) {
		$status = Status::newGood();

		list( $dstCont, $destRel ) = $this->resolveStoragePath( $params['dst'] );
		if ( $destRel === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dst'] );
			return $status;
		}

		// (a) Get a swift proxy connection
		$conn = $this->getConnection();
		if ( !$conn ) {
			$status->fatal( 'backend-fail-connect' );
			return $status;
		}

		// (b) Check the destination container
		try {
			$dContObj = $conn->get_container( $conn, $dstCont );
		} catch ( NoSuchContainerException $e ) {
			$status->fatal( 'backend-fail-copy', $params['src'], $params['dst'] );
			return $status;
		} catch ( InvalidResponseException $e ) {
			$status->fatal( 'backend-fail-connect' );
			return $status;
		} catch ( Exception $e ) { // some other exception?
			$status->fatal( 'backend-fail-internal' );
			return $status;
		}

		// (c) Check if the destination object already exists
		try {
			$dContObj->get_object( $destRel ); // throws NoSuchObjectException
			// NoSuchObjectException not thrown: file must exist
			if ( empty( $params['overwriteDest'] ) ) {
				$status->fatal( 'backend-fail-alreadyexists', $params['dst'] );
				return $status;
			}
		} catch ( NoSuchObjectException $e ) {
			// NoSuchObjectException thrown: file does not exist
		} catch ( InvalidResponseException $e ) {
			$status->fatal( 'backend-fail-connect' );
			return $status;
		} catch ( Exception $e ) { // some other exception?
			$status->fatal( 'backend-fail-internal' );
			return $status;
		}

		// (d) Actually store the object
		try {
			$obj = $dContObj->create_object( $destRel );
			$obj->load_from_filename( $params['src'], True );
		} catch ( BadContentTypeException $e ) {
			$status->fatal( 'backend-fail-contenttype', $params['dst'] );
		} catch ( IOException $e ) {
			$status->fatal( 'backend-fail-copy', $params['src'], $params['dst'] );
		} catch ( InvalidResponseException $e ) {
			$status->fatal( 'backend-fail-connect' );
		} catch ( Exception $e ) { // some other exception?
			$status->fatal( 'backend-fail-internal' );
		}

		return $status;
	}

	/**
	 * @see FileBackend::doCopyInternal()
	 */
	function doCopyInternal( array $params ) {
		$status = Status::newGood();

		list( $srcCont, $srcRel ) = $this->resolveStoragePath( $params['src'] );
		if ( $srcRel === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['src'] );
			return $status;
		}

		list( $dstCont, $destRel ) = $this->resolveStoragePath( $params['dst'] );
		if ( $destRel === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dst'] );
			return $status;
		}

		// (a) Get a swift proxy connection
		$conn = $this->getConnection();
		if ( !$conn ) {
			$status->fatal( 'backend-fail-connect' );
			return $status;
		}

		// (b) Check the source and destination containers
		try {
			$sContObj = $this->get_container( $conn, $srcCont );
			$dContObj = $conn->get_container( $conn, $dstCont );
		} catch ( NoSuchContainerException $e ) {
			$status->fatal( 'backend-fail-copy', $params['src'], $params['dst'] );
			return $status;
		} catch ( InvalidResponseException $e ) {
			$status->fatal( 'backend-fail-connect' );
			return $status;
		} catch ( Exception $e ) { // some other exception?
			$status->fatal( 'backend-fail-internal' );
			return $status;
		}

		// (c) Check if the destination object already exists
		try {
			$dContObj->get_object( $destRel ); // throws NoSuchObjectException
			// NoSuchObjectException not thrown: file must exist
			if ( empty( $params['overwriteDest'] ) ) {
				$status->fatal( 'backend-fail-alreadyexists', $params['dst'] );
				return $status;
			}
		} catch ( NoSuchObjectException $e ) {
			// NoSuchObjectException thrown: file does not exist
		} catch ( InvalidResponseException $e ) {
			$status->fatal( 'backend-fail-connect' );
			return $status;
		} catch ( Exception $e ) { // some other exception?
			$status->fatal( 'backend-fail-internal' );
			return $status;
		}

		// (d) Actually copy the file to the destination
		try {
			$this->swiftcopy( $sContObj, $srcRel, $dContObj, $destRel );
		} catch ( InvalidResponseException $e ) {
			$status->fatal( 'backend-fail-connect' );
		} catch ( Exception $e ) { // some other exception?
			$status->fatal( 'backend-fail-internal' );
		}

		return $status;
	}

	/**
	 * @see FileBackend::doDeleteInternal()
	 */
	function doDeleteInternal( array $params ) {
		$status = Status::newGood();

		list( $srcCont, $srcRel ) = $this->resolveStoragePath( $params['src'] );
		if ( $srcRel === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['src'] );
			return $status;
		}

		// (a) Get a swift proxy connection
		$conn = $this->getConnection();
		if ( !$conn ) {
			$status->fatal( 'backend-fail-connect' );
			return $status;
		}

		// (b) Check the source container
		try {
			$sContObj = $this->get_container( $conn, $srcCont );
		} catch ( NoSuchContainerException $e ) {
			$status->fatal( 'backend-fail-delete', $params['src'] );
			return $status;
		} catch ( InvalidResponseException $e ) {
			$status->fatal( 'backend-fail-connect' );
			return $status;
		} catch ( Exception $e ) { // some other exception?
			$status->fatal( 'backend-fail-internal' );
			return $status;
		}

		// (c) Actually delete the object
		try {
			$sContObj->delete_object( $srcRel );
		} catch ( NoSuchObjectException $e ) {
			if ( empty( $params['ignoreMissingSource'] ) ) {
				$status->fatal( 'backend-fail-delete', $params['src'] );
			}
		} catch ( InvalidResponseException $e ) {
			$status->fatal( 'backend-fail-connect' );
		} catch ( Exception $e ) { // some other exception?
			$status->fatal( 'backend-fail-internal' );
		}

		return $status;
	}

	/**
	 * @see FileBackend::doCopyInternal()
	 */
	function doCreateInternal( array $params ) {
		$status = Status::newGood();

		list( $dstCont, $destRel ) = $this->resolveStoragePath( $params['dst'] );
		if ( $destRel === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dst'] );
			return $status;
		}

		// (a) Get a swift proxy connection
		$conn = $this->getConnection();
		if ( !$conn ) {
			$status->fatal( 'backend-fail-connect' );
			return $status;
		}

		// (b) Check the destination container
		try {
			$dContObj = $conn->get_container( $conn, $dstCont );
		} catch ( NoSuchContainerException $e ) {
			$status->fatal( 'backend-fail-create', $params['dst'] );
			return $status;
		} catch ( InvalidResponseException $e ) {
			$status->fatal( 'backend-fail-connect' );
			return $status;
		} catch ( Exception $e ) { // some other exception?
			$status->fatal( 'backend-fail-internal' );
			return $status;
		}

		// (c) Check if the destination object already exists
		try {
			$dContObj->get_object( $destRel ); // throws NoSuchObjectException
			// NoSuchObjectException not thrown: file must exist
			if ( empty( $params['overwriteDest'] ) ) {
				$status->fatal( 'backend-fail-alreadyexists', $params['dst'] );
				return $status;
			}
		} catch ( NoSuchObjectException $e ) {
			// NoSuchObjectException thrown: file does not exist
		} catch ( InvalidResponseException $e ) {
			$status->fatal( 'backend-fail-connect' );
			return $status;
		} catch ( Exception $e ) { // some other exception?
			$status->fatal( 'backend-fail-internal' );
			return $status;
		}

		// (d) Actually create the object
		try {
			$obj = $dContObj->create_object( $destRel );
			$obj->write( $params['content'] );
		} catch ( BadContentTypeException $e ) {
			$status->fatal( 'backend-fail-contenttype', $params['dst'] );
		} catch ( InvalidResponseException $e ) {
			$status->fatal( 'backend-fail-connect' );
		} catch ( Exception $e ) { // some other exception?
			$status->fatal( 'backend-fail-internal' );
		}

		return $status;
	}

	/**
	 * @see FileBackend::prepate()
	 */
	function prepare( array $params ) {
		$status = Status::newGood();
		// @TODO: create containers as needed
		return $status; // badgers? We don't need no steenking badgers!
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
		list( $srcCont, $srcRel ) = $this->resolveStoragePath( $params['src'] );
		if ( $srcRel === null ) {
			return false; // invalid storage path
		}

		$conn = $this->getConnection();
		if ( !$conn ) {
			$status->fatal( 'backend-fail-connect' );
			return $status;
		}

		try {
			$container = $this->get_container( $conn, $srcCont );
			$container->get_object( $srcRel );
			$exists = true;
		} catch ( NoSuchContainerException $e ) {
			$exists = false;
		} catch ( NoSuchObjectException $e ) {
			$exists = false;
		} catch ( Exception $e ) { // some other exception?
			$exists = false; // fail vs not exists?
		}

		return $exists;
	}

	/**
	 * @see FileBackend::getFileTimestamp()
	 */
	function getFileTimestamp( array $params ) {
		list( $srcCont, $srcRel ) = $this->resolveStoragePath( $params['src'] );
		if ( $srcRel === null ) {
			return false; // invalid storage path
		}

		$conn = $this->getConnection();
		if ( !$conn ) {
			$status->fatal( 'backend-fail-connect' );
			return $status;
		}

		try {
			$container = $this->get_container( $conn, $srcCont);
			$obj = $container->get_object( $srcRel );
		} catch ( NoSuchContainerException $e ) {
			$obj = NULL;
		} catch ( NoSuchObjectException $e ) {
			$obj = NULL;
		} catch ( Exception $e ) { // some other exception?
			$obj = NULL; // fail vs not exists?
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

		$conn = $this->getConnection();
		if ( !$conn ) {
			return null;
		}

		// @TODO: return an Iterator class that pages via list_objects()
		try {
			$container = $this->get_container( $conn, $dirc );
			$files = $container->list_objects( 0, NULL, $dir );
		} catch ( NoSuchContainerException $e ) {
			$files = array();
		} catch ( NoSuchObjectException $e ) {
			$files = array();
		} catch ( Exception $e ) { // some other exception?
			$files = null;
		}

		// if there are no files matching the prefix, return empty array
		return $files;
	}

	/**
	 * @see FileBackend::getLocalCopy()
	 */
	function getLocalCopy( array $params ) {
		list( $srcCont, $srcRel ) = $this->resolveStoragePath( $params['src'] );
		if ( $srcRel === null ) {
			return null;
		}

		// Get source file extension
		$ext = FileBackend::extensionFromPath( $srcRel );
		// Create a new temporary file...
		$tmpFile = TempFSFile::factory( wfBaseName( $srcRel ) . '_', $ext );
		if ( !$tmpFile ) {
			return null;
		}
		$tmpPath = $tmpFile->getPath();

		$conn = $this->getConnection();
		if ( !$conn ) {
			return null;
		}

		try {
			$cont = $this->get_container( $conn, $srcCont );
			$obj = $cont->get_object( $srcRel );
			$obj->save_to_filename( $tmpPath );
		} catch ( NoSuchContainerException $e ) {
			$tmpFile = null;
		} catch ( NoSuchObjectException $e ) {
			$tmpFile = null;
		} catch ( IOException $e ) {
			$tmpFile = null;
		} catch ( Exception $e ) { // some other exception?
			$tmpFile = null;
		}

		return $tmpFile;
	}

	/**
	 * Get a connection to the swift proxy
	 *
	 * @return CF_Connection|null
	 */
	protected function getConnection() {
		if ( $this->conn === false ) {
			return null; // failed last attempt
		}
		// Authenticate with proxy and get a session key.
		// Session keys expire after a while, so we renew them periodically.
		if ( $this->conn === null || ( time() - $this->connStarted ) > $this->connTTL ) {
			try {
				$this->auth->authenticate();
				$this->conn = new CF_Connection( $this->auth );
				$this->connStarted = time();
			} catch ( AuthenticationException $e ) {
				$this->conn = false; // don't keep re-trying
			} catch ( InvalidResponseException $e ) {
				$this->conn = false; // don't keep re-trying
			}
		}
		return $this->conn;
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
}
