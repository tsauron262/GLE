<?php
/* Copyright (C) 2005       Matthieu Valleton   <mv@seeschloss.org>
 * Copyright (C) 2005       Eric Seigne         <eric.seigne@ryxeo.com>
 * Copyright (C) 2006-2016  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2007       Patrick Raguin      <patrick.raguin@gmail.com>
 * Copyright (C) 2005-2012  Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/categories/index.php
 *      \ingroup    category
 *      \brief      Home page of category area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';

require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$arrayofjs=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.js', '/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', '/bimpproductbrowser/js/checkboxManager.js');
$arrayofcss=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');

$langs->load("categories");

if (! $user->rights->categorie->lire) accessforbidden();


// $id=GETPOST('id','int');
// $label=GETPOST('label');
// $type=GETPOST('type');
// $action=GETPOST('action','aZ09');
// $confirm=GETPOST('confirm');
// $removeelem = GETPOST('removeelem','int');
// $elemid=GETPOST('elemid');

$id=GETPOST('id','int');
$type=(GETPOST('type','aZ09') ? GETPOST('type','aZ09') : Categorie::TYPE_PRODUCT);
$catname=GETPOST('catname','alpha');
$label=GETPOST('label');
$elemid=GETPOST('elemid');

// Security check
$result = restrictedArea($user, 'categorie', $id, '&category');

$object = new Categorie($db);
$result=$object->fetch($id, $label);
$object->fetch_optionals($id,$extralabels);
if ($result <= 0)
{
    dol_print_error($db,$object->error);
    exit;
}

$type=$object->type;

$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

$hookmanager->initHooks(array('categorycard'));


/*
 * Functions
 */ 


function createRestriction ($db)
{
    $arrayofid = GETPOST ('ids');
    echo '<script>console.log("OHHHH '.$arrayofid.'")</script>';
    print_r($arrayofid);
    $db->begin();
    $sql ='INSERT INTO '.MAIN_DB_PREFIX.'bimp_cat_cat (fk_parent_cat, fk_child_cat) ';
    $sql.='VALUES ('.$arrayofid[0].', '.$arrayofid[1].');';

    try
    {
        echo '<script>console.log("'.$sql.'")</script>';
        $db->query($sql);
        $db->commit();
    }
    catch(Exception $e)
    {
        echo 'ERROR:'.$e->getMessage();
        $db->rollback();
    }
}

/*
 * Action
 */ 

//echo '<script>console.log("GETPOST('action') '.GETPOST('action').'")</script>';



switch (GETPOST('action'))
{
    case 'filldb':
    {
        createRestriction($db);
        break;
    }
    default: break;
}


/*
 * View
 */ 

$categstatic = new Categorie($db);
$form = new Form($db);

if ($type == Categorie::TYPE_PRODUCT)       { $title=$langs->trans("ProductsCategoryShort");  $typetext='product'; }
elseif ($type == Categorie::TYPE_SUPPLIER)  { $title=$langs->trans("SuppliersCategoryShort"); $typetext='supplier'; }
elseif ($type == Categorie::TYPE_CUSTOMER)  { $title=$langs->trans("CustomersCategoriesArea"); $typetext='customer'; }
elseif ($type == Categorie::TYPE_MEMBER)    { $title=$langs->trans("CustomersCategoryShort");   $typetext='member'; }
elseif ($type == Categorie::TYPE_CONTACT)   { $title=$langs->trans("ContactCategoriesShort");  $typetext='contact'; }
elseif ($type == Categorie::TYPE_ACCOUNT)   { $title=$langs->trans("AccountsCategoriesShort");  $typetext='account'; }
elseif ($type == Categorie::TYPE_PROJECT)   { $title=$langs->trans("ProjectsCategoriesShort");  $typetext='project'; }
else                                        { $title=$langs->trans("Category");          $typetext='unknown'; }

llxHeader('','','','',0,0,$arrayofjs,$arrayofcss);

$head = categories_prepare_head($object,$type);


dol_fiche_head($head, 'recherchefiltree', $title, -1, 'category');

$linkback = '<a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("BackToList").'</a>';
$object->next_prev_filter=" type = ".$object->type;
$object->ref = $object->label;
$morehtmlref='<br><div class="refidno"><a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
$ways = $object->print_all_ways(" &gt;&gt; ", '', 1);
foreach ($ways as $way)
{
    $morehtmlref.=$way."<br>\n";
}
$morehtmlref.='</div>';

dol_banner_tab($object, 'label', $linkback, ($user->societe_id?0:1), 'label', 'label', $morehtmlref, '', 0, '', '', 1);

//print load_fiche_titre($title);

//print '<table border="0" width="100%" class="notopnoleftnoright">';
//print '<tr><td valign="top" width="30%" class="notopnoleft">';
print '<div class="fichecenter"><div class="fichethirdleft">';




//print '</td><td valign="top" width="70%">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


//print '</td></tr></table>';
print '</div></div></div>';

print '<div class="fichecenter"><br>';


// Charge tableau des categories
$cate_arbo = $categstatic->get_full_arbo($typetext);

// Define fulltree array
$fulltree=$cate_arbo;

// Define data (format for treeview)
$data=array();
$data[] = array('rowid'=>0,'fk_menu'=>-1,'title'=>"racine",'mainmenu'=>'','leftmenu'=>'','fk_mainmenu'=>'','fk_leftmenu'=>'');
foreach($fulltree as $key => $val)
{
    $categstatic->id=$val['id'];
    $categstatic->ref=$val['label'];
    $categstatic->color=$val['color'];
    $categstatic->type=$type;
    $li=$categstatic->getNomUrl(1,'',60);
    $desc=dol_htmlcleanlastbr($val['description']);

    $data[] = array(
    'rowid'=>$val['rowid'],
    'fk_menu'=>$val['fk_parent'],
    'entry'=>'<table class="nobordernopadding centpercent"><tr><td><span class="noborderoncategories" '.($categstatic->color?' style="background: #'.$categstatic->color.';"':' style="background: #aaa"').'><input type="checkbox" id='.$val['rowid'].'>'.$li.'</span></td>'.
    //'<td width="50%">'.dolGetFirstLineOfText($desc).'</td>'.
    '<td align="right" width="20px;"><a href="'.DOL_URL_ROOT.'/categories/viewcat.php?id='.$val['id'].'&type='.$type.'">'.img_view().'</a></td>'.
    '</tr></table>'
    );
}


print '<table class="liste nohover" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Categories").'</td><td></td><td align="right">';
if (! empty($conf->use_javascript_ajax))
{
    print '<div id="iddivjstreecontrol"><a class="notasortlink" href="#">'.img_picto('','object_category').' '.$langs->trans("UndoExpandAll").'</a> | <a class="notasortlink" href="#">'.img_picto('','object_category-expanded').' '.$langs->trans("ExpandAll").'</a></div>';
}
print '</td></tr>';

$nbofentries=(count($data) - 1);

if ($nbofentries > 0)
{
    print '<tr class="pair"><td colspan="3">';
    tree_recur($data,$data[0],0);
    print '</td></tr>';
    print "</table>";
    print '</div>';
    print '<input id="submitTree" class="button" type="button" value="Valider">';
}
else
{
    print '<tr class="pair">';
    print '<td colspan="3"><table class="nobordernopadding"><tr class="nobordernopadding"><td>'.img_picto_common('','treemenu/branchbottom.gif').'</td>';
    print '<td valign="middle">';
    print $langs->trans("NoCategoryYet");
    print '</td>';
    print '<td>&nbsp;</td>';
    print '</table></td>';
    print '</tr>';
    print "</table>";
    print '</div>';
}

print '<div id="placeforalert"><br></div>';

llxFooter();

$db->close();
