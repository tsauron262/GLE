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
  * Name : DetInter-xml_response.php GLE-1.2
  */
  require_once('../../../main.inc.php');
  $id = $_REQUEST['id'];
  $xmlStr = "<ajax-response>";


  if ($id > 0)
  {
    $requete = "SELECT *
                  FROM ".MAIN_DB_PREFIX."Synopsis_fichinterdet as ft
             LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_fichinter_c_typeInterv as t ON t.id = ft.fk_typeinterv AND t.active=1
                 WHERE fk_fichinter = ".$id. "
              ORDER BY ft.rang ";
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql))
    {
        $xmlStr .= "<FI id='".$res->rowid."'>";
        $xmlStr .= "<rowid>".$res->rowid."</rowid>";
        $xmlStr .= "<date>".date('d/m/Y',strtotime($res->date))."</date>";
        $xmlStr .= "<description><![CDATA[".traiteStr($res->description)."]]></description>";
        if ($res->isDeplacement == 1)
        {
            if ($res->fk_depProduct > 0)
            {
                require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
                $tmpProd = new Product($db);
                $tmpProd->fetch($res->fk_depProduct);
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
        $xmlStr .= "</FI>";
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



function traiteStr($str){
    return str_replace("\n", "<br>", stripslashes($str));
}
?>
