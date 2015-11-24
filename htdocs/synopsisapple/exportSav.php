<?php

require_once('../main.inc.php');



$mainmenu = isset($_GET["mainmenu"]) ? $_GET["mainmenu"] : "";


$js = <<<EOF
	<script>
	jQuery(document).ready(function(){
		jQuery.datepicker.setDefaults(jQuery.extend({showMonthAfterYear: false,
			        dateFormat: 'dd/mm/yy',
			        changeMonth: true,
			        changeYear: true,
			        showButtonPanel: true,
			        buttonImage: 'cal.png',
			        buttonImageOnly: true,
			        showTime: false,
			        duration: '',
			        constrainInput: false,}, jQuery.datepicker.regional['fr']));
		jQuery('.datePicker').datepicker();
		jQuery('.dateTimePicker').datepicker({showTime:true});
	});
	</script>
EOF;



llxHeader($js, "Fichier de log");
dol_fiche_head('', 'SynopsisTools', $langs->trans("Export Sav"));

if(!isset($user->rights->synopsisapple->read) || $user->rights->synopsisapple->read != 1)
     accessforbidden("", 0,0);

$blockCentre = (!$user->rights->synopsisapple->stat ? explode(" ", trim($user->array_options['options_apple_centre'])) : null);





$centre = (isset($_POST['centre']) ? $_POST['centre'] : null);
$typeAff = (isset($_POST['typeAff']) ? $_POST['typeAff'] : null);
$typeAff2 = (isset($_POST['typeAff2']) ? $_POST['typeAff2'] : null);
$sortie = (isset($_REQUEST['sortie']) ? $_REQUEST['sortie'] : 'html');
$paye = (isset($_REQUEST['paye']) ? 1 : 0);
$dateDeb = (isset($_POST['dateDeb']) ? $_POST['dateDeb'] : ("01/". date("m") ."/".date("Y")));
$dateFin = (isset($_POST['dateFin']) ? $_POST['dateFin'] : ("01/". (date("m")+1) ."/".date("Y")));


echo "<form method='POST'>";

echo "<select name='sortie'>";
foreach (array("html" => "Affichage", "file" => "Fichier Exel") as $val => $label) {
    $valSelect = $sortie;
    echo "<option value='" . $val . "' " . ($val == $valSelect ? "selected='selected'" : "") . ">" . $label . "</option>";
}
echo "</select>";

echo "<select name='typeAff2'>";
foreach (array("sav" => "SAV", "ca" => "CA", "nb" => "Nombre PC", "fact" => "Facture") as $val => $label) {
    $valSelect = $typeAff2;
    echo "<option value='" . $val . "' " . ($val == $valSelect ? "selected='selected'" : "") . ">" . $label . "</option>";
}
echo "</select>";

echo "<select name='typeAff'>";
echo "<option value=''></option>";
foreach (array("parTypeMat" => "Par materiel", "parTypeGar" => "Par type de garantie", "parCentre" => "Par centre") as $val => $label) {
    $valSelect = $typeAff;
    echo "<option value='" . $val . "' " . ($val == $valSelect ? "selected='selected'" : "") . ">" . $label . "</option>";
}
echo "</select><br/>";


echo "<select name='centre'>";
$result = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members` WHERE `list_refid` = 11 ");
echo "<option value=''></option>";
while ($ligne = $db->fetch_object($result)) {
    $val = $ligne->valeur;
    $label = $ligne->label;
    $valSelect = $centre;
    if(!$blockCentre || in_array($val, $blockCentre))
        echo "<option value='" . $val . "' " . ($val == $valSelect ? "selected='selected'" : "") . ">" . $label . "</option>";
}
echo "</select>";

echo "<label for='paye'>Paye</label><input type='checkbox' name='paye' id='paye'" . ($paye ? " checked='ckecked'" : "") . "/>";

echo "Debut <input name='dateDeb' type='text' class='datePicker' value='" . $dateDeb . "'/>";
echo "Fin <input name='dateFin' type='text' class='datePicker' value='" . $dateFin . "'/>";

echo "<br/><input class='butAction' type='submit' value='Valider'/></form><br/><br/>";







if (isset($_REQUEST['reinitGarantiePa'])) {
    $result = $db->query("SELECT fact.rowid, COUNT(factdet.rowid) as nbGar FROM `" . MAIN_DB_PREFIX . "facture` fact, " . MAIN_DB_PREFIX . "facturedet factdet WHERE factdet.fk_facture = fact.rowid AND factdet.`description` LIKe 'Garantie' AND fact.total > -0.1 AND fact.total < 0.1 GROUP BY fact.rowid");
    while ($ligne = $db->fetch_object($result)) {
//    if($ligne->nbGar == 1){
        $result2 = $db->query("SELECt SUM(buy_price_ht) as tot FROM " . MAIN_DB_PREFIX . "facturedet WHERE description not like 'Garantie' AND fk_facture = " . $ligne->rowid);
        $ligne2 = $db->fetch_object($result2);

        $db->query("UPDATe " . MAIN_DB_PREFIX . "facturedet SET buy_price_ht = -" . ($ligne2->tot / $ligne->nbGar) . " WHERE fk_facture = " . $ligne->rowid . " AND description LIKE 'Garantie'");
//    }
//    else
//        echo "Plusieurs Garantie (".$ligne->nbGar.") facture ".$ligne->rowid;
    }
    
    
    
    $result = $db->query("SELECT fact.rowid, COUNT(factdet.rowid) as nbGar FROM `" . MAIN_DB_PREFIX . "propal` fact, " . MAIN_DB_PREFIX . "propaldet factdet WHERE factdet.fk_propal = fact.rowid AND factdet.`description` LIKe 'Garantie' AND fact.total > -0.1 AND fact.total < 0.1 GROUP BY fact.rowid");
    while ($ligne = $db->fetch_object($result)) {
//    if($ligne->nbGar == 1){
        $result2 = $db->query("SELECt SUM(buy_price_ht) as tot FROM " . MAIN_DB_PREFIX . "propaldet WHERE description not like 'Garantie' AND fk_propal = " . $ligne->rowid);
        $ligne2 = $db->fetch_object($result2);

        $db->query("UPDATe " . MAIN_DB_PREFIX . "propaldet SET buy_price_ht = -" . ($ligne2->tot / $ligne->nbGar) . " WHERE fk_propal = " . $ligne->rowid . " AND description LIKE 'Garantie'");
//    }
//    else
//        echo "Plusieurs Garantie (".$ligne->nbGar.") facture ".$ligne->rowid;
    }
    
    die("reinit ok");
}
//$centre = "CB";


require_once(DOL_DOCUMENT_ROOT . "/synopsistools/class/synopsisexport.class.php");
$export = new synopsisexport($db, $sortie);
$export->exportChronoSav($centre, $typeAff, $typeAff2, $paye, $dateDeb, $dateFin, $blockCentre);


global $logLongTime;
$logLongTime = false;

llxFooter();







