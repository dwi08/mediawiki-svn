<?php

/**
 * Parameter criterion stating that the value must be in a certain range.
 * 
 * @since 0.4
 * 
 * @file CriterionInRange.php
 * @ingroup Validator
 * @ingroup Criteria
 * 
 * @author Jeroen De Dauw
 */
class CriterionInRange extends ItemParameterCriterion {
	
	/**
	 * Lower bound of the range. Either a number or false, for no lower limit.
	 * 
	 * @since 0.4
	 * 
	 * @var mixed
	 */
	protected $lowerBound;
	
	/**
	 * Upper bound of the range. Either a number or false, for no upper limit.
	 * 
	 * @since 0.4
	 * 
	 * @var mixed
	 */	
	protected $upperBound;	
	
	/**
	 * Constructor.
	 * 
	 * @param mixed $lowerBound
	 * @param mixed $upperBound
	 * 
	 * @since 0.4
	 */
	public function __construct( $lowerBound, $upperBound ) {
		parent::__construct();
		
		$this->lowerBound = $lowerBound;
		$this->upperBound = $upperBound;		
	}
	
	/**
	 * @see ItemParameterCriterion::validate
	 */	
	protected function doValidation( $value, Parameter $parameter, array &$parameters ) {
		if ( !is_numeric( $value ) ) {
			return false;
		}
		
		$value = (int)$value;
		
		return ( $this->upperBound === false || $value <= $this->upperBound ) 
			&& ( $this->lowerBound === false || $value >= $this->lowerBound );		
	}
	
	/**
	 * @see ItemParameterCriterion::getItemErrorMessage
	 */	
	protected function getItemErrorMessage( Parameter $parameter ) {
		return wfMsgExt( 'validator_error_invalid_range', 'parsemag', $parameter->getOriginalName(), $this->lowerBound, $this->upperBound );
	}
	
	/**
	 * @see ItemParameterCriterion::getListErrorMessage
	 */	
	protected function getListErrorMessage( Parameter $parameter, array $invalidItems ) {
		global $wgLang;
		return wfMsgExt( 'validator_list_error_invalid_range', 'parsemag', $wgLang->listToText( $invalidItems ), count( $invalidItems ) );
	}	
	
}