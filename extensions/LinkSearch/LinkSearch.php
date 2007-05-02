<?php

/**
 * Quickie special page to search the external-links table.
 * Currently only 'http' links are supported; LinkFilter needs to be
 * changed to allow other pretties.
 */

$wgExtensionFunctions[] = 'wfLinkSearchSetup';
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Linksearch',
	'author' => 'Brion Vibber',
	'description' => 'Search for Weblinks',
	'url' => 'http://www.mediawiki.org/wiki/Extension:LinkSearch',
);

# Internationalisation file
require_once( 'LinkSearch.i18n.php' );

function wfLinkSearchSetup() {
	# Add messages
	global $wgMessageCache, $wgLinkSearchMessages;
	foreach( $wgLinkSearchMessages as $lang => $messages ) {
		$wgMessageCache->addMessages( $messages, $lang );
	}

	$GLOBALS['wgSpecialPages']['Linksearch'] = array( /*class*/ 'SpecialPage', 
		/*name*/ 'Linksearch', /* permission */'', /*listed*/ true, 
		/*function*/ false, /*file*/ false );

	class LinkSearchPage extends QueryPage {
		function __construct( $query , $ns , $prot ) {
			$this->mQuery = $query;
			$this->mNs = $ns;
			$this->mProt = $prot;
		}

		function getName() {
			return 'Linksearch';
		}

		/**
		 * Disable RSS/Atom feeds
		 */
		function isSyndicated() {
			return false;
		}

		/**
		 * Return an appropriately formatted LIKE query
		 */
		static function mungeQuery( $query , $prot ) {
			return LinkFilter::makeLike( $query , $prot );
		}

		function linkParameters() {
			return array( 'target' => $this->mQuery, 'namespace' => $this->mNs );
		}

		function getSQL() {
			global $wgMiserMode;
			$dbr = wfGetDB( DB_SLAVE );
			$page = $dbr->tableName( 'page' );
			$externallinks = $dbr->tableName( 'externallinks' );

			/* strip everything past first wildcard, so that index-based-only lookup would be done */
			$munged = self::mungeQuery( $this->mQuery, $this->mProt );
			$stripped = substr($munged,0,strpos($munged,'%')+1);
			$encSearch = $dbr->addQuotes( $stripped );

			$encSQL = '';
			if ( isset ($this->mNs) && !$wgMiserMode ) $encSQL = 'AND page_namespace=' . $this->mNs;


			return
				"SELECT
					page_namespace AS namespace,
					page_title AS title,
					el_index AS value,
					el_to AS url
				FROM
					$page,
					$externallinks FORCE INDEX (el_index)
				WHERE
					page_id=el_from
					AND el_index LIKE $encSearch
					$encSQL";
		}

		function formatResult( $skin, $result ) {
			$title = Title::makeTitle( $result->namespace, $result->title );
			$url = $result->url;
			$pageLink = $skin->makeKnownLinkObj( $title );
			$urlLink = $skin->makeExternalLink( $url, $url );

			return wfMsgHtml( 'linksearch-line', $urlLink, $pageLink );
		}

		/**
		 * Override to check query validity.
		 */
		function doQuery( $offset, $limit, $shownavigation=true ) {
			global $wgOut;
			$this->mMungedQuery = LinkSearchPage::mungeQuery( $this->mQuery, $this->mProt );
			if( $this->mMungedQuery === false ) {
				$wgOut->addWikiText( wfMsg( 'linksearch-error' ) );
			} else {
				// For debugging
				$wgOut->addHtml( "\n<!-- " . htmlspecialchars( $this->mMungedQuery ) . " -->\n" );
				parent::doQuery( $offset, $limit, $shownavigation );
			}
		}

		/**
		 * Override to squash the ORDER BY.
		 * We do a truncated index search, so the optimizer won't trust
		 * it as good enough for optimizing sort. The implicit ordering
		 * from the scan will usually do well enough for our needs.
		 */
		function getOrder() {
			return '';
		}
	}

	function wfSpecialLinksearch( $par=null, $ns=null ) {
		list( $limit, $offset ) = wfCheckLimits();
		global $wgOut, $wgRequest, $wgUrlProtocols, $wgMiserMode;
		$target = $GLOBALS['wgRequest']->getVal( 'target', $par );
		$namespace = $GLOBALS['wgRequest']->getIntorNull( 'namespace', $ns );

		$protocols_list[] = '';
		foreach( $wgUrlProtocols as $prot ) {
			$protocols_list[] = $prot;
		}

		$target2 = $target;
		$protocol = '';
		$pr_sl = strpos($target2, '//' );
		$pr_cl = strpos($target2, ':' );
		if ( $pr_sl ) {
			// For protocols with '//'
			$protocol = substr( $target2, 0 , $pr_sl+2 );
			$target2 = substr( $target2, $pr_sl+2 );
		} elseif ( !$pr_sl && $pr_cl ) {
			// For protocols without '//' like 'mailto:'
			$protocol = substr( $target2, 0 , $pr_cl+1 );
			$target2 = substr( $target2, $pr_cl+1 );
		} elseif ( $protocol == '' && $target2 != '' ) {
			// default
			$protocol = 'http://';
		}
		if ( !in_array( $protocol, $protocols_list ) ) {
			// unsupported protocol, show original search request
			$target2 = $target;
			$protocol = '';
		}

		$self = Title::makeTitle( NS_SPECIAL, 'Linksearch' );

		$wgOut->addWikiText( wfMsg( 'linksearch-text', '<nowiki>' . implode( ', ',  $wgUrlProtocols) . '</nowiki>' ) );
		$s =	Xml::openElement( 'form', array( 'id' => 'mw-linksearch-form', 'method' => 'get', 'action' => $GLOBALS['wgScript'] ) ) .
			Xml::hidden( 'title', $self->getPrefixedDbKey() ) .
			'<fieldset>' .
			Xml::element( 'legend', array(), wfMsg( 'linksearch' ) ) .
			Xml::label( wfMsg( 'linksearch-pat' ), 'target' ) . ' ' .
			Xml::input( 'target', 50 , $target ) . ' ';
		if ( !$wgMiserMode ) {
			$s .= Xml::label( wfMsg( 'linksearch-ns' ), 'namespace' ) .
				XML::namespaceSelector( $namespace, '' );
		}
		$s .=	Xml::submitButton( wfMsg( 'linksearch-ok' ) ) .
			'</fieldset>' .
			Xml::closeElement( 'form' );
		$wgOut->addHtml( $s );

		if( $target != '' ) {
			$searcher = new LinkSearchPage( $target2, $namespace, $protocol );
			$searcher->doQuery( $offset, $limit );
		}
	}
}

?>
