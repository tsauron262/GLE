<?php

class Bimp_Societe extends BimpObject
{

    public static $types_ent_list = null;
    public static $types_ent_list_code = null;
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

    public function isCompany()
    {
        $id_typeent = (int) $this->getData('fk_typent');
        if ($id_typeent) {
            if (!in_array($this->db->getValue('c_typent', 'code', '`id` = ' . $id_typeent), array('TE_UNKNOWN', 'TE_PRIVATE', 'TE_OTHER'))) {
                return 1;
            }
        }

        return 0;
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

    public function getNumSepa()
    {


        if ($this->getData('num_sepa') == "") {
            $new = BimpTools::getNextRef('societe_extrafields', 'num_sepa', 'FR02ZZZ008801-', 7);
            $this->updateField('num_sepa', $new);
            $this->update();
        }
        return $this->getData('num_sepa');
    }

    public function canBuy(&$errors = array(), $msgToError = true)
    {
        self::getTypes_entArray();
        $type_ent_sans_verif = array("TE_PRIVATE", "TE_ADMIN");
        if (!isset(self::$types_ent_list_code[$this->getData("fk_typent")]) || !in_array(self::$types_ent_list_code[$this->getData("fk_typent")], $type_ent_sans_verif)) {
            /*
             * Entreprise onf fait les verifs...
             */
            if ($this->getData('fk_pays') == 1 || $this->getData('fk_pays') < 1)
                if (strlen($this->getData("siret")) != 14 || !$this->Luhn($this->getData("siret"), 14)) {
                    $errors[] = "Siret client invalide :" . $this->getData("siret");
                }
        }
        if ($this->getData('zip') == '' || $this->getData('town') == '' || $this->getData('address') == '')
            $errors[] = "Merci de renseigner l'adresse complète du client";


        if (self::$types_ent_list_code[$this->getData("fk_typent")] != "TE_PRIVATE") {
            if ($this->getData("mode_reglement") < 1) {
                $errors[] = "Mode réglement fiche client invalide ";
            }
            if ($this->getData("cond_reglement_id") < 1) {
                $errors[] = "Condition réglement fiche client invalide ";
            }
        }

        if (count($errors))
            return 0;
        
        return 1;
    }

    public function Luhn($numero, $longueur)
    {
        // On passe à la fonction la variable contenant le numéro à vérifier
        // et la longueur qu'il doit impérativement avoir

        if ((strlen($numero) == $longueur) && preg_match("#[0-9]{" . $longueur . "}#i", $numero)) {
            // si la longueur est bonne et que l'on n'a que des chiffres

            /* on décompose le numéro dans un tableau  */
            for ($i = 0; $i < $longueur; $i++) {
                $tableauChiffresNumero[$i] = substr($numero, $i, 1);
            }

            /* on parcours le tableau pour additionner les chiffres */
            $luhn = 0; // clef de luhn à tester
            for ($i = 0; $i < $longueur; $i++) {
                if ($i % 2 == 0) { // si le rang est pair (0,2,4 etc.)
                    if (($tableauChiffresNumero[$i] * 2) > 9) {
                        // On regarde si son double est > à 9
                        $tableauChiffresNumero[$i] = ($tableauChiffresNumero[$i] * 2) - 9;
                        //si oui on lui retire 9
                        // et on remplace la valeur
                        // par ce double corrigé
                    } else {

                        $tableauChiffresNumero[$i] = $tableauChiffresNumero[$i] * 2;
                        // si non on remplace la valeur
                        // par le double
                    }
                }
                $luhn = $luhn + $tableauChiffresNumero[$i];
                // on additionne le chiffre à la clef de luhn
            }

            /* test de la divition par 10 */
            if ($luhn % 10 == 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
            // la valeur fournie n'est pas conforme (caractère non numérique ou mauvaise
            // longueur)
        }
    }

    public function getTypes_entArray()
    {
        if (is_null(self::$types_ent_list)) {
            $sql = 'SELECT `id`, `libelle`, `code` FROM ' . MAIN_DB_PREFIX . 'c_typent WHERE `active` = 1';
            $rows = $this->db->executeS($sql, 'array');

            $types = array();
            $typesCode = array();
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $types[(int) $r['id']] = $r['libelle'];
                    $typesCode[(int) $r['id']] = $r['code'];
                }
            }
            self::$types_ent_list = $types;
            self::$types_ent_list_code = $typesCode;
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

    public function getAvailableDiscountsAmounts($is_fourn = false, $allowed = array())
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

                $and_where = '';
                if (isset($allowed['factures']) && !empty($allowed['factures'])) {
                    $and_where = ' AND fdet.fk_facture NOT IN (' . implode(',', $allowed['factures']) . ')';
                }

                $sql .= ' AND (SELECT COUNT(fdet.rowid) FROM ' . MAIN_DB_PREFIX . 'facturedet fdet WHERE fdet.fk_remise_except = r.rowid' . $and_where . ') = 0';

                $and_where = '';
                if (isset($allowed['commandes']) && !empty($allowed['commandes'])) {
                    $and_where = ' AND cdet.fk_commande NOT IN (' . implode(',', $allowed['commandes']) . ')';
                }

                $sql .= ' AND (SELECT COUNT(cdet.rowid) FROM ' . MAIN_DB_PREFIX . 'commandedet cdet WHERE cdet.fk_remise_except = r.rowid' . $and_where . ') = 0';

                $and_where = '';
                if (isset($allowed['propales']) && !empty($allowed['propales'])) {
                    $and_where = ' AND pdet.fk_propal NOT IN (' . implode(',', $allowed['propales']) . ')';
                }

                $sql .= ' AND (SELECT COUNT(pdet.rowid) FROM ' . MAIN_DB_PREFIX . 'propaldet pdet WHERE pdet.fk_remise_except = r.rowid' . $and_where . ') = 0';
            }

            $result = $this->db->executeS($sql, 'array');

            if (isset($result[0]['amount'])) {
                return (float) $result[0]['amount'];
            }
        }

        return 0;
    }

    public function getAvailableDiscountsArray($is_fourn = false, $allowed = array())
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
            }

            $rows = $this->db->executeS($sql, 'array');

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $disabled_label = static::getDiscountUsedLabel((int) $r['id'], false, $allowed);

                    $discounts[(int) $r['id']] = array(
                        'label'    => BimpTools::getRemiseExceptLabel($r['description']) . ' (' . BimpTools::displayMoneyValue((float) $r['amount'], '') . ' TTC)' . ($disabled_label ? ' - ' . $disabled_label : ''),
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

    public static function getDiscountUsedLabel($id_discount, $with_nom_url = false, $allowed = array())
    {
        $use_label = '';

        if (!(int) $id_discount) {
            return $use_label;
        }

        $bdb = BimpCache::getBdb();

        if (!class_exists('DiscountAbsolute')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
        }

        $discount = new DiscountAbsolute($bdb->db);
        $discount->fetch((int) $id_discount);

        if (BimpObject::objectLoaded($discount)) {
            if ((int) $discount->fk_invoice_supplier_source) {
                // Remise fournisseur
                $id_facture_fourn = 0;
                if ((isset($discount->fk_invoice_supplier) && (int) $discount->fk_invoice_supplier)) {
                    $id_facture_fourn = (int) $discount->fk_invoice_supplier;
                } elseif (isset($discount->fk_invoice_supplier_line) && (int) $discount->fk_invoice_supplier_line) {
                    $id_facture_fourn = (int) $this->db->getValue('facture_fourn_det', 'fk_facture_fourn', 'rowid = ' . (int) $discount->fk_invoice_supplier_line);
                }

                if ($id_facture_fourn) {
                    $factureFourn = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', $id_facture_fourn);
                    if (BimpObject::objectLoaded($factureFourn)) {
                        $use_label = 'Ajouté à la facture fournisseur ' . ($with_nom_url ? $factureFourn->getNomUrl(0, 1, 1, 'full') : '"' . $factureFourn->getRef() . '"');
                    } else {
                        $use_label .= 'Ajouté à la facture fournisseur #' . $id_facture_fourn;
                    }
                }
            } else {
                // Remise client
                // On ne tient pas compte de $allowed dans les deux cas suivants: 
                $id_facture = 0;
                if ((isset($discount->fk_facture) && (int) $discount->fk_facture)) {
                    $id_facture = (int) $discount->fk_facture;
                } elseif (isset($discount->fk_facture_line) && (int) $discount->fk_facture_line) {
                    $id_facture = (int) $this->db->getValue('facturedet', 'fk_facture', 'rowid = ' . (int) $discount->fk_facture_line);
                }

                if ($id_facture) {
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                    if (BimpObject::objectLoaded($facture)) {
                        $use_label = 'Ajouté à la facture ' . ($with_nom_url ? $facture->getNomUrl(0, 1, 1, 'full') : '"' . $facture->getRef() . '"');
                    } else {
                        $use_label .= 'Ajouté à la facture #' . $id_facture;
                    }
                } else {
                    $rows = $bdb->getRows('facturedet', 'fk_remise_except = ' . (int) $id_discount, null, 'array', array('fk_facture'));
                    if (is_array($rows)) {
                        foreach ($rows as $r) {
                            if (!isset($allowed['factures']) || !in_array((int) $r['fk_facture'], $allowed['factures'])) {
                                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['fk_facture']);
                                if (BimpObject::objectLoaded($facture)) {
                                    $use_label = 'Ajouté à la facture ' . ($with_nom_url ? $facture->getNomUrl(1, 1, 1, 'full') : '"' . $facture->getRef() . '"');
                                    break;
                                } else {
                                    $bdb->delete('facturedet', '`fk_facture` = ' . $r['fk_facture'] . ' AND `fk_remise_except` = ' . (int) $id_discount);
                                }
                            }
                        }
                    }

                    if (!$use_label) {
                        $rows = $bdb->getRows('commandedet', 'fk_remise_except = ' . (int) $id_discount, null, 'array', array('fk_commande'));
                        if (is_array($rows)) {
                            foreach ($rows as $r) {
                                if (!isset($allowed['commandes']) || !in_array((int) $r['fk_commande'], $allowed['commandes'])) {
                                    $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $r['fk_commande']);
                                    if (BimpObject::objectLoaded($commande)) {
                                        $use_label = 'Ajouté à la commande ' . ($with_nom_url ? $commande->getNomUrl(1, 1, 1, 'full') : '"' . $commande->getRef() . '"');
                                        break;
                                    } else {
                                        $bdb->delete('commandedet', '`fk_commande` = ' . $r['fk_commande'] . ' AND `fk_remise_except` = ' . (int) $id_discount);
                                    }
                                }
                            }
                        }
                    }

                    if (!$use_label) {
                        $rows = $bdb->getRows('propaldet', 'fk_remise_except = ' . (int) $id_discount, null, 'array', array('fk_propal'));
                        if (is_array($rows)) {
                            foreach ($rows as $r) {
                                if (!isset($allowed['propales']) || !in_array((int) $r['fk_propal'], $allowed['propales'])) {
                                    $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $r['fk_propal']);
                                    if (BimpObject::objectLoaded($propal)) {
                                        if (!in_array($propal->getData('fk_statut'), array(4, 3))) {
                                            if (!(int) $bdb->getValue('element_element', 'rowid', '`fk_source` = ' . $r['fk_propal'] . ' AND `sourcetype` = \'propal\'  AND `targettype` = \'commande\'')) {
                                                $use_label = 'Ajouté à la propale ' . ($with_nom_url ? $propal->getNomUrl(1, 1, 1, 'full') : '"' . $propal->getRef() . '"');
                                                break;
                                            }
                                        }
                                    } else {
                                        $bdb->delete('propaldet', '`fk_propal` = ' . $r['fk_propal'] . ' AND `fk_remise_except` = ' . (int) $id_discount);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $use_label;
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
