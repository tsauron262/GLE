<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS', 0, 0, array(), array());

echo '<body style="padding: 30px">';

BimpCore::displayHeaderFiles();

global $db, $user;
$bdb = new BimpDb($db);

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

echo 'TEST OK';
//$sql = 'SELECT s.code_client FROM llx_societe s WHERE s.client != 0 AND (s.outstanding_limit_credit_check > 0 OR s.outstanding_limit_atradius > 0) 
//AND (SELECT COUNT(DISTINCT c.rowid) FROM llx_commande c WHERE c.fk_soc = s.rowid AND c.date_creation >= \'2024-04-01 00:00:00\') = 0
//AND (SELECT COUNT(DISTINCT p.rowid) FROM llx_propal p WHERE p.fk_soc = s.rowid AND p.datec >= \'2024-04-01 00:00:00\') = 0;';
//
//$rows = $bdb->executeS($sql, 'array');
//
//echo count($rows) . ' clients.<br/><br/>';
//
//foreach ($rows as $r) {
//    echo $r['code_client'] . '<br/>';
//}


echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
