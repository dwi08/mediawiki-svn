$(function() {

	function hideMenus() {
		var $active = $('.toolbar button.active');
		if ($active.length) {
			$active.removeClass('active');
			$('.menu').hide();
		}
	}	
	function setupMenu(button, menu) {
		$(button).bind('click touchstart mousedown', function(e) {
			e.preventDefault();

			hideMenus();
			
			$(button).addClass('active');
			$('#menu-pane').show();
			$(menu).show();
		});
	}
	
	$('#menu-pane').bind('click touchstart mousedown', function(e) {
		e.preventDefault();
		hideMenus();
		$('#menu-pane').hide();
	});
	setupMenu('#menu-wiki', '#wiki-menu');
	setupMenu('#menu-article', '#article-menu');
	setupMenu('#menu-you', '#you-menu');
	setupMenu('#menu-contribute', '#contribute-menu');
	setupMenu('#menu-search', '#search-menu');

	$(window).scrollTop(0);
});
