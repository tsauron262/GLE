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
/*
 */

/**
        \file       htdocs/admin/Babel_Affaire.php
        \ingroup    propale
        \brief      Page d'administration/configuration du module Affaire
        \version    $Id: propale.php,v 1.71 2008/07/05 14:20:05 eldy Exp $
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_Affaire/Affaire.class.php");

$langs->load("admin");
$langs->load("bills");
$langs->load("propal");
$langs->load("other");

if (!$user->admin)
  accessforbidden();

if ($_POST["action"] == 'updateMask')
{
    $maskconstpropal=$_POST['maskconstaffaire'];
    $maskpropal=$_POST['maskaffaire'];
    if ($maskconstpropal)  dolibarr_set_const($db,$maskconstpropal,$maskpropal);
}
if ($_GET["action"] == 'setmod')
{
    // \todo Verifier si module numerotation choisi peut etre active
    // par appel methode canBeActivated

    dolibarr_set_const($db, "AFFAIRE_ADDON",$_GET["value"]);
}




/*
 * Affiche page
 */

llxHeader('',$langs->trans("Affaire"));

$dir = "../includes/modules/Affaire/";
$html=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("Affaire"),$linkback,'setup');
/*
 *  Module numerotation
 */
print "<br>";
print_titre($langs->trans("Mod&egrave;le de num&eacute;rotation des affaires"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td nowrap>'.$langs->trans("Example")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print '</tr>'."\n";

clearstatcache();

$handle = opendir($dir);
if ($handle)
{
    $var=true;
    while (($file = readdir($handle))!==false)
    {
        if (substr($file, 0, 12) == 'mod_affaire_' && substr($file, strlen($file)-3, 3) == 'php')
        {
            $file = substr($file, 0, strlen($file)-4);

            require_once(DOL_DOCUMENT_ROOT ."/includes/modules/Affaire/".$file.".php");

            $module = new $file;

            // Show modules according to features level
            if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
            if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

            $var=!$var;
            print '<tr '.$bc[$var].'><td>'.$module->nom."</td><td>\n";
            print $module->info();
            print '</td>';

            // Examples
            print '<td nowrap="nowrap">'.$module->getExample()."</td>\n";

            print '<td align="center">';
            if ($conf->global->AFFAIRE_ADDON == "$file")
            {
                print img_tick($langs->trans("Activated"));
            }
            else
            {
                print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Activate").'</a>';
            }
            print '</td>';

            $propale=new Affaire($db);

            // Info
            $htmltooltip='';
            $htmltooltip.='<b>'.$langs->trans("Version").'</b>: '.$module->getVersion().'<br>';
            $facture->type=0;
            $nextval=$module->getNextValue($mysoc,$propale);
            if ("$nextval" != $langs->trans("NotAvailable"))    // Keep " on nextval
            {
                $htmltooltip.='<b>'.$langs->trans("NextValue").'</b>: ';
                if ($nextval)
                {
                    $htmltooltip.=$nextval.'<br>';
                }
                else
                {
                    $htmltooltip.=$langs->trans($module->error).'<br>';
                }
            }

            print '<td align="center">';
            print $html->textwithtooltip('',$htmltooltip,1,0);
            print '</td>';

            print "</tr>\n";
        }
    }
    closedir($handle);
}
print "</table><br>\n";

?>