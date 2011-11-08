<?php
require_once( dirname( __FILE__ ) . '/Maintenance.php' );

class FindBug32198 extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Find rows with ipb_by=0 and ibp_by_text=<IP address>";
	}

	function execute() {
		$epoch = '20110900000000'; // Sept 2011

		$db = wfGetDB( DB_SLAVE );
		$res = $db->select( 'ipblocks', '*',
			array( 'ipb_by' => 0, 'ipb_timestamp > ' . $db->addQuotes( $db->timestamp( $epoch ) ) ),
			__METHOD__
		);
		foreach ( $res as $row ) {
			if ( IP::isIPAddress( $row->ipb_by_text ) ) {
				$this->output( "{$row->ipb_id} {$row->ipb_by_text} {$row->ipb_timestamp}\n" );
			}
		}
	}
}

$maintClass = "FindBug32198";
require_once( RUN_MAINTENANCE_IF_MAIN );
