<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 5 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : create.php
  * GLE-1.1
  */

    require_once('pre.inc.php');
    require_once(DOL_DOCUMENT_ROOT.'/Babel_GMAO/SAV.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/Babel_GMAO/SAV/SAV.functions.php');
$html = new Form($db);

    // Security check
    $socid = isset($_GET["socid"])?$_GET["socid"]:'';
    if ($user->societe_id) $socid=$user->societe_id;
    $result = restrictedArea($user, 'societe',$socid,'');

    $sortorder=$_GET["sortorder"];
    $sortfield=$_GET["sortfield"];
    if (! $sortorder) $sortorder="ASC";
    if (! $sortfield) $sortfield="nom";
    $langs->load('sav');
    $js = "<style type='text/css'>
            .fiche input { text-align:center; width: 20em; }
            .fiche textarea { width: 20em; }
           </style>";
    $js .= "<script type='text/javascript' src='".DOL_URL_ROOT."/core/lib/lib_head.js'></script>";
    llxHeader($js,"Nouvelle fiche SAV",1);

    print "<br/><div class='titre'>Nouvelle fiche</div><br/>";

    if ($socid > 0 )
    {
        //Formulaire creation SAV
        print <<<EOF


        <form id="createSAV" method="POST" action="fiche.php?action=create">
        <table cellpadding=15 width=100%>
        <tr>
        <th class="ui-widget-header ui-state-default">Description
        <td class="ui-widget-content" colspan="2">
        <input name="Description" id="Description"></input>
        <tr>
        <th class="ui-widget-header ui-state-default">Probl&egrave;me
        <td class="ui-widget-content" colspan="2">
        <textarea name="probleme" id="probleme"></textarea>
        <tr>
        <th class="ui-widget-header ui-state-default">Produit
        <td class="ui-widget-content"  colspan="2">
EOF;
print "<input type='hidden' name='socid'' id='socid' value='".$socid."'>";
        $html->select_produits('','idprod','',$conf->produit->limit_size,0,1,true,false);
print <<<EOF

        <tr>
        <th class="ui-widget-header ui-state-default">Num de s&eacute;rie
        <td class="ui-widget-content" colspan="2">
        <input name="serial" id="serial"></input>

        <tr><td colspan="3" align="center" class="ui-widget-content">

        <button id="butValid" onClick="return(false);" style="padding: 5px 10px" class="butAction ui-corner-all ui-state-default ui-widget-header">OK</button>
        </table>

        </form>
        <script>
EOF;
print "var msgNotFound = '".utf8_decode('Aucun produit trouvé de ce type pour ce client')."';";
print <<<EOF

var arrDialogMultiProd = new Array();
var maxi = 0;
                jQuery("#butValid").click(function(){
                    if (jQuery('#createSAV').validate({
                         rules: {
                            Description: {
                                required: true,
                            },
                            probleme: {
                                required: true,
                            },
                        },
                        messages: {
                            Description: {
                                requiredNoBR: "<br/><span style='font-size: 9px;'> Merci de saisir une description</span>",
                            },
                            probleme: {
                                required: "<span style=' font-size: 9px;'> Merci de d&eacute;crire le probl&egrave;me</span>",
                            },
                        }
                    }).form()){
                        //Si OK
                        var serial = jQuery('input[name=serial]').val();
                        var fk_prod = jQuery('#ajdynfieldidprod').find(':selected').val();
                        var socid = jQuery('input[name=socid]').val();
                        data = "serial="+serial+"&fk_prod="+fk_prod+"&socid="+socid;
                        jQuery.ajax({
                            url:"ajax/SAVgetData-xml_response.php",
                            datatype:"xml",
                            type:"POST",
                            data:data,
                            cache: false,
                            success:function(msg){
                                if (jQuery(msg).find('product').length > 0)
                                {
                                    //dialog box avec Le choix du produit
                                    arrDialogMultiProd = new Array();
                                    maxi = 0;
                                    jQuery(msg).find('product').each(function(){
                                        var serial = jQuery(this).find('serial').text();
                                        var label = jQuery(this).find('label').text();
                                        var refelement = jQuery(this).find('refElement').text();
                                        var ref = jQuery(this).find('ref').text();
                                        var htmlId = jQuery(this).find('htmlId').text();

                                        arrDialogMultiProd[maxi]=new Array();
                                        arrDialogMultiProd[maxi]['label']=label;
                                        arrDialogMultiProd[maxi]['refelement']=refelement;
                                        arrDialogMultiProd[maxi]['ref']=ref;
                                        arrDialogMultiProd[maxi]['serial']=serial;
                                        arrDialogMultiProd[maxi]['htmlId']=htmlId;
                                        maxi++;
                                    });
                                        jQuery('#dialogMultiProduct').dialog('open');
//                        console.log(arrDialogMultiProd);
                                } else if (jQuery(msg).find('serial').length > 0)
                                {
                                    //On continue à l'étape suivante
//                                    var data = jQuery('#createSAV').serialize();
                                    var data ="";
                                        data += 'Description='+jQuery('#Description').val();
                                        data += '&probleme='+jQuery('#probleme').val();
                                        data += '&socid='+jQuery('#socid').val();
                                        data += '&idprod='+jQuery('SELECT#idprod').val();
                                        data += '&serial='+jQuery('#serial').val();

                                        //console.log(data);
                                        sendData_MoveOn(data);
                                } else {
                                    alert (msgNotFound);
                                }
                            }

                        });
                    } else {
                        //Sinon
//                        console.log(jQuery('#createSAV').serialize());
                            var data ="";
                                data += 'Description='+jQuery('#Description').val();
                                data += '&probleme='+jQuery('#probleme').val();
                                data += '&socid='+jQuery('#socid').val();
                                data += '&idprod='+jQuery('SELECT#idprod').val();
                                data += '&serial='+jQuery('#serial').val();
                                sendData_MoveOn(data);
                    }
                    jQuery('#dialogMultiProduct').dialog({
                        modal: true,
                        title: "Choisir le produit concern&eacute;",
                        width: 620,
                        autoOpen: false,
                        buttons: {
                            Ok: function(){
                                if (jQuery('#chooseProd').validate({
                                    rules: {
                                        productClient: {
                                            required: true,
                                        },
                                    },
                                    messages:{
                                        productClient: {
                                            required: "Merci de choisir un produit",
                                        },
                                    }
                                }).form()){
                                    jQuery('input[name=serial]').val(jQuery('input[name=productClient]').val());
                                    var data ="";
                                        data += 'Description='+jQuery('#Description').val();
                                        data += '&probleme='+jQuery('#probleme').val();
                                        data += '&socid='+jQuery('#socid').val();
                                        data += '&idprod='+jQuery('SELECT#idprod').val();
                                        data += '&serial='+jQuery('#serial').val();
                                        sendData_MoveOn(data);
                                }
                            },
                            Annuler: function(){
                                jQuery(this).dialog('close');
                            }
                        }
                    });
                    jQuery('#dialogMultiProduct').bind('dialogopen', function(e,u){
                        var longHtml = "<div id='divToReplace'><form id='chooseProd'><table cellpadding=10 width=600 ><tr><th style='border-right: none;' class='ui-widget-header ui-state-default'>&nbsp;<th  style='border-left: none;'  class='ui-widget-header ui-state-default'>Ref Produit<th class='ui-widget-header ui-state-default'>Libell&eacute;<th class='ui-widget-header ui-state-default'>Num. s&eacute;rie<th  class='ui-widget-header ui-state-default'>El&eacute;ment";
                        //console.log(arrDialogMultiProd);
                        for(i=0;i<maxi;i++)
                        {
                            longHtml += "<tr class='ui-widget-content'><td align='center'><input type='radio' value='"+arrDialogMultiProd[i]['htmlId']+"' name='productClient'><td align='center'>"+arrDialogMultiProd[i]['ref']+"<td align='center'>"+arrDialogMultiProd[i]['label']+"<td align='center'>"+arrDialogMultiProd[i]['serial']+'<td align="center">'+arrDialogMultiProd[i]['refelement'];
                        }
                        longHtml += "</table></form></div>";
                        jQuery(this).find('#divToReplace').html(longHtml);
                    });

                });

                function sendData_MoveOn(pData)
                {
                    var url = "fiche.php?action=create";
                    jQuery.post(url, pData, function(msg){ var res=jQuery(msg).find('OK'); if (res){ location.href='fiche.php?id='+jQuery(msg).find('OK').text(); } else { console.log('err');} },"xml");
                }
</script>
<div id='dialogMultiProduct'>
    <div id = 'divToReplace'>
    </div>
</div>
EOF;


    } else {
        //Formulaire societe

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
        $sql = "SELECT s.rowid, s.nom, s.ville";
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

//        // Count total nb of records
//        $nbtotalofrecords = 0;
//        if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
//        {
//            $result = $db->query($sql);
//            $nbtotalofrecords = $db->num_rows($result);
//        }

        $sql .= " ORDER BY $sortfield $sortorder ";
die($sql);
        // Count total nb of records
        $nbtotalofrecords = 0;
        $result;
        if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
        {
            $result = $db->query($sql);
            $nbtotalofrecords = $db->num_rows($result);
        } else {
            $result = $db->query($sql);
            $nbtotalofrecords = $db->num_rows($result);
            $sql .= " LIMIT ".$conf->global->MAIN_DISABLE_FULL_SCANLIST;
            $result = $db->query($sql);
        }

//        $result = $db->query($sql);
        if ($result)
        {
//            $num = $db->num_rows($result);
//            $i = 0;
            print "<br/>";
            print "<br/>";
            print "<br/>";
            print "<br/>";
            print "<div style='padding-left: 25px;'>";
            print "<form action='create.php' method='GET'>";
            print "<input type='hidden' name='action' value='create' >";
            print "<table width=50%>";
            print "<tr><th style='padding: 5px 10px' class='ui-widget-header ui-state-default' colspan=2>Choix du client";
            print "<tr><td colspan=2>&nbsp;";
            if ($db->num_rows($result) > 0)
            {
                print "<tr><td width=50% class='ui-widget-content' align=center><select name='socid'>";
                while ($res=$db->fetch_object($result))
                {
                    print "<option value='".$res->rowid."' >".htmlentities($res->nom)."</option>";
                }
                print"</select>";
            } else {
                print "<tr><td width=50% class='ui-widget-content' align=center>Pas de tiers trouv&eacute;";
            }
            print "</td><td class='ui-widget-content' align=center>";
            print '<button style="" class="ui-widget-header ui-corner-all ui-state-default" ><span style="padding: 1px 10px;float: left;">'.$langs->trans("Etape suivante").'</span><span style="float: left;" class="ui-icon ui-icon-arrowreturnthick-1-e"></button>';
            print "</table>";
            print "</form>";
            if ($nbtotalofrecords > $db->num_rows($result))
            {
                print "<em>".$db->num_rows($result)."/".$nbtotalofrecords." r&eacute;sultats affich&eacute;s</em>";
            }

            print "<br/>";
            print "<br/>";
            print "<form action='create.php' method='GET'>";
            print "<table width=50%>";
            print "<tr><th width=50%  style='padding: 5px 10px' class='ui-widget-header ui-state-default' colspan=2>Filtrer par nom :</th><td align=center class='ui-widget-content'>";
            print "<table width=100%><tr><td align=center>";
            print "<input style='text-align:center;' type='text' name='search_nom' value='".$_REQUEST['search_nom']."'><td>";
            print "<input type='hidden' name='action' value='create'>";
            print '<button style="" class="ui-widget-header ui-corner-all ui-state-default" ><span style="padding: 1px 10px;float: left;">'.$langs->trans("Filtrer").'</span><span style="float: left;" class="ui-icon ui-icon-circle-zoomout"></button>';
            print "</td>";
            print "</table";
            print "</form>";

            print "</div>";

        } else {
            dol_print_error($db,$soc->error);
        }
        exit;

    }



?>