<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSXRequests.php';
require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSXRequests_v2.php';

class gsxController extends BimpController
{

    public static $in_production = true;
    protected $userExchangePrice = true;
    public $gsx = null;
    public $gsx_v2 = null;
    public $use_gsx_v2;
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
    public static $componentsTypesV2 = array(
        ''     => 'Général',
        'MOD'  => 'Module',
        'OTH'  => 'Autre',
        'REPL' => 'Remplacement'
    );
    protected $repairs = array();
    protected $issueCodes = null;

    public function __construct($module, $controller = 'index')
    {
        $this->use_gsx_v2 = BimpCore::getConf('use_gsx_v2');

        parent::__construct($module, $controller);
    }

    public function displayHead()
    {
        echo GSX_v2::renderJsVars();
    }

    // Reqêtes GSX V2:

    public function gsxRequest($method_name, $params)
    {
        // GSX V2 seulement
        // Doit être le point d'entrée de toutes les reqêtes GSX

        if (!$this->use_gsx_v2) {
            return '';
        }

        if (is_null($this->gsx_v2)) {
            $this->gsx_v2 = GSX_v2::getInstance();
        }

        if (!$this->gsx_v2->logged) {
            // Pas de token d'activation:
            return array();
        }

        if (method_exists($this, $method_name)) {
            return $this->{$method_name}($params);
        }

        return array(
            'errors' => 'Méthode "' . $method_name . '" inexistante'
        );
    }

    // Gestion des requêtes via formulaire: 

    protected function gsxLoadRequestForm($params)
    {
        $errors = array();
        $title = '';
        $html = '';

        $requestName = (isset($params['requestName']) ? $params['requestName'] : '');
        $serial = (isset($params['serial']) ? $params['serial'] : '');
        $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : null);
        $id_repair = (isset($params['id_repair']) ? (int) $params['id_repair'] : null);

        if (!$requestName) {
            $errors[] = 'Type de requête absent';
        }

        if (!count($errors)) {
            $gsx = new GSX_v2();

            if ($gsx->logged) {
                $values = $this->gsxGetRequestFormValues($requestName, $params, $errors);
                if (!count($errors)) {
                    $gsxRequest = new GSX_Request_v2($gsx, $requestName);
                    $html = $gsxRequest->generateRequestFormHtml($values, $serial, $id_sav, $id_repair);
                    $title = $gsxRequest->requestLabel;
                }
            }
        }

        return array(
            'errors'    => $errors,
            'title'     => $title,
            'form_html' => $html
        );
    }

    protected function gsxGetRequestFormValues($requestName, $params, &$errors = array())
    {
        $values = array();

        switch ($requestName) {
            case 'repairCreate':
                $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : 0);
                $serial = (isset($params['serial']) ? $params['serial'] : 0);

                if (!$id_sav) {
                    $errors[] = 'ID du SAV absent';
                } else {
                    $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
                    if (!BimpObject::objectLoaded($sav)) {
                        $errors[] = 'Le SAV d\'ID ' . $id_sav . ' n\'existe pas';
                    }
                }

                if (!$serial) {
                    $errors[] = 'N° de série absent';
                }

                if (!count($errors)) {
                    $note = '';

                    if ($sav->getData('symptomes')) {
                        $note .= 'Symptômes: ';
                        $note .= $sav->getData('symptomes') . "\n\n";
                    }
                    if ($sav->getData('diagnostic')) {
                        $note .= 'Diagnostique: ';
                        $note .= $sav->getData('diagnostic');
                    }
                    $id_user = (int) $sav->getData('id_user_tech');
                    if (!$id_user) {
                        global $user;
                        $id_user = $user->id;
                    }
                    $tech = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);

                    $values['unitReceivedDateTime'] = date('Y-m-d') . ' 08:00:00';
                    $values['purchaseOrderNumber'] = $sav->getData('ref');
                    $values['techId'] = $tech->getData('apple_techid');
                    $values['account'] = array(
                        'soldtoContactEmail' => $tech->getData('email'),
                        'soldToContactPhone' => $phone
                    );
                    $values['device'] = array(
                        'id' => $serial
                    );

                    if ($note) {
                        $values['notes'] = array(
                            array(
                                'type'    => 'TECHNICIAN',
                                'content' => $note
                            )
                        );
                    }

                    // Données client: 
                    $client = $sav->getChildObject('client');
                    if (BimpObject::objectLoaded($client)) {
                        $is_company = false;
                        $id_typeent = (int) $client->getData('fk_typent');
                        if ($id_typeent) {
                            if (!in_array(BimpCache::getBdb()->getValue('c_typent', 'code', '`rowid` = ' . $id_typeent), array('TE_UNKNOWN', 'TE_PRIVATE', 'TE_OTHER'))) {
                                $is_company = true;
                            }
                        }

                        $contact = $sav->getChildObject('contact');

                        if (BimpObject::objectLoaded($contact)) {
                            $contact_data = $contact->getDataArray();
                            if ((string) $contact_data['phone']) {
                                $primary_phone = $contact_data['phone'];
                                $sec_phone = ((string) $contact_data['phone_mobile'] ? (string) $contact_data['phone_mobile'] : (string) $contact_data['phone_perso']);
                            } elseif ((string) $contact_data['phone_mobile']) {
                                $primary_phone = $contact_data['phone_mobile'];
                                $sec_phone = (string) $contact_data['phone_perso'];
                            } else {
                                $primary_phone = (string) $contact_data['phone_perso'];
                                $sec_phone = '';
                            }

                            $values['customer'] = array(
                                'firstName'      => (string) $contact_data['firstname'],
                                'lastName'       => (string) $contact_data['lastname'],
                                'companyName'    => ($is_company ? (string) $client->getData('nom') : ''),
                                'emailAddress'   => (string) $contact_data['email'],
                                'primaryPhone'   => $primary_phone,
                                'secondaryPhone' => $sec_phone,
                                'address'        => array(
                                    array(
                                        'line1'       => $contact_data['address'],
                                        'postalCode'  => $contact_data['zip'],
                                        'city'        => $contact_data['town'],
                                        'countryCode' => ((int) $contact_data['fk_pays'] ? (string) BimpCache::getBdb()->getValue('c_country', 'code_iso', '`rowid` = ' . (int) $contact_data['fk_pays']) : '')
                                    )
                                )
                            );
                        } else {
                            $client_data = $client->getDataArray();

                            $values['customer'] = array(
                                'companyName'  => (string) $client_data['nom'],
                                'emailAddress' => (string) $client_data['email'],
                                'primaryPhone' => (string) $client_data['phone'],
                                'address'      => array(
                                    array(
                                        'line1'       => $client_data['address'],
                                        'postalCode'  => $client_data['zip'],
                                        'city'        => $client_data['town'],
                                        'countryCode' => ((int) $client_data['fk_pays'] ? (string) BimpCache::getBdb()->getValue('c_country', 'code_iso', '`rowid` = ' . (int) $client_data['fk_pays']) : '')
                                    )
                                )
                            );
                        }
                    }

                    // Issues /Parts: 
                    $issues = $sav->getChildrenObjects('issues');

                    if (!empty($issues)) {
                        $values['componentIssues'] = array();

                        foreach ($issues as $issue) {
                            $values['componentIssues'][] = array(
                                'componentCode'   => $issue->getData('category_code'),
                                'issueCode'       => $issue->getData('issue_code'),
                                'reproducibility' => $issue->getData('reproducibility'),
                                'priority'        => $issue->getData('priority'),
                                'type'            => $issue->getData('type'),
                                'order'           => $issue->getData('position'),
                            );

                            $parts = BimpCache::getBimpObjectObjects('bimpsupport', 'BS_ApplePart', array(
                                        'id_issue' => (int) $issue->id,
                                        'no_order' => 0
                            ));

                            if (!empty($parts)) {
                                if (!isset($values['parts'])) {
                                    $values['parts'] = array();
                                }

                                foreach ($parts as $part) {
                                    $typePrice = (string) $part->getData('price_type');
                                    if ($typePrice && !in_array($typePrice, array('STOCK', 'EXCHANGE'))) {
                                        $pricingOption = $typePrice;
                                    } else {
                                        $pricingOption = '';
                                    }

                                    $values['parts'][] = array(
                                        'part_label'     => $part->getData('label'),
                                        'number'         => $part->getData('part_number'),
                                        'pricingOption'  => $pricingOption,
                                        'componentIssue' => array(
                                            'componentCode'   => $issue->getData('category_code'),
                                            'issueCode'       => $issue->getData('issue_code'),
                                            'reproducibility' => $issue->getData('reproducibility')
                                        )
                                    );
                                }
                            }
                        }
                    }
                }
                break;
        }

        return $values;
    }

    protected function gsxProcessRequestForm($params)
    {
        $errors = array();
        $warnings = array();

        $requestName = (isset($params['requestName']) ? $params['requestName'] : '');

        if (!$requestName) {
            $errors[] = 'Nom de la requête absent';
        } else {
            $serial = (isset($params['serial']) ? $params['serial'] : 0);
            $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : 0);
            $id_repair = (isset($params['id_repair']) ? (int) $params['id_repair'] : 0);

            if ($this->gsx_v2->logged) {
                $gsxRequests = new GSX_Request_v2($this->gsx_v2, $requestName);
                $gsxRequests->serial = $serial;
                $gsxRequests->id_sav = $id_sav;
                $gsxRequests->id_repair = $id_repair;

                $result = $gsxRequests->processRequestForm();
                $errors = $this->gsxRequestFormResultOverride($requestName, $result, $params, $warnings);

                if (!count($errors)) {
                    if (is_array($result) && !empty($result)) {
                        $response = $this->gsx_v2->exec($requestName, $result);
                        if ($response === false) {
                            $errors = $this->gsx_v2->getErrors();
                        } else {
                            return $this->gsxOnRequestSuccess($requestName, $response, $params);
                        }
                    } else {
                        $errors[] = BimpTools::getMsgFromArray($gsxRequests->errors, 'Erreurs lors du traitement des données');
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    protected function gsxRequestFormResultOverride($requestName, &$result, $params, &$warnings = array())
    {
        $errors = array();

        $idx = BimpTools::getValue('attachments_nextIdx', 0);
        if ($idx) {
            $files = array();
            for ($i = 1; $i < $idx; $i++) {
                if (isset($_FILES['attachments_file_' . $i])) {
                    $files[] = 'attachments_file_' . $i;
                }
            }
            if (!empty($files)) {
                $serial = (isset($params['serial']) ? $params['serial'] : '');
                if (!$serial && isset($params['id_sav']) && (int) $params['id_sav']) {
                    $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', (int) $params['id_sav']);
                    if (BimpObject::objectLoaded($sav)) {
                        $serial = $sav->getSerial();
                    }
                }
                $uploadResult = $this->gsx_v2->filesUpload($serial, $files);
                if (!is_array($uploadResult) || empty($uploadResult)) {
                    return $this->gsx_v2->getErrors();
                } else {
                    if (!isset($result['attachments'])) {
                        $result['attachments'] = array();
                    }

                    $i = 0;
                    foreach ($uploadResult as $fileInputName => $file_data) {
                        if (!isset($result['attachments'][$i])) {
                            $result['attachments'][$i] = array(
                                'id'   => '',
                                'type' => 'POP'
                            );
                        }

                        $result['attachments'][$i]['id'] = $file_data['id'];
                        $i++;
                    }
                }
            }
        }

        switch ($requestName) {
            case 'repairCreate':
                if (isset($result['componentIssues']) && !empty($result['componentIssues'])) {
                    $i = 0;
                    foreach ($result['componentIssues'] as $key => $compIssue) {
                        $i++;
                        if (!isset($result['componentIssues'][$key]['order']) || !$result['componentIssues'][$key]['order']) {
                            $result['componentIssues'][$key]['order'] = $i;
                        }
                    }
                }
                if (isset($result['repairType'])) {
                    if (in_array($result['repairType'], array('MINS', 'MINC', 'WUMS', 'WUMC'))) {
                        if (!isset($result['account']['soldtoContactEmail']) || !(string) $result['account']['soldtoContactEmail']) {
                            $errors[] = 'L\'e-mail de contact est obligatoire pour les réparations de type "Mail-in"';
                        }
                        if (!isset($result['account']['soldToContactPhone']) || !(string) $result['account']['soldToContactPhone']) {
                            $errors[] = 'Le n° de téléphone de contact est obligatoire pour les réparations de type "Mail-in"';
                        }
                    }
                }
                if (isset($result['customer']['address'][0]['postalCode']) && (string) $result['customer']['address'][0]['postalCode']) {
                    if (isset($result['customer']['address'][0]['countryCode']) && $result['customer']['address'][0]['countryCode'] === 'FRA') {
                        $result['customer']['address'][0]['stateCode'] = substr($result['customer']['address'][0]['postalCode'], 0, 2);
                    }
                }
                break;
        }

        return $errors;
    }

    protected function gsxOnRequestSuccess($requestName, $response, $params)
    {
        $errors = array();
        $warnings = array();

        switch ($requestName) {
            case 'repairCreate':
//                echo 'Requête OK - Réponse:<pre>';
//                print_r($response);
//                echo '</pre>';

                $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : 0);
                if (!$id_sav) {
                    $errors[] = 'ID du SAV absent';
                } else {
                    $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
                    if (!BimpObject::objectLoaded($sav)) {
                        $errors[] = 'Le SAV d\'ID ' . $id_sav . ' n\'existe pas';
                    }
                }

                if (!isset($response['repairId'])) {
                    $errors[] = 'ID de la réparation non reçu';
                }

                if (count($errors)) {
                    return $errors;
                }

                $repair = BimpObject::getInstance('bimpapple', 'GSX_Repair');

                $rep_warnings = array();
                $rep_errors = $repair->validateArray(array(
                    'serial'        => (isset($params['serial']) ? $params['serial'] : $sav->getSerial()),
                    'id_sav'        => $id_sav,
                    'repair_number' => $response['repairId']
                ));

                if (!count($rep_errors)) {
                    $rep_errors = $repair->create($rep_warnings, true);
                }

                if (count($rep_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($rep_warnings, 'Erreurs suite à la création ' . $repair->getLabel('of_the'));
                }

                if (count($rep_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($rep_errors, 'Echec de la création ' . $repair->getLabel('of_the'));
                } else {
                    return array(
                        'errors'           => $errors,
                        'warnings'         => $warnings,
                        'success_callback' => $sav->getJsActionOnclick('attentePiece', array(), array('form_name' => 'send_msg'))
                    );
                }
                break;
        }
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Issues / parts:

    protected function gsxGetParts($params)
    {
        $errors = array();
        $parts = null;

        $serial = (isset($params['serial']) ? $params['serial'] : '');

        if (!$serial) {
            $errors[] = 'N° de série absent';
        }

        if (isset($params['id_issue'])) {
            $issue = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Issue', (int) $params['id_issue']);
            if (!BimpObject::objectLoaded($issue)) {
                $errors[] = 'Le problème composant d\'ID ' . $params['id_issue'] . ' n\'existe pas';
            }
        }

        if (!count($errors)) {
            if ($this->gsx_v2->logged) {
                $result = $this->gsx_v2->partsSummaryBySerialAndIssue($serial, $issue);

                if (isset($result['parts'])) {
                    if (isset($params['partNumberAsKey']) && (int) $params['partNumberAsKey']) {
                        $parts = array();
                        foreach ($result['parts'] as $part) {
                            $parts[$part['partNumber']] = $part;
                        }
                    } else {
                        $parts = $result['parts'];
                    }
                } else {
                    $errors = $this->gsx_v2->getErrors();
                }
            }
        }

        return array(
            'parts'  => $parts,
            'errors' => $errors
        );
    }

    protected function gsxLoadPartsList($params)
    {
        $errors = array();
        $html = '';

        if (!isset($params['serial']) || !$params['serial']) {
            $errors[] = 'Numéro de série absent';
        } else {
            $this->setSerial($params['serial']);
            $result = $this->gsxGetParts(array());

            if (!is_null($result['parts'])) {
                $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : 0);
                $suffixe = (isset($params['suffixe']) ? $params['suffixe'] : '');
                $html = $this->renderPartsList($result['parts'], $id_sav, $suffixe);
            } else {
                $errors = $result['errors'];
            }
        }

        return array(
            'errors' => $errors,
            'html'   => $html
        );
    }

    protected function gsxLoadIssueCodes($params)
    {
        $errors = array();
        $codes = array();

        $serial = (isset($params['serial']) ? $params['serial'] : 0);

        if (!$serial && isset($params['id_part']) && (int) $params['id_part']) {
            $part = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_ApplePart', (int) $params['id_part']);
            if (BimpObject::objectLoaded($part)) {
                $sav = $part->getParentInstance();
                if (BimpObject::objectLoaded($sav)) {
                    $equipment = $sav->getChildObject('equipment');
                    if (BimpObject::objectLoaded($equipment)) {
                        $serial = $equipment->getData('serial');
                    }
                }
            }
        }
        if (!$serial) {
            $errors[] = 'Numéro de série de l\'équipement absent';
        } else {
            $cache_key = 'gsx_issue_codes_' . $serial;
            if (!BimpCache::cacheExists($cache_key)) {
                if ($this->gsx_v2->logged) {
                    $result = $this->gsx_v2->getIssueCodesBySerial($serial);

                    if (is_array($result) && isset($result['componentIssues'])) {
                        foreach ($result['componentIssues'] as $categ) {
                            $codes[$categ['componentCode']] = array(
                                'label'  => $categ['componentDescription'],
                                'issues' => array()
                            );
                            foreach ($categ['issues'] as $issue) {
                                $codes[$categ['componentCode']]['issues'][$issue['code']] = $issue['description'];
                            }
                        }
                        BimpCache::$cache[$cache_key] = $codes;
                    } else {
                        $errors = $this->gsx_v2->getErrors();
                    }
                }
            }
        }

        return array(
            'errors' => $errors,
            'codes'  => $codes
        );
    }

    protected function gsx_loadAddIssueForm($params)
    {
        $errors = array();
        $html = '';
        $form_id = '';

        $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : 0);

        if (!$id_sav) {
            $errors[] = 'ID du SAV absent';
        } else {
            $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);

            if (!BimpObject::objectLoaded($sav)) {
                $errors[] = 'Le SAV d\'ID ' . $id_sav . ' n\'existe pas';
            } else {
                $serial = $sav->getSerial();

                if (!$serial) {
                    $errors[] = 'Aucun numéro de série trouvé pour le SAV #' . $id_sav;
                } else {
                    $result = $this->gsxLoadIssueCodes(array(
                        'serial' => $serial
                    ));

                    if (count($result['errors'])) {
                        $errors = $result['errors'];
                    } else {
                        $issue = BimpObject::getInstance('bimpsupport', 'BS_Issue');
                        $issue->set('id_sav', $id_sav);
                        $form = new BC_Form($issue, $id_sav, 'default', 1, true);
                        $form->setValues(array(
                            'fields' => array(
                                'serial' => $serial
                            )
                        ));
                        $html = $form->renderHtml();
                        $form_id = $form->identifier;
                    }
                }
            }
        }

        return array(
            'errors'  => $errors,
            'html'    => $html,
            'form_id' => $form_id
        );
    }

    protected function gsx_loadAddPartsForm($params)
    {
        $errors = array();
        $html = '';
        $form_id = '';

        $id_issue = (isset($params['id_issue']) ? (int) $params['id_issue'] : 0);

        if (!$id_issue) {
            $errors[] = 'ID du problème composant absent';
        } else {
            $issue = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Issue', $id_issue);
            if (!BimpObject::objectLoaded($issue)) {
                $errors[] = 'Le prodblème composant d\'ID ' . $id_issue . ' n\'existe pas';
            } else {
                $sav = $issue->getParentInstance();
                if (!BimpObject::objectLoaded($sav)) {
                    $errors[] = 'ID du SAV absent';
                } else {
                    $serial = $sav->getSerial();

                    if (!$serial) {
                        $errors[] = 'Aucun numéro de série trouvé pour le SAV #' . $sav->id;
                    } else {
                        $this->setSerial($params['serial']);
                        $result = $this->gsxGetParts(array(
                            'serial'   => $serial,
                            'id_issue' => $id_issue
                        ));

                        if (!is_null($result['parts'])) {
                            $html = $this->renderPartsList($result['parts'], $sav->id, '_issue_' . $id_issue);
                        } else {
                            $errors = $result['errors'];
                        }
                    }
                }
            }
        }

        return array(
            'errors'  => $errors,
            'html'    => $html,
            'form_id' => $form_id
        );
    }

    // Equipement:

    protected function gsxGetEquipmentInfos($params)
    {
        $errors = array();

        $id_equipment = (isset($params['id_equipment']) ? (int) $params['id_equipment'] : 0);
        $serial = '';

        if ($id_equipment) {
            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
            if (!BimpObject::objectLoaded($equipment)) {
                $errors[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas';
            } else {
                $serial = $equipment->getData('serial');
            }
        } else {
            $serial = (isset($params['serial']) ? $params['serial'] : '');
            if (!$serial) {
                $errors[] = 'Numéro de série absent';
            }
        }

        $data = array(
            'product_label'     => '',
            'date_purchase'     => '',
            'date_warranty_end' => '',
            'warranty_type'     => '',
            'warning'           => ''
        );

        if ($serial) {
            $result = $this->gsx_v2->productDetailsBySerial($serial);
            if (is_array($result)) {
                if (isset($result['device']['productDescription'])) {
                    $data['product_label'] = $result['device']['productDescription'];
                }

                if (isset($result['device']['warrantyInfo']['warrantyStatusDescription'])) {
                    $data['warranty_type'] = $result['device']['warrantyInfo']['warrantyStatusDescription'];
                }

                if (isset($result['device']['warrantyInfo']['coverageEndDate']) && (string) $result['device']['warrantyInfo']['coverageEndDate']) {
                    $dt = new DateTime($result['device']['warrantyInfo']['coverageEndDate']);
                    $data['date_warranty_end'] = $dt->format('Y-m-d H:i:s');
                }
                if (isset($result['device']['warrantyInfo']['purchaseDate']) && (string) $result['device']['warrantyInfo']['purchaseDate']) {
                    $dt = new DateTime($result['device']['warrantyInfo']['purchaseDate']);
                    $data['date_purchase'] = $dt->format('Y-m-d H:i:s');
                }

                // $data['warning'] à traiter (Utiliser $result['device']['activationDetails']['unlocked'] ?) 
            }
        }

        return array(
            'errors' => $errors,
            'data'   => $data
        );
    }

    protected function gsxGetEquipmentWarrantyInfos($params)
    {
        $errors = array();
        $html = '';

        $id_equipment = (isset($params['id_equipment']) ? (int) $params['id_equipment'] : 0);
        $serial = '';

        if ($id_equipment) {
            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
            if (!BimpObject::objectLoaded($equipment)) {
                $errors[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas';
            } else {
                $serial = $equipment->getData('serial');
            }
        } else {
            $serial = (isset($params['serial']) ? $params['serial'] : '');
            if (!$serial) {
                $errors[] = 'Numéro de série absent';
            }
        }

        if ($serial) {
            $data = $this->gsx_v2->productDetailsBySerial($serial);
            if (is_array($data)) {
                if (isset($data['device']['warrantyInfo']['warrantyStatusDescription'])) {
                    $html .= 'Garantie: <span class="bold">' . $data['device']['warrantyInfo']['warrantyStatusDescription'] . '</span><br/>';
                } else {
                    $html .= '<span class="danger">Statut de la garantie inconnu</span><br/>';
                }

                if (isset($data['device']['warrantyInfo']['daysRemaining'])) {
                    $html .= 'Jours restants: <span class="bold">' . $data['device']['warrantyInfo']['daysRemaining'] . '</span><br/>';
                }
            }
        }

        return array(
            'errors' => $errors,
            'html'   => $html
        );
    }

    // Gestion des réparations: 

    protected function gsxLoadSavGsxView($params)
    {
        $errors = array();
        $warnings = array();
        $html = '';

        $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : 0);
        $serial = (isset($params['serial']) ? $params['serial'] : '');
        $sav = null;

        if (!$id_sav) {
            $errors[] = 'ID du SAV absent';
        } else {
            $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
            if (!BimpObject::objectLoaded($sav)) {
                $errors[] = 'Le SAV d\'ID ' . $id_sav . ' n\'existe pas';
            }
        }

        if (!$serial) {
            $errors[] = 'Numéro de série absent';
        }

        if (!count($errors)) {
            $html = $this->renderSavGsxView($sav, $serial, $errors, $warnings);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'html'     => $html
        );
    }

    protected function gsxLoadSavRepairs($params)
    {
        $errors = array();
        $warnings = array();
        $html = '';

        $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : 0);

        if (!$id_sav) {
            $errors[] = 'ID du SAV absent';
        } else {
            $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
            if (!BimpObject::objectLoaded($sav)) {
                $errors[] = 'Le SAV d\'ID ' . $id_sav . ' n\'existe pas';
            } else {
                $html = $this->renderRepairs($sav);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'html'     => $html
        );
    }

    protected function gsxFindRepairsToImport($params)
    {
        $errors = array();
        $warnings = array();

        $html = '';

        $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : 0);
        $identifier = (isset($params['identifier']) ? $params['identifier'] : '');
        $identifier_type = (isset($params['identifier_type']) ? $params['identifier_type'] : '');

        if (!$id_sav) {
            $errors[] = 'ID du SAV absent';
        }

        if (!$identifier) {
            $errors[] = 'Aucun identifiant renseigné';
        }

        if (!$identifier_type) {
            $errors[] = 'Type de l\'identifiant absent';
        }

        if (!count($errors)) {
            if ($this->gsx_v2->logged) {
                $data = $this->gsx_v2->repairSummaryByIdentifier($identifier, $identifier_type);
                if (is_array($data) && !empty($data)) {
                    if (isset($data['totalNumberOfRecords']) && (int) $data['totalNumberOfRecords'] > 0) {
                        $html .= BimpRender::renderAlerts($data['totalNumberOfRecords'] . ' réparation(s) trouvée(s)', 'info');

                        $html .= '<table class="bimp_list_table">';
                        $html .= '<thead>';
                        $html .= '<th></th>';
                        $html .= '<th>Type</th>';
                        $html .= '<th>ID</th>';
                        $html .= '<th>Créée le</th>';
                        $html .= '<th>Numéro de commande</th>';
                        $html .= '<th>Satut</th>';
                        $html .= '</thead>';

                        $html .= '<tbody>';

                        foreach ($data['repairs'] as $repair_data) {
                            // Check de l\'existence de la réparation: 
                            $repair = BimpCache::findBimpObjectInstance('bimpapple', 'GSX_Repair', array(
                                        'id_sav'        => $id_sav,
                                        'repair_number' => $repair_data['repairId'],
                                        'repair_type'   => $repair_data['repairType']
                            ));

                            $dt = new DateTime($repair_data['repairCreateDate']);

                            $html .= '<tr class="repairRow"';
                            $html .= BimpRender::displayTagData(array(
                                        'repair_number' => $repair_data['repairId'],
                                        'repair_type'   => $repair_data['repairType']
                            ));
                            $html .= '>';
                            $html .= '<td>';
                            if (!BimpObject::objectLoaded($repair)) {
                                $html .= '<input type="checkbox" value="' . $repair_data['repairId'] . '" name="repairs[]" checked="1"/>';
                            }
                            $html .= '</td>';
                            $html .= '<td>' . GSX_Const::$repair_types[$repair_data['repairType']] . '</td>';
                            $html .= '<td>' . $repair_data['repairId'];
                            if (BimpObject::objectLoaded($repair)) {
                                $html .= '<br/>';
                                $html .= '<span class="warning">(Réparation déjà enregistrée)</span>';
                            }
                            $html .= '</td>';
                            $html .= '<td>' . $dt->format('d / m / Y H:i') . '</td>';
                            $html .= '<td>' . $repair_data['purchaseOrderNumber'] . '</td>';
                            $html .= '<td>' . $repair_data['repairStatusDescription'] . '</td>';
                            $html .= '</tr>';
                        }

                        $html .= '</tbody>';
                        $html .= '</table>';
                        $html .= '<div class="ajaxResults"></div>';
                    } else {
                        BimpObject::loadClass('bimpapple', 'GSX_Repair');
                        $warnings[] = 'Aucune réparation trouvée pour l\'identifiant "' . $identifier . '" de type "' . GSX_Const::$importIdentifierTypes[$identifier_type] . '"';
                    }
                } else {
                    $html .= $this->gsx_v2->displayErrors();
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'html'     => $html
        );
    }

    protected function gsxImportRepairs($params)
    {
        $errors = array();
        $warnings = array();

        $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : 0);
        $repairs = (isset($params['repairs']) ? $params['repairs'] : array());

        if (!$id_sav) {
            $errors[] = 'ID du SAV absent';
        } else {
            $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
            if (!BimpObject::objectLoaded($sav)) {
                $errors[] = 'Le SAV d\'ID ' . $id_sav . ' n\'existe pas';
            }
        }

        if (!count($repairs)) {
            $errors[] = 'Aucune réparation sélectionnée';
        }

        if (!count($errors)) {
            $serial = (string) $sav->getSerial();

            foreach ($repairs as $repair_data) {
                // check si la répa existe déjà: 
                $repair = BimpCache::findBimpObjectInstance('bimpapple', 'GSX_Repair', array(
                            'id_sav'        => $id_sav,
                            'repair_number' => $repair_data['repair_number'],
                            'repair_type'   => $repair_data['repair_type']
                ));

                if (BimpObject::objectLoaded($repair)) {
                    $warnings[] = 'La répation #' . $repair_data['repair_number'] . ' est déjà enregistrée pour ce SAV';
                    continue;
                }

                $repair = BimpObject::getInstance('bimpapple', 'GSX_Repair');
                $repair->setGSX($this->gsx_v2);

                $rep_warnings = array();
                $rep_errors = $repair->validateArray(array(
                    'serial'        => $serial,
                    'id_sav'        => $id_sav,
                    'repair_number' => $repair_data['repair_number'],
                    'repair_type'   => $repair_data['repair_type']
                ));

                if (!count($rep_errors)) {
                    $rep_errors = $repair->create($rep_warnings, true);
                }

                if (count($rep_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($rep_warnings, 'Erreurs suite à la création de la réparation #' . $repair_data['repair_number']);
                }

                if (count($rep_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($rep_errors, 'Echec de la création de la réparation #' . $repair_data['repair_number']);
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    protected function gsxRepairAction($params)
    {
        $errors = array();
        $warnings = array();
        $modal_html = '';

        $id_repair = (isset($params['id_repair']) ? (int) $params['id_repair'] : 0);
        $action = (isset($params['action']) ? $params['action'] : '');

        if (!$action) {
            $errors[] = 'Nom de l\'action a effectuée absent';
        } elseif (!$id_repair) {
            $errors[] = 'ID de la réparation absent';
        } else {
            $repair = BimpCache::getBimpObjectInstance('bimpapple', 'GSX_Repair', $id_repair);
            if (!BimpObject::objectLoaded($repair)) {
                $errors[] = 'La réparation d\'ID ' . $id_repair . ' n\'existe pas';
            } else {
                switch ($action) {
                    case 'readyForPickup':
                        $errors = $repair->updateStatus('RFPU', $warnings);
                        break;
                }
            }
        }

        return array(
            'errors'     => $errors,
            'warnings'   => $warnings,
            'modal_html' => $modal_html
        );
    }

    // Méthodes GSX V1:

    public function initGsx()
    {
        if (is_null($this->gsx)) {
            $this->gsx = new GSX($this->isIphone);
            return array_merge($this->gsx->errors['init'], $this->gsx->errors['soap']);
        }

        return array();
    }

    public function isIphone($serial)
    {
        if (preg_match('/^[0-9]{15,16}$/', $serial))
            return true;
        return false;
    }

    // Requêtes GSX V1:

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

                $repair = BimpCache::getBimpObjectInstance('bimpapple', 'GSX_Repair', $id_repair);
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
                                $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', (int) $id_sav);
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

    // Traitements: 

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
        $this->repairs = array();

        if ((int) $id_sav) {
            $this->repairs = BimpCache::getBimpObjectObjects('bimpapple', 'GSX_Repair', array(
                        'id_sav' => (int) $id_sav
            ));

            if ($this->use_gsx_v2) {
                foreach ($this->repairs as $repair) {
                    $repair->setGSX($this->gsx_v2);
                }
            }
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

    // Rendus HTML GSX V2:

    public function renderSavGsxView($sav, $serial, &$errors = array(), &$warnings = array())
    {
        $html = '';

        $this->setSerial($serial);

        if ($this->gsx_v2->logged) {
            $lookUpContent = '';
            $data = $this->gsx_v2->productDetailsBySerial($serial);

            if (is_array($data) && !empty($data) && isset($data['device'])) {
                $data = $data['device'];

                if (isset($data['identifiers']['serial'])) {
                    $this->serial2 = $data['identifiers']['serial'];
                }

                // Traitement des alertes: 
                if (isset($data['messages']) && is_array($data['messages'])) {
                    foreach ($data['messages'] as $msg) {
                        if (isset($msg['type']) && $msg['type'] === 'WARNING') {
                            if (isset($msg['message']) && (string) $msg['message']) {
                                $lookUpContent .= BimpRender::renderAlerts($msg['message'], 'warning');
                            }
                        }
                    }
                }

                $lookUpContent .= '<div class="row">';

                // Bloc infos produit: 
                $infosContent = '';
                $infosContent .= '<table class="bimp_list_table">';
                $infosContent .= '<tbody>';

                foreach (array(
            'productDescription'                    => 'Produit',
            'description'                           => 'Description',
            'activationDetails/productVersion'      => 'Version',
            'warrantyInfo/personalized'             => 'Unité personnalisée',
            'identifiers/serial'                    => 'N° de série',
            'identifiers/imei'                      => 'N° IMEI',
            'identifiers/imei2'                     => 'N°IMEI2',
            'identifiers/meid'                      => 'N° MEID',
            'configDescription'                     => 'Configuration',
            'configCode'                            => 'Code Configuration',
            'activationDetails/macAddress'          => 'Adresse MAC',
            'activationDetails/firstActivationDate' => 'Date de première activation',
            'activationDetails/unlocked'            => 'Débloqué',
            'activationDetails/unlockDate'          => 'Date de déblocage',
            'warrantyInfo/purchaseDate'             => 'Date d\'achat',
            'warrantyInfo/purchaseCountry'          => 'Pays d\'achat',
            'warrantyInfo/registrationDate'         => 'Date d\'enregistrement',
                ) as $path => $label) {
                    $value = BimpTools::getArrayValueFromPath($data, $path, true);
                    if (!is_null($value)) {
                        $infosContent .= '<tr><th>' . $label . '</th><td>' . $value . '</td></tr>';
                    }
                }

                $infosContent .= '</tbody>';
                $infosContent .= '</table>';

                $lookUpContent .= '<div class="col-sm-12 col-md-6 col-lg-6">';
                $lookUpContent .= BimpRender::renderPanel('Infos produit', $infosContent, '', array(
                            'panel_id' => 'product_infos',
                            'type'     => 'secondary',
                            'icon'     => 'fas_info',
                            'foldable' => true
                ));

                $lookUpContent .= '</div>';

                // Bloc garantie: 
                $warrantyContent = '';
                $warrantyContent .= '<table class="bimp_list_table">';
                $warrantyContent .= '<tbody>';
                foreach (array(
            'warrantyInfo/warrantyStatusDescription'                  => 'Garantie',
            'warrantyInfo/warrantyStatusCode'                         => 'Code statut garantie',
            'warrantyInfo/limitedWarranty'                            => 'Garantie limitée',
            'warrantyInfo/coverageStartDate'                          => 'Date de début de couverture',
            'warrantyInfo/coverageEndDate'                            => 'Date de fin de couverture',
            'warrantyInfo/daysRemaining'                              => 'Nombre de jours restants',
            'warrantyInfo/partCovered'                                => 'Composants couverts',
            'warrantyInfo/onsiteCoverage'                             => 'Couverture "onsite"',
            'warrantyInfo/onsiteStartDate'                            => 'Date de début de couverture "onsite"',
            'warrantyInfo/onsiteEndDate'                              => 'Date de fin de couverture "onsite',
            'warrantyInfo/contractType'                               => 'Type de contrat',
            'warrantyInfo/contractCoverageStartDate'                  => 'Date de début du contrat',
            'warrantyInfo/contractCoverageEndDate'                    => 'Date de fin du contrat',
            'warrantyInfo/appleCarePlusCoverageAvailabilityIndicator' => 'Indicateur de couverture "AppleCare+"'
                ) as $path => $label) {
                    $value = BimpTools::getArrayValueFromPath($data, $path, true);
                    if (!is_null($value)) {
                        $warrantyContent .= '<tr><th>' . $label . '</th><td>' . $value . '</td></tr>';
                    }
                }
                $warrantyContent .= '</tbody>';
                $warrantyContent .= '</table>';

                $lookUpContent .= '<div class="col-sm-12 col-md-6 col-lg-6">';
                $lookUpContent .= BimpRender::renderPanel('Couverture', $warrantyContent, '', array(
                            'panel_id' => 'product_warranty',
                            'type'     => 'secondary',
                            'icon'     => 'fas_medkit',
                            'foldable' => true
                ));
                $lookUpContent .= '</div>';

                $lookUpContent .= '</div>';

                $html .= BimpRender::renderPanel($data['productDescription'], $lookUpContent, '', array(
                            'icon'     => 'fas_desktop',
                            'type'     => 'secondary',
                            'foldable' => true
                ));
            } else {
                $html .= BimpRender::renderAlerts('Aucune données reçues pour le numéro de série "' . $this->serial . '"');
                $html .= $this->gsx_v2->displayErrors();
            }

            $html .= BimpRender::renderPanel('Réparations', $this->renderRepairs($sav), '', array(
                        'panel_id' => 'sav_repairs',
                        'type'     => 'secondary',
                        'icon'     => 'fas_tools',
                        'foldable' => true
            ));

            $html .= $sav->renderApplePartsList('gsx');


            $html .= $this->renderLoadPartsButton($sav, $serial, "deux");
        }

        return $html;
    }

    // Rendus HTML GSX V1: 

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
            $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', (int) $id_sav);
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
            $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', (int) $id_sav);

            if ($sav->isLoaded()) {
                $idUser = (int) $sav->getData('id_user_tech');
                if (!$idUser) {
                    $idUser = $user->id;
                }
                $tech = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $idUser);
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

    // Rendus HTML:

    public function renderLoadPartsButton(BS_SAV $sav, $serial = null, $suffixe = "")
    {
        if ($this->use_gsx_v2) {
            return '';
        }

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

        $serial = $sav->getSerial();
        $this->setSerial($serial);

        $html .= '<div class="buttonsContainer align-right">';

        $onclick = '';
        if ($this->use_gsx_v2) {
            $onclick = '$(\'#findRepairsToImportForm\').slideDown(250);';
        } else {
            $repair_instance = BimpObject::getInstance('bimpapple', 'GSX_Repair');
            $onclick = $repair_instance->getJsActionOnclick('importRepair', array(
                'id_sav'             => (int) $sav->id,
                'import_number'      => $serial,
                'import_number_type' => ($this->isIphone ? 'imeiNumber' : 'serialNumber')
                    ), array(
                'form_name'        => 'import',
                'success_callback' => 'function() {reloadRepairsViews(' . $sav->id . ')}'
            ));
        }

        $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
        $html .= '<i class="fas fa5-cloud-download-alt iconLeft"></i>Importer depuis GSX</button>';

        if ($this->use_gsx_v2) {
            $onclick = 'gsx_loadRequestModalForm($(this), \'repairCreate\', {';
            $onclick .= 'id_sav: ' . $sav->id . ', ';
            $onclick .= 'serial: \'' . $serial . '\'';
            $onclick .= '}, {';
            $onclick .= '});';
        } else {
            $onclick = '$(\'#createRepairForm\').slideDown(250);';
        }
        $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
        $html .= '<i class="fa fa-plus-circle iconLeft"></i>Créer une nouvelle réparation</button>';

        $html .= '</div>';

        if ($this->use_gsx_v2) {
            $html .= '<div id="findRepairsToImportForm">';
            $html .= '<div class="findRepairsToImportFormContent">';

            $buttons = array();
            $buttons[] = BimpRender::renderButton(array(
                        'label'       => 'Annuler',
                        'icon_before' => 'times',
                        'classes'     => array('btn btn-danger'),
                        'attr'        => array(
                            'onclick' => '$(\'#findRepairsToImportForm\').slideUp(250);'
            )));
            $buttons[] = BimpRender::renderButton(array(
                        'label'      => 'Valider',
                        'icon_after' => 'arrow-circle-right',
                        'classes'    => array('btn btn-primary'),
                        'attr'       => array(
                            'onclick' => 'gsx_findRepairsToImport($(this), ' . $sav->id . ')'
            )));

            $html .= BimpRender::renderFreeForm(array(
                        array(
                            'label' => 'Identifiant',
                            'input' => BimpInput::renderInput('text', 'identifier', $serial)
                        ),
                        array(
                            'label' => 'De type',
                            'input' => BimpInput::renderInput('select', 'identifier_type', 'serial', array(
                                'options' => GSX_Const::$importIdentifierTypes
                            ))
                        )
                            ), $buttons, 'Recherche de réparation à importer');

            $html .= '</div>';
            $html .= '</div>';
        } else {
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
        }

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

    public function renderPartsList($parts, $id_sav = null, $sufixe = '')
    {
        $add_btn = false;
        if (!is_null($id_sav) && !$this->use_gsx_v2) {
            $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
            if (!is_null($sav) && $sav->isLoaded()) {
//                if ($sav->isPropalEditable()) {
                $add_btn = true;
//                }
            }
        }

//        return '<pre>' . print_r($parts, true) . '</pre>';
        $html = '';
        if (is_array($parts)) {
            if (empty($parts)) {
                $html .= BimpRender::renderAlerts('Aucun composant compatible trouvé pour le numéro de série "' . $this->serial . '"', 'warning');
            } else {
                if ($this->use_gsx_v2) {
                    $html .= '<div id="partsListContainer' . $sufixe . '" class="partsListContainer">';
                }
                $html .= '<div class="partsSearchContainer">';
                $html .= '<div class="searchBloc">';
                $html .= '<label for="keywordFilter">Filtrer par mots-clés: </label>';
                $html .= '<input type="text max = "80" name = "keywordFilter" class = "keywordFilter"/>';
                $html .= '<select class = "keywordFilterType">';
                $types = array('name' => 'Nom', 'eeeCode' => 'eeeCode', 'num' => 'Référence', 'type' => 'Type'); //, 'price' => 'Prix');
                foreach ($types as $key => $type) {
                    $html .= '<option value = "' . $key . '">' . $type . '</option>';
                }
                $html .= '</select>';
                $html .= '<span class = "btn btn-default addKeywordFilter" onclick = "PM[\'parts' . $sufixe . '\'].addKeywordFilter()"><i class = "fa fa-plus-circle iconLeft"></i>Ajouter</span>';
                $html .= '</div>';

                $html .= '<div class = "searchBloc">';
                $html .= '<label for = "searchPartInput">Recherche par référence: </label>';
                $html .= '<input type = "text" name = "searchPartInput" class = "searchPartInput" size = "12" maxlength = "24"/>';
                $html .= '<span class = "btn btn-default searchPartSubmit" onclick = "PM[\'parts' . $sufixe . '\'].searchPartByNum()"><i class = "fa fa-search iconLeft"></i>Rechercher</span>';
                $html .= '</div>';

                $html .= '<div class = "curKeywords"></div>';
                $html .= '</div>';

                $html .= '<div class = "partsSearchResult"></div>';


                $groups = array();

                foreach ($parts as $part) {
                    $group = '';
                    if ($this->use_gsx_v2) {
                        $group = (isset($part['typeCode']) ? addslashes($part['typeCode']) : '');

                        if (!isset($groups[$group])) {
                            $label = (isset(self::$componentsTypesV2[$group]) ? self::$componentsTypesV2[$group] : ($group ? $part['typeDescription'] : 'Général'));
                            $groups[$group] = array(
                                'label' => $label,
                                'parts' => array()
                            );
                        }
                    } else {
                        $group = (isset($part['componentCode']) ? addslashes($part['componentCode']) : '');
                        if (!$group || $group === ' ') {
                            $group = 0;
                        }

                        if (!isset($groups[$group])) {
                            $groups[$group] = array(
                                'label' => self::$componentsTypes[$group],
                                'parts' => array()
                            );
                        }
                    }
                    $groups[$group]['parts'][] = $part;
                }

                $html .= '<div id = "partsList">';

                $headers = '<thead>';
                if ($this->use_gsx_v2) {
                    $headers .= '<th></th>';
                }
                $headers .= '<th style = "min-width: 250px">Nom</th>';
                $headers .= '<th style = "min-width: 80px">Ref.</th>';
                $headers .= '<th style = "min-width: 80px">Nouvelle Ref.</th>';
                $headers .= '<th style = "min-width: 80px">eeeCode(s)</th>';
                $headers .= '<th style = "min-width: 80px">Type</th>';
                $headers .= '<th style = "min-width: 80px">Prix commande</th>';
                $headers .= '<th style = "min-width: 80px">Prix stock</th>';
                $headers .= '<th>Prix spéciaux</th>';

                if (!$this->use_gsx_v2) {
                    $headers .= '<th style = "width: 30px; text-align: center"></th>';
                }

                $headers .= '</thead>';

                foreach ($groups as $group_code => $group) {
                    $content = '<table id = "partGroup_' . $group_code . '" class = "bimp_list_table partGroup">';
                    $content .= $headers;
                    $content .= '<tbody>';

                    $i = 1;

                    $odd = true;
                    foreach ($group['parts'] as $part) {
                        if ($this->use_gsx_v2) {
                            $code = (isset($part['componentCode']) ? addslashes($part['componentCode']) : '');
                            $eeeCode = '';
                            if (isset($part['eeeCodes'])) {
                                foreach ($part['eeeCodes'] as $eeeC) {
                                    $eeeCode .= ($eeeCode ? ', ' : '') . $eeeC;
                                }
                            }
                            $name = (isset($part['description']) ? addslashes(str_replace(" ", "", $part['description'])) : '');
                            $num = (isset($part['number']) ? addslashes($part['number']) : '');
                            $partNewNumber = (isset($part['substitutePartNumber']) ? addslashes($part['substitutePartNumber']) : '');
                            $exchange_price = (isset($part['exchangePrice']) ? addslashes($part['exchangePrice']) : 0);
                            $stock_price = (isset($part['stockPrice']) ? addslashes($part['stockPrice']) : 0);
                            $type = (isset($part['typeCode']) ? addslashes($part['typeCode']) : '');

                            $price_options = array();
                            foreach ($part['pricingOptions'] as $price_option) {
                                if (isset($price_option['code'])) {
                                    $price_options[$price_option['code']] = array(
                                        'price'       => $price_option['price'],
                                        'description' => $price_option['description']
                                    );
                                }
                            }
                        } else {
                            $partNewNumber = '';
                            if (isset($part['originalPartNumber']) && $part['originalPartNumber'] != '') {
                                $partNewNumber = $part['partNumber'];
                                $part['partNumber'] = $part['originalPartNumber'];
                            }

                            $code = (isset($part['componentCode']) ? addslashes($part['componentCode']) : '');
                            $eeeCode = (isset($part['eeeCode']) ? addslashes($part['eeeCode']) : '');
                            $name = (isset($part['partDescription']) ? addslashes(str_replace(" ", "", $part['partDescription'])) : '');
                            $num = (isset($part['partNumber']) ? addslashes($part['partNumber']) : '');
                            $exchange_price = (isset($part['exchangePrice']) ? addslashes($part['exchangePrice']) : 0);
                            $stock_price = (isset($part['stockPrice']) ? addslashes($part['stockPrice']) : 0);
                            $type = (isset($part['partType']) ? addslashes($part['partType']) : '');

                            $price_options = array();
                            if (isset($part['pricingOptions']['pricingOption'])) {
                                if (is_array($part['pricingOptions']['pricingOption']) && isset($part['pricingOptions']['pricingOption']['code'])) {
                                    $price_options = array(
                                        $part['pricingOptions']['pricingOption']['code'] => array(
                                            'price'       => $part['pricingOptions']['pricingOption']['price'],
                                            'description' => $part['pricingOptions']['pricingOption']['description']
                                        )
                                    );
                                } else {
                                    foreach ($part['pricingOptions']['pricingOption'] as $price_option) {
                                        if (isset($price_option['code'])) {
                                            $price_options[$price_option['code']] = array(
                                                'price'       => $price_option['price'],
                                                'description' => $price_option['description']
                                            );
                                        }
                                    }
                                }
                            }
                        }

                        $content .= '<tr class = "partRow_' . $i . ' partRow ' . ($odd ? 'odd' : 'even') . '"';
                        $content .= ' data-code = "' . $code . '"';
                        $content .= ' data-eee_code = "' . $eeeCode . '"';
                        $content .= ' data-name = "' . $name . '"';
                        $content .= ' data-num = "' . $num . '"';
                        $content .= ' data-newNum = "' . $partNewNumber . '"';
                        $content .= ' data-exchange_price = "' . $exchange_price . '"';
                        $content .= ' data-stock_price = "' . $stock_price . '"';
                        $content .= ' data-type = "' . $type . '"';
                        $content .= ' data-price_options = "' . (count($price_options) ? htmlentities(json_encode($price_options)) : '') . '"';
                        $content .= '>';

                        if ($this->use_gsx_v2) {
                            $content .= '<td style = "width: 30px; text-align: center">';
                            $content .= '<input type = "checkbox" name = "parts[]" value = "' . $num . '"/>';
                            $content .= '</td>';
                        }
                        $content .= '<td>' . $name . '</td>';
                        $content .= '<td>' . $num . '</td>';
                        $content .= '<td>' . $partNewNumber . '</td>';
                        $content .= '<td>' . $eeeCode . '</td>';
                        $content .= '<td>' . $type . '</td>';
                        $content .= '<td>' . $exchange_price . '</td>';
                        $content .= '<td>' . $stock_price . '</td>';
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

                    $title = $group['label'] . ' ' . '<span class = "badge partsNbr">' . count($group['parts']) . '</span>';
                    $html .= BimpRender::renderPanel($title, $content, '', array(
                                'type'        => 'default',
                                'panel_class' => 'parts_group_panel',
                                'foldable'    => true,
                                'open'        => false
                    ));
                }

                if ($this->use_gsx_v2) {
                    $html .= '</div>';
                }
            }
        } elseif (!$this->use_gsx_v2) {
            $html .= BimpRender::renderAlerts('Echec de la récupération de la liste des composants compatibles depuis la plateforme GSX');
            $html .= $this->gsx->getGSXErrorsHtml();
        }
        return $html;
    }

    // GSX Ajax V2:

    protected function ajaxProcessGsxRequest()
    {
        if ((int) BimpTools::getValue('gsx_requestForm', 0)) {
            $method = 'gsxProcessRequestForm';
            $params = array(
                'requestName' => BimpTools::getValue('gsx_requestName', ''),
                'serial'      => BimpTools::getValue('gsx_serial', ''),
                'id_sav'      => BimpTools::getValue('gsx_id_sav', 0),
                'id_repair'   => BimpTools::getValue('gsx_id_repair', 0)
            );
        } else {
            $method = BimpTools::getValue('gsx_method', '');
            $params = BimpTools::getValue('gsx_params', array());
        }

        if ($method) {
            $result = $this->gsxRequest($method, $params);

            if (!is_null($this->gsx_v2) && !$this->gsx_v2->logged) {
                return array(
                    'errors'        => array(),
                    'warnings'      => array(),
                    'gsx_no_logged' => 1
                );
            }
            return $result;
        }

        return array(
            'errors' => 'Nom de la fonction GSX absent'
        );
    }

    // GSX Ajax V1:

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

        $repair = BimpCache::getBimpObjectInstance('bimpapple', 'GSX_Repair', $id_repair);
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
            $repair = BimpCache::getBimpObjectInstance('bimpapple', 'GSX_Repair', $id_repair);

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
            $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
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
            $this->setSerial($serial);
            $parts = $this->getPartsListArray(false);
            $html = $this->renderPartsList($parts, $id_sav, $sufixe);
        }
        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    // Divers: 
    public static function dateAppleToDate($date)
    {
        $garantieT = explode("/", $date);
        if (isset($garantieT[2]))
            return $garantieT[0] . "/" . $garantieT[1] . "/20" . $garantieT[2];
        else
            return "";
    }
}
