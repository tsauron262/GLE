<?php

require_once DOL_DOCUMENT_ROOT . '/bimpuserconfig/objects/BCUserConfig.class.php';

class ListFilters extends BCUserConfig
{

    public static $config_object_name = 'ListFilters';
    public static $config_table = 'buc_list_filters';
    protected $obj_instance = null;

    // Getters: 

    public function getCreateJsCallback()
    {
        if ($this->isLoaded()) {
            $filters_id = BimpTools::getPostFieldValue('filters_id', '');

            if ($filters_id) {
                return 'loadSavedFilters(\'' . $filters_id . '\', ' . $this->id . ', 1);';
            }
        }

        return '';
    }

    public function getUpdateJsCallback()
    {
        if ($this->isLoaded()) {
            $filters_id = BimpTools::getPostFieldValue('filters_id', '');

            if ($filters_id) {
                return 'loadSavedFilters(\'' . $filters_id . '\', 0, 1);';
            } else {
                $object_name = $this->getData('obj_name');
                if ($object_name) {
                    return 'loadAllSavedFiltersByObject(\'' . $object_name . '\', 1)';
                }
            }
        }

        return '';
    }

    public function getListTitle()
    {
        global $user, $langs;

        return (BimpObject::objectLoaded($user) ? $user->getFullName($langs) . ': l' : 'L') . 'iste des filtres enregistrés';
    }

    // Overrides:

    public function validatePost()
    {
        $errors = parent::validatePost();

        if (count($errors)) {
            return $errors;
        }

        $filters = $this->getData('filters');

        if (empty($filters)) {
            $errors[] = 'Aucun filtre sélectionné';
        }

        return $errors;
    }
}
