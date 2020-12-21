<?php

die('Désactivé'); 

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

//llxHeader();

echo '<!DOCTYPE html>';
echo '<html lang="fr">';

echo '<head>';
//echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/bimpcore/views/css/ticket.css' . '"/>';
echo '<script src="/test2/includes/jquery/js/jquery.min.js?version=6.0.4" type="text/javascript"></script>';
echo '</head>';

echo '<body>';

global $db;
$bdb = new BimpDb($db);

$where = '`fk_statut` = 1 AND `paye` = 0'; // AND `rowid` = 86937';
$rows = $bdb->getRows('facture', $where, null, 'array', array(
    'rowid'
        ));

if (!is_null($rows) && count($rows)) {
    global $user;
    $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');

    foreach ($rows as $r) {
        if ($facture->fetch((int) $r['rowid'])) {
            $to_pay = (float) $facture->getRemainToPay();
            if ($to_pay >= -0.01 && $to_pay <= 0.01) {
                echo $r['rowid'] . ': ' . $to_pay . ' => ';
                if ($facture->dol_object->set_paid($user) <= 0) {
                    echo '[ECHEC] - ' . $facture->dol_object->error;
                } else {
                    echo '[OK]';
                }
                echo '<br/>';
            }
        }
    }
} else {
    echo 'AUCUNE FACTURE A TRAITER';
}

echo '</body></html>';

//llxFooter();
