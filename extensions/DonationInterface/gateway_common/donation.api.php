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
		} else if ( $gateway == 'globalcollect' ) {
			$gatewayObj = new GlobalCollectAdapter();
		} else {
			$this->dieUsage( "Invalid gateway <<<$gateway>>> passed to Donation API.", 'unknown_gateway' );
		}
		
		$normalizedData = $gatewayObj->getData();
		
		// Some test output
		$this->getResult()->addValue( 'data', 'gateway', $normalizedData['gateway'] );
		$this->getResult()->addValue( 'data', 'amount', $normalizedData['amount'] );
		$this->getResult()->addValue( 'data', 'currency', $normalizedData['currency'] );
		$this->getResult()->addValue( 'data', 'referrer', $normalizedData['referrer'] );
	}

	public function getAllowedParams() {
		return array(
			'gateway' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'amount' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'currency' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
		);
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
