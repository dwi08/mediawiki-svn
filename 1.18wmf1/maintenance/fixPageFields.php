<?php
/**
 * Correct page fields with the revision table (page_latest=0)
 */

require_once( dirname( __FILE__ ) . '/Maintenance.php' );

class FixPageFields extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Fix broken page rows with page_latest=0";
	}

	public function execute() {
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select( 'page',
			array( 'page_namespace', 'page_title', 'page_id' ),
			array( 'page_len' => 0, 'page_latest' => 0 ),
			__METHOD__
		);
		$fixLog = '';
		$found = $fixed = 0;
		foreach ( $res as $row ) {
			$this->output( "Found page {$row->page_id}\n" );
			$title = Title::newFromRow( $row );
			$revRow = $dbw->selectRow(
				array( 'page', 'revision' ),
				Revision::selectFields(),
				array( 'page_id' => $row->page_id, 'page_id = rev_page',  ),
				__METHOD__,
				array( 'ORDER BY' => 'rev_timestamp DESC' )
			);
			if ( $revRow ) {
				$article = new WikiPage( $title );
				$revision = Revision::newFromRow( $revRow );

				$dbw->begin();
				$article->updateRevisionOn( $dbw, $revision, 0 /* page_latest */ );
				if ( $revision->getParentId() ) { // updateRevisionOn() will set page_is_new=1
					$dbw->update( 'page',
						array( 'page_is_new' => 0 ), // this is NOT new
						array( 'page_id' => $row->page_id ),
						__METHOD__
					);
				}
				$dbw->commit();

				$fixed++;
				$fixLog .= $row->page_id . "\n";
			}
			wfWaitForSlaves();
			$found++;
		}
		file_put_contents( "fixPageFields-" . wfWikiID() . '-' . wfTimestampNow(), $fixLog );
		$this->output( "Done! Found $found rows and fixed $fixed rows.\n" );
	}
}

$maintClass = "FixPageFields";
require_once( RUN_MAINTENANCE_IF_MAIN );
