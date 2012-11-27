<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 5 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : KOFinance-xml_response.php
  * GLE-1.2
  */

    require_once('../../../main.inc.php');
    $id = $_REQUEST['comId'];
    $xmlStr = "<ajax-response>";
    $totalOK=true;
    $requete = "UPDATE ".MAIN_DB_PREFIX."commandedet SET finance_ok=0 WHERE fk_commande = ".$id;
    $sql = $db->query($requete);
    $idePart = 1;
    $requete = "UPDATE ".MAIN_DB_PREFIX."commande SET finance_ok=0 WHERE rowid =".$id;
    $db->query($requete);
    $xmlStr .= "<result>".$idePart."</result>";

  require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
  $commande = new Synopsis_Commande($db);
  $commande->fetch($id);
  $arrGrpTmp = $commande->listGroupMember();
  foreach($arrGrpTmp as $key=>$val)
  {
      $requete = "UPDATE ".MAIN_DB_PREFIX."commandedet SET finance_ok=0 WHERE fk_commande = ".$vak->id;
      $sql = $db->query($requete);
      $requete = "UPDATE ".MAIN_DB_PREFIX."commande SET finance_ok=0 WHERE rowid =".$val->id;
      $sql = $db->query($requete);
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
