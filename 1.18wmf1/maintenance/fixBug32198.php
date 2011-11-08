<?php
require_once( dirname( __FILE__ ) . '/Maintenance.php' );

class FixBug32198 extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Fix rows with ipb_by=0 and ibp_by_text=<IP address>";
	}

	function execute() {
		global $wgDBname;
		$epoch = '20110900000000'; // Sept 2011

		if ( $wgDBname !== 'metawiki' ) {
			die( "Must run on metawiki.\n" ); // sanity
		}

		$db = wfGetDB( DB_SLAVE );
		$res = $db->select( 'logging', '*',
			array(
				'log_type'      => 'suppress',
				'log_action'    => 'setstatus',
				'log_namespace' => NS_USER, // sanity
				'log_timestamp > ' . $db->addQuotes( $db->timestamp( $epoch ) ) ),
			__METHOD__,
			array( 'ORDER BY' => 'log_timestamp DESC' ) // different admins may change blocks
		);

		$this->output( "Scanning {$res->numRows()} CentralAuth suppress block log rows...\n" );

		$blocksFixed = 0;
		foreach ( $res as $row ) {
			$m = explode( '@', $row->log_title );
			if ( count( $m ) != 2 || $m[1] !== 'global' ) {
				continue; // not global
			}
			$blockeeName = Title::makeTitle( NS_USER, $m[0] )->getText(); // the bad username
			// Given the ORDER BY clause in the logging table query, we can make sure
			// that the last admin to change the block status of a user "owns" it via
			// ipb_by_text. If we hit changes to the block status for the same user again
			// we will have already set ipb_by_text to a non-IP so it would be skipped.
			$blockee = new CentralAuthUser( $blockeeName ); // the bad user

			$blockerName = $row->log_user_text; // the blocking admin

			// Search block log on all wikis with local accounts for this user...
			foreach ( $blockee->listAttached() as $wiki ) { // assumes user was not unattached
				$wikiDB = wfGetDB( DB_MASTER, array(), $wiki ); // DB used by $wiki
				$sRes = $wikiDB->select( 'ipblocks', '*',
					array(
						'ipb_address' => $blockeeName,
						'ipb_by'      => 0,
						'ipb_timestamp > ' . $wikiDB->addQuotes( $wikiDB->timestamp( $epoch ) ) ),
					__METHOD__
				);

				$rowsFixedForBlock = 0; // per wiki
				foreach ( $sRes as $row ) {
					if ( IP::isIPAddress( $row->ipb_by_text ) ) { // broken row
						$wikiDB->update( 'ipblocks',
							array( 'ipb_by_text' => $blockerName ),
							array( 'ipb_id'      => $row->ipb_id, 'ipb_by' => 0 ), // ipb_by for sanity
							__METHOD__
						);
						$rowsFixedForBlock++;
					}
				}

				if ( $rowsFixedForBlock > 0 ) { // should be 1 row
					$this->output( "$rowsFixedForBlock row(s) for `{$blockeeName}` on $wiki.\n" );
				}

				unset( $wikiDB ); // outer loop is fast, here for sanity
			}

			$blocksFixed++;
		}

		$this->output( "...done [fixed $blocksFixed global suppress blocks]\n" );
	}
}

$maintClass = "FixBug32198";
require_once( RUN_MAINTENANCE_IF_MAIN );
