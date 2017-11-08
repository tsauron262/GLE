<?php

class BimpInput
{

    public static function renderInput($type, $field_name, $value = '', $options = array(), $form = null, $option = null, $input_id = null)
    {
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
                $html .= '<input type="text" id="' . $field_name . '" name="' . $field_name . '" value="' . $value . '"/>';
                break;

            case 'textarea':
                if (!isset($options['rows'])) {
                    $options['rows'] = 3;
                }
                $html .= '<textarea id="' . $field_name . '" rows="' . $options['rows'] . '" name="' . $field_name . '">' . $value . '</textarea>';
                break;

            case 'switch':
                $html .= '<select class="switch" id="' . $field_name . '" name="' . $field_name . '">';
                $html .= '<option value="1"' . ($value ? ' selected' : '') . '>OUI</option>';
                $html .= '<option value="0"' . (!$value ? ' selected' : '') . '>NON</option>';
                $html .= '</select>';
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
                $html .= $form->select_company((int) $value, $field_name, '', '', 0, 0, array(), 20);
                break;

            case 'search_user':
                $html .= $form->select_dolusers((int) $value, $field_name, 0);
                break;

            case 'date':
            case 'datetime':
            case 'date_range':
                if (isset($options['format'])) {
                    $format = $options['format'];
                } else {
                    $format = 'Y-m-d H:i';
                }

                if (is_null($value)) {
                    $value = '';
                }

                if ($type === 'date_range') {
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
                                $DT->add(new DateInterval('P1D'));
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
                    $html .= self::renderDatePickerInput($field_name . '_from', $value['from'], $options, $input_id.'_from');
                    $html .= '</div>';

                    $html .= '<div class="input-group">';
                    $html .= '<span class="input-group-addon">Au</span>';
                    $html .= self::renderDatePickerInput($field_name . '_to', $value['to'], $options, $input_id.'_to');
                    $html .= '</div>';
                } else {
                    $html .= self::renderDatePickerInput($field_name, $value, $options);
                }
                break;

            default:
                $html .= '<p class="alert alert-danger">Erreur technique: type d\'input invalide pour le champ "' . $field_name . '"</p>';
                break;
        }
        return $html;
    }

    public static function renderDatePickerInput($input_name, $value = '', $options = array(), $input_id = null)
    {
        if (is_null($input_id)) {
            $input_id = $input_name;
        }

        if ($value) {
            if (preg_match('/^(\d{4})\-(\d{2})\-(\d{2}) (\d{2}):(\d{2}):(\d{2})?$/', $value)) {
                $DT = new DateTime($value);
                $value = $DT->format('d/m/Y H:i');
            }
        }
        $html = '';

        $html .= '<input type="hidden" class="datepicker_value" id="' . $input_id . '" name="' . $input_name . '" value="' . $value . '"/>';
        $html .= '<input type="text" class="form-control bs_datetimepicker" id="' . $input_id . '_bs_dt_picker" name="' . $input_name . '_picker"/>';
        $html .= '<script type="text/javascript">';
        $html .= "$('#" . $input_id . "_bs_dt_picker').datetimepicker({";
        $html .= "locale: 'fr',";
        $html .= "format: 'Do MMMM YYYY HH:mm:ss',";
        $html .= "showTodayButton: true,";
        $html .= "}); ";
        $html .= "$('#" . $input_id . "_bs_dt_picker').on('dp.change', function(e) {";
        $html .= "if (e.date) {";
        $html .= "$('#" . $input_id . "').val(e.date.format('YYYY-MM-DD HH:mm:ss')).change();";
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
                $html .= '<input type="text" class="search_list_input" value="' . $search . '" onkeyup="searchObjectList($(this));"';
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
}
