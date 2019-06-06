<?php

class BS_PretProduct extends BimpObject
{

    public function decreaseStock($qty = null, $label_ext = null)
    {
        if (is_null($qty)) {
            $qty = (float) $this->getData('qty');
        }

        if (!(float) $qty) {
            return array();
        }

        $errors = array();

        if (!(int) $this->getData('id_product')) {
            $errors[] = 'produit absent';
        } else {
            $product = $this->getChildObject('product');
            if (!BimpObject::objectLoaded($product)) {
                $errors[] = 'Le produit d\'ID ' . $this->getData('id_product') . ' n\'existe pas';
            } else {
                $pret = $this->getParentInstance();
                if (!BimpObject::objectLoaded($pret)) {
                    $errors[] = 'ID du prêt absent';
                } else {
                    global $user;
                    $label = 'Prêt de matériel #' . $pret->id;
                    if (!is_null($label_ext)) {
                        $label .= ' - ' . $label_ext;
                    }
                    $codemove = $pret->getRef() . '_' . $this->id;
                    if ($product->dol_object->correct_stock($user, (int) $pret->getData('id_entrepot'), $qty, 1, $label, 0, $codemove, '', 0) <= 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($product->dol_object));
                    }
                }
            }
        }

        return $errors;
    }

    public function increaseStock($qty = null, $label_ext = null)
    {
        if (is_null($qty)) {
            $qty = (float) $this->getData('qty');
        }

        if (!(float) $qty) {
            return array();
        }

        $errors = array();

        if (!(int) $this->getData('id_product')) {
            $errors[] = 'produit absent';
        } else {
            $product = $this->getChildObject('product');
            if (!BimpObject::objectLoaded($product)) {
                $errors[] = 'Le produit d\'ID ' . $this->getData('id_product') . ' n\'existe pas';
            } else {
                $pret = $this->getParentInstance();
                if (!BimpObject::objectLoaded($pret)) {
                    $errors[] = 'ID du prêt absent';
                } else {
                    global $user;
                    $label = 'Prêt de matériel #' . $pret->id;
                    if (!is_null($label_ext)) {
                        $label .= ' - ' . $label_ext;
                    } else {
                        $label .= ' - Retour';
                    }
                    $codemove = $pret->getRef() . '_' . $this->id;
                    if ($product->dol_object->correct_stock($user, (int) $pret->getData('id_entrepot'), $qty, 0, $label, 0, $codemove, '', 0) <= 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($product->dol_object));
                    }
                }
            }
        }

        return $errors;
    }

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            $product = $this->getChildObject('product');

            if (!BimpObject::objectLoaded($product)) {
                $errors[] = 'Le produit d\'ID ' . $this->getData('id_product') . ' n\'existe pas';
            } else {
                if ((int) $product->getData('fk_product_type') !== 0) {
                    $errors[] = 'Vous ne pouvez pas sélectionner un produit de type "' . Bimp_Product::$product_type[(int) $product->getData('fk_product_type')]['label'] . '"';
                } elseif ($product->isSerialisable()) {
                    $errors[] = 'Produit sérialisable. Veuillez sélectionner un équipement';
                }
            }
        }

        return $errors;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        $pret = $this->getParentInstance();
        if (!BimpObject::objectLoaded($pret)) {
            $errors[] = 'ID du prêt absent';
            return $errors;
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            if (!(int) $pret->getData('returned')) {
                $stock_errors = $this->decreaseStock();
                if (count($stock_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($stock_errors, 'Erreurs lors de la correction des stocks');
                }
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = array();

        $pret = $this->getParentInstance();
        if (!BimpObject::objectLoaded($pret)) {
            $errors[] = 'ID du prêt absent';
            return $errors;
        }

        $init_qty = (float) $this->getInitData('qty');

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if (!(int) $pret->getData('returned')) {
                $qty_diff = (float) $this->getData('qty') - $init_qty;

                if ($qty_diff) {
                    $stock_errors = array();
                    if ($qty_diff > 0) {
                        $stock_errors = $this->decreaseStock($qty_diff, 'Mise à jour des quantités');
                    } else {
                        $stock_errors = $this->increaseStock(abs($qty_diff), 'Mise à jour des quantités');
                    }

                    if (count($stock_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($stock_errors, 'Erreurs lors de la correction des stocks');
                    }
                }
            }
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $errors = array();

        $pret = $this->getParentInstance();
        if (BimpObject::objectLoaded($pret)) {
            if (!(int) $pret->getData('returned')) {
                $stock_errors = $this->increaseStock(null, 'Suppression du prêt');
                if (count($stock_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($stock_errors, 'Erreurs lors de la correction des stocks. Prêt de produit non supprimé');
                }
            }
        } else {
            $errors[] = 'ID du prêt absent';
        }

        if (!count($errors)) {
            $errors = parent::delete($warnings, $force_delete);
        }

        return $errors;
    }
}
