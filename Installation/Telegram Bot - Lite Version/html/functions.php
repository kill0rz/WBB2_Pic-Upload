<?php

function logging($chatID, $update) {
	$myFile = "log.txt";
	$updateArray = print_r($update, TRUE);
	$fh = fopen($myFile, 'a') or die("can't open file");
	fwrite($fh, $chatID . "\n\n");
	fwrite($fh, $updateArray . "\n\n");
	fclose($fh);
}

function post_reply($reply) {
	global $chatID;
	$sendto = API_URL . "sendmessage?chat_id=" . $chatID . "&text=" . urlencode($reply);
	file_get_contents($sendto);
}

function send_photo($fileid) {
	global $chatID;
	$sendto = API_URL . "sendphoto?chat_id=" . $chatID . "&photo=" . urlencode($fileid);
	file_get_contents($sendto);
}

function resizeImage($filepath_old, $filepath_new, $image_dimension, $scale_mode = 0, $overwrite = 0) {
	if ($overwrite == 1) {
		if (!(file_exists($filepath_old))) {
			return false;
		}

	} else {
		if (!(file_exists($filepath_old)) || file_exists($filepath_new)) {
			return false;
		}

	}

	$image_attributes = getimagesize($filepath_old);
	$image_width_old = $image_attributes[0];
	$image_height_old = $image_attributes[1];
	$image_filetype = $image_attributes[2];

	if ($image_width_old <= $image_dimension || !(isset($_POST['compress']) && $_POST['compress'] == "true")) {
		if (copy($filepath_old, $filepath_new)) {
			return true;
		} else {
			return false;
		}
	}

	if ($image_width_old <= 0 || $image_height_old <= 0) {
		return false;
	}

	$image_aspectratio = $image_width_old / $image_height_old;

	if ($scale_mode == 0) {
		$scale_mode = ($image_aspectratio > 1 ? -1 : -2);
	} elseif ($scale_mode == 1) {
		$scale_mode = ($image_aspectratio > 1 ? -2 : -1);
	}

	if ($scale_mode == -1) {
		$image_width_new = $image_dimension;
		$image_height_new = round($image_dimension / $image_aspectratio);
	} elseif ($scale_mode == -2) {
		$image_height_new = $image_dimension;
		$image_width_new = round($image_dimension * $image_aspectratio);
	} else {
		return false;
	}

	switch ($image_filetype) {
		case 1:
			$image_old = imagecreatefromgif($filepath_old);
			$image_new = imagecreate($image_width_new, $image_height_new);
			imagecopyresampled($image_new, $image_old, 0, 0, 0, 0, $image_width_new, $image_height_new, $image_width_old, $image_height_old);
			imagegif($image_new, $filepath_new);
			break;

		case 2:
			$image_old = @imagecreatefromjpeg($filepath_old);
			$image_new = imagecreatetruecolor($image_width_new, $image_height_new);
			imagecopyresampled($image_new, $image_old, 0, 0, 0, 0, $image_width_new, $image_height_new, $image_width_old, $image_height_old);
			imagejpeg($image_new, $filepath_new);
			break;

		case 3:
			$image_old = imagecreatefrompng($filepath_old);
			$image_colordepth = imagecolorstotal($image_old);

			if ($image_colordepth == 0 || $image_colordepth > 255) {
				$image_new = imagecreatetruecolor($image_width_new, $image_height_new);
			} else {
				$image_new = imagecreate($image_width_new, $image_height_new);
			}

			imagealphablending($image_new, false);
			imagecopyresampled($image_new, $image_old, 0, 0, 0, 0, $image_width_new, $image_height_new, $image_width_old, $image_height_old);
			imagesavealpha($image_new, true);
			imagepng($image_new, $filepath_new);
			break;

		default:
			return false;
	}

	imagedestroy($image_old);
	imagedestroy($image_new);
	return true;
}

function parse_dateformats($ordnername) {
	$mode = 0;
	preg_match_all("/[0-9]{2}\.[0-9]{2}\.[0-9]{4}/", $ordnername, $matches);
	if (count($matches[0])) {
		$mode = 1;
	} else {
		preg_match_all("/[0-9]{2}\.[0-9]{2}\.[0-9]{2}/", $ordnername, $matches);
		if (count($matches[0])) {
			$mode = 2;
		} else {
			preg_match_all("/[0-9]{4}-[0-9]{2}-[0-9]{2}/", $ordnername, $matches);
			if (count($matches[0])) {
				$mode = 3;
			}
		}
	}

	if ($mode > 0) {
		switch ($mode) {
			case 1:
				$teile = explode(".", $matches[0][0]);
				$year = $teile[2];
				$month = $teile[1];
				$day = $teile[0];
				if (preg_replace("/[0-9]{2}\.[0-9]{2}\.[0-9]{4}/", "", $ordnername) == "") {
					return $ordnername;
				} else {
					if (substr(preg_replace("/[0-9]{2}\.[0-9]{2}\.[0-9]{4}/", "", $ordnername), 0, 1) == "_") {
						return substr(preg_replace("/[0-9]{2}\.[0-9]{2}\.[0-9]{4}/", "", $ordnername), 1) . "_" . $day . "." . $month . "." . $year;
					} else {
						return substr(preg_replace("/[0-9]{2}\.[0-9]{2}\.[0-9]{4}/", "", $ordnername), 0, -1) . "_" . $day . "." . $month . "." . $year;
					}
				}
			case 2:
				$teile = explode(".", $matches[0][0]);
				$year = "20" . $teile[2];
				$month = $teile[1];
				$day = $teile[0];
				if (preg_replace("/[0-9]{2}\.[0-9]{2}\.[0-9]{2}/", "", $ordnername) == "") {
					return $day . "." . $month . "." . $year;
				} else {
					if (substr(preg_replace("/[0-9]{2}\.[0-9]{2}\.[0-9]{2}/", "", $ordnername), 0, 1) == "_") {
						return substr(preg_replace("/[0-9]{2}\.[0-9]{2}\.[0-9]{2}/", "", $ordnername), 1) . "_" . $day . "." . $month . "." . $year;
					} else {
						return substr(preg_replace("/[0-9]{2}\.[0-9]{2}\.[0-9]{2}/", "", $ordnername), 0, -1) . "_" . $day . "." . $month . "." . $year;
					}
				}
			case 3:
				$teile = explode("-", $matches[0][0]);
				$year = $teile[0];
				$month = $teile[1];
				$day = $teile[2];
				if (preg_replace("/[0-9]{4}-[0-9]{2}-[0-9]{2}/", "", $ordnername) == "") {
					return $day . "." . $month . "." . $year;
				} else {
					return substr(preg_replace("/[0-9]{4}-[0-9]{2}-[0-9]{2}/", "", $ordnername), 1) . "_" . $day . "." . $month . "." . $year;
				}

		}
	}
	return $ordnername;
}

function get_thread($ordner) {
	// todo neue DB-Verbidnung aufbauen
	global $db, $bot_boardid, $usenumber, $usetopic, $ersetzen;

	$sql = "SELECT threadid, topic FROM bb1_threads WHERE boardid = " . $bot_boardid . ";";
	$result = $db->query($sql);
	$ordner = trim(strtr(strtolower(utf8_encode($ordner)), $ersetzen));
	while ($row = $result->fetch_array()) {
		$name = trim(strtr(strtolower(utf8_encode($row['topic'])), $ersetzen));
		if (parse_dateformats($name) == parse_dateformats($ordner)) {
			$usenumber = $row['threadid'];
			$usetopic = htmlentities($row['topic'], ENT_NOQUOTES | ENT_HTML401, 'ISO-8859-1');
			break;
		}
	}

	if ($usenumber == 0) {
		$usetopic = htmlentities($name, ENT_NOQUOTES | ENT_HTML401, 'ISO-8859-1');
	}
}

function makeindex($pfad) {
	$datei = fopen($pfad . "index.php", "w");
	fwrite($datei, "");
	fclose($datei);
}

function query_first($db, $query_string) {
	$result = $db->query($query_string);
	$returnarray = $result->fetch_array();
	return $returnarray;
}

$ersetzen = array('ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'ß' => 'ss', ' ' => '_', '\\' => '-', '/' => '-', "http://" => "", "http" => "", "//" => "", ":" => "", ";" => "", "[" => "", "]" => "", "{" => "", "}" => "", "%" => "", "$" => "", "?" => "", "!" => "", "=" => "", "'" => "_", "(" => "_", ")" => "_");
