<?php

class BDSProcessOption extends BDSObject
{

    public static $table = 'bds_process_option';
    public static $parent_id_property = 'id_process';
    public $id_process;
    public $name;
    public $label;
    public $info;
    public $type;
    public $select_values;
    public $default_value;
    public static $associations = array(
        'operations' => array(
            'class_name'    => 'BDSProcessOperation',
            'relation'      => 'ManyToMany',
            'same_parent'   => true,
            'table'         => 'bds_process_operation_option',
            'self_key'      => 'id_option',
            'associate_key' => 'id_operation'
        )
    );
    public static $labels = array(
        'name'     => 'option',
        'isFemale' => true
    );
    public static $types = array(
        'text'   => 'Champ textuel',
        'select' => 'Liste déroulante',
        'switch' => 'Choix OUI/NON'
    );
    public static $fields = array(
        'id_process'    => array(
            'label'    => 'ID du processus',
            'type'     => 'int',
            'required' => true,
        ),
        'name'          => array(
            'label'    => 'Nom système',
            'type'     => 'string',
            'input'    => 'text',
            'required' => true
        ),
        'label'         => array(
            'label'    => 'Nom public',
            'type'     => 'string',
            'input'    => 'text',
            'required' => true
        ),
        'info'          => array(
            'label'    => 'Informations',
            'type'     => 'html',
            'input'    => 'textarea',
            'required' => false
        ),
        'type'          => array(
            'label'        => 'Type',
            'input'        => 'select',
            'options'      => 'types',
            'is_key_array' => 'types',
            'required'     => true,
        ),
        'select_values' => array(
            'label'       => 'Valeurs',
            'type'        => 'string',
            'input'       => 'text',
            'display_if'  => array(
                'input_name'  => 'type',
                'show_values' => 'select'
            ),
            'help'        => 'Utiliser la syntaxe "nom_système=>nom_public". Séparer chaque entrée par une virgule (sans espace)',
            'required'    => false,
            'required_if' => 'type=select'
        ),
        'default_value' => array(
            'label'    => 'Valeur par défaut',
            'type'     => 'string',
            'input'    => 'text',
            'required' => false
        )
    );
    public static $list_params = array(
        'checkboxes'   => 1,
        'bulk_actions' => array(
            array(
                'label'     => 'Supprimer les options sélectionnées',
                'onclick'   => 'deleteSelectedObjects(\'BDSProcessOption\', $(this));',
                'btn_class' => 'butActionDelete deleteSelectedObjects'
            )
        ),
        'headers'      => array(
            array(
                'width' => 15,
                'label' => 'Nom système',
            ),
            array(
                'width' => 15,
                'label' => 'Nom public',
            ),
            array(
                'width' => 15,
                'label' => 'Type',
            ),
            array(
                'width' => 50
            )
        ),
        'cols'         => array(
            array(
                'name'      => 'name',
                'data_type' => 'string',
            ),
            array(
                'name'      => 'label',
                'data_type' => 'string',
            ),
            array(
                'name'       => 'type',
                'data_type'  => 'array_value',
                'array_name' => 'types'
            ),
        ),
        'edit_btn'     => 1,
        'delete_btn'   => 1
    );

    public static function getClass()
    {
        return 'BDSProcessOption';
    }

    public static function getTypesQueryArray($id_parent = null)
    {
        return self::$types;
    }

    public function delete()
    {
        $errors = parent::delete();
        $this->db->delete(BDSProcessCronOption::$table, '`id_option` = ' . (int) $this->id);
        return $errors;
    }

    public function saveAssociations($association, $list)
    {
        $errors = parent::saveAssociations($association, $list);
        BDSProcessCron::checkAllOptions($this->id_process);
        return $errors;
    }
}
