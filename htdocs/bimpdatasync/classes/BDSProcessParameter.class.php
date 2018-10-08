<?php

class BDSProcessParameter extends BDSObject
{

    public static $table = 'bds_process_parameter';
    public static $parent_id_property = 'id_process';
    public $id_process;
    public $name;
    public $label;
    public $value;
    public static $labels = array(
        'name' => 'paramètre'
    );
    public static $fields = array(
        'id_process' => array(
            'label'    => 'ID du processus',
            'required' => true
        ),
        'name'       => array(
            'label'    => 'Nom système',
            'required' => true
        ),
        'label'      => array(
            'label'    => 'Nom public',
            'required' => true
        ),
        'value'      => array(
            'label' => 'Valeur'
        )
    );
    public static $list_params = array(
        'title'           => 'Liste des paramètres',
        'no_items'        => 'Aucun paramètre enregistré pour le moment',
        'checkboxes'      => 1,
        'bulk_actions'    => array(
            array(
                'label'     => 'Supprimer les paramètres sélectionnés',
                'onclick'   => 'deleteSelectedObjects(\'BDSProcessParameter\', $(this));',
                'btn_class' => 'butActionDelete deleteSelectedObjects'
            )
        ),
        'headers'         => array(
            array(
                'width' => 20,
                'label' => 'Nom système',
                'input' => true
            ),
            array(
                'width' => 25,
                'label' => 'Nom public',
                'input' => true
            ),
            array(
                'width' => 25,
                'label' => 'Valeur',
                'input' => true
            ),
            array(
                'width' => 30
            )
        ),
        'cols'            => array(
            array(
                'name'  => 'name',
                'input' => 'text'
            ),
            array(
                'name'  => 'label',
                'input' => 'text'
            ),
            array(
                'name'  => 'value',
                'input' => 'text'
            )
        ),
        'update_btn'      => 1,
        'delete_btn'      => 1,
        'row_form_inputs' => array(
            array(
                'type'          => 'text',
                'name'          => 'name',
                'id'            => 'name',
                'default_value' => ''
            ),
            array(
                'type'          => 'text',
                'name'          => 'label',
                'id'            => 'label',
                'default_value' => ''
            ),
            array(
                'type'          => 'text',
                'name'          => 'value',
                'id'            => 'value',
                'default_value' => ''
            )
        )
    );

    public static function getClass()
    {
        return 'BDSProcessParameter';
    }

    public static function getParameterLabel(BDSDb $bdb, $id_process, $name)
    {
        $where = '`id_process` = ' . (int) $this->processDefinition->id;
        $where .= ' AND `name` = \'' . $name . '\'';
        $title = $this->db->getValue(static::$table, 'label', $where);
        if (is_null($title)) {
            return $name;
        }
        return $title;
    }
}
