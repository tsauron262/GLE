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
class Interfacevalidateorder extends DolibarrTriggers {

    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf) {
        global $conf, $user;
        if ($action == 'ORDER_VALIDATE' || $action == 'PROPAL_VALIDATE') {
            $tabConatact = $object->getIdContact('internal', 'SALESREPSIGN');
            if(count($tabConatact) < 1){
                if(!is_object($object->thirdparty)){
                    $object->thirdparty = new Societe($this->db);
                    $object->thirdparty->fetch($object->socid);
                }
                $tabComm = $object->thirdparty->getSalesRepresentatives($user);
                if(count($tabComm) > 0){
                    $object->add_contact($tabComm[0]['id'], 'SALESREPSIGN', 'internal');
                }
                else{                
                    setEventMessages("Impossible de validé, pas de Commercial signataire", null, 'errors');
                    return -2; 
                }

            }
            
            if($object->cond_reglement_code == "VIDE"){  
                    setEventMessages("Merci de séléctionner les Conditions de règlement", null, 'errors');
                    return -2; 
            }
        }
         
        if ($action == 'ORDER_VALIDATE') {   
            $bvo = new BimpValidateOrder($user->db);
            $code = $bvo->checkValidateRights($user, $object);
            return $code;
        }
        if ($action == 'ORDER_UNVALIDATE') {
            setEventMessages("Impossible de dévalidé", null, 'errors');
            return -2;
        }
        
        
        
        
        //Classé facturé
        if ($action == "BILL_VALIDATE"){
            $facturee = true;
            $object->fetchObjectLinked();
            if(isset($object->linkedObjects['commande'])){
                foreach($object->linkedObjects['commande'] as $comm){
                    $facturee = false;
                    $totalCom = $comm->total_ttc;
                    $totalFact = 0;
                    $comm->fetchObjectLinked();
                    if(isset($comm->linkedObjects['facture'])){
                        foreach($comm->linkedObjects['facture'] as $fact){
                            $totalFact += $fact->total_ttc;
                        }
                    }
                    $diff = $totalCom - $totalFact;
                    if($diff < 0.02 && $diff > -0.02){
                        $facturee = true;
                        $comm->classifybilled($user);
                    }
                }
            }
            if(isset($object->linkedObjects['propal']) && $facturee){
                foreach($object->linkedObjects['propal'] as $prop)
                    $prop->classifybilled($user);
            }
        }
        
        return 0;
    }

}
