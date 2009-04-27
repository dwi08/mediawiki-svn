<?php

/**
 * Mibbit
 *
 * Integrates the Mibbit web IRC client into a special page.
 *
 * @addtogroup Extensions
 *
 * @link http://www.mediawiki.org/wiki/Extension:Mibbit
 *
 * @author MinuteElectron <minuteelectron@googlemail.com>
 * @copyright Copyright © 2008 - 2009 Robert Leverington.
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

// If this is run directly from the web die as this is not a valid entry point.
if( !defined( 'MEDIAWIKI' ) ) die( 'Invalid entry point.' );

// Extension credits.
$wgExtensionCredits[ 'specialpage' ][] = array(
	'path'           => __FILE__,
	'name'           => 'Mibbit',
	'description'    => 'Adds a special page used to chat in real time with other wiki users.',
	'descriptionmsg' => 'mibbit-desc',
	'author'         => 'Robert Leverington',
	'url'            => 'http://www.mediawiki.org/wiki/Extension:Mibbit',
	'version'        => '1.3.0',
);

// Register special page.
$dir = dirname( __FILE__ ) . '/';
$wgSpecialPages[ 'Mibbit' ] = 'Mibbit';
$wgSpecialPageGroups[ 'Mibbit' ] = 'wiki';
$wgAutoloadClasses[ 'Mibbit' ] = $dir . 'Mibbit_body.php';

// Extension messages.
$wgExtensionMessagesFiles[ 'Mibbit' ] =  $dir . 'Mibbit.i18n.php';

// Default configuration.
$wgMibbitServer          = '';
$wgMibbitChannel         = '';
$wgMibbitExtraParameters = array( 'noServerMotd' => 'true' );

// Default permissions.
$wgAvailableRights[] = 'mibbit';
$wgGroupPermissions[ '*'     ][ 'mibbit' ] = false;
$wgGroupPermissions[ 'user'  ][ 'mibbit' ] = true;
$wgGroupPermissions[ 'sysop' ][ 'mibbit' ] = true;
