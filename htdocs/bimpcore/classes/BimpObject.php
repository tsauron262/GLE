<?php

class BimpObject extends BimpCache
{

    public $db = null;
    public $cache_id = 0;
    public $module = '';
    public $object_name = '';
    public $config = null;
    public $id = null;
    public $alias = 'a';
    public $ref = "";
    public static $status_list = array();
    public static $modeDateGraph = 'day';
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
    public static $ref_properties = array('ref', 'reference', 'code');
    public static $status_properties = array('status', 'fk_statut', 'statut');
    public static $date_update_properties = array('date_update', 'tms');
    public static $user_update_properties = array('user_update', 'fk_user_modif');
    public static $allowedDbNullValueDataTypes = array('date', 'datetime', 'time');
    public static $logo_properties = array('logo');
    public static $secteur_properties = array('ef_type', 'secteur');
    public $use_commom_fields = false;
    public $use_positions = false;
    public $params_defs = array(
        'entity_name'              => array('default' => 0),
        'abstract'                 => array('data_type' => 'bool', 'default' => 0),
        'use_clones'               => array('data_type' => 'bool', 'default' => 1),
        'table'                    => array('default' => ''),
        'controller'               => array('default' => ''),
        'icon'                     => array('default' => ''),
        'primary'                  => array('default' => 'id'),
        'common_fields'            => array('data_type' => 'bool', 'default' => 1),
        'in_cache_serveur'         => array('data_type' => 'bool', 'default' => 1),
        'collections'              => array('data_type' => 'bool', 'default' => 1),
        'header_list_name'         => array('default' => ''),
//        'header_btn'               => array('data_type' => 'array', 'default' => array()),
        'header_edit_form'         => array('default' => ''),
        'header_delete_btn'        => array('data_type' => 'bool', 'default' => 1),
        'list_page_url'            => array('data_type' => 'array'),
        'parent_module'            => array('default' => ''),
        'parent_object'            => array('default' => ''),
        'parent_id_property'       => array('defautl' => ''),
        'positions'                => array('data_type' => 'bool', 'default' => 0),
        'position_insert'          => array('default' => 'before'),
        'labels'                   => array('type' => 'definitions', 'defs_type' => 'labels'),
//        'objects'                  => array('type' => 'definitions', 'defs_type' => 'object_child', 'multiple' => true),
        'force_extrafields_update' => array('data_type' => 'bool', 'default' => 0),
        'name_syntaxe'             => array('default' => ''),
        'nom_url'                  => array('type' => 'definitions', 'defs_type' => 'nom_url'),
        'new_status_logs'          => array('data_type' => 'bool', 'default' => 0),
        'objects'                  => array('type' => 'keys'),
        'associations'             => array('type' => 'keys'),
        'fields'                   => array('type' => 'keys'),
//        'forms'                    => array('type' => 'keys'),
//        'fields_tables'            => array('type' => 'keys'),
//        'views'                    => array('type' => 'keys'),
//        'lists'                    => array('type' => 'keys'),
//        'cards'                    => array('type' => 'keys'),
//        'searches'                 => array('type' => 'keys'),
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
    public $isDeleting = false;
    public $thirdparty = null;
    public $force_update = false;

    // Gestion instance:

    public static function getInstance($module, $object_name, $id_object = null, $parent = null)
    {
        $className = '';
        $instance = null;

        if (!$module || !$object_name) {
            $module = 'bimpcore';
            $object_name = 'BimpObject';
        }

        $use_clones = (int) BimpCore::getConf('use_bimp_object_instances_clones');
        $cache_key = $module . '_' . $object_name . '_base_instance';

        if ($use_clones) {
            if (isset(self::$cache[$cache_key]) && is_a(self::$cache[$cache_key], 'BimpObject') && (int) self::$cache[$cache_key]->params['use_clones']) {
                $instance = clone self::$cache[$cache_key];
                $className = get_class($instance);
            }
        }

        if (is_null($instance)) {
            if ($object_name !== 'BimpObject') {
                $file = DOL_DOCUMENT_ROOT . '/' . $module . '/objects/' . $object_name . '.class.php';
                if (file_exists($file)) {
                    if (!class_exists($object_name)) {
                        require_once $file;
                    }
                    $className = $object_name;
                }

                // Surcharge version:
                if (defined('BIMP_EXTENDS_VERSION')) {
                    $version_file = DOL_DOCUMENT_ROOT . '/' . $module . '/extends/versions/' . BIMP_EXTENDS_VERSION . '/objects/' . $object_name . '.class.php';
                    if (file_exists($version_file)) {
                        $className = $object_name . '_ExtVersion';
                        if (!class_exists($className)) {
                            require_once $version_file;
                        }
                    }
                }

                // Surcharge entité: 
                if (BimpCore::getExtendsEntity() != '') {
                    $entity_file = DOL_DOCUMENT_ROOT . '/' . $module . '/extends/entities/' . BimpCore::getExtendsEntity() . '/objects/' . $object_name . '.class.php';
                    if (file_exists($entity_file)) {
                        $className = $object_name . '_ExtEntity';
                        if (!class_exists($className)) {
                            require_once $entity_file;
                        }
                    }
                }
            }

            // Si Aucune classe trouvée on utilise la classe BimpObject: 
            if (!$className) {
                $className = 'BimpObject';
            }

            $instance = new $className($module, $object_name);

            // Si pas de classe définie pour l'objet, on vérifie toutes les 
            // éventuelles extensions pour instancier la première classe trouvée
            if ($className === 'BimpObject' && $object_name !== 'BimpObject') {
                $ext_instance = $instance;
                $ext_className = $className;
                $ext_module = $module;
                $ext_object_name = $object_name;
                $n = 0;

                while ($ext_className === 'BimpObject') {
                    $n++;
                    if ($n > 100) {
                        break; // Protection boucle infinie
                    }

                    if ($ext_instance->config->isDefined('extends') && $ext_instance->config->isDefined('extends/module') && $ext_instance->config->isDefined('extends/object_name')) {
                        $prev_module = $ext_module;
                        $prev_object_name = $ext_object_name;
                        $ext_module = $ext_instance->getConf('extends/module', '');
                        $ext_object_name = $ext_instance->getConf('extends/object_name', '');

                        if ($ext_module && $ext_object_name && !($ext_module === $prev_module && $ext_object_name === $prev_object_name)) {
                            $ext_file = DOL_DOCUMENT_ROOT . '/' . $ext_module . '/objects/' . $ext_object_name . '.class.php';
                            if (file_exists($ext_file)) {
                                $ext_className = $ext_object_name;
                                if (!class_exists($ext_className)) {
                                    require_once $ext_file;
                                }
                            }

                            // Surcharge version:
                            if (defined('BIMP_EXTENDS_VERSION')) {
                                $ext_version_file = DOL_DOCUMENT_ROOT . '/' . $ext_module . '/extends/versions/' . BIMP_EXTENDS_VERSION . '/objects/' . $ext_object_name . '.class.php';
                                if (file_exists($ext_version_file)) {
                                    $ext_className = $ext_object_name . '_ExtVersion';
                                    if (!class_exists($ext_className)) {
                                        require_once $ext_version_file;
                                    }
                                }
                            }

                            // Surcharge entité: 
                            if (BimpCore::getExtendsEntity() != '') {
                                $ext_entity_file = DOL_DOCUMENT_ROOT . '/' . $ext_module . '/extends/entities/' . BimpCore::getExtendsEntity() . '/objects/' . $ext_object_name . '.class.php';
                                if (file_exists($ext_entity_file)) {
                                    $ext_className = $ext_object_name . '_ExtEntity';
                                    if (!class_exists($ext_className)) {
                                        require_once $ext_entity_file;
                                    }
                                }
                            }

                            $ext_instance = new $ext_className($ext_module, $ext_object_name);
                            continue;
                        }
                    }
                    break;
                }

                if ($className !== $ext_className) {
                    $instance = new $ext_className($module, $object_name);
                }
            }

            if ($use_clones && (int) $instance->params['use_clones']) {
                $cache_key = $module . '_' . $object_name . '_base_instance';
                self::setCache($cache_key, $instance);
                $instance = clone self::$cache[$cache_key];
            }
        }

        if (is_a($instance, 'BimpObject')) {
            if (!is_null($id_object)) {
                $instance->fetch($id_object, $parent);
            } else {
                $instance->parent = $parent;
            }
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

    public static function loadClass($module, $object_name, &$final_class_name = '')
    {
        $final_class_name = $object_name;
        if (!class_exists($object_name)) {
            $file = DOL_DOCUMENT_ROOT . '/' . $module . '/objects/' . $object_name . '.class.php';
            if (file_exists($file)) {
                require_once $file;

                // Vérif surcharges (nécessaire car certaine surcharge peuvent affecter les variables statiques des classes de base)
                // Version:
                if (defined('BIMP_EXTENDS_VERSION')) {
                    $version_file = DOL_DOCUMENT_ROOT . '/' . $module . '/extends/versions/' . BIMP_EXTENDS_VERSION . '/objects/' . $object_name . '.class.php';
                    if (file_exists($version_file)) {
                        $final_class_name = $object_name . '_ExtVersion';
                        if (!class_exists($final_class_name)) {
                            require_once $version_file;
                        }
                    }
                }

                // Entité: 
                if (BimpCore::getExtendsEntity() != '') {
                    $entity_file = DOL_DOCUMENT_ROOT . '/' . $module . '/extends/entities/' . BimpCore::getExtendsEntity() . '/objects/' . $object_name . '.class.php';
                    if (file_exists($entity_file)) {
                        $final_class_name = $object_name . '_ExtEntity';
                        if (!class_exists($final_class_name)) {
                            require_once $entity_file;
                        }
                    }
                }
                return true;
            }
        } elseif (class_exists($object_name . '_ExtEntity')) {
            $final_class_name = $object_name . '_ExtEntity';
        } elseif (class_exists($object_name . '_ExtVersion')) {
            $final_class_name = $object_name . '_ExtVersion';
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

    public function initBdd($no_transaction_db = -1)
    {
        if ($no_transaction_db < 0) {
            $no_transaction_db = (int) $this->getConf('no_transaction_db', 0, false, 'bool');
        }

        $this->db = self::getBdb($no_transaction_db, $this->modeArchive);
    }

    public function __construct($module, $object_name)
    {
        $this->module = $module;
        $this->object_name = $object_name;

        $this->config = BimpConfig::getObjectConfigInstance($module, $object_name, $this);

        if ($this->config->isDefined('mode_archive')) {
            $this->modeArchive = (int) $this->getConf('mode_archive', 0, false, 'bool');
        }

        $this->initBdd();

        $this->use_commom_fields = (int) $this->getConf('common_fields', 1, false, 'bool');
        $this->use_positions = (int) $this->getConf('positions', 0, false, 'bool');

        if ($this->config->isDefined('dol_object')) {
            $this->dol_object = $this->config->getObject('dol_object');

            if (is_object($this->dol_object)) {
                $this->dol_object->db = $this->db->db;
            }
            $this->use_commom_fields = 0;
        }

        $this->addCommonFieldsConfig();
        
        $this->addEntityFieldConfig();

        $errors = array();
        $this->params = BimpComponent::fetchParamsStatic($this->config, '', $this->params_defs, $errors);

        if (!$this->params['parent_module']) {
            $this->params['parent_module'] = $this->module;
        }

        $this->addConfigExtraParams();
    }

    public function __clone()
    {
        if (is_object($this->config)) {
            $this->config = clone $this->config;
            $this->config->instance = $this;
        } else {
            $this->config = BimpConfig::getObjectConfigInstance($this->module, $this->object_name, $this);
            $this->addCommonFieldsConfig();
            $this->addEntityFieldConfig();
            $this->addConfigExtraParams();
        }
    }

    public function __destruct()
    {
        unset($this->config);
    }

    public function isDolObject()
    {
        return $this->config->isDefined('dol_object');
    }
    
    protected function addEntityFieldConfig(){
        if (BimpTools::isModuleDoliActif('MULTICOMPANY') && $this->getEntity_name()){
            $this->config->addParams('fields', array(
                    'entity' => array(
                        'label'    => 'Entité',
                        'type'     => 'id',
                        'values'  => array(
                            'array' => 'entitiesCache'
                        ), 
    //            array: condReglements
    //        input: 
    //            type: select_cond_reglement
    //        default_value: 
    //            callback: getCondReglementBySociete
                        'editable' => 1
                    )
                        ), 'initial');
        }
    }

    protected function addCommonFieldsConfig()
    {
        $primary = $this->getPrimary();

        $this->config->addParams('fields', array(
            $primary => array(
                'label'    => 'ID',
                'type'     => 'id',
                'input'    => array(
                    'type' => 'hidden'
                ),
                'search'   => array(
                    'type'  => 'value_part',
                    'input' => array(
                        'type' => 'text'
                    )
                ),
                'editable' => 0
            )
                ), 'initial');

        if ($this->use_commom_fields) {
            $this->config->addParams('objects', array(
                'user_create' => array(
                    'relation' => 'hasOne',
                    'delete'   => 0,
                    'instance' => array(
                        'bimp_object' => array(
                            'module' => 'bimpcore',
                            'name'   => 'Bimp_User'
                        ),
                        'id_object'   => array(
                            'field_value' => 'user_create'
                        )
                    )
                )
                    ), 'initial');
            $this->config->addParams('objects', array(
                'user_update' => array(
                    'relation' => 'hasOne',
                    'delete'   => 0,
                    'instance' => array(
                        'bimp_object' => array(
                            'module' => 'bimpcore',
                            'name'   => 'Bimp_User'
                        ),
                        'id_object'   => array(
                            'field_value' => 'user_update'
                        )
                    )
                )
                    ), 'initial');

            $this->config->addParams('fields', array(
                'date_create' => array(
                    'label'    => 'Créé le',
                    'type'     => 'datetime',
                    'input'    => array(
                        'type' => 'hidden'
                    ),
                    'editable' => 0
                )
                    ), 'initial');

            $this->config->addParams('fields', array(
                'date_update' => array(
                    'label'    => 'Mis à jour le',
                    'type'     => 'datetime',
                    'input'    => array(
                        'type' => 'hidden'
                    ),
                    'editable' => 0
                )
                    ), 'initial');

            $this->config->addParams('fields', array(
                'user_create' => array(
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
                    'editable'      => 0
                )
                    ), 'initial');

            $this->config->addParams('fields', array(
                'user_update' => array(
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
                    'editable'      => 0
                )
                    ), 'initial');
        }

        if ($this->use_positions) {
            $this->config->addParams('fields', array(
                'position' => array(
                    'label'    => 'Position',
                    'type'     => 'int',
                    'input'    => array(
                        'type' => 'hidden'
                    ),
                    'editable' => 0
                )
                    ), 'initial');
        }

        $parentModule = $this->getParentModule();
        $parentObjectName = $this->getParentObjectName();
        $parentIdProperty = $this->getParentIdProperty();

        if ($parentModule && $parentObjectName && $parentIdProperty) {
            $this->config->addParams('objects', array(
                'parent' => array(
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
                )
                    ), 'initial');
        }
    }

    public function useNoTransactionsDb()
    {
        $this->db = BimpCache::getBdb(true);

        if (is_object($this->dol_object)) {
            $this->dol_object->db = $this->db->db;
        }
    }

    public function useTransactionsDb()
    {
        $this->db = BimpCache::getBdb(false);

        if (is_object($this->dol_object)) {
            $this->dol_object->db = $this->db->db;
        }
    }

    // Getters configuation:

    public function getGeneralConf($name, $default = null, $module = null)
    {
        return BimpCore::getConf($name, $default, $module);
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

        if ($this->config->isDefined('objects/' . $child_name . '/instance/id_object/field_value')) {
            return $this->config->get('objects/' . $child_name . '/instance/id_object/field_value', '');
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

    public function getNonFetchParent()
    {
        if (!is_null($this->parent))
            return $this->parent;
        else {
            $module = $this->getParentModule();
            $object_name = $this->getParentObjectName();
            return $this->getBimpObjectInstance($module, $object_name);
        }
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

    public function getSignatureDocFileDir($doc_type = '')
    {
        return $this->getFilesDir();
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

    public function getNameProperties()
    {
        $fields = array();

        if ($this->params['name_syntaxe']) {
            $syntaxe = $this->params['name_syntaxe'];

            $n = 0;
            while (preg_match('/<(.+)>/U', $syntaxe, $matches)) {
                $field = $matches[1];
                if ($this->field_exists($field)) {
                    $fields[] = $field;
                }
                $syntaxe = str_replace('<' . $field . '>', '', $syntaxe);

                $n++;
                if ($n > 10) {
                    break;
                }
            }
        } else {
            foreach (self::$name_properties as $prop) {
                if ($this->field_exists($prop)) {
                    $fields[] = $prop;
                    break;
                }
            }
        }

        return $fields;
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

    public function getDateUpdateProperty()
    {
        foreach (static::$date_update_properties as $prop) {
            if ($this->field_exists($prop)) {
                return $prop;
            }
        }

        return '';
    }

    public function getUserUpdateProperty()
    {
        foreach (static::$user_update_properties as $prop) {
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

    public function getSecteurProperty()
    {
        foreach (static::$secteur_properties as $prop) {
            if ($this->field_exists($prop)) {
                return $prop;
            }
        }

        return '';
    }

    public function getIsObjectActiveFields()
    {
        // Fonction renommée car le nom ititial prête à confusion (getActiveFields = obtenir les champs actifs)
        return array();
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
        $name_props = $this->getNameProperties();
        $first_name_prop = '';
        if (isset($name_props[0])) {
            $first_name_prop = $name_props[0];
        }

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
            $disabled = $this->getConf($path . '/disabled', array(), false, 'array');
            $order_way = $this->getConf($path . '/order_way', 'asc');

            if (!$order_by) {
                if ($ref_prop) {
                    $order_by = $this->getFieldSqlKey($ref_prop, 'a', null, $filters, $joins);
                }

                if (!$order_by && $first_name_prop) {
                    $order_by = $this->getFieldSqlKey($first_name_prop, 'a', null, $filters, $joins);
                }

                if (!$order_by) {
                    $order_by = 'a.' . $primary;
                }
            }

            foreach ($fields_search as $key => $field) {
                if (!preg_match('/^(.+)\.(.+)/', $field)) {
                    $fields_search[$key] = $this->getFieldSqlKey($field, 'a', null, $filters, $joins);
                }
            }

            foreach ($fields_return as $key => $field) {
                if (!preg_match('/^(.+)\.(.+)/', $field)) {
                    $fields_search[$key] = $this->getFieldSqlKey($field, 'a', null, $filters, $joins);
                }
            }

            if (!in_array($fields_return, array('a.' . $primary))) {
                $fields_return[] = 'a.' . $primary;
            }
        } else {
            $filters = $this->getSearchListFilters($joins);
            $fields_search[] = 'a.' . $primary;
            $fields_return[] = 'a.' . $primary;

            if ($ref_prop && $this->field_exists($ref_prop)) {
                $ref_key = $this->getFieldSqlKey($ref_prop, 'a', null, $filters, $joins);

                if ($ref_key) {
                    $fields_search[] = $ref_key;
                    $fields_return[] = $ref_key;
                    $syntaxe .= '<' . $ref_key . '>';
                    $order_by = $ref_key;
                }
            }

            if (count($name_props)) {
                foreach ($name_props as $name_prop) {
                    $name_key = $this->getFieldSqlKey($name_prop, 'a', null, $filters, $joins);
                    if ($name_key) {
                        $fields_search[] = $name_key;
                        $fields_return[] = $name_key;

                        $syntaxe .= ($syntaxe ? ' - ' : '') . '<' . $name_key . '>';

                        if (!$order_by) {
                            $order_by = $name_key;
                        }
                    }
                }
            }

            if (!$order_by) {
                $order_by = 'a.' . $primary;
            }
        }

        return array(
            'fields_search' => $fields_search,
            'fields_return' => $fields_return,
            'disabled'      => $disabled,
            'filters'       => $filters,
            'joins'         => $joins,
            'label_syntaxe' => $syntaxe,
            'order_by'      => $order_by,
            'order_way'     => $order_way
        );
    }

    public function getSearchResults($search_name, $search_value, $options = array())
    {
        $results = $resultsDisabled = array();

        if ((string) $search_value) {
            $searches = array();

            if (is_array($search_name)) {
                $searches = $search_name;
            } elseif ($search_name === 'all') {
                foreach ($this->config->getCompiledParams('searches') as $name => $search_params) {
                    $searches[] = $name;
                }
            } elseif ($search_name) {
                $searches[] = $search_name;
            }

            if (empty($searches)) {
                $searches[] = 'default';
            }

            $search_value = $this->db->db->escape(strtolower($search_value));

            foreach ($searches as $search_name) {
                $params = $this->getSearchParams($search_name);

                $card = (isset($options['card']) ? $options['card'] : '');
                $filters = (isset($params['filters']) && is_array($params['filters']) ? $params['filters'] : array());
                $joins = (isset($params['joins']) && is_array($params['joins']) ? $params['joins'] : array());
                $primary = $this->getPrimary();

                $search_filter = '';

                foreach (explode(' ', $search_value) as $search) {
                    $or_sql = '';

                    foreach ($params['fields_search'] as $field) {
                        $or_sql .= ($or_sql ? ' OR ' : '') . '' . $field . ' LIKE \'%' . $search . '%\'';
                    }

                    if ($or_sql) {
                        $search_filter .= ($search_filter ? ' AND ' : '') . '(' . $or_sql . ')';
                    }
                }

                if ($options['active']) {
                    $fields = $this->getIsObjectActiveFields();
                    foreach ($fields as $field) {
                        $filters[$field] = 1;
                    }
                }

                if ($search_filter) {
                    $filters['search_custom'] = array(
                        'custom' => '(' . $search_filter . ')'
                    );
                }

                $max_results = (isset($options['max_results']) ? (int) $options['max_results'] : 200);

                $rows = $this->getList($filters, $max_results, 1, $params['order_by'], $params['order_way'], 'array', $params['fields_return'], $joins, null, 'DESC');

                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        if (isset($results[(int) $r[$primary]])) {
                            continue;
                        }

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
                                        $label = str_replace('<' . $field . '>', BimpTools::getDataLightWithPopover($r[$field_name], 40), $label);
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

                        $disabled = 0;
                        foreach ($params['disabled'] as $field => $inut) {
                            if (isset($r[$field]) && !$r[$field])
                                $disabled = 1;
                        }

                        if (!$disabled) {
                            $results[(int) $r[$primary]] = array(
                                'id'    => (int) $r[$primary],
                                'label' => $label,
                                'card'  => $card_html
                            );
                        } else {
                            $resultsDisabled[(int) $r[$primary]] = array(
                                'id'       => (int) $r[$primary],
                                'label'    => $label,
                                'card'     => $card_html,
                                'disabled' => 1
                            );
                        }
                    }
                }
            }
        }
        return BimpTools::merge_array($results, $resultsDisabled, true);
    }

    public function getLinkFields($with_card = true)
    {
        $fields = array();

        $default_syntaxe = '<ref> - <name>';

        $ref_prop = $this->getRefProperty();
        if (!$ref_prop) {
            $default_syntaxe = '<name>';
        }

        $label = BimpTools::getArrayValueFromPath($this->params, 'nom_url/syntaxe', $default_syntaxe);

        if ($this->params['name_syntaxe']) {
            $label = str_replace('<name>', $this->params['name_syntaxe'], $label);
        }

        $n = 0;
        while (preg_match('/<(.+)>/U', $label, $matches)) {
            $field = $matches[1];
            if ($field && $this->field_exists($field)) {
                $fields[] = $field;
            }

            $label = str_replace('<' . $field . '>', '', $label);

            $n++;
            if ($n > 10) {
                break;
            }
        }

        if ((int) BimpTools::getArrayValueFromPath($this->params, 'with_status', 0)) {
            $status_prop = $this->getStatusProperty();

            if ($status_prop && $this->field_exists($status_prop) && !in_array($status_prop, $fields)) {
                $fields[] = $status_prop;
            }
        }

        if ($with_card && !(int) BimpCore::getConf('mode_eco')) {
            $card = BimpTools::getArrayValueFromPath($this->params, 'nom_url/card', '');

            if ($card) {
                $card_fields = $this->getCardFields($card);

                foreach ($card_fields as $card_field) {
                    if (!in_array($card_field, $fields)) {
                        $fields[] = $card_field;
                    }
                }
            }
        }

        return $fields;
    }

    public function getCardFields($card_name)
    {
        $fields = array();

        if ($this->config->isDefined('cards/' . $card_name)) {
            $title = $this->getConf('cards/' . $card_name . '/title', 'nom');

            if (is_string($title) && $title) {
                switch ($title) {
                    case 'ref_nom':
                    case 'nom_ref':
                        $ref_prop = $this->getRefProperty();
                        if ($this->field_exists($ref_prop)) {
                            $fields[] = $ref_prop;
                        }
                    case 'nom':
                        foreach ($this->getNameProperties() as $name_prop) {
                            if ($this->field_exists($name_prop) && !in_array($name_prop, $fields)) {
                                $fields[] = $name_prop;
                            }
                        }
                        break;

                    default:
                        $title_field = '';
                        if ($this->config->isDefined('cards/' . $card_name . '/title/field_value')) {
                            $title_field = $this->getConf('cards/' . $card_name . '/title/field_value', '');
                        }

                        if ($title_field && $this->field_exists($title_field) && !in_array($title_field, $fields)) {
                            $fields[] = $title_field;
                        }
                        break;
                }
            }

            $status = $this->getConf('cards/' . $card_name . '/status', '');
            if (is_string($status) && $status) {
                if ($this->field_exists($status) && !in_array($status, $fields)) {
                    $fields[] = $status;
                }
            }

            if ((int) $this->getConf('cards/' . $card_name . '/logo', 0)) {
                $logo_prop = $this->getLogoProperty();

                if ($this->field_exists($logo_prop) && !in_array($logo_prop, $fields)) {
                    $fields[] = $logo_prop;
                }
            }


            $card_fields = $this->config->getParams('cards/' . $card_name . '/fields');

            foreach ($card_fields as $card_field) {
                if (isset($card_field['field']) && is_string($card_field['field']) &&
                        $this->field_exists($card_field['field']) && !in_array($card_field['field'], $fields)) {
                    $fields[] = $card_field['field'];
                }
            }
        }

        return $fields;
    }

    public function getFieldsList($viewable_only = false, $active_only = true, $with_common_fields = true)
    {
        $fields = array();

        foreach ($this->params['fields'] as $field_name) {
            if ($active_only && !$this->field_exists($field_name)) {
                continue;
            }

            if ($viewable_only && !$this->canViewField($field_name)) {
                continue;
            }

            if (!$with_common_fields == in_array($field_name, static::$common_fields)) {
                continue;
            }

            $fields[] = $field_name;
        }

        return $fields;
    }

    public function getAddFilesButton()
    {
        if ($this->isActionAllowed('addFiles') && $this->canSetAction('addFiles')) {
            return array(
                'label'   => 'Ajouter des fichiers',
                'icon'    => 'fas_folder-plus',
                'onclick' => $this->getJsActionOnclick('addFiles', array(), array(
                    'form_name' => 'add_files'
                ))
            );
        }

        return array();
    }

    public function getEntity_name()
    {
        $entity = $this->getConf('entity_name', 0);
        if ($entity == 0 && isset($this->dol_object) && $this->dol_object->ismultientitymanaged)
            $entity = $this->dol_object->element;

        return $entity;
    }

    public function getEntitysArray($withZero = true)
    {
        $tab = explode(',', getEntity($this->getEntity_name()));
        if ($withZero)
            $tab[] = 0;
        return $tab;
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

    public function isActif()
    {
        $activeFields = $this->getIsObjectActiveFields();
        foreach ($activeFields as $field) {
            if ($this->getData($field) < 1)
                return false;
        }
        return 1;
    }

    public function field_exists($field_name, &$infos = '')
    {
        if ($field_name === $this->getPrimary()) {
            return 1;
        }

        if (!isset($this->params['fields'])) {
            return 0;
        }

        if (!in_array($field_name, $this->params['fields']) && (!$this->use_commom_fields || !in_array($field_name, self::$common_fields))) {
            $infos = 'Le champ "' . $field_name . '" n\'existe pas pour les ' . $this->getLabel('name_plur');
            return 0;
        }

        if (!$this->isFieldActivated($field_name, $infos)) {
            return 0;
        }

        $field_tmp = $field_name;
        if ($this->isDolObject() && $this->isDolExtraField($field_tmp)) {
            $extra_fields = self::getExtraFieldsArray($this->dol_object->table_element);

            if (!is_array($extra_fields) || !isset($extra_fields[$field_tmp])) {
                $infos = 'Extrafield absent pour le champ "' . $field_name . '"';
                return 0;
            }
        }

        return 1;
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
        return in_array($object_name, $this->params['objects']);
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

            if (is_a($this, $instance_parent_object_name) || ($instance_parent_module === $this->module &&
                    $instance_parent_object_name === $this->object_name)) {
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

    public function isContextPublic()
    {
        // pour callbacks: 
        return BimpCore::isContextPublic();
    }

    public function isContextPrivate()
    {
        // pour callbacks: 
        return BimpCore::isContextPrivate();
    }

    public function isUserAdmin()
    {
        global $user;
        if (BimpObject::objectLoaded($user)) {
            return (int) $user->admin;
        }

        return 0;
    }

    public function isLight_exportActif()
    {
        return $this->getConf('export_light', 1);
    }

    // Getters données: 

    public function getData($field, $default = true)
    {
        if ($field === $this->getPrimary() || $field === 'id') {
            return $this->id;
        }

        if (isset($this->data[$field])) {
            return $this->data[$field];
        }

        if ($default)
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

    public function getDataArray($include_id = false, $check_user_rights = false)
    {
        if (!count($this->params['fields'])) {
            return array();
        }

        $data = array();

        foreach ($this->params['fields'] as $field) {
            if ($check_user_rights) {
                if (!$this->canViewField($field)) {
                    continue;
                }
            }
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
        } elseif (is_array($id_object) && !is_array($value)) {
            $value = array();
            $def_val = $this->getConf('fields/' . $field . '/default_value', null, false, 'any');
            foreach ($id_object as $id) {
                if (!isset($value[$id]) || is_null($value[$id])) {
                    $value[$id] = $def_val;
                }
            }
        } else {
            // Checks value:
            if (is_array($id_object)) {
                foreach ($value as $id_object => $val) {
                    $value[$id_object] = $this->getValueFromDb($field, $val);
                    $this->checkFieldValueType($field, $value[$id_object]);
                }
            } else {
                $value = $this->getValueFromDb($field, $value);
                $this->checkFieldValueType($field, $value);
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

            $null_allowed = (int) $this->getConf('fields/' . $field . '/null_allowed', 0);
            $db_value = $this->getDbValue($field, $value, $null_allowed);

            if (!is_null($db_value) || $null_allowed) {
                $this->checkFieldHistory($field, $value);
                $data[$field] = $db_value;
            }
        }

        global $conf;
        if ($this->getEntity_name())
            $data['entity'] = $conf->entity;

        return $data;
    }

    public function getDbValue($field_name, $value, &$null_allowed = 0)
    {
        if ($this->field_exists($field_name)) {
            $this->checkFieldValueType($field_name, $value);

            $field_type = $this->getConf('fields/' . $field_name . '/type', 'string');

            switch ($field_type) {
                case 'json':
                case 'object_filters':
                    if (!$value) {
                        $value = array();
                    }
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    break;

                case 'items_list':
                    if (isset($value[0]) && $value[0] === '') {
                        unset($value[0]);
                    }

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
                    break;

                case 'id_object':
                    if (!$value && $this->isDolObject()) {
                        // Pour les ID = 0 des objets Dolibarr on enregistre NULL en base pour contourner les contraintes de clés étrangères.  
                        $dol_prop = $this->getConf('fields/' . $field_name . '/dol_prop', $field_name);
                        if (property_exists($this->dol_object, $dol_prop)) {
                            $null_allowed = 1;
                            $value = null;
                        }
                    }
                    break;
            }
        }

        return $value;
    }

    public function getValueFromDb($field_name, $value)
    {
        return $value;
    }

    public function getGlobalConf($name, $default = '', $module = '')
    {
        return BimpCore::getConf($name, $default, $module);
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
            } elseif ($info['type'] == "id_object") {
                continue; //Car on les retrouve enssuite de nouveau dans $this->params['objects']
//                $obj = $this->getChildObject($info['object']);
//                $value = $this->recursiveGetExport($niveau, $pref, $obj);
            } elseif ($info['type'] == "bool")
                $value = ($value ? "OUI" : "NON");

            $tabResult[$nom] = $value;
        }

        foreach ($this->params['objects'] as $nom) {
            $infoObj = BimpComponent::fetchParamsStatic($this->config, 'objects/' . $nom, BimpConfigDefinitions::$object_child);
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
            } elseif (is_a($obj, "CommonObject")) {
                $id = 0;
                if (property_exists($obj, 'id'))
                    $id = $obj->id;
                else
                    $value = "ERR pas de champ ID" . get_class($obj);
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
            } else {
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
                $title = $ref . ' ' . $title;
            } else {
                $title .= ' #' . $this->id;
            }
        }

        return $title;
    }

    public function getRef($withGeneric = true)
    {
        $prop = $this->getRefProperty();

        if (isset($this->data[$prop]) && $this->data[$prop]) {
            return $this->data[$prop];
        }

        if ($withGeneric) {
            return '#' . $this->id;
        }

        return '';
    }

    public function getName($withGeneric = true)
    {
        $name = '';

        if ($this->params['name_syntaxe']) {
            $name = $this->params['name_syntaxe'];

            $n = 0;
            while (preg_match('/<(.+)>/U', $name, $matches)) {
                $field = $matches[1];
                $value = '';

                if (isset($this->data[$field]) && $this->data[$field]) {
                    $value = $this->data[$field];
                }

                $name = str_replace('<' . $field . '>', $value, $name);

                $n++;
                if ($n > 10) {
                    break;
                }
            }
        } else {
            $props = $this->getNameProperties();

            if (count($props)) {
                foreach ($props as $prop) {
                    if ($this->field_exists($prop) && isset($this->data[$prop]) && $this->data[$prop]) {
                        $name .= ($name ? ' ' : '') . $this->data[$prop];
                    }
                }
            }
        }

        if (!$name && $withGeneric) {
            $name = BimpTools::ucfirst($this->getLabel()) . ' #' . $this->id;
        }

        return $name;
    }

    public function getStatus()
    {
        $prop = $this->getStatusProperty();

        if (isset($this->data[$prop])) {
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

            if (!empty($file)) {
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

                    if (BimpCore::isContextPublic()) {
                        $url .= '&hashp=1';
                    }


                    if ($url && $preview) {
                        $url = 'javascript:document_preview(\'' . $url . '\', \'image/' . $ext . '\', \'Aperçu\');';
                    }
                }
            }
        }

        return $url;
    }

    public function getSecteur()
    {
        $prop = $this->getSecteurProperty();

        if (isset($this->data[$prop]) && (string) $this->data[$prop]) {
            return $this->data[$prop];
        }

        return '';
    }

    public function getDolValue($field, $value)
    {
        if ($this->field_exists($field)) {
            $data_type = $this->getConf('fields/' . $field . '/type', 'string');
            switch ($data_type) {
                case 'id_object':
                    if ($value == 0)
                        $value = null;
                    break;
                case 'date':
                case 'time':
                case 'datetime':
                    $value = BimpTools::getDateTms($value);
                    break;

                case 'items_list':
                case 'json':
                case 'object_filters':
                    $value = $this->getDbValue($field, $value);
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

        return (int) BimpCore::getConf('id_default_bank_account');
    }

    public function getInfoGraph($graphName = '')
    {
        return
                array("data1"     => array("title" => "Nom Data1"),
//                    "data2"     => array("title" => "Nom Data2"),
                    "axeX"      => array("title" => "X", "valueFormatString" => 'value type'),
                    "axeY"      => array("title" => "Y"), //Attention potentiellement plusiuers donné sur cette axe
                    'title'     => $this->getLabel(),
                    'params'    => array(), //tous les paramétre qui seront transmis a getGraphDataPoint ou a getGraphDatasPoints
                    'mode_data' => (method_exists($this, 'getGraphDataPoint')) ? 'objects' : 'unique'
        );
    }

    public function getGraphDataPoint_exemple($params, $numero_data = 1)//si c'est fonction est définit, on apelle en priorité elle charge chaque donnée via l'objet
    {
        return array("x" => '2022/01/01', "y" => 40);
    }

    public function getGraphDatasPoints_exemple($params)//si c'est fonction est définit, elle charge toutes les donnée en un seul coup
    {
        $result = array();
        $result[1][] = array("x" => "new Date(4545435)", "y" => (int) 45); //donné 1
        $result[1][] = array("x" => "new Date(454545635)", "y" => (int) 65); //donné 1
        $result[2][] = array("x" => "new Date(435353553)", "y" => (int) 74); // donné 2

        return $result;
    }

    public function getPdfNamePrincipal()
    {
        BimpCore::addlog('"getPdfNamePrincipal" n\'est pas redéfinit dans ' . $this->object_name);
        return 'n_c.pdf';
    }

    public function getObjectLogs()
    {
        if ($this->isLoaded()) {
            return BimpCache::getBimpObjectObjects('bimpcore', 'BimpObjectLog', array(
                        'obj_module' => $this->module,
                        'obj_name'   => $this->object_name,
                        'id_object'  => $this->id
            ));
        }

        return array();
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
            if (self::cacheExists($cache_key) /* && self::getCache($cache_key) == $this todo a voir pourquoi */) {
                self::setCache($cache_key, null);
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
        }

        $this->dol_object = $this->config->getObject('dol_object');
        if (is_object($this->dol_object)) {
            $this->dol_object->db = $this->db->db;
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

    public function setPreloadedData($id, $data)
    {
        // /!\ Cette méthode ne doit être appellée QUE par BimpCollection /!\

        if ((int) $this->id != (int) $id) {
            $this->reset();
            $this->id = $id;
            $this->data = $data;
            $this->initData = $data;
            $this->ref = $this->getRef();

            if ($this->isDolObject()) {
                $this->dol_object->id = $id;
                $bimpObjectFields = array();
                $this->hydrateDolObject($bimpObjectFields, false);
            }
        }
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

    public function setNewStatus($new_status, $extra_data = array(), &$warnings = array(), $force_status = false)
    {
//        BimpLog::actionStart('bimpobject_new_status', 'Nouveau statut', $this);

        $errors = array();
        $new_status = (int) $new_status;

        if (!array_key_exists($new_status, static::$status_list)) {
            $errors[] = 'Erreur: ce statut n\'existe pas';
        } else {
            $status_label = is_array(static::$status_list[$new_status]) ? static::$status_list[$new_status]['label'] : static::$status_list[$new_status];
            $object_label = $this->getLabel('the') . (isset($this->id) && $this->id ? ' ' . $this->id : '');

            if (!$force_status && !$this->canSetStatus($new_status)) {
                $errors[] = 'Vous n\'avez pas la permission de passer ' . $this->getLabel('this') . ' au statut "' . $status_label . '"';
            } elseif ($force_status || $this->isNewStatusAllowed($new_status, $errors)) {
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

//        BimpLog::actionEnd('bimpobject_new_status', $errors);

        return $errors;
    }

    public function setObjectAction($action, $id_object = 0, $extra_data = array(), &$success = '', $force_action = false)
    {
        $result = array(
            'errors'   => array(),
            'warnings' => array()
        );

        if ((int) $id_object && (!BimpObject::objectLoaded($this) || (int) $this->id !== (int) $id_object)) {
            $instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $id_object);
            if (!BimpObject::objectLoaded($instance)) {
                $result['errors'][] = BimpTools::ucfirst($instance->getLabel('the')) . ' d\'ID ' . $id_object . ' n\'existe pas';
                return $result;
            }
        } else {
            $instance = $this;
        }

        $use_db_transactions = (int) BimpCore::getConf('use_db_transactions') && !(int) $this->getConf('no_transaction_db', 0, false, 'bool');

        if ($use_db_transactions) {
            $instance->db->db->begin();
        }

//        BimpLog::actionStart('bimpobject_action', 'Action "' . $action . '"', $instance);

        if (!$instance->isLoaded()) {
            $parent_id_prop = $instance->getParentIdProperty();
            if ($parent_id_prop) {
                if (!BimpObject::objectLoaded($instance->parent)) {
                    $id_parent = (int) BimpTools::getPostFieldValue($parent_id_prop);
                    if ($id_parent) {
                        $instance->setIdParent($id_parent);
                    }
                }
            }
        }

        if (!count($result['errors'])) {
            if (!$force_action && !$instance->canSetAction($action)) {
                $result['errors'][] = 'Vous n\'avez pas la permission d\'effectuer cette action (' . $action . ')';
            } elseif (!$instance->isActionAllowed($action, $result['errors'])) {
                $result['errors'][] = 'Action impossible';
            }

            if (!count($result['errors'])) {
                $method = 'action' . ucfirst($action);
                if (method_exists($instance, $method)) {
                    $result = $instance->{$method}($extra_data, $success);

                    if (!isset($result['errors'])) {
                        BimpCore::addlog('Retour d\'action invalide', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', $instance, array(
                            'Action' => $action,
                            'Note'   => 'Toutes les actions BimpObject doivent retourner un résultat sous la forme array(\'errors\' => $errors, \'warnings\' => $warnings, ... autre valeurs facultatives ...). Retournée : ' . print_r($result, 1)
                        ));
                    }
                } else {
                    $result['errors'][] = 'Action invalide: "' . $action . '"';
                }
            }
        }

        $result['errors'] = BimpTools::merge_array($result['errors'], BimpTools::getDolEventsMsgs(array('errors')));

//        BimpLog::actionEnd('bimpobject_action', (isset($errors['errors']) ? $errors['errors'] : $errors), (isset($errors['warnings']) ? $errors['warnings'] : array()));
        global $dont_rollback;
        if ($use_db_transactions) {
            if (isset($result['errors']) && count($result['errors']) and!isset($dont_rollback)) {
                $instance->db->db->rollback();

                if ((int) BimpCore::getConf('log_actions_rollbacks')) {
                    BimpCore::addlog('Rollback suite à action', Bimp_Log::BIMP_LOG_ALERTE, 'bimpcore', $instance, array(
                        'Action'  => $action,
                        'Erreurs' => $result['errors']
                            ), true);
                }
            } else {
                if (!$instance->db->db->commit()) {
                    $result['errors'][] = 'Une erreur inconnue est survenue - opération annulée';
                    BimpCore::addlog('Commit echec - erreur inconnue', Bimp_Log::BIMP_LOG_ALERTE, 'bimpcore', $instance, array(
                        'Action' => $action,
                        'Result' => $result
                            ), true);
                }
            }
        }

        return $result;
    }

    public function initBdsAction($process, $action, &$action_data = array(), &$errors = array(), $extra_data = array(), $force_action = false)
    {
        $method = 'initBdsAction' . ucfirst($action);

        if (!method_exists($this, $method)) {
            $errors[] = 'Méthode inexistante: ' . $method;
        } else {
            if (!$force_action && !$this->canSetAction($action)) {
                $errors[] = 'Vous n\'avez pas la permission d\'effectuer cette action (' . $action . ')';
            } elseif (!$this->isActionAllowed($action, $errors)) {
                $errors[] = 'Action impossible';
            }

            if (!count($errors)) {
                $this->{$method}($process, $action_data, $errors, $extra_data);
            }
        }
    }

    public function executeBdsAction($process, $action, $step_name, $elements = array(), &$errors = array(), $operation_extra_data = array(), $action_extra_data = array(), $force_action = false)
    {
        $method = 'executeBdsAction' . ucfirst($action);

        if (!method_exists($this, $method)) {
            $errors[] = 'Méthode inexistante: ' . $method;
        } else {
            if (!$force_action && !$this->canSetAction($action)) {
                $errors[] = 'Vous n\'avez pas la permission d\'effectuer cette action (' . $action . ')';
            } elseif (!$this->isActionAllowed($action, $errors)) {
                $errors[] = 'Action impossible';
            }

            if (!count($errors)) {
                $this->{$method}($process, $step_name, $elements, $errors, $operation_extra_data, $action_extra_data);
            }
        }
    }

    public function finalizeBdsAction($process, $action, &$errors = array(), $operation_extra_data = array(), $action_extra_data = array(), $force_action = false)
    {
        $method = 'finalizeBdsAction' . ucfirst($action);

        if (method_exists($this, $method)) {
            if (!$force_action && !$this->canSetAction($action)) {
                $errors[] = 'Vous n\'avez pas la permission d\'effectuer cette action (' . $action . ')';
            } elseif (!$this->isActionAllowed($action, $errors)) {
                $errors[] = 'Action impossible';
            }

            if (!count($errors)) {
                return $this->{$method}($process, $errors, $operation_extra_data, $action_extra_data);
            }
        }

        return array();
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
                                if ($bc_field->getParam('type', 'string') === 'items_list') {
                                    if ((int) $bc_field->getParam('items_braces', 0)) {
                                        $filters[$filter_key] = array(
                                            'part_type' => 'middle',
                                            'part'      => '[' . $value . ']'
                                        );
                                    } else {
                                        $filters['custom_' . $filter_key] = array(
                                            'custom' => '0' // On plante la recherche pour éviter des résultats incohérents
                                        );

                                        BimpCore::addlog('Tentative de recherche sur une champ de type items_list sans crochets', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', $this, array(
                                            'Champ' => $bc_field->name,
                                            'Note'  => 'Mettre "searchable: 0" dans les params du champ'
                                                ), true);
                                    }
                                } else {
                                    $filters[$filter_key] = $value;
                                }
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
                // On vérifie que la valeur courante est bien enregistrée: 
                $where = 'module = \'' . $this->module . '\' AND object = \'' . $this->object_name . '\'';
                $where .= ' AND id_object = ' . (int) $this->id . ' AND field = \'' . $field . '\'';
                if ($this->id > 0 && !is_null($current_value) && !(int) $this->db->getValue('bimpcore_history', 'id', $where, 'date', 'DESC')) {
                    $this->db->insert('bimpcore_history', array(
                        'module'    => $this->module,
                        'object'    => $this->object_name,
                        'id_object' => $this->id,
                        'field'     => $field,
                        'value'     => $this->getDbValue($field, $current_value)
                    ));
                }
                $this->history[$field] = $value;
            }
        }
    }

    protected function checkDolFieldsHistory()
    {
        if (!$this->isDolObject()) {
            return;
        }

        foreach ($this->data as $field_name => $value) {
            if ($this->isDolExtraField($field_name) || $this->isDolField($field_name)) {
                $this->checkFieldHistory($field_name, $value);
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

    public function checkFieldValueType($field, &$value, &$errors = array())
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
            if ($this->isDolField($field)) {
                if (in_array($type, array('datetime', 'date'/* , 'time' */))) {
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

//                                case 'time':
//                                    $value = $matches[4] . ':' . $matches[5] . ':' . $matches[6];
//                                    break;
                            }
                        }
                    }
                }
            }

            // Traitement des cas particuliers des listes de valeurs: 
            if ($type === 'items_list') {
                if (is_string($value)) {
                    if ($value === '') {
                        $value = array();
                    } elseif ($this->getConf('fields/' . $field . '/items_braces', 0)) {
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
                    if (!BimpTools::checkValueByType($item_type, $item_value, $errors)) {
                        $check = false;
                    }
                }
                return $check;
            }

            // Vérification et ajustement de la valeur selon son type: 
            return BimpTools::checkValueByType($type, $value, $errors);
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

    public function checkFieldsHashtags($fields = null)
    {
        $errors = array();

        if ($this->isLoaded()) {
            if (is_null($fields) || !is_array($fields)) {
                $fields = $this->params['fields'];
            }

            foreach ($fields as $field_name) {
                if ($this->field_exists($field_name)) {
                    $type = $this->getConf('fields/' . $field_name . '/type', 'string');

                    if (in_array($type, array('string', 'text', 'html'))) {
                        if ($this->getConf('fields/' . $field_name . '/hashtags', 0, false, 'bool')) {
                            $hashtags = array();

                            $value = $this->getData($field_name);

                            if ($value && preg_match_all('/\{\{(.+):([0-9]+)\}\}/U', $value, $matches, PREG_SET_ORDER)) {
                                foreach ($matches as $match) {
                                    $hashtags[] = array(
                                        'obj_kw' => $match[1],
                                        'id'     => $match[2]
                                    );
                                }
                            }

                            BimpObject::loadClass('bimpcore', 'BimpLink');
                            $errors = BimpLink::setLinksForSource($this, $field_name, $hashtags);
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function addObjectLog($msg, $code = '', $no_transactions_db = false)
    {
        $errors = array();

        if (!$no_transactions_db && $this->db->db->noTransaction) {
            $no_transactions_db = true;
        }
        
        if ($this->isLoaded($errors)) {
            global $user;
            $w = array();
            
            BimpObject::createBimpObject('bimpcore', 'BimpObjectLog', array(
                'obj_module' => $this->module,
                'obj_name'   => $this->object_name,
                'id_object'  => $this->id,
                'msg'        => $msg,
                'code'       => $code,
                'date'       => date('Y-m-d H:i:s'),
                'id_user'    => (BimpObject::objectLoaded($user) ? $user->id : 0)
                    ), true, $errors, $w, $no_transactions_db);
        } else
            BimpCore::addlog('Ajout historique objet non loadé');

        return $errors;
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
            $errors[] = 'Objet enfant "' . $child_name . '" invalide';
        } else {
            switch ($relation) {
                case 'hasOne':
                    if (!$child_id_prop) {
                        $errors[] = 'Champ contenant l\'ID absent class ' . get_class($this) . ' enfant ' . $child_name;
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
                    $ok = false;
                    if (!$this->isChild($child)) {
                        $path = 'objects/' . $child_name . '/list/filters';
                        $this->config->isDefined($path);
                        $data = $this->config->getParams($path);

                        foreach ($data as $joinInChild => $data2) {
                            if (isset($data2['field_value'])) {
                                $alias = ($main_alias ? $main_alias . '___' : '') . $child_name;
                                $ok = true;
                                $joins[$alias] = array(
                                    'table' => $child->getTable(),
                                    'alias' => $alias,
                                    'on'    => $alias . '.' . $joinInChild . ' = ' . $main_alias . '.' . $data2['field_value']
                                );
                            }
                        }
                        if (!$ok)
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

        if (count($errors)) {
            BimpCore::addlog('Erreur getChildJoin()', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', $this, array(
                'Child name' => $child_name,
                'Erreurs'    => $errors
            ));
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
        // Intègre les jointures d'une succession d'objets, chaque objet étant l'enfant du précédant. 

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
        $error = '';
        $sqlKey = '';

        if (!is_null($child_name) && $child_name) {
            // Champ d'un objet enfant: 

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
                $error = 'Instance enfant invalide';
            } elseif (is_a($child_object, 'BimpObject')) {
                // Objet enfant de type bimp_object: 
                if ($relation === 'hasOne' || $id_prop) {
                    if (!is_string($id_prop) || !$id_prop) {
                        $error = 'Propriété contenant l\'ID de l\'objet "' . $child_object->getLabel() . '" absente ou invalide';
                    } else {
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
                            $error = 'Propriété de l\'ID parent absente pour l\'objet "' . $child_object->getLabel() . '"';
                        } else {
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
                        }
                    } else {
                        $error = 'L\'objet "' . $child_object->getLabel() . '" doit être enfant de "' . $this->getLabel() . '"';
                    }
                } else {
                    $error = 'Type de relation invalide pour l\'objet "' . $child_object->getLabel() . '"';
                }
            } else {
                // Objet enfant de type dol_object: 
                $child_table = BimpTools::getObjectTable($this, $id_prop, $child_object);
                if (is_null($child_table) || !(string) $child_table) {
                    $error = 'Nom de la table de l\'objet enfant "' . $child_name . '" non obtenue';
                } else {
                    $child_primary = BimpTools::getObjectPrimary($this, $id_prop, $child_object);
                    if (is_null($child_primary) || !(string) $child_primary) {
                        $error = 'Champ primaire de l\'objet enfant "' . $child_name . '" non obtenu';
                    } else {
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
                }
            }
        } elseif ($this->field_exists($field)) {
            // Champ de l'objet instancié: 
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
            $error = 'Le champ "' . $field . '" n\'existe pas pour les ' . $this->getLabel('name_plur');
        }

        if ($error) {
            $error_title = 'Echec de l\'obtention de la clé SQL pour le champ "' . $field . '" - Objet "' . $this->getLabel() . '"' . ($child_name ? ' - Enfant "' . $child_name . '"' : '');
            $errors[] = BimpTools::getMsgFromArray($error, $error_title);
            BimpCore::addlog('Echec obtention clé SQL pour le champ "' . $field . '"', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', $this, array(
                'Champ'        => $field,
                'Objet enfant' => (!is_null($child_name) && (string) $child_name ? $child_name : 'aucun'),
                'Erreur'       => $error
            ));
        }

        return $sqlKey;
    }

    protected function checkSqlFilters($filters, &$joins = array(), $main_alias = '', &$extra_filters = null)
    {
        // Vérifie les clés sql pour les champs filtrés
        $return = array();
        $add_extra_filters = false;

        if (is_null($extra_filters)) {
            $extra_filters = array();
            $add_extra_filters = true;
        }

        foreach ($filters as $field => $filter) {
            if (is_array($filter) && isset($filter['custom'])) {
                $return[$field] = $filter;
            } elseif (is_array($filter) && isset($filter['or'])) {
                $return[$field] = array('or' => $this->checkSqlFilters($filter['or'], $joins, $main_alias, $extra_filters));
            } elseif (is_array($filter) && isset($filter['and_fields'])) {
                $return[$field] = array('and_fields' => $this->checkSqlFilters($filter['and_fields'], $joins, $main_alias, $extra_filters));
            } else {
                $sqlKey = '';

                $field_name = '';
                if (strpos($field, ':') !== false) {
                    $child = null;
                    $field_alias = '';
                    $children = explode(':', $field);
                    $child_field_name = array_pop($children);
                    $col_errors = $this->getRecursiveChildrenJoins($children, $filters, $joins, $main_alias, $field_alias, $child);

                    if (empty($col_errors) && is_a($child, 'bimpObject')) {
                        $sqlKey = $child->getFieldSqlKey($child_field_name, $field_alias, null, $filters, $joins);
                    }
                } elseif ($main_alias && preg_match('/^(' . preg_quote($main_alias, '/') . '\.)(.+)$/', $field, $matches)) {
                    $field_name = $matches[2];
                } elseif (!preg_match('/\./', $field)) {
                    $field_name = $field;
                } else {
                    $sqlKey = $field;
                }

                if ($field_name) {
                    if ($this->field_exists($field_name)) {
                        $sqlKey = $this->getFieldSqlKey($field_name, $main_alias, null, $extra_filters, $joins);
                    } else {
                        $sqlKey = $field_name;
                    }
                }

                if ($sqlKey) {
                    $return[$sqlKey] = $filter;
                }
            }
        }

        if ($add_extra_filters && !empty($extra_filters)) {
            foreach ($extra_filters as $extra_filter_key => $extra_filter) {
                $return = BimpTools::mergeSqlFilter($return, $extra_filter_key, $extra_filter);
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

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        
    }

    // Gestion des objets enfants:

    public function hasChildren($object_name)
    {
        if (!in_array($object_name, $this->params['objects'])) {
            return false;
        }

        $relation = $this->getConf('objects/' . $object_name . '/relation', '', true);
        if (!$relation || $relation == 'none') {
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
//                    $cache_key = '';
                    if ($this->isChild($instance)) {
//                        if (empty($filters)) {
//                            $cache_key = $this->module . '_' . $this->object_name . '_' . $this->id . '_children_list_' . $object_name . '_by_' . $order_by . '_' . $order_way;
//
//                            if (isset(self::$cache[$cache_key])) {
//                                return self::$cache[$cache_key];
//                            }
//                        }

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

//                    if ($cache_key) {
//                        self::$cache[$cache_key] = $children;
//                    }
                }
            }
        }
        return $children;
    }

    public function getAllFiles($withLink = true)
    {
        $list = $this->getFilesArray(0);
        if ($withLink) {
            $objects = $this->getBimpObjectsLinked();
//           echo '<pre>'; print_r($objects);
            foreach ($objects as $object) {
                $list = $list + $object->getFilesArray(0);
            }
        }
        return $list;
    }

    public function getBimpObjectsLinked($not_for = '')
    {
        $objects = array();
        if ($this->isLoaded()) {
            if ($this->isDolObject()) {
                foreach (BimpTools::getDolObjectLinkedObjectsList($this->dol_object, $this->db) as $item) {
                    $id = $item['id_object'];
                    $class = "";
                    $label = "";
                    $module = "bimpcommercial";
                    switch ($item['type']) {
                        case 'propal':
                            $class = "Bimp_Propal";
                            break;
                        case 'facture':
                            $class = "Bimp_Facture";
                            break;
                        case 'commande':
                            $class = "Bimp_Commande";
                            break;
                        case 'order_supplier':
                            $class = "Bimp_CommandeFourn";
                            break;
                        case 'invoice_supplier':
                            $class = "Bimp_FactureFourn";
                            break;
                        case 'contrat':
                            $module = 'bimpcontract';
                            $class = 'BContract_contrat';
                            break;
//                        case 'fichinter':
//                            $class = 'BimpFi_fiche';
//                            $module = "bimpfi";
//                            break;
                        case 'synopsisdemandeinterv':
                            $class = "BT_demandeInter";
                            $module = "bimptechnique";
                            break;
                        default:
                            break;
                    }
                    if ($class != "") {
                        $objT = BimpCache::getBimpObjectInstance($module, $class, $id);
                        if (BimpObject::objectLoaded($objT)) {
                            $clef = $item['type'] . $objT->id;
                            if ($not_for != $clef) {
                                $objects[$clef] = $objT;
                                if ($item['type'] == 'commande') {
                                    $objects = BimpTools::merge_array($objects, $objT->getBimpObjectsLinked((isset($this->dol_object->element) ? $this->dol_object->element . $this->id : '')));
                                }
                            }
                        }
                    }
                }
            }

            $client = $this->getChildObject('client');

            if ($client->isLoaded()) {
                $objects['client' . $client->id] = $client;
            }
        }


        return $objects;
    }

    public function getDocumentFileId()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $ref = dol_sanitizeFileName($this->getRef());
        $ref = BimpTools::cleanStringForUrl($ref);

        $where = '`parent_module` = \'' . $this->module . '\' AND `parent_object_name` = \'' . $this->object_name . '\' AND `id_parent` = ' . (int) $this->id;
        $where .= ' AND `file_name` = \'' . $ref . '\' AND `file_ext` = \'pdf\'';

        return (int) $this->db->getValue('bimpcore_file', 'id', $where);
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

    public function unsetChildrenListCache($child_name)
    {
        if ($this->isLoaded()) {
            $str = $this->module . '_' . $this->object_name . '_' . $this->id . '_children_list_' . $child_name . '_by_';
            foreach (self::$cache as $cache_key => $cache_value) {
                if (strpos($cache_key, $str) === 0) {
                    unset(self::$cache[$cache_key]);
                }
            }
        }
    }

//    public function onChildCreate($child_name)
//    {
//        $this->unsetChildrenListCache($child_name);
//    }
//    
    // Getters Listes

    public function getList($filters = array(), $n = null, $p = null, $order_by = 'id', $order_way = 'DESC', $return = 'array', $return_fields = null, $joins = array(), $extra_order_by = null, $extra_order_way = 'ASC')
    {
        $table = $this->getTable();
        $primary = $this->getPrimary();

        if (is_null($table)) {
            return array();
        }

        $fields = array();

        // Vérification des champs à retourner: 
        if (is_array($return_fields)) {
            foreach ($return_fields as $field) {
                if (!preg_match('/\./', $field)) {
                    $field_alias = '';

                    if (preg_match('/^(.+) as (.+)$/', $field, $matches)) {
                        $field = $matches[1];
                        $field_alias = $matches[2];
                    }

                    if ($this->field_exists($field)) {
                        $sqlKey = $this->getFieldSqlKey($field, 'a', null, $filters, $joins);
                        if ($sqlKey) {
                            $fields[] = $sqlKey . ($field_alias ? ' as ' . $field_alias : '');
                        }
                    } else {
                        BimpCore::addlog('getList() : Champ à retourner invalide', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', $this, array(
                            'Champ' => $field
                        ));
                    }
                } else {
                    $fields[] = $field;
                }
            }
        }

        // Vérification des filtres: 
        $filters = $this->checkSqlFilters($filters, $joins, 'a');

        // Vérification du champ "order_by": 
        if ($order_by === 'id') {
            $order_by = 'a.' . $primary;
        } elseif ($order_by == 'rand') {
            $order_by = 'rand';
        } elseif (preg_match('/^a\.(.+)$/', $order_by, $matches)) {
            $order_by = '';
            if ($this->field_exists($matches[1])) {
                $order_by = $this->getFieldSqlKey($matches[1], 'a', null, $filters, $joins);
            }
        } elseif (!preg_match('/\./', $order_by)) {
            if ($this->field_exists($order_by)) {
                $order_by = $this->getFieldSqlKey($order_by, 'a', null, $filters, $joins);
            } else {
                $order_by = '';
            }
        }

        if (!$order_by) {
            $order_by = 'a.' . $primary;
        }

        if ($extra_order_by === 'id') {
            $extra_order_by = 'a.' . $primary;
        } elseif (preg_match('/^a\.(.+)$/', $extra_order_by, $matches)) {
            $extra_order_by = '';
            if ($this->field_exists($matches[1])) {
                $extra_order_by = $this->getFieldSqlKey($matches[1], 'a', null, $filters, $joins);
            }
        } elseif (!preg_match('/\./', $extra_order_by)) {
            $extra_order_by = '';
            if ($this->field_exists($extra_order_by)) {
                $extra_order_by = $this->getFieldSqlKey($extra_order_by, 'a', null, $filters, $joins);
            }
        }

        if (!$extra_order_by && $order_by != 'a.' . $primary) {
            $extra_order_by = 'a.' . $primary;
        }

        if (!count($fields))
            $fields[] = 'a.*';

        $sql = '';
        $sql .= BimpTools::getSqlSelect($fields);

        if (BimpTools::isModuleDoliActif('MULTICOMPANY')) {
            $newJoins = array();
            if ($this->getEntityFilter($newJoins, $filters)) {
                $joins = BimpTools::merge_array($joins, $newJoins);
            }
        }


        $sql .= BimpTools::getSqlFrom($table, $joins);
        $sql .= BimpTools::getSqlWhere($filters);

        if ($order_by == 'rand') {
            $sql .= ' ORDER BY rand() ';
        } else {
            $sql .= BimpTools::getSqlOrderBy($order_by, $order_way, 'a', $extra_order_by, $extra_order_way);
        }

        $sql .= BimpTools::getSqlLimit($n, $p);

//        $cache_key = 'sql_'.$sql;
//        if($this->cacheExists($cache_key))
//            $rows = $this->getCache($cache_key);
//        else{

        $rows = $this->db->executeS($sql, $return);

        if (is_null($rows)) {
            if (BimpDebug::isActive()) {
                $content = BimpRender::renderSql($sql);
                $content .= BimpRender::renderDebugInfo($this->db->err(), 'ERREUR SQL', 'fas_exclamation-circle');
                $content .= BimpRender::renderFoldableContainer('Filters', '<pre>' . print_r($filters, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                $content .= BimpRender::renderFoldableContainer('Joins', '<pre>' . print_r($joins, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                $title = 'SQL Liste - Module: "' . $this->module . '" Objet: "' . $this->object_name . '"';
                BimpDebug::addDebug('list_sql', $title, $content);
            }

            $rows = array();
        } else {
            if (BimpDebug::isActive()) {
                $nRows = count($rows);
                $content = BimpRender::renderSql($sql);
                $content .= '<br/><span class="badge badge-' . ($nRows > 0 ? 'success' : 'danger') . '">' . $nRows . ' résultat' . ($nRows > 1 ? 's' : '') . '</span>';
                $title = 'SQL Liste - Module: "' . $this->module . '" Objet: "' . $this->object_name . '"';
                BimpDebug::addDebug('list_sql', $title, $content);
            }
        }
//            $this->setCache($cache_key, $rows);
//        }

        return $rows;
    }

    public function getEntityFilter(&$joins, &$filters, $alias = 'a', $aliasParent = 'parent')
    {
        if (strlen($aliasParent) > 100)
            die($aliasParent);
        if ($this->getEntity_name()) {
            $filters['entity'] = $this->getEntitysArray();
            return 1;
        } else {
            $parent = $this->getNonFetchParent();
            if (get_class($parent) != 'BimpObject') {
                $joins[$aliasParent] = array("alias" => $aliasParent, 'table' => $parent->getTable(), 'on' => $alias . '.' . $this->getParentIdProperty() . ' = ' . $aliasParent . '.' . $parent->getPrimary());
                if ($parent->getEntity_name()) {
                    $filters[$aliasParent . '.entity'] = $parent->getEntitysArray();
                    return 1;
                } else {
                    return $parent->getEntityFilter($joins, $filters, $aliasParent, $aliasParent .= '_parent');
                }
            }
        }
        return 0;
    }

    public function getListByParent($id_parent, $n = null, $p = null, $order_by = 'id', $order_way = 'DESC', $return = 'array', $return_fields = null, $joins = null)
    {
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

        $filters = $this->checkSqlFilters($filters, $joins, 'a');

        $sql = 'SELECT COUNT(DISTINCT a.' . $primary . ') as nb_rows';

        if (BimpTools::isModuleDoliActif('MULTICOMPANY')) {
            $newJoins = array();
            if ($this->getEntityFilter($newJoins, $filters)) {
                $joins = BimpTools::merge_array($joins, $newJoins);
            }
        }

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

    public function getListTotals($return_fields = array(), $filters = array(), $joins = array())
    {
        $table = $this->getTable();

        if (is_null($table) || !$table) {
            return array();
        }

        // Vérification des champs à retourner: 
        $fields = array();
        if (is_array($return_fields)) {
            foreach ($return_fields as $sql_key => $field_alias) {
                if (preg_match('/^a\.(.+)$/', $sql_key, $matches)) {
                    if ($this->field_exists($matches[1])) {
                        $newSqlKey = $this->getFieldSqlKey($matches[1], 'a', null, $filters, $joins);
                        if ($newSqlKey) {
                            $fields[$newSqlKey] = $field_alias;
                        }
                    }
                } elseif (!preg_match('/\./', $sql_key)) {
                    if ($this->field_exists($sql_key)) {
                        $newSqlKey = $this->getFieldSqlKey($sql_key, 'a', null, $filters, $joins);
                        if ($newSqlKey) {
                            $fields[$newSqlKey] = $field_alias;
                        }
                    }
                } else {
                    $fields[$sql_key] = $field_alias;
                }
            }
        }

        if (empty($fields)) {
            return array();
        }

        // Vérification des filtres:
        $filters = $this->checkSqlFilters($filters, $joins, 'a');

        $primary = $this->getPrimary();

        $sql = 'SELECT COUNT(DISTINCT a.' . $primary . ') as __nb_rows_expected__, COUNT(a.' . $primary . ') as __nb_rows_real__';

        foreach ($fields as $sqlKey => $field_alias) {
            $sql .= ', SUM(' . $sqlKey . ') as ' . $field_alias;
        }

        $sql .= BimpTools::getSqlFrom($table, $joins);
        $sql .= BimpTools::getSqlWhere($filters);

        $rows = $this->db->executeS($sql, 'array');

        if (is_null($rows)) {
            $rows = array();

            if (BimpDebug::isActive()) {
                $content = BimpRender::renderSql($sql);
                $content .= BimpRender::renderDebugInfo($this->db->err(), 'ERREUR SQL', 'fas_exclamation-circle');
                $content .= BimpRender::renderFoldableContainer('Filters', '<pre>' . print_r($filters, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                $content .= BimpRender::renderFoldableContainer('Joins', '<pre>' . print_r($joins, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                $title = 'SQL Liste Total - Module: "' . $this->module . '" Objet: "' . $this->object_name . '"';
                BimpDebug::addDebug('list_sql', $title, $content);
            }
        } else {
            if (BimpDebug::isActive()) {
                $content = BimpRender::renderSql($sql);
                $content .= '<br/><br/>';
                $content .= 'Nb lignes attendu: ' . $rows[0]['__nb_rows_expected__'];
                $content .= '<br/>Nb lignes réel: ' . $rows[0]['__nb_rows_real__'];
                $title = 'SQL Liste Total - Module: "' . $this->module . '" Objet: "' . $this->object_name . '"';
                BimpDebug::addDebug('list_sql', $title, $content);
            }

            if ((int) $rows[0]['__nb_rows_expected__'] != (int) $rows[0]['__nb_rows_real__']) {
                // Nouvelle requête pour obtenir les bons totaux:
                $new_joins = array();
                $sql = 'SELECT COUNT(DISTINCT a.' . $primary . ') as __nb_rows_expected__, COUNT(a.' . $primary . ') as __nb_rows_real__';
                foreach ($fields as $sqlKey => $field_alias) {
                    if (preg_match('/^(.+)\.(.+)$/', $sqlKey, $matches)) {
                        $table_alias = $matches[1];
                        if ($table_alias != 'a' && !isset($new_joins[$table_alias])) {
                            if (isset($joins[$table_alias])) {
                                $new_joins[$table_alias] = $joins[$table_alias];
                            } else {
                                $check = false;
                                foreach ($joins as $join) {
                                    if (isset($join['alias']) && $join['alias'] == $table_alias) {
                                        $new_joins[$table_alias] = $join;
                                        $check = true;
                                        break;
                                    }
                                }

                                if (!$check) {
                                    continue;
                                }
                            }
                        }
                    }
                    $sql .= ', SUM(' . $sqlKey . ') as ' . $field_alias;
                }

                $sql .= BimpTools::getSqlFrom($this->getTable(), $new_joins);
                $sql .= ' WHERE a.' . $primary . ' IN (';
                $sql .= 'SELECT DISTINCT a.' . $primary;
                $sql .= BimpTools::getSqlFrom($this->getTable(), $joins);
                $sql .= BimpTools::getSqlWhere($filters);
                $sql .= ')';

                $rows = $this->db->executeS($sql, 'array');
                if (is_null($rows)) {
                    $rows = array();

                    if (BimpDebug::isActive()) {
                        $content = BimpRender::renderSql($sql);
                        $content .= BimpRender::renderDebugInfo($this->db->err(), 'ERREUR SQL', 'fas_exclamation-circle');
                        $title = 'SQL Liste Total [REQUETE 2] - Module: "' . $this->module . '" Objet: "' . $this->object_name . '"';
                        BimpDebug::addDebug('list_sql', $title, $content);
                    }
                } else {
                    if (BimpDebug::isActive()) {
                        $content = BimpRender::renderSql($sql);
                        $content .= '<br/><br/>';
                        $content .= 'Nb lignes attendu: ' . $rows[0]['__nb_rows_expected__'];
                        $content .= '<br/>Nb lignes réel: ' . $rows[0]['__nb_rows_real__'];
                        $title = 'SQL Liste Total [REQUETE 2] - Module: "' . $this->module . '" Objet: "' . $this->object_name . '"';
                        BimpDebug::addDebug('list_sql', $title, $content);
                    }

                    if ((int) $rows[0]['__nb_rows_expected__'] != (int) $rows[0]['__nb_rows_real__']) {
                        BimpCore::addlog('Total liste - écart entre le nombre de ligne attendu et réel [REQUETE 2]- A corriger', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', $this, array(
                            'Nb lignes attendu' => $rows[0]['__nb_rows_expected__'],
                            'Nb Lignes réelles' => $rows[0]['__nb_rows_real__'],
                            'Requête'           => BimpRender::renderSql($sql),
                        ));
                        $rows = array(); // Pour ne pas afficher des totaux faux   
                    }
                }
            }
        }

        return $rows;
    }

    // Gestion des signatures: 

    public function getSignatureInstance($doc_type)
    {
        if ($this->isLoaded()) {
            return BimpCache::findBimpObjectInstance('bimpcore', 'BimpSignature', array(
                        'obj_module' => $this->module,
                        'obj_name'   => $this->object_name,
                        'id_obj'     => $this->id,
                        'doc_type'   => $doc_type
                            ), true);
        }

        return null;
    }

    public function getSignatureDocFileExt($doc_type, $signed = false)
    {
        if ($signed) {
            $signature = $this->getSignatureInstance($doc_type);

            if (BimpObject::objectLoaded($signature) && $signature->getData('signed_doc_ext')) {
                return strtolower($signature->getData('signed_doc_ext'));
            }
        }

        return 'pdf';
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

    public function displayData($field, $display_name = 'default', $display_input_value = true, $no_html = false, $no_history = false)
    {
        $bc_field = new BC_Field($this, $field);
        $bc_field->display_name = $display_name;
        $bc_field->display_input_value = $display_input_value;
        $bc_field->no_html = $no_html;
        $bc_field->no_history = $no_history;

        $display = $bc_field->renderHtml();
        unset($bc_field);

        return $display;
    }

    public function displayDataDefault($field, $no_html = false, $no_history = false)
    {
        return $this->displayData($field, 'default', false, $no_html, $no_history);
    }

    public function displayFieldName($field)
    {
        return $this->getConf('fields/' . $field . '/label', $field);
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

    public function displayBool($method)
    {
        if (method_exists($this, $method))
            return ($this->$method() ? '<span class="success">OUI</span>' : '<span class="error">NON</span>');
        else
            return $method . ' n\'existe pas';
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

        if (in_array($type, array('json', 'object_filters'))) {
            $value = BimpTools::json_decode_array($value, $errors);

            if (empty($value)) {
                $missing = true;
            }
        } elseif ($type === 'items_list') {
            if ($value === '') {
                $value = array();
            }
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

        if ($missing && $required && !$this->force_update && $this->field_exists($field)) {
            $errors[] = 'Valeur obligatoire manquante : "' . BimpTools::ucfirst($this->getLabel()) . ': ' . $label . ' (' . $field . ')"';
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
            $use_db_transactions = (int) BimpCore::getConf('use_db_transactions');

            if ($use_db_transactions) {
                $this->db->db->begin();
            }

            if ($this->isLoaded()) {
                $errors = $this->update($warnings, $force_edit);

                if (!is_array($errors)) {
                    BimpCore::addlog('Retour d\'erreurs absent', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', null, array(
                        'méthode'    => 'update()',
                        'Module'     => $this->module,
                        'Object'     => $this->object_name,
                        'Class_name' => get_class($this)
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
                        'méthode'    => 'create()',
                        'Module'     => $this->module,
                        'Object'     => $this->object_name,
                        'Class_name' => get_class($this)
                    ));

                    $errors = array();
                }

                if (!count($errors)) {
                    $success = 'Création ' . $this->getLabel('of_the') . ' effectuée avec succès : ' . $this->getLink();
                    if (method_exists($this, 'getCreateJsCallback')) {
                        $success_callback = $this->getCreateJsCallback();
                    }
                }
            }

            if (!count($errors)) {
                // Associations: 
                if ((int) BimpCore::getConf('use_db_transactions'))
                    $errors = BimpTools::merge_array($errors, $this->saveAssociationsFromPost());
                else
                    $warnings = BimpTools::merge_array($warnings, $this->saveAssociationsFromPost());

                // Sous-objets ajoutés: 
                $sub_result = $this->checkSubObjectsPost($force_edit);
                if (count($sub_result['errors'])) {
                    if ((int) BimpCore::getConf('use_db_transactions'))
                        $errors = BimpTools::merge_array($errors, $sub_result['errors']);
                    else
                        $warnings = BimpTools::merge_array($warnings, $sub_result['errors']);
                }
                if ($sub_result['success_callback']) {
                    $success_callback .= $sub_result['success_callback'];
                }

                if ($this->isLoaded()) {
                    // Champs des sous-objets mis à jour: 
                    $sub_result = $this->checkChildrenUpdatesFromPost();
                    if (count($sub_result['errors'])) {
                        if ((int) BimpCore::getConf('use_db_transactions'))
                            $errors = BimpTools::merge_array($errors, $sub_result['errors']);
                        else
                            $warnings = BimpTools::merge_array($warnings, $sub_result['errors']);
                    }
                    if ($sub_result['success_callback']) {
                        $success_callback .= $sub_result['success_callback'];
                    }
                }
            }

            if ($use_db_transactions) {
                if (count($errors)) {
                    $this->db->db->rollback();

                    if ((int) BimpCore::getConf('log_actions_rollbacks')) {
                        BimpCore::addlog('Rollback Save from post', Bimp_Log::BIMP_LOG_ALERTE, 'bimpcore', $this, array(
                            'Erreurs' => $errors
                                ), true);
                    }
                } else {
                    if (!$this->db->db->commit()) {
                        $errors[] = 'Echec de l\'enregistrement des données - opération annulée';

                        BimpCore::addlog('Commit echec - erreur inconnue', Bimp_Log::BIMP_LOG_ALERTE, 'bimpcore', $this, array(
                            'Action' => 'Save From Post',
                            'Warnings' > $warnings
                                ), true);
                    }
                }
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

                if ($this->params['positions'] && $this->params['position_insert'] === 'after') {
                    $this->set('position', $this->getNextPosition());
                }

                if (!is_null($this->dol_object)) {
                    $this->checkDolFieldsHistory();
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

                    if ($this->params['positions'] && $this->params['position_insert'] === 'before') {
                        $this->setPosition(1);
                    }

                    $this->initData = $this->data;

                    $warnings = BimpTools::merge_array($warnings, $this->updateAssociations());
                    $warnings = BimpTools::merge_array($warnings, $this->saveHistory());
                    $warnings = BimpTools::merge_array($warnings, $this->checkFieldsHashtags());

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

                    if ($this->isDolObject()) {
                        if ((string) $result) {
                            $errors[] = 'Code résultat: ' . $result;
                        }
                        $errors = BimpTools::merge_array($errors, BimpTools::getErrorsFromDolObject($this->dol_object));
                    }
                }
            }
        }

        BimpLog::actionData($this->getDataArray());
        BimpLog::actionEnd('bimpobject_create', $errors, $warnings);

        $this->noFetchOnTrigger = false;
        return $errors;
    }

    public function updateFieldsMasse($ids, $fields, $filters = array())
    {
        // A amélioré grandement

        $set = array();
        foreach ($fields as $field => $val) {
            $set[] = $this->getFieldSqlKey($field) . ' = ' . $this->getDbValue($field, $val);
        }
        $filters = BimpTools::mergeSqlFilter($filters, $this->getPrimary(), array('in' => $ids));

        $where = BimpTools::getSqlWhere($filters);

        $req = 'UPDATE ' . MAIN_DB_PREFIX . $this->getTable() . ' a SET ' . implode(', ', $set) . $where;

        $this->db->execute($req);
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $this->noFetchOnTrigger = true;
        $this->force_update = $force_update;

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
                    $status_prop = $this->getStatusProperty();
                    $init_status = null;
                    if ($status_prop) {
                        $init_status = $this->getInitData($status_prop);
                    }

                    if ($this->use_commom_fields) {
                        $this->data['date_update'] = date('Y-m-d H:i:s');
                        global $user;
                        if (isset($user->id)) {
                            $this->data['user_update'] = (int) $user->id;
                        } else {
                            $this->data['user_update'] = 0;
                        }
                    } else {
                        $date_update_field = $this->getDateUpdateProperty();
                        if ($date_update_field) {
                            $this->data[$date_update_field] = date('Y-m-d H:i:s');
                        }

                        $user_update_field = $this->getUserUpdateProperty();
                        if ($user_update_field) {
                            global $user;
                            if (BimpObject::objectLoaded($user)) {
                                $this->data[$user_update_field] = $user->id;
                            }
                        }
                    }

                    if ($this->isDolObject()) {
                        $this->checkDolFieldsHistory();
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

                        if ($this->isDolObject()) {
                            $dol_errors = BimpTools::getErrorsFromDolObject($this->dol_object);

                            if (count($dol_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($dol_errors, $msg);
                            }
                        } else {
                            $errors[] = $msg;
                        }
                    } else {
                        $extra_errors = $this->updateExtraFields();
                        if (count($extra_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($extra_errors, 'Des erreurs sont survenues lors de l\'enregistrement des champs supplémentaires');
                        }

                        // Logs nouvelles valeurs de champs: 
                        $logs = array();
                        foreach ($this->fieldsWithAddNoteOnUpdate as $champAddNote) {
                            if ($this->getData($champAddNote) != $this->getInitData($champAddNote)) {
                                $logs[] = html_entity_decode('Champ ' . $this->displayFieldName($champAddNote) . ' modifié. 
Ancienne valeur : ' . $this->displayInitData($champAddNote, 'default', false, true) . '
Nouvelle : ' . $this->displayData($champAddNote, 'default', false, true));
                            }
                        }

                        if (count($logs)) {
                            $this->addObjectLog(implode("\n", $logs));
                        }

                        // Log changement de statut
                        if ($status_prop && (int) $this->params['new_status_logs']) {
                            if ($init_status != $this->getData($status_prop)) {
                                $this->addObjectLog('Mise au statut: ' . $this->displayData($status_prop, 'default', false, true), 'NEW_STATUS_' . $this->getData($status_prop));
                            }
                        }

                        $this->initData = $this->data;
                        self::setBimpObjectInstance($this);

                        $warnings = BimpTools::merge_array($warnings, $this->updateAssociations());
                        $warnings = BimpTools::merge_array($warnings, $this->saveHistory());
                        $warnings = BimpTools::merge_array($warnings, $this->checkFieldsHashtags());

                        $parent = $this->getParentInstance();

                        if (!is_null($parent)) {
                            if (method_exists($parent, 'onChildUpdate')) {
                                $parent->onChildUpdate($this);
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

    public function updateField($field, $value, $id_object = null, $force_update = true, $do_not_validate = false, $no_triggers = false)
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

            $init_data = $this->getData($field);

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
                        $data = array(
                            $field => $db_value
                        );

                        $date_update_field = $this->getDateUpdateProperty();
                        if ($date_update_field) {
                            $data[$date_update_field] = date('Y-m-d H:i:s');
                        }

                        $user_update_field = $this->getUserUpdateProperty();
                        if ($user_update_field) {
                            global $user;
                            if (BimpObject::objectLoaded($user)) {
                                $data[$user_update_field] = $user->id;
                            }
                        }

                        if ($this->db->update($table, $data, '`' . $primary . '` = ' . (int) $id_object) <= 0) {
                            $sqlError = $this->db->db->lasterror();
                            $errors[] = 'Echec de la mise à jour du champ "' . $field . '"' . ($sqlError ? ' - ' . $sqlError : '');
                        }
                    } else {
                        $errors[] = 'Erreur de configuration: paramètres de la table invalides';
                    }
                }

                $warnings = array();
                if (!count($errors)) {
                    $this->initData[$field] = $init_data;
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

                    // Check des hastags: 
                    $warnings = BimpTools::merge_array($warnings, $this->checkFieldsHashtags(array($field)));

                    if (!$no_triggers) {
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

        $filters = $this->checkSqlFilters($filters, $joins, 'a');

        $sql = BimpTools::getSqlSelect('a.' . $primary);
        $sql .= BimpTools::getSqlFrom($table, $joins);
        $sql .= BimpTools::getSqlWhere($filters);

        $rows = $this->db->executeS($sql, 'array');

        if (!is_null($rows) && count($rows)) {
            if (count($rows) > 1) {
                if (!empty($filters) && $delete_if_multiple) {
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
        BimpDebug::addDebugTime('Fetch ' . $this->getLabel() . ' - ID ' . $id);

        $this->reset();

        $reTesteParentType = false;
        if (!is_null($parent) && is_object($parent)) {
            if ($this->getParentObjectName() == '') {//pour l'instant on garde, on retestera aprés l'instanciation
                $this->parent = $parent;
                $reTesteParentType = true;
            } elseif (is_a($parent, $this->getParentObjectName())) {//tous vas bien, le parent est bien du type attendue
                $this->parent = $parent;
            } else {//Attention, le parent ne correspond pas
                BimpCore::addlog('Instance parente invalide dans fetch()', 3, 'bimpcore', $this, array(
                    'Attendu' => $this->getParentObjectName(),
                    'Obtenu'  => get_class($parent)
                        ), true);
            }
        }

        if (!is_null($this->dol_object)) {
            return $this->fetchDolObject($id);
        }

        $table = $this->getTable();
        $primary = $this->getPrimary();

        if (is_null($table) || !$table) {
            return false;
        }

        $where = '`' . $primary . '` = ' . (int) $id;
        if (BimpTools::isModuleDoliActif('MULTICOMPANY')) {
            if ($this->getEntity_name())
                $where .= ' AND entity IN (' . getEntity($this->getEntity_name()) . ')';
        }
        $row = $this->db->getRow($table, $where);

        if (!is_null($row)) {
            foreach ($row as $field => $value) {
                if ($field === $primary) {
                    $this->id = (int) $value;
                } elseif ($this->field_exists($field)) {
                    $value = $this->getValueFromDb($field, $value);
                    $this->checkFieldValueType($field, $value);
                    $this->data[$field] = $value;
                }
            }

            $extra_fields = $this->fetchExtraFields();

            foreach ($extra_fields as $field_name => $value) {
                $value = $this->getValueFromDb($field, $value);
                $this->checkFieldValueType($field_name, $value);
                $this->data[$field_name] = $value;
            }

            $this->initData = $this->data;
            $this->ref = $this->getRef();

            if ($reTesteParentType && !is_a($parent, $this->getParentObjectName())) {//Le premier test a échoué, et même aprés instanciation le parent ne correspond pas au type attendu.
                BimpCore::addlog('Instance parente invalide dans fetch()', 3, 'bimpcore', $this, array(
                    'Attendu' => $this->getParentObjectName(),
                    'Obtenu'  => get_class($parent)
                        ), true);
                $this->parent = null;
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
        } elseif ($this->isDolObject()) {
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

            $sqlError = $this->db->err();
            if ($sqlError) {
                $msg .= ' - Erreur SQL: ' . $sqlError;
            }

            if ($this->isDolObject()) {
                $msg .= ' - Code: ' . $result;
            }


            $errors[] = $msg;
        } elseif (!count($errors)) {
            $id = $this->id;
            $this->isDeleting = true;

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
                    $parent->onChildDelete($this, $id);
                }
            }

            // Suppression de l'objet du cache: 
            self::unsetBimpObjectInstance($this->module, $this->object_name, $id);

            // Réinitialisation de l'instance:
            $this->isDeleting = false;
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
            $errors[] = 'Aucun filtre';
            return false;
        }

        $table = $this->getTable();
        if (is_null($table)) {
            $errors[] = 'Nom de la table absent';
            return false;
        }
        $primary = $this->getPrimary();
        if (is_null($primary)) {
            $errors[] = 'Champ primaire absent';
            return false;
        }

        $sql = BimpTools::getSqlSelect(array($primary));
        $sql .= BimpTools::getSqlFrom($table);
        $sql .= BimpTools::getSqlWhere($filters);

        $items = $this->db->executeS($sql, 'array');

        $check = true;
        if (!is_null($items)) {
            foreach ($items as $item) {
                $instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $item[$primary]);
                if ($instance->isLoaded()) {
                    $del_warnings = array();
                    $del_errors = $instance->delete($del_warnings, $force_delete);
                    if (count($del_errors)) {
                        $check = false;
                        $errors[] = BimpTools::getMsgFromArray($del_errors, 'Echec de la suppression ' . $this->getLabel('of_the') . ' d\'ID ' . $item[$primary]);
                    }
                    if (count($del_warnings)) {
                        $check = false;
                        $errors[] = BimpTools::getMsgFromArray($del_warnings, 'Des erreurs sont survenues suite à la suppression ' . $this->getLabel('of_the') . ' d\'ID ' . $item[$primary]);
                    }
                }
            }
        }
        return $check;
    }

    // Gestion DolObjects: 

    public function hydrateDolObject(&$bimpObjectFields = array(), $withDefault = true)
    {
        $errors = array();

        if (!is_object($this->dol_object)) {
            $this->dol_object = $this->config->getObject('dol_object');

            if (is_object($this->dol_object)) {
                $this->dol_object->db = $this->db->db;
            }
        }

        foreach ($this->params['fields'] as $field) {
            if ($this->field_exists($field)) {
                $value = $this->getData($field, $withDefault);
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

            if (is_object($this->dol_object)) {
                $this->dol_object->db = $this->db->db;
            }
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

            $create_method_name = $this->getConf('dol_create_method', 'create');

            // Pour enlever les éventuelles clés associatives (erreur fatale depuis PHP8)
            $args = array();
            foreach ($params as $key => $value) {
                $args[] = $value;
            }

            $result = call_user_func_array(array($this->dol_object, $create_method_name), $args);
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

                    if (isset($this->dol_object->id) && (int) $this->dol_object->id) {
                        $id = (int) $this->dol_object->id;
                    } else {
                        $id = $result;
                    }

                    $data = $this->getDbData($fields);
                    if (!empty($data)) {
                        $up_result = $this->db->update($this->getTable(), $data, '`' . $this->getPrimary() . '` = ' . (int) $id);

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
            return $id;
        }

        $this->noFetchOnTrigger = false;
        return 0;
    }

    protected function updateDolObject(&$errors = array(), &$warnings = array())
    {
        if (!$this->isLoaded()) {
            return 0;
        }
        if (is_null($this->dol_object)) {
            $errors[] = 'Objet Dolibarr invalide';
            return 0;
        }

        if (!isset($this->dol_object->id) || !$this->dol_object->id) {
            $errors[] = 'Objet Dolibarr invalide';
            return 0;
        }

        $this->noFetchOnTrigger = true;

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

            // Pour enlever les éventuelles clés associatives (erreur fatale depuis PHP8)
            $args = array();
            foreach ($params as $key => $value) {
                $args[] = $value;
            }

            $result = call_user_func_array(array($this->dol_object, 'update'), $args);

            if ((int) $this->params['force_extrafields_update']) {
                foreach ($this->dol_object->array_options as $key => $value) {
                    if ($this->dol_field_exists(str_replace('options_', '', $key))) {
                        if ($this->dol_object->updateExtraField(str_replace('options_', '', $key), null, null) < 0) {
                            $warnings[] = 'Echec de l\'enregistrement de l\'attribut supplémentaire "' . str_replace('options_', '', $key);
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

            if (is_object($this->dol_object)) {
                $this->dol_object->db = $this->db->db;
            }
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

        // Pour enlever les éventuelles clés associatives (erreur fatale depuis PHP8)
        $args = array();
        foreach ($params as $key => $value) {
            $args[] = $value;
        }

        $result = call_user_func_array(array($this->dol_object, 'fetch'), $args);

        if ($result <= 0) {
            if (isset($this->dol_object->error) && $this->dol_object->error) {
                $errors[] = $this->dol_object->error;
            }

            return false;
        }
        if (isset($this->dol_object->entity) && !in_array($this->dol_object->entity, $this->getEntitysArray()))
            return false;

        $bimpObjectFields = array();

        $errors = $this->hydrateFromDolObject($bimpObjectFields);

        if (!empty($bimpObjectFields)) {
            $where = '`' . $this->getPrimary() . '` = ' . (int) $id;
            if (BimpTools::isModuleDoliActif('MULTICOMPANY')) {
                if ($this->getEntity_name())
                    $where .= ' AND entity IN (' . getEntity($this->getEntity_name()) . ')';
            }
            $result = $this->db->getRow($this->getTable(), $where, $bimpObjectFields, 'array');
            if (!is_null($result)) {
                foreach ($bimpObjectFields as $field_name) {
                    if (!$this->field_exists($field_name) || !isset($result[$field_name])) {
                        continue;
                    }
                    $value = $result[$field_name];
                    $this->checkFieldValueType($field_name, $value);
                    $this->data[$field_name] = $value;
                }
            } else {
                BimpCore::addlog('Echec obtention champs supplémentaires', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', $this, array(
                    'Erreur SQL'    => $this->db->err(),
                    'Champs suppl.' => $bimpObjectFields,
                    'Param SQL'     => implode("<br/>", array($this->getTable(), '`' . $this->getPrimary() . '` = ' . (int) $id, $bimpObjectFields))
                ));
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

        $delete_method = $this->getConf('dol_delete_method', 'delete');

        if (method_exists($this->dol_object, $delete_method)) {
            // Pour enlever les éventuelles clés associatives (erreur fatale depuis PHP8)
            $args = array();
            foreach ($params as $key => $value) {
                $args[] = $value;
            }

            $result = call_user_func_array(array($this->dol_object, $delete_method), $args);
        } else {
            $errors[] = 'La méthode "' . $delete_method . '" n\'existe pas dans l\'objet "' . get_class($this->dol_object) . '"';
            return 0;
        }

        if ($result <= 0) {
            if (isset($this->dol_object->error) && $this->dol_object->error) {
                $errors[] = $this->dol_object->error;
            }

            $err_sql = $this->dol_object->db->lasterror();
            if ($err_sql) {
                $errors[] = $err_sql;
            }

            return $result;
        }

        return 1;
    }

    // Gestion Fields Extra: 

    public function insertExtraFields()
    {
        // Enregistrer tous les extrafields (create) 
        // A ce stade l'ID est déjà assigné à l'objet
        // retourner un tableau d'erreurs. 
//        if (count($this->getExtraFields())) {
//            return array('Fonction d\'enregistrement des champs supplémentaires non implémentée');
//        }

        return array();
    }

    public function updateExtraFields()
    {
        // Mettre à jour tous les extrafields
        // retourner un tableau d'erreurs. 
//        if (count($this->getExtraFields())) {
//            return array('Fonction d\'enregistrement des champs supplémentaires non implémentée');
//        }

        return array();
    }

    public function updateExtraField($field_name, $value, $id_object)
    {
        // enregistrer la valeur $value pour le champ extra: $field_name et l'ID $id_object (Ne pas tenir compte de $this->id). 
        // Retourner un tableau d'erreurs.
//        if ($this->isExtraField($field_name)) {
//            return array('Fonction d\'enregistrement des champs supplémentaires non implémentée');
//        }

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
//        if (count($this->getExtraFields())) {
//            return array('Fonction de suppression des champs supplémentaires non implémentée');
//        }

        return array();
    }

    public function getExtraFieldSavedValue($field, $id_object)
    {
        // retourner la valeur actuelle en base pour le champ $field et l'ID objet $id_object (Ici, ne pas tenir compte de $this->id). 
        // Retourner null si pas d'entrée en base. 

        return null;
    }

    public function getExtraFieldFilterKey($field, &$joins, $main_alias = 'a', &$filters = array())
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

    public function isFieldActivated($field_name, &$infos = '')
    {
        return $this->isFieldUsed($field_name, $infos);
    }

    public function isFieldUsed($field_name, &$infos = '')
    {
        if ($this->config->isDefined('fields/' . $field_name)) {
            if ((int) $this->getConf('fields/' . $field_name . '/unused', 0, false, 'bool')) {
                $infos = 'Champ "' . $field_name . '" défini comme non utilisé';
                return 0;
            }

            return 1;
        }

        $infos = 'Champ "' . $field_name . '" non défini dans la config yml';

        return 0;
    }

    public function isFormAllowed($form_name, &$errors = array())
    {
        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'addFiles':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                return 1;
        }

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
//        echo '<pre>'. get_class($this);
        if(isset($this->dol_object)){//peut être un peut lourd, mais plus safe...
            $return = BimpCache::dol_can($this->dol_object);
            if(!$return)
                return 0;
        }
//        print_r($this->dol_object);
//        echo('rrr');
        switch ($right) {
            case "view" :
                if (BimpCore::isContextPublic()) {
                    return $this->canClientView();
                }
                return $this->canView();
            case 'edit' :
                if (BimpCore::isContextPublic()) {
                    return $this->canClientEdit();
                }
                return $this->canEdit();
            case "create" :
                if (BimpCore::isContextPublic()) {
                    return $this->canClientCreate();
                }
                return $this->canCreate();
            case "delete" :
                if (BimpCore::isContextPublic()) {
                    return $this->canClientDelete();
                }
                return $this->canDelete();
        }

        return 0;
    }

    public function canCreate()
    {
        if (BimpCore::isContextPublic()) {
            $this->canClientCreate();
        }

        if ($this->params['parent_object']) {
            $parent = $this->getParentInstance();
            if (is_a($parent, 'BimpObject')) {
                return (int) $parent->canCreateChild($this->object_name);
            }
        }

        return 1;
    }

    public function canEdit()
    {
        if (BimpCore::isContextPublic()) {
            return $this->canClientEdit();
        }

        if ($this->params['parent_object']) {
            $parent = $this->getParentInstance();
            if (is_a($parent, 'BimpObject')) {
                return (int) $parent->canEditChild($this->object_name);
            }
        }

        return $this->canCreate();
    }

    public function canView()
    {
        if (BimpCore::isContextPublic()) {
            return $this->canClientView();
        }

        if ($this->params['parent_object']) {
            $parent = $this->getParentInstance();
            if (is_a($parent, 'BimpObject')) {
                return (int) $parent->canViewChild($this->object_name);
            }
        }

        return 1;
    }

    public function canDelete()
    {
        if (BimpCore::isContextPublic()) {
            return $this->canClientDelete();
        }

        if ($this->params['parent_object']) {
            $parent = $this->getParentInstance();
            if (is_a($parent, 'BimpObject')) {
                return (int) $parent->canDeleteChild($this->object_name);
            }
        }

        return $this->canEdit();
    }

    public function canClientCreate()
    {
        return 0;
    }

    public function canClientEdit()
    {
        return 0;
    }

    public function canClientView()
    {
        return 0;
    }

    public function canClientDelete()
    {
        return 0;
    }

    public function canEditField($field_name)
    {
        if (BimpCore::isContextPublic()) {
            return $this->canClientEdit();
        }
        
        if($field_name == 'entity'){
            global $user;
            return $user->admin;
        }

        return (int) $this->canEdit();
    }

    public function canViewField($field_name)
    {
        if (BimpCore::isContextPublic()) {
            return $this->canClientView();
        }

        return (int) $this->canView();
    }

    public function canCreateChild($child_name)
    {
        if (BimpCore::isContextPublic()) {
            return $this->canClientCreate();
        }

        return (int) $this->canCreate();
    }

    public function canEditChild($child_name)
    {
        if (BimpCore::isContextPublic()) {
            return $this->canClientEdit();
        }

        return (int) $this->canEdit();
    }

    public function canViewChild($child_name)
    {
        if (BimpCore::isContextPublic()) {
            return $this->canClientView();
        }

        return (int) $this->canView();
    }

    public function canDeleteChild($child_name)
    {
        if (BimpCore::isContextPublic()) {
            return $this->canClientDelete();
        }

        return (int) $this->canDelete();
    }

    public function canSetAction($action)
    {
        if (BimpCore::isContextPublic()) {
            return 0;
        }

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
        if (BimpCore::isContextPublic()) {
            return 0;
        }

        return 1;
    }

    public function canDeleteFiles()
    {
        if (BimpCore::isContextPublic()) {
            return 0;
        }

        return 1;
    }

    // Gestion des positions: 

    public function resetPositions()
    {
        if ($this->getConf('positions', false, false, 'bool')) {
            $parent = $this->getParentInstance();

            if (is_a($parent, 'BimpObject')) {
                if ($parent->isDeleting) {
                    return;
                }
            }
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

    public function setPosition($new_position, &$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return false;
        }

        if ($this->params['positions']) {
            $old_position = (int) $this->getInitData('position');
            if ($old_position === $new_position) {
                return true;
            }

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
            $check = true;

            if (!in_array($this->object_name, array('Bimp_Menu'))) {
                $items = $this->getList($filters, null, null, 'position', 'asc', 'array', array($primary, 'position'), array(), $primary, ($this->params['position_insert'] === 'before' ? 'DESC' : 'ASC'));
                if ($this->db->update($table, array(
                            'position' => (int) $new_position
                                ), '`' . $primary . '` = ' . (int) $this->id) <= 0) {
                    $check = false;
                }

                if ($check) {
                    $this->set('position', (int) $new_position);
                    $i = 1;
                    foreach ($items as $item) {
                        if ($i === (int) $new_position) {
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
            } else {
                // Nécessaire pour contourner un index unique sur le champ position) 
                $this->db->db->begin();

                // On attribue temporairement la position suivante à l'élément déplacé pour libérer sa position actuelle.
                if ($this->db->update($table, array(
                            'position' => (int) $this->getNextPosition()
                                ), '`' . $primary . '` = ' . $this->id) <= 0) {
                    $errors[] = $this->db->err();
                    $check = false;
                }

                if ($check) {
                    // On décale une par une les positions des éléments affectés: 
                    $items_filters = $filters;
                    if ($new_position > $old_position) {
                        $items_filters['position'] = array(
                            'and' => array(
                                array(
                                    'operator' => '>',
                                    'value'    => $old_position
                                ),
                                array(
                                    'operator' => '<=',
                                    'value'    => $new_position
                                )
                            )
                        );
                        $items = $this->getList($items_filters, null, null, 'position', 'asc', 'array', array($primary, 'position'));
                        foreach ($items as $item) {
                            if ($check) {
                                if ($this->db->update($table, array(
                                            'position' => ((int) $item['position'] - 1)
                                                ), '`' . $primary . '` = ' . (int) $item[$primary]) <= 0) {
                                    $errors[] = $this->db->err();
                                    $check = false;
                                }
                            }
                        }
                    } else {
                        $items_filters['position'] = array(
                            'and' => array(
                                array(
                                    'operator' => '>=',
                                    'value'    => $new_position
                                ),
                                array(
                                    'operator' => '<',
                                    'value'    => $old_position
                                )
                            )
                        );
                        $items = $this->getList($items_filters, null, null, 'position', 'desc', 'array', array($primary, 'position'));
                        foreach ($items as $item) {
                            if ($check) {
                                if ($this->db->update($table, array(
                                            'position' => ((int) $item['position'] + 1)
                                                ), '`' . $primary . '` = ' . (int) $item[$primary]) <= 0) {
                                    $errors[] = $this->db->err();
                                    $check = false;
                                }
                            }
                        }
                    }

                    if ($check) {
                        // on Attribue la position finale à l'élément déplacé (qui a normalement été libérée)
                        if ($this->db->update($table, array(
                                    'position' => $new_position
                                        ), '`' . $primary . '` = ' . $this->id) <= 0) {
                            $errors[] = $this->db->err();
                            $check = false;
                        }
                    }

                    if ($check) {
                        $this->db->db->commit();
                    } else {
                        $this->db->db->rollback();
                    }
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
                    if ($this->isDeleting && (int) $this->getConf('objects/' . $children_name . '/delete', 0, false, 'bool')) {
                        return;
                    }

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

    public function addNote($content, $visibility = null, $viewed = 0, $auto = 1, $email = '', $type_author = 1, $type_dest = 0, $fk_group_dest = 0, $fk_user_dest = 0, $delete_on_view = 0, $id_societe = 0)
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $note = BimpObject::getInstance('bimpcore', 'BimpNote');
        $note->initBdd($this->db->db->noTransaction);

        if (is_string($fk_group_dest) && $fk_group_dest) {
            $fk_group_dest = BimpCore::getUserGroupId($fk_group_dest);
        }

        if (is_string($fk_user_dest) && $fk_user_dest) {
            eval('if (isset(BimpNote::' . $fk_user_dest . ')) $fk_user_dest = BimpNote::' . $fk_user_dest . ';');
        }

        if (is_null($visibility)) {
            $visibility = BimpNote::BN_MEMBERS;
        }

        $errors = $note->validateArray(array(
            'obj_type'       => 'bimp_object',
            'obj_module'     => $this->module,
            'obj_name'       => $this->object_name,
            'id_obj'         => (int) $this->id,
            'visibility'     => (int) $visibility,
            'content'        => $content,
            'viewed'         => $viewed,
            'auto'           => $auto,
            "email"          => $email,
            "type_author"    => $type_author,
            'type_dest'      => $type_dest,
            'fk_group_dest'  => $fk_group_dest,
            'fk_user_dest'   => $fk_user_dest,
            'delete_on_view' => $delete_on_view,
            'id_societe'     => $id_societe
        ));

        if (!count($errors)) {
            $warnings = array();
            $errors = $note->create($warnings, true);
        }
        $note->initBdd();

        return $errors;
    }

    public function getNotes($withObject = true, $min_visibility = null)
    {
        $list = self::getObjectNotes($this, $min_visibility);

        if ($withObject) {
            $notes = array();

            foreach ($list as $id_note) {
                $note = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNote', $id_note);
                if (BimpObject::objectLoaded($note)) {
                    $notes[$id_note] = $note;
                }
            }

            return $notes;
        }

        return $list;
    }

    public function renderNotesList($filter_by_user = true, $list_name = "default", $suffixe = "", $archive = false, $withLinked = true)
    {
        if ($this->isLoaded()) {
            if ($archive) {
                $note = BimpObject::getInstance('bimpcore', 'BimpNoteArchive');
            } else {
                $note = BimpObject::getInstance('bimpcore', 'BimpNote');
            }

            $list = new BC_ListTable($note, $list_name);
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

            if (BimpCore::getConf('date_archive', '') != '') {
                $btnHisto = '<div id="notes_archives_' . $this->object_name . '_' . $list->identifier . '_container">';
                $btnHisto .= '<button class="btn btn-default" value="charr" onclick="' . $this->getJsLoadCustomContent('renderNotesList', "$('#notes_archives_" . $this->object_name . '_' . $list->identifier . "_container')", array($filter_by_user, $list_model, $suffixe, true, $withLinked)) . '">' . BimpRender::renderIcon('fas_history') . ' Afficher les notes archivées</button>';
                $btnHisto .= '</div>';
            }

            $sup = '';
            if ($withLinked && $withLinked !== 'false' && !is_a($this, 'Bimp_Societe') && !is_a($this, 'Bimp_Product')) {
                $linkedObjects = $this->getFullLinkedObjetsArray(false);
                if (count($linkedObjects) > 0) {
                    $filterLinked = array('linked' => array('or' => array()));
                    foreach ($linkedObjects as $data_linked => $inut) {
                        $data_linked = json_decode($data_linked, true);
                        if (!in_array($data_linked['object_name'], array('Equipment', 'BS_SAV', 'BS_Ticket', 'BR_Reservation', 'Bimp_PropalLine', 'Bimp_CommandeLine', 'Bimp_FactureLine', 'BimpRevalorisation', 'FactureDolLine', 'Bimp_Vente', 'Bimp_FactureFournLine', 'Bimp_CommandeFournLine', 'Bimp_Achat'))) {
                            $filterLinked['linked']['or'][$data_linked["object_name"] . $data_linked['id_object']] = array('and_fields' => array(
                                    'obj_module' => $data_linked['module'],
                                    'obj_name'   => $data_linked['object_name'],
                                    'id_obj'     => $data_linked['id_object']
                            ));
                        }
                    }
                    $nb = count($filterLinked['linked']['or']);
                    if ($nb > 180)
                        BimpCore::addlog('Attention de trop nombreux objets liées pour l\'affichage des notes ' . $this->getLink() . '(' . $nb . ')');

                    $list2 = new BC_ListTable($note, 'linked', 1, null, 'Toutes les notes liées (' . $nb . ' objects)');
                    $list2->addIdentifierSuffix($suffixe . '_linked');
                    $list2->addFieldFilterValue('obj_type', 'bimp_object');
                    $list2->addFieldFilterValue('custom', $filterLinked['linked']);
                    $sup .= $list2->renderHtml();
                }
            }

            return $list->renderHtml() . $sup . ($archive == false ? $btnHisto : '');
        }

        return BimpRender::renderAlerts('Impossible d\'afficher la liste des notes (ID ' . $this->getLabel('of_the') . ' absent)');
    }

    // Rendus HTML

    public function renderHeader($content_only = false, $params = array())
    {
        if (BimpCore::isContextPublic()) {
            return $this->renderPublicHeader($content_only, $params);
        }

        $html = '';
        if ($this->isLoaded()) {
            if (!$content_only) {
                $html .= '<div id="' . $this->object_name . '_' . $this->id . '_header" class="object_header container-fluid">';
            }
            $html .= '<div class="row">';

            $html .= '<div class="col-lg-6 col-sm-7 col-xs-12">';

            // Menu objet:
            $html .= $this->renderObjectMenu();

            $html .= '<div style="display: inline-block">';

            // Logo / image: 
            $logo_html = $this->renderLogo('mini', true);

            if ($logo_html) {
                $html .= '<div class="object_header_logo">';
                $html .= $logo_html;
                $html .= '</div>';
            }

            // Titre: 
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
                $html .= self::replaceHastags($ref);
                $html .= '</h2>';
            }

            $name = $this->getName(false);
            if ($name) {
                $html .= '<h4>';
                $html .= self::replaceHastags($name);
                $html .= '</h4>';
            }
            $html .= '</div>';

            // Infos création / màj: 
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

            // Hashtags: 
            BimpObject::loadClass('bimpcore', 'BimpLink');
            $links = BimpLink::getLinksForSource($this, '', 'bimpcore', 'BimpHashtag');

            if (!empty($links)) {
                $html .= '<div class="object_header_infos">';
                foreach ($links as $link) {
                    $html .= '<span class="hashtag">';
                    $hashtag = $link->getLinkedObject();

                    if (BimpObject::objectLoaded($hashtag)) {
                        $html .= '<a href="' . $hashtag->getUrl() . '" target="_blank">#' . $hashtag->getData('code') . '</a>';
                        $html .= BimpRender::renderObjectIcons($hashtag, false, 'default');
                    }
                    $html .= '</span>';
                }
                $html .= '</div>';
            }


            // Infos extra
            $html .= '<div class="header_extra">';
            if (method_exists($this, 'renderHeaderExtraLeft')) {
                $html .= '<div style="margin: 10px 0;">';
                $html .= $this->renderHeaderExtraLeft();
                $html .= '</div>';
            }
            
            if($this->isDolObject()&& isset($this->dol_object)){
                global $hookmanager;
                $hookmanager->initHooks(array($this->dol_object->element.'card', 'globalcard'));
                $parameters = array();
                $reshook = $hookmanager->executeHooks('moreHtmlRef', $parameters, $this->dol_object); // Note that $action and $object may have been modified by hook
                if (empty($reshook)) {
                        $html .= $hookmanager->resPrint;
                } elseif ($reshook > 0) {
                        $html .= $hookmanager->resPrint;
                }
            }

            // Messages: 
            $html .= $this->renderMsgs();
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div class="col-lg-6 col-sm-5 col-xs-12" style="text-align: right">';

            // Statut: 
            $status = '';
            $status_prop = $this->getStatusProperty();
            if ($this->field_exists($status_prop)) {
                $status = $this->displayData($status_prop);
            }

            $html .= '<div class="header_status">';
            if ($status) {
                $html .= $status;
            }

            // Extra statut: 
            if (method_exists($this, 'renderHeaderStatusExtra')) {
                $html .= $this->renderHeaderStatusExtra();
            }
            $html .= '</div>';
            $html .= '<div class="header_tools">';

            // Boutons utilitaires: 
            $url = $this->getUrl();
            if ($url) {
                $html .= '<span class="headerIconButton bs-popover copy_object_link_header_button" onclick="bimp_copyTabsUrl($(this), \'' . $url . '\', \'' . $_SERVER['SERVER_NAME'] . '\')"';
                $html .= BimpRender::renderPopoverData('Copier lien vers onglet actuel', 'left');
                $html .= '>';
                $html .= BimpRender::renderIcon('fas_link');
                $html .= '</span>';
            }

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

            $header_buttons = $this->config->getCompiledParams('header_btn');
            $html .= '<div class="header_buttons">';

            if (!empty($header_buttons)) {
                if (isset($header_buttons['buttons_groups'])) {
                    $html .= BimpRender::renderButtonsGroups($header_buttons['buttons_groups'], array(
                                'max'                 => 1,
                                'dropdown_menu_right' => 1
                    ));
                } else {
                    $html .= BimpRender::renderButtonsGroup($header_buttons, array(
                                'max'                 => 4,
                                'dropdown_menu_right' => 1
                    ));
                }
            }

            // Bouton édition: 
            $html .= '<div style="display: inline-block">';
            if ($this->params['header_edit_form'] && $this->isEditable() && $this->can('edit')) {
                $html .= '<span class="btn btn-primary bs-popover" onclick="' . $this->getJsLoadModalForm($this->params['header_edit_form'], addslashes("Edition " . $this->getLabel('of_the') . ' #' . $this->id)) . '"';
                $html .= BimpRender::renderPopoverData('Editer');
                $html .= '>';
                $html .= BimpRender::renderIcon('fas_edit');
                $html .= '</span>';
            }

            // Bouton suppression: 
            if ((int) $this->params['header_delete_btn'] && $this->isDeletable() && $this->can('delete')) {
                $html .= '<span class="btn btn-danger bs-popover" onclick="' . $this->getJsDeleteOnClick(array(
                            'on_success' => 'reload'
                        )) . '"';
                $html .= BimpRender::renderPopoverData('Supprimer');
                $html .= '>';
                $html .= BimpRender::renderIcon('fas_trash-alt');
                $html .= '</span>';
            }

            // Bouton objets liés par citation: 
            $onclick = $this->getJsLoadModalCustomContent('renderLinkedObjectsLists', 'Objets liés par citation');
            $html .= '<span class="btn btn-default bs-popover"';
            $html .= ' onclick="' . $onclick . '"';
            $html .= BimpRender::renderPopoverData('Objets liés par citation');
            $html .= '>';
            $html .= BimpRender::renderIcon('fas_hashtag');
            $html .= '</span>';

            // Historique objet:
            $onclick = $this->getJsLoadModalCustomContent('renderLogsList', 'Historique');
            $html .= '<span class="btn btn-default bs-popover"';
            $html .= ' onclick="' . $onclick . '"';
            $html .= BimpRender::renderPopoverData('Historique');
            $html .= '>';
            $html .= BimpRender::renderIcon('fas_history');
            $html .= '</span>';

            //Suivi mail
            $random = rand(11111, 99999);
            $htmlId = 'suivi_mail_' . $random;
            $onclick = $this->getJsLoadModalCustomContent('renderSuiviMail', 'Suivi des mails', array(), 'large');
            $html .= '<span id="' . $htmlId . '" class="btn btn-default bs-popover"';
            $html .= ' onclick="' . $onclick . '"';
            $html .= BimpRender::renderPopoverData('Suivi des mails');
            $html .= '>';
            $html .= BimpRender::renderIcon('fas_inbox');
            $html .= '</span>';

            if (BimpTools::getValue('open', '') == 'suivi_mail') {
                $html .= '<script>$(document).ready(function(){  $("#' . $htmlId . '").click();});</script>';
            }

            // Logs objet:
            if (BimpCore::isUserDev()) {
                $log_instance = BimpObject::getInstance('bimpcore', 'Bimp_Log');
                $onclick = $log_instance->getJsLoadModalList('object', array(
                    'title'         => 'Logs ' . $this->getLabel('of_the') . ' ' . $this->getRef(),
                    'extra_filters' => array(
                        'obj_module' => $this->module,
                        'obj_name'   => $this->object_name,
                        'id_object'  => $this->id
                    )
                ));
                $html .= '<span class="btn btn-default bs-popover"';
                $html .= ' onclick="' . $onclick . '"';
                $html .= BimpRender::renderPopoverData('Logs objet');
                $html .= '>';
                $html .= BimpRender::renderIcon('fas_book-medical');
                $html .= '</span>';
            }

            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div class="header_extra">';

            // Extra right: 
            if (method_exists($this, 'renderHeaderExtraRight')) {
                $html .= '<div style="margin: 10px 0;">';
                $html .= $this->renderHeaderExtraRight($no_div = false);
                $html .= '</div>';
            }

            // Bouton redirection: 
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

    public function renderPublicHeader($content_only = false, $params = array())
    {
        $html = '';
        if ($this->isLoaded()) {
            if (!$content_only) {
                $html .= '<div id="' . $this->object_name . '_' . $this->id . '_header" class="object_header container-fluid">';
            }
            $html .= '<div class="row">';

            $html .= '<div class="col-lg-6 col-sm-8 col-xs-12">';

            $html .= '<div style="display: inline-block">';

            // todo: bug à résoudre
            //Logo: 
//            $logo_html = $this->renderLogo('mini', true);
//            if ($logo_html) {
//                $html .= '<div class="object_header_logo">';
//                $html .= $logo_html;
//                $html .= '</div>';
//            }
            // Titre: 
            $html .= '<div class="object_header_title">';
            $html .= '<h2>';
            $icon = $this->getConf('public_icon', $this->params['icon']);
            if ($icon) {
                $html .= '<i class="' . BimpRender::renderIconClass($icon) . ' iconLeft"></i>';
            }
            $ref = $this->getRef(false);
            $html .= BimpTools::ucfirst($this->getLabel()) . ($ref ? ' ' . $ref : '');
            $html .= '</h2>';

            $name = $this->getName(false);
            if ($name) {
                $html .= '<h4>';
                $html .= $name;
                $html .= '</h4>';
            }
            $html .= '</div>';

            // Infos créa / màj: 
            if ($this->use_commom_fields) {
                if ((int) $this->getData('date_create')) {
                    $html .= '<div class="object_header_infos">';
                    $html .= 'Créé' . ($this->isLabelFemale() ? 'e' : '') . ' le <strong>' . $this->displayData('date_create') . '</strong>';
                    $html .= '</div>';
                }
                if ((int) $this->getData('date_update')) {
                    $html .= '<div class="object_header_infos">';
                    $html .= 'Dernière mise à jour le <strong>' . $this->displayData('date_update') . '</strong>';
                    $html .= '</div>';
                }
            }

            // Extra left: 
            $html .= '<div class="header_extra">';
            if (method_exists($this, 'renderPublicHeaderExtraLeft')) {
                $html .= '<div style="margin: 10px 0;">';
                $html .= $this->renderPublicHeaderExtraLeft();
                $html .= '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div class="col-lg-6 col-sm-4 col-xs-12" style="text-align: right">';

            // Statut: 
            $status = '';
            $status_prop = $this->getStatusProperty();
            if ($this->field_exists($status_prop)) {
                $status = $this->displayData($status_prop);
            }

            $html .= '<div class="header_status">';
            if ($status) {
                $html .= $status;
            }
            if (method_exists($this, 'renderPublicHeaderStatusExtra')) {
                $html .= $this->renderPublicHeaderStatusExtra();
            }
            $html .= '</div>';

            $html .= '<div class="header_buttons">';

            // Boutons actions: 
            $header_buttons = $this->config->getCompiledParams('public_header_btn');
            if (!empty($header_buttons)) {
                if (isset($header_buttons['buttons_groups'])) {
                    $html .= BimpRender::renderButtonsGroups($header_buttons['buttons_groups'], array(
                                'max'                 => 1,
                                'dropdown_menu_right' => 1
                    ));
                } else {
                    $html .= BimpRender::renderButtonsGroup($header_buttons, array(
                                'max'                 => 6,
                                'dropdown_menu_right' => 1
                    ));
                }
            }

            $html .= '<div style="display: inline-block">';

            // Bouton édition: 
            $form_name = $this->getConf('public_header_edit_form', '');
            if ($form_name && $this->isEditable() && $this->can('edit')) {
                $html .= '<span class="btn btn-primary bs-popover" onclick="' . $this->getJsLoadModalForm($form_name, addslashes("Edition " . $this->getLabel('of_the') . ($ref ? ' ' . $ref : ''))) . '"';
                $html .= BimpRender::renderPopoverData('Editer');
                $html .= '>';
                $html .= BimpRender::renderIcon('fas_edit');
                $html .= '</span>';
            }

            // Bouton suppression: 
            if ((int) $this->params['header_delete_btn'] && $this->isDeletable() && $this->canClientDelete()) {
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
            if (method_exists($this, 'renderPublicHeaderExtraRight')) {
                $html .= '<div style="margin: 10px 0;">';
                $html .= $this->renderPublicHeaderExtraRight();
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

            $url = $this->getPublicListPageUrl();

            if ($url) {
                $html .= '<div class="buttonsContainer align-center">';
                $html .= '<button class="btn btn-large btn-primary" onclick="window.location = \'' . $url . '\'">';
                $html .= BimpRender::renderIcon('fas_list', 'iconLeft') . 'Retour à la Liste des ' . $this->getLabel('name_plur');
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

            if (stripos($search_value, 'prov') === 0)
                $search_value = str_ireplace('prov', '', $search_value);
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
            if ($this->canSetAction('removeChildObject') && $this->field_exists($field)) {
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

                if ($id_object > 0) {
                    if (is_null($instance)) {
                        $instance = $this->config->getObject('fields/' . $field . '/object');
                    }

                    if (is_a($instance, 'BimpObject')) {
                        $msg = BimpTools::ucfirst($instance->getLabel('the')) . ' d\'ID ' . $id_object . ' semble avoir été supprimé' . ($instance->isLabelFemale() ? 'e' : '');
                    } elseif (is_object($instance)) {
                        $msg = 'L\'objet de type "' . get_class($instance) . '" d\'ID ' . $id_object . ' semble avoir été supprimé';
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

        return '';
    }

    public function renderSearchInput($input_name, $value = null, $options = array())
    {
        $html = BimpInput::renderSearchObjectInput($this, $input_name, $value, $options);
        $html .= '<p class="inputHelp">Rechercher ' . $this->getLabel('a') . '</p>';
        return $html;
    }

    public function renderSearchObjectInput($input_name, $object_data)
    {

        if (preg_match('/^(.+)\-(.+)$/', $object_data, $matches)) {
            $module = $matches[1];
            $object_name = $matches[2];

            if ($module && $object_name) {
                $instance = BimpObject::getInstance($module, $object_name);
                if (get_class($instance) != 'BimpObject') {
                    return $instance->renderSearchInput($input_name);
                } else {
                    return BimpRender::renderAlerts('Erreur de configuration: objet "' . $object_data . '" non défini');
                }
            } else {
                return BimpRender::renderAlerts('Erreur de configuration: données objet invalide (' . $object_data . ')');
            }
        }
    }

    public function renderHeaderBtnRedir()
    {
        return $this->processRedirect(false);
    }

    public function renderListCsvColsOptions()
    {
        $list_name = BimpTools::getPostFieldValue('list_name', 'default');
        $list_type = BimpTools::getPostFieldValue('list_type', 'list_table');
        $light_export = BimpTools::getPostFieldValue('light_export', 0);

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

        $rows = $bc_list->getCsvColOptionsInputs($light_export);

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
        if (is_a($objT, 'BimpObject')) {
            $obj = BimpObject::getBimpObjectInstance('bimpcore', 'BimpFile');
            $bc_list = new BC_ListTable($obj, 'default', 1, null, 'Liste des fichiers ' . $objT->getLink(), 'fas_bars');

//            $bc_list->addFieldFilterValue('a.parent_object_name', get_class($objT));
            $bc_list->addFieldFilterValue('a.parent_object_name', $objT->object_name);
            $bc_list->params['add_form_values']['fields']['parent_object_name'] = $objT->object_name;
            $bc_list->addFieldFilterValue('a.parent_module', $objT->module);
            $bc_list->params['add_form_values']['fields']['parent_module'] = $objT->module;
            $bc_list->addFieldFilterValue('a.id_parent', $objT->id);
            $bc_list->params['add_form_values']['fields']['id_parent'] = $objT->id;
            if (!$with_delete)
                $bc_list->addFieldFilterValue('a.deleted', 0);
            $bc_list->identifier .= $objT->object_name . "-" . $objT->id;

            return $bc_list->renderHtml();
        }

        return '';
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
        $html = $this->printData(1);

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
            'title'         => BimpRender::renderIcon('fas_linked', 'iconLeft') . 'Tous les objets liés',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderBimpOjectLinked', '$(\'#' . $idHtml . ' .nav_tab_ajax_result\')', array(), array('button' => ''))
        );

        $idHtml = 'object_divers_linked_text';
        $tabs[] = array(
            'id'            => $idHtml,
            'title'         => 'Tous les types d\'objets liés',
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
            } elseif (BimpTools::getValue('tab') != $nomTabs)
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

    public function renderLinkedObjectsLists($type = 'both')
    {
        if ($this->isLoaded()) {
            self::loadClass('bimpcore', 'BimpLink');
            return BimpLink::renderObjectLinkedObjectsLists($this, $type);
        }

        return '';
    }

    public function renderSuiviMail()
    {
        $instance = BimpObject::getInstance('bimpcore', 'BimpMailLog');
        $list = new BC_ListTable($instance, 'default');
//        $list->addIdentifierSuffix($suffixe);
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

    public function renderLogsList()
    {
        $html = '';

        $errors = array();
        if ($this->isLoaded($errors)) {
            $log = BimpObject::getInstance('bimpcore', 'BimpObjectLog');
            $title = 'Historique ' . $this->getLabel('of_the') . ' ' . $this->getRef();
            $list = new BC_ListTable($log, 'object', 1, null, $title, 'fas_history');
            $list->addFieldFilterValue('obj_module', $this->module);
            $list->addFieldFilterValue('obj_name', $this->object_name);
            $list->addFieldFilterValue('id_object', $this->id);

            $html .= $list->renderHtml();
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors, 'danger');
        }

        return $html;
    }

    public function renderImages($full_panel = false, $filters = null)
    {
        $html = '';

        if (is_null($filters)) {
            $files = BimpCache::getBimpObjectObjects('bimpcore', 'BimpFile', array(
                        'parent_module'      => $this->module,
                        'parent_object_name' => $this->object_name,
                        'id_parent'          => $this->id,
                        'file_ext'           => array(
                            'in' => array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif')
                        )
            ));
        } else {
            $files = BimpCache::getBimpObjectObjects('bimpcore', 'BimpFile', $filters);
        }


        if (!empty($files)) {
            $html .= '<div>';

            foreach ($files as $file) {
                $url = $file->getUrl();

                if ($url) {
                    $onclick = 'loadImageModal($(this), \'' . $url . '\', \'' . $file->getData('file_name') . '\')';
                    $html .= '<div onclick="' . $onclick . '"';
                    $html .= ' style="display: inline-block; max-height: 200px; max-width: 300px; width: auto; height: auto;';
                    $html .= ' cursor: zoom-in; margin: 5px; padding: 5px; border: 1px solid #ccc; vertical-align: top; overflow: hidden">';
                    $html .= '<div style="font-size: 11px; font-style: italic">';
                    $html .= '<b>' . $file->getData('file_name') . '</b>';
                    $html .= '</div>';
                    $html .= '<img src="' . $url . '" style="max-height: 92%; max-width: 100%; height: auto; width: auto; margin: auto"/>';
                    $html .= '</div>';
                }
            }

            $html .= '</div>';
        }

        if ($full_panel) {
            $title = BimpRender::renderIcon('fas_file-image', 'iconLeft') . 'Images liées';
            return BimpRender::renderPanel($title, $html, '', array(
                        'type' => 'secondary'
            ));
        }
        return $html;
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

    public function getJsLoadModalForm($form_name = 'default', $title = '', $values = array(), $success_callback = '', $on_save = '', $force_edit = 0, $button = '$(this)', $on_save_success_callback = 'null')
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

        $js = 'loadModalForm(' . $button . ', ' . htmlentities($data) . ', \'' . htmlentities($title) . '\', \'' . htmlentities($success_callback) . '\', \'' . $on_save . '\', null, ' . $on_save_success_callback . ')';
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
        $js .= '\'' . htmlentities($title) . '\', ';
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
            if (!empty($method_params)) {
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
        $options = '';

        if (isset($params['form_name']) && $params['form_name']) {
            $options .= 'form_name: \'' . $params['form_name'] . '\'';
        }

        if (isset($params['confirm_msg']) && $params['confirm_msg']) {
            $options .= ($options ? ', ' : '') . 'confirm_msg: \'' . $params['confirm_msg'] . '\'';
        }

        if (isset($params['modal_title']) && $params['modal_title']) {
            $options .= ($options ? ', ' : '') . 'modal_title: \'' . $params['modal_title'] . '\'';
        }

        if (isset($params['modal_format']) && $params['modal_format']) {
            $options .= ($options ? ', ' : '') . 'modal_format: \'' . $params['modal_format'] . '\'';
        }

        if (isset($params['on_form_submit']) && $params['on_form_submit']) {
            $options .= ($options ? ', ' : '') . 'on_form_submit: ' . $params['on_form_submit'];
        }

        if (isset($params['no_triggers'])) {
            $options .= ($options ? ', ' : '') . 'no_triggers: ' . ((int) $params['no_triggers'] ? 1 : 0);
        }

        if (isset($params['modal_scroll_bottom']) && $params['modal_scroll_bottom']) {
            $options .= ($options ? ', ' : '') . 'modal_scroll_bottom: ' . ((int) $params['modal_scroll_bottom'] ? 1 : 0);
        }

        if (isset($params['use_bimpdatasync'])) {
            $options .= ($options ? ', ' : '') . 'use_bimpdatasync: ' . ((int) $params['use_bimpdatasync'] ? 1 : 0);
        }

        if (isset($params['use_report'])) {
            $options .= ($options ? ', ' : '') . 'use_report: ' . ((int) $params['use_report'] ? 1 : 0);
        }

        if (isset($params['display_processing'])) {
            $options .= ($options ? ', ' : '') . 'display_processing: ' . ((int) $params['display_processing'] ? 1 : 0);
        }

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
            $js .= $key . ': ' . (BimpTools::isNumericType($value) ? $value : (is_array($value) ? htmlentities(json_encode($value)) : '\'' . $value . '\''));
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
        $js .= '{' . $options . '}';
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
        $js .= ', ';
        if (isset($params['on_form_submit'])) {
            $js .= $params['on_form_submit'];
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
        if (isset($params['result_container'])) {
            $js .= $params['result_container'];
        } else {
            $js .= 'null';
        }
        $js .= ', {';
        $fl = true;
        foreach ($params as $key => $value) {
            if (!in_array($key, array('form_name', 'confirm_msg', 'single_action', 'on_form_submit', 'success_callback', 'result_container'))) {
                if (!$fl) {
                    $js .= ', ';
                } else {
                    $fl = false;
                }
                $js .= $key . ': ' . (BimpTools::isNumericType($value) ? $value : '\'' . $value . '\'');
            }
        }
        $js .= '}';
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

    public function getJsTriggerParentChange()
    {
        $parent = $this->getParentInstance();

        $js = '';

        if (is_a($parent, 'BimpObject')) {
            $js = 'triggerObjectChange(\'' . $parent->module . '\', \'' . $parent->object_name . '\'';

            if ($parent->isLoaded()) {
                $js .= ', ' . $parent->id . ')';
            }
        }

        return $js;
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
            if (preg_match('/^[aàâäeéèêëiîïoôöuùûüyŷÿ](.*)$/', strtolower($object_name))) {
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

    public function getLabel($type = '', $ucfirst = false)
    {
        $label = '';
        if (isset($this->params['labels']['name'])) {
            $labels = $this->params['labels'];

            $object_name = $labels['name'];

            $vowel_first = false;
            if (preg_match('/^[aàâäeéèêëiîïoôöuùûüyŷÿ](.*)$/', strtolower($object_name))) {
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
            default:
                $label = $object_name;
                break;

            case 'name_plur':
                $label = $name_plur;
                break;

            case 'the':
                if ($vowel_first) {
                    $label = 'l\'' . $object_name;
                } elseif ($isFemale) {
                    $label = 'la ' . $object_name;
                } else {
                    $label = 'le ' . $object_name;
                }
                break;

            case 'a':
                if ($isFemale) {
                    $label = 'une ' . $object_name;
                } else {
                    $label = 'un ' . $object_name;
                }
                break;

            case 'to':
                if ($vowel_first) {
                    $label = 'à l\'' . $object_name;
                } elseif ($isFemale) {
                    $label = 'à la ' . $object_name;
                } else {
                    $label = 'au ' . $object_name;
                }
                break;

            case 'this':
                if ($isFemale) {
                    $label = 'cette ' . $object_name;
                } elseif ($vowel_first) {
                    $label = 'cet ' . $object_name;
                } else {
                    $label = 'ce ' . $object_name;
                }
                break;

            case 'of_a':
                if ($isFemale) {
                    $label = 'd\'une ' . $object_name;
                } else {
                    $label = 'd\'un ' . $object_name;
                }
                break;

            case 'of_the':
                if ($vowel_first) {
                    $label = 'de l\'' . $object_name;
                } elseif ($isFemale) {
                    $label = 'de la ' . $object_name;
                } else {
                    $label = 'du ' . $object_name;
                }
                break;

            case 'of_this':
                if ($isFemale) {
                    $label = 'de cette ' . $object_name;
                } elseif ($vowel_first) {
                    $label = 'de cet ' . $object_name;
                } else {
                    $label = 'de ce ' . $object_name;
                }
                break;

            case 'the_plur':
                $label = 'les ' . $name_plur;
                break;

            case 'of_those':
                $label = 'de ces ' . $name_plur;
                break;

            case 'of_plur':
                if ($vowel_first) {
                    $label = 'd\'' . $name_plur;
                } else {
                    $label = 'de ' . $name_plur;
                }
                break;

            case 'all_the':
                if ($isFemale) {
                    $label = 'toutes les ' . $name_plur;
                } else {
                    $label = 'tous les ' . $name_plur;
                }
                break;
        }

        return ($ucfirst ? ucfirst($label) : $label);
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

    public static function getInstanceNom($instance, $with_generic = true)
    {
        if (is_a($instance, 'BimpObject')) {
            return $instance->getName($with_generic);
        } elseif (is_a($instance, 'User')) {
            return $instance->lastname . ' ' . $instance->firstname;
        } elseif (is_a($instance, 'ActionComm')) {
            return 'Evénement' . (BimpObject::objectLoaded($instance) ? ' #' . $instance->id : '');
        }

        foreach (self::$name_properties as $name_prop) {
            if (isset($instance->{$name_prop})) {
                return $instance->{$name_prop};
            }
        }

        if ($with_generic && isset($instance->id)) {
            return 'Objet "' . get_class($instance) . '" #' . $instance->id;
        }

        return '';
    }

    public static function getInstanceRef($instance, $with_generic = true)
    {
        if (is_a($instance, 'BimpObject')) {
            return $instance->getRef($with_generic);
        }

        foreach (self::$ref_properties as $ref_prop) {
            if (isset($instance->{$ref_prop})) {
                return $instance->{$ref_prop};
            }
        }

        if ($with_generic && isset($instance->id)) {
            return '#' . $instance->id;
        }

        return '';
    }

    // Liens et url: 

    public function getUrl($forced_context = '')
    {
        if ($forced_context == 'public' || (!$forced_context && BimpCore::isContextPublic())) {
            return $this->getPublicUrl();
        }

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

    public static function getPublicBaseUrl($internal = true, $entity = '')
    {
        if ($internal) {
            $url = BimpCore::getConf('public_base_url', '');

            if (!$url) {
                $url = DOL_URL_ROOT . '/bimpinterfaceclient/client.php?';
            }
        } else {
            $url = BimpCore::getConf('base_url', '', 'bimpinterfaceclient');
        }


        if (preg_match('/^.*\.php$/', $url)) {
            $url .= '?';
        }

        if ($entity) {
            $url .= 'e=' . $entity . '&';
        }

        return $url;
    }

    public function getPublicUrl($internal = true, $entity = '')
    {
        if ($this->isLoaded()) {
            $params = $this->getPublicUrlParams();

            if ($params) {
                $base = self::getPublicBaseUrl($internal, $entity);

                if ($base) {
                    return $base . $params;
                }
            }
        }

        return '';
    }

    public function getPublicUrlParams()
    {
        return '';
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

    public function getLink($params = array(), $forced_context = '')
    {
        // $params peut éventuellement être utilisé pour surcharger les paramères "nom_url" de l'objet. 

        if ($forced_context) {
            $is_public = ($forced_context == 'public');
        } else {
            $is_public = BimpCore::isContextPublic();
        }

        if (!$this->isLoaded()) {
            return '';
        }


        if (!$this->isActif()) {
            $params['disabled'] = true;
        }

        $html = '';
        $html .= '<span class="objectLink">';

        if (is_array($params)) {
            $params = BimpTools::overrideArray($this->params['nom_url'], $params, true, true);
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

        if ($label === '<ref> - <name>' && !$ref_prop) {
            $label = '<name>';
        }

        $n = 0;
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

            $n++;
            if ($n > 10) {
                break;
            }
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

        global $no_bimp_object_link_cards;

        if (!$no_bimp_object_link_cards && !(int) BimpCore::getConf('mode_eco')) {
            $no_bimp_object_link_cards = true;
            $card_name = '';

            if ($is_public) {
                if ($this->config->isDefined('cards/public')) {
                    $card_name = 'public';
                }
            } else {
                $card_name = BimpTools::getArrayValueFromPath($params, 'card', '');
            }

            if ($card_name) {
                $card_html = BimpCache::getBimpObjectCardHtml($this, $card_name, false);
            }
            $no_bimp_object_link_cards = false;
        }

        if (isset($params['disabled']) && $params['disabled']) {
            $label = '<strike>' . $label . '</strike>';
        }

        if ($is_public) {
            $url = $this->getPublicUrl();
        } else {
            $url = $this->getUrl('private');
        }

        if (isset($params['after_link']))
            $url .= $params['after_link'];

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
            $html .= $icon . $label;
            $html .= '</span>';
            $html .= $status;
        } else {
            $html .= $icon . $label . $status;
        }

        if (!$is_public) {
            $external_link = (isset($params['external_link']) ? (int) $params['external_link'] : 1);
            $modal_view = (isset($params['modal_view']) ? $params['modal_view'] : 'default');

            if (($url && $external_link) || $modal_view) {
                $html .= BimpRender::renderObjectIcons($this, $external_link, $modal_view, $url);
            }
        }

        if ($card_html) {
            $html .= '<span class="objectIcon cardPopoverIcon">';
            $html .= BimpRender::renderIcon('fas_sticky-note');
            $html .= '</span>';
        }

        if (!$is_public && method_exists($this, 'getNomUrlExtra')) {
            $html .= $this->getNomUrlExtra();
        }

        $html .= '</span>';
        return $html;
    }

    public function getListUrl()
    {
        return $this->getListPageUrl();
    }

    public function getListPageUrl($list_name = 'default')
    {
        if (BimpCore::isContextPublic()) {
            return $this->getPublicListPageUrl();
        }

        if (is_string($this->params['list_page_url'])) {
            if ($this->params['list_page_url'] === 'list') {
                return DOL_URL_ROOT . '/bimpcore/index.php?fc=list&module=' . $this->module . '&object_name=' . $this->object_name . '&list_name=' . $list_name;
            }

            return '';
        }

        $url = false;
        if ($this->config->isDefined('list_page_url'))
            $url = BimpTools::makeUrlFromConfig($this->config, 'list_page_url', $this->module, $this->getController());

        if (!$url && $this->isDolObject()) {
            $url = BimpTools::getDolObjectListUrl($this->dol_object);
        }

        return $url;
    }

    public function getPublicListPageUrl($internal = true)
    {
        if ($this->isLoaded()) {
            $params = $this->getPublicListPageUrlParams();

            if ($params) {
                $base = self::getPublicBaseUrl($internal);

                if ($base) {
                    return $base . $params;
                }
            }
        }

        return '';
    }

    public function getPublicListPageUrlParams()
    {
        return '';
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
            $html .= $instance->getNomUrl($with_icon);

            if (BimpCore::isContextPublic()) {
                $html = strip_tags($html);
            } else {
                $external_link = (isset($params['external_link']) ? (int) $params['external_link'] : 1);

                if ($external_link) {
                    $url = self::getInstanceUrl($instance);
                    if ($url) {
                        $html .= BimpRender::renderObjectIcons($instance, true, null, $url);
                    }
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
                self::setCache($cache_key, $this->dol_object->liste_type_contact('internal'));
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
                self::setCache($cache_key, $this->dol_object->liste_type_contact('external'));
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

    public function getFullLinkedObjetsArray($include_empty = false)
    {
        return self::getObjectFullLinkedObjetsArray($this, $include_empty);
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
                $module = '';
                $object_name = '';
                $obj_data = isset($data['move_to_object_name']) ? $data['move_to_object_name'] : '';
                $id_object = (int) isset($data['move_to_id_object']) ? $data['move_to_id_object'] : 0;

                if (!$obj_data) {
                    $errors[] = 'Type de destinataire absent';
                } elseif (preg_match('/^(.+)\-(.+)$/', $obj_data, $matches)) {
                    $module = $matches[1];
                    $object_name = $matches[2];
                }

                if (!$module || !$object_name) {
                    $errors[] = 'Données du destinataire invalide';
                }

                if (!$id_object) {
                    $errors[] = 'ID du destinataire absent';
                }

                if (!count($errors)) {
                    $moveTo = BimpCache::getBimpObjectInstance($module, $object_name, $id_object);
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
            $light_export = BimpTools::getArrayValueFromPath($data, 'light_export', 0);

            $list_data['param_n'] = 0;
            $list_data['param_p'] = 1;

            global $conf;
            $dir = $conf->bimpcore->multidir_output[$conf->entity];
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

                $content = $list->renderCsvContent($separator, $col_options, $headers, $light_export, $errors);

                if ($content && !count($errors)) {
                    if (!file_put_contents($dir . '/' . $file_name . '.csv', $content)) {
                        $errors[] = 'Echec de la création du fichier "' . $file_name . '"';
                    } else {
                        if (!file_exists($dir)) {
                            $errors[] = 'Echec de la création du fichier "' . $file_name . '"';
                        } else {
                            $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . urlencode('lists_csv/' . $this->module . '/' . $this->object_name . '/' . $list_name . '/' . $file_name . '.csv');
                            $success_callback = 'window.open(\'' . $url . '\')';
                        }
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
        $success = "";
        $errors = array();
        $warnings = array();

        $list_name = (isset($data['list_name']) ? $data['list_name'] : '');
        $list_data = (isset($data['list_data']) ? $data['list_data'] : array());
        $post_temp = $_POST;
        $_POST = $list_data;

        $list = new BC_ListTable($this, $list_name);
        if ($dataGraphe['mode_data'] == 'objects' && method_exists($this, 'getGraphDataPoint'))
            $list->initForGraph();
        $nameGraph = $list->getParam('graph')[$data['idGraph']];
        $dataGraphe = $this->getInfoGraph($nameGraph);

        $options = array();
        $options['animationEnabled'] = true;
        $options['theme'] = "light2";
        $options['title'] = array("text" => $dataGraphe['title']);
        $options['axisX'] = $dataGraphe['axeX'];
        $options['axisY'] = $dataGraphe['axeY'];
        $options['toolTip'] = array("shared" => true);
        $options['legend'] = array(
            "cursor"             => "pointer",
            "verticalAlign"      => "top",
            "horizontalAlign"    => "left",
            "dockInsidePlotArea" => false,
            "itemclick"          => "toogleDataSeries",
        );
        $i = 1;
        if ($dataGraphe['mode_data'] == 'unique' && method_exists($this, 'getGraphDatasPoints')) {//On apelle une seul methode pour tous les points
            $tmpDatas = $this->getGraphDatasPoints($dataGraphe['params']);
        }
        while (isset($dataGraphe['data' . $i])) {
            $tmpData = array();
            $tmpData["type"] = "line";
            $tmpData["showInLegend"] = true;
            $tmpData["markerType"] = "square";

            $tmpData = BimpTools::overrideArray($tmpData, $dataGraphe['data' . $i]);

            if (isset($dataGraphe['axeX']['valueFormatString']))
                $tmpData['xValueFormatString'] = $dataGraphe['axeX']['valueFormatString'];
            if (isset($dataGraphe['axeY']['valueFormatString']))
                $tmpData['yValueFormatString'] = $dataGraphe['axeY']['valueFormatString'];

            $list_id = (isset($data['list_id']) ? $data['list_id'] : '');
            if ($dataGraphe['mode_data'] == 'objects' && method_exists($this, 'getGraphDataPoint')) {//il faut charger chaque objet pour avoir ca valeur
                $tmpData['dataPoints'] = $list->getPointsForGraph($dataGraphe['params'], $i);
            } elseif ($dataGraphe['mode_data'] == 'unique' && isset($tmpDatas[$i])) {//On apelle une seul methode pour tous les points
                $tmpData['dataPoints'] = $tmpDatas[$i];
            } else {
                $errors[] = 'Aucune methode pour charger les points ' . $dataGraphe['mode_data'];
            }
            $options['data'][] = $tmpData;
            $i++;
        }

        $success_callback = 'var options = ' . json_encode($options) . ';';
        $success_callback = str_replace('"new Date', 'new Date', $success_callback);
        $success_callback = str_replace('","y"', ',"y"', $success_callback);
        $success_callback = str_replace('"toogleDataSeries"', 'toogleDataSeries', $success_callback);

        $success_callback .= '$("#' . $list_id . '_' . $data['idGraph'] . '_chartContainer").CanvasJSChart(options);';

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

    public function actionEraseCache($data, &$success)
    {
        $success = 'Cache vidé';
        $errors = $warnings = array();
        if (BimpCache::$cache_server)
            BimpCache::eraseCacheServer();
        else
            $errors[] = 'Pas de cache serveur';
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
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
                                $not_validate = BimpTools::getArrayValueFromPath($data, 'validate', false);

                                $obj_warnings = array();
                                $obj_errors = array();

                                switch ($mode) {
                                    case 'update_field':
                                        $obj_errors = $instance->updateField($field_name, $value, null, $force_update, $not_validate);
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

    public function actionGenerateBulkPdf($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Fichier généré avec succès';
        $success_callback = '';

        $id_objs = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

        if (count($id_objs) > 70) {
            $errors[] = 'Trop de PDF action impossible';
            return array(
                'errors'   => $errors,
                'warnings' => $warnings
            );
        }

        if (!is_array($id_objs) || empty($id_objs)) {
            $errors[] = 'Aucune ' . $this->getLabel() . ' sélectionnée';
        } else {
            $files = array();

            foreach ($id_objs as $id_obj) {
                $obj = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $id_obj);

                if (!BimpObject::objectLoaded($obj)) {
                    $warnings[] = ucfirst($this->getLabel('the')) . ' d\'ID ' . $id_obj . ' n\'existe pas';
                    continue;
                }

                $dir = $obj->getFilesDir();
                $filename = $obj->getPdfNamePrincipal();

                if (!file_exists($dir . $filename)) {
                    $warnings[] = ucfirst($this->getLabel()) . ' ' . $obj->getLink() . ': fichier PDF absent (' . $dir . $filename . ')';
                    continue;
                }

                $files[] = $dir . $filename;
            }

            if (!empty($files)) {
                global $user;
                require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpPDF.php';
                $fileName = 'bulk_' . $this->dol_object->element . '_' . $user->id . '.pdf';
                $dir = PATH_TMP . '/bimpcore/';

                $pdf = new BimpConcatPdf();
                $pdf->concatFiles($dir . $fileName, $files, 'F');

                $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . urlencode($fileName);
                $success_callback = 'window.open(\'' . $url . '\');';
            } else {
                $errors[] = 'Aucun PDF trouvé';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionGenerateZipPdf($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Fichier généré avec succès';
        $success_callback = '';

        $id_objs = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

        if (count($id_objs) > 50) {
            $errors[] = 'Trop de PDF action impossible';
        } else {
            if (!is_array($id_objs) || empty($id_objs)) {
                $errors[] = 'Aucune ' . $this->getLabel() . ' sélectionnée';
            } else {
                $files = array();

                foreach ($id_objs as $id_obj) {
                    $obj = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $id_obj);

                    if (!BimpObject::objectLoaded($obj)) {
                        $warnings[] = ucfirst($this->getLabel('the')) . ' d\'ID ' . $id_obj . ' n\'existe pas';
                        continue;
                    }

                    $dir = $obj->getFilesDir();
                    $filename = $obj->getPdfNamePrincipal();

                    if (!file_exists($dir . $filename)) {
                        $warnings[] = ucfirst($this->getLabel()) . ' ' . $obj->getLink() . ': fichier PDF absent (' . $dir . $filename . ')';
                        continue;
                    }

                    $files[] = array($dir . $filename, $filename);
                }

                if (!empty($files)) {
                    global $user;
                    $dir = PATH_TMP . '/bimpcore/';
                    $fileName = 'zip_' . $this->dol_object->element . '_' . $user->id . '.zip';
                    if (file_exists($dir . $fileName))
                        unlink($dir . $fileName);
                    $zip = new ZipArchive();
                    if ($zip->open($dir . $fileName, ZipArchive::CREATE) === true) {
                        foreach ($files as $tabFile) {
                            $zip->addFile($tabFile[0], $tabFile[1]);
                        }
                    }
                    $zip->close();

                    $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . urlencode($fileName);
                    $success_callback = 'window.open(\'' . $url . '\');';
                } else {
                    $errors[] = 'Aucun PDF trouvé';
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionAddFiles($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $files = BimpTools::getArrayValueFromPath($data, 'files', array());

        if (empty($files)) {
            $errors[] = 'Aucun fichier ajouté';
        } else {
            $files_dir = $this->getFilesDir();
            BimpTools::moveTmpFiles($warnings, $files, $files_dir);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Gestion statique des objets:

    public static function createBimpObject($module, $object_name, $data, $force_create = false, &$errors = array(), &$warnings = array(), $no_transactions_db = false, $no_html = false)
    {
        $instance = static::getInstance($module, $object_name);

        if (is_a($instance, 'BimpObject')) {
            if ($no_transactions_db) {
                $instance->useNoTransactionsDb();
            }

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
                $errors[] = BimpTools::getMsgFromArray($create_errors, 'Echec de la création ' . $label, $no_html);
            }

            if (count($create_warnings)) {
                $warnings[] = BimpTools::getMsgFromArray($create_warnings, 'Erreurs suite à la création ' . $label, $no_html);
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
        $config = BimpConfig::getObjectConfigInstance($module, $object_name);
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

                    foreach ($instance->params['objects'] as $obj_conf_name) {
                        $obj_params = $instance->config->getParams('objects/' . $obj_conf_name);
                        if ($obj_params['relation'] !== 'hasOne') {
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

                        if ($obj_name && $obj_module && is_string($obj_name) && is_string($obj_module)) {//C'est bien un bimp object
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
                                    if ($instance->field_exists($field)) {
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

    // Gestion des hashtags: 

    public static function getHahstagsObjectTypeSearchChoices(&$search, &$new_kw = '')
    {
        $choices = array();

        $search = strtolower($search);

        foreach (ObjectsDef::$keywords as $kw => $defs) {
            if (preg_match('/^(.*)(' . preg_quote($search, '/') . ')(.*)$/', strtolower($kw), $matches)) {
                $icon = '';

                if (isset($defs['icon']) && $defs['icon']) {
                    $icon = BimpRender::renderIcon($defs['icon'], 'iconLeft');
                }

                $choices[$kw] = array(
                    'label' => $icon . $matches[1] . '<b>' . $matches[2] . '</b>' . $matches[3],
                    'data'  => array(
                        'obj_kw' => $kw
                    )
                );
            }
        }

        foreach (ObjectsDef::$aliases as $alias => $kw) {
            if (preg_match('/^(.*)(' . preg_quote($search, '/') . ')(.*)$/', strtolower($alias), $matches)) {
                if (isset($choices[$kw])) {
                    if (preg_match('/^(.*) \(Alias: (.*)\)(.*)$/', $choices[$kw]['label'], $matches2)) {
                        $choices[$kw]['label'] = $matches2[1] . ' (Alias: ' . $matches2[2] . ' ' . $matches[1] . '<b>' . $matches[2] . '</b>' . $matches[3] . ')' . $matches2[3];
                    } else {
                        $choices[$kw]['label'] .= ' (Alias: ' . $matches[1] . '<b>' . $matches[2] . '</b>' . $matches[3] . ')';
                    }
                } else {
                    $icon = '';

                    if (isset(ObjectsDef::$keywords[$kw]['icon']) && ObjectsDef::$keywords[$kw]['icon']) {
                        $icon = BimpRender::renderIcon(ObjectsDef::$keywords[$kw]['icon'], 'iconLeft');
                    }

                    $choices[$kw] = array(
                        'label' => $icon . $kw . ' (Alias: ' . $matches[1] . '<b>' . $matches[2] . '</b>' . $matches[3] . ')',
                        'data'  => array(
                            'obj_kw' => $kw
                        )
                    );
                }
            }
        }

        if (empty($choices)) {
            // On recherche par début de référence:
            foreach (ObjectsDef::$refs_prefixes as $prefixe => $kw) {
                if (preg_match('/^' . preg_quote($prefixe, '/') . '(.*)$/i', $search, $matches3)) {
                    $new_kw = $kw;
                    return array();
                }
            }
        }

        if (!empty($choices)) {
            $choices_tmp = $choices;

            $choices = array();

            foreach ($choices_tmp as $choice) {
                $choices[] = $choice;
            }
        }

        return $choices;
    }

    public static function getHastagsObjectSearchChoices($keyword, $search)
    {
        $choices = array();

        if (isset(ObjectsDef::$keywords[$keyword])) {
            $def = explode('/', ObjectsDef::$keywords[$keyword]['def']);

            switch ($def[0]) {
                case 'BO':
                    $module = (isset($def[1]) ? $def[1] : '');
                    $object_name = (isset($def[2]) ? $def[2] : '');

                    if ($module && $object_name) {
                        $instance = BimpObject::getInstance($module, $object_name);

                        $card = BimpTools::getArrayValueFromPath($instance->params, 'nom_url/card', '');

                        $results = $instance->getSearchResults('all', $search, array(
                            'card'        => $card,
                            'max_results' => 30,
                            'active'      => 1
                        ));

                        foreach ($results as $id_object => $data) {
                            $label = $data['label'];

                            $words = explode(' ', $search);

                            foreach ($words as $word) {
                                if (preg_match('/^(.*)(' . preg_quote($word, '/') . ')(.*)$/i', $label, $matches)) {
                                    $label = $matches[1] . '<b>' . $matches[2] . '</b>' . $matches[3];
                                }
                            }

                            $choices[] = array(
                                'label' => $label,
                                'value' => $keyword . ':' . $id_object,
                                'card'  => $data['card']
                            );
                        }
                    }
                    break;

                case 'DO':
                    $module = (isset($def[1]) ? $def[1] : '');
                    $file = (isset($def[2]) ? $def[2] : '');
                    $class_name = (isset($def[3]) ? $def[3] : '');

                    if ($module) {
                        if (!$file) {
                            $file = $module;
                        }

                        if (!$class_name) {
                            $class_name = ucfirst($file);
                        }

                        BimpTools::loadDolClass($module, $file, $class_name);

                        if (class_exists($class_name)) {
                            $instance = new $class_name($this->db->db);
                        }

                        // todo
                    }

                    break;
            }
        }

        return $choices;
    }

    public static function replaceHastags($text, $no_html = false)
    {
        if (is_string($text)) {
            if (preg_match_all('/\{\{(.+):([0-9]+)\}\}/U', $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $obj_kw = (string) $match[1];
                    $id = (int) $match[2];

                    if ($obj_kw && $id && isset(ObjectsDef::$keywords[$obj_kw]['def'])) {
                        $defs = explode('/', ObjectsDef::$keywords[$obj_kw]['def']);

                        switch ($defs[0]) {
                            case 'BO':
                                $module = $defs[1];
                                $object_name = $defs[2];

                                $instance = BimpCache::getBimpObjectInstance($module, $object_name, $id);

                                $label = '';

                                if (BimpObject::objectLoaded($instance)) {
                                    if ($no_html) {
                                        $label = $instance->display('ref_nom');
                                    } else {
                                        $label = $instance->getLink();
                                    }
                                } else {
                                    $instance = BimpObject::getInstance($module, $object_name);

                                    $label = '';
                                    if (!$no_html) {
                                        $label .= '<span class="danger">';
                                    }

                                    $label .= $instance->getLabel('the') . ' #' . $id . ' n\'existe plus';

                                    if (!$no_html) {
                                        $label .= '</span>';
                                    }
                                }

                                $text = str_replace($match[0], $label, $text);
                                break;

                            case 'DO':
                                // todo
                                break;
                        }
                    }
                }
            }
        }

        return $text;
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
        $btn = false;
        if ($this->iAmAdminRedirect()) {
            $btn = true;
            if ($redirectMode == 4){//auto old vers new
                $texteBtn = "ADMIN (Normalement nouvelle) : ";
                if($newVersion){//on est sur l'ancienne
                    if ($redirect)
                        unset($_SESSION['oldVersion']);
                    elseif(!isset($_SESSION['oldVersion']))
                        $redirect = true;
                }
                else{//on est deja sur la nouvelle mais si $_SESSION['oldVersion']
                    if ($redirect)
                        $_SESSION['oldVersion'] = true;
                    elseif(isset($_SESSION['oldVersion']))
                        $redirect = true;
                }
            }
            if($redirectMode == 5){//auto new vers old
                $texteBtn = "ADMIN (Normalement Ancienne) : ";
                if(!$newVersion){//on est sur la nouvelle
                    if ($redirect)
                        unset($_SESSION['newVersion']);
                    if(!isset($_SESSION['newVersion']))
                        $redirect = true;
                }
                else{//on est deja sur l'ancienne mais si $_SESSION['newVersion']
                    if ($redirect)
                        $_SESSION['newVersion'] = true;
                    elseif(isset($_SESSION['newVersion']))
                        $redirect = true;
                }
            }
        }
        else{
            if($redirectMode == 4 && $newVersion)
                $redirect = true;
            elseif($redirectMode == 5 && !$newVersion)
                $redirect = true;
        }
        
        if($redirectMode == 1 ||
                ($redirectMode == 2 && $newVersion) ||
                ($redirectMode == 3 && !$newVersion))
            $btn = true;
        
        
        
        if ($newVersion) {
            if ($this->id > 0) {
                if ($this->getConf('controller', null))
                    $url = $this->getUrl();
            } elseif ($this->getConf('list_page_url', null))
                $url = $this->getListUrl();
            $texteBtn .= "Nouvelle version";

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
            if (BimpTools::getValue("mainmenu") != "") {
                $url .= "&mainmenu=" . BimpTools::getValue("mainmenu");
            }
            if (BimpTools::getValue("leftmenu") != "") {
                $url .= "&leftmenu=" . BimpTools::getValue("leftmenu");
            }
        } else {
            $url = BimpTools::getDolObjectUrl($this->dol_object, $this->id);
            $texteBtn .= "Ancienne version";
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
        return 0;
    }

    public static function priceToCsv($price)
    {
        return str_replace(array(" ", 'EUR', '€'), "", str_replace(".", ",", $price));
    }

    public static function getListExtrafield($name, $type, $withVide = true)
    {
        $return = array();
        $entitys = getEntity($type, 0);
        $cash_key = 'extra_list_' . $name . '_' . $type . '_' . (int) $withVide;
        if (!static::cacheServerExists($cash_key)) {
            $query = "SELECT * FROM `" . MAIN_DB_PREFIX . "extrafields` WHERE `name` LIKE '" . $name . "' AND `elementtype` = '" . $type . "'";
            if ($entitys)
                $query .= ' AND entity IN (0,' . $entitys . ')';
            $sql = self::getBdb()->db->query($query);
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

            static::setCacheServeur($cash_key, $return);
        }
        return static::getCacheServeur($cash_key);
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

    public function useEntrepot()
    {
        return (int) BimpCore::getConf("USE_ENTREPOT");
    }

    public function getDefaultCodeCentre()
    {
        if (BimpTools::isSubmit('code_centre')) {
            return BimpTools::getValue('code_centre');
        } else {
            global $user;
            $userCentres = explode(' ', $user->array_options['options_apple_centre']);
            foreach ($userCentres as $code) {
                if (preg_match('/^ ?([A-Z]+) ?$/', $code, $matches)) {
                    return $matches[1];
                }
            }

            $id_entrepot = (int) $this->getData('id_entrepot');
            if (!$id_entrepot) {
                $id_entrepot = BimpTools::getValue('id_entrepot', 0);
            }
            if ($id_entrepot) {
                global $tabCentre;
                foreach ($tabCentre as $code_centre => $centre) {
                    if ((int) $centre[8] === $id_entrepot) {
                        return $code_centre;
                    }
                }
            }
        }

        return '';
    }
}
