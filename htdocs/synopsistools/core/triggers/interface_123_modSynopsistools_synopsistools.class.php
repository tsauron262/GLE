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
class Interfacesynopsistools extends DolibarrTriggers {
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf) {
//        global $conf, $user;
        
        $file = DOL_DOCUMENT_ROOT."/synopsistools/core/triggers/interface_".$conf->global->MAIN_INFO_SOCIETE_NOM . ".class.php";
        if(is_file($file)){
            include_once $file;
            $obj = new InterfaceSpecialSoc($this->db);
            return $obj->runTrigger($action, $object,  $user,  $langs,  $conf);
        }

        return 0;
    }

}
