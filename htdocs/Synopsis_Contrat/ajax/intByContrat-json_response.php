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
        require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
        require_once(DOL_DOCUMENT_ROOT."/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php");

$id= isset($_REQUEST["id"])? $_REQUEST["id"] : '';
$action = isset($_REQUEST["action"])? $_REQUEST["action"] : '';
$page = isset($_REQUEST["page"])? $_REQUEST["page"] : '';
$limit = isset($_REQUEST["rows"])? $_REQUEST["rows"] : '';
$sidx = isset($_REQUEST["sidx"])? $_REQUEST["sidx"] : '';
$sord = isset($_REQUEST["sord"])? $_REQUEST["sord"] : '';
if(!$sidx) $sidx =1;

$typeObj = (isset($_REQUEST['type'])? $_REQUEST['type'] : "FI");
if($typeObj == "FI"){
        $fi = new Fichinter($db);
        $table = "Synopsis_fichinter";
}
else{
        $fi = new Synopsisdemandeinterv($db);
        $table = "synopsisdemandeinterv";
}

  switch($action)
  {
    default:
        $where = "fk_contrat=".$id;
       require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
        $result = $db->query("SELECT COUNT(*) AS count FROM ".MAIN_DB_PREFIX."".$table." WHERE ".$where);
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
                  FROM ".MAIN_DB_PREFIX."".$table." as c
                 WHERE ".$where."
              ORDER BY $sidx $sord
                 LIMIT $start , $limit";
        $result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error() . $SQL);
        @$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i=0;
        while($row = $db->fetch_object($result))
        {
            $arr = convDur($row->duree);
            $desc=$row->description;
            $fi->fetch($row->rowid);
            
            $userId = 0; $userStr = "";
            
            if($typeObj == "FI")
                $userId = $row->fk_user_author;
            else
                $userId = $row->fk_user_prisencharge;
            if($userId > 0){
                $userT = new User($db);
                $userT->fetch($userId);
                $userStr = $userT->getNomUrl(1);
            }
            
            $responce->rows[$i]['id']=$row->rowid;
            $responce->rows[$i]['cell']=array($row->rowid,
                                               traite_str("&nbsp;&nbsp;".$desc),
                                               $row->datei,
                                               price($row->total_ht)."&nbsp;&nbsp;",
                                               $arr['hours']["abs"].":".$arr['minutes']['rel']);
            $responce->rows[$i]['cell'][] = $fi->getNomUrl(1);
            if($typeObj != "FI"){
                $fi2 = new Fichinter($db);
                $resultT = '';
                $tabT = getElementElement("DI", "FI", $fi->id);
                foreach($tabT as $val){
                    $fi2->fetch($val["d"]);
                    if($fi2->id > 0)
                        $resultT .= $fi2->getNomUrl(1);
                }
                $responce->rows[$i]['cell'][] = $resultT;
            }
            else{
                $fi2 = new Synopsisdemandeinterv($db);
                $resultT = '';
                $tabT = getElementElement("DI", "FI", null, $fi->id);
                foreach($tabT as $val){
                    $fi2->fetch($val["s"]);
                    if($fi2->id > 0)
                        $resultT .= $fi2->getNomUrl(1);
                }
                $responce->rows[$i]['cell'][] = $resultT;
            }
            $responce->rows[$i]['cell'][] = $userStr;
            $responce->rows[$i]['cell'][] = $fi->getLibStatut(4);
            
            $i++;
        }
        echo json_encode($responce);

    break;
    case 'subGrid':
       require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
       require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
       require_once(DOL_DOCUMENT_ROOT."/Babel_GMAO/SAV.class.php");
        $result = $db->query("SELECT count(*) as count
                                FROM ".MAIN_DB_PREFIX."".$table."det
                               WHERE fk_".($table == "Synopsis_fichinter" ? "fichinter" : $table)." = ".$id);
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

        $SQL = "SELECT t.label as type, fd.rowid as id, t.isDeplacement, fd.date, fd.description, fd.total_ht, ".($typeObj=="FI" ?"fk_depProduct, " : "")."fd.fk_typeinterv, fd.duree
                  FROM ".MAIN_DB_PREFIX."".$table."det as fd
             LEFT JOIN ".MAIN_DB_PREFIX."synopsisfichinter_c_typeInterv as t ON fd.fk_typeinterv = t.id AND active = 1
                 WHERE fd.fk_".($table == "Synopsis_fichinter" ? "fichinter" : $table)." = ".$id."
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
            if ($row->fk_depProduct > 0)
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