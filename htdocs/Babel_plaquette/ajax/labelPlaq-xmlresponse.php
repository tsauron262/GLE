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
  * Name : labelPlaq-xmlresponse.php
  * GLE-1.1
  */

require_once('../../main.inc.php');
$id = $_REQUEST['id'];
$val = $_REQUEST['value'];

$requete= " SELECT * FROM Babel_Plaquette_label WHERE ecm_id = ".$id;
$sql = $db->query($requete);
$num = $db->num_rows($sql);
$requete = "";
$xml = "";
if ($num > 0)
{
    $requete = "UPDATE Babel_Plaquette_label SET label='".utf8_decode(preg_replace('/\'/','\\\'',$val))."' WHERE ecm_id =".$id;
} else {
    $requete = "INSERT INTO Babel_Plaquette_label (label, ecm_id) VALUES ('".utf8_decode(preg_replace('/\'/','\\\'',$val))."',".$id.")";
}
$sql1 = $db->query($requete);
if ($sql1)
{
    $xml .= $val;
} else {
    $xml .= "Cliquer pour m'&eacute;diter";
}

    echo $xml;

?>
