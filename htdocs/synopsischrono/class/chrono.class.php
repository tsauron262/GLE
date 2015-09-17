<?php

require_once(DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");

class Chrono extends CommonObject {

    public $DB;
    public $id;
    public $description;
    public $propalid;
    public $projetid;
    public $propal;
    public $projet;
    public $socid;
    public $societe = false;
    public $model_refid;
    public $ref;
    public $file_path;
    public $contactid;
    public $fk_user_author;
    public $date_modif;
    public $date;
    public $user_author = false;
    public $contact = false;
    public $model = false;
    public $mask;
    public $user_modif_id;
    public $error;
    public $revision;
    public $statut = 0;
    public $user_modif;
    public $validation_number;
    public $keysListId = array();
    public $keysList = array();
    public $extraValue = array();
    public $loadObject = true;
    private static $tabModelStat = array();
    private static $tabIdToNomChamp = array();
    private static $tabNomNomChampToId = array();

    public function Chrono($DB) {
        $this->db = $DB;
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISCHRONO) {
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE id = '" . $id . "';";
            $sql = $this->db->query($requete);
            if ($this->db->num_rows($sql) > 0) {
                $res = $this->db->fetch_object($sql);
                if ($res) {
                    $this->id = $id;
                    $this->date = strtotime($res->date_create);
                    $this->date_modif = strtotime($res->tms);
                    $this->socid = $res->fk_soc;
                    $this->statut = $res->fk_statut;
                    $this->note = $res->note;
                    $this->validation_number = $res->validation_number;

                    $this->fk_user_author = $res->fk_user_author;
                    if ($this->fk_user_author && $this->loadObject) {
                        $tmpUser = new User($this->db);
                        if ($this->fk_user_author > 0) {
                            $tmpUser->fetch($this->fk_user_author);
                        }
                    }
                    $this->user_author = $tmpUser;
                    $this->user_modif_id = $res->fk_user_modif;
                    if ($this->user_modif_id > 0 && $this->loadObject) {
                        $tmpUser = new User($this->db);
                        $tmpUser->fetch($this->user_modif_id);
                        $this->user_modif = $tmpUser;
                    }

                    $this->contactid = $res->fk_socpeople;
                    if ($this->contactid > 0 && $this->loadObject) {
                        require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
                        $contact = new Contact($this->db);
                        $contact->fetch($this->contactid);
                        $this->contact = $contact;
                    }
                    $this->file_path = $res->file_path;
                    $this->description = $res->description;
                    $this->model_refid = $res->model_refid;
                    $this->propalid = $res->propalid;
                    if ($this->propalid > 0 && $this->loadObject) {
                        require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
                        $propal = new Propal($this->db);
                        $propal->fetch($this->propalid);
                        $this->propal = $propal;
                    }
                    $this->projetid = $res->projetid;
                    if ($this->projetid > 0 && $this->loadObject) {
                        require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
                        $projet = new Project($this->db);
                        $projet->fetch($this->projetid);
                        $this->projet = $projet;
                    }
                    $this->ref = $res->ref;
                    $this->orig_ref = (isset($res->orig_ref) && $res->orig_ref != '' ? $res->orig_ref : $this->ref);
                    $this->revision = ($res->revision > 0 ? $res->revision : false);
                    if ($this->model_refid > 0) {
                        if (isset(self::$tabModelStat[$res->model_refid])) {
                            $this->model = self::$tabModelStat[$res->model_refid];
                        } else {
                            $this->model = new ChronoRef($this->db);
                            $this->model->fetch($res->model_refid);
                            self::$tabModelStat[$res->model_refid] = $this->model;
                        }
                        $this->mask = $this->model->modele;
                    }

                    if ($this->socid > 0 && $this->model->hasSociete && $this->loadObject) {
                        $soc = new Societe($this->db);
                        $soc->fetch($this->socid);
                        $this->societe = $soc;
                    }

                    global $user;
                    if ($this->loadObject) {
                        if (!isset($this->model->rightsLoad))
                            $this->getRights($user);
                        $this->model->rightsLoad = true;
                    }
                    return($id);
                } else {
                    return -1;
                }
            } else {
                return -1;
            }
        }
    }

    public function attenteValidate() {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "synopsischrono SET fk_statut = 999 WHERE id = " . $this->id;
        $sql = $this->db->query($requete);
        if ($sql) {
            global $user, $langs, $conf;
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('CHRONO_ASK_VALIDATE', $this, $user, $langs, $conf);
            if ($result < 0) {
                $this->error = $interface->errors;
            }
            // Fin appel triggers

            return (1);
        } else {
            return (-1);
        }
    }

    public function multivalidate() {



        //1 cherche toutes les conditions de validation
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_rights_def WHERE isValidationRight =1 AND isValidationForAll <> 1";
        $sql = $this->db->query($requete);
        $arrValid = array();
        while ($res = $this->db->fetch_object($sql)) {
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_Multivalidation WHERE validation_number " . ($this->validation_number > 0 ? " = " . $this->validation_number : " IS NULL ") . " AND chrono_refid = " . $this->id . " AND right_refid = " . $res->id;
            $sql1 = $this->db->query($requete);
            $res1 = $this->db->fetch_object($sql1);
            if ($res1->validation == "0") {
                $arrValid[$res->id] = 0;
            } else if ($res1->validation == "1") {
                $arrValid[$res->id] = 1;
            } else {
                $arrValid[$res->id] = -1;
            }
        }
        //2 Trie
        //a Toutes les valeurs à 1 => validate
        //b Toutes les valeurs != null => unvalidate
        //c Au moins une valeur à null => continue
        $all1 = false;
        $atLeast1zero = false;
        $atLeastOneNull = false;
        foreach ($arrValid as $key => $val) {
            if ($val < 0) {
                $atLeastOneNull = true;
                break;
            }
            if ($val == 0) {
                if (!$atLeast1zero) {
                    $atLeast1zero = true;
                    $all1 = false;
                }
            }
            if ($val > 0) {
                if (!$atLeast1zero) {
                    $all1 = true;
                }
            }
        }
        if ($atLeastOneNull) {
            return (1);
        } else if ($atLeast1zero) {
            $requete = "UPDATE " . MAIN_DB_PREFIX . "synopsischrono SET validation_number = ifnull(validation_number,0) + 1 WHERE id = " . $this->id;
            $sql = $this->db->query($requete);
            return $this->unvalidate();
        } else if ($all1) {
            return $this->validate();
        }
    }

    public function validate() {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "synopsischrono SET fk_statut = 2 WHERE id = " . $this->id;
        $sql = $this->db->query($requete);
        if ($sql) {

            global $user, $langs, $conf;
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('CHRONO_VALIDATE', $this, $user, $langs, $conf);
            if ($result < 0) {
                $this->error = $interface->errors;
            }
            // Fin appel triggers

            return (1);
        } else {
            return (-1);
        }
    }

    public function unvalidate() {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "synopsischrono SET fk_statut = 0 WHERE id = " . $this->id;
        $sql = $this->db->query($requete);
        if ($sql) {
            global $user, $langs, $conf;
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('CHRONO_UNVALIDATE', $this, $user, $langs, $conf);
            if ($result < 0) {
                $this->error = $interface->errors;
            }
            // Fin appel triggers

            return (1);
        } else {
            return (-1);
        }
    }

    public function revised() {
        $this->db->begin();
        $requete = "SELECT *
                     FROM " . MAIN_DB_PREFIX . "Synopsis_revision_model
                    WHERE id = " . $this->model->revision_model_refid;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $tmp = $res->phpClass;
        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Revision/modele/" . $res->phpClass . ".class.php");
        $obj = new $tmp($this->db);
        $revision = $obj->convert_revision(($this->revision ? $this->revision + 1 : 1));
        //Nouvelle ref
        //$this->ref = substr($this->ref,0,7);
        $newRef = $this->ref . "-" . $revision;
        if ($this->orig_ref . "x" != "x") {
            $newRef = $this->orig_ref . "-" . $revision;
        }
        //Nouvelle revision
        $oldId = $this->id;


        $newId = $this->create();
        $this->ref = $newRef;
        $this->revision = ($this->revision ? $this->revision + 1 : 1);
        $this->update($this->id);
        $this->getValues();
        $this->setDatas($this->id, $this->values);

//        $newId = $this->create_revision_from($newRef, ($this->revision ? $this->revision + 1 : 1));
        //Copie extra value
//        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_value WHERE chrono_refid = " . $_REQUEST['id'];
//        $sql = $this->db->query($requete);
//        while ($res = $this->db->fetch_object($sql)) {
//            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsischrono_value
//                                    ( chrono_refid,
//                                      value,
//                                      key_id)
//                             VALUES ( " . $newId . " ,
//                                      '" . addslashes($res->value) . "' ,
//                                      " . $res->key_id . " )";
//            $sql1 = $this->db->query($requete);
//        }

        $requete = "UPDATE " . MAIN_DB_PREFIX . "synopsischrono SET fk_statut = 3, revisionNext = " . $newId . " WHERE id = " . $oldId;
        $sqlA = $this->db->query($requete);

        if ($sqlA && $newId > 0) {
            global $user, $langs, $conf;
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('CHRONO_REVISED', $this, $user, $langs, $conf);
            if ($result < 0) {
                $this->error = $interface->errors;
            }
            // Fin appel triggers
            $this->db->commit();
            return $newId;
        } else {
            $this->db->rollback();
            return(-1);
        }
    }

    public function update($id) {
        global $user;
        $this->id = $id;
        $description = $this->description;
        $socid = ($this->socid > 0 ? $this->socid : false);
        $contactid = ($this->contactid > 0 ? $this->contactid : false);


        $result = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE id = " . $id);
        $ligne = $this->db->fetch_object($result);
        if ($socid && $ligne->fk_soc != $socid) {
            if ($ligne->propalid) {
                $tabModif = array(array("propal", "fk_soc", $ligne->propalid, $socid));
//            $db->query("UPDATE ");
                $tab = getElementElement("propal", null, $ligne->propalid);
                foreach ($tab as $ligne2) {
                    $tabModif[] = array($ligne2['td'], "fk_soc", $ligne2['d'], $socid);
                }


                foreach ($tabModif as $ligne3) {
                    $this->db->query("UPDATE " . MAIN_DB_PREFIX . $ligne3[0] . " SET " . $ligne3[1] . " = " . $ligne3[3] . " WHERE rowid = " . $ligne3[2]);
                }
            }
        }





        $requete = "UPDATE " . MAIN_DB_PREFIX . "synopsischrono SET description = '" . addslashes($description) . "'";
        if ($socid)
            $requete .= ", fk_soc =  " . $socid;
        else
            $requete .= ", fk_soc = NULL ";
        if ($contactid)
            $requete .= ", fk_socpeople =  " . $contactid;
        else
            $requete .= ", fk_socpeople = NULL ";
        if ($this->note != "")
            $requete .= ", note = '" . addslashes($this->note) . "'";

        $requete .= ", orig_ref = '" . $this->orig_ref . "'";
        $requete .= ", revision = '" . $this->revision . "'";

        $requete .= ", fk_user_modif = " . $user->id;
        $requete .= " WHERE id = " . $id;
        $sql = $this->db->query($requete);
        if ($sql)
            return ($id);
        else
            return (-1);
    }

    public function getLibStatut($mode) {
        return $this->LibStatut($this->statut, $mode);
    }

    /**
     *        \brief      Renvoi le libelle d'un statut donne
     *        \param      statut      Id statut
     *        \param      facturee    Si facturee
     *        \param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *        \return     string        Libelle
     */
    private function traiteLib($lib) {
        global $langs;
        return $langs->trans($lib);
    }

    private function LibStatut($statut, $mode) {
        global $langs;
        $langs->load("chrono@synopsischrono");

        if ($mode == 0) {
            if ($statut == 0)
                return $this->traiteLib('StatusDraft');
            if ($statut == 2)
                return $this->traiteLib('StatusValidated');
            if ($statut == 3)
                return $this->traiteLib('StatusRevised');
            if ($statut == 4)
                return $this->traiteLib('StatusClotured');
            if ($statut == 999)
                return $this->traiteLib('StatusInValidation');
        }
        if ($mode == 1) {
            if ($statut == 0)
                return $this->traiteLib('StatusDraftShort');
            if ($statut == 2)
                return $this->traiteLib('StatusValidatedShort');
            if ($statut == 3)
                return $this->traiteLib('StatusRevisedShort');
            if ($statut == 4)
                return $this->traiteLib('StatusCloturedShort');
            if ($statut == 999)
                return $this->traiteLib('StatusInValidationShort');
        }
        if ($mode == 2) {
            if ($statut == 0)
                return img_picto($this->traiteLib('StatusDraftShort'), 'statut0') . ' ' . $this->traiteLib('StatusDraftShort');
            if ($statut == 2)
                return img_picto($this->traiteLib('StatusValidatedShort'), 'statut1') . ' ' . $this->traiteLib('StatusValidatedShort');
            if ($statut == 3)
                return img_picto($this->traiteLib('StatusRevisedShort'), 'statut2') . ' ' . $this->traiteLib('StatusRevisedShort');
            if ($statut == 4)
                return img_picto($this->traiteLib('StatusCloturedShort'), 'statut6') . ' ' . $this->traiteLib('StatusCloturedShort');
            if ($statut == 999)
                return img_picto($this->traiteLib('StatusInValidationShort'), 'statut8', 'style="vertical-align:middle;"') . ' ' . $this->traiteLib('StatusInValidationShort');
        }
        if ($mode == 3) {
            if ($statut == 0)
                return img_picto($this->traiteLib('StatusDraftShort'), 'statut0');
            if ($statut == 2)
                return img_picto($this->traiteLib('StatusValidatedShort'), 'statut1');
            if ($statut == 3)
                return img_picto($this->traiteLib('StatusRevisedShort'), 'statut2');
            if ($statut == 4)
                return img_picto($this->traiteLib('StatusCloturedShort'), 'statut6');
            if ($statut == 999)
                return img_picto($this->traiteLib('StatusInValidationShort'), 'statut8', 'style="vertical-align:middle;"');
        }
        if ($mode == 4) {
            if ($statut == 0)
                return img_picto($this->traiteLib('StatusDraft'), 'statut0') . ' ' . $this->traiteLib('StatusDraft');
            if ($statut == 2)
                return img_picto($this->traiteLib('StatusValidated'), 'statut1') . ' ' . $this->traiteLib('StatusValidated');
            if ($statut == 3)
                return img_picto($this->traiteLib('StatusRevised'), 'statut2') . ' ' . $this->traiteLib('StatusRevised');
            if ($statut == 4)
                return img_picto($this->traiteLib('StatusClotured'), 'statut6') . ' ' . $this->traiteLib('StatusClotured');
            if ($statut == 999)
                return img_picto($this->traiteLib('StatusInValidation'), 'statut8', 'style="vertical-align:middle;"') . ' ' . $this->traiteLib('StatusInValidation');
        }
        if ($mode == 5) {
            if ($statut == 0)
                return $this->traiteLib('StatusDraftShort') . " " . img_picto($this->traiteLib('StatusDraftShort'), 'statut0');
            if ($statut == 2)
                return $this->traiteLib('StatusValidatedShort') . " " . img_picto($this->traiteLib('StatusValidatedShort'), 'statut1');
            if ($statut == 3)
                return $this->traiteLib('StatusRevisedShort') . " " . img_picto($this->traiteLib('StatusRevisedShort'), 'statut2');
            if ($statut == 4)
                return $this->traiteLib('StatusCloturedShort') . " " . img_picto($this->traiteLib('StatusCloturedShort'), 'statut6');
            if ($statut == 999)
                return $this->traiteLib('StatusInValidationShort') . " " . img_picto($this->traiteLib('StatusInValidationShort'), 'statut8', 'style="vertical-align:middle;"');
        }

        if ($mode == 6) {
            if ($statut == 0)
                return $this->traiteLib('StatusDraft') . " " . img_picto($this->traiteLib('StatusDraft'), 'statut0');
            if ($statut == 2)
                return $this->traiteLib('StatusValidated') . " " . img_picto($this->traiteLib('StatusValidated'), 'statut1');
            if ($statut == 3)
                return $this->traiteLib('StatusRevised') . " " . img_picto($this->traiteLib('StatusRevised'), 'statut2');
            if ($statut == 4)
                return $this->traiteLib('StatusClotured') . " " . img_picto($this->traiteLib('StatusClotured'), 'statut6');
            if ($statut == 999)
                return $this->traiteLib('StatusInValidation') . " " . img_picto($this->traiteLib('StatusInValidation'), 'statut8', 'style="vertical-align:middle;"');
        }
    }

    public function supprimer($id) {
        $this->db->begin();
        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "synopsischrono_chrono_" . $this->model_refid . " WHERE id = " . $id;
        $sql = $this->db->query($requete);
        $requete1 = "DELETE FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE id = " . $id;
        $sql1 = $this->db->query($requete1);

        if ($sql && $sql1) {
            $this->db->commit();
            return (1);
        } else {
            $this->db->rollback();
            return (-1);
        }
    }

    public function getRights() {
        global $user;
        $userObj = $user;
        $requete = "SELECT concat('chrono'," . $this->model_refid . ") as p,
                           d.code,
                           'chrono_user' as pr,
                           r.valeur
                      FROM " . MAIN_DB_PREFIX . "synopsischrono_rights_def as d
                 LEFT JOIN " . MAIN_DB_PREFIX . "synopsischrono_rights as r ON user_refid = " . $userObj->id . " AND chrono_refid = " . $this->model_refid . " AND r.right_refid = d.id
                     WHERE d.active = 1 ";
        $sql = $this->db->query($requete);
// print $requete;
        while ($res = $this->db->fetch_object($sql)) {
            $val = $res->valeur;
            $code = $res->code;
            $chrono = $res->p;
            $module = $res->pr;
            if ($val == 1)
                @$userObj->rights->$module->$chrono->$code = 1;
            else
                @$userObj->rights->$module->$chrono->$code = false;
        }
//        $groups = $userObj->listGroupIn();
        require_once(DOL_DOCUMENT_ROOT . "/user/class/usergroup.class.php");
        $usergroup = new UserGroup($this->db);
        $groups = $usergroup->listGroupsForUser($userObj->id,0);
        //  var_dump($groups);
        foreach ($groups as $group) {
            $group = $this->getGrpRights($group);
            foreach ($userObj->rights->chrono_user as $key => $val) {
                foreach ($val as $key1 => $val1) {
                    if ($group->rights->chrono_group->$key->$key1 && !$val1) {
                        $userObj->rights->chrono_user->$key->$key1 = "g";
                    }
                }
            }
        }


        return $userObj;
    }

    public function getGrpRights($grpObj) {
        $requete = "SELECT concat('chrono'," . $this->model_refid . ") as p,
                           d.code,
                           'chrono_group' as pr,
                           r.valeur
                      FROM " . MAIN_DB_PREFIX . "synopsischrono_rights_def as d
                 LEFT JOIN " . MAIN_DB_PREFIX . "synopsischrono_group_rights as r ON group_refid = " . $grpObj->id . " AND chrono_refid = " . $this->model_refid . " AND r.right_refid = d.id
                     WHERE d.active = 1 ";
        $sql = $this->db->query($requete);
// print $requete;
        while ($res = $this->db->fetch_object($sql)) {
            $val = $res->valeur;
            $code = $res->code;
            $chrono = $res->p;
            $module = $res->pr;
            if ($val == 1)
                @$grpObj->rights->$module->$chrono->$code = 1;
            else
                @$grpObj->rights->$module->$chrono->$code = false;
        }
        return $grpObj;
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
            foreach ($userObj->rights->chrono_user as $key => $val) {
                foreach ($val as $key1 => $val1) {
                    if ($group->rights->chrono_group->$key->$key1 && !$val1) {
                        $userObj->rights->chrono_user->$key->$key1 = "g";
                    }
                }
            }
        }
        return($userObj);
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
        if ($user->rights->chrono->valider)
            return(true);
        else {
            $userObj = $this->getGlobalRights($userObjOrId);
            $tmpProcess = 'chrono' . $this->id;
            foreach ($userObj->rights->chrono_user->$tmpProcess as $key => $val) {
                if ($this->arrRightValid[$key]['isValidationForAll']) {
                    return (true);
                } else if ($this->arrRightValid[$key]['isValidationRight']) {
                    return(true);
                }
            }
        }
        return(false);
    }

    public function getNomUrl($withpicto = 0, $option = '', $maxlen = 0) {
        global $langs;

        $this->picto = $this->model->picto;

        $result = '';

        if ($option == "desc" && $this->description != '' && stripos($this->ref, 'prod') !== null)
            $titre = dol_trunc($this->description, 40);
        else if ($this->model->id != 105)
            $titre = $this->ref . " : " . dol_trunc($this->description, 25);
        else
            $titre = $this->ref;


        $this->getValues();

        if (isset($this->extraValueById[$this->id][1068]['value']) && $this->extraValueById[$this->id][1068]['value'] == 1)
            $titre = "<span style='color:red'>" . $titre . "</span>";


        $lien = '<a title="' . $titre . '" href="' . DOL_URL_ROOT . '/synopsischrono/card.php?id=' . $this->id . '">';
        $lienfin = '</a>';
        

//        if (stripos($this->picto, '[KEY|')) {
//            $tabT = explode('[KEY|', $this->picto);
//            $tabT = explode(']', $tabT[1]);
//            $keyId = $tabT[0];
////            echo "<pre>";
////            print_r($this);
////            die("ici".$this->extraValueById[$this->id][$keyId]['value']);
//            $val = $this->extraValueById[$this->id][$keyId]['value'];
//            $this->picto = str_replace('[KEY|' . $keyId . ']', $val, $this->picto);
//        }

        if ($option == 6) {
            $lien = '<a title="' . $this->nom . '" href="' . DOL_URL_ROOT . '/synopsischrono/card.php?id=' . $this->id . '">';
            $lienfin = '</a>';
        }
        if ($option == 6 && $withpicto) {
            $result.=($lien . lien::traitePicto($this->picto,$this->id,$langs->trans("Chrono") . ': ' . $titre) . $lienfin . ' ');
        } else if ($withpicto)
            $result.=($lien . lien::traitePicto($this->picto,$this->id,$langs->trans("ShowChrono") . ': ' . $titre) . $lienfin . ' ');

        $result.=$lien . ($maxlen ? dol_trunc($titre, $maxlen) : $titre) . $lienfin;
        return $result;
    }

    private function getModeleMask($modelId = false) {
        if (!$modelId)
            $modelId = $this->model_refid;
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_conf WHERE id = " . $modelId;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        if (isset($_REQUEST['centre']))
            $res->modele = str_replace("{CENTRE}", $_REQUEST['centre'], $res->modele);
        return($res->modele);
    }

    private function getNextNumRef() {
        require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/mod_chrono_serpentine.class.php");
        $tmp = new mod_chrono_serpentine($this->db);
        $objsoc = false;
        if ($this->socid > 0) {
            $objsoc = new Societe($this->db);
            $objsoc->fetch($this->socid);
        }
        return($tmp->getNextValue($objsoc, $this, $this->getModeleMask()));
    }

    public function createPropal() {
        global $user;
        require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
        $prop = new Propal($this->db);
        $prop->modelpdf = "azurSAV";
        $prop->socid = $this->socid;
        $prop->date = dol_now();
        $prop->cond_reglement_id = 0;
        $prop->mode_reglement_id = 0;
        $prop->create($user);
        if ($this->contactid) {
            $prop->add_contact($this->contactid, 40);
            $prop->add_contact($this->contactid, 41);
        }
        $this->db->query("UPDATE " . MAIN_DB_PREFIX . "synopsischrono SET propalid = '" . $prop->id . "' WHERE id = " . $this->id);
        $this->propalid = $prop->id;
        $this->propal = $prop;
        return $prop->id;
    }

    public function create() {
        global $user;
        $ref = $this->getNextNumRef();
        //$propid = $this->prop;
        //$propal = "SELECT rowid FROM ".MAIN_DB_PREFIX."propal WHERE ref=".$this->propalid;
        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsischrono (date_create,ref,model_refid,description,fk_soc,fk_socpeople,propalid,projetid,fk_user_author)
                          VALUES (now(),'" . $ref . "','" . $this->model_refid . "','" . $this->description . "'," . ($this->socid > 0 ? $this->socid : 'NULL') . "," . ($this->contactid > 0 ? $this->contactid : 'NULL') . ",'" . $this->propalid . "','" . $this->projetid . "', " . $user->id . ")";
        $sql = $this->db->query($requete);
        if ($sql) {
            $this->id = $this->db->last_insert_id("" . MAIN_DB_PREFIX . "synopsischrono");
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsischrono_chrono_" . $this->model_refid . " (id) VALUES (" . $this->id . ")";
            $sql = $this->db->query($requete);
            if ($sql)
                return ($this->id);
        } else {
            return (-1);
            print "$requete";
        }
    }

    public function create_revision_from($newRef, $revision) {
        global $user;
        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsischrono
                                ( date_create,
                                  ref,
                                  model_refid,
                                  description,
                                  fk_soc,
                                  fk_socpeople,
				  propalid,
				  projetid,
				  fk_user_author,
                                  orig_ref,
                                  revision )
                          VALUES (now(),
                                  '" . $newRef . "',
                                  '" . $this->model_refid . "',
                                  '" . addslashes($this->description) . "',
                                  " . ($this->socid > 0 ? $this->socid : 'NULL') . ",
                                  " . ($this->contactid > 0 ? $this->contactid : 'NULL') . ",
				  '" . $this->propalid . "',
				  '" . $this->projetid . "',
                                  " . $user->id . ",
                                  '" . ($this->orig_ref . "x" == "x" ? $this->ref : $this->orig_ref) . "',
                                  " . $revision . ")";
        $sql = $this->db->query($requete);
        if ($sql) {
            $this->id = $this->db->last_insert_id("" . MAIN_DB_PREFIX . "synopsischrono");
            return ($this->id);
        } else {
            return (-1);
        }
    }

    public function getKeys() {
        $this->keysListId = array();
        $this->keysList = array();
        if ($this->model_refid > 0) {
            $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "synopsischrono_key
                     WHERE model_refid = " . $this->model_refid;
            $sql = $this->db->query($requete);
            while ($res = $this->db->fetch_object($sql)) {
                $this->keysList[$res->id] = array("key_id" => $this->id, "nom" => $res->nom, "description" => $res->description, "type" => $res->type, "inDetList" => $res->inDetList);
                $this->keysListId[] = $res->id;

                $this->keysListByModel[$this->model_refid][$res->id] = array("key_id" => $this->id, "nom" => $res->nom, "description" => $res->description, "type" => $res->type, "inDetList" => $res->inDetList);
                $this->keysListIdByModel[$this->model_refid][] = $res->id;
            }
        }
    }

    public function getValues($chrono_id = null, $queId = false) {
        if ($chrono_id == null)
            $chrono_id = $this->id;
        if (count($this->keysListIdByModel[$this->model_refid]) < 1) {
            $this->getKeys();
        }
        if (count($this->keysListId) > 0) {
            $keyStr = join(',', $this->keysListIdByModel[$this->model_refid]);

//            $requete = "SELECT *
//                          FROM " . MAIN_DB_PREFIX . "synopsischrono_value
//                         WHERE chrono_refid = " . $chrono_id . " AND key_id in (" . $keyStr . ")";
//            $sql = $this->db->query($requete);
//            while ($res = $this->db->fetch_object($sql)) {
//                $value = stripslashes($res->value);
//                $key = $this->keysList[$res->key_id]['nom'];
//                $desc = $this->keysList[$res->key_id]['description'];
//                $this->extraValue[$chrono_id][$key] = array('value' => $value, 'description' => $desc);
//                $this->values[$key] = $value;
//                $this->values[$res->key_id] = $value;
//                $this->extraValueById[$chrono_id][$res->key_id] = array('value' => $value, 'description' => $desc);
//            }
            $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_chrono_" . $this->model_refid . " WHERE id = " . $chrono_id);
            while ($tab = $this->db->fetch_array($sql)) {
                foreach ($tab as $nom => $val) {
                    if (!is_int($nom)) {
                        $value = stripslashes($val);

                        foreach ($this->keysList as $idT => $tabT)
                            if ($tabT['nom'] == $nom)
                                $key_id = $idT;

//                        $key = $this->keysList[$res->key_id]['nom'];
                        $key = $nom;
                        if ($key_id) {
                            $desc = $this->keysList[$key_id]['description'];
                            $this->values[$key_id] = $value;
                            $this->extraValueById[$chrono_id][$key_id] = array('value' => $value, 'description' => $desc);
                        }
                        if (!$queId) {
                            $this->extraValue[$chrono_id][$key] = array('value' => $value, 'description' => $desc);
                            $this->values[$key] = $value;
                        }
                    }
                }
            }
        }
    }

    public function getValuesPlus() {
        $requete = "SELECT k.nom,
                           k.id,
                           k.extraCss,
                           t.nom as typeNom,
                           t.hasSubValeur,
                           t.subValeur_table,
                           t.subValeur_idx,
                           t.subValeur_text,
                           t.htmlTag,
                           t.htmlEndTag,
                           t.endNeeded,
                           t.cssClass,
                           t.cssScript,
                           t.jsCode,
                           t.valueIsChecked,
                           t.valueIsSelected,
                           t.valueInTag,
                           t.valueInValueField,
                           t.sourceIsOption,
                           k.type_valeur,
                           k.type_subvaleur,
                           k.extraCss,
                           t.phpClass
                      FROM " . MAIN_DB_PREFIX . "synopsischrono_key_type_valeur AS t,
                           " . MAIN_DB_PREFIX . "synopsischrono_key AS k
                      " . /* LEFT JOIN " . MAIN_DB_PREFIX . "synopsischrono_value AS v ON v.key_id = k.id AND v.chrono_refid = " . $this->id . */"
                     WHERE t.id = k.type_valeur
                       AND k.model_refid = " . $this->model->id
                . " ORDER BY k.rang";

        $sql2 = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_chrono_" . $this->model->id . " WHERE id = " . $this->id);
        if ($this->db->num_rows($sql2) < 1)
            die("Pas de correspondance dans synopsischrono_chrono_" . $this->model_refid . " pour l'id " . $this->id);
        $res2 = $this->db->fetch_object($sql2);


        //print $requete;
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql)) {
//            $res->value = stripslashes($res->value);
            $nameChamp = traiteCarac($res->nom) . "Val";
            if (!isset($res2->$nameChamp))
                $nameChamp = str_replace("Val", "", $nameChamp);

            $res->value = stripslashes($res2->$nameChamp);
            if ($res->type_valeur == 10) {
                $sqlT = $this->db->query("SELECT `nomElem` FROM `" . MAIN_DB_PREFIX . "Synopsis_Process_lien` WHERE `rowid` = " . $res->type_subvaleur);
                if ($this->db->num_rows($sqlT) > 0) {
                    $resultT = $this->db->fetch_object($sqlT);
                    $tabT = getElementElement(getParaChaine($res->extraCss, "type:"), $resultT->nomElem, $this->id);
                    if (isset($tabT[0]))
                        $res->value = $tabT[0]['d'];
                }
            }


            if ($res->hasSubValeur == 1) {
                if ($res->sourceIsOption) {
                    $tmp = $res->phpClass;
                    $obj = new $tmp($this->db);
                    $obj->cssClassM = $res->extraCss;
                    $obj->idChrono = $this->id;
                    $obj->socid = $this->socid;
                    $obj->fetch($res->type_subvaleur);
                    $htmlLi = $obj->getValuePlus($res->value);
                    $html = $obj->formHtml;
//                    $htmlLi = $obj->getValue($res->value);
                    $str = "";
                    foreach ($obj->valuesArr as $key => $val) {
//                        if ($res->valueIsSelected && $res->value == $key) {
                        if ($obj->OptGroup . "x" != "x") {
                            $html .= $obj->valuesGroupArrDisplay[$key]['label'] . " - " . $val;
//                                break;
                        } else {
                            $html .= $val . "<br/>";
//                                break;
                        }
//                        }
                    }
                    if (!isset($obj->valuesArrStr) || !is_array($obj->valuesArrStr))
                        $obj->valuesArrStr = $obj->valuesArr;
                    foreach ($obj->valuesArrStr as $key => $val) {
//                        if ($res->valueIsSelected && $res->value == $key) {
                        if ($obj->OptGroup . "x" != "x") {
                            $str .= $obj->valuesGroupArrDisplay[$key]['label'] . " - " . $val;
//                                break;
                        } else {
                            if ($str != "")
                                $str .= "<br/>";
                            $str .= $val;
//                                break;
                        }
//                        }
                    }
                    $res->valueHtml = $html;
                    $res->valueStr = $str;
                    $res->valueHtmlLi = $htmlLi;
                } else {
                    //Beta
                    if ($res->phpClass == 'fct' || $res->phpClass == 'globalvar')
                        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
                    $tmp = $res->phpClass;
                    $obj = new $tmp($this->db);
                    $obj->cssClassM = $res->extraCss;
                    $obj->idChrono = $this->id;
                    $obj->fetch($res->type_subvaleur);
                    $res->valueStr = $obj->call_function_chronoModule($this->model_refid, $this->id);
                    $res->valueHtml = $obj->call_function_chronoModule($this->model_refid, $this->id);
                }
            } else {
                //Construct Form
                $html = "";
                if ($res->valueIsChecked && $res->value == 1) {
                    $html .= "OUI";
                } else if ($res->valueIsChecked && $res->value != 1) {
                    $html .= "NON";
                } else {
                    $html .= $res->value;
                }
                if ($res->type_valeur == 3)
                    $res->valueHtml = dol_print_date($html, "dayhour", 'gmt');
                else
                    $res->valueHtml = $html;
                $res->valueStr = $html;
            }

            $res->valueStr = str_replace("<br/>", "\n", $res->valueStr);
            $this->valuesPlus[$res->id] = $res;
        }
    }

    private function idChampToNom($id = null, $nom = null) {
        if (is_null($id) && is_null($nom))
            die("Idchamptonom pas de paramétre");
        if (!$this->model_refid > 0)
            die("Pas de model type");

        if (!isset(self::$tabIdToNomChamp[$this->model_refid])) {
            self::$tabIdToNomChamp[$this->model_refid] = array();
            self::$tabNomNomChampToId[$this->model_refid] = array();

            $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "synopsischrono_key
                     WHERE model_refid = " . $this->model_refid;
            $sql = $this->db->query($requete);
            while ($res = $this->db->fetch_object($sql)) {
                self::$tabIdToNomChamp[$this->model_refid][$res->id] = traiteCarac($res->nom);
                self::$tabNomNomChampToId[$this->model_refid][traiteCarac($res->nom)] = $res->id;
            }
        }

        if (isset($id))
            return self::$tabIdToNomChamp[$this->model_refid][$id];
        else {
            return self::$tabNomNomChampToId[$this->model_refid][traiteCarac($nom)];
        }
    }

    public function setDatas($chrono_id, $dataArr) {
        $this->db->begin();
        //Delete datas
        $retVal = false;


//        print_r($dataArr);die;

        $tabUpdate = array();
        foreach ($dataArr as $keyId => $value) {
            $value = convertirDate($value, false);
            echo $value;

            if (is_numeric($keyId))
                $keyId = $this->idChampToNom($keyId);

            $tabUpdate[] = $keyId . " = '" . addslashes($value) . "'";



            //Set Value
//            $value = addslashes($value);
//            $requete = "SELECT *
//                          FROM " . MAIN_DB_PREFIX . "synopsischrono_value
//                         WHERE key_id = " . $keyId . "
//                           AND chrono_refid = " . $chrono_id;
//            $sql = $this->db->query($requete);
//            if ($this->db->num_rows($sql) > 0) {
//                $requete = "UPDATE `" . MAIN_DB_PREFIX . "synopsischrono_value`
//                               SET `value`='" . $value . "'
//                             WHERE key_id = " . $keyId . "
//                               AND chrono_refid = " . $chrono_id;
//            } else {
//
//                $requete = "INSERT INTO `" . MAIN_DB_PREFIX . "synopsischrono_value`
//                                        (`value`,`chrono_refid`,`key_id`)
//                                        VALUES
//                                        ('" . $value . "', " . $chrono_id . ", " . $keyId . ")";
//            }
//            $sql = $this->db->query($requete);
//            if ($sql) {
//                $retVal = true;
//            } else {
//                $retVal = false;
//                $this->db->rollback();
//                return (-1);
//            }
        }//die;

        $retVal = $this->db->query("UPDATE " . MAIN_DB_PREFIX . "synopsischrono_chrono_" . $this->model_refid . " SET " . implode(", ", $tabUpdate) . " WHERE id =" . $this->id);


        $this->db->commit();
        return($retVal);
    }

    //retourne l'id et le template_id de la clef
    public function getKeyId($nom, $template_id) {
        return ($template_id);
    }

}

class ChronoRef {

    public $db;
    public $titre;
    public $description;
    public $hasFile;
    public $hasContact;
    public $hasSociete;
    public $hasRevision;
    public $hasDescription;
    public $nomDescription;
    public $typeDescription;
    public $hasPropal;
    public $hasProjet;
    public $modele;
    public $date_create;
    public $tms;
    public $active;
    public $revision_model_refid;
    public $maxForNbDoc = 8;

    public function ChronoRef($DB) {
        $this->db = $DB;
        $this->propInList = 0;
        $this->descInList = 0;
    }

    public function fetch($id) {
        $this->id = $id;
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_conf WHERE id = " . $id;
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql)) {
            $this->titre = $res->titre;
            $this->description = $res->description;
            $this->hasFile = $res->hasFile;
            $this->hasContact = $res->hasContact;
            $this->hasSociete = $res->hasSociete;
            $this->hasRevision = $res->hasRevision;
            $this->hasDescription = $res->hasDescription;
            $this->typeDescription = $res->typeDescription;
            $this->nomDescription = $res->nomDescription;
            $this->hasMultipleValue = $res->hasMultipleValue;
            $this->hasStatut = $res->hasStatut;
            $this->hasSuivie = $res->hasSuivie;
            $this->hasPropal = $res->hasPropal;
            $this->hasProjet = $res->hasProjet;
            $this->modele = $res->modele;
            $this->date_create = $res->date_create;
            $this->tms = $res->tms;
            $this->revision_model_refid = $res->revision_model_refid;
            $this->active = $res->active;
            $this->picto = (isset($res->picto) && $res->picto != "") ? $res->picto : 'chrono@synopsischrono';
        }
    }

}

?>