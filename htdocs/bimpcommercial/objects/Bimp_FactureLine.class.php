<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/ObjectLine.class.php';

class Bimp_FactureLine extends ObjectLine
{

    public static $parent_comm_type = 'facture';
    public static $dol_line_table = 'facturedet';
    public static $dol_line_parent_field = 'fk_facture';
    public $equipment_required = true;
    public static $equipment_required_in_entrepot = false;

    // Gestion des droits: 

    public function canCreate()
    {
        global $user;
        if (/* $user->rights->facture->paiement */$user->rights->bimpcommercial->factureAnticipe) {
            return 1;
        }

        return 0;
    }

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

                        $warnings = array();
                        $equipment->update($warnings, true);
                    }
                }

                $this->calcPaByEquipments();
            }
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

                $commande = $commLine->getParentInstance();
                if ($commande->id === 88354) {
                    return;
                }
                
                $rg = BimpCache::findBimpObjectInstance('bimpcommercial', 'ObjectLineRemise', array(
                            'id_object_line'    => (int) $this->id,
                            'object_type'       => 'facture',
                            'is_remise_globale' => 1
                                ), true);

                $new_rate = 0;

                if (BimpObject::objectLoaded($commLine)) {
                    $new_rate = (float) $commLine->getFactureLineRemiseGlobaleRate((int) $this->getData('id_obj'));

                    if (!$new_rate) {
                        if (BimpObject::objectLoaded($rg)) {
                            $rg->delete();
                        }
                    } else {
                        if (!BimpObject::objectLoaded($rg)) {
                            $commande = $commLine->getParentInstance();

                            $rg = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemise');
                            $rg_errors = $rg->validateArray(array(
                                'id_object_line'    => (int) $this->id,
                                'object_type'       => 'facture',
                                'label'             => 'Part de la remise globale sur la commande ' . (BimpObject::objectLoaded($commande) ? $commande->getRef() : ' (inconnue)'),
                                'type'              => 1,
                                'percent'           => $new_rate,
                                'is_remise_globale' => 1
                            ));

                            if (!count($rg_errors)) {
                                $rg_warnings = array();
                                $rg_errors = $rg->create($rg_warnings, true);

                                if (count($rg_warnings)) {
                                    $warnings[] = BimpTools::getMsgFromArray($rg_warnings, 'Erreurs lors de la création de la remise globale');
                                }
                            }

                            if (count($rg_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($rg_errors, 'Echec de la création de la remise globale');
                            }
                        } elseif ((float) $rg->getData('percent') !== $new_rate) {
                            $rg->set('percent', $new_rate);
                            $rg_warnings = array();
                            $rg_errors = $rg->update($rg_warnings, true);
                            if (count($rg_warnings)) {
                                $warnings[] = BimpTools::getMsgFromArray($rg_warnings, 'Erreurs lors de la mise à jour de la remise globale');
                            }
                            if (count($rg_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($rg_errors, 'Echec de la mise à jour de la remise globale');
                            }
                        }
                    }
                }
            }
        }

        parent::onSave($errors, $warnings);
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
            }
        }
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        if ((int) $this->getData('type') === self::LINE_PRODUCT && (int) $this->getData('pa_editable')) {
            $product = $this->getProduct();
            if (!BimpObject::objectLoaded($product)) {
                $errors[] = 'Produit absent';
            } elseif (!(int) $product->getData('no_fixe_prices')) {
                $new_pa = (float) $product->getCurrentPaHt();

                if ($new_pa) {
                    $this->pa_ht = $new_pa;
                    $this->id_fourn_price = 0;
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        $errors = parent::create($warnings, $force_create);
    }
}
