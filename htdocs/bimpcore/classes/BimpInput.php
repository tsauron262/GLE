<?php

class BimpInput
{

    public static function renderInput($type, $field_name, $value = '', $options = array(), $form = null, $option = null, $input_id = null)
    {
        if (is_null($input_id)) {
            $input_id = $field_name;
        }
        $html = '';
        if (is_null($form)) {
            global $db;
            $form = new Form($db);
        }
        switch ($type) {
            case 'hidden':
                $html .= '<input type="hidden" id="' . $field_name . '" name="' . $field_name . '" value="' . $value . '"/>';
                break;

            case 'text':
                if ((isset($options['addon_left']) && $options['addon_left']) ||
                        (isset($options['addon_right']) && $options['addon_right'])) {
                    $html .= '<div class="inputGroupContainer">';
                    $html .= '<div class="input-group">';

                    if (isset($options['addon_left']) && $options['addon_left']) {
                        $html .= '<span class="input-group-addon">' . $options['addon_left'] . '</span>';
                    }

                    $html .= '<input type="text" id="' . $field_name . '" name="' . $field_name . '" value="' . $value . '"/>';

                    if (isset($options['addon_right']) && $options['addon_right']) {
                        $html .= '<span class="input-group-addon">' . $options['addon_right'] . '</span>';
                    }

                    $html .= '</div>';
                    $html .= '</div>';
                } else {
                    $html .= '<input type="text" id="' . $field_name . '" name="' . $field_name . '" value="' . $value . '"/>';
                }
                break;

            case 'textarea':
                if (!isset($options['rows'])) {
                    $options['rows'] = 3;
                }
                if (!isset($options['auto_expand'])) {
                    $options['auto_expand'] = false;
                }
                if (!isset($options['note'])) {
                    $options['note'] = false;
                }
                $html .= '<textarea id="' . $field_name . '" rows="' . $options['rows'] . '" name="' . $field_name . '"';
                if ($options['auto_expand'] || $options['note']) {
                    $html .= ' class="' . ($options['auto_expand'] ? 'auto_expand' : '') . ($options['note'] ? ' note' : '') . '"';
                    $html .= ' data-min_rows="' . $options['rows'] . '"';
                }
                $html .= '>' . $value . '</textarea>';
                break;

            case 'switch':
                $html .= '<select class="switch" id="' . $field_name . '" name="' . $field_name . '">';
                $html .= '<option value="1"' . ($value ? ' selected' : '') . '>OUI</option>';
                $html .= '<option value="0"' . (!$value ? ' selected' : '') . '>NON</option>';
                $html .= '</select>';
                break;

            case 'toggle':
                if (!isset($options['toggle_on']) || !$options['toggle_on']) {
                    $options['toggle_on'] = 'OUI';
                }
                if (!isset($options['toggle_off']) || !$options['toggle_off']) {
                    $options['toggle_off'] = 'NON';
                }
                $input_id .= rand(0, 999999);
                $html .= '<input type="hidden" class="toggle_value" value="' . ($value ? '1' : '0') . '" name="' . $field_name . '" id="' . $input_id . '"/>';
                $html .= '<input type="checkbox" class="toggle" id="' . $input_id . '_toggle" ' . ($value ? ' checked' : '') . '/>';
                $html .= '<span class="toggle-label-on">' . $options['toggle_off'] . '</span>';
                $html .= '<label class="toggle-slider" for="' . $input_id . '_toggle"></label>';
                $html .= '<span class="toggle-label-on">' . $options['toggle_on'] . '</span>';
                break;

            case 'select':
                if (is_null($options['options']) || !is_array($options['options'])) {
                    $options['options'] = array();
                }

                if (count($options['options'])) {
                    $html .= '<select id="' . $field_name . '" name="' . $field_name . '">';
                    foreach ($options['options'] as $option_value => $option) {
                        if (is_array($option)) {
                            if (isset($option['label'])) {
                                $label = $option['label'];
                            } elseif (isset($option['value'])) {
                                $label = $option['value'];
                            } else {
                                $label = $option_value;
                            }
                        } else {
                            $label = $option;
                        }
                        $html .= '<option value="' . $option_value . '"';
                        if ($value == $option_value) {
                            $html .= ' selected';
                        }
                        $html .= '>' . $label . '</option>';
                    }
                    $html .= '</select>';
                } else {
                    $html .= '<p class="alert alert-warning">Aucune option disponible</p>';
                }
                break;

            case 'search_product':
                global $conf;
                ob_start();
                $form->select_produits((int) $value, $field_name, 0, $conf->product->limit_size, 0, -1, 2, '', 1);
                $html .= ob_get_clean();
                break;

            case 'search_societe':
                $filter = '';
                if (isset($options['type']) && $options['type']) {
                    switch ($options['type']) {
                        case 'customer':
                            $filter = 's.client != 0';
                            break;

                        case 'supplier':
                            $filter = 's.fournisseur != 0';
                            break;
                    }
                }

                $html .= $form->select_company((int) $value, $field_name, $filter, '', 0, 0, array(), 20);
                break;

            case 'search_user':
                $html .= $form->select_dolusers((int) $value, $field_name, 0);
                break;

            case 'check_list':
                if (!isset($options['items']) || !count($options['items'])) {
                    $html = BimpRender::renderAlerts('Aucun élément diponible', 'warning');
                } else {
                    if (!is_array($value)) {
                        $value = array($value);
                    }

                    $html = '<div class="check_list_container">';
                    $i = 1;
                    foreach ($options['items'] as $item) {
                        $i++;
                        $html .= '<div class="check_list_item">';
                        $html .= '<input type="checkbox" name="' . $field_name . '[]" value="' . $item['value'] . '" id="' . $input_id . '_' . $i . '"';
                        if ($value == $item['value']) {
                            $html .= ' checked="1"';
                        }
                        $html .= '/>';
                        $html .= '<label for="' . $input_id . '_' . $i . '">';
                        $html .= $item['label'];
                        $html .= '</label>';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                }
                break;

            case 'time':
            case 'date':
            case 'datetime':
            case 'time_range':
            case 'date_range':
            case 'datetime_range':
                if (isset($options['format'])) {
                    $format = $options['format'];
                } else {
                    switch ($type) {
                        case 'time':
                        case 'time_range':
                            $format = 'H:i:s';
                            break;

                        case 'date':
                        case 'date_range':
                            $format = 'Y-m-d';
                            break;

                        case 'datetime':
                        case 'datetime_range':
                            $format = 'Y-m-d H:i:s';
                            break;
                    }
                }

                if (is_null($value)) {
                    $value = '';
                }

                if (in_array($type, array('time_range', 'date_range', 'datetime_range'))) {
                    if (!is_array($value)) {
                        $value = array(
                            'from' => '',
                            'to'   => $value
                        );
                    }
                    if (!isset($value['to']) || !$value['to']) {
                        if (isset($value['from']) && $value['from']) {
                            $DT = new DateTime($value['from']);
                            if (isset($value['to_interval'])) {
                                $DT->add($value['to_interval']);
                            } else {
                                switch ($type) {
                                    case 'time_range':
                                        $DT->add(new DateInterval('PT1H'));
                                        break;

                                    case 'date_range':
                                    case 'datetime_range':
                                        $DT->add(new DateInterval('P1D'));
                                        break;
                                }
                            }
                            $value['to'] = $DT->format($format);
                            unset($DT);
                        }
                    }

                    if (!isset($value['from'])) {
                        $value['from'] = '';
                    }

                    if (!$value['from'] && isset($value['from_interval'])) {
                        $DT = new DateTime($value['to']);
                        $DT->sub(new DateInterval($value['from_interval']));
                        $value['from'] = $DT->format($format);
                        unset($DT);
                    }

                    $html .= '<div class="input-group">';
                    $html .= '<span class="input-group-addon">Du</span>';
                    $html .= self::renderDatePickerInput($field_name . '_from', $value['from'], $options, $input_id . '_from', str_replace('_range', '', $type));
                    $html .= '</div>';

                    $html .= '<div class="input-group">';
                    $html .= '<span class="input-group-addon">Au</span>';
                    $html .= self::renderDatePickerInput($field_name . '_to', $value['to'], $options, $input_id . '_to', str_replace('_range', '', $type));
                    $html .= '</div>';
                } else {
                    $html .= self::renderDatePickerInput($field_name, $value, $options, $input_id, $type);
                }
                break;

            case 'timer':
                if (is_null($value)) {
                    $value = 0;
                }
                $timer = BimpTools::getTimeDataFromSeconds((int) $value);

                $html .= '<div class="timer_input">';
                $html .= '<input type="hidden" name="' . $field_name . '" id="' . $input_id . '" value="' . $value . '"/>';

                $html .= '<input type="text" class="' . $field_name . '_time_value time_input_value" value="' . (int) $timer['days'] . '" name="' . $field_name . '_days"/>';
                $html .= '<span>j</span>';

                $html .= '<select name="' . $field_name . '_hours" class="' . $field_name . '_time_value time_input_value">';
                for ($i = 0; $i < 24; $i++) {
                    $html .= '<option value="' . $i . '"' . ((int) $i === (int) ($timer['hours']) ? ' selected' : '') . '>' . $i . '</option>';
                }
                $html .= '</select>';
                $html .= '<span>h</span>';

                $html .= '<select name="' . $field_name . '_minutes" class="' . $field_name . '_time_value time_input_value">';
                for ($i = 0; $i < 60; $i++) {
                    $html .= '<option value="' . $i . '"' . ((int) $i === (int) ($timer['minutes']) ? ' selected' : '') . '>' . $i . '</option>';
                }
                $html .= '</select>';
                $html .= '<span>min</span>';

                $html .= '<select name="' . $field_name . '_secondes" class="' . $field_name . '_time_value time_input_value">';
                for ($i = 0; $i < 60; $i++) {
                    $html .= '<option value="' . $i . '"' . ((int) $i === (int) ($timer['secondes']) ? ' selected' : '') . '>' . $i . '</option>';
                }
                $html .= '</select>';
                $html .= '<span>sec</span>';
                $html .= '</div>';

                $html .= '<script type="text/javascript">';
                $html .= '$(\'.' . $field_name . '_time_value\').each(function() {';
                $html .= '$(this).change(function() {';
                $html .= 'updateTimerInput($(this), \'' . $field_name . '\');';
                $html .= '});';
                $html .= '});';
                $html .= '</script>';
                break;

            default:
                $html .= '<p class="alert alert-danger">Erreur technique: type d\'input invalide pour le champ "' . $field_name . '"</p>';
                break;
        }
        return $html;
    }

    public static function renderDatePickerInput($input_name, $value = '', $options = array(), $input_id = null, $type = "datetime")
    {
        if (is_null($input_id)) {
            $input_id = $input_name;
        }

        $display_js_format = '';
        $js_format = '';
        $php_format = '';
        $dt_value = null;

        switch ($type) {
            case 'time':
                $display_js_format = 'HH:mm:ss';
                $js_format = 'HH:mm:ss';
                $php_format = 'H:i:s';
                if ($value) {
                    if (preg_match('(\d{2}):(\d{2}):(\d{2})?$/', $value)) {
                        $dt_value = new DateTime($value);
                    }
                }
                break;

            case 'date':
                $display_js_format = 'Do MMMM YYYY';
                $js_format = 'YYYY-MM-DD';
                $php_format = 'Y-m-d';
                if ($value) {
                    if (preg_match('/^(\d{4})\-(\d{2})\-(\d{2})$/', $value)) {
                        $dt_value = new DateTime($value);
                    }
                }
                break;

            case 'datetime':
                $display_js_format = 'Do MMMM YYYY HH:mm:ss';
                $js_format = 'YYYY-MM-DD HH:mm:ss';
                $php_format = 'Y-m-d H:i:s';
                if ($value) {
                    if (preg_match('/^(\d{4})\-(\d{2})\-(\d{2}) (\d{2}):(\d{2}):(\d{2})?$/', $value)) {
                        $dt_value = new DateTime($value);
                    }
                }
                break;
        }

        $html = '';

        $html .= '<input type="hidden" class="datepicker_value" id="' . $input_id . '" name="' . $input_name . '" value="';
        if (!is_null($dt_value)) {
            $html .= $dt_value->format($php_format);
        }
        $html .= '"/>';
        $html .= '<input type="text" class="form-control bs_datetimepicker" id="' . $input_id . '_bs_dt_picker" name="' . $input_name . '_picker"/>';
        $html .= '<script type="text/javascript">';
        $html .= "$('#" . $input_id . "_bs_dt_picker').datetimepicker({";
        $html .= "locale: 'fr',";
        $html .= "format: '" . $display_js_format . "',";
//        if (!is_null($dt_value)) {
//            $html .= "defaultDate: moment('" . $dt_value->format($php_format) . "'),";
//        }
        $html .= "showTodayButton: " . (isset($options['display_now']) && $options['display_now'] ? "true" : "false");
        $html .= "}); ";
        if (!is_null($dt_value)) {
            $html .= "var cur_date = moment('" . $dt_value->format($php_format) . "'); ";
            $html .= "$('#" . $input_id . "_bs_dt_picker').data('DateTimePicker').date(cur_date); ";
        }
        $html .= "$('#" . $input_id . "_bs_dt_picker').on('dp.change', function(e) {";
        $html .= "if (e.date) {";
        $html .= "$('#" . $input_id . "').val(e.date.format('" . $js_format . "')).change();";
        $html .= "} else {";
        $html .= "$('#" . $input_id . "').val('').change();";
        $html .= "}";
        $html .= "})";
        $html .= '</script>';
        return $html;
    }

    public static function renderSwitchOptionsInput($input_name, $options, $value = '', $input_id = null, $vertical = false)
    {
        $html = '<div class="switchInputContainer" data-input_name="' . $input_name . '">';
        $html .= '<input type="hidden" name="' . $input_name . '"';
        if (!is_null($input_id)) {
            $html .= ' id="' . $input_id . '"';
        }
        $html .= ' value="' . $value . '"/>';

        $html .= '<div class="btn-group' . ($vertical ? '-vertical' : '') . ' btn-group-xs" role="group">';
        foreach ($options as $option_value => $option_label) {
            $html .= '<button type="button" class="switchOption btn btn-default' . (($value == $option_value) ? ' selected' : '') . '"';
            $html .= ' onclick="selectSwitchOption($(this))"';
            $html .= ' data-value="' . $option_value . '">';
            $html .= $option_label . '</button>';
        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public static function renderSearchListInput(BimpObject $object, $config_path, $field_name, $value = '', $option = null)
    {
        if (is_null($value)) {
            $value = '';
        }

        if (!$object->config->isDefined($config_path . '/input/search_list')) {
            $html .= BimpRender::renderAlerts('Paramètres de recherche non définis pour le champ "' . $field_name . '"');
        } else {
            $path = $config_path . '/input/search_list/';
            $table = $object->getConf($path . 'table', null, true);
            $join = $object->getConf($path . 'join', '');
            $join_on = $object->getConf($path . 'join_on', '');
            $field_return_label = $object->getConf($path . 'field_return_label', null, true);
            $field_return_value = $object->getConf($path . 'field_return_value', null, true);
            $join_return_label = $object->getConf($path . 'join_return_label', '');

            if (is_null($table) || is_null($field_return_label) || is_null($field_return_value)) {
                $html .= BimpRender::renderAlerts('Configuration invalide pour le champ  "' . $field_name . '"');
            } else {
                $fields_search = array();
                if ($object->config->isDefined($path . 'options')) {
                    if (!is_null($option) && $object->config->isDefined($path . '/options/' . $option)) {
                        $fields_search = $object->getConf($path . 'options/' . $option . '/fields_search', null, true);
                        $join = $object->getConf($path . 'options/' . $option . '/join', '');
                        $join_on = $object->getConf($path . 'options/' . $option . '/join_on', '');
                        $join_return_label = $object->getConf($path . 'options/' . $option . '/join_return_label', '');
                    } else {
                        $options = $object->getConf($path . 'options', array(), true, 'array');
                        $html .= '<div class="searchListOptions optionsContainer">';
                        $html .= '<span class="displayPopupButton optionsButton" data-popup_id="' . $field_name . '_searchListOptionsPopup">Options de recherche</span>';
                        $html .= '<div id="' . $field_name . '_searchListOptionsPopup" class="tinyPopup searchListOptionsPopup">';
                        $switchOptions = array();
                        foreach ($options as $opt_name => $opt_params) {
                            $opt_label = $object->getConf($path . 'options/' . $opt_name . '/label', '', true);
                            $opt_fields_search = $object->getConf($path . 'options/' . $opt_name . '/fields_search', null, true);
                            $opt_join = $object->getConf($path . 'options/' . $opt_name . '/join', '');
                            $opt_join_on = $object->getConf($path . 'options/' . $opt_name . '/join_on', '');
                            $opt_join_return_label = $object->getConf($path . 'options/' . $opt_name . '/join_return_label', '');
                            $opt_help = $object->getConf($path . 'options/' . $opt_name . '/help', '');
                            if (!is_null($opt_fields_search) && !is_null($opt_label)) {
                                $html .= '<input type="hidden" id="searchList_' . $opt_name . '_fields_search" value="' . $opt_fields_search . '"/>';
                                if ($opt_join) {
                                    $html .= '<input type="hidden" id="searchList_' . $opt_name . '_join" value="' . $opt_join . '"/>';
                                }
                                if ($opt_join_on) {
                                    $html .= '<input type="hidden" id="searchList_' . $opt_name . '_join_on" value="' . $opt_join_on . '"/>';
                                }
                                if ($opt_join_on) {
                                    $html .= '<input type="hidden" id="searchList_' . $opt_name . '_join_return_label" value="' . $opt_join_return_label . '"/>';
                                }
                                if ($opt_help) {
                                    $html .= '<input type="hidden" id="searchList_' . $opt_name . '_help" value="' . htmlentities($opt_help) . '"/>';
                                }
                                $switchOptions[$opt_name] = $opt_label;
                            }
                            if ($opt_name === 'default') {
                                $fields_search = $opt_fields_search;
                                $join = $opt_join;
                                $join_on = $opt_join_on;
                                $join_return_label = $opt_join_return_label;
                            }
                        }
                        $html .= '<div class="title">Options de recherche</div>';
                        $html .= self::renderSwitchOptionsInput($field_name . '_search_list_option', $switchOptions, 'default', null, true);
                        $html .= '</div>';
                        $html .= '</div>';
                    }
                } else {
                    $fields_search = $object->getConf($path . 'fields_search', array(), true);
                }

                $search = '';
                if ($value) {
                    global $db;
                    $bdb = new BimpDb($db);
                    $search = $bdb->getValue($table, $fields_search[0], '`' . $field_return_value . '` = ' . (is_string($value) ? '\'' . $value . '\'' : $value));
                    if (is_null($search)) {
                        $search = '';
                    }
                    unset($bdb);
                }

                if (is_array($fields_search)) {
                    $fields_search = implode(',', $fields_search);
                }
                $html .= '<input type="hidden" name="' . $field_name . '" value="' . $value . '"/>';
                $html .= '<input type="text" name="' . $field_name . '_search" class="search_list_input" value="' . $search . '" onkeyup="searchObjectList($(this));"';
                $html .= ' data-table="' . $table . '"';
                $html .= ' data-join="' . $join . '"';
                $html .= ' data-join_on="' . $join_on . '"';
                $html .= ' data-fields_search="' . $fields_search . '"';
                $html .= ' data-field_return_label="' . $field_return_label . '"';
                $html .= ' data-field_return_value="' . $field_return_value . '"';
                $html .= ' data-join_return_label="' . $join_return_label . '"';
                $html .= '/>';
                $html .= '<i class="loading fa fa-spinner fa-spin"></i>';
                $html .= '<div class="search_input_results"></div>';
            }
        }
        return $html;
    }

    public static function renderMultipleValuesList(BimpObject $object, $field_name, $values, $label_input_name = null, $autosave = false)
    {
        if (!is_array($values)) {
            $value = $values;
            $values = array();
            if (is_string($value)) {
                $items = explode(',', $value);
                foreach ($items as $item) {
                    $values[$item] = $item;
                }
            } else {
                $values[$value] = $value;
            }
        }

        $value_input_name = $field_name . '_add_value';
        if (is_null($label_input_name)) {
            $label_input_name = $value_input_name;
        }

        if ($autosave) {
            if (!isset($object->id) || !$object->id) {
                $autosave = false;
            }
        }

        $html .= '<div class="inputMultipleValuesContainer"';
        if ($autosave) {
            $html .= ' data-module="' . $object->module . '"';
            $html .= ' data-object_name="' . $object->object_name . '"';
            $html .= ' data-id_object="' . $object->id . '"';
        }
        $html .= '>';

        $html .= '<div style="text-align: right">';
        $html .= '<button type="button" class="addValueBtn btn btn-primary" onclick="addMultipleInputCurrentValue($(this), \'' . $value_input_name . '\', \'' . $label_input_name . '\', ' . ($autosave ? 'true' : 'false') . ')">';
        $html .= '<i class="fa fa-plus-circle iconLeft"></i>';
        $html .= 'Ajouter';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div class="inputMultipleValues">';
        $html .= '<table>';
        $html .= '<thead></thead>';
        $html .= '<tbody>';
        foreach ($values as $value => $label) {
            $html .= '<tr>';
            $html .= '<td style="display: none">';
            $html .= '<input type="hidden" name="' . $field_name . '[]" value="' . $value . '"/>';
            $html .= '</td>';
            $html .= '<td>' . $label . '</td>';
            $html .= '<td><button type="button" class="btn btn-light-danger iconBtn" onclick="';
            if ($autosave) {
                $html .= 'var $button = $(this); deleteObjectMultipleValuesItem(\'' . $object->module . '\', \'' . $object->object_name . '\', ';
                $html .= $object->id . ', \'' . $field_name . '\', \'' . $value . '\', null, function() {';
                $html .= '$button.parent(\'td\').parent(\'tr\').fadeOut(250, function() {$(this).remove();});});';
            } else {
                $html .= '$(this).parent(\'td\').parent(\'tr\').fadeOut(250, function() {$(this).remove();});"';
            }
            $html .= '"><i class="fa fa-trash"></i></button></td>';
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }
}
