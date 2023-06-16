<?php

require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

class BimpInput
{

    public static $paiementRestrictive = array('VIR');

    public static function canUseRestrictedPaiement()
    {
        global $user;
        return $user->rights->bimpcommercial->factureAnticipe;
    }

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
        $data = '';

        if (isset($options['data'])) {
            $data = BimpRender::renderTagData($options['data']);
        }
        if (isset($options['no_autocorrect']) && $options['no_autocorrect'])
            $data .= ' autocorrect="off" autocapitalize="none"';


        switch ($type) {
            case 'hidden':
                $value = htmlentities($value);
                $html .= '<input type="hidden" id="' . $field_name . '" name="' . $field_name . '" value="' . $value . '" class="' . $extra_class . '"' . $data . '/>';
                break;

            case 'text':
                if (BimpCore::isContextPrivate() && isset($options['hashtags']) && (int) $options['hashtags']) {
                    $extra_class .= ($extra_class ? ' ' : '') . 'allow_hashtags';
                }

                $value = htmlentities($value);
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

                    if ($extra_class) {
                        $html .= ' class="' . $extra_class . '"';
                    }
                    $html .= '/>';

                    if (isset($options['addon_right']) && $options['addon_right']) {
                        $html .= '<span class="input-group-addon">' . $options['addon_right'] . '</span>';
                    }

                    $html .= '</div>';
                    $html .= '</div>';
                } else {
                    $html .= '<input type="text" id="' . $input_id . '" name="' . $field_name . '" value="' . $value . '"';
                    if ($extra_class) {
                        $html .= ' class="' . $extra_class . '"';
                    }
                    if (isset($options['placeholder'])) {
                        $html .= ' placeholder="' . $options['placeholder'] . '"';
                    }
                    if (isset($options['style'])) {
                        $html .= ' style="' . $options['style'] . '"';
                    }
                    $html .= $data;
                    $html .= '/>';
                }

                if (BimpCore::isContextPrivate()) {
                    if (isset($options['hashtags']) && (int) $options['hashtags']) {
                        $html .= BimpRender::renderInfoIcon('fas_hashtag', 'Vous pouvez utiliser le symbole # pour inclure un lien objet');
                    }

                    if (isset($options['scanner']) && (int) $options['scanner']) {
                        $onclick = 'var $parent = $(this).parent();';
                        $onclick .= 'if ($parent.hasClass(\'input-group\')) {';
                        $onclick .= '$parent = $parent.parent().parent();';
                        $onclick .= '}';
                        $onclick .= 'var $input = $parent.find(\'input[name=' . $field_name . ']\');';
                        $onclick .= 'BIS.openModal($input);';
                        $html .= BimpRender::renderRowButton('Scanner code-barres / Qr-Code', 'fas_camera', $onclick);
                    }
                }

                if (isset($options['min_label']) && $options['min_label']) {
                    $html .= '<div style="display: inline-block">';
                    $html .= '&nbsp;&nbsp;<span class="small min_label">' . ((isset($options['data']['min']) && $option['data']['min'] !== 'none') ? 'Min: ' . $options['data']['min'] : '') . '</span>';
                    $html .= '</div>';
                }
                if (isset($options['max_label']) && $options['max_label']) {
                    $html .= '<div style="display: inline-block">';
                    $html .= '&nbsp;&nbsp;<span class="small max_label">' . ((isset($options['data']['max']) && $option['data']['max'] !== 'none') ? 'Max: ' . $options['data']['max'] : '') . '</span>';
                    $html .= '</div>';
                }

                if (isset($options['values']) && count($options['values'])) {
                    $allow_custom = (isset($options['allow_custom']) ? (int) $options['allow_custom'] : 1);
                    $html .= '<div style="margin-top: 15px">';
                    $html .= '<select id="' . $input_id . '_input_values" class="input_values ' . $field_name . '_input_values"';
                    $html .= ' data-field_name="' . $field_name . '"';
                    $html .= ' data-allow_custom="' . $allow_custom . '"';
                    $html .= '>';
                    foreach ($options['values'] as $val => $label) {
                        $html .= '<option value="' . $val . '"' . (($val == $value) ? ' selected' : '') . '>' . $label . '</option>';
                    }
                    $html .= '</select>';
                    $html .= '</div>';
                }

                break;

            case 'password':
                $html .= '<input type="password" id="' . $input_id . '" name="' . $field_name . '" value="' . $value . '"' . $data . '/>';
                break;

            case 'qty':
                if (!isset($options['data']['data_type'])) {
                    $data .= ' data-data_type="number"';
                }

                $data .= ' data-step="' . (isset($options['step']) ? $options['step'] : 1) . '"';

                if ((isset($options['addon_left']) && $options['addon_left']) ||
                        (isset($options['addon_right']) && $options['addon_right'])) {
                    $html .= '<div class="inputGroupContainer">';
                    $html .= '<div class="input-group">';

                    if (isset($options['addon_left']) && $options['addon_left']) {
                        $html .= '<span class="input-group-addon">' . $options['addon_left'] . '</span>';
                    }

                    $html .= '<div class="qtyInputContainer">';
                    $html .= '<span class="qtyDown">';
                    $html .= '<i class="fa fa-minus"></i>';
                    $html .= '</span>';

                    $html .= '<input type="text" id="' . $input_id . '" name="' . $field_name . '" value="' . $value . '"';
                    if (isset($options['placeholder'])) {
                        $html .= ' placeholder="' . $options['placeholder'] . '"';
                    }
                    if (isset($options['style'])) {
                        $html .= ' style="' . $options['style'] . '"';
                    }
                    $html .= $data;
                    $html .= ' class="qtyInput' . ($extra_class ? ' ' . $extra_class : '') . '"';
                    $html .= '/>';

                    $html .= '<span class="qtyUp">';
                    $html .= '<i class="fa fa-plus"></i>';
                    $html .= '</span>';
                    if (isset($options['min_label']) && $options['min_label']) {
                        $html .= '<span class="inputHelp max_label">' . ((isset($options['data']['min']) && $option['data']['min'] !== 'none') ? 'Min: ' . $options['data']['min'] : '') . '</span>';
                    }
                    if (isset($options['max_label']) && $options['max_label']) {
                        $html .= '<span class="inputHelp max_label">' . ((isset($options['data']['max']) && $option['data']['max'] !== 'none') ? 'Max: ' . $options['data']['max'] : '') . '</span>';
                    }
                    $html .= '</div>';

                    if (isset($options['addon_right']) && $options['addon_right']) {
                        $html .= '<span class="input-group-addon">' . $options['addon_right'] . '</span>';
                    }

                    $html .= '</div>';
                    $html .= '</div>';
                } else {
                    $html .= '<div class="qtyInputContainer">';
                    $html .= '<span class="qtyDown">';
                    $html .= '<i class="fa fa-minus"></i>';
                    $html .= '</span>';

                    $html .= '<input type="text" id="' . $input_id . '" name="' . $field_name . '" value="' . $value . '"';
                    if (isset($options['placeholder'])) {
                        $html .= ' placeholder="' . $options['placeholder'] . '"';
                    }
                    if (isset($options['style'])) {
                        $html .= ' style="' . $options['style'] . '"';
                    }
                    $html .= $data;
                    $html .= ' class="qtyInput' . ($extra_class ? ' ' . $extra_class : '') . '"';
                    $html .= '/>';

                    $html .= '<span class="qtyUp">';
                    $html .= '<i class="fa fa-plus"></i>';
                    $html .= '</span>';
                    if (isset($options['min_label']) && $options['min_label']) {
                        $html .= '<div style="display: inline-block">';
                        $html .= '<span class="small min_label">' . ((isset($options['data']['min']) && $option['data']['min'] !== 'none') ? 'Min: ' . $options['data']['min'] : '') . '</span>';
                        $html .= '</div>';
                    }
                    if (isset($options['max_label']) && $options['max_label']) {
                        $html .= '<div style="display: inline-block">';
                        $html .= '<span class="small max_label">' . ((isset($options['data']['max']) && $option['data']['max'] !== 'none') ? 'Max: ' . $options['data']['max'] : '') . '</span>';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                }
                break;

            case 'textarea':
                if (BimpCore::isContextPrivate() && isset($options['hashtags']) && (int) $options['hashtags']) {
                    $extra_class .= ($extra_class ? ' ' : '') . 'allow_hashtags';
                }

                $value = htmlentities($value);

                if (!isset($options['rows'])) {
                    $options['rows'] = 3;
                }
                if (!isset($options['auto_expand'])) {
                    $options['auto_expand'] = false;
                }
                if (!isset($options['note'])) {
                    $options['note'] = false;
                }
                if (!isset($options['tab_key_as_enter'])) {
                    $options['tab_key_as_enter'] = false;
                }

                if (isset($options['maxlength']) && $options['maxlength']) {
                    $html .= '<p class="smallInfo">Max ' . $options['maxlength'] . ' caractères</p>';
                }

                if (BimpCore::isContextPrivate()) {
                    if (isset($options['scanner']) && (int) $options['scanner']) {
                        $onclick = 'var $input = $(this).parent().find(\'textarea[name=' . $field_name . ']\');';
                        $onclick .= 'BIS.openModal($input);';
                        $html .= BimpRender::renderRowButton('Scanner code-barres / Qr-Code', 'fas_camera', $onclick);
                    }
                    if (isset($options['hashtags']) && (int) $options['hashtags']) {
                        $html .= '<p class="inputHelp" style="display: inline-block">';
                        $html .= 'Vous pouvez utiliser le symbole # pour inclure un lien objet';
                        $html .= '</p>';
                    }
                }

                $html .= '<textarea id="' . $input_id . '" rows="' . $options['rows'] . '" name="' . $field_name . '"';
                $classes = array();

                if (isset($options['auto_expand']) && (int) $options['auto_expand']) {
                    $classes[] = 'auto_expand ';
                }
                if (isset($options['note']) && $options['note']) {
                    $classes[] = 'note';
                }
                if (isset($options['tab_key_as_enter']) && $options['tab_key_as_enter']) {
                    $classes[] = 'tab_key_as_enter';
                }
                if ($extra_class) {
                    $classes[] = $extra_class;
                }

                if (!empty($classes)) {
                    $html .= ' class="' . implode(' ', $classes) . '"';
                }

                if (isset($options['rows'])) {
                    $html .= ' data-min_rows="' . $options['rows'] . '"';
                }

                if (isset($options['maxlength']) && $options['maxlength']) {
                    $html .= ' maxlength="' . (int) $options['maxlength'] . '"';
                }

                $html .= $data . '>' . $value . '</textarea>';

                if (isset($options['values']) && is_array($options['values']) && count($options['values'])) {
                    $html .= '<ul class="texarea_values" data-input_id="' . $input_id . '" data-field_name="' . $field_name . '">';
                    foreach ($options['values'] as $val) {
                        $html .= '<li class="textarea_value">' . $val . '</li>';
                    }
                    $html .= '</ul>';
                }
                break;

            case 'html':
                if (!class_exists('DolEditor')) {
                    require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
                }
                $doleditor = new DolEditor($field_name, $value, '', 160, 'dolibarr_details', '', false, true, true, ROWS_4, '90%');

                if (BimpCore::isContextPrivate() && isset($options['hashtags']) && (int) $options['hashtags']) {
                    $doleditor->extra_class = 'allow_hashtags';

//                    if (isset($options['scanner']) && (int) $options['scanner']) {
//                        $onclick = 'var $input = $(this).parent().find(\'textarea[name=' . $field_name . ']\');';
//                        $onclick .= 'BIS.openModal($input);';
//                        $html .= BimpRender::renderRowButton('Scanner code-barres / Qr-Code', 'fas_camera', $onclick);
//                    }

                    $html .= '<p class="inputHelp" style="display: inline-block">';
                    $html .= 'Vous pouvez utiliser le symbole # pour inclure un lien objet';
                    $html .= '</p>';
                }

                $html .= $doleditor->Create(1);
                break;

            case 'switch':
                $html .= '<select class="switch ' . $extra_class . '" id="' . $input_id . '" name="' . $field_name . '"' . $data . '>';
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
                $html .= '<input type="hidden" class="toggle_value ' . $extra_class . '" value="' . ($value ? '1' : '0') . '" name="' . $field_name . '" id="' . $input_id . '"' . $data . '/>';
                $html .= '<input type="checkbox" class="toggle" id="' . $input_id . '_toggle" ' . ($value ? ' checked' : '') . '/>';
                if ($display_labels) {
                    $html .= '<span class="toggle-label-off">' . $options['toggle_off'] . '</span>';
                }
                $html .= '<label class="toggle-slider ' . (isset($options['disabled']) ? 'disabled' : '') . '" for="' . $input_id . '_toggle"></label>';
                if ($display_labels) {
                    $html .= '<span class="toggle-label-on">' . $options['toggle_on'] . '</span>';
                }
                $html .= '</div>';
                break;

            case 'select':
                if (is_null($options['options']) || !is_array($options['options'])) {
                    $options['options'] = array();
                }

                if (isset($options['select_first']) && (int) $options['select_first']) {
                    foreach ($options['options'] as $option_key => $option_val) {
                        $value = $option_key;
                        break;
                    }
                }

                if (count($options['options'])) {
                    if (count($options['options']) > 15) {
                        $extra_class .= ($extra_class ? ' ' : '') . 'searchable_select';
                    }
                    $html .= '<select id="' . $input_id . '" name="' . $field_name . '" class="' . $extra_class . '"' . $data . '>';
                    foreach ($options['options'] as $option_value => $option) {
                        $html .= self::renderSelectOption($option_value, $option, $value);
                    }
                    $html .= '</select>';

                    foreach ($options['options'] as $option_value => $option) {
                        if (isset($option['help'])) {
                            $html .= '<div class="selectOptionHelp ' . $field_name . '_help" data-option_value="' . htmlentities($option_value) . '">';
                            $html .= BimpRender::renderAlerts($option['help'], 'info');
                            $html .= '</div>';
                        }
                    }
                } else {
                    $html .= '<input type="hidden" name="' . $field_name . '" value=""/>';
                    $html .= '<p class="alert alert-warning">Aucune option disponible</p>';
                }
                break;

            case 'switch_options':
                if (is_null($options['options']) || !is_array($options['options'])) {
                    $options['options'] = array();
                }

                if (count($options['options'])) {
                    $vertical = isset($options['vertical']) ? (int) $options['vertical'] : 0;
                    $html = self::renderSwitchOptionsInput($field_name, $options['options'], $value, $input_id, $vertical, $data);
                } else {
                    $html .= '<input type="hidden" name="' . $field_name . '" value=""/>';
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
                if (!isset($options['include_empty'])) {
                    $options['include_empty'] = 0;
                }
                global $langs;
                $langs->load('bills');
                $form->load_cache_types_paiements();
                $html .= '<select id="' . $input_id . '" name="' . $field_name . '" class="' . $extra_class . '"' . $data . '>';

                if ((int) $options['include_empty']) {
                    $html .= '<option';
                    switch ($options['value_type']) {
                        case 'code':
                            $html .= ' value="" data-id_payment="0">';
                            break;

                        case 'id':
                            $html .= ' value="0" data-code="">';
                    }
                    $html .= '</option>';
                }
                
                foreach ($form->cache_types_paiements as $id_payment => $payment_data) {
                    if (!(int) $options['active_only'] || ((int) $options['active_only'] && (int) $payment_data['active'])) {
                        if (in_array($payment_data['code'], static::$paiementRestrictive) && !self::canUseRestrictedPaiement()) {
                            $payment_data['forbidden'] = 1;
                        }


                        switch ($options['value_type']) {
                            case 'code':
                                $html .= '<option value="' . $payment_data['code'] . '" data-id_payment="' . $id_payment . '"';
                                if ((string) $value === (string) $payment_data['code']) {
                                    $html .= ' selected="1"';
                                }
                                if (isset($payment_data['forbidden']) && $payment_data['forbidden']) {
                                    $html .= ' disabled="disabled"';
                                }
                                $html .= '>' . $payment_data['label'] . '</option>';
                                break;

                            case 'id':
                            default:
                                $html .= '<option value="' . $id_payment . '" data-code="' . $payment_data['code'] . '"';
                                if ((int) $value === (int) $payment_data['id']) {
                                    $html .= ' selected="1"';
                                }
                                $html .= '>' . $payment_data['label'] . '</option>';
                                break;
                        }
                    }
                }
                $html .= '</select>';
                break;

            case 'select_cond_reglement':
                $options['options'] = BimpCache::getCondReglementsArray();
                return self::renderInput('select', $field_name, $value, $options, $form, $option, $input_id);

            case 'select_mysoc_account':
                $options['options'] = BimpCache::getBankAccountsArray(true);
                return self::renderInput('select', $field_name, $value, $options, $form, $option, $input_id);

            case 'select_remises':
                $is_fourn = false;

                if (isset($options['id_client']) && (int) $options['id_client']) {
                    $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $options['id_client']);
                } elseif (isset($options['id_fourn']) && (int) $options['id_fourn']) {
                    $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', (int) $options['id_fourn']);
                    $is_fourn = true;
                }

                if (BimpObject::objectLoaded($soc)) {
                    $options['options'] = $soc->getAvailableDiscountsArray($is_fourn);
                } else {
                    $options['options'] = array();
                }

                return self::renderInput('select', $field_name, $value, $options);

            case 'select_remises_fourn':
                if (!isset($options['id_fourn'])) {
                    $options['id_fourn'] = 0;
                }
                break;

            case 'select_availability':
                $options['options'] = BimpCache::getAvailabilitiesArray();
                return self::renderInput('select', $field_name, $value, $options, $form, $option, $input_id);

            case 'select_input_reasons':
                $options['options'] = BimpCache::getDemandReasonsArray();
                return self::renderInput('select', $field_name, $value, $options, $form, $option, $input_id);

            case 'search_object':
                if (isset($options['object']) && is_a($options['object'], 'BimpObject')) {
                    $html = self::renderSearchObjectInput($options['object'], $field_name, $value, $options);
                } else {
                    $html .= BimpRender::renderAlerts('Type d\'objet à rechercher invalide');
                }
                break;

            case 'search_ziptown':
                $html = '<div class="searchZiptownInputContainer">';
                $html .= '<input autocomplete="off" typetype="text" class="search_ziptown" name="' . $field_name . '" value="' . $value . '"';
                if (isset($options['field_type'])) {
                    $html .= ' data-field_type="' . $options['field_type'] . '"';
                }
                if (isset($options['town_field'])) {
                    $html .= ' data-town_field="' . $options['town_field'] . '"';
                }
                if (isset($options['zip_field'])) {
                    $html .= ' data-zip_field="' . $options['zip_field'] . '"';
                }
                if (isset($options['state_field'])) {
                    $html .= ' data-state_field="' . $options['state_field'] . '"';
                }
                if (isset($options['country_field'])) {
                    $html .= ' data-country_field="' . $options['country_field'] . '"';
                }
                if (isset($options['data'])) {
                    foreach ($options['data'] as $data_name => $data_value) {
                        $html .= ' data-' . $data_name . '="' . $data_value . '"';
                    }
                }
                $html .= $data;
                $html .= '/>';
                $html .= '<i class="loading fa fa-spinner fa-spin"></i>';
                $html .= '</div>';
                $html .= '<div class="searchZipTownResults hideOnClickOut input_choices"></div>';
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
                $form->select_produits((int) $value, $field_name, $filter_type, $conf->product->limit_size, 0, 1, 2, '', 1);
                $html .= ob_get_clean();
                break;

            case 'search_societe':
                $filter = array();
                if (isset($options['type']) && $options['type']) {
                    switch ($options['type']) {
                        case 'customer':
                            $filter[] = 's.client != 0';
                            break;

                        case 'supplier':
                            $filter[] = 's.fournisseur != 0';
                            break;
                    }
                }
                $filter[] = 'status=1';

                $html .= $form->select_company((int) $value, $field_name, implode(" AND ", $filter), '', 0, 0, array(), 20);
                break;

            case 'search_user':
                $options = BimpTools::overrideArray(array(
                            'include_empty' => 0,
                            'empty_label'   => '',
                            'active_only'   => null
                                ), $options);

                $options['options'] = BimpCache::getUsersArray($options['include_empty'], $options['empty_label'], $options['active_only']);

                if (isset($options['include_current']) && (int) $options['include_current']) {
                    $new_options = array(
                        'current' => 'Utilisateur connecté'
                    );

                    foreach ($options['options'] as $id => $label) {
                        $new_options[$id] = $label;
                    }

                    $options['options'] = $new_options;
                }

                return self::renderInput('select', $field_name, $value, $options, $form, $option, $input_id);

            case 'search_group':
                if (!isset($options['include_empty'])) {
                    $options['include_empty'] = 0;
                }
                $options['options'] = BimpCache::getUserGroupsArray($options['include_empty']);
                return self::renderInput('select', $field_name, $value, $options, $form, $option, $input_id);

            case 'search_contact':
                $html .= $form->selectcontacts(0, (int) $value, $field_name);
                break;

            case 'search_entrepot':
                $include_empty = (isset($options['include_empty']) ? (int) $options['include_empty'] : 0);
                $has_commissions_only = (isset($options['has_commissions_only']) ? (int) $options['has_commissions_only'] : 0);
                $options['options'] = BimpCache::getEntrepotsArray($include_empty, $has_commissions_only);
                $html .= self::renderInput('select', $field_name, $value, $options, $form, $option, $input_id);
                break;

            case 'search_country':
                $options['options'] = BimpCache::getCountriesArray((isset($options['active_only']) ? (int) $options['active_only'] : 1), (isset($options['key_field']) ? $options['key_field'] : 'rowid'));
                return self::renderInput('select', $field_name, $value, $options);

            case 'search_state':
                $country = isset($options['id_country']) ? $options['id_country'] : 0;
                $active_only = isset($options['active_only']) ? $options['active_only'] : 1;
                $country_key_field = isset($options['country_key_field']) ? $options['country_key_field'] : 'rowid';
                $include_empty = isset($options['include_empty']) ? $options['include_empty'] : 1;
                $options['options'] = BimpCache::getStatesArray($country, $country_key_field, $active_only, $include_empty);

                return self::renderInput('select', $field_name, $value, $options);

            case 'search_juridicalstatus':
                $country = isset($options['country_code']) ? $options['country_code'] : 0;
                $active_only = isset($options['active_only']) ? $options['active_only'] : 1;
                $country_key_field = isset($options['country_key_field']) ? $options['country_key_field'] : 'code';
                $include_empty = isset($options['include_empty']) ? $options['include_empty'] : 1;

                $options['options'] = BimpCache::getJuridicalstatusArray($country, $country_key_field, $active_only, $include_empty);
                return self::renderInput('select', $field_name, $value, $options);

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
                        if (is_string($value)) {
                            $value = explode(',', $value);
                        } else {
                            $value = array($value);
                        }
                    }

                    if (!isset($options['max'])) {
                        $options['max'] = 'none';
                    }

                    $options = BimpTools::overrideArray(array(
                                'search_input'       => 1,
                                'select_all_buttons' => 1,
                                'max'                => 'none',
                                'max_input_name'     => '',
                                'max_input_abs'      => 0
                                    ), $options);

                    $nb_selected = 0;

                    $html = '<div class="check_list_container' . ($extra_class ? ' ' . $extra_class : '') . '"';
                    $html .= ' data-max="' . $options['max'] . '"';
                    $html .= ' data-max_input_name="' . $options['max_input_name'] . '"';
                    $html .= ' data-max_input_abs="' . $options['max_input_abs'] . '"';
                    $html .= '>';
                    if (count($options['items']) > 1) {
                        if ((int) $options['search_input']) {
                            $html .= '<div class="check_list_search_input">';
                            $html .= '<span class="searchIcon">' . BimpRender::renderIcon('fas_search', 'iconLeft') . '</span>';
                            $html .= self::renderInput('text', $field_name . '_search_input');
                            $html .= '</div>';
                        }
                        if ((int) $options['select_all_buttons']) {
                            $html .= self::renderToggleAllCheckboxes('$(this).parent().parent()', '.' . $field_name . '_check');
                        }
                    }
                    $i = 1;
                    foreach ($options['items'] as $idx => $item) {
                        $html .= self::renderCheckListItem($field_name, $input_id, $i, $idx, $item, $nb_selected, $value);
                    }

                    if ($options['max'] !== 'none' || $options['max_input_name']) {
                        $max = ($options['max'] !== 'none') ? (int) $options['max'] : 0;
                        $html .= '<p class="small">Max: <span class="check_list_max_label">' . $max . '</span></p>';

                        $html .= '<div class="check_list_max_alert"' . ($nb_selected > $max ? '' : ' style="display: none"') . '>';
                        $html .= BimpRender::renderAlerts('Veuillez désélectionner <span class="check_list_nb_items_to_unselect">' . ($nb_selected - $max) . '</span> élément(s)', 'danger');
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
                    $html .= '<span class="input-group-addon">' . BimpTools::getArrayValueFromPath($options, 'from_label', 'Du') . '</span>';
                    $options['extra_class'] = $extra_class . ($extra_class ? ' ' : '') . 'date_range_from';
                    $html .= self::renderDatePickerInput($field_name . '_from', $value['from'], $options, $input_id . '_from', str_replace('_range', '', $type));
                    $html .= '</div>';

                    $html .= '<div class="input-group">';
                    $html .= '<span class="input-group-addon">' . BimpTools::getArrayValueFromPath($options, 'to_label', 'Au') . '</span>';
                    $options['extra_class'] = $extra_class . ($extra_class ? ' ' : '') . 'date_range_to';
                    $html .= self::renderDatePickerInput($field_name . '_to', $value['to'], $options, $input_id . '_to', str_replace('_range', '', $type));
                    $html .= '</div>';

                    $options['extra_class'] = $extra_class;
                } else {
                    $html .= self::renderDatePickerInput($field_name, $value, $options, $input_id, $type);
                }
                break;

            case 'timer':
                $html = self::renderTimerInput($field_name, $value, $options);
                break;

            case 'file_upload':
                $html = '<input type="file" name="' . $field_name . '" id="' . $input_id . '"' . $data . '/>';
                break;

            case 'drop_files':
                $html = '<div class="bimp_drop_files_container"';
                $html .= ' data-max_items="' . (int) BimpTools::getArrayValueFromPath($options, 'max_items', 0) . '"';
                $html .= ' data-files_dir="' . BimpTools::getArrayValueFromPath($options, 'files_dir', '') . '"';
                $html .= ' data-allowed_ext="' . BimpTools::getArrayValueFromPath($options, 'allowed_ext', '') . '"';
                $html .= ' data-allowed_types="' . BimpTools::getArrayValueFromPath($options, 'allowed_types', '') . '"';
                $html .= '>';
                $html .= '<input type="file" class="add_file_input" id="' . $field_name . '_file_input"/>';
                $html .= '<label class="btn btn-default" for="' . $field_name . '_file_input">';
                $html .= BimpRender::renderIcon('fas_folder-open', 'iconLeft') . 'Sélectionner un fichier</label>';

                $html .= '<p class="inputHelp">Ou faites glisser un fichier dans la zone ci-dessous: </p>';

                $html .= '<div class="bimp_drop_files_area">';
                $html .= '<div class="drop_infos">Déposez vos fichiers ici</div>';
                $html .= '<div class="drop_files"></div>';
                $html .= BimpRender::rendercontentLoading('Envoi des fichiers en cours');
                $html .= '</div>';

                if (isset($options['max_items']) && (int) $options['max_items'] > 0) {
                    $html .= '<p class="small info">' . $options['max_items'] . ' fichier' . ($options['max_items'] > 1 ? 's' : '') . ' max</p>';
                }
                if (isset($options['allowed_types']) && (string) $options['allowed_types']) {
                    $html .= '<p class="small">Extension(s) autorisée(s) : <b>' . $options['allowed_types'] . '</b></p>';
                }
                if (isset($options['allowed_ext']) && (string) $options['allowed_ext']) {
                    $html .= '<p class="small">Extension(s) autorisée(s) : <b>' . $options['allowed_ext'] . '</b></p>';
                }

                $html .= '</div>';
                break;

            case 'object_filters':
                $obj_input_name = BimpTools::getArrayValueFromPath($options, 'obj_input_name', '');
                $obj_module = BimpTools::getArrayValueFromPath($options, 'obj_module', '');
                $obj_name = BimpTools::getArrayValueFromPath($options, 'obj_name', '');

                $html .= '<div class="obj_filters_input_container"';
                $html .= ' data-field_name="' . $field_name . '"';
                $html .= ' data-obj_input_name="' . $obj_input_name . '"';
                $html .= ' data-obj_module="' . $obj_module . '"';
                $html .= ' data-obj_name="' . $obj_name . '"';
                $html .= '>';

                $html .= '<div class="obj_filters_input_add_filter_container">';
                $html .= self::renderFiltersInputAddFilterInput($obj_module, $obj_name, ($obj_input_name ? true : false));
                $html .= '</div>';

                $html .= '<div class="obj_filters_input_values">';
                $html .= self::renderFiltersInputValues($obj_module, $obj_name, $value);
                $html .= '</div>';

                if (is_array($value)) {
                    $value = json_encode($value);
                }

                $html .= self::renderInput('hidden', $field_name, $value, $options, $form, $option, $input_id);

                $html .= '</div>';
                break;

            case 'signature_pad':
                $displayStyle = '';
                $prefix = rand(111111, 999999);

                if ((int) BimpTools::getArrayValueFromPath($options, 'expand', 0)) {
                    $displayStyle .= ' display: none;';
                }

                $id = $prefix . '_signature-pad';

                $html .= '<div class="signaturePadContainer" data-pad_id="' . $id . '">';

                if (isset($options['check_mentions']) && !empty($options['check_mentions'])) {
                    $html .= '<div class="" style="margin-bottom: 15px">';
                    foreach ($options['check_mentions'] as $check_option_value => $check_option_label) {
                        $item_name = $field_name . '_check_mentions';
                        $id_item = $item_name . '_' . $check_option_value . '_' . rand(111111, 999999);

                        $html .= '<div class="check_list_item">';
                        $html .= '<input type="checkbox" name="' . $item_name . '[]" value="' . $check_option_value . '" id="' . $id_item . '"';
                        $html .= ' class="' . $item_name . '_check check_list_item_input signature_mention_check"/>';
                        $html .= '<label for="' . $id_item . '">';
                        $html .= $check_option_label;
                        $html .= '</label>';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                }

                $html .= '<div class="signature_wrapper">';
                $html .= '<canvas id="' . $id . '" class="signature-pad ' . $extra_class . '" style="border: solid 1px;' . $displayStyle . '" width=400 height=200></canvas>';
                $html .= '</div>';

                $html .= '<div class="buttonsContainer align-center">';
                $html .= '<sapn class="clearSignaturePadBtn btn btn-danger btn-large" >' . BimpRender::renderIcon("fas_undo") . ' Refaire la signature</span>';
                $html .= '</div>';

                $html .= '<input type="hidden" name="' . $field_name . '" value=""/>';
                $html .= '</div>';
                break;

            default:
                $html .= '<p class="alert alert-danger">Erreur technique: type d\'input invalide pour le champ "' . $field_name . '"</p>';
                break;
        }
        return $html;
    }

    public static function renderDatePickerInput($input_name, $value = '', $options = array(), $input_id = null, $type = "datetime")
    {
        $html = '';

        if (is_null($input_id)) {
            $input_id = $input_name;
        }

        $input_id .= '_' . rand(111111, 999999);
        if (is_null($value)) {
            $value = '';
        }
//        elseif (preg_match('/^([0-9]{4})\-([0-9][0-9])\-([0-9][0-9]).*$/', $value, $matches)) {
//            if (!(int) $matches[1] || !(int) $matches[2] || !(int) $matches[3]) {
//                $value = '';
//            }
//        }

        $extra_class = isset($options['extra_class']) ? $options['extra_class'] : '';

        $display_js_format = '';
        $js_format = '';
        $php_format = '';
        $dt_value = null;
        switch ($type) {
            case 'time':
                if (BimpTools::getArrayValueFromPath($options, 'with_secondes', 1)) {
                    $display_js_format = 'HH:mm:ss';
                    $js_format = 'HH:mm:ss';
                } else {
                    $display_js_format = 'HH:mm';
                    $js_format = 'HH:mm';
                }

                $php_format = 'H:i:s';
                if ($value) {
                    if (preg_match('/^(\d{2}):(\d{2}):?(\d{2})?$/', $value)) {
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
                if (BimpTools::getArrayValueFromPath($options, 'with_secondes', 1)) {
                    $display_js_format = 'Do MMMM YYYY HH:mm';
                    $js_format = 'YYYY-MM-DD HH:mm';
                } else {
                    $display_js_format = 'Do MMMM YYYY HH:mm:ss';
                    $js_format = 'YYYY-MM-DD HH:mm:ss';
                }

                $php_format = 'Y-m-d H:i:s';
                if ($value) {
                    if (preg_match('/^(\d{4})\-(\d{2})\-(\d{2})( (\d{2}):(\d{2}):?(\d{2})?)?$/', $value)) {
                        $dt_value = new DateTime($value);
                    }
                }
                break;
        }

        if (!$value && isset($options['display_now']) && $options['display_now']) {
            $value = date($php_format);
            $dt_value = new DateTime($value);
        }

        $html .= '<div class="dropdown">';
        $html .= '<input type="hidden" class="datepicker_value' . ($extra_class ? ' ' . $extra_class : '') . '" id="' . $input_id . '" name="' . $input_name . '" value="';
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
            $html .= "defaultDate: moment('" . $dt_value->format($php_format) . "', '" . $js_format . "'),";
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
        $html .= '</div>';

        return $html;
    }

    public static function renderSwitchOptionsInput($input_name, $options, $value = '', $input_id = null, $vertical = false, $tag_data = '')
    {
        $html = '<div class="switchInputContainer" data-input_name="' . $input_name . '">';
        $html .= '<input type="hidden" name="' . $input_name . '"';
        if (!is_null($input_id)) {
            $html .= ' id="' . $input_id . '"';
        }
        $html .= ' value="' . $value . '"' . $tag_data . '/>';

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
        $html = '';

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
                    $opt_filters = $filters;

                    if (isset($opt_params['filters']) && is_array($opt_params['filters'])) {
                        foreach ($opt_params['filters'] as $field => $filter) {
                            $opt_filters = BimpTools::mergeSqlFilter($opt_filters, $field, $filter);
                        }
                    }

                    if (!is_null($opt_fields_search) && !is_null($opt_label)) {
                        $html .= '<input type="hidden" id="searchList_' . $opt_name . '_fields_search" value="' . $opt_fields_search . '"/>';
                        if ($opt_join) {
                            $html .= '<input type="hidden" id="searchList_' . $opt_name . '_join" value="' . $opt_join . '"/>';
                        }
                        if ($opt_join_on) {
                            $html .= '<input type="hidden" id="searchList_' . $opt_name . '_join_on" value="' . $opt_join_on . '"/>';
                        }
                        if ($opt_join_return_label) {
                            $html .= '<input type="hidden" id="searchList_' . $opt_name . '_join_return_label" value="' . $opt_join_return_label . '"/>';
                        }
                        if (count($opt_filters)) {
                            $html .= '<input type="hidden" id="searchList_' . $opt_name . '_filters" value="' . htmlentities(json_encode($opt_filters)) . '"/>';
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

        if (!is_array($field_return_label)) {
            $field_return_label = explode(',', $field_return_label);
        }

        $search = '';
        if ($value) {
            global $db;
            $bdb = new BimpDb($db);
            if (!is_array($fields_search)) {
                $fields_search = explode(',', $fields_search);
            }

            $sql = 'SELECT ';
            $i = 1;
            $fl = true;
            foreach ($field_return_label as $field_name) {
                if (!$fl) {
                    $sql .= ', ';
                } else {
                    $fl = false;
                }

                if (!preg_match('/\./', $field_name)) {
                    $sql .= 'a.';
                }
                $sql .= $field_name . ' as label_' . $i;
                $i++;
            }

            if (!preg_match('/ +/', $table)) {
                $table .= ' a';
            }

            $sql .= ' FROM ' . MAIN_DB_PREFIX . $table;
            if ($join) {
                $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . $join . ' ON ' . $join_on;
            }
            $sql .= ' WHERE ';

            if (!preg_match('/\./', $field_return_value)) {
                $sql .= 'a.';
            }
            $sql .= $field_return_value . ' = ' . (is_string($value) ? '\'' . $value . '\'' : $value);

            $result = $bdb->executeS($sql, 'array');

            if (is_null($result)) {
                $search = '';
            } else {
                $search = $label_syntaxe;
                for ($n = 1; $n <= count($field_return_label); $n++) {
                    $search = str_replace('<label_' . $n . '>', $result[$n - 1]['label_' . $n], $search);
                }
            }
            unset($bdb);
        }

        if (is_array($fields_search)) {
            $fields_search = implode(',', $fields_search);
        }

        $html .= '<input type="hidden" name="' . $input_name . '" value="' . $value . '"/>';
        $html .= '<input type="hidden" name="' . $input_name . '_label" value="' . $search . '"/>';
        $html .= '<input type="text" name="' . $input_name . '_search" class="search_list_input" value="" autocomplete="off"';
        $html .= ' data-table="' . $table . '"';
        $html .= ' data-join="' . $join . '"';
        $html .= ' data-join_on="' . $join_on . '"';
        $html .= ' data-fields_search="' . $fields_search . '"';
        $html .= ' data-field_return_label="' . implode(',', $field_return_label) . '"';
        $html .= ' data-field_return_value="' . $field_return_value . '"';
        $html .= ' data-join_return_label="' . $join_return_label . '"';
        $html .= ' data-label_syntaxe="' . htmlentities($label_syntaxe) . '"';
        $html .= ' data-filters="' . htmlentities(json_encode($filters)) . '"';
        $html .= '/>';
        $html .= '<i class="loading fa fa-spinner fa-spin"></i>';
        $html .= '<div class="search_input_results input_choices hideOnClickOut"></div>';
        $html .= '<div class="search_input_selected_label"' . (!$search ? ' style="display: none"' : '') . '>';
        $html .= '<i class="fa fa-check iconLeft"></i>';
        $html .= '<span class="">' . $search . '</span>';
        $html .= '</div>';
        return $html;
    }

    public static function renderSearchObjectInput(BimpObject $object, $input_name, $value, $params)
    {
        $html = '';

        if (!is_a($object, 'BimpObject')) {
            return BimpRender::renderAlerts('Erreur: objet invalide');
        }

        if (!(int) $value && BimpObject::objectLoaded($object)) {
            $value = $object->id;
        }

        if ((int) $value && !BimpObject::objectLoaded($object)) {
            $object = BimpCache::getBimpObjectInstance($object->module, $object->object_name, (int) $value);
        }

        $search_name = (isset($params['search_name']) ? $params['search_name'] : 'default');
        $card = (isset($params['card']) ? $params['card'] : '');
        $max_results = (isset($params['max_results']) ? $params['max_results'] : 200);
        $display_results = (isset($params['display_results']) ? (int) $params['display_results'] : 1);
        $data = '';

        if (isset($params['data'])) {
            $data .= BimpRender::renderTagData($params['data']);
        }

        $extra_class = BimpTools::getArrayValueFromPath($params, 'extra_class', '');

        $html .= '<div class="search_object_input_container" data-input_name="' . $input_name . '">';
        $html .= '<input type="hidden" name="' . $input_name . '" value="' . $value . '"' . $data . ' class="search_object_input_value' . ($extra_class ? ' ' . $extra_class : '') . '"/>';

        $html .= '<div class="search_object_input">';
        $html .= '<span class="search_icon">' . BimpRender::renderIcon('fas_search') . '</span>';
        $html .= '<input type="text" name="' . $input_name . '_search" value=""';
        $html .= ' data-ajax_data="' . htmlentities(json_encode(array(
                    'module'      => $object->module,
                    'object_name' => $object->object_name,
                    'search_name' => $search_name,
                    'card'        => $card,
                    'max_results' => $max_results
                ))) . '"';
        $html .= ' data-display_results="' . $display_results . '"';
        $html .= ' autocomplete="off" class="search_object_search_input"/>';
        $html .= '<span class="spinner"><i class="fa fa-spinner fa-spin"></i></span>';
        if (isset($params['scanner']) && (int) $params['scanner']) {
            $onclick = 'var $parent = $(this).parent();';
            $onclick .= 'if ($parent.hasClass(\'input-group\')) {';
            $onclick .= '$parent = $parent.parent().parent();';
            $onclick .= '}';
            $onclick .= 'var $input = $parent.find(\'input[name=' . $input_name . '_search]\');';
            $onclick .= 'BIS.openModal($input);';
            $html .= BimpRender::renderRowButton('Scanner code-barres / Qr-Code', 'fas_camera', $onclick);
        }
        $html .= '</div>';

        if (isset($params['help']) && $params['help']) {
            $html .= '<p class="inputHelp">';
            $html .= $params['help'];
            $html .= '</p>';
        }

        $html .= '<div class="search_object_result"' . (!$value ? ' style="display: none"' : '') . '>';
        if ($value) {
            if (BimpObject::objectLoaded($object)) {
                if ($card) {
                    $bc_card = new BC_Card($object, null, $card);
                    $html .= $bc_card->renderHtml();
                } else {
                    $html .= $object->getName();
                }
            } else {
                $html .= BimpRender::renderAlerts(BimpTools::ucfirst($object->getLabel('the')) . ' d\'ID ' . $value . ' n\'existe pas');
            }
        }
        $html .= '</div>';

        $html .= '<div class="no_item_selected" ' . ($value ? ' style="display: none"' : '') . '>';
        $html .= BimpRender::renderAlerts('Aucun' . $object->e() . ' ' . $object->getLabel() . ' sélectionné' . $object->e(), 'warning');
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    public static function renderTimerInput($field_name, $value, $options, $input_id = '')
    {
        if (is_null($value)) {
            $value = 0;
        }
        $timer = BimpTools::getTimeDataFromSeconds((int) $value);

        $html = '';
        $html .= '<div class="timer_input">';
        $html .= '<input type="hidden" name="' . $field_name . '"' . ($input_id ? ' id="' . $input_id . '"' : '') . ' value="' . $value . '"/>';

        $html .= '<input type="text" class="' . $field_name . '_time_value time_input_value" value="' . (int) $timer['days'] . '" name="' . $field_name . '_days"/>';
        $html .= '<span>j</span>';

        $html .= '<select name="' . $field_name . '_hours" class="' . $field_name . '_time_value time_input_value no_select2">';
        for ($i = 0; $i < 24; $i++) {
            $html .= '<option value="' . $i . '"' . ((int) $i === (int) ($timer['hours']) ? ' selected' : '') . '>' . $i . '</option>';
        }
        $html .= '</select>';
        $html .= '<span>h</span>';

        $html .= '<select name="' . $field_name . '_minutes" class="' . $field_name . '_time_value time_input_value no_select2">';
        for ($i = 0; $i < 60; $i++) {
            $html .= '<option value="' . $i . '"' . ((int) $i === (int) ($timer['minutes']) ? ' selected' : '') . '>' . $i . '</option>';
        }
        $html .= '</select>';
        $html .= '<span>min</span>';

        if (BimpTools::getArrayValueFromPath($options, 'with_secondes', 1)) {
            $html .= '<select name="' . $field_name . '_secondes" class="' . $field_name . '_time_value time_input_value no_select2">';
            for ($i = 0; $i < 60; $i++) {
                $html .= '<option value="' . $i . '"' . ((int) $i === (int) ($timer['secondes']) ? ' selected' : '') . '>' . $i . '</option>';
            }
            $html .= '</select>';
            $html .= '<span>sec</span>';
        } else {
            $html .= '<input type="hidden" name="' . $field_name . '_secondes" value="' . (int) $timer['secondes'] . '"/>';
        }

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

    public static function renderMultipleValuesInput($object, $input_name, $add_input_content, $values, $label_input_suffixe = '', $auto_save = false, $required = false, $sortable = false, $max_values = 'none', $items_options = array(), $add_all_btn = false)
    {
        $html = '';

        $add_value_input_name = $input_name . '_add_value';
        $label_input_name = $add_value_input_name . $label_input_suffixe;

        $content = '<div class="addValueInputContainer">';
        $content .= $add_input_content;
        $content .= '<button type="button" class="addValueBtn btn btn-primary" ';
        $content .= 'onclick="addMultipleInputCurrentValue($(this), \'' . $add_value_input_name . '\', \'' . $label_input_name . '\', ' . ($auto_save ? 'true' : 'false') . ')">';
        $content .= '<i class="fa fa-plus-circle iconLeft"></i>Ajouter</button>';

        if ($add_all_btn) {
            $content .= '<span class="addAllValuesBtn btn btn-default"';
            $content .= ' onclick="addMultipeInputAllValues($(this), \'' . $add_value_input_name . '\', \'' . $label_input_name . '\', ' . ($auto_save ? 'true' : 'false') . ')">';
            $content .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Tout ajouter';
            $content .= '</span>';
        }

        $content .= '</div>';

        $html = $content;

        $html .= self::renderMultipleValuesList($object, $input_name, $values, $label_input_name, $auto_save, $required, $sortable, $max_values, $items_options);

        return $html;
    }

    public static function renderMultipleValuesList(BimpObject $object, $field_name, $values, $label_input_name = null, $autosave = false, $required = 0, $sortable = 0, $max_values = 'none', $items_options = array())
    {
        if (is_null($values) || $values === '') {
            $values = array();
        }
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
            if (!BimpObject::objectLoaded($object)) {
                $autosave = false;
            }
        }

        $html .= '<div class="inputMultipleValuesContainer" data-field_name="' . $field_name . '" data-sortable="' . (int) $sortable . '"';
        if ($autosave) {
            $html .= ' data-module="' . $object->module . '"';
            $html .= ' data-object_name="' . $object->object_name . '"';
            $html .= ' data-id_object="' . $object->id . '"';
        }
        $html .= ' data-required="' . $required . '"';
        $html .= ' data-max_values="' . $max_values . '"';
        $html .= '>';

        if (is_array($items_options) && !empty($items_options)) {
            $html .= '<div class="multiple_values_items_options">';

            foreach ($items_options as $option_name => $option_data) {
                $html .= '<div class="item_option" data-name="' . $option_name . '">';
                $html .= '<div class="item_option_label">';
                $html .= BimpTools::getArrayValueFromPath($option_data, 'label', $option_name) . ': ';
                $html .= '</div>';
                $html .= '<div class="item_option_input">';
                $html .= BimpTools::getArrayValueFromPath($option_data, 'input', '');
                $html .= '</div>';
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '<div class="inputMultipleValues">';
        $html .= '<table>';
        $html .= '<thead></thead>';
        $html .= '<tbody class="multipleValuesList">';

        foreach ($values as $value => $label) {
            $html .= '<tr class="itemRow">';
            $html .= '<td style="display: none">';
            $html .= '<input class="item_value" type="hidden" name="' . $field_name . '[]" value="' . $value . '"/>';
            $html .= '</td>';
            if ((int) $sortable) {
                $html .= '<td class="positionHandle"><span></span></td>';
            }
            $html .= '<td class="item_label">' . $label . '</td>';
            $html .= '<td class="removeButton"><button type="button" class="btn btn-light-danger iconBtn" onclick="';
            if ($autosave) {
                $html .= 'var $button = $(this); deleteObjectMultipleValuesItem(\'' . $object->module . '\', \'' . $object->object_name . '\', ';
                $html .= $object->id . ', \'' . $field_name . '\', \'' . $value . '\', null, function() {';
                $html .= 'removeMultipleInputValue($button, \'' . $value_input_name . '\');});';
            } else {
                $html .= 'removeMultipleInputValue($(this), \'' . $value_input_name . '\');';
            }
            $html .= '"><i class="fas fa5-trash-alt"></i></button></td>';
            $html .= '</tr>';
        }

        $html .= '<tr class="noItemRow"' . (count($values) ? ' style="display: none"' : '') . '>';
        $html .= '<td colspan="3">';
        $html .= BimpRender::renderAlerts('Aucun élément sélectionné', 'warning');

        if (!count($values)) {
            $html .= '<input class="no_item_input" type="hidden" value="" name="' . $field_name . '"/>';
        }

        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        if ($max_values !== 'none') {
            $html .= '<p class="small">Max: ' . $max_values . ' élément' . ((int) $max_values > 1 ? 's' : '') . '</p>';
        }

        $html .= '</div>';

        return $html;
    }

    public static function renderInputContainer($input_name, $value, $content = '', $field_prefix = '', $required = 0, $multiple = 0, $extra_class = '', $extra_data = array())
    {
        $html = '';

        if (is_null($value)) {
            $value = '';
        }

        if (is_array($value)) {
            $value = implode(',', $value);
        }

        if (is_string($value)) {
            $value = htmlentities($value);
        }

        $html .= '<div class="inputContainer ' . $field_prefix . $input_name . '_inputContainer ' . $extra_class . '"';
        $html .= ' data-field_name="' . $field_prefix . $input_name . '"';
        $html .= ' data-initial_value="' . htmlentities($value) . '"';
        $html .= ' data-multiple="' . (int) $multiple . '"';
        $html .= ' data-field_prefix="' . $field_prefix . '"';
        $html .= ' data-required="' . (int) $required . '"';

        foreach ($extra_data as $data_key => $data_value) {
            $html .= ' data-' . $data_key . '="' . $data_value . '"';
        }

        $html .= '>';

        $html .= $content;

        $html .= '</div>';

        return $html;
    }

    public static function renderSearchInputContainer($input_name, $search_type, $search_on_key_up, $min_chars = 1, $content = '', $extra_data = array())
    {
        // Utilisé uniquement dans les barres de recherche des listes. 
        $html = '';
        $html .= '<div class="searchInputContainer"';
        $html .= ' data-field_name="' . $input_name . '"';
        $html .= ' data-search_type="' . $search_type . '"';
        $html .= ' data-search_on_key_up="' . $search_on_key_up . '"';
        $html .= ' data-min_chars="' . $min_chars . '"';

        foreach ($extra_data as $data_name => $data_value) {
            $html .= ' data-' . $data_name . '="' . $data_value . '"';
        }
        $html .= '>';

        $html .= $content;

        $html .= '</div>';

        return $html;
    }

    public static function renderSelectOption($option_value, $option, $value)
    {
        $html = '';
        $color = null;
        $icon = null;
        $disabled = false;
        $data = array();

        if (is_array($option)) {
            if (isset($option['label'])) {
                $label = $option['label'];
            } elseif (isset($option['value'])) {
                $label = $option['value'];
            } else {
                $label = $option_value;
            }

            if (isset($option['value'])) {
                $option_value = $option['value'];
            }

            if (isset($option['disabled']) && (int) $option['disabled']) {
                $disabled = true;
            }

            if (isset($option['data']) && is_array($option['data'])) {
                $data = $option['data'];
            }

            if (isset($option['color'])) {
                $color = $option['color'];
            } elseif (isset($option['classes'])) {
                $color = BimpTools::getAlertColor($option['classes'][0]);
            }
            if (isset($option['icon'])) {
                $icon = BimpRender::renderIconClass($option['icon']);
            }
            if (isset($option['group'])) {
                $html .= '<optgroup label="' . (isset($option['group']['label']) ? $option['group']['label'] : '') . '"';
                if (!is_null($color)) {
                    $html .= ' data-color="' . $color . '" style="color: #' . $color . '"';
                }
                if (!is_null($icon)) {
                    $html .= ' data-icon_class="' . $icon . '"';
                }
                $html .= '>';
                if (isset($option['group']['options']) && is_array($option['group']['options'])) {
                    foreach ($option['group']['options'] as $opt_value => $opt) {
                        $html .= self::renderSelectOption($opt_value, $opt, $value);
                    }
                }
                $html .= '</optgroup>';
                return $html;
            }
        } else {
            $label = $option;
        }

        $html .= '<option value="' . $option_value . '"';
        if ((string) $value == (string) $option_value) {
            $html .= ' selected="1"';
        }
        if (!is_null($color)) {
            $html .= ' data-color="' . $color . '" style="color: #' . $color . '"';
        }
        if (!is_null($icon)) {
            $html .= ' data-icon_class="' . $icon . '"';
        }

        $html .= BimpRender::renderTagData($data);

        if ($disabled) {
            $html .= ' disabled';
        }

        $html .= '>' . $label . '</option>';

        return $html;
    }

    public static function renderCheckListItem($field_name, $input_id, &$i, $idx, $item, &$nb_selected, $values = array(), &$child_selected = false)
    {
        $html = '';

        $item_children = array();

        if (is_array($item)) {
            $item_value = isset($item['value']) ? $item['value'] : $idx;
            $item_label = isset($item['label']) ? $item['label'] : 'n°' . $idx;
            $item_children = isset($item['children']) ? $item['children'] : array();
            $group_selectable = isset($item['selectable']) ? (int) $item['selectable'] : 1;
            $group_open = isset($item['open']) ? (int) $item['open'] : 1;
            $select_all_btn = isset($item['select_all_btn']) ? (int) $item['select_all_btn'] : 1;
        } else {
            $item_value = $idx;
            $item_label = (string) $item;
        }
        $i++;
        $rand = rand(111111, 999999);

        if (!empty($item_children)) {
            $children_html = '';

            $has_child_selected = false;
            foreach ($item_children as $child_idx => $child_item) {
                $children_html .= self::renderCheckListItem($field_name, $input_id, $i, $child_idx, $child_item, $nb_selected, $values, $has_child_selected);
            }

            if ($has_child_selected) {
                $child_selected = true;
            }

            $html .= '<div class="check_list_group ' . ($has_child_selected || $group_open ? 'open' : 'closed') . '">';

            if ($group_selectable) {
                $html .= '<input type="checkbox" name="' . $field_name . '[]" value="' . $item_value . '" id="' . $input_id . '_' . $i . '_' . $rand . '"';
                if (in_array($item_value, $values)) {
                    $child_selected = true;
                    $nb_selected++;
                    $html .= ' checked';
                }
                $html .= ' class="' . $field_name . '_check check_list_item_input check_list_group_input"/>';
            }

            $html .= '<div class="check_list_group_caption' . ($group_selectable ? ' selectable' : '') . '">';
            $html .= '<span class="check_list_group_title">';
            $html .= $item_label;
            $html .= '</span>';
            $html .= '</div>';

            $html .= '<div class="check_list_group_items">';

            if ($select_all_btn && count($item_children) > 0) {
                $html .= self::renderToggleAllCheckboxes('$(this).parent().parent()', '.' . $field_name . '_check');
            }

            $html .= $children_html;

            $html .= '</div>';
            $html .= '</div>';
        } else {
            $html .= '<div class="check_list_item">';
            $html .= '<input type="checkbox" name="' . $field_name . '[]" value="' . $item_value . '" id="' . $input_id . '_' . $i . '_' . $rand . '"';
            if (in_array($item_value, $values)) {
                $child_selected = true;
                $nb_selected++;
                $html .= ' checked';
            }
            $html .= ' class="' . $field_name . '_check check_list_item_input"/>';
            $html .= '<label for="' . $input_id . '_' . $i . '_' . $rand . '">';
            $html .= $item_label;
            $html .= '</label>';
            $html .= '</div>';
        }

        return $html;
    }

    public static function renderDispatcherAvailableItems($title, $input_name, $items, $options = array())
    {
        $html = '';

        return $html;
    }

    public static function renderDispatchedItems($input_name, $items, $options = array())
    {
        $html = '';

        return $html;
    }

    public static function renderJsonInput($data, $parent_name)
    {
        $html = '';

        $html .= '<table class="bimp_list_table bimp_json_values_table">';
        $html .= '<tbody class="headers_col">';
        foreach ($data as $data_name => $subData) {
            $input_name = (isset($subData['input_name']) ? $subData['input_name'] : '');
            $label = (isset($subData['label']) ? $subData['label'] : $data_name);

            $html .= '<tr id="' . $data_name . '" class="bimp_json_input_value ' . $parent_name . '_value"';
            $html .= ' data-value_name="' . $data_name . '"';
            $html .= ' data-input_name="' . $input_name . '"';
            $html .= ' data-parent_name="' . $parent_name . '"';
            $html .= '>';

            if ($input_name) {
                $html .= '<th>' . $label . '</th>';
                $html .= '<td>';
                $html .= (isset($subData['content']) ? $subData['content'] : '');
                $html .= '</td>';
            } elseif (isset($subData['children'])) {
                $html .= '<td colspan="2" class="bimp_json_input_title">';
                $html .= $label;
                $html .= '</td></tr>';

                $html .= '<tr>';
                $html .= '<td colspan="2">';
                $html .= self::renderJsonInput($subData['children'], $parent_name . '_' . $data_name);
                $html .= '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    public static function renderToggleAllCheckboxes($container, $input_filter = '', $max_elements = 0)
    {
        $html = '';

        $html .= '<div class="smallActionsContainer">';
        $html .= '<span class="small-action" onclick="checkAll(' . $container . ', \'' . $input_filter . '\', ' . (int) $max_elements . ');">';
        $html .= BimpRender::renderIcon('fas_check-square', 'iconLeft');
        if (!$max_elements) {
            $html .= 'Tout sélectionner';
        } else {
            $html .= 'Sélectionner les ' . $max_elements . ' premiers éléments';
        }
        $html .= '</span>';

        if (!$max_elements) {
            $html .= '<span class="small-action" onclick="uncheckAll(' . $container . ', \'' . $input_filter . '\');">';
            $html .= BimpRender::renderIcon('far_square', 'iconLeft') . 'Tout désélectionner';
            $html .= '</span>';
            $html .= '</div>';
        }

        return $html;
    }

    public static function renderFiltersInputAddFilterInput($module = '', $object_name = '', $has_depends_on = false)
    {
        $html .= '';

        $html .= '<div class="filters_input_add_filter_form"' . ((!$module || !$object_name) ? ' style="display: none"' : '') . '>';
        if ($module && $object_name) {
            $obj = BimpObject::getInstance($module, $object_name);
            $html .= $obj->renderFiltersSelect();
        }
        $html .= '</div>';

        $html .= '<div class="no_object_notif"' . ($module && $object_name ? ' style="display: none"' : '') . '>';

        if ($has_depends_on) {
            $html .= BimpRender::renderAlerts('Veuillez sélectionner un type d\'objet', 'warning');
        } else {
            $html .= BimpRender::renderAlerts('Erreur: aucun type d\'objet spécifié');
        }

        $html .= '</div>';

        return BimpRender::renderPanel(BimpRender::renderIcon('fas_plus-circle') . 'Ajouter un filtre', $html, '', array(
                    'foldable' => 1,
                    'type'     => 'secondary'
        ));
    }

    public static function renderFiltersInputValues($module = '', $object_name = '', $values = array(), $content_only = false)
    {
        $html = '';

        $json_errors = array();

        $values = BimpTools::json_decode_array($values, $json_errors);

        if (!empty($json_errors)) {
            $html .= BimpRender::renderAlerts(BimpTools::getMsgFromArray($json_errors));
        } elseif (empty($values)) {
            $html .= '<div class="info">';
            $html .= 'Aucun filtre ajouté';
            $html .= '</div>';
        } elseif (!$module || !$object_name) {
            $html .= '<div class="danger">Type d\'objet absent</div>';
        } else {
            $object = BimpObject::getInstance($module, $object_name);

            $bc_panel = new BC_FiltersPanel($object);
            $bc_panel->setFilters($values);
            $html = $bc_panel->renderActiveFilters(true, true, 'filters_input');
        }

        if ($content_only) {
            return $html;
        }

        return BimpRender::renderPanel(BimpRender::renderIcon('fas_filter') . 'Filtres ajoutés', $html, '', array(
                    'foldable' => 1,
                    'type'     => 'secondary'
        ));
    }
}
