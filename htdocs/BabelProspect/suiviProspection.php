<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 7-31-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : suiviProspection.php
  * GLE-1.1
  */

  require_once('pre.inc.php');
  require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");


$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("synopsisGene@synopsistools");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);
// Initialisation de l'objet Societe
$soc = new Societe($db);
$soc->id = $socid;
$soc->fetch($socid);



$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

$js .= ' <script src="'.$jspath.'/jqGrid-3.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';
$js .= "<style type='text/css'>body { position: static; }</style>";
$js .= "<style type='text/css'>.ui-progressbar{ height: 13px; background-color: #ffffff; margin: 0px; float: left; width: 100%;}</style>";
$js .= "<style type='text/css'>.ui-progressbar-value{ border:1px solid #000000;float: left; width: 100%; }
                                SELECT { width: 150px; }
                                INPUT.hasDatepicker { width: 150px;}
                                .ui-pg-selbox { width: auto; }</style>";
$js .= "<style type='text/css'>.promoteZ { z-index: 2006; /* Dialog z-index is 1006*/}</style>";

$jqgridJs = <<<EOF
<script type='text/javascript'>
EOF;
$jqgridJs .= 'var gridimgpath="'.$imgPath.'/images/";';
$jqgridJs .= 'var userId="'.$user->id.'";';
$jqgridJs .= 'var socId="'.$soc->id.'"; ';


//Affiche Source  , note , date
//Permet d'ajouter des infos

$jqgridJs .= <<<EOF
var lastsel2;


$(document).ready(function(){
    var get = "";

$.datepicker.setDefaults($.extend({showMonthAfterYear: false, changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'cal.png',
                    buttonImageOnly: true,
                    showTime: true,
                    duration: '',
                    constrainInput: false,}, $.datepicker.regional['fr']));
    if (socId > 0)
    {
        get = "&socid="+socId;
    }
    $("#gridListHistoProspect").jqGrid({
            datatype: "json",
            url: "ajax/prospection_json.php?userId="+userId+get,
            colNames:['id',"Importance", 'Source','Date', 'Notes'],
            colModel:[  {name:'id',index:'id', width:55, hidden:true,key:true,hidedlg:true,search:false},
                        {name:'importance',index:'importance', width:80, align:"center",editable:true,editrules:{required:true},edittype:"select",editoptions:{value:"0:Aucune;1:Faible;2:Moyenne;3:Elev&eacute;4:Maximum"},formoptions:{ rowpos:1, elmprefix:"*  " }},
                        {name:'source',index:'source', width:90, align:"center",editable:true,editrules:{required:true},edittype:"select",editoptions:{value:"0:Interne;1:Campagne"},formoptions:{ rowpos:2, elmprefix:"*  " },},
                        {name:'dateNote',index:'dateNote', width:90, align:"center",editrules:{required:true,length:50},editable:true,sorttype:"date",
                            editable:true,
                            editoptions:{size:12,
                                 length: "150",
                                dataInit:function(el){
                                    $(el).datepicker({dateFormat:'dd/mm/yy'});
                                },
                                defaultValue: function(){
                                    var currentTime = new Date();
                                    var month = parseInt(currentTime.getMonth() + 1);
                                    month = month <= 9 ? "0"+month : month;
                                    var day = currentTime.getDate();
                                    day = day <= 9 ? "0"+day : day;
                                    var year = currentTime.getFullYear();
                                    var hour = currentTime.getHours();
                                    hour = hour <= 9 ? "0"+hour : hour;
                                    var minu = currentTime.getMinutes();
                                    minu = minu <= 9 ? "0"+minu : minu;
                                    return day+"/"+month + "/"+year + " "+hour+":"+minu;
                                }
                            },
                            formoptions:{ rowpos:3, elmprefix:"*  ",elmsuffix:""},
                            editrules:{required:true}
                             },
                        {name:'note',index:'note', width:80, align:"center",editrules:{required:true},editable:true,edittype:"textarea", editoptions:{rows:"3",cols:"30"},formoptions:{ rowpos:5, elmprefix:"*  " }},
                      ],
            rowNum:10,
            ondblClickRow: function(id){
                if(id && id!= lastsel2){
                    jQuery('#gridListHistoProspect').restoreRow(lastsel2);
                    jQuery('#gridListHistoProspect').editRow(id,true,pickdates);
                    lastsel2=id;
                }
            },
            editurl: "ajax/editProspection_json.php?socid="+socId,
            rowList:[10,20,30],
            imgpath: gridimgpath,
            pager: jQuery('#gridListHistoProspectPager'),
            sortname: 'id',
            mtype: "POST",
            viewrecords: true,
            width: "900",
            height: 500,
            sortorder: "desc",
            //multiselect: true,
            caption: "Prospection",
            viewsortcols: true,
            loadComplete: function(){
                $('#gview_gridListHistoProspect').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
            },
        }).navGrid('#gridListHistoProspectPager',
                    {view:true},
                     {bottominfo:"Les champs marqu&eacute;s d'un * sont requis",savekey: [true,13], navkeys:[true,38,40]},
                     {bottominfo:"Les champs marqu&eacute;s d'un * sont requis",closeAfterAdd: true, savekey: [true,13], navkeys:[true,38,40] },
                     {bottominfo:"Les champs marqu&eacute;s d'un * sont requis"},{},{}
        );
    jQuery("#tr_dateNote","#gridListHistoProspect").datepicker({dateFormat:"dd/mm/yyyy"});
    $("#ui-datepicker-div").addClass("promoteZ");
    $("#ui-timepicker-div").addClass("promoteZ");
});
function pickdates(id){
    jQuery("#"+id+"_dateNote","#gridListHistoProspect").datepicker({dateFormat:"dd/mm/yyyy"});
}
</script>
EOF;

$js .= $jqgridJs;


llxHeader($js,$langs->trans("Prospection"),"Prospection","1");

$head = societe_prepare_head($soc);

dol_fiche_head($head, 'prospection', $langs->trans("Prospection"));


//Affiche un jqgrid de suivie

print '<table id="gridListHistoProspect" class="scroll" cellpadding="0" cellspacing="0"></table>';
print '<div id="gridListHistoProspectPager" class="scroll" style="text-align:center;"></div>';


?>
