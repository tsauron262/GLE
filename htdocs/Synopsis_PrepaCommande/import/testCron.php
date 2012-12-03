<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 27 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : testCron.php
  * GLE-1.2
  */

$ch = curl_init("http://127.0.0.1/GLE-1.2/main/htdocs/Synopsis_PrepaCommande/import/testImport.php");

curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt ($ch, CURLOPT_POST, 1);
curl_setopt ($ch, CURLOPT_POSTFIELDS, "username=eos&password=redalert&modeCli=1");

curl_exec($ch);
curl_close($ch);
?>
