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

$where = '`id_propal` > 0 AND `id` < 233';

$rows = $bdb->getRows('bs_sav', $where, null, 'array', array(
    'id', 'id_propal'
        ));

if (!is_null($rows) && count($rows)) {
    $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV');
    BimpObject::loadClass('bimpsupport', 'BS_SavPropalLine');
    foreach ($rows as $r) {
        if ((int) $r['id'] && $sav->fetch((int) $r['id'])) {
            echo 'SAV ' . $r['id'];
            
            $asso = new BimpAssociation($sav, 'propales');
            $asso->addObjectAssociation((int) $r['id_propal']);

            $lines = $bdb->getRows('propaldet', 'fk_propal = ' . (int) $r['id_propal'], null, 'array');
            $sav_products = $bdb->getRows('bs_sav_product', '`id_sav` = ' . (int) $sav->id, null, 'array');
            $apple_parts = $bdb->getRows('bs_apple_part', '`id_sav` = ' . (int) $sav->id, null, 'array');
            $remain_lines = array();
            $id_sav_product = 0;

            if (is_null($lines)) {
                continue;
            }

            foreach ($lines as $line) {
                $data = array(
                    'id_obj'             => (int) $r['id_propal'],
                    'id_line'            => (int) $line['rowid'],
                    'type'               => 0,
                    'id_equipment'       => 0,
                    'deletable'          => 0,
                    'editable'           => 0,
                    'linked_id_object'   => 0,
                    'linked_object_name' => '',
                    'id_reservation'     => 0,
                    'out_of_warranty'    => 1,
                    'position'           => (int) $line['rang']
                );
                $insert = false;
                if ((string) $line['description']) {
                    if ((int) $sav->getData('id_discount') && $line['description'] === 'Acompte') {
                        $data['type'] = BS_SavPropalLine::LINE_FREE;
                        $data['linked_object_name'] = 'sav_discount';
                        $data['linked_id_object'] = (int) $sav->getData('id_discount');
                        $insert = true;
                    } elseif (preg_match('/^Prise en charge.*$/', $line['description'])) {
                        $data['type'] = BS_SavPropalLine::LINE_TEXT;
                        $data['linked_object_name'] = 'sav_pc';
                        $data['linked_id_object'] = (int) $sav->id;
                        $insert = true;
                    } elseif (preg_match('/^Diagnostic :.*$/', $line['description'])) {
                        $data['type'] = BS_SavPropalLine::LINE_TEXT;
                        $data['linked_object_name'] = 'sav_diagnostic';
                        $data['linked_id_object'] = (int) $sav->id;
                        $insert = true;
                    } elseif ($line['description'] === $sav->getData('extra_infos')) {
                        $data['type'] = BS_SavPropalLine::LINE_TEXT;
                        $data['linked_object_name'] = 'sav_extra_infos';
                        $data['linked_id_object'] = (int) $sav->id;
                        $insert = true;
                    } elseif (preg_match('/^Garantie.*$/', $line['description'])) {
                        $data['type'] = BS_SavPropalLine::LINE_FREE;
                        $data['linked_object_name'] = 'sav_garantie';
                        $data['linked_id_object'] = (int) $sav->id;
                        $insert = true;
                    }
                }
                if (!$insert) {
                    if ((int) $line['fk_product']) {
                        if ((int) $line['fk_product'] === BS_SAV::$idProdPrio) {
                            $data['type'] = BS_SavPropalLine::LINE_PRODUCT;
                            $data['linked_object_name'] = 'sav_prioritaire';
                            $data['linked_id_object'] = (int) $sav->id;
                            $insert = true;
                        } else {
                            if (!is_null($sav_products)) {
                                foreach ($sav_products as $idx => $sp) {
                                    if ((int) $sp['id_product'] === (int) $line['fk_product'] &&
                                            (float) $sp['qty'] === (float) $line['qty'] &&
                                            (float) $sp['remise'] === (float) $line['remise_percent']) {
                                        $data['type'] = BS_SavPropalLine::LINE_PRODUCT;
                                        $data['id_equipment'] = (int) $sp['id_equipment'];
                                        $data['out_of_warranty'] = (int) $sp['out_of_warranty'];
                                        $data['deletable'] = 1;
                                        $data['editable'] = 1;
                                        $insert = true;
                                        unset($sav_products[$idx]);
                                        $insert = true;
                                        $id_sav_product = (int) $sp['id'];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
                if (!$insert) {
                    if (!is_null($apple_parts)) {
                        foreach ($apple_parts as $idx => $part) {
                            $label = $part['part_number'] . ' - ' . $part['label'];
                            if (strpos($line['description'], $label) !== false) {
                                $data['type'] = BS_SavPropalLine::LINE_FREE;
                                $data['linked_object_name'] = 'sav_apple_part';
                                $data['linked_id_object'] = (int) $part['id'];
                                $data['out_of_warranty'] = (int) $part['out_of_warranty'];
                                unset($apple_parts[$idx]);
                                $insert = true;
                                break;
                            }
                        }
                    }
                }

                if ($insert) {
                    $id_new_line = (int) $bdb->insert('bs_sav_propal_line', $data, true);
                    if ($id_new_line <= 0) {
                        echo '[FAIL] - ' . $bdb->db->lasterror() . '<br/>';
                    } else {
                        if ($id_sav_product) {
                            if ($bdb->update('br_reservation', array(
                                        'id_sav_propal_line' => $id_new_line
                                            ), '`id_sav_product` = ' . $id_sav_product) <= 0) {
                                echo '[FAIL MAJ RESERVATION] - ' . $bdb->db->lasterror();
                            }
                        }
                    }
                } else {
                    $remain_lines[] = $line;
                }
            }

            foreach ($remain_lines as $line) {
                $data = array(
                    'id_obj'             => (int) $r['id_propal'],
                    'id_line'            => (int) $line['rowid'],
                    'type'               => BS_SavPropalLine::LINE_FREE,
                    'id_equipment'       => 0,
                    'deletable'          => 1,
                    'editable'           => 1,
                    'linked_id_object'   => 0,
                    'linked_object_name' => '',
                    'id_reservation'     => 0,
                    'out_of_warranty'    => 1,
                    'position'           => (int) $line['rang']
                );
                if ($bdb->insert('bs_sav_propal_line', $data) <= 0) {
                    echo '[FAIL] - ' . $bdb->db->lasterror() . '<br/>';
                }
            }
        } else {
            echo 'SAV d\'ID "' . $r['id'] . '" non trouvé';
        }

        echo '<br/><br/>';
    }
} else {
    echo 'AUCUN SAV A TRAITER TROUVE';
}

echo '</body></html>';

//llxFooter();
