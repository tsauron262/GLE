<?php

class BE_Reservation extends BimpObject
{

    public static $origin_elements = array(
        0 => '',
        1 => 'Utilisateur',
        2 => 'Bon de réservation',
        3 => 'Transfert stock'
    );

    public function isEquipment()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('id_equipment')) {
                return 1;
            }
        }

        return 0;
    }

    public function isProduct()
    {
        return ($this->isEquipment() ? 0 : 1);
    }

    public function getOriginIdElementInput()
    {
        $element = (int) $this->getData('origin_element');
        if ($element) {
            switch ($element) {
                case 1:
                    return BimpInput::renderInput('search_user', 'origin_id_element', $this->getData('origin_id_element'));

                case 2:
                case 3:
                    return BimpInput::renderInput('text', 'origin_id_element', $this->getData('origin_id_element'), array(
                                'data' => array(
                                    'data_type' => 'number',
                                    'decimals'  => 0,
                                    'unsigned'  => 1
                                )
                    ));
            }
        }

        return '';
    }

    public function displayOriginElement()
    {
        if ($this->isLoaded()) {
            $id_element = (int) $this->getData('origin_id_element');
            if (!$id_element) {
                return '';
            }

            switch ((int) $this->getData('origin_element')) {
                case 1:
                    global $db;
                    $user = new User($db);
                    if ($user->fetch($id_element) <= 0) {
                        return BimpRender::renderAlerts('L\'utilisateur d\'ID ' . $id_element . ' n\'existe pas');
                    }
                    return $user->getNomUrl(1) . BimpRender::renderObjectIcons($user, true, null);

                case 2:
                case 3:
                    return 'ID: ' . $id_element;
            }
        }

        return '';
    }

//     Overrides
    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            $equipment = $this->getChildObject('equipment');

            if (!is_null($equipment) && $equipment->isLoaded()) {
                $this->set('id_product', (int) $equipment->getData('id_product'));
            } else {
                $product = $this->getChildObject('product');
                if (is_null($product) || !isset($product->id) || !$product->id) {
                    $errors[] = 'Aucun produit ou équipement sélectionné';
                } else {
                    if (isset($product->array_options['options_serialisable']) && (int) $product->array_options['options_serialisable']) {
                        $errors[] = 'Vous devez obligatoirement choisir un équipement pour le produit sélectionné';
                    }
                }
            }
        }

        return $errors;
    }
}
