<?php

/**
 * API module to allow a non-mediawiki service to know that a certain user is who s/he claims to be on this wiki.
 * See the README for what this extension is and isn't.
 *
 * @file ApiQueryVerifyIdentity.php
 *
 * @licence GNU GPL v2 or later
 * @author Neil Kandalgaonkar <neilk@wikimedia.org>
 */
class ApiQueryVerifyIdentity extends ApiQueryBase {
	public function __construct( $main, $action ) {
		parent::__construct( $main, $action, 'vi' );
	}

	/**
	 * Verify that a user has a certain active login session.
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$result = array( 'verified' => false );
	
		// check if this user is a user who can login
		if ( User::isUsableName( $params['user'] ) ) {
			// and even exists
			$userId = User::idFromName( $params['user'] );
			if ( $userId !== null ) {
				$user = User::newFromId( $userId );
				$token = $params['token'];
				$extras = preg_split( '/\|/', $params['extras'] );
				$result = IdentityApi::verifyAuthParams( $user, $token, $extras );
			}
		}

		foreach ( $result as $key => $val ) { 
			$this->getResult()->addValue( 'authentication', $key, $val );
		}
	}
	
	public function getAllowedParams() {
		return array (
			'user' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'token' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'extras' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false,
				ApiBase::PARAM_DFLT => '',
			)
		);
	}

	public function getParamDescription() {
		return array (
			'token' => 'The token that the user offers as proof of their identity; obtained through an identity API query',
			'user' => 'The username the user claims to have on this wiki',
			'extras' => 'If verified, extra information to return about the user or the wiki, delimited by pipe (|) characters.'
				. ' Possible values: avatarSrc, chat, cookie, isLoggedIn, isStaff, username, wgServer, wgArticlePath',
		);
	}

	public function getDescription() {
		return 'API module to allow a non-mediawiki service to know that a certain user is who s/he claims to be on this wiki';
	}
	
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'missingparam', 'token' ), 
			array( 'missingparam', 'user' )
		) );
	}	
	
	protected function getExamples() {
		return array (
			'api.php?action=verifyidentity&viuser=NeilK&vitoken=abcdef123456&viextras=avatarSrc|chat|cookie|isStaff|username',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': $Id: $';
	}	
	
}
