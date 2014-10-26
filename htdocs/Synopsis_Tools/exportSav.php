<?php

require_once('../main.inc.php');

$mainmenu = isset($_GET["mainmenu"]) ? $_GET["mainmenu"] : "";
llxHeader("", "Fichier de log");
dol_fiche_head('', 'SynopsisTools', $langs->trans("Export Sav"));


$centre = (isset($_POST['centre']) ? $_POST['centre'] : null);


echo "<form method='POST'>";

echo "<select name='centre'>";
$result = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members` WHERE `list_refid` = 11 ");
//    $centres = array("G" => "Grenoble", "L" => "Lyon", "M" => "Meythet");
//    foreach ($centres as $val => $centre) {
    echo "<option value=''></option>";
while ($ligne = $db->fetch_object($result)) {
    $val = $ligne->valeur;
    $centreLab = $ligne->label;
    $myCentre = $centre;
    echo "<option value='" . $val . "' " . ($val == $myCentre ? "selected='selected'" : "") . ">" . $centreLab . "</option>";
}
echo "</select>";



echo "<input class='butAction' type='submit' value='Valider'/></form><br/><br/>";












require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Tools/class/synopsisexport.class.php");
$export = new synopsisexport($db, (isset($_REQUEST['sortie']) ? $_REQUEST['sortie'] : 'html'));
$export->exportChronoSav($centre);


llxFooter();







