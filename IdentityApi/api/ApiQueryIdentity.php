<?php

/**
 * API module to allow a non-mediawiki service to know that a certain user is who s/he claims to be on this wiki.
 * See the README for what this extension is and isn't.
 *
 * @file ApiQueryIdentity.php
 *
 * @licence GNU GPL v2 or later
 * @author Neil Kandalgaonkar <neilk@wikimedia.org>
 */
class ApiQueryIdentity extends ApiQueryBase {
	public function __construct( $main, $action ) {
		parent::__construct( $main, $action, 'id' );
	}

	/**
	 * Obtain data we can pass for the purposes of verifying a user later
	 */
	public function execute() {
		$auth = IdentityApi::getAuthParams( $user );

		if ( $auth === null ) {
			$this->dieUsage( 'Could not obtain auth credentials - are you logged in?', 'mustbeloggedin' );
		}

		// a wiki is identified by the root URL of the API -- everything else about a wiki is 
		// dependent on Apache configuration
		foreach ( $auth as $key => $val ) {
			$this->getResult()->addValue( 'authentication', $key, $val );
		}
		
	}

	/**
	 * ???? do we need this? XSRF? TODO
	 * Indicates whether this module must be called with a POST request
	 * @return bool
	 */
	public function mustBePosted() {
		return false;
	}

	/**
	 * Probably needed to avoid XSRF -- TODO 
	 * Returns whether this module requires a Token to execute
	 * @return bool
	 */
	public function needsToken() {
		return false;
	}

	public function getAllowedParams() {
		return array();
	}

	public function getParamDescription() {
		return array();
	}

	public function getDescription() {
		return 'API module to allow a non-mediawiki service to know that a certain user is who s/he claims to be on this wiki';
	}
	
	public function getPossibleErrors() {
		return array();
	}	
	
	protected function getExamples() {
		return array (
			'api.php?action=identity'
		);
	}

	public function getVersion() {
		return __CLASS__ . ': $Id: $';
	}	
	
}
