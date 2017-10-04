<?php

//ALTER TABLE `llx_synopsis_apple_repair` ADD `ready_for_pick_up` BOOLEAN NOT NULL DEFAULT FALSE AFTER `serialUpdateConfirmNumber`;
//ALTER TABLE `llx_synopsis_apple_repair` ADD `repairType` TEXT NULL DEFAULT NULL AFTER `serialUpdateConfirmNumber`, 
//ADD `totalFromOrder` FLOAT NOT NULL DEFAULT '0.00' AFTER `repairType`;
// New: 
//ALTER TABLE `llx_synopsis_apple_repair` ADD `serial_number` VARCHAR(128) NULL DEFAULT NULL AFTER `chronoId`;

require_once DOL_DOCUMENT_ROOT . '/synopsisapple/GSXRequests.php';

class Repair
{

    public static $lookupNumbers = array(
        'serialNumber'             => 'Numéro de série',
        'repairNumber'             => 'Numéro de réparation (repair #)',
        'repairConfirmationNumber' => 'Numéro de confirmation (Dispatch ID)',
        'purchaseOrderNumber'      => 'Numéro de bon de commande (Purchase Order)',
        'imeiNumber'               => 'Numéro IMEI (IPhone)'
    );
    public static $repairStatusCodes = array(
        'RFPU' => 'Ready For Pick Up',
        'AWTP' => 'Awaiting Parts',
        'AWTR' => 'Parts allocated',
        'BEGR' => 'In Repair',
    );
    public static $repairTypes = array(
        'carry_in', 'repair_or_replace'
    );
    public $gsx = null;
    public $db = null;
    public $serial = null;
    public $prodId = null;
    public $rowId = null;
    public $partsPending = array();
    public $confirmNumbers = array();
    public $repairStatus = null;
    public $repairNumber = null;
    public $repairType = null;
    public $totalFromOrder = null;
    public $readyForPickUp = false;
    public $repairComplete = 0;
    public $dateClose = null;
    public $isReimbursed = false;
    protected $repairLookUp = null;
    protected $errors = array();
    public $isIphone;
    public $majSerialOk = false;
    public $totalFromOrderChanged = false;

    public function __construct($db, $gsx, $isIphone)
    {
        $this->db = $db;
        $this->gsx = $gsx;
        $this->isIphone = $isIphone;
    }

    public function setSerial($serial)
    {
        if (preg_match('/^[0-9]{15,16}$/', $serial))
            $this->isIphone = true;
        else
            $this->isIphone = false;

        if (isset($this->rowId) && $this->rowId &&
                (!isset($this->serial) || ($this->serial !== $serial))) {
            $this->serial = $serial;
            $this->update();
        } else {
            $this->serial = $serial;
        }
    }

    public function addError($msg)
    {
        $this->errors[] = $msg;
    }

    public function displayErrors()
    {
        $html = '';
        foreach ($this->errors as $error) {
            if (!preg_match('/^<p/', $error))
                $html .= '<p class="error">' . $error . '</p>';
            else
                $html .= $error;
        }
        $this->errors = array();
        return $html;
    }

    public function setDatas($repairNumber, $confirmNumber, $serialUpdateConfirmNumber, $repairComplete = 0, $rowId = null)
    {
        $this->repairNumber = $repairNumber;
        $this->confirmNumbers['repair'] = $confirmNumber;
        $this->confirmNumbers['serialUpdate'] = $serialUpdateConfirmNumber;
        $this->repairComplete = $repairComplete;
        if (isset($rowId))
            $this->rowId = $rowId;
    }

    public function create($chronoId, $confirmNumber)
    {
        if (!isset($chronoId)) {
            $this->addError('impossible de crée la réparation (chronoId absent)');
            return false;
        }

        if (!isset($confirmNumber)) {
            $this->addError('impossible de crée la réparation (numéro de confirmation absent)');
            return false;
        }

        $this->confirmNumbers['repair'] = $confirmNumber;
        $this->rowId = null;
        $this->readyForPickUp = false;
        $this->repairComplete = 0;
        $this->repairNumber = null;
        if (!$this->add($chronoId))
            return false;

        if (!$this->lookup()) {
            $this->addError('Une erreur est survenue durant la tentative de récupération du numéro de réparation');
            return false;
        }

        if (!$this->update())
            return false;

//        require_once(DOL_DOCUMENT_ROOT."/synopsischrono/class/chrono.class.php");
//        $chrono = new Chrono($this->db);
//        $chrono->fetch($chronoId);
//        $chrono->setDatas($chronoId, array(1056=>1));
        return true;
    }

    public function add($chronoId)
    {
        if (!isset($chronoId)) {
            $this->addError('impossible de crée la réparation (chronoId absent)');
            return false;
        }

        if (isset($this->rowId)) {
            return $this->update();
        } else {
            $sql = 'INSERT INTO `' . MAIN_DB_PREFIX . 'synopsis_apple_repair` ';
            $sql .= '(`chronoId`, `serial_number`, `repairNumber`, `repairConfirmNumber`, `serialUpdateConfirmNumber`, ';
            $sql .= '`repairType`, `totalFromOrder`, `ready_for_pick_up`, `closed`) ';
            $sql .= 'VALUES (';
            $sql .= '"' . $chronoId . '", ';
            $sql .= (isset($this->serial) ? '"' . $this->serial . '"' : 'NULL') . ', ';
            $sql .= (isset($this->repairNumber) ? '"' . $this->repairNumber . '"' : 'NULL') . ', ';
            $sql .= (isset($this->confirmNumbers['repair']) ? '"' . $this->confirmNumbers['repair'] . '"' : 'NULL') . ', ';
            $sql .= (isset($this->confirmNumbers['serialUpdate']) ? '"' . $this->confirmNumbers['serialUpdate'] . '"' : 'NULL') . ', ';
            if (isset($this->repairType) && in_array($this->repairType, self::$repairTypes)) {
                $sql .= '"' . $this->repairType . '", ';
            } else {
                $sql .= 'NULL, ';
            }
            if (isset($this->totalFromOrder) && is_float($this->totalFromOrder)) {
                $sql .= (float) $this->totalFromOrder . ', ';
            } else {
                $sql .= 0.00 . ', ';
            }
            $sql .= '"' . $this->readyForPickUp . '", ';
            $sql .= '"' . $this->repairComplete . '"';
            $sql .= ')';

            if (!$this->db->query($sql)) {
                $this->addError('Echec de l\'enregistrement en base de données<br/>Erreur SQL: ' . $this->db->lasterror());
                return false;
            }
            $this->rowId = $this->db->last_insert_id(MAIN_DB_PREFIX . 'synopsis_apple_repair');
        }
        return true;
    }

    public function update()
    {
        if (!isset($this->rowId)) {
            $this->addError('Impossible de mettre à jour les données de la réparation (ID absent)');
            return false;
        }

        $sql = 'UPDATE `' . MAIN_DB_PREFIX . 'synopsis_apple_repair` SET ';
        if (isset($this->serial))
            $sql .= '`serial_number` = "' . $this->serial . '", ';
        if (isset($this->repairNumber))
            $sql .= '`repairNumber` = "' . $this->repairNumber . '", ';
        if (isset($this->confirmNumbers['repair']))
            $sql .= '`repairConfirmNumber` = "' . $this->confirmNumbers['repair'] . '", ';
        if (isset($this->confirmNumbers['serialUpdate']))
            $sql .= '`serialUpdateConfirmNumber` = "' . $this->confirmNumbers['serialUpdate'] . '", ';
        if (isset($this->repairType) && in_array($this->repairType, self::$repairTypes))
            $sql .= '`repairType` = "' . $this->repairType . '", ';
        if (isset($this->totalFromOrder) && is_float($this->totalFromOrder))
            $sql .= '`totalFromOrder` = ' . (float) $this->totalFromOrder . ', ';
        $sql .= '`ready_for_pick_up` = ' . (int) $this->readyForPickUp . ', ';
        $sql .= '`closed` = ' . $this->repairComplete . ', ';
        $sql .= '`is_reimbursed` = ' . (int) $this->isReimbursed;
        $sql .= ' WHERE `rowid` = ' . $this->rowId;

        if (!$this->db->query($sql)) {
            $this->addError('Echec de l\'enregistrement en base de données<br/>Erreur SQL : ' . $this->db->lasterror());
            return false;
        }
        return true;
    }

    public function import($chronoId, $number, $numberType)
    {
        // On tente de charger une répa existante via l'un des numbers fournis:
        switch ($numberType) {
            case 'repairConfirmationNumber':
                $this->confirmNumbers['repair'] = $number;
                break;

            case 'repairNumber':
                $this->repairNumber = $number;
                break;

            case 'serialNumber':
                $this->setSerial($number);
                break;

            case 'imeiNumber':
                $this->serial = $number;
                $this->isIphone = true;
                break;
        }
        $this->load();

        // récupération des données depuis GSX: 
        if ($this->lookup($number, $numberType)) {
            if ((isset($this->confirmNumbers['repair']) &&
                    ($this->confirmNumbers['repair'] != '')) ||
                    (isset($this->repairNumber) &&
                    ($this->repairNumber != ''))) {

                if (!isset($this->rowId) || !$this->rowId) {
                    // On vérifie si la réparation existe déjà via l'un des numéros de réparation: 
                    $sql = 'SELECT `rowid`, `chronoId` FROM ' . MAIN_DB_PREFIX . 'synopsis_apple_repair WHERE ';
                    if (isset($this->confirmNumbers['repair']) && $this->confirmNumbers['repair']) {
                        $sql .= '`repairNumber` = "' . $this->repairNumber . '" AND ';
                    }
                    if (isset($this->repairNumber) && $this->repairNumber) {
                        $sql .= '`repairConfirmNumber` = "' . $this->confirmNumbers['repair'] . '"';
                    }
                    $result = $this->db->query($sql);
                    if ($this->db->num_rows($result) > 0) {
                        while ($row = $this->db->fetch_object($result)) {
                            if ($row->chronoId == $chronoId) {
                                // On ne met pas à jour pour éviter l'écrasement de données. 
                                return true;
                            }
                        }
                    }
                } else {
                    return true;
                }
            }
            return $this->add($chronoId);
        }
        return false;
    }

    public function close($sendRequest = true, $checkRepair = 1)
    {
        if (!$this->rowId)
            if (!$this->load()) {
                $this->addError('Erreur: réparation non enregistrée');
            }

        if ($this->repairComplete) {
            $this->addError('Cette réparation a déjà été fermée.');
            return false;
        }

        if ($sendRequest) {
            if (!isset($this->confirmNumbers['repair']) || $this->confirmNumbers['repair'] == '') {
                $this->addError('Erreur: aucun numéro de confirmation enregistré pour cette réparation.');
                return false;
            }

            if (!isset($this->gsx)) {
                $this->addError('connection à gsx non initialisée.');
                return false;
            }

            if ($checkRepair) {
                if (!isset($this->confirmNumbers['serialUpdate']) ||
                        (!$this->confirmNumbers['serialUpdate']) ||
                        ($this->confirmNumbers['serialUpdate'] === '')) {
                    $this->loadPartsPending();
                    if ($this->majSerialOk == false) {
                        if (count($this->partsPending)) {
                            $this->addError('La réparation ne peut pas être fermée, les numéros de série de certains composants semblent ne pas avoir été mis à jour');
                            $button .= '<p style="text-align: center; padding: 30px">';
                            $button .= '<span class="button redHover closeRepair" onclick="closeRepairSubmit($(this), \'';
                            $button .= $this->rowId . '\', false)">Forcer la fermeture</span></p>';
                            $this->addError($button);
                            return false;
                        } else {
                            $this->addError('Veuillez confirmer que plus aucune opération n\'est à effectuer sur cette réparation.');
                            $button .= '<p style="text-align: center; padding: 30px">';
                            $button .= '<span class="button redHover closeRepair" onclick="closeRepairSubmit($(this), \'';
                            $button .= $this->rowId . '\', false)">Confirmer la fermeture</span></p>';
                            $this->addError($button);
                            $this->gsx->resetSoapErrors();
                            return false;
                        }
                    }
                }
            }

            $client = '';
            $requestName = '';

            if ($this->isIphone) {
                $client = 'IPhoneMarkRepairComplete';
                $requestName = 'IPhoneMarkRepairCompleteRequest';
            } else {
                $client = 'MarkRepairComplete';
                $requestName = 'MarkRepairCompleteRequest';
            }
            $request = $this->gsx->_requestBuilder($requestName, '', array('repairConfirmationNumbers' => $this->confirmNumbers['repair']));
            $response = $this->gsx->request($request, $client);

            if (!isset($response[$client . 'Response']['repairConfirmationNumbers'])) {
                return false;
            }
        }
        $sql = "UPDATE  `" . MAIN_DB_PREFIX . "synopsis_apple_repair` SET  `closed` =  1, `date_close` = '" . date('Y-m-d') . "' ";
        $sql .= "WHERE  `rowid` = " . $this->rowId . ";";
        if (!$this->db->query($sql)) {
            $this->addError('Echec de l\'enregistrement de la fermeture de la réparation en base de données<br/>
                Erreur SQL : ' . $this->db->lasterror());
            return false;
        }
        $this->repairComplete = 1;
        return $this->updateTotalOrder();
    }

    public function load()
    {
        $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX . 'synopsis_apple_repair WHERE ';
        if (isset($this->rowId) && ($this->rowId != ''))
            $sql .= '`rowid` = "' . $this->rowId . '"';
        else if (isset($this->repairNumber) && ($this->repairNumber != '')) {
            $sql .= '`repairNumber` = "' . $this->repairNumber . '"';
        } else if (isset($this->confirmNumbers['repair']) && $this->confirmNumbers['repair'] != '') {
            $sql .= '`repairConfirmNumber` = "' . $this->confirmNumbers['repair'] . '"';
        } else if (isset($this->confirmNumbers['serialUpdate']) && $this->confirmNumbers['serialUpdate'] != '') {
            $sql .= '`serialUpdateConfirmNumber` = "' . $this->confirmNumbers['serialUpdate'] . '"';
        } else if (isset($this->serial) && $this->serial != '') {
            $sql .= '`serial_number` = "' . $this->serial . '"';
        } else {
            $this->addError('Impossible de charger les données de la réparation (aucun identifiant disponible)');
            return false;
        }

        $result = $this->db->query($sql);
        if ($this->db->num_rows($result) > 0) {
            $datas = $this->db->fetch_object($result);

            $this->rowId = $datas->rowid;
            if (isset($datas->repairNumber))
                $this->repairNumber = $datas->repairNumber;
            if (isset($datas->repairConfirmNumber))
                $this->confirmNumbers['repair'] = $datas->repairConfirmNumber;
            if (isset($datas->serialUpdateConfirmNumber))
                $this->confirmNumbers['serialUpdate'] = $datas->serialUpdateConfirmNumber;
            if (isset($datas->repairType))
                $this->repairType = $datas->repairType;
            if (isset($datas->totalFromOrder))
                $this->totalFromOrder = $datas->totalFromOrder;
            if (isset($datas->serialNumber))
                $this->setSerial($datas->serialNumber);
            $this->readyForPickUp = $datas->ready_for_pick_up;
            $this->repairComplete = $datas->closed;
            $this->dateClose = $datas->date_close;
            $this->isReimbursed = (int) $datas->is_reimbursed;
            return true;
        }
        return false;
    }

    public function lookup($data = null, $dataType = null)
    {
        if (!isset($this->gsx))
            return false;

        $n = count($this->gsx->errors['soap']);
        $datas = array(
            'repairConfirmationNumber' => isset($this->confirmNumbers['repair']) ? $this->confirmNumbers['repair'] : '',
            'customerEmailAddress'     => '',
            'customerFirstName'        => '',
            'customerLastName'         => '',
            'fromDate'                 => '',
            'toDate'                   => '',
            'incompleteRepair'         => '',
            'pendingShipment'          => '',
            'purchaseOrderNumber'      => '',
            'repairNumber'             => isset($this->repairNumber) ? $this->repairNumber : '',
            'repairStatus'             => '',
            'repairType'               => '',
            'serialNumber'             => '',
            'shipToCode'               => '',
            'soldToReferenceNumber'    => '',
            'technicianFirstName'      => '',
            'technicianLastName'       => '',
            'unreceivedModules'        => '',
        );
        if ($this->isIphone)
            $datas['imeiNumber'] = '';

        if (isset($data) && isset($dataType)) {
            if (isset($datas[$dataType])) {
                $datas[$dataType] = $data;
            }
        }

        $client = '';
        $requestName = '';
        if ($this->isIphone) {
            $client = 'IPhoneRepairLookup';
            $requestName = 'IPhoneRepairLookupRequest';
        } else {
            $client = 'RepairLookup';
            $requestName = 'RepairLookupRequest';
        }
        $request = $this->gsx->_requestBuilder($requestName, 'lookupRequestData', $datas);
        $response = $this->gsx->request($request, $client);

//            echo "<pre>";print_r($response);
        if (count($this->gsx->errors['soap']) > $n)
            return false;

        if (!isset($response[$client . 'Response']['lookupResponseData']))
            return false;

        $update = false;

        $this->repairLookUp = $response[$client . 'Response']['lookupResponseData']['repairLookup'];
        if (is_array($this->repairLookUp) && !isset($this->repairLookUp['repairConfirmationNumber']))
            $this->repairLookUp = $this->repairLookUp[0];
        if (isset($this->repairLookUp['repairNumber']) && ($this->repairLookUp['repairNumber'] != ''))
            if (!isset($this->repairNumber) || ($this->repairNumber != $this->repairLookUp['repairNumber'])) {
                $update = true;
            }
        $this->repairNumber = $this->repairLookUp['repairNumber'];
        if (isset($this->repairLookUp['repairConfirmationNumber']) && ($this->repairLookUp['repairConfirmationNumber'] != ''))
            if (!isset($this->confirmNumbers['repair']) || ($this->confirmNumbers['repair'] != $this->repairLookUp['repairNumber'])) {
                $update = true;
            }
        $this->confirmNumbers['repair'] = $this->repairLookUp['repairConfirmationNumber'];
        if (isset($this->repairLookUp['repairStatus']) && ($this->repairLookUp['repairStatus'] != '')) {
            $repairComplete = 0;
            if (($this->repairLookUp['repairStatus'] == 'Closed') || ($this->repairLookUp['repairStatus'] == 'Fermée et complétée') || ($this->repairLookUp['repairStatus'] == 'Réparation marquée comme complète'))
                $repairComplete = 1;
            if (!isset($this->repairComplete) || !$this->repairComplete != $repairComplete) {
                $update = true;
            }
            $this->repairComplete = $repairComplete;
        }
        if (isset($this->repairLookUp['repairType'])) {
            $repair_type = 'repair_or_replace';
            if ($repair_type === 'CA' || $repair_type === 'Carry-in') {
                $repair_type = 'carry_in';
            }
            if (!isset($this->repairType) || ($this->repairType !== $repair_type)) {
                $update = true;
            }
            $this->repairType = $repair_type;
        }

        if (!isset($this->totalFromOrder) || !$this->totalFromOrder) {
            $this->updateTotalOrder($update);
        } elseif ($update && isset($this->rowId) && $this->rowId) {
            $this->update();
        }

        return true;
    }

    public function updateStatus($status = 'RFPU')
    {
        if (!$this->rowId) {
            if (!$this->load()) {
                $this->addError('Erreur: réparation non enregistrée');
            }
        }
        if ($this->repairComplete) {
            $this->addError('Cette réparation a déjà été fermée.');
            return false;
        }

        if (!isset($this->confirmNumbers['repair']) || empty($this->confirmNumbers['repair'])) {
            $this->addError('Erreur: n° de confirmation de la réparation absent');
            return false;
        }

        if ($status === 'RFPU') {
            if ($this->readyForPickUp) {
                return true;
            }
        } else if (!array_key_exists($status, self::$repairStatusCodes)) {
            $this->addError('Statut de la réparation à mettre à jour invalide (' . $status . ')');
            return false;
        }

        if (!isset($this->gsx)) {
            $this->addError('Echec de la connexion à GSX');
            return false;
        }

        if (!isset($this->repairType)) {
            $this->lookup();
            if (isset($this->repairType)) {
                $this->update();
            } else {
                $this->addError('Echec de la récupération du type de réparation. Mise à jour du statut impossible');
                return false;
            }
        }

        $n = count($this->gsx->errors['soap']);

        $client = '';
        $requestName = '';

        $data = array(
            'repairConfirmationNumber' => $this->confirmNumbers['repair']
        );

        switch ($this->repairType) {
            case 'carry_in':
                $data['statusCode'] = $status;
                if ($this->isIphone) {
                    $client = 'IPhoneUpdateCarryIn';
                    $requestName = 'IPhoneUpdateCarryInRequest';
                } else {
                    $client = 'CarryInRepairUpdate';
                    $requestName = 'UpdateCarryInRequest';
                }
                $data['statusCode'] = $status;
                $clientRep = 'UpdateCarryIn' . 'Response';
                break;
            case 'repair_or_replace':
                $data['repairStatusCode'] = $status;
                if ($this->isIphone) {
                    $client = 'IPhoneUpdateRepairOrReplaceRequest';
//                    $client = 'IPhoneUpdateRepairOrReplace'; //=> A tester si fontionne pas
                    $requestName = 'IPhoneUpdateRepairOrReplaceRequest';
                } else {
//                    $client = 'UpdateRepairOrReplaceRequest';
                    $client = 'UpdateRepairOrReplace'; //=> A tester si fontionne pas
                    $requestName = 'UpdateRepairOrReplaceRequest';
                }
                $data['repairStatusCode'] = $status;
                $clientRep = $client . 'Response';
                break;
        }

        $request = $this->gsx->_requestBuilder($requestName, 'repairData', $data);
        $response = $this->gsx->request($request, $client);

//        echo "<pre>";print_r($response);exit;
//        $this->gsx->dispayLastRequestXml($request, $response, $this->gsx->errors);
//        exit;

        if (count($this->gsx->errors['soap']) > $n) {
            return false;
        }
        if (!isset($response[$clientRep]['repairConfirmation'])) {
            return false;
        }
        if ($status === 'RFPU') {
            $this->readyForPickUp = 1;
            $this->update();
        }
        return true;
    }

    public function updateTotalOrder($force_repair_update = false)
    {
        if (!$this->rowId) {
            if (!$this->load()) {
                $this->addError('Erreur: réparation non enregistrée');
                return false;
            }
        }

        if (!isset($this->gsx)) {
            $this->addError('Echec de la connexion à GSX');
            return false;
        }

        if (!isset($this->confirmNumbers['repair']) || empty($this->confirmNumbers['repair'])) {
            $this->addError('Erreur: n° de confirmation de la réparation absent');
            return false;
        }

        if ($this->isIphone) {
            $client = 'IPhoneRepairDetailsLookup';
            $requestName = 'IPhoneRepairDetailsLookupRequest';
        } else {
            $client = 'RepairDetailsLookup';
            $requestName = 'RepairDetailsLookupRequest';
        }

        $n = count($this->gsx->errors['soap']);

        $request = $this->gsx->_requestBuilder($requestName, 'repairConfirmationNumber', $this->confirmNumbers['repair']);
        $response = $this->gsx->request($request, $client);

        if (count($this->gsx->errors['soap']) > $n) {
            return false;
        }

        $parts = null;
        if ($this->isIphone) {
            if (isset($response['IPhoneRepairDetailsLookupResponse']['lookupResponseData'])) {
                $response = $response['IPhoneRepairDetailsLookupResponse']['lookupResponseData'];
            }
        } else {
            if (isset($response['RepairDetailsLookupResponse']['repairDetails'])) {
                $response = $response['RepairDetailsLookupResponse']['repairDetails'];
            }
        }
        if (isset($response['repairPartDetailsInfo'])) {
            $parts = $response['repairPartDetailsInfo'];
        } else {
            $this->addError('Echec de la récupération du montant total: Aucune réponse reçue');
            return false;
        }

        if (isset($parts['partNumber'])) {
            $parts = array($parts);
        }

        $totalFromOrder = 0;
        foreach ($parts as $part) {
            if (isset($part['netPrice']) && $part['netPrice']) {
                $totalFromOrder += (float) $part['netPrice'];
            }
        }

        if (!$totalFromOrder) {
            if (isset($response['isInvoiced']) && $response['isInvoiced'] === 'Y') {
                $totalFromOrder = 1.00;
            }
        }

        if ($totalFromOrder != $this->totalFromOrder) {
            $this->totalFromOrder = $totalFromOrder;
            $this->totalFromOrderChanged = true;
            if (isset($this->rowId) && $this->rowId) {
                return $this->update();
            }
        } elseif ($force_repair_update && isset($this->rowId) && $this->rowId) {
            return $this->update();
        }
        
        return true;
    }

    public function getInfosHtml($prodId = null)
    {
        if (!isset($this->repairLookUp))
            $this->lookup();

        $html = '<div class="repairInfos" id="repair_' . $this->rowId . '" ';
        $html .= 'data-serial="' . $this->serial . '"';
        if (!is_null($prodId)) {
            $html .= ' data-prodid="' . $prodId . '"';
        }
        $html .= '>' . "\n";
        $html .= '<div class="repairCaption">' . "\n";
        $html .= '<span class="repairTitle">Réparation n° ' . (isset($this->repairNumber) && ($this->repairNumber != '') ? $this->repairNumber : '<span style="color: #550000;">(En attente de validation par Apple)</span>') . '</span>' . "\n";
        $html .= '<div class="repairToolbar">' . "\n";
        if (!$this->readyForPickUp) {
            $html .= '<span class="button blueHover endRepair" onclick="endRepairSubmit($(this), \'' . $this->rowId . '\', true)">Terminer la réparation</span>';
        } else if (!$this->repairComplete) {
            $html .= '<span class="button redHover closeRepair" onclick="closeRepairSubmit($(this), \'' . $this->rowId . '\', true)">Restituer</span>';
        }

        $html .= '</div>' . "\n";
        $html .= '</div>' . "\n";
        $html .= '<p class="confirmation">Réparation créée' . (isset($this->repairLookUp['createdOn']) ? ' le ' . $this->repairLookUp['createdOn'] : '') . '</p>' . "\n";
//        if (!isset($this->repairNumber) || ($this->repairNumber == ''))
//            $html .= '<p class="error">Erreur: numéro de réparation absent</p>';
        if (!isset($this->confirmNumbers['repair']) || ($this->confirmNumbers['repair'] == ''))
            $html .= '<p class="error">numéro de confirmation absent</p>' . "\n";
        else
            $html .= '<p><strong>N° de confirmation: </strong>' . $this->confirmNumbers['repair'] . '</p>';

        if (isset($this->totalFromOrder)) {
            $html = '<p><strong>Sous garantie: </strong>';
            $msg = 'Sous garantie: ';
            if ((float) $this->totalFromOrder > 0) {
                $html .= 'NON ';
                $msg .= 'NON ';
                if ((float) $this->totalFromOrder != 1) {
                    $html .= '(montant: ' . $this->totalFromOrder . ' &euro;)';
                    $msg .= '(montant: ' . $this->totalFromOrder . ' €)';
                }
            } else {
                $html .= 'OUI';
                $msg .= 'OUI';
            }
            $html .= '</p>';
            if ($this->totalFromOrderChanged) {
                $html .= '<script type="text/javascript">';
                $html .= 'alert("' . $msg . '")';
                $html .= '</script>';
            }
        }
        $html .= '<p><strong>Statut dans GSX: </strong>';
        if (isset($this->repairLookUp['repairStatus']))
            $html .= $this->repairLookUp['repairStatus'];
        else
            $html .= '<span style="color: #550000">inconnu</span>';
        $html .= '</p>';
        if ($this->repairComplete) {
            $html .= '<p class="confirmation">Réparation terminée</p>' . "\n";
            if ($this->isReimbursed) {
                $html .= '<p class="confirmation">Réparation remboursée</p>' . "\n";
            } else {
                $html .= '<p style="text-align: center">';
                $html .= '<span class="button blueHover markAsReimbursed" onclick="markRepairAsReimbursed($(this), \'' . $this->rowId . '\')">Marquer comme remboursée</span>';
                $html .= '</p>';
            }
        } else {
            $html .= $this->getPartsPendingReturnHtml();
        }
        $html .= '<div class="repairRequestsResults"></div>' . "\n";
        $html .= '</div>' . "\n";
        return $html;
    }

    public function loadPartsPending()
    {
        if (!isset($this->gsx))
            return false;

        if (isset($this->partsPending)) {
            unset($this->partsPending);
            $this->partsPending = array();
        }
        $this->gsx->resetSoapErrors();
        $datas = array(
            'repairType'               => '',
            'repairStatus'             => '',
            'purchaseOrderNumber'      => '',
            'sroNumber'                => isset($this->repairNumber) ? $this->repairNumber : '',
            'repairConfirmationNumber' => isset($this->confirmNumbers['repair']) ? $this->confirmNumbers['repair'] : '',
            'serialNumbers'            => array(
                'serialNumber' => ''
            ),
            'shipToCode'               => '',
            'customerFirstName'        => '',
            'customerLastName'         => '',
            'customerEmailAddress'     => '',
            'createdFromDate'          => '',
            'createdToDate'            => '',
            'warrantyType'             => '',
            'kbbSerialNumberFlag'      => '',
            'comptiaCode'              => '',
        );

        if ($this->isIphone) {
            $client = 'IPhonePartsPendingReturn';
            $requestName = 'IPhonePartsPendingReturnRequest';
        } else {
            $client = 'PartsPendingReturn';
            $requestName = 'PartsPendingReturnRequest';
        }

        $request = $this->gsx->_requestBuilder($requestName, 'repairData', $datas);
        $response = $this->gsx->request($request, $client);


        if (count($this->gsx->errors['soap'])) {
            return false;
        }
        if (isset($response[$client . 'Response']['partsPendingResponse'])) {
            $partsPending = $response[$client . 'Response']['partsPendingResponse'];
            $this->partsPending = array();
            if (isset($partsPending['returnOrderNumber'])) {
                $partsPending = array($partsPending);
            }
//            echo "<pre>";print_r($partsPending);
            foreach ($partsPending as $part) {
                $fileName = null;
                if (isset($part['returnOrderNumber']) && isset($part['partNumber'])) {
                    if ($this->isIphone) {
                        $client2 = 'IPhoneReturnLabel';
                    } else {
                        $client2 = 'ReturnLabel';
                    }
                    $requestName2 = $client2 . 'Request';

                    $request = $this->gsx->_requestBuilder($requestName2, '', array(
                        'returnOrderNumber' => $part['returnOrderNumber'],
                        'partNumber'        => $part['partNumber']
                    ));
                    $labelResponse = $this->gsx->request($request, $client2);
                    if (isset($labelResponse[$client2 . 'Response']['returnLabelData']['returnLabelFileName'])) {
                        $direName = '/synopsischrono/' . $_REQUEST['chronoId'] . '';
                        $fileNamePure = str_replace("/", "_", $labelResponse[$client2 . 'Response']['returnLabelData']['returnLabelFileName']);
                        if (!is_dir(DOL_DATA_ROOT . $direName))
                            mkdir(DOL_DATA_ROOT . $direName);
                        $fileName = $direName . "/" . $fileNamePure;
//                        die(DOL_DATA_ROOT . $fileName);
                        if (!file_exists(DOL_DATA_ROOT . $fileName)) {
                            if (file_put_contents(DOL_DATA_ROOT . $fileName, $labelResponse[$client2 . 'Response']['returnLabelData']['returnLabelFileData']) === false)
                                $fileName = null;
                        }
                        $fileName2 = "/document.php?modulepart=synopsischrono&file=" . urlencode($_REQUEST['chronoId'] . "/" . $fileNamePure);
                    }
                }
                if (1 || $part['registeredForReturn'] == "Y") {
                    $this->partsPending[] = array(
                        'partDescription'   => $part['partDescription'],
                        'partNumber'        => $part['partNumber'],
                        'returnOrderNumber' => $part['returnOrderNumber'],
                        'fileName'          => $fileName2
                    );
                }
                if (count($this->partsPending) == 0)
                    $this->majSerialOk = true;
            }
            return true;
        }
        return false;
    }

    public function getPartsPendingReturnHtml()
    {
        if (!isset($this->repairNumber) || $this->repairNumber == '')
            return '';

        $html = '';
        if (!count($this->partsPending) || count($this->partsPending) == 0) {
            if ($this->loadPartsPending()) {
                if (count($this->gsx->errors['soap'])) {

                    $html .= '<p>Aucun composant en attente de retour n\'a été trouvé. <span class="displaySoapMsg" onclick="displaySoapMessage($(this))">Voir le message soap</span></p>';
                    $html .= '<div class="soapMessageContainer">';
                    $html .= $this->gsx->getGSXErrorsHtml();
                    $html .= '</div>';
                    return $html;
                } else if (!count($this->partsPending) || count($this->partsPending) == 0) {
                    if (isset($this->confirmNumbers['serialUpdate']) && ($this->confirmNumbers['serialUpdate'] != ''))
                        $html .= '<p class="confirmation">Numéros de série des composants retournés à jour</p>' . "\n";
                    $html .= '<p>Aucun composant en attente de retour</p>';
                    return $html;
                }
            }
        }

        $html .= '<div class="partsPendingBloc">' . "\n";
        $html .= '<div class="partsPendingCaption">' . "\n";
        $html .= '<span class="partsPendingTitle">Composant(s) en attente de retour</span>' . "\n";
        $html .= '<div class="repairToolbar">' . "\n";
        if (isset($this->confirmNumbers['serialUpdate']) && ($this->confirmNumbers['serialUpdate'] != ''))
            $html .= '<span class="confirmation" style="color: #DDE2E6; font-size: 12px; font-weight: normal">Numéros de série à jour</span>' . "\n";
        else {
            $html .= '<span class="button blueHover updateSerials"';
            $html .= ' onclick="$(\'#repair_' . $this->rowId . '\').find(\'.serialUpdateFormContainer\').stop().slideDown(250);">';
//            $html .= (isset($this->repairNumber) ? '"' . $this->repairNumber . '"' : 'null') . ')">';
            $html .= 'Mettre à jour les numéros de série</span>' . "\n";
        }
        $html .= '</div>' . "\n";
        $html .= '</div>' . "\n";
        $html .= '<table class="partsPendingTable">' . "\n";
        $html .= '<thead>' . "\n";
        $html .= '<th style="min-width: 250px">Nom</th>' . "\n";
        $html .= '<th style="min-width: 100px">Réf</th>' . "\n";
        $html .= '<th style="min-width: 100px">N° de retour</th>' . "\n";
        $html .= '<th></th>' . "\n";
        $html .= '</thead>' . "\n";
        $html .= '<tbody>' . "\n";
        foreach ($this->partsPending as $part) {
            $html .= '<tr>';
            $html .= '<td>' . $part['partDescription'] . '</td>';
            $html .= '<td class="ref">' . $part['partNumber'] . '</td>';
            $html .= '<td class="ref">' . $part['returnOrderNumber'] . '</td>';
//            if (isset($part['fileName']) && file_exists($part['fileName'])) {
            $html .= '<td><a target="_blank" href="' . DOL_URL_ROOT . $part['fileName'] . '" class="button getReturnLabel">Etiquette de retour</a></td>';
//            } else {
//                $html .= '<td></td>';
//            }
            $html .= '<tr>';
        }
        $html .= '</tbody>' . "\n";
        $html .= '</table>' . "\n";
        if (!isset($this->confirmNumbers['serialUpdate']) || ($this->confirmNumbers['serialUpdate'] == '')) {
            $html .= '<div class="serialUpdateFormContainer">' . "\n";
            $html .= '<span class="closeSerialUpdateForm"';
            $html .= 'onclick="$(this).parent(\'.serialUpdateFormContainer\').stop().slideUp(250);"';
            $html .= '>Fermer</span>' . "\n";
            if (!isset($this->confirmNumbers['repair']))
                $html .= '<p class="error">Erreur interne: numéro de confirmation de la réparation absent.</p>';
            else {
                $html .= '<select class="updateFormSelect">' . "\n";
                $html .= '<option value="partsPendingUpdateBlock">Mise à jour des numéros de série des composants</option>' . "\n";
                $html .= '<option value="kgbUpdateBlock">Mise à jour du numéro de série de l\'unité</option>' . "\n";
                $html .= '</select>' . "\n";
                $html .= '<span class="button greenHover" onclick="switchUpdateSerialForm($(this))">Afficher le formulaire</span>' . "\n";
                $valDef = array();
                $valDef['repairConfirmationNumber'] = $this->confirmNumbers['repair'];

                $valDef['partInfo'] = array();
                foreach ($this->partsPending as $partPending) {
                    $valDef['partInfo'][] = array(
                        'partNumber'      => $partPending['partNumber'],
                        'partDescription' => $partPending['partDescription'],
                    );
                }
                $gsxRequest = new GSX_Request($this->gsx, 'UpdateSerialNumber');
                $html .= '<div class="partsPendingUpdateBlock updateSerialFormBlock">' . "\n";
                $html .= $gsxRequest->generateRequestFormHtml($valDef, $this->prodId, $this->serial, $this->rowId);
                $html .= '<div class="partsPendingSerialUpdateResults"></div>' . "\n";
                $html .= '</div>' . "\n";


                unset($gsxRequest);
                $gsxRequest = new GSX_Request($this->gsx, 'KGBSerialNumberUpdate');
                $html .= '<div class="kgbUpdateBlock updateSerialFormBlock">' . "\n";
                $html .= $gsxRequest->generateRequestFormHtml(array(
                    'repairConfirmationNumber' => isset($this->confirmNumbers['repair']) ? $this->confirmNumbers['repair'] : ''
                        ), $this->prodId, $this->serial, $this->rowId);
                $html .= '<div class="kgbSerialUpdateResults"></div>' . "\n";
                $html .= '</div>' . "\n";

                $html .= '</div>' . "\n";
            }
        }
        $html .= '</div>' . "\n";
        return $html;
    }
}
?>
