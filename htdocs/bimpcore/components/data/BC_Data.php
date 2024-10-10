<?php

namespace BC_V2;

class BC_Data
{

    public $yml_config = null;
    public $user_config = null;
    public $yml_config_name = '';
    public $yml_config_path = '';
    public $params = null;
    public $options = array();
    public $contents = array();
    
    public function __construct($params = null, $yml_config = null, $yml_config_path = '', $yml_config_name = '', $user_config = null)
    {
        $this->params = $params;
        $this->yml_config = $yml_config;
        $this->yml_config_path = $yml_config_path;
        $this->yml_config_name = $yml_config_name;
        $this->user_config = $user_config;
    }
    public function setCurrentOptions($current_options)
    {
        $this->current_options = $current_options;
    }
    
    public function isValid(&$errors = array())
    {
        return 1;
    }
    
    public function setParam($param_name, $value)
    {
        $this->params[$param_name] = $value;
    }
    
    public function getParam($param_path, $default_value = null)
    {
        
    }
    
    public function getOption()
    {
        
    }
}
