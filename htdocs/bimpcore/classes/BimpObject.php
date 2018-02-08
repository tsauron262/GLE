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
        'user_update',
        'position'
    );
    public static $numeric_types = array('id', 'id_parent', 'id_object', 'int', 'float', 'money', 'percent');
    public $use_commom_fields = true;
    protected $data = array();
    protected $associations = array();
    protected $history = array();

    public static function getInstance($module, $object_name)
    {
        $file = DOL_DOCUMENT_ROOT . '/' . $module . '/objects/' . $object_name . '.class.php';
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
            $file_path = DOL_DOCUMENT_ROOT . '/' . $module . '/class/' . $file . '.class.php';
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

    public function __construct($module, $object_name)
    {
        global $db;

        $this->db = new BimpDb($db);
        $this->module = $module;
        $this->object_name = $object_name;
        $this->config = new BimpConfig(DOL_DOCUMENT_ROOT . '/' . $module . '/objects/', $object_name, $this);
        $this->use_commom_fields = $this->getConf('common_fields', 1, false, 'bool');
    }

    public function getConf($path, $default_value = null, $required = false, $data_type = 'string')
    {
        return $this->config->get($path, $default_value, $required, $data_type);
    }

    public function getCurrentConf($path, $default_value = null, $required = false, $data_type = 'string')
    {
        return $this->config->getFromCurrentPath($path, $default_value, $required, $data_type);
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

    public function getParentModule()
    {
        return $this->getConf('parent_module', $this->module);
    }

    public function getParentInstance()
    {
        $id_property = $this->getParentIdProperty();
        $module = $this->getParentModule();
        $object = $this->getParentObjectName();
        if ($module && $object) {
            $instance = self::getInstance($module, $object);
            if (!is_null($instance) && $instance && !is_null($id_property)) {
                $id = $this->getData($id_property);
                if (!is_null($id) && $id) {
                    $instance->fetch($id);
                }
            }
            return $instance;
        }
        return null;
    }

    public function hasChildren($object_name)
    {
        if (!$this->config->isDefined('objects/' . $object_name)) {
            return false;
        }

        $relation = $this->getConf('objects/' . $object_name . '/relation', '', true);
        if (!$relation) {
            return false;
        }

        $instance = $this->config->getObject('', $object_name);
        if (is_null($instance)) {
            return false;
        }

        switch ($relation) {
            case 'hasOne':
                if (isset($instance->id) && $instance->id) {
                    return true;
                }
                return false;

            case 'hasMany':
                if (is_a($instance, 'BimpObject')) {
                    if ($instance->getParentObjectName() === $this->object_name) {
                        $filters = $this->getConf('objects/' . $object_name . '/list/filters', array(), false, 'array');
                        $filters[$instance->getParentIdProperty()] = $this->id;
                        return ($instance->getListCount($filters) > 0);
                    }
                }
        }
        return false;
    }

    public function getChildObject($object_name, $id_object = null)
    {
        $child = $this->config->getObject('', $object_name);
        if (!is_null($child) && !is_null($id_object)) {
            $child->fetch($id_object);
        }
        return $child;
    }

    public function getChildrenObjects($object_name)
    {
        $children = array();
        if (isset($this->id) && $this->id) {
            if ($this->config->isDefined('objects/' . $object_name)) {
                $relation = $this->getConf('objects/' . $object_name . '/relation', '', true);
                if ($relation !== 'hasMany') {
                    return array();
                }

                $instance = $this->config->getObject('', $object_name);
                if (!is_null($instance)) {
                    if (is_a($instance, 'BimpObject')) {
                        if ($instance->getParentObjectName() === $this->object_name) {
                            $filters = $this->getConf('objects/' . $object_name . '/list/filters', array(), false, 'array');
                            $filters[$instance->getParentIdProperty()] = $this->id;
                            $primary = $instance->getPrimary();
                            $list = $instance->getList($filters, null, null, 'id', 'asc', 'array', array($primary));
                            foreach ($list as $item) {
                                $child = BimpObject::getInstance($instance->module, $instance->object_name);
                                if ($child->fetch($item[$primary])) {
                                    $children[] = $child;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $children;
    }

    public function getChildrenList($object_name)
    {
        $children = array();
        if (isset($this->id) && $this->id) {
            if ($this->config->isDefined('objects/' . $object_name)) {
                $relation = $this->getConf('objects/' . $object_name . '/relation', '', true);
                if ($relation !== 'hasMany') {
                    return array();
                }

                $instance = $this->config->getObject('', $object_name);
                if (!is_null($instance)) {
                    if (is_a($instance, 'BimpObject')) {
                        if ($instance->getParentObjectName() === $this->object_name) {
                            $filters = $this->getConf('objects/' . $object_name . '/list/filters', array(), false, 'array');
                            $filters[$instance->getParentIdProperty()] = $this->id;
                            $primary = $instance->getPrimary();
                            $list = $instance->getList($filters, null, null, 'id', 'asc', 'array', array($primary));
                            foreach ($list as $item) {
                                $children[] = (int) $item[$primary];
                            }
                        }
                    }
                }
            }
        }
        return $children;
    }

    // Gestion des données:

    public function isLoaded()
    {
        return (isset($this->id) && $this->id);
    }

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

    public function getSavedData($field, $id_object = null)
    {
        if (is_null($id_object)) {
            if ($this->isLoaded()) {
                $id_object = $this->id;
            } else {
                return null;
            }
        }
        return $this->db->getValue($this->getTable(), $field, '`id` = ' . (int) $id_object);
    }

    public function set($field, $value)
    {
        $this->validateValue($field, $value);
    }

    public function addMultipleValuesItem($name, $value)
    {
        $errors = array();
        if ($this->config->isDefined('fields/' . $name)) {
            $items = $this->getData($name);
            if (!is_array($items)) {
                $items = explode(',', $items);
            }
            $items[] = $value;
            $this->set($name, implode(',', $items));
            $errors = $this->update();
            if (!count($errors)) {
                return 'Valeur "' . $value . '" correctement enregistrée';
            }
            return $errors;
        } elseif ($this->config->isDefined('associations/' . $name)) {
            $bimpAsso = new BimpAssociation($this, $name);
            $errors = $bimpAsso->addObjectAssociation($value);
            if (!count($errors)) {
                $success = 'Association avec ' . BimpObject::getInstanceLabel($bimpAsso->associate, 'the') . ' ' . $value . ' correctement enregistrée';
                unset($bimpAsso);
                return $success;
            }

            unset($bimpAsso);
            return $errors;
        }
        $errors[] = 'Le champ "' . $name . '" n\'existe pas';
        return $errors;
    }

    public function deleteMultipleValuesItem($name, $value)
    {
        $errors = array();
        if ($this->config->isDefined('fields/' . $name)) {
            $items = $this->getData($name);
            if (!is_array($items)) {
                $items = explode(',', $items);
            }

            if (in_array($items, $value)) {
                foreach ($items as $idx => $item) {
                    if ($item === $value) {
                        unset($items[$idx]);
                    }
                }
                $this->set($name, implode(',', $items));
                $errors = $this->update();
                if (!count($errors)) {
                    return 'Suppression de la valeur "' . $value . '" correctement effectuée';
                }
                return $errors;
            } else {
                $errors[] = 'La valeur "' . $value . '" n\'est pas enregistrée';
            }
        } elseif ($this->config->isDefined('associations/' . $name)) {
            $bimpAsso = new BimpAssociation($this, $name);
            $errors = $bimpAsso->deleteAssociation($this->id, (int) $value);
            if (!count($errors)) {
                $success = 'suppression de l\'association ' . $this->getLabel('of_the') . ' ' . $this->id;
                $success .= ' avec ' . BimpObject::getInstanceLabel($bimpAsso->associate, 'the') . ' ' . $value . ' correctement effectuée';
                unset($bimpAsso);
                return $success;
            }

            unset($bimpAsso);
            return $errors;
        }
        $errors[] = 'Le champ "' . $name . '" n\'existe pas';
        return $errors;
    }

    public function setIdParent($id_parent)
    {
        $parent_id_property = $this->getParentIdProperty();
        $this->set($parent_id_property, $id_parent);
    }

    public function reset()
    {
        $this->data = array();
        $this->associations = array();
        $this->id = null;
        $this->config->resetObjects();
    }

    public function getAssociatesList($association)
    {
        if (isset($this->associations[$association])) {
            return $this->associations[$association];
        }

        $this->associations[$association] = array();

        if (!isset($this->id) || !$this->id) {
            return array();
        }

        if ($this->config->isDefined('associations/' . $association)) {
            $associations = new BimpAssociation($this, $association);
            $this->associations[$association] = $associations->getAssociatesList();
            unset($associations);
        }

        return $this->associations[$association];
    }

    public function setAssociatesList($association, $list)
    {
        if (isset($this->associations[$association])) {
            $this->associations[$association] = $list;
            return true;
        }

        if ($this->config->isDefined('associations/' . $association)) {
            $this->associations[$association] = $list;
            return true;
        }
        return false;
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

    public function getSearchFilters($fields = null)
    {
        $filters = array();

        if (is_null($fields) && BimpTools::isSubmit('search_fields')) {
            $fields = BimpTools::getValue('search_fields', null);
        }
        if (!is_null($fields)) {
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
                            case 'time_range':
                            case 'date_range':
                            case 'datetime_range':
                                if (is_array($value) &&
                                        isset($value['to']) && $value['to'] &&
                                        isset($value['from']) && $value['from']) {
                                    if ($value['from'] <= $value['to']) {
                                        $filters[$field_name] = array(
                                            'min' => $value['from'],
                                            'max' => $value['to']
                                        );
                                    }
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

        return $filters;
    }

    protected function checkFieldHistory($field, $value)
    {
        $history = $this->getConf('fields/' . $field . '/history', false, false, 'bool');
        if ($history) {
            $current_value = $this->getData($field);
            if (!isset($this->id) || !$this->id || is_null($current_value) || ($current_value != $value)) {
                $this->history[$field] = $value;
            }
        }
    }

    protected function saveHistory()
    {
        if (!count($this->history) || !isset($this->id) || !$this->id) {
            return array();
        }

        $errors = array();
        $bimpHistory = BimpObject::getInstance('bimpcore', 'BimpHistory');

        foreach ($this->history as $field => $value) {
            $errors = array_merge($errors, $bimpHistory->add($this, $field, $value));
        }

        $this->history = array();
        return $errors;
    }

    // Affichage des données:

    public function displayData($field, $display_name = 'default', $item = null)
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

            case 'position':
                if (isset($this->data['position']) && $this->data['position']) {
                    return '<span class="object_position">' . $this->data['position'] . '</span>';
                }
                return '';
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
            $value = $this->getData($field);
            if (is_array($value)) {
                if (!is_null($item) && in_array($item, $value)) {
                    $value = $item;
                } else {
                    $value = implode(',', $value);
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
        $html = '';

        $display_type = $this->getConf($display_path . '/type', '');

        if ($field) {
            $data_type = $this->getConf('fields/' . $field . '/type', 'string');
            if (!$display_type) {
                if ($this->config->isDefined('fields/' . $field . '/values')) {
                    $display_type = 'array_value';
                } else {
                    switch ($data_type) {
                        case 'time':
                        case 'date':
                        case 'datetime':
                        case 'money':
                        case 'percent':
                            $display_type = $data_type;
                            break;

                        case 'bool':
                            $display_type = 'yes_no';
                            break;

                        default:
                            $display_type = 'string';
                            break;
                    }
                }
            }
        }

        switch ($display_type) {
            case 'syntaxe':
                $syntaxe = $this->getConf($display_path . '/syntaxe', '<value>');
                $syntaxe = str_replace('<value>', $value, $syntaxe);
                $html .= $syntaxe;
                break;

            case 'nom':
            case 'nom_url':
            case 'card':
                if (!$value) {
                    $html .= '<span class="warning">Aucun</span>';
                } else {
                    if ($field) {
                        if ($field === $this->getParentIdProperty()) {
                            $instance = $this->getParentInstance();
                        } elseif ($this->config->isDefined('fields/' . $field . '/object')) {
                            $instance = $this->config->getObject('fields/' . $field . '/object');
                        } else {
                            $instance = $this;
                        }
                    } else {
                        $instance = $this->config->getObject($display_path . '/object');
                    }

                    if (!is_null($instance)) {
                        if ($display_type === 'nom') {
                            $html .= BimpObject::getInstanceNom($instance);
                        } elseif ($display_type === 'nom_url') {
                            $html .= BimpObject::getInstanceNomUrl($instance);
                        } elseif ($display_type === 'card') {
                            $card = $this->getConf($display_path . '/card', null, true, 'any');
                            if (!is_null($card)) {
                                $card_path = $display_path . '/card';
                                if (is_string($card)) {
                                    if (is_a($instance, 'BimpObject')) {
                                        if ($instance->config->isDefined('cards/' . $card)) {
                                            $instance_prev_path = $instance->config->current_path;
                                            $instance->config->setCurrentPath('cards/' . $card);
                                            $bimpCard = new BimpCard($instance, $instance->config, 'cards/' . $card);
                                            $html .= $bimpCard->render();
                                            $instance->config->setCurrentPath($instance_prev_path);
                                            return $html;
                                        }
                                    }

                                    if ($field && $this->config->isDefined('fields/' . $field . '/object')) {
                                        $object_name = $this->config->get('fields/' . $field . '/object', '', false, 'any');
                                        if ($object_name && is_string($object_name)) {
                                            if ($this->config->isDefined('objects/' . $object_name . '/cards/' . $card)) {
                                                $card_path = 'objects/' . $object_name . '/cards/' . $card;
                                            }
                                        }
                                    }
                                }
                                $bimpCard = new BimpCard($instance, $this->config, $card_path);
                                return $bimpCard->render();
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
                if ($this->config->isDefined($display_path . '/values')) {
                    $array = $this->getConf($display_path . '/values', array(), true, 'array');
                } elseif ($field) {
                    $array = $this->getConf('fields/' . $field . '/values', array(), true, 'array');
                } else {
                    $array = array();
                }
                $check = false;
                if (isset($array[$value])) {
                    if (is_array($array[$value])) {
                        $icon_only = (bool) $this->getConf($display_path . '/icon_only', false, false, 'bool');
                        if ($icon_only && isset($array[$value]['icon'])) {
                            if (!isset($array[$value]['classes'])) {
                                $array[$value]['classes'] = array();
                            }
                            $array[$value]['classes'] = array_merge($array[$value]['classes'], array('fa', 'fa-' . $array[$value]['icon'], 'iconLeft', 'bs-popover'));
                            $html .= '<div style="text-align: center">';
                            $html .= '<i ' . BimpRender::displayTagAttrs($array[$value]);
                            $html .= ' data-toggle="popover"';
                            $html .= ' data-trigger="hover"';
                            $html .= ' data-content="' . $array[$value]['label'] . '"';
                            $html .= ' data-container="body"';
                            $html .= ' data-placement="top"></i>';
                            $html .= '</div>';
                            $check = true;
                        } else {
                            $html .= '<span';
                            $html .= BimpRender::displayTagAttrs($array[$value]);
                            $html .= '>';
                            if (isset($array[$value]['icon'])) {
                                $html .= '<i class="fa fa-' . $array[$value]['icon'] . ' iconLeft"></i>';
                            }
                            if (isset($array[$value]['label'])) {
                                $html .= $array[$value]['label'];
                            }
                            $html .= '</span>';
                            $check = true;
                        }
                    } else {
                        return $array[$value];
                    }
                }

                if (!$check) {
                    $html .= '<p class="alert alert-warning">valeur non trouvée pour l\'identifiant "' . $value . '"</p>';
                }
                break;

            case 'time':
                $time = new DateTime($value);
                $format = $this->getConf($display_path . '/format', 'H:i:s', false);
                $html .= '<span class="time">' . $time->format($format) . '</span>';
                break;

            case 'date':
                $date = new DateTime($value);
                $format = $this->getConf($display_path . '/format', 'd / m / Y', false);
                $html .= '<span class="data">' . $date->format($format) . '</span>';
                break;

            case 'datetime':
                $date = new DateTime($value);
                $format = $this->getConf($display_path . '/format', 'd / m / Y H:i:s', false);
                $html .= '<span class="datetime">' . $date->format($format) . '</span>';
                break;

            case 'timer':
                $html = BimpTools::displayTimefromSeconds($value);
                break;

            case 'money':
                if ($field) {
                    $currency = $this->getConf('fields/' . $field . '/currency', 'EUR');
                } else {
                    $currency = 'EUR';
                }
                return BimpTools::displayMoneyValue($value, $currency);

            case 'percent':
                return $value . ' %';

            case 'callback':
                $method = $this->getConf($display_path . '/method', '', true);
                if (method_exists($this, $method)) {
                    $html = $this->{$method}($value);
                }
                break;

            case 'string':
            default:
                $html .= $value;
                break;
        }

        if (!$html) {
            $html = $value;
        }

        return $html;
    }

    public function displayAssociate($association, $display_name, $id_associate)
    {
        $html = '';
        if ($this->config->isDefined('associations/' . $association)) {
            if (!is_null($id_associate)) {
                if (($display_name === 'default' && !$this->config->isDefined('associations/' . $association . '/display/default')) ||
                        ($display_name && !$this->config->isDefined('associations/' . $association . '/display/' . $display_name))) {
                    if ($this->config->isDefined('associations/' . $association . '/display/type')) {
                        $display_name = '';
                    }
                }

                $instance = $this->config->getObject('associations/' . $association . '/object');
                if (!is_null($instance)) {
                    if ($instance->fetch($id_associate)) {
                        $prev_path = $this->config->current_path;
                        $this->config->setCurrentPath('associations/' . $association . '/display/' . $display_name);

                        $type = $this->getCurrentConf('type', 'object_prop');

                        switch ($type) {
                            case 'callback':
                                if ($display_name) {
                                    $method = $display_name . 'Display' . ucfirst($association) . 'Item';
                                } else {
                                    $method = 'defaultDisplay' . ucfirst($association) . 'Item';
                                }
                                $method = $display_name . 'Display' . ucfirst($association) . 'Item';
                                if (method_exists($this, $method)) {
                                    $html .= $this->{$method}($id_associate);
                                } else {
                                    $html .= BimpRender::renderAlerts('Erreur de configuration - méthodes "' . $method . '" inexistante');
                                }
                                break;

                            case 'syntaxe':
                                $syntaxe = $this->getCurrentConf('syntaxe', '', true);
                                $values = $this->getCurrentConf('values', array(), true, 'array');
                                foreach ($values as $name => $object_prop) {
                                    if (property_exists($instance, $object_prop)) {
                                        $syntaxe = str_replace('<' . $name . '>', $instance->{$object_prop}, $syntaxe);
                                    } else {
                                        $syntaxe = str_replace('<' . $name . '>', '', $syntaxe);
                                    }
                                }
                                break;

                            case 'object_prop':
                                $object_prop = $this->getCurrentConf('object_prop', '', true);
                                if ($object_prop && property_exists($instance, $object_prop)) {
                                    $html .= $instance->{$object_prop};
                                } else {
                                    $html .= BimpRender::renderAlerts('Erreur de configuration - Propriété "' . $object_prop . '" inexistante pour l\'objet "' . get_class($instance) . '"');
                                }
                                break;

                            default:
                                $html .= BimpTools::ucfirst(self::getInstanceLabel('name')) . ' ' . $id_associate;
                                break;
                        }

                        $this->config->setCurrentPath($prev_path);
                    } else {
                        $html .= BimpRender::renderAlerts(self::getInstanceLabel('name') . ' d\'ID ' . $id_associate . ' non trouvé(e)');
                    }
                } else {
                    $html .= BimpRender::renderAlerts('Erreur de configuration : Instance invalide');
                }
            } else {
                $html .= BimpRender::renderAlerts('inconnu');
            }
        }

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
                $input_type = 'datetime_range';
                $search_type = 'datetime_range';
                break;

            case 'position':
                $input_type = 'values_range';
                $search_type = 'values_range';
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
        $errors = array();
        $prev_path = $this->config->current_path;

        $fields = $this->getConf('fields', array(), true, 'array');

        foreach ($fields as $field => $params) {
            $this->config->setCurrentPath('fields/' . $field);
            $value = null;
            if (BimpTools::isSubmit($field)) {
                $value = BimpTools::getValue($field, null);
            } elseif (isset($this->data[$field])) {
                $value = $this->getData($field);
            } else {
                $value = $this->getCurrentConf('default_value', null);
            }

            $errors = array_merge($errors, $this->validateValue($field, $value));
        }

        $associations = $this->getConf('associations', array(), false, 'array');

        foreach ($associations as $asso_name => $params) {
            if (BimpTools::isSubmit($asso_name)) {
                $this->setAssociatesList($asso_name, BimpTools::getValue($asso_name));
            }
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
            $type = $this->getCurrentConf('type', '');

            if (is_null($value) || $value === '') {
                $missing = false;
                if ($required) {
                    $missing = true;
                }
                if ($missing) {
                    $errors[] = 'Valeur obligatoire manquante : "' . $label . '"';
                    return $errors;
                }
            }

            if (is_null($value)) {
                $value = '';
            }

            if (($value === '') && in_array($type, self::$numeric_types)) {
                $value = 0;
            }

            $validate = true;
            $invalid_msg = $this->getCurrentConf('invalid_msg');

            if ($type) {
                if (is_null($invalid_msg)) {
                    switch ($type) {
                        case 'time':
                            $invalid_msg = 'Format attendu: HH:MM:SS';
                            break;

                        case 'date':
                            $invalid_msg = 'Format attendu: AAAA-MM-JJ';
                            break;

                        case 'datetime':
                            $invalid_msg = 'Format attendu: AAAA-MM-JJ HH:MM:SS';
                            break;

                        case 'id_object':
                            $invalid_msg = 'La valeur doit être un nombre entier positif';
                            break;

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
                $this->checkFieldHistory($field, $value);
                $this->data[$field] = $value;
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

    public function create()
    {
        $table = $this->getTable();

        if (is_null($table)) {
            return array('Fichier de configuration invalide (table non renseignées)');
        }

        $errors = $this->validate();

        if (!count($errors)) {
            if ($this->use_commom_fields) {
                $this->data['date_create'] = date('Y-m-d H:i:s');
                global $user;
                if (isset($user->id)) {
                    $this->data['user_create'] = (int) $user->id;
                } else {
                    $this->data['user_create'] = 0;
                }
            }

            foreach ($this->data as $field => &$value) {
                $this->checkFieldValueType($field, $value);
            }

            $result = $this->db->insert($table, $this->data, true);
            if ($result > 0) {
                $this->id = $result;

                if ($this->getConf('positions', false, false, 'bool')) {
                    $insert_mode = $this->getConf('position_insert', 'before');
                    switch ($insert_mode) {
                        case 'before':
                            $this->setPosition(1);
                            break;

                        case 'after':
                            $this->setPosition((int) $this->getNextPosition());
                            break;
                    }
                }

                $errors = array_merge($errors, $this->updateAssociations());
                $errors = array_merge($errors, $this->saveHistory());

                $parent = $this->getParentInstance();
                if (!is_null($parent)) {
                    if (method_exists($parent, 'onChildSave')) {
                        $parent->onChildSave($this);
                    }
                }
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

    public function update()
    {
        $table = $this->getTable();
        $primary = $this->getPrimary();

        if (is_null($table)) {
            return array('Fichier de configuration invalide (table non renseignée)');
        }

        $errors = array();
        if (is_null($this->id) || !$this->id) {
            return array('ID Absent');
        }
        $errors = $this->validate();

        if (!count($errors)) {
            if ($this->use_commom_fields) {
                $this->data['date_update'] = date('Y-m-d H:i:s');
                global $user;
                if (isset($user->id)) {
                    $this->data['user_update'] = (int) $user->id;
                } else {
                    $this->data['user_update'] = 0;
                }
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

        $errors = array_merge($errors, $this->updateAssociations());
        $errors = array_merge($errors, $this->saveHistory());

        $parent = $this->getParentInstance();
        if (!is_null($parent)) {
            if (method_exists($parent, 'onChildSave')) {
                $parent->onChildSave($this);
            }
        }
        return $errors;
    }

    public function updateAssociations()
    {
        $errors = array();
        if (!isset($this->id) || !$this->id) {
            $errors[] = 'Mise à jour des associations impossible - ID absent';
            return $errors;
        }
        $prev_path = $this->config->current_path;

        $associations = $this->getConf('associations', array(), false, 'array');
        foreach ($associations as $association => $params) {
            if (isset($this->associations[$association])) {
                $bimpAsso = new BimpAssociation($this, $association);
                if (count($bimpAsso->errors)) {
                    $errors = array_merge($errors, $bimpAsso->errors);
                } else {
                    $errors = array_merge($errors, $bimpAsso->setObjectAssociations($this->associations[$association]));
                }
            }
        }

        $this->config->setCurrentPath($prev_path);
        return $errors;
    }

    public function find($filters)
    {
        $this->reset();

        $where = '';
        $first = true;
        foreach ($filters as $field_name => $value) {
            if ($first) {
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

        return $this->fetch($id_object);
    }

    public function fetch($id)
    {
        $this->reset();
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
        $this->reset();
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

            $id_object = $this->db->getValue($table, $primary, $where);

            if (!is_null($id_object)) {
                $this->config->setCurrentPath($prev_path);
                return $this->fetch($id_object);
            }
        }
        $this->config->setCurrentPath($prev_path);
        return false;
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

//        echo $sql; exit;
        $rows = $this->db->executeS($sql, $return);

        if (is_null($rows)) {
            $rows = array();
        }

        return $rows;
    }

    public function getListByParent($id_parent, $n = null, $p = null, $order_by = 'id', $order_way = 'DESC', $return = 'array', $return_fields = null, $joins = null)
    {
        if ($order_by === 'id') {
            $order_by = $this->getPrimary();
        }

        $table = $this->getTable();
        $parent_id_property = $this->getParentIdProperty();

        if (is_null($table) || is_null($parent_id_property)) {
            return array();
        }

        return $this->getList(array(
                    $parent_id_property => $id_parent
                        ), $n, $p, $order_by, $order_way, $return, $return_fields, $joins);
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

    public function delete()
    {
        $table = $this->getTable();
        $primary = $this->getPrimary();

        if (is_null($table)) {
            return array('Fichier de configuration invalide (table non renseignée)');
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
            $id = $this->id;
            $this->reset();
            if ($this->getConf('positions')) {
                $this->resetPositions();
            }
            $objects = $this->getConf('objects', array(), true, 'array');
            if (!is_null($objects)) {
                $prev_path = $this->config->current_path;
                foreach ($objects as $name => $params) {
                    $this->config->setCurrentPath('objects/' . $name);
                    if ((int) $delete = $this->getCurrentConf('delete', 0, false, 'bool')) {
                        $instance = $this->config->getObject('', $name);
                        if (!is_null($instance)) {
                            if (!$instance->deleteByParent($id)) {
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
                    $bimpAsso = new BimpAssociation($this, $name);
                    $errors = array_merge($errors, $bimpAsso->deleteAllObjectAssociations($id));
                }
                $this->config->setCurrentPath($prev_path);
            }

            $bimpHistory = BimpObject::getInstance('bimpcore', 'BimpHistory');
            $bimpHistory->deleteByObject($this, $id);

            $parent = $this->getParentInstance();
            if (!is_null($parent)) {
                if (method_exists($parent, 'onChildDelete')) {
                    $parent->onChildDelete($this);
                }
            }
        }

        $this->reset();
        return $errors;
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
        $primary = $this->getPrimary();
        if (is_null($primary)) {
            return false;
        }

        $sql = BimpTools::getSqlSelect(array($primary));
        $sql .= BimpTools::getSqlFrom($table);
        $sql .= BimpTools::getSqlWhere($filters);

        $items = $this->db->executeS($sql);

        $check = true;
        if (!is_null($items)) {
            foreach ($items as $item) {
                $this->reset();
                if ($this->fetch($item->id)) {
                    $del_errors = $this->delete();
                    if (count($del_errors)) {
                        $check = false;
                    }
                }
            }
        }
        return $check;
    }

    // Gestion des positions: 

    public function resetPositions()
    {
        if ($this->getConf('positions', false, false, 'bool')) {
            $filters = array();
            $parent_id_property = $this->getParentIdProperty();
            if (!is_null($parent_id_property)) {
                $id_parent = $this->getData($parent_id_property);
                if (is_null($id_parent) || !$id_parent) {
                    return;
                }
                $filters[$parent_id_property] = $id_parent;
            }

            $table = $this->getTable();
            $primary = $this->getPrimary();

            $items = $this->getList($filters, null, null, 'position', 'asc', 'array', array($primary, 'position'));
            $i = 1;
            foreach ($items as $item) {
                if ((int) $item['position'] !== (int) $i) {
                    $this->db->update($table, array(
                        'position' => (int) $i
                            ), '`' . $primary . '` = ' . (int) $item[$primary]);
                }
                $i++;
            }
        }
    }

    public function setPosition($position)
    {
        if (!isset($this->id) || !$this->id) {
            return false;
        }

        if ($this->getConf('positions', false, false, 'bool')) {
            $filters = array();
            $parent_id_property = $this->getParentIdProperty();
            if (!is_null($parent_id_property)) {
                $id_parent = $this->getData($parent_id_property);
                if (is_null($id_parent) || !$id_parent) {
                    return;
                }
                $filters[$parent_id_property] = $id_parent;
            }

            $table = $this->getTable();
            $primary = $this->getPrimary();

            $items = $this->getList($filters, null, null, 'position', 'asc', 'array', array($primary, 'position'));

            $check = true;

            if ($this->db->update($table, array(
                        'position' => (int) $position
                            ), '`' . $primary . '` = ' . (int) $this->id) <= 0) {
                $check = false;
            }

            if ($check) {
                $i = 1;
                foreach ($items as $item) {
                    if ($i === (int) $position) {
                        $i++;
                    }

                    if ((int) $item[$primary] === (int) $this->id) {
                        continue;
                    }

                    if ((int) $item['position'] !== (int) $i) {
                        if ($this->db->update($table, array(
                                    'position' => (int) $i
                                        ), '`' . $primary . '` = ' . (int) $item[$primary]) <= 0) {
                            $check = false;
                        }
                    }
                    $i++;
                }
            }
            return $check;
        }

        return false;
    }

    public function getNextPosition()
    {
        if ($this->getConf('positions', false, false, 'bool')) {
            $filters = array();

            $parent_id_property = $this->getParentIdProperty();
            if (!is_null($parent_id_property)) {
                $id_parent = $this->getData($parent_id_property);
                if (is_null($id_parent) || !$id_parent) {
                    return 0;
                }
                $filters[$parent_id_property] = $id_parent;
            }

            $sql = 'SELECT MAX(`position`) as max_pos';
            $sql .= BimpTools::getSqlFrom($this->getTable());
            $sql .= BimpTools::getSqlWhere($filters);

            $result = $this->db->executeS($sql, 'array');
            if (!is_null($result)) {
                return (int) ((int) $result[0]['max_pos'] + 1);
            }
        }
        return 1;
    }

    // Rendus HTML

    public function renderView($view_name = 'default', $panel = false)
    {
        if (!isset($this->id) || !$this->id) {
            $msg = ucfirst($this->getLabel('this')) . ' n\'existe plus';
            return BimpRender::renderAlerts($msg);
        }
        $view = new BimpView($this, $view_name);
        return $view->render($panel);
    }

    public function renderList($list_name = 'default', $panel = false, $title = null, $icon = null, $filters = array())
    {
        $list = new BimpList($this, $list_name, null, $title, $icon);
        foreach ($filters as $field_name => $value) {
            $list->addFieldFilterValue($field_name, $value);
        }
        return $list->render($panel);
    }

    public function renderForm($form_name = 'default', $panel = false)
    {
        $form = new BimpForm($this, $form_name);
        if ($panel) {
            return $form->renderPanel();
        }
        return $form->render();
    }

    public function renderViewsList($views_list_name = 'default', $panel = false)
    {
        $viewsList = new BimpViewsList($this, $views_list_name);
        return $viewsList->render($panel);
    }

    public function renderCommonFields()
    {
        $html = '';

        if (!$this->use_commom_fields) {
            return $html;
        }

        $html .= '<div class="object_common_fields">';
        $html .= '<table>';
        $html .= '<thead></thead>';
        $html .= '<tbody>';

        $html .= '<tr>';
        $html .= '<th>ID</th>';
        $html .= '<td>' . $this->id . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Créé le</th>';
        $html .= '<td>' . $this->displayData('date_create') . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Par</th>';
        $html .= '<td>' . $this->displayData('user_create') . '</td>';
        $html .= '</tr>';

        $user_update = $this->getData('user_update');
        if (!is_null($user_update) && $user_update) {
            $html .= '<tr>';
            $html .= '<th>Dernière mise à jour le</th>';
            $html .= '<td>' . $this->displayData('date_update') . '</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<th>Par</th>';
            $html .= '<td>' . $this->displayData('user_update') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    public function renderChildrenList($children_object, $list_name = 'default', $panel = false, $title = null, $icon = null)
    {
        $children_instance = $this->config->getObject('', $children_object);
        if (!isset($this->id) || !$this->id) {
            $msg = 'Impossible d\'afficher la liste des ' . $children_instance->getLabel('name_plur');
            $msg .= ' - ID ' . $this->getLabel('of_the') . ' absent';
            return BimpRender::renderAlerts($msg);
        }
        if (!is_null($children_instance) && is_a($children_instance, 'BimpObject')) {
            $title = (is_null($title) ? $this->getConf('objects/' . $children_object . '/list/title') : $title);
            $icon = (is_null($icon) ? $this->getConf('objects/' . $children_object . '/list/icon', $icon) : $icon);

            $list = new BimpList($children_instance, $list_name, $this->id, $title, $icon);

            $list_filters = $this->config->getCompiledParams('objects/' . $children_object . '/list/filters', array(), false, 'array');

            if (count($list_filters)) {
                foreach ($list_filters as $field_name => $value) {
                    $list->addFieldFilterValue($field_name, $value);
                }
            }

            $add_form_name = $this->getConf('objects/' . $children_object . '/add_form/name', null);

            if (!is_null($add_form_name)) {
                $list->setAddFormName($add_form_name);
            }

            $add_form_values = $this->config->getCompiledParams('objects/' . $children_object . '/add_form/values', null, false, 'array');
            if (!is_null($add_form_values) && count($add_form_values)) {
                $list->setAddFormValues($add_form_values);
            }

            return $list->render($panel);
        }
        $msg = 'Erreur technique: objets "' . $children_object . '" non trouvés pour ' . $this->getLabel('this');
        return BimpRender::renderAlerts($msg);
    }

    public function renderChildCard($object_name, $card_name = '', $card_path = '')
    {
        $object = $this->getChildObject($object_name);

        if (is_null($object)) {
            return '';
        }

        $config = $this->config;

        if (!$card_path) {
            if ($card_name) {
                if ($this->config->isDefined('objects/' . $object_name . '/cards/' . $card_name)) {
                    $card_path = 'objects/' . $object_name . '/cards/' . $card_name;
                } elseif (($card_name === 'default') && $this->config->isDefined('objects/' . $object_name . '/card')) {
                    $card_path = 'objects/' . $object_name . '/card';
                } elseif (is_a($object, 'BimpObject')) {
                    if ($object->config->is_defined('cards/' . $card_name)) {
                        $config = $object->config;
                        $card_path = 'cards/' . $card_name;
                    } elseif (($card_name === 'default') && $object->config->isDefined('card')) {
                        $config = $object->config;
                        $card_path = $object->config;
                    }
                }
            }
        }

        if ($card_path) {
            $card = new BimpCard($object, $config, $card_path);
            $html = $card->render();
            unset($card);
            return $html;
        }

        return '';
    }

    public function renderAssociatesList($association, $list_name = 'default', $panel = false, $title = null, $icon = null)
    {
        $bimpAsso = new BimpAssociation($this, $association);
        if (count($bimpAsso->errors)) {
            return BimpRender::renderAlerts($bimpAsso->errors);
        }
        if (!isset($this->id) || !$this->id) {
            $msg = 'Impossible d\'afficher la liste des ' . self::getInstanceLabel($bimpAsso->associate, 'name_plur');
            $msg .= ' - ID ' . $this->getLabel('of_the') . ' absent';
            return BimpRender::renderAlerts($msg);
        }

        $bimpList = new BimpList($bimpAsso->associate, $list_name, null, $title, $icon);
        $bimpList->addObjectAssociationFilter($this, $this->id, $association);

        if ($this->config->isDefined('associations/' . $association . '/add_form')) {
            $name = $this->getConf('associations/' . $association . '/add_form/name', '');
            $values = $this->getConf('associations/' . $association . '/add_form/values', null, false, 'array');

            if ($name) {
                $bimpList->setAddFormName($name);
            }
            if (!is_null($values)) {
                $bimpList->setAddFormValues($values);
            }
        } else {
            $bimpList->addForm = null;
        }

        return $bimpList->render($panel);
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

        if (!isset($labels['to'])) {
            if ($vowel_first) {
                $labels['to'] = 'à l\'' . $labels['name'];
            } elseif ($isFemale) {
                $labels['to'] = 'à la ' . $labels['name'];
            } else {
                $labels['this'] = 'au ' . $labels['name'];
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

        if (!isset($labels['the_plur'])) {
            $labels['the_plur'] = 'les ' . $labels['name_plur'];
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

            case 'to':
                if ($vowel_first) {
                    return 'à l\'' . $object_name;
                } elseif ($isFemale) {
                    return 'à la ' . $object_name;
                } else {
                    return 'au ' . $object_name;
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

            case 'the_plur':
                return 'les ' . $name_plur;

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
            return $this->getLabel() . ' ' . $this->id;
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

            case 'the_plur':
                $label = 'les objets';
                break;

            case 'a':
                $label = 'un objet';
                break;

            case 'to':
                $label = 'à l\'objet';
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

    public static function isInstanceLabelFemale($instance)
    {
        if (!is_null($instance) && is_a($instance, 'BimpObject')) {
            return $instance->isLabelFemale();
        }

        return false;
    }

    public static function getInstanceNom($instance)
    {
        if (is_a($instance, 'BimpObject')) {
            return $instance->getInstanceName();
        } elseif (is_a($instance, 'user')) {
            return $instance->lastname . ' ' . $instance->firstname;
        } elseif (property_exists($instance, 'nom')) {
            return $instance->nom;
        } elseif (property_exists($instance, 'label')) {
            return $instance->label;
        } elseif (property_exists($instance, 'ref')) {
            return $instance->ref;
        } elseif (isset($instance->id) && $instance->id) {
            return $instance->id;
        }
        return '';
    }

    // Liens et url: 

    public function getUrl()
    {
        if (!$this->isLoaded()) {
            return '';
        }

        $controller = $this->getController();
        if (!$controller) {
            return '';
        }

        return $this->module . '/index.php?fc=' . $controller . '&id=' . $this->id;
    }

    public function getChildObjectUrl($object_name, $object = null)
    {
        if (is_null($object)) {
            $object = $this->config->getObject('', $object_name);
        }

        if (is_null($object)) {
            return '';
        }

        if (is_a($object, 'BimpObject')) {
            return $object->getUrl();
        }

        $module = $this->config->getObjectModule("", $object_name);
        if ($module) {
            $file = $module . '/card.php';
            if (file_exists(DOL_DOCUMENT_ROOT . '/' . $file)) {
                $primary = 'id';
                if (is_a($object, 'Societe')) {
                    $primary = 'socid';
                }
                return DOL_URL_ROOT . '/' . $file . (isset($object->id) && $object->id ? '?' . $primary . '=' . $object->id : '');
            }
        }

        return '';
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
