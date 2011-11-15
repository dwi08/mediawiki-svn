// Limited embed frame-inner API
// Sends some events up to the parent when we navigate

(function() {

function messageParent(data) {
	var msg = '[wiki-mobile-embed]' + JSON.stringify(data);
	if (window.parent && window.parent !== window) {
		window.parent.postMessage(msg, '*');
	} else {
		alert(msg);
	}
}

function sendSize() {
	//var height = document.documentElement.scrollHeight;
	var height = document.getElementById('document-final').offsetTop + 8;
	messageParent({
		event: 'resize',
		height: height
	});
}

window.addEventListener('DOMContentLoaded', function(event) {
	messageParent({
		event: 'ready'
	});
	sendSize();
}, false);

window.addEventListener('load', function(event) {
	messageParent({
		event: 'load'
	});
	sendSize();
}, false);

window.addEventListener('click', function(event) {
	var target = event.target;
	if (target.nodeName.toLowerCase() == 'a') {
		event.stopPropagation();
		event.preventDefault();
		messageParent({
			event: 'navigate',
			url: target.getAttribute('href')
		});
	}
}, true );

window.addEventListener('resize', function(event) {
	sendSize();
}, false );

document.addEventListener('MozScrollAreaChanged', function(event) {
	// https://developer.mozilla.org/en/DOM/Detecting_document_width_and_height_changes
	sendSize();
}, false );

// hack!

var orig = {
	wm_reveal_for_hash: wm_reveal_for_hash,
	wm_toggle_section: wm_toggle_section
};

wm_reveal_for_hash = function(hash) {
	orig.wm_reveal_for_hash(hash);
	sendSize();
};

wm_toggle_section = function(section) {
	orig.wm_toggle_section(section);
	sendSize();
};


// Send generic unhandled taps up to parent
// May need to show/hide toolbars etc
document.addEventListener('click', function(event) {
	messageParent({
		event: 'click'
	});
}, false);

})();

