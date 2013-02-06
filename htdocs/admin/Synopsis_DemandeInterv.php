<?php
/*
  * GLE by Babel-Services
  *
  * Author: Jean-Marc LE FEVRE <jm.lefevre@babel-services.com>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.babel-services.com
  *
  */
/*
 */

/**
        \file       htdocs/admin/Synopsis_DemandeInterv.php
        \ingroup    demandeInterv
        \brief      Page d'administration/configuration du module demandeInterv
        \version    $Id: demandeInterv.php,v 1.38 2008/07/05 15:31:30 eldy Exp $
*/

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/Synopsis_DemandeInterv/demandeInterv.class.php');

$langs->load("admin");
$langs->load("bills");
$langs->load("other");
$langs->load("interventions");


    $dir = DOL_DOCUMENT_ROOT . "/core/modules/synopsisdemandeinterv/";

if (!$user->admin)
  accessforbidden();


/*
 * Actions
 */
if ($_POST["action"] == 'updateMask')
{
    $maskconst=$_POST['maskconst'];
    $maskvalue=$_POST['maskvalue'];
    if ($maskconst) dolibarr_set_const($db,$maskconst,$maskvalue);
}

if ($_POST["action"] == 'set_DEMANDEINTERV_DRAFT_WATERMARK')
{
    dolibarr_set_const($db, "DEMANDEINTERV_DRAFT_WATERMARK",trim($_POST["DEMANDEINTERV_DRAFT_WATERMARK"]));
}

if ($_GET["action"] == 'specimen')
{
    $modele=$_GET["module"];

    $inter = new demandeInterv($db);
    $inter->initAsSpecimen();

    // Charge le modele
    $file = "pdf_".$modele.".modules.php";
    if (file_exists($dir.$file))
    {
        $classname = "pdf_".$modele;
        require_once($dir.$file);

        $obj = new $classname($db);

        if ($obj->write_file($inter,$langs) > 0)
        {
            header("Location: ".DOL_URL_ROOT."/document.php?modulepart=demandeInterv&file=SPECIMEN.pdf");
            return;
        }
    }
    else
    {
        $mesg='<div class="error ui-state-error">'.$langs->trans("ErrorModuleNotFound").'</div>';
    }
}

if ($_GET["action"] == 'set')
{
    $type='demandeInterv';
    $sql = "INSERT INTO llx_document_model (nom, type) VALUES ('".$_GET["value"]."','".$type."')";
    if ($db->query($sql))
    {

    }
}

if ($_GET["action"] == 'del')
{
    $type='demandeInterv';
    $sql = "DELETE FROM llx_document_model";
    $sql .= "  WHERE nom = '".$_GET["value"]."' AND type = '".$type."'";
    if ($db->query($sql))
    {

    }
}

if ($_GET["action"] == 'setdoc')
{
    $db->begin();

    if (dolibarr_set_const($db, "DEMANDEINTERV_ADDON_PDF",$_GET["value"]))
    {
        // La constante qui a ete lue en avant du nouveau set
        // on passe donc par une variable pour avoir un affichage coherent
        $conf->global->DEMANDEINTERV_ADDON_PDF = $_GET["value"];
    }

    // On active le modele
    $type='demandeInterv';
    $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql_del .= "  WHERE nom = '".$_GET["value"]."' AND type = '".$type."'";
    $result1=$db->query($sql_del);
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom,type) VALUES ('".$_GET["value"]."','".$type."')";
    $result2=$db->query($sql);
    if ($result1 && $result2)
    {
        $db->commit();
    }
    else
    {
        $db->rollback();
    }
}

if ($_GET["action"] == 'setmod')
{
    // \todo Verifier si module numerotation choisi peut etre active
    // par appel methode canBeActivated

    dolibarr_set_const($db, "DEMANDEINTERV_ADDON",$_GET["value"]);
}

// defini les constantes du modele arctic
if ($_POST["action"] == 'updateMatrice') dolibarr_set_const($db, "DEMANDEINTERV_NUM_MATRICE",$_POST["matrice"]);
if ($_POST["action"] == 'updatePrefix') dolibarr_set_const($db, "DEMANDEINTERV_NUM_PREFIX",$_POST["prefix"]);
if ($_POST["action"] == 'setOffset') dolibarr_set_const($db, "DEMANDEINTERV_NUM_DELTA",$_POST["offset"]);
if ($_POST["action"] == 'setNumRestart') dolibarr_set_const($db, "DEMANDEINTERV_NUM_RESTART_BEGIN_YEAR",$_POST["numrestart"]);


/*
 * Affichage page
 */

llxHeader();

$html=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("InterventionsSetup"),$linkback,'setup');

print "<br>";


print_titre($langs->trans("demandeIntervNumberingModules"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$handle = opendir($dir);
if ($handle)
{
    $var=true;

    while (($file = readdir($handle))!==false)
    {
        if (preg_match('/^(mod_.*)\.php$/i',$file,$reg))
        {
            $file = $reg[1];
            $className = substr($file,4);

            require_once($dir.$file.".php");

            $module = new $file;

            $var=!$var;
            print '<tr '.$bc[$var].'><td>'.$module->nom."</td><td>\n";
            print $module->info();
            print '</td>';

            // Examples
            print '<td nowrap="nowrap">'.$module->getExample()."</td>\n";

            print '<td align="center">';
//            print $className;
            if ($conf->global->DEMANDEINTERV_ADDON == $className)
            {
                print img_picto($langs->trans("Activated"), 'switch_on');
            }
            else
            {
                print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$className.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
            }
            print '</td>';

                $demandeInterv=new demandeInterv($db);
                $demandeInterv->initAsSpecimen();

                // Info
                $htmltooltip='';
            $nextval=$module->getNextValue($mysoc,$demandeInterv);
            if ($nextval != $langs->trans("NotAvailable"))
            {
                $htmltooltip='<b>'.$langs->trans("NextValue").'</b>: '.$nextval;
            }
            print '<td align="center">';
//            print $html->textwithhelp('',$htmltooltip,1,0);
	    print $html->textwithpicto('',$htmltooltip,1,0);
            print '</td>';

            print '</tr>';
        }
    }
    closedir($handle);
}

print '</table><br>';



print_titre($langs->trans("TemplatePDFInterventions"));

// Defini tableau def des modeles
$type='demandeInterv';
$def = array();
$sql = "SELECT nom";
$sql.= " FROM llx_document_model";
$sql.= " WHERE type = '".$type."'";
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
}
else
{
    dol_print_error($db);
}


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$var=true;

$handle=opendir($dir);
while (($file = readdir($handle))!==false)
{
  if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,4) == 'pdf_')
    {
      $name = substr($file, 4, strlen($file) -16);
      $classname = substr($file, 0, strlen($file) -12);

      $var=!$var;

      print '<tr '.$bc[$var].'><td>';
      echo "$name";
      print "</td><td>\n";
      require_once($dir.$file);
      $module = new $classname();
      print $module->description;
      print '</td>';

        // Active
        if (in_array($name, $def))
        {
            print "<td align=\"center\">\n";
            if ($conf->global->DEMANDEINTERV_ADDON_PDF != "$name")
            {
                print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'">';
                print img_picto($langs->trans("Disable"), 'switch_off');
                print '</a>';
            }
            else
            {
                print img_picto($langs->trans("Enabled"), 'switch_on');
            }
            print "</td>";
        }
        else
        {
            print "<td align=\"center\">\n";
            print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'">'.$langs->trans("Activate").'</a>';
            print "</td>";
        }

        // Defaut
        print "<td align=\"center\">";
        if ($conf->global->DEMANDEINTERV_ADDON_PDF == "$name")
        {
            print img_picto($langs->trans("Default"), 'switch_on');
        }
        else
        {
            print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
        }
        print '</td>';

        // Info
        $htmltooltip =    '<b>'.$langs->trans("Type").'</b>: '.($module->type?$module->type:$langs->trans("Unknown"));
        $htmltooltip.='<br><b>'.$langs->trans("Width").'</b>: '.$module->page_largeur;
        $htmltooltip.='<br><b>'.$langs->trans("Height").'</b>: '.$module->page_hauteur;
        $htmltooltip.='<br><br>'.$langs->trans("FeaturesSupported").':';
        $htmltooltip.='<br><b>'.$langs->trans("Logo").'</b>: '.yn($module->option_logo);
        $htmltooltip.='<br><b>'.$langs->trans("PaymentMode").'</b>: '.yn($module->option_modereg);
        $htmltooltip.='<br><b>'.$langs->trans("PaymentConditions").'</b>: '.yn($module->option_condreg);
        $htmltooltip.='<br><b>'.$langs->trans("MultiLanguage").'</b>: '.yn($module->option_multilang);
        $htmltooltip.='<br><b>'.$langs->trans("WatermarkOnDraftOrders").'</b>: '.yn($module->option_draft_watermark);
        print '<td align="center">';
//        print $html->textwithhelp('',$htmltooltip,1,0);
	    print $html->textwithpicto('',$htmltooltip,1,0);
        print '</td>';
        print '<td align="center">';
        print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'intervention').'</a>';
        print '</td>';

        print '</tr>';
    }
}
closedir($handle);

print '</table>';

//Autres Options
print "<br>";
print_titre($langs->trans("OtherOptions"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print "<td>&nbsp;</td>\n";
print "</tr>\n";
$var=true;

//Use draft Watermark
$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print "<input type=\"hidden\" name=\"action\" value=\"set_DEMANDEINTERV_DRAFT_WATERMARK\">";
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("WatermarkOnDraftInterventionCards").'<br>';
print '<input size="50" class="flat" type="text" name="DEMANDEINTERV_DRAFT_WATERMARK" value="'.$conf->global->DEMANDEINTERV_DRAFT_WATERMARK.'">';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';


print '</table>';

print '<br>';

$db->close();

llxFooter('$Date: 2008/07/05 15:31:30 $ - $Revision: 1.38 $');
?>