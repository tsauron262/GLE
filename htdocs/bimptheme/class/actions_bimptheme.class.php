<?php

function initHeaderBimp(){
    if(!defined('NOREQUIREHTML')){
          global $jsCssBimp;
        define('NOREQUIREMENU', 1);
        require_once(DOL_DOCUMENT_ROOT."/bimpcore/Bimp_Lib.php");
        $jsCssBimp = BimpCore::displayHeaderFiles(false);
        if(!function_exists('llxHeader')){
            function llxHeader() {
                include_once(DOL_DOCUMENT_ROOT . "theme/BimpTheme/views/header.php");

                include_once(DOL_DOCUMENT_ROOT . "theme/BimpTheme/views/menu.php");
            }
        }
        else {
            header("Refresh:0");
            //pas cool deja chargé, ca ne marchera pas...
        }

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

        if($conf->theme == "BimpTheme")
            initHeaderBimp();
    }

    function doActions($parameters, &$object, &$action, $hookmanager) {
    }

    function setContentSecurityPolicy($parameters, &$object, &$action, $hookmanager) {
        
        
    }
    

}