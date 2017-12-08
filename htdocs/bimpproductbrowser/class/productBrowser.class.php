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

    public $id;      // id of the parent category
    public $id_childs = array();

    /**
     * 	Constructor
     *
     *  @param		DoliDB		$db     Database handler
     */
    function __construct($db) {
        global $conf;
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
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                array_push($this->id_childs, $obj->fk_child_cat);
            }
            return 1;
        } else {
            return -1;
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
            if ($this->id != $id_f and ! in_array($id_f, $this->id_childs) and $id_f !== '') {
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

    function getTabRestr($id_categ) {
        $objOut = null;
        $this->fetch($id_categ);
        sort($this->id_childs);
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
        return $objOut;
    }

    function addProdToCat($id_prod, $id_categ) {
        $objOut = $this->getTabRestr($id_categ);
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
        $cat->prod = array();
        $cat->mothers = array();
        $sql = 'SELECT fk_categorie';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'categorie_product';
        $sql.= ' WHERE fk_product = ' . $id_prod;
        $result = $this->db->query($sql);
        if ($result) {
            while ($obj = $this->db->fetch_object($result)) {
                $categ = New Categorie($this->db);
                $categ->fetch($obj->fk_categorie);
                $mother = New Categorie($this->db);
                $mother->fetch($categ->fk_parent);
                array_push($cat->prod, $categ);
                array_push($cat->mothers, $mother);
            }
        } else {
            dol_print_error($this->db);
            return -3;
        }
        return $cat;
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

    function getAllWays($categs) {
        $ways = array();
        foreach ($categs as $categ) {
            $ways[] = $categ->print_all_ways();
        }
        return $ways;
    }

    function searchInMotherById($id_child, $mothers) {
        for ($i = 0; $i < sizeof($mothers); $i++) {
            if ($mothers[$i]->id == $id_child)
                return $i;
        }
        return -1;
    }

    function pushInCatsToAdd($catsToAdd, $newRestr) {
        foreach ($newRestr->id_childs as $id_child) {
            $i = sizeof($catsToAdd);
            $catsToAdd[$i]->tabIdChild = array();
            $catsToAdd[$i]->tabNameChild = array();
            $categ = new Categorie($this->db);
            $categ->fetch($id_child);
            $catsToAdd[$i]->idParent = $categ->fk_parent;
            $catsToAdd[$i]->label = $categ->label;
            $filles = $categ->get_filles();
            foreach ($filles as $fille ) {
                $catsToAdd[$i]->tabIdChild[] = $fille->id;
                $catsToAdd[$i]->tabNameChild[] = $fille->label;
            }
        }
        return $catsToAdd;
    }

    /*    var objs = [];  
      [
      obj.idParent
      obj.label
      obj.tabIdChild = []
      obj.tabNameChild = []
      selectedLabel
      ]


      var cnt = 0;
      var cntRestr = [];
      var catArr = [];
     */

    function getOldWay($id_prod) {
        global $conf;
        $obj = new stdClass();
        $restr = new BimpProductBrowser($this->db);         // Création et initialisation
        $restr->fetch($conf->global->BIMP_ROOT_CATEGORY);   // de la premère erestriction

        $obj->ROOT_CATEGORY = $conf->global->BIMP_ROOT_CATEGORY;
        if ($obj->ROOT_CATEGORY === null) {
            return $obj;
        }

        //  $cat->prod : les catégories ratéchées au produit
        //  $cat->mothers : et les mères de ces catégories
        $cat = $this->getProdCateg($id_prod);
        $obj->waysAnnexesCategories = array();
        $obj->catsToAdd = array();
        $remainingRestr = array();
        $remainingRestr[] = $restr;
        $obj->catsToAdd = $this->pushInCatsToAdd($obj->catsToAdd, $restr);
        $obj->cnt = 0;
        $obj->cntRestr = array();
        $obj->cntRestr[] = sizeof($restr->id_childs);
        $obj->catArr = array();
        $cntRestr = 0;                              // TODO enlever la condition ci-dessous ($cntRestr < 100)
        while ($cntRestr < sizeof($remainingRestr) != 0 && $cntRestr < 100) {   // tant qu'il reste des restrictions à appliquer
//            $remainingRestr[$cntRestr]->toString(); // TODO enlever
            foreach ($remainingRestr[$cntRestr]->id_childs as $id_child) {      // pour chaque restriction imposées
                $ind = $this->searchInMotherById($id_child, $cat->mothers);             // chercher si un catégorie est fille de la restriction
                if ($ind >= 0) {                                            // si c'est le cas (donc si la restriction est satisfaite)
                    $selectedCategId = $cat->prod[$ind]->id;                // on prend l'identifiant de cette catégorie
                    $newRestr = new BimpProductBrowser($this->db);          // on initialise une nouvelle restriction
                    $obj->catArr[] = $cat->prod[$ind]->id;
                    $obj->catsToAdd[$obj->cnt]->selectedLabel = $cat->prod[$ind]->label ;
                    if ($newRestr->fetch($selectedCategId) == 1) {          // on cherche si cette catégorie implique une restriction
//                        echo 'Cette catégorie : (' . $cat->prod[$ind]->label . ") implique au moins une autre restriction\n";
                        $obj->cntRestr[] = sizeof($newRestr->id_childs);
                        $remainingRestr[] = $newRestr;                      // on ajoute la nouvelle restriction
                        $obj->catsToAdd = $this->pushInCatsToAdd($obj->catsToAdd, $newRestr);
                    } else {
                        $obj->cntRestr[] = 0;
                    }
//                    echo "On enlève la catégorie " . $cat->prod[$ind]->label . " et sa mère " . $cat->mothers[$ind]->label . "\n";
                    array_splice($cat->prod, $ind, 1);                  // on enlève la catégorie correspondante
                    array_splice($cat->mothers, $ind, 1);             // et sa mère
                    $obj->cnt++;
                } else {
//                    echo "Toutes les restrictions ne sont pas satisfaites\n"; // TODO enlever
                    return $obj;    // Toutes les restrictions ne sont pas satisfaites
                }
            }
            $cntRestr++;
        }
        $obj->waysAnnexesCategories = $this->getAllWays($cat->prod);
        return $obj;
    }

    function productIsCategorized($id_prod) {
        $obj = $this->getOldWay($id_prod);
        $cnt = 0;
        foreach ($obj->cntRestr as $count) {
            $cnt += $count;
        }
        if ($cnt == $obj->cnt)
            return 1;
        else if ($cnt > $obj->cnt)
            return 0;
        else
            return -1;
    }

    function toString() {
        echo "id : " . $this->id . "\n";
        echo "id_childs : ";
        foreach ($this->id_childs as $child) {
            echo $child . " ";
        }
        echo "\n";
    }

}
