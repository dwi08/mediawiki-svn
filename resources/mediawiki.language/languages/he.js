/**
 * Hebrew (עברית) language functions
 */

mediaWiki.language.convertPlural = function( count, forms ) {
	forms = mediaWiki.language.preConvertPlural( forms, 3 );
	if ( count == 1 ) {
		return forms[0];
	}
	if ( count == 2 && forms[2] ) {
		return forms[2];
	}
	return forms[1];
};

mediaWiki.language.convertGrammar = function( word, form ) {
	var grammarForms = mw.config.get( 'wgGrammarForms' );
	if ( grammarForms &&  grammarForms['he'] && grammarForms['he'][form] ) {
		return grammarForms[form][word] ;
	}
	switch ( form ) {
		case 'prefixed':
		case 'תחילית':
			// Duplicate the "Waw" if prefixed
			if ( word.substr( 0, 1 ) === "ו" && word.substr( 0, 2 ) !== "וו" ) {
				word = "ו" + word;
			}

			// Remove the "He" if prefixed
			if ( word.substr( 0, 1 ) === "ה" ) {
				word = word.substr( 1, word.length );
			}

			// Add a hyphen (maqaf) if non-Hebrew letters
			if (  word.substr( 0, 1 ) < "א" ||  word.substr( 0, 1 ) > "ת" ) {
				word = "־" + word;
			}
	}
	return word;
};
