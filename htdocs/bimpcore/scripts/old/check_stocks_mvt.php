<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'CHECK MVTS STOCKS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
}

$bdb = new BimpDb($db);

// Articles ventes: 
//$codes = $bdb->getRows('stock_mouvement', 'inventorycode LIKE \'VENTE%_ART%\'', null, 'array', array('inventorycode'));
//
//echo $bdb->db->lasterror();
//
//echo '<pre>';
//print_r($codes);
//exit;
//
//$sql = 'SELECT va.id as id_art, v.id as id_vente FROM llx_bc_vente_article va';
//$sql .= ' LEFT JOIN llx_bc_vente v ON v.id = va.id_vente';
//$sql .= ' WHERE v.status = 2';
//$sql .= ' AND (SELECT COUNT(sm.rowid) FROM llx_stock_mouvement sm WHERE CONCAT(\'VENTE\', va.id_vente, \'_ART\', va.id) = sm.inventorycode) = 0';
//
//$rows = $bdb->executeS($sql, 'array');
//
//foreach ($rows as $r) {
//    $mvt = $bdb->getRow('stock_mouvement', 'inventorycode LIKE \'VENTE' . $r['id_vente'] . '_ART' . $r['id_art'] . '\'');
//
//    if (is_null($mvt)) {
//        echo 'MOUVEMENT absent pour ART #' . $r['id_art'] . ' - VENTE #' . $r['id_vente'] . '<br/>';
//    }
//}
// Expés commandes: 

$rows = $bdb->getRows('stock_mouvement', 'inventorycode LIKE \'CO%_EXP%\'', null, 'array', array('inventorycode'));
$codes = array();
foreach ($rows as $r) {
    $codes[] = $r['inventorycode'];
}

$sql = 'SELECT s.id as ids, c.rowid as idc FROM llx_bl_commande_shipment s';
$sql .= ' LEFT JOIN llx_commande c ON c.rowid = s.id_commande_client';
$sql .= ' WHERE s.status > 1';
//$sql .= ' AND (SELECT COUNT(sm.rowid) FROM llx_stock_mouvement sm WHERE CONCAT(\'VENTE\', va.id_vente, \'_ART\', va.id) = sm.inventorycode) = 0';

$rows = $bdb->executeS($sql, 'array');

$txt = '';

$n = 0;
$ok = 0;

foreach ($rows as $r) {
    if (!in_array('CO' . $r['idc'] . '_EXP' . $r['ids'], $codes)) {
        $comm = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $r['idc']);
        $nProds = 0;
        $nEqs = 0;

        if (BimpObject::objectLoaded($comm)) {
            $lines = $comm->getLines('product');

            foreach ($lines as $line) {
                $prod = $line->getProduct();
                if (BimpObject::objectLoaded($prod)) {
                    if ($prod->isTypeProduct()) {
                        $qty = (float) $line->getShippedQty((int) $r['ids']);
                        if ($qty) {
                            if ($prod->isSerialisable()) {
                                $nEqs += $qty;
                            } else {
                                $nProds += $qty;
                            }
                        }
                    }
                }
            }
        }

        BimpCache::$cache = array();
        
        if ($nProds || $nEqs) {
            $n++;
            $txt .= 'MVT ABSENT pour comm #' . $r['idc'] . ' - EXP #' . $r['ids'] . ' (' . $nProds . ' prods - ' . $nEqs . ' équipements)<br/>';
            continue;
        }
    }
    $ok++;
}

echo 'OK: ' . $ok . '<br/><br/>';
echo 'NB ERREURS: ' . $n . '<br/><br/>';
echo $txt;
echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
