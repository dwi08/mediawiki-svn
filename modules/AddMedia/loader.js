/*
* Loader for libAddMedia module:
*/
// Scope everything in "mw"  ( keeps the global namespace clean ) 
( function( mw ) {

	mw.addMessages( {
		"mwe-loading-add-media-wiz" : "Loading add media wizard"
	});
	
	// Add class file paths ( From ROOT )
	mw.addClassFilePaths( {
		"$j.fn.dragDropFile"	: "jquery.dragDropFile.js",
			
		"mw.UploadForm"			: "mw.UploadForm.js",
		
		"mw.UploadHandler"		: "mw.UploadHandler.js",
		"mw.UploadInterface"	: "mw.UploadInterface.js",
		"mw.Firefogg"			: "mw.Firefogg.js",
		"mw.FirefoggGUI"		: "mw.FirefoggGUI.js",
		"mw.FirefoggRender"		: "modules/libSequencer/mw.FirefoggRender.js",
		"mw.RemoteSearchDriver"	: "mw.RemoteSearchDriver.js",			
		
		"baseRemoteSearch"		: "searchLibs/baseRemoteSearch.js",
		"mediaWikiSearch"		: "searchLibs/mediaWikiSearch.js",
		"metavidSearch"			: "searchLibs/metavidSearch.js",
		"archiveOrgSearch"		: "searchLibs/archiveOrgSearch.js",
		"flickrSearch"			: "searchLibs/flickrSearch.js",
		"baseRemoteSearch"		: "searchLibs/baseRemoteSearch.js",
		"kalturaSearch"			: "searchLibs/kalturaSearch.js"
		
	} );	
	
	// Upload form includes "datapicker" 
	mw.addModuleLoader( 'AddMedia.UploadForm', function( callback ){
		var request = [
			[
				'mw.UploadForm',
				'$j.ui'				
			],
			[
				'$j.ui.datepicker'
			]
		];
		mw.load( request , function() {
			callback( 'AddMedia.UploadForm' );
		} );
	})
		
	//Setup the addMediaWizard module
	mw.addModuleLoader( 'AddMedia.addMediaWizard', function( callback ) {
		// Load all the required libs:
		
		var request = [
			[	'mw.RemoteSearchDriver',
				'$j.cookie',
				'$j.fn.textSelection',
				'$j.browserTest', // ( textSelection uses browserTest ) 
				'$j.ui'
			], [
				'$j.ui.resizable',
				'$j.ui.draggable',
				'$j.ui.dialog',
				'$j.ui.tabs',
				'$j.ui.sortable'
			]
		];
		mw.load( request , function() {
			callback( 'AddMedia.addMediaWizard' );
		} );
	});
	
	//Set a variable for the base upload interface for easy inclution
	var baseUploadlibs = [
		[
			'mw.UploadHandler',
			'mw.UploadInterface',
			'$j.ui'
		],
		[
			'$j.ui.progressbar',
			'$j.ui.dialog',
			'$j.ui.draggable'
		]
	];
	
	/*
	* Upload interface loader: 
	*/
	
	mw.addModuleLoader( 'AddMedia.UploadHandler', function( callback ) {
		mw.load( baseUploadlibs , function() {
			callback( 'AddMedia.BaseUploadHandler' );
		});
	});
	
	/**
	 * The Firefogg loaders
	 *
	 * Includes both firefogg & firefogg "GUI" which share some loading logic: 
	 */ 
	 
	// Clone the baseUploadlibs array and add the firefogg lib: 
	var mwBaseFirefoggReq = baseUploadlibs.slice( 0 )
	mwBaseFirefoggReq[ 0 ].push( 'mw.Firefogg' );
	
	mw.addModuleLoader( 'AddMedia.firefogg', function( callback ) {
		
		//Load firefogg libs
		mw.load( mwBaseFirefoggReq, function() {
			callback( 'AddMedia.firefogg' );
		});
	} );
	
	mw.addModuleLoader( 'AddMedia.FirefoggGUI', function( callback ) {		
		// Clone the array: 
		var request = mwBaseFirefoggReq.slice( 0 ) ;
		
		// Add firefogg gui classes to a new "request" var: 
		request.push( [
			'mw.FirefoggGUI',
			'$j.cookie',
			'$j.ui.accordion',
			'$j.ui.slider',
			'$j.ui.datepicker'
		] );
		
		mw.load( request, function() {
			callback( 'AddMedia.FirefoggGUI' );
		});
	} );	
	
	mw.addModuleLoader( 'AddMedia.firefoggRender', function( callback ) {
		mw.load( [
			'mw.UploadHandler',
			'mw.UploadInterface',
			'mw.Firefogg',
			'mw.FirefoggRender'
		], function() {
			callback( 'AddMedia.firefoggRender' );
		});	
	});

} )( window.mw );