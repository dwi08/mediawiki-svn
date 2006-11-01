<?php

/**
 * Image map extension. 
 * TODO: image description link (icon)
 *
 * Syntax:
 * <imagemap>
 * Image:Foo.jpg | 100px | picture of a foo
 *
 * rect    0  0  50 50  [[Foo type A]]
 * circle  50 50 20     [[Foo type B]]
 *
 * </imagemap>
 *
 * Coordinates are relative to the source image, not the thumbnail
 *
 */

global $wgMessageCache;
$wgMessageCache->addMessages( array(
	'imagemap_no_image'             => '&lt;imagemap&gt;: must specify an image in the first line',
	'imagemap_invalid_image'        => '&lt;imagemap&gt;: image is invalid or non-existent',
	'imagemap_no_link'              => '&lt;imagemap&gt;: no valid link was found at the end of line $1',
	'imagemap_invalid_title'        => '&lt;imagemap&gt;: invalid title in link at line $1',
	'imagemap_missing_coord'        => '&lt;imagemap&gt;: not enough coordinates for shape at line $1',
	'imagemap_unrecognised_shape'   => '&lt;imagemap&gt;: unrecognised shape at line $1, each line must start with one of: '.
	                                   'default, rect, circle or poly',
	'imagemap_no_areas'             => '&lt;imagemap&gt;: at least one area specification must be given',
	'imagemap_invalid_coord'        => '&lt;imagemap&gt;: invalid coordinate at line $1, must be a number',
));
class ImageMap {
	static public $id = 0;

	static function render( $input, $params, $parser ) {
		$lines = explode( "\n", $input );

		$first = true;
		$lineNum = 0;
		$output = '';
		$links = array();
		foreach ( $lines as $line ) {
			++$lineNum;

			$line = trim( $line );
			if ( $line == '' ) {
				continue;
			}

			if ( $first ) {
				$first = false;

				# The first line should have an image specification on it
				# Extract it and render the HTML
				$bits = explode( '|', $line, 2 );
				if ( count( $bits ) == 1 ) {
					$image = $bits[0];
					$options = '';
				} else {
					list( $image, $options ) = $bits;
				}
				$imageTitle = Title::newFromText( $image );
				if ( !$imageTitle || $imageTitle->getNamespace() != NS_IMAGE ) {
					return self::error( 'imagemap_no_image' );
				}
				$imageHTML = $parser->makeImage( $imageTitle, $options );

				$sx = simplexml_load_string( $imageHTML );
				$imgs = $sx->xpath( '//img' );
				if ( !count( $imgs ) ) {
					return self::error( 'imagemap_invalid_image' );
				}
				$imageNode = $imgs[0];
				$thumbWidth = $imageNode['width'];
				$thumbHeight = $imageNode['height'];

				$imageObj = new Image( $imageTitle );
				# Add the linear dimensions to avoid inaccuracy in the scale 
				# factor when one is much larger than the other
				# (sx+sy)/(x+y) = s
				$denominator = $imageObj->getWidth() + $imageObj->getHeight();
				$numerator = $thumbWidth + $thumbHeight;
				if ( $denominator <= 0 || $numerator <= 0 ) {
					return self::error( 'imagemap_invalid_image' );
				}
				$scale = $numerator / $denominator;
				continue;
			}

			# Find the link
			$link = trim( strstr( $line, '[[' ) );
			if ( preg_match( '/^ \[\[  ([^|]*+)  \|  ([^\]]*+)  \]\] \w* $ /x', $link, $m ) ) {
				$title = Title::newFromText( $m[1] );
				$alt = trim( $m[2] );
			} elseif ( preg_match( '/^ \[\[  ([^\]]*+) \]\] \w* $ /x', $link, $m ) ) {
				$title = Title::newFromText( $m[1] );
				$alt = false;
			} else {
				return self::error( 'imagemap_no_link', $lineNum );
			}
			if ( !$title ) {
				return self::error( 'imagemap_invalid_title', $lineNum );
			}

			$shapeSpec = substr( $line, 0, -strlen( $link ) );

			# Tokenize shape spec
			$shape = strtok( $shapeSpec, " \t" );
			switch ( $shape ) {
				case 'default':
					$coords = array();
					break;
				case 'rect':
					$coords = self::tokenizeCoords( 4, $lineNum );
					if ( !is_array( $coords ) ) {
						return $coords;
					}
					break;
				case 'circle':
					$coords = self::tokenizeCoords( 3, $lineNum );
					if ( !is_array( $coords ) ) {
						return $coords;
					}
					break;
				case 'poly':
					$coord = strtok( " \t" );
					while ( $coord !== false ) {
						$coords[] = $coord;
						strtok( " \t" );
					}
					if ( !count( $coords ) ) {
						return self::error( 'imagemap_missing_coord', $lineNum );
					}
					break;
				default:
					return self::error( 'imagemap_unrecognised_shape', $lineNum );
			}

			# Scale the coords using the size of the source image
			foreach ( $coords as $i => $c ) {
				$coords[$i] *= $scale;
			}

			# Construct the area tag
			$attribs = array( 
				'shape' => $shape,
				'href' => $title->escapeLocalURL()
			);
			if ( $coords ) {
				$attribs['coords'] = implode( ',', $coords );
			}
			if ( $alt != '' ) {
				$attribs['alt'] = $alt;
			}
			$output .= Xml::element( 'area', $attribs ) . "\n";
			$links[] = $title;
		}

		if ( $first ) {
			return self::error( 'imagemap_no_image' );
		}

		if ( $output == '' ) {
			return self::error( 'imagemap_no_areas' );
		}

		# Construct the map
		$mapName = "ImageMap_" . ++self::$id;
		$output = "<map name=\"$mapName\">\n$output</map>\n";
		
		# Alter the image tag and output it
		$imageNode['usemap'] = $mapName;
		$output .= $imageNode->asXML();

		# Register links
		$parser->mOutput->addImage( $imageTitle->getDBkey() );
		foreach ( $links as $title ) {
			$parser->mOutput->addLink( $title );
		}
		return $output;
	}

	static function tokenizeCoords( $count, $lineNum ) {
		$coords = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$coord = strtok( " \t" );
			if ( $coord === false ) {
				return self::error( 'imagemap_missing_coord', $lineNum );
			}
			if ( !is_numeric( $coord ) || $coord > 1e9 || $coord < 0 ) {
				return self::error( 'imagemap_invalid_coord', $lineNum );
			}
			$coords[$i] = $coord;
		}
		return $coords;
	}

	static function error( $name, $line = false ) {
		return wfMsgForContent( $name, $line );
	}
}

?>
