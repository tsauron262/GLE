<?php
/*
  /**
  *
  * Name : taskDesc-ajax.php
  * GLE-1.0
  */

  $xml="<name>test</name>";
  $xml.="<datedeb>test</datedeb>";
  $xml.="<datefin>test</datefin>";
  $xml.="<parent>test</parent>";
  $xml.="<type>test</type>";
  $xml.="<ressource>test</ressource>";
  $xml.="<complet>test</complet>";
  $xml.="<group>test</group>";
  $xml.="<depend>test</depend>";
  $xml.="<shortDesc>test</shortDesc>";


    header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= $xml;
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;

?>
