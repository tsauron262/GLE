<?php

class BimpSignature extends BimpObject
{
    /*
     * *** Mémo ajout signature pour un objet: ***
     * 
     * /!\ ATTENTION: le doc_type doit être unique : voir les doc_types utilisés dans bimpinterfaceclient > docController::display()
     * 
     * - Ajouter les champs "id_signature" et "signature_params" dans l'objet (+ définitions de l'objet BimpSignature) 
     * - Ajouter le tableau static $default_signature_params
     * - Ajouter une procédure pour créer la signature (action/fonction etc.)
     * - Ajouter les méthodes: 
     *      - getSignatureDocFileDir($doc_type): dossier fichier signé ou à signer
     *      - getSignatureDocFileName($doc_type, $signed = false): nom fichier signé ou à signer
     *      - getSignatureDocFileUrl($doc_type, $forced_context = '', $signed = false): URL fichier
     *      - getSignatureDocRef($doc_type): Reférence document
     *      - getSignatureParams($doc_type): Paramètres position signature sur PDF
     *      - onSigned($bimpSignature, $data): Traitement post signature effectuée
     *      - onSignatureCancelled($bimpSignature): Traitement post signature annulée
     *      - isSignatureReopenable($doc_type, &$errors = array()): la signature peut-elle être réouverte (suite annulation) 
     *      - onSignatureReopened($bimpSignature): Traitement post réouverture signature
     * 
     * - Gérer l'enregistrement des paramètres de position de la signature sur le PDF au moment de sa génération (Si besoin) / ou régler par défaut pour les PDF fixes
     * - Intégrer selon le context: marqueur signé (champ booléen ou statut) / indicateur signature dans l'en-tête / etc. 
     * - Gérer Annulation signature si besoin
     * - Gérer Duplication / Révision / Etc. 
     * - Gérer la visualisation du docuement sur l'interface publique (bimpinterfaceclient > docController) 
     * - Gérer le droit canClientView() pour la visualisation du document sur l'espace public. 
     */

    const TYPE_DIST = 1;
    const TYPE_PAPIER = 2;
    const TYPE_ELEC = 3;
    const TYPE_PAPIER_NO_SCAN = 4;

    public static $types = array(
        -1                        => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('danger')),
        0                         => array('label' => 'En attente de signature', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::TYPE_DIST           => array('label' => 'Signature à distance', 'icon' => 'fas_sign-in-alt'),
        self::TYPE_PAPIER         => array('label' => 'Signature papier', 'icon' => 'fas_file-download'),
        self::TYPE_ELEC           => array('label' => 'Signature électronique', 'icon' => 'fas_file-signature'),
        self::TYPE_PAPIER_NO_SCAN => array('label' => 'Signature papier sans document scanné', 'icon' => 'fas_file-contract')
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

        if (in_array($field_name, array('allow_elec', 'allow_dist', 'allow_no_scan'))) {
            global $user;
            return (int) $user->admin;
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

    public function isFieldEditable($field, $force_edit = false)
    {
        if (in_array($field, array('id_contact', 'allow_elec', 'allow_dist', 'need_sms_code'))) {
            if ((int) $this->getData('signed')) {
                return 0;
            }

            return 1;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isDeletable($force_delete = false, &$errors = [])
    {
        if (!$force_delete && (int) $this->getData('signed')) {
            return 0;
        }

        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        switch ($action) {
            case 'signDistAccess':
            case 'signPapier':
            case 'signPapierNoScan':
            case 'signDist':
            case 'signElec':
            case 'sendSmsCode':
                if ((int) $this->getData('signed')) {
                    $errors[] = 'Signature déjà effectuée';
                    return 0;
                }
                if ((int) $this->getData('type') < 0) {
                    $errors[] = 'Signature annulée';
                    return 0;
                }

                switch ($action) {
                    case 'signPapierNoScan':
                        if (!(int) $this->getData('allow_no_scan')) {
                            $errors[] = 'Scan du document signé obligatoire pour cette signature';
                            return 0;
                        }
                        break;

                    case 'signDistAccess':
                    case 'signDist':
                    case 'sendSmsCode':
                        if (!(int) $this->getData('allow_dist')) {
                            $errors[] = 'Signature à distance non autorisée pour cette signature';
                            return 0;
                        }
                        break;

                    case 'signElec':
                        if (!(int) $this->getData('allow_elec')) {
                            $errors[] = 'Signature éléctronique non autorisée pour cette signature';
                            return 0;
                        }
                        break;
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

            case 'cancel':
                if ((int) $this->getData('type') < 0) {
                    $errors[] = 'Signature déjà annulée';
                    return 0;
                }
                if ((int) $this->getData('signed')) {
                    $errors[] = 'Signature déjà affectuée';
                    return 0;
                }
                $obj = $this->getObj();
                if ($this->isObjectValid($errors, $obj)) {
                    if (method_exists($obj, 'isSignatureCancellable')) {
                        if (!$obj->isSignatureCancellable($this->getData('doc_type'), $errors)) {
                            return 0;
                        }
                    }
                }
                return (count($errors) ? 0 : 1);

            case 'reopen':
                if ((int) $this->getData('type') !== -1) {
                    $errors[] = 'Cette signature n\'est pas au statut annulée';
                    return 0;
                }
                $obj = $this->getObj();
                if ($this->isObjectValid($errors, $obj)) {
                    if (method_exists($obj, 'isSignatureReopenable')) {
                        if (!$obj->isSignatureReopenable($this->getData('doc_type'), $errors)) {
                            return 0;
                        }
                    }
                }
                return (count($errors) ? 0 : 1);
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
                'label'   => (empty($this->getData('allowed_users_client')) ? 'Ouvrir accès' : 'Accès') . ' signature à distance',
                'icon'    => 'fas_sign-in-alt',
                'onclick' => $this->getJsActionOnclick('signDistAccess', array(), array(
                    'form_name' => 'open_sign_dist'
                ))
            );
        }

        if ($this->isActionAllowed('signPapier') && $this->canSetAction('signPapier')) {
            $buttons[] = array(
                'label'   => 'Déposer document signé',
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

        if ($this->isActionAllowed('signPapierNoScan') && $this->canSetAction('signPapierNoScan')) {
            $buttons[] = array(
                'label'   => 'Signature papier sans scan',
                'icon'    => 'fas_file-contract',
                'onclick' => $this->getJsActionOnclick('signPapierNoScan', array(), array(
                    'form_name' => 'sign_no_scan'
                ))
            );
        }

        if ($this->isActionAllowed('cancel') && $this->canSetAction('cancel')) {
            $buttons[] = array(
                'label'   => 'Annuler la Signature',
                'icon'    => 'fas_times-circle',
                'onclick' => $this->getJsActionOnclick('cancel', array(), array(
                    'confirm_msg' => 'Veuillez confirmer l\\\'annulation de cette signature'
                ))
            );
        }

        if ($this->isActionAllowed('reopen') && $this->canSetAction('reopen')) {
            $buttons[] = array(
                'label'   => 'Réouvrir la Signature',
                'icon'    => 'fas_undo',
                'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                    'confirm_msg' => 'Veuillez confirmer la réouveture de cette signature'
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
                'label'   => 'Ajuster la Signature sur le document',
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

    public static function getDefaultSignDistEmailContent()
    {
        $message = 'Bonjour, <br/><br/>';
        $message .= 'La signature du document "{NOM_DOCUMENT}" pour {NOM_PIECE} {REF_PIECE}  est en attente.<br/><br/>';
        $message .= 'Vous pouvez effectuer la signature électronique de ce document directement depuis votre {LIEN_ESPACE_CLIENT} ou nous retourner le document ci-joint signé.<br/><br/>';
        $message .= 'Cordialement, <br/><br/>';
        $message .= 'L\'équipe BIMP';

        return $message;
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
                $msg .= 'Le client ne dispose d\'aucun compte utilisateur valide pour la signature à distance de ce document via l\'espace client LDLC Apple.<br/>';
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
            return $client->getCommercialEmail(false);
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

    public function getCheckMentions()
    {
        $obj = $this->getObj();

        $errors = array();

        if ($this->isObjectValid($errors, $obj)) {
            if (method_exists($obj, 'getSignatureCheckMentions')) {
                return $obj->getSignatureCheckMentions($this->getData('doc_type'));
            }
        }

        return array();
    }

    // Getters Array: 

    public function getTypeInputOptions()
    {
        $types = self::$types;

        unset($types[0]);

        return $types;
    }

    public function getcontactsArray($include_empty = true, $active_only = true)
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

    // Getters Doc infos: 

    public function getDocumentFileName($signed = false)
    {
        $doc_type = $this->getData('doc_type');

        if ($doc_type) {
            $obj = $this->getObj();
            $errors = array();

            if ($this->isObjectValid($errors, $obj)) {
                if (method_exists($obj, 'getSignatureDocFileName')) {
                    $file_name = $obj->getSignatureDocFileName($doc_type, $signed);
                    $file_ext = $this->getData('signed_doc_ext');

                    if ($file_ext && $file_ext !== 'pdf') {
                        $file_name = pathinfo($file_name, PATHINFO_FILENAME) . '.' . $file_ext;
                    }

                    return $file_name;
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
                    $url = $obj->getSignatureDocFileUrl($doc_type, $forced_context, $signed);

                    if ($url && $signed) {
                        $file_ext = $this->getData('signed_doc_ext');

                        if ($file_ext && $file_ext !== 'pdf') {
                            $url = str_replace('.pdf', '.' . $file_ext, $url);
                        }
                    }

                    return $url;
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

    public function displayDocTitle()
    {
        $ref = $this->displayDocRef();
        return $this->displayDocType() . ($ref ? ' - <b>' . $ref . '</b>' : '');
    }

    public function displayDocInfos()
    {
        $html = '<h4>' . $this->displayDocTitle() . '</h4>';

        $obj = $this->getObj();

        $errors = array();
        if ($this->isObjectValid($errors, $obj)) {
            if (method_exists($obj, 'displayDocExtraInfos')) {
                $extra_infos = $obj->displayDocExtraInfos($this->getData('doc_type'));

                if ($extra_infos) {
                    $html .= '<br/>' . $extra_infos;
                }
            }
        }

        return $html;
    }

    public function dispayPublicSign()
    {
        if ($this->isLoaded()) {
            if ($this->isActionAllowed('signDist')) {
                $html = '';
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

                return $html;
            }
        }

        return '';
    }

    public function displayPublicDocument($label = 'Document PDF')
    {
        if ($this->isLoaded() && (int) $this->getData('type') >= 0) {
            $obj = $this->getObj();

            if ($this->isObjectValid()) {
                if ($this->can('view')) {
                    if (method_exists($obj, 'getSignatureDocFileUrl') && method_exists($obj, 'getSignatureDocFileName')) {
                        if (method_exists($obj, 'getSignatureDocFileDir')) {
                            $dir = $obj->getSignatureDocFileDir($this->getData('doc_type'));
                        } elseif (method_exists($obj, 'getFilesDir')) {
                            $dir = $obj->getFilesDir();
                        }

                        if ($dir) {
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
                                        $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . $label;
                                        $html .= '</span>';
                                    } else {
                                        $html = '<span class="btn btn-default disabled bs-popover" onclick=""';
                                        $html .= BimpRender::renderPopoverData('Vous n\'avez pas la permission de voir ce document');
                                        $html .= '>';
                                        $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . $label;
                                        $html .= '</span>';
                                    }
                                    return $html;
                                }
                            }
                        }
                    }
                }
            }

            return '<span class="warning">Non disponible</span>';
        }

        return '';
    }

    public function displayActionsButtons()
    {
        $html = '';

        if ($this->isLoaded()) {
            $buttons = $this->getSignButtons();

            if (!empty($buttons)) {
                $html .= '<div>';
                foreach ($buttons as $btn) {
                    $html .= BimpRender::renderRowButton($btn['label'], $btn['icon'], $btn['onclick']);
                }
                $html .= '</div>';
            }
        }

        return $html;
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

    public function renderSignButtonsGroup($label = 'Actions signature')
    {
        $buttons = $this->getSignButtons();

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

        if (!$this->getData('need_sms_code')) {
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

    public function openSignDistAccess($email_content = '', $auto_open = true, $new_users = array(), $new_user_email = '', &$warnings = array(), &$success = '')
    {
        $errors = array();

        if (!(int) $this->getData('allow_dist')) {
            $errors[] = 'La signature à distance n\'est pas autorisée pour cette signature';
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
                            $subject = 'Signature en attente - Document: ' . $doc_label;

                            if (!$email_content) {
                                $email_content = $this->getDefaultSignDistEmailContent();
                            }

                            $url = self::getPublicBaseUrl(false);

                            $email_content = str_replace(array(
                                '{NOM_DOCUMENT}',
                                '{NOM_PIECE}',
                                '{REF_PIECE}',
                                '{LIEN_ESPACE_CLIENT}'
                                    ), array(
                                $doc_label,
                                $obj->getLabel('the'),
                                $obj->getRef(),
                                '<a href="' . $url . '">espace client LDLC Apple</a>'
                                    ), $email_content);

                            $bimpMail = new BimpMail($this->getObj(), $subject, BimpTools::cleanEmailsStr($emails), '', $email_content, $comm_email);

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

            $texts = array();

            if ((int) BimpTools::getArrayValueFromPath($params, 'display_date', 1)) {
                $texts['date'] = date('d/m/Y', strtotime($this->getData('date_signed')));
            }

            $is_company = (int) $this->isClientCompany();
            if ((int) BimpTools::getArrayValueFromPath($params, 'display_nom', $is_company)) {
                $texts['nom'] = $this->getData('nom_signataire');
            }

            if ((int) BimpTools::getArrayValueFromPath($params, 'display_fonction', $is_company)) {
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
            $errors = $this->update($warnings, true);

            if (!count($errors)) {
                $obj = $this->getObj();

                if ($this->isObjectValid($warnings, $obj)) {
                    if (method_exists($obj, 'onSignatureCancelled')) {
                        $warnings = $obj->onSignatureCancelled($this);
                    }
                }
            }
        }

        return $errors;
    }

    public function reopenSignature(&$warnings = array())
    {
        $errors = array();

        if ($this->isLoaded()) {
            $this->set('type', 0);
            $this->set('signed', 0);
            $errors = $this->update($warnings, true);

            if (!count($errors)) {
                $obj = $this->getObj();

                if ($this->isObjectValid($warnings, $obj)) {
                    if (method_exists($obj, 'onSignatureReopened')) {
                        $warnings = $obj->onSignatureReopened($this);
                    }
                }
            }
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
                $url = self::getPublicBaseUrl(false);

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

                $bimpMail = new BimpMail($this->getObj(), $subject, $emails, '', $msg, $commercial_email, $commercial_email);

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
                    $subject = 'Signature effectuée - ' . $obj_label . ' - Client: ' . $client->getRef() . ' ' . $client->getName();

                    $msg = 'Bonjour,<br/><br/>';
                    $msg .= 'La signature du document "' . $obj_label . '" a été effectuée.<br/><br/>';
                    if (is_a($obj, 'BimpObject')) {
                        $msg .= '<b>Objet lié: </b>' . $obj->getLink() . '<br/>';
                    }
                    $msg .= '<b>Signature: </b>' . $this->getLink(array(), 'private') . '<br/><br/>';

                    $msg .= '<b>Date de la signature: </b>' . date('d / m / Y à H:i:s', strtotime($this->getData('date_signed'))) . '<br/>';
                    $msg .= '<b>Type signature: </b>' . BimpTools::getArrayValueFromPath(self::$types, $this->getData('type') . '/label', 'inconnue') . '<br/>';
                    $msg .= '<b>Nom Signataire: </b> ' . $this->getData('nom_signataire') . '<br/>';
                    $msg .= '<b>Adresse e-mail signataire: </b>' . $this->getData('email_signataire') . '<br/><br/>';

                    if (method_exists($obj, 'getOnSignedEmailExtraInfos')) {
                        $msg .= $obj->getOnSignedEmailExtraInfos($this->getData('doc_type'));
                    }

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

        $auto_open = (int) BimpTools::getArrayValueFromPath($data, 'auto_open', 0);
        $new_users = BimpTools::getArrayValueFromPath($data, 'allowed_users_client', array());
        $new_user_email = BimpTools::getArrayValueFromPath($data, 'new_user_email', '');
        $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', '');

        if (!$new_user_email) {
            $errors[] = 'Veuillez saisir une adresse e-mail pour la création du compte utilisateur client';
        } elseif (!BimpValidate::isEmail($new_user_email)) {
            $errors[] = 'L\'adresse e-mail pour la création du compte utilisateur client est invalide';
        }

        $errors = $this->openSignDistAccess($email_content, $auto_open, $new_users, $new_user_email, $warnings);

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
                $file_path = $this->getDocumentFilePath(true/* , 'private' */);
                $dir = $this->getDocumentFileDir();

                if (!$dir || !$file_path || !$file_name) {
                    $errors[] = 'Erreurs: chemin du fichier absent';
                } else {
                    if (file_exists($file_path)) {
                        $errors[] = 'Le document signé existe déjà. Si vous souhaitez le remplacer, veuillez le supprimer manuellement (Nom: ' . $file_name . ')';
                    } else {
                        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

                        $file_ext = pathinfo($_FILES['file_signed']['name'], PATHINFO_EXTENSION);
                        $file_name = pathinfo($file_name, PATHINFO_FILENAME) . '.' . $file_ext;
                        $this->set('signed_doc_ext', $file_ext);

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

//                    $this->sendOnSignedCommercialEmail();
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

    public function actionSignPapierNoScan($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Signature sans scan enregistrée avec succès';

        $obj = $this->getObj();

        if ($this->isObjectValid($errors, $obj)) {
            $nom = BimpTools::getArrayValueFromPath($data, 'nom_signataire', '');

            if (!$nom) {
                $errors[] = 'Veuillez saisir le nom du signataire';
            }

            $date = BimpTools::getArrayValueFromPath($data, 'date_signed', date('Y-m-d H:i:s'));

            if (!count($errors)) {
                $this->set('type', self::TYPE_PAPIER_NO_SCAN);
                $this->set('signed', 1);
                $this->set('date_signed', $date);
                $this->set('nom_signataire', $nom);

                $errors = $this->update($warnings, true);

                if (!count($errors)) {
                    if (method_exists($obj, 'onSigned')) {
                        $warnings = array_merge($warnings, $obj->onSigned($this, $data));
                    }
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

//                    $this->sendOnSignedCommercialEmail();
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
                    $this->set('code_sms_infos', $code_sms_infos);

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
                            $onsign_errors = $obj->onSigned($this, $data);

                            if (count($onsign_errors)) {
                                BimpCore::addlog('Erreurs suite à signature à distance', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', $this, array(
                                    'Erreurs' => $onsign_errors
                                ));

                                $warnings = BimpTools::merge_array($warnings, $onsign_errors);
                            }
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
        $success_callback = '';

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

    public function actionCancel($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Annulation de la signature effectuée avec succès';

        $errors = $this->cancelSignature($warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Réouverture de la signature effectuée avec succès';

        $errors = $this->reopenSignature($warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSendSmsCode($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Code envoyé avec succès';
        $success_callback = '';

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

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
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
