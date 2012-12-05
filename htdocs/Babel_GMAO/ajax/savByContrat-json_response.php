<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 18 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : savByContrat-json_response.php GLE-1.2
  */
  require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php');

$id=$_REQUEST["id"];
$action = $_REQUEST['action'];
$page = $_GET['page'];
$limit = $_GET['rows'];
$sidx = $_GET['sidx'];
$sord = $_GET['sord'];
if(!$sidx) $sidx =1;

  switch($action)
  {
    default:
       require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
        $result = $db->query("SELECT COUNT(*) AS count FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat=".$id);
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

        $SQL = "SELECT c.description as cdesc, c.fk_product, c.rowid,
                       g.durValid, g.qte,g.fk_contrat_prod,
                       date_format(g.DateDeb,'%Y-%m-%d') as DateDeb,
                       date_format(date_add(g.DateDeb,INTERVAL g.durValid MONTH),'%Y-%m-%d') as DateFin,
                       s.serial_number,
                       ifnull(p.ref,c.description) as description
                  FROM ".MAIN_DB_PREFIX."contratdet as c LEFT JOIN ".MAIN_DB_PREFIX."product as p ON c.fk_product = p.rowid,
                       Babel_GMAO_contratdet_prop as g LEFT JOIN Babel_product_serial_cont as s ON s.element_id = g.contratdet_refid AND s.element_type='contratSAV'
                 WHERE c.fk_contrat=".$id."
                   AND c.rowid = g.contratdet_refid
              ORDER BY $sidx $sord
                 LIMIT $start , $limit";
        $result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error() . $SQL);
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i=0;
        while($row = $db->fetch_object($result))
        {
            $desc=$row->cdesc;
            if ($row->fk_product > 0)
            {
                $prod = new Product($db);
                $prod->fetch($row->fk_product);
                $desc = $prod->getNomUrl(1) . " ".$prod->libelle. " ";
                $desc .= ''.$row->description;
            }
            $serial = "-";
            if ($row->serial_number ."x" != "x")
            {
                $serial = $row->serial_number;
            }
            //TODO serial + date fin SAV
            $responce->rows[$i]['id']=$row->rowid;
            $responce->rows[$i]['cell']=array($row->rowid,
                                               utf8_encode($desc),
                                               $row->DateFin,
                                               $serial);
            $i++;
        }
        echo json_encode($responce);

    break;
    case 'subGrid':
       require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
       require_once(DOL_DOCUMENT_ROOT."/Babel_GMAO/SAV.class.php");
        $result = $db->query("SELECT count(*) as count
                                FROM Babel_GMAO_SAV_client
                               WHERE element_type = 'contrat'
                                 AND element_id = ".$id);
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

        $SQL = "SELECT *
                  FROM Babel_GMAO_SAV_client as s
                 WHERE s.element_type = 'contrat'
                   AND s.element_id = ".$id."
              ORDER BY $sidx $sord
                 LIMIT $start , $limit";
        $result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error() . $SQL);
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i=0;
        while($row = $db->fetch_object($result))
        {
            $objsav = new SAV($db);
            $objsav->fetch($row->id);
            $responce->rows[$i]['id']=$row->id;
            $responce->rows[$i]['cell']=array($row->id,
                                               $objsav->getNomUrl(1),
                                               utf8_encode($row->descriptif_probleme),
                                               $row->date_create,
                                               $row->date_end,
                                               $objsav->getLibStatut(5),
                                               utf8_encode($row->lastMessage)
                                             );
            $i++;
        }
        echo json_encode($responce);

    break;


  }

?>