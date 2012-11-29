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
$contratId = $_REQUEST['contratId'];

$page = $_REQUEST['page']; // get the requested page
$limit = $_REQUEST['rows'];
 // get how many rows we want to have into the grid
 $sidx = $_REQUEST['sidx'];
 // get index row - i.e. user click to sort
 $sord = $_REQUEST['sord'];
 $id =$_REQUEST['id'];
 // get the direction
 if(!$sidx) $sidx =1;

  $result = $db->query("SELECT COUNT(*) AS count FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture = ".$contratId);
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

  $requete = "SELECT ".MAIN_DB_PREFIX."facturedet.rowid as id,
                     ".MAIN_DB_PREFIX."facturedet.fk_product,
                     ifnull(llx_product.description,".MAIN_DB_PREFIX."facturedet.description) as description,
                     ".MAIN_DB_PREFIX."facturedet.qty,
                     ".MAIN_DB_PREFIX."facturedet.total_ht
                FROM ".MAIN_DB_PREFIX."facturedet
           LEFT JOIN llx_product on llx_product.rowid = ".MAIN_DB_PREFIX."facturedet.fk_product
               WHERE ".MAIN_DB_PREFIX."facturedet.fk_facture = $contratId
            ORDER BY $sidx $sord
               LIMIT $start , $limit";
    $result = $db->query( $requete ) or die("Couldnt execute query.".mysql_error());
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
    while($row = $db->fetch_array($result,MYSQL_ASSOC))
    {
        $total = $row[total_ht];
        echo "<row id='".$row[id]."'>";
        echo "<cell>". $row[id]."</cell>";
        echo "<cell><![CDATA[". $row[fk_product]."]]></cell>";
        echo "<cell><![CDATA[". utf8_decode($row[description])."]]></cell>";
        echo "<cell><![CDATA[". $row[qty]."]]></cell>";
        echo "<cell><![CDATA[". $total."]]></cell>";
        echo "</row>";
    }
        echo "</rows>";

?>
