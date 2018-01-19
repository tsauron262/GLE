<?php

require_once __DIR__ . '/BimpModelPDF.php';

class BimpObjectPDF extends BimpModelPDF
{

    public $object = null;
    public static $type = 'bimp_object';

    public function __construct(BimpObject $object, $orientation = 'L', $format = 'A4')
    {
        $this->object = new BimpObject();

        parent::__construct($orientation, $format);
    }

    public function addSection($params)
    {
        $this->sections[] = $params;
    }

    protected function initHeader()
    {
        if (!is_null($this->object) && is_a($object, 'BimpObject') && $object->isLoaded) {
            $object_name = BimpTools::ucfirst($this->object->getLabel()) . ' "' . $this->object->getInstanceName() . '"';
            $this->header_vars['header_right'] = $this->renderTemplate(self::$tpl_dir . '/' . static::$type . '/header_right.html', array(
                'object_name' => $object_name
            ));
        }
    }

    protected function renderContent()
    {
        foreach ($this->sections as $section) {
            if (isset($section['type'])) {
                $type = $section['type'];
                if (isset($section[$type])) {
                    $method = 'render' . ucfirst($type) . 'Section';
                    if (method_exists($this, $method)) {
                        $this->{$method}($section['type']);
                    }
                }
            }
        }
    }
}
