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
		// Hurensohn-Filter
		if (str_replace("hurensohn", "", strtolower($update["message"]["text"])) != strtolower($update["message"]["text"])) {
			if ($update["message"]["from"]["id"] == $admin_id) {
				$text = "Du wolltest nicht mehr so oft Hurensohn sagen!";
			} else {
				$text = "Du sollst nicht Hurensohn sagen!";
			}
			post_reply($text);
			exit();
		}

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

							$sql2 = "SELECT q.*,u.username FROM tb_pictures_queue q JOIN tb_lastseen_users u ON q.postedby=u.userid WHERE TRIM(threadname) IS NULL AND current=1 LIMIT 1;";
							$result2 = $mysqli->query($sql2);
							while ($row2 = $result2->fetch_object()) {
								send_photo($row2->telegramfileid);
								$sql3 = "SELECT topicname FROM tb_set_topic LIMIT 1;";
								$result3 = $mysqli->query($sql3);
								while ($row3 = $result3->fetch_object()) {
									$oldname = $row3->topicname;
								}
								$text = "Gepostet von " . $row2->username . " am " . date("d.m.Y", $row2->postedat) . " um " . date("H:i", $row2->postedat) . "\n";
								$text .= "In welches Thema soll ich das Bild posten?\n/settopic {Name} [" . $oldname . "]\n/delpic --> Bild löschen";
								post_reply($text);
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

			case '/lastseen':
				if (count($befehle) > 1) {
					for ($i = 1; $i < count($befehle); $i++) {
						if (strtolower($befehle[$i]) == strtolower($update["message"]["from"]["first_name"])) {
							$text = "Willst du mich rollen? Du bist online! ;(";
							post_reply($text);
						} else {
							$sql = "SELECT * FROM tb_lastseen_users WHERE LOWER(username)='" . strtolower($mysqli->real_escape_string($befehle[$i])) . "' ORDER BY id ASC LIMIT 1";
							$result = $mysqli->query($sql);
							if ($result->num_rows > 0) {
								while ($row = $result->fetch_object()) {
									$text = "Hallo " . $update["message"]["from"]["first_name"] . ",\nich habe " . $row->username . " zuletzt am " . strftime("%A", $row->time) . ", dem " . date("d.m.Y", $row->time) . " um " . date("H:i", $row->time) . "Uhr gesehen.";
									post_reply($text);
								}
							} else {
								$text = "Tut mir leid, " . $befehle[$i] . " kenne ich nicht.";
								post_reply($text);
							}
						}
					}
				} else {
					post_reply("Du musst einen Nutzer angeben, etwa so: /lastseen " . $example_name);
				}

				break;

			case '/stats':
				if (isset($befehle[1])) {
					switch (strtolower($befehle[1])) {
						case 'allwords':
							$max_lengths = array(0, 0, 0, 0, 0, 0);
							// contents
							$toc = array();

							// heading
							$zeilenarray = array(
								"word" => "Wort",
								"count" => "Anzahl",
								"firstusedby" => "Zuerst von",
								"firstusedat" => "am",
								"lastusedby" => "Zuletzt von",
								"lastusedat" => "am",
							);
							$toc[] = $zeilenarray;

							// get information for every word
							$sql = "SELECT * FROM tb_word_stats ORDER BY word ASC;";
							$result = $mysqli->query($sql);
							while ($row = $result->fetch_object()) {
								// firstseen
								$sql2 = "SELECT username FROM tb_lastseen_users WHERE userid='" . $row->firstusedby . "' LIMIT 1;";
								$result2 = $mysqli->query($sql2);
								while ($row2 = $result2->fetch_object()) {
									$firstseen_username = $row2->username;
								}

								// lastseen
								$sql2 = "SELECT username FROM tb_lastseen_users WHERE userid='" . $row->lastusedby . "' LIMIT 1;";
								$result2 = $mysqli->query($sql2);
								while ($row2 = $result2->fetch_object()) {
									$lastseen_username = $row2->username;
								}

								$zeilenarray = array(
									"word" => $row->word,
									"count" => $row->count,
									"firstusedby" => $firstseen_username,
									"firstusedat" => date("d.m.Y h:i", $row->firstusedat),
									"lastusedby" => $lastseen_username,
									"lastusedat" => date("d.m.Y h:i", $row->lastusedat),
								);
								$toc[] = $zeilenarray;
							}

							// max space for padding
							$maxlengtharray = array(
								"word" => 0,
								"count" => 0,
								"firstusedby" => 0,
								"firstusedat" => 0,
								"lastusedby" => 0,
								"lastusedat" => 0,
							);
							foreach ($toc as $toc_row) {
								if (strlen($toc_row["word"]) > $maxlengtharray["word"]) {
									$maxlengtharray["word"] = strlen($toc_row["word"]);
								}
								if (strlen($toc_row["count"]) > $maxlengtharray["count"]) {
									$maxlengtharray["count"] = strlen($toc_row["count"]);
								}
								if (strlen($toc_row["firstusedby"]) > $maxlengtharray["firstusedby"]) {
									$maxlengtharray["firstusedby"] = strlen($toc_row["firstusedby"]);
								}
								if (strlen($toc_row["firstusedat"]) > $maxlengtharray["firstusedat"]) {
									$maxlengtharray["firstusedat"] = strlen($toc_row["firstusedat"]);
								}
								if (strlen($toc_row["lastusedby"]) > $maxlengtharray["lastusedby"]) {
									$maxlengtharray["lastusedby"] = strlen($toc_row["lastusedby"]);
								}
								if (strlen($toc_row["lastusedat"]) > $maxlengtharray["lastusedat"]) {
									$maxlengtharray["lastusedat"] = strlen($toc_row["lastusedat"]);
								}
							}

							$text = str_pad($toc[0]["word"], $maxlengtharray["word"]) . "|" . str_pad($toc[0]["count"], $maxlengtharray["count"]) . "|" . str_pad($toc[0]["firstusedby"], $maxlengtharray["firstusedby"]) . "|" . str_pad($toc[0]["firstusedat"], $maxlengtharray["firstusedat"]) . "|" . str_pad($toc[0]["lastusedby"], $maxlengtharray["lastusedby"]) . "|" . str_pad($toc[0]["lastusedat"], $maxlengtharray["lastusedat"]) . "\n\n";
							// $text .= str_pad("_", $maxlengtharray["word"], "_") . "+" . str_pad("_", $maxlengtharray["count"], "_") . "+" . str_pad("_", $maxlengtharray["firstusedby"], "_") . "+" . str_pad("_", $maxlengtharray["firstusedat"], "_") . "+" . str_pad("_", $maxlengtharray["lastusedby"], "_") . "+" . str_pad("_", $maxlengtharray["lastusedat"], "_") . "\n";

							for ($i = 1; $i < count($toc); $i++) {
								$text .= str_pad($toc[$i]["word"], $maxlengtharray["word"]) . "|" . str_pad($toc[$i]["count"], $maxlengtharray["count"]) . "|" . str_pad($toc[$i]["firstusedby"], $maxlengtharray["firstusedby"]) . "|" . str_pad($toc[$i]["firstusedat"], $maxlengtharray["firstusedat"]) . "|" . str_pad($toc[$i]["lastusedby"], $maxlengtharray["lastusedby"]) . "|" . str_pad($toc[$i]["lastusedat"], $maxlengtharray["lastusedat"]) . "\n";
							}

							post_reply("Insgesamt habe ich " . $result->num_rows . " Wörter gesehen:");
							post_reply($text);
							break;

						case 'word':
							if (isset($befehle[2]) && trim($befehle[2]) != '') {
								$sql = "SELECT * FROM tb_word_stats WHERE word='" . $mysqli->real_escape_string(trim($befehle[2])) . "' LIMIT 1;";
								$result = $mysqli->query($sql);
								$text = '';
								if ($result->num_rows > 0) {
									while ($row = $result->fetch_object()) {
										if ($row->count > 0) {
											// firstseen
											$sql2 = "SELECT username FROM tb_lastseen_users WHERE userid='" . $row->firstusedby . "' LIMIT 1;";
											$result2 = $mysqli->query($sql2);
											while ($row2 = $result2->fetch_object()) {
												$firstseen_username = $row2->username;
											}

											// lastseen
											$sql2 = "SELECT username FROM tb_lastseen_users WHERE userid='" . $row->lastusedby . "' LIMIT 1;";
											$result2 = $mysqli->query($sql2);
											while ($row2 = $result2->fetch_object()) {
												$lastseen_username = $row2->username;
											}

											// render response
											$text .= "Das Wort " . $row->word . " wurde zuerst von " . $firstseen_username . " am " . date("d.m.Y", $row->firstusedat) . " um " . date("H:i", $row->firstusedat) . " verwendet. Zuletzt hat es " . $lastseen_username . " am " . date("d.m.Y", $row->lastusedat) . " um " . date("H:i", $row->lastusedat) . " in den Chat gepostet.\nInsgesamt wurde es " . $row->count . "mal geschrieben.";
										} else {
											$text .= "Ich kenne das Wort " . trim($befehle[2]) . " nicht.";
										}
									}
								} else {
									$text .= "Ich kenne das Wort " . trim($befehle[2]) . " nicht.";
								}
								post_reply($text);
							} else {
								call_stats_help();
							}
							break;

						case 'common':
							post_reply("coming next...");
							break;

						case 'me':
							$text = "Alles, was ich über dich weiß:\n\n";

							$sql = "SELECT * FROM tb_lastseen_users WHERE userid='" . $update["message"]["from"]["id"] . "' LIMIT 1;";
							$result = $mysqli->query($sql);
							while ($row = $result->fetch_object()) {
								$text .= "Deine Telegram-ID ist " . $row->userid . ".\n";
								$text .= "Dein Telegram-Vorname ist " . $row->username . ".\n";
								$text .= "Zuletzt warst du am " . date("d.m.Y", $row->time) . " um " . date("h:i", $row->time) . "Uhr online.\n";
							}
							$text .= "Unsere Chat-ID ist " . $chatID . ".\n";

							$sql = "SELECT COUNT(*) AS anzahl FROM tb_word_stats WHERE firstusedby='" . $update["message"]["from"]["id"] . "';";
							$result = $mysqli->query($sql);
							while ($row = $result->fetch_object()) {
								$text .= "Du hast im Chat " . $row->anzahl . " Wörter zuerst gesagt.\n";
							}
							post_reply($text);
							break;

						default:
							call_stats_help();
							break;
					}
				} else {
					call_stats_help();
				}
				break;

			case '/hitlerwitz':
				$text = "Hitler marschiert in Polen ein.\n";
				$text .= "Er läuft mittig vor zwei Solden. Einer rechts und einer links an seiner Seite.\n";
				$text .= "Grimmig schauen, betritt er ein Dorf. Er schreitet langsam voran, einen Fuß vor den anderen setzend.\n";
				$text .= "\nNach kurzer Zeit kommt er an einer Schmiede vorbei. Der schmied sieht Hitler, erstarrt und lässt vor Schreck seinen Hammer fallen.\n";
				$text .= "Dann kommt er an einer Mühle vorbei. Der Müller schaut ihn an, lässt vor Schreck seinen Sack mit Mehl fallen, den er gerade in der Hand hielt.\n";
				$text .= "Unterdessen schreitet Hitler ohne eine Miene zu verziehen voran.\n\n";
				$text .= "Weiter in dem Dorf kommt er an einer Näherei vorbei. Die Schneiderinnen sehen Hitler und blickten ihn wie erstarrt an, so angsterfüllt sind sie.\n";
				$text .= "Dabenen ist ein Obstverkäufer. Auch er sieht den Führer und kann es nicht fassen. Er bringt kein Wort heraus, so erstarrt ist er.\n\n\n";
				$text .= "Ein ganzes Stück später befindet sich eine große Wiese, auf der ein kleines Mädchen spielt. Es turnt umher und pflückt dabei Blumen. So bunt, wie man sie sich nur vorstellen kann und von solch einer Schönheit, wie es sie sonst nirgens auf der Welt gibt!\n";
				$text .= "Als der Führer das sieht, zitiert er das Mädchen sofort zu sich heran und fragt sie: 'Na meine Kleine, was pflückst du denn da Schönes?'.\n";
				$text .= "Das Mädchen antwortet: 'Mein Führer, das sind die buntesten und schönsten Blumen, die man hier in der Gegend finden kann! Nie wieder wirst du so etwas schönes sehen!'.\n";
				$text .= "Sichtlich erfreut fragt er da Mädchen, ob er denn nicht einmal daran riechen dürfte. Das Mädchen streckt ihm den Strauß Blumen hin und Hitler hält seinen Kolben hinein.\n\n";
				$text .= "Da plötzlich holt das Mädchen aus und steckt ihm ein Bündel Gras in den Mund.\n";
				$text .= "Irritiert schnauzt Hitler das Kind an: 'Meine Fresse, was soll das? Ich werde dich standrechtlich erschießen lassen!'.\n";
				$text .= "Da sagt das Kind: 'Mein Vater hat gesagt, wenn der Führer ins Gras beißt, wird alles besser!'";
				post_reply($text);

				$sendto = API_URL . "sendsticker?chat_id=" . $chatID . "&sticker=BQADAgADRAIAAiHtuwM4RQnJhcQXrwI";
				file_get_contents($sendto);
				break;

			case '/help':
				$text = "/help --> Dieses Menü\n";
				$text .= "/lastseen {NUTZERNAME} --> Zeigt dir an, wann der Nutzer das letzte mal online war.\n";
				$text .= "/stats --> Zeigt Statistiken\n";
				$text .= "/nextpic --> Bearbeite Bilder fürs Forum\n";
				$text .= "/hitlerwitz --> Tells the infamous Hitlerwitz!\n";
				post_reply($text);
				break;

			case '/startvote':
				// check if is admin
				if ($update["message"]["from"]["id"] == $admin_id) {
					// check if there is a vote already
					$sql = "SELECT * FROM tb_vote_options;";
					$result = $mysqli->query($sql);
					if ($result->num_rows > 0) {
						post_reply("Es gibt bereits eine Abstimmung. Bitte schließe diese zuerst!\n/closevote");
					} else {
						// check if all params are set
						// unique array at first
						$befehle = array_unique($befehle);
						if (count($befehle) > 2) {
							$text = "Hey Leute,\nihr müsst jetzt abstimmen!\n";
							for ($i = 1; $i < count($befehle); $i++) {
								// insert each into db
								$sql = "INSERT INTO tb_vote_options (vote_option) VALUES('" . $mysqli->real_escape_string($befehle[$i]) . "')";
								$mysqli->query($sql);
								$text .= "Stimmst du für [[" . $befehle[$i] . "]], dann schreibe\n/vote " . $befehle[$i] . "\n\n";
							}
							$text .= "Möge der Bessere gewinnen!";
							post_reply($text);
						} else {
							post_reply("Du musst mindestens zwei Optionen angeben!");
						}
					}
				} else {
					post_reply("Sorry, das darf nur der Admin!");
				}
				break;

			case '/voteintermediateresult':
				// print stats
				$sql = "SELECT tb_vote_options.vote_option, COUNT(tb_vote_options.vote_option) AS count FROM tb_votes JOIN tb_vote_options ON tb_vote_options.ID=tb_votes.vote_option_id GROUP BY vote_option_id ORDER BY count DESC;";
				$result = $mysqli->query($sql);
				$count = 0;
				$merk = 0;

				$text = "Der derzeitige Zwischenstand sieht so aus:\n\n";
				while ($row = $result->fetch_object()) {
					// test if Gleichstand
					if ($merk != $row->count) {
						$count++;
					}
					$text .= "Platz " . $count . ": " . $row->vote_option . " mit " . $row->count . " Stimmen,\n";
					$merk = $row->count;
				}
				$text = substr($text, 0, -2);
				post_reply($text);
				break;

			case '/closevote':
				if ($update["message"]["from"]["id"] == $admin_id) {
					// check if there is a vote already
					$sql = "SELECT * FROM tb_vote_options;";
					$result = $mysqli->query($sql);
					if ($result->num_rows == 0) {
						post_reply("Es gibt keine Abstimmung. Bitte eröffne eine neue!\n/startvote");
					} else {
						// print stats
						$sql = "SELECT tb_vote_options.vote_option, COUNT(tb_vote_options.vote_option) AS count FROM tb_votes JOIN tb_vote_options ON tb_vote_options.ID=tb_votes.vote_option_id GROUP BY vote_option_id ORDER BY count DESC;";
						$result = $mysqli->query($sql);
						$count = 0;
						$merk = 0;

						$text = "Ergebis der letzten Abstimmung:\n\n";
						while ($row = $result->fetch_object()) {
							// test if Gleichstand
							if ($merk != $row->count) {
								$count++;
							}
							$text .= "Platz " . $count . ": " . $row->vote_option . " mit " . $row->count . " Stimmen,\n";
							$merk = $row->count;
						}
						$text = substr($text, 0, -2);
						post_reply($text);

						//reset everything
						$sql = "DELETE FROM tb_votes;";
						$mysqli->query($sql);
						$sql = "DELETE FROM tb_vote_options;";
						$mysqli->query($sql);
						post_reply("Erfolgreich zurückgesetzt!\nNeue Abstimmung mit /startvote");
					}
				} else {
					post_reply("Sorry, das darf nur der Admin!");
				}
				break;

			case '/vote':
				// test of voteoption isset
				if (isset($befehle[1])) {
					// test, if vote already given
					$sql = "SELECT ID FROM tb_votes WHERE telegram_id='" . $update["message"]["from"]["id"] . "';";
					$result = $mysqli->query($sql);
					if ($result->num_rows > 0) {
						post_reply("Sorry, du hast schon abgestimmt!");
					} else {
						// test if voteoption is valid
						$sql = "SELECT ID FROM tb_vote_options WHERE vote_option='" . $mysqli->real_escape_string(trim($befehle[1])) . "' LIMIT 1;";
						$result = $mysqli->query($sql);
						if ($result->num_rows == 1) {
							// vote is valid, save it
							while ($row = $result->fetch_object()) {
								$sql = "INSERT INTO tb_votes (vote_option_id, telegram_id) VALUES('" . $row->ID . "', '" . trim($update["message"]["from"]["id"]) . "');";
								$mysqli->query($sql);
							}
							post_reply("Stimme erfolgreich gespeichert!");
						} else {
							post_reply("Tut mir leid, aber diese Option steht nicht zur Auswahl!");
						}
					}
				} else {
					post_reply("Du musst angeben, wem du die Stimme geben willst:\n/vote Option");
				}
				break;

			default:
				// Stats
				$all_words = explode(" ", str_replace("\n", "", $update["message"]["text"]));
				foreach ($all_words as $word) {
					insert_or_update_word($word, $update["message"]["from"]["id"]);
				}
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
			$sql = "INSERT INTO tb_pictures_queue (filename, location, telegramfileid, postedby, postedat) VALUES('" . $mysqli->real_escape_string($filename) . "', '" . $savename . "', '" . $file_id . "', '" . $mysqli->real_escape_string($update["message"]["from"]["id"]) . "', '" . time() . "');";
			$mysqli->query($sql);

			// compose reply
			// post_reply("Danke, " . $update["message"]["from"]["first_name"] . "! Das Bild wurde für das Forum vorgemerkt!");
		} else {
			post_reply("Es gab leider einen Fehler beim vormerken des Bildes! :( @" . $admin_name);
		}
	}
}
