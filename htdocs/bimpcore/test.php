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

if ((int) BimpTools::getValue('test_serials', 0)) {
    echo 'CHECK SERIALS <br/>';
    BimpObject::loadClass('bimpfinanc', 'BimpRevalorisation');

    $nbOk = 0;
    BimpRevalorisation::checkAppleCareSerials($nbOk);

    echo 'OK - ' . $nbOk . ' serials traités';
}

if ((int) BimpTools::getValue('test_revals', 0)) {
    echo 'CHECKS REVALS: <br/>';
    BimpObject::loadClass('bimpfinanc', 'BimpRevalorisation');

    $nbOk = 0;
    $errors = BimpRevalorisation::checkBilledApplecareReval($nbOk);
    echo 'OK - ' . $nbOk . ' revals traitées';

    echo 'ERRORS<pre>';
    print_r($errors);
    exit;
}


echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
