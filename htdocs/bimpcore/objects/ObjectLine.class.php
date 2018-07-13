<?php

abstract class ObjectLine extends BimpObject
{

    public static $dol_line_primary = 'rowid';
    public $equipment_required = false;

    const LINE_PRODUCT = 1;
    const LINE_TEXT = 2;
    const LINE_FREE = 3;

    public $desc = null;
    public $id_product = null;
    public $qty = 1;
    public $pu_ht = null;
    public $tva_tx = null;
    public $pa_ht = null;
    public $id_fourn_price = null;
    public $remise = null;
    public $date_from = null;
    public $date_to = null;
    public $id_remise_except = null;
    public static $product_line_data = array(
        'id_product'     => array('label' => 'Produit / Service', 'type' => 'int', 'required' => 1),
        'id_fourn_price' => array('label' => 'Prix d\'achat fournisseur', 'type' => 'int', 'required' => 1),
        'desc'           => array('label' => 'Description', 'type' => 'html', 'required' => 0, 'default' => ''),
        'qty'            => array('label' => 'Quantité', 'type' => 'float', 'required' => 1, 'default' => 1),
        'pu_ht'          => array('label' => 'PU HT', 'type' => 'float', 'required' => 0, 'default' => 0),
        'tva_tx'         => array('label' => 'Taux TVA', 'type' => 'float', 'required' => 0, 'default' => 0),
        'pa_ht'          => array('label' => 'Prix d\'achat HT', 'type' => 'float', 'required' => 0, 'default' => 0),
        'remise'         => array('label' => 'Remise', 'type' => 'float', 'required' => 0, 'default' => 0),
        'date_from'      => array('label' => 'Date début', 'type' => 'date', 'required' => 0, 'default' => null),
        'date_to'        => array('label' => 'Date fin', 'type' => 'date', 'required' => 0, 'default' => null)
    );
    public static $text_line_data = array(
        'desc' => 'string',
    );
    public static $types = array(
        self::LINE_PRODUCT => 'Produit / Service',
        self::LINE_TEXT    => 'Texte libre'
    );
    protected $product = null;

    // Getters: 

    public function isEditable($force_edit = false)
    {
        if (!$force_edit && !(int) $this->getData('editable')) {
            return 0;
        }

        $parent = $this->getParentInstance();
        if (!BimpObject::objectLoaded($parent)) {
            return 0;
        }

        if ($parent->field_exists('fk_statut') && $parent->getData('fk_statut') === 0) {
            return 1;
        } elseif (isset($parent->dol_object->statut) && (int) $parent->dol_object->statut === 0) {
            return 1;
        }

        return 0;
    }

    public function isDeletable($force_delete = false)
    {
        if ($this->isLoaded()) {
            if (!$force_delete && !(int) $this->getData('deletable')) {
                return 0;
            }

            $parent = $this->getParentInstance();
            if (!BimpObject::objectLoaded($parent)) {
                return 0;
            }

            if ($parent->field_exists('fk_statut') && $parent->getData('fk_statut') === 0) {
                return 1;
            } elseif (isset($parent->dol_object->statut) && (int) $parent->dol_object->statut === 0) {
                return 1;
            }
        }

        return 0;
    }

    public function isParentEditable()
    {
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent)) {
            if ($parent->field_exists('fk_statut') && $parent->getData('fk_statut') === 0) {
                return 1;
            } elseif (isset($parent->dol_object->statut) && (int) $parent->dol_object->statut === 0) {
                return 1;
            }
        }

        return 0;
    }

    public function isLineProduct()
    {
        if ((int) $this->getData('type') === self::LINE_PRODUCT) {
            return 1;
        }

        return 0;
    }

    public function isLineText()
    {
        return (int) ((int) $this->getData('type') === self::LINE_TEXT);
    }

    public function isProductEditable()
    {
        if ($this->isLoaded()) {
            return 0;
        }

        return 1;
    }

    public function isEquipmentAvailable()
    {
        return array();
    }
    
    public function getLineDataDefs()
    {
        switch ((int) $this->getData('type')) {
            case self::LINE_PRODUCT:
            case self::LINE_FREE:
                return self::$product_line_data;

            case self::LINE_TEXT:
                return self::$text_line_data;
        }

        return array();
    }

    public function getUnitPriceTTC()
    {
        if (!is_null($this->pu_ht)) {
            $pu_ht = $this->pu_ht;

            if (!is_null($this->remise) && $this->remise > 0) {
                $pu_ht -= (float) ($pu_ht * ($this->remise / 100));
            }
            if (is_null($this->tva_tx)) {
                return $pu_ht;
            }
            return BimpTools::calculatePriceTaxIn((float) $pu_ht, (float) $this->tva_tx);
        }

        return 0;
    }

    public function getTotalHT()
    {
        if (!is_null($this->pu_ht) && !is_null($this->qty)) {
            return (float) ((float) $this->pu_ht * (float) $this->qty);
        }

        return 0;
    }

    public function getTotalTTC()
    {
        $pu_ttc = (float) $this->getUnitPriceTTC();

        if ($pu_ttc && !is_null($this->qty)) {
            return (float) ($pu_ttc * (float) $this->qty);
        }
        return 0;
    }

    public function getMargin()
    {
        $pu = (float) $this->pu_ht;
        if (!is_null($this->remise) && $this->remise > 0) {
            $pu -= ($pu * ((float) $this->remise / 100));
        }

        $margin = ($pu * (float) $this->qty) - ((float) $this->pa_ht * (float) $this->qty);
        return $margin;
    }

    public function getMarginRate()
    {
        if (!(float) $this->pa_ht) {
            return 0;
        }

        $pu = (float) $this->pu_ht;
        if (!is_null($this->remise) && (float) $this->remise > 0) {
            $pu -= ($pu * ((float) $this->remise / 100));
        }

        $margin = $pu - (float) $this->pa_ht;
        if (!$margin) {
            return 0;
        }

        return ($margin / (float) $this->pa_ht) * 100;
    }

    public function getIdFournPriceFromPost()
    {
        $id = 0;

        if (BimpTools::isSubmit('id_fourn_price')) {
            $id = (int) BimpTools::getValue('id_fourn_price', 0);
        } elseif (BimpTools::isSubmit('fields/id_fourn_price')) {
            $id = (int) BimpTools::getValue('fields/id_fourn_price', 0);
        } else {
            $id = (int) $this->id_fourn_price;
        }

        $this->id_fourn_price = $id;

        return $id;
    }

    public function getIdProductFromPost()
    {
        $id_product = 0;
        $id_equipment = 0;

        if (BimpTools::isSubmit('id_equipment')) {
            $id_equipment = (int) BimpTools::getValue('id_equipment');
        } elseif (BimpTools::isSubmit('fields/id_equipment')) {
            $id_equipment = (int) BimpTools::getValue('fields/id_equipment');
        } else {
            if ((int) $this->getData('id_equipment')) {
                $id_equipment = (int) $this->getData('id_equipment');
            }
        }

        if ($id_equipment) {
            $equipment = BimpObject::getInstance('bimpequipment', 'Equipment', (int) $id_equipment);
            if ($equipment->isLoaded()) {
                $id_product = (int) $equipment->getData('id_product');
            } else {
                $id_equipment = 0;
            }
        }

        $this->set('id_equipment', $id_equipment);

        if (!$id_product) {
            if (BimpTools::isSubmit('id_product')) {
                $id_product = (int) BimpTools::getValue('id_product', 0);
            } elseif (BimpTools::isSubmit('fields/id_product')) {
                $id_product = BimpTools::getValue('fields/id_product', 0);
            } elseif ((int) $this->id_product) {
                $id_product = (int) $this->id_product;
            }
        }

        if ($id_product !== (int) $this->id_product) {
            $this->id_product = $id_product;

            if (!is_null($this->product)) {
                unset($this->product);
                $this->product = null;
            }
        }

        return $id_product;
    }

    public function getProductFournisseursPricesArray()
    {
        $id_product = (int) $this->getIdProductFromPost();
        if ($id_product) {
            BimpObject::loadClass('bimpcore', 'Bimp_Product');
            return Bimp_Product::getFournisseursPriceArray($id_product);
        }

        return array();
    }

    public function getValueByProduct($field)
    {
        $id_product = (int) $this->getIdProductFromPost();

        if ($id_product) {
            $product = $this->getProduct();
            if (BimpObject::objectLoaded($product)) {
                switch ($field) {
                    case 'pu_ht':
                        if ((int) $this->getData('id_equipment')) {
                            $equipment = $this->getChildObject('equipment');
                            if ((float) $equipment->getData('prix_vente_except') > 0) {
                                return BimpTools::calculatePriceTaxEx((float) $equipment->getData('prix_vente_except'), (float) $product->getData('tva_tx'));
                            }
                        }
                        return $product->getData('price');

                    case 'tva_tx':
                        return $product->getData('tva_tx');

                    case 'id_fourn_price':
                        if ((int) $this->id_fourn_price) {
                            $pfp = BimpObject::getInstance('bimpcore', 'Bimp_ProductFournisseurPrice', (int) $this->id_fourn_price);
                            if (BimpObject::objectLoaded($pfp)) {
                                if ((int) $pfp->getData('fk_product') === $id_product) {
                                    return $this->id_fourn_price;
                                }
                            }
                        }
                        BimpTools::loadDolClass('fourn', 'fournisseur.product', 'ProductFournisseur');
                        $pf = new ProductFournisseur($this->db->db);
                        if ($pf->find_min_price_product_fournisseur($id_product, $this->getData('qty'))) {
                            return (int) $pf->product_fourn_price_id;
                        }
                }
            }
        }

        return 0;
    }

    public function getProductPricesValuesArray()
    {
        $this->getIdProductFromPost();

        if (!(int) $this->id_product) {
            return array();
        }

        $product = $this->getProduct();

        if (!BimpObject::objectLoaded($product)) {
            return array();
        }

        $values = array(
            0 => ''
        );

        if ((int) $this->getData('id_equipment')) {
            $equipment = $this->getChildObject('equipment');
            if (BimpObject::objectLoaded($equipment)) {
                if ((float) $equipment->getData('prix_vente_except') > 0) {
                    $pu_ht = BimpTools::calculatePriceTaxEx((float) $equipment->getData('prix_vente_except'), (float) $product->getData('tva_tx'));
                    $values['' . $pu_ht] = 'Prix de vente exceptionnel équipement: ' . BimpTools::displayMoneyValue($pu_ht, 'EUR');
                }
            }
        }

        $values['' . $product->getData('price')] = 'Prix de vente produit: ' . BimpTools::displayMoneyValue((float) $product->getData('price'), 'EUR');

        return $values;
    }

    public function getListExtraBtn()
    {
        $buttons = array();
        if ($this->isLoaded()) {
            if ((int) $this->id_product) {
                $product = $this->getProduct();
                if (BimpObject::objectLoaded($product)) {
                    if ($product->isSerialisable() && $this->equipment_required) {
                        $data = array();
                        if ((int) $this->getData('id_equipment')) {
                            $data['id_equipment'] = (int) $this->getData('id_equipment');
                        }
                        $onclick = $this->getJsActionOnclick('attributeEquipment', $data, array(
                            'form_name' => 'equipment'
                        ));
                        $buttons[] = array(
                            'label'   => 'Attribuer un équipement',
                            'icon'    => 'arrow-circle-down',
                            'onclick' => $onclick
                        );
                    }
                }
            }
        }

        return $buttons;
    }

    public function getProduct()
    {
        if ((int) $this->id_product) {
            if (is_null($this->product)) {
                $this->product = BimpObject::getInstance('bimpcore', 'Bimp_Product', (int) $this->id_product);
                if (!BimpObject::objectLoaded($this->product)) {
                    unset($this->product);
                    $this->product = null;
                }
            }
        }

        return $this->product;
    }

    // Affichages: 

    public function displayLineData($field, $edit = 0, $display_name = 'default', $no_html = false)
    {
        $html = '';

        if ((int) $this->getData('type') === self::LINE_TEXT) {
            if (!array_key_exists($field, self::$text_line_data)) {
                return '';
            }
        }

        if ($edit && $this->isEditable() && $this->canEdit()) {
            $dataDefs = $this->getLineDataDefs();
            if (!array_key_exists($field, $dataDefs)) {
                return '';
            }
            $required = (int) (isset($dataDefs[$field]['required']) ? $dataDefs[$field]['required'] : 0);
            $html .= '<div class="inputContainer ' . $field . '_inputContainer"';
            $html .= ' data-field_name="' . $field . '"';
            $html .= ' data-initial_value="' . (isset($this->{$field}) ? $this->{$field} : '') . '"';
            $html .= ' data-multiple="0"';
            $html .= ' data-required="' . $required . '"';
            $html .= ' data-data_type="' . $dataDefs[$field]['type'] . '"';
            $html .= ' data-field_prefix=""';
            $html .= '>';
            $html .= $this->renderLineInput($field);
            $html .= '</div>';
        } else {
            switch ($field) {
                case 'id_product':
                    $product = $this->getProduct();
                    if (!BimpObject::objectLoaded($product)) {
                        $msg = 'Erreur: le produit d\'ID ' . $this->id_product . ' n\'existe pas';
                        if ($no_html) {
                            $html .= $msg;
                        } else {
                            $html .= BimpRender::renderAlerts($msg);
                        }
                    } else {
                        switch ($display_name) {
                            case 'ref':
                                $html = $product->getRef();
                                break;

                            case 'nom_url':
                                if ($this->no_html) {
                                    $html = BimpObject::getInstanceNom($product);
                                } else {
                                    $html .= BimpObject::getInstanceNomUrl($product);
                                    $url = BimpObject::getInstanceUrl($product);
                                    if ($url) {
                                        $html .= '<span class="objectIcon" onclick="window.open(\'' . $url . '\')">';
                                        $html .= '<i class="fa fa-external-link"></i>';
                                        $html .= '</span>';

                                        $onclick = 'loadModalObjectPage($(this), \'' . $url . '\', \'' . addslashes(BimpObject::getInstanceNom($product)) . '\')';
                                        $html .= '<span class="objectIcon" onclick="' . $onclick . '">';
                                        $html .= '<i class="fa fa-eye"></i>';
                                        $html .= '</span>';
                                    }
                                }

                                break;

                            case 'card':
                                if ($this->no_html) {
                                    $html = BimpObject::getInstanceNom($product);
                                } else {
                                    $card = new BC_Card($product);
                                    $html = $card->renderHtml();
                                }
                                break;

                            case 'default':
                            case 'nom':
                            default:
                                $html = BimpObject::getInstanceNom($product);
                                break;
                        }
                    }
                    break;

                case 'desc':
                    if (in_array((int) $this->getData('type'), array(self::LINE_PRODUCT, self::LINE_FREE))) {
                        if ((int) $this->id_product) {
                            $html = $this->displayLineData('id_product', 0, 'nom_url', $no_html);
                            $product = $this->getProduct();
                            if (BimpObject::objectLoaded($product)) {
                                $html .= '&nbsp;&nbsp;' . $product->getData('label');
                                if (($this->equipment_required && $product->isSerialisable()) || (int) $this->getData('id_equipment')) {
                                    if ($no_html) {
                                        $html .= "\n";
                                    } else {
                                        $html .= '<br/>';
                                    }
                                    $html .= 'Equipement: ' . $this->displayEquipment();
                                }
                            }
                            if ((string) $this->desc) {
                                if ($no_html) {
                                    $html .= "\n";
                                } else {
                                    $html .= '<br/>';
                                }
                            }
                        }
                    }
                    if ($no_html) {
                        $value = BimpTools::replaceBr($this->desc);
                        $html .= (string) strip_tags($value);
                    } else {
                        $html .= (string) $this->desc;
                    }
                    break;

                case 'qty':
                    $html .= (float) $this->qty;
                    break;

                case 'pu_ht':
                    if ($no_html) {
                        $html = price((float) $this->pu_ht) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $this->pu_ht, 'EUR');
                    }
                    break;

                case 'pu_ttc':
                    if ($no_html) {
                        $html = price((float) $this->getUnitPriceTTC()) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $this->getUnitPriceTTC(), 'EUR');
                    }
                    break;

                case 'tva_tx':
                    $html .= str_replace('.', ',', (string) $this->tva_tx) . ' %';
                    break;

                case 'pa_ht':
                    if ($no_html) {
                        $html = price((float) $this->pa_ht) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $this->pa_ht, 'EUR');
                    }
                    break;

                case 'remise':
                    $html .= str_replace('.', ',', (string) $this->remise) . ' %';
                    break;

                case 'date_from':
                    if ((string) $this->date_from && $this->date_from !== '0000-00-00') {
                        $date = new DateTime($this->date_from);
                        if ($no_html) {
                            $html .= $date->format('d / m / Y');
                        } else {
                            $html .= '<span class="date">' . $date->format('d / m / Y') . '</span>';
                        }
                    }
                    break;

                case 'date_to':
                    if ((string) $this->date_to && $this->date_to !== '0000-00-00') {
                        $date = new DateTime($this->date_to);
                        if ($no_html) {
                            $html .= $date->format('d / m / Y');
                        } else {
                            $html .= '<span class="date">' . $date->format('d / m / Y') . '</span>';
                        }
                    }
                    break;

                case 'total_ht':
                    if ($no_html) {
                        $html = price((float) $this->getTotalHT()) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $this->getTotalHT(), 'EUR');
                    }
                    break;

                case 'total_ttc':
                    if ($no_html) {
                        $html = price((float) $this->getTotalTTC()) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $this->getTotalTTC(), 'EUR');
                    }
                    break;

                case 'margin':
                    $margin = (float) $this->getMargin();
                    $margin_rate = 0;
                    if ($margin !== 0.0) {
                        $margin_rate = round($this->getMarginRate(), 2);
                    }

                    if ($no_html) {
                        $html = price($margin) . ' € (' . $margin_rate . ' %)';
                    } else {
                        if ($margin <= 0) {
                            $html = '<span class="danger">';
                            $html .= BimpTools::displayMoneyValue($margin, 'EUR');
                            $html .= ' (' . $margin_rate . ' %)';
                            $html .= '</span>';
                        } else {
                            $html = '<span class="bold">';
                            $html .= BimpTools::displayMoneyValue($margin, 'EUR');
                            $html .= ' (' . $margin_rate . ' %)';
                            $html .= '</span>';
                        }
                    }
                    break;
            }
        }

        return $html;
    }

    public function displayEquipment()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('id_equipment')) {
                return $this->displayData('id_equipment', 'nom_url');
            } elseif ((int) $this->id_product) {
                $product = $this->getProduct();
                if (BimpObject::objectLoaded($product)) {
                    if ($product->isSerialisable()) {
                        return '<span class="danger">Attribution nécéssaire</span>';
                    } else {
                        return '<span class="warning">Non sérialisable</span>';
                    }
                } else {
                    return '<span class="danger">Produit invalide</span>';
                }
            }
        }

        return '';
    }

    // Traitements:

    protected function createLine($check_data = true)
    {
        $errors = array();

        $instance = $this->getParentInstance();
        if (!BimpObject::objectLoaded($instance)) {
            $errors[] = BimpTools::ucfirst(BimpObject::getInstanceLabel($instance, '')) . ' invalide';
        } else {
            if ($check_data) {
                $errors = $this->validate();
                if (count($errors)) {
                    return $errors;
                }
            }

            $object = $instance->dol_object;
            $object->error = '';
            $object->errors = array();

            $result = null;

            switch ((int) $this->getData('type')) {
                case self::LINE_PRODUCT:
                case self::LINE_FREE:
                    if (is_null($this->date_from)) {
                        $this->date_from = '';
                    }

                    if (is_null($this->date_to)) {
                        $this->date_to = '';
                    }

                    $result = $object->addLine((string) $this->desc, (float) $this->pu_ht, $this->qty, (float) $this->tva_tx, 0, 0, (int) $this->id_product, (float) $this->remise, 'HT', 0, 0, 0, (int) $this->getData('position'), 0, 0, (int) $this->id_fourn_price, (float) $this->pa_ht, '', (string) $this->date_from, (string) $this->date_to, 0, null, '', 0, 0, (int) $this->id_remise_except);
                    break;

                case self::LINE_TEXT:
                    $result = $object->addLine((string) $this->desc, 0, 0, 0);
                    break;

                default:
                    $errors[] = 'Type invalide';
                    break;
            }
            if (!is_null($result) && $result <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($object), 'Des erreurs sont survenues lors de l\'ajout de la ligne ' . BimpObject::getInstanceLabel($instance, 'to'));
            } else {
                if ($this->isLoaded()) {
                    $this->updateField('id_line', (int) $result);
                    $this->resetPositions();
                } else {
                    $this->set('id_line', (int) $result);
                }
            }
        }

        return $errors;
    }

    protected function updateLine($check_data = true)
    {
        $errors = array();

        $instance = $this->getParentInstance();
        if (!BimpObject::objectLoaded($instance)) {
            $errors[] = BimpTools::ucfirst(BimpObject::getInstanceLabel($instance, '')) . ' invalide';
        } else {
            $id_line = (int) $this->getData('id_line');
            if (!$id_line) {
                return array('ID de la ligne ' . BimpObject::getInstanceLabel($instance, 'of_the') . ' absent');
            }

            if ($check_data) {
                $errors = $this->validate();
                if (count($errors)) {
                    return $errors;
                }
            }

            $object = $instance->dol_object;
            $object->error = '';
            $object->errors = array();

            $result = null;

            switch ((int) $this->getData('type')) {
                case self::LINE_PRODUCT:
                case self::LINE_FREE:
                    if (is_null($this->date_from)) {
                        $this->date_from = '';
                    }

                    if (is_null($this->date_to)) {
                        $this->date_to = '';
                    }

                    $result = $object->updateline($id_line, (float) $this->pu_ht, $this->qty, (float) $this->remise, (float) $this->tva_tx, 0, 0, (string) $this->desc, 'HT', 0, 0, 0, 0, (int) $this->id_fourn_price, (float) $this->pa_ht, '', 0, $this->date_from, $this->date_to);
                    break;

                case self::LINE_TEXT:
                    $result = $object->updateline($id_line, 0, 0, 0, 0, 0, 0, (string) $this->desc);
                    break;

                default:
                    $errors[] = 'Type de ligne invalide';
                    break;
            }
            if (!is_null($result) && $result <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($object), 'Des erreurs sont survenues lors de l\'ajout de la ligne ' . BimpObject::getInstanceLabel($instance, 'to'));
            }
        }

        return $errors;
    }

    protected function fetchLine()
    {
        $id_line = (int) $this->getData('id_line');

        if (!$id_line) {
            return false;
        }

        $line = $this->getChildObject('line');
        if (!BimpObject::objectLoaded($line)) {
            return false;
        }

        switch ($this->getData('type')) {
            case self::LINE_PRODUCT:
            case self::LINE_FREE:
                $this->id_product = (int) $line->fk_product;
                $this->id_fourn_price = (int) $line->fk_fournprice;
                $this->desc = (string) $line->desc;
                $this->pu_ht = (float) $line->subprice;
                $this->qty = isset($line->qty) ? $line->qty : 1;
                $this->tva_tx = (float) $line->tva_tx;
                $this->pa_ht = (float) $line->pa_ht;
                $this->remise = (float) $line->remise_percent;
                $this->date_from = $line->date_start;
                $this->date_to = $line->date_end;
                $this->id_remise_except = (int) $line->fk_remise_except;
                break;

            case self::LINE_TEXT:
                $this->desc = (string) $line->desc;
                break;

            default:
                return false;
        }


        return true;
    }

    protected function deleteLine()
    {
        $errors = array();

        $instance = $this->getParentInstance();

        if (!BimpObject::objectLoaded($instance)) {
            $errors[] = BimpTools::ucfirst(BimpObject::getInstanceLabel($instance, '')) . ' invalide';
        } else {
            $id_line = (int) $this->getData('id_line');
            if (!$id_line) {
                return array('ID de la ligne ' . BimpObject::getInstanceLabel($instance, 'of_the') . ' absent');
            }

            $instance->dol_object->error = '';
            $instance->dol_object->errors = array();

            if ($instance->dol_object->deleteline($id_line) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($instance->dol_object), 'Des erreurs sont survenues lors de la suppression de la ligne ' . BimpObject::getInstanceLabel($instance, 'of_the'));
            }
        }

        return $errors;
    }

    protected function setLinesPositions()
    {
        $errors = array();

        $table = $this::$dol_line_table;
        $primary = $this::$dol_line_primary;

        if (!$table) {
            $errors[] = 'table non définie';
        }

        if (!$primary) {
            $errors[] = 'Clé primaire non définie';
        }

        $parent = $this->getParentInstance();

        if (is_null($parent)) {
            $errors[] = 'Objet parent non défini';
        } elseif (!$parent->isLoaded()) {
            $errors[] = 'ID ' . $parent->getLabel('of_the') . ' absent';
        }

        if (!count($errors)) {
            $lines = $this->getList(array(
                'id_obj' => (int) $parent->id
                    ), null, null, 'position', 'asc', 'array', array(
                'id_line', 'position'
            ));

            if (!is_null($lines) && count($lines)) {
                foreach ($lines as $line) {
                    if ($this->db->update($table, array(
                                'rang' => (int) $line['position']
                                    ), '`' . $primary . '` = ' . (int) $line['id_line']) <= 0) {
                        $msg = 'Echec de la mise à jour de la position de la ligne d\'ID ' . $line['id_line'];
                        $sqlError = $this->db->lasterror();
                        if ($sqlError) {
                            $msg .= ' - ' . $sqlError;
                        }
                        $errors[] = $msg;
                    }
                }
            }
        }

        return $errors;
    }

    public function checkEquipment()
    {
        // Pas de vérif de la disponibilité de l'équipement. Créer une surcharge si besoin. 
        $errors = array();

        if ((int) $this->getData('id_equipment')) {
            $equipment = $this->getChildObject('equipment');
            if (!$equipment->isLoaded()) {
                $errors[] = 'ID de l\'équipement invalide';
            } else {
                if ((int) $equipment->getData('id_product')) {
                    $product = $equipment->getChildObject('product');
                    if (!BimpObject::objectLoaded($product)) {
                        $errors[] = 'Le produit associé à l\'équipement ' . $equipment->id . ' - n° série "' . $equipment->getData('serial') . '" n\'existe plus';
                    } elseif ((int) $this->id_product) {
//                        echo $product->id . ' => '.$this->id_product; exit;
                        if ((int) $product->id !== (int) $this->id_product) {
                            $errors[] = 'Cet équipement ne correspond pas au produit sélectionné';
                        }
                    } else {
                        $this->id_product = $product->id;
                    }
                } else {
//                    $this->id_product = 0;
//                    $desc = $this->desc;
//                    $this->desc = $equipment->getData('product_label');
//                    if ($desc) {
//                        $this->desc .= '<br/>' . $desc;
//                    }

                    $errors[] = 'L\'équipement sélectionné n\'est associé à aucun produit';
                    $this->id_product = 0;
                }
                
                if (!count($errors)) {
                    $errors = $this->isEquipmentAvailable();
                }
            }
        }

        return $errors;
    }

    protected function checkProductSerialisable()
    {
        $warnings = array();

        if ((int) $this->getData('type') !== self::LINE_TEXT && (int) $this->id_product) {
            $qty = (int) $this->qty;

            if ($qty > 1) {
                $product = $this->getProduct();
                if (BimpObject::objectLoaded($product)) {
                    if ((int) $product->getData('fk_product_type') === Product::TYPE_PRODUCT) {
                        if ($product->isSerialisable()) {
                            $instance = BimpObject::getInstance($this->module, $this->object_name);
                            $qty--;
                            while ($qty > 0) {
                                $instance->reset();
                                $instance->validateArray($this->data);
                                $instance->set('id_equipment', 0);
                                $instance->set('id_line', 0);
                                $instance->set('id', null);
                                $instance->id = null;
                                $instance->id_product = $this->id_product;
                                $instance->qty = 1;
                                $instance->pu_ht = $this->pu_ht;
                                $instance->tva_tx = $this->tva_tx;
                                $instance->desc = $this->desc;
                                $instance->remise = $this->remise;
                                $instance->id_remise_except = $this->id_remise_except;
                                $instance->pa_ht = $this->pa_ht;
                                $instance->id_fourn_price = $this->id_fourn_price;
                                $instance->date_from = $this->date_from;
                                $instance->date_to = $this->date_to;
                                $instance->create($warnings, true);
                                $qty--;
                            }
                            $this->qty = 1;
                        }
                    }
                }
            }
        }
    }

    // Rendus HTML: 

    public function renderLineInput($field)
    {
        $html = '';

        $value = null;

        $this->getIdProductFromPost();

        if ($field === 'id_product') {
            $value = (int) $this->id_product;
        } elseif (in_array($field, array('pu_ht', 'tva_tx', 'id_fourn_price'))) {
            $value = $this->getValueByProduct($field);
        } else {
            if (BimpTools::isSubmit($field)) {
                $value = BimpTools::getValue($field);
            } elseif (BimpTools::isSubmit('fields/' . $field)) {
                $value = BimpTools::getValue('fields/' . $field);
            } elseif (isset($this->{$field})) {
                $value = $this->{$field};
            }
        }

        switch ($field) {
            case 'id_product':
                $html = BimpInput::renderInput('search_product', 'id_product', (int) $value, array(
                            'filter_type' => 'both'
                ));
                $html .= '<p class="inputHelp">Entrez la référence ou le code-barre d\'un produit.<br/>Laissez vide si vous sélectionnez un équipement.</p>';
                break;

            case 'id_fourn_price':
                if ((int) $this->getData('id_equipment')) {
                    $equipment = $this->getChildObject('equipment');
                    if (BimpObject::objectLoaded($equipment)) {
                        if ((float) $equipment->getData('prix_achat') > 0) {
                            $html .= '<input type="hidden" name="id_fourn_price" value="0"/>';
                            $html .= 'Prix d\'achat équipement: <strong>';
                            $html .= BimpTools::displayMoneyValue((float) $equipment->getData('prix_achat'), 'EUR') . '</strong>';
                            break;
                        }
                    }
                }
                $html = BimpInput::renderInput('select', 'id_fourn_price', (int) $value, array(
                            'options' => $this->getProductFournisseursPricesArray()
                ));
                break;

            case 'desc':
                $html = BimpInput::renderInput('html', 'desc', (string) $value);
                break;

            case 'qty':
                $product_type = 0;
                if ((int) $this->id_product) {
                    $product_type = (int) $this->db->getValue('product', 'fk_product_type', '`rowid` = ' . (int) $this->id_product);
                    $serialisable = (int) $this->db->getValue('product_extrafields', 'serialisable', '`fk_object` = ' . (int) $this->id_product);
                }
                if (is_null($value)) {
                    $value = 1;
                }

                if ($serialisable) {
                    $html = '<input type="hidden" name="qty" value="' . $value . '"/>' . $value;
                } elseif ($product_type === 1) {
                    $html = BimpInput::renderInput('qty', 'qty', (float) $value, array(
                                'step' => 0.100,
                                'data' => array(
                                    'data_type' => 'number',
                                    'min'       => 0,
                                    'unsigned'  => 1,
                                    'decimals'  => 3
                                )
                    ));
                } else {
                    $html = BimpInput::renderInput('qty', 'qty', (int) $value, array(
                                'data' => array(
                                    'data_type' => 'number',
                                    'min'       => 0,
                                    'unsigned'  => 1,
                                    'decimals'  => 0
                                )
                    ));
                }
                break;

            case 'pu_ht':
                $html = BimpInput::renderInput('text', 'pu_ht', (float) $value, array(
                            'values'      => $this->getProductPricesValuesArray(),
                            'data'        => array(
                                'data_type' => 'number',
                                'decimals'  => 2
                            ),
                            'addon_right' => '<i class="fa fa-' . BimpTools::getCurrencyIcon('EUR') . '"></i>'
                ));
                break;

            case 'tva_tx':
                $html = BimpInput::renderInput('text', 'tva_tx', (float) $value, array(
                            'data'        => array(
                                'data_type' => 'number',
                                'decimals'  => 2,
                                'min'       => 0,
                                'max'       => 100
                            ),
                            'addon_right' => '<i class="fa fa-percent"></i>'
                ));
                break;

            case 'remise':
                $html = BimpInput::renderInput('text', 'remise', (float) $value, array(
                            'data'        => array(
                                'data_type' => 'number',
                                'decimals'  => 2,
                                'min'       => 0,
                                'max'       => 100
                            ),
                            'addon_right' => '<i class="fa fa-percent"></i>'
                ));
                break;

            case 'date_from':
                $html = BimpInput::renderInput('date', 'date_from', (string) $value);
                break;

            case 'date_to':
                $html = BimpInput::renderInput('date', 'date_to', (string) $value);
                break;
        }

        return $html;
    }

    // Actions: 

    public function actionAttributeEquipment($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Equipement attribué avec succès';

        if (!isset($data['id_equipment']) || !(int) $data['id_equipment']) {
            $errors[] = 'Veuillez sélectionner un équipement';
        } else {
            $errors = $this->checkEquipment();
            
            if (!count($errors)) {
                $this->set('id_equipment', (int) $data['id_equipment']);
                if (isset($data['pu_ht'])) {
                    $this->pu_ht = (float) $data['pu_ht'];
                }

                if (isset($data['tva_tx'])) {
                    $this->tva_tx = (float) $data['tva_tx'];
                }

                if (isset($data['id_fourn_price'])) {
                    $this->id_fourn_price = (int) $data['id_fourn_price'];
                }

                $errors = $this->update($warnings);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function resetPositions()
    {
        parent::resetPositions();

        $this->setLinesPositions();
    }

    public function setPosition($position)
    {
        $result = parent::setPosition($position);

        $this->setLinesPositions();

        return $result;
    }

    public function reset()
    {
        $this->id_product = null;
        $this->id_fourn_price = null;
        $this->desc = null;
        $this->qty = 1;
        $this->pu_ht = null;
        $this->pa_ht = null;
        $this->tva_tx = null;
        $this->date_from = null;
        $this->date_to = null;

        if (!is_null($this->product)) {
            unset($this->product);
            $this->product = null;
        }
        parent::reset();
    }

    public function validatePost()
    {
        $errors = parent::validatePost();

        if (!count($errors)) {
            $data = null;

            switch ($this->getData('type')) {
                case self::LINE_PRODUCT:
                case self::LINE_FREE:
                    $data = static::$product_line_data;
                    break;

                case self::LINE_TEXT:
                    $data = static::$text_line_data;
                    break;
            }

            if (is_array($data)) {
                foreach ($data as $field => $params) {
                    if (BimpTools::isSubmit($field)) {
                        $this->{$field} = BimpTools::getValue($field);
                    } elseif (is_null($this->{$field}) && isset($params['default'])) {
                        $this->{$field} = $params['default'];
                    }

                    if (!is_null($this->{$field})) {
                        if (!BimpTools::checkValueByType($params['type'], $this->{$field})) {
                            $errors[] = 'Valeur invalide: "' . $field . '"';
                        }
                    }
                }
            } else {
                $errors[] = 'Type invalide';
            }
        }

        return $errors;
    }

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            switch ($this->getData('type')) {
                case self::LINE_TEXT:
                    if (is_null($this->desc) || !$this->desc) {
                        $errors[] = 'Description obligatoire';
                    }
                    $this->id_product = null;
                    $this->id_fourn_price = null;
                    $this->tva_tx = null;
                    $this->qty = null;
                    $this->pa_ht = null;
                    $this->remise = null;
                    $this->date_to = null;
                    $this->date_from = null;
                    $this->set('id_equipment', 0);
                    break;

                case self::LINE_PRODUCT:
                    if (is_null($this->id_product) || !$this->id_product) {
                        $errors[] = 'Produit ou service obligatoire';
                    }

                case self::LINE_FREE:
                    if ((int) $this->getData('id_equipment')) {
                        $errors = $this->checkEquipment();
                        if (count($errors)) {
                            return $errors;
                        }
                    }

                    if ((int) $this->getData('id_equipment')) {
                        $equipment = $this->getChildObject('equipment');
                        if ((float) $equipment->getData('prix_achat') > 0) {
                            $this->pa_ht = (float) $equipment->getData('prix_achat');
                        }
                    }

                    if (!is_null($this->id_fourn_price) && (int) $this->id_fourn_price) {
                        $fournPrice = BimpObject::getInstance('bimpcore', 'Bimp_ProductFournisseurPrice', (int) $this->id_fourn_price);
                        if (BimpObject::objectLoaded($fournPrice)) {
                            $this->pa_ht = (float) $fournPrice->getData('price');
                        } else {
                            $errors[] = 'Prix fournisseur d\'ID ' . $this->id_fourn_price . ' inexistant';
                        }
                    }


                    if ((!is_null($this->date_from) && $this->date_from) || (!is_null($this->date_to) && $this->date_to)) {
                        $date_check = true;
                        if (is_null($this->date_from) || !(string) $this->date_from) {
                            $errors[] = 'Date de début non spécifiée';
                            $date_check = false;
                        } elseif (preg_match('^\d{4}\-\d{2}\-\d{2}$', (string) $this->date_from)) {
                            $errors[] = 'Date de début invalide';
                            $date_check = false;
                        }

                        if (is_null($this->date_to) || !(string) $this->date_to) {
                            $errors[] = 'Date de fin non spécifiée';
                            $date_check = false;
                        } elseif (preg_match('^\d{4}\-\d{2}\-\d{2}$', (string) $this->date_to)) {
                            $errors[] = 'Date de fin invalide';
                            $date_check = false;
                        }

                        if ($date_check) {
                            if ($this->date_from > $this->date_to) {
                                $errors[] = 'La date de début doit être inférieure à la date de fin';
                            }
                        }
                    }
                    break;
            }
        }

        return $errors;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        if (!$this->isEditable(true)) {
            return array('Création de la ligne impossible');
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $this->checkProductSerialisable();

            $errors = $this->createLine(false);
            if (count($errors)) {
                $this->delete(true);
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        if (!$this->isEditable(true)) {
            return array('Mise à jour de la ligne impossible');
        }

        $errors = parent::update($warnings, $force_update);
        $errors = array_merge($errors, $this->updateLine(false));

        return $errors;
    }

    public function fetch($id)
    {
        if (parent::fetch($id)) {
            if (!$this->fetchLine()) {
                $this->reset();
                return false;
            }

            return true;
        }



        return false;
    }

    public function delete($force_delete = false)
    {
        if (!$this->isDeletable(true)) {
            return array('Suppression de la ligne impossible');
        }
        $errors = $this->deleteLine();

        if (!count($errors)) {
            $errors = parent::delete($force_delete);
        }

        return $errors;
    }
}
