<?php

/**
 * Action for viewing an org.
 *
 * @since 0.1
 *
 * @file ViewOrgAction.php
 * @ingroup EducationProgram
 * @ingroup Action
 *
 * @licence GNU GPL v3+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ViewOrgAction extends EPViewAction {

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param Page $page
	 * @param IContextSource $context
	 */
	protected function __construct( Page $page, IContextSource $context = null ) {
		parent::__construct( $page, $context, EPOrgs::singleton() );
	}

	/**
	 * (non-PHPdoc)
	 * @see Action::getName()
	 */
	public function getName() {
		return 'vieworg';
	}

	/**
	 * (non-PHPdoc)
	 * @see EPViewAction::displayPage()
	 */
	protected function displayPage( DBDataObject $org ) {
		parent::displayPage( $org );

		$out = $this->getOutput();

		$out->addElement( 'h2', array(), wfMsg( 'ep-institution-courses' ) );

		EPCourse::displayPager( $this->getContext(), array( 'org_id' => $org->getId() ) );

		if ( $this->getUser()->isAllowed( 'ep-course' ) ) {
			$out->addElement( 'h2', array(), wfMsg( 'ep-institution-add-course' ) );

			EPCourse::displayAddNewControl( $this->getContext(), array( 'org' => $org->getId() ) );
		}
	}

	/**
	 * Gets the summary data.
	 *
	 * @since 0.1
	 *
	 * @param EPOrg $org
	 *
	 * @return array
	 */
	protected function getSummaryData( DBDataObject $org ) {
		$stats = array();

		$stats['name'] = $org->getField( 'name' );
		$stats['city'] = $org->getField( 'city' );

		$countries = CountryNames::getNames( $this->getLanguage()->getCode() );
		$stats['country'] = $countries[$org->getField( 'country' )];

		$stats['status'] = wfMsgHtml( $org->getField( 'active' ) ? 'ep-institution-active' : 'ep-institution-inactive' );

		$stats['courses'] = $this->getLanguage()->formatNum( $org->getField( 'course_count' ) );
		$stats['students'] = $this->getLanguage()->formatNum( $org->getField( 'student_count' ) );

		foreach ( $stats as &$stat ) {
			$stat = htmlspecialchars( $stat );
		}

		if ( $org->getField( 'course_count' ) > 0 ) {
			$stats['courses'] = Linker::linkKnown(
				SpecialPage::getTitleFor( 'Courses' ),
				$stats['courses'],
				array(),
				array( 'org_id' => $org->getId() )
			);
		}

		return $stats;
	}
	
}
