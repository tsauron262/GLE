<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class API_UserAccount extends BimpObject
{

    // Droits users:

    public function canViewField($field_name)
    {
        global $user;

        if ($user->admin) {
            return 1;
        }

        if (in_array($field_name, array('pword', 'tokens'))) {
            return $this->isUserIn();
        }

        return 1;
    }

    public function canSetAction($action)
    {
        global $user;

        if ($user->admin) {
            return 1;
        }

        switch ($action) {
            case 'connect':
                return ($this->isUserIn() || $this->isDefaultUserAccount()); // Tous les users peuvent connecter le compte par défaut. 
        }
        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isLogged()
    {
        $end = (string) $this->getData('logged_end');

        if ($end && $end !== '0000-00-00 00:00:00' && $end > date('Y-m-d H:i:s')) {
            return 1;
        }

        return 0;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array(''))) {
            if (!$this->isLoaded($errors)) {
                return 0;
            }
        }

        switch ($action) {
            case 'connect':
                if ($this->isLoaded()) {
                    $api = $this->getParentInstance();

                    if (!BimpObject::objectLoaded($api) || !(int) $api->getData('active')) {
                        $errors[] = 'API non installée ou inactive';
                        return 0;
                    }
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function isUserIn($id_user = 0)
    {
        if (!$id_user) {
            global $user;
            $id_user = $user->id;
        }

        if (in_array($id_user, $this->getData('users'))) {
            return 1;
        }

        return 0;
    }

    public function isDefaultUserAccount()
    {
        if ($this->isLoaded()) {
            $api = $this->getParentInstance();

            if (BimpObject::objectLoaded($api)) {
                if ((int) $api->getDefaultUserAccountId() === $this->id) {
                    return 1;
                }
            }
        }

        return 0;
    }

    // Getters params:

    public function getDefaultListExtraButtons()
    {
        $buttons = array();

//        $buttons[] = array(
//            'label'   => 'TEST Auth',
//            'icon'    => 'fas_unlock-alt',
//            'onclick' => $this->getJsActionOnclick('setToken', array(
//                'api_name' => 'test'
//                    ), array(
//                'form_name' => 'activation_token'
//            ))
//        );

        return $buttons;
    }

    public function getListBulkActions()
    {
        $actions = array();

        if ($this->canSetAction('connect')) {
            $actions[] = array(
                'label'   => 'Connecter les comptes sélectionnés',
                'icon'    => 'fas_power-off',
                'onclick' => 'setSelectedObjectsAction($(this), \'list_id\', \'connect\', {}, null, \'\', true)'
            );
        }

        if ($this->canDelete()) {
            $actions[] = array(
                'label'   => 'supprimer les comptes sélectionnés',
                'icon'    => 'fas_trash-alt',
                'onclick' => 'deleteSelectedObjects(\'list_id\', $(this))'
            );
        }

        return $actions;
    }

    public function getExternalTokenFormTitle()
    {
        $api_name = BimpTools::getPostFieldValue('api_name', '');

        if ($api_name) {
            $api = BimpAPI::getApiInstance($api_name);

            if (is_a($api, 'BimpAPI')) {
                return 'Authentification API "' . $api->public_name . '"';
            }
        }

        return 'Erreur: API invalide';
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('connect') && $this->canSetAction('connect')) {
            $buttons[] = array(
                'label'   => ($this->isLogged() ? 'Réinitialiser la connexion' : 'Connecter'),
                'icon'    => 'fas_power-off',
                'onclick' => $this->getJsActionOnclick('connect', array(), array())
            );
        }

        return $buttons;
    }

    // Getters Array: 

    public function getApisArray($include_empty = false, $active_only = false, $key = 'id')
    {
        BimpObject::loadClass('bimpapi', 'API_Api');
        return API_Api::getApisArray($include_empty, $active_only, $key);
    }

    // Getters données: 

    public function getApiInstance()
    {
        $api_obj = $this->getParentInstance();

        if (BimpObject::objectLoaded($api_obj)) {
            return $api_obj->getApiInstance();
        }

        return null;
    }

    public function getToken($type)
    {
        $tokens = $this->getData('tokens');

        if (isset($tokens[$type])) {
            return $tokens[$type];
        }

        return '';
    }

    public function getTokenLight()
    {
        return BimpTools::getDataLightWithPopover($this->displayTokens(), 50);
    }

    // Affichages: 

    public function displayTokens()
    {
        $html = '';

        $tokens = $this->getData('tokens');

        if (is_array($tokens) && !empty($tokens)) {
            $api = $this->getApiInstance();

            foreach ($tokens as $type => $token) {
                $html .= ($html ? '<br/>' : '');
                if (is_a($api, 'BimpAPI') && isset($api::$tokens_types[$type])) {
                    $html .= '<b>' . $api::$tokens_types[$type] . ': </b>';
                } else {
                    $html .= '<b>' . $type . ': </b>';
                }

                if ($token) {
                    $html .= $token;
                } else {
                    $html .= '<span class="danger">absent</span>';
                }
            }
        }

        return $html;
    }

    public function displayLogged()
    {
        $html = '';

        if ($this->isLogged()) {
            $html .= '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Connecté</span>';
        } else {
            $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Déconnecté</span>';
        }

        return $html;
    }

    // Rendus HTML:

    public function renderExternalTokenInputExtraContent()
    {
        $html = '';
        $errors = array();

        $api_name = BimpTools::getPostFieldValue('api_name', '');

        if ($api_name) {
            $api = BimpAPI::getApiInstance($api_name);

            if (!is_a($api, 'BimpAPI')) {
                $errors[] = 'API invalide: "' . $api_name . '"';
            }
        } else {
            $errors[] = 'Type d\'API absent';
        }

        $html .= '<div style="margin: 15px 0">';

        if (!count($errors)) {
            if ($api::$mode === 'test') {
                $html .= '<p>';
                $html .= '<span class="danger">' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'Mode TEST activé</span>';
                $html .= '</p>';
            }

            $login_url = BimpTools::getArrayValueFromPath($api::$urls, 'login/' . $api::$mode, '');

            if (!$login_url) {
                $errors[] = 'Aucune URL définie pour l\'authentification sur cette API - ' . $api::$mode . ' - ' . $api::$name;
            } else {
                $onclick = 'window.open(\'' . $login_url . '\', \'Authentification API ' . $api::$public_name . '\', \'menubar=no, status=no, width=800, height=600\')';
                $html .= '<script>' . $onclick . '</script>';
                $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                $html .= 'Réouvrir la fenêtre d\'authentification' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
                $html .= '</span>';

                if (BimpObject::objectLoaded($api->userData)) {
                    $html .= '<h4>Rappel de votre identifiant: </h4>';
                    $html .= '<strong>Identifiant: </strong>' . $api->userData->getData('login') . '<br/>';
                    if ($api->is_default_user) {
                        $html .= '<strong>Mot de passe</strong>: ' . $api->userData->getData('pword');
                        // Todo : gérer réception code de manière générique... 
//                        $html .= '<script>'
//                                . 'var idMaxMesg = 0;'
//                                . 'function checkCode() {'
//                                . ' setObjectAction(null, {"module":"bimpsupport", "object_name":"BS_SAV"}, "getCodeApple", {"idMax":idMaxMesg});'
//                                . '}'
//                                . 'checkCode();'
//                                . '</script>';
                    }
                }

                $html .= '<p class="small" style="text-align: center; margin-top: 15px">';
                $html .= 'Si la fenêtre d\'authentification ne s\'ouvre pas, veuillez vérifier que votre navigateur ne bloque pas l\'ouverture des fenêtres pop-up';
                $html .= '</p>';
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        $html .= '</div>';

        return $html;
    }

    // Processus: 

    public function connect(&$errors = array(), &$warnings = array())
    {
        $api = $this->getParentInstance();
        $api_instance = $api->getApiInstance();
        $errors = $api_instance->setUser($this->id);

        if (!count($errors)) {
            return $api_instance->connect($errors, $warnings);
        }

        return false;
    }

    public function saveToken($type, $token, $logged_end = 'no_update', &$warnings = array())
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $tokens = $this->getData('tokens');

            if (!is_array($tokens)) {
                $tokens = array();
            }

            $tokens[$type] = $token;

            $this->set('tokens', $tokens);

            if ($logged_end !== 'no_update') {
                $this->set('logged_end', $logged_end);
            }

            return $this->update($warnings, true);
        }

        return $errors;
    }

    // Actions: 

    public function actionConnect($data, &$success = '')
    {
        $errors = array();
        $warnings = array();

        if ($this->isLoaded()) {
            $success = 'Connexion réussie';
            $this->connect($errors, $warnings);
        } else {
            $ids = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

            if (count($ids)) {
                $nOk = 0;
                foreach ($ids as $id) {
                    $userAccount = BimpCache::getBimpObjectInstance('bimpapi', 'API_UserAccount', $id);

                    if (BimpObject::objectLoaded($userAccount)) {
                        $user_errors = array();

                        if ($userAccount->isActionAllowed('connect', $user_errors)) {
                            $userAccount->connect($user_errors);
                        }

                        if (count($user_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($user_errors, 'Compte #' . $id . ' - ' . $userAccount->getData('name'));
                        } else {
                            $nOk++;
                        }
                    } else {
                        $warnings[] = 'Le compte utilisateur #' . $id . ' n\'existe plus';
                    }
                }

                if ($nOk) {
                    $success = $nOk . ' connexion(s) réussie(s)';
                } else {
                    $errors[] = 'Echec de la connexion de tous les comptes sélectionnés';
                }
            } else {
                $errors[] = 'Aucun compte utilisateur sélectionné';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSetExternalToken($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $api_name = BimpTools::getArrayValueFromPath($data, 'api_name', '');
        $token_type = BimpTools::getArrayValueFromPath($data, 'token_type', '');
        $token = BimpTools::getArrayValueFromPath($data, 'token', '');

        if (!$api_name) {
            $errors[] = 'Type d\'API absent';
        }

        if (!$token_type) {
            $errors[] = 'Type de token absent';
        }

        if (!$token) {
            $errors[] = 'Veuillez saisir le token';
        }

        if (!count($errors)) {
            $api = BimpAPI::getApiInstance($api_name);

            if (!is_a($api, 'BimpAPI')) {
                $errors[] = 'API invalide: "' . $api_name . '"';
            } else {
                $errors = $api->setToken($token_type, $token);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            $users = $this->getData('users');
            $init_users = $this->getInitData('users');

            $where_base = 'id_api = ' . (int) $this->getData('id_api') . ' AND users LIKE ';

            foreach ($users as $id_user) {
                if (!is_array($init_users) || !in_array($id_user, $init_users)) {
                    $id = (int) $this->db->getValue($this->getTable(), 'id', $where_base . '\'%[' . $id_user . ']%\'');
                    if ($id) {
                        $msg = 'L\'utilisteur ';
                        $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);

                        if (BimpObject::objectLoaded($user)) {
                            $msg .= '"' . $user->getName() . '"';
                        } else {
                            $msg .= '#' . $id_user;
                        }
                        $msg .= ' à déjà été ajouté à ce compte utilisateur API';

                        $errors[] = $msg;
                    }
                }
            }
        }

        return $errors;
    }
}
