<?php
class ChatModule {

	var $wgStylePath;
	var $wgStyleVersion;
	var $wgExtensionsPath;
	var $wgBlankImgUrl;
	var $globalVariablesScript;
	var $username;
	var $roomId;
	var $roomName;
	var $roomTopic;
	var $userList;
	var $messages;
	var $isChatMod;
	var $bodyClasses = '';
	var $themeSettings;
	var $avatarUrl;
	var $nodeHostname;
	var $nodePort;
	var $pathToProfilePage;
	var $pathToContribsPage;
	var $mainPageURL;
	var $wgFavicon = '';
	var $jsMessagePackagesUrl = '';
	var $app;

	public function executeIndex() {
		global $wgUser, $wgRequest, $wgCityId, $wgFavicon;
		wfProfileIn( __METHOD__ );

		// String replacement logic taken from includes/Skin.php
		$this->wgFavicon = str_replace('images.wikia.com', 'images1.wikia.nocookie.net', $wgFavicon);

		// add messages (fetch them using <script> tag)

		// Variables for this user
		$this->username = $wgUser->getName();

		// Find the chat for this wiki (or create it, if it isn't there yet).
		$this->roomName = $this->roomTopic = "";
		$this->roomId = NodeApiClient::getDefaultRoomId($this->roomName, $this->roomTopic);
		$this->roomId = (int) $this->roomId;
		
		// Set the hostname of the node server that the page will connect to.
		$this->nodePort = NodeApiClient::PORT;
		$this->nodeHostname = NodeApiClient::HOST_PRODUCTION_FROM_CLIENT;

		// Some building block for URLs that the UI needs.
		$this->pathToProfilePage = Title::makeTitle( NS_USER, '$1' )->getFullURL();
		$this->pathToContribsPage = SpecialPage::getTitleFor( 'Contributions', '$1' )->getFullURL();

		// Some i18n'ed strings used inside of templates by Backbone. The <%= stuffInHere % > is intentionally like
		// that & will end up in the string (substitution occurs later).
		$this->editCountStr = wfMsg('chat-edit-count', "<%= editCount %>");
		$this->memberSinceStr = "<%= since %>";

		if ($wgUser->isAllowed( 'chatmoderator' )) {
			$this->isChatMod = 1;
			$this->bodyClasses .= ' chat-mod ';
		} else {
			$this->isChatMod = 0;
		}

		// Adding chatmoderator group for other users. CSS classes added to body tag to hide/show option in menu.
		$userChangeableGroups = $wgUser->changeableGroups();
		if (in_array('chatmoderator', $userChangeableGroups['add'])) {
			$this->bodyClasses .= ' can-give-chat-mod ';
		}
	
		//Theme Designer stuff
		
		global $wgIsWikiaEnv;
		
		if($wgIsWikiaEnv) {
			$themeSettings = new ThemeSettings();
			$this->themeSettings = $themeSettings->getSettings();
			$this->app = WF::build('App');
			F::build('JSMessages')->enqueuePackage('Chat', JSMessages::INLINE); // package defined in Chat_setup.php
			$this->mainPageURL = Title::newMainPage()->getLocalURL();
			$this->avatarUrl = AvatarService::getAvatarUrl($this->username, 50);
			
			//$this->app->registerHook('MakeGlobalVariablesScript', 'ChatModule', 'onMakeGlobalVariablesScript', array(), false, $this);
			$this->globalVariablesScript = Skin::makeGlobalVariablesScript(Module::getSkinTemplateObj()->data);
		
			$this->jsMessagePackagesUrl = F::build('JSMessages')->getExternalPackagesUrl();
		} else {
			$vars = array();
			global $wgHooks;
			$wgHooks['MakeGlobalVariablesScript'][] = array($this, 'onMakeGlobalVariablesScript');
			
			$this->onMakeGlobalVariablesScript($vars);
			//$this->globalVariablesScript = Skin::makeGlobalVariablesScript($vars);
			global $wgOut;
			$this->globalVariablesScript = $this->makeGlobalVariablesHack( $vars );
			wfDebug( $this->globalVariablesScript );
			//$wgOut->addScript( Skin::makeVariablesScript( $vars ) );
			//$wgOut->addScript( $this->makeGlobalVariablesHack( $vars ) );
			
		}

		// Since we don't emit all of the JS headscripts or so, fetch the URL to load the JS Messages packages.

		wfProfileOut( __METHOD__ );
	}
	
	private function makeGlobalVariablesHack ( array $vars ) {
		$out = '<script type=text/javascript>';
		// FIXME: escape these
		foreach ( $vars as $key => $val ) {
			$out .= "$key = '" . $val. "';\n";
		}
		$out .= '</script>';
		return $out;
	}
	
	/*
	 * adding js variable
	 */
	
	function onMakeGlobalVariablesScript(array &$vars) {
		global $wgScript, $wgUser;

		$vars['wgUserName'] = $wgUser->getName();
		$vars['wgScript'] = $wgScript;
		
		$vars['roomId'] = $this->roomId;
		$vars['wgChatMod'] = $this->isChatMod;
		$vars['WIKIA_NODE_HOST'] = $this->nodeHostname;
		$vars['WIKIA_NODE_PORT'] = $this->nodePort;
		$vars['WEB_SOCKET_SWF_LOCATION'] = $this->wgExtensionsPath.'/wikia/Chat/swf/WebSocketMainInsecure.swf?'.$this->wgStyleVersion;
		
		$vars['pathToProfilePage'] = $this->pathToProfilePage;
		$vars['pathToContribsPage'] = $this->pathToContribsPage;
		$vars['wgAvatarUrl'] = $this->avatarUrl;

		return true;
	}
}
