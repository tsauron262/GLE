<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpLine.class.php';

class BF_Line extends BimpObject
{

    const TYPE_PRODUCT = 1;
    const TYPE_FREE = 2;
    const TYPE_TEXT = 3;

    public static $types = array(
        self::TYPE_PRODUCT => 'Produit / Service',
        self::TYPE_FREE    => 'Ligne libre',
        self::TYPE_TEXT    => 'Texte seulement'
    );

    // Getters booléens: 

    public function isCreatable($force_create = false, &$errors = []): int
    {
        if (!$force_create) {
            return (int) $this->isParentEditable($errors);
        }

        return 1;
    }

    public function isEditable($force_edit = false, &$errors = []): int
    {
        return 1;
    }

    public function isDeletable($force_delete = false, &$errors = [])
    {
        if (!$force_delete) {
            return (int) $this->isParentEditable($errors);
        }

        return 1;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if ($this->isLoaded()) {
            if (in_array($field, array('type', 'id_product'))) {
                return 0;
            }
        }

        if (in_array($field, array('qty', 'pu_ht', 'tva_tx', 'pa_ht', 'remise'))) {
            return $this->isParentEditable();
        }

        return 1;
    }

    public function isParentEditable(&$errors = [])
    {
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent)) {
            if (!(int) $parent->areLinesEditable()) {
                $errors[] = 'La demande de financement ne peut plus être mmodifiée';
                return 0;
            }

            return 1;
        }

        return 0;
    }

    // Getters params: 

    public function getDefaultValue($field)
    {
        $type = $this->getData('type');

        switch ($field) {
            case 'qty':
                switch ($type) {
                    case self::TYPE_PRODUCT:
                    case self::TYPE_FREE:
                        if (isset($this->data['qty'])) {
                            return $this->data['qty'];
                        }
                        return 1;

                    case self::TYPE_TEXT:
                        return 0;
                }

                break;

            case 'pu_ht':
                switch ($type) {
                    case self::TYPE_PRODUCT:
                        if (isset($this->data['pu_ht'])) {
                            return (float) $this->data['pu_ht'];
                        }

                        $product = $this->getProduct();
                        if (BimpObject::objectLoaded($product) && $product->hasFixePu()) {
                            return (float) $product->getData('price');
                        }
                        return 0;

                    case self::TYPE_FREE:
                        if (isset($this->data['pu_ht'])) {
                            return (float) $this->data['pu_ht'];
                        }
                        return 0;

                    case self::TYPE_TEXT:
                        return 0;
                }
                break;

            case 'tva_tx':
                switch ($type) {
                    case self::TYPE_PRODUCT:
                        if (isset($this->data['tva_tx'])) {
                            return (float) $this->data['tva_tx'];
                        }

                        $product = $this->getProduct();
                        if (BimpObject::objectLoaded($product)) {
                            return (float) $product->getData('tva_tx');
                        }
                        return BimpTools::getDefaultTva();

                    case self::TYPE_FREE:
                        if (isset($this->data['tva_tx'])) {
                            return (float) $this->data['tva_tx'];
                        }
                        return BimpTools::getDefaultTva();

                    case self::TYPE_TEXT:
                        return 0;
                }
                break;

            case 'pa_ht':
                switch ($type) {
                    case self::TYPE_PRODUCT:
                        if (isset($this->data['pa_ht'])) {
                            return (float) $this->data['pa_ht'];
                        }

                        $product = $this->getProduct();
                        if (BimpObject::objectLoaded($product) && $product->hasFixePa()) {
                            return (float) $product->getCurrentPaHt();
                        }
                        return 0;

                    case self::TYPE_FREE:
                        if (isset($this->data['pa_ht'])) {
                            return (float) $this->data['pa_ht'];
                        }
                        return 0;

                    case self::TYPE_TEXT:
                        return 0;
                }
                break;
        }
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        return $buttons;
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

    // Getters données: 

    public function getProduct()
    {
        if ((int) $this->getData('id_product')) {
            return BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $this->getData('id_product'));
        }

        return null;
    }

    public function getTotalHt()
    {
        $pu_ht = (float) $this->getData('pu_ht');

        $remise = (float) $this->getData('remise');
        if ($remise) {
            $pu_ht -= (float) ($pu_ht * ($remise / 100));
        }

        return $pu_ht * (float) $this->getData('qty');
    }

    public function getTotalTtc()
    {
        return BimpTools::calculatePriceTaxIn($this->getTotalHt(), (float) $this->getData('tva_tx'));
    }

    // Affichages: 

    public function displayDesc()
    {
        $html = '';

        switch ((int) $this->getData('type')) {
            case self::TYPE_PRODUCT:
                $product = $this->getProduct();
                if (BimpObject::objectLoaded($product)) {
                    $html .= $product->getLink() . '<br/>';
                    $html .= $product->getName();
                }
                break;

            case self::TYPE_FREE:
            case self::TYPE_TEXT:
                $html .= $this->getData('label');
                break;
        }

        return $html;
    }

    // Overrides: 

    public function validate()
    {
        $errors = array();

        switch ($this->getData('type')) {
            case self::TYPE_PRODUCT:
                $this->set('label', '');
                if ((int) $this->getData('id_product')) {
                    $product = $this->getChildObject('product');
                    if (!BimpObject::objectLoaded($product)) {
                        $errors[] = 'Le produit #' . $this->getData('id_product') . ' n\'existe plus';
                    } else {
                        $this->set('serialisable', (int) $product->isSerialisable());
                    }
                } else {
                    $errors[] = 'Produit absent';
                }
                break;

            case self::TYPE_FREE:
                $this->set('id_product', 0);

                if (!$this->getData('label')) {
                    $errors[] = 'Libellé obligatoire pour ce type de ligne';
                }
                break;

            case self::TYPE_TEXT:
                if (!$this->getData('label')) {
                    $errors[] = 'Libellé obligatoire pour ce type de ligne';
                }

                $this->set('id_product', 0);
                $this->set('qty', 0);
                $this->set('pu_ht', 0);
                $this->set('tva_tx', 0);
                $this->set('pa_ht', 0);
                $this->set('remise', 0);
                $this->set('equipments', array());
                break;
        }

        $this->set('total_ht', $this->getTotalHt());
        $this->set('total_ttc', $this->getTotalTtc());

        if (count($errors)) {
            $errors = parent::validate();
        }

        return $errors;
    }
}
