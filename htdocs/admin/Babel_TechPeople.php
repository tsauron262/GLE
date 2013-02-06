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
  * Name : Babel_TechPeople.php
  * dolibarr-24dev
  */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_TechPeople/deplacements/deplacement.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");

$langs->load("admin");
$langs->load("bills");
$langs->load("deplacement");
$langs->load("other");
$langs->load("synopsisGene@Synopsis_Tools");

if (!$user->admin)
  accessforbidden();



if ($_GET["action"] == 'set')
{
    $type='deplacement';
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type) VALUES ('".$_GET["value"]."','".$type."')";
    if ($db->query($sql))
    {

    }
}
if ($_GET["action"] == 'del')
{
    $type='deplacement';
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

    dolibarr_set_const($db, "DEPLACEMENT_ADDON",$_GET["value"]);
}


  if ($_GET["action"] == 'setdoc')
{
    $db->begin();

    if (dolibarr_set_const($db, "DEPLACEMENT_ADDON_PDF",$_GET["value"]))
    {
        $conf->global->DEPLACEMENT_ADDON_PDF = $_GET["value"];
    }

    // On active le modele
    $type='deplacement';
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


llxHeader('',$langs->trans("Sous-module déplacement"));

$dir = DOL_DOCUMENT_ROOT."/includes/modules/deplacement/";
$html=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("Configuration du module TechPeople"),$linkback,'setup');



/*
 * Modeles de documents
 */

print_titre($langs->trans("Mod&egrave;le de note de frais"));

// Defini tableau def de modele deplacement
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'deplacement'";
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
    if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,16) == 'pdf_deplacement_')
    {
        $name = substr($file, 16, strlen($file) - 28);//babel
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
            if ($conf->global->DEPLACEMENT_ADDON_PDF != "$name")
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
        if ($conf->global->DEPLACEMENT_ADDON_PDF == "$name")
        {
            print img_tick($langs->trans("Default"));
        }
        else
        {
            print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
        }
        print '</td>';

        // Info
        $htmltooltip ='<b align="center">Affichage standard PDF + XLS</b> ';



        print '<td align="center">';
        print $html->textwithtooltip('',$htmltooltip,1,0);
        print '</td>';

        print "</tr>\n";
    }
}
closedir($handle);

print '</table>';
print '<br>';

llxFooter('$Date: 2008/07/05 14:20:05 $ - $Revision: 1.71 $');

?>
