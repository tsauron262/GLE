<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 28 mai 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : BabelGeoBI.php
  * GLE-1.1
  */


require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
#require_once(DOL_DOCUMENT_ROOT.'/lib/webcal.class.php');


if (!$user->admin)
    accessforbidden();


$langs->load("admin");
$langs->load("other");

$def = array();
$actionsave=$_POST["save"];

// Positionne la variable pour le test d'affichage de l'icone
if ($actionsave)
{
    $i=0;

    $db->begin();

    $i+=dolibarr_set_const($db,'MAIN_MODULE_GEOBI_LNGDEFAULT',trim($_POST["GEOBI_LNGDEFAULT"]),'',0);
    $i+=dolibarr_set_const($db,'MAIN_MODULE_GEOBI_LATDEFAULT',trim($_POST["GEOBI_LATDEFAULT"]),'',0);

$langs->load("synopsisGene@Synopsis_Tools");
    if ($i >= 2)
    {
        $db->commit();
        header("Location: BabelGeoBI.php");
        exit;
    }
    else
    {
        $db->rollback();
        $mesg = "<font class=\"ok\">".$langs->trans("GEOBISetupSaved")."</font>";
    }
}


/**
 * Affichage du formulaire de saisie
 */

llxHeader();
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("GEOBI"),$linkback,'setup');

//print_titre($langs->trans("GEOBI"));
print '<br>';



print '<form name="GEOBIconfig" action="BabelGeoBI.php" method="post">';
print "<table class=\"noborder\" width=\"100%\">";

print "<tr class=\"liste_titre\">";
print "<td width=\"30%\">".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "<td>".$langs->trans("Examples")."</td>";
print "</tr>";
print "<tr class=\"impair\">";
print "<td>".$langs->trans("GEOBI_LATDEFAULT")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"GEOBI_LATDEFAULT\" value=\"". ($_POST["GEOBI_LATDEFAULT"]?$_POST["GEOBI_LATDEFAULT"]:$conf->global->MAIN_MODULE_GEOBI_LATDEFAULT) . "\" size=\"40\"></td>";
print "<td>43.5269449";
print "</td>";
print "</tr>";
print "<tr class=\"pair\">";
print "<td>".$langs->trans("GEOBI_LNGDEFAULT")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"GEOBI_LNGDEFAULT\" value=\"". ($_POST["GEOBI_LNGDEFAULT"]?$_POST["GEOBI_LNGDEFAULT"]:$conf->global->MAIN_MODULE_GEOBI_LNGDEFAULT) . "\" size=\"40\"></td>";
print "<td>5.4412472";
print "</td>";
print "</tr>";

print "</TABLE>";

print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</center>";

print "</form>\n";

?>
