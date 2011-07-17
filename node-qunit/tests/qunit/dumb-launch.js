var testrunner = require( 'qunit' );

testrunner.options.coverage = false;

testrunner.run({
	code:  "./dumb-code.js",
	tests: "./dumb-tests.js",
}, function( report ){
	console.warn( report );
}
);
