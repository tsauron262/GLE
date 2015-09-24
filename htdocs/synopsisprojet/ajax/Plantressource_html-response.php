<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 10 aout 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : ressource_html-repsonse.php
  * GLE-1.1
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

    $js = ' <script src="'.$jspath.'/jquery-1.3.2.js" type="text/javascript"></script>'."\n";
    $js .= ' <script src="'.$jqueryuipath.'/jquery-ui.js" type="text/javascript"></script>'."\n";
    $js .= ' <script src="'.$jqueryuipath.'/ui.core.js" type="text/javascript"></script>'."\n";
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/jquery-ui.css" />'."\n";
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/flick/jquery-ui-1.7.2.custom.css" />'."\n";
$js .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
$js .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu(); });  </script>\n";

    $js .= ' <script src="'.$jspath.'/jquery.dimensions.js" type="text/javascript"></script>'."\n";
    $js .= ' <script src="'.$jspath.'/jquery.tooltip.js" type="text/javascript"></script>'."\n";

    $js .= "<style>";
    $js .= "  .dateHeader { font-family:'Times New Roman',Georgia,Serif; color: #222222;  }     .promoteZBig{
        z-index: 1000000;
        position: fixed;
        background-color: #EFEFEF;
        padding-left: 10pt;
        padding-right: 10pt;
        padding-top: 2pt;
        padding-bottom: 2pt;
        -moz-border-radius: 8px;
        -webkit-border-radius: 8px;
        -moz-outline-radius: 8px;
        -webkit-outline: 0px;
        border: 1px solid #0073EA;
        -moz-outline: 3px solid #EFEFEF;
        min-width: 200px;
    }
    .dateHeader{
        background-color: #3875D7;
        color: #FFFFDF;
    }
 ";
    $js .="</style>";

    $js .= "<style type='text/css'>body { position: static; }</style>"."\n";
    echo $js;
//    top_menu($js,$langs->trans("Plan de ressources"), "",1,false);

  $requete = "SELECT unix_timestamp(min(task_date)) as minDate,
                     unix_timestamp(max(task_date)) as maxDate
                FROM ".MAIN_DB_PREFIX."projet_task_time,
                     ".MAIN_DB_PREFIX."projet_task
               WHERE task_date is not null
                 AND ".MAIN_DB_PREFIX."projet_task.fk_projet = ".$project_id. "
                 AND ".MAIN_DB_PREFIX."projet_task_time.fk_task = ".MAIN_DB_PREFIX."projet_task.rowid";
  $sql = $db->query($requete);
  $res = $db->fetch_object($sql);
  $startDate = $res->minDate;
  $stopDate = $res->maxDate;

$hrm=new hrm($db);
//menu vertical a la magento
print "<div style='width: 100%; height: 800px;'>";
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
                       AND ".MAIN_DB_PREFIX."Synopsis_hrm_user .user_id =  ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_user
                       AND ".MAIN_DB_PREFIX."projet_task.fk_projet = ".$project_id."
                       AND ".MAIN_DB_PREFIX."Synopsis_hrm_user.user_id = ".$val['GLEId']."";
        $sql = $db->query($requete);
        if ($sql)
        {
            $res=$db->fetch_object($sql);
            if ($res->fk_user."x" != "x")
            {
                $inProjetArr[$res->fk_user]=$val['empId'];
            }
        }
    }
}
if (count($inProjetArr) > 0)
{

    print "<div style='width:200px; min-width: 200px; float: left;'>";
    print "<TABLE style='border-collapse: collapse; width: 30%;' >";
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
        print "<TR><TD style='min-width:195px; height: 60px;' class='Actorlist $class' id='Actor-$key'>";
        print $fuser->getNomUrl(1);
        print "</TD>";
        print "</TR>";
        $iter++;
    }
    print "</TBODY>";
    print "</TABLE>";
    print "</div>";
    print "<div style='margin-left: 200px; clear: right; width: 580px; max-width: 580px;  height: 16px; padding-left: 14pt; border: 1px Solid #000000; '>Timeline</div>";
    print "<div style='float: left; width: 600px; max-width: 600px; overflow-x: scroll;overflow-y: hidden; border: 0px Solid #FF0000;'>";
    print "<div style=' border: 0px Solid #00FF00; width: 800px ; '>";
    foreach($inProjetArr as $key=>$val)
    {
        //print "    <div class='replaceInit' id='user-".$fuser->id."'></div>";
        print "<div  class='replaceInit' id='user-".$key."' style='border: 0px Solid #0000FF;height: 60px;'>";
        print "</div>";
    }
    print "</div>";
} else {
    print "Pas de ressource dans le projet";
}
print "</div>";
print "<script type='text/javascript'>";
print <<<EOF
var debugIter = 0;


   function timelineuser(user_id){
            $.ajax({
               type: 'POST',
                url: 'ajax/HTressource_xml-response.php',
EOF;
         print "data: 'projet_id=".$project_id."&user_id='+user_id,";
print <<<EOF
               async: true,
               success: function(msg){
                    var arrDayOff = new Array();
                    var arrVacation = new Array();
                    var arrProjTime = new Array();

                    var iter = 0;
                    $(msg).find('vacation').find('date').each(function(){
                        var dateVac = $(this).find('dateo').text();
                        var durVac = $(this).find('duration').text();
                        arrVacation[iter]=new Array();
                        arrVacation[iter]['startOn']=parseInt(dateVac);
                        arrVacation[iter]['duration']=parseInt(durVac);
                        iter++;
                    });
                    iter = 0;
                    $(msg).find('dayOff').find('date').each(function(){
                        var dateVac = $(this).find('dateo').text();
                        var durVac = $(this).find('duration').text();
                        arrDayOff[iter]=new Array();
                        arrDayOff[iter]['startOn']=parseInt(dateVac);
                        arrDayOff[iter]['duration']=parseInt(durVac);
                        iter++;
                    });
                    iter = 0;
                    $(msg).find('time').each(function(){
                        var TaskStartOn = $(this).find('dateo').text();
                        var duration = $(this).find('duration').text();
                        arrProjTime[iter] = new Array();
                        arrProjTime[iter]['startOn']=parseInt(TaskStartOn);
                        arrProjTime[iter]['duration']=parseInt(duration);
                        iter++;
                    });
                    var actoOccupArr = new Array();
                    $(msg).find('actor').each(function(){
                        var taskId = $(this).find('taskId').text();
                        var occupation = $(this).find('occupation').text();
                        actoOccupArr[taskId]=occupation;
                    });
EOF;
             print "var startDate = ".$startDate.";";
             print "var stopDate = ".$stopDate.";";
print <<<EOF
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
                            arrProj[iter]['taskId']="";
                            iter++;
                        }
                    }
                    var timeline = '';
                        timeline += '<div id="timeline-'+user_id+'" class="" style=\'clear:both;width:'+  (70 + 2) * projDurDay +'px; height:60px; max-height:60px;background-color:white;border:0px Solid #000000;overflow-y:none;\'>';
                        timeline +=  ' <div style=\"clear: both; width: '+ (70 + 2) * projDurDay +'px; max-height:60px; \">';
                        for(i=0;i<projDurDay;i++)
                        {
                            var d= new Date(curDate);
                            //alert (curDate);
                            var cssclass='';
                            var noborder = false;
                            var borderright = true;
                            var borderleft = true;
                            for (var j in arrDayOff)
                            {
                                if (arrDayOff[j]['startOn'] * 1000 + 3600000 * 24 >= curDate  && arrDayOff[j]['startOn'] * 1000 + 3600000 * 24 < curDate + 24 * 3600 * 1000  )
                                {
                                    cssclass += ' dayOff';
                                    if (arrDayOff[j + 1])
                                    {
                                        if (d.getDay() == 1 && arrDayOff[j + 1]['startOn'] * 1000 + 3600000 * 24 >= curDate + 24 * 3600 * 1000  && arrDayOff[j + 1]['startOn'] * 1000 + 3600000 * 24 < curDate + 24 * 3600 * 1000 * 2  )
                                        {
                                            noborder=true;
                                            borderright=false;
                                        } else if (d.getDay() != 1 && arrDayOff[j + 1]['startOn'] * 1000 + 3600000 * 24 >= curDate  && arrDayOff[j + 1]['startOn'] * 1000 + 3600000 * 24 < curDate + 24 * 3600 * 1000  )
                                        {
                                            noborder=true;
                                            borderleft=false;
                                            borderright=false;
                                        }
                                    }
                                }
                            }
                            for (var j in arrVacation)
                            {
                                var nextDay = parseInt(curDate) + 24 * 3600 * 1000;
                                var nextnextDay = parseInt(curDate) + 24 * 3600 * 1000 * 2;
                                var prevDay = parseInt(curDate) - 24 * 3600 * 1000;
                                if (arrVacation[j]['startOn'] * 1000 + 3600000 * 24 >= curDate  && arrVacation[j]['startOn'] * 1000 + 3600000 * 24 < curDate + 24 * 3600  * 1000)
                                {
                                    cssclass += ' vacation';
                                    for (var h in arrVacation)
                                    {
                                        var nextVacDay = parseInt(arrVacation[h]['startOn']) * 1000 + 3600000 * 24;
                                        if (d.getDay() == 1 && nextVacDay >= nextDay && nextVacDay < nextnextDay )
                                        {
                                            noborder=true;
                                            borderright=false;
                                            break;
                                        } else if ( nextVacDay >= nextDay && nextVacDay < nextnextDay)
                                        {
                                            noborder=true;
                                            borderleft=false;
                                            borderright=false;
                                            break;
                                        }
                                        if (d.getDay() == 5 &&  nextVacDay >= prevDay && nextVacDay < curDate )
                                        {
                                            noborder=true;
                                            borderleft=false;
                                            break;
                                        }
                                    }
                                    if (! noborder)
                                    {

                                        for (var h in arrVacation)
                                        {
                                            var nextVacDay = (parseInt(arrVacation[h]['startOn']) * 1000)  + 3600 * 1000 * 24;
                                            if (nextVacDay >= prevDay  && nextVacDay < parseInt(curDate) )
                                            {
                                                noborder=true;
                                                borderleft=false;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                            var countEventDay=0;
                            var contentDebug = "";
                            for (var j in arrProj)
                            {
                                var nextDay = parseInt(curDate) + 24 * 3600 * 1000;
                                var nextnextDay = parseInt(curDate) + 24 * 3600 * 1000 * 2;
                                var prevDay = parseInt(curDate) - 24 * 3600 * 1000;

                                if (arrProj[j]['startOn'] >= curDate   && arrProj[j]['startOn']  < curDate + 24 * 3600000 )
                                {
                                    countEventDay+= 1; /// ajoute occupattion
                                    contentDebug = d.getDay();
                                    for (var h in arrProj)
                                    {
                                        var nextProjDay = parseInt(arrProj[h]['startOn']);
                                        if (d.getDay() == 1 && nextProjDay >= nextDay && nextProjDay < nextnextDay )
                                        {
                                            noborder=true;
                                            borderright=false;
                                            break;
                                        } else if ( nextProjDay >= nextDay && nextProjDay < nextnextDay)
                                        {
                                            noborder=true;
                                            borderleft=false;
                                            borderright=false;
                                            break;
                                        }
                                        if (d.getDay() == 5 &&  nextProjDay >= prevDay && nextProjDay < curDate )
                                        {
                                            noborder=true;
                                            borderleft=false;
                                            break;
                                        }
                                    }
                                    if (! noborder)
                                    {
                                        for (var h in arrProj)
                                        {
                                            var nextProjDay = parseInt(arrProj[h]['startOn']);
                                            if (nextProjDay >= prevDay && nextProjDay < curDate )
                                            {
                                                noborder=true;
                                                borderleft=false;
                                                break;
                                            }
                                        }
                                    }
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
                                if (d.getDay() == 0) { cssclass += ' weekEnd'; borderleft=false; borderright=true; noborder = true; }
                                if (d.getDay() == 6) { cssclass += ' weekEnd'; borderleft=true; borderright=false; noborder = true; }

                                var day = d.getDate() ;
                                if (day < 10) day = '0' + day;

                                d = day+'/'+month+'/'+year.substr(-2,2);

                            }
                            var borderStyle = "";
                            if (noborder)
                            {
                                if (!borderright)
                                {
                                    if (borderleft){
                                        borderStyle = 'border-top:0.5px Solid #000000; \
                                                       border-bottom:0.5px Solid #000000; \
                                                       border-left:0.5px Solid #000000; \
                                                       border-right:0px Solid #000000;  ';
                                    } else {
                                        borderStyle = 'border-top:0.5px Solid #000000; \
                                                       border-bottom:0.5px Solid #000000; \
                                                       border-left:0 Solid #000000; \
                                                       border-right:0px Solid #000000;  ';
                                    }
                                } else {
                                    if (borderleft){
                                        borderStyle = 'border-top:0.5px Solid #000000; \
                                                       border-bottom:0.5px Solid #000000; \
                                                       border-left:0.5px Solid #000000; \
                                                       border-right:0.5px Solid #000000;  ';
                                    } else {
                                        borderStyle = 'border-top:0.5px Solid #000000; \
                                                       border-bottom:0.5px Solid #000000; \
                                                       border-left:0 Solid #000000; \
                                                       border-right:0.5px Solid #000000;  ';
                                    }
                                }
                            } else {
                                borderStyle = 'border-top:0.5px Solid #000000; \
                                               border-bottom:0.5px Solid #000000; \
                                               border-left:0.5px Solid #000000; \
                                               border-right:0.5px Solid #000000; ';
                            }

                            timeline +=  '  <div style=\'min-width: 70px; text-align:center;  max-width: 70px; max-height:60px;height:60px; float: left;  \'>';
                            timeline +=    ' <div class=\'dateHeader\' style="text-align: center;  padding-bottom: 2px ; height: 13px;  border: 1px Solid #444444;">'+d+"</div>";
                            timeline +=    ' <div  class=\'tooltip '+cssclass+'\' style="height: 43px; '+ borderStyle +' ">&nbsp;<span style="display:none;">Tache Machin<hr>Duree :n<br>Debut :m<br>Fin :o<br>% occup :p%<br>Duree std:q<br>Duree spe :r<br></span></div>';
                            timeline += '</div>';
                            curDate = parseInt(curDate) + parseInt(stepDate);
                        }
                        timeline += '</div>';
                        timeline += '</div>';
                        $('#user-'+user_id).replaceWith(timeline);


                    $('.tooltip').tooltip({
                        bodyHandler: function() {
                            var title = "";
                            var html = $(this).find('span').html();
                            return html;
                        },
                        showURL: false,
                        track: true,
                        delay: 100,
                        showBody: " - ",
                        fade: 250,
                        extraClass: "promoteZBig",
                        left: 40,
                        top: -30,
                    });


               }
            });
}
$('.Actorlist').mouseover(function(){
    $(this).addClass('ActorOver');
    var id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
    $("#timeline"+id).css({backgroundColor: "#FFFFAF", opacity: 0.9});
});
$('.Actorlist').mouseout(function(){
    $(this).removeClass('ActorOver');
    var id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
    $("#timeline"+id).css({backgroundColor: "transparent", opacity: 1});
});
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
var userArr = new Array();
iter= 0;
$('.replaceInit').each(function(){
    var user_id = $(this).attr('id').replace(/^user-/i,'');
    timelineuser(user_id);
    iter++;
});
EOF;
print "</script>";
    print "<div id='legend' style='padding-top: 25px; font-size: 9pt; font-weight: 900;  width: 193px; padding-left: 600px; margin: 10px; clear: both;'><fieldset style='-moz-corner-radius: 8px;-webkit-corner-radius: 8px;'><legend> L&eacute;gende </legend>
                 <table width=100% style='font-size: 8pt;font-weight: normal;'>
                                   <tr><td>Vacances</td><td align=right><div style='height: 10px; width: 10px' class='vacation'></div></td></tr><tr>
                                   <tr><td>F&eacute;ri&eacute;</td><td align=right><div style='height: 10px; width: 10px' class='dayOff'></div></td></tr>
                                   <tr><td>Projet</td><td align=right><div style='height: 10px; width: 10px' class='projDay'></div></td></tr>
                                   <tr><td>Projet occ. > 100%</td><td align=right><div style='height: 10px; width: 10px' class='projDayMoreThanFull'></div></td></tr></table></fieldset></div>";

?>