<?php

require_once '../GSX.class.php';
require_once dirname(__FILE__) . '/shipment.class.php';

class GsxUps {

    public $gsx = null;
    public $connect = false;
    public $shiptTo = null;
    public $upsSoapErrors = array();
    public static $upsSoapLocation = 'http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0';
    public static $upsEndpointUrls = array(
        'shipment' => array(
            'test' => 'https://wwwcie.ups.com/webservices/Ship',
            'production' => 'https://onlinetools.ups.com/webservices/Ship'
        )
    );
//    public static $apiMode = 'ut';
        public static $apiMode = 'production';
    public static $upsCarrierCode = 'UPSWW065';
    public $upsConfig = array(
        'access' => "5CFD6AC514872584",
        'userid' => "BIMP74 DEV",
        'passwd' => "Express74"
    );

    public function __construct($shipTo = null) {
        $this->shiptTo = $shipTo;
    }

    protected function gsxInit() {
        global $user;

        if (defined('PRODUCTION_APPLE') && PRODUCTION_APPLE)
            self::$apiMode = 'production';

//        $userId = 'sav@bimp.fr';
//        $password = '@Savbimp2014#';
//        $serviceAccountNo = '100520';
//        $userId = 'tommy@drsi.fr';
//        $userId = 'contact@drsi.fr';
//        $serviceAccountNo = '897316';

        $userId = 'admin.gle@bimp.fr';
        $password = 'BIMP@gle69#';
        $serviceAccountNo = '897316';

        if (isset($user->array_options['options_apple_id']) && isset($user->array_options['options_apple_service']) &&
                $user->array_options['options_apple_id'] != "" && $user->array_options['options_apple_service'] != "")
            $details = array(
                'apiMode' => self::$apiMode,
                'regionCode' => 'emea',
                'userId' => $user->array_options['options_apple_id'],
//                'password' => $user->array_options['options_apple_mdp'],
                'serviceAccountNo' => $user->array_options['options_apple_service'],
                'languageCode' => 'fr',
                'userTimeZone' => 'CEST',
                'returnFormat' => 'php',
            );
        else
        if (isset($userId) && isset($password) && isset($serviceAccountNo)) {
            $details = array(
                'apiMode' => self::$apiMode,
                'regionCode' => 'emea',
                'userId' => $userId,
//                'password' => $password,
                'serviceAccountNo' => $serviceAccountNo,
                'languageCode' => 'fr',
                'userTimeZone' => 'CEST',
                'returnFormat' => 'php',
            );
        } else {
//            echo '<p class="error">Pas d\'identifiant apple.<a href="' . DOL_URL_ROOT . '/user/card.php?id=' . $user->id . '"> Corriger</a></p>' . "\n";
            return false;
        }
        $this->gsx = new GSX($details, false, self::$apiMode);
        if (count($this->gsx->errors['init']) || count($this->gsx->errors['soap'])) {
            return false;
        }
        $this->connect = true;
        return true;
    }

    protected function UPSRequest($operation, $wsdl, $endPointUrl, $datas) {
        try {
            $mode = array(
                'soap_version' => 'SOAP_1_1', // use soap 1.1 client
                'trace' => 1
            );

            $soapClient = new SoapClient($wsdl, $mode);

            $soapClient->__setLocation($endPointUrl);

            $usernameToken['Username'] = $this->upsConfig['userid'];
            $usernameToken['Password'] = $this->upsConfig['passwd'];
            $serviceAccessLicense['AccessLicenseNumber'] = $this->upsConfig['access'];
            $upss['UsernameToken'] = $usernameToken;
            $upss['ServiceAccessToken'] = $serviceAccessLicense;

            $header = new SoapHeader(self::$upsSoapLocation, 'UPSSecurity', $upss);
            $soapClient->__setSoapHeaders($header);

            $resp = $soapClient->__soapCall($operation, $datas);
            $return = $resp;
            return $return;
        } catch (Exception $ex) {
            $this->upsSoapErrors[] = $ex;
//	    print_r($ex->detail->Errors);
            if (isset($ex->detail->Errors))
                dol_syslog(print_r($ex->detail->Errors, 1), 3);
            else if (is_string($ex))
                dol_syslog($ex, 3);
            return 0;
        }
    }

    public function createUpsShipment() {
        if (!isset($this->shiptTo) || empty($this->shiptTo)) {
            return array(
                'status' => 0,
                'html' => '<p class="error">Erreur: numéro shipt-to absent</p>'
            );
        }
        $parts = isset($_POST['parts']) ? $_POST['parts'] : 0;
        $infos = isset($_POST['shipInfos']) ? $_POST['shipInfos'] : 0;

        $request = array(
            'Request' => array(
                'RequestOption' => 'nonvalidate'
            ),
            'Shipment' => array(
                'Shipper' => array(
                    'Name' => 'BIMP',
                    'AttentionName' => 'SAV',
                    'ShipperNumber' => 'R8X411',
                    'Address' => array(
                        'AddressLine' => '3 Rue du Vieux Moulin',
                        'City' => 'BREDA',
                        'StateProvinceCode' => '74',
                        'PostalCode' => '74960',
                        'CountryCode' => 'FR',
                    ),
                    'Phone' => array(
                        'Number' => '0450227597'
                    )
                ),
                'ShipTo' => array(
                    'Name' => 'Apple',
                    'AttentionName' => 'Distribution international',
                    'Address' => array(
                        'AddressLine' => 'Hekven 6',
                        'City' => 'BREDA',
                        'PostalCode' => '4824AE',
                        'CountryCode' => 'NL',
                    ),
                    'Phone' => array(
                        'Number' => '31-076572-2415',
                    )
                ),
                'ShipFrom' => array(
                    'Name' => 'BIMP',
                    'AttentionName' => 'SAV',
                    'Address' => array(
                        'AddressLine' => '3 Rue du Vieux Moulin',
                        'City' => 'BREDA',
                        'StateProvinceCode' => '74',
                        'PostalCode' => '74960',
                        'CountryCode' => 'FR',
                    ),
                    'Phone' => array(
                        'Number' => '0450227597',
                    )
                ),
                'PaymentInformation' => array(
                    'ShipmentCharge' => array(
                        'Type' => '01',
                        'BillReceiver' => array(
                            'AccountNumber' => '4W63V6',
                            'Address' => array(
                                'PostalCode' => '4824BM'
                            )
                        )
                    )
                ),
                'Service' => array(
                    'Code' => '11',
                    'Description' => 'Standard'
                ),
                'Package' => array(
                    'Description' => '',
                    'Packaging' => array(
                        'Code' => '02'
                    ),
                    'Dimensions' => array(
                        'UnitOfMeasurement' => array(
                            'Code' => 'CM',
                            'Description' => 'cm'
                        ),
                        'Length' => $infos['length'],
                        'Width' => $infos['width'],
                        'Height' => $infos['height']
                    ),
                    'PackageWeight' => array(
                        'UnitOfMeasurement' => array(
                            'Code' => 'KGS',
                            'Description' => 'kg'
                        ),
                        'Weight' => $infos['weight']
                    )
                ),
                'LabelSpecification' => array(
                    'HTTPUserAgent' => 'Mozilla/4.5',
                    'LabelImageFormat' => array(
                        'Code' => 'GIF',
                        'Description' => 'GIF'
                    )
                )
            )
        );

        $wsdl = dirname(__FILE__) . '/wsdl/Ship.wsdl';
//        if (self::$apiMode == 'ut')
        $endPointUrl = self::$upsEndpointUrls['shipment']['test'];
//        else
//            $endPointUrl = self::$upsEndpointUrls['shipment']['production'];
        $result = $this->UPSRequest('ProcessShipment', $wsdl, $endPointUrl, array($request));
        if (!$result) {
            return $this->displaySoapErrors();
        }
        $return = array(
            'status' => '',
            'html' => ''
        );
        if (isset($result->Response->ResponseStatus->Code) && $result->Response->ResponseStatus->Code == 1) {
            $result = $result->ShipmentResults;
            $charges = $result->ShipmentCharges;

            global $db;
            $ship = new shipment($db);
            $ship->shipTo = $this->shiptTo;
            $ship->setInfos($infos['length'], $infos['width'], $infos['height'], $infos['weight']);
            $ship->setUpsInfos(
                    (isset($charges->TransportationCharges->MonetaryValue) ? $charges->TransportationCharges->MonetaryValue : 0), (isset($charges->ServiceOptionsCharges->MonetaryValue) ? $charges->ServiceOptionsCharges->MonetaryValue : 0), (isset($charges->TotalCharges->MonetaryValue) ? $charges->TotalCharges->MonetaryValue : 0), (isset($result->BillingWeight->Weight) ? $result->BillingWeight->Weight : 0), (isset($result->PackageResults->TrackingNumber) ? $result->PackageResults->TrackingNumber : 0), (isset($result->ShipmentIdentificationNumber) ? $result->ShipmentIdentificationNumber : 0)
            );

            foreach ($parts as $part) {
                $ship->addPart($part['name'], $part['ref'], $part['newRef'], $part['poNumber'], $part['sroNumber'], $part['serial'], $part['returnNbr']);
            }

            $return['html'] .= '<div class="container tabBar">';
            $return['html'] .= '<p class="confirmation">Création de l\'expédition effectuée avec succès.</p>';
            if ($ship->create()) {
                if (count($ship->errors)) {
                    $return['html'] .= $ship->displayErrors();
                }
            } else {
                $return['html'] .= '<p class="error">Echec de l\'enregistremement des données de l\'expédition.</p>';
                $return['html'] .= $ship->displayErrors();
            }

            if (isset($result->PackageResults->ShippingLabel->GraphicImage)) {
                // à modif: 
                $fileName = dirname(__FILE__) . '/labels/ups/label' . $ship->upsInfos['trackingNumber'] . '.gif';
                if (!file_put_contents($fileName, base64_decode($result->PackageResults->ShippingLabel->GraphicImage))) {
                    $return['html'] .= '<p class="error">Echec de la création du fichier image de l\'étiquette de livraison<br/>';
                    $return['html'] .= '(nom du fichier: ' . $fileName . ')</p>';
                } else {
                    // à modif: 
                    $htmlFileName = dirname(__FILE__) . '/labels/ups/label' . $ship->upsInfos['trackingNumber'] . '.html';
                    if (!file_put_contents($htmlFileName, base64_decode($result->PackageResults->ShippingLabel->HTMLImage))) {
                        $return['html'] .= '<p class="error">Echec de la création du fichier html de l\'étiquette de livraison<br/>';
                        $return['html'] .= '(nom du fichier: ' . $htmlFileName . ')</p>';
                    }
                }
            }
            $return['html'] .= '</div>';
            $return['status'] = 1;
            $return['html'] .= $ship->getInfosHtml();
        } else {
            $return['status'] = 0;
            $return['html'] .= '<p class="error">Echec de la création de l\'expédition pour une raison inconnue (' .
                    $result->Response->ResponseStatus->Description . ')</p>';
        }
        return $return;
    }

    public function registerShipmentOnGsx() {
        if (!$this->connect) {
            if (!$this->gsxInit())
                return array(
                    'ok' => 0,
                    'html' => '<p class="error">Echec de la connexion au service GSX</p>' . $this->gsx->getGSXErrorsHtml()
                );
        }

//        if (self::$apiMode != 'ut') {
//            return array(
//                'ok' => 0,
//                'html' => '<p class="error">Attention, l\'API est en mode production</p>'
//            );
//        }

        $html = '';

        if (!isset($_POST['shipId']) || empty($_POST['shipId'])) {
            $html .= '<p class="error">Erreur: ID de l\'expédition absent</p>';
        }

        $this->gsx->resetSoapErrors();

        global $db;
        $ship = new shipment($db, $_POST['shipId']);

        if (!$ship->shipTo) {
            $html = '<p class="error">Echec du chargement des données de l\'expédition</p>';
            $html .= $ship->displayErrors();
            return array(
                'ok' => 0,
                'html' => $html
            );
        }

        if (!count($ship->parts)) {
            return array(
                'ok' => 0,
                'html' => '<p class="error">Erreur: aucun composant enregistré pour cette expédition</p>'
            );
        }

        $parts = array();
        foreach ($ship->parts as $p) {
            $parts[] = array(
                'returnOrderNumber' => $p['returnOrderNumber'],
                'partNumber' => isset($p['new_number']) && !empty($p['new_number']) ? $p['new_number'] : $p['number']
            );
        }

        $datas = array(
            'bulkReturnOrder' => $parts,
            'shipToCode' => $ship->shipTo,
            'carrierCode' => self::$upsCarrierCode,
            'trackingNumber' => $ship->upsInfos['trackingNumber'],
            'length' => $ship->infos['length'],
            'width' => $ship->infos['width'],
            'height' => $ship->infos['height'],
            'estimatedTotalWeight' => $ship->infos['weight'],
            'notes' => isset($_POST['notes']) ? $_POST['notes'] : '',
            'notaFiscalNumber' => ''
        );

        $soapClient = 'RegisterPartsForBulkReturn';
        $requestName = 'RegisterPartsForBulkReturnRequest';

        $request = $this->gsx->_requestBuilder($requestName, 'bulkPartsRegistrationRequest', $datas);
        $response = $this->gsx->request($request, $soapClient);

//        $response = array(
//            'RegisterPartsForBulkReturnResponse' => array(
//                'bulkPartsRegistrationResponse' => array(
//                    'packingListFileName' => 'temp.pdf',
//                    'packingList' => 'file',
//                    'bulkReturnId' => 'B01111',
//                    'trackingURL' => 'http://some_carrier.com/tracking',
//                    'confirmationMessage' => 'confirmed'
//                )
//            )
//        );
        if (isset($response)) {
            echo 'Requete ok<br/>';
            echo '<pre>';
            print_r($response);
            exit;

            if (isset($response['RegisterPartsForBulkReturnResponse']['bulkPartsRegistrationResponse'])) {
                $html = '<p class="confirmation">Enregistrement de l\'expédition effectuée avec succès</p>';
                $response = $response['RegisterPartsForBulkReturnResponse']['bulkPartsRegistrationResponse'];
                $fileCheck = false;
                if (isset($response['packingList']) && !empty($response['packingList']) &&
                        isset($response['packingListFileName']) && !empty($response['packingListFileName'])) {
                    $filePath = dirname(__FILE__) . '/label/gsx/packingList_' . $ship->rowid . '.pdf';
                    if (file_put_contents($filePath, $response['packingList'])) {
                        $ship->gsxInfos['pdfFileName'] = 'packingList_' . $ship->rowid . '.pdf';
                        $fileCheck = true;
                    }
                }
                if (!$fileCheck) {
                    $html .= '<p class="error">Echec de la création du fichier PDF pour la liste de composants</p>';
                }
                if (isset($response['bulkReturnId']) && !empty($response['bulkReturnId']))
                    $ship->gsxInfos['bulkReturnId'] = $response['bulkReturnId'];
                if (isset($response['trackingURL']) && !empty($response['trackingURL']))
                    $ship->gsxInfos['trackingURL'] = $response['trackingURL'];
                if (isset($response['confirmationMessage']) && !empty($response['confirmationMessage']))
                    $ship->gsxInfos['confirmation'] = $response['confirmationMessage'];
            }
            if (!$ship->update()) {
                $html .= '<p class="error">Echec de l\'enregistrement des informations retournées par GSX</p>';
                if (count($ship->errors)) {
                    $html .= $ship->displayErrors();
                }
                return array(
                    'ok' => 0,
                    'html' => $html
                );
            } else {
                $html .= $ship->getInfosHtml();
                return array(
                    'ok' => 1,
                    'html' => $html
                );
            }
        }

        if (count($this->gsx->errors['soap'])) {
            return array(
                'ok' => 0,
                'html' => $this->gsx->getGSXErrorsHtml()
            );
        }

        return array(
            'ok' => 0,
            'html' => '<p class="error">Pas de réponse</p>'
        );
    }
    
    public function loadBulkReturnProforma($shipId) {
        if (!$this->connect) {
            if (!$this->gsxInit())
                return array(
                    'ok' => 0,
                    'html' => '<p class="error">Echec de la connexion au service GSX</p>' . $this->gsx->getGSXErrorsHtml()
                );
        }
        
        global $db;
        $ship = new shipment($db, $shipId);
        if (!isset($ship->gsxInfos['bulkReturnId']) || empty($ship->gsxInfos['bulkReturnId'])) {
            return array(
                'ok' => 0,
                'html' => '<p class="error">Echec: numéro de retour non trouvé</p>'
            );
        }
        
        $soapClient = 'ViewBulkReturnProforma';
        $requestName = 'ViewBulkReturnProformaRequest';

        $request = $this->gsx->_requestBuilder($requestName, 'bulkReturnId', $ship->gsxInfos['bulkReturnId']);
        $response = $this->gsx->request($request, $soapClient);
        
        if (isset($response['ViewBulkReturnProformaResponse']['bulkReturnProformaInfo'])) {
            $response = $response['ViewBulkReturnProformaResponse']['bulkReturnProformaInfo'];
            if (isset($response['proformaFileData']) && !empty($response['proformaFileData'])) {
                // à modif:
                $fileName = dirname(__FILE__).'/labels/gsx/proforma_'.$ship->upsInfos['trackingNumber'].'.pdf';
                if (file_put_contents($fileName, $response['proformaFileData'])) {
                    return array(
                        'ok' => 1,
                        'html' => '<p class="confirmation">Fichier récupéré avec succès</p>'
                    );
                }
            }
        }
        return array(
            'ok' => 1,
            'html' => '<p class="error">Echec de la récupération du fichier</p>'
        );
    }

    public function getShipToForm() {
        $html = $this->starBloc('Nouvelle expédition', 'shipTo', true);

        $html .= '<div class="tabBar">';
        $html .= '<table class="border">';
        $html .= '<tbody>';
        $html .= '<tr>';
        $html .= '<td>Numéro shipt-to:</td>';
        $html .= '<td><input type="text" id="shipToNumber" name="shipToNumber" width="350px" value="0000462140"/>&nbsp;&nbsp;';
        $html .= '<input type="button" id="shipToSubmit" value="&nbsp;&nbsp;Ok&nbsp;&nbsp;"/></td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= $this->endBloc();

        $html .= '<div style="display: none" id="ajaxRequestResults"></div>';
        return $html;
    }

    public function getShippingForm() {
        $html = $this->starBloc('Choix des composants à expédier', 'partsList', true);
        $html .= $this->getPartsListHtml();
        $html .= $this->endBloc();

        $html .= $this->starBloc('Informations expédition', 'shippingInfos', true);
        $html .= $this->getShippingInfosForm();
        $html .= $this->endBloc();

        $html .= '<div id="shippingRequestResponseContainer" style="display: none"></div>';
        return $html;
    }

    protected function getPartsListHtml() {
        $parts = $this->getPartsPendingArray();

        $html = '<input type="hidden" id="shipToUsed" name="shiptToUsed" value="' . $this->shiptTo . '"/>';
        if ($parts === false) {
            $html .= $this->gsx->getGSXErrorsHtml();
        } else if (!count($parts)) {
            $html .= '<p>Aucun composant trouvé pour ce numéro ship-to</p>' . "\n";
        } else {
            $html .= '<div id="partListSearchForm"><p>';
            $html .= '<label for="partSearch">Rechercher: </label>';
            $html .= '<input type="text" id="partSearch" name="partSearch" style="width: 250px" value=""/>';
            $html .= '<span id="searchPartButton" class="button" onclick="searchPartList()">Rechercher</span>';
            $html .= '<span id="searchPartsReset" class="button" onclick="resetPartSearch()" style="display: none">Réinitialiser</span>';
            $html .= '</p>';
            $html .= '<p id="partSearchResults"></p>';
            $html .= '</div>';

            $html .= '<table id="partsPending"><thead><tr>' . "\n";
            $html .= '<th></th>' . "\n";
            $html .= '<th>Nom</th>' . "\n";
            $html .= '<th>Ref.</th>' . "\n";
            $html .= '<th>Nouvelle Ref.</th>' . "\n";
            $html .= '<th>N° de commande</th>';
            $html .= '<th>N° de réparation</th>' . "\n";
            $html .= '<th>N° de série du produit</th>' . "\n";
            $html .= '</tr></thead><tbody>' . "\n";
            $odd = false;
            $i = 1;
            foreach ($parts as $sro => $repairParts) {
                foreach ($repairParts as $p) {
                    $html .= '<tr id="part_' . $i . '" ' . ($odd ? ' class="odd"' : '') . '>' . "\n";
                    $html .= '<td><input class="partCheck" type="checkbox" name="parts[]"/></td>' . "\n";
                    $html .= '<td class="partName">' . $p['nom'] . '</td>' . "\n";
                    $html .= '<td class="partRef">' . $p['ref'] . '</td>' . "\n";
                    $html .= '<td class="partNewRef">' . $p['newRef'] . '</td>' . "\n";
                    $html .= '<td class="partPONumber">' . $p['poNumber'] . '</td>';
                    $html .= '<td class="partSroNumber">' . $sro . '</td>' . "\n";
                    $html .= '<td class="partSerial">' . $p['serial'] . '</td>' . "\n";
                    $html .= '<input type="hidden" class="partReturnOrderNumber" value="' . $p['returnOrderNumber'] . '"/>' . "\n";
                    $html .= '</tr>' . "\n";
                    $i++;
                    $odd = !$odd;
                }
            }
            $html .= '</tbody></table>' . "\n";
        }
        return $html;
    }

    protected function getPartsPendingArray() {
        $parts = array();

        if (!$this->connect) {
            if (!$this->gsxInit())
                return false;
        }

        $this->gsx->resetSoapErrors();

        $datas = array(
            'repairType' => '',
            'repairStatus' => '',
            'purchaseOrderNumber' => '',
            'sroNumber' => '',
            'repairConfirmationNumber' => '',
            'serialNumbers' => array(
                'serialNumber' => ''
            ),
            'shipToCode' => $this->shiptTo,
            'customerFirstName' => '',
            'customerLastName' => '',
            'customerEmailAddress' => '',
            'createdFromDate' => '',
            'createdToDate' => '',
            'warrantyType' => '',
            'kbbSerialNumberFlag' => '',
            'comptiaCode' => '',
        );

        $soapClient = 'PartsPendingReturn';
        $requestName = 'PartsPendingReturnRequest';

        $request = $this->gsx->_requestBuilder($requestName, 'repairData', $datas);
        $response = $this->gsx->request($request, $soapClient);

        if (count($this->gsx->errors['soap'])) {
            return false;
        }

        if (isset($response['PartsPendingReturnResponse']['partsPendingResponse'])) {
            $response = $response['PartsPendingReturnResponse']['partsPendingResponse'];

            foreach ($response as $part) {
//                if (isset($part['registeredForReturn']) && $part['registeredForReturn'] == 'Y')
//                    continue;

                if (!isset($parts[$part['sroNumber']]))
                    $parts[$part['sroNumber']] = array();

                $newRef = false;
                if (isset($part['originalPartNumber']) && $part['originalPartNumber'] != '')
                    $newRef = true;

                $parts[$part['sroNumber']][] = array(
                    'nom' => $part['partDescription'],
                    'ref' => $newRef ? $part['originalPartNumber'] : $part['partNumber'],
                    'newRef' => $newRef ? $part['partNumber'] : '',
                    'serial' => $part['serialNumber'],
                    'returnOrderNumber' => $part['returnOrderNumber'],
                    'poNumber' => $part['purchaseOrderNumber']
                );
            }
        }
        return $parts;
    }

    protected function getShippingInfosForm() {
        $html = '<div class="tabBar">' . "\n";
        $html .= '<table class="border">' . "\n";
        $html .= '<tbody>';

        $html .= '<tr>';
        $html .= '<td>Composants à envoyer</td>';
        $html .= '<td id="partsListRecapContainer">';
        $html .= '<table style="display: none">';
        $html .= '<thead><tr>';
        $html .= '<th>Nom</th>';
        $html .= '<th>Réf.</th>';
        $html .= '<th>Nouv. Réf.</th>';
        $html .= '<th>N° de commande</th>';
        $html .= '<th>N° de série</th>';
        $html .= '<th>Réparation</th>';
        $html .= '<th></th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '<span id="partsListCheck"></span>';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td>Longueur du colis</td>';
        $html .= '<td><input type="text" id="length" name="length" class="shipInfo" width="250px"/>&nbsp;cm';
        $html .= '<span class="inputCheckInfos"></span>';
        $html .= '</td></tr>';


        $html .= '<tr>';
        $html .= '<td>Largeur du colis</td>';
        $html .= '<td><input type="text" id="width" name="width" class="shipInfo" width="250px"/>&nbsp;cm';
        $html .= '<span class="inputCheckInfos"></span>';
        $html .= '</td></tr>';

        $html .= '<tr>';
        $html .= '<td>Hauteur du colis</td>';
        $html .= '<td><input type="text" id="height" name="height" class="shipInfo" width="250px"/>&nbsp;cm';
        $html .= '<span class="inputCheckInfos"></span>';
        $html .= '</td></tr>';

        $html .= '<tr>';
        $html .= '<td>Poids du colis</td>';
        $html .= '<td><input type="text" id="weight" name="weight" class="shipInfo" width="250px"/>&nbsp;kg';
        $html .= '<span class="inputCheckInfos"></span>';
        $html .= '</td></tr>';

        $html .= '</tbody></table>' . "\n";
        $html .= '<p style="text-align: center">';
        $html .= '<input type="button" class="button" id="createShipping" value="Créer une nouvelle expédition"/>';
        $html .= '</p>';
        $html .= '</div>' . "\n";
        return $html;
    }

    public function getCurrentShipmentsHtml($n = 0) {
        $html = '';
        global $db;

        if (isset($_REQUEST['n']) && !empty($_REQUEST['n'])) {
            $n = $_REQUEST['n'];
        }
        $n += 15;
        $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX . 'synopsisapple_shipment ORDER BY `rowid` DESC LIMIT 0, ' . $n;

        $result = $db->query($sql);
        $displayMore = true;

        if ($db->num_rows($result) > 0) {
            $html .= $this->starBloc('Liste des expéditions en cours', 'currentShipmentList', isset($_REQUEST['n']));
            $html .= '<div class="tabBar">';
            $html .= '<table id="currentShipmentList"><thead>';
            $html .= '<tr>';
            $html .= '<th style="min-width: 100px">ID</th>';
            $html .= '<th style="min-width: 220px">N° de suivi</th>';
            $html .= '<th style="min-width: 160px">ID de retour Apple</th>';
            $html .= '<th></th>';
            $html .= '<th></th>';
            $html .= '</tr>';
            $html .= '</thead></tbody>';
            while ($datas = $db->fetch_object($result)) {
                $html .= '<tr>';
                $html .= '<td>' . $datas->rowid . '</td>';

                $html .= '<td>';
                if (isset($datas->tracking_number) && !empty($datas->tracking_number))
                    $html .= $datas->tracking_number;
                $html .= '</td>';

                $html .= '<td>';
                if (isset($datas->gsx_return_id) && !empty($datas->gsx_return_id))
                    $html .= $datas->gsx_return_id;
                $html .= '</td>';

                $html .= '<td>';
                if (isset($datas->gsx_tracking_url) && !empty($datas->gsx_tracking_url))
                    $html .= '<a class="button" href="' . $datas->gsx_tracking_url . '" target="_blank">Page de suivi</a>';
                $html .= '</td>';

                $html .= '<td>';
                $html .= '<span class="button" onclick="window.location = \'./retour.php?shipId=' . $datas->rowid . '\'">Afficher les détails</span>';
                $html .= '</td>';

                $html .= '</tr>';
                if ($datas->rowid <= 1) {
                    $displayMore = false;
                }
            }
            $html .= '</tbody></table>';

            if ($displayMore) {
                $html .= '<p style="text-align: center"><button class="button"';
                $html .= ' onclick="window.location = \'./retour.php?n=' . $n . '\'"';
                $html .= '>Afficher plus</button></p>';
            }
            $html .= '</div>';
            $html .= $this->endBloc();
        }

        return $html;
    }
    
    protected function starBloc($title, $containerId, $open = false) {
        $html = '<div id="' . $containerId . '" class="container">' . "\n";
        $html .= '<div class="captionContainer" onclick="onCaptionClick($(this))">' . "\n";
        $html .= '<span class="captionTitle">' . $title . '</span>' . "\n";
        $html .= '<span class="arrow ' . ($open ? 'upArrow' : 'downArrow') . '"></span>';
        $html .= '</div>' . "\n";
        $html .= '<div class="blocContent"' . (!$open ? ' style="display: none"' : '') . '>' . "\n";
        return $html;
    }

    protected function endBloc() {
        return '</div></div>' . "\n";
    }

    public function displaySoapErrors() {
        $html = '';
        if (count($this->upsSoapErrors)) {
            $html .= '<p class="error">Erreur(s) SOAP:<br/>';
            $i = 1;
            foreach ($this->upsSoapErrors as $error) {
                $html .= $i . '. ' . $error . '<br/>';
                $i++;
            }
            $html .= '</p>';
        }
        return $html;
    }

}
