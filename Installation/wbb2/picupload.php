<?php

//
//

//
// Pic-Upload Script v3.0 by kill0rz
//

//
//

include './picupload_functions.php';

$ersetzen = array('ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'ß' => 'ss', ' ' => '_', '\\' => '-', '/' => '-', "http://" => "", "http" => "", "//" => "", ":" => "", ";" => "", "[" => "", "]" => "", "{" => "", "}" => "", "%" => "", "$" => "", "?" => "", "!" => "", "=" => "", "'" => "_", "(" => "_", ")" => "_");

$albenurl = $url2board . "/" . $subordner . "/";
$error = "";
$filename = "picupload.php";

$loggedin = false;
if ($wbbuserdata['userid'] != "0" && inarray($erlaubtegruppen, $wbbuserdata['groupids'])) {
	$loggedin = true;
}

if ($loggedin) {

	if (isset($_GET['formular']) && trim($_GET['formular']) == "old") {
		$done = false;
		$dowithbutton = '';
		if (isset($_POST['newdir']) && trim($_POST['newdir']) != '') {
			$ordner = trim($_POST['newdir']);
		} elseif (isset($_POST['ordner']) && trim($_POST['ordner']) != '') {
			$ordner = trim($_POST['ordner']);
		} elseif (isset($_GET['title']) && trim($_GET['title']) != '') {
			$ordner = trim(base64_decode(urldecode(trim($_GET['title']))));
		} else {
			$ordner = '';
		}

		if (trim($ordner) == "") {
			$ordner = "default";
		}

		$ordner_orig = $ordner;
		if ($ordner != "default") {
			$ordner_anz = $ordner;
		} else {
			$ordner_anz = '';
		}

		$ordner = strtr(strtolower(trim($ordner)), $ersetzen);

		if (isset($_POST['sent']) && $_POST['sent'] == 1) {
			if (trim($_POST['links']) != '') {
				$links = trim($_POST['links']) . "\n";
			} else {
				$links = '';
			}

			//Local-Upload Felder
			for ($feld = 1; $feld < 6; $feld++) {
				$lowername = strtolower($_FILES["file" . $feld]['name']);
				if (isset($_FILES["file" . $feld]) && $_FILES["file" . $feld]['size'] > 0 && substr($lowername, -5) == '.jpeg' || substr($lowername, -4) == '.jpg' || substr($lowername, -4) == '.gif' || substr($lowername, -4) == '.bmp' || substr($lowername, -4) == '.png') {
					if (!is_dir($subordner)) {
						mkdir($subordner, 0777);
					}
					if (!is_dir($subordner . "/" . $wbbuserdata['userid'])) {
						mkdir($subordner . "/" . $wbbuserdata['userid'], 0777);
					}
					if (!is_dir($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner)) {
						mkdir($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner, 0777);
					}
					makeindex($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/");
					makeindex($subordner . "/" . $wbbuserdata['userid'] . "/");
					makeindex($subordner . "/");
					$umaskold = umask(0);
					$DateiName = strtr($lowername, $ersetzen);
					if (file_exists($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName)) {
						sleep(1);
						$DateiName = time() . $DateiName;
					}
					if (resizeImage($_FILES["file" . $feld]['tmp_name'], $subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName, 1300, 1, 1)) {
						$links .= "[IMG]" . $albenurl . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName . "[/IMG]\n";
						@chmod($DateiName, 0755);
					} else {
						$error .= "Error 1<br>";
					}
					@umask($umaskold);
					$done = true;
				}
			}

			//URL-Felder
			for ($feld = 1; $feld < 6; $feld++) {
				if (isset($_POST["url" . $feld]) && substr(strtolower($_POST["url" . $feld]), -5) == '.jpeg' || substr(strtolower($_POST["url" . $feld]), -4) == '.jpg' || substr(strtolower($_POST["url" . $feld]), -4) == '.gif' || substr(strtolower($_POST["url" . $feld]), -4) == '.bmp' || substr(strtolower($_POST["url" . $feld]), -4) == '.png') {
					$stripped = trim($_POST["url" . $feld]);
					$stripped = strip_tags($stripped);
					for ($j = 1; $j < count($wegarray); $j++) {
						$wegarray[$j] = trim($wegarray[$j]);
					}
					$stripped = str_replace($wegarray, "", $stripped);
					$checker = 'http://';
					$checker_len = strlen($checker);
					$short_string = substr($stripped, 0, $checker_len);
					if ($short_string != $checker) {
						$stripped = "http://" . $stripped;
					}

					if (!is_dir($subordner . "/" . $wbbuserdata['userid'])) {
						mkdir($subordner . "/" . $wbbuserdata['userid'], 0777);
					}
					if (!is_dir($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner)) {
						mkdir($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner, 0777);
					}
					makeindex($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/");
					makeindex($subordner . "/" . $wbbuserdata['userid'] . "/");
					$umaskold = umask(0);
					$stranfang = strripos($stripped, "/");
					$DateiName = strtr(strtolower(substr($stripped, $stranfang + 1)), $ersetzen);
					if (file_exists($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName)) {
						sleep(1);
						$DateiName = time() . $DateiName;
					}
					if (@copy($stripped, $subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName)) {
						resizeImage($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName, $subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName, 1300, 1, 1);
						$links .= "[IMG]" . $albenurl . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName . "[/IMG]\n";
						@chmod($DateiName, 0755);
					} else {
						$error .= "Error 2<br>";
					}
					@umask($umaskold);
					$done = true;
				}
			}

			//ZIP-Datei
			if (isset($_FILES['filezip']) && substr(strtolower($_FILES['filezip']['name']), -4) == '.zip') {
				for ($j = 1; $j < count($wegarray); $j++) {
					$wegarray[$j] = trim($wegarray[$j]);
				}
				$zip = new ZipArchive;
				move_uploaded_file($_FILES['filezip']['tmp_name'], "/tmp/" . $_FILES['filezip']['name']);
				if ($zip->open("/tmp/" . $_FILES['filezip']['name']) === TRUE) {
					$zip->extractTo('/tmp/picupload/');
					$zip->close();
					if ($handle = opendir('/tmp/picupload')) {
						while (false !== ($file = readdir($handle))) {
							if ($file != "." && $file != "..") {
								if (substr(strtolower($file), -5) == '.jpeg' || substr(strtolower($file), -4) == '.jpg' || substr(strtolower($file), -4) == '.gif' || substr(strtolower($file), -4) == '.bmp' || substr(strtolower($file), -4) == '.png') {
									if (!is_dir($subordner . "/" . $wbbuserdata['userid'])) {
										mkdir($subordner . "/" . $wbbuserdata['userid'], 0777);
									}
									if (!is_dir($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner)) {
										mkdir($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner, 0777);
									}
									makeindex($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/");
									makeindex($subordner . "/" . $wbbuserdata['userid'] . "/");
									$umaskold = umask(0);
									$DateiName = str_replace($wegarray, "", $file);
									$DateiName = strtr(strtolower($DateiName), $ersetzen);
									while (file_exists($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName)) {
										sleep(1);
										$DateiName = time() . $DateiName;
									}

									if (@copy('/tmp/picupload/' . $file, $subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName)) {
										@chmod($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName, 0777);
										$links .= "[IMG]" . $albenurl . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName . "[/IMG]\n";
										resizeImage($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName, $subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner . "/" . $DateiName, 1300, 1, 1);
									} else {
										$error .= "Error 2<br>";
									}
									@umask($umaskold);
									$done = true;
									unlink('/tmp/picupload/' . $file);
								}
							}
						}
						closedir($handle);
					}
				} else {
					echo 'Es gab einen Fehler in der Verarbeitung.';
				}
				unlink("/tmp/" . $_FILES['filezip']['name']);
			}

			//linkliste sortieren
			if (isset($_POST['sort']) && trim($_POST['sort']) == "true") {
				$linksarray = explode("\n", $links);
				natsort($linksarray);
				$links = implode($linksarray, "\n");
			}

			//allowrandompic
			if (isset($_POST['sort']) && trim($_POST['sort']) == "true") {
				$ordner_to_allow = $ordner . "/";
				if (is_dir($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner_to_allow)) {
					try {
						file_put_contents($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner_to_allow . "/allowtorandompic", "");
						chmod($subordner . "/" . $wbbuserdata['userid'] . "/" . $ordner_to_allow, 0755);
					} catch (Exception $e) {
						$error .= "Fehler Freigabe!\n";
					}
				}
			}

			if ($links != "" && isset($fotoalben_board_id) && $fotoalben_board_id > 0) {
				$usenumber = 0;

				$ausgabe = "<textarea rows=10 cols=150>" . $links . "</textarea>";

				get_thread();

				if ($usenumber > 0) {
					//we got a thread
					$dowithbutton = "<form action='./addreply.php' method='post'>";
					$dowithbutton .= "<input type='hidden' name='threadid' value='{$usenumber}' />";
					$dowithbutton .= "<input type='hidden' name='inhalt' value='" . base64_encode($links) . "' />";
					$dowithbutton .= "<input type='hidden' name='autosubmit' value='true' />";
					$dowithbutton .= "<input type='submit' id='addreplaytothread' value=\"Antworte auf Thread '{$usetopic}'\" />";
					$dowithbutton .= "</form>";
				} else {
					//newthread
					$dowithbutton = "<form action='./newthread.php?boardid=" . $fotoalben_board_id . "' method='post'>";
					$dowithbutton .= "<input type='hidden' name='inhalt' value='" . base64_encode($links) . "' />";

					$dowithbutton .= "<input type='hidden' name='title' value='" . base64_encode($ordner_orig) . "' />";
					$dowithbutton .= "<input type='hidden' name='autosubmit' value='true' />";
					$dowithbutton .= "<input type='submit' id='submittonewthread' value=\"Er&ouml;ffne neuen Thread '" . htmlentities($ordner_orig, ENT_NOQUOTES | ENT_HTML401, 'ISO-8859-1') . "'\" />";
					$dowithbutton .= "</form>";
				}
			}
			if (!$done) {
				$error .= "Falsches Dateiformat oder kein gültiger Ordner!<br>";
			}
		}

		$ausgabelinks = $links;
		$verzeichnishandle = $subordner . "/" . $wbbuserdata['userid'];
		$options = "<option>default</option>";
		if (is_dir($verzeichnishandle)) {
			$inhalt = scandir($verzeichnishandle);
			foreach ($inhalt as $verzeichnis) {
				if ($verzeichnis != '.' && $verzeichnis != '..' && $verzeichnis != 'index.php' && $verzeichnis != 'default' && is_dir($verzeichnishandle . "/" . $verzeichnis)) {
					if (isset($ordner) && trim($ordner) == $verzeichnis) {
						$selected = " selected";
						$ordner_anz = '';
					} else {
						$selected = "";
					}
					$options .= "<option{$selected}>" . $verzeichnis . "</option>";
				}
			}
		}

		generate_folderoverview("old");
		generate_stats("old");

		$ordner_anz = htmlentities($ordner_anz);
		eval("\$tpl->output(\"" . $tpl->get("picupload_old") . "\");");
	} else {
		//neues Uploadformular

		if (isset($_GET['title']) && trim($_GET['title']) != '') {
			$ordner = trim(base64_decode(urldecode(trim($_GET['title']))));
		} else {
			$ordner = "default";
		}

		if ($ordner != "default") {
			$ordner_anz = $ordner;
		} else {
			$ordner_anz = '';
		}

		$ordner = strtr(strtolower(trim($ordner)), $ersetzen);

		$verzeichnishandle = $subordner . "/" . $wbbuserdata['userid'];
		$options = "<option>default</option>";
		if (is_dir($verzeichnishandle)) {
			$inhalt = scandir($verzeichnishandle);
			foreach ($inhalt as $verzeichnis) {
				if ($verzeichnis != '.' && $verzeichnis != '..' && $verzeichnis != 'index.php' && $verzeichnis != 'default' && is_dir($verzeichnishandle . "/" . $verzeichnis)) {
					if (isset($ordner) && trim($ordner) == $verzeichnis) {
						$selected = " selected";
						$ordner_anz = '';
					} else {
						$selected = "";
					}
					$options .= "<option{$selected}>" . $verzeichnis . "</option>";
				}
			}
		}

		generate_folderoverview();
		generate_stats();
		eval("\$tpl->output(\"" . $tpl->get("picupload") . "\");");
	}
} else {
	echo "<meta http-equiv='refresh' content='0,index.php' />";
}
