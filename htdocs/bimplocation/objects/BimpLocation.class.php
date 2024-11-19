<?php

class BimpLocation extends BimpObject
{

    const STATUS_CANCELED = -1;
    const STATUS_DRAFT = 0;
    const STATUS_ONGOING = 1;
    const STATUS_CLOSED = 10;

    public static $status_list = array(
        self::STATUS_CANCELED => array('label' => 'Annulée', 'icon' => 'fas_exclamation-circle', 'classes' => array('danger')),
        self::STATUS_DRAFT    => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        self::STATUS_ONGOING  => array('label' => 'En cours', 'icon' => 'fas_cogs', 'classes' => array('info')),
        self::STATUS_CLOSED   => array('label' => 'Terminée', 'icon' => 'fas_check', 'classes' => array('success'))
    );
    protected $amounts = null;
    public $lines_mass_processing = false;

    // Getters booléens : 

    public function areLinesEditable()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        if (in_array((int) $this->getData('status'), array(self::STATUS_CANCELED, self::STATUS_CLOSED))) {
            return 0;
        }

        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        switch ($action) {
            case 'cancel':
                $status = (int) $this->getData('status');
                if ($status === self::STATUS_CANCELED) {
                    $errors[] = 'Cette location est déjà annulée';
                    return 0;
                }
                if ($status === self::STATUS_CLOSED) {
                    $errors[] = 'Cette location est terminée';
                    return 0;
                }
                return 1;

            case 'reopen':
                if (!in_array((int) $this->getData('status'), array(self::STATUS_CANCELED, self::STATUS_CLOSED))) {
                    $errors[] = 'Cette location ne peut pas être réouverte';
                    return 0;
                }
                return 1;

            case 'editDates':
                $status = (int) $this->getData('status');
                if ($status < 0 || $status >= 10) {
                    $errors[] = 'Le statut actuel de cette location ne permet pas de modifier les dates';
                    return 0;
                }
                return 1;

            case 'createFacture':
            case 'addToVente':
                if ($action == 'createFacture') {
                    $amounts = $this->getAmounts();
                    if (!round($amounts['total_remain_to_bill'])) {
                        $errors[] = 'Il ne reste rien à facturer';
                        return 0;
                    }
                }

                if ((int) $this->getData('id_cur_vente')) {
                    $errors[] = 'Cette location est en cours de traitement dans la vente #' . (int) $this->getData('id_cur_vente');
                    return 0;
                }
                return 1;

            case 'removeFromCurVente':
                if (!(int) $this->getData('id_cur_vente')) {
                    $errors[] = 'Il n\'y a pas de vente en cours pour cette location';
                    return 0;
                }
                return 1;

            case 'close':
                if ((int) $this->getData('status') !== self::STATUS_ONGOING) {
                    $errors[] = 'Le statut actuel de la location ne permet pas sa fermeture';
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isEquipementAvailable($equipment, $date_from = null, $date_to = null)
    {
        if (is_null($date_from)) {
            $date_from = $this->getData('date_from');
        }

        if (is_null($date_to)) {
            $date_to = $this->getData('date_to');
        }
    }

    // Getters params: 

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('cancel') && $this->canSetAction('cancel')) {
            $buttons[] = array(
                'label'   => 'Annuler',
                'icon'    => 'fas_times',
                'onclick' => $this->getJsActionOnclick('cancel', array(), array(
                    'confirm_msg' => 'Veuillez confirmer l\\\'annulation'
                ))
            );
        }

        if ($this->isActionAllowed('reopen') && $this->canSetAction('reopen')) {
            $buttons[] = array(
                'label'   => 'Réouvrir',
                'icon'    => 'fas_undo',
                'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                    'confirm_msg' => 'Veuillez confirmer la réouverture'
                ))
            );
        }

        if ($this->isActionAllowed('createFacture') && $this->canSetAction('createFacture')) {
            $buttons[] = array(
                'label'   => 'Facturer directement',
                'icon'    => 'fas_file-invoice-dollar',
                'onclick' => $this->getJsActionOnclick('createFacture', array(), array(
                    'form_name'      => 'facturation',
                    'on_form_submit' => 'function($form, extra_data) { return BimpLocation.onNewFactureFormSubmit($form, extra_data); }',
                ))
            );
        }

        if ($this->isActionAllowed('addToVente') && $this->canSetAction('addToVente')) {
            $buttons[] = array(
                'label'   => 'Ajouter à une vente',
                'icon'    => 'fas_plus-circle',
                'onclick' => $this->getJsActionOnclick('addToVente', array(), array(
                    'form_name'      => 'add_to_vente',
                    'on_form_submit' => 'function($form, extra_data) { return BimpLocation.onNewFactureFormSubmit($form, extra_data); }',
                ))
            );
        }

        if ($this->isActionAllowed('removeFromCurVente') && $this->canSetAction('removeFromCurVente')) {
            $id_vente = (int) $this->getData('id_cur_vente');
            $buttons[] = array(
                'label'   => 'Retirer de la vente en cours #' . $id_vente,
                'icon'    => 'fas_times',
                'onclick' => $this->getJsActionOnclick('removeFromCurVente', array(), array(
                    'confirm_msg' => 'Veuillez confirmer le retrait de cette location de la vente en cours #' . $id_vente
                ))
            );
        }

        if ($this->isActionAllowed('close') && $this->canSetAction('close')) {
            $amounts = $this->getAmounts();
            $confirm_msg = 'Veuillez confirmer la clôture de cette location';

            if ($amounts['total_remain_to_bill'] > 0) {
                $confirm_msg .= ' (ATTENTION : il reste ' . BimpTools::displayMoneyValue($amounts['total_remain_to_bill'], 'EUR', 0, 0, 1, 2, 0, ',', 1) . ' à facturer)';
            }

            $buttons[] = array(
                'label'   => 'Terminer',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('close', array(), array(
                    'confirm_msg' => $confirm_msg
                ))
            );
        }

        if ($this->isActionAllowed('editDates') && $this->canSetAction('editDates')) {
            $buttons[] = array(
                'label'   => 'Modifier les dates',
                'icon'    => 'fas_calendar-alt',
                'onclick' => $this->getJsActionOnclick('editDates', array(), array(
                    'form_name' => 'edit_dates'
                ))
            );
        }

        return $buttons;
    }

    public function getListExtraBtn()
    {
        $buttons = array();

        if ($this->isActionAllowed('addToVente') && $this->canSetAction('addToVente')) {
            $buttons[] = array(
                'label'   => 'Ajouter à une vente',
                'icon'    => 'fas_plus-circle',
                'onclick' => $this->getJsActionOnclick('addToVente', array(), array(
                    'form_name'      => 'add_to_vente',
                    'on_form_submit' => 'function($form, extra_data) { return BimpLocation.onNewFactureFormSubmit($form, extra_data); }',
                ))
            );
        }

        if ($this->isActionAllowed('removeFromCurVente') && $this->canSetAction('removeFromCurVente')) {
            $id_vente = (int) $this->getData('id_cur_vente');
            $buttons[] = array(
                'label'   => 'Retirer de la vente en cours #' . $id_vente,
                'icon'    => 'fas_times',
                'onclick' => $this->getJsActionOnclick('removeFromCurVente', array(), array(
                    'confirm_msg' => 'Veuillez confirmer le retrait de cette location de la vente en cours #' . $id_vente
                ))
            );
        }

        if ($this->isActionAllowed('editDates') && $this->canSetAction('editDates')) {
            $buttons[] = array(
                'label'   => 'Modifier les dates',
                'icon'    => 'fas_calendar-alt',
                'onclick' => $this->getJsActionOnclick('editDates', array(), array(
                    'form_name' => 'edit_dates'
                ))
            );
        }
        return $buttons;
    }

    // Getters array :

    public function getClientContactsArray($include_empty = true, $active_only = true)
    {
        $id_client = (int) $this->getData('id_client');

        if ($id_client) {
            return self::getSocieteContactsArray($id_client, $include_empty, '', $active_only);
        }

        return array();
    }

    public function getClientVentesArray()
    {
        $ventes = array(
            0 => 'Nouvelle vente'
        );

        $where = 'id_client = ' . (int) $this->getData('id_client') . ' AND id_entrepot = ' . (int) $this->getData('id_entrepot');
        $where .= ' AND status = 1';

        $rows = $this->db->getValues('bc_vente', 'id', $where, null, 'id', 'DESC');

        if (!empty($rows)) {
            foreach ($rows as $id_vente) {
                $ventes[$id_vente] = 'Vente #' . $id_vente;
            }
        } else {
            
        }

        return $ventes;
    }

    public function getClientDraftFacturesArray()
    {
        $factures = array(
            0 => 'Nouvelle facture'
        );

        foreach (BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', array(
            'fk_soc'    => (int) $this->getData('id_client'),
            'fk_statut' => 0,
            'type'      => array(0, 2)
        )) as $fac) {
            $factures[$fac->id] = $fac->getRef() . ' - ' . $fac->getData('libelle');
        }

        return $factures;
    }

    // Getters données:

    public function getAmounts($recalculate = false, $lines = null)
    {
        if (is_null($this->amounts) || $recalculate) {
            $this->amounts = array(
                'total_ht'             => 0,
                'total_tva'            => 0,
                'total_ttc'            => 0,
                'total_billed'         => 0,
                'total_remain_to_bill' => 0,
                'lines'                => array()
            );

            if (is_null($lines)) {
                $lines = $this->getChildrenObjects('lines');
            }

            foreach ($lines as $line) {
                $line_amounts = $line->getAmounts();

                $this->amounts['lines'][$line->id] = $line_amounts;
                $this->amounts['total_ht'] += $line_amounts['total_ht'];
                $this->amounts['total_tva'] += $line_amounts['total_tva'];
                $this->amounts['total_ttc'] += $line_amounts['total_ttc'];
                $this->amounts['total_billed'] += $line_amounts['total_billed'];
                $this->amounts['total_remain_to_bill'] += $line_amounts['remain_to_bill'];
            }
        }

        return $this->amounts;
    }

    public function getNewFactureData(&$errors = array(), $lines = null)
    {
        if (!$this->isLoaded($errors)) {
            return array();
        }

        $data = array();

        if (is_null($lines)) {
            $lines = $this->getChildrenObjects('lines');
        }

        $amounts = $this->getAmounts(false, $lines);

        if (!empty($lines)) {
            foreach ($lines as $line) {
                if (!isset($amounts['lines'][$line->id])) {
                    continue;
                }

                $line_errors = array();
                $line_data = array(
                    'id_forfait'     => (int) $line->getData('id_forfait'),
                    'total_qty'      => $amounts['lines'][$line->id]['qty'],
                    'qty'            => $amounts['lines'][$line->id]['qty'],
                    'pu_ht'          => $amounts['lines'][$line->id]['pu_ht'],
                    'tva_tx'         => $amounts['lines'][$line->id]['tva_tx'],
                    'remise'         => $amounts['lines'][$line->id]['remise'],
                    'total_billed'   => $amounts['lines'][$line->id]['total_billed'],
                    'remain_to_bill' => $amounts['lines'][$line->id]['remain_to_bill'],
                    'cancel_lines'   => array()
                );

                $billed_qties = $line->getBilledQties($line_errors);

                foreach ($billed_qties as $id_fac_prod => $fac_prod_data_by_prices) {
                    foreach ($fac_prod_data_by_prices as $fac_prod_pu_ttc => $fac_prod_data) {
                        if (($line_data['id_forfait'] === $id_fac_prod) && ((float) $fac_prod_pu_ttc === (float) $amounts['lines'][$line->id]['pu_ttc'])) {
                            $line_data['qty'] -= $fac_prod_data['qty'];
                            continue;
                        }

                        $qty = $fac_prod_data['qty'] * -1;
                        $total_ttc = $fac_prod_data['pu_ht'] * (1 + ($fac_prod_data['tva_tx'] / 100)) * $qty;
                        if ($fac_prod_data['remise']) {
                            $total_ttc -= ($total_ttc * ($fac_prod_data['remise'] / 100));
                        }

                        $line_data['cancel_lines'][] = array(
                            'id_product' => $id_fac_prod,
                            'pu_ht'      => $fac_prod_data['pu_ht'],
                            'tva_tx'     => $fac_prod_data['tva_tx'],
                            'remise'     => $fac_prod_data['remise'],
                            'qty'        => $qty,
                            'total_ttc'  => $total_ttc
                        );
                    }
                }

                $line_total_ttc = $line_data['pu_ht'] * (1 + ($line_data['tva_tx'] / 100)) * $line_data['qty'];
                if ($line_data['remise']) {
                    $line_total_ttc -= ($line_total_ttc * ($line_data['remise'] / 100));
                }
                $line_data['total_ttc'] = $line_total_ttc;

                $data[$line->id] = $line_data;
            }
        }

        return $data;
    }

    // Rendus HTML

    public function renderMontants()
    {
        $html = '';

        $errors = array();

        if ($this->isLoaded($errors)) {
            $lines = $this->getChildrenObjects('lines');
            $lines_ids = array();

            if (!empty($lines)) {
                $amounts = $this->getAmounts(false, $lines);

                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Equipement</th>';
                $html .= '<th>PU HT</th>';
                $html .= '<th>TVA</th>';
                $html .= '<th>Remise</th>';
                $html .= '<th>Nb jours</th>';
                $html .= '<th>Total TTC</th>';
                $html .= '<th>Facturé</th>';
                $html .= '<th>Reste à fac.</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';

                foreach ($lines as $line) {
                    $lines_ids[] = $line->id;
                    if (!isset($amounts['lines'][$line->id])) {
                        continue;
                    }

                    $html .= '<tr>';

                    $html .= '<td>';
                    $html .= $line->displayDataDefault('id_equipment');
                    $html .= '</td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayMoneyValue($amounts['lines'][$line->id]['pu_ht'], '', false, false, false, 2, 1, ',', 1);
                    $html .= '</td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayFloatValue($amounts['lines'][$line->id]['tva_tx'], 2, ',', 0, 0, 0, 0, 1, 1) . ' %';
                    $html .= '</td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayFloatValue($amounts['lines'][$line->id]['remise'], 2, ',', 0, 0, 0, 0, 1, 1) . ' %';
                    $html .= '</td>';

                    $html .= '<td><span class="badge badge-info">' . $amounts['lines'][$line->id]['qty'] . '</span></td>';

                    $html .= '<td><b>';
                    $html .= BimpTools::displayMoneyValue($amounts['lines'][$line->id]['total_ttc'], '', false, false, false, 2, 1, ',', 1);
                    $html .= '</b></td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayMoneyValue($amounts['lines'][$line->id]['total_billed'], '', false, false, false, 2, 1, ',', 1);
                    $html .= '</td>';

                    $html .= '<td>';
                    $html .= '<span class="' . ($amounts['lines'][$line->id]['remain_to_bill'] > 0 ? 'danger' : ($amounts['lines'][$line->id]['remain_to_bill'] < 0 ? 'warning' : 'success')) . '">';
                    $html .= BimpTools::displayMoneyValue($amounts['lines'][$line->id]['remain_to_bill'], '', false, false, false, 2, 1, ',', 1);
                    $html .= '</span>';
                    $html .= '</td>';

                    $html .= '</tr>';
                }

                $html .= '</tbody>';
                $html .= '</table>';
            }

            $html .= '<table class="bimp_list_table" style="margin-top: 30px; width: 300px; display: inline-block">';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody class="headers_col">';
            $html .= '<tr>';
            $html .= '<th>Total HT</th>';
            $html .= '<td>';
            $html .= BimpTools::displayMoneyValue($amounts['total_ht'], '', false, false, false, 2, 1, ',', 1);
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<th>Total TVA</th>';
            $html .= '<td>';
            $html .= BimpTools::displayMoneyValue($amounts['total_tva'], '', false, false, false, 2, 1, ',', 1);
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<th>Total TTC</th>';
            $html .= '<td style="font-size: 14px; font-weight: bold">';
            $html .= BimpTools::displayMoneyValue($amounts['total_ttc'], '', false, false, false, 2, 1, ',', 1);
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<th>Facturé</th>';
            $html .= '<td>';
            $html .= BimpTools::displayMoneyValue($amounts['total_billed'], '', false, false, false, 2, 1, ',', 1);
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<th>Reste à facturer</th>';
            $html .= '<td>';
            $html .= '<span class="' . ($amounts['total_remain_to_bill'] > 0 ? 'danger' : ($amounts['total_remain_to_bill'] < 0 ? 'warning' : 'success')) . '">';
            $html .= BimpTools::displayMoneyValue($amounts['total_remain_to_bill'], '', false, false, false, 2, 1, ',', 1);
            $html .= '</span>';
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '</tbody>';
            $html .= '</table>';

            if (!empty($lines_ids)) {
                $linked_filters = '((a.linked_object_name = \'location_line\' AND a.linked_id_object IN (' . implode(',', $lines_ids) . '))';
                $linked_filters .= ' OR (a.linked_object_name = \'bc_vente_article\' AND (SELECT va.linked_id_object FROM ' . MAIN_DB_PREFIX . 'bc_vente_article va WHERE va.id = a.linked_id_object) IN (' . implode(',', $lines_ids) . ')))';

                $rows = $this->db->executeS(BimpTools::getSqlFullSelectQuery('bimp_facture_line', array('DISTINCT a.id_obj'), array(
//                            'a.linked_object_name' => 'location_line',
//                            'a.linked_id_object'   => $lines_ids,
                            'linked_custom' => array(
                                'custom' => $linked_filters
                            ),
                            'f.fk_statut'   => array(0, 1, 2),
                            'f.type'        => array(0, 1, 2, 3)
                                ), array(
                            'f' => array(
                                'table' => 'facture',
                                'on'    => 'f.rowid = a.id_obj'
                            )
                        )), 'array');

                if (is_array($rows) && count($rows)) {
                    $html .= '<div style="display: inline-block; margin: 30px; vertical-align: top;">';
                    $html .= '<div style="padding-bottom: 5px; margin-bottom: 10px; border-bottom: 1px solid #636363">';
                    $html .= '<h4>' . BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft') . 'Factures</h4>';
                    $html .= '</div>';

                    foreach ($rows as $r) {
                        $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['id_obj']);
                        if (BimpObject::objectLoaded($fac)) {
                            $html .= $fac->getLink() . '<br/>';
                        }
                    }
                    $html .= '</div>';
                }
            }
        }

        $title = BimpRender::renderIcon('fas_euro-sign', 'iconLeft') . 'Montants';
        return BimpRender::renderPanel($title, $html, '', array());
    }

    public function renderFactureLinesInputs()
    {
        $html = '';

        $lines = $this->getChildrenObjects('lines');
        $errors = array();
        if (!empty($lines)) {
            $lines_data = $this->getNewFactureData($errors, $lines);

//            $html .= '<pre>';
//            $html .= print_r($lines_data, 1);
//            $html .= '</pre>';

            $html .= '<table class="bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th></th>';
            $html .= '<th>Equipement</th>';
            $html .= '<th>Désignation</th>';
            $html .= '<th>PU HT</th>';
            $html .= '<th>TVA</th>';
            $html .= '<th>Remise</th>';
            $html .= '<th>Qté</th>';
            $html .= '<th>Total TTC</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            foreach ($lines as $line) {
                if (!isset($lines_data[$line->id])) {
                    continue;
                }

                if (empty($lines_data[$line->id]['cancel_lines']) && !$lines_data[$line->id]['qty']) {
                    $check = 0;
                } else {
                    $check = 1;
                }

                $rowspan = 1 + count($lines_data[$line->id]['cancel_lines']);

                $first_cells = '<td' . ($rowspan > 1 ? ' rowspan="' . $rowspan . '"' : '') . '>';
                $first_cells .= '<input type="checkbox" class="table_row_selector line_check" name="check_line_' . $line->id . '"' . ($check ? ' checked="1"' : '') . ' data-id_line="' . $line->id . '"/>';
                $first_cells .= '</td>';
                $first_cells .= '<td' . ($rowspan > 1 ? ' rowspan="' . $rowspan . '"' : '') . '>' . $line->displayEquipment() . '</td>';

                if (!empty($lines_data[$line->id]['cancel_lines'])) {
                    $fl = true;
                    foreach ($lines_data[$line->id]['cancel_lines'] as $l) {
                        $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $l['id_product']);
                        $html .= '<tr>';
                        if ($fl) {
                            $html .= $first_cells;
                            $fl = false;
                        }

                        $html .= '<td>';
                        if (BimpObject::objectLoaded($prod)) {
                            $html .= $prod->getLink() . '<br/>';
                            $html .= $prod->getData('label');
                        } else {
                            $html .= 'Produit #' . $l['id_product'] . ' (n\'existe plus)';
                        }
                        $html .= '</td>';

                        $html .= '<td>' . BimpTools::displayMoneyValue($l['pu_ht'], '', 0, 0, 0, 2, 1, ',', 1) . '</td>';
                        $html .= '<td>' . BimpTools::displayFloatValue($l['tva_tx'], 2, ',', 0, 0, 0, 1, 1, 1) . ' %</td>';
                        $html .= '<td>' . BimpTools::displayFloatValue($l['remise'], 2, ',', 0, 0, 0, 1, 1, 1) . ' %</td>';
                        $html .= '<td>';
                        $html .= '<span class="badge badge-' . ($l['qty'] <= 0 ? 'danger' : ($l['qty'] > 0 ? 'info' : 'default')) . '">' . $l['qty'] . '</span>';
                        $html .= '</td>';
                        $html .= '<td>' . BimpTools::displayMoneyValue($l['total_ttc'], '', 0, 0, 0, 2, 1, ',', 1) . '</td>';
                        $html .= '</tr>';
                    }
                }

                $html .= '<tr>';
                if (empty($lines_data[$line->id]['cancel_lines'])) {
                    $html .= $first_cells;
                }

                $html .= '<td>';
                $html .= $line->displayForfaitInfos(false);
                $html .= '</td>';

                $html .= '<td>' . BimpTools::displayMoneyValue($lines_data[$line->id]['pu_ht'], '', 0, 0, 0, 2, 1, ',', 1) . '</td>';
                $html .= '<td>' . BimpTools::displayFloatValue($lines_data[$line->id]['tva_tx'], 2, ',', 0, 0, 0, 1, 1, 1) . ' %</td>';
                $html .= '<td>' . BimpTools::displayFloatValue($lines_data[$line->id]['remise'], 2, ',', 0, 0, 0, 1, 1, 1) . ' %</td>';

                $html .= '<td>';
                $badge_class = ($lines_data[$line->id]['qty'] < 0 ? 'danger' : ($lines_data[$line->id]['qty'] > 0 ? 'info' : 'default'));
                $html .= '<span class="badge badge-' . $badge_class . '">' . $lines_data[$line->id]['qty'] . '</span>';
                $html .= '</td>';

                $html .= '<td>' . BimpTools::displayMoneyValue($lines_data[$line->id]['total_ttc'], '', 0, 0, 0, 2, 1, ',', 1) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
        } else {
            $errors[] = 'Aucune ligne à facturer';
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors, 'danger');
        }

        return $html;
    }

    public function renderCaisseLocationLines()
    {
        $html = '';

        $lines = $this->getChildrenObjects('lines');

        $errors = array();
        if (!empty($lines)) {
            $lines_data = $this->getNewFactureData($errors, $lines);

            foreach ($lines as $line) {
                if (!isset($lines_data[$line->id])) {
                    continue;
                }

                $line_errors = array();

                $equipment = $line->getChildObject('equipment');
                $product = null;
                $forfait = $line->getChildObject('forfait');

                if (BimpObject::objectLoaded($equipment)) {
                    $product = $equipment->getChildObject('bimp_product');
                    if (!BimpObject::objectLoaded($product)) {
                        $line_errors[] = 'Aucun produit lié à l\'équipement ' . $equipment->getLink();
                    }
                } elseif ((int) $line->getData('id_equipment')) {
                    $line_errors[] = 'L\'équipement #' . $line->getData('id_equipment');
                } else {
                    $line_errors[] = 'Aucun équipement sélectionné';
                }

                if (!BimpObject::objectLoaded($forfait)) {
                    if ((int) $line->getData('id_forfait')) {
                        $line_errors[] = 'Le forfait #' . $line->getData('id_forfait') . ' n\'existe plus';
                    } else {
                        $line_errors[] = 'Aucun forfait sélectionné';
                    }
                }

                $html .= '<div id="cart_location_line_' . $line->id . '" class="cartLocationLine" data-id_location_line="' . $line->id . '">';

                if (!count($line_errors)) {
                    $pu_ttc = $lines_data[$line->id]['pu_ht'] * (1 + ($lines_data[$line->id]['tva_tx'] / 100));
                    $remise = $lines_data[$line->id]['remise'];
                    $total_qty = $lines_data[$line->id]['total_qty'];
                    $total_billed = $lines_data[$line->id]['total_billed'];
                    $remain_to_bill = $lines_data[$line->id]['remain_to_bill'];

                    $html .= '<div class="product_title">' . $product->getName();
                    if ($line->isDeletable()) {
                        if ($line->can('delete')) {
                            $onclick = $line->getJsDeleteOnClick();
                            $html .= '<span class="removeArticle" onclick="' . $onclick . '">';
                            $html .= BimpRender::renderIcon('fas_trash-alt');
                            $html .= '</span>';
                        }
                    } elseif ($line->isActionAllowed('reopen') && $line->canSetAction('reopen')) {
                        $onclick = $this->getJsActionOnclick('reopen', array(), array());
                        $html .= '<span class="reopenArticle bs-popover" onclick="' . $onclick . '"' . BimpRender::renderPopoverData('Réouvrir') . '>';
                        $html .= BimpRender::renderIcon('fas_redo');
                        $html .= '</span>';
                    }

                    $onclick = $line->getJsLoadModalForm('default', 'Edition de la location', array(), null, '', 0, '$(this)', 'function() {Vente.refresh()}');
                    $html .= '<span class="editArticle" onclick="' . $onclick . '">';
                    $html .= BimpRender::renderIcon('fas_edit');
                    $html .= '</span>';
                    $html .= '</div>';

                    $html .= '<div class="product_info"><b>N° équipement : ' . $equipment->getData('serial') . '</b></div>';
                    $html .= '<div class="product_info"><b>Réf: </b>' . $product->getRef() . '</div>';
                    $html .= '<div class="product_info"><b>Forfait : </b>' . $forfait->getRef() . ' - ' . $forfait->getData('label');
                    $html .= '<b> (' . BimpTools::displayMoneyValue($pu_ttc, 'EUR', 0, 0, 0, 2, 0, ',', 1) . ' TTC / jour)</b></div>';

                    $html .= '<div style="margin: 8px 30px">';
                    $html .= '<span style="font-size: 14px">' . $line->displayDates(true) . '</span> (' . $total_qty . ' jours)';
                    $html .= $line->renderAvailablitiesAlerts();
                    $html .= '</div>';

                    if ($remise) {
                        $html .= '<div class="location_remises">';
                        $html .= 'Remise (' . BimpTools::displayFloatValue($remise, 2, ',', 0, 0, 0, 0, 1, 1) . ' %) : ';
                        $html .= '<b>' . BimpTools::displayMoneyValue(($pu_ttc * $total_qty * ($remise / 100)), 'EUR', 0, 0, 0, 2, 0, ',', 1) . '</b>';
                        $html .= '</div>';
                    }

                    // Options article: 

                    $html .= '<div class="article_options">';
                    $html .= '<div class="product_total_price">';

                    $total_ttc = $pu_ttc * $total_qty;
                    if ($remise) {
                        $html .= '<span class="base_price">' . BimpTools::displayMoneyValue($total_ttc, 'EUR', 0, 0, 0, 2, 0, ',', 1) . '</span>';
                        $total_ttc -= ($total_ttc * ($remise / 100));
                    }

                    if ($total_billed) {
                        $html .= '<span class="bold">' . BimpTools::displayMoneyValue($total_ttc, 'EUR', 0, 0, 0, 2, 0, ',', 1) . '</span>';
                        $html .= '<br/><span style="color: #8C0000">Déjà facturé : <b>-' . BimpTools::displayMoneyValue($total_billed, 'EUR', 0, 0, 0, 2, 0, ',', 1) . '</b></span>';
                        $html .= '<br/><span class="final_price">' . BimpTools::displayMoneyValue($remain_to_bill, 'EUR', 0, 0, 0, 2, 0, ',', 1) . '</span>';
                    } else {
                        $html .= '<span class="final_price">' . BimpTools::displayMoneyValue($total_ttc, 'EUR', 0, 0, 0, 2, 0, ',', 1) . '</span>';
                    }

                    $html .= '</div>';
                    $html .= '</div>';
                }

                if (count($line_errors)) {
                    $html .= BimpRender::renderAlerts($line_errors);
                }

                $html .= '</div>';
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors, 'danger');
        }

        return $html;
    }

    // Traitements : 

    public function checkStatus()
    {
        if ($this->isLoaded() && !$this->lines_mass_processing) {
            $cur_status = (int) $this->getData('status');

            if ($cur_status >= 0 && $cur_status < self::STATUS_CLOSED) {
                $lines_ids = $this->getChildrenList('lines');

                $linked_filters = '((a.linked_object_name = \'location_line\' AND a.linked_id_object IN (' . implode(',', $lines_ids) . '))';
                $linked_filters .= ' OR (a.linked_object_name = \'bc_vente_article\' AND (SELECT va.linked_id_object FROM ' . MAIN_DB_PREFIX . 'bc_vente_article va WHERE va.id = a.linked_id_object) IN (' . implode(',', $lines_ids) . ')))';

                $res = $this->db->executeS(BimpTools::getSqlFullSelectQuery('bimp_facture_line', array(
                            'COUNT(a.id) as nb_lines'
                                ), array(
//                            'a.linked_object_name' => 'location_line',
                            'linked_custom' => array(
                                'custom' => $linked_filters
                            ),
                            'f.fk_statut'   => array(0, 1, 2),
                            'f.type'        => array(0, 1, 2, 3)
                                ), array(
                            'f' => array(
                                'table' => 'facture',
                                'on'    => 'a.id_obj = f.rowid'
                            )
                        )), 'array');

                if (isset($res[0]['nb_lines']) && $res[0]['nb_lines'] > 0) {
                    $new_status = self::STATUS_ONGOING;
                } else {
                    $new_status = self::STATUS_DRAFT;
                }

                if ($new_status !== $cur_status) {
                    $this->updateField('status', $new_status);
                }
            }
        }
    }

    public function addEquipment($equipment, $date_from = null, $date_to = null, $id_forfait = null)
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        if (is_null($date_from)) {
            $date_from = $this->getData('date_from');
        }

        if (is_null($date_to)) {
            $date_to = $this->getData('date_to');
        }

        $line = BimpObject::getInstance('bimplocation', 'BimpLocationLine');
        $line->setIdParent($this->id);

        if (!$line->isEquipmentAvailable($equipment->id, $date_from, $date_to, (int) $this->getData('id_entrepot'), $errors)) {
            return $errors;
        }

        if (is_null($id_forfait)) {
            $product = $equipment->getChildObject('bimp_product');
            if (!BimpObject::objectLoaded($product)) {
                $errors[] = 'Aucun produit associé à l\'équipement ' . $equipment->getLink();
            } else {
                $period_data = BimpTools::getDatesIntervalData($date_from, $date_to);
                $qty = $period_data['full_days'];

                $id_forfait = $product->getDefaultIdForfaitLocation($qty);
                if (!$id_forfait) {
                    $errors[] = 'Aucun forfait de location disponible pour l\'équipement ' . $equipment->getLink();
                }
            }
        }

        if (!count($errors)) {
            $errors = $line->validateArray(array(
                'id_equipment' => $equipment->id,
                'id_forfait'   => $id_forfait,
                'date_from'    => $date_from,
                'date_to'      => $date_to
            ));

            if (!count($errors)) {
                $warnings = array();
                $errors = $line->create($warnings, true);
            }
        }

        return $errors;
    }

    public function createFacture($libelle = '', &$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return null;
        }

        $fac_errors = array();
        if (!$libelle) {
            $libelle = 'Location de matériel';
        }

        if (!count($fac_errors)) {
            $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
            $fields = array(
                'fk_soc'   => (int) $this->getData('id_client'),
                'libelle'  => $libelle,
                'entrepot' => (int) $this->getData('id_entrepot'),
                'datef'    => date('Y-m-d')
            );

            if ($facture->field_exists('ef_type')) {
                $fields['ef_type'] = 'C';
            }
            if ($facture->field_exists('entrepot')) {
                $fields['entrepot'] = 1;
            }

            $fac = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Facture', $fields, true, $fac_errors);
        }

        if (count($fac_errors)) {
            $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la création de la facture');
            return null;
        } else {
            $id_contact = (int) $this->getData('id_contact_facturation');
            if ($id_contact) {
                if ($fac->dol_object->add_contact($id_contact, 'BILLING2', 'external') <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($fac->dol_object), 'Echec de l\'ajout du contact de facturation');
                }
            }
        }

        return $fac;
    }

    public function addLinesToFacture($id_facture, $lines_to_bill)
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $this->lines_mass_processing = true;

        if ($id_facture) {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);

            if (!BimpObject::objectLoaded($facture)) {
                $errors[] = 'La facture d\'ID ' . $id_facture . ' n\'existe pas';
            } elseif ((int) $facture->getData('fk_statut') > 0) {
                $errors[] = 'La facture ' . $facture->getRef() . ' n\'est plus au statut brouillon';
            } else {
                $facture->checkLines();
            }
        } else {
            $errors[] = 'ID facture absent';
        }

        if (!count($errors)) {
            $lines = $this->getChildrenObjects('lines', array(
                'id' => $lines_to_bill
            ));
            $lines_data = $this->getNewFactureData($errors, $lines);

            if (empty($lines_data)) {
                $errors[] = 'Aucune ligne à facturer';
            } else {
                foreach ($lines as $line) {
                    if (!isset($lines_data[$line->id])) {
                        continue;
                    }

                    $line_errors = array();
                    $line_warnings = array();

                    $line_label = 'Ligne de location n° ' . $line->getData('position');
                    $id_forfait = (int) $line->getData('id_forfait');
                    if (!$id_forfait) {
                        $line_errors[] = 'Aucun forfait sélectionné';
                    } else {
                        // Lignes d'annulation : 
                        if (!empty($lines_data[$line->id]['cancel_lines'])) {
                            foreach ($lines_data[$line->id]['cancel_lines'] as $cancel_line_data) {
                                $desc = '';
                                if ($id_forfait !== $cancel_line_data['id_product']) {
                                    $desc .= 'Annulation suite à changement de forfait';
                                } else {
                                    $desc .= 'Annulation suite à modification tarifaire';
                                }
                                $fac_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');

                                // Création de la ligne de facture: 
                                $fac_line->validateArray(array(
                                    'id_obj'             => (int) $facture->id,
                                    'type'               => Bimp_FactureLine::LINE_PRODUCT,
                                    'remisable'          => 2,
                                    'editable'           => 1,
                                    'pa_editable'        => 0,
                                    'linked_id_object'   => (int) $line->id,
                                    'linked_object_name' => 'location_line',
                                    'hide_in_pdf'        => 0
                                ));

                                $fac_line->qty = $cancel_line_data['qty'];
                                $fac_line->desc = $desc;
                                $fac_line->id_product = (int) $cancel_line_data['id_product'];
                                $fac_line->pu_ht = (float) $cancel_line_data['pu_ht'];
                                $fac_line->tva_tx = (float) $cancel_line_data['tva_tx'];
//                                $fac_line->remise = (float) $cancel_line_data['remise'];

                                $cancel_line_warnings = array();
                                $cancel_line_errors = $fac_line->create($cancel_line_warnings, true);

                                if (!count($cancel_line_errors)) {
                                    // Ajout de la remise: 
                                    if ((float) $cancel_line_data['remise']) {
                                        $remises_errors = array();
                                        BimpObject::createBimpObject('bimpcommercial', 'ObjectLineRemise', array(
                                            'id_object_line' => $fac_line->id,
                                            'object_type'    => 'facture',
                                            'type'           => 1,
                                            'percent'        => (float) $cancel_line_data['remise']
                                                ), true, $remises_errors);

                                        if (count($remises_errors)) {
                                            $cancel_line_errors[] = BimpTools::getMsgFromArray($remises_errors, 'Echec de l\'ajout de la remise');
                                        }
                                    }
                                }

                                if (count($cancel_line_errors)) {
                                    $line_errors[] = BimpTools::getMsgFromArray($cancel_line_errors, 'Echec de la création d\'une ligne d\'annulation');
                                }
                            }
                        }
                    }

                    // Ajout ligne de facture : 
                    if (!count($line_errors)) {
                        $desc = '';
                        $eq = $line->getChildObject('equipment');
                        if (!BimpObject::objectLoaded($eq)) {
                            $line_errors[] = 'Equipement loué absent';
                        } else {
                            $desc = ((int) $line->getData('cancelled') ? 'Annulation complète l' : 'L') . 'ocation ' . $eq->displayProduct('default', true, true);
                            $desc .= '<br/>N° ' . $eq->getData('serial');
                        }

                        $fac_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');

                        // Création de la ligne de facture: 
                        $fac_line->validateArray(array(
                            'id_obj'             => (int) $facture->id,
                            'type'               => Bimp_FactureLine::LINE_PRODUCT,
                            'remisable'          => 2,
                            'editable'           => 1,
                            'pa_editable'        => 0,
                            'linked_id_object'   => (int) $line->id,
                            'linked_object_name' => 'location_line',
                            'hide_in_pdf'        => 0
                        ));

                        $fac_line->qty = $lines_data[$line->id]['qty'];
                        $fac_line->desc = $desc;
                        $fac_line->id_product = $id_forfait;
                        $fac_line->pu_ht = $lines_data[$line->id]['pu_ht'];
                        $fac_line->tva_tx = $lines_data[$line->id]['tva_tx'];
                        $fac_line->date_from = $line->getData('date_from');
                        $fac_line->date_to = $line->getData('date_to');

                        $line_errors = $fac_line->create($line_warnings, true);
                    }

                    if (!count($line_errors)) {
                        // Ajout de la remise: 
                        if ((float) $lines_data[$line->id]['remise']) {
                            $remises_errors = array();
                            BimpObject::createBimpObject('bimpcommercial', 'ObjectLineRemise', array(
                                'id_object_line' => $fac_line->id,
                                'object_type'    => 'facture',
                                'type'           => 1,
                                'percent'        => (float) $lines_data[$line->id]['remise']
                                    ), true, $remises_errors);

                            if (count($remises_errors)) {
                                $line_errors[] = BimpTools::getMsgFromArray($remises_errors, 'Echec de l\'ajout de la remise');
                            }
                        }
                    }

                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, ucfirst($line_label));
                    }
                }
            }
        }

        $this->lines_mass_processing = false;

        if (!count($errors)) {
            $this->checkStatus();
        }

        return $errors;
    }

    public function createVenteArticlesLines($id_vente)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $lines = $this->getChildrenObjects('lines');
            $lines_data = $this->getNewFactureData($errors, $lines);

            if (!empty($lines_data)) {
                foreach ($lines as $line) {
                    if (!isset($lines_data[$line->id])) {
                        continue;
                    }

                    $line_errors = array();
                    $line_warnings = array();

                    $line_label = 'Ligne de location ' . $this->getRef() . ' n° ' . $line->getData('position');
                    $id_forfait = (int) $line->getData('id_forfait');
                    if (!$id_forfait) {
                        $line_errors[] = 'Aucun forfait sélectionné';
                    } else {
                        // Lignes d'annulation : 
                        if (!empty($lines_data[$line->id]['cancel_lines'])) {
                            foreach ($lines_data[$line->id]['cancel_lines'] as $cancel_line_data) {
                                $desc = '';
                                if ($id_forfait !== $cancel_line_data['id_product']) {
                                    $desc .= 'Annulation suite à changement de forfait';
                                } else {
                                    $desc .= 'Annulation suite à modification tarifaire';
                                }

                                // Création de la lgne de l'article : 
                                $cancel_line_errors = $cancel_line_warnings = array();
                                $vente_article = BimpObject::createBimpObject('bimpcaisse', 'BC_VenteArticle', array(
                                            'id_vente'           => $id_vente,
                                            'id_product'         => $cancel_line_data['id_product'],
                                            'qty'                => $cancel_line_data['qty'],
                                            'unit_price_tax_ex'  => $cancel_line_data['pu_ht'],
                                            'tva_tx'             => $cancel_line_data['tva_tx'],
                                            'unit_price_tax_in'  => $cancel_line_data['pu_ht'] * (1 + ($cancel_line_data['tva_tx'] / 100)),
                                            'infos'              => $desc,
                                            'linked_object_name' => 'location_line',
                                            'linked_id_object'   => $line->id
                                                ), true, $cancel_line_errors, $cancel_line_warnings);

                                if (!count($cancel_line_errors)) {
                                    // Ajout de la remise: 
                                    if ((float) $cancel_line_data['remise']) {
                                        $remises_errors = array();
                                        BimpObject::createBimpObject('bimpcaisse', 'BC_VenteRemise', array(
                                            'id_vente'   => $id_vente,
                                            'id_article' => $vente_article->id,
                                            'type'       => 1,
                                            'percent'    => (float) $cancel_line_data['remise'],
                                            'per_unit'   => 1
                                                ), true, $remises_errors);

                                        if (count($remises_errors)) {
                                            $cancel_line_errors[] = BimpTools::getMsgFromArray($remises_errors, 'Echec de l\'ajout de la remise');
                                        }
                                    }
                                }

                                if (count($cancel_line_errors)) {
                                    $line_errors[] = BimpTools::getMsgFromArray($cancel_line_errors, 'Echec de la création d\'une ligne d\'annulation');
                                }
                            }
                        }
                    }

                    // Ajout ligne : 
                    if (!count($line_errors)) {
                        $desc = '';
                        $eq = $line->getChildObject('equipment');
                        if (!BimpObject::objectLoaded($eq)) {
                            $line_errors[] = 'Equipement loué absent';
                        } else {
                            $desc = ((int) $line->getData('cancelled') ? 'Annulation complète l' : 'L') . 'ocation ' . $eq->displayProduct('default', true, true);
                            $desc .= '<br/>N° ' . $eq->getData('serial');
                        }

                        $vente_article = BimpObject::createBimpObject('bimpcaisse', 'BC_VenteArticle', array(
                                    'id_vente'           => $id_vente,
                                    'id_product'         => $id_forfait,
                                    'qty'                => $lines_data[$line->id]['qty'],
                                    'unit_price_tax_ex'  => $lines_data[$line->id]['pu_ht'],
                                    'tva_tx'             => $lines_data[$line->id]['tva_tx'],
                                    'unit_price_tax_in'  => $lines_data[$line->id]['pu_ht'] * (1 + ($lines_data[$line->id]['tva_tx'] / 100)),
                                    'infos'              => $desc,
                                    'linked_object_name' => 'location_line',
                                    'linked_id_object'   => $line->id
                                        ), true, $line_errors, $line_warnings);

                        if (!count($line_errors)) {
                            // Ajout de la remise: 
                            if ((float) $lines_data[$line->id]['remise']) {
                                $remises_errors = array();
                                BimpObject::createBimpObject('bimpcaisse', 'BC_VenteRemise', array(
                                    'id_vente'   => $id_vente,
                                    'id_article' => $vente_article->id,
                                    'type'       => 1,
                                    'percent'    => (float) $lines_data[$line->id]['remise'],
                                    'per_unit'   => 1
                                        ), true, $remises_errors);

                                if (count($remises_errors)) {
                                    $line_errors[] = BimpTools::getMsgFromArray($remises_errors, 'Echec de l\'ajout de la remise');
                                }
                            }
                        }
                    }

                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, ucfirst($line_label));
                    }
                }
            }
        }

        return $errors;
    }

    // Actions: 

    public function actionCancel($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Annulation effectuée avec succès';

        $this->set('status', self::STATUS_CANCELED);
        $errors = $this->update($warnings, true);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Réouverture effectuée avec succès';

        $this->set('status', self::STATUS_DRAFT);
        $errors = $this->update($warnings, true);

        $this->checkStatus();

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCreateFacture($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $sc = '';

        global $user;
        $user->rights->facture->creer = 1;

        $lines = BimpTools::getArrayValueFromPath($data, 'lines', array());

        if (empty($lines)) {
            $errors[] = 'Aucune ligne à facturer sélectionnée';
        } else {
            $id_facture = (int) BimpTools::getArrayValueFromPath($data, 'id_facture', 0);
            if (!$id_facture) {
                $libelle = BimpTools::getArrayValueFromPath($data, 'fac_label', 'Location de matériel');
                $facture = $this->createFacture($libelle, $errors);
            } else {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);

                if (!BimpObject::objectLoaded($facture)) {
                    $errors[] = 'La facture #' . $id_facture . ' n\'existe plus';
                }
            }

            if (!count($errors) && BimpObject::objectLoaded($facture)) {
                $lines_errors = $this->addLinesToFacture($facture->id, $lines);

                if (count($lines_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($lines_errors, 'Echec de l\'ajout des lignes à la facture');
                } else {
                    if ((int) BimpTools::getArrayValueFromPath($data, 'validate_fac', 0)) {

                        if ($facture->dol_object->validate($user, '') <= 0) {
                            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture->dol_object), 'Echec de la validation de la facture');
                        } else {
                            global $langs;
                            $facture->dol_object->generateDocument('bimpfact', $langs);

                            $ref = dol_sanitizeFileName($this->db->getValue('facture', 'ref', 'rowid = ' . $facture->id));
                            $file_url = $facture->getFileUrl($ref . '.pdf');
                            if ($file_url) {
                                $sc .= 'window.open("' . $file_url . '");bimp_msg(\'ici : ' . $file_url . '\')';
                            }
                        }
                    } else {
                        $url = $facture->getUrl();

                        if ($url) {
                            $sc = 'window.open(\'' . $url . '\');';
                        }
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionClose($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Clôture de la location effectuée avec succès';

        $this->set('status', self::STATUS_CLOSED);
        $errors = $this->update($warnings, true);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddToVente($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $sc = '';

        $id_vente = (int) BimpTools::getArrayValueFromPath($data, 'id_vente', 0);
        if ($id_vente) {
            $errors = $this->updateField('id_cur_vente', $id_vente);
            $success = 'Ajout à la vente #' . $id_vente . ' effectué avec succès';

            if (!count($errors)) {
                $sc = 'loadVente($(), ' . $id_vente . ', true);';
            }
        } else {

            $id_entrepot = (int) $this->getData('id_entrepot');

            $id_caisse = (int) $this->db->getValue('bc_caisse', 'id', 'id_entrepot = ' . (int) $this->getData('id_entrepot'));

            if (!$id_caisse) {
                $entrepot = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Entrepot', $id_entrepot);
                $errors[] = 'Il n\'existe aucune caisse pour l\'entrepot ' . (BimpObject::objectLoaded($entrepot) ? $entrepot->getName() : '#' . $this->getData('id_entrepot'));
            } else {
                $caisse = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Caisse', $id_caisse);
                if (!BimpObject::objectLoaded($caisse)) {
                    $errors[] = 'La caisse #' . $id_caisse . ' n\'existe plus';
                } else {
                    if ($caisse->isValid($errors)) {
                        global $user;
                        $vente = BimpObject::createBimpObject('bimpcaisse', 'BC_Vente', array(
                                    'id_caisse'            => $id_caisse,
                                    'id_caisse_session'    => (int) $caisse->getData('id_current_session'),
                                    'id_entrepot'          => $id_entrepot,
                                    'id_client'            => (int) $this->getData('id_client'),
                                    'id_client_contact'    => (int) $this->getData('id_contact_facturation'),
                                    'id_user_resp'         => (int) $user->id,
                                    'id_selected_location' => $this->id
                                        ), true, $errors, $warnings);

                        if (!count($errors) && BimpObject::objectLoaded($vente)) {
                            $success = 'Création de la vente #' . $vente->id . ' effectué avec succès';
                            $errors = $this->updateFIeld('id_cur_vente', $vente->id);

                            if (!count($errors)) {
                                global $main_controller;
                                if (is_a($main_controller, 'BimpController') && $main_controller->module == 'bimpcaisse') {
                                    $sc = 'loadVente($(), ' . $vente->id . ', true);';
                                }
                            }
                        }
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionRemoveFromCurVente($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_vente = (int) $this->getData('id_cur_vente');
        if ($id_vente) {
            $errors = $this->updateField('id_cur_vente', 0);
            $success = 'Retrait de la vente #' . $id_vente . ' effectué';
        } else {
            $errors[] = 'Aucune vente en cours pour cette location';
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionEditDates($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Dates mises à jour avec succès';
        $sc = '';

        $date_from = BimpTools::getArrayValueFromPath($data, 'date_from', '');
        $date_to = BimpTools::getArrayValueFromPath($data, 'date_to', '');

        if (!$date_from) {
            $errors[] = 'Veuillez saisir la date de début';
        }

        if (!$date_to) {
            $errors[] = 'Veuillez saisir la date de fin';
        }

        if ($date_from && $date_to && $date_to < $date_from) {
            $errors[] = 'La date de fin ne peut pas être antérieure à la date de début';
        }

        if (!count($errors)) {
            $init_from = $this->getInitData('date_from');
            $init_to = $this->getInitData('date_to');

            $this->validateArray(array(
                'date_from' => $date_from,
                'date_to'   => $date_to
            ));

            $errors = $this->update($warnings, true);

            if (!count($errors)) {
                $modif = BimpTools::getArrayValueFromPath($data, 'lines_dates_modify', 'none');

                if ($modif !== 'none') {
                    $sc = 'triggerObjectChange(\'bimplocation\', \'BimpLocationLine\');';
                    $id_entrepot = (int) $this->getData('id_entrepot');
                    $lines = $this->getChildrenObjects('lines');

                    foreach ($lines as $line) {
                        $line_errors = array();

                        if ($modif === 'all' || $line->getData('date_from') == $init_from) {
                            $line->set('date_from', $date_from);
                        }

                        if ($modif === 'all' || $line->getData('date_to') == $init_to) {
                            $line->set('date_to', $date_to);
                        }

                        if (!(int) $line->getData('cancelled')) {
                            $line->isEquipmentAvailable(0, '', '', $id_entrepot, $line_errors);
                        }

                        if (!count($line_errors)) {
                            $line_warnings = array();
                            $line_errors = $line->update($line_warnings, true);
                        }

                        if (count($line_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Impossible de modifier les dates de la ligne n°' . $line->getData('position'));
                        }
                    }
                }
            }
        }


        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    // Overrides

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            if ($this->getData('date_to') < $this->getData('date_from')) {
                $errors[] = 'La date de fin est antérieure à la date de début';
            }
        }

        return $errors;
    }

    public function reset()
    {
        parent::reset();

        $this->amounts = null;
        $this->lines_mass_processing = false;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $this->updateField('ref', 'LOC' . $this->id);
        }

        return $errors;
    }
}
