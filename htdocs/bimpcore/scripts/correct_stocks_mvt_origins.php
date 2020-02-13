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

//$where = '(`fk_origin` IS NULL OR `fk_origin` = 0)';
//$where .= ' AND `inventorycode` != \'\' AND `inventorycode` IS NOT NULL';
//$where .= ' AND (';

$where = '(`inventorycode` LIKE \'inventory-id-%\' AND (origintype != \'\' OR bimp_origin != \'inventory\'))';
//$where .= ' OR (`inventorycode` LIKE \'CO%_EXP%\' AND origintype != \'commande\'))';
//$where .= ' OR `inventorycode` LIKE \'CMDF%\'';
//$where .= ' OR `inventorycode` LIKE \'ANNUL_CMDF%\'';
//$where .= ' OR `inventorycode` LIKE \'VENTE%\'';
//$where .= ')';

$rows = $bdb->getRows('stock_mouvement', $where, null, 'array', array('rowid', 'inventorycode', 'origintype', 'fk_origin', 'bimp_origin', 'bimp_id_origin'), 'rowid', 'desc');

if (!(int) BimPTools::getValue('exec', 0)) {
    echo 'Corrige l\'origine des mouvements de stock<br/><br/>';

    if (is_array($rows) && count($rows)) {
        echo count($rows) . ' élément(s) à traiter <br/><br/>';
        $path = pathinfo(__FILE__);
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1" class="btn btn-default">';
        echo 'Lancer';
        echo '</a>';
        exit;
    }

    echo BimpRender::renderAlerts('Aucun élément à traiter', 'info');
}

// Corps du script

foreach ($rows as $r) {
    $code = $r['inventorycode'];

    $dol_origin = '';
    $dol_id_origin = 0;
    $bimp_origin = '';
    $bimp_id_origin = 0;

    if (preg_match('/^inventory\-id\-(\d+)$/', $code, $matches)) {
        $dol_origin = '';
        $dol_id_origin = 0;
        $bimp_origin = 'inventory';
        $bimp_id_origin = (int) $matches[1];
    }
//    elseif (preg_match('/^CO(\d+)_EXP(\d+)(_ANNUL)?$/', $code, $matches)) {
//        $dol_origin = 'commande';
//        $dol_id_origin = (int) $matches[1];
//        $bimp_origin = 'commande';
//        $bimp_id_origin = (int) $matches[1];
//    } elseif (preg_match('/^(ANNUL_)?CMDF(\d+)_LN(\d+)_RECEP(\d+)$/', $code, $matches)) {
//        $origin = 'order_supplier';
//        $id_origin = (int) $matches[2];
//    }
//    elseif (preg_match('/^VENTE(\d+)_(ART|RET)(\d+)$/', $code, $matches)) {
//        $origin = 'vente_caisse';
//        $id_origin = (int) $matches[1];
//    }

    if (($dol_origin !== $r['origintype'] && $dol_id_origin !== (int) $r['fk_origin'])) {
        echo 'Correction dol origine mvt #' . $r['rowid'] . ' (Origine: ' . $dol_origin . ' #' . $dol_id_origin . ' - Code: ' . $code . '): ';
        if (!(int) BimpTools::getValue('test', 0)) {
            if ($bdb->update('stock_mouvement', array(
                        'origintype' => $dol_origin,
                        'fk_origin'  => $dol_id_origin,
                            ), 'rowid = ' . (int) $r['rowid']) <= 0) {
                echo '<span class="danger">';
                echo '[ECHEC] - ' . $bdb->db->lasterror();
                echo '</span>';
            } else {
                echo 'OK';
            }
        }
        echo '<br/>';
    }

    if ($bimp_origin !== $r['bimp_origin'] && $bimp_id_origin !== (int) $r['bimp_id_origin']) {
        echo 'Correction bimp origine mvt #' . $r['rowid'] . ' (Origine: ' . $bimp_origin . ' #' . $bimp_id_origin . ' - Code: ' . $code . '): ';
        if (!(int) BimpTools::getValue('test', 0)) {
            if ($bdb->update('stock_mouvement', array(
                        'bimp_origin'    => $bimp_origin,
                        'bimp_id_origin' => $bimp_id_origin,
                            ), 'rowid = ' . (int) $r['rowid']) <= 0) {
                echo '<span class="danger">';
                echo '[ECHEC] - ' . $bdb->db->lasterror();
                echo '</span>';
            } else {
                echo 'OK';
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
        echo '<br/>';
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();