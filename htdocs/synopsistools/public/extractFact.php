<?php

if (!defined('NOTOKENRENEWAL'))
    define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))
    define('NOREQUIREMENU', '1'); // If there is no menu to show
if (!defined('NOREQUIREHTML'))
    define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
if (!defined('NOREQUIREAJAX'))
    define('NOREQUIREAJAX', '1');
define("NOLOGIN", 1);  // This means this output page does not require to be logged.
define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.

require_once('../../main.inc.php');

//$sautDeLigne = "<br/><br/>";
//$separateur = " | ";


//require_once(DOL_DOCUMENT_ROOT."/synopsistools/class/synopsisexport.class.php");
//$export = new synopsisexport($db, (isset($_REQUEST['sortie'])? $_REQUEST['sortie'] : 'html'));
//$export->exportFactureSav();




require_once(DOL_DOCUMENT_ROOT."/synopsistools/class/exportfacture.class.php");
$export = new exportfacture($db);
$export->exportTout();    

echo "FIN";


echo "<br/><br/><a href='" . $_SERVER["HTTP_REFERER"] . "'>Retour</a>";


