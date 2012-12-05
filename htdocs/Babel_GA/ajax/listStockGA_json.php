<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 30 mars 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : listStockGA_json.php
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
    if ($searchField == 'p.nom')
    {
        $searchField = "".MAIN_DB_PREFIX."product.label";
    }
    if ($searchField == 'departement')
    {
        $searchField = "s.fk_departement";
    }
    if ($searchField == "firstLoc")
    {
        $searchField = "date_format((SELECT distinct min(GAe.dateDeb) FROM Babel_GA_entrepotdet WHERE cessionnaire_refid = s.rowid  ),'%Y-%m-%d')";
        if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$searchString,$arr))
        {
            $searchString = $arr[3].'-'.$arr[2].'-'.$arr[1];
        }
    }

    if ($searchField == "dateLoc")
    {
        $searchField = "date_format(dateDeb,'%Y-%m-%d')";
        if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$searchString,$arr))
        {
            $searchString = $arr[3].'-'.$arr[2].'-'.$arr[1];
        }
    }
    if ($searchField == "dateFinLoc")
    {
        $searchField = "date_format(dateFin,'%Y-%m-%d')";
        if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$searchString,$arr))
        {
            $searchString = $arr[3].'-'.$arr[2].'-'.$arr[1];
        }
    }
    if ($searchField == "dateSortie")
    {
        $searchField = "date_format(dateDeSortieDefinitive,'%Y-%m-%d')";
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
    case 'subCessionnaire':
    {
        $SQL = "SELECT count(*) as cnt
                  FROM Babel_GA_entrepotdet";
        $SQL.= " WHERE  cessionnaire_refid =".$_REQUEST['SubRowId']." ";
        if ($_REQUEST['fournisseur'])
        {
            $SQL = "SELECT count(*) as cnt
                      FROM Babel_GA_entrepotdet";
            $SQL.= " WHERE fournisseur_refid =".$_REQUEST['SubRowId']." ";
        }
        if ($_REQUEST['client'])
        {
            $SQL = "SELECT count(*) as cnt
                      FROM Babel_GA_entrepotdet";
            $SQL.= " WHERE  client_refid =".$_REQUEST['SubRowId']." ";
        }
        if ($_REQUEST['cessionnaire'])
        {
            $SQL = "SELECT count(*) as cnt
                      FROM Babel_GA_entrepotdet";
            $SQL.= " WHERE  cessionnaire_refid =".$_REQUEST['SubRowId']." ";
        }


        $SQL .= " ". $wh;

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


        $sql = "SELECT Babel_GA_entrepotdet.id as id,
                       ".MAIN_DB_PREFIX."product.label as nom ";
        $sql .= " FROM Babel_GA_entrepotdet";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product on ".MAIN_DB_PREFIX."product.rowid = Babel_GA_entrepotdet.fk_product";
        $sql.= " WHERE cessionnaire_refid =".$_REQUEST['SubRowId']." ";

        if ($_REQUEST['fournisseur'])
        {
            $sql = "SELECT Babel_GA_entrepotdet.id as id,
                           ".MAIN_DB_PREFIX."product.label as nom ";
            $sql .= " FROM Babel_GA_entrepotdet";
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product on ".MAIN_DB_PREFIX."product.rowid = Babel_GA_entrepotdet.fk_product";
            $sql.= " WHERE fournisseur_refid =".$_REQUEST['SubRowId']." ";
        }
        if ($_REQUEST['client'])
        {
            $sql = "SELECT Babel_GA_entrepotdet.id as id,
                           ".MAIN_DB_PREFIX."product.label as nom ";
            $sql .= " FROM Babel_GA_entrepotdet";
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product on ".MAIN_DB_PREFIX."product.rowid = Babel_GA_entrepotdet.fk_product";
            $sql.= " WHERE client_refid =".$_REQUEST['SubRowId']." ";
        }
        if ($_REQUEST['cessionnaire'])
        {
            $sql = "SELECT Babel_GA_entrepotdet.id as id,
                           ".MAIN_DB_PREFIX."product.label as nom ";
            $sql .= " FROM Babel_GA_entrepotdet";
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product on ".MAIN_DB_PREFIX."product.rowid = Babel_GA_entrepotdet.fk_product";
            $sql.= " WHERE cessionnaire_refid =".$_REQUEST['SubRowId']." ";
        }




        $sql .= "  ".$wh."
                ORDER BY $sidx $sord
                LIMIT $start , $limit";
        $result = $db->query( $sql ) or die("Couldn t execute query : ".$sql.".".mysql_error());
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i=0;
        require_once(DOL_DOCUMENT_ROOT.'/Babel_GA/LocationGA.class.php');
        $tmpObj = new LocationGA($db);
        while($obj = $db->fetch_object($result))
        {

            $tmpObj->fetch($obj->id);
            $addr = $tmpObj->getAddress($obj->id);
            $responce->rows[$i]['cell']=array($obj->id,
                                              "<div style='padding: 2px 10px 2px 10px;'>".utf8_encode($tmpObj->product->getNomUrl(1))." ".$tmpObj->product->libelle."</div>",
                                              utf8_encode( $tmpObj->getNomUrl(1)),
                                              utf8_encode( $tmpObj->getLibStatut(4)),
                                              "<div style='padding: 2px 10px 2px 10px;'>".utf8_encode($tmpObj->client->getNomUrl(1))."</div>",
                                              "<div style='padding: 2px 10px 2px 10px;'>".utf8_encode($tmpObj->fourn->getNomUrl(1))."</div>",
                                              "<div style='padding: 2px 10px 2px 10px;'>".utf8_encode($tmpObj->cession->getNomUrl(1))."</div>",
                                              "<div style='padding: 2px 10px 2px 10px;'>".utf8_encode($addr)."</div>",
                                              $tmpObj->dateDeb,
                                              $tmpObj->dateFin,
                                              $tmpObj->dateDeSortieDefinitive,
                                              );
            $i++;
        }
        echo json_encode($responce);
    }
    break;

   case 'subAllStock':
    {
        $SQL = "SELECT count(*) as cnt
                  FROM Babel_GA_entrepotdet";

        $SQL .= " ". $wh;

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


        $sql = "SELECT Babel_GA_entrepotdet.id as id,
                       ".MAIN_DB_PREFIX."product.label as nom ";
        $sql .= " FROM Babel_GA_entrepotdet";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product on ".MAIN_DB_PREFIX."product.rowid = Babel_GA_entrepotdet.fk_product";

        $sql .= "  ".$wh."
                ORDER BY $sidx $sord
                LIMIT $start , $limit";
        $result = $db->query( $sql ) or die("Couldn t execute query : ".$sql.".".mysql_error());
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i=0;
        require_once(DOL_DOCUMENT_ROOT.'/Babel_GA/LocationGA.class.php');
        $tmpObj = new LocationGA($db);
        while($obj = $db->fetch_object($result))
        {

            $tmpObj->fetch($obj->id);
            $addr = $tmpObj->getAddress($obj->id);
            $responce->rows[$i]['cell']=array($obj->id,
                                              "<div style='padding: 2px 10px 2px 10px;'>".utf8_encode($tmpObj->product->getNomUrl(1))." ".$tmpObj->product->libelle."</div>",
                                              utf8_encode( $tmpObj->getNomUrl(1)),
                                              utf8_encode( $tmpObj->getLibStatut(4)),
                                              "<div style='padding: 2px 10px 2px 10px;'>".utf8_encode($tmpObj->client->getNomUrl(1))."</div>",
                                              "<div style='padding: 2px 10px 2px 10px;'>".utf8_encode($tmpObj->fourn->getNomUrl(1))."</div>",
                                              "<div style='padding: 2px 10px 2px 10px;'>".utf8_encode($tmpObj->cession->getNomUrl(1))."</div>",
                                              "<div style='padding: 2px 10px 2px 10px;'>".utf8_encode($addr)."</div>",
                                              $tmpObj->dateDeb,
                                              $tmpObj->dateFin,
                                              $tmpObj->dateDeSortieDefinitive,
                                              );
            $i++;
        }
        echo json_encode($responce);
    }
    break;


    default :
    {
        $SQL = "SELECT count(*) as cnt
                  FROM ".MAIN_DB_PREFIX."societe
                 WHERE 1=1";
        if ($_REQUEST['cessionnaire'] . "x" != "x" )
        {
            $SQL .= " AND ".MAIN_DB_PREFIX."societe.cessionnaire =" . $_REQUEST['cessionnaire'];
        }
        if ($_REQUEST['fournisseur'] . "x" != "x" )
        {
            $SQL .= " AND ".MAIN_DB_PREFIX."societe.fournisseur =" . $_REQUEST['fournisseur'];
        }
        if ($_REQUEST['client'] . "x" != "x" )
        {
            $SQL .= " AND ".MAIN_DB_PREFIX."societe.client =" . $_REQUEST['client'];
        }

        $SQL .= " ". $wh;

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



        $sql = "SELECT DISTINCT s.rowid,
                       s.nom as socname,
                       s.ville,
                       s.datec  as datec,
                       s.datea as datea,
                       d.nom as departement,
                       (SELECT distinct min(GAe.dateDeb) FROM Babel_GA_entrepotdet WHERE cessionnaire_refid = s.rowid  ) as firstLoc ";

        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
        $sql .= " FROM  ";
        $sql .= " ".MAIN_DB_PREFIX."societe as s";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d on (d.rowid = s.fk_departement)";
        if ($_REQUEST['cessionnaire'] . "x" != "x"  || $_REQUEST['fournisseur'] . "x" != "x"  || $_REQUEST['client'] . "x" != "x" )
        {
            $sql.= ", Babel_GA_entrepotdet as GAe";
        }
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE 1=1 ";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

        if ($_REQUEST['cessionnaire'] . "x" != "x" )
        {
            $sql .= " AND s.cessionnaire =" . $_REQUEST['cessionnaire'];
            $sql .= " AND GAe.cessionnaire_refid = s.rowid ";
        }
        if ($_REQUEST['fournisseur'] . "x" != "x" )
        {
            $sql .= " AND s.fournisseur =" . $_REQUEST['fournisseur'];
            $sql .= " AND GAe.fournisseur_refid = s.rowid ";
        }
        if ($_REQUEST['client'] . "x" != "x" )
        {
            $sql .= " AND s.client =" . $_REQUEST['client'];
            $sql .= " AND GAe.client_refid = s.rowid ";
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
        $cession=new Societe($db);
        while($obj = $db->fetch_object($result))
        {
            $cession->fetch($obj->rowid);

            $responce->rows[$i]['cell']=array($obj->rowid,
                                              "<div style='padding: 2px 10px 2px 10px;'>".utf8_encode($cession->getNomUrl(1))."</div>",
                                              utf8_encode( $obj->ville),
                                              utf8_encode( $obj->departement),
                                              $obj->firstLoc,
                                              );
            $i++;
        }
        echo json_encode($responce);
    }
    break;
}


?>
