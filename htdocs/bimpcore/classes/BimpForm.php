<?php

class BimpForm
{

    public static $field_params_def = array(
        'label'         => array('required' => true),
        'type'          => array('default' => 'string'),
        'required'      => array('data_type' => 'bool', 'default' => 0),
        'values'        => array('data_type' => 'array', 'compile' => true, 'default' => null),
        'default_value' => array('data_type' => 'any', 'default' => null),
        'editable'      => array('data_type' => 'bool', 'default' => 1),
        'viewable'      => array('data_type' => 'bool', 'default' => 1),
        'required_if'   => array(),
        'dispay_if'     => array('data_type' => 'array', 'compile' => true)
    );
    public static $row_params_def = array(
        'label'  => array('default' => ''),
        'field'  => array('default' => ''),
        'custom' => array('data_type' => 'bool', 'default' => 0),
        'hidden' => array('data_type' => 'bool', 'default' => 0),
        'show'   => array('data_type' => 'bool', 'default' => 1)
    );
    public static $custom_row_params_def = array(
        'data_type'  => array('default' => 'string'),
        'input_name' => array('default' => '', 'required' => 1),
        'display_if' => array('data_type' => 'array', 'compile' => true)
    );

    // Single line form: 

    public static function renderSingleLineForm($inputs, $params = array())
    {
        $html .= '<div class="singleLineForm' . (isset($params['main_class']) ? ' ' . $params['main_class'] : '') . '"';
        if (isset($params['data'])) {
            $html .= BimpRender::renderTagData($data);
        }
        $html .= '>';

        if (isset($params['title']) && (string) $params['title']) {
            $html .= '<div class="singleLineFormCaption">';
            $html .= '<h4>';
            if (isset($params['icon']) && (string) $params['icon']) {
                $html .= BimpRender::renderIcon($params['icon'], 'iconLeft');
            }
            $html .= $params['title'];
            $html .= '</h4>';
            $html .= '</div>';
        }

        $html .= '<div class="singleLineFormContent">';

        foreach ($inputs as $input) {
            $content = '';
            $label = BimpTools::getArrayValueFromPath($input, 'label', '');
            $input_content = BimpTools::getArrayValueFromPath($input, 'content', '');
            $input_name = BimpTools::getArrayValueFromPath($input, 'input_name', '');
            $value = BimpTools::getArrayValueFromPath($input, 'value', '');

            if ($input_content && $input_name) {
                if ($label) {
                    $content .= '<label>' . $label . ': </label><br/>';
                }

                $content .= $input_content;
                $html .= BimpInput::renderInputContainer($input_name, $value, $content);
            }
        }

        if (isset($params['after_html'])) {
            $html .= $params['after_html'];
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    // Free form: 

    public static function renderFreeForm($rows, $buttons = array(), $title = '', $icon = '', $infos = array())
    {
        $html = '<div class="freeForm">';
        if ($title) {
            $html .= '<div class="freeFormTitle">';
            if ($icon) {
                $html .= BimpRender::renderIcon($icon, 'iconLeft');
            }
            $html .= $title;
            $html .= '</div>';
        }
        $html .= '<div class="freeFormContent">';

        if (count($infos)) {
            foreach ($infos as $info) {
                $html .= '<p class="alert alert-info">';
                $html .= $info;
                $html .= '</p>';
            }
        }

        foreach ($rows as $row) {
            $html .= '<div class="freeFormRow">';
            if (isset($row['label'])) {
                $html .= '<div class="freeFormLabel">' . $row['label'] . ': </div>';
            }
            if (isset($row['input'])) {
                $html .= '<div class="freeFormInput">';
                $html .= $row['input'];
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        $html .= '<div class="freeFormAjaxResult">';
        $html .= '</div>';

        if (!empty($buttons)) {
            $html .= '<div class="freeFormSubmit rightAlign">';
            foreach ($buttons as $button) {
                $html .= $button;
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
    
    public static function renderFormInputsGroup($title, $inputName, $inputs_content, $params = array())
    {
        // params: 
        // required (bool, fac.)
        // display_if (array, fac.) 

        $html = '';

        $html .= '<div class="formInputGroup' . (isset($params['display_if']) ? ' display_if' : '') . '" data-multiple="0" data-field_name="' . $inputName . '"';
        if (isset($params['display_if'])) {
            $html .= BC_Field::renderDisplayifDataStatic($params['display_if']);
        }
        $html .= '>';

        $html .= '<div class="formGroupHeading">';
        $html .= '<div class="formGroupTitle"><h3>' . $title . (isset($params['required']) && (int) $params['required'] ? '<sup>*</sup>' : '') . '</h3></div>';
        $html .= '</div>';

        $html .= $inputs_content;

        $html .= '</div>';

        return $html;
    }

    public static function renderFormGroupMultiple($items_contents, $inputName, $title, $params = array())
    {
        // params: 
        // item_label (string, fac.)
        // max_items (int, fac.)
        // required (bool, fac.)
        // display_if (array, fac.) 

        $html = '';

        $html .= '<div class="formInputGroup' . (isset($params['display_if']) ? ' display_if' : '') . '" data-field_name="' . $inputName . '" data-multiple="1"';
        if (isset($params['max_items'])) {
            $html .= ' data-max_items="' . $params['max_items'] . '"';
        }
        if (isset($params['display_if'])) {
            $html .= BC_Field::renderDisplayifDataStatic($params['display_if']);
        }
        $html .= '>';

        // En-tête: 
        $html .= '<div class="formGroupHeading">';
        $html .= '<div class="formGroupTitle">';
        $html .= '<h3>' . $title . ((isset($params['required']) && (int) $params['required']) ? '<sup>*</sup>' : '') . '</h3>';
        if (isset($params['max_items'])) {
            $html .= '<span class="small">&nbsp;&nbsp;' . $params['max_items'] . ' élément(s) max</span>';
        }
        $html .= '</div>';
        $html .= '</div>';

        // Template: 
        if (isset($items_contents['tpl'])) {
            $html .= '<div class="dataInputTemplate">';
            $html .= '<div class="formGroupMultipleItem">';
            $html .= '<div class="formGroupHeading">';
            $html .= '<div class="formGroupTitle">';
            $html .= '<h4>' . (isset($params['item_label']) ? $params['item_label'] . ' ' : '') . '#idx</h4>';
            $html .= '</div>';
            $html .= '<div class="formGroupButtons">';
            $html .= '<button class="btn btn-danger" onclick="removeFormGroupMultipleItem($(this))">';
            $html .= BimpRender::renderIcon('fas_trash-alt');
            $html .= '</button>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= $items_contents['tpl'];
            $html .= '</div>';
            $html .= '</div>';
        }

        // Liste des items: 
        $html .= '<div class="formGroupMultipleItems">';
        $i = 0;
        foreach ($items_contents as $idx => $item_content) {
            if ($idx === 'tpl') {
                continue;
            }

            $i++;
            $html .= '<div class="formGroupMultipleItem">';
            $html .= '<div class="formGroupHeading">';
            $html .= '<div class="formGroupTitle">';
            $html .= '<h4>' . (isset($params['item_label']) ? $params['item_label'] . ' ' : '') . '#' . $i . '</h4>';
            $html .= '</div>';
            $html .= '<div class="formGroupButtons">';
            $html .= '<button class="btn btn-danger" onclick="removeFormGroupMultipleItem($(this))">';
            $html .= BimpRender::renderIcon('fas_trash-alt');
            $html .= '</button>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= str_replace('idx', $i, $item_content);

            $html .= '</div>';
        }

        $html .= '</div>';

        // Bouton ajout: 
        $html .= '<div class="buttonsContainer align-right">';
        $html .= '<span class="btn btn-default" onclick="addFormGroupMultipleItem($(this), \'' . $inputName . '\')"><i class="fa fa-plus-circle iconLeft"></i>Ajouter</span>';
        $html .= '</div>';

        $html .= '<input type="hidden" name="' . $inputName . '_nextIdx" value="' . ($i + 1) . '"/>';

        $html .= '</div>';
        return $html;
    }
    
    
//    // Standard form: 
//
//    public static function renderBimpForm($rows, $params = array())
//    {
//        $html = '';
//
//        $params = BimpTools::overrideArray(array(
//                    'form_tag'      => true,
//                    'enctype'       => 'multipart/form-data',
//                    'form_id'       => '',
//                    'form_class'    => '',
//                    'form_data'     => array(),
//                    'hidden_values' => array()
//                        ), $params);
//
//        if ($params['form_tag']) {
//            $html .= '<form';
//            if ($params['form_id']) {
//                $html .= ' id="' . $params['form_id'] . '"';
//            }
//
//            $html .= ' class="bimp_form' . ($params['form_class'] ? ' ' . $params['form_class'] : '') . '"';
//
//            if (!empty($params['form_data'])) {
//                $html .= BimpRender::renderTagData($params['form_data']);
//            }
//            $html .= '>';
//        }
//
//        foreach ($rows as $row) {
//            $html .= self::renderFormRow($label, $input, $label_col);
//        }
//
//        if ($params['form_tag']) {
//            $html .= '</form>';
//        }
//
//        return $html;
//    }
//
//    // Standard forms elements: 
//
    public static function renderFormRow($row, $form_params = array())
    {
        $html = '';

        $label_col = (int) BimpTools::getArrayValueFromPath($form_params, 'label_col', 3);
        $class = BimpTools::getArrayValueFromPath($row, 'class', '');
        $input_class = BimpTools::getArrayValueFromPath($row, 'input_class', '');
        $data = BimpTools::getArrayValueFromPath($row, 'data', array());

        $html .= '<div class="row formRow' . ($class ? ' ' . $class : '');
        if (!empty($data)) {
            $html .= BimpRender::renderTagData($data);
        }
        $html .= '">';
        $html .= '<div class="inputLabel col-xs-12 col-sm-6 col-md-' . $label_col . '">';
        $html .= BimpTools::getArrayValueFromPath($row, 'label', '');
        if ((int) BimpTools::getArrayValueFromPath($row, 'required', 0)) {
            $html .= '<sup>*</sup>';
        }
        $html .= '</div>';

        $html .= '<div class="formRowInput field col-xs-12 col-sm-6 col-md-' . (12 - $label_col);
        if ($input_class) {
            $html .= ' ' . $input_class;
        }
        $html .= '">';
        $html .= $row['content'];
        $html .= '</div>';
        $html .= '</div>';

        if (isset($row['extra_html']) && $row['extra_html']) {
            $html .= $row['extra_html'];
        }
        return $html;
    }
//
//    public static function renderFormInputsGroupNew($group, $form_params = array())
//    {
//        $title = BimpTools::getArrayValueFromPath($group, 'title', '');
//        $required = (int) BimpTools::getArrayValueFromPath($group, 'required', 0);
//        $multiple = (int) BimpTools::getArrayValueFromPath($group, 'multiple', 0);
//        $class = BimpTools::getArrayValueFromPath($group, 'class', '');
//        $data = BimpTools::getArrayValueFromPath($group, 'data', '');
//        $input_name = BimpTools::getArrayValueFromPath($group, 'input_name', '');
//
//        $html = '';
//
//        $html .= '<div class="formInputGroup' . ($class ? ' ' . $class : '') . '" data-multiple="' . $multiple . '" data-field_name="' . $input_name . '"';
//        if (!empty($data)) {
//            $html .= BimpRender::renderTagData($data);
//        }
//        $html .= '>';
//
//        $html .= '<div class="formGroupHeading">';
//        $html .= '<div class="formGroupTitle"><h3>' . $title . ($required ? '<sup>*</sup>' : '') . '</h3></div>';
//        $html .= '</div>';
//
//        if ($multiple) {
//            $item_label = BimpTools::getArrayValueFromPath($group, 'item_label', '');
//            $tpl_content = BimpTools::getArrayValueFromPath($group, 'tpl_content', '');
//            $items = BimpTools::getArrayValueFromPath($group, 'items', array());
//
//            // Template: 
//
//            $html .= '<div class="dataInputTemplate">';
//            $html .= '<div class="formGroupMultipleItem">';
//            $html .= '<div class="formGroupHeading">';
//            $html .= '<div class="formGroupTitle">';
//            $html .= '<h4>' . $item_label . '#idx</h4>';
//            $html .= '</div>';
//            $html .= '<div class="formGroupButtons">';
//            $html .= '<button class="btn btn-danger" onclick="removeFormGroupMultipleItem($(this))">';
//            $html .= BimpRender::renderIcon('fas_trash-alt');
//            $html .= '</button>';
//            $html .= '</div>';
//            $html .= '</div>';
//            $html .= $tpl_content;
//            $html .= '</div>';
//            $html .= '</div>';
//
//            // Items: 
//            $html .= '<div class="formGroupMultipleItems">';
//            $i = 0;
//            foreach ($items as $idx => $item_content) {
//                if ($idx === 'tpl') {
//                    continue;
//                }
//
//                $i++;
//                $html .= '<div class="formGroupMultipleItem">';
//                $html .= '<div class="formGroupHeading">';
//                $html .= '<div class="formGroupTitle">';
//                $html .= '<h4>' . $item_label . '#' . $i . '</h4>';
//                $html .= '</div>';
//                $html .= '<div class="formGroupButtons">';
//                $html .= '<button class="btn btn-danger" onclick="removeFormGroupMultipleItem($(this))">';
//                $html .= BimpRender::renderIcon('fas_trash-alt');
//                $html .= '</button>';
//                $html .= '</div>';
//                $html .= '</div>';
//
//                $html .= str_replace('idx', $i, $item_content);
//
//                $html .= '</div>';
//            }
//
//            // Bouton ajout: 
//            $html .= '<div class="buttonsContainer align-right">';
//            $html .= '<span class="btn btn-default" onclick="addFormGroupMultipleItem($(this), \'' . $input_name . '\')"><i class="fa fa-plus-circle iconLeft"></i>Ajouter</span>';
//            $html .= '</div>';
//
//            $html .= '<input type="hidden" name="' . $input_name . '_nextIdx" value="' . ($i + 1) . '"/>';
//
//            $html .= '</div>';
//        } else {
//            $html .= BimpTools::getArrayValueFromPath($group, 'content', '');
//        }
//
//        $html .= '</div>';
//
//        return $html;
//    }
//
//    // Custom Form:
//
//    public static function renderFormFromConfigFile($dir, $file_name, $object = null, $form_name = 'default', $values = array(), $params = array())
//    {
//        $config = new BimpConfig($dir, $file_name, $object);
//
//        if (count($config->errors)) {
//            return BimpRender::renderAlerts(BimpTools::getMsgFromArray($config->errors, 'Erreur fichier de config "' . $file_name . '"'));
//        }
//
//        if (!$config->isDefined('forms/' . $form_name)) {
//            return BimpRender::renderAlerts('Formulaire "' . $form_name . '" absent du fichier "' . $file_name . '"');
//        }
//
//        $form_rows = $config->getCompiledParams('forms/' . $form_name . '/rows');
//
//        if (empty($form_rows) && $config->isDefined('fields')) {
//            $form_rows = array();
//
//            $fields = $config->get('fields', array(), false, 'array');
//
//            if (!empty($fields)) {
//                foreach ($fields as $field_name => $field_params) {
//                    $form_rows[] = array(
//                        'fields' => $field_name
//                    );
//                }
//            }
//        }
//
//        if (empty($form_rows)) {
//            return BimpRender::renderAlerts('Aucun champ');
//        }
//
//        $rows = array();
//
//        foreach ($form_rows as $row_name => $row_params) {
//            $row = self::getRowParamsFromConfig($config, 'forms/' . $form_name . '/rows/' . $row_name, $values);
//
//            if (!is_null($row)) {
//                $rows[] = $row;
//            }
//        }
//
//        $html = self::renderBimpForm($rows, $params);
//    }
//
//    // Getters config: 
//
//    public static function getRowParamsFromConfig(BimpConfig $config, $path, $values = array())
//    {
//        if ($config->isDefined($path)) {
//            $row = array();
//
//            if ($config->isDefined($path . '/group')) {
//                // Todo... 
//            } else {
//                $row_params = BimpComponent::fetchParamsStatic($config, $path, self::$row_params_def);
//
//                if (!(int) $row_params['show']) {
//                    return null;
//                }
//
//                $row = array(
//                    'label'       => '',
//                    'input_name'  => '',
//                    'required'    => 0,
//                    'class'       => '',
//                    'input_class' => '',
//                    'data'        => array(),
//                    'content'     => '',
//                    'extra_html'  => ''
//                );
//
//                if ($row_params['field']) {
////                    $field_params
//                }
//
//                if ((int) $row_params['custom']) {
//                    
//                }
//            }
//        }
//
//        return null;
//    }
}
