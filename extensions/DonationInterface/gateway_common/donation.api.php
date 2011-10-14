<?php
/**
 * Generic Donation API
 * This API should be able to accept donation submissions for any gateway or payment type
 * Call with api.php?action=donate
 */
class DonationApi extends ApiBase {
	public function execute() {
		global $wgRequest, $wgParser;
		
		$params = $this->extractRequestParams();
		
		$gateway = $params['gateway'];
		
		// If you want to test with fake data, pass a 'test' param set to true.
		// You still have to set the gateway you are testing though.
		if ( array_key_exists( 'test', $params ) && $params['test'] ) {
			$params = $this->getTestData( $gateway );
		}
		
		$method = $params['payment_method'];
		
		if ( $gateway == 'payflowpro' ) {
			$gatewayObj = new PayflowProAdapter();
			switch ( $method ) {
				// TODO: add other payment methods
				default:
					$result = $gatewayObj->do_transaction( 'Card' );
			}
		} else if ( $gateway == 'globalcollect' ) {
			$gatewayObj = new GlobalCollectAdapter();
			switch ( $method ) {
				// TODO: add other payment methods
				default:
					$result = $gatewayObj->do_transaction( 'TEST_CONNECTION' );
			}
		} else {
			$this->dieUsage( "Invalid gateway <<<$gateway>>> passed to Donation API.", 'unknown_gateway' );
		}
		
		//$normalizedData = $gatewayObj->getData();
		
		// Some output
		$this->getResult()->setIndexedTagName( $result, 'response' );
		$this->getResult()->addValue( 'data', 'request', $params );
		$this->getResult()->addValue( 'data', 'result', $result );
	}

	public function getAllowedParams() {
		return array(
			'gateway' => $this->defineParam( 'gateway', true ),
			'test' => $this->defineParam( 'test', false  ),
			'amount' => $this->defineParam( 'amount', false ),
			'currency' => $this->defineParam( 'currency', false ),
			'fname' => $this->defineParam( 'fname', false ),
			'mname' => $this->defineParam( 'mname', false ),
			'lname' => $this->defineParam( 'lname', false ),
			'street' => $this->defineParam( 'street', false ),
			'city' => $this->defineParam( 'city', false ),
			'state' => $this->defineParam( 'state', false ),
			'zip' => $this->defineParam( 'zip', false ),
			'email' => $this->defineParam( 'email', false ),
			'country' => $this->defineParam( 'country', false ),
			'card_num' => $this->defineParam( 'card_num', false  ),
			'card_type' => $this->defineParam( 'card_type', false  ),
			'expiration' => $this->defineParam( 'expiration', false  ),
			'cvv' => $this->defineParam( 'cvv', false  ),
			'payment_method' => $this->defineParam( 'payment_method', false  ),
			'language' => $this->defineParam( 'language', false  ),
		);
	}
	
	private function defineParam( $paramName, $required = false, $type = 'string' ) {
		if ( $required ) {
			$param = array( ApiBase::PARAM_TYPE => $type, ApiBase::PARAM_REQUIRED => true );
		} else {
			$param = array( ApiBase::PARAM_TYPE => $type );
		}
		return $param;
	}
	
	private function getTestData( $gateway ) {
		$params = array(
			'gateway' => $gateway,
			'amount' => "35",
			'currency' => 'USD',
			'fname' => 'Tester',
			'mname' => 'T.',
			'lname' => 'Testington',
			'street' => '548 Market St.',
			'city' => 'San Francisco',
			'state' => 'CA',
			'zip' => '94104',
			'email' => 'test@example.com',
			'country' => 'US',
			'card_num' => '378282246310005',
			'card_type' => 'american',
			'expiration' => date( 'my', strtotime( '+1 year 1 month' ) ),
			'cvv' => '001',
			'payment_method' => 'card',
			'language' => 'en',
		);
		return $params;
	}

	public function getParamDescription() {
		return array(
			'gateway' => 'Which payment gateway to use - payflowpro, globalcollect, etc.',
			'test' => 'Set to true if you want to use bogus test data instead of supplying your own',
			'amount' => 'The amount donated',
			'currency' => 'Currency code',
			'fname' => 'First name',
			'mname' => 'Middle name',
			'lname' => 'Last name',
			'street' => 'First line of street address',
			'city' => 'City',
			'state' => 'State abbreviation',
			'zip' => 'Postal code',
			'email' => 'Email address',
			'country' => 'Country code',
			'card_num' => 'Credit card number',
			'card_type' => 'Credit card type',
			'expiration' => 'Expiration date',
			'cvv' => 'CVV security code',
			'payment_method' => 'Payment method to use',
			'language' => 'Language code',
		);
	}

	public function getDescription() {
		return array(
			'This API allow you to submit a donation to the Wikimedia Foundation using a',
			'variety of payment processors.',
		);
	}

	public function getExamples() {
		return array(
			'api.php?action=donate&gateway=payflowpro&amount=2.00&currency=USD',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': $Id: DonationApi.php 1.0 kaldari $';
	}
}
