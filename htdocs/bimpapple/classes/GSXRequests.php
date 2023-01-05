<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';
require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/XMLDoc.class.php';

class GSX_Request
{

    protected $gsx = null;
    protected $errors = array();
    protected $lastError = null;
    protected $defsDoc = null;
    protected $datas = array(
        'request'  => null,
        'response' => null,
    );
    public $requestName = '';
    public $request = '';
    public $requestLabel = '';
    public $wrapper = '';
    protected $requestOk = false;
    protected $comptiaCodes = null;
    public $serial = '';
    public $id_sav = 0;
    public $id_repair = 0;

    public function __construct($gsx, $requestName, $comptiaCodes = null, $symptomesCodes = null)
    {
        $this->gsx = $gsx;
        $this->requestName = $requestName;
        $this->comptiaCodes = $comptiaCodes;
        $this->symptomesCodes = $symptomesCodes;

        // Chargement des définitions:
        $fileName = DOL_DOCUMENT_ROOT . '/bimpapple/views/xml/datas_definitions.xml';
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
        $fileName = DOL_DOCUMENT_ROOT . '/bimpapple/views/xml/requests_definitions.xml';
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

                $data = $defsNode->getAttribute('useCart');
                if (isset($data) && ($data !== ''))
                    $defs['useCart'] = $data;

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

    protected function getDataInput($dataNode, $serial, $values = null, $index = null)
    {
        $name = $dataNode->getAttribute('name');

        if ($dataNode->hasAttribute('multiple'))
            $multiple = $dataNode->getAttribute('multiple');
        else
            $multiple = false;

        if (!$name) {
            return '<p class="alert alert-danger">Erreur de syntaxe dans le fichier xml : 1 attribut "name" non-défini.</p>';
        }

        $inputName = $name . (isset($index) ? '_' . $index : '');
        $valuesName = $name;
        $defs = $this->getDataDefinitionsArray($name);

        if (!isset($defs)) {
            return BimpRender::renderAlerts('Aucune définitions pour la donnée "' . $name . '"', 'warning');
        }

        if (isset($defs['hidden']) && ($defs['hidden'] == '1')) {
            return '';
        }

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
            $html .= '<div id="' . $inputName . '" class="formInputGroup">';
            if (isset($defs['label'])) {
                $html .= '<div class="formGroupHeading">';
                $html .= '<div class="formGroupTitle">';
                $html .= '<h3>' . $defs['label'] . '</h3>';
                $html .= '</div>';
                if ($multiple) {
                    $html .= '<div class="formGroupButtons">';
                    $html .= '<span class="btn btn-default" onclick="duplicateDatasGroup($(this), \'' . $inputName . '\')"><i class="fa fa-plus-circle iconLeft"></i>Ajouter</span>';
                    $html .= '</div>';
                }
                $html .= '</div>';
            }

            $subDatasNode = XMLDoc::findChildElements($dataNode, 'datas', null, null, 1);
            if (count($subDatasNode) == 1) {
                $dataNodes = XMLDoc::findChildElements($subDatasNode[0], 'data', null, null, 1);
                if (!isset($values[$valuesName])) {
                    $values[$valuesName][0] = null;
                } elseif (!isset($values[$valuesName][0]))
                    $values[$valuesName][0] = $values[$valuesName];

                if ($multiple) {
                    $html .= '<div class="dataInputTemplate">' . "\n";
                    foreach ($dataNodes as $node) {
                        $html .= $this->getDataInput($node, $serial, null, 'idx');
                    }
                    $html .= '</div>' . "\n";
                    $html .= '<div class="inputsList">' . "\n";
                    $i = 0;
                    foreach ($values[$valuesName] as $values2) {
                        $html .= '<div class="subInputsList">' . "\n";
                        $i++;
                        foreach ($dataNodes as $node) {
                            $html .= $this->getDataInput($node, $serial, $values2, $i);
                        }
                        $html .= '</div>' . "\n";
                    }
                    $html .= '</div>' . "\n";
                    $html .= '<input type="hidden" id="' . $inputName . '_nextIdx" name="' . $inputName . '_nextIdx" value="' . ($i + 1) . '"/>' . "\n";
                } else {
                    foreach ($dataNodes as $node) {
                        $html .= $this->getDataInput($node, $serial, $values[$valuesName][0], $index);
                    }
                }
            } else {
                $html .= '<p class="alert alert-warning">Aucunes définitions pour ces données</p>' . "\n";
            }
            $html .= '</div>';
        } else {
            $html .= '<div class="row formRow">';
            if (isset($defs['label'])) {
                $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">';
                $html .= $defs['label'];
                $html .= '</div>';
            }

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
                $html .= '<div class="inputTemplate">' . "\n";
            }
            while (true) {
                $html .= '<div class="inputContainer">';
                switch ($defs['type']) {
                    case 'text':
                    case 'number':
                    case 'tel':
                    case 'email':
                    case 'date':
                    case 'time':
                        $html .= BimpInput::renderInput('text', $inputName, $inputValue, array());
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
                                    'options' => (isset($defs['values']) ? $defs['values'] : array())
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

                        $html .= BimpInput::renderInput('toggle', $inputName, $inputValue, array());
                        break;

                    case 'fileSelect':
                        $html .= BimpInput::renderInput('file_upload', $inputName);
                        break;

                    case 'partsList':
                        $useCart = false;
                        if (isset($defs['useCart']) && $defs['useCart'] == '1') {
                            $useCart = true;
                        }

                        $subDatasNode = XMLDoc::findChildElements($dataNode, 'datas', null, null, 1);
                        if (count($subDatasNode) == 1) {
                            $partsDataNodes = XMLDoc::findChildElements($subDatasNode[0], 'data', null, null, 1);
                            if (count($partsDataNodes)) {
                                $orderLines = null;
                                if (isset($values)) {
                                    if (isset($values[$valuesName])) {
                                        if (is_array($values[$valuesName])) {
                                            $orderLines = $values[$valuesName];
                                        }
                                    }
                                }
                                if (!isset($orderLines) && $useCart) {
                                    $orderLines = array();
                                    $applePart = BimpObject::getInstance('bimpsupport', 'BS_ApplePart');
                                    $list = $applePart->getList(array(
                                        'id_sav'   => (int) $this->id_sav,
                                        'no_order' => 0
                                    ));
                                    foreach ($list as $part) {
                                        $pricingOption = '';
                                        if (isset($part['price_type']) && !in_array($part['price_type'], array('STOCK', 'EXCHANGE'))) {
                                            $pricingOption = $part['price_type'];
                                        }
                                        $orderLines[] = array(
                                            'partNumber'      => $part['part_number'],
                                            'comptiaCode'     => $part['comptia_code'],
                                            'comptiaModifier' => $part['comptia_modifier'],
                                            'partDescription' => $part['label'],
                                            'componentCode'   => $part['component_code'],
                                            'pricingOption'   => $pricingOption
                                        );
                                    }
                                }

                                $partCount = 0;
                                if (isset($orderLines) && is_array($orderLines)) {
                                    $i = 1;
                                    foreach ($orderLines as $orderLine) {
                                        $partCount++;

                                        $html .= '<div class="formInputGroup">';
                                        $html .= '<div class="formGroupHeading">';
                                        $html .= '<div class="formGroupTitle">';
                                        $html .= '<h3>';
                                        if (isset($orderLine['partDescription'])) {
                                            $html .= $orderLine['partDescription'];
                                        } else {
                                            $html .= 'Composant ' . $i;
                                        }
                                        $html .= '</h3>';
                                        $html .= '</div>';
                                        $html .= '</div>';

                                        foreach ($partsDataNodes as $partDataNode) {
                                            $html .= $this->getDataInput($partDataNode, $serial, $orderLine, $i);
                                        }
                                        $html .= '</div>';
                                        $i++;
                                    }
                                } else {
                                    $html .= BimpRender::renderAlerts('Aucun composant ajouté au panier de commande', 'warning');
                                }
                                $html .= '<input type="hidden" id="partsCount" name="partsCount" value="' . $partCount . '"/>' . "\n";
                                break;
                            }
                        }
                        $html .= BimpRender::renderAlerts('Aucune définition trouvée pour les données concernant les composants', 'danger');
                        break;

                    case 'tierPart':
                        $tab2 = array();
                        $contFile = file_get_contents(DOL_DOCUMENT_ROOT . "/bimpapple/TierParts.csv");
                        $tab1 = explode("\n", $contFile);
                        foreach ($tab1 as $ligne) {
                            $champ = explode(";", $ligne);
                            $tab2[$champ[0]][] = $champ;
                        }

                        global $db;
                        $tab3 = $tab4 = array(array("", "Part", "Tier Part"));

                        if ((int) $this->id_sav) {
                            $sql = 'SELECT `label` as nom, product_label as nom2
                                FROM ' . MAIN_DB_PREFIX . 'bs_sav s, ' . MAIN_DB_PREFIX . 'be_equipment e
                                LEFT JOIN ' . MAIN_DB_PREFIX . 'product p ON p.rowid = e.id_product AND e.id_product != 0
                                WHERE s.id = ' . (int) $this->id_sav . ' AND s.id_equipment = e.id';


                            $res = $db->query($sql);
                            if ($db->num_rows($res) > 0) {
                                $result = $db->fetch_object($res);
                                foreach ($tab2[$result->nom] as $ln) {
                                    if (stripos($ln[0], $result->nom) === 0 && stripos($ln[2], str_ireplace("S", "", $result->nom)) === 0)
                                        $tab3[] = $ln;
                                    if (stripos($ln[0], $result->nom) === 0)
                                        $tab4[] = $ln;
                                }
                                foreach ($tab2[$result->nom2] as $ln) {
                                    if (stripos($ln[0], $result->nom2) === 0 && stripos($ln[2], str_ireplace("S", "", $result->nom2)) === 0)
                                        $tab3[] = $ln;
                                    if (stripos($ln[0], $result->nom2) === 0)
                                        $tab4[] = $ln;
                                }
//                    $tab3 = BimpTools::merge_array($tab3, $tab2[$result->nom]);
                            }
                        }
                        if (count($tab3) < 2) {
                            $tab3 = $tab4;
                        }
                        if (count($tab3) < 2) {
                            foreach ($tab2 as $tabT)
                                foreach ($tabT as $tabT2)
                                    $tab3[] = $tabT2;
                        }
                        $i = 100;

                        $parts = array();
                        foreach ($tab3 as $ligne) {
                            $parts[$ligne[1]] = $ligne[2];
                        }

                        $html .= BimpInput::renderInput('select', 'partNumber_' . $i, 'Part', array(
                                    'options'     => $parts,
                                    'extra_class' => 'tierPart'
                        ));
                        break;

                    case 'comptiaCode':
                        $allComptia = false;
                        if ($dataNode->hasAttribute('allComptia')) {
                            if ($dataNode->getAttribute('allComptia') === '1')
                                $allComptia = true;
                        }

                        if ($allComptia && isset($this->comptiaCodes) &&
                                isset($this->comptiaCodes['grps'])) {
                            foreach (BS_ApplePart::$componentsTypes as $compCode => $label) {
                                foreach ($this->comptiaCodes['grps'][$compCode] as $code => $desc) {
                                    $codes[$code] = $desc;
                                }
                            }
                            $html .= BimpInput::renderInput('select', $inputName, $inputValue, array(
                                        'options' => $codes
                            ));
                        } else {
                            if (($values['componentCode'] === ' ') || !$values['componentCode'] || $values['componentCode'] === '000') {
                                $html .= '<input type="hidden" id="' . $inputName . '" name="' . $inputName . '" value="000"/>' . "\n";
                                $html .= '<span>Non-applicable</span>';
                            } else if (isset($values['componentCode']) &&
                                    isset($this->comptiaCodes) &&
                                    isset($this->comptiaCodes['grps'][$values['componentCode']])) {
                                $html .= BimpInput::renderInput('select', $inputName, $inputValue, array(
                                            'options' => $this->comptiaCodes['grps'][$values['componentCode']]
                                ));
                            } else if (isset($values[$valuesName])) {
                                $html .= BimpInput::renderInput('text', $inputName, $inputValue);
                            }
                        }
                        break;

                    case 'comptiaModifier':
                        if (isset($this->comptiaCodes['mods'])) {
                            $html .= BimpInput::renderInput('select', $inputName, $inputValue, array(
                                        'options' => $this->comptiaCodes['mods']
                            ));
                        } else if (isset($values[$valuesName])) {
                            $html .= BimpInput::renderInput('text', $inputName, $inputValue);
                        }
                        break;

                    case 'reportedSymptomCode':
                        $html .= BimpInput::renderInput('select', $inputName, $inputValue, array(
                                    'options' => $this->symptomesCodes['sym']
                        ));
                        break;
                    case 'reportedIssueCode':
                        $html .= BimpInput::renderInput('select', $inputName, $inputValue, array(
                                    'options' => $this->symptomesCodes['issue']
                        ));
                        break;

                    default:
                        $html .= '<p class="alert alert-warning">Type inéxistant pour la donnée "' . $name . '"</p>';
                        break;
                }
                if (isset($defs['infos']) && $defs['infos']) {
                    $html .= '<p class="inputHelp">' . $defs['infos'] . '</p>';
                }
                $html .= '</div>';

                if (!$multiple)
                    break;
                else {
                    if ($multipleIndex == 0) {
                        $html .= '</div>';
                        $html .= '<div class="inputsList">';
                    }
                    $inputName = $name . (isset($index) ? '_' . $index : '');
                    if (!isset($valuesArray[$multipleIndex])) {
                        $html .= '</div>' . "\n";
                        $html .= '<input type="hidden" id="' . $inputName . '_nextIdx" name="' . $inputName . '_nextIdx" value="' . $multipleIndex . '"/>' . "\n";
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
            $html = '<p class="alert alert-danger">Impossible d\'afficher le formulaire pour cette requête.<br/><br/>';
            $html .= 'Erreurs:<br/><br/>';
            $i = 1;
            foreach ($this->errors as $error) {
                $html .= $i . '. ' . $error . '<br/>';
            }
            $html .= '</p>';
            return $html;
        }

        if (!isset($this->defsDoc)) {
            return BimpRender::renderAlerts('Erreur: les définitions  concernant les données à traiter n\'ont pas été chargée correctement');
        }

        $this->serial = $serial;

        $html .= '<div class="container-fluid">';
        $html .= '<form id="repairForm_' . $this->requestName . '" class="request_form" enctype="multipart/form-data">';

        $html .= '<input name="requestType" type="hidden" value="' . $this->requestName . '"/>';
        $html .= '<input name="serial" type="hidden" value="' . $serial . '"/>';

        if (!is_null($id_repair)) {
            $this->id_repair = $id_repair;
            $html .= '<input name="id_repair" type="hidden" value="' . $id_repair . '"/>';
        }
        if (!is_null($id_sav)) {
            $this->id_sav = $id_sav;
            $html .= '<input name="id_sav" type="hidden" value="' . $id_sav . '"/>';
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
        if ($defs['type'] === 'YesNo') {
            if ((int) $value) {
                $value = 'Y';
            } else {
                $value = 'N';
            }
        }
        return $value;
    }

    public function processRequestDatas(array $datasNodes, $dataIndex = null)
    {
        $datas = array();
        foreach ($datasNodes as $dataNode) {
            if (!$dataNode->hasAttribute('donotprocess')) {
                if ($dataNode->hasAttribute('name')) {
                    $dataName = $dataNode->getAttribute('name');
                    if ($dataName !== '') {
                        $required = $dataNode->getAttribute('required');
                        if ($required === '1')
                            $required = true;
                        else
                            $required = false;

                        if ($dataNode->hasAttribute('multiple'))
                            $multiple = $dataNode->getAttribute('multiple');
                        else
                            $multiple = false;

                        $defs = $this->getDataDefinitionsArray($dataName);
                        if (isset($defs)) {
                            if ($defs['type'] == 'tierPart') {
                                continue;
                            }
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
                                    if ($multiple) {
                                        if (!isset($_POST[$dataName . '_nextIdx']))
                                            $_POST[$dataName . '_nextIdx'] = 0;
                                        if (isset($_POST[$dataName . '_nextIdx'])) {
                                            $datas[$dataName] = array();
                                            for ($i = 1; $i < (int) $_POST[$dataName . '_nextIdx']; $i++) {
                                                $datas[$dataName][] = $this->processRequestDatas($subDatasNodes, $i);
                                            }
                                        } else {
                                            $this->addError('Une erreur est survenu, impossible de déterminer le nombre de champs ajoutés pour : "' . $dataName . '"');
                                        }
                                    } else {
                                        $datas[$dataName] = $this->processRequestDatas($subDatasNodes);
                                    }
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
                                        } else
                                            $valuesArray[] = "";
                                    }
                                    $datas[$dataName] = $valuesArray;
                                } else if (isset($_POST[$inputName]) && ($_POST[$inputName] != '')) {
                                    $value = $this->checkInputData($defs, $_POST[$inputName]);
                                    $datas[$dataName] = $value;
                                } else {
                                    $default = $dataNode->getAttribute('default');
                                    if (isset($default) && ($default !== '')) {
                                        $datas[$dataName] = $default;
//                                    } else if ($required) {
//                                        $this->addError('Information obligatoire non renseignée : "' . $defs['label'] . '"');
                                    } else
                                        $datas[$dataName] = "";
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
        }
        $newData = array();
        foreach ($datas as $key => $val)
            $newData[str_replace("componentSerialNumber", "serialNumber", $key)] = $val;
        return $newData;
    }

    public function processRequestForm()
    {
        $this->requestOk = false;
        if (count($this->errors)) {
            $html = '<p class="alert alert-danger">Impossible d\'éxécuter la  requête.<br/><br/>';
            $html .= 'Erreurs:<br/><br/>';
            $i = 1;
            foreach ($this->errors as $error) {
                $html .= $i . '. ' . $error . '<br/>';
            }
            $html .= '</p>';
            return $html;
        }
        if (!isset($this->defsDoc)) {
            return '<p class="alert alert-danger">Erreur: les définitions concernant les données à traiter n\'ont pas été chargées correctement.</p>';
        }

        $requestDatas = $this->processRequestDatas($this->datas['request']);

        if (count($this->errors)) {
            $html = '<p class="alert alert-danger">Des erreurs ont été détectées:<br/><br/>';
            $i = 1;
            foreach ($this->errors as $error) {
                $html .= $i . '. ' . $error . '<br/>';
                $i++;
            }
            $html .= '</p><br/><br/>';
            unset($this->errors);
            $this->errors = array();
            return $html;
        }

        $this->requestOk = true;
        return $requestDatas;
    }

    public function isLastRequestOk()
    {
        return $this->requestOk;
    }
}

?>
