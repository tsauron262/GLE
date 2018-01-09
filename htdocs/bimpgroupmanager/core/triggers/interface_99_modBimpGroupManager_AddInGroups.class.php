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

/**
 *  \file       
 *  \ingroup    
 *  \brief      File of class of triggers for add a user in multiple groups
 */
require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpgroupmanager/class/BimpGroupManager.class.php';

/**
 *  Class of triggers for groupmanager module
 */
class InterfaceAddInGroups extends DolibarrTriggers {

    // <0 si ko, 0 si aucune action faite, >0 si ok 
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf) {
        if ($action == 'USER_SETINGROUP') {
            $gm = new BimpGroupManager($user->db);
            $gm->insertInGroups($object->id, $object->newgroupid);
        }
    }
}