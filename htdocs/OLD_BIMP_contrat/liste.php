<?php
/*
  *
  */
/*
 */

/**
        \file       htdocs/contrat/list.php
        \ingroup    contrat
        \brief      Page liste des contrats
        \version    $Id: liste.php,v 1.29 2008/04/07 17:16:46 eldy Exp $
*/

//TODO  search KO & etat des services

require("./pre.inc.php");
require_once (DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

$langs->load("contracts");
$langs->load("products");
$langs->load("companies");

// Security check
$contratid = isset($_REQUEST["id"])?$_REQUEST["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat', $contratid,'');

$staticcontrat=new Contrat($db);
$staticcontratligne=new ContratLigne($db);

/*
 * Affichage page
 */

$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";


$js .= '      <link type="text/css" rel="stylesheet" href="'.DOL_URL_ROOT.'/Synopsis_Common/css/jquery.treeview.css" />';


$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
$js .= ' <script src="'.$jspath.'/ajaxfileupload.js" type="text/javascript"></script>';
$js .= ' <script src="'.$jspath.'/jqGrid-3.5/jquery.jqGrid.js" type="text/javascript"></script>';
$js .= ' <script src="'.$jspath.'/jqGrid-3.5/js/jquery.fmatter.js" type="text/javascript"></script>';
$js .= ' <script src="'.$jspath.'/jqGrid-3.5/js/grid.formedit.js" type="text/javascript"></script>';




$js .= "<style type='text/css'>body { position: static; }</style>
        <script type='text/javascript'>";
$requete = "SELECT DISTINCT ".MAIN_DB_PREFIX."contrat.fk_soc, ".MAIN_DB_PREFIX."societe.nom FROM ".MAIN_DB_PREFIX."contrat,".MAIN_DB_PREFIX."societe WHERE ".MAIN_DB_PREFIX."societe.rowid = fk_soc";
$sql = $db->query($requete);
$js .= 'var socRess = "';
$js .=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";

while ($res = $db->fetch_object($sql))
{
    $js .= $res->fk_soc . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->nom)))  . ";";

}
$js = preg_replace('/;$/',"",$js);

$js .= '";';


$js .= 'var contratTypeRess = "-1:';
$js .=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";


$js .= "0:Libre;";
$js .= "1:Service;";
$js .= "2:Ticket;";
$js .= "3:Maintenance;";
$js .= "4:SAV;";
$js .= "5:Location;";
$js .= "6:Location fin.;";
$js .= "7:Mixte;";


$js .= '";';

$js .= 'var gridimgpath = "'.$imgPath.'/images/";';
$js .= 'var userId = "'.$user->id .'";';
$js .= 'var socId = "'.($_REQUEST['socid'] > 0?$_REQUEST['socid']:'false') .'";';

$jqgridJs .= <<<EOF

$.datepicker.setDefaults($.datepicker.regional['fr']);


jQuery.jgrid.edit.msg.minValue="Ce champs est requis";
$(document).ready(function() {
    var extra = "";
    if (socId) extra = "&socid="+socId;
var grid = $("#gridListContrat").jqGrid({
            datatype: "json",
            url: "ajax/listContrat_json.php?userId="+userId+extra,
            colNames:['id',"Ref", "Tiers",'Date Contrat','Type','Etat des services (brouillon / en cours / ferm&eacute;)'],
            colModel:[  {name:'id',index:'cid', width:0, hidden:true,key:true,hidedlg:true,search:false},
                        {
                            name:'ref',
                            index:'ref',
                            width:80,
                            align:"left",
                            editable:false,
                            searchoptions:{
                                sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]
                            },
                            formoptions:{
                                elmprefix:"*  "
                            }
                        },
                        {
                            name:'Tiers',
                            index:'socname',
                            align:"left",
                            editable:true,
                            stype: 'select',
                            edittype: 'select',
                            editable: false,
                            searchoptions:{sopt:['eq','ne']},
                            editoptions: {
                                value: socRess,
                            },formoptions:{ elmprefix:"*  " }
                        },
                        {
                            name:'date_contrat',
                            index:'date_contrat',
                            width:90,
                            align:"center",
                            sorttype:"date",
                            formatter:'date',
                            formatoptions:{srcformat:"Y-m-d",newformat:"d/m/Y"},
                            editable:false,formoptions:{ elmprefix:"   " },
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
                            name:'type',
                            index:'type',
                            width:80,
                            align:"left",
                            editable:false,
                            editable: false,
                            searchoptions:{sopt:['eq','ne']},
                            editoptions: {
                                value: contratTypeRess,
                            },
                            formoptions:{ elmprefix:"*  " }
                        },

                        {
                            name:'picto',
                            index:'picto',
                            width:200,
                            align:"center",
                            search: false,
                            sortable: false,
                            formoptions:{ elmprefix:"   " },
                        },
                      ],
            rowNum:30,
            rowList:[30,50,100],
            imgpath: gridimgpath,
            pager: jQuery('#gridListContratPager'),
            sortname: 'cid',
            mtype: "POST",
            viewrecords: true,
            width: "900",
            height: 500,
            sortorder: "desc",
            //multiselect: true,
            caption: "<span style='padding:4px; font-size: 13px; '>Contrats</span>",
            viewsortcols: true,
EOF;

    $jqgridJs.= '    }).navGrid("#gridListContratPager",'."\n";
    $jqgridJs.= '                   { view:false, add:false,'."\n";
    $jqgridJs.= '                     del:false,'."\n";
    $jqgridJs.= '                     edit:false,'."\n";
    $jqgridJs.= '                     search:true,'."\n";
    $jqgridJs.= '                     position:"left"'."\n";
    $jqgridJs.= '                     });'."\n";


$jqgridJs.= '   });'."\n";
$js .= $jqgridJs."</script>";

//llxHeader();
llxHeader($js,$langs->trans("Liste des contrats"),"Contrats","1");

print "<br/>";
print "<br/>";
print "<br/>";
print '<table id="gridListContrat" class="scroll ui-widget " cellpadding="0" cellspacing="0"></table>';
print '<div id="gridListContratPager" class="scroll" style="text-align:center;"></div>';

$db->free_result();

$db->close();

llxFooter('$Date: 2008/04/07 17:16:46 $ - $Revision: 1.29 $');
?>
