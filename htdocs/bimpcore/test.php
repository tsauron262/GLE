<?php

require_once("../main.inc.php");

	$sql = "SELECT * FROM llx_bs_sav_propal_line LIMIT 2";
		$res = $db->query($sql);
	while($row = $db->fetch_object($res)){
		echo '<pre>' . print_r($row, true) . '</pre>';
		 // exit();
	}
//
////        $pulls = file_get_contents(PATH_TMP.'/git_logs_commit/logs.logs');
//        die('llllicicicicicicic');
