<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 4 nov. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : contratDetail.php
 * GLE-1.2
 */

if(!isset($_REQUEST['action']))
    $_REQUEST['action'] = '';

require_once("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php');
if ($conf->projet->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
if ($conf->propal->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
if ($conf->contrat->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Contrat/class/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");


$langs->load("contracts");
$langs->load("orders");
$langs->load("companies");
$langs->load("bills");
$langs->load("products");
//var_dump($_REQUEST);
// Security check
$msg = false;


$tmp0 = date('Y') - 10;
$tmp = date('Y') + 15;
$dateRange = $tmp0 . ":" . $tmp;
// Security check
$socid = isset($_GET["socid"]) ? $_GET["socid"] : '';

$idLigne = $_REQUEST["id"];
$contratLn = new Synopsis_ContratLigne($db);
$contratLn->fetch($idLigne);

$form = new Form($db);
$html = new Form($db);



$requete = "SELECT c.fk_contrat,  g.type  "
        . " FROM " . MAIN_DB_PREFIX . "contratdet as c
           LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO  as g ON c.rowid = g.contratdet_refid   
                 WHERE c.rowid = " . $idLigne;
$sql = $db->query($requete);
$res = $db->fetch_object($sql);
$id = $res->fk_contrat;

$result = restrictedArea($user, 'contrat', $id, 'contrat');


if ($_REQUEST['action'] == 'delete') {
    $requete = "DELETE FROM " . MAIN_DB_PREFIX . "contratdet WHERE rowid = " . $idLigne;
    $sql = $db->query($requete);
    if ($sql) {
        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO WHERE contratdet_refid = " . $idLigne;
        $sql = $db->query($requete);
        header('location: ' . DOL_URL_ROOT . "/contrat/card.php?id=" . $id);
    } else {
        $msg = "Impossible de supprimer la ligne";
    }
}


$isGA = false;
$isSAV = false;
$isMaintenance = false;
$isTicket = false;
$type = $res->type;

$product1 = new Product($db);
$product2 = new Product($db);
$hasProd1 = false;
$hasProd2 = false;
if ($contratLn->fk_product) {
    $product1->fetch($contratLn->fk_product);
//    $type = $product->type;
    $hasProd1 = true;
}
if ($contratLn->GMAO_Mixte['fk_prod']) {
    $product2->fetch($contratLn->GMAO_Mixte['fk_prod']);
//    $type = $product->type;
    $hasProd2 = true;
}

//$type = 2;


$js = "<script src='" . DOL_URL_ROOT . "/Synopsis_Contrat/js/contratMixte-fiche.js' type='text/javascript'></script>";
$js .= '<script language="javascript" src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.validate.js"></script>' . "\n";
$js .= "<script>";
$js .= '    var yearRange = "' . $dateRange . '";';
$js .= '    var userId = ' . $user->id . ';';
$js .= '    var idContratCurrent = ' . $id . ';';
$js .= '    var g_idContrat = ' . $id . ';';
$js .= '    var g_idLigne = ' . $idLigne . ';';


$js .= "</script>";
llxHeader($js, utf8_encodeRien('Détail Contrat'), 1);



if (!$user->rights->contrat->lire) {
    accessforbidden();
}


if ($id > 0) {
    $contrat = getContratObj($id);
    $result = $contrat->fetch($id);
    if ($result > 0)
        $result = $contrat->fetch_lines(true);
    if ($result < 0) {
        dol_print_error($db, $contrat->error);
        exit;
    }

    if ($mesg)
        print $mesg;

    $nbofservices = sizeof($contrat->lines);

    $author = new User($db);
    $author->fetch($contrat->user_author_id);

    $commercial_signature = new User($db);
    $commercial_signature->fetch($contrat->commercial_signature_id);

    $commercial_suivi = new User($db);
    $commercial_suivi->fetch($contrat->commercial_suivi_id);

    $head = contract_prepare_head($contrat);
//    $head = $contrat->getExtraHeadTab($head);

    $h = count($head);
    $head[$h][0] = DOL_URL_ROOT . '/Synopsis_Contrat/contratDetail.php?id=' . $idLigne;
    $head[$h][1] = $langs->trans("D&eacute;tail");
    $head[$h][2] = 'D&eacute;tail';


    $hselected = $h;

    dol_fiche_head($head, $hselected, $langs->trans("Contract"));


    if ($msg) {
        print "<div class='ui-state-error error'>" . $msg . "</div>";
    }


    /*
     *   Contrat
     */
    print "<div class='titre'>Contrat</div>";
    print '<br/>';

    print '<table cellpadding=15 class="border" width="100%">';

    // Ref du contrat
    print '<tr><th width="25%" class="ui-widget-header ui-state-default">' . $langs->trans("Ref") . '</th>
                   <td colspan="1" class="ui-widget-content">';
    print $contrat->getNomUrl(1);
    $societe = new Societe($db);
    $societe->fetch($contrat->socid);
    print "</td>";

    // Customer
    print '   <th class="ui-widget-header ui-state-default">' . $langs->trans("Customer") . '</th>';
    print '    <td colspan="1" class="ui-widget-content">' . $societe->getNomUrl(1) . '</td></tr>';

    // Statut contrat
    print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Status") . '</th>
                   <td colspan="1" class="ui-widget-content" id="statutPanel">';
    if ($contrat->statut == 0)
        print $contrat->getLibStatut(2);
    else
        print $contrat->getLibStatut(4);
    print "</td>";
    //Type contrat
    print '    <th class="ui-widget-header ui-state-default">' . $langs->trans("Type") . '</th>
                   <td colspan="1" class="ui-widget-content" id="typePanel">';
    $arrTmpType = $contrat->getTypeContrat();
    print $arrTmpType['Nom'];
    print "</td></tr>";

    // Date
    print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Date") . '</th>';
    print '    <td colspan="3" class="ui-widget-content">' . dol_print_date($contrat->date_contrat, "dayhour") . "</td></tr>\n";

    // Projet
    if ($conf->projet->enabled) {
        $langs->load("projects");
        print '<tr><th class="ui-widget-header ui-state-default">';
        print $langs->trans("Project");
        if ($_REQUEST["action"] != "classer" && $user->rights->projet->creer)
            print '<span style="float:right;"><a href="' . $_SERVER["PHP_SELF"] . '?action=classer&amp;id=' . $id . '">' . img_edit($langs->trans("SetProject")) . '</a></span>';
        print '</th><td colspan="3" class="ui-widget-content">';
        if ($_REQUEST["action"] == "classer") {
            $form->form_project("card.php?id=$id", $contrat->socid, $contrat->fk_projet, "projetid");
        } else {
            $form->form_project("card.php?id=$id", $contrat->socid, $contrat->fk_projet, "none");
        }
        print "</td></tr>";
    }

    //ajoute lien principal
    $tabLiked = getElementElement(NULL, "contrat", NULL, $_REQUEST['id']);
    $tabLiked = array_merge($tabLiked, getElementElement("contrat", NULL, $_REQUEST['id']));
    $contrat->getHtmlLinked($tabLiked);
//        $contrat->contratCheck_link();
//
//        if ($contrat->linkedTo)
//        {
//            if (preg_match('/^([c|p|f]{1})([0-9]*)/',$contrat->linkedTo,$arr))
//            {
//                print '<tr><th class="ui-widget-header ui-state-default"><table class="nobordernopadding" style="width:100%;">';
//                print '<tr><th style="border:0" class="ui-widget-header ui-state-default">Contrat associ&eacute; &agrave; ';
//                $val1 = $arr[2];
//                switch ($arr[1])
//                {
//                    case 'c':
//                        print 'la commande<td>';
//                        print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=chSrc&amp;id='.$id.'">'.img_edit($langs->trans("Change la source")).'</a>';
//                        require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
//                        $comm = new Commande($db);
//                        $comm->fetch($val1);
//                        if($conf->global->MAIN_MODULE_SYNOPSISPREPACOMMANDE == 1){
//                            print "</table><td colspan=1 class='ui-widget-content'>".$comm->getNomUrl(1);
//                            print "<th class='ui-widget-header ui-state-default'>Prepa. commande";
//                            print "<td colspan=1 class='ui-widget-content'>".$comm->getNomUrl(1,5);
//                        } else {
//                            print "</table><td colspan=3 class='ui-widget-content'>".$comm->getNomUrl(1);
//                        }
//
//                    break;
//                    case 'f':
//                        print 'la facture<td>';
//                        print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=chSrc&amp;id='.$id.'">'.img_edit($langs->trans("Change la source")).'</a>';
//                        require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
//                        $fact = new Facture($db);
//                        $fact->fetch($val1);
//                        print "</table><td colspan=3 class='ui-widget-content'>".$fact->getNomUrl(1);
//                    break;
//                    case 'p':
//                        print 'la proposition<td>';
//                        print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=chSrc&amp;id='.$id.'">'.img_edit($langs->trans("Change la source")).'</a>';
//                        require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
//                        $prop = new Propal($db);
//                        $prop->fetch($val1);
//                        print "</table><td colspan=3 class='ui-widget-content'>".$prop->getNomUrl(1);
//                    break;
//                }
//            }
//        }
//
//        //ajoute le lien vers les propal / commande / facture
//        foreach($contrat->linkedArray as $key=>$val)
//        {
//            if ($key=='co')
//            {
//                foreach($val as $key1=>$val1)
//                {
//                    if ($val1 > 0)
//                    {
//                        require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
//                        $comm = new Commande($db);
//                        $result=$comm->fetch($val1);
//                        if ($result>0){
//                            print '<tr><th class="ui-widget-header ui-state-default">';
//                            print 'Commandes associ&eacute;es';
//                            print $comm->getNomUrl(1);
//
//                            if($conf->global->MAIN_MODULE_SYNOPSISPREPACOMMANDE == 1){
//                                print "<td colspan=1 class='ui-widget-content'>".$comm->getNomUrl(1);
//                                print "<th class='ui-widget-header ui-state-default'>Prepa. commande";
//                                print "<td colspan=1 class='ui-widget-content'>".$comm->getNomUrl(1,5);
//                            } else {
//                                print "<td colspan=3 class='ui-widget-content'>".$comm->getNomUrl(1);
//                            }
//
//                        }
//                    }
//                }
//            } else if ($key=='fa') {
//                foreach($val as $key1=>$val1)
//                {
//                        require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
//                        $fac = new Facture($db);
//                        $result=$fac->fetch($val1);
//                        if ($result>0){
//                            print '<tr><th class="ui-widget-header ui-state-default">';
//                            print 'Factures associ&eacute;es<td colspan=3 class="ui-widget-content">';
//                            print $fac->getNomUrl(1);
//                        }
//                }
//            } else if ($key=='pr') {
//                foreach($val as $key1=>$val1)
//                {
//                        require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
//                        $prop = new Propal($db);
//                        $result=$prop->fetch($val1);
//                        if ($result>0){
//                            print '<tr><th class="ui-widget-header ui-state-default">';
//                            print 'Propositions associ&eacute;es<td colspan=3 class="ui-widget-content">';
//                            print $prop->getNomUrl(1);
//                        }
//                 }
//            }
//        }
//        print '</tr>';

    print $contrat->displayExtraInfoCartouche();
    print "</table>";


    print '<br/>';
    print "<div class='titre'>D&eacute;tail</div>";
    print '<br/>';
    $modify = false;
    $ligne = $contratLn;

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////      VISUALUSATION      //////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    if (isset($conf->global->MAIN_MODULE_BABELGA)) {
        print '<div id="modDialog"><span id="modDialog-content">';
        print $contrat->displayDialog('mod', $mysoc, $contrat);
        print "</span></div>";
    }


    /* deb mod drsi */
    print '<table cellpadding=15 class="border" width="100%">';
    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Type</th>
                <td colspan="1" width="25%" class="ui-widget-content">';


    switch ($type) {
        case "2":
            print 'Tickets';
            $isTicket = true;
            break;
        case "3":
            print 'Maintenance';
            break;
        case "4": //SAV
            print 'SAV';
            break;
        default:
            print 'Divers';
            break;
    }
    print '<td colspan="2" width="50%" class="ui-widget-content">';
    if ($hasProd1)
        print $product1->getNomUrl(1) . "  " . $product1->description . "<br/>";
        print $contratLn->description;
    print "</td></tr>";
    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Prix unit. HT</th>
                           <td  width="25%" colspan="1" class="ui-widget-content">';
    print price($ligne->subprice);

    //Total HT
    print '    <th width="25%" class="ui-widget-header ui-state-default">Total HT</th>
                           <td colspan="1" class="ui-widget-content">' . price($ligne->subprice * $ligne->qty);
    print "</td></tr>";

    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Quantit&eacute;</th>
                               <td colspan="3" class="ui-widget-content">';
    $qte = $ligne->qty;
    if ($isTicket) {
        $qteTkt = $ligne->qty . " x " . $product1->qte . " = " . (intval($ligne->qty) * intval($product1->qte));
        if ($product1->qte == -1)
            $qteTkt = "Illimit&eacute;";
        $qte = $qteTkt;
    }
    print $qte;

    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Nb visite annuel</th>
                                   <td colspan="1" class="ui-widget-content">';
    if ($ligne->GMAO_Mixte['nbVisiteAn'] == 0) {
        print '<b>Pas d\'intervention sur site</b>';
    } else {
        print $ligne->GMAO_Mixte['nbVisiteAn'];
    }
    
    

    print '<th width="25%" class="ui-widget-header ui-state-default">Nb visite curative</th>
                                   <td colspan="1" class="ui-widget-content">';
    if ($ligne->GMAO_Mixte['nbVisiteAnCur'] == 0) {
        print '<b>Pas d\'intervention curative</b>';
    } else {
        print $ligne->GMAO_Mixte['nbVisiteAnCur'];
    }
    
    
    print '<tr><th width="25%" class="ui-widget-header ui-state-default">T&eacute;l&eacute;maintenance</th>';
    if ($ligne->GMAO_Mixte['telemaintenance'] > 0) {
        print '<td colspan="1" class="ui-widget-content"><b>OUI</b> '.$ligne->GMAO_Mixte['telemaintenance'];
    } else {
        print '<td colspan="1" class="ui-widget-content">NON';
    }
    
    
    print '<th width="25%" class="ui-widget-header ui-state-default">T&eacute;l&eacute;maintenance Curative</th>';
    if ($ligne->GMAO_Mixte['telemaintenanceCur'] > 0) {
        print '<td colspan="1" class="ui-widget-content"><b>OUI</b> '.$ligne->GMAO_Mixte['telemaintenanceCur'];
    } else {
        print '<td colspan="1" class="ui-widget-content">NON';
    }


    if ($isTicket) {
        print '    <th width="25%" class="ui-widget-header ui-state-default">Tickets';
        print '<br>';
        if ($ligne->GMAO_Mixte['qteTempsPerDuree'] == 0) {
            print ' ' . $ligne->GMAO_Mixte['qteTktPerDuree'] . ' ticket(s) par intervention';
        } else {
            $txtDur = "";
            $arrDur = convDur($ligne->GMAO_Mixte['qteTempsPerDuree']);
            $txtDur = $arrDur['hours']['abs'] . " heure" . ($arrDur['hours']['abs'] > 1 ? "s" : "") . " " . ($arrDur['minutes']['rel'] > 0 ? "et " . $arrDur['minutes']['rel'] . " minute" . ($arrDur['minutes']['rel'] > 1 ? "s" : "") : "");
            print ' ' . $ligne->GMAO_Mixte['qteTktPerDuree'] . ' ticket(s) pour ' . $txtDur . " d'intervention";
        }

        print '    <td colspan="1" class="ui-widget-content">';

        $arrIntervRestant = $contrat->intervRestant($ligne);
        $consomme = $arrIntervRestant['consomme'];
        $restant = $arrIntervRestant['restant'];

        if ($ligne->GMAO_Mixte['tickets'] == -1)
            print 'Illimit&eacute; ' . $consomme . " consomm&eacute; ";
        else if ($ligne->GMAO_Mixte['tickets'] > 0 && $restant < 0)
            print '<table class="noborder" width=100%><tr><td align=center valign=middle rowspan=2><span class="ui-state-error" style="border:0;"><span class="ui-icon ui-icon-info"></span><td><b>' . $ligne->GMAO_Mixte['tickets'] . "</b> initiaux <tr><td> <b><font style='font-size:10pt;' class='error'>" . $restant . "</b> restants</font></span></table>";
        else if ($ligne->GMAO_Mixte['tickets'] > 0 && $restant < $conf->global->GMAO_TKT_RESTANT_WARNING)
            print '<table class="noborder" width=100%><tr><td align=center valign=middle rowspan=2><span class="ui-state-highlight" style="border:0"><span class="ui-icon ui-icon-info"></span><td><b>' . $ligne->GMAO_Mixte['tickets'] . "</b> initiaux <tr><td> <font style='font-size:9pt;' class='highlight'><b>" . $restant . "</b> restants</span></table>";
        else if ($ligne->GMAO_Mixte['tickets'] > 0)
            print '<b>' . $ligne->GMAO_Mixte['tickets'] . "</b> initiaux <br/> <b>" . $restant . "</b> restants";
        else
            print 'Pas de tickets';
    }

    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Hotline</th>';
    if ($ligne->GMAO_Mixte['hotline'] > 0) {
        print '<td colspan="1" class="ui-widget-content"><b>OUI</b>';
    } else {
        print '<td colspan="1" class="ui-widget-content">NON';
    }

    // reconduction auto
    print '    <tr><th width="25%" class="ui-widget-header ui-state-default">Reconduction automatique</th>';
    if ($ligne->GMAO_Mixte['reconductionAuto'] > 0)
        print '<td colspan="1" class="ui-widget-content">OUI';
    else
        print '<td colspan="1" class="ui-widget-content">NON';

    // SLA
    if ($ligne->SLA . "x" != "x") {
        print '<tr><th width="25%" class="ui-widget-header ui-state-default">SLA</th>';
        print '<td colspan="3" class="ui-widget-content">' . $ligne->SLA;
    }

    if ($ligne->GMAO_Mixte['prorata'] == 1) {
        print '<tr><th width="25%" class="ui-widget-header ui-state-default">Total HT 1&egrave;re ann&eacute;e</th>
                               <td colspan="3" class="ui-widget-content">' . price($ligne->GMAO_Mixte['prixAn1']);
        print "</td></tr>";
        print '<tr><th width="25%" class="ui-widget-header ui-state-default">Total HT derni&egrave;re ann&eacute;e</th>
                               <td colspan="3" class="ui-widget-content">' . price($ligne->GMAO_Mixte['prixAnDernier']);
        print "</td></tr>";
    }

//    if ($hasProd2) {
//        print '<tr><th width="25%" class="ui-widget-header ui-state-default">Produit</th>';
//        print '<td width="75%" colspan="3" class="ui-widget-content">';
//        print $product2->getNomUrl(1);
//        print "    </td>";
//    }
//    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont WHERE element_id = " . $idLigne;
//    $sqlsn = $db->query($requete);
//    if ($db->num_rows($sqlsn) > 0) {
//        $ressn = $db->fetch_object($sqlsn);
//        print '<tr><th width="25%" class="ui-widget-header ui-state-default">Num de s&eacute;rie</th>';
//        print '<td width="75%" colspan="3" class="ui-widget-content">';
//        print $ressn->serial_number;
//        print "    </td>";
//    }
    // Clause
    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Clause contractuelle</th>';
    print '<td colspan="3" class="ui-widget-content">' . $ligne->GMAO_Mixte['clause'];
    
    
    // ProductCli
    $_REQUEST['chrono_id'] = $_REQUEST['id'];
    $lien = new lien($db);
    $lien->socid = $contrat->socid;
    $lien->cssClassM = "type:contratdet";
    $lien->fetch(3);
//        $prodHtml .= $val."<br/>";
//    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Produits concerné</th>';
//    print '<td colspan="3" class="ui-widget-content">';
//    $lien->displayValue();
    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Produits concerné</th>';
    print '<td colspan="3" class="ui-widget-content">';
    print $lien->displayForm();
    print "<script type='text/javascript' src='" . DOL_URL_ROOT . "/synopsischrono/fiche.js'></script>";





    print "<tr><th class='ui-widget-header' colspan=4>
                        <button id='NouvDI' class='butAction'>Nouv. DI</button>
                        <button id='NouvFI' class='butAction'>Nouv. FI</button>";
    if ($user->rights->contrat->supprimer && $contratLn->statut == 0)
        print "         <button id='supprLigne' class='butActionDelete'>Supprimer</button>";
    print "    </th>";
    print "</table>";
    print "<div id='delDialog'>&Ecirc;tes vous sur de vouloir supprimer <b><span class='ui-state-error'>d&eacute;finitivement</span></b> cette ligne ?</div>";
    print "<script>
    jQuery(document).ready(function(){";
    print 'var socid = "' . $contrat->socid . '";';
    print 'var fk_contrat = "' . $contrat->id . '";';
    print 'var fk_contratdet = "' . $_REQUEST['id'] . '";';
    print <<<EOF
        jQuery('#NouvDI').click(function(){
            location.href=DOL_URL_ROOT+"/synopsisdemandeinterv/card.php?action=create&leftmenu=ficheinter&fk_contrat="+fk_contrat+"&fk_contratdet="+fk_contratdet+"&socid="+socid;
        });
        jQuery('#supprLigne').click(function(){
            jQuery('#delDialog').dialog('open');
        });
        jQuery('#NouvFI').click(function(){
            location.href=DOL_URL_ROOT+"/synopsisfichinter/card.php?action=create&leftmenu=ficheinter&fk_contrat="+fk_contrat+"&fk_contratdet="+fk_contratdet+"&socid="+socid;
        });
        jQuery('#delDialog').dialog({
            buttons: {
                OK: function(){
EOF;
    print "location.href='contratDetail.php?id=" . $_REQUEST['id'] . "&action=delete';";
    print <<<EOF
                },
                Annuler: function(){
                    jQuery(this).dialog('close');
                }
            },
            modal: true,
            autoOpen: false,
            title: "Suppression ligne de contrat",
            minWidth: 540,
            width: 540,
        });
    });
</script>

EOF;
}
print '<br>';
llxFooter();
?>