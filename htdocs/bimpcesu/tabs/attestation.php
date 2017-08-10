<?php

/* Copyright (C) 2004-2009	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010		Juanjo Menent		<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/societe/info.php
 *      \ingroup    societe
 *      \brief      Page des informations d'une societe
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
if (!empty($conf->facture->enabled))
    require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';


//require '../../main.inc.php';
//require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
//require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
//require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
//require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
//require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
//require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
//if (! empty($conf->facture->enabled)) require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
//if (! empty($conf->propal->enabled)) require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
//if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
//if (! empty($conf->expedition->enabled)) require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
//if (! empty($conf->contrat->enabled)) require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
//if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
//if (! empty($conf->ficheinter->enabled)) require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';

$langs->load("companies");
$langs->load("other");

// Security check
$socid = GETPOST('id', 'int');
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '&societe');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('infothirdparty'));

$object = new Societe($db);


/*
 * 	Actions
 */

$parameters = array('id' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0)
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');



/*
 * 	View
 */

$form = new Form($b);

$title = $langs->trans("ThirdParty");
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name)
$title = $object->name . ' - ' . $langs->trans("Info");
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';













llxHeader('', $title, $help_url);


if ($socid > 0) {
    $result = $object->fetch($socid);
    if (!$result) {
        $langs->load("errors");
        print $langs->trans("ErrorRecordNotFound");

        llxFooter();
        $db->close();

        exit;
    }

    $head = societe_prepare_head($object);

    dol_fiche_head($head, 'attestation', $langs->trans("ThirdParty"), 0, 'company');

    dol_banner_tab($object, 'socid', '', ($user->societe_id ? 0 : 1), 'rowid', 'nom');

    $object->info($socid);


























//<--- -------------- FACTURE LIST START --------------- --->



    if (!empty($conf->facture->enabled) && $user->rights->facture->lire) {
        $facturestatic = new Facture($db);

        $sql = 'SELECT f.rowid as facid, f.facnumber, f.type, f.amount';
        $sql .= ', f.total as total_ht';
        $sql .= ', f.tva as total_tva';
        $sql .= ', f.total_ttc';
        $sql .= ', f.datef as df, f.datec as dc, f.paye as paye, f.fk_statut as statut';
        $sql .= ', s.nom, s.rowid as socid';
        $sql .= ', SUM(pf.amount) as am';
        $sql .= " FROM " . MAIN_DB_PREFIX . "societe as s," . MAIN_DB_PREFIX . "facture as f";
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiement_facture as pf ON f.rowid=pf.fk_facture';
        $sql .= " WHERE f.fk_soc = s.rowid AND s.rowid = " . $object->id;
        $sql .= " AND f.entity = " . $conf->entity;
        $sql .= ' GROUP BY f.rowid, f.facnumber, f.type, f.amount, f.total, f.tva, f.total_ttc,';
        $sql .= ' f.datef, f.datec, f.paye, f.fk_statut,';
        $sql .= ' s.nom, s.rowid';
        $sql .= " ORDER BY f.datef DESC, f.datec DESC";

        $resql = $db->query($sql);
        if ($resql) {
            $var = true;
            $num = $db->num_rows($resql);
            $i = 0;
            if ($num > 0) {
                print '<table class="noborder" width="100%">';

                print '<tr class="liste_titre">';
                print '<td colspan="5"><table width="100%" class="nobordernopadding"><tr><td>' . $langs->trans("LastCustomersBills", ($num <= $MAXLIST ? "" : $MAXLIST)) . '</td><td align="right"><a href="' . DOL_URL_ROOT . '/compta/facture/list.php?socid=' . $object->id . '">' . $langs->trans("AllBills") . ' <span class="badge">' . $num . '</span></a></td>';
                print '<td width="20px" align="right"><a href="' . DOL_URL_ROOT . '/compta/facture/stats/index.php?socid=' . $object->id . '">' . img_picto($langs->trans("Statistics"), 'stats') . '</a></td>';
                print '</tr></table></td>';
                print '</tr>';
            }

            while ($i < $num && $i < $MAXLIST) {
                $objp = $db->fetch_object($resql);
                $var = !$var;
                print "<tr " . $bc[$var] . ">";
                print '<td class="nowrap">';
                $facturestatic->id = $objp->facid;
                $facturestatic->ref = $objp->facnumber;
                $facturestatic->type = $objp->type;
                $facturestatic->total_ht = $objp->total_ht;
                $facturestatic->total_tva = $objp->total_tva;
                $facturestatic->total_ttc = $objp->total_ttc;
                print $facturestatic->getNomUrl(1);
                print '</td>';
                if ($objp->df > 0) {
                    print '<td align="right" width="80px">' . dol_print_date($db->jdate($objp->df), 'day') . '</td>';
                } else {
                    print '<td align="right"><b>!!!</b></td>';
                }
                print '<td align="right" style="min-width: 60px">';
                print price($objp->total_ht);
                print '</td>';

                if (!empty($conf->global->MAIN_SHOW_PRICE_WITH_TAX_IN_SUMMARIES)) {
                    print '<td align="right" style="min-width: 60px">';
                    print price($objp->total_ttc);
                    print '</td>';
                }

                print '<td align="right" class="nowrap" style="min-width: 60px">' . ($facturestatic->LibStatut($objp->paye, $objp->statut, 5, $objp->am)) . '</td>';
                print "</tr>\n";
                $i++;
            }
            $db->free($resql);

            if ($num > 0)
                print "</table>";
        }
        else {
            dol_print_error($db);
        }
    }
}

//<--- -------------- FACTURE LIST END --------------- --->












//        $sql8 = 'SELECT nom FROM llx_societe WHERE nom = "BIMP"';
//        $req = $db->query($sql8) or die('Erreur SQL !<br />'.$sql.'<br />'.mysql_error());
//        if ($db->num_rows($req) > 0)
//        $data = $db->fetch_object($req);
//        print $data;

    echo '<br/>';

    echo '<pre>';
    print ("Organisme intervenant : ");
    print_r($conf->global->MAIN_INFO_SOCIETE_NOM);
    echo '<br/>';
    print ("Adresse de l'intervenant : ");
    print_r($conf->global->MAIN_INFO_SOCIETE_ADDRESS);
    print (", ");
    print_r($conf->global->MAIN_INFO_SOCIETE_ZIP);
    print (", ");
    print_r($conf->global->MAIN_INFO_SOCIETE_TOWN);
    echo '<br/>';
    echo '<br/>';
    $date01 = date("F j, Y, g:i a");
    $date02 = date('Y-m-d', strtotime($_POST['Date']));
    print ("Attestation creer le : ");
    print ($date01);
    echo '<br />';
    echo '<br/>';
    print ("Client : ");
    print_r($object->nom);
    echo '<br />';
    print ("Adresse du client : ");
    print_r($object->address);
    print (", ");
    print_r($object->zip);
    print (", ");
    print_r($object->town);
    echo '<br />';
    echo '<br />';
    //
    //
    
    //print '<a class="noborder" href="/compta/facture/list.php?sall='.$object->id.'&prefefid='.$objp->rowid.'" ';
    //print_r ($object);
    //print ("Facture Numero : "); print_r ($object->mode_reglement_id->);
//        $sql = "SELECT * FROM llx_facture WHERE fk_soc = ". $socid;
//        $req = $db->query($sql) or die('Erreur SQL !<br />'.$sql.'<br />'.mysql_error());
//        $result = "$db->fetch_object($req)";
//        echo $result['fk_soc'];
    //print_r ($conf->global);






    dol_fiche_end();


llxFooter();

$db->close();
