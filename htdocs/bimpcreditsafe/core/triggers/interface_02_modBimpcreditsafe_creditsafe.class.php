<?php

/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013-2014 Marcos García        <marcosgdf@gmail.com>
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

include_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';
include_once DOL_DOCUMENT_ROOT . '/bimpvalidateorder/class/bimpvalidateorder.class.php';

/**
 *  Class of triggers for validateorder module
 */
class Interfacecreditsafe extends DolibarrTriggers {
    private $defaultCommEgalUser = true;
    public $errors = array();

    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf) {
        global $conf, $user;
        
        if($action == "ORDER_VALIDATE" || $action == "PROPAL_VALIDATE"){
            if(!$this->testSiret($object)){
                setEventMessages("Veuillez renseigner le numéro de siret", null, 'errors');
                return -2;
            }
        }
        
    }
    
    public function testSiret($object){
        $soc = new Societe($this->db);
        $soc->fetch($object->socid);
        
        if(isset($object->array_options) && isset($object->array_options['options_type']) && $object->array_options['options_type'] == "S")
            return true;
        
        $code = ($soc->idprof1 != "" ? $soc->idprof1 : $soc->idprof2);
        if(strlen($code) > 5)
            return 1;
        
//        $soc->fetch_optionals();
        if($soc->typent_code == "TE_PRIVATE" || $soc->typent_code == "TE_ADMIN")
            return 1;
        
        
        if($soc->country_id != 1)
            return 1;
        
        if($soc->parent > 1)
            return 1;
        
        
        return 0;
    }

}
