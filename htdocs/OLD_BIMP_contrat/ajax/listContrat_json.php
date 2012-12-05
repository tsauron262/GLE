<?php
/*
  *
  */
 /**
  *
  * Name : listContrat_json.php
  * GLE-1.1
  */

//TODO left menu + lib/*php

require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/propal.class.php');
require_once(DOL_DOCUMENT_ROOT.'/product.class.php');
require_once(DOL_DOCUMENT_ROOT.'/contrat/contrat.class.php');


$langs->load('companies');
$langs->load('commercial');
$langs->load('babel');

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
    if ($searchField == 'socname'){
        $searchField = "s.rowid";
    }
    if ($searchField == 'date_contrat')
    {
        $searchField = "c.date_contrat";
        if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$searchString,$arr))
        {
            $searchString = $arr[3].'-'.$arr[2].'-'.$arr[1].' 12:00'; //init at 12:00
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
        $SQL = "SELECT count(*) as cnt";
        $SQL.= " FROM ".MAIN_DB_PREFIX."societe as s,";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
        $SQL.= " ".MAIN_DB_PREFIX."contrat as c";
        $SQL.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
        $SQL.= " WHERE c.fk_soc = s.rowid ";
        if ($_REQUEST['socid'] > 0) $SQL .= " AND c.fk_soc = ".$_REQUEST['socid'];
        $SQL .= "  ".$wh;
        $SQL.= " GROUP BY c.rowid, c.datec, c.statut, s.nom, s.rowid";

        $result = $db->query($SQL);
        $count = 0;
        while ($row = $db->fetch_array($result,MYSQL_ASSOC))
        {
            $count ++;
        }

        if( $count >0 )
        {
            $total_pages = ceil($count/$limit);
        } else {
            $total_pages = 0;
        }
        if ($page > $total_pages) $page=$total_pages;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;


$sql = 'SELECT';
$sql.= ' sum('.$db->ifsql("cd.statut=0",1,0).') as nb_initial,';
$sql.= ' sum('.$db->ifsql("cd.statut=4 AND cd.date_fin_validite > sysdate()",1,0).') as nb_running,';
$sql.= ' sum('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite <= sysdate())",1,0).') as nb_late,';
$sql.= ' sum('.$db->ifsql("cd.statut=5",1,0).') as nb_closed,';
$sql.= " c.rowid as cid,
         c.ref,
         c.datec,
         c.date_contrat,
         ifnull(c.type,0) as type,
         c.statut,
         s.nom as socname,
         s.rowid as socid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
$sql.= " ".MAIN_DB_PREFIX."contrat as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
$sql.= " WHERE c.fk_soc = s.rowid ";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($_REQUEST['socid'] > 0) $sql .= " AND c.fk_soc = ".$_REQUEST['socid'];

//if ($search_nom)      $sql.= " AND s.nom like '%".addslashes($search_nom)."%'";
//if ($search_contract) $sql.= " AND c.rowid = '".addslashes($search_contract)."'";
//if ($sall)            $sql.= " AND (s.nom like '%".addslashes($sall)."%' OR cd.label like '%".addslashes($sall)."%' OR cd.description like '%".addslashes($sall)."%')";
//if ($socid > 0)       $sql.= " AND s.rowid = ".$socid;

$sql .= "  ".$wh." ";
$sql.= " GROUP BY c.rowid, c.datec, c.statut, s.nom, s.rowid
        ORDER BY $sidx $sord
        LIMIT $start , $limit";
//        $SQL = "SELECT Babel_global_ressources.id,
//                       Babel_global_ressources.nom,
//                       Babel_global_ressources.fk_user_resp,
//                       Babel_global_ressources.description,
//                       Babel_global_ressources.fk_parent_ressource,
//                       Babel_global_ressources.date_achat,
//                       Babel_global_ressources.valeur,
//                       Babel_global_ressources.cout,
//                       Babel_global_resatype.name as typeResa,
//                       OCTET_LENGTH(Babel_global_ressources.photo) as sizePhoto,
//                       (SELECT nom FROM Babel_global_ressources WHERE id = fk_parent_ressource) as categorie
//                  FROM ".MAIN_DB_PREFIX."user,
//                       Babel_global_ressources
//             LEFT JOIN Babel_global_resatype ON Babel_global_resatype.id = Babel_global_ressources.fk_resa_type
//                 WHERE ".MAIN_DB_PREFIX."user.rowid = Babel_global_ressources.fk_user_resp  ";
//
//        if ("x".$parentId != "x")
//        {
//            $SQL .= " AND fk_parent_ressource = ".$parentId;
//        } else {
//            $SQL .= " AND fk_parent_ressource is null ";
//        }
//        $SQL .= " AND isGroup = 0 ";
//
//        $SQL .= "  ".$wh."
//              ORDER BY $sidx $sord
//                 LIMIT $start , $limit";
//        print $SQL;
//print $sql;
        $result = $db->query( $sql ) or die("Couldn t execute query : ".$sql.".".mysql_error());
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i=0;
        while($row = $db->fetch_array($result,MYSQL_ASSOC))
        {
            $alert= false;
            $arr=array();
            $arr[] = ($row['nb_initial']>0?$row['nb_initial']:0);
            $arr[] = ($row['nb_running'] + $row['nb_late'] >0? $row['nb_running'] + $row['nb_late'] :0);
            if ($row['nb_late'] > 0) $alert = true;
                //Si nbLate > 0 => img warning
            $arr[] = ($row['nb_closed']>0?$row['nb_closed']:0);

            $img = join(' / ',$arr);
            if ($alert) $img = img_warning('Retard : '.$row['nb_late']).$img;

            $tmpsoc1 = new Societe($db);
            $tmpsoc1->fetch($row['socid'],$user->id);

            $tmpContrat = new Contrat($db);
            $tmpContrat->fetch($row[cid]);
            $type = false;

            switch($row['type'])
            {
                case 7:
                {
                    $type = 'Mixte';
                }
                break;
                case 6:
                {
                    $type = 'LocationFinanciere';
                }
                break;
                case 5:
                {
                    $type = 'Location';
                }
                break;
                case 0:
                {
                    $type = 'Libre';
                }
                break;
                case 1:
                {
                    $type = 'Service';
                }
                break;
                case 2:
                {
                    $type = 'Ticket';
                }
                break;
                case 3:
                {
                    $type = 'Maintenance';
                }
                break;
                case 4:
                {
                    $type = 'SAV';
                }
                break;
            }


            $responce->rows[$i]['cell']=array($row[cid],
                                              "<div style='padding: 2px 10px 2px 10px;'>".$tmpContrat->getNomUrl(1)."</div>",
                                              "<div style='padding: 2px 10px 2px 10px;'>".utf8_encode($tmpsoc1->getNomUrl(1))."</div>",
                                              $row[date_contrat],
                                              $type,
                                              $img,
                                              );
            $i++;
        }
        header('application/json');
        echo json_encode($responce);
    }
    break;
}


?>