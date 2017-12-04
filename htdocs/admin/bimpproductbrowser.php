<?php

/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2008-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2013 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013-2017 Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
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
 * 	\file       htdocs/admin/bimpproductbrowser.php
 * 	\ingroup    stock
 * 	\brief      Page d'administration/configuration du module de catégorisation
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/categories.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/treeview.lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpproductbrowser/class/productBrowser.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

$arrayofjs = array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.js', '/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js');
$arrayofcss = array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');

$langs->load("admin");
$langs->load("stocks");

// Securit check
if (!$user->admin)
    accessforbidden();

/*
 * Actions
 */

$idRootCateg = GETPOST('categMere', 'int');

if ($idRootCateg) {
    $db->begin();

    $res = dolibarr_set_const($db, "BIMP_ROOT_CATEGORY", $idRootCateg, 'chaine', 0, '', $conf->entity);
    
    if (!$res > 0)
        $error++;

    if (!$error) {
        $db->commit();
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        $db->rollback();
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}


/*
 * View
 */
llxHeader('', 'Configuration du module de catégorisation', '', '', 0, 0, $arrayofjs, $arrayofcss);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre('Configuration du module de catégorisation', $linkback, 'title_setup');

// print info_admin('Test');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print "  <td>Veuillez sélectionner la catégorie qui sera utilisée comme racine    </td>\n";
print "  <td align=\"right\" width=\"160\">&nbsp;</td>\n";
print '</tr>' . "\n";

print '</table>';


if (!$user->admin)
    accessforbidden();

$categstatic = new Categorie($db);

if ($type == Categorie::TYPE_PRODUCT) {
    $title = $langs->trans("ProductsCategoryShort");
    $typetext = 'product';
} elseif ($type == Categorie::TYPE_SUPPLIER) {
    $title = $langs->trans("SuppliersCategoryShort");
    $typetext = 'supplier';
} elseif ($type == Categorie::TYPE_CUSTOMER) {
    $title = $langs->trans("CustomersCategoriesArea");
    $typetext = 'customer';
} elseif ($type == Categorie::TYPE_MEMBER) {
    $title = $langs->trans("CustomersCategoryShort");
    $typetext = 'member';
} elseif ($type == Categorie::TYPE_CONTACT) {
    $title = $langs->trans("ContactCategoriesShort");
    $typetext = 'contact';
} elseif ($type == Categorie::TYPE_ACCOUNT) {
    $title = $langs->trans("AccountsCategoriesShort");
    $typetext = 'account';
} elseif ($type == Categorie::TYPE_PROJECT) {
    $title = $langs->trans("ProjectsCategoriesShort");
    $typetext = 'project';
} else {
    $title = $langs->trans("Category");
    $typetext = 'unknown';
}

// Charge tableau des categories
$cate_arbo = $categstatic->get_full_arbo($typetext);

// Define fulltree array
$fulltree = $cate_arbo;
// Define data (format for treeview)
$data = array();
$data[] = array('rowid' => 0, 'fk_menu' => -1, 'title' => "racine", 'mainmenu' => '', 'leftmenu' => '', 'fk_mainmenu' => '', 'fk_leftmenu' => '');
foreach ($fulltree as $key => $val) {
    $categstatic->id = $val['id'];
    $categstatic->ref = $val['label'];
    $categstatic->color = $val['color'];
    $categstatic->type = $type;
    $li = $categstatic->getNomUrl(1, '', 60);
    $desc = dol_htmlcleanlastbr($val['description']);
    if($val['rowid'] == $conf->global->BIMP_ROOT_CATEGORY)
        $checked = ' checked';
    else
        $checked = '';
    $data[] = array(
        'rowid' => $val['rowid'],
        'fk_menu' => $val['fk_parent'],
        'entry' => '<table class="nobordernopadding centpercent"><tr><td><span class="noborderoncategories" ' . ($categstatic->color ? ' style="background: #' . $categstatic->color . ';"' : ' style="background: #aaa"') . '><input type="radio" value=' . $val['rowid'] . ' name="categMere"'.$checked.'>' . $li . '</span></td>' .
        '<td align="right" width="20px;"><a href="' . DOL_URL_ROOT . '/categories/viewcat.php?id=' . $val['id'] . '&type=' . $type . '">' . img_view() . '</a></td>' .
        '</tr></table>'
    );
}



print '<table class="liste nohover" width="100%">';
print '<tr class="liste_titre"><td>' . $langs->trans("Categories") . '</td><td></td><td align="right">';
if (!empty($conf->use_javascript_ajax)) {
    print '<div id="iddivjstreecontrol"><a class="notasortlink" href="#">' . img_picto('', 'object_category') . ' ' . $langs->trans("UndoExpandAll") . '</a> | <a class="notasortlink" href="#">' . img_picto('', 'object_category-expanded') . ' ' . $langs->trans("ExpandAll") . '</a></div>';
}
print '</td></tr>';

$nbofentries = (count($data) - 1);
print '<form>';

if ($nbofentries > 0) {
    print '<tr class="pair"><td colspan="3">';
    tree_recur($data, $data[0], 0);
    print '</td></tr>';
    print "</table>";
    print '</div>';
} else {
    print '<tr class="pair">';
    print '<td colspan="3"><table class="nobordernopadding"><tr class="nobordernopadding"><td>' . img_picto_common('', 'treemenu/branchbottom.gif') . '</td>';
    print '<td valign="middle">';
    print $langs->trans("NoCategoryYet");
    print '</td>';
    print '<td>&nbsp;</td>';
    print '</table></td>';
    print '</tr>';
    print "</table>";
    print '</div>';
}
print '<input type="submit" class="button" type="button" value="Valider">';

print '</form>';


llxFooter();

$db->close();