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
    public static $numeric_types = array('id', 'id_parent', 'id_object', 'int', 'float', 'money', 'percent');
    public $use_commom_fields = false;
    public $use_positions = false;
    public $params_defs = array(
        'table'                    => array('default' => ''),
        'controller'               => array('default' => ''),
        'icon'                     => array('default' => ''),
        'primary'                  => array('default' => 'id'),
        'common_fields'            => array('data_type' => 'bool', 'default' => 1),
        'header_list_name'         => array('default' => 'default'),
        'header_btn'               => array('data_type' => 'array', 'default' => array()),
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
        'cards'                    => array('type' => 'keys')
    );
    public $params = array();
    protected $data = array();
    protected $associations = array();
    protected $history = array();
    public $parent = null;
    public $dol_object = null;
    public $children = array();
    public $extends = array();

    public function getExport($niveau = 10, $pref = "", $format = "xml", $sep = ";", $sautLn = "\n")
    {
        if (!$this->isLoaded())
            return "Objet non loadé";


        $tabResult = array();
        foreach ($this->config->params['fields'] as $nom => $info) {
            $value = $this->getData($nom);

            if ($info['type'] == "int") {
                $value = intval($value);
                if (is_array($info['values']['array']) && isset($info['values']['array'][$value]))
                    $value = $info['values']['array'][$value];
            }
            elseif ($info['type'] == "id_object") {
                continue; //Car on les retrouve enssuite de nouveau dans $this->params['objects']
                $obj = $this->getChildObject($info['object']);
                $value = $this->recursiveGetExport($niveau, $pref, $obj);
            } elseif ($info['type'] == "bool")
                $value = ($value ? "OUI" : "NON");


            $tabResult[$nom] = $value;
        }

        foreach ($this->params['objects'] as $nom => $infoObj) {
            $value = "";
            if ($infoObj['relation'] == "hasMany") {
                $html.= "<" . $nom . ">";
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

    function recursiveGetExport($niveau, $pref, $obj)
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
    }

    public function isDolObject()
    {
        return !is_null($this->dol_object);
    }

    protected function addCommonFieldsConfig()
    {
        $this->config->params['fields']['id'] = array(
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
        return $this->params['primary'];
    }

    public function getTable()
    {
        return $this->params['table'];
    }

    public function getController()
    {
        return $this->params['controller'];
    }

    public function getJsObjectData()
    {
        $js = '{';
        $js .= 'module: \'' . $this->module . '\'';
        $js .= ', object_name: \'' . $this->object_name . '\'';
        $js .= ', id_object: \'' . ($this->isLoaded() ? $this->id : 0) . '\'';
        $js .= '}';

        return $js;
    }

    public function getJsLoadModalForm($form_name = 'default', $title = '', $values = array(), $success_callback = '', $on_save = '')
    {
        $data = '{';
        $data .= 'module: "' . $this->module . '", ';
        $data .= 'object_name: "' . $this->object_name . '", ';
        $data .= 'id_object: "' . ($this->isLoaded() ? $this->id : 0) . '", ';
        $data .= 'id_parent: "' . (int) $this->getParentId() . '", ';
        $data .= 'form_name: "' . $form_name . '", ';

        if (count($values)) {
            $data .= 'param_values: ' . json_encode($values);
        }

        $data .= '}';

        $js = 'loadModalForm($(this), ' . htmlentities($data) . ', \'' . htmlentities($title) . '\', \'' . htmlentities($success_callback) . '\', \'' . $on_save . '\')';
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
        $js .= ');';

        return $js;
    }

    public function field_exists($field_name)
    {
        return ($this->use_commom_fields && in_array($field_name, self::$common_fields)) ||
                in_array($field_name, $this->params['fields']);
    }

    public function object_exists($object_name)
    {
        return array_key_exists($object_name, $this->params['objects']);
    }

    public function isChild($instance)
    {
        if (is_a($instance, 'BimpObject')) {
            $instance_parent_module = $instance->getParentModule();
            $instance_parent_object_name = $instance->getParentObjectName();

            if ($instance_parent_module === $this->module &&
                    $instance_parent_object_name === $this->object_name) {
                if (!$instance->isLoaded()) {
                    return true;
                }
                if ((int) $instance->getParentId() === (int) $this->id) {
                    return true;
                }
                return false;
            }

            if (is_array($this->extends)) {
                foreach ($this->extends as $extends) {
                    if ($extends['module'] === $instance_parent_module &&
                            $extends['object_name'] === $instance_parent_object_name) {
                        if (!$instance->isLoaded()) {
                            return true;
                        }
                        if ((int) $instance->getParentId() === (int) $this->id) {
                            return true;
                        }
                        return false;
                    }
                }
            }
        }
        return false;
    }

    public function association_exists($association)
    {
        return in_array($association, $this->params['associations']);
    }

    public function getRef($withGeneric = true)
    {
        if ($this->field_exists('ref')) {
            return $this->getData('ref');
        }

        if ($this->field_exists('reference')) {
            return $this->getData('reference');
        }

        if ($withGeneric) {
            return get_class($this) . "_" . $this->id;
        }

        return '';
    }

    public function getNomUrl($withpicto = true)
    {
        $html = '<a href="' . $this->getUrl() . '">';
        if ($this->params['icon']) {
            $html .= '<i class="' . BimpRender::renderIconClass($this->params['icon']) . ' iconLeft"></i>';
        }
        $html .= $this->getRef() . '</a>';
        return $html;
    }

    public function getParentIdProperty()
    {
        $property = $this->params['parent_id_property'];
        if (is_null($property)) {
            if ($this->field_exists('id_parent')) {
                $property = 'id_parent';
            }
        }
        return $property;
    }

    public function getParentId()
    {
        $prop = $this->getParentIdProperty();
        if (!is_null($prop)) {
            return $this->getData($prop);
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
            $object = $this->getParentObjectName();

            if ($module && $object) {
                $instance = self::getInstance($module, $object);

                if (!is_null($instance) && $instance && !is_null($id_property)) {
                    if (!is_null($id_parent) && $id_parent) {
                        $instance->fetch($id_parent);
                    }
                }

                $this->parent = $instance;
            }
        }
        return $this->parent;
    }

    public function doMatchFilters($filters)
    {
        foreach ($filters as $field => $filter) {
            if ($this->field_exists($field)) {
                if (is_array($filter)) {
                    // todo ... 
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

    public function isActionAllowed($action, &$errors = array())
    {
        return 1;
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

    // Gestion des objets enfants:

    public function setChild($child)
    {
        if (BimpObject::objectLoaded($child) && is_a($child, 'BimpObject')) {
            if (!isset($this->children[$child->object_name])) {
                $this->children[$child->object_name] = array();
            }
            $this->children[$child->object_name][$child->id] = $child;
        }
    }

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
        $child = $this->config->getObject('', $object_name);
        if (!is_null($child) && !is_null($id_object)) {
            $parent = null;
            if ($this->isChild($child)) {
                $parent = $this;
            }
            $child->fetch($id_object, $parent);
        } elseif (is_a($child, 'BimpObject') && BimpObject::objectLoaded($child)) {
            if ($this->isChild($child)) {
                $child->parent = $this;
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
                        if ($instance->getParentObjectName() === $this->object_name) {
                            $list_filters = $this->config->getCompiledParams('objects/' . $object_name . '/list/filters');
                            if (!is_null($list_filters)) {
                                foreach ($list_filters as $field => $filter) {
                                    $filters = BimpTools::mergeSqlFilter($filters, $field, $filter);
                                }
                            }
                            $filters = BimpTools::mergeSqlFilter($filters, $instance->getParentIdProperty(), $this->id);
                            $primary = $instance->getPrimary();
                            $list = $instance->getList($filters, null, null, $order_by, $order_way, 'array', array($primary));
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

    public function getChildrenObjects($object_name, $filters = array(), $order_by = 'id', $order_way = 'asc')
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
                        $is_child = (int) $this->isChild($instance);
                        $list_filters = $this->config->getCompiledParams('objects/' . $object_name . '/list/filters');
                        if (!is_null($list_filters)) {
                            foreach ($list_filters as $field => $filter) {
                                $filters = BimpTools::mergeSqlFilter($filters, $field, $filter);
                            }
                        }
                        if ($is_child) {
                            $filters[$instance->getParentIdProperty()] = $this->id;
                        }
                        $primary = $instance->getPrimary();
                        $list = $instance->getList($filters, null, null, $order_by, $order_way, 'array', array($primary));
                        if (!isset($this->children[$instance->object_name])) {
                            $this->children[$instance->object_name] = array();
                        }
                        foreach ($list as $item) {
                            if (!isset($this->children[$instance->object_name][(int) $item[$primary]])) {
                                $parent = null;
                                if ($is_child) {
                                    $parent = $this;
                                }
                                $child = BimpObject::getInstance($instance->module, $instance->object_name, (int) $item[$primary], $parent);

                                if ($child->isLoaded()) {
                                    $this->children[$instance->object_name][(int) $item[$primary]] = $child;
                                    $children[] = $child;
                                }
                            } else {
                                $children[] = $this->children[$instance->object_name][(int) $item[$primary]];
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
        if ($this->isDolObject()) {
            return (isset($this->id) && (int) $this->id && isset($this->dol_object->id) && (int) $this->dol_object->id);
        }
        return (isset($this->id) && (int) $this->id);
    }

    public function isNotLoaded()
    {
        return ($this->isLoaded() ? 0 : 1);
    }

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

    public function printData()
    {
        echo '<pre>';
        print_r($this->data);
        echo '</pre>';
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

        if (!$this->field_exists($field)) {
            return null;
        }

        if ($this->isDolObject()) {
            if ((int) $this->getConf('fields/' . $field . '/dol_extra_field', 0, false, 'bool')) {
                if (preg_match('/^ef_(.*)$/', $field, $matches)) {
                    $field = $matches[1];
                }
                return $this->db->getValue($this->getTable() . '_extrafields', $field, '`fk_object` = ' . (int) $id_object);
            }
        }
        $primary = $this->getPrimary();
        return $this->db->getValue($this->getTable(), $field, '`' . $primary . '` = ' . (int) $id_object);
    }

    public function set($field, $value)
    {
        return $this->validateValue($field, $value);
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

    public function setIdParent($id_parent)
    {
        $parent_id_property = $this->getParentIdProperty();
        $this->set($parent_id_property, $id_parent);

        if (!is_null($this->parent)) {
            if ((int) $this->parent->id !== (int) $id_parent) {
                $this->parent->reset();
                $this->parent->fetch($id_parent);
            }
        }
    }

    public function reset()
    {
        $this->config->resetObjects();

        $this->parent = null;

        foreach ($this->children as $object_name => $objects) {
            foreach ($objects as $id_object => $object) {
                if (is_object($object)) {
                    unset($this->children[$object_name][$id_object]);
                }
            }
        }

        $this->children = array();
        $this->data = array();
        $this->associations = array();
        $this->id = null;
        $this->ref = '';

        if (!is_null($this->dol_object)) {
            unset($this->dol_object);
            $this->dol_object = $this->config->getObject('dol_object');
        }
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
            if ($this->isDolObject()) {
                if (in_array($type, array('datetime', 'date', 'time'))) {
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
                $method = 'get' . ucfirst($field_name) . 'SearchFilters';
                if (method_exists($this, $method)) {
                    $this->{$method}($filters, $value);
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
                    $search_type = $this->getCurrentConf('search/type', 'field_input', false);

                    if ($value === '') {
                        $data_type = $this->getCurrentConf('type', '');
                        if (in_array($data_type, array('id_object', 'id'))) {
                            continue;
                        }
                    }

                    if ($search_type === 'field_input') {
                        $input_type = BC_Field::getInputType($this, $field_name);

                        switch ($input_type) {
                            case 'text':
                                $search_type = 'value_part';
                                break;

                            case 'time':
                                $search_type = 'time_range';
                                break;

                            case 'date':
                                $search_type = 'date_range';
                                break;

                            case 'datetime':
                                $search_type = 'datetime_range';
                                break;
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
            $this->config->setCurrentPath($prev_path);
        }

        return $filters;
    }

    protected function checkFieldHistory($field, $value)
    {
        if (is_null($value)) {
            return;
        }
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

    protected function checkSqlFilters($filters, &$has_extrafields)
    {
        $return = array();
        foreach ($filters as $field => $filter) {
            if (is_array($filter) && isset($filter['or'])) {
                $return[$field] = array('or' => $this->checkSqlFilters($filter['or'], $has_extrafields));
            } elseif (is_array($filter) && isset($filter['and'])) {
                $return[$field] = array('and' => $this->checkSqlFilters($filter['and'], $has_extrafields));
            } else {
                if ((int) $this->getConf('fields/' . $field . '/dol_extra_field', 0, false, 'bool')) {
                    if (preg_match('/^ef_(.*)$/', $field, $matches)) {
                        $field = $matches[1];
                    }
                    $return['ef.' . $field] = $filter;
                    $has_extrafields = true;
                } else {
                    $return[$field] = $filter;
                }
            }
        }

        return $return;
    }

    public function setNewStatus($new_status, $extra_data = array(), &$warnings = array())
    {
        $new_status = (int) $new_status;

        if (!array_key_exists($new_status, static::$status_list)) {
            return array('Erreur: ce statut n\'existe pas');
        }

        $status_label = is_array(static::$status_list[$new_status]) ? static::$status_list[$new_status]['label'] : static::$status_list[$new_status];
        $object_label = $this->getLabel('the') . (isset($this->id) && $this->id ? ' ' . $this->id : '');

        if (!$this->canSetStatus($new_status)) {
            return array('Vous n\'avez pas la permission de passer ' . $this->getLabel('this') . ' au statut "' . $status_label . '"');
        }

        $error_msg = 'Impossible de passer ' . $object_label;
        $error_msg .= ' au statut "' . $status_label . '"';

        if (!$this->isLoaded()) {
            return array($error_msg . ' ID ' . $this->getLabel('of_the') . ' absent');
        }

        $current_status = (int) $this->getSavedData('status');

        if ($current_status === $new_status) {
            return array($object_label . ' a déjà le statut "' . $status_label . '"');
        }

        $errors = array();
        if (method_exists($this, 'onNewStatus')) {
            $errors = $this->onNewStatus($new_status, $current_status, $extra_data, $warnings);
        }

        if (!count($errors)) {
            $this->set('status', $new_status);
            $errors = $this->update();
        }

        return $errors;
    }

    public function setObjectAction($action, $id_object = 0, $extra_data = array(), &$success = '')
    {
        $errors = array();

        if ((int) $id_object) {
            if (!$this->fetch($id_object)) {
                $errors[] = BimpTools::ucfirst($this->getLabel('the')) . ' d\'ID ' . $id_object . ' n\'existe pas';
                return $errors;
            }
        }

        if (!$this->canSetAction($action)) {
            return array('Vous n\'avez pas la permission d\'effectuer cette action');
        }

        if (!$this->isActionAllowed($action, $errors)) {
            return BimpTools::getMsgFromArray($errors, 'Action impossible');
        }

        $method = 'action' . ucfirst($action);
        if (method_exists($this, $method)) {
            $errors = $this->{$method}($extra_data, $success);
        } else {
            $errors[] = 'Action invalide: "' . $action . '"';
        }

        return $errors;
    }

    // Affichage des données:

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

        $fields = $this->getConf('fields', array(), true, 'array');

        foreach ($fields as $field => $params) {
            $this->config->setCurrentPath('fields/' . $field);
            if (!(int) $this->getCurrentConf('editable', 1, false, 'bool')) {
                continue;
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

        if (!$this->canEditField($field)) {
            $errors[] = 'Vous n\'avez pas la permission de modifier ce champ';
            return $errors;
        }

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
            if (!(int) $this->getConf('fields/' . $field . '/editable', 1, false, 'bool')) {
                continue;
            }
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

        if (!count($errors)) {
            if ($this->isLoaded()) {
                $errors = $this->update($warnings);
                if (!count($errors)) {
                    $success = 'Mise à jour ' . $this->getLabel('of_the') . ' effectuée avec succès';
                    if (method_exists($this, 'getUpdateJsCallback')) {
                        $success_callback = $this->getUpdateJsCallback();
                    }
                }
            } else {
                $errors = $this->create($warnings);
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
            $sub_result = $this->checkSubObjectsPost();
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

    public function create(&$warnings = array(), $force_create = false)
    {
        if (!$force_create && !$this->canCreate()) {
            return array('Vous n\'avez pas la permission de créer ' . $this->getLabel('a'));
        }
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

            foreach ($this->data as $field => &$value) {
                $this->checkFieldValueType($field, $value);
                $this->checkFieldHistory($field, $value);
                if (is_null($value)) {
                    unset($this->data[$field]);
                }
            }

            if (!is_null($this->dol_object)) {
                $result = $this->createDolObject($errors);
            } else {
                $table = $this->getTable();

                if (is_null($table)) {
                    return array('Fichier de configuration invalide (table non renseignées)');
                }

                $result = $this->db->insert($table, $this->data, true);
            }

            if ($result > 0) {
                $this->id = (int) $result;
                $this->set($this->getPrimary(), (int) $result);

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

                $warnings = array_merge($warnings, $this->updateAssociations());
                $warnings = array_merge($warnings, $this->saveHistory());

                $parent = $this->getParentInstance();
                if (!is_null($parent)) {
                    if (BimpObject::objectLoaded($parent) && is_a($parent, 'BimpObject')) {
                        $parent->setChild($this);
                    }
                    if (method_exists($parent, 'onChildSave')) {
                        $parent->onChildSave($this);
                    }
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

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        if (!$force_update && !$this->canEdit()) {
            return array('Vous n\'avez pas la permission de modifier ' . $this->getLabel('this'));
        }
        $errors = array();

        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' Absent');
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
                $this->checkFieldHistory($field, $value);
                if (is_null($value)) {
                    unset($this->data[$field]);
                }
            }

            if (!is_null($this->dol_object)) {
                $result = $this->updateDolObject($errors);
            } else {
                $table = $this->getTable();
                $primary = $this->getPrimary();

                if (is_null($table)) {
                    return array('Fichier de configuration invalide (table non renseignée)');
                }

                unset($this->data[$primary]);

                $result = $this->db->update($table, $this->data, '`' . $primary . '` = ' . (int) $this->id);
                $this->set($primary, $this->id);
            }

            if ($result <= 0) {
                $msg = 'Echec de la mise à jour ' . $this->getLabel('of_the');
                $sqlError = $this->db->db->lasterror;
                if ($sqlError) {
                    $msg .= ' - Erreur SQL: ' . $sqlError;
                }
                $errors[] = $msg;
            }
        }

        $warnings = array_merge($warnings, $this->updateAssociations());
        $warnings = array_merge($warnings, $this->saveHistory());

        $parent = $this->getParentInstance();

        if (!is_null($parent)) {
            if (BimpObject::objectLoaded($parent) && is_a($parent, 'BimpObject')) {
                $parent->setChild($this);
            }
            if (method_exists($parent, 'onChildSave')) {
                $warnings = array_merge($warnings, $parent->onChildSave($this));
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

    public function updateField($field, $value, $id_object = null)
    {
        if (is_null($id_object) || !$id_object) {
            if ($this->isLoaded()) {
                $id_object = (int) $this->id;
            }
        }
        $errors = array();

        if (is_null($id_object) || !$id_object) {
            $errors[] = 'Impossible de mettre à jour le champ "' . $field . '" - ID ' . $this->getLabel('of_the') . ' absent';
            return $errors;
        }

        if ($this->field_exists($field)) {
            $errors = $this->validateValue($field, $value);
            if (!count($errors)) {
                $extra_field = (int) $this->getConf('fields/' . $field . '/dol_extra_field', 0, false, 'bool');
                if ($extra_field && $this->isDolObject()) {
                    if ($this->db->update($this->getTable() . '_extrafields', array(
                                $field => $value
                                    ), '`fk_object` = ' . (int) $id_object) <= 0) {
                        $sqlError = $this->db->db->lasterror();
                        $errors[] = 'Echec de la mise à jour du champ "' . $field . '"' . ($sqlError ? ' - ' . $sqlError : '');
                    } else {
                        $this->set($field, $value);
                    }
                } else {
                    if ($this->db->update($this->getTable(), array(
                                $field => $value
                                    ), '`' . $this->getPrimary() . '` = ' . (int) $id_object) <= 0) {
                        $sqlError = $this->db->db->lasterror();
                        $errors[] = 'Echec de la mise à jour du champ "' . $field . '"' . ($sqlError ? ' - ' . $sqlError : '');
                    } else {
                        $this->set($field, $value);
                    }
                }

                if (!count($errors)) {
                    if ($this->getConf('fields/' . $field . '/history', false, false, 'bool')) {
                        global $user;
                        $history = BimpObject::getInstance('bimpcore', 'BimpHistory');
                        $history->validateArray(array(
                            'module'    => $this->module,
                            'object'    => $this->object_name,
                            'id_object' => (int) $id_object,
                            'field'     => $field,
                            'value'     => $value,
                            'date'      => date('Y-m-d H:i:s'),
                            'id_user'   => (int) $user->id,
                        ));
                        $history->create($warnings, true);
                    }

                    $parent = $this->getParentInstance();

                    if (!is_null($parent)) {
                        if (BimpObject::objectLoaded($parent) && is_a($parent, 'BimpObject')) {
                            $parent->setChild($this);
                        }
                        if (method_exists($parent, 'onChildSave')) {
                            $warnings = array_merge($warnings, $parent->onChildSave($this));
                        }
                    }
                }
            }
        } else {
            $errors[] = 'Le champ "' . $field . '" n\'existe pas';
        }

        return $errors;
    }

    public function find($filters, $return_first = false)
    {
        $this->reset();

        $id_object = null;

        $joins = null;
        $table = $this->getTable();
        $primary = $this->getPrimary();

        if ($this->isDolObject()) {
            $has_extrafields = false;
            $filters = $this->checkSqlFilters($filters, $has_extrafields);

            if ($has_extrafields) {
                $joins = array(
                    'alias' => 'ef',
                    'table' => $table . '_extrafields',
                    'on'    => 'ef.fk_object = a.' . $primary
                );
            }
        }

        $sql = BimpTools::getSqlSelect('a.' . $primary);
        $sql .= BimpTools::getSqlFrom($table, $joins);
        $sql .= BimpTools::getSqlWhere($filters);

        $rows = $this->db->executeS($sql, 'array');

        if (!is_null($rows) && count($rows)) {
            if (count($rows) > 1 && !$return_first) {
                return false;
            }
            $id_object = $rows[0][$primary];
        }

        if (is_null($id_object) || !$id_object) {
            return false;
        }

        return $this->fetch($id_object);
    }

    public function fetch($id, $parent = null)
    {
        if (BimpController::$debug_time) {
            global $main_controller;

            if (is_a($main_controller, 'BimpController')) {
                $main_controller->addDebugTime('Fetch ' . $this->getLabel() . ' - ID ' . $id);
            }
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

    public function getList($filters = array(), $n = null, $p = null, $order_by = 'id', $order_way = 'DESC', $return = 'array', $return_fields = null, $joins = null, $extra_order_by = null, $extra_order_way = 'ASC')
    {
        $table = $this->getTable();

        if (is_null($table)) {
            return array();
        }

        if ($order_by === 'id') {
            $order_by = $this->getPrimary();
        }
        $order_by_alias = 'a';

        if ($this->isDolObject()) {
            $has_extrafields = false;
            foreach ($return_fields as $key => $field) {
                if ($this->field_exists($field)) {
                    if ((int) $this->getConf('fields/' . $field . '/dol_extra_field', 0, false, 'bool')) {
                        if (preg_match('/^ef_(.*)$/', $field, $matches)) {
                            $field = $matches[1];
                        }
                        $return_fields[$key] = 'ef.' . $field;
                        $has_extrafields = true;
                    }
                }
            }

            $filters = $this->checkSqlFilters($filters, $has_extrafields);

            if ($this->field_exists($order_by)) {
                if ((int) $this->getConf('fields/' . $order_by . '/dol_extra_field', 0, false, 'bool')) {
                    $has_extrafields = true;
                    $order_by_alias = 'ef';
                    if (preg_match('/^ef_(.*)$/', $order_by, $matches)) {
                        $order_by = $matches[1];
                    }
                }
            } else {
                $order_by = '';
            }

            if ($has_extrafields) {
                if (is_null($joins)) {
                    $joins = array();
                }
                $joins[] = array(
                    'alias' => 'ef',
                    'table' => $table . '_extrafields',
                    'on'    => 'a.rowid = ef.fk_object'
                );
            }
        }

        $sql = '';
        $sql .= BimpTools::getSqlSelect($return_fields);
        $sql .= BimpTools::getSqlFrom($table, $joins);
        $sql .= BimpTools::getSqlWhere($filters);
        $sql .= BimpTools::getSqlOrderBy($order_by, $order_way, $order_by_alias, $extra_order_by, $extra_order_way);
        $sql .= BimpTools::getSqlLimit($n, $p);

        if (BimpTools::isSubmit('list_sql')) {
            echo $sql;
            exit;
        }

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

        if ($this->isDolObject()) {
            $has_extrafields = false;
            $filters = $this->checkSqlFilters($filters, $has_extrafields);
            if ($has_extrafields) {
                if (is_null($joins)) {
                    $joins = array();
                }
                $joins[] = array(
                    'alias' => 'ef',
                    'table' => $table . '_extrafields',
                    'on'    => 'a.rowid = ef.fk_object'
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

    public function delete($force_delete = false)
    {
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        if (!$force_delete && !$this->canDelete()) {
            return array('Vous n\'avez pas la permission de supprimer ' . $this->getLabel('this'));
        }

        $errors = array();

        $parent = $this->getParentInstance();

        if (method_exists($this, 'deleteProcess')) {
            $result = $this->deleteProcess();
        } elseif (!is_null($this->dol_object)) {
            $result = $this->deleteDolObject($errors);
        } else {
            $table = $this->getTable();
            $primary = $this->getPrimary();

            if (is_null($table)) {
                return array('Fichier de configuration invalide (table non renseignée)');
            }
            $result = $this->db->delete($table, '`' . $primary . '` = ' . (int) $this->id);
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
            $this->reset();
            if ((int) $this->params['positions']) {
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
                            $del_errors = array();
                            if ($this->isChild($instance)) {
                                if (!$instance->deleteByParent($id, $del_errors, $force_delete)) {
                                    $msg = 'Des erreurs sont survenues lors de la tentative de suppression des ';
                                    $msg .= $this->getInstanceLabel($instance, 'name_plur');
                                    if (count($del_errors)) {
                                        $msg .= ':';
                                        foreach ($del_errors as $error) {
                                            $msg .= '<br/> - ' . $error;
                                        }
                                    }
                                    $errors[] = $msg;
                                }
                            } else {
                                $relation = $this->getCurrentConf('relation', '');
                                if ($relation === 'hasOne') {
                                    $field_name = $this->getCurrentConf('instance/id_object/field_value', null);
                                    if (!is_null($field_name) && $field_name) {
                                        if ($instance->fetch((int) $this->getData($field_name))) {
                                            $del_errors = $instance->delete($force_delete);
                                            if (count($del_errors)) {
                                                $msg = 'Des erreurs sont survenues lors de la tentative de suppression ';
                                                $msg .= $this->getInstanceLabel($instance, 'of_the') . ' d\'ID ' . $this->getData($field_name) . ':';
                                                foreach ($del_errors as $error) {
                                                    $msg .= '<br/> - ' . $error;
                                                }
                                                $errors[] = $msg;
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

            if (!is_null($parent)) {
                if (method_exists($parent, 'onChildDelete')) {
                    $parent->onChildDelete($this);
                }
            }
        }

        $this->reset();
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
                $this->reset();
                if ($this->fetch($item->id)) {
                    $del_errors = $this->delete($force_delete);
                    if (count($del_errors)) {
                        $check = false;
                        $errors = array_merge($errors, $del_errors);
                    }
                }
            }
        }
        return $check;
    }

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
                    } else {
                        $bimpObjectFields[$field] = $value;
                    }
                }
            }
        }

        return $errors;
    }

    protected function createDolObject(&$errors)
    {
        if (!is_null($this->dol_object) && isset($this->dol_object->id) && $this->dol_object->id) {
            unset($this->dol_object);
            $this->dol_object = $this->config->getObject('dol_object');
        }

        if (is_null($this->dol_object)) {
            $errors[] = 'Objet Dolibarr invalide';
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
            } else {
                if (count($bimpObjectFields)) {
                    $this->id = $this->dol_object->id;
                    foreach ($bimpObjectFields as $field_name => $value) {
                        $this->updateField($field_name, $value);
                    }
                }
            }

            return $result;
        }

        return 0;
    }

    protected function updateDolObject(&$errors)
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

            foreach ($bimpObjectFields as $field => $value) {
                $this->updateField($field, $value);
            }

            if ((int) $this->params['force_extrafields_update']) {
                foreach ($this->dol_object->array_options as $key => $value) {
                    if ($this->dol_object->updateExtraField(str_replace('options_', '', $key)) <= 0) {
                        $errors[] = 'Echec de l\'enregistrement de l\'attribut supplémentaire "' . str_replace('options_', '', $key) . '"';
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
                return 0;
            }
            return 1;
        }

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
                } else {
                    $value = '';
                }
            } else {
                $prop = $this->getConf('fields/' . $field . '/dol_prop', $field);
                if (is_null($prop)) {
                    $errors[] = 'Erreur de configuration: propriété de l\'objet Dolibarr non définie pour le champ "' . $field . '"';
                } elseif (property_exists($this->dol_object, $prop)) {
                    $value = $this->dol_object->{$prop};
                } else {
                    $value = $this->getSavedData($field);
                }
            }
            if (!is_null($value)) {
                $this->checkFieldValueType($field, $value);
                $this->data[$field] = $value;
            }
        }

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
            $params = array($this->id);
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

    public function checkSubObjectsPost()
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
                    if ($multiple) {
                        $count = BimpTools::getValue($object_name . '_count', 0);
                        for ($i = 1; $i <= $count; $i++) {
                            $_POST = array();
                            foreach ($post_temp as $key => $value) {
                                if (preg_match('/^' . $object_name . '_' . $i . '_(.*)$/', $key, $matches)) {
                                    $_POST[$matches[1]] = $value;
                                }
                            }
                            if (count($_POST)) {
                                if ($parent_id_property) {
                                    $_POST[$parent_id_property] = $this->id;
                                }
                                $object->reset();
                                $result = $object->saveFromPost();
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
                        foreach ($post_temp as $key => $value) {
                            if (preg_match('/^' . $object_name . '_(.*)$/', $key, $matches)) {
                                $_POST[$matches[1]] = $value;
                            }
                        }
                        if (count($_POST)) {
                            if ($parent_id_property) {
                                $_POST[$parent_id_property] = $this->id;
                            }
                            $object->reset();
                            $result = $object->saveFromPost();
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
                }
            }
        }
        return array(
            'errors'           => $errors,
            'success_callback' => $success_callback
        );
    }

    // Gestion des droits: 

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

    public function canEdit()
    {
        if ($this->params['parent_object']) {
            $parent = $this->getParentInstance();
            if (is_a($parent, 'BimpObject')) {
                return (int) $parent->canEditChild($this->object_name);
            }
        }
        return 1;
    }

    public function canView()
    {
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
        if ($this->params['parent_object']) {
            $parent = $this->getParentInstance();
            if (is_a($parent, 'BimpObject')) {
                return (int) $parent->canDeleteChild($this->object_name);
            }
        }
        return 1;
    }

    public function canEditField($field_name)
    {
        return (int) $this->canEdit();
    }

    public function canCreateChild($child_name)
    {
        return (int) $this->canCreate();
    }

    public function canEditChild($child_name)
    {
        return (int) $this->canEdit();
    }

    public function canViewChild($child_name)
    {
        return (int) $this->canView();
    }

    public function canDeleteChild($child_name)
    {
        return (int) $this->canDelete();
    }

    public function canSetAction($action)
    {
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

    // Gestion des notes:

    public function addNote($content, $visibility = null)
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
            'content'    => $content
        ));

        if (!count($errors)) {
            $errors = $note->create();
        }

        return $errors;
    }

    public function renderNotesList($visibility = null)
    {
        if ($this->isLoaded()) {
            $note = BimpObject::getInstance('bimpcore', 'BimpNote');
            $list = new BC_ListTable($note);
            $list->addFieldFilterValue('obj_type', 'bimp_object');
            $list->addFieldFilterValue('obj_module', $this->module);
            $list->addFieldFilterValue('obj_name', $this->object_name);
            $list->addFieldFilterValue('id_obj', $this->id);
            $list->addObjectChangeReload($this->object_name);
            if (!is_null($visibility)) {
                $list->addFieldFilterValue('visibility', array(
                    'operator' => '<=',
                    'value'    => (int) $visibility
                ));
            }

            return $list->renderHtml();
        }

        return BimpRender::renderAlerts('Impossible d\'afficher la liste des notes (ID ' . $this->getLabel('of_the') . ' absent)');
    }

    // Rendus HTML

    public function renderHeader($content_only = false)
    {
        $html = '';
        if ($this->isLoaded()) {
            $name = $this->getInstanceName();

            if (!$content_only) {
                $html .= '<div id="' . $this->object_name . '_' . $this->id . '_header" class="object_header container-fluid">';
            }
            $html .= '<div class="row">';

            $html .= '<div class="col-lg-6 col-sm-8 col-xs-12">';
            if (!is_null($this->params['header_list_name']) && $this->params['header_list_name']) {
                $html .= '<div class="header_button">';
                $url = BimpTools::makeUrlFromConfig($this->config, 'list_page_url', $this->module, $this->getController());
                if ($url) {
                    $items = array(
                        '<span class="dropdown-title">Liste des ' . $this->getLabel('name_plur') . '</span>'
                    );
                    $items[] = BimpRender::renderButton(array(
                                'label'       => 'Vue rapide',
                                'icon_before' => 'far_eye',
                                'classes'     => array(
                                    'btn', 'btn-light-default'
                                ),
                                'attr'        => array(
                                    'onclick' => 'loadModalList(\'' . $this->module . '\', \'' . $this->object_name . '\', \'' . $this->params['header_list_name'] . '\', 0, $(this))'
                                )
                                    ), 'button');
                    $items[] = BimpRender::renderButton(array(
                                'label'       => 'Afficher la page',
                                'icon_before' => 'far_file-alt',
                                'classes'     => array(
                                    'btn', 'btn-light-default'
                                ),
                                'attr'        => array(
                                    'onclick' => 'window.location = \'' . $url . '\''
                                )
                                    ), 'button');
                    $items[] = BimpRender::renderButton(array(
                                'label'       => 'Afficher la page dans un nouvel onglet',
                                'icon_before' => 'fas_external-link-alt',
                                'classes'     => array(
                                    'btn', 'btn-light-default'
                                ),
                                'attr'        => array(
                                    'onclick' => 'window.open(\'' . $url . '\');'
                                )
                                    ), 'button');
                    $html .= BimpRender::renderDropDownButton('', $items, array(
                                'icon' => 'bars'
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

            $html .= '<div style="display: inline-block">';
            $html .= '<h1>';
            if ($this->params['icon']) {
                $html .= '<i class="' . BimpRender::renderIconClass($this->params['icon']) . ' iconLeft"></i>';
            }
            $html .= $name . '</h1>';

            $ref = $this->getRef(false);
            if ($ref) {
                $html .= '<h2>';
                $html .= $ref;
                $html .= '</h2>';
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

            if (method_exists($this, 'renderHeaderExtraLeft')) {
                $html .= '<div style="margin: 10px 0;">';
                $html .= $this->renderHeaderExtraLeft();
                $html .= '</div>';
            }
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
            if ($status) {
                $html .= '<div class="header_status">';
                $html .= $status;
                if (method_exists($this, 'renderHeaderStatusExtra')) {
                    $html .= $this->renderHeaderStatusExtra();
                }
                $html .= '</div>';
            }

            $this->params['header_btn'] = $this->config->getCompiledParams('header_btn');
            if (is_null($this->params['header_btn'])) {
                $this->params['header_btn'] = array();
            }

            if (count($this->params['header_btn'])) {
                $header_buttons = array();
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
                            $button['data']['html'] = 'false';
                            $button['data']['content'] = $popover;
                        }
                    }

                    if (!is_null($button)) {
                        $header_buttons[] = BimpRender::renderButton($button, 'button');
                    }
                }

                $html .= '<div class="header_buttons">';
                if (count($header_buttons)) {
                    if (count($header_buttons) > 4) {

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
                $html .= '</div>';
            }

            if (method_exists($this, 'renderHeaderExtraRight')) {
                $html .= '<div style="margin: 10px 0;">';
                $html .= $this->renderHeaderExtraRight();
                $html .= '</div>';
            }

            $html .= '</div>';

            $html .= '</div>';

            $html .= '<div class="row header_bottom"></div>';

            if (!$content_only) {
                $html .= '</div>';
            }
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
                $url = BimpTools::makeUrlFromConfig($this->config, 'list_page_url', $this->module, $this->getController());
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

            $values = array();
            if ($owner_type && $id_owner) {
                $userConfig = $this->getListConfig($owner_type, $id_owner);
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

            $content = BimpInput::renderInput('select', 'cols_add_value', '', array('options' => $cols));

            $html .= BimpInput::renderInputContainer('cols_add_value', '', $content, '', 0, 1, '', array('values_field' => 'cols'));
            $html .= BimpInput::renderMultipleValuesList($this, 'cols', $values, 'cols_add_value', 0, 0, 1);
        } else {
            $html .= BimpRender::renderAlerts('Aucune option disponible', 'warnings');
        }

        return $html;
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
        }

        return $object_name;
    }

    public function isLabelFemale()
    {
        return (int) $this->params['labels']['is_female'];
    }

    public function getInstanceName()
    {
        if (!$this->isLoaded()) {
            return ' ';
        }

        if ($this->field_exists('title') &&
                isset($this->data['title']) && $this->data['title']) {
            return $this->data['title'];
        } elseif ($this->field_exists('public_name') &&
                isset($this->data['public_name']) && $this->data['public_name']) {
            return $this->data['public_name'];
        } elseif ($this->field_exists('label') &&
                isset($this->data['label']) && $this->data['label']) {
            return $this->data['label'];
        } elseif ($this->field_exists('name') &&
                isset($this->data['name']) && $this->data['name']) {
            return $this->data['name'];
        } elseif ($this->field_exists('nom') &&
                isset($this->data['nom']) && $this->data['nom']) {
            return $this->data['nom'];
        } elseif (isset($this->id) && $this->id) {
            return BimpTools::ucfirst($this->getLabel()) . ' ' . $this->id;
        }

        return BimpTools::ucfirst($this->getLabel());
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

    public function getListColsArray($list_name = 'default')
    {
        $cols = array();

        $bc_list = new BC_ListTable($this, $list_name);

        if ((int) $bc_list->params['configurable'] &&
                $this->config->isDefined('lists_cols')) {
            foreach ($this->config->getCompiledParams('lists_cols') as $col_name => $col_params) {
                if (isset($col_params['label']) && $col_params['label']) {
                    $cols[$col_name] = $col_params['label'];
                } elseif (isset($col_params['field']) && $col_params['field'] && $this->config->isDefined('fields/' . $col_params['field'] . '/label')) {
                    $cols[$col_name] = $this->getConf('fields/' . $col_params['field'] . '/label', $col_name);
                } else {
                    $cols[$col_name] = $col_name;
                }
            }
        }

        foreach ($bc_list->params['cols'] as $col_name) {
            $col_params = $bc_list->fetchParams($bc_list->config_path . '/cols/' . $col_name, $bc_list->col_params);

            if ($col_params['label']) {
                $cols[$col_name] = $col_params['label'];
            } elseif ($col_params['field'] && $this->config->isDefined('fields/' . $col_params['field'] . '/label')) {
                $cols[$col_name] = $this->getConf('fields/' . $col_params['field'] . '/label', $col_name);
            } else {
                $cols[$col_name] = $col_name;
            }
        }

        return $cols;
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
                if ($instance->params['icon']) {
                    $html .= BimpRender::renderIcon($instance->params['icon']) . '&nbsp;';
                }
                $url = $instance->getUrl();
                if ($url) {
                    $html .= '<a href="' . $url . '" target="_blank">' . $instance->getInstanceName() . '</a>';
                } else {
                    $html .= $instance->getInstanceName();
                }
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

    // Action Communes: 

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

            if (isset($data['sort_option'])) {
                $config->set('sort_option', $data['sort_option']);
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
            'errors'   => $errors,
            'warnings' => $warnings,
            'success_callback' => 'window.location.reload();'
        );
    }
}
