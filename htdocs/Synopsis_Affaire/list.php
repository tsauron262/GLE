<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 12 aout 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : list.php
  * GLE-1.2
  */


require_once('pre.inc.php');

$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";
$js="";
//$js = ' <script src="'.$jspath.'/jquery-1.3.2.js" type="text/javascript"></script>';
//$js .= ' <script src="'.$jqueryuipath.'/jquery-ui.js" type="text/javascript"></script>';
//$js .= ' <script src="'.$jqueryuipath.'/ui.core.js" type="text/javascript"></script>';
//$js .= ' <script src="'.$jqueryuipath.'/ui.progressbar.js" type="text/javascript"></script>';
//$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/jquery-ui.css" />';
//$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/flick/jquery-ui-1.7.2.custom.css" />';

$js .= '      <link type="text/css" rel="stylesheet" href="'.DOL_URL_ROOT.'/Synopsis_Common/css/jquery.treeview.css" />';


$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
//$js .= ' <script src="'.$jspath.'/ajaxfileupload.js" type="text/javascript"></script>';
$js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
$js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';


$js .= "<style type='text/css'>body { position: static; }                 .ui-datepicker select.ui-datepicker-month, .ui-datepicker select.ui-datepicker-year  {width:48%;}
</style>
        <script type='text/javascript'>";


$js .= 'var gridimgpath = "'.$imgPath.'/images/";';

$jqgridJs .= <<<EOF

jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);

jQuery.jgrid.edit.msg.minValue="Ce champs est requis";
jQuery(document).ready(function() {

var grid = jQuery("#gridListAffaire").jqGrid({
            datatype: "json",
            url: "ajax/listAffaire_json.php",
            colNames:['id', "Ref",'Nom','Date cr&eacute;tion','Statut'],
            colModel:[  {name:'id',index:'id', width:0, hidden:true,key:true,hidedlg:true,search:false},
                        {
                            name:'Ref',
                            index:'ref',
                            align:"center",
                            width: 125,
                            editable: false,
                            searchoptions:{sopt:['eq','ne','bw','bn','in','ni','ew','en','cn','nc']},
                            formoptions:{ elmprefix:"*  " }
                        },{
                            name:'Nom',
                            index:'nom',
                            align:"center",
                            width: 250,
                            searchoptions:{sopt:['eq','ne','bw','bn','in','ni','ew','en','cn','nc']}
                        },
                        {
                            name:'date_creation',
                            index:'date_creation',
                            width:95,
                            align:"center",
                            sorttype:"date",
                            formatter:'date',
                            formatoptions:{srcformat:"Y-m-d",newformat:"d/m/Y"},
                            editable:false,formoptions:{ elmprefix:"   " },
                            searchoptions:{
                                dataInit:function(el){
                                    jQuery.datepicker.setDefaults($.datepicker.regional['fr']);
                                   jQuery(el).datepicker({
                                        dateFormat: 'dd/mm/yy',
                                        regional: 'fr',
                                        changeMonth: true,
                                        changeYear: true,
                                        showButtonPanel: true,
                                        constrainInput: true,
                                        gotoCurrent: true
                                    });
                                    jQuery("#ui-datepicker-div").addClass("promoteZ");
                                },
                                sopt:['eq','ne',"le",'lt',"ge","gt"],
                            },
                        },{
                            name:'Statut',
                            index:'statut',
                            searchable: false,
                            align:"left",
                            width: 50
                        },
                      ],
            rowNum:30,
            rowList:[30,50,100],
            imgpath: gridimgpath,
            pager: jQuery('#gridListAffairePager'),
            sortname: 'date_creation',
            beforeRequest: function(){
                jQuery('#gview_gridListAffaire').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
            },
            mtype: "POST",
            viewrecords: true,
            width: "900",
            height: 500,
            sortorder: "desc",
            //multiselect: true,
            caption: "<span style='padding:4px; font-size: 16px; '>Affaires</span>",
            viewsortcols: true,
EOF;

    $jqgridJs.= '    }).navGrid("#gridListAffairePager",'."\n";
    $jqgridJs.= '                   { view:false, add:false,'."\n";
    $jqgridJs.= '                     del:false,'."\n";
    $jqgridJs.= '                     edit:false,'."\n";
    $jqgridJs.= '                     search:true,'."\n";
    $jqgridJs.= '                     position:"left"'."\n";
    $jqgridJs.= '                     });'."\n";


$jqgridJs.= '   });'."\n";
$js .= $jqgridJs."</script>";

llxHeader($js,'Liste Affaires','',1);

print "<br/>";
print "<br/>";
print "<br/>";
print '<table id="gridListAffaire" class="scroll ui-widget " cellpadding="0" cellspacing="0"></table>';
print '<div id="gridListAffairePager" class="scroll" style="text-align:center;"></div>';


$db->close();

llxFooter();
?>
