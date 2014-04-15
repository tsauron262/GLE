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
        'C' => 'iPod',
        'D' => 'iPad'
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
                    $html .= '<div class="titre">Rechercher un composant compatible</div>' . "\n";
                    $html .= '<div class="searchBloc">' . "\n";
                    $html .= '<label for="componentType">Type de composant: </label>' . "\n";
                    $html .= '<select name="componentType" id="componentType">' . "\n";
                    foreach (self::$componentsTypes as $code => $name) {
                        $html .= '<option value="' . $code . '" ' . ((!$code) ? 'selected' : '') . '>' . $name . '</option>' . "\n";
                    }
                    $html .= '</select>' . "\n";
                    $html .= '</div>' . "\n";
                    $html .= '<div class="searchBloc"' . "\n";
                    $html .= '<label for="componentSearch">Mots-clés: </label>' . "\n";
                    $html .= '<input type="text max="80" name="componentSearch" id="componentSearch"/>' . "\n";
                    $html .= '</div>' . "\n";
                    $html .= '<button id="componentSearchSubmit" onclick="onComponentSearchSubmit()">Rechercher</button>' . "\n";
                    $html .= '</div>' . "\n";
                    $html .= '<div id="partsResult"></div>' . "\n";
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

    public function getPartsHtml($filter = null, $search = null) {
        $params = array(
            'serialNumber' => $this->serial
        );
        if (isset($search)) {
            $params['partDescription'] = $search;
        }

        $parts = $this->gsx->part($params);
//        echo 'Filtre: ' . $filter . '<br/>';
//        echo '<pre>';
//        print_r($parts);
//        echo '</pre>';

        $html = '';
        $check = false;
        if (isset($parts) && count($parts)) {
            if (isset($parts['ResponseArray']) && count($parts['ResponseArray'])) {
                if (isset($parts['ResponseArray']['responseData']) && count($parts['ResponseArray']['responseData'])) {
                    $check = true;
                    if (isset($filter) && ($filter != 0)) {
                        $filtered = array();
                        foreach ($parts['ResponseArray']['responseData'] as $part) {
                            if (isset($part['componentCode']) && $part['componentCode']) {
                                if ($part['componentCode'] == $filter) {
                                    $filtered[] = $part;
                                }
                            }
                        }
                        $parts = $filtered;
                    } else {
                        $parts = $parts['ResponseArray']['responseData'];
                    }
                }
            }
        }
        if (!$check) {
            $this->errors[] = 'GSX_parts_fail';
            $html .= '<p class="error">Echec de la récupération des données depuis la plateforme Apple GSX</p>' . "\n";
        } else if (count($parts)) {
//            echo '<pre>';
//            print_r($parts);
//            echo '</pre>';
            $html .= '<p>' . count($parts) . ' composants trouvés</p>';
            $odd = true;
            $html .= '<table id="componentsList">' . "\n";
            $html .= '<th style="min-width: 350px">Nom</th>' . "\n";
            $html .= '<th style="min-width: 100px">N°</th>' . "\n";
            $html .= '<th style="min-width: 100px">Type</th>' . "\n";
//            $html .= '<th style="min-width: 120px">Prix</th>' . "\n";
            $html .= '<th></th>' . "\n";
            $html .= '<thead>' . "\n";
            $html .= '</head><tbody>' . "\n";
            foreach ($parts as $part) {
                $html .= '<tr' . ($odd ? ' class="oddRow"' : '') . '>' . "\n";
                $html .= '<td>' . $part['partDescription'] . '</td>' . "\n";
                $html .= '<td>' . $part['partNumber'] . '</td>' . "\n";
                $html .= '<td>' . $part['partType'] . '</td>' . "\n";
//                $html .= '<td>' . $part['exchangePrice'] . '</td>' . "\n";
                $html .= '<td><button>Commander</button></td>' . "\n";
                $html .= '</tr>';
                $odd = !$odd;
            }
            $html .= '</tbody></table>' . "\n";
        } else {
            $html .= '<p>Aucun composant ne correspond à vos critères de recherche</p>';
        }

        return $html;
    }

}

?>
