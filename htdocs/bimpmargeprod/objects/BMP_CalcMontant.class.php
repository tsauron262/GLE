<?php

class BMP_CalcMontant extends BimpObject
{

    public function getTypes_montantsArray()
    {
        $montants = array();
        $instance = BimpObject::getInstance($this->module, 'BMP_TypeMontant');
        $types_montants = $instance->getList();
        foreach ($types_montants as $tm) {
            $montants[$tm['id']] = $tm['name'] . ' (' . BMP_TypeMontant::$types[(int) $tm['type']]['label'] . ')';
        }

        return $montants;
    }

    public function getTotaux_interArray()
    {
        $totaux = array();
        $instance = BimpObject::getInstance($this->module, 'BMP_TotalInter');
        foreach ($instance->getList() as $item) {
            $totaux[$item['id']] = $item['name'];
        }

        return $totaux;
    }

    public function displaySource()
    {
        if (isset($this->id) && $this->id) {
            $source_type = $this->getData('type_source');
            switch ($source_type) {
                case 1:
                    $tm = $this->getChildObject('type_montant_src');
                    if (!is_null($tm) && isset($tm->id) && $tm->id) {
                        return $tm->getData('name') . ' (' . BMP_TypeMontant::$types[(int) $tm->getData('type')]['label'] . ')';
                    }
                    break;

                case 2:
                    $ti = $this->getChildObject('total_src');
                    if (!is_null($ti) && isset($ti->id) && $ti->id) {
                        return $ti->getData('name');
                    }
                    break;

                case 3:
                    return BimpTools::displayMoneyValue($this->getData('source_amount'), 'EUR');
            }
        }

        return BimpRender::renderAlerts('Aucun');
    }

    public function displayTarget()
    {
        if (isset($this->id) && $this->id) {
            $target = $this->getChildObject('type_montant_tgt');
            if ($target->isLoaded()) {
                return $target->getData('name') . ' (' . BMP_TypeMontant::$types[(int) $target->getData('type')]['label'] . ')';
            }
            return '';
        }

        return BimpRender::renderAlerts('Aucun');
    }

    public function checkConflicts()
    {
        $errors = array();

        $type_source = $this->getData('type_source');
        if (!is_null($type_source) && ($type_source === 2)) {
            $ti = $this->getChildObject('total_src');
            $target = $this->getChildObject('type_montant_tgt');

            if (!is_null($ti) && $ti->isLoaded() &&
                    !is_null($target) && $target->isLoaded()) {
                $asso = new BimpAssociation($ti, 'types_montants');
                $montants = $asso->getAssociatesList();
                if (in_array($target->id, $montants)) {
                    $errors[] = 'Vous ne pouvez pas choisir le montant cible "' . $target->getData('name') . '" car celui-ci est inclus dans le total intermédiaire source sélectionné';
                }
            }
        }
        return $errors;
    }

//    public function getEventPercentInput($id_event)
//    {
//        if (!isset($this->id) || !$this->id) {
//            return '';
//        }
//        $value = $this->db->getValue('bmp_event_calc_auto', 'percent', '`id_event` = ' . (int) $id_event . ' AND `id_calc_auto` = ' . (int) $this->id);
//
//        if (is_null($value)) {
//            $value = '';
//        }
//        $placeholder = $this->getData('percent');
//
//        $html .= '<div class="editInputContainer" data-field_name="event_' . $id_event . '_percent">';
//        $html .= '<input type="hidden" name="event_' . $id_event . '_percent_initial_value" value="' . $value . '"/>';
//        $html .= BimpInput::renderInput('text', 'event_' . $id_event . '_percent', $value, array(
//                    'addon_right' => '<i class="fa fa-percent"></i>',
//                    'placeholder' => $placeholder,
//                    'data'        => array(
//                        'data_type' => 'number',
//                        'decimals'  => 2,
//                        'min'       => 0,
//                        'max'       => 100,
//                        'unsigned'  => 0
//                    )
//        ));
//        $html .= '</div>';
//        return $html;
//    }
//    public function setEventPercent($id_event, $value)
//    {
//        $errors = array();
//
//        if (isset($this->id) && $this->id) {
//            if (is_null($id_event) || !$id_event) {
//                $errors[] = 'ID de l\'événement absent';
//            } else {
//                if ($this->db->delete('bmp_event_calc_auto', '`id_calc_auto` = ' . (int) $this->id . ' AND `id_event` = ' . (int) $id_event) <= 0) {
//                    $errors[] = 'Echec de la suppression du pourcentage';
//                }
//                if (($value !== '') && ((float) $value !== (float) $this->getData('percent'))) {
//                    if (!$this->db->insert('bmp_event_calc_auto', array(
//                                'id_event'     => (int) $id_event,
//                                'id_calc_auto' => (int) $this->id,
//                                'percent'      => (float) $value
//                            ))) {
//                        $errors[] = 'Echec de l\'enregistrement du pourcentage - ' . $this->db->db->error();
//                    }
//                }
//            }
//            if (!count($errors)) {
//                $event = BimpObject::getInstance($this->module, 'BMP_Event');
//                if ($event->fetch($id_event)) {
//                    $id_target_montant = (int) $this->getData('id_target');
//                    $event->calcMontant($id_target_montant);
//                }
//            }
//        } else {
//            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
//        }
//
//        return $errors;
//    }

    public function getSourceAmount($id_event, $id_coprod = 0)
    {
        switch ((int) $this->getData('type_source')) {
            case 1:
                $id_type_montant = (int) $this->getData('id_montant_source');
                if ($id_type_montant) {
                    $montant = BimpObject::getInstance($this->module, 'BMP_EventMontant');
                    if ($montant->find(array(
                                'id_event'   => (int) $id_event,
                                'id_montant' => (int) $id_type_montant,
                                'id_coprod'  => $id_coprod
                            ))) {
                        return $montant->getData('amount');
                    }
                }
                break;

            case 2:
                $id_total = $this->getData('id_total_source');
                if ($id_total) {
                    $total = BimpObject::getInstance($this->module, 'BMP_TotalInter');
                    if ($total->fetch($id_total)) {
                        return $total->getEventTotal($id_event, $id_coprod);
                    }
                }
                break;

            case 3:
                return (float) $this->getData('source_amount');
        }

        return 0;
    }

    // Overrides: 

    public function validate()
    {
        $errors = parent::validate();
        if (!count($errors)) {
            $errors = $this->checkConflicts();
        }

        return $errors;
    }
}
