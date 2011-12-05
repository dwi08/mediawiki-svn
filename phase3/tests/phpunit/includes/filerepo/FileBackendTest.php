<?php

class FileBackendTest extends MediaWikiTestCase {
	private $backend, $filesToPrune, $pathsToPrune;

	function setUp() {
		parent::setUp();
		$this->backend = new FSFileBackend( array(
			'name'        => 'localtesting',
			'lockManager' => 'fsLockManager',
			'containerPaths' => array(
				'cont1' => wfTempDir() . '/localtesting/cont1',
				'cont2' => wfTempDir() . '/localtesting/cont2' )
		) );
		$this->filesToPrune = array();
		$this->pathsToPrune = array();
	}

	private function basePath() {
		return 'mwstore://localtesting';
	}

	/**
	 * @dataProvider provider_testStore
	 */
	public function testStore( $op, $source, $dest ) {
		$this->filesToPrune[] = $source;
		$this->pathsToPrune[] = $dest;

		file_put_contents( $source, "Unit test file" );
		$status = $this->backend->doOperation( $op );

		$this->assertEquals( true, $status->isOK(),
			"Store from $source to $dest succeeded." );
		$this->assertEquals( true, $status->isGood(),
			"Store from $source to $dest succeeded without warnings." );
		$this->assertEquals( true, file_exists( $source ),
			"Source file $source still exists." );
		$this->assertEquals( true, $this->backend->fileExists( array( 'src' => $dest ) ),
			"Destination file $dest exists." );

		$props1 = FSFile::getPropsFromPath( $source );
		$props2 = $this->backend->getFileProps( array( 'src' => $dest ) );
		$this->assertEquals( $props1, $props2,
			"Source and destination have the same props." );
	}

	public function provider_testStore() {
		$cases = array();

		$tmpName = TempFSFile::factory( "unittests_", 'txt' )->getPath();
		$toPath = $this->basePath() . '/cont1/fun/obj1.txt';
		$op = array( 'op' => 'store', 'src' => $tmpName, 'dst' => $toPath );
		$cases[] = array(
			$op, // operation
			$tmpName, // source
			$toPath, // dest
		);

		$op['overwriteDest'] = true;
		$cases[] = array(
			$op, // operation
			$tmpName, // source
			$toPath, // dest
		);

		return $cases;
	}

	/**
	 * @dataProvider provider_testCopy
	 */
	public function testCopy() {
	
	}

	/**
	 * @dataProvider provider_testMove
	 */
	public function testMove() {
	
	}

	/**
	 * @dataProvider provider_testDelete
	 */
	public function testDelete() {
	
	}

	/**
	 * @dataProvider provider_testCreate
	 */
	public function testCreate() {
	
	}

	/**
	 * @dataProvider provider_testConcatenate
	 */
	public function testConcatenate() {
	
	}

	/**
	 * @dataProvider provider_testPrepare
	 */
	public function testPrepare() {
	
	}

	/**
	 * @dataProvider provider_testSecure
	 */
	public function testSecure() {
	
	}

	/**
	 * @dataProvider provider_testClean
	 */
	public function testClean() {
	
	}

	/**
	 * @dataProvider provider_testGetLocalCopy
	 */
	public function testGetLocalCopy() {
	
	}

	function tearDown() {
		parent::tearDown();
		foreach ( $this->filesToPrune as $file ) {
			@unlink( $file );
		}
		foreach ( $this->pathsToPrune as $file ) {
			$this->backend->delete( array( 'src' => $file ) );
		}
		$this->backend = null;
		$this->filesToPrune = array();
		$this->pathsToPrune = array();
	}
}
