<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 23 fevr. 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : listProcessForElement.php
  * GLE-1.2
  */

    require_once("pre.inc.php");
    require_once(DOL_DOCUMENT_ROOT."/Synopsis_Process/process.class.php");
    $id = $_REQUEST['id'];
    $type = $_REQUEST['type'];
//        require_once('Var_Dump.php');
//        var_dump::display($user->rights);

    // Securite acces client
    $socid=0;
    if ($user->societe_id > 0)
    {
        $socid = $user->societe_id;
    }
    switch($type){
        case  'Commande':{
            if (!$user->rights->commande->lire) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
                $obj = new Commande($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'Propal':{
            if (!$user->rights->commande->lire) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
                $obj = new Propal($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'Facture':{
            if (!$user->rights->facture->lire) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
                $obj = new Facture($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'Expedition':{
            if (!$user->rights->expedition->lire) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");
                $obj = new Expedition($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'ActionComm':{
            if (!$user->rights->expedition->lire) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
                $obj = new ActionComm($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'Affaire':{
            if (!$user->rights->affaire->lire) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/Babel_Affaire/Affaire.class.php");
                $obj = new Affaire($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'FactureFournisseur':{
            //var_dump($user->rights->fournisseur->facture->lire);
            if (!$user->rights->fournisseur->facture->lire) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
                $obj = new FactureFournisseur($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'CommandeFournisseur':{
            //var_dump($user->rights->fournisseur->facture->lire);
            if (!$user->rights->fournisseur->commande->lire) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
                $obj = new CommandeFournisseur($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'Contrat':{
            if (!$user->rights->contrat->lire) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
                $obj = new Contrat($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'Campagne':{
            if (!$user->rights->prospectbabe->Prospection->Affiche) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/BabelProspect/Campagne.class.php");
                $obj = new Campagne($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'Chrono':{
            if (!$user->rights->synopsischrono->read) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/Synopsis_Chrono/Chrono.class.php");
                $obj = new Chrono($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'Societe':{
            if (!$user->rights->societe->lire) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                if ($user->societe_id !=  $id) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'Contact':{
            if (!$user->rights->societe->client->voir) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
                $obj = new Contact($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;

        case  'demandeInterv':{
            if (!$user->rights->synopsisdemandeinterv->lire) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/Synopsis_DemandeInterv/demandeInterv.class.php");
                $obj = new demandeInterv($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'Fichinter':{
            if (!$user->rights->ficheinter->lire) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
                $obj = new Fichinter($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'Livraison':{
            if (!$user->rights->expedition->livraison->lire) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/livraison/livraison.class.php");
                $obj = new Livraison($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'Paiement':{
            if (!$user->rights->facture->paiement) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/Paiement.class.php");
                $obj = new Paiement($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'PaiementFourn':{
            if (!$user->rights->fournisseur->facture->creer) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/fourn/paiement/paiementfourn.class.php");
                $obj = new PaiementFourn($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'Product':{
            if (!$user->rights->produit->lire) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
                $obj = new Product($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'Project':{
            if (!$user->rights->projet->lire) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
                $obj = new Project($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
        case  'Project':{
            if (!$user->rights->projet->lire) accessforbidden();
            if ($user->societe_id >0 && $id >0)
            {
                require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
                $obj = new Project($db);
                $obj->fetch((int)$id);
                if ($user->societe_id !=  $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    }
    $obj = false;

    if ($id > 0 && $type."x" != "x")
    {
        switch ($type)
        {
            case "Commande":{

                require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/order.lib.php");
                $obj = new Commande($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process de la commande '));
                $head = commande_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process de la commande '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("CustomerOrder"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"Commande",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible dans cette commande</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process dans cette commande</div>";
                }
            }

            break;
            case "Propal":{

                require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/propal.lib.php");
                $obj = new Propal($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process de la proposition '));
                $head = propal_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process de la proposition '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Proposal"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"Propal",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible dans cette proposition</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process dans cette proposition</div>";
                }
            }

            break;
            case "Facture":{

                require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/invoice.lib.php");
                $obj = new Facture($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process de la facture '));
                $head = facture_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process de la facture '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Invoice"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"Facture",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible dans cette facure</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process dans cette facture</div>";
                }
            }

            break;
            case "Expedition":{

                require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/sendings.lib.php");
                $obj = new Expedition($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process de l\'exp&eacute;dition'));
                $head = sending_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process de l\'exp&eacute;dition '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Sending"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"Expedition",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible dans cette exp&eacute;dition</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process dans cette exp&eacute;dition</div>";
                }
            }

            break;
            case "ActionComm":{

                require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/actionco.lib.php");
                $obj = new ActionComm($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process de l\'action'));
                $head = actionco_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process de l\'action '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Action"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"ActionComm",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible pour cette action</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process pour cette action</div>";
                }
            }

            break;
            case "Affaire":{

                require_once(DOL_DOCUMENT_ROOT."/Babel_Affaire/Affaire.class.php");
                require_once(DOL_DOCUMENT_ROOT."/Babel_Affaire/fct_affaire.php");
                $obj = new Affaire($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process de l\'affaire'));
                $head = affaire_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process de l\'affaire '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Affaire"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"Affaire",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible dans cette affaire</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process dans cette affaire</div>";
                }
            }

            break;
            case "FactureFournisseur":{

                require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/fourn.lib.php");
                $obj = new FactureFournisseur($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process de la facture fournisseur'));
                $head = facturefourn_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process de la facture fournisseur '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Facture Fournisseur"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"FactureFournisseur",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible dans cette facture fournisseur</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process dans cette facture fournisseur</div>";
                }
            }

            break;
            case "CommandeFournisseur":{

                require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/fourn.lib.php");
                $obj = new CommandeFournisseur($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process de la commande fournisseur'));
                $head = commandefourn_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process de la commande fournisseur '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Commande Fournisseur"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"CommandeFournisseur",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible dans cette commande fournisseur</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process dans cette commande fournisseur</div>";
                }
            }

            break;
            case "Contrat":{

                require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/contract.lib.php");
                $obj = new Contrat($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process du contrat'));
                $head = contract_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process du contrat '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Contrat"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"Contrat",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible dans ce contrat</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process dans ce contrat</div>";
                }
            }

            break;
            case "Chrono":{

                require_once(DOL_DOCUMENT_ROOT."/Synopsis_Chrono/Chrono.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/synopsis_chrono.lib.php");
                $obj = new Chrono($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process du chrono'));
                $head = chrono_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process du chrono '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Chrono"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"Chrono",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible dans ce chrono</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process dans ce chrono</div>";
                }
            }

            break;
            case "Societe":{

                require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
                $obj = new Societe($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process du tiers'));
                $head = societe_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process du tiers '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Tiers"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"Societe",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible pour cette soci&eacute;t&eacute;</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process pour cette soci&eacute;t&eacute;</div>";
                }
            }

            break;
            case "Contact":{

                require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/contact.lib.php");
                $obj = new Contact($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process du contact'));
                $head = contact_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process du contact '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Contact"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"Contact",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible pour ce contact</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process pour ce contact</div>";
                }
            }

            break;
            case "demandeInterv":{

                require_once(DOL_DOCUMENT_ROOT."/Synopsis_DemandeInterv/demandeInterv.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/demandeInterv.lib.php");
                $obj = new demandeInterv($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process de la DI'));
                $head = demandeInterv_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process de la DI '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("DI"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"demandeInterv",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible pour cette DI</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process pour cette DI</div>";
                }
            }

            break;
            case "Fichinter":{

                require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/fichinter.lib.php");
                $obj = new Fichinter($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process de la FI'));
                $head = fichinter_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process de la FI '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("DI"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"Fichinter",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible pour cette DI</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process pour cette DI</div>";
                }
            }

            break;
            case "Fichinter":{

                require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/fichinter.lib.php");
                $obj = new Fichinter($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process de la FI'));
                $head = fichinter_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process de la FI '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("DI"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"Fichinter",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible pour cette FI</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process pour cette FI</div>";
                }
            }

            break;
            case "Livraison":{

                require_once(DOL_DOCUMENT_ROOT."/livraison/livraison.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/sendings.lib.php");
                $obj = new Livraison($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process de la livraison'));
                $head = delivery_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process de la livraison '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Livraison"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"Livraison",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible pour cette livraison</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process pour cette livraison</div>";
                }
            }

            break;
            case "Paiement":{

                require_once(DOL_DOCUMENT_ROOT."/paiement.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/paiement.lib.php");
                $obj = new Paiement($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process du paiement client'));
                $head = paiement_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process du paiement client '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Paiement"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"Paiement",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible pour ce paiement client</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process pour ce paiement client</div>";
                }
            }
            break;
            case "PaiementFourn":{

                require_once(DOL_DOCUMENT_ROOT."/fourn/facture/paiementfourn.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/paiement.lib.php");
                $obj = new PaiementFourn($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process du paiement fournisseur'));
                $head = paiementFourn_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process du paiement fournisseur '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Paiement fournisseur"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"PaiementFourn",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible pour ce paiement fournisseur</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process pour ce paiement fournisseur</div>";
                }
            }
            break;
            case "Product":{

                require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
                $obj = new Product($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process du produit'));
                $head = product_prepare_head($obj, $user);

                print "<br/>";
                print_titre($langs->trans('Process du produit '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Produit"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"Product",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible pour ce produit</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process pour ce produit</div>";
                }
            }
            break;
            case "Project":{

                require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");
                $obj = new Project($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process du projet'));
                $head = project_prepare_head($obj);

                print "<br/>";
                print_titre($langs->trans('Process du projet '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Project"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"Project",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible pour ce projet</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process pour ce projet</div>";
                }
            }
            break;
            case "Campagne":{
                global $langs, $conf;
                require_once(DOL_DOCUMENT_ROOT."/BabelProspect/Campagne.class.php");
                $obj = new Campagne($db);
                $obj->fetch($id);
                llxHeader('',$langs->trans('Process de la campagne'));
                $head=array();
                $h=0;
                $head[$h][0] = DOL_URL_ROOT.'/BabelProspect/affichePropection.php?action=list&campagneId='.$obj->id;
                $head[$h][1] = $langs->trans("Retour campagne");
                $head[$h][2] = 'campagne';
                $h++;
                $head[$h][0] = DOL_URL_ROOT.'/Synopsis_Process/listProcessForElement.php?type=Campagne&id='.$obj->id;
                $head[$h][1] = $langs->trans("Process");
                $head[$h][2] = 'process';
                $head[$h][4] = 'ui-icon ui-icon-gear';


                print "<br/>";
                print_titre($langs->trans('Process de la campagne '.$obj->getNomUrl(1)));
                print "<br/>";
                dol_fiche_head($head, 'process', $langs->trans("Campagne"));
                print "<br/>";
                print "<br/>";
                $arrProcess = getIdleProcess($db,"Campagne",$id);

                if (count($arrProcess) > 0)
                {
                    $html = "";
                    $html .= "<table cellpadding=15 width=100%>";
                    $html .=  "<tr><th class='ui-widget-header ui-state-default'>Nom";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
                    $html .=  "    <th class='ui-widget-header ui-state-default'>Statut";
                    $iter =0;
                    foreach($arrProcess as $key=>$val)
                    {
                        $tmp = new Process($db);
                        $tmp->fetch($val['process']);
                        $tmp->getGlobalRights();
                        $tmp = "process".$val['process'];
                        if (! ($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                            continue;
                        $iter++;
                        $html .=  "<tr><td class='ui-widget-content'>";
                        $pDet = new processDet($db);
                        $pDet->fetch($val['processdet']);
                        $html .=  $pDet->getNomUrl(1);
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_create));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  date('d/m/Y h:i',strtotime($pDet->date_modify));
                        $html .=  "    <td class='ui-widget-content'>";
                        $html .=  $pDet->getLibStatut(4);

                    }
                    $html .=  "</table>";
                    if ($iter == 0){
                        print "<div class='ui-state-highlight'>Pas de process visible pour cette campagne</div>";
                    } else {
                        print $html;
                    }
                } else {
                    print "<div class='ui-state-highlight'>Pas de process pour cette campagne</div>";
                }
            }
            break;
        }
    } else {
        accessforbidden();
    }


llxFooter('$Date: 2008/09/10 22:23:38 $ - $Revision: 1.60.2.2 $');
?>
