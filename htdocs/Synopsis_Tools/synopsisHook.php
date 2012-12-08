<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
error_reporting(E_ALL ^ E_NOTICE);


include_once(DOL_DOCUMENT_ROOT."/Synopsis_Tools/class/divers.class.php");
include_once(DOL_DOCUMENT_ROOT."/Synopsis_Tools/SynDiversFunction.php");

global $conf;

$conf->global->MAIN_APPLICATION_TITLE = "GLE";
$conf->global->MAIN_MENU_USE_JQUERY_ACCORDION = 0;
$conf->global->MAIN_MODULE_MULTICOMPANY = "1";
$conf->global->MAIN_MODULE_ORANGEHRM = "1";

//$conf->global->PROJET_ADDON = "mod_projet_tourmaline";


$conf->global->devMailTo = 'tommy@drsi.fr';





if (isset($conf->global->MAIN_MODULE_SYNOPSISPROJET)) {
    $conf->projet->enabled = true;
    $conf->projet->dir_output = $conf->synopsisprojet->dir_output;
    $conf->imputations->dir_output = $conf->synopsisprojet->dir_output . "/imputation";
}
//if (isset($conf->global->MAIN_MODULE_SYNOPSISFICHINTER)) {
//    die;
    $conf->ficheinter->enabled = true;
//}

    
    
    
$synopsisHook = new synopsisHook();
global $synopsisHook;
   
$conf->global->MAIN_HTML_HEADER = (isset($conf->global->MAIN_HTML_HEADER)? $conf->global->MAIN_HTML_HEADER : ""). $synopsisHook->getHeader();
?>
