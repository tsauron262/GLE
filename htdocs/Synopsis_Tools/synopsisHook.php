<?php

date_default_timezone_set('Europe/Paris');

ini_set('display_errors', 1);

$builddoc = (isset($_REQUEST['action']) && ($_REQUEST['action'] != 'generatePdf' || $_REQUEST['action'] != 'builddoc'));
$viewDoc = (stripos($_SERVER['REQUEST_URI'], 'document'));
$modDev = defined('MOD_DEV_SYN') ? MOD_DEV_SYN : 0;

if(($modDev == 2 && !$builddoc && !$viewDoc) || ($modDev == 1))
    error_reporting(E_ALL);
else
    error_reporting(E_ALL ^ (E_NOTICE));

//if ($builddoc && defined('MOD_DEV_SYN') && MOD_DEV_SYN)
//    error_reporting(E_ALL ^ (E_STRICT));
//elseif ($builddoc)
//    error_reporting(E_ALL ^ (E_NOTICE | E_STRICT));
//elseif (defined('MOD_DEV_SYN') && MOD_DEV_SYN)
//    error_reporting(E_ALL);
//else

//if (defined('MOD_DEV_SYN') && MOD_DEV_SYN && (MOD_DEV_SYN == 2 || !isset($_REQUEST['action']) || ($_REQUEST['action'] != 'generatePdf' && $_REQUEST['action'] != 'builddoc')))
//    error_reporting(E_ALL);
//else
//    error_reporting(E_ALL ^ (E_NOTICE));

ini_set('upload_max_filesize', 10000);
ini_set('post_max_size', 10000);


setlocale(LC_TIME, 'fr_FR.utf8', 'fra');


include_once(DOL_DOCUMENT_ROOT . "/Synopsis_Tools/class/divers.class.php");
include_once(DOL_DOCUMENT_ROOT . "/Synopsis_Tools/SynDiversFunction.php");

global $conf, $langs, $user;

$conf->global->MAIN_MAX_DECIMALS_TOT = 5;
$conf->global->MAIN_MAX_DECIMALS_UNIT = 5;
$conf->global->MAIN_MAX_DECIMALS_SHOWN = 2;

$conf->global->MAIN_APPLICATION_TITLE = "GLE";
$conf->global->MAIN_MENU_USE_JQUERY_ACCORDION = 0;
$conf->global->MAIN_MODULE_MULTICOMPANY = "1";
$conf->global->MAIN_MODULE_ORANGEHRM = "1";

$conf->global->PRODUIT_CONFIRM_DELETE_LINE = "1";

define('PREF_BDD_ORIG', 'llx_');


$conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER = false;

//$conf->global->PROJET_ADDON = "mod_projet_tourmaline";


$conf->global->devMailTo = 'tommy@drsi.fr';


$tabProductType = array("Product", "Service", "Produit de contrat", "Déplacement", "Déplacement contrat");
$tabTypeLigne = array("Titre", "Sous-Titre", "Sous-Titre avec remise à 0", "Note", "Saut de page", "Sous-total", "Description");
if (is_object($langs)) {
    foreach ($tabProductType as $idT => $val)
        $tabProductType[$idT] = $langs->trans($val);
    foreach ($tabTypeLigne as $idT => $val)
        $tabTypeLigne[$idT] = $langs->trans($val);
}
$tabTypeLigne = array_merge($tabProductType, $tabTypeLigne);
global $tabProductType, $tabTypeLigne;

$conf->modules_parts['tpl'][] = "/Synopsis_Tools/tpl/";




$synopsisHook = new synopsisHook();
global $synopsisHook;


$conf->global->MAIN_HTML_HEADER = (isset($conf->global->MAIN_HTML_HEADER) ? $conf->global->MAIN_HTML_HEADER : "") . $synopsisHook->getHeader();



//$date = new DateTime();
//$date2 = new DateTime("2013/04/01");
//if($date > $date2)
//    die("Logiciel desactiv&eacute;. Contacter Synopsis.");
?>
