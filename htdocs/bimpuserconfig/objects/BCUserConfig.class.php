<?php

require_once DOL_DOCUMENT_ROOT . '/bimpuserconfig/objects/UserConfig.class.php';

class BCUserConfig extends UserConfig
{

    public static $config_object_name = 'BCUserConfig';
    public static $component_type = '';
    public static $use_component_name = true;
    protected $obj_instance = null;
    public static $components_types = array(
        'list_table'    => array('label' => 'Liste', 'component_object' => 'BC_ListTable', 'config_object' => 'ListTableConfig'),
        'stats_list'    => array('label' => 'Liste de statistiques', 'component_object' => 'BC_StatsList', 'config_object' => 'StatsListConfig'),
        'list_views'    => array('label' => 'Liste de vues', 'component_object' => 'BC_ListViews', 'config_object' => 'ListViewsConfig'),
        'list_custom'   => array('label' => 'Liste personnalisées', 'component_object ' => 'BC_ListCustom', 'config_object' => 'ListCustomConfig'),
        'filters_panel' => array('label' => 'Filtres', 'component_object ' => 'BC_FiltersPanel', 'config_object' => 'FiltersConfig')
    );

    // Getters booléens: 

    public function isComponentParamActive($param_name, $default_value = 1)
    {
        $obj = $this->getObjInstance();

        if (is_a($obj, 'BimpObject')) {
            $path = $this->getObjectConfigPath();

            if ($path) {
                return (int) ($obj->config->get($path . '/' . $param_name, $default_value, false, 'bool'));
            }
        }

        return 0;
    }

    // Getters données: 

    public static function getInstanceFromComponentType($comp_type = '', $id_config = null)
    {
        if (isset(static::$components_types[$comp_type])) {
            if ((int) $id_config) {
                return BimpCache::getBimpObjectInstance('bimpuserconfig', static::$components_types[$comp_type]['config_object'], (int) $id_config);
            }
            return BimpObject::getInstance('bimpuserconfig', static::$components_types[$comp_type]['config_object']);
        }

        return null;
    }

    public function getObjInstance()
    {
        if (is_null($this->obj_instance)) {            
            $module = (string) $this->getData('obj_module');
            $object_name = (string) $this->getData('obj_name');

            if ($module && $object_name) {
                $this->obj_instance = BimpObject::getInstance($module, $object_name);
            }
        }

        return $this->obj_instance;
    }

    public function getConfigFilters()
    {
        $module = $this->getData('obj_module');
        $object_name = $this->getData('obj_name');

        if ($module && $object_name) {
            $filters = array(
                'obj_module' => $module,
                'obj_name'   => $object_name
            );

            if (static::$use_component_name && $this->field_exists('component_name') && $this->getData('component_name')) {
                $filters['component_name'] = $this->getData('component_name');
            }

            return $filters;
        }

        return null;
    }

    public function getCurrentConfigKey()
    {
        $key = '';

        if ($this->isLoaded()) {
            $module = $this->getData('obj_module');
            $object_name = $this->getData('obj_name');

            if ($module && $object_name) {
                $key = 'bc_' . $module . '_' . $object_name . '_' . static::$component_type;

                if (static::$use_component_name && $this->field_exists('component_name') && $this->getData('component_name')) {
                    $key .= '_' . $this->getData('component_name');
                }
            }
        }

        return $key;
    }

    public function getObjectConfigPath()
    {
        BimpCore::addlog('BCUserConfig: getObjectConfigPath() non redéfini dans l\'objet "' . get_class($this) . '"', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore');
        return '';
    }

    // Getters array: 

    public static function getUserConfigsArray($id_user, $object, $component_name = '', $include_empty = false)
    {
        if (!is_a($object, 'BimpObject')) {
            return null;
        }
        $cache_key = $object->module . '_' . $object->object_name . '_' . static::$component_type;

        if (static::$use_component_name && $component_name) {
            $cache_key .= '_' . $component_name;
        }

        $filters = static::getConfigFiltersStatic($object, $component_name);

        if (!is_null($filters)) {
            return static::getUserConfigsArrayCore($id_user, $include_empty, array(
                        'cache_key' => $cache_key,
                        'filters'   => $filters
            ));
        }

        return array();
    }

    public static function getGroupConfigsArray($id_group, $object, $component_name = '', $include_empty = false)
    {
        if (!is_a($object, 'BimpObject')) {
            return null;
        }
        $cache_key = $object->module . '_' . $object->object_name . '_' . static::$component_type;

        if (static::$use_component_name && $component_name) {
            $cache_key .= '_' . $component_name;
        }

        $filters = static::getConfigFiltersStatic($object, $component_name);

        if (!is_null($filters)) {
            return static::getGroupConfigsArrayCore($id_group, $include_empty, array(
                        'cache_key' => $cache_key,
                        'filters'   => $filters
            ));
        }

        return array();
    }

    // Getters statics: 

    public static function getConfigFiltersStatic($object, $component_name = '')
    {
        if (is_a($object, 'BimpObject')) {
            $filters = array(
                'obj_module' => $object->module,
                'obj_name'   => $object->object_name
            );

            if (static::$use_component_name && $component_name) {
                $filters['component_name'] = $component_name;
            }

            return $filters;
        }

        return null;
    }

    public static function getCurrentConfigKeyStatic($object, $component_name = '')
    {
        $key = '';

        if (is_a($object, 'BimpObject')) {
            $key = 'bc_' . $object->module . '_' . $object->object_name . '_' . static::$component_type;

            if (static::$use_component_name && $component_name) {
                $key .= '_' . $component_name;
            }
        }

        return $key;
    }

    public static function getCurrentConfigKeyFromConfigFilters($filters)
    {
        $module = BimpTools::getArrayValueFromPath($filters, 'obj_module', '');
        $obj_name = BimpTools::getArrayValueFromPath($filters, 'obj_name');

        if ($module && $obj_name) {
            $key = 'bc_' . $module . '_' . $obj_name . '_' . static::$component_type;

            if (static::$use_component_name) {
                $comp_name = BimpTools::getArrayValueFromPath($filters, 'component_name', '');

                if ($comp_name) {
                    $key .= '_' . $comp_name;
                }
            }

            return $key;
        }

        return '';
    }

    public static function getUserCurrentConfig($id_user, $object, $component_name = '')
    {
        $key = static::getCurrentConfigKeyStatic($object, $component_name);
        $filters = static::getConfigFiltersStatic($object, $component_name);

        if ($key && !is_null($filters)) {
            $config = static::getUserCurrentConfigCore($id_user, $key, $filters);

            if (BimpObject::objectLoaded($config)) {
                if ($config->getData('obj_module') != $object->module ||
                        $config->getData('obj_name') != $object->object_name ||
                        (static::$use_component_name && ($component_name != $config->getData('component_name')))) {
                    return null;
                }

                return $config;
            }
        }

        return null;
    }

    // Affichage: 

    public function displayObjectLabel()
    {
        $instance = $this->getObjInstance();

        if (is_a($instance, 'BimpObject')) {
            return BimpTools::ucfirst($instance->getLabel());
        }

        return '';
    }

    // Overrides: 

    public function reset()
    {
        parent::reset();
        unset($this->obj_instance);
        $this->obj_instance = null;
    }
}
