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
        $objOut->deletion  = $cntDeletion;
        return  $objOut;
    }

    function print_spaces($depth) {
        for ($i = 0; $i < $depth; $i++) {
            print "----";
        }
    }

    function toString($depth = 0) {
        $this->print_spaces($depth);
        print $this->id . "<br>";
        foreach ($this->child as $child) {
            if ($child->is_a_leaf) {
                $child->print_spaces($depth + 1);
                print $child->id . '<br>';
            } else {
                $child->toString(++$depth);
            }
        }
    }

    /* @var $id type */

    function getChildCategory($id) {
        $this->fetch($id);
        $objOut = null;
        $currentCateg = new Categorie($this->db);
        $currentCateg->fetch($id);
        $tabCateg = $currentCateg->get_filles();
        $objOut->tabIdChild = array();
        $objOut->tabNameChild = array();
        $objOut->id = $currentCateg->id;
        foreach ($tabCateg as $categ) {
            if (in_array($categ->id, $this->id_childs)){
                array_push($objOut->tabIdChild, $categ->id);
                array_push($objOut->tabNameChild, $categ->label);
            }
        }
        return $objOut;
    }

}

/*
		for ($i=0 ; $i<sizeof($idChecked) ; $i++)
		{
			$id1 = $idChecked[$i];
			for ($j=$i+1 ; $j<sizeof($idChecked) ; $j++)
			{
				$id2 = $idChecked[$j];
				$sql = 'SELECT *';
				$sql.= ' FROM '.MAIN_DB_PREFIX.'categorie';
				$sql.= ' WHERE rowid = '.$id2;
				$sql.= ' AND fk_parent = '.$id1;
				$result1 = $this->db->query($sql);

				$sql = 'SELECT *';
				$sql.= ' FROM '.MAIN_DB_PREFIX.'bimp_cat_cat';
				$sql.= ' WHERE fk_child_cat = '.$id2;
				$sql.= ' AND fk_parent_cat = '.$id1;
				$result2 = $this->db->query($sql);

				if (mysqli_num_rows ($result1) == 1 && mysqli_num_rows ($result2) == 0) // true = $id1 parent of $id2
				{
					$sql ='INSERT IGNORE INTO '.MAIN_DB_PREFIX.'bimp_cat_cat (fk_parent_cat, fk_child_cat) ';
				    $sql.='VALUES ('.$id1.', '.$id2.');';
				    try
				    {
				        $this->db->query($sql);
				        $this->db->commit();
				    }
				    catch(Exception $e)
				    {
				        echo 'ERROR:'.$e->getMessage();
				        $this->db->rollback();
				    }
				}
			}
		}
		for ($i=0 ; $i<sizeof($idUnchecked) ; $i++)
		{
			$id1 = $idUnchecked[$i];
			for ($j=$i+1 ; $j<sizeof($idUnchecked) ; $j++)
			{
				$id2 = $idUnchecked[$j];
				echo '$id1 ='.$id1.' $id2 ='.$id2.' $i='.$i.' $j='.$j."\n";
				$sql = 'DELETE';
				$sql.= ' FROM '.MAIN_DB_PREFIX.'bimp_cat_cat';
				$sql.= ' WHERE fk_child_cat = '.$id2;
				$sql.= ' AND fk_parent_cat = '.$id1;
				try
				{
					$this->db->query($sql);
				} catch(Exception $e)
			    {
			        echo 'ERROR:'.$e->getMessage();
			        $this->db->rollback();
				}
			}
		}
	}
*/
/*		foreach ($arrayofid as $id1) {
			foreach ($arrayofid as $id2) {
				$sql = 'SELECT *';
				$sql.= ' FROM '.MAIN_DB_PREFIX.'categorie';
				$sql.= ' WHERE rowid = '.$id2;
				$sql.= ' AND fk_parent = '.$id1;
				// $sql.= ' LIMIT 1';
				$result = $this->db->query($sql);

				if ($result) {		// true = $id1 parent of $id2
					echo $sql."\n";

					$sql ='INSERT INTO '.MAIN_DB_PREFIX.'bimp_cat_cat (fk_parent_cat, fk_child_cat) ';
				    $sql.='VALUES ('.$id1.', '.$id2.');';

				    try
				    {
				        echo $sql."\n";
				        $this->db->query($sql);
				        $this->db->commit();
				    }
				    catch(Exception $e)
				    {
				        echo 'ERROR:'.$e->getMessage();
				        $this->db->rollback();
				    }
				}
			}
		}*/


				/**
	 *  Load an import profil from database
	 *
	 *  @param		int		$id		Id of profil to load
	 *  @return		int				<0 if KO, >0 if OK
	 */
/*	function fetchAllFromRoot($id, $ind=0)
	{
//	print "Début ".$id."<br>" ;
		$sql = 'SELECT fk_parent_cat, fk_child_cat';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'bimp_cat_cat';
		$sql.= ' WHERE fk_parent_cat = '.$id;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			if (mysqli_num_rows ($result) >= 1)	// is a node
			{
				$i=0;
				while ($obj = $result->fetch_object())
				{
					$this->id			= $obj->fk_parent_cat;
					$this->id_child[]	= $obj->fk_child_cat;

					$newChild = new ProductBrowser($this->db);
					$newChild->id = $this->id_child[$i];
					$newChild->id_parent = $this->id;
					if($newChild->fetchAllFromRoot($newChild->id, $ind) ==2) {
						++$ind;
					}
					array_push($this->child, $newChild);
					++$i;
				}
				return 1;
			}
			else // is a leaf
			{
				$this->ref_product = 'TODO';
				$this->is_a_leaf = true;
				$sql = 'SELECT rowid';
				$sql.= ' FROM '.MAIN_DB_PREFIX.'categorie';
				$sql.= ' WHERE fk_parent = '.$this->id_parent;
				$sql.= ' LIMIT '.$ind.',1';

				dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
				$result = $this->db->query($sql);

				if ($result)
				$i=0;
				while ($obj = $result->fetch_object())
				{
					$this->id			= $obj->rowid;
					++$i;
				}
				return 2;	
			}
		}
		else // not results
		{
		// dol_print_error($this->db);
		// return -3;
			return -3;
		}
	}
*/
