<?php

define('NOLOGIN', '1');

require_once("../main.inc.php");

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/PropalSavPDF.php';

global $db, $langs;

$id_sav = (int) BimpTools::getValue('id_sav');

$errors = array();

ini_set('display_errors', 1);

if (!$id_sav) {
    $errors[] = 'ID du sav absent';
} else {
    $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
    /* tocomment */
//    $sav = new BS_SAV();
//    die('Fichier "' . basename(__FILE__) . '", ligne ' . __LINE__ . ': instanciation directe à commenter');

    if (!BimpObject::objectLoaded($sav)) {
        $errors[] = 'Le SAV d\'ID ' . $id_sav . ' n\'existe pas';
    } else {
        $propal = $sav->getChildObject('propal');

        if (!BimpObject::objectLoaded($propal)) {
            $errors[] = 'ID de la proposition commerciale absent';
        } else {
            global $db;
            
            $pdf = new SavRestitutePDF($db);

            $pdf->init($propal->dol_object);
            $file = $pdf->getFilePath() . $pdf->getFileName();
            $pdf->render($file, true);
            exit;
        }
    }
}

if (count($errors)) {
    echo BimpRender::renderAlerts($errors);
    exit;
}


