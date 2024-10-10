<?php

class BimpUserElement
{

    public static $user_element_table = 'bimp_user_element';
    public static $usergroup_element_table = 'bimp_usergroup_element';

    public static function addUserElement($id_user, $element, $can_edit = 1)
    {
        $errors = array();

        $bdb = BimpCache::getBdb();

        $id = (int) $bdb->getValue(self::$user_element_table, 'id', 'id_user = ' . $id_user . ' AND element = \'' . $element . '\'');

        if ($id) {
            if ($bdb->update(self::$user_element_table, array(
                        'can_edit' => $can_edit
                            ), 'id = ' . $id) <= 0) {
                $errors[] = $bdb->err();
            }
        } else {
            if ($bdb->insert(self::$user_element_table, array(
                        'element'  => $element,
                        'id_user'  => $id_user,
                        'can_edit' => ($can_edit ? 1 : 0)
                    )) <= 0) {
                $errors[] = $bdb->err();
            }
        }

        return $errors;
    }

    public static function addUserGroupElement($id_usergroup, $element)
    {
        $errors = array();

        $bdb = BimpCache::getBdb();

        $id = (int) $bdb->getValue(self::$usergroup_element_table, 'id', 'id_usergroup = ' . $id_usergroup . ' AND element = \'' . $element . '\'');

        if (!$id) {
            if ($bdb->insert(self::$user_element_table, array(
                        'element'      => $element,
                        'id_usergroup' => $id_usergroup
                    )) <= 0) {
                $errors[] = $bdb->err();
            }
        }

        return $errors;
    }

    public static function remmoveUserElement($id_user, $element)
    {
        $errors = array();

        $bdb = BimpCache::getBdb();

        if ($bdb->delete(self::$user_element_table, 'id_user = ' . $id_user . ' AND element = \'' . $element . '\'') <= 0) {
            $errors[] = $bdb->err();
        }

        return $errors;
    }
    
    public static function remmoveUserGroupElement($id_usergroup, $element)
    {
        $errors = array();

        $bdb = BimpCache::getBdb();

        if ($bdb->delete(self::$usergroup_element_table, 'id_usergroup = ' . $id_usergroup . ' AND element = \'' . $element . '\'') <= 0) {
            $errors[] = $bdb->err();
        }

        return $errors;
    }
}
