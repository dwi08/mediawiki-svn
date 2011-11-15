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

var app = {
	loadPage: function(title) {
		ui.startSpinner();
		wiki.api({
			action: 'query',
			prop: 'revisions',
			titles: title,
			rvprop: 'timestamp|content',
			rvparse: 1
		}).then(function(data) {
			console.log('page!', data);
			var pageId, page;
			$.each(data.query.pages, function() {
				pageId = this.id;
				page = this;
			});
			var revId, rev;
			$.each(page.revisions, function() {
				revId = this.revid;
				rev = this;
			});
			ui.showPage(page.title, rev['*']);
			ui.stopSpinner();
		});
	},
};

$(function() {
	app.loadPage('While My Guitar Gently Weeps');
});

})(jQuery);

