<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_PropalLine.class.php';

class BS_SavPropalLine extends Bimp_PropalLine
{

    public static $parent_comm_type = 'sav_propal';
    public $equipment_required = true;

    // Getters: 

    public function isEditable($force_edit = false)
    {
        if (!$force_edit && !(int) $this->getData('editable') && ($this->getData('linked_object_name') !== 'sav_apple_part')) {
            return 0;
        }

        $parent = $this->getParentInstance();
        if (!BimpObject::objectLoaded($parent)) {
            return 0;
        }

        if ($parent->field_exists('fk_statut') && (int) $parent->getData('fk_statut') === 0) {
            return 1;
        } elseif (isset($parent->dol_object->statut) && (int) $parent->dol_object->statut === 0) {
            return 1;
        }

        return 0;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
            return 0;
        }

        switch ($action) {
            case 'attributeEquipment':
                $propal = $this->getParentInstance();
                if (!BimpObject::objectLoaded($propal)) {
                    $errors[] = 'ID du devis absent';
                    return 0;
                }

                $sav = $propal->getSav();
                if (!BimpObject::objectLoaded($sav)) {
                    $errors[] = 'ID du SAV absent';
                    return 0;
                }

                if (in_array((int) $sav->getData('status'), array(BS_SAV::BS_SAV_A_RESTITUER, BS_SAV::BS_SAV_FERME))) {
                    $errors[] = 'SAV Terminé';
                    return 0;
                }
                return 1;
        }

        return (int) parent::isActionAllowed($action, $errors);
    }

    public function showWarranty()
    {
        if (in_array($this->getData('type'), array(self::LINE_PRODUCT, self::LINE_FREE))) {
            if ($this->getData('linked_object_name') === 'sav_prioritaire') {
                return 0;
            }
            return 1;
        }

        return 0;
    }

    public function isWarrantyEditable()
    {
        if ($this->isLineProduct() && $this->isEditable()) {
            return 1;
        }

        return 0;
    }

    public function getListEditForm()
    {
        if ($this->getData('linked_object_name') === 'sav_apple_part') {
            return 'apple_part';
        }

        return 'default';
    }

    // Traitements:

    public function updateSav()
    {
        if ($this->getData('linked_object_name') === 'sav_garantie') {
            return array();
        }

        $propal = $this->getParentInstance();

        if (!BimpObject::objectLoaded($propal)) {
            return array('ID du devis Absent');
        }

        $sav = $propal->getSav();

        if (!BimpObject::objectLoaded($sav)) {
            return array('ID du SAV invalide');
        }

        $error = $sav->processPropalGarantie();

        if ($error) {
            return array($error);
        }

        return array();
    }

//    public function onEquipmentAttributed()
//    {
//        $errors = array();
//
//        $propal = $this->getParentInstance();
//
//        if (!BimpObject::objectLoaded($propal)) {
//            $errors[] = 'ID du devis Absent';
//        } else {
//            if ((int) $propal->getData('fk_statut') > 0) {
//                $id_sav = (int) $this->db->getValue('bs_sav', 'id', '`id_propal` = ' . (int) $propal->id);
//
//                if (!(int) $id_sav) {
//                    $errors[] = 'ID du SAV absent - ' . $this->db->db->lasterror();
//                } else {
//                    $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
//                    if ($reservation->find(array(
//                                'type'               => BR_Reservation::BR_RESERVATION_SAV,
//                                'id_sav_propal_line' => (int) $this->id
//                            ))) {
//                        $reservation->updateField('id_equipment', (int) $this->getData('id_equipment'));
//                    } else {
//                        $errors[] = 'Réservation non trouvée';
//                    }
//                }
//            }
//        }
//        if (count($errors)) {
//            return array(BimpTools::getMsgFromArray($errors, 'Des erreurs sont survenues lors de la mise à jour de la réservation correspondante'));
//        }
//
//        return array();
//    }
    // overrides: 

    public function attributeEquipment($id_equipment, $pu_ht = null, $tva_tx = null, $id_fourn_price = null, $id_equipment_line = 0)
    {
        $current_id_equipment = 0;
        $equipment_line = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLineEquipment');

        if ((int) $id_equipment_line) {
            $current_id_equipment = (int) $equipment_line->getSavedData('id_equipment', $id_equipment_line);
        }

        $errors = parent::attributeEquipment($id_equipment, $pu_ht, $tva_tx, $id_fourn_price, $id_equipment_line);
        if (count($errors)) {
            return $errors;
        }

        $propal = $this->getParentInstance();

        if (BimpObject::objectLoaded($propal)) {
            $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
            $return_first = (!(int) $current_id_equipment ? true : false);
            $id_sav = (int) $this->db->getValue('bs_sav', 'id', '`id_propal` = ' . (int) $propal->id);
            if ($reservation->find(array(
                        'type'               => BR_Reservation::BR_RESERVATION_SAV,
                        'id_sav'             => $id_sav,
                        'id_sav_propal_line' => (int) $this->id,
                        'id_equipment'       => $current_id_equipment
                            ), $return_first)) {
                $reservation->updateField('id_equipment', $id_equipment);
            }
        }

        return $errors;
    }

    public function isEquipmentAvailable(Equipment $equipment)
    {
        if (!BimpObject::objectLoaded($equipment)) {
            return array('Equipement invalide');
        }

        $propal = $this->getParentInstance();

        if (!BimpObject::objectLoaded($propal)) {
            return array('ID du devis Absent');
        }

        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');

        $id_reservation = 0;
        if ($reservation->find(array(
                    'type'               => BR_Reservation::BR_RESERVATION_SAV,
                    'id_sav_propal_line' => (int) $this->id,
                ))) {
            $id_reservation = $reservation->id;
        }

        $id_entrepot = (int) $propal->getData('entrepot');
        $errors = array();
        $equipment->isAvailable($id_entrepot, $errors, array(
            'id_reservation' => (int) $id_reservation
        ));

        return $errors;
    }

    public function getValueByProduct($field)
    {
        if ($this->getData('linked_object_name') === 'sav_apple_part') {
            switch ($field) {
                case 'tva_tx':
                    return 20;

                case 'pu_ht':
                    if (is_null($this->pu_ht) || !(float) $this->pu_ht) {
                        $part = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_ApplePart', (int) $this->getData('linked_id_object'));
                        if ($part->isLoaded()) {
                            return $part->convertPrix((float) $this->pa_ht, $part->getData('part_number'), $part->getData('label'));
                        }

                        return 0;
                    }

                    return (float) $this->pu_ht;
            }
        }

        return parent::getValueByProduct($field);
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if ($field === 'qty') {
            if (!(int) $this->getData('editable')) {
                return 0;
            }
            return 1;
        }

        return (int) parent::isFieldEditable($field, $force_edit);
    }

    public function validate()
    {
        $propal = $this->getParentInstance();

        if (!BimpObject::objectLoaded($propal)) {
            return array('ID du devis Absent');
        }

        $sav = $propal->getSav();

        if (!BimpObject::objectLoaded($sav)) {
            return array('ID du SAV invalide');
        }

        $errors = parent::validate();

        if ((int) $this->getData('type') !== self::LINE_TEXT) {
            if (!(int) $this->getData('out_of_warranty')) {
                $this->set('remisable', 0);
            }
//            if ((int) $this->getData('out_of_warranty')) {
//                if (!(float) $this->pu_ht) {
//                    $this->pu_ht = $this->getValueByProduct('pu_ht');
//                }
//            } else {
//                $this->pu_ht = 0;
//            }
//            if (is_null($this->remise)) {
//                $remise = 0;
//
//                if ((int) $this->id_product) {
//                    $product = $this->getProduct();
//                    if (BimpObject::objectLoaded($product)) {
//                        $remise .= (float) $product->getData('remise');
//                    }
//                }
//                $client = $sav->getChildObject('client');
//                // todo: remanier la remise client
//                if (BimpObject::objectLoaded($client) && isset($client->dol_object->remise_percent)) {
//                    $remise += (float) $client->dol_object->remise_percent;
//                }
//
//                $this->remise = $remise;
//            }
        } else {
            $this->set('remisable', 0);
            $this->set('out_of_warranty', 1);
        }

        return $errors;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $sav_errors = $this->updateSav();

            if (count($sav_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($sav_errors, 'Des erreurs sont survenues lors de la mise à jour du SAV');
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {

            $sav_errors = $this->updateSav();

            if (count($sav_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($sav_errors, 'des erreurs sont survenues lors de la mise à jour du SAV');
            }
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $sav_errors = $this->updateSav();

        if (count($sav_errors)) {
            $warnings[] = BimpTools::getMsgFromArray($sav_errors, 'Des erreurs sont survenues lors de la mise à jour du SAV');
        }

        $errors = parent::delete($warnings, $force_delete);

        return $errors;
    }
}
