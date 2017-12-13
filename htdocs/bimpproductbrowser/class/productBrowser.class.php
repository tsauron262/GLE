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
            foreach ($filles as $fille) {
                $catsToAdd[$i]->tabIdChild[] = $fille->id;
                $catsToAdd[$i]->tabNameChild[] = $fille->label;
            }
        }
        return $catsToAdd;
    }

    function isSatisfied($cat) {
        foreach ($this->id_childs as $id_child) {
            $ind = $this->searchInMotherById($id_child, $cat->mothers);
            if ($ind == -1)
                return false;
        }
        return true;
    }

    function searchRootCats($obj, $cats, $allRestr) {
        $rootCats = array();
        $childsCat = array();
        $selectedCat = array();
        foreach ($cats as $cat1) {
            $isRoot = true;
            if ($this->isImpliedInRestriction($cat1->fk_parent, $allRestr)) {
                $isRoot = false;
            }
            foreach ($cats as $cat2) {
                if ($cat1->id === $cat2->fk_parent) {
                    $isRoot = false;
                    break;
                }
            }
            if ($isRoot) {
                $rootCats[] = $cat1;
                foreach ($allRestr as $restr) {
                    if ($restr->id == $cat1->id) {
                        foreach ($restr->id_childs as $id_child) {
                            $newChild = new Categorie($this->db);
                            $newChild->fetch($id_child);
                            $childsCat[] = $newChild;
                        }
                    }
                }
            } else {
                $selectedCat[] = $cat1;
            }
        }
        $obj->selectedCat = $selectedCat;
        $obj->rootCats = $rootCats;
        $obj->childsCat = $childsCat;
        return $obj;
    }

    function fillChilds($obj, $allRestr) {
        $obj->child = array();
        $newRestr = new BimpProductBrowser($this->db);          // on initialise une nouvelle restriction
        $newCateg = new Categorie($this->db);
        for ($i=0 ; $i<sizeof($obj->childsCat) ; $i++) {
            $child = new stdClass();
            $child->tabIdChild = array();
            $child->tabNameChild = array();
            $child->id = $obj->childsCat[$i]->id;
            $child->label = $obj->childsCat[$i]->label;
            $filles = $obj->childsCat[$i]->get_filles();
            foreach ($filles as $fille) {
                foreach ($obj->selectedCat as $catSelected) {
                    if ($catSelected->id === $fille->id) {
                        $child->selectedId = $fille->id;
                        $child->selectedLabel = $fille->label;
                        $child->cnt++;
                    }
                }
                $child->tabIdChild[] = $fille->id;
                $child->tabNameChild[] = $fille->label;
            }
            if ($child->selectedId != null and $newRestr->fetch($child->selectedId) == 1) {
                foreach ($newRestr->id_childs as $id_child_restr) {
                    $newCateg->fetch($id_child_restr);
                    array_push($obj->childsCat, $newCateg);
                }
            }
            $obj->child[] = $child;
        }
        return $obj;
    }

    function isImpliedInRestriction($id, $allRestr) {
        foreach ($allRestr as $restr) {
            if (in_array($id, $restr->id_childs) != false) {
                return true;
            }
        }
        return false;
    }

    function getRemainingRestr($obj, $cats) {
        $allRestr = array();

        foreach ($cats as $cat) {
            $newRestr = new BimpProductBrowser($this->db);          // on initialise une nouvelle restriction
            if ($newRestr->fetch($cat->id) !== -1)
                $allRestr[] = $newRestr;
        }
        return $allRestr;
    }

    function getAnnexesCats($obj, $cats) {
        $allRestr = $this->getRemainingRestr($obj, $cats->prod);
        $obj = $this->searchRootCats($obj, $cats->prod, $allRestr);
        $obj = $this->fillChilds($obj, $allRestr);


        $obj->waysAnnexesCategories = $this->getAllWays($obj->rootCats);
        unset($obj->rootCats);
        unset($obj->childsCat);
        unset($obj->selectedCat);
        return $obj;
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
    
    function getRestriction($id_cat){
        $sql = 'SELECT fk_child_cat';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'bimp_cat_cat';
        $sql.= ' WHERE fk_parent_cat = ' . $id_cat;
        
        $tabResult = array();
        $result = $this->db->query($sql);
        if($this->db->num_rows($result) < 1)
            return false;//pas de restriction
        else{
            while($ligne = $this->db->fetch_object($result))
                $tabResult[] = $ligne->fk_child_cat;
        }
        return $tabResult;
    }
    
    
    function getStatut($id_prod){
        global $conf;
        $catsT = $this->getProdCateg($id_prod);
        
//        $prod = new Product($this->db);
//        $prod->fetch($id_prod);
//        $prod->getCate
        $cats = array($conf->global->BIMP_ROOT_CATEGORY => array());
        $catsRestr = array();
        $catsNonRestr = array();
        $idsCatObligatoir = array();
        $idsRestrNonSatisfaite = array();
        $catOK = array();
        
        $catT = new Categorie($this->db);
        
        foreach($catsT->prod as $cat){
            $cats[$cat->id] = $cat;
        }
        foreach($cats as $catId => $cat){
            $result = $this->getRestriction($catId);
            if(!$result)
                $catsNonRestr[$catId] =  $cat->print_all_ways();//array("nom"=>$cat->label,"id"=>$catId);
            else{
                $catsRestr[$catId] =  $catId;
                foreach($result as $catOb)
                    $idsCatObligatoir[$catOb] = $catOb;//Attention que des id
            }
        }
        
        foreach($idsCatObligatoir as $idCatObligatoir){//On parcoure les obligation et on en cherche une non satisfaite
            $ok = false;
            $catT->fetch($idCatObligatoir);
            foreach($cats as $cat){
                if($cat->fk_parent == $idCatObligatoir){
                        $catOK[$idCatObligatoir] = array("nomMere"=>$catT->label,"idMere"=>$idCatObligatoir, "nomFille"=>$cat->label, "idFille"=>$cat->id);
                        $ok =true;
                        Unset($catsNonRestr[$cat->id]);
                    break;
                }
            }
            
            if(!$ok){
                $idsRestrNonSatisfaite[$idCatObligatoir] = array("nomMere"=>$catT->label,"idMere"=>$idCatObligatoir);
            }
        }
        
        
        $result = array("ROOT_CATEGORY"=>$conf->global->BIMP_ROOT_CATEGORY, "catOk" => $catOK, "waysAnnexesCategories" => $catsNonRestr, "restrictionNonSatisfaite" => $idsRestrNonSatisfaite);
        
        
        
        
        if(count($idsRestrNonSatisfaite) > 0){
            $result["catAChoisir"] = array();
            foreach($idsRestrNonSatisfaite as $idT => $inut){
                $catT->fetch($idT);
                $filles = $catT->get_filles();
                foreach($filles as $catT2)
                    $result["catAChoisir"][$catT2->id] =  array("nom"=>$catT2->label,"id"=>$catT2->id);
                break;
            }
        }
        
        
//        echo "<pre>";
//        print_r($result);
//        die;
        
        return $result;
    }

    function getOldWay($id_prod) {
        if(GETPOST("test") == "test")
            return $this->getStatut($id_prod);
        global $conf;
        $obj = new stdClass();
        $obj->ROOT_CATEGORY = $conf->global->BIMP_ROOT_CATEGORY;
        if ($obj->ROOT_CATEGORY === null) {
            return $obj;
        }
        $restr = new BimpProductBrowser($this->db);         // Création et initialisation
        $restr->fetch($conf->global->BIMP_ROOT_CATEGORY);   // de la premère restriction
        //  $cat->prod : les catégories ratachées au produit
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
        $remainingCat = array();
        $cntRestr = 0;                              // TODO enlever la condition ci-dessous ($cntRestr < 100)
        while ($cntRestr < sizeof($remainingRestr) && $cntRestr < 100) {   // tant qu'il reste des restrictions à appliquer
//            $remainingRestr[$cntRestr]->toString(); // TODO enlever
            foreach ($remainingRestr[$cntRestr]->id_childs as $id_child) {      // pour chaque restriction imposées
                $ind = $this->searchInMotherById($id_child, $cat->mothers);             // chercher si un catégorie est fille de la restriction
                if ($ind >= 0) {                                            // si c'est le cas (donc si la restriction est satisfaite)
                    $selectedCategId = $cat->prod[$ind]->id;                // on prend l'identifiant de cette catégorie
                    $newRestr = new BimpProductBrowser($this->db);          // on initialise une nouvelle restriction
                    $obj->catArr[] = $cat->prod[$ind]->id;
                    $obj->catsToAdd[$obj->cnt]->selectedLabel = $cat->prod[$ind]->label;
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
                    $obj = $this->getAnnexesCats($obj, $cat);
//                    echo "Toutes les restrictions ne sont pas satisfaites\n"; // TODO enlever
                    return $obj;    // Toutes les restrictions ne sont pas satisfaites
                }
            }
            $cntRestr++;
//            if ($cntRestr == sizeof($remainingRestr)) {                     // si on s'apprète à sortir de la boucle
//                foreach ($cat->prod as $key => $remainingCateg) {                    // on boucle sur les catégories restantes
//                    $newRestr = new BimpProductBrowser($this->db);          // on initialise une nouvelle restriction
//                    if ($newRestr->fetch($remainingCateg->id) == 1) {                // si une catégorie correspond à une restriction  and !$newRestr->isSatisfied($cat)
//                        $obj->cntRestr[] = sizeof($newRestr->id_childs);    // on ajouter le nombre de fils au tableau des compteur de restriction
//                        $remainingRestr[] = $newRestr;                      // on ajoute la nouvelle restriction
//                        $obj->catsToAdd = $this->pushInCatsToAdd($obj->catsToAdd, $newRestr);   // on remplie le tableau des catégories à traiter
//                        $remainingCat[] = $cat->prod[$key];                 // on ajoute la catégorie mère d'une restriction (pour qu'elle apparaissent dans les catégories annexes)
//                        array_splice($cat->prod, $key, 1);                  // on enlève la catégorie correspondante (car elles ne sont pas à afficher)
//                        array_splice($cat->mothers, $key, 1);               // et sa mère
//                    }
//                }
//            }
        }
        $obj = $this->getAnnexesCats($obj, $cat);
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
