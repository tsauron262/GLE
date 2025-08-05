<?php
//        $pulls = file_get_contents(PATH_TMP.'/git_logs_commit/logs.logs');
require_once("../main.inc.php");
	$sql = "DELETE FROM llx_bimpcore_file
       				WHERE parent_module = 'bimptask'
       				  AND parent_object_name = 'BIMP_Task'
       				  AND id_parent  = 98033
       				  AND file_name LIKE '%_deleted'";
$db->query($sql);

die('llllicicicicicicic');
