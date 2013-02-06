<?php
/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
require_once('../../main.inc.php');


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
          FROM Babel_societe_prop_history ";
$SQL .= "
         WHERE 1 = 1";
  $SQL .= " AND Babel_societe_prop_history.societe_refid = ".$socid;

$SQL .= "  ".$wh;
//print $SQL;
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

$SQL = "SELECT Babel_societe_prop_history.id,
               Babel_societe_prop_history.note,
               date_format(Babel_societe_prop_history.dateNote,'%d/%m/%Y') as dateNote,
               Babel_societe_prop_history.source,
               Babel_societe_prop_history.importance
          FROM Babel_societe_prop_history ";
$SQL .= "
         WHERE 1 = 1";
  $SQL .= " AND Babel_societe_prop_history.societe_refid = ".$socid;

$SQL .= "  ".$wh."
      ORDER BY $sidx $sord
         LIMIT $start , $limit";
//print $SQL;

$convetImp[0]="Aucune";
$convetImp[1]="Faible";
$convetImp[2]="Moyenne";
$convetImp[3]="&Eacute;lev&eacute;";
$convetImp[4]="Important";

$result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error());
$responce->page = $page;
$responce->total = $total_pages;
$responce->records = $count;
$i=0;
while($row = $db->fetch_array($result,MYSQL_ASSOC))
{
    $soc= new Societe($db);
    $soc->fetch($row[socid]);
    $responce->rows[$i]['id']=$row[id];
    $localUser = new User($db);
    $localUser->fetch($row[fk_user_resp]);
    $overallprogress = '<div class="progressbar ui-corner-all">'.round($row[statut]).'</div>';
    $responce->rows[$i]['cell']=array($row[id],$convetImp[$row[importance]],$row[source],$row[dateNote],utf8_encode($row[note]));
    $i++;
}
echo json_encode($responce);


?>