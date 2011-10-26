<?php

function update_images_bug_28348( $start = null ) {
	echo "Correcting fi_img_timestamp column in flaggedimages\n";
	
	$BATCH_SIZE = 3000;
	
	$logFilePath = "bug28348-fixed-" . wfWikiID();
	if ( file_exists( $logFilePath ) ) {
		echo "Already done\n";
		return;
	}

	$db = wfGetDB( DB_MASTER );
	$dbCommons = wfGetDB( DB_SLAVE, array(), 'commonswiki' );

	if( $start === null ) {
		$start = $db->selectField( 'flaggedimages', 'MIN(fi_rev_id)', false, __FUNCTION__ );
	}
	$end = $db->selectField( 'flaggedimages', 'MAX(fi_rev_id)', false, __FUNCTION__ );
	if( is_null( $start ) || is_null( $end ) ) {
		echo "...flaggedimages table seems to be empty.\n";
		return;
	}
	# Do remaining chunk
	$end += $BATCH_SIZE - 1;
	$blockStart = $start;
	$blockEnd = $start + $BATCH_SIZE - 1;

	$logDump = '';

	$count = $changed = 0;
	while( $blockEnd <= $end ) {
		echo "...doing fi_rev_id from $blockStart to $blockEnd\n";
		$cond = "fi_rev_id BETWEEN $blockStart AND $blockEnd AND fi_img_timestamp IS NOT NULL" .
			" AND img_name IS NULL AND oi_name IS NULL"; // optimize
		$res = $db->select( array( 'flaggedimages', 'image', 'oldimage' ),
			'*',
			$cond,
			__FUNCTION__,
			array(),
			array( // skip OK references to local files
				'image'    => array( 'LEFT JOIN',
					'img_sha1 = fi_img_sha1 AND img_timestamp = fi_img_timestamp' ),
				'oldimage' => array( 'LEFT JOIN',
					'oi_sha1 = fi_img_sha1 AND oi_timestamp = fi_img_timestamp' )
			)
		);
		
		$didAnything = false;
		$db->begin();
		# Go through and clean up broken file rows
		foreach ( $res as $row ) {
			$count++;
			$sha1 = $row->fi_img_sha1;
			$fi_img_timestamp = trim( $row->fi_img_timestamp ); // clear pad garbage
			if ( !$fi_img_timestamp ) {
				continue; // nothing to check
			}
			$time = wfTimestamp( TS_MW, $fi_img_timestamp );
			$offsetTime = wfTimestamp( TS_MW, wfTimestamp( TS_UNIX, $time ) + 1 );
			# WMF optimization
			$ok = $dbCommons->select( 'image', '1',
				array( 'img_sha1' => $sha1, 'img_timestamp' => $dbCommons->timestamp( $time ) ) );
			if ( $ok ) {
				continue; // current version is it; skip since it must be OK
			}
			# Check if the file exists with offset time...
			$file = RepoGroup::singleton()->findFileFromKey( $sha1, array( 'time' => $offsetTime ) );
			if ( $file ) { // doesn't exist?
				# Confirm that fi_img_timestamp value is off by 1 second...
				$file = RepoGroup::singleton()->findFileFromKey( $sha1, array( 'time' => $time ) );
				if ( !$file ) {
					echo "fixed file {$row->fi_name} reference in rev ID {$row->fi_rev_id}\n";
					# Fix the fi_img_timestamp value...
					$db->update( 'flaggedimages',
						array( 'fi_img_timestamp' => $db->timestamp( $offsetTime ) ),
						array( 'fi_rev_id' => $row->fi_rev_id, 'fi_name' => $row->fi_name ),
						__METHOD__
					);
					$changed++;
					$didAnything = true;
					$logDump .= "{$row->fi_rev_id} {$row->fi_name} {$fi_img_timestamp}\n";
				}
			}
		}
		if ( $logDump != '' ) {
			file_put_contents( $logFilePath, $logDump, FILE_APPEND );
		}
		$logDump = '';
		$db->commit();

		$db->freeResult( $res );
		$blockStart += $BATCH_SIZE;
		$blockEnd += $BATCH_SIZE;
		if ( $didAnything ) {
			wfWaitForSlaves( 5 );
		}
	}

	echo "fi_img_timestamp column fixes complete ... {$count} rows [{$changed} changed]\n";
}
