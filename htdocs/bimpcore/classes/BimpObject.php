<?php

class BimpObject extends BimpCache
{

    public $db = null;
    public $module = '';
    public $object_name = '';
    public $config = null;
    public $id = null;
//    public $asGraph = false; => remplacé par param yml "has_graph" 
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
    public static $logo_properties = array('logo');
    public $use_commom_fields = false;
    public $use_positions = false;
    public $params_defs = array(
        'abstract'                 => array('data_type' => 'bool', 'default' => 0),
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
        'parent_module'            => array('default' => ''),
        'parent_object'            => array('default' => ''),
        'parent_id_property'       => array('defautl' => ''),
        'positions'                => array('data_type' => 'bool', 'default' => 0),
        'position_insert'          => array('default' => 'before'),
        'labels'                   => array('type' => 'definitions', 'defs_type' => 'labels'),
        'objects'                  => array('type' => 'definitions', 'defs_type' => 'object_child', 'multiple' => true),
        'force_extrafields_update' => array('data_type' => 'bool', 'default' => 0),
        'nom_url'                  => array('type' => 'definitions', 'defs_type' => 'nom_url'),
        'has_graph'                => array('data_type' => 'bool', 'default' => 0),
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
    public $fieldsWithAddNoteOnUpdate = array();

    // Gestion instance:

    public static function getInstance($module, $object_name, $id_object = null, $parent = null)
    {
        $file = DOL_DOCUMENT_ROOT . '/' . $module . '/objects/' . $object_name . '.class.php';
        if (file_exists($file)) {
            if (!class_exists($object_name)) {
                require_once $file;
            }
            $className = $object_name;
            $fileEx = PATH_EXTENDS . "/" . $module . '/objects/' . $object_name . '.class.php';
            if (file_exists($fileEx)) {
                require_once $fileEx;
                if (class_exists($object_name . "Ex")) {
                    $className = $object_name . "Ex";
                }
            }
            $instance = new $className($module, $object_name);
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
//            $this->config->params['fields']['date_create'] = array(
//                'label'    => 'Créé le',
//                'type'     => 'datetime',
//                'input'    => array(
//                    'type' => 'hidden'
//                ),
//                'editable' => 0
//            );
            if (!isset($this->config->params['fields']['date_create']['label']))
                $this->config->params['fields']['date_create']['label'] = 'Créé le';
            if (!isset($this->config->params['fields']['date_create']['type']))
                $this->config->params['fields']['date_create']['type'] = 'datetime';
            if (!isset($this->config->params['fields']['date_create']['input']))
                $this->config->params['fields']['date_create']['input'] = array('type' => 'hidden');
            if (!isset($this->config->params['fields']['date_create']['editable']))
                $this->config->params['fields']['date_create']['editable'] = 0;


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
        if (empty($this->params)) {
            $primary = $this->getConf('primary', '');
        } else {
            $primary = $this->params['primary'];
        }

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
        $property = isset($this->params['parent_id_property']) ? $this->params['parent_id_property'] : null;

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
        return $this->getConf('parent_object', '');
    }

    public function getParentModule()
    {
        return $this->getConf('parent_module', $this->module);
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

    public function getParentData($field)
    {
        $parent = $this->getParentInstance();
        if (BimpObject::objectLoaded($parent) && is_a($parent, 'BimpObject')) {
            return $parent->getData($field);
        }

        return null;
    }

    public function getFilesDir()
    {
        if ($this->isLoaded()) {
            return DOL_DATA_ROOT . '/bimpcore/' . $this->module . '/' . $this->object_name . '/' . $this->id . '/';
        }

        return '';
    }

    public function getFileUrl($file_name, $page = 'document')
    {
        if (!$file_name) {
            return '';
        }

        if (!$this->isLoaded()) {
            return '';
        }

        $file = $this->module . '/' . $this->object_name . '/' . $this->id . '/' . $file_name;

        return DOL_URL_ROOT . '/' . $page . '.php?modulepart=bimpcore&file=' . urlencode($file);
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
        foreach (static::$ref_properties as $prop) {
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

    public function getLogoProperty()
    {
        foreach (self::$logo_properties as $prop) {
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

    public function getSearchParams($search_name = 'default')
    {
        $fields_search = array();
        $fields_return = array();
        $joins = array();
        $filters = array();
        $syntaxe = '';
        $primary = $this->getPrimary();
        $ref_prop = $this->getRefProperty();
        $name_prop = $this->getNameProperty();

        $order_by = '';
        $order_way = 'asc';

        if ($search_name === '') {
            $search_name = 'default';
        }

        $path = 'searches/' . $search_name;
        if ($this->config->isDefined($path)) {
            $fields_search = $this->getConf($path . '/fields_search', array(), true, 'array');
            $fields_return = $this->getConf($path . '/fields_return', array(), true, 'array');
            $syntaxe = $this->getConf($path . '/label_syntaxe', '');
            $joins = $this->config->getCompiledParams($path . '/joins');
            $filters = $this->config->getCompiledParams($path . '/filters');
            $order_by = $this->getConf($path . '/order_by', '');
            $order_way = $this->getConf($path . '/order_way', 'asc');

            if (!$order_by) {
                if ($ref_prop) {
                    $order_by = 'a.' . $ref_prop;
                } elseif ($name_prop) {
                    $order_by = 'a.' . $name_prop;
                } else {
                    $order_by = 'a.' . $primary;
                }
            }

            foreach ($fields_search as $key => $field) {
                if (!preg_match('/^(.+)\.(.+)/', $field)) {
                    $fields_search[$key] = 'a.' . $field;
                }
            }

            foreach ($fields_return as $key => $field) {
                if (!preg_match('/^(.+)\.(.+)/', $field)) {
                    $fields_return[$key] = 'a.' . $field;
                }
            }

            if (!in_array($fields_return, array('a.' . $primary))) {
                $fields_return[] = 'a.' . $primary;
            }
        } else {
            $has_extrafields = false;
            $fields_search[] = 'a.' . $primary;
            $fields_return[] = 'a.' . $primary;

            if ($ref_prop) {
                if ($this->isDolObject() && (int) $this->getConf('fields/' . $ref_prop . '/dol_extra_field', 0, false, 'bool')) {
                    if (preg_match('/^ef_(.*)$/', $ref_prop, $matches)) {
                        $ref_prop = $matches[1];
                    }
                    $ref_prop = 'ef.' . $ref_prop;
                    $has_extrafields = true;
                } else {
                    $ref_prop = 'a.' . $ref_prop;
                }
                $fields_search[] = $ref_prop;
                $fields_return[] = $ref_prop;
                $syntaxe .= '<' . $ref_prop . '>';
                $order_by = 'a.' . $ref_prop;
            }

            if ($name_prop) {
                if ($this->isDolObject() && (int) $this->getConf('fields/' . $name_prop . '/dol_extra_field', 0, false, 'bool')) {
                    if (preg_match('/^ef_(.*)$/', $name_prop, $matches)) {
                        $name_prop = $matches[1];
                    }
                    $name_prop = 'ef.' . $name_prop;
                    $has_extrafields = true;
                } else {
                    $name_prop = 'a.' . $name_prop;
                }
                $fields_search[] = $name_prop;
                $fields_return[] = $name_prop;

                $syntaxe .= ($syntaxe ? ' - ' : '') . '<' . $name_prop . '>';

                if (!$order_by) {
                    $order_by = 'a.' . $name_prop;
                }
            }

            if (!$order_by) {
                $order_by = 'a.' . $primary;
            }

            if ($has_extrafields) {
                $joins['ef'] = array(
                    'alias' => 'ef',
                    'table' => $this->getTable() . '_extrafields',
                    'on'    => 'a.rowid = ef.fk_object'
                );
            }

            $filters = $this->getSearchListFilters($joins);
        }

        return array(
            'fields_search' => $fields_search,
            'fields_return' => $fields_return,
            'filters'       => $filters,
            'joins'         => $joins,
            'label_syntaxe' => $syntaxe,
            'order_by'      => $order_by,
            'order_way'     => $order_way
        );
    }

    public function getSearchResults($search_name, $search_value, $options = array())
    {
        $results = array();

        if ((string) $search_value) {
            $search_value = $this->db->db->escape(strtolower($search_value));

            $params = $this->getSearchParams($search_name);

            $card = (isset($options['card']) ? $options['card'] : '');
            $filters = (isset($params['filters']) && is_array($params['filters']) ? $params['filters'] : array());
            $joins = (isset($params['joins']) && is_array($params['joins']) ? $params['joins'] : array());
            $primary = $this->getPrimary();

            $search_filter = '';

            foreach (explode(' ', $search_value) as $search) {
                $or_sql = '';

                foreach ($params['fields_search'] as $field) {
                    $or_sql .= ($or_sql ? ' OR ' : '') . 'LOWER(' . $field . ') LIKE \'%' . $search . '%\'';
                }

                if ($or_sql) {
                    $search_filter .= ($search_filter ? ' AND ' : '') . '(' . $or_sql . ')';
                }
            }

            if ($search_filter) {
                $filters['search_custom'] = array(
                    'custom' => '(' . $search_filter . ')'
                );
            }

            $max_results = (isset($options['max_results']) ? (int) $options['max_results'] : 200);

            $rows = $this->getList($filters, $max_results, 1, $params['order_by'], $params['order_way'], 'array', $params['fields_return'], $joins);

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $label = $params['label_syntaxe'];
                    $card_html = '';

                    foreach ($params['fields_return'] as $field) {
                        $field_name = $field;
                        if (preg_match('/^.* as (.*)$/', $field, $matches)) {
                            $field_name = $matches[1];
                        } elseif (preg_match('/^.+\.(.+)$/', $field, $matches)) {
                            $field_name = $matches[1];
                        }
                        if ($params['label_syntaxe']) {
                            if (strpos($label, '<' . $field . '>') !== false) {
                                if (isset($r[$field_name])) {
                                    $label = str_replace('<' . $field . '>', $r[$field_name], $label);
                                } else {
                                    $label = str_replace('<' . $field . '>', '', $label);
                                }
                            } elseif (strpos($label, '<' . $field_name . '>') !== false) {
                                if (isset($r[$field_name])) {
                                    $label = str_replace('<' . $field_name . '>', $r[$field_name], $label);
                                } else {
                                    $label = str_replace('<' . $field_name . '>', '', $label);
                                }
                            }
                        } elseif ($field_name !== $primary) {
                            if (isset($r[$field_name]) && !empty($r[$field_name])) {
                                $label .= ($label ? ' - ' : '') . $r[$field_name];
                            }
                        }
                    }

                    if (preg_match('/^(.*)\[REDIFNEG\](.*)\[\/REDIFNEG\](.*)$/U', $label, $matches)) {
                        if ((float) $matches[2] < 0) {
                            $label = $matches[1] . '<span class="danger">' . (float) $matches[2] . '</span>' . $matches[3];
                        } else {
                            $label = $matches[1] . (float) $matches[2] . $matches[3];
                        }
                    }

                    if ($card) {
                        $instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $r[$primary]);
                        if (BimpObject::objectLoaded($instance)) {
                            $bc_card = new BC_Card($instance, null, $card);
//                            $bc_card->setParam('view_btn', 0);
                            $card_html = addslashes(htmlentities($bc_card->renderHtml()));
                        }
                    }

                    $results[(int) $r[$primary]] = array(
                        'id'    => (int) $r[$primary],
                        'label' => $label,
                        'card'  => $card_html
                    );
                }
            }
        }

        return $results;
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
        if ($field_name === $this->getPrimary()) {
            return 1;
        }

        if (!isset($this->params['fields']) || !$this->isFieldActivated($field_name)) {
            return 0;
        }

        $field_tmp = $field_name;
        if ($this->isDolObject() && $this->isDolExtraField($field_tmp)) {
            $extra_fields = self::getExtraFieldsArray($this->dol_object->table_element);

            if (!is_array($extra_fields) || !isset($extra_fields[$field_tmp])) {
                return 0;
            }
        }

        return (in_array($field_name, $this->params['fields']) || ($this->use_commom_fields && in_array($field_name, self::$common_fields)));
    }

    public function isExtraField($field_name)
    {
        return (int) $this->getConf('fields/' . $field_name . '/extra', 0);
    }

    public function dol_field_exists($field_name)
    {
        if (!$this->field_exists($field_name)) {
            return 0;
        }

        if ($this->isDolObject()) {
            if ((int) $this->getConf('fields/' . $field_name . '/dol_extra_field', 0, false, 'bool')) {
                if (preg_match('/^ef_(.+)$/', $field_name, $matches)) {
                    $field_name = $matches[1];
                }

                $extra_fields = self::getExtraFieldsArray($this->dol_object->table_element);
                if (!is_array($extra_fields) || !isset($extra_fields[$field_name])) {
                    return 0;
                }
            }
        }

        return 1;
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
        if (!$instance->isLoaded()) {
            if ($instance->config->isDefined('parent_module/field_value')) {
                $parent_module_prop = $instance->getConf('parent_module/field_value', '');
                if ($parent_module_prop && $instance->field_exists($parent_module_prop) && !(string) $instance->getData($parent_module_prop)) {
                    $instance->set($parent_module_prop, $this->module);
                    $instance->params['parent_module'] = $this->module;
                }
            }
            if ($instance->config->isDefined('parent_object/field_value')) {
                $parent_object_name_prop = $instance->getConf('parent_object/field_value', '');
                if ($parent_object_name_prop && $instance->field_exists($parent_object_name_prop) && !(string) $instance->getData($parent_object_name_prop)) {
                    $instance->set($parent_object_name_prop, $this->object_name);
                    $instance->params['parent_object'] = $this->object_name;
                }
            }
        }

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

    public function hasDataChanged($field)
    {
        return (int) ($this->getData($field) !== $this->getInitData($field));
    }

    // Getters données: 

    public function getData($field)
    {
        if ($field === $this->getPrimary() || $field === 'id') {
            return $this->id;
        }

        if (isset($this->data[$field])) {
            return $this->data[$field];
        }

        return $this->getConf('fields/' . $field . '/default_value', null, false, 'any');
    }

    public function getInitData($field)
    {
        if ($field === 'id') {
            return $this->id;
        }

        if (isset($this->initData[$field])) {
            return $this->initData[$field];
        }

        return $this->getConf('fields/' . $field . '/default_value', null, false, 'any');
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

            if (!$this->field_exists($field) || (!is_null($fields) && !empty($fields) && !in_array($field, $fields))) {
                continue;
            }

            if ($this->isExtraField($field)) {
                continue;
            }

            if (!is_null($value)) {
                $db_value = $this->getDbValue($field, $value);
                if (!is_null($db_value)) {
                    $this->checkFieldHistory($field, $value);
                    $data[$field] = $db_value;
                }
            }
        }

        return $data;
    }

    public function getDbValue($field_name, $value)
    {
        if ($this->field_exists($field_name)) {
            $this->checkFieldValueType($field_name, $value);

            $field_type = $this->getConf('fields/' . $field_name . '/type', 'string');

            if ($field_type === 'items_list') {
                if ((int) $this->getConf('fields/' . $field_name . '/items_braces', 0)) {
                    if (!is_array($value)) {
                        $value = array($value);
                    }

                    $values = $value;
                    $value = '';

                    foreach ($values as $val) {
                        $value .= '[' . $val . ']';
                    }
                } elseif (is_array($value)) {
                    $delimiter = $this->getConf('fields/' . $field_name . '/items_delimiter', ',');
                    $value = implode($delimiter, $value);
                }
            }
        }

        return $value;
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
                    $value = $this->getFilesArray();
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
            return '#' . $this->id;
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

    public function getLogoUrl($format = '', $preview = false)
    {
        $url = '';
        $logo_prop = $this->getLogoProperty();
        $dir = $this->getFilesDir();

        if ($logo_prop && $dir) {
            $file = $this->getData($logo_prop);

            $file_infos = pathinfo($file);
            $ext = $file_infos['extension'];
            $name = $file_infos['filename'];

            $file_path = '';

            if ($format) {
                $file_path = 'thumbs/' . $name . '_' . $format . '.' . $ext;
            } else {
                $file_path = $file;
            }

            if ($ext && file_exists($dir . '/logos/' . $file_path)) {
                $url = $this->getFileUrl('logos/' . $file_path, 'viewimage');

                if ($url && $preview) {
                    $url = 'javascript:document_preview(\'' . $url . '\', \'image/' . $ext . '\', \'Aperçu\');';
                }
            }
        }

        return $url;
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
                case 'items_list':
                    if (isset($value[0]) && $value[0] == '')
                        unset($value[0]);
                    if (is_array($value))
                        $value = implode(",", $value);
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

    public function getTaxeIdDefault()
    {
        return (int) BimpCore::getConf("tva_default");
    }

    public function getDefaultTva()
    {
        global $mysoc;
        // If France, show VAT mention if not applicable
        if ($mysoc->tva_assuj)
            return 20;
        else
            return 0;
    }

    public function getInfoGraph()
    {
        return array(
            array("data1" => "Donnée", "axeX" => "X", "axeY" => "Y", 'title' => $this->getLabel()));
    }

    public function getGraphDataPoint()
    {
        return '';
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
                    $status_prop = $this->getStatusProperty();

                    if (!$status_prop) {
                        $errors[] = 'Erreur technique: propriété contenant le statut non touvée';
                    } else {
                        $current_status = (int) $this->getSavedData($status_prop);

                        if ($current_status === $new_status) {
                            $errors[] = $object_label . ' a déjà le statut "' . $status_label . '"';
                        } elseif (method_exists($this, 'onNewStatus')) {
                            $errors = $this->onNewStatus($new_status, $current_status, $extra_data, $warnings);
                        }

                        if (!count($errors)) {
                            $this->set($status_prop, $new_status);
                            $errors = $this->update($warnings, true);
                        }
                    }
                }
            }
        }

        BimpLog::actionEnd('bimpobject_new_status', $errors);

        return $errors;
    }

    public function setObjectAction($action, $id_object = 0, $extra_data = array(), &$success = '', $force_action = false)
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
            if (!$force_action && !$this->canSetAction($action)) {
                $errors[] = 'Vous n\'avez pas la permission d\'effectuer cette action (' . $action . ')';
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
                            $filters = BimpTools::merge_array($filters, $instance->getSearchFilters($joins, $child_fields, $child_name));
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

                $method = 'get' . ucfirst($field_name) . 'SearchFilters';
                if (method_exists($this, $method)) {
                    $this->{$method}($filters, $value, $joins, $alias);
                    continue;
                }

                $key_errors = array();
                $filter_key = $this->getFieldSqlKey($field_name, $alias, null, $filters, $joins, $key_errors);

                if (!count($key_errors)) {
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
            $errors = BimpTools::merge_array($errors, $bimpHistory->add($this, $field, $value));
        }

        $this->history = array();
        return $errors;
    }

    public function checkObject($context = '', $field = '')
    {
        // Attention au risque de boucle infinie lors de la redéfinition de cette fonction en cas 
        // d'appel à $this->update(), $this->create(), $this->updateField() ou $this->fetch(). 
        // Utiliser $context pour connaître l'origine de l'appel à cette fonction (create, update, updateField, fetch (uniquement via BimpCache::getBimpObjectInstance())). 
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
                    if ((string) $value) {
                        if (stripos($value, "-") || stripos($value, "/")) {
                            $value = $this->db->db->jdate($value);
                        }

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
            }

            // Traitement des cas particuliers des listes de valeurs: 
            if ($type === 'items_list') {
                if (is_string($value)) {
                    if ($this->getConf('fields/' . $field . '/items_braces', 0)) {
                        $value = str_replace('][', ',', $value);
                        $value = str_replace('[', '', $value);
                        $value = str_replace(']', '', $value);
                        $value = explode(',', $value);
                    } else {
                        $delimiter = $this->getConf('fields/' . $field . '/items_delimiter', ',');
                        $value = explode($delimiter, $value);
                    }
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

    public function onSave(&$errors = array(), &$warnings = array())
    {
        
    }

    public function addConfigExtraParams()
    {
        
    }

    public function fetch_thirdparty()
    {
        $tabPossible = array('societe', 'client');

        foreach ($tabPossible as $posible) {
            $temp = $this->getChildObject($posible);
            if (is_object($temp) && $temp->isLoaded()) {
                $this->thirdparty = $temp->dol_object;
            }
        }

        return false;
    }

    // Gestion SQL: 

    public function getChildJoin($child_name, &$filters = array(), &$joins = array(), &$alias = '', $main_alias = 'a')
    {
        $errors = array();

        $child_id_prop = '';
        $relation = 'hasOne';

        if ($child_name === 'parent') {
            $child_id_prop = $this->getConf('parent_id_property', '');
        } else {
            if (!$this->config->isDefined('objects/' . $child_name)) {
                $errors[] = 'L\'objet enfant "' . $child_name . '" n\'existe pas';
            } else {
                $relation = $this->getConf('objects/' . $child_name . '/relation', 'none');

                switch ($relation) {
                    case 'hasOne':
                        $child_id_prop = $this->getConf('objects/' . $child_name . '/instance/id_object/field_value', '');
                        break;

                    case 'hasMany':
                        break;

                    default:
                        $errors[] = 'Objet "' . $this->getLabel() . '": type de relation invalide pour l\'objet "' . $child_name . '"';
                        break;
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        $child = $this->getChildObject($child_name);
        if (!is_a($child, 'BimpObject')) {
            $errors[] = 'Objet endant "' . $child_name . '" invalide';
        } else {
            switch ($relation) {
                case 'hasOne':
                    if (!$child_id_prop) {
                        $errors[] = 'Champ contenant l\'ID absent';
                    } else {
                        $child_id_prop_sql_key = $this->getFieldSqlKey($child_id_prop, $main_alias, null, $filters, $joins, $errors);

                        if (!$child_id_prop_sql_key || !empty($errors)) {
                            return $errors;
                        }

                        $alias = ($main_alias ? $main_alias . '___' : '') . $child_name;
                        if (!isset($joins[$alias])) {
                            $joins[$alias] = array(
                                'table' => $child->getTable(),
                                'alias' => $alias,
                                'on'    => $alias . '.' . $child->getPrimary() . ' = ' . $child_id_prop_sql_key
                            );
                        }
                    }
                    break;

                case 'hasMany':
                    if (!$this->isChild($child)) {
                        $errors[] = 'Objet "' . $this->getLabel() . '": relation de parenté invalide pour l\'objet "' . $child_name . '"';
                    } else {
                        $child_id_parent_property = $child->getParentIdProperty();
                        $alias = ($main_alias ? $main_alias . '___' : '') . $child_name;
                        if (!isset($joins[$alias])) {
                            $joins[$alias] = array(
                                'table' => $child->getTable(),
                                'alias' => $alias,
                                'on'    => $alias . '.' . $child_id_parent_property . ' = ' . $main_alias . '.' . $this->getPrimary()
                            );

                            if ($child->config->isDefined('parent_module/field_value')) {
                                $parent_module_prop = $child->getConf('parent_module/field_value', '');
                                if ($parent_module_prop) {
                                    $filters[$alias . '.' . $parent_module_prop] = $this->module;
                                }
                            }
                            if ($child->config->isDefined('parent_object/field_value')) {
                                $parent_object_name_prop = $child->getConf('parent_object/field_value', '');
                                if ($parent_object_name_prop) {
                                    $filters[$alias . '.' . $parent_object_name_prop] = $this->object_name;
                                }
                            }

                            if ($this->config->isDefined('objects/' . $child_name . '/list/filters')) {
                                $child_filters = $this->config->getCompiledParams('objects/' . $child_name . '/list/filters');

                                foreach ($child_filters as $field_name => $filter_data) {
                                    if (!isset($filters[$alias . '.' . $field_name])) {
                                        $filters[$alias . '.' . $field_name] = $filter_data;
                                    }
                                }
                            }
                        }
                    }
                    break;
            }
        }

        return $errors;
    }

    public function getChildrenJoins($children, &$filters = array(), &$joins = array(), $main_alias = 'a')
    {
        if (!is_array($children) || empty($children)) {
            return array();
        }

        $errors = array();

        foreach ($children as $child_name) {
            $alias = '';
            $child_errors = $this->getChildJoin($child_name, $filters, $joins, $alias, $main_alias);

            if (count($child_errors)) {
                $errors[] = BimpTools::getMsgFromArray($child_errors, 'Objet enfant "' . $child_name . '"');
            }
        }

        return $errors;
    }

    public function getRecursiveChildrenJoins($children, &$filters = array(), &$joins = array(), $main_alias = 'a', &$final_alias = '', &$final_object = null)
    {
        // Intègre les jointures d'un succession d'objets, chaque objet étant l'enfant du précédant. 

        if (!is_array($children) || empty($children)) {
            return array();
        }

        $errors = array();
        $new_filters = array();
        $new_joins = array();
        $child_alias = '';
        $child_name = array_shift($children);

        if ($child_name) {
            $child = $this->getChildObject($child_name);

            if (is_a($child, 'BimpObject')) {
                $errors = $this->getChildJoin($child_name, $new_filters, $new_joins, $child_alias, $main_alias);

                if (empty($errors)) {
                    $final_object = $child;
                    $final_alias = $child_alias;

                    if (!empty($children)) {
                        $errors = $child->getRecursiveChildrenJoins($children, $new_filters, $new_joins, $child_alias, $final_alias, $final_object);
                    }
                }

                if (!count($errors)) {
                    if (!empty($new_filters)) {
                        foreach ($new_filters as $field => $new_filter) {
                            $filters = BimpTools::mergeSqlFilter($filters, $field, $new_filter);
                        }
                    }

                    if (!empty($new_joins)) {
                        foreach ($new_joins as $alias => $new_join) {
                            if (!isset($joins[$alias])) {
                                $joins[$alias] = $new_join;
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function getFieldSqlKey($field, $main_alias = 'a', $child_name = null, &$filters = array(), &$joins = array(), &$errors = array(), $child_object = null)
    {
        $error_title = 'Echec de l\'obtention de la clé SQL pour le champ "' . $field . '" - Objet "' . $this->getLabel() . '"' . ($child_name ? ' - Enfant "' . $child_name . '"' : '');

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

            $id_prop = $this->getChildIdProperty($child_name);
            $id_prop_sql_key = $this->getFieldSqlKey($id_prop, $main_alias, null, $filters, $joins, $errors);

            if (!is_object($child_object)) {
                $errors[] = BimpTools::getMsgFromArray('Instance enfant invalide', $error_title);
                return '';
            }

            if (is_a($child_object, 'BimpObject')) {
                if ($relation === 'hasOne' || $id_prop) {
                    if (!is_string($id_prop) || !$id_prop) {
                        $msg = 'Propriété contenant l\'ID de l\'objet "' . $child_object->getLabel() . '" absente ou invalide';
                        $errors[] = BimpTools::getMsgFromArray($msg, $error_title);
                        return '';
                    }

                    if (is_a($child_object, 'BimpObject')) {
                        $alias = ($main_alias ? $main_alias . '_' : '') . $child_name;
                        if ($child_object->isDolExtraField($field)) {
                            $alias .= '_ef';
                            if (!isset($joins[$alias])) {
                                $joins[$alias] = array(
                                    'table' => $child_object->getTable() . '_extrafields',
                                    'on'    => $alias . '.fk_object = ' . $id_prop_sql_key,
                                    'alias' => $alias
                                );
                            }
                            return $alias . '.' . $field;
                        } else {
                            if (!isset($joins[$alias])) {
                                $joins[$alias] = array(
                                    'table' => $child_object->getTable(),
                                    'on'    => $alias . '.' . $child_object->getPrimary() . ' = ' . $id_prop_sql_key,
                                    'alias' => $alias
                                );
                            }
                            if ($child_object->isExtraField($field)) {
                                return $child_object->getExtraFieldFilterKey($field, $joins, $alias, $filters);
                            } else {
                                return $alias . '.' . $field;
                            }
                        }
                    }
                } elseif ($relation === 'hasMany') {
                    if ($this->isChild($child_object)) {
                        $parent_id_prop = $child_object->getParentIdProperty();
                        if (!$parent_id_prop) {
                            $msg = 'Propriété de l\'ID parent absent pour l\'objet "' . $child_object->getLabel() . '"';
                            $errors[] = BimpTools::getMsgFromArray($msg, $error_title);
                            return '';
                        }

                        $alias = ($main_alias ? $main_alias . '_' : '') . $child_name;
                        if (!isset($joins[$alias])) {
                            $joins[$alias] = array(
                                'table' => $child_object->getTable(),
                                'alias' => $alias,
                                'on'    => $main_alias . '.' . $this->getPrimary() . ' = ' . $alias . '.' . $parent_id_prop
                            );
                        }

                        if ($child_object->isDolExtraField($field)) {
                            $sub_alias = $alias . '_ef';
                            if (!isset($joins[$sub_alias])) {
                                $joins[$sub_alias] = array(
                                    'table' => $child_object->getTable() . '_extrafields',
                                    'alias' => $sub_alias,
                                    'on'    => $sub_alias . '.fk_object = ' . $alias . '.' . $child_object->getPrimary()
                                );
                            }
                            return $sub_alias . '.' . $field;
                        } elseif ($child_object->isExtraField($field)) {
                            return $child_object->getExtraFieldFilterKey($field, $joins, $alias, $filters);
                        } else {
                            return $alias . '.' . $field;
                        }
                    } else {
                        $msg = 'Erreur: l\'objet "' . $child_object->getLabel() . '" doit être enfant de "' . $this->getLabel() . '"';
                        $errors[] = BimpTools::getMsgFromArray($msg, $error_title);
                    }
                } else {
                    $msg = 'Type de relation invalide pour l\'objet "' . $child_object->getLabel() . '"';
                    $errors[] = BimpTools::getMsgFromArray($msg, $error_title);
                    return '';
                }
            } else {
                $child_table = BimpTools::getObjectTable($this, $id_prop, $child_object);
                if (is_null($child_table) || !(string) $child_table) {
                    $msg = 'Nom de la table de l\'objet enfant "' . $child_name . '" non obtenue';
                    $errors[] = BimpTools::getMsgFromArray($msg, $error_title);
                    return '';
                }
                $child_primary = BimpTools::getObjectPrimary($this, $id_prop, $child_object);
                if (is_null($child_primary) || !(string) $child_primary) {
                    $msg = 'Champ primaire de l\'objet enfant "' . $child_name . '" non obtenu';
                    $errors[] = BimpTools::getMsgFromArray($msg, $error_title);
                    return '';
                }

                $alias = ($main_alias ? $main_alias . '_' : '') . $child_name;
                if (!isset($joins[$alias])) {
                    $joins[$alias] = array(
                        'table' => $child_table,
                        'on'    => $alias . '.' . $child_primary . ' = ' . $id_prop_sql_key,
                        'alias' => $alias
                    );
                }

                return $alias . '.' . $field;
            }
        } elseif ($this->field_exists($field)) {
            if ($this->isDolExtraField($field)) {
                $alias = ($main_alias ? $main_alias . '_' : '') . '_ef';
                if (!isset($joins[$alias])) {
                    $joins[$alias] = array(
                        'table' => $this->getTable() . '_extrafields',
                        'on'    => $main_alias . '.' . $this->getPrimary() . ' = ' . $alias . '.fk_object',
                        'alias' => $alias
                    );
                }
                return $alias . '.' . $field;
            } elseif ($this->isExtraField($field)) {
                return $this->getExtraFieldFilterKey($field, $joins, $main_alias, $filters);
            } else {
                return $main_alias . '.' . $field;
            }
        } else {
            $msg = 'Le champ "' . $field . '" n\'existe pas pour les ' . $this->getLabel('name_plur');
            $errors[] = BimpTools::getMsgFromArray($msg, $error_title);
        }

        if (count($errors)) {
            BimpCore::addlog('Echec obtention clé SQL pour le champ "' . $field . '"', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', $this, array(
                'Champ'        => $field,
                'Objet enfant' => (!is_null($child_name) && (string) $child_name ? $child_name : 'aucun'),
                'Erreurs'      => $errors
            ));
        }

        return '';
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
                        $extra_filters = array();
                        $key = $this->getExtraFieldFilterKey($field_name, $joins, $main_alias, $extra_filters);
                        if ($key) {
                            $return[$key] = $filter;

                            if (!empty($extra_filters)) {
                                foreach ($extra_filters as $extra_filter_key => $extra_filter) {
                                    $return = BimpTools::mergeSqlFilter($return, $extra_filter_key, $extra_filter);
                                }
                            }
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

    // Gestion des associations: 

    public function getAssociatesList($association)
    {
        if (isset($this->associations[$association])) {
            return $this->associations[$association];
        }

        $this->associations[$association] = array();

        if (!$this->isLoaded()) {
            return array();
        }

        if ($this->config->isDefined('associations/' . $association)) {
            $asso = new BimpAssociation($this, $association);
            $this->associations[$association] = $asso->getAssociatesList();
            unset($asso);
        }

        return $this->associations[$association];
    }

    public function getAssociatesObjects($association)
    {
        // Ne fonctionne qu'avec des associés BimpObject
        $associates = array();

        $asso = new BimpAssociation($this, $association);

        if (!count($asso->errors) && is_object($asso->associate)) {
            if (isset($this->associations[$association])) {
                $list = $this->associations[$association];
            } else {
                $list = $this->getAssociatesList($association);
            }

            if (!empty($list)) {
                if (is_a($asso->associate, 'BimpObject')) {
                    foreach ($list as $id_object) {
                        $obj = BimpCache::getBimpObjectInstance($asso->associate->module, $asso->associate->object_name, $id_object);
                        if (BimpObject::objectLoaded($obj)) {
                            $associates[] = $obj;
                        }
                    }
                }
            }
        }

        return $associates;
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
                            $errors = BimpTools::merge_array($errors, $assos_errors);
                        }
                        unset($bimpAsso);
                    } elseif (isset($params->id_associate)) {
                        $bimpAsso = new BimpAssociation($this, $params->association);
                        $assos_errors = $bimpAsso->addObjectAssociation($params->id_associate, $this->id);
                        if ($assos_errors) {
                            $errors[] = 'Echec de l\'association ' . $this->getLabel('of_the') . ' avec ' . BimpObject::getInstanceLabel($bimpAsso->associate, 'the') . ' ' . $params->id_associate;
                            $errors = BimpTools::merge_array($errors, $assos_errors);
                        }
                        unset($bimpAsso);
                    }
                }
            }
        }

        return $errors;
    }

    public function addAssociates($association, $associates_ids)
    {
        $errors = array();

        $asso = new BimpAssociation($this, $association);

        if (count($asso->errors)) {
            $errors = $asso->errors;
        } else {
            foreach ($associates_ids as $id) {
                $errors = array_merge($errors, $asso->addObjectAssociation($id));
            }
        }

        unset($asso);

        return $errors;
    }

    // Gestion des filtres custom: 

    public function getCustomFilterValueLabel($field_name, $value)
    {
        return $value;
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
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

    public function getChildLabel($child_name)
    {
        $child_label = $this->getConf('objects/' . $child_name . '/label', '');

        if (!$child_label) {
            $child_label = $this->getConf('objects/' . $child_name . '/list/title', '');
        }

        if (!$child_label) {
            $child_id_prop = $this->getConf('objects/' . $child_name . '/instance/id_object/field_value', '');
            if ($child_id_prop) {
                $child_label = $this->getConf('fields/' . $child_id_prop . '/label', '');
            }

            if (!$child_label) {
                $child = $this->getChildObject($child_name);

                if (is_a($child, 'BimpObject')) {
                    $child_label = BimpTools::ucfirst($child->getLabel());
                } elseif (is_object($child)) {
                    $child_label = get_class($child);
                } else {
                    $child_label = $child_name;
                }
            }
        }

        return $child_label;
    }

    public function getChildrenList($object_name, $filters = array(), $order_by = 'id', $order_way = 'asc')
    {
        $children = array();
        if ($this->isLoaded()) {
            if ($this->object_exists($object_name)) {
                $relation = $this->getConf('objects/' . $object_name . '/relation', '', true);

                if ($relation !== 'hasMany') {
                    BimpCore::addlog('Erreur getChildrenList()', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', $this, array(
                        'Child name' => $object_name,
                        'Erreur'     => 'Relation invalide (Doit être de type "hasMany")'
                    ));
                    return array();
                }

                $instance = $this->config->getObject('', $object_name);
                if (is_a($instance, 'BimpObject')) {
                    // Filtres config: 
                    $list_filters = $this->config->getCompiledParams('objects/' . $object_name . '/list/filters');
                    if (!is_null($list_filters)) {
                        foreach ($list_filters as $field => $filter) {
                            $filters = BimpTools::mergeSqlFilter($filters, $field, $filter);
                        }
                    }

                    // Filtre ID parent: 
                    if ($this->isChild($instance)) {
                        $filters[$instance->getParentIdProperty()] = $this->id;
                    }

                    // Filtres module / object_name parent: 
                    if ($instance->config->isDefined('parent_module/field_value')) {
                        $parent_module_prop = $instance->getConf('parent_module/field_value', '');
                        if ($parent_module_prop && $instance->field_exists($parent_module_prop)) {
                            if (!isset($filters[$parent_module_prop])) {
                                $filters[$parent_module_prop] = $this->module;
                            }
                        }
                    }

                    if ($instance->config->isDefined('parent_object/field_value')) {
                        $parent_object_name_prop = $instance->getConf('parent_object/field_value', '');
                        if ($parent_object_name_prop && $instance->field_exists($parent_object_name_prop)) {
                            if (!isset($filters[$parent_object_name_prop])) {
                                $filters[$parent_object_name_prop] = $this->object_name;
                            }
                        }
                    }

                    if (empty($filters)) {
                        BimpCore::addlog('Erreur getChildrenList()', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', $this, array(
                            'Child name' => $object_name,
                            'Erreur'     => 'Aucun filtre'
                        ));
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
        return $children;
    }

    public function getChildrenObjects($object_name, $filters = array(), $order_by = 'id', $order_way = 'asc', $use_id_as_key = false)
    {
        $children = array();
        if ($this->isLoaded()) {
            if ($this->object_exists($object_name)) {
                $instance = $this->config->getObject('', $object_name);
                if (is_a($instance, 'BimpObject')) {
                    $list = $this->getChildrenList($object_name, $filters, $order_by, $order_way);

                    foreach ($list as $id_child) {
                        $child = BimpCache::getBimpObjectInstance($instance->module, $instance->object_name, (int) $id_child, $this);
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
        return $children;
    }

    public function getChildrenListArray($object_name, $filters = array(), $include_empty = 0, $order_by = 'id', $order_way = 'desc', $display_name = 'ref_nom', $display_options = array())
    {
        if ($this->isLoaded()) {
            $cache_key = $this->module . '_' . $this->object_name . '_' . $this->id . '_' . $object_name . '_list_array_by_' . $order_by . '_' . $order_way;
            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();

                $children = $this->getChildrenObjects($object_name, $filters, $order_by, $order_way, false);

                foreach ($children as $child) {
                    self::$cache[$cache_key][(int) $child->id] = $child->display($display_name, $display_options);
                }
            }

            return self::getCacheArray($cache_key, $include_empty);
        }

        return array();
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
        if (is_array($return_fields)) {
            foreach ($return_fields as $key => $field) {
                if ($this->field_exists($field)) {
                    if ($is_dol_object && $this->isDolExtraField($field)) {
                        if (preg_match('/^ef_(.*)$/', $field, $matches)) {
                            $field = $matches[1];
                        }
                        $return_fields[$key] = 'ef.' . $field;
                        $has_extrafields = true;
                    } elseif ($this->isExtraField($field)) {
                        $field_key = $this->getExtraFieldFilterKey($field, $joins, 'a', $filters);
                        if ($field_key) {
                            $return_fields[$key] = $field_key;
                        } else {
                            unset($return_fields[$key]);
                        }
                    }
                }
            }
        } else {
            $return_fields = array();
        }

        // Vérification des filtres: 
        $filters = $this->checkSqlFilters($filters, $has_extrafields, $joins);

        // Vérification du champ "order_by": 
        if ($this->field_exists($order_by)) {
            if ($is_dol_object && $this->isDolExtraField($order_by)) {
                $has_extrafields = true;
                $order_by = 'ef.' . $order_by;
            } elseif ($this->isExtraField($order_by)) {
                $order_by = $this->getExtraFieldFilterKey($order_by, $joins, 'a', $filters);
            }
        }

        if ($has_extrafields && !isset($joins['ef'])) {
            $joins['ef'] = array(
                'alias' => 'ef',
                'table' => $table . '_extrafields',
                'on'    => 'a.' . $primary . ' = ef.fk_object'
            );
        }
        
        //Non testé mais doit être fonctionnel
//        foreach($filters as $name => $value){
//            if(stripos($name, 'parent.') !== false){
//                $aliasParentExist = false;
//                foreach($joins as $clef => $data)
//                    if($data['alias'] == 'parent')
//                        $aliasParentExist = true;
//                if(!$aliasParentExist){
//                    $parent = $this->getParentInstance();
//                    $joins['parent'] = array(
//                        'alias' => 'parent',
//                        'table' => $parent->getTable(),
//                        'on'    => 'parent.' . $parent->getPrimary() . ' = a.'.$this->getParentIdProperty()
//                    );
//                }
//            }
//        }

        $sql = '';
        $sql .= BimpTools::getSqlSelect($return_fields);
        $sql .= BimpTools::getSqlFrom($table, $joins);
        $sql .= BimpTools::getSqlWhere($filters);
        $sql .= BimpTools::getSqlOrderBy($order_by, $order_way, 'a', $extra_order_by, $extra_order_way);
        $sql .= BimpTools::getSqlLimit($n, $p);

        if (BimpDebug::isActive()) {
            $content = BimpRender::renderSql($sql);
            $title = 'SQL Liste - Module: "' . $this->module . '" Objet: "' . $this->object_name . '"';
            BimpDebug::addDebug('list_sql', $title, $content);
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
        } else {
            $has_extrafields = false;
            $filters = $this->checkSqlFilters($filters, $has_extrafields, $joins);
        }

        $sql = 'SELECT COUNT(DISTINCT a.' . $primary . ') as nb_rows';
        $sql .= BimpTools::getSqlFrom($table, $joins);
        $sql .= BimpTools::getSqlWhere($filters);

        $result = $this->db->execute($sql);
        if (is_object($result)) {
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
                    $field_key = $this->getExtraFieldFilterKey($field, $joins, 'a', $filters);
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

        if (BimpDebug::isActive()) {
//            $plus = "";
//            if (class_exists('synopsisHook'))
//                $plus = ' ' . synopsisHook::getTime();
//            echo BimpRender::renderDebugInfo($sql, 'SQL Liste Total - Module: "' . $this->module . '" Objet: "' . $this->object_name . '"' . $plus);
            $content = BimpRender::renderSql($sql);
            $title = 'SQL Liste Total - Module: "' . $this->module . '" Objet: "' . $this->object_name . '"';
            BimpDebug::addDebug('list_sql', $title, $content);
        }

        $rows = $this->db->executeS($sql, 'array');

        if (is_null($rows)) {
            $rows = array();
        }

        return $rows;
    }

    // Affichage des données:

    public function display($display_name = 'ref_nom', $options = array())
    {
        if (!$this->isLoaded()) {
            return '';
        }

        $ref = $this->getRef(false);
        $nom = $this->getName(false);

        if (!$ref && !$nom) {
            return BimpTools::ucfirst($this->getLabel()) . ' #' . $this->id;
        }

        switch ($display_name) {
            case 'nom':
                if (!$nom) {
                    return BimpTools::ucfirst($this->getLabel()) . ' #' . $this->id;
                }
                return $nom;

            case 'ref':
                if (!$ref) {
                    return '#' . $this->id;
                }
                return $ref;

            case 'nom_ref':
                if ($nom) {
                    return $nom . ($ref ? ' - ' . $ref : '');
                }
                return $ref;

            case 'ref_nom':
            default:
                if ($ref) {
                    return $ref . ($nom ? ' - ' . $nom : '');
                }
                return $nom;
        }
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

    public function displayFieldName($field)
    {
        $bc_field = new BC_Field($this, $field);
        return $bc_field->params['label'];
    }

    public function displayInitData($field, $display_name = 'default', $display_input_value = true, $no_html = false)
    {
        $bc_field = new BC_Field($this, $field);
        $bc_field->display_name = $display_name;
        $bc_field->display_input_value = $display_input_value;
        $bc_field->no_html = $no_html;

        $bc_field->value = $this->getInitData($field);

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
                            default:
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

//                            default:
//                                $html .= BimpTools::ucfirst(self::getInstanceLabel($instance, 'name')) . ' ' . $id_associate;
//                                break;
                        }

                        $this->config->setCurrentPath($prev_path);
                    } else {
                        $html .= BimpRender::renderAlerts(self::getInstanceLabel($instance, 'name') . ' d\'ID ' . $id_associate . ' non trouvé(e)');
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

    public function getCommonFieldSearchInput($field, $input_name = null)
    {
        if (is_null($input_name)) {
            $input_name = 'search_' . $field;
        }
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
        $html .= ' data-field_name="' . $input_name . '"';
        $html .= ' data-search_type="' . $search_type . '"';
        $html .= ' data-search_on_key_up="' . $searchOnKeyUp . '"';
        $html .= ' data-min_chars="' . $minChars . '"';
        $html .= '>';

        $input_id = $this->object_name . '_search_' . $field;

        $html .= BimpInput::renderInput($input_type, $input_name, '', $options, null, 'default', $input_id);

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
            if (!$this->isFieldActivated($field)) {
                continue;
            }

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

            $errors = BimpTools::merge_array($errors, $this->validateValue($field, $value));
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
            if (!$this->isFieldActivated($field)) {
                continue;
            }

            $this->config->setCurrentPath('fields/' . $field);
            $value = null;
            if (isset($values[$field])) {
                $value = $values[$field];
            } elseif (isset($this->data[$field])) {
                $value = $this->data[$field];
            } else {
                $value = $this->getCurrentConf('default_value');
            }

            $errors = BimpTools::merge_array($errors, $this->validateValue($field, $value));
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

        if (!$this->isFieldActivated($field)) {
            return array();
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
        $type = $this->getCurrentConf('type', 'string');

        $missing = false;

        if ($type === 'json') {
            if (is_string($value)) {
                $value = json_decode($value, 1);
            }
        } elseif ($type === 'items_list') {
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
            $errors[] = 'Valeur obligatoire manquante : "' . $label . ' (' . $field . ') objet ' . $this->object_name . '"';
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
            if (!$this->isFieldActivated($field)) {
                continue;
            }
            $value = $this->getData($field);
            $errors = BimpTools::merge_array($errors, $this->validateValue($field, $value));
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

                if (!is_array($errors)) {
                    BimpCore::addlog('Retour d\'erreurs absent', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', null, array(
                        'méthode' => 'update()',
                        'Module'  => $this->module,
                        'Object'  => $this->object_name
                    ));
                    $errors = array();
                }

                if (!count($errors)) {
                    $success = 'Mise à jour ' . $this->getLabel('of_the') . ' effectuée avec succès';
                    if (method_exists($this, 'getUpdateJsCallback')) {
                        $success_callback = $this->getUpdateJsCallback();
                    }
                }
            } else {
                $errors = $this->create($warnings, $force_edit);

                if (!is_array($errors)) {
                    BimpCore::addlog('Retour d\'erreurs absent', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', null, array(
                        'méthode' => 'create()',
                        'Module'  => $this->module,
                        'Object'  => $this->object_name
                    ));

                    $errors = array();
                }

                if (!count($errors)) {
                    $success = 'Création ' . $this->getLabel('of_the') . ' effectuée avec succès';
                    if (method_exists($this, 'getCreateJsCallback')) {
                        $success_callback = $this->getCreateJsCallback();
                    }
                }
            }
        }

        if (!count($errors)) {
            // Associations: 
            $warnings = BimpTools::merge_array($warnings, $this->saveAssociationsFromPost());

            // Sous-objets ajoutés: 
            $sub_result = $this->checkSubObjectsPost($force_edit);
            if (count($sub_result['errors'])) {
                $warnings = BimpTools::merge_array($warnings, $sub_result['errors']);
            }
            if ($sub_result['success_callback']) {
                $success_callback .= $sub_result['success_callback'];
            }
        }

        if ($this->isLoaded()) {
            // Champs des sous-objets mis à jour: 
            $sub_result = $this->checkChildrenUpdatesFromPost();
            if (count($sub_result['errors'])) {
                $warnings = BimpTools::merge_array($warnings, $sub_result['errors']);
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
                                $sub_errors = BimpTools::merge_array($result['errors'], $result['warnings']);
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
                            $sub_errors = BimpTools::merge_array($result['errors'], $result['warnings']);
                            if ($sub_errors) {
                                $errors[] = BimpTools::getMsgFromArray($sub_errors, 'Des erreurs sont survenues lors de la création ' . $object->getLabel('of_the'));
                            }
                            if (count($result['warnings'])) {
                                $errors = BimpTools::merge_array($errors, $result['warnings']);
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

    public function checkChildrenUpdatesFromPost()
    {
        $errors = array();
        $success_callback = '';
        $children = $this->getLinkedObjectsArray();

        $data = array();

        foreach ($children as $child_name => $label) {
            foreach ($_POST as $post_name => $value) {
                if (preg_match('/^(' . preg_quote($child_name, '/') . ')' . '___(.+)$/', $post_name, $matches)) {
                    if (!isset($data[$child_name])) {
                        $data[$child_name] = array();
                    }
                    $field_name = $matches[2];
                    $data[$child_name][$field_name] = $value;
                }
            }
        }

        if (!empty($data)) {
            foreach ($data as $child_name => $fields) {
                $child = $this->getChildObject($child_name);
                if (BimpObject::objectLoaded($child)) {
                    $post_tmp = $_POST;
                    $_POST = $fields;

                    if ($child->isEditable() && $child->can('edit')) {
                        $result = $child->saveFromPost();
                        $sub_errors = BimpTools::merge_array($result['errors'], $result['warnings']);
                        if ($result['success_callback']) {
                            $success_callback .= $result['success_callback'];
                        }
                    } else {
                        $result = $child->checkChildrenUpdatesFromPost();
                        $sub_errors = $result['errors'];
                        if ($result['success_callback']) {
                            $success_callback .= $result['success_callback'];
                        }
                    }

                    if (count($sub_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($sub_errors, 'Erreurs lors de la mise à jour ' . $child->getLabel('of_the') . ' ' . $child->getRef());
                    }

                    $_POST = $post_tmp;
                } else {
                    $errors[] = 'Objet lié "' . $child_name . '" absent';
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

        if (!$this->isCreatable($force_create, $errors)) {
            if (!count($errors)) {
                $errors[] = 'Il n\'est pas possible de créer ' . $this->getLabel('a');
            }
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

                    $warnings = BimpTools::merge_array($warnings, $this->updateAssociations());
                    $warnings = BimpTools::merge_array($warnings, $this->saveHistory());

                    $parent = $this->getParentInstance();
                    if (!is_null($parent)) {
                        if (method_exists($parent, 'onChildCreate')) {
                            $parent->onChildCreate($this);
                        }
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


                $notes = array();
                foreach ($this->fieldsWithAddNoteOnUpdate as $champAddNote) {
                    if ($this->getData($champAddNote) != $this->getInitData($champAddNote))
                        $notes[] = html_entity_decode('Champ ' . $this->displayFieldName($champAddNote) . ' modifié. 
Ancienne valeur : ' . $this->displayInitData($champAddNote, 'default', false, true) . '
Nouvel : ' . $this->displayData($champAddNote, 'default', false, true));
                }
                if (count($notes))
                    $this->addNote(implode('
', $notes));

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

                        $warnings = BimpTools::merge_array($warnings, $this->updateAssociations());
                        $warnings = BimpTools::merge_array($warnings, $this->saveHistory());

                        $parent = $this->getParentInstance();

                        if (!is_null($parent)) {
                            if (method_exists($parent, 'onChildUpdate')) {
                                $parent->onChildCreate($this);
                            }
                            if (method_exists($parent, 'onChildSave')) {
                                $warnings = BimpTools::merge_array($warnings, $parent->onChildSave($this));
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
                    $errors = BimpTools::merge_array($errors, $bimpAsso->errors);
                } else {
                    $errors = BimpTools::merge_array($errors, $bimpAsso->setObjectAssociations($this->associations[$association]));
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
                $errors = BimpTools::merge_array($errors, $this->validateValue($field, $value));
            } else {
                $this->data[$field] = $value;
            }
            if (!count($errors)) {
                $value = $this->getData($field);
                $db_value = $this->getDbValue($field, $value);

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
                    $errors = BimpTools::merge_array($errors, $this->updateExtraField($field, $db_value, $id_object));
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

                $warnings = array();
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
                        $history->create($warnings, true);
                    }

                    $parent = $this->getParentInstance();

                    if (!is_null($parent)) {
                        // Trigger sur le parent: 
                        if (method_exists($parent, 'onChildSave')) {
                            $warnings = BimpTools::merge_array($warnings, $parent->onChildSave($this));
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
        if (BimpDebug::isActive()) {
            BimpDebug::addDebugTime('Fetch ' . $this->getLabel() . ' - ID ' . $id);
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

//        echo '<pre>';
//        print_r($this->data);
//        echo '</pre>';
        foreach ($this->params['fields'] as $field) {
            if ($this->field_exists($field)) {
                $value = $this->getData($field);
                if ((int) $this->getConf('fields/' . $field . '/dol_extra_field', 0, false, 'bool')) {
                    if (preg_match('/^ef_(.*)$/', $field, $matches)) {
                        $extrafield = $matches[1];
                    } else {
                        $extrafield = $field;
                    }
                    $this->dol_object->array_options['options_' . $extrafield] = $this->getDolValue($field, $value);
                } else {
                    $prop = '';
                    if (!(int) $this->getConf('fields/' . $field . '/no_dol_prop', 0)) {
                        $prop = $this->getConf('fields/' . $field . '/dol_prop', $field);
                    }

                    if ($prop && property_exists($this->dol_object, $prop)) {
                        $this->dol_object->{$prop} = $this->getDolValue($field, $value);
                    } elseif ($this->field_exists($field) && !$this->isExtraField($field)) {
//                        echo $field . ': ' . (is_null($value) ? 'NULL' : $value) . '<br/>';
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

        if (!isset($this->dol_object->id) && isset($this->dol_object->rowid)) {
            $this->dol_object->id = $this->dol_object->rowid;
        }

        $this->id = $this->dol_object->id;

        foreach ($this->params['fields'] as $field) {
            if (!$this->isFieldActivated($field)) {
                continue;
            }
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
                if (!(int) $this->getConf('fields/' . $field . '/no_dol_prop', 0)) {
                    $prop = $this->getConf('fields/' . $field . '/dol_prop', $field);
                }

                if ($prop && property_exists($this->dol_object, $prop)) {
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
                    if ($this->dol_field_exists(str_replace('options_', '', $key))) {
                        if ($this->dol_object->updateExtraField(str_replace('options_', '', $key), null, null) <= 0) {
                            $warnings[] = 'Echec de l\'enregistrement de l\'attribut supplémentaire "' . str_replace('options_', '', $key) . '"';
                        }
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
                    if (!$this->field_exists($field_name) || !isset($result[$field_name])) {
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
            if ($this->field_exists($field_name)) {
                $this->checkFieldValueType($field_name, $value);
                $this->data[$field_name] = $value;
            }
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

    public function getExtraFieldFilterKey($field, &$joins, $main_alias = '', &$filters = array())
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
        return $this->isEditable($force_delete, $errors);
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        $primary = $this->getPrimary();

        switch ($field) {
            case $primary:
                return 0;
        }

        return $this->isEditable($force_edit);
    }

    public function isFieldActivated($field_name)
    {
        return $this->isFieldUsed($field_name);
    }

    public function isFieldUsed($field_name)
    {
        if ($this->config->isDefined('fields/' . $field_name)) {
            return ((int) $this->getConf('fields/' . $field_name . '/unused', 0, false, 'bool') ? 0 : 1);
        }

        return 0;
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
        global $user;

        switch ($action) {
            case 'bulkDelete':
                return ((int) $user->id === 1 || $user->login == 'f.martinez' ? 1 : 0); // On réserver ce droit au super admin.

            case 'bulkEditField': // Pour ce type d'action, il faut également que le user ait le droit d'éditer le field en question. 
                return ($user->admin ? 1 : 0);
        }

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
            $parent_id_property = $this->getParentIdProperty();
            if (method_exists($this, 'getPositionsFilters')) {
                $filters = $this->getPositionsFilters();
                if (is_null($filters)) {
                    return;
                }
            } else {
                $filters = array();
            }

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
            if (method_exists($this, 'getPositionsFilters')) {
                $filters = $this->getPositionsFilters();
                if (is_null($filters)) {
                    return;
                }
            } else {
                $filters = array();
            }
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
            if (method_exists($this, 'getPositionsFilters')) {
                $filters = $this->getPositionsFilters();
                if (is_null($filters)) {
                    return 1;
                }
            } else {
                $filters = array();
            }

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

    public function addNote($content, $visibility = null, $viewed = 0, $auto = 1, $email = '', $type_author = 1)
    {
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }
        $note = BimpObject::getInstance('bimpcore', 'BimpNote');

        if (is_null($visibility)) {
            $visibility = BimpNote::BIMP_NOTE_MEMBERS;
        }

        $errors = $note->validateArray(array(
            'obj_type'    => 'bimp_object',
            'obj_module'  => $this->module,
            'obj_name'    => $this->object_name,
            'id_obj'      => (int) $this->id,
            'visibility'  => (int) $visibility,
            'content'     => $content,
            'viewed'      => $viewed,
            'auto'        => $auto,
            "email"       => $email,
            "type_author" => $type_author
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

            $logo_html = $this->renderLogo('mini', true);

            if ($logo_html) {
                $html .= '<div class="object_header_logo">';
                $html .= $logo_html;
                $html .= '</div>';
            }

            $html .= '<div class="object_header_title">';
            $html .= '<h1>';
            if ($this->params['icon']) {
                $html .= '<i class="' . BimpRender::renderIconClass($this->params['icon']) . ' iconLeft"></i>';
            }
            $html .= BimpTools::ucfirst($this->getLabel()) . ' #' . $this->id;
            $html .= '</h1>';

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
            $html .= '</div>';

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

            $html .= '<div class="header_buttons">';
            $html .= BimpRender::renderButtonsGroup($this->params['header_btn'], array(
                        'max'                 => 6,
                        'dropdown_menu_right' => 1
            ));

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
        $this->checkObject('render_msgs');

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
            $title = (is_null($title) ? $this->getConf('objects/' . $children_object . '/list/title', $this->getConf('objects/' . $children_object . '/label', BimpTools::ucfirst($children_instance->getLabel('name_plur')))) : $title);
            $icon = (is_null($icon) ? $this->getConf('objects/' . $children_object . '/list/icon', $icon) : $icon);

            $list = new BC_ListTable($children_instance, $list_name, $level, $this->id, $title, $icon);

            $list_filters = $this->config->getCompiledParams('objects/' . $children_object . '/list/filters', array(), false, 'array');

            if (is_array($list_filters) && count($list_filters)) {
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

    public function renderAssociatesList($association, $list_name = 'default', $title = null, $icon = null, $level = 1)
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

    public function renderAllAssociationsLists()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID ' . $this->getLabel('of_the') . ' absent');
        }

        $html = '';

        foreach ($this->params['associations'] as $asso_name) {
            $asso = new BimpAssociation($this, $asso_name);
            $associate_name = $this->getConf('associations/' . $asso_name . '/object', '');

            if ($associate_name) {
                $content = '';
                $associate = $this->getChildObject($associate_name);
                $list = $asso->getAssociatesList();

                if (empty($list)) {
                    $content .= BimpRender::renderAlerts('Aucun associé', 'warning');
                } else {
                    foreach ($list as $id_associate) {
                        if (is_a($associate, 'BimpObject')) {
                            $associate->fetch($id_associate);
                            if (BimpObject::objectLoaded($associate)) {
                                $content .= ' - ' . $associate->getLink() . '<br/>';
                            } else {
                                $content .= ' - <span class="danger">' . BimpTools::ucfirst($associate->getLabel('the')) . ' d\'ID ' . $id_associate . ' n\'existe plus</span><br/>';
                            }
                        } elseif (method_exists($associate, 'getNomUrl') && method_exists($associate, 'fetch')) {
                            $associate->fetch($id_associate);
                            if (BimpObject::objectLoaded($associate)) {
                                $content .= ' - ' . $associate->getNomUrl(1) . '<br/>';
                            } else {
                                $content .= ' - <span class="danger">L\'objet "' . get_class($associate) . '" d\'ID ' . $id_associate . ' n\'existe plus</span><br/>';
                            }
                        } else {
                            $content .= ' - #' . $id_associate . '<br/>';
                        }
                    }
                }

                if (is_a($associate, 'BimpObject')) {
                    $title = BimpTools::ucfirst($associate->getLabel('name_plur')) . ' associés';
                } else {
                    $title = 'Objets "' . get_class($associate) . '" associés';
                }

                $html .= BimpRender::renderPanel($title, $content);
            } else {
                $html .= BimpRender::renderAlerts('Asso "' . $asso_name . '": objet lié non défini (param "object" absent');
            }
        }

        return $html;
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

    public function renderSearchInput($input_name, $value = null, $options = array())
    {
        $html = BimpInput::renderSearchListInput($input_name, $options, $value, 'default');
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
        $list_type = BimpTools::getPostFieldValue('list_type', 'list_table');

        $bc_list = null;

        switch ($list_type) {
            case 'list_table':
                $bc_list = new BC_ListTable($this, $list_name);
                break;

            case 'stats_list':
                $bc_list = new BC_StatsList($this, $list_name);
                break;
        }

        if (!is_a($bc_list, 'BC_List')) {
            return BimpRender::renderAlerts('Type de liste invalide', 'danger');
        }

        $html = '';

        $rows = $bc_list->getCsvColOptionsInputs();

        if (!empty($rows)) {
            $html .= '<table class="bimp_list_table">';
            $html .= '<tbody>';

            foreach ($rows as $r) {
                $html .= '<tr>';
                $html .= '<th width="40%">' . $r['label'] . '</th>';
                $html .= '<td>' . $r['content'] . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
        } else {
            if (!empty($bc_list->errors)) {
                $html .= BimpRender::renderAlerts(BimpTools::getMsgFromArray($bc_list->errors));
            } else {
                $html .= BimpRender::renderAlerts('Aucune colonne sélectionnée', 'warning');
            }
        }

        return $html;
    }

    public function renderCaisseInput()
    {
        BimpObject::loadClass('bimpcaisse', 'BC_Caisse');
        return BC_Caisse::renderUserCaisseInput();
    }

    public static function renderListFileForObject($objT, $with_delete = 0)
    {
        $obj = BimpObject::getBimpObjectInstance('bimpcore', 'BimpFile');
        $bc_list = new BC_ListTable($obj, 'default', 1, null, 'Liste des fichiers ' . $objT->getNomUrl(), 'fas_bars');

        $bc_list->addFieldFilterValue('a.parent_object_name', get_class($objT));
        $bc_list->params['add_form_values']['fields']['parent_object_name'] = get_class($objT);
        $bc_list->addFieldFilterValue('a.parent_module', $objT->module);
        $bc_list->params['add_form_values']['fields']['parent_module'] = $objT->module;
        $bc_list->addFieldFilterValue('a.id_parent', $objT->id);
        $bc_list->params['add_form_values']['fields']['id_parent'] = $objT->id;
        if (!$with_delete)
            $bc_list->addFieldFilterValue('a.deleted', 0);
        $bc_list->identifier .= get_class($objT) . "-" . $objT->id;

        return $bc_list->renderHtml();
    }

    public function renderLogo($format = 'mini', $preview = false)
    {
        $html = '';
        $logo_url = $this->getLogoUrl($format);

        if ($logo_url) {
            $html .= '<div class="bimp_img_container">';

            if ($preview) {
                $preview_url = $this->getLogoUrl('', true);
                $html .= '<a href="' . $preview_url . '">';
            }

            $html .= '<img alt="logo" src="' . $logo_url . '"/>';

            if ($preview) {
                $html .= '</a>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    public function renderData()
    {
        $html .= $this->printData(1);

        return $html;
    }

    public function renderTypeOfBimpOjectLinked()
    {
        $html = '<pre>';
        $list = static::getTypeOfBimpObjectLinked($this->module, $this->object_name, false);
        $html .= print_r($list, 1);
        $html .= "</pre>";
        return $html;
    }

    public function renderBimpOjectDivers()
    {
        $tabs = array();

        $idHtml = 'object_divers_data';
        $tabs[] = array(
            'id'      => $idHtml,
            'title'   => 'Données',
            'content' => $this->renderData(),
//            'ajax'          => 1,
//            'ajax_callback' => $this->getJsLoadCustomContent('renderData', '$(\'#' . $idHtml . ' .nav_tab_ajax_result\')', array(), array('button' => ''))
        );

        $idHtml = 'object_divers_linked_list';
        $tabs[] = array(
            'id'            => $idHtml,
            'title'         => BimpRender::renderIcon('fas_linked', 'iconLeft') . 'Tout les objets liées',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderBimpOjectLinked', '$(\'#' . $idHtml . ' .nav_tab_ajax_result\')', array(), array('button' => ''))
        );

        $idHtml = 'object_divers_linked_text';
        $tabs[] = array(
            'id'            => $idHtml,
            'title'         => 'Tout les types d\'objets liés',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderTypeOfBimpOjectLinked', '$(\'#' . $idHtml . ' .nav_tab_ajax_result\')', array(), array('button' => ''))
        );

        $idHtml = 'object_divers_associations';
        $tabs[] = array(
            'id'            => $idHtml,
            'title'         => 'Toutes les associations',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderAllAssociationsLists', '$(\'#' . $idHtml . ' .nav_tab_ajax_result\')', array(), array('button' => ''))
        );

        $html = "<h1>" . $this->getName() . " (" . get_class($this) . ")</h2>";
        $html .= BimpRender::renderNavTabs($tabs, 'suppport_view');

        return $html;
    }

    public function renderBimpOjectLinked()
    {
        $html = '';
        $list = static::getTypeOfBimpObjectLinked($this->module, $this->object_name);

        foreach ($list as $module => $objects) {
            foreach ($objects as $class_name => $fields) {
                foreach ($fields as $field_name => $objTmp) {
                    $listObj = $objTmp->getList(array($field_name => $this->id));
                    if (count($listObj)) {
                        $list_html = new BC_ListTable($objTmp);
                        $list_html->addFieldFilterValue($field_name, $this->id);
                        if (empty($list_html->errors)) {
                            $html .= $list_html->renderHtml();
                        }
                    }
                }
            }
        }

        return $html;
    }

    public function renderListColsTypeSelect($params = array())
    {
        $html = '';

        $params = BimpTools::overrideArray(array(
                    'object_label'   => BimpTools::ucfirst($this->getLabel()),
                    'child_name'     => '',
                    'fields_prefixe' => ''
                        ), $params);

        $cols = $this->getListColsArray(true);
        $linked_objects = $this->getLinkedObjectsArray(true);
//        $children = $this->getListChidrenArray(true);

        $options = array();

        $default_type = '';
        if (count($cols) > 1) {
            $options['fields'] = 'Champs';
            $default_type = 'fields';
        }

        if (count($linked_objects) > 1) {
            $options['linked_objects'] = 'Objets liés';
            if (!$default_type) {
                $default_type = 'linked_objects';
            }
        }

//        if (count($children) > 1) {
//            $options['children'] = 'Sous-listes';
//            if (!$default_type) {
//                $default_type = 'children';
//            }
//        }

        if (empty($options)) {
            return BimpRender::renderAlerts('Aucune option disponible', 'warning');
        }

        if ($params['child_name']) {
            $params['fields_prefixe'] .= $params['child_name'] . ':';
        }

        $html .= '<div class="objectListColsTypesSelect_container"';
        $html .= ' data-module="' . $this->module . '"';
        $html .= ' data-object_name="' . $this->object_name . '"';
        $html .= ' data-child_name="' . $params['child_name'] . '"';
        $html .= ' data-fields_prefixe="' . $params['fields_prefixe'] . '"';
        $html .= '>';

        if ($params['object_label']) {
            $html .= '<div class="objectListColsTypesSelect_caption">' . $params['object_label'] . ': </div>';
        }

        $html .= '<div class="objectListColsTypesSelect_content">';
        $html .= '<div class="input_label">Type de colonne: </div>';
        $html .= BimpInput::renderInput('select', 'col_element_type', $default_type, array(
                    'options'     => $options,
                    'extra_class' => 'col_type_select'
        ));

        if (count($cols) > 1) {
            $html .= '<div class="objectColTypeItemsSelectContainer"' . ($default_type === 'fields' ? '' : ' style="display: none"') . ' data-col_type="fields">';
            $html .= '<div class="input_label">Champ: </div>';
            $html .= BimpInput::renderInput('select', 'field', '', array(
                        'options'     => $cols,
                        'extra_class' => 'field_select',
            ));
            $html .= '<div class="field_options col_type_item_options" style="display: none"></div>';
            $html .= '</div>';
        }

        if (count($linked_objects) > 1) {
            $html .= '<div class="objectColTypeItemsSelectContainer"' . ($default_type === 'linked_objects' ? '' : ' style="display: none"') . ' data-col_type="linked_objects">';
            $html .= '<div class="input_label">Objet lié: </div>';
            $html .= BimpInput::renderInput('select', 'linked_object', '', array(
                        'options'     => $linked_objects,
                        'extra_class' => 'linked_object_select'
            ));
            $html .= '<div class="linked_object_options col_type_item_options" style="display: none"></div>';
            $html .= '</div>';
        }

//        if (count($children) > 1) {
//            $html .= '<div class="objectColTypeItemsSelectContainer"' . ($default_type === 'children' ? '' : ' style="display: none"') . ' data-col_type="children">';
//            $html .= '<div class="input_label">Sous-liste: </div>';
//            $html .= BimpInput::renderInput('select', 'children', '', array(
//                        'options'     => $children,
//                        'extra_class' => 'children_select'
//            ));
//            $html .= '<div class="children_options col_type_item_options" style="display: none"></div>';
//            $html .= '</div>';
//        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderFiltersSelect($params = array())
    {
        $html = '';

        $params = BimpTools::overrideArray(array(
                    'object_label'   => BimpTools::ucfirst($this->getLabel()),
                    'child_name'     => '',
                    'fields_prefixe' => ''
                        ), $params);

        $filters = $this->getFiltersArray(true);
        $linked_objects = $this->getLinkedObjectsArray(true);
        $linked_objects = BimpTools::merge_array($linked_objects, $this->getListChidrenArray(false));
        $options = array();

        $default_type = '';
        if (count($filters) > 1) {
            $options['fields'] = 'Champs';
            $default_type = 'fields';
        }

        if (count($linked_objects) > 1) {
            $options['linked_objects'] = 'Objets liés';
            if (!$default_type) {
                $default_type = 'linked_objects';
            }
        }

        if (empty($options)) {
            return BimpRender::renderAlerts('Aucune option disponible', 'warning');
        }

        if ($params['child_name']) {
            $params['fields_prefixe'] .= $params['child_name'] . ':';
        }

        $html .= '<div class="objectFiltersSelect_container"';
        $html .= ' data-module="' . $this->module . '"';
        $html .= ' data-object_name="' . $this->object_name . '"';
        $html .= ' data-child_name="' . $params['child_name'] . '"';
        $html .= ' data-fields_prefixe="' . $params['fields_prefixe'] . '"';
        $html .= '>';

        if ($params['object_label']) {
            $html .= '<div class="objectFiltersTypeSelect_caption">' . $params['object_label'] . ': </div>';
        }

        $html .= '<div class="objectFiltersTypeSelect_content">';
        $html .= '<div class="input_label">Type: </div>';
        $html .= BimpInput::renderInput('select', 'filter_element_type', $default_type, array(
                    'options'     => $options,
                    'extra_class' => 'filter_type_select'
        ));

        if (count($filters) > 1) {
            $html .= '<div class="objectFilterItemsSelectContainer"' . ($default_type === 'fields' ? '' : ' style="display: none"') . ' data-type="fields">';
            $html .= '<div class="input_label">Champ: </div>';
            $html .= BimpInput::renderInput('select', 'field', '', array(
                        'options'     => $filters,
                        'extra_class' => 'field_select',
            ));
            $html .= '<div class="field_options filter_item_options" style="display: none"></div>';
            $html .= '</div>';
        }

        if (count($linked_objects) > 1) {
            $html .= '<div class="objectFilterItemsSelectContainer"' . ($default_type === 'linked_objects' ? '' : ' style="display: none"') . ' data-type="linked_objects">';
            $html .= '<div class="input_label">Objets liés: </div>';
            $html .= BimpInput::renderInput('select', 'linked_object', '', array(
                        'options'     => $linked_objects,
                        'extra_class' => 'linked_object_select'
            ));
            $html .= '<div class="linked_object_options filter_item_options" style="display: none"></div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderTabs($fonction, $nomTabs, $params1 = null, $params2 = null)
    {
        //pour patch le chargement auto des onglet
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

    public function getJsLoadModalForm($form_name = 'default', $title = '', $values = array(), $success_callback = '', $on_save = '', $force_edit = 0, $button = '$(this)')
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

        $js = 'loadModalForm(' . $button . ', ' . htmlentities($data) . ', \'' . htmlentities($title) . '\', \'' . htmlentities($success_callback) . '\', \'' . $on_save . '\')';
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
            $js .= htmlentities(json_encode($params['extra_filters']));
        } else {
            $js .= 'null';
        }
        $js .= ', ';

        if (isset($params['extra_joins']) && is_array($params['extra_joins']) && !empty($params['extra_joins'])) {
            $js .= htmlentities(json_encode($params['extra_joins']));
        } else {
            $js .= 'null';
        }

        $js .= ');';

        return $js;
    }

    public function getJsLoadModalCustomContent($method, $title, $method_params = array(), $modal_format = 'medium', $success_callback = 'null')
    {
        $js = '';

        $js .= 'loadModalObjectCustomContent($(this), ' . $this->getJsObjectData() . ', ';
        $js .= '\'' . $method . '\', ';
        if (is_array($method_params) && !empty($method_params)) {
            $js .= htmlentities(json_encode($method_params)) . ', ';
        } else {
            $js .= '{}, ';
        }
        $js .= '\'' . $title . '\', ';
        $js .= $success_callback . ', ';
        $js .= '\'' . $modal_format . '\'';

        $js .= ');';

//        $js .= 'bimpModal.loadAjaxContent($(this), \'loadObjectCustomContent\', {';
//        $js .= 'module: \'' . $this->module . '\'';
//        $js .= ', object_name: \'' . $this->object_name . '\'';
//        $js .= ', id_object: \'' . (int) $this->id . '\'';
//        $js .= ', method: \'' . $method . '\'';
//        $js .= ', params: ' . htmlentities(json_encode($method_params));
//        $js .= '}, \'' . $title . '\', \'Chargement\', ' . $success_callback . ', {}, \'' . $modal_format . '\');';

        return $js;
    }

    public function getJsLoadCustomContent($method = '', $resultContainer = '', $method_params = array(), $params = array())
    {
        $js = '';

        $params = BimpTools::overrideArray(array(
                    'button' => '$(this)',
                        ), $params);

        if ($method && $resultContainer) {
            $data = $this->getJsObjectData();

            $js = 'loadObjectCustomContent(' . ($params['button'] ? $params['button'] : 'null') . ', ' . $resultContainer . ', ';
            $js .= $data . ', \'' . $method . '\', ';
            if (!empty($params)) {
                $js .= htmlentities(json_encode($method_params));
            } else {
                $js .= '{}';
            }
            $js .= ');';
        }

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
        if (!isset($params['no_button']) || !$params['no_button']) {
            $js .= '$(this), ';
        } else {
            $js .= 'null, ';
        }
        $js .= $this->getJsObjectData();
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
        $js .= ', ';
        if (isset($params['modal_format']) && in_array($params['modal_format'], array('small', 'medium', 'large'))) {
            $js .= '\'' . $params['modal_format'] . '\'';
        } else {
            $js .= '\'medium\'';
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
            $js .= ((int) $params['single_action'] ? 'true' : 'false');
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
        if (isset($this->params['labels']['name'])) {
            $object_name = $this->params['labels']['name'];

            if (isset($this->params['labels']['name_plur'])) {
                $name_plur = $this->params['labels']['name_plur'];
            } else {
                if (preg_match('/^.*[ao]u$/', $object_name)) {
                    $name_plur = $object_name . 'x';
                } elseif (preg_match('/^.*ou$/', $object_name)) {
                    $name_plur = $object_name . 'x';
                } elseif (!preg_match('/^.*s$/', $object_name)) {
                    $name_plur = $object_name . 's';
                } else {
                    $name_plur = $object_name;
                }
            }

            if (isset($this->params['labels']['is_female'])) {
                $isFemale = $this->params['labels']['is_female'];
            } else {
                $isFemale = false;
            }

            $vowel_first = false;
            if (preg_match('/^[aàâäeéèêëiîïoôöuùûüyŷÿ](.*)$/', $object_name)) {
                $vowel_first = true;
            }
        } else {
            $object_name = 'objet ' . $this->object_name;
            $name_plur = 'objets ' . $this->object_name;
            $isFemale = false;
            $vowel_first = true;
        }

        $labels = array(
            'name'      => $object_name,
            'name_plur' => $name_plur,
            'is_female' => $isFemale
        );

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
        if (isset($this->params['labels']['name'])) {
            $labels = $this->params['labels'];

            $object_name = $labels['name'];

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
        } else {
            $object_name = 'objet ' . $this->object_name;
            $name_plur = 'objets ' . $this->object_name;
            $isFemale = false;
            $vowel_first = true;
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
        if (!is_a($instance, 'BimpObject')) {
            $bimpObj = BimpTools::getBimpObjectFromDolObject($instance);

            if (!is_null($bimpObj)) {
                $instance = $bimpObj;
            }
        }

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
        } elseif (is_a($instance, 'ActionComm')) {
            return 'Evénement' . (BimpObject::objectLoaded($instance) ? ' #' . $instance->id : '');
        }

        foreach (self::$name_properties as $name_prop) {
            if (isset($instance->{$name_prop})) {
                return $instance->{$name_prop};
            }
        }

        return '';
    }

    public static function getInstanceRef($instance)
    {
        if (is_a($instance, 'BimpObject')) {
            return $instance->getRef();
        }

        foreach (self::$ref_properties as $ref_prop) {
            if (isset($instance->{$ref_prop})) {
                return $instance->{$ref_prop};
            }
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

    public function getNomUrl($withpicto = true, $ref_only = true, $page_link = false, $modal_view = '', $card = '')
    {
        // Fonction maintenue pour compatibilité mais dépréciée => Utiliser getLink(). 

        $params = array(
            'with_icon'     => $withpicto,
            'external_link' => $page_link,
            'modal_view'    => $modal_view,
            'card'          => $card
        );

        if ($ref_only) {
            $params['syntaxe'] = '<ref>';
        }

        return $this->getLink($params);
    }

    public function getLink($params = array())
    {
        // $params peut éventuellement être utilisé pour surcharger les paramères "nom_url" de l'objet. 

        $html = '';
        $html .= '<span class="objectLink">';

        if (is_array($params)) {
            $params = BimpTools::overrideArray($this->params['nom_url'], $params, true);
        } else {
            $params = $this->params['nom_url'];
        }

        $icon = '';

        $with_icon = (isset($params['with_icon']) ? (int) $params['with_icon'] : 1);
        if ($with_icon && $this->params['icon']) {
            $icon = BimpRender::renderIcon($this->params['icon'], 'iconLeft');
        }

        $default_syntaxe = '<ref> - <name>';

        $ref_prop = $this->getRefProperty();
        if (!$ref_prop) {
            $default_syntaxe = '<name>';
        }

        $label = (isset($params['syntaxe']) && (string) $params['syntaxe'] ? $params['syntaxe'] : $default_syntaxe);

        while (preg_match('/<(.+)>/U', $label, $matches)) {
            $field = $matches[1];
            $value = '';
            switch ($field) {
                case 'ref':
                    $value = $this->getRef(false);
                    break;

                case 'name':
                case 'nom':
                    $value = $this->getName(true);
                    break;

                default:
                    if ($this->field_exists($field)) {
                        $value = $this->displayData($field, 'default', false, true);
                    }
                    break;
            }

            $label = str_replace('<' . $field . '>', $value, $label);
        }

        if (!$label) {
            $label = BimpTools::ucfirst($this->getLabel()) . ' #' . $this->id;
        }

        if (isset($params['label_extra']) && $params['label_extra']) {
            $label .= ' - ' . $params['label_extra'];
        }

        $status = '';
        if (isset($params['with_status']) && (int) $params['with_status'] && isset(self::$status_list)) {
            $status_prop = $this->getStatusProperty();
            if ($status_prop) {
                $status_value = $this->getData($status_prop);

                // On n'affiche que l'icône du statut: 
                if (isset(static::$status_list[$status_value]['icon'])) {
                    $status_label = isset(static::$status_list[$status_value]['label']) ? static::$status_list[$status_value]['label'] : 'Statut: ' . $status_value;
                    $status_class = isset(static::$status_list[$status_value]['classes'][0]) ? static::$status_list[$status_value]['classes'][0] : 'bold';

                    $status .= ' ' . '<span class="' . $status_class . ' bs-popover"';
                    $status .= BimpRender::renderPopoverData($status_label);
                    $status .= '>';
                    $status .= BimpRender::renderIcon(static::$status_list[$status_value]['icon']);
                    $status .= '</span>';
                }
            }
        }

        $card_html = '';
        if (isset($params['card']) && (string) $params['card']) {
            $card = new BC_Card($this, null, $params['card']);

            if ($card->isOk()) {
                $card->params['view_btn'] = 0;
                $card_html = $card->renderHtml();
            }
        }
        if (isset($params['disabled']) && $params['disabled'])
            $label = '<strike>' . $label . '</strike>';

        $url = $this->getUrl();
        if ($url) {
            $html .= '<a href="' . $url . '"';
            if ($card_html) {
                $html .= ' class="bs-popover card-popover"';
                $html .= BimpRender::renderPopoverData($card_html, 'bottom', 'true');
            }
            $html .= '>' . $icon . $label . '</a>';
            $html .= $status;
        } elseif ($card_html) {
            $html .= '<span class="bs-popover card-popover"';
            $html .= BimpRender::renderPopoverData($card_html, 'bottom', 'true');
            $html .= '>';
            $html .= $label;
            $html .= '</span>';
        } else {
            $html .= $icon . $label . $status;
        }

        $external_link = (isset($params['external_link']) ? (int) $params['external_link'] : 1);
        $modal_view = (isset($params['modal_view']) ? $params['modal_view'] : 'default');

        if (($url && $external_link) || $modal_view) {
            $html .= BimpRender::renderObjectIcons($this, $external_link, $modal_view, $url);
        }

        if ($card_html) {
            $html .= '<span class="objectIcon cardPopoverIcon">';
            $html .= BimpRender::renderIcon('fas_sticky-note');
            $html .= '</span>';
        }

        if (method_exists($this, 'getNomUrlExtra')) {
            $html .= $this->getNomUrlExtra();
        }

        $html .= '</span>';
        return $html;
    }

    public function getListUrl()
    {
        $url = BimpTools::makeUrlFromConfig($this->config, 'list_page_url', $this->module, $this->getController());

        if (!$url && $this->isDolObject()) {
            $url = BimpTools::getDolObjectListUrl($this->dol_object);
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

    public static function getInstanceNomUrl($instance, $params = array())
    {
        $html = '';

        if (is_a($instance, 'BimpObject')) {
            return $instance->getLink($params);
        } elseif (method_exists($instance, 'getNomUrl')) {
            $with_icon = (isset($params['with_icon']) ? (int) $params['with_icon'] : 1);
            $external_link = (isset($params['external_link']) ? (int) $params['external_link'] : 1);
            $html .= $instance->getNomUrl($with_icon);
            if ($external_link) {
                $url = self::getInstanceUrl($instance);
                if ($url) {
                    $html .= BimpRender::renderObjectIcons($instance, true, null, $url);
                }
            }
        } else {
            $html .= 'Objet "' . get_class($instance) . '"' . (isset($instance->id) ? ' #' . $instance->id : '');
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
        return self::getInstanceNomUrl($instance, array(
                    'with_icon'     => 1,
                    'external_link' => 1
        ));
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
            return self::getObjectFilesArray($this->module, $this->object_name, $this->id, $with_deleted);
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

    public function getLinkedObjectsArray($include_empty = false)
    {
        return self::getObjectLinkedObjectsArray($this, $include_empty);
    }

    public function getListChidrenArray($include_empty = false)
    {
        return self::getObjectListChildrenArray($this, $include_empty);
    }

    public function getListColsArray($include_empty = false)
    {
        return self::getObjectListColsArray($this, $include_empty);
    }

    public function getStatsListColsArray($list_name = 'default')
    {
        return self::getObjectStatsListColsArray($this, $list_name);
    }

    public function getModelsPdfArray()
    {
        return array();
    }

    public function getFiltersArray($include_empty = false)
    {
        return self::getObjectFiltersArray($this, $include_empty);
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

    public function actionGenerateListCsv($data, &$success)
    {
        $timestamp_debut = microtime(true);
        $errors = array();
        $warnings = array();
        $success = 'Fichier généré avec succès';
        $success_callback = '';

        $list_name = BimpTools::getArrayValueFromPath($data, 'list_name', '', $errors, 1, 'Nom de la liste absent');
        $list_type = BimpTools::getArrayValueFromPath($data, 'list_type', '', $errors, 1, 'Type de liste absent');
        $list_data = BimpTools::getArrayValueFromPath($data, 'list_data', array(), $errors, 1, 'Paramètres de la liste absents');

        if (!in_array($list_type, array('list_table', 'stats_list'))) {
            $errors[] = 'Type de liste "' . $list_type . '" invalide';
        } else {
            $file_name = BimpTools::getArrayValueFromPath($data, 'file_name', $this->getLabel() . '_' . date('Y-m-d'));
            $separator = BimpTools::getArrayValueFromPath($data, 'separator', ';');
            $headers = (int) BimpTools::getArrayValueFromPath($data, 'headers', 1);
            $col_options = BimpTools::getArrayValueFromPath($data, 'cols_options', array());

            $list_data['param_n'] = 0;
            $list_data['param_p'] = 1;

            $dir = PATH_TMP . '/bimpcore';
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
        }

        if (!count($errors)) {
            $post_temp = $_POST;
            $_POST = $list_data;

            $list = null;

            switch ($list_type) {
                case 'list_table':
                    $list = new BC_ListTable($this, $list_name, 1, isset($list_data['id_parent']) ? (int) $list_data['id_parent'] : 0);
                    break;

                case 'stats_list':
                    $list = new BC_StatsList($this, $list_name, isset($list_data['id_parent']) ? (int) $list_data['id_parent'] : 0);
                    break;
            }

            if (is_null($list)) {
                $errors[] = 'Type de liste "' . $list_type . '" invalide';
            } elseif (count($list->errors)) {
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

        $timestamp_fin = microtime(true);
        $difference_ms = $timestamp_fin - $timestamp_debut;

        dol_syslog("File : " . $difference_ms, 3, 0, "_csv");

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionGetGraphData($data, &$success)
    {
        global $modeCSV, $modeGraph;
        $modeCSV = $modeGraph = true;
        $success = "Donnée Maj";
        $list_id = (isset($data['list_id']) ? $data['list_id'] : '');
        $list_name = (isset($data['list_name']) ? $data['list_name'] : '');
        $list_data = (isset($data['list_data']) ? $data['list_data'] : array());
        $post_temp = $_POST;
        $_POST = $list_data;

        $list = new BC_ListTable($this, $list_name);

        $data = $this->getInfoGraph();
        if (method_exists($this, 'getGraphDataPoint')) {
            $success_callback = '
var options = {
	animationEnabled: true,
	theme: "light2",
	title:{
		text: "' . $data['title'] . '"
	},
	axisX:{
		title: "' . $data['axeX'] . '",
		valueFormatString: "DD MMM"
	},
	axisY: {
		title: "' . $data['axeY'] . '",
		suffix: " €",
		minimum: 30
	},
	toolTip:{
		shared:true
	},  
	legend:{
		cursor:"pointer",
		verticalAlign: "top",
		horizontalAlign: "left",
		dockInsidePlotArea: false,
		itemclick: toogleDataSeries
	},
	data: ';
            $success_callback .= '[{
		type: "line",
		showInLegend: true,
		name: "' . $data['data1'] . '",
		markerType: "square",
		xValueFormatString: "DD MMM, YYYY",
		yValueFormatString: "#,##0 €",
		dataPoints: [';


            $success_callback .= $list->getPointsForGraph();


            $success_callback .= ']},';
            if (isset($data['data2'])) {
                $success_callback .= '{
                        type: "line",
                        showInLegend: true,
                        name: "' . $data['data2'] . '",
                        markerType: "square",
                        xValueFormatString: "DD MMM, YYYY",
                        color: "#F08080",
                        yValueFormatString: "#,##0 €",
                        visible: 0,
                        dataPoints: [';



                $success_callback .= $list->getPointsForGraph(2);


                $success_callback .= ']},';
            }
            if (isset($data['data3'])) {
                $success_callback .= '{
                        type: "line",
                        showInLegend: true,
                        name: "' . $data['data3'] . '",
                        markerType: "square",
                        xValueFormatString: "DD MMM, YYYY",
                        color: "#CC2080",
                        visible: 0,
                        yValueFormatString: "#,##0 €",
                        dataPoints: [';


                $success_callback .= $list->getPointsForGraph(3);


                $success_callback .= ']},';
            }
            if (isset($data['data11'])) {
                $success_callback .= '{
                        type: "line",
                        showInLegend: true,
                        name: "' . $data['data11'] . '",
                        lineDashType: "dash",
                        markerType: "square",
                        xValueFormatString: "DD MMM, YYYY",
                        yValueFormatString: "#,##0 €",
                        dataPoints: [';


                $success_callback .= $list->getPointsForGraph(11);


                $success_callback .= ']},';
            }
            $success_callback .= ']';
            $success_callback .= '};';
            $success_callback .= '$("#' . $list_id . '_chartContainer").CanvasJSChart(options);';


            $success_callback .= 'function toogleDataSeries(e){
                        if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
                                e.dataSeries.visible = false;
                        } else{
                                e.dataSeries.visible = true;
                        }
                        e.chart.render();
                }';


            return array(
                'errors'           => $errors,
                'warnings'         => $warnings,
                'success_callback' => $success_callback
            );
        }
    }

    public function actionBulkDelete($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_objects = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

        if (!count($id_objects)) {
            $errors[] = 'Aucun' . $this->e() . ' ' . $this->getLabel() . ' sélectionné' . $this->e();
        } else {
            $nOk = 0;
            $obj_label = BimpTools::ucfirst($this->getLabel()) . ' ';

            foreach ($id_objects as $id) {
                $instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $id);

                if (!BimpObject::objectLoaded($instance)) {
                    $warnings[] = ucfirst($this->getLabel('the')) . ' d\'ID ' . $id . ' n\existe plus';
                    continue;
                }

                $obj_err = $instance->delete(); // Ne surtout pas forcer (les droits doivent être vérifiés). 

                if (count($obj_err)) {
                    $warnings[] = BimpTools::getMsgFromArray($obj_err, $obj_label . $instance->getRef(true));
                } else {
                    $nOk++;
                }
            }

            if ($nOk > 1) {
                $success = $nOk . ' ' . $this->getLabel('name_plur') . ' supprimé' . $this->e() . 's avec succès';
            } else {
                $success = $nOk . ' ' . $this->getLabel() . ' supprimé' . $this->e() . ' avec succès';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionBulkEditField($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_objects = BimpTools::getArrayValueFromPath($data, 'id_objects', array());
        $nOk = 0;

        if (!is_array($id_objects) || empty($id_objects)) {
            $errors[] = 'Aucun' . $this->e() . ' ' . $this->getLabel() . ' sélectionné' . $this->e();
        } else {
            $field_name = BimpTools::getArrayValueFromPath($data, 'field_name', '', $errors, true, 'Nom du champ à éditer absent');

            if ($field_name) {
                if (!isset($data[$field_name])) {
                    $errors[] = 'Valeur à assigner "' . $field_name . '" absente';
                } else {
                    $value = $data[$field_name];

                    if (!$this->field_exists($field_name)) {
                        $errors[] = 'Le champ "' . $field_name . '" n\'existe pas dans l\'objet "' . BimpTools::ucfirst($this->getLabel()) . '"';
                    }

                    if (!$this->canEditField($field_name)) {
                        $errors[] = 'Vous n\'avez pas la permission d\'éditer le champ "' . $this->getConf('fields/' . $field_name . '/label', $field_name) . '"';
                    }

                    if (!count($errors)) {
                        foreach ($id_objects as $id_object) {
                            $instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $id_object);

                            if (!BimpObject::objectLoaded($instance)) {
                                $warnings[] = ucfirst($this->getLabel('the')) . ' d\'ID ' . $id_object . ' n\'existe plus';
                            } else {
                                $mode = BimpTools::getArrayValueFromPath($data, 'update_mode', 'udpate_object');
                                $force_update = BimpTools::getArrayValueFromPath($data, 'force_update', false);

                                $obj_warnings = array();
                                $obj_errors = array();

                                switch ($mode) {
                                    case 'update_field':
                                        $obj_errors = $instance->updateField($field_name, $value, null, $force_update, $force_update);
                                        break;

                                    case 'update_object':
                                    default:
                                        $instance->set($field_name, $value);
                                        $obj_errors = $instance->update($obj_warnings, $force_update);
                                        break;
                                }


                                if (count($obj_errors)) {
                                    $warnings[] = BimpTools::getMsgFromArray($obj_errors, 'Echec de la mise à jour ' . $this->getLabel('of_the') . ' "' . $instance->getRef() . '"');
                                } else {
                                    $nOk++;
                                }

                                if (count($obj_warnings)) {
                                    $warnings[] = BimpTools::getMsgFromArray($obj_warnings, 'Erreurs suite à la mise à jour ' . $this->getLabel('of_the') . ' "' . $instance->getRef() . '"');
                                }
                            }
                        }

                        if ($nOk > 0) {
                            if ($nOk > 1) {
                                $success = $nOk . ' ' . $this->getLabel('name_plur') . ' mis' . $this->e() . 's à jour avec succès';
                            } else {
                                $success = '1 ' . $this->getLabel() . ' mis' . $this->e() . ' à jour avec succès';
                            }
                        } else {
                            $warnings[] = 'Aucun' . $this->e() . ' ' . $this->getLabel() . ' n\'a été mis' . $this->e() . ' à jour';
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

    // Gestion statique des objets:

    public static function createBimpObject($module, $object_name, $data, $force_create = false, &$errors = array(), &$warnings = array())
    {
        $instance = static::getInstance($module, $object_name);

        if (is_a($instance, 'BimpObject')) {
            $create_warnings = array();
            $create_errors = $instance->validateArray($data);

            if (!count($create_errors)) {
                $create_errors = $instance->create($create_warnings, $force_create);
            }

            $label = $instance->getLabel('of_the');
            $ref_prop = $instance->getRefProperty();

            if ($ref_prop && isset($data[$ref_prop])) {
                $label .= ' "' . $data[$ref_prop] . '"';
            }

            if (count($create_errors)) {
                $errors[] = BimpTools::getMsgFromArray($create_errors, 'Echec de la création ' . $label);
            }

            if (count($create_warnings)) {
                $warnings[] = BimpTools::getMsgFromArray($create_warnings, 'Erreurs suite à la création ' . $label);
            }

            return $instance;
        }

        $errors[] = 'L\'objet "' . $object_name . '" n\'existe pas dans le module "' . $module . '"';
        return null;
    }

    public static function changeBimpObjectId($old_id, $new_id, $module, $object_name)
    {
        $list = static::getTypeOfBimpObjectLinked($module, $object_name);
        foreach ($list as $module => $objects) {
            foreach ($objects as $class_name => $fields) {
                foreach ($fields as $name_field => $objTmp) {
                    $listObj = $objTmp->getListObjects(array($name_field => $old_id));
                    foreach ($listObj as $objOk) {
                        $objOk->updateField($name_field, $new_id, null, true);
                    }
                }
            }
        }

        // Mise à jour des assos:

        $bdb = self::getBdb();

        $where = 'src_object_type = \'bimp_object\' AND src_object_module = \'' . $module . '\' AND src_object_name = \'' . $object_name . '\'';
        $where .= ' AND src_id_object = ' . (int) $old_id;
        $bdb->update('bimpcore_objects_associations', array(
            'src_id_object' => $new_id,
                ), $where);

        $where = 'dest_object_type = \'bimp_object\' AND dest_object_module = \'' . $module . '\' AND dest_object_name = \'' . $object_name . '\'';
        $where .= ' AND dest_id_object = ' . (int) $old_id;
        $bdb->update('bimpcore_objects_associations', array(
            'dest_id_object' => $new_id,
                ), $where);
    }

    public static function getTypeOfBimpObjectLinked($module, $object_name, $withObjectStatic = true)
    {
        $list = BimpCache::getBimpObjectsList();

        $objectsResult = array();

        $dol_object_class = false;
        $config = new BimpConfig(DOL_DOCUMENT_ROOT . '/' . $module . '/objects/', $object_name, null);
        if ($config->isDefined('dol_object')) {
            $dol_object_class = get_class($config->getObject('dol_object'));
        }

        foreach ($list as $mod => $objects) {
            foreach ($objects as $name) {
                $instance = BimpObject::getInstance($mod, $name);
                if (is_a($instance, 'BimpObject')) {
                    $table = $instance->getTable();

                    if (!$table) {
                        continue;
                    }

                    foreach ($instance->params['objects'] as $obj_conf_name => $obj_params) {
                        if (!$obj_params['relation'] === 'hasOne') {
                            continue;
                        }

                        $obj_module = '';
                        $obj_name = '';

                        if (isset($obj_params['instance']['bimp_object'])) {
                            if (is_string($obj_params['instance']['bimp_object'])) {
                                $obj_name = $obj_params['instance']['bimp_object'];
                                $obj_module = $instance->module;
                            } else {
                                if (isset($obj_params['instance']['bimp_object']['name'])) {
                                    $obj_name = $obj_params['instance']['bimp_object']['name'];
                                    $obj_module = $obj_params['instance']['bimp_object']['module'];
                                    if (!$obj_module) {
                                        $obj_module = $instance->module;
                                    }
                                }
                            }
                        }

                        if ($obj_name && $obj_module) {//C'est bien un bimp object
                            $objTmp = BimpObject::getInstance($obj_module, $obj_name);
                            if ($obj_module === $module && is_a($objTmp, $object_name)) {
                                //                        if ($obj_module === $module && $object_name == $obj_name) {
                                $params = $instance->config->getParams('objects/' . $obj_conf_name . '/instance');
                                if (isset($params['id_object']['field_value'])) {
                                    $field = $params['id_object']['field_value'];
                                    if ($field && $instance->field_exists($field)) {
//                                        if ($instance->isExtraField($field)) // Attention: ne pas confondre les bimp extra fields (param "extra: 1") avec les dol extra fields (param "dol_extra_field: 1").  
//                                            $field = 'ef.' . $field;
                                        if ($withObjectStatic)
                                            $objectsResult[$mod][$name][$field] = $instance;
                                        else
                                            $objectsResult[$mod][$name][$field] = $field;
                                    }
                                }
                            }
                        } elseif ($dol_object_class) {//on test le dol_object
                            $obj_module = '';
                            $obj_file = '';
                            $obj_class = '';

                            if (isset($obj_params['instance']['dol_object'])) {
                                if (is_string($obj_params['instance']['dol_object'])) {
                                    $obj_module = $obj_file = $obj_params['instance']['dol_object'];
                                    $obj_class = ucfirst($obj_file);
                                } else {
                                    if (isset($obj_params['instance']['dol_object']['module'])) {
                                        $obj_module = $obj_params['instance']['dol_object']['module'];
                                        $obj_file = isset($obj_params['instance']['dol_object']['file']) ? $obj_params['instance']['dol_object']['file'] : $obj_module;
                                        $obj_class = isset($obj_params['instance']['dol_object']['class']) ? $obj_params['instance']['dol_object']['class'] : ucfirst($obj_file);
                                    }
                                }
                            }

                            if (!$obj_module || !$obj_file || !$obj_class) {
                                continue;
                            }

                            if ($obj_class === $dol_object_class) {
                                $params = $instance->config->getParams('objects/' . $obj_conf_name . '/instance');
                                if (isset($params['id_object']['field_value'])) {
                                    $field = $params['id_object']['field_value'];
                                    if ($withObjectStatic)
                                        $objectsResult[$mod][$name][$field] = $instance;
                                    else
                                        $objectsResult[$mod][$name][$field] = $field;
                                }
                            }
                        }
                    }
                }
            }
        }


        return $objectsResult;
    }

    public static function getBimpObjectLinked($module, $object_name, $id_object)
    {
        $tabResult = array();

        $list = static::getTypeOfBimpObjectLinked($module, $object_name);
        foreach ($list as $module => $data) {
            foreach ($data as $class_name => $data2) {
                foreach ($data2 as $name_field => $objTmp) {
                    $listObj = $objTmp->getList(array($name_field => $id_object));
                    foreach ($listObj as $objOk)
                        $tabResult[] = $objOk;
                }
            }
        }

        return $tabResult;
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
            if (BimpTools::getValue("viewstatut") != "") {
                $url .= "&fk_statut=" . BimpTools::getValue("viewstatut");
            }
            if (BimpTools::getValue("statut") != "") {
                $url .= "&fk_statut=" . BimpTools::getValue("statut");
            }
            if (BimpTools::getValue("search_status") != "") {
                $url .= "&fk_statut=" . BimpTools::getValue("search_status");
            }

//            https://erp.bimp.fr/test11/bimpcommercial/index.php?search=1&object=propal&sall=PR1809-91794&fc=propals
        } else {
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
        return $this->iAmAdmin();
    }

    public function iAmAdmin()
    {
        global $user;
        if ($user->admin)
            return 1;
    }

    public static function priceToCsv($price)
    {
        return str_replace(array(" ", 'EUR', '€'), "", str_replace(".", ",", $price));
    }

    public static function getListExtrafield($name, $type, $withVide = true)
    {
        $return = array();
        $sql = self::getBdb()->db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "extrafields` WHERE `name` LIKE '" . $name . "' AND `elementtype` = '" . $type . "'");
        while ($ln = self::getBdb()->db->fetch_object($sql)) {
            $param = unserialize($ln->param);
            if (isset($param['options']))
                $return = $param['options'];
        }
        if (!isset($return[0]) && $withVide) {
            $newReturn = array(0 => '');
            foreach ($return as $id => $val)
                $newReturn[$id] = $val;
            $return = $newReturn;
        }

        return $return;
    }

    public static function useLogistique()
    {
        return BimpTools::isModuleDoliActif('BIMPLOGISTIQUE');
    }

    public static function useReservations()
    {
        return BimpTools::isModuleDoliActif('BIMPLOGISTIQUE');
    }

    public static function useSav()
    {
        return BimpTools::isModuleDoliActif('BIMPSUPPORT');
    }

    public static function useApple()
    {
        return BimpTools::isModuleDoliActif('BIMPSUPPORT');
    }
}
