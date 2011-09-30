<?php

/**
 *
 */
class SpecialExemptFromAccountCreationThrottle extends SpecialPage {

	public function __construct() {
		parent::__construct( 'ExemptFromAccountCreationThrottle' );
	}

	public function execute( $par ) {
		global $wgOut, $wgRequest, $wgUser;
		$this->setHeaders();
		$wgOut->setPageTitle( wfMsg( 'efact-form' ) );

		$this->loadParameters( $par );

		$errors = '';
		if ( $this->mAction == 'success' ) {
			wfDebug( __METHOD__ . ": success.\n" );
			$this->showSuccess();
		} elseif ( $wgRequest->wasPosted() && count( $errors ) == 0 )   {

			$tokenOk = $wgUser->matchEditToken( $this->mtoken );

			if ( !$tokenOk ) {
				wfDebug( __METHOD__ . ": bad token (" . ( $wgUser->isAnon() ? 'anon' : 'user' ) . "): $token\n" );
				$wgOut->addWikiMsg( 'sessionfailure' );
				$this->showForm();
			} else {
				wfDebug( __METHOD__ . ": submit\n" );
				$this->doSubmit();
			}
		} else {
			wfDebug( __METHOD__ . ": form\n" );
			var_dump('huhu');
			$this->form( $errors );
		}
	}

	public function form( $error ) {
		global $wgUser, $wgScript, $wgOut;

		$form = '';

		// Introduction
		$wgOut->addWikiMsg( 'efact-intro' );

		// Add errors
		$wgOut->addHTML( $error );

		$form .= Xml::fieldset( wfMsg( 'efact-form-legend' ) );
		$form .= Xml::openElement( 'form',
						array( 'method' => 'post',
								'action' => $wgScript,
								'name' => 'efact-form',
								'id' => 'mw-efact-form' ) );

		$form .= Html::hidden( 'title',  SpecialPage::getTitleFor('ExemptFromAccountCreationThrottle')->getPrefixedText() );

		$fields = array ();

		// Who to exempt
		$fields['efact-ipaddress'] =
			Xml::input( 'wpAddress',
				45,
				$this->mAddress,
				array('id' => 'mw-efact-form-address' )
			);
			
		// Timeframe to exempt
		$fields['efact-time-start'] =
			Xml::input(
				'wpTimeStart',
					45,
					$this->mTimeStart,
					array( 'id' => 'mw-efact-time-start' )
				);
		
		$fields['efact-time-end'] =
			Xml::input(
				'wpTimeEnd',
				45,
				$this->mTimeEnd,
				array( 'id' => 'mw-efact-time-end' )
			);
		
		// Why to exempt them
		$fields['efact-reason'] =
			Xml::listDropDown(
				'wpBlockReasonList',
				wfMsgForContent( 'efact-reason-dropdown' ),
				wfMsgForContent( 'efact-reasonotherlist' ),
				$this->mReasonList,
				'mw-globalblock-reasonlist'
			);

		$fields['efact-otherreason'] =
			Xml::input(
				'wpReason',
				45,
				$this->mReason,
				array( 'id' => 'mw-efact-reason' )
			);

		// Build a form.
		$form .= Xml::buildForm( $fields, 'efact-submit' );

		$form .= Html::hidden( 'wpEditToken', $wgUser->editToken() );

		$form .= Xml::closeElement( 'form' );
		$form .= Xml::closeElement( 'fieldset' );


		$wgOut->addHTML( $form );

	}

	public function loadParameters( $par ) {
		global $wgRequest;
		$this->mAddress = trim( $wgRequest->getText( 'wpAddress' ) );
		if (!$this->mAddress)
			$this->mAddress = $par;
			
		$this->mReason = $wgRequest->getText( 'wpReason' );
		$this->mReasonList = $wgRequest->getText( 'wpBlockReasonList' );
		$this->mTimeStart = $wgRequest->getText( 'wpTimeStart' );
		$this->mTimeEnd = $wgRequest->getText( 'wpTimeEnd' );
		$this->mtoken = $wgRequest->getVal( 'wpEditToken' );
		$this->mAction = $wgRequest->getVal( 'action' );

	}
	public function doSubmit() {
		global $wgOut, $wgUser, $wgLang;

		wfDebug( __METHOD__ . ": start\n" );

		$dbw = wfGetDB( DB_MASTER );
		$ok = $dbw->insert(
			'petition',
			array(
				'petition_title'  => $this->fromtitle,
				'petition_first_name'  => $this->fromfirstname,
				'petition_last_name'  => $this->fromlastname,
				'petition_street'  => $this->fromstreet,
				'petition_postcode'  => $this->frompostcode,
				'petition_city'  => $this->fromcity,
				'petition_country'  => $this->fromcountry,
				'petition_email'  => $this->fromaddress,
				'petition_authenticated' => null,
				'petition_token'  => $this->hash,
				'petition_registration' => $dbw->timestamp(),
				'petition_newsletter'  => $this->newsletter,
				'petition_infoletter'  => $this->infoletter,
				'petition_comment'  => $this->comment,
				'petition_ip'  => wfGetIP(),
				'petition_userlang'  => $wgLang->getCode(),
				'petition_campaign'  => $this->campaign,
			),
			__METHOD__ );

		if ( $ok ) {
			wfDebugLog( 'Petition',
				"saving information OK for '$this->fromaddress'\n" );
		} else {
			wfDebugLog( 'Petition',
				"saving information failed for '$this->fromaddress'\n" );
		}

		
		wfDebug( __METHOD__ . ": success\n" );

		$titleObj = SpecialPage::getTitleFor( 'ExemptFromAccountCreationThrottle' );
		$wgOut->redirect( $titleObj->getFullURL( 'action=success' ) );

		wfDebug( __METHOD__ . ": end\n" );
	}

	public function showSuccess() {
		global $wgOut;

		$wgOut->setPageTitle( wfMsg( 'efact-success' ) );
		$wgOut->addWikiMsg( 'efact-success-text' );

		$wgOut->returnToMain( false );
	}
}

