<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 26 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : importHisto-json_response.php
  * GLE-1.2
  */

  $id=$_REQUEST["id"];
   require_once('../../../main.inc.php');

   require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");

    $page = $_GET['page'];
    $limit = $_GET['rows'];
    $sidx = $_GET['sidx'];
    $sord = $_GET['sord'];
    if(!$sidx) $sidx =1;


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



    $result = $db->query("SELECT COUNT(*) AS count FROM BIMP_import_history WHERE 1=1 ".$wh);
    $row = $db->fetch_object($result,MYSQL_ASSOC);
    $count = $row->count;
    if( $count >0 )
    {
        $total_pages = ceil($count/$limit);
    } else {
        $total_pages = 0;
    }
    if ($page > $total_pages)
        $page=$total_pages;
    $start = $limit*$page - $limit;
    // do not put $limit*($page - 1)
    if ($start < 0) $start=0;

    $SQL = "SELECT * FROM BIMP_import_history WHERE 1=1 ".$wh."  ORDER BY $sidx $sord LIMIT $start , $limit";
    $result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error() . $SQL);
    $responce->page = $page;
    $responce->total = $total_pages;
    $responce->records = $count;
    $i=0;
    while($row = $db->fetch_object($result,MYSQL_ASSOC))
    {
        $responce->rows[$i]['id']=$row->rowid;
        $responce->rows[$i]['cell']=array($row->id,
                                           utf8_encode($row->filename),
                                           $row->datec,
                                           "<button class='butAction' onClick='sendMail(".$row->id.");'>(R)envoyer Mail</button>",
                                           "<button class='butAction' onClick='displayReport(".$row->id.");'>Afficher</button>");
        $i++;
    }
        echo json_encode($responce);

?>
