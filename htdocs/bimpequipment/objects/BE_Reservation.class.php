<?php

class BE_Reservation extends BimpObject
{

    public function isProduct()
    {
        return 1;
    }

    public function isEquipment()
    {
        return 1;
    }

//     Overrides
    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            $equipment = $this->getChildObject('equipment');

            if (!is_null($equipment) && $equipment->isLoaded()) {
                $this->set('id_product', (int) $equipment->getData('id_product'));
                $errors[] = 'Equip';
            } else {
                $product = $this->getChildObject('product');
                if (is_null($product) || !isset($product->id) || !$product->id) {
                    $errors[] = 'Aucun produit ou équipement sélectionné';
                } else {
                    if (isset($product->array_options['options_serialisable']) && (int) $product->array_options['options_serialisable']) {
                        $errors[] = 'Vous devez obligatoirement choisir un équipement pour le produit sélectionné';
                    } else {
                        $errors[] = print_r($product->array_options, true);
                    }
                }
            }
        }

        return $errors;
    }
}
