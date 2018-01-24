<?php

//
//

//
// Pic-Upload Script v3.0 by kill0rz
// Geburtstagsintegration in Telegram
//

//
//

error_reporting(E_ALL);

function getgentime() {

}

function get_next_birthday($birthday) {
	$date = new DateTime($birthday);
	$date->modify('+' . date('Y') - $date->format('Y') . ' years');
	if ($date < new DateTime()) {
		$date->modify('+1 year');
	}

	return $date->format('Y-m-d');
}

$phpversion = phpversion();

require './acp/lib/config.inc.php';
require './acp/lib/class_db_mysql.php';
require './acp/lib/class_parse.php';
require './acp/lib/options.inc.php';
require './picupload_config.php';

$db = new db($sqlhost, $sqluser, $sqlpassword, $sqldb, $phpversion);

// -------

// current date
$today_dt = new DateTime(date("Y-m-d"));
// Post to Telegram
include "../telegram_bot/config.php";
include "../telegram_bot/functions.php";

$result = $db->unbuffered_query("SELECT username,birthday FROM bb" . $n . "_users WHERE birthday<>'0000-00-00'");
while ($row = $db->fetch_array($result)) {
	// Ist in 3 Tagen Geburtstag?
	$birthday_dt = new DateTime(get_next_birthday($row['birthday']));
	if ($birthday_dt->diff($today_dt)->format("%a") == 3) {
		$message = "In 3 Tagen hat {$row['username']} Geburtstag!";
		$chatID = $birthday_chatID;
		post_reply($message);
	}

	// Ist heute Geburtstag?
	$birthday_dt = new DateTime(get_next_birthday($row['birthday']));
	if ($birthday_dt == $today_dt) {
		$message = "Wir gratulieren {$row['username']} zum Geburtstag! =)";
		$chatID = $birthday_chatID;
		post_reply($message);
	}

	$nutzername = $row['username'];
}

die();