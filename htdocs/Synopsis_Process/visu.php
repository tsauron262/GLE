<?php
/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */

 require_once("pre.inc.php");
 require_once("main.inc.php");

  if(!$user->rights->process->configurer){
        accessforbidden();
  }

 print "<table></table>";


?>
