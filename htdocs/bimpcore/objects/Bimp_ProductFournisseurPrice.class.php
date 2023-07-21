<?php

class Bimp_ProductFournisseurPrice extends BimpObject
{

    public function __construct($module, $object_name)
    {
        if (!class_exists('ProductFournisseur')) {
            require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
        }

        parent::__construct($module, $object_name);
    }

    public function canCreate()
    {
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent)) {
            return ($parent->canCreate() && $parent->canEditPrices());
        }

        return parent::canCreate();
    }

    public function canEdit()
    {
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent)) {
            return ($parent->canEdit() && $parent->canEditPrices());
        }

        return parent::canEdit();
    }

    public function isCreatable($force_create = false, &$errors = array())
    {
        if ($force_create) {
            return 1;
        }

        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent)) {
            return $parent->isEditable($force_create, $errors);
        }

        return 1;
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        if ($force_edit) {
            return 1;
        }

        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent)) {
            return $parent->isEditable($force_edit, $errors);
        }

        return 1;
    }

    public function getRefProperty()
    {
        return 'ref_fourn';
    }

    public function displayLabel()
    {
        $html = '';

        if ($this->isLoaded()) {
            $price = (float) $this->getData('price');
            $tva = (float) $this->getData('tva_tx');
            $fourn = $this->getChildObject('fournisseur');

            $html .= BimpTools::displayMoneyValue($price);
            $html .= ' - TVA: ' . BimpTools::displayFloatValue($tva) . '% ';
            if (BimpObject::objectLoaded($fourn)) {
                $html .= ' - ' . $fourn->getName();
            } else {
                $html .= '<span class="danger">Fournisseur inconnu</span>';
            }
        }

        return $html;
    }

    public function validate()
    {
        $errors = parent::validate();

        $ref_fourn = (string) $this->getData('ref_fourn');

        if (!$ref_fourn) {
            $errors[] = 'Référence fournisseur absente';
        } else {
            $id_fp = (int) $this->db->getValue('product_fournisseur_price', 'rowid', 'fk_soc = ' . (int) $this->getData('fk_soc') . ' AND ref_fourn = \'' . $ref_fourn . '\' AND fk_product != ' . (int) $this->getData('fk_product'));
            if ($id_fp) {
                $errors[] = 'La référence "' . $ref_fourn . '" existe déjà pour un prix d\'achat du même fournisseur mais d\'un autre produit';
            }
        }

        return $errors;
    }

    public function createOrUpdate(&$warnings = array())
    {
        $errors = $this->validate();

        if (count($errors)) {
            return $errors;
        }

        foreach ($this->data as $field => &$value) {
            $this->checkFieldValueType($field, $value);
        }

        $id_product = (int) $this->getData('fk_product');

        $fourn = $this->getChildObject('fournisseur');
        if (is_null($fourn) || !$fourn->isLoaded()) {
            $errors[] = 'Fournisseur absent';
        }

        if (!$id_product) {
            $errors[] = 'Produit absent';
        }

        if (count($errors)) {
            return $errors;
        }

        $pf = new ProductFournisseur($this->db->db);
        if ($pf->fetch($id_product) <= 0) {
            $errors[] = 'Echec du chargement du produit d\'id ' . $id_product;
        } else {
            if ($this->isLoaded()) {
                $pf->fetch_product_fournisseur_price($this->id);
            }

            global $user;

            $ref = $this->getData('ref_fourn');
            $qty = (int) $this->getData('quantity');
            $buyprice = (float) $this->getData('price');
            $tva_tx = (float) $this->getData('tva_tx');
            $remise = (float) $this->getData('remise_percent');

            $pf->product_fourn_id = $fourn->id;
            $result = $pf->update_buyprice($qty, $buyprice, $user, 'HT', $fourn->dol_object, 0, $ref, $tva_tx, 0, $remise);
            if ($result <= 0) {
                $msg = '';
                if ($this->isLoaded()) {
                    $msg = 'Echec de la mise à jour du prix fournisseur';
                } else {
                    $msg = 'Echec de la création du prix fournisseur';
                }
                if ($pf->error) {
                    $msg .= ' - ' . $pf->error;
                }
                $errors[] = $msg;
            } else {
                if($this->getInitData('price') != $this->getData('price'))
                    $this->history['price'] = $this->getData('price');
                $this->saveHistory();
                if ((int) BimpTools::getPostFieldValue('is_cur_pa', 0)) {
                    $prod = $this->getChildObject('product');
                    $curpa_errors = $prod->setCurrentPaHt($buyprice, (int) $result, 'fourn_price', (int) $result);
                    if (count($curpa_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($curpa_errors);
                    }
                }

                if ((int) BimpTools::getPostFieldValue('update_comm_fourn', 0)) {
                    $prod = $this->getChildObject('product');
                    if (BimpObject::objectLoaded($prod)) {
                        $prod->updateCommandesFournPa((int) $this->getData('fk_soc'), $buyprice);
                    }
                }
                if (!$this->isLoaded()) {
                    $this->id = $result;
                    $this->fetch($this->id);
                    $this->onCreate();
                } else {
                    $this->fetch($this->id);
                }
            }
        }

        return $errors;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $this->set('datec', date('Y-m-d H:i:s'));
        return $this->createOrUpdate($warnings);
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        return $this->createOrUpdate($warnings);
    }

    protected function deleteProcess()
    {
        $pf = new ProductFournisseur($this->db->db);
        return $pf->remove_product_fournisseur_price($this->id);
    }

    public function onCreate()
    {
//        if ($this->isLoaded()) {
//            $product = $this->getChildObject('product');
//
//            if (BimpObject::objectLoaded($product)) {
//                // Propales: 
//                $sql = 'SELECT det.rowid as id FROM ' . MAIN_DB_PREFIX . 'propaldet det LEFT JOIN ' . MAIN_DB_PREFIX . 'propal p ';
//                $sql .= 'ON det.fk_propal = p.rowid WHERE det.fk_product = ' . (int) $product->id . ' ';
//                $sql .= 'AND (det.fk_product_fournisseur_price IS NULL OR det.fk_product_fournisseur_price = 0) AND p.fk_statut = 0';
//
//                $rows = $this->db->executeS($sql, 'array');
//
//                if (!is_null($rows)) {
//                    foreach ($rows as $r) {
//                        $line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_PropalLine', array(
//                                    'id_line' => (int) $r['id']
//                                        ), true);
//
//                        if (BimpObject::objectLoaded($line)) {
//                            $line->id_fourn_price = $this->id;
//                            $line->pa_ht = 0;
//                            $line->update();
//                        }
//                    }
//                }
//
//                // Commandes: 
//                $sql = 'SELECT det.rowid as id FROM ' . MAIN_DB_PREFIX . 'commandedet det LEFT JOIN ' . MAIN_DB_PREFIX . 'commande c ';
//                $sql .= 'ON det.fk_commande = c.rowid WHERE det.fk_product = ' . (int) $product->id . ' ';
//                $sql .= 'AND (det.fk_product_fournisseur_price IS NULL OR det.fk_product_fournisseur_price = 0) AND c.fk_statut = 0';
//
//                $rows = $this->db->executeS($sql, 'array');
//
//                if (!is_null($rows)) {
//                    foreach ($rows as $r) {
//                        $line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', array(
//                                    'id_line' => (int) $r['id']
//                                        ), true);
//
//                        if (BimpObject::objectLoaded($line)) {
//                            $line->id_fourn_price = $this->id;
//                            $line->pa_ht = 0;
//                            $line->update();
//                        }
//                    }
//                }
//
//                //  Factures: 
//                $sql = 'SELECT det.rowid as id FROM ' . MAIN_DB_PREFIX . 'facturedet det LEFT JOIN ' . MAIN_DB_PREFIX . 'facture f ';
//                $sql .= 'ON det.fk_facture = f.rowid WHERE det.fk_product = ' . (int) $product->id . ' ';
//                $sql .= 'AND (det.fk_product_fournisseur_price IS NULL OR det.fk_product_fournisseur_price = 0) AND f.fk_statut = 0';
//
//                $rows = $this->db->executeS($sql, 'array');
//
//                if (!is_null($rows)) {
//                    foreach ($rows as $r) {
//                        $line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
//                                    'id_line' => (int) $r['id']
//                                        ), true);
//
//                        if (BimpObject::objectLoaded($line)) {
//                            $line->id_fourn_price = $this->id;
//                            $line->pa_ht = 0;
//                            $line->update();
//                        }
//                    }
//                }
//            }
//        }
    }
}
