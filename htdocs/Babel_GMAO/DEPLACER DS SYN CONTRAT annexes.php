<?php
/*
  ** BIMP-ERP by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 7 mars 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : annexes.php
  * BIMP-ERP-1.2
  */


    require_once('pre.inc.php');
    require_once(DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php');
    if ($conf->projet->enabled)  require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
    if ($conf->propal->enabled)  require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
    if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

    $langs->load("contracts");
    $langs->load("orders");
    $langs->load("companies");
    $langs->load("bills");
    $langs->load("products");
    //var_dump($_REQUEST);
    // Security check

    if ($user->societe_id) $socid=$user->societe_id;
    $result=restrictedArea($user,'contrat',$contratid,'contrat');

    $form = new Form($db);
    $html = new Form($db);
    if($_REQUEST['action']=='DeleteAnnexe')
    {
        $idAnnexe = $_REQUEST['modele'];
        $id = $_REQUEST['id'];
        $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_contrat_annexe WHERE contrat_refid = ".$id . " AND annexe_refid = ".$idAnnexe;
        $sql = $db->query($requete);
        header('location:annexes.php?id='.$id);
    }


$js = <<<EOF
<style>
    #sortable, #draggable { list-style-type: none; margin: 0; padding: 0; float: left; margin-right: 10px; width: 100%; min-height: 170px;height: 100%; }
    #sortable li, #draggable li { padding: 5px; font-size: 1.2em; min-width: 170px; }
    </style>
    <script>
    jQuery(document).ready(function() {
        jQuery( "#sortable" ).sortable({
            revert: true,
            stop: function(event, ui) {
                saveAnnexe();
           },
           receive: function(event, ui) {
                jQuery('ul#sortable').find('.ui-draggable').attr('id', jQuery(ui.item).attr('id'));
                jQuery('ul#sortable').find('.ui-draggable').removeClass("ui-draggable");
                jQuery(ui.item).remove();
                saveAnnexe();
           }
        }).disableSelection();
        jQuery("#draggable li").each(function(){
            jQuery(this).draggable({
                connectToSortable: "#sortable",
                helper: "clone",
                revert: "invalid",
                helper: function( event ) {
                    return jQuery( "<div class='ui-widget-header ui-state-default' style='padding:5px 10px; opacity: 0.8;'>"+jQuery(event.currentTarget).html()+"</div>" );
                },
                stop: function(event,ui){
//                    jQuery(event.target).remove();
                }
            });
        });
    });
    var tout=false;
    function sendAjax(datas)
    {
        jQuery.ajax({
            url:'ajax/saveAnnexes-xml_response.php?',
            data:datas,
            datatype:"xml",
            type:"POST",
            cache: false,
            success:function(msg){
                if(jQuery(msg).find('OK').length> 0){
                    if (contratId)
                        location.href='annexes.php?id='+contratId;
                    else
                        location.href='annexes.php';
                } else {
                    if (contratId)
                        location.href='annexes.php?id='+contratId;
                    else
                        location.href='annexes.php';
                }
            }
        });
    }
    function saveAnnexe(){
        var result = new Array();
        var i = 0;
        var arr = jQuery('#sortable').sortable('toArray');
        for (var j in arr)
        {
            if (typeof(arr[j]) == "string")
            {
                var res = arr[j].split('_');
                var name = res[0];
                if (res[1]!== undefined){
                    result[i] = name+"[]="+res[1];
                    i++;
                }
            }
        }
        var datas = result.join('&');
        datas += "&id="+contratId;

        //delay 1sec

        if (!tout)
            tout = setTimeout ( "sendAjax('"+datas+"')", 1000 );
        else{
            clearTimeout(tout);
            tout = setTimeout ( "sendAjax('"+datas+"')", 1000 );
        }

    }
EOF;
    $js.= 'var contratId = '.$_REQUEST['id'].";\n";
    $js.= "</script>";

    llxHeader($js,'Annexes PDF');

    $id = $_REQUEST["id"];
    $isGA = false;
    $isSAV = false;
    $isMaintenance=false;
    $isTicket=false;
    $type = 0;
    if ($id > 0)
    {
        $contrat=getContratObj($id);
        $result=$contrat->fetch($id);
        //saveHistoUser($contrat->id, "contrat",$contrat->ref);
        if ($result > 0) $result=$contrat->fetch_lines();
        if ($result < 0)
        {
            dol_print_error($db,$contrat->error);
            exit;
        }

        if ($mesg) print $mesg;

        $nbofservices=sizeof($contrat->lignes);

        $author = new User($db);
        $author->fetch($contrat->user_author_id);

        $commercial_signature = new User($db);
        $commercial_signature->fetch($contrat->commercial_signature_id);

        $commercial_suivi = new User($db);
        $commercial_suivi->fetch($contrat->commercial_suivi_id);

        $head = contract_prepare_head($contrat);
//        $head = $contrat->getExtraHeadTab($head);

        $hselected = "annexe";

        dol_fiche_head($head, $hselected, $langs->trans("Contract"));



        /*
         *   Contrat
         */

        print '<table cellpadding=15 class="border" width="100%">';

        // Ref du contrat
        print '<tr><th width="25%" class="ui-widget-header ui-state-default">'.$langs->trans("Ref").'</th>
                   <td colspan="1" class="ui-widget-content">';
        print $contrat->getNomUrl(1);
        print "</td>";

        // Customer
        $societe = new Societe($db);
        $societe->fetch($contrat->socid);
        print '   <th class="ui-widget-header ui-state-default">'.$langs->trans("Customer").'</th>';
        print '    <td colspan="1" class="ui-widget-content">'.$societe->getNomUrl(1).'</td></tr>';

        // Statut contrat
        print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Status").'</th>
                   <td colspan="1" class="ui-widget-content" id="statutPanel">';
        if ($contrat->statut==0) print $contrat->getLibStatut(2);
        else print $contrat->getLibStatut(4);
        print "</td>";
        //Type contrat
        print '    <th class="ui-widget-header ui-state-default">'.$langs->trans("Type").'</th>
                   <td colspan="1" class="ui-widget-content" id="typePanel">';
        $arrTmpType = $contrat->getTypeContrat();
        print $arrTmpType['Nom'];
        print "</td></tr>";

        // Date
        print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Date").'</th>';
        print '    <td colspan="3" class="ui-widget-content">'.dol_print_date($contrat->date_contrat,"day")."</td></tr>\n";

        // Projet
        if ($conf->projet->enabled)
        {
            $langs->load("projects");
            print '<tr><th class="ui-widget-header ui-state-default">';
            print $langs->trans("Project");
            if ($_REQUEST["action"] != "classer" && $user->rights->projet->creer) print '<span style="float:right;"><a href="'.$_SERVER["PHP_SELF"].'?action=classer&amp;id='.$id.'">'.img_edit($langs->trans("SetProject")).'</a></span>';
            print '</th><td colspan="3" class="ui-widget-content">';
            if ($_REQUEST["action"] == "classer")
            {
                $form->form_project("card.php?id=$id",$contrat->socid,$contrat->fk_projet,"projetid");
            } else {
                $form->form_project("card.php?id=$id",$contrat->socid,$contrat->fk_projet,"none");
            }
            print "</td></tr>";
        }
        //ajoute lien principal
        $contrat->contratCheck_link();

        if($_REQUEST['action'] == "chSrc"|| count($contrat->linkedArray)==0)
        {

            $soc = $contrat->societe;
            print '<tr><th class="ui-widget-header ui-state-default"><table class="nobordernopadding" style="width:100%;">';
            print '<tr><th class="ui-widget-header ui-state-default">Contrat associ&eacute; &agrave; ';
            print '</table>';

            //liaison - une facture / commande
            $optgroupName[0]='Propositions';
            $optgroupName[1]='Commandes';
            $optgroupName[2]='Factures';
            $optgroup=array();
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."propal WHERE fk_soc = ".$soc->id;
            if ($resql = $db->query($requete))
            {
                while ($res=$db->fetch_object($resql))
                {
                    $optgroup[0]["p".$res->rowid]=$res->ref ." (".round($res->total_ht,0)." &euro;)";
                }
            }

            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."commande WHERE fk_soc = ".$soc->id;
            if ($resql = $db->query($requete))
            {
                while ($res=$db->fetch_object($resql))
                {
                    $optgroup[1]["c".$res->rowid]=$res->ref ." (".round($res->total_ht,0)." &euro;)";
                }
            }
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."facture WHERE fk_soc = ".$soc->id;
            if ($resql = $db->query($requete))
            {
                while ($res=$db->fetch_object($resql))
                {
                    $optgroup[2]["f".$res->rowid]=$res->ref ." (".round($res->total,0)." &euro;)";
                }
            }

            print '<td>';
            print "<FORM action='?action=chgSrvAction&id=".$_REQUEST['id']."' method='POST'>";
            print '<SELECT name="Link">';
            print '<option value="0">Select-></option>';
            foreach($optgroup as $key=>$val)
            {
                print '<optgroup label="'.$optgroupName[$key].'">';
                foreach($val as $key1=>$val1)
                {
                    print '<option value="'.$key1.'">'.$val1.'</option>';
                }
                print "</optgroup>";
            }
            print "</SELECT>";
            print "<input type='submit'>";
            print "</FORM>";

        } else {

            if ($contrat->linkedTo)
            {
                if (preg_match('/^([c|p|f]{1})([0-9]*)/',$contrat->linkedTo,$arr))
                {
                    print '<tr><th class="ui-widget-header ui-state-default"><table class="nobordernopadding" style="width:100%;">';
                    print '<tr><th style="border:0" class="ui-widget-header ui-state-default">Contrat associ&eacute; &agrave; ';
                    $val1 = $arr[2];
                    switch ($arr[1])
                    {
                        case 'c':
                            print 'la commande<td>';
                            print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=chSrc&amp;id='.$id.'">'.img_edit($langs->trans("Change la source")).'</a>';
                            require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
                            $comm = new Synopsis_Commande($db);
                            $comm->fetch($val1);
                            if($conf->global->MAIN_MODULE_SYNOPSISPREPACOMMANDE == 1){
                                print "</table><td colspan=1 class='ui-widget-content'>".$comm->getNomUrl(1);
                                print "<th class='ui-widget-header ui-state-default'>Prepa. commande";
                                print "<td colspan=1 class='ui-widget-content'>".$comm->getNomUrl(1,5);
                            } else {
                                print "</table><td colspan=3 class='ui-widget-content'>".$comm->getNomUrl(1);
                            }

                        break;
                        case 'f':
                            print 'la facture<td>';
                            print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=chSrc&amp;id='.$id.'">'.img_edit($langs->trans("Change la source")).'</a>';
                            require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
                            $fact = new Facture($db);
                            $fact->fetch($val1);
                            print "</table><td colspan=3 class='ui-widget-content'>".$fact->getNomUrl(1);
                        break;
                        case 'p':
                            print 'la proposition<td>';
                            print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=chSrc&amp;id='.$id.'">'.img_edit($langs->trans("Change la source")).'</a>';
                            require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
                            $prop = new Propal($db);
                            $prop->fetch($val1);
                            print "</table><td colspan=3 class='ui-widget-content'>".$prop->getNomUrl(1);
                        break;
                    }
                }
            }
        }

        //ajoute le lien vers les propal / commande / facture
        foreach($contrat->linkedArray as $key=>$val)
        {
            if ($key=='co')
            {
                foreach($val as $key1=>$val1)
                {
                    if ($val1 > 0)
                    {
                        require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
                        $comm = new Synopsis_Commande($db);
                        $result=$comm->fetch($val1);
                        if ($result>0){
                            print '<tr><th class="ui-widget-header ui-state-default">';
                            print 'Commandes associ&eacute;es';
                            print $comm->getNomUrl(1);

                            if($conf->global->MAIN_MODULE_SYNOPSISPREPACOMMANDE == 1){
                                print "<td colspan=1 class='ui-widget-content'>".$comm->getNomUrl(1);
                                print "<th class='ui-widget-header ui-state-default'>Prepa. commande";
                                print "<td colspan=1 class='ui-widget-content'>".$comm->getNomUrl(1,5);
                            } else {
                                print "<td colspan=3 class='ui-widget-content'>".$comm->getNomUrl(1);
                            }

                        }
                    }
                }
            } else if ($key=='fa') {
                foreach($val as $key1=>$val1)
                {
                        require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
                        $fac = new Facture($db);
                        $result=$fac->fetch($val1);
                        if ($result>0){
                            print '<tr><th class="ui-widget-header ui-state-default">';
                            print 'Factures associ&eacute;es<td colspan=3 class="ui-widget-content">';
                            print $fac->getNomUrl(1);
                        }
                }
            } else if ($key=='pr') {
                foreach($val as $key1=>$val1)
                {
                        require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
                        $prop = new Propal($db);
                        $result=$prop->fetch($val1);
                        if ($result>0){
                            print '<tr><th class="ui-widget-header ui-state-default">';
                            print 'Propositions associ&eacute;es<td colspan=3 class="ui-widget-content">';
                            print $prop->getNomUrl(1);
                        }
                 }
            }
        }
        print '</tr>';

        print $contrat->displayExtraInfoCartouche();
        print "</table>";

        print "<br/>";
        print "<br/>";
        print "<br/>";

        //2 colonnes
        print '<div class="demo">';
        print "<table width=100% cellpadding=15>";
        print "<tr><th class='ui-widget-header ui-state-default'>Annexes s&eacute;l&eacute;ctionn&eacute;es</th>";
        print "    <th class='ui-widget-header ui-state-default'>Annexes disponibles</th>";
        print "<tr><td class='ui-widget-content' style='padding: 0px;' valign=top>";
        $requete = "SELECT p.modeleName,
                           p.id,
                           p.ref
                      FROM ".MAIN_DB_PREFIX."Synopsis_contrat_annexe as a,
                           ".MAIN_DB_PREFIX."Synopsis_contrat_annexePdf as p
                     WHERE a.annexe_refid = p.id
                       AND contrat_refid = ".$contrat->id."
                  ORDER BY a.rang";
        $sql = $db->query($requete);
        print '<ul id="sortable">';
        $i=1;
        while ($res = $db->fetch_object($sql))
        {
            print "<li class='ui-widget-content' id='modele_".$res->id."'>Annexe ".$i.": ".$res->modeleName." (ref:".$res->ref.")";
            print "<table style='float:right'><tr>
                              <td><span class='ui-icon ui-icon-arrowreturnthick-1-n' onClick='location.href=\"annexeModele.php?action=Modify&modele=".$res->id."&id=".$contrat->id."\"' title='Modifier'></span>
                              <td><span class='ui-icon ui-icon-trash' title='Effacer' onClick='location.href=\"annexes.php?action=DeleteAnnexe&modele=".$res->id."&id=".$contrat->id."\"'></span>
                              <td><span class='ui-icon ui-icon-triangle-2-n-s'  title='D&eacute;placer'></span></table>";
            $i++;
        }
        print "</ul>";
        print "    <td class='ui-widget-content' style='padding: 0px;' valign=top>";

        $requete = "SELECT p.modeleName,
                           p.id,
                           p.ref
                      FROM ".MAIN_DB_PREFIX."Synopsis_contrat_annexePdf as p
                     WHERE p.id NOT IN (SELECT annexe_refid FROM ".MAIN_DB_PREFIX."Synopsis_contrat_annexe WHERE contrat_refid =".$contrat->id.")
                  ORDER BY p.modeleName";
        $sql = $db->query($requete);
        print '<ul id="draggable">';
        $i=1;
        while ($res = $db->fetch_object($sql))
        {
            print "<li class='ui-widget-content' id='modele_".$res->id."'>Mod&egrave;le ".$res->modeleName." ref: ".$res->ref;
            print "<table style='float:right;'><tr><td><span class='ui-icon ui-icon-arrowreturnthick-1-n' onClick='location.href=\"annexeModele.php?action=Modify&modele=".$res->id."&id=".$contrat->id."\"' title='Modifier'></span></table>";
            $i++;
        }
        print "</ul>";
print "</table>";
        print "</div>";
        print "</div>";
        print "<div align=right><button class='butAction' onClick='location.href=\"annexeModele.php?id=".$contrat->id."\"'>Ajouter un mod&egrave;le</button></div>";
        
        //1 les annexes séléctionnés + num + rang
        //2 les annexes modèles

        //Bouton ajouter un modele d'annexe


    }
    llxFooter();
?>
