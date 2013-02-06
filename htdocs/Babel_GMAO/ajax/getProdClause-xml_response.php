<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 6 nov. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : getProdClause-xml_response.php
  * GLE-1.2
  */
    $xml = "";
    require_once("../../main.inc.php");
    require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
    $action = $_REQUEST['action'];
    $fk_soc = $_REQUEST['fk_soc'];

    $tmpSoc = new Societe($db);
    $tmpSoc->fetch($fk_soc);
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."product WHERE rowid=".$_REQUEST['prod'];
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($res))
    {
        $xml .= "<clause><![CDATA[".$res->clause."]]></clause>";
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
