<?php

class BDSProcessOperation extends BimpObject
{

    public static $table = 'bds_process_operation';
    public static $parent_id_property = 'id_process';
    public $id_process;
    public $name;
    public $title;
    public $description;
    public $warning;
    public static $labels = array(
        'name'     => 'opération',
        'isFemale' => 1
    );
    public static $fields = array(
        'id_process'  => array(
            'label'    => 'ID du processus',
            'required' => true
        ),
        'name'        => array(
            'label'    => 'Nom système',
            'required' => true
        ),
        'title'       => array(
            'label'    => 'Nom public',
            'required' => true
        ),
        'description' => array(
            'label'    => 'Description',
            'required' => false
        ),
        'warning'     => array(
            'label'    => 'Alertes',
            'required' => false
        )
    );

    public static function getClass()
    {
        return 'BDSProcessOperation';
    }

    public static function getOperationOptions($id_operation)
    {
        global $db;
        $bdb = new BimpDb($db);
        $sql = 'SELECT o.* FROM '.MAIN_DB_PREFIX.'bds_process_option o ';
        $sql .= 'LEFT JOIN '.MAIN_DB_PREFIX.'bds_process_operation_option oo ';
        $sql .= 'ON oo.id_option = o.id ';
        $sql .= 'WHERE oo.id_operation = '.(int) $id_operation;
        
        $options = $bdb->executeS($sql, 'array');
        if (!is_null($options)) {
            foreach ($options as &$option) {
                if ($option['type'] === 'select') {
                    $values = explode(',', $option['values']);
                    $option['values'] = array();
                    foreach ($values as $value) {
                        $data = explode('=>', $value);
                        $option['values'][] = array(
                            'id'    => $data[0],
                            'label' => (isset($data[1]) ? $data[1] : $data[0])
                        );
                    }
                }
            }
            return $options;
        }
        
        return array();
    }
}
