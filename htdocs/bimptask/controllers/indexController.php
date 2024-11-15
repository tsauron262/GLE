<?php

class indexController extends BimpController
{

    public function displayHead()
    {
    }

    public function renderHtml()
    {
        $user = BimpCore::getBimpUser();
        if (BimpObject::objectLoaded($user)) {
            return $user->renderTasksView();
        }
//        $tabs = array();
//        $obj = BimpObject::getInstance('bimptask', 'BIMP_Task');
//        $tabList = array(
//            "my"      => 'Mes tâches assignées',
//            "byMy"    => 'Mes tâches crées',
//            "all"     => 'Toutes',
//            "orgaDev" => 'Organisation dév'
//        );
//        
//        foreach($tabList as $name => $title){
//            $list = $obj->getListFiltre($name, $title);
//            $tabs[] = array(
//                'id'      => $name,
//                'title'   => $title,
//                'content' => $list->renderHtml()
//            );
//        }
//
//        return BimpRender::renderNavTabs($tabs);
    }
    
}