<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 10 aout 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : photo_ressource.php
  * GLE-1.0
  */
require_once('../../main.inc.php');



$requete = "SELECT photo FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE id = ".$_REQUEST['ressource_id'];
$sql = $db->query($requete);
$res = $db->fetch_array($sql);

header('Content-type: image/jpeg');
header("Content-disposition: inline");
print $res[photo];


?>
