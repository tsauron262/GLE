<?php

include_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");

class object {
    
}

class synopsisHook {

    static $timeDeb = 0;
    private static $reload = false;

    function synopsisHook() {
        global $conf, $db, $tabProductType, $tabTypeLigne, $langs, $user, $tabContactPlus;


        if (is_object($db) && isset($conf->global->MAIN_MODULE_SYNOPSISTOOLS)) {
            include_once(DOL_DOCUMENT_ROOT . "/Synopsis_Tools/class/fileInfo.class.php");
            $fileInfo = new fileInfo($db);
            $fileInfo->showNewFile();
        }



        date_default_timezone_set('Europe/Paris');

        $builddoc = (isset($_REQUEST['action']) && ($_REQUEST['action'] != 'generatePdf' || $_REQUEST['action'] != 'builddoc'));
        $viewDoc = (stripos($_SERVER['REQUEST_URI'], 'document'));
        $modDev = defined('MOD_DEV_SYN') ? MOD_DEV_SYN : 0;

        if (($modDev == 2 && !$builddoc && !$viewDoc) || ($modDev == 1))
            error_reporting(E_ALL);
        else
            error_reporting(E_ALL ^ (E_NOTICE));

        ini_set('upload_max_filesize', 10000);
        ini_set('post_max_size', 10000);

        ini_set('display_errors', ($modDev > 0));
        ini_set('log_errors', '1');
        ini_set('error_log',  str_replace("DOL_DATA_ROOT", DOL_DATA_ROOT, SYSLOG_FILE));


        setlocale(LC_TIME, 'fr_FR.utf8', 'fra');


        include_once(DOL_DOCUMENT_ROOT . "/Synopsis_Tools/SynDiversFunction.php");

        $conf->global->MAIN_MAX_DECIMALS_TOT = 5;
        $conf->global->MAIN_MAX_DECIMALS_UNIT = 5;
        $conf->global->MAIN_MAX_DECIMALS_SHOWN = 2;

        $conf->global->MAIN_APPLICATION_TITLE = "GLE";
        $conf->global->MAIN_MENU_USE_JQUERY_ACCORDION = 0;
        $conf->global->MAIN_MODULE_MULTICOMPANY = "1";
        $conf->global->MAIN_MODULE_ORANGEHRM = "1";

        $conf->global->MAIN_MODULES_FOR_EXTERNAL .=',synopsisficheinter,synopsisdemandeinterv';

        $conf->global->PRODUIT_CONFIRM_DELETE_LINE = "1";

        define('PREF_BDD_ORIG', 'llx_');


        $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER = false;

//$conf->global->PROJET_ADDON = "mod_projet_tourmaline";


        $conf->global->devMailTo = 'tommy@drsi.fr';


//Key chrono
        define('CHRONO_KEY_SITE_DUREE_DEP', 1029);


        $tabProductType = array("Product", "Service", "Produit de contrat", "Déplacement", "Déplacement contrat");
        $tabTypeLigneSimple = array("Titre", "Sous-Titre", "Sous-Titre avec remise à 0", "Note", "Saut de page", "Sous-total", "Description");

        $tabContactPlus = array(1001 => array('id' => 1001, 'nom' => 'Commerciaux Société'), 1002 => array('id' => 1002, 'nom' => 'Techniciens Société'), 1003 => array('id' => 1003, 'nom' => 'Auteur'));
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

        $conf->modules_parts['tpl'][] = "/Synopsis_Tools/tpl/";

        $conf->global->MAIN_HTML_HEADER = (isset($conf->global->MAIN_HTML_HEADER) ? $conf->global->MAIN_HTML_HEADER : "") . $this->getHeader();
    }

    public static function reloadPage() {
        ob_start();
        self::$reload = true;
    }

    function initRightsSyn() {
        global $conf, $user, $db;


        if (isset($conf->global->MAIN_MODULE_SYNOPSISPROCESS)) {
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
        global $conf, $langs;
        $return = '';
        $tabElem = getTypeAndId();
        $element_type = $tabElem[0];
        $element_id = $tabElem[1];

        if (self::$reload)
            header("Location: " . $_SERVER['PHP_SELF'] . "?" . (isset($_REQUEST['id']) ? "id=" . $_REQUEST['id'] : ""));

        if (isset($conf->global->MAIN_MODULE_SYNOPSISHISTO)) {
            histoNavigation::saveHisto($element_type, $element_id);
            $return .= histoNavigation::getBlocHisto();
        }
        if ($element_id > 0 && ($element_type == "contrat" || $element_type == "commande" || $element_type == "DI" || $element_type == "FI" || $element_type == "expedition")) {
            $return .= '<div class="blockvmenupair rouge">';
            $return .= '<div class="menu_titre">';
            $return .= '<a href="#" class="vmenu">Consigne Commande</a>';
            $return .= "</div>";
            $return .= '<div class="editable consigne">';
            global $db;
            $consigne = new consigneCommande($db);
            $consigne->fetch($element_type, $element_id);
            $return .= $consigne->note;
            $return .= "</div>";
            $return .= "</div>";
        }

        if (isset($conf->global->MAIN_MODULE_SYNOPSISCONTRAT)) {
            $return .= '<div class="blockvmenupair">';
            $return .= '<div class="menu_titre"><a class="vsmenu" href="' . DOL_URL_ROOT . '/contrat/liste.php?leftmenu=contracts">
                    <img src="' . DOL_URL_ROOT . '/theme/eldy/img/object_contract.png" border="0" alt="" title=""> Contrats</a><br></div>';
            $return .= '<form method="post" action="' . DOL_URL_ROOT . '/contrat/liste.php">';
            $return .= '<input type="text" class="flat" name="sall" size="10">';
            $return .= '<input type="submit" value="' . $langs->trans("Go") . '" class="button">';
            $return .= '</div></form>';
        }


        return $return;
    }

    static function getHeader() {
        global $db;
        self::$timeDeb = microtime(true);

        //css
        $return = '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/Synopsis_Tools/css/global.css" />' . "\n";
        $cssSoc = "/Synopsis_Tools/css/" . MAIN_INFO_SOCIETE_NOM . ".css";
        if (is_file(DOL_DOCUMENT_ROOT . $cssSoc))
            $return .= '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . $cssSoc . '" />' . "\n";
        if (isset($_REQUEST['optioncss']) && $_REQUEST['optioncss'] == "print")
            $return .= '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/Synopsis_Tools/css/print.css" />' . "\n";

        $nameFile = DOL_DATA_ROOT . "/special.css";
        if (is_file($nameFile)) {
            $css = file_get_contents($nameFile);
            $return .= "<style>" . $css . "</style>";
        }


        ///js
        $return .= "<script type=\"text/javascript\">"
                . 'var DOL_URL_ROOT = "' . DOL_URL_ROOT . '";'
                . 'var idPagePrinc = "' . (isset($_SESSION['pagePrinc'])? $_SESSION['pagePrinc'] : "") . '";'
                . "</script>\n";
        $return .= '<script type="text/javascript" src="' . DOL_URL_ROOT . '/Synopsis_Tools/js/global.js"></script>';

        $jsSoc = "/Synopsis_Tools/js/" . MAIN_INFO_SOCIETE_NOM . ".js";
        if (is_file(DOL_DOCUMENT_ROOT . $jsSoc))
            $return .= '<script type="text/javascript" src="' . DOL_URL_ROOT . $jsSoc . '"></script>';

        return $return;
    }

    static function footer() {
        global $conf, $db, $logLongTime;

        if (isset($conf->global->MAIN_MODULE_SYNOPSISDASHBOARD)) {
            if (stripos($_SERVER['REQUEST_URI'], DOL_URL_ROOT . "/index.php?mainmenu=home") !== false) {
                dashboard::getDashboard();
            }
        }

        echo "</div>";

        echo "<div class='notificationText'></div><div class='notificationObj'></div>";

        $time = (microtime(true) - self::$timeDeb);
        if ($time > 2 && (!isset($logLongTime) || $logLongTime))
            dol_syslog("Pages lente " . $time . " s", 4);
        echo "<span class='timePage'>" . $time . " s</span>";
        if (isset($_REQUEST['optioncss']) && $_REQUEST['optioncss'] == "print") {
            echo "<br/>";
            echo "<br/>";
            echo "<br/>";
            echo "<br/>";
            echo "<br/>";
            echo "<br/>";
        }
    }

    public static function getObj($type) {
        $tabResult = self::getObjAndMenu($type);
        return $tabResult[0];
    }

    public static function getObjAndMenu($type) {
        global $db, $conf;
        $tabMenu = array(false, false);
        switch ($type) {
            case 'chrono': {
                    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Chrono/Chrono.class.php");
                    $obj = new Chrono($db);
                    $tabMenu[0] = "Process";
                }
                break;
            case 'propal': {
                    require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
                    $obj = new Propal($db);
                    $tabMenu[0] = "commercial";
                    $tabMenu[1] = "propals";
                }
                break;
            case 'commande': {
                    if (isset($conf->global->MAIN_MODULE_SYNOPSISPREPACOMMANDE))
                        $obj = new Synopsis_Commande($db);
                    else {
                        require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
                        $obj = new Commande($db);
                    }
                    $tabMenu[0] = "commercial";
                    $tabMenu[1] = "orders";
                }
                break;
            case 'facture': {
                    require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
                    $obj = new Facture($db);
                    $tabMenu[0] = "accountancy";
                }
                break;
            case 'prepaCom': {
//                    require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
//                    $obj = new Commande($db);
                }
                break;
            case 'FI': {
                    require_once(DOL_DOCUMENT_ROOT . "/fichinter/class/fichinter.class.php");
                    $obj = new Fichinter($db);
                    $tabMenu[0] = "synopsisficheinter";
                }
                break;
            case 'DI': {
                    require_once(DOL_DOCUMENT_ROOT . "/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php");
                    $obj = new Synopsisdemandeinterv($db);
                    $tabMenu[0] = "synopsisficheinter";
                }
                break;
            case 'contrat': {
                    require_once(DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");
                    $obj = new contrat($db);
                    $tabMenu[0] = "commercial";
                    $tabMenu[1] = "contracts";
                }
                break;
            case 'expedition': {
                    require_once(DOL_DOCUMENT_ROOT . "/expedition/class/expedition.class.php");
                    $obj = new Expedition($db);
                }
                break;
            case 'livraison': {
                    require_once(DOL_DOCUMENT_ROOT . "/livraison/class/livraison.class.php");
                    $obj = new Livraison($db);
                }
                break;
            case 'affaire': {
//                    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Affaire/Affaire.class.php");
//                    $obj = new Affaire($db);
                }
                break;
            case 'banque': {
                    require_once(DOL_DOCUMENT_ROOT . "/compta/bank/class/account.class.php");
                    $obj = new Account($db);
                }
                break;
            case 'contact': {
                    require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
                    $obj = new Contact($db);
                    $tabMenu[0] = "companies";
                }
                break;
            case 'societe': {
                    require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php");
                    $obj = new Societe($db);
                    $tabMenu[0] = "companies";
                }
                break;
            case 'campagne': {
//                    require_once(DOL_DOCUMENT_ROOT . "/BabelProspect/Campagne.class.php");
//                    $obj = new Campagne($db);
                }
                break;
            case 'projet': {
                    require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
                    $obj = new Project($db);
                    $tabMenu[0] = "synopsisprojet";
                }
                break;
            case 'tache': {
                    require_once(DOL_DOCUMENT_ROOT . "/projet/class/task.class.php");
                    $obj = new Task($db);
                    $tabMenu[0] = "synopsisprojet";
                }
                break;
            case 'groupCom': {
//                    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Tools/commandeGroup/commandeGroup.class.php");
//                    $obj = new commandeGroup($db);
                }
                break;
            case 'process': {
                    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/process.class.php");
                    $obj = new processDet($db);
                    $tabMenu[0] = "Process";
                }
                break;
            case 'product': {
                    require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
                    $obj = new product($db);
                    $tabMenu[0] = "products";
                }
                break;
            case 'user': {
                    require_once(DOL_DOCUMENT_ROOT . "/user/class/user.class.php");
                    $obj = new user($db);
                    $tabMenu[0] = "home";
                    $tabMenu[1] = "users";
                }
                break;
            case 'ndfp': {
                    require_once(DOL_DOCUMENT_ROOT . "/ndfp/class/ndfp.class.php");
                    $obj = new ndfp($db);
                    $tabMenu[0] = "accountancy";
                }
                break;
        }
        return array($obj, $tabMenu);
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
        if (isset($this->id)) {
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
            require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Tools/commandeGroup/commandeGroup.class.php");
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

    function getNomUrl($withpicto = 0, $option = 0, $max = 0, $short = 0) {
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
            $result.=($linkstart . img_object($label, $picto) . $linkend);
        if ($withpicto && $withpicto != 2)
            $result.=' ';
//        $connect = pictoConnect("commande",$this->id,$this->ref);
        $result.=$linkstart . $this->ref . $linkend . $connect;
        return $result;
    }

}

class Synopsis_OrderLine extends OrderLine {

    function fetch($id) {
        $return = parent::fetch($id);
        if ($id > 0) {
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
        }
        return $return;
    }

}

class histoNavigation {

    static function getBlocHisto() {
        global $db, $user, $conf, $langs;
        $langs->load("synopsisGene");
//        if ($conf->global->MAIN_MODULE_SYNOPSISHISTO && $user->rights->MiniHisto->all->Afficher) {
        $return = '<div class="blockvmenupair">';
        $return .= '<div class="menu_titre">';
        $return .= '<a href="#" class="vmenu">' . $langs->trans("HISTONAV") . '</a>';
        $return .= "</div>";
        $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Histo_User
                     WHERE user_refid = " . $user->id .
                " AND ref != '' AND element_type != '' ORDER BY tms DESC" .
                (isset($conf->global->SYNOPSIS_HISTO_LENGTH) && $conf->global->SYNOPSIS_HISTO_LENGTH > 0 ? " LIMIT 0," . $conf->global->SYNOPSIS_HISTO_LENGTH : " LIMIT 0,5");

        $sql = $db->query($requete);
        while ($res = $db->fetch_object($sql)) {
//print '<a href="#" class="vsmenu">'..'</a>';
            $ret = self::histoUser($res);
            if ($ret)
                $return .= "<div class='menu_contenu'>  " . $ret . "</div>";
        }
        $return .= "</div>";
        return $return;
    }

    public static function histoUser($res) {
        $tabResult = synopsisHook::getObjAndMenu($res->element_type);
        $obj = $tabResult[0];
        $tabMenu = $tabResult[1];
        if ($obj) {
            $result = $obj->fetch($res->element_id);
            if ($result > 0 && $obj->ref . "x" != "x") {
                $replace = ($tabMenu[0] ? '&mainmenu=' . $tabMenu[0] : '') . ($tabMenu[1] ? '&leftmenu=' . $tabMenu[1] : '') . '">';
                if ($res->element_type == "propal")
                    $nomUrl = str_replace('">', $replace, $obj->getNomUrl(1));
                else
                    $nomUrl = str_replace('">', $replace, $obj->getNomUrl(1, '', 20));
                return ("&nbsp;&nbsp;<span href='#' title='" . $res->element_type . " " . $res->ref . "' class='vsmenu' style='font-size: 8.5px;'>" . $nomUrl . "</span>");
            } else {
                return ("&nbsp;&nbsp;<span href='#' title='" . $res->element_type . " " . $res->ref . " (supprimer)' class='vsmenu ui-widget-error' style='font-size: 8.5px;'><del>" . dol_trunc($res->ref, 25) . "</del></span>");
            }
        } else {
            dol_syslog("objet Incorect " . $res->element_type, LOG_WARNING);
//            die("objet Incorect");
            return (false);
        }
    }

    static function saveHisto($element_type, $element_id) {
//        //saveHistoUser($fichinter->id, "FI", $fichinter->ref);


        if (isset($element_id) && isset($element_type) && $element_type != '' && $element_id > 0) {
            $obj = synopsisHook::getObj($element_type);
            if ($obj) {
                $obj->fetch($element_id);
                $ref = $obj->ref;
                global $user, $db;
                $requete = "SELECT *
                  FROM " . MAIN_DB_PREFIX . "Synopsis_Histo_User
                 WHERE user_refid = " . $user->id . "
                   AND element_type = '" . $element_type . "'
                   AND element_id = " . $element_id;
                $sql = $db->query($requete);
                if ($db->num_rows($sql) > 0) {
                    $res = $db->fetch_object($sql);
                    $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Histo_User
                       SET tms = now(),
                           ref = '" . addslashes($ref) . "'
                     WHERE id = " . $res->id;
                } else {
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Histo_User
                                (`user_refid`,`element_id`,`ref`,`element_type`)
                         VALUES (" . $user->id . "," . $element_id . ",'" . addslashes($ref) . "','" . $element_type . "')";
//        print $requete;
                }
//            die($requete);
                $sql = $db->query($requete);
                return($sql);
            }
        }
    }

}

class dashboard {

    static function getDashboard() {
//        echo <<<EOF
//        <script type="text/javascript" src="/Synopsis_Tools/dashboard2/js/lib/jquery.dashboard.min.js"></script>
//    <script type="text/javascript" src="/Synopsis_Tools/dashboard2/js/lib/themeroller.js"></script>
//
//    <script type="text/javascript">
//      // This is the code for definining the dashboard
//      $(document).ready(function() {
//
//        // load the templates
//        $('body').append('<div id="templates"></div>');
//        $("#templates").hide();
//        $("#templates").load("/Synopsis_Tools/dashboard2/demo/templates.html", initDashboard);
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
//              url: "/Synopsis_Tools/dashboard2/ajaxData.php?op=get_widgets_by_column"
//            },
//            // json feed; the widgets whcih you can add to your dashboard
//            addWidgetSettings: {
//              widgetDirectoryUrl:"/Synopsis_Tools/dashboard2/ajax/listWidget-xml_response"
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
//    <link rel="stylesheet" type="text/css" href="/Synopsis_Tools/dashboard2/themes/default/dashboardui.css" />
//    <link rel="stylesheet" type="text/css" href="/Synopsis_Tools/dashboard2/themes/default/jquery-ui-1.8.2.custom.css" />
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

        $jQueryDashBoardPath = DOL_URL_ROOT . '/Synopsis_Tools/dashboard/';
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
        echo $js;
        print '<div class="titre">Mon tableau de bord - Accueil</div>';
        print "<br/>";
        print "<br/>";
        print "<div style='padding: 5px 10px; width: 270px;' class='ui-button ui-state-default ui-widget-header ui-corner-all'><em><span style='float: left; margin: -1px 3px 0px 0px' class='ui-icon ui-icon-info'></span><a href='#' onClick='addWidget()'>Ajouter des widgets &agrave; votre tableau de bord.</a></em></div>";
        print "<br/>";
        print '<div id="dashboard">';
        print '  You need javascript to use the dashboard.';
        print '</div>';
    }

}

?>
