<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/Bimp_PropalLine.class.php';

class BS_SavPropalLine extends Bimp_PropalLine
{

    public $equipment_required = true;

    // Getters: 

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

        $id_sav = (int) $this->db->getValue('bs_sav', 'id', '`id_propal` = ' . (int) $propal->id);

        if (!(int) $id_sav) {
            return array('ID du SAV absent - ' . $this->db->db->lasterror());
        }

        $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV', $id_sav);

        if (!BimpObject::objectLoaded($sav)) {
            return array('ID du SAV invalide');
        }

        $error = $sav->processPropalGarantie();

        if ($error) {
            return array($error);
        }

        return array();
    }

    // overrides: 

    public function isEquipmentAvailable()
    {
        // Todo...
        return array();
    }
    
    public function getValueByProduct($field)
    {
        if ($this->getData('linked_object_name') === 'sav_apple_part') {
            switch ($field) {
                case 'tva_tx':
                    return 20;

                case 'pu_ht':
                    $part = BimpObject::getInstance('bimpsupport', 'BS_ApplePart', (int) $this->getData('linked_id_object'));
                    if ($part->isLoaded()) {
                        return BS_ApplePart::convertPrix((float) $this->pa_ht, $part->getData('part_number'), $part->getData('label'));
                    }
                    return 0;
            }
        }

        return parent::getValueByProduct($field);
    }

    public function validate()
    {
        $propal = $this->getParentInstance();

        if (!BimpObject::objectLoaded($propal)) {
            return array('ID du devis Absent');
        }

        $id_sav = (int) $this->db->getValue('bs_sav', 'id', '`id_propal` = ' . (int) $propal->id);

        if (!(int) $id_sav) {
            return array('ID du SAV absent');
        }

        $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV', $id_sav);

        if (!BimpObject::objectLoaded($sav)) {
            return array('ID du SAV invalide');
        }

        $errors = parent::validate();

        if ((int) $this->getData('type') !== self::LINE_TEXT) {
//            if ((int) $this->getData('out_of_warranty')) {
//                if (!(float) $this->pu_ht) {
//                    $this->pu_ht = $this->getValueByProduct('pu_ht');
//                }
//            } else {
//                $this->pu_ht = 0;
//            }

            if (is_null($this->remise)) {
                $remise = 0;

                if ((int) $this->id_product) {
                    $product = $this->getProduct();
                    if (BimpObject::objectLoaded($product)) {
                        $remise .= (float) $product->getData('remise');
                    }
                }
                $client = $sav->getChildObject('client');
                if (BimpObject::objectLoaded($client) && isset($client->dol_object->remise_percent)) {
                    $remise += (float) $client->dol_object->remise_percent;
                }

                $this->remise = $remise;
            }
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

    public function delete($force_delete = false)
    {
        $sav_errors = $this->updateSav();

        if (count($sav_errors)) {
            $errors[] = BimpTools::getMsgFromArray($sav_errors, 'Des erreurs sont survenues lors de la mise à jour du SAV');
        }

        $errors = array_merge($errors, parent::delete($force_delete));

        return $errors;
    }
}
