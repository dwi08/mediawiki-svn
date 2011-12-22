<?php

class MoodBarHooks {
	/**
	 * Adds MoodBar JS to the output if appropriate.
	 *
	 * @param $output OutputPage
	 * @param $skin Skin
	 */

	public static function onPageDisplay( &$output, &$skin ) {
		if ( self::shouldShowMoodbar( $output, $skin ) ) {
			$output->addModules( array( 'ext.moodBar.init', 'ext.moodBar.tooltip', 'ext.moodBar.core' ) );
		}

		return true;
	}
	
	/**
	 * Determines if this user has right to mark an feedback response as helpful, only the user who wrote the
	 * feedback can mark the response as helpful
	 * @param $mahaction string - mark/unmark
	 * @param $type string - the object type to be marked
	 * @param $item int - an item of $type to be marked 
	 * @param $User User Object - the User in current session
	 * @param $isAbleToMark bool - determine whether the user is able to mark the item
	 * @return bool
	 */
	public static function onMarkItemAsHelpful( $mahaction, $type, $item, $User, &$isAbleToMark ) {
		
		if ( $type == 'mbresponse' ) {
			
			switch ( $mahaction ) {
				
				case 'mark':
					$dbr = wfGetDB( DB_SLAVE );
					
					$conds = array( 'mbf_id = mbfr_mbf_id', 'mbfr_id' => intval( $item ) );
					
					if ( !$User->isAnon() ) {
						$conds['mbf_user_id'] = $User->getId();		
					}
					else {
						$conds['mbf_user_ip'] = $User->getName();
					}
					
					$res = $dbr->selectRow( array( 'moodbar_feedback', 'moodbar_feedback_response' ), 
								array( 'mbf_id' ),
								$conds,
								__METHOD__ );	
					
					if ( $res === false ) {
						$isAbleToMark = false;
					}
				break;
				
				case 'unmark':
				default:
					//We will leve the MarkAsHelpFul extension to check if the user has unmark right
				break;
			}
		}
		
		return true;
		
	}

	/**
	 * Determines whether or not we should show the MoodBar.
	 *
	 * @param $output OutputPage
	 * @param $skin Skin
	 */
	public static function shouldShowMoodbar( &$output, &$skin ) {
		// Front-end appends to header elements, which have different
		// locations and IDs in every skin.
		// Untill there is some kind of central registry of element-ids
		// that skins implement, or a fixed name for each of them, just
		// show it in Vector for now.
		if ( $skin->getSkinName() !== 'vector' ) {
			return false;
		}
		global $wgUser;
		$user = $wgUser;

		if ( $user->isAnon() ) {
			return false;
		}

		// Only show MoodBar for users registered after a certain time
		global $wgMoodBarCutoffTime;
		if ( $wgMoodBarCutoffTime &&
			$user->getRegistration() < $wgMoodBarCutoffTime )
		{
			return false;
		}

		if ( class_exists('EditPageTracking') ) {
			return ((bool)EditPageTracking::getFirstEditPage($user));
		}

		return true;
	}

	/**
	 * ResourceLoaderGetConfigVars hook
	 */
	public static function resourceLoaderGetConfigVars( &$vars ) {
		global $wgMoodBarConfig, $wgUser;
		$vars['mbConfig'] = array(
			'validTypes' => MBFeedbackItem::getValidTypes(),
			'userBuckets' => MoodBarHooks::getUserBuckets( $wgUser )
		) + $wgMoodBarConfig;
		return true;
	}

	public static function makeGlobalVariablesScript( &$vars ) {
		global $wgUser, $wgEnableEmail;
		$vars['mbEditToken'] = $wgUser->editToken();
		$vars['mbEmailEnabled'] = $wgEnableEmail;
		$vars['mbUserEmail'] = strlen( $wgUser->getEmail() ) > 0 ? true : false;
		$vars['mbIsEmailConfirmationPending'] = $wgUser->isEmailConfirmationPending(); //returns false if email authentication off, and if email is confimed already
		return true;
	}

	/**
	 * Runs MoodBar schema updates#
	 *
	 * @param $updater DatabasEUpdater
	 */
	public static function onLoadExtensionSchemaUpdates( $updater = null ) {
		$updater->addExtensionUpdate( array( 'addTable', 'moodbar_feedback',
			dirname(__FILE__).'/sql/MoodBar.sql', true ) );

		$updater->addExtensionUpdate( array( 'addField', 'moodbar_feedback',
			'mbf_user_editcount', dirname(__FILE__).'/sql/mbf_user_editcount.sql', true )
		);

		$db = $updater->getDB();
		if ( $db->tableExists( 'moodbar_feedback' ) &&
				$db->indexExists( 'moodbar_feedback', 'type_timestamp', __METHOD__ ) )
		{
			$updater->addExtensionUpdate( array( 'addIndex', 'moodbar_feedback',
				'mbf_type_timestamp_id', dirname( __FILE__ ) . '/sql/AddIDToIndexes.sql', true )
			);
		}
		$updater->addExtensionUpdate( array( 'dropIndex', 'moodbar_feedback',
			'mbf_timestamp', dirname( __FILE__ ) . '/sql/AddIDToIndexes2.sql', true )
		);

		$updater->addExtensionUpdate( array( 'addIndex', 'moodbar_feedback',
			'mbf_timestamp_id', dirname( __FILE__ ) . '/sql/mbf_timestamp_id.sql', true )
		);

		$updater->addExtensionUpdate( array( 'addField', 'moodbar_feedback',
			'mbf_hidden_state', dirname(__FILE__).'/sql/mbf_hidden_state.sql', true ) );
		
		$updater->addExtensionUpdate( array( 'addTable', 'moodbar_feedback_response',
			dirname(__FILE__).'/sql/moodbar_feedback_response.sql', true ) );

		$updater->addExtensionUpdate( array( 'addIndex', 'moodbar_feedback_response',
			'mbfr_timestamp_id', dirname( __FILE__ ) . '/sql/mbfr_timestamp_id_index.sql', true )
		);
		
		return true;
	}

	/**
	 * Gets the MoodBar testing bucket that a user is in.
	 * @param $user The user to check
	 * @return array of bucket names
	 */
	public static function getUserBuckets( $user ) {
		$id = $user->getID();
		$buckets = array();

		// No show-time bucketing yet. This method is a stub.

		sort($buckets);
		return $buckets;
	}
}
