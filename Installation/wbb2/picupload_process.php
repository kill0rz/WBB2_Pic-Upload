<?php

//
//

//
// Pic-Upload Script v3.0 by kill0rz
//

//
//

error_reporting(E_ALL);

$filename = "picupload_process.php";
require './picupload_functions.php';

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
		if (isset($fotoalben_board_id) && $fotoalben_board_id > 0) {
			$usenumber = 0;
			$ordner_orig = $ordner;
			$ordner = strtr(strtolower(trim(base64_decode(trim($_GET['folder'])))), $ersetzen);

			$sql = "SELECT threadid, topic FROM bb1_threads WHERE boardid = " . $fotoalben_board_id . ";";
			$result = $db->unbuffered_query($sql);
			while ($row = $db->fetch_array($result)) {
				$name = strtr(strtolower($row['topic']), $ersetzen);
				if ($name == $ordner) {
					$usenumber = $row['threadid'];
					$usetopic = $row['topic'];
				}
			}

			if ($usenumber > 0) {
				//we got a thread
				$response = (object) [
					'action' => 'addreplaytothread',
					'usenumber' => $usenumber,
					'usetopic' => $usetopic,
				];
			} else {
				//newthread
				$response = (object) [
					'boardid' => $fotoalben_board_id,
					'action' => 'submittonewthread',
					'usetopic' => htmlentities($ordner, ENT_NOQUOTES | ENT_HTML401, 'ISO-8859-1'),
				];
			}
		} else {
			$response = (object) [
				'boardid' => 0,
			];
		}
		echo json_encode($response);
	} elseif (isset($_GET['action']) && trim($_GET['action']) == "getboardid") {
		$response = (object) [
			'boardid' => $fotoalben_board_id,
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
		$status = (boolean) move_uploaded_file($_FILES['photo']['tmp_name'], $subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName);
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
			$response->post = $_POST;
			$response->files = $_FILES;
		}
		echo json_encode($response);
	}
}