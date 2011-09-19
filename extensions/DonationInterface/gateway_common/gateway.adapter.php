<?php

interface GatewayType {
	//all the particulars of the child classes. Aaaaall.

	/**
	 * Take the entire response string, and strip everything we don't care about.
	 * For instance: If it's XML, we only want correctly-formatted XML. Headers must be killed off. 
	 * return a string.
	 */
	function getFormattedResponse( $rawResponse );

	/**
	 * Parse the response to get the status. Not sure if this should return a bool, or something more... telling.
	 */
	function getResponseStatus( $response );

	/**
	 * Parse the response to get the errors in a format we can log and otherwise deal with.
	 * return a key/value array of codes (if they exist) and messages. 
	 */
	function getResponseErrors( $response );

	/**
	 * Harvest the data we need back from the gateway. 
	 * return a key/value array
	 */
	function getResponseData( $response );

	/**
	 * Actually do... stuff. Here. 
	 * TODO: Better comment. 
	 * Process the entire response gott'd by the last four functions. 
	 */
	function processResponse( $response );

	/**
	 * Anything we need to do to the data coming in, before we send it off. 
	 */
	function stageData();

	/**
	 * defineTransactions will define the $transactions array. 
	 * The array will contain everything we need to know about the request structure for all the transactions we care about, 
	 * for the current gateway. 
	 * First array key: Some way for us to id the transaction. Doesn't actually have to be the gateway's name for it, but I'm going with that until I have a reason not to. 
	 * Second array key: 
	 * 		'request' contains the structure of that request. Leaves in the array tree will eventually be mapped to actual values of ours, 
	 * 		according to the precidence established in the getValue function. 
	 * 		'values' contains default values for the transaction. Things that are typically not overridden should go here. 
	 */
	function defineTransactions();

	/**
	 * defineVarMap needs to set up the $var_map array. 
	 * Keys = the name (or node name) value in the gateway transaction
	 * Values = the mediawiki field name for the corresponding piece of data. 
	 */
	function defineVarMap();

	/**
	 * defineAccountInfo needs to set up the $accountInfo array. 
	 * Keys = the name (or node name) value in the gateway transaction
	 * Values = The actual values for those keys. Probably have to access a global or two. (use getGlobal()!) 
	 */
	function defineAccountInfo();

	/**
	 * defineReturnValueMap sets up the $return_value_map array.
	 * Keys = The different constants that may be contained as values in the gateway's response.
	 * Values = what that string constant means to mediawiki. 
	 */
	function defineReturnValueMap();
}

abstract class GatewayAdapter implements GatewayType {

	//Contains the map of THEIR var names, to OURS.
	//I'd have gone the other way, but we'd run into 1:many pretty quick. 
	protected $var_map;
	protected $accountInfo;
	protected $url;
	protected $transactions;
	protected $return_value_map;
	protected $postdata;
	protected $postdatadefaults;
	protected $xmlDoc;
	protected $dataObj;

	//ALL OF THESE need to be redefined in the children. Much voodoo depends on the accuracy of these constants. 
	const GATEWAY_NAME = 'Donation Gateway';
	const IDENTIFIER = 'donation';
	const COMMUNICATION_TYPE = 'xml'; //this needs to be either 'xml' or 'namevalue'
	const GLOBAL_PREFIX = 'wgDonationGateway'; //...for example. 

	function __construct() {
		global $wgDonationInterfaceTest; //this is so the forms can see it. 
		//TODO: Alter the forms so they don't need the global?
		if ( !self::getGlobal( 'Test' ) ) {
			$this->url = self::getGlobal( 'URL' );
			$wgDonationInterfaceTest = false;
		} else {
			$this->url = self::getGlobal( 'TestingURL' );
			$wgDonationInterfaceTest = true;
		}

		$this->dataObj = new DonationData( get_called_class(), $wgDonationInterfaceTest );

		$this->postdata = $this->dataObj->getData();
		//TODO: Fix this a bit. 
		$this->posted = $this->dataObj->wasPosted();

		$this->setPostDefaults();
		$this->defineTransactions();
		$this->defineVarMap();
		$this->defineAccountInfo();
		$this->defineReturnValueMap();

		$this->displaydata = $this->postdata;
		$this->stageData();
	}

	/**
	 * Override this in children if you want different defaults. 
	 */
	function setPostDefaults() {
		$returnTitle = Title::newFromText( 'Special:GlobalCollectGatewayResult' );
		$returnto = $returnTitle->getFullURL();

		$this->postdatadefaults = array(
			'order_id' => '112358' . rand(),
			'amount' => '11.38',
			'currency' => 'USD',
			'language' => 'en',
			'country' => 'US',
			'returnto' => $returnto,
			'user_ip' => ( self::getGlobal( 'Test' ) ) ? '12.12.12.12' : wfGetIP(), // current user's IP address
			'card_type' => 'visa',
		);
	}

	function getThankYouPage() {
		global $wgLang;
		$language = $wgLang->getCode();
		$page = self::getGlobal( "ThankYouPage" ) . "/$language";
		$returnTitle = Title::newFromText( $page );
		$returnto = $returnTitle->getFullURL();
		return $returnto;
	}

	function getFailPage() {
		global $wgLang;
		$language = $wgLang->getCode();
		$page = self::getGlobal( "FailPage" ) . "/$language";
		$returnTitle = Title::newFromText( $page );
		$returnto = $returnTitle->getFullURL();
		return $returnto;
	}

	function checkTokens() {
		return $this->dataObj->checkTokens();
	}

	function getData() {
		return $this->postdata;
	}

	function getDisplayData() {
		return $this->displaydata;
	}

	function isCache() {
		return $this->dataObj->isCache();
	}

	static function getGlobal( $varname ) {
		static $gotten = array( ); //cache. 
		$globalname = self::getGlobalPrefix() . $varname;
		if ( !array_key_exists( $globalname, $gotten ) ) {
			global $$globalname;
			$gotten[$globalname] = $$globalname;
		}
		return $gotten[$globalname];
	}

	function getValue( $gateway_field_name, $token = false ) {
		if ( empty( $this->transactions ) ) {
			//TODO: These dies should all just throw fatal errors instead. 
			die( 'Transactions structure is empty! Aborting.' );
		}
		//How do we determine the value of a field asked for in a particular transaction? 
		$transaction = $this->currentTransaction();

		//If there's a hard-coded value in the transaction definition, use that.
		if ( !empty( $transaction ) ) {
			if ( array_key_exists( $transaction, $this->transactions ) && is_array( $this->transactions[$transaction] ) &&
				array_key_exists( 'values', $this->transactions[$transaction] ) &&
				array_key_exists( $gateway_field_name, $this->transactions[$transaction]['values'] ) ) {
				return $this->transactions[$transaction]['values'][$gateway_field_name];
			}
		}

		//if it's account info, use that.
		//$this->accountInfo;
		if ( array_key_exists( $gateway_field_name, $this->accountInfo ) ) {
			return $this->accountInfo[$gateway_field_name];
		}


		//If there's a value in the post data (name-translated by the var_map), use that.
		if ( array_key_exists( $gateway_field_name, $this->var_map ) ) {
			if ( $token === true ) { //we just want the field name to use, so short-circuit all that mess. 
				return '@' . $this->var_map[$gateway_field_name];
			}
			if ( array_key_exists( $this->var_map[$gateway_field_name], $this->postdata ) &&
				$this->postdata[$this->var_map[$gateway_field_name]] !== '' ) {
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
		die( "getValue found NOTHING for $gateway_field_name, $transaction." );
	}

	function buildRequestXML() {
		$this->xmlDoc = new DomDocument( '1.0' );
		$node = $this->xmlDoc->createElement( 'XML' );

		$structure = $this->transactions[$this->currentTransaction()]['request'];

		$this->buildTransactionNodes( $structure, $node );
		$this->xmlDoc->appendChild( $node );
		return $this->xmlDoc->saveXML();
	}

	function buildTransactionNodes( $structure, &$node, $js = false ) {
		$transaction = $this->currentTransaction();

		if ( !is_array( $structure ) ) { //this is a weird case that shouldn't ever happen. I'm just being... thorough. But, yeah: It's like... the base-1 case. 
			$this->appendNodeIfValue( $structure, $node, $js );
		} else {
			foreach ( $structure as $key => $value ) {
				if ( !is_array( $value ) ) {
					//do not use $key. $key is meaningless in this case.			
					$this->appendNodeIfValue( $value, $node, $js );
				} else {
					$keynode = $this->xmlDoc->createElement( $key );
					$this->buildTransactionNodes( $value, $keynode, $js );
					$node->appendChild( $keynode );
				}
			}
		}
		//not actually returning anything. It's all side-effects. Because I suck like that. 
	}

	function appendNodeIfValue( $value, &$node, $js = false ) {
		$nodevalue = $this->getValue( $value, $js );
		if ( $nodevalue !== '' && $nodevalue !== false ) {
			$temp = $this->xmlDoc->createElement( $value, $nodevalue );
			$node->appendChild( $temp );
		}
	}

	//TODO: You can actually take this out if we never ever want to use ajax for a gateway. 
	function buildTransactionFormat( $transaction ) {
		$this->currentTransaction( $transaction );
		$this->xmlDoc = new DomDocument( '1.0' );
		$node = $this->xmlDoc->createElement( 'XML' );

		$structure = $this->transactions[$this->currentTransaction()]['request'];

		$this->buildTransactionNodes( $structure, $node, true );
		$this->xmlDoc->appendChild( $node );
		$xml = $this->xmlDoc->saveXML();
		$xmlStart = strpos( $xml, "<XML>" );
		self::log( "XML START" . $xmlStart );
		$xml = substr( $xml, $xmlStart );
		self::log( "XML stubby thing..." . $xml );

		return $xml;
	}

	function do_transaction( $transaction ) {
		$this->currentTransaction( $transaction );
		//update the contribution tracking data
		$this->dataObj->updateContributionTracking( defined( 'OWA' ) );
		if ( $this->getCommunicationType() === 'xml' ) {
			$this->getStopwatch( "buildRequestXML" );
			$xml = $this->buildRequestXML();
			$this->saveCommunicationStats( "buildRequestXML", $transaction );
			$returned = $this->curl_transaction( $xml );
			//put the response in a universal form, and return it. 
		}

		if ( $this->getCommunicationType() === 'namevalue' ) {
			$namevalue = $this->postdata;
			$this->getStopwatch( __FUNCTION__ );
			$returned = $this->curl_transaction( $namevalue );
			$this->saveCommunicationStats();
			//put the response in a universal form, and return it. 
		}

		self::log( "RETURNED FROM CURL:" . print_r( $returned, true ) );
		if ( $returned['result'] === false ) { //couldn't make contact. Bail.
			return $returned;
		}

		//get the status of the response
		$formatted = $this->getFormattedResponse( $returned['result'] );
		$returned['status'] = $this->getResponseStatus( $formatted );

		//get errors
		$returned['errors'] = $this->getResponseErrors( $formatted );

		//if we're still okay (hey, even if we're not), get relevent dataz.
		$returned['data'] = $this->getResponseData( $formatted );

		$this->processResponse( $returned );

		//TODO: Actually pull these from somewhere legit. 
		if ( $returned['status'] === true ) {
			$returned['message'] = "$transaction Transaction Successful!";
		} elseif ( $returned['status'] === false ) {
			$returned['message'] = "$transaction Transaction FAILED!";
		} else {
			$returned['message'] = "$transaction Transaction... weird. I have no idea what happened there.";
		}

		return $returned;

		//speaking of universal form: 
		//$result['status'] = something I wish could be boiled down to a bool, but that's way too optimistic, I think.
		//$result['message'] = whatever we want to display back? 
		//$result['errors']['code']['message'] = 
		//$result['data'][$whatever] = values they pass back to us for whatever reason. We might... log it, or pieces of it, or something? 
	}

	function getCurlBaseOpts() {
		//I chose to return this as a function so it's easy to override. 
		//TODO: probably this for all the junk I currently have stashed in the constructor.
		//...maybe. 
		$opts = array(
			CURLOPT_URL => $this->url,
			CURLOPT_USERAGENT => Http::userAgent(),
			CURLOPT_HEADER => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_TIMEOUT => self::getGlobal( 'Timeout' ),
			CURLOPT_FOLLOWLOCATION => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_FORBID_REUSE => true,
			CURLOPT_POST => 1,
		);

		// set proxy settings if necessary
		if ( self::getGlobal( 'UseHTTPProxy' ) ) {
			$opts[CURLOPT_HTTPPROXYTUNNEL] = 1;
			$opts[CURLOPT_PROXY] = self::getGlobal( 'UseHTTPProxy' );
		}
		return $opts;
	}

	function getCurlBaseHeaders() {
		$headers = array(
			'Content-Type: text/' . $this->getCommunicationType() . '; charset=utf-8',
			'X-VPS-Client-Timeout: 45',
			'X-VPS-Request-ID:' . $this->postdatadefaults['order_id'],
		);
		return $headers;
	}

	protected function currentTransaction( $transaction = '' ) { //get&set in one!
		static $current_transaction;
		if ( $transaction != '' ) {
			$current_transaction = $transaction;
		}
		if ( !isset( $current_transaction ) ) {
			return false;
		}
		return $current_transaction;
	}

	/**
	 * Sends a name-value pair string to Payflow gateway
	 *
	 * @param $data String: The exact thing we want to send.
	 */
	protected function curl_transaction( $data ) {
		// assign header data necessary for the curl_setopt() function
		$this->getStopwatch( __FUNCTION__, true );

		$ch = curl_init();

		$headers = $this->getCurlBaseHeaders();
		$headers[] = 'Content-Length: ' . strlen( $data );

		self::log( "Sending Data: " . $this->formatXmlString( $data ) );

		$curl_opts = $this->getCurlBaseOpts();
		$curl_opts[CURLOPT_HTTPHEADER] = $headers;
		$curl_opts[CURLOPT_POSTFIELDS] = $data;

		foreach ( $curl_opts as $option => $value ) {
			curl_setopt( $ch, $option, $value );
		}

		// As suggested in the PayPal developer forum sample code, try more than once to get a response
		// in case there is a general network issue
		$i = 1;

		$return = array( );

		while ( $i++ <= 3 ) {
			self::log( $this->postdatadefaults['order_id'] . ' Preparing to send transaction to ' . self::getGatewayName() );
			$return['result'] = curl_exec( $ch );
			$return['headers'] = curl_getinfo( $ch );

			if ( $return['headers']['http_code'] != 200 && $return['headers']['http_code'] != 403 ) {
				self::log( $this->postdatadefaults['order_id'] . ' Failed sending transaction to ' . self::getGatewayName() . ', retrying' );
				sleep( 1 );
			} elseif ( $return['headers']['http_code'] == 200 || $return['headers']['http_code'] == 403 ) {
				self::log( $this->postdatadefaults['order_id'] . ' Finished sending transaction to ' . self::getGatewayName() );
				break;
			}
		}

		$this->saveCommunicationStats( __FUNCTION__, $this->currentTransaction(), "Request:" . print_r( $data, true ) . "\nResponse" . print_r( $return, true ) );

		if ( $return['headers']['http_code'] != 200 ) {
			$return['result'] = false;
			//TODO: i18n here! 
			//TODO: But also, fire off some kind of "No response from the gateway" thing to somebody so we know right away. 
			$return['message'] = 'No response from ' . self::getGatewayName() . '.  Please try again later!';
			$when = time();
			self::log( $this->postdatadefaults['order_id'] . ' No response from ' . self::getGatewayName() . ': ' . curl_error( $ch ) );
			curl_close( $ch );
			return $return;
		}

		curl_close( $ch );

		return $return;
	}

	function stripXMLResponseHeaders( $rawResponse ) {
		$xmlStart = strpos( $rawResponse, '<?xml' );
		if ( $xmlStart == false ) { //I totally saw this happen one time. No XML, just <RESPONSE>...
			$xmlStart = strpos( $rawResponse, '<RESPONSE' );
		}
		if ( $xmlStart == false ) { //Still false. Your Head Asplode.
			self::log( "Wow, that was so messed up I couldn't even parse the response, so here's the thing in its entirety:\n" . $rawResponse );
			return false;
		}
		$justXML = substr( $rawResponse, $xmlStart );
		return $justXML;
	}

	public static function log( $msg, $log_level=LOG_INFO ) {
		$identifier = self::getIdentifier() . "_gateway";

		// if we're not using the syslog facility, use wfDebugLog
		if ( !self::getGlobal( 'UseSyslog' ) ) {
			wfDebugLog( $identifier, $msg );
			return;
		}

		// otherwise, use syslogging
		openlog( $identifier, LOG_ODELAY, LOG_SYSLOG );
		syslog( $log_level, $msg );
		closelog();
	}

	//To avoid reinventing the wheel: taken from http://recursive-design.com/blog/2007/04/05/format-xml-with-php/
	function formatXmlString( $xml ) {
		// add marker linefeeds to aid the pretty-tokeniser (adds a linefeed between all tag-end boundaries)
		$xml = preg_replace( '/(>)(<)(\/*)/', "$1\n$2$3", $xml );

		// now indent the tags
		$token = strtok( $xml, "\n" );
		$result = ''; // holds formatted version as it is built
		$pad = 0; // initial indent
		$matches = array( ); // returns from preg_matches()
		// scan each line and adjust indent based on opening/closing tags
		while ( $token !== false ) :

			// test for the various tag states
			// 1. open and closing tags on same line - no change
			if ( preg_match( '/.+<\/\w[^>]*>$/', $token, $matches ) ) :
				$indent = 0;
			// 2. closing tag - outdent now
			elseif ( preg_match( '/^<\/\w/', $token, $matches ) ) :
				$pad--;
			// 3. opening tag - don't pad this one, only subsequent tags
			elseif ( preg_match( '/^<\w[^>]*[^\/]>.*$/', $token, $matches ) ) :
				$indent = 1;
			// 4. no indentation needed
			else :
				$indent = 0;
			endif;

			// pad the line with the required number of leading spaces
			$line = str_pad( $token, strlen( $token ) + $pad, ' ', STR_PAD_LEFT );
			$result .= $line . "\n"; // add to the cumulative result, with linefeed
			$token = strtok( "\n" ); // get the next token
			$pad += $indent; // update the pad size for subsequent lines    
		endwhile;

		return $result;
	}

	static function getCommunicationType() {
		$c = get_called_class();
		return $c::COMMUNICATION_TYPE;
	}

	static function getGatewayName() {
		$c = get_called_class();
		return $c::GATEWAY_NAME;
	}

	static function getGlobalPrefix() {
		$c = get_called_class();
		return $c::GLOBAL_PREFIX;
	}

	static function getIdentifier() {
		$c = get_called_class();
		return $c::IDENTIFIER;
	}

	public function getStopwatch( $string, $reset = false ) {
		static $start = null;
		$now = microtime( true );

		if ( $start == null || $reset === true ) {
			$start = $now;
		}
		$clock = round( $now - $start, 4 );
		self::log( "\nClock at $string: $clock ($now)" );
		return $clock;
	}

	/**
	 *
	 * @param type $function
	 * @param type $additional
	 * @param type $vars 
	 */
	function saveCommunicationStats( $function = '', $additional = '', $vars = '' ) { //easier than looking at logs...
		$params = array( );
		if ( self::getGlobal( 'SaveCommStats' ) ) { //TODO: I should do this for real at some point. 
			$db = ContributionTrackingProcessor::contributionTrackingConnection();
			$params['contribution_id'] = $this->dataObj->getVal( 'contribution_tracking_id' );
			$params['ts'] = $db->timestamp();
			$params['duration'] = $this->getStopwatch( __FUNCTION__ );
			$params['gateway'] = self::getGatewayName();
			$params['function'] = $function;
			$params['vars'] = $vars;
			$params['additional'] = $additional;

			//can we check to see if the table exists? Bah, I should almost certianly do this Fer Reals. 

			$db->insert( 'communication_stats', $params );
		}
	}

	function xmlChildrenToArray( $xml, $nodename ) {
		$data = array( );
		foreach ( $xml->getElementsByTagName( $nodename ) as $node ) {
			foreach ( $node->childNodes as $childnode ) {
				if ( trim( $childnode->nodeValue ) != '' ) {
					$data[$childnode->nodeName] = $childnode->nodeValue;
				}
			}
		}
		return $data;
	}

	/**
	 * DO NOT DEFINE OVERLAPPING RANGES!
	 * TODO: Make sure it won't let you add overlapping ranges. That would probably necessitate the sort moving to here, too.
	 * @param type $transaction
	 * @param type $key
	 * @param type $action
	 * @param type $lower
	 * @param type $upper 
	 */
	function addCodeRange( $transaction, $key, $action, $lower, $upper = null ) {
		if ( $upper === null ) {
			$this->return_value_map[$transaction][$key][$lower] = $action;
		} else {
			$this->return_value_map[$transaction][$key][$upper] = array( 'action' => $action, 'lower' => $lower );
		}
	}

	function findCodeAction( $transaction, $key, $code ) {
		$this->getStopwatch( __FUNCTION__, true );
		if ( !array_key_exists( $transaction, $this->return_value_map ) || !array_key_exists( $key, $this->return_value_map[$transaction] ) ) {
			return null;
		}
		if ( !is_array( $this->return_value_map[$transaction][$key] ) ) {
			return null;
		}
		//sort the array so we can do this quickly. 
		ksort( $this->return_value_map[$transaction][$key], SORT_NUMERIC );

		$ranges = $this->return_value_map[$transaction][$key];
		//so, you have a code, which is a number. You also have a numerically sorted array.
		//loop through until you find an upper >= your code.
		//make sure it's in the range, and return the action. 
		foreach ( $ranges as $upper => $val ) {
			if ( $upper >= $code ) { //you've arrived. It's either here or it's nowhere.
				if ( is_array( $val ) ) {
					if ( $val['lower'] <= $code ) {
						$this->saveCommunicationStats( __FUNCTION__, $transaction, "code = $code" );
						return $val['action'];
					} else {
						return null;
					}
				} else {
					if ( $upper === $code ) {
						$this->saveCommunicationStats( __FUNCTION__, $transaction, "code = $code" );
						return $val;
					} else {
						return null;
					}
				}
			}
		}
		//if we walk straight off the end...
		return null;
	}

	function addDonorDataToSession() {
		$this->dataObj->addDonorDataToSession();
	}

	function unsetAllGatewaySessionData() {
		$this->dataObj->unsetAllDDSessionData();
	}

}
