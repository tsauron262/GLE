<?php

abstract class BimpObjectComponent
{

    public $object;
    public $name;
    public $identifier;
    public static $type = '';
    public static $params_def = array();
    protected $params;
    public $errors = array();
    public $infos = array();
    public $warnings = array();

    public function __construct(BimpObject $object, $name)
    {
        $this->object = $object;
        $this->name = $name;
        $this->identifier = $object->object_name . '_' . $name . '_' . static::$type;
    }

    public function isObjectValid()
    {
        return (!is_null($this->object) && is_a($this->object, 'BimpObject'));
    }

    public function setParams($params)
    {
        foreach ($params as $name => $value) {
            $this->setParam($name, $value);
        }
    }

    protected function fetchParams()
    {
        
    }

    protected function setParam($name, $value)
    {
        $defs = null;
        if (array_key_exists($name, self::$params_def)) {
            $defs = self::$params_def[$name];
        } elseif (array_key_exists($name, static::$params_def)) {
            $defs = self::$params_def[$name];
        }

        if (!is_null($defs)) {
            if (BimpTools::checkValueByType($defs['type'], $value)) {
                $this->params[$name] = $value;
            } else {
                $this->addError('Paramètre invalide: "' . $name . '"');
            }
        }
    }

    protected function renderHtml()
    {
        $html = '';

        $html .= '<div id="' . $this->identifier . '_container"';
        $html .= ' class="objectComponentContainer object' . ucfirst(static::$type) . 'Container ';
        $html .= $this->object->object_name . '_' . static::$type . 'Container">';

        $html .= $this->renderHtmlContent();

        $html .= '</div>';
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

    abstract public function renderHtmlContent();

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
