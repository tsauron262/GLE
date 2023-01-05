<?php

class BimpHashtag extends BimpObject
{

    // Droits Users: 

    public function canEditField($field_name)
    {
        if ($this->isLoaded()) {
            global $user;

            switch ($field_name) {
                case 'code':
                    if ($user->admin) {
                        return 1;
                    }
                    return 0;
            }
        }

        return parent::canEditField($field_name);
    }

    public function canDelete()
    {
        global $user;

        if ($user->admin) {
            return 1;
        }

        return 0;
    }

    // Getters booléens: 

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ($this->isLoaded()) {
            if ($force_delete) {
                return 1;
            }
            
            $link = BimpObject::getInstance('bimpcore', 'BimpLink');

            $rows = $link->getList(array(
                'linked_type'   => 'BO',
                'linked_module' => 'bimpcore',
                'linked_name'   => 'BimpHashtag',
                'linked_id'     => $this->id
                    ), null, null, 'id', 'desc', 'array', array('id'));

            if (empty($rows)) {
                return 1;
            }
            $errors[] = 'Certains objets sont liés à ce Hashtag';
        }

        return 0;
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        $code = $this->getData('code');

        if (!$code) {
            $errors[] = 'Mot-clé absent';
        } else {
            $id = (int) $this->db->getValue($this->getTable(), 'id', 'code = \'' . $code . '\'');

            if ($id) {
                $errors[] = 'Le mot-clé "' . $code . '" est déjà utilisé';
            }
        }

        if (!count($errors)) {
            $errors = parent::create($warnings, $force_create);
        }

        return $errors;
    }
}
