<?php

class savFormController extends BimpPublicController
{

    public static $user_client_required = false;

    public function renderHtml()
    {
        global $userClient;

        $html = '';

        $html .= '<div class="container bic_container bic_main_panel">';
        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';

        $html .= '<div id="new_sav_form" class="bimp_public_form">';

        $html .= '<h2>Prendre un rendez-vous avec un de nos centre SAV</h2>';

        $html .= '<div class="form_section" id="client_email" style="text-align: center">';
        $html .= '<div class="form_section_title">Votre adresse e-mail de contact</div>';
        $html .= '<div style="display: inline-block; margin: auto; width: 100%; max-width: 460px;">';

        if (BimpObject::objectLoaded($userClient)) {
            $html .= '<span style="font-size: 14px">' . $userClient->getData('email') . '</p>';
        } else {
            $html .= BimpInput::renderInput('text', 'client_email', '', array('extra_class' => 'required'));

            $html .= '<p class="inputHelp">';
            $html .= 'Si vous disposez déjà d\'un accès à l\'espace client BIMP, veuillez vous ';
            $html .= '<a href="' . DOL_URL_ROOT . '/bimpinterfaceclient/client.php?back=savForm">authentifier</a>';
            $html .= ' pour simplifier la prise de rendez-vous.';
            $html .= '</p>';
        }

        $html .= '</div>';
        $html .= '</div>';


        if (BimpObject::objectLoaded($userClient)) {
            $html .= $this->renderCustomerInfosForm('');
            $html .= $this->renderEquipmentForm();
            $html .= $this->renderRdvForm();
        } else {
            $html .= '<div id="client_email_ajax_result" style="display: none"></div>';
        }

        if (BimpObject::objectLoaded($userClient)) {
            $html .= '<div style="margin-top: 15px; text-align: center">';
            $html .= '<p><a href="' . DOL_URL_ROOT . '/bimpinterfaceclient/client.php">Retour à l\'espace client</a></p>';
            $html .= '</div>';
        }

        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderCustomerInfosForm($email, &$errors = array(), &$warnings = array())
    {
        $html = '';

        global $userClient;
        $client = null;
        $contact = null;
        $contact_array = array();

        $html .= '<div id="customer_infos" class="form_section" data-client_email="' . $email . '">';

        if (!BimpObject::objectLoaded($userClient)) {
            // Recherche d'un compte utilisateur client: 
            $userClient = BimpCache::findBimpObjectInstance('bimpinterfaceclient', 'BIC_UserClient', array(
                        'email' => $email
            ));

            if (BimpObject::objectLoaded($userClient)) {
                $html .= '<h3 style="text-align: center; font-weight: bold" class="info">';
                $html .= 'Un compte utilisateur existe déjà pour l\'adresse e-mail "' . $email . '".<br/>';
                $html .= '</h3>';
                $html .= '<h4 style="text-align: center; font-weight: bold" class="info">';
                $html .= 'Veuillez vous <a href="' . DOL_URL_ROOT . '/bimpinterfaceclient/client.php?back=savForm">authentifier</a>';
                $html .= '</h4>';
                $html .= '</div>';

                return $html;
            } else {
                // Recherche d'un contact client: 
                $contact = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Contact', array(
                            'email' => $email
                ));

                if (BimpObject::objectLoaded($contact)) {
                    $client = $contact->getParentInstance();
                } else {
                    // Recheche d'un client: 
                    $client = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Societe', array(
                                'email' => $email
                    ));
                }

                if (BimpObject::objectLoaded($client)) {
                    // Un client existe - Création d'un compte utilisateur (Voir avec Tommy si protection suppl. nécessaires) 

                    $has_admin = BimpCache::getBdb()->getCount('bic_user', 'id_client = ' . (int) $client->id . ' AND role = 1');
                    $uc_errors = array();
                    $uc_warnings = array();

                    $post_tmp = $_POST;
                    $_POST = array(
                        'send_mail' => 1
                    );

                    $newUserClient = BimpObject::createBimpObject('bimpinterfaceclient', 'BIC_UserClient', array(
                                'id_client'      => (int) $client->id,
                                'id_contact'     => (BimpObject::objectLoaded($contact) ? (int) $contact->id : 0),
                                'email'          => $email,
                                'role'           => (!$has_admin ? 1 : 0),
                                'renew_required' => 1
                                    ), true, $uc_errors, $uc_warnings);

                    $_POST = $post_tmp;

                    if (count($uc_warnings)) {
                        $warnings[] = BimpTools::getMsgFromArray($uc_warnings, 'Erreurs suite à la création de votre compte utilisateur');
                    }

                    if (!count($uc_errors)) {
                        // E-mail de notification à l'adresse e-mail principale de client: 
                        if ($email != $client->getData('email')) {
                            // todo...
                        }
                    }

                    if (BimpObject::objectLoaded($newUserClient)) {
                        $html .= '<p class="info" style="text-align: center; font-weight: bold">';
                        $html .= 'Un compte client au nom de "' . $client->getName() . '" existe déjà pour l\'adresse e-mail "' . $email . '"';
                        if (BimpObject::objectLoaded($contact)) {
                            $html .= ' (Contact: ' . $contact->getName() . ')';
                        }
                        $html .= '.<br/>';
                        $html .= 'Nous vous avons ouvert un accès à votre espace client personnalisé BIMP.<br/>';
                        $html .= 'Un e-mail contenant votre mot de passe vous a été envoyé.<br/>';
                        $html .= 'Veuillez consulter votre messagerie, puis ';
                        $html .= '<a href="' . DOL_URL_ROOT . '/bimpinterfaceclient/client.php?back=savForm">cliquez ici</a>';
                        $html .= ' pour vous authentifier.';
                        $html .= '</p>';

                        $html .= '<p class="danger" style="text-align: center; font-weight: bold">';
                        $html .= 'Si vous ne souhaitez pas créer un rendez-vous SAV avec ce compte client, veuillez utiliser une autre adresse e-mail';
                        $html .= '</p>';

                        $html .= '</div>';
                        return $html;
                    }
                }
            }
        } else {
            $client = $userClient->getParentInstance();
            if ((int) $userClient->getData('id_contact')) {
                $contact = $userClient->getChildObject('contact');
            }
        }

        // Formulaire infos client:
        $html .= '<div class="form_section_title">Vos informations</div>';


        if (BimpObject::objectLoaded($client)) {
            $html .= '<input type="hidden" name="id_client" value="' . (int) $client->id . '"/>';
            $html .= '<div class="row">';
            $html .= '<div class="col-xs-12">';
            $html .= '<label>Compte client</label><br/>';
            $alias = $client->getData('name_alias');
            $html .= '<p>' . $client->getRef() . ' - ' . $client->getName() . ($alias ? ' (' . $alias . ')' : '') . '</p>';
            $html .= '</div>';
            $html .= '</div>';

            if (!in_array((int) $client->getData('fk_typent'), array(0, 8))) {
                $html .= '<div class="row">';
                // Type tiers
                $html .= '<div class="col-xs-12 col-md-4">';
                $html .= '<label>Type</label><br/>';
                $html .= $client->displayData('fk_typent', 'default', false, false);
                $html .= '</div>';
                $html .= '</div>';

                // N° SIRET: 
                $html .= '<div class="col-xs-12 col-md-5" style="display: none">';
                $html .= '<label>SIRET</label><br/>';
                $html .= $client->getData('siret');
                $html .= '</div>';
                $html .= '</div>';
            }

            $contact_array = $client->getContactsArray(true, 'Nouveau contact');
        } else {
            $html .= '<div class="row">';

            // Type tiers
            $html .= '<div class="col-xs-12 col-md-3">';
            $html .= '<label>Type</label><br/>';
            $html .= BimpInput::renderInput('select', 'client_type', 8, array(
                        'options' => BimpCache::getTypesSocietesArray(false, true)
            ));
            $html .= '</div>';

            // Nom société: 
            $html .= '<div class="col-xs-12 col-md-5" style="display: none">';
            $html .= '<label>Nom société</label><sup>*</sup><br/>';
            $html .= BimpInput::renderInput('text', 'client_nom_societe', '');
            $html .= '</div>';

            // N° SIRET: 
            $html .= '<div class="col-xs-12 col-md-4" style="display: none">';
            $html .= '<label>SIRET</label><br/>';
            $html .= BimpInput::renderInput('text', 'client_siret', '');
            $html .= '</div>';

            $html .= '</div>';
        }

        $civility = '';
        $firstname = '';
        $lastname = '';
        $tel_pro = '';
        $tel_perso = '';
        $tel_mobile = '';
        $address = '';
        $zip = '';
        $town = '';
        $fk_country = 1;


        if (BimpObject::objectLoaded($contact)) {
            $civility = $contact->getData('civility');
            $firstname = $contact->getData('firstname');
            $lastname = $contact->getData('lastname');
            $tel_pro = $contact->getData('phone');
            $tel_perso = $contact->getData('phone_perso');
            $tel_mobile = $contact->getData('phone_mobile');
            $address = $contact->getData('address');
            $zip = $contact->getData('zip');
            $town = $contact->getData('town');
            $fk_country = (int) $contact->getData('fk_pays');
        } elseif (BimpObject::objectLoaded($client)) {
            $address = $client->getData('address');
            $zip = $client->getData('zip');
            $town = $client->getData('town');
            $fk_country = (int) $client->getData('fk_pays');

            $tel = $client->getData('phone');

            if ($tel) {
                if (in_array((int) $client->getData('fk_typent'), array(0, 8))) {
                    if (preg_match('/^0[67].+$/', $tel)) {
                        $tel_mobile = $tel;
                    } else {
                        $tel_perso = $tel;
                    }
                } else {
                    $tel_pro = $tel;
                }
            }
        }

        // *****

        if (count($contact_array) > 1) {
            $html .= '<div class="row">';

            // Contact: 
            $html .= '<div class="col-xs-12 col-md-5">';
            $html .= '<label>Contact</label><br/>';
            $html .= BimpInput::renderInput('select', 'client_id_contact', (BimpObject::objectLoaded($contact) ? (int) $contact->id : 0), array(
                        'options' => $contact_array
            ));
            $html .= '</div>';

            $html .= '</div>';
        }

        $html .= '<div class="row">';
        // Titre: 
        $html .= '<div class="col-xs-4 col-md-2">';
        $html .= '<label>Titre de civilité</label><br/>';
        $html .= BimpInput::renderInput('select', 'client_civility', $civility, array(
                    'options' => BimpCache::getCivilitiesArray(true, true, false)
        ));
        $html .= '</div>';

        // Prénom:
        $html .= '<div class="col-xs-8 col-md-5 col-lg-4">';
        $html .= '<label>Prénom</label><sup>*</sup><br/>';
        $html .= BimpInput::renderInput('text', 'client_firstname', $firstname, array('extra_class' => 'required'));
        $html .= '</div>';

        // Nom: 
        $html .= '<div class="col-xs-12 col-md-5 col-lg-4">';
        $html .= '<label>Nom</label><sup>*</sup><br/>';
        $html .= BimpInput::renderInput('text', 'client_lastname', $lastname, array('extra_class' => 'required'));
        $html .= '</div>';

        $html .= '</div>';

        // *****

        $html .= '<div class="row">';
        // Adresse: 
        $html .= '<div class="col-xs-12 col-md-6">';
        $html .= '<label>Adresse</label><sup>*</sup><br/>';
        $html .= BimpInput::renderInput('textarea', 'client_address', $address, array(
                    'rows'        => 2,
                    'auto_expand' => 1,
                    'extra_class' => 'required'
        ));
        $html .= '</div>';

        $html .= '</div>';

        // *****

        $html .= '<div class="row">';
        // Code postal: 
        $html .= '<div class="col-xs-6 col-md-3 col-lg-2">';
        $html .= '<label>Code postal</label><sup>*</sup><br/>';
        $html .= BimpInput::renderInput('text', 'client_zip', $zip, array('extra_class' => 'required'));
        $html .= '</div>';

        // Ville: 
        $html .= '<div class="col-xs-6 col-md-5 col-lg-4">';
        $html .= '<label>Ville</label><sup>*</sup><br/>';
        $html .= BimpInput::renderInput('text', 'client_town', $town, array('extra_class' => 'required'));
        $html .= '</div>';

        // Pays: 
        $html .= '<div class="col-xs-6 col-md-5 col-lg-4">';
        $html .= '<label>Pays</label><sup>*</sup><br/>';
        $html .= BimpInput::renderInput('select', 'client_pays', $fk_country, array(
                    'options' => BimpCache::getCountriesArray(true, 'rowid', true)
        ));
        $html .= '</div>';

        $html .= '</div>';

        // *****

        $html .= '<div class="row">';
        // Tel mobile: 
        $html .= '<div class="col-xs-12 col-md-4">';
        $html .= '<label>Tél. mobile</label><br/>';
        $html .= BimpInput::renderInput('text', 'client_phone_mobile', $tel_mobile);
        $html .= '</div>';

        // Tel domicile: 
        $html .= '<div class="col-xs-12 col-md-4">';
        $html .= '<label>Tél. domicile</label><br/>';
        $html .= BimpInput::renderInput('text', 'client_phone_perso', $tel_perso);
        $html .= '</div>';

        // Tel pro: 
        $html .= '<div class="col-xs-12 col-md-4">';
        $html .= '<label>Tél. pro</label><br/>';
        $html .= BimpInput::renderInput('text', 'client_phone_pro', $tel_pro);
        $html .= '</div>';

        $html .= '</div>';

        // *****

        $html .= '<div class="row">';
        // Préf contact: 
        $html .= '<div class="col-xs-12 col-md-4">';
        $html .= '<label>Préférence de contact pour le suivi</label><br/>';
        $html .= BimpInput::renderInput('select', 'client_pref_contact', 3, array(
                    'options' => array(
                        3 => 'SMS + E-mail',
                        1 => 'E-mail',
                        2 => 'Téléphone'
                    )
        ));
        $html .= '</div>';

        $html .= '</div>';

        // *****

        $html .= '<div class="row">';
        $html .= '<p style="font-size: italic; font-size: 11px">';
        $html .= '<sup>*</sup> Champ obligatoire.<br/>';
        $html .= 'Veuillez saisir au moins un numéro de téléphone.';
        $html .= '</p>';
        $html .= '</div>';

        $html .= '<script type="text/javascript">';
        $html .= 'SavPublicForm.setCustomerInfosFormEvents();';
        $html .= '</script>';

        $html .= '</div>';

        if (!BimpObject::objectLoaded($userClient)) {
            $html .= $this->renderEquipmentForm();
            $html .= $this->renderRdvForm();
        }

        return $html;
    }

    public function renderEquipmentForm()
    {
        $html = '';

        $html .= '<div id="equipment_infos" class="form_section">';
        $html .= '<div class="form_section_title">Votre matériel</div>';

        // *****

        $html .= '<div class="row">';

        // Type matériel: 
        require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_Reservation.php';
        $types = array('' => '');

        foreach (GSX_Reservation::$products_codes as $code) {
            $types[$code] = $code;
        }

        $html .= '<div class="col-xs-6 col-md-3 col-lg-3">';
        $html .= '<label>Type de matériel</label><sup>*</sup><br/>';
        $html .= BimpInput::renderInput('select', 'eq_type', '', array(
                    'options'     => $types,
                    'extra_class' => 'required'
        ));
        $html .= '</div>';

        // Serial:
        $html .= '<div class="col-xs-6 col-md-5 col-lg-4">';
        $html .= '<label>N° de série</label><sup>*</sup><br/>';
        $html .= BimpInput::renderInput('text', 'eq_serial', '', array('extra_class' => 'required'));
        $html .= '</div>';

        // Lien sérial: 
        $html .= '<div class="col-xs-12 col-md-4 col-lg-5" style="padding-top: 25px">';
        $html .= '<a href="https://support.apple.com/fr-fr/HT204308" target="_blank" style="padding-top: 15px;">';
        $html .= 'Où trouver votre numéro de série?' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
        $html .= '</a>';
        $html .= '</div>';

        $html .= '</div>';

        // *****

        $html .= '<div class="row">';

        // Symptomes:
        $html .= '<div class="col-xs-8 col-md-6">';
        $html .= '<label>Description du problème</label><sup>*</sup><br/>';
        $html .= BimpInput::renderInput('textarea', 'eq_symptomes', '', array(
                    'rows'        => 3,
                    'auto_expand' => 1,
                    'maxlength'   => 250,
                    'extra_class' => 'required'
        ));
        $html .= '</div>';

        $html .= '</div>';

        // *****

        $html .= '<div class="row">';

        // Etat matériel: 
        $html .= '<div class="col-xs-12 col-md-4 col-lg-3">';
        $html .= '<label>Etat matériel</label><br/>';
        $html .= BimpInput::renderInput('select', 'eq_etat', '', array(
                    'options' => array(
                        1 => 'Neuf',
                        2 => 'Bon état général',
                        3 => 'Usagé'
                    )
        ));
        $html .= '</div>';

        // Système: 
        $html .= '<div class="col-xs-12 col-md-4 col-lg-3">';
        $html .= '<label>Système</label><br/>';
        $html .= BimpInput::renderInput('select', 'eq_system', 2, array('options' => BimpCache::getSystemsArray()));
        $html .= '</div>';

        $html .= '</div>';

        // *****

        $html .= '<div class="equipment_form_ajax_result"></div>';

        $html .= '<script type="text/javascript">';
        $html .= 'SavPublicForm.setEquipmentsFormEvents();';
        $html .= '</script>';

        $html .= '</div>';

        return $html;
    }

    public function renderRdvForm()
    {
        $html = '';

        $html .= '<div id="rdv_infos" class="form_section">';
        $html .= '<div class="form_section_title">Lieu et date de votre rendez-vous</div>';

        // *****

        $html .= '<div class="row">';

        // Lieu: 
        $centres = BimpCache::getCentresArray(true, 'label', true);
        asort($centres);

        $html .= '<div class="col-xs-12 col-md-4 col-lg-3">';
        $html .= '<label>Lieu</label><sup>*</sup><br/>';
        $html .= BimpInput::renderInput('select', 'sav_centre', '', array('options' => $centres, 'extra_class' => 'required'));
        $html .= '</div>';
        $html .= '</div>';

        // *****

        $html .= '<div id="rdv_form_ajax_result"></div>';

        $html .= '<script type="text/javascript">';
        $html .= 'SavPublicForm.setRdvFormEvents();';
        $html .= '</script>';

        $html .= '</div>';

        return $html;
    }

    // Ajax Process:

    public function ajaxProcessLoadPublicSavForm()
    {
        $html = '';
        $errors = array();
        $warnings = array();

        $email = BimpTools::getValue('client_email', '');
        if ($email) {
            if (!BimpValidate::isEmail($email)) {
                $errors[] = 'Veuillez saisir une adresse e-mail valide';
            } else {
                $html .= $this->renderCustomerInfosForm($email, $errors, $warnings);
            }
        } else {
            $errors[] = 'Veuillez saisir une adresse e-mail';
        }

        return array(
            'errors'     => $errors,
            'warnings'   => $warnings,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    public function ajaxProcessLoadClientContactInfo()
    {
        $civility = '';
        $firstname = '';
        $lastname = '';
        $tel_pro = '';
        $tel_perso = '';
        $tel_mobile = '';
        $address = '';
        $zip = '';
        $town = '';
        $fk_country = 1;

        $id_contact = (int) BimpTools::getValue('id_contact', 0);

        if ($id_contact) {
            $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);

            if (BimpObject::objectLoaded($contact)) {
                $civility = $contact->getData('civility');
                $firstname = $contact->getData('firstname');
                $lastname = $contact->getData('lastname');
                $tel_pro = $contact->getData('phone');
                $tel_perso = $contact->getData('phone_perso');
                $tel_mobile = $contact->getData('phone_mobile');
                $address = $contact->getData('address');
                $zip = $contact->getData('zip');
                $town = $contact->getData('town');
                $fk_country = (int) $contact->getData('fk_pays');
            }
        }

        return array(
            'civility'   => $civility,
            'firstname'  => $firstname,
            'lastname'   => $lastname,
            'tel_pro'    => $tel_pro,
            'tel_perso'  => $tel_perso,
            'tel_mobile' => $tel_mobile,
            'address'    => $address,
            'zip'        => $zip,
            'town'       => $town,
            'fk_pays'    => $fk_country,
            'errors'     => array(),
            'warnings'   => array(),
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    public function ajaxProcessPublicSavFormFetchAvailableSlots()
    {
        $html = '';
        $errors = array();
        $warnings = array();

        $code_product = BimpTools::getValue('code_product', '');
        $code_centre = BimpTools::getValue('code_centre', '');

        if ($code_product && $code_centre) {
            $slots = array();

            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_Reservation.php';

            $centres = BimpCache::getCentres();
            $timeZone = 'Europe/Paris';

            if (isset($centres[$code_centre])) {
                $result = GSX_Reservation::fetchAvailableSlots(897316, $centres[$code_centre]['shipTo'], $code_product, $errors);

                if (isset($result['response']['slots'])) {
                    $slots = $result['response']['slots'];
                }

                if (isset($result['response']['storeTimeZone'])) {
                    $timeZone = $result['response']['storeTimeZone'];
                }
            }

            $validate_enable = false;
            $force_validation = 0;

            if (!empty($slots)) {
                $days = array('' => '');
                $days_slots = array();
                $tz = new DateTimeZone($timeZone);

                foreach ($slots as $slot) {
                    $dt_start = new DateTime($slot['start'], $tz);
                    $dt_end = new DateTime($slot['end'], $tz);

                    $day = $dt_start->format('Y-m-d');
                    if (!isset($days[$day])) {
                        $days[$day] = BimpTools::getDayOfWeekLabel($dt_start->format('N')) . ' ' . $dt_start->format('d / m / Y');
                        $days_slots[$day] = array(
                            array('label' => '', 'value' => '')
                        );
                    }

                    $days_slots[$day][] = array(
                        'label' => 'De ' . $dt_start->format('H:i') . ' à ' . $dt_end->format('H:i'),
                        'value' => $slot['start']
                    );
                }

                $html .= '<div id="sav_slot" class="row">';

                $html .= '<div class="col-xs-6 col-md-4 col-lg-3">';
                $html .= '<label>Jour</label><sup>*</sup><br/>';
                $html .= BimpInput::renderInput('select', 'sav_day', '', array('options' => $days));
                $html .= '</div>';

                $html .= '<div class="col-xs-6 col-md-4 col-lg-3">';
                $html .= '<div id="sav_slots_container" style="opacity: 0">';

                $html .= '<label>Horaire</label><sup>*</sup><br/>';

                foreach ($days_slots as $day => $slots) {
                    $html .= '<div class="sav_slot_container" style="display: none">';
                    $html .= BimpInput::renderInput('select', 'sav_slot_' . $day, '', array('options' => $slots, 'extra_class' => 'slot_select'));
                    $html .= '</div>';
                }

                $html .= '</div>';
                $html .= '</div>';

                $html .= '<script type="text/javascript">';
                $html .= 'SavPublicForm.setRdvSlotEvents();';
                $html .= '</script>';

                $html .= '</div>';
            } else {
                $html .= '<div style="text-align: center; padding: 15px">';
                $html .= '<span class="info">Il n\'y a aucun créneau horaire disponible dans les 7 prochains jours pour ce centre BIMP</span><br/><br/>';
                $html .= '<p>Vous pouvez toutefois valider ce formulaire et déposer votre matériel quand vous le souhaitez sans rendez-vous</p>';
                $html .= '</div>';
                $validate_enable = true;
                $force_validation = 1;
            }

            $html .= '<div style="text-align: center; margin: 30px 0">';

            $html .= '<span id="savFormSubmit" class="btn btn-primary btn-large' . (!$validate_enable ? ' disabled' : '') . '" onclick="SavPublicForm.submit(' . $force_validation . ')">';
            $html .= BimpRender::renderIcon('fas_check', 'iconLeft') . 'VALIDER';
            $html .= '</span>';

            $html .= '</div>';

            $html .= '<div id="sav_form_submit_result" style="display: none"></div>';
        }

        return array(
            'errors'     => $errors,
            'warnings'   => $warnings,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    public function ajaxProcessSavFormSubmit()
    {
        $errors = array();
        $warnings = array();
        $html = '';
        $success_html = '';
        $slotNotAvailable = false;
        $forceValidate = false;
        $client = null;

        $id_client = (int) BimpTools::getValue('id_client', 0);

        if ($id_client) {
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);

            if (!BimpObject::objectLoaded($client)) {
                $errors[] = 'Le compte client indiqué semble ne plus exister';
            }
        }

        if (!count($errors)) {
            $inputs = array(
                'client_email'        => array('label' => 'Adresse e-mail de contact', 'required' => 1),
                'client_type'         => array('label' => 'Type', 'required' => 0),
                'client_nom_societe'  => array('label' => 'Nom société', 'required' => 0),
                'client_siret'        => array('label' => 'N° siret', 'required' => 0),
                'client_id_contact'   => array('label' => 'Contact', 'required' => 0),
                'client_civility'     => array('label' => 'Titre', 'required' => 0),
                'client_firstname'    => array('label' => 'Prénom', 'required' => 0),
                'client_lastname'     => array('label' => 'Nom', 'required' => 0),
                'client_address'      => array('label' => 'Adresse', 'required' => 1),
                'client_zip'          => array('label' => 'Code postal', 'required' => 1),
                'client_town'         => array('label' => 'Ville', 'required' => 0),
                'client_pays'         => array('label' => 'Pays', 'required' => 0),
                'client_phone_mobile' => array('label' => 'Tél. mobile', 'required' => 0),
                'client_phone_perso'  => array('label' => 'Tél. domicile', 'required' => 0),
                'client_phone_pro'    => array('label' => 'Tél. pro', 'required' => 0),
                'client_pref_contact' => array('label' => 'Préférence de contact', 'required' => 0),
                'eq_type'             => array('label' => 'Type matériel', 'required' => 1),
                'eq_serial'           => array('label' => 'N° de série', 'required' => 1),
                'eq_symptomes'        => array('label' => 'Description du problème', 'required' => 1),
                'eq_etat'             => array('label' => 'Etat du matériel', 'required' => 0),
                'eq_system'           => array('label' => 'Système', 'required' => 0),
                'sav_centre'          => array('label' => 'Lieu', 'required' => 1),
                'sav_day'             => array('label' => 'Jour', 'required' => 0),
                'sav_slot'            => array('label' => 'Horaire', 'required' => 1)
            );

            global $userClient;
            $data = array();

            if (BimpObject::objectLoaded($userClient)) {
                $inputs['client_email']['required'] = 0;
            }

            foreach ($inputs as $input_name => $input) {
                $data[$input_name] = BimpTools::getValue($input_name);

                if ($input['required'] && (is_null($data[$input_name]) || $data[$input_name] === '')) {
                    $errors[] = 'Champ obligatoire non renseigné: "' . $input['label'] . '"';
                }
            }

            if (!is_null($data['client_type'])) {
                if (!in_array((int) $data['client_type'], array(0, 8))) {
                    if (!$data['client_nom_societe']) {
                        $errors[] = 'Champ obligatoire non renseigné: "Nom de la société"';
                    }
                }
            }

            if (!$data['client_phone_mobile'] && !$data['client_phone_perso'] && !$data['client_phone_pro']) {
                $errors[] = 'Veuillez saisir au moins un numéro de téléphone';
            }

            if (!count($errors)) {
                $centres = BimpCache::getCentres();

                if (!isset($centres[$data['sav_centre']])) {
                    $errors[] = 'Veuillez sélectionner le centre SAV BIMP';
                } else {
                    $reservationId = '';
                    $centre = $centres[$data['sav_centre']];

                    if (!(int) BimpTools::getValue('force_validate', 0)) {

                        // Création de la réservation: 
                        require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_Reservation.php';

                        $countries = BimpCache::getCountriesArray();

                        $params = array(
                            'product'  => array(
                                'serialNumber'  => $data['eq_serial'],
                                'productCode'   => $data['eq_type'],
                                'issueReported' => substr($data['eq_symptomes'], 0, 250),
                            ),
                            'customer' => array(
                                'firstname'   => substr($data['client_firstname'], 0, 30),
                                'lastname'    => substr($data['client_lastname'], 0, 30),
                                'emailId'     => (BimpObject::objectLoaded($userClient) ? $userClient->getData('email') : $data['client_email']),
                                'phoneNumber' => ($data['client_phone_mobile'] ? $data['client_phone_mobile'] : ($data['client_phone_pro'] ? $data['client_phone_pro'] : $data['client_phone_perso'])),
                                'address'     => array(
                                    'addressLine1' => substr($data['client_address'], 0, 60),
                                    'postalCode'   => $data['client_zip'],
                                    'city'         => substr($data['town'], 0, 40),
                                    'country'      => (isset($countries[$data['client_pays']]) ? $countries[$data['client_pays']] : 'France')
                                )
                            )
                        );

                        $debug = '';
                        $req_errors = array();

                        $result = GSX_Reservation::createReservation(897316, $centre['shipTo'], $params, $req_errors, $debug);

                        if (!empty($result)) {
                            if (isset($result['response']['reservationId'])) {
                                $reservationId = $result['response']['reservationId'];
                            } else {
                                $forceValidate = true;

                                if (isset($result['faults'])) {
                                    foreach ($result['faults'] as $fault) {
                                        if ($fault['code'] === 'SYS.RSV.005') {
                                            $slotNotAvailable = true;
                                        } else {
                                            $req_errors[] = $fault['message'] . ' (' . $fault['code'] . ')';
                                        }
                                    }
                                }
                            }
                        } elseif (empty($req_errors)) {
                            $req_errors[] = 'Aucune réponse';
                        }

                        if (count($req_errors) && !$slotNotAvailable) {
                            BimpCore::addlog('RDV SAV CLIENT: échec création réservation GSX', Bimp_Log::BIMP_LOG_URGENT, 'bic', null, array(
                                'Erreurs' => $req_errors,
                                'ShipTo'  => $centre['shipTo'],
                                'Params'  => $params
                            ));
                        }
                    }

                    if (!$slotNotAvailable && !$forceValidate) {
                        BimpObject::loadClass('bimpequipment', 'Equipment');
                        BimpObject::loadClass('bimpsupport', 'BS_SAV');

                        $contact = null;
                        $equipment = null;
                        $sav = null;
                        $ac = null;

                        // Création client: 
                        if (!BimpObject::objectLoaded($client)) {
                            $isCompany = (!in_array((int) $data['client_type'], array(0, 8)));
                            if ($isCompany) {
                                $nom_client = $data['client_nom_societe'];
                            } else {
                                $nom_client = strtoupper($data['client_lastname']) . ' ' . BimpTools::ucfirst($data['client_firstname']);
                            }

                            $client_errors = array();
                            $client_warnings = array();

                            $client_data = array(
                                'nom'          => $nom_client,
                                'siret'        => ($isCompany ? $data['slient_siret'] : ''),
                                'address'      => $data['client_address'],
                                'zip'          => $data['client_zip'],
                                'town'         => $data['client_town'],
                                'fk_pays'      => $data['client_pays'],
                                'phone'        => ($isCompany && $data['client_phone_pro'] ? $data['client_phone_pro'] : $data['client_phone_mobile'] ? $data['client_phone_mobile'] : $data['client_phone_perso'] ? $data['client_phone_perso'] : $data['client_phone_pro']),
                                'email'        => (BimpObject::objectLoaded($userClient) ? $userClient->getData('email') : $data['client_email']),
                                'fk_typent'    => ($isCompany ? (int) $data['client_type'] : 8),
                                'marche'       => array(18),
                                'note_private' => 'Fiche client créée automatiquement suite à prise de rendez-vous SAV en ligne'
                            );

                            $client = BimpObject::createBimpObject('bimpcore', 'Bimp_Client', $client_data, true, $client_errors, $client_warnings);

                            if (!BimpObject::objectLoaded($client)) {
                                BimpCore::addlog('RDV SAV CLIENT: Echec création du client', Bimp_Log::BIMP_LOG_URGENT, 'bic', null, array(
                                    'Erreurs'  => $client_errors,
                                    'Warnings' => $client_warnings,
                                    'Données'  => $data
                                ));
                            }
                        }

                        if (BimpObject::objectLoaded($client)) {
                            // Création / maj contact: 
                            if ((int) $data['client_id_contact']) {
                                $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', (int) $data['client_id_contact']);

                                if (BimpObject::objectLoaded($contact)) {
                                    if ((int) $contact->getData('fk_soc') !== (int) $client->id) {
                                        $contact = null;
                                    }
                                }
                            }

                            if (!is_a($contact, 'Bimp_Contact')) {
                                $contact = BimpObject::getInstance('bimpcore', 'Bimp_Contact');
                            }

                            $contact_data = array(
                                'fk_soc'       => $client->id,
                                'civility'     => $data['client_civility'],
                                'lastname'     => $data['client_lastname'],
                                'firstname'    => $data['client_firstname'],
                                'address'      => $data['client_address'],
                                'zip'          => $data['client_zip'],
                                'town'         => $data['client_town'],
                                'fk_pays'      => $data['client_pays'],
                                'phone'        => $data['client_phone_pro'],
                                'phone_perso'  => $data['client_phone_perso'],
                                'phone_mobile' => $data['clinet_phone_mobile']
                            );

                            if (!$contact->getData('email')) {
                                $contact_data['email'] = (BimpObject::objectLoaded($userClient) ? $userClient->getData('email') : $data['client_email']);
                            }

                            $contact->validateArray($contact_data);

                            $contact_errors = array();
                            $contact_warnings = array();
                            if (BimpObject::objectLoaded($contact)) {
                                $contact_errors = $contact->update($contact_warnings, true);

                                if (count($contact_errors)) {
                                    BimpCore::addlog('RDV SAV CLIENT: échec création du contact', Bimp_Log::BIMP_LOG_URGENT, 'bic', null, array(
                                        'Erreurs'         => $contact_errors,
                                        'Warnings'        => $contact_warnings,
                                        'Données contact' => $contact_data
                                    ));
                                }
                            } else {
                                $contact_errors = $contact->create($contact_warnings, true);
                            }

                            // Création userClient:
                            if (!BimpObject::objectLoaded($userClient)) {
                                $post_tmp = $_POST;
                                $_POST = array(
                                    'send_mail' => 1
                                );

                                $uc_errors = array();
                                $uc_warnings = array();

                                $uc_data = array(
                                    'id_client'  => $client->id,
                                    'id_contact' => (BimpObject::objectLoaded($contact) ? $contact->id : 0),
                                    'email'      => $userClient->getData('email')
                                );

                                $userClient = BimpObject::createBimpObject('bimpinterfaceclient', 'BIC_UserClient', $uc_data, true, $uc_errors, $uc_warnings);
                                $_POST = $post_tmp;

                                if (!BimpObject::objectLoaded($userClient)) {
                                    BimpCore::addlog('RDV SAV CLIENT: Echec création utilisateur client', Bimp_Log::BIMP_LOG_URGENT, 'bic', null, array(
                                        'Erreurs'  => $uc_errors,
                                        'Warnings' => $uc_warnings,
                                        'Données'  => $uc_data
                                    ));
                                }
                            }

                            // Création équipement:
                            $equipment = Equipment::findBySerial($data['eq_serial']);

                            if (!BimpObject::objectLoaded($equipment)) {
                                $eq_errors = array();
                                $eq_warnings = array();

                                $equipment = BimpObject::createBimpObject('bimpequipment', 'Equipment', array(
                                            'product_label' => $data['eq_type'],
                                            'serial'        => $data['eq_serial']
                                                ), true, $eq_errors, $eq_warnings);

                                if (!BimpObject::objectLoaded($equipment)) {
                                    BimpCore::addlog('RDV SAV CLIENT: échec création équipement', Bimp_Log::BIMP_LOG_URGENT, 'bic', null, array(
                                        'Erreurs'  => $eq_errors,
                                        'Warnings' => $eq_warnings,
                                        'Serial'   => $data['eq_serial']
                                    ));
                                }
                            }

                            // Création SAV: 
                            if (BimpObject::objectLoaded($equipment)) {
                                $sav_errors = array();
                                $sav_warnings = array();

                                $sav_data = array(
                                    'status'         => BS_SAV::BS_SAV_RESERVED,
                                    'code_centre'    => $data['sav_centre'],
                                    'id_equipment'   => $equipment->id,
                                    'id_client'      => $client->id,
                                    'id_contact'     => (BimpObject::objectLoaded($contact) ? $contact->id : 0),
                                    'id_user_client' => (BimpObject::objectLoaded($userClient) ? $userClient->id : 0),
                                    'contact_pref'   => $data['client_pref_contact'],
                                    'etat_materiel'  => $data['eq_etat'],
                                    'symptomes'      => $data['eq_symptomes'],
                                    'system'         => $data['eq_system']
                                );

                                $sav = BimpObject::createBimpObject('bimpsupport', 'BS_SAV', $sav_data, true, $sav_errors, $sav_warnings);

                                if (!BimpObject::objectLoaded($sav)) {
                                    BimpCore::addlog('RDV SAV CLIENT: échec création SAV', Bimp_Log::BIMP_LOG_URGENT, 'bic', null, array(
                                        'Erreurs'     => $eq_errors,
                                        'Warnings'    => $eq_warnings,
                                        'Données SAV' => $sav_data
                                    ));
                                }
                            }
                        }

                        if ($reservationId) {
                            // Création ActionComm: 
                            global $db, $user;

                            $shipToUsers = Bimp_User::getUsersByShipto($centre['shipTo']);

                            if (empty($shipToUsers)) {
                                // Log. 
                            } else {
                                $usersAssigned = array();
                                foreach ($shipToUsers as $u) {
                                    $usersAssigned[] = array('id' => $u['id'], 'transparency' => 1, 'answer_status' => 1);
                                }

                                $note = '';

                                $note .= 'Rendez-vous créé en ligne par le client' . "\n";

                                if (BimpObject::objectLoaded($contact)) {
                                    $note .= 'Contact client: ' . $contact->getLink();
                                } else {
                                    $note .= 'Nom du contact client: ' . $data['client_firstname'] . ' ' . $data['client_lastname'] . "\n";

                                    if ($data['client_phone_pro']) {
                                        $note .= 'Tél. pro: ' . $data['client_phone_pro'] . "\n";
                                    }
                                    if ($data['client_phone_mobile']) {
                                        $note .= 'Tél. mobile: ' . $data['client_phone_mobile'] . "\n";
                                    }
                                    if ($data['client_phone_perso']) {
                                        $note .= 'Tél. domicile: ' . $data['client_phone_perso'] . "\n";
                                    }
                                }

                                if (BimpObject::objectLoaded($userClient)) {
                                    $note .= 'E-mail de contact: ' . $data['client_email'] . "\n";
                                }

                                if (!BimpObject::objectLoaded($client)) {
                                    $note .= 'La fiche client n\'a pas pu être créée (une erreur est survenue)' . "\n";
                                }

                                if (BimpObject::objectLoaded($sav)) {
                                    $note .= 'SAV: ' . $sav->getLink() . "\n";
                                } else {
                                    $systems = BimpCache::getSystemsArray();
                                    $note .= 'La fiche SAV n\'a pas pu être créée (une erreur est survenue)' . "\n";
                                    $note .= '<b>Infos SAV: </b>' . "\n";
                                    $note .= 'Symptomes: ' . $data['eq_symptomes'] . "\n";
                                    $note .= 'Etat matériel: ' . BS_SAV::$etats_materiel[(int) $data['eq_etat']]['label'] . "\n";
                                    $note .= 'Système: ' . $systems[(int) $data['eq_system']] . "\n";
                                }

                                if (BimpObject::objectLoaded($equipment)) {
                                    $note .= 'Equipement ' . $equipment->getLink() . "\n";
                                } else {
                                    $note .= 'L\'équipement n\'a pas pu être créé (une erreur est survenue)' . "\n";
                                    $note .= 'N° de série: ' . $data['eq_serial'] . "\n";
                                }

                                BimpTools::loadDolClass('comm/action', 'actioncomm', 'ActionComm');
                                $ac = new ActionComm($db);

                                $ac->type_id = 52;
                                $ac->label = 'Réservation SAV';
                                $ac->transparency = 1;

                                $dateBegin = new DateTime($data['sav_slot']);
                                $dateEnd = new DateTime($data['sav_slot']);
                                $dateEnd->add(new DateInterval('PT20M'));

                                $ac->datep = $db->jdate($dateBegin->format('Y-m-d H:i:s'));
                                $ac->datef = $db->jdate($dateEnd->format('Y-m-d H:i:s'));

                                $ac->userassigned = $usersAssigned;
                                $ac->userownerid = $usersAssigned[0]['id'];
                                $ac->array_options['options_resgsx'] = $reservationId;

                                if (BimpObject::objectLoaded($client)) {
                                    $ac->socid = $client->id;
                                }

                                $ac->note = $note . "\n";

                                $id_ac = $ac->create($user);

                                if (!$id_ac) {
                                    BimpCore::addlog('RDV SAV CLIENT: Echec création ActionComm', Bimp_Log::BIMP_LOG_URGENT, 'bic', null, array(
                                        'Date'                      => $dateBegin->format('d / m / Y H:i'),
                                        'Compte utilisateur client' => (BimpObject::objectLoaded($userClient) ? '#' . $userClient->getName() : 'Non créé'),
                                        'Client'                    => (BimpObject::objectLoaded($client) ? $client->getLink() : 'Non créé'),
                                        'Equipement'                => (BimpObject::objectLoaded($equipment) ? $equipment->getLink() : 'Non créé')
                                    ));
                                }
                            }
                        }
                    }
                }
            }
        }

        return array(
            'errors'             => $errors,
            'warnings'           => $warnings,
            'html'               => $html,
            'success_html'       => $success_html,
            'slot_not_available' => $slotNotAvailable,
            'force_validate'     => $forceValidate,
            'request_id'         => BimpTools::getValue('request_id', 0)
        );
    }
}
