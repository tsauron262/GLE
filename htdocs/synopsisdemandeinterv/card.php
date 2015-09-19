<?php

/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
/*
 */

/**
  \file       htdocs/synopsisdemandeinterv/card.php
  \brief      Fichier fiche intervention
  \ingroup    synopsisdemandeinterv
  \version    $Id: card.php,v 1.100 2008/07/15 00:57:37 eldy Exp $
 */
$activeLigneContrat = false;

$afficherExtraDi = true;

if (!isset($_REQUEST['action']))
    $_REQUEST['action'] = '';


require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsisfichinter/class/synopsisfichinter.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsisdemandeinterv/modules_synopsisdemandeinterv.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/synopsisdemandeinterv.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");
if (isset($conf->projet->enabled) && $conf->projet->enabled) {
    require_once(DOL_DOCUMENT_ROOT . "/core/lib/project.lib.php");
    require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
}
if (defined("SYNOPSISDEMANDEINTERV_ADDON") && is_readable(DOL_DOCUMENT_ROOT . "/core/modules/synopsisdemandeinterv/mod_" . SYNOPSISDEMANDEINTERV_ADDON . ".php")) {
    require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsisdemandeinterv/mod_" . SYNOPSISDEMANDEINTERV_ADDON . ".php");
}

$langs->load("companies");
$langs->load("interventions");

// Get parameters
$synopsisdemandeintervid = isset($_REQUEST["id"]) ? $_REQUEST["id"] : '';


// Security check
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'synopsisdemandeinterv', $synopsisdemandeintervid, 'synopsisdemandeinterv');



/*
 * Traitements des actions
 */
if ((!isset($_REQUEST["action"]) || ($_REQUEST["action"] != 'create' && $_REQUEST["action"] != 'add')) && !$_REQUEST["id"] > 0) {
    Header("Location: index.php");
    return;
}
//var_dump($_REQUEST);
if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'modification' && $_REQUEST['confirm'] == 'yes') {
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    $synopsisdemandeinterv->id = $_REQUEST["id"];
    $synopsisdemandeinterv->fetch($_REQUEST["id"]);
    $result = $synopsisdemandeinterv->invalid($user, $conf->synopsisdemandeinterv->outputdir);
    if ($result >= 0) {
        if ($_REQUEST['lang_id']) {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($_REQUEST['lang_id']);
        }

        $result = synopsisdemandeinterv_create($db, $synopsisdemandeinterv, $_REQUEST['model'], $outputlangs);
    } else {
        $mesg = '<div class="error ui-state-error">' . $synopsisdemandeinterv->error . '</div>';
    }
}

if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'confirm_validate' && $_REQUEST['confirm'] == 'yes') {
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    $synopsisdemandeinterv->id = $_REQUEST["id"];
    $synopsisdemandeinterv->fetch($_REQUEST["id"]);
    $result = $synopsisdemandeinterv->valid($user, $conf->synopsisdemandeinterv->outputdir);
    if ($result >= 0) {
        if ($_REQUEST['lang_id']) {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($_REQUEST['lang_id']);
        }

        $result = synopsisdemandeinterv_create($db, $synopsisdemandeinterv, $_REQUEST['model'], $outputlangs);
    } else {
        $mesg = '<div class="error ui-state-error">' . $synopsisdemandeinterv->error . '</div>';
    }
}


if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'confirm_PrisEnCharge' && $_REQUEST['confirm'] == 'yes') {
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    $synopsisdemandeinterv->id = $_REQUEST["id"];
    $synopsisdemandeinterv->fetch($_REQUEST["id"]);
    if (isset($_REQUEST['fk_user'])) {
        $tech = new User($db);
        $tech->fetch($_REQUEST['fk_user']);
    } else
        $tech = $user;
    $result = $synopsisdemandeinterv->prisencharge($tech, $conf->synopsisdemandeinterv->outputdir);
    if ($result >= 0 && "x" . $_REQUEST['model'] != "x") {
        if ($_REQUEST['lang_id']) {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($_REQUEST['lang_id']);
        }

        $result = synopsisdemandeinterv_create($db, $synopsisdemandeinterv, $_REQUEST['model'], $outputlangs);
    } else {
        $mesg = '<div class="error ui-state-error">' . $synopsisdemandeinterv->error . '</div>';
    }
}

if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'confirm_Cloture' && $_REQUEST['confirm'] == 'yes') {
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    $synopsisdemandeinterv->id = $_REQUEST["id"];
    $synopsisdemandeinterv->fetch($_REQUEST["id"]);
    $result = $synopsisdemandeinterv->cloture($user, $conf->synopsisdemandeinterv->outputdir);
    if ($result >= 0 && "x" . $_REQUEST['model'] != "x") {
        if ($_REQUEST['lang_id']) {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($_REQUEST['lang_id']);
        }
    } else {
        $mesg = '<div class="error ui-state-error">' . $synopsisdemandeinterv->error . '</div>';
    }
    if ($result >= 0) {
        header('Location: card.php?id=' . $synopsisdemandeinterv->id);
//        require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
//        $fichinter = new Fichinter($db);
//
//        $fichinter->date = $synopsisdemandeinterv->date;
//        $fichinter->socid = $synopsisdemandeinterv->socid;
//        $fichinter->duree = $synopsisdemandeinterv->duree;
//        $fichinter->projet_id = $synopsisdemandeinterv->projetidp;
//        $fichinter->author = $user->id;
//        $fichinter->description = $synopsisdemandeinterv->description;
//        $fichinter->modelpdf=$conf->global->FICHEINTER_ADDON_PDF;
//        $obj = $conf->global->FICHEINTER_ADDON;
//        $obj = "mod_".$obj;
//        require_once(DOL_DOCUMENT_ROOT."/core/modules/fichinter/".$obj.".php");
//        $modFicheinter = new $obj;
//        $societe=new Societe($db);
//        $societe->fetch($fichinter->socid);
//        $fichinter->ref = $modFicheinter->getNextValue($societe,$ficheinter);
//        if ($fichinter->socid > 0)
//        {
//            $result = $fichinter->create();
//            if ($result >0)
//            {
//                //transfert toutes les lignes
//                $synopsisdemandeinterv->fetch_lines();
//                //all lines id if lignes[]
//                $objLigneDemande = new SynopsisdemandeintervLigne($db);
//                $objLigneFiche = new SynopsisfichinterLigne($db);
//                foreach ($synopsisdemandeinterv->lignes as $ligne=>$idLigne)
//                {
//                    $objLigneDemande->fetch($idLigne->id);
//                    $objLigneFiche->fk_fichinter = $result;
//                    $objLigneFiche->desc = $objLigneDemande->desc;
//                    $objLigneFiche->datei = $objLigneDemande->dateiunformated;
//                    $objLigneFiche->duration = $objLigneDemande->duration;
//                    $objLigneFiche->insert();
//                }
//                if ($result > 0)
//                {
//                    header('Location: '.DOL_URL_ROOT.'/fichinter/card.php?id='.$result);
//                } else {
//                    $mesg='<div class="error ui-state-error">'.$fichinter->error.'</div>';
//                    $_REQUEST["action"] = 'create';
//                }
//            } else {
//                $mesg='<div class="error ui-state-error">'.$fichinter->error.'</div>';
//                $_REQUEST["action"] = 'create';
//            }
//        }else {
//           $mesg='<div class="error ui-state-error">'.$fichinter->error.'</div>';
//           $_REQUEST["action"] = 'create';
//        }
    } else {
        $mesg = '<div class="error ui-state-error">' . $fichinter->error . '</div>';
        $_REQUEST["action"] = 'create';
    }
}



if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'createFI') {
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    $synopsisdemandeinterv->id = $_REQUEST["id"];
    $synopsisdemandeinterv->fetch($_REQUEST["id"]);
    require_once(DOL_DOCUMENT_ROOT . "/synopsisfichinter/class/synopsisfichinter.class.php");
    $fichinter = new Synopsisfichinter($db);

    $fichinter->date = $synopsisdemandeinterv->date;
    $fichinter->socid = $synopsisdemandeinterv->socid;
    $fichinter->duree = $synopsisdemandeinterv->duree;
    $fichinter->projet_id = $synopsisdemandeinterv->projetidp;
    $fichinter->author = $synopsisdemandeinterv->fk_user_prisencharge;
    $fichinter->description = $synopsisdemandeinterv->description;
    $fichinter->modelpdf = $conf->global->FICHEINTER_ADDON_PDF;
    $fichinter->fk_di = $synopsisdemandeinterv->id;
    $fichinter->fk_contrat = $synopsisdemandeinterv->fk_contrat;
    $fichinter->fk_commande = $synopsisdemandeinterv->fk_commande;
    $obj = $conf->global->FICHEINTER_ADDON;
    $obj = "mod_" . $obj;
    require_once(DOL_DOCUMENT_ROOT . "/core/modules/fichinter/" . $obj . ".php");
    $modFicheinter = new $obj;
    $societe = new Societe($db);
    $societe->fetch($fichinter->socid);
    $fichinter->ref = $modFicheinter->getNextValue($societe, $ficheinter);

    if ($fichinter->socid > 0) {
        $result = $fichinter->create($user);
        if ($result > 0) {

            //transfert toutes les lignes
            $requete = "SELECT *  FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value WHERE typeI = 'DI' AND interv_refid =" . $synopsisdemandeinterv->id;
            $sql1 = $db->query($requete);
            while ($res1 = $db->fetch_object($sql1)) {
                if (!in_array($res1->extra_key_refid, array(24, 25, 26, 27))) {
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value
                                            (typeI, interv_refid,extra_key_refid,extra_value)
                                     VALUES ('FI'," . $result . "," . $res1->extra_key_refid . ",'" . addslashes($res1->extra_value) . "')";
                    $db->query($requete);
                }
            }
            $synopsisdemandeinterv->fetch_lines();
            //all lines id if lignes[]
            $objLigneFiche = new SynopsisfichinterLigne($db);

            foreach ($synopsisdemandeinterv->lignes as $ligne) {
                $objLigneFiche->fk_fichinter = $result;
                $objLigneFiche->desc = $ligne->desc;
                $objLigneFiche->datei = $ligne->datei;
                if ($conf->global->FICHINTER_RESET_DURATION_DI2FI == 1) {
                    $objLigneFiche->duration = 0;
                    $objLigneFiche->total_ht = 0;
                    $objLigneFiche->total_ttc = 0;
                    $objLigneFiche->total_tva = 0;
                    $objLigneFiche->pu_ht = 0;
                } else {
                    $objLigneFiche->duration = $ligne->duration;
                    $objLigneFiche->total_ht = $ligne->total_ht;
                    $objLigneFiche->total_ttc = $ligne->total_ttc;
                    $objLigneFiche->total_tva = $ligne->total_tva;
                    $objLigneFiche->pu_ht = $ligne->pu_ht;
                }
                if (isset($ligne->fk_prod))
                    $objLigneFiche->typeIntervProd = $ligne->fk_prod;

                $objLigneFiche->fk_typeinterv = $ligne->fk_typeinterv;
                $objLigneFiche->isDeplacement = $ligne->isDeplacement;
                //Si deplacement
//                $objLigneFiche->typeIntervProd = false;
//                if (isset($ligne->fk_commandede)) {
//                    $requete = "SELECT b.rowid
//                                      FROM " . MAIN_DB_PREFIX . "product as b,
//                                           " . MAIN_DB_PREFIX . "commandedet as cd
//                                     WHERE cd.fk_product = b.rowid
//                                       AND cd.rowid = " . $ligne->fk_commandedet;
//                    $sql3 = $db->query($requete);
//                    if ($db->num_rows($sql3) > 0) {
//                        $res3 = $db->fetch_object($sql3);
//                        $objLigneFiche->typeIntervProd = $res3->rowid;
//                        $objLigneFiche->total_ht = $ligne->total_ht;
//                        $objLigneFiche->total_ttc = $ligne->total_ttc;
//                        $objLigneFiche->total_tva = $ligne->total_tva;
//                        $objLigneFiche->pu_ht = $ligne->pu_ht;
//                    } else {
//                        die($requete);
//                    }
//                }

                $objLigneFiche->qte = $ligne->qte;
                $objLigneFiche->isForfait = $ligne->isForfait;
                $objLigneFiche->fk_commandedet = $ligne->fk_commandedet;
                $objLigneFiche->fk_contratdet = $ligne->fk_contratdet;
                $objLigneFiche->tx_tva = $ligne->tx_tva;

//                    print '<br/>';
//                    var_dump($objLigneFiche);
//                    print '<br/>';
                $objLigneFiche->insert($user);
            }
            header('Location: ' . DOL_URL_ROOT . '/synopsisfichinter/card.php?id=' . $result);
        } else {
            $mesg = '<div class="error ui-state-error">Impossible de créer la FI' . $fichinter->error . '</div>';
        }
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
                           AND typeI = 'DI'
                           AND extra_key_refid= " . $idExtraKey;
            $sql = $db->query($requete);
            if ($type == 'heure') {
                $val = traiteHeure($val);
            }
            if ($type == 'checkbox') {
                if ($val == 'On' || $val == 'on' || $val == 'ON') {
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value
                                             (interv_refid,extra_key_refid,extra_value,typeI)
                                      VALUES (" . $_REQUEST['id'] . "," . $idExtraKey . ",1,'DI')";
                    $sql = $db->query($requete);
                } else {
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value
                                             (interv_refid,extra_key_refid,extra_value,typeI)
                                      VALUES (" . $_REQUEST['id'] . "," . $idExtraKey . ",0,'DI')";
                    $sql = $db->query($requete);
                }
            } else {
                $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value
                                         (interv_refid,extra_key_refid,extra_value,typeI)
                                  VALUES (" . $_REQUEST['id'] . "," . $idExtraKey . ",'" . addslashes($val) . "','DI')";
                $sql = $db->query($requete);
            }
            $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
            $synopsisdemandeinterv->fetch($_REQUEST["id"]);
            $synopsisdemandeinterv->synchroAction();
        }
    }
}

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'add') {
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);

    $synopsisdemandeinterv->date = dol_mktime($_POST["phour"], $_POST["pmin"], $_POST["psec"], $_POST["pmonth"], $_POST["pday"], $_POST["pyear"]);
    $synopsisdemandeinterv->socid = $_POST["socid"];
    $synopsisdemandeinterv->duree = $_POST["duree"];
    $synopsisdemandeinterv->projet_id = $_POST["projetidp"];
    $synopsisdemandeinterv->author = $user->id;
    $synopsisdemandeinterv->description = $_POST["description"];
    $synopsisdemandeinterv->ref = $_POST["ref"];
    $synopsisdemandeinterv->fk_contrat = $_POST["fk_contrat"];
    $synopsisdemandeinterv->fk_user_prisencharge = $_POST["userid"];
    $synopsisdemandeinterv->fk_commande = $_POST["fk_commande"];

    if ($synopsisdemandeinterv->socid > 0) {
        $result = $synopsisdemandeinterv->create();
        if ($result > 0) {
            $_REQUEST["id"] = $result;      // Force raffraichissement sur fiche venant d'etre creee
            $synopsisdemandeintervid = $result;
            foreach ($_REQUEST as $key => $val) {
                if (preg_match('/^extraKey-([0-9]*)/', $key, $arr)) {
                    $idExtraKey = $arr[1];
                    $valExtraKey = $val;
                    //print "<input type='hidden' name='type-".$res->id."' value='checkbox'>";
                    if ($_REQUEST['type-' . $idExtraKey] == "checkbox") {
                        if ($valExtraKey == 'On' || $valExtraKey == 'on' || $valExtraKey == 'ON') {
                            $valExtraKey = 1;
                        } else {
                            $valExtraKey = 0;
                        }
                    }
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value
                                            ( interv_refid,extra_key_refid,extra_value)
                                     VALUES ( " . $synopsisdemandeintervid . ",'" . $idExtraKey . "','" . addslashes($val) . "')";
                    $sql = $db->query($requete);
                }
            }
            //Si commandeLigne Det => alors on ajoute une ligne de presta
            if ($_REQUEST['comLigneId'] > 0) {
                $comLigneDet = $_REQUEST['comLigneId'];
                if ($_POST['np_desc'] && ($_POST['durationhour'] || $_POST['durationmin'])) {
                    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
                    $ret = $synopsisdemandeinterv->fetch($_POST['synopsisdemandeintervid']);
                    $desc = $_POST['np_desc'];
                    $date_intervention = $db->idate(mktime(12, 1, 1, $_POST["dimonth"], $_POST["diday"], $_POST["diyear"]));
                    $duration = ConvertTime2Seconds($_POST['durationhour'], $_POST['durationmin']);
                    $fk_typeinterv = ($_REQUEST["fk_typeinterv"] > 0 ? $_REQUEST["fk_typeinterv"] : "");

                    $qte = $_REQUEST['qte'];
                    $pu_ht = $_REQUEST['pu_ht'];
                    $isForfait = ($_REQUEST['isForfait'] == 'On' || $_REQUEST['isForfait'] == 'on' || $_REQUEST['isForfait'] == 'ON' ? 1 : 0);

                    $synopsisdemandeinterv->addline(
                            $synopsisdemandeintervid, $desc, $date_intervention, $duration, $fk_typeinterv, $qte, $pu_ht, $isForfait, $_REQUEST['comLigneId'], $_REQUEST['fk_contratdet']
                    );

                    if ($_REQUEST['lang_id']) {
                        $outputlangs = new Translate("", $conf);
                        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
                    }
                    synopsisdemandeinterv_create($db, $synopsisdemandeinterv, $synopsisdemandeinterv->modelpdf, $outputlangs);
                }
            }

            if (isset($_REQUEST['fk_contratdet']))
                addElementElement("contratdet", "synopsisdemandeinterv", $_REQUEST['fk_contratdet'], $result);
        } else {
            $mesg = '<div class="error ui-state-error">' . $synopsisdemandeinterv->error . '</div>';
            $_REQUEST["action"] = 'create';
        }
    } else {
        $mesg = '<div class="error ui-state-error">' . $langs->trans("ErrorFieldRequired", $langs->trans("ThirdParty")) . '</div>';
        $_REQUEST["action"] = 'create';
    }
}

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'update') {
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    $synopsisdemandeinterv->id = $_REQUEST["id"];

    $synopsisdemandeinterv->date = dol_mktime($_POST["phour"], $_POST["pmin"], $_POST["psec"], $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
    $synopsisdemandeinterv->socid = $_POST["socid"];
    $synopsisdemandeinterv->projet_id = $_POST["projetidp"];
    $synopsisdemandeinterv->author = $user->id;
    $synopsisdemandeinterv->description = $_POST["description"];
    $synopsisdemandeinterv->ref = $_POST["ref"];

    $synopsisdemandeinterv->update($_POST["id"]);
    $_REQUEST["id"] = $_POST["id"];      // Force raffraichissement sur fiche venant d'etre creee
}

/*
 * Generer ou regenerer le document PDF
 */
if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'builddoc') {    // En get ou en post
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    $synopsisdemandeinterv->fetch($_REQUEST['id']);
    $synopsisdemandeinterv->fetch_lines();

    if ($_REQUEST['lang_id']) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }

    $result = synopsisdemandeinterv_create($db, $synopsisdemandeinterv, $_REQUEST['model'], $outputlangs);
    if ($result <= 0) {
        dol_print_error($db, $result);
        exit;
    } else {
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface = new Interfaces($db);
        $result = $interface->run_triggers('ECM_GENsynopsisdemandeinterv', $synopsisdemandeinterv, $user, $langs, $conf);
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
if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'classin') {
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    $synopsisdemandeinterv->fetch($_REQUEST['id']);
    $synopsisdemandeinterv->set_project($user, $_POST['projetidp']);
}

if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'confirm_delete' && $_REQUEST['confirm'] == 'yes') {
    if ($user->rights->synopsisdemandeinterv->supprimer) {
        $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
        $synopsisdemandeinterv->fetch($_REQUEST['id']);
        $synopsisdemandeinterv->delete($user);
    }
    Header('Location: index.php?leftmenu=synopsisdemandeinterv');
    exit;
}

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'setdate_delivery') {
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    $synopsisdemandeinterv->fetch($_REQUEST['id']);
    $result = $synopsisdemandeinterv->set_date_delivery($user, dol_mktime(12, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']));
    if ($result < 0)
        dol_print_error($db, $synopsisdemandeinterv->error);
}

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'setdescription') {
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    $synopsisdemandeinterv->fetch($_REQUEST['id']);
    $result = $synopsisdemandeinterv->set_description($user, $_POST['description']);
    if ($result < 0)
        dol_print_error($db, $synopsisdemandeinterv->error);
}

/*
 *  Ajout d'une ligne d'intervention
 */
if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "addligne" && $user->rights->synopsisdemandeinterv->creer) {

    if ($_POST['np_desc'] && ($_POST['durationhour'] || $_POST['durationmin'])) {
        $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
        $ret = $synopsisdemandeinterv->fetch($_POST['synopsisdemandeintervid']);

        $desc = $_POST['np_desc'];
        $date_intervention = $db->idate(mktime(12, 1, 1, $_POST["dimonth"], $_POST["diday"], $_POST["diyear"]));
        $duration = ConvertTime2Seconds($_POST['durationhour'], $_POST['durationmin']);
        $fk_typeinterv = ($_REQUEST["fk_typeinterv"] > 0 ? $_REQUEST["fk_typeinterv"] : "");
        $qte = preg_replace('/,/', '.', $_REQUEST['qte']);
        $pu_ht = preg_replace('/,/', '.', $_REQUEST['pu_ht']);
        $isForfait = ($_REQUEST['isForfait'] == 'On' || $_REQUEST['isForfait'] == 'ON' || $_REQUEST['isForfait'] == 'on' ? 1 : 0);

        $synopsisdemandeinterv->addline(
                $_POST['synopsisdemandeintervid'], $desc, $date_intervention, $duration, $fk_typeinterv, $qte, $pu_ht, $isForfait, ($_REQUEST['comLigneId'] > 0 ? $_REQUEST['comLigneId'] : false), ($_REQUEST['fk_contratdet'] > 0 ? $_REQUEST['fk_contratdet'] : false)
        );


        if ($_REQUEST['lang_id']) {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($_REQUEST['lang_id']);
        }
        synopsisdemandeinterv_create($db, $synopsisdemandeinterv, $synopsisdemandeinterv->modelpdf, $outputlangs);
    }
}

/*
 *  Mise a jour d'une ligne d'intervention
 */
if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'updateligne' && $user->rights->synopsisdemandeinterv->creer && $_POST["save"] == $langs->trans("Save")) {
    $synopsisdemandeintervline = new SynopsisdemandeintervLigne($db);
    if ($synopsisdemandeintervline->fetch($_POST['ligne']) <= 0) {
        dol_print_error($db, "fetch synopsisdemandeintervdet");
        exit;
    }
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    if ($synopsisdemandeinterv->fetch($synopsisdemandeintervline->fk_synopsisdemandeinterv) <= 0) {
        dol_print_error($db, "fetch synopsisdemandeinterv");
        exit;
    }
    $desc = $_POST['description'];
    $date_intervention = dol_mktime(12, 1, 1, $_POST["dimonth"], $_POST["diday"], $_POST["diyear"]);
    $duration = ConvertTime2Seconds($_POST['durationhour'], $_POST['durationmin']);

    $synopsisdemandeintervline->desc = $desc;
    $synopsisdemandeintervline->datei = $date_intervention;
    $synopsisdemandeintervline->duration = $duration;
    $synopsisdemandeintervline->fk_typeinterv = ($_REQUEST["fk_typeinterv"] > 0 ? $_REQUEST["fk_typeinterv"] : "");

    $synopsisdemandeintervline->qte = $_REQUEST['qte'];
    $synopsisdemandeintervline->pu_ht = $_REQUEST['pu_ht'];
    $synopsisdemandeintervline->isForfait = ($_REQUEST['isForfait'] == 'on' || $_REQUEST['isForfait'] == 'On' || $_REQUEST['isForfait'] == 'ON' ? 1 : 0);
    $synopsisdemandeintervline->comLigneId = $_REQUEST['comLigneId'];
    $synopsisdemandeintervline->fk_contratdet = $_REQUEST['fk_contratdet'];


    $result = $synopsisdemandeintervline->update();

    if ($_REQUEST['lang_id']) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    synopsisdemandeinterv_create($db, $synopsisdemandeinterv, $synopsisdemandeinterv->modelpdf, $outputlangs);
}

/*
 *  Supprime une ligne d'intervention SANS confirmation
 */
if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'deleteline' && $user->rights->synopsisdemandeinterv->creer && !$conf->global->PRODUIT_CONFIRM_DELETE_LINE) {
    $synopsisdemandeintervline = new SynopsisdemandeintervLigne($db);
    if ($synopsisdemandeintervline->fetch($_REQUEST['ligne']) <= 0) {
        dol_print_error($db, "fetch synopsisdemandeintervdet");
        exit;
    }
    $result = $synopsisdemandeintervline->delete_line();
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    if ($synopsisdemandeinterv->fetch($synopsisdemandeintervline->fk_synopsisdemandeinterv) <= 0) {
        dol_print_error($db, "fetch synopsisdemandeinterv");
        exit;
    }
    if ($_REQUEST['lang_id']) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    synopsisdemandeinterv_create($db, $synopsisdemandeinterv, $synopsisdemandeinterv->modelpdf, $outputlangs);
}

/*
 *  Supprime une ligne d'intervention AVEC confirmation
 */
if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'confirm_deleteline' && $_REQUEST['confirm'] == 'yes' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE) {
    if ($user->rights->synopsisdemandeinterv->creer) {
        $synopsisdemandeintervline = new SynopsisdemandeintervLigne($db);
        if ($synopsisdemandeintervline->fetch($_REQUEST['ligne']) <= 0) {
            dol_print_error($db, "fetch synopsisdemandeintervdet");
            exit;
        }
        $result = $synopsisdemandeintervline->delete_line();
        $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
        if ($synopsisdemandeinterv->fetch($synopsisdemandeintervline->fk_synopsisdemandeinterv) <= 0) {
            dol_print_error($db, "fetch synopsisdemandeinterv");
            exit;
        }
        if ($_REQUEST['lang_id']) {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($_REQUEST['lang_id']);
        }
        synopsisdemandeinterv_create($db, $synopsisdemandeinterv, $synopsisdemandeinterv->modelpdf, $outputlangs);
    }
    Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $_REQUEST['id']);
    exit;
}

/*
 * Ordonnancement des lignes
 */

if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'up' && $user->rights->synopsisdemandeinterv->creer) {
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    $synopsisdemandeinterv->fetch($_REQUEST['id']);
    $synopsisdemandeinterv->line_up($_REQUEST['rowid']);
    if ($_REQUEST['lang_id']) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    synopsisdemandeinterv_create($db, $synopsisdemandeinterv, $synopsisdemandeinterv->modelpdf, $outputlangs);
    Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $_REQUEST["id"] . '#' . $_REQUEST['rowid']);
    exit;
}

if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'down' && $user->rights->synopsisdemandeinterv->creer) {
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    $synopsisdemandeinterv->fetch($_REQUEST['id']);
    $synopsisdemandeinterv->line_down($_REQUEST['rowid']);
    if ($_REQUEST['lang_id']) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    synopsisdemandeinterv_create($db, $synopsisdemandeinterv, $synopsisdemandeinterv->modelpdf, $outputlangs);
    Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $_REQUEST["id"] . '#' . $_REQUEST['rowid']);
    exit;
}


/*
 * View
 */
$html = new Form($db);
$formfile = new FormFile($db);
$js = "<script type='text/javascript' src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/jquery.rating.js'></script>";
$js .= "<script type='text/javascript' src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/jquery.validate.min.js'></script>";
$js .= '<link rel="stylesheet"  href="' . DOL_URL_ROOT . '/Synopsis_Common/css/jquery.rating.css" type="text/css" ></link>';
$js .= "<style> textarea{ width: 80%; height: 10em;}</style>";

//  //launchRunningProcess($db,'synopsisdemandeinterv',$_GET['id']);

llxHeader($js, "Demande Intervention");
if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'setEffUser') {
    $tmpUser = new User($db);
    $tmpUser->id = $_REQUEST['EffUserid'];
    if ($tmpUser->fetch($tmpUser->id) > 0) {
        $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
        $synopsisdemandeinterv->fetch($_REQUEST['id']);
        $synopsisdemandeinterv->SelectTarget($tmpUser);
    }
}
if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'setAttUser') {
    $tmpUser = new User($db);
    $tmpUser->id = $_REQUEST['AttUserid'];
//    die($_REQUEST['AttUserid']);
    if ($tmpUser->fetch($_REQUEST['AttUserid']) > 0) {
        $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
        $synopsisdemandeinterv->fetch($_REQUEST['id']);
        $synopsisdemandeinterv->preparePrisencharge($tmpUser);
    }
}
if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'create') {
    /*
     * Mode creation
     * Creation d'une nouvelle fiche d'intervention
     */
    $societe = "";
    if ($_REQUEST["socid"] > 0) {
        $societe = new Societe($db);
        $societe->fetch($_REQUEST["socid"]);
    }

    load_fiche_titre($langs->trans("AddDI"));

    if ($mesg)
        print $mesg . '<br>';
    if (!$conf->global->SYNOPSISDEMANDEINTERV_ADDON) {
        dol_print_error($db, $langs->trans("Error") . " " . $langs->trans("Error_SYNOPSISDEMANDEINTERV_ADDON_NotDefined"));
        exit;
    }

    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    $synopsisdemandeinterv->date = time();
    if ($synopsisdemandeintervid)
        $result = $synopsisdemandeinterv->fetch($synopsisdemandeintervid);

    $obj = $conf->global->SYNOPSISDEMANDEINTERV_ADDON;
    $obj = "mod_" . $obj;

    $modsynopsisdemandeinterv = new $obj;
    $numpr = $modsynopsisdemandeinterv->getNextValue($societe, $synopsisdemandeinterv);
//var_dump($modsynopsisdemandeinterv);
    if ($_REQUEST["socid"] > 0) {

        print "<form name='synopsisdemandeinterv' action=\"card.php\" method=\"post\">";

        print '<table cellpadding=15 class="border" width="100%">';

        print '<input type="hidden" name="socid" value=' . $_REQUEST["socid"] . '>';
        print "<tr><th class='ui-widget-header ui-state-default'>" . $langs->trans("Company") . "</th>
                   <td colspan=3 class='ui-widget-content'>" . $societe->getNomUrl(1) . "</td></tr>";

        print "<tr><th class='ui-widget-header ui-state-default'>" . $langs->trans("Date") . "</th>
                   <td colspan=3 class='ui-widget-content'>";
        $html->select_date(time(), "p", '', '', '', 'synopsisdemandeinterv');
        print "</td></tr>";

        print "<input type=\"hidden\" name=\"action\" value=\"add\">";

        print "<tr><th class='ui-widget-header ui-state-default'>" . $langs->trans("Ref") . "</th>";
        print "    <td colspan=3 class='ui-widget-content'><input name=\"ref\" value=\"" . $numpr . "\"></td></tr>\n";

        if ($conf->projet->enabled) {
            require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formprojet.class.php");
            // Projet associe
            $langs->load("project");

            print '<tr><th valign="top" class="ui-widget-header ui-state-default">' . $langs->trans("Project") . '</td><td class="ui-widget-content" colspan=3>';

            if ($societe->id)
                $numprojet = $societe->has_projects();

            if (!$numprojet) {
                print '<table class="nobordernopadding" width="100%">';
                print '<tr><td width="130"  class="ui-widget-content">' . $langs->trans("NoProject") . '</td>';

                if ($user->rights->projet->creer) {
                    print '<td class="ui-widget-content"><a href=' . DOL_URL_ROOT . '/projet/card.php?socid=' . $societe->id . '&action=create>' . $langs->trans("Add") . '</a></td>';
                }
                print '</tr></table>';
            } else {
                $formP = new FormProjets($db);
                $formP->select_projects($societe->id, '', 'projetidp');
            }
            print '</td></tr>';
        }
//lié a un contrat de maintenance ou mixte
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "contrat WHERE " . /* extraparams in (3,7) AND */" fk_soc =" . $societe->id;
        $sql = $db->query($requete);
        if ($db->num_rows($sql) > 0) {
            print '<th class="ui-widget-header ui-state-default">' . $langs->trans("Contrat de maintenance") . '</th>';
            print '<td colspan="3" class="ui-widget-content">';
            print "<SELECT id='fk_contrat' name='fk_contrat'>";
            print "<option value='-1'>S&eacute;lectionner -></option>";
            while ($res = $db->fetch_object($sql)) {
                if ($_REQUEST['fk_contrat'] == $res->rowid && $_REQUEST['fk_contrat'] > 0) {
                    print "<option selected='selected' value='" . $res->rowid . "'>" . $res->ref . "</option>";
                } elseif ($res->ref != '') {
                    print "<option value='" . $res->rowid . "'>" . $res->ref . "</option>";
                }
            }
            print "</SELECT>";
        }

        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "commande WHERE fk_soc =" . $societe->id;
        $sql = $db->query($requete);
        if ($db->num_rows($sql) > 0) {
            print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Commande") . '</th>';
            print '<td colspan="3" class="ui-widget-content">';
            print "<SELECT id='fk_commande' name='fk_commande'>";
            print "<option value='-1'>S&eacute;lectionner -></option>";
            while ($res = $db->fetch_object($sql)) {
                if ($_REQUEST['fk_commande'] == $res->rowid && $_REQUEST['fk_commande'] > 0) {
                    print "<option SELECTED value='" . $res->rowid . "'>" . $res->ref . "</option>";
                } elseif ($res->ref != '') {
                    print "<option value='" . $res->rowid . "'>" . $res->ref . "</option>";
                }
            }
            print "</SELECT>";
        }

        // Model
        print '<tr>';
        print '<th class="ui-widget-header ui-state-default">' . $langs->trans("DefaultModel") . '</th>';
        print '<td colspan="3" class="ui-widget-content">';
        $model = new Modelesynopsisdemandeinterv();
        $liste = $model->liste_modeles($db);
        $html->selectarray('model', $liste, $conf->global->SYNOPSISDEMANDEINTERV_ADDON_PDF);
        print "</td></tr>";



        print '<tr><th valign="top" class="ui-widget-header ui-state-default">' . $langs->trans("Titre") . '</th>';
        print "<td colspan=3 class='ui-widget-content'>";

        $dsc = '';
        if (isset($_REQUEST['fk_contratdet'])) {
            require_once DOL_DOCUMENT_ROOT . "/Synopsis_Contrat/class/contrat.class.php";
            $ln = new Synopsis_ContratLigne($db);
            $ln->fetch($_REQUEST['fk_contratdet']);
            $dsc = $ln->getTitreInter();
            echo '<input type="hidden" name="fk_contratdet" value="' . $_REQUEST['fk_contratdet'] . '"/>';
        }



        if (isset($conf->fckeditor->enabled) && $conf->fckeditor->enabled && isset($conf->global->FCKEDITOR_ENABLE_SOCIETE) && $conf->global->FCKEDITOR_ENABLE_SOCIETE) {
            // Editeur wysiwyg
            require_once(DOL_DOCUMENT_ROOT . "/core/lib/doleditor.class.php");
            $doleditor = new DolEditor('description', $dsc, 280, 'dolibarr_notes', 'In', true);
            $doleditor->Create();
        } else {
//            print '<textarea name="description" wrap="soft" cols="70" rows="1">' . $dsc . '</textarea>';
            print '<input type="text" style="width:320px;" name="description" value="' . $dsc . '"/>';
        }

        print '</td></tr>';

//Extra Field
//        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_key
//                     WHERE (isQuality<>1 OR isQuality is null) AND isInMainPanel = 1 AND active = 1 ORDER BY rang, label";
//        $sql = $db->query($requete);
//        $modulo = false;
//        while ($res = $db->fetch_object($sql)) {
//            $colspan = 1;
//            $modulo = !$modulo;
//            if ($res->fullLine == 1) {
//                $colspan = 3;
//                $modulo = true;
//            }
//            if ($modulo) {
//                print "<tr>";
//            }
//            if ($res->fullLine == 1) {
//                $modulo = !$modulo;
//            }
//            print "<th valign='top' class='ui-widget-header ui-state-default'>" . $res->label;
//            switch ($res->type) {
//                case "date": {
//                        print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><input type='text' name='extraKey-" . $res->id . "' class='datePicker'>";
//                        print "<input type='hidden' name='type-" . $res->id . "' value='date'>";
//                    }
//                    break;
//                case "textarea": {
//                        print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><textarea name='extraKey-" . $res->id . "'></textarea>";
//                        print "<input type='hidden' name='type-" . $res->id . "' value='comment'>";
//                    }
//                    break;
//                default:
//                case "text": {
//                        print '<td colspan="' . $colspan . '" valign="middle" class="ui-widget-content"><input type="text" name="extraKey-' . $res->id . '">';
//                        print "<input type='hidden' name='type-" . $res->id . "' value='text'>";
//                    }
//                    break;
//                case "datetime": {
//                        print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><input type='text' name='extraKey-" . $res->id . "' class='dateTimePicker'>";
//                        print "<input type='hidden' name='type-" . $res->id . "' value='datetime'>";
//                    }
//                    break;
//                case "checkbox": {
//                        print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><input type='checkbox'  name='extraKey-" . $res->id . "'>";
//                        print "<input type='hidden' name='type-" . $res->id . "' value='checkbox'>";
//                    }
//                    break;
//                case "3stars": {
//                        print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'>&nbsp;&nbsp;";
//                        print starratingPhp("extraKey-" . $res->id, $res->extra_value, 3, $iter = 1);
//                        print "<input type='hidden' name='type-" . $res->id . "' value='3stars'>";
//                    }
//                    break;
//                case "5stars": {
//                        print "<td  colspan='" . $colspan . "' valign='middle' class='ui-widget-content'>";
////                    print "<input type='checkbox' ".($res->extra_value==1?'CHECKED':"")."  name='extraKey-".$res->id."'>";
//                        print starratingPhp("extraKey-" . $res->id, "", 5, $iter = 1);
//                        print "<input type='hidden' name='type-" . $res->id . "' value='5stars'>";
//                    }
//                    break;
//                case "radio": {
//                        print "<td  colspan='" . $colspan . "' valign='middle' class='ui-widget-content'>";
//                        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_values_choice WHERE key_refid = " . $res->id;
//                        $sql1 = $db->query($requete);
//                        if ($db->num_rows($sql1) > 0) {
//                            print "<table width=100%>";
//                            while ($res1 = $db->fetch_object($sql1)) {
//                                print "<tr><td width=100%>" . $res1->label . "<td>";
//                                print "<input type='radio' value='" . $res1->value . "' name='extraKey-" . $res->id . "'>";
//                            }
//                            print "</table>";
//                        }
//                        print "<input type='hidden' name='type-" . $res->id . "' value='radio'>";
//                    }
//                    break;
//            }
//        }
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
        print '<tr><th colspan="4" align="center" class="ui-widget-header ui-state-default">';
        print '<input type="submit" class="button" value="' . $langs->trans("CreateDraftDI") . '">';
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

        $com = new Synopsis_Commande($db);
        $com->fetch($res->rowid);
        print "<tr><th class='ui-widget-header ui-state-default'>" . $langs->trans("order") . "</th>
                   <td colspan=3 class='ui-widget-content'>" . $com->getNomUrl(1) . "</td></tr>";

        if ($_REQUEST['userid'] > 0) {
            $tmpUser = new User($db);
            $tmpUser->id = $_REQUEST['userid'];
            $tmpUser->fetch($tmpUser->id);
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
        $html->select_date($tmpDate, "p", '', '', '', 'synopsisdemandeinterv');
        print "</td></tr>";


        print "<tr><th class='ui-widget-header ui-state-default'>" . $langs->trans("Ref") . "</th>";
        print "    <td colspan=3 class='ui-widget-content'><input name=\"ref\" value=\"" . $numpr . "\"></td></tr>\n";

        if ($conf->projet->enabled) {
            // Projet associe
            $langs->load("project");

            print '<tr><th valign="top" class="ui-widget-header ui-state-default">' . $langs->trans("Project") . '</td><td class="ui-widget-content" colspan=3>';

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
        $model = new Modelesynopsisdemandeinterv();
        $liste = $model->liste_modeles($db);
        $html->selectarray('model', $liste, $conf->global->SYNOPSISDEMANDEINTERV_ADDON_PDF);
        print "</td></tr>";
        print '<tr><th valign="top" class="ui-widget-header ui-state-default">' . $langs->trans("Description") . '</th>';
        print "<td colspan=3 class='ui-widget-content'>";

        if (isset($conf->fckeditor->enabled) && $conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_SOCIETE) {
            // Editeur wysiwyg
            require_once(DOL_DOCUMENT_ROOT . "/core/lib/doleditor.class.php");
            $doleditor = new DolEditor('description', '', 280, 'dolibarr_notes', 'In', true);
            $doleditor->Create();
        } else {
//            print '<textarea name="description" wrap="soft" cols="70" rows="12"></textarea>';
            print '<input type="text" style="width:320px;" name="description" value=""/>';
        }

        print '</td></tr>';
//Extra Field
        if ($afficherExtraDi) {
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_key WHERE  (isQuality<>1 OR isQuality is null) AND isInMainPanel = 1 AND active = 1 AND inDi = 1 ORDER BY rang,label";
            $sql = $db->query($requete);
            $modulo = false;
            while ($res = $db->fetch_object($sql)) {
                $colspan = 1;
                $modulo = !$modulo;
                if ($res->fullLine == 1) {
                    $colspan = 3;
                    $modulo = true;
                }
                if ($modulo) {
                    print "<tr>";
                }
                if ($res->fullLine == 1) {
                    $modulo = !$modulo;
                }
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
                            print "<input type='hidden' name='type-" . $res->id . "' value='" . $res->type . "'>";
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
                            print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'>";
//                    print "<input type='checkbox' ".($res->extra_value==1?'CHECKED':"")."  name='extraKey-".$res->id."'>";
                            print starratingPhp("extraKey-" . $res->id, $res->extra_value, 3, $iter = 1);
                            print "<input type='hidden' name='type-" . $res->id . "' value='3stars'>";
                        }
                        break;
                    case "5stars": {
                            print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'>";
//                    print "<input type='checkbox' ".($res->extra_value==1?'CHECKED':"")."  name='extraKey-".$res->id."'>";
                            print starratingPhp("extraKey-" . $res->id, $res->extra_value, 5, $iter = 1);
                            print "<input type='hidden' name='type-" . $res->id . "' value='5stars'>";
                        }
                        break;
                    case "radio": {
                            print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'>";
                            print "<input type='hidden' name='action' value='editExtra'>";
                            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_values_choice WHERE key_refid = " . $res->id;
                            $sql1 = $db->query($requete);
                            if ($db->num_rows($sql1) > 0) {
                                print "<table width=100%>";
                                while ($res1 = $db->fetch_object($sql1)) {
                                    print "<tr><td width=100%>" . $res1->label . "<td>";
                                    print "<input type='radio' value='" . $res1->value . "' name='extraKey-" . $res->id . "' >";
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
        }
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
            require_once(DOL_DOCUMENT_ROOT . "/core/lib/doleditor.class.php");
            $doleditor = new DolEditor('np_desc', '', 100, 'dolibarr_details');
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
        $html->select_date(time(), 'di', 0, 0, 0, "addinter");
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
        print "<form name='synopsisdemandeinterv' action=\"card.php\" method=\"get\">";
        print '<table class="border" width="100%">';
        print "<tr><th class='ui-widget-header ui-state-default'>" . $langs->trans("Company") . "</th><td>";
        print $html->select_company('', 'socid', '', 1);
        print "</td></tr>";
        print '<tr><th colspan="2" align="center"  class="ui-widget-header ui-state-default">';
        print "<input type=\"hidden\" name=\"action\" value=\"create\">";
        print '<input type="submit" class="button" value="' . $langs->trans("CreateDraftDI") . '">';
        print '</td></tr>';
        print '</table>';
        print '</form>';
    }
} elseif ($_REQUEST["id"] > 0) {
    /*
     * Affichage en mode visu
     */
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    $result = $synopsisdemandeinterv->fetch($_REQUEST["id"]);
    if (!$result > 0) {
        dol_print_error($db, "fetch synopsisdemandeinterv");
        exit;
    }
    $synopsisdemandeinterv->fetch_client();
    //saveHistoUser($synopsisdemandeinterv->id, "DI",$synopsisdemandeinterv->ref);

    if ($mesg)
        print $mesg . "<br>";

    $head = synopsisdemandeinterv_prepare_head($synopsisdemandeinterv);

    dol_fiche_head($head, 'card', $langs->trans("DI"));
    $synopsisdemandeinterv->info($_REQUEST['id']);

    /*
     * Confirmation de la suppression de la fiche d'intervention
     */
    if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'delete') {
        $html->form_confirm($_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id, $langs->trans('DeleteDI'), $langs->trans('ConfirmDeleteDI'), 'confirm_delete');
        print '<br>';
    }

    /*
     * Confirmation de la validation de la fiche d'intervention
     */
    if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'validate') {
        $html->form_confirm($_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id, $langs->trans('ValidateDI'), $langs->trans('ConfirmValidateDI'), 'confirm_validate');
        print '<br>';
    }

    /*
     * Confirmation de la suppression d'une ligne d'intervention
     */
    if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'ask_deleteline' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE) {
        $html->form_confirm($_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '&amp;ligne=' . $_REQUEST["ligne"], $langs->trans('DeleteDILine'), $langs->trans('ConfirmDeleteDILine'), 'confirm_deleteline');
        print '<br>';
    }

    print '<table class="border" cellpadding=15 width="100%">';

    // Ref
    print '<tr><th class="ui-widget-header ui-state-default" width="25%">' . $langs->trans("Ref") . '</th>
               <td colspan=3 class="ui-widget-content">' . $synopsisdemandeinterv->getNomUrl(1) . '</td></tr>';

    // Societe
    print "<tr><th class=\"ui-widget-header ui-state-default\">" . $langs->trans("Company") . "</th>
               <td colspan=3 class='ui-widget-content'>" . $synopsisdemandeinterv->client->getNomUrl(1) . "</td></tr>";

//Ref contrat ou commande
    if ($synopsisdemandeinterv->fk_contrat > 0) {
        print "<tr><th class='ui-widget-header ui-state-default'>Contrat</th>";
        require_once(DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");
        $contrat = new Contrat($db);
        $contrat->fetch($synopsisdemandeinterv->fk_contrat);
        print "    <td class='ui-widget-content'>" . $contrat->getNomUrl(1) . "</td> ";
        $tab = getElementElement("contratdet", "synopsisdemandeinterv", NULL, $synopsisdemandeinterv->id);
        if (isset($tab[0])) {
            print "<th class='ui-widget-header ui-state-default'>Ligne Contrat</th>";
            print "    <td class='ui-widget-content'><a href='" . DOL_URL_ROOT . "/Synopsis_Contrat/contratDetail.php?id=" . $tab[0]['s'] . "'>" . $contrat->ref . "-" . $tab[0]['s'] . "</a></td> ";
        }
    } else if ($synopsisdemandeinterv->fk_commande > 0) {
        print "<tr><th class='ui-widget-header ui-state-default'>Commande</th>";
        require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
        $com = new Synopsis_Commande($db);
        $com->fetch($synopsisdemandeinterv->fk_commande);
        print "    <td class='ui-widget-content'>" . $com->getNomUrl(1) . "<th class='ui-widget-header ui-state-default' width=20%>Pr&eacute;paration de commande<td class='ui-widget-content'> " . $com->getNomUrl(1, 5) . "</td> ";
    }
    // Date
    print '<tr><th class="ui-widget-header ui-state-default">';
    print $langs->trans('Date');
    if (isset($_REQUEST["action"]) && $_REQUEST['action'] != 'editdate_delivery' && $synopsisdemandeinterv->brouillon)
        print '<a href="' . $_SERVER["PHP_SELF"] . '?action=editdate_delivery&amp;id=' . $synopsisdemandeinterv->id . '">' . img_edit($langs->trans('SetDateCreate'), 1) . '</a>';
    print '</th>';
    print '</td><td colspan="3" class="ui-widget-content">';
    if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'editdate_delivery') {
        print '<form name="editdate_delivery" action="' . $_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '" method="post">';
        print '<input type="hidden" name="action" value="setdate_delivery">';
        $html->select_date($synopsisdemandeinterv->date, 'liv_', '', '', '', "editdate_delivery");
        print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
        print '</form>';
    } else {
        print dol_print_date($synopsisdemandeinterv->date, 'day');
    }
    print '</td>';
    print '</tr>';

////Maintenance
//    if ($synopsisdemandeinterv->fk_contrat > 0) {
//        require_once(DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");
//        $contrat = new Contrat($db);
//        $contrat->fetch($synopsisdemandeinterv->fk_contrat);
//        print '<th class="ui-widget-header ui-state-default">' . $langs->trans("Contrat de maintenance") . '</th>';
//        print '<td colspan="3" class="ui-widget-content">' . $contrat->getNomUrl(1);
//    }
    // Projet
    if (isset($conf->projet->enabled) && $synopsisdemandeinterv->socid) {
        $langs->load("projects");
        print '<tr><th class="ui-widget-header ui-state-default">';
//        print '<table class="nobordernopadding" width="100%"><tr><th class="ui-widget-header ui-state-default">';
        print $langs->trans('Project');
        $societe = new Societe($db);
        $societe->fetch($synopsisdemandeinterv->socid);
        if ($societe->id)
            $numprojet = $societe->has_projects();
        if (!$numprojet) {
//            print '</td></tr></table>';
            print '<td class="ui-widget-content" colspan=3>';
            print $langs->trans("NoProject") . '&nbsp;&nbsp;';
            if ($synopsisdemandeinterv->brouillon)
                print '<a class="butAction" href=../DOL3.5.2projet/card.php?socid=' . $societe->id . '&action=create>' . $langs->trans('AddProject') . '</a>';
            print '</td>';
        } else {
            if ($synopsisdemandeinterv->statut == 0 && $user->rights->synopsisdemandeinterv->creer) {
                if ($_REQUEST['action'] != 'classer' && $synopsisdemandeinterv->brouillon)
                    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=classer&amp;id=' . $synopsisdemandeinterv->id . '">' . img_edit($langs->trans('SetProject'), 1) . '</a></th>';
//                if ($_REQUEST['action'] != 'classer' && $synopsisdemandeinterv->brouillon) print '<a href="'.$_SERVER["PHP_SELF"].'?action=classer&amp;id='.$synopsisdemandeinterv->id.'">'.img_edit($langs->trans('SetDescription'),1).'</a>';
                print '<td colspan="3" class="ui-widget-content">';
                if ($_REQUEST['action'] == 'classer') {
                    $html->form_project($_SERVER['PHP_SELF'] . '?id=' . $synopsisdemandeinterv->id, $synopsisdemandeinterv->socid, $synopsisdemandeinterv->projetidp, 'projetidp');
                } else {
                    $html->form_project($_SERVER['PHP_SELF'] . '?id=' . $synopsisdemandeinterv->id, $synopsisdemandeinterv->socid, $synopsisdemandeinterv->projetidp, 'none');
                }
                print '</td></tr>';
            } else {
                if (!empty($synopsisdemandeinterv->projetidp)) {
//                    print '</td></tr></table>';
                    print '<td colspan="3" class="ui-widget-content">';
                    $proj = new Project($db);
                    $proj->fetch($synopsisdemandeinterv->projetidp);
                    print '<../DOL3.5.2projet/card.phpiche.php?id=' . $synopsisdemandeinterv->projetidp . '" title="' . $langs->trans('ShowProject') . '">';
                    print $proj->title;
                    print '</a>';
                    print '</td>';
                } else {
//                    print '</td></tr></table>';
                    print '<td colspan="3" class="ui-widget-content">&nbsp;</td>';
                }
            }
        }
        print '</tr>';
    }

    // Duration
    //var_dump($synopsisdemandeinterv->duree);
    print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("TotalDuration") . '</th>
               <td colspan=1 class="ui-widget-content">' . sec2time($synopsisdemandeinterv->duree) . '</td>';

    // Montant total
    print '<th class="ui-widget-header ui-state-default">' . $langs->trans("Total HT") . '</th>
               <td colspan=1 class="ui-widget-content">' . price($synopsisdemandeinterv->total_ht) . ' &euro;</td></tr>';


    // Description
    print '<tr><th class="ui-widget-header ui-state-default">';
    print $langs->trans('Description');
    if (isset($_REQUEST["action"]) && $_REQUEST['action'] != 'editdescription' && $synopsisdemandeinterv->brouillon)
        print '<a href="' . $_SERVER["PHP_SELF"] . '?action=editdescription&amp;id=' . $synopsisdemandeinterv->id . '">' . img_edit($langs->trans('SetDescription'), 1) . '</a>';
    print '</td><td colspan="3" class="ui-widget-content">';
    if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'editdescription') {
        print '<form name="editdescription" action="' . $_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '" method="post">';
        print '<input type="hidden" name="action" value="setdescription">';
        if (isset($conf->fckeditor->enabled) && $conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_SOCIETE) {
            // Editeur wysiwyg
            require_once(DOL_DOCUMENT_ROOT . "/core/lib/doleditor.class.php");
            $doleditor = new DolEditor('description', $synopsisdemandeinterv->description, 280, 'dolibarr_notes', 'In', true);
            $doleditor->Create();
        } else {
//            print '<textarea name="description" wrap="soft" cols="70" rows="12">' . dol_htmlentitiesbr_decode($synopsisdemandeinterv->description) . '</textarea>';
            print '<input type="text" name="description" style="width:320px;" value="' . dol_htmlentitiesbr_decode($synopsisdemandeinterv->description) . '"/>';
        }
        print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
        print '</form>';
    } else {
        print nl2br($synopsisdemandeinterv->description);
    }
    print '</td>';
    print '</tr>';

    // Statut
    print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Status") . '</th><td colspan=3 class="ui-widget-content">' . $synopsisdemandeinterv->getLibStatut(4) . '</td></tr>';
    // fi
    $tabFI = $synopsisdemandeinterv->getFI();
    if (count($tabFI) > 0) {
        print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Fiche d'intervention") . '</th>';
        print '<td colspan=3 class="ui-widget-content">';
        print "<table class='nobordernopadding' width=100%>";
        foreach ($tabFI as $idFI) {
            require_once(DOL_DOCUMENT_ROOT . "/synopsisfichinter/class/synopsisfichinter.class.php");
            $inter = new Synopsisfichinter($db);
            $inter->fetch($idFI);
            print "<tr><td class='ui-widget-content'>" . $inter->getNomUrl(1) . "</td></tr>";
        }
        print "</table>";
        print '</td></tr>';
    }
    print '<tr><th class="ui-widget-header ui-state-default">Attribu&eacute; &agrave;';
//    if ($synopsisdemandeinterv->statuts == 0 || $user->rights->synopsisdemandeinterv->prisencharge)
    if ($synopsisdemandeinterv->statut < 2)
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=editAttrib&id=' . $_REQUEST['id'] . '">' . img_edit($langs->trans('Changer Attribution'), 1) . '</a>';

    print '    <td class="ui-widget-content" colspan=1>';
    if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'editAttrib') {
        print "<form action='" . $_SERVER['PHP_SELF'] . "?id=" . $_REQUEST['id'] . "&action=setAttUser' method=POST>";
        print "<table border=0 class='noborder'><tr><td>";
        print select_dolusersInGroup($html, 3, '', 'AttUserid', 0, '', 0, true);
        print "<td colspan=1>";
        print "<button class='butAction ui-widget-header ui-state-default ui-corner-all'>Attribuer</button>";
        print "</table>";
        print "</form>";
        print '</td>';
    } elseif ($synopsisdemandeinterv->fk_user_prisencharge) {
        $synopsisdemandeinterv->user_prisencharge->fetch($synopsisdemandeinterv->fk_user_prisencharge);
        print ($synopsisdemandeinterv->user_prisencharge ? $synopsisdemandeinterv->user_prisencharge->getNomUrl(1) : "") . '</td>';
    } else
        print "Personne";

    print '<th class="ui-widget-header ui-state-default">Effectu&eacute; par :';
    if ($synopsisdemandeinterv->statuts == 0 || $user->rights->synopsisdemandeinterv->prisencharge)
        print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $_REQUEST['id'] . '&action=editEffPar">' . img_edit($langs->trans('Changer intervenant'), 1) . '</a>';

    print '</th><td  colspan=1 class="ui-widget-content">';
    if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'editEffPar') {
        print "<form action='" . $_SERVER['PHP_SELF'] . "?id=" . $_REQUEST['id'] . "&action=setEffUser' method=POST>";
        print "<table border=0 class='noborder'><tr><td>";
        print $html->select_users('', 'EffUserid', 0, '', 0, true);
        print "<td>";
        print "<button class='butAction ui-widget-header ui-state-default ui-corner-all'>Changer</button>";
        print "</table>";
        print "</form>";
        print '</td></tr>';
    } else {
        print (isset($synopsisdemandeinterv->user_target) ? $synopsisdemandeinterv->user_target->getNomUrl(1) : "") . '</td></tr>';
    }
    // Extra
    $modulo = false;
    $tabExtra = $synopsisdemandeinterv->getExtra();
    foreach ($tabExtra as $res) {
//    $requete = "SELECT k.label,
//                       k.type,
//                       k.id,
//                       v.extra_value,
//                       k.fullLine
//                  FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_key as k
//             LEFT JOIN " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value as v ON v.extra_key_refid = k.id AND v.interv_refid = " . $synopsisdemandeinterv->id . " AND typeI = 'DI'
//                 WHERE (isQuality<>1 OR isQuality is null) AND isInMainPanel = 1 AND k.active = 1 AND inDi = 1 ORDER BY rang, label";
//    $sql = $db->query($requete);
//    while ($res = $db->fetch_object($sql)) {
        $colspan = 1;
        $modulo = !$modulo;
        if ($res->fullLine == 1) {
            $colspan = 3;
            $modulo = true;
        }
        if ($modulo) {
            print "<tr>";
        }
        if ($res->fullLine == 1) {
            $modulo = !$modulo;
        }
        if ($afficherExtraDi) {
            print '<th class="ui-widget-header ui-state-default">' . $res->label;
            if (isset($_REQUEST["action"]) && $_REQUEST['action'] == 'editExtra-' . $res->id) {
                switch ($res->type) {
                    case "date": {
                            print "<td  colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><form action='card.php?id=" . $synopsisdemandeinterv->id . "#anchor" . $res->id . "' method='POST'><input type='hidden' name='action' value='editExtra'>";
                            print '<a name="anchor' . $res->id . '"></a>'; // ancre
                            print "&nbsp;&nbsp;<input type='text' value='" . $res->extra_value . "' name='extraKey-" . $res->id . "' class='datePicker'>";
                            print "<input type='hidden' name='type-" . $res->id . "' value='date'>&nbsp;&nbsp;&nbsp;<button class='butAction'>OK</button></FORM>";
                        }
                        break;
                    case "textarea": {
                            print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><form action='card.php?id=" . $synopsisdemandeinterv->id . "#anchor" . $res->id . "' method='POST'><input type='hidden' name='action' value='editExtra'>";
                            print '<a name="anchor' . $res->id . '"></a>'; // ancre
                            print "&nbsp;&nbsp;<textarea name='extraKey-" . $res->id . "'>" . $res->extra_value . "</textarea>";
                            print "<input type='hidden' name='type-" . $res->id . "' value='comment'>&nbsp;&nbsp;&nbsp;<button class='butAction'>OK</button></FORM>";
                        }
                        break;
                    default:
                    case "text": {
                            print '<td colspan="' . $colspan . '" valign="middle" class="ui-widget-content"><form action="card.php?id=' . $synopsisdemandeinterv->id . '#anchor' . $res->id . '" method="POST"><input type="hidden" name="action" value="editExtra">';
                            print '<a name="anchor' . $res->id . '"></a>'; // ancre
                            print '&nbsp;&nbsp;<input value="' . $res->extra_value . '" type="text" name="extraKey-' . $res->id . '">';
                            print "<input type='hidden' name='type-" . $res->id . "' value='" . $res->type . "'>&nbsp;&nbsp;&nbsp;<button class='butAction'>OK</button></FORM>";
                        }
                        break;
                    case "datetime": {
                            print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><form action='card.php?id=" . $synopsisdemandeinterv->id . "#anchor" . $res->id . "' method='POST'><input type='hidden' name='action' value='editExtra'>";
                            print '<a name="anchor' . $res->id . '"></a>'; // ancre
                            print "&nbsp;&nbsp;<input type='text' value='" . $res->extra_value . "' name='extraKey-" . $res->id . "' class='dateTimePicker'>";
                            print "<input type='hidden' name='type-" . $res->id . "' value='datetime'>&nbsp;&nbsp;&nbsp;<button class='butAction'>OK</button></FORM>";
                        }
                        break;
                    case "checkbox": {
                            print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><form action='card.php?id=" . $synopsisdemandeinterv->id . "#anchor" . $res->id . "' method='POST'><input type='hidden' name='action' value='editExtra'>&nbsp;&nbsp;";
                            print '<a name="anchor' . $res->id . '"></a>'; // ancre
                            print "<input type='checkbox' " . ($res->extra_value == 1 ? 'CHECKED' : "") . "  name='extraKey-" . $res->id . "'>";
                            print "<input type='hidden' name='type-" . $res->id . "' value='checkbox'>&nbsp;&nbsp;&nbsp;<button class='butAction'>OK</button></FORM>";
                        }
                        break;
                    case "3stars": {
                            print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><form action='card.php?id=" . $synopsisdemandeinterv->id . "#anchor" . $res->id . "' method='POST'><input type='hidden' name='action' value='editExtra'>&nbsp;&nbsp;";
                            print '<a name="anchor' . $res->id . '"></a>'; // ancre
//                    print "<input type='checkbox' ".($res->extra_value==1?'CHECKED':"")."  name='extraKey-".$res->id."'>";
                            print starratingPhp("extraKey-" . $res->id, $res->extra_value, 3, $iter = 1);
                            print "<input type='hidden' name='type-" . $res->id . "' value='3stars'>&nbsp;&nbsp;&nbsp;<button class='butAction'>OK</button></FORM>";
                        }
                        break;
                    case "5stars": {
                            print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'><form action='card.php?id=" . $synopsisdemandeinterv->id . "#anchor" . $res->id . "' method='POST'><input type='hidden' name='action' value='editExtra'>&nbsp;&nbsp;";
                            print '<a name="anchor' . $res->id . '"></a>'; // ancre
//                    print "<input type='checkbox' ".($res->extra_value==1?'CHECKED':"")."  name='extraKey-".$res->id."'>";
                            print starratingPhp("extraKey-" . $res->id, $res->extra_value, 5, $iter = 1);
                            print "<input type='hidden' name='type-" . $res->id . "' value='5stars'>&nbsp;&nbsp;&nbsp;<button class='butAction'>OK</button></FORM>";
                        }
                        break;
                    case "radio": {
                            print "<td colspan='" . $colspan . "' valign='middle' class='ui-widget-content'>";
                            print '<a name="anchor' . $res->id . '"></a>'; // ancre
                            print "<form action='card.php?id=" . $synopsisdemandeinterv->id . "#anchor" . $res->id . "' method='POST'><input type='hidden' name='action' value='editExtra'>";
                            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_values_choice WHERE key_refid = " . $res->id;
                            $sql1 = $db->query($requete);
                            if ($db->num_rows($sql1) > 0) {
                                print "<table width=100%>";
                                while ($res1 = $db->fetch_object($sql1)) {
                                    print "<tr><td width=100%>" . $res1->label . "<td>";
                                    print "<input type='radio' value='" . $res1->value . "' name='extraKey-" . $res->id . "' ".($res->extra_value == $res1->value ? "checked='checked'" : "").">";
                                }
                                print "</table>";
                            }
                            print "<input type='hidden' name='type-" . $res->id . "' value='radio'>&nbsp;&nbsp;&nbsp;<button class='butAction'>OK</button></form>";
                        }
                        break;
                }
            } else {
                print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $_REQUEST['id'] . '&action=editExtra-' . $res->id . '#anchor' . $res->id . '">' . img_edit($langs->trans('Editer'), 1) . '</a>';
                if ($res->type == 'checkbox') {
                    print '    <td colspan="' . $colspan . '" class="ui-widget-content">';
                    print '<a name="anchor' . $res->id . '"></a>'; // ancre
                    print ($res->extra_value == 1 ? 'Oui' : 'Non') . '</td>';
                } else if ($res->type == 'radio') {
                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_values_choice WHERE key_refid = " . $res->id . " AND value = '" . $res->extra_value . "'";
                    $sql1 = $db->query($requete);
                    $res1 = $db->fetch_object($sql1);
                    print '    <td colspan="' . $colspan . '" class="ui-widget-content">';
                    print '<a name="anchor' . $res->id . '"></a>'; // ancre
                    if (isset($res1))
                        print $res1->label . '</td>';
                } else {
                    print '    <td colspan="' . $colspan . '" class="ui-widget-content">';
                    print '<a name="anchor' . $res->id . '"></a>'; // ancre
                    print $res->extra_value . '</td>';
                }
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
    }
    print "</table><br>";
//var_dump($conf->format_date_short);

    /*
     * Lignes d'intervention
     */
    print '<table class="noborder" width="100%">';

    $sql = 'SELECT ft.rowid, ft.description, ft.fk_synopsisdemandeinterv, ft.duree, ft.rang, t.id as typeId, t.label as typeinterv';
    $sql.= ', ft.date as date_intervention, qte, pu_ht, total_ht, fk_commandedet, ft.fk_contratdet , isForfait';
    $sql.= " FROM " . MAIN_DB_PREFIX . "synopsisdemandeintervdet as ft";
    $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv as t ON t.id = ft.fk_typeinterv ";
    $sql.= ' WHERE ft.fk_synopsisdemandeinterv = ' . $synopsisdemandeintervid;
    $sql.= ' ORDER BY ft.rang ASC, ft.rowid';
    $resql = $db->query($sql);
    if ($resql) {
        $num = $db->num_rows($resql);
        $i = 0;

        if ($num) {
            print '<tr class="liste_titre">';
            print '<td>' . $langs->trans('Description') . '</td>';
            print '<td>' . $langs->trans('Type') . '</td>';
            print '<td>' . $langs->trans('Date') . '</td>';
            print '<td>' . $langs->trans('Duration') . '</td>';
            if ($synopsisdemandeinterv->fk_commande > 0) {
                print '<td>' . $langs->trans('Prod. Com') . '</td>';
            }
            print '<td>' . $langs->trans('Forfait') . '</td>';
            print '<td align=right>' . $langs->trans('PU HT') . '</td>';
            print '<td align=center>' . $langs->trans('Qte') . '</td>';
            print '<td align=right>' . $langs->trans('Total HT') . '</td>';
            print '<td width="48" colspan="3">&nbsp;</td>';
            print "</tr>\n";
        }
        $var = true;
        while ($i < $num) {
            $objp = $db->fetch_object($resql);
            $var = !$var;
            // Ligne en mode visu
            if ((isset($_REQUEST["action"]) && $_REQUEST['action'] != 'editline') || (!isset($_REQUEST['ligne']) || $_REQUEST['ligne'] != $objp->rowid)) {
                $arrYesNo[0] = "Non";
                $arrYesNo[1] = "Oui";
                print '<tr ' . $bc[$var] . '>';
                print '<td>';
                print '<a name="' . $objp->rowid . '"></a>'; // ancre pour retourner sur la ligne
                print nl2br($objp->description);
                print "<td width='150'>" . $objp->typeinterv . "</td>";
                print '<td width="150">' . ($objp->date_intervention > 0 ? dol_print_date($db->jdate($objp->date_intervention), 'day') : "") . '</td>';
                print '<td width="150">' . ConvertSecondToTime($objp->duree) . '</td>';
                if ($objp->fk_commandedet > 0) {
                    $requete = "SELECT fk_product FROM " . MAIN_DB_PREFIX . "commandedet WHERE rowid = " . $objp->fk_commandedet;
                    $sql4 = $db->query($requete);
                    $res4 = $db->fetch_object($sql4);
                    if ($res4->fk_product > 0) {
                        $tmpProd = new Product($db);
                        $tmpProd->fetch($res4->fk_product);
                        print '<td width="150">' . $tmpProd->getNomUrl(1) . '</td>';
                    } else {
                        print "<td width='150'>&nbsp;";
                    }
                } else {
                    print "<td width='150'>&nbsp;";
                }

                print '<td width="150">' . $arrYesNo[$objp->isForfait] . '</td>';
                print '<td width="150" align=right>' . price($objp->pu_ht) . '</td>';
                print '<td width="150" align=center>' . $objp->qte . '</td>';
                print '<td width="150" align=right>' . price($objp->total_ht) . '</td>';

                print "</td>\n";


                // Icone d'edition et suppression
                if ($synopsisdemandeinterv->statut == 0 && $user->rights->synopsisdemandeinterv->creer) {
                    print '<td width=16 align="center">';
                    print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '&amp;action=editline&amp;ligne=' . $objp->rowid . '#' . $objp->rowid . '">';
                    print img_edit();
                    print '</a>';
                    print '</td>';
                    print '<td width=16 align="center">';
                    if ($conf->global->PRODUIT_CONFIRM_DELETE_LINE) {
                        #if ($conf->use_javascript_ajax)
                        #{
                        $url = $_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '&ligne=' . $objp->rowid . '&action=confirm_deleteline&confirm=yes';
                        print '<a href="#" onClick="dialogConfirm(\'' . $url . '\',\'' . $langs->trans('ConfirmDeleteDILine') . '\',\'' . $langs->trans("Yes") . '\',\'' . $langs->trans("No") . '\',\'deleteline' . $i . '\')">';
                        print img_delete();
                        #}
                        #else
                        #{
                        #    print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$synopsisdemandeinterv->id.'&amp;action=ask_deleteline&amp;ligne='.$objp->rowid.'">';
                        #    print img_delete();
                        #}
                    } else {
                        print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '&amp;action=deleteline&amp;ligne=' . $objp->rowid . '">';
                        print img_delete();
                    }
                    print '</a></td>';
                    if ($num > 1) {
                        print '<td width=16 align="center">';
                        if ($i > 0) {
                            print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '&amp;action=up&amp;rowid=' . $objp->rowid . '">';
                            print img_up();
                            print '</a>';
                        }
                        if ($i < $num - 1) {
                            print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '&amp;action=down&amp;rowid=' . $objp->rowid . '">';
                            print img_down();
                            print '</a>';
                        }
                        print '</td>';
                    } else {
                        print '<td width=16>&nbsp</td>';
                    }
                } else {
                    print '<td colspan="2">&nbsp;</td>';
                }

                print '</tr>';
                if ($activeLigneContrat && $synopsisdemandeinterv->fk_contrat > 0) {
                    require_once(DOL_DOCUMENT_ROOT . "/core/lib/contract.lib.php");
                    $contrat = getContratObj($synopsisdemandeinterv->fk_contrat);
                    $type = getTypeContrat_noLoad($synopsisdemandeinterv->fk_contrat);
                    if ($type == 7) {
                        $contrat->fetch($synopsisdemandeinterv->fk_contrat);
                        $contrat->fetch_lines();
                        print "<tr id='contratLigne" . $val->id . "' " . $bc[$var] . ">";
                        print "<td id='showDetail' class='showDetail'><span style='float: left;' class='ui-icon ui-icon-search'></span>D&eacute;tail<td colspan=10><table style='display: none;'>";
                        foreach ($contrat->lignes as $key => $val) {
                            if ($val->id == $objp->fk_contratdet) {
                                print "<tr><td>";
                                print "<ul style='list-style: none; padding-left: 0px; padding-top:0; margin-bottom:0; margin-top: 0px;'>";
                                print $contrat->display1Line($val);
                                print "</ul>";
                            }
                        }
                        print "</table>";
                    }
                }
            }

            // Ligne en mode update
            if ($synopsisdemandeinterv->statut == 0 && isset($_REQUEST["action"]) && $_REQUEST["action"] == 'editline' && $user->rights->synopsisdemandeinterv->creer && $_REQUEST["ligne"] == $objp->rowid) {
                print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '#' . $objp->rowid . '" method="post">';
                print '<input type="hidden" name="action" value="updateligne">';
                print '<input type="hidden" name="synopsisdemandeintervid" value="' . $synopsisdemandeinterv->id . '">';
                print '<input type="hidden" name="ligne" value="' . $_REQUEST["ligne"] . '">';
                print '<tr ' . $bc[$var] . '>';
                print '<td>';
                print '<a name="' . $objp->rowid . '"></a>'; // ancre pour retourner sur la ligne
                // editeur wysiwyg
                if (isset($conf->fckeditor->enabled) && $conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS) {
                    require_once(DOL_DOCUMENT_ROOT . "/core/lib/doleditor.class.php");
                    $doleditor = new DolEditor('desc', $objp->description, 164, 'dolibarr_details');
                    $doleditor->Create();
                } else {
                    /* print '<textarea name="desc" cols="40" class="flat" rows="' . ROWS_2 . '">' . preg_replace('/<br[ ]*\/?>/', "\n", $objp->description) . '</textarea>'; */
                    print '<textarea style="width:320px;" name="description">' . preg_replace('/<br[ ]*\/?>/', "\n", $objp->description) . '</textarea>';
                }
                print '</td>';

                print "<td><SELECT name='fk_typeinterv'>";
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv WHERE active = 1 ORDER BY rang";
                $sql1 = $db->query($requete);
                print "<OPTION value='-1'>Selectionner-></OPTION>";
                while ($res1 = $db->fetch_object($sql)) {
                    if ($res1->id == $objp->typeId) {
                        print "<OPTION SELECTED value='" . $res1->id . "'>" . $res1->label . "</OPTION>";
                    } else {
                        print "<OPTION value='" . $res1->id . "'>" . $res1->label . "</OPTION>";
                    }
                }
                print "</SELECT></td>";

                // Date d'intervention
                print '<td>';
                if ($objp->date_intervention <= 0)
                    $objp->date_intervention = null;
                $html->select_date($objp->date_intervention, 'di', 0, 0, 0, "date_intervention");
                print '</td>';

                // Duration
                print '<td>';
                $html->select_duration('duration', $objp->duree);
                print '</td>';

                require_once(DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
                require_once(DOL_DOCUMENT_ROOT . '/product/class/product.class.php');
                if ($synopsisdemandeinterv->fk_commande > 0) {
                    $com = new Synopsis_Commande($db);
                    $com->fetch($synopsisdemandeinterv->fk_commande);
                    $com->fetch_group_lines(0, 0, 0, 0, 1);
                    print "<td><select name='comLigneId'>";
                    print "<option value='0'>S&eacute;lectionner-></option>";
                    foreach ($com->lines as $key => $val) {
                        if ($val->fk_product > 0) {
                            $prod = new Product($db);
                            $prod->fetch($val->fk_product);
                            if ($prod->type == 1 || $prod->type == 3) {
                                if ($val->rowid == $objp->fk_commandedet && $objp->fk_commandedet > 0) {
                                    print "<option selected='selected' value='" . $val->rowid . "'>" . $prod->ref . "   " . price($val->total_ht) . "&euro; " . $val->description . " </option>";
                                } elseif ($prod->ref != '') {
                                    print "<option value='" . $val->rowid . "'>" . $prod->ref . "   " . price($val->total_ht) . "&euro; " . $val->description . "</option>";
                                }
                            }
                        }
                    }
                    print "</select>";
                }


                // Forfait
                print '<td width=32 align=center>';
                print "<input type='checkbox' name='isForfait' " . ($objp->isForfait == 1 ? 'Checked="checked"' : "") . ">";
                print '</td>';

                // pu_ht
                print '<td>';
                print "<input size=6 type='text' name='pu_ht' value='" . $objp->pu_ht . "'>";
                print '</td>';

                // pu_ht
                print '<td>';
                print "<input size=2 type='text' name='qte' value='" . $objp->qte . "'>";
                print '</td>';


                print '<td align="center" colspan="5" valign="center"><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
                print '<br /><input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '"></td>';
                print '</tr>' . "\n";

                if ($activeLigneContrat && isset($synopsisdemandeinterv->fk_contrat)) {
                    require_once(DOL_DOCUMENT_ROOT . "/core/lib/contract.lib.php");
                    $contrat = getContratObj($synopsisdemandeinterv->fk_contrat);
                    $type = getTypeContrat_noLoad($synopsisdemandeinterv->fk_contrat);
                    if ($type == 7) {
                        $contrat->fetch($synopsisdemandeinterv->fk_contrat);
                        $contrat->fetch_lines();
                        print "<tr " . $bc[$var] . "><td colspan=8 style='border:1px Solid;'><table>";
                        foreach ($contrat->lignes as $key => $val) {
                            if ($val->statut >= 0 && $val->statut < 5) {
                                $extra = "";
                                if ($val->id == $objp->fk_contratdet)
                                    $extra = "checked";
                                print "<tr><td><input " . $extra . " type=radio name='fk_contratdet' value='" . $val->id . "'><td>";
                                print "<ul style='list-style: none; padding-left: 0px; padding-top:0; margin-bottom:0; margin-top: 0px;'>";
                                print $contrat->display1Line($val);
                                print "</ul>";
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
        dol_print_error($db, "Erreur Inconnue " . $sql);
    }

    /*
     * Ajouter une ligne
     */
    if ($synopsisdemandeinterv->statut == 0 && $user->rights->synopsisdemandeinterv->creer && $_REQUEST["action"] <> 'editline') {
        print '<tr class="liste_titre" style="border: 1px Solid #0073EA">';
        print '<td>';
        print '<a name="add"></a>'; // ancre
        print $langs->trans('Description') . '</td>';
        print '<td>' . $langs->trans('Type') . '</td>';
        print '<td>' . $langs->trans('Date') . '</td>';
        print '<td>' . $langs->trans('Duration') . '</td>';
        if ($synopsisdemandeinterv->fk_commande > 0) {
            print '<td>' . $langs->trans('Commande') . '</td>';
        }
        print '<td>' . $langs->trans('Forfait') . '</td>';
        print '<td>' . $langs->trans('PU HT') . '</td>';
        print '<td>' . $langs->trans('Qt&eacute;') . '</td>';

        print '<td colspan="4">&nbsp;</td>';
        print "</tr>\n";

        // Ajout ligne d'DI
        print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '#add" name="addinter" method="post">';
        print '<input type="hidden" name="synopsisdemandeintervid" value="' . $synopsisdemandeinterv->id . '">';
        print '<input type="hidden" name="action" value="addligne">';

//prix u, qté , total ...

        $var = true;

        if ($synopsisdemandeinterv->fk_contrat > 0) {
            require_once(DOL_DOCUMENT_ROOT . "/core/lib/contract.lib.php");
            $contrat = getContratObj($synopsisdemandeinterv->fk_contrat);
            $type = getTypeContrat_noLoad($synopsisdemandeinterv->fk_contrat);
            if ($type == 7) {
                print '<tr ' . $bc[$var] . "  style='border-left:1px Solid #0073EA; border-right:1px Solid #0073EA;' >\n";
            } else {
                print '<tr ' . $bc[$var] . "  style='border-left:1px Solid #0073EA; border-bottom:1px Solid #0073E4; border-right:1px Solid #0073EA;' >\n";
            }
        } else {
            print '<tr ' . $bc[$var] . "  style='border-left:1px Solid #0073EA; border-bottom:1px Solid #0073E4; border-right:1px Solid #0073EA;' >\n";
        }
        print '<td>';
        // editeur wysiwyg
        if (isset($conf->fckeditor->enabled) && $conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS) {
            require_once(DOL_DOCUMENT_ROOT . "/core/lib/doleditor.class.php");
            $doleditor = new DolEditor('np_desc', '', 100, 'dolibarr_details');
            $doleditor->Create();
        } else {
            print '<textarea style="width: 100%!important; height: 2em!important;" class="flat" cols="40" name="np_desc" rows="' . ROWS_2 . '"></textarea>';
        }
        print '</td>';
        print "<td><SELECT name='fk_typeinterv'>";
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv WHERE active = 1 ORDER BY rang";
        $sql1 = $db->query($requete);
        print "<OPTION value='-1'>Selectionner-></OPTION>";
        while ($res1 = $db->fetch_object($sql)) {
            if ($res1->default == 1) {
                print "<OPTION SELECTED value='" . $res1->id . "'>" . $res1->label . "</OPTION>";
            } else {
                print "<OPTION value='" . $res1->id . "'>" . $res1->label . "</OPTION>";
            }
        }
        print "</SELECT></td>";
        // Date d'DI
        print '<td>';
        $html->select_date(time(), 'di', 0, 0, 0, "addinter");
        print '</td>';

        // Duree
        print '<td>';
        $html->select_duration('duration');
        print '</td>';

        require_once(DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
        require_once(DOL_DOCUMENT_ROOT . '/product/class/product.class.php');
        if ($synopsisdemandeinterv->fk_commande > 0) {

            $com = new Synopsis_Commande($db);
            $com->fetch($synopsisdemandeinterv->fk_commande);
            $com->fetch_group_lines(0, 0, 0, 0, 1);
            print "<td><select name='comLigneId'>";
            foreach ($com->lines as $key => $val) {
                if ($val->fk_product > 0) {
                    $prod = new Product($db);
                    $prod->fetch($val->fk_product);
                    if ($prod->type == 1 || $prod->type == 3)
                        print "<option value='" . $val->id . "'>" . $prod->ref . "   " . price($val->total_ht) . "&euro;</option>";
                }
            }
            print "</select>";
        }


        // Forfait
        print '<td width=32 align=center>';
        print "<input type='checkbox' name='isForfait'>";
        print '</td>';

        // pu_ht
        print '<td>';
        print "<input size=6 type='text' name='pu_ht' style='text-align: center'>";
        print '</td>';

        // qte
        print '<td>';
        print "<input size=2 type='text' name='qte' style='text-align: center' value=1>";
        print '</td>';


        print '<td align="center" valign="middle" colspan="4"><input type="submit" class="button" value="' . $langs->trans('Add') . '" name="addligne"></td>';
        print '</tr>';
        if ($activeLigneContrat && $synopsisdemandeinterv->fk_contrat > 0) {
            require_once(DOL_DOCUMENT_ROOT . "/core/lib/contract.lib.php");
            $contrat = getContratObj($synopsisdemandeinterv->fk_contrat);
            $type = getTypeContrat_noLoad($synopsisdemandeinterv->fk_contrat);
            if ($type == 7) {
                $contrat->fetch($synopsisdemandeinterv->fk_contrat);
                $contrat->fetch_lines();
                print "<tr><td colspan=11 style='border:1px Solid; border-top: 0px;'><table width=100%>";
                foreach ($contrat->lignes as $key => $val) {
                    if ($val->statut >= 0 && $val->statut < 5) {
                        print "<tr><td><input type=radio name='fk_contratdet' value='" . $val->id . "'><td>";
                        print "<ul style='list-style: none; padding-left: 0px; padding-top:0; margin-bottom:0; margin-top: 0px;'>";
                        print $contrat->display1Line($val);
                        print "</ul>";
                    }
                }
                print "</table>";

                print "<style>li.ui-state-default table{ width:100%;}</style>";
            }
        }

        print '</form>';
    }

    print '</table>';

    print '</div>';
    print "\n";

    /**
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';

    if ($user->societe_id == 0) {
        //var_dump($_REQUEST);
        // Validate
        if ($synopsisdemandeinterv->statut == 0 && $user->rights->synopsisdemandeinterv->creer) {
            print '<button class="butAction" ';
            $url = $_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '&action=confirm_validate&confirm=yes&model=' . $synopsisdemandeinterv->modelpdf;
            print 'href="#" onClick="dialogConfirm(\'' . $url . '\',\'' . dol_escape_js($langs->trans('ConfirmValidateDI')) . '\',\'' . $langs->trans("Yes") . '\',\'' . $langs->trans("No") . '\',\'validate\')"';
            print '>' . $langs->trans("Valid") . '</button>';
        }

        // Delete
        if ($synopsisdemandeinterv->statut == 0 && $user->rights->synopsisdemandeinterv->supprimer) {
            print '<button class="butActionDelete" ';
            $url = $_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '&action=confirm_delete&confirm=yes';
            print ' onClick="dialogConfirm(\'' . $url . '\',\'' . $langs->trans("ConfirmDeleteDI") . '\',\'' . $langs->trans("Yes") . '\',\'' . $langs->trans("No") . '\',\'delete\')"';
            print '>' . $langs->trans('Delete') . '</button>';
        }
//        print $synopsisdemandeinterv->statut;
        if ($synopsisdemandeinterv->statut == 1 && $user->rights->synopsisdemandeinterv->prisencharge) {
            print '<button class="butAction " ';
            $url = $_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '&action=confirm_PrisEnCharge&confirm=yes';
            print '  onClick="dialogConfirm(\'' . $url . '\',\'' . $langs->trans("ConfirmPrisEnChargeDI") . '\',\'' . $langs->trans("Yes") . '\',\'' . $langs->trans("No") . '\',\'Prise En Charge\')"';
            print '>' . $langs->trans('PrisEnCharge') . '</button>';
        }


        if ($synopsisdemandeinterv->statut == 1 && $user->rights->synopsisdemandeinterv->prisencharge && $synopsisdemandeinterv->fk_user_prisencharge) {
            print '<button class="butAction " ';
            $url = $_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '&action=confirm_PrisEnCharge&confirm=yes&fk_user=' . $synopsisdemandeinterv->fk_user_prisencharge;
            print '  onClick="dialogConfirm(\'' . $url . '\',\'' . $langs->trans("ConfirmPrisEnChargeDI") . '\',\'' . $langs->trans("Yes") . '\',\'' . $langs->trans("No") . '\',\'Prise En Charge\')"';
            print '>' . $langs->trans('PrisEnChargeTech') . '</button>';
        }


        if ($synopsisdemandeinterv->statut > 0 && $synopsisdemandeinterv->statut < 3 && $user->rights->synopsisdemandeinterv->edit_after_validation) {
            print '<button class="butAction" ';
            $url = $_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '&action=modification&confirm=yes';
            print ' onClick="dialogConfirm(\'' . $url . '\',\'' . $langs->trans("ConfirmModifDI") . '\',\'' . $langs->trans("Yes") . '\',\'' . $langs->trans("No") . '\',\'Modification\')"';
            print '>' . $langs->trans('Modifier') . '</button>';
        }

        if ($synopsisdemandeinterv->statut > 1 && $synopsisdemandeinterv->statut < 3 && $user->rights->synopsisdemandeinterv->prisencharge) {
            print '<button class="butAction disableadOnClick" ';
            print 'onClick="location.href=\'' . $_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '&amp;action=createFI\'"';
            print '>' . $langs->trans('createFI') . '</button>';
        }
        if ($synopsisdemandeinterv->statut == 2 && $user->rights->synopsisdemandeinterv->cloture) {
            print '<button class="butAction" ';
            $url = $_SERVER["PHP_SELF"] . '?id=' . $synopsisdemandeinterv->id . '&action=confirm_Cloture&confirm=yes';
            print ' onClick="dialogConfirm(\'' . $url . '\',\'' . $langs->trans("ConfirmClotureDIJS") . '\',\'' . $langs->trans("Yes") . '\',\'' . $langs->trans("No") . '\',\'Cloture\')"';
            print '>' . $langs->trans('Cl&ocirc;ture') . '</button>';
        }
    }

    print '</div>';

    print '<table width="100%"><tr><td width="50%" valign="top">';
    /*
     * Documents generes
     */

    $filename = sanitize_string($synopsisdemandeinterv->ref);
    $filedir = $conf->synopsisdemandeinterv->dir_output . "/" . $synopsisdemandeinterv->ref;
    $urlsource = $_SERVER["PHP_SELF"] . "?id=" . $synopsisdemandeinterv->id;
    $genallowed = $user->rights->synopsisdemandeinterv->creer;
    $delallowed = $user->rights->synopsisdemandeinterv->supprimer;
    $genallowed = 1;
    $delallowed = 1;

    $var = true;
    print "<br>\n";
    $somethingshown = $formfile->show_documents('synopsisdemandeinterv', $filename, $filedir, $urlsource, $genallowed, $delallowed, $synopsisdemandeinterv->modelpdf);

    print "</td><td>";
    print "&nbsp;</td>";
    print "</tr></table>\n";

    $societe = new Societe($db);
    $societe->fetch($synopsisdemandeinterv->socid);

    echo "<br/>";
    echo "<br/>";
    show_actions_par_type('synopsisdemandeinterv', $synopsisdemandeinterv->id, $societe);
    echo "<br/>";
    show_actions_par_type('synopsisdemandeinterv', $synopsisdemandeinterv->id, $societe, true);
}




if (isset($synopsisdemandeinterv->fk_commande) && $synopsisdemandeinterv->fk_commande > 0) {
    print "<br/><br/><div id='replaceResult'>";

    $idCommande = $synopsisdemandeinterv->fk_commande;
    require(DOL_DOCUMENT_ROOT . "/Synopsis_PrepaCommande/ajax/getResults-html_response.php");

    print "</div>";
}



$db->close();

print <<<EOF

<script>
    jQuery(document).ready(function(){
        jQuery("#starrating :radio.star").rating();
//        jQuery("#starrating :radio.star").rating("select",val);
//        jQuery("#starrating :radio.star").rating('disable');

    });
</script>
EOF;


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

    $returnstring .= ($days) ? (($days == 1) ? "1 j" : $days . "j") : "";
    $returnstring .= ($days && $hours && !$minutes && !$seconds) ? "" : "";
    $returnstring .= ($hours) ? ( ($hours == 1) ? " 1h" : " " . $hours . "h") : "";
    $returnstring .= (($days || $hours) && ($minutes && !$seconds)) ? "  " : " ";
    $returnstring .= ($minutes) ? ( ($minutes == 1) ? " 1 min" : " " . $minutes . "min") : "";
    //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
    //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
    return ($returnstring);
}

?>
