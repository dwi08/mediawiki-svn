<?php

$dir = dirname( __FILE__ ) . '/';
require_once( $dir . '../gateway_common/gateway.adapter.php' );

class GlobalCollectAdapter extends GatewayAdapter {
	//Contains the map of THEIR var names, to OURS.
	//I'd have gone the other way, but we'd run into 1:many pretty quick. 

	const logidentifier = 'globalcollect_gateway';

	function __construct( $data ) {
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

		$this->return_value_map = array(
			'OK' => true,
			'NOK' => false,
		);

		global $wgGlobalCollectURL, $wgGlobalCollectMerchantID;
		if ( $wgGatewayTest ) {
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
		 * 		'structure' contains the layout of that transaction.
		 * 		'defaults' contains default values for the leaf 'values' 
		 * 		I could put a 'type' in here, but I think we can assume that if 'structure' is multi-layer, we're XML.
		 * Array "leaves" in 'structure' will be assigned a value according to the var_map, and the posted data. 
		 * 	There should also be a mechanism for assigning defaults, but I'm not entirely sure what that would look like quite yet...
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
						'PARAMS' => array( )
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

	function getCommunicationType() {
		return 'xml'; //'xml' or 'namevalue'.
	}

	function do_transaction( $transaction ) {
		$this->currentTransaction( $transaction );
		if ( $this->getCommunicationType() === 'xml' ) {
			$xml = $this->buildRequestXML();
			$response = $this->curl_transaction( $xml );
			//put the response in a universal form, and return it. 
		}

		//TODO: Actually pull these from somewhere legit. 
		if ( $response['status'] === true ) {
			$response['message'] = "$transaction Transaction Successful!";
		} elseif ( $response['status'] === false ) {
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

	function parseXMLResponse( $rawResponse ) {
		//TODO: Something.
		$rawXML = $this->stripResponseHeaders( $rawResponse );
		if ( $rawXML === false ) {
			return false;
		} else {
			$rawXML = $this->formatXmlString( $rawXML );
		}
		$realXML = new DomDocument( '1.0' );
		self::log( "Here is the Raw XML: " . $rawXML );
		$realXML->loadXML( trim( $rawXML ) );

		$aok = true;

		//find the node specified by the transaction structure. 
		//TODO: Error handling, because: Ugh. 
		$result_structure = $this->transactions[$this->currentTransaction()]['result'];
		if ( is_array( $result_structure ) ) {
			foreach ( $result_structure as $key => $value ) { //should only be one. 
				foreach ( $realXML->getElementsByTagName( $key ) as $node ) {
					if ( $value === 'value' ) { //...stupid. But it's 'value' as opposed to 'attribute'
						if ( array_key_exists( $node->nodeValue, $this->return_value_map ) && $this->return_value_map[$node->nodeValue] !== true ) {
							$aok = false;
						}
					}
					if ( $value === 'attribute' ) {
						//TODO: Figure this out. This should mean the key name of one array up, which sounds painful.
					}
					if ( is_array( $value ) ) {
						//TODO: ...this is looking like a recursive deal, here. Again. So do that. 
					}
				}
			}
		}

		//TODO: Make this... you know: abstracty.
		if ( $aok === false ) {
			foreach ( $realXML->getElementsByTagName( 'ERROR' ) as $node ) {
				$errdata = '';
				foreach ( $node->childNodes as $childnode ) {
					$errdata .= "\n" . $childnode->nodeName . " " . $childnode->nodeValue;
//					if ($childnode->nodeName == "CODE"){
//						//Excellent place to deal with the particular codes if we want to.
//					}
				}
				self::log( "ON NOES! ERROR: $errdata" );
			}
		} else {
			self::log( "Response OK" );
		}

		//TODO: The bit where you strip all the response data that we might want to save, toss it into an array, and hand it back.
		//We need to be handing more back here than just the... success or failure. Things need to be processed! I mean, probably.


		return $aok;
	}

	function stripResponseHeaders( $rawResponse ) {
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

}
