<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 26 oct. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : index.php
 * GLE-1.2
 */
require_once('../main.inc.php');
llxHeader();

print "<div class='titre'>Outil GLE</div>";
print "<br/>";
if (isset($user->rights->SynopsisTools->Global->phpMyAdmin)) {
    print" <br/><br/><a href='myAdmin.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>PhpMyAdmin</span></a>";
    print" <br/><br/><a href='./Synopsis_MyAdmin/index.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>PhpMyAdmin (Nouvelle onglet)</span></a>";
}

if (isset($user->rights->SynopsisPrepaCom->import->Admin))
    print" <br/><br/><a href='../Synopsis_PrepaCommande/import/testImport.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Import 8sens -> GLE</span></a>";


llxFooter();
?>
