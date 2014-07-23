<?php

require_once DOL_DOCUMENT_ROOT . '/synopsisapple/GSXRequests.php';

class gsxDatas {

    public $gsx = null;
    public $connect = false;
    protected $serial = null;
    protected $errors = array();
    protected $confirmNumbers = array();
    public static $apiMode = 'production';
    public static $componentsTypes = array(
        0 => 'Général',
        1 => 'Visuel',
        2 => 'Moniteurs',
        3 => 'Mémoire auxiliaire',
        4 => 'Périphériques d\'entrées',
        5 => 'Cartes',
        6 => 'Alimentation',
        7 => 'Imprimantes',
        8 => 'Périphériques multi-fonctions',
        9 => 'Périphériques de communication',
        'A' => 'Partage',
        'B' => 'iPhone',
        'E' => 'iPod',
        'F' => 'iPad'
    );

    public function __construct($serial, $userId = null, $password = null, $serviceAccountNo = null) {
        global $user;
        if (isset($user->array_options['options_apple_id']) && isset($user->array_options['options_apple_mdp']) && isset($user->array_options['options_apple_service']))
            $details = array(
                'apiMode' => self::$apiMode,
                'regionCode' => 'emea',
                'userId' => $user->array_options['options_apple_id'],
                'password' => $user->array_options['options_apple_mdp'],
                'serviceAccountNo' => $user->array_options['options_apple_service'],
                'languageCode' => 'fr',
                'userTimeZone' => 'CEST',
                'returnFormat' => 'php',
            );
        else if (isset($userId) && isset($password) && isset($serviceAccountNo)) {
            $details = array(
                'apiMode' => self::$apiMode,
                'regionCode' => 'emea',
                'userId' => $userId,
                'password' => $password,
                'serviceAccountNo' => $serviceAccountNo,
                'languageCode' => 'fr',
                'userTimeZone' => 'CEST',
                'returnFormat' => 'php',
            );
        } else {
            echo '<p class="error">Pas d\'identifiant apple.<a href="' . DOL_URL_ROOT . '/user/fiche.php?id=' . $user->id . '"> Corriger</a></p>' . "\n";
            return 0;
        }
        $this->gsx = new GSX($details);
        $this->serial = $serial;
        if (count($this->gsx->errors['init']) || count($this->gsx->errors['soap'])) {
            $this->errors[] = 'GSX_init_error';
        } else {
            $this->connect = true;
        }
    }

    public function getConfirmNumbers() {
        global $db;
        if (isset($this->serial) && $this->serial != "") {
            $result = $db->query("SELECT `confirmNumber`, `serialUpdateConfNum` FROM `" . MAIN_DB_PREFIX . "synopsis_apple_parts_cart` WHERE serial_number = '" . $this->serial . "'");
            if ($db->num_rows($result) > 0) {
                $ligne = $db->fetch_object($result);
                $this->confirmNumbers['repair'] = ($ligne->confirmNumber != '')?$ligne->confirmNumber:null;
                $this->confirmNumbers['serialUpdate'] = ($ligne->serialUpdateConfNum != '')?$ligne->serialUpdateConfNum:null;
            }
        }
    }

    public function isRepairComplete() {
        global $db;
        if (isset($this->serial) && $this->serial != "") {
            $result = $db->query("SELECT `repairComplete` FROM `" . MAIN_DB_PREFIX . "synopsis_apple_parts_cart` WHERE serial_number = '" . $this->serial . "'");
            if ($db->num_rows($result) > 0) {
                $ligne = $db->fetch_object($result);
                return (int) $ligne->repairComplete;
            }
        }
    }

    public function getLookupHtml($prodId) {
        if (count($this->errors) || !$this->connect) {
            return $this->getGSXErrorsHtml();
        }
//        echo "<pre>";print_r($this->gsx->obtainCompTIA());die;
        $response = $this->gsx->lookup($this->serial, 'warranty');
        $check = false;
        $html = '';
        if (isset($response) && count($response)) {
            if (isset($response['ResponseArray']) && count($response['ResponseArray'])) {
                if (isset($response['ResponseArray']['responseData']) && count($response['ResponseArray']['responseData'])) {
                    $datas = $response['ResponseArray']['responseData'];
//                    echo '<pre>';
//                    echo print_r($datas);
//                    echo '</pre>';
                    $check = true;

//                  --- Lookup du produit --->
                    $html .= '<table class="productDatas">' . "\n";
                    $html .= '<thead><caption>' . $datas['productDescription'] . '</caption></thead>' . "\n";
                    $html .= '<tbody>' . "\n";
                    $html .= '' . "\n";

                    $html .= '<tr>' . "\n";

                    $src = $datas['imageURL'];
//                    if (isset($src) && $src) {
                    $html .= '<td class="productImgContainer">' . "\n";
                    $html .= '<img class="productImg" src="' . $src . '"/>' . "\n";
                    $html .= '</td>' . "\n";
//                    }

                    $html .= '<td>' . "\n";
                    $html .= '<table><thead></thead><tbody>' . "\n";
                    $html .= '<tr class="oddRow">' . "\n";
                    $html .= '<td class="rowTitle">Numéro de série</td>' . "\n";
                    $html .= '<td>' . $datas['serialNumber'] . '</td>' . "\n";
                    $html .= '</tr>' . "\n";

                    $html .= '<tr>' . "\n";
                    $html .= '<td class="rowTitle">Configuration</td>' . "\n";
                    $html .= '<td>' . $datas['configDescription'] . '</td>' . "\n";
                    $html .= '</tr>' . "\n";

                    $html .= '<tr class="oddRow">' . "\n";
                    $html .= '<td class="rowTitle">Numéro de garantie</td>' . "\n";
                    $html .= '<td>' . $datas['warrantyReferenceNo'] . '</td>' . "\n";
                    $html .= '</tr>' . "\n";

                    $html .= '<tr>' . "\n";
                    $html .= '<td class="rowTitle">Garantie</td>' . "\n";
                    $html .= '<td>' . $datas['warrantyStatus'] . '</td>' . "\n";
                    $html .= '</tr>' . "\n";

                    $html .= '<tr class="oddRow">' . "\n";
                    $html .= '<td class="rowTitle">Date d\'entrée</td>' . "\n";
                    $html .= '<td>' . $datas['onsiteStartDate'] . '</td>' . "\n";
                    $html .= '</tr>' . "\n";

                    $html .= '<tr>' . "\n";
                    $html .= '<td class="rowTitle">Date de sortie</td>' . "\n";
                    $html .= '<td>' . $datas['onsiteEndDate'] . '</td>' . "\n";
                    $html .= '</tr>' . "\n";

//                    $html .= '<tr>'."\n";
//                    $html .= '<td></td>'."\n";
//                    $html .= '<td>'.$datas[''].'</td>'."\n";
//                    $html .= '</tr>'."\n";

                    $html .= '<tr class="oddRow">' . "\n";
                    $html .= '<td class="rowTitle">Jours restants</td>' . "\n";
                    $html .= '<td>' . $datas['daysRemaining'] . '</td>' . "\n";
                    $html .= '</tr>' . "\n";

                    $html .= '<tr style="height: 30px;">' . "\n";
                    $html .= '<td>' . "\n";
                    $html .= '<a class="productPdfLink" href="' . $datas['manualURL'] . '">Manuel</a>' . "\n";
                    $html .= '</td>' . "\n";
                    $html .= '</tr>' . "\n";

                    $html .= '</tbody></table>' . "\n";
                    $html .= '</td>' . "\n";

                    $html .= '</tr>' . "\n";
                    $html .= '</tbody></table>' . "\n";

//                  --- Etat de la commande --->
                    $repairComplete = $this->isRepairComplete();

                    $html .= '<div class="repairStatus">' . "\n";
                    if ($repairComplete) {
                        $html .= '<p class="confirmation">Réparation terminée</p>' . "\n";
                        $html .= '</div>' . "\n";
                    } else {
                        $requests = array();
                        $this->getConfirmNumbers();
                        if (!isset($this->confirmNumbers['repair'])) {
                            $requests = array_merge($requests, GSX_Request::getRequestsByType('repair'));
                        } else {
                            $html .= '<p class="confirmation">Réparation créée</p>' . "\n";
                            if (!isset($this->confirmNumbers['serialUpdate'])) {
                                $requests = array_merge($requests, GSX_Request::getRequestsByType('serialUpdate'));
                            } else {
                                $html .= '<p class="confirmation">Numéro de série des composants à jour</p>' . "\n";
                            }
                            $requests = array_merge($requests, GSX_Request::getRequestsByType('repairComplete'));
                        }

                        $html .= '<button class="createRepair" onclick="displayCreateRepairPopUp($(this))">Gérer la réparation</button>' . "\n";
                        $html .= '</div>' . "\n";

//                      --- Bloc Réparation --->
                        $html .= '<div class="repairPopUp">' . "\n";
                        $html .= '<input type="hidden" class="prodId" value="' . $prodId . '"/>' . "\n";
                        $html .= '<span class="hidePopUp" onclick="hideCreateRepairPopUp($(this))">Cacher</span>' . "\n";
                        $html .= '<p>Sélectionnez le type d\'opération que vous souhaitez effectuer: <br/></p>';
                        $html .= '<select class="repairTypeSelect">' . "\n";

                        foreach ($requests as $name => $label) {
                            $html .= '<option value="' . $name . '">' . $label . '</option>';
                        }
                        $html .= '</select>';
                        $html .= '<p style="text-align: right">' . "\n";
                        $html .= '<button class="loadRepairForm greenHover" onclick="GSX.loadRepairForm($(this))">Charger le formulaire</button>' . "\n";
                        $html .= '</p>' . "\n";
                        $html .= '<div class="repairFormContainer"></div>';
                        $html .= '</div>' . "\n";

//                      --- Bloc composants --->
                        $html .= '<button class="loadParts" onclick="GSX.loadProductParts($(this))">Charger la liste des composants compatibles</button>' . "\n";
                        $html .= '<div class="partsRequestResult"></div>' . "\n";
                    }
                }
            }
        }
        if (!$check) {
            $this->errors[] = 'GSX_lookup_fail';
            $html .= '<p class="error">Echec de la récupération des données depuis la plateforme Apple GSX</p>' . "\n";
        }

//        $response = $this->gsx->lookup($this->serial, 'model');
//        echo '<pre>';
//        echo print_r($response);
//        echo '</pre>';

        return $html;
    }

    public function getCartHtml($prodId) {
        $html = '<div class="cartContainer"><div class="cartTitle">Panier de composants   ';
        $html .= '<span><span class="nbrCartProducts">0</span> produit(s)</span></div></div>' . "\n";

        $html .= '<div class="cartContent">' . "\n";
        $html .= '<p class="noProducts">Aucun produit dans votre panier de commande</p>' . "\n";
        $html .= '<table class="cartProducts">' . "\n";
        $html .= '<thead>' . "\n";
        $html .= '<th style="min-width: 250px">Nom</th>' . "\n";
        $html .= '<th style="min-width: 80px">Réf</th>' . "\n";
        $html .= '<th style="min-width: 80px">Prix</th>' . "\n";
        $html .= '<th>Qté</th>' . "\n";
        $html .= '<th class="comptiaCodeTitle">CompTIA Code</th>' . "\n";
        $html .= '</thead>' . "\n";
        $html .= '<tbody></tbody>' . "\n";
        $html .= '</table>' . "\n";
        $html .= '<div class="cartSubmitContainer">' . "\n";
        $html .= '<button class="cartSave greenHover deactivated" onclick="GSX.products[' . $prodId . '].cart.save()">Sauvegarder le panier</button>' . "\n";
        $html .= '<button class="cartLoad blueHover" onclick="GSX.products[' . $prodId . '].cart.load()">Charger le panier</button>' . "\n";
        $html .= '</div>' . "\n";
        $html .= '<div class="cartRequestResults"></div>' . "\n";
        $html .= '</div>' . "\n";
        return $html;
    }

    public function getPartsListArray($partNumberAsKey = false) {
        $parts = $this->gsx->part(array('serialNumber' => $this->serial));
        if (isset($parts) && count($parts)) {
            if (isset($parts['ResponseArray']) && count($parts['ResponseArray'])) {
                if (isset($parts['ResponseArray']['responseData']) && count($parts['ResponseArray']['responseData'])) {
                    $parts = $parts['ResponseArray']['responseData'];
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
        return null;
    }

    public function getPartsListHtml($prodId, $displayCart = true) {
        $parts = $this->getPartsListArray();
        $check = false;
        if (isset($parts) && count($parts)) {
            $check = true;
            $html = '';
            if ($displayCart)
                $html .= $this->getCartHtml($prodId);
            $html .= '<div class="componentsListContainer">' . "\n";
            $html .= '<div class="titre">Liste des composants compatibles</div>' . "\n";
            $html .= '<div class="typeFilters searchBloc">' . "\n";
            $html .= '<button class="filterTitle">Filtrer par catégorie de composant</button>';
            $html .= '<div class="typeFiltersContent">' . "\n";
            $html .= '<div style="margin-bottom: 20px;">' . "\n";
            $html .= '<span class="filterCheckAll">Tout cocher</span>';
            $html .= '<span class="filterHideAll">Tout décocher</span></div></div>';
            $html .= '</div>' . "\n";
            $html .= '<div class="searchBloc"' . "\n";
            $html .= '<label for="keywordFilter">Filtrer par mots-clés: </label>' . "\n";
            $html .= '<input type="text max="80" name="keywordFilter" class="keywordFilter"/>' . "\n";
            $html .= '<select class="keywordFilterType">' . "\n";
            $types = array('name' => 'Nom', 'num' => 'Référence', 'type' => 'Type', 'price' => 'Prix');
            foreach ($types as $key => $type) {
                $html .= '<option value="' . $key . '">' . $type . '</option>' . "\n";
            }
            $html .= '</select>' . "\n";
            $html .= '<button class="addKeywordFilter" onclick="GSX.products[' . $prodId . '].PM.addKeywordFilter()">Ajouter</button>' . "\n";
            $html .= '</div>' . "\n";
            $html .= '<div class="searchBloc">' . "\n";
            $html .= '<label for="searchPartInput">Recherche par référence: </label>' . "\n";
            $html .= '<input type="text" name="searchPartInput" class="searchPartInput" size="12" maxlength="24"/>';
            $html .= '<button class="searchPartSubmit" onclick="GSX.products[' . $prodId . '].PM.searchPartByNum()">Rechercher</button>' . "\n";
            $html .= '</div>' . "\n";
            $html .= '<div class="curKeywords"></div>' . "\n";
            $html .= '<div class="searchResult"></div>';
            $html .= '<div class="partsListContainer"></div>' . "\n";
            $html .= '</div>' . "\n";

            $html .= '<script type="text/javascript">' . "\n";
            foreach ($parts as $part) {
                $html .= 'GSX.addPart(' . $prodId . ', ';
                $html .= '\'' . (isset($part['componentCode']) ? addslashes($part['componentCode']) : '') . '\'';
                $html .= ', \'' . (isset($part['partDescription']) ? addslashes($part['partDescription']) : '') . '\'';
                $html .= ', \'' . (isset($part['partNumber']) ? addslashes($part['partNumber']) : '') . '\'';
                $html .= ', \'' . (isset($part['partType']) ? addslashes($part['partType']) : '') . '\'';
                $html .= ', \'' . (isset($part['stockPrice']) ? addslashes($part['stockPrice']) : '') . '\'';
                $html .= ');' . "\n";
            }
            $html .= '</script>' . "\n";
        }
        if (!$check) {
            $html .= '<p class="error">Echec de la récupération de la liste des composants compatibles depuis la plateforme GSX</p>';
        }
        return $html;
//        echo '<pre>';
//        print_r($this->gsx->obtainCompTIA());
//        echo '</pre>';
//        return '';
    }

    public function getCompTIACodesArray() {
        $datas = $this->gsx->obtainCompTIA();
        $codes = array(
            'grps' => array(),
            'mods' => array()
        );
        $check = false;
        if (isset($datas) && count($datas)) {
            if (isset($datas['ComptiaCodeLookupResponse']) && count($datas['ComptiaCodeLookupResponse'])) {
                if (isset($datas['ComptiaCodeLookupResponse']['comptiaInfo']) && count($datas['ComptiaCodeLookupResponse']['comptiaInfo'])) {
                    $datas = $datas['ComptiaCodeLookupResponse']['comptiaInfo'];
                    $check = true;
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
                    } else
                        $check = false;
                    if (isset($datas['comptiaModifier']) && count($datas['comptiaModifier'])) {
                        foreach ($datas['comptiaModifier'] as $mod) {
                            $codes['mods'][$mod['modifierCode']] = $mod['comptiaDescription'];
                        }
                    } else
                        $check = false;
                }
            }
        }
        if (!$check)
            return 'fail';
        return $codes;
    }

    public function getRequestFormHtml($requestType, $prodId) {
        global $db;
        $gsxRequest = new GSX_Request($this, $requestType);

        $chronoId = null;
        if (isset($_REQUEST['chronoId'])) {
            $chronoId = $_REQUEST['chronoId'];
        }

        $valDef['serialNumber'] = $this->serial;
        if (isset($chronoId)) {
            require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/Chrono.class.php");
            $chrono = new Chrono($db);
            $chrono->fetch($chronoId);
            $chrono->getValues($chronoId);

            $tech = new User($db);
            $tech->fetch($chrono->extraValue[$chronoId]['Technicien']['value']);

            $valDef['diagnosis'] = $chrono->extraValue[$chronoId]['Diagnostique']['value'];
            $dateH = explode(" ", $chrono->extraValue[$chronoId]['Date / Heure']['value']);
            $valDef['unitReceivedDate'] = $dateH[0];
            $valDef['unitReceivedTime'] = $dateH[1];

            $valDef['diagnosedByTechId'] = $tech->array_options['options_apple_techid'];
            $valDef['shipTo'] = $tech->array_options['options_apple_shipto'];
            $valDef['billTo'] = $tech->array_options['options_apple_service'];
            $valDef['poNumber'] = $chrono->ref;

//        echo "<pre>"; print_r($chrono->contact);

            $valDef['customerAddress']['companyName'] = $chrono->societe->name;
            if (isset($chrono->contact->id)) {
                $valDef['customerAddress']['street'] = $chrono->contact->address;
                $valDef['customerAddress']['addressLine1'] = $chrono->contact->address;
//            $valDef['addressLine2'] = $chrono->contact->;
//            $valDef['addressLine3'] = $chrono->contact->;
//            $valDef['addressLine4'] = $chrono->contact->;
                $valDef['customerAddress']['city'] = $chrono->contact->town;
                $valDef['customerAddress']['country'] = "FRANCE";
                $valDef['customerAddress']['firstName'] = $chrono->contact->firstname;
                $valDef['customerAddress']['lastName'] = $chrono->contact->lastname;
                $valDef['customerAddress']['primaryPhone'] = $chrono->contact->phone_pro;
                $valDef['customerAddress']['secondaryPhone'] = $chrono->contact->phone_mobile;
                $valDef['customerAddress']['zipCode'] = $chrono->contact->zip;
                $valDef['customerAddress']['state'] = substr($chrono->contact->zip, 0, 2);
                $valDef['customerAddress']['emailAddress'] = $chrono->contact->email;
            }
        }
//        print_r($chrono->extraValue);
        $this->getConfirmNumbers();
        if (isset($this->confirmNumbers['repair'])) {
            $valDef['repairConfirmationNumber'] = $this->confirmNumbers['repair'];
            $valDef['repairConfirmationNumbers'] = array($this->confirmNumbers['repair']);
            $valDef['returnOrderNumber'] = $this->confirmNumbers['repair'];
        }

//            $valDef['customerAddress'] = array(
//                'companyName' => 'kjhsdk',
//                'street' => 'hkjlkjh',
//                'addressLine1' => 'kjhlkhj',
//                'city' => 'lkjhdsfl',
//                'country' => 'kjhlk',
//                'firstName' => 'kjhlk',
//                'lastName' => 'lkjhlk',
//                'primaryPhone' => '0000000000',
//                'zipCode' => '42000',
//                'state' => '042',
//                'emailAddress' => 'dsfsdf@dsfsf.fr'
//            );
//            $valDef['diagnosis'] = 'sfsdfsd';
//            $valDef['unitReceivedDate'] = '20/04/14';
//            $valDef['unitReceivedTime'] = '02:30 PM';
//            $valDef['diagnosedByTechId'] = 'dgdfg';
//            $valDef['shipTo'] = 'sgsdgs';
//            $valDef['billTo'] = 'sdgsdg';
//            $valDef['poNumber'] = 'dssdg';
        return $gsxRequest->generateRequestFormHtml($valDef, $prodId, $this->serial);
    }

    public function processRequestForm($prodId, $requestType) {
        global $db;
        $GSXRequest = new GSX_Request($this, $requestType);
        $result = $GSXRequest->processRequestForm($prodId, $this->serial);
        $html = '';
        if ($GSXRequest->isLastRequestOk()) {

            $html .= '<div class="requestResponseContainer">';

            $client = $GSXRequest->requestName;
            $request = $GSXRequest->request;
            $wrapper = $GSXRequest->wrapper;

            $requestData = $this->gsx->_requestBuilder($request, $wrapper, $result);

            $response = $this->gsx->request($requestData, $client);
            if (count($this->gsx->errors['soap'])) {
                $html .= '<p class="error">Echec de l\'envoi de la requête<br/>' . "\n";
                $i = 1;
                foreach ($this->gsx->errors['soap'] as $soapError) {
                    $html .= $i . '. ' . utf8_encode(str_replace("?", "'", $soapError)) . '.<br/>' . "\n";
                    $i++;
                }
                $html .= '</p>' . "\n";
            } else {
                if (isset($response['CreateCarryInResponse']['repairConfirmation']['confirmationNumber'])) {//Numero de rep
                    $this->confirmNumbers['repair'] = $response['CreateCarryInResponse']['repairConfirmation']['confirmationNumber'];
                    $db->query("UPDATE  `" . MAIN_DB_PREFIX . "synopsis_apple_parts_cart` SET  `confirmNumber` =  '" . $this->confirmNumbers['repair'] . "' WHERE  serial_number = '" . $this->serial . "';");
                }
                if (isset($response['UpdateSerialNumberResponse']['repairConfirmation']['confirmationNumber'])) {
                    $this->confirmNumbers['serialUpdate'] = $response['CreateCarryInResponse']['repairConfirmation']['confirmationNumber'];
                    $db->query("UPDATE  `" . MAIN_DB_PREFIX . "synopsis_apple_parts_cart` SET  `serialUpdateConfNum` =  '" . $this->confirmNumbers['serialUpdate'] . "' WHERE  serial_number = '" . $this->serial . "';");
                }
                if (isset($response['MarkRepairCompleteResponse'])) {
                    $db->query("UPDATE  `" . MAIN_DB_PREFIX . "synopsis_apple_parts_cart` SET  `repairComplete` =  1 WHERE  serial_number = '" . $this->serial . "';");
                }

                $html .= '<p class="confirmation">Requête envoyé avec succès.</p>';
//                $html .= '<pre>';
//                $html .= print_r($this->gsx->outputFormat($response), true);
//                $html .= '</pre>';

                if (isset($response['ReturnLabelResponse']['returnLabelData']['returnLabelFileName'])) {
                    $dossier = "/home/megajean/Bureau/pdf/";
                    file_put_contents($dossier . $response['ReturnLabelResponse']['returnLabelData']['returnLabelFileName'], $response['ReturnLabelResponse']['returnLabelData']['returnLabelFileData']);
                }
            }

            $html .= '</div>';
            if (isset($_REQUEST['chronoId'])) {
                $html .= '<div style="margin: 30px;">';
                $html .= '<a class="button" href="' . DOL_URL_ROOT . '/synopsischrono/fiche.php?id=' . $_REQUEST['chronoId'] . '">Retour</a>';
                $html .= '</div>';
            } else {
                $html .= '<div style="margin: 30px;">';
                $html .= '<a class="button" href="' . DOL_URL_ROOT . '/synopsisapple/test.php">Retour</a>';
                $html .= '</div>';
            }
        } else {
            $html = $result;
        }
        return $html;
    }

    public function getGSXErrorsHtml() {
        $html = '';
        if (count($this->gsx->errors['init'])) {
            $html .= '<p class="error">Erreur(s) de connection: <br/>';
            $i = 1;
            foreach ($this->gsx->errors['init'] as $errorMsg) {
                $html .= $i . '. ' . utf8_encode(str_replace("?", "'", $errorMsg)) . '.<br/>' . "\n";
                $i++;
            }
            $html .= '</p>';
        }
        if (count($this->gsx->errors['soap'])) {
            $html .= '<p class="error">Erreur(s) SOAP: <br/>';
            $i = 1;
            foreach ($this->gsx->errors['soap'] as $errorMsg) {
                $html .= $i . '. ' . utf8_encode(str_replace("?", "'", $errorMsg)) . '.<br/>' . "\n";
                $i++;
            }
            $html .= '</p>';
        }
        return $html;
    }

}

?>
