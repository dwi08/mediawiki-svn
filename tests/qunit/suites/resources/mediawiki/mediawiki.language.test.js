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
	// because it requires [lang].js to be loaded.
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
		{ word: 'Wikipedia', grammarForm: 'תחילית', expected: '־Wikipedia', description: 'Grammar test for Hebrew, Add a hyphen (maqaf) before non-Hebrew letters' }
		{ word: '1995', grammarForm: 'תחילית', expected: '־1995', description: 'Grammar test for Hebrew, Add a hyphen (maqaf) before numbers' }
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
		{ word: 'тесть', grammarForm: 'genitive', expected: 'тестя', description: 'Grammar test for Russian, genitive case' },
		{ word: 'привилегия', grammarForm: 'genitive', expected: 'привилегии', description: 'Grammar test for Russian, genitive case' },
		{ word: 'установка', grammarForm: 'genitive', expected: 'установки', description: 'Grammar test for Russian, genitive case' },
		{ word: 'похоти', grammarForm: 'genitive', expected: 'похотей', description: 'Grammar test for Russian, genitive case' },
		{ word: 'доводы', grammarForm: 'genitive', expected: 'доводов', description: 'Grammar test for Russian, genitive case' },
		{ word: 'песчаник', grammarForm: 'genitive', expected: 'песчаника', description: 'Grammar test for Russian, genitive case' }
	]
}); 
