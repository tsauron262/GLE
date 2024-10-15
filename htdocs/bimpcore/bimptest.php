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
require_once DOL_DOCUMENT_ROOT . '/bimpinterfaceclient/controllers/savFormController.php';
$sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', 577852);
$controller = new savFormController('bimpinterfaceclient');
$controller->sendRDVEmailToClient('f.martinez.bimp.fr', 'Florian Martinez', '123456789', new DateTime(), $sav, $sav->getCentreData(), array(
    'serial'    => 'SERIAL_TEST',
    'product_label'     => 'IPhone',
    'symptomes' => 'Ecran cassé'
        ), false);

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
