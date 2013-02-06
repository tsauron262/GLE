<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 29 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : nouveau.php
  * GLE-1.2
  */

  //Creer un litige
require_once('pre.inc.php');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe','','');
$socid = isset($_GET["socid"])?$_GET["socid"]:'';

$js = '<script>var DOL_URL_ROOT="'.DOL_URL_ROOT.'";</script>';
$js = '<script>var DOL_DOCUMENT_ROOT="'.DOL_DOCUMENT_ROOT.'";</script>';

$js.=<<<EOF
    <script>
jQuery(document).ready(function(){
    jQuery('#nextStep').click(function(){
        if(jQuery('#societe').val()>0)
        {
            jQuery('.serial').css('display','none');
            jQuery('.nextstep').css('display','block');
            var socid = jQuery("#societe").val();
            jQuery.ajax({
                url: "ajax/listElementRetour_xml-response.php",
                data: "socid="+socid,
                datatype: "xml",
                type:"POST",
                cache: true,
                success: function(msg){
                    html = "<table class='detailTable' cellpadding=15 width=100%;><tbody><tr>";
                    html1 = "<thead><tr>";
                    html2 = "<tbody><tr>";
                    if (jQuery(msg).find('commandes').find('commande').length > 0)
                    {
                        html1 += "<th class='ui-widget-header ui-state-default'>Commandes";
                        html2 += "<td class='ui-widget-content'><select class='addedElement' id ='commande'>";
                        html2 += "<option value='0'>S&eacute;lectionner-></option>";
                        jQuery(msg).find('commandes').find('commande').each(function(){
                            html2 += "<option value='"+jQuery(this).find('id').text()+"'>"+jQuery(this).find('ref').text()+" - "+jQuery(this).find('date').text()+"</option>";
                        });
                        html2 += "</select>";
                    }

                    if (jQuery(msg).find('factures').find('facture').length > 0)
                    {
                        html1 += "<th class='ui-widget-header ui-state-default'>Facture";
                        html2 += "<td class='ui-widget-content'><select class='addedElement' id ='facture'>";
                        html2 += "<option value='0'>S&eacute;lectionner-></option>";
                        jQuery(msg).find('factures').find('facture').each(function(){
                            html2 += "<option value='"+jQuery(this).find('id').text()+"'>"+jQuery(this).find('ref').text()+" - "+jQuery(this).find('date').text()+"</option>";
                        });
                        html2 += "</select>";
                    }

                    if (jQuery(msg).find('livraisons').find('livraison').length > 0)
                    {
                        html1 += "<th class='ui-widget-header ui-state-default'>livraisons";
                        html2 += "<td class='ui-widget-content'><select class='addedElement' id ='livraison'>";
                        html2 += "<option value='0'>S&eacute;lectionner-></option>";
                        jQuery(msg).find('livraisons').find('livraison').each(function(){
                            html2 += "<option value='"+jQuery(this).find('id').text()+"'>"+jQuery(this).find('ref').text()+" - "+jQuery(this).find('date').text()+"</option>";
                        });
                        html2 += "</select>";
                    }

                    if (jQuery(msg).find('contrats').find('contrat').length > 0)
                    {
                        html1 += "<th class='ui-widget-header ui-state-default'>Contrats";
                        html2 += "<td class='ui-widget-content'><select class='addedElement' id ='contrat'>";
                        html2 += "<option value='0'>S&eacute;lectionner-></option>";
                        jQuery(msg).find('contrats').find('contrat').each(function(){
                            html2 += "<option value='"+jQuery(this).find('id').text()+"'>"+jQuery(this).find('ref').text()+" - "+jQuery(this).find('date').text()+"</option>";
                        });
                        html2 += "</select>";
                    }

                    if (jQuery(msg).find('contratGAs').find('contratGA').length > 0)
                    {
                        html1 += "<th class='ui-widget-header ui-state-default'>Contrats de location";
                        html2 += "<td class='ui-widget-content'><select class='addedElement' id='contratGA'>";
                        html2 += "<option value='0'>S&eacute;lectionner-></option>";
                        jQuery(msg).find('contratGAs').find('contratGA').each(function(){
                            html2 += "<option value='"+jQuery(this).find('id').text()+"'>"+jQuery(this).find('ref').text()+" - "+jQuery(this).find('date').text()+"</option>";
                        });
                        html2 += "</select>";
                    }
                    html += html1 +"</thead>"+ html2+ "</tbody></table>";

                    jQuery('.detailTable').remove();
                    jQuery('#mainTable').parent().append(html);
                    jQuery('.addedElement').selectmenu({style: 'dropdown', maxHeight: 300 });
                    jQuery('.addedElement').change(function(){
                        location.href='fiche.php?action=create&fk_soc='+socid+"&element_id="+jQuery(this).find(':selected').val()+"&element_type="+jQuery(this).attr('id');
                    });
                },
            })
        }

    });
    jQuery("#societe").change(function(){
        if(jQuery(this).val()>0)
        {
            jQuery('.nextstep').css('display','block');
            jQuery('.serial').css('display','none');
            var socid = jQuery(this).val();
            jQuery.ajax({
                url: "ajax/listElementRetour_xml-response.php",
                data: "socid="+socid,
                datatype: "xml",
                type:"POST",
                cache: true,
                success: function(msg){
                    html = "<table class='detailTable' cellpadding=15 width=100%;><tbody><tr>";
                    html1 = "<thead><tr>";
                    html2 = "<tbody><tr>";
                    if (jQuery(msg).find('commandes').find('commande').length > 0)
                    {
                        html1 += "<th class='ui-widget-header ui-state-default'>Commandes";
                        html2 += "<td class='ui-widget-content'><select class='addedElement' id ='commande'>";
                        html2 += "<option value='0'>S&eacute;lectionner-></option>";
                        jQuery(msg).find('commandes').find('commande').each(function(){
                            html2 += "<option value='"+jQuery(this).find('id').text()+"'>"+jQuery(this).find('ref').text()+" - "+jQuery(this).find('date').text()+"</option>";
                        });
                        html2 += "</select>";
                    }

                    if (jQuery(msg).find('factures').find('facture').length > 0)
                    {
                        html1 += "<th class='ui-widget-header ui-state-default'>Facture";
                        html2 += "<td class='ui-widget-content'><select class='addedElement' id ='facture'>";
                        html2 += "<option value='0'>S&eacute;lectionner-></option>";
                        jQuery(msg).find('factures').find('facture').each(function(){
                            html2 += "<option value='"+jQuery(this).find('id').text()+"'>"+jQuery(this).find('ref').text()+" - "+jQuery(this).find('date').text()+"</option>";
                        });
                        html2 += "</select>";
                    }

                    if (jQuery(msg).find('livraisons').find('livraison').length > 0)
                    {
                        html1 += "<th class='ui-widget-header ui-state-default'>livraisons";
                        html2 += "<td class='ui-widget-content'><select class='addedElement' id ='livraison'>";
                        html2 += "<option value='0'>S&eacute;lectionner-></option>";
                        jQuery(msg).find('livraisons').find('livraison').each(function(){
                            html2 += "<option value='"+jQuery(this).find('id').text()+"'>"+jQuery(this).find('ref').text()+" - "+jQuery(this).find('date').text()+"</option>";
                        });
                        html2 += "</select>";
                    }

                    if (jQuery(msg).find('contrats').find('contrat').length > 0)
                    {
                        html1 += "<th class='ui-widget-header ui-state-default'>Contrats";
                        html2 += "<td class='ui-widget-content'><select class='addedElement' id ='contrat'>";
                        html2 += "<option value='0'>S&eacute;lectionner-></option>";
                        jQuery(msg).find('contrats').find('contrat').each(function(){
                            html2 += "<option value='"+jQuery(this).find('id').text()+"'>"+jQuery(this).find('ref').text()+" - "+jQuery(this).find('date').text()+"</option>";
                        });
                        html2 += "</select>";
                    }

                    if (jQuery(msg).find('contratGAs').find('contratGA').length > 0)
                    {
                        html1 += "<th class='ui-widget-header ui-state-default'>Contrats de location";
                        html2 += "<td class='ui-widget-content'><select class='addedElement' id='contratGA'>";
                        html2 += "<option value='0'>S&eacute;lectionner-></option>";
                        jQuery(msg).find('contratGAs').find('contratGA').each(function(){
                            html2 += "<option value='"+jQuery(this).find('id').text()+"'>"+jQuery(this).find('ref').text()+" - "+jQuery(this).find('date').text()+"</option>";
                        });
                        html2 += "</select>";
                    }
                    html += html1 +"</thead>"+ html2+ "</tbody></table>";

                    jQuery('.detailTable').remove();
                    jQuery('#mainTable').parent().append(html);
                    jQuery('.addedElement').selectmenu({style: 'dropdown', maxHeight: 300 });
                    jQuery('.addedElement').change(function(){
                        location.href='fiche.php?action=create&fk_soc='+socid+"&element_id="+jQuery(this).find(':selected').val()+"&element_type="+jQuery(this).attr('id');
                    });
                },
            })
        }
    });
});
</script>
EOF;
llxHeader($js,'Nouveau litige',"","1");

//$sql = "SELECT s.rowid, s.nom, s.ville, s.datec")." as datec, ".$db->pdate("s.datea as datea";
//$sql.= ", s.client, s.fournisseur ";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
//$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
//$sql.= " WHERE client = 1";
//if ($socid)
//{
//    $sql .= " AND s.rowid = ".$socid;
//}
//
//if (! $user->rights->societe->client->voir && ! $socid) //restriction
//{
//    $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
//}
//
//$sql .= " ORDER BY s.nom  " ;
//
//$result = $db->query($sql);
//     1) Choisit le client

print "<br/>";
print "<div class='titre'>Nouveau litige / Nouveau retour</div>";
print "<br/>";
print "<br/>";
print "<h3 class='serial'>Recherche par client</h3>";
//print "<table id='mainTable' cellpadding=15 width=45%>";
//print "<tr><th class='ui-widget-header ui-state-default'>Client<td class='ui-widget-content'>";
//print "<select name='societe' id='societe' style='width: 100%;'>";
//while ($res = $db->fetch_object($sql))
//{
//    print "<option value='".$res->rowid."'>".$res->nom."</option>";
//}
//print "</select>";
//print "</table>";
        // print formulaire de recherche de la société

        $search_nom=isset($_GET["search_nom"])?$_GET["search_nom"]:$_POST["search_nom"];
        $search_ville=isset($_GET["search_ville"])?$_GET["search_ville"]:$_POST["search_ville"];
        $socname=isset($_GET["socname"])?$_GET["socname"]:$_POST["socname"];
        $sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
        $sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
        $page=isset($_GET["page"])?$_GET["page"]:$_POST["page"];

        if (! $sortorder) $sortorder="ASC";
        if (! $sortfield) $sortfield="nom";

        if ($page == -1) { $page = 0 ; }

        $offset = $conf->liste_limit * $page ;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        $sql = "SELECT s.rowid, s.nom, s.ville, s.datec as datec, s.datea as datea";
        $sql.= ", st.libelle as stcomm, s.prefix_comm, s.client, s.fournisseur,";
        if ($conf->global->MAIN_MODULE_BABELGA)
        {
            $sql.=" s.cessionnaire, ";
        }
        $sql.= " s.siren as idprof1, s.siret as idprof2, ape as idprof3, idprof4 as idprof4";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
        $sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE s.fk_stcomm = st.id AND client > 0";
        if ($socid)
        {
            $sql .= " AND s.rowid = ".$socid;
        }
        if (strlen($stcomm))
        {
            $sql .= " AND s.fk_stcomm=".$stcomm;
        }

        if (! $user->rights->societe->client->voir && ! $socid) //restriction
        {
            $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        }
        if (! $user->rights->societe->lire || ! $user->rights->fournisseur->lire)
        {
            if (! $user->rights->fournisseur->lire) $sql.=" AND s.fournisseur != 1";
        }

        if ($search_nom)
        {
            $sql.= " AND (";
            $sql.= "s.nom LIKE '%".addslashes($search_nom)."%'";
            $sql.= " OR s.code_client LIKE '%".addslashes($search_nom)."%'";
            $sql.= " OR s.email like '%".addslashes($search_nom)."%'";
            $sql.= " OR s.url like '%".addslashes($search_nom)."%'";
            $sql.= ")";
        }

        // Count total nb of records
        $nbtotalofrecords = 0;
        if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
        {
            $result = $db->query($sql);
            $nbtotalofrecords = $db->num_rows($result);
        }

        $sql .= " ORDER BY $sortfield $sortorder ";

        $result = $db->query($sql);
        if ($result)
        {
//            $num = $db->num_rows($result);
//            $i = 0;
            print "<br/>";
            print "<br/>";
            print "<div style='padding-left: 25px;' class='serial'>";
            print "<table cellpadding=15 width=700  class='serial'>";
            print "<tr><th style='padding: 20px 15px; font-size: 12pt;' class='ui-widget-header ui-state-default' colspan=2>Choix du destinataire";
            if ($db->num_rows($result) > 0)
            {
                print "<tr><td width=50% class='ui-widget-content' align=center><select name='societe' id='societe'>";
                while ($res=$db->fetch_object($result))
                {
                    print "<option value='".$res->rowid."' >".htmlentities($res->nom)."</option>";
                }
                print"</select>";
            } else {
                print "<tr><td width=50% class='ui-widget-content' align=center>Pas de tiers trouv&eacute;";
            }
            print "</td><td class='ui-widget-content' align=center>";
            print '<button id="nextStep" style="padding: 5px 10px; " class="ui-widget-header ui-corner-all ui-state-default" ><span style="padding: 1px 10px;float: left;">'.$langs->trans("Etape suivante").'</span><span style="float: left;" class="ui-icon ui-icon-arrowreturnthick-1-e"></button>';
            print "</table>";

            print "<form action='nouveau.php' method='GET'>";
            print "<table cellpadding=15 width=700>";
            print "<tr><th width=50%  style='padding: 5px 10px' class='ui-widget-header ui-state-default' colspan=2>Filtrer par nom :</th><td align=center class='ui-widget-content'>";
            print "<table width=100%><tr><td align=center>";
            print "<input style='text-align:center;' type='text' name='search_nom' value='".$_REQUEST['search_nom']."'><td>";
            print "<input type='hidden' name='action' value='create'>";
            print '<button style="padding: 5px 10px;" class="ui-widget-header ui-corner-all ui-state-default" ><span style="padding: 1px 10px;float: left;">'.$langs->trans("Filtrer").'</span><span style="float: left;" class="ui-icon ui-icon-circle-zoomout"></button>';
            print "</td>";
            print "</table";
            print "</form>";
            print "</table";

            print "</div>";
        }


print "<br/>";
print "<h3 class='serial'>Recherche par num&eacute;ro de s&eacute;rie</h3>";
print "<table class='serial' id='mainTable' cellpadding=15 width=700>";
print "<tr><th class='ui-widget-header ui-state-default'>Num&eacute;ro de s&eacute;rie<td class='ui-widget-content'>";
print "<input type='text' value='' name='serial' id='serial'>";
print "<td class='ui-widget-content'><button id='seekBySerial' class='butAction ui-state-default ui-widget-header ui-corner-all butAction'>Rechercher</button>";
print "</table>";
print <<<EOF
<script>
jQuery(document).ready(function(){
    jQuery('#seekBySerial').click(function(){
        jQuery.ajax({
            url: "ajax/seekBySerial-xml_response.php",
            data: "serial="+jQuery('#serial').val(),
            datatype: "xml",
            type:"POST",
            cache: true,
            success:function(msg){
                if (jQuery(msg).find('element').length>0)
                {

                    jQuery('.detailTable').remove();

                    var classH='class="ui-wiget-header ui-state-default"';
                    var classN ='class="ui-widget-content"';
                    html = "<table class='detailTable' cellpadding=15 width=100%;><thead>";
                    html += "<tr><th "+classH+">Client<th "+classH+">Type<th "+classH+">&Eacute;l&eacute;ment<th width=10% "+classH+">Action";
                    html += "</thead><tbody>";
                    jQuery(msg).find("element").each(function(){
                        var soc = jQuery(this).find('soc').text();
                        var url = jQuery(this).find('url').text();
                        var id = jQuery(this).find('id').text();
                        var eid = jQuery(this).find('eid').text();
                        var socid = jQuery(this).find('socid').text();
                        var type = jQuery(this).find('type').text();
                        var dbtype = jQuery(this).find('dbtype').text();
                        html += "<tr><td "+classN+">"+soc+"</td><td "+classN+">"+type+"</td><td "+classN+">"+url+"</td><td "+classN+">\
                                        <button onClick='location.href=\"fiche.php?action=create&fk_soc="+socid+"&element_id="+ eid +"&element_type="+dbtype+"\"' class='ui-widget-header ui-state-default ui-corner-all butAction'>S&eacute;l&eacute;cionner</button></td>";
                    });
                    html += "</table>";
                    jQuery('#mainTable').parent().append(html);
                } else {
                    jQuery('.detailTable').remove();

                    html = "<table class='detailTable' cellpadding=15 width=100%;><tbody><tr><td class='ui-state-error'>"+jQuery('#serial').val()+" - Aucun &eacute;l&eacute;ment trouv&eacute;</table>";
                    jQuery('#mainTable').parent().append(html);
            }

            }
        });
    });
})

</script>

EOF;

print "<h3 class='nextstep' style='display:none;'>S&eacute;lection de l'&eacute;l&eacute;ment r&eacute;f&eacute;rent</h3>";

//   2) Choisit le contrat / la commande
//   3) Choix de :
//    -> Retour stock (total / partiel)
//    -> SAV
//   4a) Si retour stock, pointe le matériel => retour partiel ou total
//                                           => Litige sur ligne
//                                           => SAV sur ligne
//
//                                          Option : Mettre toute la commande / livraison / ... en litige
//                                                   Si litige:> bloque facturation en option $conf->MAIN_
//   4b) SAV :> GMAO attn au contrat / commande et date de SAV sur le produit
//
//   5) Si litige:> Résolution du litige :> Assurance, perte, facturation, SAV interne ou externe
//
//   6) Cloture du retour, possibilité de cloturer la facture, commande, livraison
//                                     de lancer la commande / facture / livraison ...


?>
