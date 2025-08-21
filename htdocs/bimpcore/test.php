<?php
//        $pulls = file_get_contents(PATH_TMP.'/git_logs_commit/logs.logs');
require_once("../main.inc.php");

$sql = "SELECT entity FROM ".MAIN_DB_PREFIX."const WHERE name = 'MAIN_MODULE_BIMPSUPPORT'";
$req = $db->query($sql);
$e = array();
while ($data = $req->fetch_object()) {
	$e[] = $data->entity;
}


$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."user WHERE admin = 1";
$req = $db->query($sql);
if ($req->num_rows) {
	while ($u = $db->fetch_array ($req)) {
		foreach ($e as $entity) {
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'user_rights (entity, fk_user, fk_id) VALUES(' .$entity. ', '.$u['rowid'].', 754392)';
			$db->query($sql);
		}
	}
}


