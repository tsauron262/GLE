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

class BimpProductBrowser extends CommonObject {

    var $cats = array();

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

    /* Create a link between a category and a product */

    function addProdToCat($id_prod, $id_categ) {
        if ($id_categ != 0) {
            $sql = 'INSERT IGNORE INTO ' . MAIN_DB_PREFIX . 'categorie_product (fk_categorie, fk_product)';
            $sql .= 'VALUES (' . $id_categ . ',' . $id_prod . ')';
            try {
                $this->db->query($sql);
                $this->db->commit();
            } catch (Exception $e) {
                echo 'ERROR:' . $e->getMessage();
                $this->db->rollback();
            }
        }
    }

    /* Delete a link between some categories and a product ($id_cat_out is an Array) */

    function deleteSomeCateg($id_prod, $id_cat_out) {
        foreach ($id_cat_out as $id_cat) {
            $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'categorie_product';
            $sql .= ' WHERE fk_product =' . $id_prod . ' AND';
            $sql .= ' fk_categorie=' . $id_cat;
            try {
                $this->db->query($sql);
                $this->db->commit();
            } catch (Exception $e) {
                echo 'ERROR:' . $e->getMessage();
                $this->db->rollback();
            }
        }
    }

    /* get all categories attached to a product */

    function getProdCateg($id_prod) {
        $sql = 'SELECT fk_categorie';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'categorie_product';
        $sql .= ' WHERE fk_product = ' . $id_prod;
        $result = $this->db->query($sql);
        if ($result) {
            while ($obj = $this->db->fetch_object($result)) {
                $categ = new Categorie($this->db);
                $categ->fetch($obj->fk_categorie);
                $this->cats[$categ->id] = $categ;
            }
            return true;
        } else {
            dol_print_error($this->db);
            return -3;
        }
    }

    /* Get category(s) implied by the cat with the id $id_cat */

    function getChilds($id_cat) {
        global $conf;
        $sql = 'SELECT fk_child_cat';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bimp_cat_cat';
        $sql .= ' WHERE fk_parent_cat = ' . $id_cat;

        
        $tabResult = array();
        if($conf->global->BIMP_CATEGORIZATION_DESCENDRE && count($this->gatCatChilds($id_cat)) > 0)
            $tabResult[] = $id_cat;
        
        $result = $this->db->query($sql);
        if ($this->db->num_rows($result) < 1)
            return $tabResult; //pas de restriction
        else {
            while ($ligne = $this->db->fetch_object($result))
                $tabResult[] = $ligne->fk_child_cat;
        }
        return $tabResult;
    }
    
    

    function gatCatChilds($id_cat) {
        $sql = 'SELECT rowid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'categorie';
        $sql .= ' WHERE fk_parent = ' . $id_cat;
        
//        echo $sql;

        $tabResult = array();
        $result = $this->db->query($sql);
        if ($this->db->num_rows($result) < 1)
            return $tabResult; //pas de restriction
        else {
            while ($ligne = $this->db->fetch_object($result))
                $tabResult[] = $ligne->rowid;
        }
        return $tabResult;
    }

    /**
     * Get every categories of the product. It consider also category that have
     * to be set (and isn't set yet).
     * 
     * @param type $id_prod the id of the product
     * @return type Array with multiple off key-value combinaison
     */
    function    getOldWay($id_prod) {
        global $conf;
        $this->cats[$conf->global->BIMP_ROOT_CATEGORY] = array();
        $this->getProdCateg($id_prod);

//        $prod = new Product($this->db);
//        $prod->fetch($id_prod);
//        $prod->getCate
        $catsRestr = array();
        $catsNonRestr = array();
        $idsCatObligatoir = array();
        $idsRestrNonSatisfaite = array();
        $catOK = array();

        $catT = new Categorie($this->db);

        
        foreach ($this->cats as $catId => $cat) {
            $result = $this->getChilds($catId);
            if (!$result && $catId != $conf->global->BIMP_ROOT_CATEGORY)
                $catsNonRestr[$catId] = $cat->print_all_ways(); //array("nom"=>$cat->label,"id"=>$catId);
            else {
                $catsRestr[$catId] = $catId;
                foreach ($result as $catOb)
                    $idsCatObligatoir[$catOb] = $catOb; //Attention que des id
            }
        }

        if ($conf->global->BIMP_CATEGORIZATION_MODE == 2) {
            //Verification des autres condition sinon on enleve des obligatoir 
            foreach ($idsCatObligatoir as $idCat) {
                if (!$this->is_cat_oblig($idCat)) {
                    Unset($idsCatObligatoir[$idCat]);
                }
            }
        }

        foreach ($idsCatObligatoir as $idCatObligatoir) {//On parcoure les obligation et on en cherche une non satisfaite
            $ok = false;
            $catT->fetch($idCatObligatoir);
            foreach ($this->cats as $cat) {
                if ($cat->fk_parent == $idCatObligatoir) {
                    $catOK[$idCatObligatoir] = array("nomMere" => $catT->label, "idMere" => $idCatObligatoir, "nomFille" => $cat->label, "idFille" => $cat->id);
                    $ok = true;
                    Unset($catsNonRestr[$cat->id]);
                    break;
                }
            }

            if (!$ok) {
                $idsRestrNonSatisfaite[$idCatObligatoir] = array("nomMere" => $catT->label, "idMere" => $idCatObligatoir);
            }
        }


        $result = array("ROOT_CATEGORY" => $conf->global->BIMP_ROOT_CATEGORY, "catOk" => $catOK, "waysAnnexesCategories" => $catsNonRestr, "restrictionNonSatisfaite" => $idsRestrNonSatisfaite);




        if (count($idsRestrNonSatisfaite) > 0) {
            $result["catAChoisir"] = array($conf->global->BIMP_ROOT_CATEGORY => array());
            if ($conf->global->BIMP_CATEGORIZATION_RECURSIVE == 1) {
                foreach ($idsRestrNonSatisfaite as $idT => $inut) {
                    $catT->fetch($idT);
                    $filles = $catT->get_filles();
                    $result["catAChoisir"]["idMere"] = $catT->id;
                    $result["catAChoisir"]["labelMere"] = $catT->label;
                    for ($i = 0; $i < sizeof($filles); $i++) {
                        $newFilles = $filles[$i]->get_filles();
                        if (!empty($newFilles)) {
                            $filles = array_merge($filles, $newFilles);
                        } else {
                            $result["catAChoisir"][$filles[$i]->id] = array("nom" => $filles[$i]->label, "id" => $filles[$i]->id);
                        }
                    }
                    break;
                }
            } else {
                foreach ($idsRestrNonSatisfaite as $idT => $inut) {
                    $catT->fetch($idT);
                    $filles = $catT->get_filles();
                    $result["catAChoisir"]["idMere"] = $catT->id;
                    $result["catAChoisir"]["labelMere"] = $catT->label;
                    foreach ($filles as $catT2)
                        $result["catAChoisir"][$catT2->id] = array("nom" => $catT2->label, "id" => $catT2->id);
                    break;
                }
            }
        }
        return $result;
    }

    function is_cat_oblig($idCat) {
        $catMere = $this->getParents($idCat);
        foreach ($catMere as $idCat2) {
            $trouver = false;
            foreach ($this->cats as $idCat3 => $inut)
                if ($idCat2 == $idCat3)
                    $trouver = true;
            if (!$trouver)//une mere n'est pas presente
                return false;
        }
        return true;
    }

    function getParents($id) {
        $sql = 'SELECT fk_parent_cat';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bimp_cat_cat';
        $sql .= ' WHERE fk_child_cat = ' . $id;

        $tabResult = array();
        $result = $this->db->query($sql);
        if ($this->db->num_rows($result) < 1)
            return false; //pas de restriction
        else {
            while ($ligne = $this->db->fetch_object($result))
                $tabResult[] = $ligne->fk_parent_cat;
        }
        return $tabResult;
    }

    /* Used by the hook to determine if the product is fully categorized */

    function productIsCategorized($id_prod) {
        $obj = $this->getOldWay($id_prod);

        if ($obj['restrictionNonSatisfaite'] != null)
            return 0;
        else
            return 1;
    }

}

class BimpProductBrowserConfig extends CommonObject {
    /* En mode 2 le module tourne a l'anvers 
     * (c'est chez l'enfant que lon choisie quelle categorie mere nous implique)
     * 
     * En mode 1 
     * C'est chez la mere que l'on choisie les categorie enfant que l'on implique
     */

    public $id;      // id of the parent category
    public $id_childs = array();
    public $mode = 1;

    function __construct($db) {
        $this->db = $db;
    }

    function getTabCategCheck() {
        global $conf;
        $tabCategCheck = array();
        if($this->mode == 2)
            $result = $this->db->query("SELECT DISTINCT(rowid) as id_mere FROM `".MAIN_DB_PREFIX."categorie` WHERE type = 0");
        else
            $result = $this->db->query("SELECT DISTINCT(fk_parent) as id_mere FROM `" . MAIN_DB_PREFIX . "categorie` WHERE type = 0");

        while ($ligne = $this->db->fetch_object($result))
            $tabCategCheck[] = $ligne->id_mere;
        //print_r($tabCategCheck);die;
        return $tabCategCheck;
    }

    /**
     *  Load an import profil from database
     */
    function fetch($id, $mode) {
        $this->mode = $mode;
        if($this->mode == 2){            
            $sql = 'SELECT fk_parent_cat as fk_child_cat';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bimp_cat_cat';
            $sql .= ' WHERE fk_child_cat = ' . $id;
        } else {
            $sql = 'SELECT fk_child_cat';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bimp_cat_cat';
            $sql .= ' WHERE fk_parent_cat = ' . $id;
        }


        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        $this->id = $id;
        $this->id_childs = array();
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                array_push($this->id_childs, $obj->fk_child_cat);
            }
            return 1;
        } else {
            return -1;
        }
    }

    /* Create a link between a category and an other category */

    function insertRow($id_parent, $id_child) {
        global $conf;
        $sql = 'INSERT IGNORE INTO ' . MAIN_DB_PREFIX . 'bimp_cat_cat ';
        if($this->mode == 2)
            $sql .= '(fk_child_cat, fk_parent_cat) ';
        else
            $sql .= '(fk_parent_cat, fk_child_cat) ';
        $sql .= 'VALUES (' . $id_parent . ', ' . $id_child . ');';
        try {
            $this->db->query($sql);
            $this->db->commit();
        } catch (Exception $e) {
            echo 'ERROR:' . $e->getMessage();
            $this->db->rollback();
        }
    }

    /* Delete a link between a category and an other category */

    function deleteRow($id_parent, $id_child) {
        global $conf;
        $sql = 'DELETE';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bimp_cat_cat';
        if($this->mode == 2){
            $sql .= ' WHERE fk_child_cat = ' . $id_parent;
            $sql .= ' AND fk_parent_cat = ' . $id_child;
        } else {
            $sql .= ' WHERE fk_child_cat = ' . $id_child;
            $sql .= ' AND fk_parent_cat = ' . $id_parent;
        }
        try {
            $this->db->query($sql);
        } catch (Exception $e) {
            echo 'ERROR:' . $e->getMessage();
            $this->db->rollback();
        }
    }

    /* Add and/or suppress row(s) from the bimp_cat_cat table */

    function changeRestrictions($checkboxs) {
        $objOut = null;
        $cntInsertion = 0;
        $cntDeletion = 0;

        for ($i = 0; $i < sizeof($checkboxs); $i++) {
            $id_f = $checkboxs[$i]['id'];
            if (/* $this->id != $id_f and */!in_array($id_f, $this->id_childs) and $id_f !== '') {
                $this->insertRow($this->id, $id_f);
                ++$cntInsertion;
            }
        }
        foreach ($this->id_childs as $child) {
            $cocher = false;
            foreach ($checkboxs as $checkbox) {
                if ($child == $checkbox['id']) {
                    $cocher = true;
                }
            }
            if (!$cocher) {
                $this->deleteRow($this->id, $child);
                ++$cntDeletion;
            }
        }
        $objOut->insertion = $cntInsertion;
        $objOut->deletion = $cntDeletion;
        return $objOut;
    }

}
