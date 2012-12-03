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
        $result = $db->query("SELECT COUNT(*) AS count FROM ".MAIN_DB_PREFIX."fichinter WHERE fk_contrat=".$id);
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
                  FROM ".MAIN_DB_PREFIX."fichinter as c
                 WHERE c.fk_contrat=".$id."
              ORDER BY $sidx $sord
                 LIMIT $start , $limit";
        $result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error() . $SQL);
        $responce->page = $page;
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
            $responce->rows[$i]['id']=$row->rowid;
            $responce->rows[$i]['cell']=array($row->rowid,
                                               utf8_encode("&nbsp;&nbsp;".$desc),
                                               $row->datei,
                                               price($row->total_ht)."&nbsp;&nbsp;",
                                               $arr['hours']["abs"].":".$arr['minutes']['rel'],
                                               $fi->getNomUrl(1),
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
                                FROM ".MAIN_DB_PREFIX."fichinterdet
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
                  FROM ".MAIN_DB_PREFIX."fichinterdet as fd
             LEFT JOIN llx_Synopsis_fichinter_c_typeInterv as t ON fd.fk_typeinterv = t.id AND active = 1
                 WHERE fd.fk_fichinter = ".$id."
              ORDER BY $sidx $sord
                 LIMIT $start , $limit";
//print $SQL;
        $result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error() . $SQL);
        $responce->page = $page;
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
                                               utf8_encode("&nbsp;&nbsp;".$row->description),
                                               $row->date,
                                               $arr['hours']["abs"].":".$arr['minutes']['rel'],
                                               utf8_encode($type),
                                               price($row->total_ht)." &euro;&nbsp;&nbsp;"
                                             );
            $i++;
        }
        echo json_encode($responce);

    break;


  }

function convDur($duration)
{

    // Initialisation
    $duration = abs($duration);
    $converted_duration = array();

    // Conversion en semaines
    $converted_duration['weeks']['abs'] = floor($duration / (60*60*24*7));
    $modulus = $duration % (60*60*24*7);

    // Conversion en jours
    $converted_duration['days']['abs'] = floor($duration / (60*60*24));
    $converted_duration['days']['rel'] = floor($modulus / (60*60*24));
    $modulus = $modulus % (60*60*24);

    // Conversion en heures
    $converted_duration['hours']['abs'] = floor($duration / (60*60));
    $converted_duration['hours']['rel'] = floor($modulus / (60*60));
    if ($converted_duration['hours']['rel'] <10){$converted_duration['hours']['rel'] ="0".$converted_duration['hours']['rel']; } ;
    $modulus = $modulus % (60*60);

    // Conversion en minutes
    $converted_duration['minutes']['abs'] = floor($duration / 60);
    $converted_duration['minutes']['rel'] = floor($modulus / 60);
    if ($converted_duration['minutes']['rel'] <10){$converted_duration['minutes']['rel'] ="0".$converted_duration['minutes']['rel']; } ;
    $modulus = $modulus % 60;

    // Conversion en secondes
    $converted_duration['seconds']['abs'] = $duration;
    $converted_duration['seconds']['rel'] = $modulus;

    // Affichage
    return( $converted_duration);
}
?>