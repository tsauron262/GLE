<?php

/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify

 * it under the terms of the GNU General Public License as published by

 * the Free Software Foundation; either version 2 of the License, or

 * (at your option) any later version.

 *

 * This program is distributed in the hope that it will be useful,

 * but WITHOUT ANY WARRANTY; without even the implied warranty of

 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the

 * GNU General Public License for more details.

 *

 * You should have received a copy of the GNU General Public License

 * along with this program; if not, write to the Free Software

 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */
/*
 * BIMP-ERP by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.synopsis-erp.com
 *
 */
/*
 */

/**
  \file       htdocs/synopsisfichinter/card.php
  \brief      Fichier fiche intervention
  \ingroup    ficheinter
  \version    $Id: card.php,v 1.100 2008/07/15 00:57:37 eldy Exp $
 */
$afficherLigneContrat = false;


require("../synopsisfichinter/pre.inc.php");


require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';
$bObj = BimpObject::getInstance("bimpfichinter", "Bimp_Fichinter", $_REQUEST['id']);
header("Location: " . DOL_URL_ROOT . '/bimptechnique/?fc=fi&id=' . $_REQUEST['id']);
$htmlRedirect = $bObj->processRedirect();

require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsisfichinter/class/synopsisfichinter.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/modules/fichinter/modules_fichinter.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/fichinter.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");


$outputlangs = $langs;

if (isset($conf->projet->enabled)) {
    require_once(DOL_DOCUMENT_ROOT . "/core/lib/project.lib.php");
    require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
}
if (defined("FICHEINTER_ADDON") && is_readable(DOL_DOCUMENT_ROOT . "/core/modules/fichinter/mod_" . FICHEINTER_ADDON . ".php")) {
    require_once(DOL_DOCUMENT_ROOT . "/core/modules/fichinter/mod_" . FICHEINTER_ADDON . ".php");
}

$langs->load("companies");
$langs->load("interventions");

// Get parameters
$fichinterid = isset($_REQUEST["id"]) ? $_REQUEST["id"] : '';


// Security check
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'synopsisficheinter', $fichinterid, 'fichinter');



/*
 * Traitements des actions
 */
if ((isset($_REQUEST["action"]) && $_REQUEST["action"] != 'create' && $_REQUEST["action"] != 'add') && !$_REQUEST["id"] > 0) {
    Header("Location: index.php");
    return;
}

if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'confirm_devalidate' && $_REQUEST['confirm'] == 'yes') {
    $fichinter = new Synopsisfichinter($db);
    $fichinter->id = $_REQUEST["id"];
    $fichinter->fetch($_REQUEST["id"]);
    $result = $fichinter->devalid($user, $conf->fichinter->outputdir);
}



if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'confirm_validate' && $_REQUEST['confirm'] == 'yes') {
    $fichinter = new Synopsisfichinter($db);
    $fichinter->id = $_REQUEST["id"];
    $fichinter->fetch($_REQUEST["id"]);
    $result = $fichinter->valid($user, $conf->fichinter->outputdir);
    if ($result >= 0) {
        if ($_REQUEST['lang_id']) {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($_REQUEST['lang_id']);
        }

        $result = fichinter_create($db, $fichinter, $_REQUEST['model'], $outputlangs);
    } else {
        $mesg = '<div class="error ui-state-error">' . $fichinter->error . '</div>';
    }
}
if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'editExtra') {
    $remId = array();
    foreach ($_REQUEST as $key => $val) {
        if (preg_match('/^extraKey-([0-9]*)/', $key, $arr) || preg_match('/^type-([0-9]*)/', $key, $arr)) {
            $idExtraKey = $arr[1];
            if ($remId[$idExtraKey]) {
                continue;
            } else {
                $remId[$idExtraKey] = true;
            }
            $type = $_REQUEST['type-' . $idExtraKey];

            //Y'a quelque chose ?
            $requete = "DELETE
                          FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value
                         WHERE interv_refid = " . $_REQUEST['id'] . "
                           AND typeI = 'FI'
                           AND extra_key_refid= " . $idExtraKey;
            $sql = $db->query($requete);
            if ($type == 'heure') {
                $val = traiteHeure($val);
            }
            if ($type == 'checkbox') {
                if ($val == 'On' || $val == 'on' || $val == 'ON') {
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value
                                             (interv_refid,extra_key_refid,extra_value,typeI)
                                      VALUES (" . $_REQUEST['id'] . "," . $idExtraKey . ",1,'FI')";
                    $sql = $db->query($requete);
                } else {
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value
                                             (interv_refid,extra_key_refid,extra_value,typeI)
                                      VALUES (" . $_REQUEST['id'] . "," . $idExtraKey . ",0,'FI')";
                    $sql = $db->query($requete);
                }
            } else {
                $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value
                                         (interv_refid,extra_key_refid,extra_value,typeI)
                                  VALUES (" . $_REQUEST['id'] . "," . $idExtraKey . ",'" . addslashes($val) . "','FI')";
                $sql = $db->query($requete);
            }
        }
    }
}


if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'remove_file') {
    require_once(DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");
    $filedir = $conf->ficheinter->dir_output . "/" . $fichinter->ref;
    $file = $filedir . '/' . GETPOST('file', 'alpha'); // Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
    $result = dol_delete_file($file);
    //if ($result >= 0) $mesg=$langs->trans("FileWasRemoced");
}

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'add') {

    $fichinter = new Synopsisfichinter($db);

    $fichinter->date = dol_mktime($_REQUEST["phour"], $_REQUEST["pmin"], $_REQUEST["psec"], $_REQUEST["pmonth"], $_REQUEST["pday"], $_REQUEST["pyear"]);
    $fichinter->socid = $_REQUEST["socid"];
    $fichinter->duree = $_REQUEST["duree"];
    $fichinter->projet_id = $_REQUEST["projetidp"];
    $fichinter->author = $user->id;
    $fichinter->description = $_REQUEST["description"];
    $fichinter->ref = $_REQUEST["ref"];
    $fichinter->modelpdf = $_REQUEST["model"];
    $fichinter->fk_contrat = $_POST["fk_contrat"];
    $fichinter->fk_commande = $_POST["fk_commande"];

    if ($fichinter->socid > 0) {
        $result = $fichinter->create($user);
        if ($result > 0) {
            if (isset($_REQUEST['fk_contratdet']))
                addElementElement("contratdet", "fichinter", $_REQUEST['fk_contratdet'], $result);

            $_REQUEST["id"] = $result;      // Force raffraichissement sur fiche venant d'etre creee
            $fichinterid = $result;
        } else {
            $mesg = '<div class="error ui-state-error">' . $fichinter->error . '</div>';
            $_REQUEST["action"] = 'create';
        }
    } else {
        $mesg = '<div class="error ui-state-error">' . $langs->trans("ErrorFieldRequired", $langs->trans("ThirdParty")) . '</div>';
        $_REQUEST["action"] = 'create';
    }
}



//$groups = $user->listGroupIn();
//require_once(DOL_DOCUMENT_ROOT . "/user/class/usergroup.class.php");
//$usergroup = new UserGroup($db);
//$groups = $usergroup->listGroupsForUser($user->id,1);
$accesGrTech = userInGroupe(64, $user->id);
$accesGrCom = userInGroupe(2, $user->id);
$accesGrCom2 = userInGroupe(43, $user->id);
$accesGrCom3 = userInGroupe(517, $user->id);
if (!($user->admin || $accesGrTech || $accesGrCom || $accesGrCom2 || $accesGrCom3) && !(isset($_REQUEST['action']) && $_REQUEST['action'] == 'create'))
    if (isset($_REQUEST['id']) && stripos($_SERVER['REQUEST_URI'], "?") === false)
        header('Location: ' . str_replace("card.php", "../synopsisfichinter/ficheFast.php?id=" . $_REQUEST['id'], "http".((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'])? "s" :"") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']));
    else
        header('Location: ' . str_replace("card.php", "../synopsisfichinter/ficheFast.php", "http".((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'])? "s" :"") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']));


if (isset($_POST["action"]) && $_POST["action"] == 'update') {
    $fichinter = new Synopsisfichinter($db);

    $fichinter->date = dol_mktime($_POST["phour"], $_POST["pmin"], $_POST["psec"], $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
    $fichinter->socid = $_POST["socid"];
    $fichinter->projet_id = $_POST["projetidp"];
    $fichinter->author = $user->id;
    $fichinter->description = $_POST["description"];
    $fichinter->ref = $_POST["ref"];
    $fichinter->update($_POST["id"]);
    $_REQUEST["id"] = $_POST["id"];      // Force raffraichissement sur fiche venant d'etre creee
}

/*
 * Generer ou regenerer le document PDF
 */
if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'builddoc') {    // En get ou en post
    $fichinter = new Synopsisfichinter($db);
    $fichinter->fetch($_REQUEST['id']);
    $fichinter->fetch_lines();

    if ($_REQUEST['lang_id']) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }

    $result = fichinter_create($db, $fichinter, $_REQUEST['model'], $outputlangs);
    if ($result <= 0) {
//        dol($db, $result);
        exit;
    } else {
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface = new Interfaces($db);
        $result = $interface->run_triggers('ECM_GENFICHEINTER', $fichinter, $user, $langs, $conf);
        if ($result < 0) {
            $error++;
            $this->errors = $interface->errors;
        }
        // Fin appel triggers
    }
}

/*
 * Classer dans un projet
 */
if (isset($_REQUEST["action"]) && $_POST['action'] == 'classin') {
    $fichinter = new Synopsisfichinter($db);
    $fichinter->fetch($_REQUEST['id']);
    $fichinter->set_project($user, $_POST['projetidp']);
}

if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'confirm_delete' && $_REQUEST['confirm'] == 'yes') {
    if ($user->rights->synopsisficheinter->supprimer) {
        $fichinter = new Synopsisfichinter($db);
        $fichinter->fetch($_REQUEST['id']);
        $fichinter->delete($user);
    }
    Header('Location: ' . DOL_URL_ROOT . '/synopsisfichinter/liste.php?leftmenu=ficheinter');
    exit;
}

if (isset($_REQUEST["action"]) && $_POST['action'] == 'setdate_delivery') {
    $fichinter = new Synopsisfichinter($db);
    $fichinter->fetch($_REQUEST['id']);
    $result = $fichinter->set_date_delivery($user, dol_mktime(12, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']));
    if ($result < 0)
        dol_print_error($db, $fichinter->error);
}

if (isset($_REQUEST["action"]) && $_POST['action'] == 'setdescription') {
    $fichinter = new Synopsisfichinter($db);
    $fichinter->fetch($_REQUEST['id']);
    $result = $fichinter->set_description($user, $_POST['description']);
    if ($result < 0)
        dol_print_error($db, $fichinter->error);
}


if (isset($_REQUEST["action"]) && $_POST['action'] == 'setdescription2') {
    $fichinter = new Synopsisfichinter($db);
    $fichinter->fetch($_REQUEST['id']);
    $result = $fichinter->set_note_private($user, $_POST['description2']);
    if ($result < 0)
        dol_print_error($db, $fichinter->error);
}

/*
 *  Ajout d'une ligne d'intervention
 */
//    var_dump($_REQUEST);
//    exit;

if (isset($_REQUEST["action"]) && $_POST['action'] == "addligne" && $user->rights->synopsisficheinter->creer) {
    if ($_POST['np_desc'] && ($_POST['durationhour'] || $_POST['durationmin'])) {
        $fichinter = new Synopsisfichinter($db);
        $ret = $fichinter->fetch($_POST['fichinterid']);
        $fichinter->info($_POST['fichinterid']);

        //toujours 1
        $qte = 1;
        //Prix jour de l'intervenant
        $duration = ConvertTime2Seconds($_POST['durationhour'], $_POST['durationmin']);
        $fk_typeinterv = (isset($_REQUEST["fk_typeinterv"]) ? $_REQUEST["fk_typeinterv"] : "");
        $pu_ht = 0;

        /* $resultT = getElementElement("contratdet", "fichinter", null, $_REQUEST['id']);
          if(isset($resultT[0]['s'])){
          $contradet = $resultT[0]['s'];
          //            die("ok".$contradet);
          $requete = "SELECT `price_ht` as prix_ht FROM `" . MAIN_DB_PREFIX . "contratdet` WHERE `rowid` = ".$contradet;
          $sql = $db->query($requete);
          $res = $db->fetch_object($sql);
          if ($res->prix_ht > 0) {
          $pu_ht = $res->prix_ht;
          }
          }
          else */if (preg_match('/dep-([0-9]*)/', $fk_typeinterv, $arr)) {
            $fk_typeinterv = 4;
            $typeIntervProd = $arr[1];
            $requete = "SELECT prix_ht
                          FROM " . MAIN_DB_PREFIX . "synopsisfichinter_User_PrixDepInterv
                         WHERE user_refid = " . $fichinter->user_creation->id . "
                           AND fk_product = " . $typeIntervProd;
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            if ($res->prix_ht > 0) {
                $pu_ht = $res->prix_ht;
            }
            //toujours 1
            $isForfait = 1;
        } else {
            $requete = "SELECT prix_ht
                          FROM " . MAIN_DB_PREFIX . "synopsisfichinter_User_PrixTypeInterv
                         WHERE user_refid = " . $fichinter->user_creation->id . "
                           AND typeInterv_refid = " . $fk_typeinterv;
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            if ($res->prix_ht > 0) {
                $pu_ht = $res->prix_ht;
            }
            //toujours 0
            $isForfait = 0;
        }

//        $pu_ht = preg_replace('/,/','.',$_REQUEST['pu_ht']);

        $desc = $_POST['np_desc'];
        $date_intervention = $db->idate(mktime(12, 1, 1, $_POST["dimonth"], $_POST["diday"], $_POST["diyear"]));
        $bool = false;
        foreach ($_REQUEST as $key => $val) {
            if (preg_match('/fk_contratdet-([0-9]*)/', $key, $arrMatch)) {
                $fk_contratdet = $val;
                $fichinter->addlineSyn(
                        $_POST['fichinterid'], $desc, $date_intervention, $duration, $fk_typeinterv, $qte, $pu_ht, $isForfait, $_REQUEST['comLigneId'], $fk_contratdet, $typeIntervProd
                );
                $bool = true;
            }
        }
        if (!$bool) {
            $fk_contratdet = $_REQUEST['fk_contratdet'];
            $fichinter->addlineSyn(
                    $_POST['fichinterid'], $desc, $date_intervention, $duration, $fk_typeinterv, $qte, $pu_ht, $isForfait, $_REQUEST['comLigneId'], $fk_contratdet, $typeIntervProd
            );
        }

        if ($_REQUEST['lang_id']) {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($_REQUEST['lang_id']);
        }
        fichinter_create($db, $fichinter, $fichinter->modelpdf, $outputlangs);
        $fichinter->majPrixDi();
    }
}

/*
 *  Mise a jour d'une ligne d'intervention
 */
if (isset($_REQUEST["action"]) && $_POST['action'] == 'updateligne' && $user->rights->synopsisficheinter->creer && $_POST["save"] == $langs->trans("Save")) {
    $fichinterline = new SynopsisfichinterLigne($db);
    if ($fichinterline->fetch($_POST['ligne']) <= 0) {
        dol_print_error($db, "Erreur FIdet update");
        exit;
    }
    $fichinter = new Synopsisfichinter($db);
    if ($fichinter->fetch($fichinterline->fk_fichinter) <= 0) {
        dol_print_error($db, "Erreur FI update");
        exit;
    }
    $fichinter->info($fichinterline->fk_fichinter);
    $desc = $_POST['desc'];
    $date_intervention = dol_mktime(12, 1, 1, $_POST["dimonth"], $_POST["diday"], $_POST["diyear"]);
    $duration = ConvertTime2Seconds($_POST['durationhour'], $_POST['durationmin']);

    $fichinterline->desc = $desc;
    $fichinterline->datei = $date_intervention;
    $fichinterline->duration = $duration;


    $qte = 1;
    //Prix jour de l'intervenant
    $duration = ConvertTime2Seconds($_POST['durationhour'], $_POST['durationmin']);


    $fichinterline->fk_typeinterv = ($_REQUEST["fk_typeinterv"] . "x" != "x" ? $_REQUEST["fk_typeinterv"] : "");
    if (preg_match('/^dep-([0-9]*)/', $fichinterline->fk_typeinterv, $arr)) {
        $fichinterline->fk_typeinterv = 4;
        $fichinterline->typeIntervProd = $arr[1];
        $requete = "SELECT prix_ht
                      FROM " . MAIN_DB_PREFIX . "synopsisfichinter_User_PrixDepInterv
                     WHERE user_refid = " . $fichinter->user_creation->id . "
                       AND fk_product = " . $fichinterline->typeIntervProd;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        if ($res->prix_ht > 0) {
            $fichinterline->pu_ht = $res->prix_ht;
        }
        //toujours 1
        $fichinterline->qte = 1;
        $fichinterline->isForfait = 1;
    } elseif($fichinterline->fk_typeinterv > 0) {
        $requete = "SELECT prix_ht
                      FROM " . MAIN_DB_PREFIX . "synopsisfichinter_User_PrixTypeInterv
                     WHERE user_refid = " . $fichinter->user_creation->id . "
                       AND typeInterv_refid = " . $fichinterline->fk_typeinterv;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
//        $fichinterline->pu_ht = 0;
        if ($res->prix_ht > 0) {
            $fichinterline->pu_ht = $res->prix_ht;
        }
        $fichinterline->qte = 1;
        $fichinterline->isForfait = 0;
    }


    $fichinterline->fk_contratdet = $_REQUEST['fk_contratdet'];

//    $fichinterline->comLigneId = $_REQUEST['comLigneId'];

    if (isset($_REQUEST['comLigneId']) && $_REQUEST['comLigneId'] != $fichinterline->comLigneId) {
        $fichinterline->comLigneId = $_REQUEST['comLigneId'];
        $ligneCommande = new OrderLine($db);
        $ligneCommande->fetch($fichinterline->comLigneId);
//        die($ligneCommande->fk_product);
        if ($ligneCommande->fk_product)
            $fichinterline->typeIntervProd = $ligneCommande->fk_product;
    }


    $result = $fichinterline->update($user);
    $fichinter->majPrixDi();


    if ($_REQUEST['lang_id']) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    fichinter_create($db, $fichinter, $fichinter->modelpdf, $outputlangs);
}

/*
 *  Supprime une ligne d'intervention SANS confirmation
 */
if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'deleteline' && $user->rights->synopsisficheinter->creer && !$conf->global->PRODUIT_CONFIRM_DELETE_LINE) {
    $fichinterline = new SynopsisfichinterLigne($db);
    if ($fichinterline->fetch($_REQUEST['ligne']) <= 0) {
        dol_print_error($db, "Erreur FIdet delete");
        exit;
    }
    $result = $fichinterline->delete_line();
    $fichinter = new Synopsisfichinter($db);
    if ($fichinter->fetch($fichinterline->fk_fichinter) <= 0) {
        dol_print_error($db, "Erreur FI delete");
        exit;
    }
    if ($_REQUEST['lang_id']) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    fichinter_create($db, $fichinter, $fichinter->modelpdf, $outputlangs);
}

/*
 *  Supprime une ligne d'intervention AVEC confirmation
 */
if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'confirm_deleteline' && $_REQUEST['confirm'] == 'yes' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE) {
    if ($user->rights->synopsisficheinter->creer) {
        $fichinterline = new SynopsisfichinterLigne($db);
        if ($fichinterline->fetch($_REQUEST['ligne']) <= 0) {
            dol_print_error($db, "Erreur FIdet delete confirm");
            exit;
        }
        $result = $fichinterline->delete_line();
        $fichinter = new Synopsisfichinter($db);
        if ($fichinter->fetch($fichinterline->fk_fichinter) <= 0) {
            dol_print_error($db, "Erreur FI deleteconfirm");
            exit;
        }
        if ($_REQUEST['lang_id']) {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($_REQUEST['lang_id']);
        }
        fichinter_create($db, $fichinter, $fichinter->modelpdf, $outputlangs);
    }
    Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $_REQUEST['id']);
    exit;
}

/*
 * Ordonnancement des lignes
 */

if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'up' && $user->rights->synopsisficheinter->creer) {
    $fichinter = new Synopsisfichinter($db);
    $fichinter->fetch($_REQUEST['id']);
    $fichinter->line_up($_REQUEST['rowid']);
    if ($_REQUEST['lang_id']) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    fichinter_create($db, $fichinter, $fichinter->modelpdf, $outputlangs);
    Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $_REQUEST["id"] . '#' . $_REQUEST['rowid']);
    exit;
}

if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'down' && $user->rights->synopsisficheinter->creer) {
    $fichinter = new Synopsisfichinter($db);
    $fichinter->fetch($_REQUEST['id']);
    $fichinter->line_down($_REQUEST['rowid']);
    if ($_REQUEST['lang_id']) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    fichinter_create($db, $fichinter, $fichinter->modelpdf, $outputlangs);
    Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $_REQUEST["id"] . '#' . $_REQUEST['rowid']);
    exit;
}


/*
 * View
 */

$html = new Form($db);
$formfile = new FormFile($db);
$js = "<script type='text/javascript' src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/jquery.rating.js'></script>";
$js .= '<link rel="stylesheet"  href="' . DOL_URL_ROOT . '/Synopsis_Common/css/jquery.rating.css" type="text/css" ></link>';
$js .= "<style> textarea{ width: 80%; height: 10em;}</style>";
$js .= "<script type='text/javascript' src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/jquery.tooltip.js'></script>";

launchRunningProcess($db, 'Fichinter', $_GET['id']);

llxHeader($js, "Fiche intervention");

echo $htmlRedirect;

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'create') {
    /*
     * Mode creation
     * Creation d'une nouvelle fiche d'intervention
     */
    if ($_REQUEST["socid"] > 0) {
        $societe = new Societe($db);
        $societe->fetch($_REQUEST["socid"]);
    }

    load_fiche_titre($langs->trans("AddIntervention"));

    if ($mesg)
        print $mesg . '<br>';

    if (!$conf->global->FICHEINTER_ADDON) {
        dol_print_error($db, $langs->trans("Error") . " " . $langs->trans("Error_FICHEINTER_ADDON_NotDefined"));
        exit;
    }

    $fichinter = new Synopsisfichinter($db);
    $fichinter->date = time();
    if ($fichinterid)
        $result = $fichinter->fetch($fichinterid);

    $obj = $conf->global->FICHEINTER_ADDON;
    $obj = "mod_" . $obj;

    include_once(DOL_DOCUMENT_ROOT . "/core/modules/fichinter/" . $obj . ".php");
    $modFicheinter = new $obj;
    $numpr = $modFicheinter->getNextValue($societe, $fichinter);

    if ($_REQUEST["socid"]) {
        print "<form name='fichinter' action=\"card.php\" method=\"post\">";

        print '<table class="border" cellpadding=15 width="100%">';

        print '<input type="hidden" name="socid" value=' . $_REQUEST["socid"] . '>';
        print "<tr><th class='ui-widget-header ui-state-default'>" . $langs->trans("Company") . "</th>
                   <td colspan=3 class='ui-widget-content'>" . ($societe->id > 0 ? $societe->getNomUrl(1) : "") . "</td></tr>";

        print "<tr><th class='ui-widget-header ui-state-default'>" . $langs->trans("Date") . "</th>
                   <td colspan=3 class='ui-widget-content'>";
        $html->select_date($db->jdate(time()), "p", '', '', '', 'fichinter');
        print "</td></tr>";

        print "<input type=\"hidden\" name=\"action\" value=\"add\">";

        print "<tr><th class=\"ui-widget-header ui-state-default\">" . $langs->trans("Ref") . "</th>";
        print "<td colspan=3 class='ui-widget-content'><input name=\"ref\" value=\"$numpr\"></td></tr>\n";

        if ($conf->projet->enabled) {
            // Projet associe
            $langs->load("project");

            print '<tr><th valign="top" class="ui-wdiget-header ui-state-default">' . $langs->trans("Project") . '</th>
                       <td colspan=3 class="ui-widget-content">';

            if ($_REQUEST["socid"])
                $numprojet = $societe->has_projects();

            if (!$numprojet) {
                print '<table class="nobordernopadding" width="100%">';
                print '<tr><td class="ui-widget-content" width="130">' . $langs->trans("NoProject") . '</td>';

                if ($user->rights->projet->creer) {
                    print '<td class="ui-widget-content"><a href=' . DOL_URL_ROOT . '/projet/card.php?socid=' . $societe->id . '&action=create>' . $langs->trans("Add") . '</a></td>';
                }
                print '</tr></table>';
            } else {
                require_once(DOL_DOCUMENT_ROOT."/core/class/html.formprojet.class.php");
                $form = new FormProjets($db);
                $form->select_projects($societe->id, '', 'projetidp');
            }
            print '</td></tr>';
        }
//lié a un contrat de maintenance ou mixte
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "contrat WHERE extraparams in (3,7) AND fk_soc =" . $societe->id;
        $sql = $db->query($requete);
        if ($db->num_rows($sql) > 0) {
            print '<th class="ui-widget-header ui-state-default">' . $langs->trans("Contrat de maintenance") . '</th>';
            print '<td colspan="3" class="ui-widget-content">';
            print "<SELECT id='fk_contrat' name='fk_contrat'>";
            print "<option value='-1'>S&eacute;lectionner -></option>";
            while ($res = $db->fetch_object($sql)) {
                if ($_REQUEST['fk_contrat'] == $res->rowid) {
                    print "<option SELECTED value='" . $res->rowid . "'>" . $res->ref . "</option>";
                } else {
                    print "<option value='" . $res->rowid . "'>" . $res->ref . "</option>";
                }
            }
            print "</SELECT>";
        }

        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "commande WHERE fk_soc =" . $societe->id." AND fk_statut < 3";
        $sql = $db->query($requete);
        if ($db->num_rows($sql) > 0) {
            print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Commande") . '</th>';
            print '<td colspan="3" class="ui-widget-content">';
            print "<SELECT id='fk_commande' name='fk_commande'>";
            print "<option value='-1'>S&eacute;lectionner -></option>";
            while ($res = $db->fetch_object($sql)) {
                if ($_REQUEST['fk_commande'] == $res->rowid) {
                    print "<option SELECTED value='" . $res->rowid . "'>" . $res->ref . " ".dol_print_date($db->jdate($res->date_commande))."</option>";
                } else {
                    print "<option value='" . $res->rowid . "'>" . $res->ref ." ".dol_print_date($db->jdate($res->date_commande))."</option>";
                }
            }
            print "</SELECT>";
        }

        // Model
        /* print '<tr>';
          print '<th class="ui-state-default ui-widget-header">' . $langs->trans("DefaultModel") . '</th>';
          print '<td colspan="3" class="ui-widget-content">';
          $model = new ModelePDFFicheinter();
          $liste = $model->liste_modeles($db);
          $html->select_array('model', $liste, $conf->global->FICHEINTER_ADDON_PDF);
          print "</td></tr>";


         */
        $dsc = '';
        if (isset($_REQUEST['fk_contratdet'])) {
            require_once DOL_DOCUMENT_ROOT . "/Synopsis_Contrat/class/contrat.class.php";
            $ln = new Synopsis_ContratLigne($db);
            $ln->fetch($_REQUEST['fk_contratdet']);
            $dsc = $ln->getTitreInter();
            echo '<input type="hidden" name="fk_contratdet" value="' . $_REQUEST['fk_contratdet'] . '"/>';
        }
        print '<tr><th valign="top" class="ui-widget-header ui-state-default">' . $langs->trans("Titre") . '</td>';
        print "<td colspan=3 class='ui-widget-content'>";

        if (isset($conf->fckeditor->enabled) && $conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_SOCIETE) {
            // Editeur wysiwyg
            require_once(DOL_DOCUMENT_ROOT . "/core/class/doleditor.class.php");
            $doleditor = new DolEditor('description', '', 280, 'dol_notes', 'In', true);
            $doleditor->Create();
        } else {
            print '<textarea name="description" wrap="soft" cols="70" rows="12">' . $dsc . '</textarea>';
        }

        print '</td></tr>';

        /*
          //Extra Field
          $requete = "SELECT *
          FROM ".MAIN_DB_PREFIX."synopsisfichinter_extra_key
          WHERE active = 1
          AND (isQuality is NULL or isQuality <> 1)
          AND isInMainPanel = 1
          ORDER BY rang, label";
          $sql = $db->query($requete);
          $modulo = false;
          while ($res = $db->fetch_object($sql)) {
          $colspan = 1;
          $modulo = !$modulo;
          if ($res->fullLine == 1) {
          $modulo = true;
          $colspan = 3;
          }
          if ($modulo)
          print "<tr>";
          if ($res->fullLine == 1)
          $modulo = !$modulo;

          print "<th valign='top' class='ui-widget-header ui-state-default'>" . $res->label;
          switch ($res->type) {
          case "date": {
          print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><input type='text' name='extraKey-" . $res->id . "' class='datePicker'>";
          print "<input type='hidden' name='type-" . $res->id . "' value='date'>";
          }
          break;
          case "textarea": {
          print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><textarea name='extraKey-" . $res->id . "'></textarea>";
          print "<input type='hidden' name='type-" . $res->id . "' value='comment'>";
          }
          break;
          default:
          case "text": {
          print '<td colspan="' . $colspan . '" valign="middle" class="ui-widget-content"><input type="text" name="extraKey-' . $res->id . '">';
          print "<input type='hidden' name='type-" . $res->id . "' value='text'>";
          }
          break;
          case "datetime": {
          print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><input type='text' name='extraKey-" . $res->id . "' class='dateTimePicker'>";
          print "<input type='hidden' name='type-" . $res->id . "' value='datetime'>";
          }
          break;
          case "checkbox": {
          print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><input type='checkbox'  name='extraKey-" . $res->id . "'>";
          print "<input type='hidden' name='type-" . $res->id . "' value='checkbox'>";
          }
          break;
          case "3stars": {
          print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'>&nbsp;&nbsp;";
          print starratingPhp("extraKey-" . $res->id, ($res->extra_value == 'Moyen' ? 1 : ($res->extra_value == 'Non' ? 0 : ($res->extra_value ? 1 : -1))), 3, $iter = 1);
          print "<input type='hidden' name='type-" . $res->id . "' value='3stars'>";
          }
          break;
          case "5stars": {
          print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'>";
          //                    print "<input type='checkbox' ".($res->extra_value==1?'CHECKED':"")."  name='extraKey-".$res->id."'>";
          print starratingPhp("extraKey-" . $res->id, "", 5, $iter = 1);
          print "<input type='hidden' name='type-" . $res->id . "' value='5stars'>";
          }
          break;
          case "radio": {
          print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'>";
          $requete = "SELECT * FROM ".MAIN_DB_PREFIX."synopsisfichinter_extra_values_choice WHERE key_refid = " . $res->id;
          $sql1 = $db->query($requete);
          if ($db->num_rows($sql1) > 0) {
          print "<table width=100%>";
          while ($res1 = $db->fetch_object($sql1)) {
          print "<tr><td width=100%>" . $res1->label . "<td>";
          print "<input type='radio' value='" . $res1->value . "' name='extraKey-" . $res->id . "'>";
          }
          print "</table>";
          }
          print "<input type='hidden' name='type-" . $res->id . "' value='radio'>";
          }
          break;
          }
          }
          print <<<EOF
          <script>
          jQuery(document).ready(function(){
          jQuery.datepicker.setDefaults(jQuery.extend({showMonthAfterYear: false,
          dateFormat: 'dd/mm/yy',
          changeMonth: true,
          changeYear: true,
          showButtonPanel: true,
          buttonImage: 'cal.png',
          buttonImageOnly: true,
          showTime: false,
          duration: '',
          constrainInput: false,}, jQuery.datepicker.regional['fr']));
          jQuery('.datePicker').datepicker();
          jQuery('.dateTimePicker').datepicker({showTime:true});
          });
          </script>
          EOF; */
        print '<tr><td colspan="4" align="center" class="ui-state-default ui-widget-header">';
        print '<input type="submit" class="button" value="' . $langs->trans("CreateDraftIntervention") . '">';
        print '</td></tr>';

        print '</table>';
        print '</form>';
    } else if (isset($_REQUEST["action"]) && $_REQUEST['action'] == "create" && $_REQUEST['comLigneId'] > 0) {
        require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
        $socid = false;
        $requete = "SELECT fk_soc,
                           " . MAIN_DB_PREFIX . "commande.rowid
                      FROM " . MAIN_DB_PREFIX . "commandedet,
                           " . MAIN_DB_PREFIX . "commande
                     WHERE " . MAIN_DB_PREFIX . "commandedet.fk_commande = " . MAIN_DB_PREFIX . "commande.rowid
                       AND " . MAIN_DB_PREFIX . "commandedet.rowid = " . $_REQUEST['comLigneId'];
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $socid = $res->fk_soc;
        $societe = new Societe($db);
        $societe->fetch($socid);

        print "<form name='synopsisdemandeinterv' action=\"card.php\" method=\"post\">";
        print "<input type='hidden' name='action' value='add'>";
        print "<input type='hidden' name='comLigneId' value='" . $_REQUEST['comLigneId'] . "'>";
        print "<input type='hidden' name='fk_commande' value='" . $res->rowid . "'>";
        print '<table cellpadding=15 class="border" width="1150">';

        print '<input type="hidden" name="socid" value=' . $socid . '>';
        print "<tr><th class='ui-widget-header ui-state-default'>" . $langs->trans("Company") . "</th>
                   <td colspan=3 class='ui-widget-content'>" . $societe->getNomUrl(1) . "</td></tr>";

        $com = new Commande($db);
        $com->fetch($res->rowid);
        print "<tr><th class='ui-widget-header ui-state-default'>" . $langs->trans("order") . "</th>
                   <td colspan=3 class='ui-widget-content'>" . $com->getNomUrl(1) . "</td></tr>";

        if ($_REQUEST['userid'] > 0) {
            $tmpUser = new User($db);
            $tmpUser->id = $_REQUEST['userid'];
            $tmpUser->fetch($_REQUEST['userid']);
            print "<tr><th class='ui-widget-header ui-state-default'>Attribu&eacute; &agrave;</th>
                       <td colspan=3 class='ui-widget-content'>" . $tmpUser->getNomUrl(1) . "</td></tr>";
            print "<input type=\"hidden\" name=\"userid\" value=\"" . $_REQUEST['userid'] . "\">";
        }


        print "<tr><th class='ui-widget-header ui-state-default'>" . $langs->trans("Date") . "</th>
                   <td colspan=3 class='ui-widget-content'>";
        $tmpDate = "";
        if ($_REQUEST['datei'] && preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/", $_REQUEST['datei'], $arr)) {
            $tmpDate = mktime(0, 0, 0, $arr[2], $arr[1], $arr[3]);
        }
        $html->select_date($db->jdate($tmpDate), "p", '', '', '', 'synopsisdemandeinterv');
        print "</td></tr>";


        print "<tr><th class='ui-widget-header ui-state-default'>" . $langs->trans("Ref") . "</th>";
        print "    <td colspan=3 class='ui-widget-content'><input name=\"ref\" value=\"" . $numpr . "\"></td></tr>\n";

        if ($conf->projet->enabled) {
            // Projet associe
            $langs->load("project");

            print '<tr><th valign="top" class="ui-widget-header ui-state-default">' . $langs->trans("Project") . '</td>
                       <td colspan=3 class="ui-widget-content">';

            if ($socid)
                $numprojet = $societe->has_projects();

            if (!$numprojet) {
                print '<table class="nobordernopadding" width="100%">';
                print '<tr><td width="130"  class="ui-widget-content">' . $langs->trans("NoProject") . '</td>';

                if ($user->rights->projet->creer) {
                    print '<td class="ui-widget-content"><a href=' . DOL_URL_ROOT . '/projet/card.php?socid=' . $societe->id . '&action=create>' . $langs->trans("Add") . '</a></td>';
                }
                print '</tr></table>';
            } else {
                select_projects($societe->id, '', 'projetidp');
            }
            print '</td></tr>';
        }

        // Model
        print '<tr>';
        print '<th class="ui-widget-header ui-state-default">' . $langs->trans("DefaultModel") . '</th>';
        print '<td colspan="3" class="ui-widget-content">';
        $liste = ModelePDFFicheinter::liste_modeles($db);
        $html->select_array('model', $liste, $conf->global->FICHEINTER_ADDON_PDF);
        print "</td></tr>";
        print '<tr><th valign="top" class="ui-widget-header ui-state-default">' . $langs->trans("Description") . '</th>';
        print "<td colspan=3 class='ui-widget-content'>";

        if (isset($conf->fckeditor->enabled) && $conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_SOCIETE) {
            // Editeur wysiwyg
            require_once(DOL_DOCUMENT_ROOT . "/core/class/doleditor.class.php");
            $doleditor = new DolEditor('description', '', 280, 'dol_notes', 'In', true);
            $doleditor->Create();
        } else {
            print '<textarea name="description" wrap="soft" cols="70" rows="12"></textarea>';
        }

        print '</td></tr>';
//Extra Field
        $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_key
                     WHERE active = 1
                       AND (isQuality is NULL OR isQuality <> 1)
                       AND isInMainPanel = 1
                  ORDER BY rang, label";
        $sql = $db->query($requete);
        $modulo = false;
        while ($res = $db->fetch_object($sql)) {
            $colspan = 1;
            $modulo = !$modulo;
            if ($res->fullLine == 1) {
                $modulo = true;
                $colspan = 3;
            }
            if ($modulo
            )
                print "<tr>";
            if ($res->fullLine == 1)
                $modulo = !$modulo;

            print "<th valign='top' class='ui-widget-header ui-state-default'>" . $res->label;

            switch ($res->type) {
                case "date": {
                        print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><input type='text' name='extraKey-" . $res->id . "' class='datePicker'>";
                        print "<input type='hidden' name='type-" . $res->id . "' value='date'>";
                    }
                    break;
                case "textarea": {
                        print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><textarea name='extraKey-" . $res->id . "'></textarea>";
                        print "<input type='hidden' name='type-" . $res->id . "' value='comment'>";
                    }
                    break;
                default:
                case "text": {
                        print '<td colspan="' . $colspan . '" valign="middle" class="ui-widget-content"><input type="text" name="extraKey-' . $res->id . '">';
                        print "<input type='hidden' name='type-" . $res->id . "' value='".$res->type."'>";
                    }
                    break;
                case "datetime": {
                        print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><input type='text' name='extraKey-" . $res->id . "' class='dateTimePicker'>";
                        print "<input type='hidden' name='type-" . $res->id . "' value='datetime'>";
                    }
                    break;
                case "checkbox": {
                        print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><input type='checkbox'  name='extraKey-" . $res->id . "'>";
                        print "<input type='hidden' name='type-" . $res->id . "' value='checkbox'>";
                    }
                    break;
                case "3stars": {
                        print "<td colspan='" . $colspan . "'  valign='middle' class='ui-widget-content'>";
//                    print "<input type='checkbox' ".($res->extra_value==1?'CHECKED':"")."  name='extraKey-".$res->id."'>";
                        print starratingPhp("extraKey-" . $res->id, ($res->extra_value == 'Moyen' ? 1 : ($res->extra_value == 'Non' ? 0 : ($res->extra_value ? 1 : -1))), 3, $iter = 1);
                        print "<input type='hidden' name='type-" . $res->id . "' value='3stars'>";
                    }
                    break;
                case "5stars": {
                        print "<td colspan='" . $colspan . "'  valign='middle' class='ui-widget-content'>";
//                    print "<input type='checkbox' ".($res->extra_value==1?'CHECKED':"")."  name='extraKey-".$res->id."'>";
                        print starratingPhp("extraKey-" . $res->id, $res->extra_value, 5, $iter = 1);
                        print "<input type='hidden' name='type-" . $res->id . "' value='5stars'>";
                    }
                    break;
                case "radio": {
                        print "<td colspan='" . $colspan . "'  valign='middle' class='ui-widget-content'>";
                        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_values_choice WHERE key_refid = " . $res->id;
                        $sql1 = $db->query($requete);
                        if ($db->num_rows($sql1) > 0) {
                            print "<table width=100%>";
                            while ($res1 = $db->fetch_object($sql1)) {
                                print "<tr><td width=100%>" . $res1->label . "<td>";
                                print "<input type='radio' value='" . $res1->value . "' name='extraKey-" . $res->id . "'>";
                            }
                            print "</table>";
                        }
                        print "<input type='hidden' name='type-" . $res->id . "' value='radio'>";
                    }
                    break;
            }
        }
        print <<<EOF
<script>
jQuery(document).ready(function(){
        jQuery.datepicker.setDefaults(jQuery.extend({showMonthAfterYear: false,
                        dateFormat: 'dd/mm/yy',
                        changeMonth: true,
                        changeYear: true,
                        showButtonPanel: true,
                        buttonImage: 'cal.png',
                        buttonImageOnly: true,
                        showTime: false,
                        duration: '',
                        constrainInput: false,}, jQuery.datepicker.regional['fr']));
        jQuery('.datePicker').datepicker();
        jQuery('.dateTimePicker').datepicker({showTime:true});
});
</script>
EOF;

        /*
         * Ajouter une ligne
         */
        print "</table>";
        print "<table width=1150>";
        print '<tr class="liste_titre">';
        print '<td>';
        print '<a name="add"></a>'; // ancre
        print $langs->trans('Description') . '</td>';
        print '<td>' . $langs->trans('Produit') . '</td>';
        print '<td>' . $langs->trans('Type') . '</td>';
        print '<td>' . $langs->trans('Date') . '</td>';
        print '<td>' . $langs->trans('Duration') . '</td>';
        print '<td>' . $langs->trans('Forfait') . '</td>';
        print '<td>' . $langs->trans('PU HT') . '</td>';
        print '<td>' . $langs->trans('Qt&eacute;') . '</td>';

        print "</tr>\n";

        // Ajout ligne d'DI

        require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
        $var = true;
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "commandedet WHERE rowid = " . $_REQUEST['comLigneId'];
        $sql2 = $db->query($requete);
        $res2 = $db->fetch_object($sql2);

        print '<tr ' . $bc[$var] . ">\n";
        print '<td width=340>';
        // editeur wysiwyg
        if (isset($conf->fckeditor->enabled) && $conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS) {
            require_once(DOL_DOCUMENT_ROOT . "/core/class/doleditor.class.php");
            $doleditor = new DolEditor('np_desc', '', 100, 'dol_details');
            $doleditor->Create();
        } else {
            print '<textarea style="width: 100%!important; height: 2em!important;" class="flat" cols="40" name="np_desc" rows="' . ROWS_2 . '">' . $res2->description . '</textarea>';
        }
        print '</td>';
        //Ref produit
        print '<td>';
        if ($res2->fk_product > 0) {
            $tmpProd = new Product($db);
            $tmpProd->fetch($res2->fk_product);
            print $tmpProd->getNomUrl(1);
        }
        print '</td>';
        print "<td><SELECT name='fk_typeinterv'>";
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv WHERE active = 1 ORDER BY rang";
        $sql3 = $db->query($requete);
        print "<OPTION value='-1'>Selectionner-></OPTION>";
        $dfltPrice = 0;
        while ($res3 = $db->fetch_object($sql3)) {
            if ($res3->default == 1) {
                print "<OPTION SELECTED value='" . $res3->id . "'>" . $res3->label . "</OPTION>";
            } else {
                print "<OPTION value='" . $res3->id . "'>" . $res3->label . "</OPTION>";
            }
        }
        print "</SELECT></td>";
        // Date d'DI
        print '<td>';
        $html->select_date($db->jdate(time()), 'di', 0, 0, 0, "addinter");
        print '</td>';

        // Duree
        print '<td>';
        $html->select_duration('duration');
        print '</td>';


        // Forfait
        print '<td width=32 align=center>';
        print "<input type='checkbox' name='isForfait'>";
        print '</td>';

        // pu_ht
        print '<td>';
        print "<input size=6 type='text' style='text-align:center' name='pu_ht' value='" . round($res2->subprice * 100) / 100 . "'>";
        print "<span style='display:none;' id='addDfltPrice'><br/></span>";
        print '</td>';

        // qte
        print '<td>';
        print "<input size=3 type='text' style='text-align:center'  name='qte' value='" . $res2->qty . "'>";
        print '</td>';


        print '</tr>';


        print '<tr><th colspan="9" align="center" class="ui-widget-header ui-state-default">';
        print '<input type="submit" class="button" value="' . $langs->trans("CreateDraftDI") . '">';
        print '</td></tr>';

        print '</table>';
        print '</form>';
    } else {
        print "<form name='fichinter' action=\"card.php\" method=\"get\">";
        print '<table class="border" width="100%" cellpadding=15>';
        print "<tr><th class=\"ui-widget-header ui-state-default\">" . $langs->trans("Company") . "</td><td class=\"ui-widget-content\">";
        print $html->select_company('', 'socid', '', 1);
        print "</td></tr>";
        print '<tr><td colspan="2" align="center"  class="ui-widget-content">';
        print "<input type=\"hidden\" name=\"action\" value=\"create\">";
        print '<input type="submit" class="button" value="' . $langs->trans("CreateDraftIntervention") . '">';
        print '</td></tr>';
        print '</table>';
        print '</form>';
    }
} elseif ($_REQUEST["id"] > 0) {
    /*
     * Affichage en mode visu
     */
    $fichinter = new Synopsisfichinter($db);
    $result = $fichinter->fetch($_REQUEST["id"]);
    if (!$result > 0) {
//        dol_print_error($db);
        exit;
    }
    $fichinter->fetch_client();
    //saveHistoUser($fichinter->id, "FI", $fichinter->ref);

    if ($mesg)
        print $mesg . "<br>";

    $head = synopsisfichinter_prepare_head($fichinter);

    dol_fiche_head($head, 'card', $langs->trans("InterventionCard"));

    /*
     * Confirmation de la suppression de la fiche d'intervention
     */
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') {
        $html->form_confirm($_SERVER["PHP_SELF"] . '?id=' . $fichinter->id, $langs->trans('DeleteIntervention'), $langs->trans('ConfirmDeleteIntervention'), 'confirm_delete');
        print '<br>';
    }
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'rafraichePrixFI') {
        $fichinter->majPrixDi();
    }

    /*
     * Confirmation de la validation de la fiche d'intervention
     */
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'validate') {
        $html->form_confirm($_SERVER["PHP_SELF"] . '?id=' . $fichinter->id, $langs->trans('ValidateIntervention'), $langs->trans('ConfirmValidateIntervention'), 'confirm_validate');
        print '<br>';
    }

    /*
     * Confirmation de la suppression d'une ligne d'intervention
     */
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'ask_deleteline' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE) {
        $html->form_confirm($_SERVER["PHP_SELF"] . '?id=' . $fichinter->id . '&amp;ligne=' . $_REQUEST["ligne"], $langs->trans('DeleteInterventionLine'), $langs->trans('ConfirmDeleteInterventionLine'), 'confirm_deleteline');
        print '<br>';
    }

    print '<table class="border" cellpadding=15 width="100%">';

    // Ref
    print '<tr><th class="ui-widget-header ui-state-default" width="25%">' . $langs->trans("Ref") . '</th>
               <td colspan=1 class="ui-widget-content">' . $fichinter->getNomUrl(1) . '</td>';

    // Societe
    print "    <th class='ui-widget-header ui-state-default'>" . $langs->trans("Company") . "</th>
               <td colspan=1 class='ui-widget-content'>" . $fichinter->client->getNomUrl(1) . "</td></tr>";

//Ref contrat ou commande
    if ($fichinter->fk_contrat > 0) {
        print "<tr><th class='ui-widget-header ui-state-default'>Contrat</th>";
        require_once(DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");
        $contrat = new Contrat($db);
        $contrat->fetch($fichinter->fk_contrat);
        print "    <td class='ui-widget-content' colspan=3>" . $contrat->getNomUrl(1) . "</td> ";
    }
    if ($fichinter->fk_commande > 0) {
        print "<tr><th class='ui-widget-header ui-state-default'>Commande</th>";
        require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
        $com = new Commande($db);
        $com->fetch($fichinter->fk_commande);
        print "<td class='ui-widget-content'>" . $com->getNomUrl(1);
        /* print "<th class='ui-widget-header ui-state-default' width=20%>Pr&eacute;paration de commande";
          print "<td class='ui-widget-content'> ". $com->getNomUrl(1,5) ."</td> "; */
    }
    // Date
    print '<tr><th class="ui-widget-header ui-state-default">';
    print '<span style="color:red">' . $langs->trans('Date du rapport d interv.') . '</span>';
    if ((!isset($_REQUEST['action']) || $_REQUEST['action'] != 'editdate_delivery') && $fichinter->brouillon)
        print '<a href="' . $_SERVER["PHP_SELF"] . '?action=editdate_delivery&amp;id=' . $fichinter->id . '">' . img_edit($langs->trans('SetDateCreate'), 1) . '</a>';
    print '</th><td colspan="1" class="ui-widget-content">';
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'editdate_delivery') {
        print '<form name="editdate_delivery" action="' . $_SERVER["PHP_SELF"] . '?id=' . $fichinter->id . '" method="post">';
        print '<input type="hidden" name="action" value="setdate_delivery">';
        $html->select_date($db->jdate($fichinter->date), 'liv_', '', '', '', "editdate_delivery");
        print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
        print '</form>';
    } else {
        print dol_print_date($fichinter->date, 'day');
    }
    $fichinter->info($fichinter->id);
    print '    </td>';

    print '    <th class="ui-widget-header ui-state-default">Intervenant';
    print '    <td class="ui-widget-content">' . $fichinter->user_creation->getNomUrl(1);
    print '</tr>';

//Maintenance
//        if ($fichinter->fk_contrat > 0)
//        {
//            require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
//            $contrat = new Contrat($db);
//            $contrat->fetch($fichinter->fk_contrat);
//            print '<th class="ui-widget-header ui-state-default">'.$langs->trans("Contrat de maintenance").'</th>';
//            print '<td colspan="3" class="ui-widget-content">'.$contrat->getNomUrl(1);
//        }
    // Projet
    if ($conf->projet->enabled) {
        $langs->load("projects");
        print '<tr><th class="ui-widget-header ui-state-default">';
        print $langs->trans('Project') . '';
        $societe = new Societe($db);
        $societe->fetch($fichinter->socid);
        $numprojet = $societe->has_projects();
        if (!$numprojet) {
            print '</th><td colspan="3" class="ui-widget-content">';
            print $langs->trans("NoProject") . '&nbsp;&nbsp;';
            if ($fichinter->brouillon)
                print '<a class="butAction" href=../projet/card.php?socid=' . $societe->id . '&action=create>' . $langs->trans('AddProject') . '</a>';
            print '</td>';
        } else {
            if (($fichinter->statut == 0 || $user->rights->synopsisficheinter->modifAfterValid) && $user->rights->synopsisficheinter->creer) {
                if ((!isset($_REQUEST['action']) || $_REQUEST['action'] != 'classer') && $fichinter->brouillon)
                    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=classer&amp;id=' . $fichinter->id . '">' . img_edit($langs->trans('SetProject'), 1) . '</a>';
                print '</th><td colspan="3" class="ui-widget-content">';
                if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'classer') {
                    $html->form_project($_SERVER['PHP_SELF'] . '?id=' . $fichinter->id, $fichinter->socid, $fichinter->projetidp, 'projetidp');
                } else {
                    $html->form_project($_SERVER['PHP_SELF'] . '?id=' . $fichinter->id, $fichinter->socid, $fichinter->projetidp, 'none');
                }
                print '</td></tr>';
            } else {
                if (!empty($fichinter->projetidp)) {
                    $proj = new Project($db);
                    $proj->fetch($fichinter->projetidp);
                    print '<a href="../projet/card.php?id=' . $fichinter->projetidp . '" title="' . $langs->trans('ShowProject') . '">';
                    print $proj->title;
                    print '</a></th>';
                } else {
                    print '</th<<td colspan="3" class="ui-widget-content">&nbsp;</td>';
                }
            }
        }
        print '</tr>';
    }

    // Duration
    /*  $colspan=3;
      if ($user->rights->synopsisficheinter->voirPrix){ $colspan=1;}
      print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("TotalDuration").'</th><td colspan='.$colspan.' class="ui-widget-content">'.ConvertSecondToTime($fichinter->duree).'</td>';

      // Montant total
      if ($user->rights->synopsisficheinter->voirPrix){
      print '    <th class="ui-widget-header ui-state-default">'.$langs->trans("Total HT").'</th>
      <td colspan=1 class="ui-widget-content">'.price($fichinter->total_ht).' &euro;</td></tr>';
      } */


    // Description
    print '<tr><th class="ui-widget-header ui-state-default">';
    print $langs->trans('Titre');
    if ((!isset($_REQUEST['action']) || $_REQUEST['action'] != 'editdescription') && $fichinter->brouillon)
        print '<a href="' . $_SERVER["PHP_SELF"] . '?action=editdescription&amp;id=' . $fichinter->id . '">' . img_edit($langs->trans('SetDescription'), 1) . '</a>';
    print '</th><td colspan="3" class="ui-widget-content">';
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'editdescription') {
        print '<form name="editdescription" action="' . $_SERVER["PHP_SELF"] . '?id=' . $fichinter->id . '" method="post">';
        print '<input type="hidden" name="action" value="setdescription">';
        if (isset($conf->fckeditor->enabled) && $conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_SOCIETE) {
            // Editeur wysiwyg
            require_once(DOL_DOCUMENT_ROOT . "/core/class/doleditor.class.php");
            $doleditor = new DolEditor('description', $fichinter->description, 280, 'dol_notes', 'In', true);
            $doleditor->Create();
        } else {
            print '<textarea name="description" wrap="soft" cols="70" rows="12">' . dol_htmlentitiesbr_decode($fichinter->description) . '</textarea>';
        }
        print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
        print '</form>';
    } else {
        print nl2br($fichinter->description);
    }
    print '</td>';
    print '</tr>';

    // Description 2 debmod
    print '<tr><th class="ui-widget-header ui-state-default">';
    print $langs->trans('Description: non imprimable');
    if ((!isset($_REQUEST['action']) || $_REQUEST['action'] != 'editdescription2') && $fichinter->brouillon)
        print '<a href="' . $_SERVER["PHP_SELF"] . '?action=editdescription2&amp;id=' . $fichinter->id . '">' . img_edit($langs->trans('SetDescriptionP'), 1) . '</a>';
    print '</th><td colspan="3" class="ui-widget-content">';
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'editdescription2') {
        print '<form name="editdescription2" action="' . $_SERVER["PHP_SELF"] . '?id=' . $fichinter->id . '" method="post">';
        print '<input type="hidden" name="action" value="setdescription2">';
        if (isset($conf->fckeditor->enabled) && $conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_SOCIETE) {
            // Editeur wysiwyg
            require_once(DOL_DOCUMENT_ROOT . "/core/class/doleditor.class.php");
            $doleditor = new DolEditor('description2', $fichinter->note_private, 280, 'dol_notes', 'In', true);
            $doleditor->Create();
        } else {
            print '<textarea name="description2" wrap="soft" cols="70" rows="12">' . dol_htmlentitiesbr_decode($fichinter->note_private) . '</textarea>';
        }
        print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
        print '</form>';
    } else {
        print nl2br($fichinter->note_private);
    }
    print '</td>';
    print '</tr>';
    //finmod
    // Statut
    /* print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Status").'</th>
      <td colspan=1 class="ui-widget-content">'.$fichinter->getLibStatut(4).'</td>'; */

    // di
    if (isset($user->rights->synopsisdemandeinterv)) {
        print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("DI") . '</th>';
        print '<td colspan=1 class="ui-widget-content">';
        $tabDI = $fichinter->getDI();
        if (count($tabDI) > 0) {
            $requete = "SELECT *
                  FROM " . MAIN_DB_PREFIX . "synopsisdemandeinterv
                 WHERE rowid IN (" . implode(",", $tabDI) . ")";
            print "<table class='nobordernopadding' width=100%>";
            if ($resql = $db->query($requete)) {
                while ($res = $db->fetch_object($resql)) {
                    print "<tr><td class='ui-widget-content'><a href='" . DOL_URL_ROOT . "/synopsisdemandeinterv/card.php?id=" . $res->rowid . "'>" . img_object('', 'synopsisdemandeinterv@synopsisdemandeinterv') . " " . $res->ref . "</a></td></tr>";
                }
            }
            print "</table>";
        }
        print '</td></tr>';
    }


    // Extra
    $requete = "SELECT k.label,
                       k.type,
                       k.id,
                       v.extra_value,
                       k.fullLine
                  FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_key as k
             LEFT JOIN " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value as v ON v.extra_key_refid = k.id AND v.interv_refid = " . $fichinter->id . " AND typeI = 'FI'
                 WHERE k.active = 1
                   AND isInMainPanel = 1
                   AND (isQuality <> 1 OR isQuality is NULL)
                  ORDER BY rang, label";
    $sql = $db->query($requete);
    $modulo = false;
    while ($res = $db->fetch_object($sql)) {
        $colspan = 1;
        $modulo = !$modulo;
        if ($res->fullLine == 1) {
            $colspan = 3;
            $modulo = true;
        }
        if ($modulo)
            print "<tr>";
        if ($res->fullLine == 1)
            $modulo = !$modulo;

        print '<th class="ui-widget-header ui-state-default">' . $res->label;
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'editExtra-' . $res->id) {
            switch ($res->type) {
                case "date": {
                        print "<td colspan='" . $colspan . "'  valign='middle' class='ui-widget-content'><form action='card.php?id=" . $fichinter->id . "#anchor" . $res->id . "' method='POST'>";
                        print '<a name="anchor' . $res->id . '"></a>'; // ancre
                        print "<input type='hidden' name='action' value='editExtra'>&nbsp;&nbsp;<input type='text' value='" . $res->extra_value . "' name='extraKey-" . $res->id . "' class='datePicker'>";
                        print "<input type='hidden' name='type-" . $res->id . "' value='date'>&nbsp;&nbsp;&nbsp;<button class='ui-button'>OK</button></FORM>";
                    }
                    break;
                case "textarea": {
                        print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><form action='card.php?id=" . $fichinter->id . "#anchor" . $res->id . "' method='POST'><input type='hidden' name='action' value='editExtra'>";
                        print '<a name="anchor' . $res->id . '"></a>'; // ancre
                        print "&nbsp;&nbsp;<textarea name='extraKey-" . $res->id . "'>" . $res->extra_value . "</textarea>";
                        print "<input type='hidden' name='type-" . $res->id . "' value='comment'>&nbsp;&nbsp;&nbsp;<button class='ui-button'>OK</button></FORM>";
                    }
                    break;
                default:
                case "text": {
                        print '<td colspan="' . $colspan . '" valign="middle" class="ui-widget-content"><form action="card.php?id=' . $fichinter->id . '#anchor' . $res->id . '" method="POST"><input type="hidden" name="action" value="editExtra">';
                        print '<a name="anchor' . $res->id . '"></a>'; // ancre
                        print '&nbsp;&nbsp;<input value="' . $res->extra_value . '" type="text" name="extraKey-' . $res->id . '">';
                        print "<input type='hidden' name='type-" . $res->id . "' value='".$res->type."'>&nbsp;&nbsp;&nbsp;<button class='ui-button'>OK</button></FORM>";
                    }
                    break;
                case "datetime": {
                        print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><form action='card.php?id=" . $fichinter->id . "#anchor" . $res->id . "' method='POST'><input type='hidden' name='action' value='editExtra'>";
                        print '<a name="anchor' . $res->id . '"></a>'; // ancre
                        print "&nbsp;&nbsp;<input type='text' value='" . $res->extra_value . "' name='extraKey-" . $res->id . "' class='dateTimePicker'>";
                        print "<input type='hidden' name='type-" . $res->id . "' value='datetime'>&nbsp;&nbsp;&nbsp;<button class='ui-button'>OK</button></FORM>";
                    }
                    break;
                case "checkbox": {
                        print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><form action='card.php?id=" . $fichinter->id . "#anchor" . $res->id . "' method='POST'><input type='hidden' name='action' value='editExtra'>";
                        print '<a name="anchor' . $res->id . '"></a>'; // ancre
                        print "&nbsp;&nbsp;<input type='checkbox' " . ($res->extra_value == 1 ? 'CHECKED' : "") . "  name='extraKey-" . $res->id . "'>";
                        print "<input type='hidden' name='type-" . $res->id . "' value='checkbox'>&nbsp;&nbsp;&nbsp;<button class='ui-button'>OK</button></FORM>";
                    }
                    break;
                case "3stars": {
                        print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><form action='card.php?id=" . $fichinter->id . "#anchor" . $res->id . "' method='POST'><input type='hidden' name='action' value='editExtra'>&nbsp;&nbsp;";
                        print '<a name="anchor' . $res->id . '"></a>'; // ancre
//                    print "<input type='checkbox' ".($res->extra_value==1?'CHECKED':"")."  name='extraKey-".$res->id."'>";
                        print starratingPhp("extraKey-" . $res->id, ($res->extra_value == 'Moyen' ? 1 : ($res->extra_value == 'Non' ? 0 : ($res->extra_value ? 1 : -1))), 3, $iter = 1);
                        print "<input type='hidden' name='type-" . $res->id . "' value='3stars'>&nbsp;&nbsp;&nbsp;<button class='ui-button'>OK</button></FORM>";
                    }
                    break;
                case "5stars": {
                        print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><form action='card.php?id=" . $fichinter->id . "#anchor" . $res->id . "' method='POST'><input type='hidden' name='action' value='editExtra'>&nbsp;&nbsp;";
                        print '<a name="anchor' . $res->id . '"></a>'; // ancre
//                    print "<input type='checkbox' ".($res->extra_value==1?'CHECKED':"")."  name='extraKey-".$res->id."'>";
                        print starratingPhp("extraKey-" . $res->id, $res->extra_value, 5, $iter = 1);
                        print "<input type='hidden' name='type-" . $res->id . "' value='5stars'>&nbsp;&nbsp;&nbsp;<button class='ui-button'>OK</button></FORM>";
                    }
                    break;
                case "radio": {
                        print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><form action='card.php?id=" . $fichinter->id . "#anchor" . $res->id . "' method='POST'><input type='hidden' name='action' value='editExtra'>";
                        print '<a name="anchor' . $res->id . '"></a>'; // ancre
//                    print "<form action='card.php?id=".$fichinter->id."' method='POST'><input type='hidden' name='action' value='editExtra'>";                    $requete= "SELECT * FROM ".MAIN_DB_PREFIX."synopsisfichinter_extra_values_choice WHERE key_refid = ".$res->id;
                        $requete = "SELECT *
                          FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_values_choice
                         WHERE key_refid = " . $res->id;

                        $sql1 = $db->query($requete);
                        if ($db->num_rows($sql1) > 0) {
                            print "<table width=100%>";
                            while ($res1 = $db->fetch_object($sql1)) {
                                print "<tr><td width=100%>" . $res1->label . "<td>";
                                print "<input type='radio' value='" . $res1->value . "' name='extraKey-" . $res->id . "' ".($res->extra_value == $res1->value ? "checked='checked'" : "").">";
                            }
                            print "</table>";
                        }
                        print "<input type='hidden' name='type-" . $res->id . "' value='radio'>&nbsp;&nbsp;&nbsp;<button class='ui-button'>OK</button></FORM>";
                        //print "&nbsp;&nbsp;&nbsp;<button class='ui-button'>OK</button></form>";
                    }
                    break;
            }
        } else {
            if (($fichinter->statut == 0 || $user->rights->synopsisficheinter->modifAfterValid ) && $user->rights->synopsisficheinter->creer) {
                print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $_REQUEST['id'] . '&action=editExtra-' . $res->id . '#anchor' . $res->id . '">' . img_edit($langs->trans('Editer'), 1) . '</a>';
            }
            if ($res->type == 'checkbox') {
                print '    <td colspan="' . $colspan . '" class="ui-widget-content">';
                print '<a name="anchor' . $res->id . '"></a>'; // ancre
                print ($res->extra_value == 1 ? 'Oui' : 'Non');
            } else if ($res->type == 'radio') {
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_values_choice WHERE key_refid = " . $res->id . " AND value = '" . $res->extra_value . "'";
                $sql1 = $db->query($requete);
                $res1 = $db->fetch_object($sql1);
                print '    <td colspan="' . $colspan . '" class="ui-widget-content">';
                print '<a name="anchor' . $res->id . '"></a>'; // ancre
                print $res1->label;
            } else {
                print '    <td colspan="' . $colspan . '" class="ui-widget-content">';
                print '<a name="anchor' . $res->id . '"></a>'; // ancre
                print $res->extra_value;
            }
        }
    }
    print "</table><br>";
    print <<<EOF
<script>
jQuery(document).ready(function(){
        jQuery.datepicker.setDefaults(jQuery.extend({showMonthAfterYear: false,
                        dateFormat: 'dd/mm/yy',
                        changeMonth: true,
                        changeYear: true,
                        showButtonPanel: true,
                        buttonImage: 'cal.png',
                        buttonImageOnly: true,
                        showTime: false,
                        duration: '',
                        constrainInput: false,}, jQuery.datepicker.regional['fr']));
        jQuery('.datePicker').datepicker();
        jQuery('.dateTimePicker').datepicker({showTime:true});
});
</script>
EOF;

    /*
     * Lignes d'intervention
     */
    print '<table class="noborder" width="100%">';
//    $sql = 'SELECT ft.rowid, ft.description, ft.fk_synopsisdemandeinterv, ft.duree, ft.rang, t.id as typeId, t.label as typeinterv';
//    $sql.= ', ft.date as date_intervention, qte, pu_ht, total_ht, fk_commandedet, fk_contratdet, isForfait';
//    $sql.= ' FROM '.MAIN_DB_PREFIX.'synopsisdemandeintervdet as ft';
//    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."synopsisfichinter_c_typeInterv as t ON t.id = ft.fk_typeinterv ";
//    $sql.= ' WHERE ft.fk_synopsisdemandeinterv = ' . $synopsisdemandeintervid;
//    $sql.= ' ORDER BY ft.rang ASC, ft.rowid';


    $sql = 'SELECT ft.rowid,
                   ft.description,
                   ft.fk_fichinter,
                   ft.duree,
                   ft.rang,
                   f.label as typeinterv,
                   ft.fk_typeinterv,
                   ft.fk_depProduct,
                   qte,
                   pu_ht,
                   total_ht,
                   fk_commandedet,
                   fk_contratdet,
                   isForfait';
    $sql.= ', ft.date as date_intervention';
    $sql.= ' FROM ' . MAIN_DB_PREFIX . 'synopsis_fichinterdet as ft';
    $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv as f ON f.id = ft.fk_typeinterv ";
    $sql.= ' WHERE ft.fk_fichinter = ' . $fichinterid;
    $sql.= ' ORDER BY ft.rang ASC, ft.rowid';
    $resql = $db->query($sql);
    if ($resql) {
        $num = $db->num_rows($resql);
        $i = 0;

        if ($num) {
            print '<tr class="liste_titre">';
            print '<td>' . $langs->trans('Description') . '</td>';
            print '<td></td>';
            print '<td>' . $langs->trans('Type') . '</td>';
            print '<td>' . $langs->trans('Date') . '</td>';
            print '<td>' . $langs->trans('Duration') . '</td>';
            print '<td width="48" colspan="3">&nbsp;</td>';
            print "</tr>\n";
        }
        $var = true;
        while ($i < $num) {
            $objp = $db->fetch_object($resql);
            $var = !$var;

            // Ligne en mode visu
            if ((!isset($_REQUEST['action']) || $_REQUEST['action'] != 'editline') || $_REQUEST['ligne'] != $objp->rowid) {
                print '<tr ' . $bc[$var] . '>';
                print '<td colspan="2">';
                print '<a name="' . $objp->rowid . '"></a>'; // ancre pour retourner sur la ligne
                print nl2br($objp->description);


                print '<td width="150">' . $objp->typeinterv;
                if ($objp->fk_depProduct > 0) {
                    $tmpProdDep = new Product($db);
                    $tmpProdDep->fetch($objp->fk_depProduct);
                    print "&nbsp;&nbsp;" . $tmpProdDep->getNomUrl(1);
                }
                print '</td>';
                print '<td width="150">' . dol_print_date($db->jdate($objp->date_intervention), 'day') . '</td>';
                print '<td width="150">' . ConvertSecondToTime($objp->duree) . '</td>';

                print "</td>\n";


                // Icone d'edition et suppression
                if (($fichinter->statut == 0 || $user->rights->synopsisficheinter->modifAfterValid) && $user->rights->synopsisficheinter->creer) {
                    print '<td align="center">';
                    print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $fichinter->id . '&amp;action=editline&amp;ligne=' . $objp->rowid . '#' . $objp->rowid . '">';
                    print img_edit();
                    print '</a>';
                    print '</td>';
                    print '<td align="center">';
                    if ($conf->global->PRODUIT_CONFIRM_DELETE_LINE) {
                        if ($conf->use_javascript_ajax) {
                            $url = $_SERVER["PHP_SELF"] . '?id=' . $fichinter->id . '&ligne=' . $objp->rowid . '&action=confirm_deleteline&confirm=yes';
                            print '<a href="#" onClick="dialogConfirm(\'' . $url . '\',\'' . $langs->trans('ConfirmDeleteInterventionLine') . '\',\'' . $langs->trans("Yes") . '\',\'' . $langs->trans("No") . '\',\'deleteline' . $i . '\')">';
                            print img_delete();
                        } else {
                            print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $fichinter->id . '&amp;action=ask_deleteline&amp;ligne=' . $objp->rowid . '">';
                            print img_delete();
                        }
                    } else {
                        print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $fichinter->id . '&amp;action=deleteline&amp;ligne=' . $objp->rowid . '">';
                        print img_delete();
                    }
                    print '</a></td>';
                    if ($num > 1) {
                        print '<td align="center">';
                        if ($i > 0) {
                            print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $fichinter->id . '&amp;action=up&amp;rowid=' . $objp->rowid . '">';
                            print img_up();
                            print '</a>';
                        }
                        if ($i < $num - 1) {
                            print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $fichinter->id . '&amp;action=down&amp;rowid=' . $objp->rowid . '">';
                            print img_down();
                            print '</a>';
                        }
                        print '</td>';
                    }
                } else {
                    print '<td colspan="3">&nbsp;</td>';
                }

                print '</tr>';
                if ($fichinter->fk_contrat && $afficherLigneContrat) {
                    require_once(DOL_DOCUMENT_ROOT . "/core/lib/contract.lib.php");
                    $contrat = getContratObj($fichinter->fk_contrat);
                    $type = $contrat->getTypeContrat_noLoad($fichinter->fk_contrat);
                    if ($type == 7) {
                        $contrat->fetch($fichinter->fk_contrat);
                        $contrat->fetch_lines(true);
                        print "<tr id='contratLigne" . $val->id . "' " . $bc[$var] . ">";
                        print "<td id='showDetail' class='showDetail'><span style='float: left;' class='ui-icon ui-icon-search'></span>D&eacute;tail<td colspan=10><table style='display: none;'>";
                        foreach ($contrat->lines as $key => $val) {
                            if ($val->id == $objp->fk_contratdet) {
                                print "<tr><td>";
                                print "<ul style='list-style: none; padding-left: 0px; padding-top:0; margin-bottom:0; margin-top: 0px;'>";
                                $tmp = preg_replace('/tt' . $objp->fk_contratdet . '/', "ttedit" . $objp->fk_contratdet . "", $contrat->display1Line($contrat, $val));
                                print $tmp;
                                print "</ul>";
                            }
                        }
                        print "</table>";
                    }
                }
            }
            $fichinter->info($fichinter->id);

            // Ligne en mode update
            //
            if (($fichinter->statut == 0 || $user->rights->synopsisficheinter->modifAfterValid) && isset($_REQUEST['action']) && $_REQUEST['action'] == 'editline' && $user->rights->synopsisficheinter->creer && $_REQUEST["ligne"] == $objp->rowid) {
                print "</table>";
                print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $fichinter->id . '#' . $objp->rowid . '" id="modForm" method="post">';
                print "<table class='nobordernopadding' width=100%>";
                print '<input type="hidden" name="action" value="updateligne">';
                print '<input type="hidden" name="fichinterid" value="' . $fichinter->id . '">';
                print '<input type="hidden" name="ligne" value="' . $_REQUEST["ligne"] . '">';
                print '<tr ' . $bc[$var] . '>';
                print '<td>';
                print '<a name="' . $objp->rowid . '"></a>'; // ancre pour retourner sur la ligne
                // editeur wysiwyg
                if (isset($conf->fckeditor->enabled) && $conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS) {
                    require_once(DOL_DOCUMENT_ROOT . "/core/class/doleditor.class.php");
                    $doleditor = new DolEditor('desc', $objp->description, 164, 'dol_details');
                    $doleditor->Create();
                } else {
                    print '<textarea name="desc" cols="70" class="flat" rows="' . ROWS_2 . '">' . preg_replace('/<br[ ]*\/?>/i', '', $objp->description) . '</textarea>';
                }
                print '</td>';
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv WHERE active = 1 ORDER by rang";
                $sql1 = $db->query($requete);
                print "<td><SELECT name='fk_typeinterv'>";
                print "<OPTION value='-1'>Selectionner-></OPTION>";
                while ($res1 = $db->fetch_object($sql1)) {
                    if (!$fichinter->fk_contrat || $res1->id == 14 || $res1->id == 20 || $res1->id == 21 || $res1->id == 22 || $res1->id == 26 || $res1->id == 27 || $res1->id == 28 || $res1->id == 29) {
                        //Si déplacement OptGroup
                        if (0 && $res1->isDeplacement == 1) {
                            $requete1 = "SELECT " . MAIN_DB_PREFIX . "product.label,
					  " . MAIN_DB_PREFIX . "product.ref,
					  " . MAIN_DB_PREFIX . "product.rowid
				     FROM " . MAIN_DB_PREFIX . "product,
					  " . MAIN_DB_PREFIX . "synopsisfichinter_User_PrixDepInterv
				    WHERE user_refid = " . $fichinter->user_creation->id . " AND " . MAIN_DB_PREFIX . "product.rowid = fk_product";
                            $sql2 = $db->query($requete1);
                            if ($db->num_rows($sql2) > 0) {
                                print "<OPTGROUP label='" . $res1->label . "'>";
                                while ($res2 = $db->fetch_object($sql2)) {
                                    if ($res2->rowid == $objp->fk_depProduct) {
                                        print "<OPTION SELECTED value='dep-" . $res2->rowid . "'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $res2->ref . " " . $res2->label . "</OPTION>";
                                    } else {
                                        print "<OPTION value='dep-" . $res2->rowid . "'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $res2->ref . " " . $res2->label . "</OPTION>";
                                    }
                                }
                                print "</OPTGROUP>";
                            } else {
                                if ($res1->id == $objp->fk_typeinterv) {
                                    print "<OPTION SELECTED value='" . $res1->id . "'>" . $res1->label . "</OPTION>";
                                } else {
                                    print "<OPTION value='" . $res1->id . "'>" . $res1->label . "</OPTION>";
                                }
                            }
                        } else {
                            if ($res1->id == $objp->fk_typeinterv) {
                                print "<OPTION SELECTED value='" . $res1->id . "'>" . $res1->label . "</OPTION>";
                            } else {
                                print "<OPTION value='" . $res1->id . "'>" . $res1->label . "</OPTION>";
                            }
                        }
                    }
                }
                print "</SELECT></td>";

                // Date d'intervention
                print '<td>';
                $html->select_date($db->jdate($objp->date_intervention), 'di', 0, 0, 0, "date_intervention");
                print '</td>';

                // Duration
                print '<td>';
                $html->select_duration('duration', $objp->duree);
                print '</td>';

                require_once(DOL_DOCUMENT_ROOT . '/synopsistools/class/divers.class.php');
                require_once(DOL_DOCUMENT_ROOT . '/product/class/product.class.php');
                if ($fichinter->fk_commande > 0) {
                    $com = new Synopsis_Commande($db);
                    $com->fetch($fichinter->fk_commande);
                    $com->fetch_group_lines(0, 0, 0, 0, 1);
                    print "<td><select name='comLigneId'>";
                    print "<option value='0'>S&eacute;lectionner-></option>";

                    foreach ($com->lines as $key => $val) {
                        $text = $val->description;
                        if ($val->fk_product) {
                            $prod = new Product($db);
                            $prod->fetch($val->fk_product);
                            $text = $prod->ref . " " . $text;

                            if ($val->rowid == $objp->fk_commandedet && $objp->fk_commandedet > 0) {
                                print "<option selected='selected' value='" . $val->rowid . "'>" . $text . " </option>";
                            } elseif ($text != '' && $text != ' ' && $prod->type != 0) {
                                print "<option value='" . $val->rowid . "'>" . $text . "</option>";
                            }
                        }
                    }
                    print "</select>";
                }


//                // Forfait
//                print '<td width=32 align=center>';
//                print "<input type='checkbox' name='isForfait' '".($objp->isForfait==1?'Checked':false)."'>";
//                print '</td>';
//
//                // pu_ht
//                print '<td>';
//                print "<input size=6 type='text' name='pu_ht' value='".$objp->pu_ht."'>";
//                print '</td>';
//
//                // pu_ht
//                print '<td>';
//                print "<input size=2 type='text' name='qte' value='".$objp->qte."'>";
//                print '</td>';


                print '<td align="center" colspan="5" valign="center"><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
                print '<br /><input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '"></td>';
                print '</tr>' . "\n";

                if ($fichinter->fk_contrat > 0 && $afficherLigneContrat) {
                    require_once(DOL_DOCUMENT_ROOT . "/core/lib/contract.lib.php");
                    $contrat = getContratObj($fichinter->fk_contrat);
                    $type = getTypeContrat_noLoad($fichinter->fk_contrat);
                    if ($type == 7) {
                        $contrat->fetch($fichinter->fk_contrat);
                        $contrat->fetch_lines(true);
                        print "<tr " . $bc[$var] . "><td colspan=8 style='border:1px Solid;'><table>";
                        foreach ($contrat->lines as $key => $val) {
//TODO ajouter filtre
                            if ($val->statut >= 0 && $val->statut < 5) {
                                $extra = "";
//                            if ($val->id == $objp->fk_contratdet) $extra="checked";
//                            print "<tr><td><input ".$extra." type=checkbox name='fk_contratdet-".$val->id."' value='".$val->id."'><td>";
                                if ($val->id == $objp->fk_contratdet) {
                                    print "<tr><td>";
                                    print "<ul style='list-style: none; padding-left: 0px; padding-top:0; margin-bottom:0; margin-top: 0px;'>";
                                    print $contrat->display1Line($contrat, $val);
                                    print "</ul>";
                                }
                            }
                        }
                        print "</table>";
                    }
                }


                print "</form>\n";
            }

            $i++;
        }

        $db->free($resql);
    } else {
        dol_print_error($db, "Erreur sql : " . $sql);
    }

    /*
     * Ajouter une ligne
     */
    if ($fichinter->statut == 0 && ( $user->rights->synopsisficheinter->modifAfterValid) && $user->rights->synopsisficheinter->creer && (!isset($_REQUEST['action']) || $_REQUEST['action'] <> 'editline')) {
        print "</table>";
        print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $fichinter->id . '#add" name="addinter" id="addinter" method="post">';
        print '<input type="hidden" name="fichinterid" value="' . $fichinter->id . '">';
        print '<input type="hidden" name="action" value="addligne">';
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<td>';
        print '<a name="add"></a>'; // ancre
        print $langs->trans('Description') . '</td>';
        print '<td>' . $langs->trans('Type') . '</td>';
        print '<td>' . $langs->trans('Date') . '</td>';
        print '<td>' . $langs->trans('Duration') . '</td>';
        print '<td colspan="4">&nbsp;</td>';
        print "</tr>\n";

        // Ajout ligne d'intervention

        $var = true;

        print '<tr ' . $bc[$var] . ">\n";
        print '<td>';
        // editeur wysiwyg
        if (isset($conf->fckeditor->enabled) && $conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS) {
            require_once(DOL_DOCUMENT_ROOT . "/core/class/doleditor.class.php");
            $doleditor = new DolEditor('np_desc', '', 100, 'dol_details');
            $doleditor->Create();
        } else {
            print '<textarea style="width: 100%!important; height: 2em!important;" class="flat" cols="70" name="np_desc" rows="' . ROWS_2 . '"></textarea>';
        }
        print '</td>';
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv WHERE active = 1 ORDER BY rang";
        $sql1 = $db->query($requete);
        print "<td><SELECT id='addfk_typeinterv' name='fk_typeinterv'>";
        print "<OPTION value='-1'>Selectionner-></OPTION>";
        while ($res1 = $db->fetch_object($sql1)) {
            if (!$fichinter->fk_contrat || $res1->isDeplacement || $res1->id == 14 || $res1->id == 20 || $res1->id == 21 || $res1->id == 22 || $res1->id == 26 || $res1->id == 27 || $res1->id == 28 || $res1->id == 29) {
                //Si déplacement OptGroup
                if (0 && $res1->isDeplacement == 1) {
                    $requete1 = "SELECT " . MAIN_DB_PREFIX . "product.label,
				  " . MAIN_DB_PREFIX . "product.ref,
				  " . MAIN_DB_PREFIX . "product.rowid
			     FROM " . MAIN_DB_PREFIX . "product,
				  " . MAIN_DB_PREFIX . "synopsisfichinter_User_PrixDepInterv
			    WHERE user_refid = " . $fichinter->user_creation->id . " AND " . MAIN_DB_PREFIX . "product.rowid = fk_product";
                    $sql2 = $db->query($requete1);
                    if ($db->num_rows($sql2) > 0) {
                        print "<OPTGROUP label='" . $res1->label . "'>";
                        while ($res2 = $db->fetch_object($sql2)) {
                            print "<OPTION value='dep-" . $res2->rowid . "'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $res2->ref . " " . $res2->label . "</OPTION>";
                        }
                        print "</OPTGROUP>";
                    } else {
                        if ($res1->default == 1) {
                            print "<OPTION SELECTED value='" . $res1->id . "'>" . $res1->label . "</OPTION>";
                        } else {
                            print "<OPTION value='" . $res1->id . "'>" . $res1->label . "</OPTION>";
                        }
                    }
                } else {
                    if ($res1->default == 1) {
                        print "<OPTION SELECTED value='" . $res1->id . "'>" . $res1->label . "</OPTION>";
                    } else {
                        print "<OPTION value='" . $res1->id . "'>" . $res1->label . "</OPTION>";
                    }
                }
            }
        }
        print "</SELECT>";
        // Date d'intervention
        print '<td>';
        $html->select_date($db->jdate(time()), 'di', 0, 0, 0, "addinter");
        print '</td>';

        // Duree
        print '<td>';
        $html->select_duration('duration');
        print '</td>';

        require_once(DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
        require_once(DOL_DOCUMENT_ROOT . '/product/class/product.class.php');


        print '<td align="center" valign="middle" colspan="4"><input type="submit" class="button" value="' . $langs->trans('Add') . '" name="addligne"></td>';
        print '</tr>';
        if ($fichinter->fk_contrat > 0 && $afficherLigneContrat) {
            $requete = "SELECT fk_contratdet FROM " . MAIN_DB_PREFIX . "synopsis_fichinterdet WHERE fk_fichinter = " . $fichinter->id . " AND fk_contratdet is not null";
            $sql = $db->query($requete);
            $arrTmp = array();
            while ($res = $db->fetch_object($sql)) {
                $arrTmp[$res->fk_contratdet] = $res->fk_contratdet;
            }
            require_once(DOL_DOCUMENT_ROOT . "/core/lib/contract.lib.php");
            $contrat = getContratObj($fichinter->fk_contrat);
            $type = getTypeContrat_noLoad($fichinter->fk_contrat);
            if ($type == 7) {
                $contrat->fetch($fichinter->fk_contrat);
                $contrat->fetch_lines(true);
                print "<tr><td colspan=11 style='border:1px Solid; border-top: 0px;'><table width=100%>";
                foreach ($contrat->lines as $key => $val) {
                    if ($val->statut >= 0 && $val->statut < 5 && !in_array($val->id, $arrTmp)) {
                        print "<tr><td><input type=checkbox name='fk_contratdet-" . $val->id . "' value='" . $val->id . "'><td>";
                        print "<ul style='list-style: none; padding-left: 0px; padding-top:0; margin-bottom:0; margin-top: 0px;'>";
                        print $contrat->display1Line($contrat, $val);
                        print "</ul>";
                    }
                }
                print "</table>";
                print "<hr>";
                print "<button class='ui-button' id='checkAll'>Tous</button><button class='ui-button'  id='uncheckAll'>Aucun</button><button class='ui-button' id='invertAll' onClick='return(false);'>Inverser</button>";
                print "<hr>";
                print <<<EOF
<script>
jQuery(document).ready(function(){
    jQuery('#checkAll').click(function(event){
        event.preventDefault();
        jQuery('#addinter').find('input:checkbox').attr('checked',true);
        return (false);
    })
    jQuery('#uncheckAll').click(function(event){
        event.preventDefault();
        jQuery('#addinter').find('input:checkbox').attr('checked',false);
        return (false);
    })
    jQuery('#invertAll').click(function(event){
        event.preventDefault();
        jQuery('#addinter').find('input:checkbox').each(function(){
            if (jQuery(this).attr('checked'))
            {
                jQuery(this).attr('checked',false);
            } else {
                jQuery(this).attr('checked',true);
            }
        });
        return (false);
    });
});
</script>
EOF;
                print "<style>li.ui-state-default table{ width:100%;}</style>";
            }
        }

        print '</table>';
        print '</form>';
    } else {
        print '</table>';
    }


    print '</div>';
    print "\n";


    /**
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';

    if ($user->societe_id == 0) {
        // Validate
        if (($fichinter->statut == 0) && $user->rights->synopsisficheinter->creer) {
            print '<a class="butAction" ';
            if ($conf->use_javascript_ajax) {
                $url = $_SERVER["PHP_SELF"] . '?id=' . $fichinter->id . '&action=confirm_validate&confirm=yes';
                print 'href="#" onClick="dialogConfirm(\'' . $url . '\',\'' . dol_escape_js(str_replace("<b></b>", $fichinter->ref, $langs->trans('ConfirmValidateIntervention'))) . '\',\'' . $langs->trans("Yes") . '\',\'' . $langs->trans("No") . '\',\'validate\')"';
            } else {
                print 'href="card.php?id=' . $_REQUEST["id"] . '&action=validate"';
            }
            print '>' . $langs->trans("Valid") . '</a>';
        }

        if (($fichinter->statut == 1) && $user->rights->synopsisficheinter->creer && $user->rights->synopsisficheinter->modifAfterValid) {
            print '<a class="butAction" ';
            if ($conf->use_javascript_ajax) {
                $url = $_SERVER["PHP_SELF"] . '?id=' . $fichinter->id . '&action=confirm_devalidate&confirm=yes';
                print 'href="#" onClick="dialogConfirm(\'' . $url . '\',\'' . dol_escape_js(str_replace("<b></b>", $fichinter->ref, $langs->trans('ConfirmDeValidateIntervention'))) . '\',\'' . $langs->trans("Yes") . '\',\'' . $langs->trans("No") . '\',\'validate\')"';
            } else {
                print 'href="card.php?id=' . $_REQUEST["id"] . '&action=devalidate"';
            }
            print '>' . $langs->trans("DeValid") . '</a>';
        }

        // Delete
        if (($fichinter->statut == 0 || $user->rights->synopsisficheinter->modifAfterValid) && $user->rights->synopsisficheinter->supprimer) {
            print '<a class="ui-button ui-button-error butActionDelete" ';
            if ($conf->use_javascript_ajax) {
                $url = $_SERVER["PHP_SELF"] . '?id=' . $fichinter->id . '&action=confirm_delete&confirm=yes';
                print 'href="#" onClick="dialogConfirm(\'' . $url . '\',\'' . $langs->trans("ConfirmDeleteIntervention") . '\',\'' . $langs->trans("Yes") . '\',\'' . $langs->trans("No") . '\',\'delete\')"';
            } else {
                print 'href="' . $_SERVER["PHP_SELF"] . '?id=' . $fichinter->id . '&amp;action=delete"';
            }
            print '>' . $langs->trans('Delete') . '</a>';
        }
    }

    print '</div>';

    print '<table width="100%"><tr><td width="50%" valign="top">';
    /*
     * Documents generes
     */
    $filename = sanitize_string($fichinter->ref);
    $filedir = $conf->ficheinter->dir_output . "/" . $fichinter->ref;
    $urlsource = $_SERVER["PHP_SELF"] . "?id=" . $fichinter->id;
    $genallowed = $user->rights->ficheinter->creer;
    $delallowed = $user->rights->ficheinter->supprimer;
    $genallowed = 1;
    $delallowed = 1;

    $var = true;
    print "<br>\n";
    $somethingshown = $formfile->show_documents('ficheinter', $filename, $filedir, $urlsource, $genallowed, $delallowed, $fichinter->modelpdf);

    print "</td><td>";
    print "&nbsp;</td>";
    print "</tr></table>\n";
}

$db->close();

llxFooter('$Date: 2008/07/15 00:57:37 $ - $Revision: 1.100 $');

function starratingPhp($name, $value, $max = 5, $iter = 1) {
    $ret = '<div id="starrating">';
    $arrStar = array();
    if ($max == 3) {
        $arrStar = array(0 => "Non", 1 => "Moyen", 2 => "Oui");
    } else if ($max == 5) {
        $arrStar = array(0 => "0%", 1 => "25%", 2 => "50%", 3 => "75%", 4 => "100%");
    }
    for ($i = 0; $i < $max; $i+=$iter) {
        $valDisp = preg_replace('/,/', ".", $i);
        if ($arrStar[$valDisp]) {
            $valDisp = $arrStar[$valDisp];
        }
        if ($value == $i) {
            $ret .= '<input class="star" type="radio" name="' . $name . '" value="' . $valDisp . '" checked="checked"/>';
        } else {
            $ret .= '<input class="star" type="radio" name="' . $name . '" value="' . $valDisp . '"/>';
        }
    }
    $ret .= '</div>';
//    var_dump($ret);
    return ($ret);
}

function sec2time($sec) {
    $returnstring = " ";
    $days = intval($sec / 86400);
    $hours = intval(($sec / 3600) - ($days * 24));
    $minutes = intval(($sec - (($days * 86400) + ($hours * 3600))) / 60);
    $seconds = $sec - ( ($days * 86400) + ($hours * 3600) + ($minutes * 60));

    $returnstring .= ( $days) ? (($days == 1) ? "1 j" : $days . "j") : "";
    $returnstring .= ( $days && $hours && !$minutes && !$seconds) ? "" : "";
    $returnstring .= ( $hours) ? ( ($hours == 1) ? " 1h" : " " . $hours . "h") : "";
    $returnstring .= ( ($days || $hours) && ($minutes && !$seconds)) ? "  " : " ";
    $returnstring .= ( $minutes) ? ( ($minutes == 1) ? " 1 min" : " " . $minutes . "min") : "";
    //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
    //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
    return ($returnstring);
}

print <<<EOF
<style>
    .showDetail{ cursor: pointer;}
</style>
EOF;
print "<script>";
print <<<EOF
    jQuery(document).ready(function(){
        jQuery('.showDetail').click(function(){
            jQuery('.showDetail').parent().find('table').css('display','none')
            jQuery(this).parent().find('table').css('display','block');
        });
        jQuery("#jsContrat li").dblclick(function(){
            var id = jQuery(this).attr('id');
            location.href=DOL_URL_ROOT+'/Synopsis_Contrat/contratDetail.php?id='+id;
        });
    });
EOF;

print "</script>";
?>
