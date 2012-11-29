<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 5 avr. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : Babel_GPI.php
  * dolibarr-24dev
  */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");
$langs->load("contracts");
$langs->load("other");
$langs->load("synopsisGene@Synopsis_Tools");

if (!$user->admin)
  accessforbidden();



if ($_REQUEST["action"] == 'set')
{
        dolibarr_set_const($db, 'MAIN_MODULE_BABELGPI_THEME', $_REQUEST['themeGPI'], 'chaine');
}

llxHeader('',$langs->trans("Acc&egrave;s externe"));

$html=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("Configuration du module d'acc&egrave;s externe"),$linkback,'setup');


print '<h3>Choix du theme</h3>';


print "<form action='".$_SERVER['PHP_SELF']."' method='post'>";
print "<input name='action' value='set' type='hidden'>";
print '<table width=400><tr><td valign=middle align=center class="ui-widget-header ui-state-default">';
print 'Th&egrave;me par d&eacute;faut';
print '<td style=" padding-left: 55px;" class="ui-widget-header">';
print '<select name="themeGPI">';
$dir = DOL_DOCUMENT_ROOT.'/Babel_GPI/css/';
$dirhandle = @opendir($dir);

//List files in uploads directory
while (($file = readdir($dirhandle)) !== false)
{
    if(!preg_match("/^[\.]/", $file)&& is_dir($dir.$file))
    {
        if ($file == $conf->global->MAIN_MODULE_BABELGPI_THEME)
        {
            print '<option selected value="'.$file.'">'.  $file.'</option>';
        } else {
            print '<option value="'.$file.'">'.  $file.'</option>';

        }
    }
}
closedir($dir);
print "</select>";
print "</table>";
print '<br>';
print "<button class='ui-corner-all butAction ui-state-default u-widget-header'>Enregistrer</button>";
print "</form>";
clearstatcache();


llxFooter('$Date: 2008/07/05 14:20:05 $ - $Revision: 1.71 $');

?>
