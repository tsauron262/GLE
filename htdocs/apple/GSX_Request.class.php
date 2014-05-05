<?php
require_once ( 'GSX.class.php' );

class GSX_Request {
    public static $datas_values = array(
        'regions_values' => array(
            '002' => 'Asie / Pacifique',
            '003' => 'Japon',
            '004' => 'Europe',
            '005' => 'Etats-unis',
            '006' => 'Canada',
            '007' => 'Amérique du sud'
        )
    );
    public static $data_definitions = array(
        'partNumber' => array(
            'label' => 'Référence',
            'type' => 'text',
            'max' => 18
        ),
        'comptiaCode' => array(
            'label' => 'Code CompTIA',
            'type' => 'comptiaCode'
        ),
        'comptiaModifier' => array(
            'label' => 'Modificateur',
            'type' => 'comptiaModifier'
        ),
        'addressLine1' => array(
            'label' => 'Adresse ligne 1',
            'type' => 'text',
            'max' => 60,
            'jsCheck' => 'text'
        ),
        'addressLine2' => array(
            'label' => 'Adresse ligne 2',
            'type' => 'text',
            'max' => 40,
            'jsCheck' => 'text'
        ),
        'addressLine3' => array(
            'label' => 'Adresse ligne 3',
            'type' => 'text',
            'max' => 40,
            'jsCheck' => 'text'
        ),
        'addressLine4' => array(
            'label' => 'Adresse ligne 4',
            'type' => 'text',
            'max' => 40,
            'jsCheck' => 'text'
        ),
        'city' => array(
            'label' => 'Ville',
            'type' => 'text',
            'max' => 40,
            'jsCheck' => 'text'
        ),
        'companyName' => array(
            'label' => 'Société',
            'type' => 'text',
            'max' => 100,
            'jsCheck' => 'text'
        ),
        'country' => array(
            'label' => 'Pays',
            'type' => 'text',
            'max' => 3,
            'jsCheck' => 'text'
        ),
        'firstName' => array(
            'label' => 'Prénom',
            'type' => 'text',
            'max' => 96,
            'jsCheck' => 'text'
        ),
        'lastName' => array(
            'label' => 'Nom',
            'type' => 'text',
            'max' => 96,
            'jsCheck' => 'text'
        ),
        'primaryPhone' => array(
            'label' => 'Numéro de téléphone principal',
            'type' => 'tel',
            'max' => 30,
            'jsCheck' => 'phone'
        ),
        'secondaryPhone' => array(
            'label' => 'Numéro de téléphone secondaire',
            'type' => 'tel',
            'max' => 30,
            'jsCheck' => 'phone'
        ),
        'region' => array(
            'label' => 'Région',
            'type' => 'select',
            'default' => '004',
            'values' => 'regions_values'
        ),
        'state' => array(
            'label' => 'Etat',
            'type' => 'text',
            'max' => 3,
            'jsCheck' => 'text'
        ),
        'zipCode' => array(
            'label' => 'Code postale',
            'type' => 'number',
            'max' => 10,
            'jsCheck' => 'codePostal'
        ),
        'emailAdresse' => array(
            'label' => 'Adresse e-mail',
            'type' => 'email',
            'max' => 241,
            'jsCheck' => 'email'
        ),
        'billTo' => array(
            'label' => 'Numéro "Bill to',
            'type' => 'text',
            'max' => 10,
            'jsCheck' => 'text'
        ),
        'checkIfOutOfWarrantyCoverage' => array(
            'label' => 'Vérifier la couverture hors garantie',
            'type' => 'YesNo',
            'max' => 1
        ),
        'customerAddress' => array(
            'label' => 'Adresse du client',
            'type' => 'datasGroup'
        ),
        'diagnosedByTechId' => array(
            'label' => 'ID du technicien à l\'origine du diagnostique',
            'type' => 'text',
            'max' => 15,
            'jsCheck' => 'text'
        ),
        'diagnosis' => array(
            'label' => 'Diagnostique du technicien',
            'type' => 'textarea',
            'max' => 1500,
            'jsCheck' => 'text'
        ),
        'fileName' => array(
            'label' => 'Nom du fichier à envoyer',
            'type' => 'fileSelect',
            'max' => 30
        ),
        'fileData' => array(
            'label' => 'Fichier',
            'type' => 'file'
        ),
        'notes' => array(
            'label' => 'Notes du technicien',
            'type' => 'textarea',
            'max' => 800,
            'jsCheck' => 'text'
        ),
        'orderLines' => array(
            'label' => 'Composants',
            'type' => 'partsList',
            'datas' => array(
                ''
            )
        ),
        'overrideDiagnosticCodeCheck' => array(
            'label' => 'Ne pas effectuer la vérification du code du diagnostique',
            'type' => 'YesNo',
            'infos' => 'Si Oui, le code de diagnostique n\'est pas vérifié lors la création de la réparation et aucun message n\'est affiché.'
        ),
        'poNumber' => array(
            'label' => 'Purchase order number',
            'type' => 'text',
            'max' => 35,
            'jsCheck' => 'text'
        ),
        'popFaxed' => array(
            'label' => 'Preuve d\'achat faxé',
            'type' => 'YesNo',
        ),
        'referenceNumber' => array(
            'label' => 'Référence',
            'type' => 'text',
            'max' => 15,
            'jsCheck' => 'text'
        ),
        'requestReviewByApple' => array(
            'label' => 'Vérification de la requête par Apple',
            'type' => 'YesNo'
        ),
        'serialNumber' => array(
            'label' => 'Numéro de série',
            'type' => 'text',
            'max' => 18,
            'jsCheck' => 'text'
        ),
        'shipTo' => array(
            'label' => 'Ship To number',
            'type' => 'text',
            'max' => 10,
            'jsCheck' => 'text'
        ),
        'symptom' => array(
            'label' => 'Symptômes de l\'unité',
            'type' => 'textarea',
            'max' => 1500,
            'jsCheck' => 'text'
        ),
        'unitReceivedDate' => array(
            'label' => 'Date de réception de l\'unité',
            'type' => 'date'
        ),
        'unitReceivedTime' => array(
            'label' => 'Heure de réception de l\'unité',
            'type' => 'time'
        ),
        'componentCheckReview' => array(
            'label' => 'Vérification des composants',
            'type' => 'YesNo'
        ),
    );
    public static $requests_definitions = array(
        'CreateCarryInReturnBeforeReplace' => array(
            'name' => 'Création d\'une réparation de type "Retour avant Remplacement"',
            'requestDatas' => array(
                'billTo' => array(
                    'required' => 0
                ),
                'checkIfOutOfWarrantyCoverage' => array(
                    'required' => 0,
                    'default' => 'N'
                ),
                'customerAddress' => array(
                    'required' => 1,
                    'datas' => array(
                        'addressLine1' => array(
                            'required' => 1
                        ),
                        'addressLine2' => array(
                            'required' => 0
                        ),
                        'addressLine3' => array(
                            'required' => 0
                        ),
                        'addressLine4' => array(
                            'required' => 0
                        ),
                        'city' => array(
                            'required' => 1
                        ),
                        'companyName' => array(
                            'required' => 0
                        ),
                        'country' => array(
                            'required' => 1
                        ),
                        'firstName' => array(
                            'required' => 1
                        ),
                        'lastName' => array(
                            'required' => 1
                        ),
                        'primaryPhone' => array(
                            'required' => 1
                        ),
                        'secondaryPhone' => array(
                            'required' => 0
                        ),
                        'region' => array(
                            'required' => 1
                        ),
                        'state' => array(
                            'required' => 1
                        ),
                        'zipCode' => array(
                            'required' => 1
                        ),
                        'emailAdresse' => array(
                            'required' => 1
                        )
                    )
                ),
                'diagnosedByTechId' => array(
                    'required' => 0
                ),
                'diagnosis' => array(
                    'required' => 1
                ),
                'fileName' => array(
                    'required' => 0
                ),
                'fileData' => array(
                    'required' => 0
                ),
                'notes' => array(
                    'required' => 0
                ),
                'orderLines' => array(
                    'required' => 1,
                    'datas' => array(
                        'partNumber' => array(
                            'required' => 1
                        ),
                        'comptiaCode' => array(
                            'required' => 0
                        ),
                        'comptiaModifier' => array(
                            'required' => 0
                        )
                    )
                ),
                'billTo' => array(
                    'required' => 0
                ),
                'overrideDiagnosticCodeCheck' => array(
                    'required' => 0,
                    'default' => 'Y'
                ),
                'poNumber' => array(
                    'required' => 1
                ),
                'popFaxed' => array(
                    'required' => 0,
                    'default' => 'N'
                ),
                'referenceNumber' => array(
                    'required' => 0
                ),
                'requestReviewByApple' => array(
                    'required' => 0,
                    'default' => 'N'
                ),
                'serialNumber' => array(
                    'required' => 1
                ),
                'shipTo' => array(
                    'required' => 1
                ),
                'symptom' => array(
                    'required' => 1
                ),
                'unitReceivedDate' => array(
                    'required' => 1
                ),
                'unitReceivedTime' => array(
                    'required' => 1
                ),
                'componentCheckReview' => array(
                    'required' => 0,
                    'default' => 'N'
                ),
            ),
            'responseDatas' => array()
        )
    );

    protected static function getDataInput($name, $defs, $options, $value = null) {
        if ($defs['type'] == 'file')
            return '';

        $html = '';
        if (isset($defs)) {
            if (isset($options['required']) && $options['required'])
                $required = true;
            else
                $required = false;

            if ($defs['type'] == 'datasGroup') {
                $html .= '<fieldset id="' . $name . '">';
                if (isset($defs['label'])) {
                    $html .= '<legend>' . $defs['label'];
                    if (isset($defs['infos'])) {
                        $html .= '<span class="displayInfos" onmouseover="displayLabelInfos($(this))" onmouseout="hideLabelInfos($(this))">';
                        $html .= '<div class="labelInfos">' . $defs['infos'] . '</div></span>';
                    }
                    $html .= '</legend>' . "\n";
                }
                if (isset($options['datas'])) {
                    foreach ($options['datas'] as $n => $opts) {
                        $val = null;
                        if (isset($value)) {
                            if (is_array($value)) {
                                if (isset($value[$n]))
                                    $val = $value[$n];
                            }
                        }
                        $html .= self::getDataInput($n, self::$data_definitions[$n], $opts, $val);
                    }
                } else {
                    $html .= '<p class="alert">Aucunes définitions pour ces données</p>' . "\n";
                }
                $html .= '</fieldset>' . "\n";
            } else {
                if (isset($defs['label'])) {
                    $html .= '<label class="dataTitle" for="' . $name . '">' . $defs['label'];
                    $html .= ($required ? '<sup><span class="required"></span></sup>' : '');
                    if (isset($defs['infos'])) {
                        $html .= '<span class="displayInfos" onmouseover="displayLabelInfos($(this))" onmouseout="hideLabelInfos($(this))">';
                        $html .= '<div class="labelInfos">' . $defs['infos'] . '</div></span>';
                    }
                    $html .= '</label>';
                }
                switch ($defs['type']) {
                    case 'text':
                    case 'number':
                    case 'tel':
                    case 'email':
                    case 'date':
                    case 'time':
                        $html .= '<br/>' . "\n";
                        $html .= '<input type="' . $defs['type'] . '" ';
                    case 'textarea':
                        if ($defs['type'] == 'textarea') {
                            if (isset($defs['max']))
                                $html .= '<span style="font-size: 11px; color: #783131">(max '.$defs['max'].' caractères)</span>';
                            $html .= '<br/>' . "\n";
                            $html .= '<textarea cols="80" rows="10" ';
                        }
                        $html .= 'id="' . $name . '" name="' . $name . '"' . $required ? ' required' : '';

                        if ($defs['type'] != 'textarea') {
                            if (isset($value))
                                $html .= ' value="' . $value . '"';
                            else if (isset($options['default']))
                                $html .= ' value="' . $options['default'] . '"';
                        }

                        $html .= isset($defs['max']) ? ' maxlength="' . $defs['max'] . '"' : '';
                        $html .= isset($defs['jsCheck']) ? ' onchange="checkInput($(this), \'' . $defs['jsCheck'] . '\')"' : '';

                        if ($defs['type'] == 'textarea') {
                            $html .= '>';
                            if (isset($value))
                                $html .= $value;
                            else if (isset($options['default']))
                                $html .= $options['default'];
                            $html .= '</textarea>' . "\n";
                        }
                        else
                            $html .= '/>' . "\n";
                        break;

                    case 'select':
                        if (isset($defs['values'])) {
                            if (isset(self::$datas_values[$defs['values']])) {
                                $html .= '<select name="' . $name . '" id="' . $name . '"' . ($required ? ' required' : '' ). '>';
                                $html .= '<option value="0">&nbsp;&nbsp;---&nbsp;&nbsp;</option>';
                                foreach (self::$datas_values[$defs['values']] as $v => $txt) {
                                    $html .= '<option value="' . $v . '"';
                                    if (isset($value)) {
                                        if ($value == $v)
                                            $html.= ' selected';
                                    } else if (isset($options['default'])) {
                                        if ($options['default'] == $v)
                                            $html .= ' selected';
                                    }
                                    $html .= '>' . $txt . '</option>' . "\n";
                                }
                                $html .= '</select>';
                                break;
                            }
                        }
                        $html .= '<p class="alert">Aucune valeur défini pour la liste de choix "' . $name . '"</p>' . "\n";
                        break;

                    case 'YesNo':
                        $defVal = 'Y';
                        if (isset($value))
                            $defVal = $value;
                        else if (isset($options['default']))
                            $defVal = $options['default'];

                        $html .= '<input type="radio" id="' . $name . '_yes" name="' . $name . '" value="Y" ' . (($defVal == 'Y') ? 'checked' : '' ). '/>' . "\n";
                        $html .= '<label for="' . $name . '_yes">Oui</label>' . "\n";
                        $html .= '<input type="radio" id="' . $name . '_no" name="' . $name . '" value="N" ' . (($defVal == 'N') ? 'checked' : '' ). '/>' . "\n";
                        $html .= '<label for="' . $name . '_no">Non</label>' . "\n";
                        break;

                    default:
                        $html .= '<p class="alert">Type inéxistant pour la donnée "' . $name . '"</p>';
                        break;

                    case 'fileSelect':
                        $html .= '<input type="file" id="' . $name . '" name="' . $name . '"/>';
                        break;

                    case 'partsList':
                        $html .= '<br/>';
                        $html .= '<button class="blueHover" onclick="importPartsFromCart(' . $name . ')">Importer la liste des composants depuis le panier</button><br/>' . "\n";
                        $html .= '<table class="requestParts" id="' . $name . '_table"><thead>' . "\n";
                        $html .= '<th style="min-width: 250px">Nom</th>';
                        foreach ($options['datas'] as $n => $opts) {
                            $html .= '<th>' . self::$data_definitions[$n]['label'] . '</th>' . "\n";
                        }
                        $html .= '</thead><tbody>' . "\n";
                        if (isset($value) && is_array($value)) {
                            $idx = 1;
                            foreach ($value as $partDatas) {
                                $html .= '<tr class="partDatasRow" id="partDatas_' . $idx . '">';
                                foreach ($options['datas'] as $n => $opts) {
                                    $html .= '<td>';
                                    if (isset($partDatas[$n])) {
                                        switch (self::$data_definitions[$n]['type']) {
                                            case 'comptiaCode':
                                            case 'comptiaModifier':
                                                $html .= $partDatas[$n];
                                                $html .= ' <input type="hidden id="part_' . $idx . '_' . $n . '" name="part_' . $idx . '_' . $n . '" value="' . $partDatas[$n] . '"/>"' . "\n";
                                                break;

                                            case 'text':
                                            case 'number':
                                                $html .= '<input type="'.self::$data_definitions[$n]['type'].'" id="part_' . $idx . '_' . $n . '" value="'.$partDatas[$n].'"';
                                                if (isset(self::$data_definitions[$n]['max']))
                                                    $html .= ' maxlength="'.self::$data_definitions[$n]['max'].'"';
                                                if (isset(self::$data_definitions[$n]['JsCheck']))
                                                    $html .= ' onchange="checkInput($(this),\''.self::$data_definitions[$n]['jsCheck'].'\')"';
                                                $html .= '/>'."\n";
                                                break;
                                        }
                                    }
                                    $html .= '</td>' . "\n";
                                }
                                $html .= '</tr>';
                                $idx++;
                            }
                        }
                        $html .= '</tbody></table>' . "\n";
                        break;
                }
            }
            $html .= '<br/>';
        } else {
            $html .= '<p class="alert">Aucunes définitions pour la donnée: ' . $name . '</p>';
        }
        return $html;
    }

    public static function generateRequestFormHtml($request, $values) {
        if (!isset(self::$requests_definitions[$request])) {
            return '<p class="error">Le type de requête demandé n\'est pas défini</p>';
        }
        $html = '<form id="gsxRequestForm" method="POST" action="./requestProcess.php&action=sendGSXRequest&request=' . $request . ' enctype="multipart/form-data">' . "\n";
        $html .= '<div class="requestTitle">' . self::$requests_definitions[$request]['name'] . '</div>' . "\n";
        $html .= '<p class="requiredInfos"><sup><span class="required"></span></sup>Champs requis</p>';
        $html .= '<div class="requestFormInputs">' . "\n";
        foreach (self::$requests_definitions[$request]['requestDatas'] as $name => $options) {
            $defs = isset(self::$data_definitions[$name]) ? self::$data_definitions[$name] : null;
            $html .= self::getDataInput($name, $defs, $options, $values[$name] ? $values[$name] : null);
        }
        $html .= '</div>' . "\n";
        $html .= '<div style="text-align: right"><button class="submit greenHover" onclik="submitGsxRequestForm()">Envoyer</button></div>';
        $html .= '</form>' . "\n";
        return $html;
    }
}

?>
