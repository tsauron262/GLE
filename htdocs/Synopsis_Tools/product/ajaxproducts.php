<?php

/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */
/*
 * GLE by Babel-Services
 *
 * Author: Jean-Marc LE FEVRE <jm.lefevre@babel-services.com>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.babel-services.com
 *
 */
/*
 */

/**
  \file       htdocs/product/ajaxproducts.php
  \brief      Fichier de reponse sur evenement Ajax
  \version    $Id: ajaxproducts.php,v 1.15 2008/08/06 13:07:00 eldy Exp $
 */
require('../../main.inc.php');

$langs->load("products");
$langs->load("main");

if (isset($_REQUEST["keysearch"])) {
    $tab = explode("/", $_REQUEST["keysearch"]);
    $tab = explode("   ", $tab[0]);
    $_REQUEST["keysearch"] = utf8_encode(addslashes($tab[0]));
//    $_REQUEST["keysearch"] = utf8_encode(addslashes($_REQUEST["keysearch"]));
}


//top_htmlhead("", "", 1);
header("text/html");
print '<body class="nocellnopadd">' . "\n";




$form = new Form($db);
$form->select_produits_do($_REQUEST['prodId'], $_REQUEST["htmlname"], $_REQUEST["type"], 50, $_REQUEST["price_level"], utf8_decode($_REQUEST["keysearch"]), $_REQUEST['status']);


//
//
//
//if ($_REQUEST['prodId'] > 0 && $_REQUEST['showUniq'] == 1) {
//    $status = -1;
//    if (isset($_REQUEST['status']))
//        $status = $_REQUEST['status'];
//    $form = new Form($db);
//    if (empty($_REQUEST['mode']) || $_REQUEST['mode'] == 1) {
//        $form->select_produits_do($_REQUEST['prodId'], $_REQUEST["htmlname"], $_REQUEST["type"], "", $_REQUEST["price_level"], utf8_decode($_REQUEST["keysearch"]), $status, true, true, 1);
//    }
//} else if ($_REQUEST['prodId'] > 0) {
//    $status = -1;
//    if (isset($_REQUEST['status']))
//        $status = $_REQUEST['status'];
//    $form = new Form($db);
//    if (empty($_REQUEST['mode']) || $_REQUEST['mode'] == 1) {
//        $form->select_produits_do($_REQUEST['prodId'], $_REQUEST["htmlname"], $_REQUEST["type"], "", $_REQUEST["price_level"], utf8_decode($_REQUEST["keysearch"]), $status);
//    }
//// Generation liste de produits
//} else if (!empty($_REQUEST['keysearch'])) {
//    $status = -1;
//    if (isset($_REQUEST['status']))
//        $status = $_REQUEST['status'];
//
//    $form = new Form($db);
//    if (empty($_REQUEST['mode']) || $_REQUEST['mode'] == 1) {
//        $form->select_produits_do("", $_REQUEST["htmlname"], $_REQUEST["type"], "", $_REQUEST["price_level"], utf8_decode($_REQUEST["keysearch"]), $status);
//    }
//    if ($_REQUEST['mode'] == 2) {
//        $form->select_produits_fournisseurs_do($_REQUEST["socid"], "", $_REQUEST["htmlname"], $_REQUEST["type"], "", utf8_decode($_REQUEST["keysearch"]));
//    }
//} else if (!empty($_REQUEST['markup'])) {
//    print $_REQUEST['markup'];
//    //print $_REQUEST['count'];
//    //$field = "<input size='10' type='text' class='flat' id='sellingdata_ht".$_REQUEST['count']."' name='sellingdata_ht".$_REQUEST['count']."' value='".$_REQUEST['markup']."'>";
//    //print '<input size="10" type="text" class="flat" id="sellingdata_ht'.$_REQUEST['count'].'" name="sellingdata_ht'.$_REQUEST['count'].'" value="'.$field.'">';
//    //print $field;
//} else if (!empty($_REQUEST['selling'])) {
//    //print $_REQUEST['markup'];
//    //print $_REQUEST['count'];
//    print '<input size="10" type="text" class="flat" name="cashflow' . $_REQUEST['count'] . '" value="' . $_REQUEST['selling'] . '">';
//}

print "</body>";
print "</html>";
?>
