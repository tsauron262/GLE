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
 * 	\file       /htdocs/bimpgroupmanager/class/BimpGroupManager.class.php
 * 	\ingroup    BimpGroupManager
 * 	\brief      Class
 */
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobjectline.class.php';

require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

class BimpGroupManager {

    /**
     * 	Constructor
     *
     *  @param		DoliDB		$db     Database handler
     */
    var $id;
    var $id_parent;
//    var $name_parent;
    var $id_childs = array();

    function __construct($db) {
        $this->db = $db;
    }

    function create() {
        dol_syslog(get_class($this) . '::create', LOG_DEBUG);
    }

    /**
     *  Load an import profil from database
     */
    function fetch($id) {

//        if ($name != '') {
//            $this->name_parent = $name;
//        } else {
//            $grp = new UserGroup($this->db);
//            $grp->fetch($id);
//            $this->name_parent = $grp->nom;
//        }

        $sql = 'SELECT fk_child';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bimp_grp_grp';
        $sql .= ' WHERE fk_parent = ' . $id;

        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        $this->id = $id;
        $this->id_childs = array();
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $this->id_childs[] = $obj->fk_child;
            }
        }
//        $sql2 = 'SELECT fk_parent';
//        $sql2 .= ' FROM ' . MAIN_DB_PREFIX . 'bimp_grp_grp';
//        $sql2 .= ' WHERE fk_child = ' . $id;
//
//        $result2 = $this->db->query($sql2);
//        if ($result2 and mysqli_num_rows($result2) > 0) {
//            while ($obj2 = $this->db->fetch_object($result2)) {
//                $this->id_parent = $obj2->fk_parent;
//            }
//        }
    }

    /**
     * Function triggered by the client 
     */
    function updateGroup($groupId, $newGroupId) {

        $this->removeChild($groupId);
        if (isset($newGroupId)) {
            $this->addChild($groupId, $newGroupId);
        }
    }

    /* Get groups ID and name   */

    function getAllGroups() {
        $groups = array();

        $sql = 'SELECT rowid, nom';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'usergroup';

        dol_syslog(get_class($this) . "::getAllGroups sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);

        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $groups[$obj->rowid] = $obj->nom;
            }
            return $groups;
        } else {
            return -1;
        }
    }

    /**
     * Other functions
     */
    function removeChild($groupId) {
        $sql = "DELETE ";
        $sql.= " FROM " . MAIN_DB_PREFIX . "bimp_grp_grp";
        $sql.= " WHERE fk_child=" . $groupId;

        dol_syslog(get_class($this) . "::delete child", LOG_DEBUG);

        $result = $this->db->query($sql);
        if (!$result) {
            $this->error = $this->db->error();
        }
    }

    function addChild($id_child, $id_parent) {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "bimp_grp_grp";
        $sql.= " (fk_parent, fk_child)";
        $sql.= " VALUES (" . $id_parent . ", " . $id_child . ")";

        dol_syslog(get_class($this) . "::insert child", LOG_DEBUG);

        $result = $this->db->query($sql);
        if (!$result) {
            $this->error = $this->db->error();
        }
    }

    function getParentId($id) {
        $sql = 'SELECT fk_parent';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bimp_grp_grp';
        $sql .= ' WHERE fk_child = ' . $id;

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                return $obj->fk_parent;
            }
        }
        return -1;
    }

    function getOldGroup() {

        $filledGroups = array();

        /* $allGroups[idGroup] = nomGroup */
        $allGroups = $this->getAllGroups();

        foreach ($allGroups as $id => $name) {
            $gm = new BimpGroupManager($this->db);
            if ($gm->fetch($id) != -1) {
                $filledGroups[$id]['name'] = $name;
                $filledGroups[$id]['childs'] = $gm->id_childs;
            }
        }
        $merged = $this->mergeGroup($allGroups, $filledGroups);
        $rootAdded = $this->addRoots($merged);
        $sorted = $this->sort($rootAdded);
        $parentAdded = $this->addParents($sorted);
        return $parentAdded;
    }

    function mergeGroup($allGroups, $filledGroups) {
        $outGroup = array();
        foreach ($allGroups as $id => $group) {
            if ($filledGroups[$id] != null) {
                $outGroup[$id] = $filledGroups[$id];
            } else {
                $outGroup[$id]['name'] = $group;
            }
        }
        return $outGroup;
    }

    function addRoots($merged) {
        foreach ($merged as $id => $inut) {
            if ($this->getParentId($id) == -1) {
                $merged[$id]['isRoot'] = true;
            } else {
                $merged[$id]['isRoot'] = false;
            }
        }
        return $merged;
    }

    function sort($groups) {

        $out = array();
        $hasParent = array();

        foreach ($groups as $id => $group) {
            if ($group['isRoot'] == true) {
                $group['id'] = $id;
                $hasParent = $this->custMerge($hasParent, $group['childs']);
                $out[] = $group;
                unset($groups[$id]);
            }
        }

        $prevSize = -1;
        while (sizeof($groups) != 0) {
            $newSize = sizeof($groups);
            if ($newSize != $prevSize) {
                foreach ($groups as $id => $group) {
                    if (in_array($id, $hasParent) != null) {
                        $group['id'] = $id;
                        $hasParent = $this->custMerge($hasParent, $group['childs']);
                        $out[] = $group;
                        unset($groups[$id]);
                    }
                }
            } else {
                foreach ($groups as $id => $group) {
                    $group['id'] = $id;
                    $out[] = $group;
                    unset($groups[$id]);
                }
            }
            $prevSize = $newSize;
        }
        return $out;
    }

    function addParents($grps) {
        foreach ($grps as $index => $grp) {
            $id_parent = $this->getParentId($grp['id']);
            if ($id_parent != -1) {
                $grps[$index]['id_parent'] = $id_parent;
            }
        }
        return $grps;
    }

    /* Merge array2 in array1 */

    function custMerge($array1, $array2) {
        foreach ($array2 as $arr2) {
            $array1[] = $arr2;
        }
        return $array1;
    }

    /**
     * used by trigger
     */
    function insertInGroups($userid, $groupid) {
        $groupsId = $this->getAllParents($groupid);
        $groupsIdFiltered = $this->removeGroupsUserOwn($userid, $groupsId);
        $this->addUserInGroups($userid, $groupsIdFiltered);
    }

    function getAllParents($groupid) {

        $parents = array($groupid);
        do {
            $len = sizeof($parents);
            $sql = 'SELECT fk_parent';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bimp_grp_grp';
            $sql .= ' WHERE fk_child = ' . end($parents);

            $result = $this->db->query($sql);
            if ($result and mysqli_num_rows($result) > 0) {
                while ($obj = $this->db->fetch_object($result)) {
                    $parents[] = $obj->fk_parent;
                }
            }
        } while ($len != sizeof($parents));

        array_shift($parents);
        return $parents;
    }

    /* Remove group which already contain the use (in order not to duplicate that user) */

    function removeGroupsUserOwn($userid, $groupsId) {

        $sql = 'SELECT fk_usergroup';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'usergroup_user';
        $sql .= ' WHERE fk_user=' . $userid;

//        foreach ($groupsId as $id) {
//            if ($id != end($groupsId))
//                $sql .= ' fk_user = ' . $id . ' OR ';
//            else
//                $sql .= ' fk_user = ' . $id;
//        }

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $ind = array_search($obj->fk_usergroup, $groupsId);
                if ($ind !== false)
                    unset($groupsId[$ind]);
//                $ind = in_array($obj->fk_usergroup, $groupsId);
//                if ($ind != false) {
//                    $groupsId = array_slice($groupsId, $ind);
//                }
            }
        }
        return $groupsId;
    }

    function addUserInGroups($userid, $groupsId) {
        $user = new User($this->db);
        $user->fetch($userid);

        if (sizeof($groupsId) == 1)
            $str = "$user->firstname $user->lastname à été ajouté au groupe : ";
        else if (sizeof($groupsId) > 1)
            $str = "$user->firstname $user->lastname à été ajouté aux groupes : ";


        $groupsId = array_reverse($groupsId);
        foreach ($groupsId as $id) {
            $grp = new UserGroup($this->db);
            $grp->fetch($id);
            $str.= "$grp->nom\n";
            $user->SetInGroup($id, 1, 1);
        }

        setEventMessages($str, null, 'mesgs');
    }

}
