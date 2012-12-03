<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 27 janv. 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : Synopsis_Chrono.php
  * GLE-1.2
  */

  //liste les type de chronos disponible


require_once("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
#require_once(DOL_DOCUMENT_ROOT.'/lib/webcal.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");

if (!$user->admin && !$user->local_admin)
    accessforbidden();


$langs->load("admin");
$langs->load("other");

$msg = "";
if($_REQUEST['action']=='setCHRONO_DISPLAY_SOC_AND_CONTACT')
{
  dolibarr_set_const($db, "CHRONO_DISPLAY_SOC_AND_CONTACT", $_REQUEST["CHRONO_DISPLAY_SOC_AND_CONTACT"]);

}

if ($_REQUEST['action']=='add')
{
    $nom = addslashes($_REQUEST['nom']);
    $desc = addslashes($_REQUEST['desc']);
    $modeleRef = addslashes($_REQUEST['modeleRef']);
    $hasFile = (preg_match('/on/i',$_REQUEST['attachFile'])==1?1:0);
    $hasSoc = (preg_match('/on/i',$_REQUEST['hasSoc'])==1?1:0);
    $hasContact = (preg_match('/on/i',$_REQUEST['hasContact'])==1?1:0);
    $hasRevision = (preg_match('/on/i',$_REQUEST['hasRevision'])==1?1:0);

    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_conf WHERE titre = '".$nom."'";
    $sql = $db->query($requete);
    if ($db->num_rows($sql) > 0){
        $msg = "Un Chrono du m&ecirc;me nom existe d&eacute;j&agrave;";

    } else {
        $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_Chrono_conf (titre,description,modele,hasFile,hasSociete,hasRevision, hasContact, date_create)
                         VALUES ('".$nom."','".$desc."','".$modeleRef."',".$hasFile.",".$hasSoc.",".$hasRevision.",".$hasContact.",now())";
        $sql = $db->query($requete);
    }
}

if ($_REQUEST['action']=='mod')
{
    $nom = addslashes($_REQUEST['nom']);
    $desc = addslashes($_REQUEST['desc']);
    $modeleRef = addslashes($_REQUEST['modeleRef']);
    $hasFile = (preg_match('/on/i',$_REQUEST['attachFile'])==1?1:0);
    $hasSoc = (preg_match('/on/i',$_REQUEST['hasSoc'])==1?1:0);
    $hasContact = (preg_match('/on/i',$_REQUEST['hasContact'])==1?1:0);
    $hasRevision = (preg_match('/on/i',$_REQUEST['hasRevision'])==1?1:0);

    $reqeute = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_conf WHERE titre = '".$nom."' AND id <> ".$_REQUEST['id'];
    $sql = $db->query($reqeute);
    if ($db->num_rows($sql) > 0){
        $msg = "Un Chrono du m&ecirc;me nom existe d&eacute;j&agrave;";

    } else {
        $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_Chrono_conf
                       SET titre = '".$nom."',
                           description= '".$desc."',
                           modele = '".$modeleRef."',
                           hasFile = ".$hasFile.",
                           hasSociete = ".$hasSoc.",
                           hasRevision = ".$hasRevision.",
                           hasContact = ".$hasContact."
                     WHERE id = ".$_REQUEST['id'];
        $sql = $db->query($requete);
    }
}

/**
 * Affichage du formulaire de saisie
 */

llxHeader("","Config Chrono");
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("Chrono"),$linkback,'setup');

if ($msg."x" != "x"){ print "<div class='ui-error ui-state-error' style='width:100%; padding:3px;'><span style='float: left;' class='ui-icon ui-icon-info'></span>".$msg."</div>"; }

//print_titre($langs->trans("GEOBI"));
print '<br>';

$h = 0;
$head = array();

$head[$h][0] = DOL_URL_ROOT.'/admin/Synopsis_Chrono.php';
$head[$h][1] = $langs->trans("Config.");
$head[$h][2] = 'main';
$h++;
dol_fiche_head($head, 'Chrono', $langs->trans("Chrono"));

print "<br/><br/><br/>";
$html=new Form($db);

print "<table width=100% cellpadding=15>";
print "<tr class=\"liste_titre\">";
print "<th style='color:white'>Configuration g&eacute;n&eacute;rale";
print "</table>";

print "<form action='Synopsis_Chrono.php?action=setCHRONO_DISPLAY_SOC_AND_CONTACT' method='post'>";
print "<table width=100%>";
print "<tr><th class='ui-widget-header ui-state-default'>Affiche les soci&eacute;t&eacute;s et les contacts dans la liste des chronos<td class='ui-widget-content'>";
print $html->selectyesno("CHRONO_DISPLAY_SOC_AND_CONTACT",$conf->global->CHRONO_DISPLAY_SOC_AND_CONTACT,1);
print "<td class='ui-widget-content'><button class='butAction'>Modifier</button>";

//CHRONO_DISPLAY_SOC_AND_CONTACT
print "</table>";
print "</form>";
print "<br/><br/><br/>";


print "<table class=\"noborder\" width=\"100%\" cellpadding=5>";

print "<tr class=\"liste_titre\">";
print "<td style='border:1px Solid; border-top-color: #0073EA; border-left-color: #0073EA;' width=\"20%\">".$langs->trans("Nom du chrono")."</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>".$langs->trans("Mod&egrave;le de ref.")."</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>".$langs->trans("Example.")."</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>".$langs->trans("Attacher un fichier")."</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>".$langs->trans("Lier &agrave; une soc.")."</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>".$langs->trans("Lier &agrave; un contact")."</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>".$langs->trans("Revision")."</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA;  border-right-color: #0073EA;' align=center rowspan=2>".$langs->trans("Action")."</td>";
print "<tr class=\"liste_titre\">";
print "<td style='border:1px Solid; border-left-color: #0073EA;' colspan=7>".$langs->trans("Description")."</td>";
print "</tr>";
$requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_conf ORDER BY titre";
$sql = $db->query($requete);
$classArr[true]="pair";
$classArr[false]="impair";
$bool=false;
while($res=$db->fetch_object($sql))
{
    $bool = !$bool;
    if ($_REQUEST['action'] == 'modify' && $_REQUEST['id'] == $res->id)
    {
        print "</table>";
        print "<table class=\"noborder\" width=\"100%\" cellpadding=5>";
        print '<form name="ChronoConfig" action="Synopsis_Chrono.php" method="post">';
        print "<input type='hidden' name='action' value='mod' />";
        print "<input type='hidden' name='id' value='".$res->id."' />";
        print "<tr class=\"liste_titre\">";
        print "<td align=center width=\"20%\">".$langs->trans("Nom du chrono")."</td>";
        print "<td align=center>".$langs->trans("Description")."</td>";
        print "<td align=center>".$langs->trans("Mod&egrave;le de ref.")."</td>";
        print "<td align=center>".$langs->trans("Attacher un fichier")."</td>";
        print "<td align=center>".$langs->trans("Lier &agrave; une soc.")."</td>";
        print "<td align=center>".$langs->trans("Lier &agrave; un contact")."</td>";
        print "<td align=center>".$langs->trans("Revision")."</td>";
        print "<td align=center>".$langs->trans("Action")."</td>";
        print "</tr>";
        print "<tr>";
        print "<td align=center><input style='text-align:center; width:90%;' name='nom' value='".$res->titre."'></input>";
        print "<td align=center><textarea name='desc' style='width: 400px; height: 80px;'>".$res->description."</textarea>";
        print "<td align=center><input style='text-align:center; width:90%;'  name='modeleRef' value='".$res->modele."'></input>";
        print "<td align=center><input name='attachFile' type='checkbox' ".($res->hasFile==1?'Checked':'')."></input>";
        print "<td align=center><input name='hasSoc' type='checkbox' ".($res->hasSociete==1?'Checked':'')."></input>";
        print "<td align=center><input name='hasContact' type='checkbox' ".($res->hasContact==1?'Checked':'')."></input>";
        print "<td align=center><input name='hasRevision' type='checkbox' ".($res->hasRevision==1?'Checked':'')."></input>";
        print "<td class=' ".$classArr[$bool]."' align=center style='border-right: 1px Solid; border-bottom: 1px Solid;'><button onClick='location.href=\"Synopsis_Chrono.php?action=modify&id=".$res->id."\"' class='butAction'>Modifier</button><br/><button onClick='location.href=\"Synopsis_Chrono.php\"' class='butAction'>Annuler</button>";
        print "</table>";
        print "<table class=\"noborder\" width=\"100%\" cellpadding=5>";
    } else {
        require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Chrono/modules_chrono.php');
        $ModeleNumRefChrono = new mod_chrono_serpentine($db);
        $exampleRef =  $ModeleNumRefChrono->getExample($res->modele,$res->id);
        print "<tr><td width=\"20%\" class=' ".$classArr[$bool]."' style='border-left: 1px solid;'>".$res->titre."</td>";
        print "<td class=' ".$classArr[$bool]."'>".$res->modele."</td>";
        print "<td class=' ".$classArr[$bool]."'>".$exampleRef."</td>";
        print "<td class=' ".$classArr[$bool]."'>".($res->hasFile==1?"Avec Fichier":"Sans fichier")."</td>";
        print "<td class=' ".$classArr[$bool]."'>".($res->hasSociete==1?"Oui":"Non")."</td>";
        print "<td class=' ".$classArr[$bool]."'>".($res->hasContact==1?"Oui":"Non")."</td>";
        print "<td class=' ".$classArr[$bool]."'>".($res->hasRevision==1?"Oui":"Non")."</td>";
        print "<td class=' ".$classArr[$bool]."' align=center rowspan=2 style='border-right: 1px Solid; border-bottom: 1px Solid;'>";
        print "<button onClick='location.href=\"Synopsis_Chrono.php?action=modify&id=".$res->id."\"' class='butAction'>".img_edit("")."  Modifier</button>";
        print "<button onClick='location.href=\"Synopsis_Chrono_advMode.php?id=".$res->id."\"' class='butAction'>".img_edit("")."  Config. avanc&eacute;e</button>";
        print "<tr><td class=' ".$classArr[$bool]."' style='border-left: 1px Solid; border-bottom: 1px Solid;' colspan=7>".$res->description."</td>";
    }
}
print "</TABLE>";

if ($_REQUEST['action'] != 'modify'){
    print "<br/>";
    print "<br/>";
    print "<br/>";

    print '<form name="ChronoConfig" action="Synopsis_Chrono.php" method="post">';
    print "<input type='hidden' name='action' value='add' />";
    print "<table class=\"noborder\" width=\"100%\" cellpadding=5>";
    print "<tr class=\"liste_titre\">";
    print "<td align=center width=\"20%\">".$langs->trans("Nom du chrono")."</td>";
    print "<td align=center>".$langs->trans("Description")."</td>";
    print "<td align=center>".$langs->trans("Mod&egrave;le de ref.")."</td>";
    print "<td align=center>".$langs->trans("Attacher un fichier")."</td>";
    print "<td align=center>".$langs->trans("Lier &agrave; une soc.")."</td>";
    print "<td align=center>".$langs->trans("Lier &agrave; un contact")."</td>";
    print "<td align=center>".$langs->trans("Revision")."</td>";
    print "</tr>";
    print "<tr>";
    print "<td class='ui-widget-content' align=center><input style='text-align:center; width:90%;' name='nom'></input>";
    print "<td class='ui-widget-content' align=center><textarea name='desc' style='width: 400px; height: 80px;'></textarea>";
    print "<td class='ui-widget-content' align=center><input style='text-align:center; width:90%;'  name='modeleRef'></input>";
    print "<td class='ui-widget-content' align=center><input name='attachFile' type='checkbox'></input>";
    print "<td class='ui-widget-content' align=center><input name='hasSoc' type='checkbox'></input>";
    print "<td class='ui-widget-content' align=center><input name='hasContact' type='checkbox'></input>";
    print "<td class='ui-widget-content' align=center><input name='hasRevision' type='checkbox'></input>";
    print "</TABLE>";

    print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Ajouter")."\">";
}
print "</center><br/><br/><br/>";



print "<div class='ui-state-highlight' style='padding:5px'><b><span class='ui-icon ui-icon-info' style='float:left; margin-top: -1px;'></span>&nbsp;&nbsp;Note sur les mod&egrave;les de num&eacute;rotation:</b><br/><br/>".$langs->trans("GenericMaskCodes",$langs->transnoentities("Chrono"),$langs->transnoentities("Chrono"),$langs->transnoentities("Chrono"))."</div>";


print "</form>\n";

llxFooter('$Date: 2005/10/03 01:36:21 $ - $Revision: 1.23 $');

?>
