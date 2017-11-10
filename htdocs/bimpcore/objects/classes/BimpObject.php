<?php

class BimpObject
{

    public $db = null;
    public $module = '';
    public $objectName = '';
    public $config = null;
    public $id = null;
    protected $data = array();

    public static function getInstance($module, $object_name)
    {
        $file = DOL_DOCUMENT_ROOT . $module . '/objects/' . $object_name . '.class.php';
        if (file_exists($file)) {
            if (!class_exists($object_name)) {
                require_once $file;
            }
            return new $object_name($module, $object_name);
        }

        return new BimpObject($module, $object_name);
    }

    public function __construct($module, $object_name)
    {
        global $db;

        $this->db = new BimpDb($db);
        $this->module = $module;
        $this->object_name = $object_name;
        $this->config = self::loadConfig($module, $object_name);

        $this->checkConfig($this->config, array('table', 'fields', 'labels'));
    }

    // Gestion des données:

    public function get($field)
    {
        if (!isset($this->config['fields'][$field])) {
            return null;
        }

        if (isset($this->data[$field])) {
            return $this->data[$field];
        }

        if (isset($this->config['fields'][$field]['default_value'])) {
            return $this->config['fields'][$field]['default_value'];
        }
        return null;
    }

    public function set($field, $value)
    {
        return $this->validateValue($field, $value);
    }

    // Validation des champs:

    public function validatePost()
    {
        if (!isset($this->config['fields'])) {
            return array();
        }

        $errors = array();
        foreach ($this->config['fields'] as $field => $params) {
            $value = null;
            if (BimpTools::isSubmit($field)) {
                if ($params['type'] === 'datetime') {
                    $value = BimpTools::getDateTimeFromForm($field);
                } else {
                    $value = BimpTools::getValue($field);
                }
            } elseif (isset($this->data[$field])) {
                $value = $this->data[$field];
            } elseif (isset($params['default_value'])) {
                $value = $params['default_value'];
            }

            $errors = array_merge($errors, $this->validateValue($field, $value));
        }
        return $errors;
    }

    public function validateArray(Array $values)
    {
        if (!isset($this->config['fields'])) {
            return array();
        }

        $errors = array();
        foreach ($this->config['fields'] as $field => $params) {
            $value = null;
            if (isset($values[$field])) {
                $value = $values[$field];
            } elseif (isset($this->data[$field])) {
                $value = $this->data[$field];
            } elseif (isset($params['default_value'])) {
                $value = $params['default_value'];
            }

            $errors = array_merge($errors, $this->validateValue($field, $value));
        }
        return $errors;
    }

    public function validateValue($field, $value)
    {
        $errors = array();

        if (!isset($this->config['fields'][$field])) {
            $errors[] = 'Le champ "' . $field . '" n\'existe pas';
        } else {
            $params = $this->config['fields'][$field];

            if (is_null($value) || ($value === '')) {
                $missing = false;
                if (isset($params['required']) && $params['required']) {
                    $missing = true;
                } elseif (isset($params['required_if'])) {
                    $required_if = explode('=', $params['required_if']);
                    $property = $required_if[0];
                    if (isset($this->data[$property])) {
                        if ($this->data[$property] == $required_if[1]) {
                            $missing = true;
                        }
                    }
                }
                if ($missing) {
                    $errors[] = 'Valeur obligatoire manquante : "' . $params['label'] . '"';
                }
            } else {
                $validate = true;
                if ($params['type'] === 'datetime') {
                    if (!preg_match('/^\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}(:\d{2})?$/', $value)) {
                        $validate = false;
                        if (!isset($params['invalid_msg'])) {
                            $params['invalid_msg'] = 'Format attendu: AAAA-MM-JJ HH:MM:SS';
                        }
                    }
                }

                if (!count($errors) && isset($params['regexp'])) {
                    if (!preg_match('/' . $params['regexp'] . '/', $value)) {
                        $validate = false;
                    }
                }
                if (!count($errors) && isset($params['is_key_array']) && is_array($params['is_key_array'])) {
                    if (!array_key_exists($value, $params['is_key_array'])) {
                        $validate = false;
                    }
                }
                if (!count($errors) && isset($params['in_array']) && is_array($params['in_array'])) {
                    if (!in_array($value, $params['in_array'])) {
                        $validate = false;
                    }
                }

                if (!$validate) {
                    $msg = '"' . $params['label'] . '": valeur invalide';
                    if (isset($params['invalid_msg'])) {
                        $msg .= ' (' . $params['invalid_msg'] . ')';
                    }
                    $errors[] = $msg;
                }

                if (!count($errors)) {
                    $this->data[$field] = $value;
                }
            }
        }
        return $errors;
    }

    public function validate()
    {
        if (!isset($this->config['fields'])) {
            return array();
        }

        $errors = array();
        foreach ($this->config['fields'] as $field => $params) {
            $errors = array_merge($errors, $this->validateValue($field, isset($this->data[$field]) ? $this->data[$field] : null));
        }
        return $errors;
    }

    // Gestion SQL: 

    public function fetch($id)
    {
        if (!self::checkConfig($this->config, array('table', 'fields'))) {
            return false;
        }

        $row = $this->db->getRow($this->config['table'], '`id` = ' . (int) $id);
        if (!is_null($row)) {
            foreach ($row as $field => $value) {
                if (isset($this->config['fields'][$field])) {
                    $this->data[$field] = $value;
                }
            }
            return true;
        }
        return false;
    }

    public function fetchBy($field, $value)
    {
        if (!self::checkConfig($this->config, array('table', 'fields'))) {
            return false;
        }

        if (!isset($this->config['fields'][$field])) {
            BimpTools::logTechnicalError($this, 'fetchBy', 'Le champ "' . $field . '" n\'existe pas');
            return false;
        }

        if ($this->config['fields'][$field]['type'] !== 'id') {
            BimpTools::logTechnicalError($this, 'fetchBy', 'Le champ "' . $field . '" doit être de type "id"');
            return false;
        }

        $where = '`' . $field . '` = ' . is_string($value) ? '\'' . $value . '\'' : $value;

        $row = $this->db->getRow($this->config['table'], $where);

        if (!is_null($row)) {
            foreach ($row as $property => $value) {
                if (isset($this->config['fields'][$property])) {
                    $this->data[$property] = $value;
                }
            }
            return true;
        }
        return false;
    }

    public function update()
    {
        if (!self::checkConfig($this->config, array('table', 'fields'))) {
            return array('Fichier de configuration invalide');
        }

        $errors = array();
        if (is_null($this->id) || !$this->id) {
            return array('ID Absent');
        }
        $errors = $this->validate();

        if (!count($errors)) {
            $result = $this->db->update($this->config['table'], $this->data, '`id` = ' . (int) $this->id);
            if ($result <= 0) {
                $msg = 'Echec de la mise à jour ' . $this->getLabel('of_the');
                $sqlError = $this->db->db->error();
                if ($sqlError) {
                    $msg .= ' - Erreur SQL: ' . $sqlError;
                }
                $errors[] = $msg;
            }
        }

        return $errors;
    }

    public function create()
    {
        if (!self::checkConfig($this->config, array('table', 'fields'))) {
            return array('Fichier de configuration invalide');
        }

        $errors = $this->validate();

        if (!count($errors)) {
            $result = $this->db->insert($this->config['table'], $this->data, true);
            if ($result > 0) {
                $this->id = $result;
            } else {
                $msg = 'Echec de l\'enregistrement ' . $this->getLabel('of_the');
                $sqlError = $this->db->db->error();
                if ($sqlError) {
                    $msg .= ' - Erreur SQL: ' . $sqlError;
                }
                $errors[] = $msg;
            }
        }

        return $errors;
    }

    public function delete()
    {
        if (!self::checkConfig($this->config, array('table'))) {
            return array('Fichier de configuration invalide');
        }

        if (is_null($this->id) || !$this->id) {
            return array('ID absent');
        }

        $errors = array();
        $result = $this->db->delete($this->config['table'], '`id` = ' . (int) $this->id);
        if ($result <= 0) {
            $msg = 'Echec de la suppression ' . $this->getLabel('of_the');
            $sqlError = $this->db->db->error();
            if ($sqlError) {
                $msg .= ' - Erreur SQL: ' . $sqlError;
            }
            $errors[] = $msg;
        } else {
            if (isset($this->config['objects'])) {
                foreach ($this->config['objects'] as $name => $params) {
                    if (isset($params['delete']) && $params['delete']) {
                        $object_name = $params['object_name'];
                        $module = isset($params['module']) ? $params['module'] : $this->module;
                        $instance = self::getInstance($module, $object_name);
                        if (!$instance->deleteByParent($this->db, $this->id)) {
                            $msg = 'Des erreurs sont survenues lors de la tentative de suppresion ';
                            $msg += 'des ' . $instance->getLabel('name_plur');
                            $errors[] = $msg;
                        }
                    }
                }
            }

            if (isset($this->config['associations'])) {
                foreach ($this->config['associations'] as $name => $params) {
                    $where = '`' . $params['self_key'] . '` = ' . (int) $this->id;
                    $result = $this->db->delete($params['table'], $where);
                    if ($result <= 0) {
                        $asso_module = isset($params['module']) ? $params['module'] : $this->module;
                        $asso_instance = self::getInstance($asso_module, $params['object_name']);
                        $msg = 'Echec de la suppression des associations avec les ' . $asso_instance->getLabel('name_plur');
                        $errors[] = $msg;
                    }
                }
            }
        }

        return $errors;
    }

    public function getDataArray($include_id = false)
    {
        if (!$this->checkConfig($this->config, array('fields'))) {
            return array();
        }

        $data = array();
        foreach ($this->config['fields'] as $field => $params) {
            if (isset($this->data[$field])) {
                $data[$field] = $this->data[$field];
            } elseif (isset($this->config['fields'][$field]['default_value'])) {
                $data[$field] = $this->config['fields'][$field]['default_value'];
            } else {
                $data[$field] = null;
            }
        }
        if ($include_id) {
            if (!is_null($this->id)) {
                $data['id'] = $this->id;
            } else {
                $data['id'] = 0;
            }
        }
        return $data;
    }

    public function getList($filters = array(), $n = null, $p = null, $order_by = 'id', $order_way = 'DESC', $return = 'array', $return_fields = null)
    {
        if (!$this->checkConfig($this->config, array('table'))) {
            return array();
        }

        $sql = 'SELECT ';

        if (!is_null($return_fields)) {
            $first_loop = true;
            foreach ($return_fields as $field) {
                if (!$first_loop) {
                    $sql .= ', ';
                } else {
                    $first_loop = false;
                }
                $sql .= '`' . $field . '`';
            }
        } else {
            $sql .= '*';
        }

        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->config['table'];

        if (count($filters)) {
            $sql .= ' WHERE ';
            $first_loop = true;
            foreach ($filters as $field => $value) {
                if (!$first_loop) {
                    $sql .= ' AND ';
                } else {
                    $first_loop = false;
                }

                $sql .= '`' . $field . '` = ' . is_string($value) ? '\'' . $value . '\'' : $value;
            }
        }

        $sql .= ' ORDER BY `' . $order_by . '` ' . $order_way;

        if (!is_null($n)) {
            if (is_null($p)) {
                $p = 1;
            }

            if ($p > 1) {
                $offset = (($n * ($p - 1)) - 1);
            } else {
                $offset = 0;
            }
            $sql .= ' LIMIT ' . $offset . ', ' . $n;
        }

        $rows = $this->db->executeS($sql, $return);

        if (is_null($rows)) {
            $rows = array();
        }

        return $rows;
    }

    public function getListByParent($id_parent, $n = null, $p = null, $order_by = 'id', $order_way = 'DESC', $return = 'array')
    {
        if ($this->checkConfig(array('table', 'parent_id_property'))) {
            return self::getList(array(
                        $this->config['parent_id_property'] => $id_parent
                            ), $n, $p, $order_by, $order_way, $return);
        }
        return array();
    }

    public function saveAssociations($association, $list)
    {
        $errors = array();

        if (!isset($this->config['associations'][$association])) {
            $errors[] = 'le type d\'association "' . $association . '" n\'est pas valide';
        } elseif (is_null($this->id) || !$this->id) {
            $errors[] = 'ID Absent';
        } else {
            $params = $this->config['associations'][$association];
            $result = $this->db->delete($params['table'], '`' . $params['self_key'] . '` = ' . (int) $this->id);
            if ($result <= 0) {
                $msg = 'Echec de la suppression des associations existantes';
                $sqlError = $this->db->db->error();
                if ($sqlError) {
                    $msg .= ' - Erreur SQL: ' . $sqlError;
                }
                $errors[] = $msg;
            } else {
                $module = isset($params['module']) ? $params['module'] : $this->module;
                $instance = self::getInstance($module, $params['object_name']);
                $label = $instance->getLabel('the');
                foreach ($list as $id) {
                    $data = array();
                    $data[$params['self_key']] = (int) $this->id;
                    $data[$params['associate_key']] = (int) $id;
                    if (!$this->db->insert($params['table'], $data)) {
                        $msg = 'Echec de l\'enregistrement de l\'association avec ' . $label . ' d\'ID ' . $id;
                        $sqlError = $this->db->db->error();
                        if ($sqlError) {
                            $msg .= ' - Erreur SQL: ' . $sqlError;
                        }
                        $errors[] = $msg;
                    }
                }
            }
        }

        return $errors;
    }

    public function getAssociationList($association)
    {
        $list = array();

        if (!isset($this->config['associations'][$association])) {
            return $list;
        }

        $params = $this->config['associations'][$association];
        $module = isset($params['module']) ? $params['module'] : $this->module;
        $instance = self::getInstance($module, $params['object_name']);

        $id_parent = null;
        if (isset($params['same_parent']) && $params['same_parent']) {
            if (isset($this->config['parent_id_property']) &&
                    isset($this->data[$this->config['parent_id_property']]) &&
                    $this->data[$this->config['parent_id_property']]) {
                $id_parent = $this->data[$this->config['parent_id_property']];
            }
        }

        if (!is_null($id_parent)) {
            return $instance->getListByParent($id_parent);
        }

        return $instance->getList();
    }

    public function getAssociatedObjectsIds($association)
    {
        if (is_null($this->id) || !$this->id) {
            return array();
        }

        if (!isset($this->config['associations'][$association])) {
            return array();
        }

        $params = $this->config['associations'][$association];
        $sql = 'SELECT `' . $params['associate_key'] . '` as id FROM ' . MAIN_DB_PREFIX . $params['table'];
        $sql .= ' WHERE `' . $params['self_key'] . '` = ' . (int) $this->id;

        $rows = $this->db->executeS($sql, 'array');

        if (!is_null($rows)) {
            $ids = array();
            foreach ($rows as $r) {
                if (!in_array($r['id'], $ids)) {
                    $ids[] = $r['id'];
                }
            }
            return $ids;
        }

        return array();
    }

    public function deleteByParent($id_parent)
    {
        if (is_null($id_parent) || !$id_parent) {
            return false;
        }

        if ($this->checkConfig('parent_id_property')) {
            return self::deleteBy($this->config['table'], array(
                        $this->config['parent_id_property'] => $id_parent
            ));
        }
    }

    public static function deleteBy($filters)
    {
        if (is_null($filters) || !count($filters)) {
            return false;
        }
        
        if ($this->checkConfig('table')) {
            return false;
        }

        $first_loop = true;
        $where = '';
        foreach ($filters as $key => $value) {
            if (!$first_loop) {
                $where .= ' AND ';
            } else {
                $first_loop = false;
            }

            $where .= '`' . $key . '` = ' . is_string($value) ? '\'' . $value . '\'' : $value;
        }

        $result = $this->db->delete($this->config['table'], $where);
        if ($result <= 0) {
            return false;
        }
        return true;
    }

    // Générations HTML: 

    public function renderForm($id_parent = null)
    {
        global $db;
        $form = new Form($db);
        $id_object = (!is_null($this->id) ? $this->id : 0);

        $html = '<form id="' . $this->getClass() . '_form" class="objectForm" method="post">';

        $html .= '<input type="hidden" name="object_name" value="' . $this->getClass() . '"/>';
        if ($id_object) {
            $html .= '<input type="hidden" name="id_object" value="' . $this->id . '" data-default_value="' . $this->id . '"/>';
        }

        $html .= '<table class="border" cellpadding="15" width="100%">';
        $html .= '<tbody>';

        foreach (static::$fields as $name => $params) {
            if ($name === static::$parent_id_property) {
                if (is_null($id_parent)) {
                    $id_parent = 0;
                }
                $html .= '<input type="hidden" name="' . $name . '" value="' . $id_parent . '"/>';
                continue;
            }
            $html .= '<tr';
            if ($params['input'] === 'hidden') {
                $html .= ' style="display: none"';
            }
            if (isset($params['display_if'])) {
                $html .= ' class="display_if" ';
                $html .= ' data-input_name="' . $params['display_if']['input_name'] . '"';
                if (isset($params['display_if']['show_values'])) {
                    $html .= ' data-show_values="' . $params['display_if']['show_values'] . '"';
                }
                if (isset($params['display_if']['hide_values'])) {
                    $html .= ' data-hide_values="' . $params['display_if']['hide_values'] . '"';
                }
            }
            $html .= '>';
            $html .= '<th class="ui-widget-header ui-state-default" width="15%">' . $params['label'] . '</th>';
            $html .= '<td class="ui-widget-content">';
            $value = '';
            if ($id_object) {
                $value = htmlentities($this->{$name});
            } elseif (isset($params['default_value'])) {
                $value = $params['default_value'];
            }

            switch ($params['input']) {
                case 'hidden':
                    $html .= '<input type="hidden" name="' . $name . '" value="' . $value . '"/>';
                    break;

                case 'datetime':
                    if (!$value) {
                        $value = date('Y-m-d H:i:s');
                    }
                    $display_now = (isset($params['display_now']) && $params['display_now']);
                    $DT = new DateTime($value);
                    $html .= $form->select_date($DT->getTimestamp(), $name, 1, 1, 0, "", 1, $display_now, 1);
                    unset($DT);
                    break;

                case 'text':
                    $size = (isset($params['input_size']) ? $params['input_size'] : 60);
                    $html .= '<input type="text" name="' . $name . '" value="' . $value . '" size="' . $size . '"/>';
                    break;

                case 'textarea':
                    $cols = (isset($params['cols']) ? $params['cols'] : 60);
                    $rows = (isset($params['rows']) ? $params['rows'] : 3);
                    $html .= '<textarea cols="' . $cols . '" rows="' . $rows . '" name="' . $name . '">' . $value . '</textarea>';
                    break;

                case 'switch':
                    $html .= '<select class="switch" name="' . $name . '">';
                    $html .= '<option value="1"' . ($value ? ' selected' : '') . '>OUI</option>';
                    $html .= '<option value="0"' . (!$value ? ' selected' : '') . '>NON</option>';
                    $html .= '</select>';
                    break;

                case 'select':
                    $options = array();
                    if (isset($params['options'])) {
                        if (is_array($params['options'])) {
                            $options = $params['options'];
                        } elseif (is_string($params['options'])) {
                            $method = 'get' . ucfirst($params['options']) . 'QueryArray';
                            if (method_exists($this, $method)) {
                                $options = $this->{$method}($id_parent);
                            }
                        }
                    }
                    $html .= '<select name="' . $name . '">';
                    foreach ($options as $option_name => $option_label) {
                        $html .= '<option value="' . $option_name . '"';
                        if ($value == $option_name) {
                            $html .= ' selected';
                        }
                        $html .= '>' . $option_label . '</option>';
                    }
                    $html .= '</select>';
                    break;

                case 'callback':
                    $method = 'get' . ucfirst($name) . 'Input';
                    if (method_exists($this, $method)) {
                        $html .= $this->{$method}($value);
                    }
                    break;
            }

            if (isset($params['help'])) {
                $html .= '<p class="inputHelp">' . $params['help'] . '</p>';
            }

            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        $html .= '<div class="ajaxResultContainer" id="' . $this->getClass() . '_formResultContainer">';
        $html .= '</div>';

        $html .= '<div class="formSubmit">';
        $html .= '<span class="butAction" onclick="saveObjectFromForm(\'' . $this->getClass() . '\')">';
        $html .= 'Enregistrer';
        $html .= '</span>';
        $html .= '</div>';
        $html .= '</form>';

        return $html;
    }

    public function renderCreateForm($id_parent = null)
    {

        $html .= '<table class="noborder" width="100%">';

        $html .= '<tr class="liste_titre">';
        $html .= '<td>';
        $html .= 'Ajout ' . static::getLabel('of_a');
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>';

        $html .= $this->renderForm($id_parent);

        $html .= '</td/>';
        $html .= '</tr/>';
        $html .= '</table/>';
        $html .= '</div>';

        return $html;
    }

    public function renderEditForm($id_parent = null)
    {
        $html = '<div>';
        $html .= '<table class="noborder" width="100%">';

        $html .= '<tr class="liste_titre">';
        $html .= '<td>';
        if (!is_null($this->id) && $this->id) {
            $html .= 'Edition ' . static::getLabel('of_the') . ' ' . $this->id;
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '<tr>';
            $html .= '<td>';
            $html .= $this->renderForm($id_parent);
        } else {
            $html .= '<p class="alert alert-danger">';
            $html .= static::getLabel('this') . 'n\'a pas été trouvé';
            $html .= '</p>';
        }


        $html .= '</td/>';
        $html .= '</tr/>';
        $html .= '</table/>';
        $html .= '</div>';

        if (!is_null($this->id) && $this->id) {
            if (count(static::$associations)) {
                foreach (static::$associations as $association => $params) {
                    $html .= $this->renderAssociationForm($association);
                }
            }
        }

        return $html;
    }

    public function renderAssociationForm($association)
    {
        if (!isset(static::$associations[$association])) {
            return '';
        }

        $params = static::$associations[$association];
        $class_name = $params['class_name'];
        $list = array();
        $method = 'get' . ucfirst($association) . 'AssociationList';

        if (method_exists($this, $method)) {
            $list = $this->{$method}();
        } else {
            $list = $this->getAssociationList($association);
        }

        $currents = $this->getAssociatedObjectsIds($association);
        $label = $class_name::getLabel('name_plur') . ' associé' . ($class_name::isLabelFemale() ? 'e' : '') . 's';

        $html .= '<div>';

        $html .= '<table class="noborder" width="100%">';

        $html .= '<tr class="liste_titre">';
        $html .= '<td>';
        $html .= ucfirst($label);
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td>';
        if (count($list)) {
            $html .= '<div id="' . $this->getClass() . '_' . $association . '_associations_list">';
            foreach ($list as $object) {
                $html .= '<div class="formRow">';
                $html .= '<input type="checkbox" value="' . $object['id'] . '" id="' . $association . '_' . $object['id'] . '" name="' . $association . '[]"';
                if (in_array($object['id'], $currents)) {
                    $html .= ' checked';
                }
                $html .= '/>';
                $html .= '<label for="' . $association . '_' . $object['id'] . '">';
                if (isset($object['label'])) {
                    $html .= $object['label'];
                } elseif (isset($object['title'])) {
                    $html .= $object['title'];
                } elseif (isset($object['name'])) {
                    $html .= $object['name'];
                } else {
                    $html .= ucfirst($class_name::getLabel('')) . ' n°' . $object['id'];
                }
                $html .= '</label>';
                $html .= '</div>';
            }
            $html .= '</div>';
        } else {
            $html .= '<div class="alert alert-warning">';
            $html .= 'Il n\'y a aucun' . ($class_name::isLabelFemale() ? 'e' : '') . ' ' . $class_name::getLabel() . ' à associer';
            $html .= '</div>';
        }

        $html .= '<div id="' . $this->getClass() . '_' . $association . '_associatonsAjaxResult"></div>';

        $html .= '<div class="formSubmit">';
        $html .= '<span class="butAction" onclick="saveObjectAssociations(' . $this->id . ', \'' . $this->getClass() . '\', \'' . $association . '\', $(this));">';
        $html .= 'Enregistrer les ' . $label;
        $html .= '</span>';
        $html .= '</div>';
        $html .= '</td/>';
        $html .= '</tr/>';

        $html .= '</table/>';

        $html .= '</div>';

        return $html;
    }

    public static function renderFormAndList()
    {
        $html = '';
        $className = static::getClass();
        $html .= '<div id="' . $className . '_card">';
        $html .= '<div class="objectToolbar">';
        $html .= '<span id="' . $className . '_openFormButton" class="butAction"';
        $html .= 'onclick="openObjectForm(\'' . $className . '\', null)">';
        $html .= 'Ajouter ' . static::getLabel('a') . '</span>';
        $html .= '<span id="' . $className . '_closeFormButton" class="butActionDelete"';
        $html .= 'onclick="closeObjectForm(\'' . $className . '\')"';
        $html .= ' style="display: none">';
        $html .= 'Annuler</span>';
        $html .= '</div>';

        $html .= '<div id="' . $className . '_formContainer" style="display: none"></div>';
        $html .= static::renderList(null);
        $html .= '</div>';

        return $html;
    }

    public function renderObjectFormAndList($object_name)
    {

        if (is_null($this->id) || !$this->id) {
            return '';
        }

        $html = '';
        if (isset(static::$objects[$object_name])) {
            $className = static::$objects[$object_name]['class_name'];
            if (!class_exists($className)) {
                require_once __DIR__ . '/' . $className . '.class.php';
            }

            if (class_exists($className)) {
                $html .= '<div id="' . $className . '_card">';
                $html .= '<div class="objectToolbar">';
                $html .= '<span id="' . $className . '_openFormButton" class="butAction"';
                $html .= 'onclick="openObjectForm(\'' . $className . '\', ' . $this->id . ')">';
                $html .= 'Ajouter ' . $className::getLabel('a') . '</span>';
                $html .= '<span id="' . $className . '_closeFormButton" class="butActionDelete"';
                $html .= 'onclick="closeObjectForm(\'' . $className . '\')"';
                $html .= ' style="display: none">';
                $html .= 'Annuler</span>';
                $html .= '</div>';

                $html .= '<div id="' . $className . '_formContainer" style="display: none"></div>';
                $html .= $className::renderList($this->id);
                $html .= '</div>';
            } else {
                $html .= '<p class="alert alert-danger">Classe "' . $className . '" absente</p>';
            }
        } else {
            $html .= '<p class="alert alert-danger">Paramètre des objets "' . $object_name . '" absents</p>';
        }

        return $html;
    }

    // Gestion du fichier de configuration: 

    public static function loadConfig($module, $object_name)
    {
        $file = DOL_DOCUMENT_ROOT . $module . '/objects/' . $object_name . '.yml';
        if (!file_exists($file)) {
            self::logConfigError($object_name, 'Fichier de configuration "' . $file . '" absent');
            return false;
        }

        return spyc_load_file($file);
    }

    public static function checkConfig($config, $properties)
    {
        if (is_null($config)) {
            return false;
        }

        if (!is_array($properties)) {
            $properties = array($properties);
        }

        $check = true;
        foreach ($properties as $property) {
            if (!isset($config[$property])) {
                self::logConfigUndefinedValue($this->object_name, $property);
                $check = false;
            }
        }
        return true;
    }

    public static function logConfigUndefinedValue($object_name, $property)
    {
        self::logConfigError($object_name, '"' . $property . '" non défini');
    }

    public static function logConfigError($object_name, $msg)
    {
        $message = 'Erreur de configuration pour l\'objet "' . $object_name . '" - ' . $msg;
        dol_syslog($message, LOG_ERR);
    }

    // Gestion des intitulés (labels) : 

    public function getLabels()
    {
        if (!$this->checkConfig('labels')) {
            return array();
        }

        $labels = $this->config['labels'];

        if (isset($labels['name'])) {
            $objectName = $labels['name'];
        } else {
            $objectName = 'objet';
        }

        $vowel_first = false;
        if (preg_match('/^[aàâäeéèêëiîïoôöuùûüyŷÿ](.*)$/', $objectName)) {
            $vowel_first = true;
        }

        if (!isset($labels['name_plur'])) {
            if (preg_match('/^.*[ao]u$/', $objectName)) {
                $labels['name_plur'] = $objectName . 'x';
            } elseif (preg_match('/^.*ou$/', $objectName)) {
                $labels['name_plur'] = $objectName . 'x';
            } elseif (!preg_match('/^.*s$/', $objectName)) {
                $labels['name_plur'] = $objectName . 's';
            } else {
                $labels['name_plur'] = $objectName;
            }
        }

        if (isset($labels['isFemale'])) {
            $isFemale = $labels['isFemale'];
        } else {
            $isFemale = false;
        }
        $labels['isFemale'] = $isFemale;

        if (!isset($labels['name'])) {
            $labels['name'] = 'object';
        }

        if (!isset($labels['the'])) {
            if ($vowel_first) {
                $labels['the'] = 'l\'';
            } elseif ($isFemale) {
                $labels['the'] = 'la';
            } else {
                $labels['the'] = 'le';
            }
        }

        if (!isset($labels['a'])) {
            if ($isFemale) {
                $labels['a'] = 'une';
            } else {
                $labels['a'] = 'un';
            }
        }

        if (!isset($labels['this'])) {
            if ($isFemale) {
                $labels['this'] = 'cette';
            } elseif ($vowel_first) {
                $labels['this'] = 'cet';
            } else {
                $labels['this'] = 'ce';
            }
        }

        if (!isset($labels['of_a'])) {
            if ($isFemale) {
                $labels['of_the'] = 'd\'une';
            } else {
                $labels['of_the'] = 'd\'un';
            }
        }

        if (!isset($labels['of_the'])) {
            if ($vowel_first) {
                $labels['of_the'] = 'de l\'';
            } elseif ($isFemale) {
                $labels['of_the'] = 'de la';
            } else {
                $labels['of_the'] = 'du';
            }
        }

        if (!isset($labels['of_this'])) {
            if ($isFemale) {
                $labels['of_this'] = 'de cette';
            } elseif ($vowel_first) {
                $labels['of_this'] = 'de cet';
            } else {
                $labels['of_this'] = 'de ce';
            }
        }

        if (!isset($labels['of_those'])) {
            $labels['of_those'] = 'de ces';
        }

        return $labels;
    }

    public function getLabel($type = '')
    {
        if (!$this->checkConfig('labels')) {
            return '';
        }

        $labels = $this->config['labels'];

        if (isset($labels['name'])) {
            $objectName = $labels['name'];
        } else {
            $objectName = 'objet';
        }

        $vowel_first = false;
        if (preg_match('/^[aàâäeéèêëiîïoôöuùûüyŷÿ](.*)$/', $objectName)) {
            $vowel_first = true;
        }

        $name_plur = '';

        if (!isset($labels['name_plur'])) {
            if (preg_match('/^.*[ao]u$/', $objectName)) {
                $name_plur = $objectName . 'x';
            } elseif (preg_match('/^.*ou$/', $objectName)) {
                $name_plur = $objectName . 'x';
            } elseif (!preg_match('/^.*s$/', $objectName)) {
                $name_plur = $objectName . 's';
            }
        } else {
            $name_plur = $labels['name_plur'];
        }

        if (isset($labels['isFemale'])) {
            $isFemale = $labels['isFemale'];
        } else {
            $isFemale = false;
        }

        switch ($type) {
            case '':
                return $objectName;

            case 'name_plur':
                return $name_plur;

            case 'the':
                if ($vowel_first) {
                    return 'l\'' . $objectName;
                } elseif ($isFemale) {
                    return 'la ' . $objectName;
                } else {
                    return 'le ' . $objectName;
                }

            case 'a':
                if ($isFemale) {
                    return 'une ' . $objectName;
                } else {
                    return 'un ' . $objectName;
                }

            case 'this':
                if ($isFemale) {
                    return 'cette ' . $objectName;
                } elseif ($vowel_first) {
                    return 'cet ' . $objectName;
                } else {
                    return 'ce ' . $objectName;
                }

            case 'of_a':
                if ($isFemale) {
                    return 'd\'une ' . $objectName;
                } else {
                    return 'd\'un ' . $objectName;
                }

            case 'of_the':
                if ($vowel_first) {
                    return 'de l\'' . $objectName;
                } elseif ($isFemale) {
                    return 'de la ' . $objectName;
                } else {
                    return 'du ' . $objectName;
                }

            case 'of_this':
                if ($isFemale) {
                    return 'de cette ' . $objectName;
                } elseif ($vowel_first) {
                    return 'de cet ' . $objectName;
                } else {
                    return 'de ce ' . $objectName;
                }

            case 'of_those':
                return 'de ces ' . $name_plur;
        }

        return $objectName;
    }

    public function isLabelFemale()
    {
        if ($this->checkConfig('labels')) {
            if (isset($this->config['labels']['isFemale'])) {
                return (int) $this->config['labels']['isFemale'];
            }
        }
        return false;
    }
}
