<?php
// This template file contains variables that should be localized.
// The line (specifying the location of the PHP/PEAR libraries) must be
// first. Moving it to a position later in the file will almost certainly
// cause an error.

// In order to use this file, first copy it to LocalSeleniumSettings.php.
// Then edit the information to conform to the local environment. You
// will almost certainly have to uncomment the line set_include_path ... and
// change the string 'PEAR' to the path to your PEAR library, e.g.,
// '/usr/share/php/PEAR' for a Debian based Linux system.
// The edited file must appear in the same directory as does RunSeleniumTests.php.

// include path. Set 'PEAR" to '/path/to/PEAR/library'

// URL: http://localhost/tests/RunSeleniumTests.php
#set_include_path( get_include_path() . PATH_SEPARATOR . 'PEAR' );

// Hostname of selenium server
$wgSeleniumTestsSeleniumHost = 'grid.tesla.usability.wikimedia.org';

// URL of the wiki to be tested. Consult web server configuration.
$wgSeleniumTestsWikiUrl = 'http://prototype.wikimedia.org/mwe-gadget-testing';

// Port used by selenium server (optional - default is 4444)
$wgSeleniumServerPort = 4444;

// Wiki login. Used by Selenium to log onto the wiki
$wgSeleniumTestsWikiUser      = 'Wikisysop';
$wgSeleniumTestsWikiPassword  = 'password';

// Common browsers on Windows platform. Modify for other platforms or
// other Windows browsers
// Use the *chrome handler in order to be able to test file uploads
// further solution suggestions: http://www.brokenbuild.com/blog/2007/06/07/testing-file-uploads-with-selenium-rc-and-firefoxor-reducing-javascript-security-in-firefox-for-fun-and-profit/
// $wgSeleniumTestsBrowsers['firefox']   = '*firefox c:\\Program Files (x86)\\Mozilla Firefox\\firefox.exe';
$wgSeleniumTestsBrowsers['firefox']   = '*firefox /usr/bin/firefox';
$wgSeleniumTestsBrowsers['iexplorer'] = '*iexploreproxy';
$wgSeleniumTestsBrowsers['opera']   = '*chrome /usr/bin/opera';

// Actually, use this browser
$wgSeleniumTestsUseBrowser = 'firefox';

// Set command line mode
$wgSeleniumTestsRunMode = 'cli';

// List of tests to be included by default
$wgSeleniumTestIncludes = array(
	'selenium_tests/EmbedPlayerLoadsTest.php'
);
?>
