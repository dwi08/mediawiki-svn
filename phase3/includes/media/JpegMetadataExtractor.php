<?php
/**
* Class for reading jpegs and extracting metadata.
* see also BitmapMetadataHandler.
*
* Based somewhat on GIFMetadataExtrator.
*/
class JpegMetadataExtractor {
	const MAX_JPEG_SEGMENTS = 200;
	// the max segment is a sanity check.
	// A jpeg file should never even remotely have
	// that many segments. Your average file has about 10.

	/** Function to extract metadata segments of interest from jpeg files
	* based on GIFMetadataExtractor.
	*
	* we can almost use getimagesize to do this
	* but gis doesn't support having multiple app1 segments
	* and those can't extract xmp on files containing both exif and xmp data
	*
	* @param String $filename name of jpeg file
	* @return Array of interesting segments.
	* @throws MWException if given invalid file.
	*/
	static function segmentSplitter ( $filename ) {
		$showXMP = function_exists( 'xml_parser_create_ns' );

		$segmentCount = 0;

		$segments = Array( 'XMP_ext' => array(), 'COM' => array() );

		if ( !$filename ) throw new MWException( "No filename specified for " . __METHOD__ );
		if ( !file_exists( $filename ) || is_dir( $filename ) ) throw new MWException( "Invalid file $filename passed to " . __METHOD__ );

		$fh = fopen( $filename, "rb" );

		if ( !$fh ) throw new MWException( "Could not open file $filename" );

		$buffer = fread( $fh, 2 );
		if ( $buffer !== "\xFF\xD8" ) throw new MWException( "Not a jpeg, no SOI" );
		while ( !feof( $fh ) ) {
			$buffer = fread( $fh, 1 );
			$segmentCount++;
			if ( $segmentCount > self::MAX_JPEG_SEGMENTS ) {
				// this is just a sanity check
				throw new MWException( 'Too many jpeg segments. Aborting' );
			}
			if ( $buffer !== "\xFF" ) {
				throw new MWException( "Error reading jpeg file marker" );
			}

			$buffer = fread( $fh, 1 );
			if ( $buffer === "\xFE" ) {

				// COM section -- file comment
				// First see if valid utf-8,
				// if not try to convert it to windows-1252.
				$com = $oldCom = trim( self::jpegExtractMarker( $fh ) );
				UtfNormal::quickIsNFCVerify( $com );
				// turns $com to valid utf-8.
				// thus if no change, its utf-8, otherwise its something else.
				if ( $com !== $oldCom ) {
					wfSuppressWarnings();
					$com = $oldCom = iconv( 'windows-1252', 'UTF-8//IGNORE', $oldCom );
					wfRestoreWarnings();
				}
				// Try it again, if its still not a valid string, then probably
				// binary junk or some really weird encoding, so don't extract.
				UtfNormal::quickIsNFCVerify( $com );
				if ( $com === $oldCom ) {
					$segments["COM"][] = $oldCom;
				} else {
					wfDebug( __METHOD__ . ' Ignoring JPEG comment as is garbage.' );
				}

			} elseif ( $buffer === "\xE1" && $showXMP ) {
				// APP1 section (Exif, XMP, and XMP extended)
				// only extract if XMP is enabled.
				$temp = self::jpegExtractMarker( $fh );

				// check what type of app segment this is.
				if ( substr( $temp, 0, 29 ) === "http://ns.adobe.com/xap/1.0/\x00" ) {
					$segments["XMP"] = substr( $temp, 29 );
				} elseif ( substr( $temp, 0, 35 ) === "http://ns.adobe.com/xmp/extension/\x00" ) {
					$segments["XMP_ext"][] = substr( $temp, 35 );
				} elseif ( substr( $temp, 0, 29 ) === "XMP\x00://ns.adobe.com/xap/1.0/\x00" ) {
					// Some images (especially flickr images) seem to have this.
					// I really have no idea what the deal is with them, but
					// whatever...
					$segments["XMP"] = substr( $temp, 29 );
					wfDebug( __METHOD__ . ' Found XMP section with wrong app identifier '
						. "Using anyways.\n" ); 
				}
			} elseif ( $buffer === "\xED" ) {
				// APP13 - PSIR. IPTC and some photoshop stuff
				$temp = self::jpegExtractMarker( $fh );
				if ( substr( $temp, 0, 14 ) === "Photoshop 3.0\x00" ) {
					$segments["PSIR"] = $temp;
				}
			} elseif ( $buffer === "\xD9" || $buffer === "\xDA" ) {
				// EOI - end of image or SOS - start of scan. either way we're past any interesting segments
				return $segments;
			} else {
				// segment we don't care about, so skip
				$size = unpack( "nint", fread( $fh, 2 ) );
				if ( $size['int'] <= 2 ) throw new MWException( "invalid marker size in jpeg" );
				fseek( $fh, $size['int'] - 2, SEEK_CUR );
			}

		}
		// shouldn't get here.
		throw new MWException( "Reached end of jpeg file unexpectedly" );

	}
	/**
	* Helper function for jpegSegmentSplitter
	* @param &$fh FileHandle for jpeg file
	* @return data content of segment.
	*/
	private static function jpegExtractMarker( &$fh ) {
		$size = unpack( "nint", fread( $fh, 2 ) );
		if ( $size['int'] <= 2 ) throw new MWException( "invalid marker size in jpeg" );
		return fread( $fh, $size['int'] - 2 );
	}

	/**
	* This reads the photoshop image resource.
	* Currently it only compares the iptc/iim hash
	* with the stored hash, which is used to determine the precedence
	* of the iptc data. In future it may extract some other info, like
	* url of copyright license.
	*
	* This should generally be called by BitmapMetadataHandler::doApp13()
	*
	* @param String $app13 photoshop psir app13 block from jpg.
	* @return String if the iptc hash is good or not.
	*/
	public static function doPSIR ( $app13 ) {
		if ( !$app13 ) return;
		// First compare hash with real thing
		// 0x404 contains IPTC, 0x425 has hash
		// This is used to determine if the iptc is newer than
		// the xmp data, as xmp programs update the hash,
		// where non-xmp programs don't.

		$offset = 14; // skip past PHOTOSHOP 3.0 identifier. should already be checked.
		$appLen = strlen( $app13 );
		$realHash = "";
		$recordedHash = "";

		// the +12 is the length of an empty item.
		while ( $offset + 12 <= $appLen ) {
			if ( substr( $app13, $offset, 4 ) !== '8BIM' ) {
				// its supposed to be 8BIM
				// but apparently sometimes isn't esp. in
				// really old jpg's
				$valid = false;
			}
			$offset += 4;
			$id = substr( $app13, $offset, 2 );
			// id is a 2 byte id number which identifies
			// the piece of info this record contains.

			$offset += 2;

			// some record types can contain a name, which
			// is a pascal string 0-padded to be an even
			// number of bytes. Most times (and any time
			// we care) this is empty, making it two null bytes.

			$lenName = ord( substr( $app13, $offset, 1 ) ) + 1;
			// we never use the name so skip it. +1 for length byte
			if ( $lenName % 2 == 1 ) $lenName++; // pad to even.
			$offset += $lenName;

			// now length of data (unsigned long big endian)
			$lenData = unpack( 'Nlen', substr( $app13, $offset, 4 ) );
			$offset += 4; // 4bytes length field;

			// this should not happen, but check.
			if ( $lenData['len'] + $offset > $appLen ) {
				wfDebug( __METHOD__ . " PSIR data too long.\n" );
				return 'iptc-no-hash';
			}

			if ( $valid ) {
				switch ( $id ) {
					case "\x04\x04":
						// IPTC block
						$realHash = md5( substr( $app13, $offset, $lenData['len'] ), true );
						break;
					case "\x04\x25":
						$recordedHash = substr( $app13, $offset, $lenData['len'] );
						break;
				}
			}

			// if odd, add 1 to length to account for
			// null pad byte.
			if ( $lenData['len'] % 2 == 1 ) $lenData['len']++;
			$offset += $lenData['len'];

		}

		if ( !$realHash || !$recordedHash ) {
			return 'iptc-no-hash';
		} elseif ( $realHash === $recordedHash ) {
			return 'iptc-good-hash';
		} else { /*$realHash !== $recordedHash */
			return 'iptc-bad-hash';
		}

	}

}
