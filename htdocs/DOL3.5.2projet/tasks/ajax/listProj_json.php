<?php
/*
 
 */
require_once('../../../main.inc.php');

if (!isset($_REQUEST['action']))
    $_REQUEST['action'] = '';
if (!isset($_REQUEST['projetId']))
    $_REQUEST['projetId'] = '';
if (!isset($_REQUEST['extra']))
    $_REQUEST['extra'] = '';
if (!isset($_REQUEST['socid']))
    $_REQUEST['socid'] = '';

 $action = $_REQUEST['action'];
 $project_id = $_REQUEST['projetId'];
 $user_id = $_REQUEST['userId'];

 $extra = $_REQUEST['extra'];

$user->fetch($user_id);
$user->getrights();
$page = $_REQUEST['page']; // get the requested page
$limit = $_REQUEST['rows']; // get how many rows we want to have into the grid
$sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
$sord = $_REQUEST['sord']; // get the direction
$socid = $_REQUEST['socid'];
if ('x'.$socid == "")
{
    $socid = false;
}

if(!$sidx) $sidx =1; // connect to the database



$wh = "";
$searchOn = $_REQUEST['_search'];
if($searchOn=='true')
{
    $oper="";

    //Prob searchfield = statut & cntMyTask + date ???
    $searchField = $_REQUEST['searchField'];
    $searchString = $_REQUEST['searchString'];
    if ($searchField == "avanc")
    {
         $searchField = ' (SELECT avg(progress) FROM ".MAIN_DB_PREFIX."projet_task WHERE fk_projet = p.rowid) ';
    }
    if ($searchField == "cntMyTask")
    {
         $searchField = ' (SELECT count(*)
                             FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors,
                                  ".MAIN_DB_PREFIX."projet_task
                            WHERE fk_user = '.$user_id.'
                              AND ".MAIN_DB_PREFIX."projet_task.rowid = ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_projet_task
                              AND ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.type = "user"
                              AND ".MAIN_DB_PREFIX."projet_task.priority <> 3
                              AND fk_projet = p.rowid ) ';
    }
    if ($searchField == "nom"){
        $searchField = "p.title";
    }
    if ($searchField == "socname"){
        $searchField = "s.nom";
    }
    if ($searchField == "ref"){
        $searchField = "p.ref";
    }    
//    if ($searchField == "fk_user_resp"){
//        $userseek = $_REQUEST['searchString'];
//        $userReq = "SELECT DISTINCT rowid FROM ".MAIN_DB_PREFIX."user WHERE name LIKE '%$userseek%' OR firstname  LIKE '%$userseek%' OR concat(name, ' ', firstname) LIKE '%$userseek%' OR concat(firstname, ' ', name) LIKE '%$userseek%'";
//        $db->query($userReq);
//
//        $searchField = "".MAIN_DB_PREFIX."societe.nom";
//    }
    if ($_REQUEST['searchOper'] == 'eq')
    {
        $oper = '=';
        $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";
    } else if ($_REQUEST['searchOper'] == 'ne')
    {
        $oper = '<>';
        $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";
    }  else if ($_REQUEST['searchOper'] == 'lt')
    {
        $oper = '<';
        $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";
    }   else if ($_REQUEST['searchOper'] == 'gt')
    {
        $oper = '>';
        $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";
    }   else if ($_REQUEST['searchOper'] == 'le')
    {
        $oper = '<=';
        $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";
    }   else if ($_REQUEST['searchOper'] == 'ge')
    {
        $oper = '>=';
        $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";
    }   else if ($_REQUEST['searchOper'] == 'bw')
    {
        $wh .= ' AND ' . $searchField . " LIKE  '".$searchString."%'" ;
    } else if ($_REQUEST['searchOper'] == 'bn')
    {
        $wh .= ' AND ' . $searchField . " NOT LIKE  '".$searchString."%'" ;
    } else if ($_REQUEST['searchOper'] == 'in')
    {
        $wh .= ' AND ' . $searchField . " IN  ('".$searchString."')" ;
    } else if ($_REQUEST['searchOper'] == 'ni')
    {
        $wh .= ' AND ' . $searchField . " NOT IN  ('".$searchString."')" ;
    } else if ($_REQUEST['searchOper'] == 'ew')
    {
        $wh .= ' AND ' . $searchField . " LIKE  '%".$searchString."'" ;
    } else if ($_REQUEST['searchOper'] == 'en')
    {
        $wh .= ' AND ' . $searchField . " NOT LIKE  '%".$searchString."'" ;
    } else if ($_REQUEST['searchOper'] == 'cn')
    {
        $wh .= ' AND ' . $searchField . " LIKE  '%".$searchString."%'" ;
    } else if ($_REQUEST['searchOper'] == 'nc')
    {
        $wh .= ' AND ' . $searchField . " NOT LIKE  '%".$searchString."%'" ;
    }
}


$SQL = "SELECT count(*) as cnt
          FROM ".MAIN_DB_PREFIX."Synopsis_projet_view as p
     LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = p.fk_soc";
    if (!$user->rights->societe->client->voir && !$socid) $SQL .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$SQL .= "
         WHERE 1 = 1";
if (!$user->rights->societe->client->voir && !$socid) $SQL .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)
{
  $SQL .= " AND s.rowid = ".$socid;
}
if ($extra == "viewmine")
{
    $SQL .= " AND p.fk_user_resp =  ".$user_id . " ";
}
//$SQL .= "   AND ".MAIN_DB_PREFIX."societe.rowid = p.fk_soc ".$wh;
$SQL .= $wh;

$result = $db->query($SQL);
$row = $db->fetch_array($result,MYSQL_ASSOC);
 $count = $row['cnt'];
        if( $count >0 )
        {
            $total_pages = ceil($count/$limit);
        } else {
            $total_pages = 0;
        }
        if ($page > $total_pages) $page=$total_pages;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
//avancement du projet,
//nb de tache pour moi/en tout ,
//date debut,
//date fin,
//role

$SQL = "SELECT p.rowid as id,
               p.title as nom, 
               p.ref as ref,
               date_format(p.dateo,'%d/%m/%Y') as dateo,
               (SELECT avg(progress) FROM ".MAIN_DB_PREFIX."projet_task
                                    WHERE fk_projet = p.rowid) as avanc, p.fk_statut as statut,
               s.rowid as socid,
               s.nom as socname";

if (!$user->rights->societe->client->voir && !$socid) $SQL .= ", sc.fk_soc, sc.fk_user";
$SQL .= "
               , (SELECT count(*)
                  FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors,
                       ".MAIN_DB_PREFIX."projet_task
                 WHERE fk_user = $user_id
                   AND ".MAIN_DB_PREFIX."projet_task.rowid = ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_projet_task
                   AND ".MAIN_DB_PREFIX."projet_task.priority <> 3
                   AND ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.type = 'user'
                   AND fk_projet = p.rowid ) as cntMyTask,
               p.fk_user_resp
          FROM ";
    if (!$user->rights->societe->client->voir && !$socid) $SQL .= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
$SQL .= " ".MAIN_DB_PREFIX."Synopsis_projet_view as p ";
$SQL .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = p.fk_soc";
$SQL .= "
         WHERE 1 = 1";
if (!$user->rights->societe->client->voir && !$socid) $SQL .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)
{
  $SQL .= " AND s.rowid = ".$socid;
}
if ($extra == "viewmine")
{
    $SQL .= " AND p.fk_user_resp =  ".$user_id . " ";
}

//$SQL .= "   AND ".MAIN_DB_PREFIX."societe.rowid = p.fk_soc

if($sidx == "dateo")
    $sidx = "p.dateo";

$SQL .= " ".$wh."
      ORDER BY $sidx $sord
         LIMIT $start , $limit";
//print $SQL;
$result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error());
@$responce->page = $page;
$responce->total = $total_pages;
$responce->records = $count;
$i=0;
while($row = $db->fetch_array($result,MYSQL_ASSOC))
{
    $soc= new Societe($db);
    $soc->fetch($row['socid']);
    $responce->rows[$i]['id']=$row['id'];
    $localUser = new User($db);
    $localUser->fetch($row['fk_user_resp']);
    require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
    $proj = new Projet($db);
    $proj->fetch($row['id']);
    $overallprogress = '<div class="progressbar ui-corner-all">'.round($row['avanc']).'</div>';
    $responce->rows[$i]['cell']=array($row['id'],
                                      "<a href='".DOL_URL_ROOT."/projet/card.php?id=".$row['id']."'>".$row['nom']."</a>",
                                      "<a href='".DOL_URL_ROOT."/projet/card.php?id=".$row['id']."'>".$row['ref']."</a>",
                                      $row['dateo'],
                                      $proj->getLibStatut(4),
                                      $overallprogress,
                                      $soc->getNomUrl(1),
                                      $row['cntMyTask'],
                                      $localUser->getNomUrl(1));
    $i++;
}
echo json_encode($responce);


?>