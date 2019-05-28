<?php

class BMP_EventMontant extends BimpObject
{

    protected $cp_new_parts = array();
    public static $status = array(
        1 => array('label' => 'A confirmer', 'icon' => 'exclamation-circle', 'classes' => array('warning')),
        2 => array('label' => 'Confirmé', 'icon' => 'check-circle', 'classes' => array('success')),
        3 => array('label' => 'Optionnel', 'icon' => 'question-circle', 'classes' => array('info'))
    );

    public function isEditable($force_edit = false)
    {
        if (!(int) $this->isEventEditable()) {
            return 0;
        }

        $id_coprod = (int) $this->getData('id_coprod');

        if ($id_coprod > 0) {
            return 1;
        }

        $typeMontant = $this->getChildObject('type_montant');
        if (is_null($typeMontant)) {
            return 0;
        }
        $editable = (int) $typeMontant->getData('editable');
        if (is_null($editable)) {
            return 0;
        }

        if ($editable) {
            $event = $this->getParentInstance();
            $status = (int) $event->getData('status');
            if ($status === 3) {
                return 0;
            } elseif ($status === 1) {
                $id_type_montant = (int) $this->getData('id_montant');
                if (($id_type_montant === BMP_Event::$id_bar20_type_montant) ||
                        ($id_type_montant === BMP_Event::$id_bar55_type_montant)) {
                    return 0;
                }
            }
        }

        return $editable;
    }

    public function isEventEditable()
    {
        $event = $this->getParentInstance();
        if (!is_null($event) && $event->isLoaded()) {
            return (int) $event->isEditable();
        }

        return 0;
    }

    public function getCreateForm()
    {
        if ($this->isEventEditable()) {
            return 'default';
        }

        return '';
    }

    public function isRequired()
    {
        $id_coprod = (int) $this->getData('id_coprod');

        if ($id_coprod > 0) {
            return 0;
        }

        $typeMontant = $this->getChildObject('type_montant');
        if (is_null($typeMontant)) {
            return 0;
        }
        $required = (bool) $typeMontant->getData('required');
        if (is_null($required)) {
            return 0;
        }
        return (int) $required;
    }

    public function isDeletable($force_delete = false)
    {
        if (!(int) $this->isEventEditable()) {
            return 0;
        }

        $id_coprod = (int) $this->getData('id_coprod');

        if ($id_coprod > 0) {
            return 1;
        }

        return (int) $this->isEditable($force_delete);
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
        $type = $this->getData('type');
        $id_coprod = $this->getData('id_coprod');

        if (is_null($id_coprod)) {
            $id_coprod = 0;
        }


        if (!is_null($type)) {
            $event = $this->getParentInstance();

            if (!is_null($event) && $event->isLoaded()) {
                $eventMontant = BimpObject::getInstance($this->module, $this->object_name);

                foreach ($eventMontant->getList(array(
                    'id_event'  => (int) $event->id,
                    'type'      => (int) $type,
                    'id_coprod' => (int) $id_coprod
                )) as $montant) {
                    $current_montants[] = $montant['id_montant'];
                }
            }
        }

        $type_instance = BimpObject::getInstance('bimpmargeprod', 'BMP_TypeMontant');

        foreach ($rows as $r) {
            if (!is_null($type)) {
                $typesMontants = $type_instance->getList(array(
                    'id_category' => (int) $r['id'],
                    'type'        => (int) $type
                ));
                foreach ($typesMontants as $idx => $typeMontant) {
                    if (in_array((int) $typeMontant['id'], $current_montants) ||
                            (($id_coprod > 0) && (!(int) $typeMontant['coprod']))) {
                        unset($typesMontants[$idx]);
                    }
                }
                if (!count($typesMontants)) {
                    continue;
                }
            }
            $color = $instance->getSavedData('color', $r['id']);
            $categories[$r['id']] = '<span style="font-weight: bold; color: #' . $color . '">' . $r['name'] . '</span>';
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
        $id_coprod = $this->getData('id_coprod');
        if (is_null($id_category) || is_null($type) || is_null($id_coprod)) {
            return array();
        }

        $event = $this->getParentInstance();

        if (!is_null($event) && $event->isLoaded())
            $current_montants = array();
        $eventMontant = BimpObject::getInstance($this->module, $this->object_name);

        foreach ($eventMontant->getList(array(
            'id_event'  => (int) $event->id,
            'type'      => (int) $type,
            'id_coprod' => (int) $id_coprod
        )) as $montant) {
            $id_type_montant = (int) $montant['id_montant'];
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
            if (($id_coprod > 0) && !(int) $r['coprod']) {
                continue;
            }

            if (!in_array((int) $r['id'], $current_montants)) {
                $types[$r['id']] = $r['name'];
            }
        }
        return $types;
    }

    public function getCoprodsArray()
    {
        $coprods = array(
            0 => ''
        );

        $event = $this->getParentInstance();
        if ($event->isLoaded()) {
            foreach ($event->getCoProds() as $id_cp => $name) {
                $coprods[$id_cp] = $name;
            }
        }

        return $coprods;
    }

    public function getCoProdsCols($edit = true)
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
                    if ($edit) {
                        $method = 'getCoProdPartInput';
                    } else {
                        $method = 'displayCoProdPartAmount';
                    }
                    $cols['coprod_' . $cp['id']] = array(
                        'label' => 'Part ' . $soc->nom,
                        'value' => array(
                            'callback' => array(
                                'method' => $method,
                                'params' => array(
                                    'id_coprod' => (int) $cp['id']
                                )
                            )
                        )
                    );
                }
            }
        }

        if (count($coprods) && !$edit) {
            $cols['coprod_0'] = array(
                'label' => 'Part restante',
                'value' => array(
                    'callback' => array(
                        'method' => $method,
                        'params' => array(
                            'id_coprod' => 0
                        )
                    )
                )
            );
        }

        return $cols;
    }

    public function getCoProdSavedPart($id_coprod)
    {
        if ($this->isLoaded()) {
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
        if ($id_coprod === 0) {
            $event = $this->getParentInstance();
            if (!is_null($event) && $event->isLoaded()) {
                $coprods = $event->getCoProds();
                $part = 100;
                foreach ($coprods as $id_cp => $cp_name) {
                    $part -= $this->getCoProdPart($id_cp);
                }
                if ($part <= 0) {
                    $part = 0;
                }
                return $part;
            }
            return 0;
        }
        $part = $this->getCoProdSavedPart($id_coprod);
        if (!is_null($part)) {
            return $part;
        }

        return $this->getCoProdDefaultPart($id_coprod);
    }

    public function getCoProdPartAmount($id_coprod)
    {
        $part = $this->getCoProdPart($id_coprod);
        if (is_null($part)) {
            $part = 0;
        }

        $amount = $this->getData('amount');
        if (is_null($amount)) {
            $amount = 0;
        }

        return $amount * ($part / 100);
    }

    public function getCoProdPartInput($id_coprod)
    {
        $value = $this->getCoProdSavedPart($id_coprod);
        $placeholder = $this->getCoProdDefaultPart($id_coprod);
        if ($this->isEventEditable()) {
            if (is_null($value)) {
                $value = '';
            }

            $html = '<div class="inputContainer coProdPart" data-id_coprod="' . $id_coprod . '" data-initial_value="' . $value . '" data-field_name="coprod_' . $id_coprod . '_part">';
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
        } else {
            if (is_null($value) || $value === '') {
                $value = $placeholder;
            }

            return $value . ' %';
        }
    }

    public function checkCoprodsParts()
    {
        if (!$this->isLoaded()) {
            return;
        }

        $event = $this->getParentInstance();
        if (is_null($event) || !$event->isLoaded()) {
            return;
        }

        $coprods = $event->getCoProds();

        $total = 0;
        $cp_parts_saved = array();
        $cp_parts_def = array();

        foreach ($coprods as $id_cp => $name) {
            $saved_part = $this->getCoProdSavedPart((int) $id_cp);
            if (!is_null($saved_part)) {
                $total += (float) $saved_part;
                $cp_parts_saved[(int) $id_cp] = (float) $saved_part;
            } else {
                $def_part = $this->getCoProdDefaultPart((int) $id_cp);
                if (!is_null($def_part)) {
                    $total += (float) $def_part;
                    $cp_parts_def[(int) $id_cp] = (float) $def_part;
                }
            }
        }

        if ($total > 100) {
            if (count($def_part)) {
                $diff = (float) round(($total - 100) / count($cp_parts_def), 2, PHP_ROUND_HALF_UP);
                foreach ($cp_parts_def as $id_cp => $value) {
                    $cp_value = (float) $value - $diff;
                    $this->db->delete('bmp_event_coprod_part', '`id_event_montant` = ' . (int) $this->id . ' AND `id_coprod` = ' . (int) $id_cp);
                    $this->db->insert('bmp_event_coprod_part', array(
                        'id_event_montant' => (int) $this->id,
                        'id_coprod'        => (int) $id_cp,
                        'part'             => (float) $cp_value
                    ));
                }
            } elseif (count($saved_part)) {
                $diff = (float) round(($total - 100) / count($cp_parts_saved), 2, PHP_ROUND_HALF_UP);
                foreach ($cp_parts_saved as $id_cp => $value) {
                    $cp_value = (float) $value - $diff;
                    $this->db->delete('bmp_event_coprod_part', '`id_event_montant` = ' . (int) $this->id . ' AND `id_coprod` = ' . (int) $id_cp);
                    $this->db->insert('bmp_event_coprod_part', array(
                        'id_event_montant' => (int) $this->id,
                        'id_coprod'        => (int) $id_cp,
                        'part'             => (float) $cp_value
                    ));
                }
            }
        }
    }

    public function getDefaultListExtraButtons($editDetails = true, $showDetails = true)
    {
        $event = $this->getParentInstance();
        if ($this->hasDetails()) {
            if ($event->getData('status') === 3) {
                $editDetails = false;
            }
            $buttons = array();
            if ($editDetails) {
                $buttons[] = array(
                    'label'   => 'Editer les détails',
                    'icon'    => 'far_file-alt',
                    'onclick' => 'loadModalView(\'' . $this->module . '\', \'' . $this->object_name . '\', ' . $this->id . ',\'' . 'details_list' . '\', $(this));'
                );
            }

            if ($showDetails) {
                $details = $this->getChildrenList('details');
                if (count($details)) {
                    $buttons[] = array(
                        'class'   => 'showDetailsList',
                        'label'   => 'Afficher la liste des détails',
                        'icon'    => 'chevron-down',
                        'onclick' => 'insertEventMontantDetailsListRow(' . $this->id . ', $(this));'
                    );
                    $buttons[] = array(
                        'class'   => 'hideDetailsList hidden',
                        'label'   => 'Masquer la liste des détails',
                        'icon'    => 'chevron-up',
                        'onclick' => 'removeEventMontantDetailsListRow(' . $this->id . ', $(this));'
                    );
                }
            }
            return $buttons;
        } else {
            if ($editDetails) {
                if (!class_exists('BMP_Event')) {
                    require_once __DIR__ . '/BMP_Event.class.php';
                }

                if ((int) $this->getData('id_montant') === $event->getBilletsIdTypeMontant()) {
                    return array(
                        array(
                            'label'   => 'Détails',
                            'icon'    => 'far_file-alt',
                            'onclick' => 'loadModalView(\'' . $this->module . '\', \'BMP_Event\', ' . $this->getData('id_event') . ',\'' . 'billets_list' . '\', $(this));'
                        )
                    );
                }
            }
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
        $types = $type->getAllTypes();
        $return = array(
            '' => ''
        );

        foreach ($types as $id_type => $label) {
            $return[$id_type] = $label;
        }
        return $return;
    }

    public function getDetailsName()
    {
        $montant = $this->getChildObject('type_montant');
        return 'Détails du montant "' . $montant->getData('name') . '"';
    }

    public function getCoprodAllowedInput()
    {
        $type_montant = $this->getChildObject('type_montant');
        $value = 0;
        if (!is_null($type_montant) && $type_montant->isLoaded()) {
            if ((int) $type_montant->getData('coprod')) {
                $value = 1;
            }
        }

        return '<input type="hidden" name="allow_coprod" value="' . $value . '"/>';
    }

    public function getListRowStyle()
    {
        if ($this->isLoaded()) {
            $id_category = (int) $this->getData('id_category_montant');
            if ($id_category) {
                $color = $this->db->getValue('bmp_categorie_montant', 'color', '`id` = ' . $id_category);
                return 'color: #' . $color . '; font-weight: bold';
            }
        }
        return '';
    }

    public function displayCategory($id_category)
    {
        $category = BimpObject::getInstance($this->module, 'BMP_CategorieMontant');
        $name = $category->getSavedData('name', (int) $id_category);
        $color = $category->getSavedData('color', (int) $id_category);

        if (!is_null($name) && $name) {
            return '<span style="font-weight: bold; color: #' . $color . '">' . $name . '</span>';
        }

        return $id_category;
    }

    public function displayCoProdPartAmount($id_coprod, $add_percents = true)
    {
        $value = $this->getCoProdPartAmount($id_coprod);

        if ($this->getData('type') === 1) {
            $value = - $value;
        }

        if (!$value) {
            return '';
        }

        $return = BimpTools::displayMoneyValue($value, 'EUR');

        if ($add_percents) {
            $part = $this->getCoProdPart($id_coprod);
            $return .= ' (' . $part . '%)';
        }

        return $return;
    }

    public function displayAmountByType($type, $coprods = 1)
    {
        if ((int) $this->getData('type') === (int) $type) {

            $amount = (float) $this->getData('amount');

            if (!(int) $coprods) {
                if ((int) $this->getData('id_coprod')) {
                    return 'HERE';
                }
                $event = $this->getParentInstance();
                if (!is_null($event) && $event->isLoaded()) {
                    foreach ($event->getCoProds() as $id_coprod => $coprod_name) {
                        $amount -= (float) $this->getCoProdPartAmount((int) $id_coprod);
                    }
                }
            }

            if ($type === 1) {
                $amount = -$amount;
            }

            if (!$amount) {
                return '';
            }

            return BimpTools::displayMoneyValue($amount, 'EUR');
        }

        return '';
    }

    public function calcDetailsTotal()
    {
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

    // Liste overrides: 

    public function listRowsOverride($list_name, &$rows)
    {
        if ($list_name === 'bilan_comptable') {
            foreach ($rows as $id => $r) {
                if ($r['frais'] === '' && $r['recette'] === '') {
                    unset($rows[$id]);
                }
            }

            $event = $this->getParentInstance();

            if (!is_null($event) && $event->isLoaded()) {
                $coprods = $event->getCoProds();
                $cp_soldes = $event->getCoprodsSoldes();
                $category = BimpObject::getInstance($this->module, 'BMP_CategorieMontant');
                $category->fetch(BMP_Event::$id_coprods_category);
                $category_name = $category->getData('name');
                $color = $category->getData('color');
                $row_style = 'font-weight: bold; color: #' . $color;

                foreach ($cp_soldes as $id_cp => $cp_solde) {
                    $rows['cp_' . $id_cp] = array(
                        'row_style'   => $row_style,
                        'category'    => $category_name,
                        'montant'     => $coprods[(int) $id_cp],
                        'status'      => '',
                        'code_compta' => '',
                        'taxe'        => '',
                        'frais'       => (($cp_solde > 0) ? BimpTools::displayMoneyValue(-$cp_solde, 'EUR') : ''),
                        'recette'     => (($cp_solde < 0) ? BimpTools::displayMoneyValue(-$cp_solde, 'EUR') : '')
                    );
                }
            }
        } elseif ($list_name === 'bilan_general') {
            foreach ($rows as $id => $r) {
                if ($r['frais'] === '' && $r['recette'] === '') {
                    unset($rows[$id]);
                }
            }
        }
//        if ($list_name === 'bilan') {
//            $event = $this->getParentInstance();
//            if (!$event->isLoaded()) {
//                return;
//            }
//
//            $ti_instance = BimpObject::getInstance($this->module, 'BMP_TotalInter');
//            $ti_list = $ti_instance->getDisplayableList();
//
//            if (!count($ti_list)) {
//                return;
//            }
//
//            $cp_instance = BimpObject::getInstance($this->module, 'BMP_EventCoProd');
//            $coprods = $cp_instance->getList(array(
//                'id_event' => (int) $event->id
//            ));
//
//            $toRemove = array();
//            $toAdd = array();
//
//            $EventMontant = BimpObject::getInstance($this->module, 'BMP_EventMontant');
//
//            $categ_assos = array(
//                'categories_frais_in',
//                'categories_recettes_in',
//                'categories_frais_ex',
//                'categories_recettes_ex'
//            );
//
//            $montants_assos = array(
//                'montants_in',
//                'montants_ex'
//            );
//            
//            $tm_instance = BimpObject::getInstance($this->module, 'BMP_TypeMontant');
//
//            foreach ($ti_list as $id_ti) {
//                $ti_instance->reset();
//                if ($ti_instance->fetch((int) $id_ti)) {
//                    $tm_list = array();
//
//                    foreach ($categ_assos as $association) {
//                        $asso = new BimpAssociation($ti_instance, $association);
//                        $categs_list = $asso->getAssociatesList($id_ti);
//                        foreach ($categs_list as $id_categ) {
//                            foreach ($tm_instance->getList(array(
//                                'id_category' => (int) $id_categ
//                            )) as $tm) {
//                                if (!in_array((int) $tm['id'], $tm_list)) {
//                                    $tm_list[] = (int) $tm['id'];
//                                }
//                            }
//                        }
//                        unset($asso);
//                    }
//                    
//                    foreach ($montants_assos as $association) {
//                        $asso = new BimpAssociation($ti_instance, $association);
//                        foreach ($asso->getAssociatesList($id_ti) as $id_tm) {
//                            if (!in_array((int) $id_tm, $tm_list)) {
//                                    $tm_list[] = (int) $id_tm;
//                                }
//                        }
//                        unset($asso);
//                    }
//                    
//                    foreach ($tm_list as $id_tm) {
//                        $em_list = $EventMontant->getList(array(
//                            'id_event'   => (int) $event->id,
//                            'id_montant' => (int) $id_tm
//                        ));
//
//                        $ti_to_add = array();
//
//                        foreach ($em_list as $em) {
//                            $EventMontant->reset();
//                            if ($EventMontant->fetch((int) $em['id'])) {
//                                $id_coprod = (int) $EventMontant->getData('id_coprod');
//                                if (!isset($ti_to_add[$id_coprod])) {
//                                    $ti_to_add[$id_coprod] = array(
//                                        'total'         => 0,
//                                        'status'        => 2,
//                                        'tva'           => null,
//                                        'coprops_parts' => array(),
//                                        'montants'      => array()
//                                    );
//                                    foreach ($coprods as $cp) {
//                                        $ti_to_add[$id_coprod]['coprops_parts'][$cp['id']] = 0;
//                                    }
//                                }
//                                $ti_to_add[$id_coprod]['montants'][] = $em['id'];
//
//                                if ((int) $EventMontant->getData('type') === 1) {
//                                    $ti_to_add[$id_coprod]['total'] -= (float) $EventMontant->getData('amount');
//                                    foreach ($coprods as $cp) {
//                                        $ti_to_add[$id_coprod]['coprops_parts'][$cp['id']] -= $EventMontant->getCoProdPartAmount((int) $cp['id']);
//                                    }
//                                } else {
//                                    $ti_to_add[$id_coprod]['total'] += (float) $EventMontant->getData('amount');
//                                    foreach ($coprods as $cp) {
//                                        $ti_to_add[$id_coprod]['coprops_parts'][$cp['id']] += $EventMontant->getCoProdPartAmount();
//                                    }
//                                }
//                                switch ($EventMontant->getData('status')) {
//                                    case 1:
//                                        $ti_to_add[$id_coprod]['status'] = 1;
//                                        break;
//
//                                    case 2:
//                                        break;
//
//                                    case 3:
//                                        if ($ti_to_add[$id_coprod]['status'] === 2) {
//                                            $ti_to_add[$id_coprod]['status'] = 3;
//                                        }
//                                }
//                                if (is_null($ti_to_add[$id_coprod]['tva'])) {
//                                    $ti_to_add[$id_coprod]['tva'] = $EventMontant->getTva();
//                                } elseif ($EventMontant->getTva() !== $ti_to_add[$id_coprod]['tva']) {
//                                    $ti_to_add[$id_coprod]['tva'] = '-';
//                                }
//                            }
//                        }
//                    }
//                    $toRemove = array_merge($toRemove, $tm_list);
//                    foreach ($ti_to_add as $id_coprod => $ti) {
//                        $toAdd[] = array(
//                            'name'          => $ti_instance->getData('name'),
//                            'coprod'        => $id_coprod,
//                            'total'         => $ti['total'],
//                            'status'        => $ti['status'],
//                            'tva'           => $ti['tva'],
//                            'coprods_parts' => $ti['coprops_parts'],
//                            'montants'      => $ti['montants']
//                        );
//                    }
//                }
//            }
//
////            echo '<pre>';
////            print_r($toAdd);
////            exit;
//
//            $newRows = array();
//
//            foreach ($rows as $id => $row) {
//                foreach ($toAdd as $key => $ta) {
//                    if (in_array($id, $ta['montants'])) {
//                        $newRow = array(
//                            'category'    => $row['category'],
//                            'coprod'      => $ta['coprod'],
//                            'montant'     => $ta['name'],
//                            'status'      => ('<span class="' . self::$status[$ta['status']]['classes'][0] . '">' . self::$status[$ta['status']]['label'] . '</span>'),
//                            'code_compta' => $row['code_compta'],
//                            'taxe'        => $ta['tva'],
//                            'recette'     => ((float) $ta['total'] > 0 ? BimpTools::displayMoneyValue($ta['total'], 'EUR') : ''),
//                            'frais'       => ((float) $ta['total'] < 0 ? BimpTools::displayMoneyValue($ta['total'], 'EUR') : '')
//                        );
//
//                        foreach ($ta['coprods_parts'] as $id_cp => $part) {
//                            $newRow['coprod_' . $id_cp] = BimpTools::displayMoneyValue($part, 'EUR');
//                            if ((float) $ta['total'] !== 0) {
//                                $newRow['coprod_' . $id_cp] .= ' (' . round(($part / $ta['total']) * 100, 2) . '%)';
//                            }
//                        }
//
//                        $newRows[$id] = $newRow;
//                        unset($toAdd[$key]);
////                        unset($rows[$id]);
//                        continue 2;
//                    }
//                }
//
//                $id_type_montant = (int) $this->db->getValue($this->getTable(), 'id_montant', '`id` = ' . (int) $id);
//
//                if (in_array($id_type_montant, $toRemove)) {
//                    unset($rows[$id]);
//                }
//            }
//
//            foreach ($newRows as $id => $row) {
//                $rows[$id] = $row;
//            }
//        }
    }

    // Overrides: 

    public function validatePost()
    {
        parent::validatePost();

        $event = $this->getParentInstance();
        $coprods = $event->getCoProds();

        foreach ($coprods as $id_cp => $cp_name) {
            if (BimpTools::isSubmit('coprod_' . $id_cp . '_part')) {
                $this->cp_new_parts[$id_cp] = BimpTools::getValue('coprod_' . $id_cp . '_part', '');
            }
        }
    }

    public function create()
    {
        $errors = parent::create();
        if (!count($errors)) {
            $event = $this->getParentInstance();
            $event->calcMontant((int) $this->getData('id_montant'), (int) $this->getData('id_coprod'));
        }

        return $errors;
    }

    public function update()
    {
        $errors = parent::update();

        if (!count($errors) && count($this->cp_new_parts)) {
            $event = $this->getParentInstance();
            if (is_null($event) || !$event->isLoaded()) {
                return array();
            }

            $instance = BimpObject::getInstance('bimpmargeprod', 'BMP_EventCoProd');
            $coprods = $instance->getList(array(
                'id_event' => (int) $event->id
            ));

            $parts = array();
            $total_wo_def = 0;
            $total = 0;
            $def_part = 0;

            $n = count($coprods);

            foreach ($coprods as $cp) {
                $value = isset($this->cp_new_parts[(int) $cp['id']]) ? $this->cp_new_parts[(int) $cp['id']] : '';
                if (is_null($value)) {
                    $value = '';
                }
                if ($value === '' || BimpTools::checkValueByType('float', $value)) {
                    if ($value === '') {
                        $total += (float) $this->getCoProdDefaultPart((int) $cp['id']);
                    } else {
                        $n--;
                        $total_wo_def += $value;
                        $total += $value;
                        $parts[(int) $cp['id']] = $value;
                    }
                } else {
                    $coprod = $event->getChildObject('coprods', (int) $cp['id']);
                    $societe = $coprod->getChildObject('societe');
                    $errors[] = 'Montant de la part du coproducteur "' . $societe->nom . '" invalide (Doit être un nombre décimal)';
                }
            }

            if ($total > 100) {
                if (($n > 0) && $total_wo_def < 100) {
                    $def_part = round((100 - $total_wo_def) / $n, 2, PHP_ROUND_HALF_DOWN);
                    foreach ($coprods as $cp) {
                        if (!array_key_exists((int) $cp['id'], $parts)) {
                            $parts[(int) $cp['id']] = $def_part;
                        }
                    }
                } else {
                    $errors[] = 'Le total des parts des co-producteurs ne peut pas dépasser 100%';
                }
            }

            if (!count($errors)) {
                $this->db->delete('bmp_event_coprod_part', '`id_event_montant` = ' . (int) $this->id);

                foreach ($parts as $id_coprod => $value) {
                    $defVal = $this->getCoProdDefaultPart((int) $id_coprod);
                    if (($value !== '') && (float) $value !== (float) $defVal) {
                        if ($this->db->insert('bmp_event_coprod_part', array(
                                    'id_event_montant' => (int) $this->id,
                                    'id_coprod'        => (int) $id_coprod,
                                    'part'             => (float) $value
                                )) <= 0) {
                            $coprod = $event->getChildObject('coprods', (int) $id_coprod);
                            $societe = $coprod->getChildObject('societe');
                            $errors[] = 'Echec de l\'enregistrement de la part du co-producteur "' . $societe->nom . '"';
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function fetch($id, $parent = null)
    {
        if (parent::fetch($id, $parent)) {
            $typeMontant = $this->getChildObject('type_montant');
            if (!is_null($typeMontant)) {
                $type = (int) $typeMontant->getData('type');
                if ($type && $type !== (int) $this->getData('type')) {
                    $this->set('type', $type);
                    $this->update();
                }
            }
            return true;
        }
        return false;
    }

    public function onChildSave(BimpObject $child)
    {
        if ($child->object_name === 'BMP_EventMontantDetail') {
            $this->calcDetailsTotal();
        }
    }

    public function onChildDelete(BimpObject $child)
    {
        if ($child->object_name === 'BMP_EventMontantDetail') {
            $this->calcDetailsTotal();
        }
    }
}
