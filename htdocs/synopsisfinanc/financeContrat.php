<?php
/*
 * * BIMP-ERP by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 19 oct. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : intervByContrat.php
 * BIMP-ERP-1.2
 */
require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php');

$langs->load("contracts");

restrictedArea($user, 'contrat', $contratid, '');

$id = $_REQUEST['id'];

llxHeader($js, 'Prélèvement Automatique');

$contrat = getContratObj($_REQUEST["id"]);
$contrat->fetch($_GET["id"]);
$contrat->info($_GET["id"]);

$head = contract_prepare_head($contrat);
//$head = $contrat->getExtraHeadTab($head);


dol_fiche_head($head, "financ", $langs->trans("Contract"));