<?php

//
//

//
// Pic-Upload Script v3.1 by kill0rz
//

//
//

require './picupload_config.php';
require './global.php';
require './acp/lib/config.inc.php';
require './acp/lib/class_parse.php';
require './acp/lib/options.inc.php';

$ersetzen = array('ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'ß' => 'ss', ' ' => '_', '\\' => '-', '/' => '-', "http://" => "", "http" => "", "//" => "", ":" => "", ";" => "", "[" => "", "]" => "", "{" => "", "}" => "", "%" => "", "$" => "", "?" => "", "!" => "", "=" => "", "'" => "_", "(" => "_", ")" => "_");
$wegarray = array("<", ">", "%3E", "alert(", "http://", "ftp://", "sftp://", "https://", "http%3A%2F%2", "https%3A%2F%2", "ftp%3A%2F%2", "sftp%3A%2F%2", "String.fromCharCode", "(", ")", "'", '"', ";", "<?php", "<?", "?>");

function inarray($array1, $array2) {
	foreach ($array1 as $a1) {
		foreach ($array2 as $a2) {
			if ($a1 == $a2) {
				return true;
			}
		}
	}
	return false;
}

function makeindex($pfad) {
	$datei = fopen($pfad . "index.php", "w");
	fwrite($datei, "");
	fclose($datei);
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

function generate_folderoverview($formular = "") {
	global $_GET, $verzeichnishandle, $folders_hinweis, $vorschauen, $unterelinks, $folders, $url2board, $use_randompic;

	if (isset($_GET['action']) && $_GET['action'] == 'togglefreigabe') {
		$get_folder = trim($_GET['folder']);
		if (is_dir($verzeichnishandle . "/" . $get_folder)) {
			$folder = scandir($verzeichnishandle . "/" . $get_folder);
			$delete = false;
			foreach ($folder as $f) {
				if ($f == 'allowtorandompic') {
					$delete = true;
				}

			}
			if ($delete) {
				unlink($verzeichnishandle . "/" . $get_folder . "/allowtorandompic");
			} else {
				file_put_contents($verzeichnishandle . "/" . $get_folder . "/allowtorandompic", "");
				chmod($verzeichnishandle . "/" . $get_folder . "/allowtorandompic", 0755);
			}
		} else {
			$error .= "Error 7";
		}
	}

	$folders = '';
	if (is_dir($verzeichnishandle)) {
		$folder = scandir($verzeichnishandle);
		if (count($folder) < 1) {
			$folders = "Noch keine vorhanden!";
		} else {
			$folders = "<table border='none' id='ordneruebersicht'> <tr> <th>Ordner</th> <if($use_randompic)> <then> <th>zum Randompic der Woche freigegeben?</th> </then> </if> </tr>";
			foreach ($folder as $f) {
				if ($f != '.' && $f != '..' && $f != 'index.php' && is_dir($verzeichnishandle . "/" . $f)) {
					$folders .= "<tr><td><a href='?folder=" . $f . "&formular={$formular}#inhalt'>" . $f . "</a></td>";
					if ($use_randompic) {
						if (file_exists($verzeichnishandle . "/" . $f . "/allowtorandompic")) {
							//ist freigegeben?
							$folders .= "<td align='center'><a href='picupload.php?folder={$f}&action=togglefreigabe&formular={$formular}'><img src='./images/erledigt.gif' alt='erledigt' /></a></td>";
						} else {
							$folders .= "<td align='center'><a href='picupload.php?folder={$f}&action=togglefreigabe&formular={$formular}'><img src='./images/delete.png' /></a></td>";
						}
					}
					$folders .= "</tr>";
				}
			}
			$folders .= "</table>";
			if ($use_randompic) {
				$folders_hinweis = "<img src='./images/erledigt.gif' alt='erledigt' /> = Album ist freigegeben, <img src='./images/delete.png' alt='delete' /> = nicht freigegeben";
			}
		}
	} else {
		$folders = "Noch keine vorhanden!";
	}
	$links = '';
	$vorschauen = '';
	if (isset($_GET['folder']) && $_GET['folder'] != '' && !isset($_GET['action'])) {
		$getfolder = trim($_GET['folder']);
		if (is_dir($verzeichnishandle . "/" . $getfolder)) {
			$folder = scandir($verzeichnishandle . "/" . $getfolder);
			foreach ($folder as $f) {
				if ($f != '.' && $f != '..' && $f != 'index.php' && $f != "allowtorandompic") {
					$links .= "[IMG]" . $url2board . "/" . $verzeichnishandle . "/" . $getfolder . "/" . $f . "[/IMG]\n";
					$vorschauen .= "<a href='" . $url2board . "/" . $verzeichnishandle . "/" . $getfolder . "/" . $f . "' target='_blank'><img src='" . $url2board . "/" . $verzeichnishandle . "/" . $getfolder . "/" . $f . "' alt='{$f}' width='150px' /></a>\n";
				}
			}
			$linksarray = explode("\n", $links);
			natsort($linksarray);
			$links = implode($linksarray, "\n");
			$unterelinks = "<textarea rows=10 cols=150>" . $links . "</textarea>";
		} else {
			$error .= "Error 3";
		}
	}
}

function generate_stats() {
	global $subordner, $db, $statsinhalt, $error, $use_randompic;
	$usersdata = array();
	if (is_dir("./" . $subordner . "/")) {
		$folder = scandir("./" . $subordner . "/");
		foreach ($folder as $f) {
			if (is_dir("./" . $subordner . "/" . $f) && $f != '.' && $f != '..') {
				//username
				$result = $db->query("SELECT username FROM bb1_users WHERE userid={$f} LIMIT 1");
				while ($row = $db->fetch_array($result)) {
					$usersdata[$f]['name'] = $row['username'];
					$usersdata[$f]['pictures'] = 0;
					$usersdata[$f]['pictures_allowed'] = 0;
				}

				//last upload date
				$latestUploadDate = trim(shell_exec("cd ./" . $subordner . "/" . $f . "/ && ls -tl . | sed -n 2p |  awk '{print $6 $7 $8}'"));
				$usersdata[$f]['lastupload'] = date_create_from_format((strlen($latestUploadDate) == 9 ? "MjY" : "MjH:i"), $latestUploadDate);

				$album = scandir("./" . $subordner . "/" . $f . "/");
				foreach ($album as $a) {
					if (is_dir("./" . $subordner . "/" . $f . "/" . $a . "/") && $f != '.' && $f != '..') {
						$picture = scandir("./" . $subordner . "/" . $f . "/" . $a . "/");
						foreach ($picture as $p) {
							$file = "./" . $subordner . "/" . $f . "/" . $a . "/" . $p;
							if ($p != 'allowtorandompic' && $p != 'index.php' && is_file($file)) {
								$usersdata[$f]['pictures']++;
							}

							if ($p != 'allowtorandompic' && $p != 'index.php' && is_file($file) && is_file("./" . $subordner . "/" . $f . "/" . $a . "/allowtorandompic")) {
								$usersdata[$f]['pictures_allowed']++;
							}

						}
					}
				}
			}
		}
		$gesamtbilder = 0;
		$gesamtbilder_allowed = 0;
		foreach ($usersdata as $data) {
			$gesamtbilder += $data['pictures'];
			$gesamtbilder_allowed += $data['pictures_allowed'];
		}
		foreach ($usersdata as $data) {
			$prz_pic = round($data['pictures'] / $gesamtbilder, 2) * 100;
			if ($gesamtbilder_allowed == 0) {
				$prz_pic_allowed = 0;
			} else {
				$prz_pic_allowed = round($data['pictures_allowed'] / $gesamtbilder_allowed, 2) * 100;
			}
			$statsinhalt .= "<tr><td>{$data['name']}</td><td>{$data['pictures']}</td><td>{$prz_pic}%</td>";
			if ($use_randompic) {
				$statsinhalt .= "<td>{$data['pictures_allowed']}</td><td>{$prz_pic_allowed}%</td>";
			}
			$statsinhalt .= "<td>" . date_format($data['lastupload'], "d.m.Y") . "</td>";
			$statsinhalt .= "</tr>";
		}
		if ($gesamtbilder_allowed > 0 && $gesamtbilder > 0) {
			$prz_all = round($gesamtbilder_allowed / $gesamtbilder, 2) * 100;
			$ges_prz = "100%";
		} else {
			$prz_all = 0;
			$ges_prz = "0%";
		}

		$statsinhalt .= "<tr><td><b>gesamt</b></td><td>{$gesamtbilder}</td><td>{$ges_prz}</td>";
		if ($use_randompic) {
			$statsinhalt .= "<td>{$gesamtbilder_allowed}</td><td>{$prz_all}%</td>";
		}
		$statsinhalt .= "</tr>";
	} else {
		$error .= "Error 9";
	}
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

function get_thread() {
	global $db, $fotoalben_board_id, $fotoalben_hidden_board_id, $ersetzen, $usenumber, $usetopic, $ordner;
	$sql = "SELECT threadid, topic FROM bb1_threads WHERE boardid = " . $fotoalben_board_id . " OR boardid = " . $fotoalben_hidden_board_id . " ORDER BY threadid DESC;";
	$result = $db->unbuffered_query($sql);
	while ($row = $db->fetch_array($result)) {
		$name = trim(strtr(strtolower($row['topic']), $ersetzen));
		if (parse_dateformats($name) == parse_dateformats($ordner)) {
			$usenumber = $row['threadid'];
			$usetopic = htmlentities($row['topic']);
		}
	}
}