<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

class BIC_UserClient extends BimpObject {

    public $use_email = true; // Mettre true pour recevoir le mail de création de compte 
    public $db;
    public $loginUser = "client_user";
    public $init = false;
    public $ref = '';
    public static $langs_list = array("fr_FR");

    # Constantes
        
    CONST USER_CLIENT_ROLE_ADMIN = 1;
    CONST USER_CLIENT_ROLE_USER = 0;
    CONST USER_CLIENT_STATUS_ACTIF = 1;
    CONST USER_CLIENT_STATUS_INACTIF = 2;

    # Tableaux static

    public static $role = Array(
        self::USER_CLIENT_ROLE_ADMIN => Array('label' => 'Administrateur', 'classes' => Array('important'), 'icon' => 'cogs'),
        self::USER_CLIENT_ROLE_USER => Array('label' => 'Utilisateur', 'classes' => Array('warning'), 'icon' => 'user')
    );
    public static $role_en_us = Array(
        self::USER_CLIENT_ROLE_ADMIN => Array('label' => 'Administrator', 'classes' => Array('important'), 'icon' => 'cogs'),
        self::USER_CLIENT_ROLE_USER => Array('label' => 'User', 'classes' => Array('warning'), 'icon' => 'user')
    );
    public static $status = Array(
        self::USER_CLIENT_STATUS_ACTIF => Array('label' => 'Actif', 'classes' => Array('success'), 'icon' => 'check'),
        self::USER_CLIENT_STATUS_INACTIF => Array('label' => 'Inactif', 'classes' => Array('danger'), 'icon' => 'times')
    );

    public function getName() {
        return $this->getData("email");
    }

    public function showLang() {
        return (count(self::$langs_list == 1)) ? 0 : 1;
    }

    public function renderHeaderStatusExtra() {

        $extra = '';
        if ($this->getData('role') == self::USER_CLIENT_ROLE_ADMIN) {
            $extra .= '&nbsp;&nbsp;<span class="important">' . BimpRender::renderIcon('fas_cog', 'iconLeft') . 'Administrateur</span>';
        } else {
            $extra .= '&nbsp;&nbsp;<span class="warning">' . BimpRender::renderIcon('fas_user', 'iconLeft') . 'Utilisateur</span>';
        }

        if ($this->getData('renew_required')) {
            $extra .= '&nbsp;&nbsp;<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Doit changer son mot de passe</span>';
        }
        return $extra;
    }

    public function getActionsButtons() {
        global $userClient;

        $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';
        if ((isset($userClient) && $userClient->getData('role') == self::USER_CLIENT_ROLE_ADMIN && $this->id != $userClient->id ) || BimpTools::getContext() == 'private') {
            if ($this->getData('status') == self::USER_CLIENT_STATUS_ACTIF) {
                $buttons[] = array(
                    'label' => 'Envoyer le mot de passe par mail',
                    'icon' => 'fas_at',
                    'onclick' => $this->getJsActionOnclick('reinit_password', array(), array(
                        'success_callback' => $callback
                    ))
                );
            }




            if ($this->getData('role') == self::USER_CLIENT_ROLE_USER) {
                $buttons[] = array(
                    'label' => 'Passer en administrateur',
                    'icon' => 'fas_cog',
                    'onclick' => $this->getJsActionOnclick('switchAdmin', array(), array(
                        'success_callback' => $callback
                    ))
                );
            } elseif ($this->getData('role') == self::USER_CLIENT_ROLE_ADMIN) {
                $buttons[] = array(
                    'label' => 'Passer en utilisateur',
                    'icon' => 'fas_user',
                    'onclick' => $this->getJsActionOnclick('switchUser', array(), array(
                        'success_callback' => $callback
                    ))
                );
            }
        }

        if ($this->id == $userClient->id) {
            $buttons[] = array(
                'label' => 'Télécharger mes données personnelles',
                'icon' => 'fas_cloud-download-alt',
                'onclick' => $this->getJsActionOnclick('download_my_data', array(), array(
                    'success_callback' => $callback
                ))
            );
        }


        return $buttons;
    }

    # Actions
    
    public function download_my_data() {
        
    }

    public function actionSwitchUser($data, &$success) {
        $this->updateField('role', self::USER_CLIENT_ROLE_USER);
        $success = "Passé au statut utilisateur avec succes";
    }

    public function actionSwitchAdmin($data, &$success) {
        $this->updateField('role', self::USER_CLIENT_ROLE_ADMIN);
        $success = "Passé au statut administrateur avec succes";
    }

    public function it_is_admin() {
        if ($this->getData('role') == self::USER_CLIENT_ROLE_ADMIN) {
            return 1;
        } else {
            return 0;
        }
    }

    public function it_is_not_admin() {
        return ($this->it_is_admin()) ? 0 : 1;
        //return 1;
    }

    public function actionGeneratePassword() {
        $mot_de_passe = $this->generatePassword();
        $this->updateField('password', $mot_de_passe->sha256);
        $this->updateField('renew_required', 1);
        mailSyn2('Mot de passe BIMP ERP Interface Client', $this->getData('email'), 'noreply@bimp.fr', 'Identifiant : ' . $this->getData('email') . '<br />Mot de passe (Généré automatiquement) : ' . $mot_de_passe->clear);
    }

    public function displayEmail() {
        return $this->getData('email');
    }

    public function canClientView() {
        global $userClient;
        if (!$this->isLoaded() || !is_object($userClient)) {
            return true;
        }
        if ($this->getData('attached_societe') == $userClient->getData('attached_societe') && ($userClient->getData('role') == 1) || $this->id == $userClient->id) {
            return true;
        }
        return false;
    }

    public function canClientEdit() {
        return $this->canClientView();
    }

    public function canClientCreate() {
        global $userClient;
        if (is_object($userClient) && $userClient->getData('status') == self::USER_CLIENT_ROLE_ADMIN)
            return true;

        return false;
    }

    public function connexion($mail, $password) {
        global $db;
        $bimp = new BimpDb($db);
        $password = hash('sha256', $password);
        if ($bimp->getValue('bic_user', 'email', 'email = "' . $mail . '" AND password = "' . $password . '" AND status = 1')) {
            return 1;
        }
        return 0;
    }

    public function GOT($email) {
        global $db;
        $this->email = $email;
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . 'bic_user' . ' WHERE email = "' . $email . '"'; // CHERCHER AUSSI AVEC LE STATUS 1
        $sql = $db->query($sql);
        if ($resql = $db->fetch_object($sql)) {
            $this->fetch($resql->id);
        } else {
            echo BimpRender::renderAlerts("Utilisateur non trouvé dans nos bases", 'danger', false);
        }
    }

    public function switch_lang($new_lang) {
        $this->updateField("lang", $new_lang, true);
        $this->set("lang", $new_lang);
        $this->update($warning = array(), true);
//        $bimp->update('bic_user', Array('lang' => $new_lang), 'id = ' . $this->id);
        echo '<script>window.location.href = "?"</script>';
    }

    public function lang($field) {
        global $langs;
        return $langs->trans($field);
    }

    public function init() {
        global $user;
        if (isset($_SESSION['userClient'])) {
            $this->GOT($_SESSION['userClient']);
            $connected_client = $this->id;
            $client = new Societe($this->db->db);
            $client->fetch($connected_client);

            $user = new User($this->db->db);
            $user->fetch(null, $this->loginUser);
            $user->getrights();
            if ($user->id < 1)
                die('Attention ' . $this->loginUser . ' n\'existe pas');
        }
        $this->init = true;
    }

    public function getContratVisible($ouvert = false) {
        $retour = array();
        $socContrats = $this->getAllContrats($ouvert);
        if ($this->it_is_admin()) {
            $retour = $socContrats;
        } else {
            foreach ($socContrats as $contrat) {
                if ($contrat->can('view'))
                    $retour[$contrat->id] = $contrat;
            }
        }
        return $retour;
    }

    public function getAllContrats($ouvert = false) {
        //renvoie tous les contrat de nottre soc avec suivant $ouvert que les actifs ou tous
        $contrat = $this->getInstance('bimpcontract', 'BContract_contrat');
        $list = $contrat->getList(Array('fk_soc' => $this->getData('attached_societe')));
        //if($ouvert) {
        $return = Array();
        foreach ($list as $on_contrat) {
            $instance = $this->getInstance('bimpcontract', 'BContract_contrat', $on_contrat['rowid']);
            if (($ouvert == false || $instance->isValide()) && $instance->getData('statut') > 0) {
                $return[$on_contrat['rowid']] = $instance;
            }
            $instance = null;
        }
        return $return;
    }

    public function isLoged() {
        if ($_SESSION['userClient'] && BimpTools::getContext() == "public") {

            if ($this->getData('status') == self::USER_CLIENT_STATUS_INACTIF) {

                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    public function deconnexion() {
        $_SESSION['userClient'] = null;
        $userClient = null;
    }

    public function random_password($l, $c = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') {
        for ($i = 0, $z = strlen($c) - 1, $s = $c{rand(0, $z)}, $i = 1; $i != $l; $x = rand(0, $z), $s .= $c{$x}, $s = ($s{$i} == $s{$i - 1} ? substr($s, 0, -1) : $s), $i = strlen($s))
            ;
        return $s;
    }

    public function generatePassword($lenght = 7) {
        $password = $this->random_password($lenght);
        return (object) Array('clear' => $password, 'sha256' => hash('sha256', $password));
    }

    public function change_password($post) {
        $this->updateField('password', hash('sha256', $post));
        mailSyn2('Changement de votre mot de passe', $this->getData('email'), 'noreply@bimp.fr', "Votre mot de passe a été changé, si vous n'êtes pas à l'origine de cette action veuillez contacter votre administrateur");
        $this->updateField('renew_required', 0);
    }

    public function actionReinit_password($data, &$success) {
        $passwords = $this->generatePassword();
        $this->updateField('renew_required', 1);
        mailSyn2('Changement de mot de passe', $this->getData('email'), 'noreply@bimp.fr', "Votre mot de passe a été changé par votre administrateur <br /> Votre nouveau mot de passe est : $passwords->clear");
        $this->updateField('password', $passwords->sha256);
        $success = 'Mot de passe réinitialisé';
        return array(
            'errors' => array(),
            'warnings' => array()
        );
    }

    public function get_dest($type) {
        $return = Array();
        switch ($type) {
            case 'commerciaux':
                $commerciaux = BimpTools::getCommercialArray($this->getData('attached_societe'));
                foreach ($commerciaux as $id_commercial) {
                    $return[$id_commercial->email] =  $id_commercial->email;
                }
                break;
            case 'admin':
                $listUser = $this->getList(array('attached_societe' => $this->getData('attached_societe')));
                foreach ($listUser as $user) {
                    if ($user['id'] != $this->id && $user['role'] == 1) {
                        $return[$user['email']] =  $user['email'];
                    }
                }
                break;
        }
        return $return;
    }
    
    public function what_is_the_context() {
        if(BimpTools::getContext() == 'public') return 0;
        return 1;
    }
    
    public function create(&$warnings = array(), $force_create = false) {
        $mot_de_passe = $this->generatePassword();
        if ($this->getList(array('email' => BimpTools::getValue('email')))) {
            return $this->lang('ERemailExist');
        }
        if (empty(BimpTools::getValue('email'))) {
            return $this->lang('ERemailVide');
        }
        if (parent::create($warnings, $force_create) > 1) {
            $this->updateField('password', $mot_de_passe->sha256);
            $this->updateField('renew_required', 1);
            if ($this->use_email && BimpTools::getValue('send_mail')) {
                if(stripos(DOL_URL_ROOT, $_SERVER['SERVER_NAME']) === false)
                    $url = $_SERVER['SERVER_NAME']. DOL_URL_ROOT . '/bimpinterfaceclient/client.php';
                else
                    $url = DOL_URL_ROOT . '/bimpinterfaceclient/client.php';
                $sujet = "Mot de passe BIMP ERP Interface Client";
                $message = 'Bonjour,<br /> Voici votre accès à votre espace client <br />'
                        . '<a href="'.$url.'">Espace client BIMP ERP</a><br />Identifiant : ' . $this->getData('email') . '<br />Mot de passe (Généré automatiquement) : ' . $mot_de_passe->clear;
                mailSyn2($sujet, $this->getData('email'), '', $message);
            }
        }
    }

}
