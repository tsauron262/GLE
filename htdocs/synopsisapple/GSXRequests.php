<?php

require_once DOL_DOCUMENT_ROOT . '/synopsisapple/GSX.class.php';
require_once DOL_DOCUMENT_ROOT . '/synopsisapple/XMLDoc.class.php';

class GSX_Request {

    protected $gsx = null;
    protected $errors = array();
    protected $lastError = null;
    protected $defsDoc = null;
    protected $datas = array(
        'request' => null,
        'response' => null,
    );
    public $requestName = '';
    public $request = '';
    public $requestLabel = '';
    public $wrapper = '';
    protected $requestOk = false;
    protected $comptiaCodes = null;

    public function __construct($gsx, $requestName) {
        $this->gsx = $gsx;
        $this->requestName = $requestName;

        // Chargement des définitions:
        $fileName = dirname(__FILE__) . '/datas_definitions.xml';
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
        $fileName = dirname(__FILE__) . '/requests_definitions.xml';
        if (!file_exists($fileName)) {
            $this->addError('Erreur: le fichier "' . $fileName . '" n\'existe pas.');
            return;
        }

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
            if ($requestNode->hasAttribute('request')) {
                $this->request = $requestNode->getAttribute('request');
            } else {
                $this->addError('Erreur de syntaxe XML: attribut "request" absent pour la requête "' . $this->requestLabel . '"');
            }
            if ($requestNode->hasAttribute('wrapper')) {
                $this->wrapper = $requestNode->getAttribute('wrapper');
            } else {
                $this->addError('Erreur de syntaxe XML: attribut "wrapper" absent pour la requête "' . $this->requestLabel . '"');
            }
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

    public function addError($msg) {
        $this->lastError = $msg;
        $this->errors[] = $this->lastError;
    }

    protected function getDataDefinitionsArray($name) {
        if (isset($this->defsDoc)) {
            $defsNode = XMLDoc::findElementsList($this->defsDoc, 'def', array('name' => 'name', 'value' => $name));
            if (count($defsNode) == 1) {
                $defsNode = $defsNode[0];
                $defs = array();
                $data = $defsNode->getAttribute('type');
                if (isset($data) && ($data !== ''))
                    $defs['type'] = $data;

                $data = $defsNode->getAttribute('max');
                if (isset($data) && ($data !== ''))
                    $defs['max'] = $data;

                $data = $defsNode->getAttribute('jsCheck');
                if (isset($data) && ($data !== ''))
                    $defs['jsCheck'] = $data;

                $data = $defsNode->getAttribute('phpCheck');
                if (isset($data) && ($data !== ''))
                    $defs['phpCheck'] = $data;

                $data = $defsNode->getAttribute('hidden');
                if (isset($data) && ($data !== ''))
                    $defs['hidden'] = $data;

                $nodes = XMLDoc::findChildElements($defsNode, 'label', null, null, 1);
                if (count($nodes) == 1)
                    $defs['label'] = XMLDoc::getElementInnerText($nodes[0]);

                $nodes = XMLDoc::findChildElements($defsNode, 'infos', null, null, 1);
                if (count($nodes) == 1)
                    $defs['infos'] = XMLDoc::getElementInnerText($nodes[0]);

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
                return $defs;
            }
        }
        return null;
    }

    protected function getDataInput($dataNode, $serial, $values = null, $index = null) {
        $name = $dataNode->getAttribute('name');

        if ($dataNode->hasAttribute('multiple'))
            $multiple = $dataNode->getAttribute('multiple');
        else
            $multiple = false;

        if (!$name) {
            return '<p class="error">Erreur de syntaxe dans le fichier xml : 1 attribut "name" non-défini.</p>';
        }

        $inputName = $name . (isset($index) ? '_' . $index : '');
        $valuesName = $name;
        $defs = $this->getDataDefinitionsArray($name);

        if (!isset($defs)) {
            return '<p class="alert">Aucune définitions pour la donnée "' . $name . '"</p>';
        }

        if (isset($defs['hidden']) && ($defs['hidden'] == '1'))
            return '';

        $html = '';

        $required = $dataNode->getAttribute('required');
        if ($required === '1')
            $required = true;
        else
            $required = false;
        $default = null;
        if ($dataNode->hasAttribute('default'))
            $default = $dataNode->getAttribute('default');

        if ($defs['type'] == 'datasGroup') {
            $html .= '<fieldset id="' . $inputName . '">';
            if (isset($defs['label'])) {
                $html .= '<legend>' . $defs['label'];
                if (isset($defs['infos'])) {
                    $html .= '<span class="displayInfos" onmouseover="displayLabelInfos($(this))" onmouseout="hideLabelInfos($(this))">';
                    $html .= '<div class="labelInfos">' . $defs['infos'] . '</div></span>';
                }
                $html .= '</legend>' . "\n";
            }
            $subDatasNode = XMLDoc::findChildElements($dataNode, 'datas', null, null, 1);
            if (count($subDatasNode) == 1) {
                $dataNodes = XMLDoc::findChildElements($subDatasNode[0], 'data', null, null, 1);
                foreach ($dataNodes as $node) {
                    $html .= $this->getDataInput($node, $serial, isset($values[$valuesName]) ? $values[$valuesName] : null, $index);
                }
            } else {
                $html .= '<p class="alert">Aucunes définitions pour ces données</p>' . "\n";
            }
            $html .= '</fieldset>' . "\n";
        } else {
            $html .= '<div class="dataBlock">' . "\n";
            if (isset($defs['label'])) {
                $html .= '<label class="dataTitle" for="' . $inputName . '">' . $defs['label'];
                $html .= ($required ? '<sup><span class="required"></span></sup>' : '');
                if (isset($defs['infos'])) {
                    $html .= '<span class="displayInfos" onmouseover="displayLabelInfos($(this))" onmouseout="hideLabelInfos($(this))">';
                    $html .= '<div class="labelInfos">' . $defs['infos'] . '</div></span>';
                }
                $html .= '</label>';
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
                    $html .= '<span class="button blueHover duplicateInput" onclick="duplicateInput($(this), \'' . $inputName . '\')">Ajouter</span>' . "\n";
                    $html .= '<div class="dataInputTemplate">' . "\n";
                }
            }
            while (true) {
                switch ($defs['type']) {
                    case 'text':
                    case 'number':
                    case 'tel':
                    case 'email':
                    case 'date':
                    case 'time':
                        $html .= '<br/>' . "\n";
                        $html .= '<input type="' . $defs['type'] . '" ';
                    case 'textarea':
                        if ($defs['type'] == 'textarea') {
                            if (isset($defs['max']))
                                $html .= '<span style="font-size: 11px; color: #783131">(max ' . $defs['max'] . ' caractères)</span>';
                            $html .= '<br/>' . "\n";
                            $html .= '<textarea cols="80" rows="10" ';
                        }
                        $html .= 'id="' . $inputName . '" name="' . $inputName . '"' . ($required ? ' required' : '');

                        if ($defs['type'] != 'textarea') {
                            if (isset($values[$valuesName]))
                                $html .= ' value="' . $values[$valuesName] . '"';
                            else if (isset($default))
                                $html .= ' value="' . $default . '"';
                        }

                        $html .= isset($defs['max']) ? ' maxlength="' . $defs['max'] . '"' : '';
                        $html .= isset($defs['jsCheck']) ? ' onchange="checkInput($(this), \'' . $defs['jsCheck'] . '\')"' : '';

                        if ($defs['type'] == 'textarea') {
                            $html .= '>';
                            if (isset($values[$valuesName]))
                                $html .= $values[$valuesName];
                            else if (isset($default))
                                $html .= $default;
                            $html .= '</textarea>' . "\n";
                        }
                        else
                            $html .= '/>' . "\n";
                        break;

                    case 'select':
                        if (isset($defs['values'])) {
                            $html .= '<select name="' . $inputName . '" id="' . $inputName . '"' . ($required ? ' required' : '' ) . '>';
//                        $html .= '<option value="">&nbsp;&nbsp;---&nbsp;&nbsp;</option>';
                            foreach ($defs['values'] as $v => $txt) {
                                $html .= '<option value="' . $v . '"';
                                if (isset($values[$valuesName])) {
                                    if ($values[$valuesName] == $v)
                                        $html.= ' selected';
                                } else if (isset($default)) {
                                    if ($default == $v)
                                        $html .= ' selected';
                                }
                                $html .= '>' . $txt . '</option>' . "\n";
                            }
                            $html .= '</select>';
                            break;
                        }
                        $html .= '<p class="alert">Aucune valeur défini pour la liste de choix "' . $name . '"</p>' . "\n";
                        break;

                    case 'YesNo':
                        $defVal = 'Y';
                        if (isset($values[$valuesName]))
                            $defVal = $values[$valuesName];
                        else if (isset($default))
                            $defVal = $default;

                        $html .= '<input type="radio" id="' . $inputName . '_yes" name="' . $inputName . '" value="Y" ' . (($defVal == 'Y') ? 'checked' : '' ) . '/>' . "\n";
                        $html .= '<label for="' . $inputName . '_yes">Oui</label>' . "\n";
                        $html .= '<input type="radio" id="' . $inputName . '_no" name="' . $inputName . '" value="N" ' . (($defVal == 'N') ? 'checked' : '' ) . '/>' . "\n";
                        $html .= '<label for="' . $inputName . '_no">Non</label>' . "\n";
                        break;

                    case 'fileSelect':
                        $html .= '<input type="file" id="' . $inputName . '" name="' . $inputName . '"/>';
                        break;

                    case 'partsList':
                        $subDatasNode = XMLDoc::findChildElements($dataNode, 'datas', null, null, 1);
                        if (count($subDatasNode) == 1) {
                            $partsDataNodes = XMLDoc::findChildElements($subDatasNode[0], 'data', null, null, 1);
                            if (count($partsDataNodes)) {
                                $orderLines = null;
                                if (isset($values)) {
                                    if (isset($values[$valuesName])) {
                                        if (is_array($values[$valuesName])) {
                                            $lines = $values[$valuesName];
                                            $orderLines = array();
                                            foreach ($lines as $line) {
                                                if (isset($line['partNumber']))
                                                    $orderLines[$line['partNumber']] = $line;
                                                else
                                                    $orderLines[] = $line;
                                            }
                                        }
                                    }
                                }
                                if (!isset($orderLines)) {
                                    global $db;
                                    $partsCart = new partsCart($db, $serial);
                                    if ($partsCart->loadCart()) {
                                        $orderLines = array();
                                        foreach ($partsCart->partsCart as $part) {
                                            $orderLines[$part['partNumber']] = array(
                                                'partNumber' => $part['partNumber'],
                                                'comptiaCode' => $part['comptiaCode'],
                                                'comptiaModifier' => $part['comptiaModifier']
                                            );
                                        }
                                    }
                                }

                                $html .= '<span class="button importParts blueHover"';
                                $html .= 'onclick="GSX.importPartsFromCartToRepair(\'' . $this->requestName . '\')">';
                                $html .= 'Importer la liste des composants depuis le panier</span><br/>' . "\n";
                                $html .= '<div class="partsImportResults"></div>' . "\n";
                                $html .= '<div class="repairsPartsInputsTemplate">' . "\n";
                                foreach ($partsDataNodes as $partDataNode) {
                                    $html .= $this->getDataInput($partDataNode, $serial);
                                }
                                $html .= '</div>';
                                $html .= '<div class="repairPartsContainer"';
                                $partCount = 0;
                                if (isset($orderLines) && is_array($orderLines)) {
                                    $html .= ' style="display: block;">' . "\n";
                                    $partsList = null;
                                    if (isset($this->gsx)) {
                                        $partsList = $this->gsx->getPartsListArray(true);
                                        if (!isset($this->comptiaCodes))
                                            $this->comptiaCodes = $this->gsx->getCompTIACodesArray();
                                        if (isset($partsList) && count($partsList)) {
                                            foreach ($orderLines as $partNumber => $orderLine) {
                                                if (isset($partsList[$partNumber])) {
                                                    if (isset($partsList[$partNumber]['partDescription']))
                                                        $orderLines[$partNumber]['partDescription'] = $partsList[$partNumber]['partDescription'];
                                                    if (isset($partsList[$partNumber]['componentCode']))
                                                        $orderLines[$partNumber]['componentCode'] = $partsList[$partNumber]['componentCode'];
                                                }
                                            }
                                        }
                                    }

                                    $i = 1;
                                    foreach ($orderLines as $orderLine) {
                                        $partCount++;
                                        $html .= '<div class="partDatasBlock">';
                                        $html .= '<div class="partDatasBlockTitle closed" onclick="togglePartDatasBlockDisplay($(this))">';
                                        if (isset($orderLine['partDescription']))
                                            $html .= $orderLine['partDescription'];
                                        else
                                            $html .= 'Composant ' . $i;
                                        $html .= '</div>';
                                        $html .= '<div class="partDatasContent partDatasContent_' . $i . '">';
                                        foreach ($partsDataNodes as $partDataNode) {
                                            $html .= $this->getDataInput($partDataNode, $serial, $orderLine, $i);
                                        }
                                        $html .= '</div></div>';
                                        $i++;
                                    }
                                } else {
                                    $html .= '>';
                                }

                                $html .= '</div>' . "\n";
                                $html .= '<input type="hidden" id="partsCount" name="partsCount" value="' . $partCount . '"/>' . "\n";
                                break;
                            }
                        }
                        $html .= '<p class="alert">Aucune définition trouvée pour les données concernant les composants.</p>';
                        break;

                    case 'comptiaCode':
                        $html .= '<div class="comptiaCodeContainer">' . "\n";
                        if (($values['componentCode'] === ' ') || ($values['componentCode'] == '')) {
                            $html .= '<input type="hidden" id="' . $inputName . '" name="' . $inputName . '" value="000"/>' . "\n";
                            $html .= '<span>Non-applicable</span>';
                        } else if (isset($values['componentCode']) &&
                                isset($this->comptiaCodes) &&
                                isset($this->comptiaCodes['grps'][$values['componentCode']])) {
                            $html .= '<select id="' . $inputName . '" name="' . $inputName . '">' . "\n";
                            $html .= '<option value="0">Code compTIA</option>' . "\n";
                            foreach ($this->comptiaCodes['grps'][$values['componentCode']] as $code => $desc) {
                                $html .= '<option value="' . $code . '"';
                                if (isset($values[$valuesName])) {
                                    if ($values[$valuesName] == $code)
                                        $html.= ' selected';
                                }
                                $html .= '>' . $code . ' - ' . $desc . '</option>';
                            }
                            $html .= '</select>' . "\n";
                        } else if (isset($values[$valuesName])) {
                            $html .= '<input type="text" id="' . $inputName . '" name="' . $inputName . '" value="';
                            $html .= $values[$valuesName] . '"' . ($required ? ' required' : '') . '/>' . "\n";
                        }
                        $html .= '</div>';
                        break;

                    case 'comptiaModifier':
                        $html .= '<div class="comptiaModifierContainer">' . "\n";

                        if (isset($this->comptiaCodes['mods'])) {
                            $html .= '<select id="' . $inputName . '" name="' . $inputName . '">' . "\n";
                            $html .= '<option value="0">Modificateur</option>' . "\n";
                            foreach ($this->comptiaCodes['mods'] as $mod => $desc) {
                                $html .= '<option value="' . $mod . '"';
                                if (isset($values[$valuesName])) {
                                    if ($values[$valuesName] == $mod)
                                        $html.= ' selected';
                                }
                                $html .= '>' . $mod . ' - ' . $desc . '</option>';
                            }
                            $html .= '</select>' . "\n";
                        } else if (isset($values[$valuesName])) {
                            $html .= '<input type="text" id="' . $inputName . '" name="' . $inputName . '" value="';
                            $html .= $values[$valuesName] . '"' . ($required ? ' required' : '') . '/>' . "\n";
                        }
                        $html .= '</div>';
                        break;

                    default:
                        $html .= '<p class="alert">Type inéxistant pour la donnée "' . $name . '"</p>';
                        break;
                }
                if (!$multiple)
                    break;
                else {
                    if ($multipleIndex == 0) {
                        $html .= '</div>' . "\n";
                        $html .= '<div class="inputsList">' . "\n";
                    }
                    $inputName = $name . (isset($index) ? '_' . $index : '');
                    if (!isset($valuesArray[$multipleIndex])) {
                        $html .= '</div>' . "\n";
                        $html .= '<input type="hidden" id="' . $inputName . '_nextIdx" name="' . $inputName . '_nextIdx" value="' . $multipleIndex . '"/>' . "\n";
                        break;
                    }
                    $values[$valuesName] = $valuesArray[$multipleIndex];
                    $multipleIndex++;
                    $inputName .= '_' . $multipleIndex;
                }
            }
            if (isset($defs['jsCheck']))
                $html .= '<span class="dataCheck"></span>';
            $html .= '</div>';
        }
        return $html;
    }

    public function generateRequestFormHtml($values, $prodId, $serial) {
        if (count($this->errors)) {
            $html = '<p class="error">Impossible d\'afficher le formulaire pour cette requête.<br/><br/>';
            $html .= 'Erreurs:<br/><br/>';
            $i = 1;
            foreach ($this->errors as $error) {
                $html .= $i . '. ' . $error . '<br/>';
            }
            $html .= '</p>';
            return $html;
        }

        if (!isset($this->defsDoc)) {
            return '<p class="error">Erreur: les définitions  concernant les données à traiter n\'ont pas été chargée correctement.</p>';
        }

        $html .= '<form class="gsxRepairForm" id="repairForm_' . $this->requestName . '" method="POST"';
        $html .= ' action="' . DOL_URL_ROOT . '/synopsisapple/ajax/requestProcess.php?serial=' . $serial . '&action=sendGSXRequest&prodId=' . $prodId . '&request=' . $this->requestName . '"';
        $html .= ' enctype="multipart/form-data">' . "\n";

        $html .= '<div class="requestTitle">' . $this->requestLabel . '</div>' . "\n";
        $html .= '<p class="requiredInfos"><sup><span class="required"></span></sup>Champs requis</p>';
        $html .= '<div class="requestFormInputs">' . "\n";
        $html .= '<input type="hidden" id="requestName" name="requestName" value="' . $this->requestName . '"/>';
        if (isset($_REQUEST['chronoId']))
            $html .= '<input type="hidden" id="chronoId" name="chronoId" value="' . $_REQUEST['chronoId'] . '"/>';
        foreach ($this->datas['request'] as $dataNode) {
            $html .= $this->getDataInput($dataNode, $serial, $values);
        }
        $html .= '</div>' . "\n";
        $html .= '<div style="text-align: right; margin: 15px 30px"><span class="button submit greenHover" onclick="submitGsxRequestForm(' . $prodId . ', \'' . $this->requestName . '\')">Envoyer</span></div>';
        $html .= '</form>' . "\n";
        return $html;
    }

    public static function getRequestsByType($type) {
        $results = array();
        $xmlDoc = new DOMDocument();
        if ($xmlDoc->load(dirname(__FILE__) . '/requests_definitions.xml')) {
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

    public function checkInputData($defs, $value) {
        return $value;
    }

    public function processRequestDatas(array $datasNodes, $dataIndex = null) {
        $datas = array();
        foreach ($datasNodes as $dataNode) {
            if ($dataNode->hasAttribute('name')) {
                $dataName = $dataNode->getAttribute('name');
                if ($dataName !== '') {
                    $required = $dataNode->getAttribute('required');
                    if ($required == '1')
                        $required = true;
                    else
                        $required = false;

                    if ($dataNode->hasAttribute('multiple'))
                        $multiple = $dataNode->getAttribute('multiple');
                    else
                        $multiple = false;

                    $defs = $this->getDataDefinitionsArray($dataName);
                    if (isset($defs)) {
                        if ($defs['type'] == 'partsList') {
                            if (isset($_POST['partsCount'])) {
                                $partCount = (int) $_POST['partsCount'];
                                $datas[$dataName] = array();
                                $subDatasNode = XMLDoc::findChildElements($dataNode, 'datas', null, null, 1);
                                if (count($subDatasNode) == 1) {
                                    $subDatasNodes = XMLDoc::findChildElements($subDatasNode[0], 'data', null, null, 1);
                                    for ($index = 1; $index <= $partCount; $index++) {
                                        $results = $this->processRequestDatas($subDatasNodes, $index);
                                        if (count($results)) {
                                            $datas[$dataName][] = $results;
                                        }
                                    }
                                } else {
                                    $this->addError('Erreur de syntax XML dans le fichier "requestes_definitions.xml":
                                    liste des données absentes pour le groupe "' . $dataName . '"');
                                }
                            } else {
                                $this->addError('Erreur technique: nombre de composants absent.');
                            }
                        } else if ($defs['type'] == 'datasGroup') {
                            $subDatasNode = XMLDoc::findChildElements($dataNode, 'datas', null, null, 1);
                            if (count($subDatasNode) == 1) {
                                $subDatasNodes = XMLDoc::findChildElements($subDatasNode[0], 'data', null, null, 1);
                                $datas[$dataName] = $this->processRequestDatas($subDatasNodes);
                            } else {
                                $this->addError('Erreur de syntax XML dans le fichier "requestes_definitions.xml": liste des données absentes pour le groupe "' . $dataName . '"');
                            }
                        } else {
                            $inputName = $dataName;
                            if (isset($dataIndex))
                                $inputName .= '_' . $dataIndex;
                            if ($multiple) {
                                $multipleIndex = 1;
                                $valuesArray = array();
                                while (true) {
                                    if (isset($_POST[$inputName . '_' . $multipleIndex])) {
                                        $value = $this->checkInputData($defs, $_POST[$inputName . '_' . $multipleIndex]);
                                        $valuesArray[] = $value;
                                        $multipleIndex++;
                                    } else
                                        break;
                                }
                                if (!count($valuesArray)) {
                                    $default = $dataNode->getAttribute('default');
                                    if (isset($default) && ($default !== '')) {
                                        $valuesArray[] = $default;
                                    } else if ($required) {
                                        $this->addError('Information obligatoire non renseignée : "' . $defs['label'] . '"');
                                    }
                                }
                                $datas[$dataName] = $valuesArray;
                            } else if (isset($_POST[$inputName]) && $_POST[$inputName]) {
                                $value = $this->checkInputData($defs, $_POST[$inputName]);
                                $datas[$dataName] = $value;
                            } else {
                                $default = $dataNode->getAttribute('default');
                                if (isset($default) && ($default !== '')) {
                                    $datas[$dataName] = $default;
                                } else if ($required) {
                                    $this->addError('Information obligatoire non renseignée : "' . $defs['label'] . '"');
                                }
                            }
                        }
                    } else {
                        $this->addError('Definitions absentes pour la donnée "' . $dataName . '"');
                    }
                } else {
                    $this->addError('Erreur de syntaxe XML: 1 attribut "nom" non-spécifié');
                }
            } else {
                $this->addError('Erreur de syntaxe XML: 1 attribut "nom" non-spécifié');
            }
        }
        return $datas;
    }

    public function processRequestForm($prodId, $serial) {
//        echo '<div>';
//        echo '<p>POST:</p>';
//        echo '<pre>';
//        print_r($_POST);
//        echo '</pre>';
//        echo '</div>';
        $this->requestOk = false;
        if (count($this->errors)) {
            $html = '<p class="error">Impossible d\'éxécuter la  requête.<br/><br/>';
            $html .= 'Erreurs:<br/><br/>';
            $i = 1;
            foreach ($this->errors as $error) {
                $html .= $i . '. ' . $error . '<br/>';
            }
            $html .= '</p>';
            return $html;
        }
        if (!isset($this->defsDoc)) {
            return '<p class="error">Erreur: les définitions concernant les données à traiter n\'ont pas été chargée correctement.</p>';
        }

        $requestDatas = $this->processRequestDatas($this->datas['request']);

        if (count($this->errors)) {
            $html = '<p class="error">Des erreurs ont été détectées:<br/><br/>';
            $i = 1;
            foreach ($this->errors as $error) {
                $html .= $i . '. ' . $error . '<br/>';
                $i++;
            }
            $html .= '</p><br/><br/>';
            unset($this->errors);
            $this->errors = array();
//            echo '<pre>';
//            print_r($requestDatas);
//            echo '</pre>';
            $html .= '<div class="singleRequestFormContainer">' . "\n";
            $html .= $this->generateRequestFormHtml($requestDatas, $prodId, $serial);
            $html .= '</div>';
            return $html;
        }

//        echo '<p>Données traitées: </p>' . "\n";
//        echo '<pre>';
//        print_r($requestDatas);
//        echo '</pre>';
        $this->requestOk = true;
        return $requestDatas;
    }

    public function isLastRequestOk() {
        return $this->requestOk;
    }

}

?>
