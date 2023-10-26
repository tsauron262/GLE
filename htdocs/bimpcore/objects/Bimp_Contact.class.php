<?php

class Bimp_Contact extends BimpObject
{

    public static $status_list = array(
        0 => array('label' => 'Désactivé', 'icon' => 'fas_times', 'classes' => array('danger')),
        1 => array('label' => 'Actif', 'icon' => 'fas_check', 'classes' => array('success'))
    );
    public static $anonymization_fields = array('lastname', 'firstname', 'address', 'zip', 'town', 'email', 'phone', 'phone_perso', 'phone_mobile', 'fax', 'jabberid', 'skype', 'birthday');

    // Getters booléens: 

    public function canClientView()
    {
        global $userClient;

        if (BimpObject::objectLoaded($userClient)) {
            if ($this->isLoaded()) {
                if ((int) $this->getData('fk_soc') == (int) $userClient->getData('id_client')) {
                    return 1;
                }

                return 0;
            }

            return 1;
        }

        return 0;
    }

    public function canClientCreate()
    {
        global $userClient;

        if (BimpObject::objectLoaded($userClient)) {
            if ((int) $this->getData('fk_soc') == (int) $userClient->getData('id_client')) {
                return 1;
            }
        }

        return 0;
    }

    public function canClientEdit()
    {
        if ($this->isLoaded()) {
            global $userClient;

            if (BimpObject::objectLoaded($userClient)) {
                if ((int) $this->getData('fk_soc') == (int) $userClient->getData('id_client')) {
                    if (!$userClient->isAdmin()) {
                        if ((int) $userClient->getData('id_contact') != (int) $this->id) {
                            return 0;
                        }
                    }
                    return 1;
                }
            }

            return 0;
        }
    }

    public function canDelete()
    {
        global $user;
        return (int) $user->admin;
    }

    public function canSetStatus($status)
    {
        if (BimpCore::isContextPublic()) {
            global $userClient;

            if (BimpObject::objectLoaded($userClient)) {
                if ($userClient->isAdmin()) {
                    return 1;
                }
            }

            return 0;
        }

        return parent::canSetStatus($status);
    }

    public function canEditField($field_name)
    {
        if ($this->isLoaded()) {
            $client = $this->getParentInstance();

            if (BimpObject::objectLoaded($client)) {
                if ($client->isAnonymised()) {
                    if (in_array($field_name, self::$anonymization_fields)) {
                        // Champs anonymisés non éditables par user: doit utiliser action "Annuler anonymisation" (revertAnonymization) du client.
                        return 0;
                    }
                }
            }
        }
        return parent::canEditField($field_name);
    }

    // Getters params: 

    public function getSocContactsListTitle()
    {
        $soc = $this->getParentInstance();

        if (BimpObject::objectLoaded($soc)) {
            return 'Contacts ' . $soc->getLabel('of_the') . ' ' . $soc->getRef() . ' - ' . $soc->getName();
        }
    }

    public function getDolObjectUpdateParams()
    {
        global $user;
        if ($this->isLoaded()) {
            return array($this->id, $user);
        }

        return array(0, $user);
    }

    public function getListExtraButtons()
    {
        $buttons = array();
        if ($this->isLoaded()) {

            if ((int) $this->getData('statut')) {
                if ($this->canSetStatus(0) && $this->isNewStatusAllowed(0)) {
                    $buttons[] = array(
                        'label'   => 'Désactiver',
                        'icon'    => 'fas_times',
                        'onclick' => 'setObjectNewStatus($(this), ' . $this->getJsObjectData() . ', 0)'
                    );
                }
            } else {
                if ($this->canSetStatus(1) && $this->isNewStatusAllowed(1)) {
                    $buttons[] = array(
                        'label'   => 'Activer',
                        'icon'    => 'fas_check',
                        'onclick' => 'setObjectNewStatus($(this), ' . $this->getJsObjectData() . ', 1)'
                    );
                }
            }
        }

        return $buttons;
    }

    // Getters données:

    public function getName($withGeneric = true)
    {
        $lastname = $this->getData('lastname');
        $firstname = $this->getData('firstname');

        return (!is_null($firstname) && $firstname ? $firstname . ' ' : '') . $lastname;
    }

    public function getCardFields($card_name)
    {
        $fields = parent::getCardFields($card_name);

        switch ($card_name) {
            case 'default':
                $fields[] = 'address';
                $fields[] = 'zip';
                $fields[] = 'town';
                $fields[] = 'fk_departement';
                $fields[] = 'fk_pays';

                $fields[] = 'email';
                $fields[] = 'phone';
                $fields[] = 'phone_perso';
                $fields[] = 'phone_mobile';
                $fields[] = 'fax';
                $fields[] = 'skype';
                break;
        }

        return $fields;
    }

    // Affichage: 

    public function displayCountry()
    {
        $id = (int) $this->getData('fk_pays');
        if ($id) {
            $countries = BimpCache::getCountriesArray();
            if (isset($countries[$id])) {
                return $countries[$id];
            }
        }
        return '';
    }

    public function displayDepartement()
    {
        $fk_dep = (int) $this->getData('fk_departement');
        if ((int) $fk_dep) {
            $deps = BimpCache::getStatesArray((int) $this->getData('fk_pays'));
            if (isset($deps[$fk_dep])) {
                return $deps[$fk_dep];
            }
        }
        return '';
    }

    public function displayFullAddress($singleLine = false)
    {
        $html = '';

        if ($this->getData('address')) {
            $html .= $this->getData('address') . ($singleLine ? ' ' : '<br/>');
        }

        if ($this->getData('zip')) {
            $html .= $this->getData('zip');

            if ($this->getData('town')) {
                $html .= ' ' . $this->getData('town');
            }
            $html .= ($singleLine ? ' ' : '<br/>');
        } elseif ($this->getData('town')) {
            $html .= $this->getData('town') . ($singleLine ? ' ' : '<br/>');
        }

        if ($this->getData('fk_departement')) {
            $html .= $this->displayDepartement();

            if ($this->getData('fk_pays')) {
                $html .= ' - ' . $this->displayCountry();
            }
        } elseif ($this->getData('fk_pays')) {
            $html .= $this->displayCountry();
        }

        return $html;
    }

    public function displayContactInfos()
    {
        $html = '';

        if ($this->getData('email')) {
            $html .= BimpRender::renderIcon('fas_envelope', 'iconLeft') . $this->getData('email');
        }

        if ($this->getData('phone')) {
            $html .= ($html ? '<br/>' : '') . BimpRender::renderIcon('fas_phone', 'iconLeft') . '(pro) ' . $this->getData('phone');
        }
        if ($this->getData('phone_perso')) {
            $html .= ($html ? '<br/>' : '') . BimpRender::renderIcon('fas_phone', 'iconLeft') . '(perso) ' . $this->getData('phone_perso');
        }
        if ($this->getData('phone_mobile')) {
            $html .= ($html ? '<br/>' : '') . BimpRender::renderIcon('fas_mobile-alt', 'iconLeft') . $this->getData('phone_mobile');
        }
        if ($this->getData('fax')) {
            $html .= ($html ? '<br/>' : '') . BimpRender::renderIcon('fas_fax', 'iconLeft') . $this->getData('fax');
        }
        if ($this->getData('skype')) {
            $html .= ($html ? '<br/>' : '') . BimpRender::renderIcon('fab_skype', 'iconLeft') . $this->getData('skype');
        }

        return $html;
    }

    public static function getNomCompletCsvValue($needed_fields = array())
    {
        $return = $needed_fields['firstname'];
        if ($needed_fields['lastname'] != '' && $needed_fields['firstname'] != '')
            $return .= ' ';
        $return .= $needed_fields['lastname'];

        return $return;
    }

    public function displayNomComplet()
    {
        $return = $this->getData('firstname');
        if ($this->getData('lastname') != '' && $this->getData('firstname') != '')
            $return .= ' ';
        $return .= $this->getData('lastname');

        return $return;
    }

    // Traitements: 

    public function anonymiseData($save_data = true)
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $data = array();
        $saved_data = array();

        foreach (self::$anonymization_fields as $field) {
            $saved_data[$field] = $this->getData($field);

            if ($field === 'birthday') {
                $data[$field] = null;
            } else {
                if ((string) $saved_data[$field]) {
                    $data[$field] = '*****';
                } else {
                    unset($saved_data[$field]);
                }
            }
        }

        if ($save_data) {
            $id_cur_saved_data = (int) $this->db->getValue('societe_saved_data', 'id', 'type = \'contact\' AND id_object = ' . (int) $this->id);

            if ($id_cur_saved_data) {
                if ($this->db->update('societe_saved_data', array(
                            'date' => date('Y-m-d'),
                            'data' => base64_encode(json_encode($saved_data))
                                ), 'id = ' . $id_cur_saved_data) <= 0) {
                    $errors[] = 'Echec de l\'enregistrement des données de sauvegarde. Pas d\'anonymisation - Erreur SQL ' . $this->db->err();
                }
            } else {
                if ($this->db->insert('societe_saved_data', array(
                            'type'      => 'contact',
                            'id_object' => (int) $this->id,
                            'date'      => date('Y-m-d'),
                            'data'      => base64_encode(json_encode($saved_data))
                        )) <= 0) {
                    $errors[] = 'Echec de l\'enregistrement des données de sauvegarde. Pas d\'anonymisation - Erreur SQL ' . $this->db->err();
                }
            }
        }

        if (!count($errors)) {
            if (!empty($data)) {
                // On fait un update direct en base pour contourner les validations de formats des données: 
                if ($this->db->update('socpeople', $data, 'rowid = ' . (int) $this->id) <= 0) {
                    $errors[] = 'Echec anonymisation des données - Erreur sql: ' . $this->db->err();
                }
            }
        }

        return $errors;
    }

    public function revertAnonymisedData()
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $rows = $this->db->getRows('societe_saved_data', 'type = \'contact\' AND id_object = ' . $this->id, 1, 'array', null, 'date', 'desc');

            if (isset($rows[0]['data'])) {
                $values = base64_decode($rows[0]['data']);

                if ($values) {
                    $values = json_decode($values, 1);
                }

                if (is_array($values) && !empty($values)) {
                    foreach (self::$anonymization_fields as $field) {
                        if (isset($values[$field])) {
                            $this->set($field, $values[$field]);
                        }
                    }

                    $warnings = array();
                    $errors = $this->update($warnings, true);

                    if (!count($errors)) {
                        if ((int) $rows[0]['id']) {
                            $this->db->delete('societe_saved_data', 'id = ' . (int) $rows[0]['id']);
                        }
                    }
                }
            }
        }

        return $errors;
    }

    // Overrides: 

    public function validate()
    {
        $civility = (string) $this->getData('civility');
        $fistname = (string) $this->getData('firstname');
        $errors = array();

        if ($civility !== 'SERVIC' && !$fistname && $this->getData('statut') > 0) {
            $errors[] = 'Le prénom est obligatoire pour les contacts de type autre que "Service"';
        }

        $errors = BimpTools::merge_array($errors, parent::validate());

        return $errors;
    }
}
