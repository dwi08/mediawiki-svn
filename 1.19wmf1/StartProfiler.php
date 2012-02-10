<?php
# WARNING: This file is publically viewable on the web. Do not put private data here.
#
$rand = mt_rand(0, 0x7fffffff);
$host = @$_SERVER['HTTP_HOST'];

/*if ( ( !($rand % 50) && $host == 'en.wikipedia.org' ) || 
     ( !($rand % 50) && $host == 'commons.wikimedia.org') ||
     ( !($rand % 50) && $host == 'de.wikipedia.org') ||
     ( !($rand % 50) && $host == 'es.wikipedia.org') ||
     ( !($rand % 1)  && $host == 'test.wikipedia.org' ) || 
     (                  $host == 'zh.wikipedia.org' ) || 
     ( !($rand % 10) && $host == 'ja.wikipedia.org' )
) {*/
if ( @$_SERVER['REQUEST_URI'] == '/w/index.php?title=United_States&action=submit' ) {
	require_once( dirname(__FILE__).'/includes/profiler/ProfilerSimpleUDP.php' );
	$wgProfiler = new ProfilerSimpleUDP( array() );
	$wgProfiler->setProfileID( 'bigpage' );
} elseif (@defined($_REQUEST['forceprofile'])) {
    require_once( dirname(__FILE__).'/includes/profiler/ProfilerSimpleText.php' );
    $wgProfiler = new ProfilerSimpleText( array() );
    $wgProfiler->setProfileID( 'forced' );
} elseif (@defined($_REQUEST['forcetrace'])) {
    require_once( dirname(__FILE__).'/includes/profiler/ProfilerSimpleTrace.php' );
    $wgProfiler = new ProfilerSimpleTrace( array() );
} elseif ( strpos( @$_SERVER['REQUEST_URI'], '/w/thumb.php' ) !== false ) {
  	require_once( dirname(__FILE__).'/includes/profiler/ProfilerSimpleUDP.php' );
	$wgProfiler = new ProfilerSimpleUDP( array() );
	$wgProfiler->setProfileID( 'thumb' );
} elseif ( $host == 'test2.wikipedia.org' ) {
  	require_once( dirname(__FILE__).'/includes/profiler/ProfilerSimpleUDP.php' );
	$wgProfiler = new ProfilerSimpleUDP( array() );
	$wgProfiler->setProfileID( 'test2' );
} elseif ( !( $rand % 50 ) ) {
  	require_once( dirname(__FILE__).'/includes/profiler/ProfilerSimpleUDP.php' );
	$wgProfiler = new ProfilerSimpleUDP( array() );
	/*
	if ( $host == 'en.wikipedia.org' ) {
		$wgProfiler->setProfileID( 'enwiki' );
	} elseif ( $host == 'de.wikipedia.org' ) {
		$wgProfiler->setProfileID( 'dewiki' );
	} elseif ( $host == 'zh.wikipedia.org' ) {
		$wgProfiler->setProfileID( 'zhwiki' );
	} elseif ( $host == 'flaggedrevs.labs.wikimedia.org' ) {
		$wgProfiler->setProfileID( 'flaggedrevs' );
	} else {
		$wgProfiler->setProfileID( 'others' );
	}*/
	if ( php_sapi_name() == 'cli' ) {
		$wgProfiler->setProfileID( 'cli' );
	} else {
		$wgProfiler->setProfileID( 'all' );
	}
	#$wgProfiler->setMinimum(5 /* seconds */);
}
elseif ( defined( 'MW_FORCE_PROFILE' ) ) {
	require_once( dirname(__FILE__).'/includes/profiler/Profiler.php' );
	$wgProfiler = new Profiler( array() );
} else {
	require_once( dirname(__FILE__).'/includes/profiler/ProfilerStub.php' );
}



