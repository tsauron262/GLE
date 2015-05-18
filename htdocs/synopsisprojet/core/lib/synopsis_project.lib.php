<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
  * Version 1.0
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
/*
 * or see http://www.gnu.org/
 */

/**
        \file       htdocs/lib/project.lib.php
        \brief      Ensemble de fonctions de base pour le module projet
        \ingroup    societe
        \version    $Id: project.lib.php,v 1.3 2008/04/19 17:26:42 eldy Exp $
*/
require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/synopsisproject.class.php");

function synopsis_project_prepare_head($projet)
{
    global $langs, $conf, $user,$db;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/card.php?id='.$projet->id;
    $head[$h][1] = $langs->trans("Project");
    $head[$h][2] = 'project';
    $h++;

    if ($projet->hasTache == 1)
    {
        $head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/tasks/card.php?id='.$projet->id;
        $head[$h][1] = $langs->trans("Tasks");
        $head[$h][2] = 'tasks';
        $h++;
    } else if ($projet->hasTacheLight == 1)
    {
        $head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/tasks/card.php?mode=light&id='.$projet->id;
        $head[$h][1] = $langs->trans("Tasks");
        $head[$h][2] = 'tasks';
        $h++;
    }

    if ($projet->hasGantt == 1)
    {
        $head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/gantt/gantt.php?id='.$projet->id;
        $head[$h][1] = $langs->trans("Gantt");
        $head[$h][2] = 'Gantt';
        $h++;
    }
    if ($projet->hasCout == 1)
    {
        $head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/cout.php?id='.$projet->id;
        $head[$h][1] = $langs->trans("Co&ucirc;t");
        $head[$h][2] = 'Cout';
        $h++;
    }
    if ($projet->hasRH == 1 && 0)
    {
        $head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/Hressources.php?id='.$projet->id;
        $head[$h][1] = $langs->trans("Ressources humaines");
        $head[$h][2] = 'HRessources';
        $h++;
    }
    if ($projet->hasRessources == 1 && 0)
    {

        $head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/ressources.php?id='.$projet->id;
        $head[$h][1] = $langs->trans("Ressources mat&eacute;riels");
        $head[$h][2] = 'Ressources';
        $h++;
    }
    if ($projet->hasRisque == 1)
    {
        $head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/risque.php?id='.$projet->id;
        $head[$h][1] = $langs->trans("Risque");
        $head[$h][2] = 'Risque';
        $h++;
    }
    if ($projet->hasImputation == 1)
    {
        $head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/histo_imputations.php?fromProjet=1&id='.$projet->id;
        $head[$h][1] = $langs->trans("Imputations");
        $head[$h][2] = 'Imputations';
        $h++;
    }
    if ($projet->hasPointage == 1)
    {
        $head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/pointage.php?id='.$projet->id;
        $head[$h][1] = $langs->trans("Pointage par &eacute;quipe");
        $head[$h][2] = 'Pointage';
        $h++;
    }
    if ($projet->hasAgenda == 1)
    {
        $head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/agenda.php?id='.$projet->id;
        $head[$h][1] = $langs->trans("Agenda projet");
        $head[$h][2] = 'Agenda';
        $h++;

    }
    if ($projet->hasDocuments == 1)
    {

        $head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/document.php?id='.$projet->id;
        $head[$h][1] = $langs->trans("Documents");
        $head[$h][2] = 'Document';
        $h++;
    }
    if ($projet->hasStats == 1 && 0)
    {

        $head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/stats.php?id='.$projet->id;
        $head[$h][1] = $langs->trans("Stats");
        $head[$h][2] = 'Stats';
        $h++;
    }
    if ($projet->hasReferents == 1 && ((isset($conf->propal) && $conf->propal->enabled) || (isset($conf->commande->enabled) && $conf->commande->enabled) || (isset($conf->facture->enabled) && $conf->facture->enabled)))
    {
        $head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/element.php?id='.$projet->id;
        $head[$h][1] = $langs->trans("Referers");
        $head[$h][2] = 'element';
        $h++;
    }

    if (isset($conf->global->MAIN_MODULE_SYNOPSISPROCESS) && $conf->global->MAIN_MODULE_SYNOPSISPROCESS && $user->rights->process->lire &&  DoesElementhasProcess($db,'Project'))
    {
        $head[$h][0] = DOL_URL_ROOT.'/Synopsis_Process/listProcessForElement.php?type=Project&id='.$projet->id;
        $head[$h][1] = $langs->trans("Process");
        $head[$h][2] = 'process';
        $head[$h][4] = 'ui-icon ui-icon-gear';
        $h++;
    }

    $head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/info.php?id='.$projet->id;
    $head[$h][1] = $langs->trans("Info");
    $head[$h][2] = 'info';
    $h++;
    complete_head_from_modules($conf,$langs,$projet,$head,$h,'project');


    return $head;
}


/**
        \brief      Affiche la liste deroulante des projets d'une societe donnee
        \param      socid       Id societe
        \param      selected    Id projet pre-selectionne
        \param      htmlname    Nom de la zone html
        \return     int         Nbre de projet si ok, <0 si ko
*/
function select_projects($socid=false, $selected='', $htmlname='projectid')
{
    global $db;

    // On recherche les projets
    $sql = "";
    if ($socid)
    {
        $sql = 'SELECT p.rowid, p.title, p.ref FROM ';
        $sql.= 'babel_projet as p';
        $sql.= " WHERE fk_soc='".$socid."'";
        $sql.= " ORDER BY p.title ASC";
    } else {
        $sql = 'SELECT p.rowid, p.title, ".MAIN_DB_PREFIX."societe.nom, p.ref FROM ';
        $sql.= 'babel_projet as p';
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe ON fk_soc=".MAIN_DB_PREFIX."societe.rowid";
        $sql.= " ORDER BY ".MAIN_DB_PREFIX."societe.nom ASC, p.title ASC";
    }
    dol_syslog("project.lib::select_projects sql=".$sql);
    $resql=$db->query($sql);
    if ($resql)
    {
        print '<select class="flat" name="'.$htmlname.'">';
        print '<option value="0">Selectionner -></option>';
        $num = $db->num_rows($resql);
        $i = 0;
        if ($num)
        {
            while ($i < $num)
            {
                $display = "";
                $obj = $db->fetch_object($resql);
                if (!$socid)
                {
                    $display = $obj->nom . " - ";
                }
                $display .= $obj->title;
                $display .= " - ".$obj->ref;
                if (!empty($selected) && $selected == $obj->rowid)
                {
                    print '<option value="'.$obj->rowid.'" selected="true">'.$display.'</option>';
                }
                else
                {
                    print '<option value="'.$obj->rowid.'">'.$display.'</option>';
                }
                $i++;
            }
        }
        print '</select>';
        $db->free($resql);
        return $num;
    }
    else
    {
        dol_print_error($db);
        return -1;
    }
}

?>
