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

/**
        \file       htdocs/admin/webcalendar.php
        \ingroup    webcalendar
        \brief      Page de configuration du module webcalendar
        \version    $Revision: 1.23 $
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
#require_once(DOL_DOCUMENT_ROOT.'/lib/webcal.class.php');


if (!$user->admin)
    accessforbidden();


$langs->load("admin");
$langs->load("other");

$def = array();
$actionsave=$_POST["save"];

// Positionne la variable pour le test d'affichage de l'icone
if ($actionsave)
{
    $i=0;

    $db->begin();

    $i+=dolibarr_set_const($db,'BABELIM_HOST',trim($_POST["BABELIM_HOST"]),'',0);
    $i+=dolibarr_set_const($db,'BABELIM_PORT',trim($_POST["BABELIM_PORT"]),'',0);
    $i+=dolibarr_set_const($db,'BABELIM_PROTO',trim($_POST["BABELIM_PROTO"]),'',0);
    $i+=dolibarr_set_const($db,'BABELIM_DOMAIN',trim($_POST["BABELIM_DOMAIN"]),'',0);
    $i+=dolibarr_set_const($db,'BABELIM_IM_USE_LDAP',($_POST["BABELIM_IM_USE_LDAP"]?"true":"false"),'',0);
    $i+=dolibarr_set_const($db,'BABELIM_XMLSOCKETPORT',trim($_POST["BABELIM_XMLSOCKETPORT"]),'',0);
/*

 */

    if ($i >= 6)
    {
        $db->commit();
        header("Location: BabelIM.php");
        exit;
    }
    else
    {
        $db->rollback();
        $mesg = "<font class=\"ok\">".$langs->trans("BABELIMSetupSaved")."</font>";
    }
}


/**
 * Affichage du formulaire de saisie
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("BABELIM"),$linkback,'setup');
print '<br>';


print '<form name="BABELIMconfig" action="BabelIM.php" method="POST">';
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td width=\"30%\">".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "<td>".$langs->trans("Examples")."</td>";
print "</tr>";
print "<tr class=\"impair\">";
print "<td>".$langs->trans("BABELIM_HOST")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"BABELIM_HOST\" value=\"". ($_POST["BABELIM_HOST"]?$_POST["BABELIM_HOST"]:$conf->global->BABELIM_HOST) . "\" size=\"40\"></td>";
print "<td>openfire.synopsis-erp.com";
print "</td>";
print "</tr>";


print "<tr class=\"pair\">";
print "<td>".$langs->trans("BABELIM_PORT")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"BABELIM_PORT\" value=\"". ($_POST["BABELIM_PORT"]?$_POST["BABELIM_PORT"]:$conf->global->BABELIM_PORT) . "\" size=\"40\"></td>";
print "<td>5333";
print "</td>";
print "</tr>";

print "<tr class=\"impair\">";
print "<td>".$langs->trans("BABELIM_PROTO")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"BABELIM_PROTO\" value=\"". ($_POST["BABELIM_PROTO"]?$_POST["BABELIM_PROTO"]:$conf->global->BABELIM_PROTO) . "\" size=\"40\"></td>";
print "<td>http / https / socket";
print "</td>";
print "</tr>";

print "<tr class=\"pair\">";
print "<td>".$langs->trans("BABELIM_DOMAIN")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"BABELIM_DOMAIN\" value=\"". ($_POST["BABELIM_DOMAIN"]?$_POST["BABELIM_DOMAIN"]:$conf->global->BABELIM_DOMAIN) . "\" size=\"40\"></td>";
print "<td>babelmrs.synopsis-erp.com";
print "</td>";
print "</tr>";


print "<tr class=\"impair\">";
print "<td>".$langs->trans("BABELIM_IM_USE_LDAP")."</td>";
print "<td><input type=\"checkbox\"  class=\"flat\" name=\"BABELIM_IM_USE_LDAP\" ". ($_POST["BABELIM_IM_USE_LDAP"]?"checked":($conf->global->BABELIM_IM_USE_LDAP == "true")?"checked":"")  . " size=\"40\"></td>";
print "<td>&nbsp;";
print "</td>";
print "</tr>";

print "<tr class=\"pair\">";
print "<td>".$langs->trans("BABELIM_XMLSOCKETPORT")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"BABELIM_XMLSOCKETPORT\" value=\"". ($_POST["BABELIM_XMLSOCKETPORT"]?$_POST["BABELIM_XMLSOCKETPORT"]:$conf->global->BABELIM_XMLSOCKETPORT) . "\" size=\"40\"></td>";
print "<td>5229";
print "</td>";
print "</tr>";


print "</TABLE>";

print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</center>";

print "</form>\n";


clearstatcache();

if ($mesg) print "<br>$mesg<br>";
print "<br>";

$db->close();

llxFooter('$Date: 2005/10/03 01:36:21 $ - $Revision: 1.23 $');
?>
