<?php

require_once DOL_DOCUMENT_ROOT."/bimpmargeprod/objects/Abstract_margeprod.class.php";
class BMP_CalcMontant extends Abstract_margeprod
{

    // Getters : 

    public function canDelete()
    {
        global $user;
        
        if ($user->admin) {
            return 1;
        }
        
        return 0;
    }
    
    public function getTypes_montantsArray()
    {
        BimpObject::loadClass($this->module, 'BMP_TypeMontant');
        return BMP_TypeMontant::getTypesMontantsArray();
    }

    public function getTotaux_interArray()
    {
        return self::getBimpObjectFullListArray($this->module, 'BMP_TotalInter');
    }

    // Affichage: 

    public function displaySource()
    {
        if ($this->isLoaded()) {
            $source_type = $this->getData('type_source');
            switch ($source_type) {
                case 1:
                    $tm = $this->getChildObject('type_montant_src');
                    if (BimpObject::objectLoaded($tm)) {
                        return $tm->getData('name') . ' (' . BMP_TypeMontant::$types[(int) $tm->getData('type')]['label'] . ')';
                    }
                    break;

                case 2:
                    $ti = $this->getChildObject('total_src');
                    if (BimpObject::objectLoaded($ti)) {
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
        if ($this->isLoaded()) {
            $target = $this->getChildObject('type_montant_tgt');
            if (BimpObject::objectLoaded($target)) {
                return $target->getData('name') . ' (' . BMP_TypeMontant::$types[(int) $target->getData('type')]['label'] . ')';
            }
            return '';
        }

        return BimpRender::renderAlerts('Aucun');
    }

    // Traitements: 

    public function checkConflicts()
    {
        $errors = array();

        $type_source = $this->getData('type_source');
        if (!is_null($type_source) && ($type_source === 2)) {
            $ti = $this->getChildObject('total_src');
            $target = $this->getChildObject('type_montant_tgt');

            if (BimpObject::objectLoaded($ti) && BimpObject::objectLoaded($target)) {
                $asso = new BimpAssociation($ti, 'types_montants');
                $montants = $asso->getAssociatesList();
                if (in_array($target->id, $montants)) {
                    $errors[] = 'Vous ne pouvez pas choisir le montant cible "' . $target->getData('name') . '" car celui-ci est inclus dans le total intermédiaire source sélectionné';
                }
            }
        }
        return $errors;
    }

    public function rebuildTypesMontantsCache($id = null)
    {
        $errors = array();

        if (is_null($id) && $this->isLoaded()) {
            $id = $this->id;
        } else {
            return array();
        }

        if ($this->db->delete('bmp_calc_montant_type_montant', '`id_calc_montant` = ' . (int) $id) <= 0) {
            $errors[] = 'Echec de la reconstruction du cache (Echec suppression des types de montants déjà enregistrés)';
        } else {

            $type_src = $this->getData('type_source');

            if ($type_src === 1) {
                if ($this->db->insert('bmp_calc_montant_type_montant', array(
                            'id_calc_montant' => (int) $id,
                            'id_type_montant' => (int) $this->getData('id_montant_source')
                        )) <= 0) {
                    $errors[] = 'Reconstruction du cache: échec de l\'insertion du type de montant d\'id ' . $this->getData('id_montant_source');
                }
            } elseif ($type_src === 2) {

                $totalInter = BimpCache::getBimpObjectInstance($this->module, 'BMP_TotalInter', (int) $this->getData('id_total_source'));
                $list = $totalInter->getAllTypesMontantsList();

                foreach ($list as $id_type_montant) {
                    if ($this->db->insert('bmp_calc_montant_type_montant', array(
                                'id_calc_montant' => (int) $id,
                                'id_type_montant' => (int) $id_type_montant
                            )) <= 0) {
                        $errors[] = 'Reconstruction du cache: échec de l\'insertion du type de montant d\'id ' . $this->getData('id_montant_source');
                    }
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
                    $event = BimpCache::getBimpObjectInstance($this->module, 'BMP_Event', (int) $id_event);
                    if ($event->isLoaded()) {
                        return (float) $event->getMontantAmount($id_type_montant, $id_coprod);
                    }
                }
                break;

            case 2:
                $id_total = (int) $this->getData('id_total_source');
                if ($id_total) {
                    $total = BimpCache::getBimpObjectInstance($this->module, 'BMP_TotalInter', $id_total);
                    if ($total->isLoaded()) {
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

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if ($this->isLoaded()) {
            $cache_errors = $this->rebuildTypesMontantsCache();
            if (count($cache_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($cache_errors, 'Des erreurs sont survenues lors de la reconstruction du cache');
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_delete = false)
    {
        $errors = parent::update($warnings, $force_delete);

        if ($this->isLoaded()) {
            $cache_errors = $this->rebuildTypesMontantsCache();
            if (count($cache_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($cache_errors, 'Des erreurs sont survenues lors de la reconstruction du cache');
            }
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $errors = array();

        if ($this->isLoaded()) {
            $id = $this->id;
        } else {
            $id = null;
        }

        $errors = parent::delete($warnings, $force_delete);

        if (!is_null($id)) {
            if ($this->db->delete('bmp_calc_montant_type_montant', '`id_calc_montant` = ' . (int) $id) <= 0) {
                $warnings[] = 'Echec de la suppression du cache (Echec suppression des types de montants déjà enregistrés)';
            }
        }

        return $errors;
    }
}
