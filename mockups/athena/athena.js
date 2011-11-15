// stub
(function($) {

var wiki = {
	base: 'https://$1.wikipedia.org/w/api.php',
	lang: 'en',

	/**
	 * @return string
	 */
	apiUrl: function() {
		return wiki.base.replace('$1', wiki.lang);
	},

	/**
	 * @param object params: map of parameters to send to API
	 * @return promise
	 */
	api: function(params) {
		var ajaxParams = {
			format: 'json'
		};
		$.extend(ajaxParams, params || {});
		return $.ajax({
			url: wiki.apiUrl(),
			data: ajaxParams,
			dataType: 'jsonp'
		});
	}
};

var ui = {
	startSpinner: function() {
	},
	
	stopSpinner: function() {
	},
	
	showPage: function(title, content) {
		// @fixme trust issues :)
		console.log('Showing title', title);
		console.log('Showing content', content);
		$('#page-content').html(content);
	}
};

var embed = {
	init: function() {
		var $embed = $('#embed');
		$(window).bind('message', function(event) {
			var src = event.originalEvent.source,
				msg = event.originalEvent.data;
			if (src !== $embed[0].contentWindow) {
				// not from our iframe; ignore
				return;
			}
			var key = '[wiki-mobile-embed]';
			if (msg.substr(0, key.length) !== key) {
				// not from our iframe's protocol; ignore
				return;
			}
			var data = JSON.parse(msg.substr(key.length));
			if ('event' in data && typeof data.event === 'string') {
				console.log(data);
				$embed.trigger('embed:' + data.event, data);
			}
		});
		$embed.bind('embed:ready', function(event, data) {
			// Reset scroll height/position
			// Will be sized shortly. :)
			//$embed.height(0);
			$(document).scrollTop(1);
		});
		$embed.bind('embed:navigate', function(event, data) {
			// hack hack hack!
			var matches = data.url.match(/^\/wiki\/(.*)$/);
			if (matches) {
				app.loadPage(decodeURIComponent(matches[1]));
			} else {
				// external!
				document.location = data.url;
			}
		});
		
		// Fancy footwork to rebind a position-fixed footer bar when scrolling to bottom
		$(window).bind('scroll', function() {
			var viewportTop = $(document).scrollTop(),
				windowHeight = $(window).height(),
				viewportBottom = viewportTop + windowHeight,
				barHeight = $('#bottom-bar').height(),
				barTop = $('#bottom').position().top;

			if (viewportBottom - barHeight >= barTop) {
				$('#bottom-bar').addClass('inplace');
			} else {
				$('#bottom-bar').removeClass('inplace');
			}
		});

		$embed.bind('embed:resize', function(event, data) {
			$embed.height(data.height);
			$(window).trigger('scroll');
		});
		$embed.bind('embed:click', function(event, data) {
			// toggle the floating state
			$('.bar').toggleClass('bar-float');
		});
	},

	/**
	 * @return promise
	 */
	loadPage: function(title) {
		var $embed = $('#embed');
		
		// Load that URL...
		$embed.attr('src', 'proxy.php?title=' + encodeURIComponent(title.replace(' ', '_')));
		var deferred = new $.Deferred();
		$embed.bind('embed:load', function() {
			deferred.resolve();
			$embed.unbind('embed:load');
		});
		return deferred.promise();
	}
};


var app = {
	init: function() {
		embed.init();
		
		$('.button-main').click(function() {
			// temp hack
			// toggle the floating state
			$('.bar').toggleClass('bar-float');
		});
		
		app.loadPage('While My Guitar Gently Weeps');
	},

	loadPage: function(title) {
		ui.startSpinner();
		embed.loadPage(title).then(function() {
			ui.stopSpinner();
		});
	}
};

$(function() {
	app.init();
});

})(jQuery);

