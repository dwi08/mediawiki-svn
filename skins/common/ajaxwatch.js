// dependencies:
// * ajax.js:
  /*extern sajax_init_object, sajax_do_call */
// * wikibits.js:
  /*extern changeText, akeytt, hookEvent, jsMsg */

// These should have been initialized in the generated js
/*extern wgAjaxWatch, wgPageName */

if(typeof wgAjaxWatch === "undefined" || !wgAjaxWatch) {
	var wgAjaxWatch = {
		watchMsg: "Watch",
		unwatchMsg: "Unwatch",
		watchingMsg: "Watching...",
		unwatchingMsg: "Unwatching..."
	};
}

wgAjaxWatch.supported = true; // supported on current page and by browser
wgAjaxWatch.watching = false; // currently watching page
wgAjaxWatch.inprogress = false; // ajax request in progress
wgAjaxWatch.timeoutID = null; // see wgAjaxWatch.ajaxCall
wgAjaxWatch.watchLinks = []; // "watch"/"unwatch" links
wgAjaxWatch.iconMode = false; // new icon driven functionality 
wgAjaxWatch.imgBasePath = ""; // base img path derived from icons on load

wgAjaxWatch.setLinkText = function(newText) {
  if( wgAjaxWatch.iconMode ) {
    for ( i = 0; i < wgAjaxWatch.watchLinks.length; i++ ) {
  		wgAjaxWatch.watchLinks[i].firstChild.alt = newText;
  		if( newText==wgAjaxWatch.watchingMsg || newText==wgAjaxWatch.unwatchingMsg ) {
  		  wgAjaxWatch.watchLinks[i].firstChild.src = wgAjaxWatch.imgBasePath
					+ "/common/images/spinner.gif";
  		} else if( newText==wgAjaxWatch.watchMsg ) {
  		  wgAjaxWatch.watchLinks[i].firstChild.src = wgAjaxWatch.imgBasePath
					+ "/vector/images/watch_off.gif";
  		} else if( newText==wgAjaxWatch.unwatchMsg ) {
  		  wgAjaxWatch.watchLinks[i].firstChild.src = wgAjaxWatch.imgBasePath
					+ "/vector/images/watch_on.gif";
  		}
  	}
  } else{
    for ( i = 0; i < wgAjaxWatch.watchLinks.length; i++ ) {
  		changeText( wgAjaxWatch.watchLinks[i], newText );
  	}
  }
};

wgAjaxWatch.setLinkID = function(newId) {
	// We can only set the first one
	wgAjaxWatch.watchLinks[0].parentNode.setAttribute( 'id', newId );
	akeytt(newId); // update tooltips for Monobook
};

wgAjaxWatch.setHref = function( string ) {
	for( i = 0; i < wgAjaxWatch.watchLinks.length; i++ ) {
		if( string == 'watch' ) {
			wgAjaxWatch.watchLinks[i].href = wgAjaxWatch.watchLinks[i].href
				.replace( /&action=unwatch/, '&action=watch' );
		} else if( string == 'unwatch' ) {
			wgAjaxWatch.watchLinks[i].href = wgAjaxWatch.watchLinks[i].href
				.replace( /&action=watch/, '&action=unwatch' );
		}
	}
}

wgAjaxWatch.ajaxCall = function() {
	if(!wgAjaxWatch.supported) {
		return true;
	} else if (wgAjaxWatch.inprogress) {
		return false;
	}
	if(!wfSupportsAjax()) {
		// Lazy initialization so we don't toss up
		// ActiveX warnings on initial page load
		// for IE 6 users with security settings.
		wgAjaxWatch.supported = false;
		return true;
	}

	wgAjaxWatch.inprogress = true;
	wgAjaxWatch.setLinkText( wgAjaxWatch.watching
		? wgAjaxWatch.unwatchingMsg : wgAjaxWatch.watchingMsg);
	sajax_do_call(
		"wfAjaxWatch",
		[wgPageName, (wgAjaxWatch.watching ? "u" : "w")], 
		wgAjaxWatch.processResult
	);
	// if the request isn't done in 10 seconds, allow user to try again
	wgAjaxWatch.timeoutID = window.setTimeout(
		function() { wgAjaxWatch.inprogress = false; },
		10000
	);
	return false;
};

wgAjaxWatch.processResult = function(request) {
	if(!wgAjaxWatch.supported) {
		return;
	}
	var response = request.responseText;
	if( response.match(/^<w#>/) ) {
		wgAjaxWatch.watching = true;
		wgAjaxWatch.setLinkText(wgAjaxWatch.unwatchMsg);
		wgAjaxWatch.setLinkID("ca-unwatch");
		wgAjaxWatch.setHref( 'unwatch' );
	} else if( response.match(/^<u#>/) ) {
		wgAjaxWatch.watching = false;
		wgAjaxWatch.setLinkText(wgAjaxWatch.watchMsg);
		wgAjaxWatch.setLinkID("ca-watch");
		wgAjaxWatch.setHref( 'watch' );
	} else {
		// Either we got a <err#> error code or it just plain broke.
		window.location.href = wgAjaxWatch.watchLinks[0].href;
		return;
	}
	jsMsg( response.substr(4), 'watch' );
	wgAjaxWatch.inprogress = false;
	if(wgAjaxWatch.timeoutID) {
		window.clearTimeout(wgAjaxWatch.timeoutID);
	}
	// Bug 12395 - avoid some watch link confusion on edit
	var watchthis = document.getElementById("wpWatchthis");
	if( watchthis && response.match(/^<[uw]#>/) ) {
		watchthis.checked = response.match(/^<w#>/) ? "checked" : "";
	}
	return;
};

wgAjaxWatch.onLoad = function() {
	// This document structure hardcoding sucks.  We should make a class and
	// toss all this out the window.
	
	var el1 = document.getElementById("ca-unwatch");
	var el2 = null;
	if ( !el1 ) {
		el1 = document.getElementById("mw-unwatch-link1");
		el2 = document.getElementById("mw-unwatch-link2");
	}
	if( el1 ) {
		wgAjaxWatch.watching = true;
	} else {
		wgAjaxWatch.watching = false;
		el1 = document.getElementById("ca-watch");
		if ( !el1 ) {
			el1 = document.getElementById("mw-watch-link1");
			el2 = document.getElementById("mw-watch-link2");
		}
		if( !el1 ) {
			wgAjaxWatch.supported = false;
			return;
		}
	}
	
	// If we're using the icon, add rollover affects
	try{
	  if( el1.firstChild.firstChild.tagName.match( /img/i ) ) {
			wgAjaxWatch.iconMode = true;
  	  wgAjaxWatch.imgBasePath = el1.firstChild.firstChild.src
				.replace( /\/vector\/images\/watch_(off|on).gif/, "" );
  	  el1.firstChild.onmouseover = function( e ) {
  	    if ( !wgAjaxWatch.inprogress )
  		this.firstChild.src = wgAjaxWatch.imgBasePath
			+ "/vector/images/watch_over.gif";
  	  }
  	  el1.firstChild.onmouseout = function( e ) {
  	    if ( !wgAjaxWatch.inprogress )
		this.firstChild.src = wgAjaxWatch.imgBasePath
			+ "/vector/images/watch_" + ( wgAjaxWatch.watching ?
				"on.gif" : "off.gif" );
  	  }
  	}
	} catch( e ) {
	  // not using the icon 
	}


	// The id can be either for the parent (Monobook-based) or the element
	// itself (non-Monobook)
	wgAjaxWatch.watchLinks.push( el1.tagName.toLowerCase() == "a"
		? el1 : el1.firstChild );

	if( el2 ) {
		wgAjaxWatch.watchLinks.push( el2 );
	}

	// I couldn't get for (watchLink in wgAjaxWatch.watchLinks) to work, if
	// you can be my guest.
	for( i = 0; i < wgAjaxWatch.watchLinks.length; i++ ) {
		wgAjaxWatch.watchLinks[i].onclick = wgAjaxWatch.ajaxCall;
	}
	return;
};

hookEvent("load", wgAjaxWatch.onLoad);
