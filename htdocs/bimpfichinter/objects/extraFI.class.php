<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

abstract class extraFI extends BimpDolObject
{

    public static $moduleRightsName = "synopsisficheinter";
    /*
     * Gestion des droits
     */

    public function canView()
    {
        return $this->getDolRights("lire");
    }

    public function canEdit()
    {
        $parent = $this->getParentInstance();
        if (is_object($parent) && $parent->isLoaded())
            return $parent->can("edit");

        if ($this->canEditAll())
            return 1;
        if ($this->getInitData("fk_statut") > 0)
            return 0;

        return $this->getDolRights("creer");
    }

    public function canEditAll()
    {
        return ($this->getDolRights("modifAfterValid") || $this->getDolRights("edit_after_validation")) ? 1 : 0;
    }

    public function canDelete()
    {
        return $this->getDolRights("supprimer");
    }

    public function canCreate()
    {
        return ($this->can("edit") && $this->getDolRights("creer"));
    }

    public function canViewPrice()
    {
        return ($this->getDolRights("voirPrix") || $this->getDolRights("config")) ? 1 : 0;
    }

    public function getDolRights($nom)
    {
        global $user;
        $module = self::$moduleRightsName;
        return (isset($user->rights->$module->$nom)) ? 1 : 0;
    }
    /*
     * Gestion des etras
     */

    public function getListExtra($key)
    {
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_values_choice WHERE key_refid = " . $key;
        $sql1 = $this->db->db->query($requete);
        $return = array();
        while ($res1 = $this->db->db->fetch_object($sql1)) {
            $return[$res1->value] = array('label' => $res1->label, 'icon' => 'fas_file-alt', 'classes' => array('warning'));
        }
        return $return;
    }

    public function getExtra37_listArray()
    {
        return $this->getListExtra(37);
    }

    public function getExtra($field)
    {
        $field = str_replace("extra", "", $field);
        if ($this->isLoaded()) {
            if (!$this->extraFetch) {
                $this->dol_object->fetch_extra();
                $this->extraFetch = true;
            }
            return $this->dol_object->extraArr[$field];
        }
    }

    public function insertExtraFields()
    {
        if (!is_object($this->dol_object)) {
            $this->dol_object = new Synopsisfichinter($this->db->db);
            $this->dol_object->id = $this->id;
        }
        $this->updateExtraFields();

        return parent::insertExtraFields();
    }

    public function updateExtraFields()
    {
        $list = $this->getExtraFields();
        foreach ($list as $extra)
            if ($this->getData($extra) != $this->getInitData($extra))
                $this->updateExtraField($extra, $this->getData($extra), 0);

        return parent::updateExtraFields();
    }

    public function updateExtraField($field_name, $value, $id_object)
    {
        if ($id_object == $this->dol_object->id || $id_object == 0) {
            if (stripos($field_name, "extra") !== false) {
                $field = str_replace("extra", "", $field_name);
                if (in_array($field, array(24, 25, 26, 27))) {
                    $value = traiteHeure($value);
                    $this->set($field_name, $value);
                }
                $this->dol_object->setExtra($field, $value);
            }
        }

        return parent::updateExtraField($field_name, $value, $id_object);
    }

    public function fetchExtraFields()
    {
        $return = parent::fetchExtraFields();
        
        $list = $this->getExtraFields();
        foreach ($list as $extra) {
            $return[$extra] = $this->getExtra($extra);
        }

        return $return;
    }
    
    public function getExtraFieldSavedValue($field, $id_object)
    {
        return $this->getInitData($field);
    }

    public function asParentCommande()
    {
        $parent = $this->getParentInstance();
        if ($parent->isLoaded())
            return ($parent->getData("fk_commande") > 0) ? 1 : 0;
        return 0;
    }

    public function asParentContrat()
    {
        $parent = $this->getParentInstance();
        if ($parent->isLoaded())
            return ($parent->getData("fk_contrat") > 0) ? 1 : 0;
        return 0;
    }

    public function traitePriceProd(&$warnings)
    {
        if ($this->getData("fk_commandedet") > 0) {//on est en mode commande
            if ($this->getData("fk_commandedet") != $this->getInitData("fk_commandedet")) {//on a changé de ligne commande
                $sql = $this->db->db->query("SELECT subprice FROM `" . MAIN_DB_PREFIX . "commandedet` WHERE `rowid` = " . $this->getData("fk_commandedet"));
//                die("SELECT subprice FROM `".MAIN_DB_PREFIX."commandedet` WHERE `rowid` = ".$this->getData("fk_commandedet"));
                while ($ln = $this->db->db->fetch_object($sql)) {
                    $this->set("pu_ht", $ln->subprice);
                    $this->data["pu_ht"] = $ln->subprice;
                    $warnings[] = "Prix de la ligne maj avec prix commande";
                }
            }
        } elseif ($this->getData("fk_contratdet") > 0) {//on est en mode contrat
            if ($this->getData("fk_contratdet") != $this->getInitData("fk_contratdet") || $this->getData("fk_commandedet") != $this->getInitData("fk_commandedet")) {//on a changé de ligne commande
                $sql = $this->db->db->query("SELECT subprice FROM `" . MAIN_DB_PREFIX . "contratdet` WHERE `rowid` = " . $this->getData("fk_contratdet"));
//                die("SELECT subprice FROM `".MAIN_DB_PREFIX."commandedet` WHERE `rowid` = ".$this->getData("fk_commandedet"));
                while ($ln = $this->db->db->fetch_object($sql)) {
                    $this->set("pu_ht", $ln->subprice);
                    $warnings[] = "Prix de la ligne maj avec prix contrat";
                }
            }
        }
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $this->traitePriceProd($warnings);

        return parent::update($warnings, $force_update);
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $this->traitePriceProd($warnings);
        return parent::create($warnings, $force_create);
    }
}
