<?php

class BimpList
{

    protected $object = null;

    public function __construct(BimpObject $object)
    {
        $this->object = $object;
    }

    public function fetchItems($params, $id_parent = null)
    {
        $p = BimpTools::getValue('p', isset($params['p']) ? $params['p'] : null);
        $n = BimpTools::getValue('n', isset($params['n']) ? $params['n'] : null);
        $order_by = BimpTools::getValue('order_by', isset($params['order_by']) ? $params['order_by'] : null);
        $order_way = BimpTools::getValue('order_way', isset($params['order_way']) ? $params['order_way'] : null);

        $filters = array();

        if (!is_null($id_parent)) {
            if (isset($this->object->config['parent_id_property'])) {
                $filters[$this->object->config['parent_id_property']] = $id_parent;
            } elseif (isset($this->object->config['fields']['id_parent'])) {
                $filters['id_parent'] = $id_parent;
            }
        } 

        //todo: implémenter la récupération des filtres depuis des formulaires de recherche. 

        return $this->object->getList($filters, $n, $p, $order_by, $order_way, 'array');
    }

    public function render($list_name = 'default', $id_parent = null)
    {
        $html = '';

        $params = null;
        if (isset($this->object->config['lists'][$list_name])) {
            $params = null;
        } elseif (($list_name === 'default') && (isset($this->object->config['list']))) {
            $params = $this->object->config['list'];
        } else {
            return $html;
        }

        if (is_null($id_parent)) {
            $parent_id_property = null;
            if (isset($this->object->config['parent_id_property'])) {
                $parent_id_property = $this->object->config['parent_id_property'];
            } elseif (isset($this->object->config['fields']['id_parent'])) {
                $parent_id_property = 'id_parent';
            }
            if (!is_null($parent_id_property)) {
                if (BimpTools::isSubmit($parent_id_property)) {
                    $id_parent = BimpTools::getValue($parent_id_property);
                } elseif (BimpTools::isSubmit('id_parent')) {
                    $id_parent = BimpTools::getValue('id_parent');
                }
            }
        }

        global $db;
        $form = new Form($db);

        $labels = $this->object->getLabels();

        $html = '<script type="text/javascript">';
        $html .= 'var object_labels = ' . json_encode($labels);
        $html .= '</script>';

        $html .= '<div id="' . $this->object->objectName . '_listContainer">';

        $html .= '<table class="noborder" width="100%">';

        $html .= '<tr class="liste_titre">';
        $html .= '<td>';
        if (isset($params['title'])) {
            $html .= $params['title'];
        } else {
            $html .= 'Liste des ' . $labels['name_plur'];
        }
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td>';

        $html .= '<div id="' . $this->object->objectName . '_list_table" class="objectListTable">';
        if (!is_null($id_parent)) {
            $html .= '<input type="hidden" id="' . $this->object->objectName . '_id_parent" value="' . $id_parent . '"/>';
        }
        $html .= '<table class="noborder" style="border: none" width="100%">';

        $nHeaders = 0;
        if (isset($params['headers'])) {
            $html .= '<thead>';
            $html .= '<tr>';

            if (isset($params['checkboxes']) && $params['checkboxes']) {
                $html .= '<th width="5%" style="text-align: center">';
                $html .= '<input type="checkbox" id="' . $this->object->objectName . '_checkall" onchange="toggleCheckAll(\'' . $this->object->objectName . '\', $(this));"/>';
                $html .= '</th>';
                $nHeaders++;
            }

            foreach ($params['headers'] as $header) {
                $html .= '<th' . (isset($header['width']) ? ' width="' . $header['width'] . '%"' : '' ) . '>';
                if (isset($header['label'])) {
                    $html .= $header['label'];
                }
                $html .= '</th>';
                $nHeaders++;
            }

            $html .= '</tr>';
            $html .= '</thead>';
        }

        $html .= '<tbody>';

        $html .= self::renderRows($list_name, $id_parent);

        if (isset($params['row_form_inputs'])) {
            $html .= '<tr id="' . $this->object->objectName . '_listInputsRow" class="inputsRow">';
            if (isset($params['checkboxes']) && $params['checkboxes']) {
                $html .= '<td></td>';
            }
            if (!is_null($id_parent)) {
                $html .= '<td style="display: none">';
                $html .= '<input typê="hidden" class="objectListRowInput" name="' . static::$parent_id_property . '" ';
                $html .= 'value="' . $id_parent . '" data-default_value="' . $id_parent . '"/>';
                $html .= '</td>';
            }
            foreach ($params['row_form_inputs'] as $input) {
                $html .= '<td' . (($input['type'] === 'hidden') ? ' style="display: none"' : '') . '>';
                if ($input['type'] !== 'empty') {
                    if ($input['type'] === 'switch') {
                        $defVal = 1;
                        $html .= '<select id="rowInput_' . $this->object->objectName . '_' . $input['id'] . '" class="switch objectListRowInput" name="' . $input['name'] . '"';
                        if (isset($input['default_value'])) {
                            $html .= ' data-default_value="' . $input['default_value'] . '"';
                            $defVal = (int) $input['default_value'];
                        }
                        $html .= '>';
                        $html .= '<option value="1"' . (($defVal === 1) ? ' selected' : '') . '>OUI</option>';
                        $html .= '<option value="0"' . (($defVal === 0) ? ' selected' : '') . '>NON</option>';
                        $html .= '</select>';
                    } elseif ($input['type'] === 'datetime') {
                        if (isset($input['default_value'])) {
                            $defVal = $input['default_value'];
                        } else {
                            $defVal = '0000-00-00 00:00';
                        }
                        $DT = new DateTime($defVal);
                        $html .= $form->select_date($DT->getTimestamp(), $input['name'], 1, 1);
                        unset($DT);
                    } else {
                        $html .= '<input type="' . $input['type'] . '" name="' . $input['name'] . '" ';
                        $html .= 'class="objectListRowInput" id="rowInput_' . $this->object->objectName . '_' . $input['id'] . '"';
                        $html .= 'value="';
                        if (isset($input['default_value'])) {
                            $html .= $input['default_value'] . '" data-default_value="' . $input['default_value'];
                        }
                        $html .= '"/>';
                    }
                }
                $html .= '</td>';
            }
            $html .= '<td>';
            $html .= '<span class="butAction" onclick="addObjectFromListInputsRow(\'' . $this->object->objectName . '\', $(this))">Ajouter</span>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '<div class="ajaxResultContainer" id="' . $this->object->objectName . '_listResultContainer"></div>';
        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';

        if (isset($params['bulk_actions'])) {
            $html .= '<tr>';
            $html .= '<td style="padding: 15px 10px">';
            foreach ($params['bulk_actions'] as $action) {
                $html .= '<span class="butAction';
                if (isset($action['btn_class'])) {
                    $html .= ' ' . $action['btn_class'];
                }
                $html .= '" onclick="' . $action['onclick'] . '">';
                $html .= $action['label'];
                $html .= '</span>';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    public function renderRows($list_name = 'default', $id_parent = null)
    {
        $html = '';

        $params = null;
        if (isset($this->object->config['lists'][$list_name])) {
            $params = null;
        } elseif (($list_name === 'default') && (isset($this->object->config['list']))) {
            $params = $this->object->config['list'];
        } else {
            return $html;
        }

        global $db;
        $form = new Form($db);
        $objectName = $this->object->objectName;
        $labels = $this->object->getLabels();
        
        if (is_null($id_parent)) {
            $parent_id_property = null;
            if (isset($this->object->config['parent_id_property'])) {
                $parent_id_property = $this->object->config['parent_id_property'];
            } elseif (isset($this->object->config['fields']['id_parent'])) {
                $parent_id_property = 'id_parent';
            }
            if (!is_null($parent_id_property)) {
                if (BimpTools::isSubmit($parent_id_property)) {
                    $id_parent = BimpTools::getValue($parent_id_property);
                } elseif (BimpTools::isSubmit('id_parent')) {
                    $id_parent = BimpTools::getValue('id_parent');
                }
            }
        }

        $rows = $this->fetchItems($list_name, $id_parent);

        if (count($rows)) {
            foreach ($rows as $r) {
                $html .= '<tr class="' . $objectName . '_row" id="' . $objectName . '_row_' . $r['id'] . '">';
                if (!is_null($id_parent)) {
                    $html .= '<td style="display: none">';
                    $html .= '<input type="hidden" class="objecRowEditInput" value="' . $id_parent . '" name="';
                    if (!is_null($this->object->config['parent_id_property'])) {
                        $html .= $this->object->config['parent_id_property'];
                    } else {
                        $html .= 'id_parent';
                    }
                    $html .= '">';
                    $html .= '</td>';
                }

                if (isset($params['checkboxes']) && $params['checkboxes']) {
                    $html .= '<td style="text-align: center">';
                    $html .= '<input type="checkbox" id_="' . $objectName . '_check_' . $r['id'] . '"';
                    $html .= ' name="' . $objectName . '_check"';
                    $html .= ' class="item_check"';
                    $html .= ' data-id_object="' . $r['id'] . '"';
                    $html .= '</td>';
                }

                if (isset($params['cols'])) {
                    foreach ($params['cols'] as $col) {
                        if (isset($col['params_callback'])) {
                            if (method_exists($objectName, $col['params_callback'])) {
                                $method = $col['params_callback'];
                                $objectName::{$method}($col, $r);
                            }
                        }
                        $html .= '<td' . (isset($col['hidden']) && $col['hidden'] ? ' style="display: none"' : '') . '>';
                        if (isset($col['input'])) {
                            if ($col['input'] === 'text') {
                                $html .= '<input type="text" class="objecRowEditInput" name="' . $col['name'] . '" value="';
                                if (isset($r[$col['name']])) {
                                    $html .= $r[$col['name']];
                                }
                                $html .= '"/>';
                            } elseif ($col['input'] === 'switch') {
                                $val = 0;
                                if (isset($r[$col['name']])) {
                                    $val = (int) $r[$col['name']];
                                }
                                $html .= '<select class="switch objecRowEditInput" name="' . $col['name'] . '">';
                                $html .= '<option value="1"' . (((int) $val !== 0) ? ' selected' : '') . '>OUI</option>';
                                $html .= '<option value="0"' . (((int) $val === 0) ? ' selected' : '') . '>NON</option>';
                                $html .= '</select>';
                            } elseif ($col['input'] === 'datetime') {
                                if (isset($r[$col['name']])) {
                                    $val = $r[$col['name']];
                                } else {
                                    $val = '0000-00-00 00:00';
                                }
                                $DT = new DateTime($val);
                                $html .= $form->select_date($DT->getTimestamp(), $col['name'], 1, 1);
                                unset($DT);
                            } elseif ($col['input'] === 'select') {
                                $html .= '<select class="objecRowEditInput" name="' . $col['name'] . '">';
                                if (isset($col['options'])) {
                                    $options = array();
                                    if (is_array($col['options'])) {
                                        $options = $col['options'];
                                    } elseif (property_exists($objectName, $col['options'])) {
                                        $options = $objectName::${$col['options']};
                                    } else {
                                        $method_name = 'get' . ucfirst($col['options']) . 'QueryArray';
                                        if (method_exists($objectName, $method_name)) {
                                            $options = $objectName::{$method_name}($id_parent);
                                        }
                                    }
                                    foreach ($options as $value => $label) {
                                        $html .= '<option value="' . $value . '">' . $label . '</option>';
                                    }
                                }
                                $html .= '</select>';
                            }
                        } elseif (isset($col['data_type'])) {
                            switch ($col['data_type']) {
                                case 'bool':
                                    if ((int) $r[$col['name']] === 1) {
                                        $html .= '<span class="success">OUI</span>';
                                    } else {
                                        $html .= '</span class="danger">NON</span>';
                                    }
                                    break;

                                case 'array_value':
                                    if (isset($col['array_name'])) {
                                        $array = array();
                                        $array_value = '';
                                        if (property_exists($objectName, $col['array_name'])) {
                                            $array = $objectName::${$col['array_name']};
                                        } else {
                                            $method_name = 'get' . ucfirst($col['array_name']) . 'QueryArray';
                                            if (method_exists($objectName, $method_name)) {
                                                $array = $objectName::{$method_name}($id_parent);
                                            }
                                        }
                                        if (array_key_exists($r[$col['name']], $array)) {
                                            $array_value = $array[$r[$col['name']]];
                                        }
                                    }
                                    if (!$array_value) {
                                        $array_value = $r[$col['name']];
                                    }
                                    $html .= $array_value;
                                    break;

                                case 'datetime':
                                    $date = new DateTime($r[$col['name']]);
                                    $html .= $date->format('d / m / Y H:i');
                                    break;

                                case 'string':
                                default:
                                    $html .= $r[$col['name']];
                                    break;
                            }
                        }
                        $html .= '</td>';
                    }
                }
                $html .= '<td>';
                if (isset($params['update_btn']) && $params['update_btn']) {
                    $html .= '<span class="butAction" onclick="updateObjectFromRow(\'' . $objectName . '\', ' . $r['id'] . ', $(this))">';
                    $html .= 'Mettre à jour';
                    $html .= '</span>';
                }
                if (isset($params['edit_btn']) && $params['edit_btn']) {
                    $html .= '<span class="butAction" onclick="openObjectForm(\'' . $objectName . '\', ' . $id_parent . ', ' . $r['id'] . ')">';
                    $html .= 'Editer';
                    $html .= '</span>';
                }
                if (isset($params['delete_btn']) && $params['delete_btn']) {
                    $html .= '<span class="butActionDelete" onclick="deleteObjects(\'' . $objectName . '\', [' . $r['id'] . '], $(this), ';
                    $html .= '$(\'#' . $objectName . '_listResultContainer\'))">';
                    $html .= 'Supprimer';
                    $html .= '</span>';
                }
                $html .= '</td>';
                $html .= '</tr>';
            }
        } else {
            if (isset($params['headers'])) {
                $nHeaders = count($params['headers']);
                if (isset($params['checkboxes']) && $params['checkboxes']) {
                    $nHeaders++;
                }
            } else {
                $nHeaders = 1;
            }
            $html .= '<tr>';
            $html .= '<td  colspan="' . $nHeaders . '" style="text-align: center">';
            $html .= '<p class="alert alert-info">';
            $html .= 'Aucun' . ($labels['isFemale'] ? 'e' : '') . ' ' . $labels['name'];
            $html .= ' enregistré' . ($labels['isFemale'] ? 'e' : '') . ' pour le moment';
            $html .= '</p>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        return $html;
    }
}
