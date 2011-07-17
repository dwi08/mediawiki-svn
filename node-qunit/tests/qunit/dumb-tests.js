QUnit.module('dumb-code.js');

test( "Really basic example", function() {
	ok( true, "looks fine" );
	var value = "hello world";
	equals( "hello world", value, "Variable assignement works" );
});

