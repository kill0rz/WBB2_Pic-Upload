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
$filename = "picupload_search.php";

$loggedin = false;
if ($wbbuserdata['userid'] != "0" && inarray($erlaubtegruppen, $wbbuserdata['groupids'])) {
	$loggedin = true;
}

function getQueryHash($postIDs, $showPosts, $sortBy, $sortOrder, $userID, $ipAddress) {
	return md5($postIDs . "\n" . $showPosts . "\n" . $sortBy . "\n" . $sortOrder . "\n" . $userID . "\n" . $ipAddress);
}

if ($loggedin) {

	if (isset($_GET['userid']) && trim($_GET['userid']) != '') {
		//username suchen
		$sql_query = "SELECT p.postid FROM bb1_posts p, bb1_threads t WHERE p.threadid=t.threadid AND p.visible=1 AND p.userid = " . intval($_GET['userid']) . " AND t.boardid = " . $fotoalben_board_id . ";";
		$result = $db->unbuffered_query($sql_query);

		$savepostids = '';
		while ($row = $db->fetch_array($result)) {
			$savepostids .= ',' . $row['postid'];
		}

		if (!$savepostids) {
			redirect($lang->get("LANG_GLOBAL_ERROR_SEARCHNORESULT"), "search.php" . $SID_ARG_1ST);
		}

		if (isset($_POST['onlystarter']) && $_POST['onlystarter'] == 1) {
			$_POST['showposts'] = 0;
		}

		$result = $db->query_first("SELECT searchid FROM bb" . $n . "_searchs WHERE searchhash = '" . getQueryHash($savepostids, 0, "lastpost", "desc", $wbbuserdata['userid'], $REMOTE_ADDR) . "'");
		if ($result['searchid']) {
			header("Location: search.php?searchid=" . $result['searchid'] . $SID_ARG_2ND_UN);
			exit();
		}

		$db->query("INSERT INTO bb" . $n . "_searchs (searchhash,searchstring,searchuserid,postids,showposts,sortby,sortorder,searchtime,userid,ipaddress)
				VALUES ('" . getQueryHash($savepostids, 0, "lastpost", "desc", $wbbuserdata['userid'], $REMOTE_ADDR) . "'") . "','','" . ((!strstr($userids, ',')) ? (intval($userids)) : (0)) . "','$savepostids{','0','lastpost','desc','" . time() . "','{$wbbuserdata['userid']}','$REMOTE_ADDR')");
		$searchid = $db->insert_id();

		header("Location: search.php?searchid=$searchid" . $SID_ARG_2ND_UN);
		exit();
	}

} else {
	echo "<meta http-equiv='refresh' content='0,index.php' />";
}
