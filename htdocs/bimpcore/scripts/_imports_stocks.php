<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'IMPORT STOCKS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

$id_entrepot = 178;

$action = BimpTools::getValue('action', '');

if (!$action) {
    $actions = array(
        'import_stocks' => 'Import des stocks',
        'import_eqs'    => 'Import des équipements',
    );

    $path = pathinfo(__FILE__);

    foreach ($actions as $code => $label) {
        echo '<div style="margin-bottom: 10px">';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=' . $code . '" class="btn btn-default">';
        echo $label . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';
    }
    exit;
}

ini_set('max_execution_time', 300);
set_time_limit(300);

switch ($action) {
    case 'import_stocks':
        importStocks($id_entrepot);
        break;

    case 'import_eqs':
        importEquipments($id_entrepot);
        break;
}

function importStocks($id_entrepot)
{
    if ((int) BimpCore::getConf('script_import_stocks_comp', 0)) {
        echo BimpRender::renderAlerts('Import déjà effectué (var conf "script_import_stocks_comp")');
        return;
    }

    $file = 'import_stocks.csv';

    $keys = array(
        'ref' => 1,
        'qty' => 2
    );

    $dir = DOL_DOCUMENT_ROOT . '/bimpcore/scripts/docs/';

    if (!file_exists($dir . $file)) {
        echo '<span class="danger">Le fichier "' . $dir . $file . '" n\'existe pas</span>';
        return;
    }

    $lines = file($dir . $file);
    $rows = array();

    foreach ($lines as $i => $line) {
        if ($i < 1) {
            continue;
        }

        $data = explode(';', $line);

        $rows[$data[$keys['ref']]] = $data[$keys['qty']];
    }

    $exec = (int) BimpTools::getValue('exec', 0);

    if (!$exec) {
        $path = pathinfo(__FILE__);
        echo '<div style="margin-bottom: 30px">';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=import_stocks&exec=1" class="btn btn-default">';
        echo 'Exécuter' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';
    }

    foreach ($rows as $ref => $qty) {
        echo $ref . ': ' . $qty . '&nbsp;';

        $prod = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array('ref' => $ref));

        if (!BimpObject::objectLoaded($prod)) {
            echo '<span class="danger">[PROD NON TROUVE]</span>';
        } elseif ($exec) {
            $errors = $prod->correctStocks($id_entrepot, (float) $qty, 0, 'IMPORT', 'Import csv stocks');

            if (count($errors)) {
                echo BimpRender::renderAlerts($errors);
            } else {
                echo '<span class="success">[OK]</span>';
            }
        }

        echo '<br/>';
        break;
    }

    if ($exec) {
        BimpCore::setConf('script_import_stocks_comp', 1);
    }
}

function importEquipments($id_entrepot)
{
//    BimpCore::setConf('script_import_serials_comp', 0);
    
    if ((int) BimpCore::getConf('script_import_serials_comp', 0)) {
        echo BimpRender::renderAlerts('Import déjà effectué (var conf "script_import_serials_comp")');
        return;
    }

    $file = 'import_equipments.csv';

    $keys = array(
        'ref'    => 6,
        'serial' => 10,
        'pa'     => 11
    );

    $dir = DOL_DOCUMENT_ROOT . '/bimpcore/scripts/docs/';

    if (!file_exists($dir . $file)) {
        echo '<span class="danger">Le fichier "' . $dir . $file . '" n\'existe pas</span>';
        return;
    }

    $lines = file($dir . $file);
    $rows = array();

    foreach ($lines as $i => $line) {
        if ($i < 1) {
            continue;
        }

        $data = explode(';', $line);

        $rows[] = array(
            'ref'    => $data[$keys['ref']],
            'serial' => $data[$keys['serial']],
            'pa'     => $data[$keys['pa']]
        );
    }

    $exec = (int) BimpTools::getValue('exec', 0);

    if (!$exec) {
        $path = pathinfo(__FILE__);
        echo '<div style="margin-bottom: 30px">';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=import_eqs&exec=1" class="btn btn-default">';
        echo 'Exécuter' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';
    }

    foreach ($rows as $r) {
        echo $r['ref'] . ': <b>' . $r['serial'] . '</b>&nbsp;';

        $prod = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array('ref' => $r['ref']));

        if (!BimpObject::objectLoaded($prod)) {
            echo '<span class="danger">[PROD NON TROUVE]</span>';
        } else {
            $eq = BimpCache::findBimpObjectInstance('bimpequipment', 'Equipment', array(
                        'id_product' => (int) $prod->id,
                        'serial'     => $r['serial']
            ));

            if (BimpObject::objectLoaded($eq)) {
                echo '<span class="danger">[EQ EXISTE DEJA]</span>';
            } elseif ($exec) {
                $errors = array();

                $eq = BimpObject::createBimpObject('bimpequipment', 'Equipment', array(
                            'id_product' => (int) $prod->id,
                            'serial'     => $r['serial']
                                ), true, $errors);

                if (count($errors)) {
                    echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($errors, 'Echec créa équipement'));
                } elseif (!BimpObject::objectLoaded($eq)) {
                    echo '<span class="danger">[ECHEC CREA EQUIPEMENT]</span>';
                } else {
                    $errors = array();
                    BimpObject::createBimpObject('bimpequipment', 'BE_Place', array(
                                'id_equipment' => (int) $eq->id,
                                'type'         => BE_Place::BE_PLACE_ENTREPOT,
                                'id_entrepot'  => $id_entrepot,
                                'date'         => date('Y-m-d H:i:s'),
                                'infos'        => 'Import CSV',
                                'code_mvt'     => 'IMPORT'
                                    ), true, $errors);

                    if (count($errors)) {
                        echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($errors, 'Echec créa emplacement'));
                    } else {
                        echo '<span class="success">[OK]</span>';
                    }
                }
            }
        }

        echo '<br/>';
    }

    if ($exec) {
        BimpCore::setConf('script_import_serials_comp', 1);
    }
}
echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
