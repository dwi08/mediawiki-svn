<?php
/* Rotbot © Luxo 2008


	*/

/*$descri = "{{Information
|Description=Test-Image for my new rotate-bot. Please do '''not delete''' - I request a speedy deletion if I'm finished with testing. Thank you --~~~~
|Source=self made
|Date=17.11.2007
|Author=[[User:Luxo]]
|Permission= Public Domain
|other_versions= -
}}

{{PD-self}}
";

wikiupload("commons.wikimedia.org","test2.jpg","Test for Rotatebot.jpg","",$descri);*/

// ############### EDIT WIKIPEDIA - FUNCTION ###############
function wikiupload($project,$filename_local,$filename_wiki,$license,$desc)
{
global $cookies;
$username = "Rotatebot";
$password = "**removed**";

logfile("Lade Bild '$filename_wiki' hoch am ".date("r",time()).".");

//$cookies
if(!$cookies["commonswikiUserName"] OR !$cookies["commonswikiUserID"])
{
	$username = "Rotatebot";
	$password = "**removed**";
	logfile("Login to $project!\n");
	wikilogin($username,$password,$project,$useragent);
	logfile("logged in to $project!\n");
	print_r($cookies);
}
else
{
	logfile("already logged in to $project for upload\n");
}


if($cookies) {
	logfile("Angemeldet in $project!\n");
} else {
	die("Keine Cookies! Abbruch\n$header\n");
}

//Angemeldet, Cookies formatieren**************

foreach ($cookies as $key=>$value)
{
	$cookie .= trim($value).";";
}
$cookie = substr($cookie,0,-1);

//************ BILD HOCHLADEN ****************
wiki_upload_file ($filename_local,$filename_wiki,$license,$desc,$project,$cookie);

}

/* ###########################################
   ########## FUNKTIONEN #####################
   ########################################### */

function wiki_upload_file ($filename_local,$filename_wiki,$license,$desc,$wiki,$cookies)
{
	$file1 = "";//Löschen wegen Speicherplatz
	$file1 = file_get_contents("/home/luxo/rotbot/cache/".$filename_local) or die("Fehler - Datei nicht gefunden! ($filename_local)");

	$data_l = array("file.file" => $file1,
	"wpDestFile" => $filename_wiki,
	"wpUploadDescription" => str_replace("\\'","'",$desc),
	"wpLicense" => $license,
	"wpIgnoreWarning" => "1",
	"wpUpload" => "Upload file");
	$file1 = "";//Löschen wegen Speicherplatz
	wiki_PostToHostFD($wiki, "/wiki/Special:Upload", $data_l, $wiki, $cookies);

	$data_l = array();//Das auch löschen wegen Speicherplatz

}

function wiki_PostToHostFD ($host, $path, $data_l, $wiki, $cookies) //this function was developed by [[:de:User:APPER]] (Christian Thiele)
{
	logfile("verbinde zu $host ...");
	$useragent = "Luxobot/1.1 (toolserver; php) luxo@ts.wikimedia.org";
	$dc = 0;
	$bo="-----------------------------305242850528394";
	$filename=$data_l['wpDestFile'];
	$fp = fsockopen($host, 80, $errno, $errstr, 30);
	if (!$fp) { echo "$errstr ($errno)<br />\n"; exit; }

	fputs($fp, "POST $path HTTP/1.0\r\n");
	fputs($fp, "Host: $host\r\n");
	fputs($fp, "Accept: image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, image/png, */*\r\n");
	fputs($fp, "Accept-Charset: iso-8859-1,*,utf-8\r\n");
	fputs($fp, "Cookie: ".$cookies."\r\n");
	fputs($fp, "User-Agent: ".$useragent."\r\n");
	fputs($fp, "Content-type: multipart/form-data; boundary=$bo\r\n");

	foreach($data_l as $key=>$val)
	{
		// Hack for attachment
		if ($key == "file.file")
		{
			$ds =sprintf("--%s\r\nContent-Disposition: attachment; name=\"wpUploadFile\"; filename=\"%s\"\r\nContent-type: image/png\r\nContent-Transfer-Encoding: binary\r\n\r\n%s\r\n", $bo, $filename, $val);
		}
		else
		{
			$ds =sprintf("--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n%s\r\n", $bo, $key, $val);
		}
		$dc += strlen($ds);
	}
	$dc += strlen($bo)+3;
	fputs($fp, "Content-length: $dc \n");
	fputs($fp, "\n");

	foreach($data_l as $key=>$val)
	{
		if ($key == "file.file")
		{
			$ds =sprintf("--%s\r\nContent-Disposition: attachment; name=\"wpUploadFile\"; filename=\"%s\"\r\nContent-type: image/png\r\nContent-Transfer-Encoding: binary\r\n\r\n%s\r\n", $bo, $filename, $val);
			$data_1["file.file"] = "";//löschen
		}
		else
		{
			$ds =sprintf("--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n%s\r\n", $bo, $key, $val);
		}
		fputs($fp, $ds );
	}
	$ds = "--".$bo."--\n";
	fputs($fp, $ds);

	$res = "";
	while(!feof($fp))
	{
		$res .= fread($fp, 1);
	}
	fclose($fp);
	file_put_contents("/home/luxo/rotbot/cache/log.txt",$res);
	return $res;
	$data_l = array();
}
