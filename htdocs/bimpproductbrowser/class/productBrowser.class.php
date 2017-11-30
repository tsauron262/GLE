<?php

/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2013 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 * 	\file       htdocs/bimpproductbrowser/class/productBrowser.class.php
 * 	\ingroup    bimpproductbrowser
 * 	\brief      File to filter and display products using categories
 */
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class ProductBrowser extends CommonObject {

    public $id;      // id of the parent ctegorie
    public $id_childs = array();

    /**
     * 	Constructor
     *
     *  @param		DoliDB		$db     Database handler
     */
    function __construct($db) {
        $this->db = $db;
    }

    /**
     * 	Add link into database
     */
    function create() {

        dol_syslog(get_class($this) . '::create', LOG_DEBUG);
    }

    /**
     *  Load an import profil from database
     *
     *  @param		int		$id		Id of profil to load
     *  @return		int				<0 if KO, >0 if OK
     */
    function fetch($id) {
        $sql = 'SELECT fk_child_cat';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'bimp_cat_cat';
        $sql.= ' WHERE fk_parent_cat = ' . $id;

        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        $this->id = $id;
        $this->id_childs = array();
        if ($result) {
            while ($obj = $this->db->fetch_object($result)) {
                array_push($this->id_childs, $obj->fk_child_cat);
            }
            return 1;
        } else {
            dol_print_error($this->db);
            return -3;
        }
    }

    /* Try not to use it in big process */

    function restrictionExistsChildOnly($id_child) {
        $sql = 'SELECT *';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'bimp_cat_cat';
        $sql.= ' WHERE fk_child_cat = ' . $id_child;
        $result = $this->db->query($sql);
        if (mysqli_num_rows($result) >= 1) {
            return true;
        } else {
            return false;
        }
    }

    /* Try not to use it in big process */

    function restrictionExistsParentOnly($id_parent) {
        $sql = 'SELECT *';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'bimp_cat_cat';
        $sql.= ' WHERE fk_parent_cat = ' . $id_parent;
        $result = $this->db->query($sql);
        if (mysqli_num_rows($result) >= 1) {
            return true;
        } else {
            return false;
        }
    }

    /* Try not to use it in big process */

    function restrictionExists($id_parent, $id_child) {
        $sql = 'SELECT *';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'bimp_cat_cat';
        $sql.= ' WHERE fk_child_cat = ' . $id_child;
        $sql.= ' AND fk_parent_cat = ' . $id_parent;
        $result = $this->db->query($sql);
        if (mysqli_num_rows($result) > 0) {
            return true;
        } else {
            return false;
        }
    }

    function insertRow($id_parent, $id_child) {
        $sql = 'INSERT IGNORE INTO ' . MAIN_DB_PREFIX . 'bimp_cat_cat (fk_parent_cat, fk_child_cat) ';
        $sql.= 'VALUES (' . $id_parent . ', ' . $id_child . ');';
        try {
            $this->db->query($sql);
            $this->db->commit();
        } catch (Exception $e) {
            echo 'ERROR:' . $e->getMessage();
            $this->db->rollback();
        }
    }

    function deleteRow($id_parent, $id_child) {
        $sql = 'DELETE';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'bimp_cat_cat';
        $sql.= ' WHERE fk_child_cat = ' . $id_child;
        $sql.= ' AND fk_parent_cat = ' . $id_parent;
        try {
            $this->db->query($sql);
        } catch (Exception $e) {
            echo 'ERROR:' . $e->getMessage();
            $this->db->rollback();
        }
    }

    function changeRestrictions($checkboxs) {
        $objOut = null;
        $cntInsertion = 0;
        $cntDeletion = 0;
        for ($i = 0; $i < sizeof($checkboxs); $i++) {
            $id_f = $checkboxs[$i]['id'];
            $val1 = $checkboxs[$i]['val'];
            if ($val1 == 'true' and $this->id != $id_f and ! in_array($id_f, $this->id_childs)) {
                $this->insertRow($this->id, $id_f);
                ++$cntInsertion;
            } elseif ($val1 == 'false' and in_array($id_f, $this->id_childs)) {
                $this->deleteRow($this->id, $id_f);
                ++$cntDeletion;
            }
        }
        $objOut->insertion = $cntInsertion;
        $objOut->deletion = $cntDeletion;
        return $objOut;
    }

    /* @var $id type */

    function getNextCategory($id) {
        $objOut = null;
        $this->fetch($id);
        $objOut->tabRestr = array();
        for ($i = 0; $i < count($this->id_childs); $i++) {
            $currentCat = new Categorie($this->db);
            $currentCat->fetch($this->id_childs[$i]);
            $objOut->tabRestr[$i]->idParent = $id;
            $objOut->tabRestr[$i]->label = $currentCat->label;
            $objOut->tabRestr[$i]->tabIdChild = array();
            $objOut->tabRestr[$i]->tabNameChild = array();

            $arrChildCat = $currentCat->get_filles();
            for ($j = 0; $j < count($arrChildCat); $j++) {
                $objOut->tabRestr[$i]->tabIdChild[$j] = $arrChildCat[$j]->id;
                $objOut->tabRestr[$i]->tabNameChild[$j] = $arrChildCat[$j]->label;
            }
        }
        return $objOut;
    }

    function addProdToCat($id_prod, $id_categ, $runInit) {
        $objOut = null;
        $this->fetch($id_categ);
        $objOut->tabRestr = array();
        for ($i = 0; $i < count($this->id_childs); $i++) {
            $currentCat = new Categorie($this->db);
            $currentCat->fetch($this->id_childs[$i]);
            $objOut->tabRestr[$i]->idParent = $id_categ;
            $objOut->tabRestr[$i]->label = $currentCat->label;
            $objOut->tabRestr[$i]->tabIdChild = array();
            $objOut->tabRestr[$i]->tabNameChild = array();
            $arrChildCat = $currentCat->get_filles();
            for ($j = 0; $j < count($arrChildCat); $j++) {
                $objOut->tabRestr[$i]->tabIdChild[$j] = $arrChildCat[$j]->id;
                $objOut->tabRestr[$i]->tabNameChild[$j] = $arrChildCat[$j]->label;
            }
        }
        if ($id_categ != 0 && $runInit == false) {
            $sql = 'INSERT IGNORE INTO ' . MAIN_DB_PREFIX . 'categorie_product (fk_categorie, fk_product)';
            $sql.= 'VALUES (' . $id_categ . ',' . $id_prod . ')';
            try {
                $this->db->query($sql);
                $this->db->commit();
            } catch (Exception $e) {
                echo 'ERROR:' . $e->getMessage();
                $this->db->rollback();
            }
        }
        return $objOut;
    }

    function deleteProdCateg($id_prod) {
        $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'categorie_product ';
        $sql.= 'WHERE fk_product =' . $id_prod;
        try {
            $this->db->query($sql);
            $this->db->commit();
        } catch (Exception $e) {
            echo 'ERROR:' . $e->getMessage();
            $this->db->rollback();
        }
    }

    function deleteSomeCateg($id_prod, $id_cat_out) {

        foreach ($id_cat_out as $id_cat) {
            $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'categorie_product';
            $sql.= ' WHERE fk_product =' . $id_prod . ' AND';
            $sql.= ' fk_categorie=' . $id_cat;
            try {
                $this->db->query($sql);
                $this->db->commit();
            } catch (Exception $e) {
                echo 'ERROR:' . $e->getMessage();
                $this->db->rollback();
            }
        }
    }

    function getProdCateg($id_prod) {
        $prodCateg = array();
        $sql = 'SELECT fk_categorie';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'categorie_product';
        $sql.= ' WHERE fk_product = ' . $id_prod;
        $result = $this->db->query($sql);
        if ($result) {
            while ($obj = $this->db->fetch_object($result)) {
                $categ = New Categorie($this->db);
                $categ->fetch($obj->fk_categorie);
                array_push($prodCateg, $categ);
            }
        } else {
            dol_print_error($this->db);
            return -3;
        }
        return $prodCateg;
    }

    function getCategRestrictions() {
        $categRestr = null;
        $categRestr->parent = array();
        $categRestr->child = array();
        $sql = 'SELECT fk_parent_cat, fk_child_cat';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'bimp_cat_cat';
        $sql.= ' ORDER BY fk_parent_cat';
        $result = $this->db->query($sql);
        $i = 0;
        if ($result) {
            while ($obj = $this->db->fetch_object($result)) {
                $categRestr->parent[$i] = $obj->fk_parent_cat;
                $categRestr->child[$i] = $obj->fk_child_cat;
                $i++;
            }
        } else {
            dol_print_error($this->db);
            return -3;
        }
        return $categRestr;
    }

    function getParentLabel($fk_parent) {
        $prodCateg = array();
        $sql = 'SELECT label';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'categorie';
        $sql.= ' WHERE rowid= ' . $fk_parent;
        $result = $this->db->query($sql);
        if ($result) {
            $obj = $this->db->fetch_object($result);
            echo $obj->label;
            return $obj->label;
        } else {
            dol_print_error($this->db);
            return -3;
        }
        return 'Parent inacessible';
    }

//    function createObj($in, $currentRestr) {
//        $indRestrChild = array_search($currentRestr, $in->categRestr->parent);
//        $sizeCat = sizeof($in->prodCateg);
//
//        $change = true;
//        while ($change === true) {
//            $change = false;
//            for ($i = 0; $i < $sizeCat; $i++) {
//                if ($in->prodCateg[$i]->fk_parent === $in->categRestr->child[$indRestrChild]) {
//                    array_push($in->obj->boolRestr, true);
//                    array_push($in->obj->parentId, $in->prodCateg[$i]->fk_parent);
//                    array_push($in->obj->parentLabel, $this->getParentLabel($in->prodCateg[$i]->fk_parent));
//                    array_push($in->obj->label, $in->prodCateg[$i]->label);
//                    array_push($in->obj->selectedId, $in->prodCateg[$i]->id);
//                    $indRestrChild = array_search($in->prodCateg[$i]->id, $in->categRestr->parent);
//                    unset($in->categRestr->child[$indRestrChild]);
//                    unset($in->categRestr->parent[$indRestrChild]);
//                    array_splice($in->prodCateg, $i, 1);
//                    --$sizeCat;
//                    $change = true;
//                    break;
//                }
////                else if (empty($in->prodCateg[$i]->cats)) {
////                    array_push($in->obj->boolRestr, false);
////                    array_push($in->obj->parentId, $in->prodCateg[$i]->fk_parent);
////                    array_push($in->obj->parentLabel, $this->getParentLabel($in->prodCateg[$i]->fk_parent));
////                    array_push($in->obj->label, $in->prodCateg[$i]->label);
////                    array_push($in->obj->selectedId, $in->prodCateg[$i]->id);
////                    array_splice($in->prodCateg, $i, 1);
////                    --$sizeCat;
////                }
//            }
//        }
//        return $in->obj;
//    }

    function createObj($in, $currentRestr) {
        $out = $this->addProdToCat($in->prod, $currentRestr, true);
        if ($in->obj->tabRestrCounter . length <= $in->obj->cnt) {
            $in->obj->tabRestrCounter[$in->obj->cnt] = 0;
        }
        $in->obj->tabRestrCounter[$in->obj->cnt]+=sizeof($out->tabRestr);

        $in->obj->tabRestr = array_merge($in->obj->tabRestr, $out->tabRestr);
        foreach ($out->tabRestr as $restr) {
            foreach ($in->obj->prodCateg as $key => $categ) {            // TODO pop categ after
                $index = array_search($categ->id, $restr->tabIdChild);
                if ($index !== false) {
                    $id = $restr->tabIdChild[$index];
                    foreach ($in->obj->tabRestr as $restrObj) {
                        if ($restr->idParent === $restrObj->idParent &&
                                $restr->label === $restrObj->label) {
                            $restrObj->selectedLabel = $categ->label;
                            array_push($in->obj->catArr, $id);
                            $in->obj->cnt++;
                            array_splice($in->obj->prodCateg, $key, 1);
                        }
                    }
                    $this->createObj($in, $id);
                }
            }
        }
    }

    function getAllCategories($id_prod) {
        $in = null;
        $obj = new stdClass();
        $obj->tabRestr = array();
        $obj->tabRestrCounter = array();
        $obj->catArr = array();
        $obj->cnt = 0;
        $obj->prodCateg = $this->getProdCateg($id_prod);
        $in->obj = $obj;
        $in->prod = $id_prod;


        $this->createObj($in, 0);

        $obj->ways = array();
        $obj->color = array();
        foreach ($obj->prodCateg as $cat) {
            $in->obj->ways[] = $cat->print_all_ways();
            $in->obj->color[] = $cat->color;
        }

        return $in->obj;
    }

}
