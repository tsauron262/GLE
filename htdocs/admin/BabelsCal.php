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
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
#require_once(DOL_DOCUMENT_ROOT.'/lib/webcal.class.php');


//TODO :
//     affiche les categories existantes
//     permet d'ajouter une formule dans une categorie
//     support multilangue
//

if (!$user->admin)
    accessforbidden();
if (!$user->rights->sCalBabel->sCalBabel->Modification)
    accessforbidden();

$langs->load("admin");
$langs->load("other");
$langs->load("synopsisGene@Synopsis_Tools");
$langs->load("BabelCalc");
$langs->load("other");

$def = array();
$actionsave=$_REQUEST["save"];
$action=$_REQUEST["action"];

//print $action;
if ($action == "ModifyCat" )
{
    if ($_REQUEST['catId'] > 0)
    {
        $requete = "UPDATE Babel_Scal_categorie
                       SET nom = '".$_REQUEST['catName']."'
                     WHERE id = ".$_REQUEST['catId'];
        $db->query($requete);
        header("Location: ".$_SERVER['PHP_SELF']);
    }
} else if ($action == "ModifyForm" || $action == "modForm" )
{
    if ($_REQUEST['fId'] > 0)
    {
        $requete = "UPDATE Babel_Scal_formula
                       SET Description = '".$_REQUEST['description']."',
                           Formula = '".$_REQUEST['formula']."',
                           Scal_categorie_refid = '".$_REQUEST['catId']."'
                     WHERE id = ".$_REQUEST['fId'];
        $db->query($requete);
        header("Location: ".$_SERVER['PHP_SELF']);
    }
} else if ($action == "addForm" && $_REQUEST['catId']>0)
{
    if ($_REQUEST['catId'] > 0)
    {
        $requete = "INSERT INTO Babel_Scal_formula
                                (Description, Formula, Scal_categorie_refid, DisplayOrder)
                         SELECT '".$_REQUEST['description']."', '".$_REQUEST['formula']."', '".$_REQUEST['catId']."', count(DisplayOrder) + 1 FROM  Babel_Scal_formula WHERE Scal_categorie_refid = ".$_REQUEST['catId'];
        $db->query($requete);
        header("Location: ".$_SERVER['PHP_SELF']);
    }
} else if ($action == "addCat" && strlen($_REQUEST['categorieStr'])>0)
{
        $requete = "INSERT INTO Babel_Scal_categorie
                                (nom, DisplayOrder)
                         SELECT  '".$_REQUEST['categorieStr']."', count(DisplayOrder) + 1 FROM  Babel_Scal_categorie";
        $db->query($requete);
        header("Location: ".$_SERVER['PHP_SELF']);
} else if ($action == "delCat" && $_REQUEST['catId'] > 0)
{
        $requete = "DELETE FROM Babel_Scal_categorie
                          WHERE id = " . $_REQUEST['catId'];
        $db->query($requete);
        header("Location: ".$_SERVER['PHP_SELF']);
}else if ($action == "delForm" && $_REQUEST['fId'] > 0)
{
        $requete = "DELETE FROM Babel_Scal_formula
                          WHERE id = " . $_REQUEST['fId'];
        $db->query($requete);
        header("Location: ".$_SERVER['PHP_SELF']);
} else if ($action == "moveUp" && $_REQUEST['catId'] > 0)
{
    if ($_REQUEST['currOrder'] > 1)
    {
        $newOrder = $_REQUEST['currOrder'] - 1;
        $requete = "SELECT *
                      FROM Babel_Scal_categorie
                     WHERE DisplayOrder = ".$newOrder;
        $resql = $db->query($requete);
        if ($resql)
        {
            $res = $db->fetch_object($resql);
            $idCatToChange = $res->id;
            $requete = "UPDATE Babel_Scal_categorie
                           SET DisplayOrder = '".$newOrder."'
                         WHERE id = ".$_REQUEST['catId'];
            $db->query($requete);
            $requete = "UPDATE Babel_Scal_categorie
                           SET DisplayOrder = '".$_REQUEST['currOrder']."'
                         WHERE id = ".$idCatToChange;
            $db->query($requete);
            header("Location: ".$_SERVER['PHP_SELF']."?catId=".$_REQUEST['catId']);
        }
    }
} else if ($action == "moveDown" && $_REQUEST['catId'] > 0)
{
    if ($_REQUEST['currOrder'] > 0)
    {
        $newOrder = $_REQUEST['currOrder'] + 1;
        $requete = "SELECT *
                      FROM Babel_Scal_categorie
                     WHERE DisplayOrder = ".$newOrder;
        $resql = $db->query($requete);
        if ($resql)
        {
            $res = $db->fetch_object($resql);
            $idCatToChange = $res->id;
            $requete = "UPDATE Babel_Scal_categorie
                           SET DisplayOrder = '".$newOrder."'
                         WHERE id = ".$_REQUEST['catId'];
            $db->query($requete);
            $requete = "UPDATE Babel_Scal_categorie
                           SET DisplayOrder = '".$_REQUEST['currOrder']."'
                         WHERE id = ".$idCatToChange;
            $db->query($requete);
            header("Location: ".$_SERVER['PHP_SELF']."?catId=".$_REQUEST['catId']);
        }
    }
} else if ($action == "moveDown" && $_REQUEST['fId'] > 0)
{
    if ($_REQUEST['currOrder'] > 0)
    {
        $newOrder = $_REQUEST['currOrder'] + 1;
        $requete = "SELECT id
                      FROM Babel_Scal_formula
                     WHERE DisplayOrder = ".$newOrder;
        $resql = $db->query($requete);
        if ($resql)
        {
            $res = $db->fetch_object($resql);
            $idCatToChange = $res->id;
            if ("x" . $res->id != "x")
            {
                $requete = "UPDATE Babel_Scal_formula
                               SET DisplayOrder = '".$newOrder."'
                             WHERE id = ".$_REQUEST['fId'];
                $db->query($requete);
                $requete = "UPDATE Babel_Scal_formula
                               SET DisplayOrder = '".$_REQUEST['currOrder']."'
                             WHERE id = ".$idCatToChange;
                $db->query($requete);
            }
            header("Location: ".$_SERVER['PHP_SELF']."?fId=".$_REQUEST['fId']);
        }
    }
}else if ($action == "moveUp" && $_REQUEST['fId'] > 0)
{
    if ($_REQUEST['currOrder'] > 1)
    {
        $newOrder = $_REQUEST['currOrder'] - 1;
        $requete = "SELECT id
                      FROM Babel_Scal_formula
                     WHERE DisplayOrder = ".$newOrder;
        $resql = $db->query($requete);
        if ($resql)
        {
            $res = $db->fetch_object($resql);
            $idCatToChange = $res->id;
            if ("x" . $res->id != "x")
            {
                $requete = "UPDATE Babel_Scal_formula
                               SET DisplayOrder = '".$newOrder."'
                             WHERE id = ".$_REQUEST['fId'];
                $db->query($requete);
                $requete = "UPDATE Babel_Scal_formula
                               SET DisplayOrder = '".$_REQUEST['currOrder']."'
                             WHERE id = ".$idCatToChange;
                $db->query($requete);
            }
            header("Location: ".$_SERVER['PHP_SELF']."?fId=".$_REQUEST['fId']);
        }
    }
}
// Positionne la variable pour le test d'affichage de l'icone
if ($actionsave)
{
    $i=0;

//    $db->begin();
//
//    $i+=dolibarr_set_const($db,'JASPER_HOST',trim($_POST["JASPER_HOST"]),'',0);
//    $i+=dolibarr_set_const($db,'JASPER_PORT',trim($_POST["JASPER_PORT"]),'',0);
//    $i+=dolibarr_set_const($db,'JASPER_PROTO',trim($_POST["JASPER_PROTO"]),'',0);
//    $i+=dolibarr_set_const($db,'JASPER_PATH',trim($_POST["JASPER_PATH"]),'',0);
//    $i+=dolibarr_set_const($db,'JASPER_REPO_PATH_GENERATED',trim($_POST["JASPER_REPO_PATH_GENERATED"]),'',0);
//    $i+=dolibarr_set_const($db,'JASPER_REPO_PATH_REPORT',trim($_POST["JASPER_REPO_PATH_REPORT"]),'',0);
/*

 */
//
//    if ($i >= 5)
//    {
//        $db->commit();
//        header("Location: BabelJasper.php");
//        exit;
//    }
//    else
//    {
//        $db->rollback();
//        $mesg = "<font class=\"ok\">".$langs->trans("JASPERSetupSaved")."</font>";
//    }
}


/**
 * Affichage du formulaire de saisie
 */

$dtreeJs = "<script language='javascript' type='text/javascript' src='".DOL_URL_ROOT."/Babel_sCal/js/dtree.js' ></script>\n";
$dtreeJs .= '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/Babel_sCal/js/dtree.css">';
$dtreeJs .= "<script language='javascript' type='text/javascript' src='".DOL_URL_ROOT."/Babel_sCal/js/config.js' ></script>\n";


llxHeader($dtreeJs);
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("BabelsCal"),$linkback,'setup');
//print_titre($langs->trans("BabelsCal"));
print '<br>';


//affiche un dtree avec les categories

$requete = "SELECT Babel_Scal_categorie.nom as catNom,
                   Babel_Scal_categorie.id as catId,
                   Babel_Scal_formula.id as fId,
                   Babel_Scal_formula.Formula as formula,
                   Babel_Scal_formula.Description as fDesc
              FROM Babel_Scal_categorie
         LEFT JOIN Babel_Scal_formula ON Babel_Scal_formula.Scal_categorie_refid = Babel_Scal_categorie.id
          ORDER BY Babel_Scal_categorie.DisplayOrder,Babel_Scal_formula.DisplayOrder ";

$arrCat=array();
$arrForm = array();
$resql = $db->query($requete);
if ($resql)
{
    while ($res = $db->fetch_object($resql))
    {
        $arrCat[$res->catNom]=$res->catId;
        if ($res->fId )
        {
            $arrForm[$res->fId]=array('parent' => $res->catId, 'desc' => $langs->Trans($res->fDesc) , 'formula' => $res->formula, 'id' => $res->fId );
        }

    }
}
//print $requete;
print "<div>";
print "<div style='max-height: 375px; overflow: auto; width: 250px; border : 1px Solid rgb(156, 172, 187); float: left;' class='dtree'>\n";
print "    <script type='text/javascript'>\n";
print "        d = new dTree('d');\n";
print "        d.icon = {\n";
print "            root            : '".DOL_URL_ROOT."/Babel_sCal/img/base.gif',";
print "            folder          : '".DOL_URL_ROOT."/Babel_sCal/img/folder.gif',";
print "            folderOpen      : '".DOL_URL_ROOT."/Babel_sCal/img/folderopen.gif',";
print "            node            : '".DOL_URL_ROOT."/Babel_sCal/img/page.gif',";
print "            empty           : '".DOL_URL_ROOT."/Babel_sCal/img/empty.gif',";
print "            line            : '".DOL_URL_ROOT."/Babel_sCal/img/line.gif',";
print "            join            : '".DOL_URL_ROOT."/Babel_sCal/img/join.gif',";
print "            joinBottom      : '".DOL_URL_ROOT."/Babel_sCal/img/joinbottom.gif',";
print "            plus            : '".DOL_URL_ROOT."/Babel_sCal/img/plus.gif',";
print "            plusBottom      : '".DOL_URL_ROOT."/Babel_sCal/img/plusbottom.gif',";
print "            minus           : '".DOL_URL_ROOT."/Babel_sCal/img/minus.gif',";
print "            minusBottom     : '".DOL_URL_ROOT."/Babel_sCal/img/minusbottom.gif',";
print "            nlPlus          : '".DOL_URL_ROOT."/Babel_sCal/img/nolines_plus.gif',";
print "            nlMinus         : '".DOL_URL_ROOT."/Babel_sCal/img/nolines_minus.gif'";
print "        };\n";
print "\n";
print "        d.add(0,-1,'".$langs->trans("Formula")."','?');\n";

foreach($arrCat as $key=>$val)
{
    print "        d.add('c".$val."',
                         0,
                         '".$key."',

                          '?catId=".$val."' , '', '' , '".DOL_URL_ROOT."/Babel_sCal/img/folder.gif');\n";
}
foreach($arrForm as $key=>$val)
{
    print "        d.add(".$val['id'].",
                         'c".$val['parent']."',
                          '".$val['desc']."',
                          '?fId=".$val['id']."');\n";
}
print "        document.write(d);\n";
print "    </script>\n";

print "</div>\n"; // div dtree
$fId = false;
$fName = false;
$fDesc =false;
$fCat = false;
$displayOrder = false;


print "<div style='margin-left: 260px; border: 1px Solid rgb(156, 172, 187); width: 300px' >\n";
if ($_REQUEST['fId'] > 0)
{//modifier form
    $requete = "SELECT id, formula, Description, Scal_categorie_refid, DisplayOrder
                  FROM Babel_Scal_formula
                 WHERE id = " . $_REQUEST['fId'];
    $resql=$db->Query($requete);
    if ($resql)
    {
        while ($res = $db->fetch_object($resql))
        {
            $fId = $res->id;
            $fName = $res->formula;
            $fDesc = $res->Description;
            $fCat = $res->Scal_categorie_refid;
            $displayOrder = $res->DisplayOrder;
        }
    }
    print "<form id='ModifyForm' action='?' method='POST'>";
    print "<table class='border' style='padding-left: 10px; width: 300px;'>";
    print "<tr><th colspan=2 style='border : 1px Solid rgb(156, 172, 187);'>".$langs->Trans('modifier')."</th></tr>";
    print "<tr><th  style='border : 1px Solid rgb(156, 172, 187);'>".$langs->Trans('categories')."</th>";
    print "    <td>";
    print "       <select name='catId' style='width:100%;'>";
    $requete = "SELECT *
                  FROM Babel_Scal_categorie
              ORDER BY Babel_Scal_categorie.DisplayOrder";
    $resql=$db->query($requete);
    if ($resql)
    {
        while ($res=$db->fetch_object($resql))
        {
            if ($fCat == $res->id)
            {
                print "<OPTION SELECTED value='".$res->id."'>".$langs->trans($res->nom)."</OPTION>";
            } else {
                print "<OPTION value='".$res->id."'>".$langs->trans($res->nom)."</OPTION>";
            }
        }
    }
    print "        </select>";
    print "    </td>";
    print "<tr><th  style='border : 1px Solid rgb(156, 172, 187);'>".$langs->trans("Formula");
    print "    <td><input style='width:100%;' type='text' name='formula' value='".$fName."'/>";
    print "<tr><th  style='border : 1px Solid rgb(156, 172, 187);'>".$langs->trans("Description");
    print "    <td><input style='width:100%;' name='description' type='text' value='".$fDesc."' />";
    print "<tr><th  style='border : 1px Solid rgb(156, 172, 187);'>".$langs->trans("D&eacute;placer");
    print "    <td  class='button'>
                 <table class='noborder'>
                   <tr>
                      <td><a href='?action=moveUp&currOrder=".$displayOrder."&fId=".$fId."' class='butAction' >".$langs->Trans('up')."</A></td>
                      <td><a href='?action=moveDown&currOrder=".$displayOrder."&fId=".$fId."' class='butAction'  >".$langs->Trans('down')."</A></td>
                   </tr>
                 </table>";
    print "</td></tr></TABLE>";
    print "<input name='fId' value='".$fId."' type='hidden'/>";
    print "<input name='action' value='modForm' type='hidden'/>";
    print "<input type=\"submit\" name=\"save\" class=\"button\" style='width:300px;border-top:2px Solid rgb(206, 222, 237);' value=\"".$langs->trans("Save")."\">";
    print "</form>";
    print "<form id='delForm' action='?' method='POST'>";
    print "<input name='action' value='delForm' type='hidden'/>";
    //print "<input name='catName' value='".$catName."' type='hidden'/>";
    print "<input name='catId' value='".$catId."' type='hidden'/>";
    print "<input  type='submit' name='savebut' class='button' style='width:300px;border-top:2px Solid rgb(206, 222, 237);' value='".$langs->trans("Effacer")."'>";
    print "</form>";
    //print "</center>";
} else if ($_REQUEST['catId'] > 0)
{//modifier cat
    $requete = "SELECT nom, id, DisplayOrder
                  FROM Babel_Scal_categorie
                 WHERE id = " . $_REQUEST['catId'];
    $resql=$db->Query($requete);
            $catId = false;
            $catName = false;
            $catDisplayOrder = false;

    if ($resql)
    {
        while ($res = $db->fetch_object($resql))
        {
            $catId = $res->id;
            $catName = $res->nom;
            $catDisplayOrder = $res->DisplayOrder;
        }
    }
    print "<form id='modCat' action='?' method='POST'>";
    print "<table class='border' style='padding-left: 10px; width: 300px;'>";
    print "<tr><th colspan=2 style='border : 1px Solid rgb(156, 172, 187);'>".$langs->Trans('modifier')."</th></tr>";
    print "<tr><th  style='border : 1px Solid rgb(156, 172, 187);'>".$langs->Trans('nom')."</th>";
    print "    <td><input style='width:100%;' type='text' name='catName' value='".$catName."'/>";
    print "    </td>";
    print "<tr><th  style='border : 1px Solid rgb(156, 172, 187);'>".$langs->trans("D&eacute;placer");
    print "    <td  class='button'>
                 <table class='noborder'>
                   <tr>
                      <td><a href='?action=moveUp&currOrder=".$catDisplayOrder."&catId=".$catId."' class='butAction' >".$langs->Trans('up')."</A></td>
                      <td><a href='?action=moveDown&currOrder=".$catDisplayOrder."&catId=".$catId."' class='butAction'  >".$langs->Trans('down')."</A></td>
                   </tr>
                 </table>";
    print "</td></tr>";
    print "</TABLE>";
    print "<input name='action' value='ModifyCat' type='hidden'/>";
    //print "<input name='catName' value='".$catName."' type='hidden'/>";
    print "<input name='catId' value='".$catId."' type='hidden'/>";
    print "<input  type='submit' name='savebut' class='button' style='width:300px;border-top:2px Solid rgb(206, 222, 237);' value='".$langs->trans("Save")."'>";
    print "</form>";


    print "<form id='delCat' action='?' method='POST'>";
    print "<input name='action' value='delCat' type='hidden'/>";
    //print "<input name='catName' value='".$catName."' type='hidden'/>";
    print "<input name='catId' value='".$catId."' type='hidden'/>";
    print "<input  type='submit' name='savebut' class='button' style='width:300px;border-top:2px Solid rgb(206, 222, 237);' value='".$langs->trans("Effacer")."'>";
    print "</form>";


    //print "</center>";
} else {
    //ajouter cat

    print "<form id='addForm' action='?' method='GET'>";
    print "<table class='border' style='padding-left: 10px; width: 300px;'>";
    print "<tr><th colspan='2' style='border : 1px Solid rgb(156, 172, 187);'>".$langs->Trans('Ajouter Formule')."</th>";
    print "<tr><th  style='border : 1px Solid rgb(156, 172, 187);'>".$langs->Trans('Categorie')."</th>";
    print "    <td>";
    print "       <select name='catId' style='width:100%';>";
    $requete = "SELECT *
                  FROM Babel_Scal_categorie
              ORDER BY Babel_Scal_categorie.DisplayOrder";
    $resql=$db->query($requete);
    if ($resql)
    {
        while ($res=$db->fetch_object($resql))
        {
            if ($fCat == $res->id)
            {
                print "<OPTION SELECTED value='".$res->id."'>".$langs->trans($res->nom)."</OPTION>";
            } else {
                print "<OPTION value='".$res->id."'>".$langs->trans($res->nom)."</OPTION>";
            }
        }
    }
    print "        </select>";
    print "    </td>";
    print "<tr><th  style='border : 1px Solid rgb(156, 172, 187);'>".$langs->trans("Formula");
    print "    <td><input style='width:100%'; type='text' name='formula' value='".$fName."'/>";
    print "<tr><th  style='border : 1px Solid rgb(156, 172, 187);'>".$langs->trans("Description");
    print "    <td><input style='width:100%;' name='description' type='text' value='".$fDesc."' />";
    print "</td></tr></TABLE>";
    print "<input type='hidden'/ name='action' value='addForm'>";
    print "<input type=\"submit\" name=\"save\" class=\"button\"  style='width:300px;border-top:2px Solid rgb(206, 222, 237);' value=\"".$langs->trans("Save")."\">";
    print "</form>";


    print "<BR><br>";

    print "<form id='AddCat' action='?' method='POST'>";
    print "<table class='border' style='padding-left: 10px; width: 300px;'>";
    print "<tr><th colspan='2' style='border : 1px Solid rgb(156, 172, 187);'>".$langs->Trans('Ajouter Categorie')."</th>";
    print "<tr><th  style='border : 1px Solid rgb(156, 172, 187);'>".$langs->Trans('Nom')."</th>";
    print "    <td><input style='width:100%;' name='categorieStr' type='text' value='".$fDesc."' />";
    print "</td></tr></TABLE>";

    print "<input type='hidden'/ name='action' value='addCat'>";
    print "<input type=\"submit\" name=\"save\" class=\"button\"  style='width:300px;border-top:2px Solid rgb(206, 222, 237);' value=\"".$langs->trans("Save")."\">";
    print "</form>";
    //print "</center>";
}
print "</div>";

//print "</form>\n";

print "<div style='clear: both; padding-top: 2pt;'>\n";
print "\n";
print "\n";
print "    <p><a href='javascript: d.openAll();'>".$langs->trans("open all")."</a> | <a href='javascript: d.closeAll();'>".$langs->trans("close all")."</a></p>\n";
print "\n";
print "</div>\n";

clearstatcache();

if ($mesg) print "<br>$mesg<br>";
print "<br>";

$db->close();

llxFooter('$Date: 2005/10/03 01:36:21 $ - $Revision: 1.23 $');
?>