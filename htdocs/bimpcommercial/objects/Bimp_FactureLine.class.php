<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/ObjectLine.class.php';

class Bimp_FactureLine extends ObjectLine
{

    public static $parent_comm_type = 'facture';
    public static $dol_line_table = 'facturedet';
    public static $dol_line_parent_field = 'fk_facture';
    public $equipment_required = true;
    public static $equipment_required_in_entrepot = false;

    // Droits user: 

    public function canCreate()
    {
        global $user;
        if (/* $user->rights->facture->paiement */$user->rights->bimpcommercial->factureAnticipe) {
            return 1;
        }

        return 0;
    }

    public function canSetAction($action)
    {
        global $user;

        switch ($action) {
            case 'bulkCreateRevalorisation':
                if ($user->admin) {
                    return 1;
                }
                return 0;
        }
        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isEquipmentAvailable(Equipment $equipment = null)
    {
        // Aucune vérif pour les factures (L'équipement est attribué à titre indicatif)
        return array();
    }

    public function isRemiseEditable()
    {
        return $this->isParentDraft();
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        switch ($field) {
            case 'pa_editable':
                return 1;

            case 'remise_crt':
            case 'remise_crt_percent':
                if (!$this->isParentDraft()) {
                    return 0;
                }
                break;

            case 'qty':
                if (!$force_edit) {
                    if ($this->getData('linked_object_name') === 'commande_line') {
                        return 0;
                    }
                }
                break;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isActionAllowed($action, &$errors = array())
    {
//        switch ($action) {
//            case 'attributeEquipment':
//                if ($this->getData('linked_object_name') === 'commande_line') {
//                    $errors[] = 'L\'attribution d\'équipement doit être faite depuis la page logistique de la commande';
//                    return 0;
//                }
//                break;
//        }

        return (int) parent::isActionAllowed($action, $errors);
    }

    public function isTypeProductAllowed()
    {
        $facture = $this->getParentInstance();

        if (BimpObject::objectLoaded($facture)) {
            $comms = $facture->getCommandesOriginList();
            if (count($comms)) {
                return 0;
            }
        }

        return 1;
    }

    // Getters params: 

    public function getListExtraBtn()
    {
        $buttons = parent::getListExtraBtn();

        if ($this->isLoaded() && $this->isNotTypeText()) {
            $facture = $this->getParentInstance();
            if (BimpObject::objectLoaded($facture)) {
                $reval = BimpObject::getInstance('bimpfinanc', 'BimpRevalorisation');
                $onclick = $reval->getJsLoadModalForm('default', 'Ajout d\\\'une revalorisation', array(
                    'fields' => array(
                        'id_facture'      => (int) $facture->id,
                        'id_facture_line' => (int) $this->id
                    )
                ));

                $buttons[] = array(
                    'label'   => 'Ajouter une revalorisation',
                    'icon'    => 'fas_search-dollar',
                    'onclick' => $onclick
                );
            }
        }

        return $buttons;
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'type_soc':
                if (!isset($joins['facture'])) {
                    $joins['facture'] = array(
                        'alias' => 'facture',
                        'table' => 'facture',
                        'on'    => 'facture.rowid = a.id_obj'
                    );
                }
                if (!isset($joins['soc'])) {
                    $joins['soc'] = array(
                        'alias' => 'soc',
                        'table' => 'societe',
                        'on'    => 'soc.rowid = facture.fk_soc'
                    );
                }

                $filters['soc.fk_typent'] = array(
                    ($excluded ? 'not_' : '') . 'in' => $values
                );
                break;
        }

        return parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors, $excluded);
    }

    // Getters Array: 

    public function getTypesArray()
    {
        global $current_bc;

        if (is_a($current_bc, 'BC_Form') || is_a($current_bc, 'BC_Field')) {
            if (!$this->isTypeProductAllowed()) {
                return array(
                    self::LINE_TEXT => 'Text libre'
                );
            }
        }

        return parent::getTypesArray();
    }

    public function getRevalTypesArray()
    {
        BimpObject::loadClass('bimpfinanc', 'BimpRevalorisation');
        return BimpRevalorisation::$types;
    }

    // Getters données: 

    public function getPaWithRevalorisations()
    {
        $pa = $this->pa_ht;

        if ($this->isLoaded()) {
            $revals = BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpRevalorisation', array(
                        'id_facture_line' => (int) $this->id,
                        'type'            => 'correction_pa',
                        'status'          => array(
                            'in' => array(0, 1)
                        )
            ));

            foreach ($revals as $reval) {
                $pa -= (float) $reval->getData('amount');
            }
        }

        return $pa;
    }

    // Affichages: 

    public function displayRevalorisations()
    {
        $html = '';
        if ($this->isLoaded()) {
            $total_attente = 0;
            $total_accepted = 0;
            $total_refused = 0;

            $revals = BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpRevalorisation', array(
                        'id_facture_line' => (int) $this->id
            ));

            foreach ($revals as $reval) {
                switch ((int) $reval->getData('status')) {
                    case 0:
                        $total_attente += (float) $reval->getTotal();
                        break;

                    case 1:
                        $total_accepted += (float) $reval->getTotal();
                        break;

                    case 2:
                        $total_refused += (float) $reval->getTotal();
                        break;
                }
            }

            if ($total_attente) {
                $html .= '<span class="warning">';
                $html .= BimpRender::renderIcon('fas_hourglass-start', 'iconLeft');
                $html .= BimpTools::displayMoneyValue($total_attente);
                $html .= '</span>';
            }

            if ($total_accepted) {
                if ($html) {
                    $html .= '<br/>';
                }
                $html .= '<span class="success">';
                $html .= BimpRender::renderIcon('fas_check', 'iconLeft');
                $html .= BimpTools::displayMoneyValue($total_accepted);
                $html .= '</span>';
            }

            if ($total_refused) {
                if ($html) {
                    $html .= '<br/>';
                }
                $html .= '<span class="danger">';
                $html .= BimpRender::renderIcon('fas_times', 'iconLeft');
                $html .= BimpTools::displayMoneyValue($total_refused);
                $html .= '</span>';
            }
        }

        return $html;
    }

    // Rendus HTML: 

    public function renderQuickAddForm($bc_list)
    {
        if (!$this->isTypeProductAllowed()) {
            return '';
        }

        return parent::renderQuickAddForm($bc_list);
    }

    // Traitements:

    public function onFactureValidate()
    {
        if ($this->isLoaded()) {
            if ($this->isProductSerialisable()) {
                // Enregistrements des données de la vente dans les équipements: 
                $eq_lines = $this->getEquipmentLines();

                foreach ($eq_lines as $eq_line) {
                    $equipment = $eq_line->getChildObject('equipment');

                    if (BimpObject::ObjectLoaded($equipment)) {
                        $pu_ht = (float) $this->getUnitPriceHTWithRemises();
                        $pu_ttc = BimpTools::calculatePriceTaxIn($pu_ht, (float) $this->tva_tx);

                        $equipment->set('prix_vente', $pu_ttc);
                        $equipment->set('vente_tva_tx', (float) $this->tva_tx);
                        $equipment->set('date_vente', date('Y-m-d H:i:s'));
                        $equipment->set('id_facture', (int) $this->getData('id_obj'));
                
                        if(!static::useLogistique()){
                            $facture = $this->getParentInstance();
                            $place = $equipment->getCurrentPlace();
                            if (BimpObject::ObjectLoaded($place) && BimpObject::ObjectLoaded($facture)) {
                                if($place->getData('type') != BE_Place::BE_PLACE_CLIENT || $place->getData('id_client') != $facture->getData('fk_soc'))
                                    $equipment->moveToPlace(BE_Place::BE_PLACE_CLIENT, $facture->getData('fk_soc'), 'Vente '.$facture->id, 'Vente : '.$facture->getRef(), 1);
                            }
                        }

                        $warnings = array();
                        $equipment->update($warnings, true);
                    }
                }
            }

            $this->checkPrixAchat();
        }
    }

    public function onSave(&$errors = array(), &$warnings = array())
    {
        if ($this->isLoaded()) {
            if ($this->getData('linked_object_name') === 'commande_line') {
                $facture = $this->getParentInstance();

                if (!BimpObject::objectLoaded($facture) || !$facture->areLinesEditable()) {
                    return;
                }

                $commLine = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $this->getData('linked_id_object'));
                if (BimpObject::objectLoaded($commLine)) {
                    $commande = $commLine->getParentInstance();

                    if (BimpObject::objectLoaded($commande)) {
                        $commande->processFacturesRemisesGlobales();
                    }
                }
            }

            $this->checkPrixAchat();
        }

        parent::onSave($errors, $warnings);
    }

    public function onEquipmentAttributed($id_equipment)
    {
        if ($this->isLoaded()) {
            $facture = $this->getParentInstance();

            if (BimpObject::objectLoaded($facture) && (int) $facture->getData('fk_statut') > 0) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                if (BimpObject::objectLoaded($equipment)) {
                    $pu_ht = (float) $this->getUnitPriceHTWithRemises();
                    $pu_ttc = BimpTools::calculatePriceTaxIn($pu_ht, (float) $this->tva_tx);

                    $equipment->set('prix_vente', $pu_ttc);
                    $equipment->set('vente_tva_tx', (float) $this->tva_tx);
                    $equipment->set('date_vente', date('Y-m-d H:i:s'));
                    $equipment->set('id_facture', (int) $this->getData('id_obj'));

                    $warnings = array();
                    $equipment->update($warnings, true);
                }
                
                
                if(!static::useLogistique()){
                    $place = $equipment->getCurrentPlace();
                    if($place->getData('type') != BE_Place::BE_PLACE_CLIENT || $place->getData('id_client') != $facture->getData('fk_soc'))
                        $equipment->moveToPlace(BE_Place::BE_PLACE_CLIENT, $facture->getData('fk_soc'), 'Vente '.$facture->id, 'Vente : '.$facture->getRef(), 1);
                }
            }
        }
    }

    public function checkPrixAchat()
    {
        $errors = array();
        if ($this->isLoaded($errors)) {
            $pa_ht = $this->calcPrixAchat();
            $errors = $this->updatePrixAchat($pa_ht);
        }
        return $errors;
    }

    public function calcPrixAchat($date = null, &$details = array(), &$errors = array())
    {
        $pa_ht = (float) $this->pa_ht;
        $fullQty = (float) $this->getFullQty();

        if (is_null($date)) {
            $facture = $this->getParentInstance();
            if (BimpObject::objectLoaded($facture)) {
                $date = $facture->getData('datec');
            } else {
                $date = '';
            }
        }

        if ((int) $this->getData('type') === self::LINE_PRODUCT && (int) $this->getData('pa_editable') && $fullQty > 0) {
            $product = $this->getProduct();
            if (BimpObject::objectLoaded($product)) {
                if ($product->isSerialisable()) {
                    $cur_pa_ht = null;
                    $errors = BimpTools::merge_array($errors, $this->calcPaByEquipments(false, $date, $pa_ht, $cur_pa_ht, $details));
                } else {
                    $commande_line = null;
                    if ($this->getData('linked_object_name') === 'commande_line' && (int) $this->getData('linked_id_object')) {
                        $commande_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $this->getData('linked_id_object'));
                    }

                    $def_pa_ht = 0;
                    $def_pa_label = '';

                    if ((int) $product->getData('no_fixe_prices')) {
                        if (BimpObject::objectLoaded($commande_line)) {
                            $comm_ref = (string) $this->db->getValue('commande', 'ref', 'rowid = ' . (int) $commande_line->getData('id_obj'));

                            if ($comm_ref) {
                                $def_pa_ht = (float) $commande_line->pa_ht;
                                $def_pa_label = 'PA commande client ' . $comm_ref;
                            }
                        }

                        if (!$def_pa_ht) {
                            $def_pa_ht = (float) $this->pa_ht;
                            $def_pa_label = 'PA enregistré dans cette ligne de facture';
                        }
                    } else {
                        $def_pa_ht = (float) $product->getCurrentPaHt(null, true, $date);
                        if ($date) {
                            $dt = new DateTime($date);
                            $def_pa_label = 'PA courant du produit au ' . $dt->format('d / m / Y');
                        } else {
                            $def_pa_label = 'PA courant du produit';
                        }
                    }

                    $remain_qty = $fullQty;
                    $total_achats = 0;

                    if (BimpObject::objectLoaded($commande_line)) {
                        // Recherche des PA réels dans les factures fourn, BR et commandes fourn.
                        $comm_fourn_lines = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_CommandeFournLine', array(
                                    'linked_object_name' => 'commande_line',
                                    'linked_id_object'   => (int) $commande_line->id
                        ));

                        foreach ($comm_fourn_lines as $cf_line) {
                            $comm_fourn_data = $this->db->getRow('commande_fournisseur', 'rowid = ' . (int) $cf_line->getData('id_obj'), array('ref', 'fk_statut'), 'array');

                            if (is_null($comm_fourn_data)) {
                                continue;
                            }

                            $cf_line_remain_qty = (float) $cf_line->qty;
                            if ($cf_line_remain_qty > $remain_qty) {
                                $cf_line_remain_qty = $remain_qty;
                            }

                            if (!$cf_line_remain_qty) {
                                continue;
                            }

                            $remain_qty -= $cf_line_remain_qty;
                            $cf_line_pu_ht = (float) $cf_line->getUnitPriceHTWithRemises();

                            $fac_fourn_lines = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_FactureFournLine', array(
                                        'linked_object_name' => 'commande_fourn_line',
                                        'linked_id_object'   => (int) $cf_line->id
                            ));

                            // Vérification des lignes de factures fourn: 
                            foreach ($fac_fourn_lines as $ff_line) {
                                $ff_line_qty = (float) $ff_line->getFullQty();
                                if ($ff_line_qty > $cf_line_remain_qty) {
                                    $ff_line_qty = $cf_line_remain_qty;
                                }

                                if (!$ff_line_qty) {
                                    continue;
                                }

                                $fac_fourn_data = $this->db->getRow('facture_fourn', 'rowid = ' . (int) $ff_line->getData('id_obj'), array('ref', 'fk_statut'), 'array');
                                if (!is_null($fac_fourn_data)) {
                                    $total_achats += ($ff_line->pu_ht * $ff_line_qty);
                                    $detail = 'PA Facture fournisseur ' . $fac_fourn_data['ref'];
                                    if ((int) $fac_fourn_data['fk_statut'] === 0) {
                                        $detail .= ' <span class="warning">(non validée)</span>';
                                    }
                                    $detail .= ' pour ' . $ff_line_qty . ' unité(s) : ' . BimpTools::displayMoneyValue((float) $ff_line->pu_ht);
                                    $details[] = $detail;
                                    $cf_line_remain_qty -= $ff_line_qty;
                                }
                            }

                            if ($cf_line_remain_qty > 0) {
                                // Vérification des réceptions validées non facturées: 
                                $receptions = $cf_line->getData('receptions');
                                foreach ($receptions as $id_reception => $reception_data) {
                                    if (!isset($reception_data['received']) || !(int) $reception_data['received']) {
                                        continue;
                                    }

                                    $br_values = $this->db->getRow('bl_commande_fourn_reception', 'id = ' . (int) $id_reception, array('num_reception', 'ref', 'status', 'id_facture'), 'array');
                                    if (!is_null($br_values)) {
                                        $br_qty = (float) $reception_data['qty'];
                                        if ($br_qty > $cf_line_remain_qty) {
                                            $br_qty = $cf_line_remain_qty;
                                        }

                                        if (!$br_qty) {
                                            continue;
                                        }

                                        // Calcul PA moyen de la réception: 
                                        $br_total_qty = 0;
                                        $br_total_amount = 0;
                                        if (isset($reception_data['qties'])) {
                                            foreach ($reception_data['qties'] as $qty_data) {
                                                $br_total_qty += (float) $qty_data['qty'];
                                                $pu_ht = (float) (isset($qty_data['pu_ht']) ? $qty_data['pu_ht'] : $cf_line_pu_ht);
                                                $br_total_amount += ((float) $qty_data['qty'] * $pu_ht);
                                            }
                                        }

                                        if ($br_total_qty > 0) {
                                            $pu_moyen = $br_total_amount / $br_total_qty;
                                            $detail = 'PA réception n°' . $br_values['num_reception'] . ' - ' . $br_values['ref'];
                                            $detail .= ' (Commande fournisseur ' . $comm_fourn_data['ref'] . ')';
                                            $detail .= ' pour ' . $br_qty . ' unité(s) - Moyenne: ' . BimpTools::displayMoneyValue($pu_moyen);
                                            $details[] = $detail;
                                            $cf_line_remain_qty -= $br_qty;
                                            $total_achats += ($pu_moyen * $br_qty);
                                        }
                                    }
                                }

                                // Attribution du PA commande fourn pour les qtés restantes: 
                                if ($cf_line_remain_qty > 0) {
                                    $total_achats += ($cf_line_pu_ht * $cf_line_remain_qty);
                                    $detail = 'PA Commande fournisseur ' . $comm_fourn_data['ref'];
                                    if ((int) $comm_fourn_data['fk_statut'] === 0) {
                                        $detail .= ' <span class="warning">(non validée)</span>';
                                    }
                                    $detail .= ' pour ' . $cf_line_remain_qty . ' unité(s) : ' . BimpTools::displayMoneyValue($cf_line_pu_ht);
                                    $details[] = $detail;
                                }
                            }
                        }
                    }

                    // Attribution du PA par défaut pour les qtés restantes: 
                    if ($remain_qty > 0) {
                        $total_achats += ($def_pa_ht * $remain_qty);
                        $details[] = $def_pa_label . ' pour ' . $remain_qty . ' unité(s) : ' . BimpTools::displayMoneyValue($def_pa_ht);
                    }

                    $pa_ht = $total_achats / $fullQty;
                }
            }
        } else {
            $details[] = 'PA enregistré dans la ligne de facture : ' . BimpTools::displayMoneyValue((float) $this->pa_ht);
        }

        return $pa_ht;
    }

    public function findValidPrixAchat($date = '')
    {
        if (!$date) {
            $date = $this->getData('datec');
        }

        $details = array();
        $pa_ht = (float) $this->calcPrixAchat($date, $details);

        return array(
            'pa_ht'  => $pa_ht,
            'origin' => BimpTools::getMsgFromArray($details)
        );
    }

    public function updatePrixAchat($new_pa_ht)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $qty = (float) $this->getFullQty();
            if ($qty) {
                $facture = $this->getParentInstance();

                if (!BimpObject::objectLoaded($facture)) {
                    $errors[] = 'ID de la facture absent';
                } else {
                    // Création de revalorisations si facture commissionnée / Màj directe en base sinon. 
                    if ((int) $facture->getData('id_user_commission') || (int) $facture->getData('id_entrepot_commission')) {
                        $total_reval = ((float) $this->pa_ht - (float) $new_pa_ht) * $qty;

                        // Check des revals existantes: 
                        $revals = BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpRevalorisation', array(
                                    'id_facture'      => (int) $facture->id,
                                    'id_facture_line' => (int) $this->id,
                                    'type'            => 'correction_pa'
                        ));

                        foreach ($revals as $reval) {
                            // Déduction du montant des revals validées / suppr. des autres. 
                            if ((int) $reval->getData('status') === 1) {
                                $total_reval -= (float) $reval->getTotal();
                            } else {
                                $w = array();
                                $del_errors = $reval->delete($w, true);
                                if (count($del_errors)) {
                                    $total_reval -= (float) $reval->getTotal();
                                }
                            }
                        }

                        if ($total_reval) {
                            $reval_amount = ($total_reval / $qty);

                            // Créa nouvelle revalorisation: 
                            $reval = BimpObject::getInstance('bimpfinanc', 'BimpRevalorisation');
                            $reval_errors = $reval->validateArray(array(
                                'id_facture'      => (int) $facture->id,
                                'id_facture_line' => (int) $this->id,
                                'type'            => 'correction_pa',
                                'qty'             => (float) $qty,
                                'amount'          => (float) $reval_amount,
                                'date'            => date('Y-m-d'),
                                'note'            => 'Correction du prix d\'achat après ajout de la facture à une commission (Nouveau prix d\'achat: ' . $new_pa_ht . ')'
                            ));

                            if (!count($reval_errors)) {
                                $reval_warnings = array();
                                $reval_errors = $reval->create($reval_warnings, true);
                            }

                            if (count($reval_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($reval_errors, 'Echec de la création ' . $reval->getLabel('of_the'));
                            }
                        }
                    } else {
                        return parent::updatePrixAchat($new_pa_ht);
                    }
                }
            }
        }

        return $errors;
    }

    // Actions: 

    public function actionBulkCreateRevalorisation($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_lines = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

        if (!count($id_lines)) {
            $errors[] = 'Aucune lignes de factures à traiter';
        } else {
            $type = BimpTools::getArrayValueFromPath($data, 'type', '', $errors, 1, 'Type de revalorisation absent');
            $date = BimpTools::getArrayValueFromPath($data, 'date', date('Y-m-d'));
            $amount_type = BimpTools::getArrayValueFromPath($data, 'amount_type', '', $errors, 1, 'Type de montant absent');
            $amount = BimpTools::getArrayValueFromPath($data, 'amount', null);
            $note = BimpTools::getArrayValueFromPath($data, 'note', '');

            if (is_null($amount)) {
                $errors[] = 'Aucun montant spécitifé';
            }

            $nOK = 0;
            if (!count($errors)) {
                foreach ($id_lines as $id_line) {
                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', $id_line);

                    if (!BimpObject::objectLoaded($line)) {
                        $warnings[] = 'La ligne de facture d\'ID ' . $id_line . ' n\'existe plus';
                        continue;
                    }

                    $facture = $line->getParentInstance();

                    if (!BimpObject::objectLoaded($facture)) {
                        $warnings[] = 'Aucune facture trouvée pour la ligne de facture #' . $id_line;
                        continue;
                    }

                    $reval_amount = 0;

                    switch ($amount_type) {
                        case 'new_pa':
                            $pa_ht = (float) $line->getPaWithRevalorisations();
                            $reval_amount = $pa_ht - $amount;
                            break;

                        default:
                        case 'reval':
                            $reval_amount = $amount;
                            break;
                    }
                    $rev_errors = array();
                    $rev_warnings = array();

                    BimpObject::createBimpObject('bimpfinanc', 'BimpRevalorisation', array(
                        'id_facture'      => (int) $facture->id,
                        'id_facture_line' => (int) $line->id,
                        'type'            => $type,
                        'date'            => $date,
                        'amount'          => $reval_amount,
                        'qty'             => (float) $line->getFullQty(),
                        'note'            => $note
                            ), true, $rev_errors, $rev_warnings);

                    if (count($rev_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($rev_errors, 'Facture "' . $facture->getRef() . '" - Ligne n°' . $line->getData('position'));
                    } else {
                        $nOK++;
                    }

                    if (count($rev_warnings)) {
                        $warnings[] = BimpTools::getMsgFromArray($rev_warnings, 'Facture "' . $facture->getRef() . '" - Ligne n°' . $line->getData('position'));
                    }
                }

                if ($nOK > 0) {
                    $success = $nOK . ' revalorisation(s) créée(s) avec succès';
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
        $errors = array();
        $details = array();

        $this->pa_ht = (float) $this->calcPrixAchat(date('Y-m-d H:i:s'), $details, $errors);
        $this->id_fourn_price = 0;

        if (count($errors)) {
            return $errors;
        }

        return parent::create($warnings, $force_create);
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $commLine = null;
        $id_facture = (int) $this->getData('id_obj');

        if ($this->isLoaded()) {
            if ($this->getData('linked_object_name') === 'commande_line' && (int) $this->getData('linked_id_object')) {
                $commLine = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $this->getData('linked_id_object'));
            }
        }

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors)) {
            if (BimpObject::objectLoaded($commLine)) {
                $commLine->onFactureDelete($id_facture);
            }
        }

        return $errors;
    }
}
