<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id: task.php,v 1.6 2007/05/28 11:51:00 hregis Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/synopsisprojet/tasks/task.php,v $
 */
/*
  ** GLE by Synopsis et DRSI
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
/**
   \file       htdocs/synopsisprojet/tasks/task.php
   \ingroup    projet
   \brief      Fiche taches d'un projet
   \version    $Revision: 1.6 $
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/core/lib/synopsis_project.lib.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/synopsisproject.class.php");

if (!$user->rights->synopsisprojet->lire) accessforbidden();



// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'synopsisprojet', $projetid, 'Synopsis_projet_view');


if(isset($_REQUEST['action']) && $_REQUEST['action'] == "modtask")
{
        $taskId=$_REQUEST['id'];
        $name = addslashes($_REQUEST['label']);
        $parentId = $_REQUEST['parent'];
        $dur=$_REQUEST['dur'] * 3600;
        if ($parentId <0) $parentId ="";
        $note = addslashes($_REQUEST['note']);
        $statut = addslashes($_REQUEST['statut']);
        $progress = $_REQUEST['slidercomplet'];
        $progress = floatval($progress);
        $progress = preg_replace("/[,]/",".",$progress);
        $description = addslashes($_REQUEST['description']);
        $shortDescription = addslashes($_REQUEST['shortDesc']);
        $url =addslashes($_REQUEST['url']);
        if (! preg_match('/^[http:\/\/]/',$url))
        {
            $url = 'http://'.$url;
        }
        $priority = $_REQUEST['type'];

        $datedeb = $_REQUEST['dateo'];
        $debdateUS="";
        $debts="0";
        $finddateUS="";
        $fints="0";
        if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/",$datedeb,$arr))
        {
            $debdateUS = $arr[3]."-".$arr[2]."-".$arr[1]." ".$arr[4].":".$arr[5];
            $debts = strtotime($arr[3]."-".$arr[2]."-".$arr[1]);
            $debts += $arr[5]*60 + $arr[4] * 3600;
        }

        $level = 1;
        //Get parent Level
        if ($parentId . "x" != "x")
        {
            $requete = "SELECT level FROM ".MAIN_DB_PREFIX."projet_task WHERE rowid = ".$parentId;
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $level = $res->level + 1;

        }



        $db->begin();

        if ($parentId . 'x' == 'x')
        {
            $parentId = "NULL";
        }

        $requete = "UPDATE ".MAIN_DB_PREFIX."projet_task
                       SET fk_task_parent = $parentId,
                           label = '$name',
                           "//duration = $dur,
                           ."statut = '$statut',
                           
                           note = '$note',
                           progress = $progress,
                           description = '$description',
                           shortDesc = '$shortDescription',
                           url = '$url',
                           priority = $priority,
                           level = $level
                     WHERE rowid = ".$taskId;
                     //dateo = '$debdateUS',
        $sql = $db->query($requete);
        if ($sql)
        {
            //ressource et role et dependance

                $db->commit();

                require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/synopsisproject.class.php");
                $taskObj = new SynopsisProjectTask($db);
                $taskObj->fetch($taskId);
                //appel triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($db);
                global $user;
                $result=$interface->run_triggers('PROJECT_UPDATE_TASK',$taskObj,$user,$langs,$conf);
                if ($result < 0) { $error++; $errors=$interface->errors; }
                 //Fin appel triggers

        } else {
            $xml = "<response>Error</response>";
            $xml .= "<requete>".$requete."</requete>";
            $xml .= "<error>".$db->lastqueryerror."</error>";
            $xml .= "<error>".$db->lasterror."</error>";
            $xml .= "<error>".print_r($db,true)."</error>";
            $db->rollback();
        }
}

$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

$js =<<<EOF
<script>
var Complet = 0;
    jQuery(document).ready(function(){
        jQuery('.datepicker').datepicker();
        jQuery("#progressBar").progressbar({ value: Complet });
        jQuery('#accordion').accordion({navigation: true});
        jQuery('#slider').slider({
            animate: true,
            range: 'min',
            max: 100,
            min: 0,
            step: 1,
            tooltips: function(t){ return (Math.round(t*100)/100 + " %") },
            change: function(event, ui){
                jQuery('#slidercomplet').val(ui.value);
            },
            slide: function(event, ui)
            {
            },
            values: 0
        });

    });
</script>
<style type='text/css'>.ui-progressbar{ height: 13px; background-color: #ffffff; margin: 0px;}</style>
<style type='text/css'>.ui-progressbar-value{ border:1px solid #000000; }</style>
<style>#ui-datepicker-div, #ui-timepicker-div { z-Index: 99999999; }</style>

EOF;
$js .= ' <script src="'.$jqueryuipath.'/ui.slider.js" type="text/javascript"></script>';


llxHeader($js,$langs->trans("Task"));

if ($_REQUEST["id"] > 0)
{

  /*
   * Fiche projet en mode visu
   *
   */
    $task = new SynopsisProjectTask($db);
    if ($task->fetch($_REQUEST["id"]) > 0 )
    {
        $projet = new SynopsisProject($db);
        $projet->fetch($task->projet_id);
        $projet->societe->fetch($projet->societe->id);

        
        $droitModif = ($task->user_creat->id == $user->id || $projet->user_resp_id == $user->id || $user->rights->synopsisprojet->modAll);

        $h=0;
        $head = array();
        $head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/tasks/task.php?id='.$task->id;
        $head[$h][1] = $langs->trans("Tasks");
        $head[$h][2] = 'tasks';
        $h++;

        dol_fiche_head($head, 'tasks', $langs->trans("Tasks"));

        if ($droitModif && isset($_REQUEST['action']) && $_REQUEST['action'] == 'modify')
        {
            print '<form method="POST" action="task.php?id='.$task->id.'">';
            print '<input type="hidden" name="action" value="modtask">';
            print '<table width="100%" cellpadding=15>';

            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Ref").'</th><td class="ui-widget-content">'.$projet->ref.'</td>';
            print '    <th class="ui-widget-header ui-state-default">'.$langs->trans("Label").'</th><td class="ui-widget-content">'.$projet->getNomUrl(1).'</td></tr>';

            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Task").'</th><td class="ui-widget-content" colspan="1"><input name="label" value="'.$task->label.'"></td>';
            print '    <th class="ui-widget-header ui-state-default">'.$langs->trans("Company").'</th><td class="ui-widget-content">'.$projet->societe->getNomUrl(1).'</td></tr>';
            $requete = "SELECT *
                          FROM ".MAIN_DB_PREFIX."projet_task
                         WHERE fk_projet = " .$_REQUEST['id'];
            $sql = $db->query($requete);
            $optDependStr = "";
            $optGrpStr = "";
            while ($res = $db->fetch_object($sql))
            {
                $optDependStr .= "<option value='".$res->rowid."'>".$res->label."</option>";
                //si c'est un group
                if ($res->priority == 3 && $res->rowid == $task->fk_task_parent)
                {
                    $optGrpStr .= "<option value='".$res->rowid."'>".$res->label."</option>";
                } else if ($res->priority == 3){
                    $optGrpStr .= "<option value='".$res->rowid."'>".$res->label."</option>";
                }
            }
            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Parent").'</th><td class="ui-widget-content" colspan="1">';
            print '         <SELECT id="parent"  name="parent">';
            print '             <option value="-2">S&eacute;lection-></option>';
            if (!$res->fk_task_parent > 0)
                print '             <option SELECTED value="-1">Racine du projet</option>';
            else
                print '             <option value="-1">Racine du projet</option>';

            print  $optGrpStr;
            print '         </SELECT>';
            print '</td>';

            print '    <th class="ui-widget-header ui-state-default">'.$langs->trans("Type de t&acirc;che").'</th><td class="ui-widget-content" colspan="1">';
            print '        <SELECT id="type"  name="type">';
            if ($res->priority == 1)
                print '          <option SELECTED value="1">Etape</option>';
            else
                print '          <option value="1">Etape</option>';
            if ($res->priority == 2)
                print '          <option SELECTED value="2">T&acirc;che</option>';
            else
                print '          <option value="2">T&acirc;che</option>';
            if ($res->priority == 3)
                print '          <option SELECTED value="3">Groupe</option>';
            else
                print '          <option value="3">Groupe</option>';
            print '        </SELECT>';

            print '</td></tr>';

            //print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Date d&eacute;but").'</th><td class="ui-widget-content" colspan="3">'.$task->dateoFRFull.'</td>';
            $requete ="SELECT * FROM ".MAIN_DB_PREFIX."projet_task_time WHERE fk_task = ".$task->id;
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Date d&eacute;but").'</th><td class="ui-widget-content" colspan="3">'.$res->task_date.'</td>';
            $totCompute = 0;
            $requete ="SELECT SUM(task_duration) / 36 as dur FROM ".MAIN_DB_PREFIX."projet_task_time WHERE fk_task = ".$task->id;
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $totCompute = round($res->dur)/100;
            //print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Dur Pr&eacute;v totale (h)").'</th><td class="ui-widget-content" colspan="1"><input name="dur" id="dur" value="'.round($task->duration*100/3600) /100 .'">&nbsp;<span onClick="jQuery(\'input#dur\').val('.$totCompute.');" label="Reajuster selon les attributions" style="cursor: pointer; display:inline-block;" class="ui-icon ui-icon-refresh"></span></td>';
			print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Dur Pr&eacute;v totale").'</th><td class="ui-widget-content" colspan="1">'.$projet->sec2hour($task->duration).'</td>';
            print '    <th class="ui-widget-header ui-state-default">'.$langs->trans("Dur Eff. totale (h)").'</th><td class="ui-widget-content" colspan="1">'.$projet->sec2hour($task->duration_effective).'</td>';

            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Statut").'</th><td class="ui-widget-content" colspan="1">';
            print '        <SELECT id="statut"  name="statut">';
            if ($task->statut == 'open')
            {
                print '          <option SELECTED value="open">Ouvert</option>';
                print '          <option value="close">Ferm&eacute;</option>';
            } else {
                print '          <option value="open">Ouvert</option>';
                print '          <option SELECTED value="close">Ferm&eacute;</option>';
            }
            print '        </SELECT>';

            print '</td>';

            print '    <th class="ui-widget-header ui-state-default">'.$langs->trans("Progression").'</th><td class="ui-widget-content" colspan="1">';
            print '       <div id="slider" class="slider"></div>';
            print '         <input type="hidden" id="slidercomplet" name="slidercomplet" value="'.$task->progress.'">';
            print "<script>jQuery(document).ready(function(){ jQuery('#slider').slider('value',".$task->progress.") });</script>";
$task->user_creat->fetch($task->user_creat->id);
            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Description courte").'</th><td class="ui-widget-content" colspan="1"><input type="text" name="shortDesc" value="'.$task->shortDesc.'"></td>';
            print '    <th class="ui-widget-header ui-state-default">'.$langs->trans("Cr&eacute;ateur").'</th><td class="ui-widget-content" colspan="1">'.$task->user_creat->getNomUrl(1).'</td></tr>';

            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Note").'</th><td class="ui-widget-content" colspan="1"><textarea style="width:100%;" name="note">'.$task->note.'</textarea></td>';
            print '    <th class="ui-widget-header ui-state-default">'.$langs->trans("URL").'</th><td class="ui-widget-content" colspan="1"><input name="url" value="'.$task->url.'"></td></tr>';

            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Description").'</th><td class="ui-widget-content" colspan="3"><textarea name="description" style="width:100%;">'.$task->description.'</textarea></td>';


            if($projet->hasGantt == 1)
                print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Couleur").'</th><td class="ui-widget-content" colspan="3"><input name="color" value="'.$task->color.'"></td></tr>';

            print "</table>";
                print "<div class='tabsAction'>";
                print "    <button class='butAction'>Modifier</button>";
                print "    <button class='butAction' onClick='location.href=\"task.php?id=".$task->id."\"; return(false);'>Annuler</button>";
                print "</div>";
            print "</form>";



        } else {
            print '<table width="100%" cellpadding=15>';

            print '<tr><th width=20% class="ui-widget-header ui-state-default">'.$langs->trans("Ref").'</th><td width=30% class="ui-widget-content">'.$projet->ref.'</td>';
            print '    <th width=20% class="ui-widget-header ui-state-default">'.$langs->trans("Label").'</th><td width=30% class="ui-widget-content">'.$projet->getNomUrl(1).'</td></tr>';

            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Task").'</th><td class="ui-widget-content" colspan="1">'.$task->getNomUrl(1).'</td>';
            print '    <th class="ui-widget-header ui-state-default">'.$langs->trans("Company").'</th><td class="ui-widget-content">'.$projet->societe->getNomUrl(1).'</td></tr>';

            $parent = false;
            if (isset($res) && $res->fk_task_parent > 0)
            {
                $parent = new SynopsisProjectTask($db);
                $parent->fetch($res->fk_task_parent);
            }

            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Parent").'</th><td class="ui-widget-content" colspan="1">'.($parent?$parent->getNomUrl(1):"").'</td>';
            print '    <th class="ui-widget-header ui-state-default">'.$langs->trans("Type t&acirc;che").'</th><td class="ui-widget-content" colspan="1">'.$langs->trans($task->task_type).'</td></tr>';

            //print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Date d&eacute;but").'</th><td class="ui-widget-content" colspan="3">'.$task->dateoFRFull.'</td>';
            $requete ="SELECT * FROM ".MAIN_DB_PREFIX."projet_task_time WHERE fk_task = ".$task->id;
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Date d&eacute;but").'</th><td class="ui-widget-content" colspan="3">'.$res->task_date.'</td>';

            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Dur Pr&eacute;v totale").'</th><td class="ui-widget-content" colspan="1">'.$projet->sec2hour($task->duration).'</td>';
            print '    <th class="ui-widget-header ui-state-default">'.$langs->trans("Dur Eff. totale").'</th><td class="ui-widget-content" colspan="1">'.$projet->sec2hour($task->duration_effective).'</td>';

            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Statut").'</th><td class="ui-widget-content" colspan="1">'.$task->statut.'</td>';
            print '    <th class="ui-widget-header ui-state-default">'.$langs->trans("Progression").'</th><td class="ui-widget-content" colspan="1">';
            print '       <div style="float: left; background-color: #FFFFFF; margin-left: 50%; margin-top: -1px; padding: 2px; padding-left: 5px; padding-right: 5px; opacity: 0.95; " class="ui-corner-all">'.$task->progress.'%</div>';
            print '       <div id="progressBar"></div>';
            print '<script>Complet="'.($task->progress>0?$task->progress:0).'";</script>';

            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Description courte").'</th><td class="ui-widget-content" colspan="1">'.$task->shortDesc.'</td>';
            $task->user_creat->fetch($task->user_creat->id);
            print '    <th class="ui-widget-header ui-state-default">'.$langs->trans("Cr&eacute;ateur").'</th><td class="ui-widget-content" colspan="1">'.$task->user_creat->getNomUrl(1).'</td></tr>';

            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Note").'</th><td class="ui-widget-content" colspan="1">'.$task->note.'</td>';
            print '    <th class="ui-widget-header ui-state-default">'.$langs->trans("URL").'</th><td class="ui-widget-content" colspan="1">'.$task->url.'</td></tr>';

            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Description").'</th><td class="ui-widget-content" colspan="3">'.$task->description.'</td>';


            if($projet->hasGantt == 1)
                print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Couleur").'</th><td class="ui-widget-content" colspan="3"><div style="background-color: #'.$task->color.'; width:1em; height: 1em;"></td></tr>';



          /* Liste des taches */

            $sql = " SELECT t.task_date,
                            sum(t.task_duration) as task_duration,
                            t.fk_user
                       FROM ".MAIN_DB_PREFIX."projet_task_time as t, ".MAIN_DB_PREFIX."user as u
                      WHERE t.fk_task =".$task->id. " AND u.rowid = t.fk_user
                   GROUP BY t.task_date,  t.fk_user
                   ORDER BY u.firstname ASC ,t.task_date ASC";

            $sql1 = " SELECT t.task_date_effective,
                             sum(t.task_duration_effective) as task_duration_effective,
                             t.fk_user
                        FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective as t, ".MAIN_DB_PREFIX."user as u
                       WHERE t.fk_task =".$task->id. " AND u.rowid = t.fk_user
                    GROUP BY t.task_date_effective,  t.fk_user
                    ORDER BY u.firstname ASC , t.task_date_effective ASC";


            $var=true;
            $resql = $db->query($sql);
            if ($resql)
            {
                $num = $db->num_rows($resql);
                $tasks = array();
                while ($res = $db->fetch_object($resql))
                {
                    $tmpUser = false;
                    if ($res->fk_user > 0)
                    {
                        $tmpUser = new User($db);
                        $tmpUser->fetch($res->fk_user);
                    }
                    $tasks[$res->fk_user][date('Y-m-d',strtotime($res->task_date))] = array('date' => $res->task_date, 'dur' => $res->task_duration, 'user' => $tmpUser);
                    $i++;
                }
                $db->free($resql);
            } else {
                dol_print_error($db);
            }
            $resql = $db->query($sql1);
            if ($resql)
            {
                $num = $db->num_rows($resql);
                while ($res = $db->fetch_object($resql))
                {
                    $tmpUser = false;
                    if ($res->fk_user > 0)
                    {
                        $tmpUser = new User($db);
                        $tmpUser->fetch($res->fk_user);
                    }
                    if(is_array($tasks[$res->fk_user][date('Y-m-d',strtotime($res->task_date_effective))]))
                        $tasks[$res->fk_user][date('Y-m-d',strtotime($res->task_date_effective))] = array('dateEff' => $res->task_date_effective,
                                                                                 'durEff'  => $res->task_duration_effective,
                                                                                 'date'    => $tasks[$res->fk_user][date('Y-m-d',strtotime($res->task_date_effective))]['date'],
                                                                                 'dur'     => $tasks[$res->fk_user][date('Y-m-d',strtotime($res->task_date_effective))]['dur'],
                                                                                 'user'    => $tasks[$res->fk_user][date('Y-m-d',strtotime($res->task_date_effective))]['user']);
                    else
                        $tasks[$res->fk_user][date('Y-m-d',strtotime($res->task_date_effective))] = array('date'    => $res->task_date_effective,
                                                                                 'dateEff' => $res->task_date_effective,
                                                                                 'durEff'  => $res->task_duration_effective,
                                                                                 'dur'     => 0,
                                                                                 'user'    =>$tmpUser);
                }
                $db->free($resql);
            } else {
                dol_print_error($db);
            }
            print '</table></form><br />';

            if($droitModif){
                print "<div class='tabsAction'>";
                print "<button class='butAction' onClick='location.href=\"task.php?action=modify&id=".$_REQUEST['id']."\"'>Modifier</button>";
                print "</div>";
            }

            print "<div id='accordion' style='width:60%;'>";


            foreach ($tasks as $userid=>$tasks)
            {
                $remUser = false;
                $remUser2 = false;
                $tot = 0;
                $totEff = 0;
                $html = "";
                ksort($tasks);
                foreach($tasks as $date => $task_time)
                {
                    $html .= "<tr>";
                    $html .= '<td class="ui-widget-content" align=center>'.dol_print_date($db->jdate($task_time['date']),'day').'</td>';
                    $html .= '<td class="ui-widget-content" align=center>'.$projet->sec2hour($task_time["dur"]).'</td>';
                    $tot += $task_time["dur"];
                    $html .= '<td class="ui-widget-content" align=center>'.$projet->sec2hour($task_time["durEff"]).'</td>';
                    $totEff += $task_time["durEff"];
                    $task_time["user"]->fetch($task_time["user"]->id);
                    $remUser = ($task_time["user"]->getNomUrl(1)?$task_time["user"]->getNomUrl(1):false);
                    $remUser2 = ($task_time["user"]->fullname?$task_time["user"]->fullname:"");
                    $html .= "</tr>\n";
                }
                print '<h3><a href="#">'.$remUser2.'</a></h3>';
                print '<div>';
                print '<table class="" cellpadding=10 width="100%">';
                print "<tr><th colspan=4 class='ui-widget-header ui-state-hover'>".($remUser?$remUser:'NA');
                print '<tr class="liste_titre"  style="padding:7px">';
                print '<th style="padding:7px" class="ui-widget-header ui-state-default">'.$langs->trans("Date").'</td>';
                print '<th style="padding:7px" class="ui-widget-header ui-state-default">'.$langs->trans("Duration").' Pr&eacute;vue</td>';
                print '<th style="padding:7px" class="ui-widget-header ui-state-default">'.$langs->trans("DurationEffective").'</td>';
                print "</tr>\n";
                print $html;
                print "<tr><th class='ui-widget-header ui-state-default'>Total";
                print "    <td align=center class='ui-widget-content ui-priority-primary'>".$projet->sec2hour($tot);
                print "    <td align=center class='ui-widget-content ui-priority-primary'>".$projet->sec2hour($totEff);
                print "</table>";
                print '</div>';
            }
            print '</div>';
        }
    }
}

$db->close();

llxFooter('$Date: 2007/05/28 11:51:00 $ - $Revision: 1.6 $');
?>