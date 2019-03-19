<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/FournObjectLine.class.php';

class Bimp_CommandeFournLine extends FournObjectLine
{

    public static $parent_comm_type = 'commande_fournisseur';
    public static $dol_line_table = 'commande_fournisseurdet';

    // Getters booléens: 

    public function isEditable($force_edit = false)
    {
        if (!$force_edit && !(int) $this->getData('editable')) {
            return 0;
        }

        $parent = $this->getParentInstance();
        if (!BimpObject::objectLoaded($parent)) {
            return 0;
        }

//        if ($parent->field_exists('fk_statut') && in_array((int) $parent->getData('fk_statut'), array(0, 1))) {
//            return 1;
//        }

        return 1;
    }

    // Getters données: 

    public function getReceivedQty($id_reception = null)
    {
        $receptions = $this->getData('receptions');

        $qty = 0;

        foreach ($receptions as $reception) {
            if (!is_null($id_reception) && ((int) $reception['id_reception'] !== (int) $id_reception)) {
                continue;
            }
            $qty += (float) $reception['qty'];
        }

        return $qty;
    }

    public function getReceptionData($id_reception)
    {
        $receptions = $this->getData('receptions');
        if (isset($receptions[$id_reception])) {
            return $receptions[$id_reception];
        }

        return array(
            'id_reception' => $id_reception,
            'qty'          => 0,
            'equipments'   => array(),
            'tav_tx'       => array(),
            'pa_ht'        => array(),
        );
    }

    // Getters config: 

    public function getLogistiqueBulkActions()
    {
        return array();
    }

    public function getLogistiqueExtraButtons()
    {
        $buttons = array();

        if ((float) $this->getReceivedQty() < (float) $this->qty) {
            $buttons[] = array(
                'label'   => 'Ajouter à une réception',
                'icon'    => 'fas_arrow-circle-down',
                'onclick' => $this->getJsLoadModalView('reception', 'Ligne n° ' . $this->getData('position') . ' - ajout à une réception')
            );
        }

        return $buttons;
    }

    // Affichages: 

    public function displayCommandeClient()
    {
        $html = '';

        if ($this->getData('linked_object_name') === 'commande_line') {
            $id_line = (int) $this->getData('linked_id_object');
            if ($id_line) {
                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $id_line);
                if (!BimpObject::objectLoaded($line)) {
                    if (is_null($line)) {
                        $line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');
                    }
                    $html .= $this->renderChildUnfoundMsg('linked_object_name', $line, true);
                } else {
                    $commande = $line->getParentInstance();
                    if (BimpObject::objectLoaded($commande)) {
                        $url = DOL_MAIN_URL_ROOT . '/bimplogistique/index.php?fc=commande&id=' . $commande->id;
                        $html .= $commande->getNomUrl(1, 1, 1, 'full');
                        $html .= '&nbsp;&nbsp;&nbsp;<a href="' . $url . '" target="_blank">Logistique' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';
                        $html .= '<br/>';
                        $html .= 'Ligne n°' . $line->getData('position');
                    } else {
                        $html .= BimpRender::renderAlerts('Erreur: Commande absente pour la ligne de commande d\'ID ' . $id_line);
                    }
                }
            }
        }

        return $html;
    }

    public function displayQties()
    {
        $html = '';

        $total_qty = (float) $this->qty;

        // Qté totale
        $html .= '<span class="bold bs-popover"' . BimpRender::renderPopoverData('Qtés commandées') . '>';
        $html .= BimpRender::renderIcon('fas_cart-arrow-down', 'iconLeft');
        $html .= $total_qty;
        $html .= '</span>';

        // Qté reçues:
        $qty_received = (float) $this->getReceivedQty();

        if ($qty_received <= 0) {
            $class = 'danger';
        } elseif ($qty_received < $total_qty) {
            $class = 'warning';
        } else {
            $class = 'success';
        }

        $html .= '<span class="bs-popover ' . $class . '" style="display: inline-block; margin-left: 15px"';
        $html .= BimpRender::renderPopoverData('Qtés réceptionnées');
        $html .= '>';
        $html .= BimpRender::renderIcon('fas_arrow-circle-down', 'iconLeft');
        $html .= $qty_received;
        $html .= '</span>';

//        // Qté facturée: 
//        $qty_billed = (float) $this->getBilledQty();
//        if ($qty_billed <= 0) {
//            $class = 'danger';
//        } elseif ($qty_billed < $total_qty) {
//            $class = 'warning';
//        } else {
//            $class = 'success';
//        }
//        $html .= '<span class="bs-popover ' . $class . '" style="display: inline-block; margin-left: 15px"';
//        $html .= BimpRender::renderPopoverData('Qtés ajoutées à une facture');
//        $html .= '>';
//        $html .= BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft');
//        $html .= $qty_billed;
//        $html .= '</span>';

        return $html;
    }

    // Rendus Html: 

    public function renderReceptionForm()
    {
        $html = '';

        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de la ligne de commande fournisseur absent');
        }

        $commande = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            return BimpRender::renderAlerts('ID de la commande fournisseur absent');
        }

        if ((int) $this->getData('type') === self::LINE_PRODUCT) {
            $product = $this->getProduct();
            if (!BimpObject::objectLoaded($product)) {
                $html .= BimpRender::renderAlerts('Aucun produit associé à cette ligne');
            } else {
                $qty = (float) $this->qty - (float) $this->getReceivedQty();
                if ($qty <= 0) {
                    $html .= BimpRender::renderAlerts('Toutes les unités de cette ligne de commande fournisseur ont déjà été réceptionnées', 'warning');
                } else {
                    $html .= BimpRender::renderAlerts('Ce formulaire est destiné à ajouter des unités à une ou plusieurs réceptions existantes.</br>La création de nouvelles réceptions se fait via le bouton "Nouvelle réception"', 'info');

                    $html .= '<div id="commande_fourn_line_' . $this->id . '_receptions_rows" class="line_reception_rows line_' . $this->id . '_reception_max_container" data-id_line="' . $this->id . '" data-id_commande="' . $commande->id . '">';
                    $html .= '<table class="bimp_list_table">';
                    $html .= '<thead>';
                    $html .= '<tr>';
                    $html .= '<th>Réception</th>';
                    $html .= '<th>Qté / NS</th>';
                    $html .= '<th>Prix d\'achat</th>';
                    $html .= '<th>Tx TVA</th>';
                    $html .= '<th></th>';
                    $html .= '</tr>';
                    $html .= '</thead>';

                    $html .= '<tbody class="receptions_rows">';

                    $tpl = $this->renderReceptionFormRowTpl(true);

                    $html .= '<tr class="line_' . $this->id . '_reception_row_tpl line_reception_row_tpl subObjectFormTemplate" style="display: none" data-next_idx="2">';
                    $html .= $tpl;
                    $html .= '</tr>';

                    $html .= '<tr class="line_' . $this->id . '_reception_row line_reception_row" data-idx="1">';
                    $tpl = str_replace('receptionidx', '1', $tpl);
                    $tpl = str_replace('linetotalmaxinputclass', 'line_' . $this->id . '_reception_max', $tpl);
                    $tpl .= '<td></td>';
                    $html .= $tpl;

                    $html .= '</tr>';

                    $html .= '</tbody>';
                    $html .= '</table>';

                    if ($this->getData('linked_object_name') === 'commande_line') {
                        $commande_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $this->getData('linked_id_object'));
                        if (BimpObject::objectLoaded($commande_line)) {
                            $commande_client = $commande_line->getParentInstance();
                            if (BimpObject::objectLoaded($commande)) {
                                $html .= '<div style="margin: 15px 0; padding: 10px 0;">';
                                $html .= 'Commande client associée: ' . $commande_client->getNomUrl(1, 1, 1) . '<br/>';
                                $product = $this->getProduct();
                                if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                                    $html .= '<div style="vertical-align: top">';
                                    $html .= '<span style="display: inline-block; padding-top: 6px; vertical-align: top">';
                                    $html .= 'Assigner les équipements reçus à cette commande client: ';
                                    $html .= '</span>';
                                    $html .= BimpInput::renderInput('toggle', 'assign_to_commande_client', 0);
                                    $html .= '</div>';
                                }
                                $html .= '</div>';
                            }
                        }
                    }

                    $html .= '<div class="ajaxResultsContainer" style="display: none"></div>';
                    $html .= '<div class="buttonsContainer align-right">';
                    $html .= '<span class="btn btn-default" onclick="addCommandeFournLineReceptionRow($(this), ' . $this->id . ');">';
                    $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter une réception';
                    $html .= '</span>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
            }
        } else {
            $html .= BimpRender::renderAlerts('Aucun produit à réceptionner pour cette commande fournisseur', 'info');
        }

        return $html;
    }

    public function renderReceptionFormRowTpl($include_receptions = false)
    {
        $qty = (float) $this->qty - (float) $this->getReceivedQty();

        if ($qty <= 0) {
            return '';
        }

        $product = $this->getProduct();
        if (!BimpObject::objectLoaded($product)) {
            return '';
        }

        $tpl = '';

        if ($include_receptions) {
            $commande = $this->getParentInstance();
            $receptions = array();
            if (BimpObject::objectLoaded($commande)) {
                $receptions = $commande->getReceptionsArray(false);
            }

            $id_reception = 0;
            foreach ($receptions as $id_r => $reception_label) {
                $id_reception = (int) $id_r;
                break;
            }

            $tpl .= '<td>';
            $tpl .= BimpInput::renderInput('select', 'line_' . $this->id . '_reception_receptionidx_id_reception', $id_reception, array(
                        'options' => $receptions
            ));
            $tpl .= '</td>';
        }

        $tpl .= '<td>';
        if ($product->isSerialisable()) {
            $tpl .= BimpInput::renderInput('textarea', 'line_' . $this->id . '_reception_receptionidx_equipments', '', array(
                        'auto_expand' => true
            ));
            $tpl .= '<p class="inputHelp">N° de série. ';
            $tpl .= 'Séparateurs possibles: sauts de ligne, espaces, virgules ou points-virgules.<br/>';
            $tpl .= 'Max: ' . $qty . ' numéro' . ($qty > 1 ? 's' : '') . ' de série.';
            $tpl .= '</p>';
        } else {
            $tpl .= BimpInput::renderInput('qty', 'line_' . $this->id . '_reception_receptionidx_qty', $qty, array(
                        'data'        => array(
                            'data_type'              => 'number',
                            'min'                    => 0,
                            'max'                    => $qty,
                            'decimals'               => 0,
                            'total_max_value'        => $qty,
                            'total_max_inputs_class' => 'line_' . $this->id . '_reception_max'
                        ),
                        'max_label'   => 1,
                        'extra_class' => 'total_max linetotalmaxinputclass'
            ));
        }

        $tpl .= '</td>';

        $tpl .= '<td>';
        $tpl .= BimpInput::renderInput('text', 'line_' . $this->id . '_reception_receptionidx_pu_ht', $this->pu_ht, array(
                    'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
                    'data'        => array(
                        'data_type' => 'number',
                        'min'       => 'none',
                        'max'       => 'none',
                        'decimals'  => 6,
                        'unsigned'  => 0
                    ),
                    'style'       => 'width: 90px'
        ));
        $tpl .= '</td>';

        $tpl .= '<td>';
        $tpl .= BimpInput::renderInput('text', 'line_' . $this->id . '_reception_receptionidx_tva_tx', $this->tva_tx, array(
                    'addon_right' => BimpRender::renderIcon('fas_percent'),
                    'data'        => array(
                        'data_type' => 'number',
                        'min'       => 0,
                        'max'       => 100,
                        'decimals'  => 6,
                        'unsigned'  => 1
                    ),
                    'style'       => 'width: 90px'
        ));
        $tpl .= '</td>';

        return $tpl;
    }

    // Traitements: 

    public function addReception(BL_CommandeFournReception $reception, $reception_data)
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la ligne de commande fournisseur absent';
            return $errors;
        }

        $product = $this->getProduct();
        if (!BimpObject::objectLoaded($product)) {
            if ((int) $this->id_product) {
                $errors[] = 'Le produit d\'ID ' . $this->id_product . ' n\'existe pas';
            } else {
                $errors[] = 'Aucun produit associé à cette ligne de commande fournisseur';
            }
            return $errors;
        }

        $entrepot = $reception->getChildObject('entrepot');

        if (!BimpObject::objectLoaded($entrepot)) {
            $errors[] = 'Entrepot absent de la réception ou invalide';
            return $errors;
        }

        $commande_fourn = $this->getParentInstance();
        if (!BimpObject::objectLoaded($commande_fourn)) {
            $errors[] = 'ID de la commande fournisseur absent';
            return $errors;
        }

        $pa_ht = isset($reception_data['pa_ht']) && !is_null($reception_data['pa_ht']) ? (float) $reception_data['pa_ht'] : (float) $this->pu_ht;
        $tva_tx = isset($reception_data['tva_tx']) && !is_null($reception_data['tva_tx']) ? (float) $reception_data['tva_tx'] : (float) $this->tva_tx;
        $equipments = array();

        $serialisable = $product->isSerialisable();

        $total_qty = (float) $this->qty;
        $received_qty = (float) $this->getReceivedQty();
        $remain_qty = $total_qty - $received_qty;

        if ($serialisable) {
            $serials = isset($reception_data['serials']) ? $reception_data['serials'] : '';
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
            } else {
                $serials = array();
            }

            $qty = count($serials);

            if ($qty > $remain_qty) {
                $errors[] = 'Il ne reste que ' . $remain_qty . ' équipements à réceptionner pour cette ligne de commande fournisseur.<br/>Veuillez retirer ' . ($qty - $remain_qty) . ' numéro(s) de série';
            }
        } else {
            $qty = isset($reception_data['qty']) ? (float) $reception_data['qty'] : 0;

            if ($qty > $remain_qty) {
                $qty = $remain_qty;
            }
        }

        if (!$qty || count($errors)) {
            return $errors;
        }

        $stock_label = 'Réception n°' . $reception->getData('num_reception') . ' BR: ' . $reception->getData('ref') . ' - Commande fournisseur: ' . $commande_fourn->getData('ref');
        $code_mvt = 'CMDF_' . $commande_fourn->id . '_LN_' . $this->id . '_RECEP_' . $reception->id;

        if ($serialisable) {
            foreach ($serials as $serial) {
                // Création de l'équipement: 
                $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');

                $eq_errors = $equipment->validateArray(array(
                    'id_product'    => $product->id,
                    'type'          => 1, // Dans produits? à sélectionner ? 
                    'serial'        => $serial,
                    'available'     => 1,
                    'date_purchase' => $commande_fourn->getData('date_commande'),
                    'prix_achat'    => $pa_ht
                ));

                $eq_warnings = array();
                if (!count($eq_errors)) {
                    $eq_errors = $equipment->create($eq_warnings, true);
                }

                if (count($eq_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($eq_errors, 'Echec de la création de l\'équipement pour le numéro de série "' . $serial . '"');
                    $qty--;
                }

                if (count($eq_warnings)) {
                    $errors[] = BimpTools::getMsgFromArray($eq_errors, 'Des erreurs sont survenues suite à la création de l\'équipement pour le numéro de série "' . $serial . '"');
                }

                if (!count($eq_errors)) {
                    $equipments[] = $equipment->id;

                    // Création de l'emplacement: 
                    $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                    $pl_errors = $place->validateArray(array(
                        'id_equipment' => (int) $equipment->id,
                        'type'         => BE_Place::BE_PLACE_ENTREPOT,
                        'date'         => date('Y-m-d H:i:s'),
                        'id_entrepot'  => (int) $entrepot->id,
                        'infos'        => $stock_label,
                        'code_mvt'     => $code_mvt
                    ));
                    if (!count($pl_errors)) {
                        $pl_warnings = array();
                        $pl_errors = $place->create($pl_warnings, true);
                    }

                    if (count($pl_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($pl_errors, 'Echec de la création de l\'emplacement pour le numéro de série "' . $serial . '"');
                        $msg = 'ECHEC CREATION EMPLACEMENT EQUIPEMENT - A CORRIGER MANUELLEMENT' . "\n";
                        $msg .= 'Plateforme: ' . DOL_URL_ROOT . ' - Equipement: ' . $equipment->id . ' - Entrepot: ' . $entrepot->id;
                        $msg .= 'Erreurs:' . "\n";
                        $msg .= print_r($pl_errors, 1);
                        dol_syslog($msg, LOG_ERR);
                        mailSyn2('[ERREUR]', 'debugerp@bimp.fr', 'BIMP<no-reply@bimp.fr>', $msg);
                    }
                }
            }
        } else {
            // Incrémentation des stocks produit: 
            global $user;
            $product->dol_object->correct_stock($user, $entrepot->id, $qty, 0, $stock_label, $code_mvt);
        }

        if ($this->getData('linked_object_name') === 'commande_line') {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $this->getData('linked_id_object'));
            if (!BimpObject::objectLoaded($line)) {
                $errors[] = 'La ligne de commande client associée n\'existe plus';
            } else {
                if ($serialisable) {
                    // Attribution des équipements si serialisable.
                    if (isset($reception_data['assign_to_commande_client']) && (int) $reception_data['assign_to_commande_client']) {
                        $line->addEquipments($equipments);
                    }
                } else {
                    // Màj statuts.
                    $line->addReceivedQty($qty);
                }
            }
        }

        $receptions = $this->getData('receptions');

        if (!isset($receptions[(int) $reception->id])) {
            $receptions[(int) $reception->id] = array(
                'id_reception' => (int) $reception->id,
                'qty'          => 0,
                'pa_ht'        => array(),
                'tva_tx'       => array()
            );
            if ($serialisable) {
                $receptions[(int) $reception->id]['equipments'] = array();
            }
        }

        $receptions[(int) $reception->id]['qty'] += $qty;
        if ($serialisable) {
            $receptions[(int) $reception->id]['equipments'] = array_merge($receptions[(int) $reception->id]['equipments'], $equipments);
        }

        if (!isset($receptions[(int) $reception->id]['pa_ht'][(float) $pa_ht])) {
            $receptions[(int) $reception->id]['pa_ht'][(float) $pa_ht] = 0;
        }

        $receptions[(int) $reception->id]['pa_ht'][(float) $pa_ht] += $qty;

        if (!isset($receptions[(int) $reception->id]['tva_tx'][(float) $tva_tx])) {
            $receptions[(int) $reception->id]['tva_tx'][(float) $tva_tx] = 0;
        }

        $receptions[(int) $reception->id]['tva_tx'][(float) $tva_tx] += $qty;

        $this->set('receptions', $receptions);
        $up_warnings = array();
        $up_errors = $this->update($up_warnings, true);

        $up_errors = array_merge($up_errors, $up_warnings);

        if (count($up_errors)) {
            $errors[] = BimpTools::getMsgFromArray($up_errors, 'Des erreurs sont survenues lors de la mise à jour de la ligne de commande fournisseur');
        }

        return $errors;
    }

    // Actions: 

    public function actionReceive($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';


        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddReceptions($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        if (!count($data)) {
            $errors[] = 'Aucunes données reçues';
        } else {
            $product = $this->getProduct();
            if (!BimpObject::objectLoaded($product)) {
                $errors[] = 'Aucun produit enregistré pour cette ligne de commande fournisseur';
            } else {
                // Vérifications: 
                $remain_qty = $this->qty - $this->getReceivedQty();
                $total_qty = 0;
                $i = 1;
                foreach ($data as $reception_data) {
                    if (!isset($reception_data['id_reception']) || !(int) $reception_data['id_reception']) {
                        $errors[] = 'Aucune réception sélectionnée pour l\'ajout n°' . $i;
                    } else {
                        $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $reception_data['id_reception']);
                        if (!BimpObject::objectLoaded($reception)) {
                            $errors[] = 'La réception d\'ID ' . $reception_data['id_reception'] . ' n\'existe pas';
                        } else {
                            if ($product->isSerialisable()) {
                                $serials = $reception_data['serials'];
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
                                } else {
                                    $serials = array();
                                }
                                if (!count($serials)) {
                                    $errors[] = 'Aucun numéro de série spécifié pour l\'ajout n°' . $i;
                                } else {
                                    $total_qty += count($serials);
                                }
                            } else {
                                $total_qty += (isset($reception_data['qty']) ? (int) $reception_data['qty'] : 0);
                            }
                        }
                    }
                    $i++;
                }

                if ($total_qty > $remain_qty) {
                    $errors[] = 'Il ne reste que ' . $remain_qty . ' unité(s) à réceptionner.<br/>Veuillez retirer ' . ($total_qty - $remain_qty) . ' unité(s).';
                }

                if (count($errors)) {
                    return $errors;
                }

                // Ajouts aux réceptions: 
                $i = 1;
                foreach ($data as $reception_data) {
                    $reception = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $reception_data['id_reception']);
                    $reception_errors = $this->addReception($reception, $reception_data);

                    if (count($reception_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($reception_errors, 'Ajout n°' . $i);
                    } else {
                        $success .= ($success ? '<br/>' : '') . 'Ajout n°' . $i . ' (Réception: ' . $reception->getData('ref') . ') effectué avec succès';
                    }

                    $i++;
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}
