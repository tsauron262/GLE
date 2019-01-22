<?php

class BMP_CategorieMontant extends BimpObject
{

    public function getCategories($include_empty = 0)
    {
        return self::getBimpObjectFullListArray($this->module, $this->object_name, $include_empty);
    }
}
