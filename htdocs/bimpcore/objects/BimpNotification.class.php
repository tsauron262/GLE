<?php

class BimpNotification extends BimpObject
{

    public function getNotificationForUser($id_user, $config, &$errors)
    {

        $notifs = array();

        // ajout notif is 
        foreach ($config as $c) {
            $bn = $this->getNotificationInstance($c['id_notification']);
            $notifs[$c['id_notification']] = $bn->getSingleNotificationForUser($id_user, $c['id_max'], $errors);
        }
        return $notifs;
    }

    public function getSingleNotificationForUser($id_user, $id_max, &$errors)
    {

        $obj = $this->getObject(null);

        if (is_a($obj, $this->getData('class'))) {
            $methode = $this->getData('method');
            if (method_exists($this->getData('class'), $methode)) {
                return $obj->$methode($id_user, $id_max, $errors);
            } else {
                $errors[] = "Méthode " . $methode . " introuvable dans " . $this->getData('class');
            }
        } else {
            $errors[] = "Impossible d'instancier " . $this->getData('class');
        }

        return -1;
    }

    public function getObject($id)
    {
        return BimpCache::getBimpObjectInstance($this->getData('module'), $this->getData('class'), $id);
    }

    public function setActionViewed($id)
    {
        die($id);
    }

    public function getNotificationInstance($id_notif)
    {
        return BimpCache::getBimpObjectInstance($this->module, $this->object_name, $id_notif);
    }
}
