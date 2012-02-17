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
		test( "-- Grammar Test for "+ opt.language, function() {
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

mw.language.grammartest({
	language: 'fi',
	test: [
		{ word: 'talo', grammarForm: 'genitive', expected: 'talon', description: 'Grammar test for Finnish, genitive case' },
		{ word: 'linux', grammarForm: 'genitive', expected: 'linuxin', description: 'Grammar test for Finnish, genitive case' },
		{ word: 'talo', grammarForm: 'elative', expected: 'talosta', description: 'Grammar test for Finnish, elative case' },
		{ word: 'pastöroitu', grammarForm: 'elative', expected: 'pastöroitusta', description: 'Grammar test for Finnish, elative case' },
		{ word: 'talo', grammarForm: 'partitive', expected: 'taloa', description: 'Grammar test for Finnish, partitive case' },
		{ word: 'talo', grammarForm: 'illative', expected: 'taloon', description: 'Grammar test for Finnish, illative case' },
		{ word: 'linux', grammarForm: 'inessive', expected: 'linuxissa', description: 'Grammar test for Finnish, inessive case' }
	]
});

mw.language.grammartest({
	language: 'ru',
	test: [
		{ word: 'честь', grammarForm: 'genitive', expected: 'честя', description: 'Grammar test for Russian, genitive case' },
		{ word: 'проведения', grammarForm: 'genitive', expected: 'проведении', description: 'Grammar test for Russian, genitive case' },
		{ word: 'Оснабрюка', grammarForm: 'genitive', expected: 'Оснабрюки', description: 'Grammar test for Russian, genitive case' },
		{ word: 'почти', grammarForm: 'genitive', expected: 'почтей', description: 'Grammar test for Russian, genitive case' },
		{ word: 'годы', grammarForm: 'genitive', expected: 'годов', description: 'Grammar test for Russian, genitive case' },
		{ word: 'песчаник', grammarForm: 'genitive', expected: 'песчаника', description: 'Grammar test for Russian, genitive case' }
	]
}); 
