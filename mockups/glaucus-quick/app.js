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
			e.stopPropagation();

			hideMenus();
			
			$(button).addClass('active');
			$('#menu-pane').show();
			$(menu).show();
		});
	}
	
	$('#menu-pane').bind('click touchstart mousedown', function(e) {
		e.preventDefault();
		e.stopPropagation();
		hideMenus();
		$('#menu-pane').hide();
	});
	setupMenu('#menu-wiki', '#wiki-menu');
	setupMenu('#menu-article', '#article-menu');
	setupMenu('#menu-you', '#you-menu');
	setupMenu('#menu-contribute', '#contribute-menu');
	setupMenu('#menu-search', '#search-menu');

	function sendHeaderTo(y) {
		$('header').css('top', y + 'px');
		$('#menu-pane').css('top', (y + 44) + 'px');
	}
	
	function hideMenuBar() {
		hideMenus();
		$('#menu-pane').hide();
		sendHeaderTo(0);
	}
	
	function showMenuThingy() {
		var top = $(window).scrollTop(),
			current = $('header').css('top');
		if (current == '0px') {
			sendHeaderTo(top);
		} else {
			hideMenuBar();
		}
	}

	var alreadyClicked = true;
	$(document).bind('click', function() {
		if (alreadyClicked) {
			alreadyClicked = false;
		} else {
			showMenuThingy();
		}
	});
	$(document).bind('scroll', function() {
		hideMenuBar();
	});

	$(document).bind('touchstart', function() {
		var current = $('header').css('top');
		if (current == '0px') {
			// simulate click handling for some reason it doesn't work
			$(document).bind('touchend.menubar', function(e) {
				$(document).unbind('touchmove.menubar');
				$(document).unbind('touchend.menubar');
				showMenuThingy();
				alreadyClicked = true;
			});
			$(document).bind('touchmove.menubar', function() {
				// moved too much! cancel
				$(document).unbind('touchmove.menubar');
				$(document).unbind('touchend.menubar');
			});
		} else {
			// for start of scrolling or touch to clear
			hideMenuBar();
			alreadyClicked = true;
		}
	});


	$(window).scrollTop(0);
});
