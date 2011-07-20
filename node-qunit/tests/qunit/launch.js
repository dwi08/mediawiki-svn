var testrunner = require( 'qunit' );
testrunner.options.coverage = false;

testrunner.run({
	deps:  './data/node-bootstrap.js',
	code:  "../../resources/mediawiki/mediawiki.js",
	tests: [
	"./suites/resources/mediawiki/mediawiki.js",
	"./suites/resources/mediawiki/mediawiki.user.js",
	"./suites/resources/jquery/jquery.client.js",
	"./suites/resources/jquery/jquery.mwPrototypes.js",
//	"./suites/resources/mediawiki/mediawiki.util.js",
//	"./suites/resources/jquery/jquery.autoEllipsis.js",
	"./suites/resources/jquery/jquery.byteLength.js",
//	"./suites/resources/jquery/jquery.byteLimit.js",
	"./suites/resources/jquery/jquery.colorUtil.js",
	"./suites/resources/jquery/jquery.getAttrs.js",
	"./suites/resources/jquery/jquery.localize.js",
	"./suites/resources/jquery/jquery.tabIndex.js",
	"./suites/resources/jquery/jquery.tablesorter.test.js",
	"./suites/resources/mediawiki/mediawiki.Title.js",
	"./suites/resources/mediawiki.special/mediawiki.special.recentchanges.js",
	]
});
