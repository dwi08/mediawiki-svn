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
		
		$ddObj = new DonationData( 'DonationApi', false, $params );
		
		$normalizedData = $ddObj->getData();
		
		// Some test output
		$this->getResult()->addValue( 'data', 'gateway', $normalizedData['gateway'] );
		$this->getResult()->addValue( 'data', 'amount', $normalizedData['amount'] );
	}

	public function getAllowedParams() {
		return array(
			'gateway' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'amount' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
		);
	}

	public function getParamDescription() {
		return array(
			'gateway' => 'Which payment gateway to use - payflowpro, globalcollect, etc.',
			'amount' => 'The amount donated',
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
			'api.php?action=donate&gateway=payflowpro&amount=2.00',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': $Id: DonationApi.php 1.0 kaldari $';
	}
}
