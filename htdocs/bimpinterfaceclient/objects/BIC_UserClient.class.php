<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

class BIC_UserClient extends BimpObject
{

    public $use_email = true; // Mettre true pour recevoir le mail de création de compte 
    public $db;
    public $loginUser = "client_user";
    public $init = false;
    public $ref = '';
    public static $langs_list = array(
        0 => "fr_FR"
    );

    CONST USER_CLIENT_ROLE_ADMIN = 1;
    CONST USER_CLIENT_ROLE_USER = 0;

    public static $roles = Array(
        self::USER_CLIENT_ROLE_ADMIN => Array('label' => 'Administrateur', 'classes' => Array('important'), 'icon' => 'fas_cogs'),
        self::USER_CLIENT_ROLE_USER  => Array('label' => 'Utilisateur', 'classes' => Array('warning'), 'icon' => 'fas_user')
    );

    CONST USER_CLIENT_STATUS_INACTIF = 0;
    CONST USER_CLIENT_STATUS_ACTIF = 1;

    public static $status = Array(
        self::USER_CLIENT_STATUS_ACTIF   => Array('label' => 'Actif', 'classes' => Array('success'), 'icon' => 'fas_check'),
        self::USER_CLIENT_STATUS_INACTIF => Array('label' => 'Inactif', 'classes' => Array('danger'), 'icon' => 'fas_times')
    );

    public function __construct($module, $object_name)
    {
        global $langs;
        $langs->load('bimp@bimpinterfaceclient');
        parent::__construct($module, $object_name);
    }

    // Droits user: 

    public function canClientView()
    {
        global $userClient;

        if (!BimpObject::objectLoaded($userClient)) {
            return 0;
        }

        if ($this->isLoaded()) {
            if ($this->getData('id_client') == $userClient->getData('id_client') && ($userClient->getData('role') == 1 || $this->id == $userClient->id)) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

    public function canClientCreate()
    {
        global $userClient;

        if (BimpObject::objectLoaded($userClient) && $userClient->isAdmin()) {
            return 1;
        }

        return 0;
    }

    public function canClientEdit()
    {
        if ($this->isLoaded()) {
            global $userClient;

            if (BimpObject::objectLoaded($userClient) && ($userClient->isAdmin() && (int) $this->getData('id_client') == (int) $userClient->getData('id_client'))) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

    public function canClientDelete()
    {
        return $this->canClientEdit();
    }

    public function canEditField($field_name)
    {
        if (BimpCore::isContextPublic()) {
            global $userClient;
            switch ($field_name) {
                case 'id_client':
                case 'renew_required':
                    return 0;

                case 'role':
                case 'status':
                case 'id_contact':
                    if (BimpObject::objectLoaded($userClient) && $userClient->isAdmin()) {
                        return 1;
                    }
                    return 0;
            }

            return 1;
        }

        return parent::canEditField($field_name);
    }

    public function canSetAction($action)
    {
        if (BimpCore::isContextPublic()) {
            global $userClient;

            if (!BimpObject::objectLoaded($userClient)) {
                return 0;
            }

            $is_admin = $userClient->isAdmin();
            $is_itself = $userClient->id == $this->id;

            if (!$is_itself && (!$is_admin || $userClient->getData('id_client') != $this->getData('id_client'))) {
                return 0;
            }

            switch ($action) {
                case 'reinit_password':
                    if ($is_itself) {
                        return 0;
                    }
                    return 1;

                case 'change_password':
                    if (!$is_itself) {
                        return 0;
                    }
                    return 1;

                case 'download_my_data':
                    return 0;

                case 'switchAdmin':
                case 'switchUser':
                    if (!$is_admin || $is_itself) {
                        return 0;
                    }
                    return 1;
            }
        }

        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'reinit_password':
                if ($this->getData('status') != self::USER_CLIENT_STATUS_ACTIF) {
                    $errors[] = 'Ce compte utilisateur n\'est pas actif';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ($force_delete) {
            return 1;
        }

        if ($this->isLoaded()) {
            $where = 'id_client = ' . (int) $this->getData('id_client') . ' AND role = ' . self::USER_CLIENT_ROLE_ADMIN . ' AND id != ' . $this->id;
            $nAdmin = (int) $this->db->getCount($this->getTable(), $where);

            if ($nAdmin > 0) {
                return 1;
            }

            return 0;
        }

        return parent::isDeletable($force_delete, $errors);
    }

    public function isLogged()
    {
        if (BimpCore::isContextPrivate()) {
            return 0;
        }

        if (!$this->isLoaded()) {
            return 0;
        }

        if ($this->getData('status') == self::USER_CLIENT_STATUS_INACTIF) {
            return 0;
        }

        if (isset($_SESSION['userClient']) && $_SESSION['userClient'] && $_SESSION['userClient'] == $this->getData('email')) {
            return 1;
        }

        return 0;
    }

    public function isAdmin()
    {
        return (int) ($this->getData('role') == self::USER_CLIENT_ROLE_ADMIN);
    }

    public function isNotAdmin()
    {
        return ($this->isAdmin()) ? 0 : 1;
    }

    public function showLang()
    {
        return (count(self::$langs_list == 1)) ? 0 : 1;
    }

    // Getters params:

    public function getNameProperties()
    {
        return array('email');
    }

    public function getRefProperty()
    {
        return '';
    }

    public function getPublicActionsButtons()
    {
        $buttons = array();

//        $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';
//        if ($this->canSetAction('reinit_password') && $this->isActionAllowed('reinit_password')) {
//            $buttons[] = array(
//                'label'   => 'Envoyer le mot de passe par e-mail',
//                'icon'    => 'fas_at',
//                'onclick' => $this->getJsActionOnclick('reinit_password', array(), array(
//                    'success_callback' => $callback
//                ))
//            );
//        }
//        if ($this->getData('role') == self::USER_CLIENT_ROLE_USER && $this->canSetAction('switchAdmin') && $this->isActionAllowed('switchAdmin')) {
//            $buttons[] = array(
//                'label'   => 'Passer en administrateur',
//                'icon'    => 'fas_cog',
//                'onclick' => $this->getJsActionOnclick('switchAdmin', array(), array(
////                    'success_callback' => $callback
//                ))
//            );
//        } elseif ($this->getData('role') == self::USER_CLIENT_ROLE_ADMIN && $this->canSetAction('switchUser') && $this->isActionAllowed('switchUser')) {
//            $buttons[] = array(
//                'label'   => 'Passer en utilisateur',
//                'icon'    => 'fas_user',
//                'onclick' => $this->getJsActionOnclick('switchUser', array(), array(
////                    'success_callback' => $callback
//                ))
//            );
//        }
//
//        if ($this->canSetAction('download_my_data') && $this->isActionAllowed('download_my_data')) {
//            $buttons[] = array(
//                'label'   => 'Télécharger mes données personnelles',
//                'icon'    => 'fas_cloud-download-alt',
//                'onclick' => $this->getJsActionOnclick('download_my_data', array(), array(
////                    'success_callback' => $callback
//                ))
//            );
//        }

        if ($this->canSetAction('change_password')) {
            $buttons[] = array(
                'label'   => 'Changer mon mot de passe',
                'icon'    => 'fas_pen',
                'onclick' => 'window.location = \'' . DOL_URL_ROOT . '/bimpinterfaceclient/client.php?display_public_form=1&public_form=changePw\''
            );
        }

        return $buttons;
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('reinit_password') && $this->canSetAction('reinit_password')) {
            $buttons[] = array(
                'label'   => 'Réinitialiser le mot de passe',
                'icon'    => 'fas_undo',
                'onclick' => $this->getJsActionOnclick('reinit_password', array(), array(
                    'confirm_msg' => 'Le mot de passe va être réinitialiser et envoyer par e-mail à l\\\'utilisateur. Veuillez confirmer'
                ))
            );
        }

        if ($this->isLoaded() && !$this->isAdmin()) {
            $userClientContrat = BimpObject::getInstance('bimpinterfaceclient', 'BIC_UserClientContrat');
            $buttons[] = array(
                'label'   => 'Contrats associés',
                'icon'    => 'fas_file-signature',
                'onclick' => $userClientContrat->getJsLoadModalList('public_user_client', array(
                    'extra_filters' => array(
                        'id_user' => (int) $this->id
                    )
                ))
            );
        }

        return $buttons;
    }

    // Getters données:

    public function get_dest($type)
    {
        $return = Array();

        if ((int) $this->getData('id_client')) {
            switch ($type) {
                case 'commerciaux':
                    $commerciaux = BimpTools::getCommercialArray($this->getData('id_client'));
                    foreach ($commerciaux as $id_commercial) {
                        $return[$id_commercial->email] = $id_commercial->email;
                    }
                    break;

                case 'admin':
                    $listUser = $this->getList(array('id_client' => $this->getData('id_client')));
                    foreach ($listUser as $user) {
                        if ($user['id'] != $this->id && $user['role'] == 1) {
                            $return[$user['email']] = $user['email'];
                        }
                    }
                    break;
            }
        }
        return $return;
    }

    public function getContratsVisibles($ouverts_only = false)
    {
        if ($this->isAdmin()) {
            return $this->getAllContrats($ouverts_only);
        }

        $retour = array();
        foreach ($this->getAllContrats($ouverts_only) as $contrat) {
            if ($contrat->can('view')) {
                $retour[$contrat->id] = $contrat;
            }
        }
        return $retour;
    }

    public function getAllContrats($ouverts_only = false)
    {
        //renvoie tous les contrat de nottre soc avec suivant $ouvert que les actifs ou tous
        $return = Array();

        if ((int) $this->getData('id_client')) {
            $contrat = $this->getInstance('bimpcontract', 'BContract_contrat');
            $list = $contrat->getList(Array('fk_soc' => $this->getData('id_client')));

            foreach ($list as $on_contrat) {
                $instance = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $on_contrat['rowid']);

                if ((!$ouverts_only || $instance->isValide()) && (int) $instance->getData('statut') > 0) {
                    $return[$on_contrat['rowid']] = $instance;
                }

                $instance = null;
            }
        }
        return $return;
    }

    public function getAssociatedContratsList()
    {
        if ($this->isLoaded()) {
            $cache_key = 'user_client_' . $this->id . '_associated_contrats_list';

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();
                $rows = $this->db->getRows('bic_user_contrat', 'id_user = ' . $this->id, null, 'array', array('id_contrat'));

                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        self::$cache[$cache_key][] = (int) $r['id_contrat'];
                    }
                }
            }

            return self::$cache[$cache_key];
        }

        return array();
    }

    // Affichage:

    public function displayHeader()
    {
        $return = '';
        $soc = $this->getChildObject('client');
        $return .= "<br/>" . $soc->getLink();
        return $return;
    }

    // Rendus HTML: 

    public function renderHeaderStatusExtra()
    {
        $extra = '';
        if ($this->getData('role') == self::USER_CLIENT_ROLE_ADMIN) {
            $extra .= '&nbsp;&nbsp;<span class="important">' . BimpRender::renderIcon('fas_cog', 'iconLeft') . 'Administrateur</span>';
        } else {
            $extra .= '&nbsp;&nbsp;<span class="warning">' . BimpRender::renderIcon('fas_user', 'iconLeft') . 'Utilisateur</span>';
        }

        $extra .= $this->displayHeader();

        if ($this->getData('renew_required')) {
            $extra .= '&nbsp;&nbsp;<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Doit changer son mot de passe</span>';
        }
        return $extra;
    }

    // Traitements:

    public function init()
    {
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

    public function GOT($email)
    {
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

    public function switch_lang($new_lang)
    {
        $this->updateField("lang", $new_lang, true);
        $this->set("lang", $new_lang);
        $this->update($warning = array(), true);
//        $bimp->update('bic_user', Array('lang' => $new_lang), 'id = ' . $this->id);
        echo '<script>window.location.href = "?"</script>';
    }

    public function lang($field, $lcfirst = false)
    {
        global $langs;

        if ($lcfirst) {
            return lcfirst($langs->trans($field));
        }

        return $langs->trans($field);
    }

    public function changePassword($new_pw)
    {
        $errors = array();

        $err = $this->updateField('password', hash('sha256', $new_pw));

        if (count($err)) {
            $errors[] = 'Echec de la mise à jour de votre mot de passe';
        }

        $msg = 'Bonjour, ' . "\n\n";
        $msg .= 'Le mot de passe de votre espace client BIMP a été changé.' . "\n";
        $msg .= 'Si vous n\'êtes pas à l\'origine de cette action veuillez contacter votre administrateur';

        mailSyn2('Espace client BIMP - Changement de votre mot de passe', $this->getData('email'), '', $msg);
        $this->updateField('renew_required', 0);

        return $errors;
    }

    public function reinitPassword(&$warnings = array())
    {
        $errors = array();

        $mdp_clear = BimpTools::randomPassword(7);
        $this->set('password', hash('sha256', $mdp_clear));
        $this->set('renew_required', 1);

        $errors = $this->update($warnings, true);

        if (!count($errors)) {
            $subject = 'Changement de mot de passe';
            $msg = 'Bonjour,<br/><br/>Le mot de passe pour votre accès à l\'espace client BIMP a été réinitialisé<br />';
            $msg .= 'Nouveau mot de passe: ' . $mdp_clear;
        }

        if (!mailSyn2($subject, BimpTools::cleanEmailsStr($this->getData('email')), '', $msg)) {
            $warnings[] = 'Echec de l\'envoi du mot de passe par e-mail';
        }

        return $errors;
    }

    // Actions: 

    public function actionSwitchUser($data, &$success)
    {
        $errors = $this->updateField('role', self::USER_CLIENT_ROLE_USER);
        $success = "Passé au statut utilisateur avec succes";

        return array('errors' => $errors);
    }

    public function actionSwitchAdmin($data, &$success)
    {
        $errors = $this->updateField('role', self::USER_CLIENT_ROLE_ADMIN);
        $success = "Passé au statut administrateur avec succes";

        return array('errors' => $errors);
    }

    public function actionGeneratePassword($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Mot de passe généré avec succès';

        $mdp_clear = BimpTools::randomPassword(7);
        $this->set('password', hash('sha256', $mdp_clear));
        $this->set('renew_required', 1);

        $errors = $this->update($warnings, true);
        $errors = $this->update($warnings, true);

        if (!count($errors)) {
            $subject = 'Espace client BIMP - Vos identifiants';
            $msg = 'Identifiant : ' . $this->getData('email') . '<br />Mot de passe (Généré automatiquement) : ' . $mdp_clear;

            if (!mailSyn2($subject, BimpTools::cleanEmailsStr($this->getData('email')), '', $msg)) {
                $warnings[] = 'Echec de l\'envoi du mot de passe par e-mail';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionReinit_password($data, &$success)
    {
        $warnings = array();
        $success = 'Mot de passe réinitialisé avec succès';

        $errors = $this->reinitPassword($warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        $mdp_clear = '';
        if (!$this->getData('password')) {
            $mdp_clear = BimpTools::randomPassword(7);
            $this->set('password', hash('sha256', $mdp_clear));
        }

        $email = $this->getData('email');

        if (!$email) {
            $errors[] = 'Adresse e-mail absente';
        } elseif (!BimpValidate::isEmail($email)) {
            $errors[] = 'Adresse e-mail invalide';
        } elseif ((int) $this->db->getValue($this->getTable(), 'id', 'email = \'' . $email . '\'')) {
            $errors[] = 'Un compte utilisateur existe déjà pour cette adresse e-mail';
        }

        if (!count($errors)) {
            if (!(int) $this->getData('role')) {
                $nbAdmin = (int) $this->db->getCount('bic_user', 'id_client = ' . (int) $this->getData('id_client') . ' AND role = 1');

                if (!$nbAdmin) {
                    $this->set('role', 1);
                }
            }

            $errors = parent::create($warnings, $force_create);

            if (!count($errors)) {
                if ($this->use_email && BimpTools::getPostFieldValue('send_mail', 0)) {
                    if (stripos(DOL_URL_ROOT, $_SERVER['SERVER_NAME']) === false)
                        $url = $_SERVER['SERVER_NAME'] . DOL_URL_ROOT . '/bimpinterfaceclient/client.php';
                    else
                        $url = DOL_URL_ROOT . '/bimpinterfaceclient/client.php?email=' . $this->getData('email');
                    $sujet = "Mot de passe BIMP ERP Interface Client";

                    $message = "Bonjour, <br /><br />";
                    $message .= "Bienvenue sur le service d’assistante de BIMP.<br />";
                    $message .= "Cet espace vous est directement dédié. Il est là pour vous garantir les meilleures prestations possibles.<br /><br/>";

                    $contrats = $this->getContratsVisibles(true);

                    if (count($contrats)) {
                        $message .= '<b>Tickets supports: </b>';
                        $message .= "Chaque ticket déclaré représente la feuille de route de votre incident, tout y est récapitulé afin de garantir un suivi optimal lors du processus de résolution.<br /><br />";
                        $message .= "<ul>";
                        $message .= '<li>Une fois ouvert, vous recevez un e-mail de confirmation de la prise en charge de votre demande</li>';
                        //$message.= '<li>À chaque avancée dans la résolution du problème, vous êtes informés des opérations effectuées et de ce qui va se passer ensuite</li>';
                        $message .= "<li>Une fois la solution trouvée, votre ticket est clos.</li>";
                        $message .= "</ul><br /><br />";
                        $message .= "Si toutefois le problème n’est pas résolu, le ticket est attribué à un autre technicien.<br />";
                        $message .= "Chez BIMP, nous faisons aussi le pari de la complémentarité des compétences dans nos équipes !<br />";
                        $message .= "Vous avez la possibilité de contacter directement l’assistance technique au numéro figurant sur votre contrat (bien laisser un message) ou par mail à hotline@bimp.fr <br />";
                        $message .= "Le service est joignable du lundi au vendredi  (de 9 h à 12 h et de 14 h à 18 h, le vendredi à 17 h.<br /><br />";
                        $message .= "Voici votre accès à votre espace client <br /><br />";
                    }

                    $message .= '<a href="' . $url . '">Espace client BIMP ERP</a><br />';
                    $message .= 'Identifiant : ' . $email . '<br />';
                    if ($mdp_clear) {
                        $message .= 'Mot de passe (Généré automatiquement) : ' . $mdp_clear;
                    }
                    $message .= '<br /><br /><br />';
                    $url_notice = "https://r.emailing.bimp-groupe.fr/mk/cl/f/fjaJDCZiyHn3ixmdjVfLHPUSzRBzlYjsfpssdw_dklmhgN7Rlm7ztqBEXLbIKJtMnEQgq_c8PnFXMmE7kB1jjsugCTsEJ7RQFNYG0t5Ks3vd_8ZYmKBoRLUFdzaJ0xHmKqyZtY7pQaJAMxOhD1AEEmrWT3yc660gskTYZLe8VetnKI-LyDzSgxOPfNV9sML4h-Y_0mMwr1V8ltNqeEzbtdlUajs02Fnek4SHgHsktedp4Qn40gRovH788YIpeD1SdAb7Oav0KBONH487Exm1-FiwSDTsmzbKE3DrrrHG0mgmuisHe4F04sEhyWZZIyfXSfasmhwq1TEd33NhdA5aizTj9oXJnYW-JM3Ph5e1oavhKYsMEu2bAJBggH0e1w";
                    $message .= "<a href='" . $url_notice . "'>Notice d'utilisation</a>";

                    mailSyn2($sujet, BimpTools::cleanEmailsStr($email), '', $message);
                }
            }
        }

        return $errors;
    }

    public function update(&$warnings = [], $force_update = false)
    {
        $errors = array();

        if ($this->getData('email') != $this->getInitData('email')) {
            $email = $this->getData('email');

            if (!$email) {
                $errors[] = 'Adresse e-mail absente';
            } elseif (!BimpValidate::isEmail($email)) {
                $errors[] = 'Adresse e-mail invalide';
            } else {
                $id_user_client = (int) $this->db->getValue($this->getTable(), 'id', 'email = \'' . $email . '\'');
                if ($id_user_client && $id_user_client !== (int) $this->id) {
                    $errors[] = 'Un compte utilisateur existe déjà pour cette adresse e-mail';
                }
            }
        }

        if (!count($errors)) {
            $errors = parent::update($warnings, $force_update);
        }

        return $errors;
    }
}
