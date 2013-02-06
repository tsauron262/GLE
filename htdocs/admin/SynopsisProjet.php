<?php

/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Created on : 24 aout 2009
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : BabelProject.php
 * GLE-1.1
 */
//TODO cout d'une resource par default



require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT . '/projet/class/project.class.php');

$langs->load("admin");
$langs->load("bills");
$langs->load("other");
$langs->load("orders");

if (!$user->admin)
    accessforbidden();

/*
 * Actions
 */

if ($_GET["action"] == 'setmod') {
    // \todo Verifier si module numerotation choisi peut etre active
    // par appel methode canBeActivated

    dolibarr_set_const($db, "PROJET_ADDON", $_GET["value"]);
}

if ($_POST["action"] == 'updateMask') {
    $maskconstorder = $_POST['maskconst'];
    $maskorder = $_POST['maskprojet'];
    if ($maskconstorder)
        dolibarr_set_const($db, $maskconstorder, $maskorder);
}
$js = "<script src='" . DOL_URL_ROOT . "/lib/lib_foot.js'></script>";
llxHeader($js, "Configuration projet");

$dir = "../core/modules/synopsis_projet/";
$html = new Form($db);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans("ProjectSetup"), $linkback, 'setup');

print "<br>";
print "<br>";

//num ref


print_titre($langs->trans("ProjectNumberingModules"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Example") . '</td>';
print '<td align="center" width="60">' . $langs->trans("Activated") . '</td>';
print '<td align="center" width="16">' . $langs->trans("Info") . '</td>';
print "</tr>\n";

clearstatcache();

$dir = "../core/modules/synopsis_projet/";
$handle = opendir($dir);
if ($handle) {
    $var = true;

    while (($file = readdir($handle)) !== false) {
        if (substr($file, 0, 11) == 'mod_projet_' && substr($file, strlen($file) - 3, 3) == 'php') {
            $file = substr($file, 0, strlen($file) - 4);

            require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsis_projet/" . $file . ".php");

            $module = new $file;

            // Show modules according to features level
            if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2)
                continue;
            if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1)
                continue;

            $var = !$var;
            print '<tr ' . $bc[$var] . '><td>' . $module->nom . "</td><td>\n";
            print $module->info();
            print '</td>';

            // Examples
            print '<td nowrap="nowrap">' . $module->getExample() . "</td>\n";

            print '<td align="center">';
            if ($conf->global->PROJET_ADDON == "$file") {
                print img_picto($langs->trans("Activated"), 'switch_on');
            } else {
                print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setmod&amp;value=' . $file . '" alt="' . $langs->trans("Default") . '">' . $langs->trans("Activate") . '</a>';
            }
            print '</td>';

            $projet = new Project($db);

            // Info
            $htmltooltip = '';
            $htmltooltip.='<b>' . $langs->trans("Version") . '</b>: ' . $module->getVersion() . '<br>';
            $facture->type = 0;
            $nextval = $module->getNextValue($mysoc, $projet);
            if ("$nextval" != $langs->trans("NotAvailable")) {    // Keep " on nextval
                $htmltooltip.='<b>' . $langs->trans("NextValue") . '</b>: ';
                if ($nextval) {
                    $htmltooltip.=$nextval . '<br>';
                } else {
                    $htmltooltip.=$langs->trans($module->error) . '<br>';
                }
            }

            print '<td align="center">';
            //print $html->textwithtooltip('',$htmltooltip,1,0);
						print $html->textwithpicto('',$htmltooltip,1,0);
            print '</td>';

            print '</tr>';
        }
    }
    closedir($handle);
}

print '</table><br>';
llxFooter('$Date: 2007/05/28 11:51:00 $ - $Revision: 1.6 $');
?>
