<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Bariley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
  
  */
/*
 */

/**
        \file       htdocs/projet/liste.php
        \ingroup    projet
        \brief      Page liste des projets
        \version    $Id: liste.php,v 1.16 2008/03/02 22:20:46 eldy Exp $
*/

require("./pre.inc.php");

$langs->load("project@projet");    
if (!$user->rights->synopsisprojet->lire) accessforbidden();

$socid = ( isset($_REQUEST["socid"]) && is_numeric($_REQUEST["socid"]) ? $_REQUEST["socid"] : 0 );

$title = $langs->trans("Projects");

// Securite acces client
if ($user->societe_id > 0) $socid = $user->societe_id;

if ($socid > 0)
{
  $soc = new Societe($db);
  $soc->fetch($socid);
  $title .= ' (<a href="liste.php">'.$soc->nom.'</a>)';
}

//



/**
 * Affichage de la liste des projets
 *
 */


$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

$js  = '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/jqGrid-3.5/src/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-3.5/jquery.jqGrid.min.js" type="text/javascript"></script>';
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
jQuery(document).ready(function(){
    var get = "";
    if (socId > 0)
    {
        get = "&socid="+socId;
    }
    jQuery("#gridListProj").jqGrid({
            datatype: "json",
            url: "tasks/ajax/listProj_json.php?userId="+userId+get,
            colNames:['id', 'D&eacute;signation','Ref','Date de d&eacute;but', 'Statut', 'Avancement','Client','Taille &eacute;quipe',"Responsable"],
            colModel:[  {name:'id',index:'id', width:5, hidden:true,key:true,hidedlg:true,search:false},
                        {name:'title',index:'title', width:300},
                        {name:'ref',index:'ref', width:30},
                        {name:'dateo',index:'dateo', width:60, align:"center",search:false},
                        {name:'statut',index:'statut', width:60, align:"right",stype:'select', searchoptions:"{value: statutRess}"},
                        {name:'avanc',index:'avanc', width:60, align:"center",search:false},
                        {name:'socname',index:'socname', width:150, align:"center"},
                        {name:'cntMyTask',index:'cntMyTask', width:50, align:"center",search:false},
                        {name:'fk_user_resp',index:'fk_user_resp', width:90, align:"center",search:false},
                      ],
            rowNum:30,
            rowList:[30,50,100],
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
                    jQuery(".progressbar").each(function(){
                       var val = $(this).text();
                        jQuery(this).text('');
                        jQuery(this).progressbar( {
                           value: parseInt(val),
                           orientation: "horizontal",
                       });
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
                 jQuery("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table><div id='"+pager_id+"' class='scroll'></div>");
                 jQuery("#"+subgrid_table_id).jqGrid({
                        url:"tasks/ajax/listAllTask_json.php?userId="+userId+"&projId="+row_id,
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

        jQuery.datepicker.setDefaults(jQuery.extend({showMonthAfterYear: false}, jQuery.datepicker.regional['fr']));
        var arr = new Array("adddatedeb", "adddatefin", "Moddatedeb", "Moddatefin");
        for (i in arr)
        {
            jQuery("#"+arr[i]).datepicker({dateFormat: 'dd/mm/yy',
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'cal.png',
                    buttonImageOnly: true,
                    showTime: true,
                    duration: '',
                    constrainInput: false,
            });

        }
        jQuery("#ui-datepicker-div").addClass("promoteZ");
        jQuery("#ui-timepicker-div").addClass("promoteZ"); setTimeout(function(){   jQuery("#gridListProj").filterToolbar('');},500);

});

</script>
EOF;

$js .= $jqgridJs;
llxHeader($js,$langs->trans("Projects"));




//$staticsoc=new Societe($db);
//
//$sql = "SELECT p.rowid as projectid, p.ref, p.title, p.dateo as do";
//$sql .= ", s.nom, s.rowid as socid, s.client";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
//$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
//$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = p.fk_soc";
//$sql .= " WHERE 1 = 1 ";
//if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
//if ($socid)
//{
//  $sql .= " AND s.rowid = ".$socid;
//}
//if ($_GET["search_ref"])
//{
//  $sql .= " AND p.ref LIKE '%".addslashes($_GET["search_ref"])."%'";
//}
//if ($_GET["search_label"])
//{
//  $sql .= " AND p.title LIKE '%".addslashes($_GET["search_label"])."%'";
//}
//if ($_GET["search_societe"])
//{
//  $sql .= " AND s.nom LIKE '%".addslashes($_GET["search_societe"])."%'";
//}
//$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);
//
//$var=true;
//$resql = $db->query($sql);
//if ($resql)
//{
//  $num = $db->num_rows($resql);
//  $i = 0;

//print_barre_liste($langs->trans("Projects"), $page, "liste.php", "", $sortfield, $sortorder, "", $num);

print '<table id="gridListProj" class="scroll" cellpadding="0" cellspacing="0"></table>';
print '<div id="gridListProjPager" class="scroll" style="text-align:center;"></div>';

//  print '<table class="noborder" width="100%">';
//  print '<tr class="liste_titre">';
//  print_liste_field_titre($langs->trans("Ref"),"liste.php","p.ref","","","",$sortfield,$sortorder);
//  print_liste_field_titre($langs->trans("Label"),"liste.php","p.title","","","",$sortfield,$sortorder);
//  print_liste_field_titre($langs->trans("Company"),"liste.php","s.nom","","","",$sortfield,$sortorder);
//  print '<td>&nbsp;</td>';
//  print "</tr>\n";
//
//  print '<form method="get" action="liste.php">';
//  print '<tr class="liste_titre">';
//  print '<td valign="right">';
//  print '<input type="text" class="flat" name="search_ref" value="'.$_GET["search_ref"].'">';
//  print '</td>';
//  print '<td valign="right">';
//  print '<input type="text" class="flat" name="search_label" value="'.$_GET["search_label"].'">';
//  print '</td>';
//  print '<td valign="right">';
//  print '<input type="text" class="flat" name="search_societe" value="'.$_GET["search_societe"].'">';
//  print '</td>';
//  print '<td class="liste_titre" align="center"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
//  print "</td>";
//  print "</tr>\n";
//
//  while ($i < $num)
//    {
//      $objp = $db->fetch_object($resql);
//      $var=!$var;
//      print "<tr $bc[$var]>";
//      print "<td><a href=\"fiche.php?id=$objp->projectid\">".img_object($langs->trans("ShowProject"),"project")." ".$objp->ref."</a></td>\n";
//      print "<td><a href=\"fiche.php?id=$objp->projectid\">".$objp->title."</a></td>\n";
//
//      // Company
//      print '<td>';
//      if ($objp->socid)
//      {
//          $staticsoc->id=$objp->socid;
//          $staticsoc->nom=$objp->nom;
//          print $staticsoc->getNomUrl(1);
//         }
//         else
//         {
//         print '&nbsp;';
//        }
//    print '</td>';
//
//      print '<td>&nbsp;</td>';
//      print "</tr>\n";
//
//      $i++;
//    }
//
//  $db->free($resql);
//}
//else
//{
//  dol_print_error($db);
//}
//
//print "</table>";

$db->close();


llxFooter('$Date: 2008/03/02 22:20:46 $ - $Revision: 1.16 $');

?>