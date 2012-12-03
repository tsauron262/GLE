<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 10 nov. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : getUserPrice-xml_response.php
  * GLE-1.2
  */
  require_once('../../../main.inc.php');
  require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
  $xml = "<ajax-response>";
  $id = $_REQUEST['userId'];
  $requete = "SELECT t.id as tid,
                     t.label as tlabel,
                     p.prix_ht
                FROM llx_Synopsis_fichinter_User_PrixTypeInterv as p
          RIGHT JOIN llx_Synopsis_fichinter_c_typeInterv as t ON t.id = p.typeInterv_refid
                 AND user_refid = ".$id. "
            ORDER BY t.rang";
  $sql = $db->query($requete);
  $tmpUser= new User($db);
  $tmpUser->fetch($id);
  $xml .= "<userDesc><![CDATA[".$tmpUser->getNomUrl(1)."]]></userDesc>";

  while ($res=$db->fetch_object($sql))
  {
     $xml .= "<interv id='".$res->tid."'>";
     $xml .= "<prix>".round($res->prix_ht*100)/100 ."</prix>";
     $xml .= "<label>".$res->tlabel."</label>";
     $xml .= "</interv>";
  }
  $requete = "SELECT i.fk_product as tid,
                     i.prix_ht,
                     p.rowid as pid
                FROM llx_product as p
           LEFT JOIN llx_Synopsis_fichinter_User_PrixDepInterv as i ON i.fk_product = p.rowid AND user_refid = ".$id. "
               WHERE p.fk_product_type=3
            ORDER BY p.ref";
  $sql = $db->query($requete);

  while ($res=$db->fetch_object($sql))
  {
    $tmpProd = new Product($db);
    $tmpProd->fetch($res->pid);
     $xml .= "<deplacement id='".$res->pid."'>";
     $xml .= "<prix>".round($res->prix_ht*100)/100 ."</prix>";
     $xml .= "<product><![CDATA[".$tmpProd->getNomUrl(1)."]]></product>";
     $xml .= "</deplacement>";
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
