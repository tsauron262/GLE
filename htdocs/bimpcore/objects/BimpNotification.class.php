<?php

class BimpNotification extends BimpObject
{

    public function getUserNotifications($id_user, $notif_data = array())
    {
        $errors = array();
        $obj_name = $this->getData('class');
        BimpObject::loadClass($this->getData('module'), $this->getData('class'));

        $methode = $this->getData('method');
        if (method_exists($obj_name, $methode)) {
            $tms = BimpTools::getArrayValueFromPath($notif_data, 'tms', '');
            $options = BimpTools::getArrayValueFromPath($notif_data, 'options', array());
            
            return $obj_name::$methode($id_user, $tms, $options, $errors);
        } else {
            $errors[] = "Méthode " . $methode . " introuvable dans " . $obj_name;
        }
        
        return array();
    }

    public function getNotifObject($id_object = null)
    {
        $module = $this->getData('module');
        $object_name = $this->getData('class');

        if ($module && $object_name) {
            return BimpCache::getBimpObjectInstance($module, $object_name, $id_object);
        }

        return null;
    }

    // Méthodes statiques :

    public static function getNotificationsForUser($id_user, $notifs_data)
    {
        $notifs = array();

        // Ajout notif is :
        foreach ($notifs_data as $n) {
            $bn = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNotification', $n['id_notification']);

            if (BimpObject::objectLoaded($bn)) {
                $notifs[$n['id_notification']] = $bn->getUserNotifications($id_user, $n);
            }
        }

        return $notifs;
    }
}
