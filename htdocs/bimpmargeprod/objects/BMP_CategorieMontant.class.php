<?php

class BMP_CategorieMontant extends BimpObject
{

    public function getCategories()
    {
        $rows = $this->getList();
        $categories = array();

        foreach ($rows as $r) {
            $categories[$r['id']] = $r['name'];
        }

        return $categories;
    }
}
