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
$langs->load("synopsisGene@synopsistools");
$langs->load('deliveries');
$langs->load('products');

if ($user->societe_id)
    header("Location: " . DOL_URL_ROOT . "/commande/card.php?id=" . $_REQUEST['id']);

if (!$user->rights->commande->lire)
    accessforbidden();
if (!$user->rights->SynopsisPrepaCom->all->Afficher)
    accessforbidden();

// Securite acces client
$socid = 0;
if ($user->societe_id > 0) {
    $socid = $user->societe_id;
}
$commande = new Synopsis_Commande($db);
$commande->fetch((int) $_GET['id']);
if ($user->societe_id > 0 && isset($_GET["id"]) && $_GET["id"] > 0) {
    if ($user->societe_id != $commande->socid) {
        accessforbidden();
    }
}



if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'addTech') {
    $requete = "INSERT into " . MAIN_DB_PREFIX . "element_element (sourcetype, fk_source, targettype, fk_target) VALUES ('soc', " . $_REQUEST['socid'] . ", 'userTech', " . $_REQUEST['userid'] . ");";
    $db->query($requete);
} elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delTech') {
    $requete = "DELETE FROM " . MAIN_DB_PREFIX . "element_element WHERE sourcetype = 'soc' AND fk_source = " . $_REQUEST['socid'] . " AND targettype = 'userTech' AND fk_target = " . $_REQUEST['userid'] . ";";
    $db->query($requete);
}



$id = $_REQUEST['id'];
if (!$id > 0) {
    llxHeader($js, "Préparation de commande", 1);
    print "<div class='error ui-error'>Pas de commande s&eacute;lectionn&eacute;e";
    exit;
}

//saveHistoUser($commande->id, "prepaCom",$commande->ref);


$js = '<script>';
$js .= ' userId = ' . $user->id . ';';
$js .= ' socId = ' . $commande->socid . ';
    comId = ' . $id . ';';
$js .= ' DOL_URL_ROOT = "' . DOL_URL_ROOT . '";';
$js.=<<<EOF
    url = {
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

    suburl = {
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
        part9b: "synopsisdemandeinterv-html_response.php",
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
            }
        });
    });
    jQuery('.accordion').accordion('activate',0);

    hash = window.location.hash.replace("#pp", "");
    if(hash != ''){
        traiteHash(hash);
    }
     
    $(window).on('hashchange', function() {
        hash = window.location.hash.replace("#pp", "");
        if(actu != hash)
            traiteHash(hash);
    });
    
    function traiteHash(hash){
        if(hash == "")
        hash = "part1a";
        hash2 = hash.slice(0,hash.length-1);
        $("#"+hash2).click();
        changePage(hash);
    }
    
    jQuery(".ui-accordion-header").click(function(){
    actu = $(this).attr("id")+"a";
        window.location.hash = "pp"+$(this).attr("id")+"a";  
    });

    jQuery('.submenu').click(function(){
        changePage(jQuery(this).attr('id'));
    });
    

function changePage(subid){
    actu = subid;
    window.location.hash = "pp"+subid;
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
//                    reloadResult();
                }
            });
        } else {
            jQuery('#subDI').replaceWith('<div id="subDI"></div>');
//             reloadResult();
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
            jQuery('#subFI').replaceWith('<div id="subFI"><table><tr><td width=20><td>FI-0000<td>01/09/2010<td>Nom Utilisateur</table></div>');
//            reloadResult();
        } else {
            jQuery('#subFI').replaceWith('<div id="subFI"></div>');
//            reloadResult();
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


};
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
    });
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
//$js .= "<script src='" . DOL_URL_ROOT . "/newGle/htdocs/core/js/datepicker.js.php' type='text/javascript'></script>";
$js .= '<script language="javascript" src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.validate.js"></script>' . "\n";

$js .= "<style>.submenu:hover { cursor: pointer; text-decoration: underline; font-weight: 900;}
.subsubmenu:hover { cursor: pointer; text-decoration: underline; font-weight: 900;}
</style>";
llxHeader($js, "Préparation de commande");

$head = commande_prepare_head($commande);
dol_fiche_head($head, 'prepaCommande', $langs->trans("Preparation de commande"), 0, 'order');



print "<div class='titre'>Pr&eacute;paration</div>";
//
//
//print "<table>";
//foreach($commande->lines as $key=>$val)
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

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'shipped' && $user->rights->commande->cloturer)
{
	$result = $commande->cloture($user);
	if ($result < 0) $mesgs=$object->errors;
}
elseif (($commande->statut == 1 || $commande->statut == 2) && $user->rights->commande->cloturer) {
    print '<br/><div class="inline-block"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $commande->id . '&amp;action=shipped">' . $langs->trans('ClassifyShipped') . '</a></div><br/><br/>';
}


print "<div id='replaceResult'>";


$idCommande = $commande->id;
require(DOL_DOCUMENT_ROOT . "/Synopsis_PrepaCommande/ajax/getResults-html_response.php");

print "</div>";
print "<br/>";

llxFooter();
?>
