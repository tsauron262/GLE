<?php
include("./listByObjet.php");die;

/*
 */
/**
 *
 * Name : listDetail.php.php
 * BIMP-ERP-1.2
 */
require_once('pre.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/chronoDetailList.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");

// Security check
$socid = isset($_GET["socid"]) ? $_GET["socid"] : '';
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'synopsischrono', $socid, '', '', 'Afficher');
//$user, $feature='societe', $objectid=0, $dbtablename='',$feature2='',$feature3=''


$id = $_REQUEST['id'];

$nomDiv = "gridChronoDet";

$js .= tabChronoDetail($id, $nomDiv, "");


llxHeader($js, "Chrono - d&eacute;tails");
dol_fiche_head('', 'Chrono', $langs->trans("Liste detail des Chrono"));

print '<script language="javascript"  src="' . DOL_URL_ROOT . '/Synopsis_Common/js/wz_tooltip/wz_tooltip.js"></script>' . "\n";

print "<br/>";

print "<div class='titre'>Chrono - d&eacute;tails :  ";

//1 liste des type de chrono disponible
print "<SELECT name='typeChrono' id='typeChrono'>";
$requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_conf where active=1 ORDER BY titre";
$sql = $db->query($requete);
print "<OPTION value='-1'>S&eacute;letionner-></OPTION>";
while ($res = $db->fetch_object($sql)) {
    if ($_REQUEST['id'] == $res->id)
        print "<option SELECTED value='" . $res->id . "'>" . $res->titre . "</option>";
    else
        print "<option value='" . $res->id . "'>" . $res->titre . "</option>";
}
print "</SELECT>";
print "</div>";

//require_once('Var_Dump.php');
//Var_Dump::Display($user->rights);
// $tmp = 'chrono'.$_REQUEST['id'];
if ($id > 0 && ($user->rights->synopsischrono->read || $user->rights->chrono_user->$tmp->voir)) {
    print "<br/>";

    print '<table id="' . $nomDiv . '" class="scroll ui-widget " cellpadding="0" cellspacing="0"></table>';
    print '<div id="' . $nomDiv . 'Pager" class="scroll" style="text-align:center;"></div>';
} else if ($id > 0) {
    print "<br/>";
    print "Vous ne disposez pas des droits pour voir ce chrono";
    print "<br/>";
}

//2 liste les details des chrono dans Grid
//    jQgrid Definition en fonction du type de Chrono
//     Alimentation Grid en fonction du type de Chrono
//3 Droits

llxFooter();

$db->close();

?>
