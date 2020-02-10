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

$where = '(`fk_origin` IS NULL OR `fk_origin` = 0)';
//$where .= ' AND `inventorycode` != \'\' AND `inventorycode` IS NOT NULL';
$where .= ' AND `inventorycode` LIKE \'CMDF%\'';
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

    if (preg_match('/^CMDF(\d+)_LN(\d+)_RECEP(\d+)$/', $code, $matches)) {
        $comm = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $matches[1]);
        if (BimpObject::objectLoaded($comm)) {
            echo 'Correction mvt #' . $r['rowid'] . ' (Commande fourn ' . $comm->getRef() . '): ';

            if ($bdb->update('stock_mouvement', array(
                        'origintype' => 'order_supplier',
                        'fk_origin'  => (int) $comm->id,
                            ), 'rowid = ' . (int) $r['rowid']) <= 0) {
                echo '[ECHEC] - ' . $bdb->db->lasterror();
            } else {
                echo 'OK';

                $bdb->update('be_equipment_place', array(
                    'origin'    => 'order_supplier',
                    'id_origin' => (int) $comm->id
                        ), 'code_mvt = \'' . $code . '\'');

                $bdb->update('be_package_place', array(
                    'origin'    => 'order_supplier',
                    'id_origin' => (int) $comm->id
                        ), 'code_mvt = \'' . $code . '\'');
            }
            echo '<br/>';
        }

        unset($comm);
        BimpCache::$cache = array();
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();