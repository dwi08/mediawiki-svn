<?php

/**
 * Provides a means for users to verify their identity on this wiki to a remote service.
 * 
 * For example: imagine if you wanted to associate a chat service with your wiki, and users should have the same names on 
 * the chat service as they do on the wiki. This isn't as complicated of a case as OpenID or oAuth, we just want two well-known services
 * run by the same organization to verify identity. 
 * 
 * This could also be done with a shared secret, but this way we can avoid having to distribute that secret or keep it up to date, and there
 * aren't any consequences if the secret is discovered.
 * 
 * Documentation:	 		http://www.mediawiki.org/wiki/Extension:IdentityApi
 * Source code:             http://svn.wikimedia.org/viewvc/mediawiki/trunk/extensions/IdentityApi
 *
 * @author Neil Kandalgaonkar <neilk@wikimedia.org>
 * @license GPL v2 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

if ( version_compare( $wgVersion, '1.17', '<' ) ) {
	die( 'This extension requires MediaWiki 1.17 or above.' );
}

if ( ! $wgSessionsInMemcached ) {
	die( 'This extension requires you to be storing sessions in Memcached (for now)' );
}


define( 'IdentityApi_VERSION', '0.1' );

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'IdentityApi',
	'version' => IdentityApi_VERSION,
	'author' => array(
		'[http://www.mediawiki.org/wiki/User:NeilK NeilK]',
	),
	'url' => 'http://www.mediawiki.org/wiki/Extension:IdentityApi',
	'descriptionmsg' => 'identityapi-desc'
);

$wgExtensionMessagesFiles['IdentityApi'] = dirname( __FILE__ ) . '/IdentityApi.i18n.php';

$wgAutoloadClasses['IdentityApi'] = dirname( __FILE__ ) . '/IdentityApi.class.php';

$wgAutoloadClasses['ApiQueryVerifyIdentity'] = dirname( __FILE__ ) . '/api/ApiQueryVerifyIdentity.php';
$wgAPIModules['verifyidentity'] = 'ApiQueryVerifyIdentity';
$wgAutoloadClasses['ApiQueryIdentity'] = dirname( __FILE__ ) . '/api/ApiQueryIdentity.php';
$wgAPIModules['identity'] = 'ApiQueryIdentity';


$wgIdentityApiConfig = array(
	'timeoutSeconds' => 60 * 60 * 24  // one day
);
