<?php
/** Additional DonationInterface Forms **/

/**
 * Include optional form classes here. 
 * All these exist, but are disabled by default. 
 * Uncomment to enable.
 */
//$wgAutoloadClasses['Gateway_Form_OneStepTwoColumn'] = $donationinterface_dir . 'gateway_forms/OneStepTwoColumn.php';
//$wgAutoloadClasses['Gateway_Form_TwoColumnPayPal'] = $donationinterface_dir . 'gateway_forms/TwoColumnPayPal.php';
//$wgAutoloadClasses['Gateway_Form_TwoColumnLetter'] = $donationinterface_dir . 'gateway_forms/TwoColumnLetter.php';
//$wgAutoloadClasses['Gateway_Form_TwoColumnLetter2'] = $donationinterface_dir . 'gateway_forms/TwoColumnLetter2.php';
//$wgAutoloadClasses['Gateway_Form_TwoColumnLetter3'] = $donationinterface_dir . 'gateway_forms/TwoColumnLetter3.php';
//$wgAutoloadClasses['Gateway_Form_TwoColumnLetter4'] = $donationinterface_dir . 'gateway_forms/TwoColumnLetter4.php';
//$wgAutoloadClasses['Gateway_Form_TwoColumnLetter5'] = $donationinterface_dir . 'gateway_forms/TwoColumnLetter5.php';
//$wgAutoloadClasses['Gateway_Form_TwoColumnLetter6'] = $donationinterface_dir . 'gateway_forms/TwoColumnLetter6.php';
//$wgAutoloadClasses['Gateway_Form_TwoColumnLetter7'] = $donationinterface_dir . 'gateway_forms/TwoColumnLetter7.php';
//$wgAutoloadClasses['Gateway_Form_TwoStepTwoColumnLetterCA'] = $donationinterface_dir . 'gateway_forms/TwoStepTwoColumnLetterCA.php';
//$wgAutoloadClasses['Gateway_Form_TwoStepTwoColumnLetter2'] = $donationinterface_dir . 'gateway_forms/TwoStepTwoColumnLetter2.php';
//$wgAutoloadClasses['Gateway_Form_TwoStepTwoColumnPremium'] = $donationinterface_dir . 'gateway_forms/TwoStepTwoColumnPremium.php';
//$wgAutoloadClasses['Gateway_Form_TwoStepTwoColumnPremiumUS'] = $donationinterface_dir . 'gateway_forms/TwoStepTwoColumnPremiumUS.php';
//$wgAutoloadClasses['Gateway_Form_SingleColumn'] = $donationinterface_dir . 'gateway_forms/SingleColumn.php';

if ( !isset( $wgDonationDataAllowedHtmlForms )) $wgDonationDataAllowedHtmlForms = array();

/**
 * DonationInterface RapidHTML whitelist additions
 * These will apply to all enabled adapters 
 */
//example addition of an extension-wide form
//$wgDonationInterfaceAllowedHtmlForms['demo'] = $wgDonationInterfaceHtmlFormDir .'/demo.html';

/** Sync up with the gateways again, in such a way that we get all the clobal changes without ditching what we already have.  **/
$wgGlobalCollectGatewayAllowedHtmlForms = array_merge( $wgGlobalCollectGatewayAllowedHtmlForms, $wgDonationDataAllowedHtmlForms );
$wgPayflowProGatewayAllowedHtmlForms = array_merge( $wgPayflowProGatewayAllowedHtmlForms, $wgDonationDataAllowedHtmlForms );

/**
 * GlobalCollect RapidHTML whitelist additions
 */
$wgGlobalCollectGatewayAllowedHtmlForms['webitects_2_3step'] = $wgGlobalCollectGatewayHtmlFormDir . '/webitects_2_3step.html';
$wgGlobalCollectGatewayAllowedHtmlForms['webitects_2_3step-CA'] = $wgGlobalCollectGatewayHtmlFormDir . '/webitects_2_3step-CA.html';
$wgGlobalCollectGatewayAllowedHtmlForms['webitects_2_3stepB-US'] = $wgGlobalCollectGatewayHtmlFormDir . '/webitects_2_3stepB-US.html';
$wgGlobalCollectGatewayAllowedHtmlForms['bt'] = $wgGlobalCollectGatewayHtmlFormDir . '/bt/bt.html';
$wgGlobalCollectGatewayAllowedHtmlForms['bt-CA'] = $wgGlobalCollectGatewayHtmlFormDir . '/bt/bt-CA.html';
$wgGlobalCollectGatewayAllowedHtmlForms['bt-US'] = $wgGlobalCollectGatewayHtmlFormDir . '/bt/bt-US.html';
$wgGlobalCollectGatewayAllowedHtmlForms['webitects2nd'] = $wgGlobalCollectGatewayHtmlFormDir . '/webitects2nd.html';
$wgGlobalCollectGatewayAllowedHtmlForms['rtbt-enets'] = $wgGlobalCollectGatewayHtmlFormDir . '/rtbt/rtbt-enets.html';
$wgGlobalCollectGatewayAllowedHtmlForms['rtbt-eps'] = $wgGlobalCollectGatewayHtmlFormDir . '/rtbt/rtbt-eps.html';
$wgGlobalCollectGatewayAllowedHtmlForms['rtbt-ideal'] = $wgGlobalCollectGatewayHtmlFormDir . '/rtbt/rtbt-ideal.html';
$wgGlobalCollectGatewayAllowedHtmlForms['rtbt-sofo'] = $wgGlobalCollectGatewayHtmlFormDir . '/rtbt/rtbt-sofo.html';
$wgGlobalCollectGatewayAllowedHtmlForms['rtbt-sofo-GB'] = $wgGlobalCollectGatewayHtmlFormDir . '/rtbt/rtbt-sofo-GB.html';

/**
 * PayflowPro RapidHTML whitelist additions
 */
//The following example is hard-coded as a default. Just a sample line. 
//$wgPayflowProGatewayAllowedHtmlForms['lightbox1'] = $wgPayflowProGatewayHtmlFormDir .'/lightbox1.html';
$wgPayflowProGatewayAllowedHtmlForms['webitects_2_3step'] = $wgPayflowProGatewayHtmlFormDir .'/webitects_2_3step.html';
$wgPayflowProGatewayAllowedHtmlForms['webitects_2_3step-CA'] = $wgPayflowProGatewayHtmlFormDir .'/webitects_2_3step-CA.html';
$wgPayflowProGatewayAllowedHtmlForms['webitects_2_2step-US'] = $wgPayflowProGatewayHtmlFormDir .'/webitects_2_2step-US.html';
$wgPayflowProGatewayAllowedHtmlForms['webitects_2_2stepB-US'] = $wgPayflowProGatewayHtmlFormDir .'/webitects_2_2stepB-US.html';
$wgPayflowProGatewayAllowedHtmlForms['globalcollect_test_2'] = $donationinterface_dir .'/gateway_forms/rapidhtml/html/globalcollect_test_2.html';
