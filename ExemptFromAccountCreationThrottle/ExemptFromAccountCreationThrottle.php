<?php
/**
 * @copyright Copyright © 2011 Raimond Spekking
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'ExemptFromAccountCreationThrottle',
	'url' => 'http://www.mediawiki.org/wiki/Extension:ExemptFromAccountCreationThrottle',
	'author' => 'Raimond Spekking',
	'descriptionmsg' => 'exemptfromaccountcreationthrottle-desc',
);

$dir = dirname( __FILE__ ) . '/';

$wgAutoloadClasses['SpecialExemptFromAccountCreationThrottle'] = $dir . 'SpecialExemptFromAccountCreationThrottle.php';
$wgAutoloadClasses['ExemptFromAccountCreationThrottleHooks'] = $dir . 'ExemptFromAccountCreationThrottle.hooks.php';

$wgSpecialPages['ExemptFromAccountCreationThrottle'] = 'SpecialExemptFromAccountCreationThrottle';
$wgSpecialPageGroups['ExemptFromAccountCreationThrottle'] = 'other';

$wgExtensionMessagesFiles['ExemptFromAccountCreationThrottle'] = $dir . 'ExemptFromAccountCreationThrottle.i18n.php';
$wgExtensionAliasesFiles['ExemptFromAccountCreationThrottle'] = $dir . 'ExemptFromAccountCreationThrottle.alias.php';

$wgHooks['exemptFromAccountCreationThrottle'][] = 'ExemptFromAccountCreationThrottleHooks::checkIP';
