<?php

//
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

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
 * GLE by Synopsis et DRSI
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
  \file       htdocs/fichinter/fiche.php
  \brief      Fichier fiche intervention
  \ingroup    ficheinter
  \version    $Id: fiche.php,v 1.100 2008/07/15 00:57:37 eldy Exp $
 */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT . "/fichinter/class/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/modules_synopsisfichinter.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/fichinter.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");
if ($conf->projet->enabled) {
    require_once(DOL_DOCUMENT_ROOT . "/core/lib/project.lib.php");
    require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
}
if (defined("FICHEINTER_ADDON") && is_readable(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/mod_" . FICHEINTER_ADDON . ".php")) {
    require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/mod_" . FICHEINTER_ADDON . ".php");
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
if ($_REQUEST["id"] > 0) {
    /*
     * Affichage en mode visu
     */
    global $db, $fichinter;
    initObj();
    //saveHistoUser($fichinter->id, "FI", $fichinter->ref);

    if ($mesg)
        print $mesg . "<br>";

    $head = Synopsis_fichinter_prepare_head($fichinter);

    dol_fiche_head($head, 'cardFast', $langs->trans("InterventionCard"));




    if (isset($_POST['update']))
        saveForm();

    $genererDoc = (extra(17) == 1 || extra(18) != null || extra(19) != '');
    if ($genererDoc) {
        genererDoc($db);
        affLienDoc($fichinter, $formfile, $conf);
        print "<br>\n";
        print "<br>\n";
    }

    print '<form method="post" action="" class="formFast"><input type="hidden" name="update" value=""/>';
    print '<table class="border" cellpadding=15 width="100%">';

    // Ref
    print '<tr><th width="25%">' . $langs->trans("Ref") . '</th>
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
        $com = new Synopsis_Commande($db);
        $com->fetch($fichinter->fk_commande);
        print "<td class='ui-widget-content'>" . $com->getNomUrl(1);
        /* print "<th class='ui-widget-header ui-state-default' width=20%>Pr&eacute;paration de commande";
          print "<td class='ui-widget-content'> ". $com->getNomUrl(1,5) ."</td> "; */
    }
    // Date
    $fichinter->info($fichinter->id);
    print '</tr><tr>';
    print '    <th class="ui-widget-header ui-state-default">Intervenant</th>';
    print '    <td class="ui-widget-content">' . $fichinter->user_creation->getNomUrl(1) . '</td>';
    print '    <th class="ui-widget-header ui-state-default">Contact</th>';
    print '    <td class="ui-widget-content">';
    $req = "SELECT *  FROM `" . MAIN_DB_PREFIX . "element_contact` WHERE `statut` = 4 AND `element_id` = " . $fichinter->id . " AND `fk_c_type_contact` = 131";
    $sql = $db->query($req);
    $res56 = $db->fetch_object($sql);
    $selected = (isset($res56->fk_socpeople) ? $res56->fk_socpeople : null); //die( "rrrr".$selected);
    $html->select_contacts($fichinter->socid, $selected);
    print '    </td>';
    print '</tr>';

    $selectHtml = '';
    $tabSelect = array(-1 => "Choix", 1 => "Forfait", 2 => "Sous garantie", 3 => "Contrat", 4 => "Temps pass&eacute;");
    foreach ($tabSelect as $i => $option) {
        if ($i == extra(35)) {
            $selectHtml .= "<OPTION SELECTED value='" . $i . "'>" . $option . "</OPTION>";
        } else {
            $selectHtml .= "<OPTION value='" . $i . "'>" . $option . "</OPTION>";
        }
    }
    $selectHtml2 = '';
    $tabSelect = array("Choix", "Installation", "Dépannage", "Télémaintenance", "Formation", "Audit", "Suivi");
    foreach ($tabSelect as $i => $option) {
        if ($i == $fichinter->natureInter) {
            $selectHtml2 .= "<OPTION SELECTED value='" . $i . "'>" . $option . "</OPTION>";
        } else {
            $selectHtml2 .= "<OPTION value='" . $i . "'>" . $option . "</OPTION>";
        }
    }
    $sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_fichinter WHERE rowid =" . $fichinter->id);
    $resultG = $db->fetch_object($sql);


    print '</tr><tr>';
    print '    <th width="25%" class="ui-widget-header ui-state-default">Date d\'intervention</th>
               <td class="ui-widget-content" colspan="1"><input type="text" class="datePicker" value="' . str_replace("-", "/", convertirDate($resultG->datei)) . '" name="date"/></td>';


    print '<th class="ui-widget-header ui-state-default">' . $langs->trans("DI") . '</th>';
    print '<td colspan=1 class="ui-widget-content">';
    $tabDI = $fichinter->getDI();
    if (count($tabDI) > 0) {
        $requete = "SELECT *
                  FROM " . MAIN_DB_PREFIX . "Synopsis_demandeInterv
                 WHERE rowid IN (" . implode(",", $tabDI) . ")";
        print "<table class='nobordernopadding' width=100%>";
        if ($resql = $db->query($requete)) {
            while ($res = $db->fetch_object($resql)) {
                print "<tr><td class='ui-widget-content'><a href='" . DOL_URL_ROOT . "/Synopsis_DemandeInterv/fiche.php?id=" . $res->rowid . "'>" . img_object('', 'intervention') . " " . $res->ref . "</a></td></tr>";
            }
        }
        print "</table>";
    }
    print '</td>';


    print '</tr>';

    print '</tr><tr>';
    print '    <th width="25%" class="ui-widget-header ui-state-default">Type de prestation</th>
               <td class="ui-widget-content" colspan="1"><select name="typeInter">' . $selectHtml . '</select></td>';
    print '    <th width="25%" class="ui-widget-header ui-state-default">Nature de la prestation</th>
               <td class="ui-widget-content" colspan="1"><select name="natureInter">' . $selectHtml2 . '</select></td>';
    print '</tr>';


    $prestations = prestations();



    echo '<tr>
            <th width="25%" class="ui-widget-header ui-state-default">Heures matin</th>
            <td class="ui-widget-content" colspan="3"><input type="text" name="date1" value="' . extra(24) . '"/> &agrave; <input type="text" name="date3" value="' . extra(26) . '"/></td>
        </tr>
';
    echo '<tr>
            <th width="25%" class="ui-widget-header ui-state-default">Heures apr&eacute;s-midi</th>
            <td class="ui-widget-content" colspan="3"><input type="text" name="date2" value="' . extra(25) . '"/>    &agrave;    <input type="text" name="date4" value="' . extra(27) . '"/></td>
        </tr>
';
    echo '<tr>
            <th width="25%" class="ui-widget-header ui-state-default">Titre</th>
            <td class="ui-widget-content" colspan="3"><input type="text" name="desc" style="width:320px;" value="' . $resultG->description . '"/></td>
        </tr>';
    echo '<tr>
            <th>Intervention</th>
        </tr>
';
    $nbPrest = 0;
    foreach ($prestations as $prestation) {
        $nbPrest++;
        $prefId = "presta" . $nbPrest . "_";
        $selectHtml = "<SELECT name='" . $prefId . "fk_typeinterv'>";
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_fichinter_c_typeInterv WHERE active = 1 AND id != 17 ORDER BY rang";
        $sql3 = $db->query($requete);
        $selectHtml .= "<OPTION value='-1'>Selectionner-></OPTION>";
        $dfltPrice = 0;
        while ($res3 = $db->fetch_object($sql3)) {
            if (!$fichinter->fk_contrat || $res3->id == 14 || $res3->id == 20 || $res3->id == 21) {
                if ($res3->id == $prestation->fk_typeinterv) {
                    $selectHtml .= "<OPTION SELECTED value='" . $res3->id . "'>" . $res3->label . "</OPTION>";
                } else {
                    $selectHtml .= "<OPTION value='" . $res3->id . "'>" . $res3->label . "</OPTION>";
                }
            }
        }
        $selectHtml .= "</SELECT></td>";


        $htmlSelect2 = "";
        if ($fichinter->fk_commande > 0) {
            $com = new Synopsis_Commande($db);
            $com->fetch($fichinter->fk_commande);
            $com->fetch_group_lines(0, 0, 0, 0, 1);
            $htmlSelect2 .= "<select name='" . $prefId . "fk_prod'>";
            $htmlSelect2 .= "<option value='0'>S&eacute;lectionner-></option>";

            foreach ($com->lines as $key => $val) {
                if (isset($val->fk_product) && $val->fk_product != '') {
                    $prod = new Product($db);
                    $prod->fetch($val->fk_product);
                    if ($prod->id == $prestation->fk_depProduct) {//$objp->fk_commandedet){
                        $htmlSelect2 .= "<option SELECTED value='" . $prod->id . "'>" . $prod->ref . " " . $val->description . " </option>";
                    } elseif ($prod->type != 0) {
                        $htmlSelect2 .= "<option value='" . $prod->id . "'>" . $prod->ref . " " . $val->description . "</option>";
                    }
                }
            }
            $htmlSelect2 .= "</select>";
        }




        $htmlStr = '<input type="hidden" name="' . $prefId . 'rowid" value="' . $prestation->rowid . '"/>';

        $htmlStr .= '<tr>
            <th width="25%">Type</th>
            <td class="ui-widget-content" colspan="1">' . $selectHtml . '</td>
            <td class="ui-widget-content" colspan="1">' . $htmlSelect2 . '</td>
            <td class="ui-widget-content" colspan="1"><input type="button" class="supprPrestaButton butAction" id="suppr_' . $prefId . '" value="Supprimer"/></td>';
        $htmlStr .= '</tr><tr>';
        $htmlStr .= '<th width="25%" class="ui-widget-header ui-state-default">Dur&eacute;e</th>
            <td class="ui-widget-content" colspan="1"><input type="text" class="heures" name="' . $prefId . 'duree" value="' . $prestation->duree . '"/></td>
        </tr>';
        $htmlStr .= '<tr>
            <th width="25%" class="ui-widget-header ui-state-default">Rapport</th>
            <td class="ui-widget-content" colspan="3"><textarea name="' . $prefId . 'descPrest">' . stripslashes($prestation->description) . '</textarea></td>
        </tr>';
        echo $htmlStr;







        require_once(DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
        require_once(DOL_DOCUMENT_ROOT . '/product/class/product.class.php');
        $htmlStr = '';
//        if ($fichinter->fk_contrat > 0) {
//            $requete = "SELECT fk_contratdet FROM " . MAIN_DB_PREFIX . "Synopsis_fichinterdet WHERE fk_fichinter = " . $fichinter->id . " AND fk_contratdet is not null";
//            $sql = $db->query($requete);
//            $arrTmp = array();
//            while ($res = $db->fetch_object($sql)) {
//                $arrTmp[$res->fk_contratdet] = $res->fk_contratdet;
//            }
//            require_once(DOL_DOCUMENT_ROOT . "/core/lib/contract.lib.php");
//            $contrat = getContratObj($fichinter->fk_contrat);
//            $type = getTypeContrat_noLoad($fichinter->fk_contrat);
//            if ($type == 7) {
//                $contrat->fetch($fichinter->fk_contrat);
//                $contrat->fetch_lines(true);
//                $htmlStr .= '<tr>
//            <th width="25%" class="ui-widget-header ui-state-default">Rapport</th>
//            <td class="ui-widget-content" colspan="3">';
//
//                $htmlStr .= "<table width=100%>";
//                $htmlStr .= "<tr><td><input type=radio name='" . $prefId . "contradet' class='radioContradet' value='0' checked=\"checked\"><td>";
//                foreach ($contrat->lines as $key => $val) {
//                    $selected = ($prestation->fk_contratdet == $val->id);
//                    $htmlStr .= "<tr><td><input type=radio name='" . $prefId . "contradet' class='radioContradet contradet" . $val->id . "' value='" . $val->id . "' " . ($selected ? 'checked="checked"' : '') . "><td>";
//                    $htmlStr .= "<ul style='list-style: none; padding-left: 0px; padding-top:0; margin-bottom:0; margin-top: 0px;'>";
//                    $htmlStr .= $contrat->display1Line($contrat, $val);
//                    $htmlStr .= "</ul>";
//                }
//                $htmlStr .= "</table>";
//
//
//                echo '</td></tr>';
//            }
//        }
        echo $htmlStr;
    }
    echo '<tr id="ajPrestaZone"><td colspan="4"><input type="button" class="butAction" id="ajPresta" value="Ajouter ligne"/><input type="hidden" id="supprPresta" name="supprPresta" value=""/></td></tr>';
    $checked = (extra(17) == 1) ? ' checked="checked"' : '';

    echo '<tr>
            <th width="25%" class="ui-widget-header ui-state-default">Pr&eacute;conisation du technicien (max 7 lignes)</th>
            <td class="ui-widget-content" colspan="3"><textarea name="desc2">' . extra(28) . '</textarea></td>
        </tr>
';


    echo '<tr>
            <th width="25%" class="ui-widget-header ui-state-default">Commentaires non imprimable</th>
            <td class="ui-widget-content" colspan="3"><textarea name="descP">' . $resultG->note_private . '</textarea></td>
        </tr>';


    echo '<tr>
            <th class="ui-widget-header ui-state-default" width="25%">Intervention termin&eacute;</th>
            <td class="ui-widget-content" colspan="1"><input type="checkbox"' . $checked . ' name="terminer" class="interTerm"/></td>
            <th class="ui-widget-header ui-state-default" width="25%" class="zoneDatePREDV">Date prochain RDV</th>
            <td class="ui-widget-content zoneDatePREDV" colspan="1"><input type="text" class="datePicker" value="' . str_replace("-", "/", convertirDate(extra(18))) . '" name="datePRDV"/></td>
        </tr>';

    echo '<tr>
            <th class="ui-widget-header ui-state-default" width="25%">Attentes client</th>
            <td class="ui-widget-content" colspan="3"><textarea name="attentes">' . extra(19) . '</textarea></td>
        </tr>';
    print '</tr></table>';

    print '</br></br><input type="submit" class="butAction"/></form>';


    if ($genererDoc) {
        print "<br>\n";
        print "<br>\n";
        affLienDoc($fichinter, $formfile, $conf);
    }
}

print <<<EOF
<script>
jQuery(document).ready(function(){
    jQuery('.datePicker').datepicker({
        dateFormat: 'dd/mm/yy',
        regional: 'fr',
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        constrainInput: true,
        gotoCurrent: true,showTime: false,        onSelect: function(dateText, inst) {            jQuery("#liv_day").val(inst.selectedDay);            jQuery("#liv_month").val(inst.selectedMonth + 1);            jQuery("#liv_year").val(inst.selectedYear);        }
    });
});
</script>
EOF;



//Pour le html repliquer (ligne de prestation)
$selectHtml = '<SELECT-supprjs name="\'+prefId+\'fk_typeinterv">';
$requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_fichinter_c_typeInterv WHERE active = 1 ORDER BY rang";
$sql3 = $db->query($requete);
$selectHtml .= '<OPTION value="-1">Selectionner-></OPTION>';
while ($res3 = $db->fetch_object($sql3)) {
    if (!$fichinter->fk_contrat || $res3->id == 14 || $res3->id == 20 || $res3->id == 21)
        $selectHtml .= '<OPTION value="' . $res3->id . '">' . $res3->label . '</OPTION>';
}
$selectHtml .= "</SELECT>";


$htmlSelect2 = "";
if ($fichinter->fk_commande > 0) {
    $com = new Synopsis_Commande($db);
    $com->fetch($fichinter->fk_commande);
    $com->fetch_group_lines(0, 0, 0, 0, 1);
    $htmlSelect2 .= '<select-supprjs name="\'+prefId+\'fk_prod">';
    $htmlSelect2 .= '<option value="0">S&eacute;lectionner-></option>';

    foreach ($com->lines as $key => $val) {
        if (isset($val->fk_product) && $val->fk_product != '') {
            $prod = new Product($db);
            $prod->fetch($val->fk_product);
            if ($prod->type != 0)
                $htmlSelect2 .= '<option value="' . $prod->id . '">' . $prod->ref . ' ' . $val->description . "</option>";
        }
    }
    $htmlSelect2 .= "</select>";
}

$htmlStr = '<tr>
            <th width="25%">Type</th>
            <td class="ui-widget-content" colspan="1">
                <input type="hidden" name="\'+prefId+\'rowid" value=""/>
                ' . $selectHtml . '
            </td>
            <td class="ui-widget-content" colspan="1">
                ' . $htmlSelect2 . '
            </td>
            <td class="ui-widget-content" colspan="1">
                <input type="button" class="supprPrestaButton butAction" id="suppr_\'+prefId+\'" value="Supprimer"/>
           </td>
        </tr>
        <tr>
            <th width="25%" class="ui-widget-header ui-state-default">Dur&eacute;e</th>
            <td class="ui-widget-content" colspan="1"><input type="text" name="\'+prefId+\'duree" class="heures-supprjs" value="0"/></td>
        </tr>';
$htmlStr .= '<tr>
            <th width="25%" class="ui-widget-header ui-state-default">Rapport</th>
            <td class="ui-widget-content" colspan="3"><textarea name="\'+prefId+\'descPrest"></textarea></td>
        </tr>';

//Contradet
require_once(DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
require_once(DOL_DOCUMENT_ROOT . '/product/class/product.class.php');
//if ($fichinter->fk_contrat > 0) {
//    $requete = "SELECT fk_contratdet FROM " . MAIN_DB_PREFIX . "Synopsis_fichinterdet WHERE fk_fichinter = " . $fichinter->id . " AND fk_contratdet is not null";
//    $sql = $db->query($requete);
//    $arrTmp = array();
//    while ($res = $db->fetch_object($sql)) {
//        $arrTmp[$res->fk_contratdet] = $res->fk_contratdet;
//    }
//    require_once(DOL_DOCUMENT_ROOT . "/core/lib/contract.lib.php");
//    $contrat = getContratObj($fichinter->fk_contrat);
//    $type = getTypeContrat_noLoad($fichinter->fk_contrat);
//    if ($type == 7) {
//        $contrat->fetch($fichinter->fk_contrat);
//        $contrat->fetch_lines(true);
//        $htmlStr .= '<tr>
//            <th width="25%" class="ui-widget-header ui-state-default">Rapport</th>
//            <td class="ui-widget-content" colspan="3">';
//
//        $htmlStr .= "<table width=100%>";
//        $htmlStr .= '<tr><td><input type=radio name="\'+prefId+\'contradet" class="radioContradet" value="0" checked="checked"><td>';
//        foreach ($contrat->lines as $key => $val) {
//            $selected = false;
//            $htmlStr .= '<tr><td><input type="radio" name="\'+prefId+\'contradet" class="radioContradet contradet' . $val->id . '" value="' . $val->id . '" ' . ($selected ? 'checked="checked"' : '') . "><td>";
//            $htmlStr .= "<ul style='list-style: none; padding-left: 0px; padding-top:0; margin-bottom:0; margin-top: 0px;'>";
//            $htmlStr .= $contrat->display1Line($contrat, $val);
//            $htmlStr .= "</ul>";
//        }
//        $htmlStr .= "</table>";
//
//
//        $htmlStr .= '</td></tr>';
//    }
//}
//Fin contradet
echo '<div style="display:none" id="refAjaxLigne"><table>' . $htmlStr . '</table></div>';

//$htmlStr = str_replace("\n", "", $htmlStr);
echo '<script>
function initPresta(){
    jQuery(".supprPrestaButton").click(function(){
        jQuery("#supprPresta").val(jQuery("#supprPresta").val()+$(this).attr("id").replace("suppr_",""));
        jQuery(this).parent().parent().next("tr").next("tr").fadeOut();
        jQuery(this).parent().parent().next("tr").fadeOut();
        jQuery(this).parent().parent().fadeOut();
    });
}
jQuery(document).ready(function(){
    nbPresta = ' . $nbPrest . ';
    initPresta();
    jQuery("#ajPresta").click(function(){
        nbPresta ++;
        prefId = "presta"+nbPresta+"_";
        actuContradet();
        var htmlStr = jQuery("#refAjaxLigne table tbody").html();
        varARemp = "\'+prefId+\'";
        htmlStr = htmlStr.split(varARemp).join(prefId);
        htmlStr = htmlStr.split("-supprjs").join("");
        jQuery("#ajPrestaZone").before(htmlStr);
        initHeure($(".heures"));
        initPresta();
    });
    


    jQuery(".heures").each(function(){
        initHeure(this);
    });

    autoSave();
});

function autoSave(){
    jQuery("*").click(function(){
        initTimeSave();
    });
    jQuery("*").keypress(function(){
        initTimeSave();
    });

    function boucleSave(){
        if(new Date().getTime()>timeMax)
            jQuery("form.formFast").submit();
        setTimeout(function(){
            boucleSave();
        }, 1000);
    }
    timeMax = null;
    function initTimeSave(){
        if(timeMax == null){
            timeMax = new Date().getTime() + 30000;
            boucleSave();
        }
        else
            timeMax = new Date().getTime() + 30000;
    }
}

function initHeure(elem){
        jQuery(elem).addClass("originalHeure");
        jQuery(elem).removeClass("heures");
        jQuery(elem).hide();


        select1 = "";
        for(i=0;i<12;i++)
            select1 = select1 + \'<option value="\'+i+\'">\'+i+\'</option>\';
        select2 = "";
        for(i=0;i<11;i++)
            select2 = select2 + \'<option value="\'+i+\'">\'+i*5+\'</option>\';
        jQuery(elem).before(\'<select class="heureHeure">\'+select1+\'</select>H <select class="minHeure">\'+select2+\'</select>M \');





//mise a jour
        secondesAv = jQuery(elem).val();
        heuresAv = 0;
        minAv = 0;
        for(;secondesAv>=3600;secondesAv = secondesAv-3600)
            heuresAv ++;
        for(;secondesAv>=300;secondesAv = secondesAv-300)
            minAv ++;
        $(elem).parent().find(".heureHeure option[value="+heuresAv+"]").attr("selected", "selected");
        $(elem).parent().find(".minHeure option[value="+minAv+"]").attr("selected", "selected");




//Enreg
        jQuery(elem).parent().find(".heureHeure, .minHeure").change(function(){
            secondes = (3600*parseInt(jQuery(elem).parent().find(".heureHeure option:selected").val())) + (300*parseInt(jQuery(elem).parent().find(".minHeure option:selected").val()));
            jQuery(elem).parent().find(".originalHeure").val(secondes);
        });
        
        cacherDecacherPRDV();
        $(".interTerm").change(function(){
            cacherDecacherPRDV();
        });


    jQuery(".radioContradet").change(function(){
        actuContradet();
    });
    actuContradet();
}




function actuContradet(){
    jQuery(".radioContradet").removeAttr("disabled");
    jQuery(".radioContradet").each(function(){
        if(jQuery(this).is(":checked"))
            jQuery(".contradet"+jQuery(this).val()+":not(:checked)").attr("disabled", true);
    });
}

function cacherDecacherPRDV(){
    zoneRDV = $(".zoneDatePREDV");
    if($(".interTerm").is(":checked"))
        zoneRDV.hide();
    else
        zoneRDV.show();
}
</script>';

function affLienDoc($fichinter, $formfile, $conf) {
    $filename = sanitize_string($fichinter->ref);
    $filedir = $conf->synopsisficheinter->dir_output . "/" . $fichinter->ref;
    $urlsource = $_SERVER["PHP_SELF"] . "?id=" . $fichinter->id;
    $somethingshown = $formfile->show_documents('synopsisficheinter', $filename, $filedir, $urlsource, 0, 0);
}

function genererDoc($db) {
    global $user, $langs, $conf;
    $fichinter = new Fichinter($db);
    $fichinter->fetch($_REQUEST['id']);
    $fichinter->fetch_lines();

    if ($_REQUEST['lang_id']) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }

    $result = fichinter_create($db, $fichinter, $_REQUEST['model'], $outputlangs);
    if ($result <= 0) {
        dol_print_error($db, $result);
        exit;
    } else {
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface = new Interfaces($db);
        $result = $interface->run_triggers('ECM_GENFICHEINTER', $fichinter, $user, $langs, $conf);
        if ($result < 0) {
            $error++;
            print_r($interface->errors);
        }
        // Fin appel triggers
    }
}

function stripslashes_r($var) { // Fonction qui supprime l'effet des magic quotes
    if (is_array($var))
        return array_map('stripslashes_r', $var);
    else
        return stripslashes($var);
}

function addslashes_r($var) { // Fonction qui supprime l'effet des magic quotes
    if (is_array($var))
        return array_map('addslashes_r', $var);
    else
        return addslashes($var);
}

if (get_magic_quotes_gpc()) { // Si les magic quotes sont activés, on les désactive
    $_GET = stripslashes_r($_GET);
    $_POST = stripslashes_r($_POST);
    $_COOKIE = stripslashes_r($_COOKIE);
}

function saveForm() {
    global $db, $fichinter;
    $_POST = addslashes_r($_POST);
//    $req = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_fichinter where `rowid` =" . $fichinter->id;
//    $sql = $db->query($req);
//    $oldData = mysql_fetch_object($req);
    $req = "UPDATE `" . MAIN_DB_PREFIX . "Synopsis_fichinter` SET  `datei` =  '" . convertirDate($_POST['date'], false) . "',`note_private` =  '" . $_POST['descP'] . "',`description` =  '" . $_POST['desc'] . "', natureInter = '" . $_POST['natureInter'] . "' WHERE  `" . MAIN_DB_PREFIX . "Synopsis_fichinter`.`rowid` =" . $fichinter->id;
    $sql = $db->query($req);
    extra(24, $_POST['date1']);
    extra(19, $_POST['attentes']);
    extra(25, $_POST['date2']);
    extra(26, $_POST['date3']);
    extra(27, $_POST['date4']);
    extra(18, $_POST['datePRDV']);
    extra(35, $_POST['typeInter']);
    extra(17, (isset($_POST['terminer']) ? '1' : '0'));
    extra(28, $_POST['desc2']);
    //prestation($_POST['descPrest'], $_POST['fk_typeinterv']);
    $fichinter->info($fichinter->id);
    for ($i = 1; isset($_POST['presta' . $i . "_rowid"]); $i++) {
        $fk_typeinterv = $_POST['presta' . $i . "_fk_typeinterv"];
        $pu_ht = 0;
        if (preg_match('/dep-([0-9]*)/', $fk_typeinterv, $arr)) {
            $fk_typeinterv = 4;
            $typeIntervProd = $arr[1];
            $requete = "SELECT prix_ht
                          FROM " . MAIN_DB_PREFIX . "Synopsis_fichinter_User_PrixDepInterv
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
                          FROM " . MAIN_DB_PREFIX . "Synopsis_fichinter_User_PrixTypeInterv
                         WHERE user_refid = " . $fichinter->user_creation->id . "
                           AND typeInterv_refid = " . $fk_typeinterv;
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            if ($res->prix_ht > 0) {
                $pu_ht = $res->prix_ht;
            }
        }


        if (!isset($_POST['presta' . $i . "_rowid"]) || $_POST['presta' . $i . "_rowid"] == 0) {
            if (stripos($_POST['supprPresta'], 'presta' . $i . '_') === false)
                $fichinter->addline(
                        $fichinter->id, $_POST['presta' . $i . "_descPrest"], date("Y-m-d"), $_POST['presta' . $i . "_duree"], $_POST['presta' . $i . "_fk_typeinterv"], 1, $pu_ht, $_POST['typeInter'], $_REQUEST['comLigneId'], (isset($_POST['presta' . $i . "_contradet"]) ? $_POST['presta' . $i . "_contradet"] : 0), $_POST['presta' . $i . "_fk_prod"]
                );
        }
        else {
            $fichinterline = new FichinterLigne($db);
            $fichinterline->fetch($_POST['presta' . $i . "_rowid"]);
            if (stripos($_POST['supprPresta'], 'presta' . $i . '_') === false) {
                $fichinterline->desc = $_POST['presta' . $i . "_descPrest"];
                $fichinterline->duration = $_POST['presta' . $i . "_duree"];
                $fichinterline->fk_typeinterv = $_POST['presta' . $i . "_fk_typeinterv"];
                $fichinterline->fk_contratdet = (isset($_POST['presta' . $i . "_contradet"]) ? $_POST['presta' . $i . "_contradet"] : 0);
                $fichinterline->typeIntervProd = $_POST['presta' . $i . "_fk_prod"];
                $result = $fichinterline->update();
            }
            else
                $fichinterline->delete_line();
        }
    }


    $req = "SELECT *  FROM `" . MAIN_DB_PREFIX . "element_contact` WHERE `statut` = 4 AND `element_id` = " . $fichinter->id . " AND `fk_c_type_contact` = 131";
    $sql = $db->query($req);
    $res = $db->fetch_object($sql);
    if (isset($res->rowid))
        $fichinter->delete_contact($res->rowid);
    $result = $fichinter->add_contact($_POST["contactid"], 131, 'external');
    //prestations($_POST['presta' . $i . "_rowid"], $_POST['presta' . $i . "_descPrest"], $_POST['presta' . $i . "_fk_typeinterv"], $_POST['presta' . $i . "_duree"]);

    initObj();
}

function initObj() {
    global $fichinter, $db;
    $fichinter = new Fichinter($db);
    $result = $fichinter->fetch($_REQUEST["id"]);
    if (!$result > 0) {
        dol_print_error($db);
        exit;
    }
    $fichinter->fetch_client();
}

function extra($cle, $val = null) {
    global $db, $fichinter;
    if (is_null($val)) {//On fait un select
        $sql = $db->query("SELECT extra_value as val FROM " . MAIN_DB_PREFIX . "Synopsis_fichinter_extra_value WHERE extra_key_refid = " . $cle . " AND interv_refid =" . $fichinter->id);
        $result = $db->fetch_object($sql);
        if (!is_object($result))
            return NULL;
        return $result->val;
    }
    else {
        if (is_null(extra($cle)))
            $req = "INSERT INTO `" . MAIN_DB_PREFIX . "Synopsis_fichinter_extra_value` (`extra_value`, extra_key_refid, `interv_refid`, typeI) VALUES('" . $val . "', " . $cle . "," . $fichinter->id . ", 'FI')";
        else
            $req = "UPDATE  `" . MAIN_DB_PREFIX . "Synopsis_fichinter_extra_value` SET  `extra_value` =  '" . $val . "' WHERE extra_key_refid = " . $cle . " AND `interv_refid` =" . $fichinter->id;
        $sql = $db->query($req);
        return true;
    }
}

function prestations($rowid = null, $desc = null, $type = null, $duree = null) {
    global $db, $fichinter;
    if ($desc) {//Insert ou update
        if ($rowid && $rowid != 0)
            $req = "UPDATE  `" . MAIN_DB_PREFIX . "Synopsis_fichinterdet` SET  `description` =  '" . $desc . "', `fk_typeinterv` =  '" . $type . "', duree = '" . $duree . "' WHERE  `rowid` =" . $rowid;
        else
            $req = "INSERT INTO `" . MAIN_DB_PREFIX . "Synopsis_fichinterdet` (`description`, `fk_typeinterv`, fk_fichinter, date, duree) VALUES ('" . $desc . "', " . $type . ", " . $fichinter->id . ", now(), '" . $duree . "')";
        $sql = $db->query($req);
    } else {//Select
        if ($rowid)
            $req = "SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_fichinterdet` WHERE fk_fichinter= " . $fichinter->id . " AND rowid =" . $rowid;
//        $req = "SELECT * FROM `".MAIN_DB_PREFIX."Synopsis_fichinterdet` WHERE rowid = (SELECT min(rowid) FROM `".MAIN_DB_PREFIX."Synopsis_fichinterdet` WHERE fk_fichinter= " . $fichinter->id . ")";
        else
            $req = "SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_fichinterdet` WHERE fk_fichinter= " . $fichinter->id;
        $sql = $db->query($req);
        return mysqlToArray($sql, $db);
    }
}

function mysqlToArray($data, $db) {
    $tab = array();
    if ($data) {
        while ($row = $db->fetch_object($data)) {
            $tab[] = $row;
        }
        return $tab;
    }
    else
        return false;
}

function convertirDate($date, $enFr = true, $nowSiNull = false) {
    $tabDate = explode('-', $date);
    if (!isset($tabDate[1]))
        $tabDate = explode('/', $date);
    if (isset($tabDate[2]) && $date != "0000-00-00") {
        if (($tabDate[0] > 32 && $enFr) || ($tabDate[2] > 32 && !$enFr))
            return $tabDate[2] . '-' . $tabDate[1] . '-' . $tabDate[0];
        else
            return $tabDate[0] . '-' . $tabDate[1] . '-' . $tabDate[2];
    }
    else {
        if ($nowSiNull)
            if ($enFr)
                return date("d-m-Y");
            else
                return date("Y-m-d");
        else
            return '';
    }
}

llxfooter();
?>
