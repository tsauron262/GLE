<?php

class BC_Filter extends BimpComponent
{

    public $component_name = 'Filtre';
    public static $type = 'filter';
    public static $config_required = false;
    public $identifier = '';
    public $values = array();
    public $excluded_values = array();
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
        ),
        'user'       => array()
    );
    public static $periods = array(
        'n' => 'Y',
        'm' => 'M',
        'j' => 'D'
    );

    public function __construct(BimpObject $object, $params, $values = array(), $path = '', $excluded_values = array())
    {
        $this->params_def['type'] = array();
        $this->params_def['label'] = array();
        $this->params_def['open'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['default_values'] = array('data_type' => 'array', 'compile' => true, 'default' => array());
        $this->params_def['default_excluded_values'] = array('data_type' => 'array', 'compile' => true, 'default' => array());
        $this->params_def['add_btn'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['exclude_btn'] = array('data_type' => 'bool', 'default' => 1);

        $this->values = $values;
        $this->excluded_values = $excluded_values;

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        parent::__construct($object, $params['name'], $path);

        if (empty($values) && empty($this->excluded_values) && (!empty($this->params['default_values']) || !empty($this->params['default_excluded_values']))) {
            $this->values = $this->params['default_values'];
            $this->excluded_values = $this->params['default_excluded_values'];
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
            case 'user':
                if ($this->params['type'] === 'user' && $value === 'current') {
                    $label = 'Utilisateur connecté';
                }
                $label = $value;
                break;

            case 'value_part':
                $part = (is_string($value) ? $value : (isset($value['value']) ? $value['value'] : ''));
                $part_type = (isset($value['part_type']) ? $value['part_type'] : $this->params['part_type']);
                $label = $this->getValuePartLabel($part, $part_type);
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

            case 'check_list':
                if (isset($this->params['items'][$value])) {
                    if (is_string($this->params['items'][$value])) {
                        $label = $this->params['items'][$value];
                    } elseif (isset($this->params['items'][$value]['label'])) {
                        $label = $this->params['items'][$value]['label'];
                    }
                }
                if (!$label) {
                    $label = 'Valeur: ' . $value;
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

    public static function getRangeSqlFilter($value, &$errors = array(), $is_dates = false, $excluded = false)
    {
        $filter = array();

        if (is_array($value)) {
            if ($is_dates && isset($value['period']) && is_array($value['period']) && !empty($value['period'])) {
                $value = self::convertDateRangePeriodValue($value['period']);
            }

            if (isset($value['max']) && $value['max'] !== '') {
                if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $value['max'])) {
                    $value['max'] .= ' 23:59:59';
                }
            }
            if (isset($value['min']) || isset($value['max'])) {
                if ($value['min'] !== '' && $value['max'] === '') {
                    if ($excluded) {
                        $filter = array(
                            'operator' => '<=',
                            'value'    => $value['min']
                        );
                    } else {
                        $filter = array(
                            'operator' => '>=',
                            'value'    => $value['min']
                        );
                    }
                } elseif ($value['max'] !== '' && $value['min'] === '') {
                    if ($excluded) {
                        $filter = array(
                            'operator' => '>=',
                            'value'    => $value['max']
                        );
                    } else {
                        $filter = array(
                            'operator' => '<=',
                            'value'    => $value['max']
                        );
                    }
                } else {
                    $filter = array(
                        'min' => $value['min'],
                        'max' => $value['max']
                    );
                    if ($excluded) {
                        $filter['not'] = 1;
                    }
                }
            } else {
                $errors[] = 'Valeurs minimales et maximales absentes';
            }
        } else {
            $errors[] = 'Valeur invalide: "' . $value . '"';
        }

        return $filter;
    }

    public static function getValuePartSqlFilter($value, $part_type, $excluded = false)
    {
        if (is_array($value) && isset($value['value'])) {
            $value = (isset($value['value']) ? $value['value'] : '');
            $part_type = (isset($value['part_type']) ? $value['part_type'] : $part_type);
        }
        
        $value = (string) $value;
        $filter = array(
            'part_type' => $part_type,
            'part'      => $value
        );
        
        if ($excluded) {
            $filter['not'] = 1;
        }
        return $filter;
    }

    public static function getValuePartLabel($value, $part_type)
    {
        $label = '';

        if (in_array($part_type, array('middle', 'end'))) {
            $label .= '... ';
        }

        if (!(string) $value && $part_type === 'full') {
            $label .= '(vide)';
        } else {
            $label .= $value;
        }
        if (in_array($part_type, array('middle', 'beginning'))) {
            $label .= ' ...';
        }
        return $label;
    }

    public static function getDateRangePeriodLabel($value)
    {
        $label = '';
        if (isset($value['qty']) && (int) $value['qty'] && isset($value['unit']) && (string) $value['unit']) {
            if (isset($value['mode']) && $value['mode'] === 'rel') {
                $label .= '-' . $value['qty'] . ' ';
                switch ($value['unit']) {
                    case 'n':
                        $label .= 'an' . ((int) $value['qty'] > 1 ? 's' : '');
                        break;

                    case 'm':
                        $label .= 'mois';
                        break;

                    case 'j':
                        $label .= 'jour' . ((int) $value['qty'] > 1 ? 's' : '');
                        break;
                }
            } else {
                if ((int) $value['qty'] === 1) {
                    switch ($value['unit']) {
                        case 'n':
                            $label .= 'Année en cours';
                            break;

                        case 'm':
                            $label .= 'Mois en cours';
                            break;

                        case 'j':
                            $label .= 'Ajourd\'hui';
                            break;
                    }
                } else {
                    $label = $value['qty'];
                    switch ($value['unit']) {
                        case 'n':
                            $label .= ' dernières années';
                            break;

                        case 'm':
                            $label .= ' derniers mois';
                            break;

                        case 'j':
                            $label .= ' derniers jours';
                            break;
                    }
                }
            }
        }

        if (!$label) {
            $label = '<span class="danger">Période invalide</span>';
        }

        if (isset($value['offset_qty']) && (int) $value['offset_qty'] && isset($value['offset_unit']) && (string) $value['offset_unit']) {
            $label .= ' (-' . $value['offset_qty'] . ' ';
            switch ($value['offset_unit']) {
                case 'n':
                    $label .= 'an' . ((int) $value['offset_qty'] > 1 ? 's' : '');
                    break;

                case 'm':
                    $label .= 'mois';
                    break;

                case 'j':
                    $label .= 'jour' . ((int) $value['offset_qty'] > 1 ? 's' : '');
                    break;
            }
            $label .= ')';
        }

        $label .= '<br/>';

        return $label;
    }

    public static function convertDateRangePeriodValue($value)
    {
        $from = '';
        $to = '';


        if (isset($value['qty']) && (int) $value['qty'] && isset($value['unit']) && (string) $value['unit']) {
            if (!isset($value['mode']) || !(string) $value['mode']) {
                $value['mode'] = 'abs';
            }

            $qty = (int) $value['qty'];

            if ($value['mode'] === 'abs') {
                $qty -= 1;
            }

            $interval = '';
            if ($qty > 0) {
                $interval = 'P' . $qty . self::$periods[$value['unit']];
            }

            $offset = '';
            if (isset($value['offset_qty']) && (int) $value['offset_qty'] > 0 && isset($value['offset_unit']) && (string) $value['offset_unit']) {
                $offset = 'P' . $value['offset_qty'] . self::$periods[$value['offset_unit']];
            }

            $dt_from = new DateTime();
            $dt_to = new DateTime();

            if ($interval) {
                $dt_from->sub(new DateInterval($interval));
            }

            if ($offset) {
                $dt_to->sub(new DateInterval($offset));
                $dt_from->sub(new DateInterval($offset));
            }

            $from = $dt_from->format('Y-m-d');
            $to = $dt_to->format('Y-m-d');

            if ($value['mode'] === 'abs') {
                $dt_now = new DateTime();
                switch ($value['unit']) {
                    case 'n':
                        $from = $dt_from->format('Y') . '-01-01';
                        if ($dt_to->format('Y') < $dt_now->format('Y')) {
                            $to = $dt_to->format('Y') . '-12-31';
                        }
                        break;
                    case 'm':
                        $from = $dt_from->format('Y-m') . '-01';
                        if ($dt_to->format('Y-m') < $dt_now->format('Y-m')) {
                            $to = $dt_to->format('Y-m') . '-01';
                            $dt_to = new DateTime($to);
                            $dt_to->add(new DateInterval('P1M'));
                            $dt_to->sub(new DateInterval('P1D'));
                            $to = $dt_to->format('Y-m-d');
                        }
                        break;
                }
            }

            $from .= ' 00:00:00';
            $to .= ' 23:59:59';
        }

        return array(
            'min' => $from,
            'max' => $to
        );
    }

    public static function getConvertedValues($filter_type, $values)
    {
        foreach ($values as $idx => $value) {
            switch ($filter_type) {
                case 'value_part':
                    $part = (is_string($value) ? $value : (isset($value['value']) ? $value['value'] : ''));
                    $part_type = (isset($value['part_type']) ? $value['part_type'] : "middle");//$this->params['part_type']);
                    $values[$idx] = array(
                        'value'     => $part,
                        'part_type' => $part_type
                    );
                    break;

                case 'date_range':
                    if (isset($value['period']) && is_array($value['period']) && !empty($value['period'])) {
                        $values[$idx] = self::convertDateRangePeriodValue($value['period']);
                    }
                    break;

                case 'user':
                    if ($value === 'current') {
                        global $user;
                        if (!BimpObject::objectLoaded($user)) {
                            unset($values[$idx]);
                            break;
                        }
                        $values[$idx] = (int) $user->id;
                    }
                    break;
            }
        }

        return $values;
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

        if ($this->params['type'] === 'check_list') {
            $html .= ' onopen="hideFiltersValues($(this))"';
            $html .= ' onclose="showFiltersValues($(this))"';
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

        $values_hidden = false;

        if ($this->params['type'] === 'check_list' && $this->params['open']) {
            $values_hidden = true;
        }

        $html .= '<div class="bimp_filter_values_container"' . ($values_hidden ? ' style="display: none"' : '') . '>';
        foreach ($this->values as $value) {
            $html .= $this->renderFilterValue($value, false);
        }

        foreach ($this->excluded_values as $value) {
            $html .= $this->renderFilterValue($value, true);
        }
        $html .= '</div>';

        $html .= '</div>';

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderFilterValue($value, $excluded = false)
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
                $html .= '<div class="bimp_filter_value_default' . ($excluded ? ' excluded' : '') . '">';
                $html .= 'Par défaut: <br/>';
                $html .= $label;
                $html .= '</div>';
            } else {
                $html .= '<div class="bimp_filter_value' . ($excluded ? ' excluded' : '') . '" data-value="' . htmlentities($value) . '" onclick="editBimpFilterValue($(this))">';
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

    public function renderValuePartInput($input_name, $add_btn_html)
    {
        $html = '';

        $html .= BimpInput::renderInput('select', $input_name . '_part_type', $this->params['part_type'], array(
                    'options' => array(
                        'middle'    => 'Contient',
                        'full'      => 'Est égal à',
                        'beginning' => 'Commence par',
                        'end'       => 'Fini par'
                    )
        ));

        $html .= BimpInput::renderInput('text', $input_name, '');
        $html .= $add_btn_html;

        return $html;
    }

    public function renderDateRangeInput($input_type, $input_name, $add_btn_html)
    {
        $html = '';

        if ($input_type === 'date_range') {
            $html .= '<div class="bimp_filter_date_range_period">';
            $html .= '<div style="margin-top: 5px">';
            $html .= 'Période passée: <br/>';
            $html .= BimpInput::renderInput('qty', $input_name . '_period_qty', 0, array(
                        'extra_class' => 'bimp_filter_date_range_period_qty',
                        'data'        => array(
                            'data_type' => 'number',
                            'min'       => 0,
                            'max'       => 'none',
                            'unsigned'  => 1,
                            'decimals'  => 0
                        )
            ));
            $html .= BimpInput::renderInput('select', $input_name . '_period_unit', 'y', array(
                        'extra_class' => 'bimp_filter_date_range_period_unit ',
                        'options'     => array(
                            'n' => 'Année(s)',
                            'm' => 'Mois',
                            'j' => 'Jour(s)',
                        )
            ));
            $html .= '</div>';
            $html .= '<div style="margin-top: 5px">';
            $html .= 'Décalage (-): <br/>';
            $html .= BimpInput::renderInput('qty', $input_name . '_period_offset_qty', 0, array(
                        'extra_class' => 'bimp_filter_date_range_offset_qty',
                        'data'        => array(
                            'data_type' => 'number',
                            'min'       => 0,
                            'max'       => 'none',
                            'unsigned'  => 1,
                            'decimals'  => 0
                        )
            ));
            $html .= BimpInput::renderInput('select', $input_name . '_period_offset_unit', 'y', array(
                        'extra_class' => 'bimp_filter_date_range_offset_unit ',
                        'options'     => array(
                            'n' => 'Année(s)',
                            'm' => 'Mois',
                            'j' => 'Jour(s)',
                        )
            ));
            $html .= '</div>';
            $html .= '<div style="margin-top: 5px">';
            $html .= 'Mode:';
            $html .= BimpInput::renderInput('select', $input_name . '_period_mode', 'abs', array(
                        'extra_class' => 'bimp_filter_date_range_period_mode ',
                        'options'     => array(
                            'abs' => 'Absolu',
                            'rel' => 'Relatif'
                        )
            ));
            $html .= '</div>';

            $html .= '<div style="text-align: right; margin-top: 2px">';
            if ((int) $this->params['exclude_btn']) {
                $html .= '<button type="button" class="btn btn-default-danger btn-small" onclick="addFieldFilterDateRangerPeriod($(this), true)">';
                $html .= BimpRender::renderIcon('fas_times-circle', 'iconLeft') . 'Exclure';
                $html .= '</button>';
            }
            if ((int) $this->params['add_btn']) {
                $html .= '<button type="button" class="btn btn-default btn-small" onclick="addFieldFilterDateRangerPeriod($(this), false)">';
                $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter';
                $html .= '</button>';
            }
            $html .= '</div>';

            $html .= '</div>';
        }

        $html .= BimpInput::renderInput($input_type, $input_name);
        $html .= $add_btn_html;

        return $html;
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
