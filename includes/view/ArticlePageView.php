<?php

class ArticlePageView extends PageView {

	protected $mRedirectedFrom, $mRedirectUrl, $mParserOutput, $mParserOptions;
	private $mOldId, $mRevision, $mPage;

	function getTabs() {
	}

	/** **/

	/**
	 * Prepare the oldid, make any possible title changes based on it, and set the
	 * redirect url if needed.
	 */
	protected function prepareTitleAndRevision() {
		if ( !is_null($this->mOldId) && !is_null($this->mRedirectUrl) ) {
			// If oldid, redirecturl, and page are ready exit
			return;
		}
		
		$this->mRedirectUrl = false;

		$request = $this->getRequest();

		$oldid = $request->getVal( 'oldid' );

		if ( isset( $oldid ) ) {
			$oldid = intval( $oldid );
			if ( $request->getVal( 'direction' ) == 'next' ) {
				$nextid = $this->getTitle()->getNextRevisionID( $oldid );
				if ( $nextid ) {
					$oldid = $nextid;
				} else {
					$this->mRedirectUrl = $this->getTitle()->getFullURL( 'redirect=no' );
				}
			} elseif ( $request->getVal( 'direction' ) == 'prev' ) {
				$previd = $this->getTitle()->getPreviousRevisionID( $oldid );
				if ( $previd ) {
					$oldid = $previd;
				}
			}
		}

		if ( !$oldid ) {
			$oldid = 0;
		}

		$this->mOldId = $oldid;

		if ( $this->mOldId ) {
			// Preload the revision on an &oldid= to see if we need to use a different title
			// We may not need the Revision instance for parser cached current pages so
			// We don't load the revision yet for those
			# Fixme WikiPage actually holds latest rev information perhaps we shouldn't preload the revision instance
			$rev = Revision::newFromId( $this->mOldId );
			if ( $rev ) {
				$this->mRevision = $rev;
				if ( $this->getTitle()->getArticleID() != $rev->getPage() ) {
					$this->setTitle( $rev->getTitle() );
				}
			} else {
				// Make note that there was no revision found so we don't double-query for a nonexistant rev
				$this->mRevision = false;
			}
		}

	}

	/**
	 * Returns a WikiPage instance that can be used for this PageView
	 * @return WikiPage
	 */ 
	public function getPage() {
		if ( is_null( $this->mPage ) ) {
			$this->prepareTitleAndRevision();
			$this->mPage = WikiPage::factory( $this->getTitle() );
		}
		return $this->mPage;
	}

	/**
	 * Sets $this->mRedirectUrl to a correct URL if the query parameters are incorrect
	 * @return int The oldid of the article that is to be shown, 0 for the
	 *             current revision
	 */
	public function getOldID() {
		if ( is_null( $this->mOldId ) ) {
			$this->prepareTitleAndRevision();
		}
		return $this->mOldId;
	}

	/**
	 * Returns true if we are looking at the latest revision for a page
	 * Returns true if $oldid is 0 or oldid refers to the latest revision
	 * Note that this returns true for nonexistant pages unlike Article::isCurrent did
	 * @return bool
	 */
	public function isLatest() {
		$oldid = $this->getOldID();
		return !$oldid || !$this->mRevision || $this->mRevision->isCurrent();
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

		return $this->getPage()->exists() && $this->getPage()->getLatest() == $this->getOldID();
	}

	/**
	 * Returns a revision instance for the oldid or current page we are displaying
	 * @return Revision
	 * @fixme Article::fetchContent had a lot of wfDebug calls we should replicate
	 */
	public function getRevision() {
		# getOldID calls prepareTitleAndRevision which may preload mRevision so
		# we call this here instead of when we try to create a rev
		$oldid = $this->getOldID();
		if ( is_null($this->mRevision) ) {
			if ( !$oldid || $oldid == $this->getPage()->getLatest() ) {
				# WikiPage caches a revision on it's own, query from there for
				# the latest revision to avoid double loading from the database
				$this->mRevision = $this->getPage()->getRevision();
			} else {
				$this->mRevision = Revision::newFromID( $oldid );
			}
			if ( is_null($this->mRevision) ) {
				# If revision was not loaded set mRevision to false to avoid double-querying the database
				$this->mRevision = false;
			}
		}
		return $this->mRevision;
	}

	/**
	 * Get the robot policy to be used for the current view
	 * @param $pOutput ParserOutput
	 * @return Array the policy that should be set
	 */
	public function getRobotPolicy( $pOutput ) {
		global $wgArticleRobotPolicies, $wgNamespaceRobotPolicies;
		global $wgDefaultRobotPolicy;

		$ns = $this->getTitle()->getNamespace();

		if ( $ns == NS_USER || $ns == NS_USER_TALK ) {
			# Don't index user and user talk pages for blocked users (bug 11443)
			if ( !$this->getTitle()->isSubpage() ) {
				if ( Block::newFromTarget( null, $this->getTitle()->getText() ) instanceof Block ) {
					return array(
						'index'  => 'noindex',
						'follow' => 'nofollow'
					);
				}
			}
		}

		if ( $this->getPage()->getID() === 0 || $this->getOldID() ) {
			# Non-articles (special pages etc), and old revisions
			return array(
				'index'  => 'noindex',
				'follow' => 'nofollow'
			);
		} elseif ( $this->getOutput()->isPrintable() ) {
			# Discourage indexing of printable versions, but encourage following
			return array(
				'index'  => 'noindex',
				'follow' => 'follow'
			);
		} elseif ( $this->getRequest()->getInt( 'curid' ) ) {
			# For ?curid=x urls, disallow indexing
			return array(
				'index'  => 'noindex',
				'follow' => 'follow'
			);
		}

		# Otherwise, construct the policy based on the various config variables.
		$policy = OutputPage::formatRobotPolicy( $wgDefaultRobotPolicy );

		if ( isset( $wgNamespaceRobotPolicies[$ns] ) ) {
			# Honour customised robot policies for this namespace
			$policy = array_merge(
				$policy,
				OutputPage::formatRobotPolicy( $wgNamespaceRobotPolicies[$ns] )
			);
		}
		if ( $this->getTitle()->canUseNoindex() && is_object( $pOutput ) && $pOutput->getIndexPolicy() ) {
			# __INDEX__ and __NOINDEX__ magic words, if allowed. Incorporates
			# a final sanity check that we have really got the parser output.
			$policy = array_merge(
				$policy,
				array( 'index' => $pOutput->getIndexPolicy() )
			);
		}

		if ( isset( $wgArticleRobotPolicies[$this->getTitle()->getPrefixedText()] ) ) {
			# (bug 14900) site config can override user-defined __INDEX__ or __NOINDEX__
			$policy = array_merge(
				$policy,
				OutputPage::formatRobotPolicy( $wgArticleRobotPolicies[$this->getTitle()->getPrefixedText()] )
			);
		}

		return $policy;
	}

	/** PageView render and sub-areas of the rendering process **/

	function render() {
		global $wgParser;
		global $wgUseFileCache, $wgUseETag;

		wfProfileIn( __METHOD__ );

		$request = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		// Prepare some info we might need, this will set mRedirectUrl if needed
		$this->prepareTitleAndRevision();

		# getOldID may want us to redirect somewhere else
		if ( $this->mRedirectUrl ) {
			$out->redirect( $this->mRedirectUrl );
			wfDebug( __METHOD__ . ": redirecting due to oldid\n" );
			wfProfileOut( __METHOD__ );

			return;
		}

		# Ensure that the user has the rights to read this page
		if ( !$this->getTitle()->userCanRead() ) {
			$out->loginToUse();
			$out->output();
			$out->disable();
			wfProfileOut( __METHOD__ );
			return;
		}

		# Set page title (may be overridden by DISPLAYTITLE)
		$out->setPageTitle( $this->getTitle()->getPrefixedText() );

		# If we got diff in the query, we want to see a diff page instead of the article.
		if ( $request->getCheck( 'diff' ) ) {
			wfDebug( __METHOD__ . ": showing diff page\n" );
			// @fixme DifferenceEngine shouldn't be rendering anything other than the diff, we should handle diffonly ourselves
			$this->showDiffPage();
			wfProfileOut( __METHOD__ );

			return;
		}

		$out->setArticleFlag( true );
		# Allow frames by default
		$out->allowClickjacking();

		$parserCache = ParserCache::singleton();

		$parserOptions = $this->getParserOptions();
		# Render printable version, use printable version cache
		// @fixme printable check should be moved from Wiki.php to PageView
		if ( $out->isPrintable() ) {
			$parserOptions->setIsPrintable( true );
			$parserOptions->setEditSection( false );
		} elseif ( $wgUseETag && !$this->getTitle()->quickUserCan( 'edit' ) ) {
			$parserOptions->setEditSection( false );
		}

		# Get variables from query string
		$oldid = $this->getOldID();

		# Try client and file cache
		# @fixme: We can't return file cached content for a permalink to the current
		#         revision, however we CAN return a 304 for the current revision's permalink
		if ( $oldid === 0 && $this->getPage()->checkTouched() ) {
			if ( $wgUseETag ) {
				$out->setETag( $parserCache->getETag( $this->getPage(), $parserOptions ) );
			}

			# Is it client cached?
			if ( $out->checkLastModified( $this->getPage()->getTouched() ) ) {
				wfDebug( __METHOD__ . ": done 304\n" );
				wfProfileOut( __METHOD__ );

				return;
			# Try file cache
			} elseif ( $wgUseFileCache && $this->tryFileCache() ) {
				wfDebug( __METHOD__ . ": done file cache\n" );
				# tell wgOut that output is taken care of
				$out->disable();
				$this->getPage()->viewUpdates();
				wfProfileOut( __METHOD__ );

				return;
			}
		}

		if ( !$wgUseETag && !$this->getTitle()->quickUserCan( 'edit' ) ) {
			$parserOptions->setEditSection( false );
		}

		# Should the parser cache be used?
		$useParserCache = $this->getPage()->isParserCacheUsed( $user ) && $this->isCurrent();
		wfDebug( __METHOD__ . ' using parser cache: ' . ( $useParserCache ? 'yes' : 'no' ) . "\n" );
		if ( $user->getStubThreshold() ) {
			wfIncrStats( 'pcache_miss_stub' );
		}

		$wasRedirected = $this->showRedirectedFromHeader();
		$this->showNamespaceHeader();

		$outputDone = false;
		$this->mParserOutput = false;

		wfRunHooks( 'ArticleViewHeader', array( &$this, &$outputDone, &$useParserCache ) ); // @fixme

		# Try the parser cache for page output
		if ( !$outputDone && $useParserCache ) {
			$this->mParserOutput = $parserCache->get( $this->getPage(), $parserOptions );

			if ( $this->mParserOutput !== false ) {
				wfDebug( __METHOD__ . ": showing parser cache contents\n" );
				$out->addParserOutput( $this->mParserOutput );
				# Ensure that UI elements requiring revision ID have
				# the correct version information.
				$out->setRevisionId( $this->getPage()->getLatest() );
				$outputDone = true;
				# Preload timestamp to avoid a DB hit
				if ( isset( $this->mParserOutput->mTimestamp ) ) {
					$this->getPage()->setTimestamp( $this->mParserOutput->mTimestamp );
				}
			}
		}

		# At this point if we haven't found anything in any cache see if the page
		# even exists, if not show a missing article page
		if ( !$outputDone && $this->getPage()->getID() == 0 ) {
			wfDebug( __METHOD__ . ": showing missing article\n" );
			$this->showMissingArticle();
			wfProfileOut( __METHOD__ );
			return;
		}

		# Fetch the content and see if there is any specialty page handling to do instead of parsing
		if ( !$outputDone ) {
			$rev = $this->getRevision();
			
			if ( $rev ) {
				$text = $rev->getText( Revision::FOR_THIS_USER ); // Loads if user is allowed
			} else {
				$text = false;
			}
			
			wfRunHooks( 'ArticleAfterFetchContent', array( &$this, &$text ) ); // @fixme
			
			# Check if the text is false, this can happen due to slow slaves (bad oldids too?)
			if ( $text === false ) {
				wfDebug( __METHOD__ . ": showing missing article due to false return from revision text\n" );
				$this->showMissingArticle();
				wfProfileOut( __METHOD__ );
				return;
			}

			# Ensure that UI elements requiring revision ID have
			# the correct version information.
			$out->setRevisionId( $rev->getId() );

			# Pages containing custom CSS or JavaScript get special treatment
			if ( $this->getTitle()->isCssOrJsPage() || $this->getTitle()->isCssJsSubpage() ) {
				wfDebug( __METHOD__ . ": showing CSS/JS source\n" );
				$this->showCssOrJsPage();
				$outputDone = true;
			} elseif( !wfRunHooks( 'ArticleViewCustom', array( $text, $this->getTitle(), $out ) ) ) { // @fixme
				# Allow extensions do their own custom view for certain pages
				$outputDone = true;
			} else {
				$rt = Title::newFromRedirectArray( $text );
				if ( $rt ) {
					wfDebug( __METHOD__ . ": showing redirect=no page\n" );
					# Viewing a redirect page (e.g. with parameter redirect=no)
					# Don't append the subtitle if this was an old revision
					$out->addHTML( $this->viewRedirect( $rt, !$wasRedirected && $this->isCurrent() ) );
					# Parse just to get categories, displaytitle, etc.
					$this->mParserOutput = $wgParser->parse( $text, $this->getTitle(), $parserOptions );
					$out->addParserOutputNoText( $this->mParserOutput );
					$outputDone = true;
				}
			}
		}

		# Run the parse, protected by a pool counter
		if ( !$outputDone ) {
			wfDebug( __METHOD__ . ": doing uncached parse\n" );

			$key = $parserCache->getKey( $this->getPage(), $parserOptions );
			$poolArticleView = new PoolWorkArticleView( $this, $key, $useParserCache, $parserOptions, $text );

			if ( !$poolArticleView->execute() ) {
				# Connection or timeout error
				wfProfileOut( __METHOD__ );
				return;
			} else {
				$outputDone = true;
			}
		}

		# Are we looking at an old revision
		if ( $oldid ) {
			$this->setOldSubtitle();

			if ( !$this->showDeletedRevisionHeader() ) {
				wfDebug( __METHOD__ . ": cannot view deleted revision\n" );
				wfProfileOut( __METHOD__ );
				return;
			}
		}

		# Get the ParserOutput actually *displayed* here.
		# Note that $this->mParserOutput is the *current* version output.
		$pOutput = ( $outputDone instanceof ParserOutput )
			? $outputDone // object fetched by hook
			: $this->mParserOutput;

		# Adjust title for main page & pages with displaytitle
		if ( $pOutput ) {
			$this->adjustDisplayTitle( $pOutput );
		}

		# For the main page, overwrite the <title> element with the con-
		# tents of 'pagetitle-view-mainpage' instead of the default (if
		# that's not empty).
		# This message always exists because it is in the i18n files
		if ( $this->getTitle()->isMainPage() ) {
			$msg = wfMessage( 'pagetitle-view-mainpage' )->inContentLanguage();
			if ( !$msg->isDisabled() ) {
				$out->setHTMLTitle( $msg->title( $this->getTitle() )->text() );
			}
		}

		# Check for any __NOINDEX__ tags on the page using $pOutput
		$policy = $this->getRobotPolicy( $pOutput );
		$out->setIndexPolicy( $policy['index'] );
		$out->setFollowPolicy( $policy['follow'] );

		$this->showViewFooter();
		$this->getPage()->viewUpdates();
		wfProfileOut( __METHOD__ );
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
		global $wgStylePath;

		if ( !is_array( $target ) ) {
			$target = array( $target );
		}

		$lang = $this->getTitle()->getPageLanguage();
		$imageDir = $lang->getDir();

		if ( $appendSubtitle ) {
			$this->getOutput()->appendSubtitle( wfMsgHtml( 'redirectpagesub' ) );
		}

		// the loop prepends the arrow image before the link, so the first case needs to be outside
		$title = array_shift( $target );

		if ( $forceKnown ) {
			$link = Linker::linkKnown( $title, htmlspecialchars( $title->getFullText() ) );
		} else {
			$link = Linker::link( $title, htmlspecialchars( $title->getFullText() ) );
		}

		$nextRedirect = $wgStylePath . '/common/images/nextredirect' . $imageDir . '.png';
		$alt = $lang->isRTL() ? '←' : '→';
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

	/*
	 * Adjust title for pages with displaytitle, -{T|}- or language conversion
	 * @param $pOutput ParserOutput
	 */
	public function adjustDisplayTitle( ParserOutput $pOutput ) {
		# Adjust the title if it was set by displaytitle, -{T|}- or language conversion
		$titleText = $pOutput->getTitleText();
		if ( strval( $titleText ) !== '' ) {
			$this->getOutput()->setPageTitle( $titleText );
		}
	}

	/**
	 * If this request is a redirect view, send "redirected from" subtitle to
	 * OutputPage. Returns true if the header was needed, false if this is not a
	 * redirect view. Handles both local and remote redirects.
	 *
	 * @return boolean
	 */
	public function showRedirectedFromHeader() {
		global $wgRedirectSources;

		$rdfrom = $this->getRequest()->getVal( 'rdfrom' );

		$out = $this->getOutput();

		if ( isset( $this->mRedirectedFrom ) ) {
			// This is an internally redirected page view.
			// We'll need a backlink to the source page for navigation.
			if ( wfRunHooks( 'ArticleViewRedirect', array( &$this ) ) ) { // @fixme
				$redir = Linker::link(
					$this->mRedirectedFrom,
					null,
					array(),
					array( 'redirect' => 'no' ),
					array( 'known', 'noclasses' )
				);

				$s = wfMsgExt( 'redirectedfrom', array( 'parseinline', 'replaceafter' ), $redir );
				$out->setSubtitle( $s );

				// Set the fragment if one was specified in the redirect
				if ( strval( $this->getTitle()->getFragment() ) != '' ) {
					$fragment = Xml::escapeJsString( $this->getTitle()->getFragmentForURL() );
					$out->addInlineScript( "redirectToFragment(\"$fragment\");" );
				}

				// @fixme Always set a canonical that takes $wgCanonicalServer into account
				// Add a <link rel="canonical"> tag
				$out->addLink( array( 'rel' => 'canonical',
					'href' => $this->getTitle()->getLocalURL() )
				);

				return true;
			}
		} elseif ( $rdfrom ) {
			// This is an externally redirected view, from some other wiki.
			// If it was reported from a trusted site, supply a backlink.
			if ( $wgRedirectSources && preg_match( $wgRedirectSources, $rdfrom ) ) {
				$redir = Linker::makeExternalLink( $rdfrom, $rdfrom );
				$s = wfMsgExt( 'redirectedfrom', array( 'parseinline', 'replaceafter' ), $redir );
				$out->setSubtitle( $s );

				return true;
			}
		}

		return false;
	}

	/**
	 * Show a header specific to the namespace currently being viewed, like
	 * [[MediaWiki:Talkpagetext]]..
	 */
	public function showNamespaceHeader() {
		if ( $this->getTitle()->isTalkPage() ) {
			if ( !wfMessage( 'talkpageheader' )->isDisabled() ) {
				$this->getOutput()->wrapWikiMsg( "<div class=\"mw-talkpageheader\">\n$1\n</div>", array( 'talkpageheader' ) );
			}
		}
	}

	/**
	 * Show the footer section of an ordinary page view
	 */
	public function showViewFooter() {
		global $wgUseTrackbacks;

		# check if we're displaying a [[User talk:x.x.x.x]] anonymous talk page
		if ( $this->getTitle()->getNamespace() == NS_USER_TALK && IP::isValid( $this->getTitle()->getText() ) ) {
			$this->getOutput()->addWikiMsg( 'anontalkpagetext' );
		}

		# If we have been passed an &rcid= parameter, we want to give the user a
		# chance to mark this new article as patrolled.
		$this->showPatrolFooter();

		# Trackbacks
		if ( $wgUseTrackbacks ) {
			$this->addTrackbacks();
		}

		wfRunHooks( 'ArticleViewFooter', array( $this ) ); // @fixme

	}

	/**
	 * If patrol is possible, output a patrol UI box. This is called from the
	 * footer section of ordinary page views. If patrol is not possible or not
	 * desired, does nothing.
	 */
	public function showPatrolFooter() {
		$rcid = $this->getRequest()->getVal( 'rcid' );

		if ( !$rcid || !$this->getTitle()->quickUserCan( 'patrol' ) ) {
			return;
		}

		$token = $this->getUser()->editToken( $rcid );
		$this->getOutput()->preventClickjacking();

		$this->getOutput()->addHTML(
			"<div class='patrollink'>" .
				wfMsgHtml(
					'markaspatrolledlink',
					Linker::link(
						$this->getTitle(),
						wfMsgHtml( 'markaspatrolledtext' ),
						array(),
						array(
							'action' => 'markpatrolled',
							'rcid' => $rcid,
							'token' => $token,
						),
						array( 'known', 'noclasses' )
					)
				) .
			'</div>'
		);
	}

	/**
	 * Show the error text for a missing article. For articles in the MediaWiki
	 * namespace, show the default message text.
	 */
	public function showMissingArticle() {
		$out = $this->getOutput();
		$user = $this->getUser();

		# Show info in user (talk) namespace. Does the user exist? Is he blocked?
		if ( $this->getTitle()->getNamespace() == NS_USER || $this->getTitle()->getNamespace() == NS_USER_TALK ) {
			$parts = explode( '/', $this->getTitle()->getText() );
			$rootPart = $parts[0];
			$user = User::newFromName( $rootPart, false /* allow IP users*/ );
			$ip = User::isIP( $rootPart );

			if ( !$user->isLoggedIn() && !$ip ) { # User does not exist
				$out->wrapWikiMsg( "<div class=\"mw-userpage-userdoesnotexist error\">\n\$1\n</div>",
					array( 'userpage-userdoesnotexist-view', wfEscapeWikiText( $rootPart ) ) );
			} elseif ( $user->isBlocked() ) { # Show log extract if the user is currently blocked
				LogEventsList::showLogExtract(
					$out,
					'block',
					$user->getUserPage()->getPrefixedText(),
					'',
					array(
						'lim' => 1,
						'showIfEmpty' => false,
						'msgKey' => array(
							'blocked-notice-logextract',
							$user->getName() # Support GENDER in notice
						)
					)
				);
			}
		}

		wfRunHooks( 'ShowMissingArticle', array( $this ) ); // @fixme

		# Show delete and move logs
		LogEventsList::showLogExtract( $out, array( 'delete', 'move' ), $this->getTitle()->getPrefixedText(), '',
			array(  'lim' => 10,
				'conds' => array( "log_action != 'revision'" ),
				'showIfEmpty' => false,
				'msgKey' => array( 'moveddeleted-notice' ) )
		);

		# Show error message
		$oldid = $this->getOldID();
		if ( $oldid ) {
			$text = wfMsgNoTrans( 'missing-article',
				$this->getTitle()->getPrefixedText(),
				wfMsgNoTrans( 'missingarticle-rev', $oldid ) );
		} elseif ( $this->getTitle()->getNamespace() === NS_MEDIAWIKI ) {
			// Use the default message text
			$text = $this->getTitle()->getDefaultMessageText();
		} else {
			$createErrors = $this->getTitle()->getUserPermissionsErrors( 'create', $user );
			$editErrors = $this->getTitle()->getUserPermissionsErrors( 'edit', $user );
			$errors = array_merge( $createErrors, $editErrors );

			if ( !count( $errors ) ) {
				$text = wfMsgNoTrans( 'noarticletext' );
			} else {
				$text = wfMsgNoTrans( 'noarticletext-nopermission' );
			}
		}
		$text = "<div class='noarticletext'>\n$text\n</div>";

		if ( !$this->getPage()->hasViewableContent() ) {
			// If there's no backing content, send a 404 Not Found
			// for better machine handling of broken links.
			$this->getRequest()->response()->header( "HTTP/1.1 404 Not Found" );
		}

		$out->addWikiText( $text );
	}

	/**
	 * If the revision requested for view is deleted, check permissions.
	 * Send either an error message or a warning header to OutputPage.
	 *
	 * @return boolean true if the view is allowed, false if not.
	 */
	public function showDeletedRevisionHeader() {
		$out = $this->getOutput();

		if ( !$this->getRevision()->isDeleted( Revision::DELETED_TEXT ) ) {
			// Not deleted
			return true;
		}

		// If the user is not allowed to see it...
		if ( !$this->getRevision()->userCan( Revision::DELETED_TEXT ) ) {
			$out->wrapWikiMsg( "<div class='mw-warning plainlinks'>\n$1\n</div>\n",
				'rev-deleted-text-permission' );

			return false;
		// If the user needs to confirm that they want to see it...
		} elseif ( $this->getRequest()->getInt( 'unhide' ) != 1 ) {
			# Give explanation and add a link to view the revision...
			$oldid = intval( $this->getOldID() );
			$link = $this->getTitle()->getFullUrl( "oldid={$oldid}&unhide=1" );
			$msg = $this->getRevision()->isDeleted( Revision::DELETED_RESTRICTED ) ?
				'rev-suppressed-text-unhide' : 'rev-deleted-text-unhide';
			$out->wrapWikiMsg( "<div class='mw-warning plainlinks'>\n$1\n</div>\n",
				array( $msg, $link ) );

			return false;
		// We are allowed to see...
		} else {
			$msg = $this->getRevision()->isDeleted( Revision::DELETED_RESTRICTED ) ?
				'rev-suppressed-text-view' : 'rev-deleted-text-view';
			$out->wrapWikiMsg( "<div class='mw-warning plainlinks'>\n$1\n</div>\n", $msg );

			return true;
		}
	}

	/**
	 * Generate the navigation links when browsing through an article revisions
	 * It shows the information as:
	 *   Revision as of \<date\>; view current revision
	 *   \<- Previous version | Next Version -\>
	 */
	public function setOldSubtitle() {
		if ( !wfRunHooks( 'DisplayOldSubtitle', array( &$this, &$oldid ) ) ) { // @fixme
			return;
		}

		$request = $this->getRequest();
		$lang = $this->getLang();

		$unhide = $request->getInt( 'unhide' ) == 1;

		# Cascade unhide param in links for easy deletion browsing
		$extraParams = array();
		if ( $request->getVal( 'unhide' ) ) {
			$extraParams['unhide'] = 1;
		}

		$revision = $this->getRevision();
		$timestamp = $revision->getTimestamp();

		$current = $this->isCurrent();
		$td = $lang->timeanddate( $timestamp, true );
		$tddate = $lang->date( $timestamp, true );
		$tdtime = $lang->time( $timestamp, true );

		$lnk = $current
			? wfMsgHtml( 'currentrevisionlink' )
			: Linker::link(
				$this->getTitle(),
				wfMsgHtml( 'currentrevisionlink' ),
				array(),
				$extraParams,
				array( 'known', 'noclasses' )
			);
		$curdiff = $current
			? wfMsgHtml( 'diff' )
			: Linker::link(
				$this->getTitle(),
				wfMsgHtml( 'diff' ),
				array(),
				array(
					'diff' => 'cur',
					'oldid' => $this->getOldID()
				) + $extraParams,
				array( 'known', 'noclasses' )
			);
		$prev = $this->getTitle()->getPreviousRevisionID( $this->getOldID() ) ;
		$prevlink = $prev
			? Linker::link(
				$this->getTitle(),
				wfMsgHtml( 'previousrevision' ),
				array(),
				array(
					'direction' => 'prev',
					'oldid' => $this->getOldID()
				) + $extraParams,
				array( 'known', 'noclasses' )
			)
			: wfMsgHtml( 'previousrevision' );
		$prevdiff = $prev
			? Linker::link(
				$this->getTitle(),
				wfMsgHtml( 'diff' ),
				array(),
				array(
					'diff' => 'prev',
					'oldid' => $this->getOldID()
				) + $extraParams,
				array( 'known', 'noclasses' )
			)
			: wfMsgHtml( 'diff' );
		$nextlink = $current
			? wfMsgHtml( 'nextrevision' )
			: Linker::link(
				$this->getTitle(),
				wfMsgHtml( 'nextrevision' ),
				array(),
				array(
					'direction' => 'next',
					'oldid' => $this->getOldID()
				) + $extraParams,
				array( 'known', 'noclasses' )
			);
		$nextdiff = $current
			? wfMsgHtml( 'diff' )
			: Linker::link(
				$this->getTitle(),
				wfMsgHtml( 'diff' ),
				array(),
				array(
					'diff' => 'next',
					'oldid' => $this->getOldID()
				) + $extraParams,
				array( 'known', 'noclasses' )
			);

		$cdel = '';

		// User can delete revisions or view deleted revisions...
		$canHide = $this->getUser()->isAllowed( 'deleterevision' );
		if ( $canHide || ( $revision->getVisibility() && $this->getUser()->isAllowed( 'deletedhistory' ) ) ) {
			if ( !$revision->userCan( Revision::DELETED_RESTRICTED ) ) {
				$cdel = Linker::revDeleteLinkDisabled( $canHide ); // rev was hidden from Sysops
			} else {
				$query = array(
					'type'   => 'revision',
					'target' => $this->getTitle()->getPrefixedDbkey(),
					'ids'    => $this->getOldID()
				);
				$cdel = Linker::revDeleteLink( $query, $revision->isDeleted( File::DELETED_RESTRICTED ), $canHide );
			}
			$cdel .= ' ';
		}

		# Show user links if allowed to see them. If hidden, then show them only if requested...
		$userlinks = Linker::revUserTools( $revision, !$unhide );

		$infomsg = $current && !wfMessage( 'revision-info-current' )->isDisabled()
			? 'revision-info-current'
			: 'revision-info';

		$r = "\n\t\t\t\t<div id=\"mw-{$infomsg}\">" .
			wfMsgExt(
				$infomsg,
				array( 'parseinline', 'replaceafter' ),
				$td,
				$userlinks,
				$revision->getID(),
				$tddate,
				$tdtime,
				$revision->getUser()
			) .
			"</div>\n" .
			"\n\t\t\t\t<div id=\"mw-revision-nav\">" . $cdel . wfMsgExt( 'revision-nav', array( 'escapenoentities', 'parsemag', 'replaceafter' ),
			$prevdiff, $prevlink, $lnk, $curdiff, $nextlink, $nextdiff ) . "</div>\n\t\t\t";

		$this->getOutput()->setSubtitle( $r );
	}

	/** Code called by PoolWorkArticleView **/

	/**
	 * Execute the uncached parse for action=view
	 */
	public function doViewParse( $text ) {
		$oldid = $this->getOldID();
		$parserOptions = $this->getParserOptions();

		# Render printable version, use printable version cache
		$parserOptions->setIsPrintable( $this->getOutput()->isPrintable() );

		# Don't show section-edit links on old revisions... this way lies madness.
		if ( !$this->isCurrent() || $this->getOutput() || !$this->getTitle()->quickUserCan( 'edit' ) ) {
			$parserOptions->setEditSection( false );
		}

		$useParserCache = $this->getPage()->isParserCacheUsed( $this->getUser() ) && $this->isCurrent();
		$this->outputWikiText( $text, $useParserCache, $parserOptions );

		return true;
	}

	/**
	 * Add the primary page-view wikitext to the output buffer
	 * Saves the text into the parser cache if possible.
	 * Updates templatelinks if it is out of date.
	 *
	 * @param $text String
	 * @param $cache Boolean
	 * @param $parserOptions mixed ParserOptions object, or boolean false
	 */
	public function outputWikiText( $text, $cache = true, $parserOptions = false ) {
		$this->mParserOutput = $this->getOutputFromWikitext( $text, $cache, $parserOptions );

		$this->getPage()->doCascadeProtectionUpdates( $this->mParserOutput );

		$this->getOutput()->addParserOutput( $this->mParserOutput );
	}

	/**
	 * This does all the heavy lifting for outputWikitext, except it returns the parser
	 * output instead of sending it straight to OutputPage. Makes things nice and simple for,
	 * say, embedding thread pages within a discussion system (LiquidThreads)
	 *
	 * @param $text string
	 * @param $cache boolean
	 * @param $parserOptions parsing options, defaults to false
	 * @return ParserOutput
	 */
	public function getOutputFromWikitext( $text, $cache = true, $parserOptions = false ) {
		global $wgParser, $wgEnableParserCache, $wgUseFileCache;

		if ( !$parserOptions ) {
			$parserOptions = $this->getParserOptions();
		}

		$time = - wfTime();
		$this->mParserOutput = $wgParser->parse( $text, $this->getTitle(),
			$parserOptions, true, true, $this->getRevision()->getId() );
		$time += wfTime();

		# Timing hack
		if ( $time > 3 ) {
			wfDebugLog( 'slow-parse', sprintf( "%-5.2f %s", $time,
				$this->getTitle()->getPrefixedDBkey() ) );
		}

		if ( $wgEnableParserCache && $cache && $this->mParserOutput->isCacheable() ) {
			$parserCache = ParserCache::singleton();
			$parserCache->save( $this->mParserOutput, $this->getPage(), $parserOptions );
		}

		// Make sure file cache is not used on uncacheable content.
		// Output that has magic words in it can still use the parser cache
		// (if enabled), though it will generally expire sooner.
		if ( !$this->mParserOutput->isCacheable() || $this->mParserOutput->containsOldMagic() ) {
			$wgUseFileCache = false;
		}

		if ( $this->isCurrent() ) {
			$this->getPage()->doCascadeProtectionUpdates( $this->mParserOutput );
		}

		return $this->mParserOutput;
	}

	/**
	 * Get parser options suitable for rendering the primary article wikitext
	 * @return mixed ParserOptions object or boolean false
	 */
	public function getParserOptions() {
		if ( !$this->mParserOptions ) {
			$this->mParserOptions = $this->getPage()->makeParserOptions( $this->getUser() );
		}
		// Clone to allow modifications of the return value without affecting cache
		return clone $this->mParserOptions;
	}

	/**
	 * Try to fetch an expired entry from the parser cache. If it is present,
	 * output it and return true. If it is not present, output nothing and
	 * return false. This is used as a callback function for
	 * PoolCounter::executeProtected().
	 *
	 * @return boolean
	 */
	public function tryDirtyCache() {
		$out = $this->getOutput();
		$parserCache = ParserCache::singleton();
		$options = $this->getParserOptions();

		if ( $out->isPrintable() ) {
			$options->setIsPrintable( true );
			$options->setEditSection( false );
		}

		$output = $parserCache->getDirty( $this, $options );

		if ( $output ) {
			wfDebug( __METHOD__ . ": sending dirty output\n" );
			wfDebugLog( 'dirty', "dirty output " . $parserCache->getKey( $this, $options ) . "\n" );
			$out->setSquidMaxage( 0 );
			$this->mParserOutput = $output;
			$out->addParserOutput( $output );
			$out->addHTML( "<!-- parser cache is expired, sending anyway due to pool overload-->\n" );

			return true;
		} else {
			wfDebugLog( 'dirty', "dirty missing\n" );
			wfDebug( __METHOD__ . ": no dirty cache\n" );

			return false;
		}
	}
}

class PoolWorkArticleView extends PoolCounterWork {

	/**
	 * @var PageView
	 */
	private $view;

	function __construct( $view, $key, $useParserCache, $parserOptions, $text ) {
		parent::__construct( 'ArticleView', $key );
		$this->view = $view;
		$this->cacheable = $useParserCache;
		$this->parserOptions = $parserOptions;
		$this->text = $text;
	}

	function doWork() {
		return $this->view->doViewParse( $this->text );
	}

	function getCachedWork() {
		$parserCache = ParserCache::singleton();
		$this->view->mParserOutput = $parserCache->get( $this->mArticle, $this->parserOptions );

		if ( $this->mArticle->mParserOutput !== false ) {
			wfDebug( __METHOD__ . ": showing contents parsed by someone else\n" );
			$out = $this->view->getOutput();
			$out->addParserOutput( $this->view->mParserOutput );
			# Ensure that UI elements requiring revision ID have
			# the correct version information.
			$out->setRevisionId( $this->view->getPage()->getLatest() );
			return true;
		}
		return false;
	}

	function fallback() {
		return $this->view->tryDirtyCache();
	}

	/**
	 * @param $status Status
	 */
	function error( $status ) {
		$out = $this->view->getOutput();

		$out->clearHTML(); // for release() errors
		$out->enableClientCache( false );
		$out->setRobotPolicy( 'noindex,nofollow' );

		$errortext = $status->getWikiText( false, 'view-pool-error' );
		$out->addWikiText( '<div class="errorbox">' . $errortext . '</div>' );

		return false;
	}
}
