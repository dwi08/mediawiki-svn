<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
	<title>Wikimedia Subversion user list</title>
	<meta charset="UTF-8">
</head>
<body>
<h1>Wikimedia Subversion user list</h1>
<table border="1">
	<tr>
		<th>Username</th>
		<th>Real name</th>
		<th>Ready for git?</th>
	</tr>
<?php

$time = microtime( true );
$svnUsersDir = '/var/cache/svnusers';
exec( "getent passwd", $lines );
exec( "HOME=/tmp /usr/bin/svn up $svnUsersDir 2>&1", $output, $retval );
if ( $retval ) {
	$error = implode( "\n", $output );
} else {
	$error = false;
}

foreach ( $lines as $line ) {
	$parts = explode( ':', trim( $line ) );
	if ( !isset( $parts[2] ) || $parts[2] < 500 ) {
		continue;
	}
	$userInfo = getUserInfo( $parts[0] );
	$encUsername = htmlspecialchars( $parts[0] );
	$userInfo = array_map( 'htmlspecialchars', $userInfo );
	$link = $userInfo['url'] ? "<a href=\"{$userInfo['url']}\">$encUsername</a>" : $encUsername;
	$readyForGit = isset( $userInfo['name'] ) && isset( $userInfo['email'] ) ? 'Y' : 'N';

	$rows[$parts[0]] = <<<EOT
<tr id="$encUsername">
<td>$link</td>
<td>{$userInfo['name']}</td>
<td>$readyForGit</td>
</tr>

EOT;
}
ksort( $rows );
echo implode( '', $rows ) . "</table>\n";
echo "<!-- Request time: " . ( microtime( true ) - $time ) . " -->\n";
if ( $retval ) {
	echo "<p>Error: " . htmlspecialchars( $error ) . "</p>\n";
}
echo "</html>\n";

function getUserInfo( $userName ) {
	$userInfo = array(
		'name' => '',
		'url' => ''
	);
	$userFileLines = @file( "$svnUsersDir/$userName" );
	if ( $userFileLines ) {
		foreach ( $userFileLines as $userLine ) {
			if ( preg_match( '/^([\w-]+):\s*(.*?)\s*$/', $userLine, $m ) ) {
				$field = strtolower( $m[1] );
				$value = $m[2];
				$userInfo[$field] = $value;
			}
		}
	}
	return $userInfo;
}
