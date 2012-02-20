<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @author Santhosh Thottingal
 * @author Timo Tijhof
 */

/**
 * ResourceLoader module for generating language specific scripts/css.
 */
class ResourceLoaderLanguageModule extends ResourceLoaderModule {

	/**
	 * Get the grammer forms for the site content language.
	 *
	 * @return Array
	 */
	protected function getSiteLangGrammarForms( ) {
		global $wgContLang;
		return  $wgContLang->getGrammarForms();
	}
	/**
	 * @param $context ResourceLoaderContext
	 * @return string Javascript code
	 */
	public function getScript( ResourceLoaderContext $context ) {
		global $wgContLang;
		$code = Xml::encodeJsVar( $wgContLang->getCode() );
		$forms = Xml::encodeJsVar( $this->getSiteLangGrammarForms() );

		$js =
<<<JAVASCRIPT
var langCode = $code,
langData = mw.language.data;
if ( langData[langCode] === undefined ) {
	langData[langCode] = new mw.Map();
}
langData[langCode].set( "grammarForms", $forms );
JAVASCRIPT;

		return $js;
	}
	/**
	 * @param $context ResourceLoaderContext
	 * @return array|int|Mixed
	 */
	public function getModifiedTime( ResourceLoaderContext $context ) {
		global $wgCacheEpoch;
		/*
		global $wgContLang, $wgCacheEpoch;
		return max( $wgCacheEpoch, $wgContLang->getLastModified() );
		*/

		return $wgCacheEpoch;
	}
	/**
	 * @return array
	 */
	public function getDependencies() {
		return array( 'mediawiki.language' );
	}
}

