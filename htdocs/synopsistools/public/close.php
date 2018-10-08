<?php
require_once(DOL_DOCUMENT_ROOT.'/main.inc.php');


echo "<img src='".DOL_URL_ROOT."/synopsistools/img/travaux.png'/>";
echo "<h1>Application ferm&eacute;e pour cause de maintenance.</h1>";
if(defined("CLOSE_DATE"))
    echo "<h2>Retour pr&eacute;vue : ". dol_print_date(CLOSE_DATE, "dayhourtext")."</h2>";

