<?php

class BS_PretProduct extends BimpObject
{

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
}
