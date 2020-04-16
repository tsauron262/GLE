<?php

class BDSProcessCronOption extends BDSObject
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
                'width' => 0,
                'input' => true
            ),
            array(
                'width' => 25,
                'label' => 'Option',
                'input' => false
            ),
            array(
                'width' => 15,
                'label' => 'Utiliser la valeur par défaut',
                'input' => true
            ),
            array(
                'width' => 30,
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
                'name'       => 'id_option',
                'data_type'  => 'array_value',
                'array_name' => 'optionsNames'
            ),
            array(
                'name'  => 'use_def_val',
                'input' => 'switch'
            ),
            array(
                'name'            => 'value',
                'params_callback' => 'setValueColInputParams'
            )
        ),
        'update_btn' => 1
    );

    public static function getClass()
    {
        return 'BDSProcessCronOption';
    }

    public static function getOptionsNamesQueryArray($id_parent = null)
    {
        global $db;
        $bdb = new BDSDb($db);

        $where = '`id` IN (SELECT `id_option` FROM ' . MAIN_DB_PREFIX . self::$table;
        $where .= ' WHERE `id_process_cron` = ' . (int) $id_parent . ')';

        $rows = $bdb->getRows(BDSProcessOption::$table, $where, null, 'object', array(
            'id',
            'label'
        ));

        $options = array();
        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $options[(int) $r->id] = $r->label;
            }
        }

        unset($bdb);
        return $options;
    }

    public static function setValueColInputParams(&$col, $row)
    {
        global $db;
        $bdb = new BDSDb($db);

        $type = $bdb->getValue(BDSProcessOption::$table, 'type', '`id` = ' . (int) $row['id_option']);

        if (!is_null($type)) {
            $col['input'] = $type;
        }

        if ($type === 'select') {
            $values = $bdb->getValue(BDSProcessOption::$table, 'select_values', '`id` = ' . (int) $row['id_option']);
            $options = array();
            if (!is_null($values)) {
                $lines = explode(',', $values);
                foreach ($lines as $line) {
                    $data = explode('=>', $line);
                    $options[$data[0]] = $data[1];
                }
            }
            $col['options'] = $options;
        }
        unset($bdb);
    }
    
    
}
