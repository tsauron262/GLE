<?php

/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2006      Auguria SARL         <info@auguria.org>
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
 */
/*
 */

/**
 *  \file       htdocs/product/card.php
 *  \ingroup    product
 *  \brief      Page de la fiche produit
 *  \version    $Id: card.php,v 1.242 2008/08/04 00:30:52 eldy Exp $
 */
//require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");


define('USE_CAT', false);

$langs->load("bills");
$langs->load("other");
$langs->load("stocks");

$mesg = '';



$form = new Form($db);
if (!isset($_POST['action']))
    $_POST['action'] = '';
if (!isset($_GET['action']))
    $_GET['action'] = '';
if (!isset($_GET['id']) && isset($_POST['id']))
    $_GET['id'] = $_POST['id'];
if (!$user->rights->produit->lire)
    accessforbidden();

/*
 *
 */
if (!isset($_POST["action"]))
    $_POST["action"] = '';
if (!isset($_GET["action"]))
    $_GET["action"] = '';


if ($_POST["action"] != '')
    $action = $_POST["action"];
else
    $action = $_GET["action"];



if ($_GET["action"] == 'fastappro') {
    $product = new Product($db);
    $product->fetch($_GET["id"]);
    $result = $product->fastappro($user);
    Header("Location: card.php?id=" . $_GET["id"]);
    exit;
}


// Action ajout d'un produit ou service
if ($_POST["action"] == 'add' && $user->rights->produit->creer) {
    $error = 0;

    if (empty($_POST["libelle"])) {
        $mesg = '<div class="error ui-state-error">' . $langs->trans('ErrorFieldRequired', $langs->transnoentities('Label')) . '</div>';
        $_GET["action"] = "create";
        $_GET["canvas"] = $product->canvas;
        $_GET["type"] = $_POST["type"];
        $error++;
    }

    if (!$error) {
        if ($_POST["canvas"] <> '' && file_exists('canvas/product.' . $_POST["canvas"] . '.class.php')) {
            $class = 'Product' . ucfirst($_POST["canvas"]);
            include_once('canvas/product.' . $_POST["canvas"] . '.class.php');
            $product = new $class($db);
        } else {
            $product = new Product($db);
        }

        $product->ref = $_POST["ref"];
        $product->label = $_POST["libelle"];
        $product->price_base_type = $_POST["price_base_type"];
        if ($product->price_base_type == 'TTC')
            $product->price_ttc = $_POST["price"];
        else
            $product->price = $_POST["price"];
        if ($product->price_base_type == 'TTC')
            $product->price_loc_ttc = $_POST["Locprice"];
        else
            $product->price_loc = $_POST["Locprice"];
        $product->tva_tx = $_POST["tva_tx"];
        $product->type = $_POST["type"];
        $product->status = $_POST["statut"];
        $product->description = $_POST["desc"];
        $product->note = $_POST["note"];
        $product->duration_value = $_POST["duration_value"];
        $product->duration_unit = $_POST["duration_unit"];
        $product->seuil_stock_alerte = $_POST["seuil_stock_alerte"];
        $product->canvas = $_POST["canvas"];
        $product->weight = $_POST["weight"];
        $product->weight_units = $_POST["weight_units"];
        $product->volume = $_POST["volume"];
        $product->volume_units = $_POST["volume_units"];
        $product->array_options['options_2dureeSav'] = $_REQUEST["set_DURAV"];
        $product->array_options['options_2clause'] = $_POST['clause'];
        if ($product->type == 2) {
            $product->array_options['options_2isSav'] = 0;
            $product->array_options['options_2reconductionAuto'] = ($_REQUEST['reconductionauto'] . "x" == "x" ? 0 : 1);
            $product->array_options['options_2visiteSurSite'] = 0;
            $product->array_options['options_2visiteCur'] = 0;
            $product->array_options['options_2SLA'] = addslashes($_REQUEST['SLA']);
            $product->array_options['options_2maintenance'] = 0;
            $product->array_options['options_2teleMaintenance'] = 0;
            $product->array_options['options_2teleMaintenanceCur'] = 0;
            $product->array_options['options_2hotline'] = 0;
            $product->array_options['options_2qte'] = 0;
            if ($_REQUEST['typeProd'] == 'SAV') {
                $product->array_options['options_2isSav'] = 1;
                $product->array_options['options_2dureeVal'] = ($_REQUEST['dureeSAV'] > 0 ? $_REQUEST['dureeSAV'] : 0);
            } else if ($_REQUEST['typeProd'] == 'MNT') {
                $product->array_options['options_2dureeVal'] = ($_REQUEST['dureeMNT'] > 0 ? $_REQUEST['dureeMNT'] : 0);
                $product->array_options['options_2visiteSurSite'] = ($_REQUEST['visite'] > 0 ? $_REQUEST['visite'] : 0);
                $product->array_options['options_2visiteCur'] = ($_REQUEST['visiteCur'] > 0 ? $_REQUEST['visiteCur'] : 0);
                $product->array_options['options_2maintenance'] = 1;
                $product->array_options['options_2teleMaintenance'] = $_REQUEST['telemaintenance'];
                $product->array_options['options_2teleMaintenanceCur'] = $_REQUEST['telemaintenanceCur'];
                $product->array_options['options_2hotline'] = $_REQUEST['hotline'];
                $product->array_options['options_2qte'] = ($_REQUEST['qteMNT'] . "x" == "x" ? 0 : $_REQUEST['qteMNT']);
                $product->array_options['options_2qtePerDuree'] = $_REQUEST['qteTktPerDuree'];
                $product->array_options['options_2timePerDuree'] = (intval($_REQUEST['qteTempsPerDureeH']) * 3600 + intval($_REQUEST['qteTempsPerDureeM']) * 60);
            } else if ($_REQUEST['typeProd'] == 'TKT') {
                $product->array_options['options_2dureeVal'] = ($_REQUEST['dureeTKT'] > 0 ? $_REQUEST['dureeTKT'] : 0);
                $product->array_options['options_2qte'] = ($_REQUEST['qteTKT'] . "x" == "x" ? 0 : $_REQUEST['qteTKT']);
            }
        }

        // MultiPrix
        if (isset($conf->global->PRODUIT_MULTIPRICES) && $conf->global->PRODUIT_MULTIPRICES == 1) {
            for ($i = 2; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++) {
                if ($_POST["price_" . $i]) {
                    $price = price2num($_POST["price_" . $i]);
                    $product->multiprices["$i"] = $price;
                    $product->multiprices_base_type["$i"] = $_POST["multiprices_base_type_" . $i];
                } else {
                    $product->multiprices["$i"] = "";
                }
            }
        }
        if ($value != $current_lang)
            $e_product = $product;

        // Produit specifique
        // $_POST n'est pas utilise dans la classe Product
        // mais dans des classes qui herite de Product
        $id = $product->create($user, $_POST);


        if ($id > 0) {
            if (isset($conf->global->MAIN_MODULE_BABELPRIME)) {
                //PN Babel
                $requete = "insert into Babel_PN_products (Product_refid, Prime_CatPN_refid) values (" . $id . "," . $_POST['catPN'] . ")";
                $db->query($requete);
            }
            //Redirect
            Header("Location: card.php?id=" . $id);
            exit;
        } else {
            $mesg = '<div class="error ui-state-error">' . $langs->trans($product->error) . '</div>';
            $_GET["action"] = "create";
            $_GET["canvas"] = $product->canvas;
            $_GET["type"] = $_POST["type"];
        }
    }
}

// Action mise a jour d'un produit ou service
if ($_POST["action"] == 'update' &&
        $_POST["cancel"] <> $langs->trans("Cancel") &&
        $user->rights->produit->creer) {
    $product = new Product($db);
    if ($product->fetch($_POST["id"])) {
        $product->ref = $_POST["ref"];
        $product->label = $_POST["libelle"];
        $product->description = $_POST["desc"];
        $product->note = $_POST["note"];
        $product->status = $_POST["statut"];
        $product->seuil_stock_alerte = $_POST["seuil_stock_alerte"];
        $product->stock_loc = $_POST["stock_loc"];
        $product->duration_value = $_POST["duration_value"];
        $product->array_options['options_2clause'] = $_POST['clause'];
        $product->duration_unit = $_POST["duration_unit"];
        $product->canvas = $_POST["canvas"];
        $product->weight = $_POST["weight"];
        $product->weight_units = $_POST["weight_units"];
        $product->volume = $_POST["volume"];
        $product->volume_units = $_POST["volume_units"];
        $product->array_options['options_2dureeSav'] = $_REQUEST["set_DURAV"];
        $product->array_options['options_2annexe'] = $_REQUEST["annexe"];
        if ($product->type == 2) {
            $product->array_options['options_2visiteSurSite'] = 0;
            $product->array_options['options_2visiteCur'] = 0;
            $product->array_options['options_2maintenance'] = 0;
            $product->array_options['options_2teleMaintenance'] = 0;
            $product->array_options['options_2teleMaintenanceCur'] = 0;
            $product->array_options['options_2hotline'] = 0;
            $product->array_options['options_2qte'] = 0;
            $product->array_options['options_2isSav'] = 0;
            $product->array_options['options_2reconductionAuto'] = ($_REQUEST['reconductionauto'] . "x" == "x" ? 0 : 1);
            $product->array_options['options_2SLA'] = addslashes($_REQUEST['SLA']);



            if ($_REQUEST['typeProd'] == 'SAV') {
                $product->array_options['options_2isSav'] = 1;
                $product->array_options['options_2dureeVal'] = ($_REQUEST['dureeSAV'] > 0 ? $_REQUEST['dureeSAV'] : 0);
            } else if ($_REQUEST['typeProd'] == 'MNT') {
                $product->array_options['options_2dureeVal'] = ($_REQUEST['dureeMNT'] > 0 ? $_REQUEST['dureeMNT'] : 0);
                $product->array_options['options_2visiteSurSite'] = ($_REQUEST['visite'] <> 0 ? $_REQUEST['visite'] : 0);
                $product->array_options['options_2visiteCur'] = ($_REQUEST['visiteCur'] <> 0 ? $_REQUEST['visiteCur'] : 0);
                $product->array_options['options_2maintenance'] = 1;
                $product->array_options['options_2teleMaintenance'] = $_REQUEST['telemaintenance'];
                $product->array_options['options_2teleMaintenanceCur'] = $_REQUEST['telemaintenanceCur'];
                $product->array_options['options_2hotline'] = $_REQUEST['hotline'];
                $product->array_options['options_2qte'] = ($_REQUEST['qteMNT'] . "x" == "x" ? 0 : $_REQUEST['qteMNT']);
                $product->array_options['options_2qtePerDuree'] = $_REQUEST['qteTktPerDuree'];
                $product->array_options['options_2timePerDuree'] = (intval($_REQUEST['qteTempsPerDureeH']) * 3600 + intval($_REQUEST['qteTempsPerDureeM']) * 60);
            } else if ($_REQUEST['typeProd'] == 'TKT') {
                $product->array_options['options_2dureeVal'] = ($_REQUEST['dureeTKT'] > 0 ? $_REQUEST['dureeTKT'] : 0);
                $product->array_options['options_2qte'] = ($_REQUEST['qteTKT'] . "x" == "x" ? 0 : $_REQUEST['qteTKT']);
            }
        }


        if ($product->check()) {
            if ($product->update($product->id, $user) > 0) {
                if (USE_CAT) {
                    $_GET["action"] = '';
                    $_GET["id"] = $_POST["id"];
                    //Ajoute les parametre extra
                    //1 detecte si y'a des parametres extra
                    require_once(DOL_DOCUMENT_ROOT . '/categories/template.class.php');
                    require_once(DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php');

                    $c = new Categorie($db);
                    $cats = $c->containing($product->id, "product", 0);

                    $template = new Template($db);
                    $arrId = array();

                    if (sizeof($cats) > 0) {
                        $title = $langs->trans("ProductIsInCategories");
                        $var = true;
                        //ajout parents
                        foreach ($cats as $cat => $c) {
                            $c->db = $db;
                            $meres = $c->get_meres();
                            foreach ($meres as $k => $v) {
                                $v->db = $db;
                                $arrId = array_merge($arrId, recursParent($v));
                            }
                        }
                        foreach ($arrId as $k => $v) {
                            $tmpC = new Categorie($db, $v);
                            $addElement = true;
                            foreach ($cats as $k1 => $v1) {
                                if ($v1->id == $v) {
                                    $addElement = false;
                                }
                            }
                            if ($addElement) {
                                $cats[] = $tmpC;
                            }
                        }
                    }


                    if (sizeof($cats) > 0) {
                        $title = $langs->trans("ProductIsInCategories");
                        $extraArr = array();
                        //Liste des templates associes
                        foreach ($cats as $cat) {
                            $tplList = $template->fetch_by_cat($cat->id);
                            if (count($tplList) > 0) {
                                foreach ($tplList as $key => $val) {
                                    $i = 0;
                                    $var = true;
                                    $template->fetch($key);
                                    $template->getKeys();
                                    $template->getValues($product->id);
                                    foreach ($template->keysList as $key1 => $val1) {
                                        $var = !$var;
                                        //2 recupere les parametres
                                        $extraArr[$cat->id][$val1['nom']] = array("value" => $_REQUEST[$val1['nom']], "template_id" => $val1['template_id']);
                                    }
                                }
                            }
                        }
                        //3 ajoute dans la database via template->create / template->update
                        $template->setDatas($product->id, $extraArr, 'product');
                    }
                }
            } else {
                $_GET["action"] = 'edit';
                $_GET["id"] = $_POST["id"];
                $mesg = $product->error;
            }
        } else {
            $_GET["action"] = 'edit';
            $_GET["id"] = $_POST["id"];
            $mesg = $langs->trans("ErrorProductBadRefOrLabel");
        }

        // Produit specifique
        if ($product->canvas <> '' && file_exists('canvas/product.' . $product->canvas . '.class.php')) {
            $class = 'Product' . ucfirst($product->canvas);
            include_once('canvas/product.' . $product->canvas . '.class.php');

            $product = new $class($db);
            if ($product->FetchCanvas($_POST["id"])) {
                $product->UpdateCanvas($_POST);
            }
        }

        //Babel PN
        if (isset($conf->global->MAIN_MODULE_BABELPRIME)) {
            $doInsert = true;
            $requete = "SELECT Prime_CatPN_refid " .
                    "     FROM Babel_PN_products " .
                    "    WHERE Product_refid = " . $_POST['id'];
            $resql = $db->query($requete);
            if ($resql) {
                $res = $db->fetch_object($resql);
                if ("x" . $res->Prime_CatPN_refid != "x") {
                    $doInsert = false;
                }
            }
            $requete = "";
            //Cas 1 setter
            if ($doInsert) {
                $requete = "insert into Babel_PN_products (Product_refid, Prime_CatPN_refid) values (" . $_POST["id"] . "," . $_POST['catPN'] . ")";
            } else {
                $requete = "update Babel_PN_products set  Prime_CatPN_refid = " . $_POST['catPN'] . " WHERE Product_refid = " . $_POST["id"];
            }
            //        print $requete;
            $db->query($requete);
        }
    }
}

// clone d'un produit
if ($_GET["action"] == 'clone' && $user->rights->produit->creer) {
    $db->begin();

    $product = new Product($db);
    $originalId = $_GET["id"];
    if ($product->fetch($_GET["id"]) > 0) {
        $product->ref = "Clone " . $product->ref;
        $product->status = 0;
        $product->id = null;

        if ($product->check()) {
            $id = $product->create($user);
            if ($id > 0) {
                if ($conf->global->CLONE_PRODUCT_ALSO_CLONE_PRODUCT_FOURN) {
                    $product->clone_fournisseurs($originalId, $id);
                }
                if (USE_CAT && $conf->global->CLONE_PRODUCT_ALSO_CLONE_PRODUCT_CAT) {
                    $product->clone_categories($originalId, $id);
                }
                if (USE_CAT && $conf->global->CLONE_PRODUCT_ALSO_CLONE_PRODUCT_CATVALUE && $conf->global->CLONE_PRODUCT_ALSO_CLONE_PRODUCT_CAT) {
                    $product->clone_categories_value($originalId, $id);
                }

                $db->commit();

                if (isset($conf->global->MAIN_MODULE_BABELPRIME)) {

                    $requete = "SELECT Prime_CatPN_refid FROM Babel_PN_products WHERE Product_refid = " . $originalId;
                    //print $requete;
                    $resql = $db->query($requete);
                    if ($resql) {
                        $res = $db->fetch_object($resql);
                        $requete1 = "INSERT INTO Babel_PN_products " .
                                "                (Prime_CatPN_refid, Product_refid) " .
                                "         VALUES ($res->Prime_CatPN_refid,$id)";
                        $db->query($requete1);
                    }

                    $db->close();
                }

                Header("Location: card.php?id=$id");
                exit;
            } else {
                if ($product->error == 'ErrorProductAlreadyExists') {
                    $db->rollback();

                    $_error = 1;
                    $_GET["action"] = "";

                    $mesg = '<div class="error ui-state-error">' . $langs->trans("ErrorProductAlreadyExists", $product->ref) . '</div>';
                    //dol_print_error($product->db);
                } else {
                    $db->rollback();
                    dol_print_error($product->db);
                }
            }
        }
    } else {
        $db->rollback();
        dol_print_error($product->db);
    }
}



// cloneToProduct
if ($_GET["action"] == 'cloneToProduct' && $user->rights->produit->creer) {
    $db->begin();

    $product = new Product($db);
    $originalId = $_GET["id"];
    if ($product->fetch($_GET["id"]) > 0) {
        $product->ref = "Clone " . $product->ref;
        $product->status = 0;
        $product->id = null;
        $product->type = 0;

        if ($product->check()) {
            $id = $product->create($user);
            if ($id > 0) {
                if ($conf->global->CLONE_PRODUCT_ALSO_CLONE_PRODUCT_FOURN) {
                    $product->clone_fournisseurs($originalId, $id);
                }
                if (USE_CAT && $conf->global->CLONE_PRODUCT_ALSO_CLONE_PRODUCT_CAT) {
                    $product->clone_categories($originalId, $id);
                }
                if (USE_CAT && $conf->global->CLONE_PRODUCT_ALSO_CLONE_PRODUCT_CATVALUE && $conf->global->CLONE_PRODUCT_ALSO_CLONE_PRODUCT_CAT) {
                    $product->clone_categories_value($originalId, $id);
                }

                $db->commit();

                if (isset($conf->global->MAIN_MODULE_BABELPRIME)) {

                    $requete = "SELECT Prime_CatPN_refid FROM Babel_PN_products WHERE Product_refid = " . $originalId;
                    //print $requete;
                    $resql = $db->query($requete);
                    if ($resql) {
                        $res = $db->fetch_object($resql);
                        $requete1 = "INSERT INTO Babel_PN_products " .
                                "                (Prime_CatPN_refid, Product_refid) " .
                                "         VALUES ($res->Prime_CatPN_refid,$id)";
                        $db->query($requete1);
                    }

                    $db->close();
                }

                Header("Location: card.php?id=$id");
                exit;
            } else {
                if ($product->error == 'ErrorProductAlreadyExists') {
                    $db->rollback();

                    $_error = 1;
                    $_GET["action"] = "";

                    $mesg = '<div class="error ui-state-error">' . $langs->trans("ErrorProductAlreadyExists", $product->ref) . '</div>';
                    //dol_print_error($product->db);
                } else {
                    $db->rollback();
                    dol_print_error($product->db);
                }
            }
        }
    } else {
        $db->rollback();
        dol_print_error($product->db);
    }
}
// cloneToLocation
if ($_GET["action"] == 'cloneToLocation' && $user->rights->produit->creer) {
    $db->begin();

    $product = new Product($db);
    $originalId = $_GET["id"];
    if ($product->fetch($_GET["id"]) > 0) {
        $product->ref = "Loc " . $product->ref;
        $product->label = "Location - " . $product->label;
        $product->status = 0;
        $product->id = null;
        $product->type = 4;

        if ($product->check()) {
            $id = $product->create($user);
            if ($id > 0) {
                if ($conf->global->CLONE_PRODUCT_ALSO_CLONE_PRODUCT_FOURN) {
                    $product->clone_fournisseurs($originalId, $id);
                }
                if (USE_CAT && $conf->global->CLONE_PRODUCT_ALSO_CLONE_PRODUCT_CAT) {
                    $product->clone_categories($originalId, $id);
                }
                if (USE_CAT && $conf->global->CLONE_PRODUCT_ALSO_CLONE_PRODUCT_CATVALUE && $conf->global->CLONE_PRODUCT_ALSO_CLONE_PRODUCT_CAT) {
                    $product->clone_categories_value($originalId, $id);
                }

                $db->commit();

                if (isset($conf->global->MAIN_MODULE_BABELPRIME)) {

                    $requete = "SELECT Prime_CatPN_refid FROM Babel_PN_products WHERE Product_refid = " . $originalId;
                    //print $requete;
                    $resql = $db->query($requete);
                    if ($resql) {
                        $res = $db->fetch_object($resql);
                        $requete1 = "INSERT INTO Babel_PN_products " .
                                "                (Prime_CatPN_refid, Product_refid) " .
                                "         VALUES ($res->Prime_CatPN_refid,$id)";
                        $db->query($requete1);
                    }

                    $db->close();
                }

                Header("Location: card.php?id=$id");
                exit;
            } else {
                if ($product->error == 'ErrorProductAlreadyExists') {
                    $db->rollback();

                    $_error = 1;
                    $_GET["action"] = "";

                    $mesg = '<div class="error ui-state-error">' . $langs->trans("ErrorProductAlreadyExists", $product->ref) . '</div>';
                    //dol_print_error($product->db);
                } else {
                    $db->rollback();
                    dol_print_error($product->db);
                }
            }
        }
    } else {
        $db->rollback();
        dol_print_error($product->db);
    }
}

/*
 * Suppression d'un produit/service pas encore affect
 */
if ($_REQUEST['action'] == 'confirm_delete' && $_REQUEST['confirm'] == 'yes' && $user->rights->produit->supprimer) {
    $product = new Product($db);
    $product->fetch($_GET['id']);
    $result = $product->delete($_GET['id']);
    if ($result >= 0) {
        Header('Location: ' . DOL_URL_ROOT . '/product/index.php?delprod=' . $product->ref);
        exit;
    } else {
        print_r($product->errors);
        $reload = 0;
        $_GET['action'] = '';
    }
}


/*
 * Ajout du produit dans une propal
 */
if ($_POST["action"] == 'addinpropal') {
    $propal = New Propal($db);
    $result = $propal->fetch($_POST["propalid"]);
    if ($result <= 0) {
        dol_print_error($db, $propal->error);
        exit;
    }

    $soc = new Societe($db);
    $result = $soc->fetch($propal->socid);
    if ($result <= 0) {
        dol_print_error($db, $soc->error);
        exit;
    }

    $prod = new Product($db);
    $result = $prod->fetch($_GET['id']);
    if ($result <= 0) {
        dol_print_error($db, $prod->error);
        exit;
    }

    $desc = $prod->description;
    $tva_tx = get_default_tva($mysoc, $soc, $prod->tva_tx);

    $price_base_type = 'HT';

    // multiprix
    if (isset($conf->global->PRODUIT_MULTIPRICES) && $conf->global->PRODUIT_MULTIPRICES == 1) {
        $pu_ht = $prod->multiprices[$soc->price_level];
        $pu_ttc = $prod->multiprices_ttc[$soc->price_level];
        $price_base_type = $prod->multiprices_base_type[$soc->price_level];
    } else {
        $pu_ht = $prod->price;
        $pu_ttc = $prod->price_ttc;
        $price_base_type = $prod->price_base_type;
    }

    // On reevalue prix selon taux tva car taux tva transaction peut etre different
    // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
    if ($tva_tx != $prod->tva_tx) {
        if ($price_base_type != 'HT') {
            $pu_ht = price2num($pu_ttc / (1 + ($tva_tx / 100)), 'MU');
        } else {
            $pu_ttc = price2num($pu_ht * (1 + ($tva_tx / 100)), 'MU');
        }
    }

    $result = $propal->addline($propal->id, $desc, $pu_ht, $_POST["qty"], $tva_tx, $prod->id, $_POST["remise_percent"], $price_base_type, $pu_ttc
    );
    if ($result > 0) {
        Header("Location: " . DOL_URL_ROOT . "/comm/propal.php?propalid=" . $propal->id);
        return;
    }

    $mesg = $langs->trans("ErrorUnknown") . ": $result";
}

/*
 * Ajout du produit dans une commande
 */
if ($_POST["action"] == 'addincommande') {
    $commande = new Commande($db);
    $result = $commande->fetch($_POST["commandeid"]);
    if ($result <= 0) {
        dol_print_error($db, $commande->error);
        exit;
    }

    $soc = new Societe($db);
    $result = $soc->fetch($commande->socid);
    if ($result <= 0) {
        dol_print_error($db, $soc->error);
        exit;
    }

    $prod = new Product($db);
    $result = $prod->fetch($_GET['id']);
    if ($result <= 0) {
        dol_print_error($db, $prod->error);
        exit;
    }

    $desc = $prod->description;
    $tva_tx = get_default_tva($mysoc, $soc, $prod->tva_tx);

    // multiprix
    if (isset($conf->global->PRODUIT_MULTIPRICES) && $conf->global->PRODUIT_MULTIPRICES == 1) {
        $pu_ht = $prod->multiprices[$soc->price_level];
        $pu_ttc = $prod->multiprices_ttc[$soc->price_level];
        $price_base_type = $prod->multiprices_base_type[$soc->price_level];
    } else {
        $pu_ht = $prod->price;
        $pu_ttc = $prod->price_ttc;
        $price_base_type = $prod->price_base_type;
    }

    // On reevalue prix selon taux tva car taux tva transaction peut etre different
    // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
    if ($tva_tx != $prod->tva_tx) {
        if ($price_base_type != 'HT') {
            $pu_ht = price2num($pu_ttc / (1 + ($tva_tx / 100)), 'MU');
        } else {
            $pu_ttc = price2num($pu_ht * (1 + ($tva_tx / 100)), 'MU');
        }
    }

    $result = $commande->addline($commande->id, $desc, $pu_ht, $_POST["qty"], $tva_tx, $prod->id, $_POST["remise_percent"], '', '', //Todo: voir si fk_remise_except est encore valable car n'apparait plus dans les propales
            $price_base_type, $pu_ttc
    );

    if ($result > 0) {
        Header("Location: " . DOL_URL_ROOT . "/commande/card.php?id=" . $commande->id);
        exit;
    }
}

/*
 * Ajout du produit dans une facture
 */
if ($_POST["action"] == 'addinfacture' && $user->rights->facture->creer) {
    $facture = New Facture($db);
    $result = $facture->fetch($_POST["factureid"]);
    if ($result <= 0) {
        dol_print_error($db, $facture->error);
        exit;
    }

    $soc = new Societe($db);
    $soc->fetch($facture->socid);
    if ($result <= 0) {
        dol_print_error($db, $soc->error);
        exit;
    }

    $prod = new Product($db);
    $result = $prod->fetch($_GET["id"]);
    if ($result <= 0) {
        dol_print_error($db, $prod->error);
        exit;
    }

    $desc = $prod->description;
    $tva_tx = get_default_tva($mysoc, $soc, $prod->tva_tx);

    // multiprix
    if (isset($conf->global->PRODUIT_MULTIPRICES) && $conf->global->PRODUIT_MULTIPRICES == 1) {
        $pu_ht = $prod->multiprices[$soc->price_level];
        $pu_ttc = $prod->multiprices_ttc[$soc->price_level];
        $price_base_type = $prod->multiprices_base_type[$soc->price_level];
    } else {
        $pu_ht = $prod->price;
        $pu_ttc = $prod->price_ttc;
        $price_base_type = $prod->price_base_type;
    }

    // On reevalue prix selon taux tva car taux tva transaction peut etre different
    // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
    if ($tva_tx != $prod->tva_tx) {
        if ($price_base_type != 'HT') {
            $pu_ht = price2num($pu_ttc / (1 + ($tva_tx / 100)), 'MU');
        } else {
            $pu_ttc = price2num($pu_ht * (1 + ($tva_tx / 100)), 'MU');
        }
    }

    $result = $facture->addline($facture->id, $desc, $pu_ht, $_POST["qty"], $tva_tx, $prod->id, $_POST["remise_percent"], '', '', '', '', '', $price_base_type, $pu_ttc
    );

    if ($result > 0) {
        Header("Location: " . DOL_URL_ROOT . "/compta/facture.php?facid=" . $facture->id);
        exit;
    }
}

if (isset($_POST["cancel"]) && $_POST["cancel"] == $langs->trans("Cancel")) {
    $action = '';
    Header("Location: card.php?id=" . $_POST["id"]);
    exit;
}



$html = new Form($db);
$formproduct = new FormProduct($db);

/*
 * Fiche creation du produit
 */
if ($_GET["action"] == 'create' && $user->rights->produit->creer) {
    if ($conf->global->PRODUCT_CANVAS_ABILITY) {
        if (!isset($product)) {
            $filecanvas = DOL_DOCUMENT_ROOT . '/product/canvas/product.' . $_GET["canvas"] . '.class.php';
            if ($_GET["canvas"] && file_exists($filecanvas)) {
                $class = 'Product' . ucfirst($_GET["canvas"]);
                include_once($filecanvas);

                $product = new $class($db, 0, $user);
            } else {
                $product = new Product($db);
            }
        }

        $product->assign_smarty_values($smarty, 'create');

        if ($_error == 1) {
            $product = $e_product;
        }
    }

    llxHeader("", "", $langs->trans("NewProduct" . $product->type));

    if ($mesg)
        print $mesg . "\n";

    if (!$conf->global->PRODUCT_CANVAS_ABILITY || !$_GET["canvas"]) {
        print '<form action="card.php" method="post">';
        print '<input type="hidden" name="action" value="add">';
        print '<input type="hidden" name="type" value="' . $_GET["type"] . '">' . "\n";

        if ($_GET["type"] == 1)
            $title = $langs->trans("NewService");
        else if ($_GET["type"] == 2)
            $title = $langs->trans("NewContrat");
        else if ($_GET["type"] == 3)
            $title = $langs->trans("NewDeplacement");
        $title = $langs->trans("NewProduct");
        print_fiche_titre($title);

        print '<table class="border" cellpadding=15 width="100%">';
        print '<tr>';
        print '<th class="ui-widget-header ui-state-default" width="20%">' . $langs->trans("Ref") . '</th>
               <td class="ui-widget-content"><input name="ref" size="40" maxlength="32" value="' . (is_object($product) ? $product->getNomUrl(1) : "") . '">';
        if ($_error == 1) {
            print $langs->trans("RefAlreadyExists");
        }
        print '</td></tr>';

        // Label
        print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Label") . '</th>
                   <td class="ui-widget-content" ><input name="libelle" size="40" value="' . (is_object($product) ? $product->label : "") . '"></td></tr>';

        // Status
        print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Status") . '</th>
                   <td class="ui-widget-content">';
        $statutarray = array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
        print $html->selectarray('statut', $statutarray, $_POST["statut"]);
        print '</td></tr>';

        // Stock min level
        if ($_GET["type"] == 0 && $conf->stock->enabled) {
            print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("StockLimit") . '</th>
                       <td class="ui-widget-content">';
            print '<input name="seuil_stock_alerte" size="4" value="0">';
            print '</td></tr>';
        } else {
            print '<input name="seuil_stock_alerte" type="hidden" value="0">';
        }

        // Description (utilise dans facture, propale...)
        print '<tr><th class="ui-widget-header ui-state-default"  valign="top">' . $langs->trans("Description") . '</th>
                   <td class="ui-widget-content">';

        if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC) {
            require_once(DOL_DOCUMENT_ROOT . "/lib/doleditor.class.php");
            $doleditor = new DolEditor('desc', '', 160, 'dol_notes', '', false);
            $doleditor->Create();
        } else {
            print '<textarea name="desc" rows="4" cols="90">';
            print '</textarea>';
        }

        print "</td></tr>";

        if ($_GET["type"] == 1) {
            print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Duration") . '</th>
                       <td class="ui-widget-content"><input name="duration_value" size="6" maxlength="5" value="' . $product->array_options['options_2dureeVal'] . '"> &nbsp;';
            print '<input name="duration_unit" type="radio" value="h">' . $langs->trans("Hour") . '&nbsp;';
            print '<input name="duration_unit" type="radio" value="d">' . $langs->trans("Day") . '&nbsp;';
            print '<input name="duration_unit" type="radio" value="w">' . $langs->trans("Week") . '&nbsp;';
            print '<input name="duration_unit" type="radio" value="m">' . $langs->trans("Month") . '&nbsp;';
            print '<input name="duration_unit" type="radio" value="y">' . $langs->trans("Year") . '&nbsp;';
            print '</td></tr>';
        } else if ($_GET['type'] == 2) {
//                print "<tr><th class='ui-widget-header ui-state-default'>Type de contrat<td class='ui-widget-content' colspan=1 align=center>";
//                print "<div id='tabs' style='width:98%'>";
//                print "<ul><li><span><a href='#MNT'>Maintenance</a></span></li>";
//                print "    <li><span><a href='#SAV'>SAV</a></span></li>";
//                print "    <li><span><a href='#TKT'>Ticket</a></span></li>";
//                print "</ul>";
//                print "<div id='MNT'>";
//                print "<table width=100% cellpadding=10>";
//                print '<tr><th class="ui-widget-header ui-state-default" >'.$langs->trans("Dur&eacute;e de validit&eacute; (en mois)").'</th>
//                           <td class="ui-widget-content"><input type="text" name="dureeMNT" value="'.$product->array_options['options_2dureeVal'].'">';
//                //Nombre de visite mensuel
//                print '<tr><th class="ui-widget-header ui-state-default" >'.$langs->trans("Visite sur site (par an)").'</th>
//                           <td class="ui-widget-content"><input type="text" name="visite" value="'.$product->VisiteSurSite.'">';
//                //Télémaintenance
//                print '<tr><th class="ui-widget-header ui-state-default" >'.$langs->trans("T&eacute;l&eacute;-Maintenance").'</th>
//                           <td class="ui-widget-content"><input type="checkbox" '.($product->array_options['options_2teleMaintenance']>0?'checked':'').' name="telemaintenance">';
//                //Accès à la hotline
//                print '<tr><th class="ui-widget-header ui-state-default" >'.$langs->trans("Hotline").'</th>
//                           <td class="ui-widget-content"><input type="checkbox" '.($product->array_options['options_2hotline']>0?'checked':'').' name="hotline">';
//                //Tkt
//                print '<tr><th class="ui-widget-header ui-state-default" >'.$langs->trans("Qt&eacute; de tickets <br/><em><small>(<b>0</b> Sans ticket, <b>-1</b> Illimit&eacute;)</small></em>").'</th>
//                           <td class="ui-widget-content"><input type="text" value="'.$product->array_options['options_2qte'].'" name="qteMNT">';
//                //Valo Ticket
//                print '<tr><th class="ui-widget-header ui-state-default" >'.$langs->trans("Dur&eacute;e par ticket<br/><em><small>(<b>-1</b>sans d&eacute;compte de temps)</small></em>").'</th>
//                           <td class="ui-widget-content"><input type="text" value="'.$product->array_options['options_2qtePerDuree'].'" name="qteTktPerDuree"> ticket pour <input type="text" size=4 value="'.$product->array_options['options_2timePerDuree']H.'" name="qteTempsPerDureeH"> h : <input type="text" size=4 value="'.$product->array_options['options_2timePerDuree']M.'" name="qteTempsPerDureeM"> min';
//
//                print "</table>";
//                print "</div>";
//                print "<div id='SAV'>";
//                //Durée de validité
//                print "<table width=100% cellpadding=10>";
//                print '<tr><th class="ui-widget-header ui-state-default" >'.$langs->trans("Dur&eacute;e de l'extension (en mois)").'</th>
//                           <td class="ui-widget-content"><input type="text" name="dureeSAV" value="'.$product->array_options['options_2dureeVal'].'">';
//                print "</table>";
//                print "</div>";
//                print "<div id='TKT'>";
//                print "<table width=100% cellpadding=10>";
//                print '<tr><th class="ui-widget-header ui-state-default" >'.$langs->trans("Dur&eacute;e de validit&eacute; (en mois)").'</th>
//                           <td class="ui-widget-content"><input type="text" name="dureeTKT" value="'.$product->array_options['options_2dureeVal'].'">';
//                //Tkt
//                print '<tr><th class="ui-widget-header ui-state-default" >'.$langs->trans("Qt&eacute; de tickets<br/><em><small>(<b>0</b> Sans ticket, <b>-1</b> Illimit&eacute;)</small></em>").'</th>
//                           <td class="ui-widget-content"><input type="text" value="'.$product->array_options['options_2qte'].'" name="qteTKT">';
//                print "</table>";
//                print "</div>";
//                print "</div>";
//                print "</tr>";
//                //Reconduction
//                print '<tr><th class="ui-widget-header ui-state-default" >'.$langs->trans("Reconduction automatique").'</th>
//                           <td class="ui-widget-content"><input type="checkbox" '.($product->array_options['options_2reconductionAuto']>0?'checked':'').' name="reconductionauto">';
//                //Le SLA ou « Contrat de niveau de service » : référence au temps de délivrance et/ou à la performance (du service) tel que déﬁni dans le contrat.
//                print '<tr><th class="ui-widget-header ui-state-default" >'.$langs->trans("SLA").'</th>
//                           <td class="ui-widget-content"><input type="text" name="SLA" value="'.$product->array_options['options_2SLA'].'">';
//print <<<EOF
//<script>
//jQuery(document).ready(function(){
//    jQuery('#tabs').tabs({
//        spinner: 'Chargement ...',
//        cache: true,
//        fx: { height: 'toggle', opacity: "toggle" },
//        select: function(a,b){
//            //console.log();
//            jQuery('#typeProd').val(b.panel.id);
//        },
//EOF;
//        print 'selected: '.($product->typeContrat == 'SAV'?1:($product->typeContrat == 'MNT'?0:2)).' ';
//print <<<EOF
//    });
//});
//</script>
//EOF;
//                print "<input id='typeProd' type='hidden' name='typeProd' value='".($product->typeContrat."x"=='x'?'MNT':$product->typeContrat)."'> ";
        } else if ($_GET['type'] == 3) {
            
        } else if ($_GET['type'] == 4) {
            // Le poids et le volume ne concerne que les produits et pas les services
            print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Weight") . '</th>
                       <td class="ui-widget-content">';
            print '<input style="margin: 3px 10px; float: left;" name="weight" size="4" value="">';
            print $formproduct->select_measuring_units("weight_units", "weight");
            print '</td></tr>';
            print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Volume") . '</th>
                       <td class="ui-widget-content">';
            print '<input style="margin: 3px 10px; float: left;" name="volume" size="4" value="">';
            print $formproduct->select_measuring_units("volume_units", "volume");
            print '</td></tr>';


            print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Duration") . '</th>
                       <td class="ui-widget-content"><input name="duration_value" size="6" maxlength="5" value="' . $product->array_options['options_2dureeVal'] . '"> &nbsp;';
            print '&nbsp; ';
            print '<input name="duration_unit" type="radio" value="h"' . ($product->duration_unit == 'h' ? ' checked' : '') . '>' . $langs->trans("Hour");
            print '&nbsp; ';
            print '<input name="duration_unit" type="radio" value="d"' . ($product->duration_unit == 'd' ? ' checked' : '') . '>' . $langs->trans("Day");
            print '&nbsp; ';
            print '<input name="duration_unit" type="radio" value="w"' . ($product->duration_unit == 'w' ? ' checked' : '') . '>' . $langs->trans("Week");
            print '&nbsp; ';
            print '<input name="duration_unit" type="radio" value="m"' . ($product->duration_unit == 'm' ? ' checked' : '') . '>' . $langs->trans("Month");
            print '&nbsp; ';
            print '<input name="duration_unit" type="radio" value="y"' . ($product->duration_unit == 'y' ? ' checked' : '') . '>' . $langs->trans("Year");
            print '</td></tr>';
        } else {
            // Le poids et le volume ne concerne que les produits et pas les services
            print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Weight") . '</th>
                       <td class="ui-widget-content">';
            print '<input style="margin: 3px 10px; float: left;" name="weight" size="4" value="">';
            print $formproduct->select_measuring_units("weight_units", "weight");
            print '</td></tr>';
            print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Volume") . '</th>
                       <td class="ui-widget-content">';
            print '<input style="margin: 3px 10px; float: left;" name="volume" size="4" value="">';
            print $formproduct->select_measuring_units("volume_units", "volume");
            print '</td></tr>';

            print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("durSAV") . '</th>
                       <td class="ui-widget-content">';
//Duree de SAV
            print "<input type='hidden' id='set_DURAV' name='set_DURAV' value='" . $conf->global->PRODUIT_DEFAULT_DUR_SAV . "'>";
            print "<span>
                          <span id='moinsSAV' style='cursor: pointer; float: left;' class='ui-icon ui-icon-circle-minus'></span>
                          <span style='float:left;'>&nbsp;&nbsp;&nbsp;</span>
                          <span style='font-weight:900; float: left;' id='dfltSAV'>" . ($conf->global->PRODUIT_DEFAULT_DUR_SAV > 0 ? $conf->global->PRODUIT_DEFAULT_DUR_SAV : 0) . "</span>
                          <span style='float: left;' >&nbsp;mois&nbsp;&nbsp;&nbsp;</span>
                          <span style='cursor: pointer; float: left;' id='plusSAV' class='ui-icon ui-icon-circle-plus'></span>
            </span>";
            print "<script>";
            print <<<EOF

jQuery(document).ready(function(){
    jQuery('#moinsSAV').click(function(){
        var cnt = parseInt(jQuery('#set_DURAV').val());
        if (isNaN(cnt)) cnt=0;
        cnt --;
        if (cnt<-1) cnt=-1;
        jQuery('#set_DURAV').val(cnt);
        jQuery('#dfltSAV').text(cnt);
    });
    jQuery('#plusSAV').click(function(){
        var cnt = parseInt(jQuery('#set_DURAV').val());
        if (isNaN(cnt)) cnt=0;
        cnt ++;
        if (cnt<-1) cnt=-1;
        jQuery('#set_DURAV').val(cnt);
        jQuery('#dfltSAV').text(cnt);
    });
});

EOF;
            print "</script>";

            print '</td></tr>';
        }

//        // Clause (utilise dans contrat FI / DI)
//        print '<tr><th class="ui-widget-header ui-state-default"  valign="top">'.$langs->trans("Clause").'</th>
//                   <td class="ui-widget-content">';
//
//        if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC)
//        {
//            require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
//            $doleditor=new DolEditor('clause','',160,'dol_notes','',false);
//            $doleditor->Create();
//        } else {
//            print '<textarea name="clause" rows="4" cols="90">';
//            print '</textarea>';
//        }
//
//        print "</td></tr>";
        // Note (invisible sur facture, propales...)
        print '<tr><th valign="top" class="ui-widget-header ui-state-default" >' . $langs->trans("NoteNotVisibleOnBill") . '</th>
                   <td class="ui-widget-content">';
        if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC) {
            require_once(DOL_DOCUMENT_ROOT . "/lib/doleditor.class.php");
            $doleditor = new DolEditor('note', '', 180, 'dol_notes', '', false);
            $doleditor->Create();
        } else {
            print '<textarea name="note" rows="8" cols="70">';
            print '</textarea>';
        }
        print "</td></tr>";



//Babel PN
        if (isset($conf->global->MAIN_MODULE_BABELPRIME)) {

            print '<tr><th class="ui-widget-header ui-state-default"  valign="top">' . $langs->trans("Catégorie PN") . '</th>
                       <td class="ui-widget-content">';
            print '<SELECT name="catPN">';
            $requete = "SELECT * FROM Babel_Prime_CatPN order by name";
            $resql = $db->query($requete);
            if ($resql) {
                while ($res = $db->fetch_object($resql)) {
                    print "<OPTION value='" . $res->id . "'>" . $res->name . "</OPTION>";
                }
            }
            print "</SELECT>";
            print "</td></tr>";
        }
//Fin Babel PN
        print '</table>';

        print '<br>';

        print '<table class="border" width="100%">';
        if ($_GET['type'] == 4) {
            print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Price") . '</th>';
            print '<td class="ui-widget-content"><input style="float:left; margin:3px 10px;" name="Locprice" size="10" value="' . $product->price_loc . '">';
            print $html->selectPriceBaseType($product->price_base_type, "price_base_type");
            print '</td></tr>';
        } else {

            if (isset($conf->global->PRODUIT_MULTIPRICES) && $conf->global->PRODUIT_MULTIPRICES == 1) {
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("SellingPrice") . ' 1</th>';
                print '<td class="ui-widget-content"><input name="price" size="10" value="' . $product->price . '">';
                print $html->selectPriceBaseType($product->price_base_type, "price_base_type");
                print '</td></tr>';
                for ($i = 2; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++) {
                    print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("SellingPrice") . ' ' . $i . '</th>';
                    print '<td class="ui-widget-content"><input name="price_' . $i . '" size="10" value="' . $product->multiprices["$i"] . '">';
                    print $html->selectPriceBaseType($product->multiprices_base_type["$i"], "multiprices_base_type_" . $i);
                    print '</td></tr>';
                }
            }
            // PRIX
            else {
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("SellingPrice") . '</th>';
                print '<td class="ui-widget-content"><input style="float:left; margin:3px 10px;" name="price" size="10" value="' . $product->price . '">';
                print $html->selectPriceBaseType($product->price_base_type, "price_base_type");
                print '</td></tr>';
            }
        }
        // VAT
        print '<tr><th class="ui-widget-header ui-state-default"  width="20%">' . $langs->trans("VATRate") . '</th>
                   <td class="ui-widget-content" >';
//        $html->select_tva("tva_tx", $conf->defaulttx, $mysoc, '');
        print $form->load_tva("tva_tx",-1,$mysoc,'');
        print '</td></tr>';

        print '</table>';

        print '<br>';

        print '<table class="border" width="100%">';
        print '<tr><td class="ui-widget-header ui-state-default"  colspan="2" align="center"><input type="submit" class="button" value="' . $langs->trans("Create") . '"></td></tr>';
        print '</table>';

        print '</form>';
    } else {
        // On assigne les valeurs meme en creation car elles sont definies si
        // on revient en erreur
        //
        $smarty->template_dir = DOL_DOCUMENT_ROOT . '/product/canvas/' . $_GET["canvas"] . '/';
        $tvaarray = load_tva($db, "tva_tx", $conf->defaulttx, $mysoc, '');
        $smarty->assign('tva_taux_value', $tvaarray['value']);
        $smarty->assign('tva_taux_libelle', $tvaarray['label']);
        $smarty->display($_GET["canvas"] . '-create.tpl');
    }
}

/**
 *
 * Fiche produit
 *
 */
if ($_GET["id"] || $_GET["ref"]) {
    $product = new Product($db);

    if (isset($_GET["ref"]) && $_GET["ref"]) {
        $result = $product->fetch('', $_GET["ref"]);
        $_GET["id"] = $product->id;
    } elseif ($_GET["id"]) {
        $result = $product->fetch($_GET["id"]);
    }
    $product->fetch_optionals($product->id);

    // Gestion des produits specifiques
    $product->canvas = '';
    if (isset($conf->global->PRODUCT_CANVAS_ABILITY) && $conf->global->PRODUCT_CANVAS_ABILITY) {
        if ($product->canvas <> '' && file_exists('canvas/product.' . $product->canvas . '.class.php')) {
            $class = 'Product' . ucfirst($product->canvas);
            include_once('canvas/product.' . $product->canvas . '.class.php');
            $product = new $class($db);

            $result = $product->FetchCanvas($_GET["id"], '', $_GET["action"]);

            $smarty->template_dir = DOL_DOCUMENT_ROOT . '/product/canvas/' . $product->canvas . '/';

            $product->assign_smarty_values($smarty, $_GET["action"]);
        }
    }
    $js = "";
    $jqueryuipath = DOL_URL_ROOT . '/Synopsis_Common/jquery/ui/';
    $js .="<script>jQuery(document).ready(function(){
            jQuery.datepicker.setDefaults(jQuery.extend({showMonthAfterYear: false,
            dateFormat: 'dd/mm/yy',
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true,
            buttonImage: 'cal.png',
            buttonImageOnly: true,
            showTime: true,
            duration: '',
            constrainInput: false,}, jQuery.datepicker.regional['fr']));

            jQuery('.datepicker').datepicker();
        });

        </script>";
    $js .= "<script src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/easySlider1.5.js' type='text/javascript'></script>";
    $js .= "<script src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/colorpicker.js' type='text/javascript'></script>";
    $js .= '<link rel="stylesheet" type="text/css" title="default" href="' . DOL_URL_ROOT . '/Synopsis_Common/css/colorpicker.css"></link>';

    $js .= <<<EOF
<script>
jQuery(document).ready(function(){
    jQuery("#slider").easySlider();
});
</script>
<style>

#colorSelector {
    position: relative;
    width: 36px;
    height: 36px;
    background: url(colorpicker/select.png);
}
#colorSelector div {
    position: absolute;
    width: 30px;
    height: 30px;
    background: url(colorpicker/select.png) center;
}
#colorSelector2 {
    position: absolute;
    top: 0;
    left: 0;
    width: 36px;
    height: 36px;
    background: url(colorpicker/select2.png);
}
#colorSelector2 div {
    position: absolute;
    top: 4px;
    left: 4px;
    width: 28px;
    height: 28px;
    background: url(colorpicker/select2.png) center;
}
#colorpickerHolder2 {
    top: 32px;
    left: 0;
    width: 356px;
    height: 0;
    overflow: hidden;
    position: absolute;
}
#colorpickerHolder2 .colorpicker {
    background-image: url(colorpicker/custom_background.png);
    position: absolute;
    bottom: 0;
    left: 0;
}
#colorpickerHolder2 .colorpicker_hue div {
    background-image: url(colorpicker/custom_indic.gif);
}
#colorpickerHolder2 .colorpicker_hex {
    background-image: url(colorpicker/custom_hex.png);
}
#colorpickerHolder2 .colorpicker_rgb_r {
    background-image: url(colorpicker/custom_rgb_r.png);
}
#colorpickerHolder2 .colorpicker_rgb_g {
    background-image: url(colorpicker/custom_rgb_g.png);
}
#colorpickerHolder2 .colorpicker_rgb_b {
    background-image: url(colorpicker/custom_rgb_b.png);
}
#colorpickerHolder2 .colorpicker_hsb_s {
    background-image: url(colorpicker/custom_hsb_s.png);
    display: none;
}
#colorpickerHolder2 .colorpicker_hsb_h {
    background-image: url(colorpicker/custom_hsb_h.png);
    display: none;
}
#colorpickerHolder2 .colorpicker_hsb_b {
    background-image: url(colorpicker/custom_hsb_b.png);
    display: none;
}
#colorpickerHolder2 .colorpicker_submit {
    background-image: url(colorpicker/custom_submit.png);
}
#colorpickerHolder2 .colorpicker input {
    color: #778398;
}
#customWidget {
    position: relative;
    height: 36px;
}

#slider{
    max-height: 155px;
}
#slider ul, #slider li{
    margin:0;
    padding:0;
    list-style:none;
    }
#slider li{
    width:160px;
    height:155px;
    overflow:hidden;
    }
#prevBtn, #nextBtn{
    display:block;
    width:30px;
    height:77px;
    position:relative;
    left:-30px;
    top:-200px;
    }
#nextBtn{
    left:95px;
    }
#prevBtn{
    left: -95px;
    top: -125px;
}
#prevBtn a, #nextBtn a{
    display:block;
    width:30px;
    height:77px;
EOF;

    $js .= 'background:url(' . DOL_URL_ROOT . '/Synopsis_Common/css/images/btn_prev.gif) no-repeat 0 0;';
    $js .= <<<EOF
    }
#nextBtn a{
EOF;
    $js .= 'background:url(' . DOL_URL_ROOT . '/Synopsis_Common/css/images/btn_next.gif) no-repeat 0 0;';
    $js .= <<<EOF
}
</style>
EOF;

    launchRunningProcess($db, 'Product', $_GET['id']);

    llxHeader($js, "", $langs->trans("CardProduct" . $product->type));
    if ($result) {
        if ($_GET["action"] <> 'edit') {
            $head = product_prepare_head($product, $user);
            $titre = $langs->trans("CardProduct" . $product->type);
            dol_fiche_head($head, 'card', $titre);
            // Confirmation de la suppression de la facture
            if ($_GET["action"] == 'delete') {
                $html = new Form($db);
                $html->form_confirm("card.php?id=" . $product->id, $langs->trans("DeleteProduct"), $langs->trans("ConfirmDeleteProduct"), "confirm_delete");
                print "<br />\n";
            }

            print($mesg);
        }
        if ($_GET["action"] <> 'edit' && $product->canvas <> '') {
            /*
             *  Smarty en mode visu
             */
            $smarty->assign('fiche_cursor_prev', $previous_ref);
            $smarty->assign('fiche_cursor_next', $next_ref);

            // Photo
            //$nbphoto=$product->show_photos($conf->produit->dir_output,1,1,0);

            $smarty->display($product->canvas . '-view.tpl');
        }

        if ($_GET["action"] <> 'edit' && $product->canvas == '') {
            // En mode visu
            print '<table  class="border" cellpadding=15 width="100%"><tr>';

            // Reference
            print '<th  class="ui-state-default ui-widget-header"width="15%">' . $langs->trans("Ref") . '</td>
                     <td  class="ui-widget-content" >';
            print $html->showrefnav($product, 'ref', '', 1, 'ref');
            print '</td>';

            $nblignes = 6;
            if ($product->isproduct() && $conf->stock->enabled)
                $nblignes++;
            if ($product->isservice())
                $nblignes++;
            if ($product->is_photo_available($conf->produit->dir_output)) {
                // Photo
                print '<td valign="middle" width=25% align="center"  class="ui-widget-content" rowspan="' . $nblignes . '">';
                $nbphoto = $product->show_photos($conf->produit->dir_output, 1, 1, 0);
                print '</td>';
            }
            print '</tr>';

            // Libelle
            print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans("Label") . '</td><td class="ui-widget-content">' . $product->label . '</td></tr>';

            if (isset($conf->global->MAIN_MODULE_SYNOPSISCONTRAT)) {
                $sql = "SELECT id, modeleName FROM " . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf WHERE id='" . $product->array_options['options_2annexe'] . "'";
                $resql = $db->query($sql);
                print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans("Annexe") . '</td><td class="ui-widget-content">';
                if ($db->num_rows($resql)) {
                    $res = $db->fetch_object($resql);
                    print $res->modeleName;
                }
                print '</td></tr>';
            }
            if (0 && $product->islocation()) {
                print '<tr><th class="ui-state-default ui-widget-header" >' . $langs->trans("Price") . '</td><td class="ui-widget-content">';
                if ($product->price_base_type == 'TTC') {
                    print price($product->price_loc_ttc) . ' ' . $langs->trans($product->price_base_type);
                } else {
                    print price($product->price_loc) . ' ' . $langs->trans($product->price_base_type);
                }
                print '</td></tr>';
                // Duration
                print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans("Duration") . '</td><td class="ui-widget-content">' . $product->duration_value . '&nbsp;';
                if ($product->duration_value > 1) {
                    $dur = array("h" => $langs->trans("Hours"), "d" => $langs->trans("Days"), "w" => $langs->trans("Weeks"), "m" => $langs->trans("Months"), "y" => $langs->trans("Years"));
                } else {
                    $dur = array("h" => $langs->trans("Hour"), "d" => $langs->trans("Day"), "w" => $langs->trans("Week"), "m" => $langs->trans("Month"), "y" => $langs->trans("Year"));
                }
                print $langs->trans($dur[$product->duration_unit]) . "&nbsp;";

                print "Soit un prix/j de ";
                $durOp = array("h" => 1 / 7, "d" => 1, "w" => 5, "m" => 30, "y" => 360);
                print price(floatval($product->price_loc) / ($product->duration_value * $durOp[$product->duration_unit]));

                print '</td></tr>';
            } else {
                // MultiPrix
                if (isset($conf->global->PRODUIT_MULTIPRICES) && $conf->global->PRODUIT_MULTIPRICES == 1) {
                    print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans("SellingPrice") . ' 1</td>';

                    if ($product->price_base_type == 'TTC') {
                        print '<td class="ui-widget-content">' . price($product->price_ttc);
                    } else {
                        print '<td class="ui-widget-content">' . price($product->price);
                    }

                    print ' ' . $langs->trans($product->price_base_type);
                    print '</td></tr>';
                    for ($i = 2; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++) {
                        print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans("SellingPrice") . ' ' . $i . '</td>';

                        if ($product->multiprices_base_type["$i"] == 'TTC') {
                            print '<td class="ui-widget-content">' . price($product->multiprices_ttc["$i"]);
                        } else {
                            print '<td class="ui-widget-content">' . price($product->multiprices["$i"]);
                        }

                        if ($product->multiprices_base_type["$i"]) {
                            print ' ' . $langs->trans($product->multiprices_base_type["$i"]);
                        } else {
                            print ' ' . $langs->trans($product->price_base_type);
                        }

                        print '</td></tr>';
                    }
                }
                // Prix
                else {
                    print '<tr><th class="ui-state-default ui-widget-header" >' . $langs->trans("SellingPrice") . '</td><td class="ui-widget-content">';
                    if ($product->price_base_type == 'TTC') {
                        print price($product->price_ttc) . ' ' . $langs->trans($product->price_base_type);
                    } else {
                        print price($product->price) . ' ' . $langs->trans($product->price_base_type);
                    }
                    print '</td></tr>';
                }
            }

            // TVA
            print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans("VATRate") . '</td><td class="ui-widget-content">' . vatrate($product->tva_tx, true) . '</td></tr>';

            // Statut
            print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans("Status") . '</td><td class="ui-widget-content">';
            print $product->getLibStatut(2);
            print '</td></tr>';

            // Description
            print '<tr><th  class="ui-state-default ui-widget-header" valign="top">' . $langs->trans("Description") . '</td><td class="ui-widget-content">' . nl2br($product->description) . '</td></tr>';
            $colspan = 'colspan="1"';
            $colspanDouble = 'colspan="2"';
            if ($product->is_photo_available($conf->produit->dir_output))
                $colspan = 'colspan="2"';
            if ($product->is_photo_available($conf->produit->dir_output))
                $colspanDouble = 'colspan="3"';


            if ($product->isservice()) {
                // Duration
                print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans("Duration") . '</td><td ' . $colspan . ' class="ui-widget-content">' . $product->duration_value . '&nbsp;';
                if ($product->duration_value > 1) {
                    $dur = array("h" => $langs->trans("Hours"), "d" => $langs->trans("Days"), "w" => $langs->trans("Weeks"), "m" => $langs->trans("Months"), "y" => $langs->trans("Years"));
                } else {
                    $dur = array("h" => $langs->trans("Hour"), "d" => $langs->trans("Day"), "w" => $langs->trans("Week"), "m" => $langs->trans("Month"), "y" => $langs->trans("Year"));
                }
                print $langs->trans($dur[$product->duration_unit]) . "&nbsp;";

                print '</td></tr>';
//            } else if (0&&$product->iscontrat()) {
            } else if ($product->type == 2) {

                if ($product->array_options['options_2isSav'] > 0) {
                    print '<tr><th ' . $colspanDouble . ' class="ui-widget-header ui-state-default" >' . $langs->trans("Contrat d'extension de SAV") . '</th>';
                    print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Dur&eacute;e de l'extension (en mois)") . '</th>';
                } else {
                    print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Dur&eacute;e de validit&eacute; (en mois)") . '</th>';
                }
                print '<td ' . $colspan . ' class="ui-widget-content">' . $product->array_options['options_2dureeVal'] . " mois";
                //Reconduction
                if ($product->array_options['options_2reconductionAuto'] > 0) {
                    print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Reconduction automatique") . '</th>
                               <td ' . $colspan . ' class="ui-widget-content">OUI';
                }
                //Le SLA ou « Contrat de niveau de service » : référence au temps de délivrance et/ou à la performance (du service) tel que déﬁni dans le contrat.
                if ($product->array_options['options_2SLA'] . "x" != "x") {
                    print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("SLA") . '</th>
                               <td ' . $colspan . ' class="ui-widget-content">' . $product->array_options['options_2SLA'];
                }
                //var_dump($product->array_options['options_2maintenance']);
                if ($product->array_options['options_2maintenance'] . "x" != "x" && $product->array_options['options_2maintenance'] <> 0) {
                    //Maintenance complète
                    print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Maintenance") . '</th>
                               <td ' . $colspan . ' class="ui-widget-content">OUI';
                }
                //Nombre de visite mensuel
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Visite préventivee (par an)") . '</th>
                               <td ' . $colspan . ' class="ui-widget-content">';
                if ($product->array_options['options_2visiteSurSite'] > 0) {
                    print 'OUI ' . $product->array_options['options_2visiteSurSite'];
                }elseif ($product->array_options['options_2visiteSurSite'] == -1) {
                    print 'OUI Illimité';
                } else {
                    print 'NON';
                }
                
                //Nombre de visite curative mensuel
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Visite curative (par an)") . '</th>
                               <td ' . $colspan . ' class="ui-widget-content">';
                if ($product->array_options['options_2visiteCur'] > 0) {
                    print 'OUI ' . $product->array_options['options_2visiteCur'];
                }elseif ($product->array_options['options_2visiteCur'] == -1) {
                    print 'OUI Illimité';
                } else {
                    print 'NON';
                }
                //Télémaintenance
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("T&eacute;l&eacute;-Maintenance préventive") . '</th>
                               <td ' . $colspan . ' class="ui-widget-content">';
                if ($product->array_options['options_2teleMaintenance'] > 0) {
                    print 'OUI '.$product->array_options['options_2teleMaintenance'];
                }elseif ($product->array_options['options_2teleMaintenance'] == -1) {
                    print 'OUI Illimité';
                }else {
                    print 'NON';
                }
                //Télémaintenance curative
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("T&eacute;l&eacute;-Maintenance curative") . '</th>
                               <td ' . $colspan . ' class="ui-widget-content">';
                if ($product->array_options['options_2teleMaintenanceCur'] > 0) {
                    print 'OUI '.$product->array_options['options_2teleMaintenanceCur'];
                }elseif ($product->array_options['options_2teleMaintenanceCur'] == -1) {
                    print 'OUI Illimité';
                }else {
                    print 'NON';
                }
                //Accès à la hotline
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Hotline") . '</th>
                               <td ' . $colspan . ' class="ui-widget-content">';
                if ($product->array_options['options_2hotline'] == -1) {
                    print 'OUI Illimité';
                }elseif ($product->array_options['options_2hotline'] > 0) {
                    print 'OUI '.$product->array_options['options_2hotline'];
                }else {
                    print 'NON';
                }
//                if ($product->array_options['options_2qte'] > 0) {
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Duree appel max avant interv. <br/><em><small>(en min)</small></em>") . '</th>
                               <td ' . $colspan . ' class="ui-widget-content">' . $product->array_options['options_2qte'];
//                } if ($product->array_options['options_2qte'] == -1) {
//                    print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Qt&eacute; de tickets") . '</th>
//                               <td ' . $colspan . ' class="ui-widget-content">Illimit&eacute;';
//                }
                //Valo Ticket
                if ($product->array_options['options_2qtePerDuree'] . "x" != "x" && $product->array_options['options_2timePerDuree'] . "x" != "x") {
                    print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Dur&eacute;e par ticket") . '</th>';
                    if ($product->array_options['options_2timePerDuree'] == 0) {
                        print '    <td ' . $colspan . ' class="ui-widget-content">' . $product->array_options['options_2qtePerDuree'] . ' ticket(s) par intervention';
                    } else {
                        $txtDur = "";
                        $arrDur = convDur($product->array_options['options_2timePerDuree']);
                        $txtDur = $arrDur['hours']['abs'] . " heure" . ($arrDur['hours']['abs'] > 1 ? "s" : "") . " " . ($arrDur['minutes']['rel'] > 0 ? "et " . $arrDur['minutes']['rel'] . " minute" . ($arrDur['minutes']['rel'] > 1 ? "s" : "") : "");
                        print '    <td ' . $colspan . ' class="ui-widget-content">' . $product->array_options['options_2qtePerDuree'] . ' ticket(s) pour ' . $txtDur . " d'intervention";
                    }
                }
            } else if ($product->type == 0) {
                // Weight / Volume
                print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans("Weight") . '</td><td ' . $colspan . '  class="ui-widget-content">';
                if ($product->weight != '') {
                    print $product->weight . " " . measuring_units_string($product->weight_units, "weight");
                } else {
                    print '&nbsp;';
                }
                print "</td></tr>\n";
                print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans("Volume") . '</td><td ' . $colspan . ' class="ui-widget-content">';
                if ($product->volume != '') {
                    print $product->volume . " " . measuring_units_string($product->volume_units, "volume");
                } else {
                    print '&nbsp;';
                }
                print "</td></tr>\n";
                //SAV
                print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans("durSAV") . '</td><td ' . $colspan . ' class="ui-widget-content">';
                print "<strong>" . ($product->array_options['options_2dureeSav'] == -1 ? $langs->trans('illimit&eacute;') . "</strong>" : ($product->array_options['options_2dureeSav'] > -1 ? $product->array_options['options_2dureeSav'] . "</strong> mois" : "0</strong> mois")) . "";
                print "</td></tr>\n";
            } else if (0 && $product->islocation()) {

                // Weight / Volume
                print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans("Weight") . '</td><td ' . $colspan . ' class="ui-widget-content">';
                if ($product->weight != '') {
                    print $product->weight . " " . measuring_units_string($product->weight_units, "weight");
                } else {
                    print '&nbsp;';
                }
                print "</td></tr>\n";
                print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans("Volume") . '</td><td ' . $colspan . ' class="ui-widget-content">';
                if ($product->volume != '') {
                    print $product->volume . " " . measuring_units_string($product->volume_units, "volume");
                } else {
                    print '&nbsp;';
                }
                print "</td></tr>\n";
            }
            // Clause
            print '<tr><th class="ui-state-default ui-widget-header" valign="top">' . $langs->trans("Clause") . '</td>
                        <td ' . $colspan . ' class="ui-widget-content">' . nl2br($product->array_options['options_2clause']) . '</td></tr>';

            // Note
            print '<tr><th class="ui-state-default ui-widget-header" valign="top">' . $langs->trans("Note") . '</td>
                        <td ' . $colspan . ' class="ui-widget-content">' . nl2br($product->note) . '</td></tr>';

            if (USE_CAT) {
                //Template
                require_once(DOL_DOCUMENT_ROOT . '/categories/template.class.php');
                require_once(DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php');

                //Get Categories
                $c = new Categorie($db);
                $cats = $c->containing($product->id, "product", 0);

//            $template = new Template($db);
                $arrId = array();
                if (sizeof($cats) > 0) {
                    $title = $langs->trans("ProductIsInCategories");
                    $var = true;
                    //ajout parents
                    foreach ($cats as $cat => $c) {
                        $c->db = $db;
                        $meres = $c->get_meres();
                        foreach ($meres as $k => $v) {
                            $v->db = $db;
                            $arrId = array_merge($arrId, recursParent($v));
                        }
                    }
                    foreach ($arrId as $k => $v) {
                        $tmpC = new Categorie($db, $v);
                        $addElement = true;
                        foreach ($cats as $k1 => $v1) {
                            if ($v1->id == $v) {
                                $addElement = false;
                            }
                        }
                        if ($addElement) {
                            $cats[] = $tmpC;
                        }
                    }
                }
                if (sizeof($cats) > 0) {
                    $title = $langs->trans("ProductIsInCategories");
                    $var = true;
                    //ajout parents
                    foreach ($cats as $cat => $c) {
                        $c->db = $db;
                        $meres = $c->get_meres();
                        foreach ($meres as $k => $v) {
                            $v->db = $db;
                            $arrId = array_merge($arrId, recursParent($v));
                        }
                    }
                    foreach ($arrId as $k => $v) {
                        $tmpC = new Categorie($db, $v);
                        $addElement = true;
                        foreach ($cats as $k1 => $v1) {
                            if ($v1->id == $v) {
                                $addElement = false;
                            }
                        }
                        if ($addElement) {
                            $cats[] = $tmpC;
                        }
                    }


                    foreach ($cats as $cat) {
                        $tplList = $template->fetch_by_cat($cat->id);
                        if (count($tplList) > 0) {
                            foreach ($tplList as $key => $val) {
                                $i = 0;
                                $var = true;
                                $template->fetch($key);
                                $template->getKeys();
                                global $product;
                                $template->getValues($product->id);
                                foreach ($template->keysList as $key1 => $val1) {
                                    $var = !$var;
                                    print "<tr " . $bc[$var] . ">";
                                    print '<th class="ui-widget-header ui-state-default" valign="middle">' . $val1['nom'] . "</td>";
                                    $value = $template->extraValue[$product->id][$val1['nom']]['value'];


                                    //print "<div id='colorSelector' class='ui-corner-all' style='width:32px; height:32px; border:1px Solid;'><div class='ui-corner-all' style='width:32px; height:32px; background-color:#".$value."'></div></div>";

                                    if ($val1['type'] == "couleur") {
                                        print '<td class="ui-widget-content" valign="middle" ' . $colspan . ' >';
                                        print "<div id='colorSelector' class='ui-corner-all' style='width:32px; height:32px; border:1px Solid;'><div class='ui-corner-all' style='width:32px; height:32px; background-color:#" . $value . "'></div></div>";
                                    } else {
                                        print '<td class="ui-widget-content" valign="middle" ' . $colspan . ' >' . $value . "</td>";
                                    }
                                    print "</tr>";
                                }
                                print "</tbody>";
                            }
                        } else {
                            /*            print "<tr><td valign=top colspan=3>Pas d'extension produit pour cette cat&eacute;gorie"; */
                        }
                    }
                }
            }

            //Babel PN
            if (isset($conf->global->MAIN_MODULE_BABELPRIME)) {
                print '<tr><td  class="ui-state-default ui-widget-header" valign="top">' . $langs->trans("Catégorie PN") . '</td><td class="ui-widget-content">';
                $requete = "SELECT * " .
                        "    FROM Babel_Prime_CatPN," .
                        "         Babel_PN_products " .
                        "   WHERE Babel_Prime_CatPN.id =  Babel_PN_products.Prime_CatPN_refid " .
                        "     AND Babel_PN_products.Product_refid = " . $product->id;
                " ";
                $resql = $db->query($requete);
                if ($resql) {
                    while ($res = $db->fetch_object($resql)) {
                        print $res->name;
                    }
                }
                print "</td></tr>";
            }

            //Fin Babel PN


            print "</table>\n";
            print "</div>\n<!-- CUT HERE -->\n";
        }
    }

    /*
     * Fiche en mode edition
     */
    if ($_GET["action"] == 'edit' && $user->rights->produit->creer) {
        if ($product->isservice()) {
            print_fiche_titre($langs->trans('Modify') . ' ' . $langs->trans('Service') . ' : ' . $product->ref, "");
        } else {
            print_fiche_titre($langs->trans('Modify') . ' ' . $langs->trans('Product') . ' : ' . $product->ref, "");
        }

        if ($mesg) {
            print '<br><div class="error ui-state-error">' . $mesg . '</div><br>';
        }

        if ($product->canvas == '') {
            print "<!-- CUT HERE -->\n";
            print "<form action=\"card.php\" method=\"post\">\n";
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="id" value="' . $product->id . '">';
            print '<input type="hidden" name="canvas" value="' . $product->canvas . '">';
            print '<table cellpadding=15 class="border" width="100%">';
            print '<tr><th class="ui-widget-header ui-state-default" width="15%">' . $langs->trans("Ref") . '</th>
                       <td class="ui-widget-content" colspan="2"><input name="ref" size="40" maxlength="32" value="' . $product->ref . '"></td></tr>';
            print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Label") . '</th>';
            $libelle = preg_replace("/'/", "&apos;", $product->label);
            print "<td class='ui-widget-content'><input name='libelle' size='40' value='" . $libelle . "'></td></tr>";

            // Status
            print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Status") . '</th>
                       <td class="ui-widget-content" colspan="2">';
            print '<select class="flat" name="statut">';
            if ($product->status) {
                print '<option value="1" selected="true">' . $langs->trans("OnSell") . '</option>';
                print '<option value="0">' . $langs->trans("NotOnSell") . '</option>';
            } else {
                print '<option value="1">' . $langs->trans("OnSell") . '</option>';
                print '<option value="0" selected="true">' . $langs->trans("NotOnSell") . '</option>';
            }
            print '</select>';
            print '</td></tr>';

            if ($product->isproduct() && $conf->stock->enabled) {
                print "<tr>" . '<th class="ui-widget-header ui-state-default">' . $langs->trans("StockLimit") . '</th>
                              <td class="ui-widget-content" colspan="2">';
                print '<input name="seuil_stock_alerte" size="4" value="' . $product->seuil_stock_alerte . '">';
                print '</td></tr>';
            } else {
                print '<input name="seuil_stock_alerte" type="hidden" value="0">';
            }


            print '<tr><th valign="top" class="ui-widget-header ui-state-default">' . $langs->trans("Annexe") . '</th>
                       <td colspan="2" class="ui-widget-content">';
            $sql = "SELECT id, modeleName FROM " . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf";
            print '<select name="annexe"><option value="-1">Choix</option>';
            $resql = $db->query($sql);
            while ($res = $db->fetch_object($resql))
                print '<option value="' . $res->id . '"' . ($res->id == $product->array_options['options_2annexe'] ? ' selected="selected"' : '') . '>' . $res->modeleName . '</option>';
            print '</select>    </td>
                   </tr>';






            // Description (utilise dans facture, propale...)
            print '<tr><th valign="top" class="ui-widget-header ui-state-default">' . $langs->trans("Description") . '</th>
                       <td colspan="2" class="ui-widget-content">';
            print "\n";
            if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC) {
                require_once(DOL_DOCUMENT_ROOT . "/lib/doleditor.class.php");
                $doleditor = new DolEditor('desc', $product->description, 160, 'dol_notes', '', false);
                $doleditor->Create();
            } else {
                print '<textarea name="desc" rows="4" cols="90">';
                print dol_htmlentitiesbr_decode($product->description);
                print "</textarea>";
            }
            print "</td></tr>";
            print "\n";

            if ($product->isservice()) {
                // Duration
                print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Duration") . '</th>
                           <th colspan="2" class="ui-widget-content"><input name="duration_value" size="3" maxlength="5" value="' . $product->duration_value . '">';
                print '&nbsp; ';
                print '<input name="duration_unit" type="radio" value="h"' . ($product->duration_unit == 'h' ? ' checked' : '') . '>' . $langs->trans("Hour");
                print '&nbsp; ';
                print '<input name="duration_unit" type="radio" value="d"' . ($product->duration_unit == 'd' ? ' checked' : '') . '>' . $langs->trans("Day");
                print '&nbsp; ';
                print '<input name="duration_unit" type="radio" value="w"' . ($product->duration_unit == 'w' ? ' checked' : '') . '>' . $langs->trans("Week");
                print '&nbsp; ';
                print '<input name="duration_unit" type="radio" value="m"' . ($product->duration_unit == 'm' ? ' checked' : '') . '>' . $langs->trans("Month");
                print '&nbsp; ';
                print '<input name="duration_unit" type="radio" value="y"' . ($product->duration_unit == 'y' ? ' checked' : '') . '>' . $langs->trans("Year");

                print '</td></tr>';
//            } else if (0&&$product->iscontrat()) {
            } else if ($product->type == 2) {
                //Extension de SAV ou pas
                print "<tr><th class='ui-widget-header ui-state-default'>Type de contrat<td class='ui-widget-content' colspan=1 align=center>";
                print "<div id='tabs' style='width:98%'>";
                print "<ul><li><span><a href='#MNT'>Maintenance</a></span></li>";
                print "    <li><span><a href='#SAV'>SAV</a></span></li>";
                print "    <li><span><a href='#TKT'>Ticket</a></span></li>";
                print "    <li><span><a href='#OSER'>Autre</a></span></li>";
                print "</ul>";
                print "<div id='MNT'>";
                print "<table width=100% cellpadding=10>";
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Dur&eacute;e de validit&eacute; (en mois)") . '</th>
                           <td class="ui-widget-content"><input style="text-align:center;"  size=4 type="text" name="dureeMNT" value="' . $product->array_options['options_2dureeVal'] . '">';
                //Nombre de visite mensuel
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Visite préventive (par an)") . '</th>
                           <td class="ui-widget-content"><input style="text-align:center;" size=4 type="text" name="visite" value="' . $product->array_options['options_2visiteSurSite'] . '">';
                //Nombre de visite curative
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Visite curative (par an)") . '</th>
                           <td class="ui-widget-content"><input style="text-align:center;" size=4 type="text" name="visiteCur" value="' . $product->array_options['options_2visiteCur'] . '">';
                //Télémaintenance
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("T&eacute;l&eacute;-Maintenance préventive") . '</th>
                           <td class="ui-widget-content"><input style="text-align:center;" size=4 type="text" name="telemaintenance" value="' . $product->array_options['options_2teleMaintenance'] . '">';
                //Télémaintenance curative
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("T&eacute;l&eacute;-Maintenance curative") . '</th>
                           <td class="ui-widget-content"><input style="text-align:center;" size=4 type="text" name="telemaintenanceCur" value="' . $product->array_options['options_2teleMaintenanceCur'] . '">';
                //Accès à la hotline
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Hotline") . '</th>
                           <td class="ui-widget-content"><input style="text-align:center;" size=4 type="text" name="hotline" value="' . $product->array_options['options_2hotline'] . '">';
                //Tkt
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Duree appel max avant interv. <br/><em><small>(en min)</small></em>") . '</th>
                           <td class="ui-widget-content"><input style="text-align:center;" size=4 type="text" value="' . $product->array_options['options_2qte'] . '" name="qteMNT">';
                //Valo Ticket
                $qteTempsPerDureeM = 0;
                $qteTempsPerDureeH = 0;
                if ($product->array_options['options_2timePerDuree'] > 0) {
                    $arrDur = convDur($product->array_options['options_2timePerDuree']);
                    $qteTempsPerDureeH = $arrDur['hours']['abs'];
                    $qteTempsPerDureeM = $arrDur['minutes']['rel'];
                }


                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Dur&eacute;e par ticket<br/><em><small>(<b>0 h 0 min</b>sans d&eacute;compte de temps)</small></em>") . '</th>
                           <td class="ui-widget-content"><input style="text-align:center;" size=4 type="text" value="' . $product->array_options['options_2qtePerDuree'] . '" name="qteTktPerDuree"> ticket(s) pour <input style="text-align:center;" type="text" size=4 value="' . $qteTempsPerDureeH . '" name="qteTempsPerDureeH"> h <input style="text-align:center;" type="text" size=4 value="' . $qteTempsPerDureeM . '" name="qteTempsPerDureeM"> min';

                print "</table>";
                print "</div>";
                print "<div id='SAV'>";
                //Durée de validité
                print "<table width=100% cellpadding=10>";
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Dur&eacute;e de l'extension (en mois)") . '</th>
                           <td class="ui-widget-content"><input style="text-align:center;" size=4 type="text" name="dureeSAV" value="' . $product->array_options['options_2dureeVal'] . '">';
                print "</table>";
                print "</div>";
                print "<div id='TKT'>";
                print "<table width=100% cellpadding=10>";
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Dur&eacute;e de validit&eacute; (en mois)") . '</th>
                           <td class="ui-widget-content"><input style="text-align:center;" size=4 type="text" name="dureeTKT" value="' . $product->array_options['options_2dureeVal'] . '">';
                //Tkt
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Qt&eacute; de tickets<br/><em><small>(<b>0</b> Sans ticket, <b>-1</b> Illimit&eacute;)</small></em>") . '</th>
                           <td class="ui-widget-content"><input style="text-align:center;" size=4 type="text" value="' . $product->array_options['options_2qte'] . '" name="qteTKT">';
                print "</table>";
                print "</div>";
                print "<div id='OSER'>";
                print "<table width=100% cellpadding=10>";
                print "</table>";
                print "</div>";
                print "</div>";
                print "</tr>";
                //Reconduction
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("Reconduction automatique") . '</th>
                           <td class="ui-widget-content"><input type="checkbox" ' . ($product->array_options['options_2reconductionAuto'] > 0 ? 'checked' : '') . ' name="reconductionauto">';
                //Le SLA ou « Contrat de niveau de service » : référence au temps de délivrance et/ou à la performance (du service) tel que déﬁni dans le contrat.
                print '<tr><th class="ui-widget-header ui-state-default" >' . $langs->trans("SLA") . '</th>
                           <td class="ui-widget-content"><input type="text" name="SLA" value="' . $product->array_options['options_2SLA'] . '">';
                print <<<EOF
<script>
jQuery(document).ready(function(){
    jQuery('#tabs').tabs({
        spinner: 'Chargement ...',
        cache: true,
        fx: { height: 'toggle', opacity: "toggle" },
        select: function(a,b){
            //console.log();
            jQuery('#typeProd').val(b.panel.id);
        },
EOF;
                print 'selected: ' . ($product->array_options['options_2isSav'] ? 1 : ($product->array_options['options_2maintenance'] ? 0 : ($product->array_options['options_2dureeVal'] ? 2 : 3))) . ' ';
                
                print <<<EOF
    });
});
</script>
EOF;
                print "<input id='typeProd' type='hidden' name='typeProd' value='" . ($product->typeContrat . "x" == 'x' ? 'MNT' : $product->typeContrat) . "'> ";
            } else if (0 && $product->islocation()) {
                print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Duration") . '</th>
                           <td colspan="2" class="ui-widget-content"><input name="duration_value" size="3" maxlength="5" value="' . $product->duration_value . '">';
                print '&nbsp; ';
                print '<input name="duration_unit" type="radio" value="h"' . ($product->duration_unit == 'h' ? ' checked' : '') . '>' . $langs->trans("Hour");
                print '&nbsp; ';
                print '<input name="duration_unit" type="radio" value="d"' . ($product->duration_unit == 'd' ? ' checked' : '') . '>' . $langs->trans("Day");
                print '&nbsp; ';
                print '<input name="duration_unit" type="radio" value="w"' . ($product->duration_unit == 'w' ? ' checked' : '') . '>' . $langs->trans("Week");
                print '&nbsp; ';
                print '<input name="duration_unit" type="radio" value="m"' . ($product->duration_unit == 'm' ? ' checked' : '') . '>' . $langs->trans("Month");
                print '&nbsp; ';
                print '<input name="duration_unit" type="radio" value="y"' . ($product->duration_unit == 'y' ? ' checked' : '') . '>' . $langs->trans("Year");
                print '</td></tr>';
                // Weight / Volume
                print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Weight") . '</td><td class="ui-widget-content">';
                print '<input style="margin:3px 10px; float: left;" name="weight" size="5" value="' . $product->weight . '"> ';
                print $formproduct->select_measuring_units("weight_units", "weight", $product->weight_units);
                print '</td></tr>';
                print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Volume") . '</td><td class="ui-widget-content">';
                print '<input style="margin: 3px 10px; float: left;" name="volume" size="5" value="' . $product->volume . '"> ';
                print $formproduct->select_measuring_units("volume_units", "volume", $product->volume_units);
                print '</td></tr>';
            } else if ($product->type == 0) {
                // Weight / Volume
                print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Weight") . '</td><td class="ui-widget-content">';
                print '<input style="margin:3px 10px; float: left;" name="weight" size="5" value="' . $product->weight . '"> ';
                print $formproduct->select_measuring_units("weight_units", "weight", $product->weight_units);
                print '</td></tr>';
                print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Volume") . '</td><td class="ui-widget-content">';
                print '<input style="margin: 3px 10px; float: left;" name="volume" size="5" value="' . $product->volume . '"> ';
                print $formproduct->select_measuring_units("volume_units", "volume", $product->volume_units);
                print '</td></tr>';
                print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("durSAV") . '</td><td class="ui-widget-content">';
//Duree de SAV
                print "<input type='hidden' id='set_DURAV' name='set_DURAV' value='" . $conf->global->PRODUIT_DEFAULT_DUR_SAV . "'>";
                print "<span>
                          <span id='moinsSAV' style='cursor: pointer; float: left;' class='ui-icon ui-icon-circle-minus'></span>
                          <span style='float:left;'>&nbsp;&nbsp;&nbsp;</span>
                          <span style='font-weight:900; float: left;' id='dfltSAV'>" . ($conf->global->PRODUIT_DEFAULT_DUR_SAV > 0 ? $conf->global->PRODUIT_DEFAULT_DUR_SAV : 0) . "</span>
                          <span style='float: left;' >&nbsp;mois&nbsp;&nbsp;&nbsp;</span>
                          <span style='cursor: pointer; float: left;' id='plusSAV' class='ui-icon ui-icon-circle-plus'></span>
            </span>";
                print "<script>";
                print <<<EOF

jQuery(document).ready(function(){
    jQuery('#moinsSAV').click(function(){
        var cnt = parseInt(jQuery('#set_DURAV').val());
        if (isNaN(cnt)) cnt=0;
        cnt --;
        if (cnt<-1) cnt=-1;
        jQuery('#set_DURAV').val(cnt);
        jQuery('#dfltSAV').text(cnt);
    });
    jQuery('#plusSAV').click(function(){
        var cnt = parseInt(jQuery('#set_DURAV').val());
        if (isNaN(cnt)) cnt=0;
        cnt ++;
        if (cnt<-1) cnt=-1;
        jQuery('#set_DURAV').val(cnt);
        jQuery('#dfltSAV').text(cnt);
    });
});

EOF;
                print "</script>";
            }

            // Clause
            print '<tr><th class="ui-widget-header ui-state-default" valign="top">' . $langs->trans("Clause") . '</td>
                       <td  class="ui-widget-content" colspan="2">';
            if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC) {
                require_once(DOL_DOCUMENT_ROOT . "/lib/doleditor.class.php");
                $doleditor = new DolEditor('clause', $product->note, 200, 'dol_notes', '', false);
                $doleditor->Create();
            } else {
                print '<textarea name="clause" rows="8" cols="70">';
                print dol_htmlentitiesbr_decode($product->note);
                print "</textarea>";
            }
            print "</td></tr>";

            // Note
            print '<tr><th class="ui-widget-header ui-state-default" valign="top">' . $langs->trans("NoteNotVisibleOnBill") . '</td>
                       <td  class="ui-widget-content" colspan="2">';
            if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC) {
                require_once(DOL_DOCUMENT_ROOT . "/lib/doleditor.class.php");
                $doleditor = new DolEditor('note', $product->note, 200, 'dol_notes', '', false);
                $doleditor->Create();
            } else {
                print '<textarea name="note" rows="8" cols="70">';
                print dol_htmlentitiesbr_decode($product->note);
                print "</textarea>";
            }
            print "</td></tr>";

            if (USE_CAT) {
                //Template
//            require_once(DOL_DOCUMENT_ROOT . '/categories/template.class.php');
                require_once(DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php');

                //Get Categories
                $c = new Categorie($db);
                $cats = $c->containing($product->id, "product", 0);

                $template = new Template($db);
                $arrId = array();
                if (sizeof($cats) > 0) {
                    //ajout parents
                    foreach ($cats as $cat => $c) {
                        $c->db = $db;
                        $meres = $c->get_meres();
                        foreach ($meres as $k => $v) {
                            $v->db = $db;
                            $arrId = array_merge($arrId, recursParent($v));
                        }
                    }
                    foreach ($arrId as $k => $v) {
                        $tmpC = new Categorie($db, $v);
                        $addElement = true;
                        foreach ($cats as $k1 => $v1) {
                            if ($v1->id == $v) {
                                $addElement = false;
                            }
                        }
                        if ($addElement) {
                            $cats[] = $tmpC;
                        }
                    }
                }

                if (sizeof($cats) > 0) {
                    $title = $langs->trans("ProductIsInCategories");
                    $var = true;
                    foreach ($cats as $cat) {
                        $tplList = $template->fetch_by_cat($cat->id);
                        if (count($tplList) > 0) {
                            foreach ($tplList as $key => $val) {
                                $i = 0;
                                $var = true;
                                $template->fetch($key);
                                $template->getKeys();
                                global $product;
                                $template->getValues($product->id);
                                foreach ($template->keysList as $key1 => $val1) {
                                    $var = !$var;
                                    print "<tr " . $bc[$var] . ">";
                                    print '<th class="ui-widget-header ui-state-default" valign="middle">' . $val1['nom'] . "</td>";
                                    $value = $template->extraValue[$product->id][$val1['nom']]['value'];
                                    $class = '';
                                    if ($val1['type'] == 'textarea') {
                                        print '<td class="ui-widget-content" valign="middle" colspan=1><textarea style="width:80%;height:4em;" id="' . $val1['nom'] . '" name="' . $val1['nom'] . '" >' . $value . '</textarea></td>';
                                    } else if ($val1['type'] == "couleur") {

                                        print "<td class='ui-widget-content' valign='middle' colspan=1>";
                                        if ($value . "x" == "x")
                                            $value = "0073EA";
                                        print "<div id='colorSelector' class='ui-corner-all' style='width:32px; height:32px; border:1px Solid;'><div class='ui-corner-all' style='width:32px; height:32px; background-color:#" . $value . "'></div></div>";
                                        print "<input type='hidden' id='" . $val1['nom'] . "' name='" . $val1['nom'] . "' value='" . $value . "'>";
                                        print <<<EOF
<script>
    jQuery(document).ready(function(){
        jQuery('#colorSelector').ColorPicker({
EOF;
                                        print "color: '#" . $value . "',\n";
                                        print <<<EOF
            onShow: function (colpkr) {
                jQuery(colpkr).fadeIn(500);
                return false;
            },
            onHide: function (colpkr) {
                jQuery(colpkr).fadeOut(500);
                return false;
            },
            onChange: function (hsb, hex, rgb) {
                jQuery('#colorSelector div').css('backgroundColor', '#' + hex);
EOF;
                                        print "jQuery('#" . $val1['nom'] . "').val(hex);\n";
                                        print <<<EOF
            }
        });

    });
</script>
EOF;
                                    } else {
                                        if ($val1['type'] == 'date') {
                                            $class = 'datepicker';
                                        }
                                        print '<td class="ui-widget-content" valign="middle" colspan=1><input class="' . $class . '" value="' . $value . '" id="' . $val1['nom'] . '" name="' . $val1['nom'] . '" ></td>';
                                    }
                                    print "</tr>";
                                }
                                print "</tbody>";
                            }
                        } else {
                            /*            print "<tr><td valign=top colspan=3>Pas d'extension produit pour cette cat&eacute;gorie"; */
                        }
                    }
                }
            }



            //Babel PN
            if (isset($conf->global->MAIN_MODULE_BABELPRIME)) {
                $selected = false;
                if ("x" . $product->id != "x") {
                    $requete = "SELECT Prime_CatPN_refid" .
                            "     FROM Babel_PN_products " .
                            "    WHERE Product_refid = " . $product->id;
                    $resql = $db->query($requete);
                    if ($resql) {
                        $res = $db->fetch_object($resql);
                        $selected = $res->Prime_CatPN_refid;
                    }
                }
                print '<tr><td valign="top">' . $langs->trans("Catégorie PN") . '</td><td>';
                print '<SELECT name="catPN">';
                $requete = "SELECT * FROM Babel_Prime_CatPN order by name";
                $resql = $db->query($requete);
                if ($resql) {
                    while ($res = $db->fetch_object($resql)) {
                        if ($selected == $res->id) {
                            print "<OPTION SELECTED value='" . $res->id . "'>" . $res->name . "</OPTION>";
                        } else {
                            print "<OPTION value='" . $res->id . "'>" . $res->name . "</OPTION>";
                        }
                    }
                }
                print "</SELECT>";
                print "</td></tr>";
            }

            //Fin Babel PN
            print '<tr><td colspan="3" align="center"><input style="padding: 5px 10px" type="submit" class="ui-state-default ui-widget-header ui-corner-all ui-button" value="' . $langs->trans("Save") . '">&nbsp;';
            print '<input type="submit" style="padding: 5px 10px" class="ui-state-default ui-widget-header ui-corner-all ui-button" name="cancel" value="' . $langs->trans("Cancel") . '"></td></tr>';
            print '</table>';
            print '</form>';
            print "<!-- CUT HERE -->\n";
        } else {
            $tvaarray = load_tva($db, "tva_tx", $conf->defaulttx, $mysoc, '', '');
            $smarty->assign('tva_taux_value', $tvaarray['value']);
            $smarty->assign('tva_taux_libelle', $tvaarray['label']);
            $smarty->display($product->canvas . '-edit.tpl');
        }
    }
} else if (!$_GET["action"] == 'create') {
    Header("Location: index.php");
    exit;
}

// Define confirmation messages
$object = $product;
$formquestionclone = array(
    'text' => $langs->trans("ConfirmClone"),
    array('type' => 'text', 'name' => 'clone_ref', 'label' => $langs->trans("NewRefForClone"), 'value' => $langs->trans("CopyOf") . ' ' . $object->ref, 'size' => 24),
    array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneContentProduct"), 'value' => 1),
    array('type' => 'checkbox', 'name' => 'clone_prices', 'label' => $langs->trans("ClonePricesProduct") . ' (' . $langs->trans("FeatureNotYetAvailable") . ')', 'value' => 0, 'disabled' => true)
);

/* * ************************************************************************* */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* * ************************************************************************* */
print "\n" . '<div class="tabsAction">' . "\n";
if ($action == 'update')
    $action = 'view';
if ($action == '' || $action == 'view') {
    if ($user->rights->produit->creer || $user->rights->service->creer) {
        if (!isset($object->no_button_edit) || $object->no_button_edit <> 1)
            print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit&amp;id=' . $object->id . '">' . $langs->trans("Modify") . '</a>';

        if (!isset($object->no_button_copy) || $object->no_button_copy <> 1) {
            if (!empty($conf->use_javascript_ajax)) {
                print '<span id="action-clone" class="butAction">' . $langs->trans('ToClone') . '</span>' . "\n";
                print $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CloneProduct'), $langs->trans('ConfirmCloneProduct', $object->ref), 'confirm_clone', $formquestionclone, 'yes', 'action-clone', 230, 600);
            } else {
                print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=clone&amp;id=' . $object->id . '">' . $langs->trans("ToClone") . '</a>';
            }
        }
    }
    $object_is_used = $object->isObjectUsed($object->id);

    if (($object->type == 0 && $user->rights->produit->supprimer)
            || ($object->type != 0 && $user->rights->service->supprimer)) {
        if (empty($object_is_used) && (!isset($object->no_button_delete) || $object->no_button_delete <> 1)) {
            if (!empty($conf->use_javascript_ajax)) {
                print '<span id="action-delete" class="butActionDelete">' . $langs->trans('Delete') . '</span>' . "\n";
                print $form->formconfirm("?id=" . $object->id, $langs->trans("DeleteProduct"), $langs->trans("ConfirmDeleteProduct"), "confirm_delete", '', 0, "action-delete");
            } else {
                print '<a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?action=delete&amp;id=' . $object->id . '">' . $langs->trans("Delete") . '</a>';
            }
        } else {
            print '<a class="butActionRefused" href="#" title="' . $langs->trans("ProductIsUsed") . '">' . $langs->trans("Delete") . '</a>';
        }
    } else {
        print '<a class="butActionRefused" href="#" title="' . $langs->trans("NotEnoughPermissions") . '">' . $langs->trans("Delete") . '</a>';
    }
}

print "\n</div><br>\n";


/*
 * All the "Add to" areas
 */

if ($object->id && ($action == '' || $action == 'view') && $object->status) {
    print '<table width="100%" class="noborder">';

    // Propals
    if (!empty($conf->propal->enabled) && $user->rights->propale->creer) {
        $propal = new Propal($db);

        $langs->load("propal");

        print '<tr class="liste_titre"><td width="50%" class="liste_titre">';
        print $langs->trans("AddToMyProposals") . '</td>';

        if ($user->rights->societe->client->voir) {
            print '<td width="50%" class="liste_titre">';
            print $langs->trans("AddToOtherProposals") . '</td>';
        } else {
            print '<td width="50%" class="liste_titre">&nbsp;</td>';
        }

        print '</tr>';

        // Liste de "Mes propals"
        print '<tr><td' . ($user->rights->societe->client->voir ? ' width="50%"' : '') . ' valign="top">';

        $sql = "SELECT s.nom, s.rowid as socid, p.rowid as propalid, p.ref, p.datep as dp";
        $sql.= " FROM " . MAIN_DB_PREFIX . "societe as s, " . MAIN_DB_PREFIX . "propal as p";
        $sql.= " WHERE p.fk_soc = s.rowid";
        $sql.= " AND p.entity = " . $conf->entity;
        $sql.= " AND p.fk_statut = 0";
        $sql.= " AND p.fk_user_author = " . $user->id;
        $sql.= " ORDER BY p.datec DESC, p.tms DESC";

        $result = $db->query($sql);
        if ($result) {
            $var = true;
            $num = $db->num_rows($result);
            print '<table class="nobordernopadding" width="100%">';
            if ($num) {
                $i = 0;
                while ($i < $num) {
                    $objp = $db->fetch_object($result);
                    $var = !$var;
                    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="addinpropal">';
                    print "<tr " . $bc[$var] . ">";
                    print '<td nowrap="nowrap">';
                    print "<a href=\"../comm/propal.php?id=" . $objp->propalid . "\">" . img_object($langs->trans("ShowPropal"), "propal") . " " . $objp->ref . "</a></td>\n";
                    print "<td><a href=\"../comm/card.php?socid=" . $objp->socid . "\">" . dol_trunc($objp->nom, 18) . "</a></td>\n";
                    print "<td nowrap=\"nowrap\">" . dol_print_date($objp->dp, "%d %b") . "</td>\n";
                    print '<td><input type="hidden" name="propalid" value="' . $objp->propalid . '">';
                    print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>' . $langs->trans("ReductionShort");
                    print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
                    if (isset($object->stock_proposition))
                        print " " . $object->stock_proposition;
                    print '</td><td align="right">';
                    print '<input type="submit" class="button" value="' . $langs->trans("Add") . '">';
                    print '</td>';
                    print '</tr>';
                    print '</form>';
                    $i++;
                }
            }
            else {
                print "<tr " . $bc[!$var] . "><td>";
                print $langs->trans("NoOpenedPropals");
                print "</td></tr>";
            }
            print "</table>";
            $db->free($result);
        }

        print '</td>';

        if ($user->rights->societe->client->voir) {
            // Liste de "Other propals"
            print '<td width="50%" valign="top">';

            $var = true;
            $otherprop = $propal->liste_array(1, 1, 1);
            print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
            print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
            print '<table class="nobordernopadding" width="100%">';
            if (is_array($otherprop) && count($otherprop)) {
                $var = !$var;
                print '<tr ' . $bc[$var] . '><td colspan="3">';
                print '<input type="hidden" name="action" value="addinpropal">';
                print $langs->trans("OtherPropals") . '</td><td>';
                print $form->selectarray("propalid", $otherprop);
                print '</td></tr>';
                print '<tr ' . $bc[$var] . '><td nowrap="nowrap" colspan="2">' . $langs->trans("Qty");
                print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>' . $langs->trans("ReductionShort");
                print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
                print '</td><td align="right">';
                print '<input type="submit" class="button" value="' . $langs->trans("Add") . '">';
                print '</td></tr>';
            } else {
                print "<tr " . $bc[!$var] . "><td>";
                print $langs->trans("NoOtherOpenedPropals");
                print '</td></tr>';
            }
            print '</table>';
            print '</form>';

            print '</td>';
        }

        print '</tr>';
    }

    // Commande
    if (!empty($conf->commande->enabled) && $user->rights->commande->creer) {
        $commande = new Commande($db);

        $langs->load("orders");

        print '<tr class="liste_titre"><td width="50%" class="liste_titre">';
        print $langs->trans("AddToMyOrders") . '</td>';

        if ($user->rights->societe->client->voir) {
            print '<td width="50%" class="liste_titre">';
            print $langs->trans("AddToOtherOrders") . '</td>';
        } else {
            print '<td width="50%" class="liste_titre">&nbsp;</td>';
        }

        print '</tr>';

        // Liste de "Mes commandes"
        print '<tr><td' . ($user->rights->societe->client->voir ? ' width="50%"' : '') . ' valign="top">';

        $sql = "SELECT s.nom, s.rowid as socid, c.rowid as commandeid, c.ref, c.date_commande as dc";
        $sql.= " FROM " . MAIN_DB_PREFIX . "societe as s, " . MAIN_DB_PREFIX . "commande as c";
        $sql.= " WHERE c.fk_soc = s.rowid";
        $sql.= " AND c.entity = " . $conf->entity;
        $sql.= " AND c.fk_statut = 0";
        $sql.= " AND c.fk_user_author = " . $user->id;
        $sql.= " ORDER BY c.date_creation DESC";

        $result = $db->query($sql);
        if ($result) {
            $num = $db->num_rows($result);
            $var = true;
            print '<table class="nobordernopadding" width="100%">';
            if ($num) {
                $i = 0;
                while ($i < $num) {
                    $objc = $db->fetch_object($result);
                    $var = !$var;
                    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="addincommande">';
                    print "<tr " . $bc[$var] . ">";
                    print '<td nowrap="nowrap">';
                    print "<a href=\"../commande/card.php?id=" . $objc->commandeid . "\">" . img_object($langs->trans("ShowOrder"), "order") . " " . $objc->ref . "</a></td>\n";
                    print "<td><a href=\"../comm/card.php?socid=" . $objc->socid . "\">" . dol_trunc($objc->nom, 18) . "</a></td>\n";
                    print "<td nowrap=\"nowrap\">" . dol_print_date($db->jdate($objc->dc), "%d %b") . "</td>\n";
                    print '<td><input type="hidden" name="commandeid" value="' . $objc->commandeid . '">';
                    print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>' . $langs->trans("ReductionShort");
                    print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
                    if (isset($object->stock_proposition))
                        print " " . $object->stock_proposition;
                    print '</td><td align="right">';
                    print '<input type="submit" class="button" value="' . $langs->trans("Add") . '">';
                    print '</td>';
                    print '</tr>';
                    print '</form>';
                    $i++;
                }
            }
            else {
                print "<tr " . $bc[!$var] . "><td>";
                print $langs->trans("NoOpenedOrders");
                print '</td></tr>';
            }
            print "</table>";
            $db->free($result);
        }

        print '</td>';

        if ($user->rights->societe->client->voir) {
            // Liste de "Other orders"
            print '<td width="50%" valign="top">';

            $var = true;
            $othercom = $commande->liste_array(1, $user);
            print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
            print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
            print '<table class="nobordernopadding" width="100%">';
            if (is_array($othercom) && count($othercom)) {
                $var = !$var;
                print '<tr ' . $bc[$var] . '><td colspan="3">';
                print '<input type="hidden" name="action" value="addincommande">';
                print $langs->trans("OtherOrders") . '</td><td>';
                print $form->selectarray("commandeid", $othercom);
                print '</td></tr>';
                print '<tr ' . $bc[$var] . '><td colspan="2">' . $langs->trans("Qty");
                print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>' . $langs->trans("ReductionShort");
                print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
                print '</td><td align="right">';
                print '<input type="submit" class="button" value="' . $langs->trans("Add") . '">';
                print '</td></tr>';
            } else {
                print "<tr " . $bc[!$var] . "><td>";
                print $langs->trans("NoOtherOpenedOrders");
                print '</td></tr>';
            }
            print '</table>';
            print '</form>';

            print '</td>';
        }

        print '</tr>';
    }

    // Factures
    if (!empty($conf->facture->enabled) && $user->rights->facture->creer) {
        print '<tr class="liste_titre"><td width="50%" class="liste_titre">';
        print $langs->trans("AddToMyBills") . '</td>';

        if ($user->rights->societe->client->voir) {
            print '<td width="50%" class="liste_titre">';
            print $langs->trans("AddToOtherBills") . '</td>';
        } else {
            print '<td width="50%" class="liste_titre">&nbsp;</td>';
        }

        print '</tr>';

        // Liste de Mes factures
        print '<tr><td' . ($user->rights->societe->client->voir ? ' width="50%"' : '') . ' valign="top">';

        $sql = "SELECT s.nom, s.rowid as socid, f.rowid as factureid, f.facnumber, f.datef as df";
        $sql.= " FROM " . MAIN_DB_PREFIX . "societe as s, " . MAIN_DB_PREFIX . "facture as f";
        $sql.= " WHERE f.fk_soc = s.rowid";
        $sql.= " AND f.entity = " . $conf->entity;
        $sql.= " AND f.fk_statut = 0";
        $sql.= " AND f.fk_user_author = " . $user->id;
        $sql.= " ORDER BY f.datec DESC, f.rowid DESC";

        $result = $db->query($sql);
        if ($result) {
            $num = $db->num_rows($result);
            $var = true;
            print '<table class="nobordernopadding" width="100%">';
            if ($num) {
                $i = 0;
                while ($i < $num) {
                    $objp = $db->fetch_object($result);
                    $var = !$var;
                    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="addinfacture">';
                    print "<tr $bc[$var]>";
                    print "<td nowrap>";
                    print "<a href=\"../compta/facture.php?facid=" . $objp->factureid . "\">" . img_object($langs->trans("ShowBills"), "bill") . " " . $objp->facnumber . "</a></td>\n";
                    print "<td><a href=\"../comm/card.php?socid=" . $objp->socid . "\">" . dol_trunc($objp->nom, 18) . "</a></td>\n";
                    print "<td nowrap=\"nowrap\">" . dol_print_date($db->jdate($objp->df), "%d %b") . "</td>\n";
                    print '<td><input type="hidden" name="factureid" value="' . $objp->factureid . '">';
                    print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>' . $langs->trans("ReductionShort");
                    print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
                    print '</td><td align="right">';
                    print '<input type="submit" class="button" value="' . $langs->trans("Add") . '">';
                    print '</td>';
                    print '</tr>';
                    print '</form>';
                    $i++;
                }
            } else {
                print "<tr " . $bc[!$var] . "><td>";
                print $langs->trans("NoDraftBills");
                print '</td></tr>';
            }
            print "</table>";
            $db->free($result);
        } else {
            dol_print_error($db);
        }

        print '</td>';

        if ($user->rights->societe->client->voir) {
            $facture = new Facture($db);

            print '<td width="50%" valign="top">';

            // Liste de Autres factures
            $var = true;

            $sql = "SELECT s.nom, s.rowid as socid, f.rowid as factureid, f.facnumber, f.datef as df";
            $sql.= " FROM " . MAIN_DB_PREFIX . "societe as s, " . MAIN_DB_PREFIX . "facture as f";
            $sql.= " WHERE f.fk_soc = s.rowid";
            $sql.= " AND f.entity = " . $conf->entity;
            $sql.= " AND f.fk_statut = 0";
            $sql.= " AND f.fk_user_author <> " . $user->id;
            $sql.= " ORDER BY f.datec DESC, f.rowid DESC";

            $result = $db->query($sql);
            if ($result) {
                $num = $db->num_rows($result);
                $var = true;
                print '<table class="nobordernopadding" width="100%">';
                if ($num) {
                    $i = 0;
                    while ($i < $num) {
                        $objp = $db->fetch_object($result);

                        $var = !$var;
                        print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
                        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                        print '<input type="hidden" name="action" value="addinfacture">';
                        print "<tr " . $bc[$var] . ">";
                        print "<td><a href=\"../compta/facture.php?facid=" . $objp->factureid . "\">$objp->facnumber</a></td>\n";
                        print "<td><a href=\"../comm/card.php?socid=" . $objp->socid . "\">" . dol_trunc($objp->nom, 24) . "</a></td>\n";
                        print "<td colspan=\"2\">" . $langs->trans("Qty");
                        print "</td>";
                        print '<td><input type="hidden" name="factureid" value="' . $objp->factureid . '">';
                        print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>' . $langs->trans("ReductionShort");
                        print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
                        print '</td><td align="right">';
                        print '<input type="submit" class="button" value="' . $langs->trans("Add") . '">';
                        print '</td>';
                        print '</tr>';
                        print '</form>';
                        $i++;
                    }
                } else {
                    print "<tr " . $bc[!$var] . "><td>";
                    print $langs->trans("NoOtherDraftBills");
                    print '</td></tr>';
                }
                print "</table>";
                $db->free($result);
            } else {
                dol_print_error($db);
            }

            print '</td>';
        }

        print '</tr>';
    }

    print '</table>';

    print '<br>';
}


llxFooter();
$db->close();
?>
