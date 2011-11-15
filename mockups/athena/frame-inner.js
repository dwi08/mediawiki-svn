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

window.addEventListener('DOMContentLoaded', function(event) {
	messageParent({
		event: 'ready'
	});
}, false);

window.addEventListener('load', function(event) {
	messageParent({
		event: 'load'
	});
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

})();

