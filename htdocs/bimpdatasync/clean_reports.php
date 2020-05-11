<?php

require_once '../bimpcore/main.php';

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

BimpObject::loadClass('bimpdatasync', 'BDS_Report');

$errors = array();

$n = BDS_Report::cleanReports($errors);

if ((int) BimpTools::getValue('debug', 0)) {
    if (!$n) {
        echo 'Aucun rapport à supprimer';
    } else {
        echo $n . ' rapport(s) supprimé(s)';
    }

    if (count($errors)) {
        echo '<br/><br/>Erreurs: <br/>';

        foreach ($errors as $error) {
            echo ' - ' . $error . '<br/>';
        }
    }
}
