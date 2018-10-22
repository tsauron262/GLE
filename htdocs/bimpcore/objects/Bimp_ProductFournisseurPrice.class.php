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

    public function createOrUpdate()
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
                if (!$this->isLoaded()) {
                    $this->id = $result;
                }

                $this->fetch($this->id);
            }
        }

        return $errors;
    }

    public function create()
    {
        return $this->createOrUpdate();
    }

    public function update()
    {
        return $this->createOrUpdate();
    }

    protected function deleteProcess()
    {
        $pf = new ProductFournisseur($this->db->db);
        return $pf->remove_product_fournisseur_price($this->id);
    }
}
