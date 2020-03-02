<?php

class BS_SavProduct extends BimpObject
{

    public function displayProduct()
    {
        $product = $this->getChildObject('product');

        if ($product->isLoaded()) {
            return $product->displayData('label');
        }

        return '';
    }

    public function getUnitPrice()
    {
        $product = $this->getChildObject('product');

        if ($product->isLoaded()) {
            return (float) $product->getData('price_ttc');
        }

        return 0;
    }

    public function canDelete()
    {
        global $user;
        return (int) $user->rights->BimpSupport->read;
    }

    public function getTotal()
    {
        $qty = (int) $this->getData('qty');
        $price = (float) $this->getUnitPrice();
        return (float) $qty * $price * (100 - $this->getData('remise')) / 100;
    }

    public function displayUnitPrice()
    {
        return BimpTools::displayMoneyValue($this->getUnitPrice(), 'EUR');
    }

    public function displayEquipment()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('id_equipment')) {
                return $this->displayData('id_equipment', 'nom_url');
            } else {
                $product = $this->getChildObject('product');
                if (!is_null($product) && $product->isLoaded()) {
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

    public function displayTotal()
    {
        return BimpTools::displayMoneyValue($this->getTotal(), 'EUR');
    }

    public function isPropalEditable()
    {
        $sav = $this->getParentInstance();
        if (!is_null($sav) && $sav->isLoaded()) {
            return (int) $sav->isPropalEditable();
        }

        return 0;
    }

    // Overrides: 

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            $reservation = null;
            if ((int) $this->getData('id_reservation')) {
                $reservation = $this->getChildObject('reservation');
                if (is_null($reservation) || !$reservation->isLoaded()) {
                    $product = $this->getChildObject('product');
                    $errors[] = 'ID de la réservation invalide pour le produit "' . $product->getData('ref') . ' - ' . $product->getData('label') . '"';
                    $reservation = null;
                }
            }

            $id_equipment = $this->getData('id_equipment');
            if ((int) $id_equipment) {
                $equipment = $this->getChildObject('equipment');
                if (!is_null($equipment) && $equipment->isLoaded()) {
                    if ((int) $this->getData('id_product') !== (int) $equipment->getData('id_product')) {
                        $errors[] = 'Equipement ' . $equipment->id . ' - "' . $equipment->getRef() . '"  invalide: ne correspondant pas au produit sélectionné';
                    } else {
                        $current_reservations = $equipment->getReservationsList();
                        if (count($current_reservations)) {
                            $id_reservation = (int) $this->getData('id_reservation');
                            if (!$id_reservation || ($id_reservation && !in_array($id_reservation, $current_reservations))) {
                                $errors[] = 'L\'équipement ' . $equipment->id . ' - "' . $equipment->getRef() . '" est déjà réservé';
                            }
                        }
                        if (!count($errors)) {
                            $sav_product = BimpObject::getInstance($this->module, $this->object_name);
                            if ($sav_product->find(array(
                                        'id_equipment' => $id_equipment
                                            ), true)) {
                                if ((int) $sav_product->id !== (int) $this->id) {
                                    $errors[] = 'L\'équipement ' . $equipment->id . ' - "' . $equipment->getRef() . '" a déjà été attribué à un produit';
                                }
                            }
                        }
                        $sav = $this->getParentInstance();
                        if (BimpObject::objectLoaded($sav)) {
                            $id_entrepot = (int) $sav->getData('id_entrepot');
                            if ($id_entrepot) {
                                $place = $equipment->getCurrentPlace();
                                if (is_null($place) || (int) $place->getData('type') !== BE_Place::BE_PLACE_ENTREPOT || (int) $place->getData('id_entrepot') !== $id_entrepot) {
                                    $errors[] = 'L\'équipement ' . $equipment->id . ' - "' . $equipment->getRef() . '" n\'est pas disponible dans l\'entrepôt sélectionné';
                                }
                            }
                        }
                    }
                } else {
                    $errors[] = 'ID de l\'équipement invalide';
                }
            }

            if (!count($errors) && !is_null($reservation)) {
                $reservation->set('id_equipment', $id_equipment);
                $errors = BimpTools::merge_array($errors, $reservation->update());
            }
        }

        return $errors;
    }

    public function create(&$warnings, $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (count($errors)) {
            return $errors;
        }

        $product = $this->getChildObject('product');

        $qty = (int) $this->getData('qty');
        if ($qty > 1) {
            if (!is_null($product) && $product->isLoaded()) {
                if ($product->isSerialisable()) {
                    $qty--;

                    while ($qty > 0) {
                        $instance = BimpObject::getInstance($this->module, $this->object_name);
                        $instance->validateArray(array(
                            'id_sav'          => (int) $this->getData('id_sav'),
                            'id_product'      => (int) $this->getData('id_product'),
                            'qty'             => 1,
                            'id_equipment'    => 0,
                            'out_of_warranty' => (int) $this->getData('out_of_warranty')
                        ));
                        $instance->create();
                        $qty--;
                    }
                    $this->set('qty', 1);
                    $this->update();
                }
            }
        }

        return $errors;
    }
}
