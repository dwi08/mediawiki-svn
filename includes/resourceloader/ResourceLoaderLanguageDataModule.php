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
 * ResourceLoader module for populating language specific data.
 */
class ResourceLoaderLanguageDataModule extends ResourceLoaderModule {

	/**
	 * Get the grammer forms for the site content language.
	 *
	 * @return array
	 */
	protected function getSiteLangGrammarForms() {
		global $wgContLang;
		return $wgContLang->getGrammarForms();
	}

	/**
	 * @param $context ResourceLoaderContext
	 * @return string Javascript code
	 */
	public function getScript( ResourceLoaderContext $context ) {
		global $wgContLang;

		return Xml::encodeJsCall( 'mw.language.setData', array(
			$wgContLang->getCode(),
			$this->getSiteLangGrammarForms()
		) );
	}

	/**
	 * @param $context ResourceLoaderContext
	 * @return array|int|Mixed
	 */
	public function getModifiedTime( ResourceLoaderContext $context ) {
		global $wgCacheEpoch;

		/**
		 * @todo FIXME: This needs to change whenever the array created by
		 * $wgContLang->getGrammarForms() changes. Which gets its data from
		 * $wgGrammarForms, which (for standard installations) comes from LocalSettings
		 * and $wgCacheEpoch would cover that. However there's two three problems:
		 *
		 * 1) $wgCacheEpoch is not meant for this use.
		 * 2) If $wgInvalidateCacheOnLocalSettingsChange is set to false,
		 *    $wgCacheEpoch will not be raised if LocalSettings is modified (see #1).
		 * 3) $wgGrammarForms can be set from anywhere. For example on WMF it is set
		 *    by the WikimediaMessages extension. Other farms might set it form
		 *    their 'CommonSettings.php'-like file or something (see #1).
		 *
		 * Possible solutions:
		 * - Store grammarforms in the language object cache instead of directly
		 *   from the global everytime. Then use $wgContLang->getLastModified().
		 * - Somehow monitor the value of $wgGrammarForms.
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
