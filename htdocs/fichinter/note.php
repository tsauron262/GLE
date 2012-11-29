<?php
/* Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
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
  *//*
 */

/**
        \file       htdocs/fichinter/note.php
        \ingroup    fichinter
        \brief      Fiche d'information sur une fiche d'intervention
        \version    $Id: note.php,v 1.7 2008/02/25 20:03:26 eldy Exp $
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/fichinter.lib.php");

$langs->load('companies');

$fichinterid = isset($_GET["id"])?$_GET["id"]:'';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'synopsisficheinter', $fichinterid, 'fichinter');


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_POST["action"] == 'update_public' && $user->rights->synopsisficheinter->creer)
{
    $fichinter = new Fichinter($db);
    $fichinter->fetch($_GET['id']);

    $db->begin();

    $res=$fichinter->update_note_public($_POST["note_public"],$user);
    if ($res < 0)
    {
        $mesg='<div class="error ui-state-error">'.$fichinter->error.'</div>';
        $db->rollback();
    }
    else
    {
        $db->commit();
    }
}

if ($_POST['action'] == 'update' && $user->rights->synopsisficheinter->creer)
{
    $fichinter = new Fichinter($db);
    $fichinter->fetch($_GET['id']);

    $db->begin();

    $res=$fichinter->update_note($_POST["note_private"],$user);
    if ($res < 0)
    {
        $mesg='<div class="error ui-state-error">'.$fichinter->error.'</div>';
        $db->rollback();
    }
    else
    {
        $db->commit();
    }
}



/******************************************************************************/
/* Affichage fiche                                                            */
/******************************************************************************/

llxHeader();

$html = new Form($db);

if ($_GET['id'])
{
    if ($mesg) print $mesg;

    $fichinter = new Fichinter($db);
    if ( $fichinter->fetch($_GET['id']) )
    {
        $societe = new Societe($db);
        if ( $societe->fetch($fichinter->socid) )
        {
            $head = Synopsis_fichinter_prepare_head($fichinter);
            dol_fiche_head($head, 'note', $langs->trans('InterventionCard'));

            print '<table cellpadding=15 class="border" width="100%">';

            print '<tr><th class="ui-widget-header ui-state-default" width="25%">'.$langs->trans('Ref').'</th>
                       <td colspan="3" class="ui-widget-content">'.$fichinter->ref.'</td></tr>';

          // Societe
            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans('Company').'</th>
                       <td colspan="3" class="ui-widget-content">'.$societe->getNomUrl(1).'</td></tr>';

                // Date
            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans('Date').'</th>
                       <td colspan="3" class="ui-widget-content">';
            print dol_print_date($fichinter->date,'day');
            print '</td>';
            print '</tr>';

                // Note publique
            print '<tr><th valign="top" class="ui-widget-header ui-state-default">'.$langs->trans("NotePublic").' :</th>';
                print '<td valign="top" colspan="3" class="ui-widget-content">';
            if ($_GET["action"] == 'edit')
            {
                print '<form method="post" action="note.php?id='.$fichinter->id.'">';
                print '<input type="hidden" name="action" value="update_public">';
                print '<textarea name="note_public" cols="80" rows="8">'.$fichinter->note_public."</textarea><br>";
                print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
                print '</form>';
            } else {
                print ($fichinter->note_public?nl2br($fichinter->note_public):"&nbsp;");
            }
                print "</td></tr>";

                // Note privee
                if (! $user->societe_id)
                {
                    print '<tr><th  class="ui-widget-header ui-state-default" valign="top">'.$langs->trans("NotePrivate").' :</th>';
                    print '<td valign="top" colspan="3" class="ui-widget-content">';
                    if ($_GET["action"] == 'edit')
                    {
                        print '<form method="post" action="note.php?id='.$fichinter->id.'">';
                        print '<input type="hidden" name="action" value="update">';
                        print '<textarea name="note_private" cols="80" rows="8">'.$fichinter->note_private."</textarea><br>";
                        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
                        print '</form>';
                    } else {
                        print ($fichinter->note_private?nl2br($fichinter->note_private):"&nbsp;");
                    }
                    print "</td></tr>";
                }

            print "</table>";

            print '</div>';

            /*
            * Actions
            */

            print '<div class="tabsAction">';
            if ($user->rights->synopsisficheinter->creer && $_GET['action'] <> 'edit')
            {
                print '<a class="butAction" href="note.php?id='.$fichinter->id.'&amp;action=edit">'.$langs->trans('Modify').'</a>';
            }
            print '</div>';
        }
    }
}
$db->close();
llxFooter('$Date: 2008/02/25 20:03:26 $ - $Revision: 1.15 ');
?>
