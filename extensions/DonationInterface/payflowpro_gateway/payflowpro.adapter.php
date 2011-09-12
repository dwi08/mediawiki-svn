<?php

class PayflowProAdapter extends GatewayAdapter {
	//Contains the map of THEIR var names, to OURS.
	//I'd have gone the other way, but we'd run into 1:many pretty quick. 

	const GATEWAY_NAME = 'Payflow Pro';
	const IDENTIFIER = 'payflowpro_gateway';
	const COMMUNICATION_TYPE = 'namevalue';
	const GLOBAL_PREFIX = 'wgPayflowProGateway';

	/**
	 * stageData should alter the postdata array in all ways necessary in preparation for
	 * communication with the gateway. 
	 */
	function stageData() {
		
	}

	function defineAccountInfo() {
		$this->accountInfo = array(
			'MERCHANTID' => self::getGlobal( 'MerchantID' ),
			//'IPADDRESS' => '', //TODO: Not sure if this should be OUR ip, or the user's ip. Hurm. 
			'VERSION' => "1.0",
		);
	}

	function defineVarMap() {
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
	}

	function defineReturnValueMap() {
		$this->return_value_map = array(
			'OK' => true,
			'NOK' => false,
		);
	}

	function defineTransactions() {
		$this->transactions = array( );

		$this->transactions['INSERT_ORDERWITHPAYMENT'] = array(
			'request' => array(
				'REQUEST' => array(
					'ACTION',
					'META' => array(
						'MERCHANTID',
						// 'IPADDRESS',
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
		);

		$this->transactions['TEST_CONNECTION'] = array(
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
			)
		);
	}

	function do_transaction( $transaction ) {
		$this->currentTransaction( $transaction );
		if ( $this->getCommunicationType() === 'xml' ) {
			$xml = $this->buildRequestXML();
			$response = $this->curl_transaction( $xml );
			//put the response in a universal form, and return it. 
		}

		//TODO: Actually pull these status messages from somewhere legit. 
		if ( $response['result'] !== false ) {
			//now, parse the thing. 


			if ( $response['status'] === true ) {
				$response['message'] = "$transaction Transaction Successful!";
			} elseif ( $response['status'] === false ) {
				$response['message'] = "$transaction Transaction FAILED!";
			} else {
				$response['message'] = "$transaction Transaction... weird. I have no idea what happened there.";
			}
		}

		return $response;

		//speaking of universal form: 
		//$result['status'] = something I wish could be boiled down to a bool, but that's way too optimistic, I think.
		//$result['message'] = whatever we want to display back? 
		//$result['errors'][]['code'] = their error code
		//$result['errors'][]['value'] = Error message
		//$result['return'][$whatever] = values they pass back to us for whatever reason. We might... log it, or pieces of it, or something? 
	}

	/**
	 * Take the entire response string, and strip everything we don't care about.
	 * For instance: If it's XML, we only want correctly-formatted XML. Headers must be killed off. 
	 * return a string.
	 */
	function getTrimmedResponse( $rawResponse ) {
		
	}

	/**
	 * Parse the response to get the status. Not sure if this should return a bool, or something more... telling.
	 */
	function getResponseStatus( $response ) {
		
	}

	/**
	 * Parse the response to get the errors in a format we can log and otherwise deal with.
	 * return a key/value array of codes (if they exist) and messages. 
	 */
	function getResponseErrors( $response ) {
		
	}

	/**
	 * Harvest the data we need back from the gateway. 
	 * return a key/value array
	 */
	function getResponseData( $response ) {
		
	}

	/**
	 * Take the entire response string, and strip everything we don't care about.
	 * For instance: If it's XML, we only want correctly-formatted XML. Headers must be killed off. 
	 * return a string.
	 */
	function getFormattedResponse( $rawResponse ) {
		
	}

	/**
	 * Actually do... stuff. Here. 
	 * TODO: Better comment. 
	 * Process the entire response gott'd by the last four functions. 
	 */
	function processResponse( $response ) {
		
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
