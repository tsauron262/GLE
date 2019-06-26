<?php

require_once("../main.inc.php");

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

$errors = array();

$module = BimpTools::getValue('module', '');                                    
$object_name = BimpTools::getValue('object_name', '');
$id_object = BimpTools::getValue('id_object', 0);
$view = BimpTools::getValue('view', 'default');

if (!$module) {
    $errors[] = 'Module absent';
}
if (!$object_name) {
    $errors[] = 'Module absent';
}
if (!$id_object) {
    $errors[] = 'Module absent';
}

if (!count($errors)) {
    
}

top_htmlhead('', $title, 0, 0, array(), array());
BimpCore::displayHeaderFiles();

echo '<body>';

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
