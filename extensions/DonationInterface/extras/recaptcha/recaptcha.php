<?php

/**
 * Extra to expose a recaptcha for 'challenged' transactions
 *
 *  To install the DontaionInterface extension, put the following line in LocalSettings.php:
 *	require_once( "\$IP/extensions/DonationInterface/donationinterface.php" );
 * 
 * TODO: Remove this file. :)
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
