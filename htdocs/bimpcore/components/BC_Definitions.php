<?php

namespace BC_V2;

class BC_Definitions
{

    public static $definitions = null;

    public static function initDefinitions()
    {
        // Chargement de toutes les définitions des composants et mise en cache serveur. 
        if (is_null(self::$definitions)) {
            $key = 'bimp_components_definitions';

            if (\BimpCache::cacheServerExists($key)) {
                self::$definitions = \BimpCache::getCacheServeur($key);
            }

            self::$definitions = spyc_load_file(DOL_DOCUMENT_ROOT . '/bimpcore/components/components.yml');
            \BimpCache::setCacheServeur($key, self::$definitions);
        }
    }

    public static function getComponentDefinitions($component_path, $override_params = array(), $params_only = false)
    {
        self::initDefinitions();

        $defs = \BimpTools::getArrayValueFromPath(self::$definitions, $component_path, array());

        // Compilation des inclusions : 
        if (isset($defs['includes'])) {
            if (is_string($defs['includes'])) {
                $includes = explode(',', $defs['includes']);
            } else {
                $includes = $defs['includes'];
            }

            unset($defs['includes']);

            foreach ($includes as $include) {
                $defs['params'] = self::getComponentDefinitions($include, $defs['params'], true);
            }
        }

        // Compilation des extensions : 
        if (isset($defs['extends'])) {
            $extends = $defs['extends'];
            unset($defs['extends']);

            $defs['params'] = self::getComponentDefinitions($extends, $defs['params'], true);
        }

        // Traitement des surcharges : 
        if (!empty($override_params)) {
            $defs['params'] = \BimpTools::overrideArray($defs['params'], $override_params, false, true);
        }
        
        if ($params_only) {
            return $defs['params'];
        }

        return $defs;
    }
}
