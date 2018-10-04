<?php

require_once __DIR__ . '/BDSObject.php';

class Manufacturer extends BDSObject
{

    public static $table = 'manufacturer';
    public $name;
    public $ref_prefixe;
    public static $labels = array(
        'name' => 'fabricant'
    );
    public static $fields = array(
        'name'        => array(
            'label'    => 'Nom du fabricant',
            'required' => true
        ),
        'ref_prefixe' => array(
            'label'    => 'Préfixe dans les références produits',
            'required' => true
        )
    );
    public static $list_params = array(
        'title'           => 'Liste des fabricants',
        'no_items'        => 'Aucun fabricant enregistré pour le moment',
        'checkboxes'      => 1,
        'bulk_actions'    => array(
            array(
                'label'     => 'Supprimer les fabricants sélectionnés',
                'onclick'   => 'deleteSelectedObjects(\'Manufacturer\', $(this));',
                'btn_class' => 'butActionDelete deleteSelectedObjects'
            )
        ),
        'headers'         => array(
            array(
                'width' => 10,
                'label' => 'ID Fabricant',
                'input' => true
            ),
            array(
                'width' => 25,
                'label' => 'Nom',
                'input' => true
            ),
            array(
                'width' => 30,
                'label' => 'Préfixe références',
                'input' => true
            ),
            array(
                'width' => 35
            )
        ),
        'cols'            => array(
            array(
                'name'      => 'id',
                'data_type' => 'string'
            ),
            array(
                'name'  => 'name',
                'input' => 'text'
            ),
            array(
                'name'  => 'ref_prefixe',
                'input' => 'text'
            )
        ),
        'update_btn'      => 1,
        'delete_btn'      => 1,
        'row_form_inputs' => array(
            array(
                'type' => 'empty'
            ),
            array(
                'type'          => 'text',
                'name'          => 'name',
                'id'            => 'name',
                'default_value' => ''
            ),
            array(
                'type'          => 'text',
                'name'          => 'ref_prefixe',
                'id'            => 'ref_prefixe',
                'default_value' => ''
            )
        )
    );

    public static function getClass()
    {
        return 'Manufacturer';
    }
}
