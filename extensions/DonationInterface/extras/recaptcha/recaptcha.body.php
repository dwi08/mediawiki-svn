<?php

/**
 * Validates a transaction against MaxMind's minFraud service
 */
class Gateway_Extras_reCaptcha extends Gateway_Extras {

	/**
	 * Container for singelton instance of self
	 */
	static $instance;

	/**
	 * Container for the captcha error
	 * @var string
	 */
	public $recap_err;

	public function __construct( &$gateway_adapter ) {
		parent::__construct( $gateway_adapter );

		//stash all the vars that reCaptcha is going to need in a global just for it. 
		//I know this is vaguely unpleasant, but it's the quickest way back to zero. 
		global $wgReCaptchaConfData;
		$wgReCaptchaConfData['UseHTTPProxy'] = $this->getGlobal( 'RecaptchaUseHTTPProxy' );
		$wgReCaptchaConfData['HTTPProxy'] = $this->getGlobal( 'RecaptchaHTTPProxy' );
		$wgReCaptchaConfData['Timeout'] = $this->getGlobal( 'RecaptchaTimeout' );
		$wgReCaptchaConfData['UseSSL'] = $this->getGlobal( 'RecaptchaUseSSL' );
		$wgReCaptchaConfData['ComsRetryLimit'] = $this->getGlobal( 'RecaptchaComsRetryLimit' );
		$wgReCaptchaConfData['GatewayClass'] = $this->gateway_adapter->getGatewayAdapterClass(); //for properly routing the logging
		// load the reCaptcha API
		require_once( dirname( __FILE__ ) . '/recaptcha-php/recaptchalib.php' );
	}

	/**
	 * Handle the challenge logic
	 */
	public function challenge() {
		// if captcha posted, validate
		if ( isset( $_POST['recaptcha_response_field'] ) ) {
			// check the captcha response
			$captcha_resp = $this->check_captcha();
			if ( $captcha_resp->is_valid ) {
				// if validated, update the action and move on
				$this->log( $this->gateway_adapter->getData( 'contribution_tracking_id' ), 'Captcha passed' );
				$this->gateway_adapter->action = "process";
				return TRUE;
			} else {
				$this->recap_err = $captcha_resp->error;
				$this->log( $this->gateway_adapter->getData( 'contribution_tracking_id' ), 'Captcha failed' );
			}
		}
		// display captcha
		$this->display_captcha();
		return TRUE;
	}

	/**
	 * Display the submission form with the captcha injected into it
	 */
	public function display_captcha() {
		global $wgOut;
		$publicKey = $this->getGlobal( 'RecaptchaPublicKey' );
		$useSSL = $this->getGlobal( 'RecaptchaUseSSL' );

		// log that a captcha's been triggered
		$this->log( $this->gateway_adapter->getData( 'contribution_tracking_id' ), 'Captcha triggered' );

		// construct the HTML used to display the captcha
		$captcha_html = Xml::openElement( 'div', array( 'id' => 'mw-donate-captcha' ) );
		$captcha_html .= recaptcha_get_html( $publicKey, $this->recap_err, $useSSL );
		$captcha_html .= '<span class="creditcard-error-msg">' . wfMsg( $this->gateway_adapter->getIdentifier() . '_gateway-error-msg-captcha-please' ) . '</span>';
		$captcha_html .= Xml::closeElement( 'div' ); // close div#mw-donate-captcha
		// load up the form class
		$form_class = $this->gateway_adapter->getFormClass();

		//hmm. Looking at this now, makes me want to say 
		//TODO: Refactor the Form Class constructors. Again. Because the next three lines of code anger me deeply.
		//#1 - all three things are clearly in the gateway adapter, and we're passing that already. 
		//#2 - I have to stuff them in variables because Form wants parameters by reference. 
		$data = $this->gateway_adapter->getData();
		$erros = $this->gateway_adapter->getValidationErrors();
		$form_obj = new $form_class( $data, $errors, $this->gateway_adapter );

		// set the captcha HTML to use in the form
		$form_obj->setCaptchaHTML( $captcha_html );

		// output the form
		$wgOut->addHTML( $form_obj->getForm() );
	}

	/**
	 * Check recaptcha answer
	 */
	public function check_captcha() {
		global $wgRequest;
		$privateKey = $this->getGlobal( 'RecaptchaPrivateKey' );
		$resp = recaptcha_check_answer( $privateKey, wfGetIP(), $wgRequest->getText( 'recaptcha_challenge_field' ), $wgRequest->getText( 'recaptcha_response_field' ) );

		return $resp;
	}

	static function onChallenge( &$gateway_adapter ) {
		$gateway_adapter->debugarray[] = 'recaptcha onChallenge hook!';
		return self::singleton( $gateway_adapter )->challenge();
	}

	static function singleton( &$gateway_adapter ) {
		if ( !self::$instance ) {
			self::$instance = new self( $gateway_adapter );
		}
		return self::$instance;
	}

}
