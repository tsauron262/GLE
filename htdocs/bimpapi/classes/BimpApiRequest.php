<?php

class BimpApiRequest
{

    public static $default_field_params = array(
        'label'         => '',
        'type'          => 'string',
        'hidden'        => 0,
        'edit'          => 1,
        'required'      => 0,
        'multiple'      => 0,
        'process'       => 1,
        'default_value' => null,
        'display_if'    => null,
        'help'          => '',
        'warning'       => ''
    );
    public static $data_definition_attributes = array(
        'type', 'max', 'values', 'regex', 'itemLabel', 'format', 'data_type', 'edit'
    );
    protected $api = null;
    public $config = null;
    public $errors = array();
    public $lastError = null;
    protected $defsDoc = null;
    protected $data = array(
        'request'  => null,
        'response' => null,
    );
    public $requestName = '';
    public $request = '';
    public $requestLabel = '';
    protected $requestOk = false;

    public function __construct($api, $request_name)
    {
        $this->api = $api;
        $this->requestName = $request_name;

        if (!is_a($this->api, 'BimpAPI')) {
            $this->addError('API absente ou invalide', true, Bimp_Log::BIMP_LOG_URGENT);
            return;
        }

        // Chargement des définitions:
        $dir = DOL_DOCUMENT_ROOT . '/bimpapi/forms/' . $this->api::$name;
        $file_name = $request_name . '.yml';

        if (!file_exists($dir . '/' . $file_name)) {
            $this->addError('Erreur: le fichier "' . $dir . '/' . $file_name . '" n\'existe pas.', true, Bimp_Log::BIMP_LOG_URGENT);
        } else {
            $this->config = new BimpConfig('bimpapi', 'forms', $request_name, $this);

            if (count($this->config->errors)) {
                $this->addError(BimpTools::getMsgFromArray($this->config->errors, 'Echec du chargement du fichier yml "' . $file_name . '"'), true, Bimp_Log::BIMP_LOG_URGENT);
            }
        }
    }

    public function addError($msg, $log_error = false, $log_level = Bimp_Log::BIMP_LOG_ERREUR)
    {
        $this->lastError = $msg;
        $this->errors[] = $this->lastError;

        if ($log_error) {
            BimpCore::addlog('Erreur API RequestForm', $log_level, 'api', null, array(
                'API'     => (is_a($this->api, 'BimpAPI') ? $this->api::$name : 'Iconnue'),
                'Requête' => ($this->requestName ? $this->requestName : 'Inconnue'),
                'Erreur'  => $msg,
            ));
        }
    }

    protected function getFieldInput($field_name, $params, $values = null, $index = null, $prefixe = '')
    {
        $params = BimpTools::overrideArray(self::$default_field_params, $params);

        if ((int) $params['hidden']) {
            return '';
        }

        $inputName = ($prefixe ? $prefixe . '_' : '') . $field_name . (isset($index) ? '_' . $index : '');
        $valuesName = $field_name;

        if (isset($values[$valuesName]) && $values[$valuesName] === 'hidden') {
            return '';
        }

        if ($params['type'] === 'custom_full_content') {
            if (isset($values[$valuesName]['content'])) {
                return $values[$valuesName]['content'];
            }
            return '';
        }

        $html = '';

        if ($params['type'] == 'fields_group') {
            $subFields = BimpTools::getArrayValueFromPath($params, 'fields', array());

            if (empty($subFields)) {
                $html .= '<p class="alert alert-warning">Aucunes définitions pour ces données</p>';
            } else {
                if ((int) $params['multiple']) {
                    $group_params = array(
                        'required'   => (int) $params['required'],
                        'display_if' => $params['display_if'],
                        'item_label' => BimpTools::getArrayValueFromPath($params, 'item_label', $params['label']),
                        'max_items'  => BimpTools::getArrayValueFromPath($params, 'max_items'),
                    );

                    // Template: 
                    $items_contents['tpl'] = '';

                    foreach ($subFields as $subfield_name => $subfield_params) {
                        $items_contents['tpl'] .= $this->getFieldInput($subfield_name, $subfield_params, null, 'idx', $inputName);
                    }

                    // Liste des items: 
                    if ((int) $params['required'] && (!isset($values[$valuesName]) || empty($values[$valuesName]))) {
                        $values[$valuesName] = array(
                            0 => array()
                        );
                    }

                    $i = 0;

                    if (isset($values[$valuesName]) && is_array($values[$valuesName])) {
                        foreach ($values[$valuesName] as $subValues) {
                            $i++;
                            $items_contents[$i] = '';
                            foreach ($subFields as $subfield_name => $subfield_params) {
                                $items_contents[$i] .= $this->getFieldInput($subfield_name, $subfield_params, $subValues, $i, $inputName);
                            }
                        }
                    }

                    $html .= BimpRender::renderFormGroupMultiple($items_contents, $inputName, $params['label'], $group_params);
                } else {
                    $inputsContent = '';
                    foreach ($subFields as $subField_name => $subField_params) {
                        $inputsContent .= $this->getFieldInput($subField_name, $subField_params, $values[$valuesName], null, $inputName);
                    }
                    $html .= BimpRender::renderFormInputsGroup($params['label'], $inputName, $inputsContent, $params);
                }
            }
        } else {
            $html .= '<div class="row formRow';
            if (!is_null($params['display_if'])) {
                $html .= ' display_if';
            }
            $html .= '"';
            if (!is_null($params['display_if'])) {
                $html .= BC_Field::renderDisplayifDataStatic($params['display_if']);
            }
            $html .= '>';
            $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">';
            $html .= $params['label'];
            if ((int) $params['required']) {
                $html .= '<sup>*</sup>';
            }
            $html .= '</div>';

            $inputValue = (isset($values[$valuesName]) ? $values[$valuesName] : $params['default_value']);

            $html .= '<div class="formRowInput col-xs-12 col-sm-6 col-md-9">';
            if ($params['warning']) {
                $html .= BimpRender::renderAlerts($params['warning'], 'warning');
            }

            if ((int) $params['multiple']) {
                $valuesArray = null;
                if (isset($values[$valuesName])) {
                    if (is_array($values[$valuesName])) {
                        $valuesArray = $values[$valuesName];
                    } else {
                        $valuesArray = array(
                            0 => $values[$valuesName]
                        );
                    }
                    $values[$valuesName] = null;
                }
                $multipleIndex = 0;
                $html .= '<span class="btn btn-default" onclick="duplicateInput($(this), \'' . $inputName . '\')"><i class="fa fa-plus-circle iconLeft"></i>Ajouter</span>';
                $html .= '<div class="inputTemplate">';
            }

            if (isset($params['values'])) {
                $params['type'] = 'select';
            }

            while (true) {
                $html .= '<div class="inputContainer">';
                if (!(int) $params['edit']) {
                    $html .= '<input type="hidden" name="' . $inputName . '" value="' . $inputValue . '"/>';
                    switch ($params['type']) {
                        case 'text':
                        case 'string':
                        case 'int':
                            $html .= $inputValue;
                            break;

                        case 'date':
                            if ($inputValue) {
                                $dt = new DateTime($inputValue);
                                $html .= $dt->format('d / m / Y');
                            }
                            break;

                        case 'time':
                            if ($inputValue) {
                                $dt = new DateTime($inputValue);
                                $html .= $dt->format('H:i:s');
                            }
                            break;

                        case 'datetime':
                            if ($inputValue) {
                                $dt = new DateTime($inputValue);
                                $html .= $dt->format('d / m / Y H:i:s');
                            }
                            break;

                        case 'select':
                            if (isset($params['values'][$inputValue])) {
                                if (is_string($params['values'][$inputValue])) {
                                    $html .= $params['values'][$inputValue];
                                } elseif (isset($params['values'][$inputValue]['label'])) {
                                    $classes = BimpTools::getArrayValueFromPath($params['values'][$inputValue], 'classes', array());
                                    if (!empty($classes)) {
                                        $html .= '<span class="' . implode(' ', $classes) . '">' . $params['values'][$inputValue]['label'] . '</span>';
                                    } else {
                                        $html .= $params['values'][$inputValue]['label'];
                                    }
                                }
                            } else {
                                $html .= $inputValue;
                            }
                            break;

                        case 'YesNo':
                        case 'bool':
                            if ((int) $inputValue) {
                                $html .= '<span class="success">OUI</span>';
                            } else {
                                $html .= '<span class="danger">NON</span>';
                            }
                    }
                } else {
                    switch ($params['type']) {
                        case 'string':
                        case 'tel':
                        case 'email':
                            $html .= BimpInput::renderInput('text', $inputName, $inputValue, array());
                            break;

                        case 'money':
                            $html .= BimpInput::renderInput('text', $inputName, $inputValue, array());
                            break;

                        case 'int':
                            $html .= BimpInput::renderInput('text', $inputName, $inputValue, array(
                                        'data' => array(
                                            'data_type' => 'number',
                                            'decimals'  => 0
                                        )
                            ));
                            break;

                        case 'text':
                            $html .= BimpInput::renderInput('textarea', $inputName, $inputValue, array(
                                        'rows'        => '3',
                                        'auto_expand' => true
                            ));
                            break;

                        case 'select':
                            $html .= BimpInput::renderInput('select', $inputName, $inputValue, array(
                                        'options' => BimpTools::getArrayValueFromPath($params, 'values', array())
                            ));
                            break;

                        case 'bool':
                            $html .= BimpInput::renderInput('toggle', $inputName, $inputValue, array());
                            break;

                        case 'date':
                            $html .= BimpInput::renderInput('date', $inputName, $inputValue);
                            break;

                        case 'time':
                            $html .= BimpInput::renderInput('time', $inputName, $inputValue);
                            break;

                        case 'datetime':
                            $html .= BimpInput::renderInput('datetime', $inputName, $inputValue);
                            break;

                        case 'file':
                            $html .= BimpInput::renderInput('file_upload', $inputName);
                            break;

                        default:
                            $html .= '<p class="alert alert-warning">Type inéxistant pour la donnée "' . $field_name . '"</p>';
                            break;
                    }
                    if ($params['help']) {
                        $html .= '<p class="inputHelp">' . $params['help'] . '</p>';
                    }
                }
                $html .= '</div>';

                if (!(int) $params['multiple']) {
                    break;
                } else {
                    if ($multipleIndex == 0) {
                        $html .= '</div>';
                        $html .= '<div class="inputsList">';
                    }
                    $inputName = $field_name . (isset($index) ? '_' . $index : '');
                    if (!isset($valuesArray[$multipleIndex])) {
                        $html .= '</div>';
                        $html .= '<input type="hidden" id="' . $inputName . '_nextIdx" name="' . $inputName . '_nextIdx" value="' . $multipleIndex . '"/>';
                    }
                    $values[$valuesName] = $valuesArray[$multipleIndex];
                    $multipleIndex++;
                    $inputName .= '_' . $multipleIndex;
                }
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        return $html;
    }

    public function generateRequestFormHtml($values, $extra_data = array(), $request_params = array(), $request_options = array())
    {
        if (count($this->errors)) {
            return BimpRender::renderAlerts(BimpTools::getMsgFromArray($this->errors, 'Impossible d\'afficher le formulaire pour cette requête'));
        }

        $html .= '<div class="container-fluid">';
        $html .= '<form id="api' . $this->api::$name . '_form_' . $this->requestName . '" class="bimp_api_request_form" enctype="multipart/form-data"';
        $html .= ' data-api_name="' . $this->api::$name . '"';
        $html .= '>';

        $html .= '<input name="api_requestForm" type="hidden" value="1"/>';
        $html .= '<input name="api_name" type="hidden" value="' . $this->api::$name . '"/>';
        $html .= '<input name="api_method" type="hidden" value="apiProcessRequestForm"/>';
        $html .= '<input name="api_requestName" type="hidden" value="' . $this->requestName . '"/>';
        $html .= '<input name="api_options" type="hidden" value="' . htmlentities(json_encode($request_options)) . '"/>';
        $html .= '<input name="api_params" type="hidden" value="' . htmlentities(json_encode($request_params)) . '"/>';

        foreach ($extra_data as $input_name => $value) {
            $html .= '<input name="' . $input_name . '" type="hidden" value="' . $value . '"/>';
        }

        $fields = $this->config->getCompiledParams('fields');
        foreach ($fields as $field_name => $field_params) {
            $html .= $this->getFieldInput($field_name, $field_params, $values);
        }

        $html .= '<div class="ajaxResultContainer" style="display: none"></div>';
        $html .= '</form>';
        $html .= '</div>';
        return $html;
    }

    public function checkInputData($field_params, $value)
    {
        switch (BimpTools::getArrayValueFromPath($field_params, 'type', 'string')) {
            case 'bool':
                $value = (bool) $value;
                if (!$value) {
                    $value = null;
                }
                break;

            case 'int':
                $value = (int) $value;
                if (isset($field_params['max'])) {
                    if ($value > (int) $field_params['max']) {
                        $this->addError('"' . $field_params['label'] . '": la valeur maximale autorisée (' . $defs['max'] . ') est dépassée');
                    }
                }
                break;
                
            case 'money':
                $value = (float) $value;
                break;

            case 'select':
                if (isset($field_params['data_type'])) {
                    switch ($field_params['data_type']) {
                        case 'int':
                            $value = (int) $value;
                            break;
                    }
                }
                break;

//            case 'text':
//                if (isset($defs['regex']) && (string) $defs['regex']) {
//                    if (!preg_match('/' . $defs['regex'] . '/', $value)) {
//                        $msg = '"' . $defs['label'] . '": format invalide';
//                        if (isset($defs['format'])) {
//                            $msg .= ' (attendu: ' . $defs['format'] . ')';
//                        }
//                        $this->addError($msg);
//                    }
//                }
//                if (isset($defs['max'])) {
//                    if (strlen($value) > (int) $defs['max']) {
//                        $this->addError('"' . $defs['label'] . '": le nombre de caractères autorisés (' . $defs['max'] . ') est dépassé');
//                    }
//                }
//                if ($value === '') {
//                    $value = null;
//                }
//                break;

//            case 'datetime':
//                if (preg_match('/^(\d{4}\-\d{2}\-\d{2}) (\d{2}:\d{2}:\d{2})$/', $value, $matches)) {
//                    $value = $matches[1] . 'T' . $matches[2] . 'Z';
//                }
//                break;

            case 'string':
            default:
                $value = (string) $value;
                break;
        }
        return $value;
    }

    public function processRequestData($fields, $fieldIndex = null, $fieldPrefix = '')
    {
        $datas = array();
        foreach ($fields as $field_name => $field_params) {
            $params = BimpTools::overrideArray(self::$default_field_params, $field_params);

            if (!(int) $params['process']) {
                continue;
            }

            // Check du display_if: 
            if (!is_null($params['display_if'])) {
                $input_name = BimpTools::getArrayValueFromPath($params['display_if'], 'field_name', '');
                if ($input_name) {
                    $inputValue = BimpTools::getValue($field_name, '');
                    $show_values = explode(',', BimpTools::getArrayValueFromPath($params['display_if'], 'show_values', ''));

                    if (!empty($show_values) && !in_array($inputValue, $show_values)) {
                        continue;
                    }

                    $hide_values = explode(',', BimpTools::getArrayValueFromPath($params['display_if'], 'hide_values', ''));

                    if (!empty($hide_values) && in_array($inputValue, $hide_values)) {
                        continue;
                    }
                }
            }

            $inputName = ($fieldPrefix ? $fieldPrefix . '_' : '') . $field_name;
            if (!is_null($fieldIndex)) {
                $inputName .= '_' . $fieldIndex;
            }

            if ($params['type'] === 'fields_group') {
                $subFields = BimpTools::getArrayValueFromPath($params, 'fields', array());
                if (!empty($subFields)) {
                    if ((int) $params['multiple']) {
                        $nextIdx = (int) BimpTools::getValue($inputName . '_nextIdx', 0);
                        if ($nextIdx) {
                            $array = array();
                            for ($i = 1; $i < $nextIdx; $i++) {
                                $result = $this->processRequestData($subFields, $i, $inputName);
                                if (!empty($result)) {
                                    $array[] = $result;
                                }
                            }
                            if (!empty($array)) {
                                $datas[$field_name] = $array;
                            }
                        }
                    } else {
                        $result = $this->processRequestData($subFields, null, $inputName);
                        if (!empty($result)) {
                            $datas[$field_name] = $result;
                        }
                    }
                } else {
                    $this->addError('Erreur dans le fichier "' . $this->requestName . '.yml": liste des données absentes pour le groupe "' . $field_name . '"');
                }
            } else {
                if ((int) $params['multiple']) {
                    $multipleIndex = 1;
                    $valuesArray = array();
                    while (true) {
                        if (isset($_POST[$inputName . '_' . $multipleIndex])) {
                            $value = $this->getInputValue($inputName . '_' . $multipleIndex, $params);
                            if (!is_null($value)) {
                                $valuesArray[] = $value;
                            }
                            $multipleIndex++;
                        } else {
                            break;
                        }
                    }

                    if (!count($valuesArray)) {
                        if (!is_null($params['default_value'])) {
                            $valuesArray[] = $params['default_value'];
                        } elseif ((int) $params['required']) {
                            $this->addError('Information obligatoire non renseignée : "' . $params['label'] . '"');
                        }
                    }

                    if (!empty($valuesArray)) {
                        $datas[$field_name] = $valuesArray;
                    }
                } else {
                    $value = $this->getInputValue($inputName, $params);
                    if (!is_null($value)) {
                        $datas[$field_name] = $value;
                    }
                }
            }
        }

        return $datas;
    }

    public function getInputValue($input_name, $field_params)
    {
        $value = null;
        if (isset($_POST[$input_name]) && $_POST[$input_name] !== '') {
            $value = $this->checkInputData($field_params, $_POST[$input_name]);
        } elseif (!is_null($field_params['default_value'])) {
            $value = $field_params['default_value'];
        } elseif ((int) $field_params['required']) {
            $this->addError('Information obligatoire non renseignée : "' . $field_params['label'] . '"');
        }

        return $value;
    }

    public function processRequestForm()
    {
        $this->requestOk = false;

        if (count($this->errors)) {
            return null;
        }

        $fields = $this->config->getCompiledParams('fields');
        $requestDatas = $this->processRequestData($fields);

        if (!count($this->errors)) {
            $this->requestOk = true;
        }

        return $requestDatas;
    }

    public function isLastRequestOk()
    {
        return $this->requestOk;
    }
}
