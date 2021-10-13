<?php

class Bimp_Rights extends BimpObject
{

    public static function getTabRights()
    {
        $cacheKey = 'listLabelRights';
        if (!isset(BimpCache::$cache[$cacheKey])) {
            BimpCache::$cache[$cacheKey] = self::initTabRights();
        }
        return BimpCache::$cache[$cacheKey];
    }

    public static function getRightName($id)
    {
        $tabRight = self::getTabRights();
        if (isset($tabRight[$id]))
            return $tabRight[$id];
        else
            return "";
    }

    public static function initTabRights()
    {
        global $db;
        $tabR = array();
        $sql = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "rights_def` ORDER BY `id` DESC");
        while ($ln = $db->fetch_object($sql)) {
            $nom = $ln->module . '->' . $ln->perms;
            if ($ln->subperms != '')
                $nom .= '->' . $ln->subperms;
            $tabR[$ln->id] = $nom;
        }
        return $tabR;
    }

    public function canDelete()
    {
        global $user;
        return $user->admin;
    }

    public static function getFullRightsDefs($by_modules = true)
    {
        $cache_key = 'list_label_rights';

        if ($by_modules) {
            $cache_key .= '_by_modules';
        }

        if (!isset(BimpCache::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $rows = self::getBdb()->getRows('rights_def', '1', null, 'array');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    if ($by_modules) {
                        self::$cache[$cache_key][$r['module']][$r['id']] = $r;
                    } else {
                        self::$cache[$cache_key][$r['id']] = $r;
                    }
                }
            }
        }

        return self::$cache[$cache_key];
    }
}
