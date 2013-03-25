<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Created on : 11 mars 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : contratTkt_fiche_ajax.php
 * GLE-1.2
 */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php');
require_once(DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php');
require_once(DOL_DOCUMENT_ROOT . '/core/lib/price.lib.php');
if ($conf->projet->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
if ($conf->propal->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
if ($conf->contrat->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/Babel_GMAO/contratMixte.class.php");
require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");

$langs->load("contracts");
$langs->load("orders");
$langs->load("companies");
$langs->load("bills");
$langs->load("products");
//var_dump($_REQUEST);
// Security check
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'contrat', $_REQUEST['idContrat'], 'contrat');


$xml = "<ajax-response>";

$action = $_REQUEST['action'];

$date_start_update = '';
$date_end_update = '';
$date_start = '';
$date_start_real_update = '';
$date_end_real_update = '';
if (isset($_REQUEST['dateDeb'])) {
    if (preg_match("/([0-9]*)[\W]([0-9]*)[\W]([0-9]*)/", $_REQUEST['dateDeb'], $arr)) {
        $date_start_update = $arr[3] . "-" . $arr[2] . "-" . $arr[1];
    }
} else {
    $date_start_update = "";
}
if (isset($_REQUEST['dateDebmod'])) {
    if (preg_match("/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})/", $_REQUEST['dateDebmod'], $arr)) {
        $date_start = $arr[3] . "-" . $arr[2] . "-" . $arr[1];
    }
}
if (isset($_REQUEST['dateDebadd'])) {
    if (preg_match("/([0-9]*)[\W]([0-9]*)[\W]([0-9]*)/", $_REQUEST['dateDebadd'], $arr)) {
        $date_start = $arr[3] . "-" . $arr[2] . "-" . $arr[1];
    }
}

if (isset($_REQUEST['dateFin'])) {
    if (preg_match("/([0-9]*)[\W]([0-9]*)[\W]([0-9]*)/", $_REQUEST['dateFin'], $arr)) {
        $date_end_update = $arr[3] . "-" . $arr[2] . "-" . $arr[1];
    } else {
        $date_end_update = "";
    }
} else {
    $date_end_update = "";
}
if (isset($_REQUEST['dateDebConf'])) {
    if (preg_match("/([0-9]*)[\W]([0-9]*)[\W]([0-9]*)/", $_REQUEST['dateDebConf'], $arr)) {
        $date_start_real_update = $arr[3] . "-" . $arr[2] . "-" . $arr[1];
    }
} else {
    $date_start_real_update = "";
}
if (isset($_REQUEST['dateFinConf'])) {
    if (preg_match("/([0-9]*)[\W]([0-9]*)[\W]([0-9]*)/", $_REQUEST['dateFinConf'], $arr)) {
        $date_end_real_update = $arr[3] . "-" . $arr[2] . "-" . $arr[1];
    }
} else {
    $date_end_real_update = "";
}


switch ($action) {

    case 'chgSrvAction':
        $newSrc = $_REQUEST['Link'];
        $contTmp = new Contrat($db);
        $contTmp->fetch($_REQUEST['id']);
        $requete = "UPDATE " . MAIN_DB_PREFIX . "contrat set linkedTo='" . $newSrc . "' WHERE rowid =" . $_REQUEST['id'];
        $resql = $db->query($requete);
        break;

    default:
        if ($_REQUEST["mode"] == 'predefined') {
            $date_start = '';
            $date_end = '';
            if ($_REQUEST["date_startmonth"] && $_REQUEST["date_startday"] && $_REQUEST["date_startyear"]) {
                $date_start = dol_mktime(12, 0, 0, $_REQUEST["date_startmonth"], $_REQUEST["date_startday"], $_REQUEST["date_startyear"]);
            }
            if ($_REQUEST["date_endmonth"] && $_REQUEST["date_endday"] && $_REQUEST["date_endyear"]) {
                $date_end = dol_mktime(12, 0, 0, $_REQUEST["date_endmonth"], $_REQUEST["date_endday"], $_REQUEST["date_endyear"]);
            }
        }

        // Si ajout champ produit libre
        if ($_REQUEST["mode"] == 'libre') {
            $date_start_sl = '';
            $date_end_sl = '';
            if ($_REQUEST["date_start_slmonth"] && $_REQUEST["date_start_slday"] && $_REQUEST["date_start_slyear"]) {
                $date_start_sl = dol_mktime(12, 0, 0, $_REQUEST["date_start_slmonth"], $_REQUEST["date_start_slday"], $_REQUEST["date_start_slyear"]);
            }
            if ($_REQUEST["date_end_slmonth"] && $_REQUEST["date_end_slday"] && $_REQUEST["date_end_slyear"]) {
                $date_end_sl = dol_mktime(12, 0, 0, $_REQUEST["date_end_slmonth"], $_REQUEST["date_end_slday"], $_REQUEST["date_end_slyear"]);
            }
        }

        break;

    case 'addligne':
        if ($user->rights->contrat->creer) {
            //Produit
            $prodid = ($_REQUEST['p_idprod_add'] > 0 ? $_REQUEST['p_idprod_add'] : false);
            //produitContrat
            $contratprodid = ($_REQUEST['p_idContratprod_add'] > 0 ? $_REQUEST['p_idContratprod_add'] : false);
            //Prix
            $pu_ht = ($_REQUEST["addPuHT"] > 0 ? $_REQUEST['addPuHT'] : 0);
            $qte = 1;
            //SLA
            $sla = ($_REQUEST["addSLA"] . "x" == "x" ? "" : $_REQUEST['addSLA']);
            //Reconductionauto
            $recondAuto = ($_REQUEST["addrecondAuto"] == "on" ? 1 : 0);
            //Commande
            $commandeId = ($_REQUEST["addCommande"] > 0 ? $_REQUEST["addCommande"] : false);
            //CommandeDet
            $commandeDetId = ($_REQUEST["addLigneCom"] > 0 ? $_REQUEST["addLigneCom"] : false);
            //Description
            $description = addslashes($_REQUEST["addDesc"]);
            //TVA
            $tva_tx = $_REQUEST["addtauxtva"];
            //Prorata
            $prorata = ($_REQUEST["addprorata"] == "on" || $_REQUEST["addprorata"] == "On" || $_REQUEST["addprorata"] == "ON" ? 1 : 0);
            //serial
            $serial = addslashes($_REQUEST["addserial"]);
            //type contrat
            $isSav = 0;
            $isTkt = 0;
            $isMnt = 0;
            $tickets = false;
            $qte = false;
            $qteTkt = false;
            $telemaintenance = false;
            $hotline = false;
            $durSAV = false; //durValid pour Tkt et Mnt
            $qteTktPerDuree = 1;
            $qteTempsPerDuree = 0;

            $type = ($_REQUEST['typeadd'] . "x" ? $_REQUEST['typeadd'] : false);
            if ($type) {
                switch ($type) {
                    case "MnT": {
                            $isMnt = 1;
                            //maintenance
                            //nb visite
                            $nbVisit = ($_REQUEST['nbVisiteadd'] > 0 ? $_REQUEST['nbVisiteadd'] : 0);
                            //Tickets
                            $tickets = ($_REQUEST['nbTicketMNTadd'] . "x" != "x" ? $_REQUEST['nbTicketMNTadd'] : 0);
                            //télémaintenance
                            $telemaintenance = ($_REQUEST['telemaintenanceadd'] == "on" ? 1 : false);
                            //Hotline
                            $hotline = ($_REQUEST['hotlineadd'] == "on" ? 1 : false);
                            $qte = 1;
                            //durValidite
                            $durSAV = ($_REQUEST['DurValMntadd'] > 0 ? $_REQUEST['DurValMntadd'] : 0);
                            $date_end = "+" . $durSAV;
                            $qteTktPerDuree = $_REQUEST['qteTktPerDureeadd'];
                            $qteTempsPerDuree = (intval($_REQUEST['qteTempsPerDureeHadd']) * 3600 + intval($_REQUEST['qteTempsPerDureeMadd']) * 60);
                        }
                        break;
                    case "TkT": {
                            $isTkt = 1;
                            //ticket
                            //nb
                            $tickets = ($_REQUEST['nbTicketadd'] . "x" != "x" ? $_REQUEST['nbTicketadd'] : 0);
                            $qte = $_REQUEST['addQte'];
                            $durSAV = ($_REQUEST['DurValTktadd'] > 0 ? $_REQUEST['DurValTktadd'] : 0);
                            $date_end = "+" . $durSAV;
                        }
                        break;
                    case "SaV": {
                            //SAV
                            //durSAV
                            $isSav = 1;
                            $durSAV = ($_REQUEST['DurSAVadd'] . "x" != "x" ? $_REQUEST['DurSAVadd'] : 0);
                            $qte = 1;
                            $prodSAV = 0;
                            if ($prodid) {
                                require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
                                $tmpProd = new Product($db);
                                $tmpProd->fetch($prodid);
                                $prodSAV = $tmpProd->durSav;
                            }
                            $date_end = "+" . (intval($durSAV) + intval($prodSAV));
                        }
                        break;
                    default: {
                            $qte = $_REQUEST['addQte'];
                            $date_end = "+" . intval($durSAV);
                        }
                        break;
                }
            }
            //Clause
            $clause = addslashes($_REQUEST['addClause']);
            //Faire requetes
            //1 ajoute dans contratDet
            //2 ajoute dans ".MAIN_DB_PREFIX."cont_co
            //3 ajoute dans lien contratDet -> ligneComDet
            //4 ajoute dans contratProp
            $contrat = new ContratMixte($db);
            $ret = $contrat->fetch($_REQUEST["id"]);
            if ($ret < 0) {
                $xml .= '<KO><![CDATA[' . $db->lastqueryerror() . "\n" . $db->lasterror() . '\n' . $db->lasterrno . '\n' . $db->errno . '\n' . $db->error . ']]></KO>';
            } else {
//                $ret=$contrat->fetch_client();
                $tva_tx = $_REQUEST['tva_tx'];
                //                $date_end = strtotime($date_end_update);
                $price_base_type = 'HT';
                $pu_ttc = 0;
                $basicSAV = 0;

                if ($contratprodid) {
                    $prod = new Product($db, $contratprodid);
                    $prod->fetch($contratprodid);
                    $basicSAV = $prod->durSav;
                }
                $pu_ttc = price2num($pu_ht * (1 + ($tva_tx / 100)), 'MU');

                $totAn1 = 0;
                $totFin = 0;
                if ($prorata == 1) {
                    //Calcul annee 1
                    $prorata_tabprice = calcul_price_total($qte, $pu_ht, 0, $tva_tx, 0, "HT", "");
                    $prorata_total_ht = $prorata_tabprice[0];
                    $prorata_total_tva = $prorata_tabprice[1];
                    $prorata_total_ttc = $prorata_tabprice[2];
                    //combien de jour entre la date de debut et la fin du mois
                    $requete = "SELECT date_contrat FROM " . MAIN_DB_PREFIX . "contrat WHERE rowid = " . $contrat->id;
                    $sqlDate = $db->query($requete);
                    $resDate = $db->fetch_object($sqlDate);
                    $dateAnniv = date('Y-m-d', strtotime($resDate->date_contrat));
                    $prorataArr = prorataTemporis1($date_start, $dateAnniv, $prorata_total_ht, $basicSAV);  // Y-m-a
                    $totAn1 = preg_replace('/,/', '.', $prorataArr["AN1"]);
                    $totFin = preg_replace('/,/', '.', $prorataArr["ANFIN"]);
                    //combien de mois entre la date et la fin de l'annee
                    //regle de 3
                    //Calcul annee n
                    //total - nb annee pleine - debut
                }


                $info_bits = 0;
                // Insert line
                $result = $contrat->addline(
                        htmlentities(utf8_encodeRien($description)), $pu_ht, $qte, $tva_tx, ($prodid ? $prodid : ""), ($_REQUEST["premise"] > 0 ? $_REQUEST["premise"] : 0), $date_start, $date_end, $price_base_type, $pu_ttc, $info_bits, $commandeDetId
                );
                if ($result > 0) {
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO
                                       (tms,
                                        contratdet_refid,
                                        durValid,
                                        clause,
                                        DateDeb,
                                        fk_prod,
                                        reconductionAuto,
                                        isSAV,
                                        SLA,
                                        fk_contrat_prod,
                                        qte,
                                        hotline,
                                        telemaintenance,
                                        maintenance,
                                        qteTempsPerDuree,
                                        qteTktPerDuree,
                                        type,
                                        prorataTemporis,
                                        prixAn1,
                                        prixAnDernier,
                                        nbVisite
                                       )
                                VALUES (now(),
                                        " . $contrat->newContractLigneId . ",
                                        " . ($durSAV ? $durSAV : 'NULL') . ",
                                        " . ($clause . "x" != "x" ? "'" . $clause . "'" : 'NULL') . ",
                                        '" . $date_start . "',
                                        " . ($prodid ? $prodid : "NULL") . ",
                                        " . $recondAuto . ",
                                        " . $isSav . ",
                                        '" . $sla . "',
                                        " . ($contratprodid ? $contratprodid : "NULL") . ",
                                        " . ($isTkt == 1 ? $qte * $tickets : ($isMnt ? $tickets : ($qte ? $qte : 'NULL'))) . ",
                                        " . ($hotline ? $hotline : 'NULL') . ",
                                        " . ($telemaintenance ? $telemaintenance : 'NULL') . ",
                                        " . ($isMnt ? $isMnt : 'NULL') . ",
                                        " . $qteTempsPerDuree . ",
                                        " . $qteTktPerDuree . ",
                                        " . ($isMnt ? 3 : ($isSav ? 4 : ($isTkt ? 2 : 0))) . ",
                                        " . ($prorata == 1 ? 1 : 0) . ",
                                        " . ($prorata == 1 ? $totAn1 : "NULL") . ",
                                        " . ($prorata == 1 ? $totFin : "NULL") . ",
                                        " . ($isMnt ? $nbVisit : "NULL") . "
                                        )";
//print $requete;
                    $result1 = $db->query($requete);
                    $totalSAV = $basicSAV + $durVal;
                    if ($isSav == 1 && $serial . "x" != "x") {
                        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont (element_id, serial_number, date_creation, date_fin_SAV, fk_user_author,element_type)
                                         VALUES (" . $contrat->newContractLigneId . ", '" . $serial . "',now(),date_add('" . $date_start . "', INTERVAL " . $totalSAV . " MONTH)," . $user->id . ",'contratSAV')";
                        $result1 = $db->query($requete);
                    } else if ($isTkt && $serial . "x" != "x") {
                        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont (element_id, serial_number, date_creation, date_fin_SAV, fk_user_author,element_type)
                                         VALUES (" . $contrat->newContractLigneId . ", '" . $serial . "',now(),date_add('" . $date_start . "', INTERVAL " . $totalSAV . " MONTH)," . $user->id . ",'contratTkt')";
                        $result1 = $db->query($requete);
                    } else if ($isMnt && $serial . "x" != "x") {
                        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont (element_id, serial_number, date_creation, date_fin_SAV, fk_user_author,element_type)
                                         VALUES (" . $contrat->newContractLigneId . ", '" . $serial . "',now(),date_add('" . $date_start . "', INTERVAL " . $totalSAV . " MONTH)," . $user->id . ",'contratMnt')";
                        $result1 = $db->query($requete);
                    } else if ($serial . "x" != "x") {
                        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont (element_id, serial_number, date_creation, date_fin_SAV, fk_user_author,element_type)
                                         VALUES (" . $contrat->newContractLigneId . ", '" . $serial . "',now(),date_add('" . $date_start . "', INTERVAL " . $totalSAV . " MONTH)," . $user->id . ",'contrat')";
                        $result1 = $db->query($requete);
                    }
                    $xml .= "<OK>OK</OK>";
                    $xml .= "<OKtext><![CDATA[<div>" . returnNewLine($contrat->newContractLigneId, $_REQUEST['p_idprod']) . "</div>]]></OKtext>";
                } else {
                    //                    $mesg='<div class="error ui-state-error">'.$contrat->error.'</div>';
                    $xml .= '<KO><![CDATA[' . $db->lastqueryerror() . "\n" . $db->lasterror() . '\n' . $db->lasterrno . '\n' . $db->errno . '\n' . $db->error . ']]></KO>';
                }
            }
        }
        break;

    case "renewDialog":
        if ($user->rights->contrat->creer) {
            $date_start = false;
            if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/', $_REQUEST['renewDate'], $arr)) {
                $date_start = $arr[3] . "-" . $arr[2] . '-' . $arr[1];
            }
            $durVal = $_REQUEST['renewDurr'];

            $sql = true;
            foreach ($_REQUEST as $key => $val) {
                if (preg_match("/^renew-/", $key)) {
                    //create renouvellement
                    $requete = "INSERT INTO Babel_contrat_renouvellement (date_renouvellement,statut,contratdet_refid,durRenew)
                                     VALUES ('" . $date_start . "',1," . $val . "," . $durVal . ")";
                    $sql = $db->query($requete);
                    if (!$sql)
                        break;
                    //UPDATE la ligne
                    $requete = "UPDATE " . MAIN_DB_PREFIX . "contratdet SET date_fin_validite=date_add('" . $date_start . "', INTERVAL " . $durVal . " MONTH) WHERE rowid =  " . $val;
                    $sql = $db->query($requete);
                    if (!$sql)
                        break;
                }
            }
            if ($sql) {
                $xml .= "<OK>OK</OK>";
            } else {
                $xml .= '<KO><![CDATA[' . $db->lastqueryerror() . "\n" . $db->lasterror() . '\n' . $db->lasterrno . '\n' . $db->errno . '\n' . $db->error . ']]></KO>';
            }
        }

        break;
    case 'validAvenant': {
            if ($user->rights->contrat->creer) {
                $id = $_REQUEST['id'];
                //create avenant
                $requete = "INSERT INTO Babel_contrat_avenant (date_avenant,statut) VALUES (now(),1)";
                $sql = $db->query($requete);
                $newAvNum = $db->last_insert_id('Babel_contrat_avenant');
                $requete = "UPDATE " . MAIN_DB_PREFIX . "contratdet SET statut = 4, date_valid =now(), avenant=" . $newAvNum . " WHERE statut=0 AND fk_contrat =" . $id;
                $resql = $db->query($requete);
                if ($resql) {
                    $xml .= "<OK>OK</OK>";
                } else {
                    $xml .= '<KO><![CDATA[' . $db->lastqueryerror() . "\n" . $db->lasterror() . '\n' . $db->lasterrno . '\n' . $db->errno . '\n' . $db->error . ']]></KO>';
                }
            }
        }
        break;
    case 'modligne':
        if ($user->rights->contrat->creer) {
            //Produit
            $prodid = ($_REQUEST['p_idprod_mod'] > 0 ? $_REQUEST['p_idprod_mod'] : false);
            //produitContrat
            $contratprodid = ($_REQUEST['p_idContratprod_mod'] > 0 ? $_REQUEST['p_idContratprod_mod'] : false);
            //Prix
            $pu_ht = ($_REQUEST["modPuHT"] > 0 ? $_REQUEST['modPuHT'] : 0);
            //SLA
            $sla = utf8_encodeRien(addslashes(($_REQUEST["modSLA"] . "x" == "x" ? "" : $_REQUEST['modSLA'])));
            //Reconductionauto
            $recondAuto = ($_REQUEST["modrecondAuto"] == "on" ? 1 : 0);
            //Commande
            $commandeId = ($_REQUEST["modCommande"] > 0 ? $_REQUEST["modCommande"] : false);
            //CommandeDet

            $commandeDetId = ($_REQUEST["modLigneCom"] > 0 ? $_REQUEST["modLigneCom"] : false);
            //Description
            $description = $_REQUEST["modDesc"];
            //TVA
            $tva_tx = $_REQUEST["modtauxtva"];
            //Prorata
            $prorata = ($_REQUEST["modprorata"] == "on" ? 1 : 0);
            //serial
            $serial = addslashes($_REQUEST["modserial"]);
            //type contrat
            $isSav = 0;
            $isTkt = 0;
            $isMnt = 0;
            $tickets = false;
            $qteTkt = false;
            $telemaintenance = false;
            $hotline = false;
            $durSAV = 0; //durValid pour Tkt et Mnt
            
//            $date_start = $_REQUEST['dateDebmod'];

            $qteTktPerDuree = 1;
            $qteTempsPerDuree = 0;

            $type = ($_REQUEST['typemod'] . "x" ? $_REQUEST['typemod'] : false);
            $qte = $_REQUEST['modQte'];
//            $date_end = "+" . intval($durSAV);
            if ($type) {
                switch ($type) {
                    case "MnT": {
                            $isMnt = 1;
                            //maintenance
                            //nb visite
                            $nbVisit = ($_REQUEST['nbVisitemod'] . "x" != 'x' ? intval($_REQUEST['nbVisitemod']) : 0);
                            //Tickets
                            $tickets = ($_REQUEST['nbTicketMNTmod'] . "x" != "x" ? $_REQUEST['nbTicketMNTmod'] : 0);
                            //télémaintenance
                            $telemaintenance = ($_REQUEST['telemaintenancemod'] == "on" ? 1 : false);
                            //Hotline
                            $hotline = ($_REQUEST['hotlinemod'] == "on" ? 1 : false);
//                            $qte = 1;
                            //durValidite
                            $durSAV = ($_REQUEST['DurValMntmod'] > 0 ? $_REQUEST['DurValMntmod'] : 0);
                            $qteTktPerDuree = $_REQUEST['qteTktPerDureemod'];
                            $qteTempsPerDuree = (intval($_REQUEST['qteTempsPerDureeHmod']) * 3600 + intval($_REQUEST['qteTempsPerDureeMmod']) * 60);
                        }
                        break;
                    case "TkT": {
                            $isTkt = 1;
                            //ticket
                            //nb
                            $tickets = ($_REQUEST['nbTicketmod'] . "x" != "x" ? $_REQUEST['nbTicketmod'] : 0);
                            $durSAV = ($_REQUEST['DurValTktmod'] > 0 ? $_REQUEST['DurValTktmod'] : 0);
                        }
                        break;
                    case "SaV": {
                            //SAV
                            //durSAV
                            $isSav = 1;
                            $durSAV = ($_REQUEST['DurSAVmod'] . "x" != "x" ? $_REQUEST['DurSAVmod'] : 0);
                            $qte = 1;
                            $prodSAV = 0;
                            if ($prodid) {
                                require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
                                $tmpProd = new Product($db);
                                $tmpProd->fetch($prodid);
                                $prodSAV = $tmpProd->durSav;
                            }
                            $durSAV = intval($durSAV) + intval($prodSAV);
                        }
                        break;
                }
            }
            $date_end = strtotime('+' . $durSAV . ' month', strtotime($date_start));

//var_dump($date_start);
            //Clause
            $clause = utf8_encodeRien(addslashes($_REQUEST['modClause']));
            //Faire requetes
            //1 ajoute dans contratDet
            //2 ajoute dans ".MAIN_DB_PREFIX."cont_co
            //3 ajoute dans lien contratDet -> ligneComDet
            //4 ajoute dans contratProp
            $contrat = new ContratMixte($db);
            $ret = $contrat->fetch($_REQUEST["id"]);
            if ($ret < 0) {
                $xml .= '<KO><![CDATA[' . $db->lastqueryerror() . "\n" . $db->lasterror() . '\n' . $db->lasterrno . '\n' . $db->errno . '\n' . $db->error . ']]></KO>';
            } else {
//                $ret=$contrat->fetch_client();
                //                $date_end = strtotime($date_end_update);
                $price_base_type = 'HT';
                $pu_ttc = 0;
                $basicSAV = 0;

                if ($contratprodid) {
                    $prod = new Product($db, $contratprodid);
                    $prod->fetch($contratprodid);
                    $basicSAV = $prod->durSav;
                }
                $pu_ttc = price2num($pu_ht * (1 + ($tva_tx / 100)), 'MU');

                $totAn1 = 0;
                $totFin = 0;
                if ($prorata == 1) {
                    //Calcul annee 1
                    $prorata_tabprice = calcul_price_total($qte, $pu_ht, 0, $tva_tx, 0, "HT", "");
                    $prorata_total_ht = $prorata_tabprice[0];
                    $prorata_total_tva = $prorata_tabprice[1];
                    $prorata_total_ttc = $prorata_tabprice[2];
                    //combien de jour entre la date de debut et la fin du mois
                    $requete = "SELECT date_contrat FROM " . MAIN_DB_PREFIX . "contrat WHERE rowid = " . $contrat->id;
                    $sqlDate = $db->query($requete);
                    $resDate = $db->fetch_object($sqlDate);
                    $dateAnniv = date('Y-m-d', strtotime($resDate->date_contrat));
                    $prorataArr = prorataTemporis1($date_start, $dateAnniv, $prorata_total_ht, $basicSAV);  // Y-m-a
                    $totAn1 = preg_replace('/,/', '.', $prorataArr["AN1"]);
                    $totFin = preg_replace('/,/', '.', $prorataArr["ANFIN"]);
                    //combien de mois entre la date et la fin de l'annee
                    //regle de 3
                    //Calcul annee n
                    //total - nb annee pleine - debut
                }


                $info_bits = 0;
                // update line
//updateline($rowid, $desc, $pu, $qty, $remise_percent=0,
//         $date_start='', $date_end='', $tvatx,
//         $date_debut_reel='', $date_fin_reel='')
                $result = $contrat->updateline(
                        $_REQUEST['lineid'], utf8_encodeRien($description), $pu_ht, $qte, ($_REQUEST["premise"] > 0 ? $_REQUEST["premise"] : 0), $date_start, $date_end, $tva_tx); /* ,'','',
                  ($prodid>0?$prodid:0),
                  $commandeDetId
                  ); */
                delElementElement("commandedet", "contratdet", NULL, $_REQUEST['lineid']);
                setElementElement("commandedet", "contratdet", $commandeDetId, $_REQUEST['lineid']);

                if ($result > 0) {
                    $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO
                                 SET
                                    durValid = " . ($durSAV ? $durSAV : 'NULL') . ",
                                    clause = " . ($clause . "x" != "x" ? "'" . $clause . "'" : 'NULL') . ",
                                    DateDeb = '" . $date_start . "',
                                    fk_prod = " . ($prodid ? $prodid : "NULL") . ",
                                    reconductionAuto = " . $recondAuto . ",
                                    isSAV = " . $isSav . ",
                                    SLA = '" . $sla . "',
                                    fk_contrat_prod = " . ($contratprodid ? $contratprodid : "NULL") . ",
                                    qte = " . ($isTkt == 1 ? $qte * $tickets : ($isMnt ? $tickets : ($qte ? $qte : 'NULL'))) . ",
                                    nbVisite = " . ($isMnt ? $nbVisit : 'NULL') . ",
                                    hotline = " . ($hotline ? $hotline : 'NULL') . ",
                                    telemaintenance = " . ($telemaintenance ? $telemaintenance : 'NULL') . ",
                                    maintenance = " . ($isMnt ? $isMnt : 'NULL') . ",
                                    qteTempsPerDuree = " . $qteTempsPerDuree . ",
                                    qteTktPerDuree = " . $qteTktPerDuree . ",
                                    prorataTemporis = " . ($prorata == 1 ? 1 : 0) . ",
                                    prixAn1 = " . ($prorata == 1 ? $totAn1 : 'NULL') . ",
                                    prixAnDernier = " . ($prorata == 1 ? $totFin : 'NULL') . ",
                                    type = " . ($isMnt ? 3 : ($isSav ? 4 : ($isTkt ? 2 : 0))) . "
                               WHERE contratdet_refid=" . $_REQUEST['lineid'];
//print $requete;
                    $req1 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO WHERE contratdet_refid = " . $_REQUEST['lineid'];
                    $sql1 = $db->query($req1);
                    if (!$db->num_rows($sql1) > 0) {
                        $requeteIns = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO (contratdet_refid) VALUES (" . $_REQUEST['lineid'] . ")";
                        $db->query($requeteIns);
                    }
                    $result1 = $db->query($requete);
                    $totalSAV = $basicSAV + $durVal;
                    if ($serial . "x" != "x") {
                        $requete = "SELECT *
                                      FROM " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont
                                     WHERE element_id = " . $_REQUEST['lineid'] . "
                                       AND element_type LIKE 'contrat%'";
                        $sql = $db->query($requete);
                        if ($db->num_rows($sql) > 0) {
                            $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont
                                           SET serial_number = '" . $serial . "',
                                               date_fin_SAV = date_add('" . $date_start . "', INTERVAL " . $totalSAV . " MONTH),
                                               fk_user_author = " . $user->id . "
                                         WHERE element_id = " . $_REQUEST['lineid'] . "
                                           AND element_type = 'contratSAV'";
                            $result1 = $db->query($requete);
                        } else {
                            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont (element_id, serial_number, date_creation, date_fin_SAV, fk_user_author,element_type)
                                             VALUES (" . $_REQUEST['lineid'] . ", '" . $serial . "',now(),date_add('" . $date_start . "', INTERVAL " . $totalSAV . " MONTH)," . $user->id . ",'contratSAV')";
                            $result1 = $db->query($requete);
                        }
                    } else if ($isTkt && $serial . "x" != "x") {
                        $requete = "SELECT *
                                      FROM " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont
                                     WHERE element_id = " . $_REQUEST['lineid'] . "
                                       AND element_type LIKE 'contrat%'";
                        $sql = $db->query($requete);
                        if ($db->num_rows($sql) > 0) {
                            $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont
                                           SET serial_number = '" . $serial . "',
                                               date_fin_SAV = date_add('" . $date_start . "', INTERVAL " . $totalSAV . " MONTH),
                                               fk_user_author = " . $user->id . "
                                         WHERE element_id = " . $_REQUEST['lineid'] . "
                                           AND element_type LIKE 'contrat%'";
                            $result1 = $db->query($requete);
                        } else {
                            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont (element_id, serial_number, date_creation, date_fin_SAV, fk_user_author,element_type)
                                             VALUES (" . $_REQUEST['lineid'] . ", '" . $serial . "',now(),date_add('" . $date_start . "', INTERVAL " . $totalSAV . " MONTH)," . $user->id . ",'contratTkt')";
                            $result1 = $db->query($requete);
//print $requete."\n";
                        }
                    } else if ($isMnt && $serial . "x" != "x") {
                        $requete = "SELECT COUNT(*) as cnt
                                      FROM " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont
                                     WHERE element_id = " . $_REQUEST['lineid'] . "
                                       AND element_type LIKE 'contrat%'";
                        $sql = $db->query($requete);
                        if ($db->num_rows($sql) > 0) {
                            $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont
                                           SET serial_number = '" . $serial . "',
                                               date_fin_SAV = date_add('" . $date_start . "', INTERVAL " . $totalSAV . " MONTH),
                                               fk_user_author = " . $user->id . "
                                         WHERE element_id = " . $_REQUEST['lineid'] . "
                                           AND element_type = 'contratMnt'";
                            $result1 = $db->query($requete);
                        } else {
                            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont (element_id, serial_number, date_creation, date_fin_SAV, fk_user_author,element_type)
                                             VALUES (" . $_REQUEST['lineid'] . ", '" . $serial . "',now(),date_add('" . $date_start . "', INTERVAL " . $totalSAV . " MONTH)," . $user->id . ",'contratMnt')";
                            $result1 = $db->query($requete);
                        }
                    } else if ($serial . "x" != "x") {
                        $requete = "SELECT *
                                      FROM " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont
                                     WHERE element_id = " . $_REQUEST['lineid'] . "
                                       AND element_type LIKE 'contrat%'";
                        $sql = $db->query($requete);
                        if ($db->num_rows($sql) > 0) {
                            $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont
                                           SET serial_number = '" . $serial . "',
                                               date_fin_SAV = date_add('" . $date_start . "', INTERVAL " . $totalSAV . " MONTH),
                                               fk_user_author = " . $user->id . "
                                         WHERE element_id = " . $_REQUEST['lineid'] . "
                                           AND element_type = 'contrat'";
                            $result1 = $db->query($requete);
                        } else {
                            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont (element_id, serial_number, date_creation, date_fin_SAV, fk_user_author,element_type)
                                             VALUES (" . $_REQUEST['lineid'] . ", '" . $serial . "',now(),date_add('" . $date_start . "', INTERVAL " . $totalSAV . " MONTH)," . $user->id . ",'contrat')";
                            $result1 = $db->query($requete);
                        }
                    }
                    //TODO effacement du Serial
                    if ('x' . $serial == "x") {
                        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont
                                          WHERE element_id = " . $_REQUEST['lineid'] . "
                                            AND element_type LIKE 'contrat%'";
                        $result1 = $db->query($requete);
                    }

                    $xml .= "<OK>OK</OK>";
                    $xml .= "<OKtext><![CDATA[<div>" . returnNewLine($_REQUEST['lineid'], $_REQUEST['p_idprod']) . "</div>]]></OKtext>";
                } else {
//                    $mesg='<div class="error ui-state-error">'.$contrat->error.'</div>';
                    $xml .= '<KO><![CDATA[' . $db->lastqueryerror() . "\n" . $db->lasterror() . '\n' . $db->lasterrno . '\n' . $db->errno . '\n' . $db->error . ']]></KO>';
                }
            }
        }
        break;
    case 'getStatut': {

            $contrat = new Contrat($db);
            $contrat->id = $_REQUEST["id"];
            if ($contrat->fetch($_REQUEST["id"])) {
                $contrat->fetch_lines();

                $xml.="<srvPanel>";
                $xml .= "<![CDATA[<div>";
                if ($contrat->statut == 0)
                    $xml .= $contrat->getLibStatut(2);
                else
                    $xml .= $contrat->getLibStatut(4);
                $xml .= "</div>]]>";
                $xml.="</srvPanel>";
            } else {
                $xml.="<srvPanel>";
                $xml .= "<![CDATA[";
                $xml .= "Erreur de chargement du contrat";
                $xml .= "]]>";
                $xml.="</srvPanel>";
            }
        }
        break;
    case 'closeLine': {
            $ligne = $_REQUEST["lineid"];
            $contrat = new Contrat($db);
            if ($contrat->fetch($_REQUEST["id"])) {
                $result = $contrat->close_line($user, $_REQUEST["lineid"], time());
                if ($result > 0) {
                    $xml .= "<OK>OK</OK>";
//                $xml .= "<OKtext><![CDATA[".returnNewLine($_REQUEST["lineid"])."]]></OKtext>";
                } else {
                    $xml .= '<KO>La ligne à mettre à jour n\'existe pas</KO>';
                }
            } else {
                $xml .= '<KO>Erreur lors de l\'activation</KO>';
            }
        }
        break;
    case 'unactivateLine': {
            $ligne = $_REQUEST["lineid"];
            $contrat = new Contrat($db);
            if ($contrat->fetch($_REQUEST["id"])) {
                $result = $contrat->cancel_line($user, $_REQUEST["lineid"]);
                if ($result > 0) {
                    $xml .= "<OK>OK</OK>";
                    $xml .= "<OKtext><![CDATA[" . returnNewLine($_REQUEST["lineid"]) . "]]></OKtext>";
                } else {
                    $xml .= '<KO>La ligne à mettre à jour n\'existe pas</KO>';
                }
            } else {
                $xml .= '<KO>Erreur lors de l\'activation</KO>';
            }
        }
        break;

    case 'activateLine': {
            $ligne = $_REQUEST["lineid"];
            $contrat = new Contrat($db);
            if ($contrat->fetch($_REQUEST["id"])) {
                $result = $contrat->active_line($user, $ligne, strtotime($date_start_real_update), strtotime($date_end_real_update));
                if ($result > 0) {
                    $xml .= "<OK>OK</OK>";
                    $xml .= "<OKtext><![CDATA[" . returnNewLine($_REQUEST["lineid"]) . "]]></OKtext>";
                } else {
                    $xml .= '<KO>La ligne à mettre à jour n\'existe pas</KO>';
                }
            } else {
                $xml .= '<KO>Erreur lors de l\'activation</KO>';
            }
        }
        break;
    case 'deleteline':
        if ($user->rights->contrat->creer) {
            $contrat = new Contrat($db);
            $contrat->fetch($_REQUEST["id"]);
            //Modif Babel post 1.0
            $requete = "DELETE FROM Babel_financement
                              WHERE fk_contratdet =" . $_REQUEST["lineid"];
            $db->query($requete);
            //fin modif

            $result = $contrat->delete_line($_REQUEST["lineid"]);

            if ($result >= 0) {
                $xml .= "<OK>OK</OK>";
                $xml .= "<OKtext><![CDATA[]]></OKtext>";
            } else {
//                $mesg=$contrat->error;
                $xml .= '<KO><![CDATA[' . $db->lastqueryerror() . "\n" . $db->lasterror() . '\n' . $db->lasterrno . '\n' . $db->errno . '\n' . $db->error . ']]></KO>';
            }
        }

        break;

    //New
    case 'getLineDet': {
            $idContrat = $_REQUEST['idContrat'];
            $idLigne = $_REQUEST['idLigneContrat'];
            $requete = "SELECT DISTINCT c.statut,
                           c.label,
                           c.description,
                           c.date_commande,
                           date_format(c.date_ouverture_prevue,'%d/%m/%Y') as date_ouverture_prevue,
                           date_format(c.date_ouverture,'%d/%m/%Y') as date_ouverture,
                           date_format(c.date_fin_validite,'%d/%m/%Y') as date_fin_validite,
                           date_format(c.date_cloture,'%d/%m/%Y') as date_cloture,
                           c.tva_tx,
                           c.qty,
                           c.fk_product,
                           c.remise_percent,
                           c.subprice,
                           c.subprice,
                           c.total_ht,
                           ee.fk_source fk_commande_ligne,
                           c.total_tva,
                           g.type,
                           unix_timestamp(date_add(date_add(g.DateDeb, INTERVAL g.durValid month), INTERVAL ifnull(pe.2dureeSav,0) MONTH)) as GMAO_dfinprev,
                           unix_timestamp(date_add(date_add(g.DateDeb, INTERVAL g.durValid month), INTERVAL ifnull(pe.2dureeSav,0) MONTH)) as GMAO_dfin,
                           unix_timestamp(g.DateDeb) as GMAO_ddeb,
                           unix_timestamp(g.DateDeb) as GMAO_ddebprev,
                           g.durValid as GMAO_durVal,
                           g.hotline as GMAO_hotline,
                           g.telemaintenance as GMAO_telemaintenance,
                           g.maintenance as GMAO_maintenance,
                           g.SLA as GMAO_sla,
                           g.clause as GMAO_clause,
                           g.qte as GMAO_qte,
                           g.nbVisite as GMAO_nbVisite,
                           g.isSAV as GMAO_isSAV,
                           g.fk_prod as GMAO_fk_prod,
                           g.reconductionAuto as GMAO_reconductionAuto,
                           g.maintenance as GMAO_maintenance,
                           g.fk_contrat_prod as GMAO_fk_contrat_prod,
                           g.qteTempsPerDuree as GMAO_qteTempsPerDuree,
                           g.qteTktPerDuree as GMAO_qteTktPerDuree,
                           sc.serial_number as GMAO_serial_number,
                           c.total_ttc,
                           c.fk_user_author,
                           c.fk_user_ouverture,
                           c.fk_user_cloture,
                           c.commentaire,
                           o.fk_commande,
                           p.ref as prodLabel,
                           p2.ref as prodLabel2
                      FROM " . MAIN_DB_PREFIX . "contratdet as c
                 LEFT JOIN " . MAIN_DB_PREFIX . "element_element as ee ON ee.sourcetype = 'commandedet' AND ee.targettype = 'contratdet' AND ee.fk_target = c.rowid
                 LEFT JOIN " . MAIN_DB_PREFIX . "commandedet as o ON ee.fk_source = o.rowid
                 LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO as g ON g.contratdet_refid = c.rowid
                 LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON  c.fk_product = p.rowid
                 LEFT JOIN " . MAIN_DB_PREFIX . "product as p2 ON  g.fk_prod = p2.rowid
                 LEFT JOIN " . MAIN_DB_PREFIX . "product_extrafields as pe ON pe.fk_object = p.rowid
                 LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont as sc ON sc.element_id = c.rowid AND sc.element_type LIKE 'contrat%'
                     WHERE c.rowid = " . $idLigne . " LIMIT 1 ";
            $sql = $db->query($requete);
//die($requete);
            while ($res = $db->fetch_object($sql)) {
                //  Description
                $xml .= "<description><![CDATA[" . utf8_encodeRien($res->description) . "]]></description>";
                //  date debut
                $xml .= "<dateDeb><![CDATA[" . date('d/m/Y', $res->GMAO_ddeb) . "]]></dateDeb>";
                //  SLA
                $xml .= "<SLA><![CDATA[" . utf8_encodeRien($res->GMAO_sla) . "]]></SLA>";
                //  reconduction auto
                $xml .= "<recondAuto><![CDATA[" . $res->GMAO_reconductionAuto . "]]></recondAuto>";
                //  commande / commandedet
                $xml .= "<commandeDet><![CDATA[" . $res->fk_commande_ligne . "]]></commandeDet>";
                $xml .= "<commande><![CDATA[" . $res->fk_commande . "]]></commande>";

                //  Produit contrat
                $xml .= "<fk_contrat_prod><![CDATA[" . $res->fk_product . "]]></fk_contrat_prod>";
                $xml .= "<product><![CDATA[" . $res->prodLabel . "]]></product>";

                if ($res->fk_product > 0) {
                    $tmpProd = new Product($db);
                    $tmpProd->fetch($res->fk_product);
                    $tmpProd->fetch_optionals($tmpProd->id);
                    $xml .= "<contratClause><![CDATA[" . utf8_encodeRien($tmpProd->array_options["options_2clause"]) . "]]></contratClause>";
                } else {
                    $xml .= "<contratClause><![CDATA[]]></contratClause>";
                }


                //  prix HT
                $xml .= "<totalht><![CDATA[" . round($res->subprice * 100) / 100 . "]]></totalht>";
                //  TVA
                $xml .= "<tva_tx><![CDATA[" . $res->tva_tx . "]]></tva_tx>";

                //  Produit
                if ($res->GMAO_fk_prod > 0) {
                    $tmpProd = new Product($db);
                    $tmpProd->fetch($res->GMAO_fk_prod);
                    $tmpProd->fetch_optionals($tmpProd->id);
                    $xml .= "<productClause><![CDATA[" . utf8_encodeRien($tmpProd->array_options["options_2clause"]) . "]]></productClause>";
                } else {
                    $xml .= "<productClause><![CDATA[]]></productClause>";
                }
                $xml .= "<fk_product><![CDATA[" . $res->GMAO_fk_prod . "]]></fk_product>";
                $xml .= "<product2><![CDATA[" . $res->prodLabel2 . "]]></product2>";

                $xml .= "<qte><![CDATA[" . $res->qty . "]]></qte>";
                //  num de série
                $xml .= "<serial_number><![CDATA[" . $res->GMAO_serial_number . "]]></serial_number>";


                //  Maintenance
                if ($res->GMAO_maintenance == 1 || $res->GMAO_telemaintenance == 1 || $res->GMAO_hotline == 1) {
                    //  Nb visite an
                    //  télémaintenance
                    //  hotline
                    $xml .= "<isMnt>1</isMnt>";
                    $xml .= "<nbVisiteAn><![CDATA[" . $res->GMAO_nbVisite . "]]></nbVisiteAn>";
                    $xml .= "<telemaintenance><![CDATA[" . $res->GMAO_telemaintenance . "]]></telemaintenance>";
                    $xml .= "<hotline><![CDATA[" . $res->GMAO_hotline . "]]></hotline>";
                    $xml .= "<qteMNT><![CDATA[" . $res->GMAO_qte . "]]></qteMNT>";
                    $xml .= "<qteTktPerDuree><![CDATA[" . $res->GMAO_qteTktPerDuree . "]]></qteTktPerDuree>";
                    $xml .= "<qteTempsPerDuree><![CDATA[" . $res->GMAO_qteTempsPerDuree . "]]></qteTempsPerDuree>";
                    $arrDur = convDur($res->GMAO_qteTempsPerDuree);
                    $xml .= "<qteTempsPerDureeH><![CDATA[" . $arrDur['hours']['abs'] . "]]></qteTempsPerDureeH>";
                    $xml .= "<qteTempsPerDureeM><![CDATA[" . $arrDur['minutes']['rel'] . "]]></qteTempsPerDureeM>";
                } else {
                    $xml .= "<isMnt>0</isMnt>";
                }
                //  Ticket
                if ($res->qty != 0 && $res->GMAO_maintenance == 0 && $res->GMAO_isSAV == 0) {
                    $xml .= "<isTkt>1</isTkt>";
                    $xml .= "<qteTkt><![CDATA[" . $res->GMAO_qte . "]]></qteTkt>";
                } else {
                    $xml .= "<isTkt>0</isTkt>";
                }

                //  Nb Ticket
                //  SAV
                $xml .= "<isSAV><![CDATA[" . $res->GMAO_isSAV . "]]></isSAV>";
                //  Durée extension
                $xml .= "<durSAV><![CDATA[" . $res->GMAO_durVal . "]]></durSAV>";
                $xml .= "<durValid><![CDATA[" . $res->GMAO_durVal . "]]></durValid>";

                //  clause
                $xml .= "<clause><![CDATA[" . utf8_encodeRien($res->GMAO_clause) . "]]></clause>";
            }
        }
        break;
    case 'sortLine': {
            $dataRaw = $_REQUEST['data'];
            $data = explode(',', $dataRaw);
            $return = true;
            foreach ($data as $key => $val) {

                $newOrder = $key + 1;
                $requete = ' UPDATE ' . MAIN_DB_PREFIX . 'contratdet  SET line_order = ' . $newOrder . '  WHERE rowid = ' . $val;
                $sql = $db->query($requete);
                if (!$sql) {
                    $return = false;
                    break;
                }
            }
            if ($return) {
                $xml .= "<OK>OK</OK>";
            } else {
                $xml .= "<KO><![CDATA[" . $db->lastqueryerror . "\n " . $db->lasterror . "]]></KO>";
            }
        }
        break;
}



if (stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml")) {
    header("Content-type: application/xhtml+xml;charset=utf-8");
} else {
    header("Content-type: text/xml;charset=utf-8");
} $et = ">";
echo "<?xml version='1.0' encoding='utf-8'?$et\n";
echo $xml;
echo "</ajax-response>";

function returnNewLine($plineId, $isProd = false) {
    global $db, $conf, $langs, $user;
    $contratline = new ContratLigne($db);
    $contrat = new Synopsis_Contrat($db);
    $contrat->fetch($_REQUEST['id']);
    $contratline->fetch($plineId);
    //$contratline->fk_product=$_REQUEST['p_idprod'];
    $contrat = new contratMixte($db);
    $contrat->id = $contratline->fk_contrat;
    $contrat->fetch($contratline->fk_contrat);
    $contratline->fk_product = $_REQUEST['p_idprod'];
    //var_dump($contratline);
//    $txt = $contrat->display1line($contrat, $contratline);
    $txt = utf8_encodeRien($txt);

    return ($txt);
}

function prorataTemporis1($dateDeb, $dateAnniv, $total, $duree) {
//rt degressif au prorata du nombre de mois. Son point de dï¿½art est le 1er jour du mois d'acquisition et non date de mise en service

    $prorata = 0;
    $ecartfinmoiscourant = 0;
    $ecartmoisexercice = 0;

    sscanf($dateDeb, "%4s-%2s-%2s", $date_Y, $date_m, $date_d); // un traitement sur la date mysql pour recuperer l'annee
    sscanf($dateAnniv, "%4s-%2s-%2s", $date_Y2, $date_m2, $date_d2);
    $date_Y2 = date("Y");
    $tab = array();
    if ($total > 0 && $duree > 0 && $dateDeb != "0000-00-00") {
        ## calcul du prorata temporis en jour ##
        // calcul ecart entre jour  date mise en service et fin du mois courant
        $ecartfinmoiscourant = (30 - $date_d);
        $ecartmoisexercice = $date_d2 - $date_d;
        // calcul ecart etre mois mise en service et debut annee suivante
        $ecartmois = (($date_m2 - $date_m) * 30);
        $prorata = $ecartfinmoiscourant + $ecartmois - $ecartmoisexercice;
        // calcul de le prix / jour
        $journalite = $total / (360 * $duree / 12);
        $totAn1 = $prorata * $journalite;
        $totAn = $total / ($duree / 12);
        $tmpTot = $total - $totAn1;
        while ($tmpTot > 0) {
            $tmpTot -= $totAn;
        }
        $totAnFin = $total - $totAn1 - ($total / ($duree - 24));
        $tab["AN1"] = $totAn1;
        $tab['ANFIN'] = $tmpTot + $totAn;
        $tab['AN'] = $total / ($duree / 12);


//         $mrt=$total;
//         // si prorata temporis la dernie annnuite cours sur la dure n+1
//         $duree=$duree+12;
//         for($i=1;$i<=$duree;$i++) {
//             $tab['mois'][$i]=$date_m+$i-1;
//             $tab['mensualite'][$i]=$mensualite;
//             $tab['vcnetdeb'][$i]=$mrt; // Valeur annee pleine
//             // calcul de la premiere mensualité + annuite prorata temporis
//             $tab['mensualite'][1]=$mensualite*($prorata/360);
//             $tab['vcnetfin'][1]=abs($total - $tab['annuite'][1]);
//             $mrt=$tab['vcnetfin'][$i];
//         } // end for
//
//         // calcul de la derniï¿½e annuitï¿½si prorata temporis
//         if ($prorata>0){
//             $tab['mensualite'][$duree]=$tab['vcnetdeb'][$duree];
//             $tab['vcnetfin'][$duree]=$tab['vcnetfin'][$duree-1]- $tab['mensualite'][$duree];
//         }
    }
    return($tab);
}

?>