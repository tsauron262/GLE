<?php
/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 28 mai 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : saveModel-xmlresponse.php
  * GLE-1.1
  */

  require_once('../../main.inc.php');

$label = preg_replace('/\'/',"\\\'",utf8_decode($_REQUEST['label']));
$id = $_REQUEST['id'];
$content = preg_replace('/\'/',"\\\'",utf8_decode($_REQUEST['content']));
$subject = preg_replace('/\'/',"\\\'",utf8_decode($_REQUEST['sujet']));

$requete = "";
$requete = "SELECT * FROM Babel_Plaquette WHERE id =".$id;
$sql = $db->query($requete);

if ('x'.$id != "x" && $db->num_rows($sql) > 0)
{
    $requete = "UPDATE Babel_Plaquette SET content = '".$content."',
                                           subject='".$subject."',
                                           label='".$label."'
                                     WHERE id = ".$id;
} else {
    $requete = "INSERT INTO Babel_Plaquette  (content, subject, label)
                       VALUES ('".$content."','".$subject."','".$label."')";
}
$sql = $db->query($requete);


  $xml = "<ajax-response>";
  if ($sql)
  {
    $xml .= "<OK>OK</OK>";
  } else {
    $xml .= "<KO><![CDATA[".$requete."]]></KO>";
  }
  $xml .= "</ajax-response>";

    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
echo $xml;



?>
