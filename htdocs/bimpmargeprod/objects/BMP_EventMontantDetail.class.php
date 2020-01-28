<?php

class BMP_EventMontantDetail extends BimpObject
{

    public function isEditable($force_edit = false)
    {
        $montant = $this->getParentInstance();
        if (BimpObject::objectLoaded($montant)) {
            return (int) $montant->isEventEditable();
        }

        return 1;
    }

    public function isCreatable($force_create = false)
    {
        return $this->isEditable();
    }

    public function isDeletable($force_delete = false)
    {
        return $this->isEditable($force_delete);
    }

    public function getTotal()
    {
        $qty = (int) $this->getData('quantity');
        $price = (float) $this->getData('unit_price');

        return round(($qty * $price), 2);
    }

    public function getDefaultQty()
    {
        $id_group = (int) BimpTools::getPostFieldValue('id_group', 0);
        if ($id_group) {
            $group = BimpCache::getBimpObjectInstance($this->module, 'BMP_EventGroup', $id_group);
            if ($group->isLoaded()) {
                return (int) $group->getData('number');
            }
        }

        return 1;
    }

    public function displayTotal()
    {
        return BimpTools::displayMoneyValue($this->getTotal(), 'EUR');
    }

    public function getDetail_valuesArray()
    {
        $eventMontant = $this->getParentInstance();
        if (!BimpObject::objectLoaded($eventMontant)) {
            return array();
        }

        $id_type_montant = (int) $eventMontant->getData('id_montant');
        if (!$id_type_montant) {
            return array();
        }

        $Detailvalues = BimpObject::getInstance($this->module, 'BMP_MontantDetailValue');
        $list = $Detailvalues->getList(array(
            'id_type_montant' => (int) $id_type_montant
        ));

        $values = array(
            0 => ''
        );

        foreach ($list as $item) {
            $values[(int) $item['id']] = $item['label'] . ' - ' . BimpTools::displayMoneyValue($item['unit_price'], 'EUR');
        }

        return $values;
    }

    public function getGroupsArray($include_empty = 1)
    {
        $eventMontant = $this->getParentInstance();

        if (BimpObject::objectLoaded($eventMontant)) {
            $event = $eventMontant->getParentInstance();
            if (BimpObject::objectLoaded($event)) {
                return $event->getGroupsArray($include_empty);
            }
        }
        return arrray();
    }

    public function useGroups()
    {
        $detailValue = BimpCache::getBimpObjectInstance($this->module, 'BMP_MontantDetailValue', (int) BimpTools::getPostFieldValue('id_montant_detail_value', 0));
        if ($detailValue->isLoaded()) {
            return (int) $detailValue->getData('use_groupe_number');
        }
        return 0;
    }

    public function getAddFormName()
    {
        $eventMontant = $this->getParentInstance();
        if (BimpObject::objectLoaded($eventMontant)) {
            $detailValue = BimpObject::getInstance($this->module, 'BMP_MontantDetailValue');
            if ($detailValue->getListCount(array(
                        'id_type_montant' => (int) $eventMontant->getData('id_montant')
                    )) > 0) {
                return 'predef';
            }
        }

        return '';
    }

    public function validatePost()
    {
        if (BimpTools::isSubmit('id_montant_detail_value')) {
            $id_value = (int) BimpTools::getValue('id_montant_detail_value');
            if (is_null($id_value) || !$id_value) {
                return array('Aucune valeur prédéfinie sélectionnée');
            }

            $id_event_montant = BimpTools::getValue('id_event_montant');

            if (is_null($id_event_montant) || !$id_event_montant) {
                return array('ID du montant correspondant absent');
            }

            $this->setIdParent($id_event_montant);

            $detailValue = BimpCache::getBimpObjectInstance($this->module, 'BMP_MontantDetailValue', (int) $id_value);
            if (!$detailValue->isLoaded()) {
                return array('Valeur prédéfinie sélectionnée invalide');
            }

            $label = $detailValue->getData('label');

            $quantity = BimpTools::getValue('quantity', null);
            if ((int) $detailValue->getData('use_groupe_number')) {
                $id_group = BimpTools::getValue('id_group', 0);
                if ($id_group) {
                    $group = BimpCache::getBimpObjectInstance($this->module, 'BMP_EventGroup', (int) $id_group);
                    if ($group->isLoaded()) {
                        $label .= ' (Groupe: ' . $group->getData('name') . ')';
                        if (is_null($quantity)) {
                            $quantity = (int) $group->getData('number');
                        }
                    }
                }
            }

            $this->set('label', $label);
            $this->set('unit_price', $detailValue->getData('unit_price'));
            $this->set('quantity', $quantity);
            return array();
        } else {
            return parent::validatePost();
        }
    }
}
