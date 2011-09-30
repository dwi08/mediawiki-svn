<?php

/**
 * Extra to log gateway response during post processing hook
 *
 * @fixme Class/file names should likely change to reflect change in purpose...
 *
 * To install:
 *      require_once( "$IP/extensions/DonationInterface/extras/conversion_log/conversion_log.php"
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the Conversion Log for Gateway extension. It is not a valid entry point.\n" );
}

$wgExtensionCredits['gateway_extras_conversionLog'][] = array(
	'name' => 'conversion log',
	'author' => 'Arthur Richards',
	'url' => '',
	'description' => "This extension handles logging for Gateway extension 'extras'"
);

$dir = dirname( __FILE__ ) . "/";
$wgAutoloadClasses['Gateway_Extras_ConversionLog'] = $dir . "conversion_log.body.php";

// Sets the 'conversion log' as logger for post-processing
$wgHooks["GatewayPostProcess"][] = array( "Gateway_Extras_ConversionLog::onPostProcess" );
