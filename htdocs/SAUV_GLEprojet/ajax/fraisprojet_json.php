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
        $SQL = "SELECT count(*) as cnt
                  FROM ".MAIN_DB_PREFIX."user,
                       ".MAIN_DB_PREFIX."Synopsis_projet_frais
             LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet_task ON  ".MAIN_DB_PREFIX."Synopsis_projet_task.rowid = ".MAIN_DB_PREFIX."Synopsis_projet_frais.fk_task
             LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet ON  ".MAIN_DB_PREFIX."Synopsis_projet.rowid = ".MAIN_DB_PREFIX."Synopsis_projet_frais.fk_projet
             LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn ON  ".MAIN_DB_PREFIX."Synopsis_projet.rowid = ".MAIN_DB_PREFIX."facture_fourn.fk_projet
             LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur ON  ".MAIN_DB_PREFIX."Synopsis_projet.rowid = ".MAIN_DB_PREFIX."commande_fournisseur.fk_projet
                 WHERE ".MAIN_DB_PREFIX."user.rowid = ".MAIN_DB_PREFIX."Synopsis_projet_frais.acheteur_id
                   AND ".MAIN_DB_PREFIX."Synopsis_projet_frais.fk_projet = ".$projId;


        $SQL .= "  ".$wh;
//        die($SQL);
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

        $SQL = "SELECT ".MAIN_DB_PREFIX."Synopsis_projet_frais.id as id,
                       ".MAIN_DB_PREFIX."Synopsis_projet_frais.designation as nom,
                       ifnull(".MAIN_DB_PREFIX."Synopsis_projet_task.title,'-') as tache,
                       concat(".MAIN_DB_PREFIX."user.firstname,' ',".MAIN_DB_PREFIX."user.lastname) as acheteur,
                       ".MAIN_DB_PREFIX."user.rowid as acheteurId,
                       dateAchat,
                       montantHT as cout,
                       ".MAIN_DB_PREFIX."facture_fourn.facnumber as factureRef,
                       ".MAIN_DB_PREFIX."commande_fournisseur.ref as commandRef,
                       ".MAIN_DB_PREFIX."facture_fourn.rowid as factureId,
                       ".MAIN_DB_PREFIX."commande_fournisseur.rowid as commandId
                  FROM ".MAIN_DB_PREFIX."user,
                       ".MAIN_DB_PREFIX."Synopsis_projet_frais
             LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet_task ON  ".MAIN_DB_PREFIX."Synopsis_projet_task.rowid = ".MAIN_DB_PREFIX."Synopsis_projet_frais.fk_task
             LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet ON  ".MAIN_DB_PREFIX."Synopsis_projet.rowid = ".MAIN_DB_PREFIX."Synopsis_projet_frais.fk_projet
             LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn ON  ".MAIN_DB_PREFIX."Synopsis_projet.rowid = ".MAIN_DB_PREFIX."facture_fourn.fk_projet AND fk_facture_fourn = ".MAIN_DB_PREFIX."facture_fourn.rowid
             LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur ON  ".MAIN_DB_PREFIX."Synopsis_projet.rowid = ".MAIN_DB_PREFIX."commande_fournisseur.fk_projet AND fk_commande_fourn = ".MAIN_DB_PREFIX."commande_fournisseur.rowid
                 WHERE ".MAIN_DB_PREFIX."user.rowid = ".MAIN_DB_PREFIX."Synopsis_projet_frais.acheteur_id
                   AND ".MAIN_DB_PREFIX."Synopsis_projet_frais.fk_projet = ".$projId;

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
            $tmpUser->id = $row[acheteurId];
            $tmpUser->fetch($tmpUser->id);



            $responce->rows[$i]['cell']=array($row[id],
                                              $row[nom],
                                              $row[tache],
                                              $tmpUser->getNomUrl(1),
                                              $row[dateAchat],
                                              $row[cout],
                                              $row[commandRef],
                                              $row[factureRef]
                                              );
            $i++;
        }
        echo json_encode($responce);
    }
    break;
}


?>
