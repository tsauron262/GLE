<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 20 avr. 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : planBar-json_response.php
  * GLE-1.2
  */

require_once('../../../main.inc.php');



//var ganttData = [
//    {
//        id: 1, name: "Feature 1", series: [
//            { name: "Planned", start: new Date(2010,00,01), end: new Date(2010,00,03) },
//            { name: "Actual", start: new Date(2010,00,02), end: new Date(2010,00,05), color: "#f0f0f0" }
//        ]
//    },
//    {
//        id: 2, name: "Feature 2", series: [
//            { name: "Planned", start: new Date(2010,00,05), end: new Date(2010,00,20) },
//            { name: "Actual", start: new Date(2010,00,06), end: new Date(2010,00,17), color: "#f0f0f0" },
//            { name: "Projected", start: new Date(2010,00,06), end: new Date(2010,00,17), color: "#e0e0e0" }
//        ]
//    },

$requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_projet_task WHERE fk_projet =  44";
$sql = $db->query($requete);
while ($res=$db->fetch_object($sql))
{
    $arrSerie = array();
    $requete1 = "SELECT min(task_date) as ddate, max(date_add(task_date,interval (SELECT sum(task_duration) FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time WHERE fk_task=".$res->rowid.") second)) as dend  FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time WHERE fk_task = ".$res->rowid. " GROUP BY fk_task ";
    $sql1 = $db->query($requete1);
    $res1 = $db->fetch_object($sql1);
    $startDate = date('r',strtotime($res1->ddate));
    $endDate = date('r',strtotime($res1->dend));

    $typeProj="Prévu";
    $arrSerie[]=array('name' => $typeProj, 'start' => $startDate , 'end' => $endDate);

    $requete1 = "SELECT min(task_date_effective) as ddate, max(date_add(task_date_effective,interval (SELECT sum(task_duration_effective) FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective WHERE fk_task=".$res->rowid.") second)) as dend FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective WHERE fk_task = ".$res->rowid." GROUP BY fk_task ";
    $sql1 = $db->query($requete1);
    $startDate = date('r',strtotime($res1->ddate));
    $endDate = date('r',strtotime($res1->dend));
    $typeProj="Réal.";
    $arrSerie[]=array('name' => $typeProj, 'start' => $startDate , 'end' => $endDate);

    $name = utf8_encode($res->title);
    $id = $res->rowid;


    $arr[] = array('id' => $id, "name" => $name, "series" => $arrSerie);

}



    print json_encode($arr);

?>