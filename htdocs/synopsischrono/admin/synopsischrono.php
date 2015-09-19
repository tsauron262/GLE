<?php

/*
 * * GLE by Synopsis et DRSI
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
 * Name : synopsischrono.php
 * GLE-1.2
 */
//liste les type de chronos disponible


require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php");
#require_once(DOL_DOCUMENT_ROOT.'/lib/webcal.class.php');
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");

if (!$user->admin && !$user->local_admin)
    accessforbidden();


$langs->load("admin");
$langs->load("other");

$msg = "";
if ($_REQUEST['action'] == 'setCHRONO_DISPLAY_SOC_AND_CONTACT') {
    dolibarr_set_const($db, "CHRONO_DISPLAY_SOC_AND_CONTACT", $_REQUEST["CHRONO_DISPLAY_SOC_AND_CONTACT"]);
}

if ($_REQUEST['action'] == 'add') {
    $nom = addslashes($_REQUEST['nom']);
    $desc = addslashes($_REQUEST['desc']);
    $modeleRef = addslashes($_REQUEST['modeleRef']);
    $hasFile = (preg_match('/on/i', $_REQUEST['attachFile']) == 1 ? 1 : 0);
    $hasSoc = (preg_match('/on/i', $_REQUEST['hasSoc']) == 1 ? 1 : 0);
    $hasContact = (preg_match('/on/i', $_REQUEST['hasContact']) == 1 ? 1 : 0);
    $hasRevision = (preg_match('/on/i', $_REQUEST['hasRevision']) == 1 ? 1 : 0);
    $hasDescription = (preg_match('/on/i', $_REQUEST['hasDescription']) == 1 ? 1 : 0);
    $hasStatut = (preg_match('/on/i', $_REQUEST['hasStatut']) == 1 ? 1 : 0);
    $hasSuivie = (preg_match('/on/i', $_REQUEST['hasSuivie']) == 1 ? 1 : 0);
    $hasPropal = (preg_match('/on/i', $_REQUEST['hasPropal']) == 1 ? 1 : 0);
    $hasProjet = (preg_match('/on/i', $_REQUEST['hasProjet']) == 1 ? 1 : 0);

    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_conf WHERE titre = '" . $nom . "'";
    $sql = $db->query($requete);
    if ($db->num_rows($sql) > 0) {
        $msg = "Un Chrono du m&ecirc;me nom existe d&eacute;j&agrave;";
    } else {
        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsischrono_conf (titre,description,modele,hasFile,hasSociete,hasRevision,hasDescription,hasStatut,hasSuivie, hasContact, hasPropal, hasProjet, date_create)
                         VALUES ('" . $nom . "','" . $desc . "','" . $modeleRef . "'," . $hasFile . "," . $hasSoc . "," . $hasRevision . "," . $hasDescription . "," . $hasStatut . "," . $hasSuivie . "," . $hasContact . "," . $hasPropal . "," . $hasProjet . ",now())";
        $sql = $db->query($requete);
        iniTabChronoList();
    }
}

if ($_REQUEST['action'] == 'mod') {
    $nom = addslashes($_REQUEST['nom']);
    $desc = addslashes($_REQUEST['desc']);
    $modeleRef = addslashes($_REQUEST['modeleRef']);
    $hasFile = (preg_match('/on/i', $_REQUEST['attachFile']) == 1 ? 1 : 0);
    $hasSoc = (preg_match('/on/i', $_REQUEST['hasSoc']) == 1 ? 1 : 0);
    $hasContact = (preg_match('/on/i', $_REQUEST['hasContact']) == 1 ? 1 : 0);
    $hasRevision = (preg_match('/on/i', $_REQUEST['hasRevision']) == 1 ? 1 : 0);
    $hasDescription = (preg_match('/on/i', $_REQUEST['hasDescription']) == 1 ? 1 : 0);
    $hasStatut = (preg_match('/on/i', $_REQUEST['hasStatut']) == 1 ? 1 : 0);
    $hasSuivie = (preg_match('/on/i', $_REQUEST['hasSuivie']) == 1 ? 1 : 0);

    $hasPropal = (preg_match('/on/i', $_REQUEST['hasPropal']) == 1 ? 1 : 0);
    $hasProjet = (preg_match('/on/i', $_REQUEST['hasProjet']) == 1 ? 1 : 0);

    $reqeute = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_conf WHERE titre = '" . $nom . "' AND id <> " . $_REQUEST['id'];
    $sql = $db->query($reqeute);
    if ($db->num_rows($sql) > 0) {
        $msg = "Un Chrono du m&ecirc;me nom existe d&eacute;j&agrave;";
    } else {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "synopsischrono_conf
                       SET titre = '" . $nom . "',
                           description= '" . $desc . "',
                           modele = '" . $modeleRef . "',
                           hasFile = " . $hasFile . ",
                           hasSociete = " . $hasSoc . ",
                           hasRevision = " . $hasRevision . ",
                           hasContact = " . $hasContact . ",
                           hasPropal = " . $hasPropal . ",
                           hasProjet = " . $hasProjet . ",
                           hasDescription = " . $hasDescription . ",
                           hasStatut = " . $hasStatut . ",
                           hasSuivie = " . $hasSuivie . ",
                           nomDescription = '" . $_REQUEST['nomDescription'] . "',
                           typeDescription = '" . $_REQUEST['typeDescription'] . "'
                     WHERE id = " . $_REQUEST['id'];
        $sql = $db->query($requete);
        iniTabChronoList();
    }
}

/**
 * Affichage du formulaire de saisie
 */
llxHeader("", "Config Chrono");
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';

print_fiche_titre($langs->trans("Chrono"), $linkback, 'setup');

if ($msg . "x" != "x") {
    print "<div class='ui-error ui-state-error' style='width:100%; padding:3px;'><span style='float: left;' class='ui-icon ui-icon-info'></span>" . $msg . "</div>";
}

//load_fiche_titre($langs->trans("GEOBI"));
print '<br>';

$h = 0;
$head = array();

$head[$h][0] = DOL_URL_ROOT . '//synopsischrono/admin/synopsischrono.php';
$head[$h][1] = $langs->trans("Config.");
$head[$h][2] = 'main';
$h++;
dol_fiche_head($head, 'Chrono', $langs->trans("Chrono"));

print "<br/><br/><br/>";
$html = new Form($db);

print "<table width=100% cellpadding=15>";
print "<tr class=\"liste_titre\">";
print "<th style='color:white'>Configuration g&eacute;n&eacute;rale";
print "</table>";

print "<form action='synopsischrono.php?action=setCHRONO_DISPLAY_SOC_AND_CONTACT' method='post'>";
print "<table width=100%>";
print "<tr><th class='ui-widget-header ui-state-default'>Affiche les soci&eacute;t&eacute;s et les contacts dans la liste des chronos<td class='ui-widget-content'>";
print $html->selectyesno("CHRONO_DISPLAY_SOC_AND_CONTACT", $conf->global->CHRONO_DISPLAY_SOC_AND_CONTACT, 1);
print "<td class='ui-widget-content'><button class='butAction'>Modifier</button>";

//CHRONO_DISPLAY_SOC_AND_CONTACT
print "</table>";
print "</form>";
print "<br/><br/><br/>";


print "<table class=\"noborder\" width=\"100%\" cellpadding=5>";

print "<tr class=\"liste_titre\">";
print "<td style='border:1px Solid; border-top-color: #0073EA; border-left-color: #0073EA;' width=\"20%\">" . $langs->trans("Nom du chrono") . "</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>" . $langs->trans("Mod&egrave;le de ref.") . "</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>" . $langs->trans("Example.") . "</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>" . $langs->trans("Attacher un fichier") . "</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>" . $langs->trans("Lier &agrave; une soc.") . "</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>" . $langs->trans("Lier &agrave; un contact") . "</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>" . $langs->trans("Lier &agrave; une propal") . "</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>" . $langs->trans("Lier &agrave; un projet") . "</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>" . $langs->trans("Revision") . "</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>" . $langs->trans("Description") . "</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>" . $langs->trans("Statut") . "</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA; '>" . $langs->trans("Suivi") . "</td>";
print "<td style='border:1px Solid; border-top-color: #0073EA;  border-right-color: #0073EA;' align=center rowspan=2>" . $langs->trans("Action") . "</td>";
print "<tr class=\"liste_titre\">";
print "<td style='border:1px Solid; border-left-color: #0073EA;' colspan=12>" . $langs->trans("Description") . "</td>";
print "</tr>";
$requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_conf ORDER BY titre";
$sql = $db->query($requete);
$classArr[true] = "pair";
$classArr[false] = "impair";
$bool = false;
while ($res = $db->fetch_object($sql)) {
    $bool = !$bool;
    if ($_REQUEST['action'] == 'modify' && $_REQUEST['id'] == $res->id) {
        print "</table>";
        print "<table class=\"noborder\" width=\"100%\" cellpadding=5>";
        print '<form name="ChronoConfig" action="synopsischrono.php" method="post">';
        print "<input type='hidden' name='action' value='mod' />";
        print "<input type='hidden' name='id' value='" . $res->id . "' />";
        print "<tr class=\"liste_titre\">";
        print "<td align=center width=\"20%\">" . $langs->trans("Nom du chrono") . "</td>";
        print "<td align=center>" . $langs->trans("Description") . "</td>";
        print "<td align=center>" . $langs->trans("Mod&egrave;le de ref.") . "</td>";
        print "<td align=center>" . $langs->trans("Attacher un fichier") . "</td>";
        print "<td align=center>" . $langs->trans("Lier &agrave; une soc.") . "</td>";
        print "<td align=center>" . $langs->trans("Lier &agrave; un contact") . "</td>";
        print "<td align=center>" . $langs->trans("Lier &agrave; une propal") . "</td>";
        print "<td align=center>" . $langs->trans("Lier &agrave; un projet") . "</td>";
        print "<td align=center>" . $langs->trans("Revision") . "</td>";
        print "<td align=center>" . $langs->trans("Description") . "</td>";
        print "<td align=center>" . $langs->trans("Statut") . "</td>";
        print "<td align=center>" . $langs->trans("Suivi") . "</td>";
        print "<td align=center>" . $langs->trans("Label champ desc") . "</td>";
        print "<td align=center>" . $langs->trans("Type champ desc") . "</td>";
        print "<td align=center>" . $langs->trans("Action") . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td align=center><input style='text-align:center; width:90%;' name='nom' value='" . $res->titre . "'></input>";
        print "<td align=center><textarea name='desc' style='width: 400px; height: 80px;'>" . $res->description . "</textarea>";
        print "<td align=center><input style='text-align:center; width:90%;'  name='modeleRef' value='" . $res->modele . "'></input>";
        print "<td align=center><input name='attachFile' type='checkbox' " . ($res->hasFile == 1 ? 'Checked' : '') . "></input>";
        print "<td align=center><input name='hasSoc' type='checkbox' " . ($res->hasSociete == 1 ? 'Checked' : '') . "></input>";
        print "<td align=center><input name='hasContact' type='checkbox' " . ($res->hasContact == 1 ? 'Checked' : '') . "></input>";
        print "<td align=center><input name='hasPropal' type='checkbox' " . ($res->hasPropal == 1 ? 'Checked' : '') . "></input>";
        print "<td align=center><input name='hasProjet' type='checkbox' " . ($res->hasProjet == 1 ? 'Checked' : '') . "></input>";
        print "<td align=center><input name='hasRevision' type='checkbox' " . ($res->hasRevision == 1 ? 'Checked' : '') . "></input>";
        print "<td align=center><input name='hasDescription' type='checkbox' " . ($res->hasDescription == 1 ? 'Checked' : '') . "></input>";
        print "<td align=center><input name='hasStatut' type='checkbox' " . ($res->hasStatut == 1 ? 'Checked' : '') . "></input>";
        print "<td align=center><input name='hasSuivie' type='checkbox' " . ($res->hasSuivie == 1 ? 'Checked' : '') . "></input>";
        print "<td align=center><input style='text-align:center; width:90%;'  name='nomDescription' value='" . $res->nomDescription . "'></input>";
        print "<td align=center><input style='text-align:center; width:50%;'  name='typeDescription' value='" . $res->typeDescription . "'></input>";
        print "<td class=' " . $classArr[$bool] . "' align=center style='border-right: 1px Solid; border-bottom: 1px Solid;'><button onClick='location.href=\"synopsischrono.php?action=modify&id=" . $res->id . "\"' class='butAction'>Modifier</button><br/><button onClick='location.href=\"synopsischrono.php\"' class='butAction'>Annuler</button>";
        print "</table>";
        print "<table class=\"noborder\" width=\"100%\" cellpadding=5>";
    } else {
        require_once(DOL_DOCUMENT_ROOT . '/synopsischrono/modules_chrono.php');
        $ModeleNumRefChrono = new mod_chrono_serpentine($db);
        $exampleRef = $ModeleNumRefChrono->getExample($res->modele, $res->id);
        print "<tr><td width=\"20%\" class=' " . $classArr[$bool] . "' style='border-left: 1px solid;'>" . $res->titre . "</td>";
        print "<td class=' " . $classArr[$bool] . "'>" . $res->modele . "</td>";
        print "<td class=' " . $classArr[$bool] . "'>" . $exampleRef . "</td>";
        print "<td class=' " . $classArr[$bool] . "'>" . ($res->hasFile == 1 ? "Avec Fichier" : "Sans fichier") . "</td>";
        print "<td class=' " . $classArr[$bool] . "'>" . ($res->hasSociete == 1 ? "Oui" : "Non") . "</td>";
        print "<td class=' " . $classArr[$bool] . "'>" . ($res->hasContact == 1 ? "Oui" : "Non") . "</td>";
        print "<td class=' " . $classArr[$bool] . "'>" . ($res->hasPropal == 1 ? "Oui" : "Non") . "</td>";
        print "<td class=' " . $classArr[$bool] . "'>" . ($res->hasProjet == 1 ? "Oui" : "Non") . "</td>";
        print "<td class=' " . $classArr[$bool] . "'>" . ($res->hasRevision == 1 ? "Oui" : "Non") . "</td>";
        print "<td class=' " . $classArr[$bool] . "'>" . ($res->hasDescription == 1 ? "Oui" : "Non") . "</td>";
        print "<td class=' " . $classArr[$bool] . "'>" . ($res->hasStatut == 1 ? "Oui" : "Non") . "</td>";
        print "<td class=' " . $classArr[$bool] . "'>" . ($res->hasSuivie == 1 ? "Oui" : "Non") . "</td>";
        print "<td class=' " . $classArr[$bool] . "' align=center rowspan=2 style='border-right: 1px Solid; border-bottom: 1px Solid;'>";
        print "<button onClick='location.href=\"synopsischrono.php?action=modify&id=" . $res->id . "\"' class='butAction'>" . img_edit("") . "  Modifier</button>";
        print "<button onClick='location.href=\"synopsischrono_advMode.php?id=" . $res->id . "\"' class='butAction'>" . img_edit("") . "  Config. avanc&eacute;e</button>";
        print "<tr><td class=' " . $classArr[$bool] . "' style='border-left: 1px Solid; border-bottom: 1px Solid;' colspan=12>" . $res->description . "</td>";
    }
}
print "</TABLE>";

if ($_REQUEST['action'] != 'modify') {
    print "<br/>";
    print "<br/>";
    print "<br/>";

    print '<form name="ChronoConfig" action="synopsischrono.php" method="post">';
    print "<input type='hidden' name='action' value='add' />";
    print "<table class=\"noborder\" width=\"100%\" cellpadding=5>";
    print "<tr class=\"liste_titre\">";
    print "<td align=center width=\"20%\">" . $langs->trans("Nom du chrono") . "</td>";
    print "<td align=center>" . $langs->trans("Description") . "</td>";
    print "<td align=center>" . $langs->trans("Mod&egrave;le de ref.") . "</td>";
    print "<td align=center>" . $langs->trans("Attacher un fichier") . "</td>";
    print "<td align=center>" . $langs->trans("Lier &agrave; une soc.") . "</td>";
    print "<td align=center>" . $langs->trans("Lier &agrave; un contact") . "</td>";
    print "<td align=center>" . $langs->trans("Lier &agrave; une propal") . "</td>";
    print "<td align=center>" . $langs->trans("Lier &agrave; un projet") . "</td>";
    print "<td align=center>" . $langs->trans("Revision") . "</td>";
    print "<td align=center>" . $langs->trans("Description") . "</td>";
    print "<td align=center>" . $langs->trans("Statut") . "</td>";
    print "<td align=center>" . $langs->trans("Suivi") . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td class='ui-widget-content' align=center><input style='text-align:center; width:90%;' name='nom'></input>";
    print "<td class='ui-widget-content' align=center><textarea name='desc' style='width: 250px; height: 80px;'></textarea>";
    print "<td class='ui-widget-content' align=center><input style='text-align:center; width:90%;'  name='modeleRef'></input>";
    print "<td class='ui-widget-content' align=center><input name='attachFile' type='checkbox'></input>";
    print "<td class='ui-widget-content' align=center><input name='hasSoc' type='checkbox'></input>";
    print "<td class='ui-widget-content' align=center><input name='hasContact' type='checkbox'></input>";
    print "<td class='ui-widget-content' align=center><input name='hasPropal' type='checkbox'></input>";
    print "<td class='ui-widget-content' align=center><input name='hasProjet' type='checkbox'></input>";
    print "<td class='ui-widget-content' align=center><input name='hasRevision' type='checkbox'></input>";
    print "<td class='ui-widget-content' align=center><input name='hasDescription' type='checkbox'></input>";
    print "<td class='ui-widget-content' align=center><input name='hasStatut' type='checkbox'></input>";
    print "<td class='ui-widget-content' align=center><input name='hasSuivie' type='checkbox'></input>";
    print "</TABLE>";

    print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"" . $langs->trans("Ajouter") . "\">";
}
print "</center><br/><br/><br/>";



print "<div class='ui-state-highlight' style='padding:5px'><b><span class='ui-icon ui-icon-info' style='float:left; margin-top: -1px;'></span>&nbsp;&nbsp;Note sur les mod&egrave;les de num&eacute;rotation:</b><br/><br/>" . $langs->trans("GenericMaskCodes", $langs->transnoentities("Chrono"), $langs->transnoentities("Chrono"), $langs->transnoentities("Chrono")) . "</div>";


print "</form>\n";

llxFooter('$Date: 2005/10/03 01:36:21 $ - $Revision: 1.23 $');

function iniTabChronoList() {
    global $db;
    $requete = "DELETE FROM " . MAIN_DB_PREFIX . "const WHERE `name` LIKE  '%MAIN_MODULE_SYNOPSISCHRONO_TABS%'";
    $db->query($requete);
    $requete = "SELECT * FROM `" . MAIN_DB_PREFIX . "synopsischrono_conf` WHERE active = 1";
    $sql = $db->query($requete);
    $i = -1;
    $hasPropal = $hasProjet = $hasSoc = 0;
    while ($res = $db->fetch_object($sql)) {
        $i++;
        if ($res->hasPropal == "1")
            $hasPropal = 1;
        if ($res->hasProjet == "1")
            $hasProjet = 1;
        if ($res->hasSociete == "1")
            $hasSoc = 1;
    }

    $sql = $db->query("SELECT c.* FROM `" . MAIN_DB_PREFIX . "synopsischrono_key`, `" . MAIN_DB_PREFIX . "synopsischrono_conf` c WHERE `type_valeur` = 6 AND `type_subvaleur` IN(1000, 1007) AND model_refid = c.id GROUP by c.id");
    while ($result = $db->fetch_object($sql))
        $hasContrat = true;

    $i = 0;
    if ($hasPropal) {
        $i++;
        $type = "propal";
        $requete3 = "INSERT INTO `" . MAIN_DB_PREFIX . "const`(`name`, `entity`, `value`, `type`, `visible`) VALUES ('MAIN_MODULE_SYNOPSISCHRONO_TABS_" . $i . "',1,'" . $type . ":+chrono:Chrono:chrono@synopsischrono:/synopsischrono/listByObjet.php?obj=" . $type . "&"/* . $res->idT . */ . "id=__ID__','chaine',0)";
//            die($requete3);
        $sql2 = $db->query($requete3);
    }
    if ($hasProjet) {
        $i++;
        $type = "project";
        $requete3 = "INSERT INTO `" . MAIN_DB_PREFIX . "const`(`name`, `entity`, `value`, `type`, `visible`) VALUES ('MAIN_MODULE_SYNOPSISCHRONO_TABS_" . $i . "',1,'" . $type . ":+chrono:Chrono:chrono@synopsischrono:/synopsischrono/listByObjet.php?obj=" . $type . "&"/* . $res->idT . */ . "id=__ID__','chaine',0)";
//            die($requete3);
        $sql2 = $db->query($requete3);
    }
    if ($hasSoc) {
        $type = "thirdparty";
        $type2 = "soc";
        $i++;
        $requete3 = "INSERT INTO `" . MAIN_DB_PREFIX . "const`(`name`, `entity`, `value`, `type`, `visible`) VALUES ('MAIN_MODULE_SYNOPSISCHRONO_TABS_" . $i . "',1,'" . $type . ":+chrono:Chrono:chrono@synopsischrono:/synopsischrono/listByObjet.php?obj=" . $type2 . "&"/* . $res->idT . */ . "id=__ID__','chaine',0)";
//            die($requete3);
        $sql2 = $db->query($requete3);
    }
    if ($hasContrat) {
        $type = "contract";
        $type2 = "ctr";
        $i++;
        $requete3 = "INSERT INTO `" . MAIN_DB_PREFIX . "const`(`name`, `entity`, `value`, `type`, `visible`) VALUES ('MAIN_MODULE_SYNOPSISCHRONO_TABS_" . $i . "',1,'" . $type . ":+chrono:Chrono:chrono@synopsischrono:/synopsischrono/listByObjet.php?obj=" . $type2 . "&"/* . $res->idT . */ . "id=__ID__','chaine',0)";
//            die($requete3);
        $sql2 = $db->query($requete3);
    }
}

?>
