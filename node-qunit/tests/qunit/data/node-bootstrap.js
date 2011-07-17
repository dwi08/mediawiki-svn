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
GLOBAL.startUp = function() {
	mw.config = new mw.Map( false );
}

require( '../../../resources/mediawiki/mediawiki.js'  );

require( '../../../resources/jquery/jquery.client.js' );
require( '../../../resources/mediawiki.page/mediawiki.page.startup.js' );

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

require( './testrunner.js' );
require( './defineTestCallback.js' );

require( '../suites/resources/mediawiki/mediawiki.js');

console.warn( "MediaWiki bootstrapping done" );


