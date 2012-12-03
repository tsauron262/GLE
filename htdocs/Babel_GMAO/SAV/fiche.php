<?php

$id = $_REQUEST['id'];

//TODO
//droit
//stocks
//Supprimer fiche (annuler)


require_once('pre.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/Babel_GMAO/SAV.class.php');
require_once(DOL_DOCUMENT_ROOT.'/Babel_GMAO/SAV/SAV.functions.php');

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";
$langs->load('sav');
$errMsg="";
if ($_REQUEST['action']=='create')
{
    $objsav = new Sav($db);
    $objsav->descriptif_produit=$_REQUEST["Description"];
    $objsav->descriptif_probleme=$_REQUEST["probleme"];
    $objsav->fk_soc=$_REQUEST["socid"];
    $objsav->fk_product=($_REQUEST["idprod"] > 0?$_REQUEST["idprod"]:"NULL");
    $objsav->serial=$_REQUEST["serial"];
    $id = $objsav->create();
    $xml="";
    if ($id < 0)
    {
        $errMsg ="Echec de la cr&eacute;tion";
        $xml = "<KO>KO ".$id."</KO>";
    } else {
        $xml = "<OK>".$id."</OK>";
    }

    header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= "<xml>".$xml."</xml>";
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;
    exit();
}
$id = $_REQUEST['id'];

if ($_REQUEST['action']=="prisecharge")
{
    $objsav = new Sav($db);
    $objsav->fetch($id);
    $objsav->setPriseEnCharge();
}
if ($_REQUEST['action']=='externalise')
{
    $objsav = new Sav($db);
    $objsav->fetch($id);
    $objsav->setTraitementExterne();
}
if ($_REQUEST['action']=='repare')
{
    $objsav = new Sav($db);
    $objsav->fetch($id);
    $objsav->setRepare();
}
if ($_REQUEST['action']=='reparer')
{
    $objsav = new Sav($db);
    $objsav->fetch($id);
    $objsav->setRepare();
}
if ($_REQUEST['action']=='miseadispo')
{
    $objsav = new Sav($db);
    $objsav->fetch($id);
    $objsav->setAttenteClient();
}
if ($_REQUEST['action']=='termine')
{
    $objsav = new Sav($db);
    $objsav->fetch($id);
    $objsav->setCloture();
}


llxHeader("","Fiche SAV",1);
if ($errMsg."x" != "x")
{
    print "<div class='ui-state-error'>".$msg."</div>";
}

$form =new Form($db);

// On recupere les donnees societes par l'objet
    $objsav = new Sav($db);
    $objsav->fetch($id);

    $objsoc = new Societe($db);
    $objsoc->fetch($objsav->societe_refid);

    $dac = utf8_decode(strftime("%Y-%m-%d %H:%M", time()));
    if ($errmesg)
    {
        print "<b>$errmesg</b><br>";
    }

    /*
     * Affichage onglets
     */

    $head = sav_prepare_head($objsav);

    dol_fiche_head($head, 'index', $langs->trans("SAV"));


    /*
     *
     */
    print '<table width="100%" class="notopnoleftnoright">';
    print '<tr><td valign="top" class="notopnoleft">';

    print '<table class="border" width="100%" cellpadding=15>';
    print '<tr><th colspan=4 align="center" class="ui-state-hover ui-widget-header">Le client</th></tr>';

    print '<tr><th width="30%" class="ui-state-default ui-widget-header">'.$langs->trans("Name").'</th><td width="70%" colspan="3" class="ui-widget-content">';
    print $objsoc->nom;
    print '</td></tr>';

    print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('Prefix').'</td><td colspan="3" class="ui-widget-content">'.$objsoc->prefix_comm.'</td></tr>';

    if ($objsoc->client)
    {
        print '<tr><th nowrap class="ui-state-default ui-widget-header">';
        print $langs->trans('CustomerCode').'</td><td colspan="3"  class="ui-widget-content">';
        print $objsoc->code_client;
        if ($objsoc->check_codeclient() <> 0) print '  <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
        print '</td></tr>';
    }

    print "<tr><th class='ui-state-default ui-widget-header' valign=\"top\">".$langs->trans('Address')."</td><td  class='ui-widget-content' colspan=\"3\">".nl2br($objsoc->adresse)."</td></tr>";

    print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('Zip').'</td><td class="ui-widget-content">'.$objsoc->cp."</td>";
    print '<th  class="ui-state-default ui-widget-header " >'.$langs->trans('Town').'</td><td class="ui-widget-content">'.$objsoc->ville."</td></tr>";

    // Country
    print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans("Country").'</td><td colspan="3"  class="ui-widget-content">';
    if ($objsoc->isInEEC()) print $form->textwithtooltip($objsoc->pays,$langs->trans("CountryIsInEEC"),1,0);
    else print $objsoc->pays;
    print '</td></tr>';

    // Phone
    print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('Phone').'</td><td class="ui-widget-content">'.dol_print_phone($objsoc->tel,$objsoc->pays_code).'</td>';

    // Fax
    print '<th class=" ui-state-default ui-widget-header ">'.$langs->trans('Fax').'</td><td class="ui-widget-content">'.dol_print_phone($objsoc->fax,$objsoc->pays_code).'</td></tr>';


    //Produit en SAV
    print '<tr><th colspan=4 align="center" class="ui-state-hover ui-widget-header">Le produit en SAV</th></tr>';

    print '<tr><th width="20%" class="ui-state-default ui-widget-header">'.$langs->trans("Ref SAV").'</th><td colspan="3" class="ui-widget-content">';
    print $objsav->getNomUrl(1);


    print '<tr><th width="30%" class="ui-state-default ui-widget-header">'.$langs->trans("Description").'</th><td width="70%" colspan="3" class="ui-widget-content">';
    print $objsav->descriptif_produit;
    print '</td></tr>';

    print '<tr><th width="30%" class="ui-state-default ui-widget-header">'.$langs->trans("Probleme").'</th><td width="70%" colspan="3" class="ui-widget-content">';
    print $objsav->descriptif_probleme;
    print '</td></tr>';

    print '<tr><th width="20%" class="ui-state-default ui-widget-header">'.$langs->trans("Date entr&eacute;e").'</th><td width="30%" colspan="1" class="ui-widget-content">';
    print date("d/m/Y H:i",$objsav->datecEpoch);
    print '</td>';


    if ($objsav->statut == 100)
    {
        print '<th width="20%" class="ui-state-default ui-widget-header">'.$langs->trans("Date fin").'</th><td width="30%" colspan="1" class="ui-widget-content">';
        print date("d/m/Y H:i",$objsav->dateeEpoch);
    } else {

        print '<th width="20%" class="ui-state-default ui-widget-header">'.$langs->trans("Derni&egrave;re mise &agrave; jour").'</th><td width="30%" colspan="1" class="ui-widget-content">';
        print $objsav->getLibStatut(4)."<br>";
        print date("d/m/Y H:i",$objsav->dateModif);

    }
    print '</td></tr>';



    if ($objsav->fk_product > 0)
    {
        print '<tr><th width="30%" class="ui-state-default ui-widget-header">'.$langs->trans("Produit").'</th><td width="0%" colspan="3" class="ui-widget-content">';
        print $objsav->product->getNomUrl(1) . "  ".$objsav->libelle;
        print '</td></tr>';

        print '<tr><th width="30%" class="ui-state-default ui-widget-header">'.$langs->trans("Description produit").'</th><td width="70%" colspan="3" class="ui-widget-content">';
        print $objsav->product->description;
        print '</td></tr>';
//TODO Refaire sans le serial
        //SAV normal + contrat d'extension
        $requete = " SELECT Babel_GMAO_contratdet_prop.durValid, ".MAIN_DB_PREFIX."contratdet.fk_contrat, element_type
                       FROM ".MAIN_DB_PREFIX."contratdet,
                            Babel_product_serial_cont,
                            Babel_GMAO_contratdet_prop
                      WHERE serial_number = '".$objsav->serial."'
                        AND element_type like 'contrat%'
                        AND ".MAIN_DB_PREFIX."contratdet.rowid = Babel_product_serial_cont.element_id
                        AND Babel_GMAO_contratdet_prop.contratdet_refid = ".MAIN_DB_PREFIX."contratdet.rowid ";
//        print $requete;
        $sql1 = $db->query($requete);
        require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
        while($res1=$db->fetch_object($res1))
        {
            $tmpcontrat = new Contrat($db);
            $tmpcontrat->fetch($res1->fk_contrat);
            if ($res1->element_type == 'contratSAV')
            {
                $extSAV = $res1->durValid;
                $totSAV = $objsav->product->durSav + $extSAV;

                print '<tr><th width="30%" class="ui-state-default ui-widget-header">Garantie SAV<br/>(produit + extension)<td width="70%" colspan="3" class="ui-widget-content" >'.$tmpcontrat->getNomUrl(1)."<br>&nbsp;&nbsp;".($objsav->product->durSav>0?$objsav->product->durSav:0) .($extSAV>0?" + " .$extSAV ." = ". $totSAV:""). " mois  ";
            } else {
                $type="Contrat";
                if ($res1->element_type == 'contratMnt')
                {
                    $type="Contrat de maintenance";
                } else if ($res1->element_type == 'contratGA')
                {
                    $type="Contrat de financement";
                } else if ($res1->element_type == 'contratLoc')
                {
                    $type="Contrat de location";
                }
                print '<tr><th width="30%" class="ui-state-default ui-widget-header">'.$type.'<td width="70%" colspan="3" class="ui-widget-content" >'.$tmpcontrat->getNomUrl(1);
            }
        }
        //Contrat de maintenance
    }
//TODO refaire avec lien par element_id et element_type puis retrouver propriété serial
//SAV , contrat ...
    $requete = "SELECT * FROM llx_product_serial_view WHERE serial_number = '".$objsav->serial."'";
    $sql = $db->query($requete);
    while($res=$db->fetch_object($sql))
    {
        $type= $res->element_type;
        if (preg_match('/^contrat/i',$type))
        {
            $type = 'contrat';
            $requete = "SELECT fk_contrat, fk_product FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid = ".$res->element_id;
            $sql1 = $db->query($requete);
            $res1 = $db->fetch_object($sql1);
            require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
            $tmpObj = new Contrat($db);
            $tmpObj->fetch($res1->fk_contrat);
            $ref = $tmpObj->getNomUrl(1);
        } else if ($type=='expedition'){
            $requete = "SELECT ".MAIN_DB_PREFIX."expeditiondet.fk_expedition,
                               ".MAIN_DB_PREFIX."commandedet.fk_product
                          FROM ".MAIN_DB_PREFIX."expeditiondet,
                               ".MAIN_DB_PREFIX."commandedet
                         WHERE ".MAIN_DB_PREFIX."expeditiondet.rowid = ".$res->element_id. "
                           AND ".MAIN_DB_PREFIX."expeditiondet.fk_origin_line = ".MAIN_DB_PREFIX."commandedet.rowid ";
            $sql1 = $db->query($requete);
            $res1 = $db->fetch_object($sql1);
            require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");
            $tmpObj = new Expedition($db);
            $tmpObj->fetch($res1->fk_expedition);
            $ref = $tmpObj->getNomUrl(1);
        } else if ($type=='facture'){
            $requete = "SELECT fk_facture, fk_product FROM ".MAIN_DB_PREFIX."facturedet WHERE rowid = ".$res->element_id;
            $sql1 = $db->query($requete);
            $res1 = $db->fetch_object($sql1);
            require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
            $tmpObj = new Facture($db);
            $tmpObj->fetch($res1->fk_facture);
            $ref = $tmpObj->getNomUrl(1);
        }
        $arrType['expedition']='&Eacute;l&eacute;ment de l\'exp&eacute;dition';
        $arrType['facture']='&Eacute;l&eacute;ment de la facture';
        $arrType['contrat']='&Eacute;l&eacute;ment du contrat';
        print '<tr><th width=30%" class="ui-state-default ui-widget-header">'.$arrType[$type].'<td width="30%" colspan="3" class="ui-widget-content">'.$ref;
    }


    print '<tr><th width="30%" class="ui-state-default ui-widget-header">'.$langs->trans("Dernier message").'</th><td width="70%" colspan="3" class="ui-widget-content">';
    print $objsav->lastMessage;
    print '</td></tr>';
    print "</table>";

    //Historique
    print '<table class="border" width="100%" cellpadding=15>';
    print '<tr><th colspan=5 align="center" class="ui-state-hover ui-widget-header">Historique</th></tr>';
    $objsav->fetchHisto();
    print '<tr><th width="20%" class="ui-state-default ui-widget-header">'.$langs->trans("Date").'</th>';
    print '<th width="30%" class="ui-state-default ui-widget-header">'.$langs->trans("Message").'</th>';
    print '<th width="20%" class="ui-state-default ui-widget-header">'.$langs->trans("Statut Pr&eacute;c.").'</th>';
    print '<th width="20%" class="ui-state-default ui-widget-header">'.$langs->trans("Statut Cour.").'</th>';
    print '<th width="10%" class="ui-state-default ui-widget-header">'.$langs->trans("Interv.").'</th></tr>';
    for ($i=0;$i<count($objsav->histo);$i++)
    {

        print '<tr><td width="20%" align=center colspan="1" class="ui-widget-content">';
        print date('d/m/Y H:i',$objsav->histo[$i]['date']);
        print '</td>';


        print '<td width="30%" align=center colspan="1" class="ui-widget-content">';
        print $objsav->histo[$i]['message'];
        print '</td>';

        print '<td width="20%" align=center colspan="1" class="ui-widget-content">';
        print $objsav->libStatut($objsav->histo[$i]['last_statut'],4);
        print '</td>';

        print '<td width="20%" align=center colspan="1" class="ui-widget-content">';
        print $objsav->libStatut($objsav->histo[$i]['current_statut'],4);
        print '</td>';

        print '<td width="10%" align=center colspan="1" class="ui-widget-content">';
        $tmpUser = new User($db);
        $tmpUser->id = $objsav->histo[$i]['user_author'];
        $tmpUser->fetch();
        print $tmpUser->getNomUrl(1);
        print '</td></tr>';

    }


    print "</table>";
//
//('SAVbrouillon');
//            if ($status == 10) return $langs->trans('SAVenTraitement');
//            if ($status == 20) return $langs->trans('SAVenTraitementExterne');
//            if ($status == 50) return $langs->trans('SAVRepare');
//            if ($status == 80) return $langs->trans('SAVenAttenteClient');
//            if ($status == 100) return $langs->trans('SAVtermine')

    print '<div class="tabsAction">';
    if ($objsav->statut > 0 && $objsav->statut < 100)
    {
            print '<a id="newMsg" href="#" class="butAction">Ajouter un message</a>';
    }
    switch ($objsav->statut)
    {
        case "0":
            print '<a href="fiche.php?id='.$id.'&amp;action=prisecharge" class="butAction">Prendre en charge</a>';
        break;
        case "10":
            print '<a href="fiche.php?id='.$id.'&amp;action=externalise" class="butAction">Externaliser</a>';
            print '<a href="fiche.php?id='.$id.'&amp;action=repare" class="butAction">R&eacute;par&eacute;</a>';
        break;
        case "20":
            print '<a href="fiche.php?id='.$id.'&amp;action=reparer" class="butAction">Classer r&eacute;par&eacute;</a>';
        break;
        case "50":
            print '<a href="fiche.php?id='.$id.'&amp;action=miseadispo" class="butAction">Mise &agrave; disposition client</a>';
        break;
        case "80":
            print '<a href="fiche.php?id='.$id.'&amp;action=termine" class="butAction">Termin&eacute;</a>';
        break;
    }
    print '</div>';
    print "<div id='newMsgDialog'>";
    print "<form id='newMsgForm'>";
    print "<fieldset><legend>Nouveau message</legend>";
    print "<textarea style='height: 120px; width:573px;' id='message' name='message'></textarea>";
    print "</fieldset>";
    print "</form>";
    print '</div>';

    print '<script>';
    print 'var SAVid = '.$id.';';
    print <<<EOF
    jQuery(document).ready(function(){
        jQuery('#newMsg').click(function(){
            jQuery('#newMsgDialog').dialog('open');
        });
        jQuery('#newMsgDialog').dialog({
            modal: true,
            title: "Nouveau message",
            width: 620,
            autoOpen: false,
            buttons: {
                Ok: function(){
                    jQuery.ajax({
                        url: "ajax/SAVnewMessage-xml_response.php",
                        data: "message="+jQuery('#message').val()+"&id="+SAVid,
                        datatype: "xml",
                        type: "POST",
                        cache: false,
                        success: function(msg)
                        {
                            if (jQuery(msg).find('OK').length>0)
                            {
                                location.href='fiche.php?id='+SAVid;
                            }
                        }
                    });
                },
                Annuler: function(){
                    jQuery(this).dialog('close');
                }
            },
            open: function(){
                jQuery('#message').val("");
            }
        });
    });
    </script>
EOF;

?>