<?php

class ObjectLineEquipment extends BimpObject
{

    public function getListBtn()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            if ((int) $this->getData('id_equipment')) {
                $objectLine = $this->getParentInstance();
                if (BimpObject::objectLoaded($objectLine) && $objectLine->isActionAllowed('attributeEquipment')) {
                    $onclick = $objectLine->getJsActionOnclick('attributeEquipment', array(
                        'id_line_equipment' => (int) $this->id,
                        'id_equipment'      => (int) $this->getData('id_equipment'),
                        'pu_ht'             => (float) $this->getData('pu_ht'),
                        'tva_tx'            => (float) $this->getData('tva_tx'),
                        'id_fourn_price'    => (float) $this->getData('id_fourn_price'),
                        'pu_ht'             => (float) $this->getData('pu_ht'),
                            ), array(
                        'form_name' => 'equipment'
                    ));
                    $buttons[] = array(
                        'label'   => 'Editer',
                        'icon'    => 'fas_edit',
                        'onclick' => $onclick
                    );
                }
            }
        }

        return $buttons;
    }

    public function displayEquipment()
    {
        if ((int) $this->getData('id_equipment')) {
            return $this->displayData('id_equipment', 'nom_url');
        }

        return '<span class="warning">Non attribué</span>';
    }

    // Traitements: 

    public function setEquipment($id_equipment, $check_availability = true)
    {
        $errors = array();

        if ($this->isLoaded()) {
            $equipment = null;
            if ((int) $id_equipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                if (!$equipment->isLoaded()) {
                    $errors[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas';
                }
            }
            if (!count($errors)) {
                $current = (int) $this->getSavedData('id_equipment');
                if ($current) {
                    if ((int) $id_equipment && $current === $id_equipment) {
                        $errors[] = 'Cet équipement a déjà été attribué';
                    } else {
                        $this->db->update('be_equipment', array(
                            'available' => 1
                                ), '`id` = ' . (int) $current);
                    }
                }
                if (!count($errors) && !is_null($equipment)) {
                    if ($check_availability) {
                        $errors = $equipment->checkAvailability();
                    }
                }
                if (!count($errors)) {
                    $this->updateField('id_equipment', (int) $id_equipment);
                    if (!is_null($equipment))
                        $equipment->updateField('available', 0);
                }
            }
        } else {
            $errors[] = 'ID de la ligne d\'équipement absent';
        }

        return $errors;
    }

    // Overrides: 

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $id_equipment = (int) $this->getData('id_equipment');
        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors) && $id_equipment) {
            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
            if ($equipment->isLoaded()) {
                $equipment->updateField('available', 1);
            }
        }

        return $errors;
    }
}
