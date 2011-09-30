<?php

class GlobalCollectGateway extends GatewayForm {

	/**
	 * Constructor - set up the new special page
	 */
	public function __construct() {
		$this->adapter = new GlobalCollectAdapter();
		parent::__construct(); //the next layer up will know who we are. 
	}

	/**
	 * Show the special page
	 *
	 * @todo
	 * - Add transaction type handler
	 * - What should a failure on transaction_type issues do? log & message client 
	 * - Set up BANK_TRANSFER: Story #308
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgRequest, $wgOut, $wgExtensionAssetsPath;
		$CSSVersion = $this->adapter->getGlobal( 'CSSVersion' );

		$wgOut->allowClickjacking();

		$wgOut->addExtensionStyle(
			$wgExtensionAssetsPath . '/DonationInterface/gateway_forms/css/gateway.css?284' .
			$CSSVersion );

		$gateway_id = $this->adapter->getIdentifier();

		$this->addErrorMessageScript();

		// Make the wiki logo not clickable.
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
		
		/*
		 * The $transactionType should default to false.
		 *
		 * This is being introduced after INSERT_ORDERWITHPAYMENT was built.
		 * Until all INSERT_ORDERWITHPAYMENT can be set in the proper forms, it
		 * will be set as the default.
		 */
		$transactionType = false;
		$transactionType = 'INSERT_ORDERWITHPAYMENT';
		$data['transaction_type'] = isset( $data['transaction_type'] ) ? $data['transaction_type'] : $transactionType;
		$this->adapter->setTransactionType( $data['transaction_type'] );
		unset( $transactionType );
		
		$this->adapter->log( '$transactionType: Default is set to: INSERT_ORDERWITHPAYMENT, this is a temporary hack for backwards compatibility.' );
		$this->adapter->log( 'Setting transaction type: ' . (string) $data['transaction_type'] );
		
		
		// dispatch forms/handling
		if ( $this->adapter->checkTokens() ) {
			if ( $this->adapter->posted && $data['payment_method'] == 'processed' ) {
				// The form was submitted and the payment method has been set
				$this->adapter->log( "Form posted and payment method set." );

				// Check form for errors
				
				$options = array();
				switch ( $this->adapter->getTransactionType() ) {
					
					case 'BANK_TRANSFER':
						$options['creditCard'] = false;
						break;
					
					case 'INSERT_ORDERWITHPAYMENT':
						$options['creditCard'] = true;
						break;
						
					default:
						$options['creditCard'] = true;
				}
				
				$form_errors = $this->validateForm( $data, $this->errors, $options );
				unset( $options );

				//$form_errors = $this->fnValidateForm( $data, $this->errors );

				// If there were errors, redisplay form, otherwise proceed to next step
				if ( $form_errors ) {

					$this->displayForm( $data, $this->errors );
				} else { // The submitted form data is valid, so process it
					// allow any external validators to have their way with the data

					// Execute the proper transaction code:
					switch ( $this->adapter->getTransactionType() ) {
						
						case 'BANK_TRANSFER':
							$this->executeBankTransfer( $wgOut );
							break;
						
						case 'INSERT_ORDERWITHPAYMENT':
							$this->executeInsertOrderWithPayment( $wgOut );
							break;
							
						default:
							
							$message = 'The transaction type [ ' . $this->adapter->getTransactionType() . ' ] was not found.'; 
							throw new Exception( $message );
					}


					//TODO: add all the hooks back in. 

				}
			} else {
				// Display form for the first time
				$oid = $wgRequest->getText( 'order_id' );
				if ( $oid && !empty( $oid ) ) {
					$wgOut->addHTML( "<pre>CAME BACK FROM SOMETHING.</pre>" );
					$result = $this->adapter->do_transaction( 'GET_ORDERSTATUS' );
					$this->displayResultsForDebug( $result );
				}
				$this->adapter->log( "Not posted, or not processed. Showing the form for the first time." );
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
	 * Execute BANK_TRANSFER
	 *
	 * @param OutputPage $wgOut
	 */
	public function executeBankTransfer( &$wgOut ) {

		$result = $this->adapter->do_transaction( 'BANK_TRANSFER' );
		$this->adapter->addDonorDataToSession();

		$this->displayResultsForDebug( $result );
	}

	/**
	 * Execute INSERT_ORDERWITHPAYMENT
	 *
	 * @param OutputPage $wgOut
	 */
	public function executeInsertOrderWithPayment( &$wgOut ) {

		$result = $this->adapter->do_transaction( 'INSERT_ORDERWITHPAYMENT' );
		$this->adapter->addDonorDataToSession();
		//$result = $this->adapter->do_transaction( 'TEST_CONNECTION' );

		$this->displayResultsForDebug( $result );

		if ( !empty( $result['data'] ) ) {

			if ( array_key_exists( 'FORMACTION', $result['data'] ) ) {
				$paymentFrame = Xml::openElement( 'iframe', array(
						'id' => 'globalcollectframe',
						'name' => 'globalcollectframe',
						'width' => '680',
						'height' => '300',
						'frameborder' => '0',
						'style' => 'display:block;',
						'src' => $result['data']['FORMACTION']
						)
				);
				$paymentFrame .= Xml::closeElement( 'iframe' );

				$wgOut->addHTML( $paymentFrame );
			}
		}
	}
	
	/**
	 * Interpret response code, return
	 * 1 if approved
	 * 2 if declined
	 * 3 if invalid data was submitted by user
	 * 4 all other errors
	 */
	function fnPayflowGetResponseMsg( $resultCode, &$responseMsg ) {
		$responseMsg = wfMsg( 'globalcollect_gateway-response-default' );

		switch ( $resultCode ) {
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
