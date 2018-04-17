<?php

/**
 *
 * GSX Web Services API PHP Class
 *
 * @author Dan "theblahman" Barrett <gsx@theblahman.net>
 *
 * @package gsxwsapi
 *
 */
function _softErrorMessage($value, $key)
{
    if (isset($key) == 'communicationMessage') {
        $softError = $value;
    }
}

class GSX
{

    /**
     *
     * Valid Region Codes
     *
     * @var array Contains valid names for all the regions in which GSX is available
     *
     * @access protected
     *
     */
    protected $validRegionCodes = array(
        'am',
        'emea',
        'apac',
        'la',
    );

    /**
     *
     * Valid Language Codes
     *
     * This array of valid language codes is from the GSX API
     * documentation.  This GSX class does its own checking of
     * certain values since it's much faster to do validation
     * on our end.
     *
     * @var array Array of valid language codes
     *
     * @access protected
     *
     */
    protected $validLanguageCodes = array(
        'en', // English
        'fr', // Frence
        'de', // German
        'es', // Spanish
        'it', // Italian
        'ja', // Japanese
        'ko', // Korean
        'zf', // Traditional Chinese
        'zh' // Simple Chinese
    );

    /**
     *
     * Valid Time Zones
     *
     * This array contains a list of all the valid timezone codes
     * that GSX allows.
     *
     * @var array Array of valid timezones according to GSX
     *
     * @access protected
     *
     */
    protected $validTimeZones = array(
        'PDT', // Pacific Daylight Time					UTC-7
        'GMT', // Greenwich Mean Time						UTC
        'PST', // Pacific Standard Time					UTC-8
        'CDT', // Central Daylight Time					UTC-5
        'CST', // Central Standard Time					UTC-6
        'EDT', // Eastern Daylight Time					UTC-4
        'EST', // Eastern Standard Time					UTC-5
        'CEST', // Central European Summer Time				UTC+2
        'CET', // Central European Time					UTC+1
        'JST', // Japan Standard Time						UTC+9
        'IST', // Indian Standard Time						UTC+5.5
        'CCT', // Chinese Coast Time						UTC+8
        'AEST', // Australian Eastern Standard Time			UTC+10
        'AEDT', // Australian Eastern Daylight Time			UTC+11
        'ACST', // Australian Central Standard Time			UTC+9.5
        'ACDT', // Australian Central Daylight Time			UTC+10.5
        'NZST'  // New Zealand Standard Time				UTC+12
    );

    /**
     *
     * Valid API Modes
     *
     * This array contains the three valid modes for the GSX Web Services
     * API - GSXIT (Testing), GSXUT (Testing) and GSX Production (Live)
     *
     * @var array Array of GSX testing and production modes.
     *
     * @access protected
     *
     */
    protected $validApiModes = array(
        'it',
        'ut',
        'production'
    );

    /**
     *
     * Valid Part Search
     *
     * This array contains all the variables we can search for in
     * the PartsLookup function of the GSX Web Services API.
     *
     * @var array Array of the valid variables for Part Search
     *
     * @see $this->part_lookup
     *
     * @access protected
     *
     */
    protected $validPartSearch = array(
        'eeeCode',
        'partNumber',
        'partDescription',
        'productName',
        'serialNumber'
    );

    /**
     *
     * Valid Repair Lookup
     *
     * This array contains all the variables we can search for in
     * the RepairLookup function of the GSX Web Services API.
     *
     * @var array Array of the valid variables for Repair Lookup
     *
     * @see $this->repair_lookup
     *
     * @access protected
     *
     */
    protected $validRepairLookup = array(
        'serialNumber',
        'repairConfirmationNumber',
        'repairNumber',
        'repairStatus',
        'repairType',
        'purchaseOrderNumber',
        'technicianFirstName',
        'technicianLastName',
        'shipToCode',
        'soldToReferenceNumber',
        'incompleteRepair',
        'pendingShipment',
        'unreceivedModules',
        'fromDate',
        'toDate',
        'customerFirstName',
        'customerLastName',
        'customerEmailAddress'
    );

    /**
     *
     * Valid Repair Statuses
     *
     * This array contains all possible repair statuses in a GSX repair
     *
     * @var array Array of the valid Repair Statuses
     *
     * @see $this->repair_lookup()
     *
     * @access protected
     *
     */
    protected $validRepairStatus = array(
        'New',
        'Saved',
        'Open',
        'Declined',
        'On Hold',
        'Closed'
    );

    /**
     *
     * Valid Repair Type
     *
     * This array contains all the repairType possibilities for the
     * RepairLookup function.
     *
     * @var array Array of the valid Repair Types
     *
     * @see $this->repair_lookup()
     *
     * @access protected
     *
     */
    protected $validRepairType = array(
        'ON',
        'WH',
        'CA'
    );

    /**
     *
     * @var array Array of the valid Warranty Status Parameters
     *
     * @see $this->warranty_status()
     *
     * @access protected
     *
     */
    protected $validWarrantyParams = array(
        'serialNumber',
        'unitReceivedDate',
        'partNumbers'
    );

    /**
     *
     * GSX Details
     *
     * This array contains all our important details regarding
     * the usage of the GSX Web Services API including login details
     * and localisation data.
     *
     * @var array Array that contains all the GSX authentication details
     *
     * @access protected
     *
     */
    protected $gsxDetails = array(
        'apiMode'          => 'ut',
        'regionCode'       => 'apac',
        'userId'           => '',
        'password'         => '',
        'serviceAccountNo' => '',
        'languageCode'     => 'fr',
        'userTimeZone'     => 'CET',
        'returnFormat'     => 'php',
        'gsxWsdl'          => '',
    );

    /**
     *
     * WSDL Url
     *
     * This is our URL for the WSDL.  It can change depending on
     * users location and their needs (APS, iPhone etc.)
     *
     * @var string WSDL URL
     *
     * @access protected
     *
     */
    protected $wsdlUrl;

    /**
     *
     * User Session ID
     *
     * Class variable for our GSX Authentication token.
     *
     * @var string Authentication ID
     *
     * @access protected
     *
     */
    protected $userSessionId;

    /**
     *
     * SOAP Client
     *
     * Class object for our GSX SOAP Client.
     *
     * @var object SOAP Client Object
     *
     * @access protected
     *
     */
    protected $soapClient;
    public $errors = array(
        'init' => array(),
        'soap' => array()
    );

    /**
     *
     * Constructor
     *
     * Builds the class and checks to see if all the details provided
     * through the constructor are valid so we can authenticate without
     * problems.
     *
     * $_gsxDetailsArray = array (
     * 		'apiMode'			=> 'production',
     * 		'regionCode'		=> 'apac',
     * 		'userId'			=> 'username@apple.com',
     * 		'password'			=> 'apple',
     * 		'serviceAccountNo'	=> '0000000000',
     * 		'languageCode'		=> 'en',
     * 		'userTimeZone'		=> 'AEST'
     * );
     *
     * @param array GSX Details array which contains authentication and regional information
     *
     * @access public
     *
     */
    public $isIphone = false;
    public $last_response = null;

    public function __construct($_gsxDetailsArray = array(), $isIphone = false, $apiMode)
    {
// We default to using production mode for GSX
        $this->isIphone = $isIphone;
        $this->apiMode = $apiMode;
        if (!isset($_gsxDetailsArray['apiMode'])) {
            $_gsxDetailsArray['apiMode'] = 'production';
        }

        if (!in_array($_gsxDetailsArray['apiMode'], $this->validApiModes)) {
            $this->errors['init'][] = 'API Mode is invalid';
        } else {
            $this->gsxDetails['apiMode'] = $_gsxDetailsArray['apiMode'];
        }

        if ($_gsxDetailsArray['regionCode'] == '') {
            $this->errors['init'][] = 'User Region Code is blank';
        } else if (!in_array($_gsxDetailsArray['regionCode'], $this->validRegionCodes)) {
            $this->errors['init'][] = 'User Region is invalid';
        } else {
            $this->gsxDetails['regionCode'] = $_gsxDetailsArray['regionCode'];
        }

        if ($_gsxDetailsArray['userId'] == '') {
            $this->errors['init'][] = 'User ID is blank';
        } else {
            $this->gsxDetails['userId'] = $_gsxDetailsArray['userId'];
        }

//        if ($_gsxDetailsArray['password'] == '') {
//            $this->errors['init'][] = 'Password is blank';
//        } else {
//            $this->gsxDetails['password'] = $_gsxDetailsArray['password'];
//        }

        if ($_gsxDetailsArray['serviceAccountNo'] == '') {
            $this->errors['init'][] = 'Service Account Number is blank';
        } else {
            $this->gsxDetails['serviceAccountNo'] = $_gsxDetailsArray['serviceAccountNo'];
        }


        if ($_gsxDetailsArray['serviceAccountNoShipTo'] == '') {
            $this->gsxDetails['serviceAccountNoShipTo'] = $this->gsxDetails['serviceAccountNo']; //shipto = sold to si pas de soldto
        } else {
            $this->gsxDetails['serviceAccountNoShipTo'] = $_gsxDetailsArray['serviceAccountNoShipTo'];
        }

// If user has left languageCode empty, we assign the GSX default.
        $this->gsxDetails['languageCode'] = ( empty($_gsxDetailsArray['languageCode']) ) ? 'en' : $_gsxDetailsArray['languageCode'];

// If user has left userTimeZone empty, we assign the GSX default.
        $this->gsxDetails['userTimeZone'] = ( empty($_gsxDetailsArray['userTimeZone']) ) ? 'PST' : $_gsxDetailsArray['userTimeZone'];

        $this->gsxDetails['returnFormat'] = $_gsxDetailsArray['returnFormat'];

        $this->gsxDetails['gsxWsdl'] = ( empty($_gsxDetailsArray['wsdl']) ) ? false : $_gsxDetailsArray['wsdl'];

        if (!count($this->errors['init']))
            $this->authenticate();
    }

    /**
     *
     * Destruct
     *
     * Destructs a number of important class-related variables
     *
     * @param null
     *
     * @todo MOAR garbage collection.
     *
     * @access public
     *
     */
    public function __destruct()
    {
// We can destruct class settings, but I don't want to log out, purely for the reason if someone
// uses this class with a custom AJAX environment.
        unset($this->userSessionId);
    }

    /**
     *
     * Assign WSDL
     *
     * Checks to see if it should use the official GSX WSDL or a custom, user-supplied WSDL link.
     *
     * @param null
     *
     * @return string The WSDL URI for GSX.
     *
     * @access protected
     *
     */
    protected function assign_wsdl()
    {
        $api_mode = ( $this->gsxDetails['apiMode'] == 'production' ) ? '' : $this->gsxDetails['apiMode'];

        $opt = ($this->isIphone) ? "IPhone" : "Asp";


//        return $this->wsdlUrl = "https://gle.synopsis-erp.com/test1/gsx-emeaAsp.wsdl";
//        return $this->wsdlUrl = ' https://gsxwsut.apple.com/apidocs/' . $api_mode . '/html/WSArtifacts.html?user=asp';
        return $this->wsdlUrl = 'https://gsxapi' . $api_mode . '.apple.com/wsdl/' . strtolower($this->gsxDetails['regionCode']) . $opt . '/gsx-' . strtolower($this->gsxDetails['regionCode']) . $opt . '.wsdl';

//        $type = "Asp";
////        $type = "IPhone";
//        if ($this->gsxDetails['gsxWsdl'] != '') {
//            return $this->wsdlUrl = $this->gsxDetails['gsxWsdl'];
//        } elseif ($api_mode == 'it') {
//            return $this->wsdlUrl = 'https://gsxwsit.apple.com/wsdl/' . strtolower($this->gsxDetails['regionCode']) . 'Asp/gsx-' . strtolower($this->gsxDetails['regionCode']) . 'Asp.wsdl';
//        } elseif ($api_mode == 'ut') {
//            if ($this->isIphone) {
//                return $this->wsdlUrl = 'https://gsxws2.apple.com/wsdl/emeaIPhone/gsx-emeaIPhone.wsdl';
//            } else
//                return $this->wsdlUrl = 'https://gsxapiut.apple.com/wsdl/' . strtolower($this->gsxDetails['regionCode']) . 'Asp/gsx-' . strtolower($this->gsxDetails['regionCode']) . 'Asp.wsdl';
//        } else {
//            if ($this->isIphone)
//                return $this->wsdlUrl = 'https://gsxws2.apple.com/wsdl/emeaIPhone/gsx-emeaIPhone.wsdl';
//            else
//                $this->wsdlUrl = 'https://gsxws2' . $api_mode . '.apple.com/wsdl/' . strtolower($this->gsxDetails['regionCode']) . 'Asp/gsx-' . strtolower($this->gsxDetails['regionCode']) . 'Asp.wsdl';
//
//            return $this->wsdlUrl;
//        }
    }

    /**
     *
     * Initiate SOAP Client
     *
     * This here function initialises the SOAP Client for use with GSX.
     *
     * @param null
     *
     * @return object soapClient object.
     *
     * @access private
     *
     */
    private function initiate_soap_client()
    {
        if (empty($this->wsdlUrl)) {
            $this->assign_wsdl();
        }

        require_once DOL_DOCUMENT_ROOT . '/synopsisapple/certifs.php';
        global $tabCert;

        $soldTo = (int) $this->gsxDetails['serviceAccountNo'];
        if (!isset($tabCert[$soldTo])) {
            echo "Pas de certif pour se soldTo " . $soldTo;
            return 0;
        }
        $typeCertif = ($this->apiMode == 'ut') ? 0 : 1;
        $certif = $tabCert[$soldTo][$typeCertif];


        $connectionOptions = array(
            'connection_timeout' => '5'
            , 'soap_version'       => 'SOAP_1_2'
            , 'local_cert'         => DOL_DOCUMENT_ROOT . "/synopsisapple/certif/" . $certif[0]
            , 'passphrase'         => $certif[1]
            , 'trace'              => TRUE
            , 'exceptions'         => TRUE
                //            ,'local_cert' => '/etc/apache2/ssl/Applecare-APP157-0000897316.Prod.apple.com.chain.pem'
        );

        
//        if(isset($_SESSION['soapClient'])){
//            $this->soapClient = unserialize($_SESSION['soapClient']);
//            return $this->soapClient;
//        }
        
        try {
            $this->soapClient = new SoapClient($this->wsdlUrl, $connectionOptions);
            $_SESSION['soapClient'] = serialize($this->soapClient);
        } catch (SoapFault $fault) {
            return $this->soap_error($fault->faultcode, $fault->faultstring);
        }

        return $this->soapClient;
    }

    /**
     *
     * Authenticate
     *
     * Authenticates details with GSX Web Services and gets a session ID if
     * the operation was successful
     *
     * @param null
     *
     * @return string Returns the userSessionId as created by GSX.
     *
     * @access public
     *
     */
    public function authenticate()
    {

        if (!is_object($this->soapClient)) {
            $this->initiate_soap_client();
        }
        if (!is_object($this->soapClient)) {
            return 0;
        }
        $authentication_array = array(
            'AuthenticateRequest' => array(
                'userId'           => $this->gsxDetails['userId'],
                'password'         => $this->gsxDetails['password'],
                'serviceAccountNo' => $this->gsxDetails['serviceAccountNo'],
                'languageCode'     => $this->gsxDetails['languageCode'],
                'userTimeZone'     => $this->gsxDetails['userTimeZone']
            )
        );

        try {
            $authentication = $this->soapClient->Authenticate($authentication_array);
        } catch (SoapFault $fault) {
            return $this->soap_error($fault->faultcode, $fault->faultstring);
        }

        $authentication = $this->_objToArr($authentication);

        return $this->userSessionId = $authentication['AuthenticateResponse']['userSessionId'];
    }

    /**
     *
     * Lookup
     *
     * Lookup either the model identifier or warranty information for a given unit.
     * * If you leave lookup type blank, it defaults to warranty lookup.
     *
     * @param string The serial number of the Apple product
     *
     * @param string Lookup type (model|warranty)
     *
     * @param string Format to return data (optional)
     *
     * @return mixed Returns array of data if successful or string on error
     *
     * @access public
     *
     */
    public function lookup($serial, $lookupType, $returnFormat = false)
    {
        if (!preg_match($this->_regex('serialNumber'), $serial)) {
            $this->error(__METHOD__, __LINE__, 'Numéro de série invalide.');
            echo 'Numéro de série invalide.';
            return false;
        }

        switch ($lookupType) {
            case 'model' :
                $clientLookup = 'FetchProductModel';
                $requestName = 'FetchProductModelRequest';
                $wrapperName = 'productModelRequest';
                if ($this->isIphone) {
                    $details = array(
                        'serialNumber' => $serial
                    );
                } else {
                    $details = array(
                        'alternateDeviceId' => $serial
                    );
                }

                $requestData = $this->_requestBuilder($requestName, $wrapperName, $details);

                $modelData = $this->request($requestData, $clientLookup);

//                $errorMessage = $this->_obtainErrorMessage($modelData);

                return $this->outputFormat($modelData['FetchProductModelResponse']['productModelResponse'], $errorMessage, $returnFormat);

                break;

            default :
            case 'warranty' :
                if ($this->isIphone) {
                    $clientLookup = 'IPhoneWarrantyStatus';
                    $wrapperName = 'iphoneUnitDetail';
                    $details = array(
                        'serialNumber' => '',
                        'imeiNumber'   => $serial
                    );
                    $responseName = 'iphoneWarrantyDetailInfo';
                } else {
                    $clientLookup = 'WarrantyStatus';
                    $wrapperName = 'unitDetail';
                    $details = array(
                        'serialNumber' => $serial
                    );
                    $responseName = 'warrantyDetailInfo';
                }


//                    $data = new gsxDatas();
//                    $details['shipTo'] = $data->shipTo;
                $details['shipTo'] = $this->gsxDetails['serviceAccountNoShipTo'];

                $requestName = $clientLookup . 'Request';
                $requestData = $this->_requestBuilder($requestName, $wrapperName, $details);
                $warrantyDetails = $this->request($requestData, $clientLookup);
//                dol_syslog(print_r($requestData,3),3);
//                print_r($requestData);
                $errorMessage = $this->_obtainErrorMessage($warrantyDetails);
                return $this->outputFormat($warrantyDetails[$clientLookup . 'Response'][$responseName], $errorMessage, $returnFormat);

                break;
        }
    }

    /**
     *
     * Part Lookup
     *
     * Obtain information for part(s) using several parameters:
     *   - EEE Code
     *   - Part Number
     *   - Serial Number (of machine, not part serial #)
     *   - Part Description (keyboard, for example)
     *
     * Alternatively, you can make an array with more information populated
     *   'eeeCode' => 'D4N' ,
     *   'partDescription' => 'logic'
     *
     * The above code will search the parts database for all parts that match
     * both the EEE Code of 'D4N' and Part Description 'logic'.
     *
     * @param mixed Array or string of parameters
     *
     * @param string 'json' for json output, 'plist' for .plist output, or leave blank for php output
     *
     * @return array Part(s) details
     *
     * @access public
     *
     */
    public function part($params, $returnFormat = false)
    {
        if (!is_array($params)) {
            if (preg_match($this->_regex('eeeCode'), $params)) {
                $finalParams['eeeCode'] = $params;
            } elseif (preg_match($this->_regex('partNumber'), $params)) {
                $finalParams['partNumber'] = $params;
            } elseif (preg_match($this->_regex('imeiNumber'), $params)) {
                if ($this->isIphone)
                    $finalParams['imeiNumber'] = $params;
                else
                    $finalParams['serialNumber'] = $params;
            } elseif (preg_match($this->_regex('partDescription'), $params)) {
                $finalParams['partDescription'] = $params;
            }
        } else {
            $finalParams = $params;
        }

        if ($this->isIphone) {
            $clientLookup = 'IPhonePartsLookup';
        } else {
            $clientLookup = 'PartsLookup';
        }
        $wrapperName = 'lookupRequestData';
        $requestName = $clientLookup . 'Request';

        $requestData = $this->_requestBuilder($requestName, $wrapperName, $finalParams);

//        echo '<pre>';
//        print_r($requestData);
//        echo '</pre>';

        $partsLookup = $this->request($requestData, $clientLookup);

//        echo '<pre>';
//        print_r($partsLookup);
//        echo '</pre>';
//var_dump($partsLookup);
//$errorMessage = $this->_obtainErrorMessage ( $partsLookup );

        return $this->outputFormat($partsLookup[$clientLookup . 'Response']['parts'], $errorMessage, $returnFormat);
    }

    public function repairs($type, $params, $returnFormat = false)
    {
        switch ($type) {
            /* ! Repair Lookup */
            case 'lookup' :
            default :
                $details = $params;
                if ($this->isIphone) {
                    $clientLookup = 'IPhoneRepairLookup';
                } else {
                    $clientLookup = 'RepairLookup';
                }

                $requestName = $clientLookup . 'Request';
                $wrapperName = 'lookupRequestData';
                dol_syslog("requete : " . $requestName, LOG_DEBUG);

                $requestData = $this->_requestBuilder($requestName, $wrapperName, $details);

                $repairLookup = $this->request($requestData, $clientLookup);

//$errorMessage = $this->_obtainErrorMessage ( $repairLookup );

                return $this->outputFormat($repairLookup, $errorMessage, $returnFormat);

                break;

            /* ! Repair Details */
            case 'details' :
                if ($this->isIphone) {
                    $clientLookup = 'IPhoneRepairDetails';
                } else {
                    $clientLookup = 'RepairDetails';
                }
                $requestName = $clientLookup . 'Request';
                $wrapperName = 'dispatchId';
                $details = $params;

                $requestData = $this->_requestBuilder($requestName, $wrapperName, $details);

                $repairLookup = $this->request($requestData, $clientLookup);

                $errorMessage = $this->_obtainErrorMessage($repairLookup);

                return $this->outputFormat($repairLookup[$clientLookup . 'Response']['lookupResponseData'], $errorMessage, $returnFormat);

                break;

            /* ! Repair Details Lookup */
            case 'details-lookup' :
                break;
        }
    }

//    public function createStockingOrder($purchaseOrderNumber, $shipToCode, $parts) {
//        $clientLookup = 'CreateStockingOrder';
//        $requestName = $clientLookup . 'Request';
//        $wrapperName = 'orderData';
//        $details = array(
//            'purchaseOrderNumber' => $purchaseOrderNumber,
//            'shipToCode' => $shipToCode,
//            'orderLines' => $parts
//        );
//
//        $requestData = $this->_requestBuilder($requestName, $wrapperName, $details);
//        $response = $this->request($requestData, $clientLookup);
//        return $this->outputFormat($response['CreateStockingOrderResponse']);
//    }
//
//    public function createSRFOrder($serial, $purchaseOrderNumber, $shipToCode, $reasonForOrder, $parts) {
//        if (!preg_match($this->_regex('serialNumber'), $serial)) {
//            $this->error(__METHOD__, __LINE__, 'Numéro de série invalide.');
//            return false;
//        }
//
//        $clientLookup = 'CreateSRFOrder';
//        $requestName = $clientLookup . 'Request';
//        $wrapperName = 'orderData';
//        $details = array(
//            'purchaseOrderNumber' => $purchaseOrderNumber,
//            'shipToCode' => $shipToCode,
//            'serialNumber' => $serial,
//            'reasonForOrder' => $reasonForOrder,
//            'orderLines' => $parts
//        );
//
//        $requestData = $this->_requestBuilder($requestName, $wrapperName, $details);
//        $response = $this->request($requestData, $clientLookup);
//        return $this->outputFormat($response['CreateSRFOrderResponse']);
//    }


    public function obtainSymtomes($serials = null, $sympCode = null)
    {

        if (!$this->userSessionId) {
            $this->authenticate();
        }

// Manually build the request...
        $compTIARequest = array(
            'ReportedSymptomIssueRequest' => array(
                'userSession' => array(
                    'userSessionId' => $this->userSessionId),
                'requestData' => array(
                ),
            ),
        );
        if (!is_null($sympCode))
            $compTIARequest['ReportedSymptomIssueRequest']['requestData']['reportedSymptomCode'] = $sympCode;

        elseif (!is_null($serials))
            $compTIARequest['ReportedSymptomIssueRequest']['requestData']['serialNumber'] = $serials;

        try {
            $compTIAAnswer = $this->soapClient->ReportedSymptomIssue($compTIARequest);
        } catch (SoapFault $fault) {
            return $this->soap_error($fault->faultcode, $fault->faultstring);
        }

        $compTIAAnswer = $this->_objToArr($compTIAAnswer);
        return $compTIAAnswer;
    }

    /**
     *
     * Obtain CompTIA
     *
     * This function obtains the codes and descriptions
     * for the CompTIA lists used in GSX.
     *
     * * NOTE *
     * This function appears to be partially broken in the current GSX API,
     * Only codes are returned, no descriptions.
     *
     * @return array List of CompTIA codes and descriptions
     *
     * @access public
     *
     */
    public function obtainCompTIA()
    {
        if (!$this->userSessionId) {
            $this->authenticate();
        }

// Manually build the request...
        $compTIARequest = array(
            'ComptiaCodeLookupRequest' => array(
                'userSession' => array(
                    'userSessionId' => $this->userSessionId
                ),
            ),
        );

        try {
            $compTIAAnswer = $this->soapClient->CompTIACodes($compTIARequest);
        } catch (SoapFault $fault) {
            return $this->soap_error($fault->faultcode, $fault->faultstring);
        }

        $compTIAAnswer = $this->_objToArr($compTIAAnswer);

        return $compTIAAnswer;
    }
// HELPER FUNCTIONS

    /**
     *
     * Request
     *
     * Performs a request after having receieved valid data from $this->lookup();
     *
     * @param array The finalised array built by $this->_requestBuilder();
     *
     * @param string The name of the WSDL function we call
     *
     * @see $this->lookup();
     *
     * @see $this->_requestBuilder();
     *
     * @access private
     *
     */
    public function request($requestData, $clientLookup)
    {
        $this->last_response = null;
        $response = array();

        if (!$this->userSessionId) {
            $this->authenticate();
        }
//echo "<pre>";print_r($requestData);
        if (!$requestData || !is_array($requestData)) {
            $this->error(__METHOD__, __LINE__, 'Invalid data passed: ' . $requestData);
        }

        if (!$clientLookup || !is_string($clientLookup)) {
            $this->error(__METHOD__, __LINE__, 'Invalid data passed: ' . $clientLookup);
        }
//echo "<pre>"; print_r($requestData);
        $SOAPRequest = array();
        try {
            $SOAPRequest = $this->soapClient->$clientLookup($requestData);
            $response = $this->_objToArr($SOAPRequest);
            $this->last_response = $response;
            global $user;
            if (in_array($user->id, array(1, 270, 271))) {
                $msg = "\n" . '***** Requête GSX SOAP: "' . $clientLookup . '" ***** ' . "\n" . "\n";
                $msg .= 'Données envoyées:' . "\n";
                $msg .= print_r($requestData, 1);
                $msg .= 'Données reçues:' . "\n";
                $msg .= print_r($response, 1);
                dol_syslog($msg, LOG_DEBUG);
            }

//            if ($user->id == 1)
//                dol_syslog("result GSX " . print_r($SOAPRequest, 1) . "<br/><br/>" . $clientLookup . print_r($requestData, 1), 3, 0, "_admin");
        } catch (SoapFault $f) {
            global $user;
                if (in_array($user->id, array(1, 270, 271))) {
                    $msg = "\n" . '***** Requête GSX SOAP: "' . $clientLookup . '" ***** ' . "\n" . "\n";
                    $msg .= 'Données envoyées:' . "\n";
                    $msg .= print_r($requestData, 1);
                    $msg .= 'Erreur(s):' . "\n";
                    $msg .= $f->faultstring;
                    dol_syslog($msg, LOG_DEBUG);
                }
                
            if (stripos($f->faultstring, "Veuillez saisir les informations relatives au(x) composant(s) ") !== false) {
                $temp = str_replace(array("Veuillez saisir les informations relatives au(x) composant(s) ", "."), "", $f->faultstring);
                $tabTmp = explode(",", $temp);
                echo '<formSus>OK</formSus>' . $f->faultstring . '<fieldset id="componentCheckDetails"><legend>Détails du composant</legend>
                    <div class="inputsList">
<div class="subInputsList">
<div class="dataBlock">';
                foreach ($tabTmp as $i => $nom) {
                    $i++;
                    echo '
                   <label class="dataTitle" for="component_' . $i . '">Composant</label><br><input type="text" id="component_' . $i . '" name="component_' . $i . '" value="' . $nom . '" maxlength="20" onchange="checkInput($(this), \'text\')">
                   <span class="dataCheck" style="display: inline-block;"><span class="ok"></span></span></div><div class="dataBlock">
                   <label class="dataTitle" for="componentSerialNumber_' . $i . '">Numéro de série du composant</label><br>
                   <input type="text" id="componentSerialNumber_' . $i . '" name="componentSerialNumber_' . $i . '" maxlength="20" onchange="checkInput($(this), \'text\')">
                   <span class="dataCheck" style="display: inline-block;"><span class="ok"></span></span></div><div class="dataBlock">';
                }
                echo "<input type='hidden' name='componentCheckDetails_nextIdx' value='" . ($i + 1) . "'/>";
                echo '</div></div></div></fieldset>';

                die;
                return array();
            }
            if (stripos($f->faultstring, "Plusieurs pièces de niveau trouvées. Veuillez indiquer la pièce de niveau requise.") !== false) {
//                echo '<formSus>OK</formSus>' . $f->faultstring . '<fieldset id="availableRepairStrategies"><legend>Type de réparation</legend>
//                    <div class="inputsList">
//<div class="subInputsList">
//<div class="dataBlock">';
//                $tabTmp = array("Carry-in"=>"Carry-in", "Mail-in" => "Mail-in");
//                foreach ($tabTmp as $i => $nom) {
//                    $i++;
//                    echo '
//                   <label class="dataTitle" for="component_' . $i . '">Composant</label><br><input type="text" id="component_' . $i . '" name="component_' . $i . '" value="' . $nom . '" maxlength="20" onchange="checkInput($(this), \'text\')">
//                   <span class="dataCheck" style="display: inline-block;"><span class="ok"></span></span></div><div class="dataBlock">
//                   <label class="dataTitle" for="componentSerialNumber_' . $i . '">Numéro de série du composant</label><br>
//                   <input type="text" id="componentSerialNumber_' . $i . '" name="componentSerialNumber_' . $i . '" maxlength="20" onchange="checkInput($(this), \'text\')">
//                   <span class="dataCheck" style="display: inline-block;"><span class="ok"></span></span></div><div class="dataBlock">';
//                }
//                echo "<input type='hidden' name='componentCheckDetails_nextIdx' value='" . ($i + 1) . "'/>";
//                echo '</div></div></div></fieldset>';
//                die;
//                return array();

                echo "<tierPart>OK</tierPart>";
                die;
                return array();
            }
            if (stripos($f->faultstring, "La réparation est hors garantie") !== false) {
                $temp = str_replace(array("Veuillez saisir les informations relatives au(x) composant(s) ", "."), "", $f->faultstring);
                $tabTmp = explode(",", $temp);
                echo '<horsgarantie>OK</horsgarantie>';
                die;
                return array();
            } else {
                $add = "";
                if (isset($f->detail) && isset($f->detail->errors) && isset($f->detail->errors->error))
                    $add = print_r($f->detail->errors->error, 1);
                if (in_array($user->id, array(1, 270, 271))) {
                    $this->soap_error($f->faultcode, $f->faultstring . " <pre> " . $add . print_r($SOAPRequest, true) . print_r($requestData, true));
                } else {
                    $this->soap_error($f->faultcode, $f->faultstring);
                }

//            dol_syslog("".print_r($requestData,true)."\n\n".print_r($SOAPRequest, true)."\n\n".$f->faultcode ." | ". $f->faultstring."\n\n".$this->wsdlUrl,3);
                return array();
            }
        }

        return $response;
    }

    /**
     *
     * Request Builder
     *
     * To save me time in having to write sometimes quite large arrays I
     * wrote this class to build the arrays for me.
     *
     * @param string The name of the request according to GSX
     *
     * @param string The name of the array all detailed request data should be in
     *
     * @param array All request data
     *
     * @return array Built request array
     *
     * @access private
     *
     */
    public function _requestBuilder($requestName, $wrapperName, $details)
    {
        $requestArray = array(
            "$requestName" => array(
                'userSession' => array(
                    'userSessionId' => $this->userSessionId
                ),
            )
        );
        if (isset($wrapperName) && ($wrapperName !== ''))
            $requestArray[$requestName][$wrapperName] = $details;
        else
            $requestArray[$requestName] = array_merge($requestArray[$requestName], $details);
        return $requestArray;
    }

    public function outputFormat($output, $errorMessage = null, $format = false)
    {
        if (!$format) {
            $format = $this->gsxDetails['returnFormat'];
        }

        $finalReturnArray = array(
            'ResponseArray' => array(
                'type'          => 'output',
                // 'code'			=> $code ,
                'responseData'  => $output,
                'urgentMessage' => $errorMessage
            )
        );

        return $this->_formatter($finalReturnArray, $format);
    }

    public function _formatter($output, $format)
    {
        switch ($format) {
            case 'json' :
                return json_encode($output);
                break;

            case 'php' :
            default :
                return $output;

                break;
        }
    }

    public function _obtainErrorMessage($output)
    {
        array_walk($output, '_softErrorMessage');

        return isset($softError) ? $softError : '';
    }

    /**
     *
     * Regex
     *
     * Using a valid regex token, we can obtain a certain chunk of
     * regex, that way we can recycle and easily change regex
     * as GSX's API changes
     *
     * @param string Name of the regex pattern we want to apply and retrieve
     *
     * @return string The appropriate regex according to the string supplied in the param
     *
     * @access private
     *
     */
    private function _regex($pattern)
    {
        switch ($pattern) {
            case 'imeiNumber':
                '/^[0-9]{15,16}$/';
                break;

            case 'dispatchId' :
            case 'gsxId' :
                return '/^[G]{1}[0-9]{9}$/ ';
                break;

            case 'serialNumber' :
                return '/^[A-Z0-9]{11,16}$/';
                break;

            case 'diagnosticEventNumber' :
                return '/^[0-9]{18,22}$/';
                break;

            case 'repairConfirmationNumber' :
                return '/^[0-9]{12}$/';
                break;

            case 'soldToAccountNumber' :
            case 'shipToAccountNumber' :
                return '/^[0-9]{10}$/';
                break;

            case 'partNumber' :
                return '/^([A-Z]{1,2})?(011|076|661|92(2|3))\-[0-9]{4}$/';
                break;

            case 'eeeCode' :
                return '/^[0-9A-Z]{3}([0-9A-Z]{1})?$/';
                break;

            case 'partDescription' :
                return '/^[a-z]{5,15}$/i';
                break;

// Lazy check… I'm not too fussed about email checks
            case 'email' :
                return '/^(.+)@(.+)$/i';
                break;
        }
    }

    /**
     *
     * Object to Array
     *
     * Found this nifty function on the PHP.net website comments somewhere...
     * 	Thanks to rarioj at gmail dot com (php.net/manual/en/function.get-object-vars.php#93416) for this function
     *
     * @param object The object in question.
     *
     * @return array Associative array that contains data that has been converted from an object to an array.
     *
     * @access private
     *
     */
    private static function _objToArr($object)
    {
        if (is_object($object)) {
            $object = get_object_vars($object);
        }

        return is_array($object) ? array_map(__METHOD__, $object) : $object;
    }

    protected function error($method, $line, $message)
    {
        $this->errors[] = array(
            'function' => $method,
            'line'     => $line,
            'msg'      => $message
        );
    }

    protected function soap_error($code, $string)
    {
        $string = (utf8_decode($string));
        // The API is not very verbose with bad credentials… wrong credentials can throw the "expired session" error.
        $additionalInfo = ( $code == 'ATH.LOG.20' ) ? ' (You may have provided the wrong login credentials)' : '';

        $this->errors['soap'][] = 'SOAP Error: ' . $string . ' (Code: ' . $code . ')' . $additionalInfo;


        $codeIgnore = array("RPR.COM.039", "RPR.COM.021", "RPR.CIN.094", "RPR.COM.162", "RPR.CIN.002", "RPR.COM.512", "RPR.CIN.010", "RPR.LKP.01", "RPR.RTN.005", "RPR.CIN.025", "RPR.COM.502", "RPR.COM.108");
        $codeIgnore2 = array();

        if (!in_array($code, $codeIgnore)) {
            dol_syslog('SOAP Error: ' . $string . ' (Code: ' . $code . ')' . $additionalInfo, LOG_ERR, 0, "_apple");
            if (!in_array($code, $codeIgnore2))
                $this->errors['log']['soap'][] = 'SOAP Error: ' . $string . ' (Code: ' . $code . ')' . $additionalInfo;
//        echo('<p class="error">SOAP Error: ' . $string . ' (Code: ' . $code . ')' . $additionalInfo . "</p>");
        }
    }

    public function resetSoapErrors()
    {
        unset($this->errors['soap']);
        $this->errors['soap'] = array();
    }

    public function getGSXErrorsHtml($log = false)
    {
        $html = '';
        $tab = ($log) ? $this->errors['log'] : $this->errors;
        if (count($tab['init'])) {
            $html .= '<p class="error">Erreur(s) de connection: <br/>';
            $i = 1;
            foreach ($tab['init'] as $errorMsg) {
                $html .= $i . '. ' . utf8_encode(str_replace("?????", "'", $errorMsg)) . '.<br/>' . "\n";
                $i++;
            }
            $html .= '</p>';
        }
        if (count($tab['soap'])) {
            $html .= '<p class="error alertJs">Erreur(s) SOAP: <br/>';
            $i = 1;
            foreach ($tab['soap'] as $errorMsg) {
                $html .= $i . '. ' . utf8_encode(str_replace("??????", "'", $errorMsg)) . '.<br/>' . "\n";
                $i++;
            }
            $html .= '</p>';
        }
        return $html;
    }

    public function dispayLastRequestXml($request = null, $response = null, $error = null)
    {
        $sortie = 'Dernière requête: <br/><br/>';
        if (isset($request))
            $sortie .= print_r($request, 1);
        else
            $sortie .= $this->soapClient->__getLastRequest();
        $sortie .= '<br/><br/>';
        $sortie .= 'Dernière réponse: <br/><br/>';

        if (isset($response))
            $sortie .= print_r($response, 1);
        else
            $sortie .= $this->soapClient->__getLastResponse();

        $sortie .= 'Dernière erreur: <br/><br/>';

        if (isset($error))
            $sortie .= print_r($error, 1);

        dol_syslog($sortie, 3, 0, "_apple3");
    }

    public function displayLastResponse()
    {
        if (!is_null($this->last_response)) {
            echo 'Réponse: <pre>';
            print_r($this->last_response);
            echo '</pre>';
        } else {
            echo '<p class="error">Aucune réponse</p>';
        }
    }
}

?>
