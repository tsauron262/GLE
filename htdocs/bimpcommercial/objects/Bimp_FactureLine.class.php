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
        if ($user->rights->facture->paiement) {
            return 1;
        }

        return 0;
    }

    public function isEquipmentAvailable(Equipment $equipment = null)
    {
        // Aucune vérif pour les factures (L'équipement est attribué à titre indicatif)
        return array();
    }
    
    public function isFieldEditable($field, $force_edit = false)
    {
        switch ($field) {
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
                        $pu_ht = $eq_line->getData('pu_ht');
                        $pa_ht = $eq_line->getData('pa_ht');
                        $tva_tx = $eq_line->getData('tva_tx');

                        if (is_null($pu_ht)) {
                            $pu_ht = (float) $this->pu_ht;
                        }

                        if (is_null($tva_tx)) {
                            $tva_tx = (float) $this->tva_tx;
                        }

                        if (is_null($pa_ht)) {
                            $pa_ht = (float) $this->pa_ht;
                        }

                        if (!is_null($this->remise) && (float) $this->remise > 0) {
                            $pu_ht -= ($pu_ht * ((float) $this->remise / 100));
                        }

                        $pu_ttc = BimpTools::calculatePriceTaxIn($pu_ht, $tva_tx);

                        $equipment->set('prix_vente', $pu_ttc);
                        $equipment->set('vente_tva_tx', $tva_tx);
//                        $equipment->set('prix_achat', $pa_ht);
//                        $equipment->set('achat_tva_tx', $tva_tx);
                        $equipment->set('date_vente', date('Y-m-d H:i:s'));
                        $equipment->set('id_facture', (int) $this->getData('id_obj'));

                        $warnings = array();
                        $equipment->update($warnings, true);
                    }
                }
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
}
