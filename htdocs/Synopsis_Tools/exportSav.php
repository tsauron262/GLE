<?php

require_once('../main.inc.php');

$mainmenu = isset($_GET["mainmenu"]) ? $_GET["mainmenu"] : "";
llxHeader("", "Fichier de log");
dol_fiche_head('', 'SynopsisTools', $langs->trans("Export Sav"));


$centre = (isset($_POST['centre']) ? $_POST['centre'] : null);
$typeAff = (isset($_POST['typeAff']) ? $_POST['typeAff'] : null);
$typeAff2 = (isset($_POST['typeAff2']) ? $_POST['typeAff2'] : null);
$sortie = (isset($_REQUEST['sortie']) ? $_REQUEST['sortie'] : 'html');


echo "<form method='POST'>";

echo "<select name='sortie'>";
foreach(array("html" => "Affichage", "file" => "Fichier Exel") as $val=> $label) {
    $valSelect = $sortie;
    echo "<option value='" . $val . "' " . ($val == $valSelect ? "selected='selected'" : "") . ">" . $label . "</option>";
}
echo "</select>";

echo "<select name='typeAff2'>";
foreach(array("ca" => "CA", "nb" => "Nombre PC") as $val=> $label) {
    $valSelect = $typeAff2;
    echo "<option value='" . $val . "' " . ($val == $valSelect ? "selected='selected'" : "") . ">" . $label . "</option>";
}
echo "</select>";

echo "<select name='typeAff'>";
    echo "<option value=''></option>";
foreach(array("parTypeMat" => "Par materiel", "parTypeGar" => "Par type de garantie") as $val=> $label) {
    $valSelect = $typeAff;
    echo "<option value='" . $val . "' " . ($val == $valSelect ? "selected='selected'" : "") . ">" . $label . "</option>";
}
echo "</select>";


echo "<select name='centre'>";
$result = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members` WHERE `list_refid` = 11 ");
    echo "<option value=''></option>";
while ($ligne = $db->fetch_object($result)) {
    $val = $ligne->valeur;
    $label = $ligne->label;
    $valSelect = $centre;
    echo "<option value='" . $val . "' " . ($val == $valSelect ? "selected='selected'" : "") . ">" . $label . "</option>";
}
echo "</select>";



echo "<input class='butAction' type='submit' value='Valider'/></form><br/><br/>";












require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Tools/class/synopsisexport.class.php");
$export = new synopsisexport($db, $sortie);
$export->exportChronoSav($centre, $typeAff, $typeAff2);


llxFooter();







