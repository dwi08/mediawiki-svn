<?php

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install the DontaionInterface extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/DonationInterface/donationinterface.php" );
EOT;
	exit( 1 );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Donation Interface',
	'author' => 'Katie Horn',
	'version' => '1.0.0',
	'descriptionmsg' => 'donationinterface-desc',
	'url' => 'http://www.mediawiki.org/wiki/Extension:DonationInterface',
);

//This is going to be a little funky. 
//Override this in LocalSettings.php BEFORE you include this file, if you want 
//to disable gateways.
if ( !isset( $wgDonationInterfaceEnabledGateways ) ) {
	$wgDonationInterfaceEnabledGateways = array(
		'paypal',
		'payflowpro',
		'globalcollect'
	);
}

$donationinterface_dir = dirname( __FILE__ ) . '/';

require_once( $donationinterface_dir . 'donate_interface/donate_interface.php' );

foreach ( $wgDonationInterfaceEnabledGateways as $gateway ) {
	//include 'em
	require_once( $donationinterface_dir . $gateway . '_gateway/' . $gateway . '_gateway.php' );
}

//load all possible form classes
$wgAutoloadClasses['Gateway_Form'] = $donationinterface_dir . 'gateway_forms/Form.php';
$wgAutoloadClasses['Gateway_Form_OneStepTwoColumn'] = $donationinterface_dir . 'gateway_forms/OneStepTwoColumn.php';
$wgAutoloadClasses['Gateway_Form_TwoStepTwoColumn'] = $donationinterface_dir . 'gateway_forms/TwoStepTwoColumn.php';
$wgAutoloadClasses['Gateway_Form_TwoColumnPayPal'] = $donationinterface_dir . 'gateway_forms/TwoColumnPayPal.php';
$wgAutoloadClasses['Gateway_Form_TwoColumnLetter'] = $donationinterface_dir . 'gateway_forms/TwoColumnLetter.php';
$wgAutoloadClasses['Gateway_Form_TwoColumnLetter2'] = $donationinterface_dir . 'gateway_forms/TwoColumnLetter2.php';
$wgAutoloadClasses['Gateway_Form_TwoColumnLetter3'] = $donationinterface_dir . 'gateway_forms/TwoColumnLetter3.php';
$wgAutoloadClasses['Gateway_Form_TwoColumnLetter4'] = $donationinterface_dir . 'gateway_forms/TwoColumnLetter4.php';
$wgAutoloadClasses['Gateway_Form_TwoColumnLetter5'] = $donationinterface_dir . 'gateway_forms/TwoColumnLetter5.php';
$wgAutoloadClasses['Gateway_Form_TwoColumnLetter6'] = $donationinterface_dir . 'gateway_forms/TwoColumnLetter6.php';
$wgAutoloadClasses['Gateway_Form_TwoColumnLetter7'] = $donationinterface_dir . 'gateway_forms/TwoColumnLetter7.php';
$wgAutoloadClasses['Gateway_Form_TwoStepTwoColumnLetter'] = $donationinterface_dir . 'gateway_forms/TwoStepTwoColumnLetter.php';
$wgAutoloadClasses['Gateway_Form_TwoStepTwoColumnLetterCA'] = $donationinterface_dir . 'gateway_forms/TwoStepTwoColumnLetterCA.php';
$wgAutoloadClasses['Gateway_Form_TwoStepTwoColumnLetter2'] = $donationinterface_dir . 'gateway_forms/TwoStepTwoColumnLetter2.php';
$wgAutoloadClasses['Gateway_Form_TwoStepTwoColumnLetter3'] = $donationinterface_dir . 'gateway_forms/TwoStepTwoColumnLetter3.php';
$wgAutoloadClasses['Gateway_Form_TwoStepTwoColumnPremium'] = $donationinterface_dir . 'gateway_forms/TwoStepTwoColumnPremium.php';
$wgAutoloadClasses['Gateway_Form_TwoStepTwoColumnPremiumUS'] = $donationinterface_dir . 'gateway_forms/TwoStepTwoColumnPremiumUS.php';
$wgAutoloadClasses['Gateway_Form_RapidHtml'] = $donationinterface_dir . 'gateway_forms/RapidHtml.php';
$wgAutoloadClasses['Gateway_Form_SingleColumn'] = $donationinterface_dir . 'gateway_forms/SingleColumn.php';



// set defaults, these should be assigned in LocalSettings.php
//$wgGlobalCollectURL = 'https://globalcollect.paypal.com';
//$wgGlobalCollectTestingURL = 'https://pilot-globalcollect.paypal.com'; // Payflow testing URL
//$wgGlobalCollectGatewayCSSVersion = 1;
//
//$wgGlobalCollectPartnerID = ''; // PayPal or original authorized reseller
//$wgGlobalCollectVendorID = ''; // paypal merchant login ID
//$wgGlobalCollectUserID = ''; // if one or more users are set up, authorized user ID, else same as VENDOR
//$wgGlobalCollectPassword = ''; // merchant login password
//
//// a boolean to determine if we're in testing mode
//$wgPayflowGatewayTest = FALSE;
//
//// timeout in seconds for communicating with paypal
//$wgGlobalCollectTimeout = 5;

/**
 * The default form to use
 */
//$wgPayflowGatewayDefaultForm = 'TwoStepTwoColumn';

/**
 * A string or array of strings for making tokens more secure
 *
 * Please set this!  If you do not, tokens are easy to get around, which can
 * potentially leave you and your users vulnerable to CSRF or other forms of
 * attack.
 */
//$wgPayflowGatewaySalt = $wgSecretKey;
//
//$wgPayflowGatewayDBserver = $wgDBserver;
//$wgPayflowGatewayDBname = $wgDBname;
//$wgPayflowGatewayDBuser = $wgDBuser;
//$wgPayflowGatewayDBpassword = $wgDBpassword;

/**
 * A string that can contain wikitext to display at the head of the credit card form
 *
 * This string gets run like so: $wg->addHtml( $wg->Parse( $wgpayflowGatewayHeader ))
 * You can use '@language' as a placeholder token to extract the user's language.
 *
 */
//$wgPayflowGatewayHeader = NULL;

/**
 * A string containing full URL for Javascript-disabled credit card form redirect
 */
//$wgPayflowGatewayNoScriptRedirect = null;

/**
 * Proxy settings
 *
 * If you need to use an HTTP proxy for outgoing traffic,
 * set wgPayflowGatweayUseHTTPProxy=TRUE and set $wgPayflowGatewayHTTPProxy
 * to the proxy desination.
 *  eg:
 *  $wgPayflowGatewayUseHTTPProxy=TRUE;
 *  $wgPayflowGatewayHTTPProxy='192.168.1.1:3128'
 */
//$wgPayflowGatewayUseHTTPProxy = FALSE;
//$wgPayflowGatewayHTTPProxy = '';

/**
 * The URL to redirect a transaction to PayPal
 */
//$wgPayflowGatewayPaypalURL = '';

/**
 * Set the max-age value for Squid
 *
 * If you have Squid enabled for caching, use this variable to configure
 * the s-max-age for cached requests.
 * @var int Time in seconds
 */
//$wgPayflowSMaxAge = 6000;

/**
 * Directory for HTML forms (used by RapidHtml form class)
 * @var string
 */
$wgPayflowHtmlFormDir = dirname( __FILE__ ) . "/forms/html";

/**
 * An array of allowed HTML forms.
 * 
 * Be sure to use full paths.  If your HTML form is not listed here, it will
 * /never/ be loaded by the rapid html form loader!
 * @var string
 */
$wgPayflowAllowedHtmlForms = array( $wgPayflowHtmlFormDir . "/demo.html" );

/**
 * Configure PayflowproGateway to use syslog for log messages rather than wfDebugLog
 * 
 * @var bool
 */
$wgPayflowGatewayUseSyslog = false;

/**
 * Configure price cieling and floor for valid contribution amount.  Values 
 * should be in USD.
 */
$wgPayflowPriceFloor = '1.00';
$wgPayflowPriceCieling = '10000.00';

/**
 * Hooks required to interface with the donation extension (include <donate> on page)
 *
 * gwValue supplies the value of the form option, the name that appears on the form
 * and the currencies supported by the gateway in the $values array
 */
$wgHooks['DonationInterface_Value'][] = 'pfpGatewayValue';
$wgHooks['DonationInterface_Page'][] = 'pfpGatewayPage';

// enable the API
//$wgAPIModules[ 'pfp' ] = 'ApiGlobalCollectGateway';
//$wgAutoloadClasses[ 'ApiGlobalCollectGateway' ] = $dir . 'api_globalcollect_gateway.php';

//function payflowGatewayConnection() {
//	global $wgPayflowGatewayDBserver, $wgPayflowGatewayDBname;
//	global $wgPayflowGatewayDBuser, $wgPayflowGatewayDBpassword;
//
//	static $db;
//
//	if ( !$db ) {
//			$db = new DatabaseMysql(
//					$wgPayflowGatewayDBserver,
//					$wgPayflowGatewayDBuser,
//					$wgPayflowGatewayDBpassword,
//					$wgPayflowGatewayDBname );
//					$db->query( "SET names utf8" );
//	}
//
//	return $db;
//}

/**
 * Hook to register form value and display name of this gateway
 * also supplies currencies supported by this gateway
 */
//function pfpGatewayValue( &$values ) {
//	$values['payflow'] = array(
//			'gateway' => 'payflow',
//			'display_name' => 'Credit Card',
//			'form_value' => 'payflow',
//			'currencies' => array(
//					'GBP' => 'GBP: British Pound',
//					'EUR' => 'EUR: Euro',
//					'USD' => 'USD: U.S. Dollar',
//					'AUD' => 'AUD: Australian Dollar',
//					'CAD' => 'CAD: Canadian Dollar',
//					'JPY' => 'JPY: Japanese Yen',
//			),
//	);
//
//	return true;
//}

/**
 *  Hook to supply the page address of the payment gateway
 *
 * The user will redirected here with supplied data with input data appended (GET).
 * For example, if $url[$key] = index.php?title=Special:GlobalCollectGateway
 * the result might look like this: http://www.yourdomain.com/index.php?title=Special:GlobalCollectGateway&amount=75.00&currency_code=USD&payment_method=payflow
 */
//function pfpGatewayPage( &$url ) {
//	global $wgScript;
//
//	$url['payflow'] = $wgScript . "?title=Special:GlobalCollectGateway";
//	return true;
//}
