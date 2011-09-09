<?php

class GlobalCollectGateway extends UnlistedSpecialPage {

	/**
	 * Defines the action to take on a PFP transaction.
	 *
	 * Possible values include 'process', 'challenge',
	 * 'review', 'reject'.  These values can be set during
	 * data processing validation, for instance.
	 *
	 * Hooks are exposed to handle the different actions.
	 *
	 * Defaults to 'process'.
	 * @var string
	 */
	public $action = 'process';

	/**
	 * Holds the GlobalCollect response from a transaction
	 * @var array
	 */
	public $payflow_response = array();

	/**
	 * A container for the form class
	 *
	 * Used to loard the form object to display the CC form
	 * @var object
	 */
	public $form_class;

	/**
	 * An array of form errors
	 * @var array
	 */
	public $errors = array();

	/**
	 * Constructor - set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'GlobalCollectGateway' );
		$this->errors = $this->getPossibleErrors();
		
		$dir = dirname( __FILE__ ) . '/';
		require_once($dir . 'globalcollect.adapter.php');
		$this->adapter = new GlobalCollectAdapter();
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgRequest, $wgOut, $wgScriptPath,
			$wgPayFlowProGatewayCSSVersion;

		$wgOut->addExtensionStyle(
			"{$wgScriptPath}/extensions/DonationInterface/gateway_forms/css/gateway.css?284" .
			$wgPayFlowProGatewayCSSVersion );

		$scriptVars = array(
			'globalcollectGatewayErrorMsgJs' => wfMsg( 'globalcollect_gateway-error-msg-js' ),
			'globalcollectGatewayErrorMsgEmail' => wfMsg( 'globalcollect_gateway-error-msg-email' ),
			'globalcollectGatewayErrorMsgAmount' => wfMsg( 'globalcollect_gateway-error-msg-amount' ),
			'globalcollectGatewayErrorMsgEmailAdd' => wfMsg( 'globalcollect_gateway-error-msg-emailAdd' ),
			'globalcollectGatewayErrorMsgFname' => wfMsg( 'globalcollect_gateway-error-msg-fname' ),
			'globalcollectGatewayErrorMsgLname' => wfMsg( 'globalcollect_gateway-error-msg-lname' ),
			'globalcollectGatewayErrorMsgStreet' => wfMsg( 'globalcollect_gateway-error-msg-street' ),
			'globalcollectGatewayErrorMsgCity' => wfMsg( 'globalcollect_gateway-error-msg-city' ),
			'globalcollectGatewayErrorMsgState' => wfMsg( 'globalcollect_gateway-error-msg-state' ),
			'globalcollectGatewayErrorMsgZip' => wfMsg( 'globalcollect_gateway-error-msg-zip' ),
			'globalcollectGatewayErrorMsgCountry' => wfMsg( 'globalcollect_gateway-error-msg-country' ),
			'globalcollectGatewayErrorMsgCardNum' => wfMsg( 'globalcollect_gateway-error-msg-card_num' ),
			'globalcollectGatewayErrorMsgExpiration' => wfMsg( 'globalcollect_gateway-error-msg-expiration' ),
			'globalcollectGatewayErrorMsgCvv' => wfMsg( 'globalcollect_gateway-error-msg-cvv' ),
			'globalcollectGatewayCVVExplain' => wfMsg( 'globalcollect_gateway-cvv-explain' ),
		);

		$wgOut->addScript( Skin::makeVariablesScript( $scriptVars ) );

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
	
		//TODO: This is short-circuiting what I really want to do here. 
		//so stop it. 
		$data = $this->adapter->getDisplayData();
		
		// dispatch forms/handling
		if ( $this->adapter->checkTokens() ) {
			if ( $this->adapter->posted && $data['payment_method'] == 'processed' ) {
				$this->adapter->log("Posted and processed.");
				
				// increase the count of attempts
				//++$data['numAttempt'];

				// Check form for errors and redisplay with messages
				$form_errors = $this->fnPayflowValidateForm( $data, $this->errors );

				if ( $form_errors ) {
					$this->fnPayflowDisplayForm( $data, $this->errors );
				} else { // The submitted form data is valid, so process it
					// allow any external validators to have their way with the data
					
					
					

					$result = $this->adapter->do_transaction( 'INSERT_ORDERWITHPAYMENT' );
					//$result = $this->adapter->do_transaction( 'TEST_CONNECTION' );
					
					$wgOut->addHTML($result['message']);
					if (!empty($result['errors'])){
						$wgOut->addHTML("<ul>");
						foreach ($result['errors'] as $code => $value){
							$wgOut->addHTML("<li>Error $code: $value");
						}
						$wgOut->addHTML("</ul>");
					}
					
					if (!empty($result['data'])){
						$wgOut->addHTML("<ul>");
						foreach ($result['data'] as $key => $value){
							$wgOut->addHTML("<li>$key: $value");
						}
						$wgOut->addHTML("</ul>");
					}
		
		
					
//					self::log( $data[ 'order_id' ] . " Preparing to query MaxMind" );
//					wfRunHooks( 'PayflowGatewayValidate', array( &$this, &$data ) );
//					self::log( $data[ 'order_id' ] . ' Finished querying Maxmind' );
//
//					// if the transaction was flagged for review
//					if ( $this->action == 'review' ) {
//						// expose a hook for external handling of trxns flagged for review
//						wfRunHooks( 'PayflowGatewayReview', array( &$this, &$data ));
//					}
//
//					// if the transaction was flagged to be 'challenged'
//					if ( $this->action == 'challenge' ) {
//						// expose a hook for external handling of trxns flagged for challenge (eg captcha)
//						wfRunHooks( 'PayflowGatewayChallenge', array( &$this, &$data ) );
//					}
//
//					// if the transaction was flagged for rejection
//					if ( $this->action == 'reject' ) {
//						// expose a hook for external handling of trxns flagged for rejection
//						wfRunHooks( 'PayflowGatewayReject', array( &$this, &$data ) );
//
//						$this->fnPayflowDisplayDeclinedResults( '' );
//						$this->fnPayflowUnsetEditToken();
//					}
//
//					// if the transaction was flagged for processing
//					if ( $this->action == 'process' ) {
//						// expose a hook for external handling of trxns ready for processing
//						wfRunHooks( 'PayflowGatewayProcess', array( &$this, &$data ) );
//						$this->fnGlobalCollectProcessTransaction( $data, $payflow_data );
//					}
//
//					// expose a hook for any post processing
//					wfRunHooks( 'PayflowGatewayPostProcess', array( &$this, &$data ) );
				}
			} else {
				// Display form for the first time
				$this->adapter->log("Not posted, or not processed. Showing the form for the first time.");
				$this->fnPayflowDisplayForm( $data, $this->errors );
			}		
		} else {
			if ( !$this->adapter->isCache() ) {
				// if we're not caching, there's a token mismatch
				$this->errors['general']['token-mismatch'] = wfMsg( 'globalcollect_gateway-token-mismatch' );
			}
			$this->fnPayflowDisplayForm( $data, $this->errors );
		}
	}
	

	/**
	 * Build and display form to user
	 *
	 * @param $data Array: array of posted user input
	 * @param $error Array: array of error messages returned by validate_form function
	 *
	 * The message at the top of the form can be edited in the payflow_gateway.i18n.php file
	 */
	public function fnPayflowDisplayForm( &$data, &$error ) {
		global $wgOut, $wgRequest;

		// save contrib tracking id early to track abondonment
//		if ( !empty($data) && ( $data[ 'numAttempt' ] == '0' && ( !$wgRequest->getText( 'utm_source_id', false ) || $wgRequest->getText( '_nocache_' ) == 'true' ) ) ) {
//			$tracked = $this->fnPayflowSaveContributionTracking( $data );
//			if ( !$tracked ) {
//				$when = time();
//				self::log( $data[ 'order_id' ] . ' Unable to save data to the contribution_tracking table ' . $when );
//			}
//		}

		$form_class = $this->getFormClass();
		$form_obj = new $form_class( $data, $error );
		$form = $form_obj->getForm();
		$wgOut->addHTML( $form );
	}

	/**
	 * Set the form class to use to generate the CC form
	 *
	 * @param string $class_name The class name of the form to use
	 */
	public function setFormClass( $class_name = NULL ) {
		if ( !$class_name ) {
			global $wgRequest, $wgPayflowProGatewayDefaultForm;
			$form_class = $wgRequest->getText( 'form_name', $wgPayflowProGatewayDefaultForm );

			// make sure our form class exists before going on, if not try loading default form class
			$class_name = "Gateway_Form_" . $form_class;
			if ( !class_exists( $class_name ) ) {
				$class_name_orig = $class_name;
				$class_name = "Gateway_Form_" . $wgPayflowProGatewayDefaultForm;
				if ( !class_exists( $class_name ) ) {
					throw new MWException( 'Could not load form ' . $class_name_orig . ' nor default form ' . $class_name );
				}
			}
		}
		$this->form_class = $class_name;
	}

	/**
	 * Get the currently set form class
	 *
	 * Will set the form class if the form class not already set
	 * Using logic in setFormClass()
	 * @return string
	 */
	public function getFormClass( ) {
		if ( !isset( $this->form_class ) ) {
			$this->setFormClass();
		}
		return $this->form_class;
	}

	/**
	 * Checks posted form data for errors and returns array of messages
	 */
	private function fnPayflowValidateForm( &$data, &$error ) {
		global $wgPayflowProGatewayPriceFloor, $wgPayflowProGatewayPriceCeiling;
		
		// begin with no errors
		$error_result = '0';

		// create the human-speak message for required fields
		// does not include fields that are not required
		$msg = array(
			'amount' => wfMsg( 'globalcollect_gateway-error-msg-amount' ),
			'emailAdd' => wfMsg( 'globalcollect_gateway-error-msg-emailAdd' ),
			'fname' => wfMsg( 'globalcollect_gateway-error-msg-fname' ),
			'lname' => wfMsg( 'globalcollect_gateway-error-msg-lname' ),
			'street' => wfMsg( 'globalcollect_gateway-error-msg-street' ),
			'city' => wfMsg( 'globalcollect_gateway-error-msg-city' ),
			'state' => wfMsg( 'globalcollect_gateway-error-msg-state' ),
			'zip' => wfMsg( 'globalcollect_gateway-error-msg-zip' ),
			'card_num' => wfMsg( 'globalcollect_gateway-error-msg-card_num' ),
			'expiration' => wfMsg( 'globalcollect_gateway-error-msg-expiration' ),
			'cvv' => wfMsg( 'globalcollect_gateway-error-msg-cvv' ),
		);

		// find all empty fields and create message
		foreach ( $data as $key => $value ) {
			if ( $value == '' || ($key == 'state' && $value == 'YY' )) {
				// ignore fields that are not required
				if ( isset( $msg[$key] ) ) {
					$error[$key] = "**" . wfMsg( 'globalcollect_gateway-error-msg', $msg[$key] ) . "**<br />";
					$error_result = '1';
				}
			}
		}

		// check amount
		if ( !preg_match( '/^\d+(\.(\d+)?)?$/', $data[ 'amount' ] ) || 
			( (float) $this->convert_to_usd( $data[ 'currency' ], $data[ 'amount' ] ) < (float) $wgPayflowProGatewayPriceFloor || 
				(float) $this->convert_to_usd( $data[ 'currency' ], $data[ 'amount' ] ) > (float) $wgPayflowProGatewayPriceCeiling ) ) {
			$error['invalidamount'] = wfMsg( 'globalcollect_gateway-error-msg-invalid-amount' );
			$error_result = '1';
		}

		// is email address valid?
		$isEmail = User::isValidEmailAddr( $data['email'] );

		// create error message (supercedes empty field message)
		if ( !$isEmail ) {
			$error['emailAdd'] = wfMsg( 'globalcollect_gateway-error-msg-email' );
			$error_result = '1';
		}

		// validate that credit card number entered is correct and set the card type
		if ( preg_match( '/^3[47][0-9]{13}$/', $data[ 'card_num' ] ) ) { // american express
			$data[ 'card' ] = 'american';
		} elseif ( preg_match( '/^5[1-5][0-9]{14}$/', $data[ 'card_num' ] ) ) { //	mastercard
			$data[ 'card' ] = 'mastercard';
		} elseif ( preg_match( '/^4[0-9]{12}(?:[0-9]{3})?$/', $data[ 'card_num' ] ) ) {// visa
			$data[ 'card' ] = 'visa';
		} elseif ( preg_match( '/^6(?:011|5[0-9]{2})[0-9]{12}$/', $data[ 'card_num' ] ) ) { // discover
			$data[ 'card' ] = 'discover';
		} else { // an invalid credit card number was entered
		//TODO: Make sure this is uncommented when you commit for reals! 
			//$error_result = '1';
			//$error[ 'card_num' ] = wfMsg( 'globalcollect_gateway-error-msg-card-num' );
		}
		
		return $error_result;
	}

	/**
	 * "Reads" the name-value pair result string returned by Payflow and creates corresponding error messages
	 *
	 * @param $data Array: array of user input
	 * @param $result String: name-value pair results returned by Payflow
	 *
	 * Credit: code modified from globalcollect_example_EC.php posted (and supervised) on the PayPal developers message board
	 */
	private function fnPayflowGetResults( $data, $result ) {
		// prepare NVP response for sorting and outputting
		$responseArray = array();

		/**
		 * The result response string looks like:
		 *	RESULT=7&PNREF=E79P2C651DC2&RESPMSG=Field format error&HOSTCODE=10747&DUPLICATE=1
		 * We want to turn this into an array of key value pairs, so explode on '&' and then
		 * split up the resulting strings into $key => $value
		 */
		$result_arr = explode( "&", $result );
		foreach ( $result_arr as $result_pair ) {
			list( $key, $value ) = preg_split( "/=/", $result_pair );
			$responseArray[ $key ] = $value;
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
		self::log( $data[ 'order_id' ] . " Transaction complete." );
		
		// if approved, display results and send transaction to the queue
		if ( $errorCode == '1' ) {
			self::log( $data[ 'order_id' ] . " " . $data[ 'i_order_id' ] . " Transaction approved.", 'globalcollect_gateway', LOG_DEBUG );
			$this->fnPayflowDisplayApprovedResults( $data, $responseArray, $responseMsg );
			// give user a second chance to enter incorrect data
		} elseif ( ( $errorCode == '3' ) && ( $data['numAttempt'] < '5' ) ) {
			self::log( $data[ 'order_id' ] . " " . $data[ 'i_order_id' ] . " Transaction unsuccessful (invalid info).", 'globalcollect_gateway', LOG_DEBUG );
			// pass responseMsg as an array key as required by displayForm
			$this->errors['retryMsg'] = $responseMsg;
			$this->fnPayflowDisplayForm( $data, $this->errors );
			// if declined or if user has already made two attempts, decline
		} elseif ( ( $errorCode == '2' ) || ( $data['numAttempt'] >= '3' ) ) {
			self::log( $data[ 'order_id' ] . " " . $data[ 'i_order_id' ] . " Transaction declined.", 'globalcollect_gateway', LOG_DEBUG );
			$this->fnPayflowDisplayDeclinedResults( $responseMsg );
		} elseif ( ( $errorCode == '4' ) ) {
			self::log( $data[ 'order_id' ] . " " . $data[ 'i_order_id' ] . " Transaction unsuccessful.", 'globalcollect_gateway', LOG_DEBUG );
			$this->fnPayflowDisplayOtherResults( $responseMsg );
		} elseif ( ( $errorCode == '5' ) ) {
			self::log( $data[ 'order_id' ] . " " . $data[ 'i_order_id' ] . " Transaction pending.", 'globalcollect_gateway', LOG_DEBUG );
			$this->fnPayflowDisplayPending( $data, $responseArray, $responseMsg );
		}

	}// end display results

	/**
	 * Interpret response code, return
	 * 1 if approved
	 * 2 if declined
	 * 3 if invalid data was submitted by user
	 * 4 all other errors
	 */
	function fnPayflowGetResponseMsg( $resultCode, &$responseMsg ) {
		$responseMsg = wfMsg( 'globalcollect_gateway-response-default' );

		switch( $resultCode ) {
			case '0':
				$responseMsg = wfMsg( 'globalcollect_gateway-response-0' );
				$errorCode = '1';
				break;
			case '126':
				$responseMsg = wfMsg( 'globalcollect_gateway-response-126-2' );
				$errorCode = '5';
				break;
			case '12':
				$responseMsg = wfMsg( 'globalcollect_gateway-response-12' );
				$errorCode = '2';
				break;
			case '13':
				$responseMsg = wfMsg( 'globalcollect_gateway-response-13' );
				$errorCode = '2';
				break;
			case '114':
				$responseMsg = wfMsg( 'globalcollect_gateway-response-114' );
				$errorCode = '2';
				break;
			case '4':
				$responseMsg = wfMsg( 'globalcollect_gateway-response-4' );
				$errorCode = '3';
				break;
			case '23':
				$responseMsg = wfMsg( 'globalcollect_gateway-response-23' );
				$errorCode = '3';
				break;
			case '24':
				$responseMsg = wfMsg( 'globalcollect_gateway-response-24' );
				$errorCode = '3';
				break;
			case '112':
				$responseMsg = wfMsg( 'globalcollect_gateway-response-112' );
				$errorCode = '3';
				break;
			case '125':
				$responseMsg = wfMsg( 'globalcollect_gateway-response-125-2' );
				$errorCode = '3';
				break;
			default:
				$responseMsg = wfMsg( 'globalcollect_gateway-response-default' );
				$errorCode = '4';
		}

		return $errorCode;
	}

	/**
	 * Prepares the transactional message to be sent via Stomp to queueing service
	 * 
	 * @param array $data
	 * @param array $resposneArray
	 * @param array $responseMsg
	 * @return array
	 */
	public function prepareStompTransaction( $data, $responseArray, $responseMsg ) {
		$countries = $this->getCountries();
		
		$transaction = array();

		// include response message
		$transaction['response'] = $responseMsg;
		
		// include date
		$transaction['date'] = time();
		
		// put all data into one array
		$optout = $this->determineOptOut( $data );
		$data[ 'anonymous' ] = $optout[ 'anonymous' ];
		$data[ 'optout' ] = $optout[ 'optout' ];
		
		$transaction += array_merge( $data, $responseArray );
		
		return $transaction;
	}
	
	/**
	 * Fetch an array of country abbrevs => country names
	 */
	public static function getCountries() {
		require_once( 'includes/countryCodes.inc' );
		return countryCodes();
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
				'amount' => array( wfMsg( 'globalcollect_gateway-donor-amount' ), $data['amount'] ),
				'email' => array( wfMsg( 'globalcollect_gateway-donor-email' ), $data['email'] ),
				'name' => array( wfMsg( 'globalcollect_gateway-donor-name' ), $data['fname'], $data['mname'], $data['lname'] ),
				'address' => array( wfMsg( 'globalcollect_gateway-donor-address' ), $data['street'], $data['city'], $data['state'], $data['zip'], $countries[$data['country']] ),
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

		$thankyou = wfMsg( 'globalcollect_gateway-thankyou' );

		// display response message
		$wgOut->addHTML( '<h2 class="response_message">' . $thankyou . '</h2>' );
		$wgOut->addHTML( '<p>' . $responseMsg );

		// unset edit token
		$this->fnPayflowUnsetEditToken();
	}

	public function getPossibleErrors() {
		return array(
			'general' => '',
			'retryMsg' => '',
			'invalidamount' => '',
			'card_num' => '',
			'card' => '',
			'cvv' => '',
			'fname' => '',
			'lname' => '',
			'city' => '',
			'country' => '',
			'street' => '',
			'state' => '',
			'zip' => '',
			'emailAdd' => '',
		);
	}


	/**
	 * Handle redirection of form content to PayPal
	 *
	 * @fixme If we can update contrib tracking table in ContributionTracking
	 * 	extension, we can probably get rid of this method and just submit the form
	 *  directly to the paypal URL, and have all processing handled by ContributionTracking
	 *  This would make this a lot less hack-ish
	 */
	public function paypalRedirect( &$data ) {
		global $wgPayflowProGatewayPaypalURL, $wgOut;

		// if we don't have a URL enabled throw a graceful error to the user
		if ( !strlen( $wgPayflowProGatewayPaypalURL ) ) {
			$this->errors['general'][ 'nopaypal' ] = wfMsg( 'payflow_gateway-error-msg-nopaypal' );
			return;
		}

		// update the utm source to set the payment instrument to pp rather than cc
		$utm_source_parts = explode( ".", $data[ 'utm_source' ] );
		$utm_source_parts[2] = 'pp';
		$data[ 'utm_source' ] = implode( ".", $utm_source_parts );
		$data[ 'gateway' ] = 'paypal';
		$data[ 'currency_code' ] = $data[ 'currency' ];
		/**
		 * update contribution tracking
		 */
		$this->updateContributionTracking( $data, true );

		$wgPayflowProGatewayPaypalURL .= "/" . $data[ 'language' ] . "?gateway=paypal";
		
		// submit the data to the paypal redirect URL
		$wgOut->redirect( $wgPayflowProGatewayPaypalURL . '&' . http_build_query( $data ) );
	}
	
	public static function log( $msg, $identifier='globalcollect_gateway', $log_level=LOG_INFO ) {
		global $wgGlobalCollectGatewayUseSyslog;
		
		// if we're not using the syslog facility, use wfDebugLog
		if ( !$wgGlobalCollectGatewayUseSyslog ) {
			wfDebugLog( $identifier, $msg );
			return;
		}
		
		// otherwise, use syslogging
		openlog( $identifier, LOG_ODELAY, LOG_SYSLOG );
		syslog( $log_level, $msg );
		closelog();	
	}
	
	/**
	 * Convert an amount for a particular currency to an amount in USD
	 * 
	 * This is grosley rudimentary and likely wildly inaccurate.
	 * This mimicks the hard-coded values used by the WMF to convert currencies
	 * for validatoin on the front-end on the first step landing pages of their
	 * donation process - the idea being that we can get a close approximation
	 * of converted currencies to ensure that contributors are not going above
	 * or below the price ceiling/floor, even if they are using a non-US currency.
	 * 
	 * In reality, this probably ought to use some sort of webservice to get real-time
	 * conversion rates.
	 *  
	 * @param $currency_code
	 * @param $amount
	 * @return unknown_type
	 */
	public function convert_to_usd( $currency_code, $amount ) {
		switch ( strtoupper( $currency_code ) ) {
			case 'USD':
				$usd_amount = $amount / 1;
				break;
			case 'GBP':
				$usd_amount = $amount / 1;
				break;
			case 'EUR':
				$usd_amount = $amount / 1;
				break;
			case 'AUD':
				$usd_amount = $amount / 2;
				break;
			case 'CAD':
				$usd_amount = $amount / 1;
				break;
			case 'CHF':
				$usd_amount = $amount / 1;
				break;
			case 'CZK':
				$usd_amount = $amount / 20;
				break;
			case 'DKK':
				$usd_amount = $amount / 5;
				break;
			case 'HKD':
				$usd_amount = $amount / 10;
				break;
			case 'HUF':
				$usd_amount = $amount / 200;
				break;
			case 'JPY':
				$usd_amount = $amount / 100;
				break;
			case 'NZD':
				$usd_amount = $amount / 2;
				break;
			case 'NOK':
				$usd_amount = $amount / 10;
				break;
			case 'PLN':
				$usd_amount = $amount / 5;
				break;
			case 'SGD':
				$usd_amount = $amount / 2;
				break;
			case 'SEK':
				$usd_amount = $amount / 10;
				break;
			case 'ILS':
				$usd_amount = $amount / 5;
				break;
			default:
				$usd_amount = $amount;
				break;
		}
		
		return $usd_amount;
	}
} // end class
