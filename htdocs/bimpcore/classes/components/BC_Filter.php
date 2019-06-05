<?php

class BC_Filter extends BimpComponent
{

    public $component_name = 'Filtre';
    public static $type = 'filter';
    public static $config_required = false;
    public $identifier = '';
    public $values = array();
    public $data = array();
    public static $type_params_def = array(
        'value'      => array(),
        'value_part' => array(
            'part_type' => array('default' => 'middle')
        ),
        'range'      => array(),
        'check_list' => array(
            'items' => array('data_type' => 'array')
        )
    );

    public function __construct(BimpObject $object, $params, $values = array(), $path = '')
    {
        $this->params_def['type'] = array();
        $this->params_def['label'] = array();
        $this->params_def['open'] = array('data_type' => 'bool', 'default' => 0);

        $this->values = $values;

        parent::__construct($object, $params['name'], $path);

        if (isset($params['open'])) {
            $this->params['open'] = (int) $params['open'];
        }

        if (!is_null($this->object)) {
            $this->identifier .= $this->object->object_name;
        }

        $this->identifier .= '_' . ($this->name ? $this->name . '_' : '') . 'filter';
    }

    public function getFilterValueLabel($value)
    {
        $label = '';

        switch ($this->params['type']) {
            case 'value':
                $label = $value;
                break;

            case 'value_part':
                if (in_array($this->params['part_type'], array('beginning', 'middle'))) {
                    $label .= '... ';
                }
                $label .= $value;
                if (in_array($this->params['part_type'], array('middle', 'end'))) {
                    $label .= ' ...';
                }
                break;

            case 'range':
            case 'date_range':
                if (is_array($value) && (isset($value['min']) || isset($value['max']))) {
                    $label .= 'Min: <strong>';
                    if (!isset($value['min']) || $value['min'] === '') {
                        $label .= '-&infin;';
                    } else {
                        $label .= $value['min'];
                    }

                    $label .= '</strong><br/>Max: <strong>';

                    if (!isset($value['max']) || $value['max'] === '') {
                        $label .= '&infin;';
                    } else {
                        $label .= $value['max'];
                    }

                    $label .= '</strong>';
                } else {
                    $label = '<span class="danger">Valeurs invalides</valeur>';
                }
                break;
        }

        return $label;
    }

    public function getSqlFilters(&$filters = array(), &$joins = array())
    {
        return array();
    }

    public function renderHtml()
    {
        $html = '';
        $html = parent::renderHtml();

        if (count($this->errors)) {
            return $html;
        }

        $html .= '<div id="' . $this->identifier . '" class="bimp_filter_container foldable_container ' . ($this->params['open'] ? 'open' : 'closed') . '" data-type="' . $this->params['type'] . '"';
        if ($this->params['type'] === 'value_part') {
            $html .= ' data-part_type="' . $this->params['part_type'] . '"';
        }

        foreach ($this->data as $data_name => $data_value) {
            $html .= ' data-' . $data_name . '="' . $data_value . '"';
        }

        $html .= '>';

        $html .= '<div class="bimp_filter_caption foldable_caption">';
        $html .= $this->params['label'];
        $html .= '<span class="foldable-caret"></span>';
        $html .= '</div>';

        $html .= '<div class="bimp_filter_content foldable_content"' . (!$this->params['open'] ? ' style="display: none"' : '') . '>';

        $html .= '<div class="bimp_filter_input_container">';
        $html .= $this->renderAddInput();
        $html .= '</div>';

        $html .= '</div>';

        $html .= '<div class="bimp_filter_values_container">';
        if (in_array($this->params['type'], array('value', 'value_part', 'range', 'date_range'))) {
            foreach ($this->values as $value) {
                $html .= $this->renderFilterValue($value);
            }
        }
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    public function renderFilterValue($value)
    {
        $html = '';

        $label = $this->getFilterValueLabel($value);

        if (!$label) {
            return '';
        }

        if (is_array($value)) {
            $value = json_encode($value);
        }

        $html .= '<div class="bimp_filter_value" data-value="' . htmlentities($value) . '" onclick="editBimpFilterValue($(this))">';
        $html .= '<span class="bimp_filter_value_remove_btn" onclick="removeBimpFilterValue(event, $(this));">';
        $html .= BimpRender::renderIcon('fas_times');
        $html .= '</span>';
        $html .= $label;
        $html .= '</div>';

        return $html;
    }

    public function renderAddInput()
    {
        return '';
    }

    public static function getDefaultTypeFromDataType($data_type)
    {
        switch ($data_type) {
            case 'color':
            case 'bool':
            case 'id_object':
            case 'id_parent':
                return 'value';

            case 'string':
            case 'text':
            case 'html':
                return 'value_part';

            case 'int':
            case 'float':
            case 'qty':
            case 'percent':
            case 'money':
                return 'range';

            case 'date':
            case 'time':
            case 'datetime':
                return 'date_range';
        }
    }
}
