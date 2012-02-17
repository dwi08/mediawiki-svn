module( 'mediawiki.language' );

test( '-- Initial check', function() {
	expect( 1 );
	ok( mw.language, 'mw.language defined' );
} );

 
mw.language.grammartest = function( options ) {
	var opt = $.extend({
		language: '',
		grammarForm: null,
		word: '',
		expected: '',
		description: ''
	}, options);
	// The test works only if the content language is opt.language
	// because it require [lang].js to be loaded.
	if( mw.config.get ( 'wgContentLanguage' ) === opt.language ) {
		test( opt.description, function() {
			expect( 1 );
			var langData = mw.language.data;
			var grammarForms = [];
			grammarForms[ opt.grammarForm ] = [] ;
			if ( langData[opt.language] === undefined ) {
				langData[opt.language] = new mw.Map();
			}else{
				grammarForms = langData[opt.language].get( 'grammarForms' );
			}
			grammarForms[opt.grammarForm][opt.word] = opt.expected ;
			langData[opt.language].set( 'grammarForms', grammarForms ); 
			equal( mw.language.convertGrammar(  opt.word, opt.grammarForm ), opt.expected, opt.description );
		} );
	}
}

mw.language.grammartest({
	language: 'en',
	word: 'pen',
	grammarForm: 'genitive',
	expected: 'pen\'s',
	description: 'Grammar test for English'
});
