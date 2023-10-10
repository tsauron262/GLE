<?php

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

    const PRODUIT = 1;
    const SERVICE = 2;
    const LOGICIEL = 3;

    public static $product_types = array(
        self::PRODUIT  => array('label' => 'Produit', 'icon' => 'fas_box'),
        self::SERVICE  => array('label' => 'Service', 'icon' => 'fas_hand-holding'),
        self::LOGICIEL => array('label' => 'Logiciel', 'icon' => 'fas_cogs')
    );
    public $product = null;

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
        if ($force_delete) {
            return 1;
        }

        return (int) $this->isParentEditable($errors);
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        // Todo : gérer tous les champs à toutes les étapes. 
        if ($this->isLoaded()) {
            if (in_array($field, array('type', 'id_product'))) {
                return 0;
            }
        }

        if (in_array($field, array('qty', 'pu_ht', 'tva_tx', 'pa_ht', 'remise', 'id_fourn_price', 'product_type'))) {
            return $this->isParentEditable();
        }

        switch ($field) {
            case 'serialisable':
                if (!$force_edit && (int) $this->getData('type') === self::TYPE_PRODUCT) {
                    return 0;
                }
                return 1;
        }

        return 1;
    }

    public function isParentEditable(&$errors = [])
    {
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent)) {
            if (!(int) $parent->areLinesEditable()) {
                $errors[] = 'La demande de location ne peut plus être modifiée';
                return 0;
            }

            return 1;
        }

        return 0;
    }

    public function isLineProduct()
    {
        return (int) ((int) $this->getData('type') === self::TYPE_PRODUCT);
    }

    public function isLineFree()
    {
        return (int) ((int) $this->getData('type') === self::TYPE_FREE);
    }

    public function isLineText()
    {
        return (int) ((int) $this->getData('type') === self::TYPE_TEXT);
    }

    public function isProductSerialisable()
    {
        if ((int) $this->getData('id_product')) {
            $prod = $this->getChildObject('product');
            if (BimpObject::objectLoaded($prod)) {
                return $prod->isSerialisable();
            }

            return 0;
        }

        return (int) $this->getData('serialisable');
    }

    public function areAllCommandesFournEditable()
    {
        $commandes_fourn = $this->getData('commandes_fourn');

        if (is_array($commandes_fourn)) {
            foreach ($commandes_fourn as $id_commande => $qty) {
                if ((float) $qty > 0) {
                    $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande);
                    if (BimpObject::objectLoaded($commande)) {
                        if ($commande->getData('fk_statut') > 0) {
                            return 0;
                        }
                    }
                }
            }
        }

        return 1;
    }

    // Getters params: 

    public function getInputValue($field_name)
    {
        $type = $this->getData('type');

        switch ($field_name) {
            case 'id_product':
                if ($type !== self::TYPE_PRODUCT) {
                    return 0;
                }
                break;

            case 'serialisable':
                $product = $this->getProduct();
                if (BimpObject::objectLoaded($product)) {
                    return (int) $product->isSerialisable();
                }
                if (isset($this->data['serialisable'])) {
                    return (int) $this->data['serialisable'];
                }
                return 0;

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
                        return BimpCache::cacheServeurFunction('getDefaultTva');

                    case self::TYPE_FREE:
                        if (isset($this->data['tva_tx'])) {
                            return (float) $this->data['tva_tx'];
                        }
                        return BimpCache::cacheServeurFunction('getDefaultTva');

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

            case 'id_fourn_price':
                if ($type === self::TYPE_PRODUCT) {
                    $product = $this->getProduct();
                    if (BimpObject::objectLoaded($product)) {
                        $sql = 'SELECT MAX(fp.rowid) as id FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price fp WHERE fp.fk_product = ' . (int) $product->id;
                        $result = $this->db->executeS($sql);
                        if (isset($result[0]->id)) {
                            return (int) $result[0]->id;
                        }
                    }
                }
                return 0;

            case 'use_pu_for_pa':
                if (!$this->isLoaded()) {
                    return 1;
                }

                if (!(int) $this->getData('id_fourn_price')) {
                    if ((float) $this->getData('pu_ht') === (float) $this->getData('pa_ht')) {
                        return 1;
                    }
                }
                return 0;
        }

        return $this->getData($field_name);
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

    public function getTypeForBimpCommObjectLine()
    {
        BimpObject::loadClass('bimpcommercial', 'ObjectLine');

        switch ((int) $this->getData('type')) {
            case self::TYPE_PRODUCT:
                return ObjectLine::LINE_PRODUCT;

            case self::TYPE_FREE:
                return ObjectLine::LINE_FREE;

            case self::TYPE_TEXT:
                return ObjectLine::LINE_TEXT;
        }

        return 0;
    }

    // Getters array: 

    public function getProductTypesArray()
    {
        $values = self::$product_types;

        if ((int) $this->getData('type') === self::TYPE_PRODUCT) {
            $product = $this->getProduct();
            if (BimpObject::objectLoaded($product)) {
                if (!(int) $product->getData('fk_product_type')) {
                    unset($values[self::SERVICE]);
                } else {
                    unset($values[self::PRODUIT]);
                    unset($values[self::LOGICIEL]);
                }
            }
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

    // Getters données: 

    public function getProduct()
    {
        $id_product = (int) $this->getData('id_product');

        if (BimpObject::objectLoaded($this->product) && (!$id_product || $this->product->id !== $id_product)) {
            $this->product = null;
        }

        if ($id_product) {
            if (!BimpObject::objectLoaded($this->product)) {
                $this->product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
            }

            return $this->product;
        }

        return null;
    }

    public function getTotalHT()
    {
        $pu_ht = (float) $this->getData('pu_ht');

        $remise = (float) $this->getData('remise');
        if ($remise) {
            $pu_ht -= (float) ($pu_ht * ($remise / 100));
        }

        return $pu_ht * (float) $this->getData('qty');
    }

    public function getTotalTTC()
    {
        return BimpTools::calculatePriceTaxIn($this->getTotalHT(), (float) $this->getData('tva_tx'));
    }

    public function getTypeForObjectLine()
    {
        BimpObject::loadClass('bimpcommercial', 'ObjectLine');

        switch ((int) $this->getData('type')) {
            case self::TYPE_PRODUCT:
                return ObjectLine::LINE_PRODUCT;

            case self::TYPE_FREE:
                return ObjectLine::LINE_FREE;

            case self::TYPE_TEXT:
                return ObjectLine::LINE_TEXT;
        }

        return 0;
    }

    public function getQtyCommandesFourn($id_commande_fourn = 0, $id_commande_excluded = 0)
    {
        $qty = 0;
        $commandes_fourn = $this->getData('commandes_fourn');

        if (is_array($commandes_fourn)) {
            foreach ($commandes_fourn as $id_commande => $qty_commande) {
                if ($id_commande_fourn && $id_commande !== $id_commande_fourn) {
                    continue;
                }
                if ($id_commande_excluded && $id_commande === $id_commande_excluded) {
                    continue;
                }
                $qty += (float) $qty_commande;
            }
        }

        return $qty;
    }

    public function getRefProduct()
    {
        switch ($this->getData('type')) {
            case self::TYPE_PRODUCT:
                $product = $this->getProduct();
                if (BimpObject::objectLoaded($product)) {
                    return $product->getRef();
                }
                break;

            case self::TYPE_FREE:
                return $this->getData('ref');
        }

        return '';
    }

    // Affichages: 

    public function displayDesc($mode_light = false, $no_link = false, $include_serials = false)
    {
        $html = '';

        switch ((int) $this->getData('type')) {
            case self::TYPE_PRODUCT:
                $product = $this->getProduct();
                if (BimpObject::objectLoaded($product)) {
                    if ($no_link) {
                        $html .= '<b>' . $product->getRef() . '</b><br/>';
                    } else {
                        $html .= $product->getLink() . '<br/>';
                    }
                    $html .= $product->getName();
                }
                break;

            case self::TYPE_FREE:
                $html .= '<b>' . $this->getData('ref') . '</b>';
            case self::TYPE_TEXT:
                $html .= ($html ? '<br/>' : '') . $this->getData('label');
                break;
        }

        if (!$mode_light) {
            $desc = $this->getData('description');
            if ($desc) {
                $html .= ($html ? '<br/><br/>' : '') . $desc;
            }
        }

        if ($include_serials && (int) $this->getData('serialisable')) {
            $serials = $this->getData('serials');

            if (count($serials)) {
                $html .= '<div style="margin-top: 10px; font-style: italic">';
                $html .= '<b>N° de série :</b> ' . implode(', ', $serials);
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function displayCommandesFournQties()
    {
        $qty_ordered = (float) $this->getQtyCommandesFourn();
        $qty = (float) $this->getData('qty');

        $class = 'success';
        if ($qty_ordered < $qty) {
            $class = 'warning';
        } elseif ($qty_ordered > $qty) {
            $class = 'danger';
        }

        return '<span class="' . $class . '">' . $qty_ordered . '</span>';
    }

    // Traitements : 

    public function hydrateBimpCommObjectLine(ObjectLine $objectLine, $qty = null)
    {
        if (is_a($objectLine, 'ObjectLine')) {
            $type = (int) $this->getData('type');
            switch ($type) {
                case self::TYPE_PRODUCT:
                case self::TYPE_FREE:
                    if ($type === self::TYPE_PRODUCT) {
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
                        $objectLine->desc .= ($objectLine->desc ? '<br/>' : '') . $desc;
                    }
                    break;

                case self::TYPE_TEXT:
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

    // Gestion des commandes fournisseur: (TO CHECK)

    public function createCommandeFournLine($id_commande, $qty, $update_commandes_fourn_field = true)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if (!(int) $id_commande) {
                $errors[] = 'ID de la commande fournisseur absent';
            } else {
                $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande);

                if (!BimpObject::objectLoaded($commande)) {
                    $errors[] = 'La commande fournisseur d\'ID ' . $id_commande . ' n\'existe pas';
                } else {
                    if ((int) $commande->getData('fk_statut') !== 0) {
                        $errors[] = 'La commande fournisseur ' . $commande->getRef() . ' n\'a plus le statut "Brouillon"';
                    }
                }
            }

            if (!count($errors)) {
                $comm_line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');

                if ($comm_line->find(array(
                            'id_obj'             => (int) $id_commande,
                            'linked_id_object'   => (int) $this->id,
                            'linked_object_name' => 'bf_line',
                                ), true, true)) {
                    $errors = $this->updateCommandeFournLine($id_commande, $qty, $update_commandes_fourn_field);
                } elseif ((float) $qty) {
                    if ((float) $qty + (float) $this->getQtyOrdered() > (float) $this->getData('qty')) {
                        $errors[] = 'Erreurs: quantités totales à commander supérieures à la quantité de la ligne à financer';
                    } else {
                        $errors = $comm_line->validateArray(array(
                            'id_obj'             => (int) $id_commande,
                            'type'               => $this->getTypeForObjectLine(),
                            'editable'           => 0,
                            'deletable'          => 0,
                            'linked_id_object'   => (int) $this->id,
                            'linked_object_name' => 'bf_line'
                        ));
                    }

                    if (!count($errors)) {
                        $this->hydrateObjectLine($comm_line, (float) $qty);
                        $w = array();
                        $errors = $comm_line->create($w, true);
                    }

                    if (!count($errors)) {
                        $commandesFourn = $this->getData('commandes_fourn');
                        if (!is_array($commandesFourn)) {
                            $commandesFourn = array();
                        }
                        $commandesFourn[(int) $id_commande] = (float) $qty;
                        if ($update_commandes_fourn_field) {
                            $this->updateField('commandes_fourn', $commandesFourn);
                        } else {
                            $this->set('commandes_fourn', $commandesFourn);
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function updateCommandeFournLine($id_commande, $qty, $update_commandes_fourn_field = true)
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } elseif (!(int) $id_commande) {
            $errors[] = 'ID de la commande fournisseur absent';
        } else {
            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande);

            if (!BimpObject::objectLoaded($commande)) {
                $errors[] = 'La commande fournisseur d\'ID ' . $id_commande . ' n\'existe pas';
            } else {
                if ((int) $commande->getData('fk_statut') !== 0) {
                    $errors[] = 'La commande fournisseur ' . $commande->getRef() . ' n\'a plus le statut "Brouillon"';
                }
            }
        }

        if (!count($errors)) {
            if (!(float) $qty) {
                $errors = $this->deleteCommandeFournLine($id_commande);
            } else {
                $cf_line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');
                if ($cf_line->find(array(
                            'id_obj'             => (int) $id_commande,
                            'linked_id_object'   => (int) $this->id,
                            'linked_object_name' => 'bf_line',
                                ), true, true)) {
                    if ((float) $qty + (float) $this->getQtyOrdered($id_commande) > (float) $this->getData('qty')) {
                        $errors[] = 'Erreurs: quantités totales à commander supérieures à la quantité de la ligne à financer';
                    } else {
                        $cf_line->set('type', $this->getTypeForObjectLine());
                        $this->hydrateObjectLine($cf_line, $qty);
                        $warnings = array();
                        $errors = $cf_line->update($warnings, true);
                        if (!count($errors)) {
                            $commandesFourn = $this->getData('commandes_fourn');
                            if (!is_array($commandesFourn)) {
                                $commandesFourn = array();
                            }
                            $commandesFourn[$id_commande] = (float) $qty;
                            if ($update_commandes_fourn_field) {
                                $this->updateField('commandes_fourn', $commandesFourn);
                            } else {
                                $this->set('commandes_fourn', $commandesFourn);
                            }
                        }
                    }
                } else {
                    $errors = $this->createCommandeFournLine($id_commande, $qty, $update_commandes_fourn_field);
                }
            }
        }

        return $errors;
    }

    public function updateCommandesFournLines($qties = array(), $update_commandes_fourn_field = true)
    {
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        $errors = array();

        $commandesFourn = $this->getData('commandes_fourn');

        if (is_array($commandesFourn)) {
            foreach ($commandesFourn as $id_commande => $qty) {
                if (isset($qties[$id_commande])) {
                    $qty = (float) $qties[$id_commande];
                }
                $line_errors = $this->updateCommandeFournLine((int) $id_commande, (float) $qty, false);
                if (count($line_errors)) {
                    $comm = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande);
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la mise à jour de la ligne correpondante pour la commande "' . $comm->getRef() . '"');
                } else {
                    $commandesFourn[$id_commande] = $qty;
                }
            }

            if ($update_commandes_fourn_field) {
                $this->updateField('commandes_fourn', $commandesFourn);
            } else {
                $this->set('commandes_fourn', $commandesFourn);
            }
        }

        return $errors;
    }

    public function deleteCommandeFournLine($id_commande, $update_commandes_fourn_field = true)
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_this') . ' absent';
        } elseif (!(int) $id_commande) {
            $errors[] = 'ID de la commande fournisseur absent';
        } else {
            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande);

            if (!BimpObject::objectLoaded($commande)) {
                $errors[] = 'La commande fournisseur d\'ID ' . $id_commande . ' n\'existe pas';
            } elseif ((int) $commande->getData('fk_statut') !== 0) {
                $errors[] = 'La commande fournisseur ' . $commande->getRef() . ' n\'a plus le statut "Brouillon"';
            }
        }

        if (!count($errors)) {
            $cf_line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');
            if ($cf_line->find(array(
                        'id_obj'             => (int) $id_commande,
                        'linked_id_object'   => (int) $this->id,
                        'linked_object_name' => 'bf_line',
                            ), true, true)) {
                $del_warnings = array();
                $errors = $cf_line->delete($del_warnings, true);
                if (!count($errors)) {
                    $commandesFourn = $this->getData('commandes_fourn');
                    if (isset($commandesFourn[$id_commande])) {
                        unset($commandesFourn[$id_commande]);
                        if ($update_commandes_fourn_field) {
                            $this->updateField('commandes_fourn', $commandesFourn);
                        } else {
                            $this->set('commandes_fourn', $commandesFourn);
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function deleteCommandesFournLines($update_commandes_fourn_field = true)
    {
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        $errors = array();

        $commandesFourn = $this->getData('commandes_fourn');

        if (is_array($commandesFourn)) {
            foreach ($commandesFourn as $id_commande => $qty) {
                $line_errors = $this->deleteCommandeFournLine($id_commande, false);
                if (count($line_errors)) {
                    $comm = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande);
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la suppression de la ligne de commande fournisseur pour la commande "' . $comm->getRef() . '"');
                } else {
                    unset($commandesFourn[$id_commande]);
                }
            }
            if ($update_commandes_fourn_field) {
                $this->updateField('commandes_fourn', $commandesFourn);
            } else {
                $this->set('commandes_fourn', $commandesFourn);
            }
        }

        return $errors;
    }

    public function onCommandeFournCancel($id_commande)
    {
        if (!$this->isLoaded()) {
            return array('ID de la ligne de location absent');
        }
        $errors = array();
        $commandesFourn = $this->getData('commandes_fourn');

        if (is_array($commandesFourn) && array_key_exists((int) $id_commande, $commandesFourn)) {
            unset($commandesFourn[$id_commande]);
            $errors = $this->updateField('commandes_fourn', $commandesFourn);
        }

        return $errors;
    }

    // Overrides: 

    public function reset()
    {
        $this->product = null;
        parent::reset();
    }

    public function validate()
    {
        $use_pu_for_pa = (int) BimpTools::getValue('use_pu_for_pa', 0);
        $errors = array();

        switch ($this->getData('type')) {
            case self::TYPE_PRODUCT:
                $this->set('label', '');
                if ((int) $this->getData('id_product')) {
                    $product = $this->getChildObject('product');
                    if (!BimpObject::objectLoaded($product)) {
                        $errors[] = 'Le produit #' . $this->getData('id_product') . ' n\'existe plus';
                    } else {
                        $this->set('ref', $product->getRef());
                        $isSerialisable = (int) $product->isSerialisable();
                        $this->set('serialisable', $isSerialisable);
                        if ($use_pu_for_pa) {
                            $this->set('id_fourn_price', 0);
                            $this->set('pa_ht', (float) $this->getData('pu_ht'));
                        } else {
                            $id_fourn_price = (int) $this->getData('id_fourn_price');
                            if ($id_fourn_price) {
                                $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_fourn_price);
                                if (!BimpObject::objectLoaded($pfp)) {
                                    $this->set('id_fourn_price', 0);
                                } elseif ((int) $pfp->getData('fk_product') !== (int) $product->id) {
                                    $errors[] = 'Le prix d\'achat fournisseur sélectionné ne correspond pas au produit sélectionné';
                                } else {
                                    $this->set('pa_ht', (float) $pfp->getData('price'));
                                }
                            }
                        }
                        if (!(int) $product->getData('fk_product_type')) {
                            if (!in_array((int) $this->getData('product_type'), array(self::PRODUIT, self::LOGICIEL))) {
                                $this->set('product_type', self::PRODUIT);
                            }
                        } else {
                            $this->set('product_type', self::SERVICE);
                        }
                    }
                } else {
                    $errors[] = 'Produit absent';
                }
                break;

            case self::TYPE_FREE:
                if (!$this->getData('label')) {
                    $errors[] = 'Libellé obligatoire pour ce type de ligne';
                }
                if ($use_pu_for_pa) {
                    $this->set('pa_ht', (float) $this->getData('pu_ht'));
                }
                $this->set('id_product', 0);
                $this->set('id_fourn_price', 0);
                break;

            case self::TYPE_TEXT:
//                if (!$this->getData('description')) {
//                    $errors[] = 'Description obligatoire pour ce type de ligne';
//                }

                $this->set('id_product', 0);
                $this->set('id_fourn_price', 0);
                $this->set('ref', '');
                $this->set('label', '');
                $this->set('qty', 0);
                $this->set('pu_ht', 0);
                $this->set('tva_tx', 0);
                $this->set('pa_ht', 0);
                $this->set('remise', 0);
                $this->set('serials', '');
                $this->set('commandes_fourn', '');
                break;
        }

        $this->set('total_ht', $this->getTotalHT());
        $this->set('total_ttc', $this->getTotalTTC());

        // Check des serials: 
        if ($this->isProductSerialisable()) {
            $serials = $this->getData('serials');
            $diff = count($serials) - (int) $this->getData('qty');
            if ($diff > 0 && $this->getData('qty') > 0) {
                $errors[] = 'Le nombre de n° de série enregistrés est supérieur aux quantités de la ligne. Veuillez retirer ' . $diff . ' n° de série ( '.print_r($serials, 1).')';
            }
        }

        if (!count($errors)) {
            $errors = parent::validate();
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if ($this->areAllCommandesFournEditable()) {
                $lines_errors = $this->updateCommandesFournLines(array(), true);
                if (count($lines_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($lines_errors, 'Des erreurs sont survenues lors de la mise à jour des commandes fournisseur correspondantes');
                }
            }
        }

        return $errors;
    }
}
