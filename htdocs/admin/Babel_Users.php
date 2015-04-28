<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 15 nov. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : Babel_Users.php
  * GLE-1.2
  */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

$langs->load("admin");
$langs->load("bills");
$langs->load("propal");
$langs->load("other");

if (!$user->admin)
  accessforbidden();


if ($_POST["action"] == 'form_FIRSTNAME_BEFORE_NAME')
{
    dolibarr_set_const($db, "FIRSTNAME_BEFORE_NAME",$_POST["FIRSTNAME_BEFORE_NAME"]);
}

llxHeader('',$langs->trans(utf8_decode("Utilisateurs")));

$html=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans(utf8_decode("Utilisateurs")),$linkback,'setup');

// Valider la commande apres cloture de la propale
// permet de na pas passer par l'option commande provisoire
$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="form_FIRSTNAME_BEFORE_NAME">';
print "<table>";
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("Afficher le pr&eacute;name avant le nom dans les menus d&eacute;roulants").'</td>';
print '<td width="60" align="center">'.$html->selectyesno("FIRSTNAME_BEFORE_NAME",$conf->global->FIRSTNAME_BEFORE_NAME,1).'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print "</table>";
print '</form>';



?>