<?php

/**
 * Static class for general functions of the IdentityApi extension.
 *
 * @file IdentityApi.classphp
 *
 * @licence GNU GPL v2 or later
 * @author Neil Kandalgaonkar <neilk@wikimedia.org>
 */

class IdentityApi {

	/**
	 * Return authentication parameters for currently logged in user, or null
	 * Side effect: adds other data to memcached, identified via token, so it can be checked by remote service
	 * @return {Null|Array}  
	 */
	public static function getAuthParams() {
		global $wgUser, $wgServer, $wgScriptPath, $wgMemc, $wgIdentityApiConfig;

		if ( !( $wgUser && $wgUser->isLoggedIn() ) ) {
			return null;
		}
			
		$data = array( 
			'user_id' => $wgUser->getId(), 
			'cookie' => $_COOKIE,
		);
		$token = $wgUser->generateToken();

		$wgMemc->set( wfMemcKey( $token ), $data, $wgIdentityApiConfig['timeoutSeconds'] );

		return array( 
			'apiurl' => $wgServer . $wgScriptPath . '/api.php',
			'username' => $wgUser->getName(),
			'token' => $token
		);
	}

	/**
	 * Verifies that this user is logged in -- using memcached
	 * @param User
	 * @param String token, which we created in getAuth()
	 * @param Array extra queries, array of strings that represent what other info to return
	 * @return Array key-val, including 'verified' => boolean, and any other properties that may have
	 *				been asked for in $extra
	 */
	public static function verifyAuthParams( $user, $token, $extras ) { 
		global $wgMemc;
		$ret = array( 'verified' => false );
		$data = $wgMemc->get( wfMemcKey( $token ) );
		if ( isset( $data ) and $data !== null and $data['user_id'] ) {
			$ret['verified'] = ( intval( $user->getId() ) === intval( $data['user_id'] ) );
		}
		if ( $ret['verified'] ) {
			$ret = array_merge( $ret, IdentityApi::getExtraInfo( $user, $data, $extras ) );
		}
		return $ret;
	}

	/**
	 * Gets additional information, as specified in $extras
	 * @param User
	 * @param Array (data as was stored)
	 * @param Array (array of strings, options for what data to return);
	 */
	public static function getExtraInfo( $user, $data, $extras ) {
		global $wgServer, $wgArticlePath;
		$ret = array();
		foreach ( $extras as $extra ) { 
			$extraInfo = array();
			if ( preg_match( '/^avatarSrc=(\\d+)$/', $extra, $matches ) ) {
				if ( class_exists( 'AvatarService' ) ) {
					$avatarSize = intval( $matches[1] );	
					$ret['avatarSrc'] = AvatarService::getAvatarUrl( $user->getName(), $avatarSize );
				}
			}
			switch ( $extra ) {
				case 'chat':
					if ( class_exists( 'Chat' ) ) {
						$canChat = Chat::canChat( $user );
						if ( $canChat ) {
							$ret['canChat'] = $canChat;
							$ret['isChatMod'] = $user->isAllowed( 'chatmoderator' );
							$userChangeableGroups = $user->changeableGroups(); // php makes us use a temp variable here, sigh
							$ret['isCanGiveChatMode'] = in_array( 'chatmoderator', $userChangeableGroups['add'] ); 
							// TODO if still relevant, check for cross-wiki chat hack (see https://svn.wikia-code.com/wikia/trunk/extensions/wikia/Chat/ChatAjax.class.php )
							// TODO get user stats for chat (see https://svn.wikia-code.com/wikia/trunk/extensions/wikia/Chat/ChatAjax.class.php )
						}
					}
					break;
				case 'cookie':
					$ret['cookie'] = $data['cookie'];
					break;
				case 'isLoggedIn':
					$ret['isLoggedIn'] = $user->isLoggedIn();
					break;
				case 'isStaff':
					// Wikia-specific
					$ret['isStaff'] = $user->isAllowed( 'staff' );
					break;
				case 'username':
					$ret['username'] = $user->getName();
					break;
				case 'wgServer':
					$ret['wgServer'] = $wgServer;
					break;
				case 'wgArticlePath':
					$ret['wgArticlePath'] = $wgArticlePath;
					break;
				default:
					break;
			}	
		}
		return $ret;
	}

}
