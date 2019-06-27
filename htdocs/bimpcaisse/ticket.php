<?php

define('NOLOGIN', '1');

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpTicket.php';

echo '<!DOCTYPE html>';
echo '<html lang="fr">';

echo '<head>';
echo '<script src="/test2/includes/jquery/js/jquery.min.js?version=6.0.4" type="text/javascript"></script>';
echo '</head>';

echo '<body>';

$errors = array();

$id_vente = (int) BimpTools::getValue('id_vente', 0);
$html = '';

if (!$id_vente) {
    $errors[] = 'ID de la vente absent';
} else {
    $vente = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Vente', $id_vente);
    if ($vente->isLoaded()) {
        $html = $vente->renderTicketHtml($errors);
    } else {
        $errors[] = 'ID de la vente invalide';
    }
}

if (!count($errors)) {
    echo $html;
    echo '<script>';
    echo '$(document).ready(function() {';
    echo 'window.print();';
    echo '$(window).click(function() {';
    echo 'window.close();';
    echo '});';
    echo '});';
    echo '</script>';
} else {
    echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/bimpcore/views/css/bimpcore.css' . '"/>';
    echo BimpRender::renderAlerts($errors);
}

echo '</body></html>';
