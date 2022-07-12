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
            $exec = $alert->isOp();
            if($exec){
                return $alert;
            }

        }
        return null;
    }
    
    public function getNbView(){
        if($this->isLoaded() && $this->getData('type') == 1){
            $sql = $this->db->db->query('SELECT count(*) as nb FROM `'.MAIN_DB_PREFIX.'user_extrafields` WHERE popup_alert_id >= '.$this->id);
            $ln = $this->db->db->fetch_object($sql);
            if(is_array($this->getData('filter')) && count($this->getData('filter')))
                $tot = count($this->getListUserFiltre ());
            else
                $tot = 'tous';
            return $ln->nb . " / " .$tot;
        }
    }
    
    public function getListUserFiltre(){
        
        $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User');
        $filterObj = new BC_FiltersPanel($user);
        $filterObj->setFilters($this->getData('filter'));
        $filters = $joins = array();
        $errors = BimpTools::merge_array($errors, $filterObj->getSqlFilters($filters, $joins));
        $filters['statut'] = 1;

        return BimpCache::getBimpObjectList('bimpcore', 'Bimp_User', $filters, $joins);
    }
    
    public function isOp(){
        if($this->isLoaded()){
            //Condition du filtre user
            if(is_array($this->getData('filter')) && count($this->getData('filter'))){
                $list = $this->getListUserFiltre();
                global $user;
                if(!in_array($user->id, $list))
                        return 0;
            }
        
            //Condition de du filtre exec
            if($this->getData('conditionExec') != '')
                $exec = 0;
            else
                $exec = 1;
            eval($this->getData('conditionExec'));
            if(!$exec)
                return 0;
        }
        return 1;
    }
    
    
    public static function getMsgs(){
        if(!BimpTools::getValue('ajax')){
            $objs = BimpCache::getBimpObjectObjects('bimpcore', 'BimpAlert', array('status'=>1, 'type'=>0), 'position');
            $html = '';
            foreach($objs as $alert){
                $exec = $alert->isOp();
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
        if(stripos($msg, '/>') === false && stripos($msg, '</') === false)
            $msg = nl2br($msg);
        return $msg;
    }
    
    public function getPopup($id, $titre, $withBtn = true){
        $return = "bimpModal.loadAjaxContent($(this), 'loadAlertModal', {id: '$id'}, '".$titre."', 'Chargement', function (result, bimpAjax) {});bimpModal.show();";
        if($withBtn){
            $onclick = $this->getJsActionOnclick('isViewed', array('id' => $id));
            $return .= "bimpModal.addButton('Ok', \"".$onclick."\", 'primary', 'is_viewed', modal_idx);";
        }
        return $return;
    }
    
    public function getListExtraButtons()
    {
        $buttons = array();
        if ($this->isLoaded()) {

            if ((int) $this->getData('type') == 1) {
                $buttons[] = array(
                        'label'   => 'Voir',
                        'icon'    => 'fas_eye',
                        'onclick' => $this->getPopup($this->id, $this->getData('label'), false)
                    );
            }
        }

        return $buttons;
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
