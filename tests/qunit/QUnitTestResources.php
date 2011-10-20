<?php

return array(

	/* Resources of QUnit test suite for MediaWiki core */

	'mediawiki.tests.qunit.suites' => array(
		'scripts' => array(
			'tests/qunit/suites/resources/jquery/jquery.autoEllipsis.test.js',
			'tests/qunit/suites/resources/jquery/jquery.byteLength.test.js',
			'tests/qunit/suites/resources/jquery/jquery.byteLimit.test.js', // mw-config def
			'tests/qunit/suites/resources/jquery/jquery.client.test.js',
			'tests/qunit/suites/resources/jquery/jquery.colorUtil.test.js',
			'tests/qunit/suites/resources/jquery/jquery.getAttrs.test.js',
			'tests/qunit/suites/resources/jquery/jquery.localize.test.js',
			'tests/qunit/suites/resources/jquery/jquery.mwExtension.test.js',
			'tests/qunit/suites/resources/jquery/jquery.tabIndex.test.js',
			# jquery.tablesorter.test.js: Broken
			#'tests/qunit/suites/resources/jquery/jquery.tablesorter.test.js', // mw-config def
			'tests/qunit/suites/resources/jquery/jquery.textSelection.test.js',
			# mediawiki.test.js: Broken due to relative path to /data/defineTestCallback.js
			#'tests/qunit/suites/resources/mediawiki/mediawiki.test.js',
			'tests/qunit/suites/resources/mediawiki/mediawiki.title.test.js', // mw-config def
			'tests/qunit/suites/resources/mediawiki/mediawiki.user.test.js',
			'tests/qunit/suites/resources/mediawiki/mediawiki.util.test.js',
			'tests/qunit/suites/resources/mediawiki.special/mediawiki.special.recentchanges.test.js',

			// mw-config def: Contains mw.config defaults, fix when /qunit/index.html is removed
		),
		'dependencies' => array(
			'jquery.autoEllipsis',
			'jquery.byteLength',
			'jquery.byteLimit',
			'jquery.client',
			'jquery.colorUtil',
			'jquery.getAttrs',
			'jquery.localize',
			'jquery.mwExtension',
			'jquery.tabIndex',
			'jquery.tablesorter',
			'jquery.textSelection',
			'mediawiki',
			'mediawiki.Title',
			'mediawiki.user',
			'mediawiki.util',
			'mediawiki.special.recentchanges',
		),
	)
);
