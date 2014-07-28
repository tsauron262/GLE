<?php

require_once DOL_DOCUMENT_ROOT . '/synopsisapple/repair.class.php';

class gsxDatas {

    private $userExchangePrice = true;
    public $gsx = null;
    public $connect = false;
    protected $serial = null;
    protected $errors = array();
    protected $repairs = array();
    public $partsPending = null;
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

    public function loadRepairs($chronoId) {
        if (!isset($chronoId))
            return;

        global $db;
        $sql = 'SELECT * FROM `' . MAIN_DB_PREFIX . 'synopsis_apple_repair` WHERE `chronoId` = "'.$chronoId.'"';
        $rows = $db->query($sql);
        if ($db->num_rows($rows) > 0) {
            while ($row = $db->fetch_object($rows)) {
                $repair = new Repair($db, $this->gsx);
                $repair->serial = $this->serial;
                $repair->setDatas($row->repairNumber, $row->repairConfirmNumber, $row->serialUpdateConfirmNumber, $row->closed, $row->rowid);
                $this->repairs[] = $repair;
            }
        }
    }

    public function importRepair($chronoId) {
        if (!$this->connect)
            return $this->getGSXErrorsHtml();
        global $db;
        if (!isset($_GET['importNumber']) || !isset($_GET['importNumberType']))
            return '<p class="error">Erreur: informations manquantes.</p>';
        $repair = new Repair($db, $this->gsx);
        if ($repair->import($chronoId, $_GET['importNumber'], $_GET['importNumberType'])) {
            return '<p class="confirmation">Les données de la réparation ont été importées avec succès</p><ok>Reload</ok>';
        }
        $html = '<p class="error">Echec de l\'importation.</p>';
        $html .= $repair->displayErrors();
        if (count($this->gsx->errors['soap']))
            $html .= $this->getGSXErrorsHtml();
        return $html;
    }

    public function closeRepair($repairRowId) {
        if (!$this->connect)
            return $this->getGSXErrorsHtml();
        global $db;

        $repair = new Repair($db, $this->gsx);
        $repair->rowId = $repairRowId;
        if ($repair->load()) {
            if ($repair->close())
                return 'ok';
        }
        $html = '<p class="error">Echec de la fermeture de la réparation</p>';
        $html .= $repair->displayErrors();
        if (count($this->gsx->errors['soap']))
            $html .= $this->getGSXErrorsHtml();
        return $html;
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

                    $html .= '<div class="container productContainer">' . "\n";
                    $html .= '<div class="captionContainer productCaption" onclick="onCaptionClick($(this))">' . "\n";
                    $html .= '<span class="captionTitle productTile">' . $datas['productDescription'] . '</span>' . "\n";
                    $html .= '<span class="arrow upArrow"></span>';
                    $html .= '</div>' . "\n";
                    $html .= '<div class="blocContent productContent">' . "\n";

                    $html .= '<div class="container">' . "\n";
                    $html .= '<div class="captionContainer" onclick="onCaptionClick($(this))">' . "\n";
                    $html .= '<span class="captionTitle infosProdTitle">Informations produit</span>' . "\n";
                    $html .= '<span class="arrow upArrow"></span>';
                    $html .= '</div>' . "\n";
                    $html .= '<div class="blocContent">' . "\n";

                    $html .= '<table class="productDatas">' . "\n";
                    $html .= '<thead></thead>' . "\n";
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

                    $html .= '<tr><td class="rowTitle">Date de sortie</td><td>' . $datas['onsiteEndDate'] . '</td></tr>' . "\n";
                    $html .= '<tr class="oddRow"><td class="rowTitle">Jours restants</td><td>' . $datas['daysRemaining'] . '</td></tr>' . "\n";
                    $html .= '<tr style="height: 30px;"><td><a class="productPdfLink" href="' . $datas['manualURL'] . '">Manuel</a></td></tr>' . "\n";

                    $html .= '</tbody></table>' . "\n";
                    $html .= '</td>' . "\n";

                    $html .= '</tr>' . "\n";
                    $html .= '</tbody></table>' . "\n";
                    $html .= '</div></div>' . "\n";

                    $html .= $this->getRepairsHtml($prodId);
                    $html .= $this->getCartHtml($prodId);
                    global $db;
                    $cart = new partsCart($db, $this->serial);
                    $cart->loadCart();
                    if (count($cart->partsCart)) {
                        $html .= '<script type="text/javascript">' . "\n";
                        $html .= $cart->getJsScript($prodId);
                        $html .= '</script>' . "\n";
                    }

                    $html .= '<div class="container">' . "\n";
                    $html .= '<div class="captionContainer" onclick="onCaptionClick($(this))">' . "\n";
                    $html .= '<span class="captionTitle partsListTitle">Liste des composants compatibles</span>' . "\n";
                    $html .= '<span class="arrow upArrow"></span>';
                    $html .= '</div>' . "\n";
                    $html .= '<div class="blocContent">' . "\n";
                    $html .= '<div class="toolBar">' . "\n";
                    $html .= '<span class="button loadParts" onclick="GSX.loadProductParts($(this))">Charger la liste des composants compatibles</span>' . "\n";
                    $html .= '</div>' . "\n";
                    $html .= '<div class="partsRequestResult"></div>' . "\n";
                    $html .= '</div></div>' . "\n";

                    $html .= '</div></div>' . "\n";
                }
            }
        }
        if (!$check) {
            $this->errors[] = 'GSX_lookup_fail';
            $html .= '<p class="error">Echec de la récupération des données depuis la plateforme Apple GSX</p>' . "\n";
            $html .= $this->getGSXErrorsHtml();
        }

//        $response = $this->gsx->lookup($this->serial, 'model');
//        echo '<pre>';
//        echo print_r($response);
//        echo '</pre>';

        return $html;
    }

    public function getRepairsHtml($prodId) {
        $html = '<div class="repairsContainer container">' . "\n";
        $html .= '<div class="rapairsCaption captionContainer" onclick="onCaptionClick($(this))">' . "\n";
        $html .= '<span class="repairsTitle captionTitle")">Réparations</span>';
        $html .= '<span class="arrow downArrow"></span>';
        $html .= '</div>' . "\n";
        $html .= '<div class="repairsContent blocContent">' . "\n";
        $html .= '<div class="toolBar">' . "\n";
        $html .= '<span class="button importRepairDatas" onclick="openRepairImportForm(' . $prodId . ')">Importer depuis GSX</span>';
        $html .= '<span class="button createNewRepair greenHover" onclick="displayCreateRepairPopUp($(this))">Créer une nouvelle réparation</span>';
        $html .= '</div>';
        $html .= '<div class="importRepairForm">' . "\n";
        $html .= '<p class="info">Afin de récupérer les données d\'une réparation déjà effectuée sur le site gsx.apple.com, ';
        $html .= 'merci de saisir l\'un des identifiants proposés que vous pourrez trouver dans votre espace GSX.<br/></p>';
        $html .= '<label for="importNumber">Identifiant: </label>' . "\n";
        $html .= '<input type="text" name="importNumber" id="importNumber" class="importNumber"';
        if (isset($this->serial))
            $html .= ' value="' . $this->serial . '"';
        $html .= '/>' . "\n";
        $html .= '<label for="importNumberType">de type: </label>' . "\n";
        $html .= '<select name="importNumberType" id="importNumberType" class="importNumberType">' . "\n";
        foreach (Repair::$lookupNumbers as $value => $text) {
            $html .= '<option value="' . $value . '">' . $text . '</option>';
        }
        $html .= '</select>' . "\n";
        $html .= '<div style="padding: 15px; text-align: right;">' . "\n";
        $html .= '<span class="button redHover repairImportClose" onclick="closeRepairImportForm(' . $prodId . ')">Annuler</span>';
        $html .= '<span class="button greenHover repairImportSubmit" onclick="importRepairSubmit(' . $prodId . ')">Importer</span>';
        $html .= '</div>' . "\n";
        $html .= '<div class="importRepairResult"></div>' . "\n";
        $html .= '</div>' . "\n";
        if (isset($_REQUEST['chronoId'])) {
            $this->loadRepairs($_REQUEST['chronoId']);
            if (count($this->repairs)) {
                foreach ($this->repairs as $repair) {
                    $repair->prodId = $prodId;
                    $html .= $repair->getInfosHtml();
                }
            } else {
                $html .= '<p>Aucune réparation enregistrée</p>';
            }
        } else {
            $html .= '<p class="error">Erreur: Les réparations associées à cette fiche n\'ont pas pu être chargées (id chrono absent)</p>' . "\n";
        }
        $html .= '</div></div>' . "\n";

        $html .= '<div class="repairPopUp">' . "\n";
        $html .= '<input type="hidden" class="prodId" value="' . $prodId . '"/>' . "\n";
        $html .= '<span class="hidePopUp" onclick="hideCreateRepairPopUp($(this))">Cacher</span>' . "\n";
        $html .= '<p>Sélectionnez le type d\'opération que vous souhaitez effectuer: <br/></p>';
        $html .= '<select class="repairTypeSelect">' . "\n";
        $requests = GSX_Request::getRequestsByType('repair');
        foreach ($requests as $name => $label) {
            $html .= '<option value="' . $name . '">' . $label . '</option>';
        }
        $html .= '</select>';
        $html .= '<p style="text-align: right">' . "\n";
        $html .= '<span class="button loadRepairForm greenHover" onclick="GSX.loadRepairForm($(this))">Charger le formulaire</span>' . "\n";
        $html .= '</p>' . "\n";
        $html .= '<div class="repairFormContainer"></div>' . "\n";
        $html .= '<div class="repairFormResults"></div>' . "\n";
        $html .= '</div>' . "\n";
        return $html;
    }

    public function getCartHtml($prodId) {
        $html = '<div class="cartContainer container">' . "\n";
        $html .= '<div class="captionContainer" onclick="onCaptionClick($(this))">' . "\n";
        $html .= '<span class="cartTitle captionTitle">Panier de composants&nbsp;&nbsp;&nbsp;</span>';
        $html .= '<span class=subtitle><span class="nbrCartProducts">0</span> produit(s)</span>' . "\n";
        $html .= '<span class="arrow downArrow"></span>';
        $html .= '</div>' . "\n";

        $html .= '<div class="cartContent blocContent">' . "\n";
        $html .= '<div class="toolBar">' . "\n";
        $html .= '<span class="button addToPropal" onclick="GSX.products[' . $prodId . '].cart.addToPropal($(this))">Ajouter à la propal</span>' . "\n";
        $html .= '<span class="button cartSave greenHover deactivated" onclick="GSX.products[' . $prodId . '].cart.save()">Sauvegarder le panier</span>' . "\n";
        $html .= '<span class="button cartLoad blueHover" onclick="GSX.products[' . $prodId . '].cart.load()">Charger le panier</span>' . "\n";
        $html .= '</div>' . "\n";
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
        $html .= '<div class="cartRequestResults"></div>' . "\n";
        $html .= '</div></div>' . "\n";
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

    public function getPartsListHtml($prodId) {
        $parts = $this->getPartsListArray();
        $check = false;
        if (isset($parts) && count($parts)) {
            $check = true;
            $html = '';
            $html .= '<div class="componentsListContainer container">' . "\n";
            $html .= '<div class="typeFilters searchBloc">' . "\n";
            $html .= '<span class="button filterTitle">Filtrer par catégorie de composant</span>';
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
            $html .= '<span class="button addKeywordFilter" onclick="GSX.products[' . $prodId . '].PM.addKeywordFilter()">Ajouter</span>' . "\n";
            $html .= '</div>' . "\n";
            $html .= '<div class="searchBloc">' . "\n";
            $html .= '<label for="searchPartInput">Recherche par référence: </label>' . "\n";
            $html .= '<input type="text" name="searchPartInput" class="searchPartInput" size="12" maxlength="24"/>';
            $html .= '<span class="button searchPartSubmit" onclick="GSX.products[' . $prodId . '].PM.searchPartByNum()">Rechercher</span>' . "\n";
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
                $html .= ', \'' . (isset($part['exchangePrice']) && $this->userExchangePrice ? addslashes($part['exchangePrice']) : (isset($part['stockPrice']) ? addslashes($part['stockPrice']) : '')) . '\'';
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

        $valDef = array();
        $valDef['serialNumber'] = $this->serial;

        switch ($requestType) {
            case 'CreateCarryInRepair':
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
//
////        echo "<pre>"; print_r($chrono->contact);
//        print_r($chrono->extraValue);

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
                break;
        }

//            $valDef['repairConfirmationNumbers'] = $this->confirmNumbers['repair'];
        return $gsxRequest->generateRequestFormHtml($valDef, $prodId, $this->serial);
    }

    public function processRequestForm($prodId, $requestType) {
        if (!$this->connect)
            return $this->getGSXErrorsHtml();

        global $db;
        $GSXRequest = new GSX_Request($this, $requestType);
        $result = $GSXRequest->processRequestForm($prodId, $this->serial);
        $html = '';
        if ($GSXRequest->isLastRequestOk()) {
            if (isset($_POST['includeFiles'])) {
                if (($_POST['includeFiles'] == 'Y') && isset($_REQUEST['chronoId'])) {
                    $dir = DOL_DATA_ROOT . '/synopsischrono/' . $_REQUEST['chronoId'] . '/';
                    $files = scandir($dir);
                    if (count($files)) {
                        $result['fileName'] = $files[0];
                        $result['fileData'] = file_get_contents($dir . $files[0]);
                    }
                }
            } else if (isset($_FILES['fileName'])) {
                if (isset($_FILES['fileName']['name']) && isset($_FILES['fileName']['tmp_name'])) {
                    $result['fileName'] = $_FILES['fileName']['name'];
                    $result['fileData'] = file_get_contents($_FILES['fileName']['tmp_name']);
                }
            }
            $client = $GSXRequest->requestName;
            $request = $GSXRequest->request;
            $wrapper = $GSXRequest->wrapper;

            $requestData = $this->gsx->_requestBuilder($request, $wrapper, $result);
            $response = $this->gsx->request($requestData, $client);
            if (count($this->gsx->errors['soap'])) {
                $html .= '<p class="error">Echec de l\'envoi de la requête</p>' . "\n";
                $html .= $this->getGSXErrorsHtml();
            } else {
                $ok = false;
                $repair = new Repair($db, $this->gsx);
                $confirmNumber = null;
                switch ($requestType) {
                    case 'CreateCarryInRepair':
                        if (isset($response['CreateCarryInResponse']['repairConfirmation']['confirmationNumber'])) {
                            $confirmNumber = $response['CreateCarryInResponse']['repairConfirmation']['confirmationNumber'];
                            if (isset($_REQUEST['chronoId'])) {
                                if ($repair->create($_REQUEST['chronoId'], $confirmNumber)) {
                                    $ok = true;
                                }
                            } else {
                                $html .= '<p class="error">Une erreur est survenue (chronoId manquant).</p>';
                            }
                        }
                        break;

                    case 'UpdateSerialNumber':
                        if (isset($response['UpdateSerialNumberResponse']['repairConfirmation']['confirmationNumber'])) {
                            $confirmNumber = $response['UpdateSerialNumberResponse']['repairConfirmation']['confirmationNumber'];
                            if (isset($_GET['repairRowId'])) {
                                $repair->rowId = $_GET['repairRowId'];
                                if ($repair->load()) {
                                    $repair->confirmNumbers['serialUpdate'] = $confirmNumber;
                                    if ($repair->update()) {
                                        $ok = true;
                                    }
                                } else {
                                    $html .= '<p class="error">Erreur: échec du chargement des données de la réparation.</p>';
                                }
                            } else {
                                $html .= '<p class="error">Une erreur est survenue (ID réparation manquant).</p>';
                            }
                        }
                        break;
                }
                if (!$ok) {
                    $html .= $repair->displayErrors();
                    if (count($this->gsx->errors['soap']))
                        $html .= $this->getGSXErrorsHtml();
                    $html .= '<p class="error">La requête a été correctement transmise mais les données de retour n\'ont pas pu être enregistrées correctement en base de données.<br/>';
                    $html .= 'Veuillez noter le numéro suivant (repair confirmation number) et le transmettre  à l\'équipe technique: ';
                    $html .= '<strong style="color: #3C3C3C">' . $confirmNumber . '</strong></p>';
                } else {
                    $html .= '<p class="confirmation">Requête envoyé avec succès.</p>';
                    if ($requestType == 'UpdateSerialNumber') {
                        if (isset($_POST['closeRepair'])) {
                            if ($_POST['closeRepair'] == 'Y') {
                                $ok = false;
                                $this->gsx->resetSoapErrors();
                                if ($repair->close()) {
                                    $html .= '<p class="confirmation">Réparation fermée avec succès</p><ok>Reload</ok>';
                                } else {
                                    $html .= '<p class="error">La réparation n\'a pas pu être fermée.</p>';
                                    $html .= $repair->displayErrors();
                                    if (count($this->gsx->errors['soap']))
                                        $html .= $this->getGSXErrorsHtml();
                                }
                            }
                        } else {
                            $html .= '<ok>Reload</ok>';
                        }
                    }
                }
//                $html .= '<pre>';
//                $html .= print_r($this->gsx->outputFormat($response), true);
//                $html .= '</pre>';
            }
        } else {
            $html = $result;
        }
        return $html;
    }

    public function getGSXErrorsHtml() {
        return $this->gsx->getGSXErrorsHtml();
    }

}

?>
