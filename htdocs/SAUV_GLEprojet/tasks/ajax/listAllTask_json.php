<?php
/*
 
 *
 */
require_once('../../../main.inc.php');


 $action = $_REQUEST['action'];
 $project_id = $_REQUEST['projId'];
 $user_id = $_REQUEST['userId'];

$page = $_REQUEST['page']; // get the requested page
$limit = $_REQUEST['rows']; // get how many rows we want to have into the grid
$sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
$sord = $_REQUEST['sord']; // get the direction



if(!$sidx) $sidx =1; // connect to the database


$wh = "";
$searchOn = $_REQUEST['_search'];
if($searchOn=='true')
{
    $sarr = $_REQUEST;
    foreach( $sarr as $k=>$v)
    {
        switch ($k)
        {
            case 'id':
            case 'nom':
            case 'datedeb':
            case 'datefin':
                $wh .= " AND ".$k." LIKE '".$v."%'";
            break;
        }
    }
}


$SQL = "SELECT count(*) as count
          FROM ".MAIN_DB_PREFIX."Synopsis_projet_task t 
     LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet_task_actors ON fk_projet_task = t.rowid
     LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet_task_depends ON ".MAIN_DB_PREFIX."Synopsis_projet_task_depends .fk_task = t.rowid
     LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet_task_time ON ".MAIN_DB_PREFIX."Synopsis_projet_task_time.fk_task = t.rowid
     LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective ON ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective .fk_task = t.rowid
         WHERE 1 = 1 AND t.fk_task_type <> 3";
if ('x'.$project_id != "x")
{
    $SQL .= " AND t.fk_projet = ".$project_id . " ";
}

$result = $db->query($SQL." ".$wh);
$row = $db->fetch_array($result,MYSQL_ASSOC);
$count = $row['count'];
if( $count >0 )
{
    $total_pages = ceil($count/$limit);
} else {
    $total_pages = 0;
}
if ($page > $total_pages) $page=$total_pages;
$start = $limit*$page - $limit; // do not put $limit*($page - 1)
if ($start<0) $start = 0;

//Si seulement mes tâches ou si toutes les taches
//Si admin du projet ou d'un groupe => affiche les tâches filles
// get role, datedebut, fin, temps prevu, temps effectif, avancement, fille enfant et dependance


$SQL = "SELECT t.rowid as id,
               title,
               statut,
               progress,
               role,
               ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_user as acto,
               fk_depends,
               date_format(task_date,'%d/%m/%Y') as task_date,
               task_duration,
               duration_effective,
               task_duration_effective,
               task_date_effective
          FROM ".MAIN_DB_PREFIX."Synopsis_projet_task t
     LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet_task_actors ON fk_projet_task = t.rowid
     LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet_task_depends ON ".MAIN_DB_PREFIX."Synopsis_projet_task_depends .fk_task = t.rowid
     LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet_task_time ON ".MAIN_DB_PREFIX."Synopsis_projet_task_time.fk_task = t.rowid
     LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective ON ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective .fk_task = t.rowid
         WHERE 1 = 1 AND t.fk_task_type <> 3";
if ('x'.$project_id != "x")
{
    $SQL .= " AND t.fk_projet = ".$project_id . " ";
}
$SQL .= " GROUP BY t.rowid     ORDER BY $sidx $sord
         LIMIT $start , $limit";
//print $SQL; die;
$result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error());
@$responce->page = $page;
$responce->total = $total_pages;
$responce->records = $count;
$i=0;
while($row = $db->fetch_array($result,MYSQL_ASSOC))
{
    $acto = new User($db);
    $acto->id = $row['acto'];
    if($acto->id)
        $acto->fetch($acto->id);
    $responce->rows[$i]['id']=$row[id];
    $responce->rows[$i]['cell']=array($row[id],
                                      $acto->getNomUrl(1),
                                      $row[role],
                                      $row['title'],
                                      $row[statut],
                                      $row[progress],
                                      $row[task_date],
                                      sec2time(abs($row[task_duration]))
                                      );
    $i++;
}
echo json_encode($responce);


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