<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 15 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : SAVnewMessage-xml_response.php
  * GLE-1.2
  */

  require_once('../../../main.inc.php');
  require_once(DOL_DOCUMENT_ROOT.'/Babel_GMAO/SAV.class.php');

  $id=$_REQUEST['id'];
  $message = $_REQUEST['message'];
  $objsav = new SAV($db);
  $objsav->fetch($id);
  $res = $objsav->newMessage($message);
  $xml = "<KO>KO</KO>";
  if ($res)
      $xml = "<OK>OK</OK>";

    header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= "<xml>".$xml."</xml>";
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;


?>
