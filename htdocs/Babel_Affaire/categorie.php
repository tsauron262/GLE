<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin            <patrick.raguin@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
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
  *//*
 */

/**
        \file       htdocs/categories/categorie.php
        \ingroup    category
        \brief      Page de l'onglet categories
        \version    $Id: categorie.php,v 1.19 2008/03/07 00:32:18 eldy Exp $
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/categories/categorie.class.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_Affaire/Affaire.class.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_Affaire/fct_affaire.php");

$langs->load("categories");

$id=$_REQUEST['id'];
$affaireid=$_REQUEST['id'];

$mesg=isset($_GET["mesg"])?'<div class="ok">'.$_GET["mesg"].'</div>':'';

$type = 'affaire';

if ($_REQUEST["typeid"] == 3)
{
    $type = 'affaire';
}

// Security check
if ($user->societe_id) $socid=$user->societe_id;
//$result = restrictedArea($user, $type, $objectid);



/*
*    Actions
*/

//Suppression d'un objet d'une categorie
if ($_REQUEST["removecat"])
{
    $object='';
    if (($_REQUEST["id"] > 0 ) && $type == 'affaire' )//&& $user->rights->produit->creer)
    {
        $object = new Affaire($db);
        $object->fetch($affaireid);
    }
    $cat = new Categorie($db,$_REQUEST["removecat"]);
    $result=$cat->del_type($object,$type);
}

//Ajoute d'un objet dans une categorie
if (isset($_REQUEST["catMere"])&& $type == 'affaire' )// && $_REQUEST["catMere"]>=0)
{
    $object='';
    if (($_REQUEST["id"]) && $user->rights->produit->creer)
    {
       $object = new Affaire($db);
       $object->fetch($affaireid);
    }
    $cat = new Categorie($db);
    $result=$cat->fetch($_REQUEST["catMere"]);

    $result=$cat->add_type($object,$type);
    if ($result >= 0)
    {
        $mesg='<div class="ok">'.$langs->trans("WasAddedSuccessfully",$cat->label).'</div>';
    }
    else
    {
        if ($cat->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') $mesg='<div class="warning">'.$langs->trans("ObjectAlreadyLinkedToCategory").'</div>';
        else $mesg='<div class="error ui-state-error">'.$langs->trans("Error").' '.$cat->error.'</div>';
    }

}






/*
*    View
*/

$html = new Form($db);

/*
 * Fiche categorie de client et/ou fournisseur
 */
    /*
    * Fiche categorie de produit
    */
    require_once(DOL_DOCUMENT_ROOT."/Babel_Affaire/Affaire.class.php");

    require_once(DOL_DOCUMENT_ROOT."/Babel_Affaire/fct_affaire.php");


    // Produit
    $affaire = new Affaire($db);
    if ($id> 0) $result = $affaire->fetch($id);

  llxHeader("","",$langs->trans("Categorie"));
//
//
//    $head=affaire_prepare_head($affaire, $user);
//    $titre=$langs->trans("CardProduct".$affaire->type);
//    dol_fiche_head($head, 'category', $titre);

    print_cartoucheAffaire($affaire,"Categorie");
    print '</div>';

    if ($mesg) print($mesg);

    formCategory($db,$affaire,'affaire',3);


/*
* Fonction Barre d'actions
*/
function formCategory($db,$object,$type,$typeid)
{
    global $user,$langs,$html,$bc;

    if ($typeid == 0) $title = $langs->trans("ProductsCategoriesShort");
    if ($typeid == 1) $title = $langs->trans("SuppliersCategoriesShort");
    if ($typeid == 2) $title = $langs->trans("CustomersCategoriesShort");
    if ($typeid == 3) $title = $langs->trans("AffaireCategoriesShort");
    if ($type == 'societe' || $type == 'fournisseur')
    {
        $nameId = 'socid';
    }
    else if ($type == 'product' || $type="affaire")
    {
        $nameId = 'id';
    }

    // Formulaire ajout dans une categorie
    print '<br>';
    print_fiche_titre($title);
    print '<form method="post" action="'.DOL_URL_ROOT.'/Babel_Affaire/categorie.php?'.$nameId.'='.$object->id.'">';
    print '<input type="hidden" name="typeid" value="'.$typeid.'">';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>';
    print $langs->trans("ClassifyInCategory").' <td align=right >';
    print $html->select_all_categories($typeid).' <td align=left><input type="submit" class="button" value="'.$langs->trans("Classify").'"></td>';
    if ($user->rights->categorie->creer)
    {
        print '<td align="right">';
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/categories/fiche.php?action=create&amp;origin='.$object->id.'&type='.$typeid.'">'.$langs->trans("NewCat").'</a>';
        print '</td>';
    }
    print '</tr>';
    print '</table>';
    print '</form>';
    print '<br/>';


    $c = new Categorie($db);
    $cats = $c->containing($object->id,$type,$typeid);

    if (sizeof($cats) > 0)
    {
        if ($typeid == 0) $title=$langs->trans("ProductIsInCategories");
        if ($typeid == 1) $title=$langs->trans("CompanyIsInSuppliersCategories");
        if ($typeid == 2) $title=$langs->trans("CompanyIsInCustomersCategories");
        if ($typeid == 3) $title=$langs->trans("AffaireIsInCategories");
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre"><td colspan="2">'.$title.':</td></tr>';

        $var = true;
        foreach ($cats as $cat)
        {
            $ways = $cat->print_all_ways ();
            foreach ($ways as $way)
            {
                $var = ! $var;
                print "<tr ".$bc[$var].">";

                // Categorie
                print "<td>".$way."</td>";

                // Lien supprimer
                print '<td align="right">';
                $permission=0;
                if ($type == 'fournisseur') $permission=$user->rights->societe->creer;
                if ($type == 'societe')     $permission=$user->rights->societe->creer;
                if ($type == 'product')     $permission=$user->rights->produit->creer;
                if ($type == 'affaire')     $permission=$user->rights->affaire->creer;
//debug
                if ($permission || true)
                {
                    print "<a href= '".DOL_URL_ROOT."/Babel_Affaire/categorie.php?".$nameId."=".$object->id."&amp;typeid=".$typeid."&amp;removecat=".$cat->id."'>";
                    print img_delete($langs->trans("DeleteFromCat")).' ';
                    print $langs->trans("DeleteFromCat")."</a>";
                } else {
                    print '&nbsp;';
                }
                print "</td>";

                print "</tr>\n";
            }
        }
        print "</table>\n";
    } else if($cats < 0) {
        print $langs->trans("ErrorUnknown");
    } else {
        if ($typeid == 0) $title=$langs->trans("ProductHasNoCategory");
        if ($typeid == 1) $title=$langs->trans("CompanyHasNoCategory");
        if ($typeid == 2) $title=$langs->trans("CompanyHasNoCategory");
        if ($typeid == 3) $title=$langs->trans("AffaireHasNoCategory");
        print $title;
        print "<br/>";
    }

    print "<br>";
    print "<table class='noborder' width='100%'><thead>\n";
    print "<tr class='liste_titre'><td colspan='3'>".$langs->trans("Extension Affaire")."</td></tr>\n";
    print "</thead>";

    require_once(DOL_DOCUMENT_ROOT.'/categories/template.class.php');
    $template = new Template($db);
    $arrId=array();

    if (sizeof($cats) > 0)
    {
        $title=$langs->trans("ProductIsInCategories");
        $var = true;
        //ajout parents
        foreach($cats as $cat=>$c)
        {
            $c->db=$db;
            $meres = $c->get_meres();
            foreach($meres as $k=>$v)
            {
                $v->db=$db;
                $arrId=array_merge($arrId,recursParent($v));
            }
        }
        foreach($arrId as $k=>$v)
        {
            $tmpC = new Categorie($db,$v);
            $addElement=true;
            foreach($cats as $k1=>$v1)
            {
                if ($v1->id == $v)
                {
                    $addElement=false;
                }
            }
            if ($addElement)
            {
                $cats[]=$tmpC;
            }
        }
    }

    //Liste des templates associes
    foreach ($cats as $cat)
    {
//        print $cat->label;
        $tplList = $template->fetch_by_cat($cat->id);
        if (count($tplList) > 0){
            foreach($tplList as $key=>$val)
            {
                $i = 0;
                $var=true;
                print "<thead><tr><td colspan=3 style='padding: 5px;'><div class='titre'>".$cat->label." ".$val."</div>";
                print "<tbody>";
                $template->fetch($key);
                $template->getKeys();
                global $affaire;
                $template->getValues($affaire->id,"affaire");
                print "<br/>";
                foreach ($template->keysList as $key1=>$val1)
                {
                    $var=!$var;
                    print "<tr ".$bc[$var].">";
                    print '<td valign="top">'.$val1['nom']."</td>";
                    print '<td valign="top">'.$val1['description']."</td>";
        //            $this->extraValue[$prod_id][$key]=array('value'=>$value,'description'=>$desc);

                    $value = $template->extraValue[$affaire->id][$val1['nom']]['value'];
                    print '<td valign="top">'.$value."</td>";
                    print "</tr>";
                }
                print "</tbody>";
            }
        } else {
/*            print "<tr><td valign=top colspan=3>Pas d'extension produit pour cette cat&eacute;gorie";*/
        }
    }
    print "</table>";

}

$db->close();

llxFooter('$Date: 2008/03/07 00:32:18 $ - $Revision: 1.19 $');
?>
