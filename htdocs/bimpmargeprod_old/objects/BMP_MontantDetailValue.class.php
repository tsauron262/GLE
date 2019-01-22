<?php

class BMP_MontantDetailValue extends BimpObject
{

    public function getTypes_montantsArray()
    {
        $typesMontants = array();
        $instance = BimpObject::getInstance($this->module, 'BMP_TypeMontant');
        $list = $instance->getList(array(
            'has_details' => 1
        ));

        foreach ($list as $item) {
            $typesMontants[(int) $item['id']] = $item['name'];
        }

        return $typesMontants;
    }
}
