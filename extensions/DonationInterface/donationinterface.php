<?php

/**
 * Donation Interface
 *
 *  To install the DontaionInterface extension, put the following line in LocalSettings.php:
 *	require_once( "\$IP/extensions/DonationInterface/donationinterface.php" );
 * 
 */


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

$donationinterface_dir = dirname( __FILE__ ) . '/';

/**
 * Figure out what we've got enabled. 
 */

$optionalParts = array( //define as fail closed. This variable will be unset before we leave this file. 
	'Extras' => false, //this one gets set in the next loop, so don't bother. 
	'Stomp' => false,
	'CustomFilters' => false, //this is definitely an Extra
	'ConversionLog' => false, //this is definitely an Extra
	'Minfraud' => false, //this is definitely an Extra
	'Minfraud_as_filter' => false, //extra
	'Recaptcha' => false, //extra
	'Paypal' => false, //this is the paypal redirect. TODO: Determine if we're even using this anymore. 
	'PayflowPro' => false,
	'GlobalCollect' => false,
	
);

foreach ($optionalParts as $subextension => $enabled){
	$globalname = 'wgDonationInterfaceEnable' . $subextension;
	global $$globalname;
	if ( isset( $$globalname ) && $$globalname === true ) {
		$optionalParts[$subextension] = true;
		if ( $subextension === 'CustomFilters' ||
			$subextension === 'ConversionLog' ||
			$subextension === 'Minfraud' ||
			$subextension === 'Recaptcha' ) {
			
			$optionalParts['Extras'] = true;
		}
	}
}


/**
 * CLASSES
 */
$wgAutoloadClasses['DonationData'] = $donationinterface_dir . 'gateway_common/DonationData.php';
$wgAutoloadClasses['GatewayAdapter'] = $donationinterface_dir . 'gateway_common/gateway.adapter.php';
$wgAutoloadClasses['GatewayForm'] = $donationinterface_dir . 'gateway_common/GatewayForm.php';
$wgAutoloadClasses['DonationApi'] = $donationinterface_dir . 'gateway_common/donation.api.php';

//load all possible form classes
$wgAutoloadClasses['Gateway_Form'] = $donationinterface_dir . 'gateway_forms/Form.php';
$wgAutoloadClasses['Gateway_Form_OneStepTwoColumn'] = $donationinterface_dir . 'gateway_forms/OneStepTwoColumn.php';
$wgAutoloadClasses['Gateway_Form_TwoStepAmount'] = $donationinterface_dir . 'gateway_forms/TwoStepAmount.php';
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



//Stomp classes
if ($optionalParts['Stomp'] === true){
	$wgAutoloadClasses['activemq_stomp'] = $donationinterface_dir . 'activemq_stomp/activemq_stomp.php'; # Tell MediaWiki to load the extension body.
}

//Extras classes - required for ANY optional class that is considered an "extra". 
if ($optionalParts['Extras'] === true){
	$wgAutoloadClasses['Gateway_Extras'] = $donationinterface_dir . "extras/extras.body.php";
}

//Custom Filters classes
if ($optionalParts['CustomFilters'] === true){
	$wgAutoloadClasses['Gateway_Extras_CustomFilters'] = $donationinterface_dir . "extras/custom_filters/custom_filters.body.php";
}

//Conversion Log classes
if ($optionalParts['ConversionLog'] === true){
	$wgAutoloadClasses['Gateway_Extras_ConversionLog'] = $donationinterface_dir . "extras/conversion_log/conversion_log.body.php";
}

//Minfraud classes
if ( $optionalParts['Minfraud'] === true || $optionalParts['Minfraud_as_filter'] === true ){
	$wgAutoloadClasses['Gateway_Extras_MinFraud'] = $donationinterface_dir . "extras/minfraud/minfraud.body.php";
}

//Minfraud as Filter classes
if ( $optionalParts['Minfraud_as_filter'] === true ){
	$wgAutoloadClasses['Gateway_Extras_CustomFilters_MinFraud'] = $donationinterface_dir . "extras/custom_filters/filters/minfraud/minfraud.body.php";
}

//Recaptcha classes
if ( $optionalParts['Recaptcha'] === true ){
	$wgAutoloadClasses['Gateway_Extras_ReCaptcha'] = $donationinterface_dir . "extras/recaptcha/recaptcha.body.php";
}


/**
 * GLOBALS
 */

/**
 * Global form dir and RapidHTML whitelist
 */
$wgDonationInterfaceHtmlFormDir = dirname( __FILE__ ) . "/gateway_forms/html";
//ffname is the $key from now on. 
$wgDonationInterfaceAllowedHtmlForms = array(
	'demo' => $wgDonationInterfaceHtmlFormDir . "/demo.html",
	'globalcollect_test' => $wgDonationInterfaceHtmlFormDir . "/globalcollect_test.html",
);

$wgDonationInterfaceTest = false;

/**
 * Default Thank You and Fail pages for all of donationinterface - language will be calc'd and appended at runtime. 
 */
//$wgDonationInterfaceThankYouPage = 'https://wikimediafoundation.org/wiki/Thank_You';
$wgDonationInterfaceThankYouPage = 'Donate-thanks';
$wgDonationInterfaceFailPage = 'Donate-error';

/**
 * The URL to redirect a transaction to PayPal
 */
$wgDonationInterfacePaypalURL = '';
$wgDonationInterfaceRetrySeconds = 5;


//Paypal gateway globals
if ( $optionalParts['Paypal'] === true ){
	// default variables that should be set in LocalSettings.php
	$wgPaypalEmail = '';
	$wgPaypalUrl = 'http://wikimediafoundation.org/wiki/Special:ContributionTracking?';
}

//Stomp globals
if ($optionalParts['Stomp'] === true){
	$wgStompServer = "";
	//$wgStompQueueName = ""; //only set this with an actual value. Default is unset. 
	//$wgPendingStompQueueName = ""; //only set this with an actual value. Default is unset. 
}

//Extras globals - required for ANY optional class that is considered an "extra". 
if ($optionalParts['Extras'] === true){
	$wgDonationInterfaceExtrasLog = '';
}

//Custom Filters globals
if ( $optionalParts['CustomFilters'] === true ){
	//Define the action to take for a given $risk_score
	$wgDonationInterfaceCustomFiltersActionRanges = array(
		'process' => array( 0, 100 ),
		'review' => array( -1, -1 ),
		'challenge' => array( -1, -1 ),
		'reject' => array( -1, -1 ),
	);
	
	/**
	 * A value for tracking the 'riskiness' of a transaction
	 *
	 * The action to take based on a transaction's riskScore is determined by
	 * $action_ranges.  This is built assuming a range of possible risk scores
	 * as 0-100, although you can probably bend this as needed.
	 */
	$wgDonationInterfaceCustomFiltersRiskScore = 0;
}

//Minfraud globals
if ( $optionalParts['Minfraud'] === true || $optionalParts['Minfraud_as_filter'] === true ){
	/**
	 * Your minFraud license key.
	 */
	$wgMinFraudLicenseKey = '';

	/**
	 * Set the risk score ranges that will cause a particular 'action'
	 *
	 * The keys to the array are the 'actions' to be taken (eg 'process').
	 * The value for one of these keys is an array representing the lower
	 * and upper bounds for that action.  For instance,
	 *   $wgMinFraudActionRagnes = array(
	 * 		'process' => array( 0, 100)
	 * 		...
	 * 	);
	 * means that any transaction with a risk score greather than or equal
	 * to 0 and less than or equal to 100 will be given the 'process' action.
	 *
	 * These are evauluated on a >= or <= basis.  Please refer to minFraud
	 * documentation for a thorough explanation of the 'riskScore'.
	 */
	$wgMinFraudActionRanges = array(
		'process' => array( 0, 100 ),
		'review' => array( -1, -1 ),
		'challenge' => array( -1, -1 ),
		'reject' => array( -1, -1 )
	);

	// Timeout in seconds for communicating with MaxMind
	$wgMinFraudTimeout = 2;

	/**
	 * Define whether or not to run minFraud in stand alone mode
	 *
	 * If this is set to run in standalone, these scripts will be
	 * accessed directly via the "GatewayValidate" hook.
	 * You may not want to run this in standalone mode if you prefer
	 * to use this in conjunction with Custom Filters.  This has the
	 * advantage of sharing minFraud info with other filters.
	 */
	$wgMinFraudStandalone = TRUE;
	
}

//Minfraud as Filter globals
if ( $optionalParts['Minfraud_as_filter'] === true ){
	$wgMinFraudStandalone = FALSE;
}

//Recaptcha globals
if ( $optionalParts['Recaptcha'] === true ){
	/**
	 * Public and Private reCaptcha keys
	 *
	 * These can be obtained at:
	 *   http://www.google.com/recaptcha/whyrecaptcha
	 */
	$wgDonationInterfaceRecaptchaPublicKey = '';
	$wgDonationInterfaceRecaptchaPrivateKey = '';

	// Timeout (in seconds) for communicating with reCatpcha
	$wgDonationInterfaceRecaptchaTimeout = 2;

	/**
	 * HTTP Proxy settings
	 * 
	 * Default to settings in DonationInterface
	 */
	//TODO: I think we can get rid of these entirely, due to the way we are now checking for globals in the extras. 
	//$wgDonationInterfaceRecaptchaUseHTTPProxy = $wgDonationInterfaceUseHTTPProxy;
	//$wgDonationInterfaceRecaptchaHTTPProxy = $wgDonationInterfaceHTTPProxy;

	/**
	 * Use SSL to communicate with reCaptcha
	 */
	$wgDonationInterfaceRecaptchaUseSSL = 1;

	/**
	 * The # of times to retry communicating with reCaptcha if communication fails
	 * @var int
	 */
	$wgDonationInterfaceRecaptchaComsRetryLimit = 3;
}


/**
 * HOOKS
 */

//Unit tests
$wgHooks['UnitTestsList'][] = 'efDonationInterfaceUnitTests';


//Paypal gateway globals
if ( $optionalParts['Paypal'] === true ){
	//TODO: Determine if this is all cruft. I'm guessing "Probably". 
	/**
	 * Hooks required to interface with the donation extension (include <donate> on page)
	 *
	 * gwValue supplies the value of the form option, the name that appears on the form
	 * and the currencies supported by the gateway in the $values array
	 */
	$wgHooks['DonationInterface_Value'][] = 'paypalGatewayValue';
	$wgHooks['DonationInterface_Page'][] = 'paypalGatewayPage';
}

//Stomp hooks
if ($optionalParts['Stomp'] === true){
	$wgHooks['ParserFirstCallInit'][] = 'efStompSetup';
	$wgHooks['gwStomp'][] = 'sendSTOMP';
	$wgHooks['gwPendingStomp'][] = 'sendPendingSTOMP';
}

//Custom Filters hooks
if ($optionalParts['CustomFilters'] === true){
	$wgHooks["GatewayValidate"][] = array( 'Gateway_Extras_CustomFilters::onValidate' );
}

//Conversion Log hooks
if ($optionalParts['ConversionLog'] === true){
	// Sets the 'conversion log' as logger for post-processing
	$wgHooks["GatewayPostProcess"][] = array( "Gateway_Extras_ConversionLog::onPostProcess" );
}

//Recaptcha hooks
if ($optionalParts['Recaptcha'] === true){
	// Set reCpatcha as plugin for 'challenge' action
	$wgHooks["GatewayChallenge"][] = array( "Gateway_Extras_ReCaptcha::onChallenge" );
}

/**
 * ADDITIONAL MAGICAL GLOBALS 
 */

// enable the API
$wgAPIModules['donate'] = 'DonationApi';

// Resource modules
$wgResourceTemplate = array(
	'localBasePath' => $donationinterface_dir . 'modules',
	'remoteExtPath' => 'DonationInterface/modules',
);
$wgResourceModules['iframe.liberator'] = array(
	'scripts' => 'iframe.liberator.js',
	'position' => 'top'
	) + $wgResourceTemplate;
$wgResourceModules['donationInterface.skinOverride'] = array(
	'styles' => 'skinOverride.css',
	'position' => 'top'
	) + $wgResourceTemplate;

$wgExtensionMessagesFiles['DonateInterface'] = $donationinterface_dir . 'donate_interface/donate_interface.i18n.php';

//Paypal magical globals
if ( $optionalParts['Paypal'] === true ){
	$wgExtensionMessagesFiles['PaypalGateway'] = $donationinterface_dir . 'paypal_gateway/paypal_gateway.i18n.php';
}

//Minfraud magical globals
if ( $optionalParts['Minfraud'] === true ){ //We do not want this in filter mode. 
	$wgExtensionFunctions[] = 'efMinFraudSetup';
}

//Minfraud as Filter globals
if ( $optionalParts['Minfraud_as_filter'] === true ){
	$wgExtensionFunctions[] = 'efCustomFiltersMinFraudSetup';
}


/**
 * FUNCTIONS
 */

//---Stomp functions---
if ($optionalParts['Stomp'] === true){
	require_once( $donationinterface_dir . 'activemq_stomp/activemq_stomp.php'  );
}

//---Minfraud functions---
if ($optionalParts['Minfraud'] === true){
	require_once( $donationinterface_dir . 'extras/minfraud/minfraud.php'  );
}

//---Minfraud as filter functions---
if ($optionalParts['Minfraud_as_filter'] === true){
	require_once( $donationinterface_dir . 'extras/custom_filters/filters/minfraud/minfraud.php'  );
}

//---Paypal functions---
if ($optionalParts['Paypal'] === true){
	require_once( $donationinterface_dir . 'paypal_gateway/paypal_gateway.php'  );
}







//This is going to be a little funky. 
//Override this in LocalSettings.php BEFORE you include this file, if you want 
//to disable gateways.
//TODO: Unfunktify, if you have a better idea here for auto-loading the classes after LocalSettings.php runs all the way. 
if ( !isset( $wgDonationInterfaceEnabledGateways ) ) {
	$wgDonationInterfaceEnabledGateways = array(
		'payflowpro',
		'globalcollect'
	);
}

foreach ( $wgDonationInterfaceEnabledGateways as $gateway ) {
	//include 'em
	require_once( $donationinterface_dir . $gateway . '_gateway/' . $gateway . '_gateway.php' );
}


function efDonationInterfaceUnitTests( &$files ) {
	$files[] = $donationinterface_dir . 'tests/AllTests.php';
	return true;
}

unset( $optionalParts );
