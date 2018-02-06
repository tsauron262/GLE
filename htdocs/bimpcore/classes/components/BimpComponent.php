<?php

abstract class BimpComponent
{

    public $object;
    public $name;
    public $config_path = null;
    public static $type = '';
    public static $config_required = true;
    public $params_def = array(
        'show' => array('data_type' => 'bool', 'default' => 1)
    );
    public static $type_params_def = array();
    public $params;
    public $errors = array();
    public $infos = array();
    public $warnings = array();

    public function __construct(BimpObject $object, $name, $path)
    {
        $this->object = $object;
        $this->name = $name;


        if (!$this->isObjectValid()) {
            $this->addError('Objet invalide');
        } else {
            if ($this->object->config->isDefined($path . ($name ? '/' . $name : ''))) {
                $this->config_path = $path . ($name ? '/' . $name : '');
            } elseif (!$name && $this->object->config->isDefined($path . '/default')) {
                $this->config_path = $path . '/default';
                $this->name = 'default';
            }
        }

        if (is_null($this->config_path) && static::$config_required) {
            $this->addTechnicalError('Configuration non définie');
        }

        $this->params = $this->fetchParams($this->config_path);

        if (isset($this->params['type'])) {
            if (array_key_exists($this->params['type'], static::$type_params_def)) {
                foreach ($this->fetchParams($this->config_path, static::$type_params_def[$this->params['type']]) as $p_name => $value) {
                    $this->params[$p_name] = $value;
                }
            }
        }
    }

    public function isObjectValid()
    {
        return (!is_null($this->object) && is_a($this->object, 'BimpObject'));
    }

    public function isOk()
    {
        if (count($this->errors) || (is_null($this->config_path) && self::$config_required)) {
            return false;
        }

        return true;
    }

    // Gestion des paramètres: 

    public function setConfPath($path = '')
    {
        if (isset($this->object->config)) {
            return $this->object->config->setCurrentPath($this->config_path . ($path ? '/' . $path : ''));
        }

        return false;
    }

    public function setParams($params)
    {
        foreach ($params as $name => $value) {
            $this->setParam($name, $value);
        }
    }

    public function setParam($name, $value)
    {
        if ($this->validateParam($name, $value)) {
            $this->params[$name] = $value;
        }
    }

    protected function validateParam($name, $value, $definitions = null)
    {
        if (is_null($definitions)) {
            $definitions = $this->params_def;
        }

        if (!array_key_exists($name, $definitions)) {
            $this->addTechnicalError('Paramètre de configuration invalide: "' . $name . '" (définitions absentes)');
            return false;
        }

        $defs = $definitions[$name];

        if (isset($defs['type']) && $defs['type'] !== 'value') {
            return true;
        }

        if (is_null($value) || ($value === '')) {
            if (isset($defs['required']) && (bool) $defs['required']) {
                $this->addTechnicalError('Paramètre de configuration obligatoire absent: "' . $name . '"');
                return false;
            }
            $value = '';
        }

        $type = isset($defs['data_type']) ? $defs['data_type'] : 'string';
        if (!BimpTools::checkValueByType($type, $value)) {
            $this->addTechnicalError('Paramètre de configuration invalide: "' . $name . '" (doit être de type "' . $type . '")');
            return false;
        }

        return true;
    }

    public function fetchParams($path, $definitions = null)
    {
        $params = array();
        if (is_null($definitions)) {
            $definitions = $this->params_def;
        }

        foreach ($definitions as $name => $defs) {
            $params[$name] = $this->fetchParam($name, $definitions, $path);
        }
        return $params;
    }

    protected function fetchParam($name, $definitions, $path)
    {
        if (!isset($definitions[$name])) {
            return null;
        }

        $defs = $definitions[$name];

        $default_value = (isset($defs['default']) ? $defs['default'] : null);
        $required = (isset($defs['required']) ? (bool) $defs['required'] : false);
        $type = (isset($defs['type']) ? $defs['type'] : 'value');

        $param = null;

        switch ($type) {
            case 'value':
                $value = null;
                $data_type = isset($defs['data_type']) ? $defs['data_type'] : 'string';
                $request = isset($defs['request']) ? (bool) $defs['request'] : false;
                if ($request) {
                    $json = isset($defs['json']) ? (bool) $defs['json'] : false;
                    if (BimpTools::isSubmit('param_' . $name)) {

                        $value = BimpTools::getValue('param_' . $name);
                        if ($json) {
                            $value = json_decode($value, true);
                        }
                    }
                }

                if (is_null($value)) {
                    if (!is_null($path)) {
                        if ($data_type === 'array') {
                            $compile = isset($defs['compile']) ? (bool) $defs['compile'] : true;
                            if ($compile) {
                                $value = $this->object->config->getCompiledParams($path . '/' . $name, array(), $required, 'array');
                            }
                        } else {
                            $value = $this->object->getConf($path . '/' . $name, $default_value, $required, $data_type);
                        }
                    } else {
                        $value = $default_value;
                    }
                }
                if (!is_null($value)) {
                    if ($this->validateParam($name, $value, $definitions)) {
                        $param = $value;
                    }
                } elseif ($required) {
                    $this->addTechnicalError('Paramètre obligatoire "' . $name . '" absent du fichier de configuration');
                }
                break;

            case 'object':
                if (!is_null($path)) {
                    $param = $this->object->config->getObject($path . '/' . $name);
                }
                break;

            case 'keys':
                if (!is_null($path)) {
                    $values = $this->object->getConf($path . '/' . $name, array(), $required, 'array');
                    $param = array();
                    if (!is_null($values)) {
                        foreach ($values as $key => $val) {
                            $param[] = $key;
                        }
                    }
                }
                break;

            case 'definitions':
                if (!is_null($path)) {
                    $defs_type = isset($defs['defs_type']) ? $defs['defs_type'] : null;
                    $multiple = isset($defs['multiple']) ? (bool) $defs['multiple'] : false;

                    if (!is_null($defs_type)) {
                        if (property_exists('BimpConfigDefinitions', $defs_type)) {
                            if (!$this->object->config->isDefined($path . '/' . $name) && $required) {
                                $this->addTechnicalError('Paramètre obligatoire "' . $name . '" absent du fichier de configuration');
                            } else {
                                if ($multiple) {
                                    $param = array();
                                    foreach ($this->object->getConf($path . '/' . $name, array(), false, 'array') as $key => $values) {
                                        $param[$key] = self::fetchParams($path . '/' . $name . '/' . $key, BimpConfigDefinitions::${$defs_type});
                                    }
                                } else {
                                    $param = $this->fetchParams($path . '/' . $name, BimpConfigDefinitions::${$defs_type});
                                }
                            }
                        } else {
                            $this->addTechnicalError('Type de définitions invalide pour le paramètre "' . $name . '" (' . $defs_type . ')');
                        }
                    } else {
                        $this->addTechnicalError('Type de définitions absent pour le paramètre "' . $name . '"');
                    }
                }
                break;

            case 'component':

                break;
        }

        return $param;
    }

    // Rendus:

    public function renderHtml()
    {
        $html = '';

        if (count($this->errors)) {
            $html .= BimpRender::renderAlerts($this->errors);
        }

        if (count($this->warnings)) {
            $html .= BimpRender::renderAlerts($this->warnings, 'warning');
        }

        if (count($this->infos)) {
            $html .= BimpRender::renderAlerts($this->infos, 'info');
        }

        return $html;
    }

    protected function writePDF($pdf)
    {
        if (!class_exists('BimpModelPDF')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpModelPDF.pho';
        }

        if (is_a($pdf, 'BimpModelPDF')) {
            
        } else {
            $this->addError('Erreur technique - instance de BimpModelPDF invalide');
        }
    }

    // Gestion des erreurs: 

    public function addTechnicalError($msg)
    {
        $this->addError('[ERREUR TECHNIQUE] ' . $msg);
    }

    public function addError($msg)
    {
        $this->errors[] = $msg;

        $label = '';

        if ($this->isObjectValid()) {
            $label .= BimpTools::ucfirst($this->object->getLabel());
        }
        $label .= ' - ' . static::$type . ' - ' . $this->name . ': ';
        dol_syslog($label . $msg, 3);
    }
}
