<?php

/*
 *
 */
/*
 */


/**
 *       \file       htdocs/contrat/card.php
 *       \ingroup    contrat
 *       \brief      Fiche contrat
 *       \version    $Id: card.php,v 1.132 2008/08/07 20:46:15 eldy Exp $
 */
require_once("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php');
if ($conf->projet->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
if ($conf->propal->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
if ($conf->contrat->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Contrat/class/contrat.class.php");

$langs->load("contracts");
$langs->load("orders");
$langs->load("companies");
$langs->load("bills");
$langs->load("products");
//var_dump($_REQUEST);
// Security check

if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'contrat', $contratid, 'contrat');



/*
 * Actions
 */

if ($_REQUEST['action'] == "doChangeDateContrat") {
    $date = $_REQUEST['dateContrat'];
    if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/', $date, $arrMatch)) {
        $dateUS = $arrMatch[3] . '-' . $arrMatch[2] . "-" . $arrMatch[1];
//        $requete = "SELECT rowid as id, fk_contrat as idContrat,date_ouverture as dateOuv, (date_fin_validite - date_ouverture) as dureeJ FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat = ".$_REQUEST['id'];

        $requete = "Update ".MAIN_DB_PREFIX."contratdet set date_ouverture_prevue = '" . $dateUS . "', date_fin_validite = DATE_ADD(date_ouverture_prevue, INTERVAL DATEDIFF(date_fin_validite, date_ouverture) DAY) , date_ouverture = date_ouverture_prevue where fk_contrat = " . $_REQUEST['id'] . ";";
        $requete2 = "Update ".MAIN_DB_PREFIX."Synopsis_contratdet_GMAO set DateDeb = '" . $dateUS . "' WHERE contratdet_refid IN (SELECT rowid FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat = " . $_REQUEST['id'] . ");";
        $sql = $db->query($requete);
        $sql = $db->query($requete2);
        $requete = "UPDATE ".MAIN_DB_PREFIX."contrat set date_contrat = '" . $dateUS . "' WHERE rowid = " . $_REQUEST['id'];
        $sql = $db->query($requete);
    }
    if ($result > 0) {
        header("Location: card.php?id=" . $_REQUEST['id']);
        exit;
    } else {
        $mesg = $contrat->error;
    }
}

if ($_REQUEST['action'] == 'generatePdf' || $_REQUEST['action'] == 'builddoc') {
    //
    $contrat = getContratObj($_REQUEST["id"]);
    $contrat->fetch($_REQUEST['id']);

    if ($conf->global->MAIN_MODULE_BABELGA == 1 && $_REQUEST['id'] > 0 && ($contrat->typeContrat == 6 || $contrat->typeContrat == 5)) {
        require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsiscontrat/modules_contratGA.php");
        contratGA_pdf_create($db, $contrat->id, $_REQUEST['model']);
    } else if ($conf->global->MAIN_MODULE_BABELGMAO == 1 && $_REQUEST['id'] > 0 && ($contrat->typeContrat == 7 || $contrat->typeContrat == 2 || $contrat->typeContrat == 3 || $contrat->typeContrat == 4)) {
        require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsiscontrat/modules_contratGMAO.php");
        contratGMAO_pdf_create($db, $contrat->id, $_REQUEST['model']);
    } else {
        require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsiscontrat/modules_synopsiscontrat.php");
        contrat_pdf_create($db, $contrat->id, $_REQUEST['model']);
    }
    header('location: card.php?id=' . $contrat->id . "#documentAnchor");
}

if ($_REQUEST['action'] == "chgSrvAction") {
    $newSrc = $_REQUEST['Link'];
    $contrat = $contrat = getContratObj($_REQUEST["id"]);
    $contrat->fetch($_REQUEST['id']);
    $requete = "UPDATE ".MAIN_DB_PREFIX."contrat set linkedTo='" . $newSrc . "' WHERE rowid =" . $_REQUEST['id'];
    $resql = $db->query($requete);
//    print $requete;
}
if ($_POST["action"] == 'confirm_active' && $_POST["confirm"] == 'yes' && $user->rights->contrat->activer) {
    $contrat = $contrat = getContratObj($_REQUEST["id"]);
    $contrat->fetch($_REQUEST["id"]);

    $result = $contrat->active_line($user, $_REQUEST["ligne"], $_REQUEST["date"], $_REQUEST["dateend"]);

    if ($result > 0) {
        Header("Location: card.php?id=" . $contrat->id);
        exit;
    } else {
        $mesg = $contrat->error;
    }
}

if ($_POST["action"] == 'confirm_closeline' && $_POST["confirm"] == 'yes' && $user->rights->contrat->activer) {
    $contrat = $contrat = getContratObj($_REQUEST["id"]);
    $contrat->fetch($_REQUEST["id"]);
    $result = $contrat->close_line($user, $_REQUEST["ligne"], $_REQUEST["dateend"]);

    if ($result > 0) {
        Header("Location: card.php?id=" . $contrat->id);
        exit;
    } else {
        $mesg = $contrat->error;
    }
}

// Si ajout champ produit predefini
if ($_POST["mode"] == 'predefined') {
    $date_start = '';
    $date_end = '';
    if ($_POST["date_startmonth"] && $_POST["date_startday"] && $_POST["date_startyear"]) {
        $date_start = dol_mktime(12, 0, 0, $_POST["date_startmonth"], $_POST["date_startday"], $_POST["date_startyear"]);
    }
    if ($_POST["date_endmonth"] && $_POST["date_endday"] && $_POST["date_endyear"]) {
        $date_end = dol_mktime(12, 0, 0, $_POST["date_endmonth"], $_POST["date_endday"], $_POST["date_endyear"]);
    }
}

// Si ajout champ produit libre
if ($_POST["mode"] == 'libre') {
    $date_start_sl = '';
    $date_end_sl = '';
    if ($_POST["date_start_slmonth"] && $_POST["date_start_slday"] && $_POST["date_start_slyear"]) {
        $date_start_sl = dol_mktime(12, 0, 0, $_POST["date_start_slmonth"], $_POST["date_start_slday"], $_POST["date_start_slyear"]);
    }
    if ($_POST["date_end_slmonth"] && $_POST["date_end_slday"] && $_POST["date_end_slyear"]) {
        $date_end_sl = dol_mktime(12, 0, 0, $_POST["date_end_slmonth"], $_POST["date_end_slday"], $_POST["date_end_slyear"]);
    }
}

// Param si updateligne
$date_start_update = '';
$date_end_update = '';
$date_start_real_update = '';
$date_end_real_update = '';
if ($_POST["date_start_updatemonth"] && $_POST["date_start_updateday"] && $_POST["date_start_updateyear"]) {
    $date_start_update = dol_mktime(12, 0, 0, $_POST["date_start_updatemonth"], $_POST["date_start_updateday"], $_POST["date_start_updateyear"]);
}
if ($_POST["date_end_updatemonth"] && $_POST["date_end_updateday"] && $_POST["date_end_updateyear"]) {
    $date_end_update = dol_mktime(12, 0, 0, $_POST["date_end_updatemonth"], $_POST["date_end_updateday"], $_POST["date_end_updateyear"]);
}
if ($_POST["date_start_real_updatemonth"] && $_POST["date_start_real_updateday"] && $_POST["date_start_real_updateyear"]) {
    $date_start_real_update = dol_mktime(12, 0, 0, $_POST["date_start_real_updatemonth"], $_POST["date_start_real_updateday"], $_POST["date_start_real_updateyear"]);
}
if ($_POST["date_end_real_updatemonth"] && $_POST["date_end_real_updateday"] && $_POST["date_end_real_updateyear"]) {
    $date_end_real_update = dol_mktime(12, 0, 0, $_POST["date_end_real_updatemonth"], $_POST["date_end_real_updateday"], $_POST["date_end_real_updateyear"]);
}


/*
 * Actions
 */

if ($_REQUEST['typeContrat'] == 'LocationFinanciere' && $_REQUEST["action"] == 'add' && $conf->global->MAIN_MODULE_BABELGA == 1) {
    $datecontrat = dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);

    require_once(DOL_DOCUMENT_ROOT . "/Babel_GA/ContratGA.class.php");
    $contrat = new ContratGA($db);

    $contrat->socid = $_POST["socid"];
    $contrat->date_contrat = $datecontrat;
    $contrat->is_financement = 1;
    $contrat->cessionnaire_refid = $_REQUEST['cessionnaire_id'];

    $contrat->commercial_suivi_id = $_POST["commercial_suivi_id"];
    $contrat->commercial_signature_id = $_POST["commercial_signature_id"];

    $contrat->client_signataire_refid = $_REQUEST['Client_contactid'];


    $contrat->note = trim($_POST["note"]);
    $contrat->projetid = trim($_POST["projetid"]);
    $contrat->remise_percent = trim($_POST["remise_percent"]);
    $contrat->ref = $contrat->getNextNumRef("");
    $contrat->linkedTo = trim($_REQUEST['Link']);
    $contrat->typeContrat = 6;
    $result = $contrat->create($user, $langs, $conf);
    if ($result > 0) {
        Header("Location: card.php?id=" . $contrat->id);
        exit;
    } else {
        $mesg = '<div class="error ui-state-error">' . $contrat->error . '</div>';
    }
    $_REQUEST["socid"] = $_POST["socid"];
    $_REQUEST["action"] = 'create';
    $action = '';
} else if ($_POST["action"] == 'add') {
    $datecontrat = dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);

    $contrat = new Synopsis_Contrat($db);

    $contrat->socid = $_POST["socid"];
    $contrat->date_contrat = $datecontrat;

    switch ($_REQUEST['typeContrat']) {
        case 'Mixte': {
                $contrat->typeContrat = 7;
            }
            break;
        case 'LocationFinanciere': {
                $contrat->typeContrat = 6;
            }
            break;
        case 'Location': {
                $contrat->typeContrat = 5;
            }
            break;
        case 'Libre': {
                $contrat->typeContrat = 0;
            }
            break;
        case 'Service': {
                $contrat->typeContrat = 1;
            }
            break;
        case 'Ticket': {
                $contrat->typeContrat = 2;
            }
            break;
        case 'Maintenance': {
                $contrat->typeContrat = 3;
            }
            break;
        case 'SAV': {
                $contrat->typeContrat = 4;
            }
            break;
    }

    $contrat->commercial_suivi_id = $_POST["commercial_suivi_id"];
    $contrat->commercial_signature_id = $_POST["commercial_signature_id"];

    $contrat->note = trim($_POST["note"]);
    $contrat->projetid = trim($_POST["projetid"]);
    $contrat->remise_percent = trim($_POST["remise_percent"]);
    $contrat->ref = $contrat->getNextNumRef("");
    $contrat->linkedTo = trim($_REQUEST['Link']);

    $result = $contrat->create($user, $langs, $conf);
    if ($result > 0) {
        if ($_REQUEST['condid'] >= 0 && $_REQUEST['paiementtype'] >= 0) {
            $requete = "UPDATE ".MAIN_DB_PREFIX."contrat
                           SET condReg_refid = " . $_REQUEST['condid'] . " ,
                               modeReg_refid = " . $_REQUEST['paiementtype'] . "
                         WHERE rowid =" . $contrat->id;
            $sql = $db->query($requete);
        }
        if ($_REQUEST['returnPrepacom'] > 0) {
            header("Location: " . DOL_URL_ROOT . "/Synopsis_PrepaCommande/prepacommande.php?id=" . $_REQUEST['returnPrepacom']);
        } else {
            header("Location: card.php?id=" . $contrat->id);
        }
        exit;
    } else {
        $mesg = '<div class="error ui-state-error">' . $contrat->error . '</div>';
    }
    $_REQUEST["socid"] = $_POST["socid"];
    $_REQUEST["action"] = 'create';
    $action = '';
}

if ($_POST["action"] == 'classin') {
    $contrat = new Synopsis_Contrat($db);
    $contrat->fetch($_REQUEST["id"]);
    $contrat->setProject($_POST["projetid"]);
}

if ($_POST["action"] == 'addligne' && $user->rights->contrat->creer) {
    if ($_POST["pqty"] && (($_POST["pu"] != '' && $_POST["desc"]) || $_POST["p_idprod"])) {
        $contrat = new Synopsis_Contrat($db);
        $ret = $contrat->fetch($_REQUEST["id"]);
        if ($ret < 0) {
            dol_print_error($db, $commande->error);
            exit;
        }
        $ret = $contrat->fetch_client();

        $date_start = '';
        $date_end = '';
        // Si ajout champ produit libre
        if ($_POST['mode'] == 'libre') {
            if ($_POST['date_start_slyear'] && $_POST['date_start_slmonth'] && $_POST['date_start_slday']) {
                $date_start = dol_mktime(12, 0, 0, $_POST['date_start_slmonth'], $_POST['date_start_slday'], $_POST['date_start_slyear']);
            }
            if ($_POST['date_end_slyear'] && $_POST['date_end_slmonth'] && $_POST['date_end_slday']) {
                $date_end = dol_mktime(12, 0, 0, $_POST['date_end_slmonth'], $_POST['date_end_slday'], $_POST['date_end_slyear']);
            }
        }
        // Si ajout champ produit predefini
        if ($_POST['mode'] == 'predefined') {
            if ($_POST['date_startyear'] && $_POST['date_startmonth'] && $_POST['date_startday']) {
                $date_start = dol_mktime(12, 0, 0, $_POST['date_startmonth'], $_POST['date_startday'], $_POST['date_startyear']);
            }
            if ($_POST['date_endyear'] && $_POST['date_endmonth'] && $_POST['date_endday']) {
                $date_end = dol_mktime(12, 0, 0, $_POST['date_endmonth'], $_POST['date_endday'], $_POST['date_endyear']);
            }
        }

        $price_base_type = 'HT';

        // Ecrase $pu par celui du produit
        // Ecrase $desc par celui du produit
        // Ecrase $txtva par celui du produit
        // Ecrase $base_price_type par celui du produit
        if ($_POST['p_idprod']) {
            $prod = new Product($db, $_POST['p_idprod']);
            $prod->fetch($_POST['p_idprod']);

            $tva_tx = get_default_tva($mysoc, $contrat->client, $prod->tva_tx);
            $tva_npr = get_default_npr($mysoc, $contrat->client, $prod->tva_npr);

            // On defini prix unitaire
            if ($conf->global->PRODUIT_MULTIPRICES == 1) {
                $pu_ht = $prod->multiprices[$contrat->client->price_level];
                $pu_ttc = $prod->multiprices_ttc[$contrat->client->price_level];
                $price_base_type = $prod->multiprices_base_type[$contrat->client->price_level];
            } else {
                $pu_ht = $prod->price;
                $pu_ttc = $prod->price_ttc;
                $price_base_type = $prod->price_base_type;
            }

            // On reevalue prix selon taux tva car taux tva transaction peut etre different
            // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
            if ($tva_tx != $prod->tva_tx) {
                if ($price_base_type != 'HT') {
                    $pu_ht = price2num($pu_ttc / (1 + ($tva_tx / 100)), 'MU');
                } else {
                    $pu_ttc = price2num($pu_ht * (1 + ($tva_tx / 100)), 'MU');
                }
            }

            $desc = $prod->description;
            $desc.= $prod->description && $_POST['desc'] ? "\n" : "";
            $desc.= $_POST['desc'];
        } else {
            $pu_ht = $_POST['pu'];
            $tva_tx = preg_replace('/\*/i', '', $_POST['tva_tx']);
            $tva_npr = preg_match('/\*/i', $_POST['tva_tx']) ? 1 : 0;
            $desc = $_POST['desc'];
        }

        $info_bits = 0;
        if ($tva_npr)
            $info_bits |= 0x01;

        // Insert line
        $result = $contrat->addline(
                        $desc,
                        $pu_ht,
                        $_POST["pqty"],
                        $tva_tx,
                        $_POST["p_idprod"],
                        $_POST["premise"],
                        $date_start,
                        $date_end,
                        $price_base_type,
                        $pu_ttc,
                        $info_bits
        );




        if ($result > 0) {

            //Modif GLE post 1.0
            //Financement
            $isModuleFinancement = true;
            if ($isModuleFinancement == true && $_REQUEST['financOK'] == "on") {
                $tx = floatval($_REQUEST['financTx']);
                $tx = preg_replace('/,/', ".", $tx);
                if ($tx < 1)
                    $tx *= 100;
                $txAchat = floatval($_REQUEST['financTxAchat']);
                $txAchat = preg_replace('/,/', ".", $txAchat);
                if ($txAchat < 1)
                    $txAchat *= 100;
                $dureeFin = intval($_REQUEST['financDuree']);
                //Ajoute dans la table Babel_financement financTxAchat financTx financDuree
                $requete = "INSERT INTO Babel_financement (taux, tauxAchat, duree, fk_contratdet)
                                  VALUES ('" . $tx . "','" . $txAchat . "','" . $dureeFin . "','" . $contrat->newContractLigneId . "')";
                $db->query($requete);
            } else if ($isModuleFinancement == true) {
                $requete = "DELETE FROM Babel_financement WHERE fk_contratdet =" . $contrat->newContractLigneId;
                $db->query($requete);
            }

            //fin modif


            /*
              if ($_REQUEST['lang_id'])
              {
              $outputlangs = new Translate("",$conf);
              $outputlangs->setDefaultLang($_REQUEST['lang_id']);
              }
              contrat_pdf_create($db, $contrat->id, $contrat->modelpdf, $outputlangs);
             */
        } else {
            $mesg = '<div class="error ui-state-error">' . $contrat->error . '</div>';
        }
    }
}

if ($_POST["action"] == 'updateligne' && $user->rights->contrat->creer && !$_POST["cancel"]) {
    $contratline = new ContratLigne($db);
    if ($contratline->fetch($_POST["elrowid"])) {
        $db->begin();

        if ($date_start_real_update == '')
            $date_start_real_update = $contratline->date_ouverture;
        if ($date_end_real_update == '')
            $date_end_real_update = $contratline->date_cloture;

        $contratline->description = $_POST["eldesc"];
        $contratline->price_ht = $_POST["elprice"];
        $contratline->subprice = $_POST["elprice"];
        $contratline->qty = $_POST["elqty"];
        $contratline->remise_percent = $_POST["elremise_percent"];
        $contratline->date_ouverture_prevue = $date_start_update;
        $contratline->date_ouverture = $date_start_real_update;
        $contratline->date_fin_validite = $date_end_update;
        $contratline->date_cloture = $date_end_real_update;
        $contratline->tva_tx = $_POST["eltva_tx"];
        $result = $contratline->update($user);
        if ($result > 0) {
            $db->commit();
            //Modif GLE post 1.0
            //Financement
            $isModuleFinancement = true;
            if ($isModuleFinancement == true && $_REQUEST['financOK'] == "on") {
                $tx = $_REQUEST['financTx'];
                $tx = preg_replace('/,/', ".", $tx);
                $tx = floatval($tx);
                $tx = preg_replace('/,/', ".", $tx);
                if ($tx < 1)
                    $tx *= 100;
                $txAchat = $_REQUEST['financTxAchat'];
                $txAchat = preg_replace('/,/', ".", $txAchat);
                $txAchat = floatval($txAchat);
                $txAchat = preg_replace('/,/', ".", $txAchat);
                if ($txAchat < 1)
                    $txAchat *= 100;
                $dureeFin = intval($_REQUEST['financDuree']);
                //Ajoute dans la table Babel_financement financTxAchat financTx financDuree
                $requete = "";
                $requetePre = "SELECT *
                                 FROM Babel_financement
                                WHERE fk_contratdet = " . $_REQUEST["elrowid"];
                $sqlpre = $db->query($requetePre);
                $requete = "";
                if ($db->num_rows($sqlpre) > 0) {
                    $requete = "UPDATE Babel_financement
                                   SET taux='" . $tx . "',
                                       tauxAchat='" . $txAchat . "',
                                       duree='" . $dureeFin . "'
                                 WHERE fk_contratdet = " . $_REQUEST["elrowid"];
                } else {
                    $requete = "INSERT INTO Babel_financement (taux, tauxAchat, duree, fk_contratdet)
                                     VALUES ('" . $tx . "','" . $txAchat . "','" . $dureeFin . "','" . $_REQUEST["elrowid"] . "')";
                }
                $db->query($requete);
            } elseif ($isModuleFinancement == true) {
                $requete = "DELETE FROM Babel_financement
                                  WHERE fk_contratdet =" . $_REQUEST["elrowid"];
                //print $requete;
                $db->query($requete);
            }
            //fin modif
        } else {
            dol_print_error($db, 'Failed to update contrat_det');
            $db->rollback();
        }
    } else {
        dol_print_error($db);
    }
}

if ($_REQUEST["action"] == 'deleteline' && $user->rights->contrat->creer) {
    $contrat = new Synopsis_Contrat($db);
    $contrat->fetch($_REQUEST["id"]);
//Modif Babel post 1.0
    $requete = "DELETE FROM Babel_financement
                      WHERE fk_contratdet =" . $_REQUEST["lineid"];
    $db->query($requete);
//fin modif

    $result = $contrat->delete_line($_REQUEST["lineid"]);

    if ($result >= 0) {
        Header("Location: card.php?id=" . $contrat->id);
        exit;
    } else {
        $mesg = $contrat->error;
    }
}

if ($_POST["action"] == 'confirm_valid' && $_POST["confirm"] == 'yes' && $user->rights->contrat->creer) {
    $contrat = $contrat = getContratObj($_REQUEST["id"]);
    $contrat->fetch($_REQUEST["id"]);
    $result = $contrat->validate($user, $langs, $conf);
}

if ($_POST["action"] == 'confirm_devalid' && $_POST["confirm"] == 'yes' && $user->rights->contrat->creer) {
    $contrat = $contrat = getContratObj($_REQUEST["id"]);
    $contrat->fetch($_REQUEST["id"]);
    $result = $contrat->devalidate($user, $langs, $conf);
}

if ($_POST["action"] == 'confirm_close' && $_POST["confirm"] == 'yes' && $user->rights->contrat->creer) {
    $contrat = $contrat = getContratObj($_REQUEST["id"]);
    $contrat->fetch($_REQUEST["id"]);
    $result = $contrat->cloture($user, $langs, $conf);
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes') {
    if ($user->rights->contrat->supprimer) {
        $contrat = $contrat = getContratObj($_REQUEST["id"]);
        $contrat->id = $_REQUEST["id"];
        $result = $contrat->delete($user, $langs, $conf);
        if ($result >= 0) {
            Header("Location: index.php");
            return;
        } else {
            $mesg = '<div class="error ui-state-error">' . $contrat->error . '</div>';
        }
    }
}
if ($_REQUEST['action'] == 'setDateAnniv') {
    if ($_REQUEST['selectAnniv'] != -1) {
        $dateAnniv = $_REQUEST['selectAnniv'];
        if ($_REQUEST['selectAnniv'] == 'custom') {
            $dateAnniv = $_REQUEST['dateAnniv'];
        }
        if (preg_match('/^([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})$/', $dateAnniv, $arr)) {
            $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_contrat_GMAO SET dateAnniv='" . $arr[3] . '-' . $arr[2] . '-' . $arr[1] . "' WHERE contrat_refid = " . $_REQUEST['id'];
            $sql = $db->query($requete);
        }
    } else {
        $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_contrat_GMAO SET dateAnniv=NULL WHERE contrat_refid = " . $_REQUEST['id'];
        $sql = $db->query($requete);
    }
}



/*
 * View
 */

$csspath = DOL_URL_ROOT . '/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT . '/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT . '/Synopsis_Common/jquery/ui/';


$header = '<script language="javascript" src="' . DOL_URL_ROOT . '/lib/lib_head.js"></script>' . "\n";

EOF;
if ($_REQUEST["id"] > 0) {
//Type
    $contrat = new Synopsis_Contrat($db);
    $contratTmp = new Synopsis_Contrat($db);
    $type = $contratTmp->getTypeContrat_noLoad($_REQUEST["id"]);

    //if ($contrat->isGA($id))
    switch ($type) {
        case '2': {
                $header .= '<script language="javascript" src="' . DOL_URL_ROOT . '/Babel_GMAO/js/contratTkt-fiche.js"></script>' . "\n";
            }
            break;
        case '3': {
                $header .= '<script language="javascript" src="' . DOL_URL_ROOT . '/Babel_GMAO/js/contratMnt-fiche.js"></script>' . "\n";
            }
            break;
        case '4': {
                $header .= '<script language="javascript" src="' . DOL_URL_ROOT . '/Babel_GMAO/js/contratSAV-fiche.js"></script>' . "\n";
            }
            break;
        case '7': {
                $header .= '<script language="javascript" src="' . DOL_URL_ROOT . '/Synopsis_Contrat/js/contratMixte-fiche.js"></script>' . "\n";
            }
            break;
        case '5': {
                $header .= '<script language="javascript" src="' . DOL_URL_ROOT . '/Babel_GA/js/contratLoc-fiche.js"></script>' . "\n";
            }
            break;
        default: {
                $header .= '<script language="javascript" src="' . DOL_URL_ROOT . '/contrat/js/contrat-fiche.js"></script>' . "\n";
            }
            break;
    }
}
if ($_REQUEST["action"] == 'create') {
    $header .= "<style>";
    $header .= "SELECT { min-width: 300px;  } ";
    $header .= "</style>";
}




$header .= '<script>jQuery(document).ready(function(){ jQuery(".datepicker").datepicker({showTime: false}) });</script>';
$header .= "<style>div.fiche{ position: static; } div#jsContrat{ max-width:1280px;}</style>";

//Max size of screen

launchRunningProcess($db, 'Contrat', $_GET['id']);
llxHeader($header, $langs->trans("ContractCard"), "Contrat", true);

$form = new Form($db);
$html = new Form($db);

$contratlignestatic = new ContratLigne($db);


/* * *******************************************************************
 *
 * Mode creation
 *
 * ******************************************************************* */
if ($_REQUEST["action"] == 'create') {
    if ($conf->global->CONTRAT_ADDON_PDF . "x" == "x") {
        print "Merci de configurer la num&eacute;rotation des contrats";
        exit(0);
    }
    dol_fiche_head($head, $a, $langs->trans("AddContract"));

    if ($mesg)
        print $mesg;

    $new_contrat = new Synopsis_Contrat($db);

    $sql = "SELECT s.nom, s.prefix_comm, s.rowid";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sql.= " WHERE s.rowid = " . $_REQUEST["socid"];

    $resql = $db->query($sql);
    if ($resql) {
        $num = $db->num_rows($resql);
        if ($num) {
            $obj = $db->fetch_object($resql);

            $soc = new Societe($db);
            $soc->fetch($obj->rowid);

            print '<form name="contrat" id="contrat" action="card.php" method="post">';

            print '<input type="hidden" name="action" value="add">';
            print '<input type="hidden" name="socid" value="' . $soc->id . '">' . "\n";
            print '<input type="hidden" name="remise_percent" value="0">';
            if ($_REQUEST['returnPrepacom'] > 0)
                print '<input type="hidden" name="returnPrepacom" value="' . $_REQUEST['comId'] . '">';

            if ($conf->global->MAIN_MODULE_BABELGA) {
                print '<table class="border" width="100%" cellpadding=15>';
            } else {
                print '<table class="border" width="100%" style="border-bottom:0;" cellpadding=15>';
            }

            // Ref
//            print '<tr><td>'.$langs->trans("Ref").'</td>';
//            print '<td><input type="text" maxlength="30" name="ref" size="20"></td></tr>';
            // Customer
            print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Customer") . '</th>
                       <td class="ui-widget-content">' . $soc->getNomUrl(1) . '</td></tr>';

            // Ligne info remises tiers
            print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans('Discount') . '</th>
                       <td class="ui-widget-content">';
            if ($soc->remise_client)
                print $langs->trans("CompanyHasRelativeDiscount", $soc->remise_client);
            else
                print $langs->trans("CompanyHasNoRelativeDiscount");
            $absolute_discount = $soc->getAvailableDiscounts();
            print '. ';
            if ($absolute_discount)
                print $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount), $langs->trans("Currency" . $conf->global->MAIN_MONNAIE));
            else
                print $langs->trans("CompanyHasNoAbsoluteDiscount");
            print '.';
            print '</td></tr>';

//Type de contrat
            //Si GMAO => contrat de maintenance, ticket, SAV etc...
            //Si GA => location financi�re
            //Sinon Location , Service , Libre
            print '<tr><th width="20%"  class="ui-widget-header ui-state-default" nowrap>' . $langs->trans("Type de contrat") . '</th>
                       <td class="ui-widget-content">';
            //$form->select_users('','commercial_suivi_id',1,'');
            print "<SELECT name='typeContrat' id='typeContrat'>";
            print "<OPTGROUP label='Standard'>";
            print "<OPTION value='Libre'>Libre";
            print "<OPTION value='Service'>Service";
            print "</OPTGROUP>";
            if ($conf->global->MAIN_MODULE_BABELGMAO) {
                print "<OPTGROUP label='GMAO'>";
                if ($_REQUEST['returnPrepacom'] > 0) {
                    print "<OPTION SELECTED value='Mixte'>Mixte";
                } else {
                    print "<OPTION value='Mixte'>Mixte";
                }

                print "<OPTION value='Ticket'>Au ticket";
                print "<OPTION value='Maintenance'>Maintenance";
                print "<OPTION value='SAV'>SAV";
                print "</OPTGROUP>";
            }
            if ($conf->global->MAIN_MODULE_BABELGA) {
                print '<OPTGROUP label="Location">';
                print "<OPTION value='Location'>Produit";
                print "<OPTION value='LocationFinanciere'>Gestion d'actif";
                print "</OPTGROUP>";
            }
            print "</SELECT>";
            print '</td></tr>';

            //condition de reglement
            if ($conf->global->MAIN_MODULE_BABELGMAO && $_REQUEST['returnPrepacom'] > 0) {
                //recupe les donn�es de la commande
                $com = new Commande($db);
                $com->fetch($_REQUEST['comId']);
                print '<tr><th width="20%"  class="ui-widget-header ui-state-default" nowrap>' . $langs->trans("Conditions de paiement") . '</th>';
                print '    <td class="ui-widget-content">';
                $form->select_conditions_paiements($com->cond_reglement_id, 'condid', -1, 0, $display = true);
                print '<tr><th width="20%"  class="ui-widget-header ui-state-default" nowrap>' . $langs->trans("Mode de paiement") . '</th>';
                print '    <td class="ui-widget-content">';
                $form->select_types_paiements($com->mode_reglement_id, 'paiementtype', "", 0, 0, 0, $display = true);
            }
            // Commercial suivi
            print '<tr><th width="20%"  class="ui-widget-header ui-state-default" nowrap>' . $langs->trans("TypeContact_contrat_internal_SALESREPFOLL") . '</th>
                       <td class="ui-widget-content">';
            $form->select_users($user->id, 'commercial_suivi_id', 1, '');
            print '</td></tr>';

            // Commercial signature
            print '<tr><th  class="ui-widget-header ui-state-default" width="20%" nowrap>' . $langs->trans("TypeContact_contrat_internal_SALESREPSIGN") . '</th>
                       <td class="ui-widget-content">';
            $form->select_users($conf->global->CONTRAT_DEFAULT_COMMERCIAL_SIGNATAIRE, 'commercial_signature_id', 1, '');
            print '</td></tr>';

            print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Date") . '</th>
                       <td class="ui-widget-content">';
            $form->select_date('', '', '', '', '', "contrat");
            print "</td></tr>";

            if ($conf->projet->enabled) {
                print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Project") . '</th>
                           <td class="ui-widget-content">';
                $proj = new Project($db);
                $form->select_array("projetid", $proj->liste_array($soc->id), 0, 1);
                print "</td></tr>";
            }

            print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("NotePublic") . '</th>
                       <td valign="top" class="ui-widget-content">';
            print '<textarea name="note_public" wrap="soft" cols="70" rows="' . ROWS_3 . '"></textarea></td></tr>';

            if (!$user->societe_id) {
                print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("NotePrivate") . '</th>
                           <td valign="top" class="ui-widget-content">';
                print '<textarea name="note" wrap="soft" cols="70" rows="' . ROWS_3 . '"></textarea></td></tr>';
            }


            //liaison - une facture / commande
            $optgroupName[0] = 'Propositions';
            $optgroupName[1] = 'Commandes';
            $optgroupName[2] = 'Factures';
            $optgroup = array();
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."propal WHERE fk_soc = " . $soc->id;
            if ($resql = $db->query($requete)) {
                while ($res = $db->fetch_object($resql)) {
                    $optgroup[0]["p" . $res->rowid] = $res->ref . " (" . round($res->total_ht, 0) . " &euro;)";
                }
            }

            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."commande WHERE fk_soc = " . $soc->id;
            if ($resql = $db->query($requete)) {
                while ($res = $db->fetch_object($resql)) {
                    $optgroup[1]["c" . $res->rowid] = $res->ref . " (" . round($res->total_ht, 0) . " &euro;)";
                }
            }
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."facture WHERE fk_soc = " . $soc->id;
            if ($resql = $db->query($requete)) {
                while ($res = $db->fetch_object($resql)) {
                    $optgroup[2]["f" . $res->rowid] = $res->facnumber . " (" . round($res->total, 0) . " &euro;)";
                }
            }

            print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("LierPropale") . '</th>
                       <td class="ui-widget-content">';
            print '<SELECT name="Link" id="Link" class="flat">';
            print '<option value="0">Select-></option>';
            foreach ($optgroup as $key => $val) {
                print '<optgroup label="' . $optgroupName[$key] . '" >';
                foreach ($val as $key1 => $val1) {
                    if ($key == 1 && $key1 == "c" . $_REQUEST['comId']) {
                        print '<option SELECTED value="' . $key1 . '">' . $val1 . '</option>';
                    } else {
                        print '<option value="' . $key1 . '">' . $val1 . '</option>';
                    }
                }
            }
            print "</SELECT>";
            print "</td></tr>";
            if ($conf->global->MAIN_MODULE_BABELGA) {
                print "</table>\n";
                print '<table class="border selectMinWidth" width="100%" cellpadding=15 style="border-top:0;">';
//                print '<tr><th class="ui-widget-header ui-state-default" width="20%" nowrap="1" style="border-top: 0; border-bottom:0;">'.$langs->trans("Gestion d'actif").'</th>
//                           <td class="ui-widget-content" style="border-top: 0; border-bottom:0;">';
//                print $form->selectyesno("isGA",0);
//                print '</td></tr>';
                print "</table>";
                print "<div class='onlyInGA' style='display: none;'>";
                print '<table  cellpadding=15  class="border selectMinWidth" style="width: 100%; ">';
                print ' <tr>';
                print "    <th class='ui-widget-header ui-state-default' style='width: 20%;border-bottom: 0; ' nowrap='1'>Cessionnaire</th>";
                print "    <td class='ui-widget-content' style='width: 80%;border-bottom: 0; '>";
                $form->select_societes('', 'cessionnaire', ' s.cessionnaire > 0 ', 1);
                print "    </td>";
                print " </tr>";
                print ' <tr>';
                print "    <th class='ui-widget-header ui-state-default' style='width: 20%;border-bottom: 0; ' nowrap='1'>Type de financement</th>";
                print "    <td class='ui-widget-content' style='width: 80%; border-bottom: 0; '>";
                print "      <SELECT id='TauxType' name='TauxType' class='flat'>";
                print "          <OPTION value='0' SELECTED>Taux fixe</OPTION>";
                print "          <OPTION value='1'>Taux 0</OPTION>";
                print "      </SELECT>";
                print "</td>";
                print "  </tr>";
                print ' <tr>';
                print "    <th class='ui-widget-header ui-state-default' style='width: 20%;border-bottom: 0; ' nowrap='1'>Client signataire du contrat</th>";
                print "    <td class='ui-widget-content' style='width: 80%;border-bottom: 0; '>";
                $form->select_contacts($soc->id, '', 'Client_contactid', 1);
                print "    </td>";
                print "  </tr>";
//                print ' <tr>';
//                print "    <th class='ui-widget-header ui-state-default' style='width: 20%;border-bottom: 0; ' nowrap='1'>Client signataire du contrat</th>";
//                print "    <td class='ui-widget-content' style='width: 80%;border-bottom: 0; '>";
//                               $form->select_contacts($soc->id,'','Client_contactid',1);
//                print "    </td>";
//                print "  </tr>";


                print "</table>";
                print "        <script type='text/javascript'>";
                print <<<EOF
                    jQuery(function(){
                      jQuery.validator.addMethod(
                                    "FRDate",
                                    function(value, element) {
                                        // put your own logic here, this is just a (crappy) example
                                        return value.match(/^\d\d?\/\d\d?\/\d\d\d\d[\W\d\d\:\d\d]?$/);
                                    },
                                    "La date doit &eacute;tre au format dd/mm/yyyy hh:mm"
                                );


                        jQuery('#typeContrat').change(function(){
                            var isGA = jQuery('#typeContrat').find(':selected').val()
                            if (isGA == "LocationFinanciere")
                            {
                                jQuery('.onlyInGA').slideDown('slow');
                            } else {
                                jQuery('.onlyInGA').slideUp('fast');
                            }
                        });
                        //rebind submit
                        jQuery('#createContract').click(function(){
                            if (jQuery('#typeContrat').find(':selected').val() == "yes")
                            {
                                if (jQuery('#contrat').validate({
                                     rules: {
                                        cessionnaire_id: {
                                          required: true,
                                          min: 1
                                        },
                                        Client_contactid: {
                                            required: true,
                                            min: 1
                                        },
                                        re: {
                                            required: true,
                                            FRDate: true
                                        },
                                        commercial_suivi_id: {
                                            required: true,
                                            min: 1
                                        },
                                        commercial_signature_id: {
                                            required: true,
                                            min: 1
                                        }
                                      },
                                      messages: {
                                        cessionnaire_id: {
                                          required: "&nbsp;&nbsp;Merci de S&eacute;lectionner un cessionnaire",
                                          min: "&nbsp;&nbsp;Merci de S&eacute;lectionner un cessionnaire",
                                        },
                                        Client_contactid: {
                                            required: "&nbsp;&nbsp;Merci de s&eacute;l&eacute;tionner un client signataire",
                                            min:  "&nbsp;&nbsp;Merci de s&eacute;l&eacute;tionner un client signataire",
                                        },
                                        re: {
                                            required: "&nbsp;&nbsp;Merci de s&eacute;l&eacute;tionner une date pour ce contrat",
                                            FRDate:  "&nbsp;&nbsp;Le format de la date est invalide",
                                        },
                                        commercial_suivi_id: {
                                            required: "&nbsp;&nbsp;Merci de s&eacute;l&eacute;tionner un commercial signataire",
                                            min:  "&nbsp;&nbsp;Merci de s&eacute;l&eacute;tionner un commercial signataire",
                                        },
                                        commercial_signature_id: {
                                            required: "&nbsp;&nbsp;Merci de s&eacute;l&eacute;tionner un commercial pour le suivi",
                                            min:  "&nbsp;&nbsp;Merci de s&eacute;l&eacute;tionner un commercial pour le suivi",
                                        }
                                      }
                                }).form()){
                                    jQuery('#contrat').submit();
                                } else {
                                    return(false)
                                }
                            } else {
                                if (jQuery('#contrat').validate({
                                     rules: {
                                        re: {
                                            required: true,
                                            FRDate: true
                                        },
                                        commercial_suivi_id: {
                                            required: true,
                                            min: 1
                                        },
                                        commercial_signature_id: {
                                            required: true,
                                            min: 1
                                        }
                                      },
                                      messages: {
                                        re: {
                                            required: "&nbsp;&nbsp;Merci de s&eacute;l&eacute;tionner une date pour ce contrat",
                                            FRDate:  "&nbsp;&nbsp;Le format de la date est invalide",
                                        },
                                        commercial_suivi_id: {
                                            required: "&nbsp;&nbsp;Merci de s&eacute;l&eacute;tionner un commercial signataire",
                                            min:  "&nbsp;&nbsp;Merci de s&eacute;l&eacute;tionner un commercial signataire",
                                        },
                                        commercial_signature_id: {
                                            required: "&nbsp;&nbsp;Merci de s&eacute;l&eacute;tionner un commercial pour le suivi",
                                            min:  "&nbsp;&nbsp;Merci de s&eacute;l&eacute;tionner un commercial pour le suivi",
                                        }
                                      }
                                }).form()){
                                    jQuery('#contrat').submit();
                                } else {
                                    return(false)
                                }
                            }
                            //jQuery('#contrat').validate();
                            return false;
                        })
                    });
EOF;
                print "        </script>";

                print "</div>";
                print '<table cellpadding=15 class="border" width="100%">';
                print '    <tr><th  class="ui-widget-header ui-state-default" colspan="2" align="center">
                                <input type="button" class="butAction" id="createContract" style="padding: 2px 10px 2px 10px" value="' . $langs->trans("Create") . '"></td></tr>';
                print "</table>\n";
            } else {
                print '<tr><th  class="ui-widget-header ui-state-default" colspan="2" align="center"><input type="submit" class="butAction" style="padding: 2px 10px 2px 10px" value="' . $langs->trans("Create") . '"></td></tr>';


                print "</table>\n";
            }


            print "</form>\n";
            print "<br/>";
            print "<br/>";
            if ($propalid) {
                /*
                 * Produits
                 */
                print '<br>';
                load_fiche_titre($langs->trans("Products"));

                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre"><td>' . $langs->trans("Ref") . '</td><td>' . $langs->trans("Product") . '</td>';
                print '<td align="right">' . $langs->trans("Price") . '</td>';
                print '<td align="center">' . $langs->trans("Qty") . '</td>';
                print '<td align="center">' . $langs->trans("ReductionShort") . '</td>';
                print '</tr>';

                $sql = "SELECT pt.rowid, p.label as product, p.ref, pt.price, pt.qty, p.rowid as prodid, pt.remise_percent";
                $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pt, ".MAIN_DB_PREFIX."product as p WHERE pt.fk_product = p.rowid AND pt.fk_propal = $propalid";
                $sql .= " ORDER BY pt.rowid ASC";
                $result = $db->query($sql);
                if ($result) {
                    $num = $db->num_rows($result);
                    $i = 0;
                    $var = true;
                    while ($i < $num) {
                        $objp = $db->fetch_object($result);
                        $var = !$var;
                        print "<tr $bc[$var]><td>[$objp->ref]</td>\n";
                        print '<td>' . $objp->product . '</td>';
                        print "<td align=\"right\">" . price($objp->price) . '</td>';
                        print '<td align="center">' . $objp->qty . '</td>';
                        print '<td align="center">' . $objp->remise_percent . '%</td>';
                        print "</tr>\n";
                        $i++;
                    }
                }
                $sql = "SELECT pt.rowid, pt.description as product, pt.price, pt.qty, pt.remise_percent";
                $sql.= " FROM ".MAIN_DB_PREFIX."propaldet as pt";
                $sql.= " WHERE  pt.fk_propal = $propalid AND pt.fk_product = 0";
                $sql.= " ORDER BY pt.rowid ASC";
                $result = $db->query($sql);
                if ($result) {
                    $num = $db->num_rows($result);
                    $i = 0;
                    while ($i < $num) {
                        $objp = $db->fetch_object($result);
                        $var = !$var;
                        print "<tr $bc[$var]><td>&nbsp;</td>\n";
                        print '<td>' . $objp->product . '</td>';
                        print '<td align="right">' . price($objp->price) . '</td>';
                        print '<td align="center">' . $objp->remise_percent . '%</td>';
                        print '<td align="center">' . $objp->qty . '</td>';
                        print "</tr>\n";
                        $i++;
                    }
                } else {
                    dol_print_error($db);
                }

                print '</table>';
            }
        }
    } else {
        dol_print_error($db);
    }

    print '</div>';
} else
/* * ************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* * ************************************************************************** */ {
    $id = $_REQUEST["id"];
    $isGA = false;
    $isSAV = false;
    $isMaintenance = false;
    $isTicket = false;
    $type = 0;
    if ($id > 0) {
        $contrat = getContratObj($id);
        $result = $contrat->fetch($id);
//        saveHistoUser($contrat->id, "contrat", $contrat->ref);
        if ($result > 0)
            $result = $contrat->fetch_lines();
        if ($result < 0) {
            dol_print_error($db, $contrat->error);
            exit;
        }

        if ($mesg)
            print $mesg;

        $nbofservices = sizeof($contrat->lignes);

        $author = new User($db);
        $author->fetch($contrat->user_author_id);

        $commercial_signature = new User($db);
        $commercial_signature->fetch($contrat->commercial_signature_id);

        $commercial_suivi = new User($db);
        $commercial_suivi->fetch($contrat->commercial_suivi_id);

        $head = contract_prepare_head($contrat);
        $head = $contrat->getExtraHeadTab($head);

        $hselected = 0;

        dol_fiche_head($head, $hselected, $langs->trans("Contract"));


        /*
         * Confirmation de la suppression du contrat
         */
        if ($_REQUEST["action"] == 'delete') {
            $form->form_confirm("card.php?id=$id", $langs->trans("DeleteAContract"), $langs->trans("ConfirmDeleteAContract"), "confirm_delete");
            print '<br>';
        }

        /*
         * Confirmation de la validation
         */
        if ($_REQUEST["action"] == 'valid') {
            //$numfa = contrat_get_num($soc);
            $form->form_confirm("card.php?id=$id", $langs->trans("ValidateAContract"), $langs->trans("ConfirmValidateContract"), "confirm_valid");
            print '<br>';
        }
        if ($_REQUEST["action"] == 'devalid') {
            //$numfa = contrat_get_num($soc);
            $form->form_confirm("card.php?id=$id", $langs->trans("D&eacute;-valider le contrat"), $langs->trans("Voulez vous vraiment d&eacute;-valider le contrat ?"), "confirm_devalid");
            print '<br>';
        }

        /*
         * Confirmation de la fermeture
         */
        if ($_REQUEST["action"] == 'close') {
            $form->form_confirm("card.php?id=$id", $langs->trans("CloseAContract"), $langs->trans("ConfirmCloseContract"), "confirm_close");
            print '<br>';
        }

        /*
         *   Contrat
         */
        if ($contrat->brouillon && $user->rights->contrat->creer) {
            print '<form action="card.php?id=' . $id . '" method="post">';
            print '<input type="hidden" name="action" value="setremise">';
        }

        print '<table cellpadding=15 class="border" width="100%">';

        // Ref du contrat
        print '<tr><th width="25%" class="ui-widget-header ui-state-default">' . $langs->trans("Ref") . '</th>
                   <td colspan="1" class="ui-widget-content">';
        print $contrat->getNomUrl(1);
        print "</td>";

        // Customer
        print '   <th class="ui-widget-header ui-state-default">' . $langs->trans("Customer") . '</th>';
        $societe = new Societe($db);
        $societe->fetch($contrat->socid);
        print '    <td colspan="1" class="ui-widget-content">' . $societe->getNomUrl(1) . '</td></tr>';

        // Statut contrat
        print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Status") . '</th>
                   <td colspan="1" class="ui-widget-content" id="statutPanel">';
        if ($contrat->statut == 0)
            print $contrat->getLibStatut(2);
        else
            print $contrat->getLibStatut(4);
        print "</td>";
        //Type contrat
        print '    <th class="ui-widget-header ui-state-default">' . $langs->trans("Type") . '</th>
                   <td colspan="1" class="ui-widget-content" id="typePanel">';
        $arrTmpType = $contrat->getTypeContrat();
        print $arrTmpType['Nom'];
        print "</td></tr>";

        // Date
        if (($contrat->statut == 0 || ($contrat->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED)) && $user->rights->contrat->creer) {

            print '<tr><th class="ui-widget-header ui-state-default"><a href="card.php?action=changeDateContrat&id=' . $contrat->id . '">' . img_edit($langs->trans("Date"), 1) . '</a> ' . $langs->trans("Date") . '</th>';
            if ($_REQUEST['action'] == 'changeDateContrat') {
                print '    <td colspan="3" class="ui-widget-content"><form method="post" action="card.php?action=doChangeDateContrat&id=' . $contrat->id . '"><input class="datepicker" value="' . dol_print_date($contrat->date_contrat, "day") . '" name="dateContrat"><button class="ui-button">Modifier</button></form></td></tr>';
            } else
                print '    <td colspan="3" class="ui-widget-content">' . dol_print_date($contrat->date_contrat, "day") . "</td></tr>\n";
        } else {
            print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Date") . '</th>';
            print '    <td colspan="3" class="ui-widget-content">' . dol_print_date($contrat->date_contrat, "day") . "</td></tr>\n";
        }

        // Projet
        if ($conf->projet->enabled) {
            $langs->load("projects");
            print '<tr><th class="ui-widget-header ui-state-default">';
            print $langs->trans("Project");
            if ($_REQUEST["action"] != "classer" && $user->rights->projet->creer)
                print '<span style="float:right;"><a href="' . $_SERVER["PHP_SELF"] . '?action=classer&amp;id=' . $id . '">' . img_edit($langs->trans("SetProject")) . '</a></span>';
            print '</th><td colspan="3" class="ui-widget-content">';
            if ($_REQUEST["action"] == "classer") {
                $form->form_project("card.php?id=$id", $contrat->socid, $contrat->fk_projet, "projetid");
            } else {
                $form->form_project("card.php?id=$id", $contrat->socid, $contrat->fk_projet, "none");
            }
            print "</td></tr>";
        }
        //ajoute lien principal
        $contrat->contratCheck_link();

        if ($_REQUEST['action'] == "chSrc" || count($contrat->linkedArray) == 0) {

            $soc = $contrat->societe;
            print '<tr><th class="ui-widget-header ui-state-default">Contrat associ&eacute; &agrave;';
            print "<td colspan=3 class='ui-widget-content'>";
            //liaison - une facture / commande
            $optgroupName[0] = 'Propositions';
            $optgroupName[1] = 'Commandes';
            $optgroupName[2] = 'Factures';
            $optgroup = array();
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."propal WHERE fk_soc = " . $soc->id;
            if ($resql = $db->query($requete)) {
                while ($res = $db->fetch_object($resql)) {
                    $optgroup[0]["p" . $res->rowid] = $res->ref . " (" . round($res->total_ht, 0) . " &euro;)";
                }
            }

            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."commande WHERE fk_soc = " . $soc->id;
            if ($resql = $db->query($requete)) {
                while ($res = $db->fetch_object($resql)) {
                    $optgroup[1]["c" . $res->rowid] = $res->ref . " (" . round($res->total_ht, 0) . " &euro;)";
                }
            }
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."facture WHERE fk_soc = " . $soc->id;
            if ($resql = $db->query($requete)) {
                while ($res = $db->fetch_object($resql)) {
                    $optgroup[2]["f" . $res->rowid] = $res->facnumber . " (" . round($res->total, 0) . " &euro;)";
                }
            }

            print "<FORM action='?action=chgSrvAction&id=" . $_REQUEST['id'] . "' method='POST'>";
            print '<table style="border:0px;" width="100%"><tr><td style="border:0px" width=40%><SELECT style="width:100%;" name="Link">';
            print '<option value="0">Select-></option>';
            foreach ($optgroup as $key => $val) {
                print '<optgroup label="' . $optgroupName[$key] . '">';
                foreach ($val as $key1 => $val1) {
                    print '<option value="' . $key1 . '">' . $val1 . '</option>';
                }
                print "</optgroup>";
            }
            print "</SELECT><td style='border:0px'>";
            print "<input type='submit'></table>";
            print "</FORM>";
        } else {

            if ($contrat->linkedTo) {
                if (preg_match('/^([c|p|f]{1})([0-9]*)/', $contrat->linkedTo, $arr)) {
                    print '<tr><th class="ui-widget-header ui-state-default"><table class="nobordernopadding" style="width:100%;">';
                    print '<tr><th style="border:0" class="ui-widget-header ui-state-default">Contrat associ&eacute; &agrave; ';
                    $val1 = $arr[2];
                    switch ($arr[1]) {
                        case 'c':
                            print 'la commande';
                            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=chSrc&amp;id=' . $id . '">' . img_edit($langs->trans("Change la source")) . '</a>';
                            require_once(DOL_DOCUMENT_ROOT . "/commande/commande.class.php");
                            $comm = new Commande($db);
                            $comm->fetch($val1);
                            if ($conf->global->MAIN_MODULE_SYNOPSISPREPACOMMANDE == 1) {
                                print "</table><td colspan=1 class='ui-widget-content'>" . $comm->getNomUrl(1);
                                print "<th class='ui-widget-header ui-state-default'>Prepa. commande";
                                print "<td colspan=1 class='ui-widget-content'>" . $comm->getNomUrl(1, 5);
                            } else {
                                print "</table><td colspan=3 class='ui-widget-content'>" . $comm->getNomUrl(1);
                            }

                            break;
                        case 'f':
                            print 'la facture<td>';
                            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=chSrc&amp;id=' . $id . '">' . img_edit($langs->trans("Change la source")) . '</a>';
                            require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
                            $fact = new Facture($db);
                            $fact->fetch($val1);
                            print "</table><td colspan=3 class='ui-widget-content'>" . $fact->getNomUrl(1);
                            break;
                        case 'p':
                            print 'la proposition<td>';
                            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=chSrc&amp;id=' . $id . '">' . img_edit($langs->trans("Change la source")) . '</a>';
                            require_once(DOL_DOCUMENT_ROOT . "/propal.class.php");
                            $prop = new Propal($db);
                            $prop->fetch($val1);
                            print "</table><td colspan=3 class='ui-widget-content'>" . $prop->getNomUrl(1);
                            break;
                    }
                }
            }
        }

        //ajoute le lien vers les propal / commande / facture
        foreach ($contrat->linkedArray as $key => $val) {
            if ($key == 'co') {
                foreach ($val as $key1 => $val1) {
                    if ($val1 > 0) {
                        require_once(DOL_DOCUMENT_ROOT . "/commande/commande.class.php");
                        $comm = new Commande($db);
                        $result = $comm->fetch($val1);
                        if ($result > 0) {
                            print '<tr><th class="ui-widget-header ui-state-default">';
                            print 'Commandes associ&eacute;es';
//                            print $comm->getNomUrl(1);

                            if ($conf->global->MAIN_MODULE_SYNOPSISPREPACOMMANDE == 1) {
                                print "<td colspan=1 class='ui-widget-content'>" . $comm->getNomUrl(1);
                                print "<th class='ui-widget-header ui-state-default'>Prepa. commande";
                                print "<td colspan=1 class='ui-widget-content'>" . $comm->getNomUrl(1, 5);
                            } else {
                                print "<td colspan=3 class='ui-widget-content'>" . $comm->getNomUrl(1);
                            }
                        }
                    }
                }
            } else if ($key == 'fa') {
                foreach ($val as $key1 => $val1) {
                    require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
                    $fac = new Facture($db);
                    $result = $fac->fetch($val1);
                    if ($result > 0) {
                        print '<tr><th class="ui-widget-header ui-state-default">';
                        print 'Factures associ&eacute;es<td colspan=3 class="ui-widget-content">';
                        print $fac->getNomUrl(1);
                    }
                }
            } else if ($key == 'pr') {
                foreach ($val as $key1 => $val1) {
                    require_once(DOL_DOCUMENT_ROOT . "/propal.class.php");
                    $prop = new Propal($db);
                    $result = $prop->fetch($val1);
                    if ($result > 0) {
                        print '<tr><th class="ui-widget-header ui-state-default">';
                        print 'Propositions associ&eacute;es<td colspan=3 class="ui-widget-content">';
                        print $prop->getNomUrl(1);
                    }
                }
            }
        }
        print '</tr>';

        print $contrat->displayExtraInfoCartouche();
        print "</table>";

        if ($contrat->brouillon == 1 && $user->rights->contrat->creer) {
            print '</form>';
        }

        print '<br>';



        $servicepos = (isset($_REQUEST["servicepos"]) ? $_REQUEST["servicepos"] : 1);
        $colorb = '333333';



        /*
         * Ajouter une ligne produit/service
         */
        if ($user->rights->contrat->lire &&
                ($contrat->statut == 0 || $contrat->statut == 1 )) {
            $var = false;

            print "<div id='jsContrat'>";

            print $contrat->displayPreLine();
            print "<div style='padding: 5px; padding-bottom: 0;  height: 2em;' class='ui-state-hover ui-widget-header ui-corner-top'>Contenu du contrat</div>";

            $contrat->fetch_lines();
            print $contrat->displayLine();
            if ($user->rights->contrat->creer && $contrat->statut == 0) {
                print $contrat->displayAddLine($mysoc, $objp);
            }

            print '</div>';

            print $contrat->displayExtraStyle();

            $tmp0 = date('Y') - 10;
            $tmp = date('Y') + 15;
            $dateRange = $tmp0 . ":" . $tmp;
            print "<script type='text/javascript'>";
            print '    var userId = ' . $user->id . ';';
            print '    var idContratCurrent = ' . $contrat->id . ';';
            print '    var yearRange = "' . $dateRange . '";';
            print '    jQuery(function() {';
            if (($contrat->statut == 0 || ($contrat->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED)) && $user->rights->contrat->creer) {
                print "      jQuery('#sortable').sortable({ items: 'li:not(li.titre)',
                                        distance: 15,
                                        delay: 200,
                                        placeholder: 'ui-placeHolder',
                                        axis: 'y',
                                        containment: 'body',
                                        opacity: 0.8,
                                        start: function(e,u)
                                        {
                                            //get element height
                                            //console.log(u.helper.css('height'));
                                            var h = u.helper.css('height');
                                            jQuery('.ui-placeHolder').css('height',h);
                                            //var p = u.helper.placeholder;
                                            //p.css('height',h);
                                            //set placeholder size
                                        },
                                        stop: function(event, ui) { reorderLine();},
                                      });";
                print "      jQuery('#sortable').disableSelection();";
            }
            print "    })";
            print "</script>";

            print $contrat->initDialog($mysoc, $objp);
        } else if ($user->rights->contrat->lire) {
            print $contrat->displayPreLine();
            print "<div style='padding: 5px; padding-bottom: 0;  height: 2em;' class='ui-state-hover ui-widget-header ui-corner-top'>Contenu du contrat</div>";

            $contrat->fetch_lines();
            print $contrat->displayLine();


            print $contrat->displayExtraStyle();
        }
        print '</div>'; // Fin du cadre



        print $contrat->displayButton($nbofservices);

        $groups = $user->listGroupIn();
        if ($contrat->statut != 0 && ($user->admin || isset($groups[11]))) {
            print '<div class="tabsAction"><a class="butAction" href="card.php?id=' . $contrat->id . '&amp;action=devalid">' . $langs->trans("D&eacute;-Valider") . '</a></div>';
        }
    }
}
/*
 * Documents generes
 */


$filename = sanitize_string($contrat->ref);
$filedir = $conf->contrat->dir_output . '/' . sanitize_string($contrat->ref);
$urlsource = $_SERVER["PHP_SELF"] . "?id=" . $contrat->id;

$genallowed = ($user->rights->contrat->generate || ($user->rights->GA->contrat->Generate && $contrat->total_ht > 0 && $contrat->cessionnaire_refid > 0 && $contrat->statut > 0) || ($user->rights->GMAO->contrat->generate));
$delallowed = ($user->rights->contrat->supprimer || $user->rights->GA->contrat->Effacer);

$var = true;
require_once(DOL_DOCUMENT_ROOT . "/html.formfile.class.php");

$html = new Form($db);
$formfile = new FormFile($db);
if ($contrat->typeContrat == 6 || $contrat->typeContrat == 5) {
    $filedir = $conf->CONTRATGA->dir_output . '/' . sanitize_string($contrat->ref);
    $somethingshown = $formfile->show_documents('contratGA', $filename, $filedir, $urlsource, $genallowed, $delallowed, $contrat->modelPdf);
} else if ($contrat->typeContrat == 7 || $contrat->typeContrat == 2 || $contrat->typeContrat == 3 || $contrat->typeContrat == 4) {

    $filedir = $conf->synopsiscontrat->dir_output . '/' . sanitize_string($contrat->ref);
    print "<div style='width: 600px'>";
    $somethingshown = $formfile->show_documents('contratGMAO', $filename, $filedir, $urlsource, $genallowed, $delallowed, $contrat->modelPdf);
    print "</div>";
} else {
    $somethingshown = $formfile->show_documents('contrat', $filename, $filedir, $urlsource, $genallowed, $delallowed, $contrat->modelPdf);
}




//Fin modif


$db->close();

llxFooter('$Date: 2008/08/07 20:46:15 $ - $Revision: 1.132 $');
?>
