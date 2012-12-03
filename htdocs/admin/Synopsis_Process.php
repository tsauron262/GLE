<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 7 janv. 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : " . MAIN_DB_PREFIX . "Synopsis_Process.php
  * GLE-1.2
  */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php");

$langs->load("admin");
$langs->load("bills");
$langs->load("propal");
$langs->load("other");

if (!$user->admin)
  accessforbidden();
$js =<<<EOF
<script>
jQuery(document).ready(function(){
    jQuery('#tabs').tabs({
        cache: true,
        spinner: 'Chargement ...',
        fx: {opacity: 'toggle' }
    })
});
</script>
EOF;
llxHeader($js,'Configuration du module Process');
print "<div id='tabs'>";
print "<ul>";
print "<li><a href='#glob'>Global</a></li>";
print "<li><a href='#trig'>Trigger</a></li>";
print "</ul>";
print "<div id='trig'>";
//Drag Drop
print "</div>";
print "<div id='glob'>";
print "</div>";
print "</div>";

llxFooter('$Date: 2005/10/03 01:36:21 $ - $Revision: 1.23 $');
?>