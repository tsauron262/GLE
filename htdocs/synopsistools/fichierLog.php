<?php

$inverser = false;
if (isset($_REQUEST['action']) && $_REQUEST['action'] == "save" && isset($_REQUEST['text'])) {
    $textSave = $_REQUEST['text'];
    if ($inverser) {
        $textT = explode("\n", $textSave);
        $textT = array_reverse($textT);
        $textSave = implode("\n", $textT);
    }
}
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
dol_fiche_head('', 'SynopsisTools', $langs->trans("Voir les fichiers de log"));


if ($user->rights->SynopsisTools->Global->fileInfo != 1) {
    print "Ce module ne vous est pas accessible";
    llxFooter();
    exit(0);
}

$prefixe = (isset($_REQUEST['prefixe']) ? $_REQUEST['prefixe'] : "");

$filename = str_replace("DOL_DATA_ROOT", DOL_DATA_ROOT, str_replace(".log", $prefixe . ".log", SYSLOG_FILE));

if (isset($textSave))
    file_put_contents($filename, $textSave);

if (is_file($filename)){
    $size = filesize($filename)/1024/1024;
    if($size > 10)
        $text = "Fichier trop gros ".$size." Mo";
    else
        $text = file_get_contents($filename);
}

if ($inverser) {
    $textT = explode("\n", $text);
    $textT = array_reverse($textT);
    $text = implode("\n", $textT);
}

$tabPrefixe = array("" => "Général", "_deprecated" => "Deprecated", "_recur" => "Récurent", "_mail" => "Mail", "_sms" => "SMS", "_apple" => "Apple", "_time" => "Pages lentes", "_sauv" => "Sauv");

foreach ($tabPrefixe as $prefV => $pref) {
    echo "<a style='margin:2px 8px;' href='?prefixe=" . $prefV . "'>" . $pref . "</a>";
}



echo "<form action='./fichierLog.php?action=save' method='post'>";
echo "<input type='hidden' name='prefixe' value='" . $prefixe . "'/>";
echo "<textarea name='text' style='width:100%; height:400px;'>" . $text . "</textarea>";
if($size)
echo "Taille : ".$size." Mo";
echo "<br/><div class='divButAction'><input type='submit' class='butAction' value='Enregistrer'/></div></form>";

//include_once(DOL_DOCUMENT_ROOT . "/synopsistools/class/fileInfo.class.php");
//$fileInfo = new fileInfo($db);
//echo $fileInfo->getFiles();



llxFooter();
?>
