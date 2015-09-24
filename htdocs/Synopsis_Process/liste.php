<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 23 fevr. 2011
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : liste.php
 * GLE-1.2
 */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");

$langs->load("process@Synopsis_Process");

// Security check
$socid = isset($_GET["socid"]) ? $_GET["socid"] : '';
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '');


$arrModel = array();

$jspath = DOL_URL_ROOT . "/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT . "/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT . "/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT . "/Synopsis_Common/images";
$js = "";

$js .= '      <link type="text/css" rel="stylesheet" href="' . DOL_URL_ROOT . '/Synopsis_Common/css/jquery.treeview.css" />';


$js .= '<link rel="stylesheet" type="text/css" media="screen" href="' . $jspath . '/jqGrid-3.5/css/ui.jqgrid.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="' . $jspath . '/jqGrid-3.5/css/jquery.searchFilter.css" />';
//$js .= ' <script src="'.$jspath.'/ajaxfileupload.js" type="text/javascript"></script>';

$js .= ' <script src="' . $jspath . '/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
$js .= ' <script src="' . $jspath . '/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';
$js .= '<script language="javascript"  src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jqGrid-3.5/js/grid.custom.js"></script>' . "\n";


$js .= "<style type='text/css'>body { position: static; }                 .ui-datepicker select.ui-datepicker-month, .ui-datepicker select.ui-datepicker-year  {width:48%;}
.ui-pg-selbox { min-width:50px; }
</style>
        <script type='text/javascript'>";
$requete = "SELECT id, label FROM " . MAIN_DB_PREFIX . "Synopsis_Process ORDER BY label ASC";
$sql = $db->query($requete);
$js .= 'var typeRess = "';
$js .= "-1:" . preg_replace("/'/", "\\'", "Sélection ->") . ";";

while ($res = $db->fetch_object($sql)) {
    $js .= $res->id . ":" . preg_replace("/'/", "\\'", utf8_decode(utf8_encode($res->label))) . ";";
    $arrModel[$res->id] = $res->label;
}

$js = preg_replace('/;$/', '', $js);
$js .= '";';

$requete = "SELECT DISTINCT fk_statut FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet ORDER BY fk_statut ASC";
$sql = $db->query($requete);
$js .= 'var statutRess = "';
$js .= "-1:" . preg_replace("/'/", "\\'", utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->")))) . ";";

while ($res = $db->fetch_object($sql)) {
    $fakeProcess = new processDet($db);
    $fakeProcess->fk_statut = $res->fk_statut;

    $js .= $res->fk_statut . ":" . preg_replace("/'/", "\\'", utf8_decode(utf8_encode($fakeProcess->getLibStatut(1)))) . ";";
}

$js = preg_replace('/;$/', '', $js);
$js .= '";';


/* Mod drsi ne sert a rie a priori est prend enormement de ressource
$requete = "SELECT d.element_refid, e.classFile, e.type
              FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet as d,
                   " . MAIN_DB_PREFIX . "Synopsis_Process as p,
                   " . MAIN_DB_PREFIX . "Synopsis_Process_type_element as e
             WHERE e.id = p.typeElement_refid
               AND d.process_refid = p.id";
$sql = $db->query($requete);
 */
$js .= 'var elemRess = "';
$js .= "-1:" . preg_replace("/'/", "\\'", utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->")))) . ";";
/*
while ($res = $db->fetch_object($sql)) {
    require_once(DOL_DOCUMENT_ROOT . "" . $res->classFile);
    $tmp = $res->type;
    $tmpObj = new $tmp($db);
    $tmpObj->fetch($res->element_refid);
    $js .= $res->element_refid . ":" . preg_replace("/'/", "\\'", utf8_decode(utf8_encode($tmpObj->ref))) . ";";
}
Fin mod drsi */
$js = preg_replace('/;$/', '', $js);
$js .= '";';


$js .= 'var gridimgpath = "' . $imgPath . '/images/";';
$js .= 'var userId = "' . $user->id . '";';

if ('x' . $_REQUEST['type'] != 'x') {
    $js .= 'var type = "' . $_REQUEST['type'] . '";';
} else {
    $js .= 'var type = false;';
}



$jqgridJs .= <<<EOF


jQuery.datepicker.setDefaults($.datepicker.regional['fr']);

jQuery.jgrid.edit.msg.minValue="Ce champs est requis";
var sg;
jQuery(document).ready(function() {

//    jQuery('#changeChrono').change(function(){
//        if (jQuery('#changeChrono :selected').val() > 0 )
//        {
//            //Met en place le filtre
//            type = jQuery('#changeChrono :selected').val();
//        } else {
//            //reset filtre
//            type=false;
//            if (sg){
//                sg.clearSearch();
//                sg=false;
//            }
//        }
//    });

    var extra = "";
    if (type)
    {
        extra="&type="+type;
    }
var grid = jQuery("#gridListProcess").jqGrid({
            datatype: "json",
            url: "ajax/listProcessDet_json.php?userId="+userId+extra,
EOF;
$jqgridJs .= <<<EOF
            colNames:['id',"hasRev", "Ref", "Type","El&eacute;ment",'Date cr&eacute;ation','Date modification','Statut'],
EOF;
$jqgridJs .= <<<EOF
            colModel:[  {name:'id',index:'c.id', width:0, hidden:true,key:true,hidedlg:true,search:false},
                        {name:'hasRev',index:'hasRev', width:0, hidden:true,hidedlg:true,search:false},
                        {
                            name:'Ref',
                            index:'c.ref',
                            align:"left",
                            width: 250,
                            stype: 'select',
                            edittype: 'select',
                            editable: false,
                            searchoptions:{sopt:['eq','ne','nc','cn',"bw",'ew','nb','ne']},
                        }, {
                            name:'Type',
                            index:'c.process_refid',
                            align:"left",
                            width: 250,
                            stype: 'select',
                            edittype: 'select',
                            editable: false,
                            searchoptions:{sopt:['eq','ne']},
                            editoptions: {
                                value: typeRess,
                            },formoptions:{ elmprefix:"*  " }
                        }, {
                            name:'Element',
                            index:'c.element_refid',
                            align:"left",
                            width: 250,
                            stype: 'select',
                            edittype: 'select',
                            editable: false,
                            sortable: false,
                            searchoptions:{sopt:['eq','ne']},
                            editoptions: {
                                value: elemRess,
                            },formoptions:{ elmprefix:"*  " }
                        }, {
                            name:'date',
                            index:'c.date_create',
                            width:155,
                            align:"center",
                            sorttype:"date",
                            formatter:'date',
                            formatoptions:{srcformat:"Y-m-d H:i",newformat:"d/m/Y H:i"},
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
                                        showTime: true,
                                        constrainInput: true,
                                        gotoCurrent: true
                                    });
                                    jQuery("#ui-datepicker-div").addClass("promoteZ");
                                },
                                sopt:['eq','ne',"le",'lt',"ge","gt"],
                            },
                        }, {
                            name:'dateMod',
                            index:'c.date_modify',
                            width:155,
                            align:"center",
                            sorttype:"date",
                            formatter:'date',
                            formatoptions:{srcformat:"Y-m-d H:i",newformat:"d/m/Y H:i"},
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
                                        showTime: true,
                                        constrainInput: true,
                                        gotoCurrent: true
                                    });
                                    jQuery("#ui-datepicker-div").addClass("promoteZ");
                                },
                                sopt:['eq','ne',"le",'lt',"ge","gt"],
                            },
                        }, {
                            name:'Statut',
                            index:'c.fk_statut',
                            align:"left",
                            width: 205,
                            stype: 'select',
                            edittype: 'select',
                            editable: false,
                            searchoptions:{sopt:['eq','ne']},
                            editoptions: {
                                value: statutRess,
                            },formoptions:{ elmprefix:"*  " }
                        },
EOF;
///societe/calendar.php?socid=
$jqgridJs .= <<<EOF

                      ],
            rowNum:30,
            rowList:[30,50,100],
            imgpath: gridimgpath,
            pager: jQuery('#gridListProcessPager'),
            sortname: 'c.date_modify',
            beforeRequest: function(){
                jQuery('#gview_gridListProcess').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
            },
            gridComplete: function(){
                jQuery('.jqgrow').each(function(){
                    var hasRev = jQuery(this).find('.hasRev').text();
                    if(hasRev != 1)
                    {
                        jQuery(this).find('.ui-sgcollapsed .ui-icon').parent().remove();
                        jQuery(this).find('.ui-sgcollapsed').removeClass("ui-sgcollapsed").removeClass('sgcollapsed');
                    }
                });
EOF;
$jqgridJs .= "jQuery('.butAction').mouseover(function(){";
$jqgridJs .= "    jQuery(this).removeClass('ui-state-default');";
$jqgridJs .= "    jQuery(this).addClass('ui-state-hover');";
$jqgridJs .= "});";
$jqgridJs .= "jQuery('.butAction').mouseout(function(){";
$jqgridJs .= "    jQuery(this).removeClass('ui-state-hover');";
$jqgridJs .= "    jQuery(this).addClass('ui-state-default');";
$jqgridJs .= "});";

$jqgridJs .= "jQuery('.butAction-rev').mouseover(function(){";
$jqgridJs .= "    jQuery(this).removeClass('ui-state-hover');";
$jqgridJs .= "    jQuery(this).addClass('ui-state-default');";
//$jqgridJs .= "console.log(jQuery(this).find('.ui-icon').css('background-image'));";
$jqgridJs .= "    jQuery(this).find('.ui-icon').css('background-image','url(\"" . $css . "/images/ui-icons_0073ea_256x240.png\")');";
//            $jqgridJs .= "    jQuery(this).find('.ui-icon').addClass('ui-state-default');";
$jqgridJs .= "});";
$jqgridJs .= "jQuery('.butAction-rev').mouseout(function(){";
$jqgridJs .= "    jQuery(this).removeClass('ui-state-default');";
$jqgridJs .= "    jQuery(this).addClass('ui-state-hover');";
$jqgridJs .= "    jQuery(this).find('.ui-icon').css('background-image','url(\"" . $css . "/images/ui-icons_ffffff_256x240.png\")');";
$jqgridJs .= "});";
$jqgridJs .= <<<EOF
            },
            mtype: "POST",
            viewrecords: true,
            autowidth: true,
            height: 500,
            sortorder: "desc",
            //multiselect: true,
            caption: "<span style='padding:4px; font-size: 16px; '>Process</span>",
            //SubGrid revision
            subGrid: true,
            subGridUrl: 'ajax/listProcessDetRevision_json.php?userId='+userId+extra,
            subGridRowExpanded: function(subgrid_id, row_id) {
                // we pass two parameters
                // subgrid_id is a id of the div tag created within a table
                // the row_id is the id of the row
                // If we want to pass additional parameters to the url we can use
                // the method getRowData(row_id) - which returns associative array in type name-value
                // here we can easy construct the following
                var subgrid_table_id;
                subgrid_table_id = subgrid_id+"_t";
                  jQuery("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
                  jQuery("#"+subgrid_table_id).jqGrid({
                      url: 'ajax/listProcessDetRevision_json.php?userId='+userId+extra+'&processDet_refid='+row_id,
                      //url:"subgrid.php?q=2&id="+row_id,
                      datatype: "json",
                      colNames: ['Id','Ref','Date de la rev.','Der. modif. rev','Statut',"R&eacute;vision"],
                      colModel: [
                        {name:"id",index:"id",width:55,key:true,hidden:true},
                        {name:"ref",index:"ref",width:290},
                        {
                            name:"dateCreate",
                            index:"date_create",
                            width:180,
                            align:"center",
                            sorttype:"date",
                            formatter:'date',
                            formatoptions:{srcformat:"Y-m-d H:i",newformat:"d/m/Y H:i"},
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
                                        showTime: true,
                                        constrainInput: true,
                                        gotoCurrent: true
                                    });
                                    jQuery("#ui-datepicker-div").addClass("promoteZ");
                                },
                                sopt:['eq','ne',"le",'lt',"ge","gt"],
                            },
                        },
                        {
                            name:'dateMod',
                            index:'c.date_modify',
                            width:180,
                            align:"center",
                            sorttype:"date",
                            formatter:'date',
                            formatoptions:{srcformat:"Y-m-d H:i",newformat:"d/m/Y H:i"},
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
                                        showTime: true,
                                        constrainInput: true,
                                        gotoCurrent: true
                                    });
                                    jQuery("#ui-datepicker-div").addClass("promoteZ");
                                },
                                sopt:['eq','ne',"le",'lt',"ge","gt"],
                            },
                        },{
                            name:'Statut',
                            index:'c.fk_statut',
                            align:"left",
                            width: 205,
                            stype: 'select',
                            edittype: 'select',
                            editable: false,
                            searchoptions:{sopt:['eq','ne']},
                            editoptions: {
                                value: statutRess,
                            },
                            formoptions:{ elmprefix:"*  " }
                        },
                        {
                            name:"revision",
                            index:"revision",
                            width:55,
                            hidden:true
                        },
                        ],
                      height: "100%",
                      rowNum:20,
                      width: 880,
                      imgpath: gridimgpath,
                      sortname: 'revision',
                      sortorder: "desc",
                  });
            },
EOF;

$jqgridJs.= '    }).navGrid("#gridListProcessPager",' . "\n";
$jqgridJs.= '                   { view:false, add:false,' . "\n";
$jqgridJs.= '                     del:false,' . "\n";
$jqgridJs.= '                     edit:false,' . "\n";
$jqgridJs.= '                     search:true,' . "\n";
$jqgridJs.= '                     position:"left"' . "\n";
$jqgridJs.= '                     });' . "\n";

$jqgridJs .= "    sg = jQuery('#mysearch').filterGrid('#gridListProcess',{
                                                            gridModel:false,
                                                            filterModel: [{label:'', stype:'select',sopt:{value: typeRess,},name:'type' }],
                                                            gridNames:false,
                                                            searchButton: 'Filtrer',
                                                            clearButton: 'Annuler',
                                                            autosearch:true,
                                                            buttonclass: 'butAction',
                                                            enableSearch:true,
                                                            enableClear: true,
                                                      });";
//$jqgridJs .= "  jQuery('select:not(.maxWidthSelect):not(.noSelDeco)').selectmenu({style: 'dropdown', maxHeight: 300 }); ";
$jqgridJs.= '   });' . "\n";
$js .= $jqgridJs . "</script>";

llxHeader($js, 'Liste process', '');

print '<div id="mysearch"></div>';

print "<br/>";

print '<table id="gridListProcess" class="scroll ui-widget " cellpadding="0" cellspacing="0"></table>';
print '<div id="gridListProcessPager" class="scroll" style="text-align:center;"></div>';


$db->close();

llxFooter('$Date: 2008/09/10 22:23:38 $ - $Revision: 1.60.2.2 $');
?>