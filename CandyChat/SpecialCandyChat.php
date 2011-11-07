<?php
/**
 * Special:CandyChat
 *
 * Web-based jabber chat client
 *
 * @file
 * @ingroup SpecialPage
 */

class SpecialCandyChat extends SpecialPage {
	
	// $request is the request (usually wgRequest)
	public function __construct( $request = null, $par = null ) {
        parent::__construct( 'CandyChat' );
	}


	/**
	 * Replaces default execute method
	 * Checks whether uploading enabled, user permissions okay,
	 * @param $subPage, e.g. the "foo" in Special:UploadWizard/foo.
	 */
	public function execute( $roomName ) {
		global $wgRequest, $wgOut, $wgExtensionAssetsPath;
		//$wgOut->setArticleBodyOnly(true);
		$wgOut->disable();
		
		// get a request parameter
		//$param = $wgRequest->getText('param');

		$authParams = IdentityApi::getAuthParams();
		
		$room = $roomName . '@conference.localhost';
		$candyPath = "$wgExtensionAssetsPath/CandyChat/lib/candy";
		include( dirname( __FILE__ ) . '/templates/chat.php' );
		
		//wfDebug(print_r($wgOut->getHeadItems(), true));

//		$output="Hello world! " . $room;
//		$wgOut->addHTML( $output );

		/*
		global $wgRequest, $wgLang, $wgUser, $wgOut, $wgExtensionAssetsPath,
		       $wgUploadWizardDisableResourceLoader;

		// side effects: if we can't upload, will print error page to wgOut
		// and return false
		if ( !( $this->isUploadAllowed() && $this->isUserUploadAllowed( $wgUser ) ) ) {
			return;
		}

		$this->setHeaders();
		$this->outputHeader();
		
		$this->campaign = $wgRequest->getVal( 'campaign' );
		
		// if query string includes 'skiptutorial=true' set config variable to true
		if ( $wgRequest->getCheck( 'skiptutorial' ) ) {
			$skip = in_array( $wgRequest->getText( 'skiptutorial' ), array( '1', 'true' ) );
			UploadWizardConfig::setUrlSetting( 'skipTutorial', $skip );
		}

		// fallback for non-JS
		$wgOut->addHTML( '<noscript>' );
		$wgOut->addHTML( '<p class="errorbox">' . htmlspecialchars( wfMsg( 'mwe-upwiz-js-off' ) ) . '</p>' );
		$this->simpleForm->show();
		$wgOut->addHTML( '</noscript>' );


		// global javascript variables
		$this->addJsVars( $subPage );

		// dependencies (css, js)
		if ( !$wgUploadWizardDisableResourceLoader && class_exists( 'ResourceLoader' ) ) {
			$wgOut->addModules( 'ext.uploadWizard' );
		} else {
			$basepath = "$wgExtensionAssetsPath/UploadWizard";
			$dependencyLoader = new UploadWizardDependencyLoader( $wgLang->getCode() );
			if ( UploadWizardConfig::getSetting( 'debug', $this->campaign ) ) {
				// each file as an individual script or style
				$dependencyLoader->outputHtmlDebug( $wgOut, $basepath );
			} else {
				// combined & minified
				$dependencyLoader->outputHtml( $wgOut, $basepath );
			}
		}

		// where the uploadwizard will go
		// TODO import more from UploadWizard's createInterface call.
		$wgOut->addHTML( self::getWizardHtml() );
		*/
	}

	/**
	 * Adds some global variables for our use, as well as initializes the UploadWizard
	 * 
	 * TODO once bug https://bugzilla.wikimedia.org/show_bug.cgi?id=26901
	 * is fixed we should package configuration with the upload wizard instead of
	 * in uploadWizard output page. 
	 * 
	 * @param subpage, e.g. the "foo" in Special:UploadWizard/foo
	 */
	public function addJsVars( $subPage ) {
		
		/*
		global $wgOut, $wgSitename;
		
		$wgOut->addScript( 
			Skin::makeVariablesScript( 
				array(
					'UploadWizardConfig' => UploadWizardConfig::getConfig( $this->campaign ) 
				) +
				// Site name is a true global not specific to Upload Wizard
				array( 
					'wgSiteName' => $wgSitename
				)
			)
		);
		*/
	}
}
