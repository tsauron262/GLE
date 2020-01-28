<?php

class BimpLine extends BimpObject
{

    const BL_PRODUCT = 1;
    const BL_FREE = 2;
    const BL_TEXT = 3;
    const BL_PRODUIT = 1;
    const BL_SERVICE = 2;
    const BL_LOGICIEL = 3;

    public static $types = array(
        self::BL_PRODUCT => 'Produit / Service',
        self::BL_FREE    => 'Ligne libre',
        self::BL_TEXT    => 'Texte seulement'
    );
    public static $product_types = array(
        0                 => '',
        self::BL_PRODUIT  => array('label' => 'Produit', 'icon' => 'fas_box'),
        self::BL_SERVICE  => array('label' => 'Service', 'icon' => 'fas_hand-holding'),
        self::BL_LOGICIEL => array('label' => 'Logiciel', 'icon' => 'fas_cogs')
    );
    protected $product = null;

    // Getters: 

    public function isEditable($force_edit = false)
    {
        if (!$force_edit) {
            return $this->isParentEditable();
        }

        return 1;
    }

    public function isDeletable($force_delete = false)
    {
        if (!$force_delete) {
            return $this->isParentEditable();
        }

        return 1;
    }

    public function isFormAllowed($form_name, &$errors = array())
    {
        if ($form_name === 'default') {
            if (!(int) $this->isEditable()) {
                $errors[] = BimpTools::ucfirst($this->getLabel('this') . ' n\'est plus éditable');
                return 0;
            }
        }

        return parent::isFormAllowed($form_name);
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
        if ((int) $this->getData('type') === self::BL_TEXT) {
            return 0;
        }

        return (float) $this->getData('pu_ht') * (float) $this->getData('qty');
    }

    public function getTotalTTC()
    {
        if ((int) $this->getData('type') === self::BL_TEXT) {
            return 0;
        }

        $pu_ttc = (float) BimpTools::calculatePriceTaxIn((float) $this->getData('pu_ht'), (float) $this->getData('tva_tx'));

        return $pu_ttc * (float) $this->getData('qty');
    }

    public function getListExtraBtn()
    {
        return array();
    }

    public function getProductTypesArray()
    {
        $product = $this->getProduct();

        $values = array();
        switch ((int) $this->getData('type')) {
            case self::BL_PRODUCT:
                if (BimpObject::objectLoaded($product)) {
                    if (!(int) $product->getData('fk_product_type')) {
                        $values[self::BL_PRODUIT] = self::$product_types[self::BL_PRODUIT];
                        $values[self::BL_LOGICIEL] = self::$product_types[self::BL_LOGICIEL];
                    } else {
                        $values[self::BL_SERVICE] = self::$product_types[self::BL_SERVICE];
                    }
                }
                break;

            case self::BL_FREE:
                $values[self::BL_PRODUIT] = self::$product_types[self::BL_PRODUIT];
                $values[self::BL_LOGICIEL] = self::$product_types[self::BL_LOGICIEL];
                $values[self::BL_SERVICE] = self::$product_types[self::BL_SERVICE];
                break;
        }

        return $values;
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
                $this->product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
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

    public function getQtyDecimals()
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
        if (in_array($field_name, array('pu_ht', 'tva_tx', 'id_fourn_price', 'equipments'))) {
            $product = $this->getProduct();
            if ($this->isLoaded()) {
                $current_id_product = (int) $this->getSavedData('id_product');
                $id_product = (isset($product->id) ? (int) $product->id : 0);
                if ($current_id_product === $id_product) {
                    return $this->getData($field_name);
                }
            }
            switch ($field_name) {
                case 'pu_ht':
                    if (BimpObject::objectLoaded($product)) {
                        return (float) $product->getData('price');
                    }
                    return 0;


                case 'tva_tx':
                    if (BimpObject::objectLoaded($product)) {
                        return (float) $product->getData('tva_tx');
                    }
                    return (float) $this->getConf('fields/tva_tx/default_value', 0);

                case 'id_fourn_price':
                    if (BimpObject::objectLoaded($product)) {
                        $sql = 'SELECT MAX(fp.rowid) as id FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price fp WHERE fp.fk_product = ' . (int) $product->id;
                        $result = $this->db->executeS($sql);
                        if (isset($result[0]->id)) {
                            return (int) $result[0]->id;
                        }
                    }
                    return 0;

                case 'equipments':
                    $equipments = array();
                    if (BimpObject::objectLoaded($product)) {
                        $equipments = $this->getData('equipments');
                        if (!is_array($equipments)) {
                            $equipments = array();
                        }
                        if (count($equipments)) {
                            $product_equipments = $this->getEquipmentsArray();
                            foreach ($equipments as $key => $id_equipment) {
                                $id_equipment = (int) $id_equipment;
                                if (!$id_equipment || !array_key_exists($id_equipment, $product_equipments)) {
                                    unset($equipments[$key]);
                                }
                            }
                        }
                    }
                    return $equipments;
            }
        }

        return $this->getData($field_name);
    }

    public function getTypeForObjectLine()
    {
        BimpObject::loadClass('bimpcommercial', 'ObjectLine');

        switch ((int) $this->getData('type')) {
            case self::BL_PRODUCT:
                return ObjectLine::LINE_PRODUCT;

            case self::BL_FREE:
                return ObjectLine::LINE_FREE;

            case self::BL_TEXT:
                return ObjectLine::LINE_TEXT;
        }

        return 0;
    }

    // Affichages: 

    public function displayDescription($display_input_value = true, $no_html = false)
    {
        $html = '';

        switch ((int) $this->getData('type')) {
            case self::BL_PRODUCT:
                $product = $this->getProduct();
                if (BimpObject::objectLoaded($product)) {
                    if ($no_html) {
                        $html .= $this->displayData('id_product', 'nom', $display_input_value, $no_html);
                        $html .= "\n";
                        $html .= $product->getData('label');
                    } else {
                        $html .= $this->displayData('id_product', 'nom_url', $display_input_value, $no_html);
                        $html .= '<br/>';
                        $html .= $product->getData('label');
                    }
                }

            case self::BL_FREE:
                $html .= $this->displayData('label', 'default', $display_input_value, $no_html);
        }

        $desc = (string) $this->displayData('description', 'default', $display_input_value, $no_html);

        if ($desc) {
            if ($html) {
                if ($no_html) {
                    $html .= "\n";
                } else {
                    $html .= '<br/>';
                }
            }

            $html .= $desc;
        }

        return $html;
    }

    public function displayEquipments($display_name = 'nom_url', $display_input_value = true, $no_html = false)
    {
        $html = '';

        if ((int) $this->getData('type') === self::BL_PRODUCT) {
            $html .= $this->displayData('equipments', $display_name, $display_input_value, $no_html);
        }

        $serials = (string) $this->getData('extra_serials');
        if ($serials) {
            if ($no_html) {
                $html .= "\n";
            } else {
                $html .= '<br/>';
            }

            $html .= str_replace(',', ', ', $serials);
        }

        return $html;
    }

    public function displayTotalHT()
    {
        return BimpTools::displayMoneyValue($this->getTotalHT(), 'EUR');
    }

    public function displayTotalTTC()
    {
        return BimpTools::displayMoneyValue($this->getTotalTTC(), 'EUR');
    }

    // Traitements : 

    public function hydrateObjectLine(ObjectLine $objectLine, $qty = null)
    {
        if (is_a($objectLine, 'ObjectLine')) {
            switch ((int) $this->getData('type')) {
                case self::BL_PRODUCT:
                case self::BL_FREE:
                    if ((int) $this->getData('type') === self::BL_PRODUCT) {
                        $objectLine->id_product = (int) $this->getData('id_product');
                        $objectLine->id_fourn_price = (int) $this->getData('id_fourn_price');
                        $objectLine->desc = '';
                    } else {
                        $objectLine->desc = $this->getData('label');
                        $objectLine->id_product = 0;
                        $objectLine->id_fourn_price = 0;
                    }
                    $objectLine->pu_ht = (float) $this->getData('pu_ht');
                    $objectLine->tva_tx = (float) $this->getData('tva_tx');
                    $objectLine->pa_ht = (float) $this->getData('pa_ht');
                    if (!is_null($qty)) {
                        $objectLine->qty = (float) $qty;
                    } else {
                        $objectLine->qty = (float) $this->getData('qty');
                    }

                    $desc = $this->getData('description');
                    if ($desc) {
                        if ($objectLine->desc) {
                            $objectLine->desc .= '<br/>';
                        }
                        $objectLine->desc .= $desc;
                    }
                    break;

                case self::BL_TEXT:
                    $objectLine->id_product = 0;
                    $objectLine->id_fourn_price = 0;
                    $objectLine->qty = 0;
                    $objectLine->pu_ht = 0;
                    $objectLine->tva_tx = 0;
                    $objectLine->pa_ht = 0;
                    $objectLine->desc = $this->getData('description');
                    break;
            }
        }
    }

    // Overrides: 

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            switch ((int) $this->getData('type')) {

                case self::BL_PRODUCT:
                    $product = $this->getProduct();
                    if (!BimpObject::objectLoaded($product)) {
                        $errors[] = 'Produit absent ou invalide';
                    } else {
                        $this->set('label', '');
                        if (!$product->isSerialisable()) {
                            $this->set('equipments', array());
                        } else {
                            $equipments = $this->getData('equipments');
                            if (!is_array($equipments)) {
                                $equipments = array($equipments);
                            }
                            $product_equipments = $this->getEquipmentsArray();
                            foreach ($equipments as $key => $id_equipment) {
                                $id_equipment = (int) $id_equipment;
                                if (!$id_equipment || !array_key_exists($id_equipment, $product_equipments)) {
                                    unset($equipments[$key]);
                                }
                            }
                            $this->set('equipments', $equipments);
                        }
                        $id_fourn_price = (int) $this->getData('id_fourn_price');
                        if ($id_fourn_price) {
                            $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_fourn_price);
                            if (!$pfp->isLoaded()) {
                                $this->set('id_fourn_price', 0);
                            } elseif ((int) $pfp->getData('fk_product') !== (int) $product->id) {
                                $errors[] = 'Le prix d\'achat fournisseur sélectionné ne correspond pas au produit sélectionné';
                            } else {
                                $this->set('pa_ht', (float) $pfp->getData('price'));
                                $this->set('id_fournisseur', (int) $pfp->getData('fk_soc'));
                            }
                        }
                    }
                    break;

                case self::BL_FREE:
                    if (!(string) $this->getData('label')) {
                        $errors[] = 'Libellé absent';
                    } else {
                        $this->set('id_product', 0);
                        $this->set('equipments', array());
                        $this->set('id_fourn_price', 0);
                    }
                    break;

                case self::BL_TEXT:
                    $this->set('id_product', 0);
                    $this->set('label', '');
                    $this->set('equipments', array());
                    $this->set('extra_serials', '');
                    $this->set('qty', 0);
                    $this->set('pu_ht', 0);
                    $this->set('tva_tx', 0);
                    $this->set('id_fourn_price', 0);
                    $this->set('id_fournisseur', 0);
                    $this->set('pa_ht', 0);
//                    $this->set('remisable', 0);
                    break;
            }

            if ((int) in_array($this->getData('type'), array(self::BL_PRODUCT, self::BL_FREE))) {
                $serials = $this->getData('extra_serials');
                if ($serials) {
                    $serials = preg_replace('/[ ;\n\s\t]+/', ',', $serials);
                    $serials = preg_replace('/,+/', ',', $serials);
                    $this->set('extra_serials', $serials);
                }
            }
        }

        return $errors;
    }
}
