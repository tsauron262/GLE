<?php

class BL_CommandeFournReception extends BimpObject
{

    const BLCFR_BROUILLON = 0;
    const BLCFR_RECEPTIONNEE = 1;
    const BLCFR_ANNULEE = 2;

    public static $status_list = array(
        self::BLCFR_BROUILLON    => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        self::BLCFR_RECEPTIONNEE => array('label' => 'Réceptionnée', 'icon' => 'check', 'classes' => array('success')),
        self::BLCFR_ANNULEE      => array('label' => 'Annulée', 'icon' => 'times', 'classes' => array('danger'))
    );
    public static $validation_status_list = array(
        0 => array('label' => 'Non commencée', 'classes' => array('info')),
        1 => array('label' => 'Commencée', 'classes' => array('warning')),
        2 => array('label' => 'Terminée', 'classes' => array('success'))
    );

    // Droits user: 

    public function canEdit()
    {
        return 1;
    }

    public function canEditStockOut()
    {
        global $user;

        return (int) $user->rights->bimpcommercial->br_stock_out;
    }

    // Getters booléens: 

    public function isFieldEditable($field, $force_edit = false)
    {
        if ($field === 'id_entrepot' && $this->isLoaded()) {
            return 0;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'saveLinesData':
            case 'validateReception':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if ((int) $this->getData('status') !== self::BLCFR_BROUILLON) {
                    $errors[] = 'La réception n\'a pas le statut "brouillon"';
                    return 0;
                }
                return 1;

            case 'cancelReception':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if ((int) $this->getData('status') !== self::BLCFR_RECEPTIONNEE) {
                    $errors[] = 'La réception n\'a pas le statut "réceptionnée"';
                    return 0;
                }
                if ((int) $this->getData('id_facture')) {
                    $errors[] = 'Cette réception a été facturée';
                    return 0;
                }
                $commande = $this->getParentInstance();
                if ((int) $commande->isBilled()) {
                    $errors[] = 'Une facture a été créée pour la commande fournisseur';
                    return 0;
                }
                return 1;

            case 'split':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if ((int) $this->getData('id_facture')) {
                    $errors[] = 'Cette réception a été facturée';
                    return 0;
                }
                if ((int) $this->getData('validation_status') === 1) {
                    $errors[] = 'Veuillez finaliser la validation de cette réception';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function isCreatable($force_create = false, &$errors = array())
    {
        $commande = $this->getParentInstance();
        if (BimpObject::objectLoaded($commande)) {
            return 1;
        }

        return 0;
    }

    public function isCancellable(&$errors = array())
    {
        if (!$this->isLoaded()) {
            $errors[] = 'ID de la réception absent';
        } else {
            if ((int) $this->getData('id_facture')) {
                $errors[] = 'Cette réception a été facturée';
            } else {
                $commande = $this->getParentInstance();
                if (!BimpObject::objectLoaded($commande)) {
                    $errors[] = 'ID de la commande fournisseur absent';
                } else {
                    BimpObject::loadClass('bimpcommercial', 'ObjectLine');
                    $lines = $commande->getChildrenObjects('lines', array(
                        'type' => array(
                            'in' => array(ObjectLine::LINE_PRODUCT, ObjectLine::LINE_FREE)
                        )
                    ));

                    $check = true;

                    $stock_out = (int) $this->getData('stock_out');

                    foreach ($lines as $line) {
                        $line_errors = array();
                        if (!$line->isReceptionCancellable($this->id, $line_errors, $stock_out)) {
                            $check = false;
                        }

                        if (count($line_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
                        }
                    }

                    return ((count($errors) || !$check) ? 0 : 1);
                }
            }
        }

        return 0;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ((int) $this->getData('status') === 0) {
            return 1;
        }

        return 0;
    }

    public function isFacturable()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('status') === self::BLCFR_RECEPTIONNEE && !(int) $this->getData('id_facture')) {
                return 1;
            }
        }


        return 0;
    }

    // Getters valeurs: 

    public function getName($with_generic = true)
    {
        return 'Réception #' . $this->getData('num_reception');
    }

    public function getTotalHT()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $commande = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            return 0;
        }

        $total_ht = 0;

        foreach ($commande->getLines('not_text') as $line) {
            $total_ht += (float) $line->getReceptionTotalHt($this->id);
        }

        return $total_ht;
    }

    public function getTotalTTC()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $commande = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            return 0;
        }

        $total_ttc = 0;

        foreach ($commande->getLines('not_text') as $line) {
            $total_ttc += (float) $line->getReceptionTotalTTC($this->id);
        }

        return $total_ttc;
    }

    // Getters config: 

    public function getListsExtraBtn()
    {
        $buttons = array();

        $buttons[] = array(
            'label'   => 'Détails',
            'icon'    => 'fas_bars',
            'onclick' => $this->getJsLoadModalView('details', 'Détails de la réception ' . $this->getData('ref'))
        );

        $success_callback = '';
        $commande_fourn = $this->getParentInstance();
        if (BimpObject::objectLoaded($commande_fourn)) {
            $success_callback = 'function() {reloadObjectHeader(' . $commande_fourn->getJsObjectData() . ')}';
        }

        if ($this->isActionAllowed('validateReception') && $this->canSetAction('validateReception')) {
            $use_bds = ((int) BimpCore::getConf('use_bds_for_receptions', null, 'bimpcommercial') && BimpCore::isUserDev());

            if (!$use_bds || (int) $this->getData('validation_status') === 0) {
                $buttons[] = array(
                    'label'   => 'Valider la réception',
                    'icon'    => 'fas_check-circle',
                    'onclick' => $this->getJsActionOnclick('validateReception', array(), array(
                        'form_name'        => 'validate',
                        'on_form_submit'   => 'function($form, extra_data) {return onReceptionValidationFormSubmit($form, extra_data);}',
                        'success_callback' => $success_callback,
                        'use_bimpdatasync' => $use_bds
                    ))
                );
            } elseif ($use_bds) {
                $buttons[] = array(
                    'label'   => 'Finaliser la validation de la réception',
                    'icon'    => 'fas_check-circle',
                    'onclick' => $this->getJsActionOnclick('validateReception', array(), array(
                        'use_bimpdatasync' => 1
                    ))
                );
            }
        }

        if ($this->isActionAllowed('cancelReception') && $this->canSetAction('cancelReception')) {
            $buttons[] = array(
                'label'   => 'Annuler la réception',
                'icon'    => 'fas_times-circle',
                'onclick' => $this->getJsActionOnclick('cancelReception', array(), array(
                    'confirm_msg'      => 'Veuillez confirmer l\\\'annulation de cette réception. Les équipements créés seront supprimés',
                    'success_callback' => $success_callback
                ))
            );
        }

        if (!(int) $this->getData('id_facture') && (int) $this->getData('status') === self::BLCFR_RECEPTIONNEE &&
                $commande_fourn->isActionAllowed('createInvoice') && $commande_fourn->canSetAction('createInvoice')) {
            $onclick = $commande_fourn->getJsActionOnclick('createInvoice', array(
                'ref_supplier'      => $commande_fourn->getData('ref_supplier'),
                'libelle'           => htmlentities(addslashes($commande_fourn->getData('libelle'))),
                'ef_type'           => $commande_fourn->getData('ef_type'),
                'id_cond_reglement' => (int) $commande_fourn->getData('fk_cond_reglement'),
                'id_mode_reglement' => (int) $commande_fourn->getData('fk_mode_reglement'),
                'receptions'        => json_encode(array($this->id))
                    ), array(
                'form_name' => 'invoice'
            ));
            $buttons[] = array(
                'label'   => 'Créer une facture fournisseur',
                'icon'    => 'fas_file-invoice-dollar',
                'onclick' => $onclick
            );
        }

        if ($this->canSetAction('split') && $this->isActionAllowed('split')) {
            $buttons[] = array(
                'label'   => 'Scinder en deux réceptions',
                'icon'    => 'fas_object-ungroup',
                'onclick' => $this->getJsActionOnclick('split', array(), array(
                    'form_name'      => 'split',
                    'on_form_submit' => 'function($form, extra_data) {return onSplitReceptionFormSubmit($form, extra_data);}'
                ))
            );
        }

        return $buttons;
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'billed':
                // Bouton Exclure désactivé
                if (is_array($values) && !empty($values)) {
                    if (in_array(0, $values) && in_array(1, $values)) {
                        break;
                    }
                    if (in_array(0, $values)) {
                        $alias = $main_alias . '___commande_fourn';
                        $joins[$alias] = array(
                            'alias' => $alias,
                            'table' => 'commande_fournisseur',
                            'on'    => $alias . '.rowid = ' . $main_alias . '.id_commande_fourn'
                        );
                        $filters[$main_alias . '.id_facture'] = 0;
                        $filters[$alias . '.invoice_status'] = array(
                            'operator' => '<',
                            'value'    => 2
                        );
                    }
                    if (in_array(1, $values)) {
                        $filters[$main_alias . '.id_facture'] = array(
                            'operator' => '>',
                            'value'    => 0
                        );
                    }
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
    }

    // Rendus HTML: 

    public function renderCommandeFournLinesForm()
    {
        $html = '';

        $commande = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            $html .= BimpRender::renderAlerts('ID de la commande fournisseur absent');
        } else {
            $lines = array();
            foreach ($commande->getChildrenObjects('lines') as $line) {
                if ((int) $line->getData('type') === Bimp_CommandeFournLine::LINE_PRODUCT) {
                    $product = $line->getProduct();
                    if (BimpObject::objectLoaded($product) && (int) $product->getData('fk_product_type') === 0) {
                        if (abs((float) $line->getFullQty()) > abs((float) $line->getReceivedQty())) {
                            $lines[] = $line;
                        }
                    }
                }
            }

            if (!count($lines)) {
                $html .= BimpRender::renderAlerts('Il n\'y a aucune unité à réceptionner pour cette commande fournisseur', 'warning');
            } else {
                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Désignation</th>';
                $html .= '<th>Qté</th>';
                $html .= '<th>Prix d\'achat</th>';
                $html .= '<th>Tx TVA</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody class="receptions_rows">';
                foreach ($lines as $line) {
                    $tpl = $line->renderReceptionFormRowTpl(false);
                    $tpl = str_replace('receptionidx', '1', $tpl);
                    $tpl = str_replace('linetotalmaxinputclass', 'line_' . $line->id . '_reception_max', $tpl);
                    $tpl = str_replace('linetotalmininputclass', 'line_' . $line->id . '_reception_min', $tpl);
                    $html .= '<tr class="reception_row">';
                    $html .= '<td>' . $line->displayLineData('desc') . '</td>';
                    $html .= $tpl;
                    $html .= '</tr>';
                    if ($line->getData('linked_object_name') === 'commande_line') {

                        $commande_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $line->getData('linked_id_object'));
                        if (BimpObject::objectLoaded($commande_line)) {
                            $commande = $commande_line->getParentInstance();
                            if (BimpObject::objectLoaded($commande)) {
                                $product = $line->getProduct();

                                $html .= '<tr class="extra_infos_row">';
                                $html .= '<td colspan="4">';
                                $html .= 'Commande client associée: ' . $commande->getNomUrl(1, 1, 1) . '<br/>';
                                if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                                    $html .= '<div style="vertical-align: top;"><span style="display: inline-block; vertical-align: top; padding-top: 6px">';
                                    $html .= 'Assigner les équipements reçues à cette commande client: </span>';
                                    $html .= BimpInput::renderInput('toggle', 'line_' . $line->id . '_assign_to_commande_client', 0) . '</div>';
                                }
                                $html .= '</td>';
                                $html .= '</tr>';
                            }
                        }
                    }
                }
                $html .= '</tbody>';
                $html .= '</table>';
            }
        }

        return $html;
    }

    public function renderLineQtyInputs($line, $idx, $qty, $max, $total_max, $pu_ht, $tva_tx, $edit_prices = true)
    {
        $html = '';

        $html .= '<td style="width: 220px;">';

        $decimals = $line->getQtyDecimals();

        $options = array(
            'data' => array(
                'data_type' => 'number',
                'decimals'  => $decimals,
                'min'       => 0,
                'max'       => 0
            )
        );

        if ((float) $line->getFullQty() >= 0) {
            $options['data']['max'] = $max;
            $options['data']['total_max_value'] = $total_max;
            $options['data']['total_max_inputs_class'] = 'line_' . $line->id . '_qty_input';
            $options['max_label'] = 1;
            $options['extra_class'] = 'total_max line_' . $line->id . '_qty_input';
        } else {
            $options['data']['min'] = $max;
            $options['data']['total_min_value'] = $total_max;
            $options['data']['total_min_inputs_class'] = 'line_' . $line->id . '_qty_input';
            $options['min_label'] = 1;
            $options['extra_class'] = 'total_min line_' . $line->id . '_qty_input';
        }

        $html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_reception_' . $this->id . '_qty_' . $idx . '_qty', (float) $qty, $options);
        $html .= '</td>';

        $html .= '<td style="width: 120px;">';
        if ($edit_prices) {
            $html .= BimpInput::renderInput('text', 'line_' . $line->id . '_reception_' . $this->id . '_qty_' . $idx . '_pu_ht', $pu_ht, array(
                        'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
                        'data'        => array(
                            'data_type' => 'number',
                            'decimals'  => 6,
                            'min'       => 'none',
                            'max'       => 'none'
                        ),
                        'style'       => 'max-width: 80px!important;'
            ));
        } else {
            $html .= BimpTools::displayMoneyValue($pu_ht);
        }
        $html .= '</td>';

        $html .= '<td style="width: 120px;">';
        if ($edit_prices) {
            $html .= BimpInput::renderInput('text', 'line_' . $line->id . '_reception_' . $this->id . '_qty_' . $idx . '_tva_tx', $tva_tx, array(
                        'addon_right' => BimpRender::renderIcon('fas_percent'),
                        'data'        => array(
                            'data_type' => 'number',
                            'decimals'  => 3,
                            'min'       => 0,
                            'max'       => 100
                        ),
                        'style'       => 'max-width: 80px!important;'
            ));
        } else {
            $html .= BimpTools::displayFloatValue($tva_tx) . '%';
        }
        $html .= '</td>';

        $html .= '<td>';
        $onclick = '$(this).popover(\'hide\'); var $tr = $(this).parent(\'td\').parent(\'tr\');$tr.find(\'input.line_' . $line->id . '_qty_input\').val(0).change();$tr.remove();';
        $html .= BimpRender::renderRowButton('Suppprimer', 'fas_trash-alt', $onclick);
        $html .= '</td>';

        return $html;
    }

    public function renderLineSerialInputs($line, $serial, $pu_ht, $tva_tx, $code_config = '', $product = null)
    {
        $html = '';
        $html .= '<td style="width: 220px" class="serial" data-serial="' . $serial . '">';
        $html .= $serial;

        if ($product && $product->getData('barcode') == $serial) {
            $html .= '<br/>';
            $html .= '<span class="danger">';
            $html .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Le numéro de série ne peut être identique au code-bar du produit';
            $html .= '</span>';
        }

        $isImei = (!preg_match("/[a-zA-Z]/", $serial)) ? true : false;

        if (!$isImei && $code_config && !preg_match('/^.+' . preg_quote($code_config) . '$/', $serial)) {
            $html .= '<br/>';
            $html .= '<span class="danger">';
            $html .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Les 4 derniers caractères ne correspondent pas au code configuration (' . $code_config . ')';
            $html .= '</span>';
        }
        $html .= '</td>';
        $html .= '<td style="width: 120px">';
        $html .= BimpInput::renderInput('text', 'line_' . $line->id . '_reception_' . $this->id . '_serial_' . $serial . '_pu_ht', $pu_ht, array(
                    'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
                    'data'        => array(
                        'data_type' => 'number',
                        'decimals'  => 6,
                        'min'       => 'none',
                        'max'       => 'none'
                    ),
                    'style'       => 'max-width: 80px!important;'
        ));
        $html .= '</td>';

        $html .= '<td style="width: 120px">';
        $html .= BimpInput::renderInput('text', 'line_' . $line->id . '_reception_' . $this->id . '_serial_' . $serial . '_tva_tx', $tva_tx, array(
                    'addon_right' => BimpRender::renderIcon('fas_percent'),
                    'data'        => array(
                        'data_type' => 'number',
                        'decimals'  => 3,
                        'min'       => 0,
                        'max'       => 100
                    ),
                    'style'       => 'max-width: 80px!important;'
        ));
        $html .= '</td>';

        $html .= '<td>';
        $onclick = '$(this).popover(\'hide\');$(this).parent(\'td\').parent(\'tr\').remove();';
        $html .= BimpRender::renderRowButton('Suppprimer', 'fas_trash-alt', $onclick);
        $html .= '</td>';

        return $html;
    }

    public function renderLineReturnEquipmentInputs($line, Equipment $equipment, $pu_ht, $tva_tx)
    {
        $html = '';

        $html .= '<td style="width: 220px" class="equipment" data-id_equipment="' . $equipment->id . '">' . $equipment->getNomUrl(1, 1, 1, 'default') . '</td>';
        $html .= '<td style="width: 120px">';
        $html .= BimpInput::renderInput('text', 'line_' . $line->id . '_reception_' . $this->id . '_return_equipment_' . $equipment->id . '_pu_ht', $pu_ht, array(
                    'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
                    'data'        => array(
                        'data_type' => 'number',
                        'decimals'  => 6,
                        'min'       => 'none',
                        'max'       => 'none'
                    ),
                    'style'       => 'max-width: 80px!important;'
        ));
        $html .= '</td>';

        $html .= '<td style="width: 120px">';
        $html .= BimpInput::renderInput('text', 'line_' . $line->id . '_reception_' . $this->id . '_return_equipment_' . $equipment->id . '_tva_tx', $tva_tx, array(
                    'addon_right' => BimpRender::renderIcon('fas_percent'),
                    'data'        => array(
                        'data_type' => 'number',
                        'decimals'  => 3,
                        'min'       => 0,
                        'max'       => 100
                    ),
                    'style'       => 'max-width: 80px!important;'
        ));
        $html .= '</td>';

        $html .= '<td>';
        $onclick = '$(this).popover(\'hide\');$(this).parent(\'td\').parent(\'tr\').remove();';
        $html .= BimpRender::renderRowButton('Suppprimer', 'fas_trash-alt', $onclick);
        $html .= '</td>';

        return $html;
    }

    public function renderDetailsView()
    {
        $html = '';

        $commandeFourn = $this->getParentInstance();

        $edit = ($this->getData('status') === self::BLCFR_BROUILLON);

        if (!BimpObject::objectLoaded($commandeFourn)) {
            return BimpRender::renderAlerts('ID de la commande fournisseur absent');
        }

        $lines = $commandeFourn->getChildrenObjects('lines');

        if (empty($lines)) {
            return BimpRender::renderAlerts('Aucune ligne enregistrée pour cette commande fournisseur');
        }

        $html .= '<div class="reception_details" data-id_reception="' . $this->id . '" data-edit="' . $edit . '">';

        if ($edit) {
            $html .= '<div class="buttonsContainer align-right">';
            $html .= '<div style="text-align: left; display: inline-block">';
            $html .= '<span class="btn btn-default" onclick="setAllReceptionLinesToMax($(this))">';
            $html .= 'Réceptionner toutes les unités' . BimpRender::renderIcon('angle-double-right', 'iconRight');
            $html .= '</span>';
            $html .= '<div class="small" style="color: #787878;">Produits non sérialisés uniquement</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th style="min-width: 220px">Ligne</th>';
        $html .= '<th style="width: 220px">Qtés / ' . ($edit ? 'N° de série' : 'Equipements') . '</th>';
        $html .= '<th style="width: 120px">Prix unitaire HT (Remises incluses)</th>';
        $html .= '<th style="width: 120px">Tx TVA</th>';
        $html .= '<th></th>';

        $colspan = 4;

        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        $has_lines = false;

        foreach ($lines as $line) {
            $isReturn = ((float) $line->getFullQty() < 0);

            $max = (float) $line->getReceptionAvailableQty($this->id);
            if (!$max) {
                continue;
            }

            $line_pu_ht = (float) $line->getUnitPriceHTWithRemises();

            $reception_data = $line->getReceptionData($this->id);

            if (!$edit && !(float) $reception_data['qty']) {
                continue;
            }

            $has_lines = true;
            $isSerialisable = false;

            $product = $line->getProduct();
            if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                $isSerialisable = true;
            }

            $commande_client_line = null;
            if (!$isReturn && $line->getData('linked_object_name') === 'commande_line') {
                $commande_client_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $line->getData('linked_id_object'));
            }

            $html .= '<tr class="line_row" data-id_line="' . $line->id . '" data-line_position="' . $line->getData('position') . '" data-serialisable="' . (int) $isSerialisable . '" data-is_return="' . (int) $isReturn . '">';

            // Desc: 
            $html .= '<td>';
            $html .= $line->displayLineData('desc');
//            if (BimpObject::objectLoaded($commande_client_line)) {
//                $commandeClient = $commande_client_line->getParentInstance();
//                if (BimpObject::objectLoaded($commandeClient)) {
//                    $html .= '<br/><br/>';
//                    $html .= '<strong>Commande client d\'origine: </strong>';
//                    $html .= $commandeClient->getNomUrl(1, 1, 1, 'full') . '&nbsp;&nbsp;(Ligne n°' . $commande_client_line->getData('position') . ')';
//                }
//            }
            $html .= '</td>';

            if ($edit) {
                if ($isSerialisable) {
                    $html .= '<td colspan="' . $colspan . '">';

                    if (!$isReturn) {
                        // *** Edition / ajout des nouveaux numéros de série: ***

                        $code_config = '';

                        if (preg_match('/^APP\-.+$/', $product->getRef())) {
                            $code_config = (string) $product->getData('code_config');
                        }

                        if (isset($reception_data['serials']) && !empty($reception_data['serials'])) {
                            $nSerialsKo = 0;
                            if ($code_config) {
                                foreach ($reception_data['serials'] as $serial_data) {
                                    if (isset($serial_data['serial']) && (string) $serial_data['serial'] && preg_match("/[a-zA-Z]/", $serial_data['serial']) && !preg_match('/^.+' . preg_quote($code_config) . '$/', $serial_data['serial'])) {
                                        $nSerialsKo++;
                                    }
                                }
                            }

                            // Liste des N° de série déjà ajoutés: 
                            $html .= '<span class="bold">' . count($reception_data['serials']) . ' numéro(s) de série ajouté(s)</span>';
                            if ($nSerialsKo > 0) {
                                $html .= BimpRender::renderAlerts('Attention: ' . $nSerialsKo . ' numéro(s) de série ne correspondent pas au code configuration du produit (' . $code_config . ')', 'warning');
                            }
                            $html .= '<table class="bimp_list_table">';
                            $html .= '<tbody>';

                            foreach ($reception_data['serials'] as $serial_data) {
                                if (isset($serial_data['serial']) && (string) $serial_data['serial']) {
                                    $pu_ht = (isset($serial_data['pu_ht']) ? (float) $serial_data['pu_ht'] : $line_pu_ht);
                                    $tva_tx = (isset($serial_data['tva_tx']) ? (float) $serial_data['tva_tx'] : (float) $line->tva_tx);
                                    $html .= '<tr class="line_' . $line->id . '_serial_data">';
                                    $html .= $this->renderLineSerialInputs($line, $serial_data['serial'], $pu_ht, $tva_tx, $code_config, $product);
                                    $html .= '</tr>';
                                }
                            }

                            $html .= '</tbody>';
                            $html .= '</table>';

                            $max -= count($reception_data['serials']);
                        }

                        // Nouveaux n° de séries à ajouter: 
                        $html .= '<div style="margin-top: 12px"><span class="bold">N° de série à ajouter: </span></div>';
                        $html .= BimpInput::renderInput('textarea', 'line_' . $line->id . '_reception_' . $this->id . '_new_serials', '', array(
                                    'auto_expand'      => true,
                                    'tab_key_as_enter' => true
                        ));
                        $html .= '<p class="inputHelp" style="display: block;">Séparateurs possibles: sauts de ligne, espaces, virgules ou points-virgules.<br/>';
                        $html .= 'Max: ' . $max . ' numéro' . ($max > 1 ? 's' : '') . ' de série.';
                        $html .= '</p>';

                        $html .= '<div style="margin-top: 12px">';
                        $html .= '<table class="bimp_list_table">';
                        $html .= '<tbody>';
                        $html .= '<tr>';
                        $html .= '<td style="width: 220px"><span class="bold">Nouveaux n° de série: </span></td>';
                        $html .= '<td style="width: 120px">';
                        $html .= BimpInput::renderInput('text', 'line_' . $line->id . '_reception_' . $this->id . '_new_serials_pu_ht', $line_pu_ht, array(
                                    'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
                                    'data'        => array(
                                        'data_type' => 'number',
                                        'decimals'  => 6,
                                        'min'       => 'none',
                                        'max'       => 'none'
                                    ),
                                    'style'       => 'max-width: 80px!important;'
                        ));
                        $html .= '</td>';

                        $html .= '<td style="width: 120px">';
                        $html .= BimpInput::renderInput('text', 'line_' . $line->id . '_reception_' . $this->id . '_new_serials_tva_tx', $line->tva_tx, array(
                                    'addon_right' => BimpRender::renderIcon('fas_percent'),
                                    'data'        => array(
                                        'data_type' => 'number',
                                        'decimals'  => 3,
                                        'min'       => 0,
                                        'max'       => 100
                                    ),
                                    'style'       => 'max-width: 80px!important;'
                        ));
                        $html .= '</td>';
                        $html .= '<td></td>';
                        $html .= '</tr>';
                        $html .= '</tbody>';
                        $html .= '</table>';
                        $html .= '</div>';

                        if (BimpObject::objectLoaded($commande_client_line)) {
                            $assign = (int) (isset($reception_data['assign_to_commande_client']) ? $reception_data['assign_to_commande_client'] : $this->getData('assign_lines_to_commandes_client'));
                            $html .= '<div style="margin-top: 12px">';
                            $html .= '<span class="bold">Assigner les équipements reçus à la ligne de commande client d\'origine: </span><br/>';
                            $html .= BimpInput::renderInput('toggle', 'line_' . $line->id . '_reception_' . $this->id . '_assign_to_commande_client', $assign);
                            $html .= '</div>';
                        }
                    } else {
                        // *** Edition / Ajout des équipements retournés: ***
                        if (isset($reception_data['return_equipments']) && !empty($reception_data['return_equipments'])) {

                            // Liste des équipements déjà ajoutés: 
                            $id_entrepot = (int) $commandeFourn->getData('entrepot');

                            $html .= '<span class="bold">' . count($reception_data['return_equipments']) . ' équipement(s) à retourner sélectionné(s)</span>';
                            $html .= '<table class="bimp_list_table">';
                            $html .= '<tbody>';

                            foreach ($reception_data['return_equipments'] as $id_equipment => $equipment_data) {
                                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                $eq_errors = array();
                                if (!BimpObject::objectLoaded($equipment)) {
                                    $eq_errors[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe plus';
                                } else {
                                    $equipment->isAvailable($id_entrepot, $eq_errors, array(
                                        'id_reception' => (int) $this->id
                                            ), array('sav'));

                                    if (count($eq_errors)) {
                                        $html .= '<tr>';
                                        $html .= '<td colspan="4">';
                                        $html .= BimpRender::renderAlerts($eq_errors);
                                        $html .= '</td>';
                                        $html .= '</tr>';
                                    } else {
                                        $pu_ht = (isset($equipment_data['pu_ht']) ? (float) $equipment_data['pu_ht'] : $line_pu_ht);
                                        $tva_tx = (isset($equipment_data['tva_tx']) ? (float) $equipment_data['tva_tx'] : (float) $line->tva_tx);
                                        $html .= '<tr class="line_' . $line->id . '_return_equipment_data" data-id_equipment="' . $id_equipment . '">';
                                        $html .= $this->renderLineReturnEquipmentInputs($line, $equipment, $pu_ht, $tva_tx);
                                        $html .= '</tr>';
                                        $max++;
                                    }
                                }
                            }

                            $html .= '</tbody>';
                            $html .= '</table>';
                        }

                        // Nouveaux Equipements à ajouter aux retours: 
                        $html .= '<div style="margin-top: 12px"><span class="bold">Equipements à retourner: </span></div>';

                        $input_name = 'line_' . $line->id . '_reception_' . $this->id . '_new_return_equipments';
                        $equipments_to_return = $line->getReturnableEquipmentsArray();
                        $input_content = BimpInput::renderInput('select', $input_name . '_add_value', array(), array(
                                    'options' => $equipments_to_return
                        ));
                        $content = BimpInput::renderMultipleValuesInput($this, $input_name, $input_content, array(), '', 0, 0, 1, abs($max), array(), true);
                        $html .= BimpInput::renderInputContainer($input_name, '', $content, '', 0, 1, '', array(
                                    'values_field' => $input_name
                        ));

                        $html .= '<div style="margin-top: 12px">';
                        $html .= '<table class="bimp_list_table">';
                        $html .= '<tbody>';
                        $html .= '<tr>';
                        $html .= '<td style="width: 220px"><span class="bold">Nouveaux équipements à retourner: </span></td>';
                        $html .= '<td style="width: 120px">';
                        $html .= BimpInput::renderInput('text', 'line_' . $line->id . '_reception_' . $this->id . '_new_return_equipments_pu_ht', $line_pu_ht, array(
                                    'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
                                    'data'        => array(
                                        'data_type' => 'number',
                                        'decimals'  => 6,
                                        'min'       => 'none',
                                        'max'       => 'none'
                                    ),
                                    'style'       => 'max-width: 80px!important;'
                        ));
                        $html .= '</td>';

                        $html .= '<td style="width: 120px">';
                        $html .= BimpInput::renderInput('text', 'line_' . $line->id . '_reception_' . $this->id . '_new_return_equipments_tva_tx', $line->tva_tx, array(
                                    'addon_right' => BimpRender::renderIcon('fas_percent'),
                                    'data'        => array(
                                        'data_type' => 'number',
                                        'decimals'  => 3,
                                        'min'       => 0,
                                        'max'       => 100
                                    ),
                                    'style'       => 'max-width: 80px!important;'
                        ));
                        $html .= '</td>';
                        $html .= '<td></td>';
                        $html .= '</tr>';
                        $html .= '</tbody>';
                        $html .= '</table>';
                        $html .= '</div>';
                    }

                    $html .= '</td>';
                } else {
                    $html .= '<td colspan="' . $colspan . '">';
                    $html .= '<div class="line_' . $line->id . '_qty_input_container">';
                    $html .= '<table class="bimp_list_table">';
                    $html .= '<tbody class="line_' . $line->id . '_qty_rows">';

                    if (!isset($reception_data['qties']) || empty($reception_data['qties'])) {
                        $reception_data['qties'] = array(
                            0 => array()
                        );
                    }

                    $total_max = $max;
                    $max -= (float) $reception_data['qty'];

                    $html .= '<tr class="line_' . $line->id . '_qty_row_tpl line_qty_row_tpl" style="display: none">';
                    $html .= $this->renderLineQtyInputs($line, 'qtyidx', 0, $max, $total_max, $line_pu_ht, (float) $line->tva_tx);
                    $html .= '</tr>';

                    $i = 1;
                    foreach ($reception_data['qties'] as $qty_data) {
                        $html .= '<tr class="line_' . $line->id . '_qty_row line_qty_row" data-qty_idx="' . $i . '">';
                        $pu_ht = (isset($qty_data['pu_ht']) ? (float) $qty_data['pu_ht'] : $line_pu_ht);
                        $tva_tx = (isset($qty_data['tva_tx']) ? (float) $qty_data['tva_tx'] : (float) $line->tva_tx);
                        $html .= $this->renderLineQtyInputs($line, $i, $qty_data['qty'], $max + (float) $qty_data['qty'], $total_max, $pu_ht, $tva_tx);
                        $html .= '</tr>';
                    }

                    $html .= '</tbody>';
                    $html .= '</table>';
                    $onclick = 'addCommandeFournReceptionLineQtyRow($(this), ' . $line->id . ');';
                    $html .= '<div style="text-align: right; margin: 5px 0">';
                    $html .= '<span class="btn btn-default btn-small" onclick="' . $onclick . '">';
                    $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter';
                    $html .= '</span>';
                    $html .= '</div>';
                    $html .= '</div>';

                    if (!$isReturn && BimpObject::objectLoaded($commande_client_line)) {
                        $assign = (int) (isset($reception_data['assign_to_commande_client']) ? $reception_data['assign_to_commande_client'] : $this->getData('assign_lines_to_commandes_client'));
                        $html .= '<div style="margin-top: 12px">';
                        $html .= 'Assigner les unités reçues à la ligne de commande client d\'origine: <br/>';
                        $html .= BimpInput::renderInput('toggle', 'line_' . $line->id . '_reception_' . $this->id . '_assign_to_commande_client', $assign);
                        $html .= '</div>';
                    }

                    $html .= '</td>';
                }
            } else {
                $html .= '<td colspan="' . $colspan . '">';
                $html .= '<table class="bimp_list_table">';
                $html .= '<tbody>';

                if ($isSerialisable) {
                    if (!$isReturn) {
                        // *** Affichage équipements reçus: ***
                        $html .= '<tr>';
                        $html .= '<td colspan="4">';
                        if (is_array($reception_data['equipments']) && count($reception_data['equipments'])) {
                            $html .= '<span class="bold">' . count($reception_data['equipments']) . ' équipements ajouté(s)</span>';
                        } else {
                            $html .= BimpRender::renderAlerts('Aucun équipement ajouté', 'info');
                        }
                        $html .= '</td>';
                        $html .= '</tr>';
                        if (isset($reception_data['equipments']) && is_array($reception_data['equipments'])) {
                            foreach ($reception_data['equipments'] as $id_equipment => $equipment_data) {
                                $html .= '<tr>';
                                $html .= '<td style="width: 220px">';
                                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                if (BimpObject::objectLoaded($equipment)) {
                                    $html .= $equipment->getNomUrl(1, 1, 1);
                                } else {
                                    $html .= BimpRender::renderAlerts('L\'équipement #' . $id_equipment . ' n\'existe plus');
                                }
                                $html .= '</td>';

                                $html .= '<td style="width: 120px">' . BimpTools::displayMoneyValue((float) isset($equipment_data['pu_ht']) ? $equipment_data['pu_ht'] : $line_pu_ht) . '</td>';
                                $html .= '<td style="width: 120px">' . BimpTools::displayFloatValue((float) isset($equipment_data['tva_tx']) ? $equipment_data['tva_tx'] : $line->tva_tx, 3) . '%</td>';
                                $html .= '<td></td>';
                                $html .= '</tr>';
                            }
                        }
                    } else {
                        // *** Affichage équipements retournés: ***
                        $html .= '<tr>';
                        $html .= '<td colspan="4">';
                        if (count($reception_data['return_equipments'])) {
                            $html .= '<tr><td colspan="4"><span class="danger">' . count($reception_data['return_equipments']) . ' équipements retournés</span></td></tr>';
                        } else {
                            $html .= BimpRender::renderAlerts('Aucun équipement à retourner ajouté', 'info');
                        }
                        $html .= '</td>';
                        $html .= '</tr>';
                        foreach ($reception_data['return_equipments'] as $id_equipment => $equipment_data) {
                            $html .= '<tr>';
                            $html .= '<td style="width: 220px">';
                            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                            if (BimpObject::objectLoaded($equipment)) {
                                $html .= $equipment->getNomUrl(1, 1, 1);
                            } else {
                                $html .= BimpRender::renderAlerts('L\'équipement #' . $id_equipment . ' n\'existe plus');
                            }
                            $html .= '</td>';

                            $html .= '<td style="width: 120px">' . BimpTools::displayMoneyValue((float) isset($equipment_data['pu_ht']) ? $equipment_data['pu_ht'] : $line_pu_ht) . '</td>';
                            $html .= '<td style="width: 120px">' . BimpTools::displayFloatValue((float) isset($equipment_data['tva_tx']) ? $equipment_data['tva_tx'] : $line->tva_tx, 3) . '%</td>';
                            $html .= '<td></td>';
                            $html .= '</tr>';
                        }
                    }
                } else {
                    foreach ($reception_data['qties'] as $qty_data) {
                        $html .= '<tr>';
                        $html .= '<td style="width: 220px">' . $qty_data['qty'] . '</td>';
                        $html .= '<td style="width: 120px">' . BimpTools::displayMoneyValue((float) isset($qty_data['pu_ht']) ? $qty_data['pu_ht'] : $line_pu_ht) . '</td>';
                        $html .= '<td style="width: 120px">' . BimpTools::displayFloatValue((float) isset($qty_data['tva_tx']) ? $qty_data['tva_tx'] : $line->tva_tx, 3) . '%</td>';
                        $html .= '<td></td>';
                        $html .= '</tr>';
                    }

                    if (count($reception_data['qties']) > 1) {
                        $html .= '<tr>';
                        $html .= '<td colspan="' . $colspan . '"><strong>Total: </strong>' . $reception_data['qty'] . '</td>';
                        $html .= '</tr>';
                    }
                }

                $html .= '</tbody>';
                $html .= '</table>';
                $html .= '</td>';
            }
            $html .= '</tr>';
        }

        if (!$has_lines) {
            $html .= '<tr>';
            $html .= '<td colspan="' . ($colspan + 1) . '" style="text-align: center">';
            if ($edit) {
                $html .= BimpRender::renderAlerts('Il ne reste aucune unité à receptionner', 'warning');
            } else {
                $html .= BimpRender::renderAlerts('Aucune unité ajouté à cette réception', 'warning');
            }
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    public function renderSplitDetailForm()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de la réception absent');
        }

        $commande = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            return BimpRender::renderAlerts('ID de la commande fournisseur absent');
        }

        $html = '';

        $lines = $commande->getLines('not_text');

        $hasLines = false;

        $html .= '<table class="bimp_list_table">';

        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Ligne</th>';
        $html .= '<th>Qtés / Equipements </th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';
        foreach ($lines as $line) {
            $isSerialisable = $line->isProductSerialisable();
            $isReturn = ($line->getFullQty() < 0);
            $reception_data = $line->getReceptionData((int) $this->id);

            if (!(float) $reception_data['qty']) {
                continue;
            }

            $hasLines = true;

            $html .= '<tr class="line_row" data-id_line="' . $line->id . '" data-serialisable="' . $isSerialisable . '">';
            $html .= '<td>';
            $html .= $line->displayLineData('desc_light');
            $html .= '</td>';

            $html .= '<td>';
            if ($isSerialisable) {
                $items = array();
                $values = array();
                if (!$isReturn) {
                    if ((int) $this->getData('status') === self::BLCFR_RECEPTIONNEE) {
//                        $suffixe = 'equipments';

                        if (isset($reception_data['equipments'])) {
                            foreach ($reception_data['equipments'] as $id_equipment => $equipment_data) {
                                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                if (BimpObject::objectLoaded($equipment)) {
                                    $items[(int) $id_equipment] = $equipment->getRef();
                                    $values[] = (int) $id_equipment;
                                }
                            }
                        }
                    } else {
//                        $suffixe = 'serials';

                        if (isset($reception_data['serials'])) {
                            foreach ($reception_data['serials'] as $serial_data) {
                                $items[$serial_data['serial']] = $serial_data['serial'];
                                $values[] = $serial_data['serial'];
                            }
                        }
                    }
                } else {
//                    $suffixe = 'return_equipments';

                    if (isset($reception_data['return_equipments'])) {
                        foreach ($reception_data['return_equipments'] as $id_equipment => $equipment_data) {
                            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                            if (BimpObject::objectLoaded($equipment)) {
                                $items[(int) $id_equipment] = $equipment->getRef();
                                $values[] = (int) $id_equipment;
                            }
                        }
                    }
                }

                $html .= BimpInput::renderInput('check_list', 'line_' . $line->id . '_items', $values, array(
                            'items' => $items
                ));
            } else {
                $html .= '<table>';
                $html .= '<tbody>';
                if (isset($reception_data['qties'])) {
                    foreach ($reception_data['qties'] as $idx => $qty_data) {
                        $html .= '<tr class="line_' . $line->id . '_qty_row" data-qty_idx=' . $idx . '>';
                        $html .= $this->renderLineQtyInputs($line, $idx, (float) $qty_data['qty'], (float) $qty_data['qty'], (float) $qty_data['qty'], (float) $qty_data['pu_ht'], (float) $qty_data['tva_tx'], false);
                        $html .= '</tr>';
                    }
                }
                $html .= '</tbody>';
                $html .= '</table>';
            }
            $html .= '</td>';

            $html .= '</tr>';
        }

        if (!$hasLines) {
            $html .= '<tr>';
            $html .= '<td colspan="2">';
            $html .= BimpRender::renderAlerts('Aucune ligne ajoutée à cette réception', 'warning');
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    // Traitements

    public function processLinesFormData($data, &$errors)
    {
        $lines_data = array();

        if (isset($data['lines']) && is_array($data['lines']) && !empty($data['lines'])) {
            foreach ($data['lines'] as $line_data) {
                if (isset($line_data['id_line']) && (int) $line_data['id_line']) {
                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', (int) $line_data['id_line']);

                    if (!BimpObject::objectLoaded($line)) {
                        $errors[] = 'La ligne de commande fournisseur d\'ID ' . $line_data['id_line'] . ' n\'existe pas';
                        continue;
                    }

                    $qty = 0;
                    $qties = array();
                    $serials = array();
                    $return_equipments = array();

                    $isSerialiable = $line->isProductSerialisable();
                    $isReturn = ((float) $line->getFullQty() < 0);

                    $line_pu_ht = (float) $line->getUnitPriceHTWithRemises();

                    if ($isSerialiable) {
                        if (!$isReturn) {
                            if (isset($line_data['serials']) && !empty($line_data['serials'])) {
                                foreach ($line_data['serials'] as $serial_data) {
                                    if (isset($serial_data['serial']) && (string) $serial_data['serial']) {
                                        $serials[] = array(
                                            'serial' => $serial_data['serial'],
                                            'pu_ht'  => (float) (isset($serial_data['pu_ht']) ? $serial_data['pu_ht'] : $line_pu_ht),
                                            'tva_tx' => (float) (isset($serial_data['tva_tx']) ? $serial_data['tva_tx'] : $line->tva_tx),
                                        );
                                        $qty++;
                                    }
                                }
                            }

                            if (isset($line_data['new_serials']) && (string) $line_data['new_serials']) {
                                $new_serials = $line->explodeSerials($line_data['new_serials']);
                                $pu_ht = (isset($line_data['new_serials_pu_ht']) ? (float) $line_data['new_serials_pu_ht'] : $line_pu_ht);
                                $tva_tx = (isset($line_data['new_serials_tva_tx']) ? (float) $line_data['new_serials_tva_tx'] : (float) $line->tva_tx);

                                if (count($new_serials)) {
                                    foreach ($new_serials as $new_serial) {
                                        $serials[] = array(
                                            'serial' => $new_serial,
                                            'pu_ht'  => (float) $pu_ht,
                                            'tva_tx' => (float) $tva_tx,
                                        );
                                        $qty++;
                                    }
                                }
                            }
                        } else {
                            if (isset($line_data['return_equipments']) && !empty($line_data['return_equipments'])) {
                                foreach ($line_data['return_equipments'] as $equipment_data) {
                                    if (isset($equipment_data['id_equipment']) && (int) $equipment_data['id_equipment']) {
                                        if (array_key_exists((int) $equipment_data['id_equipment'], $return_equipments)) {
                                            continue;
                                        }
                                        $return_equipments[(int) $equipment_data['id_equipment']] = array(
                                            'pu_ht'  => (float) (isset($equipment_data['pu_ht']) ? $equipment_data['pu_ht'] : $line_pu_ht),
                                            'tva_tx' => (float) (isset($equipment_data['tva_tx']) ? $equipment_data['tva_tx'] : $line->tva_tx),
                                        );
                                        $qty--;
                                    }
                                }
                            }

                            if (isset($line_data['new_return_equipments']) && is_array($line_data['new_return_equipments']) && !empty($line_data['new_return_equipments'])) {
                                $pu_ht = (isset($line_data['new_return_equipments_pu_ht']) ? (float) $line_data['new_return_equipments_pu_ht'] : $line_pu_ht);
                                $tva_tx = (isset($line_data['new_return_equipments_tva_tx']) ? (float) $line_data['new_return_equipments_tva_tx'] : (float) $line->tva_tx);

                                foreach ($line_data['new_return_equipments'] as $id_equipment) {
                                    if (array_key_exists((int) $id_equipment, $return_equipments)) {
                                        continue;
                                    }
                                    $return_equipments[(int) $id_equipment] = array(
                                        'pu_ht'  => (float) $pu_ht,
                                        'tva_tx' => (float) $tva_tx,
                                    );
                                    $qty--;
                                }
                            }
                        }
                    } else {
                        if (isset($line_data['qties']) && !empty($line_data['qties'])) {
                            foreach ($line_data['qties'] as $qty_data) {
                                if (isset($qty_data['qty']) && (float) $qty_data['qty']) {
                                    $qties[] = array(
                                        'qty'    => (float) $qty_data['qty'],
                                        'pu_ht'  => (float) (isset($qty_data['pu_ht']) ? $qty_data['pu_ht'] : $line_pu_ht),
                                        'tva_tx' => (float) (isset($qty_data['tva_tx']) ? $qty_data['tva_tx'] : $line->tva_tx),
                                    );
                                    $qty += (float) $qty_data['qty'];
                                }
                            }
                        }
                    }
                    $lines_data[(int) $line_data['id_line']] = array(
                        'qty'                       => $qty,
                        'qties'                     => $qties,
                        'serials'                   => $serials,
                        'return_equipments'         => $return_equipments,
                        'assign_to_commande_client' => (isset($line_data['assign_to_commande_client']) ? (int) $line_data['assign_to_commande_client'] : 0),
                    );
                }
            }
        }

        return $lines_data;
    }

    public function checkLinesData($lines_data, &$code_config_errors = null, $force_equipments_attribution = false)
    {
        $errors = array();

        foreach ($lines_data as $id_line => $line_data) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', (int) $id_line);
            if (!BimpObject::objectLoaded($line)) {
                $errors[] = 'La ligne de commande fournisseur d\'ID ' . $id_line . ' n\'existe pas';
            } else {
                $code_config_serials_errors = array();
                $line_errors = $line->checkReceptionData((int) $this->id, $line_data, $code_config_serials_errors, $force_equipments_attribution);
                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
                } elseif (is_array($code_config_errors) && count($code_config_serials_errors)) {
                    $product = $line->getProduct();
                    if (BimpObject::objectLoaded($product)) {
                        $code_config = (string) $product->getData('code_config');
                        if ($code_config) {
                            $title = 'Ligne n°' . $line->getData('position') . ' (produit ' . $product->getRef() . '): ' . count($code_config_serials_errors);
                            $title .= ' numéro(s) de série ne correspondent pas au code configuration du produit (' . $code_config . ')';
                            $code_config_errors[] = BimpTools::getMsgFromArray($code_config_serials_errors, $title);
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function saveLinesData($lines_data, &$warnings = array(), $force_equipments_attribution = false)
    {
        $codes = null;
        $errors = $this->checkLinesData($lines_data, $codes, $force_equipments_attribution);

        if (count($errors)) {
            return $errors;
        }

        foreach ($lines_data as $id_line => $line_data) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', (int) $id_line);
            if (BimpObject::objectLoaded($line)) {
                $line_warnings = array();
                $line_errors = $line->setReceptionData((int) $this->id, $line_data, false, $line_warnings, $force_equipments_attribution);

                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
                }

                if (count($line_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Ligne n°' . $line->getData('position'));
                }
            }
        }

        $this->onLinesChange();

        return $errors;
    }

    public function validateReception($date_received = null, $check_data = true, $stock_out = false, $force_equipments_attribution = false, $skip_equipments_place = false)
    {
        set_time_limit(1200);
        ignore_user_abort(true);

        $errors = array();
        if (BimpCore::getConf('use_bds_for_receptions', null, 'bimpcommercial')) {
            $errors[] = 'La validation directe des réceptions n\'est pas possible selon la configuration actuelle de l\'ERP';
            return $errors;
        }

        if (!(int) $this->getData('status') === self::BLCFR_BROUILLON) {
            $errors[] = 'La réception ne peut pas être validée car elle n\'a pas le statut "brouillon"';
            return $errors;
        }

        $commande = $this->getParentInstance();
        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande fournisseur absent';
            return $errors;
        }

        BimpObject::loadClass('bimpcommercial', 'ObjectLine');

        $lines = $commande->getChildrenObjects('lines', array(
            'type' => array(
                'in' => array(ObjectLine::LINE_PRODUCT, ObjectLine::LINE_FREE)
            )
        ));

        if ($stock_out) {
            foreach ($lines as $line) {
                $line_prod = $line->getProduct();

                if (BimpObject::objectLoaded($line_prod) && $line_prod->isTypeProduct()) {
                    $reception_data = $line->getReceptionData((int) $this->id);

                    if (isset($reception_data['assign_to_commande_client']) && (int) $reception_data['assign_to_commande_client']) {
                        if (isset($reception_data['qty']) && (float) $reception_data['qty'] > 0 && $line->getData('linked_object_name') === 'commande_line') {
                            $errors[] = 'Cette réception ne peut pas être validée avec déplacement vers stock boutique car il y a au moins une ligne associée à une commande client';
                            return $errors;
                        }
                    }
                }
            }
        }
        $lines_done = array();

        foreach ($lines as $line) {
            $line_errors = $line->validateReception((int) $this->id, $check_data, $stock_out, $force_equipments_attribution, $skip_equipments_place);

            if (count($line_errors)) {
                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
            } else {
                $lines_done[] = $line;
            }
        }

        if (count($errors)) {
            BimpCore::addlog('Echec validation BR', Bimp_Log::BIMP_LOG_ALERTE, 'logistique', $this, array(
                'Commande Fournisseur' => $commande->getRef() . ' - #' . $commande->id,
                'Erreurs'              => BimpTools::getMsgFromArray($errors)
            ));
            foreach ($lines_done as $line) {
                $cancel_errors = $line->cancelReceptionValidation((int) $this->id);

                if (count($cancel_errors)) {
                    BimpCore::addlog('Erreurs suite à l\'annulation automatique de la validation d\'un BR', Bimp_Log::BIMP_LOG_ERREUR, 'logistique', $this, array(
                        'Commande Fournisseur' => $commande->getRef() . ' - #' . $commande->id,
                        'Ligne'                => 'N°' . $line->getData('position') . ' - #' . $line->id,
                        'Erreurs'              => BimpTools::getMsgFromArray($cancel_errors)
                    ));
                }
            }
        } else {
            if (is_null($date_received) || !(string) $date_received) {
                $date_received = date('Y-m-d');
            }
            $this->set('status', self::BLCFR_RECEPTIONNEE);
            $this->set('date_received', $date_received);
            $this->set('stock_out', (int) $stock_out);
            $up_warnings = array();
            $up_errors = $this->update($up_warnings, true);
            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour du statut de la réception');
            }

            if (count($up_warnings)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Erreurs lors de la mise à jour du statut');
            }
        }

        $commande->checkReceptionStatus();

        return $errors;
    }

    public function cancelReception(&$warnings = array())
    {
        set_time_limit(1200);
        ignore_user_abort(true);

        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la réception absent';
            return $errors;
        }

        if (!$this->isActionAllowed('cancelReception', $errors)) {
            return $errors;
        }

        $commande = $this->getParentInstance();
        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande fournisseur absent';
            return $errors;
        }

        $lines_errors = array();
        if (!$this->isCancellable($lines_errors)) {
            $errors[] = BimpTools::getMsgFromArray($lines_errors, 'La réception de certaines lignes ne peut pas être annulée');
            return $errors;
        }

        BimpObject::loadClass('bimpcommercial', 'ObjectLine');
        $lines = $commande->getChildrenObjects('lines', array(
            'type' => array(
                'in' => array(ObjectLine::LINE_PRODUCT, ObjectLine::LINE_FREE)
            )
        ));

        $stock_out = (int) $this->getData('stock_out');

        foreach ($lines as $line) {
            $line_warnings = array();
            $line_errors = $line->cancelReceptionValidation((int) $this->id, $line_warnings, $stock_out);
            if (count($line_errors)) {
                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
            }

            if (count($line_warnings)) {
                $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Ligne n°' . $line->getData('position'));
            }
        }

        if (!count($errors)) {
            $this->set('status', self::BLCFR_BROUILLON);
            $this->set('validation_status', 0);
            $this->set('stock_out', 0);
            $errors = BimpTools::merge_array($errors, $this->update());
        }

        $this->onLinesChange();
        $commande->checkReceptionStatus();

        return $errors;
    }

    public function onLinesChange()
    {
        global $user;

        $errors = array();
        if ($this->isLoaded()) {
            $total_ht = $this->getTotalHT();
            $total_ttc = $this->getTotalTTC();

            $update = false;

            if ((float) round($this->getInitData('total_ht'), 5) !== (float) round($total_ht, 5)) {
                $this->set('total_ht', $total_ht);
                $update = true;
            }

            if ((float) round($this->getInitData('total_ttc'), 5) !== (float) round($total_ttc, 5)) {
                $this->set('total_ttc', $total_ttc);
                $update = true;
            }

            if ($update) {
                $warnings = array();
                $errors = $this->update($warnings, true);
            }
        } else {
            $errors[] = 'ID de l\'expédition absent';
        }

        return $errors;
    }

    // Actions:

    public function actionSaveLinesData($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Mise à jour de la réception effectuée avec succès';

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la réception absent';
        } elseif (!isset($data['lines']) || empty($data['lines'])) {
            $errors[] = 'Aucune donnée reçue';
        } else {
            $lines_data = $this->processLinesFormData($data, $errors);

            if (!count($errors)) {
                $errors = $this->saveLinesData($lines_data, $warnings);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionValidateReception($data, &$success)
    {
        $errors = array();
        $warnings = array();

        $success = 'Validation de la réception effectuée avec succès';

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la réception absent';
        } else {
            $lines_data = $this->processLinesFormData($data, $errors);

            if (!count($errors)) {
                $force_equipments_attribution = (int) BimpTools::getArrayValueFromPath($data, 'force_equipments_attribution', 0);
                $skip_equipments_place = (int) BimpTools::getArrayValueFromPath($data, 'skip_equipments_place', 0);
                $codes_config_errors = array();
                $errors = $this->checkLinesData($lines_data, $codes_config_errors, $force_equipments_attribution);

                if (!count($errors) && count($codes_config_errors) && (!isset($data['force_validation']) || !(int) $data['force_validation'])) {
                    $data['force_validation'] = 1;
                    $errors = $this->saveLinesData($lines_data, $warnings, $force_equipments_attribution);
                    $onclick = $this->getJsActionOnclick('validateReception', $data, array(
                        'success_callback' => 'function() {bimpModal.clearCurrentContent();}'
                    ));

                    $msg = BimpTools::getMsgFromArray($codes_config_errors);
                    $msg .= '<br/><span class="btn btn-default" onclick="' . $onclick . '">';
                    $msg .= BimpRender::renderIcon('fas_check', 'iconLeft') . 'Forcer la validation';
                    $msg .= '</span>';
                    $msg .= '<br/><span style="font-weight: bold">';
                    $msg .= 'ATTENTION: si vous effectuez une modification, veuillez utiliser le bouton "Valider" ci-dessous';
                    $msg .= '</span>';

                    $errors[] = $msg;
                }

                if (!count($errors)) {
                    $errors = $this->saveLinesData($lines_data, $warnings, $force_equipments_attribution);

                    if (!count($errors)) {
                        $is_sept = (isset($data['is_sept']) ? $data['is_sept'] : '');

                        // Temporaire:
                        if ($is_sept === 'oui') {
                            $info = $this->getData('info');
                            $this->updateField('info', 'DECEMBRE 2020' . ($info ? "\n\n" . $info : ''));
                        }
                        // ************

                        $date_received = BimpTools::getArrayValueFromPath($data, 'date_received', date('Y-m-d'));
                        $stock_out = (BimpTools::getArrayValueFromPath($data, 'stock_out', 'non') == 'oui' ? 1 : 0);
                        $errors = $this->validateReception($date_received, false, $stock_out, $force_equipments_attribution, $skip_equipments_place);
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCancelReception($data, &$success)
    {
        $warnings = array();
        $errors = $this->cancelReception($warnings);
        $success = 'Réception Annulée avec succès';

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSplit($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Création de la nouvelle réception effectuée avec succès';

        if (!isset($data['lines']) || empty($data['lines'])) {
            $warnings[] = 'Aucune unité à ajouter à la nouvelle réception';
        } else {
            $commande = $this->getParentInstance();
            if (!BimpObject::objectLoaded($commande)) {
                $errors[] = 'ID de la commande absent';
            } else {
                // Création de la nouvelle réception:
                $new_reception = BimpObject::getInstance($this->module, $this->object_name);
                $sql = 'SELECT MAX(num_reception) as num FROM ' . MAIN_DB_PREFIX . 'bl_commande_fourn_reception ';
                $sql .= 'WHERE `id_commande_fourn` = ' . (int) $commande->id;

                $result = $this->db->execute($sql);
                $result = $this->db->db->fetch_object($result);

                if (is_null($result) || !isset($result->num)) {
                    $num = 0;
                } else {
                    $num = (int) $result->num;
                }

                $num++;

                $rec_errors = $new_reception->validateArray(array(
                    'id_commande_fourn'                => (int) $this->getData('id_commande_fourn'),
                    'id_entrepot'                      => (int) $this->getData('id_entrepot'),
                    'ref'                              => (isset($data['ref']) ? $data['ref'] : $this->getData('ref')),
                    'status'                           => (int) $this->getData('status'),
                    'num_reception'                    => $num,
                    'date_received'                    => (isset($data['date_received']) ? $data['date_received'] : $this->getData('date_received')),
                    'info'                             => (isset($data['info']) ? $data['info'] : $this->getData('info')),
                    'id_user_resp'                     => (int) $this->getData('id_user_resp'),
                    'assign_lines_to_commandes_client' => (int) $this->getData('assign_lines_to_commandes_client'),
                ));

                if (!count($rec_errors)) {
                    $rec_warnings = array();
                    $rec_errors = $new_reception->create($rec_warnings);

                    if (count($rec_warnings)) {
                        $warnings[] = BimpTools::getMsgFromArray($rec_warnings, 'Erreurs lors de la création de la nouvelle réception');
                    }
                }

                if (count($rec_errors)) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::merge_array($rec_errors, $rec_warnings), 'Echec de la création de la nouvelle réception');
                }
            }

            // Répartition des quantités: 
            if (!count($errors) && $new_reception->isLoaded()) {
                foreach ($data['lines'] as $line_data) {
                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', (int) $line_data['id_line']);
                    $reception_data = $line->getReceptionData((int) $this->id);
                    $new_reception_data = $line->getReceptionData((int) $new_reception->id);

                    if (!BimpObject::objectLoaded($line)) {
                        $warnings[] = 'La ligne de commande fournisseur d\'ID ' . $line_data['id_line'] . ' n\'existe pas';
                        continue;
                    }

                    $isSerialisable = $line->isProductSerialisable();
                    $isReturn = ($line->getFullQty() < 0);

                    if ($isSerialisable) {
                        if (isset($line_data['items'])) {
                            foreach ($line_data['items'] as $item_value) {
                                if (!$isReturn) {
                                    $serial = '';
                                    if ((int) $this->getData('status') === self::BLCFR_RECEPTIONNEE) {
                                        // Equipements: 
                                        if (isset($reception_data['equipments'][(int) $item_value])) {
                                            $new_reception_data['equipments'][(int) $item_value] = $reception_data['equipments'][(int) $item_value];
                                            unset($reception_data['equipments'][(int) $item_value]);

                                            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $item_value);
                                            if (BimpObject::objectLoaded($equipment)) {
                                                $serial = $equipment->getData('serial');
                                            }
                                        } else {
                                            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $item_value);
                                            if (!BimpObject::objectLoaded($equipment)) {
                                                $warnings[] = 'L\'équipement d\'ID ' . $item_value . ' n\'existe plus';
                                            } else {
                                                $warnings[] = 'L\'équipement ' . $equipment->getRef() . ' n\'est plus présent dans la réception initiale';
                                            }
                                        }
                                    } else {
                                        $serial = $item_value;
                                    }

                                    if ($serial) {
                                        // Serials: 
                                        foreach ($reception_data['serials'] as $key => $serial_data) {
                                            if ($serial_data['serial'] === $serial) {
                                                $new_reception_data['serials'][] = $reception_data['serials'][$key];
                                                unset($reception_data['serials'][$key]);
                                                break;
                                            }
                                        }
                                    }
                                } else {
                                    // Equipements retournés:
                                    if (isset($reception_data['return_equipments'][(int) $item_value])) {
                                        $new_reception_data['return_equipments'][(int) $item_value] = $reception_data['return_equipments'][(int) $item_value];
                                        unset($reception_data['return_equipments'][(int) $item_value]);
                                    } else {
                                        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $item_value);
                                        if (!BimpObject::objectLoaded($equipment)) {
                                            $warnings[] = 'L\'équipement d\'ID ' . $item_value . ' n\'existe plus';
                                        } else {
                                            $warnings[] = 'L\'équipement ' . $equipment->getRef() . ' n\'est plus présent dans la réception initiale';
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        // Qtés: 
                        if (isset($line_data['qties'])) {
                            foreach ($line_data['qties'] as $qty_data) {
                                if (!(float) $qty_data['qty']) {
                                    continue;
                                }

                                $idx = (int) $qty_data['idx'];
                                if (isset($reception_data['qties'][$idx])) {
                                    $new_reception_data['qties'][$idx] = $reception_data['qties'][$idx];
                                    $new_reception_data['qties'][$idx]['qty'] = (float) $qty_data['qty'];
                                    $reception_data['qties'][$idx]['qty'] -= (float) $qty_data['qty'];

                                    if (!(float) $reception_data['qties'][$idx]['qty']) {
                                        unset($reception_data['qties'][$idx]);
                                    }
                                }
                            }
                        }
                    }

                    $new_reception_data['assign_to_commande_client'] = $reception_data['assign_to_commande_client'];
                    $new_reception_data['received'] = $reception_data['received'];

                    $old_qty = 0;
                    $new_qty = 0;
                    if ($isSerialisable) {
                        if (!$isReturn) {
                            if ((int) $this->getData('status') === self::BLCFR_RECEPTIONNEE) {
                                $old_qty = count($reception_data['equipments']);
                                $new_qty = count($new_reception_data['equipments']);
                            } else {
                                $old_qty = count($reception_data['serials']);
                                $new_qty = count($new_reception_data['serials']);
                            }
                        } else {
                            $old_qty = count($reception_data['return_equipments']) * -1;
                            $new_qty = count($new_reception_data['return_equipments']) * -1;
                        }
                    } else {
                        foreach ($reception_data['qties'] as $qty_data) {
                            $old_qty += (float) $qty_data['qty'];
                        }
                        foreach ($new_reception_data['qties'] as $qty_data) {
                            $new_qty += (float) $qty_data['qty'];
                        }
                    }

                    $reception_data['qty'] = $old_qty;
                    $new_reception_data['qty'] = $new_qty;

                    $receptions = $line->getData('receptions');
                    $receptions[(int) $this->id] = $reception_data;
                    $receptions[(int) $new_reception->id] = $new_reception_data;

                    $line->set('receptions', $receptions);
                    $line->updateField('receptions', $receptions);
                }

                $new_reception->onLinesChange();
                $this->onLinesChange();
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Validation via BimpDataSync

    public function initBdsActionValidateReception(&$action_data = array(), &$errors = array(), $extra_data = array())
    {
        $action_data['operation_title'] = 'Validation de la réception';

        if (!$this->isLoaded($errors)) {
            return;
        }

        if (!(int) $this->getData('status') === self::BLCFR_BROUILLON) {
            $errors[] = 'La réception ne peut pas être validée car elle n\'a pas le statut "brouillon"';
            return;
        }

        $commande = $this->getParentInstance();
        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande fournisseur absent';
            return;
        }

        $warnings = array();

        if ((int) BimpCore::getConf('use_db_transactions')) {
            $this->db->db->begin();
        }

        $validation_status = (int) $this->getData('validation_status');

        if (!$validation_status) {
            $lines_data = $this->processLinesFormData($extra_data, $errors);

            if (!count($errors)) {
                $codes_config_errors = array();
                $errors = $this->checkLinesData($lines_data, $codes_config_errors, false);

                if (!count($errors) && count($codes_config_errors) && (!isset($extra_data['force_validation']) || !(int) $extra_data['force_validation'])) {
                    $extra_data['force_validation'] = 1;
                    $errors = $this->saveLinesData($lines_data, $warnings, false);
                    $onclick = $this->getJsActionOnclick('validateReception', $extra_data, array(
                        'success_callback' => 'function() {bimpModal.clearCurrentContent();}'
                    ));

                    $msg = BimpTools::getMsgFromArray($codes_config_errors);
                    $msg .= '<br/><span class="btn btn-default" onclick="' . $onclick . '">';
                    $msg .= BimpRender::renderIcon('fas_check', 'iconLeft') . 'Forcer la validation';
                    $msg .= '</span>';
                    $msg .= '<br/><span style="font-weight: bold">';
                    $msg .= 'ATTENTION: si vous effectuez une modification, veuillez utiliser le bouton "Valider" ci-dessous';
                    $msg .= '</span>';

                    $errors[] = $msg;
                }
            }

            if (!count($errors)) {
                $errors = $this->saveLinesData($lines_data, $warnings, false);

                if (!count($errors)) {
                    $is_sept = (isset($extra_data['is_sept']) ? $extra_data['is_sept'] : '');
                    // Temporaire:
                    if ($is_sept === 'oui') {
                        $info = $this->getData('info');
                        $this->updateField('info', 'DECEMBRE 2020' . ($info ? "\n\n" . $info : ''));
                    }
                    // ************

                    $date_received = BimpTools::getArrayValueFromPath($extra_data, 'date_received', date('Y-m-d'));
                    $stock_out = (BimpTools::getArrayValueFromPath($extra_data, 'stock_out', 'non') == 'oui' ? 1 : 0);

                    if ($stock_out) {
                        $lines = $commande->getChildrenObjects('lines', array(
                            'type' => array(
                                'in' => array(ObjectLine::LINE_PRODUCT, ObjectLine::LINE_FREE)
                            )
                        ));

                        foreach ($lines as $line) {
                            $line_prod = $line->getProduct();

                            if (BimpObject::objectLoaded($line_prod) && $line_prod->isTypeProduct()) {
                                $reception_data = $line->getReceptionData((int) $this->id);

                                if (isset($reception_data['assign_to_commande_client']) && (int) $reception_data['assign_to_commande_client']) {
                                    if (isset($reception_data['qty']) && (float) $reception_data['qty'] > 0 && $line->getData('linked_object_name') === 'commande_line') {
                                        $errors[] = 'Cette réception ne peut pas être validée avec déplacement vers stock boutique car il y a au moins une ligne associée à une commande client';
                                    }
                                }
                            }
                        }
                    }

                    if (!count($errors)) {
                        if (is_null($date_received) || !(string) $date_received) {
                            $date_received = date('Y-m-d');
                        }
                        $this->set('validation_status', 1);
                        $this->set('date_received', $date_received);
                        $this->set('stock_out', (int) $stock_out);
                        $up_warnings = array();
                        $up_errors = $this->update($up_warnings, true);
                        if (count($up_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour de la réception');
                        } else {
                            $action_data['steps'] = $this->getBdsValidationSteps($errors);
                        }
                    }
                }
            }
        } elseif ($validation_status > 1) {
            $errors[] = 'La validation de cette réception a déjà été finalisée';
        } else {
            $action_data['steps'] = $this->getBdsValidationSteps($errors);
        }

        if ((int) BimpCore::getConf('use_db_transactions')) {
            if (count($errors)) {
                $this->db->db->rollback();
            } else {
                $this->db->db->commit();
            }
        }
    }

    protected function getBdsValidationSteps(&$errors = array())
    {
        $commande = $this->getParentInstance();
        $lines = $commande->getChildrenObjects('lines', array(
            'type' => array(
                'in' => array(ObjectLine::LINE_PRODUCT, ObjectLine::LINE_FREE)
            )
        ));

        $not_serialized_lines = array();
        $lines_serials = array();
        $lines_return_equipments = array();

        foreach ($lines as $line) {
            $line_prod = $line->getProduct();
            if (BimpObject::objectLoaded($line_prod) && $line_prod->isTypeProduct()) {
                $reception_data = $line->getReceptionData($this->id);

                if ((int) $reception_data['received']) {
                    continue;
                }

                $isSerialisable = $line_prod->isSerialisable();
                $isReturn = ((float) $line->getFullQty() < 0);

                if ($isSerialisable) {
                    if (!$isReturn) {
                        foreach ($reception_data['serials'] as $serial_data) {
                            if (!isset($serial_data['r']) || !(int) $serial_data['r']) {
                                $lines_serials[] = 'LINE_' . $line->id . '_SERIAL_' . $serial_data['serial'];
                            }
                        }
                    } else {
                        foreach ($reception_data['return_equipments'] as $id_equipment => $equipment_data) {
                            if (!isset($equipment_data['r']) || !(int) $equipment_data['r']) {
                                $lines_return_equipments[] = 'LINE_' . $line->id . '_EQ_' . $id_equipment;
                            }
                        }
                    }
                } else {
                    if (isset($reception_data['qty']) && (float) $reception_data['qty']) {
                        $not_serialized_lines[] = $line->id;
                    }
                }
            }
        }

        $steps = array();

        if (!empty($not_serialized_lines)) {
            $steps['process_not_serialized_lines'] = array(
                'label'                  => 'Traitement des produits non sérialisés',
                'on_error'               => 'continue',
                'elements'               => $not_serialized_lines,
                'nbElementsPerIteration' => 50
            );
        }
        if (!empty($lines_serials)) {
            $steps['process_lines_serials'] = array(
                'label'                  => 'Traitement des produits sérialisés',
                'on_error'               => 'continue',
                'elements'               => $lines_serials,
                'nbElementsPerIteration' => 10
            );
        }
        if (!empty($lines_return_equipments)) {
            $steps['process_return_equipments'] = array(
                'label'                  => 'Traitement des retours d\'équipements',
                'on_error'               => 'continue',
                'elements'               => $lines_return_equipments,
                'nbElementsPerIteration' => 50
            );
        }

        $steps['finalize'] = array(
            'label'    => 'Vérifications et finalisation',
            'on_error' => 'stop'
        );

        return $steps;
    }

    public function executeBdsActionValidateReception($step_name, $elements = array(), &$errors = array(), $operation_extra_data = array(), $action_extra_data = array())
    {
        switch ($step_name) {
            case 'process_not_serialized_lines':
                if (!empty($elements)) {
                    $stock_out = (int) $this->getData('stock_out');
                    foreach ($elements as $id_line) {
                        $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', $id_line);
                        if (BimpObject::objectLoaded($line)) {
                            $line->validateReception($this->id, false, $stock_out, false, false, null);
                        }
                    }
                }
                break;

            case 'process_lines_serials':
                if (!empty($elements)) {
                    $lines = array();
                    $stock_out = (int) $this->getData('stock_out');
                    foreach ($elements as $element) {
                        if (preg_match('/^LINE_(\d+)_SERIAL_(.+)$/', $element, $matches)) {
                            $id_line = (int) $matches[1];
                            $serial = $matches[2];

                            if ($id_line && $serial) {
                                if (!isset($lines[$id_line])) {
                                    $lines[$id_line] = array();
                                }
                                $lines[$id_line][] = $serial;
                            }
                        }
                    }

                    foreach ($lines as $id_line => $serials) {
                        $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', $id_line);
                        if (BimpObject::objectLoaded($line)) {
                            $line->validateReception($this->id, false, $stock_out, false, false, $serials);
                        }
                    }
                }
                break;

            case 'process_return_equipments':
                if (!empty($elements)) {
                    $lines = array();
                    $stock_out = (int) $this->getData('stock_out');
                    foreach ($elements as $element) {
                        if (preg_match('/^LINE_(\d+)_EQ_(\d+)$/', $element, $matches)) {
                            $id_line = (int) $matches[1];
                            $id_eq = (int) $matches[2];

                            if ($id_line && $id_eq) {
                                if (!isset($lines[$id_line])) {
                                    $lines[$id_line] = array();
                                }
                                $lines[$id_line][] = $id_eq;
                            }
                        }
                    }

                    foreach ($lines as $id_line => $equipments) {
                        $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', $id_line);
                        if (BimpObject::objectLoaded($line)) {
                            $line->validateReception($this->id, false, $stock_out, false, false, $equipments);
                        }
                    }
                }
                break;

            case 'finalize':
                $commande = $this->getParentInstance();
                $lines = $commande->getChildrenObjects('lines', array(
                    'type' => array(
                        'in' => array(ObjectLine::LINE_PRODUCT, ObjectLine::LINE_FREE)
                    )
                ));

                $all_received = 1;
                foreach ($lines as $line) {
                    $line_prod = $line->getProduct();
                    if (BimpObject::objectLoaded($line_prod) && $line_prod->isTypeProduct()) {
                        $r_data = $line->getReceptionData($this->id);

                        if (!isset($r_data['qty']) || !(int) $r_data['qty']) {
                            continue;
                        }

                        if (!isset($r_data['received']) || !(int) $r_data['received']) {
                            $all_received = 0;
                            break;
                        }
                    }
                }

                if (!$all_received) {
                    $errors[] = 'La réception n\'est pas complète.<br/>Veillez relancer à nouveau ce processus.<br/>Si le problème persiste, veuillez contacter debugerp@bimp.fr';
                } else {
                    $this->set('status', self::BLCFR_RECEPTIONNEE);
                    $this->set('validation_status', 2);

                    $warnings = array();
                    $up_errors = $this->update($warnings, true);

                    if (count($up_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour de la réception.<br/>Veillez relancer à nouveau ce processus.<br/>Si le problème persiste, veuillez contacter debugerp@bimp.fr');
                    } else {
                        $commande->checkReceptionStatus();
                    }
                }
                break;
        }
    }

    // Overrides: 

    public function checkObject($context = '', $field = '')
    {
        if ($context === 'fetch') {
            if (!(int) $this->getData('id_user_resp')) {
                $id_user = (int) $this->getData('user_create');
                if ($id_user) {
                    $this->updateField('id_user_resp', $id_user);
                }
            }

            global $current_bc;
            if (is_null($current_bc) || !is_a($current_bc, 'BC_List')) {
                $this->onLinesChange();
            }
        }
    }

    public function validate()
    {
        if (!(int) $this->getData('id_user_resp')) {
            global $user;
            if (BimpObject::ObjectLoaded($user)) {
                $this->set('id_user_resp', $user->id);
            }
        }
        return parent::validate();
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        $dateMAx = '2019-10-01';
        if ($this->getData('date_received') < $dateMAx)
            $errors[] = 'Date inférieur au ' . $dateMAx . ' creation impossible';

        if (!count($errors)) {
            $commande = $this->getParentInstance();

            if (!BimpObject::objectLoaded($commande)) {
                $errors[] = 'ID de la commande fournisseur absent';
            } else {
                $sql = 'SELECT MAX(num_reception) as num FROM ' . MAIN_DB_PREFIX . 'bl_commande_fourn_reception ';
                $sql .= 'WHERE `id_commande_fourn` = ' . (int) $commande->id;

                $result = $this->db->execute($sql);
                $result = $this->db->db->fetch_object($result);

                if (is_null($result) || !isset($result->num)) {
                    $num = 0;
                } else {
                    $num = (int) $result->num;
                }

                $num++;

                if (!(int) $this->getData('id_entrepot')) {
                    $this->set('id_entrepot', (int) $commande->getData('entrepot'));
                }

                $this->set('num_reception', $num);
            }
        }

        if (count($errors)) {
            return $errors;
        }

        return parent::create($warnings, $force_create);
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $errors = array();

        if ((int) $this->getData('status') !== 0) {
            $errors[] = 'Seules les récceptions au statut "brouillon" peuvent être supprimées';
        } else {
            $commande = $this->getParentInstance();
            $id_reception = (int) $this->id;

            $errors = parent::delete($warnings, $force_delete);

            if (!count($errors)) {
                if (BimpObject::objectLoaded($commande)) {
                    $lines = $commande->getLines('not_text');

                    foreach ($lines as $line) {
                        $line_errors = $line->unsetReception($id_reception);

                        if (count($line_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Erreurs lors de la mise à jour de la ligne de commande fournisseur n°' . $line->getData('position'));
                        }
                    }
                }
            }
        }

        return $errors;
    }
}
