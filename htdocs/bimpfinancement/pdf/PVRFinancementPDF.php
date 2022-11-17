<?php

require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/DocFinancementPDF.php';

class PVRFinancementPDF extends DocFinancementPDF
{

    public static $doc_type = 'pvr';

    public function __construct($db, $demande)
    {
        parent::__construct($db, $demande);

        $this->doc_name = 'Contrat de location';
    }
    
    
}
