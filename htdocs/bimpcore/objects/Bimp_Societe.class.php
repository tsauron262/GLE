<?php

class Bimp_Societe extends BimpObject
{

    public static $types_ent_list = null;
    public static $effectifs_list = null;
    public $soc_type = "";

    public function __construct($module, $object_name)
    {
        global $langs;
        if (isset($langs)) {
            $langs->load("companies");
            $langs->load("commercial");
            $langs->load("bills");
            $langs->load("banks");
            $langs->load("users");
        }

        parent::__construct($module, $object_name);
    }

    public function checkValidity()
    {
        $errors = array();

        return $errors;
    }

    public function getSocieteLabel()
    {
        if ($this->soc_type == "client" || (int) $this->getData('client') > 0) {
            return 'client';
        }

        if ($this->soc_type == "fournisseur" || (int) $this->getData('fournisseur') > 0) {
            return 'fournisseur';
        }

        return 'société';
    }

    public function getSocieteIsFemale()
    {
        if ($this->soc_type == "client" || (int) $this->getData('client') > 0) {
            return 0;
        }

        if ($this->soc_type == "fournisseur" || (int) $this->getData('fournisseur') > 0) {
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

    public function getFilesDir()
    {
        if ($this->isLoaded()) {
            return DOL_DATA_ROOT . '/societe/' . $this->id . '/';
        }
    }

    public function getFileUrl($file_name)
    {
        if (!$file_name) {
            return '';
        }

        if (!$this->isLoaded()) {
            return '';
        }

        $file = $this->id . '/' . $file_name;

        return DOL_URL_ROOT . '/document.php?modulepart=societe&file=' . urlencode($file);
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

    public function getNomUrl($withpicto = true, $ref_only = true, $page_link = false, $modal_view = '')
    {
        return $this->dol_object->getNomUrl(1);
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

    public function getAvailableDiscountsAmounts($is_fourn = false)
    {
        if ($this->isLoaded()) {
            global $conf;

            $sql = 'SELECT SUM(r.amount_ttc) as amount';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe_remise_except r';
            $sql .= ' WHERE r.entity = ' . $conf->entity;
            $sql .= ' AND r.discount_type = ' . ($is_fourn ? 1 : 0);
            $sql .= ' AND r.fk_soc = ' . (int) $this->id;

            if ($is_fourn) {
                $sql .= ' AND (r.fk_invoice_supplier IS NULL AND r.fk_invoice_supplier_line IS NULL)';
            } else {
                $sql .= ' AND (r.fk_facture IS NULL AND r.fk_facture_line IS NULL)';
                $sql .= ' AND (SELECT COUNT(fdet.rowid) FROM ' . MAIN_DB_PREFIX . 'facturedet fdet WHERE fdet.fk_remise_except = r.rowid) = 0';
                $sql .= ' AND (SELECT COUNT(cdet.rowid) FROM ' . MAIN_DB_PREFIX . 'commandedet cdet WHERE cdet.fk_remise_except = r.rowid) = 0';
                $sql .= ' AND (SELECT COUNT(pdet.rowid) FROM ' . MAIN_DB_PREFIX . 'propaldet pdet WHERE pdet.fk_remise_except = r.rowid) = 0';
            }

            $result = $this->db->executeS($sql, 'array');

            if (isset($result[0]['amount'])) {
                return (float) $result[0]['amount'];
            }
        }

        return 0;
    }

    public function getAvailableDiscountsArray($is_fourn = false)
    {
        $discounts = array();

        if ($this->isLoaded()) {
            global $conf;

            $sql = 'SELECT r.rowid as id, r.description, r.amount_ttc as amount';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe_remise_except r';
            $sql .= ' WHERE r.entity = ' . $conf->entity;
            $sql .= ' AND r.discount_type = ' . ($is_fourn ? 1 : 0);
            $sql .= ' AND r.fk_soc = ' . (int) $this->id;

            if ($is_fourn) {
                $sql .= ' AND (r.fk_invoice_supplier IS NULL AND r.fk_invoice_supplier_line IS NULL)';
            } else {
                $sql .= ' AND (r.fk_facture IS NULL AND r.fk_facture_line IS NULL)';
//                $sql .= ' AND (SELECT COUNT(pdet.rowid) FROM ' . MAIN_DB_PREFIX . 'propaldet pdet WHERE pdet.fk_remise_except = r.rowid) = 0';
//                $sql .= ' AND (SELECT COUNT(cdet.rowid) FROM ' . MAIN_DB_PREFIX . 'commandedet cdet WHERE cdet.fk_remise_except = r.rowid) = 0';
            }

            $rows = $this->db->executeS($sql, 'array');

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $disabled_label = '';

                    $id_facture = (int) $this->db->getValue('facturedet', 'fk_facture', 'fk_remise_except = ' . (int) $r['id']);
                    if ($id_facture) {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                        if (BimpObject::objectLoaded($facture)) {
                            $disabled_label = ' - Ajouté à la facture "' . $facture->getRef() . '"';
                        } else {
                            $this->db->delete('facturedet', '`fk_facture` = ' . $id_facture . ' AND `fk_remise_except` = ' . (int) $r['id']);
                        }
                    } else {
                        $id_commande = (int) $this->db->getValue('commandedet', 'fk_commande', 'fk_remise_except = ' . (int) $r['id']);
                        if ($id_commande) {
                            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);
                            if (BimpObject::objectLoaded($commande)) {
                                $disabled_label = ' - Ajouté à la commande "' . $commande->getRef() . '"';
                            } else {
                                $this->db->delete('commandedet', '`fk_commande` = ' . $id_commande . ' AND `fk_remise_except` = ' . (int) $r['id']);
                            }
                        } else {
                            $id_propal = (int) $this->db->getValue('propaldet', 'fk_propal', 'fk_remise_except = ' . (int) $r['id']);
                            if ($id_propal) {
                                $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id_propal);
                                if (BimpObject::objectLoaded($propal)) {
                                    $disabled_label = ' - Ajouté à la propale "' . $propal->getRef() . '"';
                                } else {
                                    $this->db->delete('propaldet', '`fk_propal` = ' . $id_propal . ' AND `fk_remise_except` = ' . (int) $r['id']);
                                }
                            }
                        }
                    }

                    $discounts[(int) $r['id']] = array(
                        'label'    => BimpTools::getRemiseExceptLabel($r['description']) . ' (' . BimpTools::displayMoneyValue((float) $r['amount'], '') . ' TTC)' . $disabled_label,
                        'disabled' => ($disabled_label ? 1 : 0),
                        'data'     => array(
                            'amount_ttc' => (float) $r['amount']
                        )
                    );
                }
            }
        }

        return $discounts;
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
