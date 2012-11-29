<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 1 nov. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : prixHT-xml_response.php
  * GLE-1.2
  */
    $id=$_REQUEST['id'];
    require_once('../../main.inc.php');
    require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
    $prod = new Product($db);
    $xml = "";
    if ($id > 0)
    {
        $prod->fetch($id);
        $xml = "<price>". round($prod->price*100)/100 ."</price>";
    } else {
        $xml = "<price>-1</price>";
    }

    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
    } else {
        header("Content-type: text/xml;charset=utf-8");
    }
    $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
    echo "<ajax-response>";
    echo $xml;
    echo "</ajax-response>";


?>