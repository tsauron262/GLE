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
  * Name : risquesGlobaux_json.php
  * GLE-1.1
  */

//TODO left menu + lib/*php

require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');


$langs->load('companies');
$langs->load('commercial');
$langs->load("synopsisGene@synopsistools");

 $parentId = $_REQUEST['parent'];
 $user_id = $_REQUEST['userId'];

 $action = $_REQUEST['action'];
 $projId = $_REQUEST['projId'];

$user->fetch($user_id);
$user->getrights();
$page = $_REQUEST['page']; // get the requested page
$limit = $_REQUEST['rows']; // get how many rows we want to have into the grid
$sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
$sord = $_REQUEST['sord']; // get the direction
if ('x'.$socid == "")
{
    $socid = false;
}

if(!$sidx) $sidx =1; // connect to the database

if ($sidx == "fk_user_resp")
{
        $sidx == ' CONCAT(".MAIN_DB_PREFIX."user.lastname . " ".".MAIN_DB_PREFIX."user.firstname) ';

}

$wh = "";
$searchOn = $_REQUEST['_search'];
if($searchOn=='true')
{
    $oper="";
    $searchField = $_REQUEST['searchField'];
    $searchString = $_REQUEST['searchString'];
    if ($searchField == "fk_user_resp")
    {
        $searchField == ' CONCAT(".MAIN_DB_PREFIX."user.firstname . " ".".MAIN_DB_PREFIX."user.lastname) ';
    }

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

        $SQL = "SELECT count(*) as cnt
                  FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk
                 WHERE fk_projet = ".$projId."
                   AND fk_task is null";

        $SQL .= "  ".$wh;
        $count = 0;
        $sql = $db->query($SQL);
        $res = $db->fetch_object($sql);
        $count = $res->cnt;
//        print $SQL;
        if( $count >0 )
        {
            $total_pages = ceil($count/$limit);
        } else {
            $total_pages = 0;
        }
        if ($page > $total_pages) $page=$total_pages;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;



        $SQL = "SELECT ".MAIN_DB_PREFIX."Synopsis_projet_risk.rowid,
                       ".MAIN_DB_PREFIX."Synopsis_projet_risk.occurence,
                       ".MAIN_DB_PREFIX."Synopsis_projet_risk.gravite,
                       ".MAIN_DB_PREFIX."Synopsis_projet_risk.nom,
                       ".MAIN_DB_PREFIX."Synopsis_projet_risk.description,
                       ".MAIN_DB_PREFIX."Synopsis_projet_risk.cout,
                       (".MAIN_DB_PREFIX."Synopsis_projet_risk.cout * (".MAIN_DB_PREFIX."Synopsis_projet_risk.occurence + gravite) / (100 * 2)) as coutRisk
                  FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk
                 WHERE fk_projet = ".$projId."
                   AND fk_risk_group is null";

        $SQL .= "  ".$wh."
              ORDER BY $sidx $sord
                 LIMIT $start , $limit";

        $result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error());
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i=0;
        while($row = $db->fetch_array($result,MYSQL_ASSOC))
        {

            $responce->rows[$i]['cell']=array($row[rowid],
                                              $row[nom],
                                              $row[description],
                                              $row[occurence],
                                              $row[gravite],
                                              $row[cout],
                                              $row[coutRisk],
                                              );
            $i++;
        }
        echo json_encode($responce);
    }
    break;
}


?>
