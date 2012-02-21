<?php
/**
 * ApiViewRatingsArticleFeedbackv5 class
 *
 * @package    ArticleFeedback
 * @subpackage Api
 * @author     Greg Chiasson <greg@omniti.com>
 * @author     Reha Sterbin <reha@omniti.com>
 * @version    $Id: ApiViewRatingsArticleFeedbackv5.php 103335 2011-11-16 16:25:53Z gregchiasson $
 */

/**
 * Utility methods used by api calls
 *
 * ApiArticleFeedback and ApiQueryArticleFeedback don't descend from the same
 * parent, which is why these are all static methods instead of just a parent
 * class with inheritable methods. I don't get it either.
 *
 * @package    ArticleFeedback
 * @subpackage Api
 */
class ApiArticleFeedbackv5Utils {
	/**
	 * Returns whether feedback is enabled for this page
	 *
	 * @param  $params array the params
	 * @return bool
	 */
	public static function isFeedbackEnabled( $params ) {
		global $wgArticleFeedbackv5Namespaces;
		$title = Title::newFromID( $params['pageid'] );
		if (
			// not an existing page?
			is_null( $title )
			// Namespace not a valid ArticleFeedback namespace?
			|| !in_array( $title->getNamespace(), $wgArticleFeedbackv5Namespaces )
			// Page a redirect?
			|| $title->isRedirect()
		) {
			// ...then feedback disabled
			return false;
		}
		return true;
	}

	/**
	 * Returns the revision limit for a page
	 *
	 * @param  $pageId int the page id
	 * @return int the revision limit
	 */
	public static function getRevisionLimit( $pageId ) {
		global $wgArticleFeedbackv5RatingLifetime;
		$dbr = wfGetDB( DB_SLAVE );
		$revision = $dbr->selectField(
			'revision', 'rev_id',
			array( 'rev_page' => $pageId ),
			__METHOD__,
			array(
				'ORDER BY' => 'rev_id DESC',
				'LIMIT' => 1,
				'OFFSET' => $wgArticleFeedbackv5RatingLifetime - 1
			)
		);
		return $revision ? intval( $revision ) : 0;
	}

	/**
	 * Gets the known feedback fields
	 *
	 * @return ResultWrapper the rows in the aft_article_field table
	 */
	public static function getFields() {
		global $wgMemc;

		$key = wfMemcKey( 'articlefeedbackv5', 'getFields' );
		$cached = $wgMemc->get( $key );

		if ( $cached != '' ) {
			return $cached;
		} else {
			$rv = array();
			$dbr = wfGetDB( DB_SLAVE );
			$rows = $dbr->select(
				'aft_article_field',
				array(
					'afi_name',
					'afi_id',
					'afi_data_type',
					'afi_bucket_id'
				),
				null,
				__METHOD__
			);

			foreach ( $rows as $row ) {
				$rv[] = array(
					'afi_name' => $row->afi_name,
					'afi_id' => $row->afi_id,
					'afi_data_type' => $row->afi_data_type,
					'afi_bucket_id' => $row->afi_bucket_id
				);
			}

			// An hour? That might be reasonable for a cache time.
			$wgMemc->set( $key, $rv, 60 * 60 );
		}

		return $rv;
	}

	/**
	 * Gets the known feedback options
	 *
	 * Pulls all the rows in the aft_article_field_option table, then
	 * arranges them like so:
	 *   {field id} => array(
	 *       {option id} => {option name},
	 *   ),
	 *
	 * @return array the rows in the aft_article_field_option table
	 */
	public static function getOptions() {
		global $wgMemc;

		$key = wfMemcKey( 'articlefeedbackv5', 'getOptions' );
		$cached = $wgMemc->get( $key );

		if ( $cached != '' ) {
			return $cached;
		} else {
			$rv = array();
			$dbr = wfGetDB( DB_SLAVE );
			$rows = $dbr->select(
				'aft_article_field_option',
				array(
					'afo_option_id',
					'afo_field_id',
					'afo_name'
				),
				null,
				__METHOD__
			);
			foreach ( $rows as $row ) {
				$rv[$row->afo_field_id][$row->afo_option_id] = $row->afo_name;
			}
			// An hour? That might be reasonable for a cache time.
			$wgMemc->set( $key, $rv, 60 * 60 );
		}
		return $rv;
	}

	/**
	 * Must pass in a useable database handle in a transaction - since all
	 * counts MUST be incremented in the same transaction as the data changing
	 * or the counts will be off
	 *
	 * @param $pageId      int   the ID of the page (page.page_id)
	 * @param $filters     array  an array of filter name => direction pairs
	 *                            direction 1 = increment, -1 = decrement
	 */
	public static function updateFilterCounts( $dbw, $pageId, $filters ) {

		// Don't do anything unless we have filters to process.
		if( empty( $filters ) || count($filters) < 1 ) {
			return; 
		}

		foreach ( $filters as $filter => $direction) {
			$rows[] = array(
				'afc_page_id'      => $pageId,
				'afc_filter_name'  => $filter,
				'afc_filter_count' => 0
			);
		}

		# Try to insert the record, but ignore failures.
		# Ensures the count row exists.
		$dbw->insert(
			'aft_article_filter_count',
			$rows,
			__METHOD__,
			array( 'IGNORE' )
		);

		foreach ( $filters as $filter => $direction) {
			$value = ($direction > 0) ? 'afc_filter_count + 1' : 'afc_filter_count - 1';

			# Update each row with the new count.
			$dbw->update(
				'aft_article_filter_count',
				array( "afc_filter_count = $value" ),
				array(
					'afc_page_id'     => $pageId,
					'afc_filter_name' => $filter
				),
				__METHOD__
			);

		}
	}

	/**
	 * Adds an activity item to the global log under the articlefeedbackv5
	 *
	 * @param $type      string the type of activity we'll be logging
	 * @param $pageId    int    the id of the page so we can look it up
	 * @param $itemId    int    the id of the feedback item, used to build permalinks
	 * @param $notes     string any notes that were stored with the activity
	 */
	public static function logActivity( $type, $pageId, $itemId, $notes) {

		// These are our valid activity log actions
		$valid = array( 'oversight', 'unoversight', 'hidden', 'unhidden',
				'decline', 'request', 'unrequest','flag','unflag' );

		// if we do not have a valid action, return immediately
		if ( !in_array( $type, $valid )) {
			return;
		}

		// create a title for the page
		$page = Title::newFromID( $pageId );
		$title = $page->getPartialURL();

		// to build our permalink, use the feedback entry key 
		$title = SpecialPage::getTitleFor( 'ArticleFeedbackv5', "$title/$itemId" );

		// Make sure our notes are not too long - we won't error, just hard substr it
		global $wgArticleFeedbackv5MaxActivityNoteLength;
		$notes = substr($notes, 0, $wgArticleFeedbackv5MaxActivityNoteLength);

		$log = new LogPage( 'articlefeedbackv5' );
		// comments become the notes section from the feedback
		$log->addEntry( $type, $title, $notes );

		// update our log count by 1
		$dbw = wfGetDB( DB_MASTER );
		$dbw->begin();

		$dbw->update(
			'aft_article_feedback',
			array( 'af_activity_count = af_activity_count + 1' ),
			array(
				'af_id' => $itemId
			),
			__METHOD__
		);

		$dbw->commit();
	}
}

