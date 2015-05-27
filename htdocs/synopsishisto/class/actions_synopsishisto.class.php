<?php

class ActionsSynopsisHisto {

    var $menuOk = false;

    function doActions($parameters, &$object, &$action, $hookmanager) {
        
    }

    function printSearchForm($parameters, &$object, &$action, $hookmanager) {

        return 0;
    }

    function printMenuAfter($parameters, &$object, &$action, $hookmanager) {
        if (!$this->menuOk) {
            $this->afficherMenu(0);
            $this->menuOk = true;
        }
        return 0;
    }

    function printLeftBlock($parameters, &$object, &$action, $hookmanager) {
        if (!$this->menuOk) {
            $this->afficherMenu(1);
            $this->menuOk = true;
        }
        return 0;
    }

    function afficherMenu($context) {
        global $conf, $user, $db;
        $return = '';
        $tabElem = histoNavigation::getTypeAndId();
        $element_type = $tabElem[0];
        $element_id = $tabElem[1];


        if (isset($conf->global->MAIN_MODULE_SYNOPSISHISTO)) {
            histoNavigation::saveHisto($element_type, $element_id);
            $return .= histoNavigation::getBlocHisto($context);
        }

        $this->resprints = $return;
        return 0;
    }

}

class histoNavigation {

    static function getBlocHisto($context) {
        global $db, $user, $conf, $langs;
        $langs->load("histo@synopsishisto");
//        if ($conf->global->MAIN_MODULE_SYNOPSISHISTO && $user->rights->MiniHisto->all->Afficher) {
        $return = '<div class="blockvmenupair' . ($context == 1 ? ' vmenu' : '') . '">';
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
        $return .= "<div class=\"menu_end\"></div></div>";
        return $return;
    }

    public static function histoUser($res) {
        global $conf;
        $tabResult = histoNavigation::getObjAndMenu($res->element_type);
        $obj = $tabResult[0];
        $tabMenu = $tabResult[1];
        if ($obj) {
            $sysLogActive = $conf->syslog->enabled;
            $conf->syslog->enabled = 0;
            $result = $obj->fetch($res->element_id);
            $conf->syslog->enabled = $sysLogActive;
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

    public static function getObj($type) {
        $tabResult = self::getObjAndMenu($type);
        return $tabResult[0];
    }

    static function saveHisto($element_type, $element_id) {
//        //saveHistoUser($fichinter->id, "FI", $fichinter->ref);


        if (isset($element_id) && isset($element_type) && $element_type != '' && $element_id > 0) {
            $obj = self::getObj($element_type);
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

    public static function getObjAndMenu($type) {
        global $db, $conf;
        $tabMenu = array(false, false);
        $obj = false;
//        switch ($type) {
//            case 'chrono': {
//                    require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");
//                    $obj = new Chrono($db);
//                    $tabMenu[0] = "Process";
//                }
//                break;
//            case 'propal': {
//                    require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
//                    $obj = new Propal($db);
//                    $tabMenu[0] = "commercial";
//                    $tabMenu[1] = "propals";
//                }
//                break;
//            case 'commande': {
//                    if (isset($conf->global->MAIN_MODULE_SYNOPSISPREPACOMMANDE))
//                        $obj = new Synopsis_Commande($db);
//                    else {
//                        require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
//                        $obj = new Commande($db);
//                    }
//                    $tabMenu[0] = "commercial";
//                    $tabMenu[1] = "orders";
//                }
//                break;
//            case 'facture': {
//                    require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
//                    $obj = new Facture($db);
//                    $tabMenu[0] = "accountancy";
//                }
//                break;
//            case 'prepaCom': {
////                    require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
////                    $obj = new Commande($db);
//                }
//                break;
//            case 'FI': {
//                    require_once(DOL_DOCUMENT_ROOT . "/fichinter/class/fichinter.class.php");
//                    $obj = new Fichinter($db);
//                    $tabMenu[0] = "synopsisficheinter";
//                }
//                break;
//            case 'DI': {
//                    require_once(DOL_DOCUMENT_ROOT . "/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php");
//                    $obj = new Synopsisdemandeinterv($db);
//                    $tabMenu[0] = "synopsisficheinter";
//                }
//                break;
//            case 'contrat': {
//                    require_once(DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");
//                    $obj = new contrat($db);
//                    $tabMenu[0] = "commercial";
//                    $tabMenu[1] = "contracts";
//                }
//                break;
//            case 'expedition': {
//                    require_once(DOL_DOCUMENT_ROOT . "/expedition/class/expedition.class.php");
//                    $obj = new Expedition($db);
//                }
//                break;
//            case 'livraison': {
//                    require_once(DOL_DOCUMENT_ROOT . "/livraison/class/livraison.class.php");
//                    $obj = new Livraison($db);
//                }
//                break;
//            case 'affaire': {
////                    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Affaire/Affaire.class.php");
////                    $obj = new Affaire($db);
//                }
//                break;
//            case 'banque': {
//                    require_once(DOL_DOCUMENT_ROOT . "/compta/bank/class/account.class.php");
//                    $obj = new Account($db);
//                }
//                break;
//            case 'contact': {
//                    require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
//                    $obj = new Contact($db);
//                    $tabMenu[0] = "companies";
//                }
//                break;
//            case 'societe': {
//                    require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php");
//                    $obj = new Societe($db);
//                    $tabMenu[0] = "companies";
//                }
//                break;
//            case 'campagne': {
////                    require_once(DOL_DOCUMENT_ROOT . "/BabelProspect/Campagne.class.php");
////                    $obj = new Campagne($db);
//                }
//                break;
//            case 'projet': {
//                    require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
//                    $obj = new Project($db);
//                    $tabMenu[0] = "synopsisprojet";
//                }
//                break;
//            case 'tache': {
//                    require_once(DOL_DOCUMENT_ROOT . "/projet/class/task.class.php");
//                    $obj = new Task($db);
//                    $tabMenu[0] = "synopsisprojet";
//                }
//                break;
//            case 'groupCom': {
////                    require_once(DOL_DOCUMENT_ROOT . "/synopsistools/commandeGroup/commandeGroup.class.php");
////                    $obj = new commandeGroup($db);
//                }
//                break;
//            case 'process': {
//                    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
//                    $obj = new processDet($db);
//                    $tabMenu[0] = "Process";
//                }
//                break;
//            case 'product': {
//                    require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
//                    $obj = new product($db);
//                    $tabMenu[0] = "products";
//                }
//                break;
//            case 'user': {
//                    require_once(DOL_DOCUMENT_ROOT . "/user/class/user.class.php");
//                    $obj = new user($db);
//                    $tabMenu[0] = "home";
//                    $tabMenu[1] = "users";
//                }
//                break;
//            case 'ndfp': {
//                    require_once(DOL_DOCUMENT_ROOT . "/ndfp/class/ndfp.class.php");
//                    $obj = new ndfp($db);
//                    $tabMenu[0] = "accountancy";
//                }
//                break;
//            case 'synopsisholiday': {
//                    require_once(DOL_DOCUMENT_ROOT . "/synopsisholiday/class/holiday.class.php");
//                    $obj = new Holiday($db);
//                    $tabMenu[0] = "hrm";
//                }
//                break;
//        }
        $tabTypeObject = self::getTabTypeObject($type);

        if (isset($tabTypeObject[$type])) {
            $data = $tabTypeObject[$type];
            if(is_file(DOL_DOCUMENT_ROOT . $data['path'])){
            require_once DOL_DOCUMENT_ROOT . $data['path'];
            $nomObj = $data['obj'];
            if(class_exists($nomObj)){
                $obj = new $nomObj($db);
                if(!method_exists($obj, "getNomUrl")){
                    dol_syslog("Pas de methode getNomUrl dans la class ".$nomObj,3);
                    $obj = false;
                }
            }
            else{
                dol_syslog("Impossible de charger l'object ".$nomObj,3);
            }
            $tabMenu[0] = $data['tabMenu1'];
            $tabMenu[1] = $data['tabMenu2'];
            }
            else
                dol_syslog("Impossible de chargger le fichier ".DOL_DOCUMENT_ROOT . $data['path'],3);
        } else {
            dol_syslog("Type inconnue : " . $type, 3);
        }



        if (is_object($obj))
            @$obj->loadObject = false;
        return array($obj, $tabMenu);
    }

    public static function getTabTypeObject($typeFiltre = null) {
        $tabTypeObject = array('synopsischrono' => array("obj" => "Chrono", "tabMenu1" => "Process"),
            'propal' => array("path" => "/comm/propal/class/propal.class.php",
                "tabMenu1" => "commercial",
                "tabMenu2" => "propals",
                "urls" => array("comm/propal.php")),
            'facture' => array("path" => "/compta/facture/class/facture.class.php",
                "tabMenu1" => "accountancy",
                "urls" => array("compta/facture.php"),
                "nomIdUrl" => "facid"),
            'fichinter' => array(),
            'synopsisfichinter' => array("tabMenu1" => "synopsisficheinter"),
            'synopsisdemandeinterv' => array("tabMenu1" => "synopsisficheinter"),
            'contrat' => array("tabMenu1" => "commercial",
                "tabMenu2" => "contracts"),
            'expedition' => array(),
            'livraison' => array(),
            'commande' => array("tabMenu1" => "commercial",
                "tabMenu2" => "orders",
                "urls" => array("Synopsis_PrepaCommande/prepacommande.php", "commande/card.php")),
            'banque' => array("obj" => 'Account',
                "path" => "/compta/bank/class/account.class.php",
                "urls" => array("compta/bank/card.php")),
            'contact' => array("tabMenu1" => "companies"),
            'societe' => array("tabMenu1" => "companies",
                "urls" => array("comm/card.php"),
                "nomIdUrl" => "socid"),
            'projet' => array("obj" => 'project',
                "tabMenu1" => "synopsisprojet"),
            'synopsisprojet' => array("obj" => 'synopsisproject',
                "path" => "/synopsisprojet/class/synopsisproject.class.php",
                "tabMenu1" => "synopsisprojet"),
            'tache' => array("obj" => 'Task',
                "path" => "/projet/class/task.class.php",
                "tabMenu1" => "synopsisprojet"),
            'process' => array("obj" => 'processDet',
                "path" => "/Synopsis_Process/class/process.class.php",
                "tabMenu1" => "Process"),
            'product' => array("tabMenu1" => "products"),
            'user' => array("tabMenu1" => "home",
                "tabMenu2" => "users"),
            'ndfp' => array("tabMenu1" => "accountancy"),
            'synopsisholiday' => array("obj" => 'synopsisholiday',
                   'path' => '/synopsisholiday/class/holiday.class.php',
                "tabMenu1" => "hrm"),
//            'UserGroup' => array("path" => "/user/class/usergroup.class.php",
//                "urls" => array("/group/card.php")),
               'synopsistasks' => array('urls' => array("synopsisprojet/tasks/task.php"),
                   'path' => '/synopsisprojet/class/task.class.php',
                   'obj' => 'Task')
            );

        $tabTypeObject2 = array();
        foreach ($tabTypeObject as $typeT => $data) {
            if ($typeFiltre == null || $typeFiltre == $typeT) {
                if (!isset($data['type']))
                    $data['type'] = $typeT;
                if (!isset($data['obj']))
                    $data['obj'] = ucfirst($typeT);

                if (!isset($data['path']))
                    $data['path'] = "/" . $data['type'] . "/class/" . strtolower($data['obj']) . ".class.php";
                if (!is_file(DOL_DOCUMENT_ROOT . $data['path'])) {
                    $data['path1'] = $data['path'];
                    $data['path'] = "/core/class/" . $data['obj'] . ".class.php";
                }
                if (!is_file(DOL_DOCUMENT_ROOT . $data['path'])) {
                    if ($typeFiltre != null)
                        die("impossible de charger " . $data['path1'] . " ni " . $data['path']);
                    else
                        dol_syslog("Impossible de charger " . DOL_DOCUMENT_ROOT . $data['path'], 3);
                }
                else {

                    if (!isset($data['tabMenu1']))
                        $data['tabMenu1'] = "";
                    if (!isset($data['tabMenu2']))
                        $data['tabMenu2'] = "";


                    if (!isset($data['urls']))
                        $data['urls'] = array("/" . $data['type'] . "/card.php");
                    if (!isset($data['nomIdUrl']))
                        $data['nomIdUrl'] = "id";

                    global $conf;

                    $version = isset($conf->global->MAIN_VERSION_LAST_UPGRADE) ? $conf->global->MAIN_VERSION_LAST_UPGRADE : $conf->global->MAIN_VERSION_LAST_INSTALL;
                    if (substr($version, 0, 1) > 2 && substr($version, 2, 1) < 7)
                        foreach ($data['urls'] as $idT => $url)
                            $data['urls'][$idT] = str_replace("card.php", "fiche.php", $url);
//                echo "<pre>";print_r($conf);



                    $tabTypeObject2[$typeT] = $data;
                }
            }
        }
        return $tabTypeObject2;
    }

    public static function getTypeAndId($url = null, $request = null) {

        if ($url == NULL)
            $url = $_SERVER['REQUEST_URI'];
        if ($request == NULL)
            $request = $_REQUEST;
        if (stripos($url, "ajax") != false) {
            return null;
        }


        $tabTypeObject = self::getTabTypeObject();
        foreach ($tabTypeObject as $typeT => $dataT) {
            foreach ($dataT['urls'] as $filtreUrl) {
                if (stripos($url, $filtreUrl) !== false) {
                    $element_type = $typeT;
                    $element_id = $request[$dataT['nomIdUrl']];
                }
            }
        }
        return array($element_type, $element_id);
        /* if (stripos($url, "compta/facture") != false) {
          $element_type = 'facture';
          @$element_id = $request['facid'];
          } elseif (stripos($url, "societe/soc.php") || stripos($url, "comm/card.php?socid=") || stripos($url, "comm/prospect/fiche.php?socid=")) {
          $element_type = 'societe';
          @$element_id = $request['socid'];
          } elseif (stripos($url, "product/card.php") != false) {
          $element_type = 'product';
          @$element_id = $request['id'];
          } elseif (stripos($url, "projet/tasks/task.php") != false) {
          $element_type = 'tache';
          @$element_id = $request['id'];
          } elseif (stripos($url, "projet/") != false) {
          $element_type = 'projet';
          @$element_id = $request['id'];
          } elseif (stripos($url, "commande/") != false) {
          $element_type = 'commande';
          @$element_id = $request['id'];
          } elseif (stripos($url, "compta/bank/") != false) {
          $element_type = 'banque';
          @$element_id = $request['id'];
          } elseif (stripos($url, "fichinter/") != false) {
          $element_type = 'FI';
          @$element_id = $request['id'];
          } elseif (stripos($url, "synopsisdemandeinterv/") != false) {
          $element_type = 'DI';
          @$element_id = $request['id'];
          } elseif (stripos($url, "contrat/") != false) {
          $element_type = 'contrat';
          @$element_id = $request['id'];
          } elseif (stripos($url, "user/card.php") != false) {
          $element_type = 'user';
          @$element_id = $request['id'];
          } elseif (stripos($url, "comm/propal.php") != false) {
          $element_type = 'propal';
          @$element_id = $request['id'];
          } elseif (stripos($url, "/synopsischrono/admin/synopsischrono") != false) {
          $element_type = 'configChrono';
          @$element_id = $request['id'];
          } elseif (stripos($url, "synopsischrono") != false) {
          $element_type = 'chrono';
          @$element_id = $request['id'];
          } elseif (stripos($url, "Synopsis_Process") != false) {
          $element_type = 'process';
          @$element_id = $request['process_id'];
          } elseif (stripos($url, "ndfp") != false) {
          $element_type = 'ndfp';
          @$element_id = $request['id'];
          } elseif (stripos($url, "expedition") != false) {
          $element_type = 'expedition';
          @$element_id = $request['id'];
          } elseif (stripos($url, "synopsisholiday") != false) {
          $element_type = 'synopsisholiday';
          @$element_id = $request['id'];
          } else {
          return null;
          } */
    }

}
