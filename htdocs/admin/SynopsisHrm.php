<?php
/*
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.synopsis-erp.com
 *
 */

/**
        \file       htdocs/admin/webcalendar.php
        \ingroup    webcalendar
        \brief      Page de configuration du module webcalendar
        \version    $Revision: 1.23 $
*/

require_once("../main.inc.php");
//require_once(DOL_DOCUMENT_ROOT.'/lib/webcal.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");


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

    $i+=dolibarr_set_const($db,'ORANGE_USER',trim($_POST["ORANGE_USER"]),'',0);
    $i+=dolibarr_set_const($db,'ORANGE_PASS',trim($_POST["ORANGE_PASS"]),'',0);


    if ($i >= 1)
    {
        $db->commit();
        header("Location: SynopsisHrm.php");
        exit;
    }
    else
    {
        $db->rollback();
        $mesg = "<font class=\"ok\">".$langs->trans("hrmSaved")."</font>";
    }
}


/**
 * Affichage du formulaire de saisie
 */

llxHeader();

print_titre($langs->trans("Ressource humaine"));
print '<br>';


print '<form name="hrmconfig" action="SynopsisHrm.php" method="post">';
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td width=\"30%\">".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "<td>".$langs->trans("Examples")."</td>";
print "</tr>";

print "<tr class=\"impair\">";
print "<td>".$langs->trans("ORANGE_USER")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"ORANGE_USER\" value=\"". ($_POST["ORANGE_USER"]?$_POST["ORANGE_USER"]:$conf->global->ORANGE_USER) . "\" size=\"40\"></td>";
print "<td>admin";
print "</td>";
print "</tr>";

print "<tr class=\"pair\">";
print "<td>".$langs->trans("ORANGE_PASS")."</td>";
print "<td><input type=\"password\" class=\"flat\" name=\"ORANGE_PASS\" value=\"". ($_POST["ORANGE_PASS"]?$_POST["ORANGE_PASS"]:$conf->global->ORANGE_PASS) . "\" size=\"40\"></td>";
print "<td>elkrfj";
print "</td>";
print "</tr>";


print "</TABLE>";

print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</center>";

print "</form>\n";


clearstatcache();

if ($mesg) print "<br>$mesg<br>";
print "<br>";

$db->close();

llxFooter('$Date: 2005/10/03 01:36:21 $ - $Revision: 1.23 $');
?>
