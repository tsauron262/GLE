<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'CHECK COMMANDES', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

$commandes = BimpCache::getBimpObjectList('bimpcommercial', 'Bimp_Commande', array(
            'logistique_status' => 2
        ));

foreach ($commandes as $id_c) {
    $comm = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande', (int) $id_c);

    if (BimpObject::objectLoaded($comm)) {
        $comm->checkLogistiqueStatus();
    }

    unset($comm);
    BimpCache::$cache = array();
}

//$where = '`fk_statut` IN (1,2,3) AND `logistique_status` != 6';
//
//$rows = $bdb->getRows('commande', $where, null, 'array', array('rowid', 'fk_statut', 'logistique_status', 'shipment_status', 'invoice_status'));
//
//if (is_array($rows)) {
//    foreach ($rows as $r) {
//        if (in_array((int) $r['logistique_status'], array(3, 5, 6)) &&
//                (int) $r['shipment_status'] === 2 && (int) $r['invoice_status'] === 2) {
//            if (in_array((int) $r['fk_statut'], array(1, 2))) {
//                $bdb->update('commande', array(
//                    'fk_statut' => 3
//                        ), '`rowid` = ' . (int) $r['rowid']);
//            }
//        } elseif ((int) $r['fk_statut'] === 3) {
//            $bdb->update('commande', array(
//                'fk_statut' => 1
//                    ), '`rowid` = ' . (int) $r['rowid']);
//        }
//    }
//}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();

