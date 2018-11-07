<?php

require_once DOL_DOCUMENT_ROOT.'/bimpcore/objects/Bimp_Societe.class.php';

class Bimp_Client extends Bimp_Societe
{
    public $forceTpye = "client";
    
    public function __construct($module, $object_name) {
//        $this->forceTpye = "";
        parent::__construct($module, $object_name);
    }
}
