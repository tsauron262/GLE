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
            print '<table class="noborder" width="100%">';

            print '<tr class="liste_titre">';
            print '<td colspan="5"><table width="100%" class="nobordernopadding"><tr><td>' . $langs->trans("Les dernières factures clients", ($num <= $MAXLIST ? "" : $MAXLIST)) . '</td><td align="right"><a href="' . DOL_URL_ROOT . '/compta/facture/list.php?socid=' . $object->id . '">' . $langs->trans("Toutes les factures") . ' <span class="badge">' . $num . '</span></a></td>';
            print '<td width="20px" align="right"><a href="' . DOL_URL_ROOT . '/compta/facture/stats/index.php?socid=' . $object->id . '">' . img_picto($langs->trans("Statistics"), 'stats') . '</a></td>';
            print '</tr></table></td>';
            print '</tr>';

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






//<--- -------------- TABLEAU START --------------- --->


$date01 = date("F j, Y, g:i a");

print '<div class="fichecenter"><div class="fichehalfleft">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border" width="100%">';
echo '<br />';
echo '<br />';
echo '<br />';

// Orga intervenant
print '<tr><td class="titelfield">' . $langs->trans("Organisme intervenant : ") . '</td><td colspan="3">';
print $conf->global->MAIN_INFO_SOCIETE_NOM;
print "</td></tr>";

// Adresse de l'intervenant
print '<tr><td class="titelfield">' . $langs->trans("Adresse de l'intervenant : ") . '</td><td colspan="3">';
print $conf->global->MAIN_INFO_SOCIETE_ADDRESS;
print (", ");
print_r($conf->global->MAIN_INFO_SOCIETE_ZIP);
print (", ");
print_r($conf->global->MAIN_INFO_SOCIETE_TOWN);
print "</td></tr>";

// Date de Creat'
print '<tr><td class="titelfield">' . $langs->trans("Attestation" . "<br />" . "creer le : ") . '</td><td colspan="3">';
print $date01;
print "</td></tr>";

// Nom du client
print '<tr><td class="titelfield">' . $langs->trans("Client : ") . '</td><td colspan="3">';
print $object->nom;
print "</td></tr>";

// Adresse du Client
print '<tr><td class="titelfield">' . $langs->trans("Adresse du client : ") . '</td><td colspan="3">';
print $object->address;
print (", ");
print_r($object->zip);
print (", ");
print_r($object->town);
print "</td></tr>";


//<--- -------------- TABLEAU END --------------- --->








//<--- -------------- PAS IMPORTANT START --------------- --->


//        $sql8 = 'SELECT nom FROM llx_societe WHERE nom = "BIMP"';
//        $req = $db->query($sql8) or die('Erreur SQL !<br />'.$sql.'<br />'.mysql_error());
//        if ($db->num_rows($req) > 0)
//        $data = $db->fetch_object($req);
//        print $data;

    
    //print '<a class="noborder" href="/compta/facture/list.php?sall='.$object->id.'&prefefid='.$objp->rowid.'" ';
//print_r ($object);
//print ("Facture Numero : "); print_r ($object->mode_reglement_id->);
//        $sql = "SELECT * FROM llx_facture WHERE fk_soc = ". $socid;
//        $req = $db->query($sql) or die('Erreur SQL !<br />'.$sql.'<br />'.mysql_error());
//        $result = "$db->fetch_object($req)";
//        echo $result['fk_soc'];
//print_r ($conf->global);


//<--- -------------- PAS IMPORTANT END --------------- --->




dol_fiche_end();


llxFooter();

$db->close();
