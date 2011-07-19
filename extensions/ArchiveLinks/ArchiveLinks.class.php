<?php
/**
 * Main Extension Class for Archive Links
 */

class ArchiveLinks {
	public static function queueExternalLinks ( &$article ) {
		global $wgParser, $wgArchiveLinksConfig;
		$external_links = $wgParser->getOutput();
		$external_links = $external_links->mExternalLinks;
		
		$db_master = wfGetDB( DB_MASTER );
		$db_slave = wfGetDB( DB_SLAVE );
		$db_result = array();
		
		$db_master->begin();
		
		if( !isset( $wgArchiveLinksConfig['global_rearchive_time'] ) ) {
			//30 days or 2,592,000 seconds...
			$wgArchiveLinksConfig['global_rearchive_time'] = 2592000;
		}

		if( !isset( $wgArchiveLinksConfig['page_rearchive_time'] ) ) {
			//200 days or 17,280,000 seconds
			$wgArchiveLinksConfig['page_rearchive_time'] = 1728000;
		}
		
		if( !isset( $wgArchiveLinksConfig['previous_archive_lockout_time'] ) ) {
			//2 days or 172,800 seconds
			$wgArchiveLinksConfig['previous_archive_lockout_time'] = 172800;
		}
		
		foreach ( $external_links as $link => $unused_value ) {
			$link = $db_slave->strencode( $link );
			$page_id = $article->getID();
			$time = time();
			
			if ( $wgArchiveLinksConfig['generate_feed'] === true ) {
				$db_result['blacklist'] = $db_slave->select( 'el_archive_blacklist', '*', array( 'bl_url' => $link ), __METHOD__, array( 'LIMIT' => '1', ) );				
				$db_result['history'] = $db_slave->select( 'el_archive_link_history', '*', array( 'hist_url' => $link ), __METHOD__, array( 'LIMIT' => '1', 'ORDER BY' => 'hist_id DESC' ) );
				$db_result['queue'] = $db_slave->select( 'el_archive_queue', '*', array( 'url' => $link ), __METHOD__, array( 'LIMIT' => '1', ) );
				
				$db_result['queue-numrows'] = $db_result['queue']->numRows();
				$db_result['history-numrows'] = $db_result['history']->numRows();
				
				$db_result['history-row'] = $db_result['history']->fetchRow();
				
				if ( $db_result['history-numrows'] === 0 && $db_result['queue-numrows'] === 0 ) {
					//this link is new to the wiki
					$db_master->insert( 'el_archive_queue', array(
						'page_id' => $page_id,
						'url' => $link,
						'delay_time' => '0',
						'insertion_time' => $time,
						'in_progress' => '0',
					));
					
					$db_master->insert( 'el_archive_link_history', array(
						'page_id' => $page_id,
						'url' => $link,
						'delay_time' => '0',
						'insertion_time' => $time,
						'in_progress' => '0',
					));
				} elseif ( $db_result['history-row']['hist_insertion_time'] >= $time - $wgArchiveLinksConfig['global_rearchive_time'] ) {
					$db_result['history_page'] = $db_slave->select( 'el_archive_link_history', '*', array( 'hist_url' => $link, 'page_id' => $page_id ), __METHOD__, array( 'LIMIT' => '1', 'ORDER BY' => 'hist_id DESC' ) );
					
					$db_result['history_page-numrows'] = $db_result['history_page']->numRows();
					$db_result['history_page-row'] = $db_result['history_page']->fetchRow();
					
					if ( $db_result['history_page-numrows'] === 0 && $db_result['history-row']['hist_insertion_time'] >= $time - $wgArchiveLinksConfig['previous_archive_lockout_time'] ) {
						//this link is new to this particular page but has been archived on another page less than the rearchive delay
						//grab a new version of it in case the content has changed
						$db_master->insert( 'el_archive_queue', array(
							'page_id' => $page_id,
							'url' => $link,
							'delay_time' => '0',
							'insertion_time' => $time,
							'in_progress' => '0',
						));

						$db_master->insert( 'el_archive_link_history', array(
							'page_id' => $page_id,
							'url' => $link,
							'delay_time' => '0',
							'insertion_time' => $time,
							'in_progress' => '0',
						));
						
					}
					
					if ( $db_result['history_page-row']['insertion_time'] >= $time - $wgArchiveLinksConfig['page_rearchive_time']) {
						
					}
				}
				
			} else {
				//$db_result['resource'] = $db_slave->select( 'el_archive_resource', '*', '`el_archive_resource`.`resource_url` = "' . $db_slave->strencode( $link ) . '"');
				$db_result['blacklist'] = $db_slave->select( 'el_archive_blacklist', '*', array( 'bl_url' => $link ), __METHOD__ );
				$db_result['queue'] = $db_slave->select( 'el_archive_queue', '*', array( 'url' => $link ), __METHOD__ );

				if ( $db_result['blacklist']->numRows() === 0 ) {
					if ( $db_result['queue']->numRows() === 0 ) {
						// this probably a first time job
						// but we should check the logs and resource table
						// to make sure
						$db_master->insert( 'el_archive_queue', array (
							'page_id' => $page_id,
							'url' => $link,
							'delay_time' => '0',
							'insertion_time' => $time,
							'in_progress' => '0',
						));
					} else {
						//this job is already in the queue, why?
						// * most likely reason is it has already been inserted by another page
						// * or we are checking it later because the site was down at last archival
						//  in either case we don't really need to do anything right now, so skip...
					}
				}
			}
		}

		$db_master->commit();
		
		return true;
	}
	
	public static function rewriteLinks (  &$url, &$text, &$link, &$attributes ) {
		if ( array_key_exists('rel', $attributes) && $attributes['rel'] === 'nofollow' ) {
			global $wgArchiveLinksConfig;
			if ( $wgArchiveLinksConfig['use_multiple_archives'] ) {
				//need to add support for more than one archival service at once
				//  (a page where you can select one from a list of choices)
			} else {
				switch ( $wgArchiveLinksConfig['archive_service'] ) {
					case 'local':
					//We need to have something to figure out where the filestore is...
					$link_to_archive = urlencode( substr_replace( $url, '', 0, 7 ) );
					break;
					case 'wikiwix':
					$link_to_archive = 'http://archive.wikiwix.com/cache/?url=' . $url;
					break;
					case 'webcitation':
					$link_to_archive = 'http://webcitation.org/query?url=' . $url;
					break;
					case 'internet_archive':
					default:
					$link_to_archive = 'http://wayback.archive.org/web/*/' . $url;
					break;
				}
			}
			
		    $link = HTML::element('a', array ( 'rel' => 'nofollow', 'class' => $attributes['class'], 'href' => $url ), $text )
				. HTML::openElement('sup')
				. HTML::openElement('small')
				. '&#160;'
				. HTML::element('a', array ( 'rel' => 'nofollow', 'href' => $link_to_archive ), '[' . wfMsg( 'archivelinks-cache-title') . ']')
				. HTML::closeElement('small')
				. HTML::closeElement('sup');
		
			return false;
		} else {
			return true;
		}
    }
}