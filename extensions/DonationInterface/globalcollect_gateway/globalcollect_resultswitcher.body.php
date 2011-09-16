<?php

class GlobalCollectGatewayResult extends UnlistedSpecialPage {

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
	 * An array of form errors
	 * @var array
	 */
	public $errors = array( );

	/**
	 * Constructor - set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'GlobalCollectGatewayResult' );
		$this->errors = $this->getPossibleErrors();

		$this->adapter = new GlobalCollectAdapter();
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgRequest, $wgOut, $wgExtensionAssetsPath,
		$wgPayFlowProGatewayCSSVersion;

		$wgOut->allowClickjacking();
		$wgOut->addModules( 'iframe.liberator' );

		$wgOut->addExtensionStyle(
			$wgExtensionAssetsPath . '/DonationInterface/gateway_forms/css/gateway.css?284' .
			$wgPayFlowProGatewayCSSVersion );

		$this->setHeaders();


		// dispatch forms/handling
		if ( $this->adapter->checkTokens() ) {
			// Display form for the first time
			$oid = $wgRequest->getText( 'order_id' );
			if ( $oid && !empty( $oid ) ) {
				$result = $this->adapter->do_transaction( 'GET_ORDERSTATUS' );
				$this->displayResultsForDebug( $result );
			}
			$this->adapter->log( "Not posted, or not processed. Showing the form for the first time." );
		} else {
			if ( !$this->adapter->isCache() ) {
				// if we're not caching, there's a token mismatch
				$this->errors['general']['token-mismatch'] = wfMsg( 'payflowpro_gateway-token-mismatch' );
			}
		}
	}

	function displayResultsForDebug( $results ) {
		global $wgOut;
		$wgOut->addHTML( $results['message'] );

		if ( !empty( $results['errors'] ) ) {
			$wgOut->addHTML( "<ul>" );
			foreach ( $results['errors'] as $code => $value ) {
				$wgOut->addHTML( "<li>Error $code: $value" );
			}
			$wgOut->addHTML( "</ul>" );
		}

		if ( !empty( $results['data'] ) ) {
			$wgOut->addHTML( "<ul>" );
			foreach ( $results['data'] as $key => $value ) {
				if ( is_array( $value ) ) {
					$wgOut->addHTML( "<li>$key:<ul>" );
					foreach ( $value as $key2 => $val2 ) {
						$wgOut->addHTML( "<li>$key2: $val2" );
					}
					$wgOut->addHTML( "</ul>" );
				} else {
					$wgOut->addHTML( "<li>$key: $value" );
				}
			}
			$wgOut->addHTML( "</ul>" );
		} else {
			$wgOut->addHTML( "Empty Results" );
		}
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

		$transaction = array( );

		// include response message
		$transaction['response'] = $responseMsg;

		// include date
		$transaction['date'] = time();

		// put all data into one array
		$optout = $this->determineOptOut( $data );
		$data['anonymous'] = $optout['anonymous'];
		$data['optout'] = $optout['optout'];

		$transaction += array_merge( $data, $responseArray );

		return $transaction;
	}

	public function getPossibleErrors() {
		return array(
			'general' => '',
			'retryMsg' => '',
			'invalidamount' => '',
			'card_num' => '',
			'card_type' => '',
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
			$this->errors['general']['nopaypal'] = wfMsg( 'payflow_gateway-error-msg-nopaypal' );
			return;
		}

		// update the utm source to set the payment instrument to pp rather than cc
		$utm_source_parts = explode( ".", $data['utm_source'] );
		$utm_source_parts[2] = 'pp';
		$data['utm_source'] = implode( ".", $utm_source_parts );
		$data['gateway'] = 'paypal';
		$data['currency_code'] = $data['currency'];
		/**
		 * update contribution tracking
		 */
		$this->updateContributionTracking( $data, true );

		$wgPayflowProGatewayPaypalURL .= "/" . $data['language'] . "?gateway=paypal";

		// submit the data to the paypal redirect URL
		$wgOut->redirect( $wgPayflowProGatewayPaypalURL . '&' . http_build_query( $data ) );
	}

	public static function log( $msg, $log_level=LOG_INFO ) {
		$this->adapter->log( $msg, $log_level );
	}

}

// end class
