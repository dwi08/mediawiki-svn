<?php

/**
 * Provides a unified way to define and run custom filters for incoming transactions
 *
 * Running filters through 'custom filters' rather than directly through the validate hook in the gateway
 * offers the advantage of simplifying the passage of relvent data between filters/validators that's
 * needed to perform more complex validation/filtering of transactions.
 *
 * The actual filters themselves are regular MW extensions and can optional be organized in filters/
 * They should be invoked by using the 'GatewayCustomFilter' hook, which will pass the entire
 * CustomFilter object to the filter.  The gateway object and its data are included in the CustomFilter
 * object. 
 *
 *  To install the DontaionInterface extension, put the following line in LocalSettings.php:
 *	require_once( "\$IP/extensions/DonationInterface/donationinterface.php" );
 * 
 * TODO: Remove this file. :)
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the MinFraud for Gateway extension. It is not a valid entry point.\n" );
}

$wgExtensionCredits['gateway_custom_filters'][] = array(
	'name' => 'custom filters',
	'author' => 'Arthur Richards',
	'url' => '',
	'description' => 'This extension provides a way to define custom filters for incoming transactions for the gateway.'
);
