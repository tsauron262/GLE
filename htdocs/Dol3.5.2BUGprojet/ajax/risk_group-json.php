<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 30 sept. 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : risk_group-json.php
  * GLE-1.1
  */

  $curGrp = $_REQUEST['curGrp'];

  require_once('../../master.inc.php');
$action = $_REQUEST['oper'];

$requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk WHERE fk_risk_group = ".$curGrp;

$sql = $db->query($requete);
$res = $db->fetch_object($sql);
$occurence = $res->occurence;
$importance = $res->gravite;
$description = utf8_encode(html_entity_decode($res->description)) ." ";

switch($action)
{
    case "in":
      $requete = "SELECT ".MAIN_DB_PREFIX."projet_task.title,
                         ".MAIN_DB_PREFIX."projet_task.note,
                         ".MAIN_DB_PREFIX."projet_task.fk_task_parent,
                         ".MAIN_DB_PREFIX."projet_task.level,
                         ".MAIN_DB_PREFIX."projet_task.rowid as tid,
                         ".MAIN_DB_PREFIX."projet_task.priority as priority
                    FROM ".MAIN_DB_PREFIX."projet_task,
                         ".MAIN_DB_PREFIX."Synopsis_projet_li_task_group,
                         ".MAIN_DB_PREFIX."Synopsis_projet_risk_group
                   WHERE ".MAIN_DB_PREFIX."Synopsis_projet_risk_group.id = ".$curGrp ."
                     AND ".MAIN_DB_PREFIX."Synopsis_projet_li_task_group.fk_task = ".MAIN_DB_PREFIX."projet_task.rowid
                     AND ".MAIN_DB_PREFIX."Synopsis_projet_li_task_group.fk_group_risk = ".MAIN_DB_PREFIX."Synopsis_projet_risk_group.id ";
      $sql = $db->query($requete);
      $responce;
      $count =0;
      $total = 0;
      require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
      $projet = new Project($db);

      while ($res=$db->fetch_object($sql))
      {
         $total += $projet->costTask($res->tid);
         $responce->rows[$count]['id']=$res->tid;
         $responce->rows[$count]['groupDet']=array('name' => utf8_encode(html_entity_decode($res->title)), "desc" => utf8_encode(html_entity_decode($res->note)), "fk_task_parent" => $res->fk_task_parent, "level" => $res->level,'type' => $res->priority);
         $count ++;
      }
      $totalRisk = $total * ($occurence + $importance) / 200;

      $responce->data['occurence'] = $occurence;
      $responce->data['importance'] = $importance;
      $responce->data['total'] = price(round($total));
      $responce->data['totalRisque'] = price(round($totalRisk));
      $responce->data['description'] = $description;

       echo json_encode($responce);
    break;
    default:
    case "notin":
      $requete = "SELECT ".MAIN_DB_PREFIX."projet_task.title,
                         ".MAIN_DB_PREFIX."projet_task.note,
                         ".MAIN_DB_PREFIX."projet_task.fk_task_parent,
                         ".MAIN_DB_PREFIX."projet_task.level,
                         ".MAIN_DB_PREFIX."projet_task.rowid as tid,
                         ".MAIN_DB_PREFIX."projet_task.priority as priority
                    FROM ".MAIN_DB_PREFIX."projet_task
               LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet_li_task_group ON ".MAIN_DB_PREFIX."Synopsis_projet_li_task_group.fk_task = ".MAIN_DB_PREFIX."projet_task.rowid
                                                   AND ".MAIN_DB_PREFIX."Synopsis_projet_li_task_group.fk_group_risk = ".$curGrp."
                   WHERE ".MAIN_DB_PREFIX."Synopsis_projet_li_task_group.fk_task is null OR ".MAIN_DB_PREFIX."projet_task.priority = 3" ;
      $sql = $db->query($requete);
      $responce;
      $count =0;
      $total = 0;
      require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
      $projet = new Project($db);
      while ($res=$db->fetch_object($sql))
      {
         $total += $projet->costTask($res->tid);
         $responce->rows[$count]['id']=$res->tid;
         $responce->rows[$count]['groupDet']=array('name' => utf8_encode(html_entity_decode($res->title)), "desc" => utf8_encode(html_entity_decode($res->note)), "fk_task_parent" => $res->fk_task_parent, "level" => $res->level, "type" => $res->priority );
         $count ++;
      }
      $totalRisk = $total * ($occurence + $importance) / 200;

      $responce->data['occurence'] = $occurence;
      $responce->data['importance'] = $importance;
      $responce->data['total'] = price(round($total));
      $responce->data['totalRisque'] = price(round($totalRisk));
      $responce->data['description'] = $description."";

      echo json_encode($responce);

    break;
}
?>