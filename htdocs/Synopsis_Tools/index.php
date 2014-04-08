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

//if (isset($_REQUEST['connect']))
//    echo "<script>$(window).load(function() {initSynchServ(idActionMax);});</script>";

print "<div class='titre'>Outil GLE</div>";
print "<br/>";
if (isset($user->rights->SynopsisTools->Global->phpMyAdmin)) {
    print" <br/><br/><a href='myAdmin.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>PhpMyAdmin</span></a>";
    print" <br/><br/><a href='./Synopsis_MyAdmin/index.php'  target='_blank'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>PhpMyAdmin (Nouvelle onglet)</span></a>";
}
if (isset($user->rights->SynopsisTools->Global->fileInfo)) {
    print" <br/><br/><a href='./fichierLog.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Fichier de log</span></a>";
    print" <br/><br/><a href='./listFileInfo.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Fichiers de maj</span></a>";
}

if (isset($user->rights->SynopsisPrepaCom->import->Admin))
    print" <br/><br/><a href='../Synopsis_PrepaCommande/import/testImport.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Import 8sens -> GLE</span></a>";


print" <br/><br/><a href='../Synopsis_Tools/agenda/vue.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Test Agenda</span></a>";

print" <br/><br/><a href='../Synopsis_Tools/connect.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Test Connect</span></a>";

if (isset($conf->global->GOOGLE_ENABLE_GMAPS))
    print" <br/><br/><a href='../google/gmaps_all.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Carte des tiers</span></a>";


    print" <br/><br/><a href='../apple/test.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Test Apple</span></a>";


llxFooter();
?>
