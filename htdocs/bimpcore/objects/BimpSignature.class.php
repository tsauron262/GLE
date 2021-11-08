<?php

class BimpSignature extends BimpObject
{

    const TYPE_DIST = 1;
    const TYPE_PAPIER = 2;
    const TYPE_ELEC = 3;

    public static $types = array(
        -1                => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('danger')),
        0                 => array('label' => 'En attente de signature', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::TYPE_DIST   => array('label' => 'Signature à distance', 'icon' => 'fas_sign-in-alt'),
        self::TYPE_PAPIER => array('label' => 'Signature papier', 'icon' => 'fas_file-download'),
        self::TYPE_ELEC   => array('label' => 'Signature électronique', 'icon' => 'fas_file-signature')
    );
    public static $empty_base_64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAyYAAAFeCAYAAABw2Qu3AAAEXElEQVR4nO3BAQEAAACCIP+vbkhAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMC7ATotAAEPLajTAAAAAElFTkSuQmCC';

    // Droits users: 

    public function canClientView()
    {
        global $userClient;

        if (!BimpObject::objectLoaded($userClient)) {
            return 0;
        }

        if ($this->isLoaded()) {
            if ((int) $userClient->getData('id_client') == (int) $this->getData('id_client')) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

    public function canSetAction($action)
    {
        switch ($action) {
            case 'signDist':
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

    public function canEditField($field_name)
    {
        if (BimpCore::isContextPublic()) {
            global $userClient;

            if (BimpObject::objectLoaded($userClient)) {
                if ((int) $userClient->getData('id_client') === (int) $this->getData('id_client')) {
                    if (in_array($field_name, array('nom_signataire', 'fonction_signataire'))) {
                        return 1;
                    }
                }
            }

            return 0;
        }

        return parent::canEditField($field_name);
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

    public function isDeletable($force_delete = false, &$errors = [])
    {
        if (!$force_delete && (int) $this->getData('signed')) {
            return 0;
        }

        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('setSigned', 'allowed_users_client', 'signPapier', 'signDist', 'cancel'))) {
            if (!$this->isLoaded($errors)) {
                return 0;
            }
        }

        switch ($action) {
            case 'signDistAccess':
            case 'signPapier':
            case 'signDist':
            case 'signElec':
                if ((int) $this->getData('signed')) {
                    $errors[] = 'Signature déjà effectuée';
                    return 0;
                }
                if ((int) $this->getData('type') < 0) {
                    $errors[] = 'Signature annulée';
                    return 0;
                }
                return 1;

            case 'cancel':
                if ((int) $this->getData('type') < 0) {
                    $errors[] = 'Signature déjà annulée';
                    return 0;
                }
                if ((int) $this->getData('signed')) {
                    $errors[] = 'Signature déjà affectuée';
                    return 0;
                }
                return 1;

            case 'setSignatureParams':
                if (!(int) $this->getData('signed')) {
                    $errors[] = 'Signature non effectuée';
                    return 0;
                }
                if (!in_array($this->getData('type'), array(self::TYPE_DIST, self::TYPE_ELEC))) {
                    $errors[] = 'Type invalide';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function isObjectValid(&$errors = array(), $obj = null)
    {
        if (is_null($obj)) {
            $obj = $this->getObj();
        }

        if (!is_a($obj, 'BimpObject')) {
            $errors[] = 'Objet lié invalide';
            return 0;
        }

        if (!BimpObject::objectLoaded($obj)) {
            $errors[] = 'ID ' . $obj->getLabel('of_the') . ' absent';
            return 0;
        }

        return 1;
    }

    public function isClientCompany()
    {
        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            return $client->isCompany();
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
        if ((int) $this->getData('signed') && in_array((int) $this->getData('type'), array(self::TYPE_DIST, self::TYPE_ELEC))) {
            return 1;
        }

        return 0;
    }

    // Getters params: 

    public function getSignButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('signDistAccess') && $this->canSetAction('signDistAccess')) {
            $buttons[] = array(
                'label'   => 'Accès signature à distance',
                'icon'    => 'fas_sign-in-alt',
                'onclick' => $this->getJsActionOnclick('signDistAccess', array(), array(
                    'form_name' => 'open_sign_dist'
                ))
            );
        }

        if ($this->isActionAllowed('signPapier') && $this->canSetAction('signPapier')) {
            $buttons[] = array(
                'label'   => 'Dépôser document signé',
                'icon'    => 'fas_file-download',
                'onclick' => $this->getJsLoadModalForm('sign_papier', 'Dépôt document signé')
            );
        }

        if ($this->isActionAllowed('signElec') && $this->canSetAction('signElec')) {
            $buttons[] = array(
                'label'   => 'Signature électronique',
                'icon'    => 'fas_file-signature',
                'onclick' => $this->getJsActionOnclick('signElec', array(), array(
                    'form_name' => 'sign_elec'
                ))
            );
        }

        return $buttons;
    }

    public function getActionsButtons()
    {
        $buttons = $this->getSignButtons();

        if ($this->isActionAllowed('setSignatureParams') && $this->canSetAction('setSignatureParams')) {
            $buttons[] = array(
                'label'   => 'Ajouster la Signature sur le document',
                'icon'    => 'fas_arrows-alt',
                'onclick' => $this->getJsActionOnclick('setSignatureParams', array(), array(
                    'form_name' => 'signature_params'
                ))
            );
        }

        return $buttons;
    }

    public function getListExtraBtn()
    {
        return $this->getActionsButtons();
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
            $cache_key = 'bimp_signature_' . $this->id . '_default_allowed_users_client';

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

    // Getters données: 

    public function getObj()
    {
        $module = $this->getData('obj_module');
        $name = $this->getData('obj_name');
        $id_obj = (int) $this->getData('id_obj');

        if ($module && $name) {
            if ($id_obj) {
                return BimpCache::getBimpObjectInstance($module, $name, $id_obj);
            }

            return BimpObject::getInstance($module, $name);
        }

        return null;
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
                $msg .= 'Le client ne dispose d\'aucun compte utilisateur valide pour la signature à distance de ce document via l\'espace client BIMP.<br/>';
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

    public function getSignatureParams()
    {
        $obj = $this->getObj();

        $errors = array();
        if ($this->isObjectValid($errors, $obj)) {
            if (method_exists($obj, 'getSignatureParams')) {
                return $obj->getSignatureParams($this->getData('doc_type'));
            }
        }

        return array();
    }

    public function getSignatureParamsFormValues()
    {
        $params = $this->getSignatureParams();

        foreach ($params as $key => $value) {
            $params[$key] = (int) $value;
        }

        return array(
            'fields' => $params
        );
    }

    public function getCommercialEmail()
    {
        $obj = $this->getObj();

        $errors = array();
        if ($this->isObjectValid($errors, $obj)) {
            if (method_exists($obj, 'getSignatureCommercialEmail')) {
                return $obj->getSignatureCommercialEmail($this->getData('doc_type'));
            }
        }

        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            return $client->getCommercialEmail();
        }

        return '';
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

    // Getters Array: 

    public function getTypeInputOptions()
    {
        $types = self::$types;

        unset($types[0]);

        return $types;
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

    // Getters Doc infos: 

    public function getDocumentFileName($signed = false)
    {
        $doc_type = $this->getData('doc_type');

        if ($doc_type) {
            $obj = $this->getObj();
            $errors = array();

            if ($this->isObjectValid($errors, $obj)) {
                if (method_exists($obj, 'getSignatureDocFileName')) {
                    return $obj->getSignatureDocFileName($doc_type, $signed);
                }
            }
        }

        return '';
    }

    public function getDocumentUrl($signed = false, $forced_context = '')
    {
        $doc_type = $this->getData('doc_type');

        if ($doc_type) {
            $obj = $this->getObj();
            $errors = array();

            if ($this->isObjectValid($errors, $obj)) {
                if (method_exists($obj, 'getSignatureDocFileUrl')) {
                    return $obj->getSignatureDocFileUrl($doc_type, $forced_context, $signed);
                }
            }
        }

        return '';
    }

    public function getDocumentFileDir()
    {
        $doc_type = $this->getData('doc_type');

        if ($doc_type) {
            $obj = $this->getObj();
            $errors = array();

            if ($this->isObjectValid($errors, $obj)) {
                $dir = '';
                if (method_exists($obj, 'getSignatureDocFileDir')) {
                    $dir = $obj->getSignatureDocFileDir($doc_type);
                } else {
                    $dir = $obj->getFilesDir();
                }

                return $dir;
            }
        }

        return '';
    }

    public function getDocumentFilePath($signed = false)
    {
        $doc_type = $this->getData('doc_type');

        if ($doc_type) {
            $obj = $this->getObj();
            $errors = array();

            if ($this->isObjectValid($errors, $obj)) {
                $dir = '';
                if (method_exists($obj, 'getSignatureDocFileDir')) {
                    $dir = $obj->getSignatureDocFileDir($doc_type);
                } else {
                    $dir = $obj->getFilesDir();
                }

                if ($dir) {
                    $file = $this->getDocumentFileName($signed);

                    if ($file) {
                        return $dir . $file;
                    }
                }
            }
        }

        return '';
    }

    // Affichages: 

    public function displayObj()
    {
        $obj = $this->getObj();

        if (BimpObject::objectLoaded($obj)) {
            return $obj->getLink();
        }

        return '';
    }

    public function displayDocType()
    {
        $type = $this->getData('doc_type');

        if ($type) {
            $obj = $this->getObj();

            if (is_a($obj, 'BimpObject')) {
                return $obj->getConf('signatures/' . $type . '/label', $type);
            }
        }

        return '';
    }

    public function displayDocRef()
    {
        $obj = $this->getObj();

        $errors = array();

        if ($this->isObjectValid($errors, $obj)) {
            if (method_exists($obj, 'getSignatureDocRef')) {
                return $obj->getSignatureDocRef($this->getData('doc_type'));
            } else {
                $ref = $obj->getRef();

                if ($ref) {
                    return $ref;
                }
            }
        }

        if ((int) $this->getData('id_obj')) {
            return '#' . $this->getData('id_obj');
        }

        return '';
    }

    public function dispayPublicSign()
    {
        if ($this->isLoaded()) {
            if ($this->isActionAllowed('signDist')) {
                $html = '';
                if ($this->canSetAction('signDist')) {
                    $onclick = $this->getJsActionOnclick('signDist', array(), array(
                        'form_name' => 'sign_dist'
                    ));

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

                return $html;
            }
        }

        return '';
    }

    public function displayPublicDocument()
    {
        if ($this->isLoaded() && (int) $this->getData('type') >= 0) {
            $obj = $this->getObj();

            if ($this->isObjectValid()) {
                if ($this->can('view')) {
                    if (method_exists($obj, 'getSignatureDocFileDir') && method_exists($obj, 'getSignatureDocFileUrl')) {
                        $dir = $obj->getSignatureDocFileDir($this->getData('doc_type'));
                        $file_name = $obj->getSignatureDocFileName($this->getData('doc_type'), (int) $this->getData('signed'));
                        $file = $dir . $file_name;

                        if ($file && file_exists($file)) {
                            $url = $obj->getSignatureDocFileUrl($this->getData('doc_type'), 'public', (int) $this->getData('signed'));

                            if ($url) {
                                $check = false;
                                if (BimpCore::isContextPublic()) {
                                    global $userClient;

                                    if (BimpObject::objectLoaded($userClient)) {
                                        $allowed = $this->getData('allowed_users_client');
                                        if ($userClient->isAdmin() || (is_array($allowed) && in_array($userClient->id, $allowed))) {
                                            $check = true;
                                        }
                                    }
                                } else {
                                    $check = true;
                                }

                                if ($check) {
                                    $html = '<span class="btn btn-default" onclick="window.open(\'' . $url . '\')">';
                                    $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . 'Document PDF';
                                    $html .= '</span>';
                                } else {
                                    $html = '<span class="btn btn-default disabled bs-popover" onclick=""';
                                    $html .= BimpRender::renderPopoverData('Vous n\'avez pas la permission de voir ce document');
                                    $html .= '>';
                                    $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . 'Document PDF';
                                    $html .= '</span>';
                                }
                                return $html;
                            }
                        }
                    }
                }
            }

            return '<span class="warning">Non disponible</span>';
        }

        return '';
    }

    // Rendus HTML: 

    public function renderSignatureElecForm($extra_inputs = array())
    {
        
    }

    public function renderSignatureInput()
    {
        
    }

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ((int) $this->getData('signed')) {
            $html .= '<div class="object_header_infos">';
            $html .= 'Signature effectuée le ' . date('d / m / Y', strtotime($this->getData('date_signed'))) . ' par <b>' . $this->getData('nom_signataire') . '</b>';
            $html .= '</div>';
        }

        return $html;
    }

    public function renderHeaderExtraRight()
    {
        $html = '';

        $file = $this->getDocumentFilePath();
        $file_signed = $this->getDocumentFilePath(true);

        $file_url = '';
        $file_signed_url = '';

        if ($file && file_exists($file)) {
            $file_url = $this->getDocumentUrl();
        }

        if ($file_signed && file_exists($file_signed)) {
            $file_signed_url = $this->getDocumentUrl(true);
        }

        if ($file_url || $file_signed_url) {
            $html .= '<div class="buttonsContainer align-right">';

            if ($file_url) {
                $html .= '<span class="btn btn-default" onclick="window.open(\'' . $file_url . '\')">';
                $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . 'Document';
                $html .= '</span>';
            }
            if ($file_signed_url) {
                $html .= '<span class="btn btn-default" onclick="window.open(\'' . $file_signed_url . '\')">';
                $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . 'Document signé';
                $html .= '</span>';
            }

            $html .= '</div>';
        }

        return $html;
    }

    public function renderHeaderStatusExtra()
    {
        $html = '';
        if ((int) $this->getData('signed')) {
            $html .= '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Signé</span>';
            $html .= '<br/>' . $this->displayData('type', 'default', false);
        } elseif ((int) $this->getData('type') === -1) {
            $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Annulée</span>';
        } else {
            $html .= '<span class="warning">' . BimpRender::renderIcon('fas_hourglass-start', 'iconLeft') . 'Signature en attente</span>';
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

    public function renderRelancesReportsList()
    {
        if ($this->isLoaded()) {
            $list = new BC_ListTable(BimpObject::getInstance('bimpdatasync', 'BDS_ReportLine'), 'linked_object', 1, null, 'Rapports des relances', 'far_file-alt');
            $list->addFieldFilterValue('obj_module', 'bimpcore');
            $list->addFieldFilterValue('obj_name', 'BimpSignature');
            $list->addFieldFilterValue('id_obj', $this->id);

            return $list->renderHtml();
        }

        return '';
    }

    // Traitements:

    public function sendClientEmailForFarSign($email)
    {
        $errors = array();

        if ($email && BimpValidate::isEmail($email)) {
            $obj = $this->getObj();

            if ($this->isObjectValid($errors, $obj)) {
                $new_password = '';

                for ($i = 0; $i < 100; $i++) {
                    $new_password = $this->generateRandomPassword(5);
                    if (!(int) $this->db->getCount('bimpcore_signature', 'public_access_code = "' . $new_password . '"', 'rowid')) {
                        break;
                    }
                }

                $dt = new DateTime();
                $date_from = $dt->format('Y-m-d H:i:s');
                $dt->add(new DateInterval("P4D"));
                $date_to = $dt->format('Y-m-d H:i:s');
            }

            if (!is_a($obj, 'BimpObject')) {
                $errors[] = 'Objet lié invalide';
            } else {
                $subject = BimpTools::ucfirst($obj->getLabel()) . ' - ' . $obj->getRef();

                $msg = 'Bonjour,<br/><br/>';
                $msg .= 'Merci de signer votre ' . $obj->getLabel() . ' à l\'adresse suivante: ';
                $msg .= '<a href="' . DOL_URL_ROOT . '/bimptechnique/public">' . DOL_URL_ROOT . '/bimptechnique/public</a>';
                $msg .= ' en entrant votre nom ainsi que le mot de passe suivant: <b>' . $new_password . '</b>.<br/><br/>';
                $msg .= 'Cet accès n\'est valable que 4 Jours calandaires.<br/><br/>';
                $msg .= 'Cordialement';
            }

            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du mot de passe');
            } else {
                $to = BimpTools::cleanEmailsStr($this->getData('email_signature'));
                $commercial = $this->getCommercialClient();
                $tech = $this->getChildObject('user_tech');

                $email_tech = '';
                $email_comm = '';

                if (BimpObject::objectLoaded($tech)) {
                    $email_tech = $tech->getData('email');
                }

                if (BimpObject::objectLoaded($commercial)) {
                    $email_comm = $commercial->getData('email');
                }

                $reply_to = ($email_comm ? $email_comm : $email_tech);
                $cc = ''; //($email_comm ? $email_tech . ', ' : '') . 't.sauron@bimp.fr, f.martinez@bimp.fr';

                $bimpMail = new BimpMail($subject, $to, '', $msg, $reply_to, $cc);

                global $conf;

                $file = $conf->ficheinter->dir_output . '/' . $this->dol_object->ref . '/' . $this->dol_object->ref . '.pdf';
                if (file_exists($file)) {
                    $bimpMail->addFile(array($file, 'application/pdf', $this->dol_object->ref . '.pdf'));
                }

                $mail_errors = array();
                $bimpMail->send($mail_errors);

                sleep(3);

                if (count($mail_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($mail_errors, 'Echec de l\'envoi de l\'e-mail au client pour la signature à distance');
                }
            }
        } else {
            $errors[] = 'Adresse e-mail du client absente ou invalide';
        }

        return $errors;
    }

    public function writeSignatureOnDoc($params_overrides = array())
    {
        $errors = array();

        $srcFile = $this->getDocumentFilePath(false);
        $destFile = $this->getDocumentFilePath(true);

        $image = $this->getData('base_64_signature');

        if (!$image) {
            $errors[] = 'Image absente';
        }

        if (!count($errors)) {
            $params = $this->getSignatureParams();

            if (!empty($params_overrides)) {
                $params = BimpTools::overrideArray($params, $params_overrides, true);
            }

            require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpPDF.php';

            $texts = array(
                'date' => date('d/m/Y', strtotime($this->getData('date_signed')))
            );

            if ($this->isClientCompany()) {
                $texts['nom'] = $this->getData('nom_signataire');
                $texts['fonction'] = $this->getData('fonction_signataire');
            }

            $pdf = new BimpConcatPdf();
            $errors = $pdf->insertSignatureImage($srcFile, $image, $destFile, $params, $texts);
        }

        return $errors;
    }

    public function cancelSignature(&$warnings = array())
    {
        $errors = array();

        if ($this->isLoaded()) {
            $this->set('type', -1);
            $this->update($warnings, true);
        }

        return $errors;
    }

    public function sendRelanceEmail(&$errors = array(), &$warnings = array())
    {
        $users = $this->getData('allowed_users_client');

        if (empty($users)) {
            $errors[] = 'Aucun utilisateur client autorisé pour signature';
        } else {
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
                $commercial_email = $this->getCommercialEmail();
                $date_open = $this->getData('date_open');
                $doc_label = $this->displayDocType() . ' ' . $this->displayDocRef();
                $url = DOL_URL_ROOT . '/bimpinterfaceclient/client.php';

                $subject = 'Client ' . $client->getRef() . ' - ' . $doc_label . ' - Signature en attente';

                $msg = 'Cher client, <br/><br/>';
                $msg .= 'Le ' . date('d / m / Y', strtotime($date_open)) . ' nous avons mis à votre disposition le document "' . $doc_label . '" pour signature.<br/><br/>';
                $msg .= 'Sauf erreur de notre part nous ne l\'avons pas reçu en retour.<br/><br/>';
                $msg .= 'Il convient que vous procédiez rapidement à cette signature afin que nous puissions donner suite à votre dossier.<br/><br/>';
                $msg .= 'Pour rappel, vous pouvez effectuer la signature de ce document directement depuis <a href="' . $url . '">votre espace client</a><br/><br/>';
                $msg .= 'Nous vous remercions par avance.<br/><br/>';
                $msg .= 'Cordialement,<br/><br/>';
                $msg .= 'L\'équipe BIMP';

                $filePath = $this->getDocumentFilePath();
                $fileName = $this->getDocumentFileName();

                $bimpMail = new BimpMail($subject, $emails, '', $msg, $commercial_email, $commercial_email);

                if (file_exists($filePath)) {
                    $bimpMail->addFile(array($filePath, 'application/pdf', $fileName));
                }

                return $bimpMail->send($errors, $warnings);
            }
        }

        return false;
    }

    public function sendOnSignedCommercialEmail(&$errors = array(), &$warnings = array())
    {
        $comm_email = $this->getCommercialEmail();

        if ($comm_email) {
            $obj = $this->getObj();

            if ($this->isObjectValid($errors, $obj)) {
                $client = $this->getChildObject('client');
                if (BimpObject::objectLoaded($client)) {
                    $obj_label = $this->displayDocType() . ' ' . $this->displayDocRef();
                    $subject = 'Signature effectuée - ' . $obj_label . ' - Client: ' . (BimpObject::objectLoaded($client) ? $client->getRef() . ' ' . $client->getName() : 'inconnu');

                    $msg = 'Bonjour,<br/><br/>';
                    $msg .= 'La signature du document "' . $obj_label . '" a été effectuée.<br/><br/>';
                    if (is_a($obj, 'BimpObject')) {
                        $msg .= '<b>Objet lié: </b>' . $obj->getLink() . '<br/>';
                    }
                    $msg .= '<b>Signature: </b>' . $this->getLink(array(), 'private') . '<br/><br/>';

                    $msg .= '<b>Date de la signature: </b>' . date('d / m / Y à H:i:s', strtotime($this->getData('date_signed'))) . '<br/>';
                    $msg .= '<b>Type signature: </b>' . BimpTools::getArrayValueFromPath(self::$types, $this->getData('type') . '/label', 'inconnue') .'<br/>';
                    $msg .= '<b>Nom Signataire: </b> ' . $this->getData('nom_signataire') . '<br/>';
                    $msg .= '<b>Adresse e-mail signataire: </b>' . $this->getData('email_signataire') . '<br/><br/>';

                    mailSyn2($subject, $comm_email, '', $msg);
                }
            }
        }
    }

    // Actions: 

    public function actionSignDistAccess($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $new_users = array();
        $cur_users = $this->getData('allowed_users_client');
        $auto_open = (int) BimpTools::getArrayValueFromPath($data, 'auto_open', 0);

        if ($auto_open) {
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                $contact = $this->getChildObject('contact');
                $new_users = $this->getDefaultAllowedUsersClient();

                $new_user_email = '';
                if (empty($new_users)) {
                    $new_user_email = BimpTools::getArrayValueFromPath($data, 'new_user_email', '');

                    if (!$new_user_email) {
                        $errors[] = 'Veuillez saisir une adresse e-mail pour la création du compte utilisateur client';
                    } elseif (!BimpValidate::isEmail($new_user_email)) {
                        $errors[] = 'L\'adresse e-mail pour la création du compte utilisateur client est invalide';
                    }
                } else {
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
                                    'id_client'  => (int) $client->id,
                                    'id_contact' => (BimpObject::objectLoaded($contact) && $contact->getData('email') == $new_user_email ? $contact->id : 0),
                                    'email'      => $new_user_email,
                                    'role'       => ($nAdmin > 0 ? 0 : 1),
                                    'status'     => 1
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
        } else {
            $new_users = BimpTools::getArrayValueFromPath($data, 'allowed_users_client', array());
        }

        if (!count($errors)) {
            $errors = $this->updateField('allowed_users_client', $new_users);

            if (!count($errors)) {
                if (empty($new_users)) {
                    $warnings[] = 'Aucun compte utilisateur client sélectionné - Le client n\'aura aucun accès à la signature à distance';
                    $this->updateField('date_open', null);
                } else {
                    $obj = $this->getObj();

                    if ($this->isObjectValid($errors, $obj)) {
                        $success .= ($success ? '<br/>' : '') . 'Liste des utilisteurs client enregistrée avec succès';

                        $nOk = 0;
                        if (empty($cur_users)) {
                            $up_err = $this->updateField('date_open', date('Y-m-d'));

                            if (count($up_err)) {
                                $warnings[] = BimpTools::getMsgFromArray($up_err, 'Echec màj date d\'ouverture');
                            }
                        }

                        // Envoi e-mails notification: 
                        $emails = '';

                        foreach ($new_users as $id_user) {
                            if (!in_array($id_user, $cur_users)) {
                                $bic_user = BimpCache::getBimpObjectInstance('bimpinterfaceclient', 'BIC_UserClient', $id_user);

                                if (!BimpObject::objectLoaded($bic_user)) {
                                    $warnings[] = 'Le compte utilisateur client #' . $id_user . ' n\'existe pas';
                                } else {
                                    $user_email = BimpTools::cleanEmailsStr($bic_user->getData('email'));

                                    if ($user_email) {
                                        $emails .= ($emails ? ',' : '') . $user_email;
                                        $nOk++;
                                    }
                                }
                            }
                        }

                        if ($emails) {
                            $comm_email = BimpTools::cleanEmailsStr($this->getCommercialEmail());
                            $doc_label = $this->displayDocType() . ' ' . $this->displayDocRef();
                            $subject = 'BIMP - Signature en attente - Document: ' . $doc_label;

                            $message = 'Bonjour, <br/><br/>';
                            $message .= 'La signature du document "' . $doc_label . '" pour ' . $obj->getLabel('the') . ' ' . $obj->getRef();
                            $message .= ' est en attente.<br/><br/>';

                            $url = DOL_URL_ROOT . '/bimpinterfaceclient/client.php';

                            $message .= 'Vous pouvez effectuer la signature électronique de ce document directement depuis votre <a href="' . $url . '">espace client BIMP</a><br/><br/>';
                            $message .= 'Cordialement, <br/><br/>';
                            $message .= 'L\'équipe BIMP';

                            $bimpMail = new BimpMail($subject, BimpTools::cleanEmailsStr($email), '', $message, $comm_email);

                            $filePath = $this->getDocumentFilePath();
                            $fileName = $this->getDocumentFileName();

                            if (file_exists($filePath)) {
                                $bimpMail->addFile(array($filePath, 'application/pdf', $fileName));
                            }

                            $mail_errors = array();
                            $bimpMail->send($mail_errors);

                            if (count($mail_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($mail_errors, 'Echec de l\'envoi de l\'e-mail de notification (Adresse(s): ' . $email . ')');
                            } else {
                                $success .= '<br/>E-mail de notification envoyé avec succès pour ' . $nOk . ' utilisateur(s)';
                            }
                        }
                    } else {
                        $errors[] = 'L\'objet lié est invalide';
                    }
                }
            }
        }
        
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSignPapier($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Document signé enregistré avec succès';

        $obj = $this->getObj();

        if ($this->isObjectValid($errors, $obj)) {
            $nom = $this->getData('nom_signataire');

            if (!$nom) {
                $errors[] = 'Veuillez saisir le nom du signataire';
            }

            $date = $this->getData('date_signed');

            if (!$date) {
                $date = date('Y-m-d H:i:s');
            }

            if (!isset($_FILES['file_signed']) || !isset($_FILES['file_signed']['name']) || empty($_FILES['file_signed']['name'])) {
                $errors[] = 'Fichier du document signé absent';
            } else {
                $file_name = $this->getDocumentFileName(true);
                $file_path = $this->getDocumentFilePath(true, 'private');
                $dir = $this->getDocumentFileDir();

                if (!$dir || !$file_path || !$file_name) {
                    $errors[] = 'Erreurs: chemin du fichier absent';
                } else {
                    if (file_exists($file_path)) {
                        $errors[] = 'Le document signé existe déjà. Si vous souhaitez le remplacer, veuillez le supprimer manuellement (Nom: ' . $file_name . ')';
                    } else {
                        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
                        $_FILES['file_signed']['name'] = $file_name;

                        $ret = dol_add_file_process($dir, 0, 0, 'file_signed');
                        if ($ret <= 0) {
                            $errors = BimpTools::getDolEventsMsgs(array('errors', 'warnings'));
                            if (!count($errors)) {
                                $errors[] = 'Echec de l\'enregistrement du fichier pour une raison inconnue';
                            }
                        } else {
                            $success .= 'Téléchargement du fichier effectué avec succès';
                        }
                        BimpTools::cleanDolEventsMsgs();
                    }
                }
            }

            if (!count($errors)) {
                $this->set('type', self::TYPE_PAPIER);
                $this->set('signed', 1);
                $this->set('date_signed', $date);
                $this->set('nom_signataire', $nom);

                $errors = $this->update($warnings, true);

                if (!count($errors)) {
                    if (method_exists($obj, 'onSigned')) {
                        $warnings = array_merge($warnings, $obj->onSigned($this, $data));
                    }

                    $this->sendOnSignedCommercialEmail();
                }
            }
        } else {
            $errors[] = 'Objet lié absent ou invalide';
        }

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

        $obj = $this->getObj();

        if ($this->isObjectValid($errors, $obj)) {
            $nom = BimpTools::getArrayValueFromPath($data, 'nom_signataire', '');
            $email = BimpTools::getArrayValueFromPath($data, 'email_signataire', '');
            $fonction = BimpTools::getArrayValueFromPath($data, 'fonction_signataire', '');

            if (!$email) {
                $errors[] = 'Veuillez saisir l\'adresse e-mail du signataire';
            }

            if (!$nom) {
                $errors[] = 'Veuillez saisir le nom du signataire';
            }

            $signature = BimpTools::getArrayValueFromPath($data, 'signature', '');

            if (!$signature || $signature == self::$empty_base_64) {
                $errors[] = 'Signature électronique absente';
            }

            $client = $this->getChildObject('client');

            if (BimpObject::objectLoaded($client)) {
                if ($client->isCompany()) {
                    if (!$fonction) {
                        $errors[] = 'Veuillez saisir la fonction du signatraire';
                    }
                }
            }

            if (!count($errors)) {
                $this->set('type', self::TYPE_ELEC);
                $this->set('signed', 1);
                $this->set('date_signed', date('Y-m-d H:i:s'));
                $this->set('nom_signataire', $nom);
                $this->set('fonction_signataire', $fonction);
                $this->set('email_signataire', $email);
                $this->set('base_64_signature', $signature);

                $errors = $this->update($warnings, true);
                if (!count($errors)) {
                    $doc_errors = $this->writeSignatureOnDoc();

                    if (count($doc_errors)) {
                        $warnings[] = 'Echec de l\'écriture de la signature sur le document PDF';
                    } else {
                        $url = $this->getDocumentUrl(true);

                        if ($url) {
                            $success_callback = 'window.open(\'' . $url . '\');';
                        }
                    }

                    if (method_exists($obj, 'onSigned')) {
                        $warnings = array_merge($warnings, $obj->onSigned($this, $data));
                    }

                    $this->sendOnSignedCommercialEmail();
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionSignDist($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Signature enregistrée avec succès';
        $success_callback = '';

        $obj = $this->getObj();

        if ($this->isObjectValid($errors, $obj)) {
            global $userClient;

            if (!BimpObject::objectLoaded($userClient)) {
                $errors[] = 'Aucun utilisateur connecté';
            } else {
                $nom = BimpTools::getArrayValueFromPath($data, 'nom_signataire', '');

                if (!$nom) {
                    $errors[] = 'Veuillez saisir votre nom';
                }

                $fonction = '';

                if ($this->isClientCompany()) {
                    $fonction = BimpTools::getArrayValueFromPath($data, 'fonction_signataire', '');

                    if (!$fonction) {
                        $errors[] = 'Veuillez saisir votre fonction';
                    }
                }

                $signature = BimpTools::getArrayValueFromPath($data, 'signature', '');

                if (!$signature || $signature == self::$empty_base_64) {
                    $errors[] = 'Signature électronique absente';
                }

                if (!count($errors)) {
                    require_once DOL_DOCUMENT_ROOT . '/synopsistools/class/divers.class.php';
                    $this->set('signed', 1);
                    $this->set('date_signed', date('Y-m-d H:i:s'));
                    $this->set('nom_signataire', $nom);
                    $this->set('fonction_signataire', $fonction);
                    $this->set('email_signataire', $userClient->getData('email'));
                    $this->set('id_user_client_signataire', $userClient->id);
                    $this->set('base_64_signature', $signature);
                    $this->set('type', self::TYPE_DIST);
                    $this->set('ip_signataire', synopsisHook::getUserIp());

                    $errors = $this->update($warnings, true);

                    if (!count($errors)) {
                        $doc_errors = $this->writeSignatureOnDoc();

                        if (count($doc_errors)) {
                            $warnings[] = 'Echec de l\'écriture de la signature sur le document PDF';
                        } else {
                            $url = $this->getDocumentUrl(true, 'public');

                            if ($url) {
                                $success_callback = 'window.open(\'' . $url . '\');bimp_reloadPage();';
                            }
                        }

                        if (method_exists($obj, 'onSigned')) {
                            $warnings = array_merge($warnings, $obj->onSigned($this, $data));
                        }

                        $this->sendOnSignedCommercialEmail();
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

        $errors = $this->writeSignatureOnDoc($data);

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

    // Overrides: 

    public function update(&$warnings = [], $force_update = false)
    {
        if (!(int) $this->getData('signed') && BimpTools::getPostFieldValue('sign_papier', 0)) {
            // Procédure nécessaire pour téléchargement du fichier: 
            $result = $this->setObjectAction('signPapier');

            $errors = BimpTools::getArrayValueFromPath($result, 'errors', array());
            $warnings = BimpTools::getArrayValueFromPath($result, 'warnings', array());

            return $errors;
        }

        return parent::update($warnings, $force_update);
    }
}
