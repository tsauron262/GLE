<?php

require_once DOL_DOCUMENT_ROOT . '/synopsisapple/repair.class.php';

class gsxDatas
{

    private $tabReqForceApple = array("CreateIPhoneRepairOrReplace");
    private $userExchangePrice = true;
    public $gsx = null;
    public $connect = false;
    protected $serial = null;
    protected $errors = array();
    protected $repairs = array();
    public $partsPending = null;
    public static $apiMode = 'ut';
//    public static $apiMode = 'production';
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
        'F' => 'iPad',
        'W'=> 'Watch'
    );
    protected $isIphone = false;

    public function __construct($serial, $userId = null, $password = null, $serviceAccountNo = null, $requestType = false)
    {
        global $user;

        if (in_array($requestType, $this->tabReqForceApple)) {
            $this->isIphone = true;
        }

        if (defined('PRODUCTION_APPLE') && PRODUCTION_APPLE)
            self::$apiMode = 'production';
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
                'apiMode' => self::$apiMode,
                'regionCode' => 'emea',
                'userId' => $userId,
//                'password' => $password,
                'serviceAccountNo' => $serviceAccountNo,
                'serviceAccountNoShipTo' => $serviceAccountNoShipTo,
                'languageCode' => 'fr',
                'userTimeZone' => 'CEST',
                'returnFormat' => 'php',
            );
            $this->shipTo = $serviceAccountNoShipTo;
        } else {
            echo '<p class="error">Pas d\'identifiant apple.<a href="' . DOL_URL_ROOT . '/user/card.php?id=' . $user->id . '"> Corriger</a></p>' . "\n";
            return 0;
        }

        $this->setSerial($serial);

        $this->gsx = new GSX($details, $this->isIphone, self::$apiMode);
        if (count($this->gsx->errors['init']) || count($this->gsx->errors['soap'])) {
            $this->errors[] = 'GSX_init_error';
        } else {
            $this->connect = true;
        }
    }

    public function setSerial($serial)
    {
        if (preg_match('/^[0-9]{15,16}$/', $serial)) {
            $this->isIphone = true;
        } /* else
          $this->isIphone = false; */
        $this->serial = $serial;
    }

    public function loadRepairs($chronoId)
    {
        if (!isset($chronoId))
            return;

        global $db;
        $sql = 'SELECT * FROM `' . MAIN_DB_PREFIX . 'synopsis_apple_repair` WHERE `chronoId` = "' . $chronoId . '"';
        $rows = $db->query($sql);
        if ($db->num_rows($rows) > 0) {
            while ($row = $db->fetch_object($rows)) {
                $repair = new Repair($db, $this->gsx, $this->isIphone);
                $repair->setSerial($this->serial);
                $repair->setDatas($row->repairNumber, $row->repairConfirmNumber, $row->serialUpdateConfirmNumber, $row->closed, $row->rowid);
                $repair->isReimbursed = $row->is_reimbursed;
                $repair->readyForPickUp = $row->ready_for_pick_up;
                $repair->totalFromOrder = $row->totalFromOrder;
                $this->repairs[] = $repair;
            }
        }
    }

    public function importRepair($chronoId)
    {
        if (!$this->connect)
            return $this->getGSXErrorsHtml();
        global $db;
        if (!isset($_GET['importNumber']) || !isset($_GET['importNumberType']))
            return '<p class="error">Erreur: informations manquantes.</p>';
        $repair = new Repair($db, $this->gsx, $this->isIphone);
        if ($repair->import($chronoId, $_GET['importNumber'], $_GET['importNumberType'])) {
            return '<p class="confirmation">Les données de la réparation ont été importées avec succès</p><ok>Reload</ok>';
        }
        $html = '<p class="error">Echec de l\'importation.</p>';
        $html .= $repair->displayErrors();
        if (count($this->gsx->errors['soap']))
            $html .= $this->getGSXErrorsHtml();
        return $html;
    }

    public function endRepair($repairRowId)
    {
        if (!$this->connect)
            return $this->getGSXErrorsHtml();
        global $db;

        $repair = new Repair($db, $this->gsx, $this->isIphone);
        $repair->rowId = $repairRowId;
        if ($repair->load()) {
            if ($repair->updateStatus())
                return 'ok';
        }
        $html = '<p class="error">Echec de la fermeture de la réparation</p>';
        $html .= $repair->displayErrors();
        if (count($this->gsx->errors['soap']))
            $html .= $this->getGSXErrorsHtml();
        return $html;
    }

    public function closeRepair($repairRowId)
    {
        if (!$this->connect)
            return $this->getGSXErrorsHtml();
        global $db;

        $repair = new Repair($db, $this->gsx, $this->isIphone);
        $repair->rowId = $repairRowId;
        if ($repair->load()) {
            if ($repair->close(true, (isset($_GET['checkRepair']) && ($_GET['checkRepair'] != '')) ? $_GET['checkRepair'] : false))
                return 'ok';
        }
        $html = '<p class="error">Echec de la fermeture de la réparation</p>';
        $html .= $repair->displayErrors();
        if (count($this->gsx->errors['soap']))
            $html .= $this->getGSXErrorsHtml();
        return $html;
    }

    public function markRepairAsReimbursed($repairRowId)
    {
        global $db;

        $repair = new Repair($db, $this->gsx, $this->isIphone);
        $repair->rowId = $repairRowId;
        if ($repair->load()) {
            $repair->isReimbursed = 1;
            if ($repair->update())
                return 'ok';
        }
        $html = '<p class="error">Echec de la mise à jour de la réparation</p>';
        $html .= $repair->displayErrors();
        return $html;
    }

    public function getLookupHtml($prodId)
    {
        if (count($this->errors) || !$this->connect) {
            return $this->getGSXErrorsHtml();
        }
        $response = $this->gsx->lookup($this->serial, 'warranty');
        $check = false;
        $html = '';
        if (isset($response) && count($response)) {
            if (isset($response['ResponseArray']) && count($response['ResponseArray'])) {
                if (isset($response['ResponseArray']['responseData']) && count($response['ResponseArray']['responseData'])) {
                    $datas = $response['ResponseArray']['responseData'];
                    $urgentMsg = $response['ResponseArray']['urgentMessage'];
//                    echo '<pre>';
//                    print_r($datas);
//                    echo '</pre>';
                    $check = true;

                    $html .= '<div class="container productContainer">' . "\n";
                    $html .= '<div class="captionContainer productCaption" onclick="onCaptionClick($(this))">' . "\n";
                    $html .= '<span class="captionTitle productTile">' . $datas['productDescription'] . '</span>' . "\n";
                    $html .= '<span class="arrow upArrow"></span>';
                    $html .= '</div>' . "\n";
                    $html .= '<div class="blocContent productContent">' . "\n";

                    if (isset($urgentMsg) && ($urgentMsg != '')) {
                        $html .= '<p class="alert">Message urgent du service Apple GSX: <br/>';
                        $html .= '"' . $urgentMsg . '"';
                        $html .= '</p>';
                    }
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

//                    $html .= '<tr>' . "\n";
//                    $src = $datas['imageURL'];
//                    if (isset($src) && $src) {
//                    $html .= '<td class="productImgContainer">' . "\n";
//                    $html .= '<img class="productImg" src="' . $src . '"/>' . "\n";
//                    $html .= '</td>' . "\n";
//                    }
//                    $html .= '<td>' . "\n";
//                    $html .= '<table><thead></thead><tbody>' . "\n";
//echo "<pre>"; print_r($datas);die;
                    $this->serial2 = $datas['serialNumber'];

                    if (isset($datas['serialNumber']) && $datas['serialNumber'] !== '')
                        $html .= '<tr class="oddRow"><td class="rowTitle">Numéro de série</td><td>' . $datas['serialNumber'] . '</td></tr>' . "\n";

                    if (isset($datas['imeiNumber']) && $datas['imeiNumber'] !== '')
                        $html .= '<tr class="oddRow"><td class="rowTitle">Numéro IMEI</td><td>' . $datas['imeiNumber'] . '</td></tr>' . "\n";

                    if (isset($datas['configDescription']) && $datas['configDescription'] !== '')
                        $html .= '<tr><td class="rowTitle">Configuration</td><td>' . $datas['configDescription'] . '</td></tr>' . "\n";

                    if (isset($datas['warrantyReferenceNo']) && $datas['warrantyReferenceNo'] !== '')
                        $html .= '<tr class="oddRow"><td class="rowTitle">Numéro de garantie</td><td>' . $datas['warrantyReferenceNo'] . '</td></tr>' . "\n";

                    if (isset($datas['warrantyStatus']) && $datas['warrantyStatus'] !== '')
                        $html .= '<tr><td class="rowTitle">Garantie</td><td>' . $datas['warrantyStatus'] . '</td></tr>' . "\n";

                    if (isset($datas['onsiteStartDate']) && $datas['onsiteStartDate'] !== '')
                        $html .= '<tr class="oddRow"><td class="rowTitle">Date d\'entrée</td><td>' . $datas['onsiteStartDate'] . '</td></tr>' . "\n";

                    if (isset($datas['onsiteEndDate']) && $datas['onsiteEndDate'] !== '')
                        $html .= '<tr><td class="rowTitle">Date de sortie</td><td>' . $datas['onsiteEndDate'] . '</td></tr>' . "\n";

                    if (isset($datas['estimatedPurchaseDate']) && $datas['estimatedPurchaseDate'] !== '')
                        $html .= '<tr class="oddRow"><td class="rowTitle">Date d\'achat estimé</td><td>' . $datas['estimatedPurchaseDate'] . '</td></tr>' . "\n";

                    if (isset($datas['coverageStartDate']) && $datas['coverageStartDate'] !== '')
                        $html .= '<tr><td class="rowTitle">Début de la garantie</td><td>' . $datas['coverageStartDate'] . '</td></tr>' . "\n";

                    if (isset($datas['coverageEndDate']) && $datas['coverageEndDate'] !== '')
                        $html .= '<tr class="oddRow"><td class="rowTitle">Fin de la garantie</td><td>' . $datas['coverageEndDate'] . '</td></tr>' . "\n";

                    if (isset($datas['daysRemaining']) && $datas['daysRemaining'] !== '')
                        $html .= '<tr><td class="rowTitle">Jours restants</td><td>' . $datas['daysRemaining'] . '</td></tr>' . "\n";

                    if (isset($datas['notes']) && $datas['notes'] !== '')
                        $html .= '<tr class="oddRow"><td class="rowTitle">Note</td><td>' . $datas['notes'] . '</td></tr>' . "\n";

                    if (isset($datas['activationLockStatus']) && $datas['activationLockStatus'] !== '')
                        $html .= '<tr class="oddRow" style="color:red;"><td class="rowTitle">Localisé</td><td>' . $datas['activationLockStatus'] . '</td></tr>' . "\n";

                    if (isset($datas['manualURL']) && $datas['manualURL'] !== '')
                        $html .= '<tr style="height: 30px;"><td colspan="2"><a class="productPdfLink" href="' . $datas['manualURL'] . '">Manuel</a></td></tr>' . "\n";

//                    $html .= '</tbody></table>' . "\n";
//                    $html .= '</td>' . "\n";
//                    $html .= '</tr>' . "\n";
                    $html .= '</tbody></table>' . "\n";
                    $html .= '</div></div>' . "\n";

                    $html .= $this->getRepairsHtml($prodId);
                    $html .= $this->getCartHtml($prodId);
                    global $db;
                    $cart = new partsCart($db, $this->serial, isset($_GET['chronoId']) ? $_GET['chronoId'] : null);
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

    public function getRepairsHtml($prodId)
    {
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
            $html .= '<option value="' . $value . '"';
            if ((($this->isIphone) && ($value == 'imeiNumber')) ||
                    ((!$this->isIphone) && ($value == 'serialNumber')))
                $html .= ' selected';
            $html .= '>' . $text . '</option>';
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
//        $html .= '<div style="display: none">'."\n";
        $html .= '<p>Sélectionnez le type d\'opération que vous souhaitez effectuer: <br/></p>';
        $html .= '<select class="repairTypeSelect">' . "\n";
        $requests = GSX_Request::getRequestsByType('repair');
        foreach ($requests as $name => $label) {
//            if (!$this->isIphone ||
//                    ($this->isIphone && ($name == 'CreateCarryInRepair')))
            $html .= '<option value="' . $name . '">' . $label . '</option>';
        }
        $html .= '</select>';
//        $html .= '</div>'."\n";

        $symptomesCodes = $this->getSymptomesCodesArray($this->serial2);
        $inputName = "symptomesCodes";

        $html .= '<select id="' . $inputName . '" name="' . $inputName . '">' . "\n";
//                            $html .= '<option value="0">Symtomes</option>' . "\n";

        foreach ($symptomesCodes['sym'] as $mod => $desc) {
            $html .= '<option value="' . $mod . '"';
            if (isset($values[$valuesName])) {
                if ($values[$valuesName] == $mod)
                    $html.= ' selected';
            }
            $html .= '>' . $mod . ' - ' . $desc . '</option>';
        }
        $html .= '</select>' . "\n";


        $html .= '<p style="text-align: right">' . "\n";
        $html .= '<span class="button loadRepairForm greenHover" onclick="GSX.loadRepairForm($(this))">Valider</span>' . "\n";
        $html .= '</p>' . "\n";
        $html .= '<div class="repairFormContainer"></div>' . "\n";
        $html .= '<div class="repairFormResults"></div>' . "\n";
        $html .= '</div>' . "\n";
        return $html;
    }

    public function getCartHtml($prodId)
    {
        $html = '<div class="cartContainer container">' . "\n";
        $html .= '<div class="captionContainer" onclick="onCaptionClick($(this))">' . "\n";
        $html .= '<span class="cartTitle captionTitle">Panier de composants&nbsp;&nbsp;&nbsp;</span>';
        $html .= '<span class=subtitle><span class="nbrCartProducts">0</span> produit(s)</span>' . "\n";
        $html .= '<span class="arrow downArrow"></span>';
        $html .= '</div>' . "\n";

        $html .= '<div class="cartContent blocContent">' . "\n";
        $html .= '<div class="toolBar">' . "\n";
        $html .= '<span class="button addToPropal" onclick="GSX.products[' . $prodId . '].cart.addToPropal($(this))">Ajouter à la propal</span>' . "\n";
        $html .= '<span class="button cartSave greenHover deactivated" onclick="GSX.products[' . $prodId . '].cart.save()">Sauvegarder</span>' . "\n";
        $html .= '<span class="button cartLoad blueHover" onclick="GSX.products[' . $prodId . '].cart.load()">Charger</span>' . "\n";
        $html .= '<span class="button createNewRepair greenHover" onclick="displayCreateRepairPopUp($(this))">Nouvelle réparation</span>' . "\n";
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
        $html .= '<div class="cartRequestResults2"></div>' . "\n";
        $html .= '</div></div>' . "\n";
        return $html;
    }

    public function getPartsListArray($partNumberAsKey = false)
    {
        $params = array();
        if ($this->isIphone) {
            $params['imeiNumber'] = $this->serial;
        } else {
            $params['serialNumber'] = $this->serial;
        }
        $parts = $this->gsx->part($params);
//        echo '<pre>';
//        print_r($parts);
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

    public function getPartsListHtml($prodId)
    {
        $parts = $this->getPartsListArray();
        $check = false;
        $html = '';
        if (isset($parts) && is_array($parts) && count($parts)) {
            $check = true;
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
            $types = array('name' => 'Nom', 'eeeCode' => 'eeeCode', 'num' => 'Référence', 'type' => 'Type', 'price' => 'Prix');
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
//            print_r($parts);
            foreach ($parts as $part) {
                $partNewNumber = '';
                if (isset($part['originalPartNumber']) && $part['originalPartNumber'] != '') {
                    $partNewNumber = $part['partNumber'];
                    $part['partNumber'] = $part['originalPartNumber'];
                }
                $html .= 'GSX.addPart(' . $prodId . ', ';
                $html .= '\'' . (isset($part['componentCode']) ? addslashes($part['componentCode']) : '') . '\'';
                $html .= ', \'' . (isset($part['partDescription']) ? addslashes(str_replace(" ", "", $part['partDescription'])) : '') . '\'';
                $html .= ', \'' . (isset($part['partNumber']) ? addslashes($part['partNumber']) : '') . '\'';
                $html .= ', \'' . (isset($part['partType']) ? addslashes($part['partType']) : '') . '\'';
                $html .= ', \'' . (isset($part['exchangePrice']) && $this->userExchangePrice && $part['exchangePrice'] > 0 ? addslashes($part['exchangePrice']) : (isset($part['stockPrice']) ? addslashes($part['stockPrice']) : '')) . '\'';
                $html .= ', \'' . (isset($part['eeeCode']) ? addslashes($part['eeeCode']) : '') . '\'';
                $html .= ', \'' . addslashes($partNewNumber) . '\'';
                $html .= ');' . "\n";
            }
            $html .= '</script>' . "\n";
        }
        if (!$check) {
            $html .= '<p class="error">Echec de la récupération de la liste des composants compatibles depuis la plateforme GSX</p>';
            $html .= $this->gsx->getGSXErrorsHtml();
        }
        return $html;
//        echo '<pre>';
//        print_r($this->gsx->obtainCompTIA());
//        echo '</pre>';
//        return '';
    }

    public function getSymptomesCodesArray($serial, $symCode = null)
    {
        $this->setSerial($serial);
        $datas = $this->gsx->obtainSymtomes($serial, $symCode);


        $newArray = array('sym' => array(), 'issue' => array());

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

        return $newArray;
    }

    public function getCompTIACodesArray()
    {
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

    public function getRequestFormHtml($requestType, $prodId)
    {
        global $db, $user;
        $comptiaCodes = $this->getCompTIACodesArray();
        $symptomesCodes = $this->getSymptomesCodesArray($this->serial, (isset($_REQUEST['symCode']) ? $_REQUEST['symCode'] : null));
        $gsxRequest = new GSX_Request($this, $requestType, ($comptiaCodes !== 'fail') ? $comptiaCodes : null, $symptomesCodes);
        $chronoId = null;
        if (isset($_REQUEST['chronoId'])) {
            $chronoId = $_REQUEST['chronoId'];
        }

        $valDef = array();
        $valDef['serialNumber'] = $this->serial;

//        switch ($requestType) {
//            case 'CreateCarryInRepair':
//            case 'CreateMailInRepair':
//            case 'CreateIndirectOnsiteRepair':
//            case 'CreateIPhoneRepairOrReplace':
//            case 'CreateWholeUnitExchange':
                if (isset($chronoId)) {
                    require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");
                    $chrono = new Chrono($db);
                    $chrono->fetch($chronoId);
                    $chrono->getValues($chronoId);

                    $idUser = ($chrono->extraValue[$chronoId]['Technicien']['value'] > 0) ? $chrono->extraValue[$chronoId]['Technicien']['value'] : $user->id;

                    $tech = new User($db);
                    $tech->fetch($idUser);

                    $valDef['diagnosis'] = $chrono->extraValue[$chronoId]['Diagnostic']['value'];
                    $valDef['symptom'] = $chrono->extraValue[$chronoId]['Symptomes']['value'];
//                    $dateH = explode(" ", $chrono->extraValue[$chronoId]['Date / Heure']['value']);
                    $valDef['unitReceivedDate'] = date("d/m/Y");
                    $valDef['unitReceivedTime'] = "08:00";

                    $valDef['diagnosedByTechId'] = $tech->array_options['options_apple_techid'];
                    $valDef['shipTo'] = $tech->array_options['options_apple_shipto'];
                    $valDef['shippingLocation'] = $tech->array_options['options_apple_shipto'];
                    $valDef['billTo'] = $tech->array_options['options_apple_service'];
                    $valDef['soldToContact'] = $tech->getFullName($langs);
                    $valDef['technicianName'] = $tech->lastname;
                    $valDef['soldToContactPhone'] = $tech->office_phone;
                    $valDef['poNumber'] = $chrono->ref;
                    $valDef['purchaseOrderNumber'] = $chrono->ref;
                    
                    
                    
                    //pour les retour
                    $valDef['shipToCode'] = $this->shipTo;
                    $valDef['length'] = "4";
                    $valDef['width'] = "2";
                    $valDef['height'] = "1";
                    $valDef['estimatedTotalWeight'] = "1";
                    
                    if(count($this->repairs) < 1)
                        $this->loadRepairs ($chronoId);
                    foreach($this->repairs as $repair){
                        $tabT = array();
                        $tabT['dispatchId'] = $repair->confirmNumbers['repair'];
                        
                        $cart = new partsCart($db, null, $chronoId);
                        $cart->loadCart();
                        foreach($cart->partsCart as $part){
                            $tabT['partNumber'] = $part['partNumber'];
                        $valDef['WHUBulkReturnOrder'][] = $tabT;
                        }
                    }
                    
                    
                    
//
////        echo "<pre>"; print_r($chrono->contact);
//        print_r($chrono->extraValue);
//                    if ($this->isIphone) {
//                        if($valDef['componentCheckDetails']['conponent'] == "IMEI")
//                        $valDef['componentCheckDetails']['componentSerialNumber'] = $this->serial;
//                    }
                    $valDef['customerAddress']['companyName'] = $chrono->societe->name;




                    $valDef['customerAddress']['city'] = $chrono->societe->town;
                    $valDef['customerAddress']['primaryPhone'] = $chrono->societe->phone;
                    $valDef['customerAddress']['secondaryPhone'] = $chrono->societe->phone;
                    $valDef['customerAddress']['zipCode'] = $chrono->societe->zip;
                    $valDef['customerAddress']['state'] = substr($chrono->societe->zip, 0, 2);
                    $valDef['customerAddress']['emailAddress'] = $chrono->societe->email;
                    $valDef['customerAddress']['street'] = $chrono->societe->address;
                    $valDef['customerAddress']['addressLine1'] = $chrono->societe->address;
                    $valDef['customerAddress']['country'] = "FRANCE";

                    $tabName = explode(" ", $chrono->societe->name);
                    $valDef['customerAddress']['firstName'] = $tabName[0];
                    $valDef['customerAddress']['lastName'] = (isset($tabName[1]) ? $tabName[1] : $tabName[0]);



                    if (isset($chrono->contact->id)) {
                        if ($chrono->contact->address != "")
                            $valDef['customerAddress']['street'] = $chrono->contact->address;
                        if ($chrono->contact->address != "")
                            $valDef['customerAddress']['addressLine1'] = $chrono->contact->address;
//            $valDef['addressLine2'] = $chrono->contact->;
//            $valDef['addressLine3'] = $chrono->contact->;
//            $valDef['addressLine4'] = $chrono->contact->;
                        if ($chrono->contact->town != "")
                            $valDef['customerAddress']['city'] = $chrono->contact->town;
                        $valDef['customerAddress']['firstName'] = $chrono->contact->firstname;
                        $valDef['customerAddress']['lastName'] = $chrono->contact->lastname;
                        if ($chrono->contact->phone_pro != "")
                            $valDef['customerAddress']['primaryPhone'] = $chrono->contact->phone_pro;
                        if ($chrono->contact->phone_mobile != "")
                            $valDef['customerAddress']['secondaryPhone'] = $chrono->contact->phone_mobile;
                        if ($chrono->contact->zip != "")
                            $valDef['customerAddress']['zipCode'] = $chrono->contact->zip;
                        if ($chrono->contact->zip != "")
                            $valDef['customerAddress']['state'] = substr($chrono->contact->zip, 0, 2);
                        if ($chrono->contact->email != "")
                            $valDef['customerAddress']['emailAddress'] = $chrono->contact->email;
                    }
                }
//                break;
//        }

//            $valDef['repairConfirmationNumbers'] = $this->confirmNumbers['repair'];
        return $gsxRequest->generateRequestFormHtml($valDef, $prodId, $this->serial);
    }

    public function processRequestForm($prodId, $requestType)
    {
        if (!$this->connect)
            return $this->getGSXErrorsHtml();

        global $db;
        $GSXRequest = new GSX_Request($this, $requestType);
        $result = $GSXRequest->processRequestForm($prodId, $this->serial);
        $html = '';
        if ($GSXRequest->isLastRequestOk()) {
            $filesError = false;
            if (isset($_POST['includeFiles']) && ($_POST['includeFiles'] == 'Y') && isset($_REQUEST['chronoId'])) {
                $dir = DOL_DATA_ROOT . '/synopsischrono/' . $_REQUEST['chronoId'] . '/';
                $files = scandir($dir);
                if (count($files)) {
                    $result['fileName'] = $files[0];
                    $result['fileData'] = file_get_contents($dir . $files[0]);
                } else {
                    $html .= '<p class="error">Aucun fichier-joint n\'a été trouvé</p>';
                    $filesError = true;
                }
            } else if (isset($_FILES['fileName']) &&
                    isset($_FILES['fileName']['name']) &&
                    isset($_FILES['fileName']['tmp_name']) &&
                    $_FILES['fileName']['name'] != '') {
                $result['fileName'] = $_FILES['fileName']['name'];
                $result['fileData'] = false;
                if (file_exists($_FILES['fileName']['tmp_name']))
                    $result['fileData'] = file_get_contents($_FILES['fileName']['tmp_name']);

                if ($result['fileData'] === false) {
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
                if (isset($result['serialNumber']) && strlen($result['serialNumber']) > 13) {//Si num imei echange des champ
                    $result['alternateDeviceId'] = $result['serialNumber'];
                    $result['serialNumber'] = '';
                } else {
                    $result['alternateDeviceId'] = "";
                }

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
                        $responseName = 'IPhoneUpdateSerialNumberResponse';
                        $client = 'IPhoneUpdateSerialNumber';
                        $request = 'IPhoneUpdateSerialNumberRequest';
                        break;

                    case 'KGBSerialNumberUpdate':
//                        $responseNames = array(
//                            'UpdateKGBSerialNumberResponse',
//                            'KGBSerialNumberUpdateResponse'
//                        );
                        $responseNames = array(
                            'UpdateIPhoneKGBSerialNumberResponse',
                            'IPhoneUpdateKGBSerialNumberResponse',
                            'IPhoneKGBSerialNumberUpdateResponse',
                            'UpdateIPhoneKGBSerialNumberRequestResponse'
                        );
                        $client = 'IPhoneKGBSerialNumberUpdate';
                        $request = 'UpdateIPhoneKGBSerialNumberRequest';
                        
                        $result['imeiNumber'] = $result['alternateDeviceId'];
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

            $requestData = $this->gsx->_requestBuilder($request, $wrapper, $result);
            $response = $this->gsx->request($requestData, $client);
            
            
            if (isset($response[$responseName]['repairConfirmation']['messages'])){
                $message = $response[$responseName]['repairConfirmation']['messages'];
                if(!is_array($message))
                    $message = array($message);
                foreach($message as $mess)
                    $html .= "<div class='alertJs'>".$mess ."</div>";
            }

            if (count($this->gsx->errors['soap'])) {
                $html .= '<p class="error">Echec de l\'envoi de la requête</p>' . "\n";
                $html .= $this->getGSXErrorsHtml();
                foreach ($requestData as $nomReq => $tabT) {
                    if (isset($tabT['repairData']) && isset($tabT['repairData']['fileData']))
                        $requestData[$nomReq]['repairData']['fileData'] = "Fichier joint exclue du log";
                }
                if (count($this->gsx->errors['log']['soap']))
                    dol_syslog("erreur GSX : " . $this->getGSXErrorsHtml() . "Requete :" . print_r($requestData, true) . " Reponsse : " . print_r($response, true), 4, 0, "_apple");
            } else {//pas d'erreur, on analyse le résultat
                if ($requestType == "CreateMailInRepair" || $requestType == "KGBSerialNumberUpdate")
//                    dol_syslog("iciici" . "Requete :" . print_r($requestData, true) . " Reponsse : " . print_r($response, true), 4, 0, "_apple");
                    $ok = false;
                $repair = new Repair($db, $this->gsx, $this->isIphone);
                $confirmNumber = null;
                $responseName = $requestType . "Response";
                if (isset($responseNames) && is_array($responseNames)) {
                    foreach ($responseNames as $respName) {
                        if (isset($response[$respName])) {
                            // Message à loguer -> 'Le responseName pour la requete' . $client . ' est: ' . $respName
                            $responseName = $respName;
                            break;
                        }
                    }
                }
                
                
                switch ($requestType) {
                    default:
                        if (isset($response[$responseName]['repairConfirmation']['confirmationNumber'])) {
                            $tabConfirm = $response[$responseName]['repairConfirmation'];
                            $confirmNumber = $tabConfirm['confirmationNumber'];
                            $prixTot = str_replace("EUR", "", $tabConfirm['totalFromOrder']);
                            $prixTot = str_replace("  ", "", $prixTot);
                            $prixTot = (float) str_replace(",", ".", $prixTot);
                            $repair->totalFromOrder = $prixTot;
                            if (isset($_REQUEST['requestReviewByApple']) && $_REQUEST['requestReviewByApple'] == "Y")
                                $prixTot = 0;
                            $html .= "<prix>" . $prixTot . "</prix>";
                            if (isset($_REQUEST['chronoId'])) {
                                if ($repair->create($_REQUEST['chronoId'], $confirmNumber)) {
                                    $ok = true;
                                    $html .= "<ok>Reload commande ok</ok>";
                                }
                            } else {
                                $html .= '<p class="error">Une erreur est survenue (chronoId manquant).</p>';
                            }
                        } else {
                            $msg = '<p class="error">Une Erreur est survenue: aucun numéro de confirmation retourné par Apple. Requete : ' . $client . '</p>';
                            $html .= $msg;
                            dol_syslog($msg . " | " . print_r($response, 1), LOG_ERR, 0, "_apple");
                        }
                        break;

                    case 'RegisterPartsForWHUBulkReturn':
                        if (isset($responseName) && isset($response[$responseName]['WHUBulkPartsRegistrationData'])) {
                            $datas = $response[$responseName]['WHUBulkPartsRegistrationData'];
                            $direName = '/synopsischrono/' . $_REQUEST['chronoId'] . '';
                            $fileNamePure = str_replace("/", "_", $datas['packingListFileName']);
                            if (!is_dir(DOL_DATA_ROOT . $direName))
                                mkdir(DOL_DATA_ROOT . $direName);
                            $fileName = $direName . "/" . $fileNamePure;
    //                        die(DOL_DATA_ROOT . $fileName);
                            if (!file_exists(DOL_DATA_ROOT . $fileName)) {
                                if (file_put_contents(DOL_DATA_ROOT . $fileName, $datas['packingList']) === false)
                                    $fileName = null;
                                $ok = true;
                            }
                        } else {
                            $html .= '<p class="error">Une Erreur est survenue: aucun documents retourné par Apple</p>';
                        }
                        break;

                    case 'KGBSerialNumberUpdate':
//                        echo '<pre>';
//                        print_r($response);
//                        echo '</pre>';
                        if (isset($responseName) && isset($response[$responseName]['repairConfirmationNumber'])) {
                            if ($response[$responseName]['updateStatus'] == "Y") {
                                $confirmNumber = $response[$responseName]['repairConfirmationNumber'];
                                if (isset($_GET['repairRowId'])) {
                                    $repair->rowId = $_GET['repairRowId'];
                                    if ($repair->load()) {
                                        $repair->confirmNumbers['serialUpdate'] = $confirmNumber;
                                        if ($repair->update()) {
                                            $ok = true;
                                        } else {
                                            $html .= '<p class="error">Update Fail</p>';
                                        }
                                    } else {
                                        $html .= '<p class="error">Erreur: échec du chargement des données de la réparation.</p>';
                                    }
                                } else {
                                    $html .= '<p class="error">Une erreur est survenue (ID réparation manquant).</p>';
                                }
                            } else {
                                $html .= '<p class="error">Une Erreur est survenue: echec de la maj</p>';
                            }
                        } else {
                            $html .= '<p class="error">Une Erreur est survenue: aucun numéro de confirmation retourné par Apple</p>';
                        }
                        break;
                    case 'UpdateSerialNumber':
//                        echo '<pre>';
//                        print_r($response);
//                        echo '</pre>';
                        if (isset($responseName) && isset($response[$responseName]['repairConfirmation']['repairConfirmationNumber'])) {
                            $confirmNumber = $response[$responseName]['repairConfirmation']['repairConfirmationNumber'];
                            if (isset($_GET['repairRowId'])) {
                                $repair->rowId = $_GET['repairRowId'];
                                if ($repair->load()) {
                                    $repair->confirmNumbers['serialUpdate'] = $confirmNumber;
                                    if ($repair->update()) {
                                        $ok = true;
                                    } else {
                                        $html .= '<p class="error">Update Fail</p>';
                                    }
                                } else {
                                    $html .= '<p class="error">Erreur: échec du chargement des données de la réparation.</p>';
                                }
                            } else {
                                $html .= '<p class="error">Une erreur est survenue (ID réparation manquant).</p>';
                            }
                        } else {
                            $html .= '<p class="error">Une Erreur est survenue: aucun numéro de confirmation retourné par Apple</p>';
                        }
                        break;
                }
                if (!$ok) {
                    $html .= $repair->displayErrors();
                    if (count($this->gsx->errors['soap']))
                        $html .= $this->getGSXErrorsHtml();
                    if (isset($confirmNumber)) {
                        $html .= '<p class="error">La requête a été correctement transmise mais les données de retour n\'ont pas pu être enregistrées correctement en base de données.<br/>';
                        $html .= 'Veuillez noter le numéro suivant (repair confirmation number) et le transmettre  à l\'équipe technique: ';
                        $html .= '<strong style="color: #3C3C3C">' . $confirmNumber . '</strong></p>';
                        dol_syslog("Erreur GSX : " . $html . "  |   " . $this->getGSXErrorsHtml() . print_r($response, true), 4, 0, "_apple");
                    }
                } else {
                    $html .= '<p class="confirmation">Requête envoyé avec succès.</p>';

                    if ($requestType == "CreateIPhoneRepairOrReplace")
                        dol_syslog("requete GSX sans erreur : " . $html . "  |   " . $this->getGSXErrorsHtml() . print_r($response, true), 4, 0, "_apple2");

                    if (($requestType == 'UpdateSerialNumber') ||
                            ($requestType == 'KGBSerialNumberUpdate')) {
                        if (isset($_POST['closeRepair'])) {
                            if ($_POST['closeRepair'] == 'Y') {
                                $ok = false;
                                $this->gsx->resetSoapErrors();
                                if ($repair->updateStatus()) {
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
            $html .= $result;
        }
        return $html;
    }

    public function getGSXErrorsHtml($log = false)
    {
        if (is_object($this->gsx))
            return $this->gsx->getGSXErrorsHtml($log);
        else
            return '';
    }
}

?>
