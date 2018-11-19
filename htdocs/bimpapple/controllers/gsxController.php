<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSXRequests.php';

class gsxController extends BimpController
{

    public static $in_production = true;
    protected $userExchangePrice = true;
    public $gsx = null;
    protected $serial = null;
    protected $serial2 = null;
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

    public function initGsx()
    {
        $this->gsx = new GSX($this->isIphone);
        return array_merge($this->gsx->errors['init'], $this->gsx->errors['soap']);
    }

    public function setSerial($serial, $requestType = false)
    {
        if (preg_match('/^S([0-9A-Z]{11,12})$/', $serial, $matches)) {
            $serial = $matches[1];
        }
        if (preg_match('/^[0-9]{15,16}$/', $serial)) {
            $this->isIphone = true;
        }
        $this->serial = $serial;

        if (in_array($requestType, $this->tabReqForceIphone)) {
            $this->isIphone = true;
        }
        if (in_array($requestType, $this->tabReqForceNonIphone)) {
            $this->isIphone = false;
        }
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


                        $gsx_content .= $this->renderLoadPartsButton($sav, $serial, "deux");

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

    public function renderLoadPartsButton(BS_SAV $sav, $serial = null, $suffixe = "")
    {
        if (!BimpObject::objectLoaded($sav)) {
            $html = BimpRender::renderAlerts('ID du SAV absent ou invalide');
        } else {
            if (is_null($serial)) {
                $equipment = $sav->getChildObject('equipment');
                if (BimpObject::objectLoaded($equipment)) {
                    $serial = $equipment->getData('serial');
                }
            }

            if (is_null($serial)) {
                $html = BimpRender::renderAlerts('Numéro de série de l\'équipement absent');
            } elseif (preg_match('/^S?[A-Z0-9]{11,12}$/', $serial) || preg_match('/^S?[0-9]{15}$/', $serial)) {
                $html = '<div id="loadPartsButtonContainer' . $suffixe . '" class="buttonsContainer">';
                $html .= BimpRender::renderButton(array(
                            'label'       => 'Charger la liste des composants compatibles',
                            'icon_before' => 'download',
                            'classes'     => array('btn btn-default'),
                            'attr'        => array(
                                'onclick' => 'loadPartsList(\'' . $serial . '\', ' . $sav->id . ', \'' . $suffixe . '\')'
                            )
                ));
                $html .= '</div>';
                $html .= '<div id="partsListContainer' . $suffixe . '" class="partsListContainer" style="display: none"></div>';
            } else {
                $html = BimpRender::renderAlerts('Le numéro de série de l\'équipement sélectionné ne correspond pas à un produit Apple: ' . $serial, 'warning');
            }
        }

        return BimpRender::renderPanel('Liste des composants Apple comptatibles', $html, '', array(
                    'type'     => 'secondary',
                    'icon'     => 'bars',
                    'foldable' => true
        ));
    }

    public function renderRepairs($sav)
    {
        $html = '';

        BimpObject::loadClass('bimpapple', 'GSX_Repair');
        $equipment = $sav->getChildObject('equipment');

        $serial = '';
        if (!is_null($equipment) && $equipment->isLoaded()) {
            $serial = $equipment->getData('serial');
            $this->setSerial($serial);
        }

        $html .= '<div class="buttonsContainer align-right">';

        $object_data = '{module: \'bimpapple\', object_name: \'GSX_Repair\'}';
        $extra_data = '{id_sav: ' . $sav->id . ', import_number: \'' . $serial . '\', import_number_type: \'' . ($this->isIphone ? 'imeiNumber' : 'serialNumber') . '\'}';
        $onclick = 'setObjectAction($(this), ' . $object_data . ', \'importRepair\', ' . $extra_data . ', \'import\', null, function() {reloadRepairsViews(' . $sav->id . ')});';

        $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
        $html .= '<i class="fas fa5-cloud-download-alt iconLeft"></i>Importer depuis GSX</button>';

        $html .= '<button type="button" class="btn btn-default" onclick="$(\'#createRepairForm\').slideDown(250)">';
        $html .= '<i class="fa fa-plus-circle iconLeft"></i>Créer une nouvelle réparation</button>';

        $html .= '</div>';

        $html .= '<div id="createRepairForm">';
        $html .= '<div class="createRepairFormContent">';

        $symptomCodes = $this->getSymptomesCodesArray($this->serial);

        $buttons = array();
        $buttons[] = BimpRender::renderButton(array(
                    'label'       => 'Annuler',
                    'icon_before' => 'times',
                    'classes'     => array('btn btn-danger'),
                    'attr'        => array(
                        'onclick' => '$(\'#createRepairForm\').slideUp(250);'
        )));
        $buttons[] = BimpRender::renderButton(array(
                    'label'      => 'Valider',
                    'icon_after' => 'arrow-circle-right',
                    'classes'    => array('btn btn-primary'),
                    'attr'       => array(
                        'onclick' => 'loadRepairForm($(this), ' . $sav->id . ', \'' . $this->serial . '\')'
        )));

        $html .= BimpRender::renderFreeForm(array(
                    array(
                        'label' => 'Type d\'opération',
                        'input' => BimpInput::renderInput('select', 'repairType', null, array(
                            'options' => GSX_Request::getRequestsByType('repair')
                        ))
                    ),
                    array(
                        'label' => 'Symptôme',
                        'input' => BimpInput::renderInput('select', 'symptomesCodes', null, array(
                            'options' => $symptomCodes['sym']
                        ))
                    )
                        ), $buttons, 'Création d\'une nouvelle réparation');

        $html .= '</div>';
        $html .= '</div>';

        $this->loadRepairs($sav->id);

        if (count($this->repairs)) {
            foreach ($this->repairs as $repair) {
                $html .= $repair->renderView('default', true, 2);
            }
        } else {
            $html .= BimpRender::renderAlerts('Aucune réparation enregistrée pour le moment', 'info');
        }

        $html .= '';

        return $html;
    }

    public function renderPartsList($serial, $id_sav = null, $sufixe = '')
    {
        $this->setSerial($serial);

        $add_btn = false;
        if (!is_null($id_sav)) {
            $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV', $id_sav);
            if (!is_null($sav) && $sav->isLoaded()) {
                if ($sav->isPropalEditable()) {
                    $add_btn = true;
                }
            }
        }

        $parts = $this->getPartsListArray();
//        return '<pre>' . print_r($parts, true) . '</pre>';
        $html = '';
        if (!is_null($parts) && is_array($parts) && count($parts)) {
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
            $html .= '<span class="btn btn-default addKeywordFilter" onclick="PM[\'parts' . $sufixe . '\'].addKeywordFilter()"><i class="fa fa-plus-circle iconLeft"></i>Ajouter</span>';
            $html .= '</div>';

            $html .= '<div class="searchBloc">';
            $html .= '<label for="searchPartInput">Recherche par référence: </label>';
            $html .= '<input type="text" name="searchPartInput" class="searchPartInput" size="12" maxlength="24"/>';
            $html .= '<span class="btn btn-default searchPartSubmit" onclick="PM[\'parts' . $sufixe . '\'].searchPartByNum()"><i class="fa fa-search iconLeft"></i>Rechercher</span>';
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
            $headers .= '<th style="min-width: 80px">Prix commande</th>';
            $headers .= '<th style="min-width: 80px">Prix stock</th>';
            $headers .= '<th>Prix spéciaux</th>';
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

                    $price_options = array();
                    if (isset($part['pricingOptions']['pricingOption'])) {
                        $price_options = array();
                        if (is_array($part['pricingOptions']['pricingOption']) && isset($part['pricingOptions']['pricingOption']['code'])) {
                            $price_options = array(
                                $part['pricingOptions']['pricingOption']['code'] => array(
                                    'price'       => $part['pricingOptions']['pricingOption']['price'],
                                    'description' => $part['pricingOptions']['pricingOption']['description']
                                )
                            );
                        } else {
                            foreach ($price_options = $part['pricingOptions']['pricingOption'] as $price_option) {
                                if (isset($price_option['code'])) {
                                    $price_options[$price_option['code']] = array(
                                        'price'       => $price_option['price'],
                                        'description' => $price_option['description']
                                    );
                                }
                            }
                        }
                    }

                    $content .= '<tr class="partRow_' . $i . ' partRow ' . ($odd ? 'odd' : 'even') . '"';
                    $content .= ' data-code="' . (isset($part['componentCode']) ? addslashes($part['componentCode']) : '') . '"';
                    $content .= ' data-eeeCode="' . (isset($part['eeeCode']) ? addslashes($part['eeeCode']) : '') . '"';
                    $content .= ' data-name="' . (isset($part['partDescription']) ? addslashes(str_replace(" ", "", $part['partDescription'])) : '') . '"';
                    $content .= ' data-num="' . (isset($part['partNumber']) ? addslashes($part['partNumber']) : '') . '"';
                    $content .= ' data-newNum="' . $partNewNumber . '"';
                    $content .= ' data-exchange_price="' . (isset($part['exchangePrice']) ? addslashes($part['exchangePrice']) : 0) . '"';
                    $content .= ' data-stock_price="' . (isset($part['stockPrice']) ? addslashes($part['stockPrice']) : 0) . '"';
                    $content .= ' data-type="' . (isset($part['partType']) ? addslashes($part['partType']) : '') . '"';
                    $content .= ' data-price_options="' . (count($price_options) ? htmlentities(json_encode($price_options)) : '') . '"';
                    $content .= '>';
                    $content .= '<td>' . (isset($part['partDescription']) ? addslashes(str_replace(" ", "", $part['partDescription'])) : '') . '</td>';
                    $content .= '<td>' . (isset($part['partNumber']) ? addslashes($part['partNumber']) : '') . '</td>';
                    $content .= '<td>' . $partNewNumber . '</td>';
                    $content .= '<td>' . (isset($part['eeeCode']) ? addslashes($part['eeeCode']) : '') . '</td>';
                    $content .= '<td>' . (isset($part['partType']) ? addslashes($part['partType']) : '') . '</td>';
                    $content .= '<td>' . (isset($part['exchangePrice']) ? addslashes($part['exchangePrice']) : '0') . '</td>';
                    $content .= '<td>' . (isset($part['stockPrice']) ? addslashes($part['stockPrice']) : '0') . '</td>';
                    $content .= '<td>';
                    $fl = true;
                    foreach ($price_options as $code => $option) {
                        if (!$fl) {
                            $content .= '<br/>';
                        } else {
                            $fl = false;
                        }
                        $content .= addslashes($option['price'] . ' (' . $code . ')');
                    }
                    $content .= '</td>';
                    $content .= '<td>';

                    if ($add_btn) {
                        $content .= BimpRender::renderButton(array(
                                    'label'       => 'Ajouter au panier',
                                    'icon_before' => 'shopping-basket',
                                    'classes'     => array('btn', 'btn-default'),
                                    'attr'        => array(
                                        'onclick' => 'addPartToCart($(this), ' . $id_sav . ')'
                                    )
                        ));
                    }
                    $content .= '</td>';
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
        } else {
            $html .= BimpRender::renderAlerts('Echec de la récupération de la liste des composants compatibles depuis la plateforme GSX');
            $html .= $this->gsx->getGSXErrorsHtml();
        }
        return $html;
    }

    public function renderRequestForm($id_sav, $serial, $requestType, $symptomCode, $id_repair = null, &$errors = array())
    {
        global $db, $user, $langs;
        $this->setSerial($serial);

        if (is_null($this->gsx)) {
            $this->initGsx();
        }

        if (!$this->gsx->connect) {
            $errors[] = 'Echec de la connexion à GSX';
            return '';
        }

        BimpObject::loadClass('bimpsupport', 'BS_ApplePart');
        $comptiaCodes = BS_ApplePart::getCompTIACodes();
        $symptomesCodes = $this->getSymptomesCodesArray($this->serial, $symptomCode);
        $gsxRequest = new GSX_Request($this->gsx, $requestType, $comptiaCodes, $symptomesCodes);

        $valDef = array();
        $valDef['serialNumber'] = $this->serial;

        if ($id_sav) {
            $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV', (int) $id_sav);

            if ($sav->isLoaded()) {
                $idUser = (int) $sav->getData('id_user_tech');
                if (!$idUser) {
                    $idUser = $user->id;
                }
                $tech = BimpObject::getInstance('bimpcore', 'Bimp_User', $idUser);
            }

            $valDef['diagnosis'] = $sav->getData('diagnostic');
            $valDef['symptom'] = $sav->getData('symptomes');

            $valDef['unitReceivedDate'] = date("d/m/Y");
            $valDef['unitReceivedTime'] = "08:00";

            $valDef['diagnosedByTechId'] = $tech->getData('apple_techid');
            $valDef['shipTo'] = $tech->getData('apple_shipto');
            $valDef['shippingLocation'] = $tech->getData('apple_shipto');
            $valDef['billTo'] = $tech->getData('apple_service');
            $valDef['soldToContact'] = $tech->dol_object->getFullName($langs);
            $valDef['technicianName'] = $tech->getData('apple_shipto');

            $valDef['billTo'] = $tech->dol_object->array_options['options_apple_service'];
            $valDef['soldToContact'] = $tech->dol_object->getFullName($langs);
            $valDef['technicianName'] = $tech->getData('lastname');
            $phone = $tech->getData('office_phone');
            if (is_null($phone) || !$phone) {
                $phone = $tech->getData('user_mobile');
            }
            $valDef['soldToContactPhone'] = $phone;
            $valDef['poNumber'] = $sav->getData('ref');
            $valDef['purchaseOrderNumber'] = $sav->getData('ref');
            //pour les retour
            $valDef['shipToCode'] = $this->gsx->shipTo;
            $valDef['length'] = "4";
            $valDef['width'] = "2";
            $valDef['height'] = "1";
            $valDef['estimatedTotalWeight'] = "1";

            if (count($this->repairs) < 1) {
                $this->loadRepairs($id_sav);
            }

            foreach ($this->repairs as $repair) {
                $tabT = array();
                $tabT['dispatchId'] = (string) $repair->getData('repair_confirm_number');

                $applePart = BimpObject::getInstance('bimpsupport', 'BS_ApplePart');
                $list = $applePart->getList(array(
                    'id_sav'   => (int) $id_sav,
                    'no_order' => 0
                ));

                foreach ($list as $item) {
                    $tabT['partNumber'] = $item['part_number'];
                    $valDef['WHUBulkReturnOrder'][] = $tabT;
                }
            }

            $client = $sav->getChildObject('client');

            if ($client->isLoaded()) {
                $valDef['customerAddress']['companyName'] = $client->getData('nom');
                $valDef['customerAddress']['city'] = $client->getData('town');
                $valDef['customerAddress']['primaryPhone'] = $client->getData('phone');
                $valDef['customerAddress']['secondaryPhone'] = $client->getData('phone');
                $valDef['customerAddress']['zipCode'] = $client->getData('zip');
                $valDef['customerAddress']['state'] = substr($client->getData('zip'), 0, 2);
                $valDef['customerAddress']['emailAddress'] = $client->getData('email');
                $valDef['customerAddress']['street'] = $client->getData('address');
                $valDef['customerAddress']['addressLine1'] = $client->getData('address');
                $valDef['customerAddress']['country'] = "FRANCE";

                $tabName = explode(" ", $client->getData('nom'));
                $valDef['customerAddress']['firstName'] = $tabName[0];
                $valDef['customerAddress']['lastName'] = (isset($tabName[1]) ? $tabName[1] : $tabName[0]);
            }

            $contact = $sav->getChildObject('contact');
            if ($contact->isLoaded()) {
                $address = (string) $contact->getData('address');
                if ($address) {
                    $valDef['customerAddress']['street'] = $address;
                    $valDef['customerAddress']['addressLine1'] = $address;
                }
                if ((string) $contact->getData('town')) {
                    $valDef['customerAddress']['city'] = $contact->getData('town');
                }

                $valDef['customerAddress']['firstName'] = $contact->getData('firstname');
                $valDef['customerAddress']['lastName'] = $contact->getData('lastname');

                if ((string) $contact->getData('phone')) {
                    $valDef['customerAddress']['primaryPhone'] = $contact->getData('phone');
                }
                if ((string) $contact->getData('phone_mobile')) {
                    $valDef['customerAddress']['secondaryPhone'] = $contact->getData('phone_mobile');
                }
                if ((string) $contact->getData('zip')) {
                    $valDef['customerAddress']['zipCode'] = $contact->getData('zip');
                    $valDef['customerAddress']['state'] = substr($contact->getData('zip'), 0, 2);
                }
                if ((string) $contact->getData('email')) {
                    $valDef['customerAddress']['emailAddress'] = $contact->getData('email');
                }
            }
        }
        return $gsxRequest->generateRequestFormHtml($valDef, $this->serial, $id_sav, $id_repair);
    }

    // Requêtes GSX:

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
//                        return $parts;
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

        $newArray = array('sym'   => array(
                0 => ''
            ), 'issue' => array(
                0 => ''
        ));

        if ($this->gsx->connect) {
            $this->setSerial($serial);
            $datas = $this->gsx->obtainSymtomes($serial, $symCode);

            if (!is_null($symCode)) {
                $newArray['sym'] = array($symCode => $symCode);
            }
            if (isset($datas['ReportedSymptomIssueResponse']['reportedSymptomIssueResponse']['symptoms'])) {
                foreach ($datas['ReportedSymptomIssueResponse']['reportedSymptomIssueResponse']['symptoms'] as $tab) {
                    $newArray['sym'][$tab['reportedSymptomCode']] = $tab['reportedSymptomCode'] . ' - ' . $tab['reportedSymptomDesc'];
                }
            }
            if (isset($datas['ReportedSymptomIssueResponse']['reportedSymptomIssueResponse']['issues'])) {
                $tabTemp = $datas['ReportedSymptomIssueResponse']['reportedSymptomIssueResponse']['issues'];
                if (isset($tabTemp[0])) {
                    foreach ($tabTemp as $tab) {
                        $newArray['issue'][$tab['reportedIssueCode']] = $tab['reportedIssueCode'] . ' - ' . $tab['reportedIssueDesc'];
                    }
                } else {
                    $newArray['issue'][$tabTemp['reportedIssueCode']] = $tab['reportedIssueCode'] . ' - ' . $tabTemp['reportedIssueDesc'];
                }
            }
        }

        return $newArray;
    }

    public function sendGsxRequest($requestType, $serial = null, $id_sav = null, $id_repair = null)
    {
        $html = '';

        $this->setSerial($serial, $requestType);

        $request = '';
        $client = '';
        $wrapper = '';
        $data = array();
        $responseName = '';

        if (isset($_POST['partsCount']) && isset($_POST['partNumber_100']) && $_POST['partNumber_100'] !== 'Part') {
            $_POST['partsCount'] ++;
            $_POST['partNumber_' . $_POST['partsCount']] = $_POST['partNumber_100'];
        }

        $GSXRequest = new GSX_Request($this->gsx, $requestType);
        $data = $GSXRequest->processRequestForm();

        if (!$GSXRequest->isLastRequestOk()) {
            return $data;
        }
        if (is_null($this->gsx)) {
            $errors = $this->initGsx();
            if (count($errors)) {
                return BimpRender::renderAlerts($errors);
            }
        }

        if (!$this->gsx->connect) {
            $html .= BimpRender::renderAlerts('Echec de la connexion GSX');
            return $html;
        }

        if (!self::$in_production && ($this->gsx->apiMode != "ut" || $this->gsx->apiMode != "it")) {
            return BimpRender::renderAlerts('Mode production non activé - Requête ignorée', 'warning');
        }

        $filesError = false;
        if (in_array(BimpTools::getValue('includeFiles', ''), array('1', 1, 'Y')) && !is_null($id_sav)) {
            $dir = DOL_DATA_ROOT . '/bimpcore/bimpsupport/sav/' . $id_sav . '/';
            $files = scandir($dir);
            if (count($files)) {
                $data['fileName'] = $files[0];
                $data['fileData'] = file_get_contents($dir . $files[0]);
            } else {
                $html .= '<p class="alert alert-warning">Aucun fichier-joint n\'a été trouvé</p>';
                $filesError = true;
            }
        } else if (isset($_FILES['fileName']) &&
                isset($_FILES['fileName']['name']) &&
                isset($_FILES['fileName']['tmp_name']) &&
                $_FILES['fileName']['name'] != '') {
            $data['fileName'] = $_FILES['fileName']['name'];
            $data['fileData'] = false;
            if (file_exists($_FILES['fileName']['tmp_name']))
                $data['fileData'] = file_get_contents($_FILES['fileName']['tmp_name']);

            if ($data['fileData'] === false) {
                $filesError = true;
                $html .= '<p class="error">Echec du transfert du fichier-joint:  "' . $_FILES['fileName']['name'] . '"</p>';
            }
        }
        if ($filesError) {
            return $html;
        }

        $client = $GSXRequest->requestName;
        $request = $GSXRequest->request;
        $wrapper = $GSXRequest->wrapper;

        if ($this->isIphone) {
            if (isset($data['serialNumber']) && strlen($data['serialNumber']) > 13) {//Si num imei echange des champ
                $data['alternateDeviceId'] = $data['serialNumber'];
                $data['serialNumber'] = '';
            } else {
                $data['alternateDeviceId'] = "";
            }

            $responseNames = '';

            switch ($requestType) {
                case 'CreateWholeUnitExchange':
                    $responseNames = array("CreateIPhoneWholeUnitExchangeResponse");
                    $client = "CreateIPhoneWholeUnitExchange";
                    $request = "CreateIPhoneWholeUnitExchangeRequest";

                case 'CreateCarryInRepair':
                    $responseNames = array(
                        'IPhoneCreateCarryInResponse',
                        'IPhoneCreateCarryInRepairResponse',
                        'CreateIPhoneCarryInRepairResponse',
                        'CreateIPhoneCarryInResponse'
                    );
                    $client = 'IPhoneCreateCarryInRepair';
                    $request = 'CreateIPhoneCarryInRepairRequest';
                    break;

                case 'UpdateSerialNumber':
                    $responseNames = 'IPhoneUpdateSerialNumberResponse';
                    $client = 'IPhoneUpdateSerialNumber';
                    $request = 'IPhoneUpdateSerialNumberRequest';
                    break;

                case 'KGBSerialNumberUpdate':
                    $responseNames = array(
                        'UpdateIPhoneKGBSerialNumberResponse',
                        'IPhoneUpdateKGBSerialNumberResponse',
                        'IPhoneKGBSerialNumberUpdateResponse',
                        'UpdateIPhoneKGBSerialNumberRequestResponse'
                    );
                    $client = 'IPhoneKGBSerialNumberUpdate';
                    $request = 'UpdateIPhoneKGBSerialNumberRequest';
                    $data['imeiNumber'] = $data['alternateDeviceId'];
                    break;
            }
        } else {
            switch ($requestType) {
                case 'CreateCarryInRepair':
                    $responseNames = array(
                        'CreateCarryInResponse',
                    );
                    break;

                case 'KGBSerialNumberUpdate':
                    $responseNames = array(
                        'UpdateKGBSerialNumberResponse',
                        'KGBSerialNumberUpdateResponse'
                    );
                    break;
            }
        }

        $this->gsx->resetSoapErrors();

        $requestData = $this->gsx->_requestBuilder($request, $wrapper, $data);
        $response = $this->gsx->request($requestData, $client);

//        dol_syslog("Requête " . $request . " | " . print_r($requestData, 1). " | ".print_r($_REQUEST, 1). " | " . print_r($response, 1), LOG_ERR, 0, "_apple");

        if (count($this->gsx->errors['soap'])) {
            $html .= BimpRender::renderAlerts('Echec de l\'envoi de la requête "' . $request . '"');
            $html .= $this->gsx->getGSXErrorsHtml(true);

            foreach ($requestData as $nomReq => $tabT) {
                if (isset($tabT['repairData']) && isset($tabT['repairData']['fileData']))
                    $requestData[$nomReq]['repairData']['fileData'] = "Fichier joint exclu du log";
            }
            if (count($this->gsx->errors['log']['soap']))
                dol_syslog("Erreur GSX : " . $this->gsx->getGSXErrorsHtml() . "Requête :" . print_r($requestData, true) . " Réponse : " . print_r($response, true) . "Wsdl : " . $this->gsx->wsdlUrl, 4, 0, "_apple");
        } elseif (isset($response['error'])) {
            switch ($response['error']) {
                case 'partInfos':
                    $html .= BimpRender::renderAlerts('Veuillez saisir les informations relatives au(x) composant(s) suivant(s)', 'warning');
                    $i = 1;
                    foreach ($response['parts'] as $part_name) {
                        $html .= '<div class="formInputGroup">';
                        $html .= '<div class="row formRow">';
                        $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">Nom</div>';
                        $html .= '<div class="formRowInput col-xs-12 col-sm-6 col-md-9">';
                        $html .= '<div class="inputContainer">';
                        $html .= BimpInput::renderInput('text', 'component_' . $i, $part_name);
                        $html .= '</div>';
                        $html .= '</div>';
                        $html .= '</div>';

                        $html .= '<div class="row formRow">';
                        $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">Numéro de série</div>';
                        $html .= '<div class="formRowInput col-xs-12 col-sm-6 col-md-9">';
                        $html .= '<div class="inputContainer">';
                        $html .= BimpInput::renderInput('text', 'componentSerialNumber_' . $i, '');
                        $html .= '</div>';
                        $html .= '</div>';
                        $html .= '</div>';
                        $html .= '</div>';
                        $i++;
                    }

                    $html .= '<input type="hidden" name="componentCheckDetails_nextIdx" value="' . $i . '"/>';
                    break;

                case 'tierPart':
                    $html .= BimpRender::renderAlerts('veuillez sélectionner un composant tiers');
                    break;

                case 'horsgarantie':
                    $html .= BimpRender::renderAlerts('La réparation est hors garantie. Veuillez vérifier.', 'warning');
                    $html .= '<script type="text/javascript">';
                    $html .= 'if (confirm("La réparation est hors garantie, voulez vous continuer ?")) {';
                    $html .= '$("input[name=checkIfOutOfWarrantyCoverage]").val(0).change();';
                    $html .= '$(\'#page_modal\').find(\'.modal-footer\').find(\'.save_object_button\').click();';
                    $html .= '}';
                    $html .= '</script>';
                    break;
            }
        } else {
            $responseName = $requestType . "Response";

            if (!isset($response[$responseName])) {
                if (is_string($responseNames) && $responseNames) {
                    if (isset($response[$responseNames])) {
                        $responseName = $responseNames;
                    }
                } elseif (is_array($responseNames)) {
                    foreach ($responseNames as $respName) {
                        if (isset($response[$respName])) {
                            $responseName = $respName;
                            break;
                        }
                    }
                }
            }

            if (!isset($response[$responseName])) {
                $html .= BimpRender::renderAlerts('Echec de la requête "' . $request . '" - réponse "' . $responseName . '" absente');
            } else {
                if (isset($response[$responseName]['repairConfirmation']['messages'])) {
                    $message = $response[$responseName]['repairConfirmation']['messages'];
                    if (!is_array($message))
                        $message = array($message);
                    foreach ($message as $mess)
                        $html .= BimpRender::renderAlerts($mess, 'info');
                }

                $repair = BimpObject::getInstance('bimpapple', 'GSX_Repair', $id_repair);
                $repair->setSerial($this->serial);

                $errors = array();
                switch ($requestType) {
                    default:
                        if (isset($response[$responseName]['repairConfirmation']['confirmationNumber'])) {
                            $tabConfirm = $response[$responseName]['repairConfirmation'];
                            $confirmNumber = $tabConfirm['confirmationNumber'];
                            $prixTot = str_replace("EUR", "", $tabConfirm['totalFromOrder']);
                            $prixTot = str_replace("  ", "", $prixTot);
                            $prixTot = (float) str_replace(",", ".", $prixTot);
                            $repair->totalFromOrder = $prixTot;
                            if (BimpTools::getValue('requestReviewByApple', '') === "Y") {
                                $prixTot = 0;
                            }
                            if ((int) $id_sav) {
                                $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV', (int) $id_sav);
                                if (!BimpObject::objectLoaded($sav)) {
                                    $errors[] = 'Erreur: SAV invalide';
                                } else {
                                    $repair->set('id_sav', (int) $id_sav);
                                    $repair->set('repair_confirm_number', $confirmNumber);
                                    $repair->set('total_from_order', $prixTot);

                                    $errors = $repair->create();

                                    if (!count($errors)) {
                                        if ($prixTot) {
                                            $html .= '<script type="text/javascript">';
                                            $html .= 'alert(\'Attention, la réparation n\\\'est pas prise sous garantie. Prix: ' . $prixTot . ' €\');';
                                            $html .= '</script>';
                                        }

                                        $html .= '<script type="text/javascript">';
                                        $html .= $sav->getJsActionOnclick('attentePiece', array(), array('form_name' => 'send_msg'));
                                        $html .= '</script>';
                                    }
                                }
                            } else {
                                $errors[] = 'Une erreur est survenue (ID du SAV absent)';
                            }
                        } else {
                            $errors[] = 'Une Erreur est survenue: aucun numéro de confirmation retourné par Apple. Requete : ' . $client;
                        }
                        break;

                    case 'RegisterPartsForWHUBulkReturn':
                        if (isset($responseName) && isset($response[$responseName]['WHUBulkPartsRegistrationData'])) {
                            $datas = $response[$responseName]['WHUBulkPartsRegistrationData'];
                            $direName = '/bimpcore/bimpsupport/sav/' . $_REQUEST['chronoId'];
                            $fileNamePure = str_replace("/", "_", $datas['packingListFileName']);
                            if (!is_dir(DOL_DATA_ROOT . $direName)) {
                                mkdir(DOL_DATA_ROOT . $direName);
                            }
                            $fileName = $direName . "/" . $fileNamePure;

                            if (!file_exists(DOL_DATA_ROOT . $fileName)) {
                                if (file_put_contents(DOL_DATA_ROOT . $fileName, $datas['packingList']) === false) {
                                    $fileName = null;
                                } else {
                                    $errors[] = 'Echec du téléchargement du fichier "' . $datas['packingList'] . '"';
                                }
                            }
                        } else {
                            $errors[] = 'Une Erreur est survenue: aucun document retourné par Apple';
                        }
                        break;

                    case 'KGBSerialNumberUpdate':
                        if (isset($responseName) && isset($response[$responseName]['repairConfirmationNumber'])) {
                            if ($response[$responseName]['updateStatus'] == "Y") {
                                $confirmNumber = $response[$responseName]['repairConfirmationNumber'];
                                if (!$repair->isLoaded()) {
                                    $repair->repairDetails();
                                } else {
                                    $errors[] = 'Une erreur est survenue (ID de la réparation manquant)';
                                }
                            } else {
                                $errors[] = 'Une Erreur est survenue: echec de la maj';
                            }
                        } else {
                            $errors[] = 'Une Erreur est survenue: aucun numéro de confirmation retourné par Apple';
                        }
                        break;

                    case 'UpdateSerialNumber':
                        if (isset($responseName) && isset($response[$responseName]['repairConfirmation']['repairConfirmationNumber'])) {
                            $confirmNumber = $response[$responseName]['repairConfirmation']['repairConfirmationNumber'];
                            $repair->set('new_serial', 'part');
                            $repair->update();
                            if ($repair->isLoaded()) {
                                $repair->repairDetails();
                            } else {
                                $errors[] = 'Une erreur est survenue (ID de la réparation manquant)';
                            }
                        } else {
                            $errors[] = 'Une Erreur est survenue: aucun numéro de confirmation retourné par Apple';
                        }
                        break;
                }
                if (count($errors)) {
                    $html .= BimpRender::renderAlerts($errors);
                    $msg = 'Erreur lors de la requête "' . $client . '"';
                    dol_syslog($msg . " | " . print_r($response, 1), LOG_ERR, 0, "_apple");
                } else {
                    $html .= BimpRender::renderAlerts('Requête effectuée avec succès', 'success');
                }
            }
        }
        return $html;
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

    protected function ajaxProcessSendGSXRequest()
    {
        $errors = array();
        $html = '';

        $requestType = BimpTools::getValue('requestType', '');
        $serial = BimpTools::getValue('serial', '');
        $id_sav = BimpTools::getValue('id_sav', null);
        $id_repair = BimpTools::getValue('id_repair', null);

        if (!$requestType) {
            $errors[] = 'Type de requête absent';
        }

        if (!$serial) {
            $errors[] = 'Numéro de série absent';
        }

        if (!count($errors)) {
            $html = $this->sendGsxRequest($requestType, $serial, $id_sav, $id_repair);
        }

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessLoadRepairForm()
    {
        $errors = array();
        $html = '';

        $id_sav = BimpTools::getValue('id_sav', 0);
        $id_repair = BimpTools::getValue('id_repair', null);
        $serial = BimpTools::getValue('serial', '');
        $requestType = BimpTools::getValue('repairType', '');
        $symptomCode = BimpTools::getValue('symptomesCodes', '');

        $html = $this->renderRequestForm($id_sav, $serial, $requestType, $symptomCode, $id_repair, $errors);

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessLoadSerialUpdateForm()
    {
        $errors = array();
        $html = '';

        $id_sav = BimpTools::getValue('id_sav', 0);
        $id_repair = BimpTools::getValue('id_repair', null);
        $serial = BimpTools::getValue('serial', '');
        $requestType = BimpTools::getValue('request_type', '');

        $gsxRequest = new GSX_Request($this->gsx, $requestType);

        $valDef = array();

        $repair = BimpObject::getInstance('bimpapple', 'GSX_Repair', $id_repair);
        if (!$repair->isLoaded()) {
            $errors[] = 'ID de la réparation absent ou invalide';
        } else {
            $valDef['repairConfirmationNumber'] = $repair->getData('repair_confirm_number');
            switch ($requestType) {
                case 'UpdateSerialNumber':
                    $repair->loadPartsPending();
                    if (!count($repair->partsPending)) {
                        $errors[] = 'Aucun composant en attente de retour';
                    } else {
                        $valDef['partInfo'] = array();
                        foreach ($repair->partsPending as $partPending) {
                            $valDef['partInfo'][] = array(
                                'partNumber'      => $partPending['partNumber'],
                                'partDescription' => $partPending['partDescription'],
                            );
                        }
                    }
                    break;

                case 'KGBSerialNumberUpdate':
                    break;
            }

            if (!count($errors)) {
                $html = $gsxRequest->generateRequestFormHtml($valDef, $serial, $id_sav, $id_repair);
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
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
        $sufixe = BimpTools::getValue('sufixe', '');
        $html = '';

        if (!$serial) {
            $errors[] = 'Numéro de série absent';
        }

        if (!$id_sav) {
            $errors[] = 'ID du SAV absent';
        }

        if (!count($errors)) {
            $html = $this->renderPartsList($serial, $id_sav, $sufixe);
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
