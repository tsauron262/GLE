<?php

require_once("../main.inc.php");
require_once ("../bimpcore/Bimp_Lib.php");
ini_set('display_errors', 1);

$errors = array();

$equipments = array();

$id_relance = (int) BimpTools::getValue('id_relance', 0);

if (!$id_relance) {
    $errors[] = 'ID de la relance absent';
} else {
    $relance = BimpCache::getBimpObjectInstance('bimpcommercial', 'BimpRelanceClients', $id_relance);
    if (!BimpObject::objectLoaded($relance)) {
        $errors[] = 'La relance d\'ID ' . $id_relance . ' n\'existe pas';
    } else {
        $errors[] = $relance->generateRemainToSendPdf(true);
    }
}

$errors[] = 'TEST';

if (count($errors)) {
    echo count($errors) . ' erreur(s): <br/><br/>';

    foreach ($errors as $e) {
        echo ' - ' . $e . '<br/><br/>';
    }
}