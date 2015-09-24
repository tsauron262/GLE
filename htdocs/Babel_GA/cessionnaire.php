<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
/*
 */

/**
        \file       htdocs/Babel_GA/cessionnaire.php
        \ingroup    Cessionnaires
        \brief      Page liste des Cessionnaires
        \version    $Id: liste.php,v 1.29 2008/04/07 17:16:46 eldy Exp $
*/

//TODO  search KO & etat des services

require("./pre.inc.php");

$langs->load("products");
$langs->load("companies");
$langs->load("synopsisGene@synopsistools");
if ($conf->global->MAIN_MODULE_BABELGA."x"!="1x")
{
    header('location: ../index.php');
    exit;
}
// Security check
$contratid = isset($_REQUEST["id"])?$_REQUEST["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user);


/*
 * Affichage page
 */

$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

//$js = ' <script src="'.$jspath.'/jquery-1.3.2.js" type="text/javascript"></script>';
//$js .= ' <script src="'.$jqueryuipath.'/jquery-ui.js" type="text/javascript"></script>';
//$js .= ' <script src="'.$jqueryuipath.'/ui.core.js" type="text/javascript"></script>';

$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';

$js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
$js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';

//$js .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
//$js .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu({style: 'dropdown', maxHeight: 300 }); });  </script>\n";



$js .= "<style type='text/css'>body { position: static; }</style>
        <script type='text/javascript'>";
$requete = "SELECT DISTINCT  ".MAIN_DB_PREFIX."societe.rowid , ".MAIN_DB_PREFIX."societe.nom FROM ".MAIN_DB_PREFIX."societe WHERE ".MAIN_DB_PREFIX."societe.cessionnaire = 1";
$sql = $db->query($requete);
$js .= 'var socRess = "';
$js .=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";

while ($res = $db->fetch_object($sql))
{
    $js .= $res->rowid . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->nom)))  . ";";

}
$js = preg_replace('/;$/',"",$js);

$js .= '";';


$requete = "SELECT DISTINCT ".MAIN_DB_PREFIX."c_departements.rowid, ".MAIN_DB_PREFIX."c_departements.nom FROM ".MAIN_DB_PREFIX."c_departements , ".MAIN_DB_PREFIX."societe WHERE ".MAIN_DB_PREFIX."societe.fk_departement =".MAIN_DB_PREFIX."c_departements.rowid and ".MAIN_DB_PREFIX."societe.cessionnaire=1 ";
$sql = $db->query($requete);
$js .= 'var depRess = "';
$js .=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";

while ($res = $db->fetch_object($sql))
{
    $js .= $res->rowid . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->nom)))  . ";";
}

$js = preg_replace('/;$/','',$js);
$js .= '";';


$js .= 'var gridimgpath = "'.$imgPath.'/images/";';
$js .= 'var userId = "'.$user->id .'";';

$jqgridJs .= <<<EOF

$.datepicker.setDefaults($.datepicker.regional['fr']);


jQuery.jgrid.edit.msg.minValue="Ce champs est requis";
$(document).ready(function() {
var grid = $("#gridListCessionnaire").jqGrid({
            datatype: "json",
            url: "ajax/listCessionnaire_json.php?userId="+userId,
            colNames:['id', "Tiers",'Date Cr&eacute;ation','Ville','D&eacute;partement'],
            colModel:[  {name:'id',index:'s.rowid', width:0, hidden:true,key:true,hidedlg:true,search:false},
                        {
                            name:'Tiers',
                            index:'s.nom',
                            align:"left",
                            editable:true,
                            stype: 'select',
                            edittype: 'select',
                            editable: false,
                            searchoptions:{sopt:['eq','ne']},
                            editoptions: {
                                value: socRess,
                            },
                        },
                        {
                            name:'Date Cr&eacute;ation',
                            index:'s.datec',
                            width:90,
                            align:"center",
                            sorttype:"date",
                            formatter:'date',
                            formatoptions:{srcformat:"Y-m-d",newformat:"d/m/Y"},
                            editable:false,
                            searchoptions:{
                                dataInit:function(el){
                                    $.datepicker.setDefaults($.datepicker.regional['fr']);
                                    $(el).datepicker({
                                        regional: 'fr',
                                        changeMonth: true,
                                        changeYear: true,
                                        showButtonPanel: true,
                                        constrainInput: true,
                                        gotoCurrent: true,
                                        dateFormat: 'dd/mm/yy',
                                    });
                                    $("#ui-datepicker-div").addClass("promoteZ");
                                    //$("#ui-timepicker-div").addClass("promoteZ");
                                },
                                sopt:['eq','ne',"le",'lt',"ge","gt"],
                            },
                        },
                        {
                            name:'Ville',
                            index:'ville',
                            width:80,
                            align:"left",
                            editable:false,
                            searchoptions:{
                                sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]
                            }
                        },
                        {
                            name:'departement',
                            index:'departement',
                            width:80,
                            align:"left",
                            editable:true,
                            stype: 'select',
                            edittype: 'select',
                            editable: false,
                            searchoptions:{sopt:['eq','ne']},
                            editoptions: {
                                value: depRess,
                            },

                        },

                      ],
            rowNum:30,
            rowList:[30,50,100],
            imgpath: gridimgpath,
            pager: jQuery('#gridListCessionnairePager'),
            sortname: 's.rowid',
            mtype: "POST",
            viewrecords: true,
            width: "900",
            height: 500,
            beforeRequest: function(){
                jQuery('#gview_gridListCessionnaire').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
            },
            sortorder: "desc",
            //multiselect: true,
            caption: "<span style='padding:4px; font-size: 13px; '>Cessionnaires</span>",
            viewsortcols: true,
EOF;

    $jqgridJs.= '    }).navGrid("#gridListCessionnairePager",'."\n";
    $jqgridJs.= '                   { view:false, add:false,'."\n";
    $jqgridJs.= '                     del:false,'."\n";
    $jqgridJs.= '                     edit:false,'."\n";
    $jqgridJs.= '                     search:true,'."\n";
    $jqgridJs.= '                     position:"left"'."\n";
    $jqgridJs.= '                     });'."\n";


$jqgridJs.= '   });'."\n";
$js .= $jqgridJs."</script>";

//llxHeader();
llxHeader($js,$langs->trans("Liste des Cessionnaires"),"Cessionnaires","1");

print "<br/>";
print "<br/>";
print "<br/>";
print '<table id="gridListCessionnaire" class="scroll ui-widget " cellpadding="0" cellspacing="0"></table>';
print '<div id="gridListCessionnairePager" class="scroll" style="text-align:center;"></div>';

llxFooter('$Date: 2008/04/07 17:16:46 $ - $Revision: 1.29 $');
?>
