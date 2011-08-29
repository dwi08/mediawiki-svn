<?php


class GlobalCollectAdapter {

	//Contains the map of THEIR var names, to OURS.
	//I'd have gone the other way, but we'd run into 1:many pretty quick. 
	private $var_map;
	private $accountInfo;
	private $url;
	private $transactions;
	private $postdata;
	private $postdatadefaults;
	private $xmlDoc;
	
	function __construct($data){
		global $wgGatewayTest;
		
		$this->postdata = $data;
		
		
		$returnTitle = Title::newFromText( 'Donate-thanks/en' );
		$returnto = $returnTitle->getFullURL();
		
		//this DEFINITELY needs to be defined in the parent class, and contain everything anybody might want to know.
		$this->postdatadefaults = array(
			'order_id' => '112358' . rand(),
			'amount' => '11.38',
			'currency' => 'USD',
			'language' => 'en',
			'country' => 'US',
			'returnto' => $returnto,
			'user_ip' => ( $wgGatewayTest ) ? '12.12.12.12' : wfGetIP(), // current user's IP address
			'order_id' => $this->getOrderId(),
			'i_order_id' => $this->getInternalOrderId(),
		);
	
		
		///ehh. Most of this should be broken up into functions for the sake of readibility. 
		
		$this->var_map = array(
			'ORDERID' => 'order_id',
			'AMOUNT' => 'amount',
			'CURRENCYCODE' => 'currency',
			'LANGUAGECODE' => 'language',
			'COUNTRYCODE' => 'country',
			'MERCHANTREFERENCE' => 'order_id',
			'RETURNURL' => 'returnto', //I think. It might not even BE here yet. Boo-urns. 
			'IPADDRESS' => 'user_ip' //TODO: Not sure if this should be OUR ip, or the user's ip. Hurm.
		);
		
		global $wgGlobalCollectURL, $wgGlobalCollectMerchantID;
		
		$this->url = $wgGlobalCollectURL;
		
		$this->accountInfo = array(
//			'PAYMENTPRODUCTID' => '3', //actually, these are almost certainly transaction-specific.
//			'HOSTEDINDICATOR' => '1',
			'MERCHANTID' => $wgGlobalCollectMerchantID,
			//'IPADDRESS' => '', //TODO: Not sure if this should be OUR ip, or the user's ip. Hurm. 
			'VERSION' => "1.0",
		);
		
		/* General idea here:
		 * This bad boy will (probably) contain the structure of all possible transactions as defined by the gateway. 
		 * First array key: Some way for us to id the transaction. Doesn't actually have to be the gateway's name for it, but
		 * I'm starting with that.
		 * Second array key: 
		 *		'structure' contains the layout of that transaction.
		 *		'defaults' contains default values for the leaf 'values' 
		 *		I could put a 'type' in here, but I think we can assume that if 'structure' is multi-layer, we're XML.
		 * Array "leaves" in 'structure' will be assigned a value according to the var_map, and the posted data. 
		 *	There should also be a mechanism for assigning defaults, but I'm not entirely sure what that would look like quite yet...
		 * 
		 */ 
		$this->transactions = array(
			'INSERT_ORDERWITHPAYMENT' => array(
				'request' => array(
					'REQUEST' => array(
						'ACTION',
						'META' => array(
							'MERCHANTID',
							'IPADDRESS',
							'VERSION'
						),
						'PARAMS' => array(
							'ORDER' => array(
								'ORDERID',
								'AMOUNT',
								'CURRENCYCODE',
								'LANGUAGECODE',
								'COUNTRYCODE',
								'MERCHANTREFERENCE'
								),
							'PAYMENT' => array(
								'PAYMENTPRODUCTID',
								'AMOUNT',
								'CURRENCYCODE',
								'LANGUAGECODE',
								'COUNTRYCODE',
								'HOSTEDINDICATOR',
								'RETURNURL',
							)
						)
					)
				),
				'values' => array(
					'ACTION' => 'INSERT_ORDERWITHPAYMENT',
					'HOSTEDINDICATOR' => '1',
					'PAYMENTPRODUCTID' => '3',
				),
			)
		);
	
	}
	
	function getValue($gateway_field_name, $transaction){
		//How do we determine the value of a field asked for in a particular transaction? 
		
		//If there's a hard-coded value in the transaction definition, use that.
		if(array_key_exists($transaction, $this->transactions) && 
			array_key_exists('values', $this->transactions[$transaction]) && 
			array_key_exists($gateway_field_name, $this->transactions[$transaction]['values'])){
			return $this->transactions[$transaction]['values'][$gateway_field_name];
		}
		
		//if it's account info, use that.
		//$this->accountInfo;
		if(array_key_exists($gateway_field_name, $this->accountInfo)){
			return $this->accountInfo[$gateway_field_name];
		}
		
		
		//If there's a value in the post data (name-translated by the var_map), use that.
		if (array_key_exists($gateway_field_name, $this->var_map)){
			if (array_key_exists($this->var_map[$gateway_field_name], $this->postdata) && 
				$this->postdata[$this->var_map[$gateway_field_name]] !== ''){
				//if it was sent, use that. 
				return $this->postdata[$this->var_map[$gateway_field_name]];
			} else {
				//return the default for that form value
				return $this->postdatadefaults[$this->var_map[$gateway_field_name]];
			}
		}
		
		//not in the map, or hard coded. What then? 
		//Complain furiously, for your code is faulty. 
		//TODO: Something that plays nice with others, instead of... 
		die("getValue found NOTHING for $gateway_field_name, $transaction.");	
		
	}

	function buildRequestXML($transaction){
		$this->xmlDoc = new DomDocument('1.0');
		$node = $this->xmlDoc->createElement('XML');
		
		$structure = $this->transactions[$transaction]['request'];
		
		$this->buildTransactionNodes($transaction, $structure, $node);
		$this->xmlDoc->appendChild($node);
		return $this->xmlDoc->saveXML();
	}
	
	
	function buildTransactionNodes($transaction, $structure, &$node){
		
		if (!is_array($structure)){ //this is a weird case that shouldn't ever happen. I'm just being... thorough. But, yeah: It's like... the base-1 case. 
			$this->appendNodeIfValue($structure, $transaction, $node);
		} else {
			foreach ($structure as $key => $value){
				if (!is_array($value)){
					//do not use $key. $key is meaningless in this case.			
					$this->appendNodeIfValue($value, $transaction, $node);
				} else {
					$keynode = $this->xmlDoc->createElement($key);
					$this->buildTransactionNodes($transaction, $value, $keynode);
					$node->appendChild($keynode);
				}
			}
		}
		//not actually returning anything. It's all side-effects. Because I suck like that. 
	}
	
	function appendNodeIfValue($value, $transaction, &$node){
		$nodevalue = $this->getValue($value, $transaction);
		if ($nodevalue !== '' && $nodevalue !== false){
			$temp = $this->xmlDoc->createElement($value, $nodevalue);
			$node->appendChild($temp);
		}
	}
	
	function sendRequestXML($transaction){
		$xml = $this->buildRequestXML($transaction);
		
	}

	/**
	 * Sends a name-value pair string to Payflow gateway
	 *
	 * @param $data Array: array of user input
	 * @param $payflow_data Array: array of necessary Payflow variables to
	 * 						include in string (i.e. Vendor, password)
	 */
	private function fnGlobalCollectProcessTransaction( $data, $payflow_data ) {
		global $wgOut, $wgDonationTestingMode, $wgPayflowGatewayUseHTTPProxy, $wgPayflowGatewayHTTPProxy, $wgGlobalCollectTimeout;

		// update contribution tracking
		$this->updateContributionTracking( $data, defined( 'OWA' ) );

		// create payflow query string, include string lengths
		$queryArray = array(
			'TRXTYPE' => $payflow_data['trxtype'],
			'TENDER'  => $payflow_data['tender'],
			'USER'  => $payflow_data['user'],
			'VENDOR' => $payflow_data['vendor'],
			'PARTNER' => $payflow_data['partner'],
			'PWD' => $payflow_data['password'],
			'ACCT'  => $data['card_num'],
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
		$paypalPostTo = isset ( $wgDonationTestingMode ) ? 'testingurl' : 'paypalurl';
		curl_setopt( $ch, CURLOPT_URL, $payflow_data[ $paypalPostTo ] );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_USERAGENT, $user_agent );
		curl_setopt( $ch, CURLOPT_HEADER, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $wgGlobalCollectTimeout );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $payflow_query );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST,  2 );
		curl_setopt( $ch, CURLOPT_FORBID_REUSE, true );
		curl_setopt( $ch, CURLOPT_POST, 1 );

		// set proxy settings if necessary
		if ( $wgPayflowGatewayUseHTTPProxy ) {
			curl_setopt( $ch, CURLOPT_HTTPPROXYTUNNEL, 1 );
			curl_setopt( $ch, CURLOPT_PROXY, $wgPayflowGatewayHTTPProxy );
		}

		// As suggested in the PayPal developer forum sample code, try more than once to get a response
		// in case there is a general network issue
		$i = 1;

		while ( $i++ <= 3 ) {
			self::log( $data[ 'order_id' ] . ' Preparing to send transaction to GlobalCollect' );
			$result = curl_exec( $ch );
			$headers = curl_getinfo( $ch );

			if ( $headers['http_code'] != 200 && $headers['http_code'] != 403 ) {
				self::log( $data[ 'order_id' ] . ' Failed sending transaction to GlobalCollect, retrying' );
				sleep( 1 );
			} elseif ( $headers['http_code'] == 200 || $headers['http_code'] == 403 ) {
				self::log( $data[ 'order_id' ] . ' Finished sending transaction to GlobalCollect' );
				break;
			}
		}

		if ( $headers['http_code'] != 200 ) {
			$wgOut->addHTML( '<h3>No response from credit card processor.  Please try again later!</h3><p>' );
			$when = time();
			self::log( $data[ 'order_id' ] . ' No response from credit card processor: ' . curl_error( $ch ) );
			curl_close( $ch );
			return;
		}

		curl_close( $ch );

		// get result string
		$result = strstr( $result, 'RESULT' );

		// parse string and display results to the user
		$this->fnPayflowGetResults( $data, $result );
	}






//_______________________________________________________________
//copied from  payflowpro_gateway/includes/payflowUser.inc

/**
 * Fetch and return the 'order_id' for a transaction
 * 
 * Since transactions to PayPal are initially matched internally on their end
 * with the 'order_id' field, but we don't actually care what the order id is,
 * we generate a sufficiently random number to avoid duplication. 
 * 
 * We go ahead and always generate a random order id becuse if PayPal detects
 * the same order_id more than once, it considers the request a duplicate, even
 * if the data is completely different.
 * 
 * @return int
 */
function getOrderId() {
	return $this->generateOrderId();
}

/**
 * Generate an internal order id
 * 
 * This is only used internally for tracking a user's 'session' with the credit
 * card form.  I mean 'session' in the sense of the moment a credit card page
 * is loaded for the first time (nothing posted to it - a discrete donation 
 * session) as opposed to the $_SESSION - as the $_SESSION id could potentially
 * not change between contribution attempts.
 */
function getInternalOrderId() {
	global $wgRequest;
	
	// is an order_id already set?
	//TODO: Change all these to look instead at $this->postdata... I think.
	$i_order_id = $wgRequest->getText( 'i_order_id', 0 );
	
	// if the form was not just posted OR there's no order_id set, generate one.
	if ( !$wgRequest->wasPosted() || !$i_order_id ) {
		$i_order_id = $this->generateOrderId();
	}

	return $i_order_id;
}

/**
 * Generate an order id
 */
function generateOrderId() {
	return (double) microtime() * 1000000 . mt_rand();
}
}