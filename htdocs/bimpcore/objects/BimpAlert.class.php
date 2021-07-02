<?php


class BimpAlert extends BimpObject
{
    public static $status_list = array(
        0 => array('label' => 'Désactivé', 'icon' => 'fas_times', 'classes' => array('danger')),
        1 => array('label' => 'Actif', 'icon' => 'fas_check', 'classes' => array('success'))
    );
    public static $niveaux = array(
        0 => array('label' => 'Erreur', 'icon' => 'fas_times', 'classes' => array('danger')),
        1 => array('label' => 'Warning', 'icon' => 'fas_check', 'classes' => array('warning')),
        2 => array('label' => 'Success', 'icon' => 'fas_check', 'classes' => array('success'))
    );
    
    public function canEdit() {
        global $user;
        return $user->admin;
    }
    
    
    public static function getMsgs(){
        if(!BimpTools::getValue('ajax')){
            $objs = BimpCache::getBimpObjectObjects('bimpcore', 'BimpAlert', array('status'=>1), 'position');
            $html = '';
            echo($_REQUEST['SERVER_NAME']);
            foreach($objs as $alert){
                if($alert->getData('conditionExec') != '')
                    $exec = 0;
                else
                    $exec = 1;
                eval($alert->getData('conditionExec'));
                if($exec){
                    if($alert->getData('msg') != '')
                        $html .= BimpRender::renderAlerts($alert->getData('msg'), self::$niveaux[$alert->getData('niveau')]['classes'][0]);
                    if($alert->getData('execution') != '')
                        $html .= eval($alert->getData('execution'));
                }

            }
//        echo '<pre>';print_r($_SERVER);
            return $html;
        }
    }
}
