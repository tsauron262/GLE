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
        
        $list = $obj->getListFiltre();

        
        return $list->renderHtml();
    }
    
}
