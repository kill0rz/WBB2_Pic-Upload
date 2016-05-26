<?php

// Konfiguration Anfang

$subordner = "pub/Fotoalben_neu"; //ohne / am Ende
$erlaubtegruppen = array("1","4");
$fotoalben_board_id = 1;

// Konfiguration Ende

//
//

//
// Pic-Upload Script v2.0 by kill0rz
//

//
//

$filename = "picupload.php";
require('./global.php');
require('./acp/lib/config.inc.php');
require('./acp/lib/class_parse.php');
require('./acp/lib/options.inc.php');

$albenurl = $url2board . "/" . $subordner . "/";
$error = "";
$filename = "picupload.php";

function inarray($array1, $array2){
	foreach($array1 as $a1){
		foreach($array2 as $a2){
			if($a1 == $a2) return true;
		}
	}
	return false;
}

function makeindex($pfad){
	$datei = fopen($pfad."index.php","w");
	fwrite($datei,"");
	fclose($datei);
}

function resizeImage ($filepath_old, $filepath_new, $image_dimension, $scale_mode = 0, $overwrite = 0) {
	if($overwrite == 1){
		if (!(file_exists($filepath_old))) return false;
	}else{
		if (!(file_exists($filepath_old)) || file_exists($filepath_new)) return false;
	}

	$image_attributes = getimagesize($filepath_old); 
	$image_width_old = $image_attributes[0]; 
	$image_height_old = $image_attributes[1]; 
	$image_filetype = $image_attributes[2];

	if($image_width_old <= $image_dimension or !(isset($_POST['compress']) and $_POST['compress'] == "true")){
		if(copy($filepath_old, $filepath_new)){
			return true;
		}else{
			return false;
		}
	}

	if ($image_width_old <= 0 || $image_height_old <= 0) return false;
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

$loggedin = false;
if($wbbuserdata['userid'] != "0"){
	if(inarray($erlaubtegruppen,$wbbuserdata['groupids'])){ 
		$loggedin = true;
	}
}

if($loggedin){
	$done = false;
	$dowithbutton = '';
	if(isset($_POST['newdir']) and trim($_POST['newdir']) != ''){
		$ordner = trim($_POST['newdir']);
	}elseif(isset($_POST['ordner']) and trim($_POST['ordner']) != ''){
		$ordner = trim($_POST['ordner']);
	}elseif(isset($_GET['title']) and trim($_GET['title']) != ''){
		$ordner = trim($_GET['title']);
	}else{
		$ordner = '';
	}

	if(trim($ordner) == "") $ordner = "default";

	$ordner_orig = $ordner;
	if ($ordner != "default") $ordner_anz = $ordner;
	else $ordner_anz = '';

	$ersetzen = array('ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'ß' => 'ss', ' ' => '_', '\\' => '-', '/' => '-', "http://" => "", "http" => "", "//" => "", ":" => "", ";" => "", "[" => "" , "]" => "", "{" => "", "}" => "", "%" => "", "$" => "", "?" => "", "!" => "", "=" => "");
	$ordner = strtr(strtolower($ordner), $ersetzen);
	if(isset($_POST['sent']) and $_POST['sent'] == 1){
		if(trim($_POST['links']) != '') $links = trim($_POST['links'])."\n";
		else $links = '';

		//Local-Upload Felder
		for($feld = 1; $feld < 6; $feld++){
			if(isset($_FILES["file".$feld]) and $_FILES["file".$feld]['size'] > 0 and substr( strtolower( $_FILES["file".$feld]['name'] ), -5 ) == '.jpeg' || substr( strtolower( $_FILES["file".$feld]['name'] ), -4 ) == '.jpg' || substr( strtolower( $_FILES["file".$feld]['name'] ), -4 ) == '.gif' || substr( strtolower( $_FILES["file".$feld]['name'] ), -4 ) == '.bmp' || substr( strtolower( $_FILES["file".$feld]['name'] ), -4 ) == '.png'){
				if(!is_dir($subordner."/".$wbbuserdata['userid'])){
					mkdir($subordner."/".$wbbuserdata['userid'], 0777);
				}
				if(!is_dir($subordner)){
					mkdir($subordner, 0777);
				}
				if(!is_dir($subordner."/".$wbbuserdata['userid']."/".$ordner)){
					mkdir($subordner."/".$wbbuserdata['userid']."/".$ordner, 0777);
				}
				makeindex($subordner."/".$wbbuserdata['userid']."/".$ordner."/");
				makeindex($subordner."/".$wbbuserdata['userid']."/");
				makeindex($subordner."/");
				$umaskold = umask( 0 );
				$DateiName = strtr(strtolower($_FILES["file".$feld]['name']), $ersetzen);
				if(file_exists($subordner."/".$wbbuserdata['userid']."/".$ordner."/".$DateiName)){
					sleep(1);
					$DateiName = time().$DateiName;
				}
				if(resizeImage($_FILES["file".$feld]['tmp_name'], $subordner."/".$wbbuserdata['userid']."/".$ordner."/".$DateiName, 1300, 1, 1)){
					$links .= "[IMG]".$albenurl.$wbbuserdata['userid']."/".$ordner."/".$DateiName."[/IMG]\n";
					@chmod( $DateiName, 0755 );
				}else{
					$error .= "Error 1<br>";
				}
				@umask( $umaskold );
				$done = true;
			}
		}

		//URL-Felder
		for($feld = 1; $feld < 6; $feld++){
			if(isset($_POST["url".$feld]) and substr( strtolower( $_POST["url".$feld] ), -5 ) == '.jpeg' || substr( strtolower( $_POST["url".$feld] ), -4 ) == '.jpg' || substr( strtolower( $_POST["url".$feld] ), -4 ) == '.gif' || substr( strtolower( $_POST["url".$feld] ), -4 ) == '.bmp' || substr( strtolower( $_POST["url".$feld] ), -4 ) == '.png'){
				$stripped = trim($_POST["url".$feld]);
				$stripped = strip_tags($stripped);
				$wegarray = "<,>,%3E,alert(,http://,ftp://,sftp://,https://,http%3A%2F%2,https%3A%2F%2,ftp%3A%2F%2,sftp%3A%2F%2,String.fromCharCode,(,),',".'",;,<?,<?php,?>';
				$wegarray = explode(',',$wegarray);
				for($j = 1; $j < count($wegarray); $j++){
					$wegarray[$j] = trim($wegarray[$j]);
				}
				$stripped = str_replace($wegarray, "", $stripped);
				$checker = 'http://';
				$checker_len = strlen($checker);
				$short_string = substr($stripped, 0, $checker_len);
				if($short_string != $checker){
					$stripped = "http://" . $stripped;
				}

				if(!is_dir($subordner."/".$wbbuserdata['userid'])){
					mkdir($subordner."/".$wbbuserdata['userid'], 0777);
				}
				if(!is_dir($subordner."/".$wbbuserdata['userid']."/".$ordner)){
					mkdir($subordner."/".$wbbuserdata['userid']."/".$ordner, 0777);
				}
				makeindex($subordner."/".$wbbuserdata['userid']."/".$ordner."/");
				makeindex($subordner."/".$wbbuserdata['userid']."/");
				$umaskold = umask( 0 );
				$stranfang = strripos($stripped,"/");
				$DateiName = strtr(strtolower(substr($stripped,$stranfang+1)), $ersetzen);
				if(file_exists($subordner."/".$wbbuserdata['userid']."/".$ordner."/".$DateiName)){
					sleep(1);
					$DateiName = time().$DateiName;
				}
				if(@copy($stripped, $subordner."/".$wbbuserdata['userid']."/".$ordner."/".$DateiName)){ 
					resizeImage($subordner."/".$wbbuserdata['userid']."/".$ordner."/".$DateiName, $subordner."/".$wbbuserdata['userid']."/".$ordner."/".$DateiName, 1300, 1, 1);
					$links .= "[IMG]".$albenurl.$wbbuserdata['userid']."/".$ordner."/".$DateiName."[/IMG]\n";
					@chmod( $DateiName, 0755 );
				}else{
					$error .= "Error 2<br>";
				}
				@umask( $umaskold );
				$done = true;
			}
		}

		//ZIP-Datei
		if(isset($_FILES['filezip']) and substr( strtolower( $_FILES['filezip']['name'] ), -4 ) == '.zip'){
			$wegarray = "<,>,%3E,alert(,http://,ftp://,sftp://,https://,http%3A%2F%2,https%3A%2F%2,ftp%3A%2F%2,sftp%3A%2F%2,String.fromCharCode,(,),',".'",;,<?,<?php,?>';
			$wegarray = explode(',',$wegarray);
			for($j = 1; $j < count($wegarray); $j++){
				$wegarray[$j] = trim($wegarray[$j]);
			}
			$zip = new ZipArchive;
			move_uploaded_file($_FILES['filezip']['tmp_name'], "/tmp/".$_FILES['filezip']['name']);
			if ($zip->open("/tmp/".$_FILES['filezip']['name']) === TRUE) {
				$zip->extractTo('/tmp/picupload/');
				$zip->close();
				if ($handle = opendir('/tmp/picupload')) {
					while (false !== ($file = readdir($handle))) {
						if($file != "." and $file != ".."){
							if(substr( strtolower( $file ), -5 ) == '.jpeg' || substr( strtolower( $file ), -4 ) == '.jpg' || substr( strtolower( $file ), -4 ) == '.gif' || substr( strtolower( $file ), -4 ) == '.bmp' || substr( strtolower( $file ), -4 ) == '.png'){			
								if(!is_dir($subordner."/".$wbbuserdata['userid'])){
									mkdir($subordner."/".$wbbuserdata['userid'], 0777);
								}
								if(!is_dir($subordner."/".$wbbuserdata['userid']."/".$ordner)){
									mkdir($subordner."/".$wbbuserdata['userid']."/".$ordner, 0777);
								}
								makeindex($subordner."/".$wbbuserdata['userid']."/".$ordner."/");
								makeindex($subordner."/".$wbbuserdata['userid']."/");
								$umaskold = umask( 0 );
								$DateiName = str_replace($wegarray, "", $file);
								$DateiName = strtr(strtolower($DateiName), $ersetzen);
								while(file_exists($subordner."/".$wbbuserdata['userid']."/".$ordner."/".$DateiName)){
									sleep(1);
									$DateiName = time().$DateiName;
								}

								if(@copy('/tmp/picupload/'.$file,$subordner."/".$wbbuserdata['userid']."/".$ordner."/".$DateiName)){
									@chmod($subordner."/".$wbbuserdata['userid']."/".$ordner."/".$DateiName, 0777 );
									$links .= "[IMG]".$albenurl.$wbbuserdata['userid']."/".$ordner."/".$DateiName."[/IMG]\n";
									resizeImage($subordner."/".$wbbuserdata['userid']."/".$ordner."/".$DateiName, $subordner."/".$wbbuserdata['userid']."/".$ordner."/".$DateiName, 1300, 1, 1);
								}else{
									$error .= "Error 2<br>";
								}
								@umask( $umaskold );
								$done = true;
								unlink('/tmp/picupload/'.$file);
							}
						}
					}
					closedir($handle);
				}
			} else {
				echo 'Es gab einen Fehler in der Verarbeitung.';
			}
			unlink("/tmp/".$_FILES['filezip']['name']);
		}

		if(isset($_POST['sort']) and trim($_POST['sort']) == "true"){
			$linksarray = explode("\n",$links);
			natsort($linksarray);
			$links = implode($linksarray,"\n");
		}

		if($links != "" && isset($fotoalben_board_id) && $fotoalben_board_id > 0){
			$usenumber = 0;

			$ausgabe = "<textarea rows=10 cols=150>".$links."</textarea>";

			$sql = "SELECT threadid, topic FROM bb1_threads WHERE boardid = ".$fotoalben_board_id.";";
			$result = $db->unbuffered_query($sql);
			while ($row = $db->fetch_array($result)){
				$name = strtr(strtolower($row['topic']), $ersetzen);
				if ($name == $ordner) {
					$usenumber = $row['threadid'];
					$usetopic = $row['topic'];
				}
			}

			if ($usenumber > 0) {
				//we got a thread
				$dowithbutton = "<button id='addreplaytothread' onclick=\"addreplaytothread('".base64_encode($links)."', {$usenumber});\">Antworte auf Thread '{$usetopic}'</button>";
			}else{
				//newthread
				$dowithbutton = "<button id='submittonewthread' onclick=\"submittonewthread('".base64_encode($links)."', '".base64_encode($ordner_orig)."')\">Eröffne neuen Thread '".htmlentities($ordner_orig, ENT_NOQUOTES | ENT_HTML401, 'ISO-8859-1')."'</button>";
			}
		}
		if(!$done){
			$error .= "Falsches Dateiformat oder kein gültiger Ordner!<br>";
		}
	}

	$ausgabelinks = $links;
	$verzeichnishandle = $subordner."/".$wbbuserdata['userid'];
	if(is_dir($verzeichnishandle)){
		$inhalt = scandir($verzeichnishandle);
		$options = "<option>default</option>";
		foreach($inhalt as $verzeichnis){
			if($verzeichnis != '.' and $verzeichnis != '..' and $verzeichnis != 'index.php' and $verzeichnis != 'default' && is_dir($verzeichnishandle."/".$verzeichnis)){
				if(isset($ordner) and trim($ordner) == $verzeichnis){
					$selected = " selected";
					$ordner_anz = '';
				}else{
					$selected = "";
				}
				$options .= "<option{$selected}>".$verzeichnis."</option>";
			}
		}
	}else{
		$options .= "<option>default</option>";
	}

	$folders = '';
	if(is_dir($verzeichnishandle)){
		$folder = scandir($verzeichnishandle);
		if(count($folder) < 1){
			$folders = "Noch keine Vorhanden!";
		}else{
			foreach($folder as $f){
				if($f != '.' && $f != '..' && is_dir($verzeichnishandle."/".$f)){
					$folders .= "<tr><td><a href='?folder=".$f."#inhalt'>".$f."</a></td></tr>";
				}
			}
		}
	}else{
		$folders = "Noch keine vorhanden";
	}
	$links = '';
	$vorschauen = '';
	if(isset($_GET['folder']) && $_GET['folder'] != '' && !isset($_GET['action'])){
		if(is_dir($verzeichnishandle."/".$_GET['folder'])){
			$folder = scandir($verzeichnishandle."/".$_GET['folder']);
			foreach($folder as $f){
				if($f != '.' && $f != '..'){
					$links .= "[IMG]".$url2board."/".$verzeichnishandle."/".$_GET['folder']."/".$f."[/IMG]\n";
					$vorschauen .= "<a href='".$url2board."/".$verzeichnishandle."/".$_GET['folder']."/".$f."' target='_blank'><img src='".$url2board."/".$verzeichnishandle."/".$_GET['folder']."/".$f."' alt='' width='150px' /></a>\n";
				}
			}
			$unterelinks = "<textarea rows=10 cols=150>".$links."</textarea>";
		}else{
			$error .= "Error 3";
		}
	}

	//now the statistics

	//build id-name-array

	$db = mysqli_connect($sqlhost, $sqluser, $sqlpassword, $sqldb);
	if(!$db) exit("Error.");

	$usersdata = array();
	if(is_dir("./".$subordner."/")){
		$folder = scandir("./".$subordner."/");
		foreach($folder as $f){
			if(is_dir("./".$subordner."/".$f) && $f != '.' && $f != '..'){
				//username
				$result = mysqli_query($db, "SELECT username FROM bb1_users WHERE userid={$f} LIMIT 1");
				while($row = mysqli_fetch_object($result)){
					$usersdata[$f]['name'] = $row->username;
					$usersdata[$f]['pictures'] = 0;
				}

				$album = scandir("./".$subordner."/".$f."/");
				foreach($album as $a){
					if(is_dir("./".$subordner."/".$f."/".$a."/") && $f != '.' && $f != '..'){
						$picture = scandir("./".$subordner."/".$f."/".$a."/");
						foreach($picture as $p){
							$file = "./".$subordner."/".$f."/".$a."/".$p;
							if($p != 'index.php' && is_file($file)) $usersdata[$f]['pictures']++;
						}
					}
				}
			}
		}
		$gesamtbilder = 0;
		foreach ($usersdata as $data) {
			$gesamtbilder += $data['pictures'];
		}
		foreach ($usersdata as $data) {
			$prz_pic = round($data['pictures']/$gesamtbilder, 2)*100;
			$statsinhalt .= "<tr><td>{$data['name']}</td><td>{$data['pictures']}</td><td>{$prz_pic}%</td></tr>";
		}
	}else{
		$error .= "Error 9";
	}
	$ordner_anz = htmlentities($ordner_anz);
}else{
	echo "<meta http-equiv='refresh' content='0,index.php' />";
}
eval("\$tpl->output(\"".$tpl->get("picupload")."\");");
