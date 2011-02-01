<?php

// Special:Code/MediaWiki/status
class CodeStatusListView extends CodeView {
	function __construct( $repoName ) {
		parent::__construct();
		$this->mRepo = CodeRepository::newFromName( $repoName );
	}

	function execute() {
		global $wgOut;
		$name = $this->mRepo->getName();
		$states = CodeRevision::getPossibleStates();
		$wgOut->addWikiText( "== " . wfMsg ( "code-field-status" ) . " ==\n" );

		$table_rows = '';
		foreach ( $states as $state ) {
			$link = $this->skin->link(
				SpecialPage::getTitleFor( 'Code', $name . "/status/$state" ),
				wfMsgHtml( "code-status-".$state )
			);
			$table_rows .= "<tr><td class=\"mw-codereview-status-$state\">$link</td>"
				. "<td>" . wfMsgHtml( "code-status-desc-" . $state ) . "</td></tr>\n" ;
		}
		$wgOut->addHTML( '<table class="TablePager">'
			. '<tr><th>' . wfMsgHtml( 'code-field-status' ) . '</th>'
			. '<th>' . wfMsgHtml( 'code-field-status-description' ) . '</th></tr>'
			. $table_rows
			. '</table>'   
		);
	}
}
