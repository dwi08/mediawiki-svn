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

				// increase the count of attempts
				//++$data['numAttempt'];
				// Check form for errors
				$form_errors = $this->fnValidateForm( $data, $this->errors );

				// If there were errors, redisplay form, otherwise proceed to next step
				if ( $form_errors ) {
					$this->displayForm( $data, $this->errors );
				} else { // The submitted form data is valid, so process it
					// allow any external validators to have their way with the data
					self::log( $data['order_id'] . " Preparing to query MaxMind" );
					wfRunHooks( 'PayflowGatewayValidate', array( &$this, &$data ) );
					self::log( $data['order_id'] . ' Finished querying Maxmind' );

					// if the transaction was flagged for review
					if ( $this->action == 'review' ) {
						// expose a hook for external handling of trxns flagged for review
						wfRunHooks( 'PayflowGatewayReview', array( &$this, &$data ) );
					}

					// if the transaction was flagged to be 'challenged'
					if ( $this->action == 'challenge' ) {
						// expose a hook for external handling of trxns flagged for challenge (eg captcha)
						wfRunHooks( 'PayflowGatewayChallenge', array( &$this, &$data ) );
					}

					// if the transaction was flagged for rejection
					if ( $this->action == 'reject' ) {
						// expose a hook for external handling of trxns flagged for rejection
						wfRunHooks( 'PayflowGatewayReject', array( &$this, &$data ) );

						$this->fnPayflowDisplayDeclinedResults( '' );
						$this->fnPayflowUnsetEditToken();
					}

					// if the transaction was flagged for processing
					if ( $this->action == 'process' ) {
						// expose a hook for external handling of trxns ready for processing
						wfRunHooks( 'PayflowGatewayProcess', array( &$this, &$data ) );
						$this->fnPayflowProcessTransaction( $data, $payflow_data );
					}

					// expose a hook for any post processing
					wfRunHooks( 'PayflowGatewayPostProcess', array( &$this, &$data ) );
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
	 * Sends a name-value pair string to Payflow gateway
	 *
	 * @param $data Array: array of user input
	 * @param $payflow_data Array: array of necessary Payflow variables to
	 * 						include in string (i.e. Vendor, password)
	 */
	private function fnPayflowProcessTransaction( $data, $payflow_data ) {
		global $wgOut, $wgDonationTestingMode, $wgPayflowProGatewayUseHTTPProxy, $wgPayflowProGatewayHTTPProxy, $wgPayflowProTimeout;

		// update contribution tracking
		$this->updateContributionTracking( $data, defined( 'OWA' ) );

		// create payflow query string, include string lengths
		$queryArray = array(
			'TRXTYPE' => $payflow_data['trxtype'],
			'TENDER' => $payflow_data['tender'],
			'USER' => $payflow_data['user'],
			'VENDOR' => $payflow_data['vendor'],
			'PARTNER' => $payflow_data['partner'],
			'PWD' => $payflow_data['password'],
			'ACCT' => $data['card_num'],
			'EXPDATE' => $data['expiration'],
			'AMT' => $data['amount'],
			'FIRSTNAME' => $data['fname'],
			'LASTNAME' => $data['lname'],
			'STREET' => $data['street'],
			'CITY' => $data['city'],
			'STATE' => $data['state'],
			'COUNTRY' => $data['country'],
			'ZIP' => $data['zip'],
			'INVNUM' => $data['order_id'],
			'CVV2' => $data['cvv'],
			'CURRENCY' => $data['currency'],
			'VERBOSITY' => $payflow_data['verbosity'],
			'CUSTIP' => $payflow_data['user_ip'],
		);

		foreach ( $queryArray as $name => $value ) {
			$query[] = $name . '[' . strlen( $value ) . ']=' . $value;
		}

		$queryString = implode( '&', $query );

		$payflow_query = $queryString;

		// assign header data necessary for the curl_setopt() function
		$user_agent = Http::userAgent();
		$headers[] = 'Content-Type: text/namevalue';
		$headers[] = 'Content-Length : ' . strlen( $payflow_query );
		$headers[] = 'X-VPS-Client-Timeout: 45';
		$headers[] = 'X-VPS-Request-ID:' . $data['order_id'];
		$ch = curl_init();
		$paypalPostTo = isset( $wgDonationTestingMode ) ? 'testingurl' : 'paypalurl';
		curl_setopt( $ch, CURLOPT_URL, $payflow_data[$paypalPostTo] );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_USERAGENT, $user_agent );
		curl_setopt( $ch, CURLOPT_HEADER, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $wgPayflowProTimeout );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $payflow_query );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
		curl_setopt( $ch, CURLOPT_FORBID_REUSE, true );
		curl_setopt( $ch, CURLOPT_POST, 1 );

		// set proxy settings if necessary
		if ( $wgPayflowProGatewayUseHTTPProxy ) {
			curl_setopt( $ch, CURLOPT_HTTPPROXYTUNNEL, 1 );
			curl_setopt( $ch, CURLOPT_PROXY, $wgPayflowProGatewayHTTPProxy );
		}

		// As suggested in the PayPal developer forum sample code, try more than once to get a response
		// in case there is a general network issue
		$i = 1;

		while ( $i++ <= 3 ) {
			self::log( $data['order_id'] . ' Preparing to send transaction to PayflowPro' );
			$result = curl_exec( $ch );
			$headers = curl_getinfo( $ch );

			if ( $headers['http_code'] != 200 && $headers['http_code'] != 403 ) {
				self::log( $data['order_id'] . ' Failed sending transaction to PayflowPro, retrying' );
				sleep( 1 );
			} elseif ( $headers['http_code'] == 200 || $headers['http_code'] == 403 ) {
				self::log( $data['order_id'] . ' Finished sending transaction to PayflowPro' );
				break;
			}
		}

		if ( $headers['http_code'] != 200 ) {
			$wgOut->addHTML( '<h3>No response from credit card processor.  Please try again later!</h3><p>' );
			$when = time();
			self::log( $data['order_id'] . ' No response from credit card processor: ' . curl_error( $ch ) );
			curl_close( $ch );
			return;
		}

		curl_close( $ch );

		// get result string
		$result = strstr( $result, 'RESULT' );

		// parse string and display results to the user
		$this->fnPayflowGetResults( $data, $result );
	}

	/**
	 * "Reads" the name-value pair result string returned by Payflow and creates corresponding error messages
	 *
	 * @param $data Array: array of user input
	 * @param $result String: name-value pair results returned by Payflow
	 *
	 * Credit: code modified from payflowpro_example_EC.php posted (and supervised) on the PayPal developers message board
	 */
	private function fnPayflowGetResults( $data, $result ) {
		// prepare NVP response for sorting and outputting
		$responseArray = array( );

		/**
		 * The result response string looks like:
		 * 	RESULT=7&PNREF=E79P2C651DC2&RESPMSG=Field format error&HOSTCODE=10747&DUPLICATE=1
		 * We want to turn this into an array of key value pairs, so explode on '&' and then
		 * split up the resulting strings into $key => $value
		 */
		$result_arr = explode( "&", $result );
		foreach ( $result_arr as $result_pair ) {
			list( $key, $value ) = preg_split( "/=/", $result_pair );
			$responseArray[$key] = $value;
		}

		// store the response array as an object property for easy retrival/manipulation elsewhere
		$this->payflow_response = $responseArray;

		// errors fall into three categories, "try again please", "sorry it didn't work out", and "approved"
		// get the result code for response array
		$resultCode = $responseArray['RESULT'];

		// initialize response message
		$responseMsg = '';

		// interpret result code, return
		// approved (1), denied (2), try again (3), general error (4)
		$errorCode = $this->fnPayflowGetResponseMsg( $resultCode, $responseMsg );

		// log that the transaction is essentially complete
		self::log( $data['order_id'] . " Transaction complete." );

		// if approved, display results and send transaction to the queue
		if ( $errorCode == '1' ) {
			self::log( $data['order_id'] . " " . $data['i_order_id'] . " Transaction approved.", 'payflowpro_gateway', LOG_DEBUG );
			$this->fnPayflowDisplayApprovedResults( $data, $responseArray, $responseMsg );
			// give user a second chance to enter incorrect data
		} elseif ( ( $errorCode == '3' ) && ( $data['numAttempt'] < '5' ) ) {
			self::log( $data['order_id'] . " " . $data['i_order_id'] . " Transaction unsuccessful (invalid info).", 'payflowpro_gateway', LOG_DEBUG );
			// pass responseMsg as an array key as required by displayForm
			$this->errors['retryMsg'] = $responseMsg;
			$this->fnPayflowDisplayForm( $data, $this->errors );
			// if declined or if user has already made two attempts, decline
		} elseif ( ( $errorCode == '2' ) || ( $data['numAttempt'] >= '3' ) ) {
			self::log( $data['order_id'] . " " . $data['i_order_id'] . " Transaction declined.", 'payflowpro_gateway', LOG_DEBUG );
			$this->fnPayflowDisplayDeclinedResults( $responseMsg );
		} elseif ( ( $errorCode == '4' ) ) {
			self::log( $data['order_id'] . " " . $data['i_order_id'] . " Transaction unsuccessful.", 'payflowpro_gateway', LOG_DEBUG );
			$this->fnPayflowDisplayOtherResults( $responseMsg );
		} elseif ( ( $errorCode == '5' ) ) {
			self::log( $data['order_id'] . " " . $data['i_order_id'] . " Transaction pending.", 'payflowpro_gateway', LOG_DEBUG );
			$this->fnPayflowDisplayPending( $data, $responseArray, $responseMsg );
		}
	}

// end display results

	/**
	 * Interpret response code, return
	 * 1 if approved
	 * 2 if declined
	 * 3 if invalid data was submitted by user
	 * 4 all other errors
	 */
	function fnPayflowGetResponseMsg( $resultCode, &$responseMsg ) {
		$responseMsg = wfMsg( 'payflowpro_gateway-response-default' );

		switch ( $resultCode ) {
			case '0':
				$responseMsg = wfMsg( 'payflowpro_gateway-response-0' );
				$errorCode = '1';
				break;
			case '126':
				$responseMsg = wfMsg( 'payflowpro_gateway-response-126-2' );
				$errorCode = '5';
				break;
			case '12':
				$responseMsg = wfMsg( 'payflowpro_gateway-response-12' );
				$errorCode = '2';
				break;
			case '13':
				$responseMsg = wfMsg( 'payflowpro_gateway-response-13' );
				$errorCode = '2';
				break;
			case '114':
				$responseMsg = wfMsg( 'payflowpro_gateway-response-114' );
				$errorCode = '2';
				break;
			case '4':
				$responseMsg = wfMsg( 'payflowpro_gateway-response-4' );
				$errorCode = '3';
				break;
			case '23':
				$responseMsg = wfMsg( 'payflowpro_gateway-response-23' );
				$errorCode = '3';
				break;
			case '24':
				$responseMsg = wfMsg( 'payflowpro_gateway-response-24' );
				$errorCode = '3';
				break;
			case '112':
				$responseMsg = wfMsg( 'payflowpro_gateway-response-112' );
				$errorCode = '3';
				break;
			case '125':
				$responseMsg = wfMsg( 'payflowpro_gateway-response-125-2' );
				$errorCode = '3';
				break;
			default:
				$responseMsg = wfMsg( 'payflowpro_gateway-response-default' );
				$errorCode = '4';
		}

		return $errorCode;
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
			$countries = $this->getCountries();

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
		// unset edit token
		$this->fnPayflowUnsetEditToken();
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

		// unset edit token
		$this->fnPayflowUnsetEditToken();
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

		// unset edit token
		$this->fnPayflowUnsetEditToken();
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

		// unset edit token
		$this->fnPayflowUnsetEditToken();
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
