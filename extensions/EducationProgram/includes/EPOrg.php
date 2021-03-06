<?php

/**
 * Class representing a single organization/institution.
 *
 * @since 0.1
 *
 * @file EPOrg.php
 * @ingroup EducationProgram
 *
 * @licence GNU GPL v3 or later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EPOrg extends EPPageObject {

	/**
	 * Cached array of the linked EPCourse objects.
	 *
	 * @since 0.1
	 * @var array|false
	 */
	protected $courses = false;

	/**
	 * (non-PHPdoc)
	 * @see DBDataObject::loadSummaryFields()
	 */
	public function loadSummaryFields( $summaryFields = null ) {
		if ( is_null( $summaryFields ) ) {
			$summaryFields = array( 'course_count', 'active', 'student_count', 'instructor_count', 'oa_count', 'ca_count', 'courses' );
		}
		else {
			$summaryFields = (array)$summaryFields;
		}

		$fields = array();

		if ( in_array( 'course_count', $summaryFields ) || in_array( 'courses', $summaryFields ) ) {
			$fields['courses'] = EPCourses::singleton()->selectFields( 'id', array( 'org_id' => $this->getId() ) );
			$fields['course_count'] = count( $fields['courses'] );
		}

		$dbr = wfGetDB( DB_SLAVE );

		if ( in_array( 'active', $summaryFields ) ) {
			$now = $dbr->addQuotes( wfTimestampNow() );

			$fields['active'] = EPCourses::singleton()->has( array(
				'org_id' => $this->getId(),
				'end >= ' . $now,
				'start <= ' . $now,
			) );
		}

		foreach ( array( 'student_count', 'instructor_count', 'oa_count', 'ca_count' ) as $field ) {
			$fields[$field] = EPCourses::singleton()->rawSelectRow(
				array( 'SUM(' . EPCourses::singleton()->getPrefixedField( $field ). ') AS sum' ),
				EPCourses::singleton()->getPrefixedValues( array(
					'org_id' => $this->getId()
				) )
			)->sum;
		}

		$this->setFields( $fields );
	}

	/**
	 * (non-PHPdoc)
	 * @see EPRevisionedObject::onRemoved()
	 */
	protected function onRemoved() {
		foreach ( EPCourses::singleton()->select( null, array( 'org_id' => $this->getId() ) ) as /* EPCourse */ $course ) {
			$revAction = clone $this->revAction;
			
			if ( trim( $revAction->getComment() ) === '' ) {
				$revAction->setComment( wfMsgExt(
					'ep-org-course-delete',
					'parsemag',
					$this->getField( 'name' )
				) );
			}
			else {
				$revAction->setComment( wfMsgExt(
					'ep-org-course-delete-comment',
					'parsemag',
					$this->getField( 'name' ),
					$revAction->getComment()
				) );
			}
			
			$course->revisionedRemove( $revAction );
		}
		
		parent::onRemoved();
	}

	/**
	 * (non-PHPdoc)
	 * @see DBDataObject::save()
	 */
	public function save() {
		if ( $this->hasField( 'name' ) ) {
			$this->setField( 'name', $GLOBALS['wgLang']->ucfirst( $this->getField( 'name' ) ) );
		}

		return parent::save();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see EPRevisionedObject::undelete()
	 */
	public function undelete( EPRevisionAction $revAction ) {
		$success = parent::undelete( $revAction );
		
		if ( $success ) {
			$courseRevAction = new EPRevisionAction();
		
			$courseRevAction->setUser( $revAction->getUser() );
			$courseRevAction->setComment( '' ); // TODO
			
			foreach ( $this->getField( 'courses' ) as $courseId ) {
				$courseRevision = EPRevisions::singleton()->getLatestRevision( array(
					'object_id' => $courseId,
					'type' => 'EPCourse',
				) );
				
				if ( $courseRevision !== false ) {
					$courseRevision->getObject()->undelete( $courseRevAction );
				}
			}
		}
		
		return $success;
	}

	/**
	 * Adds a control to add a new org to the provided context.
	 * Adittional arguments can be provided to set the default values for the control fields.
	 *
	 * @since 0.1
	 *
	 * @param IContextSource $context
	 * @param array $args
	 *
	 * @return boolean
	 */
	public static function displayAddNewControl( IContextSource $context, array $args = array() ) {
		if ( !$context->getUser()->isAllowed( 'ep-org' ) ) {
			return false;
		}

		$out = $context->getOutput();
		
		$out->addModules( 'ep.addorg' );

		$out->addHTML( Html::openElement(
			'form',
			array(
				'method' => 'post',
				'action' => EPOrgs::singleton()->getTitleFor( 'NAME_PLACEHOLDER' )->getLocalURL( array( 'action' => 'edit' ) ),
			)
		) );

		$out->addHTML( '<fieldset>' );

		$out->addHTML( '<legend>' . wfMsgHtml( 'ep-institutions-addnew' ) . '</legend>' );

		$out->addElement( 'p', array(), wfMsg( 'ep-institutions-namedoc' ) );

		$out->addHTML( Xml::inputLabel(
			wfMsg( 'ep-institutions-newname' ),
			'newname',
			'newname',
			false,
			array_key_exists( 'name', $args ) ? $args['name'] : false
		) );

		$out->addHTML( '&#160;' . Html::input(
			'addneworg',
			wfMsg( 'ep-institutions-add' ),
			'submit',
			array(
				'disabled' => 'disabled',
				'class' => 'ep-org-add',
			)
		) );

		$out->addHTML( Html::hidden( 'isnew', 1 ) );

		$out->addHTML( '</fieldset></form>' );

		return true;
	}

	/**
	 * Display a pager with courses.
	 *
	 * @since 0.1
	 *
	 * @param IContextSource $context
	 * @param array $conditions
	 */
	public static function displayPager( IContextSource $context, array $conditions = array() ) {
		$pager = new EPOrgPager( $context, $conditions );

		if ( $pager->getNumRows() ) {
			$context->getOutput()->addHTML(
				$pager->getFilterControl() .
				$pager->getNavigationBar() .
				$pager->getBody() .
				$pager->getNavigationBar() .
				$pager->getMultipleItemControl()
			);
		}
		else {
			$context->getOutput()->addHTML( $pager->getFilterControl( true ) );
			$context->getOutput()->addWikiMsg( 'ep-institutions-noresults' );
		}
	}

	/**
	 * Retruns the courses linked to this org.
	 *
	 * @since 0.1
	 *
	 * @param array|null $fields
	 *
	 * @return array of EPCourse
	 */
	public function getCourses( array $fields = null ) {
		if ( $this->courses === false ) {
			$courses = EPCourses::singleton()->select( $fields, array( 'org_id' => $this->getId() ) );
			
			if ( is_null( $fields ) ) {
				$this->courses = $courses;
			}
		}

		return $this->courses === false ? $courses : $courses;
	}

}
