<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 27 dec. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : listProcess_json.php
  * GLE-1.2
  */



require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/Synopsis_Process/class/process.class.php");

$langs->load("process@Synopsis_Process");

 //$user_id = $_REQUEST['userId'];

 $action = $_REQUEST['action'];

if($user_id)
    $user->id = $user_id;
$user->fetch($user->id);
$user->getrights();
$page = $_REQUEST['page']; // get the requested page
$limit = $_REQUEST['rows']; // get how many rows we want to have into the grid
$sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
$sord = $_REQUEST['sord']; // get the direction

if(!$sidx) $sidx =1; // connect to the database


$wh = "";
$searchOn = $_REQUEST['_search'];
if($searchOn=='true')
{
    $oper="";
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


switch ($action)
{
    default :
    {

        $sql = "SELECT count(*) as cnt";
        $sql .= " FROM " . MAIN_DB_PREFIX . "Synopsis_Process";
        $sql.= " WHERE 1 = 1";

        $sql .= "  ".$wh;
//        print $SQL;
        $result = $db->query($sql);
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



        $sql = "SELECT f.id,
                       f.label,
                       f.description,
                       f.fk_statut";
        $sql .= " FROM " . MAIN_DB_PREFIX . "Synopsis_Process as f";
        $sql.= " WHERE 1 = 1";


        $sql .= "  ".$wh."
                ORDER BY $sidx $sord
                LIMIT $start , $limit";

        $result = $db->query( $sql ) or die("Couldn t execute query : ".$sql.".".mysql_error());
        @$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i=0;
        $process = new process($db);

        while($obj = $db->fetch_object($result))
        {
            $process->fetch($obj->id);

            $responce->rows[$i]['cell']=array($process->id,
                                              utf8_encode($process->getNomUrl(1)),
                                              utf8_encode($process->description),
                                              utf8_encode($process->getLibStatut(5)),
                                              );
            $i++;
        }
        echo json_encode($responce);
    }
    break;
}
?>