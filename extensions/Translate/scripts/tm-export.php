<?php
/**
 * Script to export messages for translatetoolkit translation memory
 *
 * @author Niklas Laxstrom
 *
 * @copyright Copyright © 2010, Niklas Laxström
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 * @file
 */

$optionsWithArgs = array( 'target' );

$dir = dirname( __FILE__ ); $IP = "$dir/../../..";
@include("$dir/../../CorePath.php"); // Allow override
define( 'TRANSLATE_CLI', 1 );
require_once( "$IP/maintenance/Maintenance.php" );


class TMExport extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Script to export messages for translatetoolkit translation memory';
		#$this->addOption( 'target', 'where to put the sqlite file', 'required', 'target' );
		$this->addArg( 'target', 'preinitialised sqlite db', 'required' );
	}

	public function execute() {
		global $wgContLang;

		global $wgSQLiteDataDir;
		$target = $this->getArg( 0 );
		$target_dirname = dirname( $target );
		$target_basename = basename( $target );
		swap( $wgSQLiteDataDir, $target_dirname );
		$dbw = new DatabaseSqlite( 'server', 'user', 'password', $target_basename );
		swap( $wgSQLiteDataDir, $target_dirname );

		$dbw->setFlag(DBO_TRX); // HUGE speed improvement

		$groups = MessageGroups::singleton()->getGroups();
		// TODO: encapsulate list of valid language codes
		$languages = Language::getLanguageNames( false );
		unset( $languages['en'] );
		$codes = array_keys( $languages );

		foreach( $groups as $id => $group ) {
			if ( $group->isMeta() ) continue;
			$this->output( "Processing: {$group->getLabel()} ", $id );
			$capitalized = MWNamespace::isCapitalized( $group->getNamespace() );
			$ns_text = $wgContLang->getNsText( $group->getNamespace() );

			foreach ( $group->load('en') as $key => $definition ) {
				// TODO: would be nice to do key normalisation closer to the message groups, to avoid transforming back and forth.
				// But how to preserve the original keys...
				$key = strtr( $key, ' ', '_' );
				$key = $capitalized ? $wgContLang->ucfirst( $key ) : $key;

				// All the translations what there might be
				$keys_for_message = array();
				foreach ( $codes as $code ) $keys_for_message[] = $key . '/' . $code;

				$dbr = wfGetDB( DB_SLAVE );
				$tables = array( 'page', 'revision', 'text' );
				// selectFields to stfu Revision class
				$vars = array_merge(Revision::selectTextFields(), array( 'page_title' ), Revision::selectFields() );
				$conds = array(
					'page_latest = rev_id',
					'rev_text_id = old_id',
					'page_namespace' => $group->getNamespace(),
					'page_title' => $keys_for_message
				);

				$res = $dbr->select( $tables, $vars, $conds, __METHOD__ );
				// Assure that there is at least one translation
				if ( $res->numRows() < 1 ) continue;

				$insert = array(
					'text' => $definition,
					'context' => "$ns_text:$key",
					'length' => strlen($definition),
					'lang' => 'en'
				);

				$source_id = $dbw->selectField( '`sources`', 'sid', $insert, __METHOD__ );
				if ( $source_id === false ) {
					$dbw->insert( '`sources`', $insert, __METHOD__ );
					$source_id = $dbw->insertId();
				}

				$this->output( ' ', $id );

				foreach ( $res as $row ) {
					list( , $code ) = TranslateUtils::figureMessage( $row->page_title );
					$revision = new Revision($row);
					$insert = array(
						'text' => $revision->getText(),
						'lang' => $code,
						'time' => wfTimestamp(),
						'sid' => $source_id );
					// We only do SQlite which doesn't need to know unique indexes
					$dbw->replace( '`targets`', null, $insert, __METHOD__ );
				}
				$this->output( "{$res->numRows()}", $id );

			} // each translation>

			$dbw->commit();
		} // each group>
	}

}

$maintClass = 'TMExport';
require_once( DO_MAINTENANCE );
