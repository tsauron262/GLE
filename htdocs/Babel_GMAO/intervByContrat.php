<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 19 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : intervByContrat.php
  * GLE-1.2
  */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php');
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_GMAO/SAV.class.php");
require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");

$langs->load("contracts");

// Security check
$contratid = isset($_REQUEST["id"])?$_REQUEST["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat',$contratid,'');
    $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
    $jsMainpath = DOL_URL_ROOT."/Synopsis_Common/js";
    $jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
    $css = DOL_URL_ROOT."/Synopsis_Common/css";
    $imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

    $js .= ' <script src="'.$jspath.'/jqGrid-3.5/src/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-3.5/jquery.jqGrid.min.js" type="text/javascript"></script>';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';


/*
* View
*/

llxHeader($js,utf8_decode('Détails interventions'),'1');

$contrat = $contrat=getContratObj($_REQUEST["id"]);
$contrat->fetch($_GET["id"]);
$contrat->info($_GET["id"]);

$head = contract_prepare_head($contrat);
$head = $contrat->getExtraHeadTab($head);


dol_fiche_head($head, "Interv", $langs->trans("Contract"));


//jqGrid SAV

print '<table id="list1"></table> <div id="pager1"></div>';

$arrResByDate = array();
$arrResByDateDur = array();
$arrResByDateTotHT = array();
$arrResByStatut = array();
$arrResByStatutDur = array();
$arrResByStatutTotHT = array();
$arrRemDate = array();

$requete = "SELECT *
              FROM ".MAIN_DB_PREFIX."fichinter
             WHERE fk_contrat = ".$contratid;
$sql = $db->query($requete);

$totalSAV = 0;
while ($res=$db->fetch_object($sql))
{
    if ($arrResByStatut[$res->fk_statut]."x" == "x"){
        $arrResByStatut[$res->fk_statut] = 0;
    }
    $arrResByStatut[$res->fk_statut]++;
    $arrResByStatutDur[$res->fk_statut] += $res->duree;
    $arrResByStatutTotHT[$res->fk_statut] += $res->total_ht;


    if (date('U',strtotime($res->datei)) > 0 && $arrResByDate[date('Ym',strtotime($res->datei))]."x" == "x"){
        $arrResByDate[date('Ym',strtotime($res->datei))] = 0;
    }
    if (date('U',strtotime($res->datei)) > 0){
        $arrResByDate[date('Ym',strtotime($res->datei))]++;
        $arrRemDate[date('Ym',strtotime($res->datei))] = date('m/Y',strtotime($res->datei));
        $arrResByDateDur[date('Ym',strtotime($res->datei))] += $res->duree;
        $arrResByDateTotHT[date('Ym',strtotime($res->datei))] += $res->total_ht;
    }
    $totalSAV++;
}
print "<div class='titre'>Total</div>";
print "<h2>Intervention : ".$totalSAV."</h2>";
print "<div id='tab'>";
print "<ul>";
print " <li><a href='#Statut'><span>Par statut</span></a></li>";
print " <li><a href='#Date'><span>Par date</span></a></li>";
print " <li><a href='#type'><span>Par type d'intervention</span></a></li>";
print "</ul>";
print " <div id='Statut'>";
print "<table cellpadding=15 width=450>";

$objSav = new fichinter($db);
print "<tr><th class='ui-widget-header ui-state-hover'>Statut";
print "    <th class='ui-widget-header ui-state-hover'>Total interv.";
print "    <th class='ui-widget-header ui-state-hover'>Total dur&eacute;e";
print "    <th class='ui-widget-header ui-state-hover'>Total HT";
foreach($arrResByStatut as $key=>$val)
{
    print "<tr><th align=left class='ui-widget-header ui-state-default'>".$objSav->LibStatut($key,4);
    print "<td align=center class='ui-widget-content'>".$val;
    print "<td align=center class='ui-widget-content'>".sec2time($arrResByStatutDur[$key]);
    print "<td align=center class='ui-widget-content'>".price($arrResByStatutTotHT[$key])." &euro;";
}
print "</table>";
print '</div>';

print " <div id='Date'>";
print "<table cellpadding=15 width=450>";
print "<tr><th class='ui-widget-header ui-state-hover'>Mois";
print "    <th class='ui-widget-header ui-state-hover'>Total";
print "    <th class='ui-widget-header ui-state-hover'>Total dur&eacute;e";
print "    <th class='ui-widget-header ui-state-hover'>Total HT";
foreach($arrResByDate as $key=>$val)
{
    print "<tr><th align=left class='ui-widget-header ui-state-default'>".$arrRemDate[$key];
    print "    <td class='ui-widget-content' align=center>".$val;
    print "    <td align=center class='ui-widget-content'>".sec2time($arrResByDateDur[$key]);
    print "    <td align=center class='ui-widget-content'>".price($arrResByDateTotHT[$key])." &euro;";
}
print "</table>";
print '</div>';

$requete = "SELECT b.label, fd.duree, fd.total_ht, fd.fk_typeinterv
              FROM ".MAIN_DB_PREFIX."fichinterdet as fd,
                   ".MAIN_DB_PREFIX."fichinter as f,
                   llx_Synopsis_fichinter_c_typeInterv as b
             WHERE fd.fk_fichinter = f.rowid
               AND b.id = fd.fk_typeinterv
               AND f.fk_contrat = ". $contratid."
          ORDER BY b.rang";
$sql = $db->query($requete);
$arrResByTypeInterv=array();
$arrLabelInterv=array();
while($res = $db->fetch_object($sql))
{
    $arrResByTypeInterv[$res->fk_typeinterv]["duree"]+=$res->duree;
    $arrResByTypeInterv[$res->fk_typeinterv]["total_ht"]+=$res->total_ht;
    $arrLabelInterv[$res->fk_typeinterv]=$res->label;
}


print " <div id='type'>";
print "<table cellpadding=15 width=450>";
print "<tr><th class='ui-widget-header ui-state-hover'>Type";
print "    <th class='ui-widget-header ui-state-hover'>Total dur&eacute;e";
print "    <th class='ui-widget-header ui-state-hover'>Total HT";
foreach($arrResByTypeInterv as $key=>$val)
{
    print "<tr><th align=left class='ui-widget-header ui-state-default'>".$arrLabelInterv[$key];
    print "    <td align=center class='ui-widget-content'>".sec2time($arrResByTypeInterv[$key]["duree"]);
    print "    <td align=center class='ui-widget-content'>".price($arrResByTypeInterv[$key]["total_ht"])." &euro;";
}
print "</table>";
print '</div>';


print '</div>';

print '</div>';


$db->close();
print '<script>';
print 'var contratId='.$contratid.';';
print <<<EOF
jQuery(document).ready(function(){
    jQuery('#tab').tabs({
        spinner: "Chargement",
        cache: true,
        fx: { opacity: "toggle" }
    });

    jQuery('#list1').jqGrid({
        url:'ajax/intByContrat-json_response.php?id='+contratId,
        datatype: 'json',
        colNames:['rowid','Description','Date intervention', 'total_ht','Dur&eacute;e totale', 'Fiche intervention',"statut"],
        colModel:[ {name:'rowid',index:'rowid', width:55, hidden: true,hidedlg: true, search: false},
                   {name:'description',index:'description', align: 'left'},
                   {name:'datei',index:'datei', width:40,
                            align:'center',
                            sorttype:'date',
                            formatter:'date',
                            formatoptions:{srcformat:'Y-m-d',newformat:'d/m/Y'},
                            editable:false,
                            searchoptions:{
                                dataInit:function(el){
                                    jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
                                    jQuery(el).datepicker({
                                        regional: 'fr',
                                        changeMonth: true,
                                        changeYear: true,
                                        showButtonPanel: true,
                                        constrainInput: true,
                                        gotoCurrent: true,
                                        dateFormat: 'dd/mm/yy',
                                    });
                                    jQuery('#ui-datepicker-div').addClass('promoteZ');
                                },
                                sopt:['eq','ne','le','lt','ge','gt'],
                            },
                    },
                   {name:'total_ht',index:'total_ht', width:80, align: 'right'},
                   {name:'duree',index:'duree', width:80, align: 'center'},
                   {name:'rowid',index:'rowid', width:80, align: 'center'},
                   {name:'fk_statut',index:'fk_statut', width:80, align: 'center'},
                 ],
        rowNum:10,
        rowList:[10,30,50],
        width: 900,
        height: 300,
        pager: '#pager1',
        sortname: 'datei',
        beforeRequest: function(){
            jQuery('.fiche').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
        },
        viewrecords: true,
        sortorder: 'DESC',
        caption:'Historique contrat',
        subGrid: true,
        subGridUrl:'ajax/intByContrat-json_response.php?action=subGrid',
        subGridRowExpanded: function(subgrid_id, row_id) {
            var subgrid_table_id;
            subgrid_table_id = subgrid_id+"_t";
            jQuery("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
            jQuery("#"+subgrid_table_id).jqGrid({
                  width: 820,
                  url: 'ajax/intByContrat-json_response.php?action=subGrid&id='+row_id,
                  //url:"subgrid.php?q=2&id="+row_id,
                  datatype: "json",
                  colNames: ['Id','Description','Date','Dur&eacute;e','Type',"Total HT"],
                  colModel: [

                    {name:"id",index:"id",width:55,key:true,hidden:true},
                    {name:"description",index:"description",width:195},
                    {name:'date',index:'date', width:140,
                            align:'center',
                            sorttype:'date',
                            formatter:'date',
                            formatoptions:{srcformat:'Y-m-d',newformat:'d/m/Y'},
                            editable:false,
                            searchoptions:{
                                dataInit:function(el){
                                    jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
                                    jQuery(el).datepicker({
                                        regional: 'fr',
                                        changeMonth: true,
                                        changeYear: true,
                                        showButtonPanel: true,
                                        constrainInput: true,
                                        gotoCurrent: true,
                                        dateFormat: 'dd/mm/yy',
                                    });
                                    jQuery('#ui-datepicker-div').addClass('promoteZ');
                                },
                                sopt:['eq','ne','le','lt','ge','gt'],
                            },
                    },
                    {name:'duree',index:'duree', width:140,
                            align:'center',
                            searchoptions:{
                                sopt:['eq','ne','le','lt','ge','gt'],
                            },
                    },
                    {name:"type",align:"center", index:"type",width:100},
                    {name:"total_ht",align:"right",index:"total_ht",width:100 },
                    ],
                  height: "100%",
                  rowNum:20,
                  sortname: 'fd.rang',
                  sortorder: "desc",
            });
        }

    });
    jQuery('#list1').jqGrid('navGrid','#pager1',{edit:false,add:false,del:false,search:true});

});
</script>
EOF;
llxFooter('$Date: 2008/03/01 01:26:47 $ - $Revision: 1.16 $');


function sec2time($sec){
    $returnstring = " ";
    $days = intval($sec/86400);
    $hours = intval ( ($sec/3600) - ($days*24));
    $minutes = intval( ($sec - (($days*86400)+ ($hours*3600)))/60);
    $seconds = $sec - ( ($days*86400)+($hours*3600)+($minutes * 60));

    $returnstring .= ($days)?(($days == 1)? "1 j":$days."j"):"";
    $returnstring .= ($days && $hours && !$minutes && !$seconds)?"":"";
    $returnstring .= ($hours)?( ($hours == 1)?" 1h":" " .$hours."h"):"";
    $returnstring .= (($days || $hours) && ($minutes && !$seconds))?"  ":" ";
    $returnstring .= ($minutes)?( ($minutes == 1)?" 1 min":" ".$minutes."min"):"";
    //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
    //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
    return ($returnstring);
}

?>