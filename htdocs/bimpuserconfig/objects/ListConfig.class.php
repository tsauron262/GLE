<?php

require_once DOL_DOCUMENT_ROOT . '/bimpuserconfig/objects/BCUserConfig.class.php';

class ListConfig extends BCUserConfig
{

    public static $config_object_name = 'ListConfig';
    public static $list_types = array(
        'list_table'  => 'Tableau',
        'list_stats'  => 'Statistiques',
        'list_custom' => 'Liste personnalisée'
    );
    public static $has_search = false;
    public static $has_filters = false;
    public static $has_total = false;
    public static $has_pagination = false;
    public static $has_cols = false;
    public static $nbItems = array(
        10 => '10',
        20 => '20',
        30 => '30',
        40 => '40',
        50 => '50'
    );

    // Getters booléens

    public function hasFiltersPanel()
    {
        if (!static::$has_filters) {
            return 0;
        }

        $obj = $this->getObjInstance();

        if (is_a($obj, 'BimpObject')) {
            $path = $this->getObjectConfigPath();

            if ($path) {
                if ((string) $obj->config->get($path . '/filters_panel', '')) {
                    return 1;
                }
            }
        }
        return 0;
    }

    public function hasPagination()
    {
        return (int) (static::$has_pagination && $this->isComponentParamActive('pagination', 1));
    }

    public function hasTotalRow()
    {
        return (int) (static::$has_total && (int) $this->isComponentParamActive('enable_total_row', 1));
    }

    public function hasCols()
    {
        return (int) static::$has_cols;
    }

    public function isListSortable()
    {
        return (int) ($this->isComponentParamActive('enable_sort', 1));
    }

    public function isListSearchable()
    {
        return (int) (static::$has_search && $this->isComponentParamActive('enable_search', 1));
    }

    // Getters array: 

    public function getOwnerFiltersConfigsArray($include_empty = true)
    {
        global $user;
        $object = $this->getObjInstance();

        if (is_a($object, 'BimpObject')) {
            BimpObject::loadClass('bimpuserconfig', 'FiltersConfig');
            switch ((int) $this->getData('owner_type')) {
                case self::OWNER_TYPE_USER:
                    return FiltersConfig::getUserConfigsArray($user->id, $object, '', $include_empty);

                case self::OWNER_TYPE_GROUP:
                    $id_group = (int) BimpTools::getPostFieldValue('id_group', $this->getData('id_owner'));
                    if ($id_group) {
                        return FiltersConfig::getGroupConfigsArray($id_group, $object, '', $include_empty);
                    }
                    break;
            }
        }

        return array(
            0 => ''
        );
    }

    public function getOwnerFiltersArray($include_empty = true)
    {
        global $user;

        $list_name = $this->getData('component_name');
        $list_type = static::$component_type;
        $object = $this->getObjInstance();

        if ($list_name && $list_type && is_a($object, 'BimpObject')) {
            $list_path = BC_List::getConfigPath($object, $list_name, $list_type);

            if ($list_path) {
                $panel_name = $object->getConf($list_path . '/filters_panel', '');
                if ($panel_name) {
                    BimpObject::loadClass('bimpuserconfig', 'ListFilters');
                    switch ((int) $this->getData('owner_type')) {
                        case self::OWNER_TYPE_USER:
                            return ListFilters::getUserConfigsArray($user->id, $object, '', $include_empty);

                        case self::OWNER_TYPE_GROUP:
                            $id_group = (int) BimpTools::getPostFieldValue('id_group', $this->getData('id_owner'));
                            if ($id_group) {
                                return ListFilters::getGroupConfigsArray($id_group, $object, '', $include_empty);
                            }
                            break;
                    }
                }
            }
        }

        return array(
            0 => ''
        );
    }

    public function getObjSortableFieldsArray()
    {
        $obj = $this->getObjInstance();

        if (is_a($obj, 'BimpObject')) {
            return $obj->getSortableFieldsArray();
        }

        return array();
    }

    public function getObjSortOptionsArray($field = null)
    {
        $obj = $this->getObjInstance();

        if (is_a($obj, 'BimpObject')) {
            return $obj->getSortOptionsArray();
        }

        return array();
    }

    // Getters: 

    public function getObjectConfigPath()
    {
        $path = '';
        $obj = $this->getObjInstance();
        if (is_a($obj, 'BimpObject') && (string) $this->getData('component_name')) {
            $path = BC_List::getConfigPath($obj, (string) $this->getData('component_name'), static::$component_type);
        }

        return $path;
    }

    public function getReloadListJsCallback()
    {
        return '';
    }

    public function getCreateJsCallback()
    {
        return $this->getReloadListJsCallback();
    }

    public function getUpdateJsCallback()
    {
        return $this->getReloadListJsCallback();
    }

    public function getListTitle()
    {
        global $user, $langs;

        return (BimpObject::objectLoaded($user) ? $user->getFullName($langs) . ': c' : 'C') . 'onfigurations de la liste';
    }

    public function getDefaultBulkCsvFileName()
    {
        return 'csv_' . date('Y-m-d_H-i');
    }

    public function getListConfigDefaultValues()
    {
        $values = parent::getConfigDefaultValues();

        $object = $this->getObjInstance();

        if (is_a($object, 'BimpObject')) {
            $list_path = $this->getObjectConfigPath();

            if ($list_path) {
                if ($this->hasPagination()) {
                    $values['nb_items'] = $object->getConf($list_path . '/n', 10, false, 'int');
                }
                if ($this->isListSortable()) {
                    $values['sort_field'] = $object->getConf($list_path . '/sort_field', $object->getPrimary());
                    $values['sort_way'] = $object->getConf($list_path . '/sort_way', 'desc');
                    $values['sort_option'] = $object->getConf($list_path . '/sort_option', '');
                }
            }
        }

        return $values;
    }

    // Getters params: 

    public function getCustomFilterValueLabel($field_name, $value)
    {
        switch ($field_name) {
            case 'id_group':
                $label = $this->db->getValue('usergroup', 'nom', 'rowid = ' . (int) $value);
                if ($label) {
                    return $label;
                }
                return $value;
        }
        parent::getCustomFilterValueLabel($field_name, $value);
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'id_user':
                $filters['and_user'] = array(
                    'and_fields' => array(
                        'owner_type' => self::TYPE_USER,
                        'id_owner'   => array(
                            ($excluded ? 'not_' : '') . 'in' => $values
                        )
                    )
                );
                break;

            case 'id_user_with_groups':
                $filters['user_group'] = array(
                    ($excluded ? 'and_fields' : 'or') => array(
                        'and_user'  => array(
                            'and_fields' => array(
                                'owner_type' => self::TYPE_USER,
                                'id_owner'   => array(
                                    ($excluded ? 'not_' : '') . 'in' => $values
                                )
                            )
                        ),
                        'and_group' => array(
                            'and_fields' => array(
                                'owner_type'   => self::TYPE_GROUP,
                                'owner_custom' => array(
                                    'custom' => 'id_owner ' . ($excluded ? 'NOT ' : '') . 'IN (SELECT ugu.fk_usergroup FROM ' . MAIN_DB_PREFIX . 'usergroup_user ugu WHERE ugu.fk_user IN (' . implode(',', $values) . '))'
                                )
                            )
                        )
                    )
                );
                break;

            case 'id_group':
                $filters['and_group'] = array(
                    'and_fields' => array(
                        'owner_type' => self::TYPE_GROUP,
                        'id_owner'   => array(
                            ($excluded ? 'not_' : '') . 'in' => $values
                        )
                    )
                );
                break;
        }
        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors, $excluded);
    }

    public function getObject_labelSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        if ((string) $value) {
            $data = explode('-', $value);
            if (isset($data[0]) && isset($data[1])) {
                $filters[$main_alias . '.obj_module'] = $data[0];
                $filters[$main_alias . '.obj_name'] = $data[1];
            }
        }
    }

    // Renders: 

    public function renderColsInput()
    {
        $html = '';

        $obj = $this->getObjInstance();

        if (is_a($obj, 'BimpObject')) {
            $html = $obj->renderListColsTypeSelect();
        }

        return $html;
    }

    public function renderColsInput_old()
    {
        if (!$this->hasCols()) {
            return '';
        }

        $html = '';

        $list_cols = array();
        $list_name = $this->getData('list_name');
        $list_type = $this->getData('list_type');
        $object = $this->getObjInstance();

        if ($list_name && is_a($object, 'BimpObject')) {
            switch ($list_type) {
                case 'list_table':
                    $list_cols = $object->getListColsArray($list_name);
                    break;

                case 'stats_list':
                    $list_cols = $object->getStatsListColsArray($list_name);
                    break;
            }
        }

        if (count($list_cols)) {
            $values = array();
            if ($this->isLoaded()) {
                $cols = $this->getData('cols');

                if (is_array($cols)) {
                    foreach ($cols as $col_name) {
                        if (isset($list_cols[$col_name])) {
                            $values[$col_name] = $list_cols[$col_name];
                        }
                    }
                }
            } else {
                $bc_list = new BC_ListTable($object, $list_name);
                foreach ($bc_list->cols as $col_name) {
                    if (isset($list_cols[$col_name])) {
                        $values[$col_name] = $list_cols[$col_name];
                    }
                }
            }

            $input = BimpInput::renderInput('select', 'cols_add_value', '', array('options' => $list_cols));
            $content = BimpInput::renderMultipleValuesInput($this, 'cols', $input, $values, '', 0, 1, 1);
            $html .= BimpInput::renderInputContainer('cols', '', $content, '', 0, 1, '', array('values_field' => 'cols'));
        } else {
            $html .= BimpRender::renderAlerts('Aucune option disponible', 'warnings');
        }

        return $html;
    }

    public function renderColsOptionsInput()
    {
        $html = '';

        return $html;
    }

    public function renderColsOptionsInput_old()
    {
        $html = '';

        $list_cols = array();


        $list_name = $this->getData('list_name');
        $cols_options = $this->getData('cols_options');

        $object = $this->getObjInstance();
        $bc_list = new BC_ListTable($object, $list_name);

        if ($list_name && is_a($object, 'BimpObject')) {
            $list_cols = $object->getListColsArray($list_name);
        }

        if (count($list_cols)) {
            $values = array();
            if ($this->isLoaded()) {
                $cols = $this->getData('cols');
                $cols_options = $this->getData('cols_options');

                if (is_array($cols)) {
                    foreach ($cols as $col_name) {
                        if (isset($list_cols[$col_name])) {
                            $values[$col_name] = $list_cols[$col_name];
                        }
                    }
                }
            } else {
                foreach ($bc_list->cols as $col_name) {
                    if (isset($list_cols[$col_name])) {
                        $values[$col_name] = $list_cols[$col_name];
                    }
                }
            }
        }

        if (count($values)) {
            $data = array();
            foreach ($values as $col_name => $col_title) {
                $csv_displays = '';

                $col_params = $bc_list->getColParams($col_name);
                $col_label = (isset($cols_options[$col_name]['label']) ? $cols_options[$col_name]['label'] : (isset($col_params['label']) ? $col_params['label'] : $col_name));
                $col_options = array();
                $col_displays = array();

                if (isset($col_params['field']) && $col_params['field']) {
                    $bc_field = null;
                    $instance = null;
                    if (isset($col_params['child']) && $col_params['child']) {
                        if ($col_params['child'] === 'parent') {
                            $instance = $object->getParentInstance();
                        } else {
                            $instance = $object->config->getObject('', $col_params['child']);
                        }
                    } else {
                        $instance = $object;
                    }

                    if (is_a($instance, 'BimpObject')) {
                        if ($instance->field_exists($col_params['field'])) {
                            $bc_field = new BC_Field($instance, $col_params['field']);
                            $col_displays = $bc_field->getDisplayOptions();
                            $csv_displays = $bc_field->renderCsvOptionsInput('col_' . $col_name . '_csv_display', (isset($cols_options[$col_name]['csv_display']) ? $cols_options[$col_name]['csv_display'] : ''));
                            if (!$col_label) {
                                $col_label = $bc_field->params['label'];
                            }
                        } else {
                            $csv_displays = BimpRender::renderAlerts('Le champ "' . $col_params['field'] . '" n\'existe pas dans l\'objet "' . $instance->getLabel() . '"');
                        }
                    } else {
                        $csv_displays = BimpRender::renderAlerts('Instance invalide');
                    }
                }

                if (!$col_label) {
                    $col_label = $col_name;
                }

                if (!$csv_displays) {
                    $csv_displays = 'Valeur affichée';
                }

                if (empty($col_displays)) {
                    $col_displays['default'] = 'Par défaut';
                }

                $col_options['label'] = array(
                    'label'      => 'Titre',
                    'input_name' => 'col_' . $col_name . '_label',
                    'content'    => BimpInput::renderInput('text', 'col_' . $col_name . '_label', $col_label)
                );

//                $col_options['display'] = array(
//                    'label'      => 'Affichage',
//                    'input_name' => 'col_' . $col_name . '_display',
//                    'content'    => BimpInput::renderInput('select', 'col_' . $col_name . '_display', 'default', array(
//                        'options' => $col_displays
//                    ))
//                );

                $col_options['csv_display'] = array(
                    'label'      => 'Valeur CSV',
                    'input_name' => 'col_' . $col_name . '_csv_display',
                    'content'    => $csv_displays
                );

                $data[$col_name] = array(
                    'label'    => $col_title,
                    'children' => $col_options
                );
            }

            $html .= BimpInput::renderJsonInput($data, 'cols_options');
        } else {
            $html .= BimpRender::renderAlerts('Aucune option disponible', 'warnings');
        }

        return $html;
    }

    // Actions: 

    public function actionGenerateBulkCsv($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Fichier Excel généré avec succès';
        $success_callback = '';

        $id_configs = isset($data['id_objects']) ? $data['id_objects'] : array();
        $file_name = isset($data['file_name']) ? $data['file_name'] : '';
        $headers = isset($data['headers']) ? (int) $data['headers'] : 1;
        $light_export = isset($data['light_export']) ? (int) $data['light_export'] : 0;

        if (empty($id_configs)) {
            $errors[] = 'Aucune configuration sélectionnée';
        }

        if (!$file_name) {
            $errors[] = 'Veuillez spécifier un nom de fichier';
        } else {
            $dir_error = BimpTools::makeDirectories(array(
                        'bimpcore' => 'lists_excel'
                            ), DOL_DATA_ROOT);
            if ($dir_error) {
                $errors[] = $dir_error;
            }
        }

        if (!count($errors)) {
            set_time_limit(0);
            ignore_user_abort(0);

            $file_path = DOL_DATA_ROOT . '/bimpcore/lists_excel/' . $file_name . '.xlsx';

            BimpCore::loadPhpExcel();
            $excel = new PHPExcel();

            $fl = true;

            $indexes = array();

            foreach ($id_configs as $id_config) {
                $config = BimpCache::getBimpObjectInstance('bimpcore', 'ListConfig', (int) $id_config);

                if (!BimpObject::objectLoaded($config)) {
                    $warnings[] = 'La configuration d\'ID ' . $id_config . ' n\'existe pas.<br/>La liste correspondante n\'a pas été incluse dans le fichier';
                    continue;
                }

                $config_label = '"' . $config->getData('name') . '" (' . $config->displayOwner() . ')';

                if ($config->getData('list_type') !== 'list_table') {
                    $warnings[] = 'La configuration ' . $config_label . ' ne correspond pas à une liste de type "Tableau".<br/>La liste correspondante n\'a pas été incluse dans le fichier';
                }

                $obj_instance = $config->getObjInstance();
                $bc_list = new BC_ListTable($obj_instance, $config->getData('list_name'), 1, null, null, null, (int) $config->id);
                if (!$bc_list->isOk()) {
                    $warnings[] = BimpTools::getMsgFromArray($bc_list->errors, 'La configuration "' . $config_label . '" est invalide');
                    continue;
                }

                $cols_options = $config->getData('cols_options');
                $options = array();

                if (is_array($cols_options)) {
                    foreach ($cols_options as $col_name => $col_options) {
                        if (isset($col_options['csv_display'])) {
                            $options[$col_name] = $col_options['csv_display'];
                        }
                    }
                }

                $list_errors = array();
                $rows = explode("\n", $bc_list->renderCsvContent(';', $options, $headers, $light_export, $list_errors));

                if (count($list_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($list_errors, 'Erreur pour la configuration ' . $config_label);
                    continue;
                }

                if (!$fl) {
                    $sheet = $excel->createSheet();
                } else {
                    $sheet = $excel->getActiveSheet();
                    $fl = false;
                }

                $title = substr($config->getData('sheet_name'), 0, 30);

                if (!$title) {
                    if (!isset($indexes[$obj_instance->module][$obj_instance->name])) {
                        $index = 0;
                    } else {
                        $index = $indexes[$obj_instance->module][$obj_instance->name];
                    }
                    $index++;
                    $title = substr(BimpTools::ucfirst($obj_instance->getLabel('name_plur')), 0, 27) . ' ' . $index;
                    $indexes[$obj_instance->module][$obj_instance->name] = $index;
                }

                $sheet->setTitle($title);

                $row = 1;

                foreach ($rows as $r) {
                    $col = 0;
                    $cols = explode(';', $r);

                    foreach ($cols as $cell) {
                        $sheet->setCellValueByColumnAndRow($col, $row, $cell);
                        $col++;
                    }

                    $row++;
                }
            }

            $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $writer->save($file_path);

            $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('lists_excel/' . $file_name . '.xlsx');
            $success_callback = 'window.open(\'' . $url . '\')';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }
}
