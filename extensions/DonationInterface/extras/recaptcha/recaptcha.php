<?php

/**
 * Extra to expose a recaptcha for 'challenged' transactions
 *
 * To install:
 *      require_once( "$IP/extensions/DonationInterface/extras/recaptcha/recaptcha.php"
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the ReCaptcha for Gateway extension. It is not a valid entry point.\n" );
}

$wgExtensionCredits['extras_recaptcha'][] = array(
	'name' => 'reCaptcha',
	'author' => 'Arthur Richards',
	'url' => '',
	'description' => "This extension exposes a reCpathca for 'challenged' transactions in the Gateway"
);

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

$dir = dirname( __FILE__ ) . "/";
$wgAutoloadClasses['Gateway_Extras_ReCaptcha'] = $dir . "recaptcha.body.php";

// Set reCpatcha as plugin for 'challenge' action
$wgHooks["GatewayChallenge"][] = array( "Gateway_Extras_ReCaptcha::onChallenge" );
