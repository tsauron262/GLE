<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 17 nov. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : getLignesDetails-xml_response.php
  * GLE-1.2
  */

    require_once('../../../main.inc.php');
    require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
    $id = $_REQUEST['id']; // commande
    //ligne de commande
    $arrCom = array();
    foreach($_REQUEST as $key=>$val){
        if (preg_match('/^val[0-9]*/',$key)){
            $arrCom[]=$val;
        }
    }
    $xmlStr = "<ajax-response>";
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."commandedet WHERE rowid IN (".join(",",$arrCom).") ";
    $sql = $db->query($requete);
    $prodTmp = new Product($db);
    while ($res= $db->fetch_object($sql)){
        $xmlStr.= "<comLigne>";
        $xmlStr.= "<id>".$res->rowid."</id>";
        $xmlStr.= "<pu_ht>".round($res->subprice*100)/100 ."</pu_ht>";
        $xmlStr.= "<qte>".$res->qty."</qte>";
        $xmlStr.= "<description><![CDATA[".$res->description."]]></description>";
        $xmlStr.= "<forfait>0</forfait>";
        $prodTmp->fetch($res->fk_product);
        $xmlStr.= "<product><![CDATA[".$prodTmp->getNomUrl(1)."]]></product>";
        $xmlStr.= "<fk_product><![CDATA[".$res->fk_product."]]></fk_product>";
        $xmlStr.= "<typeproduct><![CDATA[".$prodTmp->type."]]></typeproduct>";
        $xmlStr.= "<total_ht>".round($res->total_ht*100)/100 ."</total_ht>";
        $xmlStr.= "</comLigne>";
    }

    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
        header("Content-type: application/xhtml+xml;charset=utf-8");
    } else {
        header("Content-type: text/xml;charset=utf-8");
    }
    $et = ">";
    print "<?xml version='1.0' encoding='utf-8'?$et\n";
    print $xmlStr;
    print "</ajax-response>";



?>