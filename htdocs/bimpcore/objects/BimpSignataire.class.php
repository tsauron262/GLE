<?php

class BimpSignataire extends BimpObject
{

    const STATUS_REFUSED = -2;
    const STATUS_CANCELLED = -1;
    const STATUS_NONE = 0;
    const STATUS_ATT_DOCUSIGN = 1;
    const STATUS_SIGNED = 10;

    public static $status_list = array(
        self::STATUS_REFUSED      => array('label' => 'Refusée', 'icon' => 'fas_exclamation-circle', 'classes' => array('important')),
        self::STATUS_CANCELLED    => array('label' => 'Annulée', 'icon' => 'fas_times-circle', 'classes' => array('danger')),
        self::STATUS_NONE         => array('label' => 'Signature en attente', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::STATUS_ATT_DOCUSIGN => array('label' => 'En attente de signature DocuSign', 'fas_hourglass-start', 'classes' => array('warning')),
        self::STATUS_SIGNED       => array('label' => 'Signée', 'icon' => 'fas_check', 'classes' => array('success'))
    );

    const TYPE_CLIENT = 1;
    const TYPE_USER = 2;
    const TYPE_CUSTOM = 3;

    public static $types = array(
        self::TYPE_CLIENT => 'Client',
        self::TYPE_USER   => 'Utilisateur',
        self::TYPE_CUSTOM => 'Autre'
    );

    const TYPE_NONE = 0;
    const TYPE_DIST = 1;
    const TYPE_PAPIER = 2;
    const TYPE_ELEC = 3;
    const TYPE_PAPIER_NO_SCAN = 4;
    const TYPE_DOCUSIGN = 5;

    public static $types_signatures = array(
        self::TYPE_NONE           => array('label' => 'Aucune signature', 'icon' => 'fas_times'),
        self::TYPE_DIST           => array('label' => 'Electronique à distance', 'icon' => 'fas_sign-in-alt'),
        self::TYPE_PAPIER         => array('label' => 'Document scanné', 'icon' => 'fas_file-download'),
        self::TYPE_ELEC           => array('label' => 'Electronique', 'icon' => 'fas_file-signature'),
        self::TYPE_PAPIER_NO_SCAN => array('label' => 'Papier sans document scanné', 'icon' => 'fas_file-contract'),
        self::TYPE_DOCUSIGN       => array('label' => 'DocuSign', 'icon' => 'fas_arrow-to-bottom')
    );

    // Droits user: 

    public function canClientView()
    {
        global $userClient;

        if (!BimpObject::objectLoaded($userClient)) {
            return 0;
        }

        if ($this->isLoaded()) {
            if ((int) $this->getData('type') !== self::TYPE_CLIENT) {
                return 0;
            }

            if ((int) $userClient->getData('id_client') === (int) $this->getData('id_client')) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

    public function canEditField($field_name)
    {
        if (BimpCore::isContextPublic()) {
            if (!$this->isTypeClient()) {
                return 0;
            }

            global $userClient;

            if (BimpObject::objectLoaded($userClient)) {
                if ((int) $userClient->getData('id_client') === (int) $this->getData('id_client')) {
                    if (in_array($field_name, array('nom', 'fonction'))) {
                        return 1;
                    }
                }
            }

            return 0;
        }

        if (in_array($field_name, array('allow_elec', 'allow_dist', 'allow_refuse', 'allow_docusign', 'need_sms_code'))) {
            global $user;
            return (int) $user->admin;
        }

        return parent::canEditField($field_name);
    }

    public function canSetAction($action)
    {
        switch ($action) {
            case 'refuse':
                if (BimpCore::isContextPrivate()) {
                    return 1;
                }

            case 'signDist':
            case 'sendSmsCode':

                global $userClient;

                if (!BimpObject::objectLoaded($userClient)) {
                    return 0;
                }

                if ((int) $userClient->getData('id_client') !== (int) $this->getData('id_client')) {
                    return 0;
                }

                $allowed = $this->getData('allowed_users_client');
                if (!is_array($allowed) || !in_array($userClient->id, $allowed)) {
                    return 0;
                }
                return 1;
        }
        return parent::canSetAction($action);
    }

    public function canDelete()
    {
        global $user;

        if ($user->admin) {
            return 1;
        }

        return 0;
    }

    // Getters booléens: 

    public function isFieldEditable($field, $force_edit = false)
    {
        if (in_array($field, array('id_contact', 'id_user', 'allow_elec', 'allow_dist', 'allow_docusign', 'allow_refuse', 'need_sms_code'))) {
            if ($this->isSigned()) {
                return 0;
            }

            return 1;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isDeletable($force_delete = false, &$errors = [])
    {
        if (!$force_delete && $this->isSigned()) {
            return 0;
        }

        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        $status = (int) $this->getData('status');
        switch ($action) {
            case 'signDistAccess':
            case 'signDist':
            case 'signElec':
            case 'sendSmsCode':
            case 'signDocuSign':
                if ($this->isSigned()) {
                    $errors[] = 'Signature déjà effectuée';
                    return 0;
                }

                if ($status < 0) {
                    $errors[] = 'Signature annulée ou refusée';
                    return 0;
                }

                switch ($action) {
                    case 'signDistAccess':
                    case 'signDist':
                    case 'sendSmsCode':
                        if ($status === self::STATUS_ATT_DOCUSIGN) {
                            $errors[] = 'Signature DocuSign en attente';
                            return 0;
                        }
                        if (!(int) $this->getData('allow_dist') || (int) $this->getData('type') !== self::TYPE_CLIENT) {
                            $errors[] = 'Signature à distance non autorisée pour ce signataire';
                            return 0;
                        }
                        break;

                    case 'signElec':
                        if ($status === self::STATUS_ATT_DOCUSIGN) {
                            $errors[] = 'Signature DocuSign en attente';
                            return 0;
                        }
                        if (!(int) $this->getData('allow_elec')) {
                            $errors[] = 'Signature éléctronique non autorisée pour ce signataire';
                            return 0;
                        }
                        break;

                    case 'signDocuSign':
                        if ($status !== self::STATUS_ATT_DOCUSIGN) {
                            $errors[] = 'Aucune signature DocuSign en attente pour ce signataire';
                            return 0;
                        }
                        if (!(int) $this->getData('allow_docusign')) {
                            $errors[] = 'DocuSign non autorisé pour ce signataire';
                            return 0;
                        }
                        break;
                }
                return 1;

            case 'setSignatureParams':
                if (!$this->isSigned()) {
                    $errors[] = 'Signature non effectuée';
                    return 0;
                }
                if (!in_array($this->getData('type'), array(self::TYPE_DIST, self::TYPE_ELEC))) {
                    $errors[] = 'Type invalide';
                    return 0;
                }
                return 1;

            case 'refuse':
                if ($status < 0 || $status >= 10) {
                    $errors[] = 'Il n\'est pas possible de refuser cette signature';
                    return 0;
                }

                if (!(int) $this->getData('allow_refuse')) {
                    $errors[] = 'Refus de la signature non autorisé pour ce signataire';
                    return 0;
                }
                return 1;

            case 'reopen':
                if ($status !== self::STATUS_REFUSED) {
                    $errors[] = 'Cette signature n\'a pas été refusée par ce sigataire';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function isSigned()
    {
        return ($this->getData('status') >= 10);
    }

    public function isTypeClient()
    {
        return ((int) $this->getData('type') == self::TYPE_CLIENT);
    }

    public function isTypeUser()
    {
        return ((int) $this->getData('type') == self::TYPE_CLIENT);
    }

    public function isTypeCustom()
    {
        return ((int) $this->getData('type') == self::TYPE_CLIENT);
    }

    public function isClientCompany()
    {
        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            return $client->isCompany();
        }

        return 0;
    }

    public function isFonctionRequired()
    {
        if ((int) $this->getData('type') === self::TYPE_CLIENT) {
            return $this->isClientCompany();
        }

        return 1;
    }

    public function isVilleRequired()
    {
        $signature = $this->getParentInstance();

        if (BimpObject::objectLoaded($signature)) {
            $params = $signature->getSignatureParams($this, 'elec');

            if (is_array($params)) {
                return (int) BimpTools::getArrayValueFromPath($params, 'display_ville', 0);
            }
        }

        return 0;
    }

    public function showAutoOpenPublicAccess()
    {
        return (empty($this->getInitData('allowed_users_client')) ? 1 : 0);
    }

    public function showOpenPublicAccessNewUserEmailInput()
    {
        $users = $this->getDefaultAllowedUsersClientArray();

        return (empty($users) ? 1 : 0);
    }

    public function showSignatureImage()
    {
        if ($this->isSigned() && in_array((int) $this->getData('type'), array(self::TYPE_DIST, self::TYPE_ELEC))) {
            return 1;
        }

        return 0;
    }

    // Getters params: 

    public function getActionsButtons($with_signataire_label = true)
    {
        $label_prefixe = '';

        if ($with_signataire_label && $this->getData('label')) {
            $label_prefixe = $this->getData('label') . ' : ';
        }

        if ($this->isActionAllowed('signElec') && $this->canSetAction('signElec')) {
            $buttons[] = array(
                'label'   => $label_prefixe . 'Signature électronique',
                'icon'    => 'fas_file-signature',
                'onclick' => $this->getJsActionOnclick('signElec', array(), array(
                    'form_name' => 'sign_elec'
                ))
            );
        }

        if ($this->isActionAllowed('signDistAccess') && $this->canSetAction('signDistAccess')) {
            $buttons[] = array(
                'label'   => $label_prefixe . (empty($this->getData('allowed_users_client')) ? 'Ouvrir' : 'Modifier') . ' accès signature à distance',
                'icon'    => 'fas_sign-in-alt',
                'onclick' => $this->getJsActionOnclick('signDistAccess', array(), array(
                    'form_name' => 'open_sign_dist'
                ))
            );
        }

        if ($this->isActionAllowed('refuse') && $this->canSetAction('refuse')) {
            $buttons[] = array(
                'label'   => $label_prefixe . 'Signature refusée',
                'icon'    => 'fas_times-circle',
                'onclick' => $this->getJsActionOnclick('refuse', array(), array(
                    'form_name' => 'motif'
                ))
            );
        }

        if ($this->isActionAllowed('reopen') && $this->canSetAction('reopen')) {
            $buttons[] = array(
                'label'   => $label_prefixe . 'Réouvrir la Signature',
                'icon'    => 'fas_undo',
                'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                    'confirm_msg' => 'Veuillez confirmer la réouveture de cette signature'
                ))
            );
        }

//        if ($this->isActionAllowed('setSignatureParams') && $this->canSetAction('setSignatureParams')) {
//            $buttons[] = array(
//                'label'   => 'Ajuster la Signature sur le document',
//                'icon'    => 'fas_arrows-alt',
//                'onclick' => $this->getJsActionOnclick('setSignatureParams', array(), array(
//                    'form_name' => 'signature_params'
//                ))
//            );
//        }

        return $buttons;
    }

    // Getters defaults: 

    public function getDefaultAllowedUsersClient()
    {
        $values = $this->getData('allowed_users_client');

        if (is_array($values) && !empty($values)) {
            return $values;
        }

        $values = array();
        $users = $this->getDefaultAllowedUsersClientArray();

        foreach ($users as $id_user => $label) {
            $values[] = $id_user;
        }

        return $values;
    }

    public function getDefaultAllowedUsersClientArray()
    {
        if ($this->isLoaded()) {
            $cache_key = 'bimp_signataire_' . $this->id . '_default_allowed_users_client';

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();

                $id_client = (int) $this->getData('id_client');
                if ($id_client) {
                    $id_contact = (int) $this->getData('id_contact');
                    $email = '';
                    if ($id_contact) {
                        $email = (string) $this->db->getValue('socpeople', 'email', 'rowid = ' . $id_contact);
                    }
                    if (!$email) {
                        $email = (string) $this->db->getValue('societe', 'email', 'rowid = ' . $id_client);
                    }

                    $where = 'a.id_client = ' . $id_client . ' AND a.status = 1';
                    $where .= ' AND (a.role = 1' . ($id_contact ? ' OR a.id_contact = ' . $id_contact : '') . ($email ? ' OR a.email = \'' . $email . '\'' : '') . ')';
                    $rows = $this->db->getRows('bic_user a', $where, null, 'array', array('a.id, a.email, a.role, a.id_contact, c.firstname, c.lastname'), 'a.id', 'desc', array(
                        'c' => array(
                            'table' => 'socpeople',
                            'on'    => 'c.rowid = a.id_contact',
                            'alias' => 'c'
                        )
                    ));

                    if (is_array($rows)) {
                        foreach ($rows as $r) {
                            $label = '';

                            if ($email && $email == $r['email']) {
                                $label .= '<b>' . $r['email'] . '</b>';
                            } else {
                                $label .= $r['email'];
                            }

                            if ($r['firstname'] || $r['lastname']) {
                                $label .= ' (';
                                if ($id_contact && (int) $r['id_contact'] === $id_contact) {
                                    $label .= '<b>';
                                }
                                $label .= 'Contact: ' . ($r['firstname'] ? $r['firstname'] : '') . ($r['firstname'] && $r['lastname'] ? ' ' . $r['lastname'] : '');
                                if ($id_contact && (int) $r['id_contact'] === $id_contact) {
                                    $label .= '</b>';
                                }
                                $label .= ')';
                            }

                            if ((int) $r['role'] === 1) {
                                $label .= ' - <b>Admin</b>';
                            }

                            self::$cache[$cache_key][(int) $r['id']] = $label;
                        }
                    }
                }
            }

            return self::$cache[$cache_key];
        }

        return array();
    }

    public function getDefaultNomSignataire()
    {
        if ($this->getData('nom_signataire')) {
            return $this->getData('nom_signataire');
        }

        $contact = null;

        if (BimpCore::isContextPublic()) {
            global $userClient;

            if (BimpObject::objectLoaded($userClient)) {
                $contact = $userClient->getChildObject('contact');
            }
        } else {
            $contact = $this->getChildObject('contact');
        }

        if (BimpObject::objectLoaded($contact)) {
            return $contact->getName();
        }

        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            return $client->getName();
        }

        return '';
    }

    public function getDefaultEmailSignataire()
    {
        if ($this->getData('email_signataire')) {
            return $this->getData('email_signataire');
        }

        $contact = null;

        if (BimpCore::isContextPublic()) {
            global $userClient;

            if (BimpObject::objectLoaded($userClient)) {
                $contact = $userClient->getChildObject('contact');
            }
        } else {
            $contact = $this->getChildObject('contact');
        }

        if (BimpObject::objectLoaded($contact)) {
            return $contact->getData('email');
        }

        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            return $client->getData('email');
        }

        return '';
    }

    public function getDefaultFonctionSignataire()
    {
        if ($this->getData('fonction_signataire')) {
            return $this->getData('fonction_signataire');
        }

        $contact = null;

        if (BimpCore::isContextPublic()) {
            global $userClient;

            if (BimpObject::objectLoaded($userClient)) {
                $contact = $userClient->getChildObject('contact');
            }
        } else {
            $contact = $this->getChildObject('contact');
        }

        if (BimpObject::objectLoaded($contact)) {
            return $contact->getData('poste');
        }

        return '';
    }

    public function getDefaultContactInfo($field)
    {

        $contact = $this->getChildObject('contact');
        if (BimpObject::objectLoaded($contact)) {
            return $contact->getData($field);
        }

        return '';
    }

    // Getters données: 

    public function getNameProperties()
    {
        return array('nom');
    }

    public function getAutoOpenPublicAccessInputHelp()
    {
        $msg = '';

        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $users = $this->getDefaultAllowedUsersClientArray();

            $contact = $this->getChildObject('contact');

            $email = '';
            if (BimpObject::objectLoaded($contact)) {
                $email = $contact->getData('email');
            }

            if (!$email) {
                $email = $client->getData('email');
            }

            if (empty($users)) {
                $msg .= 'Le client ne dispose d\'aucun compte utilisateur valide pour la signature à distance de ce document via l\'espace client.<br/>';
                $msg .= 'En sélectionnant "OUI" un compte utilisateur client sera automatiquement créé pour l\'adresse e-mail indiquée dans le champ ci-dessous.';
            } else {
                $msg .= 'En sélectionnant "OUI" l\'accès à la signature électronique à distance pour ce document sera automatiquement ouvert pour tous les comptes utilisateurs de ce client ayant le rôle d\'administrateur';

                if (BimpObject::objectLoaded($contact)) {
                    $msg .= ' ainsi que pour le compte associé au contact "' . $contact->getName() . '"';

                    if ($email) {
                        $msg .= ' ou à l\'adresse e-mail "' . $email . '"';
                    }
                } elseif ($email) {
                    $msg .= ' ainsi que pour le compte associé à l\'adresse e-mail "' . $email . '"';
                }

                $msg .= '<br/><br/>';
                $msg .= 'L\'accès à la signature à distance sera ouvert pour les comptes utilisateurs suivants: <br/>';

                $user_exists = false;
                foreach ($users as $id_user => $user_label) {
                    $msg .= '<br/>' . $user_label;

                    if ($email && strpos($user_label, $email) !== false) {
                        $user_exists = true;
                    }
                }

                if (!$user_exists) {
                    $msg .= '<br/><br/><b>' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Un compte utilisateur client sera également automatiquement créé pour l\'adresse e-mail "' . $email . '"</b>';
                }
            }
        }

        return $msg;
    }

    public function getOpenPublicAccessNewUserDefaultEmail()
    {
        $email = '';

        if ($this->getData('id_contact')) {
            $contact = $this->getChildObject('contact');

            if (BimpObject::objectLoaded($contact)) {
                $email = $contact->getData('email');
            }
        }

        if (!$email) {
            if ((int) $this->getData('id_client')) {
                $client = $this->getChildObject('client');

                if (BimpObject::objectLoaded($client)) {
                    $email = $client->getData('email');
                }
            }
        }

        return $email;
    }

    public function getSignatureParamsFormValues()
    {
        $signature = $this->getParentInstance();

        if (BimpObject::objectLoaded($signature)) {
            return $signature->getSignatureParamsFormValues($this);
        }

        return array();
    }

    public function getOnSignedNotificationEmail(&$use_as_from = false)
    {
        $email = '';
        $signature = $this->getParentInstance();

        if (BimpObject::objectLoaded($signature)) {
            $email = $signature->getOnSignedNotificationEmail($use_as_from);
        }

        if (!$email && (int) $this->getData('type') === self::TYPE_CLIENT) {
            $client = $this->getChildObject('client');

            if (BimpObject::objectLoaded($client)) {
                $use_as_from = false;
                $email = $client->getCommercialEmail(false);
            }
        }

        return $email;
    }

    public function getRelanceDelay($first_relance = false)
    {
        $obj = $this->getObj();

        $errors = array();
        if ($this->isObjectValid($errors, $obj)) {
            if (method_exists($obj, 'getSignaturesRelanceDelay')) {
                return (int) $obj->getSignaturesRelanceDelay($this->getData('doc_type'), $first_relance);
            }
        }

        return 0;
    }

    public function getCheckMentions()
    {
        $parent = $this->getParentInstance();
        if (BimpObject::objectLoaded($parent)) {
            return $parent->getCheckMentions($this->getData('code'));
        }

        return array();
    }

    // Getters Array:

    public function getContactsArray($include_empty = true, $active_only = true)
    {
        if ((int) $this->getData('id_client')) {
            return self::getSocieteContactsArray((int) $this->getData('id_client'), $include_empty);
        }

        if ($include_empty) {
            return array(
                0 => ''
            );
        }

        return array();
    }

    public function getClientUsersArray($include_empty = false)
    {
        $id_client = (int) $this->getData('id_client');

        if ($id_client) {
            BimpObject::loadClass('bimpinterfaceclient', 'BIC_UserClient');
            return BIC_UserClient::getClientUsersArray($id_client, $include_empty);
        }

        return array();
    }

    // Affichages:

    public function displaySignataire($display_name = 'nom_url', $display_input_value = true, $no_html = false)
    {
        $html = '';
        switch ($this->getData('type')) {
            case self::TYPE_CLIENT:
                if ((int) $this->getData('id_client')) {
                    $html .= 'Client : ' . $this->displayData('id_client', $display_name, $display_input_value, $no_html);
                }
                if ((int) $this->getData('id_contact')) {
                    $html .= ($html ? '<br/>' : '') . 'Contact : ' . $this->displayData('id_contact', $display_name, $display_input_value, $no_html);
                }
                break;

            case self::TYPE_USER:
                $html = 'Utilisateur : ' . $this->displayData('id_user', $display_name, $display_input_value, $no_html);
                break;

            case self::TYPE_CUSTOM:
                $html .= $this->getData('nom');
                if ($this->getData('email')) {
                    $html .= ($html ? '<br/>' : '') . $this->getData('email');
                }
                break;
        }

        if ($no_html) {
            $html = BimpTools::replaceBr($html);
        }

        return $html;
    }

    public function displayPublicDocument($label = 'Document PDF')
    {
        $signature = $this->getParentInstance();

        if ($this->isLoaded() && BimpObject::objectLoaded($signature)) {
            $obj = $signature->getObj();

            if ($signature->isObjectValid()) {
                if ($this->can('view')) {
                    $dir = $signature->getDocumentFileDir();

                    if ($dir) {
                        $buttons = array();
                        $files = array();

                        if ($this->isSigned()) {
                            $files[] = $signature->getDocumentFileName(true);
                            ;
                        } else {
                            $files = $signature->getUnsignedFilesNames();
                        }

                        $right = false;
                        if (BimpCore::isContextPublic()) {
                            global $userClient;

                            if (BimpObject::objectLoaded($userClient)) {
                                $allowed = $this->getData('allowed_users_client');
                                if ($userClient->isAdmin() || (is_array($allowed) && in_array($userClient->id, $allowed))) {
                                    $right = true;
                                }
                            }
                        } else {
                            $right = true;
                        }

                        $nb_files = count($files);
                        $i = 0;
                        $pathinfo = pathinfo($signature->getDocumentFileName(false));
                        foreach ($files as $file) {
                            if (file_exists($dir . $file)) {
                                $file_idx = 0;

                                if ($nb_files > 1) {
                                    if (preg_match('/^' . preg_quote($pathinfo['filename'], '/') . '(\-(\d+))?\.' . $pathinfo['extension'] . '$/', $file, $matches)) {
                                        $file_idx = (int) $matches[2];
                                    }
                                }

                                $url = $obj->getSignatureDocFileUrl($signature->getData('doc_type'), 'public', $this->isSigned(), $file_idx);
                                if ($url) {
                                    $i++;

                                    if ($right) {
                                        $buttons[] = array(
                                            'label'   => ($nb_files > 1 ? 'Document n°' . $i . ' (' . $file . ')' : $label),
                                            'icon'    => 'fas_file-pdf',
                                            'onclick' => 'window.open(\'' . $url . '\')'
                                        );
                                    } else {
                                        $buttons[] = array(
                                            'label'    => ($nb_files > 1 ? 'Document n°' . $i . ' (' . $file . ')' : $label),
                                            'icon'     => 'fas_file-pdf',
                                            'onclick'  => '',
                                            'disabled' => 1,
                                            'popover'  => 'Vous n\'avez pas la permission de voir ce document'
                                        );
                                    }
                                }
                            }
                        }

                        if (count($buttons) > 1) {
                            return BimpRender::renderButtonsGroups(array(
                                        array(
                                            'label'   => 'Documents PDF',
                                            'icon'    => 'fas_file-pdf',
                                            'buttons' => $buttons
                                        )
                                            ), array(
                                        'max'                 => 1,
                                        'dropdown_menu_right' => 1
                            ));
                        } else {
                            $html = '';
                            foreach ($buttons as $button) {
                                $html .= BimpRender::renderButton($button);
                            }
                            return $html;
                        }
                    }
                }
            }

            return '<span class="warning">Non disponible</span>';
        }

        return '';
    }

    public function dispayPublicSign()
    {
        $html = '';

        if ($this->isLoaded()) {
            if ($this->isActionAllowed('signDist')) {
                if ($this->canSetAction('signDist')) {
                    $infos = $this->getData('code_sms_infos');
                    if ((int) $this->getData('need_sms_code') && !BimpTools::getArrayValueFromPath($infos, 'code', '')) {
                        $onclick = $this->getJsActionOnclick('sendSmsCode', array(), array(
                            'form_name' => 'sms_code'
                        ));
                    } else {
                        $onclick = $this->getJsActionOnclick('signDist', array(), array(
                            'form_name' => 'sign_dist'
                        ));
                    }

                    $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                    $html .= BimpRender::renderIcon('fas_pen', 'iconLeft') . 'Signer';
                    $html .= '</span>';
                } else {
                    $html .= '<span class="btn btn-default disabled bs-popover"';
                    $html .= BimpRender::renderPopoverData('Vous n\'avez pas la permission');
                    $html .= '>';
                    $html .= BimpRender::renderIcon('fas_pen', 'iconLeft') . 'Signer';
                    $html .= '</span>';
                }
            }

            if ($this->isActionAllowed('refuse') && $this->canSetAction('refuse')) {
                $onclick = $this->getJsActionOnclick('refuse', array(), array(
                    'form_name' => 'motif'
                ));

                $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                $html .= BimpRender::renderIcon('fas_times', 'iconLeft') . 'Refuser';
                $html .= '</span>';
            }
        }

        return $html;
    }

    // Rendus HTML: 

    public function renderHeaderStatusExtra()
    {
        $html = '';

        if ($this->isSigned()) {
            $html .= '<br/>' . 'Type de signature : ' . $this->displayData('type', 'default', false);
        }

        return $html;
    }

    public function renderSignatureImage()
    {
        $html = '';

        if ($this->showSignatureImage()) {
            $image = $this->getData('base_64_signature');

            if (!$image) {
                $html .= BimpRender::renderAlerts('Image de la signature absente');
            } else {
                $html .= '<img src="' . $image . '" width="100%; height: auto">';
            }
        }

        return $html;
    }

    public function renderSignButtonsGroup($label = 'Actions signature')
    {
        $buttons = $this->getActionsButtons();

        if (!empty($buttons)) {
            return BimpRender::renderButtonsGroup($buttons, array(
                        'max'            => 1,
                        'dropdown_icon'  => 'fas_signature',
                        'dropdown_label' => $label
            ));
        }

        return '';
    }

    public function renderNumTelForSmsCodeInput()
    {
        $html = '';

        $msg = 'Nous allons vous envoyer un code par SMS pour certifier la signature.<br/>';
        $msg .= 'Veuillez sélectionner le numéro de téléphone mobile sur lequel envoyer ce code';

        $html .= BimpRender::renderAlerts($msg, 'info');

        $nums = array();

        global $userClient;

        if (BimpObject::objectLoaded($userClient)) {
            $contact = $userClient->getChildObject('contact');

            if (BimpObject::objectLoaded($contact)) {
                foreach (array('phone_mobile', 'phone_perso', 'phone') as $field) {
                    if ($contact->field_exists($field)) {
                        $num = $contact->getData($field);

                        if ($num) {
                            $num = str_replace(array(' ', '-', '/', '_', '.'), array('', '', '', '', ''), $num);

                            if (!array_key_exists($num, $nums) && preg_match('/^(\+33|0)(6|7)[0-9]{6}([0-9]{2})$/', $num, $matches)) {
                                $nums[$num] = $matches[1] . $matches[2] . ' ** ** ** ' . $matches[3];
                            }
                        }
                    }
                }
            }
        }

        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $num = $client->getData('phone');

            if ($num) {
                $num = str_replace(array(' ', '-', '/', '_', '.'), array('', '', '', '', ''), $num);

                if (!array_key_exists($num, $nums) && preg_match('/^(\+33|0)(6|7)[0-9]{6}([0-9]{2})$/', $num, $matches)) {
                    $nums[$num] = $matches[1] . [$matches[2]] . ' ** ** ** ' . $matches[3];
                }
            }
        }

        $nums['other'] = 'Autre';

        $html .= BimpInput::renderInput('select', 'num_tel_selected', '', array(
                    'options' => $nums
        ));

        return $html;
    }

    public function renderSignDistCodeSmsInput()
    {
        $html = '';

        if (!(int) $this->getData('need_sms_code')) {
            $html .= '<span class="success">Non applicable</span>';
            $html .= '<input type="hidden" value="xxxx" name="code_sms"/>';
            return $html;
        }

        $infos = $this->getData('code_sms_infos');

        $dt_ok = false;
        $dt_send = BimpTools::getArrayValueFromPath($infos, 'dt_send', '');

        if ($dt_send) {
            $dt = new DateTime($dt_send);
            $dt->add(new DateInterval('PT1H'));

            if ($dt->format('Y-m-d H:i:s') > date('Y-m-d H:i:s')) {
                $dt_ok = true;
            }
        }

        $html .= '<div style="text-align: center">';
        if (!$dt_ok) {
            $html .= BimpRender::renderAlerts('Le code SMS qui vous a été envoyé a expiré', 'warning');
        } else {
            $html .= '<h4>';
            $html .= 'Code SMS reçu: ';
            $html .= '</h4>';

            $html .= '<input type="text" name="code_sms" value="" style="width: 80px; font-size: 16px; line-height: 18px; padding: 8px 5px"/><br/>';
        }

        $onclick = 'bimpModal.clearAllContents();setTimeout(function() {';
        $onclick .= $this->getJsActionOnclick('sendSmsCode', array(), array(
            'form_name' => 'sms_code'
        ));
        $onclick .= '}, 500);';

        $html .= '<span style="color: #807F7F" class="btn btn-light-default" onclick="' . $onclick . '">' . ($dt_ok ? 'Code non reçu' : 'Envoyer un nouveau code') . '</span>';
        $html .= '</div>';

        return $html;
    }

    // Traitements:

    public function openSignDistAccess($send_email = true, $email_content = '', $auto_open = true, $new_users = array(), $new_user_email = '', &$warnings = array(), &$success = '')
    {
        $errors = array();

        if (!(int) $this->getData('allow_dist')) {
            $errors[] = 'La signature à distance n\'est pas autorisée pour ce signataire';
            return $errors;
        }

        $signature = $this->getParentInstance();

        if (!BimpObject::objectLoaded($signature)) {
            $errors[] = 'Signature liée absent';
            return $errors;
        }

        $obj = $signature->getObj();

        if (!$signature->isObjectValid($errors, $obj)) {
            return $errors;
        }

        $cur_users = $this->getData('allowed_users_client');

        if (empty($new_users) && $auto_open) {
            $new_users = $this->getDefaultAllowedUsersClient();
        }

        if ($auto_open) {
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                $contact = $this->getChildObject('contact');

                if (!$new_user_email) {
                    if (empty($new_users)) {
                        $new_user_email = $this->getDefaultEmailSignataire();
                    }

                    if (!$new_user_email) {
                        $email = '';
                        if (BimpObject::objectLoaded($contact)) {
                            $email = $contact->getData('email');
                        }

                        if (!$email) {
                            $email = $client->getData('email');
                        }

                        if ($email) {
                            $def_users = $this->getDefaultAllowedUsersClientArray();

                            $user_exists = false;
                            foreach ($def_users as $id_user => $user_label) {
                                if (strpos($user_label, $email) !== false) {
                                    $user_exists = true;
                                }
                            }

                            if (!$user_exists) {
                                $new_user_email = $email;
                            }
                        }
                    }
                }

                if (!count($errors) && $new_user_email) {
                    // Check de l\'existance du compte user (il peut être désactivé)
                    $bic_user = BimpCache::findBimpObjectInstance('bimpinterfaceclient', 'BIC_UserClient', array(
                                'id_client' => (int) $client->id,
                                'email'     => $new_user_email
                    ));

                    if (BimpObject::objectLoaded($bic_user)) {
                        if (!(int) $bic_user->getData('status')) {
                            $err = $bic_user->updateField('status', 1);
                            if (count($err)) {
                                $errors[] = BimpTools::getMsgFromArray($err, 'Echec de la réactivation du compte utilisateur "' . $new_user_email . '"');
                            } else {
                                $success .= 'Réactivation du compte utilisateur "' . $new_user_email . '" effectuée avec succès';
                                $new_users[] = $bic_user->id;
                            }
                        }
                    } else {
                        // Création du compte user:
                        $where = 'id_client = ' . (int) $this->getData('id_client') . ' AND status = 1 AND role = 1';
                        $nAdmin = $this->db->getCount('bic_user', $where);
                        $u_err = array();
                        $bic_user = BimpObject::createBimpObject('bimpinterfaceclient', 'BIC_UserClient', array(
                                    'id_client'          => (int) $client->id,
                                    'id_contact'         => (BimpObject::objectLoaded($contact) && $contact->getData('email') == $new_user_email ? $contact->id : 0),
                                    'email'              => $new_user_email,
                                    'role'               => ($nAdmin > 0 ? 0 : 1),
                                    'status'             => 1,
                                    'main_public_entity' => BimpPublicController::getPublicEntityForObjectSecteur($obj)
                                        ), true, $u_err, $warnings);

                        if (count($u_err) || !BimpObject::objectLoaded($bic_user)) {
                            $errors[] = BimpTools::getMsgFromArray($u_err, 'Echec de la création du compte utilisateur client pour l\'adresse email "' . $new_user_email . '"');
                        } else {
                            $success .= 'Création du compte utilisateur effectuée avec succès';
                            $new_users[] = $bic_user->id;
                        }
                    }
                }
            } else {
                $errors[] = 'Impossible de créer le compte utilisateur client - Client lié absent ou invalide';
            }
        }

        if (!count($errors)) {
            $errors = $this->updateField('allowed_users_client', $new_users);

            if (!count($errors)) {
                if (empty($new_users)) {
                    $warnings[] = 'Aucun compte utilisateur client sélectionné - Le client n\'aura aucun accès à la signature à distance';
                    $this->updateField('date_open', null);
                } else {
                    $success .= ($success ? '<br/>' : '') . 'Liste des utilisteurs client enregistrée avec succès';

                    if (empty($cur_users)) {
                        $up_err = $this->updateField('date_open', date('Y-m-d'));

                        if (count($up_err)) {
                            $warnings[] = BimpTools::getMsgFromArray($up_err, 'Echec màj date d\'ouverture');
                        }
                    }

                    $email_users = array();
                    foreach ($new_users as $id_user) {
                        if (!in_array((int) $id_user, $cur_users)) {
                            $email_users[] = $id_user;
                        }
                    }

                    // Envoi e-mails de notification au client: 
                    if ($send_email && count($email_users)) {
                        $email_errors = $this->sendSignDistEmail($email_users, $email_content, $success);

                        if (count($email_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($email_errors, 'Echec de l\'envoi de l\'e-mail de notification au client');
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function cancelSignature(&$warnings = array())
    {
        $errors = array();

        if ($this->isLoaded()) {
            $this->set('type_signature', self::TYPE_NONE);
            $this->set('status', self::STATUS_CANCELLED);
            $errors = $this->update($warnings, true);
        }

        return $errors;
    }

    public function refuseSignature(&$warnings = array(), $motif = '')
    {
        $errors = array();

        if ($this->isLoaded()) {
            $this->set('type_signature', self::TYPE_NONE);
            $this->set('status', self::STATUS_REFUSED);
            $errors = $this->update($warnings, true);

            if (!count($errors)) {
                $signature = $this->getParentInstance();

                if (BimpObject::objectLoaded($signature)) {
                    $signature->addObjectLog('Signature refusée par le signataire "' . $this->getName() . '"', 'REFUSED_BY_' . strtoupper($this->getData('code')));
                    $obj = $signature->getObj();

                    if ($signature->isObjectValid($errors, $obj) && is_a($obj, 'BimpObject')) {
                        $msg = 'Signature refusée par le signataire "' . $this->getName() . '" pour le document "' . $signature->displayDocTitle() . '"';
                        if ($motif) {
                            $msg .= '<br/><b>Motif : </b>' . $motif;
                        }
                        $obj->addObjectLog($msg, 'SIGNATURE_' . strtoupper($signature->getData('doc_type')) . '_REFUSED_BY_' . strtoupper($this->getData('code')));
                    }

                    $signature->checkStatus($warnings);
                }
            }
        }

        return $errors;
    }

    public function reopenSignature(&$warnings = array())
    {
        $errors = array();

        if ($this->isLoaded()) {
            $this->set('type_signature', self::TYPE_NONE);
            $this->set('status', self::STATUS_NONE);
            $errors = $this->update($warnings, true);

            if (!count($errors)) {
                $signature = $this->getParentInstance();

                if (BimpObject::objectLoaded($signature)) {
                    $signature->addObjectLog('Signature réouverte pour le signataire "' . $this->getName() . '"', 'REOPENED_FOR_' . strtoupper($this->getData('code')));
                    $obj = $signature->getObj();

                    if ($signature->isObjectValid($errors, $obj) && is_a($obj, 'BimpObject')) {
                        $msg = 'Signature réouverte pour le signataire "' . $this->getName() . '" pour le document "' . $signature->displayDocTitle() . '"';
                        $obj->addObjectLog($msg, 'SIGNATURE_' . strtoupper($signature->getData('doc_type')) . '_REOPENED_FOR_' . strtoupper($this->getData('code')));
                    }

                    $signature->updateField('status', BimpSignature::STATUS_NONE);
                    $signature->checkStatus();
                    $warnings = BimpTools::merge_array($warnings, $signature->onReopen());
                }
            }
        }


        return $errors;
    }

    public function setSignedPapier($date_signed = '', &$warnings = array())
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if (!$date_signed) {
                $date_signed = date('Y-m-d H:i:s');
            }

            $errors = $this->validateArray(array(
                'status'         => self::STATUS_SIGNED,
                'date_signed'    => $date_signed,
                'type_signature' => self::TYPE_PAPIER
            ));

            if (!count($errors)) {
                $errors = $this->update($warnings, true);
            }
        }

        return $errors;
    }

    // Envois des e-mails: 

    public function sendSignDistEmail($users = null, $email_content = '', &$success = '', &$warnings = array())
    {
        $errors = array();

        if (is_null($users)) {
            $users = $this->getData('allowed_users_client');
        }

        if (empty($users)) {
            $errors[] = 'Aucun compte utilisateur sélectionné pour la signature électronique à distance';
        } else {
            $signature = $this->getParentInstance();

            if (!BimpObject::objectLoaded($signature)) {
                $errors[] = 'Signature liée absente';
            } else {
                $obj = $signature->getObj();

                if ($signature->isObjectValid($errors, $obj)) {
                    $emails = '';
                    $nOk = 0;

                    foreach ($users as $id_user) {
                        $bic_user = BimpCache::getBimpObjectInstance('bimpinterfaceclient', 'BIC_UserClient', $id_user);

                        if (!BimpObject::objectLoaded($bic_user)) {
                            $warnings[] = 'Le compte utilisateur client #' . $id_user . ' n\'existe pas';
                        } else {
                            $user_email = BimpTools::cleanEmailsStr($bic_user->getData('email'));

                            if ($user_email) {
                                $emails .= ($emails ? ',' : '') . $user_email;
                                $nOk++;
                            } else {
                                $warnings[] = 'Aucune adresse e-mail enregistrée pour le compte utilisateur client "' . $bic_user->getName() . '"';
                            }
                        }
                    }

                    if ($emails) {
                        $use_comm_email_as_from = false;
                        $comm_email = BimpTools::cleanEmailsStr($this->getOnSignedNotificationEmail($use_comm_email_as_from));
                        $subject = 'Signature en attente - Document: ' . $signature->displayDocTitle(true);

                        if (!$email_content) {
                            $email_content = $signature->getDefaultSignDistEmailContent('elec');
                        }

                        $email_content = $signature->replaceEmailContentLabels($email_content);

                        $from = ($use_comm_email_as_from ? $comm_email : '');

                        $bimpMail = new BimpMail($obj, $subject, BimpTools::cleanEmailsStr($emails), $from, $email_content, $comm_email);
                        $bimpMail->addFiles($signature->getMailFiles());

                        $mail_errors = array();
                        $bimpMail->send($mail_errors);

                        if (count($mail_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($mail_errors, 'Echec de l\'envoi de l\'e-mail de notification (Adresse(s): ' . $emails . ')');
                        } else {
                            $success .= ( $success ? '<br/>' : '') . 'E-mail de notification envoyé avec succès pour ' . $nOk . ' utilisateur(s)';
                            $msg = 'Ouverture de l\'accès à la signature électroniqe à distance effectuée pour le signataire "';
                            $msg .= $this->getData('nom') . '"' . ($this->getData('code') !== 'default' ? ' (' . $this->getData('label') . ')' : '');
                            $msg .= ' avec envoi d\'un e-mail de notification à ce signataire';
                            $signature->addObjectLog($msg, 'SIGN_DIST_OPEN_' . strtoupper($this->getData('code')));
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function sendRelanceEmail(&$errors = array(), &$warnings = array())
    {
        $users = $this->getData('allowed_users_client');
        $signature = $this->getParentInstance();

        if (!BimpObject::objectLoaded($signature)) {
            $errors[] = 'Signature liée absente';
        }

        if (empty($users)) {
            $errors[] = 'Aucun utilisateur client autorisé pour signature';
        }

        if (!count($errors)) {
            $emails = '';

            $client = $this->getChildObject('client');

            if (!BimpObject::objectLoaded($client)) {
                $errors[] = 'Client absent';
                return false;
            }

            $obj = $this->getObj();

            if (!$this->isObjectValid($errors, $obj)) {
                return false;
            }

            foreach ($users as $id_user_client) {
                $userClient = BimpCache::getBimpObjectInstance('bimpinterfaceclient', 'BIC_UserClient', $id_user_client);

                if (BimpObject::objectLoaded($userClient)) {
                    $email = BimpTools::cleanEmailsStr($userClient->getData('email'));

                    if ($email) {
                        if (BimpValidate::isEmail($email)) {
                            $emails .= ($emails ? ',' : '') . $email;
                        } else {
                            $warnings[] = 'Adresse e-mail invalide pour l\'utilisteur client #' . $userClient->id . ': "' . $email . '"';
                        }
                    } else {
                        $warnings[] = 'Adresse e-mail absente pour l\'utilisteur client #' . $userClient->id;
                    }
                }
            }

            if (!$emails) {
                $errors[] = 'Aucune adresse e-mail valide pour envoi de la relance';
            } else {
                $use_comm_email_as_from = false;
                $commercial_email = $this->getOnSignedNotificationEmail($use_comm_email_as_from);
                $date_open = $this->getData('date_open');
                $doc_label = $this->displayDocType() . ' ' . $this->displayDocRef();
                $url = self::getPublicBaseUrl(false, BimpPublicController::getPublicEntityForObjectSecteur($obj));

                $subject = 'Client ' . $client->getRef() . ' - ' . $doc_label . ' - Signature en attente';

                $msg = 'Cher client, <br/><br/>';
                $msg .= 'Le ' . date('d / m / Y', strtotime($date_open)) . ' nous avons mis à votre disposition le document "' . $doc_label . '" pour signature.<br/><br/>';
                $msg .= 'Sauf erreur de notre part nous ne l\'avons pas reçu en retour.<br/><br/>';
                $msg .= 'Il convient que vous procédiez rapidement à cette signature afin que nous puissions donner suite à votre dossier.<br/><br/>';
                $msg .= 'Pour rappel, vous pouvez effectuer la signature de ce document directement depuis <a href="' . $url . '">votre espace client</a><br/><br/>';
                $msg .= 'Nous vous remercions par avance.<br/><br/>';
                $msg .= 'Cordialement,<br/><br/>';
                $msg .= 'L\'équipe BIMP';

                $from = ($use_comm_email_as_from ? $commercial_email : '');

                $obj = $this->getObj();
                if (is_a($obj, 'Bimp_Propal')) {
                    $sav = $obj->getSav();
                    if (BimpObject::objectLoaded($sav)) {
                        $obj = $sav;
                    }
                }
                $bimpMail = new BimpMail($obj, $subject, $emails, $from, $msg, $commercial_email, $commercial_email);
                $bimpMail->addFiles($signature->getMailFiles());

                return $bimpMail->send($errors, $warnings);
            }
        }

        return false;
    }

    public function sendOnSignedNotificationEmail($type = 'signed', &$errors = array(), &$warnings = array())
    {
        if ((int) $this->getData('type') !== self::TYPE_CLIENT) {
            return array();
        }

        $comm_email = $this->getOnSignedNotificationEmail();
        $signature = $this->getParentInstance();

        if (!BimpObject::objectLoaded($signature)) {
            $errors[] = 'Signature liée absente';
            return $errors;
        }

        $obj = $signature->getObj();

        if (!$signature->isObjectValid($errors, $obj)) {
            return $errors;
        }

        if ($comm_email) {
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                $obj_label = $signature->displayDocType() . ' ' . $signature->displayDocRef();
                $subject = '';
                $msg = '';

                switch ($type) {
                    case 'signed':
                        $subject = 'Signature effectuée - ' . $obj_label . ' - Client: ' . $client->getRef() . ' ' . $client->getName();

                        $msg = 'Bonjour,<br/><br/>';
                        $msg .= 'La signature du document "' . $obj_label . '" a été effectuée.<br/><br/>';
                        if (is_a($obj, 'BimpObject')) {
                            $msg .= '<b>Objet lié: </b>' . $obj->getLink() . '<br/>';
                        }
                        $msg .= '<b>Signature: </b>' . $signature->getLink(array(), 'private') . '<br/><br/>';

                        $msg .= '<b>Date de la signature: </b>' . date('d / m / Y à H:i:s', strtotime($this->getData('date_signed'))) . '<br/>';
                        $msg .= '<b>Type signature: </b>' . BimpTools::getArrayValueFromPath(self::$types_signatures, $this->getData('type_signature') . '/label', 'inconnue') . '<br/>';
                        if ($this->getData('label') !== 'Signataire') {
                            $msg .= '<b>Type signataire: </b>' . $this->getData('label') . '<br/>';
                        }
                        $msg .= '<b>Nom Signataire: </b> ' . $this->getData('nom') . '<br/>';
                        $msg .= '<b>Adresse e-mail signataire: </b>' . $this->getData('email') . '<br/><br/>';

                        if (method_exists($obj, 'getOnSignedEmailExtraInfos')) {
                            $msg .= $obj->getOnSignedEmailExtraInfos($signature->getData('doc_type'));
                        }
                        break;

                    case 'refused':
                        $subject = 'Signature refusée - ' . $obj_label . ' - Client: ' . $client->getRef() . ' ' . $client->getName();
                        $msg = 'Bonjour,<br/><br/>';
                        $msg .= 'La signature du document "' . $obj_label . '" a été refusée par le signataire "' . $this->getName() . '".<br/><br/>';
                        if (is_a($obj, 'BimpObject')) {
                            $msg .= '<b>Objet lié: </b>' . $obj->getLink() . '<br/>';
                        }
                        $msg .= '<b>Signature: </b>' . $signature->getLink(array(), 'private') . '<br/><br/>';
                        break;
                }

                if ($subject && $msg) {
                    mailSyn2($subject, $comm_email, '', $msg);
                }
            }
        }
    }

    public function sendEmail($content, $subject = '')
    {
        $errors = array();

        $signature = $this->getParentInstance();

        if (!BimpObject::objectLoaded($signature)) {
            $errors[] = 'Signature liée absente pour ce signataire';
        } else {
            if (!$subject) {
                $subject = 'Signature en attente - Document: ' . $signature->displayDocTitle(true);
            }

            $content = $signature->replaceEmailContentLabels($content);

            $email = $this->getData('email');
            if (!$email) {
                $errors[] = 'Adresse e-mail absente pour ce signataire';
            } else {
                $email = BimpTools::cleanEmailsStr($email);
            }

            if (!count($errors)) {
                $files = $signature->getMailFiles();

                if (!empty($files)) {
                    $bimpMail = new BimpMail($signature->getObj(), $subject, $email, '', $content);
                    $bimpMail->addFiles($files);

                    $mail_errors = array();
                    $bimpMail->send($mail_errors);

                    if (count($mail_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($mail_errors, 'Echec de l\'envoi de l\'e-mail de notification (Adresse(s): ' . $email . ')');
                    } else {
                        $msg = 'Document envoyé par e-mail au signataire "';
                        $msg .= $this->getData('nom') . '"' . ($this->getData('code') !== 'default' ? ' (' . $this->getData('label') . ')' : '');
                        $signature->addObjectLog($msg, 'EMAIL_SENT_' . strtoupper($this->getData('code')));
                    }
                } else {
                    $errors[] = 'Document PDF à signer absent';
                }
            }
        }

        return $errors;
    }

    // Actions: 

    public function actionSignDistAccess($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $auto_open = (int) BimpTools::getArrayValueFromPath($data, 'auto_open', 0);
        $new_users = BimpTools::getArrayValueFromPath($data, 'allowed_users_client', array());
        $new_user_email = BimpTools::getArrayValueFromPath($data, 'new_user_email', '');
        $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', '');

        if (!$new_user_email) {
            $errors[] = 'Veuillez saisir une adresse e-mail pour la création du compte utilisateur client';
        } elseif (!BimpValidate::isEmail($new_user_email)) {
            $errors[] = 'L\'adresse e-mail pour la création du compte utilisateur client est invalide';
        }

        $errors = $this->openSignDistAccess(true, $email_content, $auto_open, $new_users, $new_user_email, $warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSignElec($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Signature électronique effectuée avec succès';
        $success_callback = '';

        $signature = $this->getParentInstance();

        if (!BimpObject::objectLoaded($signature)) {
            $errors[] = 'Signature liée absente';
        } else {
            $obj = $signature->getObj();

            if ($signature->isObjectValid($errors, $obj)) {
                $nom = BimpTools::getArrayValueFromPath($data, 'nom', $this->getData('nom'));
                $email = BimpTools::getArrayValueFromPath($data, 'email', $this->getData('email'));
                $fonction = BimpTools::getArrayValueFromPath($data, 'fonction', $this->getData('fonction'));
                $ville = BimpTools::getArrayValueFromPath($data, 'ville', '');

                if (!$email) {
                    $errors[] = 'Veuillez saisir l\'adresse e-mail du signataire';
                }

                if (!$nom) {
                    $errors[] = 'Veuillez saisir le nom du signataire';
                }

                $signature_image = BimpTools::getArrayValueFromPath($data, 'signature', '');

                if (!$signature_image) {
                    $errors[] = 'Signature électronique absente';
                }

                $fonction_required = true;
                if ((int) $this->getData('type') === self::TYPE_CLIENT) {
                    if (!$this->isClientCompany()) {
                        $fonction_required = false;
                    }
                }

                if (!$fonction && $fonction_required) {
                    $errors[] = 'Veuillez saisir la fonction du signataire';
                }

                if (!$ville && $this->isVilleRequired()) {
                    $errors[] = 'Veuillez saisir le lieu de signature';
                }

                if ($signature->hasMultipleFiles()) {
                    $selected_file = BimpTools::getArrayValueFromPath($data, 'selected_file', '');
                    if (!$selected_file) {
                        $errors[] = 'Veuillez sélectionner le document que vous souhaitez signer';
                    } else {
                        $file_errors = $signature->setSelectedFile($selected_file, $this, true);
                        if (count($file_errors)) {
                            $msg = 'Signature électronique non possible pour le document sélectionné. Veuillez enregistrer la signature sous la forme d\'un scan papier';
                            $errors[] = BimpTools::getMsgFromArray($file_errors, $msg);
                        }
                    }
                }

                if (!count($errors)) {
                    $this->set('type_signature', self::TYPE_ELEC);
                    $this->set('status', self::STATUS_SIGNED);
                    $this->set('date_signed', date('Y-m-d H:i:s'));
                    $this->set('nom', $nom);
                    $this->set('fonction', $fonction);
                    $this->set('ville', $ville);
                    $this->set('email', $email);
                    $this->set('base_64_signature', $signature_image);

                    $errors = $this->update($warnings, true);
                    if (!count($errors)) {
                        $doc_errors = $signature->writeSignatureOnDoc($this);

                        if (count($doc_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($doc_errors, 'Echec de l\'écriture de la signature sur le document PDF');
                        } else {
                            $url = $signature->getDocumentUrl(true);

                            if ($url) {
                                $success_callback = 'window.open(\'' . $url . '\');bimp_reloadPage();';
                            }
                        }

                        $signature->checkStatus($warnings, $warnings);
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionSignDocuSign($data, &$success)
    {
//        $errors = array();
//        $warnings = array();
//        $success = 'Signature électronique effectuée avec succès';
//        $success_callback = '';
//
//        $obj = $this->getObj();
//
//        if ($this->isObjectValid($errors, $obj)) {
//
//            require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';
//            $api = BimpAPI::getApiInstance('docusign');
//            if (is_a($api, 'DocusignAPI')) {
//
//                $params = array(
//                    'id_account'  => $data['id_account'],
//                    'id_envelope' => $data['id_envelope']
//                );
//
//                $envelope = $api->getEnvelope($params, $errors, $warnings);
//                print_r($envelope);
//
//                if (!count($errors)) {
//                    $this->set('signed', 1);
//                    $this->set('date_signed', date('Y-m-d H:i:s'));
//                    $errors = $this->update($warnings, true);
//                    if (!count($errors)) {
//
//                        if (method_exists($obj, 'onSigned')) {
//                            $warnings = array_merge($warnings, $obj->onSigned($this, $data));
//                        }
//                    }
//                }
//            }
//        }
//
//        return array(
//            'errors'           => $errors,
//            'warnings'         => $warnings,
//            'success_callback' => $success_callback
//        );
    }

    public function actionSignDist($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Signature enregistrée avec succès';
        $success_callback = '';

        $signature = $this->getParentInstance();

        if (!BimpObject::objectLoaded($signature)) {
            $errors[] = 'Signature liée absente';
        } else {
            $obj = $signature->getObj();

            if ($signature->isObjectValid($errors, $obj)) {
                global $userClient;

                if (!BimpObject::objectLoaded($userClient)) {
                    $errors[] = 'Aucun utilisateur connecté';
                } else {
                    $nom = BimpTools::getArrayValueFromPath($data, 'nom', $this->getData('nom'));

                    if (!$nom) {
                        $errors[] = 'Veuillez saisir votre nom';
                    }

                    $fonction = '';

                    if ($this->isClientCompany()) {
                        $fonction = BimpTools::getArrayValueFromPath($data, 'fonction', $this->getData('fonction'));

                        if (!$fonction) {
                            $errors[] = 'Veuillez saisir votre fonction';
                        }
                    }

                    $signature_image = BimpTools::getArrayValueFromPath($data, 'signature', '');

                    if (!$signature_image) {
                        $errors[] = 'Signature électronique absente';
                    }

                    $code_sms_infos = array();

                    if ((int) $this->getData('need_sms_code')) {
                        $code_sms_infos = $this->getData('code_sms_infos');
                        $code = BimpTools::getArrayValueFromPath($data, 'code_sms', '');

                        if (!$code) {
                            $errors[] = 'Veuillez saisir votre reçu par SMS';
                        } elseif ($code != $code_sms_infos['code']) {
                            $errors[] = 'Code SMS invalide';
                        } else {
                            $code_sms_infos['dt_confirmed'] = date('Y-m-d H:i:s');
                        }
                    }

                    $ville = BimpTools::getArrayValueFromPath($data, 'ville', '');
                    if (!$ville && $this->isVilleRequired()) {
                        $errors[] = 'Veuillez saisir le lieu de signature';
                    }

                    if ($signature->hasMultipleFiles()) {
                        $selected_file = BimpTools::getArrayValueFromPath($data, 'selected_file', '');
                        if (!$selected_file) {
                            $errors[] = 'Veuillez sélectionner le document que vous souhaitez signer';
                        } else {
                            $file_errors = $signature->setSelectedFile($selected_file, $this, true);

                            if (count($file_errors)) {
                                $msg = 'La signature électronique du document que vous avez sélectionné n\'est pas possible.';
                                $msg .= '<br/>Nous vous remercions de bien vouloir nous excuser pour ce désagrément ';
                                $msg .= 'et de nous retourner le document signé par e-mail ou par courrier';
                                $errors[] = $msg;
                            }
                        }
                    }

                    if (!count($errors)) {
                        require_once DOL_DOCUMENT_ROOT . '/synopsistools/class/divers.class.php';
                        $this->set('type_signature', self::TYPE_DIST);
                        $this->set('status', self::STATUS_SIGNED);
                        $this->set('date_signed', date('Y-m-d H:i:s'));
                        $this->set('nom', $nom);
                        $this->set('fonction', $fonction);
                        $this->set('ville', $ville);
                        $this->set('email', $userClient->getData('email'));
                        $this->set('id_user_client_signataire', $userClient->id);
                        $this->set('base_64_signature', $signature_image);
                        $this->set('ip_signataire', synopsisHook::getUserIp());
                        $this->set('code_sms_infos', $code_sms_infos);

                        $errors = $this->update($warnings, true);

                        if (!count($errors)) {
                            $doc_errors = $signature->writeSignatureOnDoc($this);

                            if (count($doc_errors)) {
                                $warnings[] = 'Echec de l\'écriture de la signature sur le document PDF';
                            } else {
                                $url = $signature->getDocumentUrl(true, 'public');

                                if ($url) {
                                    $success_callback = 'window.open(\'' . $url . '\');bimp_reloadPage();';
                                }
                            }

                            $signature->checkStatus($warnings, $warnings);
                            $this->sendOnSignedNotificationEmail('signed');
                        }
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionSetSignatureParams($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Document signé régénéré avec succès';
        $success_callback = '';

        $errors = $this->writeSignatureOnDoc($this, $data);

        if (!count($errors)) {
            $url = $this->getDocumentUrl(true);

            if ($url) {
                $success_callback = 'window.open(\'' . $url . '\');';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback,
            'allow_reset_form' => true
        );
    }

    public function actionRefuse($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Refus de la signature enregistré avec succès';
        $sc = '';

        $errors = $this->refuseSignature($warnings, BimpTools::getArrayValueFromPath($data, 'motif', ''));

        if (!count($errors)) {
            $sc = 'bimp_reloadPage();';
            if (BimpCore::isContextPublic()) {
                $this->sendOnSignedNotificationEmail('refused');
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Réouverture de la signature effectuée avec succès';
        $sc = '';

        $errors = $this->reopenSignature($warnings);

        if (!count($errors)) {
            $sc = 'bimp_reloadPage();';
        }
        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionSendSmsCode($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Code envoyé avec succès';
        $success_callback = '';

        global $conf;
        if (!empty($conf->global->MAIN_DISABLE_ALL_SMS)) {
            $errors[] = 'Envoi des SMS désactivé pour le moment';
        } else {
            $num = BimpTools::getArrayValueFromPath($data, 'num_tel_selected', '');

            if ($num == 'other') {
                $num = BimpTools::getArrayValueFromPath($data, 'other_num', '');
            }

            if (!$num) {
                $errors[] = 'Veuillez sélectionner ou saisir une numéro de téléphone mobile';
            } else {
                $num = str_replace(array(' ', '-', '/', '_', '.'), array('', '', '', '', ''), $num);

                if (preg_match('/^(\+33|0)((6|7)([0-9]{8}))$/', $num, $matches)) {
                    $num = '+33' . $matches[2];
                    $success .= ' (' . $num . ')';
                } else {
                    $errors[] = 'Le numéro de téléphone sélectionné ou saisi ne semble par être un numéro de téléphone mobile valide';
                }
            }

            if (!count($errors)) {
                $code = BimpTools::randomPassword(4);
                require_once(DOL_DOCUMENT_ROOT . "/core/class/CSMSFile.class.php");

                $text = 'Votre code pour la signature à distance du document "' . strip_tags($this->displayDocTitle()) . '": ' . $code;

                $smsfile = new CSMSFile($num, 'BIMP', $text);
                if (!$smsfile->sendfile()) {
                    $errors[] = 'Echec de l\'envoi du sms.';
                } else {
                    $infos = array(
                        'code'         => $code,
                        'num_tel'      => $num,
                        'dt_send'      => date('Y-m-d H:i:s'),
                        'dt_confirmed' => ''
                    );

                    $this->updateField('code_sms_infos', $infos);

                    $w = array();
                    $this->update($w, true);

                    $success_callback = 'setTimeout(function() {';
                    $success_callback .= $this->getJsActionOnclick('signDist', array(), array(
                        'form_name' => 'sign_dist'
                    ));
                    $success_callback .= '}, 500);';
                }
            }
        }


        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    // Overrides: 

    public function validate()
    {
        $errors = array();
        switch ($this->getData('type')) {
            case self::TYPE_CLIENT:
                $client = $this->getChildObject('client');
                $contact = $this->getChildObject('contact');

                if (!BimpObject::objectLoaded($client)) {
                    $errors[] = 'Client absent';
                } elseif (BimpObject::objectLoaded($contact)) {
                    if (!$this->getData('nom')) {
                        $this->set('nom', $contact->getName());
                    }
                    if (!$this->getData('email')) {
                        $this->set('email', $contact->getData('email'));
                    }

                    if (!$this->getData('fonction') && $this->isClientCompany()) {
                        $this->set('fonction', $contact->displayData('poste', 'default', false, true));
                    }
                } else {
                    if (!$this->getData('nom')) {
                        $this->set('nom', $client->getName());
                    }
                    if (!$this->getData('email')) {
                        $this->set('email', $client->getData('email'));
                    }
                }
                break;

            case self::TYPE_USER:
                $user = $this->getChildObject('user');
                if (BimpObject::objectLoaded($user)) {
                    if (!$this->getData('nom')) {
                        $this->set('nom', $user->getName());
                    }
                    if (!$this->getData('email')) {
                        $this->set('email', $user->getData('email'));
                    }
                    if (!$this->getData('fonction')) {
                        $this->set('fonction', $user->displayData('job', 'default', false, true));
                    }
                }
                break;
        }

        if (!count($errors)) {
            $errors = parent::validate();
        }

        return $errors;
    }
}
