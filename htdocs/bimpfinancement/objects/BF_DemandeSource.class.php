<?php

class BF_DemandeSource extends BimpObject
{
    /*
     * Données pièces origine: 
     *  array(
     *      'id' => ID,
     *      'ref' => Ref,
     *      'extra_data' => extra data : array('label' => '...', 'value' => VALUE)
     *      )
     *  
     * Données client: 
     *  array(
     *      'id' => ID,
     *      'ref' => Ref,
     *      'nom' => Nom,
     *      'is_company' => 1/0
     *      'extra_data' => array('label' => '...', 'value' => VALUE)
     *      'contact' => array(
     *                        'id' => ID,
     *                        'nom' => Nom (Fac.)
     *                        'address' => Adresse
     *                        'zip' => zip
     *                        'town' => town
     *                        'pays' => Pays (en lettres),
     *                        'tel' => Tel
     *                        'mobile' => Mobile
     *                        'email' => Email
     *                        )
     *       )
     * 
     *  Données Commercial: 
     *  array(
     *      'id' => ID,
     *      'nom' => Nom,
     *      'tel' => 'Tel.
     *      'email' => Email
     *       )
     */

    public static $types = array();
    public static $types_origines = array(
        'devis'    => 'Devis',
        'commande' => 'Commmande',
        'facture'  => 'Facture'
    );

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
        if ($this->getData('ext_origine')) {
            $id_api = $this->getIDApi();

            if (!$id_api) {
                $errors[] = 'ID API non configuré pour ' . $this->displayData('ext_origine', 'default', false);
            } else {
                BimpObject::loadClass('bimpapi', 'API_Api');
                return API_Api::getApiInstanceByID($id_api, $errors, $check_validity);
            }
        } else {
            $errors[] = 'Aucune origine externe';
        }

        return null;
    }

    public function getClientFullAddress($icon = false, $single_line = false)
    {
        $client_data = $this->getData('client_data');

        if (isset($client_data['contact'])) {
            $contact = $client_data['contact'];
            $address = '';
            if (isset($contact['nom']) && $contact['nom']) {
                $address .= $contact['nom'];
            }

            $address .= (($address) ? (($single_line) ? ' ' : '<br/>') : '') . BimpTools::getArrayValueFromPath($contact, 'address/value', '');
            $zip = BimpTools::getArrayValueFromPath($contact, 'zip/value', '');
            $town = BimpTools::getArrayValueFromPath($contact, 'town/value', '');
            $pays = BimpTools::getArrayValueFromPath($contact, 'pays/value', '');

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

            $tel = BimpTools::getArrayValueFromPath($contact, 'tel/value', '');
            $mobile = BimpTools::getArrayValueFromPath($contact, 'mobile/value', '');
            $email = BimpTools::getArrayValueFromPath($contact, 'email/value', '');

            if ($tel) {
                $infos_contact .= ($infos_contact ? ($single_line ? ' - ' : '<br/>') : '') . ($icons ? BimpRender::renderIcon('fas_phone', 'iconLeft') : '') . $tel;
            }
            if ($mobile) {
                $infos_contact .= ($infos_contact ? ($single_line ? ' - ' : '<br/>') : '') . ($icons ? BimpRender::renderIcon('fas_mobile', 'iconLeft') : '') . $mobile;
            }
            if ($email) {
                $infos_contact .= ($infos_contact ? ($single_line ? ' - ' : '<br/>') : '') . ($icons ? BimpRender::renderIcon('fas_at', 'iconLeft') : '') . $email;
            }
        }

        return $infos_contact;
    }

    public function getCommercialInfosContact($icons = true, $single_line = false)
    {
        $infos_contact = '';

        $comm_data = $this->getData('commercial_data');

        if (!empty($comm_data)) {
            $tel = BimpTools::getArrayValueFromPath($comm_data, 'tel/value', '');
            $email = BimpTools::getArrayValueFromPath($comm_data, 'email/value', '');
            if ($tel) {
                $infos_contact .= ($infos_contact ? ($single_line ? ' - ' : '<br/>') : '') . ($icons ? BimpRender::renderIcon('fas_phone', 'iconLeft') : '') . $tel;
            }
            if ($email) {
                $infos_contact .= ($infos_contact ? ($single_line ? ' - ' : '<br/>') : '') . ($icons ? BimpRender::renderIcon('fas_at', 'iconLeft') : '') . $email;
            }
        }

        return $infos_contact;
    }

    // Affichages: 

    public function displayName()
    {
        $type = $this->getData('type');
        if ($type && isset(self::$types[$type])) {
            return self::$types[$type]['label'];
        }

        return '';
    }

    public function displayOrigine()
    {
        $html = '';

        $type_origine = $this->getData('type_origine');

        if (isset(self::$types_origines[$type_origine])) {
            $html .= self::$types_origines[$type_origine];
        } else {
            $html .= $type_origine;
        }

        $origine = $this->getData('origine_data');

        if (isset($origine['id'])) {
            $html .= (($origine) ? ' ' : '') . '#' . $origine['id'];
        }

        if (isset($origine['ref'])) {
            $html .= (($origine) ? ' - ' : '') . $origine['ref'];
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

    // Rendus HTML: 

    public function renderOrigineData()
    {
        $html = '';

        $id_propale = $this->getData('id_ext_propale');
        $propale_data = $this->getData('ext_propale');

//        $html .= '<pre>';
//        $html .= print_r($propale_data, 1);
//        $html .= '</pre>';

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

    public function renderClientData()
    {
        $html = '';

        $id_client = $this->getData('id_ext_client');
        $id_contact = $this->getData('id_ext_contact');
        $client_data = $this->getData('ext_client');
        $contact_data = $this->getData('ext_contact');

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

            $full_address = $this->getExtClientFullAddress(true, true);
            if ($full_address) {
                $html .= '<tr>';
                $html .= '<td style="text-align: right; font-weight: bold; padding-right: 15px">Adresse :</td>';
                $html .= '<td>' . $full_address . '</td>';
                $html .= '</tr>';
            }

            $infos_contact = $this->getExtClientInfosContact();
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
}
