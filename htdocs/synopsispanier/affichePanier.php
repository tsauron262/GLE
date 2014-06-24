<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('../main.inc.php');
llxHeader();

$para = "idReferent=" . $_REQUEST['idReferent']."&type=".$_REQUEST['type'];

if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'generatePdf' || $_REQUEST['action'] == 'builddoc')) {
//    if ($conf->global->MAIN_MODULE_BABELGA == 1 && $_REQUEST['id'] > 0 && ($object->typepanier == 6 || $object->typepanier == 5)) {
//        require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsispanier/modules_panierGA.php");
//        panierGA_pdf_create($db, $object->id, $_REQUEST['model']);
//    } else {//if ($conf->global->MAIN_MODULE_BABELGMAO == 1 && $_REQUEST['id'] > 0 && ($object->typepanier == 7 || $object->typepanier == 2 || $object->typepanier == 3 || $object->typepanier == 4)) {
            require_once(DOL_DOCUMENT_ROOT . "/synopsispanier/core/modules/synopsispanier/modules_synopsispanier.php");
            $model = (isset($_REQUEST['model']) ? $_REQUEST['model'] : '');

            panier_pdf_create($db, $_REQUEST['idReferent'], $model);
//    } else {
//        require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsispanier/modules_synopsispanier.php");
//        panier_pdf_create($db, $object->id, $_REQUEST['model']);
//    }
            header('location: affichePanier.php?'.$para . "#documentAnchor");
        }


$societe = new Societe($db);
$requeteMomo = "SELECT valeur FROM ".MAIN_DB_PREFIX."Synopsys_Panier where type='".$_REQUEST['type']."' and referent = ".$_REQUEST['idReferent'].";";
$result = $db->query($requeteMomo);
while ($ligne = $db->fetch_object($result))
{
    $societe->fetch($ligne->valeur);
    echo $societe->getNomUrl(1);
    echo "<br/>";
    echo $societe->getFullAddress();
    echo "<br/>";
    echo "<br/>";
} 


$object = new Object();
$object->ref = $_REQUEST['idReferent'];
            $filename = sanitize_string($object->ref);
            $filedir = $conf->synopsispanier->dir_output . '/' . sanitize_string($object->ref);
            $urlsource = $_SERVER["PHP_SELF"] . "?".$para;
            
            $genallowed = $user->rights->synopsispanier->Global->read;

            require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
            $html = new Form($db);
            $formfile = new FormFile($db);
            $somethingshown = $formfile->show_documents('synopsispanier', $filename, $filedir, $urlsource, $genallowed, $genallowed, "PANIER"); //, $object->modelPdf);
       