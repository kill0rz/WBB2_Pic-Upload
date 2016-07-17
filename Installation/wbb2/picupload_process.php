<?php

//
//

//
// Pic-Upload Script v3.0 by kill0rz
//

//
//

$filename = "picupload_process.php";
require './picupload_functions.php';

$ersetzen = array('ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'ß' => 'ss', ' ' => '_', '\\' => '-', '/' => '-', "http://" => "", "http" => "", "//" => "", ":" => "", ";" => "", "[" => "", "]" => "", "{" => "", "}" => "", "%" => "", "$" => "", "?" => "", "!" => "", "=" => "", "'" => "_", "(" => "_", ")" => "_");

$albenurl = $url2board . "/" . $subordner . "/";
$error = "";
$filename = "picupload_process.php";

$loggedin = false;
if ($wbbuserdata['userid'] != "0" && inarray($erlaubtegruppen, $wbbuserdata['groupids'])) {
	$loggedin = true;
}

if ($loggedin) {
	if (isset($_GET['action']) && trim($_GET['action']) == "autopost" && isset($_GET['folder']) && trim($_GET['folder']) != "") {
		//autopost
		$ordner_orig = trim(base64_decode(trim($_GET['folder'])));
		$ordner = strtr(strtolower(trim(base64_decode(trim($_GET['folder'])))), $ersetzen);
		$ordner_utf8 = strtr(strtolower(utf8_encode(trim(base64_decode(trim($_GET['folder']))))), $ersetzen);

		if (isset($fotoalben_board_id) && $fotoalben_board_id > 0) {
			$usenumber = 0;
			get_thread();

			if ($usenumber > 0) {
				//we got a thread
				$response = (object) [
					'action' => 'addreplaytothread',
					'boardid' => $fotoalben_board_id,
					'usenumber' => $usenumber,
					'usetopic' => $usetopic,
					'ordner_shrink' => $ordner_utf8,
				];
			} else {
				//newthread
				$response = (object) [
					'action' => 'submittonewthread',
					'boardid' => $fotoalben_board_id,
					'usetopic' => htmlentities($ordner_orig, ENT_NOQUOTES | ENT_HTML401, 'ISO-8859-1'),
					'ordner_shrink' => htmlentities($ordner_utf8, ENT_NOQUOTES | ENT_HTML401, 'ISO-8859-1'),
				];
			}
		} else {
			$response = (object) [
				'boardid' => 0,
				'ordner_shrink' => $ordner,
			];
		}
		echo json_encode($response);
	} elseif (isset($_GET['action']) && trim($_GET['action']) == "setallowrandompic" && isset($_GET['folder']) && trim($_GET['folder']) != "") {
		$ordner_to_allow = strtr(strtolower(utf8_encode(trim(base64_decode(trim($_GET['folder']))))), $ersetzen);
		if (is_dir($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner_to_allow)) {
			if (file_exists($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner_to_allow . "/allowtorandompic")) {
				$status = 1;
			} else {
				try {
					file_put_contents($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner_to_allow . "/allowtorandompic", "");
					chmod($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner_to_allow, 0755);
					$status = 1;
				} catch (Exception $e) {
					$status = 0;
				}
			}
		} else {
			$status = 0;
		}
		$response = (object) [
			'boardid' => $fotoalben_board_id,
			'status' => $status,
		];
		echo json_encode($response);
	} elseif (isset($_GET['action']) && trim($_GET['action']) == "checkexistingfolder" && isset($_GET['folder']) && trim($_GET['folder']) != "") {
		$ordner_to_check = strtr(strtolower(utf8_encode(trim(base64_decode(trim($_GET['folder']))))), $ersetzen);
		if (is_dir($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner_to_check)) {
			$status = true;
		} else {
			$status = false;
		}
		$response = (object) [
			'status' => $status,
		];
		echo json_encode($response);
	} else {
		$ordner = strtr(strtolower(trim($_POST['ordner'])), $ersetzen);

		if (!is_dir($subordner)) {
			mkdir($subordner, 0777);
		}
		if (!is_dir($subordner . "/" . $wbbuserdata['userid'])) {
			mkdir($subordner . "/" . $wbbuserdata['userid'], 0777);
		}
		if (!is_dir($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner)) {
			mkdir($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner, 0777);
		}
		makeindex($subordner . "/");
		makeindex($subordner . "/" . $wbbuserdata['userid'] . "/");
		makeindex($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/");
		$umaskold = umask(0);
		$DateiName = strtr(strtolower($_POST['filename']), $ersetzen);
		$DateiName_merk = $DateiName;
		while (file_exists($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName)) {
			sleep(1);

			$parts = explode('.', $DateiName_merk);
			$endung = array_pop($parts);
			$name = join('.', $parts);

			$DateiName = $name . "_" . time() . "." . $endung;
		}
		$status = (boolean)move_uploaded_file($_FILES['photo']['tmp_name'], $subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName);
		if ($status) {
			$links = "[IMG]" . $albenurl . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName . "[/IMG]\n";
			@chmod($DateiName, 0755);
		} else {
			$error .= "Error 1<br>";
		}
		@umask($umaskold);

		$response = (object) [
			'status' => $status,
		];
		if ($status) {
			$response->links = $links;
		}
		echo json_encode($response);
	}
}