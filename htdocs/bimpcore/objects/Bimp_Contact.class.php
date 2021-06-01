<?php

class Bimp_Contact extends BimpObject
{

    public static $status_list = array(
        0 => array('label' => 'Désactivé', 'icon' => 'fas_times', 'classes' => array('danger')),
        1 => array('label' => 'Actif', 'icon' => 'fas_check', 'classes' => array('success'))
    );

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

    // Overrides: 

    public function validate()
    {
        $civility = (string) $this->getData('civility');
        $fistname = (string) $this->getData('firstname');

        if ($civility !== 'SERVIC' && !$fistname) {
            $errors[] = 'Le prénom est obligatoire pour les contacts de type autre que "Service"';
        }

        $errors = BimpTools::merge_array($errors, parent::validate());

        return $errors;
    }
}
