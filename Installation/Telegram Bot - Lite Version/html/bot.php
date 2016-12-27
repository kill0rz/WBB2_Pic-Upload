<?php

setlocale(LC_TIME, "de_DE.utf8");
date_default_timezone_set("Europe/Berlin");

// config
include "config.php";

// functions
include "functions.php";

function call_post_help() {
	$text = "Bitte gib an, was genau du machen willst:\n";
	$text .= "/nextpic --> bearbeite das nächste Bild\n";
	$text .= "/delpic --> lösche aktuelles Bild\n";
	$text .= "/postall --> Schreibe alle Bilder ins Forum\n";
	$text .= "/help --> Allgemeine Hilfe\n";
	post_reply($text);
}

// MySQL-Config

function mysqli_db_connect() {
	global $mysqli, $chatID, $mysql_server, $mysql_user, $mysql_password, $mysql_db, $admin_name;

	try {
		$mysqli = new mysqli($mysql_server, $mysql_user, $mysql_password, $mysql_db);
	} catch (Exception $e) {
		post_reply("Datenbankfehler! @" . $admin_name);
		exit();
	}

	if ($mysqli->connect_errno) {
		post_reply("Datenbankfehler! @" . $admin_name);
		exit();
	}
	$mysqli->set_charset("utf8");
}

function db_connect() {
	global $db, $chatID, $mysql_server, $mysql_user, $mysql_password, $db_db, $admin_name;

	try {
		$db = new mysqli($mysql_server, $mysql_user, $mysql_password, $db_db);
	} catch (Exception $e) {
		post_reply("Datenbankfehler! @" . $admin_name);
		exit();
	}

	if ($db->connect_errno) {
		post_reply("Datenbankfehler! @" . $admin_name);
		exit();
	}
	$db->set_charset("utf8");
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

	if (isset($update["message"]["text"])) {
		$befehle = explode(" ", $update["message"]["text"]);

		switch (str_replace($bot_atname, "", strtolower($befehle[0]))) {
			case '/postall':
				if ($update["message"]["from"]["id"] == $admin_id) {
					$done_counter = 0;
					$links = '';
					$albenurl = $url2board;

					// Schließe die Bearbeitung aller Bilder:
					$sql = "UPDATE tb_pictures_queue SET current=0;";
					$mysqli->query($sql);

					// Hole zuerst alle Topic-Names
					$sql = "SELECT threadname FROM tb_pictures_queue WHERE TRIM(threadname) IS NOT NULL GROUP BY threadname";
					$result = $mysqli->query($sql);
					while ($row = $result->fetch_object()) {

						// prüfen, ob der Thread schon existiert
						$usenumber = 0;
						get_thread($row->threadname);

						// Jetzt nach Nutzernamen Gruppieren
						$sql3 = "SELECT q.postedby,u.username FROM tb_pictures_queue q JOIN tb_lastseen_users u ON q.postedby=u.userid GROUP BY postedby;";
						$result3 = $mysqli->query($sql3);
						while ($row3 = $result3->fetch_object()) {
							$trigger_poster_name = true;

							$sql2 = "SELECT * FROM tb_pictures_queue WHERE TRIM(threadname)='" . $row->threadname . "' AND postedby='" . $row3->postedby . "'";
							$result2 = $mysqli->query($sql2);
							while ($row2 = $result2->fetch_object()) {
								if ($trigger_poster_name) {
									$links .= "Bilder von " . $row3->username . ":\n";
									$trigger_poster_name = false;
								}

								$thema = strtr(strtolower(trim($row->threadname)), $ersetzen);

								// ggf. Ordner erstellen und directory listing verhindern
								if (!is_dir($subordner)) {
									mkdir($subordner, 0777);
								}
								if (!is_dir($subordner . "/" . $bot_userid)) {
									mkdir($subordner . "/" . $bot_userid, 0777);
								}
								if (!is_dir($subordner . "/" . $bot_userid . "/" . $thema)) {
									mkdir($subordner . "/" . $bot_userid . "/" . $thema, 0777);
								}
								makeindex($subordner . "/" . $bot_userid . "/" . $thema . "/");
								makeindex($subordner . "/" . $bot_userid . "/");
								makeindex($subordner . "/");
								$umaskold = umask(0);

								$DateiName = strtr($row2->filename, $ersetzen);
								resizeImage("./img/" . $row2->location, $subordner . "/" . $bot_userid . "/" . $thema . "/" . $DateiName, 1300, 1, 1);
								$links .= "[IMG]" . $albenurl . "/" . $bot_userid . "/" . $thema . "/" . $DateiName . "[/IMG]\n";

								// remove pic from queue
								$sql3 = "DELETE FROM tb_pictures_queue WHERE id='" . $row2->id . "'";
								$mysqli->query($sql3);
								@unlink("./img/" . $row2->location);
								$done_counter++;
							}
							$links .= "\n\n";
						}

						// VGPOST bei Viktor - v-gn.de *Anfang*
						$time = time();

						/* Thread erstellen */
						$posting_thema = $row->threadname;
						$posting_prefix = 'Telegram';

						/* Username holen */
						$user_info = query_first($db, "SELECT username FROM bb" . $n . "_users WHERE userid = '" . $bot_userid . "'");
						$vgp_username = $user_info['username'];

						// Thread schon vorhanden oder neuen erstellen?
						$hasbeenpostetasareply = true;
						if ($usenumber == 0) {
							// neuer Thread
							$subjekt = $posting_thema;

							$db->query("INSERT INTO bb" . $n . "_threads (boardid,prefix,topic,iconid,starttime,starterid,starter,lastposttime,lastposterid,lastposter,attachments,pollid,important,visible)
												VALUES ('" . $bot_boardid . "', '" . addslashes($posting_prefix) . "', '" . addslashes($posting_thema) . "', '0', '" . $time . "', '" . $bot_userid . "', '" . addslashes($user_info['username']) . "', '" . $time . "', '" . $bot_userid . "', '" . addslashes($user_info['username']) . "', '0', '0', '0', '1')");
							$threadid = $db->insert_id;
							$hasbeenpostetasareply = false;
							post_reply("Erstelle neuen Thread: " . $posting_thema);
						} else {
							// antworte auf
							$threadid = $usenumber;
							$subjekt = "[" . $posting_prefix . "] " . $posting_thema;

							post_reply("Antworte auf Thread: " . $usetopic);
						}

						$b_thread = $links;

						/* Post erstellen */
						$db->query("INSERT INTO bb" . $n . "_posts (threadid,userid,username,iconid,posttopic,posttime,message,attachments,allowsmilies,allowhtml,allowbbcode,allowimages,showsignature,ipaddress,visible)
											VALUES ('" . $threadid . "', '" . $bot_userid . "', '" . addslashes($user_info['username']) . "', '0', '" . addslashes($subjekt) . "', '" . $time . "', '" . addslashes($b_thread) . "', '0', '1', '0', '1', '1', '1', '127.0.0.1', '1')");
						$postid = $db->insert_id;

						/* Board updaten */
						$boardstr = query_first($db, "SELECT parentlist FROM bb" . $n . "_boards WHERE boardid = '" . $bot_boardid . "'");
						$parentlist = $boardstr['parentlist'];

						/* update thread info */
						$db->query("UPDATE bb" . $n . "_threads SET lastposttime = '" . $time . "', lastposterid = '" . $bot_userid . "', lastposter = '" . addslashes($user_info['username']) . "', replycount = replycount+1 WHERE threadid = '{$threadid}'", 1);

						/* update board info */
						$db->query("UPDATE bb" . $n . "_boards SET postcount=postcount+1, lastthreadid='{$threadid}', lastposttime='" . $time . "', lastposterid='" . $bot_userid . "', lastposter='" . addslashes($user_info['username']) . "' WHERE boardid IN ({$parentlist},{$bot_boardid})", 1);

						$db->query("UPDATE bb" . $n . "_users SET userposts=userposts+1 WHERE userid = '" . $bot_userid . "'", 1);

						/* Statistik updaten */
						if ($hasbeenpostetasareply) {
							$db->query("UPDATE bb" . $n . "_stats SET threadcount=threadcount+1, postcount=postcount+1", 1);
						} else {
							$db->query("UPDATE bb" . $n . "_stats SET postcount=postcount+1", 1);
						}

						// VGPOST bei Viktor - v-gn.de *Anfang*
					}
					post_reply("Es wurden " . $done_counter . " Bilder ins Forum gepostet.");
					// call_post_help();
				} else {
					post_reply("Sorry, das darf nur der Admin!");
				}

				break;

			case '/nextpic':
				// hole das nächste Bild aus der Queue und lege den Threadnamen fest

				if ($update["message"]["from"]["id"] == $admin_id) {

					// Prüfen, ob mehr als 0 Bilder zum abarbeiten da sind
					$sql = "SELECT * FROM tb_pictures_queue WHERE TRIM(threadname) IS NULL ORDER BY id ASC LIMIT 1;";
					$result = $mysqli->query($sql);
					if ($result->num_rows > 0) {
						while ($row = $result->fetch_object()) {
							// check, ob bereits ein Pic in der Queue ist
							$sql2 = "SELECT * FROM tb_pictures_queue WHERE TRIM(threadname) IS NULL AND current=1 LIMIT 1;";
							$result2 = $mysqli->query($sql2);
							if ($result2->num_rows > 0) {
								while ($row2 = $result2->fetch_object()) {
									$id = $row2->id;
								}
							} else {
								$id = $row->id;
							}

							// set current
							$sql3 = "UPDATE tb_pictures_queue SET current=1 WHERE id='" . $id . "'";
							$mysqli->query($sql3);

							$sql2 = "SELECT * FROM tb_pictures_queue WHERE TRIM(threadname) IS NULL AND current=1 LIMIT 1;";
							$result2 = $mysqli->query($sql2);
							while ($row2 = $result2->fetch_object()) {
								send_photo($row2->telegramfileid);
								$sql3 = "SELECT topicname FROM tb_set_topic LIMIT 1;";
								$result3 = $mysqli->query($sql3);
								while ($row3 = $result3->fetch_object()) {
									$oldname = $row3->topicname;
								}
								post_reply("In welches Thema soll ich das Bild posten?\n/settopic {Name} [" . $oldname . "]\n/delpic --> Bild löschen");
							}

						}
					} else {
						post_reply("Es gibt derzeit keine Bilder, die abgearbeitet werden können.");
						call_post_help();
					}
				} else {
					post_reply("Sorry, das darf nur der Admin!");
				}
				break;

			case '/delpic':
				if ($update["message"]["from"]["id"] == $admin_id) {
					$sql = "SELECT id, location FROM tb_pictures_queue WHERE current=1 AND TRIM(threadname) IS NULL LIMIT 1;";
					$result = $mysqli->query($sql);
					if ($result->num_rows > 0) {
						while ($row = $result->fetch_object()) {
							// Es gibt ein Bild, dass gelöscht werden kann
							// --> Löschen des Tupel
							$sql2 = "DELETE FROM tb_pictures_queue WHERE id='" . $row->id . "';";
							$mysqli->query($sql2);
							@unlink("./img/" . $row->location);
							post_reply("Bild erfolgreich gelöscht!\n/nextpic");
						}
					} else {
						post_reply("Es befindet sich kein Bild in der Queue.");
						call_post_help();
					}
				} else {
					post_reply("Sorry, das darf nur der Admin!");
				}
				break;

			case '/settopic':
				if ($update["message"]["from"]["id"] == $admin_id) {
					if (!isset($befehle[1]) || trim($befehle[1]) == '') {
						$sql = "SELECT topicname FROM tb_set_topic LIMIT 1;";
						$result = $mysqli->query($sql);
						while ($row = $result->fetch_object()) {
							post_reply("Kein Thema angegeben, ich nehme das letzte: " . $row->topicname);
							$topic = $row->topicname;
						}
					} else {
						$topic = '';
						for ($i = 1; $i < count($befehle); $i++) {
							$topic .= " " . $befehle[$i];
						}
						$topic = trim($topic);
					}

					$sql = "SELECT id FROM tb_pictures_queue WHERE current=1 AND TRIM(threadname) IS NULL LIMIT 1;";
					$result = $mysqli->query($sql);
					if ($result->num_rows > 0) {
						while ($row = $result->fetch_object()) {
							// Wir kennen das Thema und es gibt ein Bild, dass das Thema erwartet
							// --> Update des Tupel
							$sql2 = "UPDATE tb_pictures_queue SET threadname='" . $mysqli->real_escape_string($topic) . "', current=0 WHERE id='" . $row->id . "';";
							$mysqli->query($sql2);
							$sql2 = "UPDATE tb_set_topic SET topicname='" . $mysqli->real_escape_string($topic) . "';";
							$mysqli->query($sql2);
							post_reply("Thema erfolgreich gesetzt!");
							call_post_help();
						}
					} else {
						post_reply("Es befindet sich kein Bild in der Queue.");
					}
				} else {
					post_reply("Sorry, das darf nur der Admin!");
				}
				break;

			case '/help':
				$text = "/help --> Dieses Menü\n";
				$text .= "/nextpic --> Bearbeite Bilder fürs Forum\n";
				post_reply($text);
				break;
		}
	}

	// check if file is attached
	if (isset($update["message"]["photo"]) && @isset($update["message"]["photo"][count($update["message"]["photo"]) - 1]["file_id"])) {
		// komprimierte Bilder
		$file_id = $update["message"]["photo"][count($update["message"]["photo"]) - 1]["file_id"];
		$filename = isset($update["message"]["photo"][count($update["message"]["photo"]) - 1]["file_path"]) ? str_replace("photo/", "", $update["message"]["photo"][count($update["message"]["photo"]) - 1]["file_path"]) : time() . ".jpg";
	} elseif (isset($update["message"]["document"]["file_id"])) {
		// unkomprimierte Bilder und Dateianhänge
		if (strtolower(substr($update["message"]["document"]["mime_type"], 0, 5)) == "image") {
			$filename = $update["message"]["document"]["file_name"];
			$file_id = $update["message"]["document"]["file_id"];
		}
	}

	// if so, put it into DB
	if (isset($file_id) && isset($filename)) {
		// get info
		$sendto = API_URL . "getfile?file_id=" . $file_id;
		$resp = json_decode(file_get_contents($sendto), true);
		$file_path = $resp["result"]["file_path"];

		$sendto = API_URL_FILE . $file_path;
		$savename = time() . rand(0, 1000) . rand(0, 1000) . rand(0, 1000) . rand(0, 1000);
		if (file_put_contents("img/" . $savename, file_get_contents($sendto))) {

			$sql = "INSERT INTO tb_pictures_queue (filename, location, telegramfileid, postedby) VALUES('" . $mysqli->real_escape_string($filename) . "', '" . $savename . "', '" . $file_id . "', '" . $mysqli->real_escape_string($update["message"]["from"]["id"]) . "');";
			$mysqli->query($sql);

			// compose reply
			post_reply("Danke, " . $update["message"]["from"]["first_name"] . "! Bild wurde für das Forum vorgemerkt!");
		} else {
			post_reply("Es gab leider einen Fehler beim vormerken des Bildes! :( @" . $admin_name);
		}
	}
}