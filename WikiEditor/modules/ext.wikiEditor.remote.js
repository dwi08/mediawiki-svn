/**
 * Library to create a remote editor (particularly an instance of the modified etherpad editor pad-mediawiki)
 *
 * dependencies: mediawiki.uri
 *
 * @author Neil Kandalgaonkar
 * @license same terms as MediaWiki itself
 *
 */

( function( $, mw ) { 

	/**
	 * Creates a new remote editor object
	 * Upgrades the form on the page to have a remote editor. See launchEditor() for most of the details on how.
	 * 
	 * @param {HTMLTextAreaElement} (can be jQuery-wrapped)
	 */
	function remoteEditor( textarea ) { 
		if ( typeof textarea === 'undefined' ) {
			throw new Error( "need a textarea as argument to remoteEditor" );
		}

		if ( typeof wgRemoteEditorConfig === 'undefined' ) {
			console.log( "don't have config for the remote editor" );
			return;
		}

		// todo obtain config from...?
		this.config = wgRemoteEditorConfig;

		// we will listen for messages from the remote editor, located at this origin
		var originUri = new mw.uri( this.config.url );
		this.authorizedMessageOrigin = originUri.protocol + '://' + originUri.getAuthority();

		this.$textarea = $( textarea );
		this.$form = $(  this.$textarea.get( 0 ).form  );
		this.launchEditor();
	};
	
	remoteEditor.prototype = {
	
		/**
		 * Does the work of converting the form to use the remote editor.
		 * Creates the iframe with appropriate parameters to authenticate us to the remoteEditor.
		 * Hides the current textarea.
		 * Modify the form so that, on submit, we 
		 *   obtain wikitext from the remote editor's iframe, paste it into the textarea
		 *   fix up the commit message with names of participants
		 *   submit the form
		 */	
		launchEditor: function() { 
			
			console.log( "launch the editor: " + this.config.name );

			// set up the iframe
			var iframeName = 'remoteEditor';

			this.$textarea.hide();

			var form = this.$form.get( 0 );
			
			// parameters will authenticate us to the remoteEditor.
			var apiUrl = mw.config.get( 'wgServer' ) + mw.config.get( 'wgScript' ) + '/api.php';
			var pageTitle = mw.config.get( 'wgPageName' );
			var pageVersion = mw.config.get( 'wgCurRevisionId' );
			var userName = mw.config.get( 'wgUserName' );
			// TODO  sending editToken is a bit of a security problem -- now the remote editor can impersonate the user.
			// pass a hash instead, of userName + editToken? That can be easily calculated again... perhaps need to pass a random salt instead.
			var verifyToken = mw.user.tokens.get( 'editToken' ); 

			// create the iframe
			this.$iframe = $( '<iframe></iframe>' ).attr( { 'width': '100%', 'height': '600px', 'name': iframeName } );
			this.$textarea.before( this.$iframe );

			// tell the iframe about the current content by posting some form data to it
			// TODO these should be query params in the URL of the iframe(?)
			var $postForm = $( '<form style="visibility:hidden; height:0px;"></form>' )
				.attr( { 'method': 'post', 'action': this.config.url, 'target': iframeName } )
				.append( 
					$( '<input name="userName"></input>' ).val( userName ),
					$( '<input name="pageTitle"></input>' ).val( pageTitle ),
					$( '<input name="pageVersion"></input>' ).val( pageVersion ),
					$( '<input name="apiUrl"></input>' ).val( apiUrl ),
					$( '<input name="token"></input>' ).val( verifyToken ),
					$( '<textarea name="text"></textarea>' ).val( form.wpTextbox1.value )
				);
			this.$form.before( $postForm );
			$postForm.submit();

			// listen for the iframe's messages to us ( with text & a summary ) 
			this.listenForEdit();

			// when the save button here is clicked, post a message to the remote editor. We assume it will tell us what to do in listenForEdit
			var _this = this;
			$( "#wpSave" ).click( function() {
					var message = JSON.stringify( {type: "save"} );
					_this.$iframe.get( 0 ).contentWindow.postMessage( message, "*" ); 
					return false;
			} );

		},
	
		/**
		 * Listen for HTML5 postmessages from the remote editor
		 * At the moment, we are only listening for the message that we're done editing, please post data to the wiki.
		 */	
		listenForEdit: function() {
			var _this = this;
			/**
			 * Receive HTML5 postmessage from the iframe. 
			 * (https://developer.mozilla.org/en/DOM/window.postMessage)
			 * The message should be the usual HTML5 postmessage, having an origin, data, and window property. 
			 * We expect message.data to be a serialized JSON string, having a "content" and "comment" property.
			 * @param {postmessage}
			 */
			function receiveMessage( message ) {
				if ( message.origin !== _this.authorizedMessageOrigin ) {
					console.log( 'unauthorized postmessage -- ' + message.origin );
					return;
				}
				var data = JSON.parse( message.data );
				_this.$form.get( 0 ).wpTextbox1.value = data.content;
				var summary = _this.$form.find( "#wpSummary" ).val() + " - " + data.comment;
				_this.$form.find( "#wpSummary" ).val( summary );
				_this.$iframe.hide();
				_this.$form.submit();
			}
			window.addEventListener( "message", receiveMessage, false );
		},
			
	};

	$.fn.remoteEditor = function() {
		var $elements = this;
		$.each( $elements, function( i, textarea ) {
			var editor = new mw.remoteEditor( textarea );
		} );
	};

} )( jQuery, mediaWiki );


