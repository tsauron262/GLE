<?php

define('NOREQUIREHTML', 1);
define('NOCSRFCHECK', 1);
define('NO_REDIRECT_LOGIN', 1);
require_once("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

$errors = $warnings = array();
$mode_dev = BimpCore::isUserDev();
global $user;
$user->fetch(1);

$signature = null;


if ($mode_dev) {
    $body = file_get_contents(DOL_DATA_ROOT . '/docusign_webhook.txt');
} else {
    $body = file_get_contents('php://input');
    file_put_contents(DOL_DATA_ROOT . '/docusign_webhook.txt', $body);
}

if (!$body) {
    $errors[] = 'Donnée absentes';
} else {
    $data = json_decode($body, 1);

    if (!is_array($data) || empty($data)) {
        $errors[] = 'Données reçues invalides';
    } else {
        if ($mode_dev) {
            echo 'DATA :<pre>';
            print_r($data);
            echo '</pre>';
        }
        
        $envelopeId = BimpTools::getArrayValueFromPath($data, 'data/envelopeId', '');

        if (!$envelopeId) {
            $errors[] = 'ID DocuSign absent';
        } else {
            $signature = BimpCache::findBimpObjectInstance('bimpcore', 'BimpSignature', array(
                        'id_envelope_docu_sign' => $envelopeId
                            ), true);

            if (!BimpObject::objectLoaded($signature)) {
                $errors[] = "Aucune signature existante pour l\'ID DocuSign " . $envelopeId;
            } else {
                $data = array(
                    'send_notification_email' => true
                );

                $success = '';
                // $return = $signature->actionDownloadSignature($data, $success); => Eviter les appels directs aux méthodes actionXXX (nécessaire pour vérfis / transactions db / etc.) 
                $refresh_errors = $signature->refreshDocuSignDocument(true, $warnings, $success);

                if (count($refresh_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($refresh_errors, 'Echec de la mise à jour des données DocuSign');
                }
            }
        }
    }
}

if (count($warnings)) {
    echo 'ALERTES :<pre>';
    print_r($warnings);
    echo '</pre>';
}

if (count($errors)) {
    BimpCore::addlog('Erreurs lors du webhook DocusignEnvelopeSigned', Bimp_Log::BIMP_LOG_URGENT, 'api', $signature, array(
        'Erreurs' => $errors
    ));
    echo 'ERREURS : <pre>';
    print_r($errors);
    echo '</pre>';
} else {
    echo 'OK : ' . $success . '<br/>';
}
