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

$where = '(`inventorycode` LIKE \'CO%_EXP%\' AND origintype != \'commande\')';
//$where .= ' OR `inventorycode` LIKE \'CMDF%\'';
//$where .= ' OR `inventorycode` LIKE \'ANNUL_CMDF%\'';
//$where .= ' OR `inventorycode` LIKE \'VENTE%\'';
//$where .= ')';

$rows = $bdb->getRows('stock_mouvement', $where, null, 'array', array('rowid', 'inventorycode'), 'rowid', 'desc');

if (!(int) BimPTools::getValue('exec', 0)) {
    echo 'Corrige l\'origine des mouvements de stock<br/><br/>';

    if (is_array && count($rows)) {
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

    $origin = '';
    $id_origin = 0;

    if (preg_match('/^CO(\d+)_EXP(\d+)(_ANNUL)?$/', $code, $matches)) {
        $origin = 'commande';
        $id_origin = (int) $matches[1];
    } else {
        echo $code . '<br/>';
    }

//    elseif (preg_match('/^(ANNUL_)?CMDF(\d+)_LN(\d+)_RECEP(\d+)$/', $code, $matches)) {
//        $origin = 'order_supplier';
//        $id_origin = (int) $matches[2];
//    }
//    elseif (preg_match('/^VENTE(\d+)_(ART|RET)(\d+)$/', $code, $matches)) {
//        $origin = 'vente_caisse';
//        $id_origin = (int) $matches[1];
//    }

    if ($origin && $id_origin) {
        echo 'Correction mvt #' . $r['rowid'] . ' (Origine: ' . $origin . ' #' . $id_origin . ' - Code: ' . $code . '): ';
//        echo '<br/>';
//        continue;

        if (!in_array($origin, array('vente_caisse', 'transfert', 'sav', 'package'))) {
            $bdb->update('stock_mouvement', array(
                'origintype' => $origin,
                'fk_origin'  => $id_origin,
                    ), 'rowid = ' . (int) $r['rowid']);
        }
        if ($bdb->update('stock_mouvement', array(
                    'bimp_origin'    => $origin,
                    'bimp_id_origin' => $id_origin,
                        ), 'rowid = ' . (int) $r['rowid']) <= 0) {
            echo '[ECHEC] - ' . $bdb->db->lasterror();
        } else {
            echo 'OK';
            $bdb->update('be_equipment_place', array(
                'origin'    => $origin,
                'id_origin' => $id_origin
                    ), 'code_mvt = \'' . $code . '\'');

            $bdb->update('be_package_place', array(
                'origin'    => $origin,
                'id_origin' => $id_origin
                    ), 'code_mvt = \'' . $code . '\'');
        }
        echo '<br/>';
//        break;
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();