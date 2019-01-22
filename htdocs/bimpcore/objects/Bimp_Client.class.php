<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/Bimp_Societe.class.php';

class Bimp_Client extends Bimp_Societe
{

    public $soc_type = "client";

    public function __construct($module, $object_name)
    {
//        $this->forceTpye = "";
        parent::__construct($module, $object_name);
    }

    public function getRefProperty()
    {
        return 'code_client';
    }

    public function getSearchListFilters()
    {
        return array(
            'client' => 1
        );
    }
}
