<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX.class.php';

class gsxController extends BimpController
{

    protected $userExchangePrice = true;
    public $gsx = null;
    protected $serial = null;
    public $partsPending = null;
    protected $isIphone = false;
    protected $tabReqForceIphone = array("CreateIPhoneRepairOrReplace");
    protected $tabReqForceNonIphone = array("RegisterPartsForWHUBulkReturn");
    public static $componentsTypes = array(
        0   => 'Général',
        1   => 'Visuel',
        2   => 'Moniteurs',
        3   => 'Mémoire auxiliaire',
        4   => 'Périphériques d\'entrées',
        5   => 'Cartes',
        6   => 'Alimentation',
        7   => 'Imprimantes',
        8   => 'Périphériques multi-fonctions',
        9   => 'Périphériques de communication',
        'A' => 'Partage',
        'B' => 'iPhone',
        'E' => 'iPod',
        'F' => 'iPad',
        'W' => 'Watch'
    );
    protected $repairs = array();

    public function initGsx($requestType = false)
    {
        if (in_array($requestType, $this->tabReqForceIphone)) {
            $this->isIphone = true;
        }
        if (in_array($requestType, $this->tabReqForceNonIphone)) {
            $this->isIphone = false;
        }

        $this->gsx = new GSX($this->isIphone);
        return array_merge($this->gsx->errors['init'], $this->gsx->errors['soap']);
    }

    public function setSerial($serial)
    {
        if (preg_match('/^[0-9]{15,16}$/', $serial)) {
            $this->isIphone = true;
        }
        $this->serial = $serial;
    }

    public function getPartsListArray($partNumberAsKey = false)
    {
        if (is_null($this->gsx)) {
            $this->initGsx();
        }

        if ($this->gsx->connect) {
            $params = array();

            if ($this->isIphone) {
                $params['imeiNumber'] = $this->serial;
            } else {
                $params['serialNumber'] = $this->serial;
            }

            $parts = $this->gsx->part($params);

            if (isset($parts) && count($parts)) {
                if (isset($parts['ResponseArray']) && count($parts['ResponseArray'])) {
                    if (isset($parts['ResponseArray']['responseData']) && count($parts['ResponseArray']['responseData'])) {
                        $parts = $parts['ResponseArray']['responseData'];
                        if (isset($parts["partDescription"]))
                            $parts = array(0 => $parts);
                        if ($partNumberAsKey) {
                            $results = array();
                            foreach ($parts as $part) {
                                $results[$part['partNumber']] = $part;
                            }
                            return $results;
                        } else {
                            return $parts;
                        }
                    }
                }
            }
        }
        return null;
    }

    public function getSymptomesCodesArray($serial, $symCode = null)
    {
        if (is_null($this->gsx)) {
            $this->initGsx();
        }

        $newArray = array('sym' => array(), 'issue' => array());

        if ($this->gsx->connect) {
            $this->setSerial($serial);
            $datas = $this->gsx->obtainSymtomes($serial, $symCode);

            if (!is_null($symCode))
                $newArray['sym'] = array($symCode => $symCode);
            if (isset($datas['ReportedSymptomIssueResponse']['reportedSymptomIssueResponse']['symptoms']))
                foreach ($datas['ReportedSymptomIssueResponse']['reportedSymptomIssueResponse']['symptoms'] as $tab) {
                    $newArray['sym'][$tab['reportedSymptomCode']] = $tab['reportedSymptomDesc'];
                }

            if (isset($datas['ReportedSymptomIssueResponse']['reportedSymptomIssueResponse']['issues'])) {
                $tabTemp = $datas['ReportedSymptomIssueResponse']['reportedSymptomIssueResponse']['issues'];
                if (isset($tabTemp[0]))
                    foreach ($tabTemp as $tab) {
                        $newArray['issue'][$tab['reportedIssueCode']] = $tab['reportedIssueDesc'];
                    } else {
                    $newArray['issue'][$tabTemp['reportedIssueCode']] = $tabTemp['reportedIssueDesc'];
                }
            }
        }

        return $newArray;
    }

    public function getCompTIACodesArray()
    {
        $codes = array(
            'grps' => array(),
            'mods' => array()
        );

        if (is_null($this->gsx)) {
            $this->initGsx();
        }

        if ($this->gsx->connect) {
            $datas = $this->gsx->obtainCompTIA();

            if (isset($datas) && count($datas)) {
                if (isset($datas['ComptiaCodeLookupResponse']) && count($datas['ComptiaCodeLookupResponse'])) {
                    if (isset($datas['ComptiaCodeLookupResponse']['comptiaInfo']) && count($datas['ComptiaCodeLookupResponse']['comptiaInfo'])) {
                        $datas = $datas['ComptiaCodeLookupResponse']['comptiaInfo'];
                        if (isset($datas['comptiaGroup']) && count($datas['comptiaGroup'])) {
                            foreach ($datas['comptiaGroup'] as $i => $group) {
                                $codes['grps'][$group['componentId']] = array();
                                if ($i == 0) {
                                    $codes['grps'][$group['componentId']]['000'] = 'Non-applicable';
                                } else if (isset($group['comptiaCodeInfo']) && count($group['comptiaCodeInfo'])) {
                                    foreach ($group['comptiaCodeInfo'] as $codeInfo) {
                                        $codes['grps'][$group['componentId']][$codeInfo['comptiaCode']] = $codeInfo['comptiaDescription'];
                                    }
                                }
                            }
                        }
                        if (isset($datas['comptiaModifier']) && count($datas['comptiaModifier'])) {
                            foreach ($datas['comptiaModifier'] as $mod) {
                                $codes['mods'][$mod['modifierCode']] = $mod['comptiaDescription'];
                            }
                        }
                    }
                }
            }
        }
        return $codes;
    }

    public function loadRepairs($id_sav)
    {
        if (!(int) $id_sav) {
            return;
        }

        $instance = BimpObject::getInstance('bimpapple', 'GSX_Repair');
        $list = $instance->getList(array(
            'id_sav' => (int) $id_sav
                ), null, null, 'id', 'desc', 'array', array('id'));

        $this->repairs = array();

        foreach ($list as $item) {
            $repair = BimpObject::getInstance('bimpapple', 'GSX_Repair', (int) $item['id']);
            if (!is_null($repair) && $repair->isLoaded())
                $this->repairs[] = $repair;
        }
    }

    public function fetchPartsListFromPost()
    {
        $parts = array();
        $i = 1;
        while (true) {
            if (isset($_POST['part_' . $i . '_ref'])) {
                $parts[] = array(
                    'partNumber'      => $_POST['part_' . $i . '_ref'],
                    'comptiaCode'     => (isset($_POST['part_' . $i . '_comptiaCode']) ? $_POST['part_' . $i . '_comptiaCode'] : 0),
                    'comptiaModifier' => (isset($_POST['part_' . $i . '_comptiaModifier']) ? $_POST['part_' . $i . '_comptiaModifier'] : 0),
                    'qty'             => (isset($_POST['part_' . $i . '_qty']) ? $_POST['part_' . $i . '_qty'] : 1),
                    'componentCode'   => (isset($_POST['part_' . $i . '_componentCode']) ? $_POST['part_' . $i . '_componentCode'] : ''),
                    'partDescription' => (isset($_POST['part_' . $i . '_partDescription']) ? $_POST['part_' . $i . '_partDescription'] : 'Composant ' . $i),
                    'stockPrice'      => (isset($_POST['part_' . $i . '_stockPrice']) ? $_POST['part_' . $i . '_stockPrice'] : '')
                );
            } else
                break;
            $i++;
        }
        return $parts;
    }

    public function isIphone($serial)
    {
        if (preg_match('/^[0-9]{15,16}$/', $serial))
            return true;
        return false;
    }

    // Rendus HTML: 

    public function renderGSxView($serial, $id_sav)
    {
        $this->setSerial($serial);

        if (is_null($this->gsx)) {
            $errors = $this->initGsx();
        }

        $sav = null;

        if (!(int) $id_sav) {
            $errors[] = 'ID du SAV absent';
        } else {
            $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV', (int) $id_sav);
            if (!$sav->isLoaded()) {
                $errors[] = 'Le SAV d\'ID ' . $id_sav . ' n\'existe pas';
            }
        }

        if (count($errors)) {
            return BimpRender::renderAlerts($errors);
        }

        if ($this->gsx->connect) {
            $html = '';
            $response = $this->gsx->lookup($this->serial, 'warranty');
            $check = false;
            if (isset($response) && count($response)) {
                if (isset($response['ResponseArray']) && count($response['ResponseArray'])) {
                    if (isset($response['ResponseArray']['responseData']) && count($response['ResponseArray']['responseData'])) {
                        $datas = $response['ResponseArray']['responseData'];
                        $urgentMsg = $response['ResponseArray']['urgentMessage'];
                        $check = true;

                        $lookUpContent = '';
                        if (isset($urgentMsg) && ($urgentMsg != '')) {
                            $lookUpContent .= '<p class="alert alert-warning">Message urgent du service Apple GSX: <br/>';
                            $lookUpContent .= '"' . $urgentMsg . '"';
                            $lookUpContent .= '</p>';
                        }

                        $lookUpContent .= '<table class="bimp_list_table">';
                        $lookUpContent .= '<tbody>';

//                    $lookUpContent .= '<tr>' . "\n";
//                    $src = $datas['imageURL'];
//                    if (isset($src) && $src) {
//                    $lookUpContent .= '<td class="productImgContainer">' . "\n";
//                    $lookUpContent .= '<img class="productImg" src="' . $src . '"/>' . "\n";
//                    $lookUpContent .= '</td>' . "\n";
//                    }
//                    $lookUpContent .= '<td>' . "\n";
//                    $lookUpContent .= '<table><thead></thead><tbody>' . "\n";
//echo "<pre>"; print_r($datas);die;

                        $this->serial2 = $datas['serialNumber'];

                        if (isset($datas['serialNumber']) && $datas['serialNumber'] !== '')
                            $lookUpContent .= '<tr><th>Numéro de série</th><td>' . $datas['serialNumber'] . '</td></tr>';

                        if (isset($datas['imeiNumber']) && $datas['imeiNumber'] !== '')
                            $lookUpContent .= '<tr><th>Numéro IMEI</th><td>' . $datas['imeiNumber'] . '</td></tr>';

                        if (isset($datas['configDescription']) && $datas['configDescription'] !== '')
                            $lookUpContent .= '<tr><th>Configuration</th><td>' . $datas['configDescription'] . '</td></tr>';

                        if (isset($datas['warrantyReferenceNo']) && $datas['warrantyReferenceNo'] !== '')
                            $lookUpContent .= '<tr><th>Numéro de garantie</th><td>' . $datas['warrantyReferenceNo'] . '</td></tr>';

                        if (isset($datas['warrantyStatus']) && $datas['warrantyStatus'] !== '')
                            $lookUpContent .= '<tr><th>Garantie</th><td>' . $datas['warrantyStatus'] . '</td></tr>';

                        if (isset($datas['onsiteStartDate']) && $datas['onsiteStartDate'] !== '')
                            $lookUpContent .= '<tr><th>Date d\'entrée</th><td>' . $datas['onsiteStartDate'] . '</td></tr>';

                        if (isset($datas['onsiteEndDate']) && $datas['onsiteEndDate'] !== '')
                            $lookUpContent .= '<tr><th>Date de sortie</th><td>' . $datas['onsiteEndDate'] . '</td></tr>';

                        if (isset($datas['estimatedPurchaseDate']) && $datas['estimatedPurchaseDate'] !== '')
                            $lookUpContent .= '<tr><th>Date d\'achat estimé</th><td>' . $datas['estimatedPurchaseDate'] . '</td></tr>';

                        if (isset($datas['coverageStartDate']) && $datas['coverageStartDate'] !== '')
                            $lookUpContent .= '<tr><th>Début de la garantie</th><td>' . $datas['coverageStartDate'] . '</td></tr>';

                        if (isset($datas['coverageEndDate']) && $datas['coverageEndDate'] !== '')
                            $lookUpContent .= '<tr><th>Fin de la garantie</th><td>' . $datas['coverageEndDate'] . '</td></tr>';

                        if (isset($datas['daysRemaining']) && $datas['daysRemaining'] !== '')
                            $lookUpContent .= '<tr><th>Jours restants</th><td>' . $datas['daysRemaining'] . '</td></tr>';

                        if (isset($datas['notes']) && $datas['notes'] !== '')
                            $lookUpContent .= '<tr><th>Note</th><td>' . $datas['notes'] . '</td></tr>';

                        if (isset($datas['activationLockStatus']) && $datas['activationLockStatus'] !== '')
                            $lookUpContent .= '<tr style="color:red;"><th>Localisé</th><td>' . $datas['activationLockStatus'] . '</td></tr>';

                        if (isset($datas['manualURL']) && $datas['manualURL'] !== '')
                            $lookUpContent .= '<tr style="height: 30px;"><td colspan="2"><a class="btn btn-default" href="' . $datas['manualURL'] . '"><i class="fas fa5-pdf-file iconLeft"></i>Manuel</a></td></tr>';

                        $lookUpContent .= '</tbody></table>';

                        $gsx_content = BimpRender::renderPanel('Informations produit', $lookUpContent, '', array(
                                    'type'     => 'default',
                                    'icon'     => 'info-circle',
                                    'foldable' => true
                        ));

                        $part = BimpObject::getInstance('bimpsupport', 'BS_ApplePart');
                        $list = new BC_ListTable($part, 'default', 1, $sav->id);
                        $list->addIdentifierSuffix('gsx');
                        $gsx_content .= $list->renderHtml();

                        $gsx_content .= BimpRender::renderPanel('Réparations', $this->renderRepairs($sav), '', array(
                                    'panel_id' => 'sav_repairs',
                                    'type'     => 'secondary',
                                    'icon'     => 'wrench',
                                    'foldable' => true
                        ));

                        $parts_content = '<div id="loadPartsButtonContainer" class="buttonsContainer">';
                        $parts_content .= BimpRender::renderButton(array(
                                    'label'       => 'Charger la liste des composants compatibles',
                                    'icon_before' => 'download',
                                    'classes'     => array('btn btn-default'),
                                    'attr'        => array(
                                        'onclick' => 'loadPartsList(\'' . $serial . '\', ' . $sav->id . ')'
                                    )
                        ));
                        $parts_content .= '</div>';
                        $parts_content .= '<div id="partsListContainer" style="display: none"></div>';
                        $gsx_content .= BimpRender::renderPanel('Liste des composants Apple comptatibles', $parts_content, '', array(
                                    'type'     => 'secondary',
                                    'icon'     => 'bars',
                                    'foldable' => true
                        ));

                        $html .= BimpRender::renderPanel($datas['productDescription'], $gsx_content, '', array(
                                    'type'     => 'secondary',
                                    'foldable' => true
                        ));
                    }
                }
            }

            if (!$check) {
                $this->errors[] = 'GSX_lookup_fail';
                $html .= BimpRender::renderAlerts('Echec de la récupération des données depuis la plateforme Apple GSX');
                $html .= BimpRender::renderAlerts($this->gsx->errors['soap']);
            }

//        $response = $this->gsx->lookup($this->serial, 'model');
//        echo '<pre>';
//        echo print_r($response);
//        echo '</pre>';

            return $html;
        }

        return BimpRender::renderAlerts('Echec de la connexion GSX pour une raison inconnue');
    }

    public function renderRepairs($sav)
    {
        $html = '';

        BimpObject::loadClass('bimpapple', 'GSX_Repair');
        $equipment = $sav->getChildObject('equipment');

        $serial = '';
        if (!is_null($equipment) && $equipment->isLoaded()) {
            $serial = $equipment->getData('serial');
        }

        $html .= '<div class="buttonsContainer align-right">';

        $object_data = '{module: \'bimpapple\', object_name: \'GSX_Repair\'}';
        $extra_data = '{id_sav: ' . $sav->id . ', import_number: \'' . $serial . '\', import_number_type: \'' . ($this->isIphone ? 'imeiNumber' : 'serialNumber') . '\'}';
        $onclick = 'setObjectAction($(this), ' . $object_data . ', \'importRepair\', ' . $extra_data . ', \'import\', null, function() {reloadRepairsViews(' . $sav->id . ')});';

        $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
        $html .= '<i class="fas fa5-cloud-download-alt iconLeft"></i>Importer depuis GSX</button>';
        $html .= '</div>';

        $this->loadRepairs($sav->id);

        if (count($this->repairs)) {
            foreach ($this->repairs as $repair) {
                $html .= $repair->renderView('default', true, 2);
            }
        } else {
            $html .= BimpRender::renderAlerts('Aucune réparation enregistrée pour le moment', 'info');
        }

        return $html;
    }

    public function renderPartsList($serial, $id_sav)
    {
        $this->setSerial($serial);

        $parts = $this->getPartsListArray();
        $html = '';
        if (!is_null($parts) && is_array($parts) && count($parts)) {
//            $html .= '<div class="typeFilters searchBloc">' . "\n";
//            $html .= '<span class="btn btn-default filterTitle">Filtrer par catégorie de composant</span>';
//            $html .= '<div class="typeFiltersContent">' . "\n";
//            $html .= '<div style="margin-bottom: 20px;">' . "\n";
//            $html .= '<span class="filterCheckAll">Tout cocher</span>';
//            $html .= '<span class="filterHideAll">Tout décocher</span></div></div>';
//            $html .= '</div>' . "\n";

            $html .= '<div class="partsSearchContainer">';
            $html .= '<div class="searchBloc">';
            $html .= '<label for="keywordFilter">Filtrer par mots-clés: </label>';
            $html .= '<input type="text max="80" name="keywordFilter" class="keywordFilter"/>';
            $html .= '<select class="keywordFilterType">';
            $types = array('name' => 'Nom', 'eeeCode' => 'eeeCode', 'num' => 'Référence', 'type' => 'Type', 'price' => 'Prix');
            foreach ($types as $key => $type) {
                $html .= '<option value="' . $key . '">' . $type . '</option>';
            }
            $html .= '</select>';
            $html .= '<span class="btn btn-default addKeywordFilter" onclick="PM.addKeywordFilter()"><i class="fa fa-plus-circle iconLeft"></i>Ajouter</span>';
            $html .= '</div>';

            $html .= '<div class="searchBloc">';
            $html .= '<label for="searchPartInput">Recherche par référence: </label>';
            $html .= '<input type="text" name="searchPartInput" class="searchPartInput" size="12" maxlength="24"/>';
            $html .= '<span class="btn btn-default searchPartSubmit" onclick="PM.searchPartByNum()"><i class="fa fa-search iconLeft"></i>Rechercher</span>';
            $html .= '</div>';

            $html .= '<div class="curKeywords"></div>';
            $html .= '</div>';

            $html .= '<div class="partsSearchResult"></div>';


            $groups = array();

            foreach ($parts as $part) {
                $group = (isset($part['componentCode']) ? addslashes($part['componentCode']) : '');
                if (!$group || $group === ' ') {
                    $group = 0;
                }

                if (!isset($groups[$group])) {
                    $groups[$group] = array();
                }

                $groups[$group][] = $part;
            }

            $html .= '<div id="partsList">';

            $headers = '<thead>';
            $headers .= '<th style="min-width: 250px">Nom</th>';
            $headers .= '<th style="min-width: 80px">Ref.</th>';
            $headers .= '<th style="min-width: 80px">Nouvelle Ref.</th>';
            $headers .= '<th style="min-width: 80px">eeeCode</th>';
            $headers .= '<th style="min-width: 80px">Type</th>';
            $headers .= '<th style="min-width: 80px">Prix</th>';
            $headers .= '<th></th>';
            $headers .= '</thead>';

            foreach ($groups as $group => $group_parts) {
                $content = '<table id="partGroup_' . $group . '" class="bimp_list_table partGroup">';
                $content .= $headers;

                $content .= '<tbody>';

                $i = 1;

                $odd = true;
                foreach ($group_parts as $part) {
                    $partNewNumber = '';
                    if (isset($part['originalPartNumber']) && $part['originalPartNumber'] != '') {
                        $partNewNumber = $part['partNumber'];
                        $part['partNumber'] = $part['originalPartNumber'];
                    }

                    $content .= '<tr class="partRow_' . $i . ' partRow ' . ($odd ? 'odd' : 'even') . '"';
                    $content .= ' data-code="' . (isset($part['componentCode']) ? addslashes($part['componentCode']) : '') . '"';
                    $content .= ' data-eeeCode="' . (isset($part['eeeCode']) ? addslashes($part['eeeCode']) : '') . '"';
                    $content .= ' data-name="' . (isset($part['partDescription']) ? addslashes(str_replace(" ", "", $part['partDescription'])) : '') . '"';
                    $content .= ' data-num="' . (isset($part['partNumber']) ? addslashes($part['partNumber']) : '') . '"';
                    $content .= ' data-newNum="' . $partNewNumber . '"';
                    $content .= ' data-price="' . (isset($part['exchangePrice']) && $this->userExchangePrice && $part['exchangePrice'] > 0 ? addslashes($part['exchangePrice']) : (isset($part['stockPrice']) ? addslashes($part['stockPrice']) : '')) . '"';
                    $content .= ' data-type="' . (isset($part['partType']) ? addslashes($part['partType']) : '') . '"';
                    $content .= '>';
                    $content .= '<td>' . (isset($part['partDescription']) ? addslashes(str_replace(" ", "", $part['partDescription'])) : '') . '</td>';
                    $content .= '<td>' . (isset($part['partNumber']) ? addslashes($part['partNumber']) : '') . '</td>';
                    $content .= '<td>' . $partNewNumber . '</td>';
                    $content .= '<td>' . (isset($part['eeeCode']) ? addslashes($part['eeeCode']) : '') . '</td>';
                    $content .= '<td>' . (isset($part['partType']) ? addslashes($part['partType']) : '') . '</td>';
                    $content .= '<td>' . (isset($part['exchangePrice']) && $this->userExchangePrice && $part['exchangePrice'] > 0 ? addslashes($part['exchangePrice']) : (isset($part['stockPrice']) ? addslashes($part['stockPrice']) : '')) . '</td>';

                    $content .= '<td>' . BimpRender::renderButton(array(
                                'label'       => 'Ajouter au panier',
                                'icon_before' => 'shopping-basket',
                                'classes'     => array('btn', 'btn-default'),
                                'attr'        => array(
                                    'onclick' => 'addPartToCart($(this), ' . $id_sav . ')'
                                )
                            )) . '</td>';
                    $content .= '</tr>';
                    $i++;
                    $odd = !$odd;
                }

                $content .= '</tbody>';
                $content .= '</table>';

                $title = self::$componentsTypes[$group] . ' ' . '<span class="badge partsNbr">' . count($groups[$group]) . '</span>';
                $html .= BimpRender::renderPanel($title, $content, '', array(
                            'type'        => 'default',
                            'panel_class' => 'parts_group_panel',
                            'foldable'    => true,
                            'open'        => false
                ));
            }

            $html .= '<script type="text/javascript">';
            $html .= 'var PM = new PartsManager();';
            $html .= '</script>';
            $html .= '</div>';
        } else {
            $html .= BimpRender::renderAlerts('Echec de la récupération de la liste des composants compatibles depuis la plateforme GSX');
            $html .= $this->gsx->getGSXErrorsHtml();
        }
        return $html;
//        echo '<pre>';
//        print_r($this->gsx->obtainCompTIA());
//        echo '</pre>';
//        return '';
    }

    // Ajax Process:

    protected function ajaxProcessLoadGSXView()
    {
        $errors = array();

        $serial = BimpTools::getValue('serial', '');
        $id_sav = (int) BimpTools::getValue('id_sav', 0);

        if (!$serial) {
            $errors[] = 'Numéro de série absent';
        }

        if (!$id_sav) {
            $errors[] = 'ID du SAV absent';
        }

        if (!count($errors)) {
            $html = $this->renderGSxView($serial, $id_sav);
        }

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessLoadInfoProduct()
    {
        $errors = array();

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessLoadRepairForm()
    {
        $errors = array();

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessLoadParts()
    {
        $errors = array();

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessLoadCompTIACodes()
    {
        $errors = array();

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessSavePartsCart()
    {
        $errors = array();

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessLoadPartsCart()
    {
        $errors = array();

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessSendGSXRequest()
    {
        $errors = array();

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessLoadSmallInfoProduct()
    {
        $errors = array();

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessImportRepair()
    {
        $errors = array();

        $id_sav = BimpTools::getValue('id_sav', 0);
        $number = BimpTools::getValue('importNumber', '');
        $numberType = BimpTools::getValue('importNumberType', '');

        if (!$id_sav) {
            $errors[] = 'ID du SAV absent';
        }

        if (!$number) {
            $errors[] = 'Identifiant absent';
        }

        if (!$numberType) {
            $errors[] = 'Type d\'identifiant absent';
        }

        if (!count($errors)) {
            if (is_null($this->gsx)) {
                $errors = $this->initGsx();
            }
            if (!count($errors) && $this->gsx->connect) {
                $repair = BimpObject::getInstance('bimpapple', 'GSX_Repair');
                $repair->setGSX($this->gsx);
                $repair->isIphone = $this->isIphone;
                $errors[] = $repair->import($id_sav, $number, $numberType);
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessEndRepair()
    {
        $errors = array();

        $id_repair = (int) BimpTools::getValue('id_repair', 0);

        if (!$id_repair) {
            $errors[] = 'ID de la réparation absent';
        } else {
            if (is_null($this->gsx)) {
                $errors[] = $this->initGsx();
            }

            if (!count($errors)) {
                $repair = BimpObject::getInstance('bimpapple', 'GSX_Repair', $id_repair);

                if (is_null($repair) || !$repair->isLoaded()) {
                    $errors[] = 'Réparation d\'ID ' . $id_repair . ' non trouvée';
                } else {
                    $repair->gsx = $this->gsx;
                    $errors = $repair->updateStatus();
                }
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessCloseRepair()
    {
        $errors = array();

        $id_repair = (int) BimpTools::getValue('id_repair', 0);

        if (!$id_repair) {
            $errors[] = 'ID de la réparation absent';
        } else {
            if (is_null($this->gsx)) {
                $errors[] = $this->initGsx();
            }

            if (!count($errors)) {
                $repair = BimpObject::getInstance('bimpapple', 'GSX_Repair', $id_repair);

                if (is_null($repair) || !$repair->isLoaded()) {
                    $errors[] = 'Réparation d\'ID ' . $id_repair . ' non trouvée';
                } else {
                    $repair->gsx = $this->gsx;
                    $errors = $repair->close(true, (int) BimpTools::getValue('checkRepair', 0));
                }
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessMarkRepairAsReimbursed()
    {
        $errors = array();

        $id_repair = (int) BimpTools::getValue('id_repair', 0);

        if (!$id_repair) {
            $errors[] = 'ID de la réparation absent';
        } else {
            $repair = BimpObject::getInstance('bimpapple', 'GSX_Repair', $id_repair);

            if (is_null($repair) || !$repair->isLoaded()) {
                $errors[] = 'Réparation d\'ID ' . $id_repair . ' non trouvée';
            } else {
                $repair->set('reimbursed', 1);
                $errors = $repair->update();
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessLoadRepairs()
    {
        $errors = array();
        $html = '';

        $id_sav = BimpTools::getValue('id_sav', 0);

        if (!$id_sav) {
            $errors[] = 'ID du SAV absent';
        } else {
            $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV', $id_sav);
            if (is_null($sav) || !$sav->isLoaded()) {
                $errors[] = 'ID du SAV invalide';
            } else {
                $html = $this->renderRepairs($sav);
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessLoadPartsList()
    {
        $errors = array();
        $serial = BimpTools::getValue('serial', '');
        $id_sav = BimpTools::getValue('id_sav', 0);
        $html = '';

        if (!$serial) {
            $errors[] = 'Numéro de série absent';
        }

        if (!$id_sav) {
            $errors[] = 'ID du SAV absent';
        }

        if (!count($errors)) {
            $html = $this->renderPartsList($serial, $id_sav);
        }
        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    public static function dateAppleToDate($date)
    {
        $garantieT = explode("/", $date);
        if (isset($garantieT[2]))
            return $garantieT[0] . "/" . $garantieT[1] . "/20" . $garantieT[2];
        else
            return "";
    }
}
