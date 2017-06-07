<?php

setlocale(LC_TIME, "de_DE.utf8");
date_default_timezone_set("Europe/Berlin");

// config
include "config.php";

// functions
include "functions.php";

// read incoming info and grab the chatID
$content = file_get_contents("php://input");
$update = json_decode($content, true);
if (isset($update["message"])) {
	$chatID = $update["message"]["chat"]["id"];
	mysqli_db_connect();
	db_connect();

	// logging($chatID, $update);
	update_lastseen($update["message"]["from"]["first_name"], $update["message"]["from"]["id"]);

	include_prio(1);

	// Text
	if (isset($update["message"]["text"])) {
		$befehle = explode(" ", $update["message"]["text"]);
		$glob_switcher = str_replace($bot_atname, "", strtolower($befehle[0]));
		$hasbeentriggered = false;

		include_prio(2);

		include_prio(3);
	}

	include_prio(4);

}
