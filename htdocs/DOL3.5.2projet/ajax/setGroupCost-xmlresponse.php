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

    $requete = "INSERT INTO Babel_hrm_team (teamId, couthoraire,startDate) VALUES (".$_REQUEST['id'].",".$_REQUEST['cost'].",now())";
    $sql = $db->query($requete);
    $xml ="<KO>KO</KO>";
    if ($sql)
        $xml ="<OK>OK</OK>";

    header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= "<xml>".$xml."</xml>";
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;
?>
