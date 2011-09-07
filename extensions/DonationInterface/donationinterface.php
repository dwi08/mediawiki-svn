<?php

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

//This is going to be a little funky. 
//Override this in LocalSettings.php BEFORE you include this file, if you want 
//to disable gateways.
if ( !isset( $wgDonationInterfaceEnabledGateways ) ) {
	$wgDonationInterfaceEnabledGateways = array(
		'paypal',
		'payflowpro',
		'globalcollect'
	);
}

$donationinterface_dir = dirname( __FILE__ ) . '/';

require_once( $donationinterface_dir . 'donate_interface/donate_interface.php' );

foreach ( $wgDonationInterfaceEnabledGateways as $gateway ) {
	//include 'em
	require_once( $donationinterface_dir . $gateway . '_gateway/' . $gateway . '_gateway.php' );
}

//load all possible form classes
$wgAutoloadClasses['Gateway_Form'] = $donationinterface_dir . 'gateway_forms/Form.php';
$wgAutoloadClasses['Gateway_Form_OneStepTwoColumn'] = $donationinterface_dir . 'gateway_forms/OneStepTwoColumn.php';
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

//THE GATEWAYS WILL RESET THIS when they are instantiated. You can override it, but it won't stick around that way. 
$wgDonationInterfaceTest = false;

/**
 * Hooks required to interface with the donation extension (include <donate> on page)
 *
 * gwValue supplies the value of the form option, the name that appears on the form
 * and the currencies supported by the gateway in the $values array
 */
//$wgHooks['DonationInterface_Value'][] = 'pfpGatewayValue';
//$wgHooks['DonationInterface_Page'][] = 'pfpGatewayPage';
