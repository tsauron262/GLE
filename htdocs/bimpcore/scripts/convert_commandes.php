<?php

define('NOLOGIN', '1');

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

//llxHeader();

echo '<!DOCTYPE html>';
echo '<html lang="fr">';

echo '<head>';
//echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/bimpcore/views/css/ticket.css' . '"/>';
echo '<script src="/test2/includes/jquery/js/jquery.min.js?version=6.0.4" type="text/javascript"></script>';
echo '</head>';

echo '<body>';

global $db;
$bdb = new BimpDb($db);

$sql = 'SELECT DISTINCT r.id_commande_client as id FROM ' . MAIN_DB_PREFIX . 'br_reservation r';
$sql .= ' WHERE r.id_commande_client > 0 AND r.id_commande_client NOT IN (SELECT DISTINCT ol.id_commande FROM ' . MAIN_DB_PREFIX . 'br_order_line ol)';

$result = $bdb->executeS($sql, 'array');

$commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
BimpObject::loadClass('bimpreservation', 'BR_OrderLine');

if (!is_null($result)) {
    foreach ($result as $r) {
        if ((int) $r['id']) {
            echo '*** Commande ' . $r['id'] . ' *** <br/>';
            if ($commande->fetch((int) $r['id'])) {
                foreach ($commande->dol_object->lines as $line) {
                    if (isset($line->fk_product) && $line->fk_product) {
                        echo '    - Ligne ' . $line->id . ': ';
                        $product_type = (int) $bdb->getValue('product', 'fk_product_type', '`rowid` = ' . (int) $line->fk_product);
                        $type = ((int) $product_type === 0 ? BR_OrderLine::PRODUIT : BR_OrderLine::SERVICE);
                        $qty = (int) $line->qty;
                        $qty_shipped = 0;
                        switch ($type) {
                            case BR_OrderLine::PRODUIT:
                                $sql = 'SELECT SUM(`qty`) as qty FROM ' . MAIN_DB_PREFIX . 'br_reservation_shipment WHERE `id_commande_client` = ' . (int) $commande->id . ' AND `id_commande_client_line` = ' . (int) $line->id;
                                $res = $bdb->executeS($sql, 'array');
                                if (isset($res[0]['qty'])) {
                                    $qty_shipped = (int) $res[0]['qty'];
                                }
                                break;

                            case BR_OrderLine::SERVICE:
                                $qty_shipped = (int) $bdb->getValue('br_service', 'shipped', '`id_commande_client` = ' . (int) $commande->id . ' AND `id_commande_client_line` = ' . (int) $line->id);
                                break;
                        }

                        $data = array(
                            'id_commande'   => (int) $commande->id,
                            'id_order_line' => (int) $line->id,
                            'id_product'    => (int) $line->fk_product,
                            'type'          => (int) $type,
                            'qty'           => (int) $qty,
                            'qty_shipped'   => (int) $qty_shipped
                        );

                        $id_order_line = (int) $bdb->insert('br_order_line', $data, true);
                        if (!$id_order_line) {
                            echo '[FAIL INSERT ORDER LINE] ' . $bdb->db->lasterror();
                        } else {
                            if ($type === BR_OrderLine::SERVICE) {
                                if ($bdb->update('br_service_shipment', array(
                                            'id_br_order_line' => (int) $id_order_line
                                                ), '`id_commande_client` = ' . (int) $commande->id . ' AND `id_commande_client_line` = ' . (int) $line->id) <= 0) {
                                    echo '[FAIL UPDATE SERVICE_SHIPMENT] - ' . $bdb->db->lasterror();
                                } else {
                                    echo 'OK';
                                }
                            } else {
                                echo 'Ok';
                            }
                        }

                        echo '<br/>';
                    }
                }
                $commande->dol_object->fetchObjectLinked();
                if (isset($commande->dol_object->linkedObjects['facture']) && count($commande->dol_object->linkedObjects['facture']) === 1) {
                    foreach ($commande->dol_object->linkedObjects['facture'] as $facture) {
                        if (BimpObject::objectLoaded($facture)) {
                            echo ' - Insertion ID Facture ' . $facture->id . ': ';
                            $errors = $commande->updateField('id_facture', $facture->id);
                            if (count($errors)) {
                                echo '[FAIL] - ' . BimpTools::getMsgFromArray($errors);
                            } else {
                                echo 'OK';
                            }
                            echo '<br/>';
                            break;
                        }
                    }
                }
            } else {
                echo 'COMMANDE NON TROUVEE';
            }

            echo '<br/><br/>';
        }
    }
}

echo '</body></html>';

//llxFooter();
