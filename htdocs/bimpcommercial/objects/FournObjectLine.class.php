<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/ObjectLine.class.php';

class FournObjectLine extends ObjectLine
{

    public $ref_supplier = '';
    public static $product_line_data = array(
        'id_product'     => array('label' => 'Produit / Service', 'type' => 'int', 'required' => 1),
        'id_fourn_price' => array('label' => 'Prix d\'achat fournisseur', 'type' => 'int', 'required' => 1),
        'desc'           => array('label' => 'Description', 'type' => 'html', 'required' => 0, 'default' => ''),
        'qty'            => array('label' => 'Quantité', 'type' => 'float', 'required' => 1, 'default' => 1),
        'pu_ht'          => array('label' => 'PU HT', 'type' => 'float', 'required' => 0, 'default' => 0),
        'tva_tx'         => array('label' => 'Taux TVA', 'type' => 'float', 'required' => 0, 'default' => 0),
        'remise'         => array('label' => 'Remise', 'type' => 'float', 'required' => 0, 'default' => 0),
        'date_from'      => array('label' => 'Date début', 'type' => 'date', 'required' => 0, 'default' => null),
        'date_to'        => array('label' => 'Date fin', 'type' => 'date', 'required' => 0, 'default' => null),
        'ref_supplier'   => array('label' => 'Référence fournisseur', 'type' => 'string', 'required' => 0, 'default' => '')
    );

    // Getters - overrides ObjectLine: 

    public function getProductFournisseursPricesArray()
    {
        $id_product = (int) $this->getIdProductFromPost();
        $parent = $this->getParentInstance();

        $prices = array();
        if (BimpObject::objectLoaded($parent)) {
            $id_fourn = (int) $parent->getData('fk_soc');
            if ($id_product && $id_fourn) {
                BimpObject::loadClass('bimpcore', 'Bimp_Product');
                $prices = Bimp_Product::getFournisseursPriceArray($id_product, $id_fourn, 0, false);
            }
        }

        if ($this->isLoaded()) {
            $prices[0] = 'Prix d\'achat';
        } else {
            $prices[0] = 'Prix d\'achat exceptionnel';
        }
        return $prices;
    }

    public function getValueByProduct($field)
    {
        switch ($field) {
            case 'tva_tx':
                $tva_tx = $this->tva_tx;
                $id_fourn_price = (int) BimpTools::getPostFieldValue('id_fourn_price', (int) $this->id_fourn_price);
                if ((int) $this->id_fourn_price !== $id_fourn_price) {
                    $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_fourn_price);
                    if ($pfp->isLoaded()) {
                        $tva_tx = $pfp->getData('tva_tx');
                    } else {
                        $tva_tx = 0;
                    }
                }
                return (float) $tva_tx;

            case 'ref_supplier':
                $ref_supplier = (string) $this->ref_supplier;
                $id_fourn_price = (int) BimpTools::getPostFieldValue('id_fourn_price', (int) $this->id_fourn_price);
                if ((int) $this->id_fourn_price !== $id_fourn_price) {
                    $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_fourn_price);
                    if ($pfp->isLoaded()) {
                        $ref_supplier = $pfp->getData('ref_fourn');
                    } else {
                        $ref_supplier = '';
                    }
                }
                return $ref_supplier;
        }

        return parent::getValueByProduct($field);
    }

    // Overrides ObjectLine: 

    public function displayLineData($field, $edit = 0, $display_name = 'default', $no_html = false)
    {
        if ($edit && $this->isEditable() && $this->can("edit")) {
            return parent::displayLineData($field, $edit, $display_name, $no_html);
        }

        $html = '';
        switch ($field) {
            case 'ref_supplier':
                $html .= (string) $this->ref_supplier;
                break;

            default:
                $html = parent::displayLineData($field, $edit, $display_name, $no_html);
                break;
        }
        return $html;
    }

    public function renderLineInput($field, $attribute_equipment = false, $prefixe = '', $force_edit = false)
    {
        $html = '';

        switch ($field) {
            case 'ref_supplier':
                $value = $this->getValueByProduct('ref_supplier');
                $html .= BimpInput::renderInput('text', 'ref_supplier', $value);
                break;

            default:
                $html = parent::renderLineInput($field, $attribute_equipment, $prefixe, $force_edit);
                break;
        }

        return $html;
    }

    protected function fetchLine()
    {
        if (parent::fetchLine()) {
            if (in_array((int) $this->getData('type'), array(self::LINE_PRODUCT, self::LINE_FREE))) {
                $line = $this->getChildObject('line');
                $this->ref_supplier = $line->ref_supplier;
            }
            return true;
        }
        return false;
    }

    public function validatePost()
    {
        $errors = parent::validatePost();
        if (!count($errors)) {
            switch ((int) $this->getData('type')) {
                case self::LINE_PRODUCT:
                    if ((int) $this->id_fourn_price) {
                        $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', (int) $this->id_fourn_price);
                        if (!$pfp->isLoaded()) {
                            $errors[] = 'Le prix d\'achat fournisseur d\'ID ' . $this->id_fourn_price . ' n\'existe pas';
                            $this->id_fourn_price = 0;
                        } else {
                            $this->pu_ht = (float) $pfp->getData('price');
                        }
                    }

                    if (!(int) $this->id_fourn_price) {
                        $this->pu_ht = (float) BimpTools::getValue('pa_except', (float) $this->pu_ht);
                    }
                    break;

                case self::LINE_FREE:
                    $this->pu_ht = (float) BimpTools::getValue('pa_except', (float) $this->pu_ht);
                    break;
            }
        }

        return $errors;
    }

    public function validate()
    {
        $errors = parent::validate();

        $this->pa_ht = 0;

        if (!count($errors)) {
            switch ((int) $this->getData('type')) {
                case self::LINE_PRODUCT:
                    if ((int) $this->id_fourn_price) {
                        $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', (int) $this->id_fourn_price);
                        if (!$pfp->isLoaded()) {
                            $errors[] = 'Le prix d\'achat fournisseur d\'ID ' . $this->id_fourn_price . ' n\'existe pas';
                            $this->id_fourn_price = 0;
                        } else {
                            $this->pu_ht = (float) $pfp->getData('price');
                            $this->tva_tx = (float) $pfp->getData('tva_tx');
                        }
                    }
                    break;

                case self::LINE_FREE:
                    $this->id_fourn_price = 0;
                    break;

                case self::LINE_TEXT:
                    $this->qty = 1;
                    break;
            }
        }

        return $errors;
    }
}
