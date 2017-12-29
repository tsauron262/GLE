<?php

class BMP_TypeMontant extends BimpObject
{

    public static $types = array(
        1 => array('label' => 'Frais', 'icon' => '', 'classes' => array('danger')),
        2 => array('label' => 'Recette', 'icon' => '', 'classes' => array('success'))
    );

    public function getCategoriesArray()
    {
        $instance = BimpObject::getInstance('bimpmargeprod', 'BMP_CategorieMontant');
        $rows = $instance->getList();
        $categories = array();

        foreach ($rows as $r) {
            $categories[$r['id']] = $r['name'];
        }

        return $categories;
    }

    public function getAllTypes()
    {
        $rows = $this->getList(array(), null, null, 'id', 'asc', 'array', array('id', 'name'));
        $list = array();

        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $list[$r['id']] = $r['name'];
            }
        }
        
        return $list;
    }
}
