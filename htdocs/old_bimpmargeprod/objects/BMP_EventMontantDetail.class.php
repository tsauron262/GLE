<?php

class BMP_EventMontantDetail extends BimpObject
{

    public function isEditable($force_edit = false)
    {
        $montant = $this->getParentInstance();
        return $montant->isEventEditable();
    }

    public function getTotal()
    {
        $qty = (int) $this->getData('quantity');
        $price = (float) $this->getData('unit_price');

        return round(($qty * $price), 2);
    }

    public function displayTotal()
    {
        return BimpTools::displayMoneyValue($this->getTotal(), 'EUR');
    }

    public function getDetail_valuesArray()
    {
        $eventMontant = $this->getParentInstance();
        if (is_null($eventMontant) || !$eventMontant->isLoaded()) {
            return array();
        }

        $id_type_montant = $eventMontant->getData('id_montant');
        if (is_null($id_type_montant) || !$id_type_montant) {
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

    public function getGroupsArray()
    {
        $eventMontant = $this->getParentInstance();

        if (is_null($eventMontant) || !$eventMontant->isLoaded()) {
            return array();
        }

        $event = $eventMontant->getParentInstance();

        if (is_null($event) || !$event->isLoaded()) {
            return array();
        }

        return $event->getGroupsArray();
    }

    public function useGroups()
    {
        $fields = BimpTools::getValue('fields', array());
        if (isset($fields['id_montant_detail_value'])) {
            $id_detail_value = $fields['id_montant_detail_value'];
            if (!is_null($id_detail_value) && $id_detail_value) {
                $detailValue = BimpObject::getInstance($this->module, 'BMP_MontantDetailValue');
                if ($detailValue->fetch($id_detail_value)) {
                    return (int) $detailValue->getData('use_groupe_number');
                }
            }
        }
        return 0;
    }

    public function getAddFormName()
    {
        $eventMontant = $this->getParentInstance();
        if (!is_null($eventMontant) && $eventMontant->isLoaded()) {
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

            $detailValue = BimpObject::getInstance($this->module, 'BMP_MontantDetailValue');
            if (!$detailValue->fetch($id_value)) {
                return array('Valeur prédéfinie sélectionnée invalide');
            }

            $label = $detailValue->getData('label');

            $quantity = null;
            if ((int) $detailValue->getData('use_groupe_number')) {
                $id_group = BimpTools::getValue('id_group', 0);
                if ($id_group) {
                    $group = BimpObject::getInstance($this->module, 'BMP_EventGroup');
                    if ($group->fetch($id_group)) {
                        $label .= ' (Groupe: ' . $group->getData('name') . ')';
                        $quantity = (int) $group->getData('number');
                    }
                }
            }
            if (is_null($quantity)) {
                $quantity = BimpTools::getValue('quantity', 0);
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
