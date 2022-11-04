<?php

class BimpMailLog extends BimpObject
{
    static $traduc = array(
        'request'=>'Envoyé', 
        'delivered'=>'Délivré', 
        'proxy_open' => 'Chargé par proxy',
        'soft_bounce' => 'Soft Bounce',
        'hard_bounce' => 'Hard Bounce',
        'blocked'     => 'Bloqué',
        'click'     => 'Cliqué'
    );
    static $statutError = array('soft_bounce', 'hard_bounce', 'blocked');
    
    function changeStatut($info, $email, $date){
        $str = $this->trad($info);
        if(strtolower($email) != strtolower($this->getData('mail_to')))
            $str .= ' Dest : '.$email;
        
        $this->updateField('old_trace', $str);
        $note = null;
        if(in_array($info, static::$statutError)){
            $this->addNote ($str, null, 0, 0, '', null, 1, null, $this->getData('user_create'), $note);
        }
        else
            $this->addNote($str, null, 0, 0, '', null, null, null, null, $note);
//        $note->updateField('date_create', $date);
    }
    
    function canViewField($field_name) {
        global $user;
        if($field_name == 'msg' && !$user->admin && (stripos($this->getData('msg'), 'mdp') !== false || stripos($this->getData('msg'), 'mot de passe') !== false))
            return 0;
        return 1;
    }
    
    function getObjectUrl(){
        $instance = $this->getParentInstance();
        if($instance)
            return $instance->getLink();
    }
    
    function getParentInstance() {
        $instance = BimpCache::getBimpObjectInstance($this->getData('obj_module'),$this->getData('obj_name'),$this->getData('id_obj'));
        if($instance && $instance->isLoaded())
            return $instance;
        return null;
    }
    
    function getLinkFields($with_card = true){
        return array('obj_module', 'obj_name', 'id_obj');
    }
    
    function trad($str){
        if(isset(static::$traduc[$str]))
            $str = static::$traduc[$str];
        
        return $str;
    }
    
    function getLink($params = array(), $forced_context = ''): string {
        if($this->getData('obj_module') != ''){
            $obj = $this->getParentInstance();
            if($obj){
                $params['after_link'] = '&open=suivi_mail';
                return $obj->getLink($params, $forced_context);
            }
        }
        return parent::getLink($params, $forced_context);
    }
}



