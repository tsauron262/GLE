<?php

function printHead($type, $id, $js = '') {
    global $db, $langs;
    switch ($type) {
        case "Commande": {

                require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/order.lib.php");
                $obj = new Commande($db);
                $obj->fetch($id);
                $head = commande_prepare_head($obj);
                $titreType = "CustomerOrder";
                $nomType = "commande";
            }

            break;
        case "Propal": {

                require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/propal.lib.php");
                $obj = new Propal($db);
                $obj->fetch($id);
                $head = propal_prepare_head($obj);
                $titreType = "Proposal";
                $nomType = "proposition";
            }

            break;
        case "Facture": {

                require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/invoice.lib.php");
                $obj = new Facture($db);
                $obj->fetch($id);
                $head = facture_prepare_head($obj);
                $titreType = "Invoice";
                $nomType = "facture";
            }

            break;
        case "Expedition": {

                require_once(DOL_DOCUMENT_ROOT . "/expedition/class/expedition.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/sendings.lib.php");
                $obj = new Expedition($db);
                $obj->fetch($id);
                $head = sending_prepare_head($obj);
                $titreType = "Sending";
                $nomType = "l\'expédition";
            }

            break;
        case "ActionComm": {

                require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/actionco.lib.php");
                $obj = new ActionComm($db);
                $obj->fetch($id);
                $head = actionco_prepare_head($obj);
                $titreType = "Invoice";
                $nomType = "facture";
            }

            break;
        case "Affaire": {

                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Affaire/Affaire.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Affaire/fct_affaire.php");
                $obj = new Affaire($db);
                $obj->fetch($id);
                $head = affaire_prepare_head($obj);
                $titreType = "Invoice";
                $nomType = "facture";
            }

            break;
        case "FactureFournisseur": {

                require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.facture.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/fourn.lib.php");
                $obj = new FactureFournisseur($db);
                $obj->fetch($id);
                $head = facturefourn_prepare_head($obj);
                $titreType = "Invoice";
                $nomType = "facture";
            }

            break;
        case "CommandeFournisseur": {

                require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.commande.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/fourn.lib.php");
                $obj = new CommandeFournisseur($db);
                $obj->fetch($id);
                $head = commandefourn_prepare_head($obj);
                $titreType = "Invoice";
                $nomType = "facture";
            }

            break;
        case "Contrat": {

                require_once(DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/contract.lib.php");
                $obj = new Contrat($db);
                $obj->fetch($id);
                $langs->load("contracts");
                $head = contract_prepare_head($obj);
                $titreType = "Contrat";
                $nomType = "contract";
            }

            break;
        case "Chrono": {

                require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/core/lib/synopsischrono.lib.php");
                $obj = new Chrono($db);
                $obj->fetch($id);
                $head = chrono_prepare_head($obj);
                $titreType = "Invoice";
                $nomType = "facture";
            }

            break;
        case "Societe": {

                require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
                $obj = new Societe($db);
                $obj->fetch($id);
                $head = societe_prepare_head($obj);
                $titreType = "Invoice";
                $nomType = "facture";
            }

            break;
        case "Contact": {

                require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/contact.lib.php");
                $obj = new Contact($db);
                $obj->fetch($id);
                $head = contact_prepare_head($obj);
                $titreType = "Invoice";
                $nomType = "facture";
            }

            break;
        case "synopsisdemandeinterv": {

                require_once(DOL_DOCUMENT_ROOT . "/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/synopsisdemandeinterv.lib.php");
                $obj = new Synopsisdemandeinterv($db);
                $obj->fetch($id);
                $head = synopsisdemandeinterv_prepare_head($obj);
                $titreType = "Invoice";
                $nomType = "facture";
            }

            break;
        case "Fichinter": {

                require_once(DOL_DOCUMENT_ROOT . "/fichinter/class/fichinter.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/fichinter.lib.php");
                $obj = new Fichinter($db);
                $obj->fetch($id);
                $head = fichinter_prepare_head($obj);
                $titreType = "Invoice";
                $nomType = "facture";
            }

            break;
        case "Livraison": {

                require_once(DOL_DOCUMENT_ROOT . "/livraison/class/livraison.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/sendings.lib.php");
                $obj = new Livraison($db);
                $obj->fetch($id);
                $head = delivery_prepare_head($obj);
                $titreType = "Invoice";
                $nomType = "facture";
            }

            break;
        case "Paiement": {

                require_once(DOL_DOCUMENT_ROOT . "/paiement.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/paiement.lib.php");
                $obj = new Paiement($db);
                $obj->fetch($id);
                $head = paiement_prepare_head($obj);
                $titreType = "Invoice";
                $nomType = "facture";
            }
            break;
        case "PaiementFourn": {

                require_once(DOL_DOCUMENT_ROOT . "/fourn/facture/paiementfourn.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/paiement.lib.php");
                $obj = new PaiementFourn($db);
                $obj->fetch($id);
                $head = paiementFourn_prepare_head($obj);
                $titreType = "Invoice";
                $nomType = "facture";
            }
            break;
        case "Product": {

                require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/product.lib.php");
                $obj = new Product($db);
                $obj->fetch($id);
                $head = product_prepare_head($obj, $user);
                $titreType = "Invoice";
                $nomType = "facture";
            }
            break;
        case "Project": {

                require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
                require_once(DOL_DOCUMENT_ROOT . "/core/lib/project.lib.php");
                $obj = new Project($db);
                $obj->fetch($id);
                $head = project_prepare_head($obj);
                $titreType = "Invoice";
                $nomType = "facture";
            }
            break;
        case "Campagne": {
                global $langs, $conf;
                require_once(DOL_DOCUMENT_ROOT . "/BabelProspect/Campagne.class.php");
                $obj = new Campagne($db);
                $obj->fetch($id);
                $head = array();
                $h = 0;
                $head[$h][0] = DOL_URL_ROOT . '/BabelProspect/affichePropection.php?action=list&campagneId=' . $obj->id;
                $head[$h][1] = $langs->trans("Retour campagne");
                $head[$h][2] = 'campagne';
                $h++;
                $head[$h][0] = DOL_URL_ROOT . '/Synopsis_Process/listProcessForElement.php?type=Campagne&id=' . $obj->id;
                $head[$h][1] = $langs->trans("Process");
                $head[$h][2] = 'process';
                $head[$h][4] = 'ui-icon ui-icon-gear';
                $titreType = "Invoice";
                $nomType = "facture";
            }
            break;
    }

    llxHeader($js, $langs->trans('Process de la ' . $nomType));

    dol_fiche_head($head, 'process', $langs->trans($titreType));
}

function getNomSoc($socId) {
    global $db;
    $soc = new societe($db);
    $soc->fetch($socId);
    return $soc->getNomUrl(1);
}

function getNomProjet($projetId) {
    global $db;
    include_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
    $soc = new project($db);
    $soc->fetch($projetId);
    return $soc->getNomUrl(1);
}

class process extends CommonObject {

    public $db;
    public $id;
    public $description;
    public $bloquant;
    public $pretraitement;
    public $posttraitement;
    public $formulaire_refid;
    public $formulaire = false;
    public $PROCESS_MASK;
    public $ADDON_PDF;
    public $PROCESS_ADDON;
    public $typeElement_refid;
    public $typeElement = false;
    public $trigger_refid;
    public $revision_model_refid;
    public $trigger = false;
    public $detail = array();
    private $arrRightValid = array();
    public $valideAction;
    public $reviseAction;
    private static $cache = array();

    public function process($DB) {
        $this->db = $DB;
    }

    public static function majTabsProcess($db) {
        self::deleteTabsProcess($db);
        $requete = "SELECT p2.`type`, p2.`_GET_id` as idT FROM `" . MAIN_DB_PREFIX . "Synopsis_Process` p, " . MAIN_DB_PREFIX . "Synopsis_Process_type_element p2 WHERE p.`typeElement_refid` = p2.id AND fk_statut = 1 GROUP BY p2.`type`";
        $sql = $db->query($requete);
        $i = -1;
        while ($res = $db->fetch_object($sql)) {
            $i++;
            $typeD = str_replace(array("commande", "facture", "contrat"), array("order", "invoice", "contract"), strtolower($res->type));
            $requete3 = "INSERT INTO `" . MAIN_DB_PREFIX . "const`(`name`, `entity`, `value`, `type`, `visible`) VALUES ('MAIN_MODULE_SYNOPSISPROCESS_TABS_" . $i . "',1,'" . $typeD . ":+process:Process:@monmodule:/Synopsis_Process/listProcessForElement.php?type=" . $res->type . "&"/* . $res->idT . */ . "id=__ID__','chaine',0)";
//            die($requete3);
            $sql2 = $db->query($requete3);
        }
    }

    public static function deleteTabsProcess($db) {
        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "const WHERE `name` LIKE  '%MAIN_MODULE_SYNOPSISPROCESS_TABS%'";
        $db->query($requete);
    }

    public function setActiveOn($type_id, $element_id) {
        $processDet = new processDet($this->db);
        $processDet->ref = $this->getNextRef();
        $processDet->process_refid = $this->id;
        $processDet->element_refid = $element_id;
        $processDetId = $processDet->add();

        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Processdet_active
                                (type_refid,process_refid,element_refid, processdet_refid)
                         VALUES (" . $type_id . "," . $this->id . "," . $element_id . "," . $processDetId . ")";
        $sql = $this->db->query($requete);
        if ($sql) {
            synopsisHook::reloadPage();
            return 1;
        } else {
            $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
            return -1;
        }
    }

    public function unSetActiveOn($type_id, $element_id) {
        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet_active WHERE type_refid=" . $type_id . " AND process_refid=" . $this->id . " AND element_refid=" . $element_id;
        $sql = $this->db->query($requete);
        if ($sql) {
            return 1;
        } else {
            $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
            return -1;
        }
    }

    public function getRights($userObj) {
        $requete = "SELECT concat('process'," . $this->id . ") as p,
                           d.code,
                           'process_user' as pr,
                           r.valeur,
                           isValidationRight,
                           isValidationForAll
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def as d
                 LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Process_rights as r ON user_refid = " . $userObj->id . " AND process_refid = " . $this->id . " AND r.right_refid = d.id
                     WHERE d.active = 1 ";
        $sql = $this->db->query($requete);
// print $requete;
        $this->arrRightValid = array();
        while ($res = $this->db->fetch_object($sql)) {
            //$arrRightValid
            $val = $res->valeur;
            $code = $res->code;
            $process = $res->p;
            $module = $res->pr;
            $this->arrRightValid[$code] = array('isValidationRight' => ($res->isValidationRight > 0 ? true : false),
                'isValidationForAll' => ($res->isValidationForAll > 0 ? true : false));
            if ($val == 1)
                @$userObj->rights->$module->$process->$code = 1;
            else
                @$userObj->rights->$module->$process->$code = false;
        }
        return $userObj;
    }

    public function getGrpRights($grpObj) {
        $requete = "SELECT concat('process'," . $this->id . ") as p,
                           d.code,
                           'process_group' as pr,
                           r.valeur,
                           isValidationRight,
                           isValidationForAll
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def as d
                 LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Process_group_rights as r ON group_refid = " . $grpObj->id . " AND process_refid = " . $this->id . " AND r.right_refid = d.id
                     WHERE d.active = 1 ";
        $sql = $this->db->query($requete);
// print $requete;
        while ($res = $this->db->fetch_object($sql)) {
            $val = $res->valeur;
            $code = $res->code;
            $process = $res->p;
            $module = $res->pr;
            $this->arrRightValid[$code] = array('isValidationRight' => ($res->isValidationRight > 0 ? true : false),
                'isValidationForAll' => ($res->isValidationForAll > 0 ? true : false));

            if ($val == 1)
                @$grpObj->rights->$module->$process->$code = 1;
            else
                @$grpObj->rights->$module->$process->$code = false;
        }
        return $grpObj;
    }

    public function canValidate($userObjOrId = false) {
        $userObj = false;
        if (is_numeric($userObj)) {
            $userObj = new User($this->db);
            $userObj->fetch($userObjOrId);
        } else if (is_object($userObjOrId)) {
            $userObj = $userObjOrId;
        } else {
            global $user;
            $userObj = $user;
        }
        if ($user->rights->process->valider)
            return(true);
        else {
            $userObj = $this->getGlobalRights($userObjOrId);
            $tmpProcess = 'process' . $this->id;
            foreach ($userObj->rights->process_user->$tmpProcess as $key => $val) {
                if ($this->arrRightValid[$key]['isValidationForAll']) {
                    return (true);
                } else if ($this->arrRightValid[$key]['isValidationRight']) {
                    return(true);
                }
            }
        }
        return(false);
    }

    public function getGlobalRights($userObjOrId = false) {
        $userObj = false;
        if (is_numeric($userObj)) {
            $userObj = new User($this->db);
            $userObj->fetch($userObjOrId);
        } else if (is_object($userObjOrId)) {
            $userObj = $userObjOrId;
        } else {
            global $user;
            $userObj = $user;
        }
        $this->getRights($userObj);
        //Group de l'utilisateur
//        $groups = $userObj->listGroupIn();
        require_once(DOL_DOCUMENT_ROOT . "/user/class/usergroup.class.php");
        $usergroup = new UserGroup($this->db);
        $groups = $usergroup->listGroupsForUser($userObj->id);
        //  var_dump($groups);
        foreach ($groups as $group) {
            $group = $this->getGrpRights($group);
            foreach ($userObj->rights->process_user as $key => $val) {
                foreach ($val as $key1 => $val1) {
                    if ($group->rights->process_group->$key->$key1 && !$val1) {
                        $userObj->rights->process_user->$key->$key1 = "g";
                    }
                }
            }
        }
        return($userObj);
    }

    public function validateDet($element_id, $type_validation) {
        global $user;
        $this->db->begin();
        $typeId = $this->getValidationType($type_validation);
        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet_validation
                          WHERE validation_type_refid = " . $typeId . " AND process_refid = " . $this->id . ' AND element_refid =' . $element_id;
        $sql = $this->db->query($requete);
        if ($sql) {
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Processdet_validation (validation_type_refid,process_refid,user_refid,element_refid)
                             VALUES (" . $typeId . "," . $this->id . "," . $user->id . "," . $element_id . ")";
            $sql = $this->db->query($requete);
            if ($sql) {
                $this->db->commit();
                //Mise à jour du statut du processDet
                $statut = $this->newProcessDetStatut();

                return 1;
            } else {
                $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
                $this->db->rollback();
                return -1;
            }
        } else {
            $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
            $this->db->rollback();
            return -1;
        }
    }

    public function newProcessDetStatut() {
        //Nouveau statut
        //Trouve le processDet
        //modifie le statut
    }

    public function getNextRef() {
        global $mysoc;
        $file = 'mod_process_' . $this->PROCESS_ADDON;
        require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsis_process/" . $file . ".php");
        $module = new $file;
        $nextval = $module->getNextValue($mysoc, $this);
        return($nextval);
    }

    public function getValidationType($typeCode) {
        $typeId = false;
        $requete = "SELECT id FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def WHERE code = '" . $typeCode . "'";
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        return($res->id);
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            $this->id = $id;
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process WHERE id = " . $id;
            $sql = $this->db->query($requete);
            if ($sql) {
                $res = $this->db->fetch_object($sql);
                $this->label = $res->label;
                $this->fk_statut = $res->fk_statut;
                $this->description = $res->description;
                $this->pretraitement = $res->pretraitement;
                $this->posttraitement = $res->posttraitement;
                $this->validAction = $res->validAction;
                $this->askValidAction = $res->askValidAction;
                $this->reviseAction = $res->reviseAction;
                $this->bloquant = $res->bloquant;
                $this->PROCESS_MASK = $res->PROCESS_MASK;
                $this->PROCESS_ADDON = $res->PROCESS_ADDON;
                $this->ADDON_PDF = $res->ADDON_PDF;
                $this->typeElement_refid = $res->typeElement_refid;
                $this->trigger_refid = $res->trigger_refid;
                $this->revision_model_refid = ($res->revision_model_refid > 0 ? $res->revision_model_refid : false);

                if ($this->trigger_refid > 0) {
                    $el = new process_trigger($this->db);
                    $result = $el->fetch($this->trigger_refid);
                    $this->trigger = $el;
                }

                if ($this->typeElement_refid > 0) {
                    $el = new process_element_type($this->db);
                    $result = $el->fetch($this->typeElement_refid);
                    $this->typeElement = $el;
                }

                $this->formulaire_refid = $res->formulaire_refid;
                if ($this->formulaire_refid > 0) {
                    if (isset(self::$cache[$this->id]['formulaire'][$this->formulaire_refid]))
                        $this->formulaire = self::$cache[$this->id]['formulaire'][$this->formulaire_refid];
                    else {
                        $form = new Formulaire($this->db);
                        $result = $form->fetch($this->formulaire_refid);
                        $this->formulaire = $form;
                        self::$cache[$this->id]['formulaire'][$this->formulaire_refid] = $this->formulaire;
                    }
                }
                if (isset(self::$cache[$this->id]['lignes'])) {
                    $this->detail = self::$cache[$this->id]['lignes'];
                } else {
                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet WHERE process_refid = " . $this->id;
                    $sql = $this->db->query($requete);
                    while ($res = $this->db->fetch_object($sql)) {
                        $ligne = new processDet($this->db);
                        $ligne->fetch($res->id);
                        $this->detail[$res->id] = $ligne;
                    }
                    self::$cache[$this->id]['lignes'] = $this->detail;
                }
                global $user;
                $this->getRights($user);
                require_once(DOL_DOCUMENT_ROOT . "/user/class/usergroup.class.php");
                $usergroup = new UserGroup($this->db);
                $groupslist = $usergroup->listGroupsForUser($user->id);

                foreach ($groupslist as $key => $val)
                    $this->getGrpRights($val);
                return ($this->id);
            } else {
                $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
                return -1;
            }
        } else {
            return -1;
        }
    }

    public function activate() {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process SET fk_statut = 1 WHERE id =" . $this->id;
        $sql = $this->db->query($requete);
        self::majTabsProcess($this->db);
        return($sql);
    }

    public function unactivate() {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process SET fk_statut = 0 WHERE id =" . $this->id;
        $sql = $this->db->query($requete);
        self::majTabsProcess($this->db);
        return($sql);
    }

    public function add() {
        $this->label = addslashes($this->label);
        $this->description = addslashes($this->description);
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process WHERE label = '" . $this->label . "'";
        $sql = $this->db->query($requete);
        if ($this->db->num_rows($sql) > 0) {
            $this->error = "Un process du m&ecirc;me non existe d&eacute;j&agrave;";
            return -2;
        }
        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process (label,description) VALUES ('" . $this->label . "','" . $this->description . "')";
        $sql = $this->db->query($requete);
        if ($sql) {
            return ($this->db->last_insert_id("" . MAIN_DB_PREFIX . "Synopsis_Process"));
        } else {
            $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
            return -1;
        }
    }

    public function create() {
        return $this->add();
    }

    public function cloneProcess() {
        $this->db->begin();
        if (preg_match("/-clone\(([0-9]*)\)$/", $this->label, $arr)) {
            $cnt = $arr[1] + 1;
            $label = preg_replace("/\(([0-9]*)\)$/", "", $this->label) . '(' . $cnt . ')';
            while ($this->testLabel($label) != true) {
                $cnt++;
                $label = preg_replace("/\(([0-9]*)\)$/", "", $this->label) . '(' . $cnt . ')';
            }
            $this->label = $label;
        } else {
            $label = $this->label . '-clone(1)';
            if ($this->testLabel($label) != true) {
                $cnt = 2;
                $label = preg_replace("/\(([0-9]*)\)$/", "", $this->label) . '-clone(' . $cnt . ')';
                while ($this->testLabel($label) != true) {
                    $cnt++;
                    $label = preg_replace("/\(([0-9]*)\)$/", "", $this->label) . '-clone(' . $cnt . ')';
                }
                $this->label = $label;
            } else {
                $this->label = $label;
            }
        }
        $oldId = $this->id;
        $newId = $this->create();
        $tmp = new Process($this->db);
        $tmp->fetch($newId);
        $arrCloneData = array(
            "label" => "label",
            "description" => "description",
            "pretraitement" => "pretraitement",
            "posttraitement" => "posttraitement",
            "bloquant" => "bloquant",
            "PROCESS_ADDON" => "PROCESS_ADDON",
            "PROCESS_MASK" => "PROCESS_MASK",
            "ADDON_PDF" => "PROCESS_MASK",
            "trigger_refid" => "trigger_refid",
            "formulaire_refid" => "formulaire_refid",
        );
        foreach ($this as $key => $val) {
            if (!in_array($key, $arrCloneData))
                continue;
            $tmp->$key = $val;
        }
        $resUpdt = $tmp->update();

        //Clone Droits
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights WHERE process_refid = " . $oldId;
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql)) {
            $user_refid = $res->user_refid;
            $right_refid = $res->right_refid;
            $valeur = $res->valeur;
            $requete1 = " INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_rights (
                                      process_refid,
                                      user_refid,
                                      right_refid,
                                      valeur)
                             VALUES ( " . $newId . " ,
                                      " . $user_refid . " ,
                                      " . $right_refid . " ,
                                      " . $valeur . " )";
            $sql1 = $this->db->query($requete1);
        }


        if ($newId > 0 && $resUpdt > 0) {
            $this->db->commit();
            return($newId);
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    public function delete($id) {
        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Process WHERE id = " . $id;
        $sql = $this->db->query($requete);
        if ($sql) {
            return 1;
        } else {
            $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
            return -1;
        }
    }

    private function testLabel($label) {
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process WHERE label = '" . $label . "' AND id <> " . $this->id;
        $sql = $this->db->query($requete);
        if ($this->db->num_rows($sql) > 0) {
            return false;
        } else {
            return true;
        }
    }

    public function update() {
        $this->label = addslashes($this->label);
        $this->description = addslashes($this->description);
        $this->pretraitement = addslashes($this->pretraitement);
        $this->posttraitement = addslashes($this->posttraitement);
        $this->reviseAction = addslashes($this->reviseAction);
        $this->validAction = addslashes($this->validAction);
        $this->askValidAction = addslashes($this->askValidAction);
        $this->bloquant = ($this->bloquant == 'On' || $this->bloquant == 'ON' || $this->bloquant == 'on' ? 1 : 0);
        $this->PROCESS_ADDON = addslashes($this->PROCESS_ADDON);
        $this->PROCESS_MASK = addslashes($this->PROCESS_MASK);
        $this->ADDON_PDF = addslashes($this->ADDON_PDF);
        $this->trigger_refid = addslashes($this->trigger_refid);
        $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_trigger as t,
                           " . MAIN_DB_PREFIX . "Synopsis_Process_type_element_trigger as te
                     WHERE te.trigger_refid = t.id
                       AND te.trigger_refid = " . $this->trigger_refid;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $this->typeElement_refid = $res->element_refid;

        $this->formulaire_refid = addslashes($this->formulaire_refid);
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process WHERE label = '" . $this->label . "' AND id <> " . $this->id;
        $sql = $this->db->query($requete);
        if ($this->db->num_rows($sql) > 0) {
            $this->error = "Un process du m&ecirc;me non existe d&eacute;j&agrave;";
            return -2;
        }

        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process
                       SET label = '" . $this->label . "',
                           description = '" . $this->description . "',
                           pretraitement = '" . $this->pretraitement . "',
                           reviseAction = '" . $this->reviseAction . "',
                           validAction = '" . $this->validAction . "',
                           askValidAction = '" . $this->askValidAction . "',
                           posttraitement = '" . $this->posttraitement . "',
                           bloquant = " . $this->bloquant . ",
                           PROCESS_ADDON = '" . $this->PROCESS_ADDON . "',
                           PROCESS_MASK = '" . $this->PROCESS_MASK . "',
                           ADDON_PDF = '" . $this->ADDON_PDF . "',
                           formulaire_refid = " . $this->formulaire_refid . ",
                           typeElement_refid = " . $this->typeElement_refid . ",
                           trigger_refid = " . $this->trigger_refid . "
                     WHERE id = " . $this->id;
        $sql = $this->db->query($requete);
        if ($sql) {
            self::majTabsProcess($this->db);
            return ($this->id);
        } else {
            $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
            return (-1);
        }
    }

    public function getNomUrl($withpicto = 0, $option = 0) {
        global $langs;

        $result = '';
        $urlOption = '';

        $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/Synopsis_Process/processBuilder.php?id=' . $this->id . '">';
        if ($option == 6)
            $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/Synopsis_Process/processBuilder.php?id=' . $this->id . '">';
        $lienfin = '</a>';

        $picto = 'process@Synopsis_Process';
        $label = $langs->trans("process") . ': ' . $this->label;

        if ($withpicto)
            $result.=($lien . img_object($label, $picto, false, false, false, true) . $lienfin);
        if ($withpicto && $option == 6)
            $result.=($lien . img_object($label, $picto) . $lienfin);
        if ($withpicto && $withpicto != 2)
            $result.=' ';
        $result.=$lien . $this->label . $lienfin;
        return $result;
    }

    public function getLibStatut($mode) {
        return $this->LibStatut($this->fk_statut, $mode);
    }

    private function LibStatut($status, $mode = 0) {
        global $langs;
        $langs->load('process@Synopsis_Process');
        if ($mode == 0) {
            if ($status == 0)
                return $langs->trans('processInactif');
            if ($status == 1)
                return $langs->trans('processActif');
        }
        if ($mode == 1) {
            if ($status == 0)
                return $langs->trans('processInactif');
            if ($status == 1)
                return $langs->trans('processActif');
        }
        if ($mode == 2) {
            if ($status == 0)
                return img_picto($langs->trans('processInactif'), 'statut5') . ' ' . $langs->trans('processInactif');
            if ($status == 1)
                return img_picto($langs->trans('processActif'), 'statut4') . ' ' . $langs->trans('processActif');
        }
        if ($mode == 3) {
            if ($status == 0)
                return img_picto($langs->trans('processInactif'), 'statut5');
            if ($status == 1)
                return img_picto($langs->trans('processActif'), 'statut4');
        }
        if ($mode == 4) {
            if ($status == 0)
                return img_picto($langs->trans('processInactif'), 'statut5') . ' ' . $langs->trans('processInactif');
            if ($status == 1)
                return img_picto($langs->trans('processActif'), 'statut4') . ' ' . $langs->trans('processActif');
        }
        if ($mode == 5) {
            if ($status == 0)
                return $langs->trans('processInactif') . ' ' . img_picto($langs->trans('processInactif'), 'statut5');
            if ($status == 1)
                return $langs->trans('processActif') . ' ' . img_picto($langs->trans('processActif'), 'statut4');
        }
        return $langs->trans('Unknown');
    }

    public function setRevisionModel($rev = false) {
        $requete = 'UPDATE ' . MAIN_DB_PREFIX . 'Synopsis_Process SET revision_model_refid = NULL WHERE id = ' . $this->id;
        if ($rev > 0) {
            $requete = 'UPDATE ' . MAIN_DB_PREFIX . 'Synopsis_Process SET revision_model_refid = ' . $rev . ' WHERE id = ' . $this->id;
        }
        $sql = $this->db->query($requete);
        if ($sql)
            return 1;
        else
            return -1;
    }

}

class processDet extends process {

    public $db;
    public $id;
    public $process_refid;
    public $ref;
    public $fk_statut;
    public $valeur;
    public $process = false;
    public $revision;
    public $element_refid;
    public $validation_number;
    public $date_create;
    public $date_modify;
    public $orig_ref;
    public $isRevised = false;
    public $element = false;
    private static $cacheProcess = array();

    public function processDet($DB) {
        $this->db = $DB;
    }

    public function ask_valid() {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Processdet
                       SET fk_statut = 999
                     WHERE id = " . $this->id;
        $sql = $this->db->query($requete);
        if ($sql)
            return 1;
        else
            return -1;
    }

    public function add() {
        //$this->ref=
        $requete = " INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Processdet (element_refid,process_refid,date_create,ref) VALUES (" . $this->element_refid . "," . $this->process_refid . ",now(),'" . $this->ref . "')";
        $sql = $this->db->query($requete);
        if ($sql) {
            return ($this->db->last_insert_id("" . MAIN_DB_PREFIX . "Synopsis_Processdet"));
        } else {
            $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
            return -1;
        }
    }

    public function create() {
        return ($this->add());
    }

    public function update() {
        //$this->ref=
        $requete = " UPDATE " . MAIN_DB_PREFIX . "Synopsis_Processdet
                        SET element_refid=" . $this->element_refid . ",
                            process_refid=" . $this->process_refid . "
                        WHERE id=" . $this->id;
        $sql = $this->db->query($requete);
        if ($sql) {
            return ($this->db->last_insert_id("" . MAIN_DB_PREFIX . "Synopsis_Processdet"));
        } else {
            $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
            return -1;
        }
    }

    public function validate($valeur = false) {
        //1 Si droit de valider general => valider ou droit de valider le process
        global $user;
        $statutAllOk = true;
        $statutRefuser = false;

        if ($this->id > 0 && $this->process_refid) {
            $tmp = 'process' . $this->process_refid;

            if ($user->rights->process->valider || $user->rights->process_user->$tmp->valider) {
                if ($valeur == 1)
                    $statutAllOk = true;
                else {
                    $requete = "SELECT v.valeur
                              FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def as d
                         LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Processdet_validation as v ON v.processdet_refid = " . $this->id . "  AND v.validation_type_refid = d.id AND v.validation_number=" . $this->validation_number . "
                             WHERE isValidationRight = 1
                               AND active = 1
                               AND isValidationForAll <> 1";
//                die($requete);
                    $sql = $this->db->query($requete);
                    $statutAllOk = true;
                    while ($res = $this->db->fetch_object($sql)) {
                        if ($res->valeur . "x" == "0x") {
                            $statutRefuser = true;
                        } else if ($res->valeur . "x" == "1x") {
                            $statutAllOk = false;
                        } else {
                            $statutRefuser = true;
                            $statutAllOk = false;
                        }
                    }
                }
            } else {
                $requete = "SELECT v.valeur
                              FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def as d
                         LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Processdet_validation as v ON v.processdet_refid = " . $this->id . "  AND v.validation_type_refid = d.id AND v.validation_number=" . $this->validation_number . "
                             WHERE isValidationRight = 1
                               AND active = 1
                               AND isValidationForAll <> 1";
                $sql = $this->db->query($requete);
                while ($res = $this->db->fetch_object($sql)) {
                    if ($res->valeur . "x" == "0x") {
                        $statutRefuser = true;
                    } else if ($res->valeur . "x" == "1x") {
                        
                    } else {
                        $statutAllOk = false;
                    }
                }
            }
            //2 Sinon cherche si toute les validations sont OK
            //  Za) Si oui => valid
            //  2b) Si non => continue
            //Valider
            $requete = false;
            if ($statutAllOk) {
                $this->fetch_process();
                $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Processdet SET fk_statut = 3 WHERE id = " . $this->id;
                $eval = $this->process->validAction;
                $element_id = $this->element_refid;
                if ($eval . "x" != "x")
                    eval($eval);
            }
            elseif ($statutRefuser) {
                //TODO si Revision => nouvelle révision
                //Sinon :> retour brouillon
                $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Processdet SET fk_statut = 0, validation_number=validation_number+1 WHERE id = " . $this->id;
            } else {
                return (1);
            }
            if ($requete)
                $sql = $this->db->query($requete);
            return (2);
        } else {
            return -1;
        }
    }

    public function getNomUrl($withpicto = 0, $option = 0) {
        global $langs;

        $result = '';
        $urlOption = '';

        $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/Synopsis_Process/form.php?processDetId=' . $this->id . '&process_id=' . $this->process_refid . '">';
        if ($option == 6)
            $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/Synopsis_Process/form.php?processDetId=' . $this->id . '&process_id=' . $this->process_refid . '">';

        $lienfin = '</a>';

        $picto = 'process@Synopsis_Process';
        $label = $langs->trans("process") . ': ' . $this->ref;

        if ($withpicto)
            $result.=($lien . img_object($label, $picto, false, false, false, true) . $lienfin);
        if ($withpicto && $option == 6)
            $result.=($lien . img_object($label, $picto) . $lienfin);
        if ($withpicto && $withpicto != 2)
            $result.=' ';
        $result.=$lien . $this->ref . $lienfin;
        return $result;
    }

    public function set_revised() {
        $this->db->begin();
        $rev = ($this->revision > 0 ? $this->revision : 0);
        //2 Update
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Processdet SET fk_statut = 9 WHERE id = " . $this->id;
        $sql = $this->db->query($requete);
        $oldId = $this->id;
        $this->fetch($oldId);
        $valeursArr = $this->valeur->valeur;
        $valeurToModel = $this->valeur->valeurToModel;
        $this->fetch_process();
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_revision_model WHERE id =" . $this->process->revision_model_refid;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $objTmp = $res->phpClass;
        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Revision/modele/" . $objTmp . ".class.php");
        $obj = new $objTmp($this->db);
        $newRev = $obj->convert_revision($rev);

        //1 Clone
        $origref = ($this->orig_ref . "x" != "x" ? $this->orig_ref : $this->ref);
        $this->ref = $origref . "-" . $newRev;
        $newId = $this->add();
        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Processdet_active
                                (type_refid,process_refid,element_refid,processdet_refid)
                         VALUES (" . $this->process->typeElement_refid . "," . $this->process->id . "," . $this->element_refid . "," . $newId . ")";
        $sqlactive = $this->db->query($requete);
        $rev++;
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Processdet SET revision = " . $rev . ", orig_ref ='" . $origref . "' WHERE id = " . $newId;
        $sql1 = $this->db->query($requete);
        //TODO Clone Value
        $okVal = true;
        foreach ($valeursArr as $key => $val) {

            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Processdet_value
                                    (nom, valeur,processDet_refid,model_refid)
                             VALUES ('" . $val->nom . "','" . addslashes($val->valeur) . "'," . $newId . "," . $valeurToModel[$val->valeur] . ")";
            $sqlVal = $this->db->query($requete);
            if ($okVal && $sqlVal) {
                
            } else {
                $okVal = false;
                break;
            }
        }

//exit;
        if ($sql && $newId > 0 && $sql1 && $sqlactive && $sqlVal) {
            $this->db->commit();
            return $newId;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    public function getPrevNextRev() {
        $nextRev = false;
        $prevRev = false;
        $seek_ref = ($this->orig_ref . "x" != "x" ? $this->orig_ref : $this->ref);
        $currentRev = ($this->revision > 0 ? $this->revision : 0);
        if ($currentRev == 0) {
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet WHERE orig_ref = '" . $seek_ref . "' AND revision = 1";
            $sql = $this->db->query($requete);
            $res = $this->db->fetch_object($sql);
            $nextRev = $res->id;
        } else if ($currentRev == 1) {
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet WHERE ref = '" . $seek_ref . "'";
            $sql = $this->db->query($requete);

            $res = $this->db->fetch_object($sql);
            $prevRev = $res->id;
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet WHERE orig_ref = '" . $seek_ref . "' AND revision = " . intval($currentRev + 1);

            $sql = $this->db->query($requete);
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $nextRev = $res->id;
            }
        } else {
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet WHERE orig_ref = '" . $seek_ref . "' AND revision = " . intval($currentRev + 1);
            $sql = $this->db->query($requete);
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $nextRev = $res->id;
            }
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet WHERE orig_ref = '" . $seek_ref . "' AND revision = " . intval($currentRev - 1);
            $sql = $this->db->query($requete);
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $prevRev = $res->id;
            }
        }
        return (array('next' => $nextRev, 'prev' => $prevRev));
    }

    public function unvalidate() {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Processdet SET fk_statut = 0, validation_number = validation_number + 1 WHERE id = " . $this->id;
        $sql = $this->db->query($requete);
        if ($sql) {
            return 1;
        } else {
            return -1;
        }
    }

    public function fetch_process() {
        if ($this->process_refid > 0) {
            if (isset(self::$cacheProcess[$this->process_refid])) {
                $this->process = self::$cacheProcess[$this->process_refid];
            } else {
                $tmpProcess = new process($this->db);
                $tmpProcess->fetch($this->process_refid);
                $this->process = $tmpProcess;
                self::$cacheProcess[$this->process_refid] = $this->process;
            }
        }
    }

    public function fetch_element() {
        if (!$this->process) {
            $this->fetch_process();
        }
        if ($this->process) {
            $this->element = $process->typeElement->fetch_element($this->element_refid);
        }
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            $this->id = $id;
            $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet
                     WHERE id = " . $id;
            $sql = $this->db->query($requete);
            if ($sql && $this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $this->ref = $res->ref;
                $this->fk_statut = $res->fk_statut;
                $this->statut = $res->fk_statut;
                $this->process_refid = $res->process_refid;
                $this->element_refid = $res->element_refid;
                $this->validation_number = $res->validation_number;
                $this->date_create = $res->date_create;
                $this->revision = $res->revision;
                if ($this->statut == 9 || $res->revision > 0)
                    $this->isRevised = true;
                $this->date_modify = $res->date_modify;
                $this->orig_ref = $res->orig_ref;
                $val = new processDetValue($this->db);
                $val->fetch_by_processDet($this->id);
                $this->valeur = $val;
                return($this->id);
            } else {
                return(-1);
            }
        } else {
            return(-1);
        }
    }

    public function getLibStatut($mode) {
        return $this->LibStatut($this->fk_statut, $mode);
    }

    private function LibStatut($status, $mode = 0) {
        global $langs;
        $langs->load('process@Synopsis_Process');
        if ($mode == 0) {
            if ($status == 0)
                return $langs->trans('processDetBrouillon');
            if ($status == 1)
                return $langs->trans('processDetSubmitValid');
            if ($status == 2)
                return $langs->trans('processDetValiderPartiel');
            if ($status == 3)
                return $langs->trans('processDetValider');
            if ($status == 5)
                return $langs->trans('processDetCloturer');
            if ($status == 9)
                return $langs->trans('processDetReviser');
            if ($status == 99)
                return $langs->trans('processDetAbandonner');
            if ($status == 999)
                return $langs->trans('processDetAskValid');
        }
        if ($mode == 1) {
            if ($status == 0)
                return $langs->trans('processDetBrouillon');
            if ($status == 1)
                return $langs->trans('processDetSubmitValid');
            if ($status == 2)
                return $langs->trans('processDetValiderPartiel');
            if ($status == 3)
                return $langs->trans('processDetValider');
            if ($status == 5)
                return $langs->trans('processDetCloturer');
            if ($status == 9)
                return $langs->trans('processDetReviser');
            if ($status == 99)
                return $langs->trans('processDetAbandonner');
            if ($status == 999)
                return $langs->trans('processDetAskValid');
        }
        if ($mode == 2) {
            if ($status == 0)
                return img_picto($langs->trans('processDetBrouillon'), 'statut0') . ' ' . $langs->trans('processDetBrouillon');
            if ($status == 1)
                return img_picto($langs->trans('processDetSubmitValid'), 'statut1') . ' ' . $langs->trans('processDetSubmitValid');
            if ($status == 2)
                return img_picto($langs->trans('processDetValiderPartiel'), 'statut3') . ' ' . $langs->trans('processDetValiderPartiel');
            if ($status == 3)
                return img_picto($langs->trans('processDetValider'), 'statut4') . ' ' . $langs->trans('processDetValider');
            if ($status == 5)
                return img_picto($langs->trans('processDetCloturer'), 'statut6') . ' ' . $langs->trans('processDetCloturer');
            if ($status == 9)
                return img_picto($langs->trans('processDetReviser'), 'statut2') . ' ' . $langs->trans('processDetReviser');
            if ($status == 99)
                return img_picto($langs->trans('processDetAbandonner'), 'statut5') . ' ' . $langs->trans('processDetAbandonner');
            if ($status == 999)
                return img_picto($langs->trans('processDetAskValid'), 'statut8', 'style="vertical-align:middle;"') . ' ' . $langs->trans('processDetAskValid');
        }
        if ($mode == 3) {
            if ($status == 0)
                return img_picto($langs->trans('processDetBrouillon'), 'statut0');
            if ($status == 1)
                return img_picto($langs->trans('processDetSubmitValid'), 'statut1');
            if ($status == 2)
                return img_picto($langs->trans('processDetValiderPartiel'), 'statut3');
            if ($status == 3)
                return img_picto($langs->trans('processDetValider'), 'statut4');
            if ($status == 5)
                return img_picto($langs->trans('processDetCloturer'), 'statut6');
            if ($status == 9)
                return img_picto($langs->trans('processDetReviser'), 'statut2');
            if ($status == 99)
                return img_picto($langs->trans('processDetAbandonner'), 'statut5');
            if ($status == 999)
                return img_picto($langs->trans('processDetAskValid'), 'statut8', 'style="vertical-align:middle;"');
        }
        if ($mode == 4) {
            if ($status == 0)
                return img_picto($langs->trans('processDetBrouillon'), 'statut0') . ' ' . $langs->trans('processDetBrouillon');
            if ($status == 1)
                return img_picto($langs->trans('processDetSubmitValid'), 'statut1') . ' ' . $langs->trans('processDetSubmitValid');
            if ($status == 2)
                return img_picto($langs->trans('processDetValiderPartiel'), 'statut3') . ' ' . $langs->trans('processDetValiderPartiel');
            if ($status == 3)
                return img_picto($langs->trans('processDetValider'), 'statut4') . ' ' . $langs->trans('processDetValider');
            if ($status == 5)
                return img_picto($langs->trans('processDetCloturer'), 'statut6') . ' ' . $langs->trans('processDetCloturer');
            if ($status == 9)
                return img_picto($langs->trans('processDetReviser'), 'statut2') . ' ' . $langs->trans('processDetReviser');
            if ($status == 99)
                return img_picto($langs->trans('processDetAbandonner'), 'statut5') . ' ' . $langs->trans('processDetAbandonner');
            if ($status == 999)
                return img_picto($langs->trans('processDetAskValid'), 'statut8', 'style="vertical-align:middle;"') . ' ' . $langs->trans('processDetAskValid');
        }
        if ($mode == 5) {
            if ($status == 0)
                return $langs->trans('processDetBrouillon') . ' ' . img_picto($langs->trans('processDetBrouillon'), 'statut0');
            if ($status == 1)
                return $langs->trans('processDetSubmitValid') . ' ' . img_picto($langs->trans('processDetSubmitValid'), 'statut1');
            if ($status == 2)
                return $langs->trans('processDetValiderPartiel') . ' ' . img_picto($langs->trans('processDetValiderPartiel'), 'statut3');
            if ($status == 3)
                return $langs->trans('processDetValider') . ' ' . img_picto($langs->trans('processDetValider'), 'statut4');
            if ($status == 5)
                return $langs->trans('processDetCloturer') . ' ' . img_picto($langs->trans('processDetCloturer'), 'statut6');
            if ($status == 9)
                return $langs->trans('processDetReviser') . ' ' . img_picto($langs->trans('processDetReviser'), 'statut2');
            if ($status == 99)
                return $langs->trans('processDetAbandonner') . ' ' . img_picto($langs->trans('processDetAbandonner'), 'statut5');
            if ($status == 999)
                return $langs->trans('processDetAskValid') . ' ' . img_picto($langs->trans('processDetAskValid'), 'statut8', 'style="vertical-align:middle;"');
        }
        return $langs->trans('Unknown');
    }

}

class processDetValue extends processDet {

    public $db;
    public $processDet_refid;
    public $nom;
    public $valeurByModel = array();
    public $valeurToModel;
    public $valeur = array();

    public function processDetValue($DB) {
        $this->db = $DB;
    }

    public function fetch_by_processDet($processDet_refid) {
        $requete = "SELECT nom, valeur, model_refid
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet_value
                     WHERE processDet_refid=" . $processDet_refid;
        $sql = $this->db->query($requete);
        if ($sql) {
            while ($res = $this->db->fetch_object($sql)) {
                $this->valeur[$res->nom] = $res;
                $this->valeurByModel[$res->model_refid] = $res;
                $this->valeurToModel[$res->valeur] = $res->model_refid;
            }
        } else
            die("erreur 456789412548");
    }

}

class process_trigger extends process {

    public $db;
    public $code;

    public function process_trigger($DB) {
        $this->db = $DB;
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            $this->id = $id;
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_trigger WHERE id = " . $id;
            $sql = $this->db->query($requete);
//        print $id."<br/>";
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $this->code = $res->code;
            }
        } else {
            return -1;
        }
    }

}

class process_element_type extends process {

    public $db;
    public $type;
    public $label;
    public $element;
    public $id;
    public $ficheUrl;
    public $_GET_id;
    public $classFile;

    public function process_element_type($DB) {
        $this->db = $DB;
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            $this->id = $id;
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_type_element WHERE id = " . $id;
            $sql = $this->db->query($requete);
//        print $id."<br/>";
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $this->label = $res->label;
                $this->type = $res->type;
                $this->ficheUrl = $res->ficheUrl;
                $this->_GET_id = $res->_GET_id;
                $this->classFile = $res->classFile;
            }
        } else {
            return -1;
        }
    }

    public function fetch_element($processDet_elementId) {
        require_once(DOL_DOCUMENT_ROOT . "/" . $this->classFile);
        $objName = $this->type;
        $tmpObj = new $objName($this->db);
        $tmpObj->fetch($processDet_elementId);
        $this->element = $tmpObj;
        return $tmpObj;
    }

    public function getNomUrl_byProcessDet($processDet_elementId, $withPicto = 1) {
        require_once(DOL_DOCUMENT_ROOT . "/" . $this->classFile);
        $objName = $this->type;
        $tmpObj = new $objName($this->db);
        $tmpObj->fetch($processDet_elementId);
        $this->element = $tmpObj;
        return $tmpObj->getNomUrl($withPicto);
    }

}

class formulaire extends process {

    public $db;
    public $description;
    public $label;
    public $error;
    public $id;
    public $model_refid;
    public $model;
    public $statut;

    public function formulaire($DB) {
        $this->db = $DB;
    }

    public function add() {
        $description = addslashes($this->description);
        $label = addslashes($this->label);
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form WHERE label LIKE '" . $label . "'";
        $sql = $this->db->query($requete);
        if ($this->db->num_rows($sql) > 0) {
            $this->error = "Un formulaire du m&ecirc;me non existe d&eacute;j&agrave;";
            return -2;
        } else {
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form (label, description, fk_statut) VALUES ('" . $label . "','" . $description . "',0)";
            $sql = $this->db->query($requete);
            if ($sql) {
                return ($this->db->last_insert_id("" . MAIN_DB_PREFIX . "Synopsis_Process_form"));
            } else {
                $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
                return -1;
            }
        }
    }

    public function create() {
        return ($this->add());
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            $requete = "SELECT label,
                           description,
                           fk_statut
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form
                     WHERE id = " . $id;
            $sql = $this->db->query($requete);
            $this->id = $id;
            $ok = false;
            if ($this->db->num_rows($sql) > 0) {
                $ok = true;
                $res = $this->db->fetch_object($sql);
                $this->label = $res->label;
                $this->description = $res->description;
                $this->statut = $res->fk_statut;
            }

            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_model WHERE form_refid = " . $id . " ORDER BY rang ";
            $sql = $this->db->query($requete);
            $this->lignes = array();
            if ($this->db->num_rows($sql) > 0) {
                while ($res = $this->db->fetch_object($sql)) {
                    $member = new formulaireModel($this->db);
                    $member->fetch($res->id);
                    $this->lignes[] = $member;
                }
            }
            if ($ok) {
                return($this->id);
            } else {
                $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
                return(-1);
            }
        } else {
            return -1;
        }
    }

    public function toggleActive() {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process_form SET fk_statut = 1 WHERE id = " . $this->id;
        if ($this->statut == 1)
            $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process_form SET fk_statut = 0 WHERE id = " . $this->id;
        $sql = $this->db->query($requete);
//var_dump($requete);
        if ($sql)
            return 1;
        else {
            $this->error = $this->db->lasterrno . " " . $this->db->lastqqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
            return (-1);
        }
    }

    public function getNomUrl($withpicto = 0, $option = 0) {
        global $langs;

        $result = '';
        $urlOption = '';

        $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/Synopsis_Process/formBuilder.php?id=' . $this->id . '">';
        if ($option == 6)
            $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/Synopsis_Process/formBuilder.php?id=' . $this->id . '">';
        $lienfin = '</a>';

        $picto = 'formulaire@Synopsis_Process';
        $label = $langs->trans("Formulaire") . ': ' . $this->label;

        if ($withpicto)
            $result.=($lien . img_object($label, $picto, false, false) . $lienfin);
        if ($withpicto && $option == 6)
            $result.=($lien . img_object($label, $picto, false, false, "ABSMIDDLE", true) . $lienfin);
        if ($withpicto && $withpicto != 2)
            $result.=' ';
        $result.=$lien . $this->label . $lienfin;
        return $result;
    }

    private function testLabel($label) {
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form WHERE label = '" . $label . "' AND id <> " . $this->id;
        $sql = $this->db->query($requete);
        if ($this->db->num_rows($sql) > 0) {
            return false;
        } else {
            return true;
        }
    }

    public function cloneForm() {
        $this->db->begin();
        $tmp = new formulaire($this->db);
        //1 Clone le formulaire de base
        $tmp->description = $this->description;
        $tmp->label = $this->label; //TODO

        if (preg_match("/-clone\(([0-9]*)\)$/", $tmp->label, $arr)) {
            $cnt = $arr[1] + 1;
            $label = preg_replace("/\(([0-9]*)\)$/", "", $tmp->label) . '(' . $cnt . ')';
            while ($this->testLabel($label) != true) {
                $cnt++;
                $label = preg_replace("/\(([0-9]*)\)$/", "", $tmp->label) . '(' . $cnt . ')';
            }
            $tmp->label = $label;
        } else {
            $label = $tmp->label . '-clone(1)';
            if ($this->testLabel($label) != true) {
                $cnt = 2;
                $label = preg_replace("/\(([0-9]*)\)$/", "", $tmp->label) . '-clone(' . $cnt . ')';
                while ($this->testLabel($label) != true) {
                    $cnt++;
                    $label = preg_replace("/\(([0-9]*)\)$/", "", $tmp->label) . '-clone(' . $cnt . ')';
                }
                $tmp->label = $label;
            } else {
                $tmp->label = $label;
            }
        }
        $oldId = $this->id;
        $newId = $tmp->create();
        $step0 = false;
        if ($newId > 0)
            $step0 = true;
        //2 Clone les éléments => " . MAIN_DB_PREFIX . "Synopsis_Process_form_model
        $requete1 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_model WHERE form_refid = " . $oldId;
        $sql1 = $this->db->query($requete1);
        $step1 = true;
        $step2 = true;
        $step3 = true;
        $step6 = true;
        $step4 = true;
        $step5 = true;
        while ($res1 = $this->db->fetch_object($sql1)) {
            $oldModel = $res1->id;
            $type_refid = $res1->type_refid;
            $label = ($res->label . "x" != "x" ? "'" . $res1->label . "'" : "NULL");
            $description = ($res1->description . "x" != "x" ? "'" . $res1->description . "'" : "NULL");
            $dflt = ($res1->dflt . "x" != "x" ? "'" . $res1->dflt . "'" : "NULL");
            $src_refid = ($res1->src_refid > 0 ? $res1->src_refid : "NULL");
            $form_refid = $newId;
            $rang = ($res1->rang > 0 ? $res1->rang : "1");
            $rights = ($res1->rights . "x" != "x" ? $res1->rights : "");
            $requeteIns = " INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_model (type_refid,
                                        label,
                                        description,
                                        dflt,
                                        src_refid,
                                        form_refid,
                                        rang,
                                        rights)
                                VALUES ( " . $type_refid . " ,
                                         " . $label . " ,
                                         " . $description . " ,
                                         " . $dflt . " ,
                                         " . $src_refid . " ,
                                         " . $form_refid . " ,
                                         " . $rang . " ,
                                         '" . $rights . "' )";
            $sqlIns = $this->db->query($requeteIns);
            if (!($sqlIns && $step1)) {
                $step1 = false;
            }
            $newModel = $this->db->last_insert_id('" . MAIN_DB_PREFIX . "Synopsis_Process_form_model');
            if ($newModel > 0) {
                //3 Clone les parametres => " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_prop & " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_prop_value
                $requete2 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_prop_value WHERE model_refid = " . $oldModel;
                $sql2 = $this->db->query($requete2);
                while ($res2 = $this->db->fetch_object($sql2)) {
                    $prop_refid = $res2->prop_refid;
                    $model_refid = $newModel;
                    $valeur = ($res2->valeur . "x" == "x" ? "NULL" : "'" . $res2->valeur . "'");
                    $requeteIns2 = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_prop_value (
                                                 prop_refid,
                                                 model_refid,
                                                 valeur)
                                        VALUES ( " . $prop_refid . " ,
                                                 " . $model_refid . " ,
                                                 " . $valeur . " )\n";
                    $sqlIns2 = $this->db->query($requeteIns2);
                    if (!($sqlIns2 && $step2)) {
                        $step2 = false;
                    }
                }
                //4 Clone les styles => " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_style & " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_style_value
                $requete3 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_style_value WHERE model_refid = " . $oldModel;
                $sql3 = $this->db->query($requete3);
                while ($res3 = $this->db->fetch_object($sql3)) {
                    $style_refid = $res3->style_refid;
                    $valeur = ($res3->valeur . "x" == "x" ? "NULL" : "'" . $res3->valeur . "'");
                    $model_refid = $newModel;
                    $requeteIns3 = " INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_style_value (
                                                  style_refid,
                                                  valeur,
                                                  model_refid)
                                         VALUES ( " . $style_refid . " ,
                                                  " . $valeur . " ,
                                                  " . $model_refid . "
                                                )";
                    $sqlIns3 = $this->db->query($requeteIns3);
                    if (!($sqlIns3 && $step3)) {
                        $step3 = false;
                    }
                }
                //5 Clone la class CSS =>  " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_class_value
                $requete4 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_class_value WHERE model_refid = " . $oldModel;
                $sql4 = $this->db->query($requete4);
                while ($res4 = $this->db->fetch_object($sql4)) {
                    $valeur = ($res4->valeur . "x" == "x" ? "NULL" : "'" . $res4->valeur . "'");
                    $model_refid = $newModel;
                    $requeteIns4 = " INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_class_value (
                                                  model_refid,
                                                 valeur)
                                          VALUES ( " . $model_refid . " ,
                                                   " . $valeur . " )";
                    $sqlIns4 = $this->db->query($requeteIns4);
                    if (!($sqlIns4 && $step4)) {
                        $step4 = false;
                    }
                }
                //6 Clone les params de fonction => " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct_value
                $requete5 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct_value  WHERE model_refid = " . $oldModel;
                $sql5 = $this->db->query($requete5);
                while ($res5 = $this->db->fetch_object($sql5)) {
                    $fct_refid = ($res5->fct_refid . 'x' != 'x' ? $res5->fct_refid : "NULL");
                    $label = ($res5->label . "x" != 'x' ? $res5->label : "NULL");
                    $valeur = ($res5->valeur . "x" != "x" ? "'" . $res5->valeur . "'" : "NULL");
                    $model_refid = $newModel;
                    $requeteIns5 = " INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct_value (
                                                 fct_refid,
                                                 label,
                                                 model_refid,
                                                 valeur)
                                         VALUES ( " . $fct_refid . " ,
                                                 '" . $label . "' ,
                                                 " . $model_refid . " ,
                                                 " . $valeur . " )";
                    $sqlIns5 = $this->db->query($requeteIns5);
                    if (!($sqlIns5 && $step5)) {
                        $step5 = false;
                    }
                }
                //7 Clone les sources => " . MAIN_DB_PREFIX . "Synopsis_Process_form_src
                if ($src_refid > 0) {
                    $requete6 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_src WHERE id = " . $src_refid;
                    $sql6 = $this->db->query($requete6);
                    $res6 = $this->db->fetch_object($sql6);
                    $requete_refid = ($res6->requete_refid . "x" == 'x' ? "NULL" : $res6->requete_refid);
                    $fct_refid = ($res6->fct_refid . "x" == 'x' ? "NULL" : $res6->fct_refid);
                    $global_refid = ($res6->global_refid . "x" == 'x' ? "NULL" : $res6->global_refid);
                    $list_refid = ($res6->list_refid . "x" == 'x' ? "NULL" : $res6->list_refid);

                    $requeteIns6 = " INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_src (
                                                 requete_refid,
                                                 fct_refid,
                                                 global_refid,
                                                 list_refid)
                                          VALUES ( " . $requete_refid . " ,
                                                 " . $fct_refid . " ,
                                                 " . $global_refid . " ,
                                                 " . $list_refid . " )";
                    $sqlIns6 = $this->db->query($requeteIns6);
                    $newSrcId = $this->db->last_insert_id($sqlIns6);
                    if ($newSrcId > 0) {
                        $requeteUpdt6 = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process_form_model SET src_refid = " . $newSrcId . " WHERE id = " . $newModel;
                        $sqlUpdt6 = $this->db->query($requeteUpdt6);
                        if ($sqlUpdt6)
                            $step6 = true;
                        else
                            $step6 = false;
                    } else {
                        $step6 = false;
                    }
                }
            }
        }
//        var_dump($newId);
//        var_dump($step0);
//        var_dump($step1);
//        var_dump($step2);
//        var_dump($step3);
//        var_dump($step4);
//        var_dump($step5);
//        var_dump($step6);
//        exit;
        if ($step0 && $step1 && $step2 && $step3 && $step4 && $step5 && $step6 && $newId > 0) {
            $this->db->commit();
            return($newId);
        } else {
            $this->db->rollback();
            return(-1);
        }
    }

    public function getLibStatut($mode) {
        return $this->LibStatut($this->statut, $mode);
    }

    private function LibStatut($status, $mode = 0) {
        global $langs;
        $langs->load('process@Synopsis_Process');
        if ($mode == 0) {
            if ($status == 0)
                return $langs->trans('formInactif');
            if ($status == 1)
                return $langs->trans('formActif');
        }
        if ($mode == 1) {
            if ($status == 0)
                return $langs->trans('formInactif');
            if ($status == 1)
                return $langs->trans('formActif');
        }
        if ($mode == 2) {
            if ($status == 0)
                return img_picto($langs->trans('formInactif'), 'statut5') . ' ' . $langs->trans('formInactif');
            if ($status == 1)
                return img_picto($langs->trans('formActif'), 'statut4') . ' ' . $langs->trans('formActif');
        }
        if ($mode == 3) {
            if ($status == 0)
                return img_picto($langs->trans('formInactif'), 'statut5');
            if ($status == 1)
                return img_picto($langs->trans('formActif'), 'statut4');
        }
        if ($mode == 4) {
            if ($status == 0)
                return img_picto($langs->trans('formInactif'), 'statut5') . ' ' . $langs->trans('formInactif');
            if ($status == 1)
                return img_picto($langs->trans('formActif'), 'statut4') . ' ' . $langs->trans('formActif');
        }
        if ($mode == 5) {
            if ($status == 0)
                return $langs->trans('formInactif') . ' ' . img_picto($langs->trans('formInactif'), 'statut5');
            if ($status == 1)
                return $langs->trans('formActif') . ' ' . img_picto($langs->trans('formActif'), 'statut4');
        }
        return $langs->trans('Unknown');
    }

}

class formulaireModel extends formulaire {

    public $db;
    public $description;
    public $label;
    public $error;
    public $id;
    public $statut;
    public $type_refid;
    public $type;
    public $dflt;
    public $src;
    public $src_refid;
    public $form_refid;
    public $form;
    public $prop = array();
    public $style = array();
    public $class;
    public $rights;

    public function formulaireModel($DB) {
        $this->db = $DB;
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            $this->id = $id;
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_model WHERE id = " . $id;
            $sql = $this->db->query($requete);
            $this->id = $id;
//        print $id."<br/>";
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $this->rights = $res->rights;
                $this->label = $res->label;
                $this->description = $res->description;
                $this->type_refid = $res->type_refid;
                if ($this->type_refid > 0) {
                    $type = new formulaireType($this->db);
                    $type->fetch($this->type_refid);
                    $this->type = $type;
                }
                $this->dflt = $res->dflt;
                $this->src_refid = $res->src_refid;
                if ($this->src_refid > 0) {
                    $src = new formulaireSource($this->db);
                    $src->fetch($this->src_refid);
                    $this->src = $src;
                }
                $this->form_refid = $res->form_refid;
                $requete = "SELECT *
                          FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_style_value
                         WHERE model_refid = " . $id;
                $sql = $this->db->query($requete);
                $this->style = array();
                while ($res = $this->db->fetch_object($sql)) {
                    $style = new formulaireModelStyle($this->db);
                    $style->fetch($res->id);
                    $this->style[] = $style;
                }

                $requete = "SELECT *
                          FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_prop_value
                         WHERE model_refid = " . $id;
                $sql = $this->db->query($requete);
                $this->prop = array();
                while ($res = $this->db->fetch_object($sql)) {
                    $prop = new formulaireModelProp($this->db);
                    $prop->fetch($res->id);
                    $this->prop[] = $prop;
                }
//            require_once('Var_Dump.php');
//            Var_Dump::Display($this->prop);
                $requete = "SELECT *
                          FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_class_value
                         WHERE model_refid = " . $id;
                $sql = $this->db->query($requete);
                $this->cssClass = "";
                while ($res = $this->db->fetch_object($sql)) {
                    $class = new formulaireModelClass($this->db);
                    $class->fetch($res->id);
                    $this->cssClass = $class;
                }
            }
        } else {
            return -1;
        }
    }

    public function fetch_form() {
        if ($this->form_refid > 0) {
            $this->form = new formulaire($this->db);
            $this->form->fetch($this->form_refid);
        }
    }

}

class formulaireModelProp extends formulaireModel {

    public $db;
    public $valeur;
    public $element_name;
    public $model_refid;

    public function formulaireModelProp($DB) {
        $this->db = $DB;
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            $this->id = $id;
            $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_prop_value as v,
                           " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_prop as s
                     WHERE v.prop_refid = s.id
                       AND v.id = " . $id;
//print $requete;
            $sql = $this->db->query($requete);
            $this->id = $id;
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $this->valeur = $res->valeur;
                $this->model_refid = $res->model_refid;
                $this->element_name = $res->element_name;
            }
        } else {
            return -1;
        }
    }

}

class formulaireModelStyle extends formulaireModel {

    public $db;
    public $valeur;
    public $element_name;
    public $model_refid;

    public function formulaireModelStyle($DB) {
        $this->db = $DB;
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            $this->id = $id;
            $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_style_value as v,
                           " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_style as s
                     WHERE v.style_refid = s.id
                       AND v.id = " . $id;
            $sql = $this->db->query($requete);
            $this->id = $id;
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $this->valeur = $res->valeur;
                $this->model_refid = $res->model_refid;
                $this->element_name = $res->element_name;
            }
        } else {
            return -1;
        }
    }

}

class formulaireModelClass extends formulaireModel {

    public $db;
    public $valeur;

    public function formulaireModelClass($DB) {
        $this->db = $DB;
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            $this->id = $id;
            $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_class_value as v
                     WHERE v.id = " . $id;
            $sql = $this->db->query($requete);
            $this->id = $id;
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $this->valeur = $res->valeur;
            }
        } else {
            return -1;
        }
    }

}

class formulaireSource extends formulaire {

    public $db;
    public $description;
    public $label;
    public $error;
    public $id;
    public $code;
    public $cssClass;
    public $endNedded;
    public $htmlTag;
    public $htmlEndTag;
    public $type;
    public $uniqElem;

    public function formulaireSource($DB) {
        $this->db = $DB;
    }

    public function getValuePlus($id) {
        return $this->getValue($id);
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            $this->id = $id;
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_src WHERE id = " . $id;
            $sql = $this->db->query($requete);
            $this->id = $id;
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $this->requete_refid = $res->requete_refid;
                if ($this->requete_refid > 0) {
                    $requete = new requete($this->db);
                    $requete->fetch($this->requete_refid);
                    $this->requete = $requete;
                    $this->uniqElem = $requete;
                    $this->type = "r";
                }
                $this->fct_refid = $res->fct_refid;
                if ($this->fct_refid > 0) {
                    $fct = new fct($this->db);
                    $fct->fetch($this->fct_refid);
                    $this->fct = $fct;
                    $this->uniqElem = $fct;
                    $this->type = "f";
                }
                $this->global_refid = $res->global_refid;
                if ($this->global_refid > 0) {
                    $global = new globalvar($this->db);
                    $global->fetch($this->global_refid);
                    $this->globalvar = $global;
                    $this->uniqElem = $global;
                    $this->type = "g";
                }
                $this->list_refid = $res->list_refid;
                if ($this->list_refid > 0) {
                    $list = new listform($this->db);
                    $list->fetch($this->list_refid);
                    $this->list = $list;
                    $this->uniqElem = $list;
                    $this->type = "l";
                }
            }
        } else {
            return -1;
        }
    }

}

class listform extends formulaireSource {

    public $db;
    public $label;
    public $description;
    public $valuesArr = array();
    public $lignes = array();
    public $valueArr = array();

    public function listform($DB) {
        $this->db = $DB;
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            global $langs, $user, $mysoc, $societe, $conf;
            $conf->global->DOL_DOCUMENT_ROOT = DOL_DOCUMENT_ROOT;
            $conf->global->DOL_URL_ROOT = DOL_URL_ROOT;

            $this->id = $id;
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_list WHERE id=" . $id;
            $sql = $this->db->query($requete);
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $this->label = $res->label;
                $this->description = $res->description;
            }
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members WHERE list_refid = " . $id." ORDER BY id ASC";
            $sql = $this->db->query($requete);
            $this->lignes = array();
            if ($this->db->num_rows($sql) > 0) {
                while ($res = $this->db->fetch_object($sql)) {
                    $listMember = new listformmember($this->db);
                    $listMember->fetch($res->id);
                    $this->lignes[] = $listMember;
                }
            }
        } else {
            return -1;
        }
    }

    public function getValue($value) {
        global $langs, $user, $mysoc, $societe, $conf;
        $conf->global->DOL_DOCUMENT_ROOT = DOL_DOCUMENT_ROOT;
        $conf->global->DOL_URL_ROOT = DOL_URL_ROOT;
        if ($this->id > 0) {
            $this->valuesArr = array();
//            require_once('Var_Dump.php');
//            var_dump::display($this->lignes);
            foreach ($this->lignes as $key => $val) {
                if ($val->valeur == $value) {
                    $this->valuesArr[$val->valeur] = $val->label;
                } else if ($val->label == $value) {
                    $this->valuesArr[$val->label] = $val->valeur;
                }
            }
            return ($this->valuesArr);
        }
    }

    public function getValues() {
        global $langs, $user, $mysoc, $societe, $conf;
        $conf->global->DOL_DOCUMENT_ROOT = DOL_DOCUMENT_ROOT;
        $conf->global->DOL_URL_ROOT = DOL_URL_ROOT;
        if ($this->id > 0) {
            $this->valuesArr = array();
            foreach ($this->lignes as $key => $val) {
                $this->valuesArr[$val->valeur] = $val->label;
            }
            return ($this->valuesArr);
        }
    }

    public function add() {
        $description = addslashes($this->description);
        $label = addslashes($this->label);
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_list WHERE label LIKE '" . $label . "'";
        $sql = $this->db->query($requete);
        if ($this->db->num_rows($sql) > 0) {
            $this->error = "Une liste du m&ecirc;me non existe d&eacute;j&agrave;";
            return -2;
        } else {
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_list (label, description) VALUES ('" . $label . "','" . $description . "')";
            $sql = $this->db->query($requete);
            if ($sql) {
                return ($this->db->last_insert_id("" . MAIN_DB_PREFIX . "Synopsis_Process_form_list"));
            } else {
                $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
                return -1;
            }
        }
    }

    public function getNomUrl($withpicto = 0, $option = 0) {
        global $langs;

        $result = '';
        $urlOption = '';

        $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/Synopsis_Process/listBuilder.php?id=' . $this->id . '">';
        if ($option == 6)
            $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/Synopsis_Process/listBuilder.php?id=' . $this->id . '">';
        $lienfin = '</a>';

        $picto = 'formListe@Synopsis_Process';
        $label = $langs->trans("Liste") . ': ' . $this->label;

        if ($withpicto)
            $result.=($lien . img_object($label, $picto, false, false, 'ABSMIDDLE') . $lienfin);
        if ($withpicto && $option == 6)
            $result.=($lien . img_object($label, $picto, false, false, false, true) . $lienfin);
        if ($withpicto && $withpicto != 2)
            $result.=' ';
        $result.=$lien . $this->label . $lienfin;
        return $result;
    }

    public function update() {
        if ($this->id > 0) {
            $this->label = addslashes($this->label);
            $this->description = addslashes($this->description);

            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_list WHERE label LIKE '" . $this->label . "' AND id <>" . $this->id;
            $sql = $this->db->query($requete);
            if ($this->db->num_rows($sql) > 0) {
                $this->error = "Une liste du m&ecirc;me non existe d&eacute;j&agrave;";
                return -2;
            }
            $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process_form_list
                           SET label= '" . $this->label . "',
                               description='" . $this->description . "'
                         WHERE id = " . $this->id;
            $sql = $this->db->query($requete);
            if ($sql) {
                return (1);
            } else {
                $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
                return -1;
            }
        } else {
            $this->error = "Pas d'id";
            return (-1);
        }
    }

    public function delLignes() {
        if ($this->id > 0) {
            $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members WHERE list_refid =" . $this->id;
            $sql = $this->db->query($requete);
            if ($sql) {
                return (1);
            } else {
                $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
                return -1;
            }
        }
    }

}

class listformmember extends listform {

    public $db;
    public $label;
    public $valeur;
    public $list_refid;

    public function listformmember($DB) {
        $this->db = $DB;
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            global $langs, $user, $mysoc, $societe, $conf;
            $conf->global->DOL_DOCUMENT_ROOT = DOL_DOCUMENT_ROOT;
            $conf->global->DOL_URL_ROOT = DOL_URL_ROOT;
            $this->id = $id;
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members WHERE id=" . $id;
            $sql = $this->db->query($requete);
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $this->label = $res->label;
                $this->valeur = $res->valeur;
                $this->list_refid = $res->list_refid;
            }
        } else {
            return -1;
        }
    }

    public function add() {
        $valeur = addslashes($this->valeur);
        $label = addslashes($this->label);
        $list_refid = addslashes($this->list_refid);
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members WHERE valeur LIKE '" . $valeur . "' AND list_refid =" . $list_refid;
        $sql = $this->db->query($requete);
        if ($this->db->num_rows($sql) > 0) {
            $this->error = "Une valeur identique existe d&eacute;j&agrave; (" . $this->label . "/" . $this->valeur . ")";
            return -2;
        } else {
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members (label, valeur,list_refid) VALUES ('" . $label . "','" . $valeur . "','" . $list_refid . "')";
            $sql = $this->db->query($requete);
            if ($sql) {
                return ($this->db->last_insert_id("" . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members"));
            } else {
                $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
                return -1;
            }
        }
    }

    public function update() {
        if ($this->id > 0) {
            $this->label = addslashes($this->label);
            $this->valeur = addslashes($this->valeur);
            $this->list_refid = addslashes($this->list_refid);
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members WHERE valeur LIKE '" . $this->valeur . "' AND list_refid = " . $this->list_refid;
            $sql = $this->db->query($requete);
            if ($this->db->num_rows($sql) > 0) {
                $this->error = "Une valeur identique existe d&eacute;j&agrave; (" . $this->label . "/" . $this->valeur . ")";
                return -2;
            }
            $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members
                           SET label= '" . $this->label . "',
                               valeur='" . $this->valeur . "',
                               list_refid = '" . $this->list_refid . "'
                         WHERE id = " . $this->id;
            $sql = $this->db->query($requete);
            if ($sql) {
                return (1);
            } else {
                $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
                return -1;
            }
        } else {
            $this->error = "Pas d'id";
            return (-1);
        }
    }

}

class globalvar extends formulaireSource {

    public $db;
    public $label;
    public $description;
    public $globalvar;
    public $glabalVarEval;
    public $valueArr;
    public $valuesArr;
    public $idChrono = 0;

    public function globalvar($DB) {
        $this->db = $DB;
    }

    public function call_function_chronoModule($inut, $inut2) {
        return $this->getValue($inut);
    }

    public function fetch($id) {
        global $conf, $langs;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            global $langs, $user, $mysoc, $societe, $conf;
            $conf->global->DOL_DOCUMENT_ROOT = DOL_DOCUMENT_ROOT;
            $conf->global->DOL_URL_ROOT = DOL_URL_ROOT;
            $this->id = $id;
            $requete = "SELECT global as globalVar, label, description, id
                     FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_global WHERE id=" . $id;
            $sql = $this->db->query($requete);
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $this->globalvar = $res->globalVar;
                $this->label = $res->label;
                $this->description = $res->description;
                $this->nomElement = getParaChaine($this->cssClassM, "type:");
                $eval = $this->globalvar;
                $eval = str_replace("_CHRONOID_", $this->idChrono, $eval);
                $eval = str_replace("_NOMELEM_", $this->nomElement, $eval);
                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/functionPlusProcess.php");
                eval('$eval = ' . $eval . ";");
                $this->glabalVarEval = $eval;
            }
        } else {
            return -1;
        }
    }

    public function getValue($nouse) {
        global $langs, $user, $mysoc, $societe, $conf;
        $this->valueArr = array();
        $this->valueArr[$this->glabalVarEval] = $this->label;

        return ($this->glabalVarEval);
    }

    public function getValues() {
        global $langs, $user, $mysoc, $societe, $conf;
        $conf->global->DOL_DOCUMENT_ROOT = DOL_DOCUMENT_ROOT;
        $conf->global->DOL_URL_ROOT = DOL_URL_ROOT;

        if ($this->id > 0) {
            $this->valuesArr = array();
            $this->valuesArr[$this->glabalVarEval] = $this->label;
            return ($this->valuesArr);
        }
    }

}

class lien extends formulaireSource {

    public $valuesArr = array();
    public $socid = 0;
    public $hasMultiValue = ture;
    public $cssClassM = ""; //Nom du type de chrono au quelle on ve faire le lien

    function lien($db) {
        $this->db = $db;
    }

    function fetch($id) {//id de l'element lien table lien
        $this->id = $id;
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_lien WHERE rowid = " . $this->id;
        $sql = $this->db->query($requete);
        if($this->db->num_rows($sql) < 1)
            return 0;
        $result = $this->db->fetch_object($sql);
        $this->table = $result->table;
        $this->nomElem = $result->nomElem;
        $this->champId = $result->champId;
        $this->where = $result->where;
        $this->hasMultiValue = $result->hasMultiValue;
        $this->champVueSelect = $result->champVueSelect;
        $this->ordre = $result->ordre;
        $this->urlObj = $result->urlObj;
        $this->picto = $result->picto;
        $this->cssClass = $result->cssClass;
        $this->sqlFiltreSoc = $result->sqlFiltreSoc;
        $this->idChrono = (isset($this->idChrono) && $this->idChrono > 0) ? $this->idChrono : (isset($_REQUEST['chrono_id']) ? $_REQUEST['chrono_id'] : (isset($_REQUEST['id']) ? $_REQUEST['id'] : $this->idChrono));

        $this->nomElement = getParaChaine($this->cssClassM, "type:");
        $this->tabVal = array();
        $tabResult = getElementElement($this->nomElement, $this->nomElem, $this->idChrono, null, $this->ordre);
        foreach ($tabResult as $val)
            $this->tabVal[] = $val['d'];
        $debReq = "SELECT " . $this->champId . " as id, " .
                $this->champVueSelect . " as nom "
                . "FROM " . $this->table . " "
                . "WHERE 1";
        if (count($this->tabVal) > 0)
            $this->reqValue = $debReq . " AND " . $this->champId . " IN (" . implode(",", $this->tabVal) . ")";
        if ($this->where != "")
            $debReq .= " AND " . $this->where;
        if ($this->sqlFiltreSoc != "" && $this->socid > 0)
            $debReq .= " AND " . str_replace("[id]", $this->socid, $this->sqlFiltreSoc);
        $this->reqValues = $debReq;
        $this->typeChrono = getParaChaine($this->where, "model_refid = ", "AND");
    }

    function displayForm($inValuesArray = true) {
        $return = "";
        $return .=  '<div class="formAjax">';
        $return .=  '<span class="showFormChrono editable">' . img_edit() . '</span>';
        $return .= $this->getValues();
//            $return .= $this->formHtml;
        $return .=  '<div class="hide">';
        $return .=  '<input type="hidden" id="socid" value="' . $this->socid . '"/>';
        $return .=  '<input type="hidden" name="targettype" class="targettype" value="' . $this->nomElem . '"/>';
        $return .=  '<input type="hidden" name="sourcetype" class="sourcetype" value="' . $this->nomElement . '"/>';
        $return .=  '<input type="hidden" name="sourceid" class="sourceid" value="' . $this->idChrono . '"/>';
        $return .=  '<input type="hidden" name="ordre" class="ordre" value="' . $this->ordre . '"/>';
        $return .=  '<select class ="chronoForm">';
        foreach ($this->valuesArr as $id => $val)
            $return .=  "<option value='" . $id . "'" . (($id == $idT) ? " selected=\"selected\"" : "") . ">" . $val . "</option>";
        $return .=  '</select>';
        $return .=  '</div></div>';
        if (!$inValuesArray)
            $this->valuesArr = array();
        return $return;
    }

    function displayValue() {
        $this->getValue($inut);
        foreach ($this->valuesArr as $id => $val)
            print $val . "<br/>";
    }

    function getValuePlus($id) {
        $return = "";
        if ($this->id == 1) {
            if (count($this->tabVal) > 0) {
                $js = <<<EOF
                    <script>
                    jQuery(document).ready(function(){
                        jQuery('#tabsA').tabs({
                            cache: true,
                            spinner: 'Chargement ...',
                            fx: {opacity: 'toggle' }
                        })
                    });
                    </script>
EOF;
                $return .= "<div id='tabsA'>";
                $return .= "<ul class='syntab'>";
                $return .= "<li><a href='#actif' class='default tab'>Service Actif</a></li>";
                $return .= "<li><a href='#nonactif' class='tab'>Service non actif</a></li>";
                $return .= "</ul>";
                $return .= "<div id='nonactif'>";
                $return .= "</div>";
                $return .= "<div id='actif'>";
//Drag Drop
                $return .= "</div>";
                $return .= "" . $js;
//                $return .= "<ul class='syntab'>";
//                $return .= "<li id='#actif' class='default'>Service Actif</li>";
//                $return .= "<li id='#nonactif'>Service non actif</li>";
//                $return .= "</ul>";
                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Contrat/class/contrat.class.php");
//            echo "Produit sous contrat<br/<br/>";
                foreach ($this->tabVal as $result) {
                    if ($result > 0) {
                        $contratdet = new Synopsis_ContratLigne($this->db);
                        $contratdet->fetch($result);
                        $html = "";
                        $color = "";
                        $dtStr = ($contratdet->date_fin_validite > 0) ? date("c", $contratdet->date_fin_validite) : null;
                        $dateF = new DateTime($dtStr);
//                        $dtStr = date("c", time());
                        $dateActu = new DateTime();
                        $interval = date_diff($dateF, $dateActu);
                        if ($interval->format('%R%a') > 0)
                            $color = "red";
                        elseif ($interval->format('%R%a') > -30)
                            $color = "orange";
                        else
                            $color = "green";
                        $html .= "<div style='clear: both; background-color:" . $color . ";' class='" . ($contratdet->statut == 4 ? "actif" : "nonactif") . " syntabelem'>";
                        $html .= "<a href='" . DOL_URL_ROOT . "/Synopsis_Contrat/contratDetail.php?id=" . $result . "'>" . $contratdet->description . "</a>";
                        $html .= "<br/>";
                        if ($contratdet->fk_product > 0) {
                            $product = new Product($this->db);
                            $product->fetch($contratdet->fk_product);
                            $html .= $product->getNomUrl(1) . " " . $product->description;
                            $html .= "<br/>";
                        }
                        $SLA = "SLA : " . $contratdet->SLA . " | Date fin : " . (($contratdet->date_fin_validite > 0) ? date("d M Y", $contratdet->date_fin_validite) : "n/c");
                        $html .= $SLA."<br/>";
                        $html .= "</div>";
                        $this->valuesArr[] = $html;
                        $this->valuesArrStr[] = $contratdet->ref." - ".$product->name." ".$SLA;
                    }
                }
            }
        } else{
            $return .= $this->displayForm(false);
        }
            $this->formHtml = $return;
        return $this->valuesArr;
    }

    function setValue($idChrono, $tabVal) {
//        print_r($tabVal);
        delElementElement($this->nomElement, $this->nomElem, $idChrono, null, $this->ordre);
        foreach ($tabVal as $val) {
            if ($val != 0)
                addElementElement($this->nomElement, $this->nomElem, $idChrono, $val, $this->ordre);
        }
    }

    function getValues() {
        global $langs;
        
        if($this->champId."x" == "x" || $this->champVueSelect."x" == "x")
            return "";
        
        
        $sup = $return = "";
        $i = 0;
        $sql = $this->db->query($this->reqValues);
        while ($result = $this->db->fetch_object($sql)) {
            $result->nom = dol_trunc($result->nom, 70);
            $this->valuesArr[$result->id] = $result->nom;
            if (in_array($result->id, $this->tabVal)) {
                $i++;
                if ($this->hasMultiValue || $i == 1)
                    $return .=  $this->getOneLigneValue($this->id, $this->nomElement, $i, $result->id, $result->nom);
                $this->valuesArrStr[$result->id] = $result->nom;
            }
        }
        $return .=  $this->getOneLigneValue($this->id, $this->nomElement, "replaceId", "replaceValue", "replaceNom", "model hidden");
//                    $return .=  '<div class="model" style="display:none;"><input type="hidden" name="ChronoLien-'.$this->id.'-'.$this->nomElement.'-replaceId" value="replaceValue"/><a href="">'."replaceNom"."</a><br/></div>";
        if ($this->hasMultiValue)
            $actionChrono = "add";
        else
            $actionChrono = "change";
        if ($this->typeChrono > 0)
            $return .=  "<span class='" . $actionChrono . "Chrono chronoForm cp picto' id='addChrono" . $this->typeChrono . "'>" . img_picto($langs->trans("Create"), 'filenew') . "</span>";
        $return .=  "<button class='" . $actionChrono . "Lien chronoForm'>Ajouter</button>";
        $this->formHtml = $return;
        return $return;
    }

    function getOneLigneValue($id, $nomElement, $i, $idVal, $text, $classDiv = "", $supprAction = "supprLigne(this); ") {
        $html = '<div class="' . $classDiv . ' elem">'
                . '<input type="hidden" name="ChronoLien-' . $id . '-' . $nomElement . '-' . $i . '" value="' . $idVal . "\"/>"
                . "<button onclick='" . $supprAction . "return false;' class='supprLien chronoForm'>X</button>";
//        $html .= ;

        $picto = self::traitePicto($this->picto, $idVal);


        if ($this->urlObj != "") {
            $html .= "<a href=\"" . DOL_URL_ROOT . "/" . $this->urlObj . $idVal . "\"> " . $picto . "</a> ";
            $html .= "<a href=\"" . DOL_URL_ROOT . "/" . $this->urlObj . $idVal . "\" ";
            if ($this->typeChrono > 0)
                $html .= "onclick='dispatchePopObject(" . $idVal . ", \"chrono\", function(){}, \"" . $text . "\",1); return false;'";
            else
                $html .= "onclick='return confirm(\"Ceci va quiter la page sans enregistrer. Continuer ?\");'";
            $html .= ">" . $text . "</a>";
        } else
            $html .= $text;
        $html .= "</div>";
        return $html;
    }

    private static function traitePicto($picto, $id) {
        global $db;
        if (stripos($picto, '[KEY|')) {
            $tabT = explode('[KEY|', $picto);
            $tabT = explode(']', $tabT[1]);
            $keyId = $tabT[0];
            $val = "";
            if (is_numeric($id)) {
                $result = $db->query("SELECT value FROM " . MAIN_DB_PREFIX . "synopsischrono_value WHERE chrono_refid = " . $id . " AND key_id = " . $keyId);
                if ($db->num_rows($result > 0)) {
                    $ligne = $db->fetch_object($result);
                    $val = $ligne->value;
                }
            } else
                $val = 0;
            $picto = str_replace('[KEY|' . $keyId . ']', $val, $picto);
        }
        return ($picto != '') ? img_picto($text, $picto) : "";
    }

    function getValue($id) {
        if ($this->reqValue != "") {
            $sql = $this->db->query($this->reqValue);
//        die("jjjj");
            if ($sql)
                while ($result = $this->db->fetch_object($sql)) {
                    $picto = self::traitePicto($this->picto, $result->id);
                    $result->nom = dol_trunc($result->nom, 100);
                    if ($this->urlObj != "")
                        $html = lien($this->urlObj . $result->id) . finLien($picto . " " . $result->nom);
                    else
                        $html = $picto . $result->nom;
                    $this->valuesArr[$result->id] = $html;
                    $this->valuesArrStr[$result->id] = $result->nom;
                }
        }
    }

}

class requete extends formulaireSource {

    public $db;
    public $requete;
    public $params;
    public $limit;
    public $label;
    public $description;
    public $showFields;
    public $OptGroup;
    public $OptGroupLabel;
    public $indexField;
    public $tableName;
    public $requeteValue;
    public $postTraitement;
    public $postTraitementArr = array();
    public $showFieldsArr = array();
    public $paramsArr = array();
    public $valuesArr = array();
    public $valuesGroupArr = array();
    public $valuesGroupArrDisplay = array();
    public $valueArr = array();
    public $valueGroupArr = array();
    public $valueGroupArrDisplay = array();

    public function requete($DB) {
        $this->db = $DB;
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            global $langs, $user, $mysoc, $societe, $conf;
            $conf->global->DOL_DOCUMENT_ROOT = DOL_DOCUMENT_ROOT;
            $conf->global->DOL_URL_ROOT = DOL_URL_ROOT;
            $this->id = $id;
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_requete WHERE id=" . $id;
            $sql = $this->db->query($requete);
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $this->requete = str_replace("llx_", MAIN_DB_PREFIX, $res->requete);
                $this->postTraitement = $res->postTraitement;
                $this->requeteValue = str_replace("llx_", MAIN_DB_PREFIX, $res->requeteValue);
                if ($this->requeteValue . "x" == "x") {
                    $this->requeteValue = $this->requete;
                    $tabReq1 = explode(" ORDER BY ", $this->requete);
                    $tabReq2 = explode(" GROUP BY ", $tabReq1[0]);
                    $this->requeteValue = $tabReq2[0];
                    if (stripos($this->requeteValue, "where"))
                        $this->requeteValue .= " AND ";
                    else
                        $this->requeteValue .= " WHERE ";
                    $this->requeteValue .= "[[indexField]]";
                    if (isset($tabReq2[1]))
                        $this->requeteValue .= " GROUP BY " . $tabReq2[1];
                    if (isset($tabReq2[1]))
                        $this->requeteValue .= " ORDER BY " . $tabReq2[1];
                }
                $this->postTraitementArr = unserialize($res->postTraitement);
                $this->OptGroup = $res->OptGroup;
                $this->OptGroupLabel = $res->OptGroupLabel;
                $this->limit = $res->limite;
                $this->label = $res->label;
                $this->tableName = $res->tableName;
                $this->indexField = $res->indexField;
                $this->params = $res->params;
                $this->paramsArr = unserialize($res->params);
                $this->description = $res->description;
                $this->showFields = $res->showFields;
                $this->showFieldsArr = unserialize($res->showFields);
//                print_r($this->showFieldsArr); echo "|"; print_r($res);
            }
        } else {
            return -1;
        }
    }

    public function getValue($val) {
        if ($val != '' && $val > 0) {
            global $langs, $user, $mysoc, $societe, $conf;
            $conf->global->DOL_DOCUMENT_ROOT = DOL_DOCUMENT_ROOT;
            $conf->global->DOL_URL_ROOT = DOL_URL_ROOT;
            if ($this->id > 0) {
                $requete = $this->requeteValue;
                if ($requete . "x" == "x") {
                    $requete = $this->requete;
                    if (stripos($requete, "where"))
                        $requete .= " AND ";
                    else
                        $requete .= " WHERE ";
                    $requete .= "[[indexField]]";
                }
                $requete = vsprintf($requete, $this->paramsArr);

//           if(preg_match('/ WHERE/'))

                if ($this->tableName . "x" != 'x')
                    $requete = preg_replace('/\[\[indexField\]\]/', $this->tableName . "." . $this->indexField . "='" . $val . "'", $requete);
                else
                    $requete = preg_replace('/\[\[indexField\]\]/', $this->indexField . "='" . $val . "'", $requete);
                
                $requete = str_replace('"', "'", $requete);
                eval("\$requete = \"$requete\";");
                $sql = $this->db->query($requete);
                $arr = array();
                $arr2 = array();
                $arr3 = array();

                if ($sql) {
                    while ($res = $this->db->fetch_object($sql)) {
                        $indexField = $this->indexField;
                        $index = $res->$indexField;
                        $arrTmp = $arrTmpStr = array();
                        foreach ($this->showFieldsArr as $key => $val) {
                            $result = $res->$val;
                            $arrTmpStr[] = $res->$val;
                            if ($this->postTraitementArr[$val] . "x" != "x") {
                                $fctTmp = preg_replace('/\[VAL\]/i', $res->$val, $this->postTraitementArr[$val]);

                                if (preg_match('/^([\w\W]*)\(([\w\W]*)\)$/', $fctTmp, $arrTmpMatch)) {
                                    $arrTmpMatch = explode("(", $fctTmp);
                                    $fctTmp1 = $arrTmpMatch[0];
                                    $paramsArrTmp = explode(',', str_replace(")[SUPPR]", "", str_replace($fctTmp1 . "(", "", $fctTmp) . "[SUPPR]"));
                                    $result = call_user_func_array($fctTmp1, $paramsArrTmp);
                                }
                            }
                            $arrTmp[] = $result;
                        }
                        $this->valuesArrStr[$index] = join(' ', $arrTmpStr);
                        $this->valuesArr[$index] = join(' ', $arrTmp);

                        if ($this->OptGroup . "x" != "x") {
                            $tmp = $this->OptGroup;
                            $tmp2 = $this->OptGroupLabel;
                            $arr2[$res->$tmp]['data'][$index] = join(' ', $arrTmp);
                            $arr2[$res->$tmp]['label'] = $res->$tmp2;
                            $arr3[$index]['label'] = $res->$tmp2;
                            //var_dump($arr3);
                        }
                    }
                    if ($this->OptGroup . "x" != "x") {
//           var_dump($arr);
//           var_dump($arr2);
//           var_dump($arr3);

                        $this->valueGroupArr = $arr2;
                        $this->valueGroupArrDisplay = $arr3;
                    }
                    return ($arr);
                } else {
                    $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
                    return -1;
                }
            } else {
                $this->error = "Pas d'id!";
                return -1;
            }
        }
    }

    public function getValues() {
        global $langs, $user, $mysoc, $societe, $conf;
        $conf->global->DOL_DOCUMENT_ROOT = DOL_DOCUMENT_ROOT;
        $conf->global->DOL_URL_ROOT = DOL_URL_ROOT;
        if ($this->id > 0) {
            $requete = vsprintf($this->requete, $this->paramsArr);
            $requete .= " LIMIT  " . $this->limit;
//            eval("\$requete = \"$requete\";");
            $sql = $this->db->query($requete);


            $arr = array();
            $arr2 = array();
            $arr3 = array();

            if ($sql) {
                while ($res = $this->db->fetch_object($sql)) {
                    $indexField = $this->indexField;
                    $index = $res->$indexField;
                    $arrTmp = array();
                    foreach ($this->showFieldsArr as $key => $val) {
                        $res->$val = dol_trunc($res->$val, 60);
                        if ($this->postTraitementArr[$val] . "x" != "x") {
                            $fctTmp = preg_replace('/\[VAL\]/i', $res->$val, $this->postTraitementArr[$val]);
                            $result = $res->$val;
                            if (preg_match('/^([\w\W]*)\(([\w\W]*)\)$/', $fctTmp, $arrTmpMatch)) {
                                $arrTmpMatch = explode("(", $fctTmp);
                                $fctTmp1 = $arrTmpMatch[0];
                                $paramsArrTmp = explode(',', str_replace(")[SUPPR]", "", str_replace($fctTmp1 . "(", "", $fctTmp) . "[SUPPR]"));
                                $result = call_user_func_array($fctTmp1, $paramsArrTmp);
                            }
                            $arrTmp[] = $result;
                        } else {
                            $arrTmp[] = $res->$val;
                        }
                    }
                    $arr[$index] = join(' ', $arrTmp);

                    if ($this->OptGroup . "x" != "x") {
                        $tmp = $this->OptGroup;
                        $tmp2 = $this->OptGroupLabel;
                        $arr2[$res->$tmp]['data'][$index] = join(' ', $arrTmp);
                        $arr2[$res->$tmp]['label'] = $res->$tmp2;
                        $arr3[$index]['label'] = $res->$tmp2;
                        //var_dump($arr3);
                    }
                }
                $this->valuesArr = $arr;
                if ($this->OptGroup . "x" != "x") {
//           var_dump($arr);
//           var_dump($arr2);
//           var_dump($arr3);

                    $this->valuesGroupArr = $arr2;
                    $this->valuesGroupArrDisplay = $arr3;
                }
                return ($arr);
            } else {
                $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
                return -1;
            }
        } else {
            $this->error = "Pas d'id!";
            return -1;
        }
    }

    public function update() {
        if ($this->id > 0) {
            $this->label = addslashes($this->label);
            $this->description = addslashes($this->description);
            $this->requete = addslashes($this->requete);
            $this->indexField = addslashes($this->indexField);
            $this->limit = addslashes($this->limit);
            $this->OptGroup = $this->OptGroup;
            $this->postTraitement = addslashes($this->postTraitement);
            $this->OptGroupLabel = $this->OptGroupLabel;
            $this->showFields = serialize($this->showFields);
            $this->params = serialize(explode(',', $this->params));
            $this->tableName = addslashes($this->tableName);
            $this->requeteValue = addslashes($this->requeteValue);

            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_requete WHERE label LIKE '" . $this->label . "' AND id <>" . $this->id;
            $sql = $this->db->query($requete);
            if ($this->db->num_rows($sql) > 0) {
                $this->error = "Un formulaire du m&ecirc;me non existe d&eacute;j&agrave;";
                return -2;
            }


            $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process_form_requete
                           SET label= '" . $this->label . "',
                               description='" . $this->description . "',
                               requete='" . $this->requete . "',
                               requeteValue='" . $this->requeteValue . "',
                               tableName='" . $this->tableName . "',
                               indexField='" . $this->indexField . "',
                               limite='" . $this->limit . "',
                               showFields='" . $this->showFields . "',
                               OptGroup='" . $this->OptGroup . "',
                               OptGroupLabel='" . $this->OptGroupLabel . "',
                               postTraitement='" . $this->postTraitement . "',
                               params='" . $this->params . "'
                         WHERE id = " . $this->id;
            $sql = $this->db->query($requete);
            if ($sql) {
                return (1);
            } else {
                $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
                return -1;
            }
        } else {
            $this->error = "Pas d'id";
            return (-1);
        }
    }

    public function add() {
        $this->requete = addslashes($this->requete);
        $this->description = addslashes($this->description);
        $this->label = addslashes($this->label);
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_requete WHERE label LIKE '" . $this->label . "'";
        $sql = $this->db->query($requete);
        if ($this->db->num_rows($sql) > 0) {
            $this->error = "Un formulaire du m&ecirc;me non existe d&eacute;j&agrave;";
            return -2;
        }
        $requeteIns = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_requete (requete,label,description) VALUES ('" . $this->requete . "','" . $this->label . "','" . $this->description . "')";
        $sql = $this->db->query($requeteIns);
        if ($sql) {
            return($this->db->last_insert_id('" . MAIN_DB_PREFIX . "Synopsis_Process_form_requete'));
        } else {
            $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
            return -1;
        }
    }

    public function del() {
        if ($this->id > 0) {
            $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_requete WHERE id = " . $this->id;
            $sql = $this->db->query($requete);
            if ($sql) {
                return(1);
            } else {
                $this->error = $this->db->lasterrno . " " . $this->db->lastqueryerror . " " . $this->db->lasterror . " " . $this->db->error;
                return(false);
            }
        }
    }

    public function getNomUrl($withpicto = 0, $option = 0) {
        global $langs;

        $result = '';
        $urlOption = '';

        $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/Synopsis_Process/queryBuilder.php?id=' . $this->id . '">';
        if ($option == 6)
            $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/Synopsis_Process/queryBuilder.php?id=' . $this->id . '">';
        $lienfin = '</a>';

        $picto = 'query@Synopsis_Process';
        $label = $langs->trans("Requ&ecirc;te") . ': ' . $this->label;

        if ($withpicto)
            $result.=($lien . img_object($label, $picto, false, false, 'absmiddle') . $lienfin);
        if ($withpicto && $option == 6)
            $result.=($lien . img_object($label, $picto, false, fasle, false, true) . $lienfin);
        if ($withpicto && $withpicto != 2)
            $result.=' ';
        $result.=$lien . $this->label . $lienfin;
        return $result;
    }

}

class fct extends formulaireSource {

    public $db;
    public $label;
    public $params;
    public $description;
    public $fct;
    public $fileClass;
    public $class;
    public $fileClassFullPath;
    public $paramsArr;
    public $printVarInsteadOdReturn;
    public $VarToBePrinted;
    public $paramsForHtmlName;
    public $paramsForHtmlSelect;
    public $postTraitementValue;

    public function fct($DB) {
        $this->db = $DB;
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            global $conf, $user, $langs, $mysoc, $societe;
            $DOL_DOCUMENT_ROOT = DOL_DOCUMENT_ROOT;
            $DOL_URL_ROOT = DOL_URL_ROOT;
            $this->id = $id;
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct WHERE id=" . $id;
//var_dump($requete);print "<br/>";
            $sql = $this->db->query($requete);
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $this->label = $res->label;
                $this->params = $res->params;
                $this->description = $res->description;
                $this->fct = $res->fct;
                $this->class = $res->class;
                $this->fileClass = $res->fileClass;
                $this->fileClassFullPath = DOL_DOCUMENT_ROOT . "/" . $res->fileClass;
                $this->printVarInsteadOdReturn = $res->printVarInsteadOdReturn;
                $this->VarToBePrinted = $res->VarToBePrinted;
                $this->paramsForHtmlName = $res->paramsForHtmlName;
                $this->paramsForHtmlSelect = $res->paramsForHtmlSelect;
                $this->postTraitementValue = $res->postTraitementValue;
            }
        } else {
            return -1;
        }
    }

    public function getValue($val) {
        $eval = preg_replace('/\[\[valeur\]\]/', $val, $this->postTraitementValue);
        $ret = eval($eval);
        return $ret;
    }

    public function call_function($formId, $processDetId = false) {
        if ($formId > 0 && $processDetId > 0) {
            //Get Fonction Params
            $requete = "SELECT *
                          FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct_value
                         WHERE fct_refid = " . $this->id . "
                           AND model_refid = " . $formId;
            $sql = $this->db->query($requete);
            $params = new fct_values($this->db);
            $paramsArr = array();
            while ($res = $this->db->fetch_object($sql)) {
                $params->fetch($res->id);
                $paramsArr[$params->label] = $params->valeur;
            }


            //Get Fonction Params in the form
            $requete = "SELECT v.valeur
                          FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet_value as v,
                               " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct_value as f
                         WHERE f.label = '" . $this->paramsForHtmlName . "'
                           AND f.fct_refid = '" . $this->id . "'
                           AND v.processDet_refid = " . $processDetId . "
                           AND v.nom = f.valeur
                           AND v.model_refid =" . $formId;
            $sql = $this->db->query($requete);
//            print "<br/>".$requete."<br/>";
            while ($res1 = $this->db->fetch_object($sql)) {
                $paramsArr[$this->paramsForHtmlSelect] = $res1->valeur;
            }
//        var_dump($this->paramsForHtmlSelect);print "<br/>";
//        var_dump($paramsArr);print "<br/>";

            $paramsArrValue = preg_split('/\|\|/', $this->params);
            foreach ($paramsArrValue as $key => $val) {
                $this->paramsArr[] = $paramsArr[$val];
            }
            if (is_file($this->fileClassFullPath)) {
                require_once($this->fileClassFullPath);
                $obj = new $this->class($this->db);
                $ret = call_user_func_array(array($obj, $this->fct), $this->paramsArr);
                if ($ret) {
                    if ($this->printVarInsteadOdReturn == 1) {
                        $tmp = $this->VarToBePrinted;
                        print $this->$tmp;
                    } else {
                        print $ret;
                    }
                } else {
                    $this->error = "FCT Error";
                    return ($ret);
                }
            } else {
                $this->error = "Class introuvable";
                return -1;
            }
        } else if ($formId > 0) {
            //Get Fonction Params
            $requete = "SELECT *
                          FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct_value
                         WHERE fct_refid = " . $this->id . "
                           AND model_refid = " . $formId;
            $sql = $this->db->query($requete);
            $params = new fct_values($this->db);
            $paramsArr = array();
            while ($res = $this->db->fetch_object($sql)) {
                $params->fetch($res->id);
                $paramsArr[$params->label] = $params->valeur;
            }
            $paramsArrValue = preg_split('/\|\|/', $this->params);
            foreach ($paramsArrValue as $key => $val) {
                $this->paramsArr[] = $paramsArr[$val];
            }
            if (is_file($this->fileClassFullPath)) {
                require_once($this->fileClassFullPath);
                $obj = new $this->class($this->db);
                $ret = call_user_func_array(array($obj, $this->fct), $this->paramsArr);
                if ($ret) {
                    if ($this->printVarInsteadOdReturn == 1) {
                        $tmp = $this->VarToBePrinted;
                        print $this->$tmp;
                    } else {
                        print $ret;
                    }
                } else {
                    $this->error = "FCT Error";
                    return ($ret);
                }
            } else {
                $this->error = "Class introuvable";
                return -1;
            }
        } else {
            $this->error = "Pas d'Id";
            return -1;
        }
    }

    public function call_function_chronoModule($modeleId, $chronoId = false) {
        $return = "";
        if ($modeleId > 0 && $chronoId > 0) {
            //Get Fonction Params
            $requete = "SELECT *
                          FROM " . MAIN_DB_PREFIX . "synopsischrono_form_fct_value
                         WHERE fct_refid = " . $this->id . "
                           AND chrono_conf_refid = " . $modeleId;
            $sql = $this->db->query($requete);
            $params = new fct_values($this->db);
            $paramsArr = array();
            while ($res = $this->db->fetch_object($sql)) {
                $params->fetch($res->id);
                $paramsArr[$params->label] = $params->valeur;
            }


            //Get Fonction Params in the form
            $requete = "SELECT v.valeur
                          FROM " . MAIN_DB_PREFIX . "synopsischrono_value as v,
                               " . MAIN_DB_PREFIX . "synopsischrono_form_fct_value as f
                         WHERE f.label = '" . $this->paramsForHtmlName . "'
                           AND f.fct_refid = '" . $this->id . "'
                           AND v.chrono_refid = " . $chronoId . "
                           AND v.nom = f.valeur
                           AND f.chrono_conf_refid =" . $modeleId
            ;
            $sql = $this->db->query($requete);
//            print "<br/>".$requete."<br/>";
            while ($res1 = $this->db->fetch_object($sql)) {
                $paramsArr[$this->paramsForHtmlSelect] = $res1->valeur;
            }
//        var_dump($this->paramsForHtmlSelect);print "<br/>";
//        var_dump($paramsArr);print "<br/>";

            $paramsArrValue = preg_split('/\|\|/', $this->params);
            foreach ($paramsArrValue as $key => $val) {
                $this->paramsArr[] = $paramsArr[$val];
            }
            if (is_file($this->fileClassFullPath)) {
                require_once($this->fileClassFullPath);
                $obj = new $this->class($this->db);
                $ret = call_user_func_array(array($obj, $this->fct), $this->paramsArr);
                if ($ret) {
                    if ($this->printVarInsteadOdReturn == 1) {
                        $tmp = $this->VarToBePrinted;
                        $return .= $this->$tmp;
                    } else {
                        $return .= $ret;
                    }
                } else {
                    $this->error = "FCT Error";
                    return ($ret);
                }
            } else {
                $this->error = "Class introuvable";
                return -1;
            }
        } else if ($modeleId > 0) {
            //Get Fonction Params
            $requete = "SELECT *
                          FROM " . MAIN_DB_PREFIX . "synopsischrono_value
                         WHERE fct_refid = " . $this->id . "
                           AND chrono_refid = " . $modeleId;
            $sql = $this->db->query($requete);
            $params = new fct_values($this->db);
            $paramsArr = array();
            while ($res = $this->db->fetch_object($sql)) {
                $params->fetch($res->id);
                $paramsArr[$params->label] = $params->valeur;
            }
            $paramsArrValue = preg_split('/\|\|/', $this->params);
            foreach ($paramsArrValue as $key => $val) {
                $this->paramsArr[] = $paramsArr[$val];
            }
            if (is_file($this->fileClassFullPath)) {
                require_once($this->fileClassFullPath);
                $obj = new $this->class($this->db);
                $ret = call_user_func_array(array($obj, $this->fct), $this->paramsArr);
                if ($ret) {
                    if ($this->printVarInsteadOdReturn == 1) {
                        $tmp = $this->VarToBePrinted;
                        $return .= $this->$tmp;
                    } else {
                        $return .= $ret;
                    }
                } else {
                    $this->error = "FCT Error";
                    return ($ret);
                }
            } else {
                $this->error = "Class introuvable";
                return -1;
            }
        } else {
            $this->error = "Pas d'Id";
            return -1;
        }
        return $return;
    }

    public function getNomUrl($withpicto = 0, $option = 0) {
        global $langs;

        $result = '';
        $urlOption = '';

        $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/Synopsis_Process/fctBuilder.php?id=' . $this->id . '">';
        if ($option == 6)
            $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/Synopsis_Process/fctBuilder.php?id=' . $this->id . '">';
        $lienfin = '</a>';

        $picto = 'fct@Synopsis_Process';
        $label = $langs->trans("Fonction") . ': ' . $this->label;

        if ($withpicto)
            $result.=($lien . img_object($label, $picto, false, false, 'ABSMIDDLE') . $lienfin);
        if ($withpicto && $option == 6)
            $result.=($lien . img_object($label, $picto, false, false, false, true) . $lienfin);
        if ($withpicto && $withpicto != 2)
            $result.=' ';
        $result.=$lien . $this->label . $lienfin;
        return $result;
    }

}

class fct_values extends fct {

    public $db;
    public $id;
    public $label;
    public $valeur;
    public $paramsArr;

    public function fct_values($DB) {
        $this->db = $DB;
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            $this->id = $id;
            if ($id > 0) {
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct_value WHERE id = " . $id;
                $sql = $this->db->query($requete);
                $res = $this->db->fetch_object($sql);
                $this->label = $res->label;
                $this->valeur = $res->valeur;
            } else {
                $this->error = "Pas d'Id";
                return -1;
            }
        } else {
            return -1;
        }
    }

}

class formulaireType extends formulaire {

    public $db;
    public $description;
    public $label;
    public $error;
    public $id;
    public $code;
    public $cssClass;
    public $endNedded;
    public $htmlTag;
    public $htmlEndTag;
    public $hasDescription;
    public $hasDflt;
    public $hasTitle;
    public $hasSource;
    public $isInput;
    public $descriptionInsideTag;
    public $sourceIsOption;
    public $sourceGroupByName;
    public $titleInLegend;
    public $jsScript;
    public $jsCode;
    public $isTabTitle;
    public $isBegEndTab;
    public $titleInsideTag;
    public $isHidden;
    public $isStarRating;
    public $cssScript;
    public $repeatTag;
    public $valueInValueField;
    public $valueInTag;
    public $valueIsChecked;
    public $valueIsSelected;

    public function formulaireType($DB) {
        $this->db = $DB;
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            $this->id = $id;
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type WHERE id = " . $id;
            $sql = $this->db->query($requete);
            $this->id = $id;
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                $this->label = $res->label;
                $this->description = $res->description;
                $this->code = $res->code;
                $this->cssClass = $res->cssClass;
                $this->endNedded = $res->endNedded;
                $this->htmlTag = $res->htmlTag;
                $this->htmlEndTag = $res->htmlEndTag;
                $this->hasDescription = $res->hasDescription;
                $this->hasDflt = $res->hasDflt;
                $this->hasTitle = $res->hasTitle;
                $this->hasSource = $res->hasSource;
                $this->isInput = $res->isInput;
                $this->descriptionInsideTag = $res->descriptionInsideTag;
                $this->sourceGroupByName = $res->sourceGroupByName;
                $this->sourceIsOption = $res->sourceIsOption;
                $this->titleInLegend = $res->titleInLegend;
                $this->jsScript = $res->jsScript;
                $this->jsCode = $res->jsCode;
                $this->isTabTitle = $res->isTabTitle;
                $this->isStarRating = $res->isStarRating;
                $this->isHidden = $res->isHidden;
                $this->isBegEndTab = $res->isBegEndTab;
                $this->titleInsideTag = $res->titleInsideTag;
                $this->cssScript = $res->cssScript;
                $this->repeatTag = $res->repeatTag;
                $this->valueInValueField = $res->valueInValueField;
                $this->valueInTag = $res->valueInTag;
                $this->valueIsChecked = $res->valueIsChecked;
                $this->valueIsSelected = $res->valueIsSelected;
            }
        } else
            return -1;
    }

}

function lien($url) {
    return "<a href='" . DOL_URL_ROOT . "/" . $url . "'>";
}

function finLien($nom) {
    return $nom . "</a>";
}

?>