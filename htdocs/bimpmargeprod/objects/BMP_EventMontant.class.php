<?php

class BMP_EventMontant extends BimpObject
{

    public static $status = array(
        1 => array('label' => 'A confirmer', 'classes' => array('warning')),
        2 => array('label' => 'Confirmé', 'classes' => array('success')),
        3 => array('label' => 'Optionnel', 'classes' => array('info'))
    );

    public function isEditable()
    {
        $typeMontant = $this->getChildObject('type_montant');
        if (is_null($typeMontant)) {
            return false;
        }
        $editable = $typeMontant->getData('editable');
        if (is_null($editable)) {
            return false;
        }
        return (int) $editable;
    }

    public function isRequired()
    {
        $typeMontant = $this->getChildObject('type_montant');
        if (is_null($typeMontant)) {
            return false;
        }
        $required = (bool) $typeMontant->getData('required');
        if (is_null($required)) {
            return false;
        }
        return $required;
    }

    public function isDeletable()
    {
        return (!$this->isRequired() && $this->isEditable());
    }

    public function hasDetails()
    {
        $typeMontant = $this->getChildObject('type_montant');
        if (is_null($typeMontant)) {
            return 0;
        }
        $has_details = $typeMontant->getData('has_details');
        if (is_null($has_details)) {
            return 0;
        }
        return (int) $has_details;
    }

    public function getCategoriesArray()
    {
        $instance = BimpObject::getInstance('bimpmargeprod', 'BMP_CategorieMontant');
        $rows = $instance->getList();
        $categories = array();

        $current_montants = array();
        $type = (int) $this->getData('type');
        if (!is_null($type)) {
            $event = $this->getParentInstance();
            if (!is_null($event) && isset($event->id) && $event->id) {
                switch ($type) {
                    case 1:
                        $children_name = 'frais';
                        break;

                    case 2:
                        $children_name = 'recettes';
                        break;
                }

                foreach ($event->getChildrenObjects($children_name) as $montant) {
                    $id_type_montant = (int) $montant->getData('id_montant');
                    if (!is_null($id_type_montant) && $id_type_montant) {
                        $current_montants[] = $id_type_montant;
                    }
                }
            }
        }

        $type_instance = BimpObject::getInstance('bimpmargeprod', 'BMP_TypeMontant');

        foreach ($rows as $r) {
            if (!is_null($type)) {
                $typesMontants = $type_instance->getList(array(
                    'id_category' => (int) $r['id'],
                    'type'        => (int) $type
                        ), NULL, null, 'id', 'asc', 'array', array('id'));
                foreach ($typesMontants as $idx => $typeMontant) {
                    if (in_array((int) $typeMontant['id'], $current_montants)) {
                        unset($typesMontants[$idx]);
                    }
                }
                if (!count($typesMontants)) {
                    continue;
                }
            }
            $categories[$r['id']] = $r['name'];
        }

        if (count($categories)) {
            $categories[0] = '';
            ksort($categories);
        }

        return $categories;
    }

    public function getTypes_montantsArray()
    {
        $id_category = $this->getData('id_category_montant');
        $type = $this->getData('type');
        if (is_null($id_category) || is_null($type)) {
            return array();
        }
        $event = $this->getParentInstance();
        switch ($type) {
            case 1:
                $children_name = 'frais';
                break;

            case 2:
                $children_name = 'recettes';
                break;
        }
        $current_montants = array();
        foreach ($event->getChildrenObjects($children_name) as $montant) {
            $id_type_montant = (int) $montant->getData('id_montant');
            if (!is_null($id_type_montant) && $id_type_montant) {
                $current_montants[] = $id_type_montant;
            }
        }

        $instance = BimpObject::getInstance('bimpmargeprod', 'BMP_TypeMontant');
        $rows = $instance->getList(array(
            'type'        => $type,
            'id_category' => (int) $id_category
        ));
        $types = array();
        foreach ($rows as $r) {
            if (!in_array((int) $r['id'], $current_montants)) {
                $types[$r['id']] = $r['name'];
            }
        }
        return $types;
    }

    public function getCoProdsCols()
    {
        $id_event = $this->getData('id_event');
        if (is_null($id_event)) {
            return array();
        }

        $instance = BimpObject::getInstance('bimpmargeprod', 'BMP_EventCoProd');
        $coprods = $instance->getList(array(
            'id_event' => (int) $id_event
        ));

        $cols = array();
        foreach ($coprods as $cp) {
            $instance->reset();
            if ($instance->fetch((int) $cp['id'])) {
                $soc = $instance->getChildObject('societe');
                if (!is_null($soc) && isset($soc->id) && $soc->id) {
                    $cols['coprod_' . $cp['id']] = array(
                        'label' => 'Part ' . $soc->nom,
                        'value' => array(
                            'callback' => array(
                                'method' => 'getCoProdPartInput',
                                'params' => array(
                                    'id_coprod' => (int) $cp['id']
                                )
                            )
                        )
                    );
                }
            }
        }

        return $cols;
    }

    public function getCoProdSavedPart($id_coprod)
    {
        if (isset($this->id) && $this->id) {
            $part = $this->db->getValue('bmp_event_coprod_part', 'part', '`id_event_montant` = ' . (int) $this->id . ' AND `id_coprod` = ' . (int) $id_coprod);
            if (!is_null($part)) {
                return $part;
            }
        }
        return null;
    }

    public function getCoProdDefaultPart($id_coprod)
    {
        $event = $this->getParentInstance();
        $id_cat = $this->getData('id_category_montant');
        if (!is_null($id_cat)) {
            $where = '`id_event` = ' . (int) $event->id . ' AND `id_event_coprod` = ' . (int) $id_coprod;
            $where .= ' AND `id_category_montant` = ' . (int) $id_cat;
            $part = $this->db->getValue('bmp_event_coprod_def_part', 'part', $where);
            if (!is_null($part)) {
                return $part;
            }
        }

        $coprod = $event->getChildObject('coprods', $id_coprod);
        if (!is_null($coprod) && isset($coprod->id) && $coprod->id) {
            return $coprod->getdata('default_part');
        }
        return null;
    }

    public function getCoProdPart($id_coprod)
    {
        $part = $this->getCoProdSavedPart($id_coprod);
        if (!is_null($part)) {
            return $part;
        }

        return $this->getCoProdDefaultPart($id_coprod);
    }

    public function getCoProdPartInput($id_coprod)
    {
        $value = $this->getCoProdSavedPart($id_coprod);
        if (is_null($value) || !$value) {
            $value = '';
        }
        $placeholder = $this->getCoProdDefaultPart($id_coprod);

        $html = '<div class="editInputContainer coProdPart" data-id_coprod="' . $id_coprod . '" data-field_name="coprod_' . $id_coprod . '_part">';
        $html .= '<input type="hidden" name="coprod_' . $id_coprod . '_part_initial_value" value="' . $value . '"/>';
        $html .= BimpInput::renderInput('text', 'coprod_' . $id_coprod . '_part', $value, array(
                    'addon_right' => '<i class="fa fa-percent"></i>',
                    'placeholder' => $placeholder,
                    'data'        => array(
                        'data_type' => 'number',
                        'decimals'  => 2,
                        'min'       => 0,
                        'max'       => 100,
                        'unsigned'  => 0
                    )
        ));
        $html .= '</div>';
        return $html;
    }

    public function getDefaultListExtraButtons()
    {
        if ($this->hasDetails()) {
            return array(
                array(
                    'label'   => 'Détail',
                    'icon'    => 'file-text-o',
                    'onclick' => 'loadModalView(\'' . $this->module . '\', \'' . $this->object_name . '\', ' . $this->id . ',\'' . 'details_list' . '\', $(this));'
                )
            );
        }

        return array();
    }

    public function getTva()
    {
        if (isset($this->id) && $this->id) {
            $type = $this->getChildObject('type_montant');
            $id_taxe = $type->getData('id_taxe');
            if (!is_null($id_taxe)) {
                $taxes = BimpTools::getTaxes();
                if (array_key_exists((int) $id_taxe, $taxes)) {
                    return $taxes[(int) $id_taxe] . ' %';
                }
            }
        }

        return '<span class="warning">Non défini</span>';
    }

    public function getAllCategoriesArray()
    {
        $instance = BimpObject::getInstance('bimpmargeprod', 'BMP_CategorieMontant');
        $rows = $instance->getList();
        $categories = array(
            '' => ''
        );
        foreach ($rows as $r) {
            $categories[$r['id']] = $r['name'];
        }
        return $categories;
    }

    public function getAllTypesArray()
    {
        $type = BimpObject::getInstance('bimpmargeprod', 'BMP_TypeMontant');
        return $type->getAllTypes();
    }

    public function getDetailsName()
    {
        $montant = $this->getChildObject('type_montant');
        return 'Détails du montant "' . $montant->getData('name') . '"';
    }

    public function update()
    {
        $errors = parent::update();

        if (!count($errors)) {
            $id_event = $this->getData('id_event');
            if (is_null($id_event)) {
                return array();
            }

            $instance = BimpObject::getInstance('bimpmargeprod', 'BMP_EventCoProd');
            $coprods = $instance->getList(array(
                'id_event' => (int) $id_event
            ));

            $event = $this->getParentInstance();

            foreach ($coprods as $cp) {
                if (BimpTools::isSubmit('coprod_' . $cp['id'] . '_part')) {
                    $value = BimpTools::getValue('coprod_' . $cp['id'] . '_part');
                    if ($value === '' || BimpTools::checkValueByType('float', $value)) {
                        $this->db->delete('bmp_event_coprod_part', '`id_event_montant` = ' . (int) $this->id . ' AND `id_coprod` = ' . (int) $cp['id']);
                        $defVal = $this->getCoProdDefaultPart($cp['id']);
                        if (($value !== '') && (float) $value !== (float) $defVal) {

                            if ($this->db->insert('bmp_event_coprod_part', array(
                                        'id_event_montant' => (int) $this->id,
                                        'id_coprod'        => (int) $cp['id'],
                                        'part'             => (float) $value
                                    )) <= 0) {
                                $coprod = $event->getChildObject('coprods', (int) $cp['id']);
                                $societe = $coprod->getChildObject('societe');
                                $errors[] = 'Echec de l\'enregistrement de la part du co-producteur "' . $societe->nom . '"';
                            }
                        }
                    } else {
                        $coprod = $event->getChildObject('coprods', (int) $cp['id']);
                        $societe = $coprod->getChildObject('societe');
                        $errors[] = 'Montant de la part du coproducteur "' . $societe->nom . '" invalide (Doit être un nombre décimal)';
                    }
                }
            }
        }

        return $errors;
    }

    public function fetch($id)
    {
        if (parent::fetch($id)) {
            $typeMontant = $this->getChildObject('type_montant');
            $type = (int) $typeMontant->getData('type');
            if ($type !== (int) $this->getData('type')) {
                $this->set('type', $type);
                $this->update();
            }
            return true;
        }
        return false;
    }

    public function onChildSave($object_name)
    {
        if ($object_name === 'BMP_EventMontantDetail') {
            $amount = 0;
            $children = $this->getChildrenObjects('details');
            foreach ($children as $child) {
                if (!is_null($child) && is_a($child, 'BMP_EventMontantDetail')) {
                    $amount += $child->getTotal();
                }
            }
            $amount = (float) round($amount, 2);
            $current_amount = (float) $this->getData('amount');
            if ($current_amount !== $amount) {
                $this->set('amount', $amount);
                $this->update();
            }
        }
    }
}
