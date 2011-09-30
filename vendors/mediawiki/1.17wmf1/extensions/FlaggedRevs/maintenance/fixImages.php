<?php

if ( getenv( 'MW_INSTALL_PATH' ) ) {
    $IP = getenv( 'MW_INSTALL_PATH' );
} else {
    $IP = dirname(__FILE__).'/../../..';
}

$options = array( 'help' );
require "$IP/maintenance/commandLine.inc";

function update_flaggedimages( $start = null ) {
	echo "Removing unreferenced flaggedimages columns\n";
	
	$BATCH_SIZE = 1000;
	
	$db = wfGetDB( DB_MASTER );
	
	if( $start === null ) {
		$start = $db->selectField( 'flaggedimages', 'MIN(fi_rev_id)', false, __FUNCTION__ );
	}
	$end = $db->selectField( 'flaggedimages', 'MAX(fi_rev_id)', false, __FUNCTION__ );
	if( is_null( $start ) || is_null( $end ) ){
		echo "...flaggedimages table seems to be empty.\n";
		return;
	}
	# Do remaining chunk
	$end += $BATCH_SIZE - 1;
	$blockStart = $start;
	$blockEnd = $start + $BATCH_SIZE - 1;
	$nulled = 0;
	while( $blockEnd <= $end ) {
		echo "...doing fi_rev_id from $blockStart to $blockEnd\n";
		$cond = "fi_rev_id BETWEEN $blockStart AND $blockEnd";
		$db->begin();
		$db->update( 'flaggedimages',
			array( 'fi_img_timestamp' => '' ),
			array( $cond, "fi_img_timestamp LIKE 'DEFAULT%'" ),
			__FUNCTION__ );
		if( $db->affectedRows() > 0 ) {
			$nulled += $db->affectedRows();
		}
		$db->commit();
		$blockStart += $BATCH_SIZE;
		$blockEnd += $BATCH_SIZE;
		wfWaitForSlaves( 5 );
	}
	echo "flaggedimages columns update complete ... [{$nulled} fixed]\n";
}

update_flaggedimages();
