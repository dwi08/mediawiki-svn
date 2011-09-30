<?php

class PayflowProGateway extends GatewayForm {

	/**
	 * Constructor - set up the new special page
	 */
	public function __construct() {
		$this->adapter = new PayflowProAdapter();
		parent::__construct(); //the next layer up will know who we are. 
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgRequest, $wgOut, $wgExtensionAssetsPath;
		$CSSVersion = $this->adapter->getGlobal( 'CSSVersion' );

		$wgOut->addExtensionStyle(
			$wgExtensionAssetsPath . '/DonationInterface/gateway_forms/css/gateway.css?284' .
			$CSSVersion );

		// Hide unneeded interface elements
		$wgOut->addModules( 'donationInterface.skinOverride' );

		$gateway_id = $this->adapter->getIdentifier();

		$this->addErrorMessageScript();

		// @fixme can this be moved into the form generators?
		$js = <<<EOT
<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery("div#p-logo a").attr("href","#");
});
</script>
EOT;
		$wgOut->addHeadItem( 'logolinkoverride', $js );

		$this->setHeaders();

		/**
		 *  handle PayPal redirection
		 *
		 *  if paypal redirection is enabled ($wgPayflowProGatewayPaypalURL must be defined)
		 *  and the PaypalRedirect form value must be true
		 */
		if ( $wgRequest->getText( 'PaypalRedirect', 0 ) ) {
			$this->paypalRedirect();
			return;
		}

		//TODO: This is short-circuiting what I really want to do here. 
		//so stop it. 
		$data = $this->adapter->getDisplayData();

		// dispatch forms/handling
		if ( $this->adapter->checkTokens() ) {
			if ( $this->adapter->posted && $data['payment_method'] == 'processed' ) {
				// The form was submitted and the payment method has been set
				$this->adapter->log( "Form posted and payment method set." );

				// Check form for errors
				$form_errors = $this->fnValidateForm( $data, $this->errors );

				// If there were errors, redisplay form, otherwise proceed to next step
				if ( $form_errors ) {
					$this->displayForm( $data, $this->errors );
				} else { // The submitted form data is valid, so process it
					$result = $this->adapter->do_transaction( 'Card' );

					// if the transaction was flagged for rejection
					if ( $this->adapter->action == 'reject' ) {
						$this->fnPayflowDisplayDeclinedResults( '' );
					}

					if ( $this->adapter->action == 'process' ) {
						$this->fnPayflowDisplayResults( $result );
					}
				}
			} else {
				// Display form for the first time
				$this->displayForm( $data, $this->errors );
			}
		} else {
			if ( !$this->adapter->isCache() ) {
				// if we're not caching, there's a token mismatch
				$this->errors['general']['token-mismatch'] = wfMsg( $gateway_id . '_gateway-token-mismatch' );
			}
			$this->displayForm( $data, $this->errors );
		}
	}

	/**
	 * "Reads" the name-value pair result string returned by Payflow and creates corresponding error messages
	 *
	 * @param $data Array: array of user input
	 * @param $result String: name-value pair results returned by Payflow
	 *
	 * Credit: code modified from payflowpro_example_EC.php posted (and supervised) on the PayPal developers message board
	 */
	private function fnPayflowDisplayResults( $result ) {
		if ( is_array( $result ) && array_key_exists( 'errors', $result ) && is_array( $result['errors'] ) ) {
			foreach ( $result['errors'] as $key => $value ) {
				$errorCode = $key;
				$responseMsg = $value;
				break; //we just want the top, and this is probably the fastest way.
			}
		}

		$oid = $this->adapter->getData( 'order_id' );
		$i_oid = $this->adapter->getData( 'i_order_id' );
		$data = $this->adapter->getData();

		// if approved, display results and send transaction to the queue
		if ( $errorCode == '1' ) {
			self::log( $oid . " " . $i_oid . " Transaction approved.", LOG_DEBUG );
			$this->fnPayflowDisplayApprovedResults( $data, $responseArray, $responseMsg );
			// give user a second chance to enter incorrect data
		} elseif ( ( $errorCode == '3' ) && ( $data['numAttempt'] < '5' ) ) {
			self::log( $oid . " " . $i_oid . " Transaction unsuccessful (invalid info).", LOG_DEBUG );
			// pass responseMsg as an array key as required by displayForm
			$this->errors['retryMsg'] = $responseMsg;
			$this->fnPayflowDisplayForm( $data, $this->errors );
			// if declined or if user has already made two attempts, decline
		} elseif ( ( $errorCode == '2' ) || ( $data['numAttempt'] >= '3' ) ) {
			self::log( $oid . " " . $i_oid . " Transaction declined.", LOG_DEBUG );
			$this->fnPayflowDisplayDeclinedResults( $responseMsg );
		} elseif ( ( $errorCode == '4' ) ) {
			self::log( $oid . " " . $i_oid . " Transaction unsuccessful.", LOG_DEBUG );
			$this->fnPayflowDisplayOtherResults( $responseMsg );
		} elseif ( ( $errorCode == '5' ) ) {
			self::log( $oid . " " . $i_oid . " Transaction pending.", LOG_DEBUG );
			$this->fnPayflowDisplayPending( $data, $responseArray, $responseMsg );
		}
	}

	/**
	 * Display response message to user with submitted user-supplied data
	 *
	 * @param $data Array: array of posted data from form
	 * @param $responseMsg String: message supplied by getResults function
	 */
	function fnPayflowDisplayApprovedResults( $data, $responseArray, $responseMsg ) {
		global $wgOut, $wgExternalThankYouPage;

		$transaction = $this->prepareStompTransaction( $data, $responseArray, $responseMsg );

		/**
		 * hook to call stomp functions
		 *
		 * Sends transaction to Stomp-based queueing service,
		 * eg ActiveMQ
		 */
		wfRunHooks( 'gwStomp', array( $transaction ) );

		if ( $wgExternalThankYouPage ) {
			$wgOut->redirect( $wgExternalThankYouPage . "/" . $data['language'] );
		} else {
			// display response message
			$wgOut->addHTML( '<h3 class="response_message">' . $responseMsg . '</h3>' );

			// translate country code into text
			$countries = GatewayForm::getCountries();

			$rows = array(
				'title' => array( wfMsg( 'payflowpro_gateway-post-transaction' ) ),
				'amount' => array( wfMsg( 'payflowpro_gateway-donor-amount' ), $data['amount'] ),
				'email' => array( wfMsg( 'payflowpro_gateway-donor-email' ), $data['email'] ),
				'name' => array( wfMsg( 'payflowpro_gateway-donor-name' ), $data['fname'], $data['mname'], $data['lname'] ),
				'address' => array( wfMsg( 'payflowpro_gateway-donor-address' ), $data['street'], $data['city'], $data['state'], $data['zip'], $countries[$data['country']] ),
			);

			// if we want to show the response
			$wgOut->addHTML( Xml::buildTable( $rows, array( 'class' => 'submitted-response' ) ) );
		}
	}

	/**
	 * Display response message to user with submitted user-supplied data
	 *
	 * @param $responseMsg String: message supplied by getResults function
	 */
	function fnPayflowDisplayDeclinedResults( $responseMsg ) {
		global $wgOut;

		// general decline message
		$declinedDefault = wfMsg( 'php-response-declined' );

		// display response message
		$wgOut->addHTML( '<h3 class="response_message">' . $declinedDefault . ' ' . $responseMsg . '</h3>' );
	}

	/**
	 * Display response message when there is a system error unrelated to user's entry
	 *
	 * @param $responseMsg String: message supplied by getResults function
	 */
	function fnPayflowDisplayOtherResults( $responseMsg ) {
		global $wgOut;

		// general decline message
		$declinedDefault = wfMsg( 'php-response-declined' );

		// display response message
		$wgOut->addHTML( '<h3 class="response_message">' . $declinedDefault . ' ' . $responseMsg . '</h3>' );
	}

	function fnPayflowDisplayPending( $data, $responseArray, $responseMsg ) {
		global $wgOut;

		$transaction = $this->prepareStompTransaction( $data, $responseArray, $responseMsg );

		// hook to call stomp functions
		wfRunHooks( 'gwPendingStomp', array( $transaction ) );

		$thankyou = wfMsg( 'payflowpro_gateway-thankyou' );

		// display response message
		$wgOut->addHTML( '<h2 class="response_message">' . $thankyou . '</h2>' );
		$wgOut->addHTML( '<p>' . $responseMsg );
	}

	//TODO: Remember why the heck I decided to leave this here...
	//arguably, it's because it's slightly more "view" related, but... still, shouldn't you get stashed 
	//in the new GatewayForm class so we can override in chlidren if we feel like it? Odd. 
	function addErrorMessageScript() {
		global $wgOut;
		$gateway_id = $this->adapter->getIdentifier();

		$scriptVars = array(
			$gateway_id . 'GatewayErrorMsgJs' => wfMsg( $gateway_id . '_gateway-error-msg-js' ),
			$gateway_id . 'GatewayErrorMsgEmail' => wfMsg( $gateway_id . '_gateway-error-msg-email' ),
			$gateway_id . 'GatewayErrorMsgAmount' => wfMsg( $gateway_id . '_gateway-error-msg-amount' ),
			$gateway_id . 'GatewayErrorMsgEmailAdd' => wfMsg( $gateway_id . '_gateway-error-msg-emailAdd' ),
			$gateway_id . 'GatewayErrorMsgFname' => wfMsg( $gateway_id . '_gateway-error-msg-fname' ),
			$gateway_id . 'GatewayErrorMsgLname' => wfMsg( $gateway_id . '_gateway-error-msg-lname' ),
			$gateway_id . 'GatewayErrorMsgStreet' => wfMsg( $gateway_id . '_gateway-error-msg-street' ),
			$gateway_id . 'GatewayErrorMsgCity' => wfMsg( $gateway_id . '_gateway-error-msg-city' ),
			$gateway_id . 'GatewayErrorMsgState' => wfMsg( $gateway_id . '_gateway-error-msg-state' ),
			$gateway_id . 'GatewayErrorMsgZip' => wfMsg( $gateway_id . '_gateway-error-msg-zip' ),
			$gateway_id . 'GatewayErrorMsgCountry' => wfMsg( $gateway_id . '_gateway-error-msg-country' ),
			$gateway_id . 'GatewayErrorMsgCardType' => wfMsg( $gateway_id . '_gateway-error-msg-card_type' ),
			$gateway_id . 'GatewayErrorMsgCardNum' => wfMsg( $gateway_id . '_gateway-error-msg-card_num' ),
			$gateway_id . 'GatewayErrorMsgExpiration' => wfMsg( $gateway_id . '_gateway-error-msg-expiration' ),
			$gateway_id . 'GatewayErrorMsgCvv' => wfMsg( $gateway_id . '_gateway-error-msg-cvv' ),
			$gateway_id . 'GatewayCVVExplain' => wfMsg( $gateway_id . '_gateway-cvv-explain' ),
		);

		$wgOut->addScript( Skin::makeVariablesScript( $scriptVars ) );
	}

}

// end class
