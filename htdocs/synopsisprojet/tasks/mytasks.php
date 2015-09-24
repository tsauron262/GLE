<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * $Id: mytasks.php,v 1.7 2007/05/04 23:34:24 eldy Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/synopsisprojet/tasks/mytasks.php,v $
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
        \file       htdocs/synopsisprojet/tasks/mytasks.php
        \ingroup    projet
        \brief      Fiche taches d'un projet
        \version    $Revision: 1.7 $
*/

require("./pre.inc.php");

if (!$user->rights->synopsisprojet->lire) accessforbidden();

$langs->load('projects');

function PLines(&$inc, $parent, $lines, &$level, &$var)
{
    global $db;
    //TODO :> refaire avec iplication Gantt (prévu / effectué), projet, referant, role ...
  $form = new Form($db); // $db est null ici mais inutile pour la fonction select_date()
  global $bc, $langs;
  for ($i = 0 ; $i < sizeof($lines) ; $i++)
    {
      if ($parent == 0)
    {
    $level = 0;
    $var = !$var;
    }

      if ($lines[$i][1] == $parent)
    {
    print "<tr $bc[$var]>\n<td>";
    print '<a href="card.php?id='.$lines[$i][5].'">'.$lines[$i][4]."</a></td><td>\n";

    for ($k = 0 ; $k < $level ; $k++)
        {
        print "&nbsp;&nbsp;&nbsp;";
        }

    print '<a href="task.php?id='.$lines[$i][2].'">'.$lines[$i][0]."</a></td>\n";

    $heure = intval($lines[$i][3]);
    $minutes = (($lines[$i][3] - $heure) * 60);
    $minutes = substr("00"."$minutes", -2);

    print '<td align="right">'.$heure."&nbsp;h&nbsp;".$minutes."</td>\n";
    print "</tr>\n";
    $inc++;
    $level++;
    PLines($inc, $lines[$i][2], $lines, $level, $var);
    $level--;
    }
      else
    {
    //$level--;
    }
    }
}



$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

//$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/jquery-ui.css" />';
$js  = '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';
$js .= "<style type='text/css'>body { position: static; }</style>";
$js .= "<style type='text/css'>.ui-progressbar{ height: 16px; background-color: #ffffff; margin: 0px; float: left; width: 100%;}</style>";
$js .= "<style type='text/css'>.ui-progressbar-value{ border:1px solid #000000;float: left; width: 100%; }</style>";


$jqgridJs = <<<EOF
<script type='text/javascript'>
EOF;
$jqgridJs .= 'var gridimgpath="'.$imgPath.'/images/";';
$jqgridJs .= 'var userId="'.$user->id.'";';
$jqgridJs .= <<<EOF
jQuery(document).ready(function(){
    $("#gridListProj").jqGrid({
            datatype: "json",
            url: "ajax/listProj_json.php?userId="+userId,
            colNames:['id', 'D&eacute;signation','Date de d&eacute;but', 'Avancement','Client','Mes t&acirc;ches','Responsable'],
            colModel:[  {name:'id',index:'id', width:55, hidden:true,key:true,hidedlg:true,search:false},
                        {name:'nom',index:'nom', width:90, align:"center"},
                        {name:'dateo',index:'dateo', width:90, datefmt: "dd/mm/yyyy",sorttype: "date", align:"center"},
                        {name:'statut',index:'statut', width:130, align:"center"},
                        {name:'socname',index:'socname', width:80, align:"center"},
                        {name:'cntMyTask',index:'cntMyTask', width:80, align:"center"},
                        {name:'fk_user_resp',index:'fk_user_resp', width:180, align:"center"},
                      ],
            rowNum:10,
            rowList:[10,20,30],
            imgpath: gridimgpath,
            pager: jQuery('#gridListProjPager'),
            sortname: 'id',
            autowidth: true,
            height: 500,
            mtype: "POST",
            viewrecords: true,
            loadComplete: function(){
                $(".progressbar").each(function(){
                    var val = $(this).text();
                    $(this).text('');
                    $(this).progressbar( {
                        value: val,
                        orientation: "horizontal",
                    }
                    );
                    $(this).css('height',"10px");
                    //$(this).find('.ui-progressbar-value').css('height','8px');
                });
            },
            sortorder: "desc",
            //multiselect: true,
            caption: "Projets dans lesquel je suis impliqu&eacute;",
            subGrid : true,
            subGridUrl: 'ajax/listTask_json.php?userId="+userId',
            subGridRowExpanded: function(subgrid_id, row_id) {
                 // we pass two parameters
                 // subgrid_id is a id of the div tag created whitin a table data
                 // the id of this elemenet is a combination of the "sg_" + id of the row
                 // the row_id is the id of the row
                 // If we wan to pass additinal parameters to the url we can use
                 // a method getRowData(row_id) - which returns associative array in type name-value
                 // here we can easy construct the flowing
                 var subgrid_table_id, pager_id;
                 subgrid_table_id = subgrid_id+"_t";
                 mtype: "POST",
                 pager_id = "p_"+subgrid_table_id;
                 $("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table><div id='"+pager_id+"' class='scroll'></div>");
                 $("#"+subgrid_table_id).jqGrid({
                        url:"ajax/listTask_json.php?userId="+userId+"&projId="+row_id,
                        datatype: "json",
                        colNames: ['id', 'D&eacute;signation','R&ocirc;le','Date de d&eacute;but pr&eacute;vu', 'Dur&eacute;e pr&eacute;vue','Temps Pass&eacute;'],
                        colModel: [ {name:'id',index:'id', width:5, hidden:true,key:true,hidedlg:true,search:false},
                                    {name:'label',index:'label', width:100, align: "center"},
                                    {name:'role',index:'role', width:80, align:"center"},
                                    {name:'task_date',index:'task_date', width:100, datefmt: "dd/mm/yyyy",sorttype: "date", align:"center"},
                                    {name:'task_duration',index:'task_duration', width:75, align:"center"},
                                    {name:'task_duration_effective',index:'task_duration_effective', width:75, align:"center"},
                                  ],
                        rowNum:20,
                        pager: pager_id,
                        imgpath: gridimgpath,
                        sortname: 'task_date',
                        sortorder: "asc",
                        height: '100%',
                        width: 799,
                    }).navGrid("#"+pager_id,{edit:false,add:false,del:false}) },
        }).navGrid('#gridListProjPager',
                   { add:false,
                     del:false,
                     edit:false,
                     position:"left"
        });

});

</script>
EOF;

$js .= $jqgridJs;

llxHeader($js,$langs->trans("Mytasks"),"Projet","");

/*
 * Fiche projet en mode visu
 *
 */

//Affiche par projet l'ensemble des tâches avec duree  prévue, effective ....
//jquery + jqgrid
//Si click => detail projet ou detail task

$h=0;
$head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/tasks/mytasks.php';
$head[$h][1] = $langs->trans("Mytasks");
$head[$h][2] = "Mytasks";
$h++;
$head[$h][0] = DOL_URL_ROOT.'/synopsisprojet/tasks/myprojects.php';
$head[$h][1] = $langs->trans("Mes Projets");
$head[$h][2] = "MyProjects";
$h++;
$hselected = "Mytasks";

dol_fiche_head($head,  $hselected, $langs->trans("Mytasks"));

/* Liste des taches */

            print '<table id="gridListProj" class="scroll" cellpadding="0" cellspacing="0"></table>';
            print '<div id="gridListProjPager" class="scroll" style="text-align:center;"></div>';



//$sql = "SELECT t.rowid, t.label,
//               t.fk_task_parent,
//               t.duration_effective,
//               tt.task_duration as duration_prevu";
//$sql .= " ,    p.rowid as prowid,
//               p.label as plabel";
//$sql .= " FROM ".MAIN_DB_PREFIX."projet_task as t";
//$sql .= " ,    ".MAIN_DB_PREFIX."Synopsis_projet_task_actors as a";
//$sql .= " ,    ".MAIN_DB_PREFIX."projet_task_time as tt";
//$sql .= " ,    ".MAIN_DB_PREFIX."Synopsis_projet_view as p";
//$sql .= " WHERE p.rowid = t.fk_projet";
//$sql .= "  AND a.fk_projet_task = t.rowid";
//$sql .= "  AND tt.fk_task = t.rowid";
//$sql .= "  AND t.priority <> 3";
//$sql .= "  AND a.fk_user = ".$user->id;
//$sql .= " ORDER BY p.rowid, t.fk_task_parent";
//
//$resql = $db->query($sql);
//if ($resql)
//{
//  $num = $db->num_rows($resql);
//  $i = 0;
//  $tasks = array();
//  while ($i < $num)
//    {
//      $obj = $db->fetch_object($resql);
//      $tasks[$i][0] = $obj->label;
//      $tasks[$i][1] = $obj->fk_task_parent;
//      $tasks[$i][2] = $obj->rowid;
//      $tasks[$i][3] = $obj->duration_effective / 3600;
//      $tasks[$i][4] = $obj->plabel;
//      $tasks[$i][5] = $obj->prowid;
//      $i++;
//    }
//  $db->free();
//}
//else
//{
//  dol_print_error($db);
//}
//
//print '<table class="noborder" width="100%">';
//print '<tr class="liste_titre">';
//print '<td>'.$langs->trans("Project").'</td>';
//print '<td>'.$langs->trans("Task").'</td>';
//print '<td align="right">'.$langs->trans("DurationEffective").'</td>';
//print "</tr>\n";
//$var=true;
//
//PLines($j, 0, $tasks, $level, $var);
//
//print "</table>";
//print '</div>';

$db->close();

llxFooter('$Date: 2007/05/04 23:34:24 $ - $Revision: 1.7 $');
?>
