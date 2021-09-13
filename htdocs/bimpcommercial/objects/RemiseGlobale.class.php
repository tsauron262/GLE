<?php

class RemiseGlobale extends BimpObject
{

    public $trigger_parent_process = true;
    public static $types = array(
        'amount'  => 'Montant fixe',
        'percent' => 'Pourcentage'
    );

    // Getters booléens: 


    public function isCreatable($force_create = false, &$errors = array())
    {
        $parent = $this->getParentObject();
        if (!BimpObject::objectLoaded($parent)) {
            $errors[] = 'ID du parent absent';
            return 0;
        }
        if (!$parent::$remise_globale_allowed) {
            $errors[] = 'Les remises globales ne sont pas disponibles pour ' . $parent->getLabel('the_plur');
            return 0;
        }
        if (!$force_create && !$parent->areLinesEditable()) {
            $errors[] = BimpTools::ucfirst($parent->getLabel('the')) . ' ne peut plus être édité' . $parent->e();
            return 0;
        }

        return (int) parent::isCreatable($force_create, $errors);
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        return (int) $this->isCreatable($force_edit, $errors);
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return (int) $this->isCreatable($force_delete, $errors);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'editInLogistique':
                if ($this->getData('obj_type') !== 'order') {
                    $errors[] = 'Action réservée aux commandes';
                    return 0;
                }

                $parent = $this->getParentObject();

                if (!is_a($parent, 'Bimp_Commande') || !$parent->isLogistiqueActive()) {
                    $errors[] = 'Le traitement logistique n\'est pas en cours pour cette commande';
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    // Getters: 

    public function getParentObject()
    {
        if ((int) $this->getData('id_obj')) {
            switch ($this->getData('obj_type')) {
                case 'propal':
                    return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $this->getData('id_obj'));
                case 'order':
                    return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $this->getData('id_obj'));
                case 'invoice':
                    return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $this->getData('id_obj'));
            }
        }

        return null;
    }

    public function getMinValue(&$min_rg_amount_ttc = 0)
    {
        $commande = $this->getParentObject();

        $min_value = 0;

        if (is_a($commande, 'Bimp_Commande')) {
            $total_ttc = (float) $commande->getTotalTtcWithoutRemises(true, true);

            // Détermination du montant TTC de la rg: 
            $rg_amount_ttc = 0;

            switch ($this->getData('type')) {
                case 'amount':
                    $rg_amount_ttc = (float) $this->getData('amount');
                    break;

                case 'percent':
                    $remise_rate = (float) $this->getData('percent');
                    $rg_amount_ttc = $total_ttc * ($remise_rate / 100);
                    break;
            }

            // Calcul du montant minimale de la rg selon les factures déjà validées: 
            $lines = $commande->getLines('not_text');
            $min_rg_amount_ttc = 0;

            foreach ($lines as $line) {
                $factures = $line->getData('factures');

                if (is_array($factures)) {
                    foreach ($factures as $id_fac => $fac_data) {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);

                        if (BimpObject::objectLoaded($facture) && (int) $facture->getData('fk_statut') > 0) {
                            $fac_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
                                        'id_obj'             => (int) $id_fac,
                                        'linked_object_name' => 'commande_line',
                                        'linked_id_object'   => (int) $line->id
                                            ), true);

                            if (BimpObject::objectLoaded($fac_line)) {
                                $fac_line_remise = BimpCache::findBimpObjectInstance('bimpcommercial', 'ObjectLineRemise', array(
                                            'id_object_line'           => (int) $fac_line->id,
                                            'object_type'              => $fac_line::$parent_comm_type,
                                            'linked_id_remise_globale' => (int) $this->id
                                                ), true);

                                if (BimpObject::objectLoaded($fac_line_remise)) {
                                    $min_rg_amount_ttc += (float) $fac_line->getTotalTtcWithoutRemises(true) * ((float) $fac_line_remise->getData('percent') / 100);
                                }
                            }
                        }
                    }
                }
            }

            switch ($this->getData('type')) {
                case 'percent':
                    if ($total_ttc) {
                        $min_value = ($min_rg_amount_ttc / $total_ttc) * 100;
                    } else {
                        $min_value = $this->getData('percent');
                    }
                    break;

                case 'amount':
                    $min_value = $min_rg_amount_ttc;
                    break;
            }
        }

        return $min_value;
    }

    public function getListsExtraButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('editInLogistique') && $this->canSetAction('editInLogistique')) {
            $buttons[] = array(
                'label'   => 'Modifier la valeur',
                'icon'    => 'fas_edit',
                'onclick' => $this->getJsActionOnclick('editInLogistique', array(), array(
                    'form_name' => 'edit_logistique'
                ))
            );
        }

        return $buttons;
    }

    // Affichage: 

    public function displayValue()
    {
        switch ($this->getData('type')) {
            case 'percent':
                return BimpTools::displayFloatValue($this->getData('percent')) . '%';

            case 'amount':
                return BimpTools::displayMoneyValue((float) $this->getData('amount'));
        }

        return '';
    }

    // Rendus HTML: 

    public function renderNewLogistiqueValueInput()
    {
        $html = '';

        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('Cette remise globale n\'existe plus');
        }

        $commande = $this->getParentObject();

        if (!is_a($commande, 'Bimp_Commande')) {
            $html .= BimpRender::renderAlerts('La pièce associée n\'est pas une commande');
        } else {
            $min_amount = 0;
            $min_value = $this->getMinValue($min_amount);
            $cur_value = 0;

            switch ($this->getData('type')) {
                case 'percent':
                    $cur_value = $this->getData('percent');

                    $options = array(
                        'addon_right' => BimpRender::renderIcon('fas_percent'),
                        'min_label'   => 1,
                        'data'        => array(
                            'data_type' => 'number',
                            'decimals'  => 2,
                            'min'       => $min_value,
                            'max'       => 100
                        )
                    );
                    break;

                case 'amount':
                    $cur_value = $this->getData('amount');
                    $options = array(
                        'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
                        'min_label'   => 1,
                        'data'        => array(
                            'data_type' => 'number',
                            'decimals'  => 2,
                            'min'       => $min_value
                        )
                    );
                    break;
            }

            $html .= BimpInput::renderInput('text', 'new_value', $cur_value, $options);

            if ($min_amount) {
                $msg = 'Part de la remise globale déjà facturée: ' . BimpTools::displayMoneyValue($min_amount);

                if ($this->getData('type') == 'percent') {
                    $msg .= ' (' . BimpTools::displayFloatValue($min_value) . '  %)';
                }
                $html .= BimpRender::renderAlerts($msg, 'info');
            }
        }

        return $html;
    }

    // Actions: 

    public function actionEditInLogistique($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Remise globale mise à jour avec succès';

        $new_value = (float) BimpTools::getArrayValueFromPath($data, 'new_value');

        $commande = $this->getParentObject();

        if (!is_a($commande, 'Bimp_Commande')) {
            $errors[] = 'La pièce associée n\'est pas une commande';
        } elseif (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'La commande #' . $this->getData('id_obj') . ' n\'existe plus';
        }

        if (!count($errors)) {
            $min_value = $this->getMinValue();

            if ($new_value < $min_value) {
                $msg = 'Vous ne pouvez pas saisir une valeur inférieure à ';
                switch ($this->getData('type')) {
                    case 'percent' :
                        $msg .= BimpTools::displayFloatValue($min_value) . ' %';
                        break;

                    case 'amount':
                        $msg .= BimpTools::displayMoneyValue($min_value);
                        break;
                }

                $msg .= ' (part de la remise déjà facturée)';
                $errors[] = $msg;
            } else {
                $errors = $this->updateField($this->getData('type'), $new_value);

                if (!count($errors)) {
                    $cur_status = $commande->getData('fk_statut');
                    $commande->updateField('fk_statut', 0);
                    $warnings = BimpTools::merge_array($warnings, $commande->processRemisesGlobales());
                    $warnings = BimpTools::merge_array($warnings, $commande->processFacturesRemisesGlobales());
                    
                    $commande->updateField('fk_statut', $cur_status);
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function validate()
    {
        $errors = parent::validate();

        $parent = $this->getParentObject();

        if (!BimpObject::objectLoaded($parent)) {
            $errors[] = 'ID ou type de l\'objet parent absent ou invalide';
        }

        switch ($this->getData('type')) {
            case 'percent':
                if (!(float) $this->getData('percent')) {
                    $errors[] = 'Veuillez indiquer le pourcentage de remise';
                }
                break;

            case 'amount':
                if (!(float) $this->getData('amount')) {
                    $errors[] = 'Veuillez indiquer le montant de la remise';
                }
                break;
        }

        return $errors;
    }

    public function onSave(&$errors = array(), &$warnings = array())
    {
        parent::onSave($errors, $warnings);

        if ($this->trigger_parent_process) {
            $parent = $this->getParentObject();

            if (BimpObject::objectLoaded($parent)) {
                $warnings = BimpTools::merge_array($warnings, $parent->processRemisesGlobales());
            }
        }
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $parent = $this->getParentObject();

            if (BimpObject::objectLoaded($parent) && is_a($parent, 'BimpComm')) {
                self::$cache[$parent->object_name . '_' . $parent->id . '_remises_globales'] = null;
            }
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $parent = $this->getParentObject();
        $id = (int) $this->id;

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors) && $this->trigger_parent_process && BimpObject::objectLoaded($parent)) {
            $warnings = BimpTools::merge_array($warnings, $parent->processRemisesGlobales());
        }

        return $errors;
    }
}
