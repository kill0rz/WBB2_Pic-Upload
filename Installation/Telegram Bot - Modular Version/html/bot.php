<?php

setlocale(LC_TIME, "de_DE.utf8");
date_default_timezone_set("Europe/Berlin");

// config
include "config.php";

// functions
include "functions.php";

function call_stats_help() {
	$text = "Bitte gib an, welche Statistiken du sehen willst:\n";
	$text .= "/stats allwords --> Zeigt Statistiken zu allen Worten\n";
	$text .= "/stats word {Wort} --> Zeigt Statistiken zu einem Wort\n";
	$text .= "/stats common --> Zeigt allgemeine Statistiken\n";
	$text .= "/stats me --> Zeigt Statistiken zu dir\n";
	post_reply($text);
}

function call_post_help() {
	$text = "Bitte gib an, was genau du machen willst:\n";
	$text .= "/nextpic --> bearbeite das nächste Bild\n";
	$text .= "/delpic --> lösche aktuelles Bild\n";
	$text .= "/rotagepicright --> Bild rechtsherum drehen\n";
	$text .= "/rotagepicleft --> Bild linksherum drehen\n";
	$text .= "/postall --> Schreibe alle Bilder ins Forum\n";
	$text .= "/help --> Allgemeine Hilfe\n";
	post_reply($text);
}

function call_help() {
	$text = "/help --> Dieses Menü\n";
	$text .= "/lastseen {NUTZERNAME} --> Zeigt dir an, wann der Nutzer das letzte mal online war.\n";
	$text .= "/stats --> Zeigt Statistiken\n";
	$text .= "/nextpic --> Bearbeite Bilder fürs Forum\n";
	$text .= "/hitlerwitz --> Tells the infamous Hitlerwitz!\n";
	post_reply($text);
}

function include_prio($prio) {
	foreach (glob("bot_modules/prio{$prio}/*.php") as $filename) {
		include $filename;
	}
}

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
