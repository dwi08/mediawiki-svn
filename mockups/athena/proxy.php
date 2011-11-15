<?php

$base = 'http://en.wikipedia.org/wiki/$1?useformat=mobile';

function failOut($http, $text) {
	header('HTTP/1.x ' . $http);
	die(htmlspecialchars($text));
}

if (!isset($_GET['title'])) {
	failOut('400 Invalid Request', 'No title');
}
if (!is_string($_GET['title'])) {
	failOut('400 Invalid Request', 'Invalid title');
}

$title = $_GET['title'];

if (get_magic_quotes_gpc()) {
	$title = removeslashes($title);
}

$url = str_replace(
	'$1',
	urlencode(str_replace(' ', '_', $title)),
	$base
);


$context = stream_context_create(
	array(
		'http' => array(
			// fake "iPhone" in there to force a mode where show/hide works
			// this should not be required, grrrr!
			'user_agent' => 'Wikipedia Mobile (Athena mockup; iPhone)',
		),
	)
);

$content = file_get_contents($url, false, $context);

$dom = new DOMDocument();
$dom->loadHTML($content);

// Inject our frame client API
$body = $dom->getElementsByTagName('body')->item(0);

$script = $dom->createElement('script');
$script->setAttribute('src', 'frame-inner.js');
$body->appendChild($script);

$final = $dom->createElement('div');
$final->setAttribute('id', 'document-final');
$body->appendChild($final);

// Hide header/footer
function hide($element) {
	// don't remove header or some scripts will break
	$element->setAttribute('style', 'display: none');
}
hide($dom->getElementById('header'));
hide($dom->getElementById('footer'));

$html = $dom->saveHTML();
echo $html;

