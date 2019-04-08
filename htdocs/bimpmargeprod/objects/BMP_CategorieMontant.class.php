<?php

require_once DOL_DOCUMENT_ROOT."/bimpmargeprod/objects/Abstract_margeprod.class.php";

class BMP_CategorieMontant extends Abstract_margeprod
{

    public function canDelete()
    {
        global $user;
        
        if ($user->admin) {
            return 1;
        }
        
        return 0;
    }
    
    public function getCategories($include_empty = 0)
    {
        return self::getBimpObjectFullListArray($this->module, $this->object_name, $include_empty);
    }
}
