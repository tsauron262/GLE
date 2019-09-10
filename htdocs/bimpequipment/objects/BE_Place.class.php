<?php

require_once DOL_DOCUMENT_ROOT . '/bimpsupport/centre.inc.php';

class BE_Place extends BimpObject
{

    const BE_PLACE_CLIENT = 1;
    const BE_PLACE_ENTREPOT = 2;
    const BE_PLACE_USER = 3;
    const BE_PLACE_FREE = 4;
    const BE_PLACE_PRESENTATION = 5;
    const BE_PLACE_VOL = 6;
    const BE_PLACE_PRET = 7;
    const BE_PLACE_SAV = 8;
    const BE_PLACE_INTERNE = 9;

    public static $types = array(
        1 => 'Client',
        2 => 'En Stock',
        3 => 'Utilisateur',
        4 => 'Champ libre',
        5 => 'En Présentation',
        6 => 'Vol',
        7 => 'Matériel de prêt',
        8 => 'SAV',
        9 => 'Utilisation interne'
    );
    public static $entrepot_types = array(self::BE_PLACE_ENTREPOT, self::BE_PLACE_PRESENTATION, self::BE_PLACE_PRET, self::BE_PLACE_SAV, self::BE_PLACE_VOL, self::BE_PLACE_INTERNE);

    // Getters booléens: 

    public function isCreatable($force_create = false, &$errors = array())
    {
        if ($force_create) {
            return 1;
        }

        $equipment = $this->getParentInstance();

        if (!BimpObject::objectLoaded($equipment)) {
            $errors[] = 'ID de l\'équipement absent';
            return 0;
        }

        if (!$force_create && (int) $equipment->getData('id_package')) {
            $package = $equipment->getChildObject('package');
            $msg = 'L\'équipement ' . $equipment->getNomUrl(0, 1, 1, 'default') . ' est inclus dans le package ';
            if (BimpObject::objectLoaded($package)) {
                $msg .= $package->getNomUrl(0, 1, 1, 'default');
            } else {
                $msg .= ' #' . $equipment->getData('id_package');
            }

            $msg .= '.<br/>Il n\'est pas possible de modifier l\'emplacement de cet équipement';

            $errors[] = $msg;
            return 0;
        }

        return 1;
    }

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

    public function getTdStyle()
    {
        if ($this->isLoaded()) {
            $parent = $this->getParentInstance();
            if ($parent->isLoaded()) {
                $place = $parent->getCurrentPlace();
                if (!BimpObject::objectLoaded($place) || ((int) $place->id !== (int) $this->id)) {
                    return 'background-color: #D2D2D2!important;';
                }
            }
        }

        return '';
    }

    public function getPlaceName()
    {
        $name = '';
        $type = $this->getData('type');
        if (!is_null($type)) {
            switch ($type) {
                case self::BE_PLACE_CLIENT:
                    $client = $this->getChildObject('client');
                    if (BimpObject::ObjectLoaded($client)) {
                        $name = 'Client "' . $client->nom . '"';
                    }
                    break;

                case self::BE_PLACE_ENTREPOT:
                case self::BE_PLACE_PRESENTATION:
                case self::BE_PLACE_VOL:
                case self::BE_PLACE_SAV:
                case self::BE_PLACE_PRET:
                case self::BE_PLACE_INTERNE:
                    $entrepot = $this->getChildObject('entrepot');
                    if (BimpObject::ObjectLoaded($entrepot)) {
                        $name = 'Entrepôt "' . $entrepot->lieu . '"';
                    }
                    break;

                case self::BE_PLACE_USER:
                    $user = $this->getChildObject('user');
                    if (BimpObject::ObjectLoaded($user)) {
                        $name = 'Utilisateur "' . $user->getFullName() . '"';
                    }
                    break;

                case self::BE_PLACE_FREE:
                    $name = $this->getData('place_name');
                    break;
            }
        }

        if (!$name) {
            $name = 'inconnu';
        }

        return $name;
    }

    public function displayPlace()
    {
        $type = $this->getData('type');
        if (!is_null($type)) {
            switch ($type) {
                case self::BE_PLACE_CLIENT:
                    return $this->displayData('id_client', 'nom_url');

                case self::BE_PLACE_ENTREPOT:
                case self::BE_PLACE_PRESENTATION:
                case self::BE_PLACE_VOL:
                case self::BE_PLACE_SAV:
                case self::BE_PLACE_PRET:
                case self::BE_PLACE_INTERNE:
                    return $this->displayData('id_entrepot', 'nom_url');

                case self::BE_PLACE_USER:
                    return $this->displayData('id_user', 'nom_url');

                case self::BE_PLACE_FREE:
                    return $this->getData('place_name');
            }
        }

        return '';
    }

    // Overrides: 

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
                    $this->set('code_centre', '');
                    break;

                case self::BE_PLACE_ENTREPOT:
                case self::BE_PLACE_PRESENTATION:
                case self::BE_PLACE_INTERNE:
                case self::BE_PLACE_VOL:
                case self::BE_PLACE_SAV:
                    $id_entrepot = $this->getData('id_entrepot');
                    if (is_null($id_entrepot) || !$id_entrepot) {
                        return array('Valeur obligatoire absente: "Entrepôt"');
                    }
                    $this->set('id_client', 0);
                    $this->set('id_user', 0);
                    $this->set('place_name', '');
                    $this->set('code_centre', '');
                    break;

                case self::BE_PLACE_PRET:
                    if (!(int) $this->getData('id_entrepot')) {
                        if (!$this->getData('code_centre')) {
                            return array('Valeur obligatoire absente: "Centre" ou "Entrepot"');
                        }
                        global $tabCentre;
                        $this->set('id_entrepot', (int) $tabCentre[$this->getData('code_centre')][8]);
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
                    $this->set('code_centre', '');
                    break;

                case self::BE_PLACE_FREE:
                    $name = $this->getData('place_name');
                    if (is_null($name) || !$name) {
                        return array('Valeur obligatoire absente: "Nom de l\'emplacement"');
                    }
                    $this->set('id_entrepot', 0);
                    $this->set('id_user', 0);
                    $this->set('id_client', 0);
                    $this->set('code_centre', '');
                    break;
            }

            return parent::validate();
        }

        return array('Type invalide ou absent');
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if ($this->isLoaded()) {
            $equipment = $this->getParentInstance();
            if ($equipment->isLoaded()) {
                $equipment->onNewPlace();
            }
        }

        return $errors;
    }
}
