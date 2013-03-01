<?php

require_once(DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

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
    public $user_author_id;
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

    public function Chrono($DB) {
        $this->db = $DB;
    }

    public function fetch($id) {
        global $conf;
        if ($conf->global->MAIN_MODULE_SYNOPSISCHRONO) {
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono WHERE id = " . $id;
            $sql = $this->db->query($requete);
            $res = $this->db->fetch_object($sql);
            if ($res) {
                $this->id = $id;
                $this->date = strtotime($res->date_create);
                $this->date_modif = strtotime($res->tms);
                $this->socid = $res->fk_societe;
                $this->statut = $res->fk_statut;
                $this->validation_number = $res->validation_number;
                if ($this->socid > 0) {
                    $soc = new Societe($this->db);
                    $soc->fetch($this->socid);
                    $this->societe = $soc;
                }
                $this->user_author_id = $res->fk_user_author;
                if ($this->user_author_id > 0) {
                    $tmpUser = new User($this->db);
                    $tmpUser->fetch($this->user_author_id);
                    $this->user_author = $tmpUser;
                }
                $this->user_modif_id = $res->fk_user_modif;
                if ($this->user_modif_id > 0) {
                    $tmpUser = new User($this->db);
                    $tmpUser->fetch($this->user_modif_id);
                    $this->user_modif = $tmpUser;
                }

                $this->contactid = $res->fk_socpeople;
                if ($this->contactid > 0) {
                    require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
                    $contact = new Contact($this->db);
                    $contact->fetch($this->contactid);
                    $this->contact = $contact;
                }
                $this->file_path = $res->file_path;
                $this->description = $res->description;
                $this->model_refid = $res->model_refid;
                $this->propalid = $res->propalid;
                $this->projetid = $res->projetid;
                $this->orig_ref = $res->orig_ref;
                $this->ref = $res->ref;
                $this->revision = ($res->revision > 0 ? $res->revision : false);
                if ($this->model_refid > 0) {
                    $this->model = new ChronoRef($this->db);
                    $this->model->fetch($res->model_refid);
                    $this->mask = $this->model->modele;
                }
                global $user;
                $this->getRights($user);
                return($id);
            } else {
                return -1;
            }
        } else {
            return -1;
        }
    }

    public function attenteValidate() {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Chrono SET fk_statut = 999 WHERE id = " . $this->id;
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
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_rights_def WHERE isValidationRight =1 AND isValidationForAll <> 1";
        $sql = $this->db->query($requete);
        $arrValid = array();
        while ($res = $this->db->fetch_object($sql)) {
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_Multivalidation WHERE validation_number " . ($this->validation_number > 0 ? " = " . $this->validation_number : " IS NULL ") . " AND chrono_refid = " . $this->id . " AND right_refid = " . $res->id;
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
            $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Chrono SET validation_number = ifnull(validation_number,0) + 1 WHERE id = " . $this->id;
            $sql = $this->db->query($requete);
            return $this->unvalidate();
        } else if ($all1) {
            return $this->validate();
        }
    }

    public function validate() {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Chrono SET fk_statut = 2 WHERE id = " . $this->id;
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
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Chrono SET fk_statut = 0 WHERE id = " . $this->id;
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
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Chrono SET fk_statut = 3 WHERE id = " . $this->id;
        $sqlA = $this->db->query($requete);
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
        $newRef = substr($this->ref, 0, 7) . "-" . $revision;
        if ($this->orig_ref . "x" != "x") {
            $newRef = substr($this->orig_ref, 0, 7) . "-" . $revision;
        }
        //Nouvelle revision
        $newId = $this->create_revision_from($newRef, ($this->revision ? $this->revision + 1 : 1));
        //Copie extra value
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_value WHERE chrono_refid = " . $_REQUEST['id'];
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql)) {
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Chrono_value
                                    ( chrono_refid,
                                      value,
                                      key_id)
                             VALUES ( " . $newId . " ,
                                      '" . addslashes($res->value) . "' ,
                                      " . $res->key_id . " )";
            $sql1 = $this->db->query($requete);
        }
        if ($sqlA && $newId > 0) {
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
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Chrono SET description = '" . $description . "'";
        if ($socid)
            $requete .= ", fk_societe =  " . $socid;
        else
            $requete .= ", fk_societe = NULL ";
        if ($contactid)
            $requete .= ", fk_socpeople =  " . $contactid;
        else
            $requete .= ", fk_socpeople = NULL ";
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
$langs->load("chrono@Synopsis_Chrono");

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
        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_value WHERE chrono_refid = " . $id;
        $sql = $this->db->query($requete);
        $requete1 = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono WHERE id = " . $id;
        $sql1 = $this->db->query($requete1);

        if ($sql && $sql1) {
            $this->db->commit();
            return (1);
        } else {
            $this->db->rollback();
            return (-1);
        }
    }

    public function getRights($userObj) {
        $requete = "SELECT concat('chrono'," . $this->model_refid . ") as p,
                           d.code,
                           'chrono_user' as pr,
                           r.valeur
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_rights_def as d
                 LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Chrono_rights as r ON user_refid = " . $userObj->id . " AND chrono_refid = " . $this->model_refid . " AND r.right_refid = d.id
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


        return $userObj;
    }

    public function getGrpRights($grpObj) {
        $requete = "SELECT concat('chrono'," . $this->model_refid . ") as p,
                           d.code,
                           'chrono_group' as pr,
                           r.valeur
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_rights_def as d
                 LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Chrono_group_rights as r ON group_refid = " . $grpObj->id . " AND chrono_refid = " . $this->model_refid . " AND r.right_refid = d.id
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

        $result = '';

        $lien = '<a title="' . $this->ref . " : " . $this->description . '" href="' . DOL_URL_ROOT . '/Synopsis_Chrono/fiche.php?id=' . $this->id . '">';
        $lienfin = '</a>';

        if ($option == 6) {
            $lien = '<a title="' . $this->nom . '" href="' . DOL_URL_ROOT . '/Synopsis_Chrono/fiche.php?id=' . $this->id . '">';
            $lienfin = '</a>';
        }
        if ($option == 6 && $withpicto) {
            $result.=($lien . img_object($langs->trans("Chrono") . ': ' . $this->ref, 'chrono@Synopsis_Chrono', false, false, false, true) . $lienfin . ' ');
        } else if ($withpicto)
            $result.=($lien . img_object($langs->trans("ShowChrono") . ': ' . $this->ref, 'chrono@Synopsis_Chrono') . $lienfin . ' ');

        $result.=$lien . ($maxlen ? dol_trunc($this->ref, $maxlen) : $this->ref) . $lienfin;
        return $result;
    }

    private function getModeleMask($modelId = false) {
        if (!$modelId)
            $modelId = $this->model_refid;
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_conf WHERE id = " . $modelId;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        return($res->modele);
    }

    private function getNextNumRef() {
        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Chrono/mod_chrono_serpentine.class.php");
        $tmp = new mod_chrono_serpentine($this->db);
        $objsoc = false;
        if ($this->socid > 0) {
            $objsoc = new Societe($this->db);
            $objsoc->fetch($this->socid);
        }
        return($tmp->getNextValue($objsoc, $this, $this->getModeleMask()));
    }

    public function create() {
        global $user;
        $ref = $this->getNextNumRef();
        //$propid = $this->prop;
        //$propal = "SELECT rowid FROM ".MAIN_DB_PREFIX."propal WHERE ref=".$this->propalid;
        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Chrono (date_create,ref,model_refid,description,fk_societe,fk_socpeople,propalid,projetid,fk_user_author)
                          VALUES (now(),'" . $ref . "','" . $this->model_refid . "','" . $this->description . "'," . ($this->socid > 0 ? $this->socid : 'NULL') . "," . ($this->contactid > 0 ? $this->contactid : 'NULL') . ",'" . $this->propalid . "','" . $this->projetid . "', " . $user->id . ")";
        $sql = $this->db->query($requete);
        if ($sql) {
            $this->id = $this->db->last_insert_id("" . MAIN_DB_PREFIX . "Synopsis_Chrono");
            return ($this->id);
        } else {
            return (-1);
            print "$requete";
        }
    }

    public function create_revision_from($newRef, $revision) {
        global $user;
        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Chrono
                                ( date_create,
                                  ref,
                                  model_refid,
                                  description,
                                  fk_societe,
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
            $this->id = $this->db->last_insert_id("" . MAIN_DB_PREFIX . "Synopsis_Chrono");
            return ($this->id);
        } else {
            return (-1);
        }
    }

    public function getKeys() {
        $this->keysListId = array();
        $this->keysList = array();
        $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_key
                     WHERE model_refid = " . $this->model_refid;
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql)) {
            $this->keysList[$res->id] = array("key_id" => $this->id, "nom" => $res->nom, "description" => $res->description, "type" => $res->type, "inDetList" => $res->inDetList);
            $this->keysListId[] = $res->id;
        }
    }

    public function getValues($chrono_id) {
        if (count($this->keysListId) < 1) {
            $this->getKeys();
        }
        $keyStr = join(',', $this->keysListId);

        $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_value
                     WHERE chrono_refid = " . $chrono_id . " AND key_id in (" . $keyStr . ")";
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql)) {
            $value = $res->value;
            $key = $this->keysList[$res->key_id]['nom'];
            $desc = $this->keysList[$res->key_id]['description'];
            $this->extraValue[$chrono_id][$key] = array('value' => $value, 'description' => $desc);
        }
    }

    public function setDatas($chrono_id, $dataArr) {
        $this->db->begin();
        //Delete datas
        $retVal = false;
        foreach ($dataArr as $keyId => $value) {
            //Set Value
            $value = addslashes($value);
            $requete = "SELECT *
                          FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_value
                         WHERE key_id = " . $keyId . "
                           AND chrono_refid = " . $chrono_id;
            $sql = $this->db->query($requete);
            if ($this->db->num_rows($sql) > 0) {
                $requete = "UPDATE `" . MAIN_DB_PREFIX . "Synopsis_Chrono_value`
                               SET `value`='" . $value . "'
                             WHERE key_id = " . $keyId . "
                               AND chrono_refid = " . $chrono_id;
            } else {

                $requete = "INSERT INTO `" . MAIN_DB_PREFIX . "Synopsis_Chrono_value`
                                        (`value`,`chrono_refid`,`key_id`)
                                        VALUES
                                        ('" . $value . "', " . $chrono_id . ", " . $keyId . ")";
            }
            $sql = $this->db->query($requete);
            if ($sql) {
                $retVal = true;
            } else {
                $retVal = false;
                $this->db->rollback();
                return (-1);
            }
        }
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
    public $modele;
    public $date_create;
    public $tms;
    public $active;
    public $revision_model_refid;

    public function ChronoRef($DB) {
        $this->db = $DB;
    }

    public function fetch($id) {
        $this->id = $id;
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_conf WHERE id = " . $id;
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql)) {
            $this->titre = $res->titre;
            $this->description = $res->description;
            $this->hasFile = $res->hasFile;
            $this->hasContact = $res->hasContact;
            $this->hasSociete = $res->hasSociete;
            $this->hasRevision = $res->hasRevision;
            $this->modele = $res->modele;
            $this->date_create = $res->date_create;
            $this->tms = $res->tms;
            $this->revision_model_refid = $res->revision_model_refid;
            $this->active = $res->active;
        }
    }

}

?>