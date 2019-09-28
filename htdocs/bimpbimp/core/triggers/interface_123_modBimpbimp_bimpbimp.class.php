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
class InterfaceBimpbimp extends DolibarrTriggers {

    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf) {
        global $conf, $user;
        
        
        if($action == "USER_CREATE" && $object->array_options['options_mail_bienvenue']){
//            $msg = "Bonjour, un nouveau collaborateur : ".$object->getFullName($langs)." viens de rejoindre l'équipe";
            
            $genre = ($object->gender == "man"? "Mr " : ($object->gender == "woman" ? "Mme " : ""));
            $msg = "Bonjour à tous, <br/><br/>Un nouveau collaborateur  ".$genre.$object->getFullName($langs)." vient d'intégrer les équipes";
            
            if($object->town != "")
                $msg .= " de ".$object->town;
            if($object->job != "")
                $msg .= " en qualité de ".$object->job;
            
            $msg .= "<br/>Nous lui souhaitons la bienvenue.<br/><br/>Bien cordialement";
            
            mailSyn2("Nouveau Collaborateur", "go@bimp.fr", "rh@bimp.fr", $msg);
        }
    }

}
