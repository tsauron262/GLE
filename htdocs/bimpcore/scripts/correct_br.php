<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

top_htmlhead('', 'BIMPCORE TEST', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

set_time_limit(1200);
$id = BimpTools::getValue('id_comm', 0);

//if (!$id) {
//    die('id_comm absent');
//}

$receptions = BimpCache::getBimpObjectObjects('bimplogistique', 'BL_CommandeFournReception', array());
foreach ($receptions as $reception) {
    $reception->onLinesChange();
}
die('ok');

$sql = 'SELECT l.id, det.fk_product FROM ' . MAIN_DB_PREFIX . 'bimp_commande_fourn_line l';
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur c ON c.rowid = l.id_obj';
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseurdet det ON det.rowid = l.id_line';
$sql .= ' WHERE l.type = 1 AND c.fk_statut > 2 AND c.ref LIKE \'CFOLD8%\' AND det.fk_product > 0';

if ($id) {
    $sql .= ' AND c.rowid = ' . $id;
}

$rows = $bdb->executeS($sql, 'array');

if (!is_null($rows)) {
    echo 'n1: ' . count($rows) .'<br/>';
    $lines = array();

    $nOk = 0;
    $nFail = 0;
    foreach ($rows as $r) {
        $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', (int) $r['id']);
        /* tocomment */
//        $line = new Bimp_CommandeFournLine();
//        die('Fichier "' . basename(__FILE__) . '", ligne ' . __LINE__ . ' instanciation directe à commenter');

        if (BimpObject::objectLoaded($line)) {
            $commande = $line->getParentInstance();

            if (!BimpObject::objectLoaded($commande)) {
                echo 'ERREUR PAS DE COMM POUR LIGNE #' . $line->id . '<br/>';
                continue;
            }

            $product = $line->getProduct();

            if (!BimpObject::objectLoaded($product)) {
                echo 'pas de prod <br/>';
                continue;
            }

            if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                $line_qty = 0;
                $full_qty = (float) $line->getFullQty();
                $new_receptions = array();

                $receptions = $line->getData('receptions');
                foreach ($receptions as $id_reception => $reception_data) {
                    $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_reception);
                    /* tocomment */
//                    $reception = new BL_CommandeFournReception();
//                    die('Fichier "' . basename(__FILE__) . '", ligne ' . __LINE__ . ': instanciation directe à commenter');

                    if (BimpObject::objectLoaded($reception)) {
                        if ((int) $reception->getData('status') === 1) {
                            if (empty($reception_data['equipments']) && !empty($reception_data['qties'])) {
                                echo 'Traitement ligne #' . $line->id . ': ';
                                if ($bdb->update('bimp_commande_fourn_line', array(
                                            'type' => 3
                                                ), '`id` = ' . (int) $line->id) <= 0) {
                                    echo 'FAIL - ' . $bdb->db->lasterror();
                                    $nFail++;
                                } elseif ($bdb->update('commande_fournisseurdet', array(
                                            'fk_product' => 0,
                                            'description'       => 'Ref: ' . $product->getRef() . ' - ' . $product->getData('label')
                                                ), '`rowid` = ' . (int) $line->getData('id_line')) <= 0) {
                                    echo 'FAIL DET ' . $bdb->db->lasterror();
                                    $nFail++;
                                } else {
                                    $nOk++;
                                    echo 'OK';
                                }
                                $line_qty += (float) $reception_data['qty'];
                                $reception->onLinesChange();
                                echo '<br/>';
                                continue;
                            }
                        }
                    }
                    $new_receptions[(int) $id_reception] = $reception_data;
                    unset($receptions[(int) $id_reception]);
                }

                $remain_qty = $full_qty - $line_qty;
                
                if ($remain_qty && (float) $remain_qty !== (float) $full_qty) {
                    // Maj line: 
                    if ($bdb->update('commande_fournisseurdet', array(
                                'qty' => $line_qty
                            ), '`rowid` = '.(int) $line->getData('id_line')) <= 0) {
                        echo 'FAIL UP QTY <br/> - ' . $bdb->db->lasterror();
                    }
                    $err = $line->updateField('receptions', $receptions);
                    $err = $line->updateField('qty_modif', 0);
                    if (count($err)) {
                        echo 'FAIL UP RECEPTIONS <pre>';
                        print_r($err);
                        echo '</pre>';
                    }
                    $line->checkQties();

                    // Création d'une nouvelle ligne de $remain_qty et attribution des new réceptions. 
                    echo 'Créa new line pour line #' . $line->id . ' (' . $remain_qty . '): ';
                    
                    $newLine = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');
                    $newLine->validateArray(array(
                        'id_obj'     => (int) $line->getdata('id_obj'),
                        'type'       => ObjectLine::LINE_PRODUCT,
                        'receptions' => $new_receptions
                    ));
                    $newLine->id_product = (int) $line->id_product;
                    $newLine->pu_ht = $line->pu_ht;
                    $newLine->tva_tx = (float) $line->tva_tx;
                    $newLine->pa_ht = $line->pa_ht;
                    $newLine->qty = $remain_qty;

                    $w = array();
                    $err = $newLine->create($w, true);
                    if (count($err)) {
                        echo '<pre>';
                        print_r($err);
                        echo '</pre>';
                    } else {
                        $newLine->checkQties();
                        echo 'OK';
                        echo '<br/>';
                    }
                    if (count($w)) {
                        echo '<pre>';
                        print_r($w);
                        echo '</pre>';
                    }
                    $commande->checkReceptionStatus();
                    $commande->checkInvoiceStatus();
                }
            }
        }
    }

    echo '<br/>';
    echo 'OK: ' . $nOk . '<br/>';
    echo 'FAILS: ' . $nFail . ' <br/>';
} else {
    echo 'DB FAIL';
}


echo '</body></html>';

//llxFooter();
