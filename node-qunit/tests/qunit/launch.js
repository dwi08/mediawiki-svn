var testrunner = require( 'qunit' );
testrunner.options.coverage = false;

testrunner.run({
	deps:  './data/node-bootstrap.js',
	code:  "../../resources/mediawiki/mediawiki.js",
	tests: "./suites/resources/mediawiki/mediawiki.js"
}, function( report ) {
	console.warn( report );
});
