<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 13 sept. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : fiche-xml_response.php
 * GLE-1.2
 */
//Lister les contrats demandés dans la commande
//Faire un bouton creer si pas encore creéer
//Si déjà creer rempalcer par un lien

$ajouterTousLeTemp = true;

require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/modules/commande/modules_commande.php");
require_once(DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
require_once(DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php');
require_once(DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php');
require_once(DOL_DOCUMENT_ROOT . "/core/lib/order.lib.php");
if ($conf->projet->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
if ($conf->projet->enabled)
    require_once(DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php');
if ($conf->propal->enabled)
    require_once(DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php');

$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('propal');
$langs->load("synopsisGene@synopsistools");
$langs->load('deliveries');
$langs->load('products');

if (!$user->rights->commande->lire)
    accessforbidden();


// Securite acces client
$socid = 0;
if ($user->societe_id > 0) {
    $socid = $user->societe_id;
}
$commande = new Synopsis_Commande($db);
if ($user->societe_id > 0 && isset($_REQUEST["id"]) && $_REQUEST["id"] > 0) {
    $commande->fetch((int) $_REQUEST['id']);
    if ($user->societe_id != $commande->socid) {
        accessforbidden();
    }
}


$html = new Form($db);
$formfile = new FormFile($db);

$id = $_REQUEST['id'];
if ($id > 0) {
    $commande->fetch($id);
    if ($mesg)
        print $mesg . '<br>';

    print "<table cellpadding=10>";
    $longHtml = "<tr><th class='ui-widget-header ui-state-default'>Description";
    $longHtml .= "    <th class='ui-widget-header ui-state-default'>Qt&eacute;";
    $longHtml .= "    <th class='ui-widget-header ui-state-default'>Contrat associ&eacute;";

    $requete = "SELECT fk_product,
                               " . MAIN_DB_PREFIX . "commandedet.qty,
                               " . MAIN_DB_PREFIX . "commandedet.rowid
                          FROM " . MAIN_DB_PREFIX . "commandedet,
                               " . MAIN_DB_PREFIX . "product
                         WHERE " . MAIN_DB_PREFIX . "product.rowid = " . MAIN_DB_PREFIX . "commandedet.fk_product
                           AND fk_product_type = 2
                           AND fk_commande = " . $id;
    $sql = $db->query($requete);
    $touCreer = false;
    while ($res = $db->fetch_object($sql)) {
        $prodTmp = new Product($db);
        $prodTmp->fetch($res->fk_product);
        $tab = getElementElement("commandedet", "contratdet", $res->rowid);
        if (count($tab) > 0) {
            $tabId = array();
            foreach ($tab as $ligne)
                $tabId[] = $ligne['d'];
            $requete = "SELECT *
                              FROM " . MAIN_DB_PREFIX . "contratdet
                             WHERE rowid in(" . implode(", ", $tabId) . ")";
            $sql1 = $db->query($requete);
            $num = $db->num_rows($sql1);
        }
        else
            $num = 0;
        $longHtml .= "<tr><td class='ui-widget-content'>" . $prodTmp->getNomUrl(1) . " " . utf8_encodeRien($prodTmp->libelle) . "<td align=center class='ui-widget-content'>" . $num . "/" . $res->qty;
        $longHtml .= "<td class='ui-widget-content' align=center>";
        if ($num > 0) {
            $arr2 = array();
            $arr = array();
            while ($res1 = $db->fetch_object($sql1)) {
                if (!isset($arr2[$res1->fk_contrat])) {
                    $arr2[$res1->fk_contrat] = $res1->fk_contrat;
                    $contrat = new Contrat($db);
                    $contrat->fetch($res1->fk_contrat);
                    $arr[] = $contrat->getNomUrl(1);
                }
            }
            $longHtml .= join('<br/>', $arr);
        }
        if ($num < $res->qty || $ajouterTousLeTemp) {
            $requete = "SELECT *
                                          FROM " . MAIN_DB_PREFIX . "contrat
                                         WHERE fk_soc = " . $commande->socid;
            $sql2 = $db->query($requete);
            if ($db->num_rows($sql2) > 0) {
                $longHtml .= "<br/><select name='fk_contrat' id='fk_contrat-" . $res->rowid . "'>";
                while ($res2 = $db->fetch_object($sql2)) {
                    $longHtml .= "<option value='" . $res2->rowid . "'>" . $res2->ref . "</option>";
                }
                $longHtml .= "</select>";
                $touCreer = true;
                $longHtml .= "<button onClick='createContrat2(" . $res->fk_product . "," . $res->rowid . ")' class='butAction'>Ajouter au contrat</button>";
            } //else {
            //Sinon creer un nouveau contrat et reviens ici
//            }
        } /* else {
          //Si contrat existant => liste déroulante + ajout d'une ligne de contrat = lien contrat <=> commande
          $requete = "SELECT *
          FROM " . MAIN_DB_PREFIX . "contrat
          WHERE fk_soc = " . $commande->socid;
          $sql2 = $db->query($requete);
          if ($db->num_rows($sql2) > 0) {
          print "<select name='fk_contrat' id='fk_contrat'>";
          while ($res2 = $db->fetch_object($sql2)) {
          print "<option value='" . $res2->rowid . "'>" . $res2->ref . "</option>";
          }
          print "</select>";
          $touCreer = true;
          print "<button onClick='createContrat2(" . $res->fk_product . "," . $res->rowid . ")' class='butAction'>Ajouter au contrat</button>";
          } else {
          //Sinon creer un nouveau contrat et reviens ici
          print "<button onClick='createContrat(" . $res->fk_product . ")' class='butAction'>Cr&eacute;er le contrat</button>";
          }
          } */
        $tabLigneIdS[] = array($res->fk_product, $res->rowid);
    }
    print '<tr><td colspan="3"><img class="imgLoad" style="display:none;" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/working.gif">';
    print "<button onClick='createContrat(" . 0 . ")' class='butAction'>Cr&eacute;er nouveau contrat manuellement</button>";
    print "<button class='butAjoutContratAll butAction' onClick='ajoutContratAll(tabLigneIdS, 0, 0)'>Tout ajouter a un nouveau contrat</button>";
    if ($touCreer) {
        print '<tr><td colspan="3">';
        $requete = "SELECT *
                                  FROM " . MAIN_DB_PREFIX . "contrat
                                 WHERE fk_soc = " . $commande->socid ." ORDER BY rowid DESC";
        $sql2 = $db->query($requete);
        if ($db->num_rows($sql2) > 0) {
            print "<select name='fk_contratT' id='fk_contratT'>";
            while ($res2 = $db->fetch_object($sql2)) {
                print "<option value='" . $res2->rowid . "'>" . $res2->ref . "</option>";
            }
            print "</select>";
            print "<br/><br/>";
            print "<button class='butAjoutContratAll butAction' onClick='ajoutContratAll(tabLigneIdS, jQuery(\"#fk_contratT\").find(\":selected\").val(), 0)'>Tout ajouter au contrat</button>";
            print "<button class='butAjoutContratAll butAction' onClick='ajoutContratAll(tabLigneIdS, jQuery(\"#fk_contratT\").find(\":selected\").val(), 1)'>Tout ajouter pour renouveler contrat</button>";
        }
    }
    print "<br/><br/>";
    print '</td></tr>';
    print $longHtml;
    print "</table>";
}

print '<script>';
print 'var tabLigneIdS =' . php2js($tabLigneIdS) . '; ';

print <<<EOF
var nbResult = 0;
    var ok = true;
function boucleAttendreResult(nbResultT){
    if(nbResult == nbResultT){
        if(ok == true)
           location.href=DOL_URL_ROOT+"/contrat/card.php?id="+jQuery('#fk_contratT').find(':selected').val();
        else
           alert("Il y a eu une erreur");
    }
    else{
        setTimeout(function(){boucleAttendreResult(nbResultT, ok);}, 500);
    }
}
   

function ajoutContratAll(tabLigne, idContrat, renouveler)
{
    $(".imgLoad").show();
    $(".butAjoutContratAll").attr('disabled', 'disabled');
var lignesStr = Array();
for(i=0;i < tabLigne.length;i++){
    lignesStr.push(tabLigne[i][1]);
}
    jQuery.ajax({
           url:"ajax/xml/addProdToContrat-xml_response.php",
           data:"id="+comId+"&contratId="+idContrat+"&tabElem="+lignesStr.join("-")+"&renouveler="+renouveler,
           datatype:"xml",
           type:"POST",
           cache:false,
           success:function(msg){
            id = $(msg).find("ajax-response").text();
            if(id > 0)
                location.href=DOL_URL_ROOT+"/contrat/card.php?id="+id;
           },
           error:function(msg){
           nbResult++;
             ok =false;
           }
    }); 
}

//function ajoutContratAll(tabLigne)
//{
//    $(".imgLoad").show();
//    $(".butAjoutContratAll").attr('disabled', 'disabled');
//    for(var i=0; i<tabLigne.length;i++){
//        pId = tabLigne[i][0];
//        ligneId = tabLigne[i][1];
//        jQuery.ajax({
//               url:"ajax/xml/addProdToContrat-xml_response.php",
//               data:"id="+comId+"&contratId="+jQuery('#fk_contratT').find(':selected').val()+"&prodId="+pId+"&comLigneId="+ligneId,
//               datatype:"xml",
//               type:"POST",
//               cache:false,
//               success:function(msg){
//                nbResult++;
//                   if(!jQuery(msg).find('OK').length > 0)
//                        ok = false;
//               },
//               error:function(msg){
//               nbResult++;
//                 ok =false;
//               }
//        }); 
//    }
//    boucleAttendreResult(tabLigne.length);
//}

function createContrat2(pId,ligneId)
{
//    //TODO ajoute la ligne, ouvre la page
    idContrat = jQuery('#fk_contrat-'+ligneId).find(':selected').val();
    jQuery.ajax({
        url:"ajax/xml/addProdToContrat-xml_response.php",
        data:"id="+comId+"&contratId="+idContrat+"&prodId="+pId+"&comLigneId="+ligneId,
        datatype:"xml",
        type:"POST",
        cache:false,
        success:function(msg){
            if(jQuery(msg).find('OK').length > 0)
                location.href=DOL_URL_ROOT+"/contrat/card.php?id="+idContrat;
        }
    });
    //TODO passer en paramètre le numéro de ligne pour l'enregistrer dans la liaison contratdet<->commandedet
}

function createContrat(pId)
{
    location.href=DOL_URL_ROOT+"/contrat/card.php?action=create&socid="+socId+"&comId="+comId+"&originLine="+pId+"&returnPrepacom=1&typeContrat=7";
}
</script>
EOF;
?>