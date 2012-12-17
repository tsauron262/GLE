<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 6 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : contratHisto-json_response.php
  *
  * GLE-1.2
  *
  */


  $id=$_REQUEST["id"];
   require_once('../../../main.inc.php');
   $userId = $_REQUEST['userId'];
   $fuser = new User($db);
   $fuser->id = $userId;
   $fuser->fetch();
   $fuser->getrights();

   require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
    $page = $_GET['page'];
    $limit = $_GET['rows'];
    $sidx = $_GET['sidx'];
    $sord = $_GET['sord'];
    if(!$sidx) $sidx =1;
    $result = $db->query("SELECT COUNT(*) AS count FROM ".MAIN_DB_PREFIX."contrat WHERE fk_soc=".$id);
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

    $SQL = "SELECT * FROM ".MAIN_DB_PREFIX."contrat WHERE fk_soc=".$id."  ORDER BY $sidx $sord LIMIT $start , $limit";
    $result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error() . $SQL);
    $responce->page = $page;
    $responce->total = $total_pages;
    $responce->records = $count;
    $i=0;
    while($row = $db->fetch_object($result,MYSQL_ASSOC))
    {
        $commande = new Contrat($db);
        $commande->fetch($row->rowid);
        $responce->rows[$i]['id']=$row->rowid;
        if ($fuser->rights->SynopsisPrepaCom->all->AfficherPrix)
        {
            $responce->rows[$i]['cell']=array($row->rowid,
                                               utf8_encodeRien($commande->getNomUrl(1)),
                                               $row->date_contrat,
                                               $commande->getLibStatut(4));
        } else {
            $responce->rows[$i]['cell']=array($row->rowid,
                                               utf8_encodeRien($commande->getNomUrl(1)),
                                               $row->date_contrat,
                                               $commande->getLibStatut(4));
        }
        $i++;
    }
        echo json_encode($responce);

?>
