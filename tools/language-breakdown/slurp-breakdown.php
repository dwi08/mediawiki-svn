<?php

require "langs.php";

$url = "http://stats.wikimedia.org/wikimedia/squids/SquidReportPageViewsPerCountryBreakdown.htm";

$file = file_get_contents($url);
if (!$file) {
	die("http fetch error\n");
}

$dom = new DOMDocument();
error_reporting(0);
if (!$dom->loadHTML($file)) {
	die("parse error\n");
}
error_reporting(E_ALL);

$tables = $dom->getElementsByTagName('table');
$table = $tables->item(1); // skip first table, is a header

$data = array();
$row = array();

foreach ($table->childNodes as $node) {
	if ($node->nodeName !== 'tr') {
		continue;
	}
	
	$th = $node->getElementsByTagName('th')->item(0);
	if (!$th) {
		continue;
	}

	$class = $th->getAttribute('class');
	if ($class == 'lh3') {
		// new country
		$a = $node->getElementsByTagName('a')->item(0);
		$countryName = $a->getAttribute('name');
		echo "* $countryName\n";
		$data[$countryName] = $row;
		$row = array();
	} else if ($class == 'l') {
		// language entry
		$language = $langs[$th->textContent];
		$td = $node->getElementsByTagName('td')->item(0);
		$percent = $td->textContent;
		$row[$language] = $percent;
		//$langs[$language] = $language;
		echo "** $language # $percent\n";
	} else {
		die("unknown node? th class $class\n");
	}
}

/*

echo "\n\n\n";
sort($langs);
foreach($langs as $lang) {
	echo "'$lang' => '',\n";
}
*/

