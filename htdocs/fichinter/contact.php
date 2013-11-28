<?php
/* Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007      Laurent Destailleur  <eldy@users.sourceforge.net>
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
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.synopsis-erp.com
  *
  */
/*
 */

/**
        \file       htdocs/fichinter/contact.php
        \ingroup    fichinter
        \brief      Onglet de gestion des contacts de fiche d'intervention
        \version    $Id: contact.php,v 1.9 2008/02/25 20:03:26 eldy Exp $
*/

require ("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/fichinter.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');

$langs->load("interventions");
$langs->load("sendings");
$langs->load("companies");

$fichinterid = isset($_GET["id"])?$_GET["id"]:'';


// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'synopsisficheinter', $fichinterid, 'fichinter');

$formcompany = new FormCompany($db);

/*
 * Ajout d'un nouveau contact
 */

if ($_POST["action"] == 'addcontact' && $user->rights->synopsisficheinter->creer)
{

    $result = 0;
    $fichinter = new Fichinter($db);
    $result = $fichinter->fetch($_GET["id"]);

    if ($result > 0 && $_GET["id"] > 0)
    {
        $result = $fichinter->add_contact($_POST["contactid"], $_POST["type"], $_POST["source"]);
    }

    if ($result >= 0)
    {
        Header("Location: contact.php?id=".$fichinter->id);
        exit;
    }
    else
    {
        if ($fichinter->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
        {
            $langs->load("errors");
            $mesg = '<div class="error ui-state-error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
        }
        else
        {
            $mesg = '<div class="error ui-state-error">'.$fichinter->error.'</div>';
        }
    }
}
// modification d'un contact. On enregistre le type
if ($_POST["action"] == 'updateligne' && $user->rights->synopsisficheinter->creer)
{
    $fichinter = new Fichinter($db);
    if ($fichinter->fetch($_GET["id"]))
    {
        $contact = $fichinter->detail_contact($_POST["elrowid"]);
        $type = $_POST["type"];
        $statut = $contact->statut;

        $result = $fichinter->update_contact($_POST["elrowid"], $statut, $type);
        if ($result >= 0)
        {
            $db->commit();
        } else
        {
            dol_print_error($db, "result=$result");
            $db->rollback();
        }
    } else
    {
        dol_print_error($db);
    }
}

// bascule du statut d'un contact
if ($_GET["action"] == 'swapstatut' && $user->rights->synopsisficheinter->creer)
{
    $fichinter = new Fichinter($db);
    if ($fichinter->fetch($_GET["id"]))
    {
        $contact = $fichinter->detail_contact($_GET["ligne"]);
        $id_type_contact = $contact->fk_c_type_contact;
        $statut = ($contact->statut == 4) ? 5 : 4;

        $result = $fichinter->update_contact($_GET["ligne"], $statut, $id_type_contact);
        if ($result >= 0)
        {
            $db->commit();
        } else
        {
            dol_print_error($db, "result=$result");
            $db->rollback();
        }
    } else
    {
        dol_print_error($db);
    }
}

// Efface un contact
if ($_GET["action"] == 'deleteline' && $user->rights->synopsisficheinter->creer)
{
    $fichinter = new Fichinter($db);
    $fichinter->fetch($_GET["id"]);
    $result = $fichinter->delete_contact($_GET["lineid"]);

    if ($result >= 0)
    {
        Header("Location: contact.php?id=".$fichinter->id);
        exit;
    }
    else {
        dol_print_error($db);
    }
}


llxHeader();

$html = new Form($db);
$contactstatic=new Contact($db);


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
if (isset($mesg)) print $mesg;

$id = $_GET["id"];
if ($id > 0)
{
    $fichinter = New Fichinter($db);
    if ($fichinter->fetch($_GET['id']) > 0)
    {
        $soc = new Societe($db, $fichinter->socid);
        $soc->fetch($fichinter->socid);


        $head = synopsisfichinter_prepare_head($fichinter);
        dol_fiche_head($head, 'contact', $langs->trans("InterventionCard"));


        /*
        *   Fiche intervention synthese pour rappel
        */
        print '<table cellpadding=15 class="border" width="100%">';

        // Ref
        print '<tr><th class="ui-widget-header ui-state-default" width="25%">'.$langs->trans("Ref").'</th>
                   <td  class="ui-widget-content" colspan="3">';
        print $fichinter->ref;
        print "</td></tr>";

        // Customer
        if ( is_null($fichinter->client) )
            $fichinter->fetch_client();

        print "<tr><th class=\"ui-widget-header ui-state-default\">".$langs->trans("Company")."</th>";
        print '<td colspan="3" class="ui-widget-content">'.$fichinter->client->getNomUrl(1).'</td></tr>';
        print "</table>";

        print '</div>';

        /*
        * Lignes de contacts
        */
        echo '<br><table class="noborder" width="100%">';

        /*
        * Ajouter une ligne de contact
        * Non affiche en mode modification de ligne
        */
        if ($_GET["action"] != 'editline' && $user->rights->synopsisficheinter->creer)
        {
            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans("Source").'</td>';
            print '<td>'.$langs->trans("Company").'</td>';
            print '<td>'.$langs->trans("Contacts").'</td>';
            print '<td>'.$langs->trans("ContactType").'</td>';
            print '<td colspan="3">&nbsp;</td>';
            print "</tr>\n";

            $var = false;

            print '<form action="contact.php?id='.$id.'" method="post">';
            print '<input type="hidden" name="action" value="addcontact">';
            print '<input type="hidden" name="source" value="internal">';
            print '<input type="hidden" name="id" value="'.$id.'">';

            // Ligne ajout pour contact interne
            print "<tr $bc[$var]>";

            print '<td>';
            print $langs->trans("Internal");
            print '</td>';

            print '<td colspan="1">';
            print $conf->global->MAIN_INFO_SOCIETE_NOM;
            print '</td>';

            print '<td colspan="1">';
            // On recupere les id des users deja selectionnes
            //$userAlreadySelected = $fichinter->getListContactId('internal');     // On ne doit pas desactiver un contact deja selectionner car on doit pouvoir le seclectionner une deuxieme fois pour un autre type
            $html->select_users($user->id,'contactid',0,$userAlreadySelected);
            print '</td>';
            print '<td>';
            $formcompany->selectTypeContact($fichinter, '', 'type','internal');
            print '</td>';
            print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
            print '</tr>';

            print '</form>';

            print '<form action="contact.php?id='.$id.'" method="post">';
            print '<input type="hidden" name="action" value="addcontact">';
            print '<input type="hidden" name="source" value="external">';
            print '<input type="hidden" name="id" value="'.$id.'">';

            // Ligne ajout pour contact externe
            $var=!$var;
            print "<tr $bc[$var]>";

            print '<td>';
            print $langs->trans("External");
            print '</td>';

            print '<td colspan="1">';
            $selectedCompany = isset($_GET["newcompany"])?$_GET["newcompany"]:$fichinter->client->id;
            $selectedCompany = $formcompany->selectCompaniesForNewContact($fichinter, 'id', $selectedCompany, $htmlname = 'newcompany');
            print '</td>';

            print '<td colspan="1">';
            // On recupere les id des contacts deja selectionnes
            //$contactAlreadySelected = $fichinter->getListContactId('external');    // On ne doit pas desactiver un contact deja selectionner car on doit pouvoir le seclectionner une deuxieme fois pour un autre type
            $nbofcontacts=$html->select_contacts($selectedCompany, $selected = '', $htmlname = 'contactid',0,$contactAlreadySelected);
            if ($nbofcontacts == 0) print $langs->trans("NoContactDefined");
            print '</td>';
            print '<td>';
            $formcompany->selectTypeContact($fichinter, '', 'type','external');
            print '</td>';
            print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"';
            if (! $nbofcontacts) print ' disabled="true"';
            print '></td>';
            print '</tr>';

            print "</form>";

            print '<tr><td colspan="6">&nbsp;</td></tr>';
        }

        // Liste des contacts lies
        print '<tr class="liste_titre">';
        print '<td>'.$langs->trans("Source").'</td>';
        print '<td>'.$langs->trans("Company").'</td>';
        print '<td>'.$langs->trans("Contacts").'</td>';
        print '<td>'.$langs->trans("ContactType").'</td>';
        print '<td align="center">'.$langs->trans("Status").'</td>';
        print '<td colspan="2">&nbsp;</td>';
        print "</tr>\n";

        $societe = new Societe($db);
        $var = true;

        foreach(array('internal','external') as $source)
        {
            $tab = $fichinter->liste_contact(-1,$source);
            $num=sizeof($tab);

            $i = 0;
            while ($i < $num)
            {
                $var = !$var;

                print '<tr '.$bc[$var].' valign="top">';

                // Source
                print '<td align="left">';
                if ($tab[$i]['source']=='internal') print $langs->trans("Internal");
                if ($tab[$i]['source']=='external') print $langs->trans("External");
                print '</td>';

                // Societe
                print '<td align="left">';
                if ($tab[$i]['socid'] > 0)
                {
                    $societe->fetch($tab[$i]['socid']);
                    print $societe->getNomUrl(1);
                }
                if ($tab[$i]['socid'] < 0)
                {
                    print $conf->global->MAIN_INFO_SOCIETE_NOM;
                }
                if (! $tab[$i]['socid'])
                {
                    print '&nbsp;';
                }
                print '</td>';

                // Contact
                print '<td>';
                if ($tab[$i]['source']=='internal')
                {
                    print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$tab[$i]['id'].'">';
                    print img_object($langs->trans("ShowUser"),"user").' '.$tab[$i]['nom'].'</a>';
                }
                if ($tab[$i]['source']=='external')
                {
                    print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$tab[$i]['id'].'">';
                    print img_object($langs->trans("ShowContact"),"contact").' '.$tab[$i]['nom'].'</a>';
                }
                print '</td>';

                // Type de contact
                print '<td>'.$tab[$i]['libelle'].'</td>';

                // Statut
                print '<td align="center">';
                // Activation desativation du contact
                if ($fichinter->statut >= 0) print '<a href="contact.php?id='.$fichinter->id.'&amp;action=swapstatut&amp;ligne='.$tab[$i]['rowid'].'">';
                print $contactstatic->LibStatut($tab[$i]['status'],3);
                if ($fichinter->statut >= 0) print '</a>';
                print '</td>';

                // Icon update et delete
                print '<td align="center" nowrap>';
                if ($fichinter->statut < 5 && $user->rights->synopsisficheinter->creer)
                {
                    print '&nbsp;';
                    print '<a href="contact.php?id='.$fichinter->id.'&amp;action=deleteline&amp;lineid='.$tab[$i]['rowid'].'">';
                    print img_delete();
                    print '</a>';
                }
                print '</td>';

                print "</tr>\n";

                $i ++;
            }
        }
        print "</table>";
    }
    else
    {
        // Fiche intervention non trouvee
        print "Fiche intervention inexistante ou acc�s refus�";
    }
}

$db->close();

llxFooter('$Date: 2008/02/25 20:03:26 $');
?>