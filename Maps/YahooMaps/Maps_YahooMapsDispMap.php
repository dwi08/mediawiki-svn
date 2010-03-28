<?php

/**
 * Class for handling the display_map parser function with Yahoo! Maps
 *
 * @file Maps_YahooMapsDispMap.php
 * @ingroup MapsYahooMaps
 *
 * @author Jeroen De Dauw
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

class MapsYahooMapsDispMap extends MapsBaseMap {
	
	public $serviceName = MapsYahooMaps::SERVICE_NAME;
	
	/**
	 * @see MapsBaseMap::setFormInputSettings()
	 *
	 */
	protected function setMapSettings() {
		global $egMapsYahooMapsZoom, $egMapsYahooMapsPrefix;
		
		$this->elementNamePrefix = $egMapsYahooMapsPrefix;
		$this->defaultZoom = $egMapsYahooMapsZoom;
	}
	
	/**
	 * @see MapsBaseMap::doMapServiceLoad()
	 *
	 */
	protected function doMapServiceLoad() {
		global $egYahooMapsOnThisPage;
		
		MapsYahooMaps::addYMapDependencies( $this->output );
		$egYahooMapsOnThisPage++;
		
		$this->elementNr = $egYahooMapsOnThisPage;
	}
	
	/**
	 * @see MapsBaseMap::addSpecificMapHTML()
	 *
	 */
	public function addSpecificMapHTML() {
		global $wgOut;
		
		$this->output .= Html::element(
			'div',
			array(
				'id' => $this->mapName,
				'width' => $this->width,
				'height' => $this->height
			),
			null
		);
		
		$wgOut->addInlineScript( <<<EOT
addOnloadHook(
	function() {
		initializeYahooMap(
			'$this->mapName',
			$this->centre_lat,
			$this->centre_lon,
			$this->zoom,
			$this->type,
			[$this->types],
			[$this->controls],
			$this->autozoom,
			[],
			$this->height
		);
	}
);
EOT
		);		
	}

}
