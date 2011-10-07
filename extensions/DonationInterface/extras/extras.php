<?php

/**
 * An abstract class and set up for gateway 'extras'
 *
 * To install the DontaionInterface extension, put the following line in LocalSettings.php:
 *	require_once( "\$IP/extensions/DonationInterface/donationinterface.php" );
 * 
 * TODO: Remove this file. :)
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

