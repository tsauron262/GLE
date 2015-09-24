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
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/projet/tasks/mytasks.php,v $
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
        \file       htdocs/projet/tasks/mytasks.php
        \ingroup    projet
        \brief      Fiche taches d'un projet
        \version    $Revision: 1.7 $
*/

require("./pre.inc.php");

if (!$user->rights->synopsisprojet->lire) accessforbidden();

$langs->load('projects');



$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

$js  = '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';
$js .= "<style type='text/css'>body { position: static; }</style>";
$js .= "<style type='text/css'>.ui-progressbar{ height: 13px; background-color: #ffffff; margin: 0px; float: left; width: 100%;}</style>";
$js .= "<style type='text/css'>.ui-progressbar-value{ border:1px solid #000000;float: left; width: 100%; }</style>";


$jqgridJs = <<<EOF
<script type='text/javascript'>
EOF;
$jqgridJs .= 'var gridimgpath="'.$imgPath.'/images/";';
$jqgridJs .= 'var userId="'.$user->id.'";';
$jqgridJs .= 'var socId="'.$socid.'";';

$jqgridJs .= <<<EOF
$(document).ready(function(){
    var get = "&extra=viewmine";
    if (socId > 0)
    {
        get = "&socid="+socId;
    }

    $("#gridListProj").jqGrid({
            datatype: "json",
            url: "ajax/listProj_json.php?userId="+userId+get,
            colNames:['id', 'D&eacute;signation','Date de d&eacute;but', 'Statut','Client','Taille &eacute;quipe',"Responsable"],
            colModel:[  {name:'id',index:'id', width:55, hidden:true,key:true,hidedlg:true},
                        {name:'title',index:'title', width:90},
                        {name:'dateo',index:'dateo', width:90, },
                        {name:'statut',index:'statut', width:80, align:"right"},
                        {name:'socname',index:'socname', width:80, align:"center"},
                        {name:'cntMyTask',index:'cntMyTask', width:80, align:"center"},
                        {name:'fk_user_resp',index:'fk_user_resp', width:80, align:"center"},
                      ],
            rowNum:10,
            rowList:[10,20,30],
            imgpath: gridimgpath,
            pager: jQuery('#gridListProjPager'),
            sortname: 'id',
            mtype: "POST",
            viewrecords: true,
            autowidth: true,
            height: 500,
            sortorder: "desc",
            //multiselect: true,
            caption: "Projets",
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

            subGrid : true,
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
                        url:"ajax/listAllTask_json.php?userId="+userId+"&projId="+row_id,
                        datatype: "json",
                        colNames: ['id','Acteur','R&ocirc;le', 'D&eacute;signation','Statut','Avancement','Date de d&eacute;but', 'Dur&eacute;e'],
                        colModel: [ {name:'id',index:'id', width:55, hidden:true,key:true,hidedlg:true,search:false},
                                    {name:'acto',index:'acto', width:80, align:"center"},
                                    {name:'role',index:'role', width:80, align:"center"},
                                    {name:'title',index:'title', width:90},
                                    {name:'statut',index:'statut', width:80, align:"center",editoptions:{value:"0:Selection;1:Brouillon;2:Valider;3:Cloturer"}},
                                    {name:'progress',index:'progress', width:80, align:"center"},
                                    {name:'task_date',index:'task_date', width:90, datefmt: "dd/mm/yyyy",sorttype: "date", align:"center"},
                                    {name:'task_duration',index:'task_duration', width:80, align:"center"},
                                  ],
                        rowNum:20,
                        width: "850",
                        pager: pager_id,
                        imgpath: gridimgpath,
                        sortname: 'id',
                        sortorder: "asc",
                        height: '100%',
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
$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/mytasks.php';
$head[$h][1] = $langs->trans("Mytasks");
$head[$h][2] = "Mytasks";
$h++;
$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/myprojects.php';
$head[$h][1] = $langs->trans("Mes Projets");
$head[$h][2] = "MyProjects";
$h++;
$hselected = "MyProjects";

dol_fiche_head($head,  $hselected, $langs->trans("Mytasks"));

/* Liste des taches */

            print '<table id="gridListProj" class="scroll" cellpadding="0" cellspacing="0"></table>';
            print '<div id="gridListProjPager" class="scroll" style="text-align:center;"></div>';




$db->close();

llxFooter('$Date: 2007/05/04 23:34:24 $ - $Revision: 1.7 $');
?>
