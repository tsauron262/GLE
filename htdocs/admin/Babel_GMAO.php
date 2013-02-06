<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 19 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : Babel_GMAO.php GLE-1.2
  */
require_once('pre.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/Babel_GMAO/SAV.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

if (!$user->admin)
  accessforbidden();

if ($_POST["action"] == 'updateMask')
{
    $mask=$_REQUEST['masksav'];
    $maskConst = $_REQUEST['maskconst'];
    if ("x".$maskConst != "x" &&$mask."x" != "x") dolibarr_set_const($db,$maskConst,$mask,'',0);
}


if ($_GET["action"] == 'setmod')
{
    // \todo Verifier si module numerotation choisi peut etre active
    // par appel methode canBeActivated

    dolibarr_set_const($db, "SAV_ADDON",$_GET["value"]);
}
if($_REQUEST['action']=="GLE_RT_ROOT")
{
    dolibarr_set_const($db, "GLE_RT_ROOT",$_REQUEST["GLE_RT_ROOT"]);

}
if($_REQUEST['action']=="GLE_RT_USER")
{
    dolibarr_set_const($db, "GLE_RT_USER",$_REQUEST["GLE_RT_USER"]);

}
if($_REQUEST['action']=="GLE_RT_PASS")
{
    dolibarr_set_const($db, "GLE_RT_PASS",$_REQUEST["GLE_RT_PASS"]);

}

if ($_POST["action"] == 'form_GMAO_TKT_RESTANT_WARNING') {
    dolibarr_set_const($db, "GMAO_TKT_RESTANT_WARNING", $_POST["GMAO_TKT_RESTANT_WARNING"]);
}
llxHeader($js,$langs->trans("GMAO"));

$dir = DOL_DOCUMENT_ROOT."/includes/modules/sav/";
$html=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("Configuration du module de gestion de la SAV"),$linkback,'setup');




/*
 *  Module numerotation
 */
print "<br>";
print_titre($langs->trans("Modules de num&eacute;rotation des fiches SAV"));

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
        if (substr($file, 0, 8) == 'mod_sav_' && substr($file, strlen($file)-3, 3) == 'php')
        {
            $file = substr($file, 0, strlen($file)-4);
            require_once(DOL_DOCUMENT_ROOT ."/includes/modules/sav/".$file.".php");

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
            if ($conf->global->SAV_ADDON == "$file")
            {
                print img_tick($langs->trans("Activated"));
            } else {
                print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Activate").'</a>';
            }
            print '</td>';

            $ctr=new SAV($db);
            //$ctr->initAsSpecimen();

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
                } else {
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
//print "<tr><td>".$langs->trans('Liste des p&eacute;riodes disponibles');
//print "    <td><table>";
//$requete = "SELECT * FROM ";
//
//
//print "</table></td>";
print "</table><br>\n";


print "<form method=\"post\" action=\"Babel_GMAO.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"form_GMAO_TKT_RESTANT_WARNING\">";
print "<table width=100%>";
print "<tr ".$bc[$var].">";
print '<td width=65%>'.$langs->trans("Seuil d'alerte pour les interventions restants dans un contrat").'</td>';
print '<td width="30%" align="right">';
print "<input size='4' name='GMAO_TKT_RESTANT_WARNING' value='".$conf->global->GMAO_TKT_RESTANT_WARNING."' type='text'>";
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';
print "</table>";
print '</form>';
//

print "<form method=\"post\" action=\"Babel_GMAO.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"GLE_RT_ROOT\">";
print "<table width=100%>";
print "<tr ".$bc[$var].">";
print '<td width=65%>'.$langs->trans("Racine RT (ex http://rt.finapro.fr/rt)").'</td>';
print '<td width="30%" align="right">';
print "<input size='15' name='GLE_RT_ROOT' value='".$conf->global->GLE_RT_ROOT."' type='text'>";
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';
print "</table>";
print '</form>';

print "<form method=\"post\" action=\"Babel_GMAO.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"GLE_RT_USER\">";
print "<table width=100%>";
print "<tr ".$bc[$var].">";
print '<td width=65%>'.$langs->trans("Admin RT").'</td>';
print '<td width="30%" align="right">';
print "<input size='15' name='GLE_RT_USER' value='".$conf->global->GLE_RT_USER."' type='text'>";
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';
print "</table>";
print '</form>';

print "<form method=\"post\" action=\"Babel_GMAO.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"GLE_RT_PASS\">";
print "<table width=100%>";
print "<tr ".$bc[$var].">";
print '<td width=65%>'.$langs->trans("Mot de passe admin RT").'</td>';
print '<td width="30%" align="right">';
print "<input size='15' name='GLE_RT_PASS' value='".$conf->global->GLE_RT_PASS."' type='password'>";
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';
print "</table>";
print '</form>';



llxFooter('$Date: 2010/03/11 14:20:05 $ - $Revision: 1.71 $');


?>
