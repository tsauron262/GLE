<?php
//        $pulls = file_get_contents(PATH_TMP.'/git_logs_commit/logs.logs');
require_once("../main.inc.php");
$X = 98033;
	$sql = "DELETE FROM llx_bimpcore_file
       				WHERE parent_module = 'bimptask'
       				  AND parent_object_name = 'BIMP_Task'
       				  AND id_parent  = " .$X. "
       				  AND file_name LIKE '%_deleted'";
//	exit($sql);
var_dump($db->query($sql));

$task = BimpCache::getBimpObjectInstance('bimptask', 'BIMP_Task', $X);

var_dump($task->getFilesDir());


echo '<pre>' . print_r(scandir($task->getFilesDir()), true) . '</pre>';
$files = scandir($task->getFilesDir());
foreach ($files as $file) {
	if ($file != "." && $file != "..") {
		if (is_file($task->getFilesDir().$file)) {
			if(strpos($file, '_deleted') !== false)
				unlink($task->getFilesDir().$file);
		}
	}
}
// exit();
die('llllicicicicicicic');
