<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/ObjectLine.class.php';

class FournObjectLine extends ObjectLine
{

    public $ref_supplier = '';
    public static $product_line_data = array(
        'id_product'     => array('label' => 'Produit / Service', 'type' => 'int', 'required' => 1, 'default' => null),
        'id_fourn_price' => array('label' => 'Prix d\'achat fournisseur', 'type' => 'int', 'required' => 0, 'default' => null),
        'desc'           => array('label' => 'Description', 'type' => 'html', 'required' => 0, 'default' => ''),
        'qty'            => array('label' => 'Quantité', 'type' => 'float', 'required' => 1, 'default' => 1),
        'pu_ht'          => array('label' => 'PU HT', 'type' => 'float', 'required' => 0, 'default' => null),
        'tva_tx'         => array('label' => 'Taux TVA', 'type' => 'float', 'required' => 0, 'default' => null),
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
            case 'id_fourn_price':
                $value = $this->getValueByProduct('id_fourn_price');
                $values = $this->getProductFournisseursPricesArray(true, 'Prix d\'achat exceptionnel');

                if (!$attribute_equipment && $this->canEditPrixAchat() && $this->isEditable($force_edit)) {
                    $html .= BimpInput::renderInput('select', $prefixe . 'id_fourn_price', (int) $value, array(
                                'options' => $values
                    ));
                } else {
                    if ((int) $value && isset($values[(int) $value])) {
                        $html .= $values[(int) $value];
                    } elseif ((int) $value) {
                        $html .= BimpRender::renderAlerts('Le prix fournisseur d\'ID ' . $value . ' n\'est pas enregistré pour ce produit');
                        $value = 0;
                    } else {
                        $html .= 'Prix d\'achat exceptionnel';
                    }
                    $html .= '<input type="hidden" name="' . $prefixe . 'id_fourn_price" value="' . $value . '"/>';
                }
                $id_product = (int) $this->getIdProductFromPost();
                if ($this->canEditPrixAchat() && $id_product) {
                    $html .= '<div class="buttonsContainer" style="margin: 15px 15px 5px 15px; text-align: right">';
                    $url = DOL_URL_ROOT . '/bimpcore/index.php?fc=product&id=' . $id_product . '&navtab=prix';
                    $html .= '<span class="btn btn-default" onclick="window.open(\'' . $url . '\')">';
                    $html .= BimpRender::renderIcon('fas_pencil-alt', 'iconLeft') . 'Editer les prix d\'achat';
                    $html .= '</span>';
                    $html .= '<span class="btn btn-default" onclick="reloadParentInput($(this), \'id_fourn_price\', [\'id_product\']);">';
                    $html .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Actualiser';
                    $html .= '</span>';
                    $html .= '</div>';
                }
                break;

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
                            $this->tva_tx = (float) $pfp->getData('tva_tx');
                        }
                    }

                    if (!(int) $this->id_fourn_price) {
                        $this->pu_ht = BimpTools::getValue('pa_except', $this->pu_ht);
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
        if (!(int) $this->getData('type') && (int) $this->id_product) {
            $this->set('type', 1);
        }

        $errors = BimpObject::validate();

        if (!count($errors)) {
            switch ($this->getData('type')) {
                case self::LINE_TEXT:
                    $this->id_product = null;
                    $this->id_fourn_price = null;
                    $this->tva_tx = null;
                    $this->qty = 1;
                    $this->pa_ht = null;
                    $this->remise = null;
                    $this->date_to = null;
                    $this->date_from = null;
                    if (BimpObject::objectLoaded($this->post_equipment)) {
                        unset($this->post_equipment);
                        $this->post_equipment = null;
                    }
                    $this->set('remisable', 0);
                    break;

                case self::LINE_PRODUCT:
                    if (is_null($this->id_product) || !$this->id_product) {
                        $errors[] = 'Produit ou service obligatoire';
                    } else {
                        $product = $this->getProduct();

                        if (!BimpObject::objectLoaded($product)) {
                            $errors[] = 'Le produit d\'ID ' . $this->id_product . ' n\'existe pas';
                        } else {
                            if ((int) $product->getData('fk_product_type') === 0) {
                                $qty_str = (string) $this->qty;

                                if (preg_match('/.*\..*/', $qty_str)) {
                                    $errors[] = 'Les quantités décimales ne sont autorisées que pour les produits de type "Service". Veuillez corriger';
                                }
                            }
                        }

                        if (!count($errors)) {
                            if (is_null($this->pu_ht) || is_null($this->tva_tx)) {
                                $parent = $this->getParentInstance();
                                if (!BimpObject::objectLoaded($parent)) {
                                    $errors[] = 'ID de l\'objet parent absent';
                                } else {
                                    $pfp = $product->getCurrentFournPriceObject((int) $parent->getData('fk_soc'), true);
                                    if (BimpObject::objectLoaded($pfp)) {
                                        if (is_null($this->pu_ht)) {
                                            $this->pu_ht = $pfp->getData('price');
                                            $this->tva_tx = $pfp->getData('tva_tx');
                                        } elseif (is_null($this->tva_tx)) {
                                            $this->tva_tx = $pfp->getData('tva_tx');
                                        }
                                    } else {
                                        if (!$this->canEditPrixAchat()) {
                                            $errors[] = 'Aucun prix d\'achat fournisseur enregistré pour ce produit et ce fournisseur';
                                        }
                                    }
                                }
                            }

                            if (is_null($this->desc) || !(string) $this->desc) {
                                $this->desc = $this->getValueByProduct('desc');
                            }


                            $product = $this->getProduct();

                            if ((int) $this->getData('remisable')) {
                                if (!(int) $product->getData('remisable')) {
                                    $this->set('remisable', 0);
                                }
                            }
                        }
                    }

                case self::LINE_FREE:
                    if (is_null($this->pu_ht)) {
                        $this->pu_ht = 0;
                    }
                    if (is_null($this->tva_tx)) {
                        $this->tva_tx = 0;
                    }

                    $this->id_fourn_price = 0;
                    $this->pa_ht = 0;

                    if (BimpObject::objectLoaded($this->post_equipment)) {
                        $errors = $this->checkEquipment($this->post_equipment);
                        if (count($errors)) {
                            return $errors;
                        }
                    }

                    // Pas de TVA si vente hors UE: 
                    $parent = $this->getParentInstance();
                    if (BimpObject::objectLoaded($parent) && !$parent->isTvaActive()) {
                        $this->tva_tx = 0;
                    }

                    break;
            }

            if ($this->force_pa_ht > 0)
                $this->pa_ht = $this->force_pa_ht;
        }
        return $errors;
    }
}
