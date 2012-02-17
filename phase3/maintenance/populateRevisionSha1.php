<?php
/**
 * Fills the rev_sha1 and ar_sha1 columns of revision
 * and archive tables for revisions created before MW 1.19.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @ingroup Maintenance
 */

require_once( dirname( __FILE__ ) . '/Maintenance.php' );

class PopulateRevisionSha1 extends LoggedUpdateMaintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Populates the rev_sha1 and ar_sha1 fields";
		$this->setBatchSize( 200 );
	}

	protected function getUpdateKey() {
		return 'populate rev_sha1';
	}

	protected function doDBUpdates() {
		$db = $this->getDB( DB_MASTER );

		if ( !$db->tableExists( 'revision' ) ) {
			$this->error( "revision table does not exist", true );
		} elseif ( !$db->tableExists( 'archive' ) ) {
			$this->error( "archive table does not exist", true );
		}

		$this->output( "Populating rev_sha1 column\n" );
		$rc = $this->doSha1Updates( 'revision', 'rev_id', 'rev' );

		$this->output( "Populating ar_sha1 column\n" );
		$ac = $this->doSha1Updates( 'archive', 'ar_rev_id', 'ar' );
		$this->output( "Populating ar_sha1 column legacy rows\n" );
		$ac += $this->doSha1LegacyUpdates();

		$this->output( "rev_sha1 and ar_sha1 population complete [$rc revision rows, $ac archive rows].\n" );
		return true;
	}

	/**
	 * @param $table string
	 * @param $idCol
	 * @param $prefix string
	 * @return Integer Rows changed
	 */
	protected function doSha1Updates( $table, $idCol, $prefix ) {
		$db = $this->getDB( DB_MASTER );
		$start = $db->selectField( $table, "MIN($idCol)", false, __METHOD__ );
		$end = $db->selectField( $table, "MAX($idCol)", false, __METHOD__ );
		if ( !$start || !$end ) {
			$this->output( "...$table table seems to be empty.\n" );
			return true;
		}

		$count = 0;
		# Do remaining chunk
		$end += $this->mBatchSize - 1;
		$blockStart = $start;
		$blockEnd = $start + $this->mBatchSize - 1;
		while ( $blockEnd <= $end ) {
			$this->output( "...doing $idCol from $blockStart to $blockEnd\n" );
			$cond = "$idCol BETWEEN $blockStart AND $blockEnd
				AND $idCol IS NOT NULL AND {$prefix}_sha1 = ''";
			$res = $db->select( $table, '*', $cond, __METHOD__ );

			$db->begin();
			foreach ( $res as $row ) {
				if ( $this->upgradeRow( $row, $db, $table, $idCol, $prefix ) ) {
					$count++;
				}
			}
			$db->commit();

			$blockStart += $this->mBatchSize;
			$blockEnd += $this->mBatchSize;
			wfWaitForSlaves();
		}
		return $count;
	}
	
	protected function doSha1LegacyUpdates() {
		$count = 0;
		$db = $this->getDB( DB_MASTER );
		$res = $db->select( 'archive', '*', array( 'ar_rev_id IS NULL' ), __METHOD__ );

		$updateSize = 0;
		$db->begin();
		foreach ( $res as $row ) {
			if ( $this->upgradeRow( $row, 'archive', 'ar_timestamp', 'ar' ) ) {
				++$count;
			}
			if ( ++$updateSize >= 100 ) {
				$updateSize = 0;
				$db->commit();
				$this->output( "Commited row with ar_timestamp={$row->ar_timestamp}\n" );
				wfWaitForSlaves();
				$db->begin();
			}
		}
		$db->commit();
	}

	protected function upgradeRow( $row, $table, $idCol, $prefix ) {
		$db = $this->getDB( DB_MASTER );
		if ( $table === 'archive' ) {
			$rev = Revision::newFromArchiveRow( $row );
		} else {
			$rev = new Revision( $row );
		}
		$text = $rev->getRawText();
		if ( !is_string( $text ) ) {
			# This should not happen, but sometimes does (bug 20757)
			$this->output( "Text of revision with {$idCol}={$row->$idCol} unavailable!\n" );
			return false;
		} else {
			$db->update( $table,
				array( "{$prefix}_sha1" => Revision::base36Sha1( $text ) ),
				array( $idCol => $row->$idCol ),
				__METHOD__ 
			);
			return true;
		}
	}
}

$maintClass = "PopulateRevisionSha1";
require_once( RUN_MAINTENANCE_IF_MAIN );
