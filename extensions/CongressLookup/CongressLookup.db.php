<?php
/**
 * Static methods that retrieve information from the database.
 */
class CongressLookupDB {

	/**
	 * Given a zip code, return the data for that zip code's congressional representative
	 * @param $zip string
	 * @return array
	 */
	public static function getRepresentative( $zip ) {
		$repData = array();
		$dbr = wfGetDB( DB_SLAVE );
		 
		$zip = self::trimZip( $zip, 5 ); // Trim it to 5 digit
		$zip = intval( $zip ); // Convert into an integer

		$row = $dbr->selectRow( 'cl_zip5', 'sz5_rep_id', array( 'sz5_zip' => $zip ) );
		if ( $row ) {
			$rep_id = $row->sz5_rep_id;
			$res = $dbr->select( 
				'cl_house',
				array(
					'sh_bioguideid',
					'sh_gender',
					'sh_name',
					'sh_title',
					'sh_state',
					'sh_district',
					'sh_phone',
					'sh_fax',
					'sh_contactform'
				),
				array(
					'sh_id' => $rep_id,
				),
				__METHOD__
			);
			foreach ( $res as $row ) {
				$oneHouseRep = array(
					'bioguideid' => $row->sh_bioguideid,
					'gender' => $row->sh_gender,
					'name' => $row->sh_name,
					'title' => $row->sh_title,
					'state' => $row->sh_state,
					'district' => $row->sh_district,
					'phone' => $row->sh_phone,
					'fax' => $row->sh_fax,
					'contactform' => $row->sh_contactform
				);
				$repData[] = $oneHouseRep;
			}
			//$repData = $row;
		}
		return $repData;
	}
	
	/**
	 * Given a zip code, return the data for that zip code's senators
	 * @param $zip string
	 * @return array
	 */
	public static function getSenators( $zip ) {
		$senatorData = array();
		$dbr = wfGetDB( DB_SLAVE );
		 
		$zip = self::trimZip( $zip, 3 ); // Trim it to 3 digit
		$zip = intval( $zip ); // Convert into an integer

		$row = $dbr->selectRow( 'cl_zip3', 'sz3_state', array( 'sz3_zip' => $zip ) );
		if ( $row ) {
			$state = $row->sz3_state;
			$res = $dbr->select( 
				'cl_senate',
				array(
					'ss_bioguideid',
					'ss_gender',
					'ss_name',
					'ss_title',
					'ss_state',
					'ss_phone',
					'ss_fax',
					'ss_contactform'
				),
				array(
					'ss_state' => $state,
				),
				__METHOD__
			);
			foreach ( $res as $row ) {
				$oneSenator = array(
					'bioguideid' => $row->ss_bioguideid,
					'gender' => $row->ss_gender,
					'name' => $row->ss_name,
					'title' => $row->ss_title,
					'state' => $row->ss_state,
					'phone' => $row->ss_phone,
					'fax' => $row->ss_fax,
					'contactform' => $row->ss_contactform
				);
				$senatorData[] = $oneSenator;
			}
		}
		return $senatorData;
	}
	
	/**
	 * Helper method. Trim a zip code, but leave it as a string.
	 * @param $zip string: Raw zip code
	 * @param $length integer: How many digits we want to return (from the left)
	 * @return string The trimmed zip code
	 */	
	public static function trimZip( $zip, $length ) {
		$zip = trim( $zip );
		$zipPieces = explode( '-', $zip, 2 );
		$zip = substr( $zipPieces[0], 0, $length );
		return $zip;
	}
}
