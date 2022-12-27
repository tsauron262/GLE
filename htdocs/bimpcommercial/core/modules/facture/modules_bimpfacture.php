<?php

require_once(DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/commondocgenerator.class.php';

function createFactureAttachment($db, $object, $obj_type, $modele = '', $outputlangs = '') {
    $errors = array();
    global $langs;

    $dir = DOL_DOCUMENT_ROOT . '/bimpcommercial/core/modules/facture/doc/';
    $modele_found = 0;

    $prefix = 'pdf_bimpfact_';
    $file = $prefix . $modele . '.modules.php';
    $full_path_file = $dir . $file;
    if ($modele && file_exists($full_path_file))
        $modele_found = 1;

    if ($modele_found) {
        $class_name = $prefix . $modele;
        require_once($full_path_file);
        if (class_exists($class_name)) {

            $obj = new $class_name($db);
            if (method_exists($obj, 'write_file')) {
                if ($obj->write_file($object, $outputlangs, $modele)) {
                    $pdf_dir = DOL_DATA_ROOT . '/bimpcommercial/' . $obj_type . '/' . $obj->id . '/';
                }

                $errors = BimpTools::merge_array($errors, $obj->errors);
//                    }
            } else {
                $errors[] = "Méthode \"write_file\" inexistante";
            }
        } else {
            $errors[] = "Classe : " . $class_name . ' inexsistante';
        }
    } else {
        $errors[] = $langs->trans("Error") . " " . $langs->trans("ErrorFileDoesNotExists", $full_path_file);
    }

    return $errors;
}
