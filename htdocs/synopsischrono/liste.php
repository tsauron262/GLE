<?php

/*
 *
 */
/**
 *
 * Name : liste.php
 * GLE-1.2
 */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");

$langs->load("chrono@synopsischrono");

// Security check
$socid = isset($_GET["socid"]) ? $_GET["socid"] : '';
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'synopsischrono', $socid, '', '', 'Afficher');
//$user, $feature='societe', $objectid=0, $dbtablename='',$feature2='',$feature3=''

/*
 * Actions
 */

$arrModel = array();

$jspath = DOL_URL_ROOT . "/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT . "/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT . "/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT . "/Synopsis_Common/images";
$js = "";

$js .= '      <link type="text/css" rel="stylesheet" href="' . DOL_URL_ROOT . '/Synopsis_Common/css/jquery.treeview.css" />';


$js .= '<link rel="stylesheet" type="text/css" media="screen" href="' . $jspath . '/jqGrid-3.5/css/ui.jqgrid.css" />';
//$js .= '<link rel="stylesheet" type="text/css" media="screen" href="' . $jspath . '/jqGrid-3.5/css/jquery.searchFilter.css" />';
//$js .= ' <script src="'.$jspath.'/ajaxfileupload.js" type="text/javascript"></script>';

$js .= '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/includes/jquery/css/smoothness/jquery-ui-latest.custom.css" />' . "\n";
$js .= '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/includes/jquery/plugins/jnotify/jquery.jnotify-alt.min.css" />' . "\n";

$js .= '<script language="javascript"  src="' . DOL_URL_ROOT . '/includes/jquery/js/jquery-latest.min.js"></script>' . "\n";
$js .= '<script language="javascript"  src="' . DOL_URL_ROOT . '/includes/jquery/js/jquery-ui-latest.custom.min.js"></script>' . "\n";
$js .= '<script language="javascript"  src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jqGrid-3.5/js/grid.custom.js"></script>' . "\n";

$js .= ' <script src="' . $jspath . '/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
$js .= ' <script src="' . $jspath . '/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';
$js .= ' <script src="' . $jspath . '/jqGrid-3.5/js/grid.custom.js" type="text/javascript"></script>';
$js .= ' <script src="' . DOL_URL_ROOT . '/includes/jquery/plugins/tiptip/jquery.tipTip.min.js" type="text/javascript"></script>';
$js .= ' <script src="' . DOL_URL_ROOT . '/includes/jquery/plugins/jnotify/jquery.jnotify.min.js" type="text/javascript"></script>';
$js .= ' <script src="' . DOL_URL_ROOT . '/core/js/jnotify.js" type="text/javascript"></script>';


$js .= "<style type='text/css'>body { position: static; }                 .ui-datepicker select.ui-datepicker-month, .ui-datepicker select.ui-datepicker-year  {width:48%;}
.ui-pg-selbox { min-width:50px; }
</style>
        <script type='text/javascript'>";
$requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_conf WHERE active = 1 ORDER BY titre ASC";
$sql = $db->query($requete);
$js .= 'var typeRess = "';
$js .= "-1:" . preg_replace("/'/", "\\'", "Sélection ->") . ";";

while ($res = $db->fetch_object($sql)) {
    $js .= $res->id . ":" . html_entity_decode($res->titre) . ";";
    $arrModel[$res->id] = $res->titre;
}

$js = preg_replace('/;$/', '', $js);
$js .= '";';

$requete = "SELECT " . MAIN_DB_PREFIX . "societe.rowid, " . MAIN_DB_PREFIX . "societe.nom FROM " . MAIN_DB_PREFIX . "societe, " . MAIN_DB_PREFIX . "synopsischrono WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "synopsischrono.fk_soc GROUP BY " . MAIN_DB_PREFIX . "societe.rowid";
$sql = $db->query($requete);
$js .= 'var socRess = "';
$js .= "-1:" . html_entity_decode("S&eacute;lection ->") . ";";

if($db->num_rows($sql) < 1000){
while ($res = $db->fetch_object($sql)) {
    $js .= $res->rowid . ":" . html_entity_decode($res->nom) . ";";
}
}
else
    $js .= "0 : Trop de résultat;";

$js = preg_replace('/;$/', '', $js);
$js .= '";';



$requete = "SELECT DISTINCT fk_statut FROM " . MAIN_DB_PREFIX . "synopsischrono ORDER BY fk_statut ASC";
$sql = $db->query($requete);
$js .= 'var statutRess = "';
$js .= "-1:" . html_entity_decode("Sélection ->") . ";";

while ($res = $db->fetch_object($sql)) {
    $fakeChrono = new Chrono($db);
    $fakeChrono->statut = $res->fk_statut;

    $js .= $res->fk_statut . ":" . html_entity_decode(str_replace("&eacute;", "e", $fakeChrono->getLibStatut(0))) . ";";
}

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
        urlGrid = "ajax/listChrono_json.php?userId="+userId+extra2;
EOF;



$js .= "\n\n".'var extra2 = \''.($_REQUEST['filtre']? "&filtre=".$_REQUEST['filtre']."" : "").'\';'
        . 'setTimeout(function(){'
            . 'jQuery("#gridListProspect").jqGrid(\'setGridParam\', { url: urlGrid });'
        . '}, 500);';

$jqgridJs .= <<<EOF
var grid = jQuery("#gridListProspect").jqGrid({
            datatype: "json",
EOF;


        $jqgridJs .=    'url: urlGrid+extra2,';
if ($conf->global->CHRONO_DISPLAY_SOC_AND_CONTACT)
    $jqgridJs .= "colNames:['id' ,'hasRev','Ref', 'Type','Tiers','Contact','Date cr&eacute;ation','Dern. modif.','Statut', 'Nb Doc'],";
else
    $jqgridJs .= "colNames:['id' ,'hasRev','Ref', 'Type','Date cr&eacute;ation','Dern. modif.','Statut', 'Nb Doc'],";
$jqgridJs .= <<<EOF
            colModel:[  {name:'id',index:'id', width:0, hidden:true,key:true,hidedlg:true,search:false},
                        {name:'hasRev',index:'hasRev', width:0, hidden:true,hidedlg:true,search:false},
                        {
                            name:'Ref',
                            index:'ref',
                            align:"left",
                            width: 250,
                            stype: 'text',
                            edittype: 'text ',
                            editable: false,
                            search: true,
                        }, {
                            name:'Type',
                            index:'model_refid',
                            align:"left",
                            width: 250,
                            stype: 'select',
                            edittype: 'select',
                            editable: false,
                            searchoptions:{sopt:['eq','ne']},
                            editoptions: {
                                value: typeRess,
                            },formoptions:{ elmprefix:"*  " }
                        },
EOF;
if ($conf->global->CHRONO_DISPLAY_SOC_AND_CONTACT)
    $jqgridJs .= <<<EOF
                         {
                            name:'Tiers',
                            index:'nom',
                            align:"left",
                            width: 250,
                            stype: 'select',
                            edittype: 'select',
                            editable: false,
                            searchoptions:{sopt:['eq','ne']},
                            editoptions: {
                                value: socRess,
                            },formoptions:{ elmprefix:"*  " }
                        }, {
                            name:'Contact',
                            index:'name',
                            align:"left",
                            width: 250,
                            stype: 'select',
                            edittype: 'select',
                            editable: false,
                            searchoptions:{sopt:['eq','ne','nc','cn',"bw",'ew','nb','ne']},
                            formoptions:{ elmprefix:"*  " }
                        },
EOF;
$jqgridJs .= <<<EOF
                        {
                            name:'date',
                            index:'date_create',
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
                                        showTime: false,
                                        constrainInput: true,
                                        gotoCurrent: true
                                    });
                                    jQuery("#ui-datepicker-div").addClass("promoteZ");
                                },
                                sopt:['eq','ne',"le",'lt',"ge","gt"],
                            },
                        }, {
                            name:'date_modify',
                            index:'tms',
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
                                        showTime: false,
                                        constrainInput: true,
                                        gotoCurrent: true
                                    });
                                    jQuery("#ui-datepicker-div").addClass("promoteZ");
                                },
                                sopt:['eq','ne',"le",'lt',"ge","gt"],
                            },
                        }, {
                            name:'Statut',
                            index:'fk_statut',
                            align:"left",
                            width: 205,
                            stype: 'select',
                            editable: false,
                            searchoptions:{sopt:['eq','ne']},
                            editoptions: {
                                value: statutRess,
                            },
                            formoptions:{ elmprefix:"*  " }
                        }, {
                            name:'Nb Doc',
                            index:'nb_doc',
                            align:"right",
                            search:false,
                            width: 60
                        }
EOF;
///societe/calendar.php?socid=
$jqgridJs .= <<<EOF

                      ],
            rowNum:25,
            rowList:[25,50,100],
            imgpath: gridimgpath,
            pager: jQuery('#gridListProspectPager'),
            sortname: 'c.date_create',
            beforeRequest: function(){
                jQuery('#gview_gridListProspect').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
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
            height: 575,
            sortorder: "desc",
            //multiselect: true,
            caption: "<span style='padding:4px; font-size: 16px; '>Chrono</span>",
            //SubGrid revision
            subGrid: true,
            subGridUrl: 'ajax/listChronoRevision_json.php?userId='+userId+extra,
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
                      url: 'ajax/listChronoRevision_json.php?userId='+userId+extra+'&chrono_refid='+row_id,
                      //url:"subgrid.php?q=2&id="+row_id,
                      datatype: "json",

                      colNames: ['Id','Ref','Date de la rev.','Der. modif. rev','Statut',"R&eacute;vision", 'Nb Doc'],
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
                            index:'c.tms',
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
                            width: 150,
                        },
                        {
                            name:"revision",
                            index:"revision",
                            width:55,
                            hidden:true
                        }, {
                            name:'Nb Doc',
                            index:'nb_doc',
                            align:"right",
                            sorttype:"int",
                            search:false,
                            width: 60
                        },
                        ],
                      height: "100%",
                      rowNum:20,
                      width: 1087,
                      imgpath: gridimgpath,
                      sortname: 'revision',
                      sortorder: "desc",
                  });
            },
EOF;

$jqgridJs.= '    }).navGrid("#gridListProspectPager",' . "\n";
$jqgridJs.= '                   { view:false, add:false,' . "\n";
$jqgridJs.= '                     del:false,' . "\n";
$jqgridJs.= '                     edit:false,' . "\n";
$jqgridJs.= '                     search:false,' . "\n";
$jqgridJs.= '                     position:"left"' . "\n";
$jqgridJs.= '                     });' . "\n";

$jqgridJs .= "     jQuery('#gridListProspect').filterToolbar('');";
    
$jqgridJs .= "    sg = jQuery('#mysearch').filterGrid('#gridListProspect',{
                                                            gridModel:false,
                                                            filterModel: [{label:'', stype:'select',sopt:{value: typeRess},name:'type' }],
                                                            gridNames:false,
                                                            searchButton: 'Filtrer',
                                                            clearButton: 'Annuler',
                                                            autosearch:false,
                                                            buttonclass: 'butAction',
                                                            enableSearch:true,
                                                            enableClear: true,
                                                      });";
$jqgridJs .= "    sg = jQuery('#mysearch2').filterGrid('#gridListProspect',{
                                                            gridModel:false,
                                                            filterModel: [{label:'', stype:'text',sopt:{value: '".$_REQUEST['filtre']."'},name:'filtre' }],
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

llxHeader($js, 'Liste chrono', '');
dol_fiche_head('', 'Chrono', $langs->trans("Liste des Chrono"));

print '<div id="mysearch"></div>';
print "<br/>";
print '<div id="mysearch2"></div>';

print "<br/>";

print '<table id="gridListProspect" class="scroll ui-widget " cellpadding="0" cellspacing="0"></table>';
print '<div id="gridListProspectPager" class="scroll" style="text-align:center;"></div>';


$db->close();

llxFooter('$Date: 2008/09/10 22:23:38 $ - $Revision: 1.60.2.2 $');
?>
