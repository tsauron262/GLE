<?php


class Bimp_Rights extends BimpObject
{

    public static function getTabRights(){
        $cacheKey = 'listLabelRights';
        if(!isset(BimpCache::$cache[$cacheKey])){
            BimpCache::$cache[$cacheKey] = self::initTabRights();
        }
        return BimpCache::$cache[$cacheKey];
    }
    
    public static function getRightName($id){
        $tabRight = self::getTabRights();
        if(isset($tabRight[$id]))
            return $tabRight[$id];
        else
            return "";
    }
    
    public static function initTabRights(){
        global $db;
        $tabR = array();
        $sql = $db->query("SELECT * FROM `".MAIN_DB_PREFIX."rights_def` ORDER BY `id` DESC");
        while($ln = $db->fetch_object($sql)){
            $nom = $ln->module.'->'.$ln->perms;
            if($ln->subperms != '')
                $nom .= '->'.$ln->subperms;
            $tabR[$ln->id] = $nom;
        }
        return $tabR;
    }
    
    public function canDelete() {
        global $user;
        return $user->admin;
    }
}


