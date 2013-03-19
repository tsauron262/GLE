<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 28 sept. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : interventions-html_response.php
 * GLE-1.2
 */
require_once('../../main.inc.php');

$id = $_REQUEST['id'];
require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/Synopsis_DemandeInterv/demandeInterv.class.php");

$form = new Form($db);

$com = new Synopsis_Commande($db);
$html = new Form($db);
$res = $com->fetch($id);

$arrGrpCom = array($id => $id);
$arrGrp = $com->listGroupMember(true);
if ($arrGrp && count($arrGrp) > 0)
    foreach ($arrGrp as $key => $commandeMember) {
        $arrGrpCom[$commandeMember->id] = $commandeMember->id;
    }
$requete = "SELECT *
                FROM " . MAIN_DB_PREFIX . "Synopsis_demandeIntervdet
               WHERE fk_commandedet IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "commandedet WHERE fk_commande IN (" . join(",", $arrGrpCom) . " ))";
$sql = $db->query($requete);
$arrDi = array();
while ($res1 = $db->fetch_object($sql)) {
    $arrDi[$res1->rowid] = $res1->fk_commandedet;
}

print "<table><tr><td valign=top>";
if ($res > 0) {
    $com->fetch_group_lines(0, 0, 0, 0, 1);
//    function fetch_lines($only_product=0,$only_service=0,$only_contrat=0,$only_dep=0,$srv_dep=0)

    $cnt = 0;
    if (count($com->lines) > 0) {

        $prod = new Product($db);
        print "<div class='revertDraggable'>";
        foreach ($com->lines as $key => $val) {
            $val->id = $val->rowid;
            if (count($arrDi) > 0 && in_array($val->id, $arrDi)) {
//                continue;
            }
            if (isset($val->fk_product) && $val->fk_product > 0) {
                $prod->fetch($val->fk_product);
                if ($prod->type == 1 || $prod->type == 3) {
                    if ($cnt == 0) {
                        print "<table  cellpadding=10>";
                        print "<tr><th width=95 valign=middle style='line-height:35px; font-size: 12pt; font-weight:100;' class='ui-widget-header ui-state-default'>Ref.
                           <th width=95 valign=middle style='line-height:35px; font-size: 12pt; font-weight:100;' class='ui-widget-header ui-state-default'>Vendu HT
                           <th width=200 valign=middle style='line-height:35px; font-size: 12pt; font-weight:100;' class='ui-widget-header ui-state-default'>Description";
                        print "</table>";
                    }
                    $cnt++;
                    print "<table id='" . $val->id . "'  cellpadding=10 class='draggable  ui-widget-content ui-draggable '>";

                    $durStr = $prod->duration_value . '&nbsp;';
                    if ($prod->duration_value > 1) {
                        $dur = array("h" => $langs->trans("Hours"), "d" => $langs->trans("Days"), "w" => $langs->trans("Weeks"), "m" => $langs->trans("Months"), "y" => $langs->trans("Years"));
                    } else {
                        $dur = array("h" => $langs->trans("Hour"), "d" => $langs->trans("Day"), "w" => $langs->trans("Week"), "m" => $langs->trans("Month"), "y" => $langs->trans("Year"));
                    }
                    $durStr.= $langs->trans($dur[$prod->duration_unit]) . "&nbsp;";

                    print "<tr>";
                    print "    <td nowrap width=95 align=center class='ui-widget-content'>" . $prod->getNomUrl(1);
                    print "    <td width=95 align=center class='ui-widget-content'>" . price($val->total_ht) . " &euro;";
                    print "    <td width=200 class='ui-widget-content'>" . $val->desc;
                    print "</table>";
                }
            }
        }
        print "</div>";
        print '</td>';
        //print "</table>";
    } else {
        print " Pas de services dans la commande";
        exit;
    }
}
if ($cnt > 0) {
    print "<td valign=top align=center>";
    print "<table width=350 cellpadding=10>";
    print "<tr><th width=250 class='ui-widget-header ui-state-default' valign=middle style='line-height:35px; font-size: 12pt; font-weight:100;'>Nouvelle DI.
               <th width=100 class='ui-widget-header ui-state-default' valign=middle style='line-height:35px; font-size: 12pt; font-weight:100;'><button class='butAction' id='newDi'>Cr&eacute;er</button>";
    print "</table>";
    print "<form action='#'><div class='droppable ui-widget-header ui-droppable' style='width:348px; min-height:9em;'></div></form>";
    print "";
    print "</table>";
    print "<hr>";
}
print "<div class='titre'>Interventions attribu&eacute;es</div>";
$requete = "SELECT *
                  FROM " . MAIN_DB_PREFIX . "Synopsis_demandeInterv
                 WHERE fk_commande IN (" . join(",", $arrGrpCom) . ")";
$sql = $db->query($requete);
print "<table cellpadding=15 width=100%>";
print "<tr><th class='ui-widget-header ui-state-default'>Ref<th class='ui-widget-header ui-state-default'>Intervenant<th class='ui-widget-header ui-state-default'>Date d&eacute;but<th class='ui-widget-header ui-state-default'>Dur&eacute;e<th class='ui-widget-header ui-state-default'>Total HT<th class='ui-widget-header ui-state-default'>Contenu<th class='ui-widget-header ui-state-default'>Action";
$tmpUser = new User($db);
$tmpProd = new Product($db);
while ($res = $db->fetch_object($sql)) {
    $di = new DemandeInterv($db);
    $di->fetch($res->rowid);
    print "<tr class='droppable2' rel='" . $res->fk_user_prisencharge . "' id='" . $res->rowid . "'><td align=left class='ui-widget-content'  nowrap>" . $di->getNomUrl(1) . "";
    if ($res->fk_user_prisencharge > 0) {
        $tmpUser->fetch($res->fk_user_prisencharge);
    }
    print "    <td class='ui-widget-content' align=left>" . ($res->fk_user_prisencharge > 0 ? $tmpUser->getNomUrl(1) : "");
    print "    <td class='ui-widget-content' align=center>" . (strtotime($res->datei) > 0 ? date('d/m/Y', strtotime($res->datei)) : "");
    print "    <td class='ui-widget-content' align=center>" . ConvertSecondToTime($res->duree);
    print "    <td nowrap class='ui-widget-content' align=right>" . price($res->total_ht) . " &euro;";
    print "    <td class='ui-widget-content' align=center>";
    $requete = " SELECT c.*
                       FROM " . MAIN_DB_PREFIX . "commandedet c,
                           " . MAIN_DB_PREFIX . "Synopsis_demandeIntervdet d
                      WHERE c.rowid = d.fk_commandedet AND d.fk_demandeInterv = " . $res->rowid;
    $sql1 = $db->query($requete);
    if ($db->num_rows($sql1) > 0) {
        print "<table width=100%>";
        while ($res1 = $db->fetch_object($sql1)) {
            $tmpProd->fetch($res1->fk_product);
            print "<tr><td class='ui-widget-content' nowrap>" . $tmpProd->getNomUrl(1);
            print "    <td class='ui-widget-content'>" . $res1->description;
            //print "    <td class='ui-widget-content' align=right nowrap>".price($res1->total_ht);
        }
        print "</table>";
    }
    print "    <td class='ui-widget-content' align=center>";
    if ($user->rights->synopsisdemandeinterv->supprimer)
        print "    <button class='butAction' onClick='delDi(" . $res->rowid . ")'>Supprimer</button>";
    if ($user->rights->synopsisdemandeinterv->creer)
        print "    <button class='butAction' onClick='cloneDi(" . $res->rowid . ")'>Cloner</button>";
    if ($res->fk_statut == 0 && $user->rights->synopsisdemandeinterv->creer)
        print "<button class='butAction' onClick='validDi(" . $res->rowid . ")'>Valider</button>";
}
print "</table>";
print "<br/>";
print "<tr><td align=right><button id='addDI'  class='butAction'>Demande manuelle</button>";

//print "<table>";

print <<<EOF
<script>
jQuery(document).ready(function(){
    jQuery('#addDI').click(function(){
        location.href=DOL_URL_ROOT+"/Synopsis_DemandeInterv/fiche.php?action=create&socid="+socId+"&fk_commande="+comId;
    });

});

</script>
<style>
.ui-datepicker { z-index:20000; }
tr.ui-state-hover td { background:url("images/ui-bg_highlight-soft_25_0073ea_1x100.png") repeat-x scroll 50% 50% #0073EA;}
</style>
EOF;
print "<div id='createDIDialog' class='cntDIDial'>";
print "<form>";
print "<div id='tabsDialog'>";
print "<ul><li><a href='#fragment1'>Interventions</a></li><li><a href='#fragment2'>D&eacute;tails</a></li><li><a href='#fragment3'>Plus</a></li></ul>";
print "<div id='fragment1'>";
print "<table cellpadding=10 width=100%><tr><th class='ui-widget-header ui-state-default'>Date Intervention</td>";
print "<td class='ui-widget-content' colspan=1>";
print $form->select_date($com->date_livraison,"datei");
print '<input type="button" value="Répliqué" id="repliDate"/>';

print "<th class='ui-widget-header ui-state-default'>Intervenant</th>";
print "<td class='ui-widget-content' colspan=1>";
print select_dolusersInGroup($form, 3, '', 'userid', 1, array(1 => 1), 0, false);
//print $html->tmpReturn;

print "<tr><th class='ui-widget-header ui-state-default'>Description globale</th>";
print "<td colspan=3 class='ui-widget-content'><textarea style='width:100%' name='desc' id='desc'></textarea>";

print "</table>";
print "</div>";
print "<div id='fragment2'>";
print "<div id='toReplace'>Chargement en cours</div>";
print "</div>";

print "<div id='fragment3'>";
print "<table cellpadding=10 width=100%>";
$requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_fichinter_extra_key
                     WHERE (isQuality is NULL OR isQuality <> 1)
                       AND isInMainPanel = 1
                       AND active = 1
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
        print '<tr class="elemSup">';
    if ($res->fullLine == 1)
        $modulo = !$modulo;
    print "<th valign='top' class='ui-widget-header ui-state-default'>" . $res->label;
    switch ($res->type) {
        case "date": {
                print "<td colspan=" . $colspan . " valign='middle' class='ui-widget-content'><input type='text' name='extraKey-" . $res->id . "' class='datePicker'>";
                print "<input type='hidden' name='type-" . $res->id . "' value='date'>";
            }
            break;
        case "textarea": {
                print "<td colspan=" . $colspan . " valign='middle' class='ui-widget-content'><textarea style='width:80%' name='extraKey-" . $res->id . "'></textarea>";
                print "<input type='hidden' name='type-" . $res->id . "' value='comment'>";
            }
            break;
        default:
        case "text": {
                print "<td colspan=" . $colspan . " valign='middle' class='ui-widget-content'><input type='text' name='extraKey-" . $res->id . "'>";
                print "<input type='hidden' name='type-" . $res->id . "' value='text'>";
            }
            break;
        case "datetime": {
                print "<td colspan=" . $colspan . " valign='middle' class='ui-widget-content'><input type='text' name='extraKey-" . $res->id . "' class='dateTimePicker'>";
                print "<input type='hidden' name='type-" . $res->id . "' value='datetime'>";
            }
            break;
        case "checkbox": {
                print "<td colspan=" . $colspan . " valign='middle' class='ui-widget-content'><input type='checkbox'  name='extraKey-" . $res->id . "'>";
                print "<input type='hidden' name='type-" . $res->id . "' value='checkbox'>";
            }
            break;
        case "radio": {
                print "<td colspan=2 valign='middle' class='ui-widget-content'>";
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_fichinter_extra_values_choice WHERE key_refid = " . $res->id;
                $sql1 = $db->query($requete);
                if ($db->num_rows($sql1) > 0) {
                    print "<table width=100%>";
                    while ($res1 = $db->fetch_object($sql1)) {
                        print "<tr><td width=100%>" . $res1->label . "<td>";
                        print "<input type='radio' value='" . $res1->value . "' name='extraKey-" . $res->id . "'>";
                    }
                    print "</table>";
                }
                print "<input type='hidden' name='type-" . $res->id . "' value='radio'></td>";
            }
            break;
    }
}
print "</table>";
print "</div>";

print "</div>";
print "<div id='errorMsg'></div>";
print "</form>";
print "</div>";

print "<div id='modDIDialog' class='cntmodDIDial'>";
print "<form>";
print "<div id='toReplace2'>Chargement en cours</div>";
print "<div id='errorMsg2'></div>";
print "</form>";
print "</div>";


print <<<EOF
<script>
var DnDArray = new Array();
var DnDArray2 = new Array();
jQuery(document).ready(function(){
        $("#repliDate").click(function(){
            $("#toReplace .datePicker").val($("#datei").val());
        });
        
        function initSynScript(){
            $("#toReplace tr").each(function(){
                id = $(this).attr("id");
                $(this).find("#qte"+id).val(1);
                desc = $('#desc');
                forfait = $(this).find("#isForfait"+id);
                i=0;
                $(this).find("a").each(function(){
                    i++;
                    if(i == 2){
                        if($(this).html() == "FPR50")
                            desc.html("Installation comprennent : ");
                        if($(this).html() == "FPR30")
                            desc.html("Intervention comprennent : ");
                        if($(this).html().match("FD.*")){
//                            desc.html("Déplacement comprennent : ");
                            forfait.attr('checked', true);
                        }
                    }
                });
            });
        }


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

    if (jQuery('.cntDIDial').length>1){
//        jQuery('#createDIDialog').dialog( "destroy" );
        jQuery('#createDIDialog').remove();
    }
    if (jQuery('.cntmodDIDial').length>1){
//        jQuery('#modDIDialog').dialog( "destroy" );
        jQuery('#modDIDialog').remove();
    }
    jQuery.validator.addMethod(
                "FRDate",
                function(value, element) {
                    // put your own logic here, this is just a (crappy) example
                    return value.match(/^\d\d?\/\d\d?\/\d\d\d\d[\W\d\d\:\d\d]?$/);
                },
                "La date doit &eacute;tre au format dd/mm/yyyy hh:mm"
            );
    jQuery.validator.addMethod(
                "sup0",
                function(value, element) {
                    // put your own logic here, this is just a (crappy) example
                    return value > 0;
                },
                "Ce champs est requis"
            );


    jQuery('#modDIDialog').dialog({
            modal: true,
            autoOpen: false,
            title: "Ajout dans une demande d'intervention",
            minWidth: 940,
            width: 940,
            buttons: {
                OK: function(){
                    if(jQuery('#modDIDialog form').validate({
                        errorPlacement: function(error, element) {
                            if ("x"+element.parent().parent().find('th').text() == "x"){
                                error.html("&nbsp;"+element.attr('rel')+" (Détails)");
                                jQuery('<br/><span style="padding:10px">&nbsp;</span>').prependTo(error.appendTo( jQuery('#errorMsg2')) );
                            } else {
                                jQuery('<br/><span style="padding:10px">&nbsp;</span>').prependTo(error.appendTo( jQuery('#errorMsg2')) );
                            }
                        },
                    }).form()){
                        var data = jQuery('#modDIDialog').find('form').serialize();
                        jQuery.ajax({
                            url:"ajax/xml/modDI-xml_response.php",
                            data:"id="+comId+"&"+data+"&diId="+DnDArray2["di"],
                            datatype:"xml",
                            type:"POST",
                            cache:false,
                            success:function(msg){
                                if (jQuery(msg).find('OK').length > 0)
                                {
                                    jQuery('#modDIDialog').dialog('close');
                                    //reload
                                    reloadResult();

                                    jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
                                    jQuery('#modDIDialog').dialog( "destroy" );
                                    jQuery('#modDIDialog').remove();
                                    jQuery.ajax({
                                        url: "ajax/interventions-html_response.php",
                                        data: "id="+comId,
                                        cache: false,
                                        datatype: "html",
                                        type: "POST",
                                        success: function(msg){
                                            jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
                                        },
                                    });
                                } else {
                                    alert('Il y a eu une erreur');
                                }
                            }
                        });
                    }
                },
                Annuler: function(){
                    jQuery(this).dialog('close');
                }
            },
            open: function()
            {
//              DnDArray2["ligne"]=ligneId;
//              DnDArray2["di"]=diId;
                var data = "&val0="+DnDArray2["ligne"];
                jQuery.ajax({
                    url:"ajax/xml/getLignesDetails-xml_response.php",
                    type:"POST",
                    datatype:"xml",
                    data:"id="+comId+data,
                    cache: true,
                    success: function(msg){
                        longHtml = "<table cellpadding=10 width=100%>";
                        longHtml += "<tr><th class='ui-state-default ui-widget-header'>Produit";
                        longHtml += "    <th class='ui-state-default ui-widget-header'>Description";
                        longHtml += "    <th class='ui-state-default ui-widget-header'>Date";
                        longHtml += "    <th class='ui-state-default ui-widget-header'>Dur&eacute;e";
                        longHtml += "    <th class='ui-state-default ui-widget-header'>Forfait";
                        longHtml += "    <th class='ui-state-default ui-widget-header'>Type";
                        longHtml += "    <th width=50  class='ui-state-default ui-widget-header'>PU HT";
                        longHtml += "    <th width=32 class='ui-state-default ui-widget-header'>Qte";

                        var i=0;
                        jQuery(msg).find('comLigne').each(function(){
                            var ligneId = jQuery(this).find("id").text();
                            i++;
                            longHtml+= "<tr id='"+ligneId+"' rel='"+jQuery(this).find('fk_product').text()+"'><td class='ui-widget-content' nowrap align=center>"+jQuery(this).find("product").text();
                            longHtml+= "    <td class='ui-widget-content' align=center><textarea  class='required' rel='La description de la ligne "+i+" est manquante' name='desci"+ligneId+"' id='desci"+ligneId+"' ></textarea>";
                            longHtml+= "    <td class='ui-widget-content' align=center><input rel='La date de la ligne "+i+" est manquante' size=8 class='datePicker required FRDate' type='text' name='datei"+ligneId+"' id='datei"+ligneId+"'  >";
                            longHtml+= "    <td class='ui-widget-content' align=center>"+printSelDur(ligneId);
                            longHtml+= "    <td class='ui-widget-content' align=center><input type='checkbox' "+(jQuery(this).find("forfait").text()==1?'Checked':"")+" name='isForfait"+ligneId+"' id='isForfait"+ligneId+"'  >";;
                            longHtml+= "    <td class='ui-widget-content' align=center>"+printSelTypeInterv(ligneId,jQuery(this).find("typeproduct").text());
                            longHtml+= "    <td class='ui-widget-content' align=center><input size=8  type='text' rel='Le prix unitaire HT de la ligne "+i+" est manquant' class='required' value='"+jQuery(this).find("pu_ht").text()+"' name='pu_ht"+ligneId+"' id='pu_ht"+ligneId+"'  >";
                            longHtml+= "    <span class='DfltPriceMod' id='DfltPrice"+ligneId+"'></span>";
                            longHtml+= "    <td class='ui-widget-content' align=center><input size=4 type='text' value='"+jQuery(this).find("qte").text()+"' name='qte"+ligneId+"' id='qte"+ligneId+"'  >";
                        });
                        longHtml += "</table>";
                        jQuery('#toReplace2').replaceWith('<div id="toReplace2">'+longHtml+'</div>');
                        jQuery('.datePicker').datepicker();
                        reinitAutoPriceMod();
                        getUserIdMod();
//                        jQuery('#toReplace select').selectmenu({style: 'dropdown', maxHeight: 300 });


                    }
                });
                jQuery('.datePicker').datepicker();
                jQuery('.dateTimePicker').datepicker({showTime:true});
            },
    });


    jQuery('#createDIDialog').dialog({
            modal: true,
            autoOpen: false,
            title: "Nouvelle demande d'intervention",
            minWidth: 940,
            width: 940,
            buttons: {
                OK: function(){
                    if(jQuery('#createDIDialog form').validate({
                        errorPlacement: function(error, element) {
                            if ("x"+element.parent().parent().find('th').text() == "x"){
                                error.html("&nbsp;"+element.attr('rel')+" (Détails)");
                                jQuery('<br/><span style="padding:10px">&nbsp;</span>').prependTo(error.appendTo( jQuery('#errorMsg')) );
                            } else {
                                jQuery('<br/><span style="padding:10px">&nbsp;</span>').prependTo(error.appendTo( jQuery('#errorMsg')) );
                            }
                        },
                        rules: {
                            datei: {
                                required: true,
                                FRDate: true
                            },
                            userid: {
                                required: true,
                                sup0: true,
                            },
                            desc: {
                                required: true,
                            }
                        },
                        messages: {
                            datei: {
                                required: " La date d'intervention n'a pas été remplis (Interventions)",
                                FRDate: " La date d'intervention n'a pas été remplis (Interventions)"
                            },
                            userid:{
                                required: "  L'intervenant n'a pas été selectionné (Interventions)",
                                sup0: "  L'intervenant n'a pas été selectionné (Interventions)",
                            },
                            desc: {
                                required: " La description est requise (Interventions)",
                            }
                        }
                    }).form()){
                        var data = jQuery('#createDIDialog').find('form').serialize();
                        jQuery.ajax({
                            url:"ajax/xml/createDI-xml_response.php",
                            data:"id="+comId+"&"+data,
                            datatype:"xml",
                            type:"POST",
                            cache:false,
                            success:function(msg){
                                if (jQuery(msg).find('OK').length > 0)
                                {
                                    jQuery('#createDIDialog').dialog('close');
                                    //reload
                                    reloadResult();
                                    jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
                                    jQuery('#createDIDialog').dialog( "destroy" );
                                    jQuery('#createDIDialog').remove();
                                    jQuery.ajax({
                                        url: "ajax/interventions-html_response.php",
                                        data: "id="+comId,
                                        cache: false,
                                        datatype: "html",
                                        type: "POST",
                                        success: function(msg){
                                            jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
                                        },
                                    });
                                } else {
                                    alert('Il y a eu une erreur');
                                }
                            }
                        });
                    }
                },
                Annuler: function(){
                    jQuery(this).dialog('close');
                }
            },
            open: function()
            {
                var data = "";
                for(var i in DnDArray){
                    data += "&val"+i+"="+DnDArray[i]
                }
                jQuery.ajax({
                    url:"ajax/xml/getLignesDetails-xml_response.php",
                    type:"POST",
                    datatype:"xml",
                    data:"id="+comId+data,
                    cache: true,
                    success: function(msg){
                        longHtml = "<table cellpadding=10 width=100%>";
                        longHtml += "<tr><th class='ui-state-default ui-widget-header'>Produit";
                        longHtml += "    <th class='ui-state-default ui-widget-header'>Description";
                        longHtml += "    <th class='ui-state-default ui-widget-header'>Date";
                        longHtml += "    <th class='ui-state-default ui-widget-header'>Dur&eacute;e";
                        longHtml += "    <th class='ui-state-default ui-widget-header'>Forfait";
                        longHtml += "    <th class='ui-state-default ui-widget-header'>Type";
                        longHtml += "    <th width=50  class='ui-state-default ui-widget-header'>PU HT";
                        longHtml += "    <th width=32 class='ui-state-default ui-widget-header'>Qte";

                        var i=0;
                        jQuery(msg).find('comLigne').each(function(){
                            var ligneId = jQuery(this).find("id").text();
                            i++;
                            longHtml+= "<tr id='"+ligneId+"' rel='"+jQuery(this).find('fk_product').text()+"'><td class='ui-widget-content' nowrap align=center>"+jQuery(this).find("product").text();
                            longHtml+= "    <td class='ui-widget-content' align=center><textarea  class='required' rel='La description de la ligne "+i+" est manquante' name='desci"+ligneId+"' id='desci"+ligneId+"' >"+jQuery(this).find("description").text()+"</textarea>";
                            longHtml+= "    <td class='ui-widget-content' align=center><input rel='La date de la ligne "+i+" est manquante' size=8 class='datePicker required FRDate' type='text' name='datei"+ligneId+"' id='datei"+ligneId+"'  >";
                            longHtml+= "    <td class='ui-widget-content' align=center>"+printSelDur(ligneId);
                            longHtml+= "    <td class='ui-widget-content' align=center><input type='checkbox' "+(jQuery(this).find("forfait").text()==1?'Checked':"")+" name='isForfait"+ligneId+"' id='isForfait"+ligneId+"'  >";;
                            longHtml+= "    <td class='ui-widget-content' align=center>"+printSelTypeInterv(ligneId,jQuery(this).find("typeproduct").text());
                            longHtml+= "    <td class='ui-widget-content' align=center><input size=8  type='text' rel='Le prix unitaire HT de la ligne "+i+" est manquant' class='required' value='"+jQuery(this).find("pu_ht").text()+"' name='pu_ht"+ligneId+"' id='pu_ht"+ligneId+"'  >";
                            longHtml+= "    <span class='DfltPrice' id='DfltPrice"+ligneId+"'></span>";
                            longHtml+= "    <td class='ui-widget-content' align=center><input size=4 type='text' value='"+jQuery(this).find("qte").text()+"' name='qte"+ligneId+"' id='qte"+ligneId+"'  >";
                        });
                        longHtml += "</table>";
                        jQuery('#toReplace').replaceWith('<div id="toReplace">'+longHtml+'</div>');
                        jQuery('.datePicker').datepicker();
                        reinitAutoPrice();
                        initSynScript();
//                        jQuery('#toReplace select').selectmenu({style: 'dropdown', maxHeight: 300 });


                    }
                });
                jQuery('#tabsDialog').tabs({
                    cache: true,
                    fx: { opacity: 'toggle' },
                    spinner:"Chargement ..."
                });
                jQuery('.datePicker').datepicker();
                jQuery('.dateTimePicker').datepicker({showTime:true});
            },
    });

    jQuery('#newDi').click(function(){

        if (DnDArray.length > 0)
        {
            //Dialog + formulaire
            jQuery('#createDIDialog').dialog('open');
        } else {
            alert('Aucun element');
        }
    });
    jQuery( ".draggable" ).draggable({
                                    revert: "invalid" ,
                                    containment: "#resDisp",
                                    distance: 20,
                                    grid: [ 20,20 ],
                                    scrollSensitivity: 100,
                                    opacity: 0.7,
                                    cursor:"crosshair",
                                    top:-5,
                                    left:-5,
                                    scroll: true
                                    });
        jQuery( ".droppable" ).droppable({
            hoverClass: "ui-state-active",
            activeClass: "ui-state-hover",
            drop: function( event, ui ) {
                    DnDArray.push(ui.draggable.attr('id'));
            }
        });
        jQuery('.revertDraggable').droppable({
            hoverClass: "ui-state-active",
            activeClass: "ui-state-hover",
            drop: function( event, ui ) {
                    var tmp = new Array();
                    for(var i in DnDArray)
                    {
                        if (DnDArray[i]!=ui.draggable.attr('id')){
                            tmp.push(DnDArray[i]) ;
                        }
                    }
                    DnDArray = tmp;
            }
        });
        jQuery('.droppable2').droppable({
            hoverClass: "ui-state-active",
            activeClass: "ui-state-hover",
            drop: function( event, ui ) {
                    var ligneId = ui.draggable.attr('id')
                    var diId = jQuery(event.target).attr('id');
                    var userId = jQuery(event.target).attr('rel');
//                    console.log(ui);
//                    console.log(ligneid);
//                    console.log(diId);

                    //Ouvre dialog
                    DnDArray2= new Array();
                    DnDArray2["ligne"]=ligneId;
                    DnDArray2["di"]=diId;
                    DnDArray2["userId"]=userId;
                    jQuery('#modDIDialog').dialog('open');
            }
        });
});


//
//function createDI(pId){
//    location.href=DOL_URL_ROOT+"/Synopsis_DemandeInterv/fiche.php?action=create&comLigneId="+pId;
//}
function reinitAutoPrice()
{
    jQuery('#createDIDialog #userid').change(function(){
        getUserId();
    });
    jQuery('#createDIDialog .typeInterv').change(function(){
        getUserId();
    });
}
function reinitAutoPriceMod()
{
    //DnDArray2["userId"]
    jQuery('#modDIDialog .typeInterv').change(function(){
        getUserIdMod();
    });
}
function getUserIdMod()
{
        //Recupere la liste des prix de l'utilisateur
        //ajax
        jQuery.ajax({
            url:'ajax/xml/getPriceInterv-xml_response.php',
            data:'userId='+DnDArray2["userId"],
            datatype:"xml",
            type:"POST",
            cache: false,
            success: function(msg){
                var arr = new Array();
                jQuery(msg).find('typeInterv').each(function(){
                    var id = jQuery(this).attr('id');
                    var prix = jQuery(this).text();
                    arr[id]=prix;
                });
                var arrDep = new Array();
                jQuery(msg).find('dep').each(function(){
                    var id = jQuery(this).attr('id');
                    var prix = jQuery(this).text();
                    arrDep[id]=prix;
                });
                jQuery(".DfltPriceMod").each(function(){
                    var id = jQuery(this).attr('id');
                    var numId = id.replace(/[^0-9]*/,"");
                    var typeInterv = jQuery('#typeInterv'+numId).find(':selected').val();
                    if (typeInterv == 4){
                        var tmpId = jQuery('#modDIDialog').find('#'+numId).attr('rel');
                        jQuery(this).html("<br/>"+arrDep[tmpId]);
                    } else{
                        jQuery(this).html("<br/>"+arr[typeInterv]);
                    }

                });
            }
        })
}


function getUserId()
{
//Si userId
    var userId = false;
    if (jQuery("#userid").find(':selected').val()>0)
    {
        userId = jQuery("#userid").find(':selected').val();
        //Recupere la liste des prix de l'utilisateur
        //ajax
        jQuery.ajax({
            url:'ajax/xml/getPriceInterv-xml_response.php',
            data:'userId='+userId,
            datatype:"xml",
            type:"POST",
            cache: false,
            success: function(msg){
                var arr = new Array();
                jQuery(msg).find('typeInterv').each(function(){
                    var id = jQuery(this).attr('id');
                    var prix = jQuery(this).text();
                    arr[id]=prix;
                });
                var arrDep = new Array();
                jQuery(msg).find('dep').each(function(){
                    var id = jQuery(this).attr('id');
                    var prix = jQuery(this).text();
                    arrDep[id]=prix;
                });
                jQuery(".DfltPrice").each(function(){
                    var id = jQuery(this).attr('id');
                    var numId = id.replace(/[^0-9]*/,"");
                    var typeInterv = jQuery('#typeInterv'+numId).find(':selected').val();
                    if (typeInterv == 4){
                        var tmpId = jQuery('#createDIDialog').find('#'+numId).attr('rel');
                        jQuery(this).html("<br/>"+arrDep[tmpId]);
                    } else{
                        jQuery(this).html("<br/>"+arr[typeInterv]);
                    }

                });
            }
        })
    } else {
        //recherche les type d'interv
        //mets le prix dans la liste
        jQuery(".DfltPrice").text("");
    }
}
function printSelDur(id){
    var html = "<table width=100%><tr><td><select name='duri"+id+"' id='duri"+id+"'>";
    for(var i=0;i<24;i++)
    {
        html += "<option value='"+i+"'>"+(i<10?"0"+i:i)+"</option>";
    }
    html += "</select>";
    html += "<td><select name='durmini"+id+"' id='durmini"+id+"'>";
    for(var i=0;i<60;i+=5)
    {
        html += "<option value='"+i+"'>"+(i<10?"0"+i:i)+"</option>";
    }
    html += "</select>";
    html += "</table>";
    return(html);
}
function printSelTypeInterv(id,selected){
    var objclone =jQuery('#templateTypeInterv').parent().clone(1);
    objclone.find('select').attr('id','typeInterv'+id);
    objclone.find('select').attr('name','typeInterv'+id);
    if(selected==3){
        objclone.find('option').each(function(){
            jQuery(this).removeAttr("selected");
        });
        objclone.find("select option[value='4']").attr("SELECTED","selected");
    }

    return objclone.html();
}
function delDi(id){
    jQuery.ajax({
        url:'ajax/xml/delDI-xml_response.php',
        data:"id="+id,
        datatype:"xml",
        type:"POST",
        cache:false,
        success:function(msg){
            if(jQuery(msg).find('OK').length>0)
            {
                jQuery('#createDIDialog').dialog('close');
                //reload
                reloadResult();
                jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
                jQuery('#createDIDialog').dialog( "destroy" );
                jQuery('#createDIDialog').remove();
                jQuery.ajax({
                    url: "ajax/interventions-html_response.php",
                    data: "id="+comId,
                    cache: false,
                    datatype: "html",
                    type: "POST",
                    success: function(msg){
                        jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
                    },
                });
            } else {
                alert('Il y a eu une erreur');
            }
        }
    });
}
function cloneDi(id){
    jQuery.ajax({
        url:'ajax/xml/cloneDI-xml_response.php',
        data:"id="+id,
        datatype:"xml",
        type:"POST",
        cache:false,
        success:function(msg){
            if(jQuery(msg).find('OK').length>0)
            {
                jQuery('#createDIDialog').dialog('close');
                //reload
                reloadResult();
                jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
                jQuery('#createDIDialog').dialog( "destroy" );
                jQuery('#createDIDialog').remove();
                jQuery.ajax({
                    url: "ajax/interventions-html_response.php",
                    data: "id="+comId,
                    cache: false,
                    datatype: "html",
                    type: "POST",
                    success: function(msg){
                        jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
                    },
                });
            } else {
                alert('Il y a eu une erreur');
            }
        }
    });
}

function validDi(id){
    jQuery.ajax({
        url:'ajax/xml/validDI-xml_response.php',
        data:"id="+id,
        datatype:"xml",
        type:"POST",
        cache:false,
        success:function(msg){
            if(jQuery(msg).find('OK').length>0)
            {
                jQuery('#createDIDialog').dialog('close');
                //reload
                reloadResult();
                jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
                jQuery('#createDIDialog').dialog( "destroy" );
                jQuery('#createDIDialog').remove();
                jQuery.ajax({
                    url: "ajax/interventions-html_response.php",
                    data: "id="+comId,
                    cache: false,
                    datatype: "html",
                    type: "POST",
                    success: function(msg){
                        jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
                    },
                });
            } else {
                alert('Il y a eu une erreur');
            }
        }
    });
}
</script>

EOF;

print "<div style='display:none;'>";
print "<select id='templateTypeInterv' class='typeInterv' name='templateTypeInterv'>";
$requete = " SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_fichinter_c_typeInterv WHERE active = 1 ORDER BY rang";
$sql = $db->query($requete);
while ($res = $db->fetch_object($sql)) {
    if ($res->default == 1) {
        print "<option SELECTED value='" . $res->id . "'>" . htmlentities($res->label) . "</option>";
    } else {
        print "<option value='" . $res->id . "'>" . htmlentities($res->label) . "</option>";
    }
}
print "</select>";
print "</div>";
?>