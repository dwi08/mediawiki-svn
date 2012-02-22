$(function() {

	var prefixSet = {},
		languageNames = {};

	var baseURL = 'https://en.wikipedia.org',
		src = baseURL + "/w/api.php?action=sitematrix&format=json";

	function addPrefix(str, target) {
		str = str.toLowerCase();
		prefixSet[str] = target;
	}

	$.ajax({
		url: src,
		dataType: 'jsonp'		
	}).error(function() {
		alert('Failed to retrieve site/language list');
	}).success(function(data) {
		$.each(data.sitematrix, function(i, item) {
			if (typeof item == "object" && 'code' in item && 'name' in item && item.name) {
				var name = item.code + ' - ' + item.name;
				if (item.name !== item.localname) {
					name += ' (' + item.localname + ')';
				}
				languageNames[item.code] = name;

				addPrefix(item.code, item.code);
				addPrefix(item.name, item.code);
				if (item.localname && item.localname !== item.name) {
					addPrefix(item.localname, item.code);
				}
			}
		});
		
		$('#input-lang').focus();
	});
	
	$('#input-lang').bind('cut paste keydown change', function() {
		setTimeout(function() {
			ping($('#input-lang').val());
		}, 0);
	});
	
	function ping(str) {
		// simple prefix matches
		str = str.toLowerCase();
		$('#suggestions').empty();
		if (str.length == 0) {
			// no-op
			$('<li>').text('Start typing language name or code');
		} else {
			var found = {};
			$.each(prefixSet, function(key, val) {
				if (key.substr(0, str.length) === str && !(val in found)) {
					found[val] = true;
					$('<li>').text(languageNames[val]).appendTo('#suggestions');
				}
			});
		}
	}

});

