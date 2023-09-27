<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

$type_test = BimpTools::getValue('type', '');
if (!$type_test) {
    echo BimpRender::renderAlerts('Type de test absent');
    exit;
}

switch ($type_test) {
    case 'sms':
        $num = BimpTools::getValue('num', '');
        if (!$num) {
            echo BimpRender::renderAlerts('Numéro de tel. absent (param url "num"');
        } else {
            require_once(DOL_DOCUMENT_ROOT . "/core/class/CSMSFile.class.php");

            global $conf;
            $conf->global->MAIN_DISABLE_ALL_SMS = 0;
            $conf->global->MAIN_SMS_DEBUG = 1;

            $num = str_replace(" ", "", $num);
            if (stripos($num, "+") === false)
                $num = "+33" . substr($num, 1, 10);
            
            $smsfile = new CSMSFile($num, 'TEST', 'Test');
            if (!$smsfile->sendfile()) {
                echo BimpRender::renderAlerts('ECHEC - ' . BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($smsfile)));
            } else {
                echo BimpRender::renderAlerts('Envoi OK', 'success');
            }
        }
        break;
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
