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
    
    public static function getNextAlert($id = 0, $type = 'popup'){
        if($id == 0){
            global $user;
            $id = $user->array_options['options_popup_alert_id'];
        }
        
        $filtre = array('status'=>1, "type" => 1);
        if($id > 0)
            $filtre['id'] = array('operator' => ">", "value" => $id);
        $objs = BimpCache::getBimpObjectObjects('bimpcore', 'BimpAlert', $filtre, 'id', 'asc');
        $html = '';
        foreach($objs as $alert){
            if($alert->getData('conditionExec') != '')
                $exec = 0;
            else
                $exec = 1;
            eval($alert->getData('conditionExec'));
            if($exec){
                return $alert;
            }

        }
        return null;
    }
    
    
    public static function getMsgs(){
        if(!BimpTools::getValue('ajax')){
            $objs = BimpCache::getBimpObjectObjects('bimpcore', 'BimpAlert', array('status'=>1, 'type'=>0), 'position');
            $html = '';
            foreach($objs as $alert){
                if($alert->getData('conditionExec') != '')
                    $exec = 0;
                else
                    $exec = 1;
                eval($alert->getData('conditionExec'));
                if($exec){
                    $msg = $alert->getData('msg');
                    if($alert->getData('execution') != '')
                        $html .= eval($alert->getData('execution'));
                    if($msg != '')
                        $html .= BimpRender::renderAlerts($msg, self::$niveaux[$alert->getData('niveau')]['classes'][0]);
                    
                }

            }
//        echo '<pre>';print_r($_SERVER);
            return $html;
        }
    }
    
    public function getMsg(){
        $msg = $this->getData('msg');
        if($this->getData('execution') != '')
            eval($this->getData('execution'));
        $msg = nl2br($msg);
        return $msg;
    }
    
    public function getPopup($id, $titre){
//        return "<script>".$this->getJsActionOnclick('loadAlert').'</script>';
        
        $onclick = $this->getJsActionOnclick('isViewed', array('id' => $id));
        $return = "bimpModal.loadAjaxContent($(this), 'loadAlertModal', {id: '$id'}, '".$titre."', 'Chargement', function (result, bimpAjax) {});bimpModal.show();";
        $return .= "bimpModal.addButton('Ok', \"".$onclick."\", 'primary', 'is_viewed', modal_idx);";
        return $return;
        return '<script>$(document).ready(function () {'.$return.'});</script>';
    }
    
    public function loadAlert(){
        die('rfrfrfrfrf');
    }
    
    public function actionIsViewed($data, &$success){
        
        $success = 'Vue';
        $errors = $warnings = array();
        
        global $user;
        $user->array_options['options_popup_alert_id'] = $data['id'];
        $user->update($user);
        
        $obj = $this->getNextAlert($data['id']);
        
        if($obj)
            $callback = $this->getPopup($obj->id, $obj->getData('label'));
        else
            $callback = 'bimpModal.clearCurrentContent()';
        
        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'success_callback' => $callback
        );
    }
    
}
