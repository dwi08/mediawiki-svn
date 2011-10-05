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
		
		$gateway = $wgRequest->getText( 'gateway' );
		
		if ( $gateway == 'payflowpro' ) {
			$gatewayObj = new PayflowProAdapter();
			$result = $gatewayObj->do_transaction( 'Card' );
		} else if ( $gateway == 'globalcollect' ) {
			$gatewayObj = new GlobalCollectAdapter();
			$result = $gatewayObj->do_transaction( 'TEST_CONNECTION' );
		} else {
			$this->dieUsage( "Invalid gateway <<<$gateway>>> passed to Donation API.", 'unknown_gateway' );
		}
		
		$normalizedData = $gatewayObj->getData();
		
		// Some output
		$this->getResult()->setIndexedTagName( $result, 'response' );
		$this->getResult()->addValue( 'data', 'result', $result );
	}

	public function getAllowedParams() {
		return array(
			'gateway' => $this->defineParam( 'gateway', true ),
			'amount' => $this->defineParam( 'amount', true ),
			'currency' => $this->defineParam( 'currency', true ),
			'fname' => $this->defineParam( 'fname', true ),
			'mname' => $this->defineParam( 'mname', false ),
			'lname' => $this->defineParam( 'lname', true ),
			'street' => $this->defineParam( 'street', true ),
			'city' => $this->defineParam( 'city', true ),
			'state' => $this->defineParam( 'state', true ),
			'zip' => $this->defineParam( 'zip', true ),
			'email' => $this->defineParam( 'email', true ),
			'country' => $this->defineParam( 'country', true ),
			'card_num' => $this->defineParam( 'card_num', false  ),
			'card_type' => $this->defineParam( 'card_type', false  ),
			'expiration' => $this->defineParam( 'expiration', false  ),
			'cvv' => $this->defineParam( 'cvv', false  ),
			'payment_method' => $this->defineParam( 'payment_method', false  ),
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

	public function getParamDescription() {
		return array(
			'gateway' => 'Which payment gateway to use - payflowpro, globalcollect, etc.',
			'amount' => 'The amount donated',
			'currency' => 'Currency code',
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
