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

        $SQL = "SELECT DISTINCT ".MAIN_DB_PREFIX."Synopsis_global_ressources.id,
                       ".MAIN_DB_PREFIX."Synopsis_global_ressources.nom,
                       ".MAIN_DB_PREFIX."Synopsis_global_ressources.description,
                       OCTET_LENGTH(".MAIN_DB_PREFIX."Synopsis_global_ressources.photo) as sizePhoto,
                       (SELECT nom FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE id = fk_parent_ressource) as categorie
                  FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa,
                       ".MAIN_DB_PREFIX."user,
                       ".MAIN_DB_PREFIX."Synopsis_global_ressources ";
        $SQL .= "WHERE ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.fk_ressource =   ".MAIN_DB_PREFIX."Synopsis_global_ressources.id
                   AND ".MAIN_DB_PREFIX."user.rowid = ".MAIN_DB_PREFIX."Synopsis_global_ressources.fk_user_resp";
        $SQL .= "  AND ".MAIN_DB_PREFIX."Synopsis_global_ressources.isGroup = 0
                   AND ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.fk_projet =  ".$projId;

        $SQL .= "  ".$wh;
        $count = 0;
        $sql = $db->query($SQL);
        while ($res = $db->fetch_object($sql))
        {
            $count ++;
        }
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

        //avancement du projet,
        //nb de tache pour moi/en tout ,
        //date debut,
        //date fin,
        //role
        require_once(DOL_DOCUMENT_ROOT."/Synopsis_Hrm/hrm.class.php");
        $hrm = new hrm($db);
        $arrFerie = $hrm->jourFerie();
        $SQL = "SELECT DISTINCT ".MAIN_DB_PREFIX."Synopsis_global_ressources.id,
                       ".MAIN_DB_PREFIX."Synopsis_global_ressources.nom,
                       ".MAIN_DB_PREFIX."Synopsis_global_ressources.description,
                       OCTET_LENGTH(".MAIN_DB_PREFIX."Synopsis_global_ressources.photo) as sizePhoto,
                       (SELECT nom FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE id = fk_parent_ressource) as categorie,
                       ".MAIN_DB_PREFIX."Synopsis_global_ressources.cout,
                       ".MAIN_DB_PREFIX."Synopsis_global_ressources.fk_resa_type
                  FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa,
                       ".MAIN_DB_PREFIX."user,
                       ".MAIN_DB_PREFIX."Synopsis_global_ressources
                 WHERE ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.fk_ressource =   ".MAIN_DB_PREFIX."Synopsis_global_ressources.id
                   AND ".MAIN_DB_PREFIX."user.rowid = ".MAIN_DB_PREFIX."Synopsis_global_ressources.fk_user_resp
                   AND ".MAIN_DB_PREFIX."Synopsis_global_ressources.isGroup = 0
                   AND ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.fk_projet =  ".$projId;

        $SQL .= "  ".$wh."
              ORDER BY $sidx $sord
                 LIMIT $start , $limit";
//        print $SQL;

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
                $img = "<div>Pas de photo</div>";
            }
            $cout = 0;
            $count=0;



            $requete1 = "SELECT ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.id,
                                ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.datedeb,
                                ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.datefin,
                                unix_timestamp(".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.datedeb) as datedebF,
                                unix_timestamp(".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.datefin) as datefinF,
                                ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.fk_user_imputation,
                                ifnull(".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.fk_projet_task,-1) as taskId
                          FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa
                         WHERE fk_ressource = " . $row[id] ."
                           AND ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.fk_projet =  ".$projId;
            $sql1 = $db->query($requete1);
            while ($res1 = $db->fetch_object($sql1))
            {
                for ($j = $res1->datedebF; $j <= $res1->datefinF ; $j += 3600 * 24)
                {
                    if (date('N', $j) < 6) //pas un week end
                    {
                        //ajouter si pas un jour ferie
                        $pasFerie = true;
                        foreach($arrFerie as $key)
                        {
                            //print $key . " " . $j ."\n";
                            if ($key >=$j   && $key <= $j + 24 * 3600)
                            {
                                $pasFerie=false;
                                break;
                            }
                        }
                        if ($pasFerie)                         {
                            $nextDate=0;
                            if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/",date("d/m/Y",$j),$arr))
                            {
                                $nextDate = mktime (0, 0, 0, $arr[2],$arr[1], $arr[3]) + 24 * 3600;
                            }
                            if ($nextDate > $res1->datefinF) $nextDate = $res1->datefinF;
                            $durationPerDay = $nextDate - $j;
 //                            print $nextDate."\n".$res1->datefinF."\n".$durationPerDay / 3600 ."\n";
                            //Prob timezone
                            if ($durationPerDay > $conf->global->ALLDAY * 3600)
                            {
                                switch ($row[fk_resa_type])
                                {
                                    case 1:
                                        $count +=$conf->global->ALLDAY * 3600;
                                        $cout += $row[cout] * $conf->global->ALLDAY ; // le cout est un cout horaire
                                    break;
                                    case 2:
                                        $count += $conf->global->HALFDAY * 2 * 3600; // le cout est un cout par 1/2j
                                        $cout += $row[cout] * 2;
                                    break;
                                    case 3:
                                    default:
                                        $count += $conf->global->ALLDAY * 3600; // le cout est un cout parj
                                        $cout += $row[cout];
                                    break;
                                }
                            }else {
                                switch ($row[fk_resa_type])
                                {
                                    case 1:
                                        $count += $durationPerDay;
                                        $cout += $row[cout] * $durationPerDay  / 3600;
                                    break;
                                    case 2:
                                        $count += $conf->global->HALFDAY * 2 * 3600;
                                        $cout += $row[cout] * 2;
                                    break;
                                    case 3:
                                    default:
                                        $count += $conf->global->ALLDAY * 3600;
                                        $cout += $row[cout];
                                    break;
                                }
                            }

                        }
                    }
                }
            }
            $responce->rows[$i]['cell']=array($row[id],
                                              $row[nom],
                                              $row[categorie],
                                              $row[description],
                                              round($count/3600),
                                              $cout,
                                              $img,
                                              );
            $i++;
        }
        echo json_encode($responce);
    }
    break;
}


?>
