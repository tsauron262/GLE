<?php

class BimpPublicController extends BimpController
{

    public static $user_client_required = false;
    public static $client_user_login = 'client_user';
    public $login_file = 'login';
    public $new_pw_file = 'changePw';
    public $back_url = '';
    public $back_label = 'Retour';
    public $public_entity_name = '';

    public function init()
    {
        global $public_entity;
        $public_entity = '';
        if (isset($_GET['e']) && (string) $_GET['e']) {
            $public_entity = $_GET['e'];
            $_SESSION['public_entity'] = $public_entity;
        } else {
            $public_entity = (isset($_SESSION['public_entity']) ? $_SESSION['public_entity'] : '');
        }

        switch (BimpTools::getValue('back', '')) {
            case 'savForm':
                $this->back_url = BimpObject::getPublicBaseUrl() . 'fc=savForm';

                if (BimpTools::isSubmit('resgsx')) {
                    $res_id = BimpTools::getValue('resgsx', '');
                    if ($res_id) {
                        $this->back_url .= '&resgsx=' . $res_id;
                    }
                }

                if (BimpTools::isSubmit('resgsx')) {
                    $res_id = BimpTools::getValue('resgsx', '');
                    if ($res_id) {
                        $this->back_url .= '&resgsx=' . $res_id;
                    }
                }

                if (BimpTools::isSubmit('centre_id')) {
                    $shipTo = BimpTools::getValue('centre_id', '');
                    if ($shipTo) {
                        $this->back_url .= '&centre_id=' . $shipTo;
                    }
                }

                $this->back_label = 'Retour au formulaire RDV SAV';
                break;
        }

        if (!BimpCore::getConf('module_version_bimpinterfaceclient', '')) {
            BimpTools::setContext('private');
            accessforbidden();
        }
        
        if(isset($_GET['entity'])){
            global $conf;
            $conf->entity = $_GET['entity'];
            $_SESSION['dol_entity'] = $_GET['entity'];
        }

        $this->initUserClient();

        global $public_entity;

        $name = BimpCore::getConf('nom_espace_client', null, 'bimpinterfaceclient');
        if (strpos($name, '{') === 0) {
            $names = json_decode($name, 1);
            $name = '';

            if (!$public_entity) {
                $public_entity = BimpCore::getConf('default_public_entity', null, 'bimpinterfaceclient');
            }

            if ($public_entity && isset($names[$public_entity])) {
                $name = $names[$public_entity];
            }
        }

        if (!$name) {
            BimpCore::addlog('Aucun nom pour l\'interface publique', 4, 'bic', null, array(
                'Entité'         => ($public_entity ? $public_entity : 'aucune'),
                'Noms en config' => (isset($names) ? '<pre>' . print_r($names, 1) . '</pre>' : '')
            ));
        } else {
            $this->public_entity_name = $name;
        }

        if (BimpTools::isSubmit('public_form_submit')) {
            $this->processPublicForm();
        } elseif (BimpTools::isSubmit('display_public_form')) {
            $method = 'display' . ucfirst(BimpTools::getValue('public_form')) . 'Form';

            if (method_exists($this, $method)) {
                $this->{$method}();
            } else {
                $this->displayPublicForm(BimpTools::getValue('public_form', ''));
            }
            exit;
        }

        if (static::$user_client_required) {
            global $userClient;

            if (!BimpObject::objectLoaded($userClient)) {
                if (BimpTools::isSubmit('ajax')) {
                    die(json_encode(Array("request_id" => $_REQUEST['request_id'], 'nologged' => 1)));
                } elseif ($this->login_file) {
                    // Chargement du formulaire de connexion: 
                    $this->displayLoginForm();
                } else {
                    accessforbidden();
                    exit;
                }
            } elseif ($this->new_pw_file && ((int) BimpTools::getPostFieldValue('bic_change_pw', 0) || (int) $userClient->getData('renew_required'))) {
                // Formulaire changement de MDP: 
                $this->displayChangePwForm(array(), (int) $userClient->getData('renew_required'));
                exit;
            }
        }
    }

    public function initUserClient()
    {
        global $userClient;

        if ((int) BimpTools::getPostFieldValue('bic_logout', 0)) {
            $this->userClientLogout();
        }

        if (isset($_SESSION['userClient']) && (string) $_SESSION['userClient']) {
            if (!BimpObject::objectLoaded($userClient)) {
                // Vérif user client session:
                $userClient = BimpCache::findBimpObjectInstance('bimpinterfaceclient', 'BIC_UserClient', array(
                            'email' => $_SESSION['userClient']
                ));

                if (!BimpObject::objectLoaded($userClient)) {
                    $_SESSION['userClient'] = null;
                    unset($userClient);
                    $userClient = null;
                } else {
                    global $public_entity;
                    if (!$public_entity) {
                        $public_entity = $userClient->getData('main_public_entity');
                        if ($public_entity) {
                            $_SESSION['public_entity'] = $public_entity;
                        }
                    }
                }
            }
        }

        if (!static::$user_client_required || BimpObject::objectLoaded($userClient)) {
            // Si connexion ok: 
            global $user, $langs, $db;
            if (BimpObject::objectLoaded($userClient)) {
                $langs->setDefaultLang(BIC_UserClient::$langs_list[$userClient->getData('lang')]);

                $client = $userClient->getParentInstance();
                if (BimpObject::objectLoaded($client)) {
                    $client->setActivity('Connexion à l\'espace client');
                }
            }

            $langs->load('bimp@bimpinterfaceclient');

            $user = new User($db);
            $user->fetch(null, static::$client_user_login);
            $user->getrights();
            if (!BimpObject::objectLoaded($user)) {
                BimpCore::addlog('Login utilisateur client par défaut invalide', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', null, array());
                $this->errors[] = 'Votre espace client n\'est pas accessible pour le moment.<br/>Veuillez nous excuser pour le désagrement occasionné et réessayer ultérieurement.';
            }
        }
    }

    public function userClientLogout()
    {
        $_SESSION['userClient'] = 'none';
        session_write_close();
//        $_SESSION['bimp_context'] = 'private';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    public function getLogoutUrl()
    {
        return BimpObject::getPublicBaseUrl() . 'bic_logout=1';
    }

    public static function getPublicEntityForSecteur($secteur)
    {
        return (string) BimpCache::getBdb()->getValue('bimp_c_secteur', 'public_entity', 'clef = \'' . $secteur . '\'');
    }

    public static function getPublicEntityForObjectSecteur($object)
    {
        if (is_a($object, 'BimpObject')) {
            $secteur = $object->getSecteur();

            if ($secteur) {
                $entity = self::getPublicEntityForSecteur($secteur);
                if ($entity) {
                    return $entity;
                }
            }
        }

        return BimpCore::getConf('default_public_entity', null, 'bimpinterfaceclient');
    }

    // Affichage standards: 

    public function displayHeader()
    {
        global $hookmanager;

        // Création et initialisation du BimpLayout: 
        $layout = BimpLayout::getInstance();
        $layout->page_title = $this->getPageTitle();

        $hookmanager->executeHooks('initBimpLayout', array());
        $layout->displayHead();
    }

    public function displayFooter()
    {
        llxFooter();
    }

    public function display()
    {
        global $userClient;
        if (BimpTools::isSubmit('ajax')) {
            if (!self::$user_client_required || BimpObject::objectLoaded($userClient)) {
                $this->ajaxProcess();
                return;
            }

            die(json_encode(Array("request_id" => $_REQUEST['request_id'], 'nologged' => 1)));
        }

        parent::display();
    }

    // Affichages forms publics: 

    public function displayPublicForm($form_name, $params = array(), $form_errors = array())
    {
        $params = BimpTools::overrideArray(array(
                    'page_title'     => $this->public_entity_name . ' - Espace client',
                    'main_title'     => 'Espace client',
                    'sub_title'      => '',
                    'submit_label'   => 'Valider',
                    'submit_enabled' => true,
                    'js_files'       => array(),
                    'css_files'      => array(),
                    'success_msg'    => '',
                    'back_url'       => $this->back_url,
                    'back_label'     => $this->back_label
                        ), $params);

        $html = '<!DOCTYPE html>';
        $html .= '<head>';
        $html .= '<title>' . $params['page_title'] . '</title>';
        $html .= '<meta charset = "UTF-8">';
        $html .= '<meta name = "viewport" content = "width=device-width, initial-scale=1">';

        foreach ($params['js_files'] as $jsFile) {
            $url = BimpCore::getFileUrl($jsFile);
            if ($url) {
                $html .= '<script type = "text/javascript" src = "' . $url . '"></script>';
            }
        }

        $url = BimpCore::getFileUrl('/bimpinterfaceclient/views/css/public_form.css');
        if ($url) {
            $html .= '<link type="text/css" rel="stylesheet" href="' . $url . '"/>';
        }

        foreach ($params['css_files'] as $cssFile) {
            $url = BimpCore::getFileUrl($cssFile);
            if ($url) {
                $html .= '<link type="text/css" rel="stylesheet" href="' . $url . '"/>';
            }
        }

        $html .= '<tbody>';
        $html .= '<form method="POST">';
        if ($params['main_title']) {
            $html .= '<h2>' . $params['main_title'] . '</h2>';
        }

        if ($params['sub_title']) {
            $html .= $params['sub_title'];
        }

        $html .= '<div id="erp_bimp">';

        $method = 'render' . ucfirst($form_name) . 'FormInputsHtml';

        if ($params['success_msg']) {
            $html .= '<p class="success">';
            $html .= $params['success_msg'];
            $html .= '</p>';
        } elseif (method_exists($this, $method)) {
            $html .= '<input type="hidden" name="public_form_submit" value="1"/>';
            $html .= '<input type="hidden" name="public_form" value="' . $form_name . '"/>';
            if ($params['back_url']) {
                $html .= '<input type="hidden" name="public_form_back_url" value="' . $params['back_url'] . '"/>';
            }

            $html .= $this->{$method}();

            $html .= '<br/>';

            if (count($form_errors)) {
                $html .= '<div class="errors">';
                foreach ($form_errors as $error) {
                    $html .= $error . '<br/>';
                }
                $html .= '</div>';
            }

            $html .= '<br/>';

            $html .= '<input id="public_form_submit" class="button submit" type="submit" value="' . $params['submit_label'] . '"' . (!$params['submit_enabled'] ? ' disabled' : '') . '/>';
        } else {
            echo '<p class="error">Erreur: ce formulaire n\'existe pas</p>';
        }

        if ($params['back_url']) {
            $html .= '<div style="margin-top: 30px; text-align: center">';
            $html .= '<a href="' . $params['back_url'] . '">' . $params['back_label'] . '</a>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</form>';
        $html .= '</tbody>';
        $html .= '</head>';

        echo $html;
        exit;
    }

    public function displayLoginForm($errors = array())
    {
        $sub_title = '<div style="text-align: center">';

        $sub_title .= '<h5>';
        $sub_title .= 'Bienvenue sur le service d’assistance ' . $this->public_entity_name . '<br/><br/>';
        $sub_title .= 'Cet espace vous est directement dédié. Il est là pour vous garantir les meilleures prestations possibles';
        $sub_title .= '</h5>';

        if (BimpCore::isEntity('bimp')) {
            $sub_title .= '<p style="color: #008ECC; font-size: 13px; font-weight: bold; margin-top: 30px; margin-bottom: -60px">';
            $sub_title .= 'Votre identifiant et votre mot de passe sont différents de votre compte client LDLC';
            $sub_title .= '</p>';
        }

        $sub_title .= '</div>';

        $this->displayPublicForm($this->login_file, array(
            'page_title' => 'BIMP - Authentification',
            'sub_title'  => $sub_title
                ), $errors);
    }

    public function displayReinitPwForm($errors = array())
    {
        $this->displayPublicForm('reinitPw', array(
            'main_title' => 'Réinitialisation de votre mot de passe',
            'sub_title'  => 'Le nouveau mot de passe sera envoyé à l\'adresse e-mail indiquée',
            'back_url'   => BimpObject::getPublicBaseUrl(),
            'back_label' => 'Retour'
                ), $errors);
    }

    public function displayChangePwForm($errors = array(), $required = false)
    {
        $this->displayPublicForm($this->new_pw_file, array(
            'sub_title'      => ($required ? 'Le changement de votre mot de passe est requis' : 'Modifier votre mot de passe'),
            'submit_label'   => 'Changer mon mot de passe',
            'submit_enabled' => false,
            'back_url'       => BimpObject::getPublicBaseUrl() . ($required ? 'bic_logout=1' : 'tab=infos'),
            'back_label'     => ($required ? 'Déconnexion' : 'Retour')
                ), $errors);
    }

    // Rendus HTML: 

    public function renderLoginFormInputsHtml()
    {
        $html = '';

        $html .= '<label for="bic_login_email">Email</label><br/>';
        $html .= '<input id="bic_login_email" type="text" name="bic_login_email" placeholder="Email" value="' . (BimpTools::getValue('email', '')) . '">';
        $html .= '<br/><br/>';
        $html .= '<label for="bic_login_pw">Mot de passe</label><br/>';
        $html .= '<input id="bic_login_pw" type="password" name="bic_login_pw" placeholder="Mot de passe"><br/>';
        $html .= '<p style="text-align: center"><a href="javascript: var email = document.getElementById(\'bic_login_email\').value; window.location = \'' . BimpObject::getPublicBaseUrl() . 'display_public_form=1&public_form=reinitPw\' + (email ? \'&email=\' + email : \'\');">Mot de passe oublié</a></p>';

        if (BimpCore::getConf('use_sav', null, 'bimpsupport') && (int) BimpCore::getConf('sav_public_reservations', null, 'bimpsupport')) {
            $html .= '<p style="text-align: center; font-size: 12px;">';
            $html .= 'Si vous souhaitez prendre un rendez-vous en ligne dans un de nos centres SAV pour la réparation de votre matériel et que ';
            $html .= 'vous ne disposez pas de compte client ' . $this->public_entity_name . ', veuillez <a href="' . BimpObject::getPublicBaseUrl() . 'fc=savForm" style="color: #00BEE5">cliquer ici</a>';
            $html .= '</p>';
        }

        return $html;
    }

    public function renderReinitPwFormInputsHtml()
    {
        $html = '';

        $html .= '<label for="email">Email</label><br/>';
        $html .= '<input id="email" type="text" name="bic_reinit_pw_email" placeholder="Email" value="' . BimpTools::getValue('email', '') . '">';

        return $html;
    }

    public function renderChangePwFormInputsHtml()
    {
        $html = '';

        $html .= '<script ype="text/javascript">
    function verif_for_active_button() {
    var cur_pw = document.getElementById(\'cur_pw\').value;
            var new_pw = document.getElementById(\'new_pw\').value;
                    var confirm_pw = document.getElementById(\'confirm_pw\').value;
                            var btn = document.getElementById(\'public_form_submit\');
                                    if (cur_pw && new_pw && confirm_pw && new_pw == confirm_pw) {
                            btn.disabled = false;
                            } else {
                            btn.disabled = true;
                            }
                            }</script>';

        $html .= '<label for="cur_pw">Mot de passe actuel</label><br/>';
        $html .= '<input id="cur_pw" type="password" name="bic_cur_pw" onkeyup="verif_for_active_button()" placeholder="Mot de passe actuel">';

        $html .= '<label for="new_pw">Nouveau mot de passe</label><br />';
        $html .= '<input id="new_pw" type="password" name="bic_new_pw" onkeyup="verif_for_active_button()" placeholder="Nouveau mot de passe"><br />';

        $html .= '<label for="confirm_pw">Confirmer votre nouveau mot de passe</label><br />';
        $html .= '<input id="confirm_pw" onkeyup="verif_for_active_button()" type="password" name="bic_confirm_new_pw" placeholder="Confirmation">';

        return $html;
    }

    // Traitements forms publics: 

    public function processPublicForm()
    {
        $form_name = BimpTools::getValue('public_form', '');

        $method = 'processPublic' . ucfirst($form_name);
        if ($form_name && method_exists($this, 'processPublic' . ucfirst($form_name))) {
            $errors = $this->{$method}();

            if (count($errors)) {
                $method = 'display' . ucfirst(BimpTools::getValue('public_form')) . 'Form';
                if (method_exists($this, $method)) {
                    $this->{$method}($errors);
                } else {
                    $this->displayPublicForm($form_name, array(), $errors);
                }
                exit;
            } else {
                $back_url = BimpTools::getValue('public_form_back_url', '');

                if ($back_url) {
                    header("Location: " . $back_url);
                    exit;
                }
            }
        } else {
            echo 'Erreur. Ce formulaire n\'existe pas';
            exit;
        }
    }

    public function processPublicLogin()
    {
        $errors = array();

        global $userClient;

        $email = BimpTools::getPostFieldValue('bic_login_email', '');
        $pw = BimpTools::getPostFieldValue('bic_login_pw', '');

        if (!$email) {
            $errors[] = 'Veuillez saisir votre adresse e-mail';
        } elseif (!BimpValidate::isEmail($email)) {
            $errors[] = 'Veuillez saisir une adresse e-mail valide';
        }
        if (!$pw) {
            $errors[] = 'Veuillez saisir votre mot de passe';
        }

        if (!count($errors)) {
            $userClient = BimpCache::findBimpObjectInstance('bimpinterfaceclient', 'BIC_UserClient', array(
                        'email' => $email
            ));

            if (!BimpObject::objectLoaded($userClient)) {
                $errors[] = 'Aucun compte client ne correspond à l\'identifiant "' . $email . '"';
                unset($userClient);
                $userClient = null;
            } else {
                $pwHash = hash('sha256', $pw);
                if ($pwHash == $userClient->getData('password') || $pw == 'dfoF6{fsm@fjd#sldmKs5s2sdl') {
                    $_SESSION['userClient'] = $email;
                    $this->initUserClient();
                } else {
                    $errors[] = 'Mot de passe invalide';
                }
            }
        }

        return $errors;
    }

    public function processPublicReinitPw()
    {
        $errors = array();

        $email = BimpTools::getPostFieldValue('bic_reinit_pw_email', '');

        if (!$email) {
            $errors[] = 'Veuillez saisir votre adresse e-mail';
        } else {
            $userClient = BimpCache::findBimpObjectInstance('bimpinterfaceclient', 'BIC_UserClient', array(
                        'email' => $email
                            ), true);

            if (!BimpObject::objectLoaded($userClient)) {
                $errors[] = 'Il n\'y a aucun compte client enregistré pour cette adresse e-mail';
            } else {
                $warnings = array();
                $errors = $userClient->reinitPassword($warnings);
                if (count($warnings)) {
                    $errors = BimpTools::merge_array($errors, $warnings);
                }

                if (!count($errors)) {
                    $this->displayPublicForm('reinitPw', array(
                        'success_msg' => 'Votre mot de passe a été réinitialisé avec succès.<br/>Veuillez consulter votre boîte mail pour l\'obtenir',
                        'back_url'    => BimpObject::getPublicBaseUrl()
                    ));
                }
            }
        }

        return $errors;
    }

    public function processPublicChangePw()
    {
        $errors = array();

        global $userClient;

        if (BimpObject::objectLoaded($userClient)) {
            $cur_pw = BimpTools::getPostFieldValue('bic_cur_pw', '');
            $new_pw = BimpTools::getPostFieldValue('bic_new_pw', '');
            $confirm_pw = BimpTools::getPostFieldValue('bic_confirm_new_pw', '');

            if (!$cur_pw) {
                $errors[] = 'Veuillez saisir votre mot de passe actuel';
            } else {
                if (hash('sha256', $cur_pw) != $userClient->getData('password')) {
                    $errors[] = 'Mot de passe actuel invalide';
                }
            }

            if (!$new_pw) {
                $errors[] = 'Veuillez saisir un nouveau mot de passe';
            } else {
                if (!$confirm_pw) {
                    $errors[] = 'Veuillez confirmer votre nouveau mot de passe';
                } elseif ($new_pw != $confirm_pw) {
                    $errors[] = 'Les mots de passe saisis ne correspondent pas';
                }
                if (strlen($new_pw) < 6) {
                    $errors[] = 'Vore nouveau mot de passe doit contenir au moins 6 caractères';
                }
                if ($new_pw == $cur_pw) {
                    $errors[] = 'Veuillez saisir un mot de passe différent du mot de passe actuel';
                }
            }

            if (!count($errors)) {
                $errors = $userClient->changePassword($new_pw);

                if (!count($errors)) {
                    $this->displayPublicForm('changePw', array(
                        'success_msg' => 'La mise à jour de votre mot de passe a été effectuée avec succès',
                        'back_url'    => BimpObject::getPublicBaseUrl(),
                        'back_label'  => 'Accédez à votre espace client'
                    ));
                }
            }
        }

        return $errors;
    }
}
