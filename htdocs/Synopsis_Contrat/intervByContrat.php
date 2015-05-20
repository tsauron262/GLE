<?php

/*
 * * GLE by Synopsis et DRSI
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
require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php');
require_once(DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT . "/Babel_GMAO/SAV.class.php");
require_once(DOL_DOCUMENT_ROOT . "/fichinter/class/fichinter.class.php");

$langs->load("contracts");

// Security check
$contratid = isset($_REQUEST["id"]) ? $_REQUEST["id"] : '';
if ($user->societe_id)
    $socid = $user->societe_id;
restrictedArea($user, 'contrat', $contratid, '');
$jspath = DOL_URL_ROOT . "/Synopsis_Common/jquery";
$jsMainpath = DOL_URL_ROOT . "/Synopsis_Common/js";
$jqueryuipath = DOL_URL_ROOT . "/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT . "/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT . "/Synopsis_Common/images";

$js = ' <script src="' . $jspath . '/jqGrid-3.5/src/i18n/grid.locale-fr.js" type="text/javascript"></script>';
$js .= ' <script src="' . $jspath . '/jqGrid-3.5/jquery.jqGrid.min.js" type="text/javascript"></script>';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="' . $jspath . '/jqGrid-3.5/css/ui.jqgrid.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="' . $jspath . '/jqGrid-3.5/css/jquery.searchFilter.css" />';


/*
 * View
 */

llxHeader($js, 'Détails interventions');

$contrat = getContratObj($_REQUEST["id"]);
$contrat->fetch($_GET["id"]);
$contrat->info($_GET["id"]);

$head = contract_prepare_head($contrat);
//$head = $contrat->getExtraHeadTab($head);


dol_fiche_head($head, "interv", $langs->trans("Contract"));


//jqGrid SAV


$arrResByDate = array();
$arrResByDateDur = array();
$arrResByDateTotHT = array();
$arrResByStatut = array();
$arrResByStatutDur = array();
$arrResByStatutTotHT = array();
$arrRemDate = array();


$result = array();
for ($i = 0; $i < 2; $i++) {
    if ($i == 0)
        $table = "Synopsis_fichinter";
    else
        $table = "synopsisdemandeinterv";
    $requete = "SELECT *
              FROM " . MAIN_DB_PREFIX . "" . $table . "
             WHERE fk_contrat = " . $contratid;
    $sql = $db->query($requete);

    $totalSAV = 0;
    $total_ht = 0;
    $html = "";
    $arrResByStatut = $arrResByStatut = $arrResByStatutTotHT = $arrResByDate = $arrResByDateDur = $arrResByDateTotHT = array();
    while ($res = $db->fetch_object($sql)) {
        $anneeMoi = date('Ym', strtotime($res->datei));
        if (!isset($arrResByStatut[$res->fk_statut])) {
            $arrResByStatut[$res->fk_statut] = 0;
            $arrResByStatutDur[$res->fk_statut] = 0;
            $arrResByStatutTotHT[$res->fk_statut] = 0;
        }
        if (!isset($arrResByDate[$anneeMoi])) {
            $arrResByDate[$anneeMoi] = 0;
            $arrResByDateDur[$anneeMoi] = 0;
            $arrResByDateTotHT[$anneeMoi] = 0;
        }
        $arrResByStatut[$res->fk_statut] ++;
        $arrResByStatutDur[$res->fk_statut] += $res->duree;
        $arrResByStatutTotHT[$res->fk_statut] += $res->total_ht;

        if (date('U', strtotime($res->datei)) > 0) {
            $arrResByDate[$anneeMoi] ++;
            $arrRemDate[$anneeMoi] = date('m/Y', strtotime($res->datei));
            $arrResByDateDur[$anneeMoi] += $res->duree;
            $arrResByDateTotHT[$anneeMoi] += $res->total_ht;
        }
        $totalSAV++;
        $total_ht += $res->total_ht;
    }



    $html .= "<h2>" . ($i == 0 ? "Intervention" : "Demande") . " : " . $totalSAV . "</h2>";
    $html .= "<div id='tab" . $i . "'>";
    $html .= "<ul>";
    $html .= " <li><a href='#Statut" . $i . "'><span>Par statut</span></a></li>";
    $html .= " <li><a href='#Date" . $i . "'><span>Par date</span></a></li>";
    $html .= " <li><a href='#type" . $i . "'><span>Par type d'intervention</span></a></li>";
    $html .= " <li><a href='#Tech" . $i . "'><span>Par tech</span></a></li>";
    $html .= "</ul>";
    $html .= " <div id='Statut" . $i . "'>";
    $html .= "<table cellpadding=15 width=450>";

    $objSav = new fichinter($db);
    $html .= "<tr><th class='ui-widget-header ui-state-hover'>Statut";
    $html .= "    <th class='ui-widget-header ui-state-hover'>Total interv.";
    $html .= "    <th class='ui-widget-header ui-state-hover'>Total dur&eacute;e";
    $html .= "    <th class='ui-widget-header ui-state-hover'>Total HT";
    foreach ($arrResByStatut as $key => $val) {
        $html .= "<tr><th align=left class='ui-widget-header ui-state-default'>" . $objSav->LibStatut($key, 4);
        $html .= "<td align=center class='ui-widget-content'>" . $val;
        $html .= "<td align=center class='ui-widget-content'>" . sec2time($arrResByStatutDur[$key]);
        $html .= "<td align=center class='ui-widget-content'>" . price($arrResByStatutTotHT[$key]) . " &euro;";
    }
    $html .= "</table>";
    $html .= '</div>';

    $html .= " <div id='Date" . $i . "'>";
    $html .= "<table cellpadding=15 width=450>";
    $html .= "<tr><th class='ui-widget-header ui-state-hover'>Mois";
    $html .= "    <th class='ui-widget-header ui-state-hover'>Total";
    $html .= "    <th class='ui-widget-header ui-state-hover'>Total dur&eacute;e";
    $html .= "    <th class='ui-widget-header ui-state-hover'>Total HT";
    foreach ($arrResByDate as $key => $val) {
        $html .= "<tr><th align=left class='ui-widget-header ui-state-default'>" . $arrRemDate[$key];
        $html .= "    <td class='ui-widget-content' align=center>" . $val;
        $html .= "    <td align=center class='ui-widget-content'>" . sec2time($arrResByDateDur[$key]);
        $html .= "    <td align=center class='ui-widget-content'>" . price($arrResByDateTotHT[$key]) . " &euro;";
    }
    $html .= "</table>";
    $html .= '</div>';


    $requete = "SELECT b.label, fd.duree, fd.total_ht, fd.fk_typeinterv
              FROM " . MAIN_DB_PREFIX . "" . $table . "det as fd,
                   " . MAIN_DB_PREFIX . "" . $table . " as f,
                   " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv as b
             WHERE fd.fk_" . ($table == "Synopsis_fichinter" ? "fichinter" : $table) . " = f.rowid
               AND b.id = fd.fk_typeinterv
               AND f.fk_contrat = " . $contratid . "
          ORDER BY b.rang";
    $sql = $db->query($requete);
    $arrResByTypeInterv = array();
    $arrLabelInterv = array();
    while ($res = $db->fetch_object($sql)) {
        if (!isset($arrResByTypeInterv[$res->fk_typeinterv])) {
            $arrResByTypeInterv[$res->fk_typeinterv] = array("duree" => 0, "total_ht" => 0);
        }
        $arrResByTypeInterv[$res->fk_typeinterv]["duree"]+=$res->duree;
        $arrResByTypeInterv[$res->fk_typeinterv]["total_ht"]+=$res->total_ht;
        $arrLabelInterv[$res->fk_typeinterv] = $res->label;
    }


    $html .= " <div id='type" . $i . "'>";
    $html .= "<table cellpadding=15 width=450>";
    $html .= "<tr><th class='ui-widget-header ui-state-hover'>Type";
    $html .= "    <th class='ui-widget-header ui-state-hover'>Total dur&eacute;e";
    $html .= "    <th class='ui-widget-header ui-state-hover'>Total HT";
    foreach ($arrResByTypeInterv as $key => $val) {
        $html .= "<tr><th align=left class='ui-widget-header ui-state-default'>" . $arrLabelInterv[$key];
        $html .= "    <td align=center class='ui-widget-content'>" . sec2time($arrResByTypeInterv[$key]["duree"]);
        $html .= "    <td align=center class='ui-widget-content'>" . price($arrResByTypeInterv[$key]["total_ht"]) . " &euro;";
    }
    $html .= "</table>";
    $html .= '</div>';





    $requete = "SELECT b.lastname, b.firstname, fd.duree, fd.total_ht, f.fk_user_author
              FROM " . MAIN_DB_PREFIX . "" . $table . "det as fd,
                   " . MAIN_DB_PREFIX . "" . $table . " as f,
                   " . MAIN_DB_PREFIX . "user as b
             WHERE fd.fk_" . ($table == "Synopsis_fichinter" ? "fichinter" : $table) . " = f.rowid
               AND b.rowid = f.fk_user_author
               AND f.fk_contrat = " . $contratid . "
          ORDER BY b.firstname";
    $sql = $db->query($requete);
    $arrResByTypeInterv = array();
    $arrLabelInterv = array();
    while ($res = $db->fetch_object($sql)) {
        if (!isset($arrResByTypeInterv[$res->fk_user_author])) {
            $arrResByTypeInterv[$res->fk_user_author] = array("duree" => 0, "total_ht" => 0);
        }
        $arrResByTypeInterv[$res->fk_user_author]["duree"]+=$res->duree;
        $arrResByTypeInterv[$res->fk_user_author]["total_ht"]+=$res->total_ht;
        $arrLabelInterv[$res->fk_user_author] = $res->firstname . " " . $res->lastname;
    }


    $html .= " <div id='Tech" . $i . "'>";
    $html .= "<table cellpadding=15 width=450>";
    $html .= "<tr><th class='ui-widget-header ui-state-hover'>Type";
    $html .= "    <th class='ui-widget-header ui-state-hover'>Total dur&eacute;e";
    $html .= "    <th class='ui-widget-header ui-state-hover'>Total HT";
    foreach ($arrResByTypeInterv as $key => $val) {
        $html .= "<tr><th align=left class='ui-widget-header ui-state-default'>" . $arrLabelInterv[$key];
        $html .= "    <td align=center class='ui-widget-content'>" . sec2time($arrResByTypeInterv[$key]["duree"]);
        $html .= "    <td align=center class='ui-widget-content'>" . price($arrResByTypeInterv[$key]["total_ht"]) . " &euro;";
    }
    $html .= "</table>";
    $html .= '</div>';




    $html .= '</div>';
    $result[$i]['total'] = $totalSAV;
    $result[$i]['html'] = $html;
    $result[$i]['total_ht'] = $total_ht;
}


$requete = "SELECT *
              FROM " . MAIN_DB_PREFIX . "synopsisdemandeinterv
             WHERE fk_contrat = " . $contratid;

$contrat->fetch_lines();

$nbDepDeb = date_diff(date_create($db->idate($contrat->date_contrat)), date_create())->days;
if ($contrat->total_ht > 0) {
    $pourc = $result[0]['total_ht'] / $contrat->total_ht * 100;
    if ($nbDepDeb > 0) {
        $pourcPro = $pourc * 365 / $nbDepDeb;
    } else {
        $pourcPro = "N/C";
    }
} else {
    $pourc = "N/C";
    $pourcPro = "N/C";
}
print "<table class='border' width='100%'><tr><td> Date contrat <td>" . dol_print_date($contrat->date_contrat) . "
    <td>Vendue <td> " . $contrat->total_ht . " &euro;
    <tr><td>Prevue <td> " . $result[1]['total_ht'] . " &euro;";
print "<td>Réalisé <td " . ($pourcPro > 100 ? "style='color:red'" : "") . "> " . price($result[0]['total_ht']) . " &euro; (" . price($pourc) . " %) Prorata : " . price($pourcPro) . " %</tr>";


print '<tr>';
//$sql = $db->query("SELECT c.* FROM `" . MAIN_DB_PREFIX . "synopsischrono` c, `" . MAIN_DB_PREFIX . "synopsischrono_value` cv WHERE `key_id` = 1037 AND c.model_refid = 100 AND cv.chrono_refid = c.id AND cv.value= " . $contrat->id . $val['Sup'] . " GROUP by c.id");
//$i = 0;
//while ($result = $db->fetch_object($sql)) {
//    $i++;
//    $tabId [] = $result->id;
//}
$tabDiff = array(array("Titre" => "Appel Total", "Sup" => ""),
    array("Titre" => "Appel 1 An", "Sup" => " AND DATE(Date_H_Debut) > DATE_ADD(NOW(), INTERVAL -1 YEAR)"));
foreach ($tabDiff as $val) {
    $duree = 0;
    $i = 0;
//    if ($i > 0) {
        $sql2 = $db->query("SELECT SUM(TIMEDIFF(Date_H_Fin, Date_H_Debut)) as sum, count(id) as nb
 FROM " . MAIN_DB_PREFIX . "synopsischrono_chrono_100 WHERE Contrat = ".$contrat->id . $val['Sup'] ." AND Date_H_Fin > 0 AND Date_H_Debut > 0 GROUP BY Contrat");
        while ($result2 = $db->fetch_object($sql2)){
            $duree += duree_to_secondes($result2->sum);
            $i = $result2->nb;
//            $dateT = new DateTime($result2->sum);
//            echo $dateT.day;
//        }
    }
    $duree = sec2time($duree);
    print "<td>" . $val['Titre'] . "</td><td>" . $i . " (" . $duree . ")</td>";
}

print "</tr>";

print "</table><br/><br/>";



print "<div id='tabs'>";
print "<ul>";
print "<li><a href='#DI'>Demande Intervention</a></li>";
print "<li><a href='#FI'>Fiche Intervention</a></li>";
print "</ul>";
print "<div id='FI'>";
print '<table id="list1"></table> <div id="pager1"></div>';
print $result[0]['html'];
//Drag Drop
print "</div>";
print "<div id='DI'>";
print '<table id="list2"></table> <div id="pager2"></div>';
print $result[1]['html'];
print "</div>";
print "</div>";




print '</div>';


$db->close();
print '<script>';
print 'var contratId=' . $contratid . ';';
print <<<EOF
jQuery(document).ready(function(){
    jQuery('#tab0').tabs({
        spinner: "Chargement",
        cache: true,
        fx: { opacity: "toggle" }
    });
    jQuery('#tab1').tabs({
        spinner: "Chargement",
        cache: true,
        fx: { opacity: "toggle" }
    });
    
    jQuery('#tabs').tabs({
        cache: true,
        spinner: 'Chargement ...',
        fx: {opacity: 'toggle' }
    });

    jQuery('#list1').jqGrid({
        url:'ajax/intByContrat-json_response.php?type=FI&id='+contratId,
        datatype: 'json',
        colNames:['rowid','Description','Date intervention', 'total_ht','Dur&eacute;e totale', 'Fiche intervention', 'Tech',"statut"],
        colModel:[ {name:'rowid',index:'rowid', width:55, hidden: true,hidedlg: true, search: false},
                   {name:'description',index:'description', align: 'left'},
                   {name:'datei',index:'datei', width:60,
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
        caption:'Fiche Intervention Contrat',
        subGrid: true,
        subGridUrl:'ajax/intByContrat-json_response.php?type=FI&action=subGrid',
        subGridRowExpanded: function(subgrid_id, row_id) {
            var subgrid_table_id;
            subgrid_table_id = subgrid_id+"_t";
            jQuery("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
            jQuery("#"+subgrid_table_id).jqGrid({
                  width: 820,
                  url: 'ajax/intByContrat-json_response.php?type=FI&action=subGrid&id='+row_id,
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
    



jQuery('#list2').jqGrid({
        url:'ajax/intByContrat-json_response.php?type=DI&id='+contratId,
        datatype: 'json',
        colNames:['rowid','Description','Date intervention', 'total_ht','Dur&eacute;e totale', 'Demande intervention', 'Fiche intervention', 'Tech',"Statut"],
        colModel:[ {name:'rowid',index:'rowid', width:55, hidden: true,hidedlg: true, search: false},
                   {name:'description',index:'description', align: 'left'},
                   {name:'datei',index:'datei', width:60,
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
                   {name:'rowid',index:'rowid', width:80, align: 'center'},
                   {name:'fk_user_prisencharge',index:'fk_user_prisencharge', width:80, align: 'center'},
                   {name:'fk_statut',index:'fk_statut', width:80, align: 'center'},
                 ],
        rowNum:10,
        rowList:[10,30,50],
        width: 900,
        height: 300,
        pager: '#pager2',
        sortname: 'datei',
        beforeRequest: function(){
            jQuery('.fiche').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
        },
        viewrecords: true,
        sortorder: 'DESC',
        caption:'Demande Intervention Contrat',
        subGrid: true,
        subGridUrl:'ajax/intByContrat-json_response.php?type=DI&action=subGrid',
        subGridRowExpanded: function(subgrid_id, row_id) {
            var subgrid_table_id;
            subgrid_table_id = subgrid_id+"_t";
            jQuery("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
            jQuery("#"+subgrid_table_id).jqGrid({
                  width: 820,
                  url: 'ajax/intByContrat-json_response.php?type=DI&action=subGrid&id='+row_id,
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
    jQuery('#list2').jqGrid('navGrid','#pager2',{edit:false,add:false,del:false,search:true});

});
</script>
EOF;
llxFooter('$Date: 2008/03/01 01:26:47 $ - $Revision: 1.16 $');

function sec2time($sec) {
    $returnstring = " ";
    $days = intval($sec / 86400);
    $hours = intval(($sec / 3600) - ($days * 24));
    $minutes = intval(($sec - (($days * 86400) + ($hours * 3600))) / 60);
    $seconds = $sec - ( ($days * 86400) + ($hours * 3600) + ($minutes * 60));

    $returnstring .= ($days) ? (($days == 1) ? "1 j" : $days . "j") : "";
    $returnstring .= ($days && $hours && !$minutes && !$seconds) ? "" : "";
    $returnstring .= ($hours) ? ( ($hours == 1) ? " 1h" : " " . $hours . "h") : "";
    $returnstring .= (($days || $hours) && ($minutes && !$seconds)) ? "  " : " ";
    $returnstring .= ($minutes) ? ( ($minutes == 1) ? " 1 min" : " " . $minutes . "min") : "";
    //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
    //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
    return ($returnstring);
}






function duree_to_secondes($duree){
	$array_duree=explode(":",$duree);
        if(isset($array_duree[2]))
	$secondes=3600*$array_duree[0]+60*$array_duree[1]+$array_duree[2];
        elseif(isset($array_duree[1]))
	$secondes=60*$array_duree[0]+$array_duree[1];
        else
            $secondes = $duree;
	return $secondes;
}
?>