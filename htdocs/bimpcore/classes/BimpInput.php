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
        $extra_class = isset($options['extra_class']) ? $options['extra_class'] : '';

        switch ($type) {
            case 'hidden':
                $html .= '<input type="hidden" id="' . $field_name . '" name="' . $field_name . '" value="' . $value . '" class="' . $extra_class . '"/>';
                break;

            case 'text':
                $data = '';
                if (isset($options['data'])) {
                    foreach ($options['data'] as $data_name => $data_value) {
                        $data .= ' data-' . $data_name . '="' . $data_value . '"';
                    }
                }
                if ((isset($options['addon_left']) && $options['addon_left']) ||
                        (isset($options['addon_right']) && $options['addon_right'])) {
                    $html .= '<div class="inputGroupContainer">';
                    $html .= '<div class="input-group">';

                    if (isset($options['addon_left']) && $options['addon_left']) {
                        $html .= '<span class="input-group-addon">' . $options['addon_left'] . '</span>';
                    }

                    $html .= '<input type="text" id="' . $input_id . '" name="' . $field_name . '" value="' . $value . '"';
                    if (isset($options['placeholder'])) {
                        $html .= ' placeholder="' . $options['placeholder'] . '"';
                    }
                    if (isset($options['style'])) {
                        $html .= ' style="' . $options['style'] . '"';
                    }
                    $html .= $data;
                    $html .= ' class="' . $extra_class . '"';
                    $html .= '/>';

                    if (isset($options['addon_right']) && $options['addon_right']) {
                        $html .= '<span class="input-group-addon">' . $options['addon_right'] . '</span>';
                    }

                    $html .= '</div>';
                    $html .= '</div>';
                } else {
                    $html .= '<input type="text" id="' . $input_id . '" name="' . $field_name . '" value="' . $value . '"';
                    if (isset($options['placeholder'])) {
                        $html .= ' placeholder="' . $options['placeholder'] . '"';
                    }
                    if (isset($options['style'])) {
                        $html .= ' style="' . $options['style'] . '"';
                    }
                    $html .= $data;
                    $html .= '/>';
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

                if (isset($options['maxlength']) && $options['maxlength']) {
                    $html .= '<p class="inputHelp" style="text-align: right">Caractères max: ' . $options['maxlength'] . '</p>';
                }

                $html .= '<textarea id="' . $input_id . '" rows="' . $options['rows'] . '" name="' . $field_name . '"';
                if ($options['auto_expand'] || $options['note']) {
                    $html .= ' class="' . ($options['auto_expand'] ? 'auto_expand' : '') . ($options['note'] ? ' note' : '') . ' ' . $extra_class . '"';
                    $html .= ' data-min_rows="' . $options['rows'] . '"';
                }
                if (isset($options['maxlength']) && $options['maxlength']) {
                    $html .= ' maxlength="' . (int) $options['maxlength'] . '"';
                }
                $html .= '>' . $value . '</textarea>';
                break;

            case 'html':
                if (!class_exists('DolEditor')) {
                    require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
                }
                $doleditor = new DolEditor($field_name, $value, '', 160, 'dolibarr_details', '', false, true, true, ROWS_4, '90%');
                $html .= $doleditor->Create(1);
                break;

            case 'switch':
                $html .= '<select class="switch ' . $extra_class . '" id="' . $input_id . '" name="' . $field_name . '">';
                $html .= '<option value="1"' . ($value ? ' selected' : '') . '>OUI</option>';
                $html .= '<option value="0"' . (!$value ? ' selected' : '') . '>NON</option>';
                $html .= '</select>';
                break;

            case 'toggle':
                $display_labels = isset($options['display_label']) ? (int) $options['display_labels'] : 1;

                if ($display_labels) {
                    if (!isset($options['toggle_on']) || !$options['toggle_on']) {
                        $options['toggle_on'] = 'OUI';
                    }
                    if (!isset($options['toggle_off']) || !$options['toggle_off']) {
                        $options['toggle_off'] = 'NON';
                    }
                }

                $input_id .= rand(0, 999999);
                $html .= '<div class="toggleContainer">';
                $html .= '<input type="hidden" class="toggle_value ' . $extra_class . '" value="' . ($value ? '1' : '0') . '" name="' . $field_name . '" id="' . $input_id . '"/>';
                $html .= '<input type="checkbox" class="toggle" id="' . $input_id . '_toggle" ' . ($value ? ' checked' : '') . '/>';
                if ($display_labels) {
                    $html .= '<span class="toggle-label-off">' . $options['toggle_off'] . '</span>';
                }
                $html .= '<label class="toggle-slider" for="' . $input_id . '_toggle"></label>';
                if ($display_labels) {
                    $html .= '<span class="toggle-label-on">' . $options['toggle_on'] . '</span>';
                }
                $html .= '</div>';
                break;

            case 'select':
                if (is_null($options['options']) || !is_array($options['options'])) {
                    $options['options'] = array();
                }

                if (count($options['options'])) {
                    $html .= '<select id="' . $input_id . '" name="' . $field_name . '" class="' . $extra_class . '">';
                    foreach ($options['options'] as $option_value => $option) {
                        $color = null;
                        if (is_array($option)) {
                            if (isset($option['label'])) {
                                $label = $option['label'];
                            } elseif (isset($option['value'])) {
                                $label = $option['value'];
                            } else {
                                $label = $option_value;
                            }
                            if (isset($option['color'])) {
                                $color = $option['color'];
                            } elseif (isset($option['classes'])) {
                                $color = BimpTools::getAlertColor($option['classes'][0]);
                            }
                        } else {
                            $label = $option;
                        }
                        $html .= '<option value="' . $option_value . '"';
                        if ($value == $option_value) {
                            $html .= ' selected';
                        }
                        if (!is_null($color)) {
                            $html .= ' data-color="' . $color . '" style="color: #' . $color . '"';
                        }
                        $html .= '>' . $label . '</option>';
                    }
                    $html .= '</select>';
                } else {
                    $html .= '<p class="alert alert-warning">Aucune option disponible</p>';
                }
                break;

            case 'select_payment':
                if (!isset($options['value_type'])) {
                    $option['value_type'] = 'id';
                }
                if (!isset($options['active_only'])) {
                    $options['active_only'] = 1;
                }
                $form->load_cache_types_paiements();
                $html .= '<select id="' . $input_id . '" name="' . $field_name . '" class="' . $extra_class . '">';
                foreach ($form->cache_types_paiements as $id_payment => $payment_data) {
                    if (!(int) $options['active_only'] || ((int) $options['active_only'] && (int) $payment_data['active'])) {
                        switch ($options['value_type']) {
                            case 'code':
                                $html .= '<option value="' . $payment_data['code'] . '" data-id_payment="' . $id_payment . '">' . $payment_data['label'] . '</option>';
                                break;

                            case 'id':
                            default:
                                $html .= '<option value="' . $id_payment . '" data-code="' . $payment_data['code'] . '">' . $payment_data['label'] . '</option>';
                                break;
                        }
                    }
                }
                $html .= '</select>';
                break;

            case 'search_product':
                global $conf;
                $filter_type = 0;
                if (isset($options['filter_type'])) {
                    switch ($options['filter_type']) {
                        case 'both':
                            $filter_type = '';
                            break;

                        case 'product':
                            $filter_type = 0;
                            break;

                        case 'service':
                            $filter_type = 1;
                    }
                }
                ob_start();
                $form->select_produits((int) $value, $field_name, $filter_type, $conf->product->limit_size, 0, -1, 2, '', 1);
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

            case 'search_contact':
                $html .= $form->selectcontacts(0, (int) $value, $field_name);
                break;

            case 'search_entrepot':
                if (!class_exists('FormProduct')) {
                    require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
                }
                global $db;
                $formProduct = new FormProduct($db);
                $html .= $formProduct->selectWarehouses((int) $value, $field_name);
                break;

            case 'search_country':
                $html .= $form->select_country((int) $value, $field_name);
                break;

            case 'search_state':
                if (!class_exists('FormCompany')) {
                    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
                }
                global $db;
                $formCompany = new FormCompany($db);
                $id_country = isset($options['id_country']) ? $options['id_country'] : 0;
                $html .= $formCompany->select_state((int) $value, $id_country, $field_name);
                break;

            case 'search_juridicalstatus':
                if (!class_exists('FormCompany')) {
                    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
                }
                global $db;
                $formCompany = new FormCompany($db);
                $country_code = isset($options['country_code']) ? $options['country_code'] : 0;
                $html .= $formCompany->select_juridicalstatus((int) $value, $country_code, '', $field_name);
                break;

            case 'search_commande_client':
                if (isset($options['id_client']) && $options['id_client']) {
                    global $db;
                    $bdb = new BimpDb($db);
                    $rows = $bdb->getRows('commande', '`fk_soc` = ' . (int) $options['id_client'], null, 'array', array(
                        'rowid', 'ref'
                    ));
                    $values = array();
                    if (!is_null($rows)) {
                        foreach ($rows as $r) {
                            $values[(int) $r['rowid']] = $r['ref'];
                        }
                    }
                    return self::renderInput('select', $field_name, $value, array('options' => $values));
                } else {
                    $params = array(
                        'table'              => 'commande cmd',
                        'field_return_value' => 'cmd.rowid',
                        'field_return_label' => 'cmd.ref'
                    );
                    $params['options'] = array(
                        'default' => array(
                            'label'             => 'Recherche par référence commande',
                            'fields_search'     => 'cmd.ref,cmd.rowid',
                            'join'              => 'societe s',
                            'join_on'           => 'cmd.fk_soc = s.rowid',
                            'join_return_label' => 's.nom',
                            'help'              => 'Entrez la référence ou l\'id d\'une commande client'
                        ),
                        'client'  => array(
                            'label'             => 'Recherche par client',
                            'fields_search'     => 's.nom,s.code_client',
                            'join'              => 'societe s',
                            'join_on'           => 'cmd.fk_soc = s.rowid',
                            'join_return_label' => 's.nom',
                            'filters'           => array('s.client' => 1),
                            'help'              => 'Entrez le nom ou le code d\'un client'
                        )
                    );
                }

                $html .= self::renderSearchListInput($field_name, $params, $value);
                break;

            case 'search_commande_fournisseur':
                $params = array(
                    'table'              => 'commande_fournisseur cmd',
                    'field_return_value' => 'cmd.rowid',
                    'field_return_label' => 'cmd.ref'
                );

                if (isset($options['id_fournisseur']) && $options['id_fournisseur']) {
                    $params['fields_search'] = 'cmd.ref,cmd.rowid';
                    $params['filters'] = array('cmd.kf_soc' => (int) $options['id_fournisseur']);
                } else {
                    $params['options'] = array(
                        'default'     => array(
                            'label'             => 'Recherche par référence commande',
                            'fields_search'     => 'cmd.ref,cmd.rowid',
                            'join'              => 'societe s',
                            'join_on'           => 'cmd.fk_soc = s.rowid',
                            'join_return_label' => 's.nom',
                            'help'              => 'Entrez la référence ou l\'id d\'une commande fournisseur'
                        ),
                        'fournisseur' => array(
                            'label'             => 'Recherche par fournisseur',
                            'fields_search'     => 's.nom,s.code_fournisseur',
                            'join'              => 'societe s',
                            'join_on'           => 'cmd.fk_soc = s.rowid',
                            'join_return_label' => 's.nom',
                            'filters'           => array('s.fournisseur' => 1),
                            'help'              => 'Entrez le nom ou le code d\'un fournisseur'
                        )
                    );
                }

                $html .= self::renderSearchListInput($field_name, $params, $value);
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
                        $rand = rand(111111, 999999);
                        $html .= '<div class="check_list_item">';
                        $html .= '<input type="checkbox" name="' . $field_name . '[]" value="' . $item['value'] . '" id="' . $input_id . '_' . $i . '_' . $rand . '"';
                        if (in_array($item['value'], $value)) {
                            $html .= ' checked';
                        }
                        $html .= '/>';
                        $html .= '<label for="' . $input_id . '_' . $i . '_' . $rand . '">';
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
                $html = self::renderTimerInput($field_name, $value);
                break;

            case 'file_upload':
                $html = '<input type="file" name="' . $field_name . '" id="' . $input_id . '"/>';
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

        if (is_null($value)) {
            $value = '';
        } elseif (preg_match('/^([0-9]{4})\-([0-9][0-9])\-([0-9][0-9]).*$/', $value, $matches)) {
            if (!(int) $matches[1] || !(int) $matches[2] || !(int) $matches[3]) {
                $value = '';
            }
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

        if (!$value && isset($options['display_now']) && $options['display_now']) {
            $value = date($php_format);
            $dt_value = new DateTime($value);
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
        if (!is_null($dt_value)) {
            $html .= "defaultDate: moment('" . $dt_value->format($php_format) . "'),";
        }
        $html .= "showTodayButton: " . (isset($options['display_now']) && $options['display_now'] ? "true" : "false");
        $html .= "}); ";
//        if (!is_null($dt_value)) {
//            $html .= "var cur_date = moment('" . $dt_value->format($php_format) . "'); ";
//            $html .= "$('#" . $input_id . "_bs_dt_picker').data('DateTimePicker').date(cur_date); ";
//        }
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

    public static function renderSearchListInputFromConfig(BimpObject $object, $config_path, $input_name, $value = '', $option = null)
    {
        $params = array();

        if (!$object->config->isDefined($config_path . '/search_list')) {
            return BimpRender::renderAlerts('Paramètres de recherche non définis pour le champ "' . $input_name . '"');
        }

        $path = $config_path . '/search_list/';

        $params['table'] = $object->getConf($path . 'table', null, true);
        $params['field_return_label'] = $object->getConf($path . 'field_return_label', null, true);
        $params['field_return_value'] = $object->getConf($path . 'field_return_value', null, true);

        if (is_null($params['table']) || is_null($params['field_return_label']) || is_null($params['field_return_value'])) {
            return BimpRender::renderAlerts('Configuration invalide pour le champ  "' . $input_name . '"');
        }

        $params['join'] = $object->getConf($path . 'join', '');
        $params['join_on'] = $object->getConf($path . 'join_on', '');
        $params['join_return_label'] = $object->getConf($path . 'join_return_label', '');
        $params['label_syntaxe'] = $object->getConf($path . 'label_syntaxe', '<label_1>');

        if ($object->config->isDefined($path . '/filters')) {
            $params['filters'] = $object->config->getCompiledParams($path . '/filters');
        } else {
            $params['filters'] = array();
        }

        if ($object->config->isDefined($path . 'options')) {
            $params['options'] = $object->config->getCompiledParams($path . 'options');
        } else {
            $params['options'] = array();
            $params['fields_search'] = $object->getConf($path . 'fields_search', null, true);
        }

        return self::renderSearchListInput($input_name, $params, $value, $option);
    }

    public static function renderSearchListInput($input_name, $params, $value = null, $option = null)
    {
        if (is_null($value)) {
            $value = '';
        }

        $table = isset($params['table']) ? $params['table'] : null;
        $join = isset($params['join']) ? $params['join'] : '';
        $join_on = isset($params['join_on']) ? $params['join_on'] : '';
        $field_return_label = isset($params['field_return_label']) ? $params['field_return_label'] : null;
        $field_return_value = isset($params['field_return_value']) ? $params['field_return_value'] : null;
        $join_return_label = isset($params['join_return_label']) ? $params['join_return_label'] : '';
        $label_syntaxe = isset($params['label_syntaxe']) ? $params['label_syntaxe'] : '<label_1>';
        $filters = isset($params['filters']) ? $params['filters'] : array();

        if (is_null($table) || is_null($field_return_label) || is_null($field_return_value)) {
            return BimpRender::renderAlerts('Configuration invalide pour le champ  "' . $input_name . '"');
        }

        $fields_search = array();
        if (isset($params['options']) && count($params['options'])) {
            if (!is_null($option) && isset($params['options'][$option])) {
                $fields_search = isset($params['options'][$option]['fields_search']) ? $params['options'][$option]['fields_search'] : '';
                $join = isset($params['options'][$option]['join']) ? $params['options'][$option]['join'] : '';
                $join_on = isset($params['options'][$option]['join_on']) ? $params['options'][$option]['join_on'] : '';
                $join_return_label = isset($params['options'][$option]['join_return_label']) ? $params['options'][$option]['join_return_label'] : '';
            } else {
                $html .= '<div class="searchListOptions optionsContainer">';
                $html .= '<span class="displayPopupButton optionsButton" data-popup_id="' . $input_name . '_searchListOptionsPopup">Options de recherche</span>';
                $html .= '<div id="' . $input_name . '_searchListOptionsPopup" class="tinyPopup searchListOptionsPopup">';
                $switchOptions = array();
                foreach ($params['options'] as $opt_name => $opt_params) {
                    $opt_label = isset($opt_params['label']) ? $opt_params['label'] : null;
                    $opt_fields_search = isset($opt_params['fields_search']) ? $opt_params['fields_search'] : null;
                    $opt_join = isset($opt_params['join']) ? $opt_params['join'] : '';
                    $opt_join_on = isset($opt_params['join_on']) ? $opt_params['join_on'] : '';
                    $opt_join_return_label = isset($opt_params['join_return_label']) ? $opt_params['join_return_label'] : '';
                    $opt_help = isset($opt_params['help']) ? $opt_params['help'] : '';
                    $opt_filters = isset($opt_params['filters']) ? $opt_params['filters'] : '';

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
                        if (count($opt_filters)) {
                            $html .= '<input type="hidden" id="searchList_' . $opt_name . '_filters" value="' . json_encode($opt_filters) . '"/>';
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
                        $filters = $opt_filters;
                    }
                }
                $html .= '<div class="title">Options de recherche</div>';
                $html .= BimpInput::renderSwitchOptionsInput($input_name . '_search_list_option', $switchOptions, 'default', null, true);
                $html .= '</div>';
                $html .= '</div>';
            }
        } else {
            $fields_search = isset($params['fields_search']) ? $params['fields_search'] : null;
        }

        if (is_null($fields_search)) {
            return BimpRender::renderAlerts('Configuration invalide pour le champ  "' . $input_name . '" - Aucun champ de recherche défini');
        }

        $search = '';
        if ($value) {
            global $db;
            $bdb = new BimpDb($db);
            if (!is_array($fields_search)) {
                $fields_search = explode(',', $fields_search);
            }

            $sql = 'SELECT ' . $field_return_label . ' as label';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . $table;
            if ($join) {
                $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . $join . ' ON ' . $join_on;
            }
            $sql .= ' WHERE ' . $field_return_value . ' = ' . (is_string($value) ? '\'' . $value . '\'' : $value);

            $result = $bdb->executeS($sql, 'array');

            if (is_null($result) || !isset($result[0]['label'])) {
                $search = '';
            } else {
                $search = $result[0]['label'];
            }
            unset($bdb);
        }

        if (is_array($fields_search)) {
            $fields_search = implode(',', $fields_search);
        }

        $html .= '<input type="hidden" name="' . $input_name . '" value="' . $value . '"/>';
        $html .= '<input type="hidden" name="' . $input_name . '_label" value="' . $search . '"/>';
        $html .= '<input type="text" name="' . $input_name . '_search" class="search_list_input" value="" onkeyup="searchObjectList($(this));"';
        $html .= ' data-table="' . $table . '"';
        $html .= ' data-join="' . $join . '"';
        $html .= ' data-join_on="' . $join_on . '"';
        $html .= ' data-fields_search="' . $fields_search . '"';
        $html .= ' data-field_return_label="' . $field_return_label . '"';
        $html .= ' data-field_return_value="' . $field_return_value . '"';
        $html .= ' data-join_return_label="' . $join_return_label . '"';
        $html .= ' data-label_syntaxe="' . htmlentities($label_syntaxe) . '"';
        $html .= ' data-filters="' . htmlentities(json_encode($filters)) . '"';
        $html .= '/>';
        $html .= '<i class="loading fa fa-spinner fa-spin"></i>';
        $html .= '<div class="search_input_results"></div>';
        $html .= '<div class="search_input_selected_label"' . (!$search ? ' style="display: none"' : '') . '>';
        $html .= '<i class="fa fa-check iconLeft"></i>';
        $html .= '<span class="">' . $search . '</span>';
        $html .= '</div>';
        return $html;
    }

    public static function renderTimerInput($field_name, $value)
    {
        if (is_null($value)) {
            $value = 0;
        }
        $timer = BimpTools::getTimeDataFromSeconds((int) $value);

        $html = '';
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

        $html .= '<div class="inputMultipleValuesContainer" data-field_name="' . $field_name . '"';
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
        $html .= '<tbody class="multipleValuesList">';

        $html .= '<tr style="display: none">';
        $html .= '<td><input type="hidden" value="" name="' . $field_name . '[]"/></td>';
        $html .= '</tr>';

        foreach ($values as $value => $label) {
            $html .= '<tr class="itemRow">';
            $html .= '<td style="display: none">';
            $html .= '<input type="hidden" name="' . $field_name . '[]" value="' . $value . '"/>';
            $html .= '</td>';
            $html .= '<td>' . $label . '</td>';
            $html .= '<td style="width: 62px"><button type="button" class="btn btn-light-danger iconBtn" onclick="';
            if ($autosave) {
                $html .= 'var $button = $(this); deleteObjectMultipleValuesItem(\'' . $object->module . '\', \'' . $object->object_name . '\', ';
                $html .= $object->id . ', \'' . $field_name . '\', \'' . $value . '\', null, function() {';
                $html .= '$button.parent(\'td\').parent(\'tr\').fadeOut(250, function() {$(this).remove(); checkMultipleValues();});});';
            } else {
                $html .= '$(this).parent(\'td\').parent(\'tr\').fadeOut(250, function() {$(this).remove(); checkMultipleValues();});"';
            }
            $html .= '"><i class="fa fa-trash"></i></button></td>';
            $html .= '</tr>';
        }

        $html .= '<tr class="noItemRow"' . (count($values) ? ' style="display: none"' : '') . '>';
        $html .= '<td colspan="3">';
        $html .= BimpRender::renderAlerts('Aucun élément sélectionné', 'warning');
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }
}
