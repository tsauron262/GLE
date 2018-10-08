<?php

class Bimp_Societe extends BimpObject
{

    public static $types_ent_list = null;
    public static $effectifs_list = null;
    public $forceTpye = "client";

    public function __construct($module, $object_name)
    {
        global $langs;
        $langs->load("companies");
        $langs->load("commercial");
        $langs->load("bills");
        $langs->load("banks");
        $langs->load("users");

        parent::__construct($module, $object_name);
    }

    public function getSocieteLabel()
    {
        $client = $this->getData('client');
        if ($this->forceTpye == "client" || (!is_null($client) && (int) $client > 0)) {
            return 'client';
        }

        $fournisseur = $this->getData('fournisseur');
        if ($this->forceTpye == "fourn" || (!is_null($fournisseur) && (int) $fournisseur > 0)) {
            return 'fournisseur';
        }

        return 'societe';
    }

    public function getSocieteIsFemale()
    {
        $client = $this->getData('client');
        if ($this->forceTpye == "client" || (!is_null($client) && (int) $client > 0)) {
            return 0;
        }

        $fournisseur = $this->getData('fournisseur');
        if ($this->forceTpye == "fourn" || (!is_null($fournisseur) && (int) $fournisseur > 0)) {
            return 0;
        }

        return 1;
    }

    public function getTypes_entArray()
    {
        if (is_null(self::$types_ent_list)) {
            $sql = 'SELECT `id`, `libelle` FROM ' . MAIN_DB_PREFIX . 'c_typent WHERE `active` = 1';
            $rows = $this->db->executeS($sql, 'array');

            $types = array();
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $types[(int) $r['id']] = $r['libelle'];
                }
            }
            self::$types_ent_list = $types;
        }

        return self::$types_ent_list;
    }

    public function getEffectifsArray()
    {
        if (is_null(self::$effectifs_list)) {
            $sql = 'SELECT `id`, `libelle` FROM ' . MAIN_DB_PREFIX . 'c_effectif WHERE `active` = 1';
            $rows = $this->db->executeS($sql, 'array');

            $effectifs = array();
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $effectifs[(int) $r['id']] = $r['libelle'];
                }
            }

            self::$effectifs_list = $effectifs;
        }

        return self::$effectifs_list;
    }

    public function getCountryCode()
    {
        $fk_pays = (int) $this->getData('fk_pays');
        if ($fk_pays) {
            return $this->db->getValue('c_country', 'code', '`rowid` = ' . (int) $fk_pays);
        }
    }

    public function getContactsList()
    {
        $contacts = array();

        if ($this->isLoaded()) {
            $where = '`fk_soc` = ' . (int) $this->id;
            $rows = $this->db->getRows('socpeople', $where, null, 'array', array('rowid', 'firstname', 'lastname'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $contacts[(int) $r['rowid']] = BimpTools::ucfirst($r['firstname']) . ' ' . strtoupper($r['lastname']);
                }
            }
        }

        return $contacts;
    }

    public function displayCountry()
    {
        $id = $this->getData('fk_pays');
        if (!is_null($id) && $id) {
            return $this->db->getValue('c_country', 'label', '`rowid` = ' . (int) $id);
        }
        return '';
    }

    public function displayDepartement()
    {
        $fk_dep = (int) $this->getData('fk_departement');
        if ($fk_dep) {
            return $this->db->getValue('c_departements', 'nom', '`rowid` = ' . $fk_dep);
        }
        return '';
    }

    public function displayJuridicalStatus()
    {
        if ($this->isLoaded()) {
            $fk_fj = (int) $this->getData('fk_forme_juridique');
            if ($fk_fj) {
                return $this->db->getValue('c_forme_juridique', 'libelle', '`code` = ' . $fk_fj);
            }
        }

        return '';
    }

    protected function getDolObjectUpdateParams()
    {
        global $user;
        return array($this->id, $user);
    }

    // Overrides: 

    public function validatePost()
    {
        $errors = parent::validatePost();

        if (!count($errors)) {
            if (BimpTools::isSubmit('prenom')) {
                $prenom = BimpTools::getValue('prenom', '');
                if ($prenom) {
                    $nom = strtoupper($this->getData('nom')) . ' ' . BimpTools::ucfirst($prenom);
                    $this->set('nom', $nom);
                    $this->set('fk_typent', 8);
                }
            }
        }

        return $errors;
    }
}
