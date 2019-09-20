<?php

class BC_Filter extends BimpComponent
{

    public $component_name = 'Filtre';
    public static $type = 'filter';
    public static $config_required = false;
    public $identifier = '';
    public $values = array();
    public $is_default = false;
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
        $this->params_def['default_values'] = array('data_type' => 'array', 'compile' => true, 'default' => array());
        $this->values = $values;

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        parent::__construct($object, $params['name'], $path);

        if (empty($values) && !empty($this->params['default_values'])) {
            $this->values = $this->params['default_values'];
            $this->is_default = true;
        }

        if (isset($params['open'])) {
            $this->params['open'] = (int) $params['open'];
        }

        if (!is_null($this->object)) {
            $this->identifier .= $this->object->object_name;
        }

        $this->identifier .= '_' . ($this->name ? $this->name . '_' : '') . 'filter';

        $current_bc = $prev_bc;
    }

    public function getFilterValueLabel($value)
    {
        if (!$this->params['show']) {
            return '';
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

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

        $current_bc = $prev_bc;
        return $label;
    }

    public function getSqlFilters(&$filters = array(), &$joins = array())
    {
        return array();
    }

    public function renderHtml()
    {
        if (!$this->params['show']) {
            return '';
        }

        $html = '';
        $html = parent::renderHtml();

        if (count($this->errors)) {
            return $html;
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

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

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderFilterValue($value)
    {
        if (!$this->params['show']) {
            return '';
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html = '';

        $label = $this->getFilterValueLabel($value);

        if ($label) {
            if (is_array($value)) {
                $value = json_encode($value);
            }

            if ($this->is_default) {
                $html .= '<div class="bimp_filter_value_default">';
                $html .= 'Par défaut: <br/>';
                $html .= $label;
                $html .= '</div>';
            } else {
                $html .= '<div class="bimp_filter_value" data-value="' . htmlentities($value) . '" onclick="editBimpFilterValue($(this))">';
                $html .= '<span class="bimp_filter_value_remove_btn" onclick="removeBimpFilterValue(event, $(this));">';
                $html .= BimpRender::renderIcon('fas_times');
                $html .= '</span>';
                $html .= $label;
                $html .= '</div>';
            }
        }

        $current_bc = $prev_bc;
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
