<?php

/**
 * An abstract class and set up for gateway 'extras'
 *
 * To install:
 *      require_once( "$IP/extensions/DonationInterface/extras/extras.php"
 * Note: This should be specified in LocalSettings.php BEFORE requiring any of the other 'extras'
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of Gateway extension. It is not a valid entry point.\n" );
}

$wgExtensionCredits['gateway_extras'][] = array(
	'name' => 'extras',
	'author' => 'Arthur Richards',
	'url' => '',
	'description' => "This extension handles some of the set up required for Gateway extras"
);

/**
 * Full path to file to use for logging for Gateway scripts
 *
 * Declare in LocalSettings.php
 */
$wgDonationInterfaceExtrasLog = '';

$dir = dirname( __FILE__ ) . "/";
$wgAutoloadClasses['Gateway_Extras'] = $dir . "extras.body.php";