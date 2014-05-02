<?php
require_once ('GSX_Request.class.php');

class gsxDatas {
    public $gsx = null;
    protected $serial = null;
    protected $partsCart = array();
    protected $errors = array();
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
        else
            $details = array(
                'apiMode' => self::$apiMode,
                'regionCode' => 'emea',
                'userId' => isset($userId) ? $userId : '',
                'password' => isset($password) ? $password : '',
                'serviceAccountNo' => isset($serviceAccountNo) ? $serviceAccountNo : '',
                'languageCode' => 'fr',
                'userTimeZone' => 'CEST',
                'returnFormat' => 'php',
            );
        $this->gsx = new GSX($details);
        $this->serial = $serial;
        if (count($this->gsx->errors['init']) || count($this->gsx->errors['soap'])) {
            $this->errors[] = 'GSX_init_error';
            echo 'error';
        }
    }

    public function getLookupHtml() {
        if (count($this->errors)) {
            return '<p class="error">Impossible d\'afficher les informations demandées</p>' . "\n";
        }
        $response = $this->gsx->lookup($this->serial, 'warranty');
        $check = false;
        if (isset($response) && count($response)) {
            if (isset($response['ResponseArray']) && count($response['ResponseArray'])) {
                if (isset($response['ResponseArray']['responseData']) && count($response['ResponseArray']['responseData'])) {
                    $datas = $response['ResponseArray']['responseData'];
//                    echo '<pre>';
//                    echo print_r($datas);
//                    echo '</pre>';
                    $check = true;

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
                    $html .= '<button class="loadParts" onclick="GSX.loadProductParts($(this))">Charger la liste des composants compatibles</button>' . "\n";
                    $html .= '<button class="createRepair" onclick="displayCreateRepairPopUp($(this))">Créer une réparation</button>'."\n";
                    $html .= '<div class="partsRequestResult"></div>' . "\n";

                    $html .= '<div class="repairPopUp">'."\n";
                    $html .= '<span class="hidePopUp" onclick="hideCreateRepairPopUp($(this))">Cacher</span>'."\n";
                    $html .= '<p>Sélectionnez le type de réparation que vous souhaitez créer: <br/></p>';
                    $html .= '<select class="repairTypeSelect">'."\n";
                    foreach (GSX_Request::$requests_definitions as $name => $requestDatas) {
                        $html .= '<option value="'.$name.'">'.$requestDatas['name'].'</option>';
                    }
                    $html .= '</select>';
                    $html .= '<p style="text-align: right">'."\n";
                    $html .= '<button class="loadRepairForm greenHover" onclick="GSX.loadRepairForm($(this))">Charger le formulaire</button>'."\n";
                    $html .= '</p>'."\n";
                    $html .= '<div class="repairFormContainer"/></div>';
                    $html .= '</div>'."\n";
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
        $html .= '<th class="comptiaCodeTitle">CompTIA Code</th>'."\n";
        $html .= '</thead>' . "\n";
        $html .= '<tbody></tbody>' . "\n";
        $html .= '</table>' . "\n";
        $html .= '<div class="cartSubmitContainer">' . "\n";
        $html .= '<button class="cartSave blueHover" onclick="GSX.products[' . $prodId . '].cart.save()">Sauvegarder le panier</button>' . "\n";
        $html .= '<button class="cartSubmit greenHover" onclick="GSX.products[' . $prodId . '].cart.submit()">Valider la commande</button>' . "\n";
        $html .= '</div>' . "\n";
        $html .= '<div class="cartRequestResults"></div>' . "\n";
        $html .= '</div>' . "\n";
        return $html;
    }

    public function getPartsListHtml($prodId, $displayCart = true) {
        $parts = $this->gsx->part(array('serialNumber' => $this->serial));
        $check = false;
        if (isset($parts) && count($parts)) {
            if (isset($parts['ResponseArray']) && count($parts['ResponseArray'])) {
                if (isset($parts['ResponseArray']['responseData']) && count($parts['ResponseArray']['responseData'])) {
                    $check = true;
                    $parts = $parts['ResponseArray']['responseData'];
                    $html = '';
                    if ($displayCart)
                        $html .= $this->getCartHtml($prodId);
                    $html .= '<div class="componentsListContainer">' . "\n";
                    $html .= '<div class="titre">Liste des composants compatibles</div>' . "\n";
                    $html .= '<div class="typeFilters searchBloc">' . "\n";
                    $html .= '<button class="filterTitle">Filtrer par catégorie de composant</button>';
                    $html .= '<div class="typeFiltersContent">'."\n";
                    $html .= '<div style="margin-bottom: 20px;">'."\n";
                    $html .= '<span class="filterCheckAll">Tout cocher</span>';
                    $html .= '<span class="filterHideAll">Tout décocher</span></div></div>';
                    $html .= '</div>' . "\n";
                    $html .= '<div class="searchBloc"' . "\n";
                    $html .= '<label for="keywordFilter">Filtrer par mots-clés: </label>' . "\n";
                    $html .= '<input type="text max="80" name="keywordFilter" class="keywordFilter"/>' . "\n";
                    $html .= '<select class="keywordFilterType">'."\n";
                    $types = array('name' => 'Nom', 'num' => 'Référence', 'type' => 'Type', 'price' => 'Prix');
                    foreach ($types as $key => $type) {
                        $html .= '<option value="'.$key.'">'.$type.'</option>'."\n";
                    }
                    $html .= '</select>'."\n";
                    $html .= '<button class="addKeywordFilter" onclick="GSX.products['.$prodId.'].PM.addKeywordFilter()">Ajouter</button>' . "\n";
                    $html .= '</div>' . "\n";
                    $html .= '<div class="searchBloc">' . "\n";
                    $html .= '<label for="searchPartInput">Recherche par référence: </label>' . "\n";
                    $html .= '<input type="text" name="searchPartInput" class="searchPartInput" size="12" maxlength="24"/>';
                    $html .= '<button class="searchPartSubmit" onclick="GSX.products['.$prodId.'].PM.searchPartByNum()">Rechercher</button>' . "\n";
                    $html .= '</div>' . "\n";
                    $html .= '<div class="curKeywords"></div>' . "\n";
                    $html .= '<div class="searchResult"></div>';
                    $html .= '<div class="partsListContainer"></div>' . "\n";
                    $html .= '</div>' . "\n";

                    $html .= '<script type="text/javascript">' . "\n";
                    foreach ($parts as $part) {
                        $html .= 'GSX.addPart('.$prodId.', ';
                        $html .= '\'' . (isset($part['componentCode']) ? addslashes($part['componentCode']) : '') . '\'';
                        $html .= ', \'' . (isset($part['partDescription']) ? addslashes($part['partDescription']) : '') . '\'';
                        $html .= ', \'' . (isset($part['partNumber']) ? addslashes($part['partNumber']) : '') . '\'';
                        $html .= ', \'' . (isset($part['partType']) ? addslashes($part['partType']) : '') . '\'';
                        $html .= ', \'' . (isset($part['stockPrice']) ? addslashes($part['stockPrice']) : '') . '\'';
                        $html .= ');' . "\n";
                    }
                    $html .= '</script>' . "\n";
                }
            }
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
                            } else {
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

    public function getRequestFormHtml($requestType) {
        return GSX_Request::generateRequestFormHtml($requestType, array('serialNumber' => $this->serial));
    }

    public function addToCart($partRef, $qty) {
        $this->partsCart[$partRef] = $qty;
    }

    public function saveCart() {
        if (!count($this->partsCart))
            return false;
    }

    public function sendOrderFromCart() {
        if (!count($this->partsCart))
            return false;
    }
}

?>
