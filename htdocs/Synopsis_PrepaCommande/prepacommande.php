<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 10 sept. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : prepacommande.php
 * GLE-1.2
 */
/**
 *
  D’automatiser la saisie de nouveaux contrats (Prorata Temporis si le client a
  deja d’autres contrats de maintenance aﬁn de
  simpliﬁer les renouvellements)
  Saisie auto DI
  Saisie auto contrat
  groupage commande
 */
require_once('pre.inc.php');
require_once(DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
require_once(DOL_DOCUMENT_ROOT . "/core/lib/order.lib.php");

$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('propal');
$langs->load("synopsisGene@Synopsis_Tools");
$langs->load('deliveries');
$langs->load('products');

if (!$user->rights->commande->lire)
    accessforbidden();
if (!$user->rights->SynopsisPrepaCom->all->Afficher)
    accessforbidden();

// Securite acces client
$socid = 0;
if ($user->societe_id > 0) {
    $socid = $user->societe_id;
}
if ($user->societe_id > 0 && isset($_GET["id"]) && $_GET["id"] > 0) {
    $commande = new Synopsis_Commande($db);
    $commande->fetch((int) $_GET['id']);
    if ($user->societe_id != $commande->socid) {
        accessforbidden();
    }
}



if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'addTech') {
    $requete = "INSERT into llx_element_element (sourcetype, fk_source, targettype, fk_target) VALUES ('soc', " . $_REQUEST['socid'] . ", 'userTech', " . $_REQUEST['userid'] . ");";
    $db->query($requete);
}
elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delTech') {
    $requete = "DELETE FROM llx_element_element WHERE sourcetype = 'soc' AND fk_source = " . $_REQUEST['socid'] . " AND targettype = 'userTech' AND fk_target = " . $_REQUEST['userid'] . ";";
    $db->query($requete);
}   



$id = $_REQUEST['id'];
if (!$id > 0) {
    llxHeader($js, "Préparation de commande", 1);
    print "<div class='error ui-error'>Pas de commande s&eacute;lectionn&eacute;e";
    exit;
}
$com = new Synopsis_Commande($db);
$com->fetch($id);

//saveHistoUser($com->id, "prepaCom",$com->ref);


$js = '<script> var comId = ' . $id . ';';
$js .= ' var userId = ' . $user->id . ';';
$js .= ' var socId = ' . $com->socid . ';';
$js .= ' var DOL_URL_ROOT = "' . DOL_URL_ROOT . '";';
$js.=<<<EOF

var url = {
    part1 : "fiche-html_response.php",
    part2 : "coordonnees-html_response.php",
    part3 : "logistique-html_response.php?part=3",
    part4 : "finance-html_response.php",
    part5 : "commerciaux-html_response.php",
    part6 : "tech-html_response.php",
    part7 : "expedition-html_response.php",
    part9 : "interventions-html_response.php",
    part10 : "ficheContrat-html_response.php",
    part11 : "messages-html_response.php",
};

var suburl = {
    part1a: "fiche-html_response.php",
    part1b: "ficheClient-html_response.php",
    part1c: "ficheCommande-html_response.php",
    part2a: "coordonnees-html_response.php",
    part3a: "logistique-html_response.php",
    part4a: "finance-html_response.php",
    part5a: "commerciaux-html_response.php",
    part6a: "tech-html_response.php",
    part7a: "expedition-html_response.php",
    part9a: "interventions-html_response.php",
    part9b: "demandeInterv-html_response.php",
    part9c: "ficheInterv-html_response.php",
    part9d: "dispoEquipe-html_response.php",
    part9e: "ramification-html_response.php",
    part9f: "diffInterv-html_response.php",
    part10a: "ficheContrat-html_response.php",
    part11a: "messages-html_response.php",
}
jQuery(document).ready(function(){
    jQuery( ".accordion" ).accordion({
        animated: 'bounceslide',
        autoHeight: false,
        collapsible: false,
        active: -1,
    });
    jQuery('.accordion').bind('accordionchangestart', function(event, ui) {
    //  ui.newHeader // jQuery object, activated header
    //  ui.oldHeader // jQuery object, previous header
    //  ui.newContent // jQuery object, activated content
    //  ui.oldContent // jQuery object, previous content
        jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
        //Load HTML
        var urlAjax = 'ajax/'+url[ui.newHeader.attr('id')];
        jQuery.ajax({
            url: urlAjax,
            data: "id="+comId,
            cache: true,
            datatype: "html",
            type: "POST",
            success: function(msg){
                jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
            },
        });
    });
    jQuery('.accordion').accordion('activate',0);

    jQuery('.submenu').click(function(){
        var subid=jQuery(this).attr('id');
        jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
        //Load HTML
        var urlAjax = 'ajax/'+suburl[subid];
        var SubSubid = subid.replace(/[^0-9]*/,"");
        //console.log(SubSubid);
        if (SubSubid=="9b")
        {
            jQuery.ajax({
                url: "ajax/xml/listInterv-xml_response.php",
                data: "type=DI&id="+comId,
                datatype: "xml",
                type: "POST",
                success: function(msg){
                    var longHtml = "<table>";
                    jQuery(msg).find('datainterv').each(function(){
                        var user = jQuery(this).find('interv').text();
                        var ref = jQuery(this).find('ref').text();
                        var rowid = jQuery(this).find('rowid').text();
                        var datei = jQuery(this).find('date').text();
                        longHtml += "<tr class='subsubmenu' onClick='openDI("+rowid+");'><td width=40>&nbsp;<td width=70>"+ref+"<td width=120>"+user+"<td width=70>"+datei

                    });
                    longHtml += "</table>";
                    jQuery('#subDI').replaceWith('<div id="subDI">'+longHtml+'</div>');
                    reloadResult();
                }
            });
        } else {
            jQuery('#subDI').replaceWith('<div id="subDI"></div>');
             reloadResult();
        }
        if (SubSubid=="9c")
        {
            jQuery.ajax({
                url: "ajax/xml/listInterv-xml_response.php",
                data: "type=FI&id="+comId,
                datatype: "xml",
                type: "POST",
                success: function(msg){
                    var longHtml = "<table>";
                    jQuery(msg).find('datainterv').each(function(){
                        var user = jQuery(this).find('interv').text();
                        var ref = jQuery(this).find('ref').text();
                        var rowid = jQuery(this).find('rowid').text();
                        var datei = jQuery(this).find('date').text();
                        longHtml += "<tr class='subsubmenu' onClick='openFI("+rowid+");'><td width=40>&nbsp;<td width=70>"+ref+"<td width=120>"+user+"<td width=70>"+datei

                    });
                    longHtml += "</table>";
                    jQuery('#subFI').replaceWith('<div id="subFI">'+longHtml+'</div>');
                }
            });
            jQuery('#subFI').replaceWith('<div id="subFI"><table><tr><td width=20><td>FI-0000<td>01/09/2010<td>Jean-Marc LE FEVRE</table></div>');
            reloadResult();
        } else {
            jQuery('#subFI').replaceWith('<div id="subFI"></div>');
            reloadResult();
        }
        jQuery.ajax({
            url: urlAjax,
            data: "id="+comId,
            cache: true,
            datatype: "html",
            type: "POST",
            success: function(msg){
                jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
                reloadResult();
            },
        });

    });

});
function openDI(rowid)
{
    var subid="part9b";
    var urlAjax = 'ajax/'+suburl[subid];
    jQuery.ajax({
        url: urlAjax,
        data: "id="+comId+"&diId="+rowid,
        cache: true,
        datatype: "html",
        type: "POST",
        success: function(msg){
            jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
        },
    });
}
function openFI(rowid)
{
    var subid="part9c";
    var urlAjax = 'ajax/'+suburl[subid];
    jQuery.ajax({
        url: urlAjax,
        data: "id="+comId+"&fiId="+rowid,
        cache: true,
        datatype: "html",
        type: "POST",
        success: function(msg){
            jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
        },
    });
}
function reloadResult()
{
    jQuery.ajax({
        url:"ajax/getResults-html_response.php",
        data:"id="+comId,
        datatype:"html",
        type:"POST",
        cache: false,
        success: function(msg){
            jQuery('#replaceResult').replaceWith('<div id="replaceResult">'+jQuery(msg).html()+'</div>');
        }
    })

}

</script>
EOF;
$js .= "<script src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/ui/ui.datepickerWithWeek.js' type='text/javascript'></script>";
$js .= '<script language="javascript" src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.validate.js"></script>' . "\n";

$js .= "<style>.submenu:hover { cursor: pointer; text-decoration: underline; font-weight: 900;}
.subsubmenu:hover { cursor: pointer; text-decoration: underline; font-weight: 900;}
</style>";
llxHeader($js, "Préparation de commande");

$head = commande_prepare_head($com);
dol_fiche_head($head, 'prepaCommande', $langs->trans("Preparation de commande"), 0, 'order');



print "<div class='titre'>Pr&eacute;paration</div>";
//
//
//print "<table>";
//foreach($com->lines as $key=>$val)
//{
//    print "<tr><td>".$val->desc;
//    print "    <td>".$val->fk_prod;
//    print "    <td>".$val->qty;
//}
//print "</table>";


print "<table width=100%>";
print "<tr><td valign=top width=250>D&eacute;tail";
print "<div class='accordion'>";
print "    <h3 id='part1'><a href='#'>G&eacute;n&eacute;ralit&eacute;</a></h3>";
print "     <div><table><tr><td><div id='part1a' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>G&eacute;n&eacute;ral</div></td></tr>";
print "                 <tr><td><div id='part1b' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>Client</div></td></tr>";
print "                 <tr><td><div id='part1c' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>Commande</div></table></div>";
print "    <h3 id='part2'><a href='#'>Coordonn&eacute;es</a></h3>";
print "     <div><table><tr><td><div id='part2a' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>Coordonn&eacute;es</div></table></div>";
if ($user->rights->SynopsisPrepaCom->exped->Afficher) {
    print "    <h3 id='part3'><a href='#'>Logistique</a></h3>";
    print "     <div><table><tr><td><div id='part3a' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>Logistique</div></table></div>";
}
if ($user->rights->SynopsisPrepaCom->financier->Afficher) {
    print "    <h3 id='part4'><a href='#'>D&eacute;tails financiers</a></h3>";
    print "     <div><table><tr><td><div id='part4a' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>Finance</div></table></div>";
}
print "    <h3 id='part5'><a href='#'>Commerciaux</a></h3>";
print "     <div><table><tr><td><div id='part5a' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>Commerciaux associ&eacute;s</div></table></div>";
print "    <h3 id='part6'><a href='#'>Techniciens</a></h3>";
print "     <div><table><tr><td><div id='part6a' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>Techniciens associ&eacute;s</div></table></div>";
if ($user->rights->SynopsisPrepaCom->exped->Afficher) {
    print "    <h3 id='part7'><a href='#'>Exp&eacute;ditions</a></h3>";
    print "     <div><table><tr><td><div id='part7a' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>Exped</div></table></div>";
}
if ($user->rights->SynopsisPrepaCom->interventions->Afficher) {
    print "    <h3 id='part9'><a href='#'>Interventions</a></h3>";
    print "     <div><table><tr><td><div id='part9a' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>G&eacute;n&eacute;ralit&eacute;</div></td></tr>
                            <tr><td><div id='part9b' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>Demande</div><div id='subDI'></div></td></tr>
                            <tr><td><div id='part9c' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>Fiche</div><div id='subFI'></div></td></tr>
                            <tr><td><div id='part9f' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>Diff DI/FI</div>
                            <tr><td><div id='part9d' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>Dispo</div>
                            <tr><td><div id='part9e' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>Autre</div></table></div>";
}
if ($user->rights->SynopsisPrepaCom->contrat->Afficher) {
    print "    <h3 id='part10'><a href='#'>Contrats</a></h3>";
    print "     <div><table><tr><td><div id='part10a' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>Contrats</div></table></div>";
}
print "    <h3 id='part11'><a href='#'>Messages</a></h3>";
print "     <div><table><tr><td><div id='part11a' class='submenu' style='display: inline;'><span class='ui-icon ui-icon-extlink' style='float:left; margin-top: -2px;'></span>Messages</div></table></div>";

print "</div>";
print "<td valign=top width=900>Fiche";
print "<div id='resDisp'>";
print "</table>";


print "<div id='replaceResult'>";


require(DOL_DOCUMENT_ROOT."/Synopsis_PrepaCommande/ajax/getResults-html_response.php");
/*
print "<div class='titre'>R&eacute;sum&eacute;</div>";
print "<br/>";


print "<table><tr><td valign=top>";
print "<table cellpadding=10 width=600>";
print "<tr><th class='ui-widget-header ui-state-hover' colspan=4>Interventions";
//Prevu dans la commande

$arrGrpCom = array($com->id => $com->id);
$arrGrp = $com->listGroupMember(true);
foreach ($arrGrp as $key => $commandeMember) {
    $arrGrpCom[$commandeMember->id] = $commandeMember->id;
}

//Vendu en euro
$requete = "SELECT SUM(total_ht) as tht
              FROM " . MAIN_DB_PREFIX . "commandedet,
                   llx_product,
                   llx_categorie_product,
                   llx_categorie
             WHERE llx_product.rowid = " . MAIN_DB_PREFIX . "commandedet.fk_product
               AND llx_categorie.rowid = llx_categorie_product.fk_categorie
               AND llx_categorie_product.fk_product = llx_product.rowid
               AND llx_categorie.rowid IN (SELECT catId FROM llx_Synopsis_PrepaCom_c_cat_total)
               AND " . MAIN_DB_PREFIX . "commandedet.fk_commande IN (" . join(',', $arrGrpCom) . ")  ";
$sql = $db->query($requete);
$res = $db->fetch_object($sql);
$vendu = $res->tht;

//Prevu en euro et en temps
$requete = "SELECT SUM(llx_Synopsis_demandeIntervdet.duree) as durTot ,
                   SUM(llx_Synopsis_demandeIntervdet.total_ht) as totHT
              FROM llx_Synopsis_demandeInterv,
                   llx_Synopsis_demandeIntervdet,
                   llx_Synopsis_fichinter_c_typeInterv
             WHERE llx_Synopsis_fichinter_c_typeInterv.id=llx_Synopsis_demandeIntervdet.fk_typeinterv
               AND llx_Synopsis_demandeIntervdet.fk_demandeinterv = llx_Synopsis_demandeInterv.rowid
               AND llx_Synopsis_fichinter_c_typeInterv.inTotalRecap=1
               AND fk_commande  IN (" . join(',', $arrGrpCom) . ") ";
$sql = $db->query($requete);
$res = $db->fetch_object($sql);
$prevuEuro = $res->totHT;
$prevuTemp = $res->durTot;
$arr1 = convDur($res->durTot);
//Realise en euros et en temps
$requete = "SELECT SUM(" . MAIN_DB_PREFIX . "fichinterdet.duree) as durTot ,
                   SUM(" . MAIN_DB_PREFIX . "fichinterdet.total_ht) as totHT
              FROM " . MAIN_DB_PREFIX . "fichinter,
                   " . MAIN_DB_PREFIX . "fichinterdet,
                   llx_Synopsis_fichinter_c_typeInterv
             WHERE llx_Synopsis_fichinter_c_typeInterv.id=" . MAIN_DB_PREFIX . "fichinterdet.fk_typeinterv
               AND " . MAIN_DB_PREFIX . "fichinterdet.fk_fichinter = " . MAIN_DB_PREFIX . "fichinter.rowid
               AND llx_Synopsis_fichinter_c_typeInterv.inTotalRecap=1
               AND " . MAIN_DB_PREFIX . "fichinter.fk_commande  IN (" . join(',', $arrGrpCom) . ") ";
//print $requete;
$sql = $db->query($requete);
$res = $db->fetch_object($sql);
$realEuro = $res->totHT;
$realTemp = $res->durTot;
$arr2 = convDur($res->durTot);



print "<tr><th width=150 class='ui-widget-header ui-state-default' >Vendu";
print "    <td width=150 class='ui-widget-content' align=right>" . price($vendu) . " &euro;";

print "<tr><th class='ui-widget-header ui-state-default' >Programm&eacute;";
print "    <td class='ui-widget-content' align=right>" . price($prevuEuro) . " &euro; / " . $arr1['hours']['abs'] . "h " . ($arr1['minutes']['rel'] > 0 ? $arr1['minutes']['rel'] . "m" : "");

print "<tr><th class='ui-widget-header ui-state-default' >R&eacute;alis&eacute;";
print "    <td class='ui-widget-content' align=right>" . price($realEuro) . " &euro; / " . $arr2['hours']['abs'] . "h " . ($arr2['minutes']['rel'] > 0 ? $arr2['minutes']['rel'] . "m" : "");

$diff_real_prevuEuro = $prevuEuro - $realEuro;
$diff_real_prevuTemp = $prevuTemp - $realTemp;

$diff_vendu_prevuEuro = $vendu - $prevuEuro;
$diff_vendu_realEuro = $vendu - $realEuro;

$arr3 = convDur($diff_real_prevuTemp);

$textExtra = "<td>Pr&eacute;vu superviseur/ Vendu par com.<td align=right>" . price($diff_vendu_prevuEuro) . "&euro;<td>
          <tr><td>R&eacute;al. total / Vendu par com.<td align=right>" . price($diff_vendu_realEuro) . "&euro;<td>
          <tr><td>R&eacute;al. total / Pr&eacute;vu superviseur<td align=right>" . price($diff_real_prevuEuro) . " &euro; <td align=center>(" . $arr3['hours']['abs'] . "h " . ($arr3['minutes']['rel'] > 0 ? $arr3['minutes']['rel'] . "m" : "") . ")";


//Boite ok, sous vendu, vendu Ok
print "<tr><td class='ui-widget-content' align=center colspan=2 >";
if ($prevuTemp > 0 || $realTemp > 0) {
    if ($prevuEuro <= $vendu && $realEuro <= $vendu) {
        print "<table cellpadding=3><tr><td rowspan=3 colspan=2 width=90>" . Babel_img_crystal('Vendu OK', 'actions/agt_games', 24, ' ALIGN="middle" style="margin-bottom:8px;" ') . " Vendu OK &nbsp;&nbsp;&nbsp;" . $textExtra . "</table>";
    } else if ($prevuEuro > $vendu) {
        print "<table cellpadding=3><tr><td rowspan=3><span style='border: 0' class='ui-state-highlight'><span class='ui-icon ui-icon-alert'></span><td width=90 rowspan=3><span style='padding:3px 10px;' class='ui-state-highlight'>Sous vendu</span>" . $textExtra . "</span></table>";
    } else {
        print "<table cellpadding=3><tr><td rowspan=3><span style='border: 0' class='ui-state-error'><span class='ui-icon ui-icon-alert'></span><td rowspan=3 width=90><span style='padding:3px 10px;' class='ui-state-error'>Sous vendu</span>" . $textExtra . "</span></table>";
    }
}

print "</table>";
print "<td valign=top>";
print "<table width=300 cellpadding=10>";
print "<tr><th class='ui-widget-header ui-state-hover' colspan=2>Par type intervention (Pr&eacute;vu)";

//table avec le dispatch
$requete = "SELECT SUM(dt.total_ht) as tht, t.label
              FROM llx_Synopsis_demandeInterv as d,llx_Synopsis_demandeIntervdet as dt,llx_Synopsis_fichinter_c_typeInterv as t
             WHERE d.fk_commande  IN (" . join(',', $arrGrpCom) . ") " .
        " AND t.id = dt.fk_typeinterv
               AND d.rowid = dt.fk_demandeInterv
          GROUP BY dt.fk_typeinterv";
//print $requete;
$sql = $db->query($requete);
while ($res = $db->fetch_object($sql)) {
    if ($res->tht > 0)
        print "<tr><th class='ui-widget-header ui-state-default' width=150>" . $res->label . "<td class='ui-widget-content' align=right>" . price($res->tht) . ' &euro;';
}
print "</table>";
print "<td valign=top>";
print "<table width=300 cellpadding=10>";
print "<tr><th class='ui-widget-header ui-state-hover' colspan=2>Par type intervention (R&eacute;alis&eacute;)";

//table avec le dispatch
$requete = "SELECT SUM(dt.total_ht) as tht, t.label
              FROM " . MAIN_DB_PREFIX . "fichinter as d," . MAIN_DB_PREFIX . "fichinterdet as dt,llx_Synopsis_fichinter_c_typeInterv as t
             WHERE d.fk_commande  IN (" . join(',', $arrGrpCom) . ") " .
        " AND t.id = dt.fk_typeinterv
               AND d.rowid = dt.fk_fichinter
          GROUP BY dt.fk_typeinterv";
$sql = $db->query($requete);
while ($res = $db->fetch_object($sql)) {
    if ($res->tht > 0)
        print "<tr><th class='ui-widget-header ui-state-default' width=150>" . $res->label . "<td class='ui-widget-content' align=right>" . price($res->tht) . ' &euro;';
}
print "</table>";
//Categorie
$requete = "SELECT l.catId, c.label FROM llx_Synopsis_PrepaCom_c_cat_listContent as l, llx_categorie as c WHERE l.catId = c.rowid";
$sqlContent = $db->query($requete);
$iter = 0;
require_once(DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php');
while ($resContent = $db->fetch_object($sqlContent)) {
    $cat = new Categorie($db, $resContent->catId);
    $catsTmp = $cat->get_filles();
    if (!sizeof($catsTmp) > 0) {
        continue;
    }
    if ($iter == 0)
        print "<tr><td valign='top' colspan=1>";
    else
        print "    <td valign='top' colspan=2>";

    print "<table width=600 cellpadding=10>";
    print "<tr><th class='ui-widget-header ui-state-hover' colspan=4>Vendu par " . strtolower($resContent->label);
    $arrCat = array();
    $arrPos = array();
    $arrLabelSort = array();
    $com->fetch_group_lines();

    foreach ($com->lines as $key => $val) {
        if ($val->fk_product > 0) {
            $requete = "SELECT ct.fk_categorie, c.label, c.position, c.rowid
                      FROM llx_categorie_product as ct
                 LEFT JOIN llx_categorie as c ON ct.fk_categorie = c.rowid
                     WHERE ct.fk_product = " . $val->fk_product . "
                       AND c.position is not null
                       AND c.rowid IN (SELECT catId FROM llx_Synopsis_PrepaCom_c_cat_listContent)
                  ORDER BY position";
            $sql = $db->query($requete);
//            print $requete."<br/>";
            if ($sql) {
                while ($res1 = $db->fetch_object($sql)) {
                    $requete = "SELECT llx_categorie.rowid,
                                       llx_categorie.position,
                                       llx_categorie.label
                                  FROM llx_categorie,
                                       llx_categorie_association
                                 WHERE fk_categorie_mere_babel = llx_categorie.rowid
                                   AND fk_categorie_fille_babel = " . $res1->fk_categorie . "
                              ORDER BY position ASC,label ASC";
                    $sql2 = $db->query($requete);
//                    print $requete."<br/>";
                    while ($res2 = $db->fetch_object($sql2)) {
                        if ($res2->rowid == $resContent->catId) {
                            $arrLabelSort[$res1->position] = $res1->label;
                            $arrCat[$res1->position]+= $val->total_ht;
                            $arrPos[$res1->rowid] = $res1->position;
                        }
                    }
                    //var_dump($arrLabelSort);
                }
            }
        }
    }
    array_multisort($arrPos);
    foreach ($arrPos as $key => $val) {
        //NOTA ATTTENTION La position doit etre mise !!!
        print "<tr><th colspan=2 class='ui-wdiget-header ui-state-default'>" . $arrLabelSort[$val] . '<td class="ui-widget-content" colspan=2 align=right >' . price($arrCat[$val]) . '&euro;';
    }

    print "</table>";


    $iter++;
    if ($iter == 2)
        $iter = 0;
}

//print "<tr><td valign=top>";
//print "<table width=600 cellpadding=10>";
//print "<tr><th class='ui-widget-header ui-state-hover' colspan=4>Vendu par famille";
//$arrCat=array();
//$arrLabelSort=array();
//$arrPos=array();
//foreach($com->lines as $key=>$val)
//{
//    if ($val->fk_product > 0){
//        $requete = "SELECT ct.fk_categorie, c.label, c.position
//                  FROM llx_categorie_product as ct
//             LEFT JOIN llx_categorie as c ON ct.fk_categorie = c.rowid
//                 WHERE  ct.fk_product = ".$val->fk_product." AND c.position is not null ORDER BY position";
//        $sql = $db->query($requete);
//        if ($sql)
//        {
//            while ($res1 = $db->fetch_object($sql))
//            {
//                $requete = "SELECT llx_categorie.label,
//                                   llx_categorie.position
//                              FROM llx_categorie,
//                                   llx_categorie_association
//                             WHERE fk_categorie_mere_babel = llx_categorie.rowid
//                               AND fk_categorie_fille_babel = ".$res1->fk_categorie."
//                          ORDER BY position ASC,label ASC";
////print $requete."<br/>";
//                $sql2 = $db->query($requete);
//                if ($db->num_rows($sql2) > 0){
//                    while($res2= $db->fetch_object($sql2))
//                    {
//                        if ($res2->label == "Famille")
//                        {
//                            $arrLabelSort[$res1->position]=$res1->label;
//                            $arrCat[$res1->label]+= $val->total_ht;
//                            $arrPos[$res1->label]=$res1->position;
//                        }
//                    }
//                }
//            }
//        }
//    }
//}
//array_multisort($arrPos);
//foreach($arrPos as $key=>$val){
////NOTA ATTTENTION La position doit etre mise !!!
//    print "<tr><th colspan=2 class='ui-wdiget-header ui-state-default'>".$arrLabelSort[$val].'<td class="ui-widget-content" colspan=2 align=right >'.price($arrCat[$arrLabelSort[$val]]).'&euro;';
//}
//print "</table>";
//
//print "<td colspan=2 valign=top>";
//print "<table width=600 cellpadding=10>";
//print "<tr><th class='ui-widget-header ui-state-hover' colspan=4>Vendu par gamme";
//$arrCat=array();
//$arrLabelSort=array();
//foreach($com->lines as $key=>$val)
//{
//    if ($val->fk_product > 0){
//        $requete = "SELECT ct.fk_categorie, c.label
//                  FROM llx_categorie_product as ct
//             LEFT JOIN llx_categorie as c ON ct.fk_categorie = c.rowid
//                 WHERE  ct.fk_product = ".$val->fk_product."";
//        $sql = $db->query($requete);
//        if ($sql)
//        {
//            while ($res1 = $db->fetch_object($sql))
//            {
//                $requete = "SELECT llx_categorie.label
//                              FROM llx_categorie,
//                                   llx_categorie_association
//                             WHERE fk_categorie_mere_babel = llx_categorie.rowid
//                               AND fk_categorie_fille_babel = ".$res1->fk_categorie;
//                $sql2 = $db->query($requete);
//                while($res2= $db->fetch_object($sql2))
//                {
//                    if ($res2->label == "Gamme")
//                    {
//                        $arrLabelSort[$res1->label]=$res1->label;
//                        $arrCat[$res1->label]+= $val->total_ht;
//                    }
//                }
//            }
//        }
//    }
//}
//array_multisort($arrLabelSort,SORT_ASC,SORT_STRING);
//foreach($arrLabelSort as $key=>$val){
//    print "<tr><th colspan=2 class='ui-wdiget-header ui-state-default'>".$val.'<td class="ui-widget-content" align=right colspan=2 >'.price($arrCat[$val]).'&euro;';
//}
//print "</table>";
//print "<td>";
print "</table>";
*/
print "</div>";
print "<br/>";

//function convDur($duration) {
//
//    // Initialisation
//    $duration = abs($duration);
//    $converted_duration = array();
//
//    // Conversion en semaines
//    $converted_duration['weeks']['abs'] = floor($duration / (60 * 60 * 24 * 7));
//    $modulus = $duration % (60 * 60 * 24 * 7);
//
//    // Conversion en jours
//    $converted_duration['days']['abs'] = floor($duration / (60 * 60 * 24));
//    $converted_duration['days']['rel'] = floor($modulus / (60 * 60 * 24));
//    $modulus = $modulus % (60 * 60 * 24);
//
//    // Conversion en heures
//    $converted_duration['hours']['abs'] = floor($duration / (60 * 60));
//    $converted_duration['hours']['rel'] = floor($modulus / (60 * 60));
//    $modulus = $modulus % (60 * 60);
//
//    // Conversion en minutes
//    $converted_duration['minutes']['abs'] = floor($duration / 60);
//    $converted_duration['minutes']['rel'] = floor($modulus / 60);
//    if ($converted_duration['minutes']['rel'] < 10) {
//        $converted_duration['minutes']['rel'] = "0" . $converted_duration['minutes']['rel'];
//    };
//    $modulus = $modulus % 60;
//
//    // Conversion en secondes
//    $converted_duration['seconds']['abs'] = $duration;
//    $converted_duration['seconds']['rel'] = $modulus;
//
//    // Affichage
//    return( $converted_duration);
//}

llxFooter();
?>
