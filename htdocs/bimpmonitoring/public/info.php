<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
$echo = true;
$echo = ($_SERVER['PHP_SELF'] == $_SERVER['REQUEST_URI']);
if($echo) {
	header('Content-Type: application/json');
	define('NOLOGIN', 1);
	require_once('../../main.inc.php');
}
$array = array(
	'status' => 1,
);

global $db;
$sql = $db->query('SELECT MAX(datelastlogin) as max FROM '.MAIN_DB_PREFIX.'user WHERE login NOT IN ("admin", "t.sauron");');
if($db->num_rows($sql) > 0){
	$ln = $db->fetch_object($sql);
	$array['lastlogin'] = $ln->max;
}

$array['check_versions_lock'] = BimpCore::getConf('check_versions_lock');



if($echo)
	echo json_encode($array);
else
	$return = $array;
