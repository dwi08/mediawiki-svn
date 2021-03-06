<?php
/**
 * MediaWiki PageTriage extension
 * http://www.mediawiki.org/wiki/Extension:PageTriage
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * This program is distributed WITHOUT ANY WARRANTY.
 */

/**
 * This file loads everything needed for the PageTriage extension to function.
 *
 * @file
 * @ingroup Extensions
 * @author Ryan Kaldari
 */

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install this extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/PageTriage/PageTriage.php" );
EOT;
	exit( 1 );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'PageTriage',
	'version' => '0.1',
	'url' => 'https://www.mediawiki.org/wiki/Extension:PageTriage',
	'author' => '',
	'descriptionmsg' => 'pagetriage-desc',
);

$dir = dirname( __FILE__ ) . '/';

$wgExtensionMessagesFiles['PageTriage'] = $dir . 'PageTriage.i18n.php';
$wgExtensionMessagesFiles['PageTriageAlias'] = $dir . 'PageTriage.alias.php';

$wgAutoloadClasses['SpecialPageTriage'] = $dir . 'SpecialPageTriage.php';
$wgSpecialPages['PageTriage'] = 'SpecialPageTriage';
$wgSpecialPageGroups['PageTriage'] = 'changes';

$wgAutoloadClasses['SpecialPageTriageList'] = $dir . 'SpecialPageTriageList.php';
$wgSpecialPages['PageTriageList'] = 'SpecialPageTriageList';
$wgSpecialPageGroups['PageTriageList'] = 'changes';

$wgAutoloadClasses['ApiQueryPageTriage'] = $dir . 'api/ApiQueryPageTriage.php';

// api modules
$wgAPIModules['pagetriage'] = 'ApiQueryPageTriage';

$wgHooks['LoadExtensionSchemaUpdates'][] = 'efPageTriageSchemaUpdates';

/**
 * @param $updater DatabaseUpdater
 * @return bool
 */
function efPageTriageSchemaUpdates( $updater = null ) {
	$base = dirname( __FILE__ ) . "/sql";
	if ( $updater === null ) {
		global $wgDBtype, $wgExtNewTables, $wgExtNewFields;
		if ( $wgDBtype == 'mysql' ) {
			$wgExtNewTables[] = array( 'pagetriage', $base . '/PageTriage.sql' );
		}
	} else {
		if ( $updater->getDB()->getType() == 'mysql' ) {
			$updater->addExtensionTable( 'pagetriage', "$base/PageTriage.sql" );
		}
	}
	return true;
}

// Register ResourceLoader modules
$wgResourceModules['ext.pageTriage.core'] = array(
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'PageTriage',
	'scripts' => 'ext.pageTriage.core.js'
);
