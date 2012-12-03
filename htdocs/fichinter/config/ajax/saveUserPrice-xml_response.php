<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 11 nov. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : saveUserPrice-xml_response.php
  * GLE-1.2
  */


  //log: userId=59&type-4=95&type-1=95&type-3=95&type-5=95&type-6=95&type-7=95&type-2=95&type-8=95&type-9=95&type-10=95
  require_once('../../../main.inc.php');
  $xml = "<ajax-response>";
  $userId = $_REQUEST["userId"];
  $priceArr = array();
  foreach($_REQUEST as $key=>$val){
    if (preg_match('/^type[\W]{1}([0-9]*)/',$key,$arr)){
        $priceArr[$arr[1]]=preg_replace('/,/','.',$val);
    }
  }
  if ($userId > 0)
  {
    $requete = "DELETE FROM llx_Synopsis_fichinter_User_PrixTypeInterv WHERE user_refid = ".$userId;
    $sql = $db->query($requete);
    $error = 0;
    if (!$sql) $error++;

    foreach($priceArr as $key=>$val)
    {
        $requete = "INSERT INTO llx_Synopsis_fichinter_User_PrixTypeInterv
                                (typeInterv_refid, user_refid, prix_ht)
                         VALUES (".$key.",".$userId.",".$val.")";
        $sql = $db->query($requete);
        if (!$sql) $error++;
    }
    if ($error == 0)
       $xml .= "<OK>OK</OK>";
    else
        $xml .= "<KO>KO</KO>";
  } else {
    $xml .= "<KO>KO</KO>";
  }
  if ($userId > 0)
  {
      $priceArr = array();
      foreach($_REQUEST as $key=>$val){
        if (preg_match('/^dep[\W]{1}([0-9]*)/',$key,$arr)){
            $priceArr[$arr[1]]=preg_replace('/,/','.',$val);
        }
      }
    $requete = "DELETE FROM llx_Synopsis_fichinter_User_PrixDepInterv WHERE user_refid = ".$userId;
    $sql = $db->query($requete);
    $error = 0;
    if (!$sql) $error++;

    foreach($priceArr as $key=>$val)
    {
        $requete = "INSERT INTO llx_Synopsis_fichinter_User_PrixDepInterv
                                (fk_product, user_refid, prix_ht)
                         VALUES (".$key.",".$userId.",".$val.")";
        $sql = $db->query($requete);
        if (!$sql) $error++;
    }
  }


  if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
      header("Content-type: application/xhtml+xml;charset=utf-8");
  } else {
      header("Content-type: text/xml;charset=utf-8");
  }
  $et = ">";
  echo "<?xml version='1.0' encoding='utf-8'?$et\n";
  echo utf8_encode($xml);
  echo "</ajax-response>";

?>
