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
    var $id_childs = array();
    var $grp_ids = array();

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
    }

    /**
     * When the user drag and drop a group
     */
    function updateGroup($groupId, $newGroupId) {

        $this->removeChild($groupId);
        if (isset($newGroupId)) {
            $this->addChild($groupId, $newGroupId);
        }
    }

    /* Get groups ID and name $groups['id'] => name  */

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

    /* Remove link between 2 groups */

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

    /* Create link between 2 groups */

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

    /* Get the unique parent id by the id one of his child ($id) */

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

    /* Retrieve links between groups from the database */

    function getOldGroup() {

        $filledGroups = array();
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

    /**
     * Merge 2 scpecific array
     * @param type $allGroups    every groups (also out of module's one)
     * @param type $filledGroups groups of the module
     * @return type
     */
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

    /* Add the field isRoot */

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

    /* Reindex the array */

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

    /* Add the id of parent */

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

    /* Called by the interface */

    function setAllUsers() {
        $ids = $this->getAllUsersId();
        $users = $this->getAllUsersById($ids);
        $usergrp = $this->getGrp($users);
        $this->addInGroups($usergrp);
    }

    /* Return an array with id of all users */

    function getAllUsersId() {

        $ids = array();

        $sql = "SELECT rowid";
        $sql.= " FROM " . MAIN_DB_PREFIX . "user";

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $ids[] = $obj->rowid;
            }
        }
        return $ids;
    }

    /* Return an array with all users instances */

    function getAllUsersById($ids) {
        $users = array();
        foreach ($ids as $id) {
            $user = new User($this->db);
            $user->fetch($id);
            $users[] = $user;
        }
        return $users;
    }

    /**
     * @param type $users  object user
     * @return $usergrp['idUser']['grps'] => ids of groups of the user
     *         $usergrp['idUser']['user'] => a user object
     */
    function getGrp($users) {
        $staticgrp = new UserGroup($this->db);
        $usergrp = array();

        foreach ($users as $user) {
            $usergrp[$user->id]['grps'] = $staticgrp->listGroupsForUser($user->id);
            $usergrp[$user->id]['user'] = $user;
        }
        return $usergrp;
    }

    /* Set all user in their group (and their parents, grand-parent, etc...) */

    function addInGroups($usergrp) {
        foreach ($usergrp as $elt) {
            $grpsidWithDuplicate = array();
            foreach ($elt['grps'] as $groupid => $inut) {
                $parents = $this->getAllParents($groupid);
                $grpsidWithDuplicate = $this->custMerge($grpsidWithDuplicate, $parents);
            }
            $grpidNoDuplicate = array_unique($grpsidWithDuplicate);
            foreach ($grpidNoDuplicate as $groupid) {
                $elt['user']->SetInGroup($groupid, 1, 1);
            }
        }
    }

    /**
     * Used by trigger AddInGroups
     */
    function insertInGroups($userid, $groupid) {
        $groupsId = $this->getAllParents($groupid);
        $this->printMessage($userid, $groupsId, $groupid);
        $groupsIdFiltered = $this->removeGroupsUserOwn($userid, $groupsId);
        $grp = $this->addUserInGroups($userid, $groupsIdFiltered, $groupid);
        if (sizeof($grp) == 0) {
            return 0;
        } else if (0 < sizeof($grp)) {
            return sizeof($grp);
        } else if (!isset($grp)) {
            return -1;
        }
    }

    /* Get parent, grand-parents etc ... Of a group */

    function getAllParents($groupid) {

        $parents = array($groupid);
        do {
            $len = sizeof($parents);
            $sql = 'SELECT `fk_parent`';
            $sql .= ' FROM `' . MAIN_DB_PREFIX . 'bimp_grp_grp`';
            $sql .= ' WHERE (`fk_parent` IN (SELECT `rowid` FROM `'. MAIN_DB_PREFIX .'usergroup`)';
            $sql .= '   OR   `fk_child`  IN (SELECT `rowid` FROM `'. MAIN_DB_PREFIX .'usergroup`))';
            $sql .= ' AND `fk_child` = ' . end($parents);
            
            $result = $this->db->query($sql);
            if ($result and mysqli_num_rows($result) > 0) {
                while ($obj = $this->db->fetch_object($result)) {
                    $parents[] = $obj->fk_parent;
                }
            }
        } while ($len != sizeof($parents));

        return $parents;
    }

    /* Print dolibarr message when the user add an user in a group */

    function printMessage($userid, $groupsId, $groupid) {
        $user = new User($this->db);
        $user->fetch($userid);

        if (sizeof($groupsId) == 1) {
            $str = "$user->firstname $user->lastname à été ajouté au groupe : ";
        } else if (sizeof($groupsId) > 1) {
            $str = "$user->firstname $user->lastname à été ajouté aux groupes : ";
        }

        $groupsId2 = array_reverse($groupsId);
        foreach ($groupsId2 as $id) {
            $grp = new UserGroup($this->db);
            $grp->fetch($id);
            if ($id != $groupid) {
                $str.= "$grp->nom, ";
            } else if (sizeof($groupsId) != 1) {
                $str.= "et $grp->nom.";
            } else {
                $str.= " $grp->nom.";
            }
        }
        setEventMessages($str, null, 'mesgs');
    }

    /* Remove group which already contain the user (in order not to duplicate that user) */

    function removeGroupsUserOwn($userid, $groupsId) {

        $sql = 'SELECT fk_usergroup';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'usergroup_user';
        $sql .= ' WHERE fk_user=' . $userid;

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $ind = array_search($obj->fk_usergroup, $groupsId);
                if ($ind !== false) {
                    unset($groupsId[$ind]);
                }
            }
        }
        return $groupsId;
    }

    /* Add the user in every parents, grands-parent etc ... Of the group */

    function addUserInGroups($userid, $groupsId, $initGroupId) {
        $user = new User($this->db);
        $user->fetch($userid);

        $groupsId2 = array_reverse($groupsId);
        foreach ($groupsId2 as $id) {
            $grp = new UserGroup($this->db);
            $grp->fetch($id);
            $user->SetInGroup($id, 1, 1);
        }

        return $groupsId2;
    }

}
