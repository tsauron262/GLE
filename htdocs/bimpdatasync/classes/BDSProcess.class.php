<?php

include_once __DIR__ . '/BimpObject.php';

class BDSProcess extends BimpObject
{

    public static $table = 'bds_process';
    public $id;
    public $name;
    public $title;
    public $description;
    public $type;
    public $active;
    public static $types = array(
        'import' => 'Import',
        'export' => 'Export',
        'sync'   => 'Synchronisation'
    );
    public static $labels = array(
        'name'      => 'processus',
        'isFemale'  => false,
        'name_plur' => 'processus'
    );
    public static $fields = array(
        'name'        => array(
            'label'    => 'Nom système',
            'type'     => 'string',
            'input'    => 'text',
            'required' => true
        ),
        'title'       => array(
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
        'active'      => array(
            'label'         => 'Activé',
            'type'          => 'bool',
            'input'         => 'switch',
            'required'      => false,
            'default_value' => 0
        )
    );
    public static $objects = array(
        'parameters'     => array(
            'class_name' => 'BDSProcessParameter',
            'relation'   => 'HasMany',
            'delete'     => true
        ),
        'options'        => array(
            'class_name' => 'BDSProcessOption',
            'relation'   => 'HasMany',
            'delete'     => true
        ),
        'trigger_action' => array(
            'class_name' => 'BDSProcessTriggerAction',
            'relation'   => 'HasMany',
            'delete'     => true
        ),
        'operations'     => array(
            'class_name' => 'BDSProcessOperation',
            'relation'   => 'HasMany',
            'delete'     => true
        )
    );

//    public static $objects = array(
//        'options'        => array(
//            'table_suffixe' => 'option',
//            'label'         => 'Option',
//            'fields'        => array(
//            )
//        ),
//        'triggerActions' => array(
//            'table_suffixe' => 'trigger_action',
//            'label'         => 'Trigger',
//            'fields'        => array(
//            )
//        )
//    );

    public static function getClass()
    {
        return 'BDSProcess';
    }

    public static function getTypesQueryArray()
    {
        return self::$types;
    }

    public static function getProcessesQuery()
    {
        global $db;
        $bdb = new BimpDb($db);

        $processes = array();
        $rows = $bdb->getRows(self::$table);
        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $processes[$r->id] = array(
                    'id'   => $r->id,
                    'name' => $r->title
                );
            }
        }

        ksort($processes);
        return $processes;
    }

    public function getOperationsData()
    {
        $operations = BDSProcessOperation::getListData($this->db, $this->id);
        foreach ($operations as &$o) {
            $o['options'] = BDSProcessOperation::getOperationOptions($o['id']);
        }
        return $operations;
    }

    public function renderOperations()
    {
        ini_set('display_errors', 1);
        $operations = $this->getOperationsData();

        $html = '<link type="text/css" rel="stylesheet" href="'. DOL_URL_ROOT . '/bimpdatasync/views/css/operations.css"/>';
        $html .= '<link type="text/css" rel="stylesheet" href="'. DOL_URL_ROOT . '/bimpdatasync/views/css/reports.css"/>';
        
        $html .= '<script type="text/javascript">';
        $html .= 'var object_labels = ' . json_encode(BDSProcessOperation::getLabels());
        $html .= '</script>';

        $html .= '<script type="text/javascript" src="' . DOL_URL_ROOT . '/bimpdatasync/views/js/reports.js"></script>';
        $html .= '<script type="text/javascript" src="' . DOL_URL_ROOT . '/bimpdatasync/views/js/operations.js"></script>';
        
        $html .= '<div id="contentContainer">';
        $html .= '<div id="operationsListContainer">';

        foreach ($operations as $o) {
            $html .= '<div id="operation_' . $o['id'] . '">';
            $html .= '<table class="noborder operationTable" width="100%">';

            $html .= '<tr class="liste_titre"><td>' . $o['title'] . '</td></tr>';

            if (isset($o['description']) && $o['description']) {
                $html .= '<tr><td><div class="alert alert-info">' . $o['description'] . '</div></td></tr>';
            }

            if (isset($o['warning']) && $o['warning']) {
                $html .= '<tr><td><div class="alert alert-warning">' . $o['warning'] . '</div></td></tr>';
            }

            if (isset($o['options']) && count($o['options'])) {
                $html .= '<tr><td>';
                $html .= '<form id="process_' . $this->id . '_operation_' . $o['id'] . '_options_form">';
                foreach ($o['options'] as $option) {
                    $html .= '<div class="formRow">';
                    $html .= '<div class="formLabel">' . $option['label'] . ': </div>';
                    $html .= '<div class="formInput">';
                    $defVal = (isset($option['default_value']) ? $option['default_value'] : '');
                    switch ($option['type']) {
                        case 'text':
                            $html .= '<input type="text" name="' . $option['name'] . '" value="' . $defVal . '" style="width: 350px"/>';
                            break;

                        case 'select':
                            $html .= '<select name="' . $option['name'] . '" style="width: 350px">';
                            foreach ($option['values'] as $value) {
                                $html .= '<option name="' . $value['id'] . '">' . $value['label'] . '</option>';
                            }
                            $html .= '</select>';
                            break;

                        case 'switch':
                            $html .= '<select class="switch" name="' . $option['name'] . '">';
                            $html .= '<option value="1"' . ((int) $defVal ? ' selected' : '') . '>OUI</option>';
                            $html .= '<option value="0"' . (!(int) $defVal ? ' selected' : '') . '>NON</option>';
                            $html .= '</select>';
                            break;
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                }
                $html .= '</form>';
                $html .= '</td></tr>';

                $html .= '<tr>';
                $html .= '<td>';
                $html .= '<div id="operation_' . $o['id'] . '_resultContainer" style="display: none"></div>';
                $html .= '</td>';
                $html .= '</tr>';

                $html .= '<tr><td>';
                $html .= '<div class="formSubmit">';
                $html .= '<span class="button" onclick="initProcessOperation($(this), ' . $this->id . ', ' . $o['id'] . ')">Exécuter</span>';
                $html .= '</div>';
                $html .= '</td></tr>';
            }

            $html .= '</table>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
