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

    // Getters booléens: 

    public function isFieldEditable($field)
    {
        if ($field === 'id_entrepot' && $this->isLoaded()) {
            return 0;
        }

        return parent::isFieldEditable($field);
    }

    public function isActionAllowed($action, &$errors)
    {
        switch ($action) {
            case 'saveLinesData':
            case 'validateReception':
                if ((int) $this->getData('status') !== self::BLCFR_BROUILLON) {
                    $errors[] = 'La réception n\'a pas le statut "brouillon"';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    // Getters valeurs: 

    public function getName($with_generic = true)
    {
        return 'Réception #' . $this->getData('num_reception');
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

        if ($this->isActionAllowed('validateReception')) {
            $buttons[] = array(
                'label'   => 'Valider la réception',
                'icon'    => 'fas_check-circle',
                'onclick' => $this->getJsActionOnclick('validateReception', array(), array(
                    'form_name' => 'validate'
                ))
            );
        }

        return $buttons;
    }

    public function getCommandesFournListbulkActions()
    {
        return array();
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
                        if ((float) $line->qty > (float) $line->getReceivedQty()) {
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

    public function renderLineQtyInputs($line, $idx, $qty, $max, $total_max, $pu_ht, $tva_tx)
    {
        $html = '';

        $html .= '<td style="width: 160px;">';
        $html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_reception_' . $this->id . '_qty_' . $idx . '_qty', (float) $qty, array(
                    'data'        => array(
                        'data_type'              => 'number',
                        'decimals'               => 0,
                        'min'                    => 0,
                        'max'                    => $max,
                        'total_max_value'        => $total_max,
                        'total_max_inputs_class' => 'line_' . $line->id . '_qty_input'
                    ),
                    'max_label'   => 1,
                    'extra_class' => 'total_max line_' . $line->id . '_qty_input'
        ));
        $html .= '</td>';

        $html .= '<td style="width: 160px;">';
        $html .= BimpInput::renderInput('text', 'line_' . $line->id . '_reception_' . $this->id . '_qty_' . $idx . '_pu_ht', $pu_ht, array(
                    'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
                    'data'        => array(
                        'data_type' => 'number',
                        'decimals'  => 6,
                        'min'       => 'none',
                        'max'       => 'none'
                    )
        ));
        $html .= '</td>';

        $html .= '<td style="width: 160px;">';
        $html .= BimpInput::renderInput('text', 'line_' . $line->id . '_reception_' . $this->id . '_qty_' . $idx . '_tva_tx', $tva_tx, array(
                    'addon_right' => BimpRender::renderIcon('fas_percent'),
                    'data'        => array(
                        'data_type' => 'number',
                        'decimals'  => 3,
                        'min'       => 0,
                        'max'       => 100
                    )
        ));
        $html .= '</td>';

        $html .= '<td>';
        $onclick = '$(this).parent(\'td\').parent(\'tr\').remove();';
        $html .= BimpRender::renderRowButton('Suppprimer', 'fas_trash-alt', $onclick);
        $html .= '</td>';

        return $html;
    }

    public function renderLineSerialInputs($line, $serial, $pu_ht, $tva_tx)
    {
        $html = '';
        $html .= '<td style="width: 160px" class="serial" data-serial="' . $serial . '">' . $serial . '</td>';
        $html .= '<td style="width: 160px">';
        $html .= BimpInput::renderInput('text', 'line_' . $line->id . '_reception_' . $this->id . '_serial_' . $serial . '_pu_ht', $pu_ht, array(
                    'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
                    'data'        => array(
                        'data_type' => 'number',
                        'decimals'  => 6,
                        'min'       => 'none',
                        'max'       => 'none'
                    )
        ));
        $html .= '</td>';

        $html .= '<td style="width: 160px">';
        $html .= BimpInput::renderInput('text', 'line_' . $line->id . '_reception_' . $this->id . '_serial_' . $serial . '_tva_tx', $tva_tx, array(
                    'addon_right' => BimpRender::renderIcon('fas_percent'),
                    'data'        => array(
                        'data_type' => 'number',
                        'decimals'  => 3,
                        'min'       => 0,
                        'max'       => 100
                    )
        ));
        $html .= '</td>';

        $html .= '<td>';
        $onclick = '$(this).parent(\'td\').parent(\'tr\').remove();';
        $html .= BimpRender::renderRowButton('Suppprimer', 'fas_trash-alt', $onclick);
        $html .= '</td>';

        return $html;
    }

    public function renderDetailsView()
    {
        $html = '';

        $commandes = $this->getParentInstance();

        $edit = ($this->getData('status') === self::BLCFR_BROUILLON);

        if (!BimpObject::objectLoaded($commandes)) {
            return BimpRender::renderAlerts('ID de la commande fournisseur absent');
        }

        $lines = $commandes->getChildrenObjects('lines');

        if (empty($lines)) {
            return BimpRender::renderAlerts('Aucune ligne enregistrée pour cette commande fournisseur');
        }

        $html .= '<div class="reception_details" data-id_reception="' . $this->id . '" data-edit="' . $edit . '">';
        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Ligne</th>';
        $html .= '<th' . ($edit ? ' style="width: 160px"' : '') . '>Qtés' . ($edit ? ' / N° de série' : '') . '</th>';
        $html .= '<th' . ($edit ? ' style="width: 160px"' : '') . '>Prix unitaire HT</th>';
        $html .= '<th' . ($edit ? ' style="width: 160px"' : '') . '>Tx TVA</th>';
        if (!$edit) {
            $html .= '<th>Equipements</th>';
        } else {
            $html .= '<th></th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        $has_lines = false;

        foreach ($lines as $line) {
            $max = (float) $line->getReceptionAvailableQty($this->id);

            if (!$max) {
                continue;
            }

            $has_lines = true;

            $reception_data = $line->getReceptionData($this->id);
            $isSerialisable = false;

            $product = $line->getProduct();
            if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                $isSerialisable = true;
            }

            $commande_client_line = null;
            if ($line->getData('linked_object_name') === 'commande_line') {
                $commande_client_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $line->getData('linked_id_object'));
            }

            $html .= '<tr class="line_row" data-id_line="' . $line->id . '" data-line_position="' . $line->getData('position') . '" data-serialisable="' . (int) $isSerialisable . '">';

            // Desc: 
            $html .= '<td>';
            $html .= $line->displayLineData('desc');
            if (BimpObject::objectLoaded($commande_client_line)) {
                $commande = $commande_client_line->getParentInstance();
                if (BimpObject::objectLoaded($commande)) {
                    $html .= '<br/><br/>';
                    $html .= '<strong>Commande client d\'origine: </strong>';
                    $html .= $commande->getNomUrl(1, 1, 1, 'full') . '&nbsp;&nbsp;(Ligne n°' . $commande_client_line->getData('position') . ')';
                }
            }
            $html .= '</td>';

            if ($edit) {
                if ($isSerialisable) {
                    $html .= '<td colspan="4">';
                    if (isset($reception_data['serials']) && !empty($reception_data['serials'])) {
                        $html .= '<table class="bimp_list_table">';
                        $html .= '<tbody>';

                        foreach ($reception_data['serials'] as $serial_data) {
                            if (isset($serial_data['serial']) && (string) $serial_data['serial']) {
                                $pu_ht = (isset($serial_data['pu_ht']) ? (float) $serial_data['pu_ht'] : (float) $line->pu_ht);
                                $tva_tx = (isset($serial_data['tva_tx']) ? (float) $serial_data['tva_tx'] : (float) $line->tva_tx);
                                $html .= '<tr class="line_' . $line->id . '_serial_data">';
                                $html .= $this->renderLineSerialInputs($line, $serial_data['serial'], $pu_ht, $tva_tx);
                                $html .= '</tr>';
                            }
                        }

                        $html .= '</tbody>';
                        $html .= '</table>';

                        $max -= count($reception_data['serials']);
                    }

                    $html .= '<div style="margin-top: 12px">N° de série à ajouter: </div>';
                    $html .= BimpInput::renderInput('textarea', 'line_' . $line->id . '_reception_' . $this->id . '_new_serials', '', array(
                                'auto_expand' => true
                    ));
                    $html .= '<p class="inputHelp">Séparateurs possibles: sauts de ligne, espaces, virgules ou points-virgules.<br/>';
                    $html .= 'Max: ' . $max . ' numéro' . ($max > 1 ? 's' : '') . ' de série.';
                    $html .= '</p>';

                    $html .= '<div style="margin-top: 12px">';
                    $html .= '<table class="bimp_list_table">';
                    $html .= '<tbody>';
                    $html .= '<tr>';
                    $html .= '<td style="width: 160px">Nouveaux n° de série: </td>';
                    $html .= '<td style="width: 160px">';
                    $html .= BimpInput::renderInput('text', 'line_' . $line->id . '_reception_' . $this->id . '_new_serials_pu_ht', (float) $line->pu_ht, array(
                                'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
                                'data'        => array(
                                    'data_type' => 'number',
                                    'decimals'  => 6,
                                    'min'       => 'none',
                                    'max'       => 'none'
                                )
                    ));
                    $html .= '</td>';

                    $html .= '<td style="width: 160px">';
                    $html .= BimpInput::renderInput('text', 'line_' . $line->id . '_reception_' . $this->id . '_new_serials_tva_tx', $line->tva_tx, array(
                                'addon_right' => BimpRender::renderIcon('fas_percent'),
                                'data'        => array(
                                    'data_type' => 'number',
                                    'decimals'  => 3,
                                    'min'       => 0,
                                    'max'       => 100
                                )
                    ));
                    $html .= '</td>';
                    $html .= '<td></td>';
                    $html .= '</tr>';
                    $html .= '</tbody>';
                    $html .= '</table>';
                    $html .= '</div>';

                    if (BimpObject::objectLoaded($commande_client_line)) {
                        $assign = (int) (isset($reception_data['assign_to_commande_client']) ? $reception_data['assign_to_commande_client'] : 1);
                        $html .= '<div style="margin-top: 12px">';
                        $html .= 'Assigner les équipements à la ligne de commande client d\'origine: <br/>';
                        $html .= BimpInput::renderInput('toggle', 'line_' . $line->id . '_reception_' . $this->id . '_assign_to_commande_client', $assign);
                        $html .= '</div>';
                    }

                    $html .= '</td>';
                } else {
                    $html .= '<td colspan="4">';
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

                    $html .= '<tr class="line_' . $line->id . '_qty_row_tpl" style="display: none">';
                    $html .= $this->renderLineQtyInputs($line, 'qtyidx', 0, $max, $total_max, (float) $line->pu_ht, (float) $line->tva_tx);
                    $html .= '</tr>';

                    $i = 1;
                    foreach ($reception_data['qties'] as $qty_data) {
                        $html .= '<tr class="line_' . $line->id . '_qty_row" data-qty_idx="' . $i . '">';
                        $pu_ht = (isset($qty_data['pu_ht']) ? (float) $qty_data['pu_ht'] : (float) $line->pu_ht);
                        $tva_tx = (isset($qty_data['tva_tx']) ? (float) $qty_data['tva_tx'] : (float) $line->tva_tx);
                        $html .= $this->renderLineQtyInputs($line, $i, $qty_data['qty'], $max + (int) $qty_data['qty'], $total_max, $pu_ht, $tva_tx);
                        $html .= '</tr>';
                    }

                    $html .= '</tbody>';
                    $html .= '</table>';
                    $onclick = 'addCommandeFournReceptionLineQtyRow($(this), ' . $line->id . ');';
                    $html .= '<div style="text-align: right; margin: 5px 0">';
                    $html .= '<button class="btn btn-default btn-small" onclick="' . $onclick . '">';
                    $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter';
                    $html .= '</button>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
            } else {
                $html .= '<td colspan="3">';
                $html .= '<table class="bimp_list_table">';
                $html .= '<tbody>';

                if ($isSerialisable) {
                    foreach ($reception_data['equipments'] as $id_equipment => $equipment_data) {
                        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                        if (BimpObject::objectLoaded($equipment)) {
                            $html .= '<tr>';
                            $html .= '<td>' . $equipment->getNomUrl(1, 1, 1) . '</td>';
                            $html .= '<td>' . BimpTools::displayMoneyValue((float) isset($equipment_data['pu_ht']) ? $equipment_data['pu_ht'] : $line->pu_ht) . '</td>';
                            $html .= '<td>' . BimpTools::displayFloatValue((float) isset($equipment_data['tva_tx']) ? $equipment_data['tva_tx'] : $line->tva_tx, 3) . '%</td>';
                            $html .= '</tr>';
                        }
                    }
                } else {
                    foreach ($reception_data['qties'] as $qty_data) {
                        $html .= '<tr>';
                        $html .= '<td>' . $qty_data['qty'] . '</td>';
                        $html .= '<td>' . BimpTools::displayMoneyValue((float) isset($qty_data['pu_ht']) ? $qty_data['pu_ht'] : $line->pu_ht) . '</td>';
                        $html .= '<td>' . BimpTools::displayFloatValue((float) isset($qty_data['tva_tx']) ? $qty_data['tva_tx'] : $line->tva_tx, 3) . '%</td>';
                        $html .= '</tr>';
                    }
                    $html .= '<tr>';
                    $html .= '<td colspan="3"><strong>Total: </strong>' . $reception_data['qty'] . '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';
                $html .= '</table>';
                $html .= '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
//        $html .= '<div class="ajaxResultContainer" style="display: none"></div>';

        return $html;
    }

    public function renderValidateLinesForm()
    {
        $html = '';

        return $html;
    }

    // Traitements

    public function processLinesFormData($data)
    {
        
    }

    public function checkLinesData($lines_data)
    {
        $errors = array();

        foreach ($lines_data as $id_line => $line_data) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', (int) $id_line);
            if (!BimpObject::objectLoaded($line)) {
                $errors[] = 'La ligne de commande fournisseur d\'ID ' . $id_line . ' n\'existe pas';
            } else {
                $line_errors = $line->checkReceptionData((int) $this->id, $line_data);
                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
                }
            }
        }

        return $errors;
    }

    public function saveLinesData($lines_data, &$warnings = array())
    {
        $errors = $this->checkLinesData($lines_data);

        if (count($errors)) {
            return $errors;
        }

        foreach ($lines_data as $id_line => $line_data) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', (int) $id_line);
            if (BimpObject::objectLoaded($line)) {
                $line_warnings = array();
                $line_errors = $line->setReceptionData((int) $this->id, $line_data, false, $line_warnings);

                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
                }

                if (count($line_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Ligne n°' . $line->getData('position'));
                }
            }
        }

        return $errors;
    }

    public function validateReception()
    {
        $errors = array();

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

        $lines_done = array();

        foreach ($lines as $line) {
            $line_errors = $line->validateReception((int) $this->id);

            if (count($line_errors)) {
                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
            } else {
                $lines_done[] = $line;
            }
        }

        if (count($errors)) {
            foreach ($lines_done as $line) {
                $line->cancelReceptionValidation((int) $this->id);
            }
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
            $lines_data = array();

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

                    if (isset($line_data['qties']) && !empty($line_data['qties'])) {
                        foreach ($line_data['qties'] as $qty_data) {
                            if (isset($qty_data['qty']) && (float) $qty_data['qty']) {
                                $qties[] = array(
                                    'qty'    => (float) $qty_data['qty'],
                                    'pu_ht'  => (float) (isset($qty_data['pu_ht']) ? $qty_data['pu_ht'] : $line->pu_ht),
                                    'tva_tx' => (float) (isset($qty_data['tva_tx']) ? $qty_data['tva_tx'] : $line->tva_tx),
                                );
                                $qty += (float) $qty_data['qty'];
                            }
                        }
                    }


                    if (isset($line_data['serials']) && !empty($line_data['serials'])) {
                        foreach ($line_data['serials'] as $serial_data) {
                            if (isset($serial_data['serial']) && (string) $serial_data['serial']) {
                                $serials[] = array(
                                    'serial' => $serial_data['serial'],
                                    'pu_ht'  => (float) (isset($serial_data['pu_ht']) ? $serial_data['pu_ht'] : $line->pu_ht),
                                    'tva_tx' => (float) (isset($serial_data['tva_tx']) ? $serial_data['tva_tx'] : $line->tva_tx),
                                );
                                $qty++;
                            }
                        }
                    }

                    if (isset($line_data['new_serials']) && (string) $line_data['new_serials']) {
                        $new_serials = $line->explodeSerials($line_data['new_serials']);
                        $pu_ht = (isset($line_data['new_serials_pu_ht']) ? (float) $line_data['new_serials_pu_ht'] : (float) $line->pu_ht);
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

                    $lines_data[(int) $line_data['id_line']] = array(
                        'qty'                       => $qty,
                        'qties'                     => $qties,
                        'serials'                   => $serials,
                        'assign_to_commande_client' => (isset($line_data['assign_to_commande_client']) ? (int) $line_data['assign_to_commande_client'] : 0),
                    );
                }
            }

            $errors = $this->saveLinesData($lines_data, $warnings);
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
        $success = '';

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la réception absent';
        } else {
            $lines_data = $this->processLinesFormData($data, $errors);

            if (!count($errors)) {
                $errors = $this->checkLinesData($lines_data);

                if (!count($errors)) {
                    $errors = $this->saveLinesData($lines_data);

                    if (!count($errors)) {
                        $errors = $this->validateReception();
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        BimpObject::loadClass('bimpcommercial', 'Bimp_CommandeFournLine');
        $errors = array();

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

        if (count($errors)) {
            return $errors;
        }

//        $lines = $commande->getChildrenObjects('lines', array(
//            'type' => Bimp_CommandeFournLine::LINE_PRODUCT
//        ));
//
        // Vérification du non-dépassement des qtés max: 
//        foreach ($lines as $line) {
//
//            $line_errors = array();
//
//            if ($line->isProductSerialisable()) {
//                $serials = BimpTools::isSubmit('line_' . $line->id . '_reception_1_equipments', '');
//                $line_errors = $line->checkReceptionSerials($serials);
//            } else {
//                $qty = (float) BimpTools::getValue('line_' . $line->id . '_reception_1_qty', 0);
//                $line_errors = $line->checkReceptionQty($qty);
//            }
//            if (count($line_errors)) {
//                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
//            }
//        }
//
//        if (count($errors)) {
//            return $errors;
//        }

        $errors = parent::create($warnings, $force_create);

//        if (!count($errors)) {
//            foreach ($lines as $line) {
//                $data = array();
//
//                if ($line->isProductSerialisable()) {
//                    $data['serials'] = BimpTools::getValue('line_' . $line->id . '_reception_1_equipments', '');
//                    if (!(string) $data['serials']) {
//                        continue;
//                    }
//                    $data['assign_to_commande_client'] = BimpTools::getValue('line_' . $line->id . '_assign_to_commande_client', 0);
//                } else {
//                    $data['qty'] = BimpTools::getValue('line_' . $line->id . '_reception_1_qty', 0);
//                    if ($qty <= 0) {
//                        continue;
//                    }
//                }
//
//                $data['pa_ht'] = BimpTools::getValue('line_' . $line->id . '_reception_1_pu_ht', null);
//                $data['tva_tx'] = BimpTools::getValue('line_' . $line->id . '_reception_1_tva_tx', null);
//
//                $line_errors = $line->setReceptionData($this->id, $data, false);
//                if (count($line_errors)) {
//                    $warnings = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
//                }
//            }
//        }

        return $errors;
    }
}
