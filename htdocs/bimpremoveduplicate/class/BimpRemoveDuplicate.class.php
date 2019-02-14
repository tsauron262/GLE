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
 * 	\file       htdocs/bimpremoveduplicate/class/BimpRemoveDuplicate.class.php
 * 	\ingroup    bimpremoveduplicate
 * 	\brief      Chose 
 */
//$path = dirname(__FILE__) . '/';
//require_once($path . '../../htdocs/main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

class BimpRemoveDuplicateCustomer {

    private $db;
    public $errors;

    /**
     * 	Constructor
     *
     *  @param		DoliDB		$db     Database handler
     */
    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    public function getAllDuplicate($limit = 100, $detail = true) {
        $customers = array();

        // TODO nom + code postal
        $sql = 'SELECT CONCAT(nom, "_", zip) as group_key, COUNT(*) as counter';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe';
        $sql .= ' GROUP BY group_key';
        $sql .= ' HAVING counter > 1';
        $sql .= ' LIMIT ' . $limit;

        $result = $this->db->query($sql);
        if ($result) {
            while ($obj = $this->db->fetch_object($result)) {
                if ($detail = true)
                    $customers[] = $this->getDetail($obj->group_key);
                else
                    $customers[] = $obj;
            }
        } else {
            $this->errors[] = "Aucun doublon n'est présent dans la base";
            return 0;
        }
        return $customers;
    }

    private function getDetail($group_key, $link = true) {
        $details = array();

        $sql = 'SELECT *';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe';
        $sql .= ' WHERE CONCAT(nom, "_", zip)="' . $group_key . '"';

        $result = $this->db->query($sql);
        if ($result) {
            while ($obj = $this->db->fetch_object($result)) {
                if ($link) {
                    $societe = new Societe($this->db);
                    $societe->id = $obj->rowid;
                    $obj->link = $societe->getNomUrl(1);
                }
                $details[] = $obj;
            }
        } else {
            $this->errors[] = "Aucun client a pour nom: $nom";
            return 0;
        }
        return $details;
    }

    public function deleteCustomer($ids_to_delete) {
        $societe = new Societe($this->db);
        $out = array(
            -1 => array(),
            0 => array(),
            1 => array()
        );
        foreach ($ids_to_delete as $id) {
            $societe->errors = array();
            $societe->id = $id;
            $return_soc = $societe->delete($id);
            $out[$return_soc][] = array(
                'id' => $id,
                'error' => $societe->error,
                'errors' => $societe->errors);
        }
        return $out;
    }

//    public function getAllCustomerFiltered($from, $to) {
//        $customer = $this->getAllCustomer($from, $to);
//        
//    }
//    public function getAllCustomer($from, $to) {
//
//        $customers = array();
//
//        $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX . 'societe';
//        $sql .= ' ORDER BY nom';
//        $sql .= ' LIMIT 100';
//
//        $result = $this->db->query($sql);
//        if ($result) {
//            while ($obj = $this->db->fetch_object($result)) {
//                $customers[] = $obj;
//            }
//        }
////        levenshtein($sql, $str2)
//
//        return $customers;
//    }
//
//    public function getFilterByLevenshtein($customer) {
//        
//    }
}
