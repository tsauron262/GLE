<?php
/* Copyright (C) 2012 Maxime MANGIN <maxime@tuxserv.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file       htdocs/contratabonnement/admin/contratabonnement_conf.php
 *  \ingroup    produit
 *  \brief      Page d'administration/configuration du module contrat d'abonnement
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT ."/smsdecanet/core/modules/modSmsDecanet.class.php");

$langs->load("admin");
$langs->load("smsdecanet@smsdecanet");

// Security check
if (!$user->admin)
accessforbidden();

if(isset($_GET['del'])) {
	$from = unserialize($conf->global->DECANETSMS_FROM);
	foreach($from as $k=>$n) {
		if($n->number==$_GET['del'])
			unset($from[$k]);
	}
	dolibarr_set_const($db, "DECANETSMS_FROM", serialize($from),'chaine',0,'',$conf->entity);
}
if ($_POST["action"] == 'majAccess')
{
	dolibarr_set_const($db, "DECANETSMS_EMAIL", $_POST["emailSMS"],'chaine',0,'',$conf->entity);
	if($_POST['passSMS']!='')dolibarr_set_const($db, "DECANETSMS_PASS", $_POST["passSMS"],'chaine',0,'',$conf->entity);
}
elseif ($_POST["action"] == 'majFrom')
{
	$from = unserialize($conf->global->DECANETSMS_FROM);
	$from[]->number = $_POST['addSMS'];
	dolibarr_set_const($db, "DECANETSMS_FROM", serialize($from),'chaine',0,'',$conf->entity);
}

/*
 * Affiche page
 */

llxHeader('',$langs->trans("DecanetSMSSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("DecanetSMSSetup"),$linkback,'setup');


$var=true;

echo '<table class="noborder" width="100%">';
echo '<tr class="liste_titre">';
echo "  <td>".$langs->trans("DiagnosticSMS")."</td>";
echo '</tr>';
echo "<tr ".$bc[$var].">";
echo '<td>';
$sms = new modSmsDecanet($DB);
$data = array(
	'action'	=>	'status',
	'login'	=>	$conf->global->DECANETSMS_EMAIL,
	'pass'	=>	$conf->global->DECANETSMS_PASS,
	'lang'	=>	$langs->defaultlang
);
$result = $sms->sendRequest($data);
if(isset($result->error)) {
	echo $result->error;
} else {
	echo '<strong>'.$langs->trans('CREDITSMS').'</strong>'.$result->credit.' '.$langs->trans('SMS').' - (<a href="http://www.decanet.fr/commander/sms?lang='.$langs->defaultlang.'" target="_blank"><strong>'.$langs->trans('RechargeSms').'</strong></a>)';
}
echo '</td>';
echo '</td>';
echo '</table><br><br>';


echo '<table class="noborder" width="100%">';
echo '<tr class="liste_titre">';
echo "  <td>".$langs->trans("ParametersAccount")."</td>\n";
echo "  <td align=\"left\" ></td>";
echo "  <td >&nbsp;</td></tr>";

$var=!$var;

echo "<form method=\"post\" action=\"smsdecanet_conf.php\">";
echo '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
echo "<input type=\"hidden\" name=\"action\" value=\"majAccess\">";
echo "<tr ".$bc[$var].">";
echo '<td>'.$langs->trans("emailSMS").'</td>';
echo '<td align="left"><input type="text" name="emailSMS" size="50" class="flat" value="'.$conf->global->DECANETSMS_EMAIL.'"></td>';
echo '<td align="right"></td>';
echo '</tr>';
$var=!$var;
echo "<tr ".$bc[$var].">";
echo '<td>'.$langs->trans("passSMS").'</td>';
echo '<td align="left"><input type="password" name="passSMS" size="50" class="flat"></td>';
echo '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
echo '</tr>';
echo '</form>';
echo '</table><br><br>';

echo '<table class="noborder" width="100%">';
echo '<tr class="liste_titre">';
echo "  <td>".$langs->trans("ParametersAccount")."</td>\n";
echo "  <td align=\"left\" ></td>";
echo "  <td >&nbsp;</td></tr>";
echo "<form method=\"post\" action=\"smsdecanet_conf.php\">";
echo '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
echo "<input type=\"hidden\" name=\"action\" value=\"majFrom\">";
echo "<tr ".$bc[$var].">";
echo '<td>'.$langs->trans("addFrom").'</td>';
echo '<td align="left"><input type="text" name="addSMS" size="50" class="flat"></td>';
echo '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
echo '</tr>';
echo '</form>';
$var=!$var;
echo "<tr ".$bc[$var].">";
echo '<td>'.$langs->trans('existant').'</td>';
echo '<td align="left"></td>';
echo '<td align="right"></td>';
echo '</tr>';
$from = unserialize($conf->global->DECANETSMS_FROM);
foreach($from as $n) {
	$var=!$var;
	echo "<tr ".$bc[$var].">";
	echo '<td> * '.$n->number.' - <a href="?del='.urlencode($n->number).'">'.$langs->trans("Delete").'</a></td>';
	echo '<td align="left"></td>';
	echo '<td align="right"></td>';
	echo '</tr>';
}
echo '</table>';
$db->close();

llxFooter('$Date: 2010/03/10 15:00:00');

?>
