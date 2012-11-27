<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
/*
 */

/**
        \file       htdocs/comm/fiche.php
        \ingroup    commercial
        \brief      Onglet client de la fiche societe
        \version    $Id: fiche.php,v 1.209 2008/08/06 13:06:59 eldy Exp $
*/

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

if ($conf->global->MAIN_MODULE_BABELGA) require_once(DOL_DOCUMENT_ROOT."/Babel_GA/BabelGA.class.php");

$langs->load("companies");
$langs->load("orders");
$langs->load("bills");
$langs->load("contracts");
if ($conf->fichinter->enabled) $langs->load("interventions");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";


/*
 * Actions
 */

if ($_GET["action"] == 'attribute_prefix' && $user->rights->societe->creer)
{
    $societe = new Societe($db, $_GET["socid"]);
    $societe->attribute_prefix($db, $_GET["socid"]);
}
// conditions de reglement
if ($_POST["action"] == 'setconditions' && $user->rights->societe->creer)
{

    $societe = new Societe($db, $_GET["socid"]);
    $societe->cond_reglement=$_POST['cond_reglement_id'];
    $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET cond_reglement='".$_POST['cond_reglement_id'];
    $sql.= "' WHERE rowid='".$_GET["socid"]."'";
    $result = $db->query($sql);
    if (! $result) dol_print_error($result);
}
// mode de reglement
if ($_POST["action"] == 'setmode' && $user->rights->societe->creer)
{
    $societe = new Societe($db, $_GET["socid"]);
    $societe->mode_reglement=$_POST['mode_reglement_id'];
    $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET mode_reglement='".$_POST['mode_reglement_id'];
    $sql.= "' WHERE rowid='".$_GET["socid"]."'";
    $result = $db->query($sql);
    if (! $result) dol_print_error($result);
}
// assujetissement a la TVA
if ($_POST["action"] == 'setassujtva' && $user->rights->societe->creer)
{
    $societe = new Societe($db, $_GET["socid"]);
    $societe->tva_assuj=$_POST['assujtva_value'];
    $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET tva_assuj='".$_POST['assujtva_value']."' WHERE rowid='".$socid."'";
    $result = $db->query($sql);
    if (! $result) dol_print_error($result);
}


/*
 * Recherche
 *
 */
if ($mode == 'search') {
    if ($mode-search == 'soc') {
        $sql = "SELECT s.rowid";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    }

    if ( $db->query($sql) ) {
        if ( $db->num_rows() == 1) {
            $obj = $db->fetch_object();
            $socid = $obj->rowid;
        }
        $db->free();
    }
}



/*********************************************************************************
 *
 * Mode fiche
 *
 *********************************************************************************/
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$header .= '<script language="javascript" src="'.$jspath.'jquery.validate.min.js"></script>'."\n";

llxHeader($header,$langs->trans('CustomerCard'));

$userstatic=new User($db);

$form = new Form($db);

if ($socid > 0)
{
    // On recupere les donnees societes par l'objet
    $objsoc = new Societe($db);
    $objsoc->id=$socid;
    $objsoc->fetch($socid,$to);

    $dac = utf8_decode(strftime("%Y-%m-%d %H:%M", time()));
    if ($errmesg)
    {
        print "<b>$errmesg</b><br>";
    }

    /*
     * Affichage onglets
     */

    $head = societe_prepare_head($objsoc);

    dol_fiche_head($head, 'cessionnaire', $langs->trans("ThirdParty"));


    /*
     *
     */
    print '<table width="100%" class="notopnoleftnoright">';
    print '<tr><td class="ui-widget-header ui-state-default" valign="top" class="notopnoleft">';

    print '<table class="border" width="100%">';

    print '<tr><td class="ui-widget-header ui-state-default" width="30%">'.$langs->trans("Name").'</td>
               <td width="70%" colspan="3" class="ui-widget-content">';
    print $objsoc->nom;
    print '</td></tr>';

    print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans('Prefix').'</td>
                <td colspan="3" class="ui-widget-content">'.$objsoc->prefix_comm.'</td></tr>';

    if ($objsoc->client)
    {
        print '<tr><td nowrap class="ui-widget-header ui-state-default">';
        print $langs->trans('CustomerCode').'</td><td colspan="3" class="ui-widget-content">';
        print $objsoc->code_client;
        if ($objsoc->check_codeclient() <> 0) print '  <font class="ui-state-error error">('.$langs->trans("WrongCustomerCode").')</font>';
        print '</td></tr>';
    }

    print "<tr><td  class=\"ui-widget-header ui-state-default\" valign=\"top\">".$langs->trans('Address')."</td>
                <td class=\"ui-widget-content\" colspan=\"3\">".nl2br($objsoc->adresse)."</td></tr>";

    print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans('Zip').'</td>
               <td class="ui-widget-content">'.$objsoc->cp."</td>";
    print '<td class="ui-widget-header ui-state-default black">'.$langs->trans('Town').'</td>
               <td class="ui-widget-content">'.$objsoc->ville."</td></tr>";

    // Country
    print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans("Country").'</td>
               <td colspan="3" class="ui-widget-content">';
    if ($objsoc->isInEEC()) print $form->textwithtooltip($objsoc->pays,$langs->trans("CountryIsInEEC"),1,0);
    else print $objsoc->pays;
    print '</td></tr>';

    // Phone
    print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans('Phone').'</td>
               <td class="ui-widget-content">'.dol_print_phone($objsoc->tel,$objsoc->pays_code).'</td>';

    // Fax
    print '<td class="ui-widget-header ui-state-default black">'.$langs->trans('Fax').'</td>
           <td class="ui-widget-content">'.dol_print_phone($objsoc->fax,$objsoc->pays_code).'</td></tr>';

    print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans("Web")."</td>
               <td  class=\"ui-widget-content\" colspan=\"3\"><a href=\"http://$objsoc->url\" target=\"_blank\">".$objsoc->url."</a>&nbsp;</td></tr>";

    // Assujeti TVA ou pas
    print '<tr>';
    print '<td nowrap="nowrap" class="ui-widget-header ui-state-default">'.$langs->trans('VATIsUsed').'</td>
           <td colspan="3" class="ui-widget-content">';
    print yn($objsoc->tva_assuj);
    print '</td>';
    print '</tr>';

    // Conditions de reglement par defaut
    $langs->load('bills');
    $html = new Form($db);
    print '<tr><td nowrap class="ui-widget-header ui-state-default">';
    print '<table width="100%" class="nobordernopadding"><tr><td nowrap class="ui-widget-header ui-state-default">';
    print $langs->trans('PaymentConditions');
    print '<td>';
    if (($_GET['action'] != 'editconditions') && $user->rights->societe->creer) print '<td align="right" class="ui-widget-content"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;socid='.$objsoc->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
    print '</tr></table>';
    print '</td><td colspan="3" class="ui-widget-content">';
    if ($_GET['action'] == 'editconditions')
    {
        $html->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$objsoc->id,$objsoc->cond_reglement,'cond_reglement_id',-1,1);
    } else {
        $html->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$objsoc->id,$objsoc->cond_reglement,'none');
    }
    print "</td>";
    print '</tr>';

    // Mode de reglement
    print '<tr><td nowrap class="ui-widget-header ui-state-default">';
    print '<table width="100%" class="nobordernopadding">
            <tr><td nowrap class="ui-widget-header ui-state-default">';
    print $langs->trans('PaymentMode');
    print '<td class="ui-widget-content">';
    if (($_GET['action'] != 'editmode') && $user->rights->societe->creer) print '<td align="right" class="ui-widget-content"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;socid='.$objsoc->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
    print '</tr></table>';
    print '</td><td colspan="3" class="ui-widget-content">';
    if ($_GET['action'] == 'editmode')
    {
        $html->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$objsoc->id,$objsoc->mode_reglement,'mode_reglement_id');
    } else {
        $html->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$objsoc->id,$objsoc->mode_reglement,'none');
    }
    print "</td>";
    print '</tr>';

    // Reductions relative (Remises-Ristournes-Rabbais)
    print '<tr><td nowrap class="ui-widget-header ui-state-default">';
    print '<table width="100%" class="nobordernopadding"><tr><td nowrap class="ui-widget-header ui-state-default">';
    print $langs->trans("CustomerRelativeDiscountShort");
    print '<td><td align="right">';
    if ($user->rights->societe->creer)
    {
        print '<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$objsoc->id.'">'.img_edit($langs->trans("Modify")).'</a>';
    }
    print '</td></tr></table>';
    print '</td><td colspan="3" class="ui-widget-content">'.($objsoc->remise_client?$objsoc->remise_client.'%':$langs->trans("DiscountNone")).'</td>';
    print '</tr>';

    // Reductions absolues (Remises-Ristournes-Rabbais)
    print '<tr><td nowrap class="ui-widget-header ui-state-default">';
    print '<table width="100%" class="nobordernopadding">';
    print '<tr><td nowrap class="ui-widget-header ui-state-default">';
    print $langs->trans("CustomerAbsoluteDiscountShort");
    print '<td><td align="right" class="ui-widget-content">';
    if ($user->rights->societe->creer)
    {
        print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$objsoc->id.'">'.img_edit($langs->trans("Modify")).'</a>';
    }
    print '</td></tr></table>';
    print '</td>';
    print '<td colspan="3" class="ui-widget-content">';
        $amount_discount=$objsoc->getAvailableDiscounts();
        if ($amount_discount < 0) dol_print_error($db,$societe->error);
        if ($amount_discount > 0) print price($amount_discount).'&nbsp;'.$langs->trans("Currency".$conf->monnaie);
        else print $langs->trans("DiscountNone");
    print '</td>';
    print '</tr>';

    // Multiprix
    if ($conf->global->PRODUIT_MULTIPRICES)
    {
        print '<tr><td nowrap class="ui-widget-header ui-state-default">';
        print '<table width="100%" class="nobordernopadding"><tr><td nowrap class="ui-widget-header ui-state-default">';
        print $langs->trans("PriceLevel");
        print '<td><td align="right" class="ui-widget-content">';
        if ($user->rights->societe->creer)
        {
            print '<a href="'.DOL_URL_ROOT.'/comm/multiprix.php?id='.$objsoc->id.'">'.img_edit($langs->trans("Modify")).'</a>';
        }
        print '</td></tr></table>';
        print '</td><td colspan="3" class="ui-widget-content">'.$objsoc->price_level."</td>";
        print '</tr>';
    }

    // Adresse de livraison
    if ($conf->expedition->enabled)
    {
        print '<tr><td nowrap class="ui-widget-header ui-state-default">';
        print '<table width="100%" class="nobordernopadding"><tr><td nowrap class="ui-widget-header ui-state-default">';
        print $langs->trans("DeliveriesAddress");
        print '<td><td align="right" class="ui-widget-content">';
        if ($user->rights->societe->creer)
        {
            print '<a href="'.DOL_URL_ROOT.'/comm/adresse_livraison.php?socid='.$objsoc->id.'">'.img_edit($langs->trans("Modify")).'</a>';
        }
        print '</td></tr></table>';
        print '</td><td colspan="3"  class="ui-widget-content">';

        $sql = "SELECT count(rowid) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe_adresse_livraison";
        $sql.= " WHERE fk_societe =".$objsoc->id;

        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $objal = $db->fetch_object($resql);
            print $objal->nb?($objal->nb):$langs->trans("NoOtherDeliveryAddress");
        } else {
            dol_print_error($db);
        }

        print '</td>';
        print '</tr>';
        print "<tr><td colspan=4>";
        //$form->
        $bga = new BabelGA($db);
        $bga->fetch_taux($objsoc->id,'cessionnaire');
        $bga->drawFinanceTable();
        print "</tr>";
        print "<tr><td colspan=4>";
        print $bga->drawMargeFinTable();
        print "</tr>";
    }

    print "</table>";

    print "</td>\n";


    print '<td valign="top" width="50%" class="notopnoleftnoright">';

    // Nbre max d'elements des petites listes
    $MAXLIST=4;

    // Lien recap
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("Summary").'</td>';
    print '<td align="right"><a href="'.DOL_URL_ROOT.'/societe/recap-client.php?socid='.$objsoc->id.'">'.$langs->trans("ShowCustomerPreview").'</a></td></tr></table></td>';
    print '</tr>';
    print '</table>';
    print '<br>';


    /*
     * Dernieres propales
     */
    if ($conf->propal->enabled)
    {
        $propal_static=new Propal($db);

        print '<table class="noborder" width="100%">';

        $sql = "SELECT s.nom, s.rowid, p.rowid as propalid, p.fk_statut, p.total_ht, p.ref, p.remise, ";
        $sql.= " p.datep as dp";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c";
        $sql .= " WHERE p.fk_soc = s.rowid AND p.fk_statut = c.id";
        $sql .= " AND s.rowid = ".$objsoc->id;
        $sql .= " ORDER BY p.datep DESC";

        $resql=$db->query($sql);
        if ($resql)
        {
            $var=true;
            $num = $db->num_rows($resql);
            if ($num > 0)
            {
                print '<tr class="liste_titre">';
                print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastPropals",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/comm/propal.php?socid='.$objsoc->id.'">'.$langs->trans("AllPropals").' ('.$num.')</a></td></tr></table></td>';
                print '</tr>';
                $var=!$var;
            }
            $i = 0;
            while ($i < $num && $i < $MAXLIST)
            {
                $objp = $db->fetch_object($resql);
                print "<tr $bc[$var]>";
                print "<td nowrap><a href=\"propal.php?propalid=$objp->propalid\">".img_object($langs->trans("ShowPropal"),"propal")." ".$objp->ref."</a>\n";
                if ( ($db->jdate($objp->dp) < time() - $conf->propal->cloture->warning_delay) && $objp->fk_statut == 1 )
                {
                    print " ".img_warning();
                }
                print '</td><td align="right" width="80">'.dol_print_date($db->jdate($objp->dp),'day')."</td>\n";
                print '<td align="right" width="120">'.price($objp->total_ht).'</td>';
                print '<td align="right" nowrap="nowrap">'.$propal_static->LibStatut($objp->fk_statut,5).'</td></tr>';
                $var=!$var;
                $i++;
            }
            $db->free($resql);
        } else {
            dol_print_error($db);
        }
        print "</table>";
    }

    /*
     * Dernieres commandes
     */
    if($conf->commande->enabled)
    {
        $commande_static=new Commande($db);

        print '<table class="noborder" width="100%">';

        $sql = "SELECT s.nom, s.rowid,";
        $sql.= " c.rowid as cid, c.total_ht, c.ref, c.fk_statut, c.facture,";
        $sql.= " c.date_commande as dc";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
        $sql.= " WHERE c.fk_soc = s.rowid ";
        $sql.= " AND s.rowid = ".$objsoc->id;
        $sql.= " ORDER BY c.date_commande DESC";

        $resql=$db->query($sql);
        if ($resql)
        {
            $var=true;
            $num = $db->num_rows($resql);
            if ($num >0 )
            {
                print '<tr class="liste_titre">';
                print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastOrders",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/commande/liste.php?socid='.$objsoc->id.'">'.$langs->trans("AllOrders").' ('.$num.')</a></td></tr></table></td>';
                print '</tr>';
            }
            $i = 0;
            while ($i < $num && $i < $MAXLIST)
            {
                $objp = $db->fetch_object($resql);
                $var=!$var;
                print "<tr $bc[$var]>";
                print '<td nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$objp->cid.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$objp->ref."</a>\n";
                print '</td><td align="right" width="80">'.dol_print_date($db->jdate($objp->dc),'day')."</td>\n";
                print '<td align="right" width="120">'.price($objp->total_ht).'</td>';
                print '<td align="right" width="100">'.$commande_static->LibStatut($objp->fk_statut,$objp->facture,5).'</td></tr>';
                $i++;
            }
            $db->free($resql);
        }
        else {
            dol_print_error($db);
        }
        print "</table>";
    }

    /*
     * Last linked contracts
     */
    if($conf->contrat->enabled)
    {
        $contratstatic=new Contrat($db);

        print '<table class="noborder" width="100%">';

        $sql = "SELECT s.nom, s.rowid, c.rowid as id, c.ref as ref, c.statut, c.datec as dc";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."contrat as c";
        $sql .= " WHERE c.fk_soc = s.rowid ";
        $sql .= " AND c.cessionnaire_refid = ".$objsoc->id;
        $sql .= " ORDER BY c.datec DESC";

        $resql=$db->query($sql);
        if ($resql)
        {
            $var=true;
            $num = $db->num_rows($resql);
            if ($num >0 )
            {
                print '<tr class="liste_titre">';
                print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastContracts",($num<=$MAXLIST?"":$MAXLIST)).'</td>';
                print '<td align="right"><a href="'.DOL_URL_ROOT.'/contrat/liste.php?socid='.$objsoc->id.'">'.$langs->trans("AllContracts").' ('.$num.')</a></td></tr></table></td>';
                print '</tr>';
            }
            $i = 0;
            while ($i < $num && $i < $MAXLIST)
            {
                $contrat=new Contrat($db);

                $objp = $db->fetch_object($resql);
                $var=!$var;
                print "<tr $bc[$var]>";
                print '<td>';
                $contrat->id=$objp->id;
                $contrat->ref=$objp->ref?$objp->ref:$objp->id;
                print $contrat->getNomUrl(1);
                print "</td>\n";
                print '<td align="right" width="80">'.dol_print_date($db->jdate($objp->dc),'day')."</td>\n";
                print '<td width="20">&nbsp;</td>';
                print '<td align="right" nowrap="nowrap">';
                $contrat->fetch_lignes();
                print $contrat->getLibStatut(4);
                print "</td>\n";
                print '</tr>';
                $i++;
            }
            $db->free($resql);
        }
        else {
            dol_print_error($db);
        }
        print "</table>";
    }

    /*
     * Dernieres interventions
     */
    if ($conf->fichinter->enabled)
    {
        print '<table class="noborder" width="100%">';

        $sql = "SELECT s.nom, s.rowid, f.rowid as id, f.ref, f.datei as di";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."fichinter as f";
        $sql .= " WHERE f.fk_soc = s.rowid";
        $sql .= " AND s.rowid = ".$objsoc->id;
        $sql .= " ORDER BY f.datei DESC";

        $resql=$db->query($sql);
        if ($resql)
        {
            $var=true;
            $num = $db->num_rows($resql);
            if ($num >0 )
            {
                print '<tr class="liste_titre">';
                print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastInterventions",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/fichinter/index.php?socid='.$objsoc->id.'">'.$langs->trans("AllInterventions").' ('.$num.')</td></tr></table></td>';
                print '</tr>';
                $var=!$var;
            }
            $i = 0;
            while ($i < $num && $i < $MAXLIST)
            {
                $objp = $db->fetch_object($resql);
                print "<tr $bc[$var]>";
                print '<td nowrap><a href="'.DOL_URL_ROOT."/fichinter/fiche.php?id=".$objp->id."\">".img_object($langs->trans("ShowPropal"),"propal")." ".$objp->ref."</a>\n";
                print "</td><td align=\"right\">".dol_print_date($db->jdate($objp->di),'day')."</td>\n";
                print '</tr>';
                $var=!$var;
                $i++;
            }
            $db->free($resql);
        }
        else {
            dol_print_error($db);
        }
        print "</table>";
    }

    /*
     * Last linked projects
     */
    if ($conf->projet->enabled)
    {
        print '<table class="noborder" width=100%>';

        $sql  = "SELECT p.rowid,p.title,p.ref, p.dateo as do";
        $sql .= " FROM ".MAIN_DB_PREFIX."Synopsis_projet as p";
        $sql .= " WHERE p.fk_soc = $objsoc->id";
        $sql .= " ORDER BY p.dateo DESC";

        $result=$db->query($sql);
        if ($result) {
            $var=true;
            $i = 0 ;
            $num = $db->num_rows($result);
            if ($num > 0) {
                print '<tr class="liste_titre">';
                print '<td colspan="2"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastProjects",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/projet/liste.php?socid='.$objsoc->id.'">'.$langs->trans("AllProjects").' ('.$num.')</td></tr></table></td>';
                print '</tr>';
            }
            while ($i < $num && $i < $MAXLIST) {
                $obj = $db->fetch_object($result);
                $var = !$var;
                print "<tr $bc[$var]>";
                print '<td><a href="../projet/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowProject"),"project")." ".$obj->title.'</a></td>';

                print "<td align=\"right\">".$obj->ref ."</td></tr>";
                $i++;
            }
            $db->free($result);
        }
        else
        {
            dol_print_error($db);
        }
        print "</table>";
    }

    print "</td></tr>";
    print "</table></div>\n";


    /*
     * Barre d'action
     *
     */
    print '<div class="tabsAction">';

    if ($conf->propal->enabled && $user->rights->propale->creer)
    {
        $langs->load("propal");
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/addpropal.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddProp").'</a>';
    }

    if ($conf->commande->enabled && $user->rights->commande->creer)
    {
        $langs->load("orders");
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/commande/fiche.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddOrder").'</a>';
    }
    if ($conf->projet->enabled && $user->rights->projet->creer)
    {
        $langs->load("project");
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/projet/fiche.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddProject").'</a>';
    }

    if ($user->rights->contrat->creer)
    {
        $langs->load("contracts");
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/contrat/fiche.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddContract").'</a>';
    }

    if ($conf->fichinter->enabled && $user->rights->ficheinter->creer)
    {
        $langs->load("fichinter");
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/fichinter/fiche.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddIntervention").'</a>';
    }

    if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
    {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$objsoc->id.'">'.$langs->trans("AddAction").'</a>';
    }

    if ($user->rights->societe->contact->creer)
    {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddContact").'</a>';
    }

    print '</div>';
    print '<br>';

    /*
     * Liste des contacts
     */
    show_contacts($conf,$langs,$db,$objsoc);

    /*
     *      Listes des actions a faire
     */
    show_actions_todo($conf,$langs,$db,$objsoc);

    /*
     *      Listes des actions effectuees
     */
    show_actions_done($conf,$langs,$db,$objsoc);
} else {
    dol_print_error($db,'Bad value for socid parameter');
}

$db->close();


llxFooter('$Date: 2008/08/06 13:06:59 $ - $Revision: 1.209 $');
?>
