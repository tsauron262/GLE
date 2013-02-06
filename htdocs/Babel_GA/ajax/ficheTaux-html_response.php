<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 29 mars 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : ficheTaux-html_response.php
  * GLE-1.1
  */

require_once('../../main.inc.php');

if ($conf->global->MAIN_MODULE_BABELGA) require_once(DOL_DOCUMENT_ROOT."/Babel_GA/BabelGA.class.php");
    $type = $_REQUEST['type'];
    $date = $_REQUEST['date'];
    $bga = new BabelGA($db);
    $bga->fetch_taux($_REQUEST['id'],$type);
    $bga->drawFinanceTable($date,false);


?>
