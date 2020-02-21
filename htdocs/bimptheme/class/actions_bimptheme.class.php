<?php

class Actionsbimptheme {

    function doActions($parameters, &$object, &$action, $hookmanager) {
    }

    function setContentSecurityPolicy($parameters, &$object, &$action, $hookmanager) {
        global $user, $db, $conf;
        
        
    }
    

}

global $conf;
if($conf->global->MAIN_THEME == "eldybimp"){
    function llxHeader (){
        echo "header";
        echo "menu";
    }
    
    
}