<?php
/* Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
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
  * Infos on http://www.finapro.fr
  *
  */
/*
 */

/**
        \file       htdocs/synopsis_demandeinterv/note.php
        \ingroup    demandeInterv
        \brief      Fiche d'information sur une fiche d'intervention
        \version    $Id: note.php,v 1.7 2008/02/25 20:03:26 eldy Exp $
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/Synopsis_DemandeInterv/demandeInterv.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/demandeInterv.lib.php");

$langs->load('companies');

$demandeIntervid = isset($_GET["id"])?$_GET["id"]:'';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'synopsisdemandeinterv', $demandeIntervid, 'demandeInterv');


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_POST["action"] == 'update_public' && $user->rights->synopsisdemandeinterv->creer)
{
    $demandeInterv = new demandeInterv($db);
    $demandeInterv->fetch($_GET['id']);

    $db->begin();

    $res=$demandeInterv->update_note_public($_POST["note_public"],$user);
    if ($res < 0)
    {
        $mesg='<div class="error ui-state-error">'.$demandeInterv->error.'</div>';
        $db->rollback();
    }
    else
    {
        $db->commit();
    }
}

if ($_POST['action'] == 'update' && $user->rights->synopsisdemandeinterv->creer)
{
    $demandeInterv = new demandeInterv($db);
    $demandeInterv->fetch($_GET['id']);

    $db->begin();

    $res=$demandeInterv->update_note($_POST["note_private"],$user);
    if ($res < 0)
    {
        $mesg='<div class="error ui-state-error">'.$demandeInterv->error.'</div>';
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

    $demandeInterv = new demandeInterv($db);
    if ( $demandeInterv->fetch($_GET['id']) )
    {
        $societe = new Societe($db);
        if ( $societe->fetch($demandeInterv->socid) )
        {
            $head = demandeInterv_prepare_head($demandeInterv);
            dol_fiche_head($head, 'note', $langs->trans('DI'));

            print '<table class="border" width="100%">';

            print '<tr><td class="ui-widget-header ui-state-default" width="25%">'.$langs->trans('Ref').'</td>
                       <td colspan="3" class="ui-widget-content">'.$demandeInterv->ref.'</td></tr>';

            // Societe
            print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans('Company').'</td>
                       <td colspan="3" class="ui-widget-content">'.$societe->getNomUrl(1).'</td></tr>';

            // Date
            print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans('Date').'</td>
                       <td colspan="3" class="ui-widget-content">';
            print dol_print_date($db->jdate($demandeInterv->date),'day');
            print '</td>';
            print '</tr>';

            // Note publique
            print '<tr><td class="ui-widget-header ui-state-default" valign="top">'.$langs->trans("NotePublic").' :</td>';
            print '<td valign="top" colspan="3" class="ui-widget-content">';
            if ($_GET["action"] == 'edit')
            {
                print '<form method="post" action="note.php?id='.$demandeInterv->id.'">';
                print '<input type="hidden" name="action" value="update_public">';
                print '<textarea name="note_public" cols="80" rows="8">'.$demandeInterv->note_public."</textarea><br>";
                print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
                print '</form>';
            }  else {
                print ($demandeInterv->note_public?nl2br($demandeInterv->note_public):"&nbsp;");
            }
            print "</td></tr>";

            // Note privee
            if (! $user->societe_id)
            {
                print '<tr><td  class="ui-widget-header ui-state-default" valign="top">'.$langs->trans("NotePrivate").' :</td>';
                print '<td valign="top" colspan="3" class="ui-widget-content">';
                if ($_GET["action"] == 'edit')
                {
                    print '<form method="post" action="note.php?id='.$demandeInterv->id.'">';
                    print '<input type="hidden" name="action" value="update">';
                    print '<textarea name="note_private" cols="80" rows="8">'.$demandeInterv->note_private."</textarea><br>";
                    print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
                    print '</form>';
                } else  {
                    print ($demandeInterv->note_private?nl2br($demandeInterv->note_private):"&nbsp;");
                }
                print "</td></tr>";
            }
            print "</table>";

            print '</div>';

            /*
            * Actions
            */

            print '<div class="tabsAction">';
            if ($user->rights->synopsisdemandeinterv->creer && $_GET['action'] <> 'edit')
            {
                print '<a class="butAction" href="note.php?id='.$demandeInterv->id.'&amp;action=edit">'.$langs->trans('Modify').'</a>';
            }
            print '</div>';
        }
    }
}
$db->close();
llxFooter('$Date: 2008/02/25 20:03:26 $ - $Revision: 1.15 ');
?>
