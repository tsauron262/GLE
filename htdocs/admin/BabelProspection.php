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
//require_once(DOL_DOCUMENT_ROOT.'/lib/webcal.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");


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

//    $i+=dolibarr_set_const($db,'VOIPAST_HOST',trim($_POST["voip_phone_host"]),'',0);
//    $i+=dolibarr_set_const($db,'VOIPAST_MANNAME',trim($_POST["voip_phone_manname"]),'',0);
//    $i+=dolibarr_set_const($db,'VOIPAST_MANPASS',trim($_POST["voip_phone_manpass"]),'',0);
//    $i+=dolibarr_set_const($db,'VOIPAST_CONTEXT',trim($_POST["voip_phone_context"]),'',0);
//    $i+=dolibarr_set_const($db,'VOIPAST_WAITTIME',trim($_POST["voip_phone_waittime"]),'',0);
//    $i+=dolibarr_set_const($db,'VOIPAST_PRIORITY',trim($_POST["voip_phone_priority"]),'',0);
//    $i+=dolibarr_set_const($db,'VOIPAST_RETRY',trim($_POST["voip_phone_retry"]),'',0);


    if ($i >= 7)
    {
        $db->commit();
        header("Location: BabelProspection.php");
        exit;
    }
    else
    {
        $db->rollback();
        $mesg = "<font class=\"ok\">".$langs->trans("BabelProspectionSaved")."</font>";
    }
}


/**
 * Affichage du formulaire de saisie
 */

llxHeader();
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("Prospection Babel"),$linkback,'setup');

//print_titre($langs->trans("Prospection Babel"));
print '<br>';


print '<form name="BabelProspectionconfig" action="BabelProspection.php" method="post">';
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td width=\"30%\">".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "<td>".$langs->trans("Examples")."</td>";
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
