<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 24 aout 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : setGroupCost-xmlresponse.php
  * GLE-1.1
  */
    require_once('../../main.inc.php');
    //require_once(DOL_DOCUMENT_ROOT.'/')
//recherche si le lien employé gle / hrm est fait, si oui, GO, si non null
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_hrm_user WHERE user_id is not null AND hrm_id = ".$_REQUEST['id'];
    $sql = $db->query($requete);
    $user_id="NULL";
    if($sql)
    {
        $res = $db->fetch_object($sql);
        $user_id = $res->user_id;
    }
    if ('x'.$user_id == "x") $user_id = "NULL";
    $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_hrm_user (user_id, hrm_id, couthoraire, startDate) VALUES ($user_id,".$_REQUEST['id'].",".$_REQUEST['cost'].",now())";
    $sql = $db->query($requete);
    if ($sql)
        $xml ="<OK>OK</OK>";

    header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= "<xml>".$xml."</xml>";
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;
?>
