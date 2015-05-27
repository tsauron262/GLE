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



//if(!isset($_REQUEST['filtre']) && isset($_REQUEST['filtre2']))
//  $_REQUEST['filtre'] = $_REQUEST['filtre2'];


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
    if ($searchField == "date_create")
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

function searchdate($nom, $pref = ''){
    $wh = '';
    $searchString = $_REQUEST[$nom] ;
    $searchField=$pref.$nom;
    $searchString = explode("/", $searchString);
    $searchString = $searchString[2]."-".$searchString[1]."-".$searchString[0];
    $oper = '>';
    $oper2 = '<';
    $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";
    $wh .=  " AND " . "ADDDATE(".$searchField.", INTERVAL -1 DAY)  ".$oper2." '".$searchString."'";
    return $wh;    
}
function searchtext($nom, $pref = ''){
    $searchString = $_REQUEST[$nom] ;
    $searchField=$pref.$nom;
    $oper = 'LIKE';
    return  " AND " . $searchField . " ".$oper." '%".$searchString."%'";    
}

if ($_REQUEST['date_create'] > 0)
    $wh .= searchdate('date_create');
if ($_REQUEST['tms'] > 0)
    $wh .= searchdate('tms', 'c.');

if ($_REQUEST['model_refid'] > 0)
{
    $searchString = $_REQUEST['model_refid'] ;
    $searchField='c.model_refid';
    $oper = '=';
    $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";

}

if (isset($_REQUEST['fk_statut']) && $_REQUEST['fk_statut'] > -1)
{
    $searchString = $_REQUEST['fk_statut'] ;
    if(!$searchString > 0)
        $searchString = "0";
    $searchField='fk_statut';
    $oper = '=';
    $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";

}


if ($_REQUEST['ref'] > 0)
    $wh .= searchtext('ref');

if ($_REQUEST['type'] > 0)
{
    $searchString = $_REQUEST['type'] ;
    $searchField='model_refid';
    $oper = '=';
    $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";
}

$wh .= " AND (revision is NULL || revision = 0) ";


//if(isset($_REQUEST['filtre']))
//    $wh .= "AND (id IN (SELECT chrono_refid 
//FROM  `llx_synopsischrono_value` 
//WHERE  `value` LIKE  '%".$_REQUEST['filtre']."%') OR ref LIKE  '%".$_REQUEST['filtre']."%' OR description LIKE  '%".$_REQUEST['filtre']."%' OR nom LIKE  '%".$_REQUEST['filtre']."%') ";


if(isset($_REQUEST['filtre']))
    $wh .= "AND (ref LIKE  '%".$_REQUEST['filtre']."%' OR description LIKE  '%".$_REQUEST['filtre']."%' OR nom LIKE  '%".$_REQUEST['filtre']."%') ";

switch ($action)
{
    default :
    {

        $sql = "SELECT count(*) as cnt";
        $sql .= " FROM ".MAIN_DB_PREFIX."synopsischrono as c";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_societe";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as p ON p.rowid = c.fk_socpeople";
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



        $sql = "SELECT c.id, c.fk_societe, c.fk_socpeople, c.date_create, c.tms";
        $sql .= " FROM ".MAIN_DB_PREFIX."synopsischrono as c ";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_societe";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as p ON p.rowid = c.fk_socpeople";
        $sql.= " WHERE 2 = 2";

        $sql .= "  ".$wh."
                ORDER BY $sidx $sord
                LIMIT $start , $limit";

        $result = $db->query( $sql ) or die("Couldn t execute query : ".$sql.".".mysql_error());
        class general{}
        $responce = new general();
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
            if ($obj->fk_societe > 0)
                $tmpSoc->fetch($obj->fk_societe);
            if ($obj->fk_socpeople > 0)
                $contact->fetch($obj->fk_socpeople);
            $hasRev = false;
            if ($chrono->model->hasRevision == 1 )
            {
                $requete = "SELECT * FROM ".MAIN_DB_PREFIX."synopsischrono WHERE revision IS NOT NULL AND  orig_ref = '".$chrono->ref."' AND ref != orig_ref";
                $sql = $db->query($requete);
                if ($db->num_rows($sql) > 0) $hasRev = true;
            }
            if ($conf->global->CHRONO_DISPLAY_SOC_AND_CONTACT)
                $responce->rows[$i]['cell']=array($obj->id,
                                                  ($hasRev?'<div class="hasRev">1</div>':'<div class="hasRev">0</div>'),
                                                  utf8_encodeRien($chrono->getNomUrl(1)),
                                                  htmlspecialchars($chrono->model->titre),
                                                  utf8_encodeRien(($obj->fk_societe > 0?$tmpSoc->getNomUrl(1):'')),
                                                  utf8_encodeRien(($obj->fk_socpeople > 0?$contact->getNomUrl(1):'')),
                                                  $obj->date_create,
                                                  $obj->tms,
                                                  $chrono->getLibStatut(4)
                                                 );
            else
                $responce->rows[$i]['cell']=array($obj->id,
                                                  ($hasRev?'<div class="hasRev">1</div>':'<div class="hasRev">0</div>'),
                                                  utf8_encodeRien($chrono->getNomUrl(1)),
                                                  htmlspecialchars($chrono->model->titre),
                                                  ($obj->date_create? $obj->date_create : ""),
                                                  ($obj->tms? $obj->tms : ""),
                                                  $chrono->getLibStatut(4)
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
