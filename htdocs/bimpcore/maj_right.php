<?php
	require_once '../main.inc.php';

	if(!BimpCore::isUserDev())
		exit('zone reservé aux dev');

	else {
		$module = BimpTools::getPostFieldValue('module');
		$file = DOL_DOCUMENT_ROOT . $module.'/core/modules/mod'.ucfirst($module).'.class.php';
		if(!file_exists($file)){
			exit('module '.$module.' not found');
		}
		require_once $file;
		$class_name = 'mod'.ucfirst($module);
		$obj = new $class_name($db); // modBimpsupport

		$nomModule = $obj->rights_class;

		foreach ($obj->rights as $right) {
//			echo '<pre>' . print_r($right, true) . '</pre>';
			$sql = "SELECT * FROM ".MAIN_DB_PREFIX."rights_def WHERE id=" . $right[0];
			$req = $db->query($sql);
			$type = $right[2]?:'w';

			if (!$req->num_rows) {
				// les Admins
				$users = array();
				$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."user WHERE admin = 1";
				$req = $db->query($sql);
				if ($req->num_rows) {
					while ($u = $db->fetch_array ($req)) {
						$users[] = $u;
					}
				}

				$sql = "SELECT entity FROM ".MAIN_DB_PREFIX."const WHERE name = 'MAIN_MODULE_" . strtoupper($module) . "'";
				$req = $db->query($sql);
				if ($req->num_rows) {
					while ($row = $db->fetch_array ($req)) {
						$entity = $row['entity'];
//						$entities[] = $entity;
						$sql = "INSERT INTO " . MAIN_DB_PREFIX . "rights_def (id, libelle, module, entity, perms, type, enabled)
						 	VALUES (" . $right[0] . ", '" . addslashes($right[1]) . "', '" . $nomModule . "', '" . $entity . "', '" . $right[4] . "', '" . $type . "', 1)";
						$db->query($sql);

						foreach ($users as $u) {
							$sql = "INSERT INTO" . MAIN_DB_PREFIX . "user_rights (entity, fk_user, fk_right) VALUES (" . $entity . ", " . $u['rowid'] . ", " . $right[0] . ")";
							$db->query($sql);
						}
					}
				}
			}
		}

		header("Location: index.php?fc=dev&tab=modules_conf");
		exit();
	}
