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

$mainmenu = isset($_GET["mainmenu"]) ? $_GET["mainmenu"] : "";
llxHeader("", "Importation de données");
dol_fiche_head('', 'SynopsisTools', $langs->trans("Voir les fichier info de maj"));


if ($user->rights->SynopsisTools->Global->fileInfo != 1) {
    print "Ce module ne vous est pas accessible";
    llxFooter();
    exit(0);
}



include_once(DOL_DOCUMENT_ROOT . "/Synopsis_Tools/class/fileInfo.class.php");
$fileInfo = new fileInfo($db);
echo $fileInfo->getFiles();



llxFooter();
?>
