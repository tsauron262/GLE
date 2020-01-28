<?php

class Bimp_user_client_controller extends BimpController {

    function display() {
        global $userClient;
        $this->initUserClient();
        if (BimpTools::isSubmit('ajax')) {
            if ($userClient->isLoged()) {
                parent::display();
            } else {
                die(json_encode(Array("request_id" => $_REQUEST['request_id'], 'nologged' => 1)));
            }
        } else {
            if ($userClient->getData('renew_required') == 1) {
                require_once DOL_DOCUMENT_ROOT . "/bimpinterfaceclient/views/change_password.php";
            } else {
                define('BIMP_CONTROLLER_INIT', 1);
                top_htmlhead('
            <link href="views/css/light-bootstrap-dashboard.css?v=1.4.0" rel="stylesheet"/>
            <link href="views/css/demo.css" rel="stylesheet" />


            <link href="views/css/pe-icon-7-stroke.css" rel="stylesheet" />');
                require_once DOL_DOCUMENT_ROOT . "/bimpinterfaceclient/views/header.php";
                parent::display();

                echo BimpRender::renderAjaxModal('page_modal');

                $this->addDebugTime('Fin affichage page');

                if (BimpDebug::isActive('bimpcore/controller/display_times')) {
                    echo $this->renderDebugTime();
                }

                llxFooter();

                require_once DOL_DOCUMENT_ROOT . "/bimpinterfaceclient/views/footer.php";
            }
        }
    }

    static function initUserClient() {
        global $langs, $userClient;


        define('NOLOGIN', 1);
        require_once '../main.inc.php';
        if (BimpCore::getConf('module_version_bimpinterfaceclient') == "") {
            BimpTools::setContext('private');
            accessforbidden();
        }
        $userClient = BimpObject::getInstance('bimpinterfaceclient', 'BIC_UserClient');
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
        if (!$userClient->isLoged()) {
            require DOL_DOCUMENT_ROOT . '/bimpinterfaceclient/views/login.php';
            die;
        }

        if (isset($_REQUEST['new_lang'])) {
            $userClient->switch_lang($_REQUEST['new_lang']);
        }

        if (isset($_POST['new_password'])) {
            $userClient->change_password($_POST['new_password']);
        }
        $langs->setDefaultLang(BIC_UserClient::$langs_list[$userClient->getData('lang')]);
        $langs->load('bimp@bimpinterfaceclient');
    }

}
