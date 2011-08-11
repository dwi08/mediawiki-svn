// Library to fix tabs for a remote editor 
// Needs to be called from within WikiEditor (or not...?)
// kind of obsolete...?

// @author: Neil Kandalgaonkar

( function( $, mw ) { 

	mw.remoteEditorTabs = function() {

		function addTab() { 
			$( '#p-views ul' ).prepend( 
				$( '<li></li>' ).append( 
					$( '<span></span>' ).append( 
						$( '<a></a>' )
							.attr( { href: getEditUrl() } )
							.html( config.name )
					)
				)
			);
		}

		function modifyEditTab() {
			$( 'li#ca-edit span a' ).attr( { 'href': getEditUrl() } ); 
		}

		function getEditUrl() { 
			return mw.config.get( 'wgServer' ) + mw.config.get( 'wgScript' ) + '?' 
				+ $.param( { 
					'title': mw.config.get( 'wgPageName' ),
					'action': 'edit', 
					'editor': 'remote' 
				} );
		}

		var config = wgRemoteEditorConfig;

		// deal with the tabs
		if ( !config.tab || config.tab === 'modify' ) {  
			modifyEditTab();
		} else if ( config.tab === 'add' ) {
			addTab();
		}
	};

} )( jQuery, mediaWiki );
