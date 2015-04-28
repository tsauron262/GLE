<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 5 avr. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : Babel_contrat.php
  * dolibarr-24dev
  */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");

$langs->load("admin");
$langs->load("bills");
$langs->load("contrat");
$langs->load("other");
$langs->load("synopsisGene@Synopsis_Tools");

if (!$user->admin)
  accessforbidden();

if ($_POST["action"] == 'EDITWHENVALIDATED') {
  dolibarr_set_const($db, "CONTRAT_EDITWHENVALIDATED", $_POST["CONTRAT_EDITWHENVALIDATED"]);
}

if ($_POST["action"] == 'form_CONTRAT_DEFAULT_COMMERCIAL_SIGNATAIRE') {
  dolibarr_set_const($db, "CONTRAT_DEFAULT_COMMERCIAL_SIGNATAIRE", $_POST["CONTRAT_DEFAULT_COMMERCIAL_SIGNATAIRE"]);
}

if ($_POST["action"] == 'updateMask')
{
    $mask=$_REQUEST['maskcontrat'];
    $maskConst = $_REQUEST['maskconst'];
    if ("x".$maskConst != "x" &&$mask."x" != "x") dolibarr_set_const($db,$maskConst,$mask,'',0);
}

if ($_GET["action"] == 'set')
{
    $type='contrat';
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type) VALUES ('".$_GET["value"]."','".$type."')";
    if ($db->query($sql))
    {

    }
}
if ($_GET["action"] == 'del')
{
    $type='contrat';
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql .= "  WHERE nom = '".$_GET["value"]."' AND type = '".$type."'";
    if ($db->query($sql))
    {

    }
}


if ($_GET["action"] == 'setmod')
{
    // \todo Verifier si module numerotation choisi peut etre active
    // par appel methode canBeActivated

    dolibarr_set_const($db, "CONTRAT_ADDON",$_GET["value"]);
}


  if ($_GET["action"] == 'setdoc')
{
    $db->begin();

    if (dolibarr_set_const($db, "CONTRAT_ADDON_PDF",$_GET["value"]))
    {
        $conf->global->CONTRAT_ADDON_PDF = $_GET["value"];
    }

    // On active le modele
    $type='contrat';
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


llxHeader('',$langs->trans("Contrat de services"));

$dir = DOL_DOCUMENT_ROOT."/core/modules/contrat/";
$html=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("Configuration du module de gestion des contrats"),$linkback,'setup');



/*
 * Modeles de documents
 */

print_titre($langs->trans("Mod&egrave;le de contrat"));

// Defini tableau def de modele deplacement
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'contrat'";

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
    dol_print_error($db);
}

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
    //preg_match('/^pdf_deplacement/',$file) && preg_match('/.modules.php$/',$file)
    //
    if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,12) == 'pdf_contrat_')
    {
        $name = substr($file, 12, strlen($file) - 24);//babel
        $classname = substr($file, 0, strlen($file) -12);//pdf_deplacement_babel

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
            if ($conf->global->CONTRAT_ADDON_PDF != "$name")
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
        }
        else
        {
            print "<td align=\"center\">\n";
            print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'">'.$langs->trans("Activate").'</a>';
            print "</td>";
        }

        // Defaut
        print "<td align=\"center\">";
        if ($conf->global->CONTRAT_ADDON_PDF == "$name")
        {
            print img_tick($langs->trans("Default"));
        }
        else
        {
            print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
        }
        print '</td>';

        // Info
        $htmltooltip ='<b align="center">Affichage standard PDF</b> ';



        print '<td align="center">';
        print $html->textwithtooltip('',$htmltooltip,1,0);
        print '</td>';

        print "</tr>\n";
    }
}
closedir($handle);

print '</table>';
print '<br>';


/*
 *  Module numerotation
 */
print "<br>";
print_titre($langs->trans("Modules de num&eacute;rotation des contrats"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td nowrap>'.$langs->trans("Example")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print '</tr>'."\n";

clearstatcache();

$handle = opendir($dir);
if ($handle)
{
    $var=true;
    while (($file = readdir($handle))!==false)
    {
        if (substr($file, 0, 12) == 'mod_contrat_' && substr($file, strlen($file)-3, 3) == 'php')
        {
            $file = substr($file, 0, strlen($file)-4);
            require_once(DOL_DOCUMENT_ROOT ."/core/modules/contrat/".$file.".php");

            $module = new $file;

            // Show modules according to features level
            if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
            if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

            $var=!$var;
            print '<tr '.$bc[$var].'><td>'.$module->nom."</td><td>\n";
            print $module->info();
            print '</td>';

            // Examples
            print '<td nowrap="nowrap">'.$module->getExample()."</td>\n";

            print '<td align="center">';
            if ($conf->global->CONTRAT_ADDON == "$file")
            {
                print img_tick($langs->trans("Activated"));
            } else {
                print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Activate").'</a>';
            }
            print '</td>';

            $ctr=new Contrat($db);
            //$ctr->initAsSpecimen();

            // Info
            $htmltooltip='';
            $htmltooltip.='<b>'.$langs->trans("Version").'</b>: '.$module->getVersion().'<br>';
            $facture->type=0;
            $nextval=$module->getNextValue($mysoc,$propale);
            if ("$nextval" != $langs->trans("NotAvailable"))    // Keep " on nextval
            {
                $htmltooltip.='<b>'.$langs->trans("NextValue").'</b>: ';
                if ($nextval)
                {
                    $htmltooltip.=$nextval.'<br>';
                } else {
                    $htmltooltip.=$langs->trans($module->error).'<br>';
                }
            }

            print '<td align="center">';
            print $html->textwithtooltip('',$htmltooltip,1,0);
            print '</td>';

            print "</tr>\n";
        }
    }
    closedir($handle);
}
print "</table><br>\n";


print "<br>";
print_titre($langs->trans("Options"));

$var=!$var;

print "<form method=\"post\" action=\"Babel_contrat.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"EDITWHENVALIDATED\">";
print "<table width=100%>";
print "<tr ".$bc[$var].">";
print '<td width=65%>'.$langs->trans("Edition des contrats apr&egrave;s validation (et avenants)").'</td>';
print '<td width="30%" align="right">';
print $html->selectyesno("CONTRAT_EDITWHENVALIDATED",$conf->global->CONTRAT_EDITWHENVALIDATED,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';
print "</table>";
print '</form>';

print "<form method=\"post\" action=\"Babel_contrat.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"form_CONTRAT_DEFAULT_COMMERCIAL_SIGNATAIRE\">";
print "<table width=100%>";
print "<tr ".$bc[$var].">";
print '<td width=65%>'.$langs->trans("Signataire des contrats par d&eacute;faut").'</td>';
print '<td width="30%" align="right">';
//($selected='',$htmlname='userid',$show_empty=0,$exclude='',$disabled=0,$display=true)
$html->select_users($conf->global->CONTRAT_DEFAULT_COMMERCIAL_SIGNATAIRE,"CONTRAT_DEFAULT_COMMERCIAL_SIGNATAIRE",1,'',0,true);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';
print "</table>";
print '</form>';

//print "<tr><td>".$langs->trans('Liste des p&eacute;riodes disponibles');
//print "    <td><table>";
//$requete = "SELECT * FROM ";
//
//
//print "</table></td>";
llxFooter('$Date: 2010/03/11 14:20:05 $ - $Revision: 1.71 $');

?>
