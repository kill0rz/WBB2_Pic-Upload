<?php

//
//

//
// Pic-Upload Script v3.1 by kill0rz
//

//
//

error_reporting(E_ALL);
if (!(isset($argv[1]) && trim($argv[1]) == "1")) {
	die("nein.");
}

function getgentime() {

}

function is_allowed_type($file) {
	if (
		substr(strtolower($file), -5) == '.jpeg' ||
		substr(strtolower($file), -4) == '.jpg' ||
		substr(strtolower($file), -4) == '.gif' ||
		substr(strtolower($file), -4) == '.bmp' ||
		substr(strtolower($file), -4) == '.png'
	) {
		return true;
	} else {
		return false;
	}
}

$phpversion = phpversion();

require './acp/lib/config.inc.php';
require './acp/lib/class_db_mysql.php';
require './acp/lib/class_parse.php';
require './acp/lib/options.inc.php';
require './picupload_config.php';

$db = new db($sqlhost, $sqluser, $sqlpassword, $sqldb, $phpversion);

// -------

$userordners = $subordner;

if (rand(0, 1) == 1) {
	// simple algorithm
	if (is_dir($userordners)) {
		do {
			$userfolder = scandir($userordners);
			do {
				$userordner = $userfolder[array_rand($userfolder)];
			} while ($userordner == '.' || $userordner == '..' || !is_dir($userordners . "/" . $userordner . "/"));

			$useralbums = scandir($userordners . "/" . $userordner);
			do {
				$useralbum = $useralbums[array_rand($useralbums)];
			} while ($useralbum == '.' || $useralbum == '..' || !is_dir($userordners . "/" . $userordner . "/" . $useralbum));

			$userpics = scandir($userordners . "/" . $userordner . "/" . $useralbum);
			do {
				$userpic = $userpics[array_rand($userpics)];
			} while ($userpic == '.' || $userpic == '..' || is_dir($userordners . "/" . $userordner . "/" . $useralbum . "/" . $userpic) || !is_allowed_type($userpic));

			$imgurl = $url2board . "/" . $userordners . "/" . $userordner . "/" . $useralbum . "/" . $userpic;
			$albumname = $useralbum;
			$userid = $userordner;
		} while (!file_exists("./" . $userordners . "/" . $userordner . "/" . $useralbum . "/allowtorandompic"));

	} else {
		$error .= "Error 3";
	}
} else {
	$useralbums = array();
	//komplex algorithm
	if (is_dir($userordners)) {
		$folder = scandir($userordners);
		foreach ($folder as $userordner) {
			if ($userordner != '.' and $userordner != '..' and is_dir($userordners . "/" . $userordner . "/")) {
				$folder2 = scandir($userordners . "/" . $userordner . "/");
				foreach ($folder2 as $album) {
					if ($album != '.' and $album != '..' and is_dir($userordners . "/" . $userordner . "/" . $album)) {
						if (file_exists($userordners . "/" . $userordner . "/" . $album . "/allowtorandompic")) {
							$folder3 = scandir($userordners . "/" . $userordner . "/" . $album . "/");
							foreach ($folder3 as $picture) {
								if (is_file($userordners . "/" . $userordner . "/" . $album . "/" . $picture) && substr(strtolower($picture), -5) == '.jpeg' || substr(strtolower($picture), -4) == '.jpg' || substr(strtolower($picture), -4) == '.gif' || substr(strtolower($picture), -4) == '.bmp' || substr(strtolower($picture), -4) == '.png') {
									$nextnumber = count($useralbums);
									$useralbums[$nextnumber]['userid'] = $userordner;
									$useralbums[$nextnumber]['name'] = $album;
									$useralbums[$nextnumber]['url'] = $url2board . "/" . $userordners . "/" . $userordner . "/" . $album . "/" . $picture;
								}
							}
						}
					}
				}
			}
		}
		$usenumber = rand(1, count($useralbums)) - 1;
		$imgurl = $useralbums[$usenumber]['url'];
		$albumname = $useralbums[$usenumber]['name'];
		$userid = $useralbums[$usenumber]['userid'];
	} else {
		$error .= "Error 3";
	}
}

if (!count($useralbums) > 0) {
	die("Keine Alben zum indexieren!");
}

$result = $db->unbuffered_query("SELECT username FROM bb" . $n . "_users WHERE userid='{$userid}'");
while ($row = $db->fetch_array($result)) {
	$nutzername = $row['username'];
}

$imgurl_encoded = str_replace("https%3A%2F%2F", "https://", urlencode($imgurl));
$message = "Das Zufallsbild der Woche kommt heute von [b]{$nutzername}[/b] aus dem Album [b]{$albumname}[/b]: [url={$imgurl_encoded}]{$imgurl}[/url]";
$result = $db->query("INSERT INTO bb" . $n . "_xy_shoutbox SET `name`='Random-Bot',`comment`='" . addslashes($message) . "',`date`='" . time() . "'");
die();