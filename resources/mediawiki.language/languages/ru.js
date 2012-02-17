/**
 * Russian (Русский) language functions
 */

mediaWiki.language.convertPlural = function( count, forms ) {
	if ( forms.length === 2 ) {
		return count == 1 ? forms[0] : forms[1];		
	}
	forms = mediaWiki.language.preConvertPlural( forms, 3 );
	if ( count > 10 && Math.floor( ( count % 100 ) / 10 ) == 1 ) {
		return forms[2];
	}
	switch ( count % 10 ) {
		case 1:
			return forms[0];
		case 2:
		case 3:
		case 4:
			return forms[1];
		default:
			return forms[2];
	}
};

mediaWiki.language.convertGrammar = function( word, form ) {
	var grammarForms = mw.language.data[ 'ru' ].get( 'grammarForms' );
	if ( grammarForms && grammarForms[form] ) {
		return grammarForms[form][word] ;
	}
	switch ( form ) {
		case 'genitive': // родительный падеж
			if ( (  word.substr( word.length - 4 )  == 'вики' ) || (  word.substr( word.length - 4 ) == 'Вики' ) ) {
			}
			else if ( word.substr( word.length - 1 ) == 'ь' )
				word = word.substr(0, word.length - 1 ) + 'я';
			else if ( word.substr( word.length - 2 ) == 'ия' )
				word = word.substr(0, word.length - 2 ) + 'ии';
			else if ( word.substr( word.length - 2 ) == 'ка' )
				word = word.substr(0, word.length - 2 ) + 'ки';
			else if ( word.substr( word.length - 2 )  == 'ти' )
				word = word.substr(0, word.length - 2 ) + 'тей';
			else if ( word.substr( word.length - 2 ) == 'ды' )
				word = word.substr(0, word.length - 2 ) + 'дов';
			else if ( word.substr( word.length - 3 ) == 'ник' )
				word = word.substr(0, word.length - 3 ) + 'ника';
			break;
	}
	return word;
};
