<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

class ExemptFromAccountCreationThrottleHooks {

	public static function checkIP( $ip ) {
		$testIP = '::2';
		
		if ( $ip == $testIP ) {
			wfDebugLog( 'CACT', "NOT TRHOTTLED: $ip is my IP address\n" );
			return false;
		} else {
			wfDebugLog( 'CACT', "throttled: $ip is my IP address\n" );
			return true;
		}
		
		
		
		
		return false;
	}

}
	

