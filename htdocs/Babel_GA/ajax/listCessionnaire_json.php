<?php
/*
  ** GLE by Synopsis et DRSI
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
require_once(DOL_DOCUMENT_ROOT."/prospect.class.php");

$langs->load("propal");
$langs->load("synopsisGene@Synopsis_Tools");
$langs->load('companies');
$langs->load('commercial');
$langs->load("synopsisGene@Synopsis_Tools");

 $user_id = $_REQUEST['userId'];

 $action = $_REQUEST['action'];

$user->id = $user_id;
$user->fetch();
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
    if ($searchField == 's.nom')
    {
        $searchField = "s.rowid";
    }
    if ($searchField == 'departement')
    {
        $searchField = "s.fk_departement";
    }
    if ($searchField == "s.datec")
    {
        $searchField = "date_format(s.datec,'%Y-%m-%d')";
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


switch ($action)
{
    default :
    {
        $SQL = "SELECT count(*) as cnt
                  FROM ".MAIN_DB_PREFIX."societe
                 WHERE ".MAIN_DB_PREFIX."societe.cessionnaire = 1 " . $wh;
        if ("x".$parentId != "x")
                {
                    $SQL .= " AND fk_parent_ressource = ".$parentId;
                } else {
                    $SQL .= " AND fk_parent_ressource is null ";
                }
                $SQL .= " AND isGroup = 0 ";

        $SQL .= "  ".$wh;
//        print $SQL;
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



        $sql = "SELECT s.rowid, s.nom as socname, s.ville, s.datec  as datec, s.datea as datea,";
        $sql.= " st.libelle as stcomm, s.prefix_comm, s.fk_stcomm, s.fk_prospectlevel,";
        $sql.= " d.nom as departement";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_stcomm as st";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= ", ".MAIN_DB_PREFIX."societe as s";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d on (d.rowid = s.fk_departement)";
        $sql.= " WHERE s.fk_stcomm = st.id AND s.cessionnaire = 1";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

        if (isset($stcomm) && $stcomm != '')
        {
            $sql .= " AND s.fk_stcomm=".$stcomm;
        }
        if ($user->societe_id)
        {
            $sql .= " AND s.rowid = " .$user->societe_id;
        }


        $sql .= "  ".$wh."
                ORDER BY $sidx $sord
                LIMIT $start , $limit";
//print $sql;
        $result = $db->query( $sql ) or die("Couldn t execute query : ".$sql.".".mysql_error());
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i=0;
        require_once(DOL_DOCUMENT_ROOT."/Babel_GA/cessionnaire.class.php");
        $cession=new Cessionnaire($db);
        $cession->cessionnaire=1;

        while($obj = $db->fetch_object($result))
        {
            $cession->fetch($obj->rowid);
//->getNomUrl(1)
            $responce->rows[$i]['cell']=array($obj->rowid,
                                              "<div style='padding: 2px 10px 2px 10px;'>".utf8_encode($cession->getNomUrl(1))."</div>",
                                              $obj->datec,
                                              utf8_encode(  $obj->ville),
                                              utf8_encode(  $obj->departement),
                                              );
            $i++;
        }
        echo json_encode($responce);
    }
    break;
}


?>