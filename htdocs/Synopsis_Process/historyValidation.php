<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 25 fevr. 2011
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : historyValidation.php
 * GLE-1.2
 */
require_once('pre.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
$js = "";
$filterMyValid = false;
$filterMyPossible = false;
$filterProcess = false;
if ($_REQUEST['filterMyValid'] . "x" != "x")
    $filterMyValid = true;
if ($_REQUEST['filterMyPossible'] . "x" != "x")
    $filterMyPossible = true;
if ($_REQUEST['filterProcess'] > 0)
    $filterProcess = $_REQUEST['filterProcess'];

$limit = 20;
$page = 0;

if ($_REQUEST['page'] > 0)
    $page = $_REQUEST['page'];
if ($_REQUEST['limit'] > 0)
    $limit = $_REQUEST['limit'];


if ($filterMyValid) {
    llxHeader($js, "Mes validations");
    print "<br/>";
    print "<div class='titre'>Mes validations</div>";
    print "<br/>";
    print "<br/>";
    displayNav($page, $limit, "filterMyValid");
} else if ($filterMyPossible) {
    llxHeader($js, "Suivi des validations");
    print "<br/>";
    print "<div class='titre'>Suivi des validations</div>";
    print "<br/>";
    print "<br/>";
    displayNav($page, $limit, "filterMyPossible");
} else if ($filterProcess) {
    llxHeader($js, "Validation du process");
    print "<br/>";
    print "<div class='titre'>Validation du process";
} else {
    llxHeader($js, "Historique de validation");
    print "<br/>";
    print "<div class='titre'>Historique des validations</div>";
    print "<br/>";
    print "<br/>";
    displayNav($page, $limit, false);
}


$requete = "SELECT DISTINCT process_refid,
                       element_refid,
                       processdet_refid
                  FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet_validation";
if ($filterMyValid)
    $requete.="  WHERE user_refid = " . $user->id;
if ($filterProcess)
    $requete.="  WHERE processdet_refid = " . $filterProcess;
$requete .= " ORDER BY dateValid DESC ";
$requete .= " LIMIT " . $page*$limit . "," . $limit;
$sql = $db->query($requete);
if (!$filterProcess) {
    print "<table width=100% cellpadding=15>";
    print "<tr><th width=200 class='ui-widget-header ui-state-default'>Ref<th class='ui-widget-header ui-state-default' width=200>Statut<th width=150 class='ui-widget-header ui-state-default'>&Eacute;l&eacute;ments<th class='ui-widget-header ui-state-default'>Historique";
}
$process = new process($db);
$processDet = new processDet($db);
$tmpUser = new User($db);
while ($res = $db->fetch_object($sql)) {
    $process->fetch($res->process_refid, false);
    $processDet->fetch($res->processdet_refid);
    if ($filterProcess) {
        print "&nbsp;" . $processDet->getNomUrl(1);
        print "</div><br/><br/>";
        print "<table width=100% cellpadding=15>";
        print "<tr><th width=200 class='ui-widget-header ui-state-default'>Ref<th class='ui-widget-header ui-state-default' width=200>Statut<th width=150 class='ui-widget-header ui-state-default'>&Eacute;l&eacute;ments<th class='ui-widget-header ui-state-default'>Historique";
    }

    if (!$process->canValidate() && $filterMyPossible) {
        continue;
    }
    $tmp = $process->typeElement->type;
    require_once(DOL_DOCUMENT_ROOT . $process->typeElement->classFile);
    $obj = new $tmp($db);
    $obj->fetch($processDet->element_refid);

    print "<tr><td class='ui-widget-content'>" . $processDet->getNomUrl(1) . "<td class='ui-widget-content'>";
    print $processDet->getLibStatut(4);
    print "<td class='ui-widget-content'>";

    print $obj->getNomUrl(1);
    print "<td class='ui-widget-content' style='padding:0px;'>";
    $requete1 = "SELECT DISTINCT validation_number
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet_validation
                     WHERE process_refid = " . $res->process_refid . "
                       AND element_refid = " . $res->element_refid . "
                       AND processdet_refid = " . $res->processdet_refid . "
                  ORDER BY validation_number";
    $sql1 = $db->query($requete1);
    print "<table width=100%>";
    while ($res1 = $db->fetch_object($sql1)) {
        print "<tr><td class='ui-widget-content' width=110 style='padding-left: 10px;'>Validation #" . $res1->validation_number;
        $requete2 = " SELECT v.validation_type_refid, v.valeur, v.note, v.user_refid, d.label
                            FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet_validation as v
                      RIGHT JOIN " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def as d ON v.validation_type_refid = d.id AND v.process_refid = " . $res->process_refid . " AND v.element_refid = " . $res->element_refid . " AND v.processdet_refid = " . $res->processdet_refid . " AND v.validation_number = " . $res1->validation_number . "
                           WHERE d.isValidationRight = 1
                             AND d.isValidationForAll <> 1
                             AND d.active = 1";
        $sql2 = $db->query($requete2);
//            print $requete2;
        if ($db->num_rows($sql2) > 0) {
            print "    <td class='ui-widget-content'><table width=100% cellpadding=10>";
            while ($res2 = $db->fetch_object($sql2)) {
                if ($res2->valeur . "x" == "x") {
                    print "        <tr><td nowrap>" . $res2->label . "<td nowrap colspan=3><table><tr><td><span class='ui-icon ui-icon-help'></span><td>&nbsp;&nbsp;En attente de validation</table>";
                } else {
                    $tmpUser->fetch($res2->user_refid);
                    print "        <tr><td nowrap>" . $res2->label . "<td nowrap>" . ($res2->valeur == 1 ? img_picto("ok", "tick") . " Valider par " : img_error() . " Refuser par ") . "" . $tmpUser->getNomUrl(1) . "<td width=600>" . $res2->note;
                }
            }
            print "        </table></td>";
        } else {
            print "<td>&nbsp;</td>";
        }
        //TODO si note identique et utilisateur identique et valeur identique condense
    }
    print "</table>";
}

function displayNav($page, $limit, $type) {
    global $db;
    //Get Tot page
    $requete = "SELECT count(DISTINCT process_refid,
                       element_refid,
                       processdet_refid) as cnt
                  FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet_validation";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $totPage = ceil($res->cnt / $limit);
    //Display Nav
    $get = "";
    if ($type) {
        $get = "?" . $type . "=1";
    }
    print "<div style='float:right; padding: 15px; margin-top: -6em;' class='ui-widget' id='navPage'><table><tr>";
    if ($page != 0)
        print "<td><span style='cursor: pointer; float: left;' onClick='location.href=\"historyValidation.php" . $get . "&page=" . intval($page - 1) . "&limit=" . $limit . "\"' class='ui-icon ui-icon-triangle-1-w ui-state-default'></span>";
    print "&nbsp;&nbsp;<td>Page " . intval($page + 1) . " sur " . $totPage . "<span>&nbsp;&nbsp;";
    if ($page . "x" != intval($totPage - 1) . "x")
        print "<td><span style='cursor: pointer; float: left;' onClick='location.href=\"historyValidation.php" . $get . "&page=" . intval($page + 1) . "&limit=" . $limit . "\"' class='ui-icon ui-icon-triangle-1-e ui-state-default'></span>";
    print "</table></div>";

    print "<script>jQuery(document).ready(function(){
        jQuery('.ui-icon').mouseover(function(){
            jQuery(this).addClass('ui-state-highlight');
        });
        jQuery('.ui-icon').mouseout(function(){
            jQuery(this).removeClass('ui-state-highlight');
        });
    })</script>";
}

llxFooter();
?>