<?php


class Bimp_Rights extends BimpObject
{

    public function getTabRights(){
        $cacheKey = 'listLabelRights';
        if(!isset(BimpCache::$cache[$cacheKey])){
            BimpCache::$cache[$cacheKey] = $this->initTabRights();
        }
        return BimpCache::$cache[$cacheKey];
    }
    
    public function initTabRights(){
        $tabR = array();
        $sql = $this->db->db->query("SELECT * FROM `".MAIN_DB_PREFIX."rights_def` ORDER BY `id` DESC");
        while($ln = $this->db->db->fetch_object($sql)){
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


