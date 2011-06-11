<?php

/**
 * Provides hover-over tool tips on articles from words defined on the
 * Terminology page.
 *
 * @file
 * @defgroup Lingo
 * @author Barry Coughlan
 * @copyright 2010 Barry Coughlan
 * @author Stephan Gambke
 * @version 0.2 alpha
 * @licence GNU General Public Licence 2.0 or later
 * @see http://www.mediawiki.org/wiki/Extension:Lingo Documentation
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is part of a MediaWiki extension, it is not a valid entry point.' );
}

define( 'LINGO_VERSION', '0.2 alpha' );


// set defaults for settings

// set the backend to access the glossary
$wgexLingoBackend = 'LingoBasicBackend';

// set default for Terminology page (null = take from i18n)
$wgexLingoPage = null;


// server-local path to this file
$dir = dirname( __FILE__ );

// register message file
$wgExtensionMessagesFiles['Lingo'] = $dir . '/Lingo.i18n.php';

// register class files with the Autoloader
$wgAutoloadClasses['LingoHooks'] = $dir . '/LingoHooks.php';
$wgAutoloadClasses['LingoParser'] = $dir . '/LingoParser.php';
$wgAutoloadClasses['LingoTree'] = $dir . '/LingoTree.php';
$wgAutoloadClasses['LingoElement'] = $dir . '/LingoElement.php';
$wgAutoloadClasses['LingoBackend'] = $dir . '/LingoBackend.php';
$wgAutoloadClasses['LingoBasicBackend'] = $dir . '/LingoBasicBackend.php';
$wgAutoloadClasses['LingoMessageLog'] = $dir . '/LingoMessageLog.php';

// register hook handlers
$wgHooks['SpecialVersionExtensionTypes'][] = 'LingoHooks::setCredits'; // set credits
$wgHooks['ParserAfterTidy'][] = 'LingoHooks::parse'; // parse page
//$wgHooks['ParserFirstCallInit'][] = 'LingoHooks::setup'; // do late setup
$wgHooks['LanguageGetMagic'][] = 'LingoHooks::setMagicWords'; // set magic words

// register resource modules with the Resource Loader
$wgResourceModules['ext.Lingo.Styles'] = array(
	'localBasePath' => $dir,
	'remoteExtPath' => 'Lingo',
	// 'scripts' => 'libs/ext.myExtension.js',
	'styles' => 'skins/Lingo.css',
	// 'messages' => array( 'myextension-hello-world', 'myextension-goodbye-world' ),
	// 'dependencies' => array( 'jquery.ui.datepicker' ),
);

$wgResourceModules['ext.Lingo.Scripts'] = array(
	'localBasePath' => $dir,
	'remoteExtPath' => 'Lingo',
	'scripts' => 'libs/Lingo.js',
	// 'styles' => 'skins/Lingo.css',
	// 'messages' => array( 'myextension-hello-world', 'myextension-goodbye-world' ),
	// 'dependencies' => array( 'jquery.ui.datepicker' ),
);

MagicWord::$mDoubleUnderscoreIDs[] = 'noglossary';

unset( $dir );

