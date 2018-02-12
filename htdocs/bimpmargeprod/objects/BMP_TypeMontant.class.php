<?php

class BMP_TypeMontant extends BimpObject
{

    const BMP_TYPE_FRAIS = 1;
    const BMP_TYPE_RECETTE = 2;

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

    public function rebuildAllCalcMontantsCaches()
    {

        $errors = array();
        $calc_montant = BimpObject::getInstance($this->module, 'BMP_CalcMontant');

        $list = $calc_montant->getList(array(), null, null, 'id', 'asc', 'array', array('id'));

        foreach ($list as $item) {
            if ($calc_montant->fetch((int) $item['id'])) {
                $errors = array_merge($errors, $calc_montant->rebuildTypesMontantsCache());
            }
        }


        return $errors;
    }

    public function validate()
    {
        $errors = parent::validate();

        if ((int) $this->getData('has_details')) {
            if ((int) $this->getData('editable')) {
                $errors[] = 'Un type de montant ne peux pas être à la fois éditable et avoir une liste de détails. Veuillez choisir entre l\'un ou l\'autre';
            }
            if (!(int) $this->getData('required')) {
                $errors[] = 'Un type de montant avec une liste de détails doit être obligatoire';
            }
        }
        return $errors;
    }

    public function create()
    {
        $errors = parent::create();

        $errors = array_merge($errors, $this->rebuildAllCalcMontantsCaches());

        return $errors;
    }

    public function update()
    {
        $errors = parent::update();

        $errors = array_merge($errors, $this->rebuildAllCalcMontantsCaches());

        return $errors;
    }

    public function delete()
    {
        $errors = parent::delete();

        $errors = array_merge($errors, $this->rebuildAllCalcMontantsCaches());

        return $errors;
    }
}
