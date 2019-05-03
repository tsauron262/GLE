<?php
require_once(DOL_DOCUMENT_ROOT."/synopsisres/extractObjTypeId.php");

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
        $tabElem = getTypeAndId();
        $element_type = $tabElem[0];
        $element_id = $tabElem[1];


        if (isset($conf->global->MAIN_MODULE_SYNOPSISHISTO)) {
            histoNavigation::saveHisto($element_type, $element_id);
            $return .= histoNavigation::getBlocHisto($context);
        }
        
       if($user->pass_indatabase_crypted == ""){
            setEventMessages("<a href='".DOL_URL_ROOT."/user/card.php?id=".$user->id."'>Merci de changer votre mdp (les comptes non mise à jour le 30 avril seront désactivés)</a>", null, 'errors');
            setEventMessages("<a href='".DOL_URL_ROOT."/user/card.php?id=".$user->id."'>Merci de changer votre mdp (les comptes non mise à jour le 30 avril seront désactivés)</a>", null, 'errors');
            setEventMessages("<a href='".DOL_URL_ROOT."/user/card.php?id=".$user->id."'>Merci de changer votre mdp (les comptes non mise à jour le 30 avril seront désactivés)</a>", null, 'errors');
            setEventMessages("<a href='".DOL_URL_ROOT."/user/card.php?id=".$user->id."'>Merci de changer votre mdp (les comptes non mise à jour le 30 avril seront désactivés)</a>", null, 'errors');
            setEventMessages("<a href='".DOL_URL_ROOT."/user/card.php?id=".$user->id."'>Merci de changer votre mdp (les comptes non mise à jour le 30 avril seront désactivés)</a>", null, 'errors');
            setEventMessages("<a href='".DOL_URL_ROOT."/user/card.php?id=".$user->id."'>Merci de changer votre mdp (les comptes non mise à jour le 30 avril seront désactivés)</a>", null, 'errors');
            setEventMessages("<a href='".DOL_URL_ROOT."/user/card.php?id=".$user->id."'>Merci de changer votre mdp (les comptes non mise à jour le 30 avril seront désactivés)</a>", null, 'errors');
            setEventMessages("<a href='".DOL_URL_ROOT."/user/card.php?id=".$user->id."'>Merci de changer votre mdp (les comptes non mise à jour le 30 avril seront désactivés)</a>", null, 'errors');
            setEventMessages("<a href='".DOL_URL_ROOT."/user/card.php?id=".$user->id."'>Merci de changer votre mdp (les comptes non mise à jour le 30 avril seront désactivés)</a>", null, 'errors');
            setEventMessages("<a href='".DOL_URL_ROOT."/user/card.php?id=".$user->id."'>Merci de changer votre mdp (les comptes non mise à jour le 30 avril seront désactivés)</a>", null, 'errors');
            setEventMessages("<a href='".DOL_URL_ROOT."/user/card.php?id=".$user->id."'>Merci de changer votre mdp (les comptes non mise à jour le 30 avril seront désactivés)</a>", null, 'errors');
       }

        if(isset($user->array_options['options_alias'])){
            if(stripos($user->array_options['options_alias'], "@itribu") > 0)
                 setEventMessages($user->array_options['options_alias']."<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci d'utiliser  un Apple ID non itribu</a>", null, 'errors');
        }
        if(isset($user->array_options['options_mail_sec'])){
            if($user->office_phone == ""){
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre téléphone pro</a>", null, 'errors');
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre téléphone pro</a>", null, 'errors');
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre téléphone pro</a>", null, 'errors');
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre téléphone pro</a>", null, 'errors');
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre téléphone pro</a>", null, 'errors');
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre téléphone pro</a>", null, 'errors');
            }

            if($user->array_options['options_mail_sec'] == ""){
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre email de secours</a>", null, 'errors');
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre email de secours</a>", null, 'errors');
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre email de secours</a>", null, 'errors');
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre email de secours</a>", null, 'errors');
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre email de secours</a>", null, 'errors');
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre email de secours</a>", null, 'errors');
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre email de secours</a>", null, 'errors');
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre email de secours</a>", null, 'errors');
            }
            elseif(stripos($user->array_options['options_mail_sec'], "@") === false){
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre email de secours valide</a>", null, 'errors');
            }
            elseif(stripos($user->array_options['options_mail_sec'], "@bimp") > 0){
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre email de secours non bimp</a>", null, 'errors');
            }
            elseif(stripos($user->array_options['options_mail_sec'], "@itribu") > 0){
                 setEventMessages("<a href='".DOL_URL_ROOT."/bimpcore/tabs/user.php'>Merci de renseigner votre email de secours non itribu</a>", null, 'errors');
            }
        }
       
//        die("finfff");
       
       

        $this->resprints = $return;
        return 0;
    }

}

class histoNavigation {

    static function getBlocHisto($context) {
        global $db, $user, $conf, $langs;
        $langs->load("histo@synopsishisto");
//        if ($conf->global->MAIN_MODULE_SYNOPSISHISTO && $user->rights->MiniHisto->all->Afficher) {
        $return .= '<div class="blockvmenufirst blockvmenupair' . ($context == 1 ? ' vmenu' : '') . '">';
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
        $return .= "</div><div class=\"blockvmenuend\"></div>";
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
            if ($result > 0 && $obj->id > 0) {
                $replace = ($tabMenu[0] ? '&mainmenu=' . $tabMenu[0] : '') . ($tabMenu[1] ? '&leftmenu=' . $tabMenu[1] : '') . '">';
                if ($res->element_type == "propal")
                    $nomUrl = str_replace('">', $replace, $obj->getNomUrl(1));
                else
                    $nomUrl = str_replace('">', $replace, $obj->getNomUrl(1, '', 20));
                if(isset($tabResult[2])){
                    $data = $tabResult[2];
                    if(isset($data['changeNomUrl']) && isset($data['changeNomUrl'][1])){
                        $nomUrl = str_replace ($data['changeNomUrl'][0], $data['changeNomUrl'][1], $nomUrl);
                    }
                    if(isset($data['refPlus'])){
                        $nomUrl = substr ($nomUrl, 0, -4). $data['refPlus']."</a>";
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
        if (isset($element_id) && isset($element_type) && $element_type != '' && $element_id > 0) {
            $obj = self::getObj($element_type);
            if ($obj) {
                if($obj->fetch($element_id)){
                    if(method_exists($obj, "getData"))
                            $ref = $obj->getData("ref");
                    else
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
                    }
                    $sql = $db->query($requete);
                    return($sql);
                }
            }
        }
    }

    public static function getObjAndMenu($type) {
        global $db, $conf;
        $tabMenu = array(false, false);
        $obj = false;

        $tabTypeObject = getTabTypeObject($type);

        if (isset($tabTypeObject[$type])) {
            $data = $tabTypeObject[$type];
            if(is_file(DOL_DOCUMENT_ROOT . $data['path'])){
                require_once DOL_DOCUMENT_ROOT . $data['path'];
                $nomObj = $data['obj'];
                if(class_exists($nomObj)){
                    if(stripos($nomObj, "bimp") !== false){
                        $obj = BimpObject::getInstance($data['module'], $nomObj);
                    }
                    else{
                        $obj = new $nomObj($db);
                    }
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



        if (is_object($obj)){
            @$obj->loadObject = false;
        }
        else
            dol_syslog("Pas d'objet : " . $type, 3);
        return array($obj, $tabMenu, $data);
    }

}
