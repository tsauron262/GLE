<?php

class BimpLine extends BimpObject
{

    const BL_PRODUCT = 1;
    const BL_FREE = 2;
    const BL_TEXT = 3;

    public static $types = array(
        self::BL_PRODUCT => 'Produit / Service',
        self::BL_FREE    => 'Ligne libre',
        self::BL_TEXT    => 'Texte seulement'
    );
    protected $product = null;

    // Getters: 

    public function isEditable()
    {
        return $this->isParentEditable();
    }

    public function isDeletable()
    {
        return $this->isParentEditable();
    }

    public function isLineProduct()
    {
        return (int) ((int) $this->getData('type') === self::BL_PRODUCT);
    }

    public function isLineFree()
    {
        return (int) ((int) $this->getData('type') === self::BL_FREE);
    }

    public function isLineText()
    {
        return (int) ((int) $this->getData('type') === self::BL_TEXT);
    }

    public function getTotalHT()
    {
        return 0;
    }

    public function getTotalTTC()
    {
        return 0;
    }

    public function getListExtraBtn()
    {
        return array();
    }

    public function getFournPricesArray()
    {
        $product = $this->getProduct();

        if (BimpObject::objectLoaded($product)) {
            return self::getProductFournPricesArray((int) $product->id, true, 'Prix d\'achat exceptionnel');
        }

        return array(
            0 => ''
        );
    }

    public function getEquipmentsArray()
    {
        return array(
            1 => '1',
            2 => '2',
            3 => '3'
        );
        $product = $this->getProduct();

        if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
            return self::getProductEquipmentsArray((int) $product->id);
        }

        return array();
    }

    public function getProduct()
    {
        if (is_null($this->product)) {
            $id_product = (int) BimpTools::getPostFieldValue('id_product', 0);

            if (!$id_product) {
                $id_product = (int) $this->getData('id_product');
            }

            if ($id_product) {
                $this->product = BimpObject::getInstance('bimpcore', 'Bimp_Product', $id_product);
            }
        }

        return $this->product;
    }

    public function getQtyStep()
    {
        $product = $this->getProduct();

        if (BimpObject::objectLoaded($product)) {
            if ((int) $product->getData('fk_product_type') === 1) {
                return 0.1;
            }
        }

        return 1;
    }

    public function getQtyDecimals($param)
    {
        $product = $this->getProduct();

        if (BimpObject::objectLoaded($product)) {
            if ((int) $product->getData('fk_product_type') === 1) {
                return 3;
            }
        }

        return 0;
    }

    public function getInputValue($field_name)
    {
        if ($field_name === 'id_product') {
            if ((int) $this->getData('type') !== self::BL_PRODUCT) {
                return 0;
            }
            return (int) $this->getData('id_product');
        }
        if (in_array($field_name, array('pu_ht', 'tva_tx', 'id_fourn_price'))) {
            if ($this->isLoaded()) {
                return $this->getData($field_name);
            } else {
                $product = $this->getProduct();

                if (BimpObject::objectLoaded($product)) {
                    switch ($field_name) {
                        case 'pu_ht':
                            return (float) $product->getData('price');

                        case 'tva_tx':
                            return (float) $product->getData('tva_tx');

                        case 'id_fourn_price':
                            $sql = 'SELECT MAX(fp.rowid) as id FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price fp WHERE fp.fk_product = ' . (int) $product->id;
                            $result = $this->db->executeS($sql);
                            if (isset($result[0]->id)) {
                                return (int) $result[0]->id;
                            }
                            return 0;
                    }
                }
                return 0;
            }
        }

        return $this->getData($field_name);
    }

    // Affichages: 

    public function displayDescription($display_input_value = true, $no_html = false)
    {
        switch ((int) $this->getData('type')) {
            case self::BL_PRODUCT:
                return $this->displayData('id_product', 'nom_url', $display_input_value, $no_html);
            case self::BL_FREE:
            case self::BL_TEXT:
                return $this->displayData('description', 'default', $display_input_value, $no_html);
        }

        return '';
    }

    public function displayTotalHT()
    {
        return BimpTools::displayMoneyValue($this->getTotalHT(), 'EUR');
    }

    public function displayTotalTTC()
    {
        return BimpTools::displayMoneyValue($this->getTotalTTC(), 'EUR');
    }
}
