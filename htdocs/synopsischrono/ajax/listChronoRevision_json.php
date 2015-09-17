<?php
/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 10 mars 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : listContrat_json.php
  * GLE-1.1
  */


require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/synopsischrono/class/chrono.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");

$langs->load("synopsisGene@synopsistools");
$langs->load("chrono@synopsischrono");

 $user_id = $_REQUEST['userId'];

 $action = $_REQUEST['action'];

$user->fetch($user_id);
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
    if ($searchField == 'p.name')
    {
        $searchField = "CONCAT(name,' ' ,firstname)";
    }
    if ($searchField == "c.date_create")
    {
        $searchField = "date_format(c.date_create,'%Y-%m-%d')";
        if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$searchString,$arr))
        {
            $searchString = $arr[3].'-'.$arr[2].'-'.$arr[1];
        }
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
//print $wh;

if ($_REQUEST['c_model_refid'] > 0)
{
    $searchString = $_REQUEST['c_model_refid'] ;
    $searchField='c.model_refid';
    $oper = '=';
    $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";

}
if ($_REQUEST['chrono_refid'] > 0)
    $wh .= " AND revision is not null AND orig_ref IN (SELECT ref FROM ".MAIN_DB_PREFIX."synopsischrono WHERE id = ".$_REQUEST['chrono_refid'].")";

switch ($action)
{
    default :
    {

        $sql = "SELECT count(*) as cnt";
        $sql .= " FROM ".MAIN_DB_PREFIX."synopsischrono as st";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = st.fk_soc";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as p ON p.rowid = st.fk_socpeople";
        $sql.= " WHERE 1=1 ";

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



        $sql = "SELECT c.id, c.tms as date_modify, c.date_create";
        $sql .= " FROM ".MAIN_DB_PREFIX."synopsischrono as c ";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as p ON p.rowid = c.fk_socpeople";
        $sql.= " WHERE 2 = 2";

        $sql .= "  ".$wh."
                ORDER BY $sidx $sord
                LIMIT $start , $limit";

        $result = $db->query( $sql ) or die("Couldn t execute query : ".$sql.".".mysql_error());
        class geneClass{}
        
        $responce = new geneClass();
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i=0;

        $chrono = new Chrono($db);
        $tmpSoc = new Societe($db);
        $contact = new Contact($db);


        while($obj = $db->fetch_object($result))
        {
            $chrono->fetch($obj->id);

            $responce->rows[$i]['cell']=array($obj->id,
                                              $chrono->getNomUrl(1),
                                              $obj->date_create,
                                              $obj->date_modify,
                                              $chrono->getLibStatut(4),
                                              $chrono->revision,
                                             );
            
            $responce->rows[$i]['cell'][] = count_files($conf->synopsischrono->dir_output . "/" . $chrono->id);
            $i++;
        }
        echo json_encode($responce);
    }
    break;
}

function count_files($dir) {
    $num = 0;
    if (is_dir($dir)) {
        $dir_handle = opendir($dir);
        while ($entry = readdir($dir_handle))
            if (is_file($dir . '/' . $entry))
                $num++;

        closedir($dir_handle);
    }
    return $num;
}
?>