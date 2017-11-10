<?php

class BimpObject
{

    public $db = null;
    public $module = '';
    public $object_name = '';
    public $config = null;
    public $id = null;
    public static $common_fields = array(
        'id',
        'date_create',
        'date_update',
        'user_create',
        'user_update'
    );
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

    public static function getDolInstance($dol_object_params)
    {
        if (is_string($dol_object_params)) {
            $module = $file = $dol_object_params;
            $className = ucfirst($file);
        } else {
            $module = isset($dol_object_params['module']) ? $dol_object_params['module'] : null;
            if (is_null($module)) {
                return null;
            }
            $file = isset($dol_object_params['file']) ? $dol_object_params['file'] : $module;
            $className = isset($dol_object_params['class']) ? $dol_object_params['class'] : ucfirst($file);
        }

        if (!class_exists($className)) {
            $file_path = DOL_DOCUMENT_ROOT . $module . '/class/' . $file . '.class.php';
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }

        if (class_exists($className)) {
            global $db;
            return new $className($db);
        }

        return null;
    }

    public function getAssociateInstance($params)
    {
        if (isset($params['bimp_object'])) {
            if (is_string($params['bimp_object'])) {
                $module = $this->module;
                $name = $params['bimp_object'];
            } else {
                $module = isset($params['bimp_objet']['module']) ? $params['bimp_objet']['module'] : $this->module;
                $name = $params['bimp_objet']['name'];
            }
            return self::getInstance($module, $name);
        } elseif (isset($params['dol_object'])) {
            return self::getDolInstance($params['dol_object']);
        }

        return null;
    }

    public function __construct($module, $object_name)
    {
        global $db;

        $this->db = new BimpDb($db);
        $this->module = $module;
        $this->object_name = $object_name;
        $this->config = new BimpConfig(DOL_DOCUMENT_ROOT . $module . '/objects/', $object_name);
    }

    public function getConf($path, $default_value = null, $required = false, $data_type = 'string')
    {
        return $this->config->get($path, $this, $default_value, $required, $data_type);
    }

    public function getCurrentConf($path, $default_value = null, $required = false, $data_type = 'string')
    {
        return $this->config->getFromCurrentPath($path, $this, $default_value, $required, $data_type);
    }

    public function getPrimary()
    {
        return $this->getConf('primary', 'id');
    }

    public function getTable()
    {
        return $this->getConf('table', null, true);
    }

    public function getController()
    {
        return $this->getConf('controller', '');
    }

    public function getParentIdProperty()
    {
        $property = $this->getConf('parent_id_property', null);
        if (is_null($property)) {
            if ($this->config->isDefined('fields/id_parent')) {
                $property = 'id_parent';
            }
        }
        return $property;
    }

    public function getParentObjectName()
    {
        return $this->getConf('parent_object', '');
    }

    // Gestion des données:

    public function getData($field)
    {
        if (isset($this->data[$field])) {
            return $this->data[$field];
        }

        if ($this->config->isDefined('fields/' . $field)) {
            return $this->getConf('fields/' . $field . '/default_value');
        }
        return null;
    }

    public function set($field, $value)
    {
        return $this->validateValue($field, $value);
    }

    public function setIdParent($id_parent)
    {
        $parent_id_property = $this->getParentIdProperty();
        $this->set($parent_id_property, $id_parent);
    }

    public function reset()
    {
        $this->data = array();
        $this->id = null;
    }

    public function checkFieldValueType($field, &$value)
    {
        $type = '';
        if (in_array($field, self::$common_fields)) {
            switch ($field) {
                case 'user_create':
                case 'user_update':
                    $type = 'id_object';

                case 'date_create':
                case 'date_update':
                    $type = 'datetime';
            }
        } else {
            $type = $this->getConf('fields/' . $field . '/type', '', true);
        }

        if ($type) {
            return BimpTools::checkValueByType($type, $value);
        }

        return false;
    }

    public function getSearchFilters()
    {
        $filters = array();

        if (BimpTools::isSubmit('search_fields')) {
            $fields = BimpTools::getValue('search_fields', array());
            $prev_path = $this->config->current_path;
            foreach ($fields as $field_name => $value) {
                if ($value === '') {
                    continue;
                }
                if (in_array($field_name, self::$common_fields)) {
                    switch ($field_name) {
                        case 'id':
                            $filters[$field_name] = array(
                                'part_type' => 'beginning',
                                'part'      => $value
                            );
                            break;

                        case 'user_create':
                        case 'user_update':
                            $filters[$field_name] = $value;
                            break;

                        case 'date_create':
                        case 'date_update':
                            if (!isset($value['to']) || !$value['to']) {
                                $value['to'] = date('Y-m-d H:i:s');
                            }
                            if (!isset($value['from']) || !$value['from']) {
                                $value['from'] = '0000-00-00 00:00:00';
                            }
                            $filters[$field_name] = array(
                                'min' => $value['from'],
                                'max' => $value['to']
                            );
                            break;
                    }
                } else if ($this->config->setCurrentPath('fields/' . $field_name)) {
                    if ($this->config->isDefined('fields/' . $field_name . '/search')) {
                        $search_type = $this->getCurrentConf('search/type', 'field_input', false);

                        if ($value === '') {
                            $data_type = $this->getCurrentConf('type', '');
                            if (in_array($data_type, array('id_object', 'id'))) {
                                continue;
                            }
                        }

                        switch ($search_type) {
                            case 'date_range':
                                if (!is_array($value)) {
                                    $value = array(
                                        'from' => '',
                                        'to'   => ''
                                    );
                                }
                                if (!isset($value['to']) || !$value['to']) {
                                    $value['to'] = date('Y-m-d H:i:s');
                                }
                                if (!isset($value['from']) || !$value['from']) {
                                    $value['from'] = '0000-00-00 00:00:00';
                                }
                                if (isset($value['from']) && isset($value['to'])) {
                                    $filters[$field_name] = array(
                                        'min' => $value['from'],
                                        'max' => $value['to']
                                    );
                                }
                                break;

                            case 'values_range':
                                if (isset($value['min']) && isset($value['max'])) {
                                    $filters[$field_name] = $value;
                                }
                                break;

                            case 'value_part':
                                $part_type = $this->getCurrentConf('search/part_type', 'middle');
                                $filters[$field_name] = array(
                                    'part_type' => $part_type,
                                    'part'      => $value
                                );
                                break;

                            case 'field_input':
                            case 'values':
                            default:
                                $filters[$field_name] = $value;
                                break;
                        }
                    }
                }
            }
            $this->config->setCurrentPath($prev_path);
        }

//        echo '<pre>';
//        print_r($filters);
//        exit;
        return $filters;
    }

    // Affichage des données:

    public function displayData($field, $display_name = 'default')
    {
        if (is_null($display_name) || ($display_name === '')) {
            $display_name = 'default';
        }

        switch ($field) {
            case $this->getPrimary():
            case 'id':
            case 'id_object':
                if (isset($this->id) && $this->id) {
                    return $this->id;
                }
                return '<span class="warning">' . ucfirst($this->getLabel()) . ' non enregistré</span>';

            case 'date_create':
                if (isset($this->data['date_create']) && $this->data['date_create']) {
                    $date = new DateTime($this->data['date_create']);
                    return '<span class="datetime date_create">' . $date->format('d / m / Y') . ' <span class="time">à ' . $date->format('H:i:s') . '</span></span>';
                }
                return '';

            case 'date_update':
                if (isset($this->data['date_update']) && $this->data['date_update']) {
                    $date = new DateTime($this->data['date_update']);
                    return '<span class="datetime date_update">' . $date->format('d / m / Y') . ' <span class="time">à ' . $date->format('H:i:s') . '</span></span>';
                }
                return '';

            case 'user_create':
            case 'user_update':
                if (isset($this->data[$field]) && (int) $this->data[$field] > 0) {
                    global $db;
                    $user = new User($db);
                    $user->fetch($this->data[$field]);
                    return $user->getNomUrl(1);
                } else {
                    return '<span class="danger">Inconnu</span>';
                }
        }

        $html = '';
        if ($this->config->isDefined('fields/' . $field)) {
            if (!isset($this->data[$field])) {
                return '<span class="unknown">Inconnu</span>';
            }
            if (($display_name === 'default' && !$this->config->isDefined('fields/' . $field . '/display/default')) ||
                    ($display_name && !$this->config->isDefined('fields/' . $field . '/display/' . $display_name))) {
                if ($this->config->isDefined('fields/' . $field . '/display/type')) {
                    $display_name = '';
                }
            }
            $html .= $this->displayValue($this->data[$field], 'fields/' . $field . '/display' . ($display_name ? '/' . $display_name : ''), $field);
        } else {
            $html = '<p class="alert alert-danger">Champ "' . $field . '" non défini</p>';
        }

        return $html;
    }

    public function displayValue($value, $display_path, $field = '')
    {
        $prev_path = $this->config->current_path;
        $html = '';

        if ($this->config->setCurrentPath($display_path)) {
            $display_type = $this->getCurrentConf('type', 'string');
            switch ($display_type) {
                case 'nom':
                case 'nom_url':
                case 'card':
                    if (!$value) {
                        $html .= '<span class="warning">Aucun</span>';
                    } else {
                        if ($field) {
                            if ($this->config->isDefined('fields/' . $field . '/object')) {
                                $instance = $this->getConf('fields/' . $field . '/object', null, false, 'object');
                            } else {
                                $instance = $this;
                            }
                        } else {
                            $instance = $this->getCurrentConf('object', null, true, 'object');
                        }

                        if (!is_null($instance)) {
                            if ($display_type === 'nom') {
                                $html .= BimpObject::getInstanceNom($instance);
                            } elseif ($display_type === 'nom_url') {
                                $html .= BimpObject::getInstanceNomUrl($instance);
                            } elseif ($display_type === 'card') {
                                $card = $this->getCurrentConf('card', null, true, 'any');
                                if (!is_null($card)) {
                                    if (is_array($card)) {
                                        $this->config->setCurrentPath($display_path . '/card');
                                        $html .= BimpRender::renderObjectCard($instance, $this->config);
                                    } elseif (is_string($card)) {
                                        if (is_a($instance, 'BimpObject')) {
                                            if ($instance->config->isDefined('cards/' . $card)) {
                                                $instance->config->setCurrentPath('cards/' . $card);
                                                $html .= BimpRender::renderObjectCard($instance, $instance->config);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if (!$html) {
                            $html .= 'Objet: ' . $value;
                        }
                    }
                    break;

                case 'check':
                    if ((int) $value) {
                        $html .= '<span class="check_on"></span>';
                    } else {
                        $html .= '</span class="check_off"></span>';
                    }
                    break;

                case 'yes_no':
                    if ((int) $value) {
                        $html .= '<span class="success">OUI</span>';
                    } else {
                        $html .= '</span class="danger">NON</span>';
                    }
                    break;

                case 'array_value':
                    $array = $this->getConf($display_path . '/values', array(), true, 'array');

                    $check = false;
                    if (isset($array[$value])) {
                        if (is_array($array[$value])) {
                            if (isset($array[$value]['label'])) {
                                $html = '<span';
                                $html .= BimpRender::displayTagAttrs($array[$value]);
                                $html .= '>' . $array[$value]['label'] . '</span>';
                                $check = true;
                            }
                        } else {
                            $html .= $array[$value];
                            $check = true;
                        }
                    }

                    if (!$check) {
                        $html .= '<p class="alert alert-warning">valeur non trouvée pour l\'identifiant "' . $value . '"</p>';
                    }
                    break;

                case 'date':
                    $date = new DateTime($value);
                    $format = $this->getConf($display_path . '/format', 'd / m / Y', false);
                    $html .= '<span class="data">' . $date->format($format) . '</span>';
                    break;

                case 'time':
                    $time = new DateTime($value);
                    $format = $this->getConf($display_path . '/format', 'H:i:s', false);
                    $html .= '<span class="time">' . $time->format($format) . '</span>';
                    break;

                case 'datetime':
                    $date = new DateTime($value);
                    $format = $this->getConf($display_path . '/format', 'd / m / Y H:i:s', false);
                    $html .= '<span class="datetime">' . $date->format($format) . '</span>';
                    break;

                case 'timer':
                    $timer = BimpTools::getTimeDataFromSeconds((int) $value);
                    $html .= '<span class="timer">';
                    if ($timer['days'] > 0) {
                        $html .= $timer['days'] . ' j ';
                        $html .= $timer['hours'] . ' h ';
                        $html .= $timer['minutes'] . ' min ';
                    } elseif ($timer['hours'] > 0) {
                        $html .= $timer['hours'] . ' h ';
                        $html .= $timer['minutes'] . ' min ';
                    } elseif ($timer['minutes'] > 0) {
                        $html .= $timer['minutes'] . ' min ';
                    }
                    $html .= $timer['secondes'] . ' sec ';
                    $html .= '</span>';
                    break;

                case 'string':
                default:
                    $html .= $value;
                    break;
            }
        } else {
            $html = $value;
        }

        $this->config->setCurrentPath($prev_path);
        return $html;
    }

    public function getCommonFieldSearchInput($field)
    {
        $name = 'search_' . $field;
        $input_type = '';
        $search_type = '';
        $searchOnKeyUp = 0;
        $minChars = 1;
        $options = array();
        $html = '';

        switch ($field) {
            case 'id':
                $input_type = 'text';
                $search_type = 'value_part';
                $searchOnKeyUp = 1;
                break;

            case 'user_create':
            case 'user_update':
                $input_type = 'search_user';
                $search_type = 'field_input';
                break;

            case 'date_create':
            case 'date_update':
                $input_type = 'date_range';
                $search_type = 'date_range';
                break;
        }

        $html .= '<div class="searchInputContainer"';
        $html .= ' data-field_name="' . $name . '"';
        $html .= ' data-search_type="' . $search_type . '"';
        $html .= ' data-search_on_key_up="' . $searchOnKeyUp . '"';
        $html .= ' data-min_chars="' . $minChars . '"';
        $html .= '>';

        $input_id = $this->object_name . '_search_' . $field;

        $html .= BimpInput::renderInput($input_type, $name, '', $options, null, 'default', $input_id);

        $html .= '</div>';
        return $html;
    }

    // Validation des champs:

    public function validatePost()
    {

        $fields = $this->getConf('fields', null, true, 'array');

        if (is_null($fields)) {
            return array();
        }

        $errors = array();

        $prev_path = $this->config->current_path;

        foreach ($fields as $field => $params) {
            $this->config->setCurrentPath('fields/' . $field);
            $value = null;
            if (BimpTools::isSubmit($field)) {
                $type = $this->getCurrentConf('type', '');
                if ($type === 'datetime') {
                    $value = BimpTools::getDateTimeFromForm($field);
                } else {
                    $value = BimpTools::getValue($field);
                }
            } elseif (isset($this->data[$field])) {
                $value = $this->data[$field];
            } else {
                $value = $this->getCurrentConf('default_value', null);
            }

            $errors = array_merge($errors, $this->validateValue($field, $value));
        }

        $this->config->setCurrentPath($prev_path);
        return $errors;
    }

    public function validateArray(Array $values)
    {
        $fields = $this->getConf('fields', null, true, 'array');

        if (is_null($fields)) {
            return array();
        }

        $errors = array();
        $prev_path = $this->config->current_path;

        foreach ($fields as $field => $params) {
            $this->config->setCurrentPath('fields/' . $field);
            $value = null;
            if (isset($values[$field])) {
                $value = $values[$field];
            } elseif (isset($this->data[$field])) {
                $value = $this->data[$field];
            } else {
                $value = $this->getCurrentConf('default_value');
            }

            $errors = array_merge($errors, $this->validateValue($field, $value));
        }

        $this->config->setCurrentPath($prev_path);
        return $errors;
    }

    public function validateValue($field, $value)
    {
        $errors = array();

        $prevPath = $this->config->current_path;
        if (!$this->config->setCurrentPath('fields/' . $field)) {
            $errors[] = 'Le champ "' . $field . '" n\'existe pas';
        } else {
            $label = $this->getCurrentConf('label', $field);
            $required = (int) $this->getCurrentConf('required', 0, false, 'bool');
            if (!$required) {
                $required_if = $this->getCurrentConf('required_if');
                if (!is_null($required_if)) {
                    $required_if = explode('=', $required_if);
                    $property = $required_if[0];
                    if (isset($this->data[$property])) {
                        if ($this->data[$property] == $required_if[1]) {
                            $required = true;
                        }
                    }
                }
            }
            if (is_null($value) || ($value === '')) {
                $missing = false;
                if ($required) {
                    $missing = true;
                }
                if ($missing) {
                    $errors[] = 'Valeur obligatoire manquante : "' . $label . '"';
                }
            } else {
                $validate = true;
                $type = $this->getCurrentConf('type', '');
                $invalid_msg = $this->getCurrentConf('invalid_msg');

                if ($type) {
                    if (is_null($invalid_msg)) {
                        switch ($type) {
                            case 'datetime':
                                $invalid_msg = 'Format attendu: AAAA-MM-JJ HH:MM:SS';
                                break;

                            case 'time':
                                $invalid_msg = 'Format attendu: HH:MM:SS';
                                break;

                            case 'date':
                                $invalid_msg = 'Format attendu: AAAA-MM-JJ';

                            case 'id_object':
                                $invalid_msg = 'La valeur doit être un nombre entier positif';

                            default:
                                $invalid_msg = 'La valeur doit être de type "' . $type . '"';
                        }
                    }

                    if (!$this->checkFieldValueType($field, $value)) {
                        $validate = false;
                    } else {
                        switch ($type) {
                            case 'id':
                            case 'id_object':
                                if ($required && ((int) $value <= 0)) {
                                    $errors[] = 'Valeur obligatoire manquante : "' . $label . '"';
                                    $validate = false;
                                }
                                break;
                        }
                    }
                }

                if (!count($errors) && !is_null($regexp = $this->getCurrentConf('regexp'))) {
                    if (!preg_match('/' . $regexp . '/', $value)) {
                        $validate = false;
                    }
                }
                if (!count($errors) && !is_null($is_key_array = $this->getCurrentConf('is_key_array'))) {
                    if (is_array($is_key_array) && !array_key_exists($value, $is_key_array)) {
                        $validate = false;
                    }
                }
                if (!count($errors) && !is_null($in_array = $this->getCurrentConf('in_array'))) {
                    if (is_array($in_array) && !in_array($value, $in_array)) {
                        $validate = false;
                    }
                }

                if (!$validate) {
                    $msg = '"' . $label . '": valeur invalide';
                    if (!is_null($invalid_msg)) {
                        $msg .= ' (' . $invalid_msg . ')';
                    }
                    $errors[] = $msg;
                }

                if (!count($errors)) {
                    $this->data[$field] = $value;
                }
            }
        }

        $this->config->setCurrentPath($prevPath);
        return $errors;
    }

    public function validate()
    {
        $fields = $this->getConf('fields', null, true, 'array');
        if (is_null($fields)) {
            return array();
        }

        $errors = array();
        foreach ($fields as $field => $params) {
            $errors = array_merge($errors, $this->validateValue($field, isset($this->data[$field]) ? $this->data[$field] : null));
        }
        return $errors;
    }

    // Gestion SQL:

    public function find($filters)
    {
        $this->reset();

        $where = '';
        $first = true;
        foreach ($filters as $field_name => $value) {
            if ($first) {
                $where .= 'WHERE ';
                $first = false;
            } else {
                $where .= ' AND ';
            }
            $where .= '`' . $field_name . '` = ' . (is_string($value) ? '\'' . $value . '\'' : $value);
        }

        $id_object = $this->db->getValue($this->getTable(), $this->getPrimary(), $where);

        if (is_null($id_object) || !$id_object) {
            return false;
        }

        $this->fetch($id_object);
        return true;
    }

    public function fetch($id)
    {
        $table = $this->getTable();
        $primary = $this->getPrimary();

        if (is_null($table) || !$table) {
            return false;
        }

        $row = $this->db->getRow($table, '`' . $primary . '` = ' . (int) $id);

        if (!is_null($row)) {
            foreach ($row as $field => $value) {
                if ($field === $primary) {
                    $this->id = $value;
                } elseif (in_array($field, self::$common_fields) ||
                        $this->config->isDefined('fields/' . $field)) {
                    $this->checkFieldValueType($field, $value);
                    $this->data[$field] = $value;
                }
            }
            return true;
        }
        return false;
    }

    public function fetchBy($field, $value)
    {
        $table = $this->getTable();
        $primary = $this->getPrimary();

        if (is_null($table) || !$table) {
            return false;
        }

        $prev_path = $this->config->current_path;

        if (!$this->config->setCurrentPath('fields/' . $field)) {
            BimpTools::logTechnicalError($this, 'fetchBy', 'Le champ "' . $field . '" n\'existe pas');
            return false;
        }

        $type = $this->getCurrentConf('type', '');

        if (!in_array($type, array('id', 'id_object'))) {
            BimpTools::logTechnicalError($this, 'fetchBy', 'Le champ "' . $field . '" doit être un identifiant unique');
        } else {
            $where = '`' . $field . '` = ' . is_string($value) ? '\'' . $value . '\'' : $value;

            $row = $this->db->getRow($table, $where);

            if (!is_null($row)) {
                foreach ($row as $property => $value) {
                    if ($property === $primary) {
                        $this->id = $value;
                    } elseif (in_array($field, self::$common_fields) ||
                            $this->config->isDefined('fields/' . $property)) {
                        $this->data[$property] = $value;
                    }
                }
                $this->config->setCurrentPath($prev_path);
                return true;
            }
        }
        $this->config->setCurrentPath($prev_path);
        return false;
    }

    public function update()
    {
        $table = $this->getTable();
        $primary = $this->getPrimary();

        if (is_null($table)) {
            return array('Fichier de configuration invalide');
        }

        $errors = array();
        if (is_null($this->id) || !$this->id) {
            return array('ID Absent');
        }
        $errors = $this->validate();

        if (!count($errors)) {
            $this->data['date_update'] = date('Y-m-d H:i:s');
            global $user;
            if (isset($user->id)) {
                $this->data['user_update'] = (int) $user->id;
            } else {
                $this->data['user_update'] = 0;
            }

            foreach ($this->data as $field => &$value) {
                $this->checkFieldValueType($field, $value);
            }

            $result = $this->db->update($table, $this->data, '`' . $primary . '` = ' . (int) $this->id);
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
        $table = $this->getTable();

        if (is_null($table)) {
            return array('Fichier de configuration invalide');
        }

        $errors = $this->validate();

        if (!count($errors)) {
            $this->data['date_create'] = date('Y-m-d H:i:s');
            global $user;
            if (isset($user->id)) {
                $this->data['user_create'] = (int) $user->id;
            } else {
                $this->data['user_create'] = 0;
            }

            foreach ($this->data as $field => &$value) {
                $this->checkFieldValueType($field, $value);
            }

            $result = $this->db->insert($table, $this->data, true);
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
        $table = $this->getTable();
        $primary = $this->getPrimary();

        if (is_null($table)) {
            return array('Fichier de configuration invalide');
        }
        if (is_null($this->id) || !$this->id) {
            return array('ID absent');
        }

        $errors = array();
        $result = $this->db->delete($table, '`' . $primary . '` = ' . (int) $this->id);
        if ($result <= 0) {
            $msg = 'Echec de la suppression ' . $this->getLabel('of_the');
            $sqlError = $this->db->db->error();
            if ($sqlError) {
                $msg .= ' - Erreur SQL: ' . $sqlError;
            }
            $errors[] = $msg;
        } else {
            $objects = $this->getConf('objects', array(), true, 'array');
            if (!is_null($objects)) {
                $prev_path = $this->config->current_path;
                foreach ($objects as $name => $params) {
                    $this->config->setCurrentPath('objects/' . $name);
                    if ((int) $delete = $this->getCurrentConf('delete', 0, false, 'bool')) {
                        $instance = $this->getCurrentConf('object', null, true, 'object');
                        if (!is_null($instance)) {
                            if (!$instance->deleteByParent($this->id)) {
                                $msg = 'Des erreurs sont survenues lors de la tentative de suppresion des ';
                                $msg .= $this->getInstanceLabel($instance, 'name_plur');
                                $errors[] = $msg;
                            }
                        }
                    }
                }
                $this->config->setCurrentPath($prev_path);
            }

            $associations = $this->getConf('associations', null, false, 'array');
            if (!is_null($associations)) {
                $prev_path = $this->config->current_path;
                foreach ($associations as $name => $params) {
                    $this->config->setCurrentPath('associations/' . $name);

                    $self_key = $this->getCurrentConf('self_key', null, true);
                    $asso_table = $this->getCurrentConf('table', null, true);

                    if (is_null($self_key) || is_null($asso_table)) {
                        $errors[] = 'Configuration invalide pour l\'association de type "' . $name . '"';
                    } else {
                        $where = '`' . $self_key . '` = ' . (int) $this->id;
                        $result = $this->db->delete($asso_table, $where);
                        if ($result <= 0) {
                            $msg = 'Echec de la suppression des associations de type "' . $name . '".';
                            $errors[] = $msg;
                        }
                    }
                }
                $this->config->setCurrentPath($prev_path);
            }
        }

        return $errors;
    }

    public function getDataArray($include_id = false)
    {
        $fields = $this->getConf('fields', null, true, 'array');

        if (is_null($fields)) {
            return array();
        }

        $data = array();
        $primary = $this->getPrimary();
        $prev_path = $this->config->current_path;
        foreach ($fields as $field => $params) {
            $this->config->setCurrentPath('fields/' . $field);
            if (isset($this->data[$field])) {
                $data[$field] = $this->data[$field];
            } else {
                $data[$field] = $this->getCurrentConf('default_value');
            }
        }
        if ($include_id) {
            $id = !is_null($this->id) ? $this->id : 0;
            $data['id'] = $id;
            if ($primary !== 'id') {
                $data[$primary] = $id;
            }
        }

        $this->config->setCurrentPath($prev_path);
        return $data;
    }

    public function getList($filters = array(), $n = null, $p = null, $order_by = 'id', $order_way = 'DESC', $return = 'array', $return_fields = null, $joins = null)
    {
        if ($order_by === 'id') {
            $order_by = $this->getPrimary();
        }

        $table = $this->getTable();

        if (is_null($table)) {
            return array();
        }

        $sql = '';
        $sql .= BimpTools::getSqlSelect($return_fields);
        $sql .= BimpTools::getSqlFrom($table, $joins);
        $sql .= BimpTools::getSqlWhere($filters);
        $sql .= BimpTools::getSqlOrderBy($order_by, $order_way);
        $sql .= BimpTools::getSqlLimit($n, $p);

        $rows = $this->db->executeS($sql, $return);

        if (is_null($rows)) {
            $rows = array();
        }

        return $rows;
    }

    public function getListCount($filters = array(), $joins = null)
    {
        $table = $this->getTable();
        if (is_null($table)) {
            return 0;
        }
        $primary = $this->getPrimary();

        $sql = 'SELECT COUNT(DISTINCT a.' . $primary . ') as nb_rows';
        $sql .= BimpTools::getSqlFrom($table, $joins);
        $sql .= BimpTools::getSqlWhere($filters);

        $result = $this->db->execute($sql);
        if ($result > 0) {
            $obj = $this->db->db->fetch_object($result);
            return (int) $obj->nb_rows;
        }
        return 0;
    }

    public function getListByParent($id_parent, $n = null, $p = null, $order_by = 'id', $order_way = 'DESC', $return = 'array', $return_fields = null, $joins = null)
    {
        if ($order_by === 'id') {
            $order_by = $this->getPrimary();
        }

        $table = $this->getTable();
        $parent_id_property = $this->getParentIdProperty();

        if (is_null($table) ||  is_null($parent_id_property)) {
            return array();
        }

        return $this->getList(array(
                    $parent_id_property => $id_parent
                        ), $n, $p, $order_by, $order_way, $return, $return_fields, $joins);
    }

    public function saveAssociations($association, $list)
    {
        $errors = array();
        $prev_path = $this->config->current_path;

        if (!$this->config->setCurrentPath('associations/' . $association)) {
            $errors[] = 'le type d\'association "' . $association . '" n\'est pas valide';
        } elseif (is_null($this->id) || !$this->id) {
            $errors[] = 'ID Absent';
        } else {
            $self_key = $this->getCurrentConf('self_key', null, true);
            $associate_key = $this->getCurrentConf('associate_key', null, true);
            $table = $this->getCurrentConf('table', null, true);

            if (is_null($table) || is_null($self_key) || is_null($associate_key)) {
                $errors[] = 'Configuration invalide pour l\'association "' . $association . '"';
            } else {
                $result = $this->db->delete($table, '`' . $self_key . '` = ' . (int) $this->id);
                if ($result <= 0) {
                    $msg = 'Echec de la suppression des associations existantes';
                    $sqlError = $this->db->db->error();
                    if ($sqlError) {
                        $msg .= ' - Erreur SQL: ' . $sqlError;
                    }
                    $errors[] = $msg;
                } else {
                    foreach ($list as $id) {
                        $data = array();
                        $data[$self_key] = (int) $this->id;
                        $data[$associate_key] = (int) $id;
                        if (!$this->db->insert($table, $data)) {
                            $instance = $this->getCurrentConf('associate_object', null, true, 'object');
                            if (!is_null($instance)) {
                                $label = $this->getInstanceLabel($instance, 'the');
                            } else {
                                $label = 'l\'objet associé';
                            }
                            unset($instance);
                            $msg = 'Echec de l\'enregistrement de l\'association avec ' . $this->getInstanceLabel($instance, 'the') . ' d\'ID ' . $id;
                            $sqlError = $this->db->db->error();
                            if ($sqlError) {
                                $msg .= ' - Erreur SQL: ' . $sqlError;
                            }
                            $errors[] = $msg;
                        }
                    }
                }
            }
        }

        $this->config->setCurrentPath($prev_path);
        return $errors;
    }

    public function getAssociationList($association)
    {
        $list = array();

        $prev_path = $this->config->current_path;

        if (!$this->config->setCurrentPath('associations/' . $association)) {
            return $list;
        }

        $instance = $this->getCurrentConf('associate_object', null, true, 'object');

        if (!is_null($instance)) {
            $id_parent = null;
            if ((int) $same_parent = $this->getCurrentConf('same_parent', 0, false, 'bool')) {
                $parent_id_property = $this->getParentIdProperty();
                if (!is_null($parent_id_property) && isset($this->data[$parent_id_property]) && $this->data[$parent_id_property]) {
                    $id_parent = $this->data[$parent_id_property];
                }
            }

            if (is_a($instance, 'BimpObject')) {
                if (!is_null($id_parent)) {
                    $list = $instance->getListByParent($id_parent);
                } else {
                    $list = $instance->getList();
                }
            } else {
                $filters = array();
                if (!is_null($id_parent)) {
                    $associate_parent_id_property = $this->getCurrentConf('associate_object/parent_id_property');
                    if (!is_null($associate_parent_id_property)) {
                        $filters[$associate_parent_id_property] = $id_parent;
                    }
                }

                $list = BimpTools::getDolObjectList($instance, $filters);
            }
        }

        $this->config->setCurrentPath($prev_path);
        return $list;
    }

    public function getAssociatedObjectsIds($association)
    {
        if (is_null($this->id) || !$this->id) {
            return array();
        }

        $prev_path = $this->config->current_path;
        if (!$this->config->setCurrentPath('associations/' . $association)) {
            return array();
        }

        $ids = array();

        $associate_key = $this->getCurrentConf('associate_key', null, true);
        $self_key = $this->getCurrentConf('self_key', null, true);
        $table = $this->getCurrentConf('table', null, true);

        if (!is_null($associate_key) && !is_null($self_key) && !is_null($table)) {
            $sql = 'SELECT `' . $associate_key . '` as id FROM ' . MAIN_DB_PREFIX . $table;
            $sql .= ' WHERE `' . $self_key . '` = ' . (int) $this->id;

            $rows = $this->db->executeS($sql, 'array');

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    if (!in_array($r['id'], $ids)) {
                        $ids[] = $r['id'];
                    }
                }
            }
        }

        $this->config->setCurrentPath($prev_path);
        return $ids;
    }

    public function deleteByParent($id_parent)
    {
        if (is_null($id_parent) || !$id_parent) {
            return false;
        }

        $parent_id_property = $this->getParentIdProperty();

        if (!is_null($parent_id_property) && $parent_id_property) {
            return self::deleteBy(array(
                        $parent_id_property => (int) $id_parent
            ));
        }
    }

    public function deleteBy($filters)
    {
        if (is_null($filters) || !count($filters)) {
            return false;
        }

        $table = $this->getTable();
        if (is_null($table)) {
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

            $where .= '`' . $key . '` = ' . (is_string($value) ? '\'' . $value . '\'' : $value);
        }

        $result = $this->db->delete($table, $where);
        if ($result <= 0) {
            return false;
        }
        return true;
    }

    // Gestion des intitulés (labels):

    public function getLabels()
    {
        $labels = $this->getConf('labels', array(), false, 'array');

        if (isset($labels['name'])) {
            $object_name = $labels['name'];
        } else {
            $object_name = 'objet';
        }

        $vowel_first = false;
        if (preg_match('/^[aàâäeéèêëiîïoôöuùûüyŷÿ](.*)$/', $object_name)) {
            $vowel_first = true;
        }

        if (!isset($labels['name_plur'])) {
            if (preg_match('/^.*[ao]u$/', $object_name)) {
                $labels['name_plur'] = $object_name . 'x';
            } elseif (preg_match('/^.*ou$/', $object_name)) {
                $labels['name_plur'] = $object_name . 'x';
            } elseif (!preg_match('/^.*s$/', $object_name)) {
                $labels['name_plur'] = $object_name . 's';
            } else {
                $labels['name_plur'] = $object_name;
            }
        }

        if (isset($labels['is_female'])) {
            $isFemale = $labels['is_female'];
        } else {
            $isFemale = false;
        }
        $labels['is_female'] = $isFemale;

        if (!isset($labels['name'])) {
            $labels['name'] = 'object';
        }

        if (!isset($labels['the'])) {
            if ($vowel_first) {
                $labels['the'] = 'l\'' . $labels['name'];
            } elseif ($isFemale) {
                $labels['the'] = 'la ' . $labels['name'];
            } else {
                $labels['the'] = 'le ' . $labels['name'];
            }
        }

        if (!isset($labels['a'])) {
            if ($isFemale) {
                $labels['a'] = 'une ' . $labels['name'];
            } else {
                $labels['a'] = 'un ' . $labels['name'];
            }
        }

        if (!isset($labels['this'])) {
            if ($isFemale) {
                $labels['this'] = 'cette ' . $labels['name'];
            } elseif ($vowel_first) {
                $labels['this'] = 'cet ' . $labels['name'];
            } else {
                $labels['this'] = 'ce ' . $labels['name'];
            }
        }

        if (!isset($labels['of_a'])) {
            if ($isFemale) {
                $labels['of_a'] = 'd\'une ' . $labels['name'];
            } else {
                $labels['of_a'] = 'd\'un ' . $labels['name'];
            }
        }

        if (!isset($labels['of_the'])) {
            if ($vowel_first) {
                $labels['of_the'] = 'de l\'' . $labels['name'];
            } elseif ($isFemale) {
                $labels['of_the'] = 'de la ' . $labels['name'];
            } else {
                $labels['of_the'] = 'du ' . $labels['name'];
            }
        }

        if (!isset($labels['of_this'])) {
            if ($isFemale) {
                $labels['of_this'] = 'de cette ' . $labels['name'];
            } elseif ($vowel_first) {
                $labels['of_this'] = 'de cet ' . $labels['name'];
            } else {
                $labels['of_this'] = 'de ce ' . $labels['name'];
            }
        }

        if (!isset($labels['of_those'])) {
            $labels['of_those'] = 'de ces ' . $labels['name_plur'];
        }

        return $labels;
    }

    public function getLabel($type = '')
    {
        $labels = $this->getConf('labels', array(), false, 'array');

        if (isset($labels['name'])) {
            $object_name = $labels['name'];
        } else {
            $object_name = 'objet';
        }

        $vowel_first = false;
        if (preg_match('/^[aàâäeéèêëiîïoôöuùûüyŷÿ](.*)$/', $object_name)) {
            $vowel_first = true;
        }

        $name_plur = '';

        if (!isset($labels['name_plur'])) {
            if (preg_match('/^.*[ao]u$/', $object_name)) {
                $name_plur = $object_name . 'x';
            } elseif (preg_match('/^.*ou$/', $object_name)) {
                $name_plur = $object_name . 'x';
            } elseif (!preg_match('/^.*s$/', $object_name)) {
                $name_plur = $object_name . 's';
            }
        } else {
            $name_plur = $labels['name_plur'];
        }

        if (isset($labels['is_female'])) {
            $isFemale = $labels['is_female'];
        } else {
            $isFemale = false;
        }

        switch ($type) {
            case '':
                return $object_name;

            case 'name_plur':
                return $name_plur;

            case 'the':
                if ($vowel_first) {
                    return 'l\'' . $object_name;
                } elseif ($isFemale) {
                    return 'la ' . $object_name;
                } else {
                    return 'le ' . $object_name;
                }

            case 'a':
                if ($isFemale) {
                    return 'une ' . $object_name;
                } else {
                    return 'un ' . $object_name;
                }

            case 'this':
                if ($isFemale) {
                    return 'cette ' . $object_name;
                } elseif ($vowel_first) {
                    return 'cet ' . $object_name;
                } else {
                    return 'ce ' . $object_name;
                }

            case 'of_a':
                if ($isFemale) {
                    return 'd\'une ' . $object_name;
                } else {
                    return 'd\'un ' . $object_name;
                }

            case 'of_the':
                if ($vowel_first) {
                    return 'de l\'' . $object_name;
                } elseif ($isFemale) {
                    return 'de la ' . $object_name;
                } else {
                    return 'du ' . $object_name;
                }

            case 'of_this':
                if ($isFemale) {
                    return 'de cette ' . $object_name;
                } elseif ($vowel_first) {
                    return 'de cet ' . $object_name;
                } else {
                    return 'de ce ' . $object_name;
                }

            case 'of_those':
                return 'de ces ' . $name_plur;
        }

        return $object_name;
    }

    public function isLabelFemale()
    {
        return $this->getConf('labels/is_female', 0, false, 'bool');
    }

    public function getInstanceName()
    {
        if ($this->config->isDefined('fields/title') &&
                isset($this->data['title']) && $this->data['title']) {
            return $this->data['title'];
        } elseif ($this->config->isDefined('fields/public_name') &&
                isset($this->data['public_name']) && $this->data['public_name']) {
            return $this->data['public_name'];
        } elseif ($this->config->isDefined('fields/label') &&
                isset($this->data['label']) && $this->data['label']) {
            return $this->data['label'];
        } elseif ($this->config->isDefined('fields/name') &&
                isset($this->data['name']) && $this->data['name']) {
            return $this->data['name'];
        } elseif (isset($this->id) && $this->id) {
            return $this->getLabel() . ' n° ' . $this->id;
        }

        return ucfirst($this->getLabel());
    }

    public static function getInstanceLabel($instance, $type = '')
    {
        if (!is_null($instance) && is_a($instance, 'BimpObject')) {
            return $instance->getLabel($type);
        }

        switch ($type) {
            case '':
            case 'name':
                $label = 'objet';
                break;

            case 'name_plur':
                $label = 'objets';
                break;

            case 'the':
                $label = 'l\'objet';
                break;

            case 'a':
                $label = 'un objet';
                break;

            case 'this':
                $label = 'cet objet';
                break;

            case 'of_a':
                $label = 'd\'un objet';
                break;

            case 'of_the':
                $label = 'de l\'objet';
                break;

            case 'of_this';
                $label = 'de cet objet';
                break;

            case 'of_those':
                $label = 'de ces objets';
                break;
        }

        if (!is_null($instance)) {
            $label .= ' "' . get_class($instance) . '"';
        }

        return $label;
    }

    public static function getInstanceNom($instance)
    {
        return '<span class="objectName">Nom d\'une instance</span>';
    }

    public static function getInstanceNomUrl($instance)
    {
        if (is_a($instance, 'BimpObject')) {
            return '<a href="" target="_blank">' . $instance->getInstanceName() . '</a>';
        } elseif (method_exists($instance, 'getNomUrl')) {
            return $instance->getNomUrl(1);
        }

        return 'Objet "' . get_class($instance) . '"' . isset($instance->id) ? ' n° ' . $instance->id : '';
    }

    public static function getInstanceUrl($instance)
    {
        if (is_a($instance, 'BimpObject')) {
            if ($controller = $instance->getController()) {
                return DOL_URL_ROOT . '/' . $instance->module . '/index.php?fc=' . $controller . (isset($instance->id) && $instance->id ? '&id=' . $instance->id : '');
            }
            return '';
        }
        return BimpTools::getDolObjectUrl($instance);
    }
}
