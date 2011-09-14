<?php

abstract class PageView extends ContextSource {

	public static function factory( RequestContext $context ) {
		
		$pageview = null;
		wfRunHooks( 'PageViewFromContext', array( $context, &$pageview ) );
		if ( !$pageview ) {
			switch ( $context->getTitle()->getNamespace() ) {
				case NS_SPECIAL:
					$pageview = SpecialPage::newFromContext( $context );
					break;
				case NS_FILE:
					$pageview = new FilePageView( $context );
					break;
				case NS_CATEGORY:
					$pageview = new CategoryPageView( $context );
					break;
				case NS_MEDIAWIKI:
					$pageview = new MessagePageView( $context );
					break;
				default:
					$pageview = new ArticlePageView( $context );
					break;
			}
		}
		$pageview->setContext( $context );
		
		return $pageview;
	}


	/** The rel=canonical url if any */
	protected $canonicalURL = null;

	protected function setCanonicalURL( $url ) {
		$this->canonicalURL = $url;
	}


	abstract function getTabs();

	abstract function render();

}
