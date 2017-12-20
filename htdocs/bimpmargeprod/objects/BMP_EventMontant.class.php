<?php

class BMP_EventMontant extends BimpObject
{

    public function getCategoriesArray()
    {
        $instance = BimpObject::getInstance('bimpmargeprod', 'BMP_CategorieMontant');
        $rows = $instance->getList();
        $categories = array();

        foreach ($rows as $r) {
            $categories[$r['id']] = $r['name'];
        }

        return $categories;
    }

    public function getTypes_montantsArray()
    {
        $id_category = $this->getData('id_category_montant');

        if (is_null($id_category)) {
            return array();
        }

        $instance = BimpObject::getInstance('bimpmargeprod', 'BMP_TypeMontant');
        $rows = $instance->getList(array(
            'type'        => 1,
            'id_category' => (int) $id_category
        ));
        $types = array();
        foreach ($rows as $r) {
            $types[$r['id']] = $r['name'];
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
        $html .= BimpInput::renderInput('text', 'coprod_' . $id_coprod . '_part', $value, array(
                    'addon_right' => '<i class="fa fa-percent"></i>',
                    'placeholder' => $placeholder
        ));
        $html .= '</div>';
        return $html;
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

    public function getAllTypesArray()
    {
        $type = BimpObject::getInstance('bimpmargeprod', 'BMP_TypeMontant');
        return $type->getAllTypes();
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
}
