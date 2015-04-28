<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 20 aout 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : getProdContratProd-xml_response.php
  * GLE-1.2
  */


    require_once("../../main.inc.php");
    require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
    $xml = "<ajax-response>";
    $action = $_REQUEST['action'];
    $fk_soc = $_REQUEST['fk_soc'];
    $prod = new Product($db);
    $tmpSoc = new Societe($db);
    $tmpSoc->fetch($fk_soc);
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."product WHERE rowid=".$_REQUEST['prod'];
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($res))
    {
        $tva_tx = get_default_tva($mysoc,$tmpSoc,$res->tva_tx,$_REQUEST['prod']);
        $xml .= "<isSAV>".$res->isSAV."</isSAV>";
        $xml .= "<durValid>".$res->durValid."</durValid>";
        $xml .= "<clause><![CDATA[".$res->clause."]]></clause>";
        $xml .= "<reconductionAuto>".$res->reconductionAuto."</reconductionAuto>";
        $xml .= "<VisiteSurSite>".$res->VisiteSurSite."</VisiteSurSite>";
        $xml .= "<SLA><![CDATA[".utf8_encode($res->SLA)."]]></SLA>";
        $xml .= "<Maintenance>".$res->Maintenance."</Maintenance>";
        $xml .= "<TeleMaintenance>".$res->TeleMaintenance."</TeleMaintenance>";
        $xml .= "<Hotline>".$res->Hotline."</Hotline>";
        $xml .= "<qte>".($res->Maintenance>0?0:$res->qte)."</qte>";
        $xml .= "<qteMNT>".($res->Maintenance>0?$res->qte:0)."</qteMNT>";
        $xml .= "<price>".round($res->price*100)/100 ."</price>";
        $xml .= "<tva>".round($tva_tx*100)/100 ."</tva>";

        $xml .= "<qteTktPerDuree><![CDATA[".$res->qteTktPerDuree."]]></qteTktPerDuree>";
        $xml .= "<qteTempsPerDuree><![CDATA[".$res->qteTempsPerDuree."]]></qteTempsPerDuree>";
        $arrDur= convDur($res->qteTempsPerDuree);
        $xml .= "<qteTempsPerDureeH><![CDATA[".$arrDur['hours']['abs']."]]></qteTempsPerDureeH>";
        $xml .= "<qteTempsPerDureeM><![CDATA[".$arrDur['minutes']['rel']."]]></qteTempsPerDureeM>";

    }


    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
       header("Content-type: application/xhtml+xml;charset=utf-8");
    } else {
       header("Content-type: text/xml;charset=utf-8");
    }
    $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
    echo $xml;
    echo "</ajax-response>";

?>
