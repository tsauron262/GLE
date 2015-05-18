<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 10 aout 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : ressource_json.php
  * GLE-1.0
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
        $SQL = "SELECT count(*) as cnt FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE ".MAIN_DB_PREFIX."user.rowid = ".MAIN_DB_PREFIX."Synopsis_global_ressources.fk_user_resp " . $wh;

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

        //avancement du projet,
        //nb de tache pour moi/en tout ,
        //date debut,
        //date fin,
        //role

        $SQL = "SELECT ".MAIN_DB_PREFIX."Synopsis_global_ressources.id,
                       ".MAIN_DB_PREFIX."Synopsis_global_ressources.nom,
                       ".MAIN_DB_PREFIX."Synopsis_global_ressources.fk_user_resp,
                       ".MAIN_DB_PREFIX."Synopsis_global_ressources.description,
                       ".MAIN_DB_PREFIX."Synopsis_global_ressources.fk_parent_ressource,
                       ".MAIN_DB_PREFIX."Synopsis_global_ressources.date_achat,
                       ".MAIN_DB_PREFIX."Synopsis_global_ressources.valeur,
                       ".MAIN_DB_PREFIX."Synopsis_global_ressources.cout,
                       OCTET_LENGTH(".MAIN_DB_PREFIX."Synopsis_global_ressources.photo) as sizePhoto,
                       (SELECT nom FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE id = fk_parent_ressource) as categorie
                  FROM  ".MAIN_DB_PREFIX."user,".MAIN_DB_PREFIX."Synopsis_global_ressources
";
        if ($projId."x" !="x")
        {
            $SQL .= " INNER JOIN ".MAIN_DB_PREFIX."Synopsis_projet_ressources ON  ".MAIN_DB_PREFIX."Synopsis_projet_ressources.fk_global_ressource = ".MAIN_DB_PREFIX."Synopsis_global_ressources.id ";
        }
        $SQL .= "
                 WHERE ".MAIN_DB_PREFIX."user.rowid = ".MAIN_DB_PREFIX."Synopsis_global_ressources.fk_user_resp";

        if ("x".$parentId != "x")
        {
            $SQL .= " AND ".MAIN_DB_PREFIX."Synopsis_global_ressources.fk_parent_ressource = ".$parentId;
        } else {
            $SQL .= " AND ".MAIN_DB_PREFIX."Synopsis_global_ressources.fk_parent_ressource is null ";
        }
        $SQL .= " AND ".MAIN_DB_PREFIX."Synopsis_global_ressources.isGroup = 0 ";

        $SQL .= "  ".$wh."
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
            $tmpUser = new User($db);
            $tmpUser->fetch($row[fk_user_resp]);
            if ($row[sizePhoto] > 0)
            {
            $img = "<img style='max-height:200px;' src='ajax/photo_ressource.php?ressource_id=".$row[id]."' />";

            } else {
            $img = "<div onClick='newPhoto(this,".$row[id].")'>Ajouter une photo</div>";
            }



            $responce->rows[$i]['cell']=array($row[id],
                                              $row[nom],
                                              $row[categorie],
                                              $tmpUser->getNomUrl(1),
                                              $row[description],
                                              $row[date_achat],
                                              price(round($row[valeur],2)) . "&euro;",
                                              price(round($row[cout],2)) . "&euro;",
                                              $img,
                                              );
            $i++;
        }
        echo json_encode($responce);
    }
    break;
}


?>
