<?php

class BimpSignature extends BimpObject
{

    const TYPE_DIST = 1;
    const TYPE_PAPIER = 2;
    const TYPE_ELEC = 3;

    public static $types = array(
        -1                => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('warning')),
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
                    if (in_array($field_name, array('nom_signataire'))) {
                        return 1;
                    }
                }
            }

            return 0;
        }

        return parent::canEditField($field_name);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('setSigned', 'allowed_users_client', 'signPapier', 'signDist'))) {
            if (!$this->isLoaded($errors)) {
                return 0;
            }
        }

        switch ($action) {
            case 'signDistAccess':
            case 'signPapier':
            case 'signDist':
                if ((int) $this->getData('signed')) {
                    $errors[] = 'Signature déjà effectuée';
                    return 0;
                }
                if ((int) $this->getData('type') < 0) {
                    $errors[] = 'Signature annulée';
                    return 0;
                }
                return 1;

            case 'setSigned':
                if ((int) $this->getData('signed')) {
                    $errors[] = 'Signature déjà effectuée';
                    return 0;
                }

                if ((int) $this->getData('type') === self::TYPE_DIST &&
                        $this->getData('public_access_code')) {
                    $errors[] = 'Code d\'accès pour sigature à distance déjà envoyé au client';
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

    public function showAutoOpenPublicAccess()
    {
        return (empty($this->getInitData('allowed_users_client')) ? 1 : 0);
    }

    public function showOpenPublicAccessNewUserEmailInput()
    {
        $users = $this->getDefaultAllowedUsersClientArray();

        return (empty($users) ? 1 : 0);
    }

    // Getters params: 

    public function getActionsButtons()
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
                'onclick' => $this->getJsActionOnclick('signPapier', array(), array(
                    'form_name' => 'sign_papier'
                ))
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

    public function getListExtraBtn()
    {
        $buttons = array();

        return $buttons;
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
        if ($this->isLoaded()) {
            $obj = $this->getObj();

            if ($this->isObjectValid()) {
                if ($this->can('view')) {
                    if (method_exists($obj, 'getSignatureDocFileDir') && method_exists($obj, 'getSignatureDocFileUrl')) {
                        $file = $obj->getSignatureDocFileDir($this->getData('doc_type'));

                        if ($file && file_exists($file)) {
                            $url = $obj->getSignatureDocFileUrl($this->getData('doc_type'), 'public');

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

    // Traitements:

    public function generateRandomPassword($nombre_char)
    {
        $password = "";
        for ($i = 0; $i < $nombre_char; $i++) {
            $selecteur_type = rand(0, 1000);

            if ($selecteur_type % 2 == 0) {
                // C'est un char
                $char = chr(rand(65, 90));
                $selecteur_maj = rand(0, 1000);
                if ($selecteur_maj % 2 == 0) {
                    // C'est une majuscule
                    $password .= strtoupper($char);
                } else {
                    // C'est une minuscule
                    $password .= strtolower($char);
                }
            } else {
                $password .= rand(0, 9);
            }
        }
        return $password;
    }

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

    public function writeSignatureOnDoc()
    {
        $errors = array();

        return $errors;
    }

    // Actions: 

    public function actionSignDistAccess($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $new_users = array();
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
                                        ), true, $u_err);

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
                } else {
                    $obj = $this->getObj();

                    if ($this->isObjectValid($errors, $obj)) {
                        $cur_users = $this->getData('allowed_users_client');
                        $success .= ($success ? '<br/>' : '') . 'Liste des utilisteurs client enregistrée avec succès';
                        // Envoi e-mail notification: 
                        $nOk = 0;

                        foreach ($new_users as $id_user) {
                            if (!in_array($id_user, $cur_users)) {
                                $bic_user = BimpCache::getBimpObjectInstance('bimpinterfaceclient', 'BIC_UserClient', $id_user);

                                if (!BimpObject::objectLoaded($bic_user)) {
                                    $warnings[] = 'Le compte utilisateur client #' . $id_user . ' n\'existe pas';
                                } else {
                                    $email = BimpTools::cleanEmailsStr($bic_user->getData('email'));

                                    $subject = 'BIMP - Signature en attente';

                                    $message = 'Bonjour, <br/><br/>';
                                    $message .= 'La signature du document "' . $this->displayDocType() . '" pour ' . $obj->getLabel('the') . ' ' . $obj->getRef();
                                    $message .= ' est en attente.<br/><br/>';

                                    $url = DOL_URL_ROOT . '/bimpinterfaceclient/client.php?email=' . $this->getData('email');

                                    $message .= 'Vous pouvez effectuer la signature électronique de ce document directement depuis votre <a href="' . $url . '">espace client BIMP</a><br/><br/>';
                                    $message .= 'Cordialement, <br/><br/>';
                                    $message .= 'L\'équipe BIMP';

                                    $bimpMail = new BimpMail($subject, $email, '', $message);

                                    $mail_errors = array();
                                    $bimpMail->send($mail_errors);

                                    if (count($mail_errors)) {
                                        $warnings[] = BimpTools::getMsgFromArray($mail_errors, 'Echec de l\'envoi de l\'e-mail de notification à l\'adresse "' . $email . '"');
                                    } else {
                                        $nOk++;
                                    }
                                }
                            }
                        }

                        if ($nOk) {
                            $success .= '<br/>E-mail de notification envoyé avec succès pour ' . $nOk . ' utilisateur(s)';
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

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSignElec($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $nom = BimpTools::getArrayValueFromPath($data, 'nom_signataire', '');
        $email = BimpTools::getArrayValueFromPath($data, 'email_signataire', '');

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

        if (!count($errors)) {
            $this->set('signed', 1);
            $this->set('date_signed', date('Y-m-d H:i:s'));
            $this->set('nom_signataire', $nom);
            $this->set('email_signataire', $email);
            $this->set('base_64_signature', $signature);

            $errors = $this->update($warnings, true);

            if (!count($errors)) {
                $doc_errors = $this->writeSignatureOnDoc();

                if (count($doc_errors)) {
                    $warnings[] = 'Echec de l\écriture de la signature sur le document PDF';
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSignDist($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Signature enregistrée avec succès';

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

                $signature = BimpTools::getArrayValueFromPath($data, 'signature', '');

                if (!$signature || $signature == self::$empty_base_64) {
                    $errors[] = 'Signature électronique absente';
                }

                if (!count($errors)) {
                    $this->set('signed', 1);
                    $this->set('date_signed', date('Y-m-d H:i:s'));
                    $this->set('nom_signataire', $nom);
                    $this->set('email_signataire', $userClient->getData('email'));
                    $this->set('id_user_client_signataire', $userClient->id);
                    $this->set('base_64_signature', $signature);
                    $this->set('type', self::TYPE_DIST);
                    $this->set('ip_signataire', BimpTools::getArrayValueFromPath($_SERVER, 'REMOTE_ADDR', ''));

                    $errors = $this->update($warnings, true);

                    if (!count($errors)) {
                        if (method_exists($obj, 'onSigned')) {
                            $warnings = array_merge($warnings, $obj->onSigned($this, $data));
                        }
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}
