<?php

require_once ( 'GSX.class.php' );

class gsxDatas {

    public $gsx = null;
    protected $serial = null;
    protected $errors = array();
    public static $componentsTypes = array(
        0 => 'Général',
        1 => 'Visuel',
        2 => 'Affichage',
        3 => 'Stockage',
        4 => 'Périphériques d\'entrées',
        5 => 'Cartes',
        6 => 'Alimentation',
        7 => 'Impression',
        8 => 'Périphériques multi-fonctions',
        9 => 'Périphériques de communication',
        'A' => 'Partage',
        'B' => 'iPhone',
        'E' => 'iPod',
        'F' => 'iPad'
    );

    public function __construct($serial) {
        global $user;
        if (isset($user->array_options['options_apple_id']) && isset($user->array_options['options_apple_mdp']) && isset($user->array_options['options_apple_service']))
            $details = array(
                'apiMode' => 'production',
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
                'apiMode' => 'production',
                'regionCode' => 'emea',
                'userId' => 'Corinne@actitec.fr',
                'password' => 'cocomart01',
                'serviceAccountNo' => '0000100635',
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
        $html = '<input type="hidden" id="curSerial" value="' . $this->serial . '"/>';
        if (isset($response) && count($response)) {
            if (isset($response['ResponseArray']) && count($response['ResponseArray'])) {
                if (isset($response['ResponseArray']['responseData']) && count($response['ResponseArray']['responseData'])) {
                    $datas = $response['ResponseArray']['responseData'];
//                    echo '<pre>';
//                    echo print_r($datas);
//                    echo '</pre>';
                    $check = true;

                    $html .= '<table id="productDatas">' . "\n";
                    $html .= '<thead><caption>' . $datas['productDescription'] . '</caption></thead>' . "\n";
                    $html .= '<tbody>' . "\n";
                    $html .= '' . "\n";

                    $html .= '<tr>' . "\n";

                    $src = $datas['imageURL'];
                    if (isset($src) && $src) {
                        $html .= '<td class="productImgContainer">' . "\n";
                        $html .= '<img class="productImg" src="' . $src . '"/>' . "\n";
                        $html .= '</td>' . "\n";
                    }

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

                    $html .= '<div id="componentsListContainer">' . "\n";
                    $html .= '<div class="titre">Liste des composants compatibles</div>' . "\n";
                    $html .= '<div id="typeFilters" class="searchBloc">' . "\n";
                    $html .= '<div id="filterTitle">Filtrer par catégorie de composant</div>';
                    $html .= '<div id="typeFiltersContent"><div style="margin-bottom: 20px;"><span id="filterCheckAll">Tout cocher</span>';
                    $html .= '<span id="filterHideAll">Tout décocher</span></div></div>';
                    $html .= '</div>' . "\n";
                    $html .= '<div class="searchBloc"' . "\n";
                    $html .= '<label for="keywordFilter">Filtrer par mots-clés: </label>' . "\n";
                    $html .= '<input type="text max="80" name="keywordFilter" id="keywordFilter"/>' . "\n";
                    $html .= '<button id="addKeywordFilter" onclick="addKeywordFilter()">Ajouter</button>' . "\n";
                    $html .= '</div>' . "\n";
                    $html .= '</div>' . "\n";
                    $html .= '<div id="curKeywords"></div>'."\n";
                    $html .= '<div id="partsListContainer"></div>' . "\n";

                    $parts = $this->gsx->part(array('serialNumber' => $this->serial));

                    $checkParts = false;
                    if (isset($parts) && count($parts)) {
                        if (isset($parts['ResponseArray']) && count($parts['ResponseArray'])) {
                            if (isset($parts['ResponseArray']['responseData']) && count($parts['ResponseArray']['responseData'])) {
                                $checkParts = true;
                                $parts = $parts['ResponseArray']['responseData'];
                                $html .= '<script type="text/javascript">'."\n";
                                foreach ($parts as $part) {
                                    $html .= 'PM.addPart(\''.  addslashes($part['componentCode']).'\', \''.addslashes($part['partDescription']).'\', ';
                                    $html .= '\''.addslashes($part['partNumber']).'\', \''.addslashes($part['partType']).'\');'."\n";
                                }
                                $html .= '</script>'."\n";
                            }
                        }
                    }
                    if (!$checkParts) {
                        $html .= '<p class="error">Echec de la récupération de la liste des composants compatibles depuis la plateforme GSX</p>';
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
}

?>
