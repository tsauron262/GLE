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
                        $equipment->set('prix_achat', $pa_ht);
                        $equipment->set('achat_tva_tx', $tva_tx);
                        $equipment->set('date_vente', date('Y-m-d H:i:s'));
                        $equipment->set('id_facture', (int) $this->getData('id_obj'));

                        $warnings = array();
                        $equipment->update($warnings, true);
                    }
                }
            }
        }
    }
}
