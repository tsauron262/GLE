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
}
