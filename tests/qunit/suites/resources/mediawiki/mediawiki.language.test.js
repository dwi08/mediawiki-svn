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
				var langData = mw.language.data;
				var grammarForms = [];
				if ( langData[opt.language] === undefined ) {
					langData[opt.language] = new mw.Map();
				}else{
					grammarForms = langData[opt.language].get( 'grammarForms' );
				}
				if ( grammarForms[ opt.test[i].grammarForm ] === undefined ) {
					grammarForms[ opt.test[i].grammarForm ] = [] ;
				}
				grammarForms[opt.test[i].grammarForm][opt.test[i].word] = opt.test[i].expected ;
				langData[opt.language].set( 'grammarForms', grammarForms ); 
				equal( mw.language.convertGrammar(  opt.test[i].word, opt.test[i].grammarForm ), opt.test[i].expected, opt.test[i].description );
			}
		} );
	}
}

mw.language.grammartest({
	language: 'en',
	test: [ 
		{ word: 'pen', grammarForm: 'genitive', expected: 'pen\'s', description: 'Grammar test for English' }
	]
});

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
