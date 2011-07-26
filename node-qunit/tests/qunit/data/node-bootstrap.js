/**
 * MediaWiki bootstraper for MediaWiki qunit test suite under node.js
 *
 * Copyright © 2011 - Ashar Voultoiz <hashar@free.fr>
 *
 */

console.warn( "Loading MediaWiki bootstraper" );

console.warn( ".. initializing DOM & jQuery" );
  GLOBAL.jsdom    = require( 'jsdom' );
  GLOBAL.document = jsdom.jsdom();
  GLOBAL.window   = document.createWindow();
  GLOBAL.$      = require( 'jquery' );
  GLOBAL.jQuery = require( 'jquery' ).create( window );
console.warn( "... Testing jQuery and DOM" );
  console.warn( "  Running -> $( \"<h1>test passes</h1>\" ).appendTo( \"body\" );" );
  $( "<h1>test passes</h1>" ).appendTo( "body" );
  console.warn( "  Result  -> " + $("body").html() )
console.warn( "Looks good." );

console.warn( "Now really bootstraping ..." );
$( '<h1 id="qunit-header">MediaWiki JavaScript Test Suite</h1>'
	+ '<h2 id="qunit-banner"></h2>'
	+ '<div id="qunit-testrunner-toolbar">'
	+ '<p><a href="http://www.mediawiki.org/wiki/Manual:JavaScript_unit_testing">See testing documentation on mediawiki.org</a></p>'
	+ '</div>'
	+ '<h2 id="qunit-userAgent"></h2>'
	+ '<ol id="qunit-tests"></ol>'
	+'<!-- Scripts inserting stuff here shall remove it themselfs! -->'
	+'<div id="content"></div>'
 ).appendTo( "body" );

GLOBAL.startUp = function() {
	GLOBAL.mw.config = new mw.Map( true );
}
require( '../../../resources/mediawiki/mediawiki.js'  );
GLOBAL.mediaWiki = window.mediaWiki;

require( '../../../resources/jquery/jquery.client.js' );
require( '../../../resources/mediawiki.page/mediawiki.page.startup.js' );

require( '../../../resources/jquery/jquery.cookie.js' );
require( '../../../resources/mediawiki/mediawiki.user.js' );

require( '../../../resources/jquery/jquery.messageBox.js' );
require( '../../../resources/jquery/jquery.mwPrototypes.js' );
require( '../../../resources/mediawiki/mediawiki.util.js' );

require( '../../../resources/jquery/jquery.checkboxShiftClick.js' );
require( '../../../resources/jquery/jquery.makeCollapsible.js'    );
require( '../../../resources/jquery/jquery.placeholder.js'        );
require( '../../../resources/mediawiki.page/mediawiki.page.ready.js' );

console.warn( "Setting MediaWiki user options" );
// MW: user.options
mw.user.options.set({"skin": "vector"});

// MW: Non-default modules
require( '../../../resources/jquery/jquery.autoEllipsis.js' );
require( '../../../resources/jquery/jquery.byteLength.js' );
require( '../../../resources/jquery/jquery.byteLimit.js' );
require( '../../../resources/jquery/jquery.colorUtil.js' );
require( '../../../resources/jquery/jquery.getAttrs.js' );
require( '../../../resources/jquery/jquery.localize.js' );
require( '../../../resources/jquery/jquery.tabIndex.js' );
require( '../../../resources/jquery/jquery.tablesorter.js' );
require( '../../../resources/mediawiki/mediawiki.Title.js' );
require( '../../../resources/mediawiki.special/mediawiki.special.js' );
require( '../../../resources/mediawiki.special/mediawiki.special.recentchanges.js' );



require( './testrunner.js' );

// jQuery might have been extended by the above requires.
// So we have to reinit the $ shortcut:
GLOBAL.$ = GLOBAL.jQuery;

console.warn( "MediaWiki bootstrapping done." + "\n" );

