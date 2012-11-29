<?php

/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Created on : 24 aout 2009
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : BabelProject.php
 * GLE-1.1
 */
//TODO cout d'une resource par default



require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT . '/projet/class/project.class.php');

$langs->load("admin");
$langs->load("bills");
$langs->load("propal");
$langs->load("other");

if (!$user->admin)
  accessforbidden();


/*
 * Actions
 */
if ($_REQUEST['action']== 'set_PROPAL_REVISION_MODEL')
{
    dolibarr_set_const($db, "PROPAL_REVISION_MODEL",$_REQUEST["PROPAL_REVISION_MODEL"]);
}


/*
 * Affiche page
 */

llxHeader('',$langs->trans("PropalSetup"));

$dir = "../includes/modules/propale/";
$html=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("PropalSetup"),$linkback,'setup');

/*
 * Modeles de revision
 */

print_titre($langs->trans("Mod&egrave;le de r&eacute;vision"));
$dir = DOL_DOCUMENT_ROOT."/Synopsis_Revision/modele/";

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td width=\"140\">".$langs->trans("Name")."</td>\n";
print "  <td width=\"340\">".$langs->trans("Description")."</td>\n";
print "  <td width=\"140\">".$langs->trans("Activ&eacute;")."</td>\n";
$handle=opendir($dir);

$var=true;
while (($file = readdir($handle))!==false)
{
    if (preg_match('/^revision_([\w\W]*).class.php/',$file,$arrTmp))
    {
        $classname = "revision_".$arrTmp[1];
        require_once($dir.$file);
        $module = new $classname($db);

        $var=!$var;
        print "<tr ".$bc[$var]."><td>";
        print $module->nom;
        print "</td><td>";
        print $module->description;
        print '</td><td>';
        if ($conf->global->PROPAL_REVISION_MODEL == $classname)
        {
            print img_picto($langs->trans("Activated"),'switch_on');
        } else {
            print "<a href='?action=set_PROPAL_REVISION_MODEL&PROPAL_REVISION_MODEL=".$classname."'>Activer</a>";
        }
        print '</tr>';
    }
}
print "</table>";

die;

 //PROPAL_REVISION_MODEL

/*
 * Modeles de documents
 */

print_titre($langs->trans("ProposalsPDFModules"));

// Defini tableau def de modele propal
$def = array();
$sql = "SELECT nom";
$sql.= " FROM llx_document_model";
$sql.= " WHERE type = 'propal'";
$resql=$db->query($sql);
if ($resql)
{
    $i = 0;
    $num_rows=$db->num_rows($resql);
    while ($res=$db->fetch_object($resql))
    {
        array_push($def, $res->nom);
        $i++;
    }
} else {
    dolibarr_print_error($db);
}

$dir = "../includes/modules/propale/";

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td width=\"140\">".$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Description")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Activated")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$handle=opendir($dir);

$var=true;
while (($file = readdir($handle))!==false)
{
    if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,12) == 'pdf_propale_')
    {
        $name = substr($file, 12, strlen($file) - 24);
        $classname = substr($file, 0, strlen($file) -12);

        $var=!$var;
        print "<tr ".$bc[$var].">\n  <td>";
        print "$name";
        print "</td>\n  <td>\n";
        require_once($dir.$file);
        $module = new $classname($db);
        print $module->description;
        print '</td>';

        // Active
        if (in_array($name, $def))
        {
            print "<td align=\"center\">\n";
            if ($conf->global->PROPALE_ADDON_PDF != "$name")
            {
                print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'">';
                print img_tick($langs->trans("Disable"));
                print '</a>';
            }
            else
            {
                print img_tick($langs->trans("Enabled"));
            }
            print "</td>";
        } else {
            print "<td align=\"center\">\n";
            print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;libelle='.$module->name.'&amp;value='.$name.'">'.$langs->trans("Activate").'</a>';
            print "</td>";
        }

        // Defaut
        print "<td align=\"center\">";
        if ($conf->global->PROPALE_ADDON_PDF == "$name")
        {
            print img_tick($langs->trans("Default"));
        } else {
            print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;libelle='.$module->name.'&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
        }
        print '</td>';

        // Info
        $htmltooltip =    '<b>'.$langs->trans("Name").'</b>: '.$module->name;
        $htmltooltip.='<br><b>'.$langs->trans("Type").'</b>: '.($module->type?$module->type:$langs->trans("Unknown"));
        $htmltooltip.='<br><b>'.$langs->trans("Height").'/'.$langs->trans("Width").'</b>: '.$module->page_hauteur.'/'.$module->page_largeur;
        $htmltooltip.='<br><br>'.$langs->trans("FeaturesSupported").':';
        $htmltooltip.='<br><b>'.$langs->trans("Logo").'</b>: '.yn($module->option_logo);
        $htmltooltip.='<br><b>'.$langs->trans("PaymentMode").'</b>: '.yn($module->option_modereg);
        $htmltooltip.='<br><b>'.$langs->trans("PaymentConditions").'</b>: '.yn($module->option_condreg);
        $htmltooltip.='<br><b>'.$langs->trans("MultiLanguage").'</b>: '.yn($module->option_multilang);
        //TODO : $htmltooltip.='<br><b>'.$langs->trans("Escompte").'</b>: '.yn($module->option_escompte);
        $htmltooltip.='<br><b>'.$langs->trans("CreditNote").'</b>: '.yn($module->option_credit_note);
        $htmltooltip.='<br><b>'.$langs->trans("FreeLegalTextOnProposal").'</b>: '.yn($module->option_freetext);
        $htmltooltip.='<br><b>'.$langs->trans("WatermarkOnDraftProposal").'</b>: '.yn($module->option_draft_watermark);



        print '<td align="center">';
        print $html->textwithtooltip('',$htmltooltip,1,0);
        print '</td>';
        print '<td align="center">';
        print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'propal').'</a>';
        print '</td>';

        print "</tr>\n";
    }
}
closedir($handle);

print '</table>';
print '<br>';


/*
 * Autres options
 *
 */
print_titre($langs->trans("OtherOptions"));

$var=true;
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td>".$langs->trans("Parameter")."</td>\n";
print '<td width="60" align="center">'.$langs->trans("Value")."</td>\n";
print "<td>&nbsp;</td>\n";
print "</tr>";

$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print "<input type=\"hidden\" name=\"action\" value=\"setdefaultduration\">";
print "<tr ".$bc[$var].">";
print '<td>'.$langs->trans("DefaultProposalDurationValidity").'</td>';
print '<td width="60" align="center">'."<input size=\"3\" class=\"flat\" type=\"text\" name=\"value\" value=\"".$conf->global->PROPALE_VALIDITY_DURATION."\"></td>";
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print '</form>';


$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print "<input type=\"hidden\" name=\"action\" value=\"set_PROPALE_WARN_VALIDATION\">";
print '<tr '.$bc[$var].'><td colspan="1">';
print $langs->trans("Montant de proposition g&eacute;n&eacute;rant un warning").'</td><td width="60" align="center">';
print '<input size="5" class="flat" type="text" name="PROPALE_WARN_VALIDATION" value="'.$conf->global->PROPALE_WARN_VALIDATION.'">';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';


$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print "<input type=\"hidden\" name=\"action\" value=\"set_PROPALE_NOMORE_VALIDATION\">";
print '<tr '.$bc[$var].'><td colspan="1">';
print $langs->trans("Montant de proposition demandant une validation par un manager").'</td><td width="60" align="center">';
print '<input size="5" class="flat" type="text" name="PROPALE_NOMORE_VALIDATION" value="'.$conf->global->PROPALE_NOMORE_VALIDATION.'">';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print "<input type=\"hidden\" name=\"action\" value=\"setaddshippingdate\">";
print "<tr ".$bc[$var].">";
print '<td>'.$langs->trans("AddShippingDateAbility").'</td>';
print '<td width="60" align="center">'.$html->selectyesno('value',$conf->global->PROPALE_ADD_SHIPPING_DATE,1).'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print '</form>';

$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print "<input type=\"hidden\" name=\"action\" value=\"setadddeliveryaddress\">";
print "<tr ".$bc[$var].">";
print '<td>'.$langs->trans("AddDeliveryAddressAbility").'</td>';
print '<td width="60" align="center">'.$html->selectyesno('value',$conf->global->PROPALE_ADD_DELIVERY_ADDRESS,1).'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print '</form>';

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="setusecustomercontactasrecipient">';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("UseCustomerContactAsPropalRecipientIfExist");
print '</td><td width="60" align="center">';
print $html->selectyesno("value",$conf->global->PROPALE_USE_CUSTOMER_CONTACT_AS_RECIPIENT,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="setuseoptionline">';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("UseOptionLineIfNoQuantity");
print '</td><td width="60" align="center">';
print $html->selectyesno("value",$conf->global->PROPALE_USE_OPTION_LINE,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="setuseoptionlinetotal">';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("Consid&eacute;rer les options dans le total_ht");
print '</td><td width="60" align="center">';
print $html->selectyesno("value",$conf->global->PROPALE_COUNT_OPTION_LINE_IN_TOTAL,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';


if ($conf->commande->enabled)
{
    $var=!$var;
    print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
    print "<input type=\"hidden\" name=\"action\" value=\"setclassifiedinvoiced\">";
    print "<tr ".$bc[$var].">";
    print '<td>'.$langs->trans("ClassifiedInvoicedWithOrder").'</td>';
    print '<td width="60" align="center">';
    print $html->selectyesno('value',$conf->global->PROPALE_CLASSIFIED_INVOICED_WITH_ORDER,1);
    print "</td>";
    print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
    print '</tr>';
    print '</form>';
}

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="set_PROPALE_FREE_TEXT">';
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("FreeLegalTextOnProposal").'<br>';
print '<textarea name="PROPALE_FREE_TEXT" class="flat" cols="100">'.$conf->global->PROPALE_FREE_TEXT.'</textarea>';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print "<input type=\"hidden\" name=\"action\" value=\"set_PROPALE_DRAFT_WATERMARK\">";
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("WatermarkOnDraftProposal").'<br>';
print '<input size="50" class="flat" type="text" name="PROPALE_DRAFT_WATERMARK" value="'.$conf->global->PROPALE_DRAFT_WATERMARK.'">';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print "<input type=\"hidden\" name=\"action\" value=\"set_PROPALE_USE_COEF\">";
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PROPALE_USE_COEF").'<td align=center>';
print '<input class="flat" type="checkbox" name="PROPALE_USE_COEF" '.($conf->global->PROPALE_USE_COEF==1?'checked':'').'>';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="PROPALE_MODIF_AFTER_VALID_IS_REVISION">';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("Une modification sur une propale valid&eacute;e est une revision");
print '</td><td width="60" align="center">';
print $html->selectyesno("value",$conf->global->PROPALE_MODIF_AFTER_VALID_IS_REVISION,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';



print '</table>';



/*
 *  Repertoire
 */
print '<br>';
print_titre($langs->trans("PathToDocuments"));

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td>".$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Value")."</td>\n";
print "</tr>\n";
print "<tr ".$bc[false].">\n  <td width=\"140\">".$langs->trans("PathDirectory")."</td>\n  <td>".$conf->propal->dir_output."</td>\n</tr>\n";
print "</table>\n<br>";



$db->close();

llxFooter('$Date: 2008/07/05 14:20:05 $ - $Revision: 1.71 $');
?>