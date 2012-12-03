<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 24 aout 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : configJour_html-response.php
  * GLE-1.1
  */
require_once('../../main.inc.php');


//                    + configuration :> envoie des doc en PJ ou lien interne / externe (zimbra V2) + template du mail


$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';

print "<html><head></head><body></body>";
print $header;

  $idmenu=$_GET['idmenu'];
  print '<form name="configProj" method="POST"  action="config.php?idmenu='.$idmenu.'&selectedTabs=3">'; //TODO change to selecteTabs
  print '<input name="action" id="action" type=hidden value="updtEmailProj"></input>';
  print '<table width=1100px style="border-collapse:collapse;">';
  print '<tr>';
  print '<th><span style="padding-top:20pt; color:#FFFFFF">Envoie des documents en pi&egrave;ce jointe ?</span>';
  print '</th>';
  print '<td><input name="PJDoc" id="PJDoc" type=checkbox '. ($conf->global->PROJECT_PJDOC_EMAIL>0?"checked":"") .'></input>';
  print '</td>';
  print '</tr>';
  print '<tr>';
  print '<th<span style="padding-top:20pt; color:#FFFFFF">Affiche le lien du document dans le mail ?</span></th>';
  print '</th>';
  print '<td><input name="linkDoc" id="linkDoc" type=checkbox '. ($conf->global->PROJECT_LINKDOC_EMAIL>0?"checked":"") .' ></input>';
  print '</td>';
  print '</tr>';

  print '<tr>';
  print '<th style="vertical-align:top"><br/><br/><span style="color:#FFFFFF">Template du mail</span>
            <i><p style="width: 95%; color: #333333;  background-color:#EEEEEE; padding: 5pt; text-align: left; font-weight:lighter;padding-top:10pt; font-size:9pt;">#projectAdminMail# => Email du responsable de projet<br/>
               #projectAdminName# => Nom du responsable de projet<br/>
               #projectAdminFirstName# => Pr&eacute;nom du responsable de projet<br/>
               #currentDate# => Date du jour (dd/mm/YYYY)<br/>
               #docName# =>Nom du document<br/>
               #taskName# =>Nom de la t&acirc;che<br/>
               #projectName# =>Nom du projet<br/>
</p></i><br/>';
  print '</th>';
  print '<td><textarea style="width: 600px; height: 300px;" name="mailTemplate" id="mailTemplate" >'.$conf->global->PROJECT_TEMPLATE_EMAIL.'</textarea>';
  print '</td>';
  print '</tr>';


  print '<tr>';
  print '<td colspan=2 align=right><input class="butAction"  type="submit" value="Valider" ></input>';
  print '</td>';
  print '</tr>';
  print '</table>';

  print '</form>';



?>