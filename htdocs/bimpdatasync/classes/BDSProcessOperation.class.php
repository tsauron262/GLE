<?php

class BDSProcessOperation extends BDSObject
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
            'type'     => 'int',
            'required' => true
        ),
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
        'warning'     => array(
            'label'    => 'Alertes',
            'type'     => 'string',
            'input'    => 'textarea',
            'required' => false
        )
    );
    public static $associations = array(
        'options' => array(
            'class_name'    => 'BDSProcessOption',
            'relation'      => 'ManyToMany',
            'same_parent'   => true,
            'table'         => 'bds_process_operation_option',
            'self_key'      => 'id_operation',
            'associate_key' => 'id_option'
        )
    );

    public static function getClass()
    {
        return 'BDSProcessOperation';
    }

    public static function getOperationOptions($id_operation)
    {
        global $db;
        $bdb = new BDSDb($db);
        $sql = 'SELECT o.* FROM ' . MAIN_DB_PREFIX . 'bds_process_option o ';
        $sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'bds_process_operation_option oo ';
        $sql .= 'ON oo.id_option = o.id ';
        $sql .= 'WHERE oo.id_operation = ' . (int) $id_operation;

        $options = $bdb->executeS($sql, 'array');
        if (!is_null($options)) {
            foreach ($options as &$option) {
                if ($option['type'] === 'select') {
                    $values = explode(',', $option['select_values']);
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

    public static function renderList($id_parent = null)
    {
        $html = '<link type="text/css" rel="stylesheet" href="' . DOL_URL_ROOT . '/bimpdatasync/views/css/operations.css"/>';
        $html .= '<link type="text/css" rel="stylesheet" href="' . DOL_URL_ROOT . '/bimpdatasync/views/css/reports.css"/>';

        $html .= '<script type="text/javascript">';
        $html .= 'var object_labels = ' . json_encode(BDSProcessOperation::getLabels());
        $html .= '</script>';

        $html .= '<script type="text/javascript" src="' . DOL_URL_ROOT . '/bimpdatasync/views/js/reports.js"></script>';
        $html .= '<script type="text/javascript" src="' . DOL_URL_ROOT . '/bimpdatasync/views/js/operations.js"></script>';

        $html .= '<div id="contentContainer">';
        $html .= '<div id="' . static::getClass() . '_listContainer">';
        $html .= '<div id="' . static::getClass() . '_list_table" class="objectListTable">';

        if (!is_null($id_parent)) {
            $html .= '<input type="hidden" id="' . static::getClass() . '_id_parent" value="' . $id_parent . '"/>';
        }

        $html .= '<table class="noborder" style="border: none" width="100%">';
        $html .= '<tbody>';

        $html .= static::renderListRows($id_parent);

        $html .= '</tbody>';
        $html .= '</table>';

        $html .= '<div class="ajaxResultContainer" id="' . static::getClass() . '_listResultContainer"></div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public static function renderListRows($id_parent = null)
    {
        global $db;
        $bdb = new BDSDb($db);

        $operations = self::getListData($bdb, $id_parent);

        foreach ($operations as &$o) {
            $o['options'] = self::getOperationOptions($o['id']);
        }

        $html .= '<tr><td>';
        foreach ($operations as $operation) {
            $html .= '<div id="operation_' . $operation['id'] . '">';
            $html .= '<table class="noborder operationTable" width="100%">';

            $html .= '<tr class="liste_titre"><td>' . $operation['title'] . '</td></tr>';

            if (isset($operation['description']) && $operation['description']) {
                $html .= '<tr><td><div class="alert alert-info">' . $operation['description'] . '</div></td></tr>';
            }

            if (isset($operation['warning']) && $operation['warning']) {
                $html .= '<tr><td><div class="alert alert-warning">' . $operation['warning'] . '</div></td></tr>';
            }

            if (isset($operation['options']) && count($operation['options'])) {
                $html .= '<tr><td>';
                $html .= '<form id="process_' . $id_parent . '_operation_' . $operation['id'] . '_options_form">';
                foreach ($operation['options'] as $option) {
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
                                $html .= '<option name="' . $value['id'] . '"';
                                if ($defVal == $value['id']) {
                                    $html .= ' selected';
                                }
                                $html .= '>' . $value['label'] . '</option>';
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
                    if (isset($option['info']) && $option['info']) {
                        $html .= '<div class="inputDesc">';
                        $html .= '<p>' . $option['info'] . '</p>';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                }
                $html .= '</form>';
                $html .= '</td></tr>';
            }

            $html .= '<tr>';
            $html .= '<td>';
            $html .= '<div id="operation_' . $operation['id'] . '_resultContainer" style="display: none"></div>';
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '<tr><td>';
            $html .= '<div class="formSubmit">';
            $html .= '<span class="butAction" style="float: left" onclick="openObjectForm(\'' . static::getClass() . '\', ' . $id_parent . ', ' . $operation['id'] . ')">Editer</span>';
            $html .= '<span class="butActionDelete" style="float: left" onclick="deleteObjects(\'' . static::getClass() . '\', [' . $operation['id'] . '], $(this), ';
            $html .= '$(\'#' . static::getClass() . '_listResultContainer\'))">Supprimer</span>';
            $html .= '<span class="butAction" onclick="initProcessOperation($(this), ' . $id_parent . ', ' . $operation['id'] . ')">Exécuter</span>';
            $html .= '</div>';
            $html .= '</td></tr>';

            $html .= '</table>';
            $html .= '</div>';
        }

        $html .= '</tr></td>';

        return $html;
    }
    
    public function saveAssociations($association, $list)
    {
        $errors = parent::saveAssociations($association, $list);
        BDSProcessCron::checkAllOptions($this->id_process);
        return $errors;
    }
}
