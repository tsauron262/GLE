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
 * 	\file       htdocs/bimpremovev2duplicate/class/BimpRemoveDuplicate.class.php
 * 	\ingroup    bimpremovev2duplicate
 * 	\brief      Chose 
 */
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';


class BimpRemoveDuplicateCustomerV2 {

    const MAX_STRING_LENGTH = 25;

    private $db;
    public $errors;
    public $nb_row;
    public $score;
    // Score
    public $s_min;
    public $s_name;
    public $s_email;
    public $s_address;
    public $s_zip;
    public $s_town;
    public $s_phone;
    public $s_siret;
    public static $filename = DOL_DATA_ROOT . '/progress.txt';

    /**
     * 	Constructor
     *
     *  @param		DoliDB		$db     Database handler
     */
    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    public function getAllDuplicate($limit, $commercial, $detail = true) {
        file_put_contents(self::$filename, 0);
        ini_set('memory_limit', '2048M');
        set_time_limit(7200);
        $customers2 = array();

        $sql2 = 'SELECT rowid, nom, email, address, zip, town, phone, datec, siret';
        $sql2 .= ' FROM ' . MAIN_DB_PREFIX . 'societe';
        $sql2 .= ' WHERE duplicate=0';
        $sql2 .= ' ORDER BY rowid';
        $result2 = $this->db->query($sql2);
        if ($result2) {
            while ($obj2 = $this->db->fetch_object($result2)) {
                $customers2[] = $obj2;
            }
        } else {
            $this->errors[] = "Tous les tiers ont été traité";
            return 0;
        }

        $customers = array();
        if (empty($commercial))
            $customers = $customers2;
        else {
            $sql = 'SELECT s.rowid, nom, email, address, zip, town, phone, s.datec, siret';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe s, ' . MAIN_DB_PREFIX . 'societe_commerciaux sc';
            $sql .= ' WHERE duplicate=0';
            $sql .= ' AND sc.fk_soc=s.rowid';
            $sql .= ' AND sc.fk_user IN (' . implode(',', $commercial) . ')';
            $sql .= ' ORDER BY s.rowid';


            $result = $this->db->query($sql);
            if ($result) {
                while ($obj = $this->db->fetch_object($result)) {
                    $customers[] = $obj;
                }
            } else {
                $this->errors[] = "Tous les tiers de ce(s) commercial(ux) ont été traité";
                return 0;
            }
        }
        $this->nb_row = sizeof($customers);


        $clean_customers = $this->cleanLines($customers);
        $clean_customers2 = $this->cleanLines($customers2);
        $duplicates = $this->groupDuplicate($clean_customers, $clean_customers2, $limit);
        $duplicates_clean = $this->cleanLinesAfterGroup($duplicates);
        return $duplicates_clean;
    }

    private function getCommerciaux($id_soc) {
        $commerciaux = array();

        $sql = 'SELECT u.firstname as firstname, u.lastname as lastname';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux as sc';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as u ON sc.fk_user=u.rowid';
        $sql .= ' WHERE sc.fk_soc=' . $id_soc;

        $result = $this->db->query($sql);
        while ($obj = $this->db->fetch_object($result)) {
            $commerciaux[] = $obj->firstname . ' ' . $obj->lastname;
        }
        return $commerciaux;
    }

    /**
     * 
     * @param array $customers all customers pre-selected
     */
    private function cleanLines($customers) {
        $out = array();
        foreach ($customers as $i => $c) {
            $tmp = $c;

            // Nom
            $tmp->nom = strtoupper($tmp->nom);

            // Email
            $tmp->email = strtolower($tmp->email);
            if (strpos($tmp->email, 'refused') != false)
                $tmp->email = '';

            // Address
            if ($tmp->address == 'NULL')
                $tmp->address = '';
            $tmp->address = strtolower($tmp->address);
            $tmp->address = str_replace('avenue', 'av', $tmp->address);
            $tmp->address = str_replace('boulevard', 'bd', $tmp->address);
            $tmp->address = str_replace('chemin', 'ch', $tmp->address);
            $tmp->address = str_replace('route', 'rte', $tmp->address);
            $tmp->address = str_replace(',', '', $tmp->address);

            // Zipcode
            if (strlen($tmp->zip) != 5)
                $tmp->zip = '';

            // Town
            if ($tmp->town == 'NULL')
                $tmp->town = '';
            $tmp->town = strtoupper($tmp->town);

            // Phone
            if ($tmp->phone == 'NULL')
                $tmp->phone = '';
            $tmp->phone = str_replace('+33', '0', $tmp->phone);
            $tmp->phone = str_replace(' ', '', $tmp->phone);
            $tmp->phone = str_replace('.', '', $tmp->phone);
            $tmp->phone = str_replace('-', '', $tmp->phone);
            $tmp->phone = str_replace('/', '', $tmp->phone);

            // Siret
            if ($tmp->siret == 'NULL' or $tmp->siret == NULL or $tmp->siret == null)
                $tmp->siret = '';

            // Date create
            if ($tmp->datec == '0000-00-00 00:00:00')
                $tmp->datec = null;


            $out[] = $tmp;
        }
        return $out;
    }

    private function cleanLinesAfterGroup($duplicates) {


        foreach ($duplicates as $i => $a) {
            foreach ($a as $j => $b) {
                // CLEAN FOR DISPLAY 
                $duplicates[$i][$j]->address = mb_strimwidth($duplicates[$i][$j]->address, 0, self::MAX_STRING_LENGTH, "...");
                $duplicates[$i][$j]->nom = mb_strimwidth($duplicates[$i][$j]->nom, 0, self::MAX_STRING_LENGTH, "...");
                $duplicates[$i][$j]->email = mb_strimwidth($duplicates[$i][$j]->email, 0, self::MAX_STRING_LENGTH, "...");
            }
        }
        return $duplicates;
    }

    /**
     * 
     * @param array $customers all customers pre-selected
     */
    private function groupDuplicate($customers, $customers2, $limit) {
        session_start();

        $cnt_group = 0;
        $ids_processed = array();

        $out = array();

        foreach ($customers as $i => $a) {

            file_put_contents(self::$filename, $i);

            $ids_processed[] = $a->rowid;
            foreach ($customers2 as $j => $b) {
                if ($a->rowid != $b->rowid and $this->compare($a, $b) == 1) {
                    $customers[$i]->not_processed = true;
                    $a->commerciaux = $this->getCommerciaux($a->rowid);
                    $b->commerciaux = $this->getCommerciaux($b->rowid);
                    if (isset($a->grp)) {
                        $grp = $a->grp;
                        $customers2[$j]->grp = $a->grp;
                    } elseif (isset($b->grp)) {
                        $grp = $b->grp;
                        $customers[$i]->grp = $b->grp;
                    } else {
                        $customers[$i]->grp = $cnt_group;
                        $customers2[$j]->grp = $cnt_group;
                        $grp = $cnt_group;
                        $cnt_group++;
                    }
                    $out[$grp][$a->rowid] = $a;
                    $out[$grp][$b->rowid] = $b;

                    if (end($ids_processed) == $a->rowid)
                        array_pop($ids_processed);
                }
            }
            if ($limit <= $i)
                break;
        }
        $this->setAsProcessed($ids_processed);

        return $out;
    }

    public function setAsProcessed($ids_processed) {
        $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'societe';
        $sql .= ' SET duplicate=1';
        $sql .= ' WHERE rowid IN (' . implode(',', $ids_processed) . ')';

        try {
            $this->db->query($sql);
            $this->db->commit();
            return 1;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->db->rollback();
            return 0;
        }
    }

    public function setAsUnprocessed() {
        $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'societe';
        $sql .= ' SET duplicate=0';

        try {
            $this->db->query($sql);
            $this->db->commit();
            return 1;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->db->rollback();
            return -1;
        }
        return 0;
    }

    /**
     * Compare 2 societe
     * @param array $a
     * @param array $b
     * @return int return 1 if they are duplicate else 0
     */
    private function compare($a, $b) {
        $score = 0;
        $score += $this->s_name * $this->compareUnit($a->nom, $b->nom);
        $score += $this->s_email * $this->compareUnit($a->email, $b->email);
        $score += $this->s_address * $this->compareUnit($a->address, $b->address);
        $score += $this->s_zip * $this->compareUnit($a->zip, $b->zip);
        $score += $this->s_town * $this->compareUnit($a->town, $b->town);
        $score += $this->s_phone * $this->compareUnit($a->phone, $b->phone);
        $score += $this->s_siret * $this->compareUnit($a->s_siret, $b->s_siret);

        if ($this->s_min < $score)
            return 1;

        return 0;
    }

    private function compareUnit($a, $b) {
        if ($a != NULL and $a != '' and $b != NULL and $b != '' and strlen($b) > 3 and strlen($a) > 3
                and ( $a == $b or strpos($a, $b) != false or strpos($b, $a) != false)
        ) {
            return 1;
        }
        return 0;
    }

    /**
     * 
     * @param type $src_to_dest array with multiple scr having multiple
     */
    public function mergeDuplicate($src_to_dest) {

        $success = array();

        foreach ($src_to_dest as $src => $dest) {
            
            if(!$src > 0)
                $this->errors[] = "Tier source d'id $src invalide";
            
            if(!$dest > 0)
                $this->errors[] = "Tier source d'id $dest invalide";
            
            if(empty($this->errors))
                $success[$src] = $dest;
            else
                return $success;
            
            $societe_dest = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $dest);
            $societe_dest->mergeSocietes((int) $src);
            
            if(empty($this->errors))
                $success[$src] = $dest;
            else
                return $success;

        }
        return $success;
    }

//    private function addErrorMerge($id_source, $error) {
//        if (isset($this->errors[$id_source]))
//            $this->errors[$id_source] .= html_entity_decode($error);
//        else
//            $this->errors[$id_source] = html_entity_decode($error);
//    }

}
