<?php


class FileBackendTest extends MediaWikiTestCase {


    /**
     *
     * @dataProvider provideTestFiles
     */
    function testStore( $srcPath, $dstZone, $dstRel, $flags ) {

    }

    function provideTestFiles() {
        return array(
                     array('srcPath', 'dstZone', 'dstRel', 'flags')
        );
    }
} // end class