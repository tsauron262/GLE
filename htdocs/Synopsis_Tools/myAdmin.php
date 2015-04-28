<?php
/*
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
require_once('../main.inc.php');

$mainmenu=isset($_GET["mainmenu"])?$_GET["mainmenu"]:"";
llxHeader("", "PhpMyAdmin");
dol_fiche_head('', 'SynopsisTools', $langs->trans("PhpMyAdmin"), false, 'tools@Synopsis_Tools');
echo '<iframe style="width: 99.7%; height: 100%;"  SRC="Synopsis_MyAdmin/index.php">';
echo '</iframe>';

llxFooter();

?>
