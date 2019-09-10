<?php

require_once DOL_DOCUMENT_ROOT.'/synopsisapple/GSX.class.php';
require_once dirname(__FILE__) . '/shipment.class.php';

ini_set('display_errors', 1);

class GsxUps
{

    public static $shipToAdresses = array(
        1 => array(
            'label'       => 'B&W (Purple Label)',
            'Name'        => 'DB Schenker',
            'Attention'   => 'B&W',
            'AddressLine' => 'Hekven 6',
            'City'        => 'Breda',
            'PostalCode'  => '4824 AE',
            'CountryCode' => 'NL',
            'PhoneNumber' => '31-076572-2415'
        ),
        2 => array(
            'label'       => 'KBB (Brown Label)',
            'Name'        => 'DB Schenker',
            'Attention'   => 'KBB',
            'AddressLine' => 'Hekven 6',
            'City'        => 'Breda',
            'PostalCode'  => '4824 AE',
            'CountryCode' => 'NL',
            'PhoneNumber' => '31-076572-2415'
        ),
        3 => array(
            'label'       => 'LITHIUM BATTERIES',
            'Name'        => 'DB Schenker',
            'Attention'   => 'LITHIUM BATTERIES',
            'AddressLine' => 'Hekven 6',
            'City'        => 'Breda',
            'PostalCode'  => '4824 AE',
            'CountryCode' => 'NL',
            'PhoneNumber' => '31-076572-2415'
        ),
        4 => array(
            'label'       => 'RC (Yellow Label)',
            'Name'        => 'Pegatron Czech',
            'Attention'   => 'RC',
            'AddressLine' => 'Na Rovince 862',
            'City'        => 'Ostrava',
            'PostalCode'  => '720 00',
            'CountryCode' => 'CZ',
            'PhoneNumber' => ''
        ),
        5 => array(
            'label'       => 'ROR (Red Label)',
            'Name'        => 'DB Schenker',
            'Attention'   => 'RoR',
            'AddressLine' => 'Hekven 6',
            'City'        => 'Breda',
            'PostalCode'  => '4824 AE',
            'CountryCode' => 'NL',
            'PhoneNumber' => '31-076572-2415'
        ),
    );
    public $gsx = null;
    public $connect = false;
    public $shiptTo = null;
    public $shipmentShipToKey = 0;
    public $upsSoapErrors = array();
    public static $upsSoapLocation = 'http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0';
    public static $upsEndpointUrls = array(
        'shipment' => array(
            'test'       => 'https://wwwcie.ups.com/webservices/Ship',
            'production' => 'https://onlinetools.ups.com/webservices/Ship'
        )
    );
    public static $upsMode = 'production';
//    public static $apiMode = 'ut';
    public static $apiMode = 'production';
    public static $upsCarrierCode = 'UPSWW065';
    public $upsConfig = array(
        'access' => "5CFD6AC514872584",
        'userid' => "BIMP74 DEV",
        'passwd' => "Express74"
    );

    public function __construct($shipTo = null)
    {
        $this->shiptTo = $shipTo;
    }

    protected function gsxInit()
    {
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
                'apiMode'          => self::$apiMode,
                'regionCode'       => 'emea',
                'userId'           => $user->array_options['options_apple_id'],
//                'password' => $user->array_options['options_apple_mdp'],
                'serviceAccountNo' => $user->array_options['options_apple_service'],
                'languageCode'     => 'fr',
                'userTimeZone'     => 'CEST',
                'returnFormat'     => 'php',
            );
        else
        if (isset($userId) && isset($password) && isset($serviceAccountNo)) {
            $details = array(
                'apiMode'          => self::$apiMode,
                'regionCode'       => 'emea',
                'userId'           => $userId,
//                'password' => $password,
                'serviceAccountNo' => $serviceAccountNo,
                'languageCode'     => 'fr',
                'userTimeZone'     => 'CEST',
                'returnFormat'     => 'php',
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

    protected function UPSRequest($operation, $wsdl, $endPointUrl, $datas)
    {
        try {
            $mode = array(
                'soap_version' => 'SOAP_1_1', // use soap 1.1 client
                'trace'        => 1
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

    public function createUpsShipment()
    {
        $errors = array();

        if (!isset($this->shiptTo) || empty($this->shiptTo)) {
            $errors[] = 'numéro shipt-to absent';
        }

        $parts = isset($_POST['parts']) ? $_POST['parts'] : 0;
        $infos = isset($_POST['shipInfos']) ? $_POST['shipInfos'] : 0;

        if (!$parts)
            $errors[] = 'Aucun composant associé à cette expédition';
//        $parts = array(
//            array(
//                'name' => 'TEST',
//                'ref' => '123456789',
//                'newRef' => '123456789',
//                'poNumber' => '123456789',
//                'serial' => '123456789',
//                'returnNbr' => '123456789',
//                'expectedReturn' => '2016-12-01'
//            )
//        );
        if (!$infos)
            $errors[] = 'Aucune informartion sur les dimensions et le poids du colis reçu';
        if (!$infos['shipToKey'])
            $errors[] = 'destinataire non sélectionné';

        if (!array_key_exists($this->shiptTo, shipToList::$list))
            $errors[] = 'Numéro ship-to invalide';

        if (count($errors)) {
            $html = '<p class="error">Des erreurs ont été détectées: <br/>';
            foreach ($errors as $error) {
                $html .= '- ' . $error . '.<br/>';
            }
            $html .= '</p>';
            return array(
                'ok'   => 0,
                'html' => $html
            );
        }

        $shipToAdress = self::$shipToAdresses[$infos['shipToKey']];
        $shipToInfos = shipToList::$list[$this->shiptTo];
        global $db;

        if (isset($infos['upsTrackingNumber']) && !empty($infos['upsTrackingNumber'])) {
            $ship = new shipment($db);
            $ship->shipTo = $this->shiptTo;
            $ship->setInfos($infos['length'], $infos['width'], $infos['height'], $infos['weight']);
            $ship->upsInfos['trackingNumber'] = $infos['upsTrackingNumber'];
            $ship->upsInfos['identificationNumber'] = $infos['upsTrackingNumber'];
            foreach ($parts as $part) {
                $ship->addPart($part['name'], $part['ref'], $part['newRef'], $part['poNumber'], $part['sroNumber'], $part['serial'], $part['returnNbr'], $part['expectedReturn']);
            }

            $return['html'] .= '<div class="container tabBar">';
            if ($ship->create()) {
                if (count($ship->errors)) {
                    $return['html'] .= $ship->displayErrors();
                } else {
                    $return['html'] .= '<p class="confirmation">Enregistrement de l\'expédition effectuée avec succès.</p>';
                }
            } else {
                $return['html'] .= '<p class="error">Echec de l\'enregistremement des données de l\'expédition.</p>';
                $return['html'] .= $ship->displayErrors();
            }

            $return['html'] .= '</div>';
            $return['status'] = 1;
            $return['html'] .= $ship->getInfosHtml();
        } else {
            $request = array(
                'Request'  => array(
                    'RequestOption' => 'nonvalidate'
                ),
                'Shipment' => array(
                    'Description'        => "Retour de pièces"
                    ,
                    'Shipper'            => array(
                        'Name'          => $shipToInfos['Name'],
                        'AttentionName' => $shipToInfos['AttentionName'],
                        'ShipperNumber' => '4W63V6',
                        'Address'       => array(
                            'AddressLine'       => $shipToInfos['Address']['AddressLine'],
                            'City'              => $shipToInfos['Address']['City'],
                            'StateProvinceCode' => $shipToInfos['Address']['StateProvinceCode'],
                            'PostalCode'        => '4824BM',
                            'CountryCode'       => $shipToInfos['Address']['CountryCode'],
                        ),
                        'Phone'         => array(
                            'Number' => $shipToInfos['Phone']['Number']
                        )
                    ),
                    'ShipTo'             => array(
                        'Name'          => $shipToAdress['Name'],
                        'AttentionName' => $shipToAdress['Attention'],
                        'Address'       => array(
                            'AddressLine' => $shipToAdress['AddressLine'],
                            'City'        => $shipToAdress['City'],
                            'PostalCode'  => $shipToAdress['PostalCode'],
                            'CountryCode' => $shipToAdress['CountryCode'],
                        ),
                        'Phone'         => array(
                            'Number' => $shipToAdress['PhoneNumber'],
                        )
                    ),
                    'ReturnService'      => array(
                        'Code' => 3,
                    ),
                    'ShipFrom'           => array(
                        'Name'          => $shipToInfos['Name'],
                        'AttentionName' => $shipToInfos['AttentionName'],
                        'ShipperNumber' => $shipToInfos['ShipperNumber'],
                        'Address'       => array(
                            'AddressLine'       => $shipToInfos['Address']['AddressLine'],
                            'City'              => $shipToInfos['Address']['City'],
                            'StateProvinceCode' => $shipToInfos['Address']['StateProvinceCode'],
                            'PostalCode'        => $shipToInfos['Address']['PostalCode'],
                            'CountryCode'       => $shipToInfos['Address']['CountryCode'],
                        ),
                        'Phone'         => array(
                            'Number' => $shipToInfos['Phone']['Number']
                        )
                    ),
                    'PaymentInformation' => array(
                        'ShipmentCharge' => array(
                            'Type'         => '01',
                            'BillReceiver' => array(
                                'AccountNumber' => '4W63V6',
                                'Address'       => array(
                                    'PostalCode' => '4824BM'
                                )
                            )
                        )
                    ),
                    'Service'            => array(
                        'Code'        => ($infos['shipToKey'] == 3) ? '11' : '07',
                        'Description' => ($infos['shipToKey'] == 3) ? 'Standard' : 'Express'
                    ),
                    'Package'            => array(
                        'Description'   => 'retour pièces',
                        'Packaging'     => array(
                            'Code' => '02'
                        ),
                        'Dimensions'    => array(
                            'UnitOfMeasurement' => array(
                                'Code'        => 'CM',
                                'Description' => 'cm'
                            ),
                            'Length'            => $infos['length'],
                            'Width'             => $infos['width'],
                            'Height'            => $infos['height']
                        ),
                        'PackageWeight' => array(
                            'UnitOfMeasurement' => array(
                                'Code'        => 'KGS',
                                'Description' => 'kg'
                            ),
                            'Weight'            => $infos['weight']
                        )
                    ),
                    'LabelSpecification' => array(
                        'HTTPUserAgent'    => 'Mozilla/4.5',
                        'LabelImageFormat' => array(
                            'Code'        => 'GIF',
                            'Description' => 'GIF'
                        )
                    )
                )
            );
            dol_syslog(print_r($request, true), 3);

            $wsdl = dirname(__FILE__) . '/wsdl/Ship.wsdl';
            if (self::$upsMode == 'test')
                $endPointUrl = self::$upsEndpointUrls['shipment']['test'];
            else
                $endPointUrl = self::$upsEndpointUrls['shipment']['production'];
            $result = $this->UPSRequest('ProcessShipment', $wsdl, $endPointUrl, array($request));
//            $result = null;
            if (!$result) {
                return $this->displaySoapErrors();
            }
            $return = array(
                'status' => '',
                'html'   => ''
            );
            if (isset($result->Response->ResponseStatus->Code) && $result->Response->ResponseStatus->Code == 1) {
                $result = $result->ShipmentResults;
                $charges = $result->ShipmentCharges;
                ;
                $ship = new shipment($db);
                $ship->shipTo = $this->shiptTo;
                $ship->setInfos($infos['length'], $infos['width'], $infos['height'], $infos['weight']);
                $ship->setUpsInfos(
                        (isset($charges->TransportationCharges->MonetaryValue) ? $charges->TransportationCharges->MonetaryValue : 0), (isset($charges->ServiceOptionsCharges->MonetaryValue) ? $charges->ServiceOptionsCharges->MonetaryValue : 0), (isset($charges->TotalCharges->MonetaryValue) ? $charges->TotalCharges->MonetaryValue : 0), (isset($result->BillingWeight->Weight) ? $result->BillingWeight->Weight : 0), (isset($result->PackageResults->TrackingNumber) ? $result->PackageResults->TrackingNumber : 0), (isset($result->ShipmentIdentificationNumber) ? $result->ShipmentIdentificationNumber : 0)
                );

                foreach ($parts as $part) {
                    $ship->addPart($part['name'], $part['ref'], $part['newRef'], $part['poNumber'], $part['sroNumber'], $part['serial'], $part['returnNbr'], $part['expectedReturn']);
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
                    $filesDir = $ship->getFilesDir();

                    if ($filesDir) {
                        if (!file_put_contents($filesDir . '/ups.gif', base64_decode($result->PackageResults->ShippingLabel->GraphicImage))) {
                            $return['html'] .= '<p class="error">Echec de la création du fichier image de l\'étiquette de livraison<br/>';
                            $return['html'] .= '(Fichier: ' . $filesDir . '/ups.gif' . ')</p>';
                        }
                    }

                    // to remove:
//                $fileName = dirname(__FILE__) . '/labels/' . $ship->upsInfos['trackingNumber'] . '/ups.gif';
//                if (!file_exists(dirname(__FILE__) . '/labels/' . $ship->upsInfos['trackingNumber']))
//                    mkdir(dirname(__FILE__) . '/labels/' . $ship->upsInfos['trackingNumber']);
//                if (!file_put_contents($fileName, base64_decode($result->PackageResults->ShippingLabel->GraphicImage))) {
//                    $return['html'] .= '<p class="error">Echec de la création du fichier image de l\'étiquette de livraison<br/>';
//                    $return['html'] .= '(chemin du fichier: ' . $fileName . ')</p>';
//                }
                    // ---------
                }
                $return['html'] .= '</div>';
                $return['status'] = 1;
                $return['html'] .= $ship->getInfosHtml();
            } else {
                $return['status'] = 0;
                $return['html'] .= '<p class="error">Echec de la création de l\'expédition pour une raison inconnue (' .
                        $result->Response->ResponseStatus->Description . ')</p>';
            }
        }
        return $return;
    }

    public function registerShipmentOnGsx()
    {
        if (!$this->connect) {
            if (!$this->gsxInit())
                return array(
                    'ok'   => 0,
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
                'ok'   => 0,
                'html' => $html
            );
        }

        if (!count($ship->parts)) {
            return array(
                'ok'   => 0,
                'html' => '<p class="error">Erreur: aucun composant enregistré pour cette expédition</p>'
            );
        }

        $parts = array();
        foreach ($ship->parts as $p) {
            $parts[] = array(
                'returnOrderNumber' => $p['returnOrderNumber'],
                'partNumber'        => isset($p['new_number']) && !empty($p['new_number']) ? $p['new_number'] : $p['number']
            );
        }

        $datas = array(
            'bulkReturnOrder'      => $parts,
            'shipToCode'           => $ship->shipTo,
            'carrierCode'          => self::$upsCarrierCode,
            'trackingNumber'       => $ship->upsInfos['trackingNumber'],
            'length'               => $ship->infos['length'],
            'width'                => $ship->infos['width'],
            'height'               => $ship->infos['height'],
            'estimatedTotalWeight' => $ship->infos['weight'],
            'notes'                => isset($_POST['notes']) ? $_POST['notes'] : '',
            'notaFiscalNumber'     => ''
        );

        $soapClient = 'RegisterPartsForBulkReturn';
        $requestName = 'RegisterPartsForBulkReturnRequest';

        $request = $this->gsx->_requestBuilder($requestName, 'bulkPartsRegistrationRequest', $datas);
        $response = $this->gsx->request($request, $soapClient);
        if (isset($response['RegisterPartsForBulkReturnResponse']['bulkPartsRegistrationData'])) {
            $html = '<p class="confirmation">Enregistrement de l\'expédition effectuée avec succès</p>';
            $response = $response['RegisterPartsForBulkReturnResponse']['bulkPartsRegistrationData'];
            print_r($response);
            if (isset($response['packingList']) && !empty($response['packingList'])) {
                $fileDir = $ship->getFilesDir();
                $fileCheck = false;
                if ($fileDir) {
                    if (file_put_contents($fileDir . '/PackingList.pdf', $response['packingList']))
                        $fileCheck = true;
                }
                if (!$fileCheck)
                    $html .= '<p class="error">Echec de la création du fichier PDF pour la liste de composants</p>';

                // to remove:
//                if (!file_exists(dirname(__FILE__) . "/labels/" . $ship->upsInfos['trackingNumber']))
//                    mkdir(dirname(__FILE__) . "/labels/" . $ship->upsInfos['trackingNumber']);
//                $filePath = dirname(__FILE__) . "/labels/" . $ship->upsInfos['trackingNumber'] . '/packinglist.pdf';
//                file_put_contents($filePath, $response['packingList']);
                // ---------
            }
            if (isset($response['bulkReturnId']) && !empty($response['bulkReturnId']))
                $ship->gsxInfos['bulkReturnId'] = $response['bulkReturnId'];
            if (isset($response['trackingURL']) && !empty($response['trackingURL']))
                $ship->gsxInfos['trackingURL'] = $response['trackingURL'];
            if (isset($response['confirmationMessage']) && !empty($response['confirmationMessage']))
                $ship->gsxInfos['confirmation'] = $response['confirmationMessage'];

            if (!$ship->update()) {
                $html .= '<p class="error">Echec de l\'enregistrement des informations retournées par GSX</p>';
                if (count($ship->errors)) {
                    $html .= $ship->displayErrors();
                }
                return array(
                    'ok'   => 0,
                    'html' => $html
                );
            } else {
                $html .= $ship->getInfosHtml();
                return array(
                    'ok'   => 1,
                    'html' => $html
                );
            }
        }

        if (count($this->gsx->errors['soap'])) {
            return array(
                'ok'   => 0,
                'html' => $this->gsx->getGSXErrorsHtml()
            );
        }

        return array(
            'ok'   => 0,
            'html' => '<p class="error">Pas de réponse</p>'
        );
    }

    public function loadPartsReturnLabels($shipId)
    {
        if (!$this->connect) {
            if (!$this->gsxInit())
                return array(
                    'ok'   => 0,
                    'html' => '<p class="error">Echec de la connexion au service GSX</p>' . $this->gsx->getGSXErrorsHtml()
                );
        }

        global $db, $conf;
        $ship = new shipment($db, $shipId);
        if (!isset($ship->ref) || empty($ship->ref)) {
            return array(
                'ok'   => 0,
                'html' => '<p class="error">Erreur: numéro de suivi UPS absent</p>'
            );
        }
        if (!isset($ship->gsxInfos['bulkReturnId']) || empty($ship->gsxInfos['bulkReturnId'])) {
            return array(
                'ok'   => 0,
                'html' => '<p class="error">Erreur: numéro de retour GSX absent</p>'
            );
        }

        if (!count($ship->parts)) {
            return array(
                'ok'   => 0,
                'html' => '<p class="error">Aucun composant enregistré pour cette expédition.</p>'
            );
        }

        $errors = array();
        $filesDir = $ship->getFilesDir();

        if (!$filesDir) {
            return array(
                'ok'   => 0,
                'html' => '<p class="error">Echec de la création du dossier "' . $filesDir . '"</p>'
            );
        }

        $filesDir .= '/labels';

        if (!file_exists($filesDir))
            if (!mkdir($filesDir)) {
                return array(
                    'ok'   => 0,
                    'html' => '<p class="error">Echec de la création du dossier "' . $filesDir . '"</p>'
                );
            }

        $filesDir .= '/';

        // to remove:
//        $filesDir2 = dirname(__FILE__) . '/labels/' . $ship->ref;
//        if (!file_exists($filesDir2))
//            mkdir($filesDir2);
//        $filesDir2 .= '/';
        // ---------

        $soapClient = 'ReturnLabel';
        $requestName = 'ReturnLabelRequest';

        foreach ($ship->parts as $partRowId => $part) {
            if ((!isset($part['number']) || empty($part['number'])) && (!isset($part['new_number']) || empty($part['new_number']))) {
                $errors[] = 'Pas de partNumber enregistré pour le composant d\'ID ' . $partRowId;
                continue;
            }
            if (!isset($part['returnOrderNumber']) || empty($part['returnOrderNumber'])) {
                $errors[] = 'Pas de numéro de retour enregistré pour le composant d\'ID ' . $partRowId;
                continue;
            }
            $partNumber = (isset($part['new_number']) && !empty($part['new_number'])) ? $part['new_number'] : $part['number'];
            $fileName = 'label_' . $part['returnOrderNumber'] . '_' . $partNumber . '.pdf';

            $fileName = str_replace("/", "_", $fileName);

            if (file_exists($filesDir . $fileName))
                continue;

            $datas = array(
                'partNumber'        => $partNumber,
                'returnOrderNumber' => $part['returnOrderNumber']
            );

            $request = $this->gsx->_requestBuilder($requestName, '', $datas);
            $response = $this->gsx->request($request, $soapClient);

            if (isset($response['ReturnLabelResponse']['returnLabelData'])) {
                $response = $response['ReturnLabelResponse']['returnLabelData'];
                if (isset($response['returnLabelFileData']) && !empty($response['returnLabelFileData'])) {

                    // to remove:
//                    file_put_contents($filesDir2 . $fileName, $response['returnLabelFileData']);
                    // ---------

                    if (file_put_contents($filesDir . $fileName, $response['returnLabelFileData']))
                        continue;
                }
            }
            $errors[] = 'Echec de la création du fichier "' . $fileName . '" pour le composant d\'ID ' . $partRowId;
        }

        if (count($errors)) {
            $html = '<p class="error">des erreurs sont survenues: <br/>';
            foreach ($errors as $e) {
                $html .= '- ' . $e . '.<br/>';
            }
            $html .= '</p>';
            return array(
                'ok'   => 0,
                'html' => $html
            );
        }

        require_once(DOL_DOCUMENT_ROOT . "/synopsisapple/core/modules/synopsisapple/modules_synopsisapple.php");
        synopsisapple_pdf_create($db, $ship, 'appleretour');

        return array(
            'ok'   => 1,
            'html' => ''
        );
    }

    public function generateReturnPDF($shipId)
    {
        global $db;
        $ship = new shipment($db, $shipId);
        require_once(DOL_DOCUMENT_ROOT . "/synopsisapple/core/modules/synopsisapple/modules_synopsisapple.php");
        $model = (isset($_REQUEST['model']) ? $_REQUEST['model'] : 'appleretour');
        return synopsisapple_pdf_create($db, $ship, $model);
    }

    public function getShipToForm()
    {
        $html = $this->starBloc('Nouvelle expédition', 'shipTo', true);

        $html .= '<div class="tabBar">';
        $html .= '<table class="border">';
        $html .= '<tbody>';
        $html .= '<tr>';
        $html .= '<td>Numéro shipt-to:</td>';
//        $html .= '<td><input type="text" id="shipToNumber" name="shipToNumber" width="350px" value="0000494685"/>&nbsp;&nbsp;';
//        $html .= '<input type="button" id="shipToSubmit" value="&nbsp;&nbsp;Ok&nbsp;&nbsp;"/></td>';

        $html .= '<td><select id="shipToNumber" name="shipToNumber" style="width: 350px">';
        foreach (shipToList::$list as $shipTo => $datas) {
            $html .= '<option value="' . $shipTo . '">' . $shipTo . ': ' . $datas['Name'] . ' - ' . $datas['Address']['PostalCode'] . ' ' . $datas['Address']['City'] . '</option>';
        }
        $html .= '</select>';
        $html .= '<input type="button" id="shipToSubmit" value="&nbsp;&nbsp;Ok&nbsp;&nbsp;"/></td>';

        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= $this->endBloc();

        $html .= '<div style="display: none" id="ajaxRequestResults"></div>';
        return $html;
    }

    public function getShippingForm()
    {
        $html = $this->starBloc('Choix des composants à expédier', 'partsList', true);
        $html .= $this->getPartsListHtml();
        $html .= $this->endBloc();

        $html .= $this->starBloc('Informations expédition', 'shippingInfos', true);
        $html .= $this->getShippingInfosForm();
        $html .= $this->endBloc();

        $html .= '<div id="shippingRequestResponseContainer" style="display: none"></div>';
        return $html;
    }

    protected function getPartsListHtml()
    {
        $parts = $this->getPartsPendingArray();

        $html = '<input type="hidden" id="shipToUsed" name="shiptToUsed" value="' . $this->shiptTo . '"/>';
        if ($parts === false) {
            if (count($this->gsx->errors['soap'])) {
                foreach ($this->gsx->errors['soap'] as $idx => $error) {
                    if (preg_match('/.*(Code: RPR\.RTN\.005).*/', $error)) {
                        $html .= '<p class="error">Il n\'y a aucun composant en attente de retour pour ce centre <br/>';
                        global $user;
                        if (!isset($user->array_options['options_apple_id']) || !isset($user->array_options['options_apple_service']) ||
                                $user->array_options['options_apple_id'] == "" || $user->array_options['options_apple_service'] == "") {
                            $html .= 'Attention, vous devez avoir un Identifiant Apple valide (Apple ID) pour pouvoir accéder à ce service</p>';
                        }

                        unset($this->gsx->errors['soap'][$idx]);
                    }
                }
            }
            $html .= $this->gsx->getGSXErrorsHtml();
            return $html;
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
            $html .= '<th id="partName_title" class="sortable desc" onclick="onSortableClick($(this))"><span>Nom</span><span class="arrow"></span></th>' . "\n";
            $html .= '<th id="partRef_title" class="sortable desc" onclick="onSortableClick($(this))"><span>Ref.</span><span class="arrow"></span></th>' . "\n";
            $html .= '<th id="partNewRef_title" class="sortable desc" onclick="onSortableClick($(this))"><span>Nouvelle Ref.</span><span class="arrow"></span></th>' . "\n";
            $html .= '<th id="partPONumber_title" class="sortable desc" onclick="onSortableClick($(this))"><span>N° de commande</span><span class="arrow"></span></th>';
            $html .= '<th id="partSroNumber_title" class="sortable desc numeric" onclick="onSortableClick($(this))"><span>N° de réparation</span><span class="arrow"></span></th>' . "\n";
            $html .= '<th id="partSerial_title" class="sortable desc" onclick="onSortableClick($(this))"><span>N° de série du produit</span><span class="arrow"></span></th>' . "\n";
            $html .= '<th id="partDateValue_title" class="sortable desc useInput" onclick="onSortableClick($(this))"><span>Date de retour attendue</span><span class="arrow"></span></th>';
            $html .= '<th id="vendorName_title" class="sortable desc" onclick="onSortableClick($(this))"><span>Distinataire</span><span class="arrow"></span></th>';
            $html .= '</tr></thead><tbody>' . "\n";
            $odd = false;
            $i = 1;
            foreach ($parts as $sro => $repairParts) {
                foreach ($repairParts as $p) {
                    $DT = null;
                    if (!empty($p['expectedReturnDate'])) {
//                        2017-09-06 07:00:00 +00:00
                        if (preg_match('/^\{4}\-\d{2}\-\d{2}( \{2}:\d{2}:\d{2} )?(\+\d{2}:\d{2})?$/', $p['expectedReturnDate'])) {
                            $DT = new DateTime($p['expectedReturnDate']);
                            $date = $DT->format('d / m / Y');
                        } else {
                            $date = $p['expectedReturnDate'];
                        }
                    } else {
                        $date = 'non spécifiée';
                    }
                    $html .= '<tr id="part_' . $i . '" ' . ($odd ? ' class="odd"' : '') . '>' . "\n";
                    $html .= '<td><input class="partCheck" type="checkbox" name="parts[]"/></td>' . "\n";
                    $html .= '<td class="partName">' . $p['nom'] . '</td>' . "\n";
                    $html .= '<td class="partRef">' . $p['ref'] . '</td>' . "\n";
                    $html .= '<td class="partNewRef">' . $p['newRef'] . '</td>' . "\n";
                    $html .= '<td class="partPONumber">' . $p['poNumber'] . '</td>';
                    $html .= '<td class="partSroNumber">' . $sro . '</td>' . "\n";
                    $html .= '<td class="partSerial">' . $p['serial'] . '</td>' . "\n";
                    $html .= '<td class="partReturnDate">' . $date . '</td>' . "\n";
                    $html .= '<td class="vendorName">' . $p['vendorName'] . '</td>' . "\n";
                    $html .= '<input type="hidden" class="partReturnOrderNumber" value="' . $p['returnOrderNumber'] . '"/>' . "\n";
                    $html .= '<input type="hidden" class="partDateValue" value="' . (isset($DT) ? $DT->format('Ymd') : '00000000') . '" />' . "\n";
                    $html .= '</tr>' . "\n";
                    $i++;
                    $odd = !$odd;
                }
            }
            $html .= '</tbody></table>' . "\n";
        }
        return $html;
    }

    protected function getPartsPendingArray()
    {
        $parts = array();

        if (!$this->connect) {
            if (!$this->gsxInit())
                return false;
        }

        $this->gsx->resetSoapErrors();

        $datas = array(
            'repairType'               => '',
            'repairStatus'             => '',
            'purchaseOrderNumber'      => '',
            'sroNumber'                => '',
            'repairConfirmationNumber' => '',
            'serialNumbers'            => array(
                'serialNumber' => ''
            ),
            'shipToCode'               => $this->shiptTo,
            'customerFirstName'        => '',
            'customerLastName'         => '',
            'customerEmailAddress'     => '',
            'createdFromDate'          => '',
            'createdToDate'            => '',
            'warrantyType'             => '',
            'kbbSerialNumberFlag'      => '',
            'comptiaCode'              => '',
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

            if (isset($response['returnOrderNumber'])) {
                $response = array($response);
            }

            foreach ($response as $part) {
//                if (isset($part['registeredForReturn']) && $part['registeredForReturn'] == 'Y')
//                    continue;

                if (!isset($parts[$part['sroNumber']]))
                    $parts[$part['sroNumber']] = array();

                $newRef = false;
                if (isset($part['originalPartNumber']) && $part['originalPartNumber'] != '')
                    $newRef = true;

                $parts[$part['sroNumber']][] = array(
                    'nom'                => $part['partDescription'],
                    'ref'                => $newRef ? $part['originalPartNumber'] : $part['partNumber'],
                    'newRef'             => $newRef ? $part['partNumber'] : '',
                    'serial'             => $part['serialNumber'],
                    'returnOrderNumber'  => $part['returnOrderNumber'],
                    'poNumber'           => $part['purchaseOrderNumber'],
                    'expectedReturnDate' => $part['expectedReturnDate'],
                    'vendorName'         => $part['vendorName'] . " | " . $part['vendorState']
                );
            }
        }
        return $parts;
    }

    protected function getShippingInfosForm()
    {
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

        $html .= '<tr>';
        $html .= '<td>Destination</td>';
        $html .= '<td>';
        $html .= '<select id="shipmentShipTo" name="shipmentShipTo">';
        $html .= '<option value="0">Sélectionnez un destinataire</option>';
        foreach (self::$shipToAdresses as $key => $adress) {
            $html .= '<option value="' . $key . '">' . $adress['label'] . '</option>';
        }
        $html .= '</select>';
        $html .= '<span class="inputCheckInfos"></span>';
        $html .= '</td></tr>';

        $html .= '<tr>';
        $html .= '<td>Numéro de suivi UPS</td>';
        $html .= '<td>';
        $html .= '<div><input type="text" id="upsTrackingNumber" name="upsTrackingNumber"/></div>';
        $html .= '<div><p style="font-size: 9px; font-style: italic">Utiliser ce champ uniquement pour les expéditions déjà enregistrées chez UPS</p></div>';
        $html .= '</td></tr>';

        $html .= '</tbody></table>' . "\n";
        $html .= '<p style="text-align: center">';
        $html .= '<input type="button" class="button" id="createShipping" onclick="createShipping()" value="Créer une nouvelle expédition"/>';
        $html .= '</p>';
        $html .= '</div>' . "\n";
        return $html;
    }

    public function getCurrentShipmentsHtml($n = 0)
    {
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

    protected function starBloc($title, $containerId, $open = false)
    {
        $html = '<div id="' . $containerId . '" class="container">' . "\n";
        $html .= '<div class="captionContainer" onclick="onCaptionClick($(this))">' . "\n";
        $html .= '<span class="captionTitle">' . $title . '</span>' . "\n";
        $html .= '<span class="arrow ' . ($open ? 'upArrow' : 'downArrow') . '"></span>';
        $html .= '</div>' . "\n";
        $html .= '<div class="blocContent"' . (!$open ? ' style="display: none"' : '') . '>' . "\n";
        return $html;
    }

    protected function endBloc()
    {
        return '</div></div>' . "\n";
    }

    public function displaySoapErrors()
    {
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
