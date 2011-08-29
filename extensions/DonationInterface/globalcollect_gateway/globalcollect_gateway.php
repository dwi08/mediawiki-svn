<?php

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

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgAutoloadClasses['GlobalCollectGateway'] = $dir . 'globalcollect_gateway.body.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form' ] = $dir . 'forms/Form.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_OneStepTwoColumn' ] = $dir . 'forms/OneStepTwoColumn.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_TwoStepTwoColumn' ] = $dir . 'forms/TwoStepTwoColumn.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_TwoColumnPayPal' ] = $dir . 'forms/TwoColumnPayPal.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_TwoColumnLetter' ] = $dir . 'forms/TwoColumnLetter.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_TwoColumnLetter2' ] = $dir . 'forms/TwoColumnLetter2.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_TwoColumnLetter3' ] = $dir . 'forms/TwoColumnLetter3.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_TwoColumnLetter4' ] = $dir . 'forms/TwoColumnLetter4.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_TwoColumnLetter5' ] = $dir . 'forms/TwoColumnLetter5.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_TwoColumnLetter6' ] = $dir . 'forms/TwoColumnLetter6.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_TwoColumnLetter7' ] = $dir . 'forms/TwoColumnLetter7.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_TwoStepTwoColumnLetter' ] = $dir . 'forms/TwoStepTwoColumnLetter.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_TwoStepTwoColumnLetterCA' ] = $dir . 'forms/TwoStepTwoColumnLetterCA.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_TwoStepTwoColumnLetter2' ] = $dir . 'forms/TwoStepTwoColumnLetter2.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_TwoStepTwoColumnLetter3' ] = $dir . 'forms/TwoStepTwoColumnLetter3.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_TwoStepTwoColumnPremium' ] = $dir . 'forms/TwoStepTwoColumnPremium.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_TwoStepTwoColumnPremiumUS' ] = $dir . 'forms/TwoStepTwoColumnPremiumUS.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_RapidHtml' ] = $dir . 'forms/RapidHtml.php';
//$wgAutoloadClasses[ 'GlobalCollectGateway_Form_SingleColumn' ] = $dir . 'forms/SingleColumn.php';
$wgExtensionMessagesFiles['GlobalCollectGateway'] = $dir . '../payflowpro_gateway/payflowpro_gateway.i18n.php';
$wgExtensionMessagesFiles['GlobalCollectGatewayCountries'] = $dir . '../payflowpro_gateway/payflowpro_gateway.countries.i18n.php';
$wgExtensionMessagesFiles['GlobalCollectGatewayUSStates'] = $dir . '../payflowpro_gateway/payflowpro_gateway.us-states.i18n.php';
$wgExtensionAliasesFiles['GlobalCollectGateway'] = $dir . '../payflowpro_gateway/payflowpro_gateway.alias.php';
$wgSpecialPages['GlobalCollectGateway'] = 'GlobalCollectGateway';
//$wgAjaxExportList[] = "fnGlobalCollectofofWork";


// set defaults, these should be assigned in LocalSettings.php
$wgGlobalCollectURL = 'https://ps.gcsip.nl/wdl/wdl';
//$wgGlobalCollectTestingURL = 'https://pilot-globalcollect.paypal.com'; // GlobalCollect testing URL

$wgGlobalCollectMerchantID = ''; // GlobalCollect ID

// a boolean to determine if we're in testing mode
$wgGlobalCollectGatewayTest = FALSE;

// timeout in seconds for communicating with paypal
$wgGlobalCollectTimeout = 5;

/**
 * The default form to use
 */
$wgGlobalCollectGatewayDefaultForm = 'TwoStepTwoColumn';

/**
 * A string or array of strings for making tokens more secure
 *
 * Please set this!  If you do not, tokens are easy to get around, which can
 * potentially leave you and your users vulnerable to CSRF or other forms of
 * attack.
 */
$wgGlobalCollectGatewaySalt = $wgSecretKey;

$wgGlobalCollectGatewayDBserver = $wgDBserver;
$wgGlobalCollectGatewayDBname = $wgDBname;
$wgGlobalCollectGatewayDBuser = $wgDBuser;
$wgGlobalCollectGatewayDBpassword = $wgDBpassword;

/**
 * A string that can contain wikitext to display at the head of the credit card form
 *
 * This string gets run like so: $wg->addHtml( $wg->Parse( $wgpayflowGatewayHeader ))
 * You can use '@language' as a placeholder token to extract the user's language.
 *
 */
$wgGlobalCollectGatewayHeader = NULL;

/**
 * A string containing full URL for Javascript-disabled credit card form redirect
 */
$wgGlobalCollectGatewayNoScriptRedirect = null;

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
$wgGlobalCollectGatewayUseHTTPProxy = FALSE;
$wgGlobalCollectGatewayHTTPProxy = '';

/**
 * Set the max-age value for Squid
 *
 * If you have Squid enabled for caching, use this variable to configure
 * the s-max-age for cached requests.
 * @var int Time in seconds
 */
$wgGlobalCollectSMaxAge = 6000;

/**
 * Directory for HTML forms (used by RapidHtml form class)
 * @var string
 */
$wgGlobalCollectHtmlFormDir = dirname( __FILE__ ) . "/forms/html";

/**
 * An array of allowed HTML forms.
 * 
 * Be sure to use full paths.  If your HTML form is not listed here, it will
 * /never/ be loaded by the rapid html form loader!
 * @var string
 */
$wgGlobalCollectAllowedHtmlForms = array(	$wgGlobalCollectHtmlFormDir . "/demo.html" );

/**
 * Configure GlobalCollectproGateway to use syslog for log messages rather than wfDebugLog
 * 
 * @var bool
 */
$wgGlobalCollectGatewayUseSyslog = false;

/**
 * Configure price cieling and floor for valid contribution amount.  Values 
 * should be in USD.
 */
$wgGlobalCollectPriceFloor = '1.00';
$wgGlobalCollectPriceCieling = '10000.00';
