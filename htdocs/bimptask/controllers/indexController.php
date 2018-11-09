<?php

class indexController extends BimpController
{

    public function displayHead()
    {
    }

    public function renderHtml()
    {
        global $user;
        $obj = BimpObject::getInstance('bimptask', 'BIMP_Task');

        $list = new BC_ListTable($obj, 'default', 1, null, 'Vos taches assignées');
        $list->addFieldFilterValue('id_user_owner', (int) $user->id);
        $tabFiltre = $obj::getFiltreDstRight($user);
        print_r($tabFiltre);die;
        if(count($tabFiltre[1])>0)
            $list->addFieldFilterValue('dst', array(
                $tabFiltre[0] => implode(',', $tabFiltre[1])
            ));
        return $list->renderHtml();
    }
    
    }