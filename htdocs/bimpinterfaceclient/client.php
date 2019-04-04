<?php
require_once '../bimpcore/main.php';

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';


BimpTools::setContext("public");


// Désactivation de l'autantification DOLIBARR
define('NOLOGIN', 1);


// REQUIREMENTS
require_once '../main.inc.php';
//require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

if (BimpCore::getConf('module_version_bimpinterfaceclient') == "") {
    accessforbidden();
}
$userClient = BimpObject::getInstance('bimpinterfaceclient', 'BIC_UserClient');
if (isset($_REQUEST['lang'])) {
    $userClient->switch_lang($_REQUEST['lang']);
}
// Connexion du client
if ($_POST['identifiant_contrat']) {
    if ($userClient->connexion($_POST['identifiant_contrat'], $_POST['pass'])) {
        $_SESSION['userClient'] = $_POST['identifiant_contrat'];
    } else {
        echo BimpRender::renderAlerts("Ce compte n'existe pas / plus", 'info', false);
    }
}
// Déconnexion du client
elseif ($_REQUEST['action'] == 'deconnexion') {
    $userClient->deconnexion();
}

// Si le client est connecter
$userClient->init();
if ($userClient->isLoged()) {

    if (isset($_REQUEST['lang'])) {
        $userClient->switch_lang($_REQUEST['lang']);
    }
    $langs->setDefaultLang(BIC_UserClient::$langs_list[$userClient->getData('lang')]);
    $userClient->runContxte();
    $request = isset($_REQUEST['fc']);
    if ($request) {
        $content_request = $_REQUEST['fc'];
    }
    $couverture = $userClient->my_soc_is_cover();
    //$couverture = Array();
    BimpCore::displayHeaderFiles();
    define('BIMP_NO_HEADER', 1);
    
    
    require_once DOL_DOCUMENT_ROOT . "/bimpinterfaceclient/views/header.php";

    $nameController = $_REQUEST['fc'] ? $_REQUEST['fc'] : 'index';
    $controller = BimpController::getInstance('bimpinterfaceclient', $nameController);
    $controller->display();
    
    require_once DOL_DOCUMENT_ROOT . "/bimpinterfaceclient/views/footer.php";
    
    if (isset($_POST['new_passwd'])) {
        $passwd = hash('sha256', $_POST['new_passwd']);
        $userClient->password = $passwd;
        $userClient->change_password();
    }
} else {
    require DOL_DOCUMENT_ROOT . '/bimpinterfaceclient/views/login.php';
}
?>
