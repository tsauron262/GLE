<?php
/*
  ** BIMP-ERP by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 27 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : PN.php
  * BIMP-ERP-1.2
  */
//TODO :
//=> Cost Effective Res et RH
//=> temps RH/res commandé = temps effectué idem facture => prob ajouter option dansfct costProjet

//=> Feuille de route xls
//=> Edition
//=> Catégorie:héritage

    require_once('pre.inc.php');
    require_once('fct_affaire.php');
    require_once('Affaire.class.php');

    $langs->load('affaires');
    $langs->load('orders');
    $langs->load('propal');
    $langs->load('projects');
    $langs->load('bills');
    if (!$user->rights->affaire->lire) accessforbidden();

    $affaireid=$_REQUEST['id'];


    $affaire = new Affaire($db);
    $affaire->fetch($affaireid);
    if ($_REQUEST['action'] == 'edit')
    {
        print "<form method=post action='".$_SERVER['PHP_SELF']."?id=".$affaire->id."'>";
        print "<input type='hidden' name='action' id='action' value='modify'>";
    }
    $js = <<<EOF
<script>
        jQuery(document).ready(function(){
            jQuery('#tabs').tabs({
                cache: true,
                fx: { opacity: 'toggle' },
                spinner: "Chargement en cours..."
            });
        });
</script>
EOF;

    llxHeader($js,"Affaire - PN","",1);
    print_cartoucheAffaire($affaire,'PN',$_REQUEST['action']);

    //faire modeledefiche PN au choix
    $venteArr=array();
    //1 liste les propostions => Attn Prix achat spécifique
    $remArray=array();
    $totalPrAchat = 0;
    $totalPrAchatMin = 0;
    $totalPrAchatMax = 0;
    $totalPr = 0;
    $totalPrMarge = 0;
    $totalPrMargeMin = 0;
    $totalPrMargeMax = 0;
    $totalComm = 0;
    $totalFac = 0;
    $projMod=false;
    $class='ui-widget-content';
    print '<div id="tabs">';
    print '<ul>';
    print '    <li><a href="#fragment-1"><span>Vente</span></a></li>';
    print '    <li><a href="#fragment-2"><span>Achat</span></a></li>';
    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Affaire_Element WHERE type='projet' AND affaire_refid=".$affaireid;
    $sql = $db->query($requete);
    if ($db->num_rows($sql) > 0)
    {
        $projMod=true;
        print '    <li><a href="#fragment-2a"><span>Ressources</span></a></li>';
        print '    <li><a href="#fragment-2b"><span>Frais</span></a></li>';
    }
    print '    <li><a href="#fragment-3"><span>Synth&egrave;se</span></a></li>';

    //si projet => 2a => Ressources (mat et rh)
    //          => 2b => Frais de proj

    print '</ul>';
    if ($projMod)
    {
        $totRes=0;
        $totRH=0;
        $totFP=0;
        $totRHEff=0;
        $totFPEff=0;
        $totResEff=0;

        $fraisProjStr="";

        print '<div id="fragment-2a">';
        print "<table width=90% cellpadding=10>";
        print "<thead><tr><th width=28% class='titre' style='color: white;'>Titre</th><th width=18% class='titre' style='color: white;'>Co&ucirc;t Mat.<th width=18% class='titre' style='color: white;'>Co&ucirc;t Mat. Effectif<th width=18% class='titre' style='color: white;'>Co&ucirc;t RH<th width=18% class='titre' style='color: white;'>Co&ucirc;t RH Effectif</thead><tbody>";
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Affaire_Element WHERE type='projet' AND affaire_refid=".$affaireid;
        $sql = $db->query($requete);
        if ($db->num_rows($sql) > 0)
        {
            $arrTask=array();
            while ($res=$db->fetch_object($requete))
            {
                $proj= new Project($db);
                $proj->fetch($res->element_id);
                $proj->costProject();
                foreach($proj->cost['task'] as $key=>$val)
                {
                    if($val['FraisProjet'] > 0)
                    {
                        $fraisProjStr .= "<tr><td nowrap class='".$class."'>".$proj->tasks[$key]['title'];
                        $fraisProjStr .= "    <td align=right nowrap  class='".$class."'>".price($val['FraisProjet']);
                        $fraisProjStr .= "    <td align=right nowrap  class='".$class."'>".price($proj->costEffective['task'][$key]['FraisProjet']);
                        $totFP+=$val['FraisProjet'];
                        $totFPEff+=$proj->costEffective['task'][$key]['FraisProjet'];

                    }
                    if($val['coutRessource'] > 0)
                    {
                        $arrTask[$key]['title']=$proj->tasks[$key]['title'];
                        $arrTask[$key]['res']+=$val['coutRessource'];
                    }
                    if($proj->costEffective['task'][$key]['coutRessource'] >0 )
                    {
                        $arrTask[$key]['title']=$proj->tasks[$key]['title'];
                        $arrTask[$key]['resEff']+=$proj->costEffective['task'][$key]['coutRessource'];
                    }
                    if($val['coutRH'] > 0)
                    {
                        $arrTask[$key]['title']=$proj->tasks[$key]['title'];
                        $arrTask[$key]['rh']+=$val['coutRH'];
                    }
                    if($proj->costEffective['task'][$key]['coutRH'] >0 )
                    {
                        $arrTask[$key]['title']=$proj->tasks[$key]['title'];
                        $arrTask[$key]['rhEff']+=$proj->costEffective['task'][$key]['coutRH'];
                    }
                    $totRes+=$val['coutRessource'];
                    $totResEff+=$proj->costEffective['task'][$key]['coutRessource'];
                    $totRH+=$val['coutRH'];
                    $totRHEff += $proj->costEffective['task'][$key]['coutRH'];
                }
                foreach($arrTask as $key=>$val)
                {
                    print "<tr><td nowrap  class='ui-widget-header ui-state-default'>".$val['title'];
                    print '<td nowrap align=right  class="'.$class.'">'.($val['res']>0?price($val['res']):"-");
                    print '<td nowrap align=right  class="'.$class.'">'.($val['resEff']>0?price($val['resEff']):"-");
                    print '<td nowrap align=right  class="'.$class.'">'.($val['rh']>0?price($val['rh']):"-");
                    print '<td nowrap align=right  class="'.$class.'">'.($val['rhEff']>0?price($val['rhEff']):"-");
                }
            }
            print "</tbody>";
            print "<tfoot><tr><th nowrap class='ui-widget-header ui-state-default'>Total";
            print "           <th align=right nowrap class='ui-widget-content'>".price($totRes);
            print "           <th align=right nowrap class='ui-widget-content'>".price($totResEff);
            print "           <th align=right nowrap class='ui-widget-content'>".price($totRH);
            print "           <th align=right nowrap class='ui-widget-content'>".price($totRHEff);

        }
        print "</table>";
        print "</div>";
        print '<div id="fragment-2b">';

        print "<table width=90% cellpadding=10>";
        print "<thead><tr><th width=40% class='titre' style='color: white;'>D&eacute;signation</th>
                          <th width=30% class='titre' style='color: white;'>Montant pr&eacute;vu
                          <th width=30% class='titre' style='color: white;'>Montant</thead><tbody>";
        print $fraisProjStr;

        print "</tbody>";
        print "<tfoot><tr><th nowrap align=right class='ui-widget-header ui-state-default'>Total";
        print "           <th nowrap align=right class='ui-widget-content'>".price($totFP);
        print "           <th nowrap align=right class='ui-widget-content'>".price($totFPEff);

        print "</table>";
        print "</div>";
    }
    print '<div id="fragment-1">';

    print "<table width=90% cellpadding=10>";
    print "<thead><tr><th width=10% class='titre' style='color: white;'>Tiers</th><th width=30% class='titre' style='color: white;' colspan=5>Proposition<th width=30% class='titre' style='color: white;' colspan=3>Commande<th width=30% class='titre' style='color: white;' colspan=3>Facture";
    print "        <tr><th class='ui-widget-header ui-state-default'>Nom.</th>
                       <th class='ui-widget-header ui-state-default'>Ref.</th>
                       <th class='ui-widget-header ui-state-default'>Total</th>
                       <th class='ui-widget-header ui-state-default'>Achat</th>
                       <th class='ui-widget-header ui-state-default'>Marge</th>
                       <th class='ui-widget-header ui-state-default'>Statut</th>";
    print "            <th class='ui-widget-header ui-state-default'>Ref.</th>
                       <th class='ui-widget-header ui-state-default'>Total</th>
                       <th class='ui-widget-header ui-state-default'>Statut</th>";
    print "            <th class='ui-widget-header ui-state-default'>Ref.</th>
                       <th class='ui-widget-header ui-state-default'>Total</th>
                       <th class='ui-widget-header ui-state-default'>Statut</th>";
    print "</tr><tbody>";
    $requete = " SELECT *
                   FROM ".MAIN_DB_PREFIX."propal
                  WHERE rowid in (SELECT element_id FROM " . MAIN_DB_PREFIX . "Synopsis_Affaire_Element WHERE type='propale' AND affaire_refid = ".$affaireid.")
               ORDER BY datep DESC";
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql))
    {

        $dateU=strtotime($res->datep);
        $remArray['propale'][$res->rowid]=1;
        $requete1 = "SELECT * FROM ".MAIN_DB_PREFIX."propaldet WHERE fk_propal = ".$res->rowid;
        $sql1 = $db->query($requete1);
        $totalAchat = 0;
        $totalAchatMin = 0;
        $totalAchatMax = 0;
        while ($res1 = $db->fetch_object($sql1))
        {
            if ($res1->fk_product > 0)
            {
                if ($res1->pa_ht > 0 )
                {
                    $totalAchat += $res1->pa_ht * $res1->qty;
                    $totalAchatMax=false;
                    $totalAchatMin=false;
                } else {
                    //pas de prix d'achat, on cherche le prix min et max fournisseur
                    $requete2 = "SELECT min(unitprice) as mn, max(unitprice) as mx
                                   FROM ".MAIN_DB_PREFIX."product_fournisseur,
                                        ".MAIN_DB_PREFIX."product_fournisseur_price
                                  WHERE ".MAIN_DB_PREFIX."product_fournisseur.fk_product = ".MAIN_DB_PREFIX."product_fournisseur_price.fk_product_fournisseur
                                    AND ".MAIN_DB_PREFIX."product_fournisseur.fk_product = ".$res1->fk_product."
                               GROUP BY ".MAIN_DB_PREFIX."product_fournisseur.fk_product";
                    $sql2 = $db->query($requete2);
                    $totalAchatMin=false;
                    $totalAchatMax=false;
                    while ($res2 = $db->query($sql2))
                    {
                        $totalAchatMin += $res1->qty * $res1->mn;
                        $totalAchatMax += $res1->qty * $res1->mx;
                        $totalAchat=false;
                    }
                }
            }
        }
        $tmpSoc=new Societe($db);
        $tmpSoc->fetch($res->fk_soc);

        $venteArr[$dateU][$res->ref] .= "    <tr><td class='".$class."' nowrap align='center'>".$tmpSoc->getNomUrl(1);
        $venteArr[$dateU][$res->ref] .= "    <td class='".$class."' nowrap align='center'>".$res->ref;
        $venteArr[$dateU][$res->ref] .= "    <td class='".$class."' nowrap align='right'>".price($res->total_ht);
        if ($totalAchat || (($totalAchatMax && $totalAchatMin) && $totalAchatMax== $totalAchatMin))
        {
            $venteArr[$dateU][$res->ref] .= "    <td class='".$class."' nowrap align='right'>".price($totalAchat);
            $venteArr[$dateU][$res->ref] .= "    <td class='".$class."' nowrap align='right'>".price($res->total_ht - $totalAchat);
        } else if ($totalAchatMax && $totalAchatMin){
            $venteArr[$dateU][$res->ref] .= "    <td class='".$class."' nowrap align='right'>".price($totalAchatMin)." &agrave; ".price($totalAchatMax);
            $venteArr[$dateU][$res->ref] .= "    <td class='".$class."' nowrap align='right'>".price($res->total_ht - $totalAchatMin)." &agrave; ".price($res->total_ht - $totalAchatMax);
        } else {
            $venteArr[$dateU][$res->ref] .= "    <td class='".$class."' nowrap align='right'>-";
            $venteArr[$dateU][$res->ref] .= "    <td class='".$class."' nowrap align='right'>-";
        }
        $totalPrAchat += $totalAchat;
        $totalPrAchatMin += $totalAchatMin;
        $totalPrAchatMax += $totalAchatMax;
        $totalPr += $res->total_ht;
        $totalPrMarge += ($res->total_ht - $totalAchat);
        $totalPrMargeMax += ($res->total_ht - $totalAchatMax);
        $totalPrMargeMin += ($res->total_ht - $totalAchatMin);

        require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
        $prop= new Propal($db);
        $prop->id=$res->rowid;
        $prop->statut = $res->fk_statut;
        $venteArr[$dateU][$res->ref] .= "    <td class='".$class."' align='right' nowrap>".$prop->getLibStatut(5);

        //La commande li&eacute;e
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."co_pr WHERE fk_propale = ".$res->rowid;
        $sql1=$db->query($requete);
        if ($db->num_rows($sql1) > 0)
        {
            while ($res1 = $db->fetch_object($sql1))
            {
                if (!$remArray['commande'][$res1->fk_commande]==1)
                {
                    $remArray['commande'][$res1->fk_commande]=1;
                    require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
                    $com = new Commande($db);
                    $com->fetch($res1->fk_commande);
                    $venteArr[$dateU][$res->ref] .= "    <td class=".$class." nowrap>".$com->getNomUrl(1);
                    $venteArr[$dateU][$res->ref] .= "    <td class=".$class." nowrap>".price($com->total_ht);
                    $venteArr[$dateU][$res->ref] .= "    <td align=right class=".$class." nowrap>".$com->getLibStatut(5);
                    $totalComm += $com->total_ht;
                }
            }
        } else {
            $venteArr[$dateU][$res->ref] .= "<td class=".$class." colspan=3 align=center>-</td>";
        }

//TODO liaison fcture - commande
        //La facture li&eacute;e
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."fa_pr WHERE fk_propal = ".$res->rowid;
        $sql1=$db->query($requete);
        if ($db->num_rows($sql1) > 0)
        {
            while ($res1 = $db->fetch_object($sql1))
            {
                if (!$remArray['facture'][$res1->fk_facture]==1)
                {
                    $remArray['facture'][$res1->fk_facture]=1;
                    require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
                    $com = new Facture($db);
                    $com->fetch($res1->fk_facture);
                    $venteArr[$dateU][$res->ref] .= "    <td class=".$class." nowrap>".$com->getNomUrl(1);
                    $venteArr[$dateU][$res->ref] .= "    <td class=".$class." nowrap>".price($com->total_ht);
                    $venteArr[$dateU][$res->ref] .= "    <td align=right class=".$class." nowrap>".$com->getLibStatut(5);
                    $totalFac += $com->total_ht;
                }
            }
        } else {
            $venteArr[$dateU][$res->ref] .= "<td class=".$class." nowrap colspan=3 align=center>-</td>";
        }
    }

    //epart command
    $requete = "SELECT rowid, fk_soc, unix_timestamp(date_commande) as date_commandeU
                   FROM ".MAIN_DB_PREFIX."commande
                  WHERE rowid in (SELECT element_id FROM " . MAIN_DB_PREFIX . "Synopsis_Affaire_Element WHERE type='commande' AND affaire_refid = ".$affaireid.")
               ORDER BY date_commande DESC";
    $sql= $db->query($requete);
    if ($db->num_rows($sql) > 0)
    {
        while($res= $db->fetch_object($sql))
        {
            $dateU=$res->date_commandeU;
            if (!$remArray['commande'][$res->rowid]==1)
            {
                $tmpSoc = new Societe($db);
                $tmpSoc->fetch($res->fk_soc);

                $venteArr[$dateU][$res->ref].= "<tr><td align=center class='".$class."' style='padding: 0px;'>".$tmpSoc->getNomUrl(1)."</td>";
                $venteArr[$dateU][$res->ref].= "    <td colspan=5 align=center class='".$class."' style='padding: 0px;'>-</td>";
                $com = new Commande($db);
                $com->fetch($res->rowid);
                $venteArr[$dateU][$res->ref] .= "    <td class=".$class." nowrap>".$com->getNomUrl(1);
                $venteArr[$dateU][$res->ref] .= "    <td class=".$class." nowrap>".price($com->total_ht);
                $venteArr[$dateU][$res->ref] .= "    <td class=".$class." align=right nowrap>".$com->getLibStatut(5)."";
                $totalComm += $com->total_ht;
                $remArray['commande'][$res->rowid]=1;


                //Lien facture
                $requete = "SELECT fk_target as fk_facture FROM " . MAIN_DB_PREFIX . "element_element as fk_facture WHERE sourcetype = 'commande' AND targettype = 'facture' AND fk_source = ".$res->rowid;
                $sql1=$db->query($requete);
                $atleastone = false;
                while($res1=$db->fetch_object($sql1))
                {
                    if (!$remArray['facture'][$res1->fk_facture]==1 && $db->num_rows($sql) > 0)
                    {
                        $fac = new Facture($db);
                        $fac->fetch($res1->fk_facture);
                        $venteArr[$dateU][$res->ref] .= "    <td class=".$class." nowrap>".$fac->getNomUrl(1);
                        $venteArr[$dateU][$res->ref] .= "    <td class=".$class." nowrap>".price($fac->total_ht);
                        $venteArr[$dateU][$res->ref] .= "    <td align=right class=".$class." nowrap>".$fac->getLibStatut(5);
                        $atleastone=true;
                        $totalFac += $fac->total_ht;
                        $remArray['facture'][$res1->fk_facture]=1;
                    }

                }
                if (!$atleastone)$venteArr[$dateU][$res->ref] .= "   <td align=center colspan='3' class='".$class."'>-";
            }

        }
    }
    $requete = "SELECT rowid, fk_soc, unix_timestamp(datef) as datefU
                   FROM ".MAIN_DB_PREFIX."facture
                  WHERE rowid in (SELECT element_id FROM " . MAIN_DB_PREFIX . "Synopsis_Affaire_Element WHERE type='facture' AND affaire_refid = ".$affaireid.")
               ORDER BY datef DESC";
    $sql= $db->query($requete);
    if ($db->num_rows($sql) > 0)
    {
        while($res= $db->fetch_object($sql))
        {
            $dateU=$res->datefU;
            if (!$remArray['facture'][$res->rowid]==1)
            {
                    $tmpSoc = new Societe($db);
                    $tmpSoc->fetch($res->fk_soc);
                    $venteArr[$dateU][$res->ref].= "<tr><td align=center class='".$class."' style='padding: 0px;'>".$tmpSoc->getNomUrl(1)."</td>";
                    $venteArr[$dateU][$res->ref].= "    <td class='".$class."' colspan=5 align=center>-";
                    $venteArr[$dateU][$res->ref].= "    <td class='".$class."' colspan=3 align=center>-";
                    $fac = new Facture($db);
                    $fac->fetch($res->rowid);
                    $venteArr[$dateU][$res->ref] .= "    <td class=".$class." nowrap>".$fac->getNomUrl(1);
                    $venteArr[$dateU][$res->ref] .= "    <td class=".$class." nowrap>".price($fac->total_ht);
                    $venteArr[$dateU][$res->ref] .= "    <td class=".$class." nowrap align=right>".$fac->getLibStatut(5)."";
                    $totalFac += $fac->total_ht;
                    $remArray['facture'][$res->rowid]=1;
            }
        }
    }

    krsort($venteArr,SORT_NUMERIC);

    foreach($venteArr as $date=>$arr)
    {
        foreach($arr as $key=>$val)
        {
            print $val;
        }
    }

    print "</tbody>";
    print "<tfoot>";
    print "<tr>";
    print "   <td colspan=6 style='padding: 0px;'><table width=100% cellpadding=10><tr>";
    print "    <th class='ui-widget-header ui-state-default'>Total";
    print "    <th colspan=4 align=right class='ui-widget-header ui-state-default'>".price($totalPr);
    print"     <tr>";
    if ($totalPrAchat || (($totalPrAchatMax && $totalPrAchatMin) && $totalPrAchatMax== $totalPrAchatMin))
    {
        print "    <th class='ui-widget-header ui-state-default'>Total achat";
        print "    <th colspan=4 align=right class='ui-widget-header ui-state-default'>".price($totalPrAchat);
        print"     <tr>";
        print "    <th class='ui-widget-header ui-state-default'>Total marge";
        print "    <th colspan=4 align=right class='ui-widget-header ui-state-default'>".price($totalPrMarge);
    }  else if ($totalPrAchatMax && $totalPrAchatMin){
        print "    <th class='ui-widget-header ui-state-default'>Total achat";
        print "    <th colspan=4 align=right class='ui-widget-header ui-state-default'>".price($totalPrAchatMin)." &agrave; ".price($totalPrAchatMax);
        print"     <tr>";
        print "    <th class='ui-widget-header ui-state-default'>Total marge";
        print "    <th colspan=4 align=right class='ui-widget-header ui-state-default'>".price($totalPr - $totalPrAchatMin)." &agrave; ".price($totalPr - $totalPrAchatMax);
    } else {
        print "    <th class='ui-widget-header ui-state-default'>Total achat";
        print "    <th colspan=4 align=right class='ui-widget-header ui-state-default'>-";
        print"     <tr>";
        print "    <th class='ui-widget-header ui-state-default'>Total marge";
        print "    <th colspan=4 align=right class='ui-widget-header ui-state-default'>-";
    }
    print "</table>";
    print "    <th class='ui-widget-header ui-state-default'>Total";
    print "    <th colspan=2 align=right class='ui-widget-header ui-state-default'>".price($totalComm);
    print "    <th class='ui-widget-header ui-state-default'>Total";
    print "    <th colspan=2 align=right class='ui-widget-header ui-state-default'>".price($totalFac);
    print "</tfoot></table>";

    print "</div>";
    print '<div id="fragment-2">';


//Faire meme presentation que au dessus

    $achatArr=array();
    //2 liste les commandes / factures
    $requete = " SELECT ".MAIN_DB_PREFIX."commande_fournisseur.rowid,
                        ".MAIN_DB_PREFIX."commande_fournisseur.total_ht,
                        ".MAIN_DB_PREFIX."commande_fournisseur.fk_statut,
                        ".MAIN_DB_PREFIX."commande_fournisseur.ref,
                        unix_timestamp(".MAIN_DB_PREFIX."commande_fournisseur.date_commande) as date_commandeU,
                        ".MAIN_DB_PREFIX."facture_fourn.ref as fref,
                        ".MAIN_DB_PREFIX."facture_fourn.rowid as frowid,
                        ".MAIN_DB_PREFIX."facture_fourn.fk_statut as ffk_statut,
                        ".MAIN_DB_PREFIX."facture_fourn.total_ht as ftotal_ht
                   FROM ".MAIN_DB_PREFIX."commande_fournisseur
              LEFT JOIN Babel_li_fourn_co_fa ON Babel_li_fourn_co_fa.fk_commande=".MAIN_DB_PREFIX."commande_fournisseur.rowid
              LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn ON Babel_li_fourn_co_fa.fk_facture = ".MAIN_DB_PREFIX."facture_fourn.rowid
                  WHERE ".MAIN_DB_PREFIX."commande_fournisseur.rowid in (SELECT element_id
                                    FROM " . MAIN_DB_PREFIX . "Synopsis_Affaire_Element
                                   WHERE type='commande fournisseur'
                                     AND affaire_refid = ".$affaireid.") ";
    $sql = $db->query($requete);
    $totComPr=0;
    $totComCom=0;
    $totFa=0;
    print "<table width=80% cellpadding=10>";
    print "<thead><tr><th class='titre' style='color: white;' colspan=3>Commande Fournisseur<th class='titre' style='color: white;' colspan=3>Facture Fournisseur";
    print "        <tr><th class='ui-widget-header ui-state-default'>Ref.
                       <th class='ui-widget-header ui-state-default'>Total
                       <th class='ui-widget-header ui-state-default'>Statut";
    print "            <th class='ui-widget-header ui-state-default'>Ref.
                       <th class='ui-widget-header ui-state-default'>Total
                       <th class='ui-widget-header ui-state-default'>Statut";
    print "<tbody>";
    while ($res = $db->fetch_object($sql))
    {
        $dateU = $res->date_commandeU;
        require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php');
        $coFourn=new CommandeFournisseur($db);
        $coFourn->id=$res->rowid;
        $coFourn->statut = $res->fk_statut;
        $coFourn->ref = $res->ref;

        $class='ui-widget-content';
        $achatArr[$dateU][$res->ref] .= "<tr class='com'><td class='".$class."' align='left'>".$coFourn->getNomUrl(1);
        $achatArr[$dateU][$res->ref] .=  "    <td class='".$class."' align='right'>".price($res->total_ht);
        $achatArr[$dateU][$res->ref] .=  "    <td class='".$class."' align='right'>".$coFourn->getLibStatut(5);

        if ($coFourn->statut< 2){
            $totComPr+=$res->total_ht;
        } else if ($coFourn->statut <6)
        {
            $totComCom+=$res->total_ht;
        }

        if ($res->frowid."x" != "x")
        {
            require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php');
            $faFourn=new FactureFournisseur($db);
            $faFourn->id=$res->frowid;
            $faFourn->statut = $res->ffk_statut;
            $faFourn->ref = $res->fref;
            $achatArr[$dateU][$res->ref] .=  "    <td class='".$class."' align='left'>".$faFourn->getNomUrl(1);
            $achatArr[$dateU][$res->ref] .=  "    <td class='".$class."' align='right'>".price($res->ftotal_ht);
            $achatArr[$dateU][$res->ref] .=  "    <td class='".$class."' align='right'>".$faFourn->getLibStatut(5);
            if ($faFourn->type != 2)
            {
                $totFa+=$res->ftotal_ht;
            }

        } else {
            $achatArr[$dateU][$res->ref] .=  "<td colspan=3 class='".$class."' align=center>-</td>";
        }

    }
    $requete = " SELECT *
                   FROM ".MAIN_DB_PREFIX."facture_fourn
                  WHERE ".MAIN_DB_PREFIX."facture_fourn.rowid in (SELECT element_id
                                    FROM " . MAIN_DB_PREFIX . "Synopsis_Affaire_Element
                                   WHERE type='facture fournisseur'
                                     AND affaire_refid = ".$affaireid.")
                    AND ".MAIN_DB_PREFIX."facture_fourn.rowid not in (SELECT fk_facture
                                                          FROM Babel_li_fourn_co_fa
                                                         WHERE Babel_li_fourn_co_fa.fk_commande not in( SELECT rowid FROM " . MAIN_DB_PREFIX . "Synopsis_Affaire_Element WHERE affaire_refid = ".$affaireid." AND type='commande fournisseur'))";

    $sql = $db->query($requete);
    if($db->num_rows($sql)>0)
    {
        while ($res = $db->fetch_object($sql))
        {
            $dateU = strtotime($res->datef);
            $class='ui-widget-content';
            require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php');
            $faFourn=new FactureFournisseur($db);
            $faFourn->id=$res->rowid;
            $faFourn->statut = $res->fk_statut;
            $faFourn->ref = $res->ref;
            if ($faFourn->type != 2)
            {
                $totFa+=$res->ftotal_ht;
            }

            $achatArr[$dateU][$faFourn->ref] .= "<tr class='fa'><td colspan='3' class='".$class."' align=center >-";

            $achatArr[$dateU][$faFourn->ref] .= "    <td class='".$class."' align='left'>".$faFourn->getNomUrl(1);
            $achatArr[$dateU][$faFourn->ref] .= "    <td class='".$class."' align='right'>".price($res->total_ht);
            $achatArr[$dateU][$faFourn->ref] .= "    <td class='".$class."' align='right'>".$faFourn->getLibStatut(5);
        }
    }

    krsort($achatArr,SORT_NUMERIC);

    foreach($achatArr as $date=>$arr)
    {
        foreach($arr as $key=>$val)
        {
            print $val;
        }
    }

    print "</tbody>";
    print "<tfoot>";
    print "<tr>";
    print "   <td colspan=3 style='padding: 0px;'><table width=100% cellpadding=10><tr>";
    print "<th class='ui-widget-header ui-state-default'>Total :";
    print "<th class='ui-widget-header ui-state-default'>".price($totCom);
    print "</table>";
    print "   <td colspan=3 style='padding: 0px;'><table width=100% cellpadding=10><tr>";
    print "<th class='ui-widget-header ui-state-default'>Total :";
    print "<th class='ui-widget-header ui-state-default'>".price($totFa);
    print "</table>";


    print "</tfoot>";
    print "</table>";

    print "</div>";
    print '<div id="fragment-3">';
    print "<table width=80% cellpadding=10>";

    print "<thead>";
    print "<tr><th class='ui-widget-header ui-state-default'>D&eacute;signation";
    print "    <th class='ui-widget-header ui-state-default'>Total Pr&eacute;vu";
    print "    <th class='ui-widget-header ui-state-default'>Total Command&eacute;";
    print "    <th class='ui-widget-header ui-state-default'>Total Factur&eacute;";
    print "</thead>";
    print "<tbody>";
    print "<tr><th class='ui-widget-header ui-state-hover'>Achat";
    if ($totalPrAchat || (($totalPrAchatMax && $totalPrAchatMin) && $totalPrAchatMax== $totalPrAchatMin))
    {
        print "   <td align='right' class='".$class."'>".showNegative($totalPrAchat + $totComPr);
    }  else if ($totalPrAchatMax && $totalPrAchatMin){
        print "   <td align='right' class='".$class."'>".showNegative($totalPrAchatMin + $totComPr)." &agrave; ".showNegative($totalPrAchatMax + $totComPr);
    } else {
        if ($totComPr > 0 )
        {
            print "   <td align='center' class='".$class."'>".showNegative($totComPr);
        } else{
            print "   <td align='center' class='".$class."'>-";
        }
    }
    print "    <td align='right' class='".$class."'>".showNegative($totCom);
    print "    <td align='right' class='".$class."'>".showNegative($totFa);

    print "<tr><th class='ui-widget-header ui-state-hover'>Vente";
    print "    <td align='right' class='".$class."'>".showNegative($totalPr);
    print "    <td align='right' class='".$class."'>".showNegative($totalComm);
    print "    <td align='right' class='".$class."'>".showNegative($totalFac);

    $totProj=0;
    $totProjEff=0;
    if ($projMod)
    {
        print "<tr><th class='ui-widget-header ui-state-default'>Projet";
        print "    <th class='ui-widget-header ui-state-default'>Total Pr&eacute;vu";
        print "    <th class='ui-widget-header ui-state-default' colspan=2>Total Effectu&eacute;";

        print "<tr><th class='ui-widget-header ui-state-hover'>Ressource Humaines";
        print "    <td align='right' class='".$class."'>".showNegative($totRH);//prevu
        print "    <td align='right' class='".$class."' colspan=2>".showNegative($totRHEff);//fait

        print "<tr><th class='ui-widget-header ui-state-hover'>Ressource Mat.";
        print "    <td align='right' class='".$class."'>".showNegative($totRes);//prevu
        print "    <td align='right' class='".$class."' colspan=2>".showNegative($totResEff);

        print "<tr><th class='ui-widget-header ui-state-hover'>Frais";
        print "    <td align='right' class='".$class."'>".showNegative($totFP);//prevu
        print "    <td align='right' class='".$class."' colspan=2>".showNegative($totFPEff);
        $totProjEff = $totFPEff+$totResEff+$totRHEff;
        $totProj = $totFP+$totRes+$totRH;
    }


    print "<tr style='border-top-style:double !important;'><th class='ui-widget-header ui-state-hover' >Rentabilit&eacute;</th>";
    if ($totalPrAchat || (($totalPrAchatMax && $totalPrAchatMin) && $totalPrAchatMax== $totalPrAchatMin))
    {
        print "   <td align='right' class='".$class."'>".showNegative($totalPrMarge - $totProj );
        print     " <small><em>(soit ". round((($totalPrMarge - $totProj)/$totalPrMarge)*10000)/100 ."%)<em></small>  ";
    }  else if ($totalPrAchatMax && $totalPrAchatMin){
        print "   <td align='right' class='".$class."'>".showNegative($totalPr - $totalPrAchatMin - $totProj)." &agrave; ".price($totalPr - $totalPrAchatMax - $totProj);
        print     " <small><em>(soit ". round((($totalPr - $totalPrAchatMin - $totProj)/$totalPr)*10000)/100 ." &agrave; ". round((($totalPr - $totalPrAchatMax - $totProj)/$totalPr)*10000)/100 ."%)<em></small>  ";
    } else {
        print "   <td align='right' class='".$class."'>".showNegative($totalPr - $totProj)."";
        print     " <small><em>(soit ". round((($totalPr - $totProj)/$totalPr)*10000)/100 ."%)<em></small>  ";
    }

    print "    <td align='right' class='".$class."'>".showNegative($totalComm - $totCom - $totProjEff);
    print     " <small><em>(soit ". round((($totalComm-$totCom-$totProjEff)/$totalComm)*10000)/100 ."%)<em></small>  ";
    print "    <td align='right' class='".$class."'>".showNegative($totalFac - $totFa - $totProjEff);
    print     " <small><em>(soit ". round((($totalFac-$totFa-$totProjEff)/$totalFac)*10000)/100 ."%)<em></small>  ";


    print "</table>";

    print "</div>";

function showNegative($val)
{
    if ($val <0)
    {
        return "<div style='color:#FF0000;' class='ui-state-highlight' ><strong>".price($val)."</strong></div>";

    }else {
        return price($val);
    }
}
?>
