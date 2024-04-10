<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS', 0, 0, array(), array());

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

echo '<div style="padding: 30px">';


$m = BimpTools::getValue('m', 'bimpcore', 'aZ09');
$o = BimpTools::getValue('o', '', 'aZ09');
$t = BimpTools::getValue('t', '', 'aZ09');
$n = BimpTools::getValue('n', '', 'aZ09');

if (!$m || !$o || $r || !$n) {
    echo 'Params invalides.<br/>';
    echo 'm=module&o=obj_name&t=type&n=name';
} else {
    echo '<h3>'.$m .' - ' . $o .'</h3>';
    echo '<h4>'.$t .' : ' . $n .'</h4>';
    echo '<br/><br/>';
    
    require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpYml.php';
    $errors = array();

    $dir = DOL_DOCUMENT_ROOT . '/' . $m . '/objects/';
    $file_name = $o . '.yml';
    $file = $dir . $file_name;
    $path = $t . 's' . '/' . $n . '/rows';

    $res = BimpYmlGenerator::numericKeysToLiteralKeys($file, $path, $t, $errors);

    if (!$res && !count($errors)) {
        $errors[] = 'Aucun résultat';
    } 
    
    if (count($errors)) {
        echo '<pre>';
        print_r($errors);
        echo '</pre>';
    } else {
        echo 'Résultat: <br/><br/><pre>';
        echo $res;
        echo '</pre>';
    }
}

echo '<br/>FIN';
echo '</div>';
echo '</body></html>';

//llxFooter();
