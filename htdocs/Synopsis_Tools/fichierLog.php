<?php

if(isset($_REQUEST['action']) && $_REQUEST['action'] == "save")
    $textSave = $_REQUEST['text'];
$_REQUEST['text'] = "";
$_POST['text'] = "";
$_GET['text'] = "";
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
llxHeader("", "Fichier de log");
dol_fiche_head('', 'SynopsisTools', $langs->trans("Voir le fichier de log"));


if ($user->rights->SynopsisTools->Global->fileInfo != 1) {
    print "Ce module ne vous est pas accessible";
    llxFooter();
    exit(0);
}


$filename = str_replace("DOL_DATA_ROOT", DOL_DATA_ROOT, SYSLOG_FILE);

if(isset($textSave))
    file_put_contents ($filename, $textSave);

$text = file_get_contents($filename);
echo "<form action='./fichierLog.php?action=save' method='post'>";
echo "<textarea name='text' style='width:100%; height:400px;'>".$text."</textarea>";
echo "<br/><div class='divButAction'><input type='submit' class='butAction' value='Enregistrer'/></div></form>";

//include_once(DOL_DOCUMENT_ROOT . "/Synopsis_Tools/class/fileInfo.class.php");
//$fileInfo = new fileInfo($db);
//echo $fileInfo->getFiles();



llxFooter();
?>
