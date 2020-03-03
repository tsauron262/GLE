<?php

require_once DOL_DOCUMENT_ROOT."/bimpmargeprod/objects/Abstract_margeprod.class.php";

class BMP_EventMontant extends Abstract_margeprod
{

    protected $cp_new_parts = array();
    public static $status_list = array(
        1 => array('label' => 'A confirmer', 'icon' => 'exclamation-circle', 'classes' => array('warning')),
        2 => array('label' => 'Confirmé', 'icon' => 'check-circle', 'classes' => array('success')),
        3 => array('label' => 'Optionnel', 'icon' => 'question-circle', 'classes' => array('info'))
    );

    // Getters boolééns: 

    public function isEventEditable()
    {
        $event = $this->getParentInstance();
        if (!is_null($event) && $event->isLoaded()) {
            return (int) $event->isInEditableStatus();
        }

        return 0;
    }

    public function isCreatable($force_create = false)
    {
        if (!(int) $this->isEventEditable()) {
            return 0;
        }
        return 1;
    }

    public function isEditable($force_edit = false)
    {
        return (int) ($this->isEventEditable());
    }
    


    public function isDeletable($force_delete = false)
    {
        return (int) $this->isFieldEditable('amount', $force_delete);
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if (in_array($field, array('amount', 'tva_tx'))) {
            if (!$this->isEventEditable()) {
                return 0;
            }

            if ($field === 'amount') {
                $typeMontant = $this->getChildObject('type_montant');
                if (is_null($typeMontant)) {
                    return 0;
                }

                if ((int) $typeMontant->getData('has_details')) {
                    return 0;
                }

                $event = $this->getParentInstance();
                if (BimpObject::objectLoaded($event)) {
                    $id_type_montant = (int) $this->getData('id_montant');
                    if ((int) $event->getData('status') === 1) {
                        if (($id_type_montant === BMP_Event::$id_bar20_type_montant) ||
                                ($id_type_montant === BMP_Event::$id_bar55_type_montant)) {
                            return 0;
                        }
                    }
                    
                    // Check des calculs auto activés: 
                    $cm_targets = $event->getCalcMontantsTargets(true);
                    
                    if (in_array($id_type_montant, $cm_targets)) {
                        return 0;
                    }
                }
            }
            if($this->getInitData("status") != 2 || $field == "status")
                return 1;
            return 0;
        }

        return (int) parent::isFieldEditable($field, $force_edit);
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

    public function hasDetails()
    {
        (int) $id_type_montant = BimpTools::getPostFieldValue('id_montant', $this->getData('id_montant'));

        if ($id_type_montant) {
            $type_montant = BimpCache::getBimpObjectInstance($this->module, 'BMP_TypeMontant', $id_type_montant);
            if ($type_montant->isLoaded()) {
                return (int) $type_montant->getData('has_details');
            }
        }
        return 0;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'setPaiements':
                if (!$this->isEventEditable()) {
                    $errors[] = 'Cet Evénement est validé et n\'est donc plus modifiable';
                    return 0;
                }
                $event = $this->getParentInstance();
                if (!$event->showCoprods()) {
                    $errors[] = 'Cet Evénement n\'a pas de copro.';
                    return 0;
                }
                return 1;
        }

        return (int) parent::isActionAllowed($action, $errors);
    }

    // Getters array

    public function getCategoriesBMPArray()
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
            $categories[$r['id']] = '<span style="font-weight: bold; color: #' . $r['color'] . '">' . $r['name'] . '</span>';
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

        $types = array();
        $event = $this->getParentInstance();

        if (BimpObject::objectLoaded($event)) {
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

            foreach ($rows as $r) {
                if (($id_coprod > 0) && !(int) $r['coprod']) {
                    continue;
                }

                if (!in_array((int) $r['id'], $current_montants)) {
                    $types[$r['id']] = $r['name'];
                }
            }
        }

        return $types;
    }

    public function getCoprodsArray()
    {
        $event = $this->getParentInstance();
        if (BimpObject::objectLoaded($event)) {
            $coprods = $event->getCoProds(true);
            $coprods[0] = 'Le Fil';
            return $coprods;
        }

        return array(
            0 => ''
        );
    }

    public function getAllCategoriesArray()
    {
        return BimpCache::getBimpObjectFullListArray('bimpmargeprod', 'BMP_CategorieMontant', 1);
    }

    public function getAllTypesArray()
    {
        return BimpCache::getBimpObjectFullListArray($this->module, 'BMP_TypeMontant', 1);
    }

    public function getPaiementCategoriesArray()
    {
        $event = $this->getParentInstance();
        if (BimpObject::objectLoaded($event)) {
            $id_coprod = BimpTools::getPostFieldValue('id_coprod', null);
            $type = BimpTools::getPostFieldValue('type', null);
            if (!is_null($id_coprod) && !is_null($type)) {
                $montants = $event->getChildrenObjects('montants', array(
                    'type'      => (int) $type,
                    'id_coprod' => (int) $id_coprod
                ));

                $categories = array(
                    0 => ''
                );

                foreach ($montants as $montant) {
                    if (!array_key_exists((int) $montant->getData('id_category_montant'), $categories)) {
                        $categ = BimpCache::getBimpObjectInstance($this->module, 'BMP_CategorieMontant', (int) $montant->getData('id_category_montant'));
                        if ($categ->isLoaded()) {
                            $categories[(int) $categ->id] = $categ->getData('name');
                        }
                    }
                }

                return $categories;
            }
        }

        return array(
            0 => ''
        );
    }

    public function getPaiementTypesMontantsArray()
    {
        $event = $this->getParentInstance();
        if (BimpObject::objectLoaded($event)) {
            $id_coprod = BimpTools::getPostFieldValue('id_coprod', null);
            $type = BimpTools::getPostFieldValue('type', null);
            $id_categ = BimpTools::getPostFieldValue('id_category_montant', null);
            if (!is_null($id_coprod) && !is_null($type) && !is_null($id_categ)) {
                $montants = $event->getChildrenObjects('montants', array(
                    'type'                => (int) $type,
                    'id_coprod'           => (int) $id_coprod,
                    'id_category_montant' => (int) $id_categ
                ));

                $types_montants = array(
                    0 => ''
                );

                foreach ($montants as $montant) {
                    if (!array_key_exists((int) $montant->id, $types_montants)) {
                        $tm = BimpCache::getBimpObjectInstance($this->module, 'BMP_TypeMontant', (int) $montant->getData('id_montant'));
                        if ($tm->isLoaded()) {
                            $types_montants[(int) $tm->id] = $tm->getData('name');
                        }
                    }
                }

                return $types_montants;
            }
        }

        return array();
    }

    // Getters params: 

    public function getCreateForm()
    {
        if ($this->isEventEditable()) {
            return 'default';
        }

        return '';
    }

    public function getDefaultListExtraButtons($edit = true, $showDetails = true)
    {
        $event = $this->getParentInstance();
        $buttons = array();

        if (BimpObject::objectLoaded($event) && $this->isLoaded()) {
            if ($event->getData('status') >= 3) {
                $edit = false;
            }

            if ($this->isActionAllowed('setPaiements')) {
//                $paiements = $this->getData('paiements');
//                if (is_array($paiements) && !empty($paiements)) {
                $buttons[] = array(
                    'label'   => 'Gérer les paiements',
                    'icon'    => 'fas_hand-holding-usd',
                    'onclick' => $this->getJsActionOnclick('setPaiements', array(
                        'id_event'            => (int) $this->getData('id_event'),
                        'type'                => (int) $this->getData('type'),
                        'id_coprod'           => (int) $this->getData('id_coprod'),
                        'id_category_montant' => (int) $this->getData('id_category_montant'),
                        'id_montant'          => (int) $this->getData('id_montant')
                            ), array(
                        'form_name'      => 'paiement_montant',
                        'on_form_submit' => 'function($form, extra_data) {return onEventMontantPaiementFormSubmit($form, extra_data);}'
                    ))
                );
//                }
            }

            if ($this->hasDetails()) {
                if ($edit) {
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
            } else {
                if ($edit) {
                    if ((int) $this->getData('id_montant') === (int) $event->getBilletsIdTypeMontant()) {
                        $buttons[] = array(
                            'label'   => 'Détails',
                            'icon'    => 'far_file-alt',
                            'onclick' => 'loadModalView(\'' . $this->module . '\', \'BMP_Event\', ' . (int) $this->getData('id_event') . ',\'' . 'billets_list' . '\', $(this));'
                        );
                    }
                }
            }
        }

        return $buttons;
    }

    public function getDefaultListExtraHeaderBtn($type_montant = 0)
    {
        if (!$this->isActionAllowed('setPaiements')) {
            return array();
        }

        switch ($type_montant) {
            case 1:
                $label = 'Ajouter un paiement exceptionnel';
                break;

            case 2:
                $label = 'Ajouter un reçu exceptionnel';
                break;

            default:
                return array();
        }

        return array(
            array(
                'label'       => $label,
                'icon_before' => 'fas_hand-holding-usd',
                'classes'     => array('btn', 'btn-default'),
                'attr'        => array(
                    'type'    => 'button',
                    'onclick' => $this->getJsActionOnclick('setPaiements', array(
                        'id_event' => (int) $this->getParentId(),
                        'type'     => $type_montant
                            ), array(
                        'form_name'      => 'paiement',
                        'on_form_submit' => 'function($form, extra_data) {return onEventMontantPaiementFormSubmit($form, extra_data);}'
                    ))
                )
            )
        );
    }

    public function getCoProdsCols($edit = true)
    {
        $event = $this->getParentInstance();

        if (!BimpObject::objectLoaded($event)) {
            return array();
        }

        $coprods = $event->getChildrenObjects('coprods');

        $cols = array();
        foreach ($coprods as $cp) {
            $soc = $cp->getChildObject('societe');
            if (BimpObject::objectLoaded($soc)) {
                if ($edit) {
                    $method = 'renderCoProdPartInput';
                } else {
                    $method = 'displayCoProdPartAmount';
                }
                $cols['coprod_' . $cp->id] = array(
                    'label'     => 'Part ' . $soc->getData('nom'),
                    'value'     => array(
                        'callback' => array(
                            'method' => $method,
                            'params' => array(
                                'id_coprod' => (int) $cp->id
                            )
                        ),
                    ),
                    'max_width' => '80px',
                    'min_width' => '80px'
                );
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

    public function getDetailsName()
    {
        $montant = $this->getChildObject('type_montant');
        return 'Détails du montant "' . $montant->getData('name') . '"';
    }

    // Gettesr parts co-prods: 

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
        if (BimpObject::objectLoaded($coprod)) {
            return (float) $coprod->getData('default_part');
        }
        return null;
    }

    public function getCoProdPart($id_coprod)
    {
        if ($id_coprod === 0) {
            $event = $this->getParentInstance();
            if (BimpObject::objectLoaded($event)) {
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
        return (float) $this->getData('amount') * ((float) $this->getCoProdPart($id_coprod) / 100);
    }

    // Getters valeurs: 

    public function getTvaTx()
    {
        if ($this->isLoaded()) {
            return (float) $this->getData('tva_tx');
        }

        return 0;
    }

    public function getMontantTtc()
    {
        return BimpTools::calculatePriceTaxIn((float) $this->getData('amount'), (float) $this->getData('tva_tx'));
    }

    public function getPaiements()
    {
        $paiements = array();

        if ($this->isLoaded()) {
            $coprods = $this->getCoprodsArray();
            $saved_paiements = $this->getData('paiements');
            $montant_ttc = (float) $this->getMontantTtc();
            $remain = $montant_ttc;

            if (is_array($saved_paiements) && !empty($saved_paiements)) {
                foreach ($coprods as $id_cp => $cp_label) {
                    if (array_key_exists((int) $id_cp, $saved_paiements)) {
                        if (is_array($saved_paiements[$id_cp])) {
                            $type = isset($saved_paiements[$id_cp]['type']) ? $saved_paiements[$id_cp]['type'] : 'amount';
                            $value = 0;
                            switch ($type) {
                                case 'percent':
                                    $percent = (float) $saved_paiements[$id_cp]['value'];
                                    if ($percent > 100) {
                                        $percent = 100;
                                    } elseif ($percent < 0) {
                                        $percent = 0;
                                    }

                                    $value = (float) ($montant_ttc * ($percent / 100));
                                    break;

                                case 'amount':
                                    $value = (float) $saved_paiements[$id_cp]['value'];
                                    break;
                            }
                            $paiements[$id_cp] = $value;
                        } elseif (BimpTools::isNumericType($saved_paiements[$id_cp])) {
                            $paiements[$id_cp] = (float) $saved_paiements[$id_cp];
                        } else {
                            $paiements[$id_cp] = 0;
                        }

                        $remain -= (float) $paiements[$id_cp];
                    }
                }
                if ($remain != 0) {
                    $paiements[(int) $this->getData('id_coprod')] += $remain;
                }
            } else {
                foreach ($coprods as $id_cp => $cp_label) {
                    if ((int) $id_cp === (int) $this->getData('id_coprod')) {
                        $paiements[$id_cp] = $montant_ttc;
                    } else {
                        $paiements[$id_cp] = 0;
                    }
                }
            }
        }

        return $paiements;
    }

    // Affichages: 

    public function displayCategory($id_category)
    {
        $category = BimpCache::getBimpObjectInstance($this->module, 'BMP_CategorieMontant', (int) $id_category);
        $name = $category->getData('name');
        $color = $category->getData('color');

        if (!is_null($name) && $name) {
            return '<span style="font-weight: bold; color: #' . $color . '">' . $name . '</span>';
        }

        return $id_category;
    }

    public function displayCoProdPartAmount($id_coprod, $add_percents = true)
    {
        $value = $this->getCoProdPartAmount($id_coprod);

        if ((int) $this->getData('type') === 1) {
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

    public function displayPaidAmount($type)
    {
        if ((int) $this->getData('type') === (int) $type) {

            $amount = (float) $this->getData('amount');

            if (!(int) $coprods) {
                if ((int) $this->getData('id_coprod')) {
                    return '';
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

    public function displayPaidBy()
    {
        if ($this->isLoaded()) {
            $paiements = $this->getPaiements();
            $payers = array();
            foreach ($paiements as $id_cp => $amount) {
                if (round((float) $amount, 2)) {
                    $payers[] = $id_cp;
                }
            }

            if (count($payers) <= 1) {
                $coprods = $this->getCoprodsArray();
                if (isset($coprods[(int) $payers[0]])) {
                    return $coprods[(int) $payers[0]];
                }
                return '';
            }
            return 'Partagé';
        }

        return '';
    }

    // Rendus HTML: 

    public function renderCoprodAllowedInput()
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

    public function renderListRowStyle()
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

    public function renderCoProdPartInput($id_coprod)
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

    public function renderPaiementInputs()
    {
        $html = '';
        $event = $this->getParentInstance();
        if (BimpObject::objectLoaded($event)) {
            $id_montant_coprod = BimpTools::getPostFieldValue('id_coprod');
            $id_type_montant = BimpTools::getPostFieldValue('id_montant');

            if (is_null($id_montant_coprod)) {
                $errors[] = 'Coproducteur absent';
            }
            if (is_null($id_type_montant)) {
                $errors[] = 'Type de montant absent';
            }

            if (!count($errors)) {
                $montant = BimpCache::findBimpObjectInstance($this->module, 'BMP_EventMontant', array(
                            'id_event'   => (int) $event->id,
                            'id_coprod'  => (int) $id_montant_coprod,
                            'id_montant' => (int) $id_type_montant
                                ), false, false);
                if (BimpObject::objectLoaded($montant)) {
                    $montant_type = (int) $montant->getData('type');
                    $montant_ht = (float) $montant->getData('amount');
                    $montant_ttc = $montant->getMontantTtc();
                    $tva_tx = (float) $montant->getTvaTX();

                    $html .= '<input type="hidden" name="montant_total_ttc" value="' . $montant_ttc . '"/>';
                    $html .= '<input type="hidden" name="tva_tx" value="' . $tva_tx . '"/>';
                    $html .= '<p>Montant HT: <strong>' . BimpTools::displayMoneyValue($montant_ht, 'EUR') . '</strong></p>';
                    $html .= '<p>Montant TTC: <strong>' . BimpTools::displayMoneyValue($montant_ttc, 'EUR') . '</strong></p>';
                    $html .= '<p>Taux de TVA: <strong>' . BimpTools::displayFloatValue($tva_tx) . '%</strong></p>';
                    if ($montant_ttc != 0) {
                        $paiements_data = $montant->getData('paiements');
                        $paiements = $montant->getPaiements();
                        $coprods = $this->getCoprodsArray();
                        foreach ($paiements as $id_coprod => $paid) {
                            $html .= '<input type="hidden" name="initial_paiement_' . $id_coprod . '" value="' . $paid . '"/>';
                        }

                        $html .= '<table class="bimp_list_table">';
                        $html .= '<thead>';
                        $html .= '<tr>';
                        $html .= '<th>Co-producteur</th>';
                        $html .= '<th>Montant ' . ($montant_type === 1 ? 'payé' : 'reçu') . ' HT</th>';
                        $html .= '<th>Montant ' . ($montant_type === 1 ? 'payé' : 'reçu') . ' TTC</th>';
                        $html .= '<th>Pourcentage</th>';
                        $html .= '<th>Enregistrer</th>';
                        $html .= '</tr>';
                        $html .= '</thead>';

                        $html .= '<tbody>';

                        $save_options = array(
                            'amount'  => array('label' => 'Montant', 'icon' => 'fas_euro-sign'),
                            'percent' => array('label' => 'Pourcentage', 'icon' => 'fas_percent')
                        );

                        foreach ($paiements as $id_cp => $paid_ttc) {
                            $rate = $paid_ttc / $montant_ttc;
                            $percent = round($rate, 2) * 100;
                            $paid_ht = $montant_ht * $rate;

                            $bk = ((int) $id_cp === (int) $id_montant_coprod ? ' background-color: #EBEBEB!important;' : '');

                            $html .= '<tr id="coprod_' . $id_cp . '_payment_row" class="coprod_payment_row" data-id_coprod="' . $id_cp . '">';
                            $html .= '<td style="font-weight: bold;' . ($bk ? $bk : '') . '">' . $coprods[(int) $id_cp] . '</td>';
                            $html .= '<td' . ($bk ? ' style="' . $bk . '"' : '') . '>';
                            $html .= BimpInput::renderInput('text', 'new_paid_amount_ttc' . $id_cp, round($paid_ht, 2), array(
                                        'data'        => array(
                                            'data_type' => 'number',
                                            'decimals'  => 2,
                                            'min'       => 0,
                                            'max'       => $montant_ht
                                        ),
                                        'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
                                        'style'       => 'width: 120px',
                                        'extra_class' => 'payment_input payment_amount_ht'
                            ));
                            $html .= '</td>';
                            $html .= '<td' . ($bk ? ' style="' . $bk . '"' : '') . '>';
                            $html .= BimpInput::renderInput('text', 'new_paid_amount_ht' . $id_cp, round($paid_ttc, 2), array(
                                        'data'        => array(
                                            'data_type' => 'number',
                                            'decimals'  => 2,
                                            'min'       => 0,
                                            'max'       => $montant_ttc
                                        ),
                                        'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
                                        'style'       => 'width: 120px',
                                        'extra_class' => 'payment_input payment_amount_ttc'
                            ));
                            $html .= '</td>';
                            $html .= '<td' . ($bk ? ' style="' . $bk . '"' : '') . '>';
                            $html .= BimpInput::renderInput('text', 'new_paid_percent_' . $id_cp, $percent, array(
                                        'data'        => array(
                                            'data_type' => 'number',
                                            'decimals'  => 6,
                                            'min'       => 0,
                                            'max'       => 100
                                        ),
                                        'addon_right' => BimpRender::renderIcon('fas_percent'),
                                        'style'       => 'width: 120px',
                                        'extra_class' => 'payment_input payment_percent'
                            ));
                            $html .= '</td>';
                            $html .= '<td' . ($bk ? ' style="' . $bk . '"' : '') . '>';

                            $paid_type = isset($paiements_data[$id_cp]['type']) ? $paiements_data[$id_cp]['type'] : 'percent';
                            $html .= BimpInput::renderInput('select', 'new_paid_type_' . $id_cp, $paid_type, array(
                                        'options'     => $save_options,
                                        'extra_class' => 'payment_type'
                            ));
                            $html .= '</td>';
                            $html .= '</tr>';
                        }

                        $html .= '</tbody>';
                        $html .= '</table>';

                        $html .= '<p class="inputHelp">Entrez directement la nouvelle valeur souhaitée. Les ajustements se feront automatiquement.</p>';
                        $html .= BimpRender::renderAlerts('Si vous choisissez d\'enregistrer un pourcentage, le montant ' . ($montant_type === 1 ? 'payé' : 'reçu') . ' correspondant sera recalculé en cas de changement.', 'info');
                    } else {
                        $html .= BimpRender::renderAlerts('Aucun paiement nécessaire pour ce type de montant', 'info');
                    }
                } else {
                    $errors[] = 'Montant correspondant non trouvé';
                }
            }
        } else {
            $errors[] = 'ID de l\'événement absent';
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        return $html;
    }

    // Traitements: 

    public function checkCoprodsParts()
    {
        if (!$this->isLoaded()) {
            return;
        }

        $event = $this->getParentInstance();
        if (!BimpObject::objectLoaded($event)) {
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

    public function calcDetailsTotal()
    {
        $amount = 0;
        $children = $this->getChildrenObjects('details');
        foreach ($children as $child) {
            if (!is_null($child) && is_a($child, 'BMP_EventMontantDetail')) {
                $amount += (float) $child->getTotal();
            }
        }
        $amount = (float) round($amount, 2);
        $current_amount = (float) $this->getData('amount');
        if ($current_amount !== $amount) {
            $this->set('amount', $amount);
            $this->update();
        }
    }

    public function setPaiements($paiements)
    {
        if (!$this->isLoaded()) {
            return array('ID du montant absent');
        }

        if (!is_array($paiements) || !count($paiements)) {
            return array('Liste des paiements non spécifiée');
        }

        $coprods = $this->getCoprodsArray();
        foreach ($coprods as $id_cp => $cp_label) {
            if (!array_key_exists($id_cp, $paiements)) {
                $paiements[$id_cp] = array(
                    'type'  => 'amount',
                    'value' => 0
                );
            }
        }

        $this->set('paiements', $paiements);
        $this->checkPaiements(false);

        return $this->update();
    }

    public function checkPaiements($save_if_change = false)
    {
        if (!$this->isLoaded()) {
            return array('ID du montant absent');
        }

        $paiements = $this->getData('paiements');

        if (!is_array($paiements) || !count($paiements)) {
            return array('Aucun paiements enregistrés');
        }

        $id_coprod = (int) $this->getData('id_coprod');
        $coprods = $this->getCoprodsArray();
        $montant_ttc = (float) $this->getMontantTtc();
        $remain = $montant_ttc;

        $has_change = false;
        foreach ($paiements as $id_cp => $paiement) {
            if (!array_key_exists((int) $id_cp, $coprods)) {
                unset($paiements[$id_cp]);
                $has_change = true;
            }
        }
        foreach ($coprods as $id_cp => $cp_label) {
            if (!array_key_exists((int) $id_cp, $paiements)) {
                $has_change = true;
                $paiements[(int) $id_cp] = array(
                    'type'  => 'amount',
                    'value' => 0
                );
            }
        }

        foreach ($paiements as $id_cp => $paiement) {
            $amount = 0;
            $type = 'amount';

            if (is_array($paiement)) {
                if (isset($paiement['type'])) {
                    $type = $paiement['type'];
                }

                switch ($type) {
                    case 'amount':
                        $amount = (float) $paiement['value'];
                        break;

                    case 'percent':
                        $percent = (float) $paiement['value'];
                        if ($percent > 100) {
                            $percent = 100;
                        } elseif ($percent < 0) {
                            $percent = 0;
                        }
                        $amount = (float) ($montant_ttc * ($percent / 100));
                        break;
                }
            } elseif (BimpTools::isNumericType($paiement)) {
                $amount = (float) $paiement;
            }

            if ((int) $id_cp === $id_coprod) {
                continue;
            }

            if ((float) $amount > $remain) {
                $has_change = true;
                $amount = $remain;
            }

            if ((float) $amount < 0) {
                $has_change = true;
                $amount = 0;
            }

            $remain -= (float) $amount;

            if ($remain < 0) {
                $remain = 0;
            }

            $value = (float) $amount;

            if ($type === 'percent') {
                if (!(float) $montant_ttc) {
                    $value = 0;
                } else {
                    $value = (float) (($amount / $montant_ttc) * 100);
                }
            }

            if (!is_array($paiements[$id_cp])) {
                $has_change = true;
            }

            $paiements[$id_cp] = array(
                'type'  => $type,
                'value' => $value
            );
        }

        if ($remain > 0) {
            $amount = 0;
            $type = 'amount';
            if (is_array($paiements[$id_coprod])) {
                $type = $paiements[$id_coprod]['type'];
                switch ($type) {
                    case 'amount':
                        $amount = (float) $paiements[$id_coprod]['value'];
                        break;

                    case 'percent':
                        $percent = (float) $paiement[$id_coprod]['value'];
                        if ($percent > 100) {
                            $percent = 100;
                        } elseif ($percent < 0) {
                            $percent = 0;
                        }

                        $amount = $montant_ttc * ($percent / 100);
                        break;
                }
            } elseif (BimpTools::isNumericType($paiements[$id_coprod])) {
                $amount = (float) $paiements[$id_coprod];
            }

            if ((float) $amount !== (float) $remain || !is_array($paiements[$id_coprod])) {
                if ($type === 'percent') {
                    if ((float) $montant_ttc) {
                        $value = 0;
                    } else {
                        $value = ($remain / $montant_ttc) * 100;
                    }
                } else {
                    $value = $remain;
                }
                $paiements[$id_coprod] = array(
                    'type'  => $type,
                    'value' => $value
                );
                $has_change = true;
            }
        }

        $this->set('paiements', $paiements);

        if ($save_if_change && $has_change) {
            $update_errors = $this->update();
            if (count($update_errors)) {
                return array(BimpTools::getMsgFromArray($update_errors, 'Echec de l\'enregistrement des paiements'));
            }
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

            if (BimpObject::objectLoaded($event)) {
                $coprods = $event->getCoProds();
                $cp_soldes = $event->getCoprodsSoldes();
                $category = BimpCache::getBimpObjectInstance($this->module, 'BMP_CategorieMontant', (int) BMP_Event::$id_coprods_category);
                $category_name = $category->getData('name');
                $color = $category->getData('color');
                $row_style = 'font-weight: bold; color: #' . $color;

                foreach ($cp_soldes as $id_cp => $cp_solde) {
                    $row = array(
                        'params' => array(
                            'checkbox'       => 0,
                            'single_cell'    => false,
                            'item_params'    => array(
                                'update_btn'      => 0,
                                'edit_btn'        => 0,
                                'delete_btn'      => 0,
                                'page_btn'        => 0,
                                'inline_view'     => null,
                                'modal_view'      => null,
                                'edit_form'       => '',
                                'edit_form_title' => '',
                                'extra_btn'       => array(),
                                'row_style'       => $row_style,
                                'td_style'        => ''
                            ),
                            'canEdit'        => 0,
                            'canView'        => 1,
                            'canDelete'      => 0,
                            'instance_name'  => '',
                            'url'            => '',
                            'page_btn_label' => '',
                        ),
                        'cols'   => array(
//                            'row_style'   => $row_style,
                            'category'    => array('content' => $category_name, 'show' => 1, 'hidden' => 0),
                            'montant'     => array('content' => $coprods[(int) $id_cp], 'show' => 1, 'hidden' => 0),
                            'status'      => array('content' => '', 'show' => 1, 'hidden' => 0),
                            'code_compta' => array('content' => '', 'show' => 1, 'hidden' => 0),
                            'taxe'        => array('content' => '', 'show' => 1, 'hidden' => 0),
                            'frais'       => array('content' => ($cp_solde > 0) ? BimpTools::displayMoneyValue(-$cp_solde, 'EUR') : '', 'show' => 1, 'hidden' => 0),
                            'recette'     => array('content' => ($cp_solde < 0) ? BimpTools::displayMoneyValue(-$cp_solde, 'EUR') : '', 'show' => 1, 'hidden' => 0)
                        )
                    );

                    $rows['cp_' . $id_cp] = $row;
                }
            }
        } elseif ($list_name === 'bilan_general') {
            foreach ($rows as $id => $r) {
                if ($r['frais'] === '' && $r['recette'] === '') {
                    unset($rows[$id]);
                }
            }
        }
    }

    // Actions: 

    public function actionSetPaiements($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Paiements exceptionnels enregistrés avec succès';

        $id_event = isset($data['id_event']) ? (int) $data['id_event'] : null;
        $type = isset($data['type']) ? (int) $data['type'] : null;
        $id_coprod = isset($data['id_coprod']) ? (int) $data['id_coprod'] : null;
        $id_type_montant = isset($data['id_montant']) ? (int) $data['id_montant'] : null;
        $paiements = isset($data['paiements']) ? $data['paiements'] : null;

        if (is_null($id_event)) {
            $errors[] = 'ID de l\'événement absent';
        }
        if (is_null($type)) {
            $errors[] = 'Type du montant (frais ou recette) absent';
        }
        if (is_null($id_coprod)) {
            $errors[] = 'Co-producteur absent';
        }
        if (is_null($id_type_montant)) {
            $errors[] = 'Type de montant absent';
        }
        if (is_null($paiements)) {
            $errors[] = 'Liste des paiements absente';
        }

        if (!count($errors)) {
            $montant = BimpCache::findBimpObjectInstance($this->module, $this->object_name, array(
                        'id_event'   => $id_event,
                        'type'       => $type,
                        'id_montant' => $id_type_montant,
                        'id_coprod'  => $id_coprod
            ));

            if (!$montant->isLoaded()) {
                $errors[] = 'Montant non trouvé';
            } else {
                $errors = $montant->setPaiements($paiements);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
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

    public function create(&$warnings = array(), $force_create = false)
    {
        if ((int) $this->getData('id_montant') && !(float) $this->getData('tva_tx')) {
            $tm = $this->getChildObject('type_montant');
            $id_taxe = (int) $tm->getData('id_taxe');
            if ($id_taxe) {
                $this->set('tva_tx', BimpTools::getTaxeRateById((int) $id_taxe));
            }
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors) && $this->isLoaded()) {
            $event = $this->getParentInstance();
            if (BimpObject::objectLoaded($event)) {
                $tm = BimpCache::getBimpObjectInstance($this->module, 'BMP_TypeMontant', (int) $this->getData('id_montant'));

                // Création des détails du montant pour les valeurs prédéfinies non liées aux groupes
                if ($tm->isLoaded() && (int) $tm->getData('has_details')) {
                    $mdv_instance = BimpObject::getInstance($this->module, 'BMP_MontantDetailValue');
                    $rows = $mdv_instance->getList(array(
                        'id_type_montant'   => (int) $tm->id,
                        'use_groupe_number' => 0,
                    ));
                    if (is_array($rows) && count($rows)) {
                        foreach ($rows as $r) {
                            $detail = BimpObject::getInstance($this->module, 'BMP_EventMontantDetail');
                            $detail_errors = $detail->validateArray(array(
                                'id_event_montant' => (int) $this->id,
                                'label'            => $r['label'],
                                'quantity'         => (int) $r['qty'],
                                'unit_price'       => (float) $r['unit_price']
                            ));

                            $detail_warnings = array();
                            if (!count($detail_errors)) {
                                $detail_errors = $detail->create($detail_warnings, true);
                            }
                            if (count($detail_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($detail_errors, 'Echec de la création de la valeur prédéfinie "' . $r['label'] . '"');
                            }
                            if (count($detail_warnings)) {
                                $warnings[] = BimpTools::getMsgFromArray($detail_warnings, 'Des erreurs sont survenues suite à la création de la valeur prédéfinie "' . $r['label'] . '"');
                            }
                        }
                    }
                }

                $event->calcMontant((int) $this->getData('id_montant'), null);
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $paiements_errors = $this->checkPaiements(false);
        if (count($paiements_errors)) {
            $warnings[] = BimpTools::getMsgFromArray($paiements_errors, 'Des erreurs sont survenues lors de la mise à jour des paiements');
        }

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if (count($this->cp_new_parts)) {
                $event = $this->getParentInstance();
                if (BimpObject::objectLoaded($event)) {
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
                            $errors[] = 'Montant de la part du coproducteur "' . $societe->getData('nom') . '" invalide (Doit être un nombre décimal)';
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
                                    $errors[] = 'Echec de l\'enregistrement de la part du co-producteur "' . $societe->getData('nom') . '"';
                                }
                            }
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
