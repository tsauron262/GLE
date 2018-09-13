<?php

class BF_FraisFournisseur extends BimpObject
{

    // Affichages: 
    
    public function displaySupplier($display_name)
    {
        if ($this->isLoaded()) {

            $id_supplier = (int) $this->getData('id_soc_supplier');
            if ($id_supplier) {
                return $this->displayData('id_soc_supplier', $display_name);
            } else {
                $name = $this->getData('supplier_name');
                if (!is_null($name)) {
                    return $name;
                }
            }
        }

        return '';
    }
    
    // Traitements: 
     
    public function createFile()
    {
        
    }
    
    // Overrides: 
    
    public function create()
    {
        
    }
}
