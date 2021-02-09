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
            return array();
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
        $button = null;

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

                if ($requestName === 'repairCreate') {
                    $button = array(
                        'label'   => BimpRender::renderIcon('fas_question-circle', 'iconLeft') . 'Tester éligibilité',
                        'onclick' => 'gsx_FetchRepairEligibility($(this))'
                    );
                }
            }
        }

        return array(
            'errors' => $errors,
            'title'  => $title,
            'html'   => $html,
            'button' => $button
        );
    }

    protected function gsxGetRequestFormValues($requestName, $params, &$errors = array())
    {
        $values = array();

        switch ($requestName) {
            case 'repairCreate':
                $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : 0);
                $serial = (isset($params['serial']) ? $params['serial'] : 0);
                $repairType = (isset($params['repairType']) ? $params['repairType'] : '');

                if (!$id_sav) {
                    $errors[] = 'ID du SAV absent 53';
                } else {
                    $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
                    if (!BimpObject::objectLoaded($sav)) {
                        $errors[] = 'Le SAV d\'ID ' . $id_sav . ' n\'existe pas';
                    }
                }

                if (!$serial) {
                    $errors[] = 'N° de série absent';
                }

                if (!$repairType) {
                    $errors[] = 'Type de réparation absent';
                }

                if (!count($errors)) {
                    if ((int) $sav->getData('id_propal')) {
                        GSX_Const::$sav_files = BimpCache::getObjectFilesArray('bimpcommercial', 'Bimp_Propal', (int) $sav->getData('id_propal'), false, true);
                    }

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

                    $values['repairType'] = $repairType;
                    $values['unitReceivedDateTime'] = date('Y-m-d') . ' 06:00:00';
                    $values['purchaseOrderNumber'] = $sav->getData('ref');
                    $values['techId'] = $tech->getData('apple_techid');
                    $phone = $tech->getData('office_phone');
                    if (is_null($phone) || !$phone) {
                        $phone = $tech->getData('user_mobile');
                    }
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
                            if (!in_array(BimpCache::getBdb()->getValue('c_typent', 'code', '`id` = ' . $id_typeent), array('TE_UNKNOWN', 'TE_PRIVATE', 'TE_OTHER'))) {
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
                            $company_name = '';
                            $firstname = '';
                            $lastname = '';

                            if ($is_company) {
                                $company_name = (string) $client_data['nom'];
                            } elseif (preg_match(('/^(.+) (.+)$/U'), $client_data['nom'], $matches)) {
                                $lastname = $matches[1];
                                $firstname = $matches[2];
                            } else {
                                $lastname = $client_data['nom'];
                            }

                            $values['customer'] = array(
                                'firstName'    => $firstname,
                                'lastName'     => $lastname,
                                'companyName'  => $company_name,
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

                        $n = 1;
                        foreach ($issues as $issue) {
                            $is_tier_parts = $issue->isTierPart();

                            if (!$is_tier_parts) {
                                $values['componentIssues'][] = array(
                                    'componentCode'   => $issue->getData('category_code'),
                                    'issueCode'       => $issue->getData('issue_code'),
                                    'reproducibility' => $issue->getData('reproducibility'),
                                    'priority'        => (string) $n,
                                    'type'            => $issue->getData('type'),
                                    'order'           => (string) $n,
                                );
                            }

                            $n++;

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

                                    if ($is_tier_parts) {
                                        $values['parts'][] = array(
                                            'part_label'     => $part->getData('label'),
                                            'number'         => $part->getData('part_number'),
                                            'pricingOption'  => $pricingOption,
                                            'componentIssue' => 'hidden'
                                        );
                                    } else {
                                        $part_used = (string) $part->getData('new_part_number');
                                        if (!$part_used) {
                                            $part_used = $part->getData('part_number');
                                        }
                                        $values['parts'][] = array(
                                            'part_label'     => $part->getData('label'),
                                            'number'         => $part->getData('part_number'),
                                            'partUsed'       => $part_used,
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

                    $values['questions'] = array(
                        'content' => $this->renderRepairQuestionsFormContent(array(
                            'id_sav'     => $id_sav,
                            'repairType' => $repairType
                                ), $errors)
                    );
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
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    protected function gsxRequestFormResultOverride($requestName, &$result, $params, &$warnings = array())
    {
        $errors = array();

        $sav = null;
        if (isset($params['id_sav'])) {
            $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', (int) $params['id_sav']);
        }

        // Traitement des fichiers: 
        $idx = BimpTools::getValue('attachments_nextIdx', 0);
        if ($idx) {
            $files = array();
            $sav_files_dir = '';
            if (BimpObject::objectLoaded($sav)) {
                $propal = $sav->getChildObject('propal');
                if (BimpObject::objectLoaded($propal)) {
                    $sav_files_dir = $propal->getFilesDir();
                }
            }

            for ($i = 1; $i < $idx; $i++) {
                if (isset($_POST['attachments_fileOrigin_' . $i]) && $_POST['attachments_fileOrigin_' . $i] === 'sav') {
                    // Fichier de la propale du SAV: 
                    if (!$sav_files_dir) {
                        return array('Impossible de déterminer le dossier pour les fichiers du SAV (SAV ou devis invalide)');
                    }

                    $id_file = (int) BimpTools::getValue('attachments_savFile_' . $i, 0);
                    if ($id_file) {
                        $fileObj = BimpCache::getBimpObjectInstance('bimpcore', 'BimpFile', $id_file);
                        if (BimpObject::objectLoaded($fileObj)) {
                            $file_name = $fileObj->getData('file_name') . '.' . $fileObj->getData('file_ext');
                            $file_path = $sav_files_dir . $file_name;
                            if (!file_exists($file_path)) {
                                $errors[] = 'Le fichier "' . $file_name . '" n\'existe pas';
                                continue;
                            }
                            $files[] = array(
                                'name' => $file_name,
                                'size' => filesize($file_path),
                                'path' => $file_path
                            );
                        } else {
                            $errors[] = 'Le fichier d\'ID ' . $id_file . ' n\'existe plus (Ajout de fichier #' . $i . ')';
                        }
                    } else {
                        $errors[] = 'Aucun fichier du SAV sélectionné pour l\'ajout de fichier #' . $i;
                    }
                } elseif (isset($_FILES['attachments_file_' . $i])) {
                    // Fichier uploadé: 
                    $files[] = array(
                        'name' => $_FILES['attachments_file_' . $i]['name'],
                        'size' => $_FILES['attachments_file_' . $i]['size'],
                        'path' => $_FILES['attachments_file_' . $i]['tmp_name']
                    );
                } else {
                    $errors[] = 'Aucun fichier sélectionné pour l\'ajout de fichier #' . $i;
                }
            }

            if (!empty($files)) {
                $serial = (isset($params['serial']) ? $params['serial'] : '');
                if (!$serial && BimpObject::objectLoaded($sav)) {
                    $serial = $sav->getSerial();
                }

                $uploadResult = $this->gsx_v2->filesUpload($serial, $files);
                if (!is_array($uploadResult) || empty($uploadResult)) {
                    return $this->gsx_v2->getErrors();
                } else {
                    if (!isset($result['attachments'])) {
                        $result['attachments'] = array();
                    }

                    $i = 0;
                    foreach ($uploadResult as $file_data) {
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
            case 'repairEligibility':
                if (isset($result['componentIssues']) && !empty($result['componentIssues'])) {
                    $i = 0;
                    foreach ($result['componentIssues'] as $key => $compIssue) {
                        $i++;
                        if (!isset($result['componentIssues'][$key]['order']) || !$result['componentIssues'][$key]['order']) {
                            $result['componentIssues'][$key]['order'] = $i;
                        }
                    }
                }

                if (isset($result['parts']) && !empty($result['parts'])) {
                    foreach ($result['parts'] as $key => $part) {
                        if (isset($part['componentIssue'])) {
                            if (!isset($part['componentIssue']['componentCode']) || !(string) $part['componentIssue']['componentCode']) {
                                unset($result['parts'][$key]['componentIssue']);
                            }
                        }
                    }
                }

                // Traitement des questions: 
                if (!isset($params['repairType']) && isset($result['repairType'])) {
                    $params['repairType'] = $result['repairType'];
                }
                $questions_result = $this->gsxProcessRepairQuestionsForm($params, $result);
                if (count($questions_result['errors'])) {
                    $errors[] = BimpTools::getMsgFromArray($questions_result['errors'], 'Erreurs lors du traitement des questions');
                }

                if ($requestName === 'repairCreate') {
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
                }
                break;
        }

        return $errors;
    }

    protected function gsxOnRequestSuccess($requestName, $response, $params)
    {
        $errors = array();
        $warnings = array();
        $success_callback = '';

        switch ($requestName) {
            case 'repairCreate':
//                echo 'Requête OK - Réponse:<pre>';
//                print_r($response);
//                echo '</pre>';

                $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : 0);
                if (!$id_sav) {
                    $errors[] = 'ID du SAV absent 54';
                } else {
                    $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
                    if (!BimpObject::objectLoaded($sav)) {
                        $errors[] = 'Le SAV d\'ID ' . $id_sav . ' n\'existe pas';
                    }
                }

                if (!count($errors)) {
                    $repair = BimpObject::getInstance('bimpapple', 'GSX_Repair');
                    $errors = $repair->processRepairRequestOutcome($response, $warnings);
                }

                if (!count($errors) && !isset($response['repairId'])) {
                    $errors[] = 'ID de la réparation non reçu';
                }

                if (!count($errors)) {


                    if (!count($errors)) {
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
                            $success_callback = 'bimpModal.clearAllContents();';
                            $success_callback .= 'setTimeout(function() {' . $sav->getJsActionOnclick('attentePiece', array(), array('form_name' => 'send_msg')) . '}, 1000);';
                            $success_callback .= ';reloadRepairsViews(' . $id_sav . ');';
                        }
                    }
                }
                break;
        }
        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
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
                $is_tier_part = (!(string) $issue->getData('category_code'));

                $result = $this->gsx_v2->partsSummaryBySerialAndIssue($serial, $issue);

                if (isset($result['parts'])) {
                    $parts = array();
                    foreach ($result['parts'] as $part) {
                        if ($is_tier_part && $part['typeCode'] !== 'CNTC') {
                            continue;
                        } elseif (!$is_tier_part && $part['typeCode'] === 'CNTC') {
                            continue;
                        }

                        if (isset($params['partNumberAsKey']) && (int) $params['partNumberAsKey']) {
                            $parts[$part['partNumber']] = $part;
                        } else {
                            $parts[] = $part;
                        }
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

            if (!is_null($result['parts']) && empty($result['errors'])) {
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

                            if (isset($categ['issues']) && is_array($categ['issues'])) {
                                foreach ($categ['issues'] as $issue) {
                                    $codes[$categ['componentCode']]['issues'][$issue['code']] = $issue['description'];
                                }
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
            $errors[] = 'ID du SAV absent 55';
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
                    $errors[] = 'ID du SAV absent 56';
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
            'id_product'        => 0,
            'product_label'     => '',
            'serial'            => $serial,
            'imei'              => '',
            'imei2'             => '',
            'meid'              => '',
            'date_purchase'     => '',
            'date_warranty_end' => '',
            'warranty_type'     => '',
            'warning'           => ''
        );

        if ($serial) {
            $matches = array();
            if (preg_match('/^S(.+)$/', $serial, $matches)) {
                $serial = $matches[1];
            }
            $result = $this->gsx_v2->productDetailsBySerial($serial);
            if (is_array($result)) {
                if (isset($result['device']['productDescription'])) {
                    $data['product_label'] = $result['device']['productDescription'];
                }

                if (isset($result['device']['identifiers']['serial'])) {
                    $data['serial'] = $result['device']['identifiers']['serial'];

                    if (isset($result['device']['identifiers']['imei'])) {
                        $data['imei'] = $result['device']['identifiers']['imei'];
                    } else {
                        $data['imei'] = "n/a";
                    }

                    if (isset($result['device']['identifiers']['imei2'])) {
                        $data['imei2'] = $result['device']['identifiers']['imei2'];
                    } else {
                        $data['imei2'] = "n/a";
                    }

                    if (isset($result['device']['identifiers']['meid'])) {
                        $data['meid'] = $result['device']['identifiers']['meid'];
                    } else {
                        $data['meid'] = "n/a";
                    }

                    $matches = array();
                    if (preg_match('/^.+(.{4})$/', $data['serial'], $matches)) {
                        $product = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                                    'code_config' => $matches[1],
                                    'ref'         => array(
                                        'part'      => 'APP-',
                                        'part_type' => 'beginning'
                                    )
                                        ), true);
//                        
                        if (BimpObject::objectLoaded($product)) {
                            $data['id_product'] = (int) $product->id;
                        }
                    }
                }

                if (isset($result['device']['warrantyInfo']['warrantyStatusDescription'])) {
                    $data['warranty_type'] = $result['device']['warrantyInfo']['warrantyStatusDescription'];
                }

                if (isset($result['device']['warrantyInfo']['coverageEndDate']) &&
                        (string) $result['device']['warrantyInfo']['coverageEndDate'] &&
                        !preg_match('/^1970\-01\-01.*$/', $result['device']['warrantyInfo']['coverageEndDate'])) {
                    $dt = new DateTime($result['device']['warrantyInfo']['coverageEndDate']);
                    $data['date_warranty_end'] = $dt->format('Y-m-d H:i:s');
                }
                if (isset($result['device']['warrantyInfo']['purchaseDate']) &&
                        (string) $result['device']['warrantyInfo']['purchaseDate'] &&
                        !preg_match('/^1970\-01\-01.*$/', $result['device']['warrantyInfo']['purchaseDate'])) {
                    $dt = new DateTime($result['device']['warrantyInfo']['purchaseDate']);
                    $data['date_purchase'] = $dt->format('Y-m-d H:i:s');
                }
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

    protected function gsxGetEquipmentEligibility($params)
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
            $data2 = $this->gsx_v2->productDetailsBySerial($serial);
            if (isset($data2['device']['productDescription']) && !empty($data2['device']['productDescription'])) {

                $html .= '<div style="margin-top: 15px; padding: 10px; border: 1px solid #DCDCDC">';
                $html .= $data2['device']['productDescription'];
                $html .= '</div>';
            }
            $data = $this->gsx_v2->serialEligibility($serial);
            
            foreach($data['eligibilityDetails']['outcome'] as $out){
                if($out['action'] == 'WARNING'){
                    foreach($out['reasons'] as $warn){
                        if($warn['type'] == 'WARNING'){
                            foreach($warn['messages'] as $msg)
                                $html .= '<div class="blink big">'.BimpRender::renderAlerts($msg).'</div>';
                        }
                    }
                }
            }
            
            if (isset($data['eligibilityDetails']) && !empty($data['eligibilityDetails'])) {

                $html .= '<div style="margin-top: 15px; padding: 10px; border: 1px solid #DCDCDC">';
                $html .= '<h4>Eligibilité</h4>';
                $html .= '<table class="bimp_list_table">';
                $html .= '<tbody class="headers_col">';
                foreach (array(
            'coverageDescription' => 'Couverture',
            'coverageCode'        => 'Code couverture'
                ) as $path => $label) {
                    $value = BimpTools::getArrayValueFromPath($data['eligibilityDetails'], $path, '', $errors, false, '', array(
                                'value2String' => true
                    ));
                    $html .= '<tr>';
                    $html .= '<th>' . $label . '</ht>';
                    $html .= '<td>' . $value . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody>';
                $html .= '</table>';

                if (isset($data['eligibilityDetails']['outcome']) && !empty($data['eligibilityDetails']['outcome'])) {
                    BimpObject::loadClass('bimpapple', 'GSX_Repair');
                    $warnings_msgs = array();
                    $danger_msg = GSX_Repair::processRepairRequestOutcome($data['eligibilityDetails'], $warnings_msgs);

                    if (!empty($danger_msg)) {
                        $html .= BimpRender::renderAlerts($danger_msg);
                    }
                    if (!empty($warnings_msgs)) {
                        $html .= BimpRender::renderAlerts($warnings_msgs, 'warning');
                    }
                }
                $html .= '</div>';
            } else {
                $errors = $this->gsx_v2->getErrors();
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

        if ($id_sav != 'none') {
            if (!$id_sav) {
                $errors[] = 'ID du SAV absent 57';
            } else {
                $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
                if (!BimpObject::objectLoaded($sav)) {
                    $errors[] = 'Le SAV d\'ID ' . $id_sav . ' n\'existe pas';
                }
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

    public function getInfoSerialHtml($serial)
    {

        if (is_null($this->gsx_v2)) {
            $this->gsx_v2 = GSX_v2::getInstance();
        }
        $tab = $this->gsxLoadSavGsxView(array('serial' => $serial, 'id_sav' => 'none'));
        return $tab;
    }

    protected function gsxLoadSavRepairs($params)
    {
        $errors = array();
        $warnings = array();
        $html = '';

        $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : 0);

        if (!$id_sav) {
            $errors[] = 'ID du SAV absent 58';
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
            $errors[] = 'ID du SAV absent 59';
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
            $errors[] = 'ID du SAV absent 60';
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

                    case 'updatePartNumber':
                        $part_number = (isset($params['part_number']) ? $params['part_number'] : '');
                        $kgb_number = (isset($params['kgb_number']) ? $params['kgb_number'] : '');
                        $kbb_number = (isset($params['kbb_number']) ? $params['kbb_number'] : '');
                        $sequence_number = (isset($params['sequence_number']) ? $params['sequence_number'] : 0);

                        if (!$part_number) {
                            $errors[] = 'Réference du composant absent';
                        }

                        if (!$kgb_number) {
                            $errors[] = 'Nouveau numéro de série';
                        }

                        if (!count($errors)) {
                            $errors = $repair->updatePartNumber($part_number, $kgb_number, $kbb_number, $sequence_number, $warnings);
                        }
                        break;

                    case 'closeRepair':
                        $check_repair = (isset($params['check_repair']) ? (int) $params['check_repair'] : 1);
                        $send_request = (isset($params['send_request']) ? (int) $params['send_request'] : 1);
                        return $repair->close($send_request, $check_repair);
                }
            }
        }

        return array(
            'errors'     => $errors,
            'warnings'   => $warnings,
            'modal_html' => $modal_html
        );
    }

    protected function gsxFetchRepairEligibility($params)
    {
        // Appellé via le formulaire de création d'une réparation. 
        ini_set('display_errors', 1);

        $errors = array();
        $warnings = array();
        $html = '';
        $repair_ok = 0;

        $serial = (isset($params['serial']) ? $params['serial'] : 0);
        $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : 0);

        if (!$serial) {
            $errors[] = 'Numéro de série absent';
        }

        if (!$id_sav) {
            $errors[] = 'ID du SAV absent 61';
        }

        if (!count($errors) && $this->gsx_v2->logged) {
            $gsxRequests = new GSX_Request_v2($this->gsx_v2, 'repairCreate');
            $gsxRequests->serial = $serial;
            $gsxRequests->id_sav = $id_sav;

            $result = $gsxRequests->processRequestForm();
            $errors = $this->gsxRequestFormResultOverride('repairEligibility', $result, $params, $warnings);

            if (!count($errors)) {
                if (is_array($result) && !empty($result)) {
                    $data = array();
                    foreach (array(
                'repairType',
                'coverageOption',
                'unitReceivedDateTime',
                'addressCosmeticDamage',
                'consumerLaw',
                'componentIssues',
                'device',
                'parts',
                'questionDetails'
                    ) as $data_name) {
                        if (isset($result[$data_name])) {
                            $data[$data_name] = $result[$data_name];
                        }
                    }

                    $response = $this->gsx_v2->exec('repairEligibility', $data);

                    if ($response === false) {
                        $errors = $this->gsx_v2->getErrors();
                    } else {
                        $repair_ok = 1;
                        $html = $this->renderEligibilityDetails($response, $repair_ok, array(
                            'REPAIR_TYPE'
                        ));
                    }
                } else {
                    $errors[] = BimpTools::getMsgFromArray($gsxRequests->errors, 'Erreurs lors du traitement des données');
                }
            }
        }

        return array(
            'errors'    => $errors,
            'warnings'  => $warnings,
            'html'      => $html,
            'repair_ok' => $repair_ok
        );
    }

    protected function gsxLoadRepairEligibilityDetails($params)
    {
        // Appellé via le bouton "Tester Eligibilité" 
        $errors = array();
        $warnings = array();
        $html = '';

        if ($this->gsx_v2->logged) {
            $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : 0);
            $repair_type = (isset($params['repairType']) ? $params['repairType'] : '');

            if (!$id_sav) {
                $errors[] = 'ID du SAV absent 62';
            } else {
                $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
                if (!BimpObject::objectLoaded($sav)) {
                    $errors[] = 'Le SAV d\'ID ' . $id_sav . ' n\'existe pas';
                } else {
                    $serial = $sav->getSerial();

                    if (!$serial) {
                        $errors[] = 'Numéro de série de l\'appareil absent';
                    } else {
                        $data = array(
                            'device' => array(
                                'id' => $serial
                            )
                        );

                        if ($repair_type) {
                            $data['repairType'] = $repair_type;

                            $issues = $sav->getChildrenObjects('issues');

                            if (!empty($issues)) {
                                $data['componentIssues'] = array();

                                $n = 1;
                                foreach ($issues as $issue) {
                                    $is_tier_parts = $issue->isTierPart();

                                    if (!$is_tier_parts) {
                                        $data['componentIssues'][] = array(
                                            'componentCode'   => $issue->getData('category_code'),
                                            'issueCode'       => $issue->getData('issue_code'),
                                            'reproducibility' => $issue->getData('reproducibility'),
                                            'priority'        => (int) $n,
                                            'type'            => $issue->getData('type'),
                                            'order'           => (int) $n,
                                        );
                                    }

                                    $n++;
                                    $parts = BimpCache::getBimpObjectObjects('bimpsupport', 'BS_ApplePart', array(
                                                'id_issue' => (int) $issue->id,
                                                'no_order' => 0
                                    ));

                                    if (!empty($parts)) {
                                        if (!isset($data['parts'])) {
                                            $data['parts'] = array();
                                        }

                                        foreach ($parts as $part) {
                                            $part_data = array(
                                                'number' => $part->getData('part_number')
                                            );

                                            $typePrice = (string) $part->getData('price_type');
                                            if ($typePrice && !in_array($typePrice, array('STOCK', 'EXCHANGE'))) {
                                                $part_data['pricingOption'] = $typePrice;
                                            }

                                            if (!$is_tier_parts) {
                                                $part_data['partUsed'] = $part->getData('part_number');
                                                $part_data['componentIssue'] = array(
                                                    'componentCode'   => $issue->getData('category_code'),
                                                    'issueCode'       => $issue->getData('issue_code'),
                                                    'reproducibility' => $issue->getData('reproducibility')
                                                );
                                            }

                                            $data['parts'][] = $part_data;
                                        }
                                    }
                                }
                            }
                        }

                        $this->gsx_v2->resetErrors();
                        $response = $this->gsx_v2->exec('repairEligibility', $data);

                        if ($response === false) {
                            $errors = $this->gsx_v2->getErrors();
                        } else {
                            $repair_ok = 1;
                            $html = $this->renderEligibilityDetails($response, $repair_ok, ($repair_type ? array('REPAIR_TYPE') : array()));
                        }
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'html'     => $html
        );
    }

    protected function gsxGetRepairQuestions($params)
    {
        $errors = array();
        $questions = array();

        if ($this->gsx_v2->logged) {

            $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : 0);
            $repairType = (isset($params['repairType']) ? $params['repairType'] : '');
            if (!$id_sav) {
                $errors[] = 'ID du SAV absent 63';
            } else {
                $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
                if (!BimpObject::objectLoaded($sav)) {
                    $errors[] = 'Le SAV d\'ID ' . $id_sav . ' n\'existe pas';
                }
            }

            if (!$repairType) {
                $errors[] = 'Type de réparation absent';
            }

            if (!count($errors)) {
                $issues = array();
                $parts = array();

                $n = 0;
                foreach ($sav->getChildrenObjects('issues') as $issue) {
                    if ($issue->isTierPart()) {
                        continue;
                    }
                    $n++;

                    $issues[] = array(
                        'componentCode'   => $issue->getData('category_code'),
                        'issueCode'       => $issue->getData('issue_code'),
                        'reproducibility' => $issue->getData('reproducibility'),
                        'priority'        => $n,
                        'type'            => $issue->getData('type'),
                        'order'           => $n,
                    );
                }

                foreach ($sav->getChildrenObjects('apple_parts')as $part) {
                    $issue = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Issue', (int) $part->getData('id_issue'));
                    if (BimpObject::objectLoaded($issue) && !$issue->isTierPart()) {
                        $parts[] = array(
                            'number'         => $part->getData('part_number'),
                            'componentIssue' => array(
                                'componentCode'   => $issue->getData('category_code'),
                                'issueCode'       => $issue->getData('issue_code'),
                                'reproducibility' => $issue->getData('reproducibility')
                            )
                        );
                    } else {
                        $parts[] = array(
                            'number' => $part->getData('part_number'));
                    }
                }

                if (GSX_Const::$mode === 'test') {
                    $questions = json_decode(file_get_contents(DOL_DOCUMENT_ROOT . '/bimpapple/questions.json'), 1);
                } else {
                    $this->gsx_v2->resetErrors();
                    $questions = $this->gsx_v2->repairQestions($sav->getSerial(), $repairType, $issues, $parts);
                    if ($questions === false) {
                        $questions = array();
                        $errors = $this->gsx_v2->getErrors();
                    }
                }
            }
        }

        return array(
            'errors'    => $errors,
            'questions' => $questions
        );
    }

    protected function gsxProcessRepairQuestionsForm($params, &$result = array())
    {
        $data = $this->gsxGetRepairQuestions($params);
        $errors = array();

        if (isset($data['errors']) && !empty($data['errors'])) {
            $errors = $data['errors'];
        } elseif (isset($data['questions']) && !empty($data['questions'])) {
            if (isset($data['questions']['questionDetails']) && !empty($data['questions']['questionDetails'])) {
                $result['questionDetails'] = array();

                foreach ($data['questions']['questionDetails'] as $qd) {
                    $prefixe = 'tpl_' . $qd['templateId'];
                    $tpl = array(
                        'templateId' => $qd['templateId']
                    );
                    $trees = array();

                    if (isset($qd['trees']) && !empty($qd['trees'])) {
                        foreach ($qd['trees'] as $t) {
                            $prefixe .= '_tree_' . $t['treeId'];
                            $tree = array(
                                'treeId' => $t['treeId']
                            );

                            if (isset($t['questions']) && !empty($t['questions'])) {
                                $responses = $this->gsxProcessRepairQuestionsInputs($t['questions'], $prefixe, $errors);

                                if (!empty($responses)) {
                                    $tree['questions'] = $responses;
                                }
                            }

                            $trees[] = $tree;
                        }
                    }
                    if (!empty($trees)) {
                        $tpl['trees'] = $trees;
                    }

                    $result['questionDetails'][] = $tpl;
                }

//                $array = array(
//                    'questionDetails' => $result['questionDetails']
//                );
//
//                ini_set('display_errors', 1);
//                $json = json_encode($array);
//                if (!$json) {
//                    echo json_last_error_msg();
//                } else {
//                    echo 'JSON: ';
//                    echo json_encode($array);
//                }
//                exit;
            }
        }

        return array(
            'errors' => $errors,
            'result' => $result
        );
    }

    protected function gsxProcessRepairQuestionsInputs($questions, $prefixe, &$errors = array())
    {
        $responses = array();

        if (is_array($questions) && !empty($questions)) {
            foreach ($questions as $q) {
                $input_name = $prefixe . '_q_' . $q['questionId'];

                $value = BimpTools::getPostFieldValue($input_name, '');
                if ($value) {
                    $response = array(
                        'questionId' => $q['questionId'],
                        'answers'    => array(),
                    );

                    if (isset($q['questionPhrase']) && (string) $q['questionPhrase']) {
                        $response['questionPhrase'] = $q['questionPhrase'];
                    }

                    if (isset($q['optional']) && (int) $q['optional']) {
                        $response['optional'] = true;
                    }

                    $answer = array();
                    if (isset($q['answers']) && !empty($q['answers'])) {
                        foreach ($q['answers'] as $a) {
                            if ($value == $a['answerId']) {
                                $answer['answerId'] = $a['answerId'];

                                if (isset($a['questions']) && !empty($a['questions'])) {
                                    $subResponses = $this->gsxProcessRepairQuestionsInputs($a['questions'], $input_name . '_a_' . $a['answerId'], $errors);
                                    if (!empty($subResponses)) {
                                        $answer['questions'] = $subResponses;
                                    }
                                }
                                break;
                            }
                        }
                    } else {
                        $answer['answerPhrase'] = $value;
                    }

                    $response['answers'][] = $answer;
                    $responses[] = $response;
                } elseif ($q['answerType'] !== 'INT' && (!isset($q['optional']) || !(int) $q['optional'])) {
                    $errors[] = 'Une réponse est obligatoire pour la question "' . $q['questionPhrase'] . '"';
                }
            }
        }


        return $responses;
    }

    // Diagnostics: 

    protected function gsxDiagnosticSuites($params)
    {
        $errors = array();
        $warnings = array();

        $html = '';

        $id_sav = (isset($params['id_sav']) ? (int) $params['id_sav'] : 0);
        $serial = (isset($params['serial']) ? $params['serial'] : '');

        if ($serial == "") {
            if (!$id_sav) {
                $errors[] = 'ID du SAV absent 64v';
            } else {
                $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
                if (!BimpObject::objectLoaded($sav)) {
                    $errors[] = 'Le SAV d\'ID ' . $id_sav . ' n\'existe pas';
                } else {
                    $serial = $sav->getSerial();
                }
            }
        }

        if (!count($errors)) {
            if ($this->gsx_v2->logged) {
                $data = $this->gsx_v2->diagnosticSuites($serial);
                if (is_array($data) && !empty($data)) {
                    if (isset($data['suiteDetails']) && count($data['suiteDetails'])) {
                        $html .= BimpRender::renderAlerts(count($data['suiteDetails']) . ' diagnostic(s) trouvé(s)', 'info');

                        $html .= '<table class="bimp_list_table">';
                        $html .= '<thead>';
                        $html .= '<th>Type de diagnotic</th>';
                        $html .= '<th>Temps estimé</th>';
                        $html .= '<th></th>';
                        $html .= '</thead>';

                        $html .= '<tbody>';

                        foreach ($data['suiteDetails'] as $suite) {
                            $html .= '<tr>';
                            $html .= '<td class="suite_name">' . $suite['suiteName'] . '</td>';
                            $html .= '<td>';
                            $html .= 'De ' . $suite['timeEstimate']['minimum'] . ' à ' . $suite['timeEstimate']['maximum'] . ' minute(s)';
                            $html .= '</td>';
                            $html .= '<td>';
                            $html .= '<span class="btn btn-default" onclick="gsx_runDiagnostic($(this), \'' . $serial . '\', ' . $suite['suiteId'] . ')">';
                            $html .= 'Lancer' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
                            $html .= '</span>';
                            $html .= '</td>';
                            $html .= '</tr>';
                        }

                        $html .= '</tbody>';
                        $html .= '</table>';
                        $html .= '<div class="ajaxResults"></div>';
                    } else {
                        $warnings[] = 'Aucun diagnostic disponible pour le numéro de série "' . $serial . '"';
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

    protected function gsxRunDiagnostic($params)
    {
        $errors = array();
        $warnings = array();


        $suite_id = (isset($params['suite_id']) ? (int) $params['suite_id'] : '');
        $serial = (isset($params['serial']) ? $params['serial'] : '');



        if (!$suite_id) {
            $errors[] = 'Identifiant du type de diagnostic absent';
        }

        if (!count($errors)) {
            if ($this->gsx_v2->logged) {
                $data = $this->gsx_v2->runDiagnostic($serial, $suite_id);
                if (!$data) {
                    $errors = $this->gsx_v2->getErrors();
                } elseif (!isset($data['diagnosticsInitiated']) || !(int) $data['diagnosticsInitiated']) {
                    $errors[] = 'Echec de l\'initialisation du diagnostic pour une raison inconnue';
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
        );
    }

    protected function gsxRefreshDiagnosticStatus($params)
    {
        $errors = array();

        $serial = (isset($params['serial']) ? $params['serial'] : '');
        if ($serial == "") {
            $errors[] = 'Serial absent';
        } else {
            $html = $this->renderDiagnosticStatus($serial);
        }

        return array(
            'errors' => $errors,
            'html'   => $html
        );
    }

    protected function gsxLoadDiagnosticsDetails($params)
    {
        $errors = array();

        $serial = (isset($params['serial']) ? $params['serial'] : '');
        if ($serial != '') {
            $html = $this->renderDiagnosticsDetails($serial);
        } else
            $errors[] = 'Serial absent 76';

        return array(
            'errors' => $errors,
            'html'   => $html
        );
    }

    // Méthodes GSX V1:

    public function initGsx()
    {
        if (is_null($this->gsx)) {
            $this->gsx = new GSX($this->isIphone);
            return BimpTools::merge_array($this->gsx->errors['init'], $this->gsx->errors['soap']);
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

    public function checkEquipmentData($sav, $data)
    {
        if (BimpObject::objectLoaded($sav)) {
            $equipment = $sav->getChildObject('equipment');
            if (BimpObject::objectLoaded($equipment)) {
                if (isset($data['identifiers']['serial']) && $data['identifiers']['serial'] === $equipment->getData('serial')) {
                    if (!(int) $equipment->getData('id_product') && preg_match('/^\*+$/', $equipment->getData('product_label'))) {
                        $update = false;

                        if (isset($data['productDescription']) && !preg_match('/^\*+$/', $data['productDescription'])) {
                            $equipment->set('product_label', $data['productDescription']);
                            $update = true;
                        }

                        if (isset($data['warrantyInfo']['warrantyStatusDescription']) &&
                                $data['warrantyInfo']['warrantyStatusDescription'] !== $equipment->getData('warranty_type')) {
                            $equipment->set('warranty_type', $data['warrantyInfo']['warrantyStatusDescription']);
                            $update = true;
                        }

                        if (isset($data['warrantyInfo']['coverageEndDate']) &&
                                (string) $data['warrantyInfo']['coverageEndDate'] &&
                                !preg_match('/^1970\-01\-01.*$/', $data['warrantyInfo']['coverageEndDate'])) {
                            $dt = new DateTime($data['warrantyInfo']['coverageEndDate']);
                            $date = $dt->format('Y-m-d H:i:s');
                            if ($date !== $equipment->getData('date_warranty_end')) {
                                $equipment->set('date_warranty_end', $date);
                                $update = true;
                            }
                        }

                        if (isset($data['warrantyInfo']['purchaseDate']) &&
                                (string) $data['warrantyInfo']['purchaseDate'] &&
                                !preg_match('/^1970\-01\-01.*$/', $data['warrantyInfo']['purchaseDate'])) {
                            $dt = new DateTime($data['warrantyInfo']['purchaseDate']);
                            $date = $dt->format('Y-m-d H:i:s');
                            if ($date !== $equipment->getData('date_purchase')) {
                                $equipment->set('date_purchase', $date);
                                $update = true;
                            }
                        }

                        if ($update) {
                            $warnings = array();
                            $equipment->update($warnings, true);
                        }
                    }
                }
            }
        }
    }

    // Rendus HTML V2:

    public function renderSavGsxView($sav, $serial, &$errors = array(), &$warnings = array())
    {
        $html = '';

        $this->setSerial($serial);

        if ($this->gsx_v2->logged) {

            $html .= '<div class="buttonsContainer align-right">';
            $html .= '<button type="button" class="btn btn-default" onclick="loadGSXView($(this), ' . (int) $sav->id . ')">';
            $html .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Actualiser toutes les données GSX';
            $html .= '</button>';
            $html .= '</div>';
            $lookUpContent = '';
            $data = $this->gsx_v2->productDetailsBySerial($serial);

            if (is_array($data) && !empty($data) && isset($data['device'])) {
                $data = $data['device'];

                if (isset($data['identifiers']['serial'])) {
                    $this->serial2 = $data['identifiers']['serial'];
                }

                $this->checkEquipmentData($sav, $data);

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
                $infosContent .= '<tbody class="headers_col">';

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
                    $value = BimpTools::getArrayValueFromPath($data, $path, '', $errors, false, '', array(
                                'value2String' => true
                    ));
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
                $warrantyContent .= '<tbody class="headers_col">';
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
                    $value = BimpTools::getArrayValueFromPath($data, $path, '', $errors, false, '', array(
                                'value2String' => true
                    ));
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

            $html .= BimpRender::renderPanel('Diagnostics', $this->renderSavGsxDiagnosticsView($serial), '', array(
                        'panel_id' => 'sav_diagnostics',
                        'type'     => 'secondary',
                        'icon'     => 'fas_stethoscope',
                        'foldable' => true
            ));

            if (is_object($sav)) {
                $html .= BimpRender::renderPanel('Réparations', $this->renderRepairs($sav), '', array(
                            'panel_id' => 'sav_repairs',
                            'type'     => 'secondary',
                            'icon'     => 'fas_tools',
                            'foldable' => true
                ));

                $html .= $sav->renderApplePartsList('gsx');
            }
        } else {
            $warnings[] = "pas loggé";
            die('dddddd');
        }

        return $html;
    }

    protected function renderSavGsxDiagnosticsView($serial)
    {
        if (!$this->use_gsx_v2) {
            return '';
        }

        $html = '';

        $html .= '<div>';
        $html .= '<h3 style="display: inline-block; margin-left: 15px">Diagnostic en cours</h3>';
        $html .= '<span  style="margin-left: 30px" class="btn btn-default" onclick="gsx_refeshDiagnosticStatus($(this), \'' . $serial . '\')">';
        $html .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Actualiser';
        $html .= '</span>';
        $html .= '</div>';

        $html .= '<div id="currentDiagnosticStatus">';
        $html .= $this->renderDiagnosticStatus($serial);
        $html .= '</div>';

        $html .= '<div class="buttonsContainer align-right">';
        $html .= '<button class="btn btn-default" onclick="gsx_diagnosticSuites($(this), \'' . $serial . '\')">';
        $html .= 'Lancer diagnostic' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        $html .= '</button>';

        $html .= '<button class="btn btn-default" onclick="gsx_loadDiagnosticsDetails($(this), \'' . $serial . '\')">';
        $html .= BimpRender::renderIcon('fas_download', 'iconLeft') . 'Charger le détail des diagnostics';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div id="diagnosticsDetails" style="display: none">';
        $html .= '</div>';

        return $html;
    }

    protected function renderDiagnosticStatus($serial)
    {
        $html = '';

        if (!isset($serial) || $serial == "") {
            return BimpRender::renderAlerts('Serial absent');
        }

        if ($this->gsx_v2->logged) {
            $this->gsx_v2->resetErrors();
            $data = $this->gsx_v2->diagnosticStatus($serial);

            if ($data === false) {
                $html .= $this->gsx_v2->displayErrors();
            } elseif (isset($data['diagnosticSuite']['name']) && (string) $data['diagnosticSuite']['name']) {
                $html .= '<strong>Type: </strong>' . $data['diagnosticSuite']['name'] . '<br/>';
                $html .= '<strong>Statut: </strong>' . $data['diagnosticSuite']['suiteStatus'] . ' - ' . $data['diagnosticSuite']['statusDescription'] . '<br/>';
                $html .= '<strong>Progression: </strong>' . $data['diagnosticSuite']['percentComplete'];
            } else {
                $html .= BimpRender::renderAlerts('Aucun diagnostic en cours', 'info');
            }
        }

        return $html;
    }

    protected function renderDiagnosticsDetails($serial)
    {
        $html = '';
        $errors = array();

        if ($serial != "") {
            $this->gsx_v2->resetErrors();
            $data = $this->gsx_v2->diagnosticsLookup($serial);

            if ($data === false) {
                $html .= $this->gsx_v2->displayErrors();
            } elseif (isset($data['diagnostics']) && count($data['diagnostics'])) {
                foreach ($data['diagnostics'] as $diag) {
                    $title = 'Diagnostic: ' . $diag['context']['suite'];
                    $content = '';

                    $content .= '<table class="bimp_list_table">';
                    $content .= '<tbody class="headers_col">';
                    foreach (array(
                'context/diagnosticStartTimeStamp' => 'Commencé le',
                'context/diagnosticEndTimeStamp'   => 'Terminé le',
                'context/channelId'                => 'Canal',
                'context/systemId'                 => 'Système',
                'context/diagnosticEventEndResult' => 'Code résultat'
                    ) as $path => $label) {
                        $value = BimpTools::getArrayValueFromPath($diag, $path, '', $errors, false, '', array(
                                    'value2String' => true
                        ));
                        if (!is_null($value)) {
                            $content .= '<tr><th>' . $label . '</th><td>' . $value . '</td></tr>';
                        }
                    }

                    if (isset($diag['deviceProfile']) && !empty($diag['deviceProfile'])) {
                        $html .= '<tr>';
                        $html .= '<th>Profile matériel</th>';
                        $html .= '<td>';

                        $html .= '<table>';
                        $html .= '<tbody>';

                        foreach (array(
                    'type'                  => 'Type',
                    'data/hardwareModel'    => 'Modèle',
                    'data/currentOsVersion' => 'Version actuelle de l\'OS',
                    'data/latestOsVersion'  => 'Dernière version de l\'OS',
                    'data/restoreDate'      => 'Date de restauration',
                    'data/ilifeVersion'     => 'ilife Version',
                        ) as $path => $label) {
                            $value = BimpTools::getArrayValueFromPath($diag, $path, '', $errors, false, '', array(
                                        'value2String' => true
                            ));
                            if (!is_null($value)) {
                                $content .= '<tr><th>' . $label . '</th><td>' . $value . '</td></tr>';
                            }
                        }

                        $html .= '</tbody>';
                        $html .= '</table>';

                        $html .= '</td>';
                        $html .= '</tr>';
                    }

                    $content .= '</tbody>';
                    $content .= '</table>';

                    $content .= '<h4>Résultats des tests</h4>';
                    if (isset($diag['testResults']) && !empty($diag['testResults'])) {
                        foreach ($diag['testResults'] as $test) {
                            $test_html = '';

                            $test_html .= '<table class="bimp_list_table">';
                            $test_html .= '<tbody class="headers_col">';
                            foreach (array(
                        'testId'         => 'Identifiant',
                        'testType'       => 'Type',
                        'testStatus'     => 'Statut',
                        'testStatusCode' => 'Code statut',
                        'testMessage'    => 'Message',
                        'moduleName'     => 'Nom du module',
                        'moduleLocation' => 'Emplacement du module'
                            ) as $path => $label) {
                                $value = BimpTools::getArrayValueFromPath($test, $path, '', $errors, false, '', array(
                                            'value2String' => true
                                ));
                                if (!is_null($value)) {
                                    $test_html .= '<tr><th>' . $label . '</th><td>' . $value . '</td></tr>';
                                }
                            }

                            if (isset($test['detailResultData']) && !empty($test['detailResultData'])) {
                                $test_html .= '<tr><th>Résultats</th>';
                                $test_html .= '<td>';

                                $test_html .= BimpRender::renderRecursiveArrayContent($test['detailResultData'], array(
                                            'foldable' => 1,
                                            'open'     => 0,
                                            'title'    => 'Résultats'
                                ));

                                $test_html .= '</td>';
                                $test_html .= '</tr>';
                            }
                            $test_html .= '</tbody>';
                            $test_html .= '</table>';

                            if ((int) $test['testStatusCode'] === 0) {
                                $test_title = $test['testName'] . '  ' . '<span class="badge badge-success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . $test['testStatus'] . '</span>';
                                $test_open = false;
                            } else {
                                $test_title = $test['testName'] . '  ' . '<span class="badge badge-warning">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . $test['testStatus'] . '</span>';
                                $test_open = true;
                            }

                            $content .= BimpRender::renderPanel($test_title, $test_html, '', array(
                                        'type'     => 'default',
                                        'foldable' => true,
                                        'open'     => $test_open
                            ));
                        }
                    } else {
                        $content .= BimpRender::renderAlerts('Aucun résultat de test reçu', 'warning');
                    }

                    $html .= BimpRender::renderPanel($title, $content, '', array(
                                'type'     => 'secondary',
                                'foldable' => true
                    ));
                }
            } else {
                $html .= BimpRender::renderAlerts('Aucun diagnostic trouvé pour ce serial', 'warning');
            }
        }

        return $html;
    }

    protected function renderRepairQuestionsFormContent($params, &$errors = array())
    {
        $html = '';

        if ($this->gsx_v2->logged) {
            $result = $this->gsxGetRepairQuestions($params);

            if (!empty($result['errors'])) {
                $html .= BimpRender::renderAlerts($result['errors']);
            } elseif (!isset($result['questions']) || empty($result['questions'])) {
                $html .= BimpRender::renderAlerts('Aucune question reçue', 'warning');
            } else {
                //            $data = json_decode(file_get_contents(DOL_DOCUMENT_ROOT . '/bimpapple/questions.json'), 1);
                $data = $result['questions'];

                if (isset($data['message'])) {
                    $html .= BimpRender::renderAlerts($data['message'], 'info');
                }

                if (isset($data['questionDetails']) && !empty($data['questionDetails'])) {
                    foreach ($data['questionDetails'] as $qd) {
                        if (isset($qd['trees']) && !empty($qd['trees'])) {
                            $html .= '<div class="repairFormQuestionDetails" data-templateId="' . $qd['templateId'] . '">';
                            $prefixe = 'tpl_' . $qd['templateId'];

                            foreach ($qd['trees'] as $tree) {
                                if (isset($tree['questions']) && !empty($tree['questions'])) {
                                    $prefixe .= '_tree_' . $tree['treeId'];
                                    $html .= '<div class="repairFormQuestionsTree" data-treeId="' . $tree['treeId'] . '">';
                                    $html .= $this->renderRepairQuestionsInputs($tree['questions'], $prefixe);
                                    $html .= '</div>';
                                }
                            }

                            $html .= '</div>';
                        }
                    }
                }
            }
        }

        return BimpRender::renderFormInputsGroup('Questions', 'repairQuestions', $html);
    }

    protected function renderRepairQuestionsInputs($questions, $prefixe = '', $display_if = array())
    {
        $html = '';

        $html .= '<div class="rapairFormQuestions">';

        foreach ($questions as $question) {
            $html .= '<div class="repairFormQuestion' . (!empty($display_if) ? ' display_if' : '') . '" data-questionId="' . $question['questionId'] . '"';
            if (!empty($display_if)) {
                $html .= BC_Field::renderDisplayifDataStatic($display_if);
            }
            $html .= '>';
            $html .= '<div class="repairQuestionLabel">' . $question['questionPhrase'] . '</div>';
            if ($question['answerType'] !== 'INT') {
                $input_name = $prefixe . '_q_' . $question['questionId'];
                $input_type = '';
                $options = array();

                switch ($question['answerType']) {
                    case 'FFB':
                    case 'BBX':
                        $input_type = 'textarea';
                        $options['auto_expand'] = 1;
                        $options['rows'] = 1;
                        break;

                    case 'RAD':
                    case 'DPD':
                    case 'CHK':
                        $input_type = 'select';
                        $answers = array(
                            '' => ''
                        );
                        if (isset($question['answers']) && !empty($question['answers'])) {
                            foreach ($question['answers'] as $answer) {
                                $answers[$answer['answerId']] = $answer['answerPhrase'];
                            }
                        }
                        $options['options'] = $answers;
                        break;
                }

                if ($input_type) {
                    $html .= '<div class="repairQuestionInput">';
                    $html .= BimpInput::renderInput($input_type, $input_name, '', $options);
                    $html .= '</div>';
                }
            }

            if (isset($question['answers']) && !empty($question['answers'])) {
                foreach ($question['answers'] as $answer) {
                    if (isset($answer['questions']) && !empty($answer['questions'])) {
                        $html .= $this->renderRepairQuestionsInputs($answer['questions'], $input_name . '_a_' . $answer['answerId'], array(
                            'field_name'  => $input_name,
                            'show_values' => array(
                                $answer['answerId']
                            )
                        ));
                    }
                }
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    protected function renderEligibilityDetails($response, &$repair_ok = 1, $excluded_msgs_types = array())
    {
        $html = '';
        $errors = array();

        if (isset($response['eligibilityDetails']) && !empty($response['eligibilityDetails'])) {
            $html .= '<h3>Détails éligibilité</h3>';
            $html .= '<table class="bimp_list_table">';
            $html .= '<tbody class="headers_col">';

            foreach (array(
        'coverageDescription' => 'Couverture',
        'coverageCode'        => 'Code couverture',
        'technicianMandatory' => 'Technicien requis',
            ) as $path => $label) {
                $value = BimpTools::getArrayValueFromPath($response['eligibilityDetails'], $path, '', $errors, false, '', array(
                            'value2String' => true
                ));
                if (!is_null($value)) {
                    $html .= '<tr><th>' . $label . '</th><td>' . $value . '</td></tr>';
                }
            }

            if (isset($response['eligibilityDetails']['outcome']) && !empty($response['eligibilityDetails']['outcome'])) {
                $html .= '<tr>';
                $html .= '<th>Messages</th>';
                $html .= '<td>';
                $rep_warnings = array();

                BimpObject::loadClass('bimpapple', 'GSX_Repair');
                $rep_errors = GSX_Repair::processRepairRequestOutcome($response['eligibilityDetails'], $rep_warnings, $excluded_msgs_types);

                if (count($rep_errors)) {
                    $html .= BimpRender::renderAlerts($rep_errors);
//                    $repair_ok = 0;  test todo pour faire quand meme avec warning
                }

                if (count($rep_warnings)) {
                    $html .= BimpRender::renderAlerts($rep_warnings, 'warning');
                }

                if ($rep_errors && !count($rep_warnings)) {
                    $html .= BimpRender::renderAlerts('Aucun message', 'info');
                }
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        if (isset($response['parts']) && !empty($response['parts'])) {
            $html .= '<h3>Eligibilité composants</h3>';

            foreach ($response['parts'] as $part) {
                $html .= '<h4>Composant "' . $part['number'] . '"</h4>';

                $html .= '<table class="bimp_list_table">';
                $html .= '<tbody class="headers_col">';

                foreach (array(
            'partType'            => 'Type de composant',
            'coverageDescription' => 'Couverture',
            'coverageCode'        => 'Code couverture',
            'billable'            => 'Facturable'
                ) as $path => $label) {
                    $value = BimpTools::getArrayValueFromPath($part, $path, '', $errors, false, '', array(
                                'value2String' => true
                    ));
                    if (!is_null($value)) {
                        $html .= '<tr><th>' . $label . '</th><td>' . $value . '</td></tr>';
                    }
                }

                $html .= '</tbody>';
                $html .= '</table>';
            }
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
            $errors[] = 'ID du SAV absent 68';
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


                        $gsx_content .= $sav->renderLoadPartsButton($serial, "deux");

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

    // Rendus HTML GSX V1 / V2:

    public function renderRepairs($sav)
    {
        $html = '';

        BimpObject::loadClass('bimpapple', 'GSX_Repair');

        $serial = $sav->getSerial();
        $this->setSerial($serial);
        $has_parts = $sav->hasParts();
        $has_repairs = BimpCache::getBdb()->getCount('bimp_gsx_repair', '`id_sav` = ' . (int) $sav->id);

        $html .= '<div class="buttonsContainer align-right">';
        $onclick = '';
        if ($this->use_gsx_v2) {
            $onclick = '$(\'.gsxRepairViewForm\').hide();$(\'#findRepairsToImportForm\').slideDown(250);';
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

        $onclick = '$(\'.gsxRepairViewForm\').hide();$(\'#createRepairForm\').slideDown(250);';
        $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
        $html .= '<i class="fa fa-plus-circle iconLeft"></i>Créer une nouvelle réparation</button>';

        if ($this->use_gsx_v2) {
            if ($has_parts) {
                $onclick = '$(\'.gsxRepairViewForm\').hide();$(\'#testEligibilityForm\').slideDown(250);';
                $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
                $html .= BimpRender::renderIcon('fas_question-circle', 'iconLeft') . 'Tester éligibilité composants</button>';
            }

            if (isset(GSX_Const::$urls['gsx'][GSX_v2::$mode]) && GSX_Const::$urls['gsx'][GSX_v2::$mode]) {
                $html .= '<a href="' . GSX_Const::$urls['gsx'][GSX_v2::$mode] . '" class="btn btn-default" target="_blank">';
                $html .= 'GSX' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
                $html .= '</a>';
            }
        }

        $html .= '</div>';

        if ($this->use_gsx_v2) {
            // Form Import réparation
            $html .= '<div id="findRepairsToImportForm" class="gsxRepairViewForm">';
            $html .= '<div class="findRepairsToImportFormContent gsxRepairViewFormContent">';

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

            // Form Créa réparation
            $html .= '<div id="createRepairForm" class="gsxRepairViewForm">';
            $html .= '<div class="createRepairFormContent gsxRepairViewFormContent">';

            $buttons = array();
            $buttons[] = BimpRender::renderButton(array(
                        'label'       => 'Annuler',
                        'icon_before' => 'times',
                        'classes'     => array('btn btn-danger'),
                        'attr'        => array(
                            'onclick' => '$(\'#createRepairForm\').slideUp(250);'
            )));
            $onclick = 'gsx_loadRequestModalForm($(this), \'Création d\\\'une nouvelle réparation\', \'repairCreate\', {';
            $onclick .= 'id_sav: ' . $sav->id . ', ';
            $onclick .= 'serial: \'' . $serial . '\'';
            $onclick .= '}, {});';

            $buttons[] = BimpRender::renderButton(array(
                        'label'      => 'Valider',
                        'icon_after' => 'arrow-circle-right',
                        'classes'    => array('btn btn-primary'),
                        'attr'       => array(
                            'onclick' => $onclick
            )));

            $html .= BimpRender::renderFreeForm(array(
                        array(
                            'label' => 'Type de réparation',
                            'input' => BimpInput::renderInput('select', 'repairType', null, array(
                                'options' => GSX_Const::$repair_types
                            ))
                        ),
                            ), $buttons, 'Création d\'une nouvelle réparation');

            $html .= '</div>';
            $html .= '</div>';

            // Form Test éligibilité: 
            if ($has_parts) {
                $html .= '<div id="testEligibilityForm" class="gsxRepairViewForm">';
                $html .= '<div class="testEligibilityFormContent gsxRepairViewFormContent">';

                $buttons = array();
                $buttons[] = BimpRender::renderButton(array(
                            'label'       => 'Annuler',
                            'icon_before' => 'times',
                            'classes'     => array('btn btn-danger'),
                            'attr'        => array(
                                'onclick' => '$(\'#testEligibilityForm\').slideUp(250);'
                )));

                $onclick = 'gsx_loadEligibilityDetails($(this), ' . $sav->id . ', $(\'#gsxEligibilityDetails\'));';
                $buttons[] = BimpRender::renderButton(array(
                            'label'      => 'Valider',
                            'icon_after' => 'arrow-circle-right',
                            'classes'    => array('btn btn-primary'),
                            'attr'       => array(
                                'onclick' => $onclick
                )));

                $html .= BimpRender::renderFreeForm(array(
                            array(
                                'label' => 'Type de réparation',
                                'input' => BimpInput::renderInput('select', 'eligibilityRepairType', null, array(
                                    'options' => GSX_Const::$repair_types
                                ))
                            ),
                                ), $buttons, 'Test éligibilité');

                $html .= '</div>';
                $html .= '</div>';

                // Container détails éligibilité:
                $html .= '<div id="gsxEligibilityDetails" style="display: none; margin: 15px 0; max-width: 800px">';
                $html .= '</div>';
            }

            if (!$has_repairs) {
                $eligibility_content = '';
                $result = $this->gsxLoadRepairEligibilityDetails(array(
                    'id_sav' => (int) $sav->id
                ));

                if (isset($result['errors']) && !empty($result['errors'])) {
                    $eligibility_content .= BimpRender::renderAlerts($result['errors']);
                }
                if (isset($result['warnings']) && !empty($result['warnings'])) {
                    $eligibility_content .= BimpRender::renderAlerts($result['warnings'], 'warning');
                }
                if (isset($result['html']) && !empty($result['html'])) {
                    $eligibility_content .= $result['html'];
                }


                $html .= BimpRender::renderPanel('Eligibilité pour le numéro de série "' . $serial . '"', $eligibility_content, '', array(
                            'foldable' => 1,
                            'open'     => 1,
                            'type'     => 'secondary'
                ));
            }
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
                $html .= $repair->renderView(($this->use_gsx_v2 ? 'gsx_v2' : 'default'), true, 2);
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
                $html .= BimpRender::renderAlerts('Aucun composant compatible trouvé', 'warning');
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

                            if (isset($part['pricingOptions']) && is_array($part['pricingOptions'])) {
                                foreach ($part['pricingOptions'] as $price_option) {
                                    if (isset($price_option['code'])) {
                                        $price_options[$price_option['code']] = array(
                                            'price'       => $price_option['price'],
                                            'description' => $price_option['description']
                                        );
                                    }
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
        } elseif ((int) BimpTools::getValue('gsx_fetchRepairEligibility', 0)) {
            $method = 'gsxFetchRepairEligibility';
            $params = array(
                'serial' => BimpTools::getValue('gsx_serial', ''),
                'id_sav' => BimpTools::getValue('gsx_id_sav', 0),
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
            $errors[] = 'ID du SAV absent 69';
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
            $errors[] = 'ID du SAV absent 70';
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
            $errors[] = 'ID du SAV absent 71';
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
            $errors[] = 'ID du SAV absent 72';
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
