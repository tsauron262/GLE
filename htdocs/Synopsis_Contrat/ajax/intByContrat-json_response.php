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
  * Name : intByContrat-json_response.php GLE-1.2
  */
  require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php');

$id= isset($_REQUEST["id"])? $_REQUEST["id"] : '';
$action = isset($_REQUEST["action"])? $_REQUEST["action"] : '';
$page = isset($_REQUEST["page"])? $_REQUEST["page"] : '';
$limit = isset($_REQUEST["rows"])? $_REQUEST["rows"] : '';
$sidx = isset($_REQUEST["sidx"])? $_REQUEST["sidx"] : '';
$sord = isset($_REQUEST["sord"])? $_REQUEST["sord"] : '';
if(!$sidx) $sidx =1;

  switch($action)
  {
    default:
       require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
        $result = $db->query("SELECT COUNT(*) AS count FROM ".MAIN_DB_PREFIX."Synopsis_fichinter WHERE fk_contrat=".$id);
        $row = $db->fetch_object($result);
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
                  FROM ".MAIN_DB_PREFIX."Synopsis_fichinter as c
                 WHERE c.fk_contrat=".$id."
              ORDER BY $sidx $sord
                 LIMIT $start , $limit";
        $result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error() . $SQL);
        @$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
        $fi = new Fichinter($db);
        $i=0;
        while($row = $db->fetch_object($result))
        {
            $arr = convDur($row->duree);
            $desc=$row->description;
            $fi->fetch($row->rowid);
            $userT = new User($db);
            $userT->fetch($fi->user_author_id);
            
            $responce->rows[$i]['id']=$row->rowid;
            $responce->rows[$i]['cell']=array($row->rowid,
                                               traite_str("&nbsp;&nbsp;".$desc),
                                               $row->datei,
                                               price($row->total_ht)."&nbsp;&nbsp;",
                                               $arr['hours']["abs"].":".$arr['minutes']['rel'],
                                               $fi->getNomUrl(1),
                                               $userT->getNomUrl(1),
                                               $fi->getLibStatut(4)
                                               );
            $i++;
        }
        echo json_encode($responce);

    break;
    case 'subGrid':
       require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
       require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
       require_once(DOL_DOCUMENT_ROOT."/Babel_GMAO/SAV.class.php");
        $result = $db->query("SELECT count(*) as count
                                FROM ".MAIN_DB_PREFIX."Synopsis_fichinterdet
                               WHERE fk_fichinter = ".$id);
        $row = $db->fetch_object($result);
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

        $SQL = "SELECT t.label as type, t.isDeplacement, fd.date, fd.description, fd.total_ht, fk_depProduct, fd.fk_typeinterv, fd.duree
                  FROM ".MAIN_DB_PREFIX."Synopsis_fichinterdet as fd
             LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_fichinter_c_typeInterv as t ON fd.fk_typeinterv = t.id AND active = 1
                 WHERE fd.fk_fichinter = ".$id."
              ORDER BY $sidx $sord
                 LIMIT $start , $limit";
//print $SQL;
        $result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error() . $SQL);
        @$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i=0;
        while($row = $db->fetch_object($result))
        {
            $type = $row->type;
            if ($row->isDeplacement == 1 && $row->fk_depProduct > 0)
            {
                $tmpProd = new Product($db);
                $tmpProd->fetch($row->fk_depProduct);
                $type = $row->type . " ".$tmpProd->getNomUrl(1);
            }
            $arr = convDur($row->duree);
            $responce->rows[$i]['id']=$row->id;
            $responce->rows[$i]['cell']=array($row->id,
                                               traite_str("&nbsp;&nbsp;".$row->description),
                                               $row->date,
                                               $arr['hours']["abs"].":".$arr['minutes']['rel'],
                                               traite_str($type),
                                               price($row->total_ht)." &euro;&nbsp;&nbsp;"
                                             );
            $i++;
        }
        echo json_encode($responce);

    break;


  }
function traite_str($str){
    return $str;
}
?>