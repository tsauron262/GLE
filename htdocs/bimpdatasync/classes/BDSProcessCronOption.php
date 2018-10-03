<?php

class BDSProcessCronOption extends BDSObject
{

    public static $table = 'bds_process_cron_option';
    public static $parent_id_property = 'id_process_cron';
    public $id_process_cron;
    public $id_option;
    public $use_def_val;
    public $value;
    
    public static $list_params = array(
        'title'        => 'Liste des options',
        'no_items'     => 'Aucune option disponible',
        'checkboxes'   => 0,
        'headers'      => array(
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
        'cols'         => array(
            array(
                'name' => 'id_option',
                'input' => 'hidden'
            ),
            array(
                'name'  => 'name',
                'data_type' => 'string'
            ),
            array(
                'name'  => 'value',
                'input' => 'text'
            ),
            array(
                'name'  => 'value',
                'input' => 'text'
            )
        ),
        'update_btn'   => 1
    );

    public static function getClass()
    {
        return 'BDSProcessCronOption';
    }
}
