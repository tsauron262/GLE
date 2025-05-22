<?php

require_once("../main.inc.php");

require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'INSTALL RDC', 0, 0, array(), array());

echo '<body style="padding: 30px">';

BimpCore::displayHeaderFiles();

global $db, $user;
$bdd = new BimpDb($db);

if (!BimpObject::objectLoaded($user)) {
	echo BimpRender::renderAlerts('Aucun utilisateur connecté');
	exit;
}

if (!$user->admin) {
	echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
	exit;
}

ini_set('display_errors', 1);
$errors = array();

exit('Ce script est obsolète, il n\'est plus nécessaire de le lancer');

$societes = array_column($bdd->getRows('societe', 'import_key IS NOT NULL', null, 'array', array('rowid')), 'rowid');
$errors['societe_atradius'] = $bdd->delete('societe_atradius' , 'id_soc IN (' . implode(', ', $societes) . ')');
$errors['societe_extrafields'] = $bdd->delete('societe_extrafields' , 'fk_object IN (' . implode(', ', $societes) . ')');
$errors['ca_rdc'] = $bdd->delete('ca_rdc' , 'id_obj IN (' . implode(', ', $societes) . ')');
$errors['concurrence_rdc'] = $bdd->delete('concurrence_rdc' , 'fk_soc IN (' . implode(', ', $societes) . ')');
$errors['actioncomm'] = $bdd->delete('actioncomm' , 'fk_soc IN (' . implode(', ', $societes) . ')');
$errors['ticket'] = $bdd->delete('ticket', 'fk_soc IN (' . implode(', ', $societes) . ')');

$contacts = array_column($bdd->getRows('socpeople', 'import_key IS NOT NULL', null, 'array', array('rowid')), 'rowid');
$errors['element_contact'] = $bdd->delete('element_contact' , 'fk_socpeople IN (' . implode(', ', $contacts) . ')');
$errors['actioncomm'] = $bdd->delete('actioncomm' , 'fk_contact IN (' . implode(', ', $societes) . ')');

$tickets = array_column($bdd->getRows('ticket', 'import_key IS NOT NULL', null, 'array', array('rowid')), 'rowid');
$errors['element_contact'] = $bdd->delete('element_contact', 'element_id IN (' . implode(', ', $tickets) . ')');
$errors['actioncomm'] = $bdd->delete('actioncomm', 'fk_element IN (' . implode(', ', $tickets) . ') AND elementtype = "ticket"');
$errors['bimpcore_note'] = $bdd->delete('bimpcore_note', 'obj_module = \'bimpticket\' AND id_obj IN (' . implode(', ', $tickets) . ')');
$errors['bimpcore_object_log'] = $bdd->delete('bimpcore_object_log', 'obj_module = \'bimpticket\' AND id_object IN (' . implode(', ', $tickets) . ')');
$errors['bimpcore_mail'] = $bdd->delete('bimpcore_mail', 'obj_module = \'bimpticket\' AND id_obj IN (' . implode(', ', $tickets) . ')');
$errors['ecm_files'] = $bdd->delete('ecm_files', 'src_object_type = \'ticket\' AND src_object_id IN (' . implode(', ', $tickets) . ')');
$errors['bimpcore_file'] = $bdd->delete('bimpcore_file', 'parent_module = \'bimpticket\' AND id_parent IN (' . implode(', ', $tickets) . ')');

$errors['fin']['ticket'] = $bdd->delete('ticket', 'rowid IN (' . implode(', ', $tickets) . ')');
$errors['fin']['socpeople'] = $bdd->delete('socpeople' , 'import_key IS NOT NULL');
$errors['fin']['societe'] = $bdd->delete('societe' , 'import_key IS NOT NULL');
$errors['fin']['AC_TICKET_'] = $bdd->delete('actioncomm', 'elementtype = "ticket" AND fk_soc IN (' . implode(', ', $societes) . ')');



echo 'Erreurs : <pre>' . print_r($errors, 1) . '</pre>';

echo '<br/>FIN';
echo '</body></html>';
