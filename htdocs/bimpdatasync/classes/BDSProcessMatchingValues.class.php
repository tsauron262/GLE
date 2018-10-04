<?php

class BDSProcessMatchingValues extends BDSObject
{

    public static $table = 'bds_process_matching_values';
    public static $parent_id_property = 'id_process';
    public $id_process;
    public $name;
    public $label;
    public $description;
    public $type;
    public $ext_label;
    public $loc_label;
    public $loc_table;
    public $field_in;
    public $field_out;
    public static $labels = array(
        'name'      => 'correspondance de valeurs',
        'name_plur' => 'correspondances de valeurs',
        'isFemale'  => true
    );
    public static $types = array(
        'loc_table' => 'Table de données locale',
        'custom'    => 'Correspondances personnalisées'
    );
    public static $objects = array(
        'custom_values' => array(
            'class_name' => 'BDSProcessCustomMatchingValues',
            'relation'   => 'HasMany',
            'delete'     => true
        )
    );
    public static $fields = array(
        'id_process'  => array(
            'label'    => 'ID du processus',
            'type'     => 'int',
            'required' => true,
        ),
        'name'        => array(
            'label'    => 'Nom système',
            'type'     => 'string',
            'input'    => 'text',
            'required' => true
        ),
        'label'       => array(
            'label'    => 'Nom public',
            'type'     => 'string',
            'input'    => 'text',
            'required' => true
        ),
        'description' => array(
            'label'    => 'Description',
            'type'     => 'string',
            'input'    => 'textarea',
            'required' => false
        ),
        'type'        => array(
            'label'        => 'Type',
            'input'        => 'select',
            'options'      => 'types',
            'is_key_array' => 'types',
            'required'     => true,
        ),
        'ext_label'   => array(
            'label'    => 'Intitulé de la valeur externe',
            'type'     => 'string',
            'input'    => 'text',
            'required' => true
        ),
        'loc_label'   => array(
            'label'    => 'Intitulé de la valeur locale',
            'type'     => 'string',
            'input'    => 'text',
            'required' => true
        ),
        'loc_table'   => array(
            'label'       => 'Table locale',
            'type'        => 'string',
            'input'       => 'text',
            'display_if'  => array(
                'input_name'  => 'type',
                'show_values' => 'loc_table'
            ),
            'required'    => false,
            'required_if' => 'type=loc_table',
        ),
        'field_in'    => array(
            'label'       => 'Champ des valeurs en entrée',
            'type'        => 'string',
            'input'       => 'text',
            'display_if'  => array(
                'input_name'  => 'type',
                'show_values' => 'loc_table'
            ),
            'required'    => false,
            'required_if' => 'type=loc_table'
        ),
        'field_out'   => array(
            'label'       => 'Champ des valeurs en sortie',
            'type'        => 'string',
            'input'       => 'text',
            'display_if'  => array(
                'input_name'  => 'type',
                'show_values' => 'loc_table'
            ),
            'required'    => false,
            'required_if' => 'type=loc_table'
        ),
    );
    public static $list_params = array(
        'checkboxes'   => 1,
        'bulk_actions' => array(
            array(
                'label'     => 'Supprimer les Correspondances sélectionnées',
                'onclick'   => 'deleteSelectedObjects(\'BDSProcessMatchingValues\', $(this));',
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
                'width' => 25,
                'label' => 'Description',
            ),
            array(
                'width' => 15,
                'label' => 'Type',
            ),
            array(
                'width' => 30
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
                'name'      => 'description',
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
        return 'BDSProcessMatchingValues';
    }

    public static function getTypesQueryArray($id_parent = null)
    {
        return self::$types;
    }

    public function renderEditForm($id_parent = null)
    {
        $html = parent::renderEditForm($id_parent);

        if (isset($this->id) && $this->id) {
            if ($this->type === 'custom') {
                if (isset($this->label) && $this->label) {
                    BDSProcessCustomMatchingValues::$list_params['title'] .= ' pour: "' . $this->label . '"';
                }
                if (isset($this->loc_label) && $this->loc_label) {
                    BDSProcessCustomMatchingValues::$list_params['headers'][0]['label'] = $this->loc_label;
                }
                if (isset($this->ext_label) && $this->ext_label) {
                    BDSProcessCustomMatchingValues::$list_params['headers'][2]['label'] = $this->ext_label;
                }
                $html .= BDSProcessCustomMatchingValues::renderList($this->id);
            }
        }

        return $html;
    }

    public function getMatchedValue($value, $processType = 'import')
    {
        $table = 0;
        $select = 0;
        $where = 0;

        switch ($this->type) {
            case 'loc_table':
                $table = $this->ps_table;
                switch ($processType) {
                    case 'import':
                        $select = $this->field_out;
                        $where = '`' . $this->field_in . '`';
                        break;

                    case 'export':
                        $select = $this->field_in;
                        $where = '`' . $this->field_out . '`';
                        break;
                }
                break;

            case 'custom':
                $table = 'bds_process_custom_matching_values';
                $where = '`id_match` = ' . (int) $this->id;

                switch ($processType) {
                    case 'import':
                        $select = 'loc_value';
                        $where .= ' AND `ext_value`';
                        break;

                    case 'export':
                        $select = 'ext_value';
                        $where .= ' AND `loc_value`';
                        break;
                }
                break;
        }

        if ($table && $where && $select) {
            $where .= ' = \'' . $value . '\'';
            return $this->db->getValue($table, $select, $where);
        }
        return null;
    }

    public static function createInstanceByName($id_process, $name)
    {
        global $db;
        $bdb = new BDSDb($db);

        $where = '`id_process` = ' . (int) $id_process;
        $where .= ' AND `name` = \'' . $name . '\'';

        $id = $bdb->getValue(self::$table, 'id', $where);

        $match = null;
        if (!is_null($id)) {
            $match = new BDSProcessMatchingValues();
            if (!$match->fetch($id) || !$match->id) {
                unset($match);
                $match = null;
            }
        }
        unset($bdb);
        return $match;
    }
}
