<?php
/*
  ** GLE by Synopsis et DRSI
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
    require_once("pre.inc.php");
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
    $msg=false;

    if ($user->societe_id) $socid=$user->societe_id;
    $result=restrictedArea($user,'contrat',$contratid,'contrat');

    $tmp0 = date('Y') - 10;
    $tmp = date('Y')+15;
    $dateRange = $tmp0.":".$tmp;
    // Security check
    $socid = isset($_GET["socid"])?$_GET["socid"]:'';
    if ($user->societe_id) $socid=$user->societe_id;
    $result = restrictedArea($user, 'societe', $socid);
    $idLigne = $_REQUEST["id"];

    $form = new Form($db);
    $html = new Form($db);



    $requete = "SELECT c.fk_contrat, g.type
                  FROM ".MAIN_DB_PREFIX."contratdet as c
             LEFT JOIN Babel_GMAO_contratdet_prop  as g ON c.rowid = g.contratdet_refid
                 WHERE rowid = ".$idLigne;
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $id = $res->fk_contrat;


    if ($_REQUEST['action']=='delete')
    {
        $requete = "DELETE FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid = ".$idLigne;
        $sql = $db->query($requete);
        if ($sql)
        {
            $requete = "DELETE FROM Babel_GMAO_contratdet_prop WHERE contratdet_refid = ".$idLigne;
            $sql = $db->query($requete);
            header('location: '.DOL_URL_ROOT."/contrat/fiche.php?id=".$id);
        } else {
            $msg = "Impossible de supprimer la ligne";
        }
    }


    $isGA = false;
    $isSAV = false;
    $isMaintenance=false;
    $isTicket=false;
    $type = $res->type;

    $js = "<script src='".DOL_URL_ROOT."/Babel_GMAO/js/contratMixte-fiche.js' type='text/javascript'></script>";
    $js .= "<script>";
    $js .= '    var yearRange = "'.$dateRange.'";';
    $js .= '    var userId = '.$user->id.';';
    $js .= '    var idContratCurrent = '.$id.';';
    $js .= '    var g_idContrat = '.$id.';';
    $js .= '    var g_idLigne = '.$idLigne.';';


    $js .= "</script>";
    llxHeader($js,utf8_decode('Détail Contrat'),1);



    if(!$user->rights->contrat->lire){
        accessforbidden();
    }


    if ($id > 0)
    {
        $contrat=getContratObj($id);
        $result=$contrat->fetch($id);
        if ($result > 0) $result=$contrat->fetch_lines(true);
        if ($result < 0)
        {
            dol_print_error($db,$contrat->error);
            exit;
        }

        if ($mesg) print $mesg;

        $nbofservices=sizeof($contrat->lignes);

        $author = new User($db);
        $author->id = $contrat->user_author_id;
        $author->fetch();

        $commercial_signature = new User($db);
        $commercial_signature->id = $contrat->commercial_signature_id;
        $commercial_signature->fetch();

        $commercial_suivi = new User($db);
        $commercial_suivi->id = $contrat->commercial_suivi_id;
        $commercial_suivi->fetch();

        $head = contract_prepare_head($contrat);
        $head = $contrat->getExtraHeadTab($head);

        $h = count($head);
        $head[$h][0] = DOL_URL_ROOT.'/Babel_GMAO/contratDetail.php?id='.$idLigne;
        $head[$h][1] = $langs->trans("D&eacute;tail");
        $head[$h][2] = 'D&eacute;tail';


        $hselected = $h;

        dol_fiche_head($head, $hselected, $langs->trans("Contract"));


        if ($msg)
        {
            print "<div class='ui-state-error error'>".$msg."</div>";
        }


        /*
         *   Contrat
         */
        print "<div class='titre'>Contrat</div>";
        print '<br/>';

        print '<table cellpadding=15 class="border" width="100%">';

        // Ref du contrat
        print '<tr><th width="25%" class="ui-widget-header ui-state-default">'.$langs->trans("Ref").'</th>
                   <td colspan="1" class="ui-widget-content">';
        print $contrat->getNomUrl(1);
        print "</td>";

        // Customer
        print '   <th class="ui-widget-header ui-state-default">'.$langs->trans("Customer").'</th>';
        print '    <td colspan="1" class="ui-widget-content">'.$contrat->societe->getNomUrl(1).'</td></tr>';

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
        print '    <td colspan="3" class="ui-widget-content">'.dol_print_date($contrat->date_contrat,"dayhour")."</td></tr>\n";

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
                $form->form_project("fiche.php?id=$id",$contrat->socid,$contrat->fk_projet,"projetid");
            } else {
                $form->form_project("fiche.php?id=$id",$contrat->socid,$contrat->fk_projet,"none");
            }
            print "</td></tr>";
        }

        //ajoute lien principal
        $contrat->contratCheck_link();

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
                        $comm = new Commande($db);
                        $comm->fetch($val1);
                        if($conf->global->MAIN_MODULE_BABELPREPACOMMANDE == 1){
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
                        $comm = new Commande($db);
                        $result=$comm->fetch($val1);
                        if ($result>0){
                            print '<tr><th class="ui-widget-header ui-state-default">';
                            print 'Commandes associ&eacute;es';
                            print $comm->getNomUrl(1);

                            if($conf->global->MAIN_MODULE_BABELPREPACOMMANDE == 1){
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


        print '<br/>';
        print "<div class='titre'>D&eacute;tail</div>";
        print '<br/>';
        $modify = false;
        $ligne = $contrat->lignes[$idLigne];

        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //////////////////////////////////////////////////      VISUALUSATION      //////////////////////////////////////////////////////////////////////////////
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        print '<div id="modDialog"><span id="modDialog-content">';
        print $contrat->displayDialog('mod',$mysoc,$contrat);
        print "</span></div>";

        print '<table cellpadding=15 class="border" width="100%">';
        switch($type){
            case "2":
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Type</th>';
                $prodContrat = new Product($db);
                $hasProdContrat=false;

                if ($ligne->GMAO_Mixte['fk_contrat_prod'])
                {
                    print '<td colspan="1" width="25%" class="ui-widget-content">Tickets';
                    print '<td colspan="2" width="50%" class="ui-widget-content">';
                    $prodContrat->fetch($ligne->GMAO_Mixte['fk_contrat_prod']);
                    $hasProdContrat=true;
                    print $prodContrat->getNomUrl(1);
                } else {
                    print '<td colspan="1"  width="25%" class="ui-widget-content">Tickets';
                    print '<td colspan="2"  width="50%" class="ui-widget-content">';
                    print $contrat->lignes[$idLigne]->desc;
                }

                print "</td></tr>";
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Prix unit. HT</th>
                           <td  width="25%" colspan="1" class="ui-widget-content">';
                print price ($ligne->GMAO_Mixte['pu']);

                //Total HT
                print '    <th width="25%" class="ui-widget-header ui-state-default">Total HT</th>
                           <td colspan="1" class="ui-widget-content">'.price ($ligne->GMAO_Mixte['pu'] * $ligne->GMAO_Mixte['qty']);
                print "</td></tr>";

                if ($hasProdContrat)
                {
                    $total = intval($ligne->GMAO_Mixte['qty']) * intval($prodContrat->qte);
                    $qteTkt=$ligne->GMAO_Mixte['qty']." x ". $prodContrat->qte ." = ".$total;
                    if($prodContrat->qte == -1) $qteTkt = "Illimit&eacute;";
                    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Quantit&eacute;</th>
                               <td colspan="3" class="ui-widget-content">'.$qteTkt;
                } else {
                    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Quantit&eacute;</th>
                               <td colspan="3" class="ui-widget-content">'.$ligne->GMAO_Mixte['qty'];
                }


                // SLA
                if ($ligne->GMAO_Mixte['SLA']."x" != "x")
                {
                    print '<tr><th width="25%" class="ui-widget-header ui-state-default">SLA</th>';
                    print '<td colspan="3" class="ui-widget-content">'.$ligne->GMAO_Mixte['SLA'];
                }

                // reconduction auto
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Reconduction automatique</th>';
                if ($ligne->GMAO_Mixte['reconductionAuto'] > 0)
                {
                    print '<td colspan="3" class="ui-widget-content">OUI';
                } else {
                    print '<td colspan="3" class="ui-widget-content">NON';
                }

                if ($ligne->GMAO_Mixte['prorata'] == 1){
                    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Total HT 1&egrave;re ann&eacute;e</th>
                               <td colspan="3" class="ui-widget-content">'.price($ligne->GMAO_Mixte['prixAn1']);
                    print "</td></tr>";
                    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Total HT derni&egrave;re ann&eacute;e</th>
                               <td colspan="3" class="ui-widget-content">'.price($ligne->GMAO_Mixte['prixAnDernier']);
                    print "</td></tr>";
                }


                // Clause
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Clause contractuelle</th>';
                print '<td colspan="3" class="ui-widget-content">'.nl2br($ligne->GMAO_Mixte['clause']);

                print "</td></tr>";
            break;
            case "3":
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Type</th>';
                $prodContrat = new Product($db);
                $hasProdContrat=false;
                if ($ligne->GMAO_Mixte['fk_contrat_prod'])
                {
                    print '<td colspan="1" width="25%" class="ui-widget-content">Maintenance';
                    print '<td colspan="2" width="50%" class="ui-widget-content">';
                    $prodContrat->fetch($ligne->GMAO_Mixte['fk_contrat_prod']);
                    $hasProdContrat=true;
                    print $prodContrat->getNomUrl(1);
                } else {
                    print '<td colspan="1"  width="25%" class="ui-widget-content">Maintenance';
                    print '<td colspan="2"  width="50%" class="ui-widget-content">';
                    print $contrat->lignes[$idLigne]->desc;
                }

                print "</td></tr>";

                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Total HT</th>
                           <td colspan="1" class="ui-widget-content">'.price ($ligne->GMAO_Mixte['pu'] * $ligne->GMAO_Mixte['qty']);
                print "</td>";
                // reconduction auto
                print '    <th width="25%" class="ui-widget-header ui-state-default">Reconduction automatique</th>';
                if ($ligne->GMAO_Mixte['reconductionAuto'] > 0)
                {
                    print '<td colspan="1" class="ui-widget-content">OUI';
                } else {
                    print '<td colspan="1" class="ui-widget-content">NON';
                }


                if ($ligne->GMAO_Mixte['prorata'] == 1){
                    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Total HT 1&egrave;re ann&eacute;e</th>
                               <td colspan="1" class="ui-widget-content">'.price($ligne->GMAO_Mixte['prixAn1']);
                    print "    </td>";
                    print '    <th width="25%" class="ui-widget-header ui-state-default">Total HT derni&egrave;re ann&eacute;e</th>
                               <td colspan="1" class="ui-widget-content">'.price($ligne->GMAO_Mixte['prixAnDernier']);
                    print "</td></tr>";
                }

                if ($hasProdContrat)
                {
//                    var_dump::display($prodContrat);
                    $total = intval($ligne->GMAO_Mixte['qty']) * intval( $prodContrat->qte);
                    if ($ligne->GMAO_Mixte['nbVisiteAn'] == 0){
                        print '<tr><th width="25%" class="ui-widget-header ui-state-default">Nb visite annuel</th>
                                   <td colspan="1" class="ui-widget-content"><b>Pas d\'intervention sur site</b>';
                    } else {
                        print '<tr><th width="25%" class="ui-widget-header ui-state-default">Nb visite annuel</th>
                                   <td colspan="1" class="ui-widget-content">'.$ligne->GMAO_Mixte['nbVisiteAn'];
                    }
                } else {
                    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Quantit&eacute;</th>
                               <td colspan="1" class="ui-widget-content">'.$ligne->GMAO_Mixte['qty'];
                }
                print "</td>";

                print '    <th width="25%" class="ui-widget-header ui-state-default">Tickets';
                    print '<br>';
                    if ($ligne->GMAO_Mixte['qteTempsPerDuree'] == 0){
                        print ' '.$ligne->GMAO_Mixte['qteTktPerDuree'].' ticket(s) par intervention';
                    } else {
                        $txtDur = "";
                        $arrDur = $contrat->convDur($ligne->GMAO_Mixte['qteTempsPerDuree']);
                        $txtDur = $arrDur['hours']['abs']." heure".($arrDur['hours']['abs']>1?"s":"")." ".($arrDur['minutes']['rel']>0?"et ".$arrDur['minutes']['rel']." minute".($arrDur['minutes']['rel']>1?"s":""):"");
                        print ' '.$ligne->GMAO_Mixte['qteTktPerDuree'].' ticket(s) pour '.$txtDur." d'intervention";
                    }

                print '    <td colspan="1" class="ui-widget-content">';

                $arrIntervRestant = $contrat->intervRestant($ligne);
                $consomme=$arrIntervRestant['consomme'];
                $restant = $arrIntervRestant['restant'];
                if ($ligne->GMAO_Mixte['tickets'] == -1) print 'Illimit&eacute; '.$consomme. " consomm&eacute; ";
                if ($restant < 0)
                {
                    if ($ligne->GMAO_Mixte['tickets'] > 0) print '<table class="noborder" width=100%><tr><td align=center valign=middle rowspan=2><span class="ui-state-error" style="border:0;"><span class="ui-icon ui-icon-info"></span><td><b>'.$ligne->GMAO_Mixte['tickets']. "</b> initiaux <tr><td> <b><font style='font-size:10pt;' class='error'>".$restant."</b> restants</font></span></table>";
                } else if ($restant < $conf->global->GMAO_TKT_RESTANT_WARNING)
                {
                    if ($ligne->GMAO_Mixte['tickets'] > 0) print '<table class="noborder" width=100%><tr><td align=center valign=middle rowspan=2><span class="ui-state-highlight" style="border:0"><span class="ui-icon ui-icon-info"></span><td><b>'.$ligne->GMAO_Mixte['tickets']. "</b> initiaux <tr><td> <font style='font-size:9pt;' class='highlight'><b>".$restant."</b> restants</span></table>";
                } else {
                    if ($ligne->GMAO_Mixte['tickets'] > 0) print '<b>'.$ligne->GMAO_Mixte['tickets']. "</b> initiaux <br/> <b>".$restant."</b> restants";
                }
                if ($ligne->GMAO_Mixte['tickets'] == 0) print 'Pas de tickets';


//                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Maintenance</th>';

//                // Maintenance
//                if ($ligne->GMAO_Mixte['maintenance']> 0)
//                {
//                    print '<td colspan="3" class="ui-widget-content">OUI';
//                } else {
//                    print '<td colspan="3" class="ui-widget-content">NON';
//                }

                //Hotline / telemaintenance
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Hotline</th>';
                if ($ligne->GMAO_Mixte['hotline'] > 0)
                {
                    print '<td colspan="1" class="ui-widget-content"><b>OUI</b>';
                } else {
                    print '<td colspan="1" class="ui-widget-content">NON';
                }
                print '    <th width="25%" class="ui-widget-header ui-state-default">T&eacute;l&eacute;maintenance</th>';
                if ($ligne->GMAO_Mixte['telemaintenance'] > 0)
                {
                    print '<td colspan="1" class="ui-widget-content"><b>OUI</b>';
                } else {
                    print '<td colspan="1" class="ui-widget-content">NON';
                }
                // SLA
                if ($ligne->GMAO_Mixte['SLA']."x" != "x")
                {
                    print '<tr><th width="25%" class="ui-widget-header ui-state-default">SLA</th>';
                    print '<td colspan="3" class="ui-widget-content">'.$ligne->GMAO_Mixte['SLA'];
                }

                // Clause
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Clause contractuelle</th>';
                print '<td colspan="3" class="ui-widget-content">'.nl2br($ligne->GMAO_Mixte['clause']);
            break;
            case "4": //SAV
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Type</th>';
                $prodContrat = new Product($db);
                $hasProdContrat=false;
                if ($ligne->GMAO_Mixte['fk_contrat_prod'])
                {
                    print '<td colspan="1" width="25%" class="ui-widget-content">SAV';
                    print '<td colspan="2" width="50%" class="ui-widget-content">';
                    $prodContrat->fetch($ligne->GMAO_Mixte['fk_contrat_prod']);
                    $hasProdContrat=true;
                    print $prodContrat->getNomUrl(1);
                } else {
                    print '<td colspan="1"  width="25%" class="ui-widget-content">SAV';
                    print '<td colspan="2"  width="50%" class="ui-widget-content">';
                    print $contrat->lignes[$idLigne]->desc;
                }
                // SLA
                if ($ligne->GMAO_Mixte['SLA']."x" != "x")
                {
                    print '<tr><th width="25%" class="ui-widget-header ui-state-default">SLA</th>';
                    print '<td colspan="3" class="ui-widget-content">'.$ligne->GMAO_Mixte['SLA'];
                }
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Prix HT</th>
                           <td  width="25%" colspan="1" class="ui-widget-content">';
                print price ($ligne->GMAO_Mixte['pu']);

                // reconduction auto
                print '    <th width="25%" class="ui-widget-header ui-state-default">Reconduction automatique</th>';
                if ($ligne->GMAO_Mixte['reconductionAuto'] > 0)
                {
                    print '<td colspan="1" class="ui-widget-content">OUI';
                } else {
                    print '<td colspan="1" class="ui-widget-content">NON';
                }

                print "</td></tr>";

                if ($ligne->GMAO_Mixte['prorata'] == 1){
                    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Total HT 1&egrave;re ann&eacute;e</th>
                               <td colspan="3" class="ui-widget-content">'.price($ligne->GMAO_Mixte['prixAn1']);
                    print "</td></tr>";
                    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Total HT derni&egrave;re ann&eacute;e</th>
                               <td colspan="3" class="ui-widget-content">'.price($ligne->GMAO_Mixte['prixAnDernier']);
                    print "</td></tr>";
                }

                print "</td></tr>";
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Produit</th>
                           <td width="25%" colspan="1" class="ui-widget-content">';
                if ($contrat->lignes[$idLigne]->fk_product>0)
                {
                    print $contrat->lignes[$idLigne]->product->getNomUrl(1);
                }
                print "    </td>";
                print '    <th width="25%" class="ui-widget-header ui-state-default">Num de s&eacute;rie</th>
                           <td colspan="1" width="25%" class="ui-widget-content">'.$ligne->GMAO_Mixte['serial_number'];
                print "</td></tr>";
                // Clause
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Clause contractuelle</th>';
                print '<td colspan="3" class="ui-widget-content">'.$ligne->GMAO_Mixte['clause'];

            break;
            default:
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Type</th>
                           <td colspan="3" class="ui-widget-content">Divers';
                print "</td></tr>";
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Description</th>
                           <td colspan="3" class="ui-widget-content">';
                print $contrat->lignes[$idLigne]->desc;
                print "</td></tr>";
                // SLA
                if ($ligne->GMAO_Mixte['SLA']."x" != "x")
                {
                    print '<tr><th width="25%" class="ui-widget-header ui-state-default">SLA</th>';
                    print '<td colspan="3" class="ui-widget-content">'.$ligne->GMAO_Mixte['SLA'];
                }

                // reconduction auto
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Reconduction automatique</th>';
                if ($ligne->GMAO_Mixte['reconductionAuto'] > 0)
                {
                    print '<td colspan="3" class="ui-widget-content">OUI';
                } else {
                    print '<td colspan="3" class="ui-widget-content">NON';
                }
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Prix Unitaire</th>
                           <td colspan="3" class="ui-widget-content">';
                print price($contrat->lignes[$idLigne]->subprice);
                print "</td></tr>";
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Quantit&eacute;</th>
                           <td colspan="3" class="ui-widget-content">';
                print $contrat->lignes[$idLigne]->qty;
                print "</td></tr>";
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Total HT</th>
                           <td colspan="3" class="ui-widget-content">';
                print price($contrat->lignes[$idLigne]->total_ht);

                if ($ligne->GMAO_Mixte['prorata'] == 1){
                    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Total HT 1&egrave;re ann&eacute;e</th>
                               <td colspan="3" class="ui-widget-content">'.price($ligne->GMAO_Mixte['prixAn1']);
                    print "</td></tr>";
                    print '<tr><th width="25%" class="ui-widget-header ui-state-default">Total HT derni&egrave;re ann&eacute;e</th>
                               <td colspan="3" class="ui-widget-content">'.price($ligne->GMAO_Mixte['prixAnDernier']);
                    print "</td></tr>";
                }

                print "</td></tr>";
                $requete = "SELECT * FROM Babel_product_serial_cont WHERE element_id = ".$idLigne;
                $sqlsn = $db->query($requete);
                if ($db->num_rows($sqlsn) > 0)
                {
                    $ressn = $db->fetch_object($sqlsn);
                    if ($contrat->lignes[$idLigne]->fk_product>0)
                    {
                        print '<tr><th width="25%" class="ui-widget-header ui-state-default">Produit</th>';
                        print '<td width="25%" colspan="1" class="ui-widget-content">';
                        print $contrat->lignes[$idLigne]->product->getNomUrl(1);
                        print "    </td>";
                        print '<th width="25%" class="ui-widget-header ui-state-default">Num de s&eacute;rie</th>';
                        print '<td width="25%" colspan="1" class="ui-widget-content">';
                        print $ressn->serial_number ;
                        print "    </td>";
                    } else {
                        print '<tr><th width="25%" class="ui-widget-header ui-state-default">>Num de s&eacute;rie</th>';
                        print '<td width="75%" colspan="3" class="ui-widget-content">';
                        print $ressn->serial_number;
                        print "    </td>";
                    }
                } else {
                    if ($contrat->lignes[$idLigne]->fk_product>0)
                    {
                        print '<tr><th width="25%" class="ui-widget-header ui-state-default">Produit</th>';
                        print '<td width="75%" colspan="3" class="ui-widget-content">';
                        print $contrat->lignes[$idLigne]->product->getNomUrl(1);
                        print "    </td>";
                    }
                }
                // Clause
                print '<tr><th width="25%" class="ui-widget-header ui-state-default">Clause contractuelle</th>';
                print '<td colspan="3" class="ui-widget-content">'.$ligne->GMAO_Mixte['clause'];
            break;
        }

        print "<tr><th class='ui-widget-header' colspan=4>
                        <button id='NouvDI' class='butAction'>Nouv. DI</button>
                        <button id='NouvFI' class='butAction'>Nouv. FI</button>";
        if ($user->rights->contrat->supprimer && $contrat->lignes[$idLigne]->statut==0)
            print "         <button id='supprLigne' class='butActionDelete'>Supprimer</button>";
        print "    </th>";
        print "</table>";
        print "<div id='delDialog'>&Ecirc;tes vous sur de vouloir supprimer <b><span class='ui-state-error'>d&eacute;finitivement</span></b> cette ligne ?</div>";
        print "<script>";
        print 'var socid = "'.$contrat->societe->id.'";';
        print 'var fk_contrat = "'.$contrat->id.'";';
        print <<<EOF
    jQuery(document).ready(function(){
        jQuery('#NouvDI').click(function(){
            location.href=DOL_URL_ROOT+"/Synopsis_DemandeInterv/fiche.php?action=create&leftmenu=ficheinter&fk_contrat="+fk_contrat+"&socid="+socid;
        });
        jQuery('#supprLigne').click(function(){
            jQuery('#delDialog').dialog('open');
        });
        jQuery('#NouvFI').click(function(){
            location.href=DOL_URL_ROOT+"/fichinter/fiche.php?action=create&leftmenu=ficheinter&fk_contrat="+fk_contrat+"&socid="+socid;
        });
        jQuery('#delDialog').dialog({
            buttons: {
                OK: function(){
EOF;
             print "location.href='contratDetail.php?id=".$_REQUEST['id']."&action=delete';";
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


?>