<?php
/**
 * TODO: Remove this file. :)
 */

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install GlobalCollect Gateway extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/globalcollect_gateway/globalcollect_gateway.php" );
EOT;
	exit( 1 );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'GlobalCollect Gateway',
	'author' => 'Four Kitchens',
	'version' => '1.0.0',
	'descriptionmsg' => 'globalcollect_gateway-desc',
	'url' => 'http://www.mediawiki.org/wiki/Extension:GlobalCollectGateway',
);

//$wgGlobalCollectGatewayUseSyslog = false;

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
//$wgAutoloadClasses['GlobalCollectGateway'] = $dir . 'globalcollect_gateway.body.php';
//$wgAutoloadClasses['GlobalCollectGatewayResult'] = $dir . 'globalcollect_resultswitcher.body.php';
//$wgAutoloadClasses['GlobalCollectAdapter'] = $dir . 'globalcollect.adapter.php';
//$wgExtensionMessagesFiles['GlobalCollectGateway'] = $dir . '../payflowpro_gateway/payflowpro_gateway.i18n.php';
//$wgExtensionMessagesFiles['GlobalCollectGatewayCountries'] = $dir . '../payflowpro_gateway/payflowpro_gateway.countries.i18n.php';
//$wgExtensionMessagesFiles['GlobalCollectGatewayUSStates'] = $dir . '../payflowpro_gateway/payflowpro_gateway.us-states.i18n.php';
//$wgExtensionAliasesFiles['GlobalCollectGateway'] = $dir . '../payflowpro_gateway/payflowpro_gateway.alias.php';
//$wgSpecialPages['GlobalCollectGateway'] = 'GlobalCollectGateway';
//$wgSpecialPages['GlobalCollectGatewayResult'] = 'GlobalCollectGatewayResult';
//$wgAjaxExportList[] = "fnGlobalCollectofofWork";
// set defaults, these should be assigned in LocalSettings.php
//$wgGlobalCollectGatewayURL = 'https://ps.gcsip.nl/wdl/wdl';
//$wgGlobalCollectGatewayTestingURL = 'https://'; // GlobalCollect testing URL

//$wgGlobalCollectGatewayCSSVersion = 1;

//$wgGlobalCollectGatewayMerchantID = ''; // GlobalCollect ID
// a boolean to determine if we're in testing mode
//$wgGlobalCollectGatewayTest = FALSE;

// timeout in seconds for communicating with [gateway]
//$wgGlobalCollectGatewayTimeout = 2;

/**
 * The default form to use
 */
//$wgGlobalCollectGatewayDefaultForm = 'TwoStepTwoColumn';

/**
 * A string or array of strings for making tokens more secure
 *
 * Please set this!  If you do not, tokens are easy to get around, which can
 * potentially leave you and your users vulnerable to CSRF or other forms of
 * attack.
 */
//$wgGlobalCollectGatewaySalt = $wgSecretKey;


/**
 * A string that can contain wikitext to display at the head of the credit card form
 *
 * This string gets run like so: $wg->addHtml( $wg->Parse( $wgpayflowGatewayHeader ))
 * You can use '@language' as a placeholder token to extract the user's language.
 *
 */
//$wgGlobalCollectGatewayHeader = NULL;

/**
 * A string containing full URL for Javascript-disabled credit card form redirect
 */
//$wgGlobalCollectGatewayNoScriptRedirect = null;

/**
 * Proxy settings
 *
 * If you need to use an HTTP proxy for outgoing traffic,
 * set wgGlobalCollectGatweayUseHTTPProxy=TRUE and set $wgGlobalCollectGatewayHTTPProxy
 * to the proxy desination.
 *  eg:
 *  $wgGlobalCollectGatewayUseHTTPProxy=TRUE;
 *  $wgGlobalCollectGatewayHTTPProxy='192.168.1.1:3128'
 */
//$wgGlobalCollectGatewayUseHTTPProxy = FALSE;
//$wgGlobalCollectGatewayHTTPProxy = '';

/**
 * Set the max-age value for Squid
 *
 * If you have Squid enabled for caching, use this variable to configure
 * the s-max-age for cached requests.
 * @var int Time in seconds
 */
//$wgGlobalCollectGatewaySMaxAge = 6000;

/**
 * Directory for HTML forms (used by RapidHtml form class)
 * @var string
 */
//$wgGlobalCollectGatewayHtmlFormDir = dirname( __FILE__ ) . "/forms/html";

/**
 * An array of allowed HTML forms.
 * 
 * Be sure to use full paths.  If your HTML form is not listed here, it will
 * /never/ be loaded by the rapid html form loader!
 * @var string
 */
//$wgGlobalCollectGatewayAllowedHtmlForms = $wgDonationInterfaceAllowedHtmlForms;

///**
// * Configure price cieling and floor for valid contribution amount.  Values 
// * should be in USD.
// */
//$wgGlobalCollectGatewayPriceFloor = '1.00';
//$wgGlobalCollectGatewayPriceCeiling = '10000.00';
//
///**
// * Thank You & Fail pages. 
// */
//$wgGlobalCollectGatewayThankYouPage = $wgDonationInterfaceThankYouPage;
//$wgGlobalCollectGatewayFailPage = $wgDonationInterfaceFailPage;