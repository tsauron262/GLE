<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'CORRECT STOCKS MVTS ORIGIN', 0, 0, array(), array());

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

$where = '';
//$where = '(`fk_origin` IS NULL OR `fk_origin` = 0)';
//$where .= ' AND `inventorycode` != \'\' AND `inventorycode` IS NOT NULL';
//$where .= ' AND (';
//$where .= ' OR (`inventorycode` LIKE \'inventory-id-%\' AND (origintype != \'\' OR bimp_origin != \'inventory\'))';
//$where .= ' OR (`inventorycode` LIKE \'CO%_EXP%\' AND (origintype != \'commande\' OR bimp_origin != \'commande\'))';
//$where .= ' OR `inventorycode` LIKE \'CMDF%\'';
//$where .= ' OR `inventorycode` LIKE \'ANNUL_CMDF%\'';
//$where .= ' OR (`inventorycode` LIKE \'VENTE%\' AND (origintype != \'\' OR bimp_origin != \'vente_caisse\'))';
//$where .= ' OR (`inventorycode` LIKE \'TR%\' AND (origintype != \'\' OR bimp_origin != \'transfert\'))';
//$where .= ' OR ((`label` LIKE \'SAV%\' OR `label` LIKE \'Vente SAV%\') AND (origintype != \'\' OR bimp_origin != \'sav\'))';
//$where .= ' OR ((`inventorycode` LIKE \'PACKAGE%_ADD\' OR `inventorycode` LIKE \'PACKAGE%_REMOVE\' OR `inventorycode` LIKE \'AJOUT PACKAGE %\') AND (origintype != \'\' OR bimp_origin != \'package\'))';
$where .= ' (`inventorycode` LIKE \'PRET%\' AND (origintype != \'\' OR bimp_origin != \'pret\'))';

//$where .= ' AND rowid = 48863';
//$where .= ')';

$rows = $bdb->getRows('stock_mouvement', $where, null, 'array', null, 'rowid', 'desc');

if (!(int) BimPTools::getValue('exec', 0)) {
    echo 'Corrige l\'origine des mouvements de stock<br/><br/>';

    if (is_array($rows) && count($rows)) {
        echo count($rows) . ' élément(s) à traiter <br/><br/>';
        $path = pathinfo(__FILE__);
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1" class="btn btn-default">';
        echo 'Lancer';
        echo '</a>';
        echo ' <a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1&test=1" class="btn btn-default">';
        echo 'TEST';
        echo '</a>';
        echo ' <a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1&test_one=1" class="btn btn-default">';
        echo 'Exec one';
        echo '</a>';
        exit;
    }

    echo BimpRender::renderAlerts('Aucun élément à traiter', 'info');
}

// Corps du script
// Params: 
$test = (int) BimpTools::getValue('test', 0);
$test_one = (int) BimpTools::getValue('test_one', 0);
$details = (int) BimpTools::getValue('details', 0);

foreach ($rows as $r) {
    $code = $r['inventorycode'];
    $label = $r['label'];
    $dol_origin = '';
    $dol_id_origin = 0;
    $bimp_origin = '';
    $bimp_id_origin = 0;
    $id_package = 0;

//    if (preg_match('/^inventory\-id\-(\d+)$/', $code, $matches)) {
//        $dol_origin = '';
//        $dol_id_origin = 0;
//        $bimp_origin = 'inventory';
//        $bimp_id_origin = (int) $matches[1];
//    } elseif (preg_match('/^CO(\d+)_EXP(\d+)(_ANNUL)?$/', $code, $matches)) {
//        $dol_origin = 'commande';
//        $dol_id_origin = (int) $matches[1];
//        $bimp_origin = 'commande';
//        $bimp_id_origin = (int) $matches[1];
//    } elseif (preg_match('/^(ANNUL_)?CMDF(\d+)_LN(\d+)_RECEP(\d+)$/', $code, $matches)) {
//        $origin = 'order_supplier';
//        $id_origin = (int) $matches[2];
//    }
//    elseif (preg_match('/^VENTE(\d+)_(ART|RET)(\d+)$/', $code, $matches)) {
//        $bimp_origin = 'vente_caisse';
//        $bimp_id_origin = (int) $matches[1];
//    } elseif (preg_match('/^TR.*$/', $code)) {
//        if (preg_match('/^TR\-(\d+)$/', $code, $matches)) {
//            $bimp_origin = 'transfert';
//            $bimp_id_origin = (int) $matches[1];
//
//            if (preg_match('/^.*Produit "(.+)" \- serial: "(.+)".*$/', $label, $matches2)) {
//                $ref_prod = $matches2[1];
//                $serial = $matches2[2];
//                $id_line = 0;
//
//                $id_prod = (int) $bdb->getValue('product', 'rowid', 'ref = \'' . $ref_prod . '\'');
//                if ($id_prod) {
//                    $id_equipment = (int) $bdb->getValue('be_equipment', 'id', 'serial = \'' . $serial . '\' AND id_product = ' . $id_prod);
//
//                    if ($id_equipment) {
//                        $id_line = $bdb->getValue('bt_transfer_det', 'id', 'id_transfer = ' . $bimp_id_origin . ' AND id_product = ' . $id_prod . ' AND id_equipment = ' . $id_equipment);
//
//                        if ($id_line) {
//                            $code = 'TR' . $bimp_id_origin . '_LN' . $id_line . '_EQ' . $id_equipment;
//                        }
//                    }
//                }
//
//                if (!$id_line) {
//                    $code = 'TR' . $bimp_id_origin;
//                }
//            } elseif (preg_match('/^.*\-ref:(.+)$/', $label, $matches2)) {
//                $id_prod = (int) $bdb->getValue('product', 'rowid', 'ref = \'' . $matches2[1] . '\'');
//                $id_line = 0;
//
//                if ($id_prod) {
//                    $id_line = (int) $bdb->getValue('bt_transfer_det', 'id', 'id_transfer = ' . $bimp_id_origin . ' AND id_product = ' . $id_prod);
//                }
//
//                if ($id_line) {
//                    $code = 'TR' . $bimp_id_origin . '_LN' . $id_line;
//                } else {
//                    $code = 'TR' . $bimp_id_origin;
//                }
//            }
//        } elseif (preg_match('/^TR(\d+).*$/', $code, $matches)) {
//            $bimp_origin = 'transfert';
//            $bimp_id_origin = (int) $matches[1];
//        }
//    } elseif (preg_match('/^(SAV[A-Z0-9]+)$/', $label, $matches)) {
//        $ref_sav = $matches[1];
//        $id_sav = (int) $bdb->getValue('bs_sav', 'id', 'ref = \'' . $ref_sav . '\'');
//        if ($id_sav) {
//            $bimp_origin = 'sav';
//            $bimp_id_origin = $id_sav;
//            $code = 'SAV' . $id_sav;
//        } else {
//            continue;
//        }
//    } elseif (preg_match('/^Vente SAV.+$/', $label, $matches)) {
//        $id_sav = 0;
//        $id_propal_line = 0;
//        $id_eq = 0;
//
//        if (preg_match('/^.*serial: "(.+)".*$/', $label, $matches2)) {
//            $serial = $matches2[1];
//            $id_eq = (int) $bdb->getValue('be_equipment', 'id', '(serial = \'' . $serial . '\' OR concat("S", serial) = \'' . $serial . '\') AND id_product = ' . (int) $r['fk_product']);
//            if ($id_eq) {
//                $id_propal_line = (int) $bdb->getValue('object_line_equipment', 'id_object_line', 'object_type = \'sav_propal\' AND id_equipment = ' . (int) $id_eq);
//                if ($id_propal_line) {
//                    $id_propal = (int) $bdb->getValue('bs_sav_propal_line', 'id_obj', 'id = ' . (int) $id_propal_line);
//                    if ($id_propal) {
//                        $id_sav = (int) $bdb->getValue('bs_sav', 'id', 'id_propal = ' . (int) $id_propal);
//                    }
//                }
//            }
//        }
//        if ($id_sav) {
//            $code = 'SAV' . $id_sav;
//            if ($id_propal_line) {
//                $code .= '_LN' . $id_propal_line;
//                if ($id_eq) {
//                    $code .= '_EQ' . $id_eq;
//                }
//            }
//            $bimp_origin = 'sav';
//            $bimp_id_origin = $id_sav;
//        } else {
//            continue;
//        }
//    } elseif (preg_match('^(Ouverture du SAV|Restitution) (SAV[A-Z0-9]+)$', $label, $matches)) {
//        $ref_sav = $matches[2];
//        $id_sav = (int) $bdb->getValue('bs_sav', 'id', 'ref = \'' . $ref_sav . '\'');
//        if ($id_sav) {
//            $bimp_origin = 'sav';
//            $bimp_id_origin = $id_sav;
//            $code = 'SAV' . $id_sav;
//        } else {
//            continue;
//        }
//    } elseif (preg_match('/^PACKAGE(\d+)_(ADD|REMOVE)$/', $code, $matches)) {
//        $bimp_origin = 'package';
//        $bimp_id_origin = (int) $matches[1];
//
//        if ($matches[2] === 'ADD') {
//            $id_package = (int) $matches[1];
//        }
//    } elseif (preg_match('/^AJOUT PACKAGE (.+)$/', $code, $matches)) {
//        $id_package = (int) $bdb->getValue('be_package', 'id', 'ref = \'' . $matches[1] . '\'');
//
//        if ($id_package) {
//            $bimp_origin = 'package';
//            $bimp_id_origin = $id_package;
//            $code = 'PACKAGE' . $id_package . '_ADD';
//        }
//    }
    if (preg_match('/^PRET(\d+)_\d+$/', $code, $matches)) {
        $bimp_origin = 'pret';
        $bimp_id_origin = (int) $matches[1];
    }

//    if ($id_package && !preg_match('/Emplacement de destination/', $r['label'])) {
//        $sql = 'SELECT id FROM ' . MAIN_DB_PREFIX . 'be_package_place ';
//        $sql .= 'WHERE date <= \'' . $r['datem'] . '\' ORDER BY date DESC LIMIT 1';
//
//        $res = $bdb->executeS($sql, 'array');
//
//        if (!isset($res[0]['id'])) {
//            $sql = 'SELECT id FROM ' . MAIN_DB_PREFIX . 'be_package_place ';
//            $sql .= 'WHERE date > \'' . $r['datem'] . '\' ORDER BY date ASC LIMIT 1';
//            $res = $bdb->executeS($sql, 'array');
//        }
//
//        if (isset($res[0]['id']) && (int) $res[0]['id']) {
//            $place = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_PackagePlace', (int) $res[0]['id']);
//            if (BimpObject::objectLoaded($place)) {
//                $label .= ' - Emplacement de destination: ' . $place->getPlaceName();
//            }
//        }
//    }
    // UPDATES: 
    if ($dol_origin !== $r['origintype'] ||
            $dol_id_origin !== (int) $r['fk_origin'] ||
            $bimp_origin !== $r['bimp_origin'] ||
            $bimp_id_origin !== (int) $r['bimp_id_origin'] ||
            $code !== $r['inventorycode'] ||
            $label !== $r['label']) {
        if ($details || $test || $test_one) {
            echo 'Correction bimp origine mvt #' . $r['rowid'] . ' (Origine: ' . $bimp_origin . ' #' . $bimp_id_origin . ' - Code: ' . $code . ')<br/>';
        }
        if (!$test) {
            if ($bdb->update('stock_mouvement', array(
                        'origintype'     => $dol_origin,
                        'fk_origin'      => $dol_id_origin,
                        'bimp_origin'    => $bimp_origin,
                        'bimp_id_origin' => $bimp_id_origin,
                        'inventorycode'  => $code,
                        'label'          => $label
                            ), 'rowid = ' . (int) $r['rowid']) <= 0) {
                if (!$details && !$test && !$test_one) {
                    echo 'Correction bimp origine mvt #' . $r['rowid'] . ' (Origine: ' . $bimp_origin . ' #' . $bimp_id_origin . ' - Code: ' . $code . '): ';
                }
                echo '<span class="danger">';
                echo '[ECHEC] - ' . $bdb->db->lasterror();
                echo '</span><br/>';
            } else {
                $bdb->update('be_equipment_place', array(
                    'origin'    => $bimp_origin,
                    'id_origin' => $bimp_id_origin
                        ), 'code_mvt = \'' . $code . '\'');

                $bdb->update('be_package_place', array(
                    'origin'    => $bimp_origin,
                    'id_origin' => $bimp_id_origin
                        ), 'code_mvt = \'' . $code . '\'');
            }
        }

        if ($test_one) {
            break;
        }
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();