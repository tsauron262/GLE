<?php

/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 18 juin 09
  *
  * Infos on http://www.finapro.fr
  *
  */
/**
 *
 * Name : overview_grid_init.php
 * nagios_db
 */

require_once('./pre.inc.php');
$socId = $_REQUEST['socid'];


$page = $_GET['page']; // get the requested page
$limit = $_GET['rows'];
 // get how many rows we want to have into the grid
 $sidx = $_GET['sidx'];
 // get index row - i.e. user click to sort
 $sord = $_GET['sord'];
 // get the direction
 if(!$sidx) $sidx =1;
 // connect to the database

  $result = $db->query("SELECT count(*) as count FROM ".MAIN_DB_PREFIX."contrat where is_financement = 0 AND fk_soc = ".$socId);
   $row = $db->fetch_array($result,MYSQL_ASSOC);
   $count = $row['count'];
   if( $count >0 ) {
        $total_pages = ceil($count/$limit);
   } else {
        $total_pages = 0;
   }
   if ($page > $total_pages)
        $page=$total_pages;
   $start = $limit*$page - $limit; // do not put $limit*($page - 1)
   if ($start < 0) $start = 0;

   require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
   $contrat = new contrat($db);




 $requete = "
       SELECT rowid as id,
              ref,
              date_contrat,
              statut,
              (SELECT count(*) from ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat=id) as totEleme
         FROM ".MAIN_DB_PREFIX."contrat
        WHERE fk_soc = ".$socId." AND is_financement = 0
        ORDER BY $sidx $sord
        LIMIT $start , $limit";
    $result = mysql_query( $requete ) or die("Couldnt execute query.".mysql_error());
    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
    echo "<rows>";
    echo "<page>".$page."</page>";
    echo "<total>".$total_pages."</total>";
    echo "<records>".$count."</records>"; // be sure to put text data in CDATA
    while($row = mysql_fetch_array($result,MYSQL_ASSOC))
    {
        $contrat->fetch($row[id]);
        echo "<row id='". $row[id]."'>";
        echo "<cell>". $row[id]."</cell>";
        echo "<cell><![CDATA[". $row[ref]."]]></cell>";
        echo "<cell><![CDATA[". $row[date_contrat]."]]></cell>";
        echo "<cell><![CDATA[". $contrat->getLibStatut(2)."]]></cell>";
        echo "<cell><![CDATA[". $row[totEleme]."]]></cell>";
        echo "</row>";
    }
        echo "</rows>";
?>
