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

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
#require_once(DOL_DOCUMENT_ROOT.'/lib/webcal.class.php');


if (!$user->admin && ! $user->local_admin)
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

    $i+=dolibarr_set_const($db,'BABEL_MINIHISTO_LENGTH',trim($_POST["BABEL_MINIHISTO_LENGTH"]),'',0);
/*

 */

    if ($i >= 1)
    {
        $db->commit();
        header("Location: Babel_Historique.php");
        exit;
    }
    else
    {
        $db->rollback();
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
}


/**
 * Affichage du formulaire de saisie
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("BABELIM"),$linkback,'setup');
print '<br>';


print '<form name="BABELHistoConfig" action="Babel_Historique.php" method="POST">';
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"ui-widget-header ui-state-default\">";
print "<th class=\"ui-widget-header ui-state-default\" width=\"30%\">".$langs->trans("Parameter")."</th>";
print "<th class=\"ui-widget-header ui-state-default\">".$langs->trans("Value")."</th>";
print "<th class=\"ui-widget-header ui-state-default\">".$langs->trans("Examples")."</th>";
print "<th class=\"ui-widget-header ui-state-default\"></th>";
print "</tr>";
print "<tr class=\"impair\">";
print "<td align=left>&nbsp;&nbsp;".$langs->trans("Longueur de l'historique")."</td>";
print "<td align=center><input style='text-align:center;' type=\"text\" size=4 class=\"flat\" name=\"BABEL_MINIHISTO_LENGTH\" value=\"". ($_POST["BABEL_MINIHISTO_LENGTH"]?$_POST["BABEL_MINIHISTO_LENGTH"]:$conf->global->BABEL_MINIHISTO_LENGTH) . "\" ></td>";
print "<td align=center>5";
print "</td>";
print "<td align=center>";
print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</tr>";

print "</center>";

print "</form>\n";


clearstatcache();

if ($mesg) print "<br>$mesg<br>";
print "<br>";

$db->close();

llxFooter('$Date: 2005/10/03 01:36:21 $ - $Revision: 1.23 $');
?>
