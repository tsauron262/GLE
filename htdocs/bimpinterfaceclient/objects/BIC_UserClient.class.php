<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
class BIC_UserClient extends BimpObject {

    public $db;
    public $loginUser = "client_user";
    public $init = false;
    public $ref = 'hjgfghj';
    public static $langs_list = array("fr_FR", 'en_US', 'de_DE', 'es_ES');
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
    
    public function __construct($module, $object_name) {
        global $langs;
        $langs->load('bimp@bimpinterfaceclient');
        return parent::__construct($module, $object_name);
    }
    
    public function getName(){
        return $this->getData("email");
    }

    public function renderHeaderStatusExtra() {

        $extra = '';
        if ($this->getData('role') == self::USER_CLIENT_ROLE_ADMIN) {
            $extra .= '&nbsp;&nbsp;<span class="important">' . BimpRender::renderIcon('fas_cog', 'iconLeft') . 'Administrateur</span>';
        } else {
            $extra .= '&nbsp;&nbsp;<span class="warning">' . BimpRender::renderIcon('fas_user', 'iconLeft') . 'Utilisateur</span>';
        }

        if ($this->getData('renew_required')) {
            $extra .= '&nbsp;&nbsp;<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Doit changer sont mot de passe</span>';
        }
        return $extra;
    }

    public function renderAssociateContrat() {
        global $couverture;
        $html = '';

        $socid = $this->getData('attachet_societe');

        if ($this->isLoaded()) {
            $contrat = BimpObject::getInstance('bimpinterfaceclient', 'BIC_UserClientContrats');
            $bc_list = new BC_ListTable($contrat, 'default', 1, null, 'Contrat associés à ' . $this->displayEmail(), 'fas_file-invoice-dollar');
            $bc_list->addFieldFilterValue('id_user', $this->getData('id'));
            $html = $bc_list->renderHtml();
        }

        return $html;
    }

    public function getCouverture() {
        global $couverture;
        return Array(1 => 'kjhgf');
    }

    # Bouttons supplémentaires

    public function getListExtraButtons() {
        $buttons = array();

        $buttons[] = array(
            'label' => 'Page utilisateur',
            'icon' => 'fas_file',
            'onclick' => $this->getJsActionOnclick('redirect')
        );

        return $buttons;
    }

    public function getActionsButtons() {
        $buttons = array();

        $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';

        $buttons[] = array(
            'label' => 'Réinitialiser le mot de passe',
            'icon' => 'fas_lock',
            'onclick' => $this->getJsActionOnclick('generatePassword', array(), array(
                'success_callback' => $callback
            ))
        );

        if ($this->getData('role') == self::USER_CLIENT_ROLE_USER) {
            $buttons[] = array(
                'label' => 'Mettre administrateur',
                'icon' => 'fas_cog',
                'onclick' => $this->getJsActionOnclick('switchAdmin', array(), array(
                    'success_callback' => $callback
                ))
            );
        } elseif ($this->getData('role') == self::USER_CLIENT_ROLE_ADMIN) {
            $buttons[] = array(
                'label' => 'Mettre utilisateur',
                'icon' => 'fas_user',
                'onclick' => $this->getJsActionOnclick('switchUser', array(), array(
                    'success_callback' => $callback
                ))
            );
        }

        return $buttons;
    }

    # Actions

    public function actionRedirect() {
        return '<script>window.location.href = "' . DOL_URL_ROOT . '/bimpinterfaceclient/?page=users&id=' . $this->getData('id') . '"</script>';
    }

    public function actionSwitchUser() {
        $this->updateField('role', self::USER_CLIENT_ROLE_USER);
    }

    public function actionSwitchAdmin() {
        $this->updateField('role', self::USER_CLIENT_ROLE_ADMIN);
    }

    public function i_am_admin() {
        if ($this->getData('role') == self::USER_CLIENT_ROLE_ADMIN) {
            return true;
        } else {
            return false;
        }
    }

    public function actionGeneratePassword() {
        return 'ok';
    }

    public function displayEmail() {
        return $this->getData('email');
    }

    public function canClientView() {
        global $userClient;
        if (!defined('CONTEXTE_CLIENT')) {
            return false;
        }
        if (!$this->isLoaded()) {
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
        if($userClient->getData('status') == self::USER_CLIENT_ROLE_ADMIN)
            return true;
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
            echo BimpRender::renderAlerts("Utilisateur Non trouvé dans nos bases", 'danger', false);
        }
    }

    public function switch_lang($new_lang) {
        global $db;
        $bimp = new BimpDb($db);
        $this->updateField("lang", $new_lang);
//        $bimp->update('bic_user', Array('lang' => $new_lang), 'id = ' . $this->id);
        echo '<script>window.location.href = "' . DOL_URL_ROOT . '/bimpinterfaceclient/"</script>';
    }

    public function lang($field) {
        global $langs;
        return $langs->trans($field);
    }
    
    public function init() {
        global $user;
        if(isset($_SESSION['userClient'])){
            $this->GOT($_SESSION['userClient']);
            $connected_client = $this->id;
            $client = new Societe($this->db->db);
            $client->fetch($connected_client);

            $user = new User($this->db->db);
            $user->fetch(null, $this->loginUser);
            if ($user->id < 1)
                die('Attention ' . $this->loginUser . ' user existe pas');

//            if (count($couverture) > 0) {
//                //$this->check_all_attached_contrat($couverture);
//            }
        }
        $this->init = true;
    }
    
    public function getContratVisible($ouvert = false){//todo renvoie les contrat (bimp object visible par le user   viré le global couverture
        $retour = array();
        $socContrats = $this->getAllContrats($ouvert);
        if($this->i_am_admin()){
            $retour = $socContrats;
        }
        else{
            foreach($socContrats as $contrat){
                if(1/*on test la visiblité du contrat*/)
                    $retour[] = $contrat;
            }
        }
        return $retour;
    }
    
    public function getAllContrats($ouvert = false){
        //renvoie tous les contrat de nottre soc avec suivant $ouvert que les actifs ou tous
        $contrat = $this->getInstance('bimpcontract', 'BContract_contrat');
        $list = $contrat->getList(Array('fk_soc' => $this->getData('attached_societe')));
        if($ouvert) {
            $return = Array();
            foreach($list as $on_contrat){
                $instance = $this->getInstance('bimpcontract', 'BContract_contrat', $on_contrat['rowid']);
                if($instance->isValide()) {
                    $return[$on_contrat['rowid']] = $on_contrat['ref'];
                }
                $instance = null;
            }
            return $return;
        } else {
            foreach ($list as $on_contrat) {
                $return[$on_contrat['rowid']] = $on_contrat['ref'];
            }
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

    public function runContxte() {
        if (BimpTools::isSubmit('ajax')) {
            if($this->isLoged()){
                require_once DOL_DOCUMENT_ROOT . '/bimpcore/index.php';
            } else {
                die(json_encode(Array("request_id" => $_REQUEST['request_id'], 'nologged' => 1)));
            }
            
        } else {
            top_htmlhead("");
        }
    }
    
    public function my_soc_is_cover() {//todo a viré
        global $db;
        $bimp = new BimpDb($db);
        $in_covers = Array();
        $liste_contrat = $bimp->getRows('contrat', 'fk_soc = ' . $this->getData('attached_societe'));
        foreach ($liste_contrat as $contrat) {
            $current = new Contrat($db);
            $current->fetch($contrat->rowid);
            $extra = (object) $current->array_options;

            if ($extra->options_date_start) { // Nouveau contrat
                $debut = new DateTime();
                $fin = new DateTime();
                $debut->setTimestamp($extra->options_date_start);
                $fin->setTimestamp($extra->options_date_start);
                $fin = $fin->add(new DateInterval("P" . $extra->options_duree_mois . "M"));
                $fin = $fin->sub(new DateInterval("P1D"));

                $fin = strtotime($fin->format('Y-m-d'));
                $debut = strtotime($debut->format('Y-m-d'));
                $aujourdhui = strtotime(date('Y-m-d'));

                if ($fin - $aujourdhui > 0) {
                    $in_covers[$current->id] = $current->ref;
                }
            } else {
                foreach ($current->lines as $line) {
                    if ($line->statut == 4) {
                        $in_covers[$current->id] = $current->ref;
                    }
                }
            }
        }
        return $in_covers;
    }
    
    public function deconnexion() {
        $_SESSION['userClient'] = null;
        $userClient = null;
    }

    public function random_password($c, $l) {
        for ($i = 0, $z = strlen($c) - 1, $s = $c{rand(0, $z)}, $i = 1; $i != $l; $x = rand(0, $z), $s .= $c{$x}, $s = ($s{$i} == $s{$i - 1} ? substr($s, 0, -1) : $s), $i = strlen($s))
            ;
        return $s;
    }

}
