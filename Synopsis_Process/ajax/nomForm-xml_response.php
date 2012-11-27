<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 3 mars 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : nomForm-xml_response.php
  * GLE-1.2
  */

  require_once('../../main.inc.php');

  $id = $_REQUEST['id'];
  $value = utf8_decode($_REQUEST['value']);

  if ($id > 0 && $value ."x" != "x")
  {
    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form WHERE label ='".addslashes($value)."' AND id <> ".$id;
    $sql = $db->query($requete);
    if ($db->num_rows($sql) > 0)
    {
        print utf8_encode("<div class='ui-error error'>Le nom ".$value." est d&eacute;j&agrave; utilis&eacute;</div>");
    } else {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process_form SET label='".addslashes($value)."' WHERE id = ".$id;
        $sql = $db->query($requete);
        if ($sql)
        {
            print utf8_encode($value);
        } else {
            print "<div class='ui-error error'>Erreur d'enregistrement</div>";
        }
    }
  }

?>
