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
        $tabList = array(
            "my"      => 'Mes tâches assignées',
            "byMy"    => 'Mes tâches crées',
            "all"     => 'Toutes',
            "orgaDev" => 'Organisation dév'
        );
        
        foreach($tabList as $name => $title){
            $list = $obj->getListFiltre($name, $title);
            $tabs[] = array(
                'id'      => $name,
                'title'   => $title,
                'content' => $list->renderHtml()
            );
        }
        
//        $list = $obj->getListFiltre("my");
//        $list2 = $obj->getListFiltre("byMy");
//        $list3 = $obj->getListFiltre();
//        $list4 = $obj->getListFiltre("orgaDev");
//        
//        
//        
//        
//        $tabs[] = array(
//            'id'      => 'my',
//            'title'   => 'Mes tâches assignées',
//            'content' => $list->renderHtml()
//        );
//        $tabs[] = array(
//            'id'      => 'byMy',
//            'title'   => 'Mes tâches crées',
//            'content' => $list2->renderHtml()
//        );
//        $tabs[] = array(
//            'id'      => 'all',
//            'title'   => 'Toutes',
//            'content' => $list3->renderHtml()
//        );
//        $tabs[] = array(
//            'id'      => 'orga',
//            'title'   => 'Orga dév',
//            'content' => $list4->renderHtml()
//        );

        return BimpRender::renderNavTabs($tabs);
    }
    
}