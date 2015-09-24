<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 24 juil. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : myactivity-bar-chart.php
  * GLE-1.0
  */

require_once('../../main.inc.php');

//$userid=59;

$requete = "SELECT ifnull(sum(".MAIN_DB_PREFIX."projet_task_time.task_duration),0) as td,
                   ".MAIN_DB_PREFIX."projet_task.fk_projet,
                   ".MAIN_DB_PREFIX."Synopsis_projet_view.title
              FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors,
                   ".MAIN_DB_PREFIX."Synopsis_projet_view,
                   ".MAIN_DB_PREFIX."projet_task
         LEFT JOIN ".MAIN_DB_PREFIX."projet_task_time ON ".MAIN_DB_PREFIX."projet_task_time.fk_task = ".MAIN_DB_PREFIX."projet_task.rowid
             WHERE ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_projet_task = ".MAIN_DB_PREFIX."projet_task.rowid
               AND ".MAIN_DB_PREFIX."Synopsis_projet_view.rowid = ".MAIN_DB_PREFIX."projet_task.fk_projet
          GROUP BY ".MAIN_DB_PREFIX."projet_task.fk_projet";

if ($_REQUEST['dur']=="effective")
{
$requete = "SELECT ifnull(sum(".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective.task_duration_effective),0) as td,
                   ".MAIN_DB_PREFIX."projet_task.fk_projet,
                   ".MAIN_DB_PREFIX."Synopsis_projet_view.title
              FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors,
                   ".MAIN_DB_PREFIX."Synopsis_projet_view,
                   ".MAIN_DB_PREFIX."projet_task
         LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective ON ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective.fk_task = ".MAIN_DB_PREFIX."projet_task.rowid
             WHERE ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_projet_task = ".MAIN_DB_PREFIX."projet_task.rowid
               AND ".MAIN_DB_PREFIX."Synopsis_projet_view.rowid = ".MAIN_DB_PREFIX."projet_task.fk_projet
          GROUP BY ".MAIN_DB_PREFIX."projet_task.fk_projet";

}

$sql = $db->query($requete);


print <<<EOF

{
  "elements" : [
    {
EOF;
print <<<EOF
      "colours" : [
        "0x336699", "0x88AACC", "0x999933", "0x666699",
        "0xCC9933", "0x006666", "0x3399FF", "0x993300",
        "0xAAAA77", "0x666666", "0xFFCC66", "0x6699CC",
        "0x663366", "0x9999CC", "0xAAAAAA", "0x669999",
        "0xBBBB55", "0xCC6600", "0x9999FF", "0x0066CC",
        "0x99CCCC", "0x999999", "0xFFCC00", "0x009999",
        "0x99CC33", "0xFF9900", "0x999966", "0x66CCCC",
        "0x339966", "0xCCCC33"      ],

      "alpha" : 0.6,
      "start_angle" : 135,
      "radius":150,
      "no-labels":false,
      "ani--mate" : true,
      //"label-colour":0,  // leave out or set to null for auto-colour labels
      "values" : [
EOF;
//        {
//          "value" : 135,
//          "label" : "Label Num 1 - click to google",
//          "label-colour":"0xFF0000",         // Override the label colour
//          "on-click":"http://www.google.com",
//          "animate":[{"type":"bounce","distance":5},{"type":"fade"}]
//        }
$arr=array();
$tot=0;
while ($res = $db->fetch_object($sql))
{
    $arr[]=array('td'=>$res->td, 'title'=>$res->title);
    $tot = $res->td + $tot;
}

$arr1 = array();
foreach ($arr as $key=>$val)
{
    $percent = round(($val["td"] * 100) /$tot);
    $arr1[] ='{
              "value" : '.$val["td"].',
              "label" : "'.$val['title'].'",
              "tip"   : "Projet: '.$val['title'].'<br>Temps: '.sec2time($val['td']).'<br>Occupation: '.$percent.'%",
              "animate" : [{"type":"bounce","distance":15},{"type":"fade"}]
            }';
}


print join(',',$arr1);
print <<<EOF

      ],
      "type" : "pie",
      "border" : "2"
    }
  ],
  "bg_colour" : "#FAFAFA",
  "title" : {
EOF;
if ($_REQUEST['dur']=="effective")
{
    print '"text" : "Activité  planifiée",';
} else {
    print '"text" : "Activité prévue",';
}

print <<<EOF
    "style" : "{font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;}"
  }
}


EOF;
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

function sec2hour($sec){
    $days=false;
    $returnstring = " ";
    $hours = intval ( ($sec/3600) );
    $minutes = intval( ($sec - ( ($hours*3600)))/60);
    $seconds = $sec - ( ($hours*3600)+($minutes * 60));

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