<?php

class BE_Place extends BimpObject
{

    const BE_PLACE_CLIENT = 1;
    const BE_PLACE_ENTREPOT = 2;
    const BE_PLACE_USER = 3;
    const BE_PLACE_FREE = 4;

    public static $types = array(
        1 => 'Client',
        2 => 'Entrepôt',
        3 => 'Utilisateur',
        4 => 'Champ libre'
    );

    public function getContactsArray()
    {
        $contacts = array();

        $id_client = $this->getData('id_client');
        if (!is_null($id_client)) {
            $where = '`fk_soc` = ' . (int) $id_client;
            $rows = $this->db->getRows('socpeople', $where, null, 'array', array('rowid', 'firstname', 'lastname'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $contacts[(int) $r['rowid']] = BimpTools::ucfirst($r['firstname']) . ' ' . strtoupper($r['lastname']);
                }
            }
        }

        return $contacts;
    }

    public function displayPlace()
    {
        $type = $this->getData('type');
        if (!is_null($type)) {
            switch ($type) {
                case self::BE_PLACE_CLIENT:
                    return $this->displayData('id_client', 'nom_url');

                case self::BE_PLACE_ENTREPOT:
                    return $this->displayData('id_entrepot', 'nom_url');

                case self::BE_PLACE_USER:
                    return $this->displayData('id_user', 'nom_url');

                case self::BE_PLACE_FREE:
                    return $this->getData('place_name');
            }
        }

        return '';
    }

    public function validate()
    {   
        $type = $this->getData('type');

        if (!is_null($type) && array_key_exists($type, self::$types)) {
            switch ((int) $type) {
                case self::BE_PLACE_CLIENT:
                    $id_client = $this->getData('id_client');
                    if (is_null($id_client) || !$id_client) {
                        return array('Valeur obligatoire absente: "Client"');
                    }
                    $this->set('id_entrepot', 0);
                    $this->set('id_user', 0);
                    $this->set('place_name', '');
                    break;

                case self::BE_PLACE_ENTREPOT:
                    $id_entrepot = $this->getData('id_entrepot');
                    if (is_null($id_entrepot) || !$id_entrepot) {
                        return array('Valeur obligatoire absente: "Entrepôt"');
                    }
                    $this->set('id_client', 0);
                    $this->set('id_user', 0);
                    $this->set('place_name', '');
                    break;

                case self::BE_PLACE_USER:
                    $id_user = $this->getData('id_user');
                    if (is_null($id_user) || !$id_user) {
                        return array('Valeur obligatoire absente: "Utilisateur"');
                    }
                    $this->set('id_entrepot', 0);
                    $this->set('id_client', 0);
                    $this->set('place_name', '');
                    break;

                case self::BE_PLACE_FREE:
                    $name = $this->getData('place_name');
                    if (is_null($name) || !$name) {
                        return array('Valeur obligatoire absente: "Nom de l\'emplacement"');
                    }
                    $this->set('id_entrepot', 0);
                    $this->set('id_user', 0);
                    $this->set('id_client', 0);
                    break;
            }
            
            return parent::validate();
        }

        return array('Type invalide ou absent');
    }
}
