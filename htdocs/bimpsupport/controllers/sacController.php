<?php

class sacController extends BimpController
{

    public function display()
    {
        $obj = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Sac', $_GET['id']);
        if($obj->isLoaded()){
            $list = $obj->getSav();
            if(count($list) == 1){
                foreach($list as $obj){
                    header('Location: '.DOL_URL_ROOT.'/bimpsupport?fc=sav&id='.$obj->id);
                    die;
                }
            }
        }
        
        return parent::display();
    }
}
