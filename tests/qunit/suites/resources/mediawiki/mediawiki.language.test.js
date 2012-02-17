module( 'mediawiki.language' );

test( '-- Initial check', function() {
	expect( 1 );
	ok( mw.language, 'mw.language defined' );
} );

 
mw.language.grammartest = function( options ) {
	var opt = $.extend({
		language: '',
		test: [],
	}, options);
	// The test works only if the content language is opt.language
	// because it require [lang].js to be loaded.
	if( mw.config.get ( 'wgContentLanguage' ) === opt.language ) {
		test( "Grammar Test", function() {
			expect( opt.test.length);
			for ( var i= 0 ; i < opt.test.length; i++ ) {
				equal( mw.language.convertGrammar(  opt.test[i].word, opt.test[i].grammarForm ), opt.test[i].expected, opt.test[i].description );
			}
		} );
	}
}

mw.language.grammartest({
	language: 'bs',
	test: [
		{ word: 'word', grammarForm: 'instrumental', expected: 's word', description: 'Grammar test for Bosnian, instrumental case' },
		{ word: 'word', grammarForm: 'lokativ', expected: 'o word', description: 'Grammar test for Bosnian, lokativ case' }
	]
}); 

mw.language.grammartest({
	language: 'he',
	test: [
		{ word: "ויקיפדיה", grammarForm: 'prefixed', expected: "וויקיפדיה", description: 'Grammar test for Hebrew, Duplicate the "Waw" if prefixed' },
		{ word: "וולפגנג", grammarForm: 'prefixed', expected: "וולפגנג", description: 'Grammar test for Hebrew, Duplicate the "Waw" if prefixed, but not if it is already duplicated.' },
		{ word: "הקובץ", grammarForm: 'prefixed', expected: "קובץ", description: 'Grammar test for Hebrew, Remove the "He" if prefixed' },
		{ word: 'wikipedia', grammarForm: 'תחילית', expected: '־wikipedia', description: 'Grammar test for Hebrew, Add a hyphen (maqaf) if non-Hebrew letters' }
	]
}); 

mw.language.grammartest({
	language: 'hsb',
	test: [
		{ word: 'word', grammarForm: 'instrumental', expected: 'z word', description: 'Grammar test for Upper Sorbian, instrumental case' },
		{ word: 'word', grammarForm: 'lokatiw', expected: 'wo word', description: 'Grammar test for Upper Sorbian, lokatiw case' }
	]
}); 

mw.language.grammartest({
	language: 'hy',
	test: [
		{ word: 'Մաունա', grammarForm: 'genitive', expected: 'Մաունայի', description: 'Grammar test for Armenian, genitive case' },
		{ word: 'հետո', grammarForm: 'genitive', expected: 'հետոյի', description: 'Grammar test for Armenian, genitive case' },
		{ word: 'գիրք', grammarForm: 'genitive', expected: 'գրքի', description: 'Grammar test for Armenian, genitive case' },
		{ word: 'ժամանակի', grammarForm: 'genitive', expected: 'ժամանակիի', description: 'Grammar test for Armenian, genitive case' }
	]
}); 
