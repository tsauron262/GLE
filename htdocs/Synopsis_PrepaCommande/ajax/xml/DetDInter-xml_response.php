<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 28 sept. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : DetDInter-xml_response.php
  * GLE-1.2
  */
  require_once('../../../main.inc.php');
  require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
  $id = $_REQUEST['id'];
  $xmlStr = "<ajax-response>";


  if ($id > 0)
  {
    $requete = "SELECT *
                  FROM ".MAIN_DB_PREFIX."Synopsis_demandeIntervdet as ft
             LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_fichinter_c_typeInterv as t ON t.id = ft.fk_typeinterv AND t.active=1
                 WHERE fk_demandeInterv = ".$id. "
              ORDER BY ft.rang ";
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql))
    {
        $xmlStr .= "<DI id='".$res->rowid."'>";
        $xmlStr .= "<rowid>".$res->rowid."</rowid>";
        $xmlStr .= "<date>".date('d/m/Y',strtotime($res->date))."</date>";
        $xmlStr .= "<description><![CDATA[".traiteStr($res->description)."]]></description>";
        if ($res->isDeplacement == 1)
        {
            $requete = "SELECT *
                          FROM ".MAIN_DB_PREFIX."product p,
                               ".MAIN_DB_PREFIX."commandedet cdet
                         WHERE p.fk_product_type=3
                           AND cdet.rowid = ".$res->fk_commandedet."
                           AND cdet.fk_product = p.rowid";
            $sql1 = $db->query($requete);
            $res1 = $db->fetch_object($sql1);
            if ($res1->fk_product > 0)
            {
                $tmpProd = new Product($db);
                $tmpProd->fetch($res1->fk_product);
                $xmlStr .= "<type><![CDATA[".traiteStr($res->label)."<br/>".$tmpProd->getNomUrl(1)."]]></type>";
            } else {
                $xmlStr .= "<type><![CDATA[".traiteStr($res->label)."]]></type>";
            }
        } else {
            $xmlStr .= "<type><![CDATA[".traiteStr($res->label)."]]></type>";
        }
        $tmpDur = convDur($res->duree);
        $xmlStr .= "<total_ht><![CDATA[".price($res->total_ht)."]]></total_ht>";

        $xmlStr .= "<duree><![CDATA[".$tmpDur['hours']['abs']."h".$tmpDur['minutes']['rel']."]]></duree>";
        $xmlStr .= "<rang>".$res->rang."</rang>";
        $xmlStr .= "</DI>";
    }
    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    print "<?xml version='1.0' encoding='utf-8'?$et\n";
    print $xmlStr;
    print "</ajax-response>";

  } else {
    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    print "<?xml version='1.0' encoding='utf-8'?$et\n";
    print "<ajax-response>";
    print "<error>Aucun Element</error>";
    print "</ajax-response>";

  }

function traiteStr($str){ return str_replace("\n", "<br/>", $str); }
?>
