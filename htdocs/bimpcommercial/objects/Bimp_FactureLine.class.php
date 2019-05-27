<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/ObjectLine.class.php';

class Bimp_FactureLine extends ObjectLine
{

    public static $parent_comm_type = 'facture';
    public static $dol_line_table = 'facturedet';
    public $equipment_required = true;

    // Gestion des droits: 
    public function canCreate()
    {
        global $user;
        if ($user->rights->facture->paiement) {
            return 1;
        }

        return 0;
    }
}
