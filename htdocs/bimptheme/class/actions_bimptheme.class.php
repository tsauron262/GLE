<?php



function initHeaderBimp(){
    define('NOREQUIREMENU', 1);
    if(!function_exists('llxHeader')){
        function llxHeader (){
            echo "header";
            echo "menu";
        }
    }
    else{
        //pas cool deja chargé, ca ne marchera pas...
    }
        
}

class Actionsbimptheme {
    
    function __construct() {
        
        //pas terible copier coller de main.inc mais pas encore chargé au bon moment.
        global $conf, $user;  
        $conf->theme = $conf->global->MAIN_THEME;
        // Replace conf->css by personalized value if theme not forced
        if (empty($conf->global->MAIN_FORCETHEME) && !empty($user->conf->MAIN_THEME)) 
            $conf->theme = $user->conf->MAIN_THEME;

        // Case forcing style from url
        if (GETPOST('theme', 'alpha'))
            $conf->theme = GETPOST('theme', 'alpha', 1);

        if($conf->theme == "eldybimp")
            initHeaderBimp();
    }

    function doActions($parameters, &$object, &$action, $hookmanager) {
    }

    function setContentSecurityPolicy($parameters, &$object, &$action, $hookmanager) {
        
        
    }
    

}