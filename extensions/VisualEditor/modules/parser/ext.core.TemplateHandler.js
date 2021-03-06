/**
 * Template and template argument handling, first cut.
 *
 * AsyncTokenTransformManager objects provide preprocessor-frame-like
 * functionality once template args etc are fully expanded, and isolate
 * individual transforms from concurrency issues. Template expansion is
 * controlled using a tplExpandData structure created independently for each
 * handled template tag.
 *
 * @author Gabriel Wicke <gwicke@wikimedia.org>
 * @author Brion Vibber <brion@wikimedia.org>
 */
var $ = require('jquery'),
	request = require('request'),
	events = require('events'),
	qs = require('querystring'),
	ParserFunctions = require('./ext.core.ParserFunctions.js').ParserFunctions,
	AttributeTransformManager = require('./mediawiki.TokenTransformManager.js')
									.AttributeTransformManager,
	defines = require('./mediawiki.parser.defines.js');


function TemplateHandler ( manager ) {
	this.reset();
	this.register( manager );
	this.parserFunctions = new ParserFunctions( manager );
}

TemplateHandler.prototype.reset = function ( token ) {
	return {token: token};
};

// constants
TemplateHandler.prototype.rank = 1.1;

TemplateHandler.prototype.register = function ( manager ) {
	this.manager = manager;
	// Register for template and templatearg tag tokens
	manager.addTransform( this.onTemplate.bind(this), 
			this.rank, 'tag', 'template' );

	// Template argument expansion
	manager.addTransform( this.onTemplateArg.bind(this), 
			this.rank, 'tag', 'templatearg' );

};


/** 
 * Main template token handler
 *
 * Expands target and arguments (both keys and values) and either directly
 * calls or sets up the callback to _expandTemplate, which then fetches and
 * processes the template.
 */
TemplateHandler.prototype.onTemplate = function ( token, frame, cb ) {
	//console.warn('onTemplate! ' + JSON.stringify( token, null, 2 ) + 
	//		' args: ' + JSON.stringify( this.manager.args ));


	// create a new temporary frame for argument and title expansions
	var tplExpandData = {
			args: {},
			manager: this.manager,
			cb: cb,
			origToken: token,
			resultTokens: [],
			attribsAsync: true,
			overallAsync: false,
			expandDone: false
		},
		transformCB,
		i = 0,
		res;

	var attributes = [token.attribs.shift()];
	if( token.attribs.length ) {
		attributes = attributes.concat( this._nameArgs( token.attribs ) );
	}

	this.manager.env.dp( 'before AttributeTransformManager: ', 
						attributes );
	new AttributeTransformManager( 
				this.manager, 
				this._returnAttributes.bind( this, tplExpandData )
			).process( attributes );

	// Unblock finish
	if ( ! tplExpandData.attribsAsync ) {
		// Attributes were transformed synchronously
		this.manager.env.dp ( 'sync attribs for ', tplExpandData.target,
				tplExpandData.expandedArgs
		);
		// All attributes are fully expanded synchronously (no IO was needed)
		return this._expandTemplate ( tplExpandData );
	} else {
		// Async attribute expansion is going on
		this.manager.env.dp( 'async return for ', token );
		tplExpandData.overallAsync = true;
		return { async: true };
	}
};

/**
 * Create positional (number) keys for arguments without explicit keys
 */
TemplateHandler.prototype._nameArgs = function ( attribs ) {
	var n = 1,
		out = [];
	for ( var i = 0, l = attribs.length; i < l; i++ ) {
		// FIXME: Also check for whitespace-only named args!
		if ( ! attribs[i].k.length ) {
			out.push( {k: [ n.toString() ], v: attribs[i].v } );
			n++;
		} else {
			out.push( attribs[i] );
		}
	}
	this.manager.env.dp( '_nameArgs: ', out );
	return out;
};

/**
 * Callback for argument (including target) expansion in AttributeTransformManager
 */
TemplateHandler.prototype._returnAttributes = function ( tplExpandData, 
															attributes ) 
{
	this.manager.env.dp( 'TemplateHandler._returnAttributes: ', attributes );
	// Remove the target from the attributes
	tplExpandData.attribsAsync = false;
	tplExpandData.target = attributes[0].v;
	attributes.shift();
	tplExpandData.expandedArgs = attributes;
	if ( tplExpandData.overallAsync ) {
		this._expandTemplate ( tplExpandData );
	}
};

/**
 * Fetch, tokenize and token-transform a template after all arguments and the
 * target were expanded.
 */
TemplateHandler.prototype._expandTemplate = function ( tplExpandData ) {
	//console.warn('TemplateHandler.expandTemplate: ' +
	//		JSON.stringify( tplExpandData, null, 2 ) );
	var res;

	
	if ( ! tplExpandData.target ) {
		this.manager.env.dp( 'No target! ', tplExpandData );
		console.trace();
	}

	// TODO:
	// check for 'subst:'
	// check for variable magic names
	// check for msg, msgnw, raw magics
	// check for parser functions

	// First, check the target for loops
	var target = this.manager.env.tokensToString( tplExpandData.target ).trim();

	var args = this.manager.env.KVtoHash( tplExpandData.expandedArgs );

	this.manager.env.dp( 'argHash: ', args );

	var prefix = target.split(':', 1)[0].toLowerCase().trim();
	if ( prefix && 'pf_' + prefix in this.parserFunctions ) {
		var funcArg = target.substr( prefix.length + 1 );
		this.manager.env.tp( 'func prefix: ', prefix,
				' args=', tplExpandData.expandedArgs,
				' funcArg=', funcArg);
		//this.manager.env.dp( 'entering prefix', funcArg, args  );
		res = this.parserFunctions[ 'pf_' + prefix ]( funcArg, 
				tplExpandData.expandedArgs, args, tplExpandData.origToken.attribs );

		// XXX: support async parser functions!
		if ( tplExpandData.overallAsync ) {
			this.manager.env.dp( 'TemplateHandler._expandTemplate: calling back ',
					'after parser func ', prefix, ' with res:', res );
			return tplExpandData.cb( res, false, true );
		} else {
			this.manager.env.dp( 'TemplateHandler._expandTemplate: sync return ',
					'after parser func ', prefix, ' with res:', res );
			return { tokens: res };
			//data.reset();
		}
	}
	this.manager.env.tp( 'template target: ' + target );

	// now normalize the target before template processing
	target = this.manager.env.normalizeTitle( target );

	var checkRes = this.manager.loopAndDepthCheck.check( target, this.manager.env.maxDepth );
	if( checkRes ) {
		// Loop detected or depth limit exceeded, abort!
		res = [
				checkRes,
				new TagTk( 'a', [{k: 'href', v: target}] ),
				target,
				new EndTagTk( 'a' )
			];
		if ( tplExpandData.overallAsync ) {
			return tplExpandData.cb( res, false, true );
		} else {
			return { tokens: res };
		}
	}

	// Get a nested transformation pipeline for the input type. The input
	// pipeline includes the tokenizer, synchronous stage-1 transforms for
	// 'text/wiki' input and asynchronous stage-2 transforms). 
	var inputPipeline = this.manager.newChildPipeline( 
				this.manager.inputType || 'text/wiki', 
				args,
				tplExpandData.target
			);

	// Hook up the inputPipeline output events to our handlers
	inputPipeline.addListener( 'chunk', this._onChunk.bind ( this, tplExpandData ) );
	inputPipeline.addListener( 'end', this._onEnd.bind ( this, tplExpandData ) );
	

	// Resolve a possibly relative link
	var templateName = this.manager.env.resolveTitle( 
			target,
			'Template' 
		);

	// XXX: notes from brion's mediawiki.parser.environment
	// resolve template name
	// load template w/ canonical name
	// load template w/ variant names (language variants)

	// For now, just fetch the template and pass the callback for further
	// processing along.
	this._fetchTemplateAndTitle( 
			templateName, 
			this._processTemplateAndTitle.bind( this, inputPipeline ),
			tplExpandData
		);

	// If nothing was async so far and the template source was retrieved and
	// fully processed without async requests (using the cache), then
	// expandDone is set to true in our _onEnd handler.
	if ( tplExpandData.overallAsync || 
			! tplExpandData.expandDone ) {
		tplExpandData.overallAsync = true;
		this.manager.env.dp( 'Async return from _expandTemplate for ', 
				tplExpandData.target );
		return { async: true };
	} else {
		this.manager.env.dp( 'Sync return from _expandTemplate for ',
				tplExpandData.target, ' : ',
				tplExpandData.result
				);
		return tplExpandData.result;
	}
};


/**
 * Handle chunk emitted from the input pipeline after feeding it a template
 */
TemplateHandler.prototype._onChunk = function( tplExpandData, chunk ) {
	// We encapsulate the output by default, so collect tokens here.
	this.manager.env.dp( 'TemplateHandler._onChunk', chunk );
	tplExpandData.resultTokens = tplExpandData.resultTokens.concat( chunk );
};

/**
 * Handle the end event emitted by the parser pipeline after fully processing
 * the template source.
 */
TemplateHandler.prototype._onEnd = function( tplExpandData, token ) {
	this.manager.env.dp( 'TemplateHandler._onEnd', tplExpandData.resultTokens );
	tplExpandData.expandDone = true;
	var res = tplExpandData.resultTokens;
	// Strip 'end' tokens and trailing newlines
	var l = res[res.length - 1];
	while ( res.length &&
			(	l.type === 'END'  || l.constructor === NlTk ) 
	) 
	{
		this.manager.env.dp( 'TemplateHandler, stripping end or whitespace tokens' );
		res.pop();
		l = res[res.length - 1];
	}

	// Could also encapsulate the template tokens here, if that turns out
	// better for the editor.

	//console.warn( 'TemplateHandler._onEnd: ' + JSON.stringify( res, null, 2 ) );

	if ( tplExpandData.overallAsync ) {
		this.manager.env.dp( 'TemplateHandler._onEnd: calling back with res:', res );
		tplExpandData.cb( res, false, true );
	} else {
		this.manager.env.dp( 'TemplateHandler._onEnd: synchronous return!' );
		tplExpandData.result = { tokens: res };
		//data.reset();
	}
};



/**
 * Process a fetched template source
 */
TemplateHandler.prototype._processTemplateAndTitle = function( pipeline, src, title ) {
	// Feed the pipeline. XXX: Support different formats.
	this.manager.env.dp( 'TemplateHandler._processTemplateAndTitle: ', src );
	pipeline.process ( src );
};



/**
 * Fetch a template
 */
TemplateHandler.prototype._fetchTemplateAndTitle = function ( title, callback, tplExpandData ) {
	// @fixme normalize name?
	var self = this;
	if ( title in this.manager.env.pageCache ) {
		// Unwind the stack
		process.nextTick(
				function () {
					callback( self.manager.env.pageCache[title], title );
				} 
		);
	} else if ( ! this.manager.env.fetchTemplates ) {
		callback( 'Warning: Page/template fetching disabled, and no cache for ' + 
				title, title );
	} else {
		
		// We are about to start an async request for a template, so mark this
		// template expansion as such.
		tplExpandData.overallAsync = true;
		this.manager.env.dp( 'Note: trying to fetch ', title );

		// Start a new request if none is outstanding
		this.manager.env.dp( 'requestQueue: ', this.manager.env.requestQueue );
		if ( this.manager.env.requestQueue[title] === undefined ) {
			this.manager.env.tp( 'Note: Starting new request for ' + title );
			this.manager.env.requestQueue[title] = new TemplateRequest( this.manager, title );
		}
		// Append a listener to the request
		this.manager.env.requestQueue[title].once( 'src', callback );

	}
};


/*********************** Template argument expansion *******************/

/**
 * Expand template arguments with tokens from the containing frame.
 */
TemplateHandler.prototype.onTemplateArg = function ( token, frame, cb ) {
	
	token.resultTokens = false;

	new AttributeTransformManager ( 
				this.manager, 
				this._returnArgAttributes.bind( this, token, cb, frame ) 
			).process( token.attribs );

	if ( token.resultTokens !== false ) {
		// synchronous return
		//console.warn( 'synchronous attribute expand: ' + JSON.stringify( token.resultTokens ) );

		return { tokens: token.resultTokens };
	} else {
		//console.warn( 'asynchronous attribute expand: ' + JSON.stringify( token, null, 2 ) );
		// asynchronous return
		token.resultTokens = [];
		return { async: true };
	}
};

TemplateHandler.prototype._returnArgAttributes = function ( token, cb, frame, attributes ) {
	//console.warn( '_returnArgAttributes: ' + JSON.stringify( attributes ));
	var argName = this.manager.env.tokensToString( attributes[0].v ).trim(),
		res;
	if ( argName in this.manager.args ) {
		// return tokens for argument
		//console.warn( 'templateArg found: ' + argName + 
		//		' vs. ' + JSON.stringify( this.manager.args ) ); 
		res = this.manager.args[argName];
	} else {
		var defaultValue = (attributes[1] && ! attributes[1].k.length && attributes[1].v) || false;
		this.manager.env.dp( 'templateArg not found: ', argName,
				' vs. ', defaultValue );
		if ( defaultValue ) {
			res = defaultValue;
		} else {
			res = [ '{{{' + argName + '}}}' ];
		}
	}
	if ( token.resultTokens !== false ) {
		cb( res );
	} else {
		token.resultTokens =  res;
	}
};


/***************** Template fetch request helper class ********/

function TemplateRequest ( manager, title ) {
	// Increase the number of maximum listeners a bit..
	this.setMaxListeners( 10000 );
	var self = this,
		url = manager.env.wgScriptPath + '/api' + 
		manager.env.wgScriptExtension +
		'?' + 
		qs.stringify( {
			format: 'json',
			action: 'query',
			prop: 'revisions',
			rvprop: 'content',
			titles: title
		} );
		//'?format=json&action=query&prop=revisions&rvprop=content&titles=' + title;

	request({
		method: 'GET',
		followRedirect: true,
		url: url,
		headers: { 
			'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64; rv:9.0.1) ' +
							'Gecko/20100101 Firefox/9.0.1 Iceweasel/9.0.1' 
		}
	}, 
	function (error, response, body) {
		//console.warn( 'response for ' + title + ' :' + body + ':' );
		if(error) {
			manager.env.dp(error);	
			self.emit('src', 'Page/template fetch failure for title ' + title, title);
		} else if(response.statusCode ==  200) {
			var src = '',
				data,
				normalizedTitle;
			try {
				//console.warn( 'body: ' + body );
				data = JSON.parse( body );
			} catch(e) {
				console.warn( "Error: while parsing result. Error was: " );
				console.warn( e );
				console.warn( "Response that didn't parse was:");
				console.warn( "------------------------------------------\n" + body );
				console.warn( "------------------------------------------" );
			}
			try {
				$.each( data.query.pages, function(i, page) {
					if (page.revisions && page.revisions.length) {
						src = page.revisions[0]['*'];
						normalizeTitle = page.title;
					}
				});
			} catch ( e2 ) {
				console.warn( 'Did not find page revisions in the returned body:' + body );
				src = '';
			}
			//console.warn( 'Page ' + title + ': got ' + src );
			manager.env.tp( 'Retrieved ' + title );
			manager.env.pageCache[title] = src;
			self.emit( 'src', src, title );
		}
		// XXX: handle other status codes

		// Remove self from request queue
		manager.env.dp( 'trying to remove ', title, ' from requestQueue' );
		delete manager.env.requestQueue[title];
		manager.env.dp( 'after deletion:', manager.env.requestQueue );
	});
}

		/*
		* XXX: The jQuery version does not quite work with node, but we keep
		* it around for now.
		$.ajax({
			url: url,
			data: {
				format: 'json',
				action: 'query',
				prop: 'revisions',
				rvprop: 'content',
				titles: title
			},
			success: function(data, statusString, xhr) {
				console.warn( 'Page ' + title + ' success ' + JSON.stringify( data ) );
				var src = null, title = null;
				$.each(data.query.pages, function(i, page) {
					if (page.revisions && page.revisions.length) {
						src = page.revisions[0]['*'];
						title = page.title;
					}
				});
				if (typeof src !== 'string') {
					console.warn( 'Page ' + title + 'not found! Got ' + src );
					callback( 'Page ' + title + ' not found' );
				} else {
					// Add to cache
					console.warn( 'Page ' + title + ': got ' + src );
					this.manager.env.pageCache[title] = src;
					callback(src, title);
				}
			},
			error: function(xhr, msg, err) {
				console.warn( 'Page/template fetch failure for title ' + 
						title + ', url=' + url + JSON.stringify(xhr) + ', err=' + err );
				callback('Page/template fetch failure for title ' + title);
			},
			dataType: 'json',
			cache: false, // @fixme caching, versions etc?
			crossDomain: true
		});
		*/

// Inherit from EventEmitter
TemplateRequest.prototype = new events.EventEmitter();
TemplateHandler.prototype.constructor = TemplateRequest;


if (typeof module == "object") {
	module.exports.TemplateHandler = TemplateHandler;
}
