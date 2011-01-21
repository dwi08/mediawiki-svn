<?php

class CodeRevision {
	public $mRepoId, $mRepo, $mId, $mAuthor, $mTimestamp, $mMessage, $mPaths, $mStatus, $mOldStatus, $mCommonPath;

	/**
	 * @static
	 * @param CodeRepository $repo
	 * @param  $data
	 * @return CodeRevision
	 */
	public static function newFromSvn( CodeRepository $repo, $data ) {
		$rev = new CodeRevision();
		$rev->mRepoId = $repo->getId();
		$rev->mRepo = $repo;
		$rev->mId = intval( $data['rev'] );
		$rev->mAuthor = $data['author'];
		$rev->mTimestamp = wfTimestamp( TS_MW, strtotime( $data['date'] ) );
		$rev->mMessage = rtrim( $data['msg'] );
		$rev->mPaths = $data['paths'];
		$rev->mStatus = 'new';
		$rev->mOldStatus = '';

		$common = null;
		if ( $rev->mPaths ) {
			if ( count( $rev->mPaths ) == 1 ) {
				$common = $rev->mPaths[0]['path'];
			} else {
				$first = array_shift( $rev->mPaths );
				$common = explode( '/', $first['path'] );

				foreach ( $rev->mPaths as $path ) {
					$compare = explode( '/', $path['path'] );

					// make sure $common is the shortest path
					if ( count( $compare ) < count( $common ) ) {
						list( $compare, $common ) = array( $common, $compare );
					}

					$tmp = array();
					foreach ( $common as $k => $v ) {
						if ( $v == $compare[$k] ) {
							$tmp[] = $v;
						} else {
							break;
						}
					}
					$common = $tmp;
				}
				$common = implode( '/', $common );

				array_unshift( $rev->mPaths, $first );
			}

			$rev->mPaths = CodeRevision::getPathFragments( $rev->mPaths );
		}
		$rev->mCommonPath = $common;

		// Check for ignored paths
		global $wgCodeReviewDeferredPaths;
		if ( isset( $wgCodeReviewDeferredPaths[ $repo->getName() ] ) ) {
			foreach ( $wgCodeReviewDeferredPaths[ $repo->getName() ] as $defer ) {
				if ( preg_match( $defer, $rev->mCommonPath ) ) {
					$rev->mStatus = 'deferred';
					break;
				}
			}
		}
		return $rev;
	}

	/**
	 * @static
	 * @param array $paths
	 * @return array
	 */
	public static function getPathFragments( $paths = array() ) {
		$allPaths = array();

		foreach( $paths as $path ) {
			$currentPath = "/";
			foreach( explode( '/', $path['path'] ) as $fragment ) {
				if ( $currentPath !== "/" ) {
					$currentPath .= '/';
				}

				$currentPath .= $fragment;

				$allPaths[] = array( 'path' => $currentPath, 'action' => $path['action'] ) ;
			}
		}

	    return $allPaths;
	}

	/**
	 * @static
	 * @throws MWException
	 * @param CodeRepository $repo
	 * @param  $row
	 * @return CodeRevision
	 */
	public static function newFromRow( CodeRepository $repo, $row ) {
		$rev = new CodeRevision();
		$rev->mRepoId = intval( $row->cr_repo_id );
		if ( $rev->mRepoId != $repo->getId() ) {
			throw new MWException( "Invalid repo ID in " . __METHOD__ );
		}
		$rev->mRepo = $repo;
		$rev->mId = intval( $row->cr_id );
		$rev->mAuthor = $row->cr_author;
		$rev->mTimestamp = wfTimestamp( TS_MW, $row->cr_timestamp );
		$rev->mMessage = $row->cr_message;
		$rev->mStatus = $row->cr_status;
		$rev->mOldStatus = '';
		$rev->mCommonPath = $row->cr_path;
		return $rev;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return intval( $this->mId );
	}

	/**
	 * Like getId(), but returns the result as a string, including prefix,
	 * i.e. "r123" instead of 123.
	 * @param $id
	 */
	public function getIdString( $id = null ) {
		if ( $id === null ) {
			$id = $this->getId();
		}
		return $this->mRepo->getRevIdString( $id );
	}

	/**
	 * Like getIdString(), but if more than one repository is defined
	 * on the wiki then it includes the repo name as a prefix to the revision ID
	 * (separated with a period).
	 * This ensures you get a unique reference, as the revision ID alone can be
	 * confusing (e.g. in e-mails, page titles etc.).  If only one repository is
	 * defined then this returns the same as getIdString() as there is no ambiguity.
	 *
	 * @param null $id
	 * @return
	 */
	public function getIdStringUnique( $id = null ) {
		if ( $id === null ) {
			$id = $this->getId();
		}
		return $this->mRepo->getRevIdStringUnique( $id );
	}

	/**
	 * @return int
	 */
	public function getRepoId() {
		return intval( $this->mRepoId );
	}

	/**
	 * @return
	 */
	public function getAuthor() {
		return $this->mAuthor;
	}

	/**
	 * @return
	 */
	public function getWikiUser() {
		return $this->mRepo->authorWikiUser( $this->getAuthor() );
	}

	/**
	 * @return
	 */
	public function getTimestamp() {
		return $this->mTimestamp;
	}

	/**
	 * @return
	 */
	public function getMessage() {
		return $this->mMessage;
	}

	/**
	 * @return
	 */
	public function getStatus() {
		return $this->mStatus;
	}

	/**
	 * @return
	 */
	public function getCommonPath() {
		return $this->mCommonPath;
	}

	/**
	 * List of all possible states a CodeRevision can be in
	 * @return Array
	 */
	public static function getPossibleStates() {
		return array( 'new', 'fixme', 'reverted', 'resolved', 'ok', 'deferred', 'old' );
	}

	/**
	 * List of all flags a user can mark themself as having done to a revision
	 * @return Array
	 */
	public static function getPossibleFlags() {
		return array( 'inspected', 'tested' );
	}

	/**
	 * Returns whether the provided status is valid
	 * @param String $status
	 * @return bool
	 */
	public static function isValidStatus( $status ) {
		return in_array( $status, self::getPossibleStates(), true );
	}

	/**
	 * @throws MWException
	 * @param $status String, value in CodeRevision::getPossibleStates
	 * @param $user User
	 * @return bool
	 */
	public function setStatus( $status, $user ) {
		if ( !$this->isValidStatus( $status ) ) {
			throw new MWException( "Tried to save invalid code revision status" );
		}
		// Get the old status from the master
		$dbw = wfGetDB( DB_MASTER );
		$this->mOldStatus = $dbw->selectField( 'code_rev',
			'cr_status',
			array( 'cr_repo_id' => $this->mRepoId, 'cr_id' => $this->mId ),
			__METHOD__
		);
		if ( $this->mOldStatus === $status ) {
			return false; // nothing to do here
		}
		// Update status
		$this->mStatus = $status;
		$dbw->update( 'code_rev',
			array( 'cr_status' => $status ),
			array(
				'cr_repo_id' => $this->mRepoId,
				'cr_id' => $this->mId ),
			__METHOD__
		);
		// Log this change
		if ( $user && $user->getId() ) {
			$dbw->insert( 'code_prop_changes',
				array(
					'cpc_repo_id'   => $this->getRepoId(),
					'cpc_rev_id'    => $this->getId(),
					'cpc_attrib'    => 'status',
					'cpc_removed'   => $this->mOldStatus,
					'cpc_added'     => $status,
					'cpc_timestamp' => $dbw->timestamp(),
					'cpc_user'      => $user->getId(),
					'cpc_user_text' => $user->getName()
				),
				__METHOD__
			);
		}

		$this->sendStatusToUDP( $status, $this->mOldStatus );

		return true;
	}

	/**
	 * Quickie protection against huuuuuuuuge batch inserts
	 *
	 * @param DatabaseBase $db
	 * @param String $table
	 * @param Array $data
	 * @param string $method
	 * @param array $options
	 * @return void
	 */
	protected static function insertChunks( $db, $table, $data, $method = __METHOD__, $options = array() ) {
		$chunkSize = 100;
		for ( $i = 0; $i < count( $data ); $i += $chunkSize ) {
			$db->insert( $table,
				array_slice( $data, $i, $chunkSize ),
				$method,
				$options
			);
		}
	}

	/**
	 * @return void
	 */
	public function save() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->begin();

		$dbw->insert( 'code_rev',
			array(
				'cr_repo_id' => $this->mRepoId,
				'cr_id' => $this->mId,
				'cr_author' => $this->mAuthor,
				'cr_timestamp' => $dbw->timestamp( $this->mTimestamp ),
				'cr_message' => $this->mMessage,
				'cr_status' => $this->mStatus,
				'cr_path' => $this->mCommonPath,
				'cr_flags' => '' ),
			__METHOD__,
			array( 'IGNORE' )
		);

		// Already exists? Update the row!
		$newRevision = $dbw->affectedRows() > 0;
		if ( !$newRevision ) {
			$dbw->update( 'code_rev',
				array(
					'cr_author' => $this->mAuthor,
					'cr_timestamp' => $dbw->timestamp( $this->mTimestamp ),
					'cr_message' => $this->mMessage,
					'cr_path' => $this->mCommonPath ),
				array(
					'cr_repo_id' => $this->mRepoId,
					'cr_id' => $this->mId ),
				__METHOD__
			);
		}

		// Update path tracking used for output and searching
		if ( $this->mPaths ) {
			CodeRevision::insertPaths( $dbw, $this->mPaths, $this->mRepoId, $this->mId );
		}

		$affectedRevs = $this->getUniqueAffectedRevs();

		if ( count( $affectedRevs ) ) {
			$this->addReferencesTo( $affectedRevs );
		}

		global $wgEnableEmail;
		// Email the authors of revisions that this follows up on
		if ( $wgEnableEmail && $newRevision && count( $affectedRevs ) > 0 ) {
			// Get committer wiki user name, or repo name at least
			$commitAuthor = $this->getWikiUser();
			# Author might not have a username in the wiki:
			$committer = $commitAuthor ? $commitAuthor->getName() : htmlspecialchars( $this->mAuthor );
			// Get the authors of these revisions
			$res = $dbw->select( 'code_rev',
				array(
					'cr_repo_id',
					'cr_id',
					'cr_author',
					'cr_timestamp',
					'cr_message',
					'cr_status',
					'cr_path',
				),
				array(
					'cr_repo_id' => $this->mRepoId,
					'cr_id'      => $affectedRevs,
					'cr_id < ' . intval( $this->mId ), # just in case
					// No sense in notifying if it's the same person
					'cr_author != ' . $dbw->addQuotes( $this->mAuthor )
				),
				__METHOD__,
				array( 'USE INDEX' => 'PRIMARY' )
			);

			// Get repo and build comment title (for url)
			$url = $this->getFullUrl();

			foreach ( $res as $row ) {
				$revision = CodeRevision::newFromRow( $this->mRepo, $row );
				$users = $revision->getCommentingUsers();

				$rowUrl = $revision->getFullUrl();

				$revisionAuthor = $revision->getWikiUser();

				//Add the followup revision author if they have not already been added as a commentor (they won't want dupe emails!)
				if ( $revisionAuthor && !array_key_exists( $revisionAuthor->getId(), $users ) ) {
					$users[$revisionAuthor->getId()] = $revisionAuthor;
				}

				//Notify commenters and revision author of followup revision
				foreach ( $users as $user ) {
					if ( $user->canReceiveEmail() ) {
						// Send message in receiver's language
						$lang = array( 'language' => $user->getOption( 'language' ) );
						$user->sendMail(
							wfMsgExt( 'codereview-email-subj2', $lang, $this->mRepo->getName(),
								$this->getIdString( $row->cr_id ) ),
							wfMsgExt( 'codereview-email-body2', $lang, $committer,
								$this->getIdStringUnique( $row->cr_id ), $url, $this->mMessage, $rowUrl )
						);
					}
				}
			}
		}
		$dbw->commit();
	}

	public static function insertPaths( $dbw, $paths, $repoId, $revId ) {
		$data = array();
		foreach ( $paths as $path ) {
			$data[] = array(
				'cp_repo_id' => $repoId,
				'cp_rev_id'  => $revId,
				'cp_path'    => $path['path'],
				'cp_action'  => $path['action'] );
		}
		self::insertChunks( $dbw, 'code_paths', $data, __METHOD__, array( 'IGNORE' ) );
	}

	/**
	 * Returns a unique value array from that of getAffectedRevs() and getAffectedBugRevs()
	 *
	 * @return array
	 */
	public function getUniqueAffectedRevs() {
		return array_unique( array_merge( $this->getAffectedRevs(), $this->getAffectedBugRevs() ) );
	}

	/**
	 * Get the revisions this commit references
	 *
	 * @return array
	 */
	public function getAffectedRevs() {
		$affectedRevs = array();
		if ( preg_match_all( '/\br(\d{2,})\b/', $this->mMessage, $m ) ) {
			foreach ( $m[1] as $rev ) {
				$affectedRev = intval( $rev );
				if ( $affectedRev != $this->mId ) {
					$affectedRevs[] = $affectedRev;
				}
			}
		}
	    return $affectedRevs;
	}

	/**
	 * Parses references bugs in the comment, inserts them to code bugs, and returns an array of previous revs linking to the same bug
	 *
	 * @return array
	 */
	public function getAffectedBugRevs() {
		// Update bug references table...
		$affectedBugs = array();
		if ( preg_match_all( '/\bbug (\d+)\b/', $this->mMessage, $m ) ) {
			$data = array();
			foreach ( $m[1] as $bug ) {
				$data[] = array(
					'cb_repo_id' => $this->mRepoId,
					'cb_from'    => $this->mId,
					'cb_bug'     => $bug
				);
				$affectedBugs[] = intval( $bug );
			}
			$dbw = wfGetDB( DB_MASTER );
		    $dbw->insert( 'code_bugs', $data, __METHOD__, array( 'IGNORE' ) );
		}

		// Also, get previous revisions that have bugs in common...
		$affectedRevs = array();
		if ( count( $affectedBugs ) ) {
			$res = $dbw->select( 'code_bugs',
				array( 'cb_from' ),
				array(
					'cb_repo_id' => $this->mRepoId,
					'cb_bug'     => $affectedBugs,
					'cb_from < ' . intval( $this->mId ), # just in case
				),
				__METHOD__,
				array( 'USE INDEX' => 'cb_repo_id' )
			);
			foreach ( $res as $row ) {
				$affectedRevs[] = intval( $row->cb_from );
			}
		}

	    return $affectedRevs;
	}

	/**
	 * @return
	 */
	public function getModifiedPaths() {
		$dbr = wfGetDB( DB_SLAVE );
		return $dbr->select(
			'code_paths',
			array( 'cp_path', 'cp_action' ),
			array( 'cp_repo_id' => $this->mRepoId, 'cp_rev_id' => $this->mId ),
			__METHOD__
		);
	}

	/**
	 * @return bool
	 */
	public function isDiffable() {
		global $wgCodeReviewMaxDiffPaths;
		$paths = $this->getModifiedPaths();
		if ( !$paths->numRows() || ( $wgCodeReviewMaxDiffPaths > 0 && $paths->numRows() > $wgCodeReviewMaxDiffPaths ) ) {
			return false; // things need to get done this year
		}
		return true;
	}

	/**
	 * @param  $text
	 * @param  $review
	 * @param null $parent
	 * @return CodeComment
	 */
	public function previewComment( $text, $review, $parent = null ) {
		$data = $this->commentData( rtrim( $text ), $review, $parent );
		$data['cc_id'] = null;
		return CodeComment::newFromData( $this, $data );
	}

	/**
	 * @param  $text
	 * @param  $review
	 * @param null $parent
	 * @return int
	 */
	public function saveComment( $text, $review, $parent = null ) {
		$text = rtrim( $text );
		if ( !strlen( $text ) ) {
			return 0;
		}
		$dbw = wfGetDB( DB_MASTER );
		$data = $this->commentData( $text, $review, $parent );

		$dbw->begin();
		$data['cc_id'] = $dbw->nextSequenceValue( 'code_comment_cc_id' );
		$dbw->insert( 'code_comment', $data, __METHOD__ );
		$commentId = $dbw->insertId();
		$dbw->commit();

		$url = $this->getFullUrl( $commentId );

		$this->sendCommentToUDP( $commentId, $text, $url );

		return $commentId;
	}

	/**
	 * @param  $subject
	 * @param  $body
	 * @return void
	 */
	public function emailNotifyUsersOfChanges( $subject, $body ) {
		// Give email notices to committer and commenters
		global $wgCodeReviewENotif, $wgEnableEmail, $wgCodeReviewCommentWatcherEmail,
			$wgCodeReviewCommentWatcherName, $wgUser;
		if ( !$wgCodeReviewENotif && !$wgEnableEmail ) {
			return;
		}

		$args = func_get_args();
		array_shift( $args ); //Drop $subject
		array_shift( $args ); //Drop $body

		// Make list of users to send emails to
		$users = $this->getCommentingUsers();
		$user = $this->getWikiUser();
		if ( $user ) {
			$users[$user->getId()] = $user;
		}
		// If we've got a spam list, send e-mails to it too
		if ( $wgCodeReviewCommentWatcherEmail ) {
			$watcher = new User();
			$watcher->setEmail( $wgCodeReviewCommentWatcherEmail );
			$watcher->setName( $wgCodeReviewCommentWatcherName );
			$users[0] = $watcher; // We don't have any anons, so using 0 is safe
		}

		foreach ( $users as $id => $user ) {
			// canReceiveEmail() returns false for the fake watcher user, so exempt it
			// This is ugly
			if ( $id == 0 || $user->canReceiveEmail() ) {
				// Send message in receiver's language
				$lang = array( 'language' => $user->getOption( 'language' ) );

				$localSubject = wfMsgExt( $subject, $lang, $this->mRepo->getName(), $this->getIdString() );
				$localBody = call_user_func_array( 'wfMsgExt', array_merge( array( $body, $lang, $wgUser->getName() ), $args ) );

				$user->sendMail( $localSubject, $localBody );
			}
		}
	}

	/**
	 * @param  $text
	 * @param  $review
	 * @param null $parent
	 * @return array
	 */
	protected function commentData( $text, $review, $parent = null ) {
		global $wgUser;
		$dbw = wfGetDB( DB_MASTER );
		$ts = wfTimestamp( TS_MW );
		$sortkey = $this->threadedSortkey( $parent, $ts );
		return array(
			'cc_repo_id' => $this->mRepoId,
			'cc_rev_id' => $this->mId,
			'cc_text' => $text,
			'cc_parent' => $parent,
			'cc_user' => $wgUser->getId(),
			'cc_user_text' => $wgUser->getName(),
			'cc_timestamp' => $dbw->timestamp( $ts ),
			'cc_review' => $review,
			'cc_sortkey' => $sortkey );
	}

	/**
	 * @throws MWException
	 * @param  $parent
	 * @param  $ts
	 * @return string
	 */
	protected function threadedSortKey( $parent, $ts ) {
		if ( $parent ) {
			// We construct a threaded sort key by concatenating the timestamps
			// of all our parent comments
			$dbw = wfGetDB( DB_MASTER );
			$parentKey = $dbw->selectField( 'code_comment',
				'cc_sortkey',
				array( 'cc_id' => $parent ),
				__METHOD__ );
			if ( $parentKey ) {
				return $parentKey . ',' . $ts;
			} else {
				// hmmmm
				throw new MWException( 'Invalid parent submission' );
			}
		} else {
			return $ts;
		}
	}

	/**
	 * @return array
	 */
	public function getComments() {
		$dbr = wfGetDB( DB_SLAVE );
		$result = $dbr->select( 'code_comment',
			array(
				'cc_id',
				'cc_text',
				'cc_user',
				'cc_user_text',
				'cc_timestamp',
				'cc_review',
				'cc_sortkey' ),
			array(
				'cc_repo_id' => $this->mRepoId,
				'cc_rev_id' => $this->mId ),
			__METHOD__,
			array(
				'ORDER BY' => 'cc_sortkey' )
		);
		$comments = array();
		foreach ( $result as $row ) {
			$comments[] = CodeComment::newFromRow( $this, $row );
		}
		return $comments;
	}

	/*
	 * @return int
	 */
	public function getCommentCount() {
		$dbr = wfGetDB( DB_SLAVE );
		$result = $dbr->select( 'code_comment',
			array( 'cc_id' ),
			array(
				'cc_repo_id' => $this->mRepoId,
				'cc_rev_id' => $this->mId ),
			__METHOD__
		);

		if ( $result ) {
			return intval( $result->comments );
		} else {
			return 0;
		}
	}

	/**
	 * @return array
	 */
	public function getPropChanges() {
		$dbr = wfGetDB( DB_SLAVE );
		$result = $dbr->select( array( 'code_prop_changes', 'user' ),
			array(
				'cpc_attrib',
				'cpc_removed',
				'cpc_added',
				'cpc_timestamp',
				'cpc_user',
				'cpc_user_text',
				'user_name'
			), array(
				'cpc_repo_id' => $this->mRepoId,
				'cpc_rev_id' => $this->mId,
			),
			__METHOD__,
			array( 'ORDER BY' => 'cpc_timestamp DESC' ),
			array( 'user' => array( 'LEFT JOIN', 'cpc_user = user_id' ) )
		);
		$changes = array();
		foreach ( $result as $row ) {
			$changes[] = CodePropChange::newFromRow( $this, $row );
		}
		return $changes;
	}

	/**
	 * @return array
	 */
	public function getPropChangeUsers() {
		$dbr = wfGetDB( DB_SLAVE );
		$result = $dbr->select( 'code_prop_changes',
			'DISTINCT(cpc_user)',
			array(
				'cpc_repo_id' => $this->mRepoId,
				'cpc_rev_id' => $this->mId,
			),
			__METHOD__
		);
		$users = array();
		foreach ( $result as $row ) {
			$users[$row->cpc_user] = User::newFromId( $row->cpc_user );
		}
		return $users;
	}

	/**
	* "Review" being revision commenters, and people who set/removed tags and changed the status
	*/
	public function getReviewContributingUsers() {
		return array_merge( $this->getCommentingUsers(), $this->getPropChangeUsers() );
	}

	/**
	 * @return array
	 */
	protected function getCommentingUsers() {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'code_comment',
			'DISTINCT(cc_user)',
			array(
				'cc_repo_id' => $this->mRepoId,
				'cc_rev_id' => $this->mId,
				'cc_user != 0' // users only
			),
			__METHOD__
		);
		$users = array();
		foreach( $res as $row ) {
			$users[$row->cc_user] = User::newFromId( $row->cc_user );
		}
		return $users;
	}

	/**
	 * Get all revisions referring to this revision (called followups of this revision in the UI).
	 *
	 * Any references from a revision to itself or from a revision to a revision in its past
	 * (i.e. with a lower revision ID) are silently dropped.
	 * @return array of code_rev database row objects
	 */
	public function getReferences() {
		$refs = array();
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			array( 'code_relations', 'code_rev' ),
			array( 'cr_id', 'cr_status', 'cr_timestamp', 'cr_author', 'cr_message' ),
			array(
				'cf_repo_id' => $this->mRepoId,
				'cf_to' => $this->mId,
				'cr_repo_id = cf_repo_id',
				'cr_id = cf_from'
			),
			__METHOD__
		);
		foreach( $res as $row ) {
			if ( $this->mId < intval( $row->cr_id ) ) {
				$refs[] = $row;
			}
		}
		return $refs;
	}

	/**
	 * Add references from the specified revisions to this revision. In the UI, this will
	 * show the specified revisions as follow-ups to this one.
	 *
	 * This function will silently refuse to add a reference from a revision to itself or from
	 * revisions in its past (i.e. with lower revision IDs)
	 * @param $revs array of revision IDs
	 */
	public function addReferencesFrom( $revs ) {
		$data = array();
		foreach ( array_unique( (array)$revs ) as $rev ) {
			if ( $rev > $this->getId() ) {
				$data[] = array(
					'cf_repo_id' => $this->getRepoId(),
					'cf_from' => $rev,
					'cf_to' => $this->getId()
				);
			}
		}
		$this->addReferences( $data );
	}

	/**
	 * @param  $data
	 * @return void
	 */
	private function addReferences( $data ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert( 'code_relations', $data, __METHOD__, array( 'IGNORE' ) );
	}

	/**
	 * Same as addReferencesFrom(), but adds references from this revision to
	 * the specified revisions.
	 * @param $revs array of revision IDs
	 */
	public function addReferencesTo( $revs ) {
		$data = array();
		foreach ( array_unique( (array)$revs ) as $rev ) {
			if ( $rev < $this->getId() ) {
				$data[] = array(
					'cf_repo_id' => $this->getRepoId(),
					'cf_from' => $this->getId(),
					'cf_to' => $rev,
				);
			}
		}
		$this->addReferences( $data );
	}

	/**
	 * Remove references from the specified revisions to this revision. In the UI, this will
	 * no longer show the specified revisions as follow-ups to this one.
	 * @param $revs array of revision IDs
	 */
	public function removeReferencesFrom( $revs ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'code_relations', array(
				'cf_repo_id' => $this->getRepoId(),
				'cf_from' => $revs,
				'cf_to' => $this->getId()
			), __METHOD__
		);
	}

	/**
	 * Get all sign-offs for this revision
	 * @param $from DB_SLAVE or DB_MASTER
	 * @return array of CodeSignoff objects
	 */
	public function getSignoffs( $from = DB_SLAVE ) {
		$db = wfGetDB( $from );
		$result = $db->select( 'code_signoffs',
			array( 'cs_user', 'cs_user_text', 'cs_flag', 'cs_timestamp', 'cs_timestamp_struck' ),
			array(
				'cs_repo_id' => $this->mRepoId,
				'cs_rev_id' => $this->mId,
			),
			__METHOD__,
			array( 'ORDER BY' => 'cs_timestamp' )
		);

		$signoffs = array();
		foreach ( $result as $row ) {
			$signoffs[] = CodeSignoff::newFromRow( $this, $row );
		}
		return $signoffs;
	}

	/**
	 * Add signoffs for this revision
	 * @param $user User object for the user who did the sign-off
	 * @param $flags array of flags (strings, see getPossibleFlags()). Each flag is added as a separate sign-off
	 */
	public function addSignoff( $user, $flags ) {
		$dbw = wfGetDB( DB_MASTER );
		$rows = array();
		foreach ( (array)$flags as $flag ) {
			$rows[] = array(
				'cs_repo_id' => $this->mRepoId,
				'cs_rev_id' => $this->mId,
				'cs_user' => $user->getID(),
				'cs_user_text' => $user->getName(),
				'cs_flag' => $flag,
				'cs_timestamp' => $dbw->timestamp(),
				'cs_timestamp_struck' => Block::infinity()
			);
		}
		$dbw->insert( 'code_signoffs', $rows, __METHOD__, array( 'IGNORE' ) );
	}

	/**
	 * Strike a set of sign-offs by a given user. Any sign-offs in $ids not
	 * by $user are silently ignored, as well as nonexistent IDs and
	 * already-struck sign-offs.
	 * @param $user User object
	 * @param $ids array of sign-off IDs to strike
	 */
	public function strikeSignoffs( $user, $ids ) {
		foreach ( $ids as $id ) {
			$signoff = CodeSignoff::newFromId( $this, $id );
			// Only allow striking own signoffs
			if ( $signoff && $signoff->userText === $user->getName() ) {
				$signoff->strike();
			}
		}
	}

	/**
	 * @param int $from
	 * @return array
	 */
	public function getTags( $from = DB_SLAVE ) {
		$db = wfGetDB( $from );
		$result = $db->select( 'code_tags',
			array( 'ct_tag' ),
			array(
				'ct_repo_id' => $this->mRepoId,
				'ct_rev_id' => $this->mId ),
			__METHOD__ );

		$tags = array();
		foreach ( $result as $row ) {
			$tags[] = $row->ct_tag;
		}
		return $tags;
	}

	/**
	 * @param  $addTags
	 * @param  $removeTags
	 * @param null $user
	 * @return void
	 */
	public function changeTags( $addTags, $removeTags, $user = null ) {
		// Get the current tags and see what changes
		$tagsNow = $this->getTags( DB_MASTER );
		// Normalize our input tags
		$addTags = $this->normalizeTags( $addTags );
		$removeTags = $this->normalizeTags( $removeTags );
		$addTags = array_diff( $addTags, $tagsNow );
		$removeTags = array_intersect( $removeTags, $tagsNow );
		// Do the queries
		$dbw = wfGetDB( DB_MASTER );
		if ( $addTags ) {
			$dbw->insert( 'code_tags',
				$this->tagData( $addTags ),
				__METHOD__,
				array( 'IGNORE' )
			);
		}
		if ( $removeTags ) {
			$dbw->delete( 'code_tags',
				array(
					'ct_repo_id' => $this->mRepoId,
					'ct_rev_id'  => $this->mId,
					'ct_tag'     => $removeTags ),
				__METHOD__
			);
		}
		// Log this change
		if ( ( $removeTags || $addTags ) && $user && $user->getId() ) {
			$dbw->insert( 'code_prop_changes',
				array(
					'cpc_repo_id'   => $this->getRepoId(),
					'cpc_rev_id'    => $this->getId(),
					'cpc_attrib'    => 'tags',
					'cpc_removed'   => implode( ',', $removeTags ),
					'cpc_added'     => implode( ',', $addTags ),
					'cpc_timestamp' => $dbw->timestamp(),
					'cpc_user'      => $user->getId(),
					'cpc_user_text' => $user->getName()
				),
				__METHOD__
			);
		}
	}

	/**
	 * @param  $tags
	 * @return array
	 */
	protected function normalizeTags( $tags ) {
		$out = array();
		foreach ( $tags as $tag ) {
			$out[] = $this->normalizeTag( $tag );
		}
		return $out;
	}

	/**
	 * @param  $tags
	 * @return array
	 */
	protected function tagData( $tags ) {
		$data = array();
		foreach ( $tags as $tag ) {
			if ( $tag == '' ) continue;
			$data[] = array(
				'ct_repo_id' => $this->mRepoId,
				'ct_rev_id'  => $this->mId,
				'ct_tag'     => $this->normalizeTag( $tag ) );
		}
		return $data;
	}

	/**
	 * @param  $tag
	 * @return bool
	 */
	public function normalizeTag( $tag ) {
		global $wgContLang;
		$lower = $wgContLang->lc( $tag );

		$title = Title::newFromText( $tag );
		if ( $title && $lower === $wgContLang->lc( $title->getPrefixedText() ) ) {
			return $lower;
		} else {
			return false;
		}
	}

	/**
	 * @param  $tag
	 * @return  bool
	 */
	public function isValidTag( $tag ) {
		return ( $this->normalizeTag( $tag ) !== false );
	}

	/**
	 * @param string $path
	 * @return bool|int
	 */
	public function getPrevious( $path = '' ) {
		$dbr = wfGetDB( DB_SLAVE );
		$encId = $dbr->addQuotes( $this->mId );
		$tables = array( 'code_rev' );
		if ( $path != '' ) {
			$conds = $this->getPathConds( $path );
			$order = 'cp_rev_id DESC';
			$tables[] = 'code_paths';
		} else {
			$conds = array( 'cr_repo_id' => $this->mRepoId );
			$order = 'cr_id DESC';
		}
		$conds[] = "cr_id < $encId";
		$row = $dbr->selectRow( $tables, 'cr_id',
			$conds,
			__METHOD__,
			array( 'ORDER BY' => $order )
		);
		if ( $row ) {
			return intval( $row->cr_id );
		} else {
			return false;
		}
	}

	/**
	 * @param string $path
	 * @return bool|int
	 */
	public function getNext( $path = '' ) {
		$dbr = wfGetDB( DB_SLAVE );
		$encId = $dbr->addQuotes( $this->mId );
		$tables = array( 'code_rev' );
		if ( $path != '' ) {
			$conds = $this->getPathConds( $path );
			$order = 'cp_rev_id ASC';
			$tables[] = 'code_paths';
		} else {
			$conds = array( 'cr_repo_id' => $this->mRepoId );
			$order = 'cr_id ASC';
		}
		$conds[] = "cr_id > $encId";
		$row = $dbr->selectRow( $tables, 'cr_id',
			$conds,
			__METHOD__,
			array( 'ORDER BY' => $order )
		);
		if ( $row ) {
			return intval( $row->cr_id );
		} else {
			return false;
		}
	}

	/**
	 * @param  $path
	 * @return array
	 */
	protected function getPathConds( $path ) {
		$dbr = wfGetDB( DB_SLAVE );
		return array(
			'cp_repo_id' => $this->mRepoId,
			'cp_path ' . $dbr->buildLike( $path, $dbr->anyString() ),
			// join conds
			'cr_repo_id = cp_repo_id',
			'cr_id = cp_rev_id'
		);
	}

	/**
	 * @param string $path
	 * @return bool|int
	 */
	public function getNextUnresolved( $path = '' ) {
		$dbr = wfGetDB( DB_SLAVE );
		$encId = $dbr->addQuotes( $this->mId );
		$tables = array( 'code_rev' );
		if ( $path != '' ) {
			$conds = $this->getPathConds( $path );
			$order = 'cp_rev_id ASC';
			$tables[] = 'code_paths';
		} else {
			$conds = array( 'cr_repo_id' => $this->mRepoId );
			$order = 'cr_id ASC';
		}
		$conds[] = "cr_id > $encId";
		$conds['cr_status'] = array( 'new', 'fixme' );
		$row = $dbr->selectRow( $tables, 'cr_id',
			$conds,
			__METHOD__,
			array( 'ORDER BY' => $order )
		);
		if ( $row ) {
			return intval( $row->cr_id );
		} else {
			return false;
		}
	}

	/**
	 * @param string $commentId
	 * @return \type
	 */
	public function getFullUrl( $commentId = '' ) {
		$title = SpecialPage::getTitleFor( 'Code', $this->mRepo->getName() . '/' . $this->mId );

		if ( $commentId !== '' ) {
			$title->setFragment( "#c{$commentId}" );
		}

		return $title->getFullUrl();
	}

	/**
	 * @param  $commentId
	 * @param  $text
	 * @param null $url
	 * @return void
	 */
	protected function sendCommentToUDP( $commentId, $text, $url = null ) {
		global $wgCodeReviewUDPAddress, $wgCodeReviewUDPPort, $wgCodeReviewUDPPrefix, $wgLang, $wgUser;

		if( $wgCodeReviewUDPAddress ) {
			if( is_null( $url ) ) {
				$url = $this->getFullUrl( $commentId );
			}

			$line = wfMsg( 'code-rev-message' ) . " \00314(" . $this->mRepo->getName() .
					")\003 \0037" . $this->getIdString() . "\003 \00303" . RecentChange::cleanupForIRC( $wgUser->getName() ) .
					"\003: \00310" . RecentChange::cleanupForIRC( $wgLang->truncate( $text, 100 ) ) . "\003 " . $url;

			RecentChange::sendToUDP( $line, $wgCodeReviewUDPAddress, $wgCodeReviewUDPPrefix, $wgCodeReviewUDPPort );
		}
	}

	/**
	 * @param  $status
	 * @param  $oldStatus
	 * @return void
	 */
	protected function sendStatusToUDP( $status, $oldStatus ) {
		global $wgCodeReviewUDPAddress, $wgCodeReviewUDPPort, $wgCodeReviewUDPPrefix, $wgUser;

		if( $wgCodeReviewUDPAddress ) {
			$url = $this->getFullUrl();

			$line = wfMsg( 'code-rev-status' ) . " \00314(" . $this->mRepo->getName() .
					")\00303 " . RecentChange::cleanupForIRC( $wgUser->getName() ) . "\003 " .
					/* Remove three apostrophes as they are intended for the parser  */
					str_replace( "'''", '', wfMsg( 'code-change-status', "\0037" . $this->getIdString() . "\003" ) ) .
					": \00315" . wfMsg( 'code-status-' . $oldStatus ) . "\003 -> \00310" .
					wfMsg( 'code-status-' . $status ) . "\003 " . $url;

			RecentChange::sendToUDP( $line, $wgCodeReviewUDPAddress, $wgCodeReviewUDPPrefix, $wgCodeReviewUDPPort );
		}
	}
}
