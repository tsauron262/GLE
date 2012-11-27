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
  * Name : listCommandeDet-xml_response.php
  * GLE-1.2
  */

    $id=$_REQUEST['id'];
    require_once('../../main.inc.php');
    require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."commandedet WHERE fk_commande = ".$id." ORDER BY rang";
    $sql = $db->query($requete);
    $xml = "<commande>";
    while ($res = $db->fetch_object($sql))
    {
        $desc = "";
        if ($res->fk_product > 0)
        {
            $tmpProd = new Product($db);
            $tmpProd->fetch($res->fk_product);
            $desc = $tmpProd->ref." ".$tmpProd->libelle;
        } else {
            $desc = substr($res->description,0,30);
        }
        $xml .="<commandeDet id='".$res->rowid."'><![CDATA[".utf8_encode($desc)."]]></commandeDet>";
    }
    $xml .= "</commande>";

    if (stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
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