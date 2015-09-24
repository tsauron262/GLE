<?php

/*
 *
  /**
 *
 * Name : calendar_html-repsonse.php
 * GLE-1.1
 */

require_once('../../main.inc.php');

$projectId = $_REQUEST["projId"];

$jspath = DOL_URL_ROOT . "/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT . "/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT . "/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT . "/Synopsis_Common/images";


$js .= '      <link type="text/css" rel="stylesheet" href="' . DOL_URL_ROOT . '/Synopsis_Common/css/jquery.treeview.css" />' . "\n";


$js .= '<link rel="stylesheet" type="text/css" media="screen" href="' . $jspath . '/jqGrid-3.5/css/ui.jqgrid.css" />' . "\n";
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="' . $jspath . '/jqGrid-3.5/css/jquery.searchFilter.css" />' . "\n";
$js .= ' <script src="' . $jspath . '/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
$js .= ' <script src="' . $jspath . '/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';

$js .= "<style type='text/css'>body { position: static; }</style>" . "\n";


$jqgridJs = "<script type='text/javascript'>" . "\n";
$jqgridJs .= 'var gridimgpath="' . $imgPath . '/images/";';
$jqgridJs .= 'var userId="' . $user->id . '";';
$jqgridJs .= 'var socId="' . $soc->id . '"; ';
$jqgridJs .= 'var campId="' . $campagneId . '"; ';
$jqgridJs .= 'var DOL_URL_ROOT="' . DOL_URL_ROOT . '"; ';
$jqgridJs .= "var userincomp='" . $jsEditopts["userincomp"] . "'\n";
$jqgridJs .= "var catRess='" . $jsEditopts["catRess"] . "'\n";
$jqgridJs .= "var projId='" . $_REQUEST['projet_id'] . "'\n";
$jqgridJs .="</script>";

$js.="<style>.today{ background-color: #EEEEF3; color: #0000FF; } .weekend { background-color: #C1C1C1; color: #FFFFFF;} .ferie { background-color: #00C100; color: #FFFFFF;} th {width:150px;} .ui-jqgrid-titlebar-close { display: none; }</style>";

$js .= $jqgridJs;
print $js;
//    top_menu($js,$langs->trans("Ressources"), "",1,false);
print $js;

//require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Hrm/hrm.class.php");
//$hrm = new Hrm($db);

require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
$proj = new Project($db);


//TODO durProj+ group
//affiche les dépenses historiquement
//$proj->getStartDate();
//$proj->getEndDate();

$startDate = time() - (86400 * 200);
$stopDate = time() + (86400 * 100);
$durProj = (abs($stopDate - $startDate) / (86400));


$proj->id = $projectId;
$requete = "SELECT *
              FROM " . MAIN_DB_PREFIX . "projet_task
             WHERE fk_projet = " . $projectId;
$sql = $db->query($requete);
$globalCost = array();
$globalCost['FP'] = array();
$globalCost['R'] = array();
$globalCost['RH'] = array();

$proj->costProjetRessource($conf);
if (is_array($proj->costByDay["projet"][$projectId]["Ressource"]))
    foreach ($proj->costByDay["projet"][$projectId]["Ressource"] as $key => $value) {
        $globalCost['R'][$key]+=floatval($value["totalCostForRessources"]);
    }
$extraCost = 0;
while ($res = $db->fetch_object($sql)) {
    $proj->costTask($res->rowid);
    if (is_array($proj->costByDay["task"][$res->rowid]["Ressource"]))
        foreach ($proj->costByDay["task"][$res->rowid]["Ressource"] as $key => $value) {
            $globalCost['R'][$key]+=floatval($value["totalCostForRessources"]);
        }

    if (is_array($proj->costByDay["task"][$res->rowid]["FraisProjet"]))
        foreach ($proj->costByDay["task"][$res->rowid]["FraisProjet"] as $key => $value) {
            $globalCost['FP'][$key]+=floatval($value);
        }

    //
    if (is_array($proj->costByDay["task"][$res->rowid]["HRessource"]['cost']))
        foreach ($proj->costByDay["task"][$res->rowid]["HRessource"]['cost'] as $key => $value) {
            $globalCost['RH'][$key]+=floatval($value);
        }
    $extraCost += $proj->costByDay["task"][$res->rowid]["HRessource"]['cost'][-1];
//                                    $this->costByDay['task'][$taskId]['HRessource']['cost'][-1] += $cout;
}
//require_once('Var_Dump.php');
//Var_Dump::Display($proj->costByDay["task"]);
print "<div>";
print "<div style='min-width: 700px;'>";
print "<table id='caltable' class='scroll' cellpadding='0' cellspacing='0' style='border-collapse: collapse; '>";
print "</table>";
print '<div id="caltablePager" class="scroll" style="text-align:center;"></div>';
print "</div>";
print "<div style='float:left; position: absolute; top: 150px; left: 1130px;'>";
print "<fieldset><legend>L&eacute;gende</legend>";
print "<table><tbody>";
print "<tr><td><div style='height: 10px; width: 10px; border:1px Solid #0000FF;' class='today' ></div></td><td>Aujourd'hui</td></tr>";
print "<tr><td><div style='height: 10px; width: 10px; border:1px Solid #000000;' class='ferie' ></div></td><td>Jour f&eacute;ri&eacute;</td></tr>";
print "<tr><td><div style='height: 10px; width: 10px; border:1px Solid #000000;' class='weekend' ></div></td><td>Week-End</td></tr>";
print "</tbody></table>";
print "</fieldset>";
print price($extraCost);
print "</div>";
print "</div>";


$cost = array();
$alldata = "";
for ($i = 0; $i < $durProj; $i++) {

    $RHCost = 0;
    $RCost = 0;
    $ECost = 0;

    foreach ($globalCost['R'] as $key => $val) {
        if (date('d', $startDate + $i * (86400)) == date('d', $key)
                && date('m', $startDate + $i * (86400)) == date('m', $key)
                && date('Y', $startDate + $i * (86400)) == date('Y', $key)) {
            $RCost += floatval($val);
        }
    }
    foreach ($globalCost['FP'] as $key => $val) {
        if (date('d', $startDate + $i * (86400)) == date('d', $key)
                && date('m', $startDate + $i * (86400)) == date('m', $key)
                && date('Y', $startDate + $i * (86400)) == date('Y', $key)) {
            $ECost += floatval($val);
        }
    }
    foreach ($globalCost['RH'] as $key => $val) {
        if (date('d', $startDate + $i * (86400)) == date('d', $key)
                && date('m', $startDate + $i * (86400)) == date('m', $key)
                && date('Y', $startDate + $i * (86400)) == date('Y', $key)) {
            $RHCost += floatval($val);
        }
    }
    $TCost = $RHCost + $RCost + $ECost;
    $cost['RH'] += $RHCost;
    $cost['R'] += $RCost;
    $cost['E'] += $ECost;
    $cost['T'] += $TCost;




    $jour = date('N', $startDate + $i * (86400));

    if (isset($arrFerie)) {
        foreach ($arrFerie as $key) {

            if (date('d', $startDate + $i * (86400)) == date('d', $key)
                    && date('m', $startDate + $i * (86400)) == date('m', $key)
                    && date('Y', $startDate + $i * (86400)) == date('Y', $key)) {
                $jour = 8;
            }
        }
    }

    if (date('d', $startDate + $i * (86400)) == date('d', time())
            && date('m', $startDate + $i * (86400)) == date('m', time())
            && date('Y', $startDate + $i * (86400)) == date('Y', time())) {
        $jour = 9;
    }



    $alldata.="<row>";
    $alldata.="<cell>" . $i . "</cell>";
    $alldata.="<cell>" . $jour . "</cell>";
    $alldata.="<cell>" . date('Y-m-d', $startDate + $i * (86400)) . "</cell>";
    $alldata.="<cell>" . preg_replace('/,/', '.', round($RHCost * 100) / 100) . "</cell>";
    $alldata.="<cell>" . preg_replace('/,/', '.', round($RCost * 100) / 100) . "</cell>";
    $alldata.="<cell>" . preg_replace('/,/', '.', round($ECost * 100) / 100) . "</cell>";
    $alldata.="<cell>" . preg_replace('/,/', '.', round($TCost * 100) / 100) . "</cell>";
    $alldata.="</row>";
}

print <<<EOF
<script>
var mydata = "<?xml version='1.0' encoding='utf-8'?> \
<calendar><rows>
EOF;
print $alldata;
print <<<EOF
    </rows></calendar>";
</script>
EOF;

print "<script>";
print 'var gridimgpath="' . $imgPath . '/images/";';

print <<<EOF
    $.datepicker.setDefaults($.datepicker.regional['fr'])

jQuery("#caltable").jqGrid({
        datatype: "xmlstring",
        datastr: mydata,
        height: 400,
        width: 900,
        colNames:['Id','Jour','Date', 'Co&ucirc;t RH (&euro;)', 'Co&ucirc;t Ressoure (&euro;)','Frais de projet (&euro;)','Total (&euro;)',],
        colModel:[ {name:'id',index:'id', width:0, sorttype:"int",  hidden:true,key:true,hidedlg:true,search:false},
                   {name:'jour',index:'jour', width:0, sorttype:"int",  hidden:true,hidedlg:true,search:false},
                   {name:'date',index:'date',align:"center", width:90, sorttype:"date", formatter:'date',formatoptions:{srcformat:"Y-m-d",newformat:"d/m/Y"},datefmt:'d/m/Y' ,searchoptions:{
                                    dataInit:function(el){
                                        $(el).datepicker($.datepicker.regional['fr']);
                                        $("#ui-datepicker-div").addClass("promoteZ");
                                        //$("#ui-timepicker-div").addClass("promoteZ");
                                    },
                                    sopt:['eq','ne',"le",'lt',"ge","gt"],
                                },
                   },
                   {name:'RHCost',index:'RHCost',align:"center", formatter: "currency",width:100,searchoptions:{sopt:['eq','ne',"le",'lt',"ge","gt"]},},
                   {name:'RCost',index:'RCost', align:"center",formatter: "currency",width:80,sorttype:"float",searchoptions:{sopt:['eq','ne',"le",'lt',"ge","gt"]},},
                   {name:'ECost',index:'ECost', align:"center",formatter: "currency",width:80,sorttype:"float",searchoptions:{sopt:['eq','ne',"le",'lt',"ge","gt"]},},
                   {name:'Tot',index:'Tot', align:"center", formatter: "currency",width:80,sorttype:"float",searchoptions:{sopt:['eq','ne',"le",'lt',"ge","gt"]},},
                 ],
       xmlReader: {
          root:"calendar",
          row:"row",
          id : "id"
      },
      afterInsertRow: function(rowid, rowdata,rowelem)
      {
        var jour = rowdata.jour;
        if (jour == 7 || jour == 6)
        {
            jQuery('#caltable').setCell(rowid,0,"","weekend");
            jQuery('#caltable').setCell(rowid,1,"","weekend");
            jQuery('#caltable').setCell(rowid,2,"","weekend");
            jQuery('#caltable').setCell(rowid,3,"","weekend");
            jQuery('#caltable').setCell(rowid,4,"","weekend");
            jQuery('#caltable').setCell(rowid,5,"","weekend");
            jQuery('#caltable').setCell(rowid,6,"","weekend");
        } else if (jour == 8)
        {
            jQuery('#caltable').setCell(rowid,0,"","ferie");
            jQuery('#caltable').setCell(rowid,1,"","ferie");
            jQuery('#caltable').setCell(rowid,2,"","ferie");
            jQuery('#caltable').setCell(rowid,3,"","ferie");
            jQuery('#caltable').setCell(rowid,4,"","ferie");
            jQuery('#caltable').setCell(rowid,5,"","ferie");
            jQuery('#caltable').setCell(rowid,6,"","ferie");
        } else if (jour == 9)
        {
            jQuery('#caltable').setCell(rowid,0,"","today");
            jQuery('#caltable').setCell(rowid,1,"","today");
            jQuery('#caltable').setCell(rowid,2,"","today");
            jQuery('#caltable').setCell(rowid,3,"","today");
            jQuery('#caltable').setCell(rowid,4,"","today");
            jQuery('#caltable').setCell(rowid,5,"","today");
            jQuery('#caltable').setCell(rowid,6,"","today");
        }
      },
        rowNum:365,
        imgpath: gridimgpath,
        pager: jQuery('#caltablePager'),
        sortname: 'date',
        mtype: "POST",
        viewrecords: true,
        sortorder: "desc",
        //multiselect: true,
        caption: "<span style='padding:4px; font-size: 13px;'>Historique des co&ucirc;ts</span>",
        viewsortcols: true,
        footerrow : true,
        userDataOnFooter : true,
 }).navGrid("#gridListRessources1Pager",
            {   add:false,
                del:false,
                edit:false,
                search:true,
                position:"left"
});

EOF;
//print "var mydata = [ ".$alldata." ];";
//print 'for(var i=0;i<=mydata.length;i++) jQuery("#caltable").addRowData(i+1,mydata[i]);';



print 'var tmpArr=new Array();';
print 'tmpArr["date"]="Total";';
print 'tmpArr["RHCost"]=' . preg_replace('/,/', '.', round($cost['RH'] * 100) / 100) . ';';
print 'tmpArr["RCost"]=' . preg_replace('/,/', '.', round($cost['R'] * 100) / 100) . ';';
print 'tmpArr["ECost"]=' . preg_replace('/,/', '.', round($cost['E'] * 100) / 100) . ';';
print 'tmpArr["Tot"]=' . preg_replace('/,/', '.', round($cost['T'] * 100) / 100) . ';';


print 'jQuery("#caltable").footerData("set",tmpArr,true);';
print "</script>";
?>
