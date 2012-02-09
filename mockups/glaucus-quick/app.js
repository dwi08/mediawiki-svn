$(function() {
	
	function setupMenu(button, menu) {
		$(button).bind('click touchstart', function(e) {
			e.preventDefault();
			
			var $active = $('.toolbar button.active');
			if ($active.length) {
				$active.removeClass('active');
				$('.menu').hide();
			} else {
				$(button).addClass('active');
				$(menu).show();
			}
		});
	}
	
	setupMenu('#menu-wiki', '#wiki-menu');
	setupMenu('#menu-you', '#you-menu');

});
