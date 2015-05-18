<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 10 aout 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : countressource_html-repsonse.php
  * GLE-1.1
  */


//prix de chaque ressource + date de reservation (global + subgrid (si plusieurs reservation pour 1 ressource))
//prix total a la fin
//TODO changer json
//TODO changer presentation
//TODO ajouter footer detail (total)
//TODO ajouter subgrid

require_once('../../main.inc.php');
  //require_once(DOL_DOCUMENT_ROOT."/core/lib/ressource.lib.php");


$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("synopsisGene@synopsistools");

$projId = $_REQUEST['projet_id'];

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);
// Initialisation de l'objet Societe
//
//if (! ($user->rights->SynopsisRessources->SynopsisRessources->Utilisateur || $user->rights->SynopsisRessources->SynopsisRessources->Admin || $user->rights->SynopsisRessources->SynopsisRessources->Resa))
//{
//    accessforbidden();
//}
$soc = new Societe($db);
$soc->fetch($socid);
print '<html><head></head><body>';


    $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
    $jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
    $css = DOL_URL_ROOT."/Synopsis_Common/css";
    $imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

    $js = ' <script src="'.$jspath.'/jquery-1.3.2.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jqueryuipath.'/jquery-ui.js" type="text/javascript"></script>';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/jquery-ui.css" />';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/flick/jquery-ui-1.7.2.custom.css" />';

    $js .= '      <link type="text/css" rel="stylesheet" href="'.DOL_URL_ROOT.'/Synopsis_Common/css/jquery.treeview.css" />';

$js .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
$js .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu(); });  </script>\n";

    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/jqGrid-3.5/src/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-3.5/jquery.jqGrid.min.js" type="text/javascript"></script>';



    $js .= "<style type='text/css'>body { position: static; }</style>";

    $jqgridJs ="<script type='text/javascript'>";
    $jqgridJs .= 'var gridimgpath="'.$imgPath.'/images/";';
    $jqgridJs .= 'var userId="'.$user->id.'";';
    $jqgridJs .= 'var socId="'.$soc->id.'"; ';
    $jqgridJs .= 'var DOL_URL_ROOT="'.DOL_URL_ROOT.'"; ';
    $jqgridJs .= "var projId='".$projId."'\n";



$jqgridJs .= <<<EOF
    var grid;
    var remParent = 0;
    var remCatName = "Ressources";


        var get = "&projId="+projId;
        grid = $("#gridListRessources1InProj").jqGrid({
                datatype: "json",
                url: "ajax/ressourceInProj_json.php?userId="+userId+get,
                colNames:['id',"D&eacute;signation", "Cat&eacute;gorie",'Description','Dur&eacute;e','Co&ucirc;t','Photo'],
                colModel:[  {name:'rowid',index:'rowid', width:0, hidden:true,key:true,hidedlg:true,search:false},
                            {name:'nom',index:'nom', width:80, align:"center",editable:false,searchoptions:{
                                sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]}
                            },
                            {
                                name:'categorie',
                                index:'categorie',
                                display:"none",
                                align:"center",
                                editable:false,
                                hidden:true,
                                hidedlg:false,
                                edithidden: false,
                                search:false,
                                stype: 'select',
                                edittype: 'select',
                                editable: false,
                                searchoptions:{sopt:['eq','ne']},

                            },
                            {name:'description',index:'description', width:180, align:"center",editable:false,edittype:"textarea",
                                searchoptions:{sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]}
                            },
                            {name:'dur',index:'dur', width:180, align:"center",editable:false,edittype:"textarea",
                                searchoptions:{sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]}
                            },
                            {name:'costdur',index:'costdur', width:180, align:"center",editable:false,edittype:"textarea", formatter: "currency",
                                searchoptions:{sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]}
                            },
                            {name:'photo',index:'photo', width:200, align:"center", edittype:'file',editable:true, search: false,sortable: false,formoptions:{ elmprefix:"   " },},

                          ],
                rowNum:10,
                rowList:[10,20,30],
                imgpath: gridimgpath,
                pager: jQuery('#gridListRessources1InProjPager'),
                sortname: 'rowid',
                mtype: "POST",
                viewrecords: true,
            autowidth: true,
                height: 500,
                sortorder: "desc",
                //multiselect: true,
                caption: "<span style='padding:4px; font-size: 13px; '>Ressources</span>",
                viewsortcols: true,
                subGrid: true,
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
                    pager_id = "p_"+subgrid_table_id;
                    $("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table><div id='"+pager_id+"' class='scroll'></div>");
                    jQuery("#"+subgrid_table_id).jqGrid({
                        url:"ajax/ressource_subgrid-json.php?id="+row_id+"&userId="+userId+get,
                        datatype: "json",
                        colNames: ['id','Imputer &agrave;','T&acirc;che','D&eacute;but','Fin','Dur&eacute;e','Co&ucirc;t'],
                        colModel: [
                            {name:"id",index:"id",width:80,hidden:true,key:true,hidedlg:true,search:false},
                            {name:"fk_user_imputation",index:"fk_user_imputation",width:130},
                            {name:"fk_projet_task",index:"fk_projet_task",width:130},
                            {
                                name:"datedeb",
                                index:"datedeb",
                                width:130,
                                align:"center",
                                sorttype:"date",
                                formatter:'date',
                                formatoptions:{srcformat:"Y-m-d H:i",newformat:"d/m/Y H:i"},
                                editable:true,formoptions:{ elmprefix:"   " },
                                searchoptions:{
                                    dataInit:function(el){
                                        $(el).datepicker();
                                        $("#ui-datepicker-div").addClass("promoteZ");
                                        //$("#ui-timepicker-div").addClass("promoteZ");
                                    },
                                    sopt:['eq','ne',"le",'lt',"ge","gt"],
                                },
                            },
                            {
                                name:"datefin",
                                index:"datefin",
                                width:130,
                                align:"center",
                                sorttype:"date",
                                formatter:'date',
                                formatoptions:{srcformat:"Y-m-d H:i",newformat:"d/m/Y H:i"},
                                editable:true,formoptions:{ elmprefix:"   " },
                                searchoptions:{
                                    dataInit:function(el){
                                        $(el).datepicker();
                                        $("#ui-datepicker-div").addClass("promoteZ");
                                        //$("#ui-timepicker-div").addClass("promoteZ");
                                    },
                                    sopt:['eq','ne',"le",'lt',"ge","gt"],
                                },
                            },
                            {name:"Dur",index:"Dur",width:130},//remove sortable
                            {name:"coutDur",index:"coutDur",width:130},//remove sortable
                        ],
                        rowNum:20,
                        pager: pager_id,
                        imgpath: gridimgpath,
                        sortname: 'id',
                        sortorder: "asc",
                        height: '100%'
                    }).navGrid("#"+pager_id,{edit:false,add:false,del:false})
                },
                subGridRowColapsed: function(subgrid_id, row_id) {
                    // this function is called before removing the data
                    //var subgrid_table_id;
                    //subgrid_table_id = subgrid_id+"_t";
                    //jQuery("#"+subgrid_table_id).remove();
                },
                loadComplete: function(){
                //    alert ("loadComplete");
                },
EOF;
        $jqgridJs.= '    }).navGrid("#gridListRessources1InProjPager",'."\n";
        $jqgridJs.= '                   { add:false,'."\n";
        $jqgridJs.= '                     del:false,'."\n";
        $jqgridJs.= '                     edit:false,'."\n";
        $jqgridJs.= '                     search:true,'."\n";
        $jqgridJs.= '                     position:"left"'."\n";
        $jqgridJs.= '                     });'."\n";


    //
    $jqgridJs.= '</script>'."\n";


    $js .= $jqgridJs;
    require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Ressources/ressource.class.php');
    $ress = new Ressource($db);
//    llxHeader($js,$langs->trans("Ressources"),"Ressources","1");
    top_menu($js,$langs->trans("Ressources"), "",1,false);
    //Affiche un jqgrid de suivie


    print '<div class="tabBar">';

    print '<table id="gridListRessources1InProj" class="scroll" cellpadding="0" cellspacing="0"></table>';
    print '<div id="gridListRessources1InProjPager" class="scroll" style="text-align:center;"></div>';
    print "</div>";


print "</body></html>";

?>