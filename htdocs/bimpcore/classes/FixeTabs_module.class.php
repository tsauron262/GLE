<?php

abstract class FixeTabs_module{
    protected $bimp_fixe_tabs = null;
    protected $user = null;
    
    function __construct($bimp_fixe_tabs, $user) {
        $this->bimp_fixe_tabs = $bimp_fixe_tabs;
        $this->user = $user;
    }
    
    function displayHead(){
        return "";
    }
    
    abstract function init();
    
    abstract function can($right);
}