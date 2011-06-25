<?php
if (!defined( 'MEDIAWIKI' )) die( "Not an entry point" );

/**
 * Pretty much based on UppercaseCollation from core.
 *
 * Collation that is case-insensitive, and allows specify
 * custom 'first-character' headings for category pages.
 */
class CustomHeaderCollation extends Collation {

	// The basic idea is we store the sortkey as three parts A^B^C
	// A is the capitalized first letter of the header it falls under.
	// B is the is the header it falls under (In whatever case the user enters)
	// C is the rest of the sortkey.
	//
	// The user enters something like [[category:some cat|^my header^foo]]
	// which gets turned into "^my header^foo\n<page name>"
	// which we turn into "M^my header^FOO\n<PAGE NAME>"
	function getSortKey( $string ) {
		global $wgContLang;
		// UppercaseCollation uses an EN lang object always instead of content lang.
		// I'm not sure why. To me it makes more sense to use $wgContLang.
		// There's minnor differences in some languages (like Turkish)

		$matches = array();
		if ( preg_match( '/^\^([^\n^]*)\^(.*)$/Ds', $string, $matches ) ) {
			if ( $matches[1] === '' ) $matches[1] = ' ';
			$part1 = $wgContLang->firstChar( $wgContLang->uc( $matches[1] ) );
			$part2 = $matches[1];
			$part3 = $wgContLang->uc( $matches[2] );

		} else {
			// Ordinay sortkey, no header info.
			$part3 = $wgContLang->uc( $string );
			$part1 = $part2 = $wgContLang->firstChar( $part3 );
		}

		return $part1 . '^' . $part2 . '^' . $part3;
	}

	function getFirstLetter( $string ) {
			global $wgContLang;

			# Stolen from UppercaseCollation
			# not sure when this could actually happen.
			if ( $string[0] === "\0" ) {
				$string = substr( $string, 1 );
			}

			$m = array();
			if ( preg_match( '/^[^\n^]*\^([^\n^]*)\^/', $string, $m ) ) {
				return $m[1];
			} else {
				// Probably shouldn't happen.
				return $wgContLang->ucfirst( $wgContLang->firstChar( $string ) );
			}
	}
}
