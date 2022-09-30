<?php

// Entitié: prolease

require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/objects/BF_Demande.class.php';

class BF_Demande_ExtEntity extends BF_Demande
{

    public static $origines = array(
        'none' => array('label' => 'Interne'),
        'bimp' => array('label' => 'BIMP', 'id_api' => 0, 'id_api_user_account')
    );

    // Getters données: 

    public function getExtDataFieldLabel($field)
    {
        $origine = $this->getData('ext_origine');

        if (!$origine || $origine == 'none' || !isset(self::$origines[$origine])) {
            switch ($field) {
                case 'id_ext_propale':
                    return 'ID devis externe';

                case 'id_ext_client':
                    return 'ID client externe';

                case 'id_ext_contact':
                    return 'ID contact client  externe';

                case 'id_ext_commercial':
                    return 'ID commercial externe';

                case 'ext_propale':
                    return 'Données devis externe';

                case 'ext_client':
                    return 'Données client externe';

                case 'ext_contact':
                    return 'Données contact client  externe';

                case 'ext_commercial':
                    return 'Données commercial externe';
            }
        } else {
            switch ($field) {
                case 'id_ext_propale':
                    return 'ID devis ' . self::$origines[$origine];

                case 'id_ext_client':
                    return 'ID client ' . self::$origines[$origine];

                case 'id_ext_contact':
                    return 'ID contact client  ' . self::$origines[$origine];

                case 'id_ext_commercial':
                    return 'ID commercial ' . self::$origines[$origine];

                case 'ext_propale':
                    return 'Données devis ' . self::$origines[$origine];

                case 'ext_client':
                    return 'Données client ' . self::$origines[$origine];

                case 'ext_contact':
                    return 'Données contact client  ' . self::$origines[$origine];

                case 'ext_commercial':
                    return 'Données commercial ' . self::$origines[$origine];
            }
        }
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $ext_origine = $this->getData('ext_origine');

        if ($ext_origine) {
            $html = '';
            $html .= '<bOrigine : </b>' . $this->displayData('ext_origine', 'default', false);
        } else {
            return parent::renderHeaderExtraLeft();
        }
    }

    public function renderBimpPropalData()
    {
        $html = '';

        $id_propale = $this->getData('id_bimp_propale');
        $propale_data = $this->getData('bimp_propale');

        $html .= '<h3 style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #ccc">';
        $html .= BimpRender::renderIcon('fas_file-invoice', 'iconLeft') . 'Devis';
        if (isset($propale_data['ref']['value']) && $propale_data['ref']['value']) {
            $html .= ' ' . $propale_data['ref']['value'];
        }
        $html .= '</h3>';
        $html .= '<table cellpadding="5">';
        $html .= '<tbody">';
        $html .= '<tr>';
        $html .= '<td style="text-align: right; font-weight: bold; padding-right: 15px">ID BIMP :</td>';
        if ($id_propale) {
            $html .= '<td>' . $id_propale . '</td>';
        } else {
            $html .= '<td><span class="error">Non spécifié</span></td>';
        }
        $html .= '</tr>';

        if (!empty($propale_data)) {
            foreach ($propale_data as $data_name => $data) {
                $value = BimpTools::getArrayValueFromPath($data, 'value', '');
                if ((string) $value) {
                    if (in_array($data_name, array('total_ht', 'total_ttc'))) {
                        $value = BimpTools::displayMoneyValue($value, 'EUR');
                    }
                    $html .= '<tr>';
                    $html .= '<td style="text-align: right; font-weight: bold; padding-right: 15px">' . BimpTools::getArrayValueFromPath($data, 'label', $data_name) . ' :</td>';
                    $html .= '<td>' . $value . '</td>';
                    $html .= '</tr>';
                }
            }
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="2">' . BimpRender::renderAlerts('Données devis BIMP absentes') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        $title = BimpRender::renderIcon('fas_file-invoice', 'iconLeft') . 'Infos devis BIMP';
        return BimpRender::renderPanel($title, $html, '', array(
                    'type' => 'secondary'
        ));
    }

    public function renderBimpClientData()
    {
        $html = '';

        $id_client = $this->getData('id_bimp_client');
        $id_contact = $this->getData('id_bimp_contact');
        $client_data = $this->getData('bimp_client');
        $contact_data = $this->getData('bimp_contact');

        // Client: 
        $html .= '<h3 style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #ccc">';
        $html .= BimpRender::renderIcon('fas_user-circle', 'iconLeft') . 'Client';
        if (isset($client_data['ref']['value']) && $client_data['ref']['value']) {
            $html .= ' ' . $client_data['ref']['value'];
        }
        if (isset($client_data['nom']['value']) && $client_data['nom']['value']) {
            $html .= ' ' . $client_data['nom']['value'];
        }
        $html .= '</h3>';
        $html .= '<table cellpadding="5">';
        $html .= '<tbody">';
        $html .= '<tr>';
        $html .= '<td style="text-align: right; font-weight: bold; padding-right: 15px">ID BIMP :</td>';
        if ($id_client) {
            $html .= '<td>' . $id_client . '</td>';
        } else {
            $html .= '<td><span class="error">Non spécifié</span></td>';
        }
        $html .= '</tr>';

        if (!empty($client_data)) {
            foreach ($client_data as $data_name => $data) {
                if (in_array($data_name, array('ref', 'nom', 'address', 'zip', 'town', 'pays', 'phone', 'email'))) {
                    continue;
                }

                $value = BimpTools::getArrayValueFromPath($data, 'value', '');

                if ((string) $value) {
                    $html .= '<tr>';
                    $html .= '<td style="text-align: right; font-weight: bold; padding-right: 15px">' . BimpTools::getArrayValueFromPath($data, 'label', $data_name) . ' :</td>';
                    $html .= '<td>' . $value . '</td>';
                    $html .= '</tr>';
                }
            }
            $address = BimpTools::getArrayValueFromPath($client_data, 'address/value', '');
            $zip = BimpTools::getArrayValueFromPath($client_data, 'zip/value', '');
            $town = BimpTools::getArrayValueFromPath($client_data, 'town/value', '');
            $pays = BimpTools::getArrayValueFromPath($client_data, 'pays/value', '');

            $full_address = BimpTools::displayAddress($address, $zip, $town, '', $pays, true, true);
            if ($full_address) {
                $html .= '<tr>';
                $html .= '<td style="text-align: right; font-weight: bold; padding-right: 15px">Adresse :</td>';
                $html .= '<td>' . $full_address . '</td>';
                $html .= '</tr>';
            }

            $infos_contact = '';
            $phone = BimpTools::getArrayValueFromPath($client_data, 'phone/value', '');
            $email = BimpTools::getArrayValueFromPath($client_data, 'email/value', '');
            if ($phone) {
                $infos_contact .= ($infos_contact ? '<br/>' : '') . BimpRender::renderIcon('fas_phone', 'iconLeft') . $phone;
            }
            if ($email) {
                $infos_contact .= ($infos_contact ? '<br/>' : '') . BimpRender::renderIcon('fas_at', 'iconLeft') . $email;
            }
            if ($infos_contact) {
                $html .= '<tr>';
                $html .= '<td style="text-align: right; font-weight: bold; padding-right: 15px">Infos contact :</td>';
                $html .= '<td>' . $infos_contact . '</td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="2">' . BimpRender::renderAlerts('Données client BIMP absentes') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        // Contact: 
        $html .= '<h3 style="margin-top: 20px; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #ccc">';
        $html .= BimpRender::renderIcon('far_id-card', 'iconLeft') . 'Contact client';
        if (isset($contact_data['civility']['value']) && $contact_data['civility']['value']) {
            $html .= ' ' . $contact_data['civility']['value'];
        }
        if (isset($contact_data['firstname']['value']) && $contact_data['firstname']['value']) {
            $html .= ' ' . $contact_data['firstname']['value'];
        }
        if (isset($contact_data['lastname']['value']) && $contact_data['lastname']['value']) {
            $html .= ' ' . $contact_data['lastname']['value'];
        }
        $html .= '</h3>';

        $html .= '<table cellpadding="5">';
        $html .= '<tbody">';
        $html .= '<tr>';
        $html .= '<td style="text-align: right; font-weight: bold; padding-right: 15px">ID BIMP :</td>';
        if ($id_contact) {
            $html .= '<td>' . $id_contact . '</td>';
        } else {
            $html .= '<td><span class="error">Non spécifié</span></td>';
        }
        $html .= '</tr>';

        if (!empty($contact_data)) {
            foreach ($contact_data as $data_name => $data) {
                if (in_array($data_name, array('civility', 'firstname', 'lastname', 'address', 'zip', 'town', 'pays', 'phone_perso', 'phone_pro', 'phone_mobile', 'email'))) {
                    continue;
                }

                $value = BimpTools::getArrayValueFromPath($data, 'value', '');

                if ((string) $value) {
                    $html .= '<tr>';
                    $html .= '<td style="text-align: right; font-weight: bold; padding-right: 15px">' . BimpTools::getArrayValueFromPath($data, 'label', $data_name) . ' :</td>';
                    $html .= '<td>' . $value . '</td>';
                    $html .= '</tr>';
                }
            }
            $address = BimpTools::getArrayValueFromPath($contact_data, 'address/value', '');
            $zip = BimpTools::getArrayValueFromPath($contact_data, 'zip/value', '');
            $town = BimpTools::getArrayValueFromPath($contact_data, 'town/value', '');
            $pays = BimpTools::getArrayValueFromPath($contact_data, 'pays/value', '');

            $full_address = BimpTools::displayAddress($address, $zip, $town, '', $pays, true, true);
            if ($full_address) {
                $html .= '<tr>';
                $html .= '<td style="text-align: right; font-weight: bold; padding-right: 15px">Adresse :</td>';
                $html .= '<td>' . $full_address . '</td>';
                $html .= '</tr>';
            }

            $infos_contact = '';
            $phone_pro = BimpTools::getArrayValueFromPath($contact_data, 'phone_pro/value', '');
            $phone_perso = BimpTools::getArrayValueFromPath($contact_data, 'phone_perso/value', '');
            $phone_mobile = BimpTools::getArrayValueFromPath($contact_data, 'phone_mobile/value', '');
            $email = BimpTools::getArrayValueFromPath($contact_data, 'email/value', '');
            if ($phone_pro) {
                $infos_contact .= ($infos_contact ? '<br/>' : '') . BimpRender::renderIcon('fas_phone', 'iconLeft') . '(pro) ' . $phone_pro;
            }
            if ($phone_perso) {
                $infos_contact .= ($infos_contact ? '<br/>' : '') . BimpRender::renderIcon('fas_phone', 'iconLeft') . '(perso) ' . $phone_perso;
            }
            if ($phone_mobile) {
                $infos_contact .= ($infos_contact ? '<br/>' : '') . BimpRender::renderIcon('far_mobile', 'iconLeft') . $phone_mobile;
            }
            if ($email) {
                $infos_contact .= ($infos_contact ? '<br/>' : '') . BimpRender::renderIcon('fas_at', 'iconLeft') . $email;
            }
            if ($infos_contact) {
                $html .= '<tr>';
                $html .= '<td style="text-align: right; font-weight: bold; padding-right: 15px">Infos contact :</td>';
                $html .= '<td>' . $infos_contact . '</td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="2">' . BimpRender::renderAlerts('Données contact client BIMP absentes') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        $title = BimpRender::renderIcon('fas_user-circle', 'iconLeft') . 'Infos client BIMP';
        return BimpRender::renderPanel($title, $html, '', array(
                    'type' => 'secondary'
        ));
    }

    // Traitements: 

    public function generateDevis($data)
    {
        if ($this->getData('ext_origine')) {
            $errors = array();
            return $errors;
        } else {
            return parent::generateDevis($data);
        }
    }

    // Méthodes statiques: 

    public static function createFromBimpPropale($data, &$errors = array(), &$warnings = array())
    {
        $errors = array();

        if (!isset($data['propale'])) {
            $errors = 'Données du devis BIMP absentes';
        }

        if (!isset($data['propale_lines'])) {
            $errors = 'Lignes du devis BIMP absentes';
        }

        if (!isset($data['client'])) {
            $errors[] = 'Données du client BIMP absentes';
        }

        if (count($errors)) {
            return null;
        }

        $propale = $data['propale'];
        $lines = $data['propale_lines'];
        $client = $data['client'];
        $extra_data = (isset($data['extra_data']) ? $data['extra_data'] : array());
        $contact = (isset($data['contact']) ? $data['contact'] : array());
        $commercial = (isset($data['commercial']) ? $data['commercial'] : array());

        $id_propale = BimpTools::getArrayValueFromPath($propale, 'id', 0);
        if (!$id_propale) {
            $errors[] = 'ID du devis BIMP absent';
        }

        $id_client = BimpTools::getArrayValueFromPath($client, 'id', 0);
        if (!$id_client) {
            $errors[] = 'ID du client BIMP absent';
        }

        if (count($errors)) {
            return null;
        }
        $ref_propale = BimpTools::getArrayValueFromPath($propale, 'ref', '#' . $id_propale);

        $df_data = array(
            'label'              => 'Devis BIMP ' . $ref_propale,
            'id_bimp_propale'    => $id_propale,
            'id_bimp_client'     => $id_client,
            'id_bimp_contact'    => BimpTools::getArrayValueFromPath($contact, 'id', 0),
            'id_bimp_commercial' => BimpTools::getArrayValueFromPath($commercial, 'id', 0),
            'bimp_propale'       => BimpTools::getArrayValueFromPath($propale, 'data', array()),
            'bimp_client'        => BimpTools::getArrayValueFromPath($client, 'data', array()),
            'bimp_contact'       => BimpTools::getArrayValueFromPath($contact, 'data', array()),
            'bimp_commercial'    => BimpTools::getArrayValueFromPath($commercial, 'data', array())
        );

        if (isset($extra_data['duration'])) {
            $df_data['duration'] = $extra_data['duration'];
        }
        if (isset($extra_data['periodicity'])) {
            $df_data['periodicity'] = $extra_data['periodicity'];
        } else {
            $df_data['periodicity'] = 'none';
        }
        if (isset($extra_data['mode_calcul'])) {
            $df_data['mode_calcul'] = $extra_data['mode_calcul'];
        }

        $demande = BimpObject::createBimpObject('bimpfinancement', 'BF_Demande', $df_data, false, $errors, $warnings);

        if (!BimpObject::objectLoaded($demande)) {
            return null;
        }

        if (!empty($lines)) {
            BimpObject::loadClass('bimpfinancement', 'BF_Line');
            foreach ($lines as $line) {
                $line_data = array();

                switch ((int) BimpTools::getArrayValueFromPath($line, 'type', BF_Line::TYPE_FREE)) {
                    case BF_Line::TYPE_FREE:
                        $line_data = array(
                            'id_demande'           => $demande->id,
                            'type'                 => BF_Line::TYPE_FREE,
                            'label'                => BimpTools::getArrayValueFromPath($line, 'label', ''),
                            'qty'                  => (float) BimpTools::getArrayValueFromPath($line, 'qty', 0),
                            'pu_ht'                => (float) BimpTools::getArrayValueFromPath($line, 'pu_ht', 0),
                            'tva_tx'               => (float) BimpTools::getArrayValueFromPath($line, 'tva_tx', 0),
                            'remise'               => (float) BimpTools::getArrayValueFromPath($line, 'remise', 0),
                            'pa_ht'                => (float) BimpTools::getArrayValueFromPath($line, 'pa_ht', 0),
                            'id_bimp_propale_line' => (int) BimpTools::getArrayValueFromPath($line, 'id', 0),
                            'serialisable'         => (int) BimpTools::getArrayValueFromPath($line, 'serialisable', 0)
                        );
                        break;

                    case BF_Line::TYPE_TEXT:
                        $line_data = array(
                            'id_demande'           => $demande->id,
                            'type'                 => BF_Line::TYPE_TEXT,
                            'label'                => BimpTools::getArrayValueFromPath($line, 'label', ''),
                            'id_bimp_propale_line' => (int) BimpTools::getArrayValueFromPath($line, 'id', 0)
                        );
                        break;
                }

                $line_errors = array();
                BimpObject::createBimpObject('bimpfinancement', 'BF_Line', $line_data, false, $line_errors);

                if (count($line_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout de la ligne du devis #' . BimpTools::getArrayValueFromPath($line, 'id', '(ID inconnu)'));
                }
            }
        }

        return $demande;
    }
}
