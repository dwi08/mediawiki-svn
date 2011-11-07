<?php

class CandyChatHooks {
	/**
	 * Adds Chat drawer js to the output if appropriate.
	 */
	public static function onPageDisplay( &$output, &$skin ) {
		if ( self::shouldShowChatDrawer( $output, $skin ) ) {
			//$output->addModules( array( 'ext.candyChat.init', 'ext.candyChat.core' ) );
			$output->addModules( array( 'ext.candyChat.init' ) );
		}

		return true;
	}
	
	public static function onSkinTemplateNavigation (&$sktemplate, &$links) {

/*		$headerLinks = array();
		foreach( $links['views'] as $link => $params ) {
			$headerLinks[$link] = $params;
			if( $link == '' )
		}
*/
		wfDebug( print_r($links['views'], true) );
		wfDebug('***********************************************************');
		
		
		
		$links['views']['foo'] = array(
			'class' => false,
			'text' => wfMessageFallback( "foo", 'foo' )->text(),
			'href' => '#', //$title->getLocalURL( 'action=history' ),
			'rel' => 'archives',
		);
		
		
		
		return true;
	}

	/**
	 * Determines whether or not the chat drawer should be displayed.
	 */
	public static function shouldShowChatDrawer( &$output, &$skin ) {
		return true;
	}

}