<?php

class BF_FraisFournisseur extends BimpObject
{

    public function displaySupplier($display_name)
    {
        if (isset($this->id) && $this->id) {

            $id_supplier = $this->getData('id_soc_supplier');
            if (!is_null($id_supplier) && $id_supplier) {
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
}
