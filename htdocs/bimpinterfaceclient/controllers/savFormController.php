<?php

class savFormController extends BimpPublicController
{

    public static $user_client_required = false;

    public function renderHtml()
    {

        if ((int) BimpTools::getValue('cancel_rdv', 0)) {
            return $this->renderCancelRdvForm();
        }

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
            $html .= '<div style="text-align: center">';
            $html .= BimpInput::renderInput('text', 'client_email', '', array('extra_class' => 'required'));
            $html .= '<span class="btn btn-primary emailFormSubmit" onclick="SavPublicForm.emailSubmit();">';
            $html .= 'Valider';
            $html .= '</span>';
            $html .= '</div>';

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

            $html .= '<div class="col-xs-12 editContactNotif" style="' . (BimpObject::objectLoaded($contact) ? '' : 'display: none') . '">';
            $html .= '<p class="warning bold" style="padding-left: 15px; margin: 10px 0; border-left: 3px solid #E69900">';
            $html .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Attention: si vous modifiez les informations de contact si dessous, celles-ci seront mises à jour dans notre système pour le contact sélectionné.<br/>';
            $html .= 'Ceci pourrait éventuellement affecter l\'adresse de livraison pour des commandes en cours à livrer à ce contact.<br/>';
            $html .= 'Si vous souhaitez modifier l\'adresse ci-dessous tout en conservant l\'adresse de livraison du contact, veuillez de préférence sélectionner "Nouveau contact".';
            $html .= '</p>';
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

        $no_reservation_allowed = false; // En prévision... 

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

        $html .= '<div id="rdv_form_ajax_result">';
        $html .= '<p style="font-style: italic; font-size: 11px;">Sélectionnez le type de matériel et le lieu pour obtenir la liste des créneaux horaires disponibles</p>';
        $html .= '</div>';

        $html .= '<script type="text/javascript">';
        $html .= 'SavPublicForm.setRdvFormEvents();';
        $html .= '</script>';

        $html .= '<div style="display: none" id="SlotNotAvailableNotif">';
        $msg = 'Le créneau horaire sélectionné semble avoir été réservé par quelqu\'un d\'autre entre temps.<br/>';
        $msg .= 'Veuillez sélectionner un autre créneau horaire ou cliquez sur "Ouvrir le SAV sans Rendez-vous" (dans ce dernier cas, vous pourrez déposer votre matériel au centre SAV BIMP sélectionné quand vous le souhaitez).<br/>';
        $html .= BimpRender::renderAlerts($msg, 'warning');
        $html .= '</div>';

        $html .= '<div style="display: none" id="reservationErrorNotif">';
        $msg = 'Une erreur est survenue. Il n\'est pas possible de réserver le créneau horaire sélectionné pour le moment.<br/>';
        $msg .= 'Veuillez réessayer ultérieurement ou cliquer sur "Ouvrir le SAV sans Rendez-vous" (dans ce dernier cas, vous pourrez déposer votre matériel au centre SAV BIMP sélectionné quand vous le souhaitez).';
        $html .= BimpRender::renderAlerts($msg, 'warning');
        $html .= '</div>';

        $html .= '<div id="noReservationSubmit" style="margin-top: 20px; text-align: center;' . ($no_reservation_allowed ? '' : ' display: none') . '" data-never_hidden="' . ($no_reservation_allowed ? '1' : '0') . '">';
        $html .= '<span class="btn btn-default" onclick="SavPublicForm.submit($(this), 1, \'Le client ne souhaite pas de rendez-vous\');">';
        $html .= 'OUVRIR LE SAV SANS RENDEZ-VOUS';
        $html .= '</span>';
        $html .= '</div>';

        $html .= '</div>';

        $html .= '<div id="debug" style="display: none;"></div>';

        return $html;
    }

    public function renderCancelRdvForm()
    {
        $html = '';

        $html .= '<div class="container bic_container bic_main_panel">';
        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';

        $html .= '<div id="cancel_sav_form" class="bimp_public_form">';
        $html .= '<h2>Annulation de votre Rendez-vous</h2>';

        $id_sav = (int) BimpTools::getValue('s', 0);
        $ref_sav = BimpTools::getValue('r', '');
        $res_id = BimpTools::getValue('res', '');

        $errors = array();
        $sav = null;
        $ac = null;

        if (!$ref_sav) {
            $errors[] = 'Référence du SAV absente';
        } elseif (!$id_sav) {
            $errors[] = 'Identifiant du SAV absent';
        } else {
            $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);

            if (BimpObject::objectLoaded($sav)) {
                if ($sav->getData('ref') != $ref_sav) {
                    $errors[] = 'Réference du SAV invalide';
                } elseif ((int) $sav->getData('status') > 0) {
                    $html .= BimpRender::renderAlerts('Votre rendez-vous SAV ne peut pas être annulé car votre matériel semble déjà avoir été pris en charge par un technicien');
                }
            } else {
                $errors[] = 'Le SAV "' . $ref_sav . '" n\'existe plus';
            }
        }

        if (!count($errors)) {
            if ($res_id) {
                $id_ac = (int) BimpCache::getBdb()->getValue('actioncomm_extrafields', 'fk_object', 'resgsx = \'' . $res_id . '\'');

                if ($id_ac) {
                    $ac = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ActionComm', $id_ac);
                }
            }

            $code_centre = BimpTools::getValue('c', '');
            $date = BimpTools::getValue('d', '');

            if (!$code_centre && BimpObject::objectLoaded($sav)) {
                $code_centre = $sav->getData('code_centre');
            }

            if (!$date && $res_id) {
                $id_ac = (int) BimpCache::getBdb()->getValue('actioncomm_extrafields', 'fk_object', 'resgsx = \'' . $res_id . '\'');

                if ($id_ac) {
                    $ac = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ActionComm', $id_ac);

                    $date = $ac->getData('datep');
                }
            }

            $html .= '<div class="form_section" id="cancel_confirm" style="text-align: center">';

            if ($ref_sav) {
                $html .= 'Référence: <b>' . $ref_sav . '</b><br/><br/>';
            }

            if ($code_centre) {
                $centres = BimpCache::getCentres();

                if (isset($centres[$code_centre])) {
                    $html .= 'Lieu: <br/><b>' . $centres[$code_centre]['label'] . '</b><br/>';
                    $html .= $centres[$code_centre]['address'] . '<br/>';
                    $html .= $centres[$code_centre]['zip'] . ' ' . $centres[$code_centre]['town'] . '<br/><br/>';
                }
            }

            if ($date) {
                $html .= 'Date: <b>' . date('d / m / Y à H:i', strtotime($date)) . '</b><br/>';
            }

            $html .= '<div style="text-align: center; margin-top: 30px">';
            $html .= '<span class="btn btn-danger btn-large" onclick="SavPublicForm.cancelRDV($(this), ' . $id_sav . ', \'' . $ref_sav . '\', \'' . $res_id . '\')">';
            $html .= 'Confirmer l\'annulation de ce Rendez-vous SAV';
            $html .= '</span>';
            $html .= '</div>';

            $html .= '<div id="cancelSavAjaxResult" class="ajaxResultContainer" style="display: none"></div>';
            $html .= '</div>';
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }
        
        $html .= '<p style="text-align: center">';
        $html .= '<a href="'.DOL_URL_ROOT.'/bimpinterfaceclient/client.php?tab=sav">Retour à votre espace client</a>';
        $html .= '</p>';

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
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
            $centres = BimpCache::getCentres();

            $html .= '<div style="margin: 5px 0 15px 10px; padding-left: 10px; border-left: 2px solid #4D4C4C; font-weight: bold">';
            $html .= $centres[$code_centre]['address'] . '<br/>';
            $html .= $centres[$code_centre]['zip'] . ' ' . $centres[$code_centre]['town'];
            $html .= '</div>';

            $slots = array();

            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_Reservation.php';

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
//                
//                echo '<pre>';
//                print_r($slots);
//                exit;
                foreach ($slots as $slot) {
                    $dt_start = new DateTime($slot['start']);

                    $day = $dt_start->format('Y-m-d');
                    if (!isset($days[$day])) {
                        $days[$day] = BimpTools::getDayOfWeekLabel($dt_start->format('N')) . ' ' . $dt_start->format('d / m / Y');
                        $days_slots[$day] = array(
                            array('label' => '', 'value' => '')
                        );
                    }

                    $days_slots[$day][] = array(
                        'label' => 'De ' . date('d / m / Y H:i', strtotime($slot['start'])) . ' à ' . date('d / m / Y H:i', strtotime($slot['end'])),
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

            $html .= '<span id="savFormSubmit" class="btn btn-primary btn-large' . (!$validate_enable ? ' disabled' : '') . '" onclick="SavPublicForm.submit($(this), ' . ($force_validation ? '1, \'Aucun créneau horaire disponible\'' : '') . ')">';
            $html .= BimpRender::renderIcon('fas_check', 'iconLeft') . ($force_validation ? 'OUVRIR LE SAV SANS RENDEZ-VOUS' : 'VALIDER');
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
        $debug = '';
        $slotNotAvailable = false;
        $forceValidate = false;
        $forceValidateReason = '';
        $client = null;

        // CheckClient: 
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
                'sav_slot'            => array('label' => 'Horaire', 'required' => 0)
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

            if (!(int) BimpTools::getValue('force_validate', 0)) {
                if (!isset($data['sav_day']) || !$data['sav_day']) {
                    $errors[] = 'Veuillez sélectionner le jour du RDV';
                }

                if (!isset($data['sav_slot']) || !$data['sav_slot']) {
                    $errors[] = 'Veuillez sélectionner un créneau horaire';
                }
            }

            if (!count($errors)) {
                $centres = BimpCache::getCentres();

                if (!isset($centres[$data['sav_centre']])) {
                    $errors[] = 'Veuillez sélectionner le centre SAV BIMP';
                } else {
                    $reservationId = '';
                    $noRdvReason = '';
                    $centre = $centres[$data['sav_centre']];

                    if (!(int) BimpTools::getValue('force_validate', 0)) {
                        // Création de la réservation: 
                        require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_Reservation.php';

                        $countries = BimpCache::getCountriesArray();

                        $params = array(
                            'reservationDate' => $data['sav_slot'],
                            'product'         => array(
                                'serialNumber'  => $data['eq_serial'],
                                'productCode'   => $data['eq_type'],
                                'issueReported' => substr($data['eq_symptomes'], 0, 250),
                            ),
                            'customer'        => array(
                                'firstName'   => substr($data['client_firstname'], 0, 30),
                                'lastName'    => substr($data['client_lastname'], 0, 30),
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

                        $req_errors = array();

                        $result = GSX_Reservation::createReservation(897316, $centre['shipTo'], $params, $req_errors, $debug);
                        // ********** POUR TESTS **************************************************
//                        $result = array(
//                            'response' => array(
//                                'reservationId' => '123456789'
//                            )
//                        );
                        // ************************************************************************

                        if (!empty($result)) {
                            if (isset($result['response']['reservationId'])) {
                                $reservationId = $result['response']['reservationId'];
                            } else {
                                $forceValidate = true;

                                if (isset($result['faults'])) {
                                    foreach ($result['faults'] as $fault) {
                                        if ($fault['code'] === 'SYS.RSV.005') {
                                            $slotNotAvailable = true;
                                            $forceValidateReason = 'Créneau horaire sélectionné par le client indisponible';
                                            break;
                                        } else {
                                            $req_errors[] = $fault['message'] . ' (' . $fault['code'] . ')';
                                        }
                                    }

                                    if (!$forceValidateReason) {
                                        $forceValidateReason = 'Echec requête réservation sur GSX';
                                        if (count($req_errors)) {
                                            $forceValidateReason .= '<br/>' . BimpTools::getMsgFromArray($req_errors, 'Erreurs');
                                        }
                                    }
                                }
                            }
                        } elseif (empty($req_errors)) {
                            $forceValidate = true;
                            $forceValidateReason = 'Echec requête réservation (Les admin ERP ont été prévenus)';
                            $req_errors[] = 'Aucune réponse';
                        }

                        if (count($req_errors) && !$slotNotAvailable) {
                            $debug .= 'Echec reqête réservation <br/>';
                            $debug .= BimpTools::getMsgFromArray($req_errors, 'Erreurs');

                            BimpCore::addlog('RDV SAV CLIENT: échec création réservation GSX', Bimp_Log::BIMP_LOG_URGENT, 'bic', null, array(
                                'Erreurs' => $req_errors,
                                'ShipTo'  => $centre['shipTo'],
                                'Params'  => $params
                            ));
                        }
                    } else {
                        $noRdvReason = BimpTools::getValue('force_validate_reason', 'non spécifiée');
                        $debug .= 'Pas de réservation.<br/>Raison: ' . $noRdvReason;
                    }

                    if (!$slotNotAvailable && !$forceValidate) {
                        $dateBegin = null;
                        $dateEnd = null;

                        if (isset($data['sav_slot']) && (string) $data['sav_slot']) {
                            $dateBegin = new DateTime($data['sav_slot']);
                            $dateEnd = new DateTime($data['sav_slot']);
                            $dateEnd->add(new DateInterval('PT20M'));
                        }

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

                            $debug .= '<br/><br/><b>Création du client:</b>';

                            $client = BimpObject::createBimpObject('bimpcore', 'Bimp_Client', $client_data, true, $client_errors, $client_warnings);

                            if (!BimpObject::objectLoaded($client)) {
                                $debug .= BimpRender::renderAlerts($client_errors);
                                BimpCore::addlog('RDV SAV CLIENT: Echec création du client', Bimp_Log::BIMP_LOG_URGENT, 'bic', null, array(
                                    'Erreurs'  => $client_errors,
                                    'Warnings' => $client_warnings,
                                    'Données'  => $data
                                ));
                            } else {
                                $debug .= '<span class="success">OK</span>';
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
                                'phone_mobile' => $data['client_phone_mobile']
                            );

                            if (!$contact->getData('email')) {
                                $contact_data['email'] = (BimpObject::objectLoaded($userClient) ? $userClient->getData('email') : $data['client_email']);
                            }

                            $contact->validateArray($contact_data);

                            $contact_errors = array();
                            $contact_warnings = array();
                            if (BimpObject::objectLoaded($contact)) {
                                $debug .= '<br/><br/><b>Mise à jour du contact: </b>';
                                $contact_errors = $contact->update($contact_warnings, true);

                                if (count($contact_errors)) {
                                    $debug .= BimpRender::renderAlerts($contact_errors);

                                    BimpCore::addlog('RDV SAV CLIENT: échec mise à jour du contact', Bimp_Log::BIMP_LOG_URGENT, 'bic', $contact, array(
                                        'Erreurs'         => $contact_errors,
                                        'Warnings'        => $contact_warnings,
                                        'Données contact' => $contact_data
                                    ));
                                } else {
                                    $debug .= '<span class="success">OK</span>';
                                }
                            } else {
                                $debug .= '<br/><br/><b>Création du contact: </b>';
                                $contact_errors = $contact->create($contact_warnings, true);

                                if (count($contact_errors)) {
                                    $debug .= BimpRender::renderAlerts($contact_errors);

                                    BimpCore::addlog('RDV SAV CLIENT: échec création du contact', Bimp_Log::BIMP_LOG_URGENT, 'bic', null, array(
                                        'Erreurs'         => $contact_errors,
                                        'Warnings'        => $contact_warnings,
                                        'Données contact' => $contact_data
                                    ));
                                } else {
                                    $debug .= '<span class="success">OK</span>';
                                }
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
                                    'email'      => $data['client_email']
                                );

                                $debug .= '<br/><br/><b>Création du userClient: </b>';

                                $userClient = BimpObject::createBimpObject('bimpinterfaceclient', 'BIC_UserClient', $uc_data, true, $uc_errors, $uc_warnings);
                                $_POST = $post_tmp;

                                if (!BimpObject::objectLoaded($userClient)) {
                                    $debug .= BimpRender::renderAlerts($uc_errors);
                                    BimpCore::addlog('RDV SAV CLIENT: Echec création utilisateur client', Bimp_Log::BIMP_LOG_URGENT, 'bic', null, array(
                                        'Erreurs'  => $uc_errors,
                                        'Warnings' => $uc_warnings,
                                        'Données'  => $uc_data
                                    ));
                                } else {
                                    $debug .= '<span class="success">OK</span>';
                                }
                            }

                            // Création équipement:
                            $equipment = Equipment::findBySerial($data['eq_serial']);

                            if (!BimpObject::objectLoaded($equipment)) {
                                $eq_errors = array();
                                $eq_warnings = array();

                                $debug .= '<br/><br/><b>Création de l\'équipement: </b>';
                                $equipment = BimpObject::createBimpObject('bimpequipment', 'Equipment', array(
                                            'product_label' => $data['eq_type'],
                                            'serial'        => $data['eq_serial']
                                                ), true, $eq_errors, $eq_warnings);

                                if (!BimpObject::objectLoaded($equipment)) {
                                    $debug .= BimpRender::renderAlerts($eq_errors);

                                    BimpCore::addlog('RDV SAV CLIENT: échec création équipement', Bimp_Log::BIMP_LOG_URGENT, 'bic', null, array(
                                        'Erreurs'  => $eq_errors,
                                        'Warnings' => $eq_warnings,
                                        'Serial'   => $data['eq_serial']
                                    ));
                                } else {
                                    $debug .= '<span class="success">OK</span>';
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
                                    'system'         => $data['eq_system'],
                                    'pword_admin'    => '/',
                                    'resgsx'         => $reservationId
                                );

                                $debug .= '<br/><br/><b>Création du SAV: </b>';

                                $sav = BimpObject::createBimpObject('bimpsupport', 'BS_SAV', $sav_data, true, $sav_errors, $sav_warnings);

                                if (!BimpObject::objectLoaded($sav)) {
                                    $debug .= BimpRender::renderAlerts($sav_errors);

                                    BimpCore::addlog('RDV SAV CLIENT: échec création SAV', Bimp_Log::BIMP_LOG_URGENT, 'bic', null, array(
                                        'Erreurs'     => $sav_errors,
                                        'Warnings'    => $sav_warnings,
                                        'Données SAV' => $sav_data
                                    ));
                                } else {
                                    $debug .= '<span class="success">OK</span>';
                                    $msg = 'Création du SAV en ligne par le client';
                                    if (!is_null($dateBegin)) {
                                        $msg .= "\n" . 'Date du rendez-vous: ' . $dateBegin->format('d / m / Y à H:i');
                                        $msg .= "\n" . 'ID réservation GSX: ' . $reservationId;
                                    } else {
                                        $msg .= ' sans rendez-vous';
                                    }

                                    $debug .= '<br/><br/>Ajout note SAV: ';

                                    $note_errors = $sav->addNote($msg);

                                    if (count($note_errors)) {
                                        $debug .= BimpRender::renderAlerts($note_errors);
                                    } else {
                                        $debug .= '<span class="success">OK</span>';
                                    }
                                }
                            }
                        }

                        $id_ac = 0;
                        BimpObject::loadClass('bimpcore', 'Bimp_User');
                        $shipToUsers = Bimp_User::getUsersByShipto($centre['shipTo']);

                        if ($reservationId) {
                            // Création ActionComm: 
                            global $db, $user;

                            $usersAssigned = array();

                            if (!empty($shipToUsers)) {
                                foreach ($shipToUsers as $u) {
                                    $usersAssigned[] = array('id' => $u['id'], 'transparency' => 1, 'answer_status' => 1);
                                }
                            }

                            $note = '';

                            $note .= 'Rendez-vous créé en ligne par le client<br/>';

                            if (BimpObject::objectLoaded($contact)) {
                                $note .= 'Contact client: ' . $contact->getLink(array(), 'private') . '<br/>';
                            } else {
                                $note .= 'Nom du contact client: ' . $data['client_firstname'] . ' ' . $data['client_lastname'] . '<br/>';

                                if ($data['client_phone_pro']) {
                                    $note .= 'Tél. pro: ' . $data['client_phone_pro'] . '<br/>';
                                }
                                if ($data['client_phone_mobile']) {
                                    $note .= 'Tél. mobile: ' . $data['client_phone_mobile'] . '<br/>';
                                }
                                if ($data['client_phone_perso']) {
                                    $note .= 'Tél. domicile: ' . $data['client_phone_perso'] . '<br/>';
                                }
                            }

                            $note .= 'E-mail de contact: ';

                            if (BimpObject::objectLoaded($userClient)) {
                                $note .= $userClient->getData('email') . '<br/>';
                            } else {
                                $note .= $data['client_email'] . '<br/>';
                            }

                            if (!BimpObject::objectLoaded($client)) {
                                $note .= 'La fiche client n\'a pas pu être créée (une erreur est survenue)<br/>';
                            }

                            if (BimpObject::objectLoaded($sav)) {
                                $note .= 'SAV: ' . $sav->getLink(array(), 'private') . '<br/>';
                                $debug .= '<br/>Lien privé SAV: ' . $sav->getLink(array(), 'private') . '<br/>';
                            } else {
                                $systems = BimpCache::getSystemsArray();
                                $note .= 'La fiche SAV n\'a pas pu être créée (une erreur est survenue)<br/>';
                                $note .= '<b>Infos SAV: </b><br/>';
                                $note .= 'Symptomes: ' . $data['eq_symptomes'] . '<br/>';
                                $note .= 'Etat matériel: ' . BS_SAV::$etats_materiel[(int) $data['eq_etat']]['label'] . '<br/>';
                                $note .= 'Système: ' . $systems[(int) $data['eq_system']] . '<br/>';
                            }

                            if (BimpObject::objectLoaded($equipment)) {
                                $note .= 'Equipement ' . $equipment->getLink(array(), 'private') . '<br/>';
                            } else {
                                $note .= 'L\'équipement n\'a pas pu être créé (une erreur est survenue)<br/>';
                                $note .= 'N° de série: ' . $data['eq_serial'] . '<br/>';
                            }

                            BimpTools::loadDolClass('comm/action', 'actioncomm', 'ActionComm');
                            $ac = new ActionComm($db);

                            $ac->type_id = 52;
                            $ac->label = 'Réservation SAV';
                            $ac->transparency = 1;

                            $ac->datep = $db->jdate($dateBegin->format('Y-m-d H:i:s'));
                            $ac->datef = $db->jdate($dateEnd->format('Y-m-d H:i:s'));

                            $ac->userassigned = $usersAssigned;
                            $ac->userownerid = $usersAssigned[0]['id'];

                            if ($reservationId) {
                                $ac->array_options['options_resgsx'] = $reservationId;
                            }

                            if (BimpObject::objectLoaded($client)) {
                                $ac->socid = $client->id;
                            }

                            if (BimpObject::objectLoaded($contact)) {
                                $ac->contactid = $contact->id;
                            }

                            $ac->note = $note;

                            $debug .= '<br/><br/><b>Création ActionComm: </b>';

                            if (empty($usersAssigned)) {
                                BimpCore::addlog('RDV SAV CLIENT: impossible de créer RDV agenda - aucun utilisateur assigné au ShipTo "' . $centre['shipTo'] . '"', Bimp_Log::BIMP_LOG_URGENT, 'bic', $sav, array(
                                    'ID Réservation' => ($reservationId ? $reservationId : 'Aucun'),
                                    'Date'           => $dateBegin->format('d / m / Y H:i'),
                                    'Client'         => (BimpObject::objectLoaded($client) ? $client->getLink() : 'aucun'),
                                    'Note'           => $note
                                ));

                                $debug .= BimpRender::renderAlerts('Aucun user pour le shipTo ' . $centre['shipTo']);
                            } elseif ($reservationId) {
                                $id_ac = $ac->create($user);

                                if (!$id_ac) {
                                    $ac_errors = BimpTools::getErrorsFromDolObject($ac);

                                    $debug .= BimpRender::renderAlerts(BimpTools::getMsgFromArray($ac_errors, 'Echec'));
                                    BimpCore::addlog('RDV SAV CLIENT: Echec création ActionComm', Bimp_Log::BIMP_LOG_URGENT, 'bic', null, array(
                                        'Date'                      => $dateBegin->format('d / m / Y H:i'),
                                        'Compte utilisateur client' => (BimpObject::objectLoaded($userClient) ? '#' . $userClient->getName() : 'Non créé'),
                                        'Client'                    => (BimpObject::objectLoaded($client) ? $client->getLink() : 'Non créé'),
                                        'Equipement'                => (BimpObject::objectLoaded($equipment) ? $equipment->getLink() : 'Non créé'),
                                        'Erreurs'                   => $ac_errors
                                    ));
                                } else {
                                    $debug .= '<span class="success">OK</span>';
                                }
                            }
                        }

                        // Envoi e-mail users: 

                        if (!empty($shipToUsers)) {
                            $emails = '';
                            foreach ($shipToUsers as $u) {
                                if (isset($u['email']) && $u['email']) {
                                    $emails .= ($emails ? ',' : '') . $u['email'];
                                }
                            }

                            $emails = BimpTools::cleanEmailsStr($emails);
                            if ($emails) {
                                $msg = 'Bonjour,' . "\n\n";
                                $msg .= 'Un nouveau SAV a été créé en ligne par un client' . "\n\n";

                                if ($id_ac) {
                                    $msg .= '<a href="' . DOL_URL_ROOT . '/comm/action/card.php?id=' . $id_ac . '">Fiche événement du rendez-vous</a>' . "\n";
                                } else {
                                    $msg .= 'Ce SAV a été créé sans rendez-vous (Raison: ' . ($noRdvReason ? $noRdvReason : 'non spécifiée') . ')' . "\n";
                                }

                                if (!is_null($dateBegin)) {
                                    $msg .= 'Date: ' . $dateBegin->format('d / m / Y à H:i') . "\n";
                                }

                                $msg .= "\n";

                                if (BimpObject::objectLoaded($sav)) {
                                    $msg .= 'SAV: ' . $sav->getLink(array(), 'private') . "\n";
                                } else {
                                    $msg .= 'Le SAV n\'a pas pu être créé (une erreur est survenue)' . "\n";
                                    $msg .= '<b>Infos SAV: </b>' . "\n";
                                    $msg .= 'Symptomes: ' . $data['eq_symptomes'] . "\n";
                                    $msg .= 'Etat matériel: ' . BS_SAV::$etats_materiel[(int) $data['eq_etat']]['label'] . "\n";
                                    $msg .= 'Système: ' . $systems[(int) $data['eq_system']] . "\n";
                                }

                                if (BimpObject::objectLoaded($client)) {
                                    $msg .= 'Client: ' . $client->getLink(array(), 'private') . "\n";
                                } else {
                                    $msg .= 'Le client n\'a pas pu être créé (Une erreur est survenue)' . "\n";
                                }

                                if (BimpObject::objectLoaded($contact)) {
                                    $msg .= 'Contact: ' . $contact->getLink(array(), 'private') . "\n";
                                } else {
                                    $msg .= 'Nom du contact client: ' . $data['client_firstname'] . ' ' . $data['client_lastname'] . '<br/>';

                                    if ($data['client_phone_pro']) {
                                        $msg .= 'Tél. pro: ' . $data['client_phone_pro'] . '<br/>';
                                    }
                                    if ($data['client_phone_mobile']) {
                                        $msg .= 'Tél. mobile: ' . $data['client_phone_mobile'] . '<br/>';
                                    }
                                    if ($data['client_phone_perso']) {
                                        $msg .= 'Tél. domicile: ' . $data['client_phone_perso'] . '<br/>';
                                    }
                                }

                                if (BimpObject::objectLoaded($userClient)) {
                                    $msg .= 'Adresse e-mail de contact: ' . $userClient->getData('email') . "\n";
                                } else {
                                    $msg .= 'Adresse e-mail de contact: ' . $data['client_email'] . "\n";
                                }

                                if (BimpObject::objectLoaded($equipment)) {
                                    $msg .= 'Equipement: ' . $equipment->getLink(array(), 'private') . "\n";
                                } else {
                                    $msg .= 'L\'équipement n\'a pas pu être créé (une erreur est survenue)<br/>';
                                    $msg .= 'N° de série: ' . $data['eq_serial'] . '<br/>';
                                }

                                mailSyn2('Nouveau SAV créé en ligne', $emails, '', $msg);
                            }
                        }

                        // Envoi e-mail client: 

                        $msg = 'Bonjour,' . "\n\n";

                        if ($reservationId) {
                            $msg .= 'Merci d’avoir pris rendez-vous dans notre Centre de Services Agrée Apple, nous vous confirmons la prise en compte de votre réservation.' . "\n";
                        } elseif (BimpObject::objectLoaded($sav)) {
                            $msg .= 'Nous vous confirmons l\'enregistrement de votre dossier SAV dans notre centre de Services Agrée Apple.' . "\n";
                            $msg .= 'Aucun rendez-vous n\'a été fixé. Vous pouvez donc déposer votre matériel dans notre centre BIMP quand vous le souhaitez.' . "\n";
                        } else {
                            $msg .= 'En raison d\'un problème technique, votre dossier SAV n\'a pas pu être enregistré.';
                            $msg .= 'Toutefois, les techniciens du centre BIMP de ' . $centre['town'] . ' ont été alertés par e-mail de votre demande.' . "\n";
                            $msg .= 'Vous pouvez donc passer à notre agence BIMP quand vous le souhaitez pour déposer votre matériel.' . "\n";
                        }

                        $msg .= "\n";

                        $msg .= '<b>Adresse du centre BIMP: </b>' . "\n";
                        $msg .= $centre['address'] . "\n";
                        $msg .= $centre['zip'] . ' ' . $centre['town'] . "\n\n";

                        if (!is_null($dateBegin)) {
                            $msg .= '<b>Date du rendez-vous: </b>' . "\n";
                            $msg .= 'Le ' . $dateBegin->format('d / m / Y à H:i') . "\n\n";
                        }

                        if (BimpObject::objectLoaded($sav)) {
                            $msg .= '<b>Référence SAV:</b> ' . $sav->getRef() . "\n";
                        }
                        $msg .= '<b>N° de série du matériel concerné: </b>' . $data['eq_serial'] . "\n\n";

                        $msg .= "Afin de préparer au mieux votre prise en charge, nous souhaitons attirer votre attention sur les points suivants :
- Vous devez sauvegarder vos données car nous serons peut-être amenés à les effacer de votre appareil.

- Vous devez désactiver la fonction « localiser » dans le menu iCloud avec votre mot de passe.

- Le délai de traitement des réparations est habituellement de 7 jours.


Conditions particulières aux iPhones


- Pour certains types de pannes sous garantie, un envoi de l’iPhone dans un centre Apple peut être nécessaire, entrainant un délai plus long (jusqu’à 10 jours ouvrés), dans ce cas un téléphone de prêt est possible (sous réserve de disponibilité). Si cela vous intéresse, merci de vous munir d’un chèque de caution.

La plupart de nos centres peuvent effectuer une réparation de votre écran d’iPhone sous 24h00. Pour savoir si votre centre SAV est éligible à ce type de réparation consultez nottre site internet.

Nous proposons des services de sauvegarde des données, de protection de votre téléphone… venez nous rencontrer pour découvrir tous les services que nous pouvons vous proposer.
Votre satisfaction est notre objectif, nous mettrons tout en œuvre pour vous satisfaire et réduire les délais d’immobilisation de votre produit Apple.

Bien cordialement

L’équipe BIMP";

                        $email_client = BimpTools::cleanEmailsStr(BimpObject::objectLoaded($userClient) ? $userClient->getData('email') : $data['client_email']);
                        $email_client_ok = mailSyn2('SAV BIMP - Confirmation', $email_client, '', $msg);

                        // HTML Succès: 

                        $success_html = '<div class="form_section" id="client_email" style="text-align: center">';

                        if (BimpObject::objectLoaded($sav)) {
                            $success_html .= '<h2 class="success">Votre dossier SAV a été ouvert avec succès</h2>';
                            $success_html .= '<p>Référence: ' . $sav->getRef() . '</p>';
                        } else {
                            $success_html = '<h3>En raison d\'une erreur technique, votre dossier SAV n\'a pas pu être créé.</h3>';
                            $success_html .= '<p>Toutefois, les techniciens du centre BIMP de ' . $centre['town'] . ' ont été alertés par e-mail de votre demande.<br/>';
                            $success_html .= 'Vous pouvez donc passer à notre agence BIMP quand vous le souhaitez pour déposer votre matériel.</p><br/>';
                        }

                        $success_html .= '<br/>';

                        if ($reservationId && !is_null($dateBegin)) {
                            $success_html .= '<p>Date de votre rendez-vous: <b>' . $dateBegin->format('d / m / Y à H:i') . '</b></p><br/>';
                        }

                        $success_html .= '<b></b>';

                        $success_html .= '<p>Lieu: <b><br/>';
                        $success_html .= $centre['address'] . '<br/>';
                        $success_html .= $centre['zip'] . ' ' . $centre['town'] . '</b></p><br/>';

                        if ($email_client_ok) {
                            $success_html .= '<p>Un e-mail récapitulatif a été envoyé à <b>' . $email_client . '</b></p>';
                        } else {
                            $success_html .= 'Afin de préparer au mieux votre prise en charge, nous souhaitons attirer votre attention sur les points suivants : <br/>';
                            $success_html .= '- Vous devez sauvegarder vos données car nous serons peut-être amenés à les effacer de votre appareil.<br/>';
                            $success_html .= '- Vous devez désactiver la fonction « localiser » dans le menu iCloud avec votre mot de passe.<br/>';
                            $success_html .= '- Le délai de traitement des réparations est habituellement de 7 jours.<br/><br/>';

                            $success_html .= 'Conditions particulières aux iPhones: <br/>';
                            $success_html .= '- Pour certains types de pannes sous garantie, un envoi de l’iPhone dans un centre Apple peut être nécessaire, entrainant un délai plus long (jusqu’à 10 jours ouvrés), dans ce cas un téléphone de prêt est possible (sous réserve de disponibilité). Si cela vous intéresse, merci de vous munir d’un chèque de caution.<br/><br/>';

                            $success_html .= 'La plupart de nos centres peuvent effectuer une réparation de votre écran d’iPhone sous 24h00. Pour savoir si votre centre SAV est éligible à ce type de réparation consultez nottre site internet.<br/><br/>';

                            $success_html .= 'Nous proposons des services de sauvegarde des données, de protection de votre téléphone… venez nous rencontrer pour découvrir tous les services que nous pouvons vous proposer.<br/>';
                            $success_html .= 'Votre satisfaction est notre objectif, nous mettrons tout en œuvre pour vous satisfaire et réduire les délais d’immobilisation de votre produit Apple.<br/>';
                        }

                        $success_html .= '</div>';

//                        $success_html .= '<br/>DEBUG: <br/>';
//                        $success_html .= $debug;
                    }
                }
            }
        }

        return array(
            'errors'                => $errors,
            'warnings'              => $warnings,
            'html'                  => $html,
            'success_html'          => $success_html,
            'slot_not_available'    => $slotNotAvailable,
            'force_validate'        => $forceValidate,
            'force_validate_reason' => $forceValidateReason,
//            'debug'                 => '',
            'debug'                 => $debug,
            'request_id'            => BimpTools::getValue('request_id', 0)
        );
    }

    public function ajaxProcessCancelSav()
    {
        $errors = array();
        $warnings = array();
        $success_html = '';

        $id_sav = BimpTools::getValue('id_sav', 0);
        $ref_sav = BimpTools::getValue('ref_sav', '');
        $reservation_id = BimpTools::getValue('reservation_id', '');

        if (!$ref_sav) {
            $errors[] = 'Reférence du SAV absente';
        } elseif (!$id_sav) {
            $errors[] = 'Identifiant du SAV absent';
        } else {
            $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);

            if (!BimpObject::objectLoaded($sav)) {
                $errors[] = 'Le SAV "' . $ref_sav . '" n\'existe plus';
            } else {
                if ($sav->getData('ref') != $ref_sav) {
                    $errors[] = 'Référence du SAV invalide';
                } else {
                    if ($reservation_id) {
                        $centre = $sav->getCentreData();

                        if (is_null($centre)) {
                            $errors[] = 'Centre absent';
                        }

                        if (!count($errors)) {
                            // Annulation de la requête: 
                            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_Reservation.php';

                            $result = GSX_Reservation::cancelReservation(897316, $centre['ship_to'], $reservation_id, $errors);

                            if (isset($result['faults']) && !empty($result['faults'])) {
                                $request_errors = array();
                                foreach ($result['faults'] as $fault) {
                                    $request_errors[] = $fault['message'] . ' (code: ' . $fault['code'] . ')';
                                }
                                $errors[] = BimpTools::getMsgFromArray($request_errors, 'Echec de l\'annulation de la réservation');
                            } elseif (is_null($result) && !count($errors)) {
                                $errors[] = 'Echec de l\'annulation de la réservation pour une raison inconnue';
                            }
                        }
                    }

                    if (!count($errors)) {
                        $success_html = '<h2 class="success">Votre rendez-vous SAV a été annulé avec succès</h2>';
                        $success_html .= '<p><a href="' . DOL_URL_ROOT . '/bimpinterfaceclient/client.php">Retour à votre espace client</a></p>';

                        // Maj SAV: 
                        $sav->updateField('status', -2);
                        $sav->addNote('Annulé par le client le ' . date('d / m / Y à H:i'), 4);

                        // Màj action comm: 
//                        if ($reservation_id) {
//                            $id_ac = (int) BimpCache::getBdb()->getValue('actioncomm_extrafields', 'fk_object', 'resgsx = \'' . $reservation_id . '\'');
//                            
//                            if ($id_ac) {
////                                /Users/flo/Documents/NetBeansProjects/BIMP_ERP/FLODEV_1/BIMP_ERP_FLODEV_1/htdocs/comm/action/class/actioncomm.class.php
//                                global $db;
//                                $ac = new ActionComm($db);
//                            }
//                        }
                    }
                }
            }
        }

        return array(
            'errors'       => $errors,
            'warnings'     => $warnings,
            'success_html' => $success_html,
            'request_id'   => BimpTools::getValue('request_id', 0)
        );
    }
}
