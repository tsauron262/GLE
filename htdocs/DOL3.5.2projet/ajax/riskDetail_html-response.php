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
  * Name : riskDetail_html-response.php
  * GLE-1.1
  */



//TODO :> sum dans jqgrid

$project_id = $_REQUEST['project_id'];

require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');

print "<html><head>";
print "</head><body>";

//jggrid
    $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
    $jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
    $css = DOL_URL_ROOT."/Synopsis_Common/css";
    $imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

    $js = ' <script src="'.$jspath.'/jquery-1.3.2.js" type="text/javascript"></script>';
    $js .= '<script src="'.$jqueryuipath.'/jquery-ui.js" type="text/javascript"></script>';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/jquery-ui.css" />';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/flick/jquery-ui-1.7.2.custom.css" />';

    $js .= '<link type="text/css" rel="stylesheet" href="'.DOL_URL_ROOT.'/Synopsis_Common/css/jquery.treeview.css" />';
    $js .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
    $js .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu(); });  </script>\n";


    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';



    $js .= "<style type='text/css'>body { position: static; }</style>";
    $js .= "<style type='text/css'>.FormElement { max-width: 90%; }</style>";

    $jqgridJs ="<script type='text/javascript'>";
    $jqgridJs .= 'var gridimgpath="'.$imgPath.'/images/";';
    $jqgridJs .= 'var userId="'.$user->id.'";';
    $jqgridJs .= 'var socId="'.$soc->id.'"; ';
    $jqgridJs .= 'var campId="'.$campagneId.'"; ';
    $jqgridJs .= 'var DOL_URL_ROOT="'.DOL_URL_ROOT.'"; ';
    $jqgridJs .= "var projId='".$project_id."'\n";

$jqgridJs .= <<<EOF
    var grid;
    var remParent = 0;
    var remCatName = "Ressources";


        var get = "&projId="+projId;
        grid = $("#gridListRessources1InProj").jqGrid({
                datatype: "json",
                url: "ajax/risquesGlobaux_json.php?userId="+userId+get,
                colNames:['id',"Nom", "Description",'Occurence','Gravit&eacute;','Co&ucirc;t global','Co&ucirc;t du risque'],
                colModel:[  {
                                name:'rowid',
                                index:'rowid',
                                width:0,
                                hidden:true,
                                key:true,
                                hidedlg:true,
                                search:false
                            },
                            {
                                name:'nom',
                                index:'nom',
                                width:80,
                                align:"center",
                                editable:true,
                                searchoptions:{ sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"] },
                                editrules:{required:true},
                                formoptions:{ elmprefix:"*  " },
                            },
                            {
                                name:'description',
                                index:'description',
                                align:"center",
                                edittype: 'textarea',
                                editable: true,
                                formoptions:{ elmprefix:"   " },
                                searchoptions:{ sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"] },
                            },
                            {
                                name:'occurence',
                                index:'occurence',
                                width:180,
                                align:"center",
                                editable:true,
                                searchoptions:{ sopt:['eq','ne',"lt",'gt',"le","gt","in","ni","ew","en",'cn',"nc"] },
                                editrules:{required:true,minValue:0,maxValue:100,integer:true},
                                formoptions:{ elmprefix:"*  " },
                            },
                            {
                                name:'gravite',
                                index:'gravite',
                                width:180,
                                align:"center",
                                editable:true,
                                searchoptions:{ sopt:['eq','ne',"lt",'gt',"le","gt","in","ni","ew","en",'cn',"nc"] },
                                editrules:{required:true,minValue:0,maxValue:100,integer:true},
                                formoptions:{ elmprefix:"*  " },
                            },
                            {
                                name:'cout',
                                index:'cout',
                                width:180,
                                align:"center",
                                editable:true,
                                editrules:{required:true,minValue:0,number:true},
                                formoptions:{ elmprefix:"*  " },
                                formatter: "currency",
                                searchoptions:{ sopt:['eq','ne',"lt",'gt',"le","gt","in","ni","ew","en",'cn',"nc"] },
                            },
                            {
                                name:'coutRisk',
                                index:'coutRisk',
                                width:180,
                                align:"center",
                                editable:false,
                                formatter: "currency",
                                searchoptions:{ sopt:['eq','ne',"lt",'gt',"le","gt","in","ni","ew","en",'cn',"nc"] },
                            },
                          ],
                rowNum:10,
                rowList:[10,20,30],
                imgpath: gridimgpath,
                pager: jQuery('#gridListRessources1InProjPager'),
                sortname: 'rowid',
                editurl: "ajax/riskDetailEdit_ajax.php?usrId="+userId+get,
                mtype: "POST",
                viewrecords: true,
            autowidth: true,
                height: 500,
                sortorder: "desc",
                //multiselect: true,
                caption: "<span style='padding:4px; font-size: 13px; '>Risques globaux</span>",
                viewsortcols: true,
                loadComplete: function(){
                //    alert ("loadComplete");
                },
EOF;
if (true) //TODO : droit de modifier les risque du projet ou project admin
{

        $jqgridJs.= '    }).navGrid("#gridListRessources1InProjPager",'."\n";
        $jqgridJs.= '                   { add:true,'."\n";
        $jqgridJs.= '                     del:true,'."\n";
        $jqgridJs.= '                     edit:true,'."\n";
        $jqgridJs.= '                     search:true,'."\n";
        $jqgridJs.= '                     position:"left"'."\n";
        $jqgridJs.= '                     });'."\n";
} else {
        $jqgridJs.= '    }).navGrid("#gridListRessources1InProjPager",'."\n";
        $jqgridJs.= '                   { add:false,'."\n";
        $jqgridJs.= '                     del:false,'."\n";
        $jqgridJs.= '                     edit:false,'."\n";
        $jqgridJs.= '                     search:false,'."\n";
        $jqgridJs.= '                     position:"left"'."\n";
        $jqgridJs.= '                     });'."\n";
}

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
