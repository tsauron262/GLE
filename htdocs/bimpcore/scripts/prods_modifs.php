<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'MAJ PRODUITS EN SERIE', 0, 0, array(), array());

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

$filepath = DOL_DATA_ROOT . '/bimpcore/prods_modifs.txt';

$refs = file($filename);

if (!(int) BimPTools::getValue('exec', 0)) {
    if (is_array($refs) && count($refs)) {
        echo count($refs) . ' élément(s) à traiter <br/><br/>';

        $path = pathinfo(__FILE__);
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1" class="btn btn-default">';
        echo 'Exécuter';
        echo '</a><br/><br/>';
        
        echo 'Refs produits: <pre>';
        print_r($refs);
        echo '</pre>';
    }

    echo BimpRender::renderAlerts('Aucun élément à traiter', 'info');
    exit;
}

changeFieldValue($refs, 'famille', 3097);

function changeFieldValue($refs, $field, $value)
{
    foreach ($refs as $ref) {
        $prod = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                    'ref' => $ref
        ));

        if (!BimpObject::objectLoaded($prod)) {
            echo BimpRender::renderAlerts('Aucun produit trouvé pour la réf "' . $ref . '"');
        } else {
            echo 'PROD #' . $prod->id . ' - ' . $ref . ': ';
            $up_errors = $prod->updateField($field, $value);
            if (count($up_errors)) {
                echo BimpRender::renderAlerts($up_errors, 'ECHEC');
            } else {
                echo 'OK';
            }
            echo '<br/>';
        }
    }
}
echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
