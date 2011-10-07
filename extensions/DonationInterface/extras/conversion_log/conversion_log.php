<?php

/**
 * Extra to log gateway response during post processing hook
 *
 * @fixme Class/file names should likely change to reflect change in purpose...
 *
 *  To install the DontaionInterface extension, put the following line in LocalSettings.php:
 *	require_once( "\$IP/extensions/DonationInterface/donationinterface.php" );
 * 
 * TODO: Remove this file. :)
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
