<?php

class BL_CommandeFournReception extends BimpObject
{

    // Getters booléens: 

    public function isFieldEditable($field)
    {
        if ($field === 'id_entrepot' && $this->isLoaded()) {
            return 0;
        }

        return parent::isFieldEditable($field);
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

    public function renderDetailsView()
    {
        $html = '';

        $commandes = $this->getParentInstance();

        $lines_data = array();

        if (!BimpObject::objectLoaded($commandes)) {
            $html .= BimpRender::renderAlerts('ID de la commande fournisseur absent');
        } else {
            $lines = $commandes->getChildrenObjects('lines');
            foreach ($lines as $line) {
                $data = $line->getReceptionData($this->id);
                if ((float) $data['qty'] > 0) {
                    $lines_data[$line->id] = $data;
                    $lines_data[$line->id]['line'] = $line;
                }
            }
        }

        if (empty($lines_data)) {
            $html .= BimpRender::renderAlerts('Aucune ligne enregistée pour cette réception');
        } else {
            $html .= '<table class="bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Ligne</th>';
            $html .= '<th>Qté</th>';
            $html .= '<th>Prix unitaire HT</th>';
            $html .= '<th>Tx TVA</th>';
            $html .= '<th>Equipements</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            foreach ($lines_data as $line_data) {
                $line = $line_data['line'];
                $html .= '<tr>';
                $html .= '<td>' . $line->displayLineData('desc') . '</td>';
                $html .= '<td>' . $line_data['qty'] . '</td>';
                $html .= '<td>';
                foreach ($line_data['pa_ht'] as $pa_ht => $qty) {
                    if ((float) $qty !== (float) $line_data['qty']) {
                        $html .= $qty . ': ';
                    }
                    $html .= BimpTools::displayMoneyValue((float) $pa_ht) . '<br/>';
                }
                $html .= '</td>';
                $html .= '<td>';
                foreach ($line_data['tva_tx'] as $tva_tx => $qty) {
                    if ((float) $qty !== (float) $line_data['qty']) {
                        $html .= $qty . ': ';
                    }
                    $html .= BimpTools::displayFloatValue((float) $tva_tx) . '%<br/>';
                }
                $html .= '</td>';
                $html .= '<td>';
                
                $product = $line->getProduct();
                if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                foreach ($line_data['equipments'] as $id_equipment) {
                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                    if (BimpObject::objectLoaded($equipment)) {
                        $html .= $equipment->getNomUrl(1, 1, 1) . '<br/>';
                    }
                }
                } else {
                    $html .= '<span class="warning">Non sérialisable</span>';
                }
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }
        return $html;
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

        $lines = $commande->getChildrenObjects('lines', array(
            'type' => Bimp_CommandeFournLine::LINE_PRODUCT
        ));

        // Vérification du non-dépassement des qtés max: 
        foreach ($lines as $line) {
            $product = $line->getProduct();
            if (BimpObject::objectLoaded($product) && (int) $product->getData('fk_product_type') === 0) {
                $remain_qty = (float) $line->qty - (float) $line->getReceivedQty();
                if ($product->isSerialisable()) {
                    $serials = BimpTools::isSubmit('line_' . $line->id . '_reception_1_equipments', '');
                    if ($serials) {
                        $serials = str_replace("\n", ';', $serials);
                        $serials = str_replace(" ", ';', $serials);
                        $serials = str_replace(",", ';', $serials);
                        $serials = explode(';', $serials);
                        foreach ($serials as $idx => $serial) {
                            if (!(string) $serial) {
                                unset($serials[$idx]);
                            }
                        }
                        $qty = count($serials);
                        if ($qty > $remain_qty) {
                            $msg = 'Il ne reste que ' . $remain_qty . ' équipement(s) à réceptionner pour cette ligne de commande fournisseur.<br/>';
                            $msg .= 'Veuillez retirer ' . ($qty - $remain_qty) . ' numéro(s) de série';
                            $errors[] = BimpTools::getMsgFromArray($msg, 'Ligne n°' . $line->getData('position'));
                        }
                    }
                } else {
                    $qty = (float) BimpTools::getValue('line_' . $line->id . '_reception_1_qty', 0);
                    if ($qty > 0) {
                        if ($qty > $remain_qty) {
                            $msg = 'Il ne reste que ' . $remain_qty . ' unité(s) à réceptionner pour cette ligne de commande fournisseur.<br/>';
                            $msg .= 'Veuillez retirer ' . ($qty - $remain_qty) . ' unité(s)';
                            $errors[] = BimpTools::getMsgFromArray($msg, 'Ligne n°' . $line->getData('position'));
                        }
                    }
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            foreach ($lines as $line) {
                $product = $line->getProduct();
                if (BimpObject::objectLoaded($product) && (int) $product->getData('fk_product_type') === 0) {
                    $data = array();

                    if ($product->isSerialisable()) {
                        $data['serials'] = BimpTools::getValue('line_' . $line->id . '_reception_1_equipments', '');
                        if (!(string) $data['serials']) {
                            continue;
                        }
                        $data['assign_to_commande_client'] = BimpTools::getValue('line_' . $line->id . '_assign_to_commande_client', 0);
                    } else {
                        $data['qty'] = BimpTools::getValue('line_' . $line->id . '_reception_1_qty', 0);
                        if ($qty <= 0) {
                            continue;
                        }
                    }

                    $data['pa_ht'] = BimpTools::getValue('line_' . $line->id . '_reception_1_pu_ht', null);
                    $data['tva_tx'] = BimpTools::getValue('line_' . $line->id . '_reception_1_tva_tx', null);

                    $line_errors = $line->addReception($this, $data);
                    if (count($line_errors)) {
                        $warnings = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
                    }
                }
            }
            
            $commande->checkReceptionStatus();
        }

        return $errors;
    }
}
