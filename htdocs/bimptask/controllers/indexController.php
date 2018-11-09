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
        return $list->renderHtml();
    }
    
    }