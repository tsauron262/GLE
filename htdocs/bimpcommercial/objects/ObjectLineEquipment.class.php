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
                        'id_equipment'      => (int) $this->getData('id_equipment')
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
    
    public function getEquipmentInfoCreate(){
        $parent = $this->getParentInstance();
        $gr_parent = $parent->getParentInstance();
        $result = array();

        if (BimpObject::objectLoaded($gr_parent)) {
            $client = $gr_parent->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                $result['fields'] = array(
                    'id_product' => (int) $parent->id_product
                );
                $result['objects'] = array(
                    'places' => array(
                        'fields' => array(
                            'type'      => 1,
                            'id_client' => (int) $client->id
                        )
                    )
                );
            }
        }
        
        return $result;
    }

    public function displayEquipment()
    {
        if ((int) $this->getData('id_equipment')) {
            return $this->displayData('id_equipment', 'nom_url');
        }

        return '<span class="warning">Non attribué</span>';
    }

    public function displayPa()
    {
        if ($this->isLoaded()) {
            $equipment = $this->getChildObject('equipment');
            if (BimpObject::objectLoaded($equipment)) {
                return $equipment->displayData('prix_achat');
            }
        }
        
        return '';
    }
    
    public function displayPaTvatx()
    {
        if ($this->isLoaded()) {
            $equipment = $this->getChildObject('equipment');
            if (BimpObject::objectLoaded($equipment)) {
                return $equipment->displayData('achat_tva_tx');
            }
        }
        
        return '';
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
                    }
                }
                if (!count($errors) && BimpObject::objectLoaded($equipment)) {
                    if ($check_availability) {
                        $id_entrepot = 0;

                        $line = $this->getParentInstance();
                        if (BimpObject::objectLoaded($line)) {
                            if ($line::$equipment_required_in_entrepot) {
                                $lineParent = $line->getParentInstance();
                                if (BimpObject::objectLoaded($lineParent)) {
                                    $id_entrepot = (int) $lineParent->getData('entrepot');
                                    if (!$id_entrepot) {
                                        $errors[] = 'Aucun entrepôt défini pour ' . $lineParent->getLabel('the') . ' ' . $lineParent->getNomUrl(0, 1, 1, 'full');
                                    }
                                } else {
                                    $errors[] = 'Objet parent absent pour ' . $line->getLabel('the') . ' #' . $line->id;
                                }
                            }
                        }

                        if (!count($errors)) {
                            $equipment->isAvailable($id_entrepot, $errors);
                        }
                    }
                }
                if (!count($errors)) {
                    $this->updateField('id_equipment', (int) $id_equipment);
                }
            }
        } else {
            $errors[] = 'ID de la ligne d\'équipement absent';
        }

        return $errors;
    }

    public function removeEquipment()
    {
        $errors = array();

        if ($this->isLoaded() && (int) $this->getData('id_equipment')) {
            $errors = $this->updateField('id_equipment', 0);
        }

        return $errors;
    }
}
