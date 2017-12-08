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
        $cat->motherId = array();
        $sql = 'SELECT fk_categorie';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'categorie_product';
        $sql.= ' WHERE fk_product = ' . $id_prod;
        $result = $this->db->query($sql);
        if ($result) {
            while ($obj = $this->db->fetch_object($result)) {
                $categ = New Categorie($this->db);
                $categ->fetch($obj->fk_categorie);
                array_push($cat->prod, $categ);
                array_push($cat->motherId, $categ->fk_parent);
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

//    function createObj($obj, $currentRestr) {
//        $out = $this->getTabRestr($currentRestr);
//        if ($obj->tabRestrCounter . length <= $obj->cnt) {
//            $obj->tabRestrCounter[$obj->cnt] = 0;
//        }
//        $tabIdSelected = array();
//        $obj->tabRestrCounter[$obj->cnt]+=sizeof($out->tabRestr);
//        $obj->tabRestr = array_merge($obj->tabRestr, $out->tabRestr);
//        foreach ($out->tabRestr as $restr) {    // restriction
//            $cntSister = 0;
//            foreach ($obj->prodCateg as $key => $categ) {   // categ liées aux prod
//                $index = array_search($categ->id, $restr->tabIdChild); // cherche si la catégorie est fille de la restriction
//                if ($index !== false) { // la catégorie est fille de la restriction
//                    if ($cntSister > 0) {   // si elle a des soeurs
//                        $newRestr = $restr;
//                        $newRestr->label .= ' Rajouté ';
//                        array_push($obj->tabRestr, $newRestr);
//                    }
////                        $newRestr = $restr;
////                        $newRestr->tabRestr = $categ->label;
////                        array_push($out->tabRestr, $newRestr);
//                    $restr->selectedLabel = $categ->label;  // ajout du choix sélectionné antérieurement par l'utilisateur
//                    $id = $restr->tabIdChild[$index];
//                    array_push($obj->catArr, $id);
//                    array_splice($obj->prodCateg, $key, 1);
//                    $obj->cnt++;
//
//                    $cntSister++;
//                    array_push($tabIdSelected, $id);
//                }
//            }
//            foreach ($tabIdSelected as $idSelected) {
//                $this->createObj($obj, $idSelected);
//            }
//        }
//    }

    function getMothersId($prodCateg) {
        $mothersId = array();
        foreach ($prodCateg as $categ) {
            $motherId = $categ->fk_parent;
            array_push($mothersId, $motherId);
        }
        return $mothersId;
    }

    function getWaysAllWays($mothers) {
        $ways = array();
        foreach ($mothers as $cat) {
            $ways[] = $cat->print_all_ways();
        }
        return $ways;
    }
    
    function searchInMotherId($id_child, $motherId) {
        for ($i=0 ; $i < sizeof($motherId) ; $i++){
            if ($motherId[$i] == $id_child)
                return $i;
        }
        return -1;
    }

    function getOldWay($id_prod) {
        global $conf;
        $obj = new stdClass();
        $restr = new BimpProductBrowser($this->db);
        $restr->fetch($conf->global->BIMP_ROOT_CATEGORY);

        $obj->ROOT_CATEGORY = $conf->global->BIMP_ROOT_CATEGORY;
        if ($obj->ROOT_CATEGORY === null) {
            return $obj;
        }

        $cat = $this->getProdCateg($id_prod);
        
        $remainingRestr = array();
        $remainingRestr[] = $restr;
        
        $cntRestr = 0;
        while ($cntRestr < sizeof($remainingRestr) != 0 && $cntRestr < 100) {   // tant qu'il reste des restrictions à appliquer
            $remainingRestr[$cntRestr]->toString();
            foreach ($remainingRestr[$cntRestr]->id_childs as $id_child) {      // pour chaque restriction imposées
                $ind = $this->searchInMotherId($id_child, $cat->motherId);             // chercher si un catégorie est fille de la restriction
                if ($ind >= 0) {                                            // si c'est le cas (donc si la restriction est satisfaite)
                    $selectedCategId = $cat->prod[$ind]->id;                // on prend l'identifiant de cette catégorie
                    $newRestr = new BimpProductBrowser($this->db);          // on initialise une nouvelle restriction
                    if ($newRestr->fetch($selectedCategId) == 1) {          // on cherche si cette catégorie implique une restriction
//                        echo 'Cette catégorie : ('.$cat->prod[$ind]->label.") implique au moins une autre restriction\n";
                        $remainingRestr[] = $newRestr;
                    }
//                    echo "On enlève la catégorie ".$cat->prod[$ind]->label." et sa mère ".$cat->motherId[$ind]."\n";
                    array_splice($cat->prod, $ind, 1);                  // on enlève la catégorie correspondante
                    array_splice($cat->motherId, $ind, 1);             // et sa mère
                } else {
                    return $obj;    // Toutes les restrictions ne sont pas satisfaites
                }
            }
            $cntRestr++;
        }

//        foreach ($prodCateg as $ind => $categ) {
//            echo $ind . "\n";
//            if ($ind == 0) {
//                $remainingRestr->initRestr();
//                $indexFound = in_array($restrId, $mothersId);
//            } else {
//                $remainingRestr->fetch($categ);
//                foreach ($remainingRestr->id_childs as $restrId) {
//                    $indexFound = in_array($restrId, $mothersId);
//                    echo "Index found : (" . $indexFound . ")\n";
//                    if ($indexFound != false) {                    // si une fille correspond à cette restriction
//                        array_splice($prodCateg, $indexFound, 1);  // enlever la fille correspondant
//                        //                    array_splice($mothersId, $indexFound, 1);    // enlever la mère correspondante
//                    } else {            // toutes les restrictions ne sont pas comblé
//                        return $obj;    // finir l'algo
//                    }
//                }
//            }
//        }
        $obj->ways = $this->getWaysAllWays($prodCateg);
        return $obj;
    }

    function productIsCategorized($id_prod) {
        $obj = $this->getOldWay($id_prod);
        $cnt = 0;
        foreach ($obj->tabRestrCounter as $restrCounter) {
            $cnt += $restrCounter;
        }
        if ($cnt == $obj->cnt)
            return 1;
        else if ($cnt > $obj->cnt)
            return 0;
        else
            return -1;
    }

    function toString() {
        echo "id : ".$this->id."\n";
        echo "id_childs : ";
        foreach ($this->id_childs as $child) {
            echo $child." ";
        }
        echo "\n";
    }
}
