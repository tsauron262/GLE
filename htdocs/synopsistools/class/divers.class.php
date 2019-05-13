<?php

include_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");

//die(md5("admin:BaikalDAV:"."admin@synopsis"));
//class object {
//    
//}

class synopsisHook {//FA1506-0369

    static $timeDeb = 0;
    static $timeDebRel = 0;
    private static $MAX_TIME_LOG = 5;
    private static $MAX_REQ_LOG = 1000;
    private static $reload = false;

    function synopsisHook() {
        global $conf, $db, $dbBIMPERP, $tabProductType, $tabTypeLigne, $langs, $user, $tabContactPlus, $tabSelectNatureIntrv, $tabCentre;

        require_once(DOL_DOCUMENT_ROOT . "/synopsisapple/centre.inc.php");

        //Pour les logiciel externe.
        $dbBIMPERP = $db;

        if (defined('MAX_TIME_LOG'))
            self::$MAX_TIME_LOG = MAX_TIME_LOG;
        
        
        



        if (is_object($db) && isset($conf->global->MAIN_MODULE_SYNOPSISTOOLS)) {
            include_once(DOL_DOCUMENT_ROOT . "/synopsistools/class/fileInfo.class.php");
            $fileInfo = new fileInfo($db);
            $fileInfo->showNewFile();
        }
        
        global $tabProdPrixModifToujours;
        $tabProdPrixModifToujours = array("REMISECRT");



        date_default_timezone_set('Europe/Paris');

        $builddoc = 0; //(isset($_REQUEST['action']) && ($_REQUEST['action'] != 'generatePdf' || $_REQUEST['action'] != 'builddoc'));
        $viewDoc = (stripos($_SERVER['REQUEST_URI'], 'document'));
        $modDev = defined('MOD_DEV_SYN') ? MOD_DEV_SYN : 0;

        if (($modDev == 1 && !$builddoc && !$viewDoc) || ($modDev == 1))
            error_reporting(E_ALL);
        elseif ($modDev == 2)
            error_reporting(E_ALL ^ (E_NOTICE));
        else
            error_reporting(E_ALL ^ (E_NOTICE | E_STRICT | E_WARNING | E_DEPRECATED));

        ini_set('upload_max_filesize', 10000);
        ini_set('post_max_size', 10000);

        ini_set('display_errors', ($modDev > 0));
        ini_set('log_errors', '1');
        ini_set('error_log', str_replace("DOL_DATA_ROOT", DOL_DATA_ROOT, $conf->global->SYSLOG_FILE));


        setlocale(LC_TIME, 'fr_FR.utf8', 'fra');

        if (defined("TAB_IP_INTERNE") && in_array($_SERVER['REMOTE_ADDR'], explode(",", str_replace(" ", "", TAB_IP_INTERNE))))
            $conf->global->MAIN_SESSION_TIMEOUT = $conf->global->MAIN_SESSION_TIMEOUT * 6;

        include_once(DOL_DOCUMENT_ROOT . "/synopsistools/SynDiversFunction.php");

        /* $conf->global->MAIN_MAX_DECIMALS_TOT = 5;
          $conf->global->MAIN_MAX_DECIMALS_UNIT = 5;
          $conf->global->MAIN_MAX_DECIMALS_SHOWN = 2; */

        $conf->global->MAIN_APPLICATION_TITLE = "BIMP-ERP";
        $conf->global->MAIN_MENU_USE_JQUERY_ACCORDION = 0;
        $conf->global->MAIN_MODULE_MULTICOMPANY = "1";
        $conf->global->MAIN_MODULE_ORANGEHRM = "1";

        $conf->global->MAIN_MODULES_FOR_EXTERNAL .= ',synopsisficheinter,synopsisdemandeinterv';

        $conf->global->PRODUIT_CONFIRM_DELETE_LINE = "1";

        define('PREF_BDD_ORIG', 'llx_');


        $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER = false;

//$conf->global->PROJET_ADDON = "mod_projet_tourmaline";


        $conf->global->devMailTo = 'tommy@drsi.fr';


//Key chrono
        define('CHRONO_KEY_SITE_DUREE_DEP', 1029);


        $tabProductType = array("Product", "Service", "Produit de contrat", "Déplacement", "Déplacement contrat", "Logiciel");
        $tabTypeLigneSimple = array("Titre", "Sous-Titre", "Sous-Titre avec remise à 0", "Note", "Saut de page", "Sous-total", "Description");


        $tabSelectNatureIntrv = array("Choix", "Installation", "Dépannage", "Télémaintenance", "Formation", "Audit", "Suivi");

        $tabContactPlus = array(1001 => array('id' => 1001, 'nom' => 'Commerciaux Société'), 1002 => array('id' => 1002, 'nom' => 'Techniciens Société'), 1003 => array('id' => 1003, 'nom' => 'Auteur'), 1004 => array('id' => 1004, 'nom' => 'Tech Chrono'));
        if (is_object($langs)) {
            foreach ($tabProductType as $idT => $val)
                $tabProductType[$idT] = $langs->trans($val);
            foreach ($tabTypeLigneSimple as $idT => $val)
                $tabTypeLigneSimple[$idT] = $langs->trans($val);
        }
//$tabTypeLigne = array_merge($tabProductType, $tabTypeLigne);
        $tabTypeLigne = $tabProductType;
        foreach ($tabTypeLigneSimple as $id => $val)
            $tabTypeLigne[$id + 100] = $val;

        $conf->modules_parts['tpl'][] = "/synopsistools/tpl/";

        if (!defined('NOLOGIN'))
            $conf->global->MAIN_HTML_HEADER = (isset($conf->global->MAIN_HTML_HEADER) ? $conf->global->MAIN_HTML_HEADER : "") . $this->getHeader();

        if (defined('PORT_INTERNE') && $_SERVER["SERVER_PORT"] != PORT_INTERNE)
            $conf->global->MAIN_SECURITY_ENABLECAPTCHA = 1;
    }
    

    public static function reloadPage() {
        ob_start();
        self::$reload = true;
    }

    function initRightsSyn() {
        global $conf, $user, $db;



        //bimp pas de logo pour sav
        if ($user->id && isset($conf->global->MAIN_MODULE_SYNOPSISCHRONO)) {


//            require_once(DOL_DOCUMENT_ROOT . "/user/class/usergroup.class.php");
//            $groupSav = new UserGroup($db);
//            $groupSav->fetch('', "XX SAV");
//            if (isset($groupSav->members[$user->id]))

            if (userInGroupe("XX Sav", $user->id))
                $conf->global->MAIN_SHOW_LOGO = false;
        }

        if (isset($conf->global->MAIN_MODULE_SYNOPSISPROCESS)) {
            require_once(DOL_DOCUMENT_ROOT . "/synopsisres/extractObjTypeId.php");
            $tab = getTypeAndId();
            if ($tab[0] == "projet")
                $tab[0] = "project";
            launchRunningProcess($db, $tab[0], $tab[1]);
        }

        if (isset($conf->global->MAIN_MODULE_SYNOPSISPROJET)) {
            @$conf->projet = $conf->synopsisprojet;
            @$user->rights->projet = $user->rights->synopsisprojet;
            @$user->rights->projet->all = $user->rights->synopsisprojet;
            @$conf->imputations->dir_output = $conf->synopsisprojet->dir_output . "/imputation";
        }

        if (isset($conf->global->MAIN_MODULE_SYNOPSISFICHEINTER)) {
//            @$conf->ficheinter = $conf->synopsisficheinter;
            @$user->rights->ficheinter = $user->rights->synopsisficheinter;
        }
    }

    static function getMenu() {
        if (self::$reload)
            header("Location: " . $_SERVER['PHP_SELF'] . "?" . (isset($_REQUEST['id']) ? "id=" . $_REQUEST['id'] : ""));
    }

    static function getHeader() {
        global $db, $langs, $isMobile, $conf, $user;
        self::$timeDeb = microtime(true);
        self::$timeDebRel = microtime(true);
        
        
        $admin = false;
        if(defined('IP_ADMIN')){
            if(is_array(IP_ADMIN)){
                foreach(IP_ADMIN as $ip)
                    if($ip == $_SERVER['REMOTE_ADDR'])
                        $admin =true;
            }
            elseif(IP_ADMIN == $_SERVER['REMOTE_ADDR'])
                $admin = true;
        }

        if (defined("CLOSE_DATE") && !stripos($_SERVER['REQUEST_URI'], 'close.php') && !$admin) {
            if(is_object($langs))
                $langs->load("main");
            require (DOL_DOCUMENT_ROOT . "/synopsistools/public/close.php");
            die;
        }
        
//        if (isset($conf->file->main_force_https) && $conf->file->main_force_https != "" && stripos($_SERVER["SCRIPT_URI"], str_replace("https://", "", $conf->file->main_force_https)) === false) {
        if (defined("REDIRECT_DOMAINE") && REDIRECT_DOMAINE != "" && stripos($_SERVER["HTTP_HOST"], str_replace(array("https://", "http://"), "", REDIRECT_DOMAINE)) === false) {

            header('HTTP/1.1 301 Moved Permanently');
            header('Location: '.REDIRECT_DOMAINE . "/" . $_SERVER["REQUEST_URI"]);
        }

        if (defined('URL_REDIRECT') && !$admin){
                header("Location:" . URL_REDIRECT);
        }

        $useragent = $_SERVER['HTTP_USER_AGENT'];
        $isMobile = (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) ? true : false;

        //css
        $return = '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/synopsistools/css/global.css?nocache=m" />' . "\n";
//        $return = '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/synopsistools/jquerymobile/jquery.mobile-1.4.5/jquery.mobile-1.4.5.min.css" />' . "\n";
//        $return .= '<link rel="stylesheet" href="' . DOL_URL_ROOT . '/synopsistools/jquery/mobile/jquery.mobile-1.4.3.min.css">';
        $cssSoc = "/synopsistools/css/" . $conf->global->MAIN_INFO_SOCIETE_NOM . ".css";
        if (is_file(DOL_DOCUMENT_ROOT . $cssSoc))
            $return .= '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . $cssSoc . '" />' . "\n";
        if (isset($_REQUEST['optioncss']) && $_REQUEST['optioncss'] == "print")
            $return .= '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/synopsistools/css/print.css" />' . "\n";

        $nameFile = DOL_DATA_ROOT . "/special.css";
        if (is_file($nameFile)) {
            $css = file_get_contents($nameFile);
            $return .= "<style>" . $css . "</style>";
        }


        if (isset($conf->global->MAIN_MODULE_SYNOPSISCHRONO)) {
            //Pour la liste rapide des centres
            $listCentre = "<select name='centreRapide'>";
            $result = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members` WHERE `list_refid` = 11 ");
            $listCentre .= "<option value=''>Ajouter</option>";
            while ($ligne = $db->fetch_object($result)) {
                $val = $ligne->valeur;
                $centre = $ligne->label;
                $listCentre .= "<option value='" . $val . "'>" . $centre . "</option>";
            }
            $listCentre .= "</select>";
        } else
            $listCentre = "";


        ///js
        $return .= "<script type=\"text/javascript\">"
                . 'var DOL_URL_ROOT = "' . DOL_URL_ROOT . '";';
        $return .= 'var idPagePrinc = "' . (isset($_SESSION['pagePrinc']) ? $_SESSION['pagePrinc'] : "") . '";'
                . 'var selectCentre = "' . $listCentre . '";'
//                . 'alert("Test en cours !!! Nombreuses errerurs de chargement possible.");'
                . "</script>\n";
        $return .= '<script type="text/javascript" src="' . DOL_URL_ROOT . '/synopsistools/js/global.js"></script>';
        $return .= '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/synopsistools/css/responsive.css">';
        $return .= '<script type="text/javascript" src="' . DOL_URL_ROOT . '/synopsistools/js/responsive.js"></script>';
//        $return .= '<script type="text/javascript" src="' . DOL_URL_ROOT . '/synopsistools/jquerymobile/jquery.mobile-1.4.5/jquery.mobile-1.4.5.min.js"></script>';

        $jsSoc = "/synopsistools/js/" . $conf->global->MAIN_INFO_SOCIETE_NOM . ".js";
        if (is_file(DOL_DOCUMENT_ROOT . $jsSoc))
            $return .= '<script type="text/javascript" src="' . DOL_URL_ROOT . $jsSoc . '"></script>';

//        $return .= '<script src="' . DOL_URL_ROOT . '/synopsistools/jquery/mobile/jquery.mobile-1.4.3.min.js"></script>';

        if (is_object($langs)) {
            $langsSoc = "/synopsistools/langs/fr_FR/" . $conf->global->MAIN_INFO_SOCIETE_NOM . ".lang";
            if (is_file(DOL_DOCUMENT_ROOT . $langsSoc))
                $langs->load($conf->global->MAIN_INFO_SOCIETE_NOM . "@synopsistools");
            $langs->load("synopsisGene@synopsistools");
        }

        return $return;
    }
    
    static function getTime($relatif = false){
        if($relatif){
            $return = (microtime(true) - self::$timeDebRel);
            self::$timeDebRel = microtime(true);
        }
        else
            $return = (microtime(true) - self::$timeDeb);
        return $return;
    }

    static function footer() {
        global $conf, $db, $logLongTime, $user;

        $return = "";
        
        $return .= '<script>var DEFAULT_ENTREPOT = "' . $user->array_options['options_defaultentrepot'] . '";</script>';

        if (isset($conf->global->MAIN_MODULE_SYNOPSISDASHBOARD)) {
            if (stripos($_SERVER['REQUEST_URI'], DOL_URL_ROOT . "/index.php?mainmenu=home") !== false) {
                //$return .= dashboard::getDashboard();
            }
        }

        $return .= "</div>";

        $return .= "<div class='notificationText'></div><div class='notificationObj'></div>";

        $nbReq = $db->countReq;

        $time = self::getTime();
        if ($time > self::$MAX_TIME_LOG && (!isset($logLongTime) || $logLongTime))
            dol_syslog("Pages lente " . $time . " s", 4, 0, "_time");
        if ($nbReq > self::$MAX_REQ_LOG && (!isset($logLongTime) || $logLongTime))
            dol_syslog("Pages trop de req " . $nbReq . " ", 4, 0, "_time");
        if ($nbReq > self::$MAX_REQ_LOG / 2 && $time > self::$MAX_TIME_LOG / 2 && (!isset($logLongTime) || $logLongTime))
            dol_syslog("Pages trop de req*temp " . $nbReq . " en " . $time . " s", 4, 0, "_time");
        $return .= "<span class='timePage'>" . number_format($time, 4) . " s | " . $nbReq . " requetes</span>";
        if (isset($_REQUEST['optioncss']) && $_REQUEST['optioncss'] == "print") {
            $return .= "<br/>";
            $return .= "<br/>";
            $return .= "<br/>";
            $return .= "<br/>";
            $return .= "<br/>";
            $return .= "<br/>";
        }
        return $return;
    }

    public static function getObj($type) {
        $tabResult = self::getObjAndMenu($type);
        return $tabResult[0];
    }

}

class consigneCommande {

    var $note = '';
    var $rowid = 0;
    var $fk_group = 0;
    var $fk_comm = 0;

    public function consigneCommande($db) {
        $this->db = $db;
    }

    public function fetch($element_type, $element_id) {
        global $conf;
        $db = $this->db;
        if ($element_id > 0) {
            $obj = synopsisHook::getObj($element_type);
            $obj->fetch($element_id);
            if ($element_type == "commande") {
                $id_comm = $element_id;
            } elseif ($element_type == "FI" || $element_type == "DI") {
                $id_comm = $obj->fk_commande;
            } elseif ($element_type == "expedition") {
                $id_comm = $obj->origin_id;
            } elseif ($element_type == "contrat") {
                $tabT = getElementElement("commande", "contrat", null, $obj->id);
                if (isset($tabT[0]['s']))
                    $id_comm = $tabT[0]['s'];
            }

            if (isset($id_comm) && $id_comm > 0) {
                $comm = synopsisHook::getObj("commande");
                $comm->fetch($id_comm);
                if (isset($conf->global->MAIN_MODULE_SYNOPSISPREPACOMMANDE) && $comm->isGroupMember())
                    $this->fk_group = $comm->OrderGroup->id;
                else
                    $this->fk_comm = $id_comm;
                $this->init();
            }
        }
    }

    public function init() {
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_commande_consigne WHERE ";
        if ($this->fk_group)
            $sql .= " fk_group = " . $this->fk_group;
        else
            $sql .= " fk_comm = " . $this->fk_comm;
        $result = $this->db->query($sql);
        if ($this->db->num_rows($result) > 0) {
            $ligne = $this->db->fetch_object($result);
            $this->note = $ligne->note;
            $this->rowid = $ligne->rowid;
        }
        if ($this->note == "") {
            $this->note = "Cliquez pour éditer";
        }
    }

    public function setNote($note) {
        $this->note = $note;
        if ($this->rowid == 0) {
            if ($this->fk_group) {
                $champ = "fk_group";
                $val = $this->fk_group;
            } else {
                $champ = "fk_comm";
                $val = $this->fk_comm;
            }
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_commande_consigne (" . $champ . ") VALUES (" . $val . ")";
            $result = $this->db->query($sql);
            $this->rowid = $this->db->last_insert_id($result);
        }
        $sql = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_commande_consigne SET note ='" . $this->note . "' WHERE rowid = " . $this->rowid;
        $result = $this->db->query($sql);
    }

}

class Synopsis_Commande extends Commande {

    function fetch($id, $ref = '', $ref_ext = '', $ref_int = '') {
        $return = parent::fetch($id, $ref, $ref_ext, $ref_int);
        if (isset($this->id) && $this->id > 0) {
            $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_commande WHERE rowid = " . $this->id);
            if ($this->db->num_rows($sql) < 1) {
                $this->db->query("INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_commande (`rowid`) VALUES (" . $this->id . ")");
                $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_commande WHERE rowid = " . $this->id);
            }
            $result = $this->db->fetch_object($sql);
            $this->logistique_ok = $result->logistique_ok;
            $this->logistique_statut = $result->logistique_statut;
            $this->finance_ok = $result->finance_ok;
            $this->finance_statut = $result->finance_statut;
            $this->logistique_date_dispo = $result->logistique_date_dispo;
            return $return;
        }
    }

    function fetch_lines($only_product = 0) {
//        parent::fetch_lines($only_product);
////        $this->lines=array();
//
//        foreach ($this->lines as $id => $line) {
//            $newLine = new Synopsis_OrderLine($this->db);
//            $newLine->fetch($this->lines[$id]->id);
//            $this->lines[$id] = $newLine;
//        }
//        return;
        return $this->fetch_commande_lignes(array($this->id), $only_product);
    }

//La commande est elle membre d'un groupe
    public function listIdGroupMember() {
//        return false;
        $requete = "SELECT command_refid "
                . "FROM `" . MAIN_DB_PREFIX . "Synopsis_commande_grpdet` "
                . "WHERE `commande_group_refid` = (SELECT `commande_group_refid` "
                . "                                 FROM `" . MAIN_DB_PREFIX . "Synopsis_commande_grpdet` "
                . "                                 WHERE `command_refid` = " . $this->id . ")";
        $sql = $this->db->query($requete);
        $return = array();
        if ($this->db->num_rows($sql) > 0) {
            while ($result = $this->db->fetch_object($sql)) {
                if ($result->command_refid > 0)
                    $return[$result->command_refid] = $result->command_refid;
            }
            return $return;
        }
        return array($this->id);
    }

    public function isGroupMember() {
//        return false;
        $requete = "SELECT " . MAIN_DB_PREFIX . "Synopsis_commande_grp.id as gid
                      FROM " . MAIN_DB_PREFIX . "Synopsis_commande_grpdet,
                           " . MAIN_DB_PREFIX . "Synopsis_commande_grp
                     WHERE " . MAIN_DB_PREFIX . "Synopsis_commande_grp.id=" . MAIN_DB_PREFIX . "Synopsis_commande_grpdet.commande_group_refid
                       AND command_refid = " . $this->id;
        $sql = $this->db->query($requete);
        if ($this->db->num_rows($sql) > 0) {
            $res = $this->db->fetch_object($sql);
            require_once(DOL_DOCUMENT_ROOT . "/synopsistools/commandeGroup/commandeGroup.class.php");
            $comGrp = new CommandeGroup($this->db);
            $comGrp->fetch($res->gid);
            $this->OrderGroup = $comGrp;
            return ($comGrp);
        }
        return false;
    }

    public function listGroupMember($excludeMyself = true) {
        $ret = $this->isGroupMember();
        if ($ret) {
            $this->OrderGroup = $ret;
            $arr = array();
            foreach ($ret->commandes as $key => $commande) {
                if ($excludeMyself && $commande->id == $this->id) {
                    continue;
                }
                $arr[$key] = $commande;
            }
            return($arr);
        }
        return array();
    }

    function fetch_commande_lignes($arrId, $only_product = 0, $only_service = 0, $only_contrat = 0, $only_dep = 0, $srv_dep = 0) {

        $sql1 = 'SELECT l.rowid FROM ' . MAIN_DB_PREFIX . 'commandedet as l';
        $sql2 = ' WHERE l.fk_commande IN (' . implode(",", $arrId) . ")";
        if ($only_product) {
            $sql1 .= ', ' . MAIN_DB_PREFIX . 'product as p';
            $sql2 .= ' AND p.fk_product_type = 0 AND p.rowid = l.fk_product';
        }
        $sql2 .= ' ORDER BY l.rang';

        dol_syslog("Commande::fetch_lines sql=" . $sql1 . $sql2, LOG_DEBUG);
        $result = $this->db->query($sql1 . $sql2);
        if ($result) {
            $num = $this->db->num_rows($result);

            $i = 0;
            while ($i < $num) {
                $objp = $this->db->fetch_object($result);

                $line = new Synopsis_OrderLine($this->db);

                $line->fetch($objp->rowid);

                $this->lines[$i] = $line;

                $i++;
            }
            $this->db->free($result);
//            parent::fetch_lines($only_product);
            return $this->lines;
        } else {
            $this->error = $this->db->error();
            dol_syslog('Commande::fetch_lines: Error ' . $this->error, LOG_ERR);
            return -3;
        }
    }

    function fetch_group_lines($only_product = 0, $only_service = 0, $only_contrat = 0, $only_dep = 0, $srv_dep = 0) {
        $grp = $this->listIdGroupMember();
        return $this->fetch_commande_lignes($grp, $only_product, $only_service, $only_contrat, $only_dep, $srv_dep);
//        $lines = array();
//        $comms = $this->listGroupMember(false);
//        $i = 0;
//        if (count($comms) > 0) {
//            foreach ($comms as $commande) {
//                $commande->fetch_lines();
//                foreach ($commande->lines as $ligne) {
//                    $lines[$i] = $ligne;
//                    $i++;
//                }
////            $this->lines = array_merge($this->lines, $commande->lines);
//            }
//            $this->lines = $lines;
//        } else
//            $this->fetch_lines($only_product);
//        return true;
////        return $this->fetch_lines($only_product);
    }

    function getNomUrl($withpicto = 0, $option = 0, $max = 0, $short = 0, $notooltip = 0, $save_lastsearch_value = -1) {
        global $conf, $langs;

        $result = '';

        if (!empty($conf->expedition->enabled) && ($option == 1 || $option == 2))
            $url = DOL_URL_ROOT . '/expedition/shipment.php?id=' . $this->id;
        else
            $url = DOL_URL_ROOT . '/Synopsis_PrepaCommande/prepacommande.php?id=' . $this->id;

        if ($short)
            return $url;

        $linkstart = '<a href="' . $url . '">';
        $linkend = '</a>';

        $picto = 'order';
        $label = $langs->trans("ShowOrder") . ': ' . $this->ref;

        if ($withpicto)
            $result .= ($linkstart . img_object($label, $picto) . $linkend);
        if ($withpicto && $withpicto != 2)
            $result .= ' ';
//        $connect = pictoConnect("commande",$this->id,$this->ref);
        $result .= $linkstart . $this->ref . $linkend . $connect;
        return $result;
    }

}

class Synopsis_OrderLine extends OrderLine {

    function fetch($id) {
        $return = parent::fetch($id);
        if ($id > 0 && $this->qty > 0) {
            $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_commandedet WHERE rowid = " . $id);
            if (!$this->db->num_rows($sql) > 0) {
                $this->db->query("INSERT INTO  " . MAIN_DB_PREFIX . "Synopsis_commandedet (rowid) VALUES (" . $this->rowid . ")");
                $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_commandedet WHERE rowid = " . $id);
                if (!$this->db->num_rows($sql) > 0)
                    die("Impossible de ajouter la ligne a Synopsis_commandedet");
            }
            $result = $this->db->fetch_object($sql);
            $this->logistique_ok = $result->logistique_ok;
            $this->finance_ok = $result->finance_ok;
            $this->coef = $result->coef;
            $this->logistique_date_dispo = $result->logistique_date_dispo;
            $this->liv_direct = $result->liv_direct;
        }
        return $return;
    }

}

class dashboard {

    static function getDashboard() {
//        echo <<<EOF
//        <script type="text/javascript" src="/synopsistools/dashboard2/js/lib/jquery.dashboard.min.js"></script>
//    <script type="text/javascript" src="/synopsistools/dashboard2/js/lib/themeroller.js"></script>
//
//    <script type="text/javascript">
//      // This is the code for definining the dashboard
//      $(document).ready(function() {
//
//        // load the templates
//        $('body').append('<div id="templates"></div>');
//        $("#templates").hide();
//        $("#templates").load("/synopsistools/dashboard2/demo/templates.html", initDashboard);
//
//        // call for the themeswitcher
//        $('#switcher').themeswitcher();
//
//        function initDashboard() {
//
//          // to make it possible to add widgets more than once, we create clientside unique id's
//          // this is for demo purposes: normally this would be an id generated serverside
//          var startId = 100;
//
//          var dashboard = $('#dashboard').dashboard({
//            // layout class is used to make it possible to switch layouts
//            layoutClass:'layout',
//            // feed for the widgets which are on the dashboard when opened
//            json_data : {
//              url: "/synopsistools/dashboard2/ajaxData.php?op=get_widgets_by_column"
//            },
//            // json feed; the widgets whcih you can add to your dashboard
//            addWidgetSettings: {
//              widgetDirectoryUrl:"/synopsistools/dashboard2/ajax/listWidget-xml_response"
//            },
//
//            // Definition of the layout
//            // When using the layoutClass, it is possible to change layout using only another class. In this case
//            // you don't need the html property in the layout
//
//            layouts :
//              [
//                { title: "Layout1",
//                  id: "layout1",
//                  image: "/synopsys_dashboard/dashboard2/demo/layouts/layout1.png",
//                  html: '<div class="layout layout-a"><div class="column first column-first"></div></div>',
//                  classname: 'layout-a'
//                },
//                { title: "Layout2",
//                  id: "layout2",
//                  image: "/synopsys_dashboard/dashboard2/demo/layouts/layout2.png",
//                  html: '<div class="layout layout-aa"><div class="column first column-first"></div><div class="column second column-second"></div></div>',
//                  classname: 'layout-aa'
//                },
//                { title: "Layout3",
//                  id: "layout3",
//                  image: "/synopsys_dashboard/dashboard2/demo/layouts/layout3.png",
//                  html: '<div class="layout layout-ba"><div class="column first column-first"></div><div class="column second column-second"></div></div>',
//                  classname: 'layout-ba'
//                },
//                { title: "Layout4",
//                  id: "layout4",
//                  image: "/synopsys_dashboard/dashboard2/demo/layouts/layout4.png",
//                  html: '<div class="layout layout-ab"><div class="column first column-first"></div><div class="column second column-second"></div></div>',
//                  classname: 'layout-ab'
//                },
//                { title: "Layout5",
//                  id: "layout5",
//                  image: "/synopsys_dashboard/dashboard2/demo/layouts/layout5.png",
//                  html: '<div class="layout layout-aaa"><div class="column first column-first"></div><div class="column second column-second"></div><div class="column third column-third"></div></div>',
//                  classname: 'layout-aaa'
//                }
//              ]
//
//          }); // end dashboard call
//
//          // binding for a widgets is added to the dashboard
//          dashboard.element.live('dashboardAddWidget',function(e, obj){
//            var widget = obj.widget;
//
//            dashboard.addWidget({
//              "id":startId++,
//              "title":widget.title,
//              "url":widget.url,
//              "metadata":widget.metadata
//              }, dashboard.element.find('.column:first'));
//          });
//
//          // the init builds the dashboard. This makes it possible to first unbind events before the dashboars is built.
//          dashboard.init();
//        }
//      });
//      
//
//    </script>
//
//    <link rel="stylesheet" type="text/css" href="/synopsistools/dashboard2/themes/default/dashboardui.css" />
//    <link rel="stylesheet" type="text/css" href="/synopsistools/dashboard2/themes/default/jquery-ui-1.8.2.custom.css" />
//
//  </head>
//
//  <body>
//
//<!--  <div class="header_tile_image">
//    <div class="headerbox">
//      <div id="switcher"></div>
//    </div>
//    <div class="headerlinks">
//      <a class="openaddwidgetdialog headerlink" href="#">Add Widget</a>&nbsp;<span class="headerlink">|</span>&nbsp;
//      <a class="editlayout headerlink" href="#">Edit layout</a>
//    </div>
//  </div>-->
//      
//      <div class="butAction ui-widget-header ui-corner-all ui-state-default" style="padding: 5px 10px; width: 290px;"><em><span class="ui-icon ui-icon-info" style="float: left; margin: -1px 3px 0px 0px"></span><a class="openaddwidgetdialog" href="#">Ajouter des widgets à votre tableau de bord.</a></em></div>
//
//
//  <div id="dashboard" class="dashboard">
//    <!-- this HTML covers all layouts. The 5 different layouts are handled by setting another layout classname -->
//    <div class="layout">
//      <div class="column first column-first"></div>
//      <div class="column second column-second"></div>
//      <div class="column third column-third"></div>
//    </div>
//  </div>
//EOF;

        $jQueryDashBoardPath = DOL_URL_ROOT . '/synopsistools/dashboard/';
        global $user;
        $js = '
    <script>var DOL_URL_ROOT="' . DOL_URL_ROOT . '";</script>
    <script>var DOL_DOCUMENT_ROOT="' . DOL_DOCUMENT_ROOT . '";</script>
    <script type="text/javascript" src="' . $jQueryDashBoardPath . 'jquery.dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="' . $jQueryDashBoardPath . 'dashboard.css" />

    <script type="text/javascript" src="' . $jQueryDashBoardPath . 'dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="' . $jQueryDashBoardPath . 'demo.css" />
    <script type="text/javascript">var userid=' . $user->id . ';</script>
    <script type="text/javascript">var dashtype="4";</script>

';
        $return = $js;
        $return .= '<div class="titre">Mon tableau de bord - Accueil</div>';
        $return .= "<br/>";
        $return .= "<br/>";
        $return .= "<div style='padding: 5px 10px; width: 270px;' class='ui-button ui-state-default ui-widget-header ui-corner-all'><em><span style='float: left; margin: -1px 3px 0px 0px' class='ui-icon ui-icon-info'></span><a href='#' onClick='addWidget()'>Ajouter des widgets &agrave; votre tableau de bord.</a></em></div>";
        $return .= "<br/>";
        $return .= '<div id="dashboard">';
        $return .= '  You need javascript to use the dashboard.';
        $return .= '</div>';
        return $return;
    }

}






?>
