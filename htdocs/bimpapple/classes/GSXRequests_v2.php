<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';
require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/XMLDoc.class.php';

class GSX_Request_v2
{

    public static $data_definition_attributes = array(
        'type', 'max', 'values', 'regex', 'itemLabel', 'format', 'data_type', 'edit'
    );
    protected $gsx = null;
    public $errors = array();
    public $lastError = null;
    protected $defsDoc = null;
    protected $datas = array(
        'request'  => null,
        'response' => null,
    );
    public $requestName = '';
    public $request = '';
    public $requestLabel = '';
    protected $requestOk = false;
    public $serial = '';
    public $id_sav = 0;
    public $id_repair = 0;

    public function __construct($gsx, $requestName)
    {
        $this->gsx = $gsx;
        $this->requestName = $requestName;

        // Chargement des définitions:
        $fileName = DOL_DOCUMENT_ROOT . '/bimpapple/views/xml/datas_definitions_v2.xml';
        if (!file_exists($fileName)) {
            $this->addError('Erreur: le fichier "' . $fileName . '" n\'existe pas.');
            return;
        } else {
            $this->defsDoc = new DOMDocument();
            if (!$this->defsDoc->load($fileName)) {
                $this->addError('Echec du chargement du fichier xml "' . $fileName . '"');
                unset($this->defsDoc);
                $this->defsDoc = null;
            }
        }

        // Chargement des données pour la requête demandée:
        $fileName = DOL_DOCUMENT_ROOT . '/bimpapple/views/xml/requests_definitions_v2.xml';
        if (!file_exists($fileName)) {
            $this->addError('Erreur: le fichier "' . $fileName . '" n\'existe pas.');
            return;
        }

        ini_set('display_errors', 1);
        error_reporting(E_All);

        $doc = new DOMDocument();
        if (!$doc->load($fileName)) {
            $this->addError('Echec du chargement du fichier xml "' . $fileName . '"');
            unset($doc);
            return;
        }

        $requestNode = XMLDoc::findElementsList($doc, 'request', array('name' => 'name', 'value' => $requestName));

        if (!count($requestNode)) {
            $this->addError('Erreur: données absentes pour la requête "' . $requestName . '"');
            unset($doc);
            return;
        }

        $requestNode = $requestNode[0];

        $labelNode = XMLDoc::findChildElements($requestNode, 'label', null, null, 1);
        if (count($labelNode)) {
            $this->requestLabel = XMLDoc::getElementInnerText($labelNode[0]);
        } else {
            $this->addError('Erreur de syntaxe XML: attribut "label" absent pour la requête "' . $requestName . '"');
        }

        $dataNodes = null;

        $datasNode = XMLDoc::findChildElements($requestNode, 'datas', null, null, 1);
        if (count($datasNode) == 1) {
            $dataNodes = XMLDoc::findChildElements($datasNode[0], 'data', null, null, 1);
        }

        if (!isset($dataNodes) || !count($dataNodes)) {
            $this->addError('Aucune donnée trouvée pour la requête "' . $this->requestLabel . '".');
            unset($doc);
            return;
        }

        $this->datas['request'] = $dataNodes;
    }

    public function addError($msg)
    {
        $this->lastError = $msg;
        $this->errors[] = $this->lastError;
    }

    protected function getDataDefinitionsArray($name)
    {
        if (isset($this->defsDoc)) {
            $defsNode = XMLDoc::findElementsList($this->defsDoc, 'def', array('name' => 'name', 'value' => $name));
            if (count($defsNode) == 1) {
                $defsNode = $defsNode[0];
                $defs = array();

                foreach (self::$data_definition_attributes as $attr_name) {
                    $data = $defsNode->getAttribute($attr_name);
                    if (isset($data) && ($data !== '')) {
                        $defs[$attr_name] = $data;
                    }
                }

                $nodes = XMLDoc::findChildElements($defsNode, 'label', null, null, 1);
                if (count($nodes) == 1) {
                    $defs['label'] = XMLDoc::getElementInnerText($nodes[0]);
                }

                $nodes = XMLDoc::findChildElements($defsNode, 'infos', null, null, 1);
                if (count($nodes) == 1) {
                    $defs['infos'] = XMLDoc::getElementInnerText($nodes[0]);
                }

                $data = $defsNode->getAttribute('values');
                if (isset($data) && $data !== '') {
                    if (property_exists($this->gsx, $data)) {
                        $defs['values'] = GSX_Const::${$data};
                    } else {
                        $method = 'get' . ucfirst($data) . 'Array';
                        if (method_exists($this->gsx, $method)) {
                            $defs['values'] = $this->gsx->{$method}();
                        }
                    }
                }

                if (!isset($defs['values'])) {
                    $nodes = XMLDoc::findChildElements($defsNode, 'values', null, null, 1);
                    if (count($nodes) == 1) {
                        $defs['values'] = array();
                        $nodes = XMLDoc::filterElement($nodes[0], 'val', null);
                        foreach ($nodes as $node) {
                            $val = $node->getAttribute('val');
                            if ($val !== '') {
                                $defs['values'][$val] = XMLDoc::getElementInnerText($node);
                            }
                        }
                    }
                }
                return $defs;
            }
        }
        return null;
    }

    protected function getDataInput($dataNode, $serial, $values = null, $index = null, $prefixe = '')
    {
        if ($dataNode->hasAttribute('hidden')) {
            if ((int) $dataNode->getAttribute('hidden')) {
                return '';
            }
        }

        $dataName = $dataNode->getAttribute('name');

        if ($dataNode->hasAttribute('multiple'))
            $multiple = $dataNode->getAttribute('multiple');
        else
            $multiple = false;

        if (!$dataName) {
            return BimpRender::renderAlerts('Erreur de syntaxe dans le fichier xml : 1 attribut "name" non-défini');
        }

        $inputName = ($prefixe ? $prefixe . '_' : '') . $dataName . (isset($index) ? '_' . $index : '');
        $valuesName = $dataName;
        
        if (isset($values[$valuesName]) && $values[$valuesName] === 'hidden') {
            return '';
        }
        
        $defs = $this->getDataDefinitionsArray($dataName);

        if (!isset($defs)) {
            return BimpRender::renderAlerts('Aucune définitions pour la donnée "' . $dataName . '"', 'warning');
        }

        $html = '';

        $required = $dataNode->getAttribute('required');
        if ($required === '1') {
            $required = true;
        } else {
            $required = false;
        }

        $default = null;
        if ($dataNode->hasAttribute('default')) {
            $default = $dataNode->getAttribute('default');
        }

        if ($defs['type'] === 'custom_full_content') {
            if (isset($values[$valuesName]['content'])) {
                return $values[$valuesName]['content'];
            }
            return '';
        }

        $label = '';
        $nodes = XMLDoc::findChildElements($dataNode, 'label', null, null, 1);
        if (count($nodes) == 1) {
            $label = XMLDoc::getElementInnerText($nodes[0]);
        } else {
            $label = (isset($defs['label']) ? $defs['label'] : $dataName);
        }

        $display_if = null;
        $nodes = XMLDoc::findChildElements($dataNode, 'display_if', null, null, 1);
        if (isset($nodes[0])) {
            $display_if = array(
                'field_name' => $nodes[0]->getAttribute('input_name')
            );
            if ($nodes[0]->hasAttribute('show_values')) {
                $display_if['show_values'] = $nodes[0]->getAttribute('show_values');
            }
            if ($nodes[0]->hasAttribute('hide_values')) {
                $display_if['hide_values'] = $nodes[0]->getAttribute('hide_values');
            }
        }

        if ($defs['type'] == 'datasGroup') {
            $params = array(
                'required' => $required
            );
            if (!is_null($display_if)) {
                $params['display_if'] = $display_if;
            }

            $subDatasNode = XMLDoc::findChildElements($dataNode, 'datas', null, null, 1);
            if (count($subDatasNode) == 1) {
                $dataNodes = XMLDoc::findChildElements($subDatasNode[0], 'data', null, null, 1);

                if ($multiple) {
                    $items_contents = array();
                    $node = XMLDoc::findChildElements($dataNode, 'itemLabel', null, null, 1);
                    if (count($node) == 1) {
                        $params['item_label'] = XMLDoc::getElementInnerText($node[0]);
                    } elseif (isset($defs['itemLabel'])) {
                        $params['item_label'] = $defs['itemLabel'];
                    }
                    if ($dataNode->hasAttribute('maxitems')) {
                        $params['max_items'] = (int) $dataNode->getAttribute('maxitems');
                    }

                    // Template: 
                    $items_contents['tpl'] = '';
                    foreach ($dataNodes as $node) {
                        $items_contents['tpl'] .= $this->getDataInput($node, $serial, null, 'idx', $inputName);
                    }

                    // Liste des items: 
                    if ($required && (!isset($values[$valuesName]) || empty($values[$valuesName]))) {
                        $values[$valuesName] = array(
                            0 => array()
                        );
                    }

                    $i = 0;

                    foreach ($values[$valuesName] as $subValues) {
                        $i++;
                        $items_contents[$i] = '';
                        foreach ($dataNodes as $node) {
                            $items_contents[$i] .= $this->getDataInput($node, $serial, $subValues, $i, $inputName);
                        }
                    }
                    $html .= BimpRender::renderFormGroupMultiple($items_contents, $inputName, $label, $params);
                } else {
                    $inputsContent = '';
                    foreach ($dataNodes as $node) {
                        $inputsContent .= $this->getDataInput($node, $serial, $values[$valuesName], null, $inputName);
                    }
                    $html .= BimpRender::renderFormInputsGroup($label, $inputName, $inputsContent, $params);
                }
            } else {
                $html .= '<p class="alert alert-warning">Aucunes définitions pour ces données</p>';
            }
        } else {
            $html .= '<div class="row formRow';
            if (!is_null($display_if)) {
                $html .= ' display_if';
            }
            $html .= '"';
            if (!is_null($display_if)) {
                $html .= BC_Field::renderDisplayifDataStatic($display_if);
            }
            $html .= '>';
            $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">';
            $html .= $label;
            if ($required) {
                $html .= '<sup>*</sup>';
            }
            $html .= '</div>';

            $inputValue = (isset($values[$valuesName]) ? $values[$valuesName] : $default);

            $html .= '<div class="formRowInput col-xs-12 col-sm-6 col-md-9">';
            if ($multiple) {
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

            if ($defs['type'] === 'select') {
                $options = array();
                if ($dataNode->hasAttribute('values')) {
                    $arrayName = $dataNode->getAttribute('values');
                    if ($arrayName) {
                        if (property_exists('GSX_Const', $arrayName)) {
                            $options = GSX_Const::${$arrayName};
                        } else {
                            $method = 'get' . ucfirst($arrayName) . 'Array';
                            if (method_exists($this->gsx, $method)) {
                                $options = $this->gsx->{$method}();
                            }
                        }
                    }
                } else {
                    $nodes = XMLDoc::findChildElements($dataNode, 'values', null, null, 1);
                    if (count($nodes) == 1) {
                        $nodes = XMLDoc::filterElement($nodes[0], 'val', null);
                        foreach ($nodes as $node) {
                            $val = $node->getAttribute('val');
                            $options[$val] = XMLDoc::getElementInnerText($node);
                        }
                    }
                }

                if (empty($options) && isset($defs['values'])) {
                    $options = $defs['values'];
                }
            }

            while (true) {
                $html .= '<div class="inputContainer">';
                if (isset($defs['edit']) && !(int) $defs['edit']) {
                    $html .= '<input type="hidden" name="' . $inputName . '" value="' . $inputValue . '"/>';
                    switch ($defs['type']) {
                        case 'text':
                        case 'textarea':
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
                            if (isset($options[$inputValue])) {
                                $html .= $options[$inputValue];
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
                    switch ($defs['type']) {
                        case 'text':
                        case 'number':
                        case 'tel':
                        case 'email':
                            $html .= BimpInput::renderInput('text', $inputName, $inputValue, array());
                            if (isset($defs['max'])) {
                                $html .= '<span class="inputHelp" style="font-size: 11px;">';
                                $html .= $defs['max'] . ' car. max.';
                                $html .= '</span>';
                            }
                            break;

                        case 'int':
                            $html .= BimpInput::renderInput('text', $inputName, $inputValue, array(
                                        'data' => array(
                                            'data_type' => 'number',
                                            'decimals'  => 0
                                        )
                            ));
                            break;

                        case 'textarea':
                            $html .= BimpInput::renderInput('textarea', $inputName, $inputValue, array(
                                        'rows'        => '3',
                                        'auto_expand' => true,
                                        'maxlength'   => isset($defs['max']) ? $defs['max'] : ''
                            ));
                            break;

                        case 'select':
                            $html .= BimpInput::renderInput('select', $inputName, $inputValue, array(
                                        'options' => $options
                            ));
                            break;

                        case 'YesNo':
                            if (is_null($inputValue)) {
                                $inputValue = 1;
                            }
                            if ($inputValue === 'Y') {
                                $inputValue = 1;
                            } else {
                                $inputValue = 0;
                            }

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
                            $html .= '<p class="alert alert-warning">Type inéxistant pour la donnée "' . $dataName . '"</p>';
                            break;
                    }
                    if (isset($defs['infos']) && $defs['infos']) {
                        $html .= '<p class="inputHelp">' . $defs['infos'] . '</p>';
                    }
                }
                $html .= '</div>';

                if (!$multiple) {
                    break;
                } else {
                    if ($multipleIndex == 0) {
                        $html .= '</div>';
                        $html .= '<div class="inputsList">';
                    }
                    $inputName = $dataName . (isset($index) ? '_' . $index : '');
                    if (!isset($valuesArray[$multipleIndex])) {
                        $html .= '</div>';
                        $html .= '<input type="hidden" id="' . $inputName . '_nextIdx" name="' . $inputName . '_nextIdx" value="' . $multipleIndex . '"/>';
                    }
                    $values[$valuesName] = $valuesArray[$multipleIndex];
                    $multipleIndex++;
                    $inputName .= '_' . $multipleIndex;
                }
            }
            if ($inputName === "trackingNumber") {//Ajout lien création UPS
                $html .= '<p><a class="btn btn-default" target="_blank" href="https://row.ups.com/Default.aspx?Company=AppleDist&LoginId=aduser&Password=aduser">Création retour UPS</a></p>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        return $html;
    }

    public function generateRequestFormHtml($values, $serial, $id_sav = null, $id_repair = null)
    {
        if (count($this->errors)) {
            return BimpRender::renderAlerts(BimpTools::getMsgFromArray($this->errors, 'Impossible d\'afficher le formulaire pour cette requête'));
        }

        if (!isset($this->defsDoc)) {
            return BimpRender::renderAlerts('Erreur: les définitions  concernant les données à traiter n\'ont pas été chargée correctement');
        }

        $this->serial = $serial;

        $html .= '<div class="container-fluid">';
        $html .= '<form id="repairForm_' . $this->requestName . '" class="request_form" enctype="multipart/form-data">';

        $html .= '<input name="gsx_requestForm" type="hidden" value="1"/>';
        $html .= '<input name="gsx_requestName" type="hidden" value="' . $this->requestName . '"/>';
        $html .= '<input name="gsx_serial" type="hidden" value="' . $serial . '"/>';

        if (!is_null($id_repair)) {
            $this->id_repair = $id_repair;
            $html .= '<input name="gsx_id_repair" type="hidden" value="' . $id_repair . '"/>';
        }

        if (!is_null($id_sav)) {
            $this->id_sav = $id_sav;
            $html .= '<input name="gsx_id_sav" type="hidden" value="' . $id_sav . '"/>';
        }

        foreach ($this->datas['request'] as $dataNode) {
            $html .= $this->getDataInput($dataNode, $serial, $values);
        }

        $html .= '<div class="formSus" id="formSus_' . $this->requestName . '"></div>';

        $html .= '<div class="ajaxResultContainer" style="display: none"></div>';
        $html .= '</form>';
        $html .= '</div>';
        return $html;
    }

    public static function getRequestsByType($type)
    {
        $results = array();
        $xmlDoc = new DOMDocument();
        if ($xmlDoc->load(DOL_DOCUMENT_ROOT . '/bimpapple/views/xml/requests_definitions.xml')) {
            $requestsNodes = XMLDoc::findElementsList($xmlDoc, 'request', array('name' => 'type', 'value' => $type));
            foreach ($requestsNodes as $node) {
                $labelNode = XMLDoc::findChildElements($node, 'label', null, null, 1);
                if (count($labelNode) == 1) {
                    if ($node->hasAttribute('name'))
                        $results[$node->getAttribute('name')] = XMLDoc::getElementInnerText($labelNode[0]);
                }
            }
        }
        return $results;
    }

    public function checkInputData($defs, $value)
    {
        switch ($defs['type']) {
            case 'YesNo':
                if ((int) $value) {
                    $value = 'Y';
                } else {
                    $value = 'N';
                }
                break;

            case 'bool':
                $value = (bool) $value;
                if (!$value) {
                    $value = null;
                }
                break;

            case 'int':
                $value = (int) $value;
                if (isset($defs['max'])) {
                    if ($value > (int) $defs['max']) {
                        $this->addError('"' . $defs['label'] . '": la valeur maximale autorisée (' . $defs['max'] . ') est dépassée');
                    }
                }
                break;

            case 'select':
                if (isset($defs['data_type'])) {
                    switch ($defs['data_type']) {
                        case 'int':
                            $value = (int) $value;
                            break;
                    }
                }
                break;

            case 'text':
                if (isset($defs['regex']) && (string) $defs['regex']) {
                    if (!preg_match('/' . $defs['regex'] . '/', $value)) {
                        $msg = '"' . $defs['label'] . '": format invalide';
                        if (isset($defs['format'])) {
                            $msg .= ' (attendu: ' . $defs['format'] . ')';
                        }
                        $this->addError($msg);
                    }
                }
                if (isset($defs['max'])) {
                    if (strlen($value) > (int) $defs['max']) {
                        $this->addError('"' . $defs['label'] . '": le nombre de caractères autorisés (' . $defs['max'] . ') est dépassé');
                    }
                }
                if ($value === '') {
                    $value = null;
                }
                break;

            case 'datetime':
                if (preg_match('/^(\d{4}\-\d{2}\-\d{2}) (\d{2}:\d{2}:\d{2})$/', $value, $matches)) {
                    $value = $matches[1] . 'T' . $matches[2] . 'Z';
                }
                break;
        }
        return $value;
    }

    public function processRequestDatas(array $datasNodes, $dataIndex = null, $dataPrefix = '')
    {
        $datas = array();
        foreach ($datasNodes as $dataNode) {
            // Check du display_if: 
            $nodes = XMLDoc::findChildElements($dataNode, 'display_if', null, null, 1);
            if (isset($nodes[0])) {
                $input_name = (string) $nodes[0]->getAttribute('input_name');
                if ($input_name) {
                    $inputValue = BimpTools::getValue($input_name, '');
                    if ($nodes[0]->hasAttribute('show_values')) {
                        $show_values = explode(',', $nodes[0]->getAttribute('show_values'));
                        if (!in_array($inputValue, $show_values)) {
                            continue;
                        }
                    }
                    if ($nodes[0]->hasAttribute('hide_values')) {
                        $hide_values = explode(',', $nodes[0]->getAttribute('hide_values'));
                        if (in_array($inputValue, $hide_values)) {
                            continue;
                        }
                    }
                }
            }


            if ($dataNode->hasAttribute('donotprocess')) {
                continue;
            }

//            if ($dataNode->hasAttribute('hidden')) {
//                if ((int) $dataNode->getAttribute('hidden')) {
//                    continue;
//                }
//            }

            if (!$dataNode->hasAttribute('name')) {
                $this->addError('Erreur de syntaxe XML: 1 attribut "nom" non-spécifié');
                continue;
            }

            $dataName = $dataNode->getAttribute('name');
            if (!$dataName) {
                $this->addError('Erreur de syntaxe XML: 1 attribut "nom" non-spécifié');
                continue;
            }

            $defs = $this->getDataDefinitionsArray($dataName);
            if (!isset($defs)) {
                $this->addError('Definitions absentes pour la donnée "' . $dataName . '"');
                continue;
            }

            $required = $dataNode->getAttribute('required');
            if ($required === '1') {
                $required = true;
            } else {
                $required = false;
            }

            if ($dataNode->hasAttribute('multiple')) {
                $multiple = (int) $dataNode->getAttribute('multiple');
            } else {
                $multiple = false;
            }

            $inputName = ($dataPrefix ? $dataPrefix . '_' : '') . $dataName;
            if (!is_null($dataIndex)) {
                $inputName .= '_' . $dataIndex;
            }

            if ($defs['type'] === 'datasGroup') {
                $subDatasNode = XMLDoc::findChildElements($dataNode, 'datas', null, null, 1);
                if (count($subDatasNode) == 1) {
                    $subDatasNodes = XMLDoc::findChildElements($subDatasNode[0], 'data', null, null, 1);
                    if ($multiple) {
                        $nextIdx = (int) BimpTools::getValue($inputName . '_nextIdx', 0);
                        if ($nextIdx) {
                            $array = array();
                            for ($i = 1; $i < $nextIdx; $i++) {
                                $result = $this->processRequestDatas($subDatasNodes, $i, $inputName);
                                if (!empty($result)) {
                                    $array[] = $result;
                                }
                            }
                            if (!empty($array)) {
                                $datas[$dataName] = $array;
                            }
                        }
                    } else {
                        $result = $this->processRequestDatas($subDatasNodes, null, $inputName);
                        if (!empty($result)) {
                            $datas[$dataName] = $result;
                        }
                    }
                } else {
                    $this->addError('Erreur de syntax XML dans le fichier "requestes_definitions.xml": liste des données absentes pour le groupe "' . $dataName . '"');
                }
            } else {
                if ($multiple) {
                    $multipleIndex = 1;
                    $valuesArray = array();
                    while (true) {
                        if (isset($_POST[$inputName . '_' . $multipleIndex])) {
                            $value = $this->checkInputData($defs, $_POST[$inputName . '_' . $multipleIndex]);
                            if (!is_null($value)) {
                                $valuesArray[] = $value;
                            }
                            $multipleIndex++;
                        } else {
                            break;
                        }
                    }
                    if (!count($valuesArray)) {
                        $default = $this->checkInputData($defs, $dataNode->getAttribute('default'));
                        if (!isset($default) && ($default !== '')) {
                            $valuesArray[] = $default;
                        } elseif ($required) {
                            $this->addError('Information obligatoire non renseignée : "' . $defs['label'] . '"');
                        }
                    }

                    if (!empty($valuesArray)) {
                        $datas[$dataName] = $valuesArray;
                    }
                } elseif (isset($_POST[$inputName]) && ($_POST[$inputName] !== '')) {
                    $value = $this->checkInputData($defs, $_POST[$inputName]);
                    if (!is_null($value)) {
                        $datas[$dataName] = $value;
                    }
                } else {
                    $default = $this->checkInputData($defs, $dataNode->getAttribute('default'));
                    if (isset($default) && ($default !== '')) {
                        $datas[$dataName] = $default;
                    } elseif ($required) {
                        $this->addError('Information obligatoire non renseignée : "' . $defs['label'] . '"');
                    }
                }
            }
        }

        return $datas;
    }

    public function processRequestForm()
    {
        $this->requestOk = false;

        if (count($this->errors)) {
            return null;
        }

        if (!isset($this->defsDoc)) {
            $this->errors[] = 'Les définitions concernant les données à traiter n\'ont pas été chargées correctement';
            return null;
        }

        $requestDatas = $this->processRequestDatas($this->datas['request']);

//        if (count($this->errors)) {
//            return null;
//        }

        $this->requestOk = true;
        return $requestDatas;
    }

    public function isLastRequestOk()
    {
        return $this->requestOk;
    }
}

?>
