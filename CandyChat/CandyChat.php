<?php
/**
 * CandyChat extension
 *
 * @file
 * @ingroup Extensions
 *
 * This file contains the include file for CandyChat
 *
 * Usage: Include the following line in your LocalSettings.php
 * require_once( "$IP/extensions/CandyChat/CandyChat.php" );
 *
 * @author Ian Baker <ian@wikimedia.org>
 * @license GPL v2 or later
 * @version 0.1.0
 */

if (!defined('MEDIAWIKI')) {
	echo "This Mediawiki extension must be run from within Mediawiki.  See the README for more information.";
	exit( 1 );
}

/* Configuration */


// Credits
$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Candy Chat',
	'author' => 'Ian Baker',
	'version' => '0.1.0',
	'descriptionmsg' => 'candychat-desc',
	'url' => 'http://www.mediawiki.org/wiki/Extension:CandyChat'
);

// Resources
$ccResourceTemplate = array(
	'localBasePath' => dirname(__FILE__) . '/resources',
	'remoteExtPath' => 'CandyChat/resources'
);

$dir = dirname( __FILE__ );

$wgAutoloadClasses['SpecialCandyChat'] = $dir . '/SpecialCandyChat.php';
$wgAutoloadClasses['CandyChatHooks'] = $dir . '/CandyChat.hooks.php';

$wgExtensionMessagesFiles['CandyChat'] = $dir . '/CandyChat.i18n.php';
$wgExtensionAliasesFiles['CandyChat'] = $dir . '/CandyChat.alias.php';
$wgSpecialPages['CandyChat'] = 'SpecialCandyChat';
$wgSpecialPageGroups['CandyChat'] = 'other';

// hooks
$wgHooks['BeforePageDisplay'][] = 'CandyChatHooks::onPageDisplay';
$wgHooks['SkinTemplateNavigation'][] = 'CandyChatHooks::onSkinTemplateNavigation';

// resource loader config
$wgResourceModules['ext.candyChat.init'] = $ccResourceTemplate + array(
	'styles' => 'ext.candyChat.init.css',
	'scripts' => 'ext.candyChat.init.js',
/*	'messages' => array(
		'moodbar-trigger-feedback',
		'moodbar-trigger-share',
		'moodbar-trigger-editing',
		'tooltip-p-moodbar-trigger-feedback',
		'tooltip-p-moodbar-trigger-share',
		'tooltip-p-moodbar-trigger-editing',
	),*/
	'position' => 'top',
	'dependencies' => array(
		'jquery.cookie',
		'jquery.client',
	),
);
