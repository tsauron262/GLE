<?php

require_once(DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/commondocgenerator.class.php';

/**
  \class      ModeleSynopsissynopsischrono
  \brief      Classe mere des modeles de deplacement
 */
class ModeleBimpSupport extends CommonDocGenerator
{

    var $error = '';
    public $errors = array();

    function pdferror()
    {
        return $this->error;
    }

    static function liste_modeles($db, $maxfilenamelength = 0)
    {
        global $typeChrono;
        $type = 'synopsischrono_' . $typeChrono;
        $type2 = 'synopsischrono';
        $liste = array();
        $sql = "SELECT nom as id, ifnull(libelle,nom) as lib";
        $sql.=" FROM " . MAIN_DB_PREFIX . "document_model";
        $sql.=" WHERE type = '" . $type . "' OR type = '" . $type2 . "' ORDER BY type DESC";

        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $row = $db->fetch_row($resql);
                $liste[$row[0]] = $row[1];
                $i++;
            }
        } else {
            $this->error = $db->error();
            return -1;
        }
        return $liste;
    }
}

function bimpsupport_pdf_create($db, $object, $obj_type, $modele = '', $outputlangs = '')
{
    $errors = array();

    global $langs, $conf;
    $langs->load("babel");
    $langs->load("contracts");

    $dir = DOL_DOCUMENT_ROOT . "/bimpsupport/core/modules/bimpsupport/doc/";
    $modelisok = 0;

    $file = "pdf_bimpsupport_" . $modele . ".modules.php";
    if ($modele && file_exists($dir . $file))
        $modelisok = 1;

//    if (!$modelisok) {
//        if (isset($conf->global->SYNOPSIS_PANIER_ADDON_PDF))
//            $modele = $conf->global->SYNOPSIS_PANIER_ADDON_PDF;
//        $file = "pdf_bimpsupport_" . $modele . ".modules.php";
//        if (file_exists($dir . $file))
//            $modelisok = 1;
//    }
//    if (!$modelisok) {
//        $liste = array();
//        $model = new ModeleSynopsischrono();
//        $liste = $model->liste_modeles($db);
//        $modele = key($liste);        // Renvoie premiere valeur de cle trouve dans le tableau
//        $file = "pdf_synopsischrono_" . $modele . ".modules.php";
//        if (file_exists($dir . $file))
//            $modelisok = 1;
//    }

    if ($modelisok) {
        $classname = "pdf_bimpsupport_" . $modele;
        require_once($dir . $file);

        $obj = new $classname($db);
        if ($obj->write_file($object, $outputlangs) > 0) {
            $pdf_dir = DOL_DATA_ROOT . '/bimpcore/' . $obj_type . '/' . $obj->id . '/';
            bimpsupport_delete_preview($db, $object->getData('ref'), $pdf_dir);
        } else {
            dol_syslog("Erreur dans bimpsupport_pdf_create");
            $error = $obj->pdferror();
            if ($error) {
                $errors[] = $error;
            }
            if (count($obj->errors)) {
                $errors = BimpTools::merge_array($errors, $obj->errors);
            }
        }
    } else {
        $errors[] = $langs->trans("Error") . " " . $langs->trans("ErrorFileDoesNotExists", $dir . $file);
    }

    return $errors;
}

function bimpsupport_delete_preview($db, $obj_ref, $dir)
{
    global $langs, $conf;

    $obj_ref = sanitize_string($obj_ref);
    $file = $dir . "/" . $obj_ref . ".pdf.png";
    $multiple = $file . ".";

    if (file_exists($file) && is_writable($file)) {
        if (!unlink($file)) {
            $this->error = $langs->trans("ErrorFailedToOpenFile", $file);
            return 0;
        }
    } else {
        for ($i = 0; $i < 20; $i++) {
            $preview = $multiple . $i;

            if (file_exists($preview) && is_writable($preview)) {
                if (!unlink($preview)) {
                    $this->error = $langs->trans("ErrorFailedToOpenFile", $preview);
                    return 0;
                }
            }
        }
    }
}

function couperChaine($chaine, $nb)
{
    if (strlen($chaine) > $nb)
        $chaine = substr($chaine, 0, $nb) . "...";
    return $chaine;
}

function traiteStr($str)
{
    return utf8_encodeRien(utf8_encodeRien(htmlspecialchars($str)));
}

function max_size($chaine, $lg_max)
{
    if (strlen($chaine) > $lg_max) {
        $chaine = substr($chaine, 0, $lg_max);
        $last_space = strrpos($chaine, " ");
        $chaine = substr($chaine, 0, $last_space) . "...";
    }

    return $chaine;
}
?>
