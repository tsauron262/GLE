<?php

date_default_timezone_set('Europe/Paris');

ini_set('display_errors', 1);

$builddoc = (isset($_REQUEST['action']));
if ($builddoc && defined('MOD_DEV_SYN') && MOD_DEV_SYN)
    error_reporting(E_ALL ^ (E_STRICT));
elseif ($builddoc)
    error_reporting(E_ALL ^ (E_NOTICE | E_STRICT));
elseif (defined('MOD_DEV_SYN') && MOD_DEV_SYN)
    error_reporting(E_ALL);
else
    error_reporting(E_ALL ^ (E_NOTICE));

ini_set('upload_max_filesize', 10000);
ini_set('post_max_size', 10000);

setlocale (LC_TIME, 'fr_FR.utf8','fra'); 


include_once(DOL_DOCUMENT_ROOT . "/Synopsis_Tools/class/divers.class.php");
include_once(DOL_DOCUMENT_ROOT . "/Synopsis_Tools/SynDiversFunction.php");

global $conf, $langs;

$conf->global->MAIN_MAX_DECIMALS_TOT = 5;
$conf->global->MAIN_MAX_DECIMALS_UNIT = 5;
$conf->global->MAIN_MAX_DECIMALS_SHOWN = 2;

$conf->global->MAIN_APPLICATION_TITLE = "GLE";
$conf->global->MAIN_MENU_USE_JQUERY_ACCORDION = 0;
$conf->global->MAIN_MODULE_MULTICOMPANY = "1";
$conf->global->MAIN_MODULE_ORANGEHRM = "1";

//$conf->global->PROJET_ADDON = "mod_projet_tourmaline";


$conf->global->devMailTo = 'tommy@drsi.fr';


if (is_object($langs))
    $tabProductType = array(0 => $langs->trans("Product"), 1 => $langs->trans("Service"), 2 => $langs->trans("Produit de contrat"), 3 => $langs->trans("Déplacement"), 4 => $langs->trans("Déplacement contrat"));
global $tabProductType;



$conf->modules_parts['tpl'][] = "/Synopsis_Tools/tpl/";

global $tabTypeLigne;
$tabTypeLigne = array("Product", "Service", "Product", "Titre", "Sous-Titre", "Sous-Titre avec remise à 0", "Note", "Saut de page", "Sous-total", "Description");

if (isset($conf->global->MAIN_MODULE_SYNOPSISPROJET)) {
    @$conf->projet->enabled = true;
    $conf->projet->dir_output = $conf->synopsisprojet->dir_output;
    @$conf->imputations->dir_output = $conf->synopsisprojet->dir_output . "/imputation";
}
if (isset($conf->global->MAIN_MODULE_SYNOPSISFICHEINTER)) {
    @$conf->ficheinter->enabled = true;
    @$user->rights->ficheinter->lire = true;
}




$synopsisHook = new synopsisHook();
global $synopsisHook;

$conf->global->MAIN_HTML_HEADER = (isset($conf->global->MAIN_HTML_HEADER) ? $conf->global->MAIN_HTML_HEADER : "") . $synopsisHook->getHeader();
?>
