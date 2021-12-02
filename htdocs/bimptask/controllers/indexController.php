<?php

class indexController extends BimpController
{

    public function displayHead()
    {
    }

    public function renderHtml()
    {
        global $user;
        $tabs = array();
        $obj = BimpObject::getInstance('bimptask', 'BIMP_Task');
        $list = $obj->getListFiltre("my");
        $list2 = $obj->getListFiltre("byMy");
        
        
        
        
        $tabs[] = array(
            'id'      => 'my',
            'title'   => 'Mes tâches assignées',
            'content' => $list->renderHtml()
        );
        $tabs[] = array(
            'id'      => 'byMy',
            'title'   => 'Mes tâches créer',
            'content' => $list2->renderHtml()
        );

        return BimpRender::renderNavTabs($tabs);
    }
    
}