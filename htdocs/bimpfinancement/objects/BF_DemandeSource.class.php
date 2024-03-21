<?php

class BF_DemandeSource extends BimpObject
{

    public static $types = array();
    public static $types_origines = array(
        'propale'  => array('label' => 'Devis', 'icon' => 'fas_file-invoice', 'is_female' => 0),
        'commande' => array('label' => 'Commmande', 'icon' => 'fas_dolly', 'is_female' => 1)
    );

    // Droits user: 

    public function canDelete()
    {
        return BimpCore::isUserDev();
    }

    // Getters données: 

    public function getIDApi()
    {
        $type = $this->getData('type');

        if ($type) {
            return (int) BimpCore::getConf('id_api_webservice_' . $type, null, 'bimpfinancement');
        }

        BimpCore::addlog('ID API non configuré pour le type de source "' . $type . '" du module bimpfinancement', Bimp_Log::BIMP_LOG_URGENT, 'divers');
        return 0;
    }

    public function getIdFourn()
    {
        $type = $this->getData('type');

        if ($type) {
            return (int) BimpCore::getConf('id_fourn_' . $type, null, 'bimpfinancement');
        }

        BimpCore::addlog('ID Fournisseur non configuré pour le type de source "' . $type . '" du module bimpfinancement', Bimp_Log::BIMP_LOG_URGENT, 'divers');
        return 0;
    }

    public function getAPI(&$errors = array(), $check_validity = true)
    {
        $id_api = $this->getIDApi();

        if (!$id_api) {
            $errors[] = 'ID API non configuré pour ' . $this->displayData('ext_origine', 'default', false);
        } else {
            BimpObject::loadClass('bimpapi', 'API_Api');
            return API_Api::getApiInstanceByID($id_api, $errors, $check_validity);
        }

        return null;
    }

    public function getClientFullAddress($icon = false, $single_line = false)
    {
        $client_data = $this->getData('client_data');

        if (isset($client_data['address'])) {
            $address = BimpTools::getArrayValueFromPath($client_data, 'address/address', '');
            $zip = BimpTools::getArrayValueFromPath($client_data, 'address/zip', '');
            $town = BimpTools::getArrayValueFromPath($client_data, 'address/town', '');
            $pays = BimpTools::getArrayValueFromPath($client_data, 'address/pays', '');

            return BimpTools::displayAddress($address, $zip, $town, '', $pays, $icon, $single_line);
        }

        return '';
    }

    public function getClientInfosContact($icons = true, $single_line = false)
    {
        $infos_contact = '';

        $client_data = $this->getData('client_data');

        if (isset($client_data['contact'])) {
            $contact = $client_data['contact'];

            $nom = BimpTools::getArrayValueFromPath($contact, 'prenom', '');
            $nom .= ($nom ? ' ' : '') . BimpTools::getArrayValueFromPath($contact, 'nom', '');
            $tel = BimpTools::getArrayValueFromPath($contact, 'tel', '');
            $mobile = BimpTools::getArrayValueFromPath($contact, 'mobile', '');
            $email = BimpTools::getArrayValueFromPath($contact, 'email', '');

            if ($nom) {
                $infos_contact .= ($infos_contact ? ($single_line ? ' - ' : '<br/>') : '') . ($icons ? BimpRender::renderIcon('fas_user-circle', 'iconLeft') : '') . $nom;
            }
            if ($tel) {
                $infos_contact .= ($infos_contact ? ($single_line ? ' - ' : '<br/>') : '') . ($icons ? BimpRender::renderIcon('fas_phone', 'iconLeft') : '') . $tel;
            }
            if ($mobile) {
                $infos_contact .= ($infos_contact ? ($single_line ? ' - ' : '<br/>') : '') . ($icons ? BimpRender::renderIcon('fas_mobile', 'iconLeft') : '') . $mobile;
            }
            if ($email) {
                $infos_contact .= ($infos_contact ? ($single_line ? ' - ' : '<br/>') : '') . ($icons ? BimpRender::renderIcon('fas_at', 'iconLeft') : '');
                $infos_contact .= '<a href="mailto: ' . BimpTools::cleanEmailsStr($email) . '">' . $email . '</a>';
            }
        }

        return $infos_contact;
    }

    public function getCommercialInfosContact($icons = true, $single_line = false)
    {
        $infos_contact = '';

        $comm_data = $this->getData('commercial_data');

        if (!empty($comm_data)) {
            $tel = BimpTools::getArrayValueFromPath($comm_data, 'tel', '');
            $email = BimpTools::getArrayValueFromPath($comm_data, 'email', '');
            if ($tel) {
                $infos_contact .= ($infos_contact ? ($single_line ? ' - ' : '<br/>') : '') . ($icons ? BimpRender::renderIcon('fas_phone', 'iconLeft') : '') . $tel;
            }
            if ($email) {
                $infos_contact .= ($infos_contact ? ($single_line ? ' - ' : '<br/>') : '') . ($icons ? BimpRender::renderIcon('fas_at', 'iconLeft') : '') . $email;
            }
        }

        return $infos_contact;
    }

    public function getSignataireName()
    {
        $client = $this->getData('client_data');

        $name = BimpTools::ucfirst(strtolower(BimpTools::getArrayValueFromPath($client, 'signataire/prenom', '')));

        $lastname = strtoupper(BimpTools::getArrayValueFromPath($client, 'signataire/nom', ''));
        if ($lastname) {
            $name .= ($name ? ' ' : '') . $lastname;
        }

        return $name;
    }

    public function getAdressesLivraisons()
    {
        $client_data = $this->getData('client_data');

        $livraisons = BimpTools::getArrayValueFromPath($client_data, 'livraisons', array());
        if (!empty($livraisons)) {
            $return = '';
            $fl = true;
            foreach ($livraisons as $livraison) {
                if (!$fl) {
                    $return .= '<br/><br/>';
                } else {
                    $fl = false;
                }

                $address = BimpTools::getArrayValueFromPath($livraison, 'address', '');
                $zip = BimpTools::getArrayValueFromPath($livraison, 'zip', '');
                $town = BimpTools::getArrayValueFromPath($livraison, 'town', '');
                $pays = BimpTools::getArrayValueFromPath($livraison, 'pays', '');
                $return = BimpTools::displayAddress($address, $zip, $town, '', $pays, 0, 1);
            }

            return $return;
        }

        return $this->getClientFullAddress(0, 0);
    }

    public function getClientPdfData()
    {
        $client = $this->getData('client_data');
        return array(
            'ref'         => BimpTools::getArrayValueFromPath($client, 'ref', ''),
            'is_company'  => (int) BimpTools::getArrayValueFromPath($client, 'is_company', 0),
            'nom'         => BimpTools::getArrayValueFromPath($client, 'nom', ''),
            'full_adress' => $this->getClientFullAddress(false, false)
        );
    }

    // Affichages: 

    public function displayName()
    {
        $type = $this->getData('type');
        if ($type && isset(self::$types[$type])) {
            return self::$types[$type];
        }

        return '';
    }

    public function displayOrigine($with_type_source = false, $with_icon = false, $with_article = false)
    {
        $html = '';

        $type_origine = $this->getData('type_origine');

        if ($with_icon && isset(self::$types_origines[$type_origine]['icon'])) {
            $html .= BimpRender::renderIcon(self::$types_origines[$type_origine]['icon'], 'iconLeft');
        }

        if (isset(self::$types_origines[$type_origine]['label'])) {
            if ($with_article) {
                $html .= ((int) BimpTools::getArrayValueFromPath(static::$types_origines, $type_origine . '/is_female', 0) ? 'la' : 'le') . ' ';
            }
            $label = self::$types_origines[$type_origine]['label'];

            if ($with_article) {
                $label = strtolower($label);
            }

            $html .= $label;
        } else {
            if ($with_article) {
                $html .= 'la pièce "' . $type_origine . '"';
            } else {
                $html .= $type_origine;
            }
        }

        if ($with_type_source) {
            $type = $this->getData('type');
            if ($type && isset(self::$types[$type])) {
                $html .= ($html ? ' ' : '') . self::$types[$type];
            }
        }

        $origine = $this->getData('origine_data');

        if (isset($origine['id'])) {
            $html .= ($html ? ' ' : '') . '#' . $origine['id'];
        }

        if (isset($origine['ref'])) {
            $html .= ($html ? ' - ' : '') . $origine['ref'];
        }

        return $html;
    }

    public function displayClient($with_popover_infos = false)
    {
        $html = '';
        $client_data = $this->getData('client_data');
        if (!empty($client_data)) {
            $label = '';
            $label .= BimpRender::renderIcon('fas_user-circle', 'iconLeft') . 'Client ' . $this->displayName() . ' : ';
            $ref = BimpTools::getArrayValueFromPath($client_data, 'ref', '');
            if (!$ref) {
                $id = BimpTools::getArrayValueFromPath($client_data, 'id', 0);
                if ($id) {
                    $ref = '#' . $id;
                }
            }
            $nom = BimpTools::getArrayValueFromPath($client_data, 'nom', '');
            $label .= ($ref ? ' ' . $ref : '') . ($nom ? ' ' . $nom : '');

            $popover = '';
            if ($with_popover_infos) {
                $popover = $this->getClientFullAddress();
                $popover .= ($popover ? '<br/><br/>' : '') . $this->getClientInfosContact();
            }

            if ($popover) {
                $html .= '<span class="objectLink">';
                $html .= '<span class="card-popover bs-popover"' . BimpRender::renderPopoverData($popover, 'bottom', 'true') . '>';
                $html .= $label;
                $html .= '</span>';
                $html .= '<span class="objectIcon cardPopoverIcon">';
                $html .= BimpRender::renderIcon('fas_sticky-note');
                $html .= '</span>';
                $html .= '</span>';
            } else {
                $html .= $label;
            }
        }

        return $html;
    }

    public function displayCommercial($with_popover_infos = false)
    {
        $html = '';

        $comm_data = $this->getData('commercial_data');
        if (!empty($comm_data)) {
            $nom = BimpTools::getArrayValueFromPath($comm_data, 'nom', '');

            if ($nom) {
                $popover = '';
                if ($with_popover_infos) {
                    $popover = $this->getCommercialInfosContact();
                }

                if ($popover) {
                    $html .= '<span class="objectLink">';
                    $html .= '<span class="card-popover bs-popover"' . BimpRender::renderPopoverData($popover, 'bottom', 'true') . '>';
                    $html .= $nom;
                    $html .= '</span>';
                    $html .= '<span class="objectIcon cardPopoverIcon">';
                    $html .= BimpRender::renderIcon('fas_sticky-note');
                    $html .= '</span>';
                    $html .= '</span>';
                } else {
                    $html .= $nom;
                }
            }
        }

        return $html;
    }

    public function displayExtraData($data, $key, $is_table_row = false)
    {
        $html = '';

        $label = '';
        $value = '';
        $data_type = 'string';

        if (is_array($data[$key])) {
            $label = BimpTools::getArrayValueFromPath($data, $key . '/label', $key);
            $value = BimpTools::getArrayValueFromPath($data, $key . '/value', '');
            $data_type = BimpTools::getArrayValueFromPath($data, $key . '/type', 'string');
        } else {
            $label = $key;
            $value = $data[$key];
        }

        if (!(string) $value) {
            return '';
        }

        switch ($data_type) {
            case 'money':
                $value = BimpTools::displayMoneyValue($value);
                break;

            case 'float':
                $value = BimpTools::displayFloatValue($value);
                break;
        }

        if ($is_table_row) {
            $html .= '<tr>';
            $html .= '<th>' . $label . '</th>';
            $html .= '<td>';
        } else {
            $html .= '<span class="bold">' . $label . ' : </span>';
        }

        $html .= $value;

        if ($is_table_row) {
            $html .= '</td>';
            $html .= '</tr>';
        }

        return $html;
    }

    // Rendus HTML: 

    public function renderView($view_name = 'default', $panel = false, $level = 1)
    {
        $html = '';

        $html .= '<div class="row">';
        $html .= '<div class="col-xs-12 col-sm-6">';
        $html .= $this->renderClientData();
        $html .= '</div>';

        $html .= '<div class="col-xs-12 col-sm-6">';
        $html .= '<div>';
        $html .= $this->renderOrigineData();
        $html .= '</div>';

        $html .= '<div style="margin-top: 15px">';
        $html .= $this->renderCommercialData();
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderOrigineData()
    {
        $html = '';

        $origine = $this->getData('origine_data');

        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th colspan="2" style="font-size: 14px">' . $this->displayOrigine(false, true) . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody">';

        if (!empty($origine)) {
            if (isset($origine['extra_data'])) {
                foreach ($origine['extra_data'] as $data_name => $data_def) {
                    $html .= $this->displayExtraData($origine['extra_data'], $data_name, true);
                }
            }
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="2">' . BimpRender::renderAlerts('Données absentes') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    public function renderClientData()
    {
        $html = '';

        $client_data = $this->getData('client_data');

//        $html .= '<pre>';
//        $html .= print_r($client_data, 1);
//        $html .= '</pre>';
        // Client: 
        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th colspan="2" style="font-size: 14px">' . $this->displayClient(false) . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody class="headers_col">';

        if (!empty($client_data)) {
            $is_company = (int) BimpTools::getArrayValueFromPath($client_data, 'is_company', 0);
            $html .= '<tr>';
            $html .= '<th><span class="bold">Client pro</span></th>';
            $html .= '<td>';
            if ($is_company) {
                $html .= '<span class="success">OUI</span>';
            } else {
                $html .= '<span class="danger">NON</span>';
            }
            $html .= '</td>';
            $html .= '</tr>';

            if ($is_company) {
                // SIRET: 
                $html .= '<tr>';
                $html .= '<th>N° SIRET</th>';
                $html .= '<td>';
                $html .= BimpTools::getArrayValueFromPath($client_data, 'siret', '');
                $html .= '</td>';
                $html .= '</tr>';

                // SIREN: 
                $html .= '<tr>';
                $html .= '<th>N° SIREN</th>';
                $html .= '<td>';
                $html .= BimpTools::getArrayValueFromPath($client_data, 'siren', '');
                $html .= '</td>';
                $html .= '</tr>';

                // Forme juridique: 
                $html .= '<tr>';
                $html .= '<th>Forme juridique</th>';
                $html .= '<td>';
                $html .= BimpTools::getArrayValueFromPath($client_data, 'forme_juridique', '');
                $html .= '</td>';
                $html .= '</tr>';

                // Capital social: 
                $html .= '<tr>';
                $html .= '<th>Capital social</th>';
                $html .= '<td>';
                $html .= BimpTools::getArrayValueFromPath($client_data, 'capital', '');
                $html .= '</td>';
                $html .= '</tr>';
            }

            // Adresse: 
            $html .= '<tr>';
            $html .= '<th>Adresse' . ($is_company ? ' (siège)' : '') . '</th>';
            $html .= '<td>';
            $html .= $this->getClientFullAddress(true, false);
            $html .= '</td>';
            $html .= '</tr>';

            // Infos contact: 
            $html .= '<tr>';
            $html .= '<th>Infos de contact</th>';
            $html .= '<td>';
            $html .= $this->getClientInfosContact(true, false);
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<th>Signataire</th>';
            $html .= '<td>';
            $prenom = BimpTools::getArrayValueFromPath($client_data, 'signataire/prenom', '');
            $nom = BimpTools::getArrayValueFromPath($client_data, 'signataire/nom', '');
            $fonction = BimpTools::getArrayValueFromPath($client_data, 'signataire/fonction', '');

            $html .= $prenom;
            if ($nom) {
                $html .= ($prenom ? ' ' : '') . strtoupper($nom);
            }
            if ($fonction) {
                $html .= ($prenom || $nom ? '<br/>' : '') . 'Fonction : ' . $fonction;
            }
            $html .= '</td>';
            $html .= '</tr>';

            $livraisons = BimpTools::getArrayValueFromPath($client_data, 'livraisons', array());

            if (!empty($livraisons)) {
                $html .= '<tr>';
                $html .= '<th>Site(s) de livraison / installation</th>';
                $html .= '<td>';
                $html .= '<ul>';
                foreach ($livraisons as $livraison) {
                    $address = BimpTools::getArrayValueFromPath($livraison, 'address', '');
                    $zip = BimpTools::getArrayValueFromPath($livraison, 'zip', '');
                    $town = BimpTools::getArrayValueFromPath($livraison, 'town', '');
                    $pays = BimpTools::getArrayValueFromPath($livraison, 'pays', '');
                    $html .= '<li>' . BimpTools::displayAddress($address, $zip, $town, '', $pays, 0, 1) . '</li>';
                }
                $html .= '</ul>';
                $html .= '</td>';
                $html .= '</tr>';
            }

            if (isset($client_data['extra_data'])) {
                foreach ($client_data['extra_data'] as $data_name => $data_def) {
                    $html .= $this->displayExtraData($client_data['extra_data'], $data_name, true);
                }
            }
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="2">' . BimpRender::renderAlerts('Données absentes') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    public function renderCommercialData()
    {
        $html = '';

        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th colspan="2" style="font-size: 14px">';
        $html .= BimpRender::renderIcon('fas_user', 'iconLeft');
        $html .= 'Commercial ' . $this->displayData('type', 'default', false);
        $html .= '</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody class="headers_col">';

        $comm_data = $this->getData('commercial_data');

        if (!empty($comm_data)) {
            $html .= '<tr>';
            $html .= '<th><span class="bold">Nom</span></th>';
            $html .= '<td>' . $this->displayCommercial(false) . '</td>';
            $html .= '</tr>';

            if (isset($comm_data['tel'])) {
                $html .= '<tr>';
                $html .= '<th><span class="bold">Tel. pro</span></th>';
                $html .= '<td>' . $comm_data['tel'] . '</td>';
                $html .= '</tr>';
            }
            if (isset($comm_data['email'])) {
                $html .= '<tr>';
                $html .= '<th><span class="bold">E-mail</span></th>';
                $html .= '<td><a href="mailto: ' . BimpTools::cleanEmailsStr($comm_data['email']) . '">' . $comm_data['email'] . '</a></td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="2">' . BimpRender::renderAlerts('Données absentes') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    // Traitements: 

    public function setDemandeFinancementStatus($status, $note = '')
    {
        $errors = array();

        $type_origine = $this->getData('type_origine');
        $id_origine = (int) $this->getData('id_origine');

        if (!$type_origine) {
            $errors[] = 'Type de la pièce d\'origine absent';
        }

        if (!$id_origine) {
            $errors[] = 'ID de la pièce d\'origine absent';
        }

        if (!count($errors)) {
            $api = $this->getAPI($errors);

            if (!count($errors)) {
                $req_errors = array();

                $id_demande = 0;
                if ((int) $this->getData('id_init_demande')) {
                    $id_demande = (int) $this->getData('id_init_demande');
                } else {
                    $id_demande = (int) $this->getData('id_demande');
                }

                $api->setDemandeFinancementStatus($id_demande, $type_origine, $id_origine, $status, $note, $req_errors);

                if (count($req_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de la requête');
                } else {
                    BimpObject::loadClass('bimpcommercial', 'BimpCommDemandeFin');
                    switch ($status) {
                        case BimpCommDemandeFin::DOC_STATUS_REFUSED:
                            $this->updateField('refuse_submitted', 1);
                            break;

                        case BimpCommDemandeFin::DOC_STATUS_CANCELED:
                            $this->updateField('cancel_submitted', 1);
                            break;
                    }
                }
            }
        }

        return $errors;
    }

    public function setDocFinStatus($doc_type, $status, $note = '')
    {
        $errors = array();

        $type_origine = $this->getData('type_origine');
        $id_origine = (int) $this->getData('id_origine');

        if ($type_origine && $id_origine) {
            $api = $this->getAPI($errors);

            if (!count($errors)) {
                $req_errors = array();
                $id_demande = 0;
                if ((int) $this->getData('id_init_demande')) {
                    $id_demande = (int) $this->getData('id_init_demande');
                } else {
                    $id_demande = (int) $this->getData('id_demande');
                }

                $api->setDocFinancementStatus($id_demande, $type_origine, $id_origine, $doc_type, BimpCommDemandeFin::DOC_STATUS_ACCEPTED, $req_errors);

                if (count($req_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de l\'enregistrement du status signé sur ' . $this->displayName());
                }
            }
        }

        return $errors;
    }

    public function reviewDocFin($doc_type)
    {
        $errors = array();

        $type_origine = $this->getData('type_origine');
        $id_origine = (int) $this->getData('id_origine');

        if ($type_origine && $id_origine) {
            $api = $this->getAPI($errors);

            if (!count($errors)) {
                $req_errors = array();
                $id_demande = 0;
                if ((int) $this->getData('id_init_demande')) {
                    $id_demande = (int) $this->getData('id_init_demande');
                } else {
                    $id_demande = (int) $this->getData('id_demande');
                }

                $api->reviewDocFin($id_demande, $type_origine, $id_origine, $doc_type, $req_errors);

                if (count($req_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de l\'annulation du ' . $doc_type . ' sur ' . $this->displayName());
                }
            }
        }

        return $errors;
    }

    public function reopenDemande($df_status, &$warnings = array())
    {
        $errors = array();

        $api = $this->getAPI($errors);
        $demande = $this->getParentInstance();
        if (!BimpObject::objectLoaded($demande)) {
            $errors[] = 'Demande de fincancement liée absente';
        }

        if (!count($errors)) {
            $status = 1;
            if ($df_status >= 20) {
                $status = 20;
            } elseif ($df_status >= 10) {
                $status = 10;
            }

            $id_demande = 0;
            if ((int) $this->getData('id_init_demande')) {
                $id_demande = (int) $this->getData('id_init_demande');
            } else {
                $id_demande = (int) $this->getData('id_demande');
            }

            $req_errors = array();
            $result = $api->reopenDemandeFinancement($id_demande, $this->getData('type_origine'), $this->getData('id_origine'), $status, $req_errors, $warnings);

            if (!count($req_errors)) {
                if (!(int) BimpTools::getArrayValueFromPath($result, 'success', 0)) {
                    $errors[] = 'Echec de la requête auprès de ' . $this->displayName() . ' pour une raison inconnue';
                } else {
                    if ((int) $demande->getData('id_main_source') == $this->id) {
                        $dir = $demande->getFilesDir();
                        $devis_status = (int) $result['devis_status'];
                        if ($devis_status >= 20) {
                            $devis_status = BF_Demande::DOC_REFUSED;
                        } elseif ($devis_status >= 10) {
                            $devis_status = BF_Demande::DOC_ACCEPTED;
                        } elseif ($devis_status > 0) {
                            $devis_status = BF_Demande::DOC_SEND;
                        } else {
                            $file = $demande->getSignatureDocFileName('devis');
                            if (file_exists($dir . $file)) {
                                $devis_status = BF_Demande::DOC_GENERATED;
                            } else {
                                $devis_status = BF_Demande::DOC_NONE;
                            }
                        }
                        $contrat_status = (int) $result['contrat'];
                        if ($contrat_status >= 20) {
                            $contrat_status = BF_Demande::DOC_REFUSED;
                        } elseif ($contrat_status >= 10) {
                            $contrat_status = BF_Demande::DOC_ACCEPTED;
                        } elseif ($contrat_status > 0) {
                            $contrat_status = BF_Demande::DOC_SEND;
                        } else {
                            $file = $demande->getSignatureDocFileName('contrat');
                            if (file_exists($dir . $file)) {
                                $contrat_status = BF_Demande::DOC_GENERATED;
                            } else {
                                $contrat_status = BF_Demande::DOC_NONE;
                            }
                        }
                        $demande->updateField('devis_status', $devis_status);
                        $demande->updateField('contrat_status', $contrat_status);
                    }

                    $this->updateField('cancel_submitted', 0);
                    $this->updateField('refuse_submitted', 0);
                }
            } else {
                $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de la requête auprès de ' . $this->displayName());
            }
        }

        return $errors;
    }
}
