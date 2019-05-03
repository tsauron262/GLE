<?php

require_once(DOL_DOCUMENT_ROOT . "/synopsisres/extractObjTypeId.php");

class Actionssynopsisfav {

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
        $tabElem = getTypeAndId();
        $element_type = $tabElem[0];
        $element_id = $tabElem[1];


        if (isset($conf->global->MAIN_MODULE_SYNOPSISFAV)) {
            $socid = favoriCli::saveHisto($element_type, $element_id);

            if ($socid > 0)
                $return .= favoriCli::getBlocHisto($context, $socid);
        }

        $this->resprints = $return;
        return 0;
    }

}

class favoriCli {

    static function getBlocHisto($context, $socid) {
        global $db, $user, $conf, $langs;
        $soc = new Societe($db);
        $soc->fetch($socid);
        $langs->load("fav@synopsisfav");
//        if ($conf->global->MAIN_MODULE_synopsisfav && $user->rights->MiniHisto->all->Afficher) {
        $return = '<div class="blockvmenufirst blockvmenupair' . ($context == 1 ? ' vmenu' : '') . '">';
        $return .= '<div class="menu_titre">';
        $return .= '<a href="#" class="vmenu">' . $langs->trans("HISTOFAV") . "</a><br/>" . $soc->name . '';
        $return .= "</div>";
        $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Fav_User
                     WHERE soc_refid = " . $socid .
                " AND ref != '' AND element_type != '' ORDER BY tms DESC" .
                (isset($conf->global->SYNOPSIS_FAV_LENGTH) && $conf->global->SYNOPSIS_FAV_LENGTH > 0 ? " LIMIT 0," . $conf->global->SYNOPSIS_FAV_LENGTH : " LIMIT 0,5");
//die($requete);
        $sql = $db->query($requete);
        $return .= "<form method='post'>";
        while ($res = $db->fetch_object($sql)) {
            if (isset($_REQUEST['supprFav' . $res->id]))
                $db->query("DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Fav_User WHERE id =" . $res->id);
            else {
//print '<a href="#" class="vsmenu">'..'</a>';
                $ret = self::histoUser($res);
                if ($ret)
                    $return .= "<div class='menu_contenu'>  " . $ret . "<input type='submit' name='supprFav" . $res->id . "' class='supprFav' value='X'/></div>";
            }
        }
        $return .= "</form>";
        $return .= "<div class=\"menu_end\">";
        $return .= '<form method="post"><input type="submit" name="saveFav" class="butAction" value="Ajouter au favori"/></form>';
        $return .= '</div></div><div class="blockvmenuend"></div>';
        return $return;
    }

    public static function histoUser($res) {
        global $conf;
        $tabResult = favoriCli::getObjAndMenu($res->element_type);
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
                if (isset($tabResult[2])) {
                    $data = $tabResult[2];
                    if (isset($data['changeNomUrl']) && isset($data['changeNomUrl'][1])) {
                        $nomUrl = str_replace($data['changeNomUrl'][0], $data['changeNomUrl'][1], $nomUrl);
                    }
                    if (isset($data['refPlus'])) {
                        $nomUrl = substr($nomUrl, 0, -4) . $data['refPlus'] . "</a>";
                    }
                }
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


                $socid = 0;
                if (isset($obj->fk_soc))
                    $socid = $obj->fk_soc;
                elseif (isset($obj->fk_societe))
                    $socid = $obj->fk_societe;
                elseif (isset($obj->socid))
                    $socid = $obj->socid;
                elseif (isset($obj->id) && get_class($obj) == "Societe")
                    $socid = $obj->id;

                if ($socid > 0) {
                    if (isset($_REQUEST['saveFav'])) {
                        $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Fav_User
                     WHERE soc_refid = " . $socid . "
                       AND element_type = '" . $element_type . "'
                       AND element_id = " . $element_id;
                        $sql = $db->query($requete);
                        if ($db->num_rows($sql) > 0) {
                            $res = $db->fetch_object($sql);
                            $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Fav_User
                           SET tms = now(),
                               ref = '" . addslashes($ref) . "'
                         WHERE id = " . $res->id;
                        } else {
                            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Fav_User
                                    (`soc_refid`,`element_id`,`ref`,`element_type`)
                             VALUES (" . $socid . "," . $element_id . ",'" . addslashes($ref) . "','" . $element_type . "')";
                            //        print $requete;
                        }
                        $sql = $db->query($requete);
                        //            die($requete);
                    }
                    return($socid);
                }
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
        $tabTypeObject = getTabTypeObject($type);

        if (isset($tabTypeObject[$type])) {
            $data = $tabTypeObject[$type];
            if (is_file(DOL_DOCUMENT_ROOT . $data['path'])) {
                require_once DOL_DOCUMENT_ROOT . $data['path'];
                $nomObj = $data['obj'];
                if (class_exists($nomObj)) {
                    if(stripos($nomObj, "bimp") !== false){
                        $obj = BimpObject::getInstance($data['module'], $nomObj);
                    }
                    else{
                        $obj = new $nomObj($db);
                    }
                    if (!method_exists($obj, "getNomUrl")) {
                        dol_syslog("Pas de methode getNomUrl dans la class " . $nomObj, 3);
                        $obj = false;
                    }
                } else {
                    dol_syslog("Impossible de charger l'object " . $nomObj, 3);
                }
                $tabMenu[0] = $data['tabMenu1'];
                $tabMenu[1] = $data['tabMenu2'];
            } else
                dol_syslog("Impossible de chargger le fichier " . DOL_DOCUMENT_ROOT . $data['path'], 3);
        } else {
            dol_syslog("Type inconnue : " . $type, 3);
        }



        if (is_object($obj))
            @$obj->loadObject = false;
        return array($obj, $tabMenu, $data);
    }

}
