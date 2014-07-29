<?php

require_once DOL_DOCUMENT_ROOT . '/synopsisapple/GSXRequests.php';

class Repair {

    public static $lookupNumbers = array(
        'serialNumber' => 'Numéro de série',
        'repairNumber' => 'Numéro de réparation (repair #)',
        'repairConfirmationNumber' => 'Numéro de confirmation (Dispatch ID)',
        'purchaseOrderNumber' => 'Numéro de bon de commande (Purchase Order)'
    );
    public $gsx = null;
    public $db = null;
    public $serial = null;
    public $prodId = null;
    public $rowId = null;
    public $partsPending = null;
    public $confirmNumbers = array();
    public $repairStatus = null;
    public $repairNumber = null;
    public $repairComplete = 0;
    protected $repairLookUp = null;
    protected $errors = array();

    public function __construct($db, $gsx) {
        $this->db = $db;
        $this->gsx = $gsx;
    }

    public function addError($msg) {
        $this->errors[] = $msg;
    }

    public function displayErrors() {
        $html = '';
        foreach ($this->errors as $error) {
            $html .= '<p class="error">' . $error . '</p>';
        }
        $this->errors = array();
        return $html;
    }

    public function setDatas($repairNumber, $confirmNumber, $serialUpdateConfirmNumber, $repairComplete = 0, $rowId = null) {
        $this->repairNumber = $repairNumber;
        $this->confirmNumbers['repair'] = $confirmNumber;
        $this->confirmNumbers['serialUpdate'] = $serialUpdateConfirmNumber;
        $this->repairComplete = $repairComplete;
        if (isset($rowId))
            $this->rowId = $rowId;
    }

    public function create($chronoId, $confirmNumber) {
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
        
        require_once(DOL_DOCUMENT_ROOT."/synopsischrono/Chrono.class.php");
        $chrono = new Chrono($this->db);
        $chrono->fetch($chronoId);
        $chrono->setDatas($chronoId, array(1056=>1));
        return true;
    }

    public function add($chronoId) {
        if (!isset($chronoId)) {
            $this->addError('impossible de crée la réparation (chronoId absent)');
            return false;
        }

        if (isset($this->rowId)) {
            return $this->update();
        } else {
            $sql = 'INSERT INTO `' . MAIN_DB_PREFIX . 'synopsis_apple_repair` (`chronoId`, `repairNumber`, `repairConfirmNumber`, `serialUpdateConfirmNumber`, `closed`) ';
            $sql .= 'VALUES (';
            $sql .= '"' . $chronoId . '", ';
            $sql .= (isset($this->repairNumber) ? '"' . $this->repairNumber . '"' : 'NULL') . ', ';
            $sql .= (isset($this->confirmNumbers['repair']) ? '"' . $this->confirmNumbers['repair'] . '"' : 'NULL') . ', ';
            $sql .= (isset($this->confirmNumbers['serialUpdate']) ? '"' . $this->confirmNumbers['serialUpdate'] . '"' : 'NULL') . ', ';
            $sql .= '"' . $this->repairComplete . '"';
            $sql .= ')';

            echo $sql;
            if (!$this->db->query($sql)) {
                $this->addError('Echec de l\'enregistrement en base de données<br/>Erreur SQL: ' . $this->db->lasterror());
                return false;
            }
            $this->cartRowId = $this->db->last_insert_id(MAIN_DB_PREFIX . 'synopsis_apple_repair');
        }
        return true;
    }

    public function update() {
        if (!isset($this->rowId)) {
            $this->addError('Impossible de mettre à jour les données de la réparation (ID absent)');
            return false;
        }

        $sql = 'UPDATE `' . MAIN_DB_PREFIX . 'synopsis_apple_repair` SET ';
        if (isset($this->repairNumber))
            $sql .= '`repairNumber` = "' . $this->repairNumber . '", ';
        if (isset($this->confirmNumbers['repair']))
            $sql .= '`repairConfirmNumber` = "' . $this->confirmNumbers['repair'] . '", ';
        if (isset($this->confirmNumbers['serialUpdate']))
            $sql .= '`serialUpdateConfirmNumber` = "' . $this->confirmNumbers['serialUpdate'] . '", ';
        $sql .= '`closed` = ' . $this->repairComplete;
        $sql .= ' WHERE `rowid` = ' . $this->rowId;

        if (!$this->db->query($sql)) {
            $this->addError('Echec de l\'enregistrement en base de données<br/>Erreur SQL : ' . $this->db->lasterror());
            return false;
        }
        return true;
    }

    public function import($chronoId, $number, $numberType) {
        if ($this->lookup($number, $numberType)) {
            if (isset($this->confirmNumbers['repair']) &&
                    ($this->confirmNumbers['repair'] != '') &&
                    isset($this->repairNumber) &&
                    ($this->repairNumber != '')) {

                $sql = 'SELECT `chronoId` FROM ' . MAIN_DB_PREFIX . 'synopsis_apple_repair WHERE ';
                $sql .= '`repairNumber` = "' . $this->repairNumber . '" AND ';
                $sql .= '`repairConfirmNumber` = "' . $this->confirmNumbers['repair'] . '"';
                $result = $this->db->query($sql);
                if ($this->db->num_rows($result) > 0) {
                    while ($row = $this->db->fetch_object($result)) {
                        if ($row->chronoId == $chronoId) {
                            $this->addError('Cette réparation a déjà été importé');
                            return false;
                        }
                    }
                }

                return $this->add($chronoId);
            }
        }
        return false;
    }

    public function close($sendRequest = true) {
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

            $request = $this->gsx->_requestBuilder('MarkRepairCompleteRequest', '', array('repairConfirmationNumbers' => $this->confirmNumbers['repair']));
            $response = $this->gsx->request($request, 'MarkRepairComplete');

            if (!isset($response['MarkRepairCompleteResponse']['repairConfirmationNumbers'])) {
                return false;
            }
        }
        if (!$this->db->query("UPDATE  `" . MAIN_DB_PREFIX . "synopsis_apple_repair` SET  `closed` =  1 WHERE  `rowid` = " . $this->rowId . ";")) {
            $this->addError('Echec de l\'enregistrement de la fermeture de la réparation en base de données<br/>
                Erreur SQL : ' . $this->db->lasterror());
            return false;
        }
        $this->repairComplete = 1;
        return true;
    }

    public function load() {
        $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX . 'synopsis_apple_repair WHERE ';
        if (isset($this->rowId) && ($this->rowId != ''))
            $sql .= '`rowid` = "' . $this->rowId . '"';
        else if (isset($this->repairNumber) && ($this->repairNumber != '')) {
            $sql .= '`repairNumber` = "' . $this->repairNumber . '"';
        } else if (isset($this->confirmNumbers['repair']) && $this->confirmNumbers['repair'] != '') {
            $sql .= '`repairConfirmNumber` = "' . $this->confirmNumbers['repair'] . '"';
        } else if (isset($this->confirmNumbers['serialUpdate']) && $this->confirmNumbers['serialUpdate'] != '') {
            $sql .= '`serialUpdateConfirmNumber` = "' . $this->confirmNumbers['serialUpdate'] . '"';
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
            $this->repairComplete = $datas->closed;
            return true;
        }
        return false;
    }

    public function lookup($data = null, $dataType = null) {
        if (!isset($this->gsx))
            return false;

        $n = count($this->gsx->errors['soap']);
        $datas = array(
            'repairConfirmationNumber' => isset($this->confirmNumbers['repair']) ? $this->confirmNumbers['repair'] : '',
            'customerEmailAddress' => '',
            'customerFirstName' => '',
            'customerLastName' => '',
            'fromDate' => '',
            'toDate' => '',
            'incompleteRepair' => '',
            'pendingShipment' => '',
            'purchaseOrderNumber' => '',
            'repairNumber' => isset($this->repairNumber) ? $this->repairNumber : '',
            'repairStatus' => '',
            'repairType' => '',
            'serialNumber' => '',
            'shipToCode' => '',
            'soldToReferenceNumber' => '',
            'technicianFirstName' => '',
            'technicianLastName' => '',
            'unreceivedModules' => '',
        );
        if (isset($data) && isset($dataType)) {
            if (isset($datas[$dataType])) {
                $datas[$dataType] = $data;
            }
        }

        $request = $this->gsx->_requestBuilder('RepairLookupRequest', 'lookupRequestData', $datas);
        $response = $this->gsx->request($request, 'RepairLookup');

        if (count($this->gsx->errors['soap']) > $n)
            return false;

        if (!isset($response['RepairLookupResponse']['lookupResponseData']))
            return false;

        $this->repairLookUp = $response['RepairLookupResponse']['lookupResponseData'];
        if (isset($this->repairLookUp['repairNumber']) && ($this->repairLookUp['repairNumber'] != ''))
            $this->repairNumber = $this->repairLookUp['repairNumber'];
        if (isset($this->repairLookUp['repairConfirmationNumber']) && ($this->repairLookUp['repairConfirmationNumber'] != ''))
            $this->confirmNumbers['repair'] = $this->repairLookUp['repairConfirmationNumber'];
        if (isset($this->repairLookUp['repairStatus']) && ($this->repairLookUp['repairStatus'] != '')) {
            if (($this->repairLookUp['repairStatus'] == 'Closed') || ($this->repairLookUp['repairStatus'] == 'Fermée et complétée'))
                $this->repairComplete = 1;
            else
                $this->repairComplete = 0;
        }
        return true;
    }

    public function getInfosHtml() {
        if (!isset($this->repairLookUp))
            $this->lookup();

        $html = '<div class="repairInfos" id="repair_' . $this->rowId . '">' . "\n";
        $html .= '<div class="repairCaption">' . "\n";
        $html .= '<span class="repairTitle">Réparation n° ' . (isset($this->repairNumber) && ($this->repairNumber != '') ? $this->repairNumber : '<span style="color: #550000;">(inconnu)</span>') . '</span>' . "\n";
        $html .= '<div class="repairToolbar">' . "\n";
        if (!$this->repairComplete)
            $html .= '<span class="button redHover closeRepair" onclick="closeRepairSubmit($(this), \'' . $this->rowId . '\')">Fermer cette réparation</span>';
        $html .= '</div>' . "\n";
        $html .= '</div>' . "\n";
        $html .= '<p class="confirmation">Réparation créée' . (isset($this->repairLookUp['createdOn']) ? ' le ' . $this->repairLookUp['createdOn'] : '') . '</p>' . "\n";
        if (!isset($this->repairNumber) || ($this->repairNumber == ''))
            $html .= '<p class="error">Erreur: numéro de réparation absent</p>';
        if (!isset($this->confirmNumbers['repair']) || ($this->confirmNumbers['repair'] == ''))
            $html .= '<p class="error">numéro de confirmation absent</p>' . "\n";
        else
            $html .= '<p><strong>N° de confirmation: </strong>' . $this->confirmNumbers['repair'] . '</p>';
        $html .= '<p><strong>Statut dans GSX: </strong>';
        if (isset($this->repairLookUp['repairStatus']))
            $html .= $this->repairLookUp['repairStatus'];
        else
            $html .= '<span style="color: #550000">inconnu</span>';
        $html .= '</p>';
        if ($this->repairComplete)
            $html .= '<p class="confirmation">Réparation terminée</p>' . "\n";
        else {
            $html .= $this->getPartsPendingReturnHtml();
        }
        $html .= '<div class="repairRequestsResults"></div>' . "\n";
        $html .= '</div>' . "\n";
        return $html;
    }

    public function loadPartsPending() {
        if (!isset($this->gsx))
            return false;

        if (isset($this->partsPending)) {
            unset($this->partsPending);
        }
        $this->gsx->resetSoapErrors();
        $datas = array(
            'repairType' => '',
            'repairStatus' => '',
            'purchaseOrderNumber' => '',
            'sroNumber' => '',
            'sroNumber' => isset($this->repairNumber) ? $this->repairNumber : '',
            'repairConfirmationNumber' => isset($this->confirmNumbers['repair']) ? $this->confirmNumbers['repair'] : '',
            'serialNumbers' => array(
                'serialNumber' => ''
            ),
            'shipToCode' => '',
            'customerFirstName' => '',
            'customerLastName' => '',
            'customerEmailAddress' => '',
            'createdFromDate' => '',
            'createdToDate' => '',
            'warrantyType' => '',
            'kbbSerialNumberFlag' => '',
            'comptiaCode' => '',
        );
        $request = $this->gsx->_requestBuilder('PartsPendingReturnRequest', 'repairData', $datas);
        $response = $this->gsx->request($request, 'PartsPendingReturn');
        if (count($this->gsx->errors['soap'])) {
            return false;
        }
        if (isset($response['PartsPendingReturnResponse']['partsPendingResponse'])) {
            $partsPending = $response['PartsPendingReturnResponse']['partsPendingResponse'];
            $this->partsPending = array();
            if (isset($partsPending['returnOrderNumber'])) {
                $partsPending = array($partsPending);
            }
            foreach ($partsPending as $part) {
                $fileName = null;
                if (isset($part['returnOrderNumber']) && isset($part['partNumber'])) {
                    $request = $this->gsx->_requestBuilder('ReturnLabelRequest', '', array(
                        'returnOrderNumber' => $part['returnOrderNumber'],
                        'partNumber' => $part['partNumber']
                            ));
                    $labelResponse = $this->gsx->request($request, 'ReturnLabel');
                    if (isset($labelResponse['ReturnLabelResponse']['returnLabelData']['returnLabelFileName'])) {
                        $direName = '/synopsischrono/' . $_REQUEST['chronoId'] . '';
                        $fileNamePure = $labelResponse['ReturnLabelResponse']['returnLabelData']['returnLabelFileName'];
                        if (!is_dir(DOL_DATA_ROOT . $direName))
                            mkdir(DOL_DATA_ROOT . $direName);
                        $fileName = $direName . "/" . $fileNamePure;
//                        die(DOL_DATA_ROOT . $fileName);
                        if (!file_exists(DOL_DATA_ROOT . $fileName)) {
                            if (file_put_contents(DOL_DATA_ROOT . $fileName, $labelResponse['ReturnLabelResponse']['returnLabelData']['returnLabelFileData']) === false)
                                $fileName = null;
                        }
                        $fileName2 = "/document.php?modulepart=synopsischrono&file=" . urlencode($_REQUEST['chronoId'] . "/" . $fileNamePure);
                    }
                }
                $this->partsPending[] = array(
                    'partDescription' => $part['partDescription'],
                    'partNumber' => $part['partNumber'],
                    'returnOrderNumber' => $part['returnOrderNumber'],
                    'fileName' => $fileName2
                );
            }
            return true;
        }
        return false;
    }

    public function getPartsPendingReturnHtml() {
        $html = '';
        if (!count($this->partsPending))
            $this->loadPartsPending();

        if (!count($this->partsPending)) {
            if (isset($this->confirmNumbers['serialUpdate']) && ($this->confirmNumbers['serialUpdate'] != ''))
                $html .= '<p class="confirmation">Numéros de série des composants retournés à jour (n° de confirmation: ' . $this->confirmNumbers['serialUpdate'] . ')</p>' . "\n";
            $html .= '<p>Aucun composant en attente de retour</p>';
            return $html;
        }

        $html .= '<div class="partsPendingBloc">' . "\n";
        $html .= '<div class="partsPendingCaption">' . "\n";
        $html .= '<span class="partsPendingTitle">Composant(s) en attente de retour</span>' . "\n";
        $html .= '<div class="repairToolbar">' . "\n";
        if (isset($this->confirmNumbers['serialUpdate']) && ($this->confirmNumbers['serialUpdate'] != ''))
            $html .= '<span class="confirmation" style="color: #DDE2E6; font-size: 12px; font-weight: normal">Numéros de série à jour (n° de confirmation: ' . $this->confirmNumbers['serialUpdate'] . ')</span>' . "\n";
        else {
            $html .= '<span class="button blueHover updateSerials"';
            $html .= 'onclick="$(\'#repair_' . $this->rowId . '\').find(\'.serialUpdateFormContainer\').stop().slideDown(250);"';
            $html .= (isset($this->repairNumber) ? '"' . $this->repairNumber . '"' : 'null') . ')">';
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
                $valDef = array();
                $valDef['repairConfirmationNumber'] = $this->confirmNumbers['repair'];
            }

            $valDef['partInfo'] = array();
            foreach ($this->partsPending as $partPending) {
                $valDef['partInfo'][] = array(
                    'partNumber' => $partPending['partNumber'],
                    'partDescription' => $partPending['partDescription'],
                );
            }
            $gsxRequest = new GSX_Request($this->gsx, 'UpdateSerialNumber');
            $html .= $gsxRequest->generateRequestFormHtml($valDef, $this->prodId, $this->serial, $this->rowId);
            $html .= '<div class="partsPendingSerialUpdateResults"></div></div>' . "\n";
        }
        $html .= '</div>' . "\n";
        return $html;
    }

}

?>
