<?php
/**
 * File for articles
 * @file
 */

/**
 * Class for viewing MediaWiki article and history.
 *
 * This maintains WikiPage functions for backwards compatibility.
 *
 * @TODO: move and rewrite code to an Action class
 *
 * See design.txt for an overview.
 * Note: edit user interface and cache support functions have been
 * moved to separate EditPage and HTMLFileCache classes.
 *
 * @internal documentation reviewed 15 Mar 2010
 * @deprecated
 */
class Article extends Page {
	/**@{{
	 * @private
	 */

	/**
	 * @var RequestContext
	 */
	protected $mContext;

	/**
	 * @var WikiPage
	 */
	protected $mPage;

	var $mContent;                    // !<
	var $mContentLoaded = false;      // !<
	var $mOldId;                      // !<

	/**
	 * @var Title
	 */
	var $mRedirectedFrom = null;

	/**
	 * @var mixed: boolean false or URL string
	 */
	var $mRedirectUrl = false;        // !<
	var $mRevIdFetched = 0;           // !<

	/**
	 * @var Revision
	 */
	var $mRevision = null;

	/**
	 * @var ParserOutput
	 */
	var $mParserOutput;

	/**@}}*/

	/**
	 * Constructor and clear the article
	 * @param $title Title Reference to a Title object.
	 * @param $oldId Integer revision ID, null to fetch from request, zero for current
	 */
	public function __construct( Title $title, $oldId = null ) {
		$this->mOldId = $oldId;
		$this->mPage = $this->newPage( $title );
	}

	protected function newPage( Title $title ) {
		return new WikiPage( $title );
	}

	/**
	 * Constructor from a page id
	 * @param $id Int article ID to load
	 */
	public static function newFromID( $id ) {
		$t = Title::newFromID( $id );
		# @todo FIXME: Doesn't inherit right
		return $t == null ? null : new self( $t );
		# return $t == null ? null : new static( $t ); // PHP 5.3
	}

	/**
	 * Create an Article object of the appropriate class for the given page.
	 *
	 * @param $title Title
	 * @param $context RequestContext
	 * @return Article object
	 */
	public static function newFromTitle( $title, RequestContext $context ) {
		if ( NS_MEDIA == $title->getNamespace() ) {
			// FIXME: where should this go?
			$title = Title::makeTitle( NS_FILE, $title->getDBkey() );
		}

		$page = null;
		wfRunHooks( 'ArticleFromTitle', array( &$title, &$page ) );
		if ( !$page ) {
			switch( $title->getNamespace() ) {
				case NS_FILE:
					$page = new ImagePage( $title );
					break;
				case NS_CATEGORY:
					$page = new CategoryPage( $title );
					break;
				default:
					$page = new Article( $title );
			}
		}
		$page->setContext( $context );

		return $page;
	}

	/**
	 * Tell the page view functions that this view was redirected
	 * from another page on the wiki.
	 * @param $from Title object.
	 */
	public function setRedirectedFrom( Title $from ) {
		$this->mRedirectedFrom = $from;
	}

	/**
	 * Get the WikiPage used here
	 * @return WikiPage
	 */
	public function getPage() {
		return $this->mPage;
	}

	/**
	 * Get the title object of the article
	 * @return Title object of this page
	 */
	public function getTitle() {
		return $this->mPage->getTitle();
	}

	/**
	 * Clear the object
	 * @todo FIXME: Shouldn't this be public?
	 * @private
	 */
	public function clear() {
		$this->mContentLoaded = false;

		$this->mRedirectedFrom = null; # Title object if set
		$this->mRevIdFetched = 0;
		$this->mRedirectUrl = false;

		$this->mPage->clear();
	}

	/**
	 * Note that getContent/loadContent do not follow redirects anymore.
	 * If you need to fetch redirectable content easily, try
	 * the shortcut in Article::followRedirect()
	 *
	 * This function has side effects! Do not use this function if you
	 * only want the real revision text if any.
	 *
	 * @return Return the text of this revision
	 */
	public function getContent() {
		global $wgUser;

		wfProfileIn( __METHOD__ );

		if ( $this->mPage->getID() === 0 ) {
			# If this is a MediaWiki:x message, then load the messages
			# and return the message value for x.
			if ( $this->getTitle()->getNamespace() == NS_MEDIAWIKI ) {
				$text = $this->getTitle()->getDefaultMessageText();
				if ( $text === false ) {
					$text = '';
				}
			} else {
				$text = wfMsgExt( $wgUser->isLoggedIn() ? 'noarticletext' : 'noarticletextanon', 'parsemag' );
			}
			wfProfileOut( __METHOD__ );

			return $text;
		} else {
			$this->loadContent();
			wfProfileOut( __METHOD__ );

			return $this->mContent;
		}
	}

	public function getOldID() {
		wfDeprecated( __METHOD__, '1.19-pageoutput' );
		throw new MWException(__METHOD__ . " moved to PageView." );
	}

	public function getOldIDFromRequest() {
		wfDeprecated( __METHOD__, '1.19-pageoutput' );
		throw new MWException(__METHOD__ . " moved to PageView." );
	}

	/**
	 * Load the revision (including text) into this object
	 */
	function loadContent() {
		if ( $this->mContentLoaded ) {
			return;
		}

		wfProfileIn( __METHOD__ );

		$this->fetchContent( $this->getOldID() );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Get text of an article from database
	 * Does *NOT* follow redirects.
	 *
	 * @param $oldid Int: 0 for whatever the latest revision is
	 * @return mixed string containing article contents, or false if null
	 */
	function fetchContent( $oldid = 0 ) {
		if ( $this->mContentLoaded ) {
			return $this->mContent;
		}

		# Pre-fill content with error message so that if something
		# fails we'll have something telling us what we intended.
		$t = $this->getTitle()->getPrefixedText();
		$d = $oldid ? wfMsgExt( 'missingarticle-rev', array( 'escape' ), $oldid ) : '';
		$this->mContent = wfMsgNoTrans( 'missing-article', $t, $d ) ;

		if ( $oldid ) {
			$revision = Revision::newFromId( $oldid );
			if ( !$revision ) {
				wfDebug( __METHOD__ . " failed to retrieve specified revision, id $oldid\n" );
				return false;
			}
			// Revision title doesn't match the page title given?
			if ( $this->mPage->getID() != $revision->getPage() ) {
				$function = array( get_class( $this->mPage ), 'newFromID' );
				$this->mPage = call_user_func( $function, $revision->getPage() );
				if ( !$this->mPage->getId() ) {
					wfDebug( __METHOD__ . " failed to get page data linked to revision id $oldid\n" );
					return false;
				}
			}
		} else {
			if ( !$this->mPage->getLatest() ) {
				wfDebug( __METHOD__ . " failed to find page data for title " . $this->getTitle()->getPrefixedText() . "\n" );
				return false;
			}

			$revision = $this->mPage->getRevision();
			if ( !$revision ) {
				wfDebug( __METHOD__ . " failed to retrieve current page, rev_id " . $this->mPage->getLatest() . "\n" );
				return false;
			}
		}

		// @todo FIXME: Horrible, horrible! This content-loading interface just plain sucks.
		// We should instead work with the Revision object when we need it...
		$this->mContent   = $revision->getText( Revision::FOR_THIS_USER ); // Loads if user is allowed

		$this->mRevIdFetched = $revision->getId();
		$this->mContentLoaded = true;
		$this->mRevision =& $revision;

		wfRunHooks( 'ArticleAfterFetchContent', array( &$this, &$this->mContent ) );

		return $this->mContent;
	}

	/**
	 * No-op
	 * @deprecated since 1.18
	 */
	public function forUpdate() {
		wfDeprecated( __METHOD__ );
	}

	/**
	 * Returns true if the currently-referenced revision is the current edit
	 * to this page (and it exists).
	 * @return bool
	 */
	public function isCurrent() {
		# If no oldid, this is the current version.
		if ( $this->getOldID() == 0 ) {
			return true;
		}

		return $this->mPage->exists() && $this->mRevision && $this->mRevision->isCurrent();
	}

	/**
	 * Use this to fetch the rev ID used on page views
	 *
	 * @return int revision ID of last article revision
	 */
	public function getRevIdFetched() {
		if ( $this->mRevIdFetched ) {
			return $this->mRevIdFetched;
		} else {
			return $this->mPage->getLatest();
		}
	}

	/**
	 * This is the default action of the index.php entry point: just view the
	 * page of the given title.
	 */
	public function view() {
		wfDeprecated( __METHOD__, '1.19-pageoutput' );
		throw new MWException(__METHOD__ . " moved to PageView." );
	}

	/**
	 * Show a diff page according to current request variables. For use within
	 * Article::view() only, other callers should use the DifferenceEngine class.
	 */
	public function showDiffPage() {
		global $wgRequest, $wgUser;

		$diff = $wgRequest->getVal( 'diff' );
		$rcid = $wgRequest->getVal( 'rcid' );
		$diffOnly = $wgRequest->getBool( 'diffonly', $wgUser->getOption( 'diffonly' ) );
		$purge = $wgRequest->getVal( 'action' ) == 'purge';
		$unhide = $wgRequest->getInt( 'unhide' ) == 1;
		$oldid = $this->getOldID();

		$de = new DifferenceEngine( $this->getTitle(), $oldid, $diff, $rcid, $purge, $unhide );
		// DifferenceEngine directly fetched the revision:
		$this->mRevIdFetched = $de->mNewid;
		$de->showDiffPage( $diffOnly );

		if ( $diff == 0 || $diff == $this->mPage->getLatest() ) {
			# Run view updates for current revision only
			$this->mPage->viewUpdates();
		}
	}

	/**
	 * Show a page view for a page formatted as CSS or JavaScript. To be called by
	 * Article::view() only.
	 *
	 * This is hooked by SyntaxHighlight_GeSHi to do syntax highlighting of these
	 * page views.
	 */
	protected function showCssOrJsPage() {
		global $wgOut;

		$dir = $this->getContext()->getLang()->getDir();
		$lang = $this->getContext()->getLang()->getCode();

		$wgOut->wrapWikiMsg( "<div id='mw-clearyourcache' lang='$lang' dir='$dir' class='mw-content-$dir'>\n$1\n</div>",
			'clearyourcache' );

		// Give hooks a chance to customise the output
		if ( wfRunHooks( 'ShowRawCssJs', array( $this->mContent, $this->getTitle(), $wgOut ) ) ) {
			// Wrap the whole lot in a <pre> and don't parse
			$m = array();
			preg_match( '!\.(css|js)$!u', $this->getTitle()->getText(), $m );
			$wgOut->addHTML( "<pre class=\"mw-code mw-{$m[1]}\" dir=\"ltr\">\n" );
			$wgOut->addHTML( htmlspecialchars( $this->mContent ) );
			$wgOut->addHTML( "\n</pre>\n" );
		}
	}

	public function getRobotPolicy( $action ) {
		wfDeprecated( __METHOD__, '1.19-pageoutput' );
		throw new MWException(__METHOD__ . " moved to PageView." );
	}

	/**
	 * @see OutputPage::formatRobotPolicy
	 * @deprecated
	 */
	public static function formatRobotPolicy( $policy ) {
		wfDeprecated( __METHOD__, '1.19-pageoutput' );
		return OutputPage::formatRobotPolicy( $policy );
	}

	public function showRedirectedFromHeader() {
		wfDeprecated( __METHOD__, '1.19-pageoutput' );
		throw new MWException(__METHOD__ . " moved to PageView." );
	}

	public function showNamespaceHeader() {
		wfDeprecated( __METHOD__, '1.19-pageoutput' );
		throw new MWException(__METHOD__ . " moved to PageView." );
	}

	public function showViewFooter() {
		wfDeprecated( __METHOD__, '1.19-pageoutput' );
		throw new MWException(__METHOD__ . " moved to PageView." );
	}

	public function showPatrolFooter() {
		wfDeprecated( __METHOD__, '1.19-pageoutput' );
		throw new MWException(__METHOD__ . " moved to PageView." );
	}

	public function showMissingArticle() {
		wfDeprecated( __METHOD__, '1.19-pageoutput' );
		throw new MWException(__METHOD__ . " moved to PageView." );
	}

	public function showDeletedRevisionHeader() {
		wfDeprecated( __METHOD__, '1.19-pageoutput' );
		throw new MWException(__METHOD__ . " moved to PageView." );
	}

	public function doViewParse() {
		wfDeprecated( __METHOD__, '1.19-pageoutput' );
		return OutputPage::formatRobotPolicy( $policy );
	}

	public function tryDirtyCache() {
		wfDeprecated( __METHOD__, '1.19-pageoutput' );
		return OutputPage::formatRobotPolicy( $policy );
	}

	/**
	 * View redirect
	 *
	 * @param $target Title|Array of destination(s) to redirect
	 * @param $appendSubtitle Boolean [optional]
	 * @param $forceKnown Boolean: should the image be shown as a bluelink regardless of existence?
	 * @return string containing HMTL with redirect link
	 */
	public function viewRedirect( $target, $appendSubtitle = true, $forceKnown = false ) {
		global $wgOut, $wgLang, $wgStylePath;

		if ( !is_array( $target ) ) {
			$target = array( $target );
		}

		$imageDir = $wgLang->getDir();

		if ( $appendSubtitle ) {
			$wgOut->appendSubtitle( wfMsgHtml( 'redirectpagesub' ) );
		}

		// the loop prepends the arrow image before the link, so the first case needs to be outside
		$title = array_shift( $target );

		if ( $forceKnown ) {
			$link = Linker::linkKnown( $title, htmlspecialchars( $title->getFullText() ) );
		} else {
			$link = Linker::link( $title, htmlspecialchars( $title->getFullText() ) );
		}

		$nextRedirect = $wgStylePath . '/common/images/nextredirect' . $imageDir . '.png';
		$alt = $wgLang->isRTL() ? '←' : '→';
		// Automatically append redirect=no to each link, since most of them are redirect pages themselves.
		foreach ( $target as $rt ) {
			$link .= Html::element( 'img', array( 'src' => $nextRedirect, 'alt' => $alt ) );
			if ( $forceKnown ) {
				$link .= Linker::linkKnown( $rt, htmlspecialchars( $rt->getFullText(), array(), array( 'redirect' => 'no' ) ) );
			} else {
				$link .= Linker::link( $rt, htmlspecialchars( $rt->getFullText() ), array(), array( 'redirect' => 'no' ) );
			}
		}

		$imageUrl = $wgStylePath . '/common/images/redirect' . $imageDir . '.png';
		return '<div class="redirectMsg">' .
			Html::element( 'img', array( 'src' => $imageUrl, 'alt' => '#REDIRECT' ) ) .
			'<span class="redirectText">' . $link . '</span></div>';
	}

	/**
	 * Builds trackback links for article display if $wgUseTrackbacks is set to true
	 */
	public function addTrackbacks() {
		global $wgOut;

		$dbr = wfGetDB( DB_SLAVE );
		$tbs = $dbr->select( 'trackbacks',
			array( 'tb_id', 'tb_title', 'tb_url', 'tb_ex', 'tb_name' ),
			array( 'tb_page' => $this->mPage->getID() )
		);

		if ( !$dbr->numRows( $tbs ) ) {
			return;
		}

		$wgOut->preventClickjacking();

		$tbtext = "";
		foreach ( $tbs as $o ) {
			$rmvtxt = "";

			if ( $this->getContext()->getUser()->isAllowed( 'trackback' ) ) {
				$delurl = $this->getTitle()->getFullURL( "action=deletetrackback&tbid=" .
					$o->tb_id . "&token=" . urlencode( $this->getContext()->getUser()->editToken() ) );
				$rmvtxt = wfMsg( 'trackbackremove', htmlspecialchars( $delurl ) );
			}

			$tbtext .= "\n";
			$tbtext .= wfMsgNoTrans( strlen( $o->tb_ex ) ? 'trackbackexcerpt' : 'trackback',
					$o->tb_title,
					$o->tb_url,
					$o->tb_ex,
					$o->tb_name,
					$rmvtxt );
		}

		$wgOut->wrapWikiMsg( "<div id='mw_trackbacks'>\n$1\n</div>\n", array( 'trackbackbox', $tbtext ) );
	}

	/**
	 * Removes trackback record for current article from trackbacks table
	 * @deprecated since 1.18
	 */
	public function deletetrackback() {
		return Action::factory( 'deletetrackback', $this )->show();
	}

	/**
	 * Handle action=render
	 */

	public function render() {
		global $wgOut;

		$wgOut->setArticleBodyOnly( true );
		$this->view();
	}

	/**
	 * Handle action=purge
	 */
	public function purge() {
		return Action::factory( 'purge', $this )->show();
	}

	/**
	 * Mark this particular edit/page as patrolled
	 * @deprecated since 1.18
	 */
	public function markpatrolled() {
		Action::factory( 'markpatrolled', $this )->show();
	}

	/**
	 * User-interface handler for the "watch" action.
	 * Requires Request to pass a token as of 1.18.
	 * @deprecated since 1.18
	 */
	public function watch() {
		Action::factory( 'watch', $this )->show();
	}

	/**
	 * Add this page to $wgUser's watchlist
	 *
	 * This is safe to be called multiple times
	 *
	 * @return bool true on successful watch operation
	 * @deprecated since 1.18
	 */
	public function doWatch() {
		global $wgUser;
		return WatchAction::doWatch( $this->getTitle(), $wgUser );
	}

	/**
	 * User interface handler for the "unwatch" action.
	 * Requires Request to pass a token as of 1.18.
	 * @deprecated since 1.18
	 */
	public function unwatch() {
		Action::factory( 'unwatch', $this )->show();
	}

	/**
	 * Stop watching a page
	 * @return bool true on successful unwatch
	 * @deprecated since 1.18
	 */
	public function doUnwatch() {
		global $wgUser;
		return WatchAction::doUnwatch( $this->getTitle(), $wgUser );
	}

	/**
	 * action=protect handler
	 */
	public function protect() {
		$form = new ProtectionForm( $this );
		$form->execute();
	}

	/**
	 * action=unprotect handler (alias)
	 */
	public function unprotect() {
		$this->protect();
	}

	/**
	 * Info about this page
	 * Called for ?action=info when $wgAllowPageInfo is on.
	 */
	public function info() {
		Action::factory( 'info', $this )->show();
	}

	/**
	 * Overriden by ImagePage class, only present here to avoid a fatal error
	 * Called for ?action=revert
	 */
	public function revert() {
		Action::factory( 'revert', $this )->show();
	}

	/**
	 * User interface for rollback operations
	 */
	public function rollback() {
		Action::factory( 'rollback', $this )->show();
	}

	/**
	 * Output a redirect back to the article.
	 * This is typically used after an edit.
	 *
	 * @deprecated in 1.18; call $wgOut->redirect() directly
	 * @param $noRedir Boolean: add redirect=no
	 * @param $sectionAnchor String: section to redirect to, including "#"
	 * @param $extraQuery String: extra query params
	 */
	public function doRedirect( $noRedir = false, $sectionAnchor = '', $extraQuery = '' ) {
		wfDeprecated( __METHOD__ );
		global $wgOut;

		if ( $noRedir ) {
			$query = 'redirect=no';
			if ( $extraQuery )
				$query .= "&$extraQuery";
		} else {
			$query = $extraQuery;
		}

		$wgOut->redirect( $this->getTitle()->getFullURL( $query ) . $sectionAnchor );
	}

	/**
	 * UI entry point for page deletion
	 */
	public function delete() {
		global $wgOut, $wgRequest;

		$confirm = $wgRequest->wasPosted() &&
				$this->getContext()->getUser()->matchEditToken( $wgRequest->getVal( 'wpEditToken' ) );

		$this->DeleteReasonList = $wgRequest->getText( 'wpDeleteReasonList', 'other' );
		$this->DeleteReason = $wgRequest->getText( 'wpReason' );

		$reason = $this->DeleteReasonList;

		if ( $reason != 'other' && $this->DeleteReason != '' ) {
			// Entry from drop down menu + additional comment
			$reason .= wfMsgForContent( 'colon-separator' ) . $this->DeleteReason;
		} elseif ( $reason == 'other' ) {
			$reason = $this->DeleteReason;
		}

		# Flag to hide all contents of the archived revisions
		$suppress = $wgRequest->getVal( 'wpSuppress' ) && $this->getContext()->getUser()->isAllowed( 'suppressrevision' );

		# This code desperately needs to be totally rewritten

		# Read-only check...
		if ( wfReadOnly() ) {
			$wgOut->readOnlyPage();

			return;
		}

		# Check permissions
		$permission_errors = $this->getTitle()->getUserPermissionsErrors( 'delete', $this->getContext()->getUser() );

		if ( count( $permission_errors ) > 0 ) {
			$wgOut->showPermissionsErrorPage( $permission_errors );

			return;
		}

		$wgOut->setPagetitle( wfMsg( 'delete-confirm', $this->getTitle()->getPrefixedText() ) );

		# Better double-check that it hasn't been deleted yet!
		$dbw = wfGetDB( DB_MASTER );
		$conds = $this->getTitle()->pageCond();
		$latest = $dbw->selectField( 'page', 'page_latest', $conds, __METHOD__ );
		if ( $latest === false ) {
			$wgOut->showFatalError(
				Html::rawElement(
					'div',
					array( 'class' => 'error mw-error-cannotdelete' ),
					wfMsgExt( 'cannotdelete', array( 'parse' ),
						wfEscapeWikiText( $this->getTitle()->getPrefixedText() ) )
				)
			);
			$wgOut->addHTML( Xml::element( 'h2', null, LogPage::logName( 'delete' ) ) );
			LogEventsList::showLogExtract(
				$wgOut,
				'delete',
				$this->getTitle()->getPrefixedText()
			);

			return;
		}

		# Hack for big sites
		$bigHistory = $this->mPage->isBigDeletion();
		if ( $bigHistory && !$this->getTitle()->userCan( 'bigdelete' ) ) {
			global $wgLang, $wgDeleteRevisionsLimit;

			$wgOut->wrapWikiMsg( "<div class='error'>\n$1\n</div>\n",
				array( 'delete-toobig', $wgLang->formatNum( $wgDeleteRevisionsLimit ) ) );

			return;
		}

		if ( $confirm ) {
			$this->doDelete( $reason, $suppress );

			if ( $wgRequest->getCheck( 'wpWatch' ) && $this->getContext()->getUser()->isLoggedIn() ) {
				$this->doWatch();
			} elseif ( $this->getTitle()->userIsWatching() ) {
				$this->doUnwatch();
			}

			return;
		}

		// Generate deletion reason
		$hasHistory = false;
		if ( !$reason ) {
			$reason = $this->generateReason( $hasHistory );
		}

		// If the page has a history, insert a warning
		if ( $hasHistory && !$confirm ) {
			global $wgLang;

			$revisions = $this->mPage->estimateRevisionCount();
			// @todo FIXME: i18n issue/patchwork message
			$wgOut->addHTML( '<strong class="mw-delete-warning-revisions">' .
				wfMsgExt( 'historywarning', array( 'parseinline' ), $wgLang->formatNum( $revisions ) ) .
				wfMsgHtml( 'word-separator' ) . Linker::link( $this->getTitle(),
					wfMsgHtml( 'history' ),
					array( 'rel' => 'archives' ),
					array( 'action' => 'history' ) ) .
				'</strong>'
			);

			if ( $bigHistory ) {
				global $wgDeleteRevisionsLimit;
				$wgOut->wrapWikiMsg( "<div class='error'>\n$1\n</div>\n",
					array( 'delete-warning-toobig', $wgLang->formatNum( $wgDeleteRevisionsLimit ) ) );
			}
		}

		return $this->confirmDelete( $reason );
	}

	/**
	 * Output deletion confirmation dialog
	 * @todo FIXME: Move to another file?
	 * @param $reason String: prefilled reason
	 */
	public function confirmDelete( $reason ) {
		global $wgOut;

		wfDebug( "Article::confirmDelete\n" );

		$deleteBackLink = Linker::linkKnown( $this->getTitle() );
		$wgOut->setSubtitle( wfMsgHtml( 'delete-backlink', $deleteBackLink ) );
		$wgOut->setRobotPolicy( 'noindex,nofollow' );
		$wgOut->addWikiMsg( 'confirmdeletetext' );

		wfRunHooks( 'ArticleConfirmDelete', array( $this, $wgOut, &$reason ) );

		if ( $this->getContext()->getUser()->isAllowed( 'suppressrevision' ) ) {
			$suppress = "<tr id=\"wpDeleteSuppressRow\">
					<td></td>
					<td class='mw-input'><strong>" .
						Xml::checkLabel( wfMsg( 'revdelete-suppress' ),
							'wpSuppress', 'wpSuppress', false, array( 'tabindex' => '4' ) ) .
					"</strong></td>
				</tr>";
		} else {
			$suppress = '';
		}
		$checkWatch = $this->getContext()->getUser()->getBoolOption( 'watchdeletion' ) || $this->getTitle()->userIsWatching();

		$form = Xml::openElement( 'form', array( 'method' => 'post',
			'action' => $this->getTitle()->getLocalURL( 'action=delete' ), 'id' => 'deleteconfirm' ) ) .
			Xml::openElement( 'fieldset', array( 'id' => 'mw-delete-table' ) ) .
			Xml::tags( 'legend', null, wfMsgExt( 'delete-legend', array( 'parsemag', 'escapenoentities' ) ) ) .
			Xml::openElement( 'table', array( 'id' => 'mw-deleteconfirm-table' ) ) .
			"<tr id=\"wpDeleteReasonListRow\">
				<td class='mw-label'>" .
					Xml::label( wfMsg( 'deletecomment' ), 'wpDeleteReasonList' ) .
				"</td>
				<td class='mw-input'>" .
					Xml::listDropDown( 'wpDeleteReasonList',
						wfMsgForContent( 'deletereason-dropdown' ),
						wfMsgForContent( 'deletereasonotherlist' ), '', 'wpReasonDropDown', 1 ) .
				"</td>
			</tr>
			<tr id=\"wpDeleteReasonRow\">
				<td class='mw-label'>" .
					Xml::label( wfMsg( 'deleteotherreason' ), 'wpReason' ) .
				"</td>
				<td class='mw-input'>" .
				Html::input( 'wpReason', $reason, 'text', array(
					'size' => '60',
					'maxlength' => '255',
					'tabindex' => '2',
					'id' => 'wpReason',
					'autofocus'
				) ) .
				"</td>
			</tr>";

		# Disallow watching if user is not logged in
		if ( $this->getContext()->getUser()->isLoggedIn() ) {
			$form .= "
			<tr>
				<td></td>
				<td class='mw-input'>" .
					Xml::checkLabel( wfMsg( 'watchthis' ),
						'wpWatch', 'wpWatch', $checkWatch, array( 'tabindex' => '3' ) ) .
				"</td>
			</tr>";
		}

		$form .= "
			$suppress
			<tr>
				<td></td>
				<td class='mw-submit'>" .
					Xml::submitButton( wfMsg( 'deletepage' ),
						array( 'name' => 'wpConfirmB', 'id' => 'wpConfirmB', 'tabindex' => '5' ) ) .
				"</td>
			</tr>" .
			Xml::closeElement( 'table' ) .
			Xml::closeElement( 'fieldset' ) .
			Html::hidden( 'wpEditToken', $this->getContext()->getUser()->editToken() ) .
			Xml::closeElement( 'form' );

			if ( $this->getContext()->getUser()->isAllowed( 'editinterface' ) ) {
				$title = Title::makeTitle( NS_MEDIAWIKI, 'Deletereason-dropdown' );
				$link = Linker::link(
					$title,
					wfMsgHtml( 'delete-edit-reasonlist' ),
					array(),
					array( 'action' => 'edit' )
				);
				$form .= '<p class="mw-delete-editreasons">' . $link . '</p>';
			}

		$wgOut->addHTML( $form );
		$wgOut->addHTML( Xml::element( 'h2', null, LogPage::logName( 'delete' ) ) );
		LogEventsList::showLogExtract( $wgOut, 'delete',
			$this->getTitle()->getPrefixedText()
		);
	}

	/**
	 * Perform a deletion and output success or failure messages
	 */
	public function doDelete( $reason, $suppress = false ) {
		global $wgOut;

		$id = $this->getTitle()->getArticleID( Title::GAID_FOR_UPDATE );

		$error = '';
		if ( $this->mPage->doDeleteArticle( $reason, $suppress, $id, $error ) ) {
			$deleted = $this->getTitle()->getPrefixedText();

			$wgOut->setPagetitle( wfMsg( 'actioncomplete' ) );
			$wgOut->setRobotPolicy( 'noindex,nofollow' );

			$loglink = '[[Special:Log/delete|' . wfMsgNoTrans( 'deletionlog' ) . ']]';

			$wgOut->addWikiMsg( 'deletedtext', wfEscapeWikiText( $deleted ), $loglink );
			$wgOut->returnToMain( false );
		} else {
			if ( $error == '' ) {
				$wgOut->showFatalError(
					Html::rawElement(
						'div',
						array( 'class' => 'error mw-error-cannotdelete' ),
						wfMsgExt( 'cannotdelete', array( 'parse' ),
							wfEscapeWikiText( $this->getTitle()->getPrefixedText() ) )
					)
				);

				$wgOut->addHTML( Xml::element( 'h2', null, LogPage::logName( 'delete' ) ) );

				LogEventsList::showLogExtract(
					$wgOut,
					'delete',
					$this->getTitle()->getPrefixedText()
				);
			} else {
				$wgOut->showFatalError( $error );
			}
		}
	}

	public function setOldSubtitle( $oldid = 0 ) {
		wfDeprecated( __METHOD__, '1.19-pageoutput' );
		return OutputPage::formatRobotPolicy( $policy );
	}

	/* Caching functions */

	/**
	 * checkLastModified returns true if it has taken care of all
	 * output to the client that is necessary for this request.
	 * (that is, it has sent a cached version of the page)
	 *
	 * @return boolean true if cached version send, false otherwise
	 */
	protected function tryFileCache() {
		static $called = false;

		if ( $called ) {
			wfDebug( "Article::tryFileCache(): called twice!?\n" );
			return false;
		}

		$called = true;
		if ( $this->isFileCacheable() ) {
			$cache = new HTMLFileCache( $this->getTitle() );
			if ( $cache->isFileCacheGood( $this->mPage->getTouched() ) ) {
				wfDebug( "Article::tryFileCache(): about to load file\n" );
				$cache->loadFromFileCache();
				return true;
			} else {
				wfDebug( "Article::tryFileCache(): starting buffer\n" );
				ob_start( array( &$cache, 'saveToFileCache' ) );
			}
		} else {
			wfDebug( "Article::tryFileCache(): not cacheable\n" );
		}

		return false;
	}

	/**
	 * Check if the page can be cached
	 * @return bool
	 */
	public function isFileCacheable() {
		$cacheable = false;

		if ( HTMLFileCache::useFileCache() ) {
			$cacheable = $this->mPage->getID() && !$this->mRedirectedFrom && !$this->getTitle()->isRedirect();
			// Extension may have reason to disable file caching on some pages.
			if ( $cacheable ) {
				$cacheable = wfRunHooks( 'IsFileCacheable', array( &$this ) );
			}
		}

		return $cacheable;
	}

	/**#@-*/

	public function outputWikiText( $text, $cache = true, $parserOptions = false ) {
		wfDeprecated( __METHOD__, '1.19-pageoutput' );
		return OutputPage::formatRobotPolicy( $policy );
	}

	/**
	 * Lightweight method to get the parser output for a page, checking the parser cache
	 * and so on. Doesn't consider most of the stuff that WikiPage::view is forced to
	 * consider, so it's not appropriate to use there.
	 *
	 * @since 1.16 (r52326) for LiquidThreads
	 *
	 * @param $oldid mixed integer Revision ID or null
	 * @param $user User The relevant user
	 * @return ParserOutput or false if the given revsion ID is not found
	 */
	public function getParserOutput( $oldid = null, User $user = null ) {
		global $wgEnableParserCache, $wgUser;
		$user = is_null( $user ) ? $wgUser : $user;

		wfProfileIn( __METHOD__ );
		// Should the parser cache be used?
		$useParserCache = $wgEnableParserCache &&
			$user->getStubThreshold() == 0 &&
			$this->mPage->exists() &&
			$oldid === null;

		wfDebug( __METHOD__ . ': using parser cache: ' . ( $useParserCache ? 'yes' : 'no' ) . "\n" );

		if ( $user->getStubThreshold() ) {
			wfIncrStats( 'pcache_miss_stub' );
		}

		if ( $useParserCache ) {
			$parserOutput = ParserCache::singleton()->get( $this, $this->mPage->getParserOptions() );
			if ( $parserOutput !== false ) {
				wfProfileOut( __METHOD__ );
				return $parserOutput;
			}
		}

		// Cache miss; parse and output it.
		if ( $oldid === null ) {
			$text = $this->mPage->getRawText();
		} else {
			$rev = Revision::newFromTitle( $this->getTitle(), $oldid );
			if ( $rev === null ) {
				wfProfileOut( __METHOD__ );
				return false;
			}
			$text = $rev->getText();
		}

		$output = $this->getOutputFromWikitext( $text, $useParserCache );
		wfProfileOut( __METHOD__ );
		return $output;
	}

	public function getOutputFromWikitext( $text, $cache = true, $parserOptions = false ) {
		wfDeprecated( __METHOD__, '1.19-pageoutput' );
		return OutputPage::formatRobotPolicy( $policy );
	}

	/**
	 * Sets the context this Article is executed in
	 *
	 * @param $context RequestContext
	 * @since 1.18
	 */
	public function setContext( $context ) {
		$this->mContext = $context;
	}

	/**
	 * Gets the context this Article is executed in
	 *
	 * @return RequestContext
	 * @since 1.18
	 */
	public function getContext() {
		if ( $this->mContext instanceof RequestContext ) {
			return $this->mContext;
		} else {
			wfDebug( __METHOD__ . " called and \$mContext is null. Return RequestContext::getMain(); for sanity\n" );
			return RequestContext::getMain();
		}
	}

	/**
	 * Use PHP's magic __get handler to handle accessing of
	 * raw WikiPage fields for backwards compatibility.
	 *
	 * @param $fname String Field name
	 */
	public function __get( $fname ) {
		if ( property_exists( $this->mPage, $fname ) ) {
			#wfWarn( "Access to raw $fname field " . __CLASS__ );
			return $this->mPage->$fname;
		}
		trigger_error( 'Inaccessible property via __get(): ' . $fname, E_USER_NOTICE );
	}

	/**
	 * Use PHP's magic __set handler to handle setting of
	 * raw WikiPage fields for backwards compatibility.
	 *
	 * @param $fname String Field name
	 * @param $fvalue mixed New value
	 */
	public function __set( $fname, $fvalue ) {
		if ( property_exists( $this->mPage, $fname ) ) {
			#wfWarn( "Access to raw $fname field of " . __CLASS__ );
			$this->mPage->$fname = $fvalue;
		// Note: extensions may want to toss on new fields
		} elseif ( !in_array( $fname, array( 'mContext', 'mPage' ) ) ) {
			$this->mPage->$fname = $fvalue;
		} else {
			trigger_error( 'Inaccessible property via __set(): ' . $fname, E_USER_NOTICE );
		}
	}

	/**
	 * Use PHP's magic __call handler to transform instance calls to
	 * WikiPage functions for backwards compatibility.
	 *
	 * @param $fname String Name of called method
	 * @param $args Array Arguments to the method
	 */
	public function __call( $fname, $args ) {
		if ( is_callable( array( $this->mPage, $fname ) ) ) {
			#wfWarn( "Call to " . __CLASS__ . "::$fname; please use WikiPage instead" );
			return call_user_func_array( array( $this->mPage, $fname ), $args );
		}
		trigger_error( 'Inaccessible function via __call(): ' . $fname, E_USER_ERROR );
	}

	// ****** B/C functions to work-around PHP silliness with __call and references ****** //
	public function updateRestrictions( $limit = array(), $reason = '', &$cascade = 0, $expiry = array() ) {
		return $this->mPage->updateRestrictions( $limit, $reason, $cascade, $expiry );
	}

	public function doDeleteArticle( $reason, $suppress = false, $id = 0, $commit = true, &$error = '' ) {
		return $this->mPage->doDeleteArticle( $reason, $suppress, $id, $commit, $error );
	}

	public function doRollback( $fromP, $summary, $token, $bot, &$resultDetails, User $user = null ) {
		global $wgUser;
		$user = is_null( $user ) ? $wgUser : $user;
		return $this->mPage->doRollback( $fromP, $summary, $token, $bot, $resultDetails, $user );
	}

	public function commitRollback( $fromP, $summary, $bot, &$resultDetails, User $guser = null ) {
		global $wgUser;
		$guser = is_null( $guser ) ? $wgUser : $guser;
		return $this->mPage->commitRollback( $fromP, $summary, $bot, $resultDetails, $guser );
	}

	public function generateReason( &$hasHistory ) {
		return $this->mPage->getAutoDeleteReason( $hasHistory );
	}

	// ****** B/C functions for static methods ( __callStatic is PHP>=5.3 ) ****** //
	public static function selectFields() {
		return WikiPage::selectFields();
	}

	public static function onArticleCreate( $title ) {
		return WikiPage::onArticleCreate( $title );
	}

	public static function onArticleDelete( $title ) {
		return WikiPage::onArticleDelete( $title );
	}

	public static function onArticleEdit( $title ) {
		return WikiPage::onArticleEdit( $title );
	}

	public static function getAutosummary( $oldtext, $newtext, $flags ) {
		return WikiPage::getAutosummary( $oldtext, $newtext, $flags );
	}
	// ******
}

