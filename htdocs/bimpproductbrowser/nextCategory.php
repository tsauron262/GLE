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
 *      \file       /bimpproductbrowser/nextCategory.php
 *      \ingroup    bimpproductbrowser
 *      \brief      Page without view, it just manage POST request
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpproductbrowser/class/productBrowser.class.php';
$pb = new BimpProductBrowser($db);

switch (GETPOST('action')) {
    case 'addCategory': {
            $objOut = $pb->addProdToCat(GETPOST('id_prod'), GETPOST('id_categ'));
            echo json_encode($objOut);
            break;
        }
    case 'delSomeCateg': {
            $pb->deleteSomeCateg(GETPOST('id_prod'), GETPOST('id_cat_out'));
        }
    case 'getOldWay': {
            $objOut = $pb->getOldWay(GETPOST('id_prod'));
//            if(GETPOST("test") != ""){
//            echo "<pre>";print_r($objOut);die;}
            echo json_encode($objOut);
        }
    default: break;
}

$db->close();



