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
    exit;
}

$filepath = DOL_DATA_ROOT . '/bimpcore/quick_modifs.txt';
$module = '';
$object_name = '';
$ref_field = '';
$data = '';

// Pour forçage statuts commandes fourn: 
 
//$module = 'bimpcommercial';
//$object_name = 'Bimp_CommandeFourn';
//$ref_field = 'ref';
//$data = array(
//    'fk_statut'      => 5,
//    'invoice_status' => 2,
//    'billed'         => 1,
//    'status_forced'  => array(
//        'reception' => 1,
//        'invoice'   => 1
//    )
//);

$bdb = new BimpDb($db);
$errors = array();

if (!$module) {
    $errors[] = 'Module non défini';
}
if (!$object_name) {
    $errors[] = 'Nom de l\'objet non défini';
}
if (!$ref_field) {
    $errors[] = 'Champ de référence non défini';
}
if (!$field) {
    $errors[] = 'Champ à editer non défini';
}
if (!file_exists($filepath)) {
    $errors[] = 'Le fichier "' . $filepath . '" n\'existe pas';
}

if (count($errors)) {
    echo BimpRender::renderAlerts($errors);
    exit;
}

$refs = file($filepath, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);

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

foreach ($refs as $ref) {
    $object = BimpCache::findBimpObjectInstance($module, $object_name, array(
                $ref_field => $ref
    ));

    if (!BimpObject::objectLoaded($object)) {
        echo BimpRender::renderAlerts('Aucun' . $object->e() . ' ' . $object->getLabel() . ' trouvé' . $object->e() . ' pour la réf "' . $ref . '"');
    } else {
        echo BimpTools::ucfirst($object->getLabel()) . ' #' . $object->id . ' - ' . $ref . ': ';
        
        $up_errors = $object->validateArray($data);
        
        if (!count($up_errors)) {
            $up_errors = $object->update($up_warnings, true);
        }
        
        if (count($up_errors)) {
            echo BimpRender::renderAlerts($up_errors, 'danger');
        } else {
            echo 'OK';
        }
        
        if (count($up_warnings)) {
            echo BimpRender::renderAlerts($up_warnings, 'warning');
        }
        echo '<br/>';
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
