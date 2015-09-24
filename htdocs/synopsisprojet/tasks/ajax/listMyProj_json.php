<?php
/*

 */
require_once('../../../main.inc.php');


 $action = $_REQUEST['action'];
 $project_id = $_REQUEST['projetId'];
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


$result = $db->query("SELECT COUNT(*) AS count FROM ".MAIN_DB_PREFIX."Synopsis_projet_view WHERE 1=1 ".$wh);
$row = $db->fetch_array($result,MYSQL_ASSOC);
$count = $row['count'];
if( $count >0 )
{
    $total_pages = ceil($count/$limit);
} else {
    $total_pages = 10;
}
if ($page > $total_pages) $page=$total_pages;
$start = $limit*$page - $limit; // do not put $limit*($page - 1)
if ($start<0) $start = 0;

//avancement du projet,
//nb de tache pour moi/en tout ,
//date debut,
//date fin,
//role

$SQL = "SELECT ".MAIN_DB_PREFIX."Synopsis_projet_view.rowid as id,
               ".MAIN_DB_PREFIX."Synopsis_projet_view.label as nom,
               ".MAIN_DB_PREFIX."Synopsis_projet_view.dateo,
               ".MAIN_DB_PREFIX."Synopsis_projet_view.fk_statut,
               ".MAIN_DB_PREFIX."societe.nom as socname,
               (SELECT count(*)
                  FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors,
                       ".MAIN_DB_PREFIX."projet_task
                 WHERE ".MAIN_DB_PREFIX."projet_task.rowid = ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_projet_task
                   AND ".MAIN_DB_PREFIX."projet_task.priority <> 3
                   AND fk_projet = ".MAIN_DB_PREFIX."Synopsis_projet_view.rowid ) as cntPeople
          FROM ".MAIN_DB_PREFIX."societe, ".MAIN_DB_PREFIX."Synopsis_projet_view
         WHERE 1 = 1
           AND ".MAIN_DB_PREFIX."Synopsis_projet_view.fk_user_resp = $user_id
           AND ".MAIN_DB_PREFIX."societe.rowid = ".MAIN_DB_PREFIX."Synopsis_projet_view.fk_soc
           ".$wh."
      ORDER BY $sidx $sord
         LIMIT $start , $limit";
//print $SQL;
$result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error());
$responce->page = $page;
$responce->total = $total_pages;
$responce->records = $count;
$i=0;
while($row = $db->fetch_array($result,MYSQL_ASSOC))
{
    $responce->rows[$i]['id']=$row[id];
    $responce->rows[$i]['cell']=array($row[id],$row['nom'],$row[dateo],$row[statut],$row[socname],$row[cntPeople],);
    $i++;
}
echo json_encode($responce);


?>