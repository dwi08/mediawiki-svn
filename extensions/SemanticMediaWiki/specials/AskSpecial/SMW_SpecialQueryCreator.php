<?php

/**
 * This special page for Semantic MediaWiki implements a customisable form for
 * executing queries outside of articles.
 *
 * @file SMW_SpecialQueryCreator.php
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Sergey Chernyshev
 * @author Devayon Das
 */
class SMWQueryCreatorPage extends SMWQueryUI {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( 'QueryCreator' );
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
	}

	/**
	 * The main entrypoint. Call the various methods of SMWQueryUI and
	 * SMWQueryUIHelper to build ui elements and to process them.
	 *
	 * @global OutputPage $wgOut
	 * @param string $p
	 */
	protected function makePage( $p ) {
		global $wgOut;
		$htmlOutput = $this->makeForm( $p );
		if ( $this->uiCore->getQueryString() != "" ) {
			if ( $this->usesNavigationBar() ) {
				$htmlOutput .= Html::rawElement( 'div', array( 'class' => 'smwqcnavbar' ),
					$this->getNavigationBar ( $this->uiCore->getLimit(),
						$this->uiCore->getOffset(),
						$this->uiCore->hasFurtherResults() )
				);
			}

			$htmlOutput .= Html::rawElement( 'div', array( 'class' => 'smwqcresult' ), $this->uiCore->getHTMLResult() );

			if ( $this->usesNavigationBar() ) {
				$htmlOutput .= Html::rawElement( 'div', array( 'class' => 'smwqcnavbar' ),
					$this->getNavigationBar ( $this->uiCore->getLimit(),
						$this->uiCore->getOffset(),
						$this->uiCore->hasFurtherResults() )
				);
			}
		}
		$wgOut->addHTML( $htmlOutput );
	}

	/**
	 * This method should call the various processXXXBox() methods for each of
	 * the corresponding getXXXBox() methods which the UI uses.
	 * Merge the results of these methods and return them.
	 *
	 * @global WebRequest $wgRequest
	 * @return array
	 */
	protected function processParams() {
		global $wgRequest;
		$params = array_merge(
			array(
				'format'  =>  $wgRequest->getVal( 'format' ),
				'offset'  =>  $wgRequest->getVal( 'offset',  '0'  ),
				'limit'   =>  $wgRequest->getVal( 'limit',   '20' ) ),
			$this->processPoSortFormBox( $wgRequest ),
			$this->processFormatSelectBox( $wgRequest )
		);
		return $params;
	}

	/**
	 * Displays a form section showing the options for a given format,
	 * based on the getParameters() value for that format's query printer.
	 *
	 * @param string $format
	 * @param array $paramValues The current values for the parameters (name => value)
	 * @param array $ignoredAttribs Attributes which should not be generated by this method.
	 *
	 * @return string
	 *
	 * Overridden from parent to ignore some parameters.
	 */
	protected function showFormatOptions( $format, array $paramValues, array $ignoredAttribs = array() ) {
		return parent::showFormatOptions( $format, $paramValues, array(
			'format', 'limit', 'offset', 'mainlabel', 'intro', 'outro', 'default'
		) );
	}

	/**
	 * Creates the search form
	 *
	 * @global OutputPage $wgOut
	 * @global string $smwgScriptPath
	 * @return string
	 */
	protected function makeForm() {
		global $wgOut, $smwgScriptPath;
		SMWOutputs::requireResource( 'jquery' );
		$result = '<div class="smwqcerrors">' . $this->getErrorsHtml() . '</div>';
		$specTitle = $this->getTitle();
		$formatBox = $this->getFormatSelectBoxSep( 'broadtable' );
		$result .= Html::openElement( 'form', array( 'name' => 'qc', 'id'=>'smwqcform', 'action' => $specTitle->escapeLocalURL(), 'method' => 'get' ) ) . "\n" .
			Html::hidden( 'title', $specTitle->getPrefixedText() );
		$result .= wfMsg( 'smw_qc_query_help' );
		// Main query and format options
		$result .= $this->getQueryFormBox();
		// sorting and prinouts
		$result .= '<div class="smwqcsortbox">' . $this->getPoSortFormBox() . '</div>';
		// additional options

		// START: show|hide additional options
		$result .= '<div class="smwqcformatas">' . Html::element( 'strong', array(), wfMsg( 'smw_ask_format_as' ) );
		$result .= $formatBox[0] . '<span id="show_additional_options" style="display:inline;">' .
			'<a href="#addtional" rel="nofollow" onclick="' .
			 "jQuery('#additional_options').show('blind');" .
			 "document.getElementById('show_additional_options').style.display='none';" .
			 "document.getElementById('hide_additional_options').style.display='inline';" . '">' .
			 wfMsg( 'smw_qc_show_addnal_opts' ) . '</a></span>';
		$result .= '<span id="hide_additional_options" style="display:none"><a href="#" rel="nofollow" onclick="' .
			 "jQuery('#additional_options').hide('blind');;" .
			 "document.getElementById('hide_additional_options').style.display='none';" .
			 "document.getElementById('show_additional_options').style.display='inline';" . '">' .
			 wfMsg( 'smw_qc_hide_addnal_opts' ) . '</a></span>';
		$result .= '</div>';
		// END: show|hide additional options

		$result .= '<div id="additional_options" style="display:none">';
		$result .= $this->getOtherParametersBox();
		$result .= '<fieldset><legend>' . wfMsg( 'smw_qc_formatopt' ) . "</legend>\n" .
					$formatBox[1] . // display the format options
					"</fieldset>\n";

		$result .= '</div>'; // end of hidden additional options
		$result .= '<br/><input type="submit" value="' . wfMsg( 'smw_ask_submit' ) . '"/><br/>';
		$result .= '<a href="' . htmlspecialchars( wfMsg( 'smw_ask_doculink' ) ) . '">' . wfMsg( 'smw_ask_help' ) . '</a>';

		if ( $this->uiCore->getQueryString() != '' ) { // hide #ask if there isnt any query defined
			$result .= ' | <a name="show-embed-code" id="show-embed-code" href="##" rel="nofollow">' .
				wfMsg( 'smw_ask_show_embed' ) .
			'</a>';
			$result .= '<div id="embed-code-dialog">' . $this->getAskEmbedBox() . '</div>';
			SMWOutputs::requireResource( 'jquery.ui.autocomplete' );
			$wgOut->addScriptFile( "$smwgScriptPath/libs/jquery-ui/jquery-ui.dialog.min.js" );
			$wgOut->addStyle( "$smwgScriptPath/skins/SMW_custom.css" );

			$javascriptText = <<<EOT
<script type="text/javascript">
	jQuery( document ).ready( function(){
		jQuery( '#embed-code-dialog' ).dialog( {
			autoOpen:false,
			modal: true,
			buttons: {
				Ok: function(){
					jQuery( this ).dialog( "close" );
				}
			}
		} );
		jQuery( '#show-embed-code' ).bind( 'click', function(){
			jQuery( '#embed-code-dialog' ).dialog( "open" );
		} );
	} );
</script>
EOT;
			$wgOut->addScript( $javascriptText );
		}

		$result .= '<input type="hidden" name="eq" value="no"/>' .
			"\n</form><br/>";
	return $result;
	}

	/**
	 * Overridden to include form parameters.
	 *
	 * @return array of strings in the urlparamater=>value format
	 */
	protected function getUrlArgs() {
		$tmpArray = array();
		$params = $this->uiCore->getParameters();
		foreach ( $params as $key => $value ) {
			if ( !in_array( $key, array( 'sort', 'order', 'limit', 'offset', 'title' ) ) ) {
				$tmpArray[$key] = $value;
			}
		}
		$this->setUrlArgs( $tmpArray );
		return $this->urlArgs;
	}

	/**
	 * Creates controls for limit, intro, outro, default and offset
	 *
	 * @return string
	 */
	protected function getOtherParametersBox() {
		$params = $this->uiCore->getParameters();
		if ( array_key_exists( 'limit', $params ) ) {
			$limit = $params['limit'];
		} else {
			$limit = '';
		}
		if ( array_key_exists( 'offset', $params ) ) {
			$offset = $params['offset'];
		} else {
			$offset = '';
		}
		if ( array_key_exists( 'intro', $params ) ) {
			$intro = $params['intro'];
		} else {
			$intro = '';
		}
		if ( array_key_exists( 'outro', $params ) ) {
			$outro = $params['outro'];
		} else {
			$outro = '';
		}
		if ( array_key_exists( 'default', $params ) ) {
			$default = $params['default'];
		} else {
			$default = '';
		}
		$result = '<fieldset><legend>' . wfMsg( 'smw_ask_otheroptions' ) . "</legend>\n" .
			Html::rawElement( 'div',
				array( 'style' => 'width: 30%; min-width:220px; margin:5px; padding: 1px; float: left;' ),
				wfMsg( 'smw_qc_intro' ) .
					'<input name="p[intro]" value="' . $intro . '" style="width:220px;"/> <br/>' .
					wfMsg( 'smw_paramdesc_intro' )
			) .
			Html::rawElement( 'div',
				array( 'style' => 'width: 30%; min-width:220px; margin:5px; padding: 1px; float: left;' ),
				wfMsg( 'smw_qc_outro' ) .
					'<input name="p[outro]" value="' . $outro . '" style="width:220px;"/> <br/>' .
					wfMsg( 'smw_paramdesc_outro' )
			) .
			Html::rawElement( 'div',
				array( 'style' => 'width: 30%; min-width:220px; margin:5px; padding: 1px; float: left;' ),
				wfMsg( 'smw_qc_default' ) .
					'<input name="p[default]" value="' . $default . '" style="width:220px;" /> <br/>' .
					wfMsg( 'smw_paramdesc_default' )
			) .
			Html::hidden( 'p[limit]', $limit ) .
			Html::hidden( 'p[offset]', $offset ) .
			'</fieldset>';

		return $result;
	}
}

