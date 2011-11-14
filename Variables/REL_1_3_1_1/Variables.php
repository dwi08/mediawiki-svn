<?php
/**
 * Variables extension -- define page-scoped variables
 * This is a re-pack of 1.3.1 with adjusted comments since Version 2.0 is out
 * already but this is just the last release for MediaWiki wikis below 1.12.
 * So if you use MW 1.12+ use Variables 1.4 for even better 2.0+ wich comes with
 * a major bugfix.
 *
 * @file
 * @ingroup Extensions
 * @version 1.3.1.1
 * @author Rob Adams
 * @author Tom Hempel
 * @author Xiloynaha
 * @author Daniel Werner < danweetz@web.de >
 * @license Public domain
 * @link http://www.mediawiki.org/wiki/Extension:Variables
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['parserhook'][] = array(
	'path' => __FILE__,
	'name' => 'Variables',
	'version' => '1.3.1.1',
	'author' => array( 'Rob Adams', 'Tom Hempel', 'Xiloynaha', '[http://www.mediawiki.org/wiki/User:Danwe Daniel Werner]' ),
	'description' => 'Parser functions allowing to work with dynamic variables in an article scoped context',
	'url' => 'http://www.mediawiki.org/wiki/Extension:VariablesExtension',
);

// instead of 'ParserFirstCallInit' for <1.12 compatbility. Use Variables 2.0 if you have MW 1.12+
$wgExtensionFunctions[] = 'wfSetupVariables';

$wgHooks['LanguageGetMagic'][] = 'wfVariablesLanguageGetMagic';


class ExtVariables {

	var $mVariables = array();

	function __construct() {
		global $wgHooks;
		$wgHooks['ParserClearState'][] = &$this;
	}

	function onParserClearState( &$parser ) {
		$this->mVariables = array(); //remove all variables to avoid conflicts with job queue or Special:Import
		return true;
	}

	function vardefine( &$parser, $expr = '', $value = '' ) {
		$this->mVariables[$expr] = $value;
		return '';
	}

	function vardefineecho( &$parser, $expr = '', $value = '' ) {
		$this->mVariables[$expr] = $value;
		return $value;
	}

	function varf( &$parser, $expr = '', $defaultVal = '' ) {
		if ( isset( $this->mVariables[$expr] ) && $this->mVariables[$expr] != '' ) {
			return $this->mVariables[$expr];
		} else {
			return $defaultVal;
		}
	}

	function varexists( &$parser, $expr = '' ) {
		return array_key_exists( $expr, $this->mVariables );
	}
}

function wfSetupVariables() {
	global $wgParser, $wgExtVariables;

	$wgExtVariables = new ExtVariables;

	$wgParser->setFunctionHook( 'vardefine', array( &$wgExtVariables, 'vardefine' ) );
	$wgParser->setFunctionHook( 'vardefineecho', array( &$wgExtVariables, 'vardefineecho' ) );
	$wgParser->setFunctionHook( 'var', array( &$wgExtVariables, 'varf' ) );
	$wgParser->setFunctionHook( 'varexists', array( &$wgExtVariables, 'varexists' ) );
}

function wfVariablesLanguageGetMagic( &$magicWords, $langCode = 0 ) {
	require_once( dirname( __FILE__ ) . '/Variables.i18n.php' );
	foreach( efVariablesWords( $langCode ) as $word => $trans ) {
		$magicWords[$word] = $trans;
	}
	return true;
}
