<?php

class BimpNotification extends BimpObject{

    public function getNotificationForUser($id_user, $config, &$errors) {
        
        $notifs = array();

        // ajout notif is 
        foreach($config as $c) {
            $notifs[$c['nom']] = $this->getSingleNotificationForUser($id_user, $c['module'], $c['class'], $c['method'], $c['id_max'], $errors);
        }
        
        return $notifs;
    }
    
    public function getSingleNotificationForUser($id_user, $module, $class, $method, $id_max, &$errors) {
        
        $obj = BimpCache::getBimpObjectInstance($module, $class);
        
        if(is_a($obj, $class)) {
            if (method_exists($class, $method)) {
                return $obj->$method($id_user, $id_max, $errors);
            } else {
                $errors[] = "Méthode " . $method . " introuvable dans " . $class;
            }
        } else {
                $errors[] = "Impossible d'instancier " . $class;
        }
        
        return -1;
    }
    
    
}
