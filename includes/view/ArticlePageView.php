<?php

class ArticlePageView extends PageView {

	private $mOldId, $mRedirectedFrom, $mRedirectUrl, $mPage, $mParserOutput;

	function getTabs() {
	}

	/** **/

	public function getPage() {
		if ( is_null( $this->mPage ) ) {
			$this->mPage = WikiPage::factory( $this->getTitle() );
		}
		return $this->mPage;
	}

	/**
	 * @return int The oldid of the article that is to be shown, 0 for the
	 *             current revision
	 */
	public function getOldID() {
		// @fixme Merge getOldID and getOldIDFromRequest together for PageView
		if ( is_null( $this->mOldId ) ) {
			$this->mOldId = $this->getOldIDFromRequest();
		}

		return $this->mOldId;
	}

	/**
	 * Get the robot policy to be used for the current view
	 * @return Array the policy that should be set
	 */
	public function getRobotPolicy() {
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
		$policy = self::formatRobotPolicy( $wgDefaultRobotPolicy );

		if ( isset( $wgNamespaceRobotPolicies[$ns] ) ) {
			# Honour customised robot policies for this namespace
			$policy = array_merge(
				$policy,
				self::formatRobotPolicy( $wgNamespaceRobotPolicies[$ns] )
			);
		}
		if ( $this->getTitle()->canUseNoindex() && is_object( $this->mParserOutput ) && $this->mParserOutput->getIndexPolicy() ) {
			# __INDEX__ and __NOINDEX__ magic words, if allowed. Incorporates
			# a final sanity check that we have really got the parser output.
			$policy = array_merge(
				$policy,
				array( 'index' => $this->mParserOutput->getIndexPolicy() )
			);
		}

		if ( isset( $wgArticleRobotPolicies[$this->getTitle()->getPrefixedText()] ) ) {
			# (bug 14900) site config can override user-defined __INDEX__ or __NOINDEX__
			$policy = array_merge(
				$policy,
				self::formatRobotPolicy( $wgArticleRobotPolicies[$this->getTitle()->getPrefixedText()] )
			);
		}

		return $policy;
	}

	/**
	 * Converts a String robot policy into an associative array, to allow
	 * merging of several policies using array_merge().
	 * @param $policy Mixed, returns empty array on null/false/'', transparent
	 *            to already-converted arrays, converts String.
	 * @return Array: 'index' => <indexpolicy>, 'follow' => <followpolicy>
	 */
	public static function formatRobotPolicy( $policy ) {
		if ( is_array( $policy ) ) {
			return $policy;
		} elseif ( !$policy ) {
			return array();
		}

		$policy = explode( ',', $policy );
		$policy = array_map( 'trim', $policy );

		$arr = array();
		foreach ( $policy as $var ) {
			if ( in_array( $var, array( 'index', 'noindex' ) ) ) {
				$arr['index'] = $var;
			} elseif ( in_array( $var, array( 'follow', 'nofollow' ) ) ) {
				$arr['follow'] = $var;
			}
		}

		return $arr;
	}

	/**
	 * Sets $this->mRedirectUrl to a correct URL if the query parameters are incorrect
	 *
	 * @return int The old id for the request
	 */
	public function getOldIDFromRequest() {
		$this->mRedirectUrl = false;

		$request = $this->getRequest();

		$oldid = $request->getVal( 'oldid' );

		if ( isset( $oldid ) ) {
			$oldid = intval( $oldid );
			if ( $rRequest->getVal( 'direction' ) == 'next' ) {
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

		return $oldid;
	}

	/** PageView render and sub-areas of the rendering process **/

	function render() {
		global $wgParser;
		global $wgUseFileCache, $wgUseETag;

		wfProfileIn( __METHOD__ );

		$request = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		# Get variables from query string
		$oldid = $this->getOldID();

		# getOldID may want us to redirect somewhere else
		if ( $this->mRedirectUrl ) {
			$out->redirect( $this->mRedirectUrl );
			wfDebug( __METHOD__ . ": redirecting due to oldid\n" );
			wfProfileOut( __METHOD__ );

			return;
		}

		# Set page title (may be overridden by DISPLAYTITLE)
		$out->setPageTitle( $this->getTitle()->getPrefixedText() );

		# If we got diff in the query, we want to see a diff page instead of the article.
		if ( $request->getCheck( 'diff' ) ) {
			wfDebug( __METHOD__ . ": showing diff page\n" );
			$this->showDiffPage();
			wfProfileOut( __METHOD__ );

			return;
		}

		$out->setArticleFlag( true );
		# Allow frames by default
		$out->allowClickjacking();

		$parserCache = ParserCache::singleton();

		$parserOptions = $this->getPage()->getParserOptions();
		# Render printable version, use printable version cache
		// @fixme printable check should be moved from Wiki.php to PageView
		if ( $out->isPrintable() ) {
			$parserOptions->setIsPrintable( true );
			$parserOptions->setEditSection( false );
		} elseif ( $wgUseETag && !$this->getTitle()->quickUserCan( 'edit' ) ) {
			$parserOptions->setEditSection( false );
		}

		# Try client and file cache
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
		$useParserCache = $this->getPage()->isParserCacheUsed( $user, $oldid );
		wfDebug( __METHOD__ . ' using parser cache: ' . ( $useParserCache ? 'yes' : 'no' ) . "\n" );
		if ( $user->getStubThreshold() ) {
			wfIncrStats( 'pcache_miss_stub' );
		}

		$wasRedirected = $this->showRedirectedFromHeader();
		$this->showNamespaceHeader();

		# Iterate through the possible ways of constructing the output text.
		# Keep going until $outputDone is set, or we run out of things to do.
		$pass = 0;
		$outputDone = false;
		$this->mParserOutput = false;

		while ( !$outputDone && ++$pass ) {
			switch( $pass ) {
				case 1:
					wfRunHooks( 'ArticleViewHeader', array( &$this, &$outputDone, &$useParserCache ) ); // @fixme
					break;
				case 2:
					# Try the parser cache
					if ( $useParserCache ) {
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
					break;
				case 3:
					$text = $this->getContent();
					if ( $text === false || $this->getPage()->getID() == 0 ) {
						wfDebug( __METHOD__ . ": showing missing article\n" );
						$this->showMissingArticle();
						wfProfileOut( __METHOD__ );
						return;
					}

					# Another whitelist check in case oldid is altering the title
					if ( !$this->getTitle()->userCanRead() ) {
						wfDebug( __METHOD__ . ": denied on secondary read check\n" );
						$out->loginToUse();
						$out->output();
						$out->disable();
						wfProfileOut( __METHOD__ );
						return;
					}

					# Are we looking at an old revision
					if ( $oldid && !is_null( $this->mRevision ) ) {
						$this->setOldSubtitle( $oldid );

						if ( !$this->showDeletedRevisionHeader() ) {
							wfDebug( __METHOD__ . ": cannot view deleted revision\n" );
							wfProfileOut( __METHOD__ );
							return;
						}

						# If this "old" version is the current, then try the parser cache...
						if ( $oldid === $this->getPage()->getLatest() && $this->getPage()->isParserCacheUsed( $user, false ) ) {
							$this->mParserOutput = $parserCache->get( $this->getPage(), $parserOptions );
							if ( $this->mParserOutput ) {
								wfDebug( __METHOD__ . ": showing parser cache for current rev permalink\n" );
								$out->addParserOutput( $this->mParserOutput );
								$out->setRevisionId( $this->getPage()->getLatest() );
								$outputDone = true;
								break;
							}
						}
					}

					# Ensure that UI elements requiring revision ID have
					# the correct version information.
					$out->setRevisionId( $this->getRevIdFetched() );

					# Pages containing custom CSS or JavaScript get special treatment
					if ( $this->getTitle()->isCssOrJsPage() || $this->getTitle()->isCssJsSubpage() ) {
						wfDebug( __METHOD__ . ": showing CSS/JS source\n" );
						$this->showCssOrJsPage();
						$outputDone = true;
					} elseif( !wfRunHooks( 'ArticleViewCustom', array( $this->mContent, $this->getTitle(), $out ) ) ) { // @fixme
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
					break;
				case 4:
					# Run the parse, protected by a pool counter
					wfDebug( __METHOD__ . ": doing uncached parse\n" );

					$key = $parserCache->getKey( $this->getPage(), $parserOptions );
					$poolArticleView = new PoolWorkArticleView( $this, $key, $useParserCache, $parserOptions ); // @fixme

					if ( !$poolArticleView->execute() ) {
						# Connection or timeout error
						wfProfileOut( __METHOD__ );
						return;
					} else {
						$outputDone = true;
					}
					break;
				# Should be unreachable, but just in case...
				default:
					break 2;
			}
		}

		# Adjust the title if it was set by displaytitle, -{T|}- or language conversion
		if ( $this->mParserOutput ) {
			$titleText = $this->mParserOutput->getTitleText();

			if ( strval( $titleText ) !== '' ) {
				$out->setPageTitle( $titleText );
			}
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

		# Now that we've filled $this->mParserOutput, we know whether
		# there are any __NOINDEX__ tags on the page
		$policy = $this->getRobotPolicy();
		$out->setIndexPolicy( $policy['index'] );
		$out->setFollowPolicy( $policy['follow'] );

		$this->showViewFooter();
		$this->getPage()->viewUpdates();
		wfProfileOut( __METHOD__ );
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

		if ( !$this->mRevision->isDeleted( Revision::DELETED_TEXT ) ) {
			// Not deleted
			return true;
		}

		// If the user is not allowed to see it...
		if ( !$this->mRevision->userCan( Revision::DELETED_TEXT ) ) {
			$out->wrapWikiMsg( "<div class='mw-warning plainlinks'>\n$1\n</div>\n",
				'rev-deleted-text-permission' );

			return false;
		// If the user needs to confirm that they want to see it...
		} elseif ( $this->getRequest()->getInt( 'unhide' ) != 1 ) {
			# Give explanation and add a link to view the revision...
			$oldid = intval( $this->getOldID() );
			$link = $this->getTitle()->getFullUrl( "oldid={$oldid}&unhide=1" );
			$msg = $this->mRevision->isDeleted( Revision::DELETED_RESTRICTED ) ?
				'rev-suppressed-text-unhide' : 'rev-deleted-text-unhide';
			$out->wrapWikiMsg( "<div class='mw-warning plainlinks'>\n$1\n</div>\n",
				array( $msg, $link ) );

			return false;
		// We are allowed to see...
		} else {
			$msg = $this->mRevision->isDeleted( Revision::DELETED_RESTRICTED ) ?
				'rev-suppressed-text-view' : 'rev-deleted-text-view';
			$out->wrapWikiMsg( "<div class='mw-warning plainlinks'>\n$1\n</div>\n", $msg );

			return true;
		}
	}

}

