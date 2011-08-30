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
		//TODO: Squish ALL the globals here, in the constructor. 
		//easier to abstract out that way. 
		global $wgGatewayTest;
		
		$this->postdata = $data;
		
		//TODO: Make a Thing in which we do things like this. 
		$this->postdata['amount'] = $this->postdata['amount'] * 100;
		
		
		$returnTitle = Title::newFromText( 'Donate-thanks/en' );
		$returnto = $returnTitle->getFullURL();
		$returnto = "http://www.katiehorn.com";
		
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
			'IPADDRESS' => 'user_ip', //TODO: Not sure if this should be OUR ip, or the user's ip. Hurm.
			
		);
		
		$this->return_value_map  = array(
			'OK' => true,
			'NOK' => false,
		);
		
		global $wgGlobalCollectURL, $wgGlobalCollectMerchantID;
		if ($wgGatewayTest){
			$this->url = $wgGlobalCollectURL;
		} else {
			$this->url = $wgGlobalCollectURL;
		}
		
		
		$this->accountInfo = array(
//			'PAYMENTPRODUCTID' => '3', //actually, these are almost certainly transaction-specific.
//			'HOSTEDINDICATOR' => '1',
			'MERCHANTID' => $wgGlobalCollectMerchantID,
			//'IPADDRESS' => '', //TODO: Not sure if this should be OUR ip, or the user's ip. Hurm. 
			'VERSION' => "1.0",
		);
		
		//oof. This is getting a little long and unwieldy. Maybe we should build it. Or maybe that sucks. I can't tell yet.
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
//							'IPADDRESS',
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
				'result' => array( //just like the rest: This is still in flux. But the idea here is that this is the sctucture you'd scan for. 
					'RESULT' => 'value'
				),
				'result_errors' => array(
					'ERROR' => array(
						'CODE' => 'value', //as opposed to "attribute", which would imply that it belongs to the parent...
						'MESSAGE' => 'value',
					)
				),
				'result_data' => array(
					'ROW' => array(
						//uhh... presumably we'd look for some Stuff in here. 
					)
				)
			),
			'TEST_CONNECTION' => array(
				'request' => array(
					'REQUEST' => array(
						'ACTION',
						'META' => array(
							'MERCHANTID',
//							'IPADDRESS',
							'VERSION'
						),
						'PARAMS' => array()
					)
				),
				'values' => array(
					'ACTION' => 'TEST_CONNECTION'
				),
				'result' => array( //just like the rest: This is still in flux. But the idea here is that this is the sctucture you'd scan for. 
					'RESULT' => 'value'
				),
				'result_errors' => array(
					'ERROR' => array(
						'CODE' => 'value', //as opposed to "attribute", which would imply that it belongs to the parent...
						'MESSAGE' => 'value',
					)
				),
				'result_data' => array(
					'ROW' => array(
						//uhh... presumably we'd look for some Stuff in here. 
					)
				)
			),
		);
	
	}
	
	function getCommunicationType(){
		return 'xml'; //'xml' or 'namevalue'.
	}
	
	
	function getValue($gateway_field_name, $transaction = ''){
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

	function buildRequestXML(){
		$this->xmlDoc = new DomDocument('1.0');
		$node = $this->xmlDoc->createElement('XML');
		
		$structure = $this->transactions[$this->currentTransaction()]['request'];
		
		$this->buildTransactionNodes($structure, $node);
		$this->xmlDoc->appendChild($node);
		return $this->xmlDoc->saveXML();
	}
	
	
	function buildTransactionNodes($structure, &$node){
		$transaction = $this->currentTransaction();
		
		if (!is_array($structure)){ //this is a weird case that shouldn't ever happen. I'm just being... thorough. But, yeah: It's like... the base-1 case. 
			$this->appendNodeIfValue($structure, $node);
		} else {
			foreach ($structure as $key => $value){
				if (!is_array($value)){
					//do not use $key. $key is meaningless in this case.			
					$this->appendNodeIfValue($value, $node);
				} else {
					$keynode = $this->xmlDoc->createElement($key);
					$this->buildTransactionNodes( $value, $keynode);
					$node->appendChild($keynode);
				}
			}
		}
		//not actually returning anything. It's all side-effects. Because I suck like that. 
	}
	
	function appendNodeIfValue($value, &$node){
		$nodevalue = $this->getValue($value, $this->currentTransaction());
		if ($nodevalue !== '' && $nodevalue !== false){
			$temp = $this->xmlDoc->createElement($value, $nodevalue);
			$node->appendChild($temp);
		}
	}
	
	function do_transaction($transaction){
		$this->currentTransaction($transaction);
		if ($this->getCommunicationType() === 'xml'){
			$xml = $this->buildRequestXML();
			$response = $this->curl_transaction($xml);
			//put the response in a universal form, and return it. 
		}
		
		//TODO: Actually pull these from somewhere legit. 
		if ($response['status'] === true){
			$response['message'] = "$transaction Transaction Successful!";
		} elseif ($response['status'] === false){
			$response['message'] = "$transaction Transaction FAILED!";
		} else {
			$response['message'] = "$transaction Transaction... weird. I have no idea what happened there.";
		}
		
		return $response;
		
		//speaking of universal form: 
		//$result['status'] = something I wish could be boiled down to a bool, but that's way too optimistic, I think.
		//$result['message'] = whatever we want to display back? 
		//$result['errors'][]['code'] = their error code
		//$result['errors'][]['value'] = Error message
		//$result['return'][$whatever] = values they pass back to us for whatever reason. We might... log it, or pieces of it, or something? 
	}
	
	function getCurlBaseOpts(){
		//I chose to return this as a function so it's easy to override. 
		//TODO: probably this for all the junk I currently have stashed in the constructor.
		//...maybe. 
		global $wgGlobalCollectTimeout, $wgPayflowGatewayUseHTTPProxy;
		$opts = array(
			CURLOPT_URL => $this->url,
			//CURLOPT_USERAGENT => Http::userAgent(),
			CURLOPT_HEADER => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_TIMEOUT => $wgGlobalCollectTimeout,
			//CURLOPT_FOLLOWLOCATION => 0,
			//CURLOPT_SSL_VERIFYPEER => 0,
			//CURLOPT_SSL_VERIFYHOST => 2,
			//CURLOPT_FORBID_REUSE => true,
			CURLOPT_POST => 1,
		);

		// set proxy settings if necessary
		if ( $wgPayflowGatewayUseHTTPProxy ) {
			$opts[CURLOPT_HTTPPROXYTUNNEL] = 1;
			$opts[CURLOPT_PROXY] = $wgPayflowGatewayHTTPProxy;
		}
		return $opts;
	}
	
	
	function getCurlBaseHeaders(){
		$headers = array (
			'Content-Type: text/' . $this->getCommunicationType() . '; charset=utf-8',
		//	'X-VPS-Client-Timeout: 45',
		//	'X-VPS-Request-ID:' . $this->postdatadefaults['order_id'],
		);
		return $headers;
	}
	
	private function currentTransaction($transaction = ''){ //get&set in one!
		static $current_transaction;
		if ($transaction != ''){
			$current_transaction = $transaction;
		}
		if (!isset($current_transaction)){
			return false;
		}
		return $current_transaction;
	}

	/**
	 * Sends a name-value pair string to Payflow gateway
	 *
	 * @param $data String: The exact thing we want to send.
	 */
	private function curl_transaction( $data ) {
		global $wgOut; //TODO: Uhm, this shouldn't touch the view. Something further upstream should decide what to do with this. 

		// TODO: This, but way before we get here. 
		//$this->updateContributionTracking( $data, defined( 'OWA' ) );

		// assign header data necessary for the curl_setopt() function
		
		$ch = curl_init();
		
		$headers = $this->getCurlBaseHeaders();
		$headers[] = 'Content-Length: ' . strlen( $data );
		
		self::log("Sending Data: " . $data);
		
		$curl_opts = $this->getCurlBaseOpts();
		$curl_opts[CURLOPT_HTTPHEADER] = $headers;
		$curl_opts[CURLOPT_POSTFIELDS] = $data;
		
		foreach ($curl_opts as $option => $value){
			curl_setopt($ch, $option, $value);
		}

		// As suggested in the PayPal developer forum sample code, try more than once to get a response
		// in case there is a general network issue
		$i = 1;
		
		$return = array();

		while ( $i++ <= 3 ) {
			self::log( $this->postdatadefaults['order_id'] . ' Preparing to send transaction to GlobalCollect' );
			$return['result'] = curl_exec( $ch );
			$return['headers'] = curl_getinfo( $ch );

			if ( $return['headers']['http_code'] != 200 && $return['headers']['http_code'] != 403 ) {
				self::log( $this->postdatadefaults['order_id'] . ' Failed sending transaction to GlobalCollect, retrying' );
				sleep( 1 );
			} elseif ( $return['headers']['http_code'] == 200 || $return['headers']['http_code'] == 403 ) {
				self::log( $this->postdatadefaults['order_id'] . ' Finished sending transaction to GlobalCollect' );
				break;
			}
		}

		if ( $return['headers']['http_code'] != 200 ) {
			$return['result'] = false;
			//TODO: i18n here! 
			$return['message'] = 'No response from credit card processor.  Please try again later!';
			$when = time();
			self::log( $this->postdatadefaults['order_id'] . ' No response from credit card processor: ' . curl_error( $ch ) );
			curl_close( $ch );
			return $return;
		}

		curl_close( $ch );
		self::log("Results: " . print_r($return['result'], true));		
		
//		if ($this->getCommunicationType() === 'namevalue'){
//			$return['result'] = strstr( $return['result'], 'RESULT' );
//			//TODO: Finish this for namevalue. 
//		}
		if ($this->getCommunicationType() === 'xml'){
			//$return['result'] = $this->stripResponseHeaders($return['result']);
			$return['status'] = $this->parseXMLResponse($return['result']);
		}
		
		return $return;

		// parse string and display results to the user
		//TODO: NO NO NO. NO DISPLAY HERE. 
		//$this->fnPayflowGetResults( $data, $return['result'] );
	}
	
	function parseXMLResponse($rawResponse){
		//TODO: Something.
		$rawXML = $this->stripResponseHeaders($rawResponse);
		if ($rawXML === false){
			return false;
		}
		$realXML = new DomDocument('1.0');
		self::log("Here is the Raw XML: " . $rawXML);
		$realXML->loadXML(trim($rawXML));

		$aok = true;
		
		//find the node specified by the transaction structure. 
		//TODO: Error handling, because: Ugh. 
		$result_structure = $this->transactions[$this->currentTransaction()]['result']; 
		if(is_array($result_structure)){
			foreach ($result_structure as $key=>$value){ //should only be one. 
				foreach($realXML->getElementsByTagName($key) as $node) {
					if ($value === 'value') { //...stupid. But it's 'value' as opposed to 'attribute'
						if (array_key_exists($node->nodeValue, $this->return_value_map) && $this->return_value_map[$node->nodeValue] !==  true){
							$aok = false;
						}						
					}
					if ($value === 'attribute') {
						//TODO: Figure this out. This should mean the key name of one array up, which sounds painful.
					}
					if (is_array($value)){
						//TODO: ...this is looking like a recursive deal, here. Again. So do that. 
					}
				}
			}
		}
		
		//TODO: Make this... you know: abstracty.
		if ($aok === false){
			foreach($realXML->getElementsByTagName('ERROR') as $node) {
				$errdata = '';
				foreach($node->childNodes as $childnode){
					$errdata .= "\n" . $childnode->nodeName . " " . $childnode->nodeValue;
//					if ($childnode->nodeName == "CODE"){
//						//Excellent place to deal with the particular codes if we want to.
//					}
				}
				self::log("ON NOES! ERROR: $errdata");
			}
		} else {
			self::log("Response OK");
		}
		
		//TODO: The bit where you strip all the response data that we might want to save, toss it into an array, and hand it back.
		//We need to be handing more back here than just the... success or failure. Things need to be processed! I mean, probably.
		

		return $aok;
	}

	function stripResponseHeaders($rawResponse){
		$xmlStart = strpos($rawResponse, '<?xml');
		if ($xmlStart == false){ //I totally saw this happen one time. No XML, just <RESPONSE>...
			$xmlStart = strpos($rawResponse, '<RESPONSE');
		}
		if ($xmlStart == false){ //Still false. Your Head Asplode.
			self::log("Wow, that was so messed up I couldn't even parse the response, so here's the thing in its entirety:\n" . $rawResponse, true);
			return false;
		}
		$justXML = substr($rawResponse, $xmlStart);
		return $justXML;
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
		return (double) microtime() * 1000000 . mt_rand(1000, 9999);
	}
}
