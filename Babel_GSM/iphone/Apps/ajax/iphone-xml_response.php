<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 9 avr. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : iphone-xml_response.php
  * dolibarr-24dev
  */
  require_once('../../../../main.inc.php');
  $xml = "";
  $action = $_REQUEST['action'];
  switch($action)
  {
        case "list":{
            switch($_REQUEST['type'])
          {
                case "client":{
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."societe WHERE client=1 ORDER BY nom";
                    $sql = $db->query($requete);
                    $xml .= "<societes>";
                    while($res = $db->fetch_object($sql))
                    {
                        //nom , code_client, id
                        $xml.="<soc id='".$res->rowid."' codeCli='".$res->code_client."'><![CDATA[".$res->nom."]]></soc>";
                    }
                    $xml .= "</societes>";
                    $xml = utf8_encode($xml);
                }
              break;
          }
        }
      break;
  }
//
//  $xml = "<tr><td>testa</td><td>test1b</td></tr>";
//  $xml .= "<tr><td>test2a</td><td>test2b</td></tr>";
//  $xml .= "<tr><td>test3a</td><td>test3b</td></tr>";
//  $xml .= "<tr><td>test4a</td><td>test4b</td></tr>";


    header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= $xml;
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;

?>
