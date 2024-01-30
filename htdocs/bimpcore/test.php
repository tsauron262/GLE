<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS', 0, 0, array(), array());

echo '<body style="padding: 30px">';

BimpCore::displayHeaderFiles();

global $db, $user;
$bdb = new BimpDb($db);

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

foreach (BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Propal', array(
    'date_valid' => array(
        'min' => '2024-01-30 12:50:00',
        'max' => '2024-01-30 16:41:00',
    ),
    'ef_type'    => array('operator' => '!=', 'value' => 'S')
)) as $p) {
    if (BimpObject::objectLoaded($p)) {
        echo '#' . $p->id . '<br/>';
        $p->dol_object->generateDocument($p->getModelPdf(), $langs);
    }
}


echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
