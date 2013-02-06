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
  * Name : Babel_PrepaCommande.php
  * GLE-1.2
  */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

$langs->load("admin");
$langs->load("bills");
$langs->load("propal");
$langs->load("other");

if (!$user->admin)
  accessforbidden();


if ($_POST["action"] == 'form_PREPACOMMANDE_SHOW_FINANCE_FULL_DETAIL')
{
    dolibarr_set_const($db, "PREPACOMMANDE_SHOW_FINANCE_FULL_DETAIL",$_POST["PREPACOMMANDE_SHOW_FINANCE_FULL_DETAIL"]);
}
if ($_POST["action"] == 'form_PREPACOMMANDE_SHOW_WEEK_WHEN_TEMPORARY')
{
    dolibarr_set_const($db, "PREPACOMMANDE_SHOW_WEEK_WHEN_TEMPORARY",$_POST["PREPACOMMANDE_SHOW_WEEK_WHEN_TEMPORARY"]);
}

llxHeader('',$langs->trans(utf8_decode("Pr&eacute;paration de commande")));

$dir = "../includes/modules/Affaire/";
$html=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans(utf8_decode("Pr&eacute;paration de commande")),$linkback,'setup');

// Valider la commande apres cloture de la propale
// permet de na pas passer par l'option commande provisoire
$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="form_PREPACOMMANDE_SHOW_FINANCE_FULL_DETAIL">';
print "<table>";
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("Affiche le d&eacute;tail de chaque commande dans la validation financi&egrave;re").'</td>';
print '<td width="60" align="center">'.$html->selectyesno("PREPACOMMANDE_SHOW_FINANCE_FULL_DETAIL",$conf->global->PREPACOMMANDE_SHOW_FINANCE_FULL_DETAIL,1).'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print "</table>";
print '</form>';
$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="form_PREPACOMMANDE_SHOW_WEEK_WHEN_TEMPORARY">';
print "<table>";
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("Affiche la semaine de disponibilit&eacute; au lieu de la date ").'</td>';
print '<td width="60" align="center">'.$html->selectyesno("PREPACOMMANDE_SHOW_WEEK_WHEN_TEMPORARY",$conf->global->PREPACOMMANDE_SHOW_WEEK_WHEN_TEMPORARY,1).'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print "</table>";
print '</form>';



?>