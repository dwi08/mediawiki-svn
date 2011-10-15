<?php
/**
 * Print query results by embeddings them into pages.
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWQuery
 */

/**
 * Printer for embedded data.
 * Embeds in the page output the contents of the pages in the query result set.
 * Printouts are ignored: it only matters which pages were returned by the query.
 * The optional "titlestyle" formatting parameter can be used to apply a format to
 * the headings for the page titles. If "titlestyle" is not specified, a <h1> tag is
 * used.
 * @author Fernando Correia
 * @author Markus Krötzsch
 * @ingroup SMWQuery
 */
class SMWEmbeddedResultPrinter extends SMWResultPrinter {

	protected $m_showhead;
	protected $m_embedformat;

	/**
	 * @see SMWResultPrinter::handleParameters
	 * 
	 * @since 1.6.3
	 * 
	 * @param array $params
	 * @param $outputmode
	 */
	protected function handleParameters( array $params, $outputmode ) {
		parent::handleParameters( $params, $outputmode );
		
		$this->m_showhead = !$params['embedonly'];
		$this->m_embedformat = $params['embedformat'];
	}

	public function getName() {
		return wfMsg( 'smw_printername_embedded' );
	}

	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		global $wgParser;
		// No page should embed itself, find out who we are:
		if ( $wgParser->getTitle() instanceof Title ) {
			$title = $wgParser->getTitle()->getPrefixedText();
		} else { // this is likely to be in vain -- this case is typical if we run on special pages
			global $wgTitle;
			$title = $wgTitle->getPrefixedText();
		}

		// print header
		$result = '';
		$footer = '';
		$embstart = '';
		$embend = '';
		$headstart = '';
		$headend = '';
		$this->hasTemplates = true;

		switch ( $this->m_embedformat ) {
			case 'h1': case 'h2': case 'h3': case 'h4': case 'h5': case 'h6':
				$headstart = '<' . $this->m_embedformat . '>';
				$headend = '</' . $this->m_embedformat . ">\n";
			break;
			case 'ul': case 'ol':
				$result .= '<' . $this->m_embedformat . '>';
				$footer = '</' . $this->m_embedformat . '>';
				$embstart = '<li>';
				$headend = "<br />\n";
				$embend = "</li>\n";
			break;
		}

		// Print all result rows:
		foreach ( $res->getResults() as $diWikiPage ) {
			if ( $diWikiPage instanceof SMWDIWikiPage  ) { // ensure that we deal with title-likes
				$dvWikiPage = SMWDataValueFactory::newDataItemValue( $diWikiPage, null );
				$result .= $embstart;

				if ( $this->m_showhead ) {
					$result .= $headstart . $dvWikiPage->getLongWikiText( $this->mLinker ) . $headend;
				}

				if ( $dvWikiPage->getLongWikiText() != $title ) {
					if ( $diWikiPage->getNamespace() == NS_MAIN ) {
						$result .= '{{:' . $diWikiPage->getDBkey() . '}}';
					} else {
						$result .= '{{' . $dvWikiPage->getLongWikiText() . '}}';
					}
				} else { // block recursion
					$result .= '<b>' . $dvWikiPage->getLongWikiText() . '</b>';
				}

				$result .= $embend;
			}
		}

		// show link to more results
		if ( $this->linkFurtherResults( $res ) ) {
			$link = $res->getQueryLink();
			if ( $this->getSearchLabel( SMW_OUTPUT_WIKI ) ) {
				$link->setCaption( $this->getSearchLabel( SMW_OUTPUT_WIKI ) );
			}
			$link->setParameter( 'embedded', 'format' );
			// ordered lists confusing in paged output
			$format = ( $this->m_embedformat == 'ol' ) ? 'ul':$this->m_embedformat;
			$link->setParameter( $format, 'embedformat' );
			if ( !$this->m_showhead ) {
				$link->setParameter( 'on', 'embedonly' );
			}
			$result .= $embstart . $link->getText( SMW_OUTPUT_WIKI, $this->mLinker ) . $embend;
		}
		$result .= $footer;

		return $result;
	}

	public function getParameters() {
		$params = parent::getParameters();

		$params['embedformat'] = new Parameter( 'embedformat' );
		$params['embedformat']->setMessage( 'smw_paramdesc_embedformat' );
		$params['embedformat']->setDefault( 'h1' );
		$params['embedformat']->addCriteria( new CriterionInArray( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ol', 'ul' ) );
		
		$params['embedonly'] = new Parameter( 'embedonly', Parameter::TYPE_BOOLEAN );
		$params['embedonly']->setMessage( 'smw_paramdesc_embedonly' );
		$params['embedonly']->setDefault( false, false );	
		
		return $params;
	}

}
