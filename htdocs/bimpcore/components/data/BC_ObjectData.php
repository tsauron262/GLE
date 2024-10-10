<?php

namespace BC_V2;

class BC_ObjectData extends BC_Data
{

    public $object = null;    
    
    public function __construct(BimpObject $object, $yml_config_path = '', $yml_config_name = '', $user_config = null)
    {
        $this->object = $object;
        $yml_config = null;
        if (is_a($object, 'BimpObject')) {
            $yml_config = $object->config;
        }
        
        parent::__construct(array(), $yml_config, $yml_config_path, $yml_config_name, $user_config);
    }
    
    public function isValid(&$errors = array())
    {
        if (is_null($this->object)) {
            $errors[] = 'Objet lié absent';
            return 0;
        }
        
        if (!is_a($this->object, 'BimpObject')) {
            $errors[] = 'Objet lié invalide';
            return 0;
        }
        
        1;
    }
}
