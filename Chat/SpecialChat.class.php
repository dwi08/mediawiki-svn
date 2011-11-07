<?php

class SpecialChat extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'Chat', 'chat' );
	}

	public function execute( $par ) {
		wfProfileIn( __METHOD__ );
		global $wgUser, $wgOut;

		// @FixMe Hrmm, messages work in the special page simply by adding them in the _setup.php. Maybe this isn't necessary.
		//wfLoadExtensionMessages( 'Chat' );
		
		// check if logged in
		if($wgUser->isLoggedIn()){
			if( Chat::canChat($wgUser) ){
				if( isset( $wgIsWikiaEnv ) ){
					Wikia::setVar( 'OasisEntryModuleName', 'Chat' );
					Chat::logChatWindowOpenedEvent();
				} else {
					$chat = new ChatModule();
					$chat->executeIndex();
					$objvars = get_object_vars($chat);
					extract($objvars);
					global $wgFavicon, $wgBlankImgUrl, $wgScriptPath, 
					$wgExtensionsPath, $wgStyleVersion, $wgStylePath;
					require dirname(__FILE__).'/templates/Chat_Index.php';
					$wgOut->disable();
				}
			} else {
				$wgOut->showErrorPage( 'chat-you-are-banned', 'chat-you-are-banned-text' );
			}
		} else {
			// TODO: FIXME: Make a link on this page which lets the user login.
			// TODO: FIXME: Make a link on this page which lets the user login.
			
			// $wgOut->permissionRequired( 'chat' ); // this is a really useless message, don't use it.
			$wgOut->showErrorPage( 'chat-no-login', 'chat-no-login-text' );

		}

		wfProfileOut( __METHOD__ );
	}
}
