<?php

class BimpNote extends BimpObject
{

    // Visibilités:
    const BIMP_NOTE_AUTHOR = 1;
    const BIMP_NOTE_ADMIN = 2;
    const BIMP_NOTE_MEMBERS = 3;
    const BIMP_NOTE_ALL = 4;
    // Types d'auteur:
    const BN_AUTHOR_USER = 1;
    const BN_AUTHOR_SOC = 2;
    const BN_AUTHOR_FREE = 3;

    public static $visibilities = array(
        self::BIMP_NOTE_AUTHOR  => array('label' => 'Auteur seulement', 'classes' => array('danger')),
        self::BIMP_NOTE_ADMIN   => array('label' => 'Administrateurs seulement', 'classes' => array('important')),
        self::BIMP_NOTE_MEMBERS => array('label' => 'Membres', 'classes' => array('warning')),
        self::BIMP_NOTE_ALL     => array('label' => 'Membres et client', 'classes' => array('success')),
    );
    public static $types_author = array(
        self::BN_AUTHOR_USER => 'Utilisateur',
        self::BN_AUTHOR_SOC  => 'Tiers',
        self::BN_AUTHOR_FREE => 'Libre'
    );

    // Getters Overrides BimpObject: 

    public function getParentInstance()
    {
        if (is_null($this->parent)) {
            $object_type = (string) $this->getData('obj_type');
            $module = (string) $this->getData('obj_module');
            $object_name = (string) $this->getData('obj_name');
            $id_object = (int) $this->getData('id_obj');

            if ($object_type && $module && $object_name && $id_object) {
                if ($object_type === 'bimp_object') {
                    $this->parent = BimpObject::getInstance($module, $object_name, $id_object);
                    if (!BimpObject::objectLoaded($this->parent)) {
                        unset($this->parent);
                        $this->parent = null;
                    }
                }
            }
        }

        return $this->parent;
    }

    // Getters: 

    public static function getFiltersByUser($id_user = null)
    {
        $filters = array();

        if (is_null($id_user)) {
            global $user;
        } elseif ((int) $id_user) {
            $user = new User($this->db->db);
            $user->fetch((int) $id_user);
        }

        if (!BimpObject::objectLoaded($user)) {
            $filters['visibility'] = array(
                'operator' => '>',
                'value'    => 3
            );
        } elseif (!$user->admin) {
            $filters['or_visibility'] = array(
                'or' => array(
                    'visibility'  => array(
                        'operator' => '>',
                        'value'    => 2
                    ),
                    'user_create' => $user->id
                )
            );
        }

        return $filters;
    }

    public function isCreatable()
    {        
        return (int) $this->isEditable();
    }

    public function isEditable()
    {
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent) && is_a($parent, 'BimpObject')) {
            return (int) $parent->areNotesEditable();
        }

        return 1;
    }
    
    public function isDeletable()
    {
        return (int) $this->isEditable();
    }

    // Affichage: 

    public function displayAuthor($display_input_value = true, $no_html = false)
    {
        switch ((int) $this->getData('type_author')) {
            case self::BN_AUTHOR_USER:
                return $this->displayData('user_create', 'nom_url', $display_input_value, $no_html);

            case self::BN_AUTHOR_SOC:
                return $this->displayData('id_societe', 'nom_url', $display_input_value, $no_html);

            case self::BN_AUTHOR_FREE:
                return $this->displayData('email', 'default', $display_input_value, $no_html);
        }

        return '';
    }

    // Overrrides: 

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            switch ((int) $this->getData('type_author')) {
                case self::BN_AUTHOR_USER:
                    if ($this->isLoaded()) {
                        if (!(int) $this->getData('user_create')) {
                            $errors[] = 'ID de l\'utilisateur absent';
                        }
                    }
                    break;

                case self::BN_AUTHOR_SOC:
                    if (!(int) $this->getData('id_societe')) {
                        $errors[] = 'Société à l\'origine de la note absente';
                    }
                    break;

                case self::BN_AUTHOR_FREE:
                    if (!(string) $this->getData('email')) {
                        $errors[] = 'Adresse e-mail absente';
                    }
                    break;
            }
        }

        return $errors;
    }
}
