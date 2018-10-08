<?php

class BDSProcessCustomMatchingValues extends BDSObject
{

    public static $table = 'bds_process_custom_matching_values';
    public static $parent_id_property = 'id_match';
    public $id_match;
    public $loc_value;
    public $ext_value;
    public $loc_label;
    public $ext_label;
    public static $labels = array(
        'name'     => 'correspondance personnalisée',
        'name'     => 'correspondances personnalisées',
        'isFemale' => true
    );
    public static $fields = array(
        'id_match'  => array(
            'label'    => 'ID du processus',
            'type'     => 'int',
            'required' => true,
        ),
        'loc_value' => array(
            'label'    => 'Nom système',
            'type'     => 'string',
            'required' => true
        ),
        'ext_value' => array(
            'label'    => 'Nom public',
            'type'     => 'string',
            'required' => true
        ),
        'loc_label' => array(
            'label'    => 'Intitulé local',
            'type'     => 'string',
            'required' => false
        ),
        'ext_label' => array(
            'label'    => 'Intitulé externe',
            'type'     => 'string',
            'required' => false
        ),
    );
    public static $list_params = array(
        'title'           => 'Liste des correspondances personnalisées',
        'no_items'        => 'Aucune correspondance personnalisée enregistrée pour le moment',
        'checkboxes'      => 1,
        'bulk_actions'    => array(
            array(
                'label'     => 'Supprimer les correspondances sélectionnées',
                'onclick'   => 'deleteSelectedObjects(\'BDSProcessCustomMatchingValues\', $(this));',
                'btn_class' => 'butActionDelete deleteSelectedObjects'
            )
        ),
        'headers'         => array(
            array(
                'width' => 15,
                'label' => 'Valeur locale',
                'input' => true
            ),
            array(
                'width' => 20,
                'label' => 'Intitulé local',
                'input' => true
            ),
            array(
                'width' => 15,
                'label' => 'Valeur externe',
                'input' => true
            ),
            array(
                'width' => 20,
                'label' => 'Intitulé externe',
                'input' => true
            ),
            array(
                'width' => 30
            )
        ),
        'cols'            => array(
            array(
                'name'  => 'loc_value',
                'input' => 'text'
            ),
            array(
                'name'  => 'loc_label',
                'input' => 'text'
            ),
            array(
                'name'  => 'ext_value',
                'input' => 'text'
            ),
            array(
                'name'  => 'ext_label',
                'input' => 'text'
            )
        ),
        'update_btn'      => 1,
        'delete_btn'      => 1,
        'row_form_inputs' => array(
            array(
                'type'          => 'text',
                'name'          => 'loc_value',
                'id'            => 'loc_value',
                'default_value' => ''
            ),
            array(
                'type'          => 'text',
                'name'          => 'loc_label',
                'id'            => 'loc_label',
                'default_value' => ''
            ),
            array(
                'type'          => 'text',
                'name'          => 'ext_value',
                'id'            => 'ext_value',
                'default_value' => ''
            ),
            array(
                'type'          => 'text',
                'name'          => 'ext_label',
                'id'            => 'ext_label',
                'default_value' => ''
            )
        )
    );

    public static function getClass()
    {
        return 'BDSProcessCustomMatchingValues';
    }
}
