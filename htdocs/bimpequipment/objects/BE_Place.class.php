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
}
