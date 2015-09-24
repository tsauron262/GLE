<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 10 aout 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : ressource_html-repsonse.php GLE-1.0
  */
  require_once('../../main.inc.php');
  require_once(DOL_DOCUMENT_ROOT."/Synopsis_Hrm/hrm.class.php");

//TODO :> affiche le Détail des ressource = role, cout, date,
//TODO : % d'occupation
//TODO restrict date in seek datas
$project_id=$_REQUEST['projet_id'];

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("synopsisGene@synopsistools");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);

    $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
    $jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
    $css = DOL_URL_ROOT."/Synopsis_Common/css";
    $imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

    $js = ' <script src="'.$jspath.'/jquery-1.3.2.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jqueryuipath.'/jquery-ui.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jqueryuipath.'/ui.core.js" type="text/javascript"></script>';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/jquery-ui.css" />';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/flick/jquery-ui-1.7.2.custom.css" />';


    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';

    $js .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
    $js .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu(); });  </script>\n";



    $js .= "<style type='text/css'>body { position: static; }</style>";

    $js .= '      <script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.bgiframe.js" ></script>';
    $jqgridJs ="<script type='text/javascript'>";
    $jqgridJs .= 'var gridimgpath="'.$imgPath.'/images/";';
    $jqgridJs .= 'var userId="'.$user->id.'";';
    $jqgridJs .= 'var socId="'.$soc->id.'"; ';
    $jqgridJs .= 'var campId="'.$campagneId.'"; ';
    $jqgridJs .= 'var DOL_URL_ROOT="'.DOL_URL_ROOT.'"; ';
    $jqgridJs .= "var userincomp='".$jsEditopts["userincomp"]."'\n";
    $jqgridJs .= "var catRess='".$jsEditopts["catRess"]."'\n";
    $jqgridJs .= "var projId='".$_REQUEST['projet_id']."'\n";
    $jqgridJs .="</script>";
    $js .= $jqgridJs;
    echo $js;
//    top_menu($js,$langs->trans("Ressources"), "",1,false);

$hrm=new hrm($db);
//menu vertical a la magento
print "<div style='width: 100%; height: 800px;'>";
print "<div style='width:25%; min-width: 200px; float: left;'>";
$hrm->listRessources();
$inProjetArr = array();
foreach($hrm->allRessource as $key=>$val)
{
    if ("x".$val['GLEId'] != "x")
    {
        $requete = "SELECT DISTINCT ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_user
                      FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors,
                           ".MAIN_DB_PREFIX."projet_task,
                           ".MAIN_DB_PREFIX."Synopsis_hrm_user
                     WHERE ".MAIN_DB_PREFIX."projet_task.rowid = ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_projet_task
                       AND ".MAIN_DB_PREFIX."Synopsis_hrm_user.user_id =  ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_user
                       AND ".MAIN_DB_PREFIX."projet_task.fk_projet = ".$project_id."
                       AND ".MAIN_DB_PREFIX."Synopsis_hrm_user.user_id = ".$val['GLEId']."";
        echo $requete;
        $sql = $db->query($requete);
        $res=$db->fetch_object($sql);
        if ($res->fk_user."x" != "x")
        {
            $inProjetArr[$res->fk_user]=$val['empId'];
        }
    }
}
if (count($inProjetArr) > 0)
{
    print "<TABLE style='border-collapse: collapse; width: 100%;' >";
    print "<THEAD>";
    print "<TR>";
    print "<TH>Nom</TH>";
    print "</TR>";
    print "</THEAD>";
    print"<TBODY>";

    $iter=0;
    foreach($inProjetArr as $key=>$val)
    {
        $class="";
        if ($iter ==  0 ){ $class="ActorActive"; }
        $fuser = new User($db);
        $fuser->fetch($key);
        print "<TR><TD class='Actorlist $class' id='Actor-$key'>";
        print $fuser->getNomUrl(1);
        print "</TD></TR>";
        $iter++;
    }
    print "</TBODY>";
    print "</TABLE>";
} else {
    print "Pas de ressource dans le projet";
}
print "</div>";
print "<div id='detailUser' style='width:70%; min-width: 200px; height: 100%; float: left; background-color: #313131;'>&nbsp;";
print "</div>";
print "</div>";
print "<script type='text/javascript'>";
print "   $('.Actorlist').click(function(){
            var user_id = $(this).attr('id');
            $('.ActorActive').removeClass('ActorActive');
            $(this).addClass('ActorActive');
            $.ajax({
               type: 'POST',
                url: 'ajax/HTressource_xml-response.php',
               data: 'projet_id=".$project_id."&user_id='+user_id,
               async: true,
               success: function(msg){
                    var arrDayOff = new Array();
                    var arrVacation = new Array();
                    var arrProjTime = new Array();
                    var pairimpair='pair';
                    var totTimePrev = 0;
                    var totTimeEff = 0;
                    var totTimeCost = 0;
                    var userName = new Array();
                    var coutHoraire = new Array();
                    var curUser;
                    $(msg).find('actorDetail').each(function(){
                            curUser=$(this).find('id').text();
                            userName[curUser]=$(this).find('fullname').text();
                            coutHoraire[curUser]=parseInt($(this).find('couthoraire').text());
                    })


                    var longHtml = '<div id=\'detailUser\' style=\'width:70%; min-width: 200px; height: 100%; float: left; background-color: #A1A1F1;\'>';
                        longHtml += '  <p><span>'+ userName[curUser] +'</span>';
                        longHtml += '  <p><span>Equipe :</span>';
                        longHtml += '     '+$(msg).find('team').find('name').text()+'</p>';

                        longHtml += '<table id=\'tableJQ\' width=100% style=\"border-collapse: collapse;\"><thead>';
                        longHtml += '<tr>';
                        longHtml += '  <th id=\'tache\' align=\'left\'>T&acirc;che</td>';
                        longHtml += '  <th id=\'role\' align=\'center\'>R&ocirc;le</td>';
                        longHtml += '  <th suffix=\'h\' id=\'tmpprev\' formatter=\'integer\'  align=\'right\'>Temps pr&eacute;vu</td>';
                        longHtml += '  <th suffix=\'h\' id=\'tmpeff\' formatter=\'integer\' align=\'right\'>Temps effectif</td>';
                        longHtml += '  <th thousandsSeparator=\' \' suffix=\'&euro;\' id=\'cout\' formatter=\'currency\' align=\'right\'>Co&ucirc;t</td></tr>';
                        longHtml += '</thead><tbody>';

                    $(msg).find('actor').each(function(){
                        var task = $(this).find('task').text();
                        var role = $(this).find('role').text();
                        var taskId = $(this).find('taskId').text();
                        var userId = $(this).find('id').text();
                        var TimePrev = 0;
                        $(msg).find('time').each(function(){
                            if ( $(this).find('taskId').text() == taskId && $(this).find('id').text() == userId)
                            {
                                TimePrev += parseInt($(this).find('duration').text());
                            }
                        });
                        var TimeEff = 0;
                        $(msg).find('effectiveTime').each(function(){
                            if ( $(this).find('taskId').text() == taskId && $(this).find('id').text() == userId)
                            {
                                TimeEff += parseInt($(this).find('duration').text());
                            }
                        });
                        var cost = (TimePrev / 3600 )* coutHoraire[userId];


                        if (pairimpair == 'pair')
                        {
                            pairimpair = 'impair';
                        } else {
                            pairimpair = 'pair';
                        }
                        longHtml += '<tr class=\''+pairimpair+'\'><td align=left>'+task+'</td>';
                        longHtml += '    <td align=center>'+role+'</td>';
                        totTimePrev += parseInt(TimePrev);
                        totTimeEff += parseInt(TimeEff);
                        totTimeCost += parseInt(cost);
                        longHtml += '    <td align=right style=\'padding-right: 17px;\'>' + parseFloat(Math.round(TimePrev/36)/100) + '</td>';
                        longHtml += '    <td align=right style=\'padding-right: 17px;\'>' +  parseFloat(Math.round(TimeEff/36)/100) + '</td>';
                        longHtml += '    <td align=right style=\'padding-right: 17px;\'>'+ parseInt(cost) +'</td>';
                        longHtml += '</tr>';
                    });
//                    longHtml += '<tr style=\'border-top:1px Solid #000000; \' class=\''+pairimpair+'\'><td colspan=1 align=center>Total</td><td>&nbsp;</td>';
//                    longHtml += '    ';
//                    longHtml += '    <td align=right style=\'padding-right: 17px;\'>' + parseInt(totTimePrev/3600) + 'h</td>';
//                    longHtml += '    <td align=right style=\'padding-right: 17px;\'>' + parseInt(totTimeEff/3600) + 'h</td>';
//                    longHtml += '    <td align=right style=\'padding-right: 17px;\'>'+parseInt(totTimeCost)+'&euro;</td>';
//                    longHtml += '</tr>';

                    var iter = 0;
                    $(msg).find('vacation').find('date').each(function(){
                        var dateVac = $(this).find('dateDeb').text();
                        var durVac = $(this).find('duration').text();
                        arrVacation[iter]=new Array();
                        arrVacation[iter]['startOn']=parseInt(dateVac);
                        arrVacation[iter]['duration']=parseInt(durVac);
                        iter++;
                    });
                    iter = 0;
                    $(msg).find('dayOff').find('date').each(function(){
                        var dateVac = $(this).find('dateDeb').text();
                        var durVac = $(this).find('duration').text();
                        arrDayOff[iter]=new Array();
                        arrDayOff[iter]['startOn']=parseInt(dateVac);
                        arrDayOff[iter]['duration']=parseInt(durVac);
                        iter++;
                    });
                    iter = 0;
                    $(msg).find('time').each(function(){
                        var TaskStartOn = $(this).find('dateDeb').text();
                        var duration = $(this).find('duration').text();
                        arrProjTime[iter] = new Array();
                        arrProjTime[iter]['startOn']=parseInt(TaskStartOn);
                        arrProjTime[iter]['duration']=parseInt(duration);
                        iter++;
                    });
                    longHtml += '</tbody></table><div id=\'pagertableJQ\'></div>';

                var startDate = $(msg).find('ProjStart').text();
                var stopDate = $(msg).find('ProjStop').text();
                    stopDate = parseInt(stopDate) + 3600 * 24 * 60
                var curDate = startDate * 1000;
                var stepDate = 3600*24*1000;
                var projDurDay = parseInt((stopDate - startDate)/ (3600 * 24));
                var arrProj = new Array();
                var iter=0;
                var decal = 0;
                for (var i in arrProjTime)
                {
                    for (j=0;j<arrProjTime[i]['duration'] / (3600*24); j++ )
                    {
                        decali = -1;
                        while (true)
                        {
                            decali = testDate(arrProjTime,i,j,decal,arrDayOff,arrVacation);
                            if (decali == decal) break;
                            decal = decali;
                        }

                        arrProj[iter]=new Array();
                        arrProj[iter]['startOn']=(parseInt(arrProjTime[i]['startOn']) + parseInt(parseInt(j) + parseInt(decal)) * parseInt(3600) * parseInt(24) ) * parseInt(1000);
                        iter++;
                    }
                }
                function testDate(arrProjTime,i,j,decal,arrDayOff,arrVacation)
                {
                        var d = new Date((parseInt(arrProjTime[i]['startOn']) + parseInt(parseInt(j) + parseInt(decal)) * parseInt(3600) * parseInt(24)) * parseInt(1000));
                        if  (d.getDay() == 0 || d.getDay()==6)
                        {
                            decal ++;
                            d = new Date((parseInt(arrProjTime[i]['startOn']) + parseInt(parseInt(j) + parseInt(decal)) * parseInt(3600) * parseInt(24)) * parseInt(1000));
                        }
                        if  (d.getDay() == 0 || d.getDay()==6)
                        {
                            decal ++;
                            d = new Date((parseInt(arrProjTime[i]['startOn']) + parseInt(parseInt(j) + parseInt(decal)) * parseInt(3600) * parseInt(24)) * parseInt(1000));
                        }
                //        //Si day off ou vacation decal ++
                        for (var h in arrDayOff)
                        {
                            if (d.getTime() >= arrDayOff[h]['startOn'] * parseInt(1000) && d.getTime() < arrDayOff[h]['startOn'] * parseInt(1000)  + 24 * 3600 * 1000)
                            {
                                decal ++;
                                d = new Date((parseInt(arrProjTime[i]['startOn']) + parseInt(parseInt(j) + parseInt(decal)) * parseInt(3600) * parseInt(24)) * parseInt(1000));
                            }
                        }
                        for (var h in arrVacation)
                        {
                            if (d.getTime()  >= arrVacation[h]['startOn'] * parseInt(1000) && d.getTime()  < arrVacation[h]['startOn'] * parseInt(1000) + 24 * 1000 * 3600)
                            {
                                decal ++;
                                d = new Date((parseInt(arrProjTime[i]['startOn']) + parseInt(parseInt(j) + parseInt(decal)) * parseInt(3600) * parseInt(24)) * parseInt(1000));
                            }
                        }
                    return (decal);
                }
                var timeline = '<br><div id=timeline style=\'clear:both;width:100%; max-width:800px; height:50px; max-height:50px;background-color:white;border:1px Solid #000000;overflow-x:scroll;overflow-y:none;\'>';
                    timeline +=  ' <div style=\"clear: both; width: '+ (70 + 2) * projDurDay +'px; max-height:45px; \">';
                    for(i=0;i<projDurDay;i++)
                    {
                        var d= new Date(curDate);
                        //alert (curDate);
                        var cssclass='';
                        for (var j in arrDayOff)
                        {
                            if (arrDayOff[j]['startOn'] * 1000 + 3600000 * 24 >= curDate  && arrDayOff[j]['startOn'] * 1000 + 3600000 * 24 < curDate + 24 * 3600 * 1000  )
                            {
                                cssclass += ' dayOff';
                            }
                        }
                        for (var j in arrVacation)
                        {
                            if (arrVacation[j]['startOn'] * 1000 + 3600000 * 24 >= curDate  && arrVacation[j]['startOn'] * 1000 + 3600000 * 24 < curDate + 24 * 3600  * 1000)
                            {
                                cssclass += ' vacation';
                            }
                        }
                        var countEventDay=0;
                        for (var j in arrProj)
                        {
                            if (arrProj[j]['startOn'] >= curDate   && arrProj[j]['startOn']  < curDate + 24 * 3600000 )
                            {
                                countEventDay++;
                            }
                        }
                        if (countEventDay == 1)
                        {
                            cssclass += ' projDay';
                        }
                        if (countEventDay > 1)
                        {
                            cssclass += ' projDay projDayMoreThanFull';
                        }

                        if (d != null) {
                            var year = d.getYear()+'';
                            var month = d.getMonth() + 1 ;
                            if (month < 10) month = '0' + month
                            if (d.getDay() == 0 || d.getDay()==6) cssclass += ' weekEnd';
                            d = d.getDate()+'/'+month+'/'+year.substr(-2,2);
                        }
                        timeline +=  '  <div class=\''+cssclass+'\' style=\'min-width: 70px; max-width: 70px; max-height:42px;height:22px; float: left; border:1px Solid #000000;\'>';
                        timeline +=  d;
                        timeline += '</div>';
                        curDate = parseInt(curDate) + parseInt(stepDate);
                    }
                    timeline += '</div>';
                    timeline += '</div>';
                    longHtml += '</div>';
                    longHtml += timeline;
                    $('#detailUser').replaceWith(longHtml);
                    tableToGridEos('#tableJQ');
var tmpArr=new Array();
        tmpArr['tache']='Total';
        tmpArr['role']='';
        tmpArr['tmpprev']=parseFloat(Math.round(totTimePrev/36)/100);
        tmpArr['tmpeff']=parseFloat(Math.round(totTimeEff/36)/100);
        tmpArr['cout']=parseFloat(Math.round(totTimeCost*100)/100);
    var mygrid = $('#tableJQ');
        mygrid.footerData('set',tmpArr,true);
                    $('#tableJQ').trigger('reloadGrid');
               }
            });
}); ";

print "   $('.Actorlist').mouseover(function(){
    $(this).addClass('ActorOver');
}); ";
print "   $('.Actorlist').mouseout(function(){
    $(this).removeClass('ActorOver');
}); ";
print <<<EOF
function priceFormat(num,showCents)
{
    var nStr = '' + Math.round(parseFloat(num) * 100) / 100;
        var x = nStr.split(',');
        var x1 = x[0];
        var x2;
        if (showCents)
        {
            x2 = x.length > 1 ? '.' + x[1] : '.00';
        } else {
            x2 = "";
        }
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + ' ' + '$2');
        }
        return x1 + x2;
}

function tableToGridEos(selector, options) {
$(selector).each(function() {
    if(this.grid) {return;} //Adedd from Tony Tomov
    // This is a small "hack" to make the width of the jqGrid 100%
    $(this).width("99%");
    var w = $(this).width();

    // Text whether we have single or multi select
    var inputCheckbox = $('input[type=checkbox]:first', $(this));
    var inputRadio = $('input[type=radio]:first', $(this));
    var selectMultiple = inputCheckbox.length > 0;
    var selectSingle = !selectMultiple && inputRadio.length > 0;
    var selectable = selectMultiple || selectSingle;
    var inputName = inputCheckbox.attr("name") || inputRadio.attr("name");

    // Build up the columnModel and the data
    var colModel = [];
    var colNames = [];
    $('th', $(this)).each(function() {
        if (colModel.length == 0 && selectable) {
            colModel.push({
                name: '__selection__',
                index: '__selection__',
                width: 0,
                hidden: true
            });
            colNames.push('__selection__');
        } else {
            colModel.push({
                name: $(this).attr("id") || $(this).html(),
                index: $(this).attr("id") || $(this).html(),
                align: $(this).attr("align") || $(this).html(),
                formatter: $(this).attr("formatter") || $(this).html(),
                formatoptions:{ suffix: $(this).attr("suffix") , thousandsSeparator: $(this).attr("thousandsSeparator") },
                footerrow : true,
                width: $(this).width() || 150
            });
            colNames.push($(this).html());
        }
    });
    var data = [];
    var rowIds = [];
    var rowChecked = [];
    $('tbody > tr', $(this)).each(function() {
        var row = {};
        var rowPos = 0;
        data.push(row);
        $('td', $(this)).each(function() {
            if (rowPos == 0 && selectable) {
                var input = $('input', $(this));
                var rowId = input.attr("value");
                rowIds.push(rowId || data.length);
                if (input.attr("checked")) {
                    rowChecked.push(rowId);
                }
                row[colModel[rowPos].name] = input.attr("value");
            } else {
                row[colModel[rowPos].name] = $(this).html();
            }
            rowPos++;
        });
    });

    // Clear the original HTML table
    $(this).empty();

    // Mark it as jqGrid
    $(this).addClass("scroll");

    $(this).jqGrid($.extend({
        datatype: "local",
        width: w,
        caption: 'R&eacute;capitulatif',
        colNames: colNames,
        colModel: colModel,
        pager: $("#pagertableJQ"),
        rownumbers: false,
        gridview : true,
        sortorder: "asc",
        sortname: "tache",
        footerrow: true,
        //inputName: inputName,
        //inputValueCol: imputName != null ? "__selection__" : null
    }, options || {})).navGrid('#pagertableJQ',{edit:false,add:false,del:false,search:false,refresh:true,position:"left",view:false});

    // Add data
    for (var a = 0; a < data.length; a++) {
        var id = null;
        if (rowIds.length > 0) {
            id = rowIds[a];
            if (id && id.replace) {
                // We have to do this since the value of a checkbox
                // or radio button can be anything
                id = encodeURIComponent(id).replace(/[.\-%]/g, "_");
            }
        }
        if (id == null) {
            id = a + 1;
        }
        $(this).addRowData(id, data[a]);
    }

    // Set the selection
    for (var a = 0; a < rowChecked.length; a++) {
        $(this).setSelection(rowChecked[a]);
    }

});
};


EOF;
print "</script>";

?>
