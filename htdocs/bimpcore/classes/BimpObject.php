<?php

class BimpObject extends BimpCache
{

    public $db = null;
    public $module = '';
    public $object_name = '';
    public $config = null;
    public $id = null;
    public $ref = "";
    public static $status_list = array();
    public static $common_fields = array(
        'id',
        'date_create',
        'date_update',
        'user_create',
        'user_update',
        'position'
    );
    public static $numeric_types = array('id', 'id_parent', 'id_object', 'int', 'float', 'money', 'percent', 'bool', 'qty');
    public static $name_properties = array('public_name', 'name', 'nom', 'label', 'libelle', 'title', 'titre', 'description');
    public static $ref_properties = array('ref', 'reference', 'code', 'facnumber');
    public static $status_properties = array('status', 'fk_statut', 'statut');
    public $use_commom_fields = false;
    public $use_positions = false;
    public $params_defs = array(
        'table'                    => array('default' => ''),
        'controller'               => array('default' => ''),
        'icon'                     => array('default' => ''),
        'primary'                  => array('default' => 'id'),
        'common_fields'            => array('data_type' => 'bool', 'default' => 1),
        'header_list_name'         => array('default' => ''),
        'header_btn'               => array('data_type' => 'array', 'default' => array()),
        'header_edit_form'         => array('default' => ''),
        'header_delete_btn'        => array('data_type' => 'bool', 'default' => 1),
        'list_page_url'            => array('data_type' => 'array'),
        'parent_object'            => array('default' => ''),
        'parent_id_property'       => array('defautl' => ''),
        'parent_module'            => array('default' => ''),
        'positions'                => array('data_type' => 'bool', 'default' => 0),
        'position_insert'          => array('default' => 'before'),
        'labels'                   => array('type' => 'definitions', 'defs_type' => 'labels'),
        'objects'                  => array('type' => 'definitions', 'defs_type' => 'object_child', 'multiple' => true),
        'force_extrafields_update' => array('data_type' => 'bool', 'default' => 0),
        'associations'             => array('type' => 'keys'),
        'fields'                   => array('type' => 'keys'),
        'forms'                    => array('type' => 'keys'),
        'fields_tables'            => array('type' => 'keys'),
        'views'                    => array('type' => 'keys'),
        'lists'                    => array('type' => 'keys'),
        'cards'                    => array('type' => 'keys'),
        'searches'                 => array('type' => 'keys'),
    );
    public static $check_on_create = 1;
    public static $check_on_update = 1;
    public static $check_on_update_field = 1;
    public $params = array();
    public $msgs = array(
        'errors'   => array(),
        'warnings' => array(),
        'infos'    => array()
    );
    protected $data = array();
    protected $initData = array();
    protected $associations = array();
    protected $history = array();
    public $parent = null;
    public $dol_object = null;
    public $extends = array();
    public $redirectMode = 5; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old
    public $noFetchOnTrigger = false;

    // Gestion instance:

    public static function getInstance($module, $object_name, $id_object = null, $parent = null)
    {
        $file = DOL_DOCUMENT_ROOT . '/' . $module . '/objects/' . $object_name . '.class.php';
        if (file_exists($file)) {
            if (!class_exists($object_name)) {
                require_once $file;
            }
            $instance = new $object_name($module, $object_name);
        } else {
            $instance = new BimpObject($module, $object_name);
        }

        if (!is_null($id_object)) {
            $instance->fetch($id_object, $parent);
        } else {
            $instance->parent = $parent;
        }

        return $instance;
    }

    public static function getDolInstance($dol_object_params, $id_object = null)
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
            $instance = new $className($db);

            if (!is_null($id_object)) {
                $instance->fetch($id_object);
            }

            return $instance;
        }

        return null;
    }

    public static function loadClass($module, $object_name)
    {
        if (!class_exists($object_name)) {
            $file = DOL_DOCUMENT_ROOT . '/' . $module . '/objects/' . $object_name . '.class.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
        return false;
    }

    public static function objectLoaded($object)
    {
        if (!is_null($object)) {
            if (is_a($object, 'BimpObject')) {
                if ($object->isLoaded()) {
                    return 1;
                }
            } elseif (isset($object->id) && (int) $object->id) {
                return 1;
            } elseif (isset($object->rowid) && (int) $object->rowid) {
                return 1;
            }
        }

        return 0;
    }

    public function __construct($module, $object_name)
    {
        $this->db = self::getBdb();
        $this->module = $module;
        $this->object_name = $object_name;
        $this->config = new BimpConfig(DOL_DOCUMENT_ROOT . '/' . $module . '/objects/', $object_name, $this);

        $this->use_commom_fields = (int) $this->getConf('common_fields', 1, false, 'bool');
        $this->use_positions = (int) $this->getConf('positions', 0, false, 'bool');

        if ($this->config->isDefined('dol_object')) {
            $this->dol_object = $this->config->getObject('dol_object');
            $this->use_commom_fields = 0;
        }

        $this->addCommonFieldsConfig();

        $errors = array();
        $this->params = BimpComponent::fetchParamsStatic($this->config, '', $this->params_defs, $errors);

        if (!$this->params['parent_module']) {
            $this->params['parent_module'] = $this->module;
        }

        $this->addConfigExtraParams();
    }

    public function isDolObject()
    {
        return $this->config->isDefined('dol_object');
    }

    protected function addCommonFieldsConfig()
    {
        $primary = $this->getPrimary();
        $this->config->params['fields'][$primary] = array(
            'label'    => 'ID',
            'type'     => 'id',
            'input'    => array(
                'type' => 'hidden'
            ),
            'editable' => 0
        );

        if ($this->use_commom_fields) {
            $this->config->params['fields']['date_create'] = array(
                'label'    => 'Créé le',
                'type'     => 'datetime',
                'input'    => array(
                    'type' => 'hidden'
                ),
                'editable' => 0
            );
            $this->config->params['fields']['date_update'] = array(
                'label'    => 'Mis à jour le',
                'type'     => 'datetime',
                'input'    => array(
                    'type' => 'hidden'
                ),
                'editable' => 0
            );
            $this->config->params['objects']['user_create'] = array(
                'relation' => 'hasOne',
                'delete'   => 0,
                'instance' => array(
                    'dol_object' => 'user',
                    'id_object'  => array(
                        'field_value' => 'user_create'
                    )
                )
            );
            $this->config->params['fields']['user_create'] = array(
                'label'         => 'Créé par',
                'type'          => 'id_object',
                'object'        => 'user_create',
                'input'         => array(
                    'type' => 'hidden'
                ),
                'search'        => array(
                    'input' => array(
                        'type' => 'search_user'
                    )
                ),
                'default_value' => array(
                    'prop' => array(
                        'name'   => 'id',
                        'object' => array(
                            'global' => 'user'
                        )
                    )
                ),
                'display'       => array(
                    'default' => array(
                        'type' => 'nom_url'
                    )
                ),
                'editable'      => 0
            );
            $this->config->params['objects']['user_update'] = array(
                'relation' => 'hasOne',
                'delete'   => 0,
                'instance' => array(
                    'dol_object' => 'user',
                    'id_object'  => array(
                        'field_value' => 'user_update'
                    )
                )
            );
            $this->config->params['fields']['user_update'] = array(
                'label'         => 'Mis à jour par',
                'type'          => 'id_object',
                'object'        => 'user_update',
                'input'         => array(
                    'type' => 'hidden'
                ),
                'search'        => array(
                   'input' => array(
                       'type' => 'search_user'
                   )
                ),
                'default_value' => array(
                    'prop' => array(
                        'name'   => 'id',
                        'object' => array(
                            'global' => 'user'
                        )
                    )
                ),
                'display'       => array(
                    'default' => array(
                        'type' => 'nom_url'
                    )
                ),
                'editable'      => 0
            );
        }

        if ($this->use_positions) {
            $this->config->params['fields']['position'] = array(
                'label'    => 'Position',
                'type'     => 'int',
                'input'    => array(
                    'type' => 'hidden'
                ),
                'editable' => 0
            );
        }

        $parentModule = $this->getParentModule();
        $parentObjectName = $this->getParentObjectName();
        $parentIdProperty = $this->getParentIdProperty();

        if ($parentModule && $parentObjectName && $parentIdProperty) {
            $this->config->params['objects']['parent'] = array(
                'relation' => 'hasOne',
                'delete'   => 0,
                'instance' => array(
                    'bimp_object' => array(
                        'module' => $parentModule,
                        'name'   => $parentObjectName
                    ),
                    'id_object'   => array(
                        'field_value' => $parentIdProperty
                    )
                )
            );
            $this->params['objects'][] = 'parent';
        }
    }

    // Getters configuation: 

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
        $primary = $this->params['primary'];

        if (!$primary) {
            if ($this->isDolObject()) {
                $primary = 'rowid';
            } else {
                $primary = 'id';
            }
        }

        return $primary;
    }

    public function getTable($table = '')
    {
        return $this->params['table'];
    }

    public function getController()
    {
        return $this->params['controller'];
    }

    public function getParentIdProperty()
    {
        $property = $this->params['parent_id_property'];
//        if ($this->object_name === 'BE_Place') {
////            echo $this->object_name . ': ' . $property . '<br/>';
////            echo '<pre>';
////            print_r($this->params);
////            echo '</pre>';
////        }
        if (is_null($property)) {
            if ($this->field_exists('id_parent')) {
                $property = 'id_parent';
            }
        }
        return $property;
    }

    public function getChildIdProperty($child_name)
    {
        if ($child_name === 'parent') {
            return $this->getParentIdProperty();
        }

        if (isset($this->config->params['objects'][$child_name]['instance']['id_object']['field_value'])) {
            return $this->config->params['objects'][$child_name]['instance']['id_object']['field_value'];
        }

        return '';
    }

    public function getParentId()
    {
        $prop = $this->getParentIdProperty();
        if (!is_null($prop)) {
            return (int) $this->getData($prop);
        }

        return null;
    }

    public function getParentObjectName()
    {
        return $this->params['parent_object'];
    }

    public function getParentModule()
    {
        return $this->params['parent_module'];
    }

    public function getParentInstance()
    {
        $id_property = $this->getParentIdProperty();
        $id_parent = (int) $this->getData($id_property);

        if (is_null($this->parent) || ($id_parent && (!(int) $this->parent->id || (int) $this->parent->id !== $id_parent))) {
            unset($this->parent);
            $this->parent = null;
            $module = $this->getParentModule();
            $object_name = $this->getParentObjectName();

            if ($module && $object_name) {
                $this->parent = BimpCache::getBimpObjectInstance($module, $object_name, $id_parent);
            }
        }
        return $this->parent;
    }

    public function getFilesDir()
    {
        if ($this->isLoaded()) {
            return DOL_DATA_ROOT . '/bimpcore/' . $this->module . '/' . $this->object_name . '/' . $this->id . '/';
        }

        return '';
    }

    public function getFileUrl($file_name)
    {
        if (!$file_name) {
            return '';
        }

        if (!$this->isLoaded()) {
            return '';
        }

        $file = $this->module . '/' . $this->object_name . '/' . $this->id . '/' . $file_name;

        return DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . urlencode($file);
    }

    public function getListConfig($owner_type, $id_owner, $list_name = 'default')
    {
        return self::getObjectListConfig($this->module, $this->object_name, $owner_type, $id_owner, $list_name);
    }

    public function getNameProperty()
    {
        foreach (self::$name_properties as $prop) {
            if ($this->field_exists($prop)) {
                return $prop;
            }
        }

        return '';
    }

    public function getRefProperty()
    {
        foreach (self::$ref_properties as $prop) {
            if ($this->field_exists($prop)) {
                return $prop;
            }
        }

        return '';
    }

    public function getStatusProperty()
    {
        foreach (self::$status_properties as $prop) {
            if ($this->field_exists($prop)) {
                return $prop;
            }
        }

        return '';
    }

    public function getSearchListFilters()
    {
        return array();
    }

    public function getSearchData($search_name = 'default')
    {
        $syntaxe = '';
        $has_extrafields = false;
        $fields_seach = array($this->getPrimary());
        $fields_return_label = array();
        $ref_prop = $this->getRefProperty();

        $n = 0;
        if ($ref_prop) {
            if ($this->isDolObject() && (int) $this->getConf('fields/' . $ref_prop . '/dol_extra_field', 0, false, 'bool')) {
                if (preg_match('/^ef_(.*)$/', $ref_prop, $matches)) {
                    $ref_prop = $matches[1];
                }
                $ref_prop = 'ef.' . $ref_prop;
                $has_extrafields = true;
            } else {
                $ref_prop = $ref_prop;
            }
            $n++;
            $fields_seach[] = $ref_prop;
            $fields_return_label[] = $ref_prop;
            $syntaxe .= '<label_' . $n . '>';
        }

        $name_prop = $this->getNameProperty();
        if ($name_prop) {
            if ($this->isDolObject() && (int) $this->getConf('fields/' . $name_prop . '/dol_extra_field', 0, false, 'bool')) {
                if (preg_match('/^ef_(.*)$/', $name_prop, $matches)) {
                    $name_prop = $matches[1];
                }
                $name_prop = 'ef.' . $name_prop;
                $has_extrafields = true;
            }
            $n++;
            $fields_seach[] = $name_prop;
            $fields_return_label[] = $name_prop;

            if ($syntaxe) {
                $syntaxe .= ' - ';
            }

            $syntaxe .= '<label_' . $n . '>';
        }

        return array(
            'fields_search'      => $fields_seach,
            'field_return_label' => $fields_return_label,
            'label_syntaxe'      => $syntaxe,
            'filters'            => $this->getSearchListFilters(),
            'has_extrafields'    => $has_extrafields
        );
    }

    public function getListPageUrl($list_name = 'default')
    {
        if (is_string($this->params['list_page_url'])) {
            if ($this->params['list_page_url'] === 'list') {
                return DOL_URL_ROOT . '/bimpcore/index.php?fc=list&module=' . $this->module . '&object_name=' . $this->object_name . '&list_name=' . $list_name;
            }

            return '';
        }

        return BimpTools::makeUrlFromConfig($this->config, 'list_page_url', $this->module, $this->getController());
    }

    // Getters boolééns: 

    public function isLoaded(&$errors = array())
    {
        $check = 0;
        if (isset($this->id) && (int) $this->id) {
            $check = 1;
            if ($this->isDolObject() && (!isset($this->dol_object->id) || !(int) $this->dol_object->id)) {
                $check = 0;
            }
        }

        if (!$check) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        }

        return $check;
    }

    public function isNotLoaded()
    {
        return (int) ($this->isLoaded() ? 0 : 1);
    }

    public function field_exists($field_name)
    {
        return ($this->use_commom_fields && in_array($field_name, self::$common_fields)) ||
                in_array($field_name, $this->params['fields']);
    }

    public function isExtraField($field_name)
    {
        return (int) $this->getConf('fields/' . $field_name . '/extra', 0);
    }

    public function dol_field_exists($field_name)
    {
        if (!$this->field_exists($field_name)) {
            return false;
        }

        if ($this->isDolObject()) {
            if ((int) $this->getConf('fields/' . $field_name . '/dol_extra_field', 0, false, 'bool')) {
                if (preg_match('/^ef_(.*)$/', $field_name, $matches)) {
                    $field_name = $matches[1];
                }

                $extra_fields = self::getExtraFieldsArray($this->dol_object->table_element);
                if (!is_array($extra_fields) || !isset($extra_fields[$field_name])) {
                    return false;
                }
            }
        }

        return true;
    }

    public function isDolField($field_name)
    {
        if ($this->isDolObject()) {
            if ((int) $this->getConf('fields/' . $field_name . '/dol_extra_field', 0)) {
                return 1;
            }

            $prop = $this->getConf('fields/' . $field_name . '/dol_prop', $field_name);

            if (property_exists($this->dol_object, $prop)) {
                return 1;
            }
        }

        return 0;
    }

    public function isDolExtraField(&$field_name)
    {
        if ($this->isDolObject()) {
            if ((int) $this->getConf('fields/' . $field_name . '/dol_extra_field', 0, false, 'bool')) {
                if (preg_match('/^ef_(.*)$/', $field_name, $matches)) {
                    $field_name = $matches[1];
                }
                return 1;
            }
        }

        return 0;
    }

    public function object_exists($object_name)
    {
        return array_key_exists($object_name, $this->params['objects']);
    }

    public function association_exists($association)
    {
        return in_array($association, $this->params['associations']);
    }

    public function isChild($instance)
    {
        if (is_a($instance, 'BimpFile'))
            return true;

        if (is_a($instance, 'BimpObject')) {
            $instance_parent_module = $instance->getParentModule();
            $instance_parent_object_name = $instance->getParentObjectName();

            if ($instance_parent_module === $this->module &&
                    $instance_parent_object_name === $this->object_name) {
                if (!$instance->isLoaded()) {
                    return 1;
                }
                if ((int) $instance->getParentId() === (int) $this->id) {
                    return 1;
                }
                return 0;
            }

            if (is_array($this->extends)) {
                foreach ($this->extends as $extends) {
                    if ($extends['module'] === $instance_parent_module &&
                            $extends['object_name'] === $instance_parent_object_name) {
                        if (!$instance->isLoaded()) {
                            return 1;
                        }
                        if ((int) $instance->getParentId() === (int) $this->id) {
                            return 1;
                        }
                        return 0;
                    }
                }
            }
        }
        return 0;
    }

    public function isParent(BimpObject $object)
    {
        if (!BimpObject::objectLoaded($object)) {
            return 0;
        }
        if ($this->getParentModule() !== $object->module || $this->getParentObjectName() !== $object->object_name || (int) $object->id !== (int) $this->getParentId()) {
            return 0;
        }

        return 1;
    }

    public function doMatchFilters($filters)
    {
        foreach ($filters as $field => $filter) {
            if ($this->field_exists($field)) {
                if (is_array($filter)) {
                    // todo ... 
                    return 0;
                } else {
                    $type = $this->getConf('fields/' . $field . '/type', 'string', false);
                    $value = $this->getData($field);
                    BimpTools::checkValueByType($type, $value);
                    BimpTools::checkValueByType($type, $filter);
                    if ($value !== $filter) {
                        return 0;
                    }
                }
            } else {
                return 0;
            }
        }

        return 1;
    }

    public function showListConfigNbItems()
    {
        $list_name = BimpTools::getPostFieldValue('list_name');

        $path = BC_List::getConfigPath($this, $list_name);

        if ($path) {
            return (int) $this->config->get($path . '/pagination', 1, false, 'bool');
        }

        return 1;
    }

    public function showListConfigSort()
    {
        $list_name = BimpTools::getPostFieldValue('list_name');

        $path = BC_List::getConfigPath($this, $list_name);

        if ($path) {
            return (int) $this->config->get($path . '/enable_sort', 1, false, 'bool');
        }

        return 1;
    }

    public function hasDataChanged($field)
    {
        return (int) ($this->getData($field) !== $this->getInitData($field));
    }

    // Getters données: 

    public function getData($field)
    {
        if ($field === 'id') {
            return $this->id;
        }

        if (isset($this->data[$field])) {
            return $this->data[$field];
        }

        if ($this->field_exists($field)) {
            return $this->getConf('fields/' . $field . '/default_value');
        }

        return null;
    }

    public function getInitData($field)
    {
        if ($field === 'id') {
            return $this->id;
        }

        if (isset($this->initData[$field])) {
            return $this->initData[$field];
        }

        if ($this->field_exists($field)) {
            return $this->getConf('fields/' . $field . '/default_value');
        }

        return null;
    }

    public function getDataArray($include_id = false)
    {
        if (!count($this->params['fields'])) {
            return array();
        }

        $data = array();

        foreach ($this->params['fields'] as $field) {
            if (isset($this->data[$field])) {
                $data[$field] = $this->data[$field];
            } else {
                $data[$field] = $this->getConf('fields/' . $field . '/default_value', null, false, 'any');
            }
        }
        if ($include_id) {
            $primary = $this->getPrimary();
            $id = !is_null($this->id) ? $this->id : 0;
            $data['id'] = $id;
            if ($primary !== 'id') {
                $data[$primary] = $id;
            }
        }

        return $data;
    }

    public function getSavedData($field, $id_object = null)
    {
        // $id_object peut être un array d'id. 
        if (is_null($id_object)) {
            if ($this->isLoaded()) {
                $id_object = $this->id;
            } else {
                return null;
            }
        }

        if (!$this->field_exists($field)) {
            return null;
        }

        $value = null;

        if ($this->isExtraField($field)) {
            // Cas d'un extrafield BimpObject: 
            if (is_array($id_object)) {
                $value = array();
                foreach ($id_object as $id) {
                    $value[$id] = $this->getExtraFieldSavedValue($field, $id);
                }
            } else {
                $value = $this->getExtraFieldSavedValue($field, $id_object);
            }
        } elseif ($this->isDolObject() && $this->dol_field_exists($field) &&
                (int) $this->getConf('fields/' . $field . '/dol_extra_field', 0, false, 'bool')) {
            // Cas d'un extrafield Dolibarr:
            if (preg_match('/^ef_(.*)$/', $field, $matches)) {
                $field = $matches[1];
            }
            if (is_array($id_object)) {
                $rows = $this->db->getRows($this->getTable() . '_extrafields', '`fk_object` IN (' . implode(',', $id_object) . ')', null, 'array', array('fk_object', $field));
                if (is_array($rows)) {
                    $value = array();
                    foreach ($rows as $r) {
                        $value[(int) $r['fk_object']] = $r[$field];
                    }
                }
            } else {
                $value = $this->db->getValue($this->getTable() . '_extrafields', $field, '`fk_object` = ' . (int) $id_object);
            }
        } else {
            // Cas ordinaire: 
            $primary = $this->getPrimary();
            $table = $this->getTable();

            if ($table && $primary) {
                if (is_array($id_object)) {
                    $rows = $this->db->getRows($table, '`' . $primary . '` IN (' . implode(',', $id_object) . ')', null, 'array', array($primary, $field));
                    if (is_array($rows)) {
                        $value = array();
                        foreach ($rows as $r) {
                            $value[(int) $r[$primary]] = $r[$field];
                        }
                    }
                } else {
                    $value = $this->db->getValue($table, $field, '`' . $primary . '` = ' . (int) $id_object);
                }
            }
        }

        // récup valeur par défaut: 
        if (is_null($value)) {
            if (is_array($id_object)) {
                $value = array();
                $def_val = $this->getConf('fields/' . $field . '/default_value', null, false, 'any');
                foreach ($id_object as $id) {
                    $value[$id] = $def_val;
                }
            } else {
                $value = $this->getConf('fields/' . $field . '/default_value', null, false, 'any');
            }
        } elseif (is_array($id_object)) {
            if (!is_array($value)) {
                $value = array();
                $def_val = $this->getConf('fields/' . $field . '/default_value', null, false, 'any');
                foreach ($id_object as $id) {
                    if (!isset($value[$id]) || is_null($value[$id])) {
                        $value[$id] = $def_val;
                    }
                }
            }
        }

        return $value;
    }

    public function getDbData($fields = null)
    {
        $data = array();

        $primary = $this->getPrimary();

        foreach ($this->data as $field => $value) {
            if ($field === $primary || $field === 'id') {
                continue;
            }

            if (!is_null($fields) && !empty($fields) && !in_array($field, $fields)) {
                continue;
            }

            if ($this->isExtraField($field)) {
                continue;
            }

            if (!is_null($value)) {
                $this->checkFieldValueType($field, $value);
                $this->checkFieldHistory($field, $value);

                $field_type = $this->getConf('fields/' . $field . '/type', 'string');

                if ($field_type === 'items_list') {
                    if (is_array($value)) {
                        $delimiter = $this->getConf('fields/' . $field . '/delimiter', ',');
                        $value = implode($delimiter, $value);
                    }
                }

                if (!is_null($value)) {
                    $data[$field] = $value;
                }
            }
        }

        return $data;
    }

    public function getExport($niveau = 10, $pref = "", $format = "xml", $sep = ";", $sautLn = "\n")
    {
        if (!$this->isLoaded())
            return "Objet non loadé";

        $tabResult = array();
        foreach ($this->config->getCompiledParams('fields') as $nom => $info) {
            $value = $this->getData($nom);

            if ($info['type'] == "int") {
                $value = intval($value);
                if (is_array($info['values']['array']) && isset($info['values']['array'][$value]))
                    $value = $info['values']['array'][$value];
            }
            elseif ($info['type'] == "id_object") {
                continue; //Car on les retrouve enssuite de nouveau dans $this->params['objects']
//                $obj = $this->getChildObject($info['object']);
//                $value = $this->recursiveGetExport($niveau, $pref, $obj);
            } elseif ($info['type'] == "bool")
                $value = ($value ? "OUI" : "NON");

            $tabResult[$nom] = $value;
        }

        foreach ($this->params['objects'] as $nom => $infoObj) {
            $value = "";
            if ($infoObj['relation'] == "hasMany") {
                $html .= "<" . $nom . ">";
                $lines = $this->getChildrenObjects($nom);
                $i = 0;
                $value = array();
                foreach ($lines as $obj) {
                    $i++;
                    $value[$nom . "-" . $i] = $this->recursiveGetExport($niveau, $pref . "-" . $i, $obj);
                }
            } elseif ($infoObj['relation'] == "hasOne") {
                $obj = $this->getChildObject($nom);
                $value = $this->recursiveGetExport($niveau, $pref . "-" . $i, $obj);
            } elseif ($infoObj['relation'] == "none") {
//                    $lines = $this->get($nom);
//                    $i = 0;
//                    $value = array();
//                    foreach($lines as $obj){
//                        die;
//                        $i++;
//                        $value[$nom."-".$i] = $this->recursiveGetExport($niveau, $pref."-".$i, $obj);
//                    }

                if ($nom == "files") {
                    $value = $this->getObjectFilesArray($this);
                }
//                 elseif($nom == "contact"){
////                    $value = $this->($name);
//                }
                else {
                    $obj = $this->getChildObject($nom);
                    $value = $this->recursiveGetExport($niveau, $pref, $obj);
                }
            } else {
                print_r($infoObj);
            }
            $tabResult[$nom] = $value;
        }

        return $tabResult;
    }

    public function recursiveGetExport($niveau, $pref, $obj)
    {
        $value = "";
        if ($niveau > 0) {
            if (is_a($obj, "BimpObject")) {
                if (method_exists($obj, "isLoaded"))
                    if ($obj->isLoaded())
                        $value = $obj->getExport($niveau - 1, $pref . "-");
                    else
                        echo "ERR Objet non loadé";
                else
                    echo "ERR Objet bizarre";
            }
            elseif (is_a($obj, "CommonObject")) {
                $id = 0;
                if (property_exists($obj, 'id'))
                    $id = $obj->id;
                else
                    $value = "ERR pas de champ ID" . $nom;
                if ($id > 0) {
                    $value = array();
                    if (method_exists($obj, "getNomUrl"))
                        if (isset($obj->id) && $obj->id > 0)
                            $value['lien'] = $obj->getNomUrl(1);
                    if (method_exists($obj, "fetch_optionals") && count($obj->array_options) < 1)
                        $obj->fetch_optionals();
                    foreach ($obj as $clef => $val) {
                        if (!in_array($clef, array("db", "error", "errors", "context", "oldcopy")))
                            $value[$clef] = $val;
                    }
                }
            }
            else {
                echo "ERR Type objet inconnue " . get_class($obj);
                $value = "ERR Type objet inconnue " . get_class($obj);
            }
        }
        return $value;
    }

    public function getPageTitle()
    {
        $title = BimpTools::ucfirst($this->getLabel());

        if ($this->isLoaded()) {
            $ref = $this->getRef();

            if ($ref) {
                $title .= ' ' . $ref;
            } else {
                $title .= ' #' . $this->id;
            }
        }

        return $title;
    }

    public function getRef($withGeneric = true)
    {
        $prop = $this->getRefProperty();

        if ($this->field_exists($prop) && isset($this->data[$prop]) && $this->data[$prop]) {
            return $this->data[$prop];
        }

        if ($withGeneric) {
            return $this->id;
        }

        return '';
    }

    public function getName($withGeneric = true)
    {
        $prop = $this->getNameProperty();

        if ($this->field_exists($prop) && isset($this->data[$prop]) && $this->data[$prop]) {
            return $this->data[$prop];
        }

        if ($withGeneric) {
            return BimpTools::ucfirst($this->getLabel()) . ' #' . $this->id;
        }

        return '';
    }

    public function getStatus()
    {
        $prop = $this->getStatusProperty();

        if ($this->field_exists($prop) && isset($this->data[$prop]) && $this->data[$prop]) {
            return $this->data[$prop];
        }

        return '';
    }

    public function getDolValue($field, $value)
    {
        if ($this->field_exists($field)) {
            $data_type = $this->getConf('fields/' . $field . '/type', 'string');
            switch ($data_type) {
                case 'date':
                case 'time':
                case 'datetime':
                    $value = BimpTools::getDateForDolDate($value);
                    break;
            }
        }

        return $value;
    }

    public function getExtraFields()
    {
        $fields = array();

        foreach ($this->params['fields'] as $field_name) {
            if ($this->isExtraField($field_name)) {
                $fields[] = $field_name;
            }
        }

        return $fields;
    }

    public function getDefaultBankAccount()
    {
        if ((int) BimpCore::getConf('use_caisse_for_payments')) {
            global $user;
            $caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');
            $id_caisse = (int) $caisse->getUserCaisse((int) $user->id);
            if ($id_caisse) {
                $caisse = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Caisse', $id_caisse);
                if ($caisse->isLoaded()) {
                    if ($caisse->isValid()) {
                        return (int) $caisse->getData('id_account');
                    }
                }
            }
        }

        return (int) BimpCore::getConf('bimpcaisse_id_default_account');
    }

    // Gestion des données:

    public function printData($return_html = false)
    {
        if ($return_html) {
            return '<pre>' . print_r($this->data, 1) . '</pre>';
        }

        echo '<pre>';
        print_r($this->data);
        echo '</pre>';
    }

    public function resetMsgs()
    {
        $this->msgs = array(
            'errors'   => array(),
            'warnings' => array(),
            'infos'    => array()
        );
    }

    public function reset()
    {
        if ($this->isLoaded()) {
            $cache_key = 'bimp_object_' . $this->module . '_' . $this->object_name . '_' . $this->id;
            if (isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = null;
            }
        }
        $this->parent = null;

        $this->data = array();
        $this->initData = array();
        $this->associations = array();
        $this->id = null;
        $this->ref = '';

        if (!is_null($this->dol_object)) {
            unset($this->dol_object);
            $this->dol_object = $this->config->getObject('dol_object');
        }

        $this->resetMsgs();
    }

    public function set($field, $value)
    {
        if (!$this->field_exists($field)) {
            return array('Le champ "' . $field . '" n\existe pas');
        }
        return $this->validateValue($field, $value);
    }

    public function setIdParent($id_parent)
    {
        $parent_id_property = $this->getParentIdProperty();
        $this->set($parent_id_property, $id_parent);

        if (!BimpObject::objectLoaded($this->parent) ||
                (BimpObject::objectLoaded($this->parent) && ((int) $this->parent->id !== (int) $id_parent))) {
            $this->parent = null;
            $this->getParentInstance();
        }
    }

    public function setNewStatus($new_status, $extra_data = array(), &$warnings = array())
    {
        BimpLog::actionStart('bimpobject_new_status', 'Nouveau statut', $this);

        $errors = array();
        $new_status = (int) $new_status;

        if (!array_key_exists($new_status, static::$status_list)) {
            $errors[] = 'Erreur: ce statut n\'existe pas';
        } else {
            $status_label = is_array(static::$status_list[$new_status]) ? static::$status_list[$new_status]['label'] : static::$status_list[$new_status];
            $object_label = $this->getLabel('the') . (isset($this->id) && $this->id ? ' ' . $this->id : '');

            if (!$this->canSetStatus($new_status)) {
                $errors[] = 'Vous n\'avez pas la permission de passer ' . $this->getLabel('this') . ' au statut "' . $status_label . '"';
            } elseif ($this->isNewStatusAllowed($new_status, $errors)) {
                $error_msg = 'Impossible de passer ' . $object_label;
                $error_msg .= ' au statut "' . $status_label . '"';

                if (!$this->isLoaded()) {
                    $errors[] = $error_msg . ' ID ' . $this->getLabel('of_the') . ' absent';
                } else {
                    $current_status = (int) $this->getSavedData('status');

                    if ($current_status === $new_status) {
                        $errors[] = $object_label . ' a déjà le statut "' . $status_label . '"';
                    } elseif (method_exists($this, 'onNewStatus')) {
                        $errors = $this->onNewStatus($new_status, $current_status, $extra_data, $warnings);
                    }

                    if (!count($errors)) {
                        $this->set('status', $new_status);
                        $errors = $this->update();
                    }
                }
            }
        }

        BimpLog::actionEnd('bimpobject_new_status', $errors);

        return $errors;
    }

    public function setObjectAction($action, $id_object = 0, $extra_data = array(), &$success = '')
    {
        $errors = array();

        if ((int) $id_object) {
            if (!$this->fetch($id_object)) {
                $errors[] = BimpTools::ucfirst($this->getLabel('the')) . ' d\'ID ' . $id_object . ' n\'existe pas';
            }
        }

        BimpLog::actionStart('bimpobject_action', 'Action "' . $action . '"', $this);

        if (!$this->isLoaded()) {
            $parent_id_prop = $this->getParentIdProperty();
            if ($parent_id_prop) {
                if (!BimpObject::objectLoaded($this->parent)) {
                    $id_parent = (int) BimpTools::getPostFieldValue($parent_id_prop);
                    if ($id_parent) {
                        $this->setIdParent($id_parent);
                    }
                }
            }
        }

        if (!count($errors)) {
            if (!$this->canSetAction($action)) {
                $errors[] = 'Vous n\'avez pas la permission d\'effectuer cette action';
            } elseif (!$this->isActionAllowed($action, $errors)) {
                $errors[] = BimpTools::getMsgFromArray($errors, 'Action impossible');
            }

            if (!count($errors)) {
                $method = 'action' . ucfirst($action);
                if (method_exists($this, $method)) {
                    $errors = $this->{$method}($extra_data, $success);
                } else {
                    $errors[] = 'Action invalide: "' . $action . '"';
                }
            }
        }

        BimpLog::actionEnd('bimpobject_action', (isset($errors['errors']) ? $errors['errors'] : $errors), (isset($errors['warnings']) ? $errors['warnings'] : array()));

        return $errors;
    }

    public function addMultipleValuesItem($name, $value)
    {
        $errors = array();
        if ($this->field_exists($name)) {
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
        } elseif ($this->association_exists($name)) {
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
        if ($this->field_exists($name)) {
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
        } elseif ($this->association_exists($name)) {
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
        $items = array();

        foreach ($list as $id_item) {
            if ((int) $id_item && !in_array((int) $id_item, $items)) {
                $items[] = (int) $id_item;
            }
        }
        if (isset($this->associations[$association])) {
            $this->associations[$association] = $items;
            return true;
        }

        if ($this->config->isDefined('associations/' . $association)) {
            $this->associations[$association] = $items;
            return true;
        }
        return false;
    }

    public function saveAssociationsFromPost()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        $errors = array();

        if (BimpTools::isSubmit('associations_params')) {
            $assos = json_decode(BimpTools::getValue('associations_params'));
            foreach ($assos as $params) {
                if (isset($params->association)) {
                    if (isset($params->object_name) && isset($params->object_module) && isset($params->id_object)) {
                        $obj = BimpObject::getInstance($params->object_module, $params->object_name);
                        $bimpAsso = new BimpAssociation($obj, $params->association);
                        $assos_errors = $bimpAsso->addObjectAssociation($this->id, $params->id_object);
                        if ($assos_errors) {
                            $errors[] = 'Echec de l\'association ' . $this->getLabel('of_the') . ' avec ' . $obj->getLabel('the') . ' ' . $params->id_object;
                            $errors = array_merge($errors, $assos_errors);
                        }
                        unset($bimpAsso);
                    } elseif (isset($params->id_associate)) {
                        $bimpAsso = new BimpAssociation($this, $params->association);
                        $assos_errors = $bimpAsso->addObjectAssociation($params->id_associate, $this->id);
                        if ($assos_errors) {
                            $errors[] = 'Echec de l\'association ' . $this->getLabel('of_the') . ' avec ' . BimpObject::getInstanceLabel($bimpAsso->associate, 'the') . ' ' . $params->id_associate;
                            $errors = array_merge($errors, $assos_errors);
                        }
                        unset($bimpAsso);
                    }
                }
            }
        }

        return $errors;
    }

    public function getSearchFilters(&$joins = array(), $fields = null, $alias = '')
    {
        $filters = array();

        if (is_null($fields) && BimpTools::isSubmit('search_fields')) {
            $fields = BimpTools::getValue('search_fields', null);

            if (BimpTools::isSubmit('search_children')) {
                foreach (BimpTools::getValue('search_children') as $child_name => $child_fields) {
                    $on_field = $this->getChildIdProperty($child_name);
                    if ($on_field) {
                        $instance = $this->getChildObject($child_name);
                        if ($on_field && !is_null($instance) && is_a($instance, 'BimpObject')) {
                            $joins[$child_name] = array(
                                'table' => $instance->getTable(),
                                'alias' => $child_name,
                                'on'    => ($alias ? $alias : 'a') . '.' . $on_field . ' = ' . $child_name . '.' . $instance->getPrimary()
                            );
                            $filters = array_merge($filters, $instance->getSearchFilters($joins, $child_fields, $child_name));
                        }
                    }
                }
            }
        }

        if (!is_null($fields)) {
            $prev_path = $this->config->current_path;
            foreach ($fields as $field_name => $value) {
                if ($value === '') {
                    continue;
                }

                if (!$alias) {
                    $alias = 'a';
                }

                $filter_key = $alias . '.' . $field_name;

                $method = 'get' . ucfirst($field_name) . 'SearchFilters';
                if (method_exists($this, $method)) {
                    $this->{$method}($filters, $value, $joins, $alias);
                    continue;
                }
                if (in_array($field_name, self::$common_fields)) {
                    switch ($field_name) {
                        case 'id':
                            $filters[$filter_key] = array(
                                'part_type' => 'beginning',
                                'part'      => $value
                            );
                            break;

                        case 'user_create':
                        case 'user_update':
                            $filters[$filter_key] = $value;
                            break;

                        case 'date_create':
                        case 'date_update':
                            if (!isset($value['to']) || !$value['to']) {
                                $value['to'] = date('Y-m-d H:i:s');
                            }
                            if (!isset($value['from']) || !$value['from']) {
                                $value['from'] = '0000-00-00 00:00:00';
                            }
                            $filters[$filter_key] = array(
                                'min' => $value['from'],
                                'max' => $value['to']
                            );
                            break;
                    }
                } elseif ($this->field_exists($field_name)) {
                    if ($value === '') {
                        $data_type = $this->getCurrentConf('type', '');
                        if (in_array($data_type, array('id_object', 'id'))) {
                            continue;
                        }
                    }

                    if ($alias !== 'a' && $this->isDolExtraField($field_name)) {
                        if (!isset($joins[$alias . '_ef'])) {
                            $joins[$alias . '_ef'] = array(
                                'table' => $this->getTable() . '_extrafields',
                                'on'    => $alias . '.rowid = ' . $alias . '_ef.fk_object',
                                'alias' => $alias . '_ef'
                            );
                        }
                        $filter_key = $alias . '_ef.' . $field_name;
                    }

                    $bc_field = new BC_Field($this, $field_name);
                    $seach_data = $bc_field->getSearchData();

                    switch ($seach_data['search_type']) {
                        case 'search_object':
                            $instance = null;
                            if ($bc_field->params['type'] === 'id_parent') {
                                $instance = BimpObject::getInstance($this->getParentModule(), $this->getParentObjectName());
                            } elseif (isset($bc_field->params['object']) && $bc_field->params['object']) {
                                $instance_params = $this->config->getObjectInstanceParams('', $bc_field->params['object']);
                                if ($instance_params['object_type'] === 'bimp_object') {
                                    $instance = BimpObject::getInstance($instance_params['module'], $instance_params['object_name']);
                                }
                            }
                            if (is_a($instance, 'BimpObject')) {
                                $id_prop = $instance->getPrimary();
                                $ref_prop = $instance->getRefProperty();

                                if ($id_prop) {
                                    $table = $instance->getTable();
                                    $joins[$table] = array(
                                        'alias' => $table,
                                        'table' => $table,
                                        'on'    => $table . '.' . $id_prop . ' = ' . $alias . '.' . $field_name
                                    );
                                    $filters['or_' . $field_name] = array(
                                        'or' => array(
                                            $table . '.' . $id_prop => $value
                                        )
                                    );
                                    if ($ref_prop) {
                                        $filters['or_' . $field_name]['or'][$table . '.' . $ref_prop] = array(
                                            'part_type' => 'middle',
                                            'part'      => $value
                                        );
                                    }
                                }
                            }
                            break;

                        case 'time_range':
                        case 'date_range':
                        case 'datetime_range':
                            if (is_array($value) &&
                                    isset($value['to']) && $value['to'] &&
                                    isset($value['from']) && $value['from']) {
                                if ($value['from'] <= $value['to']) {
                                    $filters[$filter_key] = array(
                                        'min' => $value['from'],
                                        'max' => $value['to']
                                    );
                                }
                            }
                            break;

                        case 'values_range':
                            if (isset($value['min']) && isset($value['max'])) {
                                $filters[$filter_key] = $value;
                            }
                            break;

                        case 'value_part':
                            $filters[$filter_key] = array(
                                'part_type' => $seach_data['part_type'],
                                'part'      => $value
                            );
                            break;

                        case 'field_input':
                        case 'values':
                        default:
                            $filters[$filter_key] = $value;
                            break;
                    }
                }
            }
            $this->config->setCurrentPath($prev_path);
        }
        return $filters;
    }

    protected function checkFieldHistory($field, $value)
    {
        if (is_null($value)) {
            return;
        }
        if ((int) $this->getConf('fields/' . $field . '/history', 0, false, 'bool')) {
            $current_value = $this->getInitData($field);
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

    public function checkObject($context = '', $field = '')
    {
        // Attention au risque de boucle infinie lors de la redéfinition de cette fonction en cas 
        // de déclenchement de $this->update(), $this->create(), $this->updateField() ou $this->fetch(). 
        // Utiliser $context pour connaître l'origine de l'appel de cette fonction (create, update, updateField, fetch (via BimpCache::getBimpObjectInstance()). 
    }

    public function checkFieldValueType($field, &$value)
    {
        $type = '';
        if (in_array($field, self::$common_fields)) {
            switch ($field) {
                case 'id':
                    $type = 'id';
                    break;

                case 'user_create':
                case 'user_update':
                    $type = 'id_object';
                    break;

                case 'date_create':
                case 'date_update':
                    $type = 'datetime';
                    break;

                case 'position':
                    $type = 'int';
                    break;
            }
        } else {
            $type = $this->getConf('fields/' . $field . '/type', 'string');
        }

        if ($type) {

            // Ajustement du format des dates dans le cas des objets Dolibarr:
            if ($this->isDolObject()) {
                if (in_array($type, array('datetime', 'date', 'time'))) {
                    if (stripos($value, "-") || stripos($value, "/"))
                        $value = $this->db->db->jdate($value);

                    $value = $this->db->db->idate($value);
                    if (preg_match('/^(\d{4})\-?(\d{2})\-?(\d{2}) ?(\d{2})?:?(\d{2})?:?(\d{2})?$/', $value, $matches)) {
                        switch ($type) {
                            case 'datetime':
                                $value = $matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' ' . $matches[4] . ':' . $matches[5] . ':' . $matches[6];
                                break;

                            case 'date':
                                $value = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
                                break;

                            case 'time':
                                $value = $matches[4] . ':' . $matches[5] . ':' . $matches[6];
                                break;
                        }
                    }
                }
            }

            // Traitement des cas particuliers des listes de valeurs: 
            if ($type === 'items_list') {
                if (is_string($value)) {
                    $delimiter = $this->getConf('fields/' . $field . '/items_delimiter', ',');
                    $value = explode($delimiter, $value);
                }
                if (!is_array($value)) {
                    return false;
                }

                $item_type = $this->getConf('fields/' . $field . '/items_data_type', 'string');
                $check = true;
                foreach ($value as $key => $item_value) {
                    if (in_array($item_type, array('id', 'id_object'))) {
                        if ($item_value === '') {
                            unset($value[$key]);
                            continue;
                        }
                    }
                    if (!BimpTools::checkValueByType($item_type, $item_value)) {
                        $check = false;
                    }
                }
                return $check;
            }

            // Vérification et ajustement de la valeur selon son type: 
            return BimpTools::checkValueByType($type, $value);
        }

        return false;
    }

    protected function checkSqlFilters($filters, &$has_extrafields = false, &$joins = array(), $main_alias = '')
    {
        $return = array();
        foreach ($filters as $field => $filter) {
            if (is_array($filter) && isset($filter['or'])) {
                $return[$field] = array('or' => $this->checkSqlFilters($filter['or'], $has_extrafields, $joins, $main_alias));
            } elseif (is_array($filter) && isset($filter['and'])) {
                $return[$field] = array('and' => $this->checkSqlFilters($filter['and'], $has_extrafields, $joins, $main_alias));
            } else {
                if (preg_match('/^(' . ($main_alias ? $main_alias : 'a') . '\.)(.+)$/', $field, $matches)) {
                    $field_name = $matches[2];
                } else {
                    $field_name = $field;
                }
                if ($this->field_exists($field_name)) {
                    if ($this->isExtraField($field_name)) {
                        $key = $this->getExtraFieldFilterKey($field_name, $joins, $main_alias);
                        if ($key) {
                            $return[$key] = $filter;
                        }
                        continue;
                    } elseif ($this->isDolExtraField($field_name)) {
                        $filter_key = ($main_alias ? $main_alias . '_' : '') . 'ef.' . $field_name;
                        $return[$filter_key] = $filter;
                        $has_extrafields = true;

                        $join_alias = ($main_alias ? $main_alias . '_' : '') . 'ef';
                        if (!isset($joins[$join_alias])) {
                            $on = $join_alias . '.fk_object = ' . ($main_alias ? $main_alias : 'a') . '.' . $this->getPrimary();
                            $joins[$join_alias] = array(
                                'alias' => $join_alias,
                                'table' => $this->getTable() . '_extrafields',
                                'on'    => $on
                            );
                        }
                        continue;
                    }

                    $filter_key = ($main_alias ? $main_alias . '_' : '') . 'a.' . $field_name;
                    $return[$filter_key] = $filter;
                    continue;
                }

                $return[$field] = $filter;
            }
        }

        return $return;
    }

    public function onSave(&$errors = array(), &$warnings = array())
    {
        
    }

    public function addConfigExtraParams()
    {
        
    }

    public function getFieldSqlKey($field, $main_alias = 'a', $child_name = null, &$joins = array(), &$errors = array(), $child_object = null)
    {
        if (!is_null($child_name) && $child_name) {
            if ($child_name === 'parent') {
                if (is_null($child_object)) {
                    $child_object = $this->getParentInstance();
                }
                $relation = 'hasOne';
            } else {
                if (is_null($child_object)) {
                    $child_object = $this->config->getObject('', $child_name);
                }
                $relation = $this->getConf('objects/' . $child_name . '/relation', 'none');
            }

            if (!is_a($child_object, 'BimpObject')) {
                $errors[] = 'Instance enfant invalide';
                return '';
            }

            $id_prop = $this->getChildIdProperty($child_name);
            if ($relation === 'hasOne' || $id_prop) {
                if (!is_string($id_prop) || !$id_prop) {
                    $errors[] = 'Propriété contenant l\'ID de l\'objet "' . $child_object->getLabel() . '" absente ou invalide';
                    return '';
                }

                if ($child_object->isDolExtraField($field)) {
                    $alias = $child_name . '_ef';
                    if (!isset($joins[$alias])) {
                        $joins[$alias] = array(
                            'table' => $child_object->getTable() . '_extrafields',
                            'on'    => $alias . '.fk_object = ' . $main_alias . '.' . $id_prop,
                            'alias' => $alias
                        );
                    }
                    return $alias . '.' . $field;
                } else {
                    $alias = $child_name;
                    if (!isset($joins[$alias])) {
                        $joins[$alias] = array(
                            'table' => $child_object->getTable(),
                            'on'    => $alias . '.' . $child_object->getPrimary() . ' = ' . $main_alias . '.' . $id_prop,
                            'alias' => $alias
                        );
                    }
                    if ($child_object->isExtraField($field)) {
                        return $child_object->getExtraFieldFilterKey($field, $joins, $alias);
                    } else {
                        return $alias . '.' . $field;
                    }
                }
            } elseif ($relation === 'hasMany') {
                if ($this->isChild($child_object)) {
                    $parent_id_prop = $child_object->getParentIdProperty();
                    if (!$parent_id_prop) {
                        $errors[] = 'Propriété de l\'ID parent absent pour l\'objet "' . $child_object->getLabel() . '"';
                    } else {
                        $alias = $child_name;
                        if (!isset($joins[$alias])) {
                            $joins[$alias] = array(
                                'table' => $child_object->getTable(),
                                'alias' => $alias,
                                'on'    => $main_alias . '.' . $this->getPrimary() . ' = ' . $alias . '.' . $parent_id_prop
                            );
                        }

                        if ($child_object->isDolExtraField($field)) {
                            $sub_alias = $child_name . '_ef';
                            if (!isset($joins[$sub_alias])) {
                                $joins[$sub_alias] = array(
                                    'table' => $child_object->getTable() . '_extrafields',
                                    'alias' => $sub_alias,
                                    'on'    => $sub_alias . '.fk_object = ' . $alias . '.' . $child_object->getPrimary()
                                );
                            }
                            return $alias . '.' . $field;
                        } elseif ($child_object->isExtraField($field)) {
                            return $child_object->getExtraFieldFilterKey($field, $joins, $child_name);
                        } else {
                            return $alias . '.' . $field;
                        }
                    }
                } else {
                    $errors[] = 'Erreur: l\'objet "' . $child_object->getLabel() . '" doit être enfant de "' . $this->getLabel() . '"';
                }
            } else {
                $errors[] = 'Type de relation invalide pour l\'objet "' . $child_object->getLabel() . '"';
                return '';
            }
        } elseif ($this->field_exists($field)) {
            if ($this->isDolExtraField($field)) {
                if (!isset($joins['ef'])) {
                    $joins['ef'] = array(
                        'table' => $this->getTable() . '_extrafields',
                        'on'    => $main_alias . '.' . $this->getPrimary() . ' = ef.fk_object',
                        'alias' => 'ef'
                    );
                }
                return 'ef.' . $field;
            } elseif ($this->isExtraField($field)) {
                return $this->getExtraFieldFilterKey($field, $joins, $main_alias);
            } else {
                return $main_alias . '.' . $field;
            }
        } else {
            $errors[] = 'Le champ "' . $field . '" n\'existe pas pour les ' . $this->getLabel('name_plur');
        }

        return '';
    }

    // Gestion des filtres custom: 

    public function getCustomFilterValueLabel($field_name, $value)
    {
        return $value;
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array())
    {
        
    }

    // Gestion des objets enfants:

    public function hasChildren($object_name)
    {
        if (!array_key_exists($object_name, $this->params['objects'])) {
            return false;
        }

        $relation = $this->getConf('objects/' . $object_name . '/relation', '', true);
        if (!$this->params['objects'][$object_name]['relation'] || $this->params['objects'][$object_name]['relation'] === 'none') {
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
                        if ($this->isChild($instance)) {
                            $filters[$instance->getParentIdProperty()] = $this->id;
                        }
                        return ($instance->getListCount($filters) > 0);
                    }
                }
        }

        return false;
    }

    public function getTaxeIdDefault()
    {
        return (int) BimpCore::getConf("tva_default");
    }

    public function getChildObject($object_name, $id_object = null)
    {
        if ($object_name === 'parent') {
            return $this->getParentInstance();
        }

        $child = $this->config->getObject('', $object_name, $id_object);

        if (!is_null($child)) {
            if (is_a($child, 'BimpObject') && BimpObject::objectLoaded($child)) {
                if ($this->isChild($child)) {
                    $child->parent = $this;
                }
            }
        }

        return $child;
    }

    public function getChildrenList($object_name, $filters = array(), $order_by = 'id', $order_way = 'asc')
    {
        $children = array();
        if ($this->isLoaded()) {
            if ($this->object_exists($object_name)) {
                $relation = $this->getConf('objects/' . $object_name . '/relation', '', true);
                if ($relation !== 'hasMany') {
                    return array();
                }

                $instance = $this->config->getObject('', $object_name);
                if (!is_null($instance)) {
                    if (is_a($instance, 'BimpObject')) {
                        $list_filters = $this->config->getCompiledParams('objects/' . $object_name . '/list/filters');
                        if (!is_null($list_filters)) {
                            foreach ($list_filters as $field => $filter) {
                                $filters = BimpTools::mergeSqlFilter($filters, $field, $filter);
                            }
                        }
                        if ($this->isChild($instance)) {
                            $filters[$instance->getParentIdProperty()] = $this->id;
                        } elseif (empty($filters)) {
                            $msg = 'Appel à getChildrenList() invalide' . "\n";
                            $msg .= 'Obj: ' . $this->object_name . ' - instance: ' . $instance->object_name . "\n";
                            $msg .= 'ERP: ' . DOL_URL_ROOT;

                            mailSyn2('ERREUR getChildren', 'f.martinez@bimp.fr', 'no-replay@bimp.fr', $msg);

                            return array();
                        }
                        $primary = $instance->getPrimary();
                        $list = $instance->getList($filters, null, null, $order_by, $order_way, 'array', array($primary));
                        foreach ($list as $item) {
                            $children[] = (int) $item[$primary];
                        }
                    }
                }
            }
        }
        return $children;
    }

    public function getChildrenListArray($object_name, $include_empty = 0, $order_by = 'id', $order_way = 'desc')
    {
        if ($this->isLoaded()) {
            $cache_key = $this->module . '_' . $this->object_name . '_' . $this->id . '_' . $object_name . '_list_array_by' . $order_by . '_' . $order_way;
            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();
                if ($this->object_exists($object_name)) {
                    $relation = $this->getConf('objects/' . $object_name . '/relation', '', true);
                    if ($relation !== 'hasMany') {
                        return array();
                    }

                    $instance = $this->config->getObject('', $object_name);
                    if (!is_null($instance)) {
                        if (is_a($instance, 'BimpObject')) {
                            if ($this->isChild($instance)) {
                                $primary = $instance->getPrimary();
                                $name_prop = $this->getNameProperty();
                                if ($name_prop) {
                                    foreach ($instance->getListByParent($this->id, null, null, $order_by, $order_way, 'array', array($primary, $name_prop)) as $item) {
                                        self::$cache[$cache_key][(int) $item[$primary]] = $item[$name_prop];
                                    }
                                }
                            } elseif (empty($filters)) {
                                $msg = 'Appel à getChildrenListArray() invalide' . "\n";
                                $msg .= 'Obj: ' . $this->object_name . ' - instance: ' . $instance->object_name . "\n";
                                $msg .= 'ERP: ' . DOL_URL_ROOT;

                                mailSyn2('ERREUR getChildren', 'f.martinez@bimp.fr', 'no-replay@bimp.fr', $msg);

                                return array();
                            }
                        }
                    }
                }
            }

            return self::getCacheArray($cache_key, $include_empty);
        }

        return array();
    }

    public function getChildrenObjects($object_name, $filters = array(), $order_by = 'id', $order_way = 'asc', $use_id_as_key = false)
    {
        $children = array();
        if ($this->isLoaded()) {
            if ($this->object_exists($object_name)) {
                $relation = $this->getConf('objects/' . $object_name . '/relation', '', true);
                if ($relation !== 'hasMany') {
                    return array();
                }

                $instance = $this->config->getObject('', $object_name);
                if (!is_null($instance)) {
                    if (is_a($instance, 'BimpObject')) {
                        $list_filters = $this->config->getCompiledParams('objects/' . $object_name . '/list/filters');
                        if (!is_null($list_filters)) {
                            foreach ($list_filters as $field => $filter) {
                                $filters = BimpTools::mergeSqlFilter($filters, $field, $filter);
                            }
                        }
                        if ($this->isChild($instance)) {
                            $filters = BimpTools::mergeSqlFilter($filters, $instance->getParentIdProperty(), $this->id);
                        } elseif (empty($filters)) {
                            $msg = 'Appel à getChildrenObjects() invalide' . "\n";
                            $msg .= 'Obj: ' . $this->object_name . ' - instance: ' . $instance->object_name . "\n";
                            $msg .= 'ERP: ' . DOL_URL_ROOT;

                            mailSyn2('ERREUR getChildren', 'f.martinez@bimp.fr', 'no-replay@bimp.fr', $msg);

                            return array();
                        }
                        $primary = $instance->getPrimary();
                        $list = $instance->getList($filters, null, null, $order_by, $order_way, 'array', array($primary));
                        foreach ($list as $item) {
                            $child = BimpCache::getBimpObjectInstance($instance->module, $instance->object_name, (int) $item[$primary], $this);
                            if (BimpObject::objectLoaded($child)) {
                                if ($use_id_as_key) {
                                    $children[(int) $child->id] = $child;
                                } else {
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

    // Getters Listes

    public function getList($filters = array(), $n = null, $p = null, $order_by = 'id', $order_way = 'DESC', $return = 'array', $return_fields = null, $joins = array(), $extra_order_by = null, $extra_order_way = 'ASC')
    {
        $table = $this->getTable();
        $primary = $this->getPrimary();

        if (is_null($table)) {
            return array();
        }

        if (preg_match('/^a\.(.+)$/', $order_by, $matches)) {
            $order_by = $matches[1];
        }

        if ($order_by === 'id') {
            $order_by = $primary;
        }

        $is_dol_object = $this->isDolObject();
        $has_extrafields = false;

        // Vérification des champs à retourner: 
        foreach ($return_fields as $key => $field) {
            if ($this->field_exists($field)) {
                if ($is_dol_object && $this->isDolExtraField($field)) {
                    if (preg_match('/^ef_(.*)$/', $field, $matches)) {
                        $field = $matches[1];
                    }
                    $return_fields[$key] = 'ef.' . $field;
                    $has_extrafields = true;
                } elseif ($this->isExtraField($field)) {
                    $field_key = $this->getExtraFieldFilterKey($field, $joins);
                    if ($field_key) {
                        $return_fields[$key] = $field_key;
                    } else {
                        unset($return_fields[$key]);
                    }
                }
            }
        }

        // Vérification des filtres: 
        $filters = $this->checkSqlFilters($filters, $has_extrafields, $joins);

        // Vérification du champ "order_by": 
        if ($this->field_exists($order_by)) {
            if ($is_dol_object && $this->isDolExtraField($order_by)) {
                $has_extrafields = true;
                $order_by = 'ef.' . $order_by;
            } elseif ($this->isExtraField($order_by)) {
                $order_by = $this->getExtraFieldFilterKey($order_by, $joins);
            }
        }

        if ($has_extrafields && !isset($joins['ef'])) {
            $joins['ef'] = array(
                'alias' => 'ef',
                'table' => $table . '_extrafields',
                'on'    => 'a.' . $primary . ' = ef.fk_object'
            );
        }

        $sql = '';
        $sql .= BimpTools::getSqlSelect($return_fields);
        $sql .= BimpTools::getSqlFrom($table, $joins);
        $sql .= BimpTools::getSqlWhere($filters);
        $sql .= BimpTools::getSqlOrderBy($order_by, $order_way, 'a', $extra_order_by, $extra_order_way);
        $sql .= BimpTools::getSqlLimit($n, $p);

//        echo $sql . '<br/><br/>'; 
//        return;
//        exit;

        if (BimpDebug::isActive('bimpcore/objects/print_list_sql') || BimpTools::isSubmit('list_sql')) {
            $plus = "";
            if (class_exists('synopsisHook'))
                $plus = ' ' . synopsisHook::getTime();
            echo BimpRender::renderDebugInfo($sql, 'SQL Liste - Module: "' . $this->module . '" Objet: "' . $this->object_name . '"' . $plus);
        }

        $rows = $this->db->executeS($sql, $return);

        if (is_null($rows)) {
            $rows = array();
        }

        return $rows;
    }

    public function getListByParent($id_parent, $n = null, $p = null, $order_by = 'id', $order_way = 'DESC', $return = 'array', $return_fields = null, $joins = null)
    {
        if (preg_match('/^a\.(.+)$/', $order_by, $matches)) {
            $order_by = $matches[1];
        }

        if ($order_by === 'id') {
            $order_by = $this->getPrimary();
        }

        $table = $this->getTable();
        $parent_id_property = $this->getParentIdProperty();

        if (!(string) $table || !(string) $parent_id_property) {
            return array();
        }

        return $this->getList(array(
                    $parent_id_property => $id_parent
                        ), $n, $p, $order_by, $order_way, $return, $return_fields, $joins);
    }

    public function getListCount($filters = array(), $joins = array())
    {
        $table = $this->getTable();
        if (is_null($table)) {
            return 0;
        }
        $primary = $this->getPrimary();

        if ($this->isDolObject()) {
            $has_extrafields = false;
            $filters = $this->checkSqlFilters($filters, $has_extrafields, $joins);
            if ($has_extrafields && !isset($joins['ef'])) {
                $joins['ef'] = array(
                    'alias' => 'ef',
                    'table' => $table . '_extrafields',
                    'on'    => 'a.' . $primary . ' = ef.fk_object'
                );
            }
        }

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

    public function getListObjects($filters = array(), $n = null, $p = null, $order_by = 'id', $order_way = 'DESC')
    {
        $primary = $this->getPrimary();

        $rows = $this->getList($filters, $n, $p, $order_by, $order_way, 'array', array($primary));

        $objects = array();

        foreach ($rows as $r) {
            $instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $r[$primary]);
            if (BimpObject::objectLoaded($instance)) {
                $objects[(int) $r[$primary]] = $instance;
            }
        }

        return $objects;
    }

    public function getListTotals($fields = array(), $filters = array(), $joins = array())
    {
        $table = $this->getTable();
        $primary = $this->getPrimary();

        if (is_null($table)) {
            return array();
        }

        $is_dol_object = $this->isDolObject();
        $has_extrafields = false;

        // Vérification des champs à retourner: 
        foreach ($fields as $key => $field) {
            if ($this->field_exists($field)) {
                if ($is_dol_object && $this->isDolExtraField($field)) {
                    if (preg_match('/^ef_(.*)$/', $field, $matches)) {
                        $field = $matches[1];
                    }
                    $fields[$key] = 'ef.' . $field;
                    $has_extrafields = true;
                } elseif ($this->isExtraField($field)) {
                    $field_key = $this->getExtraFieldFilterKey($field, $joins);
                    if ($field_key) {
                        $fields[$key] = $field_key;
                    } else {
                        unset($fields[$key]);
                    }
                }
            }
        }

        // Vérification des filtres: 
        $filters = $this->checkSqlFilters($filters, $has_extrafields, $joins);

        if ($has_extrafields && !isset($joins['ef'])) {
            $joins['ef'] = array(
                'alias' => 'ef',
                'table' => $table . '_extrafields',
                'on'    => 'a.' . $primary . ' = ef.fk_object'
            );
        }

        $sql = 'SELECT ';
        $fl = true;
        foreach ($fields as $key => $name) {
            if (!$fl) {
                $sql .= ', ';
            } else {
                $fl = false;
            }

            $sql .= 'SUM(' . $key . ') as ' . $name;
        }

        $sql .= BimpTools::getSqlFrom($table, $joins);
        $sql .= BimpTools::getSqlWhere($filters);

//        echo $sql . '<br/><br/>'; 
//        exit;

        if (BimpDebug::isActive('bimpcore/objects/print_list_sql') || BimpTools::isSubmit('list_sql')) {
            $plus = "";
            if (class_exists('synopsisHook'))
                $plus = ' ' . synopsisHook::getTime();
            echo BimpRender::renderDebugInfo($sql, 'SQL Liste Total - Module: "' . $this->module . '" Objet: "' . $this->object_name . '"' . $plus);
        }

        $rows = $this->db->executeS($sql, 'array');

        if (is_null($rows)) {
            $rows = array();
        }

        return $rows;
    }

    // Affichage des données:

    public function display($display_name = 'nom', $options = array())
    {
        $link = isset($options['link']) ? (int) $options['link'] : 0;
        $no_html = isset($options['no_html']) ? (int) $options['no_html'] : 0;
        $icon = isset($options['icon']) ? (int) $options['icon'] : 1;
        $status_icon = isset($options['status_icon']) ? (int) $options['status_icon'] : 0;
        $status_color = isset($options['status_color']) ? (int) $options['status_color'] : 0;

        $external_link_icon = isset($options['objects_icons']['external_link']) ? (int) $options['objects_icons']['external_link'] : $link;
        $modal_view_icon = isset($options['objects_icons']['modal_view']) ? (string) $options['objects_icons']['modal_view'] : ($link ? 'default' : '');

        $label = '';

        $ref = $this->getRef();
        $nom = $this->getName();

        if (!$ref) {
            $ref = '#' . $this->id;
        }
        if (!$nom) {
            $nom = BimpTools::ucfirst($this->getLabel());
        }

        switch ($display_name) {
            case 'nom':
                $label = $nom;
                break;

            case 'ref':
                $label = $ref;
                break;

            case 'ref_nom':
                $label = $ref . ' - ' . $nom;

            case 'nom_ref':
                $label = $nom . ' - ' . $ref;
                break;

            default:
                if (is_string($display_name)) {
                    $label = $display_name;
                }
        }

        if (!$label) {
            $label = $nom . ' ' . $ref;
        }

        if ($no_html) {
            return $label;
        }

        $html = '';

        $url = '';
        if ($link) {
            $url = $this->getUrl();
            if ($url) {
                $html .= '<a href="' . $url . '">';
            }
        }

        $html = '<span ';
        $status = $this->getStatus();

        if ($status_color && $status !== '') {
            
        }

        $html .= '>';

        if ($icon && $this->params['icon']) {
            $html .= BimpRender::renderIcon($this->params['icon'], 'iconLeft');
        }

        $html .= '</span>';

        if ($url) {
            $html .= '</a>';
        }

        return $html;
    }

    public function displayData($field, $display_name = 'default', $display_input_value = true, $no_html = false)
    {
        $bc_field = new BC_Field($this, $field);
        $bc_field->display_name = $display_name;
        $bc_field->display_input_value = $display_input_value;
        $bc_field->no_html = $no_html;

        $display = $bc_field->renderHtml();
        unset($bc_field);

        return $display;
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
                    if ($instance->fetch($id_associate) && isset($instance->id) && $instance->id) {
                        $prev_path = $this->config->current_path;
                        $this->config->setCurrentPath('associations/' . $association . '/display/' . $display_name);

                        $type = $this->getCurrentConf('type', null);

                        if (is_null($type)) {
                            $type = $display_name;
                        }

                        switch ($type) {
                            case 'card':
                                $card_name = $this->getCurrentConf('card', 'default');
                                if (is_a($instance, 'BimpObject')) {
                                    $card = new BC_Card($instance, null, $card_name);
                                    $html .= $card->renderHtml();
                                }
                                break;

                            case 'nom':
                                $html .= self::getInstanceNom($instance);
                                break;

                            case 'nom_url':
                                $html .= self::getInstanceNomUrl($instance);
                                break;

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
        $force_edit = (int) BimpTools::getPostFieldValue('force_edit', 0);

        $fields = $this->getConf('fields', array(), true, 'array');

        foreach ($fields as $field => $params) {
            $this->config->setCurrentPath('fields/' . $field);
            if ($this->isLoaded()) {
                if (!(int) $this->getCurrentConf('editable', 1, false, 'bool')) {
                    continue;
                }
//                $label = $this->getCurrentConf('label', $field, true);
                if (!$this->canEditField($field)) {
//                    $errors[] = 'Vous n\'avez pas la permission de modifier le champ "' . $label . '"';
                    continue;
                }
                if (!$this->isFieldEditable($field, $force_edit)) {
//                    $errors[] = 'Le champ "' . $label . '" n\'est pas modifiable';
                    continue;
                }
            }

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
            return $errors;
        }

        $label = $this->getCurrentConf('label', $field, true);

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

        $missing = false;

        if ($type === 'items_list') {
            if (!is_array($value)) {
                $delimiter = $this->getCurrentConf('items_delimiter', ',');
                $value = explode($delimiter, $value);
            }
            $item_type = $this->getCurrentConf('items_data_type', 'string');

            foreach ($value as $key => $item_value) {
                if (is_null($item_value)) {
                    $item_value = '';
                }
                if ($item_value === '' && in_array($item_type, self::$numeric_types)) {
                    $item_value = 0;
                }
                if (!$item_value && in_array($item_type, BC_Field::$missing_if_empty_types)) {
                    unset($value[$key]);
                }
            }

            if (!count($value)) {
                $missing = true;
            }
        } else {
            if (is_null($value)) {
                $value = '';
            }

            if (($value === '') && in_array($type, self::$numeric_types)) {
                $value = 0;
            }

            if (!$value) {
                if (in_array($type, BC_Field::$missing_if_empty_types)) {
                    $missing = true;
                }
            }
        }

        if ($missing && $required) {
            $errors[] = 'Valeur obligatoire manquante : "' . $label . ' (' . $field . ')"';
            return $errors;
        }

        $validate = true;
        $invalid_msg = $this->getCurrentConf('invalid_msg');

        if ($value) {
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
        }

        if (!$validate) {
            $msg = '"' . $label . '": valeur invalide : ' . $value;
            if (!is_null($invalid_msg)) {
                $msg .= ' (' . $invalid_msg . ')';
            }
            $errors[] = $msg;
        }

        if (!count($errors)) {
            $this->data[$field] = $value;
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

    public function saveFromPost()
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        $errors = $this->validatePost();
        $force_edit = (int) BimpTools::getPostFieldValue('force_edit', 0);

        if (!count($errors)) {
            if ($this->isLoaded()) {
                $errors = $this->update($warnings, $force_edit);
                if (!count($errors)) {
                    $success = 'Mise à jour ' . $this->getLabel('of_the') . ' effectuée avec succès';
                    if (method_exists($this, 'getUpdateJsCallback')) {
                        $success_callback = $this->getUpdateJsCallback();
                    }
                }
            } else {
                $errors = $this->create($warnings, $force_edit);
                if (!count($errors)) {
                    $success = 'Création ' . $this->getLabel('of_the') . ' effectuée avec succès';
                    if (method_exists($this, 'getCreateJsCallback')) {
                        $success_callback = $this->getCreateJsCallback();
                    }
                }
            }
        }

        if (!count($errors)) {
            $warnings = array_merge($warnings, $this->saveAssociationsFromPost());
            $sub_result = $this->checkSubObjectsPost($force_edit);
            if (count($sub_result['errors'])) {
                $warnings = array_merge($warnings, $sub_result['errors']);
            }
            if ($sub_result['success_callback']) {
                $success_callback .= $sub_result['success_callback'];
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success'          => $success,
            'success_callback' => $success_callback
        );
    }

    public function checkSubObjectsPost($force_edit = false)
    {
        $errors = array();
        $success_callback = '';
        if ($this->isLoaded()) {
            $objects = explode(',', BimpTools::getValue('sub_objects', ''));
            foreach ($objects as $object_name) {
                $object = $this->getChildObject($object_name);
                if (is_a($object, 'BimpObject')) {
                    $parent_id_property = '';
                    if ($this->isChild($object)) {
                        $parent_id_property = $object->getParentIdProperty();
                    }
                    $multiple = BimpTools::getValue($object_name . '_multiple', 0);
                    $post_temp = $_POST;
                    $files_temp = $_FILES;
                    if ($multiple) {
                        $count = BimpTools::getValue($object_name . '_count', 0);
                        for ($i = 1; $i <= $count; $i++) {
                            $_POST = array();
                            $_FILES = array();

                            foreach ($post_temp as $key => $value) {
                                if (preg_match('/^' . $object_name . '_' . $i . '_(.*)$/', $key, $matches)) {
                                    $_POST[$matches[1]] = $value;
                                }
                            }
                            foreach ($files_temp as $key => $value) {
                                if (preg_match('/^' . $object_name . '_' . $i . '_(.*)$/', $key, $matches)) {
                                    $_FILES[$matches[1]] = $value;
                                }
                            }
                            if (count($_POST)) {
                                if ($parent_id_property) {
                                    $_POST[$parent_id_property] = $this->id;
                                }

                                if ($force_edit) {
                                    $_POST['force_edit'] = 1;
                                }

                                $new_object = BimpObject::getInstance($object->module, $object->object_name);
                                $result = $new_object->saveFromPost();
                                $sub_errors = array_merge($result['errors'], $result['warnings']);
                                if ($sub_errors) {
                                    $errors[] = BimpTools::getMsgFromArray($sub_errors, 'Des erreurs sont survenues lors de la création ' . $object->getLabel('of_the') . ' n° ' . $i);
                                }
                                if ($result['success_callback']) {
                                    $success_callback .= $result['success_callback'];
                                }
                            }
                        }
                    } else {
                        $_POST = array();
                        if ($force_edit) {
                            $_POST['force_edit'] = 1;
                        }
                        foreach ($post_temp as $key => $value) {
                            if (preg_match('/^' . $object_name . '_(.*)$/', $key, $matches)) {
                                $_POST[$matches[1]] = $value;
                            }
                        }
                        if (count($_POST)) {
                            if ($parent_id_property) {
                                $_POST[$parent_id_property] = $this->id;
                            }
                            $new_object = BimpObject::getInstance($object->module, $object->object_name);
                            $result = $new_object->saveFromPost();
                            $sub_errors = array_merge($result['errors'], $result['warnings']);
                            if ($sub_errors) {
                                $errors[] = BimpTools::getMsgFromArray($sub_errors, 'Des erreurs sont survenues lors de la création ' . $object->getLabel('of_the'));
                            }
                            if (count($result['warnings'])) {
                                $errors = array_merge($errors, $result['warnings']);
                            }
                            if ($result['success_callback']) {
                                $success_callback = $result['success_callback'];
                            }
                        }
                    }
                    $_POST = $post_temp;
                    $_FILES = $files_temp;
                }
            }
        }
        return array(
            'errors'           => $errors,
            'success_callback' => $success_callback
        );
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $this->noFetchOnTrigger = true;
        BimpLog::actionStart('bimpobject_create', 'Création', $this);

        $errors = array();

        if (!$force_create && !$this->can("create")) {
            $errors[] = 'Vous n\'avez pas la permission de créer ' . $this->getLabel('a');
        }

        if (!$this->isCreatable($force_create)) {
            $errors[] = 'Il n\'est pas possible de créer ' . $this->getLabel('a');
        }

        if (!count($errors)) {
            $errors = $this->validate();

            if (!count($errors)) {
                if ($this->use_commom_fields) {
                    $dc = $this->getData('date_create');
                    if (is_null($dc) || !$dc) {
                        $this->data['date_create'] = date('Y-m-d H:i:s');
                    }

                    $uc = (int) $this->getData('user_create');
                    if (is_null($uc) || !$uc) {
                        global $user;
                        if (isset($user->id)) {
                            $uc = (int) $user->id;
                        } else {
                            $uc = 0;
                        }
                    }
                    $this->set('user_create', $uc);
                }

                if (!is_null($this->dol_object)) {
                    $result = $this->createDolObject($errors, $warnings);
                } else {
                    $table = $this->getTable('');

                    if (is_null($table)) {
                        $errors[] = 'Fichier de configuration invalide (table non renseignée)';
                        $result = 0;
                    } else {
                        $result = $this->db->insert($table, $this->getDbData(), true);
                    }
                }

                if ($result > 0) {
                    $this->id = (int) $result;
                    $this->set($this->getPrimary(''), (int) $result);

                    $extra_errors = $this->insertExtraFields();
                    if (count($extra_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($extra_errors, 'Des erreurs sont survenues lors de l\'enregistrement des champs supplémentaires');
                    }

                    self::setBimpObjectInstance($this);

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

                    $this->initData = $this->data;

                    $warnings = array_merge($warnings, $this->updateAssociations());
                    $warnings = array_merge($warnings, $this->saveHistory());

                    $parent = $this->getParentInstance();
                    if (!is_null($parent)) {
                        if (method_exists($parent, 'onChildSave')) {
                            $parent->onChildSave($this);
                        }
                    }

                    $this->onSave($errors, $warnings);

                    if (static::$check_on_create) {
                        $this->checkObject('create');
                    }
                } else {
                    $msg = 'Echec de l\'enregistrement ' . $this->getLabel('of_the');
                    $sqlError = $this->db->db->lasterror;
                    if ($sqlError) {
                        $msg .= ' - Erreur SQL: ' . $sqlError;
                    }
                    $errors[] = $msg;
                }
            }
        }

        BimpLog::actionData($this->getDataArray());
        BimpLog::actionEnd('bimpobject_create', $errors, $warnings);

        $this->noFetchOnTrigger = false;
        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $this->noFetchOnTrigger = true;

        BimpLog::actionStart('bimpobject_update', 'Mise à jour', $this);

        $errors = array();

        if (!$force_update && !$this->can("edit")) {
            $errors[] = 'Vous n\'avez pas la permission de modifier ' . $this->getLabel('this');
        }

        if (!$this->isEditable($force_update)) {
            $errors[] = 'Il n\'est pas possible de modifier ' . $this->getLabel('this');
        }

        if (!count($errors)) {
            if (!$this->isLoaded()) {
                $errors[] = 'ID ' . $this->getLabel('of_the') . ' Absent';
            } else {
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

                    if ($this->isDolObject()) {
                        $result = $this->updateDolObject($errors, $warnings);
                    } else {
                        $table = $this->getTable();
                        $primary = $this->getPrimary();

                        if (is_null($table)) {
                            $errors[] = 'Fichier de configuration invalide (table non renseignée)';
                            $result = 0;
                        } else {
                            $result = $this->db->update($table, $this->getDbData(), '`' . $primary . '` = ' . (int) $this->id);
                        }
                    }

                    if ($result <= 0) {
                        $msg = 'Echec de la mise à jour ' . $this->getLabel('of_the');
                        $sqlError = $this->db->db->lasterror;
                        if ($sqlError) {
                            $msg .= ' - Erreur SQL: ' . $sqlError;
                        }
                        $errors[] = $msg;
                    } else {
                        $extra_errors = $this->updateExtraFields();
                        if (count($extra_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($extra_errors, 'Des erreurs sont survenues lors de l\'enregistrement des champs supplémentaires');
                        }

                        $this->initData = $this->data;
                        self::setBimpObjectInstance($this);

                        $warnings = array_merge($warnings, $this->updateAssociations());
                        $warnings = array_merge($warnings, $this->saveHistory());

                        $parent = $this->getParentInstance();

                        if (!is_null($parent)) {
                            if (method_exists($parent, 'onChildSave')) {
                                $warnings = array_merge($warnings, $parent->onChildSave($this));
                            }
                        }

                        $this->onSave($errors, $warnings);
                        if (static::$check_on_update) {
                            $this->checkObject('update');
                        }
                    }
                }
            }
        }

        BimpLog::actionData($this->getDataArray());
        BimpLog::actionEnd('bimpobject_update', $errors, $warnings);

        $this->noFetchOnTrigger = false;

        return $errors;
    }

    public function updateAssociations()
    {
        $errors = array();
        if (!$this->isLoaded()) {
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

    public function updateField($field, $value, $id_object = null, $force_update = true, $do_not_validate = false)
    {
        BimpLog::actionStart('bimpobject_update_field', 'Mise à jour du champ "' . $field . '"', $this);
        BimpLog::actionData($value);

        if (is_null($id_object) || !$id_object) {
            if ($this->isLoaded()) {
                $id_object = (int) $this->id;
            }
        }

        $errors = array();

        if (is_null($id_object) || !$id_object) {
            $errors[] = 'Impossible de mettre à jour le champ "' . $field . '" - ID ' . $this->getLabel('of_the') . ' absent';
        } elseif ($this->field_exists($field)) {
            if (!$force_update) {
                if (!$this->canEditField($field)) {
                    $errors[] = 'Vous n\'avez pas la permission de modifier ce champ';
                }
                if ($this->isFieldEditable($field)) {
                    $errors[] = 'Ce champ n\'est pas éditable';
                }
            }
            if (!$do_not_validate) {
                $errors = array_merge($errors, $this->validateValue($field, $value));
            } else {
                $this->data[$field] = $value;
            }
            if (!count($errors)) {
                $value = $this->getData($field);
                $db_value = $value;

                $data_type = $this->getConf('fields/' . $field . '/type', 'string');

                if ($data_type === 'items_list') {
                    if (is_array($value)) {
                        $delimiter = $this->getConf('items_delimiter', ',');
                        $db_value = implode($delimiter, $value);
                    }
                }

                if ($this->isDolExtraField($field)) {
                    // Cas d'un dol extrafield: 
                    if ($this->db->update($this->getTable() . '_extrafields', array(
                                $field => $db_value
                                    ), '`fk_object` = ' . (int) $id_object) <= 0) {
                        $sqlError = $this->db->db->lasterror();
                        $errors[] = 'Echec de la mise à jour du champ "' . $field . '"' . ($sqlError ? ' - ' . $sqlError : '');
                    }
                } elseif ($this->isExtraField($field)) {
                    // Cas d'un BimpObject extra field: 
                    $errors = array_merge($errors, $this->updateExtraField($field, $db_value, $id_object));
                } else {
                    // Cas d'un field ordinaire: 
                    $table = $this->getTable();
                    $primary = $this->getPrimary();

                    if ($table && $primary) {
                        if ($this->db->update($table, array(
                                    $field => $db_value
                                        ), '`' . $primary . '` = ' . (int) $id_object) <= 0) {
                            $sqlError = $this->db->db->lasterror();
                            $errors[] = 'Echec de la mise à jour du champ "' . $field . '"' . ($sqlError ? ' - ' . $sqlError : '');
                        }
                    } else {
                        $errors[] = 'Erreur de configuration: paramètres de la table invalides';
                    }
                }

                if (!count($errors)) {
                    $this->initData[$field] = $this->data[$field];
                    if ($this->getConf('fields/' . $field . '/history', false, false, 'bool')) {
                        // Mise à jour de l'historique du champ: 
                        global $user;
                        $history = BimpObject::getInstance('bimpcore', 'BimpHistory');
                        $history->validateArray(array(
                            'module'    => $this->module,
                            'object'    => $this->object_name,
                            'id_object' => (int) $id_object,
                            'field'     => $field,
                            'value'     => $db_value,
                            'date'      => date('Y-m-d H:i:s'),
                            'id_user'   => (int) $user->id,
                        ));
                        $warnings = array();
                        $history->create($warnings, true);
                    }

                    $parent = $this->getParentInstance();

                    if (!is_null($parent)) {
                        // Trigger sur le parent: 
                        if (method_exists($parent, 'onChildSave')) {
                            $warnings = array_merge($warnings, $parent->onChildSave($this));
                        }
                    }

                    $this->onSave($errors, $warnings);

                    if (static::$check_on_update_field) {
                        $this->checkObject('updateField', $field);
                    }
                }
            }
        } else {
            $errors[] = 'Le champ "' . $field . '" n\'existe pas';
        }

        BimpLog::actionEnd('bimpobject_update_field', $errors);

        return $errors;
    }

    public function updateFields($fields_values, $force_update = true, &$warnings = array())
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $update = false;

        foreach ($fields_values as $field => $value) {
            if ($this->field_exists($field)) {
                if ($value != $this->getData($field)) {
                    $this->set($field, $value);
                    $update = true;
                }
            }
        }

        if ($update) {
            $errors = $this->update($warnings, $force_update);
        }

        return $errors;
    }

    public function find($filters, $return_first = false, $delete_if_multiple = false)
    {
        $this->reset();

        $id_object = null;

        $joins = array();
        $table = $this->getTable();
        $primary = $this->getPrimary();

        $hasExtraFields = false;
        $filters = $this->checkSqlFilters($filters, $hasExtraFields, $joins);

        $sql = BimpTools::getSqlSelect('a.' . $primary);
        $sql .= BimpTools::getSqlFrom($table, $joins);
        $sql .= BimpTools::getSqlWhere($filters);

        $rows = $this->db->executeS($sql, 'array');

        if (!is_null($rows) && count($rows)) {
            if (count($rows) > 1 && !$return_first) {
                if ($delete_if_multiple) {
                    $fl = true;
                    foreach ($rows as $r) {
                        if ($fl) {
                            $fl = false;
                            if ($return_first) {
                                continue;
                            }
                        }
                        if ($this->fetch((int) $r[$primary])) {
                            $this->delete();
                        }
                    }
                    $this->reset();
                }
                if (!$return_first) {
                    return false;
                }
            }
            $id_object = (int) $rows[0][$primary];
        }

        if (is_null($id_object) || !$id_object) {
            return false;
        }

        return $this->fetch($id_object);
    }

    public function fetch($id, $parent = null)
    {
        global $main_controller;

        if (is_a($main_controller, 'BimpController')) {
            $main_controller->addDebugTime('Fetch ' . $this->getLabel() . ' - ID ' . $id);
        }

        $this->reset();

        if (!is_null($parent)) {
            $this->parent = $parent;
        }

        if (!is_null($this->dol_object)) {
            return $this->fetchDolObject($id);
        }

        $table = $this->getTable();
        $primary = $this->getPrimary();

        if (is_null($table) || !$table) {
            return false;
        }

        $row = $this->db->getRow($table, '`' . $primary . '` = ' . (int) $id);

        if (!is_null($row)) {
            foreach ($row as $field => $value) {
                if ($field === $primary) {
                    $this->id = (int) $value;
                } elseif ($this->field_exists($field)) {
                    $this->checkFieldValueType($field, $value);
                    $this->data[$field] = $value;
                }
            }

            $extra_fields = $this->fetchExtraFields();

            foreach ($extra_fields as $field_name => $value) {
                $this->checkFieldValueType($field_name, $value);
                $this->data[$field_name] = $value;
            }

            $this->initData = $this->data;
            $this->ref = $this->getRef();

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

    public function delete(&$warnings = array(), $force_delete = false)
    {
        BimpLog::actionStart('bimpobject_delete', 'Suppression', $this);

        self::setBimpObjectInstance($this);

        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } elseif (!$force_delete && !$this->can("delete")) {
            $errors[] = 'Vous n\'avez pas la permission de supprimer ' . $this->getLabel('this');
        } elseif (!$this->isDeletable($force_delete, $errors)) {
            if (empty($errors)) {
                $errors[] = 'Il n\'est pas possible de supprimer ' . $this->getLabel('this');
            }
        }

        if (count($errors)) {
            return $errors;
        }

        $parent = $this->getParentInstance();

        // Suppression de la ligne en base: 
        if (method_exists($this, 'deleteProcess')) {
            $result = $this->deleteProcess();
        } elseif (!is_null($this->dol_object)) {
            $result = $this->deleteDolObject($errors);
        } else {
            $table = $this->getTable();
            $primary = $this->getPrimary();

            if (!$table || !$primary) {
                $errors[] = 'Erreur de configuration: paramètre de la table invalides';
                $result = 0;
            } else {
                $result = $this->db->delete($table, '`' . $primary . '` = ' . (int) $this->id);
            }
        }

        if ($result <= 0) {
            $msg = 'Echec de la suppression ' . $this->getLabel('of_the');
            $sqlError = $this->db->db->lasterror;
            if ($sqlError) {
                $msg .= ' - Erreur SQL: ' . $sqlError;
            }
            $errors[] = $msg;
        } elseif (!count($errors)) {
            $id = $this->id;

            // Suppr des extras fields: 
            $extra_errors = $this->deleteExtraFields();
            if (count($extra_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($extra_errors, 'Des erreurs sont survenues lors de la suppression des champs supplémentaires');
            }

            // Réintialisation de la position des autres objets du même parent: 
            if ((int) $this->params['positions']) {
                $this->resetPositions();
            }

            // Suppression des objets enfants: 
            $objects = $this->getConf('objects', array(), true, 'array');
            if (!is_null($objects)) {
                $prev_path = $this->config->current_path;
                foreach ($objects as $name => $params) {
                    $this->config->setCurrentPath('objects/' . $name);
                    if ((int) $delete = $this->getCurrentConf('delete', 0, false, 'bool')) {
                        $instance = $this->config->getObject('', $name);
                        if (!is_null($instance) && is_a($instance, 'BimpObject')) {
                            $del_errors = array();
                            if ($this->isChild($instance)) {
                                if (!$instance->deleteByParent($id, $del_errors, true)) {
                                    $msg = 'Des erreurs sont survenues lors de la tentative de suppression des ';
                                    $msg .= $this->getInstanceLabel($instance, 'name_plur');
                                    $warnings[] = BimpTools::getMsgFromArray($del_errors, $msg);
                                }
                            } else {
                                $relation = $this->getCurrentConf('relation', '');
                                if ($relation === 'hasOne') {
                                    $field_name = $this->getCurrentConf('instance/id_object/field_value', null);
                                    if (!is_null($field_name) && $field_name) {
                                        if ($instance->fetch((int) $this->getData($field_name))) {
                                            $instance_warnings = array();
                                            $del_errors = $instance->delete($instance_warnings, true);
                                            if (count($del_errors)) {
                                                $msg = 'Des erreurs sont survenues lors de la tentative de suppression ';
                                                $msg .= $this->getInstanceLabel($instance, 'of_the') . ' d\'ID ' . $this->getData($field_name);
                                                $warnings[] = BimpTools::getMsgFromArray($del_errors, $msg);
                                            }
                                            if (count($instance_warnings)) {
                                                $warnings[] = BimpTools::getMsgFromArray($instance_warnings, BimpTools::ucfirst($instance->getLabel() . ' ' . (int) $this->getData($field_name)));
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $this->config->setCurrentPath($prev_path);
            }

            // Suppressions des associations: 
            $associations = $this->getConf('associations', null, false, 'array');
            if (!is_null($associations)) {
                $prev_path = $this->config->current_path;
                foreach ($associations as $name => $params) {
                    $bimpAsso = new BimpAssociation($this, $name);
                    $asso_errors = $bimpAsso->deleteAllObjectAssociations($id);
                    if (count($asso_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($asso_errors, 'Echec de la suppression des associations de type "' . $name . '"');
                    }
                }
                $this->config->setCurrentPath($prev_path);
            }

            // Suppression de l'historique des champs: 
            $bimpHistory = BimpObject::getInstance('bimpcore', 'BimpHistory');
            $bimpHistory->deleteByObject($this, $id);

            // Trigger sur le parent:
            if (BimpObject::objectLoaded($parent)) {
                if (method_exists($parent, 'onChildDelete')) {
                    $parent->onChildDelete($this);
                }
            }

            // Suppression de l'objet du cache: 
            self::unsetBimpObjectInstance($this->module, $this->object_name, $id);

            // Réinitialisation de l'instance:
            $this->reset();
        }

        BimpLog::actionEnd('bimpobject_delete', $errors, $warnings);
        return $errors;
    }

    public function deleteByParent($id_parent, &$errors = array(), $force_delete = false)
    {
        if (is_null($id_parent) || !$id_parent) {
            return false;
        }

        $parent_id_property = $this->getParentIdProperty();

        if (!is_null($parent_id_property) && $parent_id_property) {
            return self::deleteBy(array(
                        $parent_id_property => (int) $id_parent
                            ), $errors, $force_delete);
        }

        $errors[] = 'Erreur technique: propriété contenant l\'ID du parent absente';
    }

    public function deleteBy($filters, &$errors = array(), $force_delete = false)
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
                $instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $item->id);
                if ($instance->isLoaded()) {
                    $del_warnings = array();
                    $del_errors = $instance->delete($del_warnings, $force_delete);
                    if (count($del_errors)) {
                        $check = false;
                        $errors[] = BimpTools::getMsgFromArray($del_errors, 'Echec de la suppression ' . $this->getLabel('of_the') . ' d\'ID ' . $item->id);
                    }
                    if (count($del_warnings)) {
                        $check = false;
                        $errors[] = BimpTools::getMsgFromArray($del_warnings, 'Des erreurs sont survenues suite à la suppression ' . $this->getLabel('of_the') . ' d\'ID ' . $item->id);
                    }
                }
            }
        }
        return $check;
    }

    // Gestion DolObjects: 

    public function hydrateDolObject(&$bimpObjectFields = array())
    {
        $errors = array();

        foreach ($this->data as $field => $value) {
            if ($this->field_exists($field)) {
                if ((int) $this->getConf('fields/' . $field . '/dol_extra_field', 0, false, 'bool')) {
                    if (preg_match('/^ef_(.*)$/', $field, $matches)) {
                        $extrafield = $matches[1];
                    } else {
                        $extrafield = $field;
                    }
                    $this->dol_object->array_options['options_' . $extrafield] = $this->getDolValue($field, $value);
                } else {
                    $prop = $this->getConf('fields/' . $field . '/dol_prop', $field);
                    if (is_null($prop)) {
                        $errors[] = 'Erreur de configuration: propriété de l\'objet Dolibarr non définie pour le champ "' . $field . '"';
                    } if (property_exists($this->dol_object, $prop)) {
                        $this->dol_object->{$prop} = $this->getDolValue($field, $value);
                    } elseif ($this->field_exists($field) && !$this->isExtraField($field)) {
                        $bimpObjectFields[$field] = $value;
                    }
                }
            }
        }

        return $errors;
    }

    public function hydrateFromDolObject(&$bimpObjectFields = array())
    {
        if (!self::objectLoaded($this->dol_object)) {
            return array('Echec de la récupération des données depuis l\'objet Dolibarr - objet non chargé');
        }

        $errors = array();

        if (!isset($this->dol_object->id) && isset($this->dol_object->rowid))
            $this->dol_object->id = $this->dol_object->rowid;

        $this->id = $this->dol_object->id;

        foreach ($this->params['fields'] as $field) {
            $value = null;
            if ((int) $this->getConf('fields/' . $field . '/dol_extra_field', 0, false, 'bool')) {
                if (preg_match('/^ef_(.*)$/', $field, $matches)) {
                    $extrafield = $matches[1];
                } else {
                    $extrafield = $field;
                }
                if (isset($this->dol_object->array_options['options_' . $extrafield])) {
                    $value = $this->dol_object->array_options['options_' . $extrafield];
                }
            } else {
                $prop = $this->getConf('fields/' . $field . '/dol_prop', $field);
                if (is_null($prop)) {
                    $errors[] = 'Erreur de configuration: propriété de l\'objet Dolibarr non définie pour le champ "' . $field . '"';
                } elseif (property_exists($this->dol_object, $prop)) {
                    $value = $this->dol_object->{$prop};
                } elseif ($this->field_exists($field) && !$this->isExtraField($field)) {
                    $bimpObjectFields[] = $field;
                }
            }
            if (!is_null($value)) {
                $this->checkFieldValueType($field, $value);
                $this->data[$field] = $value;
            }
        }

        return $errors;
    }

    protected function createDolObject(&$errors = array(), &$warnings = array())
    {
        $this->noFetchOnTrigger = true;

        if (!is_null($this->dol_object) && isset($this->dol_object->id) && $this->dol_object->id) {
            unset($this->dol_object);
            $this->dol_object = $this->config->getObject('dol_object');
        }

        if (is_null($this->dol_object)) {
            $errors[] = 'Objet Dolibarr invalide';
            $this->noFetchOnTrigger = false;
            return 0;
        }

        $bimpObjectFields = array();
        $errors = $this->hydrateDolObject($bimpObjectFields);

        if (!count($errors)) {
            if (method_exists($this, 'beforeCreateDolObject')) {
                $this->beforeCreateDolObject();
            }

            if (method_exists($this, 'getDolObjectCreateParams')) {
                $params = $this->getDolObjectCreateParams();
            } else {
                global $user;
                $params = array($user);
            }

            $result = call_user_func_array(array($this->dol_object, 'create'), $params);
            if ($result <= 0) {
                if (isset($this->dol_object->error) && $this->dol_object->error) {
                    $errors[] = $this->dol_object->error;
                } elseif (count($this->dol_object->errors)) {
                    global $langs;
                    $langs->load("errors");
                    foreach ($this->dol_object->errors as $error) {
                        $errors[] = 'Erreur: ' . $langs->trans($error);
                    }
                }
            } else {
                if (!empty($bimpObjectFields)) {
                    $fields = array();
                    foreach ($bimpObjectFields as $field_name => $value) {
                        $fields[] = $field_name;
                    }

                    $data = $this->getDbData($fields);

                    if (!empty($data)) {
                        $up_result = $this->db->update($this->getTable(), $data, '`' . $this->getPrimary() . '` = ' . (int) $result);

                        if ($up_result <= 0) {
                            $msg = 'Echec de l\'insertion des champs additionnels';
                            $sql_errors = $this->db->db->lasterror;
                            if ($sql_errors) {
                                $msg .= ' - Erreur SQL: ' . $sql_errors;
                            }

                            $warnings[] = $msg;
                        }
                    }
                }
            }

            $this->noFetchOnTrigger = false;
            return $result;
        }

        $this->noFetchOnTrigger = false;
        return 0;
    }

    protected function updateDolObject(&$errors = array(), &$warnings = array())
    {
        $this->noFetchOnTrigger = true;
        if (!$this->isLoaded()) {
            $this->noFetchOnTrigger = false;
            return 0;
        }
        if (is_null($this->dol_object)) {
            $errors[] = 'Objet Dolibarr invalide';
            $this->noFetchOnTrigger = false;
            return 0;
        }

        if (!isset($this->dol_object->id) || !$this->dol_object->id) {
            $errors[] = 'Objet Dolibarr invalide';
            $this->noFetchOnTrigger = false;
            return 0;
        }

        $bimpObjectFields = array();
        $errors = $this->hydrateDolObject($bimpObjectFields);

        if (!count($errors)) {
            if (method_exists($this, 'beforeUpdateDolObject')) {
                $this->beforeUpdateDolObject();
            }

            if (method_exists($this, 'getDolObjectUpdateParams')) {
                $params = $this->getDolObjectUpdateParams();
            } else {
                global $user;
                $params = array($user);
            }

            $result = call_user_func_array(array($this->dol_object, 'update'), $params);

            if ((int) $this->params['force_extrafields_update']) {
                foreach ($this->dol_object->array_options as $key => $value) {
                    if ($this->dol_object->updateExtraField(str_replace('options_', '', $key)) <= 0) {
                        $warnings[] = 'Echec de l\'enregistrement de l\'attribut supplémentaire "' . str_replace('options_', '', $key) . '"';
                    }
                }
            }

            if ($result < 0) {
                if (isset($this->dol_object->error) && $this->dol_object->error) {
                    $errors[] = $this->dol_object->error;
                } elseif (count($this->dol_object->errors)) {
                    global $langs;
                    $langs->load("errors");
                    foreach ($this->dol_object->errors as $error) {
                        $errors[] = 'Erreur: ' . $langs->trans($error);
                    }
                }
                $this->noFetchOnTrigger = false;
                return 0;
            } else {
                if (!empty($bimpObjectFields)) {
                    $fields = array();
                    foreach ($bimpObjectFields as $field_name => $value) {
                        $fields[] = $field_name;
                    }

                    $data = $this->getDbData($fields);

                    if (!empty($data)) {
                        $up_result = $this->db->update($this->getTable(), $this->getDbData($fields), '`' . $this->getPrimary() . '` = ' . (int) $this->id);

                        if ($up_result <= 0) {
                            $msg = 'Echec de la mise à jour des champs additionnels';
                            $sql_errors = $this->db->db->lasterror;
                            if ($sql_errors) {
                                $msg .= ' - Erreur SQL: ' . $sql_errors;
                            }

                            $warnings[] = $msg;
                        }
                    }
                }
            }
            $this->noFetchOnTrigger = false;
            return 1;
        }

        $this->noFetchOnTrigger = false;
        return 0;
    }

    protected function fetchDolObject($id, &$errors = array())
    {
        if (!is_null($this->dol_object) && isset($this->dol_object->id) && $this->dol_object->id) {
            unset($this->dol_object);
            $this->dol_object = $this->config->getObject('dol_object');
        }

        if (is_null($this->dol_object)) {
            $errors[] = 'Objet Dolibarr invalide';
            return false;
        }

        if (method_exists($this, 'getDolObjectFetchParams')) {
            $params = $this->getDolObjectFetchParams($id);
        } else {
            $params = array($id);
        }

        $result = call_user_func_array(array($this->dol_object, 'fetch'), $params);


        if ($result <= 0) {
            if (isset($this->dol_object->error) && $this->dol_object->error) {
                $errors[] = $this->dol_object->error;
            }

            return false;
        }

        $bimpObjectFields = array();

        $errors = $this->hydrateFromDolObject($bimpObjectFields);

        if (!empty($bimpObjectFields)) {
            $result = $this->db->getRow($this->getTable(), '`' . $this->getPrimary() . '` = ' . (int) $id, $bimpObjectFields, 'array');
            if (!is_null($result)) {
                foreach ($bimpObjectFields as $field_name) {
                    if (!isset($result[$field_name])) {
                        continue;
                    }
                    $value = $result[$field_name];
                    $this->checkFieldValueType($field_name, $value);
                    $this->data[$field_name] = $value;
                }
            }
        }

        $extra_fields = $this->fetchExtraFields();

        foreach ($extra_fields as $field_name => $value) {
            $this->checkFieldValueType($field_name, $value);
            $this->data[$field_name] = $value;
        }

        $this->initData = $this->data;

        if (!count($errors)) {
            return true;
        }
        return false;
    }

    protected function deleteDolObject(&$errors)
    {
        if (is_null($this->dol_object) || !isset($this->dol_object->id) || !$this->dol_object->id) {
            $errors[] = 'Objet Dolibarr invalide';
            return 0;
        }

        if (method_exists($this, 'getDolObjectDeleteParams')) {
            $params = $this->getDolObjectDeleteParams();
        } else {
            global $user;
            $params = array($user);
        }

        $result = call_user_func_array(array($this->dol_object, 'delete'), $params);

        if ($result <= 0) {
            if (isset($this->dol_object->error) && $this->dol_object->error) {
                $errors[] = $this->dol_object->error;
            }

            return 0;
        }

        return 1;
    }

    // Gestion Fields Extra: 

    public function insertExtraFields()
    {
        // Enregistrer tous les extrafields (create) 
        // A ce stade l'ID est déjà assigné à l'objet
        // retourner un tableau d'erreurs. 

        if (count($this->getExtraFields())) {
            return array('Fonction d\'enregistrement des champs supplémentaires non implémentée');
        }

        return array();
    }

    public function updateExtraFields()
    {
        // Mettre à jour tous les extrafields
        // retourner un tableau d'erreurs. 

        if (count($this->getExtraFields())) {
            return array('Fonction d\'enregistrement des champs supplémentaires non implémentée');
        }

        return array();
    }

    public function updateExtraField($field_name, $value, $id_object)
    {
        // enregistrer la valeur $value pour le champ extra: $field_name et l'ID $id_object (Ne pas tenir compte de $this->id). 
        // Retourner un tableau d'erreurs.

        if ($this->isExtraField($field_name)) {
            return array('Fonction d\'enregistrement des champs supplémentaires non implémentée');
        }

        return array();
    }

    public function fetchExtraFields()
    {
        // Fetcher tous les extrafields
        // retourner les valeurs dans un tableau ($field_name => $value) 
        // Ne pas assigner les valeurs directement dans $this->data. D'autres vérifs sont faites ultérieurement de manière générique.

        return array();
    }

    public function deleteExtraFields()
    {
        // Supprimer les extrafields
        // Retourner un tableau d'erreurs

        if (count($this->getExtraFields())) {
            return array('Fonction de suppression des champs supplémentaires non implémentée');
        }

        return array();
    }

    public function getExtraFieldSavedValue($field, $id_object)
    {
        // retourner la valeur actuelle en base pour le champ $field et l'ID objet $id_object (Ici, ne pas tenir compte de $this->id). 
        // Retourner null si pas d'entrée en base. 

        return null;
    }

    public function getExtraFieldFilterKey($field, &$joins, $main_alias = '')
    {
        // Retourner la clé de filtre SQl sous la forme alias_table.nom_champ_db 
        // Implémenter la jointure dans $joins en utilisant l'alias comme clé du tableau (pour éviter que la même jointure soit ajouté plusieurs fois à $joins). 
        // Si $main_alias est défini, l'utiliser comme préfixe de alias_table. Ex: $main_alias .'_'.$alias_table (Bien utiliser l'underscore).  
        // ET: utiliser $main_alias à la place de "a" dans la clause ON. 
//        Ex: 
//        $join_alias = ($main_alias ? $main_alias . '_' : '') . 'xxx';
//        $joins[$join_alias] = array(
//            'alias' => $join_alias,
//            'table' => 'nom_table',
//            'on'    => $join_alias . '.xxx = ' . ($main_alias ? $main_alias : 'a') . '.xxx'
//        );
//        
//        return $join_alias.'.nom_champ_db';

        return '';
    }

    // Gestion des autorisations: 

    public function isParentEditable()
    {
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent)) {
            if (is_a($parent, 'BimpObject')) {
                return (int) $parent->isEditable();
            }

            return 1;
        }

        return 0;
    }

    public function isCreatable($force_create = false, &$errors = array())
    {
        return 1;
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        return 1;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return $this->isEditable();
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        return $this->isEditable($force_edit);
    }

    public function isFormAllowed($form_name, &$errors = array())
    {
        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        return 1;
    }

    public function isNewStatusAllowed($new_status, &$errors = array())
    {
        return 1;
    }

    public function areNotesEditable()
    {
        return 1;
    }

    // Gestion des droits users: 

    public function can($right)
    {
        switch ($right) {
            case "view" :
                if (BimpTools::getContext() == "public")
                    return ($this->canView() && $this->canClientView());
                else
                    return $this->canView();
            case 'edit' :
                if (BimpTools::getContext() == "public")
                    return ($this->canEdit() && $this->canClientEdit());
                else
                    return $this->canEdit();
            case "create" :
                if (BimpTools::getContext() == "public")
                    return ($this->canCreate() && $this->canClientCreate());
                else
                    return $this->canCreate();
            case "delete" :
                if (BimpTools::getContext() == "public")
                    return ($this->canDelete() && $this->canClientDelete());
                else
                    return $this->canDelete();

            default:
                return 0;
        }
    }

    public function canCreate()
    {
        if ($this->params['parent_object']) {
            $parent = $this->getParentInstance();
            if (is_a($parent, 'BimpObject')) {
                return (int) $parent->canCreateChild($this->object_name);
            }
        }
        return 1;
    }

    public function canClientCreate()
    {
        return 0;
    }

    protected function canEdit()
    {
        if ($this->params['parent_object']) {
            $parent = $this->getParentInstance();
            if (is_a($parent, 'BimpObject')) {
                return (int) $parent->canEditChild($this->object_name);
            }
        }
        return $this->canCreate();
    }

    public function canClientEdit()
    {
        return 0;
    }

    protected function canView()
    {
        if ($this->params['parent_object']) {
            $parent = $this->getParentInstance();
            if (is_a($parent, 'BimpObject')) {
                return (int) $parent->canViewChild($this->object_name);
            }
        }
        return 1;
    }

    public function canClientView()
    {
        return 0;
    }

    public function canDelete()
    {
        if ($this->params['parent_object']) {
            $parent = $this->getParentInstance();
            if (is_a($parent, 'BimpObject')) {
                return (int) $parent->canDeleteChild($this->object_name);
            }
        }
        return $this->canEdit();
    }

    public function canClientDelete()
    {
        return 0;
    }

    public function canEditField($field_name)
    {
        return (int) $this->can("edit");
    }

    public function canViewField($field_name)
    {
        return (int) $this->can("view");
    }

    public function canCreateChild($child_name)
    {
        return (int) $this->can("create");
    }

    public function canEditChild($child_name)
    {
        return (int) $this->can("edit");
    }

    public function canViewChild($child_name)
    {
        return (int) $this->can("view");
    }

    public function canDeleteChild($child_name)
    {
        return (int) $this->can("delete");
    }

    public function canSetAction($action)
    {
        // Ne JAMAIS mettre des actions spécifiques à un objet ici !!
//        switch ($action) {
//            case 'createFacture':
//                $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
//                return $facture->canCreate();
//
//            case 'editFacture':
//                $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
//                return $facture->canEdit();
//        }
        return 1;
    }

    public function canSetStatus($status)
    {
        return 1;
    }

    public function canDeleteFiles()
    {
        return 1;
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
        if (!isset($this->id) || !(int) $this->id) {
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
                $this->set('position', (int) $position);
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

    public function resetChildrenPositions($children_name)
    {
        if ($this->isLoaded()) {
            if ($this->config->isDefined('objects/' . $children_name . '/instance/bimp_object')) {
                if ($this->getConf('objects/' . $children_name . '/relation', 'none') === 'hasMany') {
                    $instance_def = $this->getConf('objects/' . $children_name . '/instance/bimp_object', '', false, 'any');
                    if (empty($instance_def)) {
                        return;
                    }
                    $module = '';
                    $object_name = '';

                    if (is_string($instance_def)) {
                        $module = $this->module;
                        $object_name = $instance_def;
                    } else {
                        if (isset($instance_def['module'])) {
                            $module = $instance_def['module'];
                        }
                        if (isset($instance_def['name'])) {
                            $object_name = $instance_def['name'];
                        }

                        if ($module && $object_name) {
                            $instance = BimpObject::getInstance($module, $object_name);

                            if (!$this->isChild($instance)) {
                                return;
                            }

                            if ($instance->params['positions']) {
                                $parent_id_prop = $instance->getParentIdProperty();

                                if (!(string) $parent_id_prop) {
                                    return;
                                }

                                $table = $instance->getTable();
                                $primary = $instance->getPrimary();

                                $items = $instance->getList(array(
                                    $parent_id_prop => (int) $this->id
                                        ), null, null, 'position', 'asc', 'array', array($primary, 'position'));

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
                    }
                }
            }
        }
    }

    // Gestion des notes:

    public function addNote($content, $visibility = null, $viewed = 0, $auto = 1)
    {
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }
        $note = BimpObject::getInstance('bimpcore', 'BimpNote');

        if (is_null($visibility)) {
            $visibility = BimpNote::BIMP_NOTE_MEMBERS;
        }

        $errors = $note->validateArray(array(
            'obj_type'   => 'bimp_object',
            'obj_module' => $this->module,
            'obj_name'   => $this->object_name,
            'id_obj'     => (int) $this->id,
            'visibility' => (int) $visibility,
            'content'    => $content,
            'viewed'     => $viewed,
            'auto'       => $auto
        ));

        if (!count($errors)) {
            $errors = $note->create();
        }

        return $errors;
    }

    public function getNotes()
    {
        return self::getObjectNotes($this);
    }

    public function renderTabs($fonction, $nomTabs, $params1 = null, $params2 = null)
    {//pour patch le chargement auto des onglet
        if (!BimpTools::isSubmit('ajax')) {
            if ($nomTabs == '' || $nomTabs == "default") {
                if (BimpTools::isSubmit('tab') && BimpTools::getValue('tab') != 'default')
                    return 'ne devrais jamais etre visible';
            }
            elseif (BimpTools::getValue('tab') != $nomTabs)
                return 'ne devrais jamais etre visible2';
        }
        if (method_exists($this, $fonction)) {
            if (isset($params2))
                return $this->$fonction($params1, $params2);
            elseif (isset($params1))
                return $this->$fonction($params1);
            else
                return $this->$fonction();
        }
        return 'fonction : ' . $fonction . " inexistante";
    }

    public function renderNotesList($filter_by_user = true, $list_model = "default", $suffixe = "")
    {
        if ($this->isLoaded()) {
            $note = BimpObject::getInstance('bimpcore', 'BimpNote');
            $list = new BC_ListTable($note, $list_model);
            $list->addIdentifierSuffix($suffixe);
            $list->addFieldFilterValue('obj_type', 'bimp_object');
            $list->addFieldFilterValue('obj_module', $this->module);
            $list->addFieldFilterValue('obj_name', $this->object_name);
            $list->addFieldFilterValue('id_obj', $this->id);
            $list->addObjectChangeReload($this->object_name);

            if ($filter_by_user) {
                $filters = BimpNote::getFiltersByUser();
                foreach ($filters as $field => $filter) {
                    $list->addFieldFilterValue($field, $filter);
                }
            }

            return $list->renderHtml();
        }

        return BimpRender::renderAlerts('Impossible d\'afficher la liste des notes (ID ' . $this->getLabel('of_the') . ' absent)');
    }

    // Rendus HTML

    public function renderHeader($content_only = false, $params = array())
    {
        $html = '';
        if ($this->isLoaded()) {
            if (!$content_only) {
                $html .= '<div id="' . $this->object_name . '_' . $this->id . '_header" class="object_header container-fluid">';
            }
            $html .= '<div class="row">';

            $html .= '<div class="col-lg-6 col-sm-8 col-xs-12">';

            $html .= $this->renderObjectMenu();

            $html .= '<div style="display: inline-block">';
            $html .= '<h1>';
            if ($this->params['icon']) {
                $html .= '<i class="' . BimpRender::renderIconClass($this->params['icon']) . ' iconLeft"></i>';
            }
            $html .= BimpTools::ucfirst($this->getLabel()) . ' #' . $this->id . '</h1>';

            $ref = $this->getRef(false);
            if ($ref) {
                $html .= '<h2>';
                $html .= $ref;
                $html .= '</h2>';
            }

            $name = $this->getName(false);
            if ($name) {
                $html .= '<h4>';
                $html .= $name;
                $html .= '</h4>';
            }

            if ($this->use_commom_fields) {
                if ((int) $this->getData('user_create')) {
                    $html .= '<div class="object_header_infos">';
                    $html .= 'Créé' . ($this->isLabelFemale() ? 'e' : '') . ' le <strong>' . $this->displayData('date_create') . '</strong>';
                    $html .= ' par ' . $this->displayData('user_create', 'nom_url');
                    $html .= '</div>';
                }
                if ((int) $this->getData('user_update')) {
                    $html .= '<div class="object_header_infos">';
                    $html .= 'Dernière mise à jour le <strong>' . $this->displayData('date_update') . '</strong>';
                    $html .= ' par ' . $this->displayData('user_update', 'nom_url');
                    $html .= '</div>';
                }
            }

            $html .= '<div class="header_extra">';
            if (method_exists($this, 'renderHeaderExtraLeft')) {
                $html .= '<div style="margin: 10px 0;">';
                $html .= $this->renderHeaderExtraLeft();
                $html .= '</div>';
            }
            $html .= $this->renderMsgs();
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div class="col-lg-6 col-sm-4 col-xs-12" style="text-align: right">';

            $status = '';
            if ($this->field_exists('status')) {
                $status = $this->displayData('status');
            } elseif ($this->field_exists('statut')) {
                $status = $this->displayData('statut');
            } elseif ($this->field_exists('fk_statut')) {
                $status = $this->displayData('fk_statut');
            }

            $html .= '<div class="header_status">';
            if ($status) {
                $html .= $status;
            }
            if (method_exists($this, 'renderHeaderStatusExtra')) {
                $html .= $this->renderHeaderStatusExtra();
            }
            $html .= '</div>';
            $html .= '<div class="header_tools">';
            if (isset($params['allow_lock']) && (int) $params['allow_lock']) {
                $locked = (isset($params['locked']) ? (int) $params['locked'] : 1);

                $html .= '<span class="headerIconButton bs-popover unlock_object_header_button"';
                $html .= BimpRender::renderPopoverData('Dévérouiller l\'en-tête (Cesser de maintenir l\'affichage lors du défilement vertical)', 'bottom');
                $html .= (!$locked ? ' style="display: none;"' : '') . '>';
                $html .= BimpRender::renderIcon('fas_lock-open');
                $html .= '</span>';

                $html .= '<span class="headerIconButton bs-popover lock_object_header_button"';
                $html .= BimpRender::renderPopoverData('Vérouiller l\'en-tête (Maintenir l\'affichage lors du défilement vertical)', 'bottom');
                $html .= ($locked ? ' style="display: none;"' : '') . '>';
                $html .= BimpRender::renderIcon('fas_lock');
                $html .= '</span>';
            }

            $onclick = $this->getJsTriggerObjectChangeOnClick();
            $html .= '<span class="headerIconButton bs-popover" onclick="' . $onclick . '" ';
            $html .= BimpRender::renderPopoverData('Actualiser', 'bottom');
            $html .= '>';
            $html .= BimpRender::renderIcon('fas_redo');
            $html .= '</span>';
            $html .= '</div>';

            $this->params['header_btn'] = $this->config->getCompiledParams('header_btn');
            if (is_null($this->params['header_btn'])) {
                $this->params['header_btn'] = array();
            }

            $header_buttons = array();
            if (count($this->params['header_btn'])) {
                foreach ($this->params['header_btn'] as $header_btn) {
                    $button = null;
                    $label = isset($header_btn['label']) ? $header_btn['label'] : '';
                    $onclick = isset($header_btn['onclick']) ? $header_btn['onclick'] : '';
                    $icon = isset($header_btn['icon']) ? $header_btn['icon'] : '';
                    $onclick = str_replace('component_id', $this->identifier, $onclick);
                    $disabled = isset($header_btn['disabled']) ? (int) $header_btn['disabled'] : 0;
                    $popover = isset($header_btn['popover']) ? (string) $header_btn['popover'] : '';
                    $classes = array('btn', 'btn-light-default');
                    if ($disabled) {
                        $classes[] = 'disabled';
                    }
                    if ($popover) {
                        $classes[] = 'bs-popover';
                    }
                    if ($label) {
                        $button = array(
                            'classes' => $classes,
                            'label'   => $label,
                            'attr'    => array(
                                'type'    => 'button',
                                'onclick' => $onclick
                            )
                        );
                        if ($icon) {
                            $button['icon_before'] = $icon;
                        }
                        if ($popover) {
                            $button['data']['toggle'] = 'popover';
                            $button['data']['trigger'] = 'hover';
                            $button['data']['container'] = 'body';
                            $button['data']['placement'] = 'top';
                            $button['data']['html'] = 'true';
                            $button['data']['content'] = $popover;
                        }
                    }

                    if (!is_null($button)) {
                        $header_buttons[] = BimpRender::renderButton($button, 'button');
                    }
                }
            }

            $html .= '<div class="header_buttons">';
            if (count($header_buttons)) {
                if (count($header_buttons) > 6) {
                    $html .= BimpRender::renderDropDownButton('Actions', $header_buttons, array(
                                'icon'       => 'fas_cogs',
                                'menu_right' => 1
                    ));
                } else {
                    foreach ($header_buttons as $btn) {
                        $html .= str_replace('btn-light-default', 'btn-default', $btn);
                    }
                }
            }

            $html .= '<div style="display: inline-block">';
            if ($this->params['header_edit_form'] && $this->isEditable() && $this->can('edit')) {
                $html .= '<span class="btn btn-primary bs-popover" onclick="' . $this->getJsLoadModalForm($this->params['header_edit_form'], addslashes("Edition " . $this->getLabel('of_the') . ' #' . $this->id)) . '"';
                $html .= BimpRender::renderPopoverData('Editer');
                $html .= '>';
                $html .= BimpRender::renderIcon('fas_edit');
                $html .= '</span>';
            }

            if ((int) $this->params['header_delete_btn'] && $this->isDeletable() && $this->can('delete')) {
                $html .= '<span class="btn btn-danger bs-popover" onclick="' . $this->getJsDeleteOnClick(array(
                            'on_success' => 'reload'
                        )) . '"';
                $html .= BimpRender::renderPopoverData('Supprimer');
                $html .= '>';
                $html .= BimpRender::renderIcon('fas_trash-alt');
                $html .= '</span>';
            }
            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div class="header_extra">';
            if (method_exists($this, 'renderHeaderExtraRight')) {
                $html .= '<div style="margin: 10px 0;">';
                $html .= $this->renderHeaderExtraRight();
                $html .= '</div>';
            }
            if (method_exists($this, 'renderHeaderBtnRedir')) {
                $html .= '<div style="margin: 10px 0;">';
                $html .= $this->renderHeaderBtnRedir();
                $html .= '</div>';
            }
            $html .= '</div>';

            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div class="row header_bottom"></div>';

            if (!$content_only) {
                $html .= '</div>';
            }
        } else {
            $html .= BimpRender::renderAlerts(BimpTools::ucfirst($this->getLabel('this')) . ' n\'existe plus');

            $url = $this->getListPageUrl();

            if ($url) {
                $html .= '<div class="buttonsContainer align-center">';
                $html .= '<button class="btn btn-large btn-primary" onclick="window.location = \'' . $url . '\'">';
                $html .= BimpRender::renderIcon('fas_list', 'iconLeft') . 'Liste des ' . $this->getLabel('name_plur');
                $html .= '</button>';
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function renderObjectMenu()
    {
        $html = '';
        if (!is_null($this->params['header_list_name']) && $this->params['header_list_name']) {
            $html .= '<div class="header_button">';
            $url = $this->getListPageUrl($this->params['header_list_name']);
            if ($url) {
                $items = array(
                    '<span class="dropdown-title">' . BimpRender::renderIcon('fas_list', 'iconLeft') . 'Liste des ' . $this->getLabel('name_plur') . '</span>'
                );
                $items[] = BimpRender::renderButton(array(
                            'label'       => 'Vue rapide de la liste',
                            'icon_before' => 'far_eye',
                            'classes'     => array(
                                'btn', 'btn-light-default'
                            ),
                            'attr'        => array(
                                'onclick' => 'loadModalList(\'' . $this->module . '\', \'' . $this->object_name . '\', \'' . $this->params['header_list_name'] . '\', 0, $(this))'
                            )
                                ), 'button');
                $items[] = BimpRender::renderButton(array(
                            'label'       => 'Afficher la liste',
                            'icon_before' => 'far_file-alt',
                            'classes'     => array(
                                'btn', 'btn-light-default'
                            ),
                            'attr'        => array(
                                'onclick' => 'window.location = \'' . $url . '\''
                            )
                                ), 'button');
                $items[] = BimpRender::renderButton(array(
                            'label'       => 'Afficher la liste dans un nouvel onglet',
                            'icon_before' => 'fas_external-link-alt',
                            'classes'     => array(
                                'btn', 'btn-light-default'
                            ),
                            'attr'        => array(
                                'onclick' => 'window.open(\'' . $url . '\');'
                            )
                                ), 'button');
                $html .= BimpRender::renderDropDownButton('', $items, array(
                            'icon' => 'fas_list'
                ));
            } else {
                $html .= BimpRender::renderButton(array(
                            'icon_before' => 'bars',
                            'classes'     => array(
                                'btn', 'btn-default', 'bs-popover'
                            ),
                            'attr'        => array(
                                'onclick'        => 'loadModalList(\'' . $this->module . '\', \'' . $this->object_name . '\', \'' . $this->params['header_list_name'] . '\', 0, $(this))',
                                'data-toggle'    => 'popover',
                                'data-trigger'   => 'hover',
                                'data-placement' => 'top',
                                'data-container' => 'body',
                                'data-content'   => 'Afficher la liste des ' . $this->getLabel('name_plur')
                            )
                                ), 'button');
            }
            $html .= '</div>';
        }

        return $html;
    }

    public function renderMsgs()
    {
        $html = '';

        if (count($this->msgs['errors'])) {
            $html .= BimpRender::renderAlerts($this->msgs['errors']);
        }

        if (count($this->msgs['warnings'])) {
            $html .= BimpRender::renderAlerts($this->msgs['warnings'], 'warning');
        }

        if (count($this->msgs['infos'])) {
            $html .= BimpRender::renderAlerts($this->msgs['infos'], 'info');
        }

        return $html;
    }

    public function renderView($view_name = 'default', $panel = false, $level = 1)
    {
        if (!isset($this->id) || !$this->id) {
            $msg = ucfirst($this->getLabel('this')) . ' n\'existe plus';
            return BimpRender::renderAlerts($msg);
        }
        $view = new BC_View($this, $view_name, !$panel, $level);
        return $view->renderHtml();
    }

    public function renderList($list_name = 'default', $panel = false, $title = null, $icon = null, $filters = array(), $level = 1)
    {
        $list = new BC_ListTable($this, $list_name, $level, null, $title, $icon);

        if (!is_null($filters)) {
            foreach ($filters as $field_name => $value) {
                $list->addFieldFilterValue($field_name, $value);
            }
        }

        return $list->renderHtml();
    }

    public function renderListCustom($list_name = 'default', $title = null, $icon = null)
    {
        $list = new BC_ListCustom($this, $list_name, null, $title, $icon);
        return $list->renderHtml();
    }

    public function renderStatsList($list_name = 'default', $title = null, $icon = null)
    {
        $list = new BC_StatsList($this, $list_name, null, $title, $icon);
        return $list->renderHtml();
    }

    public function renderForm($form_name = 'default', $panel = false, $level = 1)
    {
        $form = new BC_Form($this, null, $form_name, $level, !$panel);

        return $form->renderHtml();
    }

    public function renderViewsList($views_list_name = 'default', $panel = false, $title = null, $icon = null, $filters = array(), $level = 1)
    {
        $viewsList = new BC_ListViews($this, $views_list_name, $level, null, $title, $icon);
        $viewsList->params['panel'] = (int) $panel;

        if (!is_null($filters)) {
            foreach ($filters as $field_name => $value) {
                $viewsList->addFieldFilterValue($field_name, $value);
            }
        }

        return $viewsList->renderHtml();
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

    public function renderChildrenList($children_object, $list_name = 'default', $panel = false, $title = null, $icon = null, $level = 1)
    {
        $children_instance = $this->config->getObject('', $children_object);

        if (!$this->isLoaded()) {
            $msg = 'Impossible d\'afficher la liste des ' . $children_instance->getLabel('name_plur');
            $msg .= ' - ID ' . $this->getLabel('of_the') . ' absent';
            return BimpRender::renderAlerts($msg);
        }

        $children_instance->parent = $this;

        if (!is_null($children_instance) && is_a($children_instance, 'BimpObject')) {
            $title = (is_null($title) ? $this->getConf('objects/' . $children_object . '/list/title') : $title);
            $icon = (is_null($icon) ? $this->getConf('objects/' . $children_object . '/list/icon', $icon) : $icon);

            $list = new BC_ListTable($children_instance, $list_name, $level, $this->id, $title, $icon);

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

            return $list->renderHtml();
        }
        $msg = 'Erreur technique: objets "' . $children_object . '" non trouvés pour ' . $this->getLabel('this');
        return BimpRender::renderAlerts($msg);
    }

    public function renderChildCard($object_name, $card_name = '', $card_path = '')
    {
        $card = new BC_Card($this, $object_name, $card_name);
        return $card->renderHtml();
    }

    public function renderAssociatesList($association, $list_name = 'default', $panel = false, $title = null, $icon = null, $level = 1)
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

        $list = new BC_ListTable($bimpAsso->associate, $list_name, $level, null, $title, $icon);

        $list->addObjectAssociationFilter($this, $this->id, $association);

        if ($this->config->isDefined('associations/' . $association . '/add_form')) {
            $name = $this->getConf('associations/' . $association . '/add_form/name', '');
            $values = $this->getConf('associations/' . $association . '/add_form/values', null, false, 'array');

            if ($name) {
                $list->setAddFormName($name);
            }
            if (!is_null($values)) {
                $list->setAddFormValues($values);
            }
        } else {
            $list->addForm = null;
        }

        return $list->renderHtml();
    }

    public function renderSearchResults($search_value, $search_name = 'default')
    {
        $errors = array();

        if (is_null($search_value) || !$search_value) {
            $errors[] = 'Aucun terme de recherche spécifié';
        }

        if (!$this->config->isDefined('searches/' . $search_name)) {
            $errors[] = 'Type de recherche invalide';
        }

        if (!count($errors)) {
            if ($this->params['list_page_url']) {
                $url = $this->getListPageUrl();
                if ($url) {
                    $list_button = '<div class="buttonsContainer">';
                    $list_button .= '<a class="btn btn-primary" href="' . $url . '">';
                    $list_button .= '<i class="fa fa-chevron-left iconLeft"></i>Liste complète des ' . $this->getLabel('name_plur');
                    $list_button .= '</a>';
                    $list_button .= '</div>';
                }
            }

            $search = new BC_Search($this, $search_name, $search_value);
            $html = $list_button;
            $html .= $search->renderHtml();
            $html .= $list_button;
            return $html;
        }

        return BimpRender::renderAlerts($errors);
    }

    public function renderListConfigColsInput()
    {
        $html = '';

        $list_name = BimpTools::getPostFieldValue('list_name');
        $cols = $this->getListColsArray($list_name);

        if (count($cols)) {
            $owner_type = BimpTools::getPostFieldValue('owner_type', '');
            $id_owner = BimpTools::getPostFieldValue('id_owner', 0);
            $list_name = BimpTools::getPostFieldValue('list_name', 'default');

            $values = array();
            if ($owner_type && $id_owner) {
                $userConfig = $this->getListConfig($owner_type, $id_owner, $list_name);
                if (BimpObject::objectLoaded($userConfig)) {
                    foreach (explode(',', $userConfig->getData('cols')) as $col_name) {
                        if (isset($cols[$col_name])) {
                            $values[$col_name] = $cols[$col_name];
                        }
                    }
                } else {
                    $bc_list = new BC_ListTable($this, $list_name);
                    foreach ($bc_list->cols as $col_name) {
                        if (isset($cols[$col_name])) {
                            $values[$col_name] = $cols[$col_name];
                        }
                    }
                }
            }

            $input = BimpInput::renderInput('select', 'cols_add_value', '', array('options' => $cols));
            $content = BimpInput::renderMultipleValuesInput($this, 'cols', $input, $values, '', 0, 1, 1);
            $html .= BimpInput::renderInputContainer('cols', '', $content, '', 0, 1, '', array('values_field' => 'cols'));
        } else {
            $html .= BimpRender::renderAlerts('Aucune option disponible', 'warnings');
        }

        return $html;
    }

    public function renderRemoveChildObjectButton($field, $reload_page = false)
    {
        $html = '';

        if ($this->isLoaded()) {
            if ($this->field_exists($field)) {
                if ($this->getConf('fields/' . $field . '/type', 'string') === 'id_object') {
                    if ((int) $this->getData($field)) {
                        $object = $this->config->getObject('fields/' . $field . '/object');
                        $html .= '<span type="button" class="btn btn-danger" onclick="' . $this->getJsActionOnclick('removeChildObject', array(
                                    'field'       => $field,
                                    'reload_page' => (int) $reload_page
                                )) . '">';
                        $html .= BimpRender::renderIcon('fas_times', 'iconLeft') . 'Retirer';
                        if (is_a($object, 'BimpObject')) {
                            $html .= ' ' . $object->getLabel('the');
                        }
                        $html .= '</span>';
                    }
                }
            }
        }

        return $html;
    }

    public function renderChildUnfoundMsg($field, $instance = null, $remove_button = true, $reload_page = false)
    {
        if ($this->field_exists($field)) {
            if ($this->getConf('fields/' . $field . '/type', 'string') === 'id_object') {
                $id_object = (int) $this->getData($field);

                if (is_null($instance)) {
                    $instance = $this->config->getObject('fields/' . $field . '/object');
                }

                if (is_a($instance, 'BimpObject')) {
                    $msg = BimpTools::ucfirst($instance->getLabel('the')) . ' d\'ID ' . $id_object . ' semble avoir été supprimé' . ($instance->isLabelFemale() ? 'e' : '');
                } elseif (is_object($instance)) {
                    $msg .= 'L\'objet de type "' . get_class($instance) . '" d\'ID ' . $id_object . ' semble avoir été supprimé';
                } else {
                    $msg = 'Cet objet semble avoir été supprimé (ID: ' . $id_object . ')';
                }

                if ($remove_button) {
                    $msg .= ' ' . $this->renderRemoveChildObjectButton($field, $reload_page);
                }

                return BimpRender::renderAlerts($msg);
            }
        }
    }

    public function renderSearchInput($input_name, $value = null)
    {
        $search_data = $this->getSearchData();

        $params = array(
            'table'              => $this->getTable(),
            'field_return_label' => $this->getNameProperty(),
            'field_return_value' => $this->getPrimary(),
            'fields_search'      => $search_data['fields_search'],
            'field_return_label' => $search_data['field_return_label'],
            'label_syntaxe'      => $search_data['label_syntaxe'],
            'filters'            => $search_data['filters']
        );

        if ($search_data['has_extrafields']) {
            $params['join'] = $this->getTable() . '_extrafields ef';
            $params['join_on'] = 'a.' . $this->getPrimary() . ' = ef.fk_object';
        }

        $html = BimpInput::renderSearchListInput($input_name, $params, $value, 'default');
        $html .= '<p class="inputHelp">Rechercher ' . $this->getLabel('a') . '</p>';
        return $html;
    }

    public function renderSearchObjectInput($input_name, $object_name)
    {
        if (isset($this->params['objects'][$object_name])) {
            $params = $this->params['objects'][$object_name];
            if (isset($params['instance']['bimp_object'])) {
                $instance = BimpObject::getInstance($params['instance']['bimp_object']['module'], $params['instance']['bimp_object']['name']);
                return $instance->renderSearchInput($input_name);
            } elseif (isset($params['instance']['dol_object'])) {
                return BimpRender::renderAlerts('Dol object: "' . $object_name . '" à implémenter', 'warning');
            }
        }
        return BimpRender::renderAlerts('Erreur de configuration: objet "' . $object_name . '" non défini');
    }

    public function renderHeaderBtnRedir()
    {
        return $this->processRedirect(false);
    }

    public function renderListCsvColsOptions()
    {
        $list_name = BimpTools::getPostFieldValue('list_name', 'default');

        $bc_list = new BC_ListTable($this, $list_name);

        if (count($bc_list->errors)) {
            return BimpRender::renderAlerts($bc_list->errors);
        }

        $cols = $bc_list->cols;

        $html = '';

        $html .= '<table class="bimp_list_table">';
        $html .= '<tbody>';

        foreach ($cols as $col_name) {
            $label = '';
            $content = '';

            $col_params = $bc_list->getColParams($col_name);

            if (!(int) $col_params['show'] || (int) $col_params['hidden'] || !(int) $col_params['available_csv']) {
                continue;
            }

            if (isset($col_params['field']) && $col_params['field']) {
                $bc_field = null;
                $instance = null;
                if (isset($col_params['child']) && $col_params['child']) {
                    if ($col_params['child'] === 'parent') {
                        $instance = $this->getParentInstance();
                    } else {
                        $instance = $this->config->getObject('', $col_params['child']);
                    }
                } else {
                    $instance = $this;
                }

                if (is_a($instance, 'BimpObject')) {
                    if ($instance->field_exists($col_params['field'])) {
                        $bc_field = new BC_Field($instance, $col_params['field']);
                        if (count($bc_field->errors)) {
                            $content = BimpRender::renderAlerts($bc_field->errors);
                        } else {
                            $label = $bc_field->params['label'];
                            $value = '';
                            $options = $bc_field->getNoHtmlOptions($value);

                            if (!empty($options)) {
                                $content = BimpInput::renderInput('select', 'col_' . $col_name . '_option', $value, array(
                                            'options'     => $options,
                                            'extra_class' => 'col_option'
                                ));
                            } else {
                                $content .= 'Valeur';
                            }
                        }
                    } else {
                        $content = BimpRender::renderAlerts('Le champ "' . $col_params['field'] . '" n\'existe pas dans l\'objet "' . $instance->getLabel() . '"');
                    }
                } else {
                    $content = BimpRender::renderAlerts('Instance invalide');
                }
            } else {
                $label = ((string) $col_params['label'] ? $col_params['label'] : $col_name);
                $content = 'Valeur affichée';
            }

            $html .= '<tr>';
            $html .= '<th>' . $label . '</th>';
            $html .= '<td>' . $content . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    public function renderCaisseInput()
    {
        BimpObject::loadClass('bimpcaisse', 'BC_Caisse');
        return BC_Caisse::renderUserCaisseInput();
    }

    // Générations javascript: 

    public function getJsObjectData()
    {
        $js = '{';
        $js .= 'module: \'' . $this->module . '\'';
        $js .= ', object_name: \'' . $this->object_name . '\'';
        $js .= ', id_object: \'' . ($this->isLoaded() ? $this->id : 0) . '\'';
        $js .= '}';

        return $js;
    }

    public function getJsLoadModalForm($form_name = 'default', $title = '', $values = array(), $success_callback = '', $on_save = '', $force_edit = 0)
    {
        $id_parent = 0;
        $parent_id_property = $this->getParentIdProperty();

        if ($parent_id_property) {
            if (isset($values['fields'][$parent_id_property])) {
                $id_parent = (int) $values['fields'][$parent_id_property];
            }
        }

        if (!$id_parent) {
            $id_parent = (int) $this->getParentId();
        }

        $data = '{';
        $data .= 'module: "' . $this->module . '", ';
        $data .= 'object_name: "' . $this->object_name . '", ';
        $data .= 'id_object: "' . ($this->isLoaded() ? $this->id : 0) . '", ';
        $data .= 'id_parent: "' . $id_parent . '", ';
        $data .= 'form_name: "' . $form_name . '", ';

        if (!empty($values)) {
            $data .= 'param_values: ' . json_encode($values) . ', ';
        }

        $data .= 'force_edit: ' . $force_edit;

        $data .= '}';

        $js = 'loadModalForm($(this), ' . htmlentities($data) . ', \'' . htmlentities($title) . '\', \'' . htmlentities($success_callback) . '\', \'' . $on_save . '\')';
        return $js;
    }

    public function getJsLoadModalView($view_name = 'default', $title = '')
    {
        $js = '';

        if ($this->isLoaded()) {
            $js = 'loadModalView(\'' . $this->module . '\', \'' . $this->object_name . '\', ' . $this->id . ', \'' . $view_name . '\', $(this), \'' . htmlentities($title) . '\');';
        }

        return $js;
    }

    public function getJsLoadModalList($list_name = 'default', $params = array())
    {
        $js = 'loadModalList(\'' . $this->module . '\', \'' . $this->object_name . '\', \'' . $list_name . '\', ';

        if (isset($params['id_parent']) && (int) $params['id_parent']) {
            $js .= (int) $params['id_parent'];
        } elseif ((int) $this->getParentId()) {
            $js .= (int) $this->getParentId();
        } else {
            $js .= 'null';
        }

        $js .= ', $(this), ';

        if (isset($params['title']) && (string) $params['title']) {
            $js .= '\'' . htmlentities($params['title']) . '\'';
        } else {
            $js .= '\'Liste des ' . htmlentities($this->getLabel('name_plur')) . '\'';
        }
        $js .= ', ';

        if (isset($params['extra_data']) && is_array($params['extra_data']) && !empty($params['extra_data'])) {
            $js .= '{';
            $data = '';
            foreach ($params['extra_data'] as $key => $value) {
                $data .= $key . ': "' . $value . '"';
            }
            $js .= htmlentities($data);
            $js .= '}';
        } else {
            $js .= '{}';
        }
        $js .= ', ';

        if (isset($params['extra_filters']) && is_array($params['extra_filters']) && !empty($params['extra_filters'])) {
            $js .= json_encode($params['extra_data']);
        } else {
            $js .= 'null';
        }
        $js .= ', ';

        if (isset($params['extra_joins']) && is_array($params['extra_joins']) && !empty($params['extra_joins'])) {
            $js .= json_encode($params['extra_joins']);
        } else {
            $js .= 'null';
        }

        $js .= ');';

        return $js;
    }

    public function getJsNewStatusOnclick($new_status, $data = array(), $params = array())
    {
        $js = 'setObjectNewStatus(';
        $js .= '$(this), ' . $this->getJsObjectData();
        $js .= ', ' . $new_status . ', {';
        $fl = true;
        foreach ($data as $key => $value) {
            if (!$fl) {
                $js .= ', ';
            } else {
                $fl = false;
            }
            $js .= $key . ': ' . (BimpTools::isNumericType($value) ? $value : '\'' . $value . '\'');
        }
        $js .= '}, ';
        if (isset($params['result_container'])) {
            $js .= $params['result_container'];
        } else {
            $js .= 'null';
        }
        $js .= ', ';
        if (isset($params['success_callback'])) {
            $js .= $params['success_callback'];
        } else {
            $js .= 'null';
        }
        $js .= ', ';
        if (isset($params['confirm_msg'])) {
            $js .= '\'' . $params['confirm_msg'] . '\'';
        } else {
            $js .= 'null';
        }
        $js .= ');';
        return $js;
    }

    public function getJsActionOnclick($action, $data = array(), $params = array())
    {
        $js = 'setObjectAction(';
        $js .= '$(this), ' . $this->getJsObjectData();
        $js .= ', \'' . $action . '\', {';
        $fl = true;
        foreach ($data as $key => $value) {
            if (!$fl) {
                $js .= ', ';
            } else {
                $fl = false;
            }
            $js .= $key . ': ' . (BimpTools::isNumericType($value) ? $value : '\'' . $value . '\'');
        }
        $js .= '}, ';
        if (isset($params['form_name'])) {
            $js .= '\'' . $params['form_name'] . '\'';
        } else {
            $js .= 'null';
        }
        $js .= ', ';
        if (isset($params['result_container'])) {
            $js .= $params['result_container'];
        } else {
            $js .= 'null';
        }
        $js .= ', ';
        if (isset($params['success_callback'])) {
            $js .= $params['success_callback'];
        } else {
            $js .= 'null';
        }
        $js .= ', ';
        if (isset($params['confirm_msg'])) {
            $js .= '\'' . $params['confirm_msg'] . '\'';
        } else {
            $js .= 'null';
        }
        $js .= ', ';
        if (isset($params['on_form_submit'])) {
            $js .= $params['on_form_submit'];
        } else {
            $js .= 'null';
        }
        $js .= ', ';
        if (isset($params['no_triggers'])) {
            $js .= ((int) $params['no_triggers'] ? 'true' : 'false');
        } else {
            $js .= 'false';
        }

        $js .= ');';

        return $js;
    }

    public function getJsBulkActionOnclick($action, $data = array(), $params = array())
    {
        $js = 'setSelectedObjectsAction(';
        $js .= '$(this), \'list_id\', \'' . $action . '\', {';
        $fl = true;
        foreach ($data as $key => $value) {
            if (!$fl) {
                $js .= ', ';
            } else {
                $fl = false;
            }
            $js .= $key . ': ' . (BimpTools::isNumericType($value) ? $value : '\'' . $value . '\'');
        }
        $js .= '}, ';
        if (isset($params['form_name'])) {
            $js .= '\'' . $params['form_name'] . '\'';
        } else {
            $js .= 'null';
        }
        $js .= ', ';
        if (isset($params['confirm_msg'])) {
            $js .= '\'' . $params['confirm_msg'] . '\'';
        } else {
            $js .= 'null';
        }
        $js .= ', ';
        if (isset($params['single_action'])) {
            $js .= $params['single_action'];
        } else {
            $js .= 'false';
        }
        $js .= ');';

        return $js;
    }

    public function getJsTriggerObjectChangeOnClick()
    {
        if ($this->isLoaded()) {
            return 'triggerObjectChange(\'' . $this->module . '\', \'' . $this->object_name . '\', ' . $this->id . ')';
        }

        return '';
    }

    public function getJsDeleteOnClick($params = array())
    {
        if (!$this->isLoaded()) {
            return '';
        }

        $js = 'deleteObject($(this), \'' . $this->module . '\', \'' . $this->object_name . '\', ' . $this->id;

        if (isset($params['result_container'])) {
            $js .= ', ' . $params['result_container'];
        } else {
            $js .= ', null';
        }

        if (isset($params['on_success'])) {
            switch ($params['on_success']) {
                case 'redirect':
                    if ($this->params['list_page_url']) {
                        $url = $this->getListPageUrl();
                        if ($url) {
                            $js .= ', function() {window.location = \'' . $url . '\';}';
                            break;
                        }
                    }

                case 'reload':
                    $js .= ', function() {bimp_reloadPage();}';
                    break;

                default:
                    $js .= ', null';
            }
        } elseif (isset($params['success_callback'])) {
            $js .= ', ' . $params['success_callback'];
        } else {
            $js .= ', null';
        }

        $js .= ')';

        return $js;
    }

    // Gestion des intitulés (labels):

    public function getLabels()
    {
        $labels = $this->params['labels'];

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

        if (!isset($labels['of_plur'])) {
            if ($vowel_first) {
                $labels['of_plur'] = 'd\'' . $labels['name_plur'];
            } else {
                $labels['of_plur'] = 'de ' . $labels['name_plur'];
            }
        }

        if (!isset($labels['all_the'])) {
            if ($isFemale) {
                $labels['all_the'] = 'toutes les ' . $labels['name_plur'];
            } else {
                $labels['all_the'] = 'tous les ' . $labels['name_plur'];
            }
        }
        return $labels;
    }

    public function getLabel($type = '')
    {
        $labels = $this->params['labels'];

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

            case 'of_plur':
                if ($vowel_first) {
                    return 'd\'' . $name_plur;
                } else {
                    return 'de ' . $name_plur;
                }

            case 'all_the':
                if ($isFemale) {
                    return 'toutes les ' . $name_plur;
                } else {
                    return 'tous les ' . $name_plur;
                }
        }

        return $object_name;
    }

    public function isLabelFemale()
    {
        return (int) $this->params['labels']['is_female'];
    }

    public function e()
    {
        if ($this->isLabelFemale()) {
            return 'e';
        }

        return '';
    }

    public function getInstanceName()
    {
        return $this->getName();
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
            return $instance->getName();
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

    public static function getInstanceRef($instance)
    {
        if (is_a($instance, 'BimpObject')) {
            return $instance->getRef();
        }

        if (isset($instance->ref)) {
            return $instance->ref;
        }
        if (isset($instance->id)) {
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
            if ($this->isDolObject()) {
                return $this->getInstanceUrl($this->dol_object);
            }
            return '';
        }

        return DOL_URL_ROOT . '/' . $this->module . '/index.php?fc=' . $controller . '&id=' . $this->id;
    }

    public function getNomUrl($withpicto = true, $ref_only = true, $page_link = false, $modal_view = '')
    {
        $url = $this->getUrl();

        $label = '';
        if ($withpicto && $this->params['icon']) {
            $label .= BimpRender::renderIcon($this->params['icon'], 'iconLeft');
        }
        $ref = $this->getRef();

        if (!$ref || !$ref_only) {
            $nom = $this->getInstanceNom($this);
            if ($ref && preg_match('/^.*' . $ref . '.*$/', $nom)) {
                $label .= $nom;
            } else {
                $label .= ($ref ? $ref . ' - ' : '') . $nom;
            }
        } else {
            $label .= $ref;
        }

        if ($url) {
            $html .= '<a href="' . $url . '">' . $label . '</a>';
        } else {
            $html .= $label;
        }

        if (($url && $page_link) || $modal_view) {
            $html .= BimpRender::renderObjectIcons($this, $page_link, $modal_view, $url);
        }

        return $html;
    }

    public function getListUrl()
    {
        $url = BimpTools::makeUrlFromConfig($this->config, 'list_page_url', $this->module, $this->getController());

        if (!$url && $this->isDolObject()) {
            $url = BimpTools::getDolObjectListUrl($object);
        }

        return $url;
    }

    public function getCommonListUrl($list_name = 'default', $filters = array())
    {
        $url = DOL_URL_ROOT . '/bimpcore/index.php?fc=list&module=' . $this->module . '&object_name=' . $this->object_name . '&list_name=' . $list_name;
        if (!empty($filters)) {
            $url .= '&filters=' . urlencode(json_encode($filters));
        }
        return $url;
    }

    public function getChildObjectUrl($object_name, $object = null)
    {
        if (is_null($object)) {
            $object = $this->config->getObject('', $object_name);
        }

        if (is_null($object)) {
            return '';
        }

        return $this->getInstanceUrl($object);
    }

    public static function getInstanceNomUrl($instance)
    {
        $html = '';
        if (is_a($instance, 'BimpObject')) {
            if ($instance->isDolObject()) {
                $html = $instance->dol_object->getNomUrl(1);
            } else {
                $html .= $instance->getNomUrl();
            }
        } elseif (method_exists($instance, 'getNomUrl')) {
            $html .= $instance->getNomUrl(1);
        } else {
            $html .= 'Objet "' . get_class($instance) . '"' . isset($instance->id) ? ' n° ' . $instance->id : '';
        }

        return $html;
    }

    public static function getInstanceUrl($instance)
    {
        if (is_a($instance, 'BimpObject')) {
            return $instance->getUrl();
        }
        return BimpTools::getDolObjectUrl($instance);
    }

    public static function getInstanceNomUrlWithIcons($instance)
    {
        $html = self::getInstanceNomUrl($instance);
        $url = self::getInstanceUrl($instance);

        if ($url) {
            $html .= BimpRender::renderObjectIcons($instance, true, null, $url);
        }

        return $html;
    }

    // Array communs: 

    public function getInternalContactTypesArray()
    {
        if ($this->isDolObject() && method_exists($this->dol_object, 'liste_type_contact')) {
            $cache_key = $this->module . '_' . $this->object_name . '_internal_contact_types_array';
            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = $this->dol_object->liste_type_contact('internal');
            }
            return self::$cache[$cache_key];
        }

        return array();
    }

    public function getExternalContactTypesArray()
    {
        if ($this->isDolObject() && method_exists($this->dol_object, 'liste_type_contact')) {
            $cache_key = $this->module . '_' . $this->object_name . '_external_contact_types_array';
            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = $this->dol_object->liste_type_contact('external');
            }
            return self::$cache[$cache_key];
        }

        return array();
    }

    public function getFilesArray($with_deleted = 0)
    {
        if ($this->isLoaded()) {
            return self::getObjectFilesArray($this, $with_deleted);
        }

        return array();
    }

    public function getSortableFieldsArray()
    {
        $fields = array();

        foreach ($this->params['fields'] as $field_name) {
            $bc_field = new BC_Field($this, $field_name);
            if ($bc_field->params['sortable']) {
                $fields[$field_name] = $bc_field->params['label'];
            }
        }

        return $fields;
    }

    public function getSortOptionsArray($field = null)
    {
        $options = array();

        if (is_null($field)) {
            $field = BimpTools::getPostFieldValue('sort_field');
        }

        if ($this->config->isDefined('fields/' . $field . '/sort_options')) {
            foreach ($this->config->getCompiledParams('fields/' . $field . '/sort_options') as $option_name => $params) {
                $options[$option_name] = $params['label'];
            }
        } elseif ($this->getConf('fields/' . $field . '/type', 'string') === 'id_object') {
            $obj = $this->config->getObject('fields/' . $field . '/object');
            if (!is_null($obj) && is_a($obj, 'BimpObject')) {
                $obj_label = $this->getConf('fields/' . $field . '/label') . ': ';
                foreach ($obj->params['fields'] as $field_name) {
                    if ((int) $obj->getConf('fields/' . $field_name . '/sortable', 1, false, 'bool')) {
                        $options[$field_name] = $obj_label . $obj->getConf('fields/' . $field_name . '/label', $field_name);
                    }
                }
            }
        }

        return $options;
    }

    public function getListColsArray($list_name = 'default')
    {
        return self::getObjectListColsArray($this, $list_name);
    }

    // Actions Communes: 

    public function actionDeleteFile($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Fichier supprimé avec succès';

        if (!$this->canDeleteFiles()) {
            $errors[] = 'Vous n\'avez pas la permissions de supprimer les fichier pour ' . $this->getLabel('the_plur');
        } else {
            if (!isset($data['file']) || !(string) $data['file']) {
                $errors[] = 'Aucun fichier spécifié';
            } elseif (!file_exists($data['file'])) {
                $errors[] = 'Fichier à supprimer non trouvé';
            } elseif (!preg_match('/^\/?' . str_replace('/', '\/', DOL_DATA_ROOT) . '\/.*$/', $data['file'])) {//|| !is_file($data['file'])) {
                $errors[] = 'ce fichier n\'est pas supprimable';
            } else {
                if (!unlink($data['file'])) {
                    $errors[] = 'Echec de la suppression du fichier';
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRemoveLinkedObject($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Lien supprimé avec succès';

        if (!isset($data['id_link']) || !(int) $data['link_id']) {
            
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSetListConfig($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Paramètres enregistrés avec succès';

        if (!isset($data['owner_type']) || !$data['owner_type']) {
            $errors[] = 'Type de propriétaire absent';
        }
        if (!isset($data['id_owner']) || !$data['id_owner']) {
            $errors[] = 'ID du propriétaire absent';
        }
        if (!isset($data['list_name']) || !$data['list_name']) {
            $errors[] = 'Nom de la liste absent';
        }

        if (!count($errors)) {
            $config = BimpObject::getInstance('bimpcore', 'ListConfig');
            $config->find(array(
                'owner_type' => $data['owner_type'],
                'id_owner'   => $data['id_owner'],
                'obj_module' => $this->module,
                'obj_name'   => $this->object_name,
                'list_name'  => $data['list_name']
            ));

            if (isset($data['nb_items'])) {
                $config->set('nb_items', (int) $data['nb_items']);
            }

            if (isset($data['sort_field'])) {
                $config->set('sort_field', $data['sort_field']);
            }

            if (isset($data['sort_way'])) {
                $config->set('sort_way', $data['sort_way']);
            }

            $sort_options = $this->getSortOptionsArray();

            if (isset($data['sort_option']) && array_key_exists($data['sort_option'], $sort_options)) {
                $config->set('sort_option', $data['sort_option']);
            } else {
                $config->set('sort_option', '');
            }

            if (isset($data['cols'])) {
                $config->set('cols', implode(',', $data['cols']));
            }

            if ($config->isLoaded()) {
                $errors = $config->update($warnings, true);
            } else {
                $config->set('owner_type', $data['owner_type']);
                $config->set('id_owner', $data['id_owner']);
                $config->set('obj_module', $this->module);
                $config->set('obj_name', $this->object_name);
                $config->set('list_name', $data['list_name']);
                $errors = $config->create($warnings, true);
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionRemoveChildObject($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Mise à jour effectuée avec succès';

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } elseif (!isset($data['field']) || !(string) $data['field']) {
            $errors[] = 'Champ contenant l\'ID de l\'objet non spécifié';
        } elseif (!$this->field_exists($data['field'])) {
            $errors[] = 'Le champ "' . $data['field'] . '" n\'existe pas pour les ' . $this->data['name_plur'];
        } elseif ($this->getConf('fields/' . $data['field'] . '/type', 'string') !== 'id_object') {
            $errors[] = 'Le champ "' . $data['field'] . '" ne contient pas l\'ID d\'un objet';
        } else {
            $id_object = (int) $this->getData($data['field']);
            $instance = null;
            if ($this->config->isDefined('fields/' . $data['field'] . '/object')) {
                $instance = $this->config->getObject('fields/' . $data['field'] . '/object');
            }

            if (!$id_object) {
                $msg = 'Aucun';
                if (is_a($instance, 'BimpObject')) {
                    $msg .= ($instance->isLabelFemale() ? 'e' : '') . ' ' . $instance->getLabel() . ' associé' . ($instance->isLabelFemale() ? 'e' : '');
                } elseif (is_object($instance)) {
                    $msg .= ' objet de type "' . get_class($instance) . '" associé';
                } else {
                    $msg .= ' objet associé';
                }
                $msg .= ' à ' . $this->getLabel('this');
                $errors[] = $msg;
            } else {
                $up_errors = array();
                if (!(int) $this->getConf('fields/' . $data['field'] . '/required', 0, false, 'bool')) {
                    $this->set($data['field'], 0);
                    $up_errors = $this->update($warnings, true);
                } else {
                    $up_errors = $this->updateField($data['field'], 0, $this->id, true, true);
                }
                if (count($up_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour ' . $this->getLabel('of_the'));
                }
            }
        }

        $success_callback = '';
        if (isset($data['reload_page']) && (int) $data['reload_page']) {
            $success_callback = 'bimp_reloadPage();';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionMoveFile($data, &$success)
    {
        $errors = array();
        $warnings = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } elseif (!isset($data['id_file']) || !(int) $data['id_file']) {
            $errors[] = 'ID du fichier absent';
        } else {
            $file = BimpCache::getBimpObjectInstance('bimpcore', 'BimpFile', (int) $data['id_file']);
            if (!$file->isLoaded()) {
                $errors[] = 'Le fichier d\'ID ' . $data['id_file'] . ' n\'existe pas';
            } elseif (!$file->isParent($this)) {
                $errors[] = 'Ce fichier n\'appartient pas à ' . $this->getLabel('this');
            } else {
                $object_name = isset($data['move_to_object_name']) ? $data['move_to_object_name'] : '';
                $id_object = (int) isset($data['move_to_id_object']) ? $data['move_to_id_object'] : 0;

                if (!$object_name) {
                    $errors[] = 'Type de destinataire absent';
                }

                if (!$id_object) {
                    $errors[] = 'Destinataire absent';
                }

                if (!count($errors)) {
                    $moveTo = $this->getChildObject($object_name, $id_object);
                    if (!BimpObject::objectLoaded($moveTo)) {
                        $errors[] = 'Destinataire invalide';
                    } else {
                        $keep_copy = (int) isset($data['keep_copy']) ? $data['keep_copy'] : 0;
                        $create_link = (int) isset($data['create_link']) ? $data['create_link'] : 0;
                        $errors = $file->moveToObject($moveTo, $keep_copy);

                        if ($keep_copy) {
                            $success = 'Fichier copié avec succès';
                        } else {
                            $success = 'Fichier déplacé avec succès';
                        }
                        if (!count($errors) && !$keep_copy && $create_link) {
                            $asso = new BimpAssociation($this, 'files');
                            $asso->addObjectAssociation((int) $file->id);
                        }
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSaveListFilters($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Filtres enregistrés avec succès';

        if (!isset($data['list_type']) || !(string) $data['list_type']) {
            $errors[] = 'Type de liste absent';
        }
        if (!isset($data['name']) || !(string) $data['name']) {
            $errors[] = 'Veuillez spécifié un nom pour cet enregistrement';
        }

        if (!count($errors)) {
            $owner_type = (isset($data['owner_type']) ? (int) $data['owner_type'] : 2);

            $id_owner = 0;
            if (!isset($data['id_owner']) || !(int) $data['id_owner']) {
                if ($owner_type === 2) {
                    global $user;

                    if (!BimpObject::objectLoaded($user)) {
                        $errors[] = 'Aucun utilisateur connecté';
                    } else {
                        $id_owner = $user->id;
                    }
                } else {
                    $errors[] = 'ID du groupe propriétaire absent';
                }
            } else {
                $id_owner = (int) $data['id_owner'];
            }

            $filters = (isset($data['filters']) && is_array($data['filters']) ? $data['filters'] : array());

            $values = array(
                'fields'   => array(),
                'children' => array()
            );
            if (!empty($filters)) {
                foreach ($filters['fields'] as $field_name => $filter) {
                    if (isset($filter['values']) && is_array($filter['values']) && !empty($filter['values'])) {
                        $values['fields'][$field_name] = $filter['values'];
                    }
                }
                foreach ($filters['children'] as $child => $fields) {
                    if (!isset($values['children'][$child])) {
                        $values['children'][$child] = array();
                    }

                    foreach ($fields as $field_name => $filter)
                        if (isset($filter['values']) && is_array($filter['values']) && !empty($filter['values'])) {
                            $values['children'][$child][$field_name] = $filter['values'];
                        }
                }
            }

            if (count($values)) {
                $listFilters = BimpObject::getInstance('bimpcore', 'ListFilters');

                $errors = $listFilters->validateArray(array(
                    'owner_type' => $owner_type,
                    'name'       => (string) $data['name'],
                    'id_owner'   => $id_owner,
                    'obj_module' => $this->module,
                    'obj_name'   => $this->object_name,
                    'list_type'  => $data['list_type'],
                    'list_name'  => (isset($data['list_name']) ? (string) $data['list_name'] : 'default'),
                    'panel_name' => (isset($data['panel_name']) ? (string) $data['panel_name'] : 'default'),
                    'filters'    => $values
                ));

                if (!count($errors)) {
                    $errors = $listFilters->create($warnings);
                }
            } else {
                $errors[] = 'Aucun filtre sélectionné';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionGenerateListCsv($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Fichier généré avec succès';
        $success_callback = '';

//        $errors[] = 'En cours de développement';

        $list_name = (isset($data['list_name']) ? $data['list_name'] : '');
        $file_name = (isset($data['file_name']) ? $data['file_name'] : $this->getLabel() . '_' . data('Y-m-d'));
        $separator = (isset($data['separator']) ? $data['separator'] : ';');
        $headers = (isset($data['headers']) ? (int) $data['headers'] : 1);
        $col_options = (isset($data['cols_options']) ? $data['cols_options'] : array());
        $list_data = (isset($data['list_data']) ? $data['list_data'] : array());

        $list_data['param_n'] = 0;
        $list_data['param_p'] = 1;

        if (!$list_name) {
            $errors[] = 'Type de liste absent';
        }
        if (empty($list_data)) {
            $errors[] = 'Paramètres de la liste absent';
        }

        $dir = DOL_DATA_ROOT . '/bimpcore';
        $dir_error = BimpTools::makeDirectories(array(
                    'lists_csv' => array(
                        $this->module => array(
                            $this->object_name => $list_name
                        )
                    )
                        ), $dir);

        if ($dir_error) {
            $errors[] = $dir_error;
        } else {
            $dir .= '/lists_csv/' . $this->module . '/' . $this->object_name . '/' . $list_name;

            if (!file_exists($dir)) {
                $errors[] = 'Echec de la création du dossier "' . $dir . '"';
            }
        }

        if (!count($errors)) {
            $post_temp = $_POST;
            $_POST = $list_data;

            $list = new BC_ListTable($this, $list_name);

            if (count($list->errors)) {
                $errors = $list->errors;
            } else {
                set_time_limit(0);

                $content = $list->renderCsvContent($separator, $col_options, $headers, $errors);

                if ($content && !count($errors)) {
                    if (!file_put_contents($dir . '/' . $file_name . '.csv', $content)) {
                        $errors[] = 'Echec de la création du fichier "' . $file_name . '"';
                    } else {
                        $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('lists_csv/' . $this->module . '/' . $this->object_name . '/' . $list_name . '/' . $file_name . '.csv');
                        $success_callback = 'window.open(\'' . $url . '\')';
                    }
                } elseif (!count($errors)) {
                    $warnings[] = 'Aucun contenu à générer trouvé';
                }
            }

            $_POST = $post_temp;
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    // Divers: 

    public static function testMail($mail)
    {
        if (filter_var($mail, FILTER_VALIDATE_EMAIL))
            return true;
        return false;
    }

    public static function getDefaultEntrepot()
    {
        global $user;
        if (!isset($user->array_options)) {
            $user->fetch_optionals();
        }
        if (isset($user->array_options["options_defaultentrepot"]))
            return $user->array_options["options_defaultentrepot"];

        return 0;
    }

    public function processRedirect($newVersion = true)
    {
        $redirect = ((BimpTools::getValue("redirectForce") == 1) ? 1 : 0);
        $redirectMode = $this->redirectMode;
        $texteBtn = "";
        if (BimpTools::getValue("redirectForce_oldVersion"))
            $_SESSION['oldVersion'] = true;
        if ($this->iAmAdminRedirect()) {
            if ($redirectMode == 4 && (isset($_SESSION['oldVersion']) || !$newVersion)) {//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old
                $redirectMode = 1;
                $texteBtn = "ADMIN (N) : ";
            } elseif ($redirectMode == 5) {
                $redirectMode = 1;
                $texteBtn = "ADMIN (A) : ";
            }
        }
        $btn = false;
        if ($newVersion) {
            if ($redirect)
                unset($_SESSION['oldVersion']);
            if ($this->id > 0)
                $url = $this->getUrl();
            else
                $url = $this->getListUrl();
            $texteBtn .= "Nouvelle version";
            if ($redirectMode == 4)
                $redirect = true;
            elseif (in_array($redirectMode, array(3, 5)))
                $redirect = false;
            elseif ($redirectMode != 0)
                $btn = true;

            $search = null;
            if (BimpTools::getValue("sall") != "") {
                $search = BimpTools::getValue("sall");
            } elseif (BimpTools::getValue("search_all") != "") {
                $search = BimpTools::getValue("search_all");
            }
            if ($search) {
                $objName = "";
                if (isset($this->dol_object) && isset($this->dol_object->element))
                    $objName = $this->dol_object->element;
                if ($objName == "order_supplier")
                    $objName = "commande_fourn";
                if ($objName == "invoice_supplier")
                    $objName = "facture_fourn";
                $url .= "&search=1&object=" . $objName . "&sall=" . $search;
            }
            if (BimpTools::getValue("socid") != "") {
                $objName = "";
                if (isset($this->dol_object) && isset($this->dol_object->element))
                    $objName = $this->dol_object->element;
                $url .= "&socid=" . BimpTools::getValue("socid");
            }

//            https://erp.bimp.fr/test11/bimpcommercial/index.php?search=1&object=propal&sall=PR1809-91794&fc=propals
        }
        else {
            if ($redirect)
                $_SESSION['oldVersion'] = true;

            $url = BimpTools::getDolObjectUrl($this->dol_object, $this->id);
            $texteBtn .= "Ancienne version";
            if ($redirectMode == 5)
                $redirect = true;
            elseif (in_array($redirectMode, array(2, 4)))
                $redirect = false;
            elseif ($redirectMode != 0)
                $btn = true;
        }
        if ($redirect && $url != "") {
            $ob = ob_get_contents();
            if ($ob != "")
                die("<script>window.location = '" . $url . "';</script>");
            else {
                header("location: " . $url);
                die("<script>window.location = '" . $url . "';</script>");
            }
        } elseif ($btn && $url != "")
            return "<form method='POST'><input type='submit' class='btn btn-primary saveButton' name='redirige' value='" . $texteBtn . "'/><input type='hidden' name='redirectForce' value='1'/></form>";

        return '';
    }

    public function iAmAdminRedirect()
    {
        global $user;
//        return 0;
        if ($user->admin)
            return 1;
    }

    public static function priceToCsv($price)
    {
        return str_replace(array(" ", 'EUR', '€'), "", str_replace(".", ",", $price));
    }
}
