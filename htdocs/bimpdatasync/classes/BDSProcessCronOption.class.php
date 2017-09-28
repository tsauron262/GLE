<?php

class BDSProcessCronOption extends BimpObject
{

    public static $table = 'bds_process_cron_option';
    public static $parent_id_property = 'id_process_cron';
    public $id_process_cron;
    public $id_option;
    public $use_def_val;
    public $value;
    public static $labels = array(
        'name'      => 'option',
        'name_plur' => 'options',
        'isFemale'  => 1
    );
    public static $fields = array(
        'id_process_cron' => array(
            'label'    => 'ID de la tâche planifiée',
            'type'     => 'int',
            'required' => true
        ),
        'id_option'       => array(
            'label'    => 'ID de l\'option',
            'type'     => 'int',
            'required' => true
        ),
        'use_def_val'     => array(
            'label'    => 'Utiliser la valeur par défaut',
            'type'     => 'bool',
            'required' => true
        ),
        'value'           => array(
            'label'       => 'Valeur',
            'type'        => 'string',
            'required_if' => 'use_def_val=0',
        )
    );
    public static $list_params = array(
        'title'      => 'Liste des options',
        'no_items'   => 'Aucune option disponible',
        'checkboxes' => 0,
        'headers'    => array(
            array(
                'width' => 30,
                'label' => 'Option',
                'input' => false
            ),
            array(
                'width' => 40,
                'label' => 'valeur',
                'input' => true
            ),
            array(
                'width' => 30
            )
        ),
        'cols'       => array(
            array(
                'name'  => 'id_option',
                'input' => 'hidden'
            ),
            array(
                'name'      => 'name',
                'data_type' => 'string'
            ),
            array(
                'name' => 'use_def_val',
                'input' => 'switch'
            ),
            array(
                'name'  => 'value',
                'input' => 'text'
            )
        ),
        'update_btn' => 1
    );

    public static function getClass()
    {
        return 'BDSProcessCronOption';
    }
}
