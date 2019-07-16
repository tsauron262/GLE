<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/FournObjectLine.class.php';

class Bimp_FactureFournLine extends FournObjectLine
{

    public static $parent_comm_type = 'facture_fournisseur';
    public static $dol_line_table = 'facture_fourn_det';
    public static $dol_line_parent_field = 'fk_facture_fourn';
    public $equipment_required = true;
    public static $equipment_required_in_entrepot = false;

    public function isEquipmentAvailable(Equipment $equipment = null)
    {
        // Aucune vérif pour les factures fourn (L'équipement est attribué à titre indicatif)
        return array();
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        switch ($field) {
            case 'qty':
                if (!$force_edit) {
                    if ($this->getData('linked_object_name') === 'commande_fourn_line') {
                        return 0;
                    }
                }
                break;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function onFactureValidate()
    {
        if ($this->isLoaded()) {
            if ($this->isProductSerialisable()) {
                // Enregistrements des données de l'achat dans les équipements: 
                $eq_lines = $this->getEquipmentLines();

                foreach ($eq_lines as $eq_line) {
                    $equipment = $eq_line->getChildObject('equipment');

                    if (BimpObject::ObjectLoaded($equipment)) {
                        $pa_ht = $eq_line->getData('pu_ht');
                        $tva_tx = $eq_line->getData('tva_tx');

                        if (is_null($pa_ht)) {
                            $pa_ht = (float) $this->pu_ht;
                        }

                        if (is_null($tva_tx)) {
                            $tva_tx = (float) $this->tva_tx;
                        }

                        if (!is_null($this->remise) && (float) $this->remise > 0) {
                            $pa_ht -= ($pa_ht * ((float) $this->remise / 100));
                        }

                        $equipment->set('prix_achat', $pa_ht);
                        $equipment->set('achat_tva_tx', $tva_tx);

                        $warnings = array();
                        $equipment->update($warnings, true);
                    }
                }
            }
        }
    }
}
