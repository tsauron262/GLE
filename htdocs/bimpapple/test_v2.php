<?php

require_once __DIR__ . '/../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

ini_set('display_errors', 1);
set_time_limit(0);
ignore_user_abort(0);
top_htmlhead('', 'TESTS GSX V2', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

echo '<div style="padding: 15px">';

global $db;
$bdb = new BimpDb($db);

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_Reservation.php';

$gsx = new GSX_Reservation();

require_once DOL_DOCUMENT_ROOT . '/bimpsupport/centre.inc.php';

global $tabCentre;
$shipTos = array();

foreach ($tabCentre as $centre) {
    if (!in_array($centre[4], $shipTos)) {
        $shipTos[] = $centre[4];
    }
}

foreach ($shipTos as $shipTo) {
    $errors = array();
    $debug = '';
    $resas = $gsx->fetchAvailableSlots(897316, $shipTo, 'IPOD', $errors, $debug);

    echo '*** DEBUG *** <br/><br/>' . $debug . '<br/>******<br/>';

    if (count($errors)) {
        echo BimpRender::renderAlerts($errors);
    }

    echo '<br/>RESULTS:<br/><br/>';
    echo '<pre>';
    print_r($resas);
    echo '</pre>';
}

echo '</div>';
echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
