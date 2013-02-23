<?php
/* Copyright (C) 2012      Mikael Carlavan        <contact@mika-carl.fr>
 *                                                http://www.mikael-carlavan.fr
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
 *      \file       htdocs/ndfp/admin/dict.php
 *		\ingroup    ndfp
 *		\brief      Page to display dictionaries for module
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/ndfp/class/ndfp.class.php");

$langs->load("admin");
$langs->load("ndfp");
$langs->load("other");
$langs->load("errors");

if (!$user->admin)
{
   accessforbidden(); 
}

//Init error
$error = false;
$message = false;


//
$tables = array();

$c_exp = new StdClass();
$c_exp->name = "DictionnaryExp";
$c_exp->mysqlname = MAIN_DB_PREFIX."c_exp";
$c_exp->url = DOL_URL_ROOT."/ndfp/admin/dictexp.php";

$c_exp_tax_range = new StdClass();
$c_exp_tax_range->name = "DictionnaryTaxRange";
$c_exp_tax_range->mysqlname = MAIN_DB_PREFIX."c_exp_tax_range";
$c_exp_tax_range->url = DOL_URL_ROOT."/ndfp/admin/dictexptaxrange.php";

$c_exp_tax_cat = new StdClass();
$c_exp_tax_cat->name = "DictionnaryTaxCat";
$c_exp_tax_cat->mysqlname = MAIN_DB_PREFIX."c_exp_tax_cat";
$c_exp_tax_cat->url = DOL_URL_ROOT."/ndfp/admin/dictexptaxcat.php";

$c_exp_tax = new StdClass();
$c_exp_tax->name = "DictionnaryTax";
$c_exp_tax->mysqlname = MAIN_DB_PREFIX."c_exp_tax";
$c_exp_tax->url = DOL_URL_ROOT."/ndfp/admin/dictexptax.php";

$tables[] = $c_exp;
$tables[] = $c_exp_tax_range;
$tables[] = $c_exp_tax_cat;
$tables[] = $c_exp_tax;

$h = 0;
$head = array();

$head[$h][0] = DOL_URL_ROOT.'/ndfp/admin/config.php';
$head[$h][1] = $langs->trans("Setup");
$head[$h][2] = 'config';
$h++;

$head[$h][0] = DOL_URL_ROOT.'/ndfp/admin/dict.php';
$head[$h][1] = $langs->trans("Dict");
$head[$h][2] = 'dict';
$h++;

$current_head = 'dict';

$html = new Form($db);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';           
/*
 * View
 */

require_once("../tpl/admin.dict.tpl.php");

$db->close();

?>
