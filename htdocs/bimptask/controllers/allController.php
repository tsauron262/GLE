<?php

class allController extends BimpController
{

    public function displayHead()
    {
        
    }

    public function renderHtml()
    {
        global $user;
        $obj = BimpObject::getInstance('bimptask', 'BIMP_Task');

        $list = new BC_ListTable($obj, 'default', 1, null, 'Toutes les taches');
        $tabFiltre = $obj::getFiltreDstRight($user);
        print_r($tabFiltre);
        if (count($tabFiltre[1]) > 0)
            $list->addFieldFilterValue('dst', array(
                'or' => array(
                    'a.id_facture'  => array(
                        'operator' => $tabFiltre[0],
                        'value'    => '("' . implode('","', $tabFiltre[1]) . '")'
                    ),
                    'id_user_owner' => $user->id
                )
            ));
        return $list->renderHtml();
    }
}
