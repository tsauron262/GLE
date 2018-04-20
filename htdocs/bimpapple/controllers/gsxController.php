<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX.class.php';

class gsxController extends BimpController
{

    public static $apiMode = 'production';
    protected $userExchangePrice = true;
    public $gsx = null;
    protected $serial = null;
    public $partsPending = null;
    public $connect = false;
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

    public function initGsx($userId = null, $password = null, $serviceAccountNo = null, $requestType = false)
    {
        global $user;

        if (defined('PRODUCTION_APPLE') && PRODUCTION_APPLE) {
            self::$apiMode = 'production';
        }
//
//        $userId = 'sav@bimp.fr';
//        $password = '@Savbimp2014#';
//        $serviceAccountNo = '100520';
        $userId = 'admin.gle@bimp.fr';
//        $password = 'BIMP@gle69#';
        $serviceAccountNo = '897316';
        $serviceAccountNoShipTo = '897316';

        if (isset($user->array_options['options_apple_id']) && isset($user->array_options['options_apple_service']) &&
                $user->array_options['options_apple_id'] != "" && $user->array_options['options_apple_service'] != "") {
            $userId = $user->array_options['options_apple_id'];
            $serviceAccountNo = $user->array_options['options_apple_service'];
            $serviceAccountNoShipTo = $user->array_options['options_apple_shipto'];
        }

        if (isset($userId) && isset($serviceAccountNo)) {
            $details = array(
                'apiMode'                => self::$apiMode,
                'regionCode'             => 'emea',
                'userId'                 => $userId,
//                'password' => $password,
                'serviceAccountNo'       => $serviceAccountNo,
                'serviceAccountNoShipTo' => $serviceAccountNoShipTo,
                'languageCode'           => 'fr',
                'userTimeZone'           => 'CEST',
                'returnFormat'           => 'php',
            );
            $this->shipTo = $serviceAccountNoShipTo;

            if (in_array($requestType, $this->tabReqForceIphone)) {
                $this->isIphone = true;
            }
            if (in_array($requestType, $this->tabReqForceNonIphone)) {
                $this->isIphone = false;
            }

            $this->gsx = new GSX($details, $this->isIphone, self::$apiMode);

            if (!count($this->gsx->errors['init']) && !count($this->gsx->errors['soap'])) {
                $this->connect = true;
            }
        } else {
            return array('Pas d\'identifiant apple&nbsp;&nbsp;<a href="' . DOL_URL_ROOT . '/user/card.php?id=' . $user->id . '"> Corriger</a>');
        }

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

        if ($this->connect) {
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

        if ($this->connect) {
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

        if ($this->connect) {
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

        if ($this->connect) {
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

//                    $lookUpContent .= '</tbody></table>' . "\n";
//                    $lookUpContent .= '</td>' . "\n";
//                    $lookUpContent .= '</tr>' . "\n";
                        $lookUpContent .= '</tbody></table>';

//                        $lookUpContent .= $this->getRepairsHtml($prodId);
//                        $lookUpContent .= $this->getCartHtml($prodId);
//                        global $db;
//                        $cart = new partsCart($db, $this->serial, isset($_GET['chronoId']) ? $_GET['chronoId'] : null);
//                        $cart->loadCart();
//                        if (count($cart->partsCart)) {
//                            $lookUpContent .= '<script type="text/javascript">' . "\n";
//                            $lookUpContent .= $cart->getJsScript($prodId);
//                            $lookUpContent .= '</script>' . "\n";
//                        }

                        $gsx_content = BimpRender::renderPanel('Informations produit', $lookUpContent, '', array(
                                    'type'     => 'default',
                                    'icon'     => 'info-circle',
                                    'foldable' => true
                        ));

//                        $footer = '';
//                        $footer .= '<div style="text-align: right">';
//                        $footer .= BimpRender::renderButton(array(
//                                    'classes'     => array('btn', 'btn-default'),
//                                    'label'       => 'Enregistrer le panier',
//                                    'icon_before' => 'save',
//                                    'attr'        => array(
//                                        'onclick' => 'savePartCart($(this), ' . $sav->id . ');'
//                                    )
//                                        ), 'button');
//                        $footer .= '</div>';
//                        $gsx_content .= BimpRender::renderPanel('Panier de composants', $this->renderPartsCart($sav), $footer, array(
//                                    'type'     => 'default',
//                                    'icon'     => 'shopping-basket',
//                                    'foldable' => true
//                        ));

                        $part = BimpObject::getInstance('bimpsupport', 'BS_ApplePart');
                        $list = new BC_ListTable($part, 'default', 1, $sav->id);
                        $gsx_content .= $list->renderHtml();



                        $gsx_content .= BimpRender::renderPanel('Réparations', $repairs_content, '', array(
                                    'type'     => 'secondary',
                                    'icon'     => 'wrench',
                                    'foldable' => true
                        ));

                        $gsx_content .= BimpRender::renderPanel('Liste des composants Apple comptatibles', '', '', array(
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
        
        $html .= '<div class="buttonsContainer align-right">';
        $html .= '<button type="button" class="btn btn-default" onclick="gsxImportForm.slideDown(250);">';
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
            if (!count($errors) && $this->connect) {
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

    public static function dateAppleToDate($date)
    {
        $garantieT = explode("/", $date);
        if (isset($garantieT[2]))
            return $garantieT[0] . "/" . $garantieT[1] . "/20" . $garantieT[2];
        else
            return "";
    }
}
