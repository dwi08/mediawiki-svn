/**
 * Initialization script for the MoodBar MediaWiki extension
 *
 * @author Timo Tijhof, 2011
 */
( function( $ ) {

	var cc = mw.moodBar = {

		//conf: mw.config.get( 'ccConfig' ),

		/*
		cookiePrefix: function() {
			return 'ext.moodBar@' + cc.conf.bucketConfig.version + '-';
		},
		*/
		
		isDisabled: function() {
			/*
			var cookieDisabled = ($.cookie( cc.cookiePrefix() + 'disabled' ) == '1');
			var browserDisabled = false;
			var clientInfo = $.client.profile();
			
			if ( clientInfo.name == 'msie' && clientInfo.versionNumber < 9 ) {
				browserDisabled = true;
			}
			
			return cookieDisabled || browserDisabled;
			*/
			
			return false;
		},

		ui: {
			// jQuery objects
			pCandyChat: null,
			trigger: null,
			overlay: null
		},

		init: function() {
			var ui = cc.ui;

			/*cc.conf.bucketKey = mw.user.bucket(
				'candychat-trigger',
				cc.conf.bucketConfig
			);
			*/
			// Create portlet
			ui.pCandyChat = $( '<div id="p-candychat"></div>' );

			/*
			// Create trigger
			ui.trigger = $( '<a>' )
				.attr( 'href', '#' )
				.attr( 'title', mw.msg( 'tooltip-p-moodbar-trigger-' + cc.conf.bucketKey ) )
				.text( mw.msg( 'moodbar-trigger-' + cc.conf.bucketKey, mw.config.get( 'wgSiteName' ) ) );
			*/
			
			ui.trigger = $( 'CandyChat Trigger' );
			// Insert trigger into portlet
			ui.trigger
				.wrap( '<p>' )	
				.parent()
				.appendTo( ui.pCandyChat );

			// Inject portlet into document, when document is ready
			$( cc.inject );
		},

		inject: function() {
			$( '#mw-head' ).append( cc.ui.pCandyChat );
		}

	};

	if ( !cc.isDisabled() ) {
		cc.init();
	}

} )( jQuery );
