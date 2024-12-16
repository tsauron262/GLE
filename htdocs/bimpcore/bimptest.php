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

//$sql = BimpTools::getSqlFullSelectQuery('contrat', array('a.rowid'), array(
//            'a.version'             => 2,
//            'a.fk_commercial_suivi' => array(
//                'operator' => '!=',
//                'value'    => '(SELECT ec.fk_socpeople FROM llx_element_contact ec WHERE ec.fk_c_type_contact = 11 AND ec.element_id = a.rowid ORDER BY ec.rowid DESC LIMIT 1'
//            )
//                ), array());
//
//$rows = $bdb->executeS($sql, 'array');
//
//foreach ($rows as $r) {
//    $c = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', (int) $r['rowid']);
//
//    if (BimpObject::objectLoaded($c)) {
//        $id_comm = $c->getCommercialId();
//        echo $c->id . ' : ' .  $c->getData('fk_commercial_suivi') . ' - ' . $id_comm .' => ';
//        
//        if ($bdb->update('contrat', array(
//            'fk_commercial_suivi' => $id_comm
//        ), 'rowid = ' . $r['rowid']) > 0) {
//            echo 'OK';
//        } else {
//            echo 'KO - ' . $bdb->err();
//        }
//        echo '<br/>';
//    }
//}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
