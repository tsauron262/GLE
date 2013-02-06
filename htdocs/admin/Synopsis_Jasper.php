<?php
/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */

/**
        \file       htdocs/admin/webcalendar.php
        \ingroup    webcalendar
        \brief      Page de configuration du module webcalendar
        \version    $Revision: 1.23 $
*/

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
#require_once(DOL_DOCUMENT_ROOT.'/lib/webcal.class.php');


if (!$user->admin)
    accessforbidden();


$langs->load("admin");
$langs->load("other");
$langs->load("synopsisGene@Synopsis_Tools");

$def = array();
$actionsave=$_POST["save"];

// Positionne la variable pour le test d'affichage de l'icone
if ($actionsave)
{
    $i=0;

    $db->begin();

    $i+=dolibarr_set_const($db,'JASPER_HOST',trim($_POST["JASPER_HOST"]),'',0);
    $i+=dolibarr_set_const($db,'JASPER_PORT',trim($_POST["JASPER_PORT"]),'',0);
    $i+=dolibarr_set_const($db,'JASPER_PROTO',trim($_POST["JASPER_PROTO"]),'',0);
    $i+=dolibarr_set_const($db,'JASPER_PATH',trim($_POST["JASPER_PATH"]),'',0);
    $i+=dolibarr_set_const($db,'JASPER_REPO_PATH_GENERATED',trim($_POST["JASPER_REPO_PATH_GENERATED"]),'',0);
    $i+=dolibarr_set_const($db,'JASPER_REPO_PATH_REPORT',trim($_POST["JASPER_REPO_PATH_REPORT"]),'',0);
    $i+=dolibarr_set_const($db,'BABELJASPER_JASPER_USE_LDAP',trim($_POST["BABELJASPER_JASPER_USE_LDAP"]?"true":"false"),'',0);
/*

 */
    if ($i >= 7)
    {
        $db->commit();
        header("Location: Synopsis_Jasper.php");
        exit;
    }
    else
    {
        $db->rollback();
        $mesg = "<font class=\"ok\">".$langs->trans("JASPERSetupSaved")."</font>";
    }
}


/**
 * Affichage du formulaire de saisie
 */

llxHeader();
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("JASPER"),$linkback,'setup');

//print_titre($langs->trans("JASPER"));
print '<br>';


print '<form name="JASPERconfig" action="Synopsis_Jasper.php" method="post">';
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td width=\"30%\">".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "<td>".$langs->trans("Examples")."</td>";
print "</tr>";
print "<tr class=\"impair\">";
print "<td>".$langs->trans("JASPER_HOST")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"JASPER_HOST\" value=\"". ($_POST["JASPER_HOST"]?$_POST["JASPER_HOST"]:$conf->global->JASPER_HOST) . "\" size=\"40\"></td>";
print "<td>jasperserver.synopsis-erp.com";
print "</td>";
print "</tr>";


print "<tr class=\"pair\">";
print "<td>".$langs->trans("JASPER_PORT")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"JASPER_PORT\" value=\"". ($_POST["JASPER_PORT"]?$_POST["JASPER_PORT"]:$conf->global->JASPER_PORT) . "\" size=\"40\"></td>";
print "<td>8080";
print "</td>";
print "</tr>";

print "<tr class=\"impair\">";
print "<td>".$langs->trans("JASPER_PROTO")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"JASPER_PROTO\" value=\"". ($_POST["JASPER_PROTO"]?$_POST["JASPER_PROTO"]:$conf->global->JASPER_PROTO) . "\" size=\"40\"></td>";
print "<td>http / https";
print "</td>";
print "</tr>";

print "<tr class=\"pair\">";
print "<td>".$langs->trans("JASPER_PATH")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"JASPER_PATH\" value=\"". ($_POST["JASPER_PATH"]?$_POST["JASPER_PATH"]:$conf->global->JASPER_PATH) . "\" size=\"40\"></td>";
print "<td>/jasperserver/services/repository";
print "</td>";
print "</tr>";


print "<tr class=\"impair\">";
print "<td>".$langs->trans("JASPER_REPO_PATH_GENERATED")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"JASPER_REPO_PATH_GENERATED\" value=\"". ($_POST["JASPER_REPO_PATH_GENERATED"]?$_POST["JASPER_REPO_PATH_GENERATED"]:$conf->global->JASPER_REPO_PATH_GENERATED) . "\" size=\"40\"></td>";
print "<td>/ContentFiles/Babel/ERP";
print "</td>";
print "</tr>";

print "<tr class=\"pair\">";
print "<td>".$langs->trans("JASPER_REPO_PATH_REPORT")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"JASPER_REPO_PATH_REPORT\" value=\"". ($_POST["JASPER_REPO_PATH_REPORT"]?$_POST["JASPER_REPO_PATH_REPORT"]:$conf->global->JASPER_REPO_PATH_REPORT) . "\" size=\"40\"></td>";
print "<td>/Reports/Babel/ERP";
print "</td>";
print "</tr>";



print "<tr class=\"impair\">";
print "<td>".$langs->trans("BABELJASPER_JASPER_USE_LDAP")."</td>";
print "<td><input type=\"checkbox\"  class=\"flat\" name=\"BABELJASPER_JASPER_USE_LDAP\" ". ($_POST["BABELJASPER_JASPER_USE_LDAP"]?"checked":($conf->global->BABELJASPER_JASPER_USE_LDAP == "true")?"checked":"")  . " size=\"40\"></td>";

print "<td>";
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
