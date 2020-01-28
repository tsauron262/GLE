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
        
        $list = $obj->getListFiltre("my");
        
        return $list->renderHtml();
    }
    
}