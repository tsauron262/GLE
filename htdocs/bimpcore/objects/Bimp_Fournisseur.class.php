<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/Bimp_Societe.class.php';

class Bimp_Fournisseur extends Bimp_Societe
{

    public $soc_type = "fournisseur";

    public function getRefProperty()
    {
        return 'code_fournisseur';
    }

    public function getSearchListFilters()
    {
        return array(
            'fournisseur' => 1
        );
    }
}
