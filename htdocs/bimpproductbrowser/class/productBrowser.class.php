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
        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimp_cat_cat (fk_parent_cat, fk_child_cat) ';
        $sql.='VALUES (' . $id_parent . ', ' . $id_child . ');';
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

    function addProdToCat($id_prod, $id_categ) {
        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'categorie_product (fk_categorie, fk_product)';
        $sql.= 'VALUES (' . $id_categ . ',' . $id_prod . ')';
        try {
            $this->db->query($sql);
            $this->db->commit();
        } catch (Exception $e) {
            echo 'ERROR:' . $e->getMessage();
            $this->db->rollback();
        }
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

    function deleteSomecateg($id_prod, $id_cat_out) {

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
}
